<?php
// =====================================
// ARCHIVO: modules/admin/api.php - API COMPLETA CORREGIDA
// =====================================

// Evitar cualquier output antes del JSON
ob_start();

// Configurar error handling para que no muestre errores en pantalla
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Incluir archivos necesarios
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/config_functions.php';
require_once dirname(__DIR__, 2) . '/classes/OperadorManager.php';

// Verificar sesión y permisos
App::init();
App::requireRole('admin');

class AdminAPI {
    private $db;

    private function validatePassword($password) {
        // Validaciones
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
    
    public function __construct() {
        try {
            $this->db = Database::getInstance();
        } catch(Exception $e) {
            $this->sendError('Error de conexión a base de datos: ' . $e->getMessage());
        }
    }
    
    public function handleRequest() {
        // Limpiar cualquier output previo
        ob_clean();
        
        // Establecer headers
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        
        $action = $_POST['action'] ?? $_GET['action'] ?? '';
        
        try {
            switch($action) {
                // Configuración
                case 'save_config':
                    $result = $this->saveConfiguration();
                    break;
                case 'get_config':
                    $result = $this->getConfiguration();
                    break;
                case 'upload_config_image':
                    $result = $this->uploadConfigImage();
                    break;
                    
                // Usuarios - ACCIONES FALTANTES
                case 'users':
                    $result = $this->getUsers();
                    break;
                case 'create_user':
                    $result = $this->createUser();
                    break;
                case 'update_user':
                    $result = $this->updateUser();
                    break;
                case 'toggle_user':
                    $result = $this->toggleUser();
                    break;
                case 'delete_user':
                    $result = $this->deleteUser();
                    break;
                    
                // Estadísticas
                case 'statistics':
                    $result = $this->getStatistics();
                    break;
                    
                default:
                    $result = ['success' => false, 'error' => 'Acción no válida: ' . $action];
            }
            
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            
        } catch(Exception $e) {
            error_log("Admin API Error ({$action}): " . $e->getMessage() . " - " . $e->getTraceAsString());
            $this->sendError($e->getMessage());
        }
        
        exit;
    }
    
    private function sendError($message) {
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // ============================================
    // MÉTODOS DE USUARIOS - IMPLEMENTACIÓN COMPLETA
    // ============================================
    
    private function createUser() {
        try {
            // Validar datos obligatorios
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $full_name = trim($_POST['full_name'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? '';
            $active = isset($_POST['active']) ? (int)$_POST['active'] : 1;

            // ===== OBTENER AGENCIA DEL ADMIN =====
            $agenciaId = $_SESSION['agencia_id'] ?? null;

            if (!$agenciaId) {
                throw new Exception('Error: No se pudo identificar la agencia del administrador');
            }
            
            if (empty($username) || empty($email) || empty($full_name) || empty($password) || empty($role)) {
                throw new Exception('Todos los campos obligatorios deben estar completos');
            }
            
            // Validar longitud de campos
            if (strlen($username) < 3 || strlen($username) > 50) {
                throw new Exception('El nombre de usuario debe tener entre 3 y 50 caracteres');
            }
            
            // Validar longitud mínima
            if (strlen($password) < 8) {
                throw new Exception('La contraseña debe tener al menos 8 caracteres');
            }
            // Validar mayúscula
            if (!preg_match('/[A-Z]/', $password)) {
                throw new Exception('La contraseña debe incluir al menos una letra mayúscula (A-Z)');
            }
            // Validar minúscula
            if (!preg_match('/[a-z]/', $password)) {
                throw new Exception('La contraseña debe incluir al menos una letra minúscula (a-z)');
            }
            // Validar número
            if (!preg_match('/[0-9]/', $password)) {
                throw new Exception('La contraseña debe incluir al menos un número (0-9)');
            }
            // Validar carácter especial
            if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
                throw new Exception('La contraseña debe incluir al menos un carácter especial (!@#$%^&*)');
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('El email no tiene un formato válido');
            }
            
            if (!in_array($role, ['admin', 'agent', 'operador'])) {
                throw new Exception('Rol no válido');
            }
            
            // Verificar que no exista username o email duplicado
            $existing = $this->db->fetch(
                "SELECT id FROM users WHERE username = ? OR email = ?",
                [$username, $email]
            );
            
            if ($existing) {
                throw new Exception('El nombre de usuario o email ya existe');
            }
            
            // Hashear contraseña
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insertar usuario
            $userId = $this->db->insert('users', [
                'username' => $username,
                'email' => $email,
                'full_name' => $full_name,
                'password' => $hashedPassword,
                'role' => $role,
                'agencia_id' => $agenciaId,
                'active' => $active,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            if (!$userId) {
                throw new Exception('Error al crear el usuario en la base de datos');
            }

            // Sincronizar pool de operadores según el rol
            OperadorManager::sync($this->db, (int) $userId, (int) $agenciaId, $role);

            return [
                'success' => true,
                'message' => 'Usuario creado correctamente',
                'data' => ['id' => $userId]
            ];
            
        } catch(Exception $e) {
            throw new Exception('Error al crear usuario: ' . $e->getMessage());
        }
    }
    
    private function updateUser() {
        try {
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                throw new Exception('ID de usuario requerido');
            }
            
            // Verificar que el usuario existe
            // ===== OBTENER AGENCIA DEL ADMIN =====
            $agenciaId = $_SESSION['agencia_id'] ?? null;

            if (!$agenciaId) {
                throw new Exception('Error: No se pudo identificar la agencia');
            }

            // Verificar que el usuario existe y pertenece a la agencia
            $existingUser = $this->db->fetch(
                "SELECT * FROM users WHERE id = ? AND agencia_id = ?", 
                [$id, $agenciaId]
            );

            if (!$existingUser) {
                throw new Exception('Usuario no encontrado o no pertenece a tu agencia');
            }
            
            // Validar datos básicos
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $full_name = trim($_POST['full_name'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $role = $_POST['role'] ?? '';
            $active = isset($_POST['active']) ? (int)$_POST['active'] : 1;
            
            if (empty($username) || empty($email) || empty($full_name) || empty($role)) {
                throw new Exception('Todos los campos obligatorios deben estar completos');
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('El email no tiene un formato válido');
            }
            
            if (!in_array($role, ['admin', 'agent', 'operador'])) {
                throw new Exception('Rol no válido');
            }
            
            // Verificar duplicados (excluyendo el usuario actual)
            $duplicate = $this->db->fetch(
                "SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?",
                [$username, $email, $id]
            );
            
            if ($duplicate) {
                throw new Exception('El nombre de usuario o email ya existe en otro usuario');
            }
            
            // Preparar consulta SQL manualmente para mayor control
            $updateFields = [];
            $updateValues = [];
            
            $updateFields[] = "username = ?";
            $updateValues[] = $username;
            
            $updateFields[] = "email = ?";
            $updateValues[] = $email;
            
            $updateFields[] = "full_name = ?";
            $updateValues[] = $full_name;
            
            $updateFields[] = "role = ?";
            $updateValues[] = $role;
            
            $updateFields[] = "active = ?";
            $updateValues[] = $active;
            
            $updateFields[] = "updated_at = ?";
            $updateValues[] = date('Y-m-d H:i:s');
            
            // Solo actualizar contraseña si se proporcionó una nueva
            if (!empty($password)) {
                // Validar longitud mínima
                if (strlen($password) < 8) {
                    throw new Exception('La contraseña debe tener al menos 8 caracteres');
                }
                // Validar mayúscula
                if (!preg_match('/[A-Z]/', $password)) {
                    throw new Exception('La contraseña debe incluir al menos una letra mayúscula (A-Z)');
                }
                // Validar minúscula
                if (!preg_match('/[a-z]/', $password)) {
                    throw new Exception('La contraseña debe incluir al menos una letra minúscula (a-z)');
                }
                // Validar número
                if (!preg_match('/[0-9]/', $password)) {
                    throw new Exception('La contraseña debe incluir al menos un número (0-9)');
                }
                // Validar carácter especial
                if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
                    throw new Exception('La contraseña debe incluir al menos un carácter especial (!@#$%^&*)');
                }
                
                $updateFields[] = "password = ?";
                $updateValues[] = password_hash($password, PASSWORD_DEFAULT);
            }
            
            // Agregar ID al final para la condición WHERE
            $updateValues[] = $id;
            
            // Construir y ejecutar consulta
            $sql = "UPDATE users SET " . implode(", ", $updateFields) . " WHERE id = ?";

            $stmt = $this->db->query($sql, $updateValues);

            // Sincronizar pool de operadores según el rol
            OperadorManager::sync($this->db, (int) $id, (int) $agenciaId, $role);
            
            if (!$stmt) {
                throw new Exception('Error al actualizar el usuario en la base de datos');
            }
            
            return [
                'success' => true,
                'message' => 'Usuario actualizado correctamente'
            ];
            
        } catch(Exception $e) {
            error_log("Error en updateUser: " . $e->getMessage());
            throw new Exception('Error al actualizar usuario: ' . $e->getMessage());
        }
    }
    
    private function toggleUser() {
        try {
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                throw new Exception('ID de usuario requerido');
            }
            
            error_log("Toggling user ID: " . $id);
            
            // No permitir desactivar al usuario con ID 1 (admin principal)
            if ($id === 1) {
                throw new Exception('No se puede desactivar el administrador principal');
            }
            
            // Obtener usuario actual
            // ===== OBTENER AGENCIA DEL ADMIN =====
            $agenciaId = $_SESSION['agencia_id'] ?? null;

            if (!$agenciaId) {
                throw new Exception('Error: No se pudo identificar la agencia');
            }

            // Obtener usuario actual y verificar que pertenece a la agencia
            $user = $this->db->fetch(
                "SELECT id, username, active FROM users WHERE id = ? AND agencia_id = ?", 
                [$id, $agenciaId]
            );

            if (!$user) {
                throw new Exception('Usuario no encontrado o no pertenece a tu agencia');
            }
            
            error_log("Current user status: " . print_r($user, true));
            
            // Cambiar estado
            $newStatus = $user['active'] ? 0 : 1;
            $action = $newStatus ? 'activado' : 'desactivado';
            
            // Actualizar en base de datos
            $updated = $this->db->update('users', ['active' => $newStatus], 'id = ?', [$id]);
            
            if (!$updated) {
                throw new Exception('Error al actualizar el estado del usuario');
            }
            
            error_log("User {$id} status changed to: " . $newStatus);
            
            return [
                'success' => true,
                'message' => "Usuario {$action} correctamente"
            ];
            
        } catch(Exception $e) {
            error_log("Toggle user error: " . $e->getMessage());
            throw new Exception('Error al cambiar estado: ' . $e->getMessage());
        }
    }
    
    private function deleteUser() {
        try {
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                throw new Exception('ID de usuario requerido');
            }
            
            // No permitir eliminar al usuario con ID 1 (admin principal)
            if ($id === 1) {
                throw new Exception('No se puede eliminar el administrador principal');
            }
            
            // Obtener usuario
            // ===== OBTENER AGENCIA DEL ADMIN =====
            $agenciaId = $_SESSION['agencia_id'] ?? null;

            if (!$agenciaId) {
                throw new Exception('Error: No se pudo identificar la agencia');
            }

            // Obtener usuario y verificar que pertenece a la agencia
            $user = $this->db->fetch(
                "SELECT id, username FROM users WHERE id = ? AND agencia_id = ?", 
                [$id, $agenciaId]
            );

            if (!$user) {
                throw new Exception('Usuario no encontrado o no pertenece a tu agencia');
            }
            
            // Eliminar usuario
            $deleted = $this->db->delete('users', ['id' => $id]);
            
            if (!$deleted) {
                throw new Exception('Error al eliminar el usuario de la base de datos');
            }
            
            return [
                'success' => true,
                'message' => "Usuario '{$user['username']}' eliminado correctamente"
            ];
            
        } catch(Exception $e) {
            throw new Exception('Error al eliminar usuario: ' . $e->getMessage());
        }
    }
    
private function getUsers() {
    try {
        // ===== OBTENER AGENCIA DEL ADMIN =====
        $agenciaId = $_SESSION['agencia_id'] ?? null;
        
        if (!$agenciaId) {
            throw new Exception('Error: No se pudo identificar la agencia del administrador');
        }
        
        // ===== FILTRAR USUARIOS SOLO DE SU AGENCIA =====
        $users = $this->db->fetchAll(
            "SELECT id, username, email, full_name, role, active, last_login, created_at 
             FROM users 
             WHERE agencia_id = ?
             ORDER BY created_at DESC",
            [$agenciaId]
        );
        
        foreach($users as &$user) {
            $user['active'] = (bool)$user['active'];
            $user['last_login_formatted'] = $user['last_login'] ? 
                date('d/m/Y H:i', strtotime($user['last_login'])) : 'Nunca';
            $user['created_at_formatted'] = date('d/m/Y H:i:s', strtotime($user['created_at']));
        }
        
        return ['success' => true, 'data' => $users];
    } catch(Exception $e) {
        throw new Exception('Error obteniendo usuarios: ' . $e->getMessage());
    }
}
    
    private function getStatistics() {
        try {
            // Contar usuarios
            $totalUsers = $this->db->fetch("SELECT COUNT(*) as count FROM users")['count'] ?? 0;
            
            // Contar programas (asumiendo tabla 'programs')
            $totalPrograms = 0;
            if ($this->tableExists('programs')) {
                $totalPrograms = $this->db->fetch("SELECT COUNT(*) as count FROM programs")['count'] ?? 0;
            }
            
            // Contar recursos de biblioteca (asumiendo tabla 'library_resources')
            $totalResources = 0;
            if ($this->tableExists('library_resources')) {
                $totalResources = $this->db->fetch("SELECT COUNT(*) as count FROM library_resources")['count'] ?? 0;
            }
            
            // Contar sesiones activas (últimas 24 horas)
            $activeSessions = $this->db->fetch(
                "SELECT COUNT(DISTINCT user_id) as count FROM users 
                 WHERE last_login > DATE_SUB(NOW(), INTERVAL 24 HOUR)"
            )['count'] ?? 0;
            
            return [
                'success' => true,
                'data' => [
                    'totalUsers' => (int)$totalUsers,
                    'totalPrograms' => (int)$totalPrograms,
                    'totalResources' => (int)$totalResources,
                    'activeSessions' => (int)$activeSessions
                ]
            ];
        } catch(Exception $e) {
            throw new Exception('Error obteniendo estadísticas: ' . $e->getMessage());
        }
    }
    
    // ============================================
    // MÉTODOS DE CONFIGURACIÓN (EXISTENTES)
    // ============================================
    
private function saveConfiguration() {
    try {
        // Obtener agencia_id del admin actual
        $agencia_id = $_SESSION['agencia_id'] ?? null;
        
        if (!$agencia_id) {
            throw new Exception('Usuario sin agencia asignada');
        }
        
        // Preparar datos para actualizar en tabla agencias
        $data = [];
        
        // Nombre de la empresa
        if (isset($_POST['company_name'])) {
            $data['nombre'] = trim($_POST['company_name']);
        }
        
        // Logo
        if (isset($_POST['logo_url']) && !empty($_POST['logo_url'])) {
            $data['logo_url'] = $_POST['logo_url'];
        }
        
        // Colores admin
        if (isset($_POST['admin_primary_color'])) {
            $data['admin_primary_color'] = $_POST['admin_primary_color'];
        }
        if (isset($_POST['admin_secondary_color'])) {
            $data['admin_secondary_color'] = $_POST['admin_secondary_color'];
        }
        
        // Colores agent
        if (isset($_POST['agent_primary_color'])) {
            $data['agent_primary_color'] = $_POST['agent_primary_color'];
        }
        if (isset($_POST['agent_secondary_color'])) {
            $data['agent_secondary_color'] = $_POST['agent_secondary_color'];
        }
        
        if (empty($data)) {
            throw new Exception('No hay datos para actualizar');
        }
        
        // Validar colores
        $colorFields = ['admin_primary_color', 'admin_secondary_color', 'agent_primary_color', 'agent_secondary_color'];
        foreach ($colorFields as $field) {
            if (isset($data[$field]) && !preg_match('/^#[0-9A-Fa-f]{6}$/', $data[$field])) {
                throw new Exception("Color {$field} no válido");
            }
        }
        
        // Actualizar tabla agencias
        $this->db->update('agencias', $data, 'id = ?', [$agencia_id]);
        
        return [
            'success' => true,
            'message' => 'Configuración guardada correctamente'
        ];
        
    } catch(Exception $e) {
        error_log("Save config error: " . $e->getMessage());
        throw new Exception('Error al guardar: ' . $e->getMessage());
    }
}
    
private function getConfiguration() {
    try {
        // Obtener agencia_id del admin actual
        $agencia_id = $_SESSION['agencia_id'] ?? null;
        
        if (!$agencia_id) {
            throw new Exception('Usuario sin agencia asignada');
        }
        
        // Obtener configuración de la agencia
        $agencia = $this->db->fetch(
            "SELECT 
                nombre as company_name,
                logo_url,
                admin_primary_color,
                admin_secondary_color,
                agent_primary_color,
                agent_secondary_color
             FROM agencias 
             WHERE id = ?",
            [$agencia_id]
        );
        
        if (!$agencia) {
            throw new Exception('Agencia no encontrada');
        }
        
        return [
            'success' => true,
            'data' => $agencia
        ];
        
    } catch(Exception $e) {
        throw new Exception('Error obteniendo configuración: ' . $e->getMessage());
    }
}
    
    private function uploadConfigImage() {
        try {
            if (!isset($_FILES['image'])) {
                throw new Exception('No se recibió imagen');
            }
            
            $file = $_FILES['image'];
            $type = $_POST['type'] ?? 'general';
            
            // Validaciones
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Error en la subida del archivo');
            }
            
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($file['type'], $allowedTypes)) {
                throw new Exception('Tipo de archivo no permitido');
            }
            
            if ($file['size'] > 10 * 1024 * 1024) {
                throw new Exception('Archivo demasiado grande (máx 10MB)');
            }
            
            // Crear directorio
            $uploadDir = dirname(__DIR__, 2) . '/assets/uploads/config/';
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true)) {
                    throw new Exception('No se pudo crear directorio de uploads');
                }
            }
            
