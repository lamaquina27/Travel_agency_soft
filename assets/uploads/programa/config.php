<?php
// Configuración de uploads para programas
define("PROGRAMA_UPLOAD_DIR", __DIR__);
define("PROGRAMA_MAX_FILE_SIZE", 5 * 1024 * 1024); // 5MB
define("PROGRAMA_ALLOWED_TYPES", ["image/jpeg", "image/png", "image/gif", "image/webp"]);
define("PROGRAMA_ALLOWED_EXTENSIONS", ["jpg", "jpeg", "png", "gif", "webp"]);

// Función para validar archivos
function validarArchivoPrograma($file) {
    if (!in_array($file["type"], PROGRAMA_ALLOWED_TYPES)) {
        return "Tipo de archivo no permitido";
    }
    
    if ($file["size"] > PROGRAMA_MAX_FILE_SIZE) {
        return "Archivo demasiado grande";
    }
    
    $extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    if (!in_array($extension, PROGRAMA_ALLOWED_EXTENSIONS)) {
        return "Extensión no permitida";
    }
    
    return true;
}
?>