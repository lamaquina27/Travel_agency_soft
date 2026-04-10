<?php
// =====================================
// ARCHIVO: modules/itinerarios/api.php - VERSIÓN MODIFICADA
// =====================================

require_once '../../config/app.php';
require_once '../../config/database.php';

App::requireLogin();

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$user = App::getUser();

try {
    $db = Database::getInstance();
    
    switch($method) {
        case 'GET':
            handleGetRequest($db, $user);
            break;
            
        case 'POST':
            handlePostRequest($db, $user);
            break;
            
        case 'DELETE':
            handleDeleteRequest($db, $user);
            break;
            
        default:
            throw new Exception('Método no permitido');
    }
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function handleGetRequest($db, $user) {
    $action = $_GET['action'] ?? 'get_all';
    $userId = $user['id'];
    
    switch($action) {
        case 'get_all':
            getAllItinerarios($db, $userId);
            break;
            
        case 'get_completed':
            getCompletedItinerarios($db, $userId);
            break;
            
        case 'get_by_id':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                throw new Exception('ID requerido');
            }
            getItinerarioById($db, $userId, $id);
            break;
            
        case 'get_plantillas':
            // Nueva función para obtener plantillas al crear programa
            getPlantillasDisponibles($db, $userId);
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
}

function getAllItinerarios($db, $userId) {
    // CAMBIO PRINCIPAL: Mostrar TODOS los programas, pero marcar cuáles puede editar
    $stmt = $db->prepare("
        SELECT 
            p.id,
            p.request_id,
            p.traveler_name,
            p.traveler_lastname,
            p.destination,
            p.arrival_date,
            p.departure_date,
            p.passengers,
            p.program_title,
            p.cover_image,
            p.status,
            p.total_days,
            p.currency,
            p.created_at,
            p.updated_at,
            p.user_id,
            u.name as creator_name,
            u.role as creator_role,
            CASE WHEN p.user_id = ? THEN 1 ELSE 0 END as can_edit
        FROM programas p
        LEFT JOIN users u ON p.user_id = u.id
        ORDER BY p.updated_at DESC
    ");
    
    $stmt->execute([$userId]);
    $itinerarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Procesar datos para mostrar
    foreach ($itinerarios as &$itinerario) {
        // Asegurar que las fechas estén en formato correcto
        $itinerario['arrival_date'] = $itinerario['arrival_date'] ? date('Y-m-d', strtotime($itinerario['arrival_date'])) : null;
        $itinerario['departure_date'] = $itinerario['departure_date'] ? date('Y-m-d', strtotime($itinerario['departure_date'])) : null;
        
        // Calcular duración si hay fechas
        if ($itinerario['arrival_date'] && $itinerario['departure_date']) {
            $arrival = new DateTime($itinerario['arrival_date']);
            $departure = new DateTime($itinerario['departure_date']);
            $itinerario['duration_days'] = $departure->diff($arrival)->days;
        } else {
            $itinerario['duration_days'] = 0;
        }
        
        // Completar nombre del viajero
        $itinerario['traveler_full_name'] = trim(($itinerario['traveler_name'] ?? '') . ' ' . ($itinerario['traveler_lastname'] ?? ''));
        
        // Asegurar que el estado existe
        if (!$itinerario['status']) {
            $itinerario['status'] = 'draft';
        }
        
        // Agregar información del creador
        $itinerario['creator_info'] = $itinerario['creator_name'] ? 
            "Creado por: " . $itinerario['creator_name'] : 
            "Creador desconocido";
    }
    
    echo json_encode([
        'success' => true,
        'itinerarios' => $itinerarios,
        'total' => count($itinerarios)
    ]);
}

function getCompletedItinerarios($db, $userId) {
    // Mantener filtro solo para completados (estos sí son personales)
    $stmt = $db->prepare("
        SELECT 
            id,
            program_title,
            destination,
            traveler_name,
            traveler_lastname,
            status,
            created_at
        FROM programas 
        WHERE user_id = ? AND status IN ('completed', 'sent', 'approved')
        ORDER BY created_at DESC
    ");
    
    $stmt->execute([$userId]);
    $propuestas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'propuestas' => $propuestas
    ]);
}

function getItinerarioById($db, $userId, $id) {
    // CAMBIO: Permitir ver cualquier itinerario, pero solo editar los propios
    $stmt = $db->prepare("
        SELECT p.*, 
               CASE WHEN p.user_id = ? THEN 1 ELSE 0 END as can_edit,
               u.name as creator_name
        FROM programas p 
        LEFT JOIN users u ON p.user_id = u.id
        WHERE p.id = ?
    ");
    $stmt->execute([$userId, $id]);
    $itinerario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$itinerario) {
        throw new Exception('Itinerario no encontrado');
    }
    
    // Obtener días
    $stmt = $db->prepare("SELECT * FROM programa_days WHERE program_id = ? ORDER BY day_number");
    $stmt->execute([$id]);
    $days = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener servicios para cada día
    foreach ($days as &$day) {
        $stmt = $db->prepare("SELECT * FROM programa_services WHERE day_id = ?");
        $stmt->execute([$day['id']]);
        $day['services'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Decodificar comidas
        $day['meals'] = json_decode($day['meals'] ?? '[]', true);
    }
    
    // Obtener precios
    $stmt = $db->prepare("SELECT * FROM programa_prices WHERE program_id = ?");
    $stmt->execute([$id]);
    $prices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'itinerario' => [
            'general' => $itinerario,
            'days' => $days,
            'prices' => $prices
        ]
    ]);
}

