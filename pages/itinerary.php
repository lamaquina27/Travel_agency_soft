<?php
// ====================================================================
// ARCHIVO: pages/itinerary.php - ITINERARIO COMPLETO CON ALTERNATIVAS
// ====================================================================

require_once 'config/app.php';
require_once 'config/config_functions.php';

// Verificar acceso público
$is_public = isset($_GET['public']) && $_GET['public'] == '1';

if (!$is_public) {
    // Acceso normal - verificar login
    if (!App::isLoggedIn()) {
        header('Location: ' . APP_URL . '/login');
        exit;
    }
} else {
    // Acceso público - limpiar sesión temporal
    unset($_SESSION['temp_public_access']);
}

// Obtener ID del programa
$programa_id = $_GET['id'] ?? null;

if (!$programa_id) {
    header('Location: ' . APP_URL . '/itinerarios');
    exit;
}

try {
    ConfigManager::init();
    $company_name = ConfigManager::getCompanyName();
    $config = ConfigManager::get();
} catch(Exception $e) {
    $company_name = 'Travel Agency';
    $config = [];
}

// Cargar datos completos del programa
try {
    $db = Database::getInstance();
    
    // Obtener datos básicos del programa
    $programa = $db->fetch(
        "SELECT ps.*, pp.titulo_programa, pp.foto_portada, pp.idioma_predeterminado,
                DATE_FORMAT(ps.fecha_llegada, '%d/%m/%Y') as fecha_llegada_formatted,
                DATE_FORMAT(ps.fecha_salida, '%d/%m/%Y') as fecha_salida_formatted,
                DATEDIFF(ps.fecha_salida, ps.fecha_llegada) as duracion_dias
         FROM programa_solicitudes ps 
         LEFT JOIN programa_personalizacion pp ON ps.id = pp.solicitud_id 
         WHERE ps.id = ?", 
        [$programa_id]
    );
    
    if (!$programa) {
        throw new Exception('Programa no encontrado');
    }
    
    // Obtener días del programa
    $dias = $db->fetchAll(
        "SELECT *, COALESCE(duracion_estancia, 1) as duracion_estancia FROM programa_dias WHERE solicitud_id = ? ORDER BY dia_numero ASC", 
        [$programa_id]
    );
    
foreach ($dias as &$dia) {
    // Buscar el día de biblioteca que coincida por título y ubicación
    $biblioteca_dia = $db->fetch(
        "SELECT id FROM biblioteca_dias 
         WHERE titulo = ? AND ubicacion = ? AND activo = 1
         LIMIT 1", 
        [$dia['titulo'], $dia['ubicacion']]
    );
    
    if ($biblioteca_dia) {
        $dia['ubicaciones_secundarias'] = $db->fetchAll(
            "SELECT ubicacion, latitud, longitud, orden 
             FROM biblioteca_dias_ubicaciones_secundarias 
             WHERE dia_id = ? 
             ORDER BY orden ASC", 
            [$biblioteca_dia['id']]
        );
        error_log("DEBUG - Programa: " . $dia['titulo'] . " -> Biblioteca ID: " . $biblioteca_dia['id'] . " -> Ubicaciones: " . count($dia['ubicaciones_secundarias']));
    } else {
        $dia['ubicaciones_secundarias'] = [];
        error_log("DEBUG - No se encontró biblioteca_dia para: " . $dia['titulo']);
    }
}

    // Obtener servicios para cada día con todas las alternativas
    foreach ($dias as &$dia) {
        $servicios_raw = $db->fetchAll(
            "SELECT 
                pds.*,
                CASE 
                    WHEN pds.tipo_servicio = 'actividad' THEN ba.nombre
                    WHEN pds.tipo_servicio = 'transporte' THEN bt.titulo
                    WHEN pds.tipo_servicio = 'alojamiento' THEN bal.nombre
                END as nombre,
                CASE 
                    WHEN pds.tipo_servicio = 'actividad' THEN ba.descripcion
                    WHEN pds.tipo_servicio = 'transporte' THEN bt.descripcion
                    WHEN pds.tipo_servicio = 'alojamiento' THEN bal.descripcion
                END as descripcion,
                CASE 
                    WHEN pds.tipo_servicio = 'actividad' THEN ba.ubicacion
                    WHEN pds.tipo_servicio = 'transporte' THEN CONCAT(COALESCE(bt.lugar_salida, ''), ' → ', COALESCE(bt.lugar_llegada, ''))
                    WHEN pds.tipo_servicio = 'alojamiento' THEN bal.ubicacion
                END as ubicacion,
                CASE 
                    WHEN pds.tipo_servicio = 'actividad' THEN ba.imagen1
                    WHEN pds.tipo_servicio = 'transporte' THEN NULL
                    WHEN pds.tipo_servicio = 'alojamiento' THEN bal.imagen
                END as imagen,
                CASE 
                    WHEN pds.tipo_servicio = 'actividad' THEN ba.imagen2
                    ELSE NULL
                END as imagen2,
                CASE 
                    WHEN pds.tipo_servicio = 'actividad' THEN ba.imagen3
                    ELSE NULL
                END as imagen3,
                CASE 
                    WHEN pds.tipo_servicio = 'actividad' THEN ba.latitud
                    WHEN pds.tipo_servicio = 'transporte' THEN bt.lat_salida
                    WHEN pds.tipo_servicio = 'alojamiento' THEN bal.latitud
                END as latitud,
                CASE 
                    WHEN pds.tipo_servicio = 'actividad' THEN ba.longitud
                    WHEN pds.tipo_servicio = 'transporte' THEN bt.lng_salida
                    WHEN pds.tipo_servicio = 'alojamiento' THEN bal.longitud
                END as longitud,
                CASE 
                    WHEN pds.tipo_servicio = 'transporte' THEN bt.lat_llegada
                    ELSE NULL
                END as lat_llegada,
                CASE 
                    WHEN pds.tipo_servicio = 'transporte' THEN bt.lng_llegada
                    ELSE NULL
                END as lng_llegada,
                CASE 
                    WHEN pds.tipo_servicio = 'transporte' THEN bt.duracion
                    ELSE NULL
                END as duracion,
                CASE 
                    WHEN pds.tipo_servicio = 'transporte' THEN bt.medio
                    ELSE NULL
                END as medio_transporte,
                CASE 
                    WHEN pds.tipo_servicio = 'alojamiento' THEN bal.tipo
                    ELSE NULL
                END as tipo_alojamiento,
                CASE 
                    WHEN pds.tipo_servicio = 'alojamiento' THEN bal.categoria
                    ELSE NULL
                END as categoria_alojamiento
            FROM programa_dias_servicios pds
            LEFT JOIN biblioteca_actividades ba ON pds.tipo_servicio = 'actividad' AND pds.biblioteca_item_id = ba.id AND ba.activo = 1
            LEFT JOIN biblioteca_transportes bt ON pds.tipo_servicio = 'transporte' AND pds.biblioteca_item_id = bt.id AND bt.activo = 1
            LEFT JOIN biblioteca_alojamientos bal ON pds.tipo_servicio = 'alojamiento' AND pds.biblioteca_item_id = bal.id AND bal.activo = 1
            WHERE pds.programa_dia_id = ?
            ORDER BY pds.orden ASC, pds.es_alternativa ASC, pds.orden_alternativa ASC", 
            [$dia['id']]
        );
        
        // Organizar servicios por orden secuencial
        $servicios_organizados = [];
        foreach ($servicios_raw as $servicio) {
            $orden = $servicio['orden'];
            
            if (!isset($servicios_organizados[$orden])) {
                $servicios_organizados[$orden] = [
                    'principal' => null,
                    'alternativas' => []
                ];
            }
            
            if ($servicio['es_alternativa'] == 0) {
                $servicios_organizados[$orden]['principal'] = $servicio;
            } else {
                $servicios_organizados[$orden]['alternativas'][] = $servicio;
            }
        }
        
        ksort($servicios_organizados);
        $dia['servicios'] = $servicios_organizados;
    }
    
    unset($dia);
    
    // Obtener información de precios
    $precios = $db->fetch(
        "SELECT * FROM programa_precios WHERE solicitud_id = ?", 
        [$programa_id]
    );
    //Cambio para mostrar los precios o esconderlos
    $mostrar_precios = $precios && (!isset($precios['mostrar_precio']) || (int)$precios['mostrar_precio'] === 1);
    
    // Preparar datos para el mapa
    $puntos_mapa = [];
    foreach ($dias as $dia) {
        foreach ($dia['servicios'] as $orden => $servicio_grupo) {
            $servicio = $servicio_grupo['principal'];
            if ($servicio && $servicio['latitud'] && $servicio['longitud']) {
                $puntos_mapa[] = [
                    'lat' => floatval($servicio['latitud']),
                    'lng' => floatval($servicio['longitud']),
                    'titulo' => $servicio['nombre'],
                    'descripcion' => $servicio['descripcion'],
                    'tipo' => $servicio['tipo_servicio'],
                    'dia' => $dia['dia_numero'],
                    'ubicacion' => $servicio['ubicacion'],
                    'imagen' => $servicio['imagen']
                ];
            }
            
            // Agregar punto de llegada para transportes
            if ($servicio && $servicio['tipo_servicio'] == 'transporte' && 
                $servicio['lat_llegada'] && $servicio['lng_llegada']) {
                $puntos_mapa[] = [
                    'lat' => floatval($servicio['lat_llegada']),
                    'lng' => floatval($servicio['lng_llegada']),
                    'titulo' => $servicio['nombre'] . ' (Llegada)',
                    'descripcion' => $servicio['descripcion'],
                    'tipo' => 'transporte_llegada',
                    'dia' => $dia['dia_numero'],
                    'ubicacion' => $servicio['ubicacion']
                ];
            }
        }
    }
// Agregar ubicaciones secundarias al mapa
if (!empty($dia['ubicaciones_secundarias'])) {
    foreach ($dia['ubicaciones_secundarias'] as $ubicacion_sec) {
        if ($ubicacion_sec['latitud'] && $ubicacion_sec['longitud']) {
            $puntos_mapa[] = [
                'lat' => floatval($ubicacion_sec['latitud']),
                'lng' => floatval($ubicacion_sec['longitud']),
                'titulo' => $ubicacion_sec['ubicacion'],
                'descripcion' => 'Ubicación secundaria - ' . $dia['titulo'],
                'tipo' => 'ubicacion_secundaria',
                'dia' => $dia['dia_numero'],
                'ubicacion' => $ubicacion_sec['ubicacion']
            ];
        }
    }
}
    
} catch(Exception $e) {
    error_log("Error cargando programa: " . $e->getMessage());
    header('Location: ' . APP_URL . '/itinerarios');
    exit;
}

// Funciones helper
function getServiceIcon($tipo, $medio_transporte = null) {
    switch($tipo) {
        case 'actividad': 
            return 'fas fa-hiking';
            
        case 'transporte':
            if ($medio_transporte) {
                switch(strtolower($medio_transporte)) {
                    case 'avion': return 'fas fa-plane';
                    case 'bus': return 'fas fa-bus';
                    case 'tren': return 'fas fa-train';
                    case 'barco': return 'fas fa-ship';
                    case 'coche': return 'fas fa-car';
                    default: return 'fas fa-plane';
                }
            }
            return 'fas fa-plane';
            
        case 'alojamiento': 
            return 'fas fa-bed';
            
        default: 
            return 'fas fa-map-marker-alt';
    }
}

function formatTransportMedium($medio) {
    $medios = [
        'avion' => 'Avión',
        'bus' => 'Bus',
        'coche' => 'Coche',
        'barco' => 'Barco',
        'tren' => 'Tren'
    ];
    return $medios[$medio] ?? ucfirst($medio);
}

function formatAccommodationType($tipo) {
    $tipos = [
        'hotel' => 'Hotel',
        'camping' => 'Camping',
        'casa_huespedes' => 'Casa de Huéspedes',
        'crucero' => 'Crucero',
        'lodge' => 'Lodge',
        'atipico' => 'Alojamiento Atípico',
        'campamento' => 'Campamento',
        'camping_car' => 'Camping Car',
        'tren' => 'Tren Hotel'
    ];
    return $tipos[$tipo] ?? ucfirst($tipo);
}

// Calcular duración-------------------- También se movió para evitar error de no inicialización
// Calcular duración real basada en los días del programa
$duracion_dias = 0;
foreach ($dias as $dia) {
    $duracion_estancia = intval($dia['duracion_estancia']) ?: 1;
    $duracion_dias += $duracion_estancia;
}

// Si no hay días en el programa, usar el conteo de días
if ($duracion_dias == 0) {
    $duracion_dias = count($dias);
}

// Datos para el template
$titulo_programa = $programa['titulo_programa'] ?: 'Viaje a ' . $programa['destino'];
$nombre_viajero = trim($programa['nombre_viajero'] . ' ' . $programa['apellido_viajero']);
$imagen_portada = $programa['foto_portada'] ?: 'https://images.unsplash.com/photo-1488646953014-85cb44e25828?w=1200&h=600&fit=crop';
$num_dias = $duracion_dias; 
$num_pasajeros = $programa['numero_pasajeros'];



// Calcular fechas basado en fecha de llegada + días del programa
$fecha_inicio_formatted = '';
$fecha_fin_formatted = '';

if ($programa['fecha_llegada']) {
    $fecha_inicio = new DateTime($programa['fecha_llegada']);
    $fecha_inicio_formatted = $fecha_inicio->format('d M Y');
    
    // Calcular fecha de salida: fecha_llegada + duración_días - 1
    $fecha_fin = clone $fecha_inicio;
    $fecha_fin->add(new DateInterval('P' . ($duracion_dias - 1) . 'D'));
    $fecha_fin_formatted = $fecha_fin->format('d M Y');
}

?>

<!DOCTYPE html>
<html lang="<?= $config['default_language'] ?? 'es' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo_programa) ?> - <?= $company_name ?></title>
    <!-- Google Translate -->
<script type="text/javascript">
    function googleTranslateElementInit() {
        new google.translate.TranslateElement({
            pageLanguage: '<?= $programa['idioma_predeterminado'] ?? 'es' ?>',
            includedLanguages: 'en,fr,pt,it,de,es',
            layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
            autoDisplay: false
        }, 'google_translate_element');
    }
