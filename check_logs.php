<?php
// check_logs.php - ELIMINA DESPUÉS DE USAR
// Protegido: solo superadmin autenticado (expone configuración/logs).
require_once __DIR__ . '/config/app.php';
App::init();
App::requireRole('superadmin');

echo "<h2>📋 Revisión de Logs</h2>";

// 1. Verificar configuración de logs
echo "<h3>⚙️ Configuración de PHP:</h3>";
echo "Error reporting: " . error_reporting() . "<br>";
echo "Display errors: " . ini_get('display_errors') . "<br>";
echo "Log errors: " . ini_get('log_errors') . "<br>";
echo "Error log: " . ini_get('error_log') . "<br>";

// 2. Buscar archivos de log comunes
echo "<h3>📁 Archivos de Log:</h3>";
$logFiles = [
    $_SERVER['DOCUMENT_ROOT'] . '/error_log',
    $_SERVER['DOCUMENT_ROOT'] . '/../error_log',
    '/tmp/php_errors.log',
    ini_get('error_log'),
    'error_log'
];

foreach ($logFiles as $logFile) {
    if ($logFile && file_exists($logFile)) {
        echo "✅ Encontrado: $logFile<br>";
        
        // Leer últimas líneas
        $lines = file($logFile);
        $lastLines = array_slice($lines, -20); // Últimas 20 líneas
        
        echo "<strong>Últimas entradas:</strong><br>";
        echo "<pre style='background: #f4f4f4; padding: 10px; max-height: 200px; overflow-y: scroll;'>";
        foreach ($lastLines as $line) {
            // Resaltar errores relacionados con biblioteca
            if (stripos($line, 'biblioteca') !== false || stripos($line, 'upload') !== false) {
                echo "<span style='background: yellow;'>$line</span>";
            } else {
                echo htmlspecialchars($line);
            }
        }
        echo "</pre><br>";
    } else {
        echo "❌ No encontrado: $logFile<br>";
    }
}

// 3. Probar escritura de log
echo "<h3>✍️ Prueba de Escritura de Log:</h3>";
error_log("=== TEST LOG ENTRY === " . date('Y-m-d H:i:s'));
echo "Log de prueba enviado. Revisa arriba si aparece.<br>";

// 4. Verificar permisos de directorios
echo "<h3>🔐 Permisos de Directorios:</h3>";
$dirs = [
    $_SERVER['DOCUMENT_ROOT'] . '/assets',
    $_SERVER['DOCUMENT_ROOT'] . '/assets/uploads',
    $_SERVER['DOCUMENT_ROOT'] . '/assets/uploads/biblioteca',
];

foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        $perms = substr(sprintf('%o', fileperms($dir)), -4);
        $writable = is_writable($dir) ? 'SÍ' : 'NO';
        echo "📁 $dir - Permisos: $perms - Escribible: $writable<br>";
    } else {
        echo "❌ No existe: $dir<br>";
    }
}

// 5. Probar conexión a base de datos
echo "<h3>🗄️ Conexión a Base de Datos:</h3>";
try {
    require_once 'config/database.php';
    $db = Database::getInstance();
    
    echo "✅ Conexión exitosa<br>";
    
    // Verificar tablas de biblioteca
    $tables = ['biblioteca_dias', 'biblioteca_alojamientos', 'biblioteca_actividades', 'biblioteca_transportes'];
    foreach ($tables as $table) {
        $exists = $db->fetch("SHOW TABLES LIKE ?", [$table]);
        if ($exists) {
            $count = $db->fetch("SELECT COUNT(*) as total FROM $table");
            echo "✅ $table - Registros: " . $count['total'] . "<br>";
        } else {
            echo "❌ $table - No existe<br>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error de BD: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<p><strong>⚠️ Elimina este archivo después de revisar.</strong></p>";
?>