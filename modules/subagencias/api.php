<?php
// =====================================
// ARCHIVO: modules/subagencias/api.php
// =====================================

ob_start();

ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/app.php';

App::init();
App::requireLogin();

class SubagenciasAPI {
    private $db;

    public function __construct() {
        try {
            $this->db = Database::getInstance();
        } catch (Exception $e) {
            $this->sendError('Error de conexión: ' . $e->getMessage());
        }
    }

    public function handleRequest() {
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');

        $action   = $_POST['action'] ?? $_GET['action'] ?? '';
        $userRole = $_SESSION['user_role'] ?? '';

        try {
            if (in_array($userRole, ['admin', 'superadmin'], true)) {
                switch ($action) {
                    case 'list_subagencias':
                        $result = $this->listSubagencias();
                        break;
                    case 'create_subagencia':
                        $result = $this->createSubagencia();
                        break;
                    case 'toggle_subagencia':
                        $result = $this->toggleSubagencia();
                        break;
                    case 'delete_subagencia':
                        $result = $this->deleteSubagencia();
                        break;
                    case 'assign_tour':
                        $result = $this->assignTour();
                        break;
                    case 'unassign_tour':
                        $result = $this->unassignTour();
                        break;
                    case 'list_assigned_tours':
                        $result = $this->listAssignedTours();
                        break;
                    default:
                        $result = ['success' => false, 'message' => 'Acción no válida: ' . $action];
                }
            } elseif ($userRole === 'agent') {
                switch ($action) {
                    case 'list_subagencias':
                        $result = $this->listSubagencias();
                        break;
                    case 'assign_tour':
                        $result = $this->assignTour();
                        break;
                    case 'unassign_tour':
                        $result = $this->unassignTour();
                        break;
                    case 'list_assigned_tours':
                        $result = $this->listAssignedTours();
                        break;
                    default:
                        $result = ['success' => false, 'message' => 'Acción no válida: ' . $action];
                }
            } elseif ($userRole === 'subagencia') {
                switch ($action) {
                    case 'get_my_tours':
                        $result = $this->getMyTours();
                        break;
                    case 'get_tour_detail':
                        $result = $this->getTourDetail();
                        break;
                    case 'update_precio':
                        $result = $this->updatePrecio();
                        break;
                    case 'get_config':
                        $result = $this->getConfig();
                        break;
                    case 'save_config':
                        $result = $this->saveConfig();
                        break;
                    default:
                        $result = ['success' => false, 'message' => 'Acción no válida: ' . $action];
                }
            } else {
                $result = ['success' => false, 'message' => 'Acceso denegado'];
            }

            echo json_encode($result, JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            error_log('SubagenciasAPI error: ' . $e->getMessage());
            $this->sendError('Error interno del servidor');
        }
    }

    // ================================================================
    // ACCIONES ADMIN
    // ================================================================

    private function listSubagencias(): array {
        $agenciaId = (int)$_SESSION['agencia_id'];

        $rows = $this->db->fetchAll(
            "SELECT u.id, u.username, u.email, u.full_name, u.active,
                    COALESCE(c.nombre, '') AS nombre_comercial,
                    COALESCE(c.email_contacto, '') AS email_contacto,
                    COALESCE(c.telefono, '') AS telefono,
                    (SELECT COUNT(*) FROM subagencia_tour_precios stp WHERE stp.user_id = u.id) AS tours_asignados
             FROM users u
             LEFT JOIN config_sub_agencias c ON c.user_id = u.id
             WHERE u.agencia_id = ? AND u.role = 'subagencia'
             ORDER BY u.id DESC",
            [$agenciaId]
        );

        return ['success' => true, 'data' => $rows];
    }

    private function createSubagencia(): array {
        $agenciaId = (int)$_SESSION['agencia_id'];
        $email     = trim($_POST['email']     ?? '');
        $username  = trim($_POST['username']  ?? '');
        $fullName  = trim($_POST['full_name'] ?? '');
        $password  = $_POST['password']       ?? '';
        $nombre    = trim($_POST['nombre']    ?? '');

        if (!$email || !$username || !$fullName || !$password || !$nombre) {
            return ['success' => false, 'message' => 'Todos los campos son obligatorios'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Email no válido'];
        }

        if (strlen($password) < 8) {
            return ['success' => false, 'message' => 'La contraseña debe tener al menos 8 caracteres'];
        }

        $existing = $this->db->fetch(
            "SELECT id FROM users WHERE username = ? OR email = ?",
            [$username, $email]
        );
        if ($existing) {
            return ['success' => false, 'message' => 'El username o email ya está en uso'];
        }

        $userId = $this->db->insert('users', [
            'username'   => $username,
            'email'      => $email,
            'full_name'  => $fullName,
            'password'   => password_hash($password, PASSWORD_DEFAULT),
            'role'       => 'subagencia',
            'agencia_id' => $agenciaId,
            'active'     => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->db->insert('config_sub_agencias', [
            'user_id' => $userId,
            'nombre'  => $nombre,
        ]);

        return ['success' => true, 'message' => 'Subagencia creada correctamente', 'id' => $userId];
    }

    private function toggleSubagencia(): array {
        $agenciaId = (int)$_SESSION['agencia_id'];
        $userId    = (int)($_POST['user_id'] ?? 0);

        if (!$userId) {
            return ['success' => false, 'message' => 'user_id es obligatorio'];
        }

        $user = $this->db->fetch(
            "SELECT id, active FROM users WHERE id = ? AND agencia_id = ? AND role = 'subagencia'",
            [$userId, $agenciaId]
        );
        if (!$user) {
            return ['success' => false, 'message' => 'Subagencia no encontrada'];
        }

        $nuevoEstado = $user['active'] ? 0 : 1;
        $this->db->update('users', ['active' => $nuevoEstado], 'id = ?', [$userId]);

        return ['success' => true, 'active' => $nuevoEstado];
    }

    private function deleteSubagencia(): array {
        $agenciaId = (int)$_SESSION['agencia_id'];
        $userId    = (int)($_POST['user_id'] ?? 0);

        if (!$userId) {
            return ['success' => false, 'message' => 'user_id es obligatorio'];
        }

        $user = $this->db->fetch(
            "SELECT id FROM users WHERE id = ? AND agencia_id = ? AND role = 'subagencia'",
            [$userId, $agenciaId]
        );
        if (!$user) {
            return ['success' => false, 'message' => 'Subagencia no encontrada'];
        }

        // ON DELETE CASCADE en config_sub_agencias y subagencia_tour_precios elimina las filas hijas
        $this->db->delete('users', 'id = ?', [$userId]);

        return ['success' => true, 'message' => 'Subagencia eliminada correctamente'];
    }

    private function assignTour(): array {
        $agenciaId   = (int)$_SESSION['agencia_id'];
        $subUserId   = (int)($_POST['sub_user_id']  ?? 0);
        $solicitudId = (int)($_POST['solicitud_id'] ?? 0);

        if (!$subUserId || !$solicitudId) {
            return ['success' => false, 'message' => 'sub_user_id y solicitud_id son obligatorios'];
        }

        $sub = $this->db->fetch(
            "SELECT id FROM users WHERE id = ? AND agencia_id = ? AND role = 'subagencia'",
            [$subUserId, $agenciaId]
        );
        if (!$sub) {
            return ['success' => false, 'message' => 'Subagencia no encontrada'];
        }

        $actorRole = $_SESSION['user_role'] ?? '';
        $actorId   = (int)$_SESSION['user_id'];

        if ($actorRole === 'agent') {
            $tour = $this->db->fetch(
                "SELECT id FROM programa_solicitudes WHERE id = ? AND agencia_id = ? AND user_id = ?",
                [$solicitudId, $agenciaId, $actorId]
            );
        } else {
            $tour = $this->db->fetch(
                "SELECT id FROM programa_solicitudes WHERE id = ? AND agencia_id = ?",
                [$solicitudId, $agenciaId]
            );
        }
        if (!$tour) {
            return ['success' => false, 'message' => 'Tour no encontrado'];
        }

        $existe = $this->db->fetch(
            "SELECT id FROM subagencia_tour_precios WHERE user_id = ? AND solicitud_id = ?",
            [$subUserId, $solicitudId]
        );
        if ($existe) {
            return ['success' => false, 'message' => 'Este tour ya está asignado a esa subagencia'];
        }

        // Copiar los precios originales como valores iniciales del override
        $precios = $this->db->fetch(
            "SELECT precio_adulto, precio_nino, precio_total, precio_incluye,
                    precio_no_incluye, condiciones_generales, movilidad_reducida,
                    info_pasaporte, info_seguros
             FROM programa_precios WHERE solicitud_id = ?",
            [$solicitudId]
        );

        $this->db->insert('subagencia_tour_precios', [
            'user_id'               => $subUserId,
            'solicitud_id'          => $solicitudId,
            'precio_adulto'         => $precios['precio_adulto']         ?? null,
            'precio_nino'           => $precios['precio_nino']           ?? null,
            'precio_total'          => $precios['precio_total']          ?? null,
            'precio_incluye'        => $precios['precio_incluye']        ?? null,
            'precio_no_incluye'     => $precios['precio_no_incluye']     ?? null,
            'condiciones_generales' => $precios['condiciones_generales'] ?? null,
            'movilidad_reducida'    => $precios['movilidad_reducida']    ?? 0,
            'info_pasaporte'        => $precios['info_pasaporte']        ?? null,
            'info_seguros'          => $precios['info_seguros']          ?? null,
            'public_token'          => bin2hex(random_bytes(16)),
        ]);

        return ['success' => true, 'message' => 'Tour asignado correctamente'];
    }

    private function unassignTour(): array {
        $agenciaId   = (int)$_SESSION['agencia_id'];
        $subUserId   = (int)($_POST['sub_user_id']  ?? 0);
        $solicitudId = (int)($_POST['solicitud_id'] ?? 0);

        if (!$subUserId || !$solicitudId) {
            return ['success' => false, 'message' => 'sub_user_id y solicitud_id son obligatorios'];
        }

        $sub = $this->db->fetch(
            "SELECT id FROM users WHERE id = ? AND agencia_id = ? AND role = 'subagencia'",
            [$subUserId, $agenciaId]
        );
        if (!$sub) {
            return ['success' => false, 'message' => 'Subagencia no encontrada'];
        }

        // El agent solo puede desasignar tours que le pertenecen
        $actorRole = $_SESSION['user_role'] ?? '';
        if ($actorRole === 'agent') {
            $actorId = (int)$_SESSION['user_id'];
            $tourDelAgent = $this->db->fetch(
                "SELECT id FROM programa_solicitudes WHERE id = ? AND agencia_id = ? AND user_id = ?",
                [$solicitudId, $agenciaId, $actorId]
            );
            if (!$tourDelAgent) {
                return ['success' => false, 'message' => 'Tour no encontrado'];
            }
        }

        $this->db->delete(
            'subagencia_tour_precios',
            'user_id = ? AND solicitud_id = ?',
            [$subUserId, $solicitudId]
        );

        return ['success' => true, 'message' => 'Tour desasignado correctamente'];
    }

    private function listAssignedTours(): array {
        $agenciaId = (int)$_SESSION['agencia_id'];
        $subUserId = (int)($_GET['sub_user_id'] ?? $_POST['sub_user_id'] ?? 0);

        if (!$subUserId) {
            return ['success' => false, 'message' => 'sub_user_id es obligatorio'];
        }

        $sub = $this->db->fetch(
            "SELECT id FROM users WHERE id = ? AND agencia_id = ? AND role = 'subagencia'",
            [$subUserId, $agenciaId]
        );
        if (!$sub) {
            return ['success' => false, 'message' => 'Subagencia no encontrada'];
        }

        $actorRole = $_SESSION['user_role'] ?? '';
        $actorId   = (int)$_SESSION['user_id'];

        if ($actorRole === 'agent') {
            $tours = $this->db->fetchAll(
                "SELECT stp.id, stp.solicitud_id, stp.precio_total, stp.public_token,
                        COALESCE(pp.titulo_programa, ps.destino) AS titulo,
                        ps.destino, ps.fecha_inicio, ps.fecha_fin
                 FROM subagencia_tour_precios stp
                 JOIN programa_solicitudes ps ON ps.id = stp.solicitud_id
                 LEFT JOIN programa_personalizacion pp ON pp.solicitud_id = ps.id
                 WHERE stp.user_id = ? AND ps.user_id = ?
                 ORDER BY stp.created_at DESC",
                [$subUserId, $actorId]
            );
        } else {
            $tours = $this->db->fetchAll(
                "SELECT stp.id, stp.solicitud_id, stp.precio_total, stp.public_token,
                        COALESCE(pp.titulo_programa, ps.destino) AS titulo,
                        ps.destino, ps.fecha_inicio, ps.fecha_fin
                 FROM subagencia_tour_precios stp
                 JOIN programa_solicitudes ps ON ps.id = stp.solicitud_id
                 LEFT JOIN programa_personalizacion pp ON pp.solicitud_id = ps.id
                 WHERE stp.user_id = ?
                 ORDER BY stp.created_at DESC",
                [$subUserId]
            );
        }

        return ['success' => true, 'data' => $tours];
    }

    // ================================================================
    // ACCIONES SUBAGENCIA
    // ================================================================

    private function getMyTours(): array {
        $userId = (int)$_SESSION['user_id'];

        $tours = $this->db->fetchAll(
            "SELECT stp.id, stp.solicitud_id, stp.precio_adulto, stp.precio_nino,
                    stp.precio_total, stp.public_token,
                    COALESCE(pp.titulo_programa, ps.destino) AS titulo,
                    ps.destino, ps.fecha_inicio, ps.fecha_fin, ps.num_dias
             FROM subagencia_tour_precios stp
             JOIN programa_solicitudes ps ON ps.id = stp.solicitud_id
             LEFT JOIN programa_personalizacion pp ON pp.solicitud_id = ps.id
             WHERE stp.user_id = ?
             ORDER BY stp.created_at DESC",
            [$userId]
        );

        return ['success' => true, 'data' => $tours];
    }

    private function getTourDetail(): array {
        $userId      = (int)$_SESSION['user_id'];
        $solicitudId = (int)($_GET['solicitud_id'] ?? $_POST['solicitud_id'] ?? 0);

        if (!$solicitudId) {
            return ['success' => false, 'message' => 'solicitud_id es obligatorio'];
        }

        $stp = $this->db->fetch(
            "SELECT * FROM subagencia_tour_precios WHERE user_id = ? AND solicitud_id = ?",
            [$userId, $solicitudId]
        );
        if (!$stp) {
            return ['success' => false, 'message' => 'Tour no encontrado o no asignado'];
        }

        $programa = $this->db->fetch(
            "SELECT ps.id, ps.destino, ps.fecha_inicio, ps.fecha_fin, ps.num_dias,
                    ps.num_pasajeros,
                    COALESCE(pp.titulo_programa, ps.destino) AS titulo,
                    pp.foto_portada, pp.idioma_predeterminado
             FROM programa_solicitudes ps
             LEFT JOIN programa_personalizacion pp ON pp.solicitud_id = ps.id
             WHERE ps.id = ?",
            [$solicitudId]
        );

        $dias = $this->db->fetchAll(
            "SELECT id, dia_numero, titulo, descripcion, ubicacion, imagen1
             FROM programa_dias
             WHERE solicitud_id = ?
             ORDER BY dia_numero",
            [$solicitudId]
        );

        return [
            'success'  => true,
            'programa' => $programa,
            'precios'  => $stp,
            'dias'     => $dias,
        ];
    }

    private function updatePrecio(): array {
        $userId      = (int)$_SESSION['user_id'];
        $solicitudId = (int)($_POST['solicitud_id'] ?? 0);

        if (!$solicitudId) {
            return ['success' => false, 'message' => 'solicitud_id es obligatorio'];
        }

        $stp = $this->db->fetch(
            "SELECT id FROM subagencia_tour_precios WHERE user_id = ? AND solicitud_id = ?",
            [$userId, $solicitudId]
        );
        if (!$stp) {
            return ['success' => false, 'message' => 'Tour no encontrado o no asignado'];
        }

        // Whitelist de campos editables — nunca se permite editar user_id, solicitud_id, public_token
        $camposPermitidos = [
            'precio_adulto', 'precio_nino', 'cantidad_adultos', 'cantidad_ninos',
            'precio_total', 'noches_incluidas', 'precio_incluye', 'precio_no_incluye',
            'condiciones_generales', 'movilidad_reducida', 'info_pasaporte', 'info_seguros',
        ];

        $data = [];
        foreach ($camposPermitidos as $campo) {
            if (array_key_exists($campo, $_POST)) {
                $data[$campo] = $_POST[$campo] === '' ? null : $_POST[$campo];
            }
        }

        if (empty($data)) {
            return ['success' => false, 'message' => 'No se enviaron campos para actualizar'];
        }

        $this->db->update(
            'subagencia_tour_precios',
            $data,
            'user_id = ? AND solicitud_id = ?',
            [$userId, $solicitudId]
        );

        return ['success' => true, 'message' => 'Precios actualizados correctamente'];
    }

    private function getConfig(): array {
        $userId = (int)$_SESSION['user_id'];

        $config = $this->db->fetch(
            "SELECT nombre, logo_url, primary_color, secondary_color,
                    divisa, email_contacto, telefono
             FROM config_sub_agencias
             WHERE user_id = ?",
            [$userId]
        );

        if (!$config) {
            $config = [
                'nombre'          => '',
                'logo_url'        => null,
                'primary_color'   => '#667eea',
                'secondary_color' => '#764ba2',
                'divisa'          => 'USD',
                'email_contacto'  => '',
                'telefono'        => '',
            ];
        }

        return ['success' => true, 'data' => $config];
    }

    private function saveConfig(): array {
        $userId = (int)$_SESSION['user_id'];

        $camposPermitidos = [
            'nombre', 'primary_color', 'secondary_color',
            'divisa', 'email_contacto', 'telefono',
        ];

        $data = [];
        foreach ($camposPermitidos as $campo) {
            if (isset($_POST[$campo])) {
                $data[$campo] = trim($_POST[$campo]) === '' ? null : trim($_POST[$campo]);
            }
        }

        if (empty($data)) {
            return ['success' => false, 'message' => 'No se enviaron campos para guardar'];
        }

        $existe = $this->db->fetch(
            "SELECT id FROM config_sub_agencias WHERE user_id = ?",
            [$userId]
        );

        if ($existe) {
            $this->db->update('config_sub_agencias', $data, 'user_id = ?', [$userId]);
        } else {
            $data['user_id'] = $userId;
            $this->db->insert('config_sub_agencias', $data);
        }

        return ['success' => true, 'message' => 'Configuración guardada correctamente'];
    }

    private function sendError(string $message): void {
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

$api = new SubagenciasAPI();
$api->handleRequest();
