<?php
// =====================================
// ARCHIVO: auth/login.php - Autenticación Unificada (CORREGIDO)
// =====================================
// 🔧 FIX: Agregada validación de fecha_fin_suscripcion
// =====================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    App::redirect('/login');
}

// Obtener email y contraseña del formulario
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Validar que no estén vacíos
if (empty($email) || empty($password)) {
    $_SESSION['error'] = 'Por favor complete todos los campos';
    App::redirect('/login');
}

// Validar formato de email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Por favor ingrese un correo electrónico válido';
    App::redirect('/login');
}

try {
    $db = Database::getInstance();
    
    // ✅ VERIFICAR MODO MANTENIMIENTO
    $config = $db->fetch("SELECT maintenance_mode FROM company_settings LIMIT 1");
    if ($config && $config['maintenance_mode'] == 1) {
        // Verificar si el usuario existe Y es admin o superadmin
        $user = $db->fetch(
            "SELECT role FROM users WHERE email = ? AND active = 1",
            [$email]
        );
        
        // Si no es admin ni superadmin, bloquear acceso
        if (!$user || ($user['role'] !== 'admin' && $user['role'] !== 'superadmin')) {
            $_SESSION['error'] = '🚧 El sitio está en modo mantenimiento. Solo los administradores pueden acceder.';
            App::redirect('/login');
        }
    }
    
    // 🔧 FIX: Buscar usuario por EMAIL e INCLUIR agencia_id
    $user = $db->fetch(
        "SELECT id, username, email, password, full_name, role, active, agencia_id,
                session_token, session_ip, session_user_agent, session_started_at
        FROM users 
        WHERE email = ? AND active = 1",
        [$email]
    );

    // Verificar si el usuario existe y la contraseña es correcta
    if ($user && password_verify($password, $user['password'])) {


        // ==================================================
        // GENERAR NUEVO TOKEN DE SESIÓN
        // ==================================================

        // Generar token único para esta sesión
        $sessionToken = bin2hex(random_bytes(32)); // 64 caracteres hexadecimales
        $sessionIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $sessionUserAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

        // Actualizar en base de datos
        $db->update('users', [
            'session_token' => $sessionToken,
            'session_ip' => $sessionIp,
            'session_user_agent' => $sessionUserAgent,
            'session_started_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$user['id']]);

        // 🔧 FIX: Guardar TODOS los datos en sesión, incluyendo agencia_id
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['session_token'] = $sessionToken;
        
        // ✅ CRÍTICO: Guardar agencia_id en sesión (para admins y agents)
        if ($user['role'] !== 'superadmin') {
            $_SESSION['agencia_id'] = $user['agencia_id'];
            
            // Verificar que el usuario tenga una agencia asignada
            if (empty($user['agencia_id'])) {
                $_SESSION['error'] = 'Error: Usuario sin agencia asignada. Contacte al administrador.';
                App::redirect('/login');
                exit;
            }
            
            // ✅✅✅ VALIDACIÓN CORREGIDA: Verificar agencia activa Y fecha de suscripción
            $agencia = $db->fetch(
                "SELECT activa, estado_suscripcion, fecha_fin_suscripcion FROM agencias WHERE id = ?",
                [$user['agencia_id']]
            );
            
            if (!$agencia) {
                $_SESSION['error'] = 'Error: Agencia no encontrada.';
                App::redirect('/login');
                exit;
            }
            
            // Verificar si está activa
            if (!$agencia['activa']) {
                $_SESSION['error'] = 'Su agencia ha sido desactivada. Contacte al administrador.';
                App::redirect('/login');
                exit;
            }
            
            // Verificar si el estado es activo
            if ($agencia['estado_suscripcion'] !== 'activa') {
                $_SESSION['error'] = 'La suscripción de su agencia no está activa. Contacte al administrador.';
                App::redirect('/login');
                exit;
            }
            
            // ✅✅✅ NUEVA VALIDACIÓN: Verificar si la fecha de suscripción ya venció
            $fechaHoy = date('Y-m-d');
            if ($agencia['fecha_fin_suscripcion'] < $fechaHoy) {
                $_SESSION['error'] = 'La suscripción de su agencia ha expirado (' . date('d/m/Y', strtotime($agencia['fecha_fin_suscripcion'])) . '). Contacte al administrador para renovarla.';
                App::redirect('/login');
                exit;
            }
        }

        // Actualizar última fecha de login
        $db->update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);

        // Redirigir según el rol del usuario
        if ($user['role'] === 'superadmin') {
            // SUPERADMIN va a su dashboard especial
            App::redirect('/superadmin/dashboard');
        } elseif ($user['role'] === 'admin') {
            // ADMIN va al dashboard normal
            App::redirect('/dashboard');
        } else {
            // AGENT va al dashboard normal
            App::redirect('/dashboard');
        }
    } else {
        $_SESSION['error'] = 'Correo electrónico o contraseña incorrectos';
        App::redirect('/login');
    }

} catch (Exception $e) {
    error_log("Error en login: " . $e->getMessage());
    $_SESSION['error'] = 'Error del sistema. Intente nuevamente.';
    App::redirect('/login');
}