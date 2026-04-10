<?php
// =====================================
// ARCHIVO: modules/superadmin/agencias_api.php
// API para Gestión de Agencias por Superadmin
// =====================================

ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/app.php';

App::init();
App::requireRole('superadmin'); // Solo superadmin puede acceder

class SuperadminAgenciasAPI {
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
            error_log("=== SUPERADMIN AGENCIAS API ===");
            error_log("Action: " . $action);
            error_log("User ID: " . $this->userId);
            
            switch($action) {
                case 'create':
                    $result = $this->createAgencia();
                    break;
                case 'list':
                    $result = $this->listAgencias();
                    break;
                case 'get':
                    $result = $this->getAgencia($_GET['id'] ?? null);
                    break;
                case 'update':
                    $result = $this->updateAgencia($_POST['id'] ?? null);
                    break;
                case 'get_historial':
                    $result = $this->getHistorial($_GET['agencia_id'] ?? null);
                    break;
                default:
                    throw new Exception('Acción no válida: ' . $action);
            }
            
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            
        } catch(Exception $e) {
            error_log("Error en API Agencias: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $this->sendError($e->getMessage());
        }
    }
    
    // =====================================
    // CREAR NUEVA AGENCIA
    // =====================================
    private function createAgencia() {
        try {
            // Validar datos requeridos
            if (empty($_POST['nombre'])) {
                throw new Exception('El nombre de la agencia es requerido');
            }
            if (empty($_POST['fecha_inicio_suscripcion'])) {
                throw new Exception('La fecha de inicio de suscripción es requerida');
            }
            if (empty($_POST['fecha_fin_suscripcion'])) {
                throw new Exception('La fecha de fin de suscripción es requerida');
            }
            if (empty($_POST['max_usuarios']) || !is_numeric($_POST['max_usuarios'])) {
                throw new Exception('El número máximo de usuarios es requerido');
            }
            
            // Preparar datos para insertar
            $data = [
                'nombre' => trim($_POST['nombre']),
                'descripcion' => isset($_POST['descripcion']) ? trim($_POST['descripcion']) : null,
                'nit' => isset($_POST['nit']) ? trim($_POST['nit']) : null,
                'direccion' => isset($_POST['direccion']) ? trim($_POST['direccion']) : null,
                'telefono' => isset($_POST['telefono']) ? trim($_POST['telefono']) : null,
                'email_contacto' => isset($_POST['email_contacto']) ? trim($_POST['email_contacto']) : null,
                'fecha_inicio_suscripcion' => $_POST['fecha_inicio_suscripcion'],
                'fecha_fin_suscripcion' => $_POST['fecha_fin_suscripcion'],
                'estado_suscripcion' => 'activa',
                'max_usuarios' => intval($_POST['max_usuarios']),
                'activa' => true
            ];
            
            // Insertar agencia
            $agenciaId = $this->db->insert('agencias', $data);
            
            if (!$agenciaId) {
                throw new Exception('Error al crear la agencia');
            }
            
            // Registrar en historial
            $datosNuevos = json_encode([
                'nombre' => $data['nombre'],
                'max_usuarios' => $data['max_usuarios'],
                'estado_suscripcion' => 'activa',
                'fecha_inicio' => $data['fecha_inicio_suscripcion'],
                'fecha_fin' => $data['fecha_fin_suscripcion']
            ], JSON_UNESCAPED_UNICODE);
            
            $this->db->insert('agencias_historial', [
                'agencia_id' => $agenciaId,
                'tipo_evento' => 'creacion',
                'descripcion' => 'Agencia creada por superadmin',
                'usuario_superadmin_id' => $this->userId,
                'datos_nuevos' => $datosNuevos
            ]);
            
            return [
                'success' => true,
                'message' => 'Agencia creada exitosamente',
                'agencia_id' => $agenciaId
            ];
            
        } catch(Exception $e) {
            error_log("Error en createAgencia: " . $e->getMessage());
            throw $e;
        }
    }
    
    // =====================================
    // LISTAR TODAS LAS AGENCIAS
    // =====================================
    private function listAgencias() {
        try {
            $agencias = $this->db->fetchAll(
                "SELECT 
                    a.*,
                    COUNT(DISTINCT u.id) as total_usuarios,
                    DATEDIFF(a.fecha_fin_suscripcion, CURDATE()) as dias_restantes
                FROM agencias a
                LEFT JOIN users u ON u.agencia_id = a.id AND u.active = 1
                GROUP BY a.id
                ORDER BY a.created_at DESC"
            );
            
            return [
                'success' => true,
                'agencias' => $agencias
            ];
            
        } catch(Exception $e) {
            error_log("Error en listAgencias: " . $e->getMessage());
            throw $e;
        }
    }
    
    // =====================================
    // OBTENER DATOS DE UNA AGENCIA
    // =====================================
    private function getAgencia($id) {
        try {
            if (!$id) {
                throw new Exception('ID de agencia requerido');
            }
            
            // Obtener datos de la agencia
            $agencia = $this->db->fetch(
                "SELECT 
                    a.*,
                    COUNT(DISTINCT u.id) as total_usuarios,
                    DATEDIFF(a.fecha_fin_suscripcion, CURDATE()) as dias_restantes
                FROM agencias a
                LEFT JOIN users u ON u.agencia_id = a.id AND u.active = 1
                WHERE a.id = ?
                GROUP BY a.id",
                [$id]
            );
            
            if (!$agencia) {
                throw new Exception('Agencia no encontrada');
            }
            
            return [
                'success' => true,
                'agencia' => $agencia
            ];
            
        } catch(Exception $e) {
            error_log("Error en getAgencia: " . $e->getMessage());
            throw $e;
        }
    }
    
    // =====================================
    // ACTUALIZAR AGENCIA
    // =====================================
    private function updateAgencia($id) {
        try {
            if (!$id) {
                throw new Exception('ID de agencia requerido');
            }
            
            // Obtener datos actuales
            $agenciaAnterior = $this->db->fetch("SELECT * FROM agencias WHERE id = ?", [$id]);
            
            if (!$agenciaAnterior) {
                throw new Exception('Agencia no encontrada');
            }
            
            // Preparar datos para actualizar (solo los campos que vienen en el POST)
            $data = [];
            
            if (isset($_POST['nombre'])) {
                $data['nombre'] = trim($_POST['nombre']);
            }
            if (isset($_POST['descripcion'])) {
                $data['descripcion'] = trim($_POST['descripcion']);
            }
            if (isset($_POST['nit'])) {
                $data['nit'] = trim($_POST['nit']);
            }
            if (isset($_POST['direccion'])) {
                $data['direccion'] = trim($_POST['direccion']);
            }
            if (isset($_POST['telefono'])) {
                $data['telefono'] = trim($_POST['telefono']);
            }
            if (isset($_POST['email_contacto'])) {
                $data['email_contacto'] = trim($_POST['email_contacto']);
            }
            if (isset($_POST['fecha_fin_suscripcion'])) {
                $data['fecha_fin_suscripcion'] = $_POST['fecha_fin_suscripcion'];
            }
            if (isset($_POST['max_usuarios'])) {
                $data['max_usuarios'] = intval($_POST['max_usuarios']);
            }
            if (isset($_POST['estado_suscripcion'])) {
                $data['estado_suscripcion'] = $_POST['estado_suscripcion'];
            }
            
            if (empty($data)) {
                throw new Exception('No hay datos para actualizar');
            }
            
            // Actualizar agencia
            $this->db->update('agencias', $data, 'id = ?', [$id]);
            
            // Registrar en historial
            $datosAnteriores = json_encode($agenciaAnterior, JSON_UNESCAPED_UNICODE);
            $datosNuevos = json_encode(array_merge((array)$agenciaAnterior, $data), JSON_UNESCAPED_UNICODE);
            
            $tipoEvento = 'edicion';
            $descripcion = 'Datos de la agencia actualizados';
            
            // Detectar eventos especiales
            if (isset($data['fecha_fin_suscripcion']) && $data['fecha_fin_suscripcion'] != $agenciaAnterior['fecha_fin_suscripcion']) {
                $tipoEvento = 'renovacion_suscripcion';
                $descripcion = 'Suscripción renovada hasta ' . $data['fecha_fin_suscripcion'];
            }
            if (isset($data['estado_suscripcion'])) {
                if ($data['estado_suscripcion'] == 'suspendida') {
                    $tipoEvento = 'suspension';
                    $descripcion = 'Agencia suspendida';
                } elseif ($data['estado_suscripcion'] == 'activa' && $agenciaAnterior['estado_suscripcion'] != 'activa') {
                    $tipoEvento = 'activacion';
                    $descripcion = 'Agencia reactivada';
                }
            }
            if (isset($data['max_usuarios']) && $data['max_usuarios'] != $agenciaAnterior['max_usuarios']) {
                $tipoEvento = 'cambio_limite_usuarios';
                $descripcion = "Límite de usuarios cambiado de {$agenciaAnterior['max_usuarios']} a {$data['max_usuarios']}";
            }
            
            $this->db->insert('agencias_historial', [
                'agencia_id' => $id,
                'tipo_evento' => $tipoEvento,
                'descripcion' => $descripcion,
                'usuario_superadmin_id' => $this->userId,
                'datos_anteriores' => $datosAnteriores,
                'datos_nuevos' => $datosNuevos
            ]);
            
            return [
                'success' => true,
                'message' => 'Agencia actualizada exitosamente'
            ];
            
        } catch(Exception $e) {
            error_log("Error en updateAgencia: " . $e->getMessage());
            throw $e;
        }
    }
    
    // =====================================
    // OBTENER HISTORIAL DE AGENCIA
    // =====================================
    private function getHistorial($agenciaId) {
        try {
            if (!$agenciaId) {
                throw new Exception('ID de agencia requerido');
            }
            
            $historial = $this->db->fetchAll(
                "SELECT 
                    h.*,
                    u.full_name as superadmin_nombre
                FROM agencias_historial h
                LEFT JOIN users u ON u.id = h.usuario_superadmin_id
                WHERE h.agencia_id = ?
                ORDER BY h.created_at DESC",
                [$agenciaId]
            );
            
            return [
                'success' => true,
                'historial' => $historial
            ];
            
        } catch(Exception $e) {
            error_log("Error en getHistorial: " . $e->getMessage());
            throw $e;
        }
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
    $api = new SuperadminAgenciasAPI();
    $api->handleRequest();
} catch(Exception $e) {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}