<?php
// ====================================================================
// ARCHIVO: modules/bonos/api.php - API PARA OBTENER DATOS PARA BONOS
// ====================================================================

ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/classes/FechaCalculator.php';

App::init();
App::requireLogin();

class BonosAPI
{
    private $db;

    public function __construct()
    {
        try {
            $this->db = Database::getInstance();
        } catch (Exception $e) {
            $this->sendError('Error de conexión a base de datos: ' . $e->getMessage());
        }
    }

    public function handleRequest()
    {
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');

        $action = $_POST['action'] ?? $_GET['action'] ?? '';

        try {
            error_log("=== PROGRAMA BONOS API ===");
            error_log("Action: " . $action);
            error_log("POST: " . print_r($_POST, true));

            switch ($action) {

                case 'get':
                    $result = $this->getBonos($_GET['programa_id'] ?? null);
                    break;

                default:
                    throw new Exception('Acción no válida: ' . $action);
            }

            echo json_encode($result, JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            error_log("Error en Bonos API: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $this->sendError($e->getMessage());
        }
    }
    private function getBonos($programa_id)
    {
        if (!$programa_id) {
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
                "SELECT id,fecha_llegada FROM programa_solicitudes WHERE id = ? AND user_id = ? AND agencia_id = ?",
                [$programa_id, $user_id, $agencia_id]
            );

            if (!$programa) {
                throw new Exception('Programa no encontrado o sin permisos');
            }

            //------------llamada a la tabla viajeros ------------
            $viajeros = $this->db->fetchAll(
                "SELECT v.nombre, v.apellido,v.tipo_documento,v.numero_documento,v.fecha_nacimiento 
                FROM viajeros_solicitud vs
                INNER JOIN viajeros v ON vs.viajero_id = v.id
                WHERE vs.solicitud_id = ? AND v.agencia_id = ?
                ORDER BY v.nombre ASC, v.apellido ASC",
                [$programa_id, $agencia_id]
            );
            //------------llamada a la tabla de hoteles ------------
            $hoteles = $this->db->fetchAll(
                "SELECT 
                    bv.nombre,
                    bv.ubicacion,
                    a.tipo_acomodacion,
                    a.descripcion,
                    a.acomodacion,
                    pd.dia_numero,
                    pd.fecha_dia AS checkin,
                    DATE_ADD(pd.fecha_dia, INTERVAL pd.duracion_estancia DAY) AS checkout
                FROM programa_dias pd
                INNER JOIN programa_dias_servicios pds ON pds.programa_dia_id = pd.id
                INNER JOIN biblioteca_alojamientos bv ON bv.id = pds.biblioteca_item_id
                LEFT JOIN acomodaciones a ON a.id = pds.acomodacion_id
                WHERE pd.solicitud_id = ? 
                    AND pds.tipo_servicio = 'alojamiento'
                    AND bv.agencia_id = ?
                ORDER BY pd.dia_numero ASC",
                [$programa_id, $agencia_id]
            );
            //------------llamada a la tabla dias ------------
            $dias = $this->db->fetchAll(
                "SELECT *, COALESCE(duracion_estancia, 1) as duracion_estancia 
                FROM programa_dias 
                WHERE solicitud_id = ? 
                ORDER BY dia_numero ASC",
                [$programa_id]
            );
            //-------------agregamos la lista de viajeros a la variable programa -------------------
            $programa["viajeros"] = $viajeros;
            //-------------usamos la funcio de calcular fechas para empezar la logica de chekin y chekout--------------
            $dias = FechaCalculator::calcularFechasDias($dias, $programa['fecha_llegada']);
            $mapa_fechas = [];
            //------------- loop para guardar fecha de llegada y estancia------------------
            foreach ($dias as &$dia) {
                $arreglo_fechas = [];
                $arreglo_fechas["fecha_calculada"] = $dia['fecha_calculada'];
                $arreglo_fechas["estancia"] = $dia['duracion_estancia'];
                $mapa_fechas[$dia["dia_numero"]] = $arreglo_fechas;
            }
            //------------- loop para guardar checkin y chekout en cada hotel------------------
            foreach ($hoteles as &$hotel) {
                $hotel['checkin'] = $mapa_fechas[$hotel['dia_numero']]["fecha_calculada"];
                $hotel['checkout'] = date("Y-m-d", strtotime($hotel['checkin'] . "+ {$mapa_fechas[$hotel['dia_numero']]["estancia"]} days"));
            }
            //-------------agregamos la lista de hoteles a la variable programa -------------------
            $programa["hoteles"] = $hoteles;
            return [
                'success' => true,
                'data' => $programa
            ];

        } catch (Exception $e) {
            error_log("Error en getPrecios: " . $e->getMessage());
            throw $e;
        }
    }
    private function sendError($message)
    {
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => $message
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
$api = new BonosAPI();
$api->handleRequest();
