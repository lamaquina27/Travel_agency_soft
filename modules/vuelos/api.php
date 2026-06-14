<?php
// ====================================================================
// ARCHIVO: modules/vuelos/api.php
// API para consulta y guardado de vuelos en días de programa
// ====================================================================

ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/aerodatabox.php';
require_once dirname(__DIR__, 2) . '/classes/FechaCalculator.php';

App::init();
App::requireLogin();

class VuelosAPI
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

    // ----------------------------------------------------------------
    // SECCIÓN 1: Enrutador de acciones
    // ----------------------------------------------------------------
    public function handleRequest()
    {
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');

        $input  = json_decode(file_get_contents('php://input'), true) ?? [];
        $action = $_GET['action'] ?? $_POST['action'] ?? $input['action'] ?? '';

        try {
            switch ($action) {
                case 'preview':
                    $result = $this->previewVuelo(
                        $input['codigo_vuelo']     ?? '',
                        $input['programa_dias_id'] ?? null
                    );
                    break;

                case 'save':
                    $result = $this->saveVuelo(
                        $input['codigo_vuelo']     ?? '',
                        $input['programa_dias_id'] ?? null
                    );
                    break;

                case 'get':
                    $result = $this->getVuelosDia(
                        (int)($input['programa_dias_id'] ?? $_GET['programa_dias_id'] ?? 0)
                    );
                    break;

                case 'delete':
                    $result = $this->deleteVuelo(
                        (int)($input['vuelo_dia_id'] ?? 0)
                    );
                    break;

                default:
                    throw new Exception("Acción no reconocida: {$action}");
            }

            echo json_encode($result, JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            $this->sendError($e->getMessage());
        }
    }

    // ----------------------------------------------------------------
    // SECCIÓN 2: Preview — consulta sin escribir en base de datos
    // ----------------------------------------------------------------
    private function previewVuelo(string $codigoVuelo, ?int $programaDiasId): array
    {
        if (empty($codigoVuelo)) {
            throw new Exception('El código de vuelo es requerido.');
        }

        if (!$programaDiasId) {
            throw new Exception('El ID del día de programa es requerido.');
        }

        $fechaDia = $this->obtenerFechaRealDia((int)$programaDiasId);

        $vuelo = AeroDataBox::fetchFlight($codigoVuelo, $fechaDia);

        if (!$vuelo) {
            throw new Exception(
                "No se encontró el vuelo {$codigoVuelo} para la fecha {$fechaDia}."
            );
        }

        return [
            'success'   => true,
            'fecha_dia' => $fechaDia,
            'vuelo'     => $vuelo,
        ];
    }

    // ----------------------------------------------------------------
    // SECCIÓN 3: Save — escribe en codigos_vuelos y vuelos_dias
    // ----------------------------------------------------------------
    private function saveVuelo(string $codigoVuelo, ?int $programaDiasId): array
    {
        if (empty($codigoVuelo)) {
            throw new Exception('El código de vuelo es requerido.');
        }

        if (!$programaDiasId) {
            throw new Exception('El ID del día de programa es requerido.');
        }

        // Obtener la fecha del día para llamar a la API
        $fechaDia = $this->obtenerFechaRealDia((int)$programaDiasId);

        // Verificar que el vuelo sigue existiendo en AeroDataBox
        $vuelo = AeroDataBox::fetchFlight($codigoVuelo, $fechaDia);

        if (!$vuelo) {
            throw new Exception(
                "No se pudo verificar el vuelo {$codigoVuelo} para la fecha {$fechaDia}."
            );
        }

        // Buscar si este código de vuelo ya existe en el catálogo
        $stmt = $this->db->getConnection()->prepare(
            'SELECT id FROM codigos_vuelos WHERE codigo_vuelo = ? LIMIT 1'
        );
        $stmt->execute([$vuelo['codigo_vuelo']]);
        $existente = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existente) {
            $codigoVueloId = $existente['id'];
        } else {
            // Insertar el vuelo en el catálogo
            $stmt = $this->db->getConnection()->prepare('
                INSERT INTO codigos_vuelos
                    (codigo_vuelo, aerolinea, ciudad_origen, codigo_aeropuerto_origen,
                     aeropuerto_origen, ciudad_destino, codigo_aeropuerto_destino,
                     aeropuerto_destino, terminal, hora_salida, hora_llegada)
                VALUES
                    (:codigo_vuelo, :aerolinea, :ciudad_origen, :codigo_aeropuerto_origen,
                     :aeropuerto_origen, :ciudad_destino, :codigo_aeropuerto_destino,
                     :aeropuerto_destino, :terminal, :hora_salida, :hora_llegada)
            ');
            $stmt->execute($vuelo);
            $codigoVueloId = $this->db->getConnection()->lastInsertId();
        }

        // Verificar que este vuelo no esté ya asignado a este día
        $stmt = $this->db->getConnection()->prepare('
            SELECT id FROM vuelos_dias
            WHERE codigo_vuelo_id = ? AND programa_dias_id = ?
            LIMIT 1
        ');
        $stmt->execute([$codigoVueloId, $programaDiasId]);

        if ($stmt->fetch()) {
            throw new Exception('Este vuelo ya está asignado a este día.');
        }

        // Calcular el orden: cuántos vuelos tiene el día + 1
        $stmt = $this->db->getConnection()->prepare(
            'SELECT COUNT(*) FROM vuelos_dias WHERE programa_dias_id = ?'
        );
        $stmt->execute([$programaDiasId]);
        $orden = (int) $stmt->fetchColumn() + 1;

        // Crear el vínculo en vuelos_dias
        $stmt = $this->db->getConnection()->prepare('
            INSERT INTO vuelos_dias (codigo_vuelo_id, programa_dias_id, orden)
            VALUES (?, ?, ?)
        ');
        $stmt->execute([$codigoVueloId, $programaDiasId, $orden]);

        return [
            'success'         => true,
            'codigo_vuelo_id' => $codigoVueloId,
            'orden'           => $orden,
            'vuelo'           => $vuelo,
        ];
    }

    // ----------------------------------------------------------------
    // SECCIÓN 5: Get — devuelve los vuelos asignados a un día
    // ----------------------------------------------------------------
    private function getVuelosDia(int $programaDiasId): array
    {
        if (!$programaDiasId) {
            throw new Exception('El ID del día de programa es requerido.');
        }

        $stmt = $this->db->getConnection()->prepare('
            SELECT
                vd.id               AS vuelo_dia_id,
                vd.orden,
                cv.id               AS codigo_vuelo_id,
                cv.codigo_vuelo,
                cv.aerolinea,
                cv.ciudad_origen,
                cv.codigo_aeropuerto_origen,
                cv.aeropuerto_origen,
                cv.ciudad_destino,
                cv.codigo_aeropuerto_destino,
                cv.aeropuerto_destino,
                cv.terminal,
                cv.hora_salida,
                cv.hora_llegada
            FROM vuelos_dias vd
            JOIN codigos_vuelos cv ON cv.id = vd.codigo_vuelo_id
            WHERE vd.programa_dias_id = ?
            ORDER BY vd.orden ASC
        ');
        $stmt->execute([$programaDiasId]);
        $vuelos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'success' => true,
            'vuelos'  => $vuelos,
            'total'   => count($vuelos),
        ];
    }

    // ----------------------------------------------------------------
    // SECCIÓN 6: Delete — elimina un vuelo de un día
    // ----------------------------------------------------------------
    private function deleteVuelo(int $vueloDiaId): array
    {
        if (!$vueloDiaId) {
            throw new Exception('El ID de la asignación (vuelo_dia_id) es requerido.');
        }

        // Obtener el codigo_vuelo_id y el dia antes de eliminar
        $stmt = $this->db->getConnection()->prepare(
            'SELECT codigo_vuelo_id, programa_dias_id FROM vuelos_dias WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$vueloDiaId]);
        $relacion = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$relacion) {
            throw new Exception('La asignación de vuelo no existe.');
        }

        $codigoVueloId  = $relacion['codigo_vuelo_id'];
        $programaDiasId = $relacion['programa_dias_id'];

        // Eliminar la asignación del día
        $stmt = $this->db->getConnection()->prepare(
            'DELETE FROM vuelos_dias WHERE id = ?'
        );
        $stmt->execute([$vueloDiaId]);

        // Si el vuelo ya no está en ningún otro día, eliminarlo del catálogo
        $stmt = $this->db->getConnection()->prepare(
            'SELECT COUNT(*) FROM vuelos_dias WHERE codigo_vuelo_id = ?'
        );
        $stmt->execute([$codigoVueloId]);
        $enUso = (int) $stmt->fetchColumn();

        if ($enUso === 0) {
            $stmt = $this->db->getConnection()->prepare(
                'DELETE FROM codigos_vuelos WHERE id = ?'
            );
            $stmt->execute([$codigoVueloId]);
        }

        // Reordenar los vuelos restantes del día (evitar gaps: 1,3 → 1,2)
        $stmt = $this->db->getConnection()->prepare('
            SELECT id FROM vuelos_dias
            WHERE programa_dias_id = ?
            ORDER BY orden ASC
        ');
        $stmt->execute([$programaDiasId]);
        $restantes = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $stmtUpdate = $this->db->getConnection()->prepare(
            'UPDATE vuelos_dias SET orden = ? WHERE id = ?'
        );
        foreach ($restantes as $nuevoOrden => $id) {
            $stmtUpdate->execute([$nuevoOrden + 1, $id]);
        }

        return [
            'success' => true,
            'message' => 'Vuelo eliminado correctamente.',
        ];
    }

    // ----------------------------------------------------------------
    // SECCIÓN 4: Respuesta de error estandarizada
    // ----------------------------------------------------------------
    private function sendError(string $message): void
    {
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error'   => $message,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }


    private function obtenerFechaRealDia(int $programaDiasId): string
    {
        $dia = $this->db->fetch(
            "SELECT 
                pd.id,
                pd.solicitud_id,
                pd.dia_numero,
                pd.fecha_dia,
                pd.duracion_estancia,
                ps.fecha_llegada
            FROM programa_dias pd
            INNER JOIN programa_solicitudes ps ON ps.id = pd.solicitud_id
            WHERE pd.id = ?
            AND ps.agencia_id = ?
            LIMIT 1",
            [$programaDiasId, $_SESSION['agencia_id']]
        );

        if (!$dia) {
            throw new Exception('Día no encontrado o sin permisos.');
        }

        if (!empty($dia['fecha_dia'])) {
            return $dia['fecha_dia'];
        }

        $dias = $this->db->fetchAll(
            "SELECT 
                id,
                dia_numero,
                fecha_dia,
                duracion_estancia
            FROM programa_dias
            WHERE solicitud_id = ?
            ORDER BY dia_numero ASC",
            [$dia['solicitud_id']]
        );

        $diasCalculados = FechaCalculator::calcularFechasDias($dias, $dia['fecha_llegada']);

        foreach ($diasCalculados as $diaCalculado) {
            if ((int)$diaCalculado['id'] === $programaDiasId) {
                if (empty($diaCalculado['fecha_calculada'])) {
                    throw new Exception('No se pudo calcular la fecha del día.');
                }

                return $diaCalculado['fecha_calculada'];
            }
        }

        throw new Exception('No se encontró la fecha calculada del día.');
    }
}

$api = new VuelosAPI();
$api->handleRequest();
