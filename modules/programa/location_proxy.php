<?php
// ====================================================================
// ARCHIVO: modules/programa/location_proxy.php
// PROPÓSITO: Proxy para búsqueda de ubicaciones (evitar CORS)
// ====================================================================

require_once dirname(__DIR__, 2) . '/config/app.php';
App::init();
App::requireLogin(); // solo usuarios autenticados pueden usar el proxy

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

$query = $_GET['q'] ?? '';

if (empty($query)) {
    http_response_code(400);
    echo json_encode(['error' => 'Query vacío']);
    exit;
}

// Construir URL de Nominatim
$url = 'https://nominatim.openstreetmap.org/search?' . http_build_query([
    'format' => 'json',
    'q' => $query,
    'limit' => 5,
    'addressdetails' => 1
]);

// Configurar contexto para la petición
$options = [
    'http' => [
        'method' => 'GET',
        'header' => [
            'User-Agent: TravelAgency/1.0 (Contact: admin@travelagency.com)',
            'Accept: application/json',
            'Accept-Language: es-ES,es;q=0.9'
        ],
        'timeout' => 10
    ]
];

$context = stream_context_create($options);

// Realizar petición
$result = @file_get_contents($url, false, $context);

// Verificar errores
if ($result === false) {
    $error = error_get_last();
    http_response_code(500);
    echo json_encode([
        'error' => 'Error al buscar ubicación',
        'details' => $error['message'] ?? 'Unknown error'
    ]);
    exit;
}

// Verificar respuesta válida
$data = json_decode($result, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    echo json_encode(['error' => 'Respuesta inválida del servidor']);
    exit;
}

// Devolver resultados
echo json_encode($data);