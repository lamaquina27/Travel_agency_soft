<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/gmail.php';
require_once __DIR__ . '/../../config/database.php';

class GmailClient {

    private $client;
    private $gmail;
    private $db;
    private $emailAccountId;

    public function __construct($emailAccountId = null) {
        $this->db = Database::getInstance();
        $this->emailAccountId = $emailAccountId;

        $this->client = new Google\Client();
        $this->client->setClientId(GMAIL_CLIENT_ID);
        $this->client->setClientSecret(GMAIL_CLIENT_SECRET);
        $this->client->setRedirectUri(GMAIL_REDIRECT_URI);
        $this->client->setScopes(GMAIL_SCOPES);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');

        if ($emailAccountId) {
            $this->loadTokenFromDb();
        }
    }

    public function getAuthUrl() {
        return $this->client->createAuthUrl();
    }

    public function handleCallback($code) {
        $token = $this->client->fetchAccessTokenWithAuthCode($code);
        if (isset($token['error'])) {
            throw new Exception('Error OAuth: ' . $token['error']);
        }
        return $token;
    }

    public function getUserInfo() {
        $token = $this->client->getAccessToken();

        // Primary: decode the id_token JWT (issued by Google — no need to re-verify)
        if (!empty($token['id_token'])) {
            $parts   = explode('.', $token['id_token']);
            $payload = json_decode(
                base64_decode(strtr($parts[1] ?? '', '-_', '+/')), true
            );
            if ($payload && !empty($payload['email'])) {
                return [
                    'email' => $payload['email'],
                    'name'  => $payload['name'] ?? $payload['email'],
                ];
            }
        }

        // Fallback: call Google userinfo endpoint with the access token
        $accessToken = $token['access_token'] ?? '';
        $ctx = stream_context_create(['http' => [
            'header' => "Authorization: Bearer {$accessToken}\r\n",
        ]]);
        $response = @file_get_contents(
            'https://www.googleapis.com/oauth2/v3/userinfo', false, $ctx
        );
        if ($response) {
            $info = json_decode($response, true);
            if (!empty($info['email'])) {
                return [
                    'email' => $info['email'],
                    'name'  => $info['name'] ?? $info['email'],
                ];
            }
        }

        throw new Exception('No se pudo obtener información del usuario de Google.');
    }

    public function saveToken($userId, $email, $token) {
        $existing = $this->db->fetch(
            "SELECT id FROM email_accounts WHERE user_id = ? AND email = ?",
            [$userId, $email]
        );
        $tokenJson = json_encode($token);
        if ($existing) {
            $this->db->update('email_accounts',
                ['access_token' => $tokenJson, 'status' => 'active'],
                'id = ?', [$existing['id']]
            );
            return $existing['id'];
        } else {
            return $this->db->insert('email_accounts', [
                'user_id'      => $userId,
                'email'        => $email,
                'access_token' => $tokenJson,
                'provider' => 'gmail',
                'status'       => 'active',
            ]);
        }
    }

    private function loadTokenFromDb() {
        $account = $this->db->fetch(
            "SELECT access_token FROM email_accounts WHERE id = ? AND status = 'active'",
            [$this->emailAccountId]
        );
        if (!$account) return;

        $token = json_decode($account['access_token'], true);
        $this->client->setAccessToken($token);

        if ($this->client->isAccessTokenExpired()) {
            $newToken = $this->client->fetchAccessTokenWithRefreshToken(
                $this->client->getRefreshToken()
            );
            $this->db->update('email_accounts',
                ['access_token' => json_encode($newToken)],
                'id = ?', [$this->emailAccountId]
            );
            $this->client->setAccessToken($newToken);
        }

        $this->gmail = new Google\Service\Gmail($this->client);
    }

    public function getEmails($query = '', $maxResults = 20) {
        $params = ['maxResults' => $maxResults];
        if ($query) $params['q'] = $query;

        $messages = $this->gmail->users_messages->listUsersMessages('me', $params);
        $result = [];

        foreach ($messages->getMessages() ?? [] as $msg) {
            $full = $this->gmail->users_messages->get('me', $msg->getId(), ['format' => 'metadata',
                'metadataHeaders' => ['From', 'Subject', 'Date']
            ]);
            $headers = [];
            foreach ($full->getPayload()->getHeaders() as $h) {
                $headers[$h->getName()] = $h->getValue();
            }
            $result[] = [
                'id'      => $msg->getId(),
                'from'    => $headers['From']    ?? '',
                'subject' => $headers['Subject'] ?? '(sin asunto)',
                'date'    => $headers['Date']    ?? '',
                'snippet' => $full->getSnippet(),
                'unread'  => in_array('UNREAD', $full->getLabelIds() ?? []),
            ];
        }
        return $result;
    }

    public function getEmailDetail($messageId) {
        $full = $this->gmail->users_messages->get('me', $messageId, ['format' => 'full']);
        $headers = [];
        foreach ($full->getPayload()->getHeaders() as $h) {
            $headers[$h->getName()] = $h->getValue();
        }
        $body = $this->extractBody($full->getPayload());
        return [
            'id'      => $messageId,
            'from'    => $headers['From']    ?? '',
            'to'      => $headers['To']      ?? '',
            'subject' => $headers['Subject'] ?? '(sin asunto)',
            'date'    => $headers['Date']    ?? '',
            'body'    => $body,
            'unread'  => in_array('UNREAD', $full->getLabelIds() ?? []),
        ];
    }

