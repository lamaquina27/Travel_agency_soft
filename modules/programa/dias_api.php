<?php
// ====================================================================
// ARCHIVO: modules/programa/dias_api.php - FIX CONSTRAINT UNIQUE
// ====================================================================
// SOLUCIÓN: Usar números temporales negativos para evitar duplicados
// ====================================================================

ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/classes/FechaCalculator.php';
require_once __DIR__ . '/upload_images.php';

App::init();
App::requireLogin();

class ProgramaDiasAPI
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
        $input = json_decode(file_get_contents('php://input'), true);

        if ($input) {
            $_POST = array_merge($_POST, $input);
            $action = $action ?: ($input['action'] ?? '');
        }

        try {
            error_log("=== PROGRAMA DÍAS API (FIX UNIQUE) ===");
            error_log("Action: " . $action);

            switch ($action) {
                case 'list':
                    $result = $this->listDias($_GET['programa_id'] ?? $_POST['programa_id'] ?? null);
                    break;

                case 'add_from_biblioteca':
                    // Decodificar el JSON del body
                    $input = json_decode(file_get_contents('php://input'), true);

                    $programaId = $input['programa_id'] ?? null;
                    $bibliotecaDiaId = $input['biblioteca_dia_id'] ?? null;
                    $diaNumeroEspecifico = $input['dia_numero'] ?? null; // ← NUEVO PARÁMETRO

                    error_log("📥 add_from_biblioteca recibido:");
                    error_log("   - programa_id: $programaId");
                    error_log("   - biblioteca_dia_id: $bibliotecaDiaId");
                    error_log("   - dia_numero: " . ($diaNumeroEspecifico ?? 'auto'));

                    if (!$programaId || !$bibliotecaDiaId) {
                        throw new Exception('Faltan parámetros requeridos');
                    }

                    // ✨ PASAR EL NÚMERO ESPECÍFICO A LA FUNCIÓN
                    $result = $this->addDiaFromBiblioteca(
                        $programaId,
                        $bibliotecaDiaId,
                        $diaNumeroEspecifico  // ← NUEVO PARÁMETRO
                    );

                    error_log("✅ Resultado: " . json_encode($result));
                    break;

                case 'delete':
                    $result = $this->deleteDia($_POST['dia_id'] ?? null);
                    break;

                case 'get_ubicaciones_secundarias':
                    $result = $this->getUbicacionesSecundarias($_GET['dia_id'] ?? null);
                    break;

                case 'update':
                    $diaId = $_POST['dia_id'] ?? null;
                    $updateData = $_POST['data'] ?? [];

                    error_log("🔍 Case UPDATE - dia_id recibido: " . var_export($diaId, true));
                    error_log("🔍 Case UPDATE - POST completo: " . json_encode($_POST, JSON_UNESCAPED_UNICODE));

                    if (!$diaId) {
                        throw new Exception('ID de día requerido (recibido: ' . var_export($diaId, true) . ')');
                    }

                    $result = $this->updateDia($diaId, $updateData);
                    break;

                case 'reorder':
                    $result = $this->reorderDias(
                        $_POST['solicitud_id'] ?? null,
                        $_POST['nuevo_orden'] ?? []
                    );
                    break;

                case 'cambiar_estancia':
                    $result = $this->cambiarEstancia($_POST['dia_id'] ?? null, $_POST['duracion'] ?? null);
                    break;

                case 'update_comidas':
                    $result = $this->updateComidas($_POST['dia_id'] ?? null, $_POST);
                    break;

                case 'get_comidas':
                    $result = $this->getComidas($_GET['dia_id'] ?? null);
                    break;

                default:
                    throw new Exception('Acción no válida: ' . $action);
            }

            echo json_encode($result, JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            error_log("❌ Error en Días API: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $this->sendError($e->getMessage());
        }
    }

    private function listDias($programaId)
    {
        if (!$programaId) {
            throw new Exception('ID de programa requerido');
        }

        try {
            $user_id = $_SESSION['user_id'];
            $agencia_id = $_SESSION['agencia_id'] ?? null;

            if (!$agencia_id) {
                throw new Exception('Usuario sin agencia asignada');
            }

            $programa = $this->db->fetch(
                "SELECT id, fecha_llegada FROM programa_solicitudes WHERE id = ? AND user_id = ? AND agencia_id = ?",
                [$programaId, $user_id, $agencia_id]
            );


            if (!$programa) {
                throw new Exception('Programa no encontrado o sin permisos');
            }

            $dias = $this->db->fetchAll(
                "SELECT *, COALESCE(duracion_estancia, 1) as duracion_estancia 
                FROM programa_dias 
                WHERE solicitud_id = ? 
                ORDER BY dia_numero ASC",
                [$programaId]
            );

            $dias = FechaCalculator::calcularFechasDias($dias, $programa['fecha_llegada']);



            return [
                'success' => true,
                'data' => $dias
            ];

        } catch (Exception $e) {
            error_log("Error en listDias: " . $e->getMessage());
            throw $e;
        }
    }

    // ✅ REORDER CON SOLUCIÓN AL CONSTRAINT UNIQUE
    private function reorderDias($programaId, $nuevoOrden)
    {
        if (!$programaId || !is_array($nuevoOrden) || empty($nuevoOrden)) {
            throw new Exception('ID de programa y nuevo orden requeridos');
        }

        try {
            $user_id = $_SESSION['user_id'];
            $agencia_id = $_SESSION['agencia_id'] ?? null;

            if (!$agencia_id) {
                throw new Exception('Usuario sin agencia asignada');
            }

            error_log("🔄 REORDER (FIX UNIQUE CONSTRAINT)");
            error_log("   Programa: $programaId | Usuario: $user_id | Agencia: $agencia_id");
            error_log("   Nuevo orden: " . json_encode($nuevoOrden));

            // Verificar permisos
            $programa = $this->db->fetch(
                "SELECT id FROM programa_solicitudes WHERE id = ? AND user_id = ? AND agencia_id = ?",
                [$programaId, $user_id, $agencia_id]
            );

            if (!$programa) {
                throw new Exception('Programa no encontrado o sin permisos');
            }

            // Obtener conexión PDO
            $pdo = $this->db->getConnection();
            $pdo->beginTransaction();

            error_log("📦 Transacción iniciada");

            try {
                // ⚡ ESTRATEGIA: Usar números NEGATIVOS temporalmente para evitar duplicados

                // PASO 1: Cambiar TODOS los días a números negativos (temporales)
                error_log("   PASO 1: Asignando números temporales negativos...");
                $tempStmt = $pdo->prepare("UPDATE programa_dias SET dia_numero = -id WHERE solicitud_id = ?");
                $tempStmt->execute([$programaId]);
                error_log("   ✅ Números temporales asignados");

                // PASO 2: Ahora asignar los números finales (ya no hay conflicto)
                error_log("   PASO 2: Asignando números finales...");
                $updateStmt = $pdo->prepare("UPDATE programa_dias SET dia_numero = ? WHERE id = ?");

                foreach ($nuevoOrden as $index => $diaId) {
                    $nuevoDiaNumero = $index + 1;

                    error_log("      → Día ID=$diaId → Posición $nuevoDiaNumero");

                    // Verificar que existe
                    $checkStmt = $pdo->prepare("SELECT id FROM programa_dias WHERE id = ? AND solicitud_id = ?");
                    $checkStmt->execute([$diaId, $programaId]);

                    if (!$checkStmt->fetch()) {
                        throw new Exception("Día ID=$diaId no encontrado");
                    }

                    // Asignar número final (sin conflicto porque todos son negativos)
                    $updateStmt->execute([$nuevoDiaNumero, $diaId]);
                    error_log("      ✅ Actualizado");
                }

                $pdo->commit();
                error_log("✅ COMMIT exitoso - Reordenamiento completado");

                return [
                    'success' => true,
                    'message' => 'Días reordenados correctamente',
                    'nuevo_orden' => $nuevoOrden
                ];

            } catch (Exception $e) {
                $pdo->rollback();
                error_log("❌ ROLLBACK: " . $e->getMessage());
                throw $e;
            }

        } catch (Exception $e) {
            error_log("❌ Error FATAL: " . $e->getMessage());
            throw $e;
        }
    }


    private function addDiaFromBiblioteca($programaId, $bibliotecaDiaId, $diaNumeroEspecifico = null)
    {
        if (!$programaId || !$bibliotecaDiaId) {
            throw new Exception('ID de programa y día de biblioteca requeridos');
        }

        try {
            $user_id = $_SESSION['user_id'];
            $agencia_id = $_SESSION['agencia_id'] ?? null;

            if (!$agencia_id) {
                throw new Exception('Usuario sin agencia asignada');
            }

            // Verificar programa
            $programa = $this->db->fetch(
                "SELECT id FROM programa_solicitudes 
             WHERE id = ? AND user_id = ? AND agencia_id = ?",
                [$programaId, $user_id, $agencia_id]
            );

            if (!$programa) {
                throw new Exception('Programa no encontrado o sin permisos');
            }

            // Verificar día de biblioteca
            $diaBiblioteca = $this->db->fetch(
                "SELECT * FROM biblioteca_dias 
             WHERE id = ? AND agencia_id = ? AND activo = 1",
                [$bibliotecaDiaId, $agencia_id]
            );

            if (!$diaBiblioteca) {
                throw new Exception('Día de biblioteca no encontrado');
            }

            // ⚡ USAR TRANSACCIÓN PARA EVITAR RACE CONDITIONS
            $pdo = $this->db->getConnection();
            $pdo->beginTransaction();

            try {
                // 🔒 OBTENER ÚLTIMO NÚMERO CON LOCK
                $stmt = $pdo->prepare(
                    "SELECT MAX(dia_numero) as max_dia 
                 FROM programa_dias 
                 WHERE solicitud_id = ? 
                 FOR UPDATE"
                );
                $stmt->execute([$programaId]);
                $ultimoDia = $stmt->fetch(PDO::FETCH_ASSOC);

                // ✨ DETERMINAR EL NÚMERO DE DÍA
                if ($diaNumeroEspecifico !== null && $diaNumeroEspecifico !== '') {
                    $nuevoDiaNumero = (int) $diaNumeroEspecifico;
                    error_log("✅ Usando número específico: $nuevoDiaNumero");
                } else {
                    $nuevoDiaNumero = ($ultimoDia['max_dia'] ?? 0) + 1;
                    error_log("✅ Calculado automáticamente: $nuevoDiaNumero");
                }

                // ⚠️ VERIFICAR SI YA EXISTE (DOBLE VERIFICACIÓN)
                $stmt = $pdo->prepare(
                    "SELECT id FROM programa_dias 
                 WHERE solicitud_id = ? AND dia_numero = ?"
                );
                $stmt->execute([$programaId, $nuevoDiaNumero]);
                $existente = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($existente) {
                    error_log("⚠️ Número $nuevoDiaNumero duplicado, recalculando...");

                    // Recalcular automáticamente el siguiente disponible
                    $stmt = $pdo->prepare(
                        "SELECT MAX(dia_numero) as max_dia 
                     FROM programa_dias 
                     WHERE solicitud_id = ?"
                    );
                    $stmt->execute([$programaId]);
                    $ultimoDia = $stmt->fetch(PDO::FETCH_ASSOC);
                    $nuevoDiaNumero = ($ultimoDia['max_dia'] ?? 0) + 1;

                    error_log("✅ Nuevo número recalculado: $nuevoDiaNumero");
                }

                // Crear datos del nuevo día
                $nuevoDiaData = [
                    'solicitud_id' => $programaId,
                    'dia_numero' => $nuevoDiaNumero,
                    'titulo' => $diaBiblioteca['titulo'],
                    'descripcion' => $diaBiblioteca['descripcion'],
                    'ubicacion' => $diaBiblioteca['ubicacion'],
                    'latitud' => $diaBiblioteca['latitud'],
                    'longitud' => $diaBiblioteca['longitud'],
                    'duracion_estancia' => $diaBiblioteca['duracion_estancia'] ?? 1,
                    'biblioteca_dia_id' => $bibliotecaDiaId,
                    'imagen1' => $diaBiblioteca['imagen1'],
                    'imagen2' => $diaBiblioteca['imagen2'],
                    'imagen3' => $diaBiblioteca['imagen3']
                ];

                error_log("📝 Insertando día con número: $nuevoDiaNumero");

                $nuevoDiaId = $this->db->insert('programa_dias', $nuevoDiaData);

                if (!$nuevoDiaId) {
                    throw new Exception('Error al insertar día');
                }

                error_log("✅ Día insertado con ID: $nuevoDiaId");

                // Copiar ubicaciones secundarias
                $this->copiarUbicacionesSecundarias($bibliotecaDiaId, $nuevoDiaId, $agencia_id);

                // Actualizar fecha de salida
                $this->actualizarFechaSalida($programaId);

                $pdo->commit();  // ✅ CONFIRMAR TRANSACCIÓN

                return [
                    'success' => true,
                    'dia_id' => $nuevoDiaId,
                    'dia_numero' => $nuevoDiaNumero,
                    'message' => "Día agregado exitosamente como día #$nuevoDiaNumero"
                ];

            } catch (Exception $e) {
                $pdo->rollback();  // ❌ REVERTIR SI HAY ERROR
                error_log("❌ Error en transacción: " . $e->getMessage());
                throw $e;
            }

        } catch (Exception $e) {
            error_log("❌ Error en addDiaFromBiblioteca: " . $e->getMessage());
            throw $e;
        }
    }

    private function copiarUbicacionesSecundarias($bibliotecaDiaId, $programaDiaId, $agencia_id)
    {
        try {
            error_log("📍 Copiando ubicaciones secundarias: Biblioteca Día ID=$bibliotecaDiaId → Programa Día ID=$programaDiaId");

            // Obtener ubicaciones secundarias del día de biblioteca
            $ubicacionesBiblioteca = $this->db->fetchAll(
                "SELECT ubicacion, latitud, longitud, orden 
                FROM biblioteca_dias_ubicaciones_secundarias 
                WHERE dia_id = ? AND agencia_id = ? 
                ORDER BY orden ASC",
                [$bibliotecaDiaId, $agencia_id]
            );

            if (empty($ubicacionesBiblioteca)) {
                error_log("   ℹ️  No hay ubicaciones secundarias para copiar");
                return;
            }

            error_log("   📋 Encontradas " . count($ubicacionesBiblioteca) . " ubicaciones secundarias");

            // Insertar cada ubicación secundaria en la tabla del programa
            foreach ($ubicacionesBiblioteca as $ubicacion) {
                $ubicacionData = [
                    'programa_dia_id' => $programaDiaId,
                    'ubicacion' => $ubicacion['ubicacion'],
                    'latitud' => $ubicacion['latitud'],
                    'longitud' => $ubicacion['longitud'],
                    'orden' => $ubicacion['orden']
                ];

                $insertId = $this->db->insert('programa_dias_ubicaciones_secundarias', $ubicacionData);

                if ($insertId) {
                    error_log("   ✅ Ubicación copiada: {$ubicacion['ubicacion']} (ID: $insertId)");
                } else {
                    error_log("   ⚠️ Error copiando ubicación: {$ubicacion['ubicacion']}");
                }
            }

            error_log("✅ Ubicaciones secundarias copiadas exitosamente");

        } catch (Exception $e) {
            error_log("❌ Error copiando ubicaciones secundarias: " . $e->getMessage());
            // No lanzamos excepción para no interrumpir el proceso principal
        }
    }

    private function deleteDia($diaId)
    {
        if (!$diaId) {
            throw new Exception('ID de día requerido');
        }

        try {
            $user_id = $_SESSION['user_id'];
            $agencia_id = $_SESSION['agencia_id'] ?? null;

            if (!$agencia_id) {
                throw new Exception('Usuario sin agencia asignada');
            }

            $dia = $this->db->fetch(
                "SELECT pd.*, ps.user_id, ps.id as programa_id 
                 FROM programa_dias pd 
                 JOIN programa_solicitudes ps ON pd.solicitud_id = ps.id 
                 WHERE pd.id = ? AND ps.user_id = ? AND ps.agencia_id = ?",
                [$diaId, $user_id, $agencia_id]
            );

            if (!$dia) {
                throw new Exception('Día no encontrado o sin permisos');
            }

            $this->db->query(
                "DELETE FROM programa_dias_servicios WHERE programa_dia_id = ?",
                [$diaId]
            );

            $deleted = $this->db->delete('programa_dias', 'id = ?', [$diaId]);

            if (!$deleted) {
                throw new Exception('Error al eliminar día');
            }
            
            // Eliminar imágenes físicas
            if (!empty($dia['imagen1'])) ProgramaImageUploader::deletePhysicalImage($dia['imagen1']);
            if (!empty($dia['imagen2'])) ProgramaImageUploader::deletePhysicalImage($dia['imagen2']);
            if (!empty($dia['imagen3'])) ProgramaImageUploader::deletePhysicalImage($dia['imagen3']);

            $this->reorderDiasAfterDelete($dia['programa_id'], $dia['dia_numero']);

            // Actualizar fecha de salida
            $this->actualizarFechaSalida($dia['programa_id']);

            return [
                'success' => true,
                'message' => 'Día eliminado exitosamente'
            ];

        } catch (Exception $e) {
            error_log("Error en deleteDia: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Actualizar día del programa
     */
    private function updateDia($diaId, $data)
    {
        // ✅ Intentar obtener dia_id desde múltiples lugares
        if (!$diaId && isset($data['dia_id'])) {
            $diaId = $data['dia_id'];
            unset($data['dia_id']);
        }

        // Convertir a entero
        $diaId = (int) $diaId;

        if (!$diaId || $diaId <= 0) {
            error_log("❌ dia_id inválido: " . var_export($diaId, true));
            error_log("❌ data recibido: " . json_encode($data, JSON_UNESCAPED_UNICODE));
            throw new Exception('ID de día requerido');
        }

        try {
            error_log("📝 Actualizando día ID: $diaId");
            error_log("📋 Datos recibidos: " . json_encode($data, JSON_UNESCAPED_UNICODE));

            $user_id = $_SESSION['user_id'];
            $agencia_id = $_SESSION['agencia_id'] ?? null;

            if (!$agencia_id) {
                throw new Exception('Usuario sin agencia asignada');
            }

            // Verificar permisos
            $dia = $this->db->fetch(
                "SELECT pd.*, ps.user_id 
             FROM programa_dias pd 
             JOIN programa_solicitudes ps ON pd.solicitud_id = ps.id 
             WHERE pd.id = ? AND ps.user_id = ? AND ps.agencia_id = ?",
                [$diaId, $user_id, $agencia_id]
            );

            if (!$dia) {
                throw new Exception('Día no encontrado o sin permisos');
            }

            // Preparar datos para actualizar
            $updateData = [];

            if (isset($data['titulo'])) {
                $updateData['titulo'] = trim($data['titulo']);
            }
            if (isset($data['descripcion'])) {
                $updateData['descripcion'] = trim($data['descripcion']);
            }
            if (isset($data['ubicacion'])) {
                $updateData['ubicacion'] = trim($data['ubicacion']);
            }
            if (isset($data['latitud'])) {
                $updateData['latitud'] = $data['latitud'] ?: null;
            }
            if (isset($data['longitud'])) {
                $updateData['longitud'] = $data['longitud'] ?: null;
            }
            // ✅ Campos de imágenes del día
            if (isset($data['imagen1'])) {
                $updateData['imagen1'] = $data['imagen1'] ?: null;
            }
            if (isset($data['imagen2'])) {
                $updateData['imagen2'] = $data['imagen2'] ?: null;
            }
            if (isset($data['imagen3'])) {
                $updateData['imagen3'] = $data['imagen3'] ?: null;
            }

            error_log("🔄 Campos a actualizar: " . json_encode($updateData, JSON_UNESCAPED_UNICODE));

            // Actualizar día
            if (!empty($updateData)) {
                $updated = $this->db->update(
                    'programa_dias',
                    $updateData,
                    'id = ?',
                    [$diaId]
                );
                
                // Eliminar imágenes físicas reemplazadas o eliminadas
                if ($updated) {
                    if (array_key_exists('imagen1', $updateData) && $updateData['imagen1'] !== $dia['imagen1'] && !empty($dia['imagen1'])) {
                        ProgramaImageUploader::deletePhysicalImage($dia['imagen1']);
                    }
                    if (array_key_exists('imagen2', $updateData) && $updateData['imagen2'] !== $dia['imagen2'] && !empty($dia['imagen2'])) {
                        ProgramaImageUploader::deletePhysicalImage($dia['imagen2']);
                    }
                    if (array_key_exists('imagen3', $updateData) && $updateData['imagen3'] !== $dia['imagen3'] && !empty($dia['imagen3'])) {
                        ProgramaImageUploader::deletePhysicalImage($dia['imagen3']);
                    }
                }

                error_log("✅ Filas actualizadas: " . ($updated ? 'Sí' : 'No'));
            }

            // ✅ ACTUALIZAR UBICACIONES SECUNDARIAS
            if (isset($data['ubicaciones_secundarias'])) {
                error_log("📍 Actualizando ubicaciones secundarias...");
                $this->updateUbicacionesSecundarias($diaId, $data['ubicaciones_secundarias']);
            }

            error_log("✅ Día ID=$diaId actualizado correctamente");

            return [
                'success' => true,
                'message' => 'Día actualizado correctamente',
                'dia_id' => $diaId
            ];

        } catch (Exception $e) {
            error_log("❌ Error en updateDia: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }


    private function reorderDiasAfterDelete($programaId, $deletedDiaNumber)
    {
        try {
            $pdo = $this->db->getConnection();
            $stmt = $pdo->prepare("
                UPDATE programa_dias 
                SET dia_numero = dia_numero - 1 
                WHERE solicitud_id = ? AND dia_numero > ?
            ");
            $stmt->execute([$programaId, $deletedDiaNumber]);

        } catch (Exception $e) {
            error_log("Error reordenando después de eliminar: " . $e->getMessage());
        }
    }

    private function cambiarEstancia($diaId, $nuevaDuracion)
    {
        try {
            $diaId = (int) $diaId;
            $nuevaDuracion = (int) $nuevaDuracion;
            $user_id = $_SESSION['user_id'];
            $agencia_id = $_SESSION['agencia_id'] ?? null;

            if (!$agencia_id) {
                throw new Exception('Usuario sin agencia asignada');
            }

            if ($diaId <= 0 || $nuevaDuracion < 1 || $nuevaDuracion > 30) {
                throw new Exception('Datos no válidos');
            }

            $dia = $this->db->fetch(
                "SELECT pd.*, ps.user_id 
                 FROM programa_dias pd 
                 JOIN programa_solicitudes ps ON pd.solicitud_id = ps.id 
                 WHERE pd.id = ? AND ps.user_id = ? AND ps.agencia_id = ?",
                [$diaId, $user_id, $agencia_id]
            );

            if (!$dia) {
                throw new Exception('Día no encontrado o sin permisos');
            }

            $pdo = $this->db->getConnection();
            $stmt = $pdo->prepare("UPDATE programa_dias SET duracion_estancia = ? WHERE id = ?");
            $stmt->execute([$nuevaDuracion, $diaId]);

            // Actualizar fecha de salida
            $this->actualizarFechaSalida($dia['solicitud_id']);

            return [
                'success' => true,
                'message' => 'Estancia actualizada correctamente',
                'nueva_duracion' => $nuevaDuracion
            ];

        } catch (Exception $e) {
            throw new Exception('Error al cambiar estancia: ' . $e->getMessage());
        }
    }

    private function updateComidas($diaId, $data)
    {
        try {
            $diaId = (int) $diaId;
            $user_id = $_SESSION['user_id'];
            $agencia_id = $_SESSION['agencia_id'] ?? null;

            if (!$agencia_id) {
                throw new Exception('Usuario sin agencia asignada');
            }

            // Verificar permisos
            $dia = $this->db->fetch(
                "SELECT pd.*, ps.user_id 
             FROM programa_dias pd 
             JOIN programa_solicitudes ps ON pd.solicitud_id = ps.id 
             WHERE pd.id = ? AND ps.user_id = ? AND ps.agencia_id = ?",
                [$diaId, $user_id, $agencia_id]
            );

            if (!$dia) {
                throw new Exception('Día no encontrado o sin permisos');
            }

            // Preparar datos para actualizar
            $updateData = [
                'comidas_incluidas' => isset($data['comidas_incluidas']) ? (int) $data['comidas_incluidas'] : 0,
                'desayuno' => isset($data['desayuno']) ? (int) $data['desayuno'] : 0,
                'almuerzo' => isset($data['almuerzo']) ? (int) $data['almuerzo'] : 0,
                'cena' => isset($data['cena']) ? (int) $data['cena'] : 0
            ];

            // Actualizar en base de datos
            $this->db->update(
                'programa_dias',
                $updateData,
                'id = ?',
                [$diaId]
            );

            error_log("✅ Comidas actualizadas para día $diaId: " . json_encode($updateData));

            return [
                'success' => true,
                'message' => 'Comidas actualizadas correctamente',
                'data' => $updateData
            ];

        } catch (Exception $e) {
            error_log("❌ Error en updateComidas: " . $e->getMessage());
            throw new Exception('Error al actualizar comidas: ' . $e->getMessage());
        }
    }

    private function getComidas($diaId)
    {
        try {
            $diaId = (int) $diaId;
            $user_id = $_SESSION['user_id'];
            $agencia_id = $_SESSION['agencia_id'] ?? null;

            if (!$agencia_id) {
                throw new Exception('Usuario sin agencia asignada');
            }

            // Obtener datos de comidas
            $comidas = $this->db->fetch(
                "SELECT pd.comidas_incluidas, pd.desayuno, pd.almuerzo, pd.cena
             FROM programa_dias pd 
             JOIN programa_solicitudes ps ON pd.solicitud_id = ps.id 
             WHERE pd.id = ? AND ps.user_id = ? AND ps.agencia_id = ?",
                [$diaId, $user_id, $agencia_id]
            );

            if (!$comidas) {
                throw new Exception('Día no encontrado o sin permisos');
            }

            // Convertir a booleanos
            $data = [
                'comidas_incluidas' => (bool) $comidas['comidas_incluidas'],
                'desayuno' => (bool) $comidas['desayuno'],
                'almuerzo' => (bool) $comidas['almuerzo'],
                'cena' => (bool) $comidas['cena']
            ];

            error_log("✅ Comidas obtenidas para día $diaId: " . json_encode($data));

            return [
                'success' => true,
                'data' => $data
            ];

        } catch (Exception $e) {
            error_log("❌ Error en getComidas: " . $e->getMessage());
            throw new Exception('Error al obtener comidas: ' . $e->getMessage());
        }
    }

    private function actualizarFechaSalida($programaId)
    {
        try {
            // Obtener fecha de llegada
            $programa = $this->db->fetch(
                "SELECT fecha_llegada FROM programa_solicitudes WHERE id = ?",
                [$programaId]
            );

            if (!$programa || !$programa['fecha_llegada']) {
                error_log("No hay fecha de llegada para calcular fecha de salida");
                return;
            }

            // Obtener suma total de duraciones
            $result = $this->db->fetch(
                "SELECT COALESCE(SUM(duracion_estancia), 0) as total_dias 
                 FROM programa_dias 
                 WHERE solicitud_id = ?",
                [$programaId]
            );

            $totalDias = intval($result['total_dias']) ?: 0;

            if ($totalDias === 0) {
                error_log("No hay días creados, no se actualiza fecha de salida");
                return;
            }

            // Calcular fecha de salida
            $fechaLlegada = new DateTime($programa['fecha_llegada']);
            $fechaSalida = clone $fechaLlegada;
            $fechaSalida->add(new DateInterval('P' . ($totalDias) . 'D'));

            // Actualizar en base de datos
            $this->db->update(
                'programa_solicitudes',
                ['fecha_salida' => $fechaSalida->format('Y-m-d')],
                'id = ?',
                [$programaId]
            );

            error_log("✅ Fecha de salida actualizada: " . $fechaSalida->format('Y-m-d') . " (total días: $totalDias)");

        } catch (Exception $e) {
            error_log("Error actualizando fecha de salida: " . $e->getMessage());
        }
    }

    private function sendError($message)
    {
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $message
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Obtener ubicaciones secundarias de un día
     */
    private function getUbicacionesSecundarias($diaId)
    {
        if (!$diaId) {
            throw new Exception('ID de día requerido');
        }

        try {
            $ubicaciones = $this->db->fetchAll(
                "SELECT id, ubicacion, latitud, longitud, orden 
             FROM programa_dias_ubicaciones_secundarias 
             WHERE programa_dia_id = ? 
             ORDER BY orden ASC",
                [$diaId]
            );

            return [
                'success' => true,
                'data' => $ubicaciones
            ];

        } catch (Exception $e) {
            error_log("Error en getUbicacionesSecundarias: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Actualizar ubicaciones secundarias de un día
     */
    private function updateUbicacionesSecundarias($diaId, $ubicaciones)
    {
        try {
            // Eliminar ubicaciones existentes
            $this->db->query(
                "DELETE FROM programa_dias_ubicaciones_secundarias WHERE programa_dia_id = ?",
                [$diaId]
            );

            // Insertar nuevas ubicaciones
            if (!empty($ubicaciones)) {
                foreach ($ubicaciones as $ubic) {
                    if (isset($ubic['ubicacion']) && !empty($ubic['ubicacion'])) {
                        $ubicData = [
                            'programa_dia_id' => $diaId,
                            'ubicacion' => $ubic['ubicacion'],
                            'latitud' => $ubic['latitud'] ?? null,
                            'longitud' => $ubic['longitud'] ?? null,
                            'orden' => $ubic['orden'] ?? 1
                        ];

                        $this->db->insert('programa_dias_ubicaciones_secundarias', $ubicData);
                    }
                }
            }

            error_log("✅ Ubicaciones secundarias actualizadas para día $diaId");

        } catch (Exception $e) {
            error_log("Error actualizando ubicaciones secundarias: " . $e->getMessage());
            throw $e;
        }
    }
}

// Iniciar API
$api = new ProgramaDiasAPI();
$api->handleRequest();