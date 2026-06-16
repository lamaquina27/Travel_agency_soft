<?php
// ====================================================================
// ARCHIVO: modules/programa/archivos_api.php
// PROPÓSITO: API CRUD de adjuntos (archivos y enlaces) de un programa
//            Tabla: programa_adjuntos
// ====================================================================

ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/app.php';

App::init();
App::requireLogin();

class ProgramaArchivosAPI
{
    private $db;

    // Whitelist de tipos permitidos para archivos subidos
    private $allowedTypes = [
        'application/pdf',
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/webp',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // .docx
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',        // .xlsx
    ];
    private $maxFileSize = 10 * 1024 * 1024; // 10 MB

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
            error_log("=== PROGRAMA ARCHIVOS API ===");
            error_log("Action: " . $action);

            switch ($action) {
                case 'get':
                    // Listar adjuntos de un programa
                    $result = $this->getArchivos($_GET['programa_id'] ?? null);
                    break;

                case 'save':
                    // Subir archivo(s) y/o guardar un enlace
                    $result = $this->saveArchivos();
                    break;

                case 'delete':
                    // Eliminar un adjunto por su id
                    $result = $this->deleteArchivo($_POST['id'] ?? null);
                    break;

                case 'update_titulo':
                    // Cambiar el título (nombre legible) de un adjunto
                    $result = $this->actualizarTitulo($_POST['id'] ?? null, $_POST['titulo'] ?? '');
                    break;

                default:
                    throw new Exception('Acción no válida: ' . $action);
            }

            echo json_encode($result, JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            error_log("Error en Archivos API: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $this->sendError($e->getMessage());
        }
    }

    // ================================================================
    // GET — listar adjuntos del programa
    // ================================================================
    private function getArchivos($programaId)
    {
        $this->verificarPermiso($programaId);
        $solicitud_id = $_GET['programa_id'];

        $data = $this->db->fetchAll(
            "SELECT * FROM programa_adjuntos
            WHERE solicitud_id = ? ORDER BY created_at DESC",
            [$solicitud_id]
        );

        return [
            'success' => true,
            'data' => $data
        ];
    }

    // ================================================================
    // SAVE — subir archivo(s) y/o guardar enlace
    // ================================================================
    private function saveArchivos()
    {
        $programaId = $_POST['programa_id'] ?? null;
        $this->verificarPermiso($programaId);
        $archivos = $_FILES['archivos'] ?? null;
        $enlace = trim($_POST['enlace'] ?? '');


        $titulo = trim($_POST['titulo'] ?? '');

        if ($enlace && filter_var($enlace, FILTER_VALIDATE_URL)) {
            $datos = [
                'solicitud_id' => $programaId,
                'enlace' => $enlace,
            ];
            // El título solo se guarda si ya se corrió la migración 030 (columna presente).
            if ($titulo !== '' && $this->tieneColumnaTitulo()) {
                $datos['titulo'] = mb_substr($titulo, 0, 255);
            }
            $id = $this->db->insert('programa_adjuntos', $datos);
            if (!$id) {
                throw new Exception('Error al insertar en base de datos');
            }
        }

        if (!empty($_FILES['archivos']) && is_array($_FILES['archivos']['name'])) {
            $files = $_FILES['archivos'];
            $total = count($files['name']);

            for ($i = 0; $i < $total; $i++) {
                if ($files['error'][$i] !== UPLOAD_ERR_OK)
                    continue;

                $tmp = $files['tmp_name'][$i];
                $nombre = $files['name'][$i];
                $tamano = $files['size'][$i];

                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $tmp);
                finfo_close($finfo);

                if (!in_array($mime, $this->allowedTypes) || $tamano > $this->maxFileSize) {
                    continue;
                }

                $ruta = $this->generarRutaSubida($programaId, $nombre);
                if (!move_uploaded_file($tmp, $ruta['full_path'])) {
                    throw new Exception("No se pudo mover el archivo: $nombre");
                }

                $this->db->insert('programa_adjuntos', [
                    'solicitud_id' => $programaId,
                    'archivo' => $ruta['url'],
                ]);
            }
        }