</script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: #2c3e50;
            background: #ffffff;
        }
        
        /* ========================================
           HERO SECTION
           ======================================== */
        .hero-section {
            height: 100vh;
            background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.6)), url('<?= addslashes($imagen_portada) ?>');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            position: relative;
        }
        
        .hero-content {
            max-width: 800px;
            padding: 0 20px;
            animation: fadeInUp 1s ease-out;
        }
        
        .hero-subtitle {
            font-size: 1.2rem;
            margin-bottom: 15px;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 500;
        }
        
        .hero-title {
            font-family: 'Playfair Display', serif;
            font-size: 4rem;
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }
        
        .hero-description {
            font-size: 1.3rem;
            margin-bottom: 30px;
            opacity: 0.95;
        }
        
        .hero-stats {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin-bottom: 40px;
            flex-wrap: wrap;
        }
        
        .hero-stat {
            text-align: center;
            background: rgba(255,255,255,0.15);
            padding: 20px 25px;
            border-radius: 15px;
            backdrop-filter: blur(10px);
            min-width: 120px;
        }
        
        .hero-stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            display: block;
            margin-bottom: 5px;
        }
        
        .hero-stat-title {
            display: block;
            font-size: 12px;
            color: rgba(255,255,255,0.8);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 4px;
            font-weight: 600;
        }
        
        .scroll-indicator {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            animation: bounce 2s infinite;
        }
        
        .scroll-indicator i {
            font-size: 2rem;
            opacity: 0.8;
        }
        
        /* ========================================
           NAVIGATION BAR
           ======================================== */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(20px);
            padding: 15px 0;
            z-index: 1000;
            transform: translateY(-100%);
            transition: transform 0.3s ease;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }
        
        .navbar.visible {
            transform: translateY(0);
        }
        
        .navbar-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 95px;
        }
        
        .navbar-brand {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            font-weight: 600;
            color: #2c3e50;
            text-decoration: none;
        }
        
        .navbar-nav {
            display: flex;
            gap: 30px;
            list-style: none;
        }
        
        .navbar-nav a {
            color: #2c3e50;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .navbar-nav a:hover {
            color: #3498db;
        }
        
        /* ========================================
           MAIN CONTENT SECTIONS
           ======================================== */
        .main-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 80px 20px;
            background: #ffffff;
        }
        
        .section {
            margin-bottom: 100px;
        }
        
        .section-header {
            text-align: center;
            margin-bottom: 60px;
        }
        
        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 3rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .section-subtitle {
            font-size: 1.2rem;
            color: #7f8c8d;
            max-width: 600px;
            margin: 0 auto;
        }
        /* ===== SELECTOR DE IDIOMA ELEGANTE ===== */
.translate-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
}

#google_translate_element {
    background: rgba(255, 255, 255, 0.95);
    border: 1px solid rgba(255, 255, 255, 0.4);
    border-radius: 12px;
    padding: 10px 15px;
    backdrop-filter: blur(15px);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
    transition: all 0.3s ease;
}

#google_translate_element:hover {
    background: rgba(255, 255, 255, 1);
    transform: translateY(-2px);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.18);
}

.goog-te-gadget-icon {
    display: none !important;
}

.goog-te-gadget-simple {
    background: transparent !important;
    border: none !important;
    font-family: 'Inter', sans-serif !important;
}

.VIpgJd-ZVi9od-xl07Ob-lTBxed {
    background: transparent !important;
    border: none !important;
    color: #2c3e50 !important;
    text-decoration: none !important;
    font-family: inherit !important;
    font-size: 14px !important;
    font-weight: 600 !important;
    cursor: pointer !important;
    padding: 6px 12px !important;
    border-radius: 8px !important;
    transition: all 0.2s ease !important;
    display: flex !important;
    align-items: center !important;
    gap: 8px !important;
}

.VIpgJd-ZVi9od-xl07Ob-lTBxed:hover {
    background: rgba(52, 152, 219, 0.1) !important;
    color: #3498db !important;
}

.VIpgJd-ZVi9od-xl07Ob-lTBxed img {
    display: none !important;
}

.VIpgJd-ZVi9od-xl07Ob-lTBxed span[style*="border-left"] {
    display: none !important;
}

.VIpgJd-ZVi9od-xl07Ob-lTBxed span[aria-hidden="true"] {
    color: #6b7280 !important;
    font-size: 12px !important;
    margin-left: 6px !important;
    transition: all 0.2s ease !important;
}

.VIpgJd-ZVi9od-xl07Ob-lTBxed:hover span[aria-hidden="true"] {
    color: #3498db !important;
    transform: translateY(1px) !important;
}

.VIpgJd-ZVi9od-ORHb-OEVmcd {
            left: 0;
            display: none !important;
            top: 0;
        }

.goog-te-menu-frame {
    border: none !important;
    border-radius: 12px !important;
    box-shadow: 0 15px 50px rgba(0, 0, 0, 0.15) !important;
    backdrop-filter: blur(10px) !important;
    overflow: hidden !important;
    margin-top: 5px !important;
}

.goog-te-menu2 {
    background: rgba(255, 255, 255, 0.98) !important;
    border: none !important;
    padding: 8px 0 !important;
}

.goog-te-menu2-item {
    font-family: 'Inter', sans-serif !important;
    font-size: 13px !important;
    font-weight: 500 !important;
    color: #374151 !important;
    padding: 12px 18px !important;
    transition: all 0.15s ease !important;
    cursor: pointer !important;
    border: none !important;
    margin: 0 8px !important;
    border-radius: 8px !important;
}

.goog-te-menu2-item:hover {
    background: rgba(52, 152, 219, 0.1) !important;
    color: #3498db !important;
    transform: translateX(3px) !important;
}

.goog-te-menu2-item-selected {
    background: #3498db !important;
    color: white !important;
    font-weight: 600 !important;
}

.goog-te-banner-frame.skiptranslate { 
    display: none !important; 
}

body { 
    top: 0px !important; 
}

