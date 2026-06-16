<?php
// ====================================================================
// ARCHIVO: setup_uploads_programa.php - CONFIGURAR CARPETAS DE UPLOADS
// ====================================================================
// ⚠️  EJECUTAR SOLO UNA VEZ PARA CONFIGURAR CARPETAS
// ====================================================================

// Protegido: solo por consola (CLI) o superadmin autenticado.
if (PHP_SAPI !== 'cli') {
    require_once __DIR__ . '/config/app.php';
    App::init();
    App::requireRole('superadmin');
}

echo "🚀 Configurando carpetas de uploads para programa...\n\n";

// Obtener año y mes actuales
$currentYear = date('Y');
$currentMonth = date('m');

// Crear estructura de carpetas
$baseDir = 'assets/uploads/programa';
$directories = [
    $baseDir,
    "$baseDir/$currentYear",
    "$baseDir/$currentYear/$currentMonth"
];

echo "📁 Creando directorios...\n";
foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "✅ Creado: $dir\n";
        } else {
            echo "❌ Error creando: $dir\n";
        }
    } else {
        echo "✅ Ya existe: $dir\n";
    }
}

// Crear archivo .htaccess para proteger las carpetas
$htaccessContent = '# Protección para uploads de programa
<Files "*.php">
    Order allow,deny
    Deny from all
</Files>

# Permitir solo archivos de imagen
<FilesMatch "\.(jpg|jpeg|png|gif|webp)$">
    Order allow,deny
    Allow from all
</FilesMatch>

# Prevenir ejecución de scripts
Options -ExecCGI
AddHandler cgi-script .php .pl .py .jsp .asp .sh .cgi
Options -Indexes
';

$htaccessPath = "$baseDir/.htaccess";
if (file_put_contents($htaccessPath, $htaccessContent)) {
    echo "✅ Archivo de protección creado: $htaccessPath\n";
} else {
    echo "❌ Error creando .htaccess: $htaccessPath\n";
}

// Crear archivo index.php para protección adicional
$indexContent = '<?php
// Archivo de protección - No eliminar
header("HTTP/1.0 403 Forbidden");
exit("Acceso denegado");
?>';

$indexPath = "$baseDir/index.php";
if (file_put_contents($indexPath, $indexContent)) {
    echo "✅ Archivo de protección creado: $indexPath\n";
} else {
    echo "❌ Error creando index.php: $indexPath\n";
}

// Crear archivo de configuración para uploads
$configContent = '<?php
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
?>';

$configPath = "$baseDir/config.php";
if (file_put_contents($configPath, $configContent)) {
    echo "✅ Archivo de configuración creado: $configPath\n";
} else {
    echo "❌ Error creando config.php: $configPath\n";
}

// Verificar permisos
echo "\n📋 Verificando permisos...\n";

$testDirs = [
    $baseDir,
    "$baseDir/$currentYear",
    "$baseDir/$currentYear/$currentMonth"
];

foreach ($testDirs as $dir) {
    if (is_writable($dir)) {
        echo "✅ $dir - Escribible\n";
    } else {
        echo "⚠️  $dir - No escribible (chmod 755 requerido)\n";
    }
}

echo "\n🎉 Configuración completada!\n";
echo "📁 Las imágenes se guardarán en: $baseDir/YYYY/MM/\n";
echo "🔒 Carpetas protegidas con .htaccess\n";
echo "📋 Configuración guardada en: $baseDir/config.php\n\n";

echo "⚠️  IMPORTANTE:\n";
echo "1. Ejecuta este script solo UNA VEZ\n";
echo "2. Verifica que las carpetas tengan permisos 755\n";
echo "3. Puedes eliminar este archivo después de ejecutarlo\n";
echo "4. Las URLs de imágenes serán: TU_DOMINIO/assets/uploads/programa/YYYY/MM/archivo.jpg\n\n";

// Test de creación de archivo
echo "🧪 Realizando test de escritura...\n";
$testFile = "$baseDir/$currentYear/$currentMonth/test_" . time() . ".txt";
if (file_put_contents($testFile, "Test de escritura - " . date('Y-m-d H:i:s'))) {
    echo "✅ Test de escritura exitoso: $testFile\n";
    unlink($testFile); // Eliminar archivo de test
    echo "✅ Archivo de test eliminado\n";
} else {
    echo "❌ Error en test de escritura\n";
}

echo "\n✅ ¡Todo listo para subir imágenes de programas!\n";
echo "\n📝 SIGUIENTE PASO:\n";
echo "Ejecuta el archivo programa.php en tu navegador para probar el formulario.\n";
?>