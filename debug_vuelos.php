<?php
/**
 * Script de debug para probar la API de vuelos
 * Accede a: http://localhost/Travel_agency_soft/debug_vuelos.php
 */

require_once 'config/database.php';
require_once 'config/app.php';
require_once 'config/aerodatabox.php';

App::init();

// Si envía datos POST, prueba el API
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo_vuelo = $_POST['codigo_vuelo'] ?? '';
    $programa_dias_id = $_POST['programa_dias_id'] ?? '';
    
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Debug Vuelos API</title>
        <style>
            body { font-family: monospace; background: #f5f5f5; padding: 20px; }
            .box { background: white; border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; }
            .label { font-weight: bold; color: #333; }
            .value { background: #f9f9f9; padding: 10px; margin: 5px 0; border-left: 3px solid #007bff; }
            .error { color: red; }
            .success { color: green; }
            pre { background: #f4f4f4; padding: 10px; overflow-x: auto; }
        </style>
    </head>
    <body>
        <h1>Debug Vuelos API</h1>
        
        <div class="box">
            <div class="label">Parámetros Recibidos:</div>
            <div class="value">
                código_vuelo: <strong><?php echo htmlspecialchars($codigo_vuelo); ?></strong>
            </div>
            <div class="value">
                programa_dias_id: <strong><?php echo htmlspecialchars($programa_dias_id); ?></strong>
            </div>
        </div>

        <div class="box">
            <div class="label">Validación:</div>
            <?php
            if (empty($codigo_vuelo)) {
                echo '<div class="value error">❌ código_vuelo está vacío</div>';
            } else {
                echo '<div class="value success">✓ código_vuelo: ' . htmlspecialchars($codigo_vuelo) . '</div>';
            }
            
            if (empty($programa_dias_id)) {
                echo '<div class="value error">❌ programa_dias_id está vacío</div>';
            } else {
                $programa_dias_id_int = (int)$programa_dias_id;
                echo '<div class="value success">✓ programa_dias_id: ' . $programa_dias_id_int . '</div>';
            }
            ?>
        </div>

        <div class="box">
            <div class="label">Test JSON Enviado:</div>
            <pre><?php
            $test_json = json_encode([
                'action' => 'preview',
                'codigo_vuelo' => $codigo_vuelo,
                'programa_dias_id' => (int)$programa_dias_id
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            echo htmlspecialchars($test_json);
            ?></pre>
        </div>

        <div class="box">
            <div class="label">Próximos pasos:</div>
            <ol>
                <li>Revisa los logs de PHP en: <code>C:\xampp\apache\logs\error.log</code></li>
                <li>Busca líneas que empiezan con: <code>VuelosAPI</code></li>
                <li>Copia aquí lo que veas en los logs</li>
            </ol>
        </div>
    </body>
    </html>
    <?php
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Debug Vuelos API</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; }
        .box { background: white; border: 1px solid #ddd; padding: 20px; border-radius: 5px; margin: 10px 0; }
        h1 { color: #333; }
        .form-group { margin: 15px 0; }
        label { display: block; font-weight: bold; margin-bottom: 5px; color: #555; }
        input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 3px; cursor: pointer; font-size: 16px; }
        button:hover { background: #0056b3; }
        .info { background: #e7f3ff; border-left: 4px solid #2196F3; padding: 10px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🐛 Debug Vuelos API</h1>
        
        <div class="box info">
            <strong>Este script te ayuda a probar la API de vuelos</strong><br>
            Ingresa los mismos valores que usas en el programa y verás exactamente qué se envía.
        </div>

        <form method="POST" class="box">
            <div class="form-group">
                <label for="codigo_vuelo">Código de Vuelo:</label>
                <input type="text" id="codigo_vuelo" name="codigo_vuelo" placeholder="Ej: EK330" value="EK330">
            </div>

            <div class="form-group">
                <label for="programa_dias_id">ID del Día (programa_dias_id):</label>
                <input type="number" id="programa_dias_id" name="programa_dias_id" placeholder="Ej: 436" value="436">
            </div>

            <button type="submit">Enviar Prueba</button>
        </form>

        <div class="box">
            <h3>📝 Instrucciones:</h3>
            <ol>
                <li>Rellena los campos con los valores que usas en el programa (código_vuelo=EK330, programa_dias_id=436)</li>
                <li>Haz clic en "Enviar Prueba"</li>
                <li>Verifica que los valores se reciban correctamente</li>
                <li>Abre los logs de PHP en: <code>C:\xampp\apache\logs\error.log</code></li>
                <li>Busca líneas con "VuelosAPI"</li>
                <li>Comparte esas líneas para diagnosticar el problema</li>
            </ol>
        </div>
    </div>
</body>
</html>