/* Responsive */
@media (max-width: 768px) {
    .translate-container {
        top: 15px;
        right: 15px;
    }
    
    #google_translate_element {
        padding: 8px 12px;
    }
    
    .VIpgJd-ZVi9od-xl07Ob-lTBxed {
        font-size: 13px !important;
        padding: 5px 10px !important;
    }
}
        /* ========================================
           OVERVIEW SECTION
           ======================================== */
        .overview-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 40px;
            margin-bottom: 60px;
        }
        
        .overview-content {
            background: #ffffff;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            border: 1px solid #e9ecef;
        }
        
        .overview-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 15px;
            transition: all 0.3s ease;
        }
        
        .detail-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .detail-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #3498db, #2980b9);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }
        
        .detail-info h4 {
            font-weight: 600;
            margin-bottom: 5px;
            color: #2c3e50;
        }
        
        .detail-info p {
            color: #7f8c8d;
            margin: 0;
        }
        
        .overview-summary {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 15px;
            border-left: 5px solid #3498db;
        }
        
        .overview-summary h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 1.3rem;
        }
        
        .overview-summary p {
            color: #5a6c7d;
            line-height: 1.8;
        }
        
        /* ========================================
           MAP SECTION
           ======================================== */
        .map-container {
            background: #ffffff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            height: 500px;
            border: 1px solid #e9ecef;
        }
        
        #map {
            height: 100%;
            width: 100%;
        }
        
        /* ========================================
           ITINERARY SECTION - DISEÑO LIMPIO
           ======================================== */
        .itinerary-timeline {
            position: relative;
        }
        
        .itinerary-timeline::before {
            content: '';
            position: absolute;
            left: 60px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e9ecef;
            border-radius: 1px;
        }
        
        .day-card {
            position: relative;
            margin-bottom: 40px;
            padding-left: 140px;
            animation: fadeInUp 0.6s ease-out;
        }
        
        /* Day Number - Diseño uniforme para todos */
        .day-number {
            position: absolute;
            left: 0;
            top: 20px;
            width: 100px;
            height: 80px;
            background: #ffffff;
            border: 2px solid #3498db;
            border-radius: 15px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #2c3e50;
            font-weight: 700;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }
        
        .day-number:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.12);
        }
        
        .day-number-main {
            font-size: 1.4rem;
            line-height: 1;
            color: #2c3e50;
        }
        
        .day-number-label {
            font-size: 0.7rem;
            color: #7f8c8d;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 2px;
        }
        
        /* Badge minimalista para duración */
        .duration-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #3498db;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            border: 2px solid white;
        }
        /* Badge de duración inline */
        .day-location .duration-badge {
            position: relative;
            top: 0;
            right: 0;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            font-size: 13px;
            border-radius: 20px;
            background: linear-gradient(135deg, #3498db, #2980b9);
            box-shadow: 0 2px 8px rgba(52, 152, 219, 0.3);
        }

     @media print {
    /* ========== CONFIGURACIÓN GLOBAL ========== */
    * {
        -webkit-print-color-adjust: exact !important;
        color-adjust: exact !important;
        print-color-adjust: exact !important;
        box-sizing: border-box !important;
    }
    
    @page {
        margin: 12mm;
        size: A4 portrait;
    }
    
    html, body {
        width: 100% !important;
        height: auto !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    
    body {
        font-size: 9pt !important;
        line-height: 1.3 !important;
        color: #000 !important;
        background: #fff !important;
    }
    
    /* ========== OCULTAR ELEMENTOS ========== */
    .navbar, 
    .scroll-indicator, 
    .pricing-actions, 
    .footer-actions,
    .alternatives-header,
    .translate-container,
    #google_translate_element,
    .simple-image-modal,
    .day-images,
    .service-image,
    .accordion-arrow,
    .alternatives-toggle,
    .map-container {
        display: none !important;
    }
    
    /* ========== HERO SECTION COMPACTO ========== */
    .hero-section {
        height: 180px !important;
        min-height: 180px !important;
        max-height: 180px !important;
        background-attachment: scroll !important;
        page-break-after: always !important;
        margin: 0 !important;
        padding: 25px 15px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }
    
    .hero-content {
        text-align: center !important;
        max-width: 100% !important;
        padding: 0 !important;
    }
    
    .hero-title {
        font-size: 20pt !important;
        margin: 0 0 8px 0 !important;
        color: #fff !important;
        line-height: 1.2 !important;
    }
    
    .hero-subtitle {
        font-size: 10pt !important;
        margin: 0 0 5px 0 !important;
        color: #fff !important;
    }
    
    .hero-description {
        font-size: 10pt !important;
        margin: 0 0 12px 0 !important;
        color: #fff !important;
    }
    
    .hero-stats {
        gap: 12px !important;
        margin: 0 !important;
        justify-content: center !important;
    }
    
    .hero-stat {
        padding: 8px 12px !important;
        background: rgba(255,255,255,0.2) !important;
        min-width: auto !important;
    }
    
    .hero-stat-number {
        font-size: 16pt !important;
        margin-bottom: 2px !important;
    }
    
    .hero-stat-label,
    .hero-stat-title {
        font-size: 7pt !important;
    }
    
    /* ========== LAYOUT PRINCIPAL SIN ESPACIOS ========== */
    .main-content {
        padding: 0 !important;
        max-width: 100% !important;
        margin: 0 !important;
    }
    
    .section {
        margin-bottom: 18px !important;
        page-break-inside: avoid !important;
    }
    
    .section-header {
        margin-bottom: 12px !important;
        text-align: center !important;
    }
    
    .section-title {
        font-size: 14pt !important;
        margin: 0 0 5px 0 !important;
        color: #2c3e50 !important;
        line-height: 1.2 !important;
    }
    
    .section-subtitle {
        font-size: 9pt !important;
        color: #666 !important;
        margin: 0 !important;
    }
    
    /* ========== OVERVIEW COMPACTO ========== */
    .overview-grid {
        display: block !important;
        margin-bottom: 0 !important;
    }
    
    .overview-content {
        margin-bottom: 10px !important;
        padding: 10px !important;
        border: 1px solid #ddd !important;
        box-shadow: none !important;
        border-radius: 5px !important;
        page-break-inside: avoid !important;
    }
    
    .overview-content h3 {
        font-size: 10pt !important;
        margin: 0 0 8px 0 !important;
    }
    
    .overview-details {
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
        gap: 8px !important;
        margin-bottom: 10px !important;
    }
    
    .detail-item {
        padding: 8px !important;
        border: 1px solid #e9ecef !important;
        border-radius: 4px !important;
        background: #f8f9fa !important;
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
        page-break-inside: avoid !important;
    }
    
    .detail-icon {
        width: 28px !important;
        height: 28px !important;
        font-size: 12px !important;
        flex-shrink: 0 !important;
    }
    
    .detail-info h4 {
        font-size: 9pt !important;
        margin: 0 0 2px 0 !important;
    }
    
    .detail-info p {
        font-size: 8pt !important;
        margin: 0 !important;
        line-height: 1.2 !important;
    }
    
    .overview-summary {
        padding: 10px !important;
        margin: 0 !important;
    }
    
    .overview-summary h3 {
        font-size: 9pt !important;
        margin-bottom: 5px !important;
    }
    
    .overview-summary p {
        font-size: 8pt !important;
        line-height: 1.3 !important;
        margin: 0 !important;
    }
    
    /* ========== DÍAS SUPER COMPACTOS ========== */
    .itinerary-timeline::before {
        display: none !important;
    }
    
    .day-card {
        page-break-inside: avoid !important;
        margin-bottom: 12px !important;
        padding-left: 0 !important;
        position: relative !important;
    }
    
    .day-number {
        position: relative !important;
        left: auto !important;
        top: auto !important;
        width: auto !important;
        height: auto !important;
        display: inline-block !important;
        padding: 4px 10px !important;
        margin: 0 0 5px 0 !important;
        border: 2px solid #3498db !important;
        border-radius: 5px !important;
        background: #fff !important;
    }
    
    .day-number-main {
        font-size: 11pt !important;
        color: #2c3e50 !important;
        font-weight: 700 !important;
        line-height: 1 !important;
    }
    
    .day-number-label {
        font-size: 7pt !important;
        color: #7f8c8d !important;
        line-height: 1 !important;
    }
    
    .duration-badge {
        background: #3498db !important;
        color: #fff !important;
        font-size: 6pt !important;
        padding: 2px 5px !important;
        border-radius: 3px !important;
        position: absolute !important;
        top: -5px !important;
        right: -5px !important;
    }
    
    .day-content {
        border: 1px solid #ddd !important;
        border-radius: 5px !important;
        overflow: hidden !important;
        box-shadow: none !important;
        page-break-inside: avoid !important;
        margin: 0 !important;
    }
    
    .day-header {
        padding: 10px !important;
        border-bottom: 1px solid #e9ecef !important;
        background: #f8f9fa !important;
    }
    
    .day-title {
        font-size: 11pt !important;
        color: #2c3e50 !important;
        margin: 0 0 6px 0 !important;
        font-weight: 600 !important;
        line-height: 1.2 !important;
    }
    
    .day-location {
        font-size: 8pt !important;
        margin: 0 !important;
    }
    
    .primary-location,
    .secondary-locations-new {
        padding: 6px !important;
        margin: 0 0 6px 0 !important;
        page-break-inside: avoid !important;
    }
    
    .secondary-locations-new {
        background: #f8fffe !important;
        border: 1px solid #e8f5e8 !important;
        border-radius: 4px !important;
    }
    
    .secondary-header {
        margin-bottom: 5px !important;
        padding-bottom: 5px !important;
    }
    
    .secondary-header h4 {
        font-size: 8pt !important;
        margin: 0 !important;
    }
    
    .location-icon {
        width: 22px !important;
        height: 22px !important;
        font-size: 10px !important;
    }
    
    .location-marker {
        width: 18px !important;
        height: 18px !important;
        font-size: 7pt !important;
    }
    
    .location-item {
        padding: 4px 0 !important;
        gap: 6px !important;
    }
    
    .location-name {
        font-size: 8pt !important;
        line-height: 1.2 !important;
        margin: 0 !important;
    }
    
    .location-coords {
        font-size: 7pt !important;
    }
    
    /* ========== SERVICIOS COMPACTOS ========== */
    .day-services {
        padding: 10px !important;
    }
    
    .day-description {
        padding: 8px !important;
        margin-bottom: 8px !important;
        background: #f8f9fa !important;
        border-left: 3px solid #3498db !important;
        border-radius: 3px !important;
        font-size: 8pt !important;
        line-height: 1.3 !important;
        page-break-inside: avoid !important;
    }
    
    .day-description p {
        margin: 0 !important;
    }
    
    .stay-info-box {
        font-size: 7pt !important;
        padding: 6px !important;
        margin-top: 6px !important;
    }
    
    .services-grid {
        display: block !important;
    }
    
    .service-group {
        margin-bottom: 8px !important;
        border: 1px solid #e9ecef !important;
        border-radius: 4px !important;
        overflow: hidden !important;
        page-break-inside: avoid !important;
    }
    
    .service-item {
        padding: 8px !important;
        display: flex !important;
        align-items: flex-start !important;
        gap: 8px !important;
        page-break-inside: avoid !important;
        border-left: 3px solid #3498db !important;
    }
    
    .service-item.principal {
        background: #fff !important;
    }
    
    .service-item.alternativa {
        background: #f8f9fa !important;
        border-left-color: #95a5a6 !important;
        border-top: 1px solid #e9ecef !important;
    }
    
    .service-icon {
        width: 28px !important;
        height: 28px !important;
        font-size: 12px !important;
        flex-shrink: 0 !important;
        border-radius: 5px !important;
    }
    
    .service-icon.actividad {
        background: #e74c3c !important;
    }
    
    .service-icon.transporte {
        background: #3498db !important;
    }
    
    .service-icon.alojamiento {
        background: #f39c12 !important;
    }
    
    .service-details {
        flex: 1 !important;
        min-width: 0 !important;
    }
    
    .service-details h4 {
        font-size: 9pt !important;
        margin: 0 0 3px 0 !important;
        color: #2c3e50 !important;
        line-height: 1.2 !important;
    }
    
    .service-details p {
        font-size: 8pt !important;
        line-height: 1.2 !important;
        color: #555 !important;
        margin: 0 0 3px 0 !important;
    }
    
    .service-meta {
        font-size: 7pt !important;
        color: #888 !important;
        margin-top: 3px !important;
        display: flex !important;
        flex-wrap: wrap !important;
        gap: 6px !important;
    }
    
    .service-meta span {
        margin: 0 !important;
    }
    
    .extended-stay-badge,
    .duration-indicator {
        background: #6c757d !important;
        color: #fff !important;
        padding: 2px 6px !important;
        border-radius: 3px !important;
        font-size: 6pt !important;
        display: inline-block !important;
    }
    
    .alternative-badge {
        background: #95a5a6 !important;
        color: #fff !important;
        padding: 2px 5px !important;
        border-radius: 3px !important;
        font-size: 6pt !important;
    }
    
    .alternative-notes {
        margin-top: 5px !important;
        padding: 5px !important;
        font-size: 7pt !important;
        line-height: 1.2 !important;
    }
    
    /* ========== COMIDAS COMPACTO ========== */
    .day-meals {
        padding: 8px !important;
        margin-top: 8px !important;
        background: #fff9f0 !important;
        border-left: 3px solid #f39c12 !important;
        border-radius: 3px !important;
        page-break-inside: avoid !important;
    }
    
    .day-meals h4 {
        font-size: 8pt !important;
        margin: 0 0 5px 0 !important;
        color: #d35400 !important;
    }
    
    .meals-list {
        gap: 5px !important;
    }
    
    .meal-item {
        font-size: 7pt !important;
        padding: 4px 8px !important;
    }
    
    /* ========== ALTERNATIVAS ========== */
    .alternatives-list {
        max-height: none !important;
        overflow: visible !important;
        display: block !important;
        padding: 0 !important;
    }
    
    /* ========== PRECIOS COMPACTO ========== */
    .pricing-section {
        page-break-before: always !important;
        background: #fff !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    
    .pricing-content {
        padding: 0 !important;
    }
    
    .pricing-header {
        margin-bottom: 12px !important;
        text-align: center !important;
    }
    
    .pricing-header h2 {
        font-size: 14pt !important;
        margin: 0 0 5px 0 !important;
    }
    
    .pricing-header p {
        font-size: 9pt !important;
        margin: 0 !important;
    }
    
    .price-main-card {
        padding: 12px !important;
        border: 2px solid #3498db !important;
        border-radius: 5px !important;
        margin-bottom: 12px !important;
        page-break-inside: avoid !important;
        text-align: center !important;
    }
    
    .price-display {
        margin: 0 !important;
    }
    
    .price-amount {
        margin-bottom: 8px !important;
    }
    
    .price-currency {
        font-size: 10pt !important;
    }
    
    .price-value {
        font-size: 18pt !important;
        margin: 0 5px !important;
    }
    
    .price-per {
        font-size: 9pt !important;
    }
    
    .nights-included {
        padding: 6px 12px !important;
        font-size: 8pt !important;
        display: inline-flex !important;
        gap: 5px !important;
    }
    
    .pricing-accordions {
        margin-bottom: 0 !important;
    }
    
    .pricing-accordion {
        margin-bottom: 8px !important;
        border: 1px solid #ddd !important;
        border-radius: 4px !important;
        overflow: hidden !important;
        page-break-inside: avoid !important;
    }
    
    .accordion-header {
        padding: 8px 10px !important;
        background: #f8f9fa !important;
        border-bottom: 1px solid #e9ecef !important;
    }
    
    .accordion-title {
        font-size: 9pt !important;
        font-weight: 600 !important;
        display: flex !important;
        align-items: center !important;
        gap: 6px !important;
    }
    
    .accordion-title i {
        font-size: 10px !important;
    }
    
    .accordion-content {
        max-height: none !important;
        overflow: visible !important;
        display: block !important;
        padding: 8px 10px !important;
    }
    
    .pricing-list {
        margin: 0 !important;
        padding: 0 !important;
        list-style: none !important;
    }
    
    .pricing-list li {
        font-size: 8pt !important;
        padding: 3px 0 !important;
        line-height: 1.2 !important;
        display: flex !important;
        align-items: flex-start !important;
        gap: 6px !important;
    }
    
    .pricing-list li i {
        font-size: 8px !important;
        margin-top: 2px !important;
    }
    
    .conditions-text,
    .passport-info,
    .insurance-info,
    .additional-info,
    .accessibility-details {
        padding: 8px !important;
        font-size: 8pt !important;
        line-height: 1.3 !important;
        margin: 0 !important;
    }
    
    .status-badge {
        padding: 5px 10px !important;
        font-size: 7pt !important;
    }
    
    .accessibility-details ul {
        margin: 5px 0 !important;
        padding-left: 15px !important;
    }
    
    .accessibility-details li {
        font-size: 8pt !important;
        line-height: 1.2 !important;
        margin-bottom: 2px !important;
    }
    
    .accessibility-details p {
        font-size: 8pt !important;
        line-height: 1.3 !important;
        margin: 5px 0 !important;
    }
    
    /* ========== FOOTER COMPACTO ========== */
    .footer {
        background: #2c3e50 !important;
        color: #fff !important;
        padding: 12px !important;
        text-align: center !important;
        page-break-inside: avoid !important;
        margin-top: 15px !important;
    }
    
    .footer-content {
        padding: 0 !important;
    }
    
    .footer h3 {
        font-size: 12pt !important;
        margin: 0 0 5px 0 !important;
    }
    
    .footer p {
        font-size: 8pt !important;
        margin: 0 !important;
    }
    
    .footer-bottom {
        font-size: 7pt !important;
        margin-top: 8px !important;
        padding-top: 8px !important;
        border-top: 1px solid #34495e !important;
    }
    
    /* ========        @media print {
    /* ========== CONFIGURACIÓN GLOBAL ========== */
    * {
        -webkit-print-color-adjust: exact !important;
        color-adjust: exact !important;
        print-color-adjust: exact !important;
        box-sizing: border-box !important;
    }
    
    @page {
        margin: 15mm;
        size: A4 landscape;
    }
    
    html, body {
        width: 100% !important;
        height: auto !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    
    body {
        font-size: 9pt !important;
        line-height: 1.3 !important;
        color: #000 !important;
        background: #fff !important;
    }
    
    /* ========== OCULTAR ELEMENTOS ========== */
    .navbar, 
    .scroll-indicator, 
    .pricing-actions, 
    .footer-actions,
    .alternatives-header,
    .translate-container,
    #google_translate_element,
    .simple-image-modal,
    .day-images,
    .service-image,
    .accordion-arrow,
    .alternatives-toggle,
    .map-container,
    #map,
    .leaflet-container,
    .section:has(#map),
    section[id="map"] {
        display: none !important;
        height: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
        visibility: hidden !important;
    }
    
    /* ========== HERO SECTION SUPER COMPACTO ========== */
    .hero-section {
        height: 140px !important;
        min-height: 140px !important;
        max-height: 140px !important;
        background-attachment: scroll !important;
        page-break-after: always !important;
        margin: 0 !important;
        padding: 20px 15px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }
    
    .hero-content {
        text-align: center !important;
        max-width: 100% !important;
        padding: 0 !important;
    }
    
    .hero-title {
        font-size: 18pt !important;
        margin: 0 0 6px 0 !important;
        color: #fff !important;
        line-height: 1.1 !important;
    }
    
    .hero-subtitle {
        font-size: 9pt !important;
        margin: 0 0 4px 0 !important;
        color: #fff !important;
        display: none !important;
    }
    
    .hero-description {
        font-size: 9pt !important;
        margin: 0 0 10px 0 !important;
        color: #fff !important;
    }
    
    .hero-stats {
        gap: 10px !important;
        margin: 0 !important;
        justify-content: center !important;
    }
    
    .hero-stat {
        padding: 6px 10px !important;
        background: rgba(255,255,255,0.2) !important;
        min-width: auto !important;
    }
    
    .hero-stat-number {
        font-size: 14pt !important;
        margin-bottom: 1px !important;
    }
    
    .hero-stat-label,
    .hero-stat-title {
        font-size: 6pt !important;
    }
    
    /* ========== LAYOUT PRINCIPAL SIN ESPACIOS ========== */
    .main-content {
        padding: 0 !important;
        max-width: 100% !important;
        margin: 0 !important;
    }
    
    .section {
        margin-bottom: 12px !important;
        page-break-inside: avoid !important;
    }
    
    /* Ocultar sección completa del mapa */
    .section:nth-child(3),
    section#map,
    .section:has(.map-container) {
        display: none !important;
        height: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    
    .section-header {
        margin-bottom: 8px !important;
        text-align: center !important;
    }
    
    .section-title {
        font-size: 13pt !important;
        margin: 0 0 4px 0 !important;
        color: #2c3e50 !important;
        line-height: 1.1 !important;
    }
    
    .section-subtitle {
        font-size: 8pt !important;
        color: #666 !important;
        margin: 0 !important;
    }
    
    /* ========== OVERVIEW COMPACTO ========== */
    .overview-grid {
        display: block !important;
        margin-bottom: 0 !important;
    }
    
    .overview-content {
        margin-bottom: 10px !important;
        padding: 10px !important;
        border: 1px solid #ddd !important;
        box-shadow: none !important;
        border-radius: 5px !important;
        page-break-inside: avoid !important;
    }
    
    .overview-content h3 {
        font-size: 10pt !important;
        margin: 0 0 8px 0 !important;
    }
    
    .overview-details {
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
        gap: 8px !important;
        margin-bottom: 10px !important;
    }
    
    .detail-item {
        padding: 8px !important;
        border: 1px solid #e9ecef !important;
        border-radius: 4px !important;
        background: #f8f9fa !important;
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
        page-break-inside: avoid !important;
    }
    
    .detail-icon {
        width: 28px !important;
        height: 28px !important;
        font-size: 12px !important;
        flex-shrink: 0 !important;
    }
    
    .detail-info h4 {
        font-size: 9pt !important;
        margin: 0 0 2px 0 !important;
    }
    
    .detail-info p {
        font-size: 8pt !important;
        margin: 0 !important;
        line-height: 1.2 !important;
    }
    
    .overview-summary {
        padding: 10px !important;
        margin: 0 !important;
    }
    
    .overview-summary h3 {
        font-size: 9pt !important;
        margin-bottom: 5px !important;
    }
    
    .overview-summary p {
        font-size: 8pt !important;
        line-height: 1.3 !important;
        margin: 0 !important;
    }
    
    /* ========== DÍAS SUPER COMPACTOS ========== */
    .itinerary-timeline::before {
        display: none !important;
    }
    
    .day-card {
        page-break-inside: avoid !important;
        margin-bottom: 12px !important;
        padding-left: 0 !important;
        position: relative !important;
    }
    
    .day-number {
        position: relative !important;
        left: auto !important;
        top: auto !important;
        width: auto !important;
        height: auto !important;
        display: inline-block !important;
        padding: 4px 10px !important;
        margin: 0 0 5px 0 !important;
        border: 2px solid #3498db !important;
        border-radius: 5px !important;
        background: #fff !important;
    }
    
    .day-number-main {
        font-size: 11pt !important;
        color: #2c3e50 !important;
        font-weight: 700 !important;
        line-height: 1 !important;
    }
    
    .day-number-label {
        font-size: 7pt !important;
        color: #7f8c8d !important;
        line-height: 1 !important;
    }
    
    .duration-badge {
        background: #3498db !important;
        color: #fff !important;
        font-size: 6pt !important;
        padding: 2px 5px !important;
        border-radius: 3px !important;
        position: absolute !important;
        top: -5px !important;
        right: -5px !important;
    }
    
    .day-content {
        border: 1px solid #ddd !important;
        border-radius: 5px !important;
        overflow: hidden !important;
        box-shadow: none !important;
        page-break-inside: avoid !important;
        margin: 0 !important;
    }
    
    .day-header {
        padding: 10px !important;
        border-bottom: 1px solid #e9ecef !important;
        background: #f8f9fa !important;
    }
    
    .day-title {
        font-size: 11pt !important;
        color: #2c3e50 !important;
        margin: 0 0 6px 0 !important;
        font-weight: 600 !important;
        line-height: 1.2 !important;
    }
    
    .day-location {
        font-size: 8pt !important;
        margin: 0 !important;
    }
    
    .primary-location,
    .secondary-locations-new {
        padding: 6px !important;
        margin: 0 0 6px 0 !important;
        page-break-inside: avoid !important;
    }
    
    .secondary-locations-new {
        background: #f8fffe !important;
        border: 1px solid #e8f5e8 !important;
        border-radius: 4px !important;
    }
    
    .secondary-header {
        margin-bottom: 5px !important;
        padding-bottom: 5px !important;
    }
    
    .secondary-header h4 {
        font-size: 8pt !important;
        margin: 0 !important;
    }
    
    .location-icon {
        width: 22px !important;
        height: 22px !important;
        font-size: 10px !important;
    }
    
    .location-marker {
        width: 18px !important;
        height: 18px !important;
        font-size: 7pt !important;
    }
    
    .location-item {
        padding: 4px 0 !important;
        gap: 6px !important;
    }
    
    .location-name {
        font-size: 8pt !important;
        line-height: 1.2 !important;
        margin: 0 !important;
    }
    
    .location-coords {
        font-size: 7pt !important;
    }
    
    /* ========== SERVICIOS ========== */
    .day-services {
        padding: 15px !important;
    }
    
    .day-description {
        padding: 12px !important;
        margin-bottom: 15px !important;
        background: #f8f9fa !important;
        border-left: 3px solid #3498db !important;
        border-radius: 4px !important;
        font-size: 9pt !important;
        page-break-inside: avoid !important;
    }
    
    .services-grid {
        display: block !important;
    }
    
    .service-group {
        margin-bottom: 12px !important;
        border: 1px solid #e9ecef !important;
        border-radius: 6px !important;
        overflow: hidden !important;
        page-break-inside: avoid !important;
    }
    
    .service-item {
        padding: 12px !important;
        display: flex !important;
        align-items: flex-start !important;
        gap: 12px !important;
        page-break-inside: avoid !important;
        border-left: 3px solid #3498db !important;
    }
    
    .service-item.principal {
        background: #fff !important;
    }
    
    .service-item.alternativa {
        background: #f8f9fa !important;
        border-left: 3px solid #95a5a6 !important;
        border-top: 1px solid #e9ecef !important;
    }
    
    .service-icon {
        width: 35px !important;
        height: 35px !important;
        font-size: 14px !important;
        flex-shrink: 0 !important;
        border-radius: 8px !important;
    }
    
    .service-icon.actividad {
        background: #e74c3c !important;
    }
    
    .service-icon.transporte {
        background: #3498db !important;
    }
    
    .service-icon.alojamiento {
        background: #f39c12 !important;
    }
    
    .service-details {
        flex: 1 !important;
    }
    
    .service-details h4 {
        font-size: 11pt !important;
        margin-bottom: 5px !important;
        color: #2c3e50 !important;
    }
    
    .service-details p {
        font-size: 9pt !important;
        line-height: 1.3 !important;
        color: #555 !important;
        margin-bottom: 5px !important;
    }
    
    .service-meta {
        font-size: 8pt !important;
        color: #888 !important;
        margin-top: 5px !important;
    }
    
    .service-meta span {
        margin-right: 10px !important;
    }
    
    .extended-stay-badge,
    .duration-indicator {
        background: #6c757d !important;
        color: #fff !important;
        padding: 3px 8px !important;
        border-radius: 4px !important;
        font-size: 7pt !important;
    }
    
    .alternative-badge {
        background: #95a5a6 !important;
        color: #fff !important;
        padding: 2px 6px !important;
        border-radius: 4px !important;
        font-size: 7pt !important;
    }
    
    /* ========== COMIDAS ========== */
    .day-meals {
        padding: 12px !important;
        margin-top: 12px !important;
        background: #fff9f0 !important;
        border-left: 3px solid #f39c12 !important;
        border-radius: 4px !important;
        page-break-inside: avoid !important;
    }
    
    .day-meals h4 {
        font-size: 10pt !important;
        margin-bottom: 8px !important;
        color: #d35400 !important;
    }
    
    .meal-item {
        font-size: 8pt !important;
        padding: 5px 10px !important;
    }
    
    /* ========== ALTERNATIVAS ========== */
    .alternatives-list {
        max-height: none !important;
        overflow: visible !important;
        display: block !important;
        padding: 0 !important;
    }
    
    /* ========== PRECIOS ========== */
    .pricing-section {
        page-break-before: always !important;
        background: #fff !important;
        padding: 20px 0 !important;
        margin: 0 !important;
    }
    
    .pricing-header {
        margin-bottom: 20px !important;
    }
    
    .pricing-header h2 {
        font-size: 18pt !important;
    }
    
    .price-main-card {
        padding: 20px !important;
        border: 2px solid #3498db !important;
        border-radius: 8px !important;
        margin-bottom: 20px !important;
        page-break-inside: avoid !important;
    }
    
    .price-value {
        font-size: 24pt !important;
    }
    
    .pricing-accordion {
        margin-bottom: 12px !important;
        border: 1px solid #ddd !important;
        border-radius: 6px !important;
        overflow: hidden !important;
        page-break-inside: avoid !important;
    }
    
    .accordion-header {
        padding: 10px 12px !important;
        background: #f8f9fa !important;
        border-bottom: 1px solid #e9ecef !important;
    }
    
    .accordion-title {
        font-size: 10pt !important;
        font-weight: 600 !important;
    }
    
    .accordion-content {
        max-height: none !important;
        overflow: visible !important;
        display: block !important;
        padding: 12px !important;
    }
    
    .pricing-list {
        margin: 0 !important;
        padding: 0 !important;
    }
    
    .pricing-list li {
        font-size: 9pt !important;
        padding: 4px 0 !important;
        line-height: 1.3 !important;
    }
    
    /* ========== FOOTER ========== */
    .footer {
        background: #2c3e50 !important;
        color: #fff !important;
        padding: 20px !important;
        text-align: center !important;
        page-break-inside: avoid !important;
    }
    
    .footer h3 {
        font-size: 16pt !important;
        margin-bottom: 10px !important;
    }
    
    .footer p {
        font-size: 9pt !important;
    }
    
    .footer-bottom {
        font-size: 8pt !important;
        margin-top: 15px !important;
    }
    
    /* ========== TIPOGRAFÍA GENERAL ========== */
    h1, h2, h3, h4, h5, h6 {
        page-break-after: avoid !important;
        page-break-inside: avoid !important;
    }
    
    p, ul, ol {
        orphans: 3 !important;
        widows: 3 !important;
    }
    
    ul, ol {
        page-break-inside: avoid !important;
    }
}

/* ========== CLASE PRINT MODE ========== */
.print-mode .accordion-content,
.print-mode .alternatives-list {
    max-height: none !important;
    display: block !important;
    overflow: visible !important;
}
    
        
        /* ========================================
           DAY CONTENT - DISEÑO LIMPIO
           ======================================== */
        .day-content {
            background: #ffffff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
        }
        
        .day-content:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }
        
        .day-header {
            padding: 30px;
            background: #ffffff;
            border-bottom: 1px solid #e9ecef;
        }
        
        .day-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .day-location {
    margin-bottom: 20px;
}

/* Ubicación Principal */
.primary-location {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 15px 20px;
    background: #ffffff;
    border: 2px solid #3498db;
    border-radius: 12px;
    margin-bottom: 15px;
    box-shadow: 0 2px 8px rgba(52, 152, 219, 0.1);
}

.location-icon {
    width: 40px;
    height: 40px;
    background: #3498db;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 18px;
}

.location-info {
    flex: 1;
}

.location-title {
    font-weight: 600;
    color: #2c3e50;
    font-size: 16px;
    margin-bottom: 4px;
}

.location-subtitle {
    color: #7f8c8d;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Ubicaciones Secundarias como Lista Organizada */
.secondary-locations-new {
    margin-top: 15px;
    padding: 20px;
    background: #f8fffe;
    border-radius: 12px;
    border: 1px solid #e8f5e8;
}

.secondary-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #e8f5e8;
}

.secondary-header h4 {
    margin: 0;
    color: #27ae60;
    font-size: 14px;
    font-weight: 600;
}

.locations-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.location-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid rgba(39, 174, 96, 0.1);
}

.location-item:last-child {
    border-bottom: none;
}

.location-marker {
    width: 24px;
    height: 24px;
    background: #27ae60;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 10px;
    flex-shrink: 0;
    margin-top: 2px;
}

.location-details {
    flex: 1;
}

.location-name {
    font-weight: 500;
    color: #2c3e50;
    font-size: 14px;
    line-height: 1.4;
    margin-bottom: 4px;
}

.location-coords {
    font-size: 11px;
    color: #7f8c8d;
    display: flex;
    align-items: center;
    gap: 4px;
}
        
        /* Duration indicator minimalista */
        .duration-indicator {
            display: inline-flex;
            align-items: center;
            background: #f8f9fa;
            color: #6c757d;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            border: 1px solid #e9ecef;
        }
        
        .stay-duration-note {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #f8f9fa;
            color: #6c757d;
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 500;
            border: 1px solid #e9ecef;
            margin-left: 10px;
        }
        
        /* ========================================
           DAY IMAGES
           ======================================== */
         
        
        /* ========================================
   DAY IMAGES - MEJORADAS
   ======================================== */

   /* ========================================
   DAY IMAGES - VERSIÓN SIMPLE Y FUNCIONAL
   ======================================== */
.day-images {
    display: grid;
    grid-template-columns: 1fr 1fr;
    grid-template-rows: 1fr 1fr;
    gap: 15px;
    height: 400px;
    border-radius: 12px;
    overflow: hidden;
    margin: 20px 0;
}

.day-image {
    background-size: cover;
    background-position: center;
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
    cursor: pointer;
    border-radius: 6px;
}

.day-image:first-child {
    grid-row: span 2;
}

.day-image:hover {
    transform: scale(1.02);
    box-shadow: 0 5px 20px rgba(0,0,0,0.3);
}

.day-image::before {
    content: '🔍 Ver imagen';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.7);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: 600;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.day-image:hover::before {
    opacity: 1;
}

/* Modal simple para imágenes */
.simple-image-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.9);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 10000;
    padding: 20px;
}

.simple-image-modal.show {
    display: flex;
}

.simple-modal-content {
    position: relative;
    max-width: 90%;
    max-height: 90%;
    text-align: center;
}

.simple-modal-content img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    border-radius: 8px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.5);
}

