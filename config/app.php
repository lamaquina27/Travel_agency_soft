<?php
// =====================================
// ARCHIVO: config/app.php - Configuración Principal para Hosting
// =====================================

require_once 'config_functions.php';

class App {
    public static function init() {
        self::loadConfig();
        self::startSession();
        self::setTimezone();
        self::initializeConfigManager();
    }

    private static function loadConfig() {
        // Cargar variables de entorno desde .env
        if (file_exists(dirname(__DIR__) . '/.env')) {
            $lines = file(dirname(__DIR__) . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                    putenv(sprintf('%s=%s', $name, $value));
                    $_ENV[$name] = $value;
                    $_SERVER[$name] = $value;
                }
            }
        }

        // ✅ DEFINIR CONSTANTES DINÁMICAMENTE
        if (!defined('APP_NAME')) {
            $companyName = 'TravelSoft';
            try {
                ConfigManager::init();
                $companyName = ConfigManager::getCompanyName();
            } catch(Exception $e) {
                // Si hay error, usar valor por defecto
            }
            define('APP_NAME', $_ENV['APP_NAME'] ?? $companyName);
        }
        
        if (!defined('APP_URL')) {
            // Detectar automáticamente la URL base
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            
            // Para hosting, usar la URL del .env si existe, sino detectar automáticamente
            $appUrl = $_ENV['APP_URL'] ?? ($protocol . '://' . $host);
            
            define('APP_URL', rtrim($appUrl, '/'));
            define('APP_PATH', '/');
        }
        
        if (!defined('APP_DEBUG')) {
            define('APP_DEBUG', ($_ENV['APP_DEBUG'] ?? 'false') === 'true');
        }
        
        if (!defined('BASE_PATH')) {
            define('BASE_PATH', dirname(__DIR__));
        }
    }

    private static function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            // Sesión sin expiración por inactividad: 1 año
            ini_set('session.gc_maxlifetime', 31536000);
            session_set_cookie_params(31536000);
            session_start();
        }
    }

    private static function setTimezone() {
        date_default_timezone_set('America/Bogota');
    }
    
    private static function initializeConfigManager() {
        try {
            ConfigManager::init();
        } catch(Exception $e) {
            error_log("Error initializing ConfigManager: " . $e->getMessage());
        }
    }

public static function requireLogin() {
    if (!self::isLoggedIn()) {
        self::redirect('/login');
    }
    
    // ==================================================
    // VALIDAR TOKEN DE SESIÓN ÚNICA
    // ==================================================
    
    // Obtener token de la sesión actual
    $currentToken = $_SESSION['session_token'] ?? null;
    
    if ($currentToken) {
        try {
            $db = Database::getInstance();
            
            // Obtener token de la base de datos
            $user = $db->fetch(
                "SELECT session_token FROM users WHERE id = ?",
                [$_SESSION['user_id']]
            );
            
            // Si los tokens no coinciden, la sesión fue cerrada en otro dispositivo
            if (!$user || $user['session_token'] !== $currentToken) {
                // Cerrar sesión actual
                session_unset();
                session_destroy();
                session_start();
                
                // Mensaje informativo
                $_SESSION['error'] = '⚠️ Tu sesión se cerró porque se abrió en otro dispositivo.';
                
                self::redirect('/login');
            }
            
        } catch (Exception $e) {
            error_log("Error validando token de sesión: " . $e->getMessage());
            // En caso de error, no cerrar la sesión para no afectar la experiencia
        }
    }
    // ==================================================
    // ✅✅✅ VALIDAR SUSCRIPCIÓN DE AGENCIA
    // ==================================================
    
    // Solo validar para usuarios que NO son superadmin
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] !== 'superadmin' && isset($_SESSION['agencia_id'])) {
        try {
            $db = Database::getInstance();
            
            // Obtener datos de la agencia
            $agencia = $db->fetch(
                "SELECT activa, estado_suscripcion, fecha_fin_suscripcion FROM agencias WHERE id = ?",
                [$_SESSION['agencia_id']]
            );
            
            // Si la agencia no existe, cerrar sesión
            if (!$agencia) {
                session_unset();
                session_destroy();
                session_start();
                $_SESSION['error'] = 'Agencia no encontrada. Contacte al administrador.';
                self::redirect('/login');
                exit;
            }
            
            // Si la agencia está desactivada, cerrar sesión
            if (!$agencia['activa']) {
                session_unset();
                session_destroy();
                session_start();
                $_SESSION['error'] = 'Su agencia ha sido desactivada. Contacte al administrador.';
                self::redirect('/login');
                exit;
            }
            
            // Si el estado no es activo, cerrar sesión
            if ($agencia['estado_suscripcion'] !== 'activa') {
                session_unset();
                session_destroy();
                session_start();
                $_SESSION['error'] = 'La suscripción de su agencia no está activa. Contacte al administrador.';
                self::redirect('/login');
                exit;
            }
            
            // ✅✅✅ VALIDACIÓN CRÍTICA: Si la fecha ya venció, cerrar sesión
            $fechaHoy = date('Y-m-d');
            if ($agencia['fecha_fin_suscripcion'] < $fechaHoy) {
                session_unset();
                session_destroy();
                session_start();
                $_SESSION['error'] = 'La suscripción de su agencia ha expirado (' . date('d/m/Y', strtotime($agencia['fecha_fin_suscripcion'])) . '). Contacte al administrador para renovarla.';
                self::redirect('/login');
                exit;
            }
            
        } catch (Exception $e) {
            error_log("Error verificando suscripción: " . $e->getMessage());
            // En caso de error de conexión, no cerrar sesión
        }
    }
}

