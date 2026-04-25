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

class UbicacionesAPI
{
    private $db;
    private $agencia_id;

    public function __construct()
    {
        try {
            $this->db = Database::getInstance();
            $this->agencia_id = $_SESSION['agencia_id'] ?? null;

            if (!$this->agencia_id) {
                throw new Exception('Usuario sin agencia asignada');
            }
        } catch (Exception $e) {
            $this->sendError('Error de conexión: ' . $e->getMessage());
        }
    }

    public function handleRequest()
    {
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');

        $action = $_GET['action'] ?? $_POST['action'] ?? '';

        try {
            error_log("=== UBICACIONES API ===");
            error_log("Action: " . $action);

            switch ($action) {
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

        } catch (Exception $e) {
            error_log("UbicacionesAPI Error: " . $e->getMessage());
            $this->sendError($e->getMessage());
        }

        exit;
    }

    private function sendError($message)
    {
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
    private function searchUbicaciones($query)
    {
        if (empty($query) || strlen($query) < 3) {
            return ['success' => true, 'data' => [], 'source' => 'none'];
        }

        try {

            //primero busca en Nominatim de busquedas pasadas
            $results = $this->searchNominatim($query);
            error_log("Nominatim local encontró: " . count($results) . " resultados");

            //---------------En caso de que nominatim  no encuentre o no supere un puntaje minimo busca en geoapify--------------------
            if (empty($results) || $results[0]['puntaje'] <= 80) {
                error_log("Nominatim sin resultados para '$query', usando Geoapify...");
                $results = $this->searchGeoapify($query);
                error_log("Geoapify encontró: " . count($results) . " resultados");
            }
            //---------------En caso de que nominatim o geoapify no encuentren o no superen un puntaje minimo busca en google----------
            if (empty($results) || $results[0]['puntaje'] <= 80) {
                error_log("Nominatim sin resultados para '$query', usando Google...");
                $results = $this->searchGoogle($query);
            }

            return [
                'success' => true,
                'data' => $results,
                'count' => count($results)
            ];
        } catch (Exception $e) {
            throw new Exception('Error en búsqueda: ' . $e->getMessage());
        }
    }
    /**
     * Búsqueda en base de datos local
     */
    private function searchLocal($query)
    {
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
                    'lat' => (float) $result['latitud'],
                    'lon' => (float) $result['longitud'],
                    'country' => $result['pais'],
                    'region' => $result['region'],
                    'source' => 'local',
                    'uso_count' => $result['uso_count']
                ];
            }

            return $formatted;

        } catch (Exception $e) {
            error_log("Error en búsqueda local: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Búsqueda en API externa (Nominatim)
     */
    private function searchNominatim($query)
    {
        try {
            // DESPUÉS
            $url = "https://nominatim.openstreetmap.org/search?" . http_build_query([
                'format' => 'json',
                'q' => $query,
                'limit' => 15,
                'addressdetails' => 1,
                'accept-language' => 'es,en;q=0.9',
                'extratags' => 1,
                'namedetails' => 1
            ]);

            // Segunda búsqueda con contexto de hotel si no trae resultados
            $urlHotel = "https://nominatim.openstreetmap.org/search?" . http_build_query([
                'format' => 'json',
                'q' => $query . ' hotel',
                'limit' => 8,
                'addressdetails' => 1,
                'accept-language' => 'es,en;q=0.9',
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
                $nombre = explode(',', $item['display_name'] ?? '')[0];
                //variable para comparar nombre exacto
                $nombre_exacto = $item['namedetails']['name'] ?? $nombre;
                $puntaje = 0;
                // Saltar resultados cuyo nombre principal tenga caracteres no latinos (chino, tailandés, árabe, etc.)
                if (!preg_match('/^[\p{Latin}\p{N}\s\-\.\,\(\)\'\/]+$/u', trim($nombre))) {
                    continue;
                }
                //-------------------Comparativas de texto para asignar puntaje ----------------
                if (strcasecmp($nombre_exacto, $query) == 0) {
                    $puntaje = 100;
                } elseif (stripos($nombre, $query) !== false) {
                    $puntaje = 90;
                } elseif (levenshtein($nombre_exacto, $query) > 0 && levenshtein($nombre_exacto, $query) <= 5) {
                    $puntaje = 80;
                } else {
                    $puntaje = 0;
                }

                $ubicacion = $this->formatNominatimResult($item, $puntaje);
                $this->saveUbicacionAsync($ubicacion);
                $formatted[] = $ubicacion;
            }
            //----------------Funcion para organizar el arreglo de ubicaciones, de mayor puntaje a menor ----------------------
            usort($formatted, function ($a, $b) {
                return $b['puntaje'] <=> $a['puntaje'];
            });

            return $formatted;

        } catch (Exception $e) {
            error_log("Error en búsqueda Nominatim: " . $e->getMessage());
            return [];
        }
    }

    private function searchGeoapify($query)
    {
        try {
            $apiKey = $_ENV['GEOAPIFY_API_KEY'] ?? '';

            if (empty($apiKey)) {
                error_log("GEOAPIFY_API_KEY no configurado");
                return [];
            }

            $url = "https://api.geoapify.com/v1/geocode/search?" . http_build_query([
                'text' => $query,
                'apiKey' => $apiKey,
                'limit' => 10,
                'lang' => 'es',
                'format' => 'json',
            ]);

            $options = [
                'http' => [
                    'header' => "User-Agent: TravelAgency/1.0\r\n",
                    'timeout' => 8
                ]
            ];

            $context = stream_context_create($options);
            $response = @file_get_contents($url, false, $context);

            if ($response === false) {
                error_log("Error al conectar con Geoapify");
                return [];
            }

            $data = json_decode($response, true);

            if (!$data || !isset($data['results'])) {
                return [];
            }

            $formatted = [];
            foreach ($data['results'] as $item) {
                $nombre = $item['name'] ?? $item['address_line1'] ?? '';
                $puntaje = 0;
                // Filtrar nombres con caracteres no latinos
                if (!empty($nombre) && !preg_match('/^[\p{Latin}\p{N}\s\-\.\,\(\)\'\/]+$/u', $nombre)) {
                    continue;
                }
                //-------------------Comparativas de texto para asignar puntaje ----------------
                if (strcasecmp($nombre, $query) == 0) {
                    $puntaje = 100;
                } elseif (stripos($nombre, $query) !== false) {
                    $puntaje = 90;
                } elseif (levenshtein($nombre, $query) > 0 && levenshtein($nombre, $query) <= 5) {
                    $puntaje = 80;
                } else {
                    $puntaje = 0;
                }
                $partes = array_filter([
                    $item['name'] ?? null,
                    $item['street'] ?? null,
                    $item['city'] ?? null,
                    $item['state'] ?? null,
                    $item['country'] ?? null,
                ]);
                $displayName = implode(', ', $partes);

                $ubicacion = [
                    'id' => null,
                    'display_name' => $displayName ?: $nombre,
                    'name' => $nombre,
                    'puntaje' => $puntaje,
                    'type' => $this->mapOsmType($item['result_type'] ?? 'other'),
                    'lat' => (float) ($item['lat'] ?? 0),
                    'lon' => (float) ($item['lon'] ?? 0),
                    'country' => $item['country'] ?? null,
                    'region' => $item['state'] ?? null,
                    'place_id' => $item['place_id'] ?? null,
                    'osm_type' => 'geoapify',
                    'source' => 'geoapify'
                ];

                $this->saveUbicacionAsync($ubicacion);

                $formatted[] = $ubicacion;
            }
            //----------------Funcion para organizar el arreglo de ubicaciones, de mayor puntaje a menor ----------------------
            usort($formatted, function ($a, $b) {
                return $b['puntaje'] <=> $a['puntaje'];
            });
            return $formatted;

        } catch (Exception $e) {
            error_log("Error en búsqueda Geoapify: " . $e->getMessage());
            return [];
        }
    }

    private function searchGoogle($query)
    {
        try {
            $apiKey = $_ENV['GOOGLE_PLACES_API_KEY'] ?? '';

            if (empty($apiKey)) {
                error_log("GOOGLE_PLACES_API_KEY no configurado");
                return [];
            }

            $url = "https://maps.googleapis.com/maps/api/place/textsearch/json?" . http_build_query([
                'query' => $query,
                'key' => $apiKey,
                'language' => 'es',
            ]);

            $options = [
                'http' => [
                    'header' => "User-Agent: TravelAgency/1.0\r\n",
                    'timeout' => 8
                ]
            ];

            $context = stream_context_create($options);
            $response = @file_get_contents($url, false, $context);

            if ($response === false) {
                error_log("Error al conectar con Google Places");
                return [];
            }

            $data = json_decode($response, true);

            if (!$data || !isset($data['results']) || $data['status'] !== 'OK') {
                error_log("Google Places status: " . ($data['status'] ?? 'sin respuesta'));
                return [];
            }

            $formatted = [];
            foreach ($data['results'] as $item) {
                $nombre = $item['name'] ?? '';
                $puntaje = 0;
                if (!empty($nombre) && !preg_match('/^[\p{Latin}\p{N}\s\-\.\,\(\)\'\/\&]+$/u', $nombre)) {
                    continue;
                }
                //-------------------Comparativas de texto para asignar puntaje ----------------
                if (strcasecmp($nombre, $query) == 0) {
                    $puntaje = 100;
                } elseif (stripos($nombre, $query) !== false) {
                    $puntaje = 90;
                } elseif (levenshtein($nombre, $query) > 0 && levenshtein($nombre, $query) <= 5) {
                    $puntaje = 80;
                } else {
                    $puntaje = 0;
                }
                $lat = $item['geometry']['location']['lat'] ?? 0;
                $lon = $item['geometry']['location']['lng'] ?? 0;

                $pais = null;
                $region = null;
                foreach ($item['address_components'] ?? [] as $comp) {
                    if (in_array('country', $comp['types']))
                        $pais = $comp['long_name'];
                    if (in_array('administrative_area_level_1', $comp['types']))
                        $region = $comp['long_name'];
                }

                $ubicacion = [
                    'id' => null,
                    'display_name' => $item['formatted_address'] ?? $nombre,
                    'name' => $nombre,
                    'puntaje' => $puntaje,
                    'type' => $this->mapOsmType($item['types'][0] ?? 'other'),
                    'lat' => (float) $lat,
                    'lon' => (float) $lon,
                    'country' => $pais,
                    'region' => $region,
                    'place_id' => $item['place_id'] ?? null,
                    'osm_type' => 'google',
                    'source' => 'google'
                ];

                $this->saveUbicacionAsync($ubicacion);
                $formatted[] = $ubicacion;
            }
            //----------------Funcion para organizar el arreglo de ubicaciones, de mayor puntaje a menor ----------------------
            usort($formatted, function ($a, $b) {
                return $b['puntaje'] <=> $a['puntaje'];
            });
            return $formatted;

        } catch (Exception $e) {
            error_log("Error en búsqueda Google Places: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Formatear resultado de Nominatim
     */
    private function formatNominatimResult($item, $puntaje)
    {
        $address = $item['address'] ?? [];

        // Limpiar display_name: quedarse solo con partes en caracteres latinos
        $displayName = $item['display_name'] ?? '';
        $cleanName = $this->cleanDisplayName($displayName);

        return [
            'id' => null,
            'display_name' => $cleanName,
            'puntaje' => $puntaje,
            'name' => $this->extractName($item),
            'type' => $this->mapOsmType($item['type'] ?? 'other'),
            'lat' => (float) ($item['lat'] ?? 0),
            'lon' => (float) ($item['lon'] ?? 0),
            'country' => $address['country'] ?? null,
            'region' => $address['state'] ?? $address['region'] ?? null,
            'place_id' => $item['place_id'] ?? null,
            'osm_type' => $item['osm_type'] ?? null,
            'source' => 'nominatim'
        ];
    }

    private function cleanDisplayName($displayName)
    {
        // Dividir por comas y filtrar partes que tengan caracteres no latinos
        $parts = explode(',', $displayName);
        $cleanParts = [];

        foreach ($parts as $part) {
            $part = trim($part);
            // Si la parte contiene solo caracteres latinos, números, espacios y puntuación básica, la conserva
            if (preg_match('/^[\p{Latin}\p{N}\s\-\.\,\(\)\'\/]+$/u', $part)) {
                $cleanParts[] = $part;
            }
        }

        // Si quedaron partes limpias, unirlas
        if (!empty($cleanParts)) {
            return implode(', ', $cleanParts);
        }

        // Si todo era caracteres no latinos, devolver solo el nombre del país/región en inglés si existe
        return $displayName; // fallback al original
    }

    /**
     * Extraer nombre principal de resultado
     */
    private function extractName($item)
    {
        $displayName = $item['display_name'] ?? '';
        $parts = explode(',', $displayName);
        return trim($parts[0] ?? $displayName);
    }

    /**
     * Mapear tipo de OSM a tipo de sistema
     */
    private function mapOsmType($osmType)
    {
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
    private function saveUbicacionAsync($ubicacion)
    {
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

        } catch (Exception $e) {
            // No fallar, solo loguear
            error_log("Error guardando ubicación async: " . $e->getMessage());
        }
    }

    /**
     * Guardar ubicación manualmente (cuando usuario la usa)
     */
    private function saveUbicacion($data)
    {
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

        } catch (Exception $e) {
            throw new Exception('Error guardando ubicación: ' . $e->getMessage());
        }
    }

    /**
     * Incrementar contador de uso
     */
    private function incrementUsoCount($id)
    {
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

        } catch (Exception $e) {
            throw new Exception('Error incrementando uso: ' . $e->getMessage());
        }
    }

    /**
     * Obtener detalles de una ubicación
     */
    private function getUbicacion($id)
    {
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

        } catch (Exception $e) {
            throw new Exception('Error obteniendo ubicación: ' . $e->getMessage());
        }
    }
}

// Instanciar y ejecutar API
$api = new UbicacionesAPI();
$api->handleRequest();