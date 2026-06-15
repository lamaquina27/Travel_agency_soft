<?php

require_once __DIR__ . '/gmail_client.php';
require_once __DIR__ . '/../../config/database.php';

/**
 * EmailSyncService
 *
 * Sincroniza correos nuevos de una cuenta Gmail activa hacia email_messages.
 *
 * Clasifica cada mensaje en dos categorías:
 *
 *   message_type = 'lead'
 *     Subject contiene [TRAVELSOFT] → correo de creación de lead.
 *     El RuleEngine decide si crear lead nuevo o vincular a uno existente.
 *
 *   message_type = 'chat'
 *     from_email coincide con pipeline.email_cliente de un lead activo → conversación.
 *     Se vincula directamente al lead activo más reciente del cliente.
 *     También detecta mensajes enviados por el agente desde Gmail (outbound chat).
 *
 * Mensajes que no son ni lead ni chat → se descartan, NO se guardan en BD.
 *
 * Sync incremental:
 *   Primera ejecución: últimos 50 correos con [TRAVELSOFT] + clientes conocidos.
 *   Ejecuciones siguientes: Gmail History API → solo mensajes nuevos.
 */
class EmailSyncService {

    private Database $db;

    private const GMAIL_QUERY        = 'subject:[TRAVELSOFT]';
    private const INITIAL_FETCH_LIMIT = 50;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // =========================================================
    // Punto de entrada principal
    // =========================================================

    public function syncAccount(int $accountId): array {
        $account = $this->loadAccount($accountId);
        if (!$account) {
            throw new \RuntimeException("Account $accountId not found or inactive");
        }

        $gmail  = new GmailClient($accountId);
        $result = [
            'new_messages'    => 0,
            'skipped'         => 0,
            'new_history_id'  => '',
            'new_message_ids' => [],
        ];

        $messageIds = $this->fetchNewMessageIds($gmail, $account);

        if (empty($messageIds)) {
            $currentHistoryId = $gmail->getCurrentHistoryId();
            $this->updateHistoryId($accountId, $currentHistoryId);
            $result['new_history_id'] = $currentHistoryId;
            return $result;
        }

        $agencyId = $this->getAgencyId($account['user_id']);
        if (!$agencyId) {
            throw new \RuntimeException("No agency found for user {$account['user_id']}");
        }

        // Email de la cuenta de la agencia (para detectar outbound desde Gmail)
        $accountEmail = $account['email'] ?? '';

        foreach ($messageIds as $messageId) {
            try {
                [$status, $dbId] = $this->processMessage(
                    $gmail, $messageId, $accountId, $agencyId, $accountEmail
                );
                if ($status === 'new') {
                    $result['new_messages']++;
                    if ($dbId) $result['new_message_ids'][] = $dbId;
                }
                if ($status === 'skipped') $result['skipped']++;
                // 'filtered' → no relevante, se descarta sin guardar nada
            } catch (\Exception $e) {
                // No abortar todo el lote por el fallo de un solo mensaje:
                // se registra y se continúa con los demás (evita reprocesar todo).
                error_log('EmailSync processMessage ' . $messageId . ': ' . $e->getMessage());
                $result['skipped']++;
            }
        }

        $newHistoryId = $gmail->getCurrentHistoryId();
        $this->updateHistoryId($accountId, $newHistoryId);
        $result['new_history_id'] = $newHistoryId;

        return $result;
    }

    // =========================================================
    // Obtención de IDs de mensajes
    // =========================================================

    private function fetchNewMessageIds(GmailClient $gmail, array $account): array {
        $historyId = $account['gmail_history_id'] ?? null;

        if ($historyId) {
            return $gmail->getMessageIdsSinceHistory($historyId);
        }

        // Primera ejecución: solo correos con [TRAVELSOFT]
        return $gmail->getRecentMessageIds(self::GMAIL_QUERY, self::INITIAL_FETCH_LIMIT);
    }

    // =========================================================
    // Procesamiento de un mensaje individual
    // =========================================================