            // Generar nombre
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = $type . '_' . uniqid() . '.' . $extension;
            $filepath = $uploadDir . $filename;
            
            // Mover archivo
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                throw new Exception('Error moviendo archivo');
            }
            
            $url = APP_URL . '/assets/uploads/config/' . $filename;
            
            return [
                'success' => true,
                'url' => $url,
                'message' => 'Imagen subida correctamente'
            ];
            
        } catch(Exception $e) {
            throw new Exception('Error subiendo imagen: ' . $e->getMessage());
        }
    }
    
    // ============================================
    // MÉTODOS AUXILIARES
    // ============================================
    
    private function tableExists($tableName) {
        try {
            $result = $this->db->fetch("SHOW TABLES LIKE ?", [$tableName]);
            return !empty($result);
        } catch(Exception $e) {
            return false;
        }
    }
    
    private function ensureConfigTable() {
        $sql = "CREATE TABLE IF NOT EXISTS `company_settings` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `company_name` VARCHAR(100) DEFAULT 'Travel Agency',
            `logo_url` VARCHAR(255) NULL,
            `background_image` VARCHAR(255) NULL,
            `admin_primary_color` VARCHAR(7) DEFAULT '#e53e3e',
            `admin_secondary_color` VARCHAR(7) DEFAULT '#fd746c',
            `agent_primary_color` VARCHAR(7) DEFAULT '#667eea',
            `agent_secondary_color` VARCHAR(7) DEFAULT '#764ba2',
            `login_bg_color` VARCHAR(7) DEFAULT '#667eea',
            `login_secondary_color` VARCHAR(7) DEFAULT '#764ba2',
            `default_language` VARCHAR(5) DEFAULT 'es',
            `session_timeout` INT DEFAULT 60,
            `max_file_size` INT DEFAULT 10,
            `backup_frequency` ENUM('daily','weekly','monthly','never') DEFAULT 'weekly',
            `maintenance_mode` TINYINT(1) DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $this->db->query($sql);
    }
    
    private function prepareConfigData($data) {
        $allowed = [
            'company_name', 'logo_url', 'background_image',
            'admin_primary_color', 'admin_secondary_color',
            'agent_primary_color', 'agent_secondary_color',
            'login_bg_color', 'login_secondary_color',
            'default_language', 'max_file_size',
            'backup_frequency', 'maintenance_mode'
        ];
        
        $configData = [];
        foreach ($allowed as $key) {
            if (isset($data[$key])) {
                $configData[$key] = $data[$key];
            }
        }
        
        // ✅ CONVERSIÓN IMPORTANTE
        if (isset($configData['maintenance_mode'])) {
            $configData['maintenance_mode'] = (int)$configData['maintenance_mode'];
        }
        if (isset($configData['max_file_size'])) {
            $configData['max_file_size'] = (int)$configData['max_file_size'];
        }
        
        return $configData;
    }
    
    private function validateConfigData($data) {
        // Validar colores hex
        $colorFields = [
            'admin_primary_color', 'admin_secondary_color',
            'agent_primary_color', 'agent_secondary_color',
            'login_bg_color', 'login_secondary_color'
        ];
        
        foreach ($colorFields as $field) {
            if (isset($data[$field]) && !preg_match('/^#[0-9A-Fa-f]{6}$/', $data[$field])) {
                throw new Exception("Color {$field} no válido");
            }
        }
        
        // Validar números
        if (isset($data['max_file_size']) && ((int)$data['max_file_size'] < 1 || (int)$data['max_file_size'] > 100)) {
            throw new Exception('El tamaño máximo de archivo debe estar entre 1 y 100 MB');
        }
    }
    
    private function updateConfig($id, $data) {
        try {
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            // Preparar consulta SQL manualmente
            $updateFields = [];
            $updateValues = [];
            
            foreach ($data as $key => $value) {
                $updateFields[] = "`{$key}` = ?";
                $updateValues[] = $value;
            }
            
            // Agregar ID para WHERE
            $updateValues[] = $id;
            
            // Ejecutar consulta
            $sql = "UPDATE company_settings SET " . implode(", ", $updateFields) . " WHERE id = ?";
            $result = $this->db->query($sql, $updateValues);
            
            if (!$result) {
                throw new Exception('Error al ejecutar la consulta de actualización');
            }
            
            return true;
            
        } catch(Exception $e) {
            error_log("Error en updateConfig: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function createConfig($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->db->insert('company_settings', $data);
    }
}

// ============================================
// INICIALIZAR Y PROCESAR SOLICITUD
// ============================================

try {
    $api = new AdminAPI();
    $api->handleRequest();
} catch(Exception $e) {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false, 
        'error' => 'Error interno del servidor: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}