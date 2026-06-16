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
     * @param  array  $attachments     Adjuntos subidos. Cada uno:
     *                                 ['filename' => string, 'mime' => string, 'tmp_name' => string]
     * @return array  ['success' => bool, 'message_id' => int|null, 'error' => string|null]
     */
    public function sendMessage(int $pipelineId, int $emailAccountId, string $messageBody, array $attachments = []): array {
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

        // El cuerpo que se guarda conserva el formato (negrita, etc.) que el agente
        // escribió en el editor; se sanea sin convertir el HTML a entidades, porque
        // el historial se pinta con innerHTML (htmlspecialchars haría visibles las etiquetas).
        $storedBody = $this->sanitizeChatHtml($messageBody);

        // Procesar adjuntos: guardarlos en disco (para mostrarlos en el historial)
        // y preparar el payload binario para Gmail.
        $gmailAttachments = [];
        $savedFiles       = [];  // [['url' => ..., 'name' => ...], ...]
        foreach ($attachments as $att) {
            $tmp = $att['tmp_name'] ?? '';
            if (!$tmp || !is_file($tmp)) {
                continue;
            }
            $raw = file_get_contents($tmp);
            if ($raw === false) {
                continue;
            }
            $filename = $this->sanitizeFilename($att['filename'] ?? 'archivo');
            $mime     = $att['mime'] ?? 'application/octet-stream';

            $gmailAttachments[] = ['filename' => $filename, 'mime' => $mime, 'data' => $raw];

            $saved = $this->storeAttachment((int) $lead['agencia_id'], $tmp, $filename);
            if ($saved) {
                $savedFiles[] = ['url' => $saved, 'name' => $filename];
            } else {
                // No se pudo guardar en disco (permisos/ruta): el archivo SÍ se envió por
                // Gmail, así que al menos dejamos constancia del nombre para no mostrar
                // una burbuja "vacía" en el historial.
                error_log("ChatService: no se pudo guardar el adjunto '$filename' (agencia {$lead['agencia_id']})");
                $savedFiles[] = ['url' => null, 'name' => $filename];
            }
        }

        // Adjuntar la lista de archivos al final del cuerpo guardado (vista en historial)
        if ($savedFiles) {
            $storedBody .= $this->renderAttachmentsHtml($savedFiles);
        }

        $gmailMessageId = null;
        try {
            $gmailClient  = new GmailClient($emailAccountId);
            $gmailMessage = $gmailClient->sendEmail(
                $clientName ? "$clientName <$clientEmail>" : $clientEmail,
                $subject,
                $messageBody,
                $gmailAttachments
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
            'body'                => $storedBody,
            'direction'           => 'outbound',
            'message_type'        => 'chat',
            'received_at'         => date('Y-m-d H:i:s'),
        ]);

        return ['success' => true, 'message_id' => (int) $dbId, 'error' => null];
    }

    /**
     * Guarda un adjunto en disco siguiendo la convención de uploads del proyecto:
     *   assets/uploads/agencia_<id>/chat/YYYY/MM/<archivo>
     * Devuelve la URL pública (vía APP_URL) o null si falla.
     */
    private function storeAttachment(int $agenciaId, string $tmpPath, string $filename): ?string {
        $year  = date('Y');
        $month = date('m');

        $baseDir  = dirname(__DIR__, 2) . '/assets/uploads/agencia_' . $agenciaId . '/chat';
        $monthDir = $baseDir . '/' . $year . '/' . $month;

        if (!is_dir($monthDir) && !mkdir($monthDir, 0755, true) && !is_dir($monthDir)) {
            return null;
        }

        // Nombre único para evitar colisiones
        $ext      = pathinfo($filename, PATHINFO_EXTENSION);
        $baseName = pathinfo($filename, PATHINFO_FILENAME);
        $unique   = $baseName . '_' . time() . '_' . bin2hex(random_bytes(4)) . ($ext ? '.' . $ext : '');

        $fullPath = $monthDir . '/' . $unique;

        // move_uploaded_file falla si el archivo no es un upload válido; copy como fallback
        if (!@move_uploaded_file($tmpPath, $fullPath) && !@copy($tmpPath, $fullPath)) {
            return null;
        }

        return APP_URL . '/assets/uploads/agencia_' . $agenciaId . '/chat/' . $year . '/' . $month . '/' . $unique;
    }

    /**
     * Limpia un nombre de archivo de caracteres peligrosos para rutas.
     */
    private function sanitizeFilename(string $name): string {
        $name = basename($name);
        $name = preg_replace('/[^\w.\- ]+/u', '_', $name);
        return $name !== '' ? $name : 'archivo';
    }

    /**
     * Genera el bloque HTML con la lista de adjuntos para incrustar en el body guardado.
     */
    private function renderAttachmentsHtml(array $files): string {
        $items = '';
        foreach ($files as $f) {
            $name = htmlspecialchars($f['name'], ENT_QUOTES, 'UTF-8');
            $icon = '<i class="fas fa-paperclip"></i> ';
            if (!empty($f['url'])) {
                $url = htmlspecialchars($f['url'], ENT_QUOTES, 'UTF-8');
                $items .= "<div class=\"chat-attachment\">$icon<a href=\"$url\" target=\"_blank\" rel=\"noopener\">$name</a></div>";
            } else {
                // Sin URL (fallo al guardar en disco): mostrar el nombre sin enlace.
                $items .= "<div class=\"chat-attachment\">$icon<span>$name</span></div>";
            }
        }
        return "<div class=\"chat-attachments\">$items</div>";
    }

    /**
     * Sanea el HTML de un mensaje de chat conservando solo etiquetas de formato
     * seguras. No convierte el HTML a entidades (el historial se pinta con innerHTML).
     */
    private function sanitizeChatHtml(string $html): string {
        $allowed = '<b><strong><i><em><u><s><strike><br><div><span><p><a><ul><ol><li><blockquote>';
        $clean = strip_tags($html, $allowed);
        // Defensa XSS: quitar manejadores de eventos (onclick, onmouseover, …)
        $clean = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $clean);
        // Neutralizar href/src con javascript:
        $clean = preg_replace('/(href|src)\s*=\s*("javascript:[^"]*"|\'javascript:[^\']*\')/i', '$1="#"', $clean);
        // Si el agente escribió texto plano (sin etiquetas), respetar los saltos de línea.
        if ($clean === strip_tags($clean)) {
            $clean = nl2br($clean);
        }
        return $clean;
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
            "SELECT id, agencia_id, nombre_cliente, email_cliente, destino
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