    /**
     * Descarga el mensaje y decide si guardarlo como 'lead', 'chat' o descartarlo.
     *
     * Lógica:
     *   1. ¿from_email = email de la cuenta de la agencia?
     *      → Agente envió desde Gmail. Verificar si to_email es cliente conocido → chat outbound.
     *
     *   2. ¿from_email coincide con pipeline.email_cliente de un lead activo?
     *      → Mensaje directo del cliente → chat inbound.
     *
     *   3. ¿Subject contiene [TRAVELSOFT]?
     *      → Solicitud nueva (formulario u otro origen) → lead.
     *
     *   4. Ninguna condición → descartar (no se guarda nada).
     *
     * @return array{0: string, 1: int|null}  ['new'|'skipped'|'filtered', dbId|null]
     */
    private function processMessage(
        GmailClient $gmail,
        string      $messageId,
        int         $accountId,
        int         $agencyId,
        string      $accountEmail
    ): array {
        try {
            $msg = $gmail->getFullMessage($messageId);
        } catch (\Google\Service\Exception $e) {
            // Mensaje ya no existe en Gmail (borrado antes de procesarlo)
            if ($e->getCode() === 404) return ['filtered', null];
            throw $e;
        }

        $fromEmail  = $this->extractEmailAddress($msg['from']);
        $toEmail    = $this->extractEmailAddress($msg['to']);
        $receivedAt = $msg['date'] ? date('Y-m-d H:i:s', strtotime($msg['date'])) : null;

        // ── Caso 1: outbound desde Gmail (el agente respondió directamente) ──────
        if ($fromEmail === strtolower($accountEmail)) {
            $lead = $this->findActiveLeadByClientEmail($toEmail, $agencyId);
            if ($lead) {
                return $this->saveMessage($msg, $accountId, $agencyId, [
                    'pipeline_id'  => $lead['id'],
                    'direction'    => 'outbound',
                    'message_type' => 'chat',
                    'from_email'   => $fromEmail,
                    'to_email'     => $toEmail,
                    'received_at'  => $receivedAt,
                ]);
            }
            // El agente envió a alguien que no es cliente → descartar
            return ['filtered', null];
        }

        // ── Caso 2: inbound de un cliente conocido → chat ────────────────────────
        $lead = $this->findActiveLeadByClientEmail($fromEmail, $agencyId);
        if ($lead) {
            return $this->saveMessage($msg, $accountId, $agencyId, [
                'pipeline_id'  => $lead['id'],
                'direction'    => 'inbound',
                'message_type' => 'chat',
                'from_email'   => $fromEmail,
                'to_email'     => $toEmail,
                'received_at'  => $receivedAt,
            ]);
        }

        // ── Caso 3: solicitud nueva con [TRAVELSOFT] → lead ──────────────────────
        if (stripos($msg['subject'], '[TRAVELSOFT]') !== false) {
            return $this->saveMessage($msg, $accountId, $agencyId, [
                'pipeline_id'  => null,
                'direction'    => 'inbound',
                'message_type' => 'lead',
                'from_email'   => $fromEmail,
                'to_email'     => $toEmail,
                'received_at'  => $receivedAt,
            ]);
        }

        // ── Caso 4: no relevante ─────────────────────────────────────────────────
        return ['filtered', null];
    }

    /**
     * Inserta el mensaje en email_messages.
     * @return array{0: string, 1: int|null}
     */
    private function saveMessage(
        array  $msg,
        int    $accountId,
        int    $agencyId,
        array  $meta
    ): array {
        // INSERT IGNORE: si ya existe el provider_message_id para esta cuenta,
        // MySQL lo descarta silenciosamente (rowCount = 0) en lugar de lanzar excepción.
        $data = [
            'agency_id'           => $agencyId,
            'email_account_id'    => $accountId,
            'pipeline_id'         => $meta['pipeline_id'],
            'provider_message_id' => $msg['id'],
            'thread_id'           => $msg['thread_id'],
            'from_email'          => $meta['from_email'],
            'to_email'            => $meta['to_email'],
            'subject'             => substr($msg['subject'], 0, 998),
            'body'                => $msg['body'],
            'direction'           => $meta['direction'],
            'message_type'        => $meta['message_type'],
            'received_at'         => $meta['received_at'],
        ];

        $columns      = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql          = "INSERT IGNORE INTO email_messages ({$columns}) VALUES ({$placeholders})";

        $pdo  = $this->db->getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);

        if ($stmt->rowCount() === 0) {
            return ['skipped', null];   // ya existía — duplicado ignorado
        }

        return ['new', (int) $pdo->lastInsertId()];
    }

    // =========================================================
    // Helpers de BD
    // =========================================================

    private function loadAccount(int $accountId): ?array {
        $row = $this->db->fetch(
            "SELECT ea.id, ea.user_id, ea.email, ea.gmail_history_id
             FROM email_accounts ea
             WHERE ea.id = ? AND ea.provider = 'gmail' AND ea.status = 'active'",
            [$accountId]
        );
        return $row ?: null;
    }

    private function getAgencyId(int $userId): ?int {
        $user = $this->db->fetch(
            "SELECT agencia_id FROM users WHERE id = ?",
            [$userId]
        );
        return ($user && $user['agencia_id']) ? (int) $user['agencia_id'] : null;
    }

    /**
     * Busca el lead activo más reciente cuyo email_cliente coincida.
     * "Activo" = estado no marcado como es_final en pipeline_estados.
     */
    private function findActiveLeadByClientEmail(string $email, int $agencyId): ?array {
        if (empty($email)) return null;

        $row = $this->db->fetch(
            "SELECT p.id
             FROM pipeline p
             JOIN pipeline_estados pe ON pe.id = p.estado_id
             WHERE p.agencia_id    = ?
               AND p.email_cliente = ?
               AND pe.es_final     = 0
             ORDER BY p.created_at DESC
             LIMIT 1",
            [$agencyId, strtolower($email)]
        );
        return $row ?: null;
    }

    private function updateHistoryId(int $accountId, string $historyId): void {
        $this->db->update(
            'email_accounts',
            ['gmail_history_id' => $historyId, 'last_synced_at' => date('Y-m-d H:i:s')],
            'id = ?',
            [$accountId]
        );
    }

    private function extractEmailAddress(string $header): string {
        if (preg_match('/<([^>]+)>/', $header, $m)) {
            return strtolower(trim($m[1]));
        }
        return strtolower(trim($header));
    }

    public function getActiveAccounts(): array {
        return $this->db->fetchAll(
            "SELECT ea.id, ea.email, ea.gmail_history_id, u.agencia_id
             FROM email_accounts ea
             JOIN users u ON u.id = ea.user_id
             WHERE ea.provider = 'gmail'
               AND ea.status   = 'active'
               AND u.agencia_id IS NOT NULL",
            []
        );
    }
}