.simple-modal-close {
    position: absolute;
    top: -15px;
    right: -15px;
    background: #e74c3c;
    color: white;
    border: none;
    width: 35px;
    height: 35px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 18px;
    font-weight: bold;
    display: flex;
    align-items: center;
    justify-content: center;
}

.simple-modal-close:hover {
    background: #c0392b;
}

@media (max-width: 768px) {
    .day-images {
        grid-template-columns: 1fr;
        height: 200px;
    }
    
    .day-image:first-child {
        grid-row: span 1;
    }
    
    .simple-modal-close {
        top: 10px;
        right: 10px;
    }
}
        /* ========================================
           DAY SERVICES
           ======================================== */
        .day-services {
            padding: 30px;
        }
        
        .day-description {
            margin-bottom: 25px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 12px;
            border-left: 4px solid #3498db;
        }
        
        .day-description p {
            margin: 0;
            color: #5a6c7d;
            line-height: 1.7;
        }
        
        .stay-info-box {
            margin-top: 15px;
            padding: 15px;
            background: #ffffff;
            border-radius: 8px;
            border: 1px solid #e9ecef;
            color: #6c757d;
            font-size: 14px;
        }
        
        .services-grid {
            display: grid;
            gap: 20px;
        }
        
        /* ========================================
           SERVICIOS - DISEÑO LIMPIO
           ======================================== */
        .service-group {
            margin-bottom: 20px;
            border: 1px solid #e9ecef;
            border-radius: 15px;
            overflow: hidden;
            background: #ffffff;
            transition: all 0.3s ease;
        }
        
        .service-group:hover {
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            border-color: #3498db;
        }
        
        .service-item {
            display: flex;
            gap: 20px;
            padding: 20px;
            transition: all 0.3s ease;
            align-items: flex-start;
        }
        
        .service-item.principal {
            background: #ffffff;
            border-left: 4px solid #3498db;
        }
        
        .service-item:hover {
            background: #f8f9fa;
        }
        
        /* Service icons organizados sin solapamiento */
        .service-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            flex-shrink: 0;
            margin-top: 5px;
        }
        
        .service-icon.actividad {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
        }
        
        .service-icon.transporte {
            background: linear-gradient(135deg, #3498db, #2980b9);
        }
        
        .service-icon.alojamiento {
            background: linear-gradient(135deg, #f39c12, #e67e22);
        }
        
        .service-details {
            flex: 1;
            min-width: 0;
        }
        
        .service-details h4 {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .service-details p {
            color: #7f8c8d;
            margin-bottom: 8px;
            font-size: 0.95rem;
            line-height: 1.5;
        }
        
        .service-meta {
            display: flex;
            gap: 15px;
            margin-top: 10px;
            flex-wrap: wrap;
        }
        
        .service-meta span {
            font-size: 0.85rem;
            color: #95a5a6;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .service-image {
            width: 150px;
            height: 150px;
            border-radius: 15px;
            background-size: cover;
            background-position: center;
            flex-shrink: 0;
            margin-top: 5px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .service-image::before {
            content: '🔍 Ver imagen';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 600;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .service-image:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
        }

        .service-image:hover::before {
            opacity: 1;
        }
        
        /* Extended stay badge minimalista */
        .extended-stay-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: #f8f9fa;
            color: #6c757d;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 0.75rem;
            font-weight: 600;
            border: 1px solid #e9ecef;
            margin-left: 8px;
        }
        
        /* ========================================
           MEALS SECTION
           ======================================== */
        .day-meals {
            margin-top: 20px;
            padding: 20px;
            background: #fff9f0;
            border-radius: 12px;
            border-left: 4px solid #f39c12;
            border: 1px solid #fef5e7;
        }
        
        .day-meals h4 {
            margin-bottom: 15px;
            color: #d35400;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .meals-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .meal-item {
            display: inline-flex;
            align-items: center;
            padding: 8px 12px;
            background: #ffffff;
            border-radius: 20px;
            font-size: 13px;
            color: #d35400;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #fef5e7;
            transition: all 0.3s ease;
        }
        
        .meal-item:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        .meal-item i {
            margin-right: 6px;
            color: #27ae60;
            font-size: 12px;
        }
        
        /* ========================================
           ALTERNATIVAS
           ======================================== */
        .alternatives-header {
            padding: 12px 20px;
            background: #f8f9fa;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
            color: #6c757d;
            font-size: 0.9rem;
            border-top: 1px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .alternatives-header:hover {
            background: #e9ecef;
            color: #495057;
        }
        
        .alternatives-header i {
            color: #6c757d;
        }
        
        .alternatives-toggle {
            margin-left: auto;
            transition: transform 0.3s ease;
        }
        
        .alternatives-toggle.rotated {
            transform: rotate(180deg);
        }
        
        .alternatives-list {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }
        
        .alternatives-list.expanded {
            max-height: 1000px;
        }
        
        .service-item.alternativa {
            background: #fafbfc;
            border-bottom: 1px solid #e9ecef;
            border-left: 3px solid #6c757d;
            position: relative;
        }
        
        .service-item.alternativa:last-child {
            border-bottom: none;
        }
        
        .service-item.alternativa .service-icon {
            background: linear-gradient(135deg, #6c757d, #495057) !important;
            width: 45px;
            height: 45px;
            font-size: 1rem;
        }
        
        .alternative-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            background: #6c757d;
            color: white;
            font-size: 9px;
            padding: 2px 6px;
            border-radius: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .alternative-notes {
            margin-top: 8px;
            padding: 8px 12px;
            background: rgba(108, 117, 125, 0.1);
            border-left: 3px solid #6c757d;
            border-radius: 4px;
            font-size: 0.85rem;
            color: #495057;
            font-style: italic;
        }
        
        /* ========================================
           PRICING SECTION
           ======================================== */
        .pricing-section {
            background: #f8f9fa;
            padding: 80px 0;
            margin: 100px 0;
            border-radius: 30px;
        }

        .pricing-content {
            max-width: 900px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .pricing-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .pricing-header h2 {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            color: #2c3e50;
            margin-bottom: 15px;
        }

        .pricing-header p {
            font-size: 1.1rem;
            color: #7f8c8d;
        }

        .price-main-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 40px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            border: 1px solid #e9ecef;
            text-align: center;
        }

        .price-display {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }

        .price-amount {
            display: flex;
            align-items: baseline;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .price-currency {
            font-size: 1.5rem;
            font-weight: 600;
            color: #7f8c8d;
        }

        .price-value {
            font-size: 3.5rem;
            font-weight: 700;
            color: #2c3e50;
        }

        .price-per {
            font-size: 1.2rem;
            color: #7f8c8d;
        }

        .nights-included {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px 25px;
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            border-radius: 50px;
            font-weight: 600;
        }

        .pricing-accordions {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-bottom: 40px;
        }

        .pricing-accordion {
            background: #ffffff;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 3px 15px rgba(0,0,0,0.05);
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
        }

        .pricing-accordion:hover {
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transform: translateY(-2px);
        }

        .accordion-header {
            padding: 20px 25px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
            transition: background-color 0.3s ease;
        }

        .accordion-header:hover {
            background: #f8f9fa;
        }

        .accordion-header.active {
            background: #f0f7ff;
        }

        .accordion-title {
            display: flex;
            align-items: center;
            gap: 15px;
            font-weight: 600;
            color: #2c3e50;
            font-size: 1.1rem;
        }

        .accordion-title i {
            font-size: 1.3rem;
        }

        .accordion-arrow {
            color: #7f8c8d;
            transition: transform 0.3s ease;
        }

        .accordion-arrow.rotated {
            transform: rotate(180deg);
        }

        .accordion-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease, padding 0.3s ease;
            background: #ffffff;
        }

        .accordion-content.active {
            max-height: 1000px;
            padding: 0 25px 25px 25px;
        }

        .pricing-list {
            list-style: none;
            padding: 0;
            margin: 15px 0 0 0;
        }

        .pricing-list li {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 8px 0;
            border-bottom: 1px solid #f8f9fa;
        }

        .pricing-list li:last-child {
            border-bottom: none;
        }

        .pricing-list.included i {
            color: #27ae60;
            margin-top: 2px;
        }

        .pricing-list.excluded i {
            color: #e74c3c;
            margin-top: 2px;
        }

        .pricing-list span {
            color: #2c3e50;
            line-height: 1.5;
        }

        .conditions-text,
        .passport-info,
        .insurance-info,
        .additional-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-top: 15px;
            line-height: 1.6;
            color: #5a6c7d;
            border-left: 4px solid #3498db;
        }

        .accessibility-info {
            margin-top: 15px;
        }

        .accessibility-status {
            margin-bottom: 15px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .status-badge.fully-accessible {
            background: #d5f4e6;
            color: #27ae60;
        }

        .status-badge.partially-accessible {
            background: #fef9e7;
            color: #f39c12;
        }

        .status-badge.not-accessible {
            background: #fdf2f2;
            color: #e74c3c;
        }

        .accessibility-details {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            color: #5a6c7d;
            line-height: 1.6;
        }

        .pricing-actions {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        /* ========================================
           FOOTER
           ======================================== */
        .footer {
            background: #2c3e50;
            color: white;
            text-align: center;
            padding: 60px 20px 30px;
        }
        
        .footer-content {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .footer h3 {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            margin-bottom: 20px;
        }
        
        .footer p {
            font-size: 1.1rem;
            margin-bottom: 30px;
            opacity: 0.9;
        }
        
        .footer-actions {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .btn {
            padding: 15px 30px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(52, 152, 219, 0.3);
            color: white;
        }
        
        .btn-outline {
            background: transparent;
            color: white;
            border: 2px solid white;
        }
        
        .btn-outline:hover {
            background: white;
            color: #2c3e50;
        }
        
        .footer-bottom {
            border-top: 1px solid #34495e;
            padding-top: 20px;
            font-size: 0.9rem;
            opacity: 0.7;
        }
        
        /* ========================================
           ANIMATIONS
           ======================================== */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0) translateX(-50%);
            }
            40% {
                transform: translateY(-10px) translateX(-50%);
            }
            60% {
                transform: translateY(-5px) translateX(-50%);
            }
        }
        
        /* ========================================
           RESPONSIVE DESIGN
           ======================================== */
        @media (max-width: 1024px) {
            .overview-grid {
                grid-template-columns: 1fr;
            }
            
            .day-card {
                padding-left: 120px;
            }
            
            .day-number {
                width: 80px;
                height: 60px;
            }
            
            .day-number-main {
                font-size: 1.2rem;
            }
        }
        
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-stats {
                gap: 20px;
            }
            
            .hero-stat {
                min-width: 100px;
                padding: 15px 20px;
            }
            
            .section-title {
                font-size: 2rem;
            }
            
            .overview-details {
                grid-template-columns: 1fr;
            }
            
            .day-images {
                grid-template-columns: 1fr;
                height: 200px;
            }
            
            .day-image:first-child {
                grid-row: span 1;
            }
            
            .itinerary-timeline::before {
                left: 40px;
            }
            
            .day-card {
                padding-left: 90px;
            }
            
            .day-number {
                width: 70px;
                height: 50px;
            }
            
            .day-number-main {
                font-size: 1rem;
            }
            
            .day-number-label {
                font-size: 0.6rem;
            }
            
            .navbar-nav {
                display: none;
            }
            
            .service-item {
                flex-direction: column;
                gap: 15px;
            }
            
            .service-icon {
                width: 45px;
                height: 45px;
                font-size: 1rem;
                margin-top: 0;
            }
            
            .service-meta {
                flex-direction: column;
                gap: 8px;
            }
            
            .day-title {
                font-size: 1.4rem;
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            
            .extended-stay-badge,
            .duration-indicator,
            .stay-duration-note {
                margin-left: 0;
                margin-top: 5px;
            }
        }
        
        @media (max-width: 480px) {
            .main-content {
                padding: 60px 15px;
            }
            
            .day-card {
                padding-left: 80px;
            }
            
            .day-number {
                width: 60px;
                height: 45px;
            }
            
            .day-number-main {
                font-size: 0.9rem;
            }
            
            .day-number-label {
                font-size: 0.55rem;
            }
            
            .itinerary-timeline::before {
                left: 30px;
            }
            
            .day-header,
            .day-services {
                padding: 20px;
            }
            
            .day-title {
                font-size: 1.2rem;
            }
            
            .service-item {
                padding: 15px;
            }
        }
        
        /* ========================================
           PRINT STYLES
           ======================================== */
        @media print {
            .navbar, .scroll-indicator, .pricing-actions, .footer-actions {
                display: none !important;
            }
            
            .hero-section {
                height: 200px !important;
                background-attachment: scroll !important;
            }
            
            .day-card {
                page-break-inside: avoid;
                margin-bottom: 20px !important;
            }
            
            .pricing-section {
                page-break-before: always;
            }
            
            .accordion-content {
                max-height: none !important;
                padding: 0 25px 25px 25px !important;
            }
            
            .alternatives-list {
                max-height: none !important;
            }
            
            body {
                font-size: 12px !important;
                background: #ffffff !important;
            }
            
            .section-title {
                font-size: 1.5rem !important;
            }
            
            .day-title {
                font-size: 1.2rem !important;
            }
            
            .map-container {
                display: none !important;
            }
        }
        /* ========================================
   UBICACIONES SECUNDARIAS - DISEÑO MEJORADO
   ======================================== */
.main-location {
    font-weight: 600;
    color: #2c3e50;
}

.secondary-locations-section {
    margin-top: 15px;
    padding: 15px;
    background: #f8fffe;
    border-radius: 12px;
    border-left: 4px solid #27ae60;
    border: 1px solid #e8f5e8;
}

.secondary-locations-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
    font-weight: 600;
    color: #27ae60;
    font-size: 0.9rem;
}

.secondary-locations-header i {
    font-size: 14px;
}

.secondary-locations-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.secondary-location-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 10px 12px;
    background: #ffffff;
    border-radius: 8px;
    border: 1px solid #e8f5e8;
    transition: all 0.3s ease;
}

.secondary-location-item:hover {
    transform: translateX(3px);
    box-shadow: 0 3px 10px rgba(39, 174, 96, 0.1);
    border-color: #27ae60;
}

.location-marker {
    width: 24px;
    height: 24px;
    background: linear-gradient(135deg, #27ae60, #2ecc71);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 10px;
    flex-shrink: 0;
    margin-top: 2px;
}

.location-details {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.location-name {
    font-weight: 500;
    color: #2c3e50;
    font-size: 0.9rem;
    line-height: 1.3;
}

.location-coords {
    font-size: 0.75rem;
    color: #7f8c8d;
    display: flex;
    align-items: center;
    gap: 4px;
}

.location-coords i {
    font-size: 10px;
    color: #95a5a6;
}

/* Responsive para ubicaciones secundarias */
@media (max-width: 768px) {
    .secondary-locations-section {
        margin-top: 10px;
        padding: 12px;
    }
    
    .secondary-location-item {
        padding: 8px 10px;
    }
    
    .location-name {
        font-size: 0.85rem;
    }
    
    .location-coords {
        font-size: 0.7rem;
    }
}

@media (max-width: 480px) {
    .secondary-locations-list {
        gap: 6px;
    }
    
    .secondary-location-item {
        gap: 8px;
    }
    
    .location-marker {
        width: 20px;
        height: 20px;
        font-size: 9px;
    }
}






/* Nuevos estilos para desglose de precios - COLORES ACTUALIZADOS */
.price-breakdown {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.price-categories {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    justify-content: center;
}

.price-category {
    flex: 1;
    min-width: 200px;
    max-width: 300px;
    padding: 20px;
    border-radius: 12px;
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border: 2px solid #e9ecef;
    display: flex;
    gap: 15px;
    align-items: center;
    transition: all 0.3s ease;
}

.price-category:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
}

/* Adultos - Azul coherente con el diseño */
.price-category.adulto {
    border-color: #3498db;
    background: linear-gradient(135deg, #f0f7ff 0%, #ffffff 100%);
}

.price-category.adulto .category-icon {
    background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
    box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
}

/* Niños - Verde coherente con ubicaciones secundarias */
.price-category.nino {
    border-color: #27ae60;
    background: linear-gradient(135deg, #f0fff4 0%, #ffffff 100%);
}

.price-category.nino .category-icon {
    background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
    box-shadow: 0 4px 12px rgba(39, 174, 96, 0.3);
}

.category-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
    flex-shrink: 0;
}

.category-info {
    flex: 1;
}

.category-label {
    font-size: 14px;
    color: #5a6c7d;
    font-weight: 600;
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.category-price {
    display: flex;
    align-items: baseline;
    gap: 5px;
    margin-bottom: 5px;
}

.category-price .price-currency {
    font-size: 16px;
    font-weight: 600;
    color: #2c3e50;
}

.category-price .price-value {
    font-size: 28px;
    font-weight: 800;
    color: #2c3e50;
}

.category-price .price-per {
    font-size: 12px;
    color: #7f8c8d;
}

.category-subtotal {
    font-size: 12px;
    color: #95a5a6;
    font-style: italic;
}

.price-total-section {
    margin-top: 10px;
}

.total-divider {
    height: 2px;
    background: linear-gradient(90deg, transparent, #e9ecef, transparent);
    margin-bottom: 20px;
}

/* Total - Degradado más suave coherente con el hero - TEXTO BLANCO */
.price-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 30px;
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    border-radius: 12px;
    color: #ffffff;
    box-shadow: 0 5px 20px rgba(44, 62, 80, 0.3);
}

.total-label {
    font-size: 18px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #ffffff;
}

.price-total .total-amount {
    display: flex;
    align-items: baseline;
    gap: 8px;
}

.price-total .price-currency {
    font-size: 20px;
    font-weight: 600;
    color: #ffffff;
}

.price-total .price-value {
    font-size: 36px;
    font-weight: 900;
    color: #ffffff;
}

/* Noches incluidas - Naranja coherente con alojamientos */
.nights-included {
    text-align: center;
    padding: 12px 20px;
    background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
    color: white;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    box-shadow: 0 4px 12px rgba(243, 156, 18, 0.3);
}

.nights-included i {
    font-size: 16px;
}

/* Responsive */
@media (max-width: 768px) {
    .price-categories {
        flex-direction: column;
    }
    
    .price-category {
        max-width: 100%;
    }
    
    .price-total {
        flex-direction: column;
        gap: 10px;
        text-align: center;
        padding: 20px;
    }
    
    .category-price .price-value {
        font-size: 24px;
    }
    
    .price-total .price-value {
        font-size: 28px;
        color: #ffffff;
    }
    
    .total-label {
        color: #ffffff;
    }
    
    .price-total .price-currency {
        color: #ffffff;
    }
    
    .category-icon {
        width: 45px;
        height: 45px;
        font-size: 20px;
    }
}

/* Estilos para impresión */
@media print {
    .price-breakdown {
        page-break-inside: avoid;
    }
    
    .price-category {
        border: 1px solid #ddd !important;
        box-shadow: none !important;
    }
    
    .price-category.adulto {
        border-color: #3498db !important;
    }
    
    .price-category.nino {
        border-color: #27ae60 !important;
    }
    
    .category-icon {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    .price-total {
        background: #2c3e50 !important;
        color: #ffffff !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    .price-total .total-label,
    .price-total .price-currency,
    .price-total .price-value {
        color: #ffffff !important;
    }
    
    .nights-included {
        background: #f39c12 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}

/* Animaciones suaves */
.price-category {
    animation: fadeInUp 0.6s ease-out;
}

.price-category:nth-child(1) {
    animation-delay: 0.1s;
}

.price-category:nth-child(2) {
    animation-delay: 0.2s;
}

.price-total-section {
    animation: fadeInUp 0.6s ease-out;
    animation-delay: 0.3s;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Estados de hover más refinados */
.price-category.adulto:hover {
    border-color: #2980b9;
    box-shadow: 0 8px 20px rgba(52, 152, 219, 0.2);
}

.price-category.nino:hover {
    border-color: #2ecc71;
    box-shadow: 0 8px 20px rgba(39, 174, 96, 0.2);
}

.price-category:hover .category-icon {
    transform: scale(1.05);
}

.price-total:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(44, 62, 80, 0.4);
}


    </style>
</head>

<body>
    <div class="translate-container">
        <div id="google_translate_element"></div>
    </div>
    <!-- Navigation Bar -->
    <nav class="navbar" id="navbar">
        <div class="navbar-content">
            <a href="#" class="navbar-brand"><?= htmlspecialchars($company_name) ?></a>
            <ul class="navbar-nav">
                <li><a href="#overview">Resumen</a></li>
                <li><a href="#map">Mapa</a></li>
                <li><a href="#itinerary">Itinerario</a></li>
                <?php if ($mostrar_precios): ?>
                    <li><a href="#pricing">Precios</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-content">
            <div class="hero-subtitle">Tu aventura perfecta</div>
            <h1 class="hero-title">Itinerario personalizado de <?= $duracion_dias ?> <?= $duracion_dias == 1 ? 'día' : 'días' ?></h1>
            <div class="hero-description">
                Diseñado especialmente para <strong><?= htmlspecialchars($nombre_viajero) ?></strong>
            </div>
            
            <div class="hero-stats">
                <div class="hero-stat">
                    <span class="hero-stat-number"><?= $duracion_dias ?></span>
                    <span class="hero-stat-label"><?= $duracion_dias == 1 ? 'Día' : 'Días' ?></span>
                </div>
                <div class="hero-stat">
                    <span class="hero-stat-number"><?= $num_pasajeros ?></span>
                    <span class="hero-stat-label"><?= $num_pasajeros == 1 ? 'Viajero' : 'Viajeros' ?></span>
                </div>
                <?php if ($fecha_inicio_formatted): ?>
                <div class="hero-stat">
                    <span class="hero-stat-title">Fecha de Salida</span>
                    <span class="hero-stat-number"><?= date('j', strtotime($programa['fecha_llegada'])) ?></span>
                    <span class="hero-stat-label"><?= date('M Y', strtotime($programa['fecha_llegada'])) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="scroll-indicator">
            <i class="fas fa-chevron-down"></i>
        </div>
    </section>

    <!-- Main Content -->
    <div class="main-content">
        
        <!-- Overview Section -->
        <section id="overview" class="section">
            <div class="section-header">
                <h2 class="section-title">Resumen del Viaje</h2>
                <p class="section-subtitle">
                    Todo lo que necesitas saber sobre tu próxima aventura
                </p>
            </div>
            
            <div class="overview-grid">
                <div class="overview-content">
                    <div class="overview-details">
                        <div class="detail-item">
                            <div class="detail-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="detail-info">
                                <h4>Destino</h4>
                                <p><?= htmlspecialchars($programa['destino']) ?></p>
                            </div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-icon">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div class="detail-info">
                                <h4>Fechas del Viaje</h4>
                                <p><strong>Salida:</strong> <?= $fecha_inicio_formatted ?><br>
                                <strong>Regreso:</strong> <?= $fecha_fin_formatted ?></p>
                            </div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="detail-info">
                                <h4>Viajeros</h4>
                                <p><?= $num_pasajeros ?> <?= $num_pasajeros == 1 ? 'persona' : 'personas' ?></p>
                            </div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-icon">
                                <i class="fas fa-route"></i>
                            </div>
                            <div class="detail-info">
                                <h4>Duración</h4>
                                <p><?= $duracion_dias ?> <?= $duracion_dias == 1 ? 'día increíble' : 'días increíbles' ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="overview-summary">
                        <h3>Sobre este viaje</h3>
                        <p>
                            Un itinerario cuidadosamente diseñado que combina los mejores destinos, 
                            experiencias únicas y servicios de calidad. Cada día está pensado para 
                            ofrecerte momentos inolvidables y la comodidad que mereces.
                        </p>
                    </div>
                </div>
                
                <div class="overview-content">
                    <h3 style="margin-bottom: 20px; color: #2c3e50;">Lo que incluye</h3>
                    <div style="display: flex; flex-direction: column; gap: 15px;">
                        <?php 
                        $total_actividades = 0;
                        $total_alojamientos = 0;
                        $total_transportes = 0;
                        
                        foreach ($dias as $dia) {
                            foreach ($dia['servicios'] as $servicio_grupo) {
                                $servicio = $servicio_grupo['principal'];
                                if ($servicio) {
                                    switch($servicio['tipo_servicio']) {
                                        case 'actividad': $total_actividades++; break;
                                        case 'alojamiento': $total_alojamientos++; break;
                                        case 'transporte': $total_transportes++; break;
                                    }
                                }
                            }
                        }
                        ?>
                        
                        <div class="detail-item">
                            <div class="detail-icon">
                                <i class="fas fa-route"></i>
                            </div>
                            <div class="detail-info">
                                <h4>Duración</h4>
                                <p><?= $duracion_dias ?> <?= $duracion_dias == 1 ? 'día' : 'días' ?> de aventura</p>
                            </div>
                        </div>
                        
                        <?php if ($total_alojamientos > 0): ?>
                        <div class="detail-item">
                            <div class="detail-icon" style="background: linear-gradient(135deg, #f39c12, #e67e22);">
                                <i class="fas fa-bed"></i>
                            </div>
                            <div class="detail-info">
                                <h4><?= $total_alojamientos ?> Alojamientos</h4>
                                <p>Hospedaje confortable y bien ubicado</p>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($total_transportes > 0): ?>
                        <div class="detail-item">
                            <div class="detail-icon" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                                <i class="fas fa-car"></i>
                            </div>
                            <div class="detail-info">
                                <h4><?= $total_transportes ?> Transportes</h4>
                                <p>Traslados cómodos y seguros</p>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($total_actividades > 0): ?>
                        <div class="detail-item">
                            <div class="detail-icon" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                                <i class="fas fa-hiking"></i>
                            </div>
                            <div class="detail-info">
                                <h4><?= $total_actividades ?> Actividades</h4>
                                <p>Experiencias únicas e inolvidables</p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- Map Section -->
        <?php if (!empty($puntos_mapa)): ?>
        <section id="map" class="section">
            <div class="section-header">
                <h2 class="section-title">Mapa del Viaje</h2>
                <p class="section-subtitle">
                    Explora todos los lugares que visitarás durante tu aventura
                </p>
            </div>
            
            <div class="map-container">
                <div id="map"></div>
            </div>
        </section>
        <?php endif; ?>

        <!-- Itinerary Section -->
        <section id="itinerary" class="section">
            <div class="section-header">
                <h2 class="section-title">Itinerario Día a Día</h2>
                <p class="section-subtitle">
                    Un recorrido detallado de cada momento de tu viaje
                </p>
            </div>
            
            <div class="itinerary-timeline">
                <?php 
                $diaActual = 1;
                foreach ($dias as $index => $dia): 
                    $duracion = (int)($dia['duracion_estancia'] ?? 1);
                    $diaFinal = $diaActual + $duracion - 1;
                    
                    // Texto del rango
                    $rangoTexto = $duracion === 1 
                        ? "Día {$diaActual}" 
                        : "Días {$diaActual}-{$diaFinal}";
                    
                    $duracionTexto = $duracion > 1 ? " ({$duracion} días)" : '';
                ?>
                <div class="day-card" style="animation-delay: <?= $index * 0.1 ?>s;">
                    <div class="day-number">
                        <div class="day-number-main">
                            <?= $duracion === 1 ? $diaActual : "{$diaActual}-{$diaFinal}" ?>
                        </div>
                        <div class="day-number-label">
                            <?= $duracion === 1 ? 'DÍA' : 'DÍAS' ?>
                        </div>
                        <?php if ($duracion > 1): ?>
                            <div class="duration-badge"><?= $duracion ?>d</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="day-content">
                        <div class="day-header">
                            <h3 class="day-title">
                                <?= $rangoTexto ?>: <?= htmlspecialchars($dia['titulo']) ?>
                                <?php if ($duracion > 1): ?>
                                    <span class="duration-indicator"><?= $duracionTexto ?></span>
                                <?php endif; ?>
                            </h3>
                            <div class="day-location">
                                <?php if ($duracion > 1): ?>
                                <div class="duration-badge" style="margin-bottom: 15px; display: inline-block;">
                                    <i class="fas fa-calendar-alt"></i> Estancia de <?= $duracion ?> días
                                </div>
                                <?php endif; ?>
                                
                                <!-- Todas las Ubicaciones Unificadas -->
                                <div class="secondary-locations-new">
                                    <div class="secondary-header">
                                        <i class="fas fa-map-marked-alt"></i>
                                        <h4>Lugares que visitarás:</h4>
                                    </div>
                                    
                                    <ul class="locations-list">
                                        <!-- Primera ubicación (antes llamada "principal") -->
                                        <li class="location-item">
                                            <div class="location-marker">1</div>
                                            <div class="location-details">
                                                <div class="location-name"><?= htmlspecialchars($dia['ubicacion']) ?></div>
                                            </div>
                                        </li>
                                        
                                        <?php if (!empty($dia['ubicaciones_secundarias'])): ?>
                                        <!-- Resto de ubicaciones -->
                                        <?php foreach ($dia['ubicaciones_secundarias'] as $index => $ubicacion_sec): ?>
                                        <li class="location-item">
                                            <div class="location-marker"><?= $index + 2 ?></div>
                                            <div class="location-details">
                                                <div class="location-name"><?= htmlspecialchars($ubicacion_sec['ubicacion']) ?></div>
                                                <?php if ($ubicacion_sec['latitud'] && $ubicacion_sec['longitud']): ?>
                                                <div class="location-coords">
                                                    <i class="fas fa-crosshairs"></i>
                                                    <?= number_format($ubicacion_sec['latitud'], 4) ?>, <?= number_format($ubicacion_sec['longitud'], 4) ?>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </li>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                                <?php if (empty($dia['ubicaciones_secundarias'])): ?>
                                <!-- Si solo hay una ubicación, cerrar el div aquí -->
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($dia['imagen1'] || $dia['imagen2'] || $dia['imagen3']): ?>
                        <div class="day-images">
                            <?php if ($dia['imagen1']): ?>
                            <div class="day-image" 
                                style="background-image: url('<?= htmlspecialchars($dia['imagen1']) ?>')"
                                onclick="showImage('<?= htmlspecialchars($dia['imagen1']) ?>')"></div>
                            <?php endif; ?>
                            
                            <?php if ($dia['imagen2']): ?>
                            <div class="day-image" 
                                style="background-image: url('<?= htmlspecialchars($dia['imagen2']) ?>')"
                                onclick="showImage('<?= htmlspecialchars($dia['imagen2']) ?>')"></div>
                            <?php endif; ?>
                            
                            <?php if ($dia['imagen3']): ?>
                            <div class="day-image" 
                                style="background-image: url('<?= htmlspecialchars($dia['imagen3']) ?>')"
                                onclick="showImage('<?= htmlspecialchars($dia['imagen3']) ?>')"></div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="day-services">
                            <?php if (!empty($dia['descripcion'])): ?>
                            <div class="day-description">
                                <p><?= nl2br(htmlspecialchars($dia['descripcion'])) ?></p>
                                <?php if ($duracion > 1): ?>
                                <div class="stay-info-box">
                                    <strong>Estancia Extendida:</strong> Estos servicios y actividades están disponibles durante toda tu estancia de <?= $duracion ?> días en <?= htmlspecialchars($dia['ubicacion']) ?>. Podrás disfrutar con total flexibilidad y sin prisas.
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>

                            <!-- Mostrar comidas si están incluidas -->
                            <?php if (isset($dia['comidas_incluidas']) && $dia['comidas_incluidas'] == 1): ?>
                                <div class="day-meals">
                                    <h4>
                                        <i class="fas fa-utensils"></i>
                                        Comidas incluidas
                                    </h4>
                                    <div class="meals-list">
                                        <?php if ($dia['desayuno'] == 1): ?>
                                            <span class="meal-item">
                                                <i class="fas fa-check"></i> 
                                                Desayuno
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($dia['almuerzo'] == 1): ?>
                                            <span class="meal-item">
                                                <i class="fas fa-check"></i> 
                                                Almuerzo
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($dia['cena'] == 1): ?>
                                            <span class="meal-item">
                                                <i class="fas fa-check"></i> 
                                                Cena
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($dia['servicios'])): ?>
                            <h4 style="margin-bottom: 20px; color: #2c3e50; font-size: 1.2rem; font-weight: 600;">
                                <i class="fas fa-list-ul"></i> Servicios incluidos
                                <?php if ($duracion > 1): ?>
                                    <span style="font-size: 0.8rem; color: #6c757d; font-weight: normal;">
                                        (Disponibles durante <?= $duracion ?> días)
                                    </span>
                                <?php endif; ?>
                            </h4>
                            
                            <div class="services-grid">
                                <?php foreach ($dia['servicios'] as $servicio_grupo): ?>
                                    <?php $servicio = $servicio_grupo['principal']; ?>
                                    <?php if ($servicio): ?>
                                    <div class="service-group">
                                        <!-- Servicio Principal -->
                                        <div class="service-item principal">
                                            <div class="service-icon <?= $servicio['tipo_servicio'] ?>">
                                                <i class="<?= getServiceIcon($servicio['tipo_servicio'], $servicio['medio_transporte']) ?>"></i>
                                            </div>
                                            
                                            <div class="service-details">
                                                <h4>
                                                    <?= htmlspecialchars($servicio['nombre']) ?>
                                                    <?php if ($duracion > 1 && $servicio['tipo_servicio'] == 'alojamiento'): ?>
                                                        <span class="extended-stay-badge">
                                                            <i class="fas fa-bed"></i>
                                                            <?= $duracion ?> noches
                                                        </span>
                                                    <?php endif; ?>
                                                </h4>
                                                
                                                <?php if ($servicio['descripcion']): ?>
                                                <p><?= htmlspecialchars($servicio['descripcion']) ?></p>
                                                <?php endif; ?>
                                                
                                                <div class="service-meta">
                                                    <?php if ($servicio['ubicacion']): ?>
                                                    <span>
                                                        <i class="fas fa-map-marker-alt"></i>
                                                        <?= htmlspecialchars($servicio['ubicacion']) ?>
                                                    </span>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($servicio['tipo_servicio'] == 'transporte' && $servicio['duracion']): ?>
                                                    <span>
                                                        <i class="fas fa-clock"></i>
                                                        <?= htmlspecialchars($servicio['duracion']) ?>
                                                    </span>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($servicio['tipo_servicio'] == 'transporte' && $servicio['medio_transporte']): ?>
                                                    <span>
                                                        <i class="<?= getServiceIcon('transporte', $servicio['medio_transporte']) ?>"></i>
                                                        <?= formatTransportMedium($servicio['medio_transporte']) ?>
                                                    </span>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($servicio['tipo_servicio'] == 'alojamiento' && $servicio['categoria_alojamiento']): ?>
                                                    <span>
                                                        <i class="fas fa-star"></i>
                                                        <?= $servicio['categoria_alojamiento'] ?> estrellas
                                                    </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <?php if ($servicio['imagen']): ?>
                                            <div class="service-image" 
                                                style="background-image: url('<?= htmlspecialchars($servicio['imagen']) ?>');"
                                                onclick="showImage('<?= htmlspecialchars($servicio['imagen']) ?>')"></div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Alternativas -->
                                        <?php if (!empty($servicio_grupo['alternativas'])): ?>
                                        <div class="alternatives-header" onclick="toggleAlternatives(<?= $servicio['id'] ?>)">
                                            <i class="fas fa-sync-alt"></i>
                                            <span><?= count($servicio_grupo['alternativas']) ?> alternativa<?= count($servicio_grupo['alternativas']) > 1 ? 's' : '' ?> disponible<?= count($servicio_grupo['alternativas']) > 1 ? 's' : '' ?></span>
                                            <i class="fas fa-chevron-down alternatives-toggle" id="toggle-<?= $servicio['id'] ?>"></i>
                                        </div>
                                        
                                        <div class="alternatives-list" id="alternatives-<?= $servicio['id'] ?>">
                                            <?php foreach ($servicio_grupo['alternativas'] as $alternativa): ?>
                                            <div class="service-item alternativa">
                                                <div class="alternative-badge">Alt <?= $alternativa['orden_alternativa'] ?></div>
                                                
                                                <div class="service-icon">
                                                    <i class="<?= getServiceIcon($alternativa['tipo_servicio'], $alternativa['medio_transporte']) ?>"></i>
                                                </div>
                                                
                                                <div class="service-details">
                                                    <h4 style="color: #495057; margin-bottom: 5px;">
                                                        <?= htmlspecialchars($alternativa['nombre']) ?>
                                                    </h4>
                                                    
                                                    <?php if ($alternativa['descripcion']): ?>
                                                    <p style="font-size: 0.9rem; color: #6c757d;">
                                                        <?= htmlspecialchars($alternativa['descripcion']) ?>
                                                    </p>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($alternativa['notas_alternativa']): ?>
                                                    <div class="alternative-notes">
                                                        <i class="fas fa-sticky-note"></i>
                                                        <?= htmlspecialchars($alternativa['notas_alternativa']) ?>
                                                    </div>
                                                    <?php endif; ?>
                                                    
                                                    <div class="service-meta" style="margin-top: 8px;">
                                                        <?php if ($alternativa['ubicacion']): ?>
                                                        <span style="font-size: 0.8rem;">
                                                            <i class="fas fa-map-marker-alt"></i>
                                                            <?= htmlspecialchars($alternativa['ubicacion']) ?>
                                                        </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                
                                                <?php if ($alternativa['imagen']): ?>
                                                <div class="service-image" 
                                                    style="width: 120px; height: 120px; background-image: url('<?= htmlspecialchars($alternativa['imagen']) ?>');"
                                                    onclick="showImage('<?= htmlspecialchars($alternativa['imagen']) ?>')"></div>
                                                <?php endif; ?>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                            
                            <?php endif; ?>
                            
                            
                        </div>
                    </div>
                </div>
                <?php 
                    $diaActual += $duracion;
                endforeach; 
                ?>
            </div>
        </section>

        <!-- Pricing Section -->
        <?php if ($mostrar_precios): ?>
        <section id="pricing" class="pricing-section">
            <div class="pricing-content">
                <div class="pricing-header">
                    <h2>Información de Precios</h2>
                    <p>Desglose detallado de la inversión de tu viaje</p>
                </div>
                
                
                <!-- Precio Principal - NUEVA ESTRUCTURA -->
                <div class="price-main-card">
                    <div class="price-breakdown">
                        <!-- Precios por Categoría -->
                        <div class="price-categories">
                            <?php 
                            $mostrar_adultos = isset($precios['precio_adulto']) && $precios['precio_adulto'] > 0;
                            $mostrar_ninos = isset($precios['precio_nino']) && $precios['precio_nino'] > 0;
                            $cantidad_adultos = $precios['cantidad_adultos'] ?? 1;
                            $cantidad_ninos = $precios['cantidad_ninos'] ?? 0;
                            ?>
                            
                            <?php if ($mostrar_adultos): ?>
                            <div class="price-category adulto">
                                <div class="category-icon">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="category-info">
                                    <div class="category-label">
                                        <?= $cantidad_adultos ?> <?= $cantidad_adultos == 1 ? 'Adulto' : 'Adultos' ?>
                                    </div>
                                    <div class="category-price">
                                        <span class="price-currency"><?= htmlspecialchars($precios['moneda']) ?></span>
                                        <span class="price-value"><?= number_format($precios['precio_adulto'], 0, ',', '.') ?></span>
                                        <span class="price-per">c/u</span>
                                    </div>
                                    <?php if ($cantidad_adultos > 1): ?>
                                    <div class="category-subtotal">
                                        Subtotal: <?= htmlspecialchars($precios['moneda']) ?> 
                                        <?= number_format($precios['precio_adulto'] * $cantidad_adultos, 0, ',', '.') ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($mostrar_ninos): ?>
                            <div class="price-category nino">
                                <div class="category-icon">
                                    <i class="fas fa-child"></i>
                                </div>
                                <div class="category-info">
                                    <div class="category-label">
                                        <?= $cantidad_ninos ?> <?= $cantidad_ninos == 1 ? 'Niño' : 'Niños' ?>
                                    </div>
                                    <div class="category-price">
                                        <span class="price-currency"><?= htmlspecialchars($precios['moneda']) ?></span>
                                        <span class="price-value"><?= number_format($precios['precio_nino'], 0, ',', '.') ?></span>
                                        <span class="price-per">c/u</span>
                                    </div>
                                    <?php if ($cantidad_ninos > 1): ?>
                                    <div class="category-subtotal">
                                        Subtotal: <?= htmlspecialchars($precios['moneda']) ?> 
                                        <?= number_format($precios['precio_nino'] * $cantidad_ninos, 0, ',', '.') ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Precio Total -->
                        <div class="price-total-section">
                            <div class="total-divider"></div>
                            <div class="price-total">
                                <span class="total-label">Precio Total</span>
                                <div class="total-amount">
                                    <span class="price-currency"><?= htmlspecialchars($precios['moneda']) ?></span>
                                    <span class="price-value"><?= number_format($precios['precio_total'], 0, ',', '.') ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Noches Incluidas -->
                        <?php if ($precios['noches_incluidas'] > 0): ?>
                        <div class="nights-included">
                            <i class="fas fa-bed"></i>
                            <?= $precios['noches_incluidas'] ?> <?= $precios['noches_incluidas'] == 1 ? 'noche incluida' : 'noches incluidas' ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Resto del contenido de precios (incluye/no incluye/condiciones) -->
                <div class="pricing-accordions">
                    <!-- Lo que incluye -->
                    <?php if (!empty($precios['precio_incluye'])): ?>
                    <div class="pricing-accordion">
                        <div class="accordion-header" onclick="toggleAccordion(this)">
                            <div class="accordion-title">
                                <i class="fas fa-check-circle" style="color: #27ae60;"></i>
                                <span>¿Qué incluye el precio?</span>
                            </div>
                            <i class="fas fa-chevron-down accordion-icon"></i>
                        </div>
                        <div class="accordion-content">
                            <ul class="pricing-list">
                                <?php 
                                $incluidos = explode("\n", $precios['precio_incluye']);
                                foreach ($incluidos as $item): 
                                    $item = trim($item);
                                    if (empty($item)) continue;
                                ?>
                                <li>
                                    <i class="fas fa-check" style="color: #27ae60;"></i>
                                    <?= nl2br(htmlspecialchars($item)) ?>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Lo que NO incluye -->
                    <?php if (!empty($precios['precio_no_incluye'])): ?>
                    <div class="pricing-accordion">
                        <div class="accordion-header" onclick="toggleAccordion(this)">
                            <div class="accordion-title">
                                <i class="fas fa-times-circle" style="color: #e74c3c;"></i>
                                <span>¿Qué NO incluye?</span>
                            </div>
                            <i class="fas fa-chevron-down accordion-icon"></i>
                        </div>
                        <div class="accordion-content">
                            <ul class="pricing-list">
                                <?php 
                                $no_incluidos = explode("\n", $precios['precio_no_incluye']);
                                foreach ($no_incluidos as $item): 
                                    $item = trim($item);
                                    if (empty($item)) continue;
                                ?>
                                <li>
                                    <i class="fas fa-times" style="color: #e74c3c;"></i>
                                    <?= nl2br(htmlspecialchars($item)) ?>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Condiciones generales -->
                    <?php if (!empty($precios['condiciones_generales'])): ?>
                    <div class="pricing-accordion">
                        <div class="accordion-header" onclick="toggleAccordion(this)">
                            <div class="accordion-title">
                                <i class="fas fa-file-contract" style="color: #3498db;"></i>
                                <span>Condiciones Generales</span>
                            </div>
                            <i class="fas fa-chevron-down accordion-icon"></i>
                        </div>
                        <div class="accordion-content">
                            <div class="conditions-text">
                                <?= nl2br(htmlspecialchars($precios['condiciones_generales'])) ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Información de Pasaporte -->
                    <?php if (!empty($precios['info_pasaporte'])): ?>
                    <div class="pricing-accordion">
                        <div class="accordion-header" onclick="toggleAccordion(this)">
                            <div class="accordion-title">
                                <i class="fas fa-passport" style="color: #9b59b6;"></i>
                                <span>Información de Pasaporte</span>
                            </div>
                            <i class="fas fa-chevron-down accordion-icon"></i>
                        </div>
                        <div class="accordion-content">
                            <div class="passport-text">
                                <?= nl2br(htmlspecialchars($precios['info_pasaporte'])) ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Información de Seguros -->
                    <?php if (!empty($precios['info_seguros'])): ?>
                    <div class="pricing-accordion">
                        <div class="accordion-header" onclick="toggleAccordion(this)">
                            <div class="accordion-title">
                                <i class="fas fa-shield-alt" style="color: #16a085;"></i>
                                <span>Información de Seguros</span>
                            </div>
                            <i class="fas fa-chevron-down accordion-icon"></i>
                        </div>
                        <div class="accordion-content">
                            <div class="insurance-text">
                                <?= nl2br(htmlspecialchars($precios['info_seguros'])) ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Movilidad Reducida -->
                    <?php if ($precios['movilidad_reducida']): ?>
                    <div class="accessibility-badge">
                        <i class="fas fa-wheelchair"></i>
                        <span>Este programa está adaptado para personas con movilidad reducida</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <h3>¿Listo para tu aventura?</h3>
            <p>Contáctanos para personalizar este itinerario según tus preferencias</p>
            
            <div class="footer-actions">
                
                <a href="#" class="btn btn-outline" onclick="downloadItinerary()">
                    <i class="fas fa-download"></i>
                    Descargar PDF
                </a>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($company_name) ?>. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript para funcionalidad -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // =====================================================
        // NAVBAR SCROLL FUNCTIONALITY
        // =====================================================
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 100) {
                navbar.classList.add('visible');
            } else {
                navbar.classList.remove('visible');
            }
        });

        // =====================================================
        // SMOOTH SCROLLING FOR NAVIGATION
        // =====================================================
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // =====================================================
        // MAP INITIALIZATION
        // =====================================================
        <?php if (!empty($puntos_mapa)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const puntosMapa = <?= json_encode($puntos_mapa) ?>;
            
            if (puntosMapa.length > 0) {
                let centerLat = puntosMapa.reduce((sum, loc) => sum + loc.lat, 0) / puntosMapa.length;
                let centerLng = puntosMapa.reduce((sum, loc) => sum + loc.lng, 0) / puntosMapa.length;
                
                const map = L.map('map').setView([centerLat, centerLng], 8);
                
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors'
                }).addTo(map);
                
                const iconColors = {
                    'actividad': '#e74c3c',
                    'alojamiento': '#f39c12',
                    'transporte': '#3498db',
                    'transporte_llegada': '#9b59b6'
                };
                
                puntosMapa.forEach(function(punto, index) {
                    const color = iconColors[punto.tipo] || '#95a5a6';
                    
                    const customIcon = L.divIcon({
                        html: `
                            <div style="
                                background-color: ${color};
                                width: 30px;
                                height: 30px;
                                border-radius: 50%;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                color: white;
                                font-weight: bold;
                                font-size: 12px;
                                border: 2px solid white;
                                box-shadow: 0 2px 8px rgba(0,0,0,0.3);
                            ">${punto.dia}</div>
                        `,
                        className: 'custom-div-icon',
                        iconSize: [30, 30],
                        iconAnchor: [15, 15]
                    });
                    
                    const marker = L.marker([punto.lat, punto.lng], {
                        icon: customIcon
                    }).addTo(map);
                    
                    const popupContent = `
                        <div style="text-align: center; min-width: 200px;">
                            <h4 style="margin: 0 0 8px 0; color: ${color}; font-size: 1rem;">
                                ${punto.titulo}
                            </h4>
                            <p style="margin: 0 0 6px 0; color: #666; font-size: 0.85rem;">
                                <i class="fas fa-${punto.tipo === 'actividad' ? 'hiking' : (punto.tipo === 'alojamiento' ? 'bed' : 'car')}"></i>
                                ${punto.tipo.charAt(0).toUpperCase() + punto.tipo.slice(1)} - Día ${punto.dia}
                            </p>
                            <p style="margin: 0 0 8px 0; color: #888; font-size: 0.8rem;">
                                <i class="fas fa-map-marker-alt"></i>
                                ${punto.ubicacion}
                            </p>
                            ${punto.descripcion ? `
                                <p style="margin: 8px 0 0 0; color: #555; font-size: 0.75rem; line-height: 1.3;">
                                    ${punto.descripcion.substring(0, 60)}...
                                </p>
                            ` : ''}
                            ${punto.imagen ? `
                                <img src="${punto.imagen}" 
                                     style="width: 100%; height: 80px; object-fit: cover; border-radius: 6px; margin-top: 8px;"
                                     alt="${punto.titulo}">
                            ` : ''}
                        </div>
                    `;
                    
                    marker.bindPopup(popupContent);
                });
                
                if (puntosMapa.length > 1) {
                    const group = new L.featureGroup(Object.values(map._layers).filter(layer => layer instanceof L.Marker));
                    map.fitBounds(group.getBounds().pad(0.1));
                }
                
                // Conexiones entre puntos del mismo día
                const puntosPerDia = {};
                puntosMapa.forEach(punto => {
                    if (!puntosPerDia[punto.dia]) {
                        puntosPerDia[punto.dia] = [];
                    }
                    puntosPerDia[punto.dia].push(punto);
                });
                
                const colores = ['#3498db', '#e74c3c', '#2ecc71', '#f39c12', '#9b59b6', '#1abc9c'];
                
                Object.keys(puntosPerDia).forEach((dia, index) => {
                    const puntosDia = puntosPerDia[dia];
                    if (puntosDia.length > 1) {
                        const coordenadas = puntosDia.map(p => [p.lat, p.lng]);
                        const color = colores[index % colores.length];
                        
                        L.polyline(coordenadas, {
                            color: color,
                            weight: 2,
                            opacity: 0.6,
                            dashArray: '5, 5'
                        }).addTo(map);
                    }
                });
            }
        });
        <?php endif; ?>

        // =====================================================
        // ACCORDION FUNCTIONALITY FOR PRICING SECTION
        // =====================================================
        function toggleAccordion(sectionId) {
            const content = document.getElementById(`content-${sectionId}`);
            const arrow = document.getElementById(`arrow-${sectionId}`);
            const header = arrow.closest('.accordion-header');
            
            // Cerrar otros accordions abiertos
            document.querySelectorAll('.accordion-content.active').forEach(function(otherContent) {
                if (otherContent.id !== `content-${sectionId}`) {
                    otherContent.classList.remove('active');
                    const otherId = otherContent.id.replace('content-', '');
                    const otherArrow = document.getElementById(`arrow-${otherId}`);
                    if (otherArrow) {
                        const otherHeader = otherArrow.closest('.accordion-header');
                        otherArrow.classList.remove('rotated');
                        otherHeader.classList.remove('active');
                    }
                }
            });
            
            // Toggle del accordion actual
            content.classList.toggle('active');
            arrow.classList.toggle('rotated');
            header.classList.toggle('active');
        }

        // =====================================================
        // ALTERNATIVES FUNCTIONALITY
        // =====================================================
        function toggleAlternatives(servicioId) {
            const alternativesList = document.getElementById(`alternatives-${servicioId}`);
            const toggle = document.getElementById(`toggle-${servicioId}`);
            
            if (alternativesList && toggle) {
                alternativesList.classList.toggle('expanded');
                toggle.classList.toggle('rotated');
                
                // Añadir efecto visual smooth
                if (alternativesList.classList.contains('expanded')) {
                    alternativesList.style.maxHeight = alternativesList.scrollHeight + 'px';
                } else {
                    alternativesList.style.maxHeight = '0px';
                }
            }
        }

        // =====================================================
        // ACTION BUTTONS FUNCTIONALITY
        // =====================================================
        function requestQuote() {
            // Crear modal personalizado para solicitar cotización
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10000;
                animation: fadeIn 0.3s ease;
            `;
            
            modal.innerHTML = `
                <div style="
                    background: white;
                    padding: 30px;
                    border-radius: 15px;
                    max-width: 400px;
                    width: 90%;
                    text-align: center;
                    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
                ">
                    <div style="font-size: 2.5rem; margin-bottom: 15px;">✈️</div>
                    <h3 style="margin-bottom: 10px; color: #2c3e50;">¡Solicita tu cotización!</h3>
                    <p style="color: #7f8c8d; margin-bottom: 25px; font-size: 0.9rem;">
                        Nos pondremos en contacto contigo para personalizar este increíble viaje
                    </p>
                    <div style="display: flex; gap: 10px; justify-content: center;">
                        <button onclick="this.closest('[style*=\"position: fixed\"]').remove()" 
                                style="padding: 10px 20px; background: #95a5a6; color: white; border: none; border-radius: 20px; cursor: pointer; font-size: 0.9rem;">
                            Cerrar
                        </button>
                        <button onclick="window.location.href='mailto:info@agencia.com?subject=Cotización Itinerario'" 
                                style="padding: 10px 20px; background: #3498db; color: white; border: none; border-radius: 20px; cursor: pointer; font-size: 0.9rem;">
                            Enviar Email
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Cerrar modal al hacer click fuera
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.remove();
                }
            });
        }

function downloadItinerary() {
    // Mostrar mensaje de preparación
    const loadingMsg = document.createElement('div');
    loadingMsg.style.cssText = `
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: white;
        padding: 30px 50px;
        border-radius: 15px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        z-index: 99999;
        text-align: center;
        font-family: 'Inter', sans-serif;
    `;
    loadingMsg.innerHTML = `
        <div style="font-size: 2rem; margin-bottom: 15px;">📄</div>
        <div style="font-size: 1.2rem; font-weight: 600; color: #2c3e50; margin-bottom: 10px;">
            Preparando PDF
        </div>
        <div style="font-size: 0.9rem; color: #7f8c8d;">
            Expandiendo todo el contenido...
        </div>
    `;
    document.body.appendChild(loadingMsg);
    
    // Preparar para impresión
    document.body.classList.add('print-mode');
    
    // Expandir TODO el contenido de accordions
    document.querySelectorAll('.accordion-content').forEach(content => {
        content.style.maxHeight = 'none';
        content.style.overflow = 'visible';
        content.style.display = 'block';
        content.style.padding = '15px 25px';
        content.classList.add('active');
    });
    
    // Expandir TODAS las alternativas
    document.querySelectorAll('.alternatives-list').forEach(list => {
        list.style.maxHeight = 'none';
        list.style.overflow = 'visible';
        list.style.display = 'block';
        list.classList.add('expanded');
    });
    
    // Rotar flechas de accordions
    document.querySelectorAll('.accordion-arrow').forEach(arrow => {
        arrow.classList.add('rotated');
    });
    
    document.querySelectorAll('.alternatives-toggle').forEach(toggle => {
        toggle.classList.add('rotated');
    });
    
    // Hacer visibles los headers de accordions como activos
    document.querySelectorAll('.accordion-header').forEach(header => {
        header.classList.add('active');
    });
    
    // Scroll to top antes de imprimir
    window.scrollTo(0, 0);
    
    // Delay para que se rendericen TODOS los cambios
    setTimeout(() => {
        loadingMsg.remove();
        window.print();
    }, 1200);
}

        // =====================================================
        // ANIMATION ON SCROLL
        // =====================================================
        document.addEventListener('DOMContentLoaded', function() {
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        entry.target.style.animationDelay = '0s';
                        entry.target.classList.add('animate');
                    }
                });
            }, observerOptions);

            // Observar elementos con animaciones
            document.querySelectorAll('.day-card, .service-group, .detail-item, .pricing-accordion').forEach(function(el) {
                observer.observe(el);
            });
        });

        // =====================================================
        // KEYBOARD ACCESSIBILITY
        // =====================================================
        document.addEventListener('keydown', function(e) {
            // ESC para cerrar modales
            if (e.key === 'Escape') {
                document.querySelectorAll('[style*="position: fixed"]').forEach(modal => {
                    if (modal.style.zIndex === '10000') {
                        modal.remove();
                    }
                });
            }
            
            // Enter y Space para activar accordions
            if ((e.key === 'Enter' || e.key === ' ') && e.target.closest('.accordion-header')) {
                e.preventDefault();
                e.target.closest('.accordion-header').click();
            }
        });

        // =====================================================
        // PRINT FUNCTIONALITY
        // =====================================================
        window.addEventListener('beforeprint', function() {
            console.log('Preparando documento para impresión...');
            
            // Expandir todos los accordions para impresión
            document.querySelectorAll('.accordion-content').forEach(function(content) {
                content.style.maxHeight = 'none';
                content.style.overflow = 'visible';
                content.style.display = 'block';
                content.style.padding = '15px';
                content.classList.add('active');
            });
            
            // Expandir todas las alternativas
            document.querySelectorAll('.alternatives-list').forEach(function(list) {
                list.style.maxHeight = 'none';
                list.style.overflow = 'visible';
                list.style.display = 'block';
                list.classList.add('expanded');
            });
            
            // Asegurar que todos los headers estén visibles
            document.querySelectorAll('.accordion-header').forEach(function(header) {
                header.classList.add('active');
            });
            
            // Expandir flechas
            document.querySelectorAll('.accordion-arrow, .alternatives-toggle').forEach(function(arrow) {
                arrow.classList.add('rotated');
            });
            
            // Aplicar clase print-mode
            document.body.classList.add('print-mode');
            
            console.log('Documento preparado para impresión');
        });

        window.addEventListener('afterprint', function() {
            console.log('Impresión finalizada, restaurando vista...');
            
            // Restaurar estado original después de impresión
            // Esperar un poco antes de colapsar para evitar flash visual
            setTimeout(() => {
                document.body.classList.remove('print-mode');
                
                // Solo colapsar los que NO estaban activos antes
                document.querySelectorAll('.accordion-content:not([data-was-active])').forEach(function(content) {
                    content.style.maxHeight = '0';
                    content.style.overflow = 'hidden';
                    content.classList.remove('active');
                });
                
                document.querySelectorAll('.alternatives-list:not([data-was-expanded])').forEach(function(list) {
                    list.style.maxHeight = '0';
                    list.style.overflow = 'hidden';
                    list.classList.remove('expanded');
                });
                
                console.log('Vista restaurada');
            }, 100);
        });

        // =====================================================
        // PERFORMANCE OPTIMIZATIONS
        // =====================================================
        
        // Throttle para eventos de scroll
        function throttle(func, limit) {
            let inThrottle;
            return function() {
                const args = arguments;
                const context = this;
                if (!inThrottle) {
                    func.apply(context, args);
                    inThrottle = true;
                    setTimeout(() => inThrottle = false, limit);
                }
            }
        }

        // =====================================================
        // INICIALIZACIÓN FINAL
        // =====================================================
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🌟 Itinerario cargado exitosamente');
            
            // Añadir clase para indicar que JS está cargado
            document.body.classList.add('js-loaded');
            
            // Precarga de imágenes críticas
            const criticalImages = document.querySelectorAll('.day-image');
            criticalImages.forEach((img, index) => {
                if (index < 3) { // Solo las primeras 3 imágenes
                    const preloadImg = new Image();
                    const bgImage = img.style.backgroundImage;
                    if (bgImage) {
                        preloadImg.src = bgImage.slice(5, -2); // Extraer URL de url("...")
                    }
                }
            });
        });

        // =====================================================
