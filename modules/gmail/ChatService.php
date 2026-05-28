<?php

require_once __DIR__ . '/gmail_client.php';
require_once __DIR__ . '/../../config/database.php';

/**
 * ChatService
 *
 * Maneja la comunicación bidireccional de email dentro de un lead del pipeline.
 *
 * Responsabilidades:
 *   - Enviar un mensaje desde TravelSoft al cliente (vía Gmail API)
 *   - Guardar el mensaje enviado en email_messages (direction=outbound, type=chat)
 *   - Devolver el historial de chat de un lead (todos los message_type='chat')
 */
class ChatService {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // =========================================================
    // Enviar mensaje desde TravelSoft
    // =========================================================

    /**
     * Envía un email al cliente del lead y lo guarda en email_messages.
     *
     * @param  int    $pipelineId      ID del lead en pipeline
     * @param  int    $emailAccountId  Cuenta Gmail desde la que se envía
     * @param  string $messageBody     Texto del mensaje (HTML permitido)
     * @return array  ['success' => bool, 'message_id' => int|null, 'error' => string|null]
     */
    public function sendMessage(int $pipelineId, int $emailAccountId, string $messageBody): array {
        // Cargar el lead
        $lead = $this->loadLead($pipelineId);
        if (!$lead) {
            return ['success' => false, 'message_id' => null, 'error' => "Lead #$pipelineId no encontrado"];
        }

        // Cargar la cuenta Gmail
        $account = $this->loadAccount($emailAccountId);
        if (!$account) {
            return ['success' => false, 'message_id' => null, 'error' => "Cuenta #$emailAccountId no encontrada o inactiva"];
        }

        $clientEmail = strtolower($lead['email_cliente']);
        $clientName  = $lead['nombre_cliente'] ?? '';

        // Construir subject para el hilo
        // Si ya hay mensajes en el chat, reutilizar el subject del primero
        $subject = $this->getThreadSubject($pipelineId, $lead);

        $gmailMessageId = null;
        try {
            $gmailClient  = new GmailClient($emailAccountId);
            $gmailMessage = $gmailClient->sendEmail(
                $clientName ? "$clientName <$clientEmail>" : $clientEmail,
                $subject,
                $messageBody
            );
            // sendEmail() devuelve un objeto Google\Service\Gmail\Message; guardamos solo su ID
            $gmailMessageId = is_object($gmailMessage) ? $gmailMessage->getId() : $gmailMessage;
        } catch (\Exception $e) {
            return ['success' => false, 'message_id' => null, 'error' => $e->getMessage()];
        }

        // Guardar en email_messages
        $dbId = $this->db->insert('email_messages', [
            'agency_id'           => $lead['agencia_id'],
            'email_account_id'    => $emailAccountId,
            'pipeline_id'         => $pipelineId,
            'provider_message_id' => $gmailMessageId,  // ID real del mensaje en Gmail
            'thread_id'           => null,
            'from_email'          => $account['email'],
            'to_email'            => $clientEmail,
            'subject'             => $subject,
            'body'                => nl2br(htmlspecialchars($messageBody, ENT_QUOTES, 'UTF-8')),
            'direction'           => 'outbound',
            'message_type'        => 'chat',
            'received_at'         => date('Y-m-d H:i:s'),
        ]);

        return ['success' => true, 'message_id' => (int) $dbId, 'error' => null];
    }

    // =========================================================
    // Historial del chat
    // =========================================================

    /**
     * Devuelve todos los mensajes de chat de un lead, ordenados por fecha.
     *
     * @param  int  $pipelineId
     * @return array  Lista de mensajes con: id, direction, from_email, to_email,
     *                subject, body, received_at
     */
    public function getChatHistory(int $pipelineId): array {
        return $this->db->fetchAll(
            "SELECT id, direction, from_email, to_email, subject, body, received_at
             FROM email_messages
             WHERE pipeline_id  = ?
               AND message_type = 'chat'
             ORDER BY received_at ASC",
            [$pipelineId]
        ) ?: [];
    }

    /**
     * Devuelve el email de creación del lead (message_type='lead') si existe.
     * Útil para el Front cuando quiere mostrar la solicitud original.
     */
    public function getLeadOriginMessage(int $pipelineId): ?array {
        $row = $this->db->fetch(
            "SELECT id, from_email, subject, body, received_at
             FROM email_messages
             WHERE pipeline_id  = ?
               AND message_type = 'lead'
             ORDER BY received_at ASC
             LIMIT 1",
            [$pipelineId]
        );
        return $row ?: null;
    }

    // =========================================================
    // Helpers
    // =========================================================

    private function loadLead(int $pipelineId): ?array {
        $row = $this->db->fetch(
            "SELECT id, agencia_id, nombre_cliente, email_cliente
             FROM pipeline
             WHERE id = ?",
            [$pipelineId]
        );
        return $row ?: null;
    }

    private function loadAccount(int $accountId): ?array {
        $row = $this->db->fetch(
            "SELECT id, email
             FROM email_accounts
             WHERE id = ? AND provider = 'gmail' AND status = 'active'",
            [$accountId]
        );
        return $row ?: null;
    }

    /**
     * Determina el subject a usar para el hilo de conversación.
     * Si ya existe un mensaje de chat, reutiliza el subject del primero.
     * Si no, construye uno limpio a partir del lead.
     */
    private function getThreadSubject(int $pipelineId, array $lead): string {
        $existing = $this->db->fetch(
            "SELECT subject FROM email_messages
             WHERE pipeline_id = ? AND message_type = 'chat'
             ORDER BY received_at ASC LIMIT 1",
            [$pipelineId]
        );

        if ($existing && !empty($existing['subject'])) {
            $subject = $existing['subject'];
            // Asegurarse de que empiece con Re: para mantener el hilo
            if (!preg_match('/^Re:/i', $subject)) {
                $subject = 'Re: ' . $subject;
            }
            return $subject;
        }

        // Primera vez: construir subject a partir del lead
        $destino = $lead['destino'] ?? 'tu viaje';
        return "[TRAVELSOFT] Conversación sobre $destino";
    }
}