public static function requireRole($role) {
    // Primero verificar que esté logueado
    if (!self::isLoggedIn()) {
        self::redirect('/login');
    }
    
    // Obtener el rol del usuario en sesión
    $userRole = $_SESSION['user_role'] ?? null;
    
    // Verificar que tenga el rol requerido
    if ($userRole !== $role) {
        // Si no tiene el rol, mostrar error 403
        http_response_code(403);
        
        echo '<!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Acceso Denegado</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    height: 100vh;
                    margin: 0;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                }
                .error-container {
                    background: white;
                    padding: 50px;
                    border-radius: 20px;
                    text-align: center;
                    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                    max-width: 500px;
                }
                .error-icon {
                    font-size: 80px;
                    margin-bottom: 20px;
                }
                h1 {
                    color: #1a202c;
                    margin-bottom: 15px;
                }
                p {
                    color: #718096;
                    margin-bottom: 30px;
                    line-height: 1.6;
                }
                .btn {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 12px 30px;
                    border-radius: 8px;
                    text-decoration: none;
                    display: inline-block;
                    font-weight: 600;
                }
                .btn:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
                }
            </style>
        </head>
        <body>
            <div class="error-container">
                <div class="error-icon">🚫</div>
                <h1>Acceso Denegado</h1>
                <p>No tienes permisos para acceder a esta sección del sistema. Se requiere rol: <strong>' . htmlspecialchars($role) . '</strong></p>
                <a href="' . APP_URL . '/dashboard" class="btn">Volver al Dashboard</a>
            </div>
        </body>
        </html>';
        
        exit;
    }
}


    public static function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

public static function getUser() {
    if (!self::isLoggedIn()) {
        return null;
    }

    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? '',
        'email' => $_SESSION['email'] ?? '', 
        'name' => $_SESSION['user_name'] ?? '',
        'role' => $_SESSION['user_role'] ?? 'agent',
        'agencia_id' => $_SESSION['agencia_id'] ?? null
    ];
}

    public static function redirect($path) {
        $url = APP_URL . $path;
        header("Location: $url");
        exit();
    }

    public static function getCurrentPath() {
        $request = $_SERVER['REQUEST_URI'];
        $path = parse_url($request, PHP_URL_PATH);
        return str_replace(rtrim(parse_url(APP_URL, PHP_URL_PATH), '/'), '', $path) ?: '/';
    }

    public static function asset($path) {
        return APP_URL . '/assets/' . ltrim($path, '/');
    }

    public static function url($path) {
        return APP_URL . '/' . ltrim($path, '/');
    }


    // ===== MÉTODOS PARA CONFIGURACIÓN =====
    
    public static function getLoginColors() {
        try {
            ConfigManager::init();
            return ConfigManager::getLoginColors();
        } catch(Exception $e) {
            return [
                'primary' => '#667eea',
                'secondary' => '#764ba2'
            ];
        }
    }

    public static function getCompanyName() {
        try {
            ConfigManager::init();
            return ConfigManager::getCompanyName();
        } catch(Exception $e) {
            return 'TravelSoft';
        }
    }

    public static function getLogo() {
        try {
            ConfigManager::init();
            return ConfigManager::getLogo();
        } catch(Exception $e) {
            return '';
        }
    }

    public static function getDefaultLanguage() {
        try {
            ConfigManager::init();
            return ConfigManager::getDefaultLanguage();
        } catch(Exception $e) {
            return 'es';
        }
    }

    public static function getColorsForRole($role) {
        try {
            ConfigManager::init();
            return ConfigManager::getColorsForRole($role);
        } catch(Exception $e) {
            if ($role === 'admin') {
                return [
                    'primary' => '#e53e3e',
                    'secondary' => '#fd746c'
                ];
            } else {
                return [
                    'primary' => '#667eea',
                    'secondary' => '#764ba2'
                ];
            }
        }
    }

    public static function getConfig($key = null) {
        try {
            ConfigManager::init();
            return ConfigManager::get($key);
        } catch(Exception $e) {
            return $key ? null : [];
        }
    }
    
}