// IMAGE MODAL FUNCTIONALITY
// =====================================================
let currentImageIndex = 0;
let currentDayId = null;

function openImageModal(dayId, imageIndex = 0) {
    currentDayId = dayId;
    currentImageIndex = imageIndex;
    
    const modal = document.getElementById(`imageModal-${dayId}`);
    const modalImage = document.getElementById(`modalImage-${dayId}`);
    const counter = document.getElementById(`imageCounter-${dayId}`);
    const images = window[`dayImages${dayId}`];
    
    if (images && images[imageIndex]) {
        modalImage.src = images[imageIndex];
        counter.textContent = `${imageIndex + 1} de ${images.length}`;
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // Ocultar navegación si solo hay una imagen
        const prevBtn = modal.querySelector('.image-modal-prev');
        const nextBtn = modal.querySelector('.image-modal-next');
        
        if (images.length <= 1) {
            prevBtn.style.display = 'none';
            nextBtn.style.display = 'none';
        } else {
            prevBtn.style.display = 'flex';
            nextBtn.style.display = 'flex';
        }
    }
}

// =====================================================
// SIMPLE IMAGE MODAL FUNCTIONALITY
// =====================================================
function showImage(imageSrc) {
    const modal = document.getElementById('simpleImageModal');
    const modalImg = document.getElementById('modalImageSrc');
    
    modalImg.src = imageSrc;
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeImageModal() {
    const modal = document.getElementById('simpleImageModal');
    modal.classList.remove('show');
    document.body.style.overflow = '';
}

// Cerrar con ESC o click fuera
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeImageModal();
    }
});