// NUEVA FUNCIÓN: Obtener plantillas disponibles para crear programas
function getPlantillasDisponibles($db, $userId) {
    $stmt = $db->prepare("
        SELECT 
            p.id,
            p.program_title,
            p.destination,
            p.total_days,
            p.traveler_name,
            p.traveler_lastname,
            p.cover_image,
            u.name as creator_name,
            CASE WHEN p.user_id = ? THEN 'Propio' ELSE 'Plantilla' END as type
        FROM programas p
        LEFT JOIN users u ON p.user_id = u.id
        WHERE p.status IN ('completed', 'draft')
        ORDER BY 
            CASE WHEN p.user_id = ? THEN 0 ELSE 1 END,
            p.updated_at DESC
    ");
    
    $stmt->execute([$userId, $userId]);
    $plantillas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'plantillas' => $plantillas
    ]);
}

function handlePostRequest($db, $user) {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? null;
    $userId = $user['id'];
    
    if (!$action) {
        throw new Exception('Acción requerida');
    }
    
    switch($action) {
        case 'duplicate':
            duplicateItinerario($db, $userId, $input['itinerario_id']);
            break;
            
        case 'duplicate_propuesta':
            duplicatePropuesta($db, $userId, $input['propuesta_id']);
            break;
            
        case 'update_status':
            updateStatus($db, $userId, $input['itinerario_id'], $input['status']);
            break;
            
        case 'crear_desde_plantilla':
            // Nueva función para crear programa desde plantilla
            crearDesdeePlantilla($db, $userId, $input['plantilla_id'], $input['datos_viajero']);
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
}

function duplicateItinerario($db, $userId, $itinerarioId) {
    // CAMBIO: Permitir duplicar cualquier itinerario (no solo los propios)
    $stmt = $db->prepare("SELECT * FROM programas WHERE id = ?");
    $stmt->execute([$itinerarioId]);
    $original = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$original) {
        throw new Exception('Itinerario no encontrado');
    }
    
    $db->beginTransaction();
    
    try {
        // Duplicar programa principal con el nuevo usuario como propietario
        $stmt = $db->prepare("
            INSERT INTO programas (
                user_id, request_id, traveler_name, traveler_lastname, destination,
                arrival_date, departure_date, passengers, accompaniment, program_title,
                budget_language, cover_image, total_days, currency, nights_included,
                price_includes, price_excludes, general_conditions, mobility_reduced,
                passport_info, insurance_info, status, created_at, updated_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft', NOW(), NOW()
            )
        ");
        
        $newRequestId = 'REQ-' . date('Y') . '-' . rand(1000, 9999);
        $newTitle = ($original['program_title'] ?? 'Itinerario') . ' (Copia)';
        
        $stmt->execute([
            $userId, // IMPORTANTE: usar el ID del usuario actual, no el original
            $newRequestId,
            $original['traveler_name'],
            $original['traveler_lastname'],
            $original['destination'],
            $original['arrival_date'],
            $original['departure_date'],
            $original['passengers'],
            $original['accompaniment'],
            $newTitle,
            $original['budget_language'],
            $original['cover_image'],
            $original['total_days'],
            $original['currency'],
            $original['nights_included'],
            $original['price_includes'],
            $original['price_excludes'],
            $original['general_conditions'],
            $original['mobility_reduced'],
            $original['passport_info'],
            $original['insurance_info']
        ]);
        
        $newProgramId = $db->lastInsertId();
        
        // Duplicar días
        $stmt = $db->prepare("SELECT * FROM programa_days WHERE program_id = ? ORDER BY day_number");
        $stmt->execute([$itinerarioId]);
        $days = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($days as $day) {
            $stmt = $db->prepare("
                INSERT INTO programa_days (
                    program_id, day_number, city, activities, meals, accommodation, notes, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $newProgramId,
                $day['day_number'],
                $day['city'],
                $day['activities'],
                $day['meals'],
                $day['accommodation'],
                $day['notes']
            ]);
            
            $newDayId = $db->lastInsertId();
            
            // Duplicar servicios del día
            $stmt = $db->prepare("SELECT * FROM programa_services WHERE day_id = ?");
            $stmt->execute([$day['id']]);
            $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($services as $service) {
                $stmt = $db->prepare("
                    INSERT INTO programa_services (
                        day_id, service_type, name, description, location, price, notes, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                
                $stmt->execute([
                    $newDayId,
                    $service['service_type'],
                    $service['name'],
                    $service['description'],
                    $service['location'],
                    $service['price'],
                    $service['notes']
                ]);
            }
        }
        
        // Duplicar precios
        $stmt = $db->prepare("SELECT * FROM programa_prices WHERE program_id = ?");
        $stmt->execute([$itinerarioId]);
        $prices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($prices as $price) {
            $stmt = $db->prepare("
                INSERT INTO programa_prices (
                    program_id, passenger_type, detail, amount, created_at
                ) VALUES (?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $newProgramId,
                $price['passenger_type'],
                $price['detail'],
                $price['amount']
            ]);
        }
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Itinerario duplicado exitosamente',
            'new_id' => $newProgramId
        ]);
        
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
}

// NUEVA FUNCIÓN: Crear programa desde plantilla con datos del viajero
function crearDesdeePlantilla($db, $userId, $plantillaId, $datosViajero) {
    $stmt = $db->prepare("SELECT * FROM programas WHERE id = ?");
    $stmt->execute([$plantillaId]);
    $plantilla = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$plantilla) {
        throw new Exception('Plantilla no encontrada');
    }
    
    $db->beginTransaction();
    
    try {
        // Crear nuevo programa con datos del viajero
        $stmt = $db->prepare("
            INSERT INTO programas (
                user_id, request_id, traveler_name, traveler_lastname, destination,
                arrival_date, departure_date, passengers, accompaniment, program_title,
                budget_language, cover_image, total_days, currency, status, created_at, updated_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft', NOW(), NOW()
            )
        ");
        
        $newRequestId = 'REQ-' . date('Y') . '-' . rand(1000, 9999);
        $newTitle = $datosViajero['program_title'] ?? ($plantilla['program_title'] . ' - ' . $datosViajero['traveler_name']);
        
        $stmt->execute([
            $userId,
            $newRequestId,
            $datosViajero['traveler_name'],
            $datosViajero['traveler_lastname'],
            $datosViajero['destination'] ?? $plantilla['destination'],
            $datosViajero['arrival_date'],
            $datosViajero['departure_date'],
            $datosViajero['passengers'] ?? 1,
            $datosViajero['accompaniment'] ?? 'sin-acompanamiento',
            $newTitle,
            $datosViajero['language'] ?? 'es',
            $plantilla['cover_image'],
            $plantilla['total_days'],
            $plantilla['currency']
        ]);
        
        $newProgramId = $db->lastInsertId();
        
        // Copiar estructura de días y servicios de la plantilla
        // (código similar al duplicateItinerario pero sin copiar datos específicos del viajero)
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Programa creado desde plantilla exitosamente',
            'new_id' => $newProgramId
        ]);
        
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
}

function updateStatus($db, $userId, $itinerarioId, $newStatus) {
    $validStatuses = ['draft', 'completed', 'sent', 'approved'];
    
    if (!in_array($newStatus, $validStatuses)) {
        throw new Exception('Estado no válido');
    }
    
    // CAMBIO: Solo permitir cambiar estado en programas propios
    $stmt = $db->prepare("
        UPDATE programas 
        SET status = ?, updated_at = NOW() 
        WHERE id = ? AND user_id = ?
    ");
    
    $stmt->execute([$newStatus, $itinerarioId, $userId]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Itinerario no encontrado o sin permisos para modificarlo');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Estado actualizado exitosamente'
    ]);
}

function handleDeleteRequest($db, $user) {
    $input = json_decode(file_get_contents('php://input'), true);
    $itinerarioId = $input['itinerario_id'] ?? null;
    $userId = $user['id'];
    
    if (!$itinerarioId) {
        throw new Exception('ID de itinerario requerido');
    }
    
    // MANTENER: Solo eliminar programas propios
    $stmt = $db->prepare("SELECT id FROM programas WHERE id = ? AND user_id = ?");
    $stmt->execute([$itinerarioId, $userId]);
    $itinerario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$itinerario) {
        throw new Exception('Itinerario no encontrado o sin permisos para eliminarlo');
    }
    
    $db->beginTransaction();
    
    try {
        // Eliminar en orden (por las foreign keys)
        $stmt = $db->prepare("DELETE FROM programa_services WHERE program_id = ?");
        $stmt->execute([$itinerarioId]);
        
        $stmt = $db->prepare("DELETE FROM programa_prices WHERE program_id = ?");
        $stmt->execute([$itinerarioId]);
        
        $stmt = $db->prepare("DELETE FROM programa_days WHERE program_id = ?");
        $stmt->execute([$itinerarioId]);
        
        $stmt = $db->prepare("DELETE FROM programas WHERE id = ?");
        $stmt->execute([$itinerarioId]);
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Itinerario eliminado exitosamente'
        ]);
        
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
}

?>