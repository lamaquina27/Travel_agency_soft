<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/AuditLogger.php';

class RoomingModel
{
    // service_type válidos
    const TYPE_AL_AEROPUERTO = 'llevada_al_aeropuerto';
    const TYPE_AL_HOTEL      = 'llevada_al_hotel';
    const TYPE_POR_ASIGNAR   = 'por_asignar';

    // status válidos (espejo del ENUM en BD)
    const STATUS_EN_PROCESO = 'en_proceso';
    const STATUS_COMPLETADO = 'completado';
    const STATUS_CANCELADO  = 'cancelado';

    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function create(array $data, ?int $userId = null): int
    {
        if (empty($data['agencia_id']) || empty($data['solicitud_id']) || empty($data['service_type'])) {
            throw new Exception('agencia_id, solicitud_id y service_type son obligatorios');
        }

        $this->db->query(
            "INSERT INTO rooming
                (agencia_id, solicitud_id, programa_dia_id, service_type, service_date, city,
                airport_code_origen, airport_code_destino, flight_code, arrival_time, departure_time,
                pickup_time, pickup_location, dropoff_location, hotel_id,
                guide_name, status, internal_notes, operator_notes)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['agencia_id'],
                $data['solicitud_id'],
                $data['programa_dia_id']     ?? null,
                $data['service_type'],
                $data['service_date']       ?? null,
                $data['city']               ?? null,
                $data['airport_code_origen']    ?? null,
                $data['airport_code_destino']   ?? null,
                $data['flight_code']        ?? null,
                $data['arrival_time']       ?? null,
                $data['departure_time']     ?? null,
                $data['pickup_time']        ?? null,
                $data['pickup_location']    ?? null,
                $data['dropoff_location']   ?? null,
                $data['hotel_id']           ?? null,
                $data['guide_name']         ?? null,
                $data['status']             ?? self::STATUS_EN_PROCESO,
                $data['internal_notes']     ?? null,
                $data['operator_notes']     ?? null,
            ]
        );
        $id = (int) $this->db->getConnection()->lastInsertId();

        $this->logTrace($id, 'created', $userId);