document.addEventListener('click', function(e) {
    if (e.target.id === 'simpleImageModal') {
        closeImageModal();
    }
});
function nextImage(dayId) {
    const images = window[`dayImages${dayId}`];
    if (images && currentImageIndex < images.length - 1) {
        openImageModal(dayId, currentImageIndex + 1);
    } else if (images) {
        openImageModal(dayId, 0); // Volver al principio
    }
}

function prevImage(dayId) {
    const images = window[`dayImages${dayId}`];
    if (images && currentImageIndex > 0) {
        openImageModal(dayId, currentImageIndex - 1);
    } else if (images) {
        openImageModal(dayId, images.length - 1); // Ir al final
    }
}

// Cerrar modal con ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && currentDayId) {
        closeImageModal(currentDayId);
    }
    if (e.key === 'ArrowRight' && currentDayId) {
        nextImage(currentDayId);
    }
    if (e.key === 'ArrowLeft' && currentDayId) {
        prevImage(currentDayId);
    }
});

// Cerrar modal haciendo click fuera de la imagen
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('image-modal')) {
        if (currentDayId) {
            closeImageModal(currentDayId);
        }
    }
});

        // CSS adicional para animaciones dinámicas
        const additionalStyles = `
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            
            @keyframes slideInUp {
                from { transform: translateY(20px); opacity: 0; }
                to { transform: translateY(0); opacity: 1; }
            }
            
            .js-loaded .day-card {
                opacity: 1;
                transform: translateY(0);
            }
            
            .day-card {
                opacity: 0;
                transform: translateY(20px);
                transition: all 0.6s ease;
            }
            
            /* Hover effects suaves */
            .service-item:hover {
                background: #f8f9fa;
                transform: translateX(3px);
            }
            
            .day-number:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 20px rgba(0,0,0,0.12);
            }
            
            /* Mejoras para accesibilidad */
            .accordion-header:focus,
            .alternatives-header:focus {
                outline: 2px solid #3498db;
                outline-offset: 2px;
            }
            
            /* Mejoras para el mapa */
            .leaflet-popup-content {
                font-family: 'Inter', sans-serif;
            }
            
            .leaflet-popup-content-wrapper {
                border-radius: 8px;
            }
        `;
        
        const styleSheet = document.createElement('style');
        styleSheet.textContent = additionalStyles;
        document.head.appendChild(styleSheet);
    </script>
