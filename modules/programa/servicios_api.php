<?php
// ====================================================================
// ARCHIVO: modules/programa/servicios_api.php - VERSIÓN CON AISLAMIENTO TOTAL
// ====================================================================
// 
// IMPORTANTE: Este archivo REEMPLAZA completamente el archivo original
// modules/programa/servicios_api.php
//
// CAMBIO PRINCIPAL: Ahora COPIA todos los datos de biblioteca en lugar
// de solo guardar la referencia (biblioteca_item_id)
//
// ====================================================================

ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once __DIR__ . '/upload_images.php';

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
            error_log("=== PROGRAMA SERVICIOS API (AISLADO) ===");
            error_log("Action: " . $action);
            
            switch($action) {
                case 'add_service':
                    $result = $this->addService(
                        $_POST['dia_id'] ?? null,
                        $_POST['tipo_servicio'] ?? null,
                        $_POST['biblioteca_item_id'] ?? null,
                        $_POST['acomodacion_id'] ?? null
                    );
                    break;
                case 'add_alternative':
                    $result = $this->addAlternative(
                        $_POST['servicio_principal_id'] ?? null,
                        $_POST['biblioteca_item_id'] ?? null,
                        $_POST['variacion_precio'] ?? null
                    );
                    break;
                case 'list':
                    $result = $this->listServices($_GET['dia_id'] ?? null);
                    break;
                case 'delete':
                    $result = $this->deleteService($_POST['servicio_id'] ?? $_GET['servicio_id'] ?? null);
                    break;
                case 'update':
                    $input = json_decode(file_get_contents('php://input'), true);
                    $servicioId = $input['servicio_id'] ?? $_POST['servicio_id'] ?? null;
                    $data = $input ?: $_POST;
                    $result = $this->updateService($servicioId, $data);
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
    
    // ================================================================
    // FUNCIÓN PRINCIPAL: AGREGAR SERVICIO CON AISLAMIENTO TOTAL
    // ================================================================
    private function addService($diaId, $tipoServicio, $bibliotecaItemId, $acomodacionId = null) {
        if (!$diaId || !$tipoServicio || !$bibliotecaItemId) {
            throw new Exception('Día, tipo de servicio e item de biblioteca requeridos');
        }
        
        try {
            $user_id = $_SESSION['user_id'];
            $agencia_id = $_SESSION['agencia_id'] ?? null;

            if (!$agencia_id) {
                throw new Exception('Usuario sin agencia asignada');
            }
            
            error_log("➕ Agregando servicio AISLADO: Día=$diaId, Tipo=$tipoServicio, Item=$bibliotecaItemId");
            
            // Verificar permisos del día
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
            
            // ⭐ OBTENER DATOS COMPLETOS DEL SERVICIO DE BIBLIOTECA
            $bibliotecaItem = $this->getBibliotecaItemCompleto($tipoServicio, $bibliotecaItemId, $agencia_id);
            if (!$bibliotecaItem) {
                throw new Exception('Item de biblioteca no encontrado');
            }

            $acomodacionId = !empty($acomodacionId) ? (int)$acomodacionId : null;

            if ($tipoServicio === 'alojamiento' && $acomodacionId) {
                $acomodacion = $this->db->fetch(
                    "SELECT a.id
                    FROM acomodaciones a
                    INNER JOIN biblioteca_alojamientos h ON a.hotel_id = h.id
                    WHERE a.id = ?
                    AND h.id = ?
                    AND h.agencia_id = ?
                    LIMIT 1",
                    [$acomodacionId, $bibliotecaItemId, $agencia_id]
                );

                if (!$acomodacion) {
                    throw new Exception('La acomodación no pertenece al alojamiento seleccionado');
                }
            }
            
            // Obtener el siguiente orden
            $lastOrder = $this->db->fetch(
                "SELECT MAX(orden) as max_orden FROM programa_dias_servicios 
                 WHERE programa_dia_id = ? AND es_alternativa = 0", 
                [$diaId]
            );
            
            $nextOrder = ($lastOrder['max_orden'] ?? 0) + 1;
            
            // ⭐ CREAR ARRAY DE DATOS COPIADOS SEGÚN EL TIPO DE SERVICIO
            $servicioData = $this->prepararDatosServicio($diaId, $tipoServicio, $bibliotecaItem, $nextOrder);
            
            // ⭐ MANTENER biblioteca_item_id SOLO COMO REFERENCIA HISTÓRICA
            $servicioData['biblioteca_item_id'] = $bibliotecaItemId;
            $servicioData['acomodacion_id'] = ($tipoServicio === 'alojamiento') ? $acomodacionId : null;
            
            error_log("📝 Datos del servicio AISLADO a insertar: " . json_encode($servicioData, JSON_PRETTY_PRINT));
            
            $servicioId = $this->db->insert('programa_dias_servicios', $servicioData);
            
            if (!$servicioId) {
                throw new Exception('Error al insertar servicio');
            }
            
            error_log("✅ Servicio AISLADO agregado exitosamente: ID $servicioId");
            
            return [
                'success' => true,
                'servicio_id' => $servicioId,
                'orden' => $nextOrder,
                'es_principal' => true,
                'message' => 'Servicio agregado exitosamente (datos copiados)'
            ];
            
        } catch(Exception $e) {
            error_log("Error en addService: " . $e->getMessage());
            throw $e;
        }
    }
    
    // ================================================================
    // FUNCIÓN: PREPARAR DATOS DEL SERVICIO SEGÚN TIPO
    // ================================================================
    private function prepararDatosServicio($diaId, $tipoServicio, $bibliotecaItem, $orden) {
        // Datos base comunes
        $servicioData = [
            'programa_dia_id' => $diaId,
            'tipo_servicio' => $tipoServicio,
            'orden' => $orden,
            'servicio_principal_id' => null,
            'es_alternativa' => 0,
            'orden_alternativa' => 0
        ];
        
        // ⭐ COPIAR DATOS ESPECÍFICOS SEGÚN EL TIPO
        switch($tipoServicio) {
            case 'actividad':
                $servicioData = array_merge($servicioData, [
                    // Campos comunes
                    'nombre_servicio' => $bibliotecaItem['nombre'] ?? null,
                    'descripcion_servicio' => $bibliotecaItem['descripcion'] ?? null,
                    'ubicacion_servicio' => $bibliotecaItem['ubicacion'] ?? null,
                    'latitud' => $bibliotecaItem['latitud'] ?? null,
                    'longitud' => $bibliotecaItem['longitud'] ?? null,
                    // Campos específicos de actividad
                    'actividad_imagen1' => $bibliotecaItem['imagen1'] ?? null,
                    'actividad_imagen2' => $bibliotecaItem['imagen2'] ?? null,
                    'actividad_imagen3' => $bibliotecaItem['imagen3'] ?? null,
                    'actividad_idioma' => $bibliotecaItem['idioma'] ?? null
                ]);
                break;
                
            case 'transporte':
                $servicioData = array_merge($servicioData, [
                    // Campos comunes
                    'nombre_servicio' => $bibliotecaItem['titulo'] ?? null,
                    'descripcion_servicio' => $bibliotecaItem['descripcion'] ?? null,
                    'ubicacion_servicio' => ($bibliotecaItem['lugar_salida'] ?? '') . ' → ' . ($bibliotecaItem['lugar_llegada'] ?? ''),
                    'latitud' => $bibliotecaItem['lat_salida'] ?? null,
                    'longitud' => $bibliotecaItem['lng_salida'] ?? null,
                    // Campos específicos de transporte
                    'transporte_medio' => $bibliotecaItem['medio'] ?? null,
                    'transporte_titulo' => $bibliotecaItem['titulo'] ?? null,
                    'transporte_lugar_salida' => $bibliotecaItem['lugar_salida'] ?? null,
                    'transporte_lugar_llegada' => $bibliotecaItem['lugar_llegada'] ?? null,
                    'transporte_lat_salida' => $bibliotecaItem['lat_salida'] ?? null,
                    'transporte_lng_salida' => $bibliotecaItem['lng_salida'] ?? null,
                    'transporte_lat_llegada' => $bibliotecaItem['lat_llegada'] ?? null,
                    'transporte_lng_llegada' => $bibliotecaItem['lng_llegada'] ?? null,
                    'transporte_duracion' => $bibliotecaItem['duracion'] ?? null,
                    'transporte_distancia_km' => $bibliotecaItem['distancia_km'] ?? null,
                    'transporte_idioma' => $bibliotecaItem['idioma'] ?? null
                ]);
                break;
                
            case 'alojamiento':
                $servicioData = array_merge($servicioData, [
                    // Campos comunes
                    'nombre_servicio' => $bibliotecaItem['nombre'] ?? null,
                    'descripcion_servicio' => $bibliotecaItem['descripcion'] ?? null,
                    'ubicacion_servicio' => $bibliotecaItem['ubicacion'] ?? null,
                    'latitud' => $bibliotecaItem['latitud'] ?? null,
                    'longitud' => $bibliotecaItem['longitud'] ?? null,
                    // Campos específicos de alojamiento
                    'alojamiento_tipo' => $bibliotecaItem['tipo'] ?? null,
                    'alojamiento_categoria' => $bibliotecaItem['categoria'] ?? null,
                    'alojamiento_imagen' => $bibliotecaItem['imagen'] ?? null,
                    'alojamiento_sitio_web' => $bibliotecaItem['sitio_web'] ?? null,
                    'alojamiento_idioma' => $bibliotecaItem['idioma'] ?? null
                ]);
                break;
        }
        
        return $servicioData;
    }
    
    // ================================================================
    // FUNCIÓN: OBTENER ITEM COMPLETO DE BIBLIOTECA
    // ================================================================
    private function getBibliotecaItemCompleto($tipoServicio, $itemId, $agencia_id) {
        try {
            switch($tipoServicio) {
                case 'actividad':
                    return $this->db->fetch(
                        "SELECT * FROM biblioteca_actividades 
                         WHERE id = ? AND activo = 1 AND agencia_id = ?", 
                        [$itemId, $agencia_id]
                    );
                case 'transporte':
                    return $this->db->fetch(
                        "SELECT * FROM biblioteca_transportes 
                         WHERE id = ? AND activo = 1 AND agencia_id = ?", 
                        [$itemId, $agencia_id]
                    );
                case 'alojamiento':
                    return $this->db->fetch(
                        "SELECT * FROM biblioteca_alojamientos 
                         WHERE id = ? AND activo = 1 AND agencia_id = ?", 
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
    
    // ================================================================
    // FUNCIÓN: AGREGAR ALTERNATIVA (TAMBIÉN CON AISLAMIENTO)
    // ================================================================
    private function addAlternative($servicioPrincipalId, $bibliotecaItemId, $variacionPrecio = null) {
        if (!$servicioPrincipalId || !$bibliotecaItemId) {
            throw new Exception('Servicio principal e item de biblioteca requeridos');
        }
        
        try {
            $user_id = $_SESSION['user_id'];
            $agencia_id = $_SESSION['agencia_id'] ?? null;

            if (!$agencia_id) {
                throw new Exception('Usuario sin agencia asignada');
            }
            
            error_log("➕ Agregando ALTERNATIVA aislada para servicio $servicioPrincipalId");
            
            // Obtener datos del servicio principal
            $servicioPrincipal = $this->db->fetch(
                "SELECT pds.*, ps.user_id 
                FROM programa_dias_servicios pds
                JOIN programa_dias pd ON pds.programa_dia_id = pd.id
                JOIN programa_solicitudes ps ON pd.solicitud_id = ps.id 
                WHERE pds.id = ? AND ps.user_id = ? AND ps.agencia_id = ?", 
                [$servicioPrincipalId, $user_id, $agencia_id]
            );
            
            if (!$servicioPrincipal) {
                throw new Exception('Servicio principal no encontrado');
            }
            
            // ⭐ OBTENER DATOS COMPLETOS DE BIBLIOTECA
            $bibliotecaItem = $this->getBibliotecaItemCompleto(
                $servicioPrincipal['tipo_servicio'], 
                $bibliotecaItemId, 
                $agencia_id
            );
            
            if (!$bibliotecaItem) {
                throw new Exception('Item de biblioteca no encontrado');
            }
            
            // Obtener siguiente orden de alternativa
            $lastAlternativeOrder = $this->db->fetch(
                "SELECT MAX(orden_alternativa) as max_orden 
                 FROM programa_dias_servicios 
                 WHERE servicio_principal_id = ?", 
                [$servicioPrincipalId]
            );
            
            $nextAlternativeOrder = ($lastAlternativeOrder['max_orden'] ?? 0) + 1;
            
            // ⭐ PREPARAR DATOS COPIADOS PARA LA ALTERNATIVA
            $alternativaData = $this->prepararDatosServicio(
                $servicioPrincipal['programa_dia_id'],
                $servicioPrincipal['tipo_servicio'],
                $bibliotecaItem,
                $servicioPrincipal['orden']
            );
            
            // Modificar para que sea alternativa
            $alternativaData['servicio_principal_id'] = $servicioPrincipalId;
            $alternativaData['es_alternativa'] = 1;
            $alternativaData['orden_alternativa'] = $nextAlternativeOrder;
            $alternativaData['biblioteca_item_id'] = $bibliotecaItemId; // Referencia histórica
            // Diferencia de precio respecto al hotel principal (+más / -menos)
            if ($variacionPrecio !== null && $variacionPrecio !== '') {
                $alternativaData['variacion_precio'] = (float) $variacionPrecio;
            }
            
            error_log("📝 Datos de ALTERNATIVA aislada: " . json_encode($alternativaData, JSON_PRETTY_PRINT));
            
            $alternativaId = $this->db->insert('programa_dias_servicios', $alternativaData);
            
            if (!$alternativaId) {
                throw new Exception('Error al insertar alternativa');
            }
            
            error_log("✅ ALTERNATIVA aislada agregada: ID $alternativaId");
            
            return [
                'success' => true,
                'alternativa_id' => $alternativaId,
                'servicio_principal_id' => $servicioPrincipalId,
                'orden_alternativa' => $nextAlternativeOrder,
                'message' => 'Alternativa agregada exitosamente (datos copiados)'
            ];
            
        } catch(Exception $e) {
            error_log("Error en addAlternative: " . $e->getMessage());
            throw $e;
        }
    }
    
    // ================================================================
    // RESTO DE FUNCIONES (SIN CAMBIOS - COPIAR DEL ORIGINAL)
    // ================================================================
    
    private function deleteService($servicioId) {
        if (!$servicioId) {
            throw new Exception('ID de servicio requerido');
        }
        
        try {
            $user_id = $_SESSION['user_id'];
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
            
            // Si es servicio principal, eliminar también sus alternativas
            if ($servicio['es_alternativa'] == 0) {
                // Fetch alternativas para borrar sus imágenes
                $alternativas = $this->db->fetchAll(
                    "SELECT actividad_imagen1, actividad_imagen2, actividad_imagen3, alojamiento_imagen 
                     FROM programa_dias_servicios WHERE servicio_principal_id = ?", 
                    [$servicioId]
                );
                foreach ($alternativas as $alt) {
                    if (!empty($alt['actividad_imagen1'])) ProgramaImageUploader::deletePhysicalImage($alt['actividad_imagen1']);
                    if (!empty($alt['actividad_imagen2'])) ProgramaImageUploader::deletePhysicalImage($alt['actividad_imagen2']);
                    if (!empty($alt['actividad_imagen3'])) ProgramaImageUploader::deletePhysicalImage($alt['actividad_imagen3']);
                    if (!empty($alt['alojamiento_imagen'])) ProgramaImageUploader::deletePhysicalImage($alt['alojamiento_imagen']);
                }

                $stmt = $this->db->query(
                    "DELETE FROM programa_dias_servicios WHERE servicio_principal_id = ?", 
                    [$servicioId]
                );
            }
            
            // Eliminar imágenes del servicio principal
            if (!empty($servicio['actividad_imagen1'])) ProgramaImageUploader::deletePhysicalImage($servicio['actividad_imagen1']);
            if (!empty($servicio['actividad_imagen2'])) ProgramaImageUploader::deletePhysicalImage($servicio['actividad_imagen2']);
            if (!empty($servicio['actividad_imagen3'])) ProgramaImageUploader::deletePhysicalImage($servicio['actividad_imagen3']);
            if (!empty($servicio['alojamiento_imagen'])) ProgramaImageUploader::deletePhysicalImage($servicio['alojamiento_imagen']);
            
            // Eliminar el servicio
            $deleted = $this->db->delete('programa_dias_servicios', 'id = ?', [$servicioId]);
            
            if (!$deleted) {
                throw new Exception('Error al eliminar servicio');
            }
            
            return [
                'success' => true,
                'message' => 'Servicio eliminado exitosamente'
            ];
            
        } catch(Exception $e) {
            error_log("Error en deleteService: " . $e->getMessage());
            throw $e;
        }
    }
    
private function listServices($diaId) {
    if (!$diaId) {
        throw new Exception('ID de día requerido');
    }
    
    try {
        $user_id = $_SESSION['user_id'];
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
        
        // ⭐ OBTENER TODOS LOS SERVICIOS (principales y alternativas) en lista plana
        $todos_servicios = $this->db->fetchAll(
            "SELECT 
                pds.*,
                pds.nombre_servicio as nombre,
                pds.nombre_servicio as titulo,
                pds.descripcion_servicio as descripcion,
                pds.ubicacion_servicio as ubicacion,
                pds.latitud,
                pds.longitud,
                
                -- Campos de actividad
                pds.actividad_imagen1 as imagen1,
                pds.actividad_imagen2 as imagen2,
                pds.actividad_imagen3 as imagen3,
                
                -- Campos de transporte
                pds.transporte_medio as medio,
                pds.transporte_lugar_salida as lugar_salida,
                pds.transporte_lugar_llegada as lugar_llegada,
                pds.transporte_lat_salida as lat_salida,
                pds.transporte_lng_salida as lng_salida,
                pds.transporte_lat_llegada as lat_llegada,
                pds.transporte_lng_llegada as lng_llegada,
                pds.transporte_duracion as duracion,
                
                -- Campos de alojamiento
                pds.alojamiento_tipo as tipo,
                pds.alojamiento_categoria as categoria,
                pds.alojamiento_imagen as imagen,
                pds.acomodacion_id,
                a.tipo_acomodacion AS acomodacion_nombre,
                a.descripcion AS acomodacion_descripcion,
                a.acomodacion AS acomodacion_capacidad
                
            FROM programa_dias_servicios pds
            LEFT JOIN acomodaciones a ON pds.acomodacion_id = a.id
            WHERE pds.programa_dia_id = ?
            ORDER BY pds.orden ASC, pds.es_alternativa ASC, pds.orden_alternativa ASC", 
            [$diaId]
        );
        
        error_log("📋 Total servicios encontrados (planos): " . count($todos_servicios));
        
        // ⭐ AGRUPAR: Separar principales de alternativas
        $servicios_principales = [];
        $alternativas_por_principal = [];
        
        foreach ($todos_servicios as $servicio) {
            if ($servicio['es_alternativa'] == 0) {
                // Es un servicio principal
                $servicio['alternativas'] = []; // Inicializar array de alternativas vacío
                $servicios_principales[$servicio['id']] = $servicio;
                error_log("✅ Servicio principal encontrado: ID={$servicio['id']}, Nombre={$servicio['nombre']}");
            } else {
                // Es una alternativa
                $principal_id = $servicio['servicio_principal_id'];
                if (!isset($alternativas_por_principal[$principal_id])) {
                    $alternativas_por_principal[$principal_id] = [];
                }
                $alternativas_por_principal[$principal_id][] = $servicio;
                error_log("🔄 Alternativa encontrada: ID={$servicio['id']}, Principal_ID=$principal_id, Orden_Alt={$servicio['orden_alternativa']}");
            }
        }
        
        // ⭐ ASIGNAR alternativas a sus servicios principales
        foreach ($servicios_principales as $id => &$principal) {
            if (isset($alternativas_por_principal[$id])) {
                $principal['alternativas'] = $alternativas_por_principal[$id];
                error_log("📦 Servicio ID=$id tiene " . count($principal['alternativas']) . " alternativas");
            } else {
                $principal['alternativas'] = [];
                error_log("📦 Servicio ID=$id NO tiene alternativas");
            }
        }
        unset($principal); // Romper referencia
        
        // ⭐ CONVERTIR a array indexado (sin las keys de ID)
        $servicios_agrupados = array_values($servicios_principales);
        
        error_log("✅ Servicios principales agrupados: " . count($servicios_agrupados));
        foreach ($servicios_agrupados as $srv) {
            error_log("   - ID={$srv['id']}: {$srv['nombre']} con " . count($srv['alternativas']) . " alternativas");
        }
        
        return [
            'success' => true,
            'data' => $servicios_agrupados,
            'total_principales' => count($servicios_agrupados),
            'total_servicios' => count($todos_servicios)
        ];
        
    } catch(Exception $e) {
        error_log("Error en listServices: " . $e->getMessage());
        throw $e;
    }
}
    
private function updateService($servicioId, $data) {
    if (!$servicioId) {
        throw new Exception('ID de servicio requerido');
    }
    
    try {
        $user_id = $_SESSION['user_id'];
        $agencia_id = $_SESSION['agencia_id'] ?? null;

        if (!$agencia_id) {
            throw new Exception('Usuario sin agencia asignada');
        }
        
        // ⭐ VERIFICAR PERMISOS Y QUE SEA ACTIVIDAD
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
        
        // ⭐ SOLO PERMITIR EDITAR ACTIVIDADES
        //if ($servicio['tipo_servicio'] !== 'actividad') {
         //   throw new Exception('Solo se pueden editar actividades');
        //}

        //Ahora si se editan hoteles
        if (!in_array($servicio['tipo_servicio'], ['actividad', 'alojamiento'])) {
            throw new Exception('Solo se pueden editar actividades y alojamientos');
        }
        
        // ⭐ VALIDACIONES
        if (empty($data['nombre_servicio'])) {
            throw new Exception('El nombre es obligatorio');
        }
        
        if (empty($data['descripcion_servicio'])) {
            throw new Exception('La descripción es obligatoria');
        }
        
        // Validar al menos 1 imagen
    //    $hasImage = false;
    //    for ($i = 1; $i <= 3; $i++) {
    //        $imageKey = 'actividad_imagen' . $i;
    //        if (!empty($data[$imageKey]) || !empty($servicio[$imageKey])) {
    //            $hasImage = true;
    //            break;
    //        }
    //    }

    // Validar imágenes solo para actividades
        if ($servicio['tipo_servicio'] === 'actividad') {
            $hasImage = false;

            for ($i = 1; $i <= 3; $i++) {
                $imageKey = 'actividad_imagen' . $i;

                if (!empty($data[$imageKey]) || !empty($servicio[$imageKey])) {
                    $hasImage = true;
                    break;
                }
            }

            if (!$hasImage) {
                throw new Exception('Debe tener al menos 1 imagen');
            }
        }
        


        if ($servicio['tipo_servicio'] === 'alojamiento' && isset($data['acomodacion_id'])) {
            $acomodacionId = !empty($data['acomodacion_id']) ? (int)$data['acomodacion_id'] : null;

            if ($acomodacionId) {
                $acomodacion = $this->db->fetch(
                    "SELECT a.id
                    FROM acomodaciones a
                    INNER JOIN biblioteca_alojamientos h ON a.hotel_id = h.id
                    WHERE a.id = ?
                    AND h.id = ?
                    AND h.agencia_id = ?
                    LIMIT 1",
                    [$acomodacionId, $servicio['biblioteca_item_id'], $agencia_id]
                );

                if (!$acomodacion) {
                    throw new Exception('La acomodación no pertenece al alojamiento seleccionado');
                }
            }

            $data['acomodacion_id'] = $acomodacionId;
        }
        
        // ⭐ PREPARAR DATOS PARA ACTUALIZAR
        $updateData = [];
        $allowedFields = [
            'nombre_servicio',
            'descripcion_servicio',
            'ubicacion_servicio',
            'latitud',
            'longitud',
            'actividad_imagen1',
            'actividad_imagen2',
            'actividad_imagen3',
            'acomodacion_id',
            'variacion_precio'
        ];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        if (isset($updateData['variacion_precio'])) {
            $val = preg_replace('/[^0-9.\-]/', '', $updateData['variacion_precio']);
            $updateData['variacion_precio'] = is_numeric($val) ? floatval($val) : null;
        }  
              
        if (empty($updateData)) {
            throw new Exception('No hay datos para actualizar');
        }
        
        // ⭐ ACTUALIZAR EN BASE DE DATOS
        $rowsAffected = $this->db->update(
            'programa_dias_servicios',
            $updateData,
            'id = ?',
            [$servicioId]
        );
        
        if ($rowsAffected) {
            // Eliminar imágenes físicas reemplazadas o eliminadas
            if (array_key_exists('actividad_imagen1', $updateData) && $updateData['actividad_imagen1'] !== $servicio['actividad_imagen1'] && !empty($servicio['actividad_imagen1'])) {
                ProgramaImageUploader::deletePhysicalImage($servicio['actividad_imagen1']);
            }
            if (array_key_exists('actividad_imagen2', $updateData) && $updateData['actividad_imagen2'] !== $servicio['actividad_imagen2'] && !empty($servicio['actividad_imagen2'])) {
                ProgramaImageUploader::deletePhysicalImage($servicio['actividad_imagen2']);
            }
            if (array_key_exists('actividad_imagen3', $updateData) && $updateData['actividad_imagen3'] !== $servicio['actividad_imagen3'] && !empty($servicio['actividad_imagen3'])) {
                ProgramaImageUploader::deletePhysicalImage($servicio['actividad_imagen3']);
            }
            if (array_key_exists('alojamiento_imagen', $updateData) && $updateData['alojamiento_imagen'] !== $servicio['alojamiento_imagen'] && !empty($servicio['alojamiento_imagen'])) {
                ProgramaImageUploader::deletePhysicalImage($servicio['alojamiento_imagen']);
            }
        }
        
        error_log("✅ Servicio (actividad) $servicioId actualizado: $rowsAffected filas");
        
        return [
            'success' => true,
            'message' => $servicio['tipo_servicio'] === 'alojamiento'
                ? 'Acomodación actualizada correctamente'
                : 'Actividad actualizada exitosamente',
            'servicio_id' => $servicioId
        ];

        
    } catch(Exception $e) {
        error_log("❌ Error en updateService: " . $e->getMessage());
        throw $e;
    }
}
    
    private function reorderServices($diaId, $orden) {
        if (!$diaId || empty($orden)) {
            throw new Exception('ID de día y orden requeridos');
        }
        
        try {
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
    
    private function sendError($message) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $message 
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Ejecutar API
try {
    $api = new ProgramaServiciosAPI();
    $api->handleRequest();
} catch(Exception $e) {
    error_log("Error fatal en API: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor'
    ], JSON_UNESCAPED_UNICODE);
}