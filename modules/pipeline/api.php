<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/app.php';

App::init();
App::requireLogin();

class PipelineAPI
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
            switch ($action) {
                case 'get_estados':
                    $result = $this->getEstados();
                    break;
                case 'get_tags':
                    $result = $this->getTags();
                    break;
                case 'get_agentes':
                    $result = $this->getAgentes();
                    break;
                case 'crear_lead':
                    $result = $this->crearLead();
                    break;
                case 'crear_template':
                    $result = $this->crearTemplate();
                    break;
                case 'get_templates':
                    $result = $this->getTemplates();
                    break;
                case 'mover_estado':
                    $result = $this->moverEstado();
                    break;
                case 'asignar_asesor':
                    $result = $this->asignarAsesor();
                    break;
                case 'get_pipeline':
                    $result = $this->getPipeline();
                    break;
                case 'save_pipeline':
                    $result = $this->savePipeline();
                    break;
                case 'filtrar_pipeline':
                    $result = $this->filtrarPipeline();
                    break;
                default:
                    throw new Exception('Acción no válida: ' . $action);
            }

            echo json_encode($result, JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            $this->sendError($e->getMessage());
        }
    }
    private function savePipeline()
    {
        $agencia_id = $_SESSION['agencia_id'] ?? null;
        if (!$agencia_id) {
            throw new Exception('Usuario sin agencia asignada');
        }

        // Leer el ID del pipeline desde la URL (?id=X)
        $pipeline_id = $_GET['id'] ?? null;
        if (!$pipeline_id) {
            throw new Exception('ID de pipeline requerido');
        }

        // Leer el cuerpo JSON enviado por el fetch
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            throw new Exception('Datos inválidos o vacíos');
        }

        $this->db->update(
            'pipeline',
            [
                'estado_id' => $data['estado_id'] ?? null,
                'tag_id' => $data['tag_id'] ?? null,
                'usuario_id' => $data['usuario_id'] ?? null,
                'budget' => $data['budget'] ?? null,
                'telefono_cliente' => $data['telefono_cliente'] ?? null,
                'viajeros' => $data['viajeros'] ?? null,
                'fecha_salida' => $data['fecha_salida'] ?? null,
                'fecha_llegada' => $data['fecha_llegada'] ?? null,
                'destino' => $data['destino'] ?? null,
            ],
            'id = ? AND agencia_id = ?',
            [$pipeline_id, $agencia_id]
        );

        return ['success' => true, 'mensaje' => 'Pipeline guardado exitosamente'];
    }
    private function getEstados()
    {
        $agencia_id = $_SESSION['agencia_id'] ?? null;
        if (!$agencia_id) {
            throw new Exception('Usuario sin agencia asignada');
        }

        $estados = $this->db->fetchAll(
            "SELECT id, nombre, descripcion, posicion
            FROM pipeline_estados
            WHERE agencia_id = ?
            ORDER BY posicion ASC",
            [$agencia_id]
        );

        return ['success' => true, 'data' => $estados];
    }
    private function getTags()
    {
        $agencia_id = $_SESSION['agencia_id'] ?? null;
        if (!$agencia_id) {
            throw new Exception('Usuario sin agencia asignada');
        }

        $tags = $this->db->fetchAll(
            "SELECT id, nombre
            FROM tags
            WHERE agencia_id = ?
            ORDER BY id ASC",
            [$agencia_id]
        );

        return ['success' => true, 'data' => $tags];
    }
    private function getAgentes()
    {
        $agencia_id = $_SESSION['agencia_id'] ?? null;
        if (!$agencia_id) {
            throw new Exception('Usuario sin agencia asignada');
        }

        $agentes = $this->db->fetchAll(
            "SELECT id, username
            FROM users
            WHERE agencia_id = ? AND active=1
            ORDER BY id ASC",
            [$agencia_id]
        );

        return ['success' => true, 'data' => $agentes];
    }
    private function getPipeline()
    {
        $agencia_id = $_SESSION['agencia_id'] ?? null;
        if (!$agencia_id) {
            throw new Exception('Usuario sin agencia asignada');
        }

        $pipeline_id = $_GET['id'] ?? $_POST['id'] ?? null;
        if (!$pipeline_id) {
            throw new Exception('ID de pipeline requerido');
        }

        $pipeline = $this->db->fetch(
            "SELECT id, usuario_id, solicitud_id, estado_id, tag_id, nombre_cliente, telefono_cliente, destino, viajeros, budget, fecha_salida, fecha_llegada,descripcion
            FROM pipeline
            WHERE agencia_id = ? AND id = ?",
            [$agencia_id, $pipeline_id]
        );

        return ['success' => true, 'data' => $pipeline];
    }
    private function crearLead()
    {
        $agencia_id = $_SESSION['agencia_id'] ?? null;
        if (!$agencia_id) {
            throw new Exception('Usuario sin agencia asignada');
        }

        $nombre_cliente = trim($_POST['nombre_cliente'] ?? '');
        $email_cliente = trim($_POST['email_cliente'] ?? '');
        $destino = trim($_POST['destino'] ?? '');
        $fecha_salida = $_POST['fecha_salida'] ?? '';
        $estado_id = $_POST['estado_id'] ?? null;

        if (!$nombre_cliente)
            throw new Exception('El nombre del cliente es obligatorio');
        if (!$email_cliente)
            throw new Exception('El email del cliente es obligatorio');
        if (!filter_var($email_cliente, FILTER_VALIDATE_EMAIL))
            throw new Exception('El email no es válido');
        if (!$destino)
            throw new Exception('El destino es obligatorio');
        if (!$fecha_salida)
            throw new Exception('La fecha de salida es obligatoria');
        if (!$estado_id)
            throw new Exception('El estado es obligatorio');

        $estado = $this->db->fetch(
            "SELECT id FROM pipeline_estados WHERE id = ? AND agencia_id = ?",
            [$estado_id, $agencia_id]
        );
        if (!$estado) {
            throw new Exception('Estado no válido para esta agencia');
        }

        $data = [
            'agencia_id' => $agencia_id,
            'nombre_cliente' => $nombre_cliente,
            'email_cliente' => $email_cliente,
            'telefono_cliente' => trim($_POST['telefono_cliente'] ?? '') ?: null,
            'destino' => $destino,
            'descripcion' => trim($_POST['descripcion'] ?? '') ?: null,
            'viajeros' => intval($_POST['viajeros'] ?? 1),
            'fecha_salida' => $fecha_salida,
            'fecha_llegada' => $_POST['fecha_llegada'] ?? null,
            'budget' => !empty($_POST['budget']) ? floatval($_POST['budget']) : null,
            'source' => trim($_POST['source'] ?? '') ?: null,
            'estado_id' => intval($estado_id),
            'usuario_id' => !empty($_POST['usuario_id']) ? intval($_POST['usuario_id']) : null,
            'tag_id' => !empty($_POST['tag_id']) ? intval($_POST['tag_id']) : null,
        ];

        $nuevo_id = $this->db->insert('pipeline', $data);

        if (!$nuevo_id) {
            throw new Exception('Error al crear el lead');
        }

        return [
            'success' => true,
            'message' => 'Lead creado exitosamente',
            'id' => $nuevo_id
        ];
    }
    private function crearTemplate()
    {
        $agencia_id = $_SESSION['agencia_id'] ?? null;
        if (!$agencia_id) {
            throw new Exception('Usuario sin agencia asignada');
        }



        $data = [
            'agencia_id' => $agencia_id,
            "nombre" => $_POST['nombre'],
            "texto" => $_POST['texto']
        ];

        $nuevo_id = $this->db->insert('template_mensaje', $data);

        if (!$nuevo_id) {
            throw new Exception('Error al crear el template');
        }

        return [
            'success' => true,
            'message' => 'Template creado exitosamente',
            'id' => $nuevo_id
        ];
    }
    private function getTemplates()
    {
        $agencia_id = $_SESSION['agencia_id'] ?? null;
        if (!$agencia_id) {
            throw new Exception('Usuario sin agencia asignada');
        }



        $templates = $this->db->fetch(
            "SELECT id, nombre, texto
            FROM template_mensaje
            WHERE agencia_id = ?",
            [$agencia_id]
        );

        return ['success' => true, 'data' => $templates];
    }
    private function moverEstado()
    {
        $agencia_id = $_SESSION['agencia_id'] ?? null;
        $user_id = $_SESSION['user_id'];
        $user_role = $_SESSION['user_role'] ?? 'agent';

        $pipeline_id = $_POST['pipeline_id'] ?? null;
        $estado_id = $_POST['estado_id'] ?? null;

        if (!$pipeline_id)
            throw new Exception('ID de lead requerido');
        if (!$estado_id)
            throw new Exception('ID de estado requerido');

        $where_lead = $user_role === 'admin'
            ? "id = ? AND agencia_id = ?"
            : "id = ? AND agencia_id = ? AND usuario_id = ?";
        $params_lead = $user_role === 'admin'
            ? [$pipeline_id, $agencia_id]
            : [$pipeline_id, $agencia_id, $user_id];

        $lead = $this->db->fetch(
            "SELECT id FROM pipeline WHERE $where_lead",
            $params_lead
        );
        if (!$lead) {
            throw new Exception('Lead no encontrado o sin permisos');
        }

        $estado = $this->db->fetch(
            "SELECT id FROM pipeline_estados WHERE id = ? AND agencia_id = ?",
            [$estado_id, $agencia_id]
        );
        if (!$estado) {
            throw new Exception('Estado no válido para esta agencia');
        }

        $this->db->update(
            'pipeline',
            ['estado_id' => intval($estado_id)],
            'id = ?',
            [$pipeline_id]
        );

        return [
            'success' => true,
            'message' => 'Estado actualizado correctamente',
            'estado_id' => intval($estado_id)
        ];
    }

    private function asignarAsesor()
    {
        $user_role = $_SESSION['user_role'] ?? 'agent';
        $agencia_id = $_SESSION['agencia_id'] ?? null;

        if ($user_role !== 'admin') {
            throw new Exception('Solo los administradores pueden asignar asesores');
        }

        $pipeline_id = $_POST['pipeline_id'] ?? null;
        $usuario_id = $_POST['usuario_id'] ?? null;

        if (!$pipeline_id)
            throw new Exception('ID de lead requerido');
        if (!$usuario_id)
            throw new Exception('ID de asesor requerido');

        $lead = $this->db->fetch(
            "SELECT id FROM pipeline WHERE id = ? AND agencia_id = ?",
            [$pipeline_id, $agencia_id]
        );
        if (!$lead) {
            throw new Exception('Lead no encontrado');
        }

        $asesor = $this->db->fetch(
            "SELECT id FROM users WHERE id = ? AND agencia_id = ?",
            [$usuario_id, $agencia_id]
        );
        if (!$asesor) {
            throw new Exception('El asesor no pertenece a esta agencia');
        }

        $this->db->update(
            'pipeline',
            ['usuario_id' => intval($usuario_id)],
            'id = ?',
            [$pipeline_id]
        );

        return [
            'success' => true,
            'message' => 'Asesor asignado correctamente',
            'usuario_id' => intval($usuario_id)
        ];
    }

    private function filtrarPipeline()
    {
        $agencia_id = $_SESSION['agencia_id'] ?? null;
        if (!$agencia_id) {
            throw new Exception('Usuario sin agencia asignada');
        }

        $where = ["p.agencia_id = ?"];
        $params = [$agencia_id];

        if (!empty($_GET['estado_id'])) {
            $where[] = "p.estado_id = ?";
            $params[] = intval($_GET['estado_id']);
        }

        if (!empty($_GET['usuario_id'])) {
            $where[] = "p.usuario_id = ?";
            $params[] = intval($_GET['usuario_id']);
        }

        if (!empty($_GET['tag_id'])) {
            $where[] = "p.tag_id = ?";
            $params[] = intval($_GET['tag_id']);
        }

        if (!empty($_GET['buscar'])) {
            $where[] = "(p.nombre_cliente LIKE ? OR p.email_cliente LIKE ? OR p.destino LIKE ?)";
            $term = '%' . $_GET['buscar'] . '%';
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        if (!empty($_GET['fecha_desde'])) {
            $where[] = "p.fecha_salida >= ?";
            $params[] = $_GET['fecha_desde'];
        }

        if (!empty($_GET['fecha_hasta'])) {
            $where[] = "p.fecha_salida <= ?";
            $params[] = $_GET['fecha_hasta'];
        }

        $where_sql = implode(' AND ', $where);

        $leads = $this->db->fetchAll(
            "SELECT p.*,
                    pe.nombre  AS estado_nombre,
                    pe.posicion AS estado_posicion,
                    u.full_name AS asesor_nombre,
                    t.nombre   AS tag_nombre
            FROM pipeline p
            LEFT JOIN pipeline_estados pe ON p.estado_id = pe.id
            LEFT JOIN users            u  ON p.usuario_id = u.id
            LEFT JOIN tags             t  ON p.tag_id = t.id
            WHERE $where_sql
            ORDER BY p.created_at DESC",
            $params
        );

        return ['success' => true, 'data' => $leads];
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

$api = new PipelineAPI();
$api->handleRequest();
