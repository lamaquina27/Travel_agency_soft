<?php
// ====================================================================
// ARCHIVO: modules/rooming/api.php
// API JSON del módulo Rooming (logística de traslados de aeropuerto).
// Respeta agencia (multi-tenant) y rol (admin/agent gestionan;
// operador solo ve/opera lo asignado a él).
// ====================================================================

ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/classes/RoomingModel.php';

App::init();
App::requireLogin();

class RoomingAPI
{
    private $db;
    private $model;
    private $agenciaId;
    private $userId;
    private $role;

    public function __construct()
    {
        $this->db        = Database::getInstance();
        $this->model     = new RoomingModel();
        $this->agenciaId = $_SESSION['agencia_id'] ?? null;
        $this->userId    = $_SESSION['user_id'] ?? null;
        $this->role      = $_SESSION['user_role'] ?? null;
    }

    public function handleRequest()
    {
        // Fusionar cuerpo JSON (si lo hay) con $_POST
        $input = json_decode(file_get_contents('php://input'), true);
        if (is_array($input)) {
            $_POST = array_merge($_POST, $input);
        }

        $action = $_POST['action'] ?? $_GET['action'] ?? '';

        try {
            if (!$this->agenciaId) {
                throw new Exception('Usuario sin agencia asignada');
            }

            // La exportación maneja sus propios headers (descarga CSV), no JSON.
            if ($action === 'exportar') {
                $this->requireGestor();
                $this->exportarCsv();
                return;
            }

            switch ($action) {
                case 'listar':                  $result = $this->listar();              break;
                case 'detalle':                 $result = $this->detalle();             break;
                case 'get_operadores':          $result = $this->getOperadores();       break;
                case 'get_programas_vendidos':  $result = $this->getProgramasVendidos(); break;
                case 'asignar_operador':        $result = $this->asignarOperador();     break;
                case 'quitar_operador':         $result = $this->quitarOperador();      break;
                case 'bulk_asignar_operador':   $result = $this->bulkAsignarOperador(); break;
                case 'bulk_update':             $result = $this->bulkUpdate();          break;
                case 'get_reglas':              $result = $this->getReglas();           break;
                case 'crear_regla':             $result = $this->crearRegla();          break;
                case 'update_regla':            $result = $this->updateRegla();         break;
                case 'toggle_regla':            $result = $this->toggleRegla();         break;
                case 'eliminar_regla':          $result = $this->eliminarRegla();       break;
                case 'reordenar_reglas':        $result = $this->reordenarReglas();     break;
                case 'aplicar_reglas':          $result = $this->aplicarReglas();       break;
                case 'actualizar_estado':       $result = $this->actualizarEstado();    break;
                case 'actualizar':              $result = $this->actualizar();          break;
                case 'crear':                   $result = $this->crear();               break;
                case 'eliminar':                $result = $this->eliminar();            break;
                case 'generar':                 $result = $this->generar();             break;
                default:
                    throw new Exception('Acción no válida: ' . $action);
            }

            ob_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($result, JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            $this->sendError($e->getMessage());
        }
    }

    // =========================================================
    // Permisos
    // =========================================================

    private function esOperador(): bool
    {
        return $this->role === 'operador';
    }

    /** Lanza 403 si el usuario es operador (acción solo para gestores). */
    private function requireGestor(): void
    {
        if ($this->esOperador()) {
            http_response_code(403);
            throw new Exception('No tienes permisos para esta acción');
        }
    }

    /** Operador: solo puede tocar roomings asignados a él. */
    private function requirePuedeEditar(int $roomingId): void
    {
        if ($this->esOperador() && !$this->model->estaAsignadoA($roomingId, (int) $this->userId)) {
            http_response_code(403);
            throw new Exception('Este servicio no está asignado a ti');
        }
    }

    // =========================================================
    // Listado y detalle
    // =========================================================

    private function listar(): array
    {
        // El operador solo ve los suyos
        if ($this->esOperador()) {
            $data = $this->model->getByOperador((int) $this->userId, (int) $this->agenciaId);
            return ['success' => true, 'data' => $data];
        }

        // admin/agent: auto-generar los traslados de programas vendidos que aún
        // estén pendientes (self-heal), para que el listado siempre esté completo
        // sin requerir acción manual. Es idempotente y no debe romper el listado.
        try {
            $this->model->generarPendientes((int) $this->agenciaId);
        } catch (Exception $e) {
            error_log('Rooming auto-generar pendientes: ' . $e->getMessage());
        }

        // admin/agent: lista con filtros opcionales
        $filtros = ['agencia_id' => (int) $this->agenciaId];
        foreach (['solicitud_id', 'service_type', 'status', 'hotel_id', 'city', 'service_date_from', 'service_date_to'] as $f) {
            $val = $_POST[$f] ?? $_GET[$f] ?? null;
            if ($val !== null && $val !== '') {
                $filtros[$f] = $val;
            }
        }

        return ['success' => true, 'data' => $this->model->filter($filtros)];
    }

    private function detalle(): array
    {
        $id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
        if (!$id) {
            throw new Exception('ID de rooming requerido');
        }

        if ($this->esOperador()) {
            $this->requirePuedeEditar($id);
        }

        $rooming = $this->model->getWithOperators($id, (int) $this->agenciaId);
        if (!$rooming) {
            throw new Exception('Servicio no encontrado');
        }

        return ['success' => true, 'data' => $rooming];
    }

    // =========================================================
    // Catálogos de apoyo (solo gestores)
    // =========================================================

    private function getOperadores(): array
    {
        $this->requireGestor();
        return ['success' => true, 'data' => $this->model->getOperadoresAgencia((int) $this->agenciaId)];
    }

    private function getProgramasVendidos(): array
    {
        $this->requireGestor();
        $programas = $this->db->fetchAll(
            "SELECT id, nombre, destino, fecha_llegada
             FROM programa_solicitudes
             WHERE agencia_id = ? AND comprado = 1
             ORDER BY fecha_llegada DESC, id DESC",
            [(int) $this->agenciaId]
        );
        return ['success' => true, 'data' => $programas];
    }

    // =========================================================
    // Operadores sobre un rooming (solo gestores)
    // =========================================================

    private function asignarOperador(): array
    {
        $this->requireGestor();
        $roomingId  = (int) ($_POST['rooming_id'] ?? 0);
        $operadorId = (int) ($_POST['operador_id'] ?? 0);
        if (!$roomingId || !$operadorId) {
            throw new Exception('rooming_id y operador_id son requeridos');
        }

        // El rooming debe ser de la agencia
        if (!$this->model->getById($roomingId, (int) $this->agenciaId)) {
            throw new Exception('Servicio no encontrado');
        }
        // El operador debe pertenecer a la agencia
        $op = $this->db->fetch(
            "SELECT id FROM operadores WHERE id = ? AND agencia_id = ?",
            [$operadorId, (int) $this->agenciaId]
        );
        if (!$op) {
            throw new Exception('Operador no válido para esta agencia');
        }

        $ok = $this->model->assignOperator($roomingId, $operadorId, (int) $this->userId);
        return ['success' => true, 'asignado' => $ok];
    }

    private function quitarOperador(): array
    {
        $this->requireGestor();
        $roomingId  = (int) ($_POST['rooming_id'] ?? 0);
        $operadorId = (int) ($_POST['operador_id'] ?? 0);
        if (!$roomingId || !$operadorId) {
            throw new Exception('rooming_id y operador_id son requeridos');
        }
        if (!$this->model->getById($roomingId, (int) $this->agenciaId)) {
            throw new Exception('Servicio no encontrado');
        }

        $ok = $this->model->removeOperator($roomingId, $operadorId, (int) $this->userId);
        return ['success' => true, 'eliminado' => $ok];
    }

    // =========================================================
    // Acciones en lote (solo gestores)
    // =========================================================

    /** Asigna un operador a varios traslados a la vez. */
    private function bulkAsignarOperador(): array
    {
        $this->requireGestor();
        $ids        = $_POST['ids'] ?? [];
        $operadorId = (int) ($_POST['operador_id'] ?? 0);
        if (!is_array($ids) || !$ids) {
            throw new Exception('Selecciona al menos un traslado');
        }
        if (!$operadorId) {
            throw new Exception('operador_id requerido');
        }
        // El operador debe pertenecer a la agencia
        $op = $this->db->fetch(
            "SELECT id FROM operadores WHERE id = ? AND agencia_id = ?",
            [$operadorId, (int) $this->agenciaId]
        );
        if (!$op) {
            throw new Exception('Operador no válido para esta agencia');
        }

        $count = 0;
        foreach ($ids as $rid) {
            $rid = (int) $rid;
            if (!$rid || !$this->model->getById($rid, (int) $this->agenciaId)) {
                continue; // ignora ids inválidos o de otra agencia
            }
            $this->model->assignOperator($rid, $operadorId, (int) $this->userId);
            $count++;
        }
        return ['success' => true, 'afectados' => $count];
    }

    /** Aplica campos (hora de recogida y/o estado) a varios traslados a la vez. */
    private function bulkUpdate(): array
    {
        $this->requireGestor();
        $ids = $_POST['ids'] ?? [];
        if (!is_array($ids) || !$ids) {
            throw new Exception('Selecciona al menos un traslado');
        }

        $data = [];
        foreach (['pickup_time', 'status'] as $c) {
            if (array_key_exists($c, $_POST) && $_POST[$c] !== '') {
                $data[$c] = $_POST[$c];
            }
        }
        if (empty($data)) {
            throw new Exception('No hay cambios para aplicar');
        }
        if (isset($data['status'])) {
            $validos = [RoomingModel::STATUS_EN_PROCESO, RoomingModel::STATUS_COMPLETADO, RoomingModel::STATUS_CANCELADO];
            if (!in_array($data['status'], $validos, true)) {
                throw new Exception('Estado no válido');
            }
        }

        $count = 0;
        foreach ($ids as $rid) {
            $rid = (int) $rid;
            if (!$rid) {
                continue;
            }
            try {
                $this->model->update($rid, (int) $this->agenciaId, $data, (int) $this->userId);
                $count++;
            } catch (Exception $e) {
                // ignora fallos puntuales para no romper el lote
            }
        }
        return ['success' => true, 'afectados' => $count];
    }

    // =========================================================
    // Actualización
    // =========================================================

    private function actualizarEstado(): array
    {
        $id     = (int) ($_POST['id'] ?? 0);
        $estado = $_POST['status'] ?? '';
        if (!$id) {
            throw new Exception('ID de rooming requerido');
        }

        $validos = [RoomingModel::STATUS_EN_PROCESO, RoomingModel::STATUS_COMPLETADO, RoomingModel::STATUS_CANCELADO];
        if (!in_array($estado, $validos, true)) {
            throw new Exception('Estado no válido');
        }

        $this->requirePuedeEditar($id);

        $ok = $this->model->update($id, (int) $this->agenciaId, ['status' => $estado], (int) $this->userId);
        return ['success' => true, 'actualizado' => $ok];
    }

    private function actualizar(): array
    {
        $id = (int) ($_POST['id'] ?? 0);
        if (!$id) {
            throw new Exception('ID de rooming requerido');
        }

        $this->requirePuedeEditar($id);

        // Solo campos editables (el modelo además aplica su propia whitelist)
        $campos = [
            'service_type', 'service_date', 'city', 'airport_code_origen', 'airport_code_destino',
            'flight_code', 'arrival_time', 'departure_time', 'pickup_time', 'pickup_location',
            'dropoff_location', 'hotel_id', 'guide_name', 'status', 'internal_notes', 'operator_notes'
        ];
        // El operador SOLO puede tocar estado + sus notas (la restricción no puede vivir solo en el front)
        if ($this->esOperador()) {
            $campos = ['status', 'operator_notes'];
        }
        $data = [];
        foreach ($campos as $c) {
            if (array_key_exists($c, $_POST)) {
                $data[$c] = $_POST[$c] === '' ? null : $_POST[$c];
            }
        }
        if (empty($data)) {
            throw new Exception('No hay campos para actualizar');
        }

        $ok = $this->model->update($id, (int) $this->agenciaId, $data, (int) $this->userId);
        return ['success' => true, 'actualizado' => $ok];
    }

    // =========================================================
    // Crear / eliminar / generar (solo gestores)
    // =========================================================

    private function crear(): array
    {
        $this->requireGestor();

        $solicitudId = (int) ($_POST['solicitud_id'] ?? 0);
        if (!$solicitudId) {
            throw new Exception('solicitud_id es requerido');
        }
        // La solicitud debe ser de la agencia
        if (!$this->db->fetch("SELECT id FROM programa_solicitudes WHERE id = ? AND agencia_id = ?", [$solicitudId, (int) $this->agenciaId])) {
            throw new Exception('Programa no encontrado');
        }

        $data = ['agencia_id' => (int) $this->agenciaId, 'solicitud_id' => $solicitudId];
        $data['service_type'] = $_POST['service_type'] ?? RoomingModel::TYPE_POR_ASIGNAR;

        $opcionales = [
            'programa_dia_id', 'service_date', 'city', 'airport_code_origen', 'airport_code_destino',
            'flight_code', 'arrival_time', 'departure_time', 'pickup_time', 'pickup_location',
            'dropoff_location', 'hotel_id', 'guide_name', 'status', 'internal_notes', 'operator_notes'
        ];
        foreach ($opcionales as $c) {
            if (array_key_exists($c, $_POST) && $_POST[$c] !== '') {
                $data[$c] = $_POST[$c];
            }
        }

        $id = $this->model->create($data, (int) $this->userId);
        $this->model->sincronizarPasajeros($solicitudId, (int) $this->agenciaId);
        return ['success' => true, 'id' => $id];
    }

    private function eliminar(): array
    {
        $this->requireGestor();
        $id = (int) ($_POST['id'] ?? 0);
        if (!$id) {
            throw new Exception('ID de rooming requerido');
        }

        $ok = $this->model->delete($id, (int) $this->agenciaId, (int) $this->userId);
        return ['success' => true, 'eliminado' => $ok];
    }

    private function generar(): array
    {
        $this->requireGestor();
        $solicitudId = (int) ($_POST['solicitud_id'] ?? 0);
        if (!$solicitudId) {
            throw new Exception('solicitud_id es requerido');
        }

        // Genera solo si está vendido y aún no se generó (gateado por la bandera)
        $res = $this->model->generarSiVendido($solicitudId, (int) $this->agenciaId);

        if ($res['motivo'] === 'no_encontrado') {
            throw new Exception('El programa no existe o no es de tu agencia');
        }
        if ($res['motivo'] === 'no_vendido') {
            throw new Exception('El programa no está marcado como vendido');
        }

        return [
            'success'   => true,
            'generado'  => $res['generado'],
            'motivo'    => $res['motivo'],   // ok / ya_generado / sin_vuelos
            'generados' => count($res['ids']),
            'ids'       => $res['ids'],
        ];
    }

    // =========================================================
    // Exportar CSV (solo gestores)
    // =========================================================

    private function exportarCsv(): void
    {
        $filtros = ['agencia_id' => (int) $this->agenciaId];
        foreach (['solicitud_id', 'service_type', 'status', 'hotel_id', 'city', 'service_date_from', 'service_date_to'] as $f) {
            $val = $_POST[$f] ?? $_GET[$f] ?? null;
            if ($val !== null && $val !== '') {
                $filtros[$f] = $val;
            }
        }
        $rows = $this->model->filter($filtros);

        ob_clean();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="rooming_' . date('Ymd_His') . '.csv"');

        $out = fopen('php://output', 'w');
        // BOM para que Excel respete UTF-8
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, [
            'ID', 'Fecha', 'Tipo', 'Ciudad', 'Pasajeros', 'Vuelo', 'Aeropuerto origen', 'Aeropuerto destino',
            'Llegada', 'Salida', 'Recogida', 'Destino', 'Hotel', 'Estado'
        ]);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['id'], $r['service_date'], $r['service_type'], $r['city'], $r['cantidad_pasajeros'], $r['flight_code'],
                $r['airport_code_origen'], $r['airport_code_destino'], $r['arrival_time'], $r['departure_time'],
                $r['pickup_location'], $r['dropoff_location'], $r['hotel_nombre'] ?? '', $r['status']
            ]);
        }
        fclose($out);
        exit;
    }

    // =========================================================
    // Reglas de asignación automática (solo gestores)
    // =========================================================

    private function getReglas(): array
    {
        $this->requireGestor();
        return ['success' => true, 'data' => $this->model->getReglas((int) $this->agenciaId)];
    }

    private function crearRegla(): array
    {
        $this->requireGestor();
        $operadorId = (int) ($_POST['operador_id'] ?? 0);
        $airport    = trim($_POST['airport_code'] ?? '');
        $type       = trim($_POST['service_type'] ?? '');
        $city       = trim($_POST['city'] ?? '');

        if (!$operadorId) {
            throw new Exception('Selecciona el operador de la regla');
        }
        if ($airport === '' && $type === '' && $city === '') {
            throw new Exception('Define al menos una condición (aeropuerto, tipo o ciudad)');
        }
        if ($type !== '' && !in_array($type, [RoomingModel::TYPE_AL_HOTEL, RoomingModel::TYPE_AL_AEROPUERTO], true)) {
            throw new Exception('Tipo no válido');
        }
        // El operador debe ser de la agencia
        $op = $this->db->fetch("SELECT id FROM operadores WHERE id = ? AND agencia_id = ?", [$operadorId, (int) $this->agenciaId]);
        if (!$op) {
            throw new Exception('Operador no válido para esta agencia');
        }

        $id = $this->model->createRegla((int) $this->agenciaId, $operadorId, strtoupper($airport) ?: null, $type ?: null, $city ?: null);
        return ['success' => true, 'id' => $id];
    }

    private function updateRegla(): array
    {
        $this->requireGestor();
        $id         = (int) ($_POST['id'] ?? 0);
        $operadorId = (int) ($_POST['operador_id'] ?? 0);
        $airport    = trim($_POST['airport_code'] ?? '');
        $type       = trim($_POST['service_type'] ?? '');
        $city       = trim($_POST['city'] ?? '');

        if (!$id) {
            throw new Exception('ID de regla requerido');
        }
        if (!$operadorId) {
            throw new Exception('Selecciona el operador de la regla');
        }
        if ($airport === '' && $type === '' && $city === '') {
            throw new Exception('Define al menos una condición (aeropuerto, tipo o ciudad)');
        }
        if ($type !== '' && !in_array($type, [RoomingModel::TYPE_AL_HOTEL, RoomingModel::TYPE_AL_AEROPUERTO], true)) {
            throw new Exception('Tipo no válido');
        }
        $op = $this->db->fetch("SELECT id FROM operadores WHERE id = ? AND agencia_id = ?", [$operadorId, (int) $this->agenciaId]);
        if (!$op) {
            throw new Exception('Operador no válido para esta agencia');
        }

        $this->model->updateRegla($id, (int) $this->agenciaId, $operadorId, strtoupper($airport) ?: null, $type ?: null, $city ?: null);
        return ['success' => true];
    }

    private function reordenarReglas(): array
    {
        $this->requireGestor();
        $orden = $_POST['orden'] ?? [];
        if (!is_array($orden) || !$orden) {
            throw new Exception('Orden inválido');
        }
        $this->model->reordenarReglas($orden, (int) $this->agenciaId);
        return ['success' => true];
    }

    private function toggleRegla(): array
    {
        $this->requireGestor();
        $id     = (int) ($_POST['id'] ?? 0);
        $activa = (int) ($_POST['activa'] ?? 0);
        if (!$id) {
            throw new Exception('ID de regla requerido');
        }
        $ok = $this->model->toggleRegla($id, (int) $this->agenciaId, $activa);
        return ['success' => true, 'actualizado' => $ok];
    }

    private function eliminarRegla(): array
    {
        $this->requireGestor();
        $id = (int) ($_POST['id'] ?? 0);
        if (!$id) {
            throw new Exception('ID de regla requerido');
        }
        $ok = $this->model->deleteRegla($id, (int) $this->agenciaId);
        return ['success' => true, 'eliminado' => $ok];
    }

    /** Aplica las reglas activas a una lista de traslados (los filtrados en la vista). */
    private function aplicarReglas(): array
    {
        $this->requireGestor();
        $ids = $_POST['ids'] ?? [];
        if (!is_array($ids) || !$ids) {
            throw new Exception('No hay traslados a los que aplicar las reglas');
        }
        $asignados = $this->model->aplicarReglasIds($ids, (int) $this->agenciaId, (int) $this->userId);
        return ['success' => true, 'asignados' => $asignados, 'evaluados' => count($ids)];
    }

    // =========================================================
    private function sendError($message): void
    {
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

$api = new RoomingAPI();
$api->handleRequest();
