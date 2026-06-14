<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/app.php';

App::init();
App::requireLogin();

class TagsAPI
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
                case 'get_tags_programa':
                    $result = $this->getTagsPrograma();
                    break;
                case 'save_tags_programa':
                    $result = $this->saveTagsPrograma();
                    break;
                default:
                    throw new Exception('Acción no válida: ' . $action);
            }

            echo json_encode($result, JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            $this->sendError($e->getMessage());
        }
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
            WHERE agencia_id = ? AND  tipo = 'itinerario'
            ORDER BY id ASC",
            [$agencia_id]
        );

        return ['success' => true, 'data' => $tags];
    }
    private function getTagsPrograma()
    {
        $agencia_id = $_SESSION['agencia_id'] ?? null;
        $solicitud_id = $_GET['programa_id'] ?? null;
        if (!$agencia_id) {
            throw new Exception('Usuario sin agencia asignada');
        }

        $tags = $this->db->fetchAll(
            "SELECT t.id, t.nombre
            FROM tags t
            JOIN itinerario_tags ts ON ts.tag_id = t.id
            WHERE t.agencia_id = ? AND  t.tipo = 'itinerario' AND ts.solicitud_id=?
            ORDER BY t.id ASC",
            [$agencia_id, $solicitud_id]
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

        $nombre = trim($data['nombre'] ?? '');
        if ($nombre === '') {
            throw new Exception('El nombre del tag es obligatorio');
        }

        try {
            $this->db->insert(
                'tags',
                [
                    'agencia_id' => $agencia_id,
                    'nombre' => $nombre,
                    'tipo' => 'itinerario'
                ]
            );
        } catch (Exception $e) {
            if (strpos($e->getMessage(), '1062') !== false || stripos($e->getMessage(), 'Duplicate') !== false) {
                throw new Exception('Ya existe un tag con ese nombre');
            }
            throw $e;
        }
        return ['success' => true, 'mensaje' => 'tags guardado exitosamente'];
    }
    private function saveTagsPrograma()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data)
            throw new Exception('Datos inválidos o vacíos');

        $solicitud_id = intval($data['solicitud_id'] ?? 0);
        $this->verificarPermiso($solicitud_id);   // dueño o admin

        // Recibir la lista y sanearla a enteros válidos
        $tagIds = $data['tag_id'] ?? [];
        if (!is_array($tagIds))
            $tagIds = [];
        $tagIds = array_values(array_unique(array_filter(array_map('intval', $tagIds))));

        // (Opcional, recomendado) dejar solo tags que sean de la agencia y de itinerario
        $agencia_id = $_SESSION['agencia_id'];
        if ($tagIds) {
            $ph = implode(',', array_fill(0, count($tagIds), '?'));
            $validos = $this->db->fetchAll(
                "SELECT id FROM tags WHERE id IN ($ph) AND agencia_id = ? AND tipo = 'itinerario'",
                array_merge($tagIds, [$agencia_id])
            );
            $tagIds = array_map(fn($r) => (int) $r['id'], $validos);
        }

        // Reemplazar: borrar los actuales e insertar los nuevos
        $this->db->delete('itinerario_tags', 'solicitud_id = ?', [$solicitud_id]);
        foreach ($tagIds as $tid) {
            $this->db->insert('itinerario_tags', [
                'solicitud_id' => $solicitud_id,
                'tag_id' => $tid,
            ]);
        }

        return ['success' => true, 'mensaje' => 'Tags del tour actualizados'];
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
        if (empty($data['id'])) {
            throw new Exception('ID requerido');
        }

        $nombre = trim($data['nombre'] ?? '');
        if ($nombre === '') {
            throw new Exception('El nombre del tag es obligatorio');
        }

        try {
            $this->db->update(
                'tags',
                [
                    'nombre' => $nombre,
                ],
                'agencia_id = ? AND id = ? AND tipo = \'itinerario\'',
                [$agencia_id, $data['id']]
            );
        } catch (Exception $e) {
            if (strpos($e->getMessage(), '1062') !== false || stripos($e->getMessage(), 'Duplicate') !== false) {
                throw new Exception('Ya existe un tag con ese nombre');
            }
            throw $e;
        }
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
            'id = ? AND agencia_id = ? AND tipo = \'itinerario\'',
            [$data['id'], $agencia_id]
        );
        return ['success' => true, 'mensaje' => 'tags eliminados exitosamente'];
    }

    private function verificarPermiso($programaId)
    {
        if (!$programaId) {
            throw new Exception('ID de programa requerido');
        }
        $es_admin = ($_SESSION['user_role'] ?? '') === 'admin';
        $user_id = $_SESSION['user_id'];
        $agencia_id = $_SESSION['agencia_id'] ?? null;

        if (!$agencia_id) {
            throw new Exception('Usuario sin agencia asignada');
        }

        if ($es_admin) {
            $programa = $this->db->fetch(
                "SELECT id FROM programa_solicitudes WHERE id = ? AND agencia_id = ?",
                [$programaId, $agencia_id]
            );
        } else {
            $programa = $this->db->fetch(
                "SELECT id FROM programa_solicitudes WHERE id = ? AND user_id = ? AND agencia_id = ?",
                [$programaId, $user_id, $agencia_id]
            );
        }

        if (!$programa) {
            throw new Exception('Programa no encontrado o sin permisos');
        }

        return $programa;
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

$api = new TagsAPI();
$api->handleRequest();
