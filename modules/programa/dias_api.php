<?php
// ====================================================================
// ARCHIVO: modules/programa/dias_api.php - FIX CONSTRAINT UNIQUE
// ====================================================================
// SOLUCIÓN: Usar números temporales negativos para evitar duplicados
// ====================================================================

ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/app.php';

App::init();
App::requireLogin();

class ProgramaDiasAPI {
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
        $input = json_decode(file_get_contents('php://input'), true);
        
        if ($input) {
            $_POST = array_merge($_POST, $input);
            $action = $action ?: ($input['action'] ?? '');
        }
        
        try {
            error_log("=== PROGRAMA DÍAS API (FIX UNIQUE) ===");
            error_log("Action: " . $action);
            
            switch($action) {
                case 'list':
                    $result = $this->listDias($_GET['programa_id'] ?? $_POST['programa_id'] ?? null);
                    break;

                case 'add_from_biblioteca':
                    $result = $this->addDiaFromBiblioteca($_POST['programa_id'] ?? null, $_POST['biblioteca_dia_id'] ?? null);
                    break;
                    
                case 'delete':
                    $result = $this->deleteDia($_POST['dia_id'] ?? null);
                    break;
                    
                case 'update':
                    $result = $this->updateDia($_POST['dia_id'] ?? null, $_POST);
                    break;
                    
                case 'reorder':
                    $result = $this->reorderDias(
                        $_POST['solicitud_id'] ?? null, 
                        $_POST['nuevo_orden'] ?? []
                    );
                    break;
                    
                case 'cambiar_estancia':
                    $result = $this->cambiarEstancia($_POST['dia_id'] ?? null, $_POST['duracion'] ?? null);
                    break;

                case 'update_comidas':
                    $result = $this->updateComidas($_POST['dia_id'] ?? null, $_POST);
                    break;
                    
                case 'get_comidas':
                    $result = $this->getComidas($_GET['dia_id'] ?? null);
                    break;
                    
                default:
                    throw new Exception('Acción no válida: ' . $action);
            }
            
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            
        } catch(Exception $e) {
            error_log("❌ Error en Días API: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $this->sendError($e->getMessage());
        }
    }
    
    private function listDias($programaId) {
        if (!$programaId) {
            throw new Exception('ID de programa requerido');
        }
        
        try {
            $user_id = $_SESSION['user_id'];
            $agencia_id = $_SESSION['agencia_id'] ?? null;

            if (!$agencia_id) {
                throw new Exception('Usuario sin agencia asignada');
            }

            $programa = $this->db->fetch(
                "SELECT id FROM programa_solicitudes WHERE id = ? AND user_id = ? AND agencia_id = ?", 
                [$programaId, $user_id, $agencia_id]
            );
            
            if (!$programa) {
                throw new Exception('Programa no encontrado o sin permisos');
            }
            
            $dias = $this->db->fetchAll(
                "SELECT *, COALESCE(duracion_estancia, 1) as duracion_estancia 
                 FROM programa_dias 
                 WHERE solicitud_id = ? 
                 ORDER BY dia_numero ASC", 
                [$programaId]
            );
            
            return [
                'success' => true,
                'data' => $dias
            ];
            
        } catch(Exception $e) {
            error_log("Error en listDias: " . $e->getMessage());
            throw $e;
        }
    }
    
