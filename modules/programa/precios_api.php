<?php
// ====================================================================
// ARCHIVO: modules/programa/precios_api.php - API PARA GESTIÓN DE PRECIOS
// ====================================================================

ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/app.php';

App::init();
App::requireLogin();

class ProgramaPreciosAPI {
    private $db;
    
    public function __construct() {
        try {
            $this->db = Database::getInstance();
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
            error_log("=== PROGRAMA PRECIOS API ===");
            error_log("Action: " . $action);
            error_log("POST: " . print_r($_POST, true));
            
            switch($action) {
                case 'save':
                    $result = $this->savePrecios();
                    break;
                case 'get':
                    $result = $this->getPrecios($_GET['programa_id'] ?? null);
                    break;
                case 'delete':
                    $result = $this->deletePrecios($_POST['programa_id'] ?? null);
                    break;
                default:
                    throw new Exception('Acción no válida: ' . $action);
            }
            
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            
        } catch(Exception $e) {
            error_log("Error en Precios API: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $this->sendError($e->getMessage());
        }
    }
    
    private function savePrecios() {
        try {
            $user_id = $_SESSION['user_id'];
            $programa_id = $_POST['programa_id'] ?? null;
            
            if (!$programa_id) {
                throw new Exception('ID de programa requerido');
            }
            
            // Validar que el programa pertenece a la agencia del usuario
            $agencia_id = $_SESSION['agencia_id'] ?? null;

            if (!$agencia_id) {
                throw new Exception('Usuario sin agencia asignada');
            }

            // Verificar que el programa pertenece al usuario
            $programa = $this->db->fetch(
                "SELECT id FROM programa_solicitudes WHERE id = ? AND user_id = ? AND agencia_id = ?", 
                [$programa_id, $user_id, $agencia_id]
            );
            
            if (!$programa) {
                throw new Exception('Programa no encontrado o sin permisos');
            }
            
            // Preparar datos de precios - NUEVOS CAMPOS
            $preciosData = [
                'solicitud_id' => $programa_id,
                'moneda' => trim($_POST['moneda'] ?? 'USD'),
                'precio_adulto' => $this->parseDecimal($_POST['precio_adulto'] ?? null),
                'precio_nino' => $this->parseDecimal($_POST['precio_nino'] ?? null),
                'cantidad_adultos' => intval($_POST['cantidad_adultos'] ?? 1),
                'cantidad_ninos' => intval($_POST['cantidad_ninos'] ?? 0),
                'precio_total' => $this->parseDecimal($_POST['precio_total'] ?? null),
                'noches_incluidas' => intval($_POST['noches_incluidas'] ?? 0),
                'precio_incluye' => trim($_POST['precio_incluye'] ?? ''),
                'precio_no_incluye' => trim($_POST['precio_no_incluye'] ?? ''),
                'condiciones_generales' => trim($_POST['condiciones_generales'] ?? ''),
                'movilidad_reducida' => isset($_POST['movilidad_reducida']) ? 1 : 0,
                'mostrar_precio' => (isset($_POST['mostrar_precio']) && $_POST['mostrar_precio'] == '1') ? 1 : 0,
                'info_pasaporte' => trim($_POST['info_pasaporte'] ?? ''),
                'info_seguros' => trim($_POST['info_seguros'] ?? ''),
                'visados_entrada' => trim($_POST['visados_entrada'] ?? ''),
                'requisitos_sanitarios' => trim($_POST['requisitos_sanitarios'] ?? ''),
                'llegada_punto_encuentro' => trim($_POST['llegada_punto_encuentro'] ?? ''),
                'asistencia_emergencia' => trim($_POST['asistencia_emergencia'] ?? ''),
                'info_hoteles_servicios' => trim($_POST['info_hoteles_servicios'] ?? ''),
                'informacion_practica' => trim($_POST['informacion_practica'] ?? '')
            ];
            
            error_log("📝 Datos de precios a guardar: " . print_r($preciosData, true));
            
            // Verificar si ya existen precios para este programa
            $existingPrecios = $this->db->fetch(
                "SELECT id FROM programa_precios WHERE solicitud_id = ?", 
                [$programa_id]
            );
            
            if ($existingPrecios) {
                // Actualizar precios existentes
                $updated = $this->db->update(
                    'programa_precios', 
                    $preciosData, 
                    'solicitud_id = ?', 
                    [$programa_id]
                );
                
                if ($updated === false) {
                     throw new Exception('Error al actualizar precios');
                    }
                
                error_log("✅ Precios actualizados para programa $programa_id");
                
            } else {
                // Insertar nuevos precios
                $precioId = $this->db->insert('programa_precios', $preciosData);
                
                if (!$precioId) {
                    throw new Exception('Error al insertar precios');
                }
                
                error_log("✅ Precios creados para programa $programa_id con ID $precioId");
            }

            // ACTUALIZAR número de pasajeros en programa_solicitudes
            $total_pasajeros = $preciosData['cantidad_adultos'] + $preciosData['cantidad_ninos'];
            $this->db->update(
                'programa_solicitudes',
                ['numero_pasajeros' => $total_pasajeros],
                'id = ?',
                [$programa_id]
            );

            error_log("✅ Número de pasajeros actualizado a: $total_pasajeros");
            
            return [
                'success' => true,
                'message' => 'Precios guardados exitosamente'
            ];
            
        } catch(Exception $e) {
            error_log("Error en savePrecios: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function getPrecios($programaId) {
        if (!$programaId) {
            throw new Exception('ID de programa requerido');
        }
        
        try {
            $user_id = $_SESSION['user_id'];
            
            // Validar que el programa pertenece a la agencia del usuario
            $agencia_id = $_SESSION['agencia_id'] ?? null;

            if (!$agencia_id) {
                throw new Exception('Usuario sin agencia asignada');
            }

            // Verificar permisos
            $programa = $this->db->fetch(
                "SELECT id FROM programa_solicitudes WHERE id = ? AND user_id = ? AND agencia_id = ?", 
                [$programaId, $user_id, $agencia_id]
            );
            
            if (!$programa) {
                throw new Exception('Programa no encontrado o sin permisos');
            }
            
            // Obtener precios del programa
            $precios = $this->db->fetch(
                "SELECT * FROM programa_precios WHERE solicitud_id = ?", 
                [$programaId]
            );

            // Si NO existen precios guardados, cargar plantilla de la agencia
            if (!$precios) {
                $plantilla = $this->db->fetch(
                    "SELECT precio_incluye, precio_no_incluye, condiciones_generales, info_pasaporte, info_seguros,
                            visados_entrada, requisitos_sanitarios, llegada_punto_encuentro,
                            asistencia_emergencia, info_hoteles_servicios, informacion_practica
                    FROM biblioteca_plantillas_precios
                    WHERE agencia_id = ?",
                    [$agencia_id]
                );

                if ($plantilla) {
                    // Crear estructura de precios vacía pero con plantilla pre-cargada
                    $precios = [
                        'precio_incluye' => $plantilla['precio_incluye'],
                        'precio_no_incluye' => $plantilla['precio_no_incluye'],
                        'condiciones_generales' => $plantilla['condiciones_generales'],
                        'info_pasaporte' => $plantilla['info_pasaporte'],
                        'info_seguros' => $plantilla['info_seguros'],
                        'visados_entrada' => $plantilla['visados_entrada'],
                        'requisitos_sanitarios' => $plantilla['requisitos_sanitarios'],
                        'llegada_punto_encuentro' => $plantilla['llegada_punto_encuentro'],
                        'asistencia_emergencia' => $plantilla['asistencia_emergencia'],
                        'info_hoteles_servicios' => $plantilla['info_hoteles_servicios'],
                        'informacion_practica' => $plantilla['informacion_practica'],
                        'mostrar_precio' => 0
                    ];
                    
                    error_log("✅ Plantilla de precios cargada para programa $programaId");
                }
            }

            return [
                'success' => true,
                'data' => $precios
            ];
            
        } catch(Exception $e) {
            error_log("Error en getPrecios: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function deletePrecios($programaId) {
        if (!$programaId) {
            throw new Exception('ID de programa requerido');
        }
        
        try {
            $user_id = $_SESSION['user_id'];
            
            // Validar que el programa pertenece a la agencia del usuario
            $agencia_id = $_SESSION['agencia_id'] ?? null;

            if (!$agencia_id) {
                throw new Exception('Usuario sin agencia asignada');
            }

            // Verificar permisos
            $programa = $this->db->fetch(
                "SELECT id FROM programa_solicitudes WHERE id = ? AND user_id = ? AND agencia_id = ?", 
                [$programaId, $user_id, $agencia_id]
            );
            
            if (!$programa) {
                throw new Exception('Programa no encontrado o sin permisos');
            }
            
            // Eliminar precios
            $deleted = $this->db->delete('programa_precios', 'solicitud_id = ?', [$programaId]);
            
            if (!$deleted) {
                throw new Exception('Error al eliminar precios');
            }
            
            return [
                'success' => true,
                'message' => 'Precios eliminados exitosamente'
            ];
            
        } catch(Exception $e) {
            error_log("Error en deletePrecios: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function parseDecimal($value) {
        if ($value === null || $value === '') {
            return null;
        }
        
        // Limpiar y convertir a decimal
        $cleaned = preg_replace('/[^0-9.,]/', '', $value);
        $cleaned = str_replace(',', '.', $cleaned);
        
        return is_numeric($cleaned) ? floatval($cleaned) : null;
    }
    
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

// Instanciar y ejecutar API
$api = new ProgramaPreciosAPI();
$api->handleRequest();