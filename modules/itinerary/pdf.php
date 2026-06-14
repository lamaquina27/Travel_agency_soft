<?php

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once __DIR__ . '/itinerary_renderer.php';

use Dompdf\Dompdf;
use Dompdf\Options;

App::init();

$isPublic = isset($_GET['public']) && $_GET['public'] == '1';

if (!$isPublic) {
    App::requireLogin();
}

try {
    $programaId = isset($_GET['programa_id']) ? (int)$_GET['programa_id'] : 0;

    if (!$programaId) {
        throw new Exception('ID de programa requerido.');
    }

    $renderer = new ItineraryRenderer($programaId);
    $html = $renderer->renderHtml();

    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isPhpEnabled', false);
    $options->set('defaultFont', 'DejaVu Sans');
    $options->set('dpi', 96);

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $filename = 'itinerary-program-' . $programaId . '.pdf';

    $dompdf->stream($filename, [
        'Attachment' => true
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo 'Error generando PDF: ' . htmlspecialchars($e->getMessage());
}