        return $id;
    }

    public function update(int $id, int $agenciaId, array $data, ?int $userId = null): bool
    {
        if (empty($data)) {
            throw new Exception('No hay datos para actualizar');
        }

        $campos  = [];
        $valores = [];

        $permitidos = [
            'service_type', 'service_date', 'city', 'airport_code_origen', 'airport_code_destino', 'flight_code',
            'arrival_time', 'departure_time', 'pickup_time', 'pickup_location',
            'dropoff_location', 'hotel_id', 'guide_name', 'status',
            'internal_notes', 'operator_notes'
        ];

        foreach ($permitidos as $campo) {
            if (array_key_exists($campo, $data)) {
                $campos[]  = "`$campo` = ?";
                $valores[] = $data[$campo];
            }
        }

        if (empty($campos)) {
            throw new Exception('Ningún campo válido para actualizar');
        }

        $valores[] = $id;
        $valores[] = $agenciaId;

        $result = $this->db->query(
            "UPDATE rooming SET " . implode(', ', $campos) . " WHERE id = ? AND agencia_id = ?",
            $valores
        );

        $this->logTrace($id, 'updated', $userId);

        return $result->rowCount() > 0;
    }

    public function delete(int $id, int $agenciaId, ?int $userId = null): bool
    {
        // Trazar ANTES de borrar: logTrace lee el rooming para resolver agencia_id.
        $this->logTrace($id, 'deleted', $userId);

        $result = $this->db->query(
            "DELETE FROM rooming WHERE id = ? AND agencia_id = ?",
            [$id, $agenciaId]
        );

        return $result->rowCount() > 0;
    }

    public function getById(int $id, int $agenciaId): ?array
    {
        $rooming = $this->db->fetch(
            "SELECT r.*, ba.nombre as hotel_nombre
            FROM rooming r
            LEFT JOIN biblioteca_alojamientos ba ON ba.id = r.hotel_id
            WHERE r.id = ? AND r.agencia_id = ?",
            [$id, $agenciaId]
        );

        return $rooming ?: null;
    }

    public function getBySolicitud(int $solicitudId, int $agenciaId): array
    {
        return $this->db->fetchAll(
            "SELECT r.*, ba.nombre as hotel_nombre
            FROM rooming r
            LEFT JOIN biblioteca_alojamientos ba ON ba.id = r.hotel_id
            WHERE r.solicitud_id = ? AND r.agencia_id = ?
            ORDER BY r.service_date ASC, r.arrival_time ASC",
            [$solicitudId, $agenciaId]
        );
    }

    public function getWithOperators(int $id, int $agenciaId): ?array
    {
        $rooming = $this->getById($id, $agenciaId);

        if (!$rooming) {
            return null;
        }

        $rooming['operadores'] = $this->db->fetchAll(
            "SELECT o.id as operador_id, u.full_name, u.email, ao.created_at as asignado_en
            FROM asignacion_operadores ao
            JOIN operadores o ON o.id = ao.operador_id
            JOIN users u ON u.id = o.usuario_id
            WHERE ao.rooming_id = ?",
            [$id]
        );

        return $rooming;
    }
    
    public function filter(array $filters): array
    {
        if (empty($filters['agencia_id'])) {
            throw new Exception('agencia_id es obligatorio para filtrar');
        }

        $where  = ["r.agencia_id = ?"];
        $params = [(int) $filters['agencia_id']];

        if (!empty($filters['solicitud_id'])) {
            $where[]  = "r.solicitud_id = ?";
            $params[] = (int) $filters['solicitud_id'];
        }

        if (!empty($filters['service_type'])) {
            $where[]  = "r.service_type = ?";
            $params[] = $filters['service_type'];
        }

        if (!empty($filters['status'])) {
            $where[]  = "r.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['hotel_id'])) {
            $where[]  = "r.hotel_id = ?";
            $params[] = (int) $filters['hotel_id'];
        }

        if (!empty($filters['city'])) {
            $where[]  = "r.city LIKE ?";
            $params[] = '%' . $filters['city'] . '%';
        }

        if (!empty($filters['service_date_from'])) {
            $where[]  = "r.service_date >= ?";
            $params[] = $filters['service_date_from'];
        }

        if (!empty($filters['service_date_to'])) {
            $where[]  = "r.service_date <= ?";
            $params[] = $filters['service_date_to'];
        }

        $sql = "SELECT r.*, ba.nombre as hotel_nombre
                FROM rooming r
                LEFT JOIN biblioteca_alojamientos ba ON ba.id = r.hotel_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY r.service_date ASC, r.created_at DESC";

        return $this->db->fetchAll($sql, $params);
    }

    public function assignOperator(int $roomingId, int $operadorId, ?int $userId = null): bool
    {
        $result = $this->db->query(
            "INSERT IGNORE INTO asignacion_operadores (rooming_id, operador_id)
            VALUES (?, ?)",
            [$roomingId, $operadorId]
        );

        $this->logTrace($roomingId, 'operator_assigned', $userId);

        return $result->rowCount() > 0;
    }

    public function removeOperator(int $roomingId, int $operadorId, ?int $userId = null): bool
    {
        $result = $this->db->query(
            "DELETE FROM asignacion_operadores
            WHERE rooming_id = ? AND operador_id = ?",
            [$roomingId, $operadorId]
        );

        $this->logTrace($roomingId, 'operator_removed', $userId);

        return $result->rowCount() > 0;
    }

    public function getOperators(int $roomingId): array
    {
        return $this->db->fetchAll(
            "SELECT o.id as operador_id, u.full_name, u.email, ao.created_at as asignado_en
            FROM asignacion_operadores ao
            JOIN operadores o ON o.id = ao.operador_id
            JOIN users u ON u.id = o.usuario_id
            WHERE ao.rooming_id = ?",
            [$roomingId]
        );
    }

    /**
     * Lista los operadores (pool) de una agencia, para el dropdown de asignación.
     */
    public function getOperadoresAgencia(int $agenciaId): array
    {
        return $this->db->fetchAll(
            "SELECT o.id AS operador_id, o.usuario_id, u.full_name, u.email
            FROM operadores o
            JOIN users u ON u.id = o.usuario_id
            WHERE o.agencia_id = ?
            ORDER BY u.full_name ASC",
            [$agenciaId]
        );
    }

    /**
     * Roomings asignados a un operador (identificado por su usuario_id).
     * Usado para el listado restringido del rol 'operador'.
     */
    public function getByOperador(int $usuarioId, int $agenciaId): array
    {
        return $this->db->fetchAll(
            "SELECT r.*, ba.nombre AS hotel_nombre
            FROM rooming r
            JOIN asignacion_operadores ao ON ao.rooming_id = r.id
            JOIN operadores o ON o.id = ao.operador_id
            LEFT JOIN biblioteca_alojamientos ba ON ba.id = r.hotel_id
            WHERE o.usuario_id = ? AND o.agencia_id = ? AND r.agencia_id = ?
            ORDER BY r.service_date ASC, r.created_at DESC",
            [$usuarioId, $agenciaId, $agenciaId]
        );
    }

    /**
     * ¿El rooming está asignado al operador (por usuario_id)?
     * Usado para validar permisos de edición del rol 'operador'.
     */
    public function estaAsignadoA(int $roomingId, int $usuarioId): bool
    {
        $row = $this->db->fetch(
            "SELECT 1
            FROM asignacion_operadores ao
            JOIN operadores o ON o.id = ao.operador_id
            WHERE ao.rooming_id = ? AND o.usuario_id = ?
            LIMIT 1",
            [$roomingId, $usuarioId]
        );
        return (bool) $row;
    }

    public function generateFromItinerary(int $solicitudId, int $agenciaId): array
    {
        // 1. Validar que la solicitud pertenezca a la agencia (multi-tenant)
        $solicitud = $this->db->fetch(
            "SELECT id FROM programa_solicitudes WHERE id = ? AND agencia_id = ?",
            [$solicitudId, $agenciaId]
        );
        if (!$solicitud) {
            throw new Exception('La solicitud no existe o no pertenece a la agencia');
        }

        // 2. Rango de días del itinerario: define el primer y el último día,
        //    para clasificar cada vuelo como llegada / salida / intermedio.
        $rango = $this->db->fetch(
            "SELECT MIN(dia_numero) AS primer_dia, MAX(dia_numero) AS ultimo_dia
             FROM programa_dias WHERE solicitud_id = ?",
            [$solicitudId]
        );
        $primerDia = ($rango && $rango['primer_dia'] !== null) ? (int) $rango['primer_dia'] : 0;
        $ultimoDia = ($rango && $rango['ultimo_dia'] !== null) ? (int) $rango['ultimo_dia'] : 0;

        // 3. Vuelos del itinerario (datos del vuelo + día al que pertenecen)
        $vuelos = $this->db->fetchAll(
            "SELECT cv.codigo_vuelo,
                    cv.codigo_aeropuerto_origen, cv.codigo_aeropuerto_destino,
                    cv.aeropuerto_origen, cv.aeropuerto_destino,
                    cv.ciudad_origen, cv.ciudad_destino,
                    cv.hora_salida, cv.hora_llegada,
                    d.id AS programa_dia_id, d.dia_numero, d.fecha_dia
             FROM vuelos_dias vd
             JOIN codigos_vuelos cv ON cv.id = vd.codigo_vuelo_id
             JOIN programa_dias   d ON d.id = vd.programa_dias_id
             WHERE d.solicitud_id = ?
             ORDER BY d.dia_numero ASC, vd.orden ASC",
            [$solicitudId]
        );

        // 4. Por cada vuelo, construir los roomings que correspondan según su día.
        $porCrear = [];
        foreach ($vuelos as $v) {
            $dia       = (int) $v['dia_numero'];
            $esLlegada = ($dia === $primerDia);
            $esSalida  = ($dia === $ultimoDia);

            if ($esLlegada && !$esSalida) {
                // Primer día -> recogen en aeropuerto y llevan al hotel
                $porCrear[] = $this->buildLlevadaAlHotel($v, $agenciaId, $solicitudId);
            } elseif ($esSalida && !$esLlegada) {
                // Último día -> recogen en hotel y llevan al aeropuerto
                $porCrear[] = $this->buildLlevadaAlAeropuerto($v, $agenciaId, $solicitudId);
            } elseif (!$esLlegada && !$esSalida) {
                // Día intermedio -> llevar al aeropuerto en origen Y recoger en destino
                $porCrear[] = $this->buildLlevadaAlAeropuerto($v, $agenciaId, $solicitudId);
                $porCrear[] = $this->buildLlevadaAlHotel($v, $agenciaId, $solicitudId);
            } else {
                // Itinerario de un solo día con vuelo (primer día == último día):
                // lo tratamos como llegada.
                $porCrear[] = $this->buildLlevadaAlHotel($v, $agenciaId, $solicitudId);
            }
        }

        // 5. Crear sin duplicar. Clave: solicitud + tipo + vuelo + fecha.
        $idsCreados = [];
        foreach ($porCrear as $data) {
            $existe = $this->db->fetch(
                "SELECT id FROM rooming
                 WHERE solicitud_id = ? AND service_type = ?
                   AND programa_dia_id <=> ? AND flight_code <=> ?",
                [$solicitudId, $data['service_type'], $data['programa_dia_id'], $data['flight_code']]
            );
            if ($existe) {
                continue; // ya existe: lo dejamos intacto
            }
            $idsCreados[] = $this->create($data);
        }

        // Sincronizar la cantidad de pasajeros en los roomings del programa
        $this->sincronizarPasajeros($solicitudId, $agenciaId);

        return $idsCreados;
    }

    /**
     * Genera el Rooming List de un programa SOLO si está vendido (comprado=1)
     * y aún no se ha generado (rooming_generado=0). Marca la bandera al generar.
     * Garantiza "una sola vez por programa confirmado".
     *
     * @return array ['generado'=>bool, 'motivo'=>string, 'ids'=>int[]]
     */
    public function generarSiVendido(int $solicitudId, int $agenciaId): array
    {
        $prog = $this->db->fetch(
            "SELECT comprado, rooming_generado FROM programa_solicitudes WHERE id = ? AND agencia_id = ?",
            [$solicitudId, $agenciaId]
        );

        if (!$prog) {
            return ['generado' => false, 'motivo' => 'no_encontrado', 'ids' => []];
        }
        if ((int) $prog['comprado'] !== 1) {
            return ['generado' => false, 'motivo' => 'no_vendido', 'ids' => []];
        }
        if ((int) $prog['rooming_generado'] === 1) {
            return ['generado' => false, 'motivo' => 'ya_generado', 'ids' => []];
        }

        $ids = $this->generateFromItinerary($solicitudId, $agenciaId);

        // Marcar como generado si el programa YA tiene roomings (recién creados o
        // existentes). Así un programa vendido sin vuelos aún no se marca (podrá
        // generarse cuando los tenga), y un re-vendido sin cambios no reintenta.
        $tiene = $this->db->fetch(
            "SELECT COUNT(*) AS c FROM rooming WHERE solicitud_id = ? AND agencia_id = ?",
            [$solicitudId, $agenciaId]
        );
        $tieneRoomings = ((int) ($tiene['c'] ?? 0)) > 0;

        if ($tieneRoomings) {
            $this->db->query(
                "UPDATE programa_solicitudes SET rooming_generado = 1 WHERE id = ? AND agencia_id = ?",
                [$solicitudId, $agenciaId]
            );
        }

        return [
            'generado' => !empty($ids),
            'motivo'   => !empty($ids) ? 'ok' : ($tieneRoomings ? 'ya_existian' : 'sin_vuelos'),
            'ids'      => $ids,
        ];
    }

    /**
     * Resetea la bandera de generación (al desmarcar el programa como vendido).
     * Los roomings ya creados NO se borran; solo se habilita regenerar si vuelve
     * a venderse (la idempotencia evita duplicar lo existente).
     */
    public function resetRoomingGenerado(int $solicitudId, int $agenciaId): void
    {
        $this->db->query(
            "UPDATE programa_solicitudes SET rooming_generado = 0 WHERE id = ? AND agencia_id = ?",
            [$solicitudId, $agenciaId]
        );
    }

    /**
     * Sincroniza la cantidad de pasajeros (tomada SIEMPRE de viajeros_solicitud)
     * en TODOS los roomings de un programa. Se llama al generar y cada vez que
     * cambian los pasajeros del itinerario. Devuelve la cantidad aplicada.
     */
    public function sincronizarPasajeros(int $solicitudId, int $agenciaId): int
    {
        $row = $this->db->fetch(
            "SELECT COUNT(*) AS c FROM viajeros_solicitud WHERE solicitud_id = ?",
            [$solicitudId]
        );
        $cantidad = (int) ($row['c'] ?? 0);

        $this->db->query(
            "UPDATE rooming SET cantidad_pasajeros = ? WHERE solicitud_id = ? AND agencia_id = ?",
            [$cantidad, $solicitudId, $agenciaId]
        );

        return $cantidad;
    }

    /**
     * Datos de un rooming "llevada al hotel" (recogida en aeropuerto -> hotel).
     */
    private function buildLlevadaAlHotel(array $v, int $agenciaId, int $solicitudId): array
    {
        $hotel = $this->alojamientoDelDia((int) $v['programa_dia_id']);

        return [
            'agencia_id'           => $agenciaId,
            'solicitud_id'         => $solicitudId,
            'service_type'         => self::TYPE_AL_HOTEL,
            'programa_dia_id'      => (int) $v['programa_dia_id'],
            'service_date'         => $v['fecha_dia'] ?: null,
            'city'                 => $v['ciudad_destino'],
            'airport_code_origen'  => $v['codigo_aeropuerto_origen'],
            'airport_code_destino' => $v['codigo_aeropuerto_destino'],
            'flight_code'          => $v['codigo_vuelo'],
            'arrival_time'         => $v['hora_llegada'],
            'departure_time'       => $v['hora_salida'],
            'pickup_location'      => $v['aeropuerto_destino'],   // aeropuerto donde aterriza
            'dropoff_location'     => $hotel['nombre'] ?? null,   // hotel destino
            'hotel_id'             => $hotel['id'] ?? null,
            'status'               => self::STATUS_EN_PROCESO,
        ];
    }

    /**
     * Datos de un rooming "llevada al aeropuerto" (hotel -> aeropuerto).
     */
    private function buildLlevadaAlAeropuerto(array $v, int $agenciaId, int $solicitudId): array
    {
        $hotel = $this->alojamientoDelDia((int) $v['programa_dia_id']);

        return [
            'agencia_id'           => $agenciaId,
            'solicitud_id'         => $solicitudId,
            'service_type'         => self::TYPE_AL_AEROPUERTO,
            'programa_dia_id'      => (int) $v['programa_dia_id'],
            'service_date'         => $v['fecha_dia'] ?: null,
            'city'                 => $v['ciudad_origen'],
            'airport_code_origen'  => $v['codigo_aeropuerto_origen'],
            'airport_code_destino' => $v['codigo_aeropuerto_destino'],
            'flight_code'          => $v['codigo_vuelo'],
            'arrival_time'         => $v['hora_llegada'],
            'departure_time'       => $v['hora_salida'],
            'pickup_location'      => $hotel['nombre'] ?? null,   // hotel de salida
            'dropoff_location'     => $v['aeropuerto_origen'],    // aeropuerto de donde despega
            'hotel_id'             => $hotel['id'] ?? null,
            'status'               => self::STATUS_EN_PROCESO,
        ];
    }

    /**
     * Alojamiento principal (id + nombre) de un día del itinerario, o null.
     */
    private function alojamientoDelDia(int $programaDiaId): ?array
    {
        $row = $this->db->fetch(
            "SELECT ba.id, ba.nombre
             FROM programa_dias_servicios s
             JOIN biblioteca_alojamientos ba ON ba.id = s.biblioteca_item_id
             WHERE s.programa_dia_id = ?
               AND s.tipo_servicio = 'alojamiento'
               AND s.es_alternativa = 0
             ORDER BY s.orden ASC
             LIMIT 1",
            [$programaDiaId]
        );
        return $row ?: null;
    }

    private function logTrace(int $roomingId, string $action, ?int $userId): void
    {
        // Sin usuario no hay a quién atribuir la acción: no trazamos.
        if (!$userId) {
            return;
        }

        // El agencia_id se resuelve desde el propio rooming, así la firma es
        // uniforme para todos los métodos (incluidos assign/removeOperator).
        $row = $this->db->fetch(
            "SELECT agencia_id FROM rooming WHERE id = ?",
            [$roomingId]
        );
        if (!$row) {
            return;
        }

        AuditLogger::log(
            (int) $row['agencia_id'],
            'rooming_' . $action,
            "Rooming #{$roomingId}: {$action}",
            $userId
        );
    }

}
