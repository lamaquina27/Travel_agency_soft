<?php
// router.php - Enrutador para el servidor local de PHP
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Si el archivo existe físicamente (ej. una imagen o CSS), lo sirve directamente
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false;
}

// De lo contrario, envía todo a index.php
require_once __DIR__ . '/index.php';
