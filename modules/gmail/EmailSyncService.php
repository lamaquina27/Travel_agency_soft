<?php

require_once __DIR__ . '/gmail_client.php';
require_once __DIR__ . '/../../config/database.php';

/**
 * EmailSyncService
 *
 * Sincroniza correos nuevos de una cuenta Gmail activa hacia la tabla email_messages.
 *
 * Estrategia incremental:
 *   - Primera ejecución : descarga los últimos 50 correos con [TRAVELSOFT] en el asunto.
 *                         Guarda el historyId actual para la próxima vez.
 *   - Ejecuciones siguientes: usa Gmail History API para traer SOLO los mensajes
 *                         nuevos desde el último historyId conocido.
 *
 * Deduplicación:
 *   email_messages tiene UNIQUE KEY (email_account_id, provider_message_id).
 *   Si un mensaje ya existe el INSERT falla silenciosamente → cero duplicados en BD.
 */
class EmailSyncService {

    private Database $db;

    // Query de Gmail para filtrar correos relevantes
    private const GMAIL_QUERY = 'subject:[TRAVELSOFT]';

    // Límite de correos a descargar en la primera ejecución
    private const INITIAL_FETCH_LIMIT = 50;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // =========================================================
    // Punto de entrada principal
    // =========================================================

    /**
     * Sincroniza una cuenta Gmail.
     *
     * @param  int   $accountId   ID en email_accounts
     * @return array [
     *   'new_messages'  => int,   // Mensajes nuevos guardados en BD
     *   'skipped'       => int,   // Mensajes ya existentes (dedup)
     *   'errors'        => int,   // Mensajes que fallaron al guardarse
     *   'new_history_id'=> string // historyId actualizado
     * ]
     */
    public function syncAccount(int $accountId): array {
        $account = $this->loadAccount($accountId);
        if (!$account) {
            throw new \RuntimeException("Account $accountId not found or inactive");
        }

        $gmail  = new GmailClient($accountId);
        $result = [
            'new_messages'    => 0,
            'skipped'         => 0,
            'errors'          => 0,
            'new_history_id'  => '',
            'new_message_ids' => [],   // IDs reales en email_messages (solo los de esta pasada)
        ];

        // Obtener IDs de mensajes nuevos
        $messageIds = $this->fetchNewMessageIds($gmail, $account);

        if (empty($messageIds)) {
            // Sin mensajes nuevos: actualizar historyId igual
            $currentHistoryId = $gmail->getCurrentHistoryId();
            $this->updateHistoryId($accountId, $currentHistoryId);
            $result['new_history_id'] = $currentHistoryId;
            return $result;
        }

        // Obtener agencia_id del usuario dueño de la cuenta
        $agencyId = $this->getAgencyId($account['user_id']);
        if (!$agencyId) {
            throw new \RuntimeException("No agency found for user {$account['user_id']}");
        }

        // Procesar cada mensaje
        foreach ($messageIds as $messageId) {
            try {
                [$status, $dbId] = $this->processMessage($gmail, $messageId, $accountId, $agencyId);
                if ($status === 'new') {
                    $result['new_messages']++;
                    if ($dbId) $result['new_message_ids'][] = $dbId;
                }
                if ($status === 'skipped')  $result['skipped']++;
                // 'filtered' = no tiene [TRAVELSOFT] → se ignora, no se guarda
            } catch (\Exception $e) {
                $result['errors']++;
                throw $e;
            }
        }

        // Guardar el historyId más reciente
        $newHistoryId = $gmail->getCurrentHistoryId();
        $this->updateHistoryId($accountId, $newHistoryId);
        $result['new_history_id'] = $newHistoryId;

        return $result;
    }

    // =========================================================
    // Obtención de IDs de mensajes
    // =========================================================

    /**
     * Decide si usar History API (ejecuciones posteriores)
     * o fetch inicial (primera vez).
     */
    private function fetchNewMessageIds(GmailClient $gmail, array $account): array {
        $historyId = $account['gmail_history_id'] ?? null;

        if ($historyId) {
            // Sincronización incremental: solo lo nuevo
            return $gmail->getMessageIdsSinceHistory($historyId);
        }

        // Primera ejecución: últimos N correos con [TRAVELSOFT]
        return $gmail->getRecentMessageIds(self::GMAIL_QUERY, self::INITIAL_FETCH_LIMIT);
    }

    // =========================================================
    // Procesamiento de un mensaje individual
    // =========================================================

    /**
     * Descarga el mensaje completo y lo inserta en email_messages.
     *
     * @return string 'new' | 'skipped'
     */
    /**
     * @return array{0: string, 1: int|null}  ['new'|'skipped'|'filtered', dbId|null]
     */
    private function processMessage(GmailClient $gmail, string $messageId, int $accountId, int $agencyId): array {
        $msg = $gmail->getFullMessage($messageId);

        // Solo procesar correos con la palabra clave en el subject.
        // Esto filtra tanto en sync incremental (History API) como en primera ejecución.
        if (stripos($msg['subject'], '[TRAVELSOFT]') === false) {
            return ['filtered', null];
        }

        // Parsear fecha de recepción
        $receivedAt = $msg['date'] ? date('Y-m-d H:i:s', strtotime($msg['date'])) : null;

        try {
            $dbId = $this->db->insert('email_messages', [
                'agency_id'          => $agencyId,
                'email_account_id'   => $accountId,
                'pipeline_id'        => null,
                'provider_message_id'=> $msg['id'],
                'thread_id'          => $msg['thread_id'],
                'from_email'         => $this->extractEmailAddress($msg['from']),
                'to_email'           => $this->extractEmailAddress($msg['to']),
                'subject'            => substr($msg['subject'], 0, 998),
                'body'               => $msg['body'],
                'direction'          => 'inbound',
                'received_at'        => $receivedAt,
            ]);
            return ['new', (int) $dbId];

        } catch (\PDOException $e) {
            // Código 23000 = violación de constraint (UNIQUE KEY duplicado)
            if (str_starts_with($e->getCode(), '23')) {
                return ['skipped', null];
            }
            throw $e;
        }
    }

    // =========================================================
    // Helpers
    // =========================================================

    private function loadAccount(int $accountId): ?array {
        $row = $this->db->fetch(
            "SELECT id, user_id, gmail_history_id
             FROM email_accounts
             WHERE id = ? AND provider = 'gmail' AND status = 'active'",
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

    private function updateHistoryId(int $accountId, string $historyId): void {
        $this->db->update(
            'email_accounts',
            ['gmail_history_id' => $historyId, 'last_synced_at' => date('Y-m-d H:i:s')],
            'id = ?',
            [$accountId]
        );
    }

    /**
     * Extrae la dirección de email limpia desde un header "From" o "To".
     * Ejemplos:
     *   "Juan García <juan@gmail.com>"  → "juan@gmail.com"
     *   "juan@gmail.com"                → "juan@gmail.com"
     */
    private function extractEmailAddress(string $header): string {
        if (preg_match('/<([^>]+)>/', $header, $m)) {
            return strtolower(trim($m[1]));
        }
        return strtolower(trim($header));
    }

    // =========================================================
    // Método público: lista todas las cuentas activas
    // Usado por el worker para iterar sobre todas las agencias.
    // =========================================================

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
