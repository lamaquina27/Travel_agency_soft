<?php
// =====================================
// ARCHIVO: modules/superadmin/usuarios_api.php
// API para Gestión de Usuarios de Agencias por Superadmin
// GRUPO DE SOLUCIONES 3 + GRUPO 4 (GESTIÓN DE SUPERADMINS)
// =====================================

ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/functions.php';
require_once dirname(__DIR__, 2) . '/classes/OperadorManager.php';

App::init();
App::requireRole('superadmin'); // Solo superadmin puede acceder

class SuperadminUsuariosAPI {
    private $db;
    private $userId;
    
    public function __construct() {
        try {
            $this->db = Database::getInstance();
            $user = App::getUser();
            $this->userId = $user['id'];
        } catch(Exception $e) {
            $this->sendError('Error de conexión a base de datos: ' . $e->getMessage());
        }
    }
    
    public function handleRequest() {
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        
        $action = $_POST['action'] ?? $_GET['action'] ?? '';
        
        try {
            error_log("=== SUPERADMIN USUARIOS API ===");
            error_log("Action: " . $action);
            error_log("User ID: " . $this->userId);
            
            switch($action) {
                case 'list_by_agencia':
                    $result = $this->listUsersByAgencia($_GET['agencia_id'] ?? null);
                    break;
                case 'create':
                    $result = $this->createUserForAgencia();
                    break;
                case 'get':
                    $result = $this->getUser($_GET['user_id'] ?? null);
                    break;
                case 'update':
                    $result = $this->updateUser($_POST['user_id'] ?? null);
                    break;
                case 'toggle_status':
                    $result = $this->toggleUserStatus($_POST['user_id'] ?? null);
                    break;
                case 'change_password':
                    $result = $this->changeUserPassword($_POST['user_id'] ?? null);
                    break;
                case 'check_email':
                    $result = $this->checkEmailExists($_GET['email'] ?? null, $_GET['user_id'] ?? null);
                    break;
                    
                // ========================================
                // NUEVAS ACCIONES GRUPO 4: GESTIÓN DE SUPERADMINS
                // ========================================
                case 'list_superadmins':
                    $result = $this->listSuperadmins();
                    break;
                case 'create_superadmin':
                    $result = $this->createSuperadmin();
                    break;
                case 'update_superadmin':
                    $result = $this->updateSuperadmin($_POST['user_id'] ?? null);
                    break;
                case 'delete_superadmin':
                    $result = $this->deleteSuperadmin($_POST['user_id'] ?? null);
                    break;
                    
                default:
                    throw new Exception('Acción no válida: ' . $action);
            }
            
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            
        } catch(Exception $e) {
            error_log("Error en API Usuarios: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }
    
    // =====================================
    // LISTAR USUARIOS POR AGENCIA
    // =====================================
    private function listUsersByAgencia($agenciaId) {
        try {
            if (!$agenciaId) {
                throw new Exception('ID de agencia requerido');
            }
            
            // Verificar que la agencia existe
            $agencia = $this->db->fetch("SELECT * FROM agencias WHERE id = ?", [$agenciaId]);
            if (!$agencia) {
                throw new Exception('Agencia no encontrada');
            }
            
            // Obtener usuarios de la agencia
            $usuarios = $this->db->fetchAll(
                "SELECT 
                    id,
                    username,
                    email,
                    full_name,
                    role,
                    active,
                    last_login,
                    created_at,
                    updated_at
                FROM users 
                WHERE agencia_id = ? 
                ORDER BY created_at DESC",
                [$agenciaId]
            );
            
            return [
                'success' => true,
                'usuarios' => $usuarios,
                'agencia' => $agencia
            ];
            
        } catch(Exception $e) {
            error_log("Error en listUsersByAgencia: " . $e->getMessage());
            throw $e;
        }
    }
    
    // =====================================
    // CREAR USUARIO PARA AGENCIA
    // =====================================
    private function createUserForAgencia() {
        try {
            // Validar datos requeridos
            $agenciaId = $_POST['agencia_id'] ?? null;
            $email = trim($_POST['email'] ?? '');
            $fullName = trim($_POST['full_name'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? '';
            
            // Validaciones básicas
            if (!$agenciaId) {
                throw new Exception('ID de agencia requerido');
            }
            
            if (empty($email)) {
                throw new Exception('El email es requerido');
            }
            
            if (empty($fullName)) {
                throw new Exception('El nombre completo es requerido');
            }
            
            if (empty($password)) {
                throw new Exception('La contraseña es requerida');
            }
            
            if (empty($role) || !in_array($role, ['admin', 'agent', 'operador'])) {
                throw new Exception('El rol debe ser admin, agent u operador');
            }
            
            // Validar email
            $emailValidation = validateEmail($email);
            if (!$emailValidation['valid']) {
                throw new Exception($emailValidation['message']);
            }
            $email = $emailValidation['email'];
            
            // Validar contraseña con función centralizada
            $passwordValidation = validatePassword($password);
            if (!$passwordValidation['valid']) {
                throw new Exception($passwordValidation['message']);
            }
            
            // Verificar que la agencia existe
            $agencia = $this->db->fetch("SELECT * FROM agencias WHERE id = ?", [$agenciaId]);
            if (!$agencia) {
                throw new Exception('Agencia no encontrada');
            }
            
            // VALIDAR LÍMITE DE USUARIOS (Tarea 3.4)
            $usuariosActuales = $this->db->fetch(
                "SELECT COUNT(*) as total FROM users WHERE agencia_id = ? AND active = 1",
                [$agenciaId]
            );
            
            $totalUsuarios = intval($usuariosActuales['total']);
            $maxUsuarios = intval($agencia['max_usuarios']);
            
            if ($totalUsuarios >= $maxUsuarios) {
                throw new Exception(
                    "No se puede crear más usuarios. La agencia ha alcanzado su límite de {$maxUsuarios} usuarios."
                );
            }
            
            // Verificar que el email no exista
            $emailExists = $this->db->fetch(
                "SELECT id FROM users WHERE email = ?",
                [$email]
            );
            
            if ($emailExists) {
                throw new Exception('El email ya está registrado en el sistema');
            }
            
            // Generar username único desde el email
            $username = $this->generateUsername($email);
            
            // Hash de la contraseña
            $hashedPassword = hashPassword($password);
            
            // Datos del usuario
            $userData = [
                'username' => $username,
                'email' => $email,
                'password' => $hashedPassword,
                'full_name' => $fullName,
                'role' => $role,
                'agencia_id' => $agenciaId,
                'active' => 1
            ];
            
            // Insertar usuario
            $userId = $this->db->insert('users', $userData);
            
            if (!$userId) {
                throw new Exception('Error al crear el usuario');
            }

            // Sincronizar pool de operadores según el rol
            OperadorManager::sync($this->db, (int) $userId, (int) $agenciaId, $role);

            // REGISTRAR EN HISTORIAL DE AGENCIA (Tarea 3.6)
            $datosNuevos = json_encode([
                'user_id' => $userId,
                'email' => $email,
                'full_name' => $fullName,
                'role' => $role,
                'username' => $username
            ], JSON_UNESCAPED_UNICODE);
            
            $this->db->insert('agencias_historial', [
                'agencia_id' => $agenciaId,
                'tipo_evento' => 'creacion_usuario',
                'descripcion' => "Usuario creado: {$fullName} ({$email}) con rol {$role}",
                'usuario_superadmin_id' => $this->userId,
                'datos_nuevos' => $datosNuevos
            ]);
            
            return [
                'success' => true,
                'message' => 'Usuario creado exitosamente',
                'user_id' => $userId
            ];
            
        } catch(Exception $e) {
            error_log("Error en createUserForAgencia: " . $e->getMessage());
            throw $e;
        }
    }
    
    // Continúa en el siguiente mensaje...
    
    // =====================================
    // OBTENER DATOS DE UN USUARIO
    // =====================================
    private function getUser($userId) {
        try {
            if (!$userId) {
                throw new Exception('ID de usuario requerido');
            }
            
            $user = $this->db->fetch(
                "SELECT 
                    id,
                    username,
                    email,
                    full_name,
                    role,
                    agencia_id,
                    active,
                    last_login,
                    created_at
                FROM users 
                WHERE id = ?",
                [$userId]
            );
            
            if (!$user) {
                throw new Exception('Usuario no encontrado');
            }
            
            return [
                'success' => true,
                'user' => $user
            ];
            
        } catch(Exception $e) {
            error_log("Error en getUser: " . $e->getMessage());
            throw $e;
        }
    }
    
    // =====================================
    // ACTUALIZAR USUARIO
    // =====================================
    private function updateUser($userId) {
        try {
            if (!$userId) {
                throw new Exception('ID de usuario requerido');
            }
            
            // Obtener usuario actual
            $userActual = $this->db->fetch("SELECT * FROM users WHERE id = ?", [$userId]);
            if (!$userActual) {
                throw new Exception('Usuario no encontrado');
            }
            
            // Validar que no sea superadmin
            if ($userActual['role'] === 'superadmin') {
                throw new Exception('No se puede editar usuarios superadmin desde esta interfaz');
            }
            
            // Preparar datos a actualizar
            $updateData = [];
            
            // Email
            if (isset($_POST['email']) && !empty($_POST['email'])) {
                $email = trim($_POST['email']);
                $emailValidation = validateEmail($email);
                
                if (!$emailValidation['valid']) {
                    throw new Exception($emailValidation['message']);
                }
                
                $email = $emailValidation['email'];
                
                // Verificar que el email no exista en otro usuario
                $emailExists = $this->db->fetch(
                    "SELECT id FROM users WHERE email = ? AND id != ?",
                    [$email, $userId]
                );
                
                if ($emailExists) {
                    throw new Exception('El email ya está registrado en otro usuario');
                }
                
                $updateData['email'] = $email;
            }
            
            // Nombre completo
            if (isset($_POST['full_name']) && !empty($_POST['full_name'])) {
                $updateData['full_name'] = sanitizeString($_POST['full_name']);
            }
            
            // Rol (solo admin o agent)
            if (isset($_POST['role']) && !empty($_POST['role'])) {
                $role = $_POST['role'];
                
                if (!in_array($role, ['admin', 'agent', 'operador'])) {
                    throw new Exception('El rol debe ser admin, agent u operador');
                }
                
                $updateData['role'] = $role;
            }
            
            // Contraseña (OPCIONAL)
            if (isset($_POST['password']) && !empty($_POST['password'])) {
                $password = $_POST['password'];
                
                // Validar contraseña
                $passwordValidation = validatePassword($password);
                if (!$passwordValidation['valid']) {
                    throw new Exception($passwordValidation['message']);
                }
                
                $updateData['password'] = hashPassword($password);
            }
            
            // Si no hay nada que actualizar
            if (empty($updateData)) {
                throw new Exception('No hay datos para actualizar');
            }
            
            // Actualizar
            $updated = $this->db->update(
                'users',
                $updateData,
                'id = ?',
                [$userId]
            );
            
            if (!$updated) {
                throw new Exception('Error al actualizar el usuario');
            }

            // Sincronizar pool de operadores según el rol efectivo
            OperadorManager::sync($this->db, (int) $userId, (int) $userActual['agencia_id'], $updateData['role'] ?? $userActual['role']);

            // REGISTRAR EN HISTORIAL
            $descripcionCambios = [];
            if (isset($updateData['email'])) {
                $descripcionCambios[] = "Email actualizado a: {$updateData['email']}";
            }
            if (isset($updateData['full_name'])) {
                $descripcionCambios[] = "Nombre actualizado a: {$updateData['full_name']}";
            }
            if (isset($updateData['role'])) {
                $descripcionCambios[] = "Rol cambiado a: {$updateData['role']}";
            }
            if (isset($updateData['password'])) {
                $descripcionCambios[] = "Contraseña actualizada";
            }
            
            $descripcion = "Usuario editado: {$userActual['full_name']} - " . implode(', ', $descripcionCambios);
            
            $this->db->insert('agencias_historial', [
                'agencia_id' => $userActual['agencia_id'],
                'tipo_evento' => 'edicion_usuario',
                'descripcion' => $descripcion,
                'usuario_superadmin_id' => $this->userId,
                'datos_anteriores' => json_encode([
                    'user_id' => $userId,
                    'email' => $userActual['email'],
                    'full_name' => $userActual['full_name'],
                    'role' => $userActual['role']
                ], JSON_UNESCAPED_UNICODE),
                'datos_nuevos' => json_encode(array_merge(
                    ['user_id' => $userId],
                    $updateData
                ), JSON_UNESCAPED_UNICODE)
            ]);
            
            return [
                'success' => true,
                'message' => 'Usuario actualizado exitosamente'
            ];
            
        } catch(Exception $e) {
            error_log("Error en updateUser: " . $e->getMessage());
            throw $e;
        }
    }
    
    // =====================================
    // ACTIVAR/DESACTIVAR USUARIO
    // =====================================
    private function toggleUserStatus($userId) {
        try {
            if (!$userId) {
                throw new Exception('ID de usuario requerido');
            }
            
            // Obtener usuario
            $user = $this->db->fetch("SELECT * FROM users WHERE id = ?", [$userId]);
            if (!$user) {
                throw new Exception('Usuario no encontrado');
            }
            
            // Validar que no sea superadmin
            if ($user['role'] === 'superadmin') {
                throw new Exception('No se puede cambiar el estado de usuarios superadmin');
            }
            
            // Cambiar estado
            $nuevoEstado = $user['active'] ? 0 : 1;
            
            $updated = $this->db->update(
                'users',
                ['active' => $nuevoEstado],
                'id = ?',
                [$userId]
            );
            
            if (!$updated) {
                throw new Exception('Error al cambiar el estado del usuario');
            }
            
            $accion = $nuevoEstado ? 'activado' : 'desactivado';
            
            // REGISTRAR EN HISTORIAL
            $this->db->insert('agencias_historial', [
                'agencia_id' => $user['agencia_id'],
                'tipo_evento' => $nuevoEstado ? 'activacion_usuario' : 'desactivacion_usuario',
                'descripcion' => "Usuario {$accion}: {$user['full_name']} ({$user['email']})",
                'usuario_superadmin_id' => $this->userId,
                'datos_nuevos' => json_encode([
                    'user_id' => $userId,
                    'email' => $user['email'],
                    'full_name' => $user['full_name'],
                    'estado' => $accion
                ], JSON_UNESCAPED_UNICODE)
            ]);
            
            return [
                'success' => true,
                'message' => "Usuario {$accion} exitosamente",
                'new_status' => $nuevoEstado
            ];
            
        } catch(Exception $e) {
            error_log("Error en toggleUserStatus: " . $e->getMessage());
            throw $e;
        }
    }
    
    // =====================================
    // CAMBIAR CONTRASEÑA DE USUARIO
    // =====================================
    private function changeUserPassword($userId) {
        try {
            if (!$userId) {
                throw new Exception('ID de usuario requerido');
            }
            
            $newPassword = $_POST['new_password'] ?? '';
            
            if (empty($newPassword)) {
                throw new Exception('La nueva contraseña es requerida');
            }
            
            // Obtener usuario
            $user = $this->db->fetch("SELECT * FROM users WHERE id = ?", [$userId]);
            if (!$user) {
                throw new Exception('Usuario no encontrado');
            }
            
            // Validar que no sea superadmin
            if ($user['role'] === 'superadmin') {
                throw new Exception('No se puede cambiar la contraseña de usuarios superadmin desde esta interfaz');
            }
            
            // Validar contraseña
            $passwordValidation = validatePassword($newPassword);
            if (!$passwordValidation['valid']) {
                throw new Exception($passwordValidation['message']);
            }
            
            // Actualizar contraseña
            $hashedPassword = hashPassword($newPassword);
            
            $updated = $this->db->update(
                'users',
                ['password' => $hashedPassword],
                'id = ?',
                [$userId]
            );
            
            if (!$updated) {
                throw new Exception('Error al cambiar la contraseña');
            }
            
            return [
                'success' => true,
                'message' => 'Contraseña cambiada exitosamente'
            ];
            
        } catch(Exception $e) {
            error_log("Error en changeUserPassword: " . $e->getMessage());
            throw $e;
        }
    }
    
    // =====================================
    // VERIFICAR SI EMAIL EXISTE
    // =====================================
    private function checkEmailExists($email, $excludeUserId = null) {
        try {
            if (empty($email)) {
                return [
                    'success' => true,
                    'exists' => false
                ];
            }
            
            $email = trim($email);
            
            // Validar formato
            $emailValidation = validateEmail($email);
            if (!$emailValidation['valid']) {
                return [
                    'success' => false,
                    'message' => $emailValidation['message']
                ];
            }
            
            // Buscar email
            $query = "SELECT id FROM users WHERE email = ?";
            $params = [$email];
            
            // Si se excluye un ID (para edición)
            if ($excludeUserId) {
                $query .= " AND id != ?";
                $params[] = $excludeUserId;
            }
            
            $exists = $this->db->fetch($query, $params);
            
            return [
                'success' => true,
                'exists' => $exists ? true : false
            ];
            
        } catch(Exception $e) {
            error_log("Error en checkEmailExists: " . $e->getMessage());
            throw $e;
        }
    }
    
    // ========================================
    // 🆕 GRUPO 4: MÉTODOS PARA GESTIÓN DE SUPERADMINS
    // ========================================
    
    /**
     * TAREA 4.3.1: Listar todos los superadministradores
     */
    private function listSuperadmins() {
        try {
            $superadmins = $this->db->fetchAll(
                "SELECT 
                    id,
                    username,
                    email,
                    full_name,
                    active,
                    last_login,
                    created_at
                FROM users 
                WHERE role = 'superadmin'
                ORDER BY id ASC"
            );
            
            return [
                'success' => true,
                'superadmins' => $superadmins
            ];
            
        } catch(Exception $e) {
            error_log("Error en listSuperadmins: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * TAREA 4.3.2: Crear nuevo superadministrador
     * IMPORTANTE: agencia_id debe ser NULL para superadmins
     */
    private function createSuperadmin() {
        try {
            // Validar datos requeridos
            $email = trim($_POST['email'] ?? '');
            $fullName = trim($_POST['full_name'] ?? '');
            $password = $_POST['password'] ?? '';
            
            // Validaciones básicas
            if (empty($email)) {
                throw new Exception('El email es requerido');
            }
            
            if (empty($fullName)) {
                throw new Exception('El nombre completo es requerido');
            }
            
            if (empty($password)) {
                throw new Exception('La contraseña es requerida');
            }
            
            // Validar email
            $emailValidation = validateEmail($email);
            if (!$emailValidation['valid']) {
                throw new Exception($emailValidation['message']);
            }
            $email = $emailValidation['email'];
            
            // Validar contraseña
            $passwordValidation = validatePassword($password);
            if (!$passwordValidation['valid']) {
                throw new Exception($passwordValidation['message']);
            }
            
            // Verificar que el email no exista
            $emailExists = $this->db->fetch(
                "SELECT id FROM users WHERE email = ?",
                [$email]
            );
            
            if ($emailExists) {
                throw new Exception('El email ya está registrado en el sistema');
            }
            
            // Generar username único desde el email
            $username = $this->generateUsername($email);
            
            // Hash de la contraseña
            $hashedPassword = hashPassword($password);
            
            // Datos del superadmin
            // ⚠️ CRÍTICO: agencia_id = NULL para superadmins
            $userData = [
                'username' => $username,
                'email' => $email,
                'password' => $hashedPassword,
                'full_name' => $fullName,
                'role' => 'superadmin',
                'agencia_id' => null,  // NULL porque no pertenece a ninguna agencia
                'active' => 1
            ];
            
            // Insertar superadmin
            $userId = $this->db->insert('users', $userData);
            
            if (!$userId) {
                throw new Exception('Error al crear el superadministrador');
            }
            
            error_log("✅ Nuevo superadmin creado: ID={$userId}, Email={$email}");
            
            return [
                'success' => true,
                'message' => 'Superadministrador creado exitosamente',
                'user_id' => $userId
            ];
            
        } catch(Exception $e) {
            error_log("Error en createSuperadmin: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * TAREA 4.3.3: Actualizar superadministrador
     * VALIDACIÓN: No permitir editar al superadmin con id=1
     */
    private function updateSuperadmin($userId) {
        try {
            if (!$userId) {
                throw new Exception('ID de usuario requerido');
            }
            
            // VALIDACIÓN CRÍTICA: Proteger al superadmin original (id=1)
            if ($userId == 1) {
                throw new Exception('No se puede editar al superadministrador original del sistema');
            }
            
            // Obtener usuario actual
            $userActual = $this->db->fetch("SELECT * FROM users WHERE id = ?", [$userId]);
            if (!$userActual) {
                throw new Exception('Usuario no encontrado');
            }
            
            // Validar que sea superadmin
            if ($userActual['role'] !== 'superadmin') {
                throw new Exception('El usuario no es un superadministrador');
            }
            
            // VALIDACIÓN: No permitir que se desactive a sí mismo
            if ($userId == $this->userId && isset($_POST['active']) && $_POST['active'] == 0) {
                throw new Exception('No puedes desactivarte a ti mismo');
            }
            
            // Preparar datos a actualizar
            $updateData = [];
            
            // Email
            if (isset($_POST['email']) && !empty($_POST['email'])) {
                $email = trim($_POST['email']);
                $emailValidation = validateEmail($email);
                
                if (!$emailValidation['valid']) {
                    throw new Exception($emailValidation['message']);
                }
                
                $email = $emailValidation['email'];
                
                // Verificar que el email no exista en otro usuario
                $emailExists = $this->db->fetch(
                    "SELECT id FROM users WHERE email = ? AND id != ?",
                    [$email, $userId]
                );
                
                if ($emailExists) {
                    throw new Exception('El email ya está registrado en otro usuario');
                }
                
                $updateData['email'] = $email;
            }
            
            // Nombre completo
            if (isset($_POST['full_name']) && !empty($_POST['full_name'])) {
                $updateData['full_name'] = sanitizeString($_POST['full_name']);
            }
            
            // Contraseña (OPCIONAL)
            if (isset($_POST['password']) && !empty($_POST['password'])) {
                $password = $_POST['password'];
                
                // Validar contraseña
                $passwordValidation = validatePassword($password);
                if (!$passwordValidation['valid']) {
                    throw new Exception($passwordValidation['message']);
                }
                
                $updateData['password'] = hashPassword($password);
            }
            
            // Si no hay nada que actualizar
            if (empty($updateData)) {
                throw new Exception('No hay datos para actualizar');
            }
            
            // Actualizar
            $updated = $this->db->update(
                'users',
                $updateData,
                'id = ?',
                [$userId]
            );
            
            if (!$updated) {
                throw new Exception('Error al actualizar el superadministrador');
            }
            
            error_log("✅ Superadmin actualizado: ID={$userId}");
            
            return [
                'success' => true,
                'message' => 'Superadministrador actualizado exitosamente'
            ];
            
        } catch(Exception $e) {
            error_log("Error en updateSuperadmin: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * TAREA 4.3.4 y 4.4: Eliminar superadministrador
     * VALIDACIONES:
     * - No permitir eliminar al superadmin con id=1
     * - No permitir eliminar al último superadmin del sistema
     * - No permitir que se elimine a sí mismo
     */
    private function deleteSuperadmin($userId) {
        try {
            if (!$userId) {
                throw new Exception('ID de usuario requerido');
            }
            
            // VALIDACIÓN CRÍTICA 1: Proteger al superadmin original (id=1)
            if ($userId == 1) {
                throw new Exception('No se puede eliminar al superadministrador original del sistema');
            }
            
            // VALIDACIÓN CRÍTICA 2: No permitir que se elimine a sí mismo
            if ($userId == $this->userId) {
                throw new Exception('No puedes eliminarte a ti mismo');
            }
            
            // Obtener usuario
            $user = $this->db->fetch("SELECT * FROM users WHERE id = ?", [$userId]);
            if (!$user) {
                throw new Exception('Usuario no encontrado');
            }
            
            // Validar que sea superadmin
            if ($user['role'] !== 'superadmin') {
                throw new Exception('El usuario no es un superadministrador');
            }
            
            // VALIDACIÓN CRÍTICA 3: Verificar que no sea el último superadmin
            $totalSuperadmins = $this->db->fetch(
                "SELECT COUNT(*) as total FROM users WHERE role = 'superadmin' AND active = 1"
            );
            
            if ($totalSuperadmins['total'] <= 1) {
                throw new Exception('No se puede eliminar el último superadministrador activo del sistema');
            }
            
            // Eliminar el superadmin
            $deleted = $this->db->delete('users', 'id = ?', [$userId]);
            
            if (!$deleted) {
                throw new Exception('Error al eliminar el superadministrador');
            }
            
            error_log("✅ Superadmin eliminado: ID={$userId}, Email={$user['email']}");
            
            return [
                'success' => true,
                'message' => 'Superadministrador eliminado exitosamente'
            ];
            
        } catch(Exception $e) {
            error_log("Error en deleteSuperadmin: " . $e->getMessage());
            throw $e;
        }
    }
    
    // =====================================
    // GENERAR USERNAME ÚNICO
    // =====================================
    private function generateUsername($email) {
        // Extraer parte del email antes del @
        $baseName = strtolower(explode('@', $email)[0]);
        
        // Limpiar caracteres especiales
        $baseName = preg_replace('/[^a-z0-9]/', '', $baseName);
        
        // Verificar si existe
        $username = $baseName;
        $counter = 1;
        
        while (true) {
            $exists = $this->db->fetch(
                "SELECT id FROM users WHERE username = ?",
                [$username]
            );
            
            if (!$exists) {
                break;
            }
            
            $username = $baseName . $counter;
            $counter++;
        }
        
        return $username;
    }
    
    // =====================================
    // MÉTODO AUXILIAR PARA ENVIAR ERRORES
    // =====================================
    private function sendError($message) {
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => $message
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// =====================================
// INICIALIZAR Y EJECUTAR API
// =====================================
try {
    $api = new SuperadminUsuariosAPI();
    $api->handleRequest();
} catch(Exception $e) {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}