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
                case 'save_estados':
                    $result = $this->saveEstados();
                    break;
                case 'update_estados':
                    $result = $this->updateEstados();
                    break;
                case 'delete_estados':
                    $result = $this->deleteEstados();
                    break;
                case 'reordenar_estados':
                    $result = $this->reordenarEstados();
                    break;
                case 'get_tags':
                    $result = $this->getTags();
                    break;
                case 'save_tags':
                    $result = $this->saveTags();
                    break;
                case 'update_tags':
                    $result = $this->updateTags();
                    break;
                case 'delete_tags':
                    $result = $this->deleteTags();
                    break;
                case 'get_source':
                    $result = $this->getSource();
                    break;
                case 'save_source':
                    $result = $this->saveSource();
                    break;
                case 'update_source':
                    $result = $this->updateSource();
                    break;
                case 'delete_source':
                    $result = $this->deleteSource();
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
                case 'update_template':
                    $result = $this->updateTemplate();
                    break;
                case 'delete_template':
                    $result = $this->deleteTemplate();
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
                case 'get_mensajes':
                    $result = $this->getMensajes();
                    break;
                case 'save_pipeline':
                    $result = $this->savePipeline();
                    break;
                case 'filtrar_pipeline':
                    $result = $this->filtrarPipeline();
                    break;
                case 'get_programas':
                    $result = $this->get_programas();
                    break;
                case 'asignar_itinerario':
                    $result = $this->asignar_itinerario();
                    break;
                case 'desvincular_itinerario':
                    $result = $this->desvincular_itinerario();
                    break;
                case 'asignar_tag':
                    $result = $this->asignarTag();
                    break;
                case 'editar_lead':
                    $result = $this->editarLead();
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
                'tag_id2' => $data['tag_id2'] ?? null,
                'usuario_id' => $data['usuario_id'] ?? null,
                'budget' => $data['budget'] ?? null,
                'telefono_cliente' => $data['telefono_cliente'] ?? null,
                'viajeros' => $data['viajeros'] ?? null,
                'fecha_salida' => $data['fecha_salida'] ?? null,
                'fecha_llegada' => $data['fecha_llegada'] ?? null
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
            "SELECT id, nombre, color, descripcion, posicion, es_final, tipo_final
            FROM pipeline_estados
            WHERE agencia_id = ?
            ORDER BY posicion ASC",
            [$agencia_id]
        );

        return ['success' => true, 'data' => $estados];
    }
    private function saveEstados()
    {
        $agencia_id = $_SESSION['agencia_id'] ?? null;
        if (!$agencia_id) {
            throw new Exception('Usuario sin agencia asignada');
        }

        // Leer el cuerpo JSON enviado por el fetch
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            throw new Exception('Datos inválidos o vacíos');
        }

        // Calcular la siguiente posición para que el estado quede al final
        // (evita chocar con UNIQUE(agencia_id, posicion) por el DEFAULT 0).
        $pos = $this->db->fetch(
            "SELECT COALESCE(MAX(posicion), 0) + 1 AS siguiente
            FROM pipeline_estados
            WHERE agencia_id = ?",
            [$agencia_id]
        );
        $siguiente = $pos['siguiente'];

        $tipo_final = in_array($data['tipo_final'] ?? null, ['ganado', 'perdido'], true) ? $data['tipo_final'] : null;
        $es_final = (!empty($data['es_final']) || $tipo_final) ? 1 : 0;

        $nuevo_id = $this->db->insert(
            'pipeline_estados',
            [
                'agencia_id' => $agencia_id,
                'nombre' => $data['nombre'],
                'color' => $data['color'] ?? '#6366f1',
                'descripcion' => $data['descripcion'] ?? null,
                'es_final' => $es_final,
                'tipo_final' => $tipo_final,
                'posicion' => $siguiente
            ]
        );
        return ['success' => true, 'id' => $nuevo_id, 'posicion' => $siguiente, 'mensaje' => 'estado guardado exitosamente'];
    }
    private function updateEstados()
    {
        $agencia_id = $_SESSION['agencia_id'] ?? null;
        if (!$agencia_id) {
            throw new Exception('Usuario sin agencia asignada');
        }
        // Leer el cuerpo JSON enviado por el fetch
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            throw new Exception('Datos inválidos o vacíos');
        }

        if (empty($data['id'])) {
            throw new Exception('ID de estado requerido');
        }

        // Actualización PARCIAL: solo se tocan los campos presentes en el body,
        // para no pisar es_final/tipo_final al guardar solo nombre/color (ni viceversa).
        $fields = [];
        if (array_key_exists('nombre', $data))      $fields['nombre'] = $data['nombre'];
        if (array_key_exists('color', $data))       $fields['color'] = $data['color'] ?: '#6366f1';
        if (array_key_exists('descripcion', $data)) $fields['descripcion'] = $data['descripcion'];
        if (array_key_exists('tipo_final', $data) || array_key_exists('es_final', $data)) {
            $tipo_final = in_array($data['tipo_final'] ?? null, ['ganado', 'perdido'], true) ? $data['tipo_final'] : null;
            $fields['tipo_final'] = $tipo_final;
            $fields['es_final'] = (!empty($data['es_final']) || $tipo_final) ? 1 : 0;
        }
        if (!$fields) {
            throw new Exception('No hay cambios que aplicar');
        }

        $this->db->update(
            'pipeline_estados',
            $fields,
            'agencia_id = ? AND id=?',
            [$agencia_id, $data['id']]
        );
        return ['success' => true, 'mensaje' => 'estado actualizado exitosamente'];
    }
    private function deleteEstados()
    {
        $agencia_id = $_SESSION['agencia_id'] ?? null;
        if (!$agencia_id) {
            throw new Exception('Usuario sin agencia asignada');
        }
        // Leer el cuerpo JSON enviado por el fetch
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['id']))
            throw new Exception('ID requerido');
        if (!$data) {
            throw new Exception('Datos inválidos o vacíos');
        }
        $cantidad = $this->db->fetch(
            'SELECT COUNT(*) AS total FROM pipeline 
            WHERE estado_id = ? AND agencia_id = ?',
            [$data['id'], $agencia_id]
        );
        if ($cantidad['total'] == 0) {
            $this->db->delete(
                'pipeline_estados',
                'id = ? AND agencia_id = ?',

                [$data['id'], $agencia_id]
            );
            return ['success' => true, 'mensaje' => 'Estado Borrado  exitosamente'];
        } else {
            return throw new Exception("No se puede eliminar el estado tiene leads asignados");
        }

    }
    private function reordenarEstados()
    {
        $agencia_id = $_SESSION['agencia_id'] ?? null;
        if (!$agencia_id) {
            throw new Exception('Usuario sin agencia asignada');
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $orden = $data['orden'] ?? null;
        if (!is_array($orden) || count($orden) === 0) {
            throw new Exception('Orden inválido o vacío');
        }
        $orden = array_map('intval', $orden);

        $existentes = $this->db->fetchAll(
            "SELECT id FROM pipeline_estados WHERE agencia_id = ?",
            [$agencia_id]
        );
        $idsAgencia = array_map('intval', array_column($existentes, 'id'));

        $a = $orden;
        $b = $idsAgencia;
        sort($a);
        sort($b);
        if ($a !== $b) {
            throw new Exception('La lista enviada no coincide con los estados de la agencia');
        }


        $pdo = $this->db->getConnection();
        $pdo->beginTransaction();
        try {
            foreach ($orden as $i => $id) {
                $this->db->update(
                    'pipeline_estados',
                    ['posicion' => -($i + 1)],
                    'id = ? AND agencia_id = ?',
                    [$id, $agencia_id]
                );
            }
            foreach ($orden as $i => $id) {
                $this->db->update(
                    'pipeline_estados',
                    ['posicion' => $i + 1],
                    'id = ? AND agencia_id = ?',
                    [$id, $agencia_id]
                );
            }
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }

        return ['success' => true, 'mensaje' => 'Orden de estados actualizado'];
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
            WHERE agencia_id = ? AND  tipo = 'pipeline'
            ORDER BY id ASC",
            [$agencia_id]
        );

        return ['success' => true, 'data' => $tags];
    }
    private function saveTags()
    {
        $agencia_id = $_SESSION['agencia_id'] ?? null;
        if (!$agencia_id) {
            throw new Exception('Usuario sin agencia asignada');
        }

        // Leer el cuerpo JSON enviado por el fetch
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            throw new Exception('Datos inválidos o vacíos');
        }

        $this->db->insert(
            'tags',
            [
                'agencia_id' => $agencia_id,
                'nombre' => $data['nombre'],
            ]

        );
        return ['success' => true, 'mensaje' => 'tags guardado exitosamente'];
    }
    private function updateTags()
    {
        $agencia_id = $_SESSION['agencia_id'] ?? null;
        if (!$agencia_id) {
            throw new Exception('Usuario sin agencia asignada');
        }
        // Leer el cuerpo JSON enviado por el fetch
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            throw new Exception('Datos inválidos o vacíos');
        }

        $this->db->update(
            'tags',
            [
                'nombre' => $data['nombre'],
            ],
            'agencia_id = ? AND id=?',
            [$agencia_id, $data['id']]
        );
        return ['success' => true, 'mensaje' => 'tags actualizados exitosamente'];
    }
    private function deleteTags()
    {
        $agencia_id = $_SESSION['agencia_id'] ?? null;
        if (!$agencia_id) {
            throw new Exception('Usuario sin agencia asignada');
        }
        // Leer el cuerpo JSON enviado por el fetch
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['id']))
            throw new Exception('ID requerido');
        if (!$data) {
            throw new Exception('Datos inválidos o vacíos');
        }

        $this->db->delete(
            'tags',
            'id = ? AND agencia_id = ?',

            [$data['id'], $agencia_id]
        );
        return ['success' => true, 'mensaje' => 'tags eliminados exitosamente'];
    }


    private function getSource()
    {
        $agencia_id = $_SESSION['agencia_id'] ?? null;
        if (!$agencia_id) {
            throw new Exception('Usuario sin agencia asignada');
        }

        $source = $this->db->fetchAll(
            "SELECT id, nombre
            FROM pipeline_sources
            WHERE agencia_id = ?
            ORDER BY id ASC",
            [$agencia_id]
        );

        return ['success' => true, 'data' => $source];
    }
    private function saveSource()
    {
        $agencia_id = $_SESSION['agencia_id'] ?? null;
        if (!$agencia_id) {
            throw new Exception('Usuario sin agencia asignada');
        }

        // Leer el cuerpo JSON enviado por el fetch
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            throw new Exception('Datos inválidos o vacíos');
        }

        $this->db->insert(
            'pipeline_sources',
            [
                'agencia_id' => $agencia_id,
                'nombre' => $data['nombre'],
            ]

        );
        return ['success' => true, 'mensaje' => 'source guardado exitosamente'];
    }
    private function updateSource()
    {
        $agencia_id = $_SESSION['agencia_id'] ?? null;
        if (!$agencia_id) {
            throw new Exception('Usuario sin agencia asignada');
        }
        // Leer el cuerpo JSON enviado por el fetch
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            throw new Exception('Datos inválidos o vacíos');
        }

        $this->db->update(
            'pipeline_sources',
            [
                'nombre' => $data['nombre'],
            ],
            'agencia_id = ? AND id=?',
            [$agencia_id, $data['id']]
        );
        return ['success' => true, 'mensaje' => 'source actualizados exitosamente'];
    }
    private function deleteSource()
    {
        $agencia_id = $_SESSION['agencia_id'] ?? null;
        if (!$agencia_id) {
            throw new Exception('Usuario sin agencia asignada');
        }
        // Leer el cuerpo JSON enviado por el fetch
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['id']))
            throw new Exception('ID requerido');
        if (!$data) {
            throw new Exception('Datos inválidos o vacíos');
        }

        $this->db->delete(
            'pipeline_sources',
            'id = ? AND agencia_id = ?',

            [$data['id'], $agencia_id]
        );
        return ['success' => true, 'mensaje' => 'tags eliminados exitosamente'];
    }


    private function getAgentes()
    {
        $agencia_id = $_SESSION['agencia_id'] ?? null;
        if (!$agencia_id) {
            throw new Exception('Usuario sin agencia asignada');
        }

        $agentes = $this->db->fetchAll(
            "SELECT id, username, full_name
            FROM users
            WHERE agencia_id = ? AND active=1
            ORDER BY full_name ASC, username ASC",
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

        // Un agente solo puede ver SUS leads; el admin, todos los de la agencia.
        $user_id = $_SESSION['user_id'] ?? null;
        $user_role = $_SESSION['user_role'] ?? 'agent';
        $where  = $user_role === 'admin' ? "agencia_id = ? AND id = ?" : "agencia_id = ? AND id = ? AND usuario_id = ?";
        $params = $user_role === 'admin' ? [$agencia_id, $pipeline_id] : [$agencia_id, $pipeline_id, $user_id];

        $pipeline = $this->db->fetch(
            "SELECT id, usuario_id, solicitud_id, estado_id, tag_id, nombre_cliente, telefono_cliente, destino, viajeros, budget, fecha_salida, fecha_llegada,descripcion
            FROM pipeline
            WHERE {$where}",
            $params
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
            'source' => !empty($_POST['source']) ? intval($_POST['source']) : null,
            'estado_id' => intval($estado_id),
            'usuario_id' => !empty($_POST['usuario_id']) ? intval($_POST['usuario_id']) : null,
            'tag_id' => !empty($_POST['tag_id']) ? intval($_POST['tag_id']) : null,
            'tag_id2' => !empty($_POST['tag_id2']) ? intval($_POST['tag_id2']) : null,
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



        $templates = $this->db->fetchAll(
            "SELECT id, nombre, texto
            FROM template_mensaje
            WHERE agencia_id = ?
            ORDER BY nombre ASC",
            [$agencia_id]
        );

        return ['success' => true, 'data' => $templates];
    }
    private function updateTemplate()
    {
        $agencia_id = $_SESSION['agencia_id'] ?? null;
        if (!$agencia_id)
            throw new Exception('Usuario sin agencia asignada');

        $id = (int) ($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $texto = trim($_POST['texto'] ?? '');

        if (!$id || !$nombre || !$texto)
            throw new Exception('ID, nombre y texto son requeridos');

        $this->db->query(
            "UPDATE template_mensaje SET nombre = ?, texto = ? WHERE id = ? AND agencia_id = ?",
            [$nombre, $texto, $id, $agencia_id]
        );

        return ['success' => true, 'message' => 'Template actualizado'];
    }
    private function deleteTemplate()
    {
        $agencia_id = $_SESSION['agencia_id'] ?? null;
        if (!$agencia_id)
            throw new Exception('Usuario sin agencia asignada');

        $id = (int) ($_POST['id'] ?? 0);
        if (!$id)
            throw new Exception('ID requerido');

        $this->db->query(
            "DELETE FROM template_mensaje WHERE id = ? AND agencia_id = ?",
            [$id, $agencia_id]
        );

        return ['success' => true, 'message' => 'Template eliminado'];
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

        // Agentes solo ven sus propios leads — se aplica en backend, no solo en frontend
        if (($_SESSION['user_role'] ?? '') === 'agent') {
            $where[] = "p.usuario_id = ?";
            $params[] = $_SESSION['user_id'];
        }

        if (!empty($_GET['estado_id'])) {
            $where[] = "p.estado_id = ?";
            $params[] = intval($_GET['estado_id']);
        }

        if (!empty($_GET['usuario_id'])) {
            $where[] = "p.usuario_id = ?";
            $params[] = intval($_GET['usuario_id']);
        }

        if (!empty($_GET['tag_id'])) {
            $where[] = "(p.tag_id = ? OR p.tag_id2 = ?)";
            $params[] = intval($_GET['tag_id']);
            $params[] = intval($_GET['tag_id']);
        }

        if (!empty($_GET['buscar'])) {
            $where[] = "(p.nombre_cliente LIKE ? OR p.email_cliente LIKE ? OR p.destino LIKE ?)";
            $term = '%' . $_GET['buscar'] . '%';
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        // Leads vinculados a un itinerario concreto (usado desde el editor de programa)
        if (!empty($_GET['solicitud_id'])) {
            $where[] = "p.solicitud_id = ?";
            $params[] = intval($_GET['solicitud_id']);
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
                    pe.nombre   AS estado_nombre,
                    pe.posicion AS estado_posicion,
                    u.full_name AS asesor_nombre,
                    t.nombre    AS tag_nombre,
                    t2.nombre   AS tag_nombre2,
                    pp.titulo_programa AS itinerario_titulo
            FROM pipeline p
            LEFT JOIN pipeline_estados pe ON p.estado_id  = pe.id
            LEFT JOIN users            u  ON p.usuario_id = u.id
            LEFT JOIN tags             t  ON p.tag_id     = t.id
            LEFT JOIN tags             t2 ON p.tag_id2    = t2.id
            LEFT JOIN programa_personalizacion pp ON p.solicitud_id = pp.solicitud_id
            WHERE $where_sql
            ORDER BY p.created_at DESC",
            $params
        );


        return ['success' => true, 'data' => $leads];
    }

    private function get_programas()
    {
        $agencia_id = $_SESSION['agencia_id'] ?? null;
        if (!$agencia_id) {
            throw new Exception('Usuario sin agencia asignada');
        }

        $user_role = $_SESSION['user_role'] ?? 'agent';
        $user_id = $_SESSION['user_id'] ?? null;

        $where = "agencia_id = ?";
        $params = [$agencia_id];

        if ($user_role === 'agent' && $user_id) {
            $where .= " AND user_id = ?";
            $params[] = $user_id;
        }

        $programas = $this->db->fetchAll(
            "SELECT id, nombre, destino, fecha_salida
             FROM programa_solicitudes
             WHERE $where
             ORDER BY created_at DESC",
            $params
        );

        return ['success' => true, 'data' => $programas];
    }

    private function asignar_itinerario()
    {
        $agencia_id = $_SESSION['agencia_id'] ?? null;
        if (!$agencia_id) {
            throw new Exception('Usuario sin agencia asignada');
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $pipeline_id = $data['pipeline_id'] ?? null;
        $solicitud_id = $data['solicitud_id'] ?? null;

        if (!$pipeline_id)
            throw new Exception('pipeline_id requerido');
        if (!$solicitud_id)
            throw new Exception('solicitud_id requerido');

        $lead = $this->db->fetch(
            "SELECT id FROM pipeline WHERE id = ? AND agencia_id = ?",
            [$pipeline_id, $agencia_id]
        );
        if (!$lead)
            throw new Exception('Lead no encontrado o sin permisos');

        $programa = $this->db->fetch(
            "SELECT id FROM programa_solicitudes WHERE id = ? AND agencia_id = ?",
            [$solicitud_id, $agencia_id]
        );
        if (!$programa)
            throw new Exception('Programa no encontrado o sin permisos');

        $this->db->update(
            'pipeline',
            ['solicitud_id' => intval($solicitud_id)],
            'id = ?',
            [$pipeline_id]
        );

        return ['success' => true, 'message' => 'Itinerario asignado correctamente'];
    }

    // Quita el vínculo lead↔itinerario (pone solicitud_id = NULL). Como el vínculo
    // vive en pipeline.solicitud_id, esto se refleja igual en el pipeline y en el editor.
    private function desvincular_itinerario()
    {
        $agencia_id = $_SESSION['agencia_id'] ?? null;
        $user_role = $_SESSION['user_role'] ?? 'agent';
        if (!$agencia_id) {
            throw new Exception('Usuario sin agencia asignada');
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $pipeline_id = $data['pipeline_id'] ?? null;
        if (!$pipeline_id) {
            throw new Exception('pipeline_id requerido');
        }

        // El agente solo puede tocar sus leads; el admin, los de su agencia
        $where  = $user_role === 'admin' ? "id = ? AND agencia_id = ?" : "id = ? AND agencia_id = ? AND usuario_id = ?";
        $params = $user_role === 'admin' ? [$pipeline_id, $agencia_id] : [$pipeline_id, $agencia_id, $_SESSION['user_id'] ?? null];
        $lead = $this->db->fetch("SELECT id FROM pipeline WHERE {$where}", $params);
        if (!$lead) {
            throw new Exception('Lead no encontrado o sin permisos');
        }

        $this->db->update('pipeline', ['solicitud_id' => null], 'id = ?', [$pipeline_id]);

        return ['success' => true, 'message' => 'Itinerario desvinculado'];
    }

    private function editarLead()
    {
        $agencia_id = $_SESSION['agencia_id'] ?? null;
        $user_role = $_SESSION['user_role'] ?? 'agent';
        if (!$agencia_id)
            throw new Exception('Usuario sin agencia asignada');

        $data = json_decode(file_get_contents('php://input'), true);
        $pipeline_id = $data['pipeline_id'] ?? null;
        if (!$pipeline_id)
            throw new Exception('pipeline_id requerido');

        $where = $user_role === 'admin' ? "id = ? AND agencia_id = ?" : "id = ? AND agencia_id = ? AND usuario_id = ?";
        $params = $user_role === 'admin' ? [$pipeline_id, $agencia_id] : [$pipeline_id, $agencia_id, $_SESSION['user_id']];
        $lead = $this->db->fetch("SELECT id FROM pipeline WHERE $where", $params);
        if (!$lead)
            throw new Exception('Lead no encontrado o sin permisos');

        $nombre = trim($data['nombre_cliente'] ?? '');
        $email = trim($data['email_cliente'] ?? '');
        $destino = trim($data['destino'] ?? '');
        if (!$nombre)
            throw new Exception('El nombre no puede estar vacío');
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL))
            throw new Exception('Email inválido');
        if (!$destino)
            throw new Exception('El destino no puede estar vacío');

        $this->db->update('pipeline', [
            'nombre_cliente' => $nombre,
            'email_cliente' => $email,
            'telefono_cliente' => trim($data['telefono_cliente'] ?? '') ?: null,
            'destino' => $destino,
            'fecha_salida' => $data['fecha_salida'] ?: null,
            'fecha_llegada' => $data['fecha_llegada'] ?: null,
            'viajeros' => intval($data['viajeros'] ?? 1),
            'budget' => $data['budget'] !== '' && $data['budget'] !== null ? floatval($data['budget']) : null,
            'source' => intval($data['source'] ?? '') ?: null,
            'descripcion' => trim($data['descripcion'] ?? '') ?: null,
        ], 'id = ?', [$pipeline_id]);

        return ['success' => true, 'message' => 'Lead actualizado'];
    }

    private function asignarTag()
    {
        $agencia_id = $_SESSION['agencia_id'] ?? null;
        $user_role = $_SESSION['user_role'] ?? 'agent';
        if (!$agencia_id)
            throw new Exception('Usuario sin agencia asignada');

        $data = json_decode(file_get_contents('php://input'), true);
        $pipeline_id = $data['pipeline_id'] ?? null;
        if (!$pipeline_id)
            throw new Exception('pipeline_id requerido');

        // Un agente solo puede etiquetar SUS leads (antes solo filtraba por agencia → IDOR)
        $where  = $user_role === 'admin' ? "id = ? AND agencia_id = ?" : "id = ? AND agencia_id = ? AND usuario_id = ?";
        $params = $user_role === 'admin' ? [$pipeline_id, $agencia_id] : [$pipeline_id, $agencia_id, $_SESSION['user_id'] ?? null];
        $lead = $this->db->fetch("SELECT id FROM pipeline WHERE {$where}", $params);
        if (!$lead)
            throw new Exception('Lead no encontrado');

        $this->db->update(
            'pipeline',
            [
                'tag_id' => !empty($data['tag_id']) ? intval($data['tag_id']) : null,
                'tag_id2' => !empty($data['tag_id2']) ? intval($data['tag_id2']) : null,
            ],
            'id = ?',
            [$pipeline_id]
        );

        return ['success' => true, 'message' => 'Tags actualizados'];
    }

    private function getMensajes()
    {
        $agencia_id = $_SESSION['agencia_id'] ?? null;
        $pipeline_id = $_GET['pipeline_id'] ?? null;

        if (!$pipeline_id)
            throw new Exception('pipeline_id requerido');

        // Verificar que el lead pertenece a esta agencia (y al agente si aplica)
        $whereOwn = "id = ? AND agencia_id = ?";
        $paramsOwn = [$pipeline_id, $agencia_id];
        if (($_SESSION['user_role'] ?? '') === 'agent') {
            $whereOwn .= " AND usuario_id = ?";
            $paramsOwn[] = $_SESSION['user_id'];
        }
        $lead = $this->db->fetch("SELECT id FROM pipeline WHERE $whereOwn", $paramsOwn);
        if (!$lead)
            throw new Exception('Lead no encontrado o sin permisos');

        $mensajes = $this->db->fetchAll(
            "SELECT id, from_email, to_email, subject, body, direction,
                    received_at, created_at
             FROM email_messages
             WHERE pipeline_id = ? AND agency_id = ?
             ORDER BY COALESCE(received_at, created_at) ASC",
            [$pipeline_id, $agencia_id]
        );

        // Sanear el body para mostrarlo de forma segura
        foreach ($mensajes as &$m) {
            $m['body_plain'] = trim(strip_tags($m['body'] ?? ''));
        }
        unset($m);

        return ['success' => true, 'data' => $mensajes];
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
