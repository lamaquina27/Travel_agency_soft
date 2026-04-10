<?php
// =====================================
// ARCHIVO: config/constants.php - Constantes Adicionales para UI Components
// =====================================

// Verificar si las constantes necesarias están definidas
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

if (!defined('PUBLIC_PATH')) {
    define('PUBLIC_PATH', BASE_PATH . '/assets');
}

if (!defined('UPLOAD_PATH')) {
    define('UPLOAD_PATH', BASE_PATH . '/assets/uploads');
}

if (!defined('CONFIG_UPLOAD_PATH')) {
    define('CONFIG_UPLOAD_PATH', UPLOAD_PATH . '/config');
}

// Asegurar que los directorios existen
$requiredDirs = [
    PUBLIC_PATH,
    UPLOAD_PATH,
    CONFIG_UPLOAD_PATH
];

foreach ($requiredDirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// Funciones auxiliares para el manejo de rutas
if (!function_exists('getPublicUrl')) {
    function getPublicUrl($path) {
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path; // Ya es una URL completa
        }
        
        // Asegurar que la ruta comience con /
        if (strpos($path, '/') !== 0) {
            $path = '/' . $path;
        }
        
        return APP_URL . $path;
    }
}

if (!function_exists('getAssetUrl')) {
    function getAssetUrl($path) {
        return APP_URL . '/assets/' . ltrim($path, '/');
    }
}

if (!function_exists('fileExists')) {
    function fileExists($path) {
        // Si es una URL, asumimos que existe (no podemos verificar fácilmente)
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return true;
        }
        
        // Verificar rutas absolutas
        if (file_exists($path)) {
            return true;
        }
        
        // Verificar rutas relativas desde BASE_PATH
        if (file_exists(BASE_PATH . $path)) {
            return true;
        }
        
        // Verificar rutas relativas desde PUBLIC_PATH
        if (file_exists(PUBLIC_PATH . $path)) {
            return true;
        }
        
        return false;
    }
}