<?php
require_once 'config/app.php';
require_once 'config/database.php';

App::init();

$token = $_GET['t'] ?? null;

if (!$token) {
    die('Enlace inválido');
}

$db = Database::getInstance();

// -------------------------------------------------------
// Flujo 1: token de subagencia (subagencia_tour_precios)
// -------------------------------------------------------
$sub = $db->fetch(
    "SELECT stp.solicitud_id, stp.user_id
     FROM subagencia_tour_precios stp
     WHERE stp.public_token = ?",
    [$token]
);

if ($sub) {
    $_SESSION['subagencia_context'] = [
        'user_id'     => (int)$sub['user_id'],
        'solicitud_id'=> (int)$sub['solicitud_id'],
    ];
    header('Location: ' . APP_URL . '/preview?id=' . $sub['solicitud_id'] . '&public=1&sub=1');
    exit;
}

// -------------------------------------------------------
// Flujo 2: token original del programa (base64)
// -------------------------------------------------------
unset($_SESSION['subagencia_context']);

$decoded = base64_decode($token);
if (!$decoded || !strpos($decoded, '_')) {
    die('Token inválido');
}

$parts      = explode('_', $decoded);
$programa_id = $parts[0] ?? null;

if (!$programa_id || !is_numeric($programa_id)) {
    die('Programa no encontrado');
}

$_SESSION['temp_public_access'] = true;
$_GET['id'] = $programa_id;

$type = $_GET['type'] ?? 'preview';

if ($type === 'itinerary') {
    header('Location: ' . APP_URL . '/itinerary?id=' . $programa_id . '&public=1');
} else {
    header('Location: ' . APP_URL . '/preview?id=' . $programa_id . '&public=1');
}
exit;
