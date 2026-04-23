<?php
// ====================================================================
// ARCHIVO: modules/programa/upload_images.php
// PROPÓSITO: Upload de imágenes para edición de días y actividades
// ====================================================================

ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/app.php';

App::init();
App::requireLogin();

class ProgramaImageUploader {
    private $db;
    private $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    private $maxFileSize = 10 * 1024 * 1024; // 10MB
    
    public function __construct() {
        try {
            $this->db = Database::getInstance();
        } catch(Exception $e) {
            $this->sendError('Error de conexión: ' . $e->getMessage());
        }
    }
    
    public function handleRequest() {
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $type = $_POST['type'] ?? ''; // 'dia' o 'actividad'
            $itemId = $_POST['item_id'] ?? null;
            
            if (!in_array($type, ['dia', 'actividad'])) {
                throw new Exception('Tipo no válido');
            }
            
            if (!$itemId) {
                throw new Exception('ID requerido');
            }
            
            // Verificar permisos
            $this->verifyPermissions($type, $itemId);
            
            // Procesar upload
            $result = $this->uploadImages($type, $itemId);
            
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            
        } catch(Exception $e) {
            error_log("Error en upload: " . $e->getMessage());
            $this->sendError($e->getMessage());
        }
    }
    
    private function verifyPermissions($type, $itemId) {
        $user_id = $_SESSION['user_id'];
        $agencia_id = $_SESSION['agencia_id'] ?? null;
        
        if (!$agencia_id) {
            throw new Exception('Usuario sin agencia');
        }
        
        if ($type === 'dia') {
            $item = $this->db->fetch(
                "SELECT pd.* FROM programa_dias pd
                 JOIN programa_solicitudes ps ON pd.solicitud_id = ps.id
                 WHERE pd.id = ? AND ps.user_id = ? AND ps.agencia_id = ?",
                [$itemId, $user_id, $agencia_id]
            );
        } else {
            $item = $this->db->fetch(
                "SELECT pds.* FROM programa_dias_servicios pds
                 JOIN programa_dias pd ON pds.programa_dia_id = pd.id
                 JOIN programa_solicitudes ps ON pd.solicitud_id = ps.id
                 WHERE pds.id = ? AND ps.user_id = ? AND ps.agencia_id = ?",
                [$itemId, $user_id, $agencia_id]
            );
        }
        
        if (!$item) {
            throw new Exception('Item no encontrado o sin permisos');
        }
    }
    
    private function uploadImages($type, $itemId) {
        $uploadedImages = [];
        $agencia_id = $_SESSION['agencia_id'];
        
        // Procesar hasta 3 imágenes
        for ($i = 1; $i <= 3; $i++) {
            $fileKey = 'imagen' . $i;
            
            if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES[$fileKey];
                
                // Validar tipo
                if (!in_array($file['type'], $this->allowedTypes)) {
                    throw new Exception("Tipo de archivo no permitido para imagen $i");
                }
                
                // Validar tamaño
                if ($file['size'] > $this->maxFileSize) {
                    throw new Exception("Imagen $i excede el tamaño máximo (10MB)");
                }
                
                // Generar ruta de destino
                $uploadPath = $this->generateUploadPath($type, $agencia_id, $itemId, $i);
                
                // Mover archivo
                if (!move_uploaded_file($file['tmp_name'], $uploadPath['full_path'])) {
                    throw new Exception("Error moviendo imagen $i");
                }
                
                $uploadedImages[$fileKey] = $uploadPath['url'];
                error_log("✅ Imagen $i subida: " . $uploadPath['url']);
            }
        }
        
        return [
            'success' => true,
            'images' => $uploadedImages,
            'message' => count($uploadedImages) . ' imagen(es) subida(s)'
        ];
    }
    
    private function generateUploadPath($type, $agencia_id, $itemId, $imageNumber) {
        $year = date('Y');
        $month = date('m');
        
        // Directorio base
        $baseDir = $_SERVER['DOCUMENT_ROOT'] . '/travel_agency/assets/uploads/agencia_' . $agencia_id . '/programa';
        $yearDir = $baseDir . '/' . $year;
        $monthDir = $yearDir . '/' . $month;
        
        // Crear directorios si no existen
        if (!is_dir($baseDir)) mkdir($baseDir, 0755, true);
        if (!is_dir($yearDir)) mkdir($yearDir, 0755, true);
        if (!is_dir($monthDir)) mkdir($monthDir, 0755, true);
        
        // Nombre de archivo
        $extension = pathinfo($_FILES['imagen' . $imageNumber]['name'], PATHINFO_EXTENSION);
        $filename = $type . '_' . $itemId . '_imagen' . $imageNumber . '_' . time() . '.' . $extension;
        
        $fullPath = $monthDir . '/' . $filename;
        $url = APP_URL . '/assets/uploads/agencia_' . $agencia_id . '/programa/' . $year . '/' . $month . '/' . $filename;
        
        return [
            'full_path' => $fullPath,
            'url' => $url
        ];
    }
    
    private function sendError($message) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $message
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Ejecutar
$uploader = new ProgramaImageUploader();
$uploader->handleRequest();