<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/gmail_client.php';

App::requireLogin();
header('Content-Type: application/json');

$action    = $_GET['action'] ?? $_POST['action'] ?? '';
$userId    = $_SESSION['user_id'];

function getActiveAccount($db, $userId) {
    return $db->fetch(
        "SELECT id, email FROM email_accounts WHERE user_id = ? AND status = 'active' LIMIT 1",
        [$userId]
    );
}

switch ($action) {

    case 'get_account_status':
        $db      = Database::getInstance();
        $account = getActiveAccount($db, $userId);
        echo json_encode([
            'success'   => true,
            'connected' => (bool) $account,
            'email'     => $account['email'] ?? null,
        ]);
        break;


    case 'get_emails':
        $db      = Database::getInstance();
        $account = getActiveAccount($db, $userId);
        if (!$account) {
            echo json_encode(['success' => false, 'error' => 'No hay cuenta Gmail conectada']);
            break;
        }
        $query      = $_GET['q']           ?? '';
        $maxResults = (int)($_GET['limit'] ?? 20);
        try {
            $gmail  = new GmailClient($account['id']);
            $emails = $gmail->getEmails($query, $maxResults);
            echo json_encode(['success' => true, 'emails' => $emails]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'get_email_detail':
        $db        = Database::getInstance();
        $account   = getActiveAccount($db, $userId);
        $messageId = $_GET['message_id'] ?? '';
        if (!$account || !$messageId) {
            echo json_encode(['success' => false, 'error' => 'Parámetros inválidos']);
            break;
        }
        try {
            $gmail  = new GmailClient($account['id']);
            $detail = $gmail->getEmailDetail($messageId);
            echo json_encode(['success' => true, 'email' => $detail]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'send_email':
        App::requireRole(['admin', 'agent']);
        $db      = Database::getInstance();
        $account = getActiveAccount($db, $userId);
        if (!$account) {
            echo json_encode(['success' => false, 'error' => 'No hay cuenta Gmail conectada']);
            break;
        }
        $to      = $_POST['to']      ?? '';
        $subject = $_POST['subject'] ?? '';
        $body    = $_POST['body']    ?? '';
        if (!$to || !$subject || !$body) {
            echo json_encode(['success' => false, 'error' => 'Faltan campos requeridos']);
            break;
        }
        try {
            $gmail = new GmailClient($account['id']);
            $gmail->sendEmail($to, $subject, $body);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'disconnect':
        App::requireRole(['admin', 'agent']);
        $db = Database::getInstance();
        $db->update('email_accounts', ['status' => 'inactive'], 'user_id = ?', [$userId]);
        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Acción no válida']);
        break;
}