    // ✅ REORDER CON SOLUCIÓN AL CONSTRAINT UNIQUE
    private function reorderDias($programaId, $nuevoOrden) {
        if (!$programaId || !is_array($nuevoOrden) || empty($nuevoOrden)) {
            throw new Exception('ID de programa y nuevo orden requeridos');
        }
        
        try {
            $user_id = $_SESSION['user_id'];
            $agencia_id = $_SESSION['agencia_id'] ?? null;
            
            if (!$agencia_id) {
                throw new Exception('Usuario sin agencia asignada');
            }
            
            error_log("🔄 REORDER (FIX UNIQUE CONSTRAINT)");
            error_log("   Programa: $programaId | Usuario: $user_id | Agencia: $agencia_id");
            error_log("   Nuevo orden: " . json_encode($nuevoOrden));
            
            // Verificar permisos
            $programa = $this->db->fetch(
                "SELECT id FROM programa_solicitudes WHERE id = ? AND user_id = ? AND agencia_id = ?", 
                [$programaId, $user_id, $agencia_id]
            );
            
            if (!$programa) {
                throw new Exception('Programa no encontrado o sin permisos');
            }
            
            // Obtener conexión PDO
            $pdo = $this->db->getConnection();
            $pdo->beginTransaction();
            
            error_log("📦 Transacción iniciada");
            
            try {
                // ⚡ ESTRATEGIA: Usar números NEGATIVOS temporalmente para evitar duplicados
                
                // PASO 1: Cambiar TODOS los días a números negativos (temporales)
                error_log("   PASO 1: Asignando números temporales negativos...");
                $tempStmt = $pdo->prepare("UPDATE programa_dias SET dia_numero = -id WHERE solicitud_id = ?");
                $tempStmt->execute([$programaId]);
                error_log("   ✅ Números temporales asignados");
                
                // PASO 2: Ahora asignar los números finales (ya no hay conflicto)
                error_log("   PASO 2: Asignando números finales...");
                $updateStmt = $pdo->prepare("UPDATE programa_dias SET dia_numero = ? WHERE id = ?");
                
                foreach ($nuevoOrden as $index => $diaId) {
                    $nuevoDiaNumero = $index + 1;
                    
                    error_log("      → Día ID=$diaId → Posición $nuevoDiaNumero");
                    
                    // Verificar que existe
                    $checkStmt = $pdo->prepare("SELECT id FROM programa_dias WHERE id = ? AND solicitud_id = ?");
                    $checkStmt->execute([$diaId, $programaId]);
                    
                    if (!$checkStmt->fetch()) {
                        throw new Exception("Día ID=$diaId no encontrado");
                    }
                    
                    // Asignar número final (sin conflicto porque todos son negativos)
                    $updateStmt->execute([$nuevoDiaNumero, $diaId]);
                    error_log("      ✅ Actualizado");
                }
                
                $pdo->commit();
                error_log("✅ COMMIT exitoso - Reordenamiento completado");
                
                return [
                    'success' => true,
                    'message' => 'Días reordenados correctamente',
                    'nuevo_orden' => $nuevoOrden
                ];
                
            } catch (Exception $e) {
                $pdo->rollback();
                error_log("❌ ROLLBACK: " . $e->getMessage());
                throw $e;
            }
            
        } catch(Exception $e) {
            error_log("❌ Error FATAL: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function addDiaFromBiblioteca($programaId, $bibliotecaDiaId) {
        if (!$programaId || !$bibliotecaDiaId) {
            throw new Exception('ID de programa y día de biblioteca requeridos');
        }
        
        try {
            $user_id = $_SESSION['user_id'];
            $agencia_id = $_SESSION['agencia_id'] ?? null;

            if (!$agencia_id) {
                throw new Exception('Usuario sin agencia asignada');
            }
            
            $programa = $this->db->fetch(
                "SELECT id FROM programa_solicitudes WHERE id = ? AND user_id = ? AND agencia_id = ?", 
                [$programaId, $user_id, $agencia_id]
            );
            
            if (!$programa) {
                throw new Exception('Programa no encontrado o sin permisos');
            }
            
            $diaBiblioteca = $this->db->fetch(
                "SELECT * FROM biblioteca_dias WHERE id = ? AND agencia_id = ? AND activo = 1", 
                [$bibliotecaDiaId, $agencia_id]
            );
            
            if (!$diaBiblioteca) {
                throw new Exception('Día de biblioteca no encontrado');
            }
            
            $ultimoDia = $this->db->fetch(
                "SELECT MAX(dia_numero) as max_dia FROM programa_dias WHERE solicitud_id = ?", 
                [$programaId]
            );
            
            $nuevoDiaNumero = ($ultimoDia['max_dia'] ?? 0) + 1;
            
            $nuevoDiaData = [
                'solicitud_id' => $programaId,
                'dia_numero' => $nuevoDiaNumero,
                'titulo' => $diaBiblioteca['titulo'],
                'descripcion' => $diaBiblioteca['descripcion'],
                'ubicacion' => $diaBiblioteca['ubicacion'],
                'duracion_estancia' => $diaBiblioteca['duracion_estancia'] ?? 1,
                'imagen1' => $diaBiblioteca['imagen1'],
                'imagen2' => $diaBiblioteca['imagen2'],
                'imagen3' => $diaBiblioteca['imagen3']
            ];
            
            $nuevoDiaId = $this->db->insert('programa_dias', $nuevoDiaData);
            
            if (!$nuevoDiaId) {
                throw new Exception('Error al insertar día');
            }
            
            return [
                'success' => true,
                'dia_id' => $nuevoDiaId,
                'message' => 'Día agregado exitosamente'
            ];
            
        } catch(Exception $e) {
            error_log("Error en addDiaFromBiblioteca: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function deleteDia($diaId) {
        if (!$diaId) {
            throw new Exception('ID de día requerido');
        }
        
        try {
            $user_id = $_SESSION['user_id'];
            $agencia_id = $_SESSION['agencia_id'] ?? null;

            if (!$agencia_id) {
                throw new Exception('Usuario sin agencia asignada');
            }
            
            $dia = $this->db->fetch(
                "SELECT pd.*, ps.user_id, ps.id as programa_id 
                 FROM programa_dias pd 
                 JOIN programa_solicitudes ps ON pd.solicitud_id = ps.id 
                 WHERE pd.id = ? AND ps.user_id = ? AND ps.agencia_id = ?", 
                [$diaId, $user_id, $agencia_id]
            );
            
            if (!$dia) {
                throw new Exception('Día no encontrado o sin permisos');
            }
            
            $this->db->query(
                "DELETE FROM programa_dias_servicios WHERE programa_dia_id = ?", 
                [$diaId]
            );
            
            $deleted = $this->db->delete('programa_dias', 'id = ?', [$diaId]);
            
            if (!$deleted) {
                throw new Exception('Error al eliminar día');
            }
            
            $this->reorderDiasAfterDelete($dia['programa_id'], $dia['dia_numero']);
            
            return [
                'success' => true,
                'message' => 'Día eliminado exitosamente'
            ];
            
        } catch(Exception $e) {
            error_log("Error en deleteDia: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function updateDia($diaId, $data) {
        if (!$diaId) {
            throw new Exception('ID de día requerido');
        }
        
        try {
            $user_id = $_SESSION['user_id'];
            $agencia_id = $_SESSION['agencia_id'] ?? null;

            if (!$agencia_id) {
                throw new Exception('Usuario sin agencia asignada');
            }
            
            $dia = $this->db->fetch(
                "SELECT pd.*, ps.user_id 
                 FROM programa_dias pd 
                 JOIN programa_solicitudes ps ON pd.solicitud_id = ps.id 
                 WHERE pd.id = ? AND ps.user_id = ? AND ps.agencia_id = ?", 
                [$diaId, $user_id, $agencia_id]
            );
            
            if (!$dia) {
                throw new Exception('Día no encontrado o sin permisos');
            }
            
            $updateData = [];
            $allowedFields = ['titulo', 'descripcion', 'ubicacion', 'duracion_estancia', 'imagen1', 'imagen2', 'imagen3'];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }
            
            if (empty($updateData)) {
                throw new Exception('No hay datos para actualizar');
            }
            
            $pdo = $this->db->getConnection();
            $setParts = [];
            $values = [];
            
            foreach ($updateData as $key => $value) {
                $setParts[] = "`$key` = ?";
                $values[] = $value;
            }
            $values[] = $diaId;
            
            $sql = "UPDATE programa_dias SET " . implode(', ', $setParts) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);
            
            return [
                'success' => true,
                'message' => 'Día actualizado exitosamente'
            ];
            
        } catch(Exception $e) {
            error_log("Error en updateDia: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function reorderDiasAfterDelete($programaId, $deletedDiaNumber) {
        try {
            $pdo = $this->db->getConnection();
            $stmt = $pdo->prepare("
                UPDATE programa_dias 
                SET dia_numero = dia_numero - 1 
                WHERE solicitud_id = ? AND dia_numero > ?
            ");
            $stmt->execute([$programaId, $deletedDiaNumber]);
            
        } catch(Exception $e) {
            error_log("Error reordenando después de eliminar: " . $e->getMessage());
        }
    }
    
    private function cambiarEstancia($diaId, $nuevaDuracion) {
        try {
            $diaId = (int)$diaId;
            $nuevaDuracion = (int)$nuevaDuracion;
            $user_id = $_SESSION['user_id'];
            $agencia_id = $_SESSION['agencia_id'] ?? null;

            if (!$agencia_id) {
                throw new Exception('Usuario sin agencia asignada');
            }
            
            if ($diaId <= 0 || $nuevaDuracion < 1 || $nuevaDuracion > 30) {
                throw new Exception('Datos no válidos');
            }
            
            $dia = $this->db->fetch(
                "SELECT pd.*, ps.user_id 
                 FROM programa_dias pd 
                 JOIN programa_solicitudes ps ON pd.solicitud_id = ps.id 
                 WHERE pd.id = ? AND ps.user_id = ? AND ps.agencia_id = ?", 
                [$diaId, $user_id, $agencia_id]
            );
            
            if (!$dia) {
                throw new Exception('Día no encontrado o sin permisos');
            }
            
            $pdo = $this->db->getConnection();
            $stmt = $pdo->prepare("UPDATE programa_dias SET duracion_estancia = ? WHERE id = ?");
            $stmt->execute([$nuevaDuracion, $diaId]);
            
            return [
                'success' => true,
                'message' => 'Estancia actualizada correctamente',
                'nueva_duracion' => $nuevaDuracion
            ];
            
        } catch(Exception $e) {
            throw new Exception('Error al cambiar estancia: ' . $e->getMessage());
        }
    }
    
    private function updateComidas($diaId, $data) {
        return [
            'success' => true,
            'message' => 'Funcionalidad pendiente'
        ];
    }
    
    private function getComidas($diaId) {
        return [
            'success' => true,
            'data' => []
        ];
    }
    
    private function sendError($message) {
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $message
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Iniciar API
$api = new ProgramaDiasAPI();
$api->handleRequest();