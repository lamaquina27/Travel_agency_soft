<?php
// ====================================================================
// ARCHIVO: auth/force_close_session.php
// DESCRIPCIÓN: Forzar cierre de sesión anterior e iniciar nueva
// ====================================================================

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/app.php';

App::init();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    App::redirect('/login');
}

// Verificar que haya una solicitud pendiente
if (!isset($_SESSION['pending_login'])) {
    App::redirect('/login');
}

$pendingLogin = $_SESSION['pending_login'];

try {
    $db = Database::getInstance();
    
    // Validar credenciales nuevamente (por seguridad)
    $user = $db->fetch(
        "SELECT id, username, email, password, full_name, role, active, agencia_id 
         FROM users 
         WHERE email = ? AND active = 1",
        [$pendingLogin['email']]
    );
    
    if (!$user || !password_verify($pendingLogin['password'], $user['password'])) {
        $_SESSION['error'] = 'Error de autenticación. Intente nuevamente.';
        unset($_SESSION['pending_login']);
        App::redirect('/login');
    }
    
    // Generar nuevo token de sesión
    $sessionToken = bin2hex(random_bytes(32));
    $sessionIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $sessionUserAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    // Actualizar en base de datos (esto invalida la sesión anterior)
    $db->update('users', [
        'session_token' => $sessionToken,
        'session_ip' => $sessionIp,
        'session_user_agent' => $sessionUserAgent,
        'session_started_at' => date('Y-m-d H:i:s'),
        'last_login' => date('Y-m-d H:i:s')
    ], 'id = ?', [$user['id']]);
    
    // Limpiar pending_login
    unset($_SESSION['pending_login']);
    
    // Iniciar sesión normalmente
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['session_token'] = $sessionToken;
    $_SESSION['agencia_id'] = $user['agencia_id'];
    
    // Agregar agencia_id si no es superadmin
    if ($user['role'] !== 'superadmin') {
        $_SESSION['agencia_id'] = $user['agencia_id'];
        
        // ✅✅✅ VALIDACIÓN CORREGIDA: Verificar agencia activa Y fecha de suscripción
        $agencia = $db->fetch(
            "SELECT activa, estado_suscripcion, fecha_fin_suscripcion FROM agencias WHERE id = ?",
            [$user['agencia_id']]
        );
        
        if (!$agencia) {
            $_SESSION['error'] = 'Error: Agencia no encontrada.';
            unset($_SESSION['pending_login']);
            App::redirect('/login');
            exit;
        }
        
        // Verificar si está activa
        if (!$agencia['activa']) {
            $_SESSION['error'] = 'Su agencia ha sido desactivada. Contacte al administrador.';
            unset($_SESSION['pending_login']);
            App::redirect('/login');
            exit;
        }
        
        // Verificar si el estado es activo
        if ($agencia['estado_suscripcion'] !== 'activa') {
            $_SESSION['error'] = 'La suscripción de su agencia no está activa. Contacte al administrador.';
            unset($_SESSION['pending_login']);
            App::redirect('/login');
            exit;
        }
        
        // ✅✅✅ NUEVA VALIDACIÓN: Verificar si la fecha de suscripción ya venció
        $fechaHoy = date('Y-m-d');
        if ($agencia['fecha_fin_suscripcion'] < $fechaHoy) {
            $_SESSION['error'] = 'La suscripción de su agencia ha expirado (' . date('d/m/Y', strtotime($agencia['fecha_fin_suscripcion'])) . '). Contacte al administrador para renovarla.';
            unset($_SESSION['pending_login']);
            App::redirect('/login');
            exit;
        }
    }
    
    // Redirigir según rol
    if ($user['role'] === 'superadmin') {
        App::redirect('/superadmin/dashboard');
    } else {
        App::redirect('/dashboard');
    }
    
} catch (Exception $e) {
    error_log("Error en force_close_session: " . $e->getMessage());
    $_SESSION['error'] = 'Error del sistema. Intente nuevamente.';
    unset($_SESSION['pending_login']);
    App::redirect('/login');
}