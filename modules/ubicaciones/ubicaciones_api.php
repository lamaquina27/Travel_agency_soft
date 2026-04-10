<?php
// ====================================================================
// ARCHIVO: modules/ubicaciones/ubicaciones_api.php
// DESCRIPCIÓN: API para búsqueda centralizada de ubicaciones
// ====================================================================

ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/app.php';

App::init();
App::requireLogin();

class UbicacionesAPI {
    private $db;
    private $agencia_id;
    
    public function __construct() {
        try {
            $this->db = Database::getInstance();
            $this->agencia_id = $_SESSION['agencia_id'] ?? null;
            
            if (!$this->agencia_id) {
                throw new Exception('Usuario sin agencia asignada');
            }
        } catch(Exception $e) {
            $this->sendError('Error de conexión: ' . $e->getMessage());
        }
    }
    
    public function handleRequest() {
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        
        $action = $_GET['action'] ?? $_POST['action'] ?? '';
        
        try {
            error_log("=== UBICACIONES API ===");
            error_log("Action: " . $action);
            
            switch($action) {
                case 'search':
                    $query = $_GET['q'] ?? $_POST['q'] ?? '';
                    $result = $this->searchUbicaciones($query);
                    break;
                    
                case 'save':
                    $data = json_decode(file_get_contents('php://input'), true);
                    $result = $this->saveUbicacion($data);
                    break;
                    
                case 'increment':
                    $id = $_POST['id'] ?? null;
                    $result = $this->incrementUsoCount($id);
                    break;
                    
                case 'get':
                    $id = $_GET['id'] ?? null;
                    $result = $this->getUbicacion($id);
                    break;
                    
                default:
                    throw new Exception('Acción no válida');
            }
            
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            
        } catch(Exception $e) {
            error_log("UbicacionesAPI Error: " . $e->getMessage());
            $this->sendError($e->getMessage());
        }
        
        exit;
    }
    
