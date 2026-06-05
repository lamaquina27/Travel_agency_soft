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

    $cuentaRow = $db->fetch(
        "SELECT id FROM email_accounts WHERE user_id = ? AND provider = 'gmail' AND status = 'active' LIMIT 1",
        [$user['id']]
    );

    echo json_encode([
        'lead'             => $lead,
        'origin'           => $origin,
        'messages'         => $history,
        'email_account_id' => $cuentaRow ? (int) $cuentaRow['id'] : 0,
    ]);
    exit;
}

// ─────────────────────────────────────────────────────────────────
// POST /gmail/chat/send
// Body JSON: { pipeline_id, message_body, email_account_id }
// ─────────────────────────────────────────────────────────────────
if ($method === 'POST' && $action === 'send') {
    // Soporta dos formatos:
    //   - application/json            → mensaje sin adjuntos
    //   - multipart/form-data         → mensaje con adjuntos (archivos en $_FILES)
    $isMultipart = !empty($_FILES) || stripos($_SERVER['CONTENT_TYPE'] ?? '', 'multipart/form-data') !== false;

    if ($isMultipart) {
        $pipelineId     = (int) ($_POST['pipeline_id']     ?? 0);
        $messageBody    = trim($_POST['message_body']       ?? '');
        $emailAccountId = (int) ($_POST['email_account_id'] ?? 0);
    } else {
        $body = json_decode(file_get_contents('php://input'), true);
        $pipelineId     = (int) ($body['pipeline_id']     ?? 0);
        $messageBody    = trim($body['message_body']       ?? '');
        $emailAccountId = (int) ($body['email_account_id'] ?? 0);
    }

    // Límites de tamaño (Gmail tope ~25MB por correo)
    $MAX_FILE_SIZE  = 20 * 1024 * 1024; // 20 MB por archivo
    $MAX_TOTAL_SIZE = 25 * 1024 * 1024; // 25 MB en total

    // Recolectar adjuntos (campo de formulario "attachments[]")
    $attachments = [];
    if (!empty($_FILES['attachments'])) {
        $f = $_FILES['attachments'];
        // Normalizar a array (puede venir como un solo archivo o múltiples)
        $names = (array) $f['name'];
        $count = count($names);
        $totalSize = 0;
        for ($i = 0; $i < $count; $i++) {
            $err = $f['error'][$i] ?? UPLOAD_ERR_NO_FILE;

            // Archivo rechazado por PHP por exceder upload_max_filesize / MAX_FILE_SIZE
            if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
                http_response_code(413);
                echo json_encode(['error' => 'El archivo "' . ($f['name'][$i] ?? '') . '" supera el tamaño máximo permitido (20 MB).']);
                exit;
            }
            if ($err !== UPLOAD_ERR_OK) {
                continue;
            }

            $size = (int) ($f['size'][$i] ?? 0);
            if ($size > $MAX_FILE_SIZE) {
                http_response_code(413);
                echo json_encode(['error' => 'El archivo "' . $f['name'][$i] . '" supera el máximo de 20 MB.']);
                exit;
            }
            $totalSize += $size;
            if ($totalSize > $MAX_TOTAL_SIZE) {
                http_response_code(413);
                echo json_encode(['error' => 'Los adjuntos superan el máximo total de 25 MB.']);
                exit;
            }

            $attachments[] = [
                'filename' => $f['name'][$i],
                'mime'     => $f['type'][$i] ?: 'application/octet-stream',
                'tmp_name' => $f['tmp_name'][$i],
            ];
        }
    }

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

    $result = $chatService->sendMessage($pipelineId, $emailAccountId, $messageBody, $attachments);

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