<!-- Modal simple para imágenes -->
<div id="simpleImageModal" class="simple-image-modal">
    <div class="simple-modal-content">
        <button class="simple-modal-close" onclick="closeImageModal()">&times;</button>
        <img id="modalImageSrc" src="" alt="Imagen ampliada">
    </div>
</div>
<script>
// JavaScript para Google Translate
document.addEventListener('DOMContentLoaded', function() {
    // Auto-aplicar idioma guardado
    setTimeout(() => {
        const savedLang = sessionStorage.getItem('language') || 
                         localStorage.getItem('preferredLanguage') || 
                         '<?= $programa['idioma_predeterminado'] ?? 'es' ?>';
        
        if (savedLang && savedLang !== '<?= $programa['idioma_predeterminado'] ?? 'es' ?>') {
            const select = document.querySelector('.goog-te-combo');
            if (select) {
                select.value = savedLang;
                select.dispatchEvent(new Event('change'));
            }
        }
    }, 1000);

    // Guardar idioma seleccionado
    setTimeout(function() {
        const select = document.querySelector('.goog-te-combo');
        if (select) {
            select.addEventListener('change', function() {
                if (this.value) {
                    sessionStorage.setItem('language', this.value);
                    localStorage.setItem('preferredLanguage', this.value);
                }
            });
        }
    }, 2000);
});
</script>
<script src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
</body>
</html>