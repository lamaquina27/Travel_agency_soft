<?php
// ====================================================================
// ARCHIVO: modules/programa/servicios_api.php - VERSIÓN CORREGIDA
// ====================================================================

ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/app.php';

App::init();
App::requireLogin();

class ProgramaServiciosAPI {
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
            error_log("=== PROGRAMA SERVICIOS API (CORREGIDO) ===");
            error_log("Action: " . $action);
            error_log("GET: " . print_r($_GET, true));
            error_log("POST: " . print_r($_POST, true));
            
            switch($action) {
                case 'add_service':
                    $result = $this->addService(
                        $_POST['dia_id'] ?? null,
                        $_POST['tipo_servicio'] ?? null,
                        $_POST['biblioteca_item_id'] ?? null
                    );
                    break;
                case 'add_alternative':
                    $result = $this->addAlternative(
                        $_POST['servicio_principal_id'] ?? null,
                        $_POST['biblioteca_item_id'] ?? null
                    );
                    break;
                case 'list':
                    $result = $this->listServices($_GET['dia_id'] ?? null);
                    break;
                case 'delete':
                    $result = $this->deleteService($_POST['servicio_id'] ?? $_GET['servicio_id'] ?? null);
                    break;
                case 'update':
                    $result = $this->updateService($_POST['servicio_id'] ?? null, $_POST);
                    break;
                case 'reorder':
                    $result = $this->reorderServices($_POST['dia_id'] ?? null, $_POST['orden'] ?? []);
                    break;
                case 'test':
                    $result = ['success' => true, 'message' => 'API funcionando correctamente'];
                    break;
                default:
                    throw new Exception('Acción no válida: ' . $action);
            }
            
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            
        } catch(Exception $e) {
            error_log("Error en Servicios API: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $this->sendError($e->getMessage());
        }
    }
    
    private function deleteService($servicioId) {
        if (!$servicioId) {
            throw new Exception('ID de servicio requerido');
        }
        
        try {
            $user_id = $_SESSION['user_id'];
            
            error_log("🗑️ Eliminando servicio $servicioId por usuario $user_id");
            
            // Validar que el servicio pertenece a un programa de la agencia del usuario
            $agencia_id = $_SESSION['agencia_id'] ?? null;

            if (!$agencia_id) {
                throw new Exception('Usuario sin agencia asignada');
            }

            // Verificar permisos
            $servicio = $this->db->fetch(
                "SELECT pds.*, ps.user_id 
                FROM programa_dias_servicios pds
                JOIN programa_dias pd ON pds.programa_dia_id = pd.id
                JOIN programa_solicitudes ps ON pd.solicitud_id = ps.id 
                WHERE pds.id = ? AND ps.user_id = ? AND ps.agencia_id = ?", 
                [$servicioId, $user_id, $agencia_id]
            );
            
            if (!$servicio) {
                throw new Exception('Servicio no encontrado o sin permisos');
            }
            
            error_log("✅ Servicio encontrado: " . print_r($servicio, true));
            
            // Si es servicio principal, eliminar también sus alternativas
            if ($servicio['es_alternativa'] == 0) {
                error_log("🗑️ Eliminando servicio PRINCIPAL y sus alternativas");
                
                // ⭐ CORRECCIÓN: Usar query() en lugar de execute()
                $stmt = $this->db->query(
                    "DELETE FROM programa_dias_servicios WHERE servicio_principal_id = ?", 
                    [$servicioId]
                );
                $alternativasEliminadas = $stmt->rowCount();
                error_log("🗑️ Alternativas eliminadas: $alternativasEliminadas");
            }
            
            // Eliminar el servicio principal
            $deleted = $this->db->delete('programa_dias_servicios', 'id = ?', [$servicioId]);
            
            if (!$deleted) {
                throw new Exception('Error al eliminar servicio de la base de datos');
            }
            
            error_log("✅ Servicio eliminado exitosamente");
            
            // Reordenar si era principal (no crítico si falla)
            try {
                if ($servicio['es_alternativa'] == 0) {
                    $this->reorderServicesAfterDelete($servicio['programa_dia_id'], $servicio['orden']);
                }
            } catch(Exception $e) {
                error_log("⚠️ Error reordenando servicios (no crítico): " . $e->getMessage());
            }
            
            return [
                'success' => true,
                'message' => $servicio['es_alternativa'] == 0 ? 
                    'Servicio principal y sus alternativas eliminados' : 
                    'Alternativa eliminada exitosamente',
                'servicio_id' => $servicioId,
                'alternatives_deleted' => $servicio['es_alternativa'] == 0 ? $alternativasEliminadas ?? 0 : 0
            ];
            
        } catch(Exception $e) {
            error_log("❌ Error detallado en deleteService: " . $e->getMessage());
            error_log("❌ Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }
    
    private function addService($diaId, $tipoServicio, $bibliotecaItemId) {
        if (!$diaId || !$tipoServicio || !$bibliotecaItemId) {
            throw new Exception('Día, tipo de servicio e item de biblioteca requeridos');
        }
        
        try {
            $user_id = $_SESSION['user_id'];
            
            error_log("➕ Agregando servicio PRINCIPAL: Día=$diaId, Tipo=$tipoServicio, Item=$bibliotecaItemId");
            
            // Validar que el día pertenece a un programa de la agencia del usuario
            $agencia_id = $_SESSION['agencia_id'] ?? null;

            if (!$agencia_id) {
                throw new Exception('Usuario sin agencia asignada');
            }

            // Verificar que el día pertenece a un programa del usuario
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
            
            // Verificar que el item de biblioteca existe
            $bibliotecaItem = $this->getBibliotecaItem($tipoServicio, $bibliotecaItemId);
            if (!$bibliotecaItem) {
                throw new Exception('Item de biblioteca no encontrado');
            }
            
            // Obtener el siguiente orden para este día
            $lastOrder = $this->db->fetch(
                "SELECT MAX(orden) as max_orden FROM programa_dias_servicios 
                 WHERE programa_dia_id = ? AND es_alternativa = 0", 
                [$diaId]
            );
            
            $nextOrder = ($lastOrder['max_orden'] ?? 0) + 1;
            
            // Insertar servicio PRINCIPAL
            $servicioData = [
                'programa_dia_id' => $diaId,
                'tipo_servicio' => $tipoServicio,
                'biblioteca_item_id' => $bibliotecaItemId,
                'orden' => $nextOrder,
                'servicio_principal_id' => null,
                'es_alternativa' => 0,
                'orden_alternativa' => 0
            ];
            
            error_log("📝 Datos del servicio PRINCIPAL: " . print_r($servicioData, true));
            
            $servicioId = $this->db->insert('programa_dias_servicios', $servicioData);
            
            if (!$servicioId) {
                throw new Exception('Error al insertar servicio');
            }
            
            error_log("✅ Servicio PRINCIPAL agregado: ID $servicioId, Tipo: $tipoServicio");
            
            return [
                'success' => true,
                'servicio_id' => $servicioId,
                'orden' => $nextOrder,
                'es_principal' => true,
                'message' => 'Servicio principal agregado exitosamente'
            ];
            
        } catch(Exception $e) {
            error_log("Error en addService: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function addAlternative($servicioPrincipalId, $bibliotecaItemId) {
        if (!$servicioPrincipalId || !$bibliotecaItemId) {
            throw new Exception('ID de servicio principal e item de biblioteca requeridos');
        }
        
        try {
            $user_id = $_SESSION['user_id'];
            
            error_log("🔄 Agregando ALTERNATIVA: ServicioPrincipal=$servicioPrincipalId, Item=$bibliotecaItemId");
            
            // Validar que el servicio principal pertenece a la agencia del usuario
            $agencia_id = $_SESSION['agencia_id'] ?? null;

            if (!$agencia_id) {
                throw new Exception('Usuario sin agencia asignada');
            }

            $servicioPrincipal = $this->db->fetch(
                "SELECT pds.*, pd.solicitud_id, ps.user_id
                FROM programa_dias_servicios pds
                JOIN programa_dias pd ON pds.programa_dia_id = pd.id
                JOIN programa_solicitudes ps ON pd.solicitud_id = ps.id
                WHERE pds.id = ? AND ps.user_id = ? AND ps.agencia_id = ?",
                [$servicioPrincipalId, $user_id, $agencia_id]
            );
            
            if (!$servicioPrincipal) {
                throw new Exception('Servicio principal no encontrado o sin permisos');
            }
            
            // Verificar que el item de biblioteca existe y es del mismo tipo
            $bibliotecaItem = $this->getBibliotecaItem($servicioPrincipal['tipo_servicio'], $bibliotecaItemId);
            if (!$bibliotecaItem) {
                throw new Exception('Item de biblioteca no encontrado o tipo incompatible');
            }
            
            // Obtener el siguiente orden de alternativa
            $lastAlternativeOrder = $this->db->fetch(
                "SELECT MAX(orden_alternativa) as max_orden FROM programa_dias_servicios 
                 WHERE servicio_principal_id = ?", 
                [$servicioPrincipalId]
            );
            
            $nextAlternativeOrder = ($lastAlternativeOrder['max_orden'] ?? 0) + 1;
            
            // Insertar ALTERNATIVA
            $alternativaData = [
                'programa_dia_id' => $servicioPrincipal['programa_dia_id'],
                'tipo_servicio' => $servicioPrincipal['tipo_servicio'],
                'biblioteca_item_id' => $bibliotecaItemId,
                'orden' => $servicioPrincipal['orden'],
                'servicio_principal_id' => $servicioPrincipalId,
                'es_alternativa' => 1,
                'orden_alternativa' => $nextAlternativeOrder
            ];
            
            error_log("📝 Datos de la ALTERNATIVA: " . print_r($alternativaData, true));
            
            $alternativaId = $this->db->insert('programa_dias_servicios', $alternativaData);
            
            if (!$alternativaId) {
                throw new Exception('Error al insertar alternativa');
            }
            
            error_log("✅ ALTERNATIVA agregada: ID $alternativaId");
            
            return [
                'success' => true,
                'alternativa_id' => $alternativaId,
                'servicio_principal_id' => $servicioPrincipalId,
                'orden_alternativa' => $nextAlternativeOrder,
                'message' => 'Alternativa agregada exitosamente'
            ];
            
        } catch(Exception $e) {
            error_log("Error en addAlternative: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function listServices($diaId) {
        if (!$diaId) {
            throw new Exception('ID de día requerido');
        }
        
        try {
            $user_id = $_SESSION['user_id'];
            
            error_log("📋 Listando servicios CON ALTERNATIVAS para día $diaId del usuario $user_id");
            
            // Validar que el día pertenece a la agencia del usuario
            $agencia_id = $_SESSION['agencia_id'] ?? null;

            if (!$agencia_id) {
                throw new Exception('Usuario sin agencia asignada');
            }

            // Verificar permisos
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
            
            // Obtener TODOS los servicios (principales y alternativas) con datos de biblioteca
            $servicios = $this->db->fetchAll(
                "SELECT 
                    pds.*,
                    CASE 
                        WHEN pds.tipo_servicio = 'actividad' THEN ba.nombre
                        WHEN pds.tipo_servicio = 'transporte' THEN bt.titulo
                        WHEN pds.tipo_servicio = 'alojamiento' THEN bal.nombre
                        ELSE 'Desconocido'
                    END as titulo,
                    CASE 
                        WHEN pds.tipo_servicio = 'actividad' THEN ba.nombre
                        WHEN pds.tipo_servicio = 'transporte' THEN bt.titulo
                        WHEN pds.tipo_servicio = 'alojamiento' THEN bal.nombre
                        ELSE 'Desconocido'
                    END as nombre,
                    CASE 
                        WHEN pds.tipo_servicio = 'actividad' THEN ba.descripcion
                        WHEN pds.tipo_servicio = 'transporte' THEN bt.descripcion
                        WHEN pds.tipo_servicio = 'alojamiento' THEN bal.descripcion
                        ELSE 'Sin descripción'
                    END as descripcion,
                    CASE 
                        WHEN pds.tipo_servicio = 'actividad' THEN ba.ubicacion
                        WHEN pds.tipo_servicio = 'transporte' THEN CONCAT(COALESCE(bt.lugar_salida, ''), ' → ', COALESCE(bt.lugar_llegada, ''))
                        WHEN pds.tipo_servicio = 'alojamiento' THEN bal.ubicacion
                        ELSE 'Sin ubicación'
                    END as ubicacion,
                    CASE 
                        WHEN pds.tipo_servicio = 'transporte' THEN bt.medio
                        ELSE NULL
                    END as medio,
                    CASE 
                        WHEN pds.tipo_servicio = 'transporte' THEN bt.lugar_salida
                        ELSE NULL
                    END as lugar_salida,
                    CASE 
                        WHEN pds.tipo_servicio = 'transporte' THEN bt.lugar_llegada
                        ELSE NULL
                    END as lugar_llegada
                FROM programa_dias_servicios pds
                LEFT JOIN biblioteca_actividades ba ON pds.tipo_servicio = 'actividad' AND pds.biblioteca_item_id = ba.id
                LEFT JOIN biblioteca_transportes bt ON pds.tipo_servicio = 'transporte' AND pds.biblioteca_item_id = bt.id
                LEFT JOIN biblioteca_alojamientos bal ON pds.tipo_servicio = 'alojamiento' AND pds.biblioteca_item_id = bal.id
                WHERE pds.programa_dia_id = ?
                ORDER BY pds.orden ASC, pds.es_alternativa ASC, pds.orden_alternativa ASC", 
                [$diaId]
            );
            
            error_log("📋 Servicios encontrados (con alternativas): " . count($servicios));
            
            // Organizar servicios en estructura jerárquica
            $serviciosOrganizados = $this->organizarServiciosConAlternativas($servicios);
            
            return [
                'success' => true,
                'data' => $serviciosOrganizados,
                'count' => count($servicios),
                'principals_count' => count(array_filter($servicios, fn($s) => $s['es_alternativa'] == 0)),
                'alternatives_count' => count(array_filter($servicios, fn($s) => $s['es_alternativa'] == 1)),
                'dia_id' => $diaId
            ];
            
        } catch(Exception $e) {
            error_log("Error en listServices: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function organizarServiciosConAlternativas($servicios) {
        $organizados = [];
        $principales = [];
        $alternativas = [];
        
        // Separar principales y alternativas
        foreach ($servicios as $servicio) {
            if ($servicio['es_alternativa'] == 0) {
                $principales[$servicio['id']] = $servicio;
                $principales[$servicio['id']]['alternativas'] = [];
            } else {
                $alternativas[] = $servicio;
            }
        }
        
        // Asignar alternativas a sus principales
        foreach ($alternativas as $alternativa) {
            $principalId = $alternativa['servicio_principal_id'];
            if (isset($principales[$principalId])) {
                $principales[$principalId]['alternativas'][] = $alternativa;
            }
        }
        
        // Convertir a array indexado
        return array_values($principales);
    }
    
    private function updateService($servicioId, $data) {
        if (!$servicioId) {
            throw new Exception('ID de servicio requerido');
        }
        
        try {
            $user_id = $_SESSION['user_id'];
            
            // Validar que el servicio pertenece a la agencia del usuario
            $agencia_id = $_SESSION['agencia_id'] ?? null;

            if (!$agencia_id) {
                throw new Exception('Usuario sin agencia asignada');
            }

            // Verificar permisos
            $servicio = $this->db->fetch(
                "SELECT pds.*, ps.user_id 
                FROM programa_dias_servicios pds
                JOIN programa_dias pd ON pds.programa_dia_id = pd.id
                JOIN programa_solicitudes ps ON pd.solicitud_id = ps.id 
                WHERE pds.id = ? AND ps.user_id = ? AND ps.agencia_id = ?", 
                [$servicioId, $user_id, $agencia_id]
            );
            
            if (!$servicio) {
                throw new Exception('Servicio no encontrado o sin permisos');
            }
            
            // Preparar datos para actualizar
            $updateData = [];
            $allowedFields = ['orden', 'notas_alternativa'];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }
            
            if (empty($updateData)) {
                throw new Exception('No hay datos para actualizar');
            }
            
            // Actualizar servicio
            $updated = $this->db->update('programa_dias_servicios', $updateData, 'id = ?', [$servicioId]);
            
            if (!$updated) {
                throw new Exception('Error al actualizar servicio');
            }
            
            return [
                'success' => true,
                'message' => 'Servicio actualizado exitosamente'
            ];
            
        } catch(Exception $e) {
            error_log("Error en updateService: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function reorderServices($diaId, $orden) {
        if (!$diaId || !is_array($orden)) {
            throw new Exception('ID de día y orden requeridos');
        }
        
        try {
            $user_id = $_SESSION['user_id'];
            
            // Validar que el día pertenece a la agencia del usuario
            $agencia_id = $_SESSION['agencia_id'] ?? null;

            if (!$agencia_id) {
                throw new Exception('Usuario sin agencia asignada');
            }

            // Verificar permisos
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
            
            // Solo reordenar servicios principales
            foreach ($orden as $index => $servicioId) {
                $this->db->update(
                    'programa_dias_servicios', 
                    ['orden' => $index + 1], 
                    'id = ? AND programa_dia_id = ? AND es_alternativa = 0', 
                    [$servicioId, $diaId]
                );
            }
            
            return [
                'success' => true,
                'message' => 'Orden actualizado exitosamente'
            ];
            
        } catch(Exception $e) {
            error_log("Error en reorderServices: " . $e->getMessage());
            throw $e;
        }
    }
    
private function getBibliotecaItem($tipoServicio, $itemId) {
    try {
        // Validar que el servicio pertenece a la misma agencia
        $agencia_id = $_SESSION['agencia_id'] ?? null;
        
        if (!$agencia_id) {
            throw new Exception('Usuario sin agencia asignada');
        }
        
        switch($tipoServicio) {
            case 'actividad':
                return $this->db->fetch(
                    "SELECT * FROM biblioteca_actividades WHERE id = ? AND activo = 1 AND agencia_id = ?", 
                    [$itemId, $agencia_id]
                );
            case 'transporte':
                return $this->db->fetch(
                    "SELECT * FROM biblioteca_transportes WHERE id = ? AND activo = 1 AND agencia_id = ?", 
                    [$itemId, $agencia_id]
                );
            case 'alojamiento':
                return $this->db->fetch(
                    "SELECT * FROM biblioteca_alojamientos WHERE id = ? AND activo = 1 AND agencia_id = ?", 
                    [$itemId, $agencia_id]
                );
            default:
                return null;
        }
    } catch(Exception $e) {
        error_log("Error obteniendo item de biblioteca: " . $e->getMessage());
        return null;
    }
}
    
    private function reorderServicesAfterDelete($diaId, $deletedOrder) {
        try {
            // ⭐ CORRECCIÓN: Usar query() en lugar de execute()
            $stmt = $this->db->query(
                "UPDATE programa_dias_servicios 
                 SET orden = orden - 1 
                 WHERE programa_dia_id = ? AND orden > ? AND es_alternativa = 0", 
                [$diaId, $deletedOrder]
            );
            $affected = $stmt->rowCount();
            error_log("✅ Servicios reordenados: $affected");
            
        } catch(Exception $e) {
            error_log("Error reordenando servicios: " . $e->getMessage());
        }
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
$api = new ProgramaServiciosAPI();
$api->handleRequest();