    private function sendError($message) {
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false, 
            'error' => $message
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * BÚSQUEDA HÍBRIDA: Local + Externa
     */
    private function searchUbicaciones($query) {
        if (empty($query) || strlen($query) < 3) {
            return ['success' => true, 'data' => [], 'source' => 'none'];
        }
        
        try {
            // FASE 1: Búsqueda local en base de datos
            $localResults = $this->searchLocal($query);
            
            error_log("Búsqueda local encontró: " . count($localResults) . " resultados");
            
            // Si hay suficientes resultados locales, retornarlos
            if (count($localResults) >= 5) {
                return [
                    'success' => true,
                    'data' => $localResults,
                    'source' => 'local',
                    'count' => count($localResults)
                ];
            }
            
            // FASE 2: Búsqueda externa en Nominatim
            $externalResults = $this->searchNominatim($query);
            
            error_log("Búsqueda externa encontró: " . count($externalResults) . " resultados");
            
            // Combinar resultados (locales primero)
            $combinedResults = array_merge($localResults, $externalResults);
            
            // Limitar a 10 resultados
            $combinedResults = array_slice($combinedResults, 0, 10);
            
            return [
                'success' => true,
                'data' => $combinedResults,
                'source' => 'hybrid',
                'local_count' => count($localResults),
                'external_count' => count($externalResults),
                'total_count' => count($combinedResults)
            ];
            
        } catch(Exception $e) {
            throw new Exception('Error en búsqueda: ' . $e->getMessage());
        }
    }
    
    /**
     * Búsqueda en base de datos local
     */
    private function searchLocal($query) {
        try {
            // Usar FULLTEXT search si está disponible
            $sql = "SELECT 
                        id,
                        nombre,
                        nombre_completo,
                        tipo,
                        pais,
                        region,
                        latitud,
                        longitud,
                        uso_count,
                        'local' as source
                    FROM ubicaciones_principales
                    WHERE (agencia_id = ? OR agencia_id IS NULL)
                      AND (
                          MATCH(nombre, nombre_completo, pais, region) AGAINST(? IN NATURAL LANGUAGE MODE)
                          OR nombre LIKE ?
                          OR nombre_completo LIKE ?
                      )
                    ORDER BY 
                        uso_count DESC,
                        agencia_id DESC,
                        created_at DESC
                    LIMIT 10";
            
            $searchTerm = "%{$query}%";
            
            $results = $this->db->fetchAll($sql, [
                $this->agencia_id,
                $query,
                $searchTerm,
                $searchTerm
            ]);
            
            // Formatear resultados para el frontend
            $formatted = [];
            foreach ($results as $result) {
                $formatted[] = [
                    'id' => $result['id'],
                    'display_name' => $result['nombre_completo'],
                    'name' => $result['nombre'],
                    'type' => $result['tipo'],
                    'lat' => (float)$result['latitud'],
                    'lon' => (float)$result['longitud'],
                    'country' => $result['pais'],
                    'region' => $result['region'],
                    'source' => 'local',
                    'uso_count' => $result['uso_count']
                ];
            }
            
            return $formatted;
            
        } catch(Exception $e) {
            error_log("Error en búsqueda local: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Búsqueda en API externa (Nominatim)
     */
    private function searchNominatim($query) {
        try {
            $url = "https://nominatim.openstreetmap.org/search?" . http_build_query([
                'format' => 'json',
                'q' => $query,
                'limit' => 8,
                'addressdetails' => 1,
                'accept-language' => 'es'
            ]);
            
            $options = [
                'http' => [
                    'header' => "User-Agent: TravelAgency/1.0\r\n",
                    'timeout' => 5
                ]
            ];
            
            $context = stream_context_create($options);
            $response = @file_get_contents($url, false, $context);
            
            if ($response === false) {
                error_log("Error al conectar con Nominatim");
                return [];
            }
            
            $data = json_decode($response, true);
            
            if (!$data || !is_array($data)) {
                return [];
            }
            
            // Formatear y guardar resultados relevantes
            $formatted = [];
            foreach ($data as $item) {
                $ubicacion = $this->formatNominatimResult($item);
                
                // Auto-guardar ubicación en BD (async)
                $this->saveUbicacionAsync($ubicacion);
                
                $formatted[] = $ubicacion;
            }
            
            return $formatted;
            
        } catch(Exception $e) {
            error_log("Error en búsqueda Nominatim: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Formatear resultado de Nominatim
     */
    private function formatNominatimResult($item) {
        $address = $item['address'] ?? [];
        
        return [
            'id' => null,
            'display_name' => $item['display_name'] ?? '',
            'name' => $this->extractName($item),
            'type' => $this->mapOsmType($item['type'] ?? 'other'),
            'lat' => (float)($item['lat'] ?? 0),
            'lon' => (float)($item['lon'] ?? 0),
            'country' => $address['country'] ?? null,
            'region' => $address['state'] ?? $address['region'] ?? null,
            'place_id' => $item['place_id'] ?? null,
            'osm_type' => $item['osm_type'] ?? null,
            'source' => 'nominatim'
        ];
    }
    
    /**
     * Extraer nombre principal de resultado
     */
    private function extractName($item) {
        $displayName = $item['display_name'] ?? '';
        $parts = explode(',', $displayName);
        return trim($parts[0] ?? $displayName);
    }
    
    /**
     * Mapear tipo de OSM a tipo de sistema
     */
    private function mapOsmType($osmType) {
        $mapping = [
            'city' => 'ciudad',
            'town' => 'ciudad',
            'village' => 'ciudad',
            'hotel' => 'hotel',
            'motel' => 'hotel',
            'hostel' => 'hotel',
            'monument' => 'monumento',
            'memorial' => 'monumento',
            'airport' => 'aeropuerto',
            'aerodrome' => 'aeropuerto',
            'station' => 'estacion',
            'bus_station' => 'estacion',
            'train_station' => 'estacion',
            'park' => 'parque',
            'beach' => 'playa',
            'restaurant' => 'restaurante',
            'cafe' => 'restaurante',
            'state' => 'region',
            'region' => 'region',
            'country' => 'pais'
        ];
        
        return $mapping[$osmType] ?? 'otro';
    }
    
    /**
     * Guardar ubicación en BD (asíncrono, no bloquea la respuesta)
     */
    private function saveUbicacionAsync($ubicacion) {
        try {
            // Verificar si ya existe
            $exists = $this->db->fetch(
                "SELECT id FROM ubicaciones_principales 
                 WHERE latitud = ? AND longitud = ? 
                 AND (agencia_id = ? OR agencia_id IS NULL)
                 LIMIT 1",
                [$ubicacion['lat'], $ubicacion['lon'], $this->agencia_id]
            );
            
            if ($exists) {
                // Ya existe, solo incrementar uso
                $this->db->query(
                    "UPDATE ubicaciones_principales 
                     SET uso_count = uso_count + 1 
                     WHERE id = ?",
                    [$exists['id']]
                );
                return;
            }
            
            // No existe, insertar como global (agencia_id = NULL)
            $data = [
                'nombre' => $ubicacion['name'],
                'nombre_completo' => $ubicacion['display_name'],
                'tipo' => $ubicacion['type'],
                'pais' => $ubicacion['country'],
                'region' => $ubicacion['region'],
                'latitud' => $ubicacion['lat'],
                'longitud' => $ubicacion['lon'],
                'place_id' => $ubicacion['place_id'],
                'osm_type' => $ubicacion['osm_type'],
                'agencia_id' => null, // Global
                'uso_count' => 1
            ];
            
            $this->db->insert('ubicaciones_principales', $data);
            
        } catch(Exception $e) {
            // No fallar, solo loguear
            error_log("Error guardando ubicación async: " . $e->getMessage());
        }
    }
    
    /**
     * Guardar ubicación manualmente (cuando usuario la usa)
     */
    private function saveUbicacion($data) {
        try {
            if (empty($data['nombre']) || empty($data['lat']) || empty($data['lon'])) {
                throw new Exception('Datos incompletos');
            }
            
            // Verificar si ya existe
            $exists = $this->db->fetch(
                "SELECT id FROM ubicaciones_principales 
                 WHERE latitud = ? AND longitud = ? 
                 AND agencia_id = ?
                 LIMIT 1",
                [$data['lat'], $data['lon'], $this->agencia_id]
            );
            
            if ($exists) {
                // Ya existe, incrementar uso
                $this->incrementUsoCount($exists['id']);
                return [
                    'success' => true,
                    'id' => $exists['id'],
                    'action' => 'updated'
                ];
            }
            
            // Insertar nueva ubicación específica de agencia
            $insertData = [
                'nombre' => $data['nombre'],
                'nombre_completo' => $data['nombre_completo'] ?? $data['nombre'],
                'tipo' => $data['tipo'] ?? 'otro',
                'pais' => $data['pais'] ?? null,
                'region' => $data['region'] ?? null,
                'latitud' => $data['lat'],
                'longitud' => $data['lon'],
                'place_id' => $data['place_id'] ?? null,
                'osm_type' => $data['osm_type'] ?? null,
                'agencia_id' => $this->agencia_id,
                'uso_count' => 1
            ];
            
            $id = $this->db->insert('ubicaciones_principales', $insertData);
            
            return [
                'success' => true,
                'id' => $id,
                'action' => 'created'
            ];
            
        } catch(Exception $e) {
            throw new Exception('Error guardando ubicación: ' . $e->getMessage());
        }
    }
    
    /**
     * Incrementar contador de uso
     */
    private function incrementUsoCount($id) {
        try {
            if (!$id) {
                throw new Exception('ID requerido');
            }
            
            $this->db->query(
                "UPDATE ubicaciones_principales 
                 SET uso_count = uso_count + 1 
                 WHERE id = ? AND (agencia_id = ? OR agencia_id IS NULL)",
                [$id, $this->agencia_id]
            );
            
            return ['success' => true];
            
        } catch(Exception $e) {
            throw new Exception('Error incrementando uso: ' . $e->getMessage());
        }
    }
    
    /**
     * Obtener detalles de una ubicación
     */
    private function getUbicacion($id) {
        try {
            if (!$id) {
                throw new Exception('ID requerido');
            }
            
            $ubicacion = $this->db->fetch(
                "SELECT * FROM ubicaciones_principales 
                 WHERE id = ? AND (agencia_id = ? OR agencia_id IS NULL)",
                [$id, $this->agencia_id]
            );
            
            if (!$ubicacion) {
                throw new Exception('Ubicación no encontrada');
            }
            
            return [
                'success' => true,
                'data' => $ubicacion
            ];
            
        } catch(Exception $e) {
            throw new Exception('Error obteniendo ubicación: ' . $e->getMessage());
        }
    }
}

// Instanciar y ejecutar API
$api = new UbicacionesAPI();
$api->handleRequest();