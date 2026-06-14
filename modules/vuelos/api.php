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

        // Leer php://input solo UNA vez
        $rawInput = file_get_contents('php://input');
        $input    = json_decode($rawInput, true) ?? [];
        $action   = $_GET['action'] ?? $_POST['action'] ?? $input['action'] ?? '';

        error_log('===== VuelosAPI handleRequest START =====');
        error_log('Raw input: ' . $rawInput);
        error_log('Decoded input: ' . json_encode($input));
        error_log('Action: ' . $action);
        error_log('REQUEST METHOD: ' . $_SERVER['REQUEST_METHOD']);
        error_log('CONTENT_TYPE: ' . ($_SERVER['CONTENT_TYPE'] ?? 'NOT SET'));

        try {
            if (empty($action)) {
                throw new Exception('No action specified');
            }

            switch ($action) {
                case 'preview':
                    $codigo_vuelo = $input['codigo_vuelo'] ?? '';
                    $programa_dias_id = $input['programa_dias_id'] ?? null;
                    error_log("VuelosAPI - Preview: codigo_vuelo='$codigo_vuelo', programa_dias_id='$programa_dias_id'");
                    $result = $this->previewVuelo(
                        $codigo_vuelo,
                        $programa_dias_id
                    );
                    break;

                case 'save':
                    $codigo_vuelo = $input['codigo_vuelo'] ?? '';
                    $programa_dias_id = $input['programa_dias_id'] ?? null;
                    error_log("VuelosAPI - Save: codigo_vuelo='$codigo_vuelo', programa_dias_id='$programa_dias_id'");
                    $result = $this->saveVuelo(
                        $codigo_vuelo,
                        $programa_dias_id
                    );
                    break;

                case 'get':
                    $programa_dias_id = (int)($input['programa_dias_id'] ?? $_GET['programa_dias_id'] ?? 0);
                    error_log("VuelosAPI - Get: programa_dias_id='$programa_dias_id'");
                    $result = $this->getVuelosDia($programa_dias_id);
                    break;

                case 'delete':
                    $vuelo_dia_id = (int)($input['vuelo_dia_id'] ?? 0);
                    error_log("VuelosAPI - Delete: vuelo_dia_id='$vuelo_dia_id'");
                    $result = $this->deleteVuelo($vuelo_dia_id);
                    break;

                default:
                    throw new Exception("Acción no reconocida: {$action}");
            }

            error_log('Result: ' . json_encode($result));
            echo json_encode($result, JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            error_log('Exception caught: ' . $e->getMessage());
            $this->sendError($e->getMessage());
        }
    }

    // ----------------------------------------------------------------
    // SECCIÓN 2: Preview — consulta sin escribir en base de datos
    // ----------------------------------------------------------------
    private function previewVuelo($codigoVuelo, $programaDiasId): array
    {
        $codigoVuelo = trim((string)$codigoVuelo);
        $programaDiasId = $programaDiasId ? (int)$programaDiasId : null;

        if (empty($codigoVuelo)) {
            throw new Exception('El código de vuelo es requerido.');
        }

        if (!$programaDiasId) {
            error_log("VuelosAPI - Error: programa_dias_id es null/0/false. Recibido: " . var_export($programaDiasId, true));
            throw new Exception('El ID del día de programa es requerido. Valor recibido: ' . var_export($programaDiasId, true));
        }

        error_log("DEBUG: previewVuelo - Obteniendo fecha del día $programaDiasId");
        
        try {
            $fechaDia = $this->obtenerFechaRealDia((int)$programaDiasId);
            error_log("DEBUG: previewVuelo - Fecha obtenida: $fechaDia");
        } catch (Exception $e) {
            error_log("ERROR en obtenerFechaRealDia: " . $e->getMessage());
            throw $e;
        }

        error_log("DEBUG: previewVuelo - Buscando vuelo $codigoVuelo para fecha $fechaDia");
        
        try {
            $vuelo = AeroDataBox::fetchFlight($codigoVuelo, $fechaDia);
            error_log("DEBUG: previewVuelo - Resultado de AeroDataBox: " . json_encode($vuelo));
        } catch (Exception $e) {
            error_log("ERROR en AeroDataBox::fetchFlight: " . $e->getMessage());
            throw $e; // Re-lanzar la excepción con el mensaje de AeroDataBox
        }

        if (!$vuelo) {
            error_log("ERROR: No se encontró el vuelo $codigoVuelo para fecha $fechaDia");
            throw new Exception(
                "No se encontró el vuelo {$codigoVuelo} para la fecha {$fechaDia}."
            );
        }

        error_log("DEBUG: previewVuelo - Retornando resultado exitoso");
        return [
            'success'   => true,
            'fecha_dia' => $fechaDia,
            'vuelo'     => $vuelo,
        ];
    }

    // ----------------------------------------------------------------
    // SECCIÓN 3: Save — escribe en codigos_vuelos y vuelos_dias
    // ----------------------------------------------------------------
    private function saveVuelo($codigoVuelo, $programaDiasId): array
    {
        $codigoVuelo = trim((string)$codigoVuelo);
        $programaDiasId = $programaDiasId ? (int)$programaDiasId : null;

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
        error_log("DEBUG: obtenerFechaRealDia called with programaDiasId=$programaDiasId");
        error_log("DEBUG: SESSION agencia_id=" . ($_SESSION['agencia_id'] ?? 'NULL'));
        
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

        error_log("DEBUG: First query result: " . json_encode($dia));

        if (!$dia) {
            error_log("ERROR: Día no encontrado o sin permisos");
            throw new Exception('Día no encontrado o sin permisos.');
        }

        if (!empty($dia['fecha_dia'])) {
            error_log("DEBUG: Usando fecha_dia del registro: " . $dia['fecha_dia']);
            return $dia['fecha_dia'];
        }

        error_log("DEBUG: fecha_dia está vacía, calculando...");
        
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

        error_log("DEBUG: Días del programa: " . count($dias));
        error_log("DEBUG: fecha_llegada para cálculo: " . ($dia['fecha_llegada'] ?? 'NULL'));

        $diasCalculados = FechaCalculator::calcularFechasDias($dias, $dia['fecha_llegada']);

        error_log("DEBUG: Días calculados: " . json_encode($diasCalculados));

        foreach ($diasCalculados as $diaCalculado) {
            if ((int)$diaCalculado['id'] === $programaDiasId) {
                if (empty($diaCalculado['fecha_calculada'])) {
                    error_log("ERROR: No se pudo calcular la fecha del día");
                    throw new Exception('No se pudo calcular la fecha del día.');
                }
                error_log("DEBUG: Fecha calculada encontrada: " . $diaCalculado['fecha_calculada']);
                return $diaCalculado['fecha_calculada'];
            }
        }

        error_log("ERROR: No se encontró el día en la lista calculada");
        throw new Exception('No se pudo encontrar el día en los cálculos.');
    }
}

$api = new VuelosAPI();
$api->handleRequest();
