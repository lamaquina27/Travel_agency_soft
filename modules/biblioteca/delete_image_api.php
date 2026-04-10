<?php
// =====================================
// ARCHIVO: modules/biblioteca/delete_image_api.php
// Endpoint para eliminar imágenes individuales INSTANTÁNEAMENTE
// =====================================

ob_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/app.php';

App::init();
App::requireLogin();

class DeleteImageAPI {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function handleRequest() {
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        
        try {
            // Validar método
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            // Obtener datos del request
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                throw new Exception('Datos inválidos');
            }
            
            $type = $input['type'] ?? '';
            $resourceId = (int)($input['resource_id'] ?? 0);
            $imageField = $input['image_field'] ?? '';
            
            // Validar parámetros
            $allowedTypes = ['dias', 'actividades'];
            if (!in_array($type, $allowedTypes)) {
                throw new Exception('Tipo de recurso no válido');
            }
            
            if ($resourceId <= 0) {
                throw new Exception('ID de recurso inválido');
            }
            
            $allowedFields = ['imagen1', 'imagen2', 'imagen3'];
            if (!in_array($imageField, $allowedFields)) {
                throw new Exception('Campo de imagen inválido');
            }
            
            error_log("=== DELETE IMAGE REQUEST ===");
            error_log("Type: $type, ID: $resourceId, Field: $imageField");
            
            // Verificar permisos
            $agencia_id = $_SESSION['agencia_id'] ?? null;
            if (!$agencia_id) {
                throw new Exception('Usuario sin agencia asignada');
            }
            
            $table = "biblioteca_" . $type;
            
            // Obtener recurso actual
            $resource = $this->db->fetch(
                "SELECT $imageField, agencia_id FROM `{$table}` WHERE id = ?",
                [$resourceId]
            );
            
            if (!$resource) {
                throw new Exception('Recurso no encontrado');
            }
            
            // Verificar que pertenece a la agencia
            if ($resource['agencia_id'] != $agencia_id) {
                throw new Exception('Sin permisos para modificar este recurso');
            }
            
            $imageUrl = $resource[$imageField];
            
            if (empty($imageUrl)) {
                throw new Exception('No hay imagen para eliminar en este campo');
            }
            
            // Eliminar archivo físico del servidor
            $this->deletePhysicalFile($imageUrl);
            
            // Actualizar base de datos (poner NULL en el campo)
            $updated = $this->db->update(
                $table,
                [$imageField => NULL],
                'id = ?',
                [$resourceId]
            );
            
            if (!$updated) {
                throw new Exception('Error al actualizar la base de datos');
            }
            
            error_log("✅ Imagen eliminada exitosamente: $imageField");
            
            echo json_encode([
                'success' => true,
                'message' => 'Imagen eliminada correctamente',
                'field' => $imageField
            ]);
            
        } catch(Exception $e) {
            error_log("❌ Error eliminando imagen: " . $e->getMessage());
            
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        
        exit;
    }
    
    private function deletePhysicalFile($imageUrl) {
        try {
            // Extraer ruta relativa desde la URL
            // Ejemplo: http://localhost/travel_agency/assets/uploads/... -> /assets/uploads/...
            $parsedUrl = parse_url($imageUrl);
            $relativePath = $parsedUrl['path'] ?? '';
            
            // Remover el prefijo de la aplicación si existe
            $appPath = parse_url(APP_URL, PHP_URL_PATH);
            if ($appPath && strpos($relativePath, $appPath) === 0) {
                $relativePath = substr($relativePath, strlen($appPath));
            }
            
            // Construir ruta física completa
            $physicalPath = BASE_PATH . $relativePath;
            
            error_log("Intentando eliminar archivo físico: $physicalPath");
            
            // Verificar que el archivo existe y eliminarlo
            if (file_exists($physicalPath)) {
                if (unlink($physicalPath)) {
                    error_log("✅ Archivo físico eliminado: $physicalPath");
                } else {
                    error_log("⚠️ No se pudo eliminar el archivo: $physicalPath");
                }
            } else {
                error_log("⚠️ Archivo no encontrado en servidor: $physicalPath");
            }
            
        } catch(Exception $e) {
            error_log("⚠️ Error eliminando archivo físico: " . $e->getMessage());
            // No lanzar excepción, solo log el error
            // La actualización en BD es lo más importante
        }
    }
}

// Inicializar y procesar
try {
    $api = new DeleteImageAPI();
    $api->handleRequest();
} catch(Exception $e) {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
    exit;
}