<?php
// =====================================================
// ARCHIVO: modules/translate/api.php - API DE TRADUCCIÓN
// =====================================================

// Configurar headers y errores
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Incluir archivos necesarios
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/translate.php';

// Inicializar aplicación
App::init();

class TranslateAPI {
    
    private $db;
    private $supportedLanguages = ['es', 'en', 'fr', 'pt', 'it', 'de'];
    
    public function __construct() {
        try {
            $this->db = Database::getInstance();
        } catch(Exception $e) {
            $this->sendError('Error de conexión a base de datos: ' . $e->getMessage());
        }
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_POST['action'] ?? $_GET['action'] ?? '';
        
        try {
            switch($method) {
                case 'POST':
                    $this->handlePostRequest($action);
                    break;
                case 'GET':
                    $this->handleGetRequest($action);
                    break;
                default:
                    $this->sendError('Método HTTP no soportado', 405);
            }
        } catch(Exception $e) {
            $this->sendError('Error del servidor: ' . $e->getMessage(), 500);
        }
    }
    
    private function handlePostRequest($action) {
        switch($action) {
            case 'set_language':
                $this->setLanguage();
                break;
            case 'save_user_preference':
                $this->saveUserLanguagePreference();
                break;
            default:
                // Si no hay action, intentar obtener del body JSON
                $this->setLanguageFromJSON();
        }
    }
    
    private function handleGetRequest($action) {
        switch($action) {
            case 'get_languages':
                $this->getSupportedLanguages();
                break;
            case 'get_current':
                $this->getCurrentLanguage();
                break;
            case 'get_user_preference':
                $this->getUserLanguagePreference();
                break;
            default:
                $this->sendError('Acción GET no válida', 400);
        }
    }
    
    /**
     * Establecer idioma desde JSON (para peticiones AJAX)
     */
    private function setLanguageFromJSON() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['language'])) {
            $this->sendError('Parámetro language requerido', 400);
        }
        
        $language = $input['language'];
        $this->validateAndSetLanguage($language);
    }
    
    /**
     * Establecer idioma desde POST
     */
    private function setLanguage() {
        $language = $_POST['language'] ?? null;
        
        if (!$language) {
            $this->sendError('Parámetro language requerido', 400);
        }
        
        $this->validateAndSetLanguage($language);
    }
    
    /**
     * Validar y establecer idioma
     */
    private function validateAndSetLanguage($language) {
        // Validar idioma
        if (!in_array($language, $this->supportedLanguages)) {
            $this->sendError('Idioma no soportado: ' . $language, 400);
        }
        
        // Guardar en sesión
        $_SESSION['app_language'] = $language;
        
        // Si está autenticado, guardar preferencia en BD
        if (App::isLoggedIn()) {
            try {
                $user = App::getUser();
                $this->db->update(
                    'users', 
                    ['language_preference' => $language, 'updated_at' => date('Y-m-d H:i:s')], 
                    'id = ?', 
                    [$user['id']]
                );
            } catch(Exception $e) {
                // Error silencioso - no es crítico
                error_log("Error guardando preferencia de idioma: " . $e->getMessage());
            }
        }
        
        $this->sendSuccess([
            'language' => $language,
            'message' => 'Idioma actualizado correctamente',
            'session_updated' => true,
            'user_preference_saved' => App::isLoggedIn()
        ]);
    }
    
    /**
     * Guardar preferencia de idioma del usuario
     */
    private function saveUserLanguagePreference() {
        if (!App::isLoggedIn()) {
            $this->sendError('Usuario no autenticado', 401);
        }
        
        $language = $_POST['language'] ?? null;
        
        if (!$language || !in_array($language, $this->supportedLanguages)) {
            $this->sendError('Idioma no válido', 400);
        }
        
        try {
            $user = App::getUser();
            
            // Verificar si la columna existe, si no, agregarla
            $this->ensureLanguageColumn();
            
            $this->db->update(
                'users',
                ['language_preference' => $language, 'updated_at' => date('Y-m-d H:i:s')],
                'id = ?',
                [$user['id']]
            );
            
            // También actualizar sesión
            $_SESSION['app_language'] = $language;
            
            $this->sendSuccess([
                'language' => $language,
                'message' => 'Preferencia de idioma guardada',
                'user_id' => $user['id']
            ]);
            
        } catch(Exception $e) {
            $this->sendError('Error guardando preferencia: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Obtener idiomas soportados
     */
    private function getSupportedLanguages() {
        $languages = TranslateSystem::getSupportedLanguages();
        $current = TranslateSystem::getCurrentLanguage();
        
        $this->sendSuccess([
            'languages' => $languages,
            'current' => $current,
            'default' => TranslateSystem::getDefaultLanguage()
        ]);
    }
    
    /**
     * Obtener idioma actual
     */
    private function getCurrentLanguage() {
        $current = TranslateSystem::getCurrentLanguage();
        $source = 'default';
        
        if (isset($_GET['lang'])) {
            $source = 'url_parameter';
        } elseif (App::isLoggedIn() && isset($_SESSION['app_language'])) {
            $source = 'user_session';
        }
        
        $this->sendSuccess([
            'language' => $current,
            'source' => $source,
            'is_authenticated' => App::isLoggedIn()
        ]);
    }
    
    /**
     * Obtener preferencia de idioma del usuario
     */
    private function getUserLanguagePreference() {
        if (!App::isLoggedIn()) {
            $this->sendError('Usuario no autenticado', 401);
        }
        
        try {
            $user = App::getUser();
            
            $userData = $this->db->fetch(
                "SELECT language_preference FROM users WHERE id = ?",
                [$user['id']]
            );
            
            $preference = $userData['language_preference'] ?? TranslateSystem::getDefaultLanguage();
            
            $this->sendSuccess([
                'user_id' => $user['id'],
                'language_preference' => $preference,
                'session_language' => $_SESSION['app_language'] ?? null
            ]);
            
        } catch(Exception $e) {
            $this->sendError('Error obteniendo preferencia: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Asegurar que existe la columna language_preference
     */
    private function ensureLanguageColumn() {
        try {
            // Verificar si la columna existe
            $columns = $this->db->fetchAll("DESCRIBE users");
            $hasLanguageColumn = false;
            
            foreach($columns as $column) {
                if ($column['Field'] === 'language_preference') {
                    $hasLanguageColumn = true;
                    break;
                }
            }
            
            // Si no existe, crearla
            if (!$hasLanguageColumn) {
                $this->db->execute(
                    "ALTER TABLE users ADD COLUMN language_preference VARCHAR(5) DEFAULT 'es' AFTER role"
                );
            }
            
        } catch(Exception $e) {
            // Error silencioso - la columna podría ya existir
            error_log("Error verificando/creando columna language_preference: " . $e->getMessage());
        }
    }
    
    /**
     * Enviar respuesta de éxito
     */
    private function sendSuccess($data = []) {
        echo json_encode([
            'success' => true,
            'timestamp' => date('c'),
            'data' => $data
        ]);
        exit;
    }
    
    /**
     * Enviar respuesta de error
     */
    private function sendError($message, $code = 400) {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'error' => $message,
            'timestamp' => date('c'),
            'code' => $code
        ]);
        exit;
    }
}

// =====================================================
// EJECUTAR API
// =====================================================

try {
    $api = new TranslateAPI();
    $api->handleRequest();
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor',
        'timestamp' => date('c')
    ]);
}