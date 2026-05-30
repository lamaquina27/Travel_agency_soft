<?php

/**
 * Chat API — TravelSoft
 *
 * Endpoints:
 *   GET  /gmail/chat?pipeline_id=4            → historial del chat
 *   POST /gmail/chat/send                     → enviar mensaje al cliente
 *
 * Todos requieren sesión activa (App::requireLogin).
 * Responden JSON.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/ChatService.php';

// No filtrar warnings/notices al cuerpo de la respuesta (rompería el JSON)
ini_set('display_errors', '0');
error_reporting(E_ALL);

// Inicializar la app (arranca sesión y define constantes como APP_URL)
App::init();

App::requireLogin();

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

$chatService = new ChatService();
$user        = App::getUser();
$agencyId    = (int) ($user['agencia_id'] ?? 0);

// ─────────────────────────────────────────────────────────────────
// GET /gmail/chat?pipeline_id=4
// Devuelve el historial de chat + el mensaje de origen del lead
// ─────────────────────────────────────────────────────────────────
if ($method === 'GET') {
    $pipelineId = (int) ($_GET['pipeline_id'] ?? 0);

    if (!$pipelineId) {
        http_response_code(400);
        echo json_encode(['error' => 'pipeline_id requerido']);
        exit;
    }

    // Verificar que el lead pertenece a la agencia del usuario
    $db   = Database::getInstance();
    $lead = $db->fetch(
        "SELECT id, nombre_cliente, email_cliente, destino FROM pipeline WHERE id = ? AND agencia_id = ?",
        [$pipelineId, $agencyId]
    );

    if (!$lead) {
        http_response_code(404);
        echo json_encode(['error' => 'Lead no encontrado']);
        exit;
    }

    $history = $chatService->getChatHistory($pipelineId);
    $origin  = $chatService->getLeadOriginMessage($pipelineId);

    echo json_encode([
        'lead'     => $lead,
        'origin'   => $origin,   // correo que creó el lead (null si fue manual)
        'messages' => $history,
    ]);
    exit;
}

// ─────────────────────────────────────────────────────────────────
// POST /gmail/chat/send
// Body JSON: { pipeline_id, message_body, email_account_id }
// ─────────────────────────────────────────────────────────────────
if ($method === 'POST' && $action === 'send') {
    $body = json_decode(file_get_contents('php://input'), true);

    $pipelineId     = (int) ($body['pipeline_id']     ?? 0);
    $messageBody    = trim($body['message_body']       ?? '');
    $emailAccountId = (int) ($body['email_account_id'] ?? 0);

    if (!$pipelineId || !$messageBody || !$emailAccountId) {
        http_response_code(400);
        echo json_encode(['error' => 'pipeline_id, message_body y email_account_id son requeridos']);
        exit;
    }

    // Verificar que el lead pertenece a la agencia
    $db   = Database::getInstance();
    $lead = $db->fetch(
        "SELECT id FROM pipeline WHERE id = ? AND agencia_id = ?",
        [$pipelineId, $agencyId]
    );

    if (!$lead) {
        http_response_code(404);
        echo json_encode(['error' => 'Lead no encontrado']);
        exit;
    }

    // Verificar que la cuenta Gmail pertenece a un usuario de la misma agencia
    $account = $db->fetch(
        "SELECT ea.id FROM email_accounts ea
         JOIN users u ON u.id = ea.user_id
         WHERE ea.id = ? AND u.agencia_id = ? AND ea.status = 'active'",
        [$emailAccountId, $agencyId]
    );

    if (!$account) {
        http_response_code(403);
        echo json_encode(['error' => 'Cuenta Gmail no válida para esta agencia']);
        exit;
    }

    $result = $chatService->sendMessage($pipelineId, $emailAccountId, $messageBody);

    if (!$result['success']) {
        http_response_code(500);
        echo json_encode(['error' => $result['error']]);
        exit;
    }

    echo json_encode([
        'success'    => true,
        'message_id' => $result['message_id'],
    ]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Método no permitido']);
