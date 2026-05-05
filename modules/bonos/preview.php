<?php

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once __DIR__ . '/bono_renderer.php';

App::init();
App::requireLogin();

try {
    $programaId = isset($_GET['programa_id']) ? (int)$_GET['programa_id'] : 0;

    if (!$programaId) {
        throw new Exception('ID de programa requerido.');
    }

    $hotelsPerPage = isset($_GET['hotels_per_page']) ? (int)$_GET['hotels_per_page'] : 1;
    $renderer = new BonoRenderer($programaId, $hotelsPerPage);
    echo $renderer->renderHtml(false);

} catch (Exception $e) {
    http_response_code(400);
    echo '<h1>Error generando bono</h1>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
}