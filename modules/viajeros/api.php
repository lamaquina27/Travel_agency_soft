<?php
// ====================================================================
// ARCHIVO: modules/viajeros/api.php
// API encargada de la gestión de Viajeros (Crear, Listar, Editar, Borrar)
// ====================================================================

ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/app.php';

App::init();
App::requireLogin();

class ViajerosAPI {
    private $db;
    
    public function __construct() {
        try {
            $this->db = Database::getInstance();
        } catch(Exception $e) {
            $this->sendError('Error de conexión a la base de datos: ' . $e->getMessage());
        }
    }
    
    public function handleRequest() {
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        
        $action = $_POST['action'] ?? $_GET['action'] ?? '';
        
        try {
            switch($action) {
                case 'create':
                    $result = $this->createViajero();
                    break;
                case 'list':
                    $result = $this->listViajeros();
                    break;
                case 'find_by_document':
                    $result = $this->findByDocument();
                    break;
                case 'update':
                    $result = $this->updateViajero();
                    break;
                case 'delete':
                    $result = $this->deleteViajero();
                    break;
                default:
                    throw new Exception("Acción no válida: {$action}");
            }
            
            echo json_encode($result);
            
        } catch(Exception $e) {
            $this->sendError($e->getMessage());
        }
    }
    
    // ====================================================
    // MÉTODOS CRUD (Lógica de Negocio)
    // ====================================================
    
        private function createViajero() {
        $agencia_id = $_SESSION['agencia_id'] ?? null;
        if (!$agencia_id) throw new Exception('Sesión inválida. Agencia no encontrada.');

        // 1. Agregamos fecha_nacimiento a la regla de obligatorios
        if (empty($_POST['nombre']) || empty($_POST['apellido']) || empty($_POST['numero_documento']) || empty($_POST['fecha_nacimiento'])) {
            throw new Exception('El nombre, apellido, documento y fecha de nacimiento son obligatorios.');
        }

        //Agrego esto para funcionamiento de busqueda y predicción
        $tipo_documento = intval($_POST['tipo_documento'] ?? 1);
        $numero_documento = trim($_POST['numero_documento']);

        $existente = $this->db->fetch(
            "SELECT *
            FROM viajeros
            WHERE agencia_id = ?
            AND tipo_documento = ?
            AND numero_documento = ?
            LIMIT 1",
            [$agencia_id, $tipo_documento, $numero_documento]
        );

        if ($existente) {
            return [
                'success' => true,
                'message' => 'El viajero ya existía en esta agencia',
                'already_exists' => true,
                'data' => $existente
            ];
        }

        $data = [
            'agencia_id' => $agencia_id,
            'nombre' => trim($_POST['nombre']),
            'apellido' => trim($_POST['apellido']),
            'tipo_documento' => $tipo_documento,
            'numero_documento' => $numero_documento,
            'mail' => trim($_POST['mail'] ?? ''),
            'telefono' => trim($_POST['telefono'] ?? ''),
            'pais_nacimiento' => trim($_POST['pais_nacimiento'] ?? ''),
            // 2. Quitamos el if (ternario), ahora sabemos 100% que existe
            'fecha_nacimiento' => $_POST['fecha_nacimiento'] 
        ];

        $viajero_id = $this->db->insert('viajeros', $data);

        return [
            'success' => true,
            'message' => 'Viajero creado correctamente',
            'data' => array_merge(['id' => $viajero_id], $data)
        ];
    }

    
    private function listViajeros() {
        $agencia_id = $_SESSION['agencia_id'] ?? null;
        if (!$agencia_id) throw new Exception('Sesión inválida.');

        // Se usa ORDER BY para mostrar los más recientes primero
        $viajeros = $this->db->fetchAll(
            "SELECT * FROM viajeros WHERE agencia_id = ? ORDER BY created_at DESC", 
            [$agencia_id]
        );

        return [
            'success' => true,
            'data' => $viajeros
        ];
    }

    private function findByDocument() {
        $agencia_id = $_SESSION['agencia_id'] ?? null;
        if (!$agencia_id) {
            throw new Exception('Sesión inválida.');
        }

        $tipo_documento = intval($_POST['tipo_documento'] ?? $_GET['tipo_documento'] ?? 0);
        $numero_documento = trim($_POST['numero_documento'] ?? $_GET['numero_documento'] ?? '');

        if (!$tipo_documento || empty($numero_documento)) {
            throw new Exception('Tipo y número de documento son obligatorios para buscar.');
        }

        $viajero = $this->db->fetch(
            "SELECT *
            FROM viajeros
            WHERE agencia_id = ?
            AND tipo_documento = ?
            AND numero_documento = ?
            LIMIT 1",
            [$agencia_id, $tipo_documento, $numero_documento]
        );

        return [
            'success' => true,
            'found' => !empty($viajero),
            'data' => $viajero ?: null
        ];
    }

    private function updateViajero() {
        $agencia_id = $_SESSION['agencia_id'] ?? null;
        if (!$agencia_id) throw new Exception('Sesión inválida.');

        $viajero_id = intval($_POST['id'] ?? 0);
        if (!$viajero_id) throw new Exception('ID del viajero no proporcionado.');

        // 1. Validar campos obligatorios (igual que al crear)
        if (empty($_POST['nombre']) || empty($_POST['apellido']) || empty($_POST['numero_documento']) || empty($_POST['fecha_nacimiento'])) {
            throw new Exception('El nombre, apellido, documento y fecha de nacimiento son obligatorios.');
        }

        $data = [
            'nombre' => trim($_POST['nombre']),
            'apellido' => trim($_POST['apellido']),
            'tipo_documento' => intval($_POST['tipo_documento'] ?? 1),
            'numero_documento' => trim($_POST['numero_documento']),
            'mail' => trim($_POST['mail'] ?? ''),
            'telefono' => trim($_POST['telefono'] ?? ''),
            'pais_nacimiento' => trim($_POST['pais_nacimiento'] ?? ''),
            'fecha_nacimiento' => $_POST['fecha_nacimiento']
        ];

        // 2. Seguridad: Actualizamos SÓLO si el ID pertenece a esta agencia
        $updated = $this->db->update('viajeros', $data, "id = ? AND agencia_id = ?", [$viajero_id, $agencia_id]);

        if (!$updated) {
            throw new Exception('No se pudo actualizar el viajero. Puede que no exista o no tengas permisos.');
        }

        return [
            'success' => true,
            'message' => 'Viajero actualizado correctamente'
        ];
    }

    private function deleteViajero() {
        $agencia_id = $_SESSION['agencia_id'] ?? null;
        if (!$agencia_id) throw new Exception('Sesión inválida.');

        $viajero_id = intval($_POST['id'] ?? 0);
        if (!$viajero_id) throw new Exception('ID del viajero no proporcionado.');

        // 1. Seguridad: Borramos SÓLO si pertenece a esta agencia
        $deleted = $this->db->delete('viajeros', "id = ? AND agencia_id = ?", [$viajero_id, $agencia_id]);

        if (!$deleted) {
            throw new Exception('No se pudo eliminar el viajero. Asegúrate de que existe.');
        }

        return [
            'success' => true,
            'message' => 'Viajero eliminado del sistema'
        ];
    }

    
    // ====================================================
    // MÉTODOS DE AYUDA
    // ====================================================
    
    private function sendError($message) {
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error' => $message
        ]);
        exit;
    }
}

// Punto de entrada de la API
$api = new ViajerosAPI();
$api->handleRequest();