    private function extractBody($payload) {
        if ($payload->getMimeType() === 'text/html') {
            return base64_decode(strtr($payload->getBody()->getData(), '-_', '+/'));
        }
        foreach ($payload->getParts() ?? [] as $part) {
            if ($part->getMimeType() === 'text/html') {
                return base64_decode(strtr($part->getBody()->getData(), '-_', '+/'));
            }
        }
        foreach ($payload->getParts() ?? [] as $part) {
            if ($part->getMimeType() === 'text/plain') {
                return nl2br(htmlspecialchars(
                    base64_decode(strtr($part->getBody()->getData(), '-_', '+/'))
                ));
            }
        }
        return '';
    }


    /**
     * Envía un email.
     *
     * @param string $to           Destinatario
     * @param string $subject      Asunto
     * @param string $body         Cuerpo HTML
     * @param array  $attachments  Lista de adjuntos. Cada uno:
     *                             ['filename' => string, 'mime' => string, 'data' => raw bytes]
     */
    public function sendEmail($to, $subject, $body, array $attachments = []) {
        // Codificar el subject para soportar caracteres no-ASCII (RFC 2047)
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

        if (empty($attachments)) {
            // Mensaje simple sin adjuntos
            $rawMessage = "To: $to\r\nSubject: $encodedSubject\r\nMIME-Version: 1.0\r\n"
                . "Content-Type: text/html; charset=utf-8\r\n\r\n$body";
        } else {
            // Mensaje multipart/mixed: cuerpo HTML + adjuntos
            $boundary = 'tsbnd_' . bin2hex(random_bytes(12));

            $rawMessage  = "To: $to\r\n";
            $rawMessage .= "Subject: $encodedSubject\r\n";
            $rawMessage .= "MIME-Version: 1.0\r\n";
            $rawMessage .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n\r\n";

            // Parte 1: cuerpo HTML
            $rawMessage .= "--$boundary\r\n";
            $rawMessage .= "Content-Type: text/html; charset=utf-8\r\n";
            $rawMessage .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $rawMessage .= chunk_split(base64_encode($body)) . "\r\n";

            // Parte 2..n: adjuntos
            foreach ($attachments as $att) {
                $filename = $att['filename'] ?? 'archivo';
                $mime     = $att['mime']     ?? 'application/octet-stream';
                $data     = $att['data']     ?? '';

                $rawMessage .= "--$boundary\r\n";
                $rawMessage .= "Content-Type: $mime; name=\"$filename\"\r\n";
                $rawMessage .= "Content-Transfer-Encoding: base64\r\n";
                $rawMessage .= "Content-Disposition: attachment; filename=\"$filename\"\r\n\r\n";
                $rawMessage .= chunk_split(base64_encode($data)) . "\r\n";
            }

            $rawMessage .= "--$boundary--";
        }

        $encoded = rtrim(strtr(base64_encode($rawMessage), '+/', '-_'), '=');

        $message = new Google\Service\Gmail\Message();
        $message->setRaw($encoded);
        return $this->gmail->users_messages->send('me', $message);
    }

    // =========================================================
    // Métodos para sincronización incremental (worker)
    // =========================================================

    /**
     * Devuelve el historyId actual del buzón.
     * Se guarda en email_accounts.gmail_history_id en la primera ejecución.
     */
    public function getCurrentHistoryId(): string {
        $profile = $this->gmail->users->getProfile('me');
        return (string) $profile->getHistoryId();
    }

    /**
     * Obtiene IDs de mensajes NUEVOS desde un historyId anterior.
     * Retorna array de messageIds únicos.
     */
    public function getMessageIdsSinceHistory(string $startHistoryId): array {
        $messageIds = [];
        $pageToken  = null;

        do {
            $params = [
                'startHistoryId' => $startHistoryId,
                'historyTypes'   => ['messageAdded'],
            ];
            if ($pageToken) $params['pageToken'] = $pageToken;

            $history = $this->gmail->users_history->listUsersHistory('me', $params);

            foreach ($history->getHistory() ?? [] as $record) {
                foreach ($record->getMessagesAdded() ?? [] as $added) {
                    $messageIds[] = $added->getMessage()->getId();
                }
            }

            $pageToken = $history->getNextPageToken();

        } while ($pageToken);

        return array_unique($messageIds);
    }

    /**
     * Obtiene mensajes recientes por query (primera ejecución).
     * Retorna array de messageIds.
     */
    public function getRecentMessageIds(string $query = '', int $maxResults = 50): array {
        $params = ['maxResults' => $maxResults];
        if ($query) $params['q'] = $query;

        $response = $this->gmail->users_messages->listUsersMessages('me', $params);
        $ids = [];
        foreach ($response->getMessages() ?? [] as $msg) {
            $ids[] = $msg->getId();
        }
        return $ids;
    }

    /**
     * Obtiene el mensaje completo con threadId incluido.
     * Versión extendida de getEmailDetail, usada por el worker.
     */
    public function getFullMessage(string $messageId): array {
        $full    = $this->gmail->users_messages->get('me', $messageId, ['format' => 'full']);
        $headers = [];
        foreach ($full->getPayload()->getHeaders() as $h) {
            $headers[$h->getName()] = $h->getValue();
        }
        $body = $this->extractBody($full->getPayload());
        return [
            'id'       => $messageId,
            'thread_id'=> $full->getThreadId() ?? '',
            'from'     => $headers['From']    ?? '',
            'to'       => $headers['To']      ?? '',
            'subject'  => $headers['Subject'] ?? '',
            'date'     => $headers['Date']    ?? '',
            'body'     => $body,
        ];
    }
}
