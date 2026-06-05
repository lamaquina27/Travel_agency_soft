<?php

require_once __DIR__ . '/EmailParser.php';
require_once __DIR__ . '/../../config/database.php';

/**
 * RuleEngine
 *
 * Evalúa las reglas (email_rules) de una agencia contra un mensaje
 * recién guardado en email_messages y ejecuta las acciones correspondientes.
 *
 * Comportamiento por defecto (sin reglas configuradas):
 *   Si el correo tiene [TRAVELSOFT] en el asunto, se intenta crear un lead.
 *
 * Prioridad de reglas: menor número = mayor prioridad (campo priority).
 *
 * Acciones posibles:
 *   ignore        → No crear lead. Marcar como procesado. STOP.
 *   create_lead   → Parsear + crear lead. STOP.
 *   assign_status → Acumular: establece el estado del lead a crear.
 *   assign_user   → Acumular: asigna el asesor del lead a crear.
 *   add_tag       → Acumular: agrega tag al lead a crear.
 *
 * Deduplicación de leads:
 *   No se crea un lead nuevo si ya existe uno con el mismo email_cliente
 *   para la misma agencia en los últimos 30 días.
 *   En ese caso, el mensaje se vincula al lead existente.
 */
class RuleEngine {

    private Database $db;

    // Ventana de deduplicación de leads (días)
    private const DEDUP_WINDOW_DAYS = 30;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // =========================================================
    // Punto de entrada
    // =========================================================

    /**
     * Procesa un email_message recién guardado.
     *
     * @param  int    $emailMessageId  ID en email_messages
     * @param  int    $agencyId
     * @param  int    $emailAccountId
     * @return array  [
     *   'action'   => string,  // 'lead_created' | 'lead_existing' | 'ignored' | 'parse_error' | 'no_action'
     *   'lead_id'  => int|null,
     *   'detail'   => string   // descripción legible del resultado
     * ]
     */
    public function process(int $emailMessageId, int $agencyId, int $emailAccountId): array {
        $message = $this->loadMessage($emailMessageId);
        if (!$message) {
            return ['action' => 'no_action', 'lead_id' => null, 'detail' => "Message $emailMessageId not found"];
        }

        // Cargar reglas activas de la agencia para esta cuenta, ordenadas por prioridad
        $rules = $this->loadRules($agencyId, $emailAccountId);

        // Acumuladores para reglas de tipo assign_*
        $overrides = [
            'estado_id'  => null,
            'usuario_id' => null,
            'tag_id'     => null,
        ];

        $shouldCreateLead = true;  // comportamiento por defecto

        foreach ($rules as $rule) {
            if (!$this->matchesCondition($rule, $message)) {
                continue;
            }

            switch ($rule['action_type']) {
                case 'ignore':
                    return ['action' => 'ignored', 'lead_id' => null,
                            'detail' => "Rule #{$rule['id']} '{$rule['nombre']}': ignored"];

                case 'create_lead':
                    // Seguir procesando para acumular assigns, pero forzar creación
                    $shouldCreateLead = true;
                    break;

                case 'assign_status':
                    $overrides['estado_id'] = (int) $rule['pipeline_estado_id'];
                    break;

                case 'assign_user':
                    $overrides['usuario_id'] = (int) $rule['usuario_asignado_id'];
                    break;

                case 'add_tag':
                    // Resolver nombre de tag a ID
                    $tagId = $this->resolveTagId($rule['tag'], $agencyId);
                    if ($tagId) $overrides['tag_id'] = $tagId;
                    break;
            }
        }

        if (!$shouldCreateLead) {
            return ['action' => 'no_action', 'lead_id' => null, 'detail' => 'No matching rule triggered lead creation'];
        }

        // Intentar crear el lead
        return $this->createLeadFromMessage($message, $agencyId, $overrides);
    }

    // =========================================================
    // Creación del lead
    // =========================================================

