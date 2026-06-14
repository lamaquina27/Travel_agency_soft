<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/gmail_client.php';

App::requireLogin();

function redirectAfterOauth() {
    $user = App::getUser();
    if ($user['role'] === 'admin') {
        header('Location: ' . APP_URL . '/administrador/configuracion');
    } else {
        header('Location: ' . APP_URL . '/perfil');
    }
    exit;
}

$action = $_GET['action'] ?? '';

if ($action === 'connect') {
    $gmailClient = new GmailClient();
    $authUrl = $gmailClient->getAuthUrl();
    header('Location: ' . $authUrl);
    exit;
}

if ($action === 'callback') {
    $code  = $_GET['code']  ?? '';
    $error = $_GET['error'] ?? '';

    if ($error) {
        $_SESSION['flash_error'] = 'Autorización rechazada: ' . htmlspecialchars($error);
        redirectAfterOauth();
    }

    if (!$code) {
        $_SESSION['flash_error'] = 'No se recibió código de autorización.';
        redirectAfterOauth();
    }

    try {
        $gmailClient = new GmailClient();
        $token       = $gmailClient->handleCallback($code);

        $userInfo    = $gmailClient->getUserInfo();
        $gmailClient->saveToken($_SESSION['user_id'], $userInfo['email'], $token);

        $_SESSION['flash_success'] = 'Cuenta Gmail conectada: ' . $userInfo['email'];
    } catch (Exception $e) {
        $_SESSION['flash_error'] = 'Error al conectar Gmail: ' . $e->getMessage();
    }

    redirectAfterOauth();
}

http_response_code(400);
echo json_encode(['error' => 'Acción no válida']);
