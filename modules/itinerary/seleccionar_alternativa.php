<?php
// =====================================================================
// modules/itinerary/seleccionar_alternativa.php
// Guarda la elección de hotel del CLIENTE desde el link público compartido.
// Autorizado por el public_token del programa; bloqueado si ya está vendido.
// Body JSON: { programa_id, token, grupo_principal_id, servicio_id }
//   - grupo_principal_id = id del alojamiento PRINCIPAL del grupo
//   - servicio_id        = opción elegida (el principal o una de sus alternativas)
// =====================================================================

require_once dirname(__DIR__, 2) . '/config/database.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) { $input = $_POST; }

    $programaId = (int) ($input['programa_id'] ?? 0);
    $token      = trim((string) ($input['token'] ?? ''));
    $grupoId    = (int) ($input['grupo_principal_id'] ?? 0);
    $servicioId = (int) ($input['servicio_id'] ?? 0);

    if (!$programaId || $token === '' || !$grupoId || !$servicioId) {
        throw new Exception('Parámetros incompletos');
    }

    $db = Database::getInstance();

    // 1) Programa + token + no vendido
    $programa = $db->fetch(
        "SELECT id, comprado, public_token FROM programa_solicitudes WHERE id = ? LIMIT 1",
        [$programaId]
    );
    if (!$programa) {
        throw new Exception('Programa no encontrado');
    }
    if (empty($programa['public_token']) || !hash_equals((string) $programa['public_token'], $token)) {
        throw new Exception('Token inválido');
    }
    if ((int) $programa['comprado'] === 1) {
        throw new Exception('La reserva ya está vendida; la selección no se puede cambiar');
    }

    // 2) El grupo (principal) debe ser un alojamiento de este programa
    $principal = $db->fetch(
        "SELECT pds.id
         FROM programa_dias_servicios pds
         JOIN programa_dias pd ON pd.id = pds.programa_dia_id
         WHERE pds.id = ? AND pd.solicitud_id = ?
           AND pds.es_alternativa = 0 AND pds.tipo_servicio = 'alojamiento'
         LIMIT 1",
        [$grupoId, $programaId]
    );
    if (!$principal) {
        throw new Exception('Grupo de alojamiento no válido');
    }

    // 3) La opción elegida debe pertenecer al grupo (el principal o una alternativa suya)
    $chosen = $db->fetch(
        "SELECT id FROM programa_dias_servicios
         WHERE id = ? AND (id = ? OR servicio_principal_id = ?)
         LIMIT 1",
        [$servicioId, $grupoId, $grupoId]
    );
    if (!$chosen) {
        throw new Exception('Opción no válida para este grupo');
    }

    // 4) Aplicar selección: marca la elegida, desmarca el resto del grupo
    $db->query(
        "UPDATE programa_dias_servicios
         SET seleccionado = CASE WHEN id = ? THEN 1 ELSE 0 END
         WHERE id = ? OR servicio_principal_id = ?",
        [$servicioId, $grupoId, $grupoId]
    );

    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
