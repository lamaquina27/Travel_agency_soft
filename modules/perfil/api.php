<?php
// =====================================
// ARCHIVO: modules/perfil/api.php - API para Perfil de Agente
// =====================================

// Log de debug
error_log("=== INICIO API PERFIL ===");

// Limpiar cualquier output previo
while (ob_get_level()) {
    ob_end_clean();
}

// Configurar headers
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Configurar error handling
ini_set('display_errors', 0);
error_reporting(0);

// Función para enviar respuesta JSON
function sendJsonResponse($success, $message = '', $data = null) {
    $response = [
        'success' => $success,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    $jsonResponse = json_encode($response, JSON_UNESCAPED_UNICODE);
    
    error_log("Enviando respuesta JSON: " . $jsonResponse);
    
    echo $jsonResponse;
    exit;
}

try {
    error_log("Método de petición: " . $_SERVER['REQUEST_METHOD']);
    
    // Verificar método
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJsonResponse(false, 'Método no permitido. Solo POST.');
    }
    
    // Incluir archivos necesarios (2 niveles arriba desde modules/perfil/)
    $configPath = dirname(__DIR__, 2) . '/config/database.php';
    $appPath = dirname(__DIR__, 2) . '/config/app.php';
    
    error_log("Incluyendo: " . $configPath);
    error_log("Incluyendo: " . $appPath);
    
    if (!file_exists($configPath)) {
        sendJsonResponse(false, 'Archivo de configuración de BD no encontrado: ' . $configPath);
    }
    
    if (!file_exists($appPath)) {
        sendJsonResponse(false, 'Archivo de configuración de App no encontrado: ' . $appPath);
    }
    
    require_once $configPath;
    require_once $appPath;
    
    error_log("Archivos incluidos correctamente");
    
    // Inicializar app
    App::init();
    error_log("App inicializada");
    
    // Verificar sesión
    if (!App::isLoggedIn()) {
        sendJsonResponse(false, 'Sesión no válida. Debe estar logueado.');
    }
    
    $user = App::getUser();
    error_log("Usuario obtenido: " . json_encode($user));
    
    // Verificar rol
    if ($user['role'] !== 'agent') {
        sendJsonResponse(false, 'Acceso denegado. Solo para agentes. Rol actual: ' . $user['role']);
    }
    
    // Obtener input
    $rawInput = file_get_contents('php://input');
    error_log("Input recibido: " . $rawInput);
    
    if (empty($rawInput)) {
        sendJsonResponse(false, 'No se recibieron datos JSON');
    }
    
    $input = json_decode($rawInput, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendJsonResponse(false, 'Error parsing JSON: ' . json_last_error_msg());
    }
    
    error_log("JSON parseado: " . json_encode($input));
    
    $action = $input['action'] ?? '';
    
    if (empty($action)) {
        sendJsonResponse(false, 'Acción no especificada en los datos');
    }
    
    error_log("Acción solicitada: " . $action);
    
    // Procesar acción
    switch($action) {
        case 'change_password':
            handleChangePassword($input, $user);
            break;
            
        default:
            sendJsonResponse(false, 'Acción no válida: ' . $action);
    }

} catch(Exception $e) {
    error_log("Error en API: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    sendJsonResponse(false, 'Error del sistema: ' . $e->getMessage());
}

function handleChangePassword($data, $user) {
    error_log("=== CAMBIO DE CONTRASEÑA ===");
    
    try {
        $currentPassword = $data['current_password'] ?? '';
        $newPassword = $data['new_password'] ?? '';
        
        error_log("Contraseña actual proporcionada: " . (!empty($currentPassword) ? 'SÍ' : 'NO'));
        error_log("Nueva contraseña proporcionada: " . (!empty($newPassword) ? 'SÍ' : 'NO'));
        
        if (empty($currentPassword)) {
            sendJsonResponse(false, 'La contraseña actual es obligatoria');
        }
        
        if (empty($newPassword)) {
            sendJsonResponse(false, 'La nueva contraseña es obligatoria');
        }
        
        // Conectar BD
        $db = Database::getInstance();
        error_log("Conexión a BD establecida");
        
        // Buscar usuario
        $sql = "SELECT id, password FROM users WHERE id = ? AND role = 'agent' AND active = 1";
        $userData = $db->fetch($sql, [$user['id']]);
        
        error_log("Consulta usuario: " . $sql);
        error_log("Parámetros: " . json_encode([$user['id']]));
        error_log("Usuario encontrado: " . ($userData ? 'SÍ' : 'NO'));
        
        if (!$userData) {
            sendJsonResponse(false, 'Usuario no encontrado en la base de datos');
        }
        
        // Verificar contraseña actual
        $passwordValid = password_verify($currentPassword, $userData['password']);
        error_log("Contraseña actual válida: " . ($passwordValid ? 'SÍ' : 'NO'));
        
        if (!$passwordValid) {
            sendJsonResponse(false, 'La contraseña actual es incorrecta');
        }
        
        // Validar nueva contraseña
        $validation = validatePassword($newPassword);
        error_log("Validación nueva contraseña: " . json_encode($validation));
        
        if (!$validation['valid']) {
            sendJsonResponse(false, $validation['message']);
        }
        
        // Verificar que sea diferente
        $samePassword = password_verify($newPassword, $userData['password']);
        error_log("Nueva contraseña igual a actual: " . ($samePassword ? 'SÍ' : 'NO'));
        
        if ($samePassword) {
            sendJsonResponse(false, 'La nueva contraseña debe ser diferente a la actual');
        }
        
        // Actualizar contraseña
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Usar el método update de la clase Database
        $updated = $db->update(
            'users',
            [
                'password' => $hashedPassword,
                'updated_at' => date('Y-m-d H:i:s')
            ],
            'id = ?',
            [$user['id']]
        );
        
        error_log("Resultado actualización: " . ($updated ? 'ÉXITO' : 'FALLO'));
        
        if ($updated) {
            error_log("Contraseña actualizada exitosamente");
            sendJsonResponse(true, 'Contraseña actualizada correctamente');
        } else {
            error_log("Error en actualización de contraseña");
            sendJsonResponse(false, 'Error al actualizar en la base de datos');
        }
        
    } catch(Exception $e) {
        error_log("Error en handleChangePassword: " . $e->getMessage());
        sendJsonResponse(false, 'Error procesando cambio: ' . $e->getMessage());
    }
}

function validatePassword($password) {
    if (strlen($password) < 8) {
        return ['valid' => false, 'message' => 'La contraseña debe tener al menos 8 caracteres'];
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        return ['valid' => false, 'message' => 'La contraseña debe incluir al menos una letra mayúscula (A-Z)'];
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        return ['valid' => false, 'message' => 'La contraseña debe incluir al menos una letra minúscula (a-z)'];
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        return ['valid' => false, 'message' => 'La contraseña debe incluir al menos un número (0-9)'];
    }
    
    if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        return ['valid' => false, 'message' => 'La contraseña debe incluir al menos un carácter especial (!@#$%^&*)'];
    }
    
    return ['valid' => true, 'message' => 'Contraseña válida'];
}

error_log("=== FIN API PERFIL ===");
?>