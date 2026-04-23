<?php
// ============================================================
// SCRIPT DE MIGRACIONES - Travel Agency 2
// ============================================================
// USO:
//   php database/migrate.php
//   O abrir en navegador: http://localhost/travel_agency2/database/migrate.php
//
// Este script aplica automáticamente las migraciones pendientes
// en el orden correcto, sin repetir las ya aplicadas.
// ============================================================

// Solo ejecutar desde CLI o con clave de seguridad en web
$isCli = (php_sapi_name() === 'cli');
$webKey = $_GET['key'] ?? '';
$configuredKey = getenv('MIGRATE_KEY') ?: 'migrate_local_2026';

if (!$isCli && $webKey !== $configuredKey) {
    http_response_code(403);
    die("Acceso denegado. Usa: ?key=migrate_local_2026");
}

require_once dirname(__DIR__) . '/config/database.php';

echo $isCli ? "" : "<pre style='font-family:monospace;font-size:14px;padding:20px'>";
echo "============================================================\n";
echo "  TRAVEL AGENCY 2 — Gestor de Migraciones\n";
echo "============================================================\n\n";

try {
    $db = Database::getInstance()->getConnection();

    // 1. Crear tabla de control si no existe
    $db->exec("CREATE TABLE IF NOT EXISTS db_migrations (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        migration   VARCHAR(255) NOT NULL UNIQUE,
        applied_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 2. Leer migraciones ya aplicadas
    $applied = $db->query("SELECT migration FROM db_migrations")
                  ->fetchAll(PDO::FETCH_COLUMN);

    // 3. Leer archivos de migración ordenados
    $migrationsDir = __DIR__ . '/migrations';
    $files = glob($migrationsDir . '/*.sql');
    sort($files);

    if (empty($files)) {
        echo "⚠️  No se encontraron archivos en database/migrations/\n";
        exit(0);
    }

    $pendientes = 0;
    $aplicadas  = 0;

    foreach ($files as $file) {
        $name = basename($file, '.sql');

        if (in_array($name, $applied)) {
            echo "✅ Ya aplicada:   $name\n";
            continue;
        }

        echo "⏳ Aplicando:     $name ... ";

        $sql = file_get_contents($file);

        // Ejecutar sentencias separadas por ;
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            fn($s) => !empty($s) && !str_starts_with(ltrim($s), '--')
        );

        foreach ($statements as $statement) {
            if (!empty(trim($statement))) {
                $db->exec($statement);
            }
        }

        // Registrar como aplicada
        $stmt = $db->prepare("INSERT IGNORE INTO db_migrations (migration) VALUES (?)");
        $stmt->execute([$name]);

        echo "✅ OK\n";
        $aplicadas++;
        $pendientes++;
    }

    echo "\n------------------------------------------------------------\n";
    if ($pendientes === 0) {
        echo "🎉 Todo al día. No había migraciones pendientes.\n";
    } else {
        echo "🎉 $aplicadas migración(es) aplicada(s) correctamente.\n";
    }
    echo "------------------------------------------------------------\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

echo $isCli ? "" : "</pre>";
