<?php
// ====================================================================
// ARCHIVO: auth/logout.php - Cerrar sesión y limpiar token
// ====================================================================

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/app.php';

App::init();

// Solo procesar si hay sesión activa
if (App::isLoggedIn()) {
    $userId = $_SESSION['user_id'];
    
    try {
        $db = Database::getInstance();
        
        // Limpiar token de sesión en la base de datos
        $db->update('users', [
            'session_token' => null,
            'session_ip' => null,
            'session_user_agent' => null,
            'session_started_at' => null
        ], 'id = ?', [$userId]);
        
    } catch (Exception $e) {
        error_log("Error limpiando sesión en logout: " . $e->getMessage());
    }
}

// Destruir sesión
session_unset();
session_destroy();

// Redirigir al login
header('Location: ' . APP_URL . '/login');
exit;