        return [
            'success' => true,
            'message' => 'Adjunto(s) guardado(s)'
        ];
    }

    // ================================================================
    // DELETE — eliminar un adjunto por id
    // ================================================================
    private function deleteArchivo($id)
    {
        if (!$id) {
            throw new Exception('ID de adjunto requerido');
        }


        $data = $this->db->fetch("SELECT * FROM programa_adjuntos WHERE id = ?", [$id]);
        if ($data) {
            $this->verificarPermiso($data['solicitud_id']);
            $id_eliminado = $this->db->delete("programa_adjuntos", " id = ? AND solicitud_id = ?", [$id, $data['solicitud_id']]);

            $this->borrarArchivoFisico($data['archivo']);
        }

        return [
            'success' => true,
            'message' => 'Adjunto eliminado'
        ];
    }

    // ================================================================
    // UPDATE — cambiar el título de un adjunto
    // ================================================================
    private function actualizarTitulo($id, $titulo)
    {
        if (!$id) {
            throw new Exception('ID de adjunto requerido');
        }
        if (!$this->tieneColumnaTitulo()) {
            throw new Exception('La función de títulos requiere correr la migración 030.');
        }

        $data = $this->db->fetch("SELECT solicitud_id FROM programa_adjuntos WHERE id = ?", [$id]);
        if (!$data) {
            throw new Exception('Adjunto no encontrado');
        }
        $this->verificarPermiso($data['solicitud_id']); // dueño o agencia

        $titulo = mb_substr(trim((string) $titulo), 0, 255);
        $this->db->update(
            'programa_adjuntos',
            ['titulo' => ($titulo !== '' ? $titulo : null)],
            'id = ? AND solicitud_id = ?',
            [$id, $data['solicitud_id']]
        );

        return ['success' => true, 'message' => 'Título actualizado', 'titulo' => $titulo];
    }

    /**
     * ¿Existe la columna `titulo`? (migración 030). Se memoiza para no consultar
     * el esquema en cada operación. Permite que subir archivos/enlaces siga
     * funcionando aunque la migración aún no se haya corrido.
     */
    private function tieneColumnaTitulo()
    {
        static $existe = null;
        if ($existe === null) {
            try {
                $col = $this->db->fetchAll("SHOW COLUMNS FROM programa_adjuntos LIKE 'titulo'");
                $existe = !empty($col);
            } catch (Exception $e) {
                $existe = false;
            }
        }
        return $existe;
    }

    // ================================================================
    // HELPERS
    // ================================================================

    /**
     * Verifica que el programa exista y pertenezca al usuario/agencia en sesión.
     * Lanza excepción si no hay permiso. Devuelve la fila del programa.
     */
    private function verificarPermiso($programaId)
    {
        if (!$programaId) {
            throw new Exception('ID de programa requerido');
        }

        $user_id = $_SESSION['user_id'];
        $agencia_id = $_SESSION['agencia_id'] ?? null;

        if (!$agencia_id) {
            throw new Exception('Usuario sin agencia asignada');
        }

        $programa = $this->db->fetch(
            "SELECT id FROM programa_solicitudes WHERE id = ? AND user_id = ? AND agencia_id = ?",
            [$programaId, $user_id, $agencia_id]
        );

        if (!$programa) {
            throw new Exception('Programa no encontrado o sin permisos');
        }

        return $programa;
    }

    /**
     * Genera la ruta física y la URL pública para un archivo subido.
     * Mismo patrón que upload_images.php → /assets/uploads/agencia_{id}/programa/adjuntos/{año}/{mes}/
     */
    private function generarRutaSubida($programaId, $nombreOriginal)
    {
        $agencia_id = $_SESSION['agencia_id'];
        $year = date('Y');
        $month = date('m');

        // Subcarpeta única por subida → permite conservar el nombre original
        // sin riesgo de colisión si suben dos archivos con el mismo nombre.
        $token = time() . '_' . mt_rand(1000, 9999);

        $baseDir = dirname(__DIR__, 2) . '/assets/uploads/agencia_' . $agencia_id . '/programa/adjuntos';
        $dir = $baseDir . '/' . $year . '/' . $month . '/' . $token;

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Nombre original limpio (sin rutas), conservando acentos/espacios
        $filename = basename($nombreOriginal);
        $filename = preg_replace('/[\/\\\\:*?"<>|]+/', '_', $filename);

        return [
            'full_path' => $dir . '/' . $filename,
            // En la URL se codifica el nombre (espacios/acentos); el front lo decodifica para mostrarlo
            'url' => APP_URL . '/assets/uploads/agencia_' . $agencia_id . '/programa/adjuntos/' . $year . '/' . $month . '/' . $token . '/' . rawurlencode($filename),
        ];
    }

    /**
     * Elimina del disco un archivo dado su URL pública (solo dentro de /assets/uploads/).
     */
    private function borrarArchivoFisico($url)
    {
        if (empty($url))
            return false;

        $path = parse_url($url, PHP_URL_PATH);
        // La URL guarda el nombre codificado (espacios/acentos); decodificar para hallar el archivo real
        $path = $path ? rawurldecode($path) : $path;
        $pos = $path ? strpos($path, '/assets/uploads/') : false;
        if ($pos === false)
            return false;

        $fullPath = dirname(__DIR__, 2) . substr($path, $pos);
        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }
        return false;
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

// Instanciar y ejecutar API
$api = new ProgramaArchivosAPI();
$api->handleRequest();