    private function createLeadFromMessage(array $message, int $agencyId, array $overrides): array {
        // Parsear el body del correo
        $parsed = EmailParser::parse($message['body'] ?? '');

        if (!$parsed['valid']) {
            $errorList = implode(' | ', $parsed['errors']);
            return [
                'action'  => 'parse_error',
                'lead_id' => null,
                'detail'  => "Parse failed: $errorList",
            ];
        }

        $data = $parsed['data'];

        // Verificar deduplicación de leads
        $existingLead = $this->findExistingLead($data['email_cliente'], $agencyId);
        if ($existingLead) {
            // Vincular este mensaje al lead existente
            $this->db->update(
                'email_messages',
                ['pipeline_id' => $existingLead['id']],
                'id = ?',
                [$message['id']]
            );
            return [
                'action'  => 'lead_existing',
                'lead_id' => $existingLead['id'],
                'detail'  => "Linked to existing lead #{$existingLead['id']} (same email within " . self::DEDUP_WINDOW_DAYS . " days)",
            ];
        }

        // Resolver estado inicial
        $estadoId = $overrides['estado_id'] ?? $this->getDefaultEstadoId($agencyId);
        if (!$estadoId) {
            return [
                'action'  => 'no_action',
                'lead_id' => null,
                'detail'  => "No pipeline states configured for agency $agencyId",
            ];
        }

        // Construir datos del lead
        $leadData = [
            'agencia_id'                    => $agencyId,
            'estado_id'                     => $estadoId,
            'nombre_cliente'                => $data['nombre_cliente'],
            'email_cliente'                 => $data['email_cliente'],
            'destino'                       => $data['destino'],
            'fecha_salida'                  => $data['fecha_salida'],
            'source'                        => 'email',
            'created_from_email_message_id' => $message['id'],
        ];

        // Campos opcionales: solo si el parser los extrajo
        if (!empty($data['telefono_cliente'])) $leadData['telefono_cliente'] = $data['telefono_cliente'];
        if (!empty($data['fecha_llegada']))    $leadData['fecha_llegada']    = $data['fecha_llegada'];
        if (isset($data['viajeros']))          $leadData['viajeros']         = $data['viajeros'];
        if (!empty($data['budget']))           $leadData['budget']           = $data['budget'];
        if (!empty($data['descripcion']))      $leadData['descripcion']      = $data['descripcion'];

        // Overrides de reglas
        if ($overrides['usuario_id']) $leadData['usuario_id'] = $overrides['usuario_id'];
        if ($overrides['tag_id'])     $leadData['tag_id']     = $overrides['tag_id'];

        // INSERT en pipeline
        $leadId = $this->db->insert('pipeline', $leadData);

        // Vincular el mensaje al lead recién creado
        $this->db->update(
            'email_messages',
            ['pipeline_id' => $leadId],
            'id = ?',
            [$message['id']]
        );

        return [
            'action'  => 'lead_created',
            'lead_id' => $leadId,
            'detail'  => "Lead #$leadId created for {$data['email_cliente']} → {$data['destino']}",
        ];
    }

    // =========================================================
    // Evaluación de condición de una regla
    // =========================================================

    private function matchesCondition(array $rule, array $message): bool {
        $field    = $rule['condition_field'];
        $operator = $rule['operator'];
        $value    = $rule['value'];

        // Mapear condition_field al dato del mensaje
        $subject = match ($field) {
            'from'           => $message['from_email'] ?? '',
            'to'             => $message['to_email']   ?? '',
            'subject'        => $message['subject']    ?? '',
            'body'           => $message['body']       ?? '',
            'has_attachment' => '',  // no implementado en esta versión
            default          => '',
        };

        // Comparación insensible a mayúsculas
        $haystack = strtolower($subject);
        $needle   = strtolower($value);

        return match ($operator) {
            'contains'     =>  str_contains($haystack, $needle),
            'not_contains' => !str_contains($haystack, $needle),
            'equals'       =>  $haystack === $needle,
            'starts_with'  =>  str_starts_with($haystack, $needle),
            'ends_with'    =>  str_ends_with($haystack, $needle),
            default        => false,
        };
    }

    // =========================================================
    // Helpers de BD
    // =========================================================

    private function loadMessage(int $id): ?array {
        $row = $this->db->fetch(
            "SELECT id, from_email, to_email, subject, body FROM email_messages WHERE id = ?",
            [$id]
        );
        return $row ?: null;
    }

    private function loadRules(int $agencyId, int $emailAccountId): array {
        return $this->db->fetchAll(
            "SELECT * FROM email_rules
             WHERE agency_id = ?
               AND email_account_id = ?
               AND is_active = 1
             ORDER BY priority ASC",
            [$agencyId, $emailAccountId]
        ) ?: [];
    }

    /**
     * Busca un lead existente para el mismo email dentro de la ventana de dedup.
     */
    private function findExistingLead(string $emailCliente, int $agencyId): ?array {
        $row = $this->db->fetch(
            "SELECT id FROM pipeline
             WHERE agencia_id    = ?
               AND email_cliente = ?
               AND created_at   >= NOW() - INTERVAL " . self::DEDUP_WINDOW_DAYS . " DAY
             ORDER BY created_at DESC
             LIMIT 1",
            [$agencyId, $emailCliente]
        );
        return $row ?: null;
    }

    /**
     * Obtiene el estado con posición 0 de la agencia (estado inicial por defecto).
     */
    private function getDefaultEstadoId(int $agencyId): ?int {
        $estado = $this->db->fetch(
            "SELECT id FROM pipeline_estados
             WHERE agencia_id = ?
             ORDER BY posicion ASC
             LIMIT 1",
            [$agencyId]
        );
        return $estado ? (int) $estado['id'] : null;
    }

    /**
     * Resuelve un nombre de tag a su ID para la agencia.
     */
    private function resolveTagId(string $tagName, int $agencyId): ?int {
        $tag = $this->db->fetch(
            "SELECT id FROM tags WHERE nombre = ? AND agencia_id = ?",
            [trim($tagName), $agencyId]
        );
        return $tag ? (int) $tag['id'] : null;
    }
}
