<?php

ini_set('memory_limit', '1024M');
set_time_limit(360);

if (isset($_GET['debug']) && $_GET['debug'] == '1') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(E_ALL);
}

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

    $projectRoot = dirname(__DIR__, 2);

    foreach ([
        $projectRoot . '/tmp',
        $projectRoot . '/tmp/fonts',
        $projectRoot . '/tmp/pdf-thumbs'
    ] as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
    }

    $renderer = new ItineraryRenderer($programaId);
    $html = $renderer->renderHtml();

    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isPhpEnabled', false);
    $options->set('defaultFont', 'TravelPdf');
    $options->set('dpi', 96);
    $options->set('chroot', $projectRoot);
    $options->set('tempDir', $projectRoot . '/tmp');
    $options->set('fontDir', $projectRoot . '/tmp/fonts');
    $options->set('fontCache', $projectRoot . '/tmp/fonts');

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // Numeración de páginas (esquina inferior derecha)
    try {
        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans', 'normal');
        if ($font) {
            $canvas->page_text(
                $canvas->get_width() - 115,
                $canvas->get_height() - 24,
                'Página {PAGE_NUM} / {PAGE_COUNT}',
                $font, 8, [0.55, 0.6, 0.68]
            );
        }
    } catch (Throwable $e) {
        error_log('PDF page numbers: ' . $e->getMessage());
    }

    $filename = 'itinerary-program-' . $programaId . '.pdf';

    $dompdf->stream($filename, [
        'Attachment' => true
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');

    echo "ERROR PDF\n\n";
    echo "Mensaje: " . $e->getMessage() . "\n\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n\n";
    echo "Trace:\n" . $e->getTraceAsString();
}
