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
        $oauth2 = new Google\Service\Oauth2($this->client);
        $info   = $oauth2->userinfo->get();
        return ['email' => $info->getEmail(), 'name' => $info->getName()];
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


    public function sendEmail($to, $subject, $body) {
        $rawMessage = "To: $to\r\nSubject: $subject\r\nMIME-Version: 1.0\r\n"
            . "Content-Type: text/html; charset=utf-8\r\n\r\n$body";
        $encoded = rtrim(strtr(base64_encode($rawMessage), '+/', '-_'), '=');

        $message = new Google\Service\Gmail\Message();
        $message->setRaw($encoded);
        return $this->gmail->users_messages->send('me', $message);
    }
}
