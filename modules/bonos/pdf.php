<?php

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once __DIR__ . '/bono_renderer.php';
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

App::init();
App::requireLogin();

try {
    $programaId = isset($_GET['programa_id']) ? (int)$_GET['programa_id'] : 0;

    if (!$programaId) {
        throw new Exception('ID de programa requerido.');
    }

    $renderer = new BonoRenderer($programaId);
    $html = $renderer->renderHtml(true);

    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isPhpEnabled', false);
    $options->set('dpi', 96);
    $options->set('isRemoteEnabled', true);
    $options->set('defaultFont', 'DejaVu Sans');

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $filename = 'bono-reserva-programa-' . $programaId . '.pdf';

    $dompdf->stream($filename, [
        'Attachment' => true
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo 'Error generando PDF: ' . htmlspecialchars($e->getMessage());
}