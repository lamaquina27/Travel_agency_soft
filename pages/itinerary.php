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
} catch (Exception $e) {
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

    //---Conversión de días a fechas
    $fecha_base = !empty($programa['fecha_llegada']) ? new DateTime($programa['fecha_llegada']) : null;
    $dias_acumulados = 0;

    foreach ($dias as &$dia) {
        if ($fecha_base) {
            $fecha_dia = clone $fecha_base;

            if ($dias_acumulados > 0) {
                $fecha_dia->modify("+{$dias_acumulados} days");
            }

            $dia['fecha_calculada'] = $fecha_dia->format('Y-m-d');

            $duracion = intval($dia['duracion_estancia'] ?? 1);
            $fecha_fin_dia = clone $fecha_dia;

            if ($duracion > 1) {
                $fecha_fin_dia->modify('+' . ($duracion - 1) . ' days');
            }

            $dia['fecha_fin_calculada'] = $fecha_fin_dia->format('Y-m-d');
        }

        $dias_acumulados += intval($dia['duracion_estancia'] ?? 1);
    }

    unset($dia);




    // Cargar ubicaciones secundarias para cada día
    foreach ($dias as &$dia) {
        $dia['ubicaciones_secundarias'] = $db->fetchAll(
            "SELECT ubicacion, latitud, longitud, orden 
         FROM programa_dias_ubicaciones_secundarias 
         WHERE programa_dia_id = ? 
         ORDER BY orden ASC",
            [$dia['id']]
        );
    }
    unset($dia); // Romper la referencia
// ✅ DESPUÉS (nuevo código - ubicaciones del PROGRAMA, no de biblioteca):
    foreach ($dias as &$dia) {
        // Obtener ubicaciones secundarias del DÍA DEL PROGRAMA (aisladas)
        $dia['ubicaciones_secundarias'] = $db->fetchAll(
            "SELECT ubicacion, latitud, longitud, orden 
         FROM programa_dias_ubicaciones_secundarias 
         WHERE programa_dia_id = ? 
         ORDER BY orden ASC",
            [$dia['id']]
        );

        error_log("DEBUG - Día: " . $dia['titulo'] . " (ID: {$dia['id']}) -> Ubicaciones secundarias: " . count($dia['ubicaciones_secundarias']));
    }

    // Obtener servicios para cada día con todas las alternativas
    foreach ($dias as &$dia) {
        $servicios_raw = $db->fetchAll(
            "SELECT 
                pds.*,
                -- Los datos ya están copiados en programa_dias_servicios
                pds.nombre_servicio as nombre,
                pds.descripcion_servicio as descripcion,
                pds.ubicacion_servicio as ubicacion,
                pds.latitud,
                pds.longitud,
                
                -- Campos específicos de actividad
                pds.actividad_imagen1 as imagen,
                pds.actividad_imagen2 as imagen2,
                pds.actividad_imagen3 as imagen3,
                
                -- Campos específicos de transporte
                pds.transporte_medio as medio_transporte,
                pds.transporte_titulo,
                pds.transporte_lugar_salida,
                pds.transporte_lugar_llegada,
                pds.transporte_lat_salida as lat_salida,
                pds.transporte_lng_salida as lng_salida,
                pds.transporte_lat_llegada as lat_llegada,
                pds.transporte_lng_llegada as lng_llegada,
                pds.transporte_duracion as duracion,
                pds.transporte_distancia_km,
                
                -- Campos específicos de alojamiento
                pds.alojamiento_tipo as tipo_alojamiento,
                pds.alojamiento_categoria as categoria_alojamiento,
                pds.alojamiento_imagen as alojamiento_imagen_principal,
                pds.alojamiento_sitio_web,
                pds.acomodacion_id,
                a.tipo_acomodacion AS acomodacion_nombre,
                a.descripcion AS acomodacion_descripcion,
                a.acomodacion AS acomodacion_capacidad

                FROM programa_dias_servicios pds
                LEFT JOIN acomodaciones a ON pds.acomodacion_id = a.id
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
    $mostrar_precios = $precios && (!isset($precios['mostrar_precio']) || (int) $precios['mostrar_precio'] === 1);
    $vendido = ($programa['comprado']) ? 1 : 0;

    // Preparar datos para el mapa
// Preparar datos para el mapa - SOLO UBICACIONES DE DÍAS
    $puntos_mapa = [];
    foreach ($dias as $dia) {
        // Agregar ubicación principal del día
        if ($dia['latitud'] && $dia['longitud']) {
            $puntos_mapa[] = [
                'lat' => floatval($dia['latitud']),
                'lng' => floatval($dia['longitud']),
                'titulo' => $dia['titulo'],
                'descripcion' => $dia['descripcion'],
                'tipo' => 'dia',
                'dia' => $dia['dia_numero'],
                'ubicacion' => $dia['ubicacion'],
                'imagen' => $dia['imagen1'] ?? null
            ];
        }

        // Agregar ubicaciones secundarias del día
        if (!empty($dia['ubicaciones_secundarias'])) {
            foreach ($dia['ubicaciones_secundarias'] as $index => $ubicacion_sec) {
                if ($ubicacion_sec['latitud'] && $ubicacion_sec['longitud']) {
                    $puntos_mapa[] = [
                        'lat' => floatval($ubicacion_sec['latitud']),
                        'lng' => floatval($ubicacion_sec['longitud']),
                        'titulo' => 'Ubicación ' . ($index + 2),  // ✅ CAMBIADO
                        'descripcion' => $dia['titulo'],  // ✅ SIMPLIFICADO
                        'tipo' => 'dia',  // ✅ CAMBIADO de 'ubicacion_secundaria' a 'dia'
                        'dia' => $dia['dia_numero'],
                        'ubicacion' => $ubicacion_sec['ubicacion'],
                        'imagen' => null
                    ];
                }
            }
        }
    }

} catch (Exception $e) {
    error_log("Error cargando programa: " . $e->getMessage());
    header('Location: ' . APP_URL . '/itinerarios');
    exit;
}

// Funciones helper
function getServiceIcon($tipo, $medio_transporte = null)
{
    switch ($tipo) {
        case 'actividad':
            return 'fas fa-hiking';

        case 'transporte':
            if ($medio_transporte) {
                switch (strtolower($medio_transporte)) {
                    case 'avion':
                        return 'fas fa-plane';
                    case 'bus':
                        return 'fas fa-bus';
                    case 'tren':
                        return 'fas fa-train';
                    case 'barco':
                        return 'fas fa-ship';
                    case 'coche':
                        return 'fas fa-car';
                    default:
                        return 'fas fa-plane';
                }
            }
            return 'fas fa-plane';

        case 'alojamiento':
            return 'fas fa-bed';

        default:
            return 'fas fa-map-marker-alt';
    }
}

function formatTransportMedium($medio)
{
    $medios = [
        'avion' => 'Avión',
        'bus' => 'Bus',
        'coche' => 'Coche',
        'barco' => 'Barco',
        'tren' => 'Tren'
    ];
    return $medios[$medio] ?? ucfirst($medio);
}

function formatAccommodationType($tipo)
{
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

// Datos para el template
$titulo_programa = $programa['titulo_programa'] ?: 'Viaje a ' . $programa['destino'];
$nombre_viajero = trim(
    ($programa['nombre'] ?? $programa['nombre_viajero'] ?? '') . ' ' .
    ($programa['apellido'] ?? $programa['apellido_viajero'] ?? '')
);

if ($nombre_viajero === '') {
    $nombre_viajero = 'tu viaje';
}
// Normalizar imagen: extraer solo el path si es URL absoluta y reconstruir
// con APP_URL local. Funciona con BD del hosting o local sin cambios.
$_foto_raw = $programa['foto_portada'] ?? '';
if ($_foto_raw) {
    if (str_starts_with($_foto_raw, 'http://') || str_starts_with($_foto_raw, 'https://')) {
        $_foto_raw = parse_url($_foto_raw, PHP_URL_PATH); // → /assets/uploads/...
    }
    $_foto_raw = rtrim(APP_URL, '/') . '/' . ltrim($_foto_raw, '/');
}


// Calcular duración ------------------------- También se movió para evitar error de no inicialización
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


$imagen_portada = $_foto_raw ?: 'https://images.unsplash.com/photo-1488646953014-85cb44e25828?w=1200&h=600&fit=crop';
$num_dias = $duracion_dias;
$num_pasajeros = $programa['numero_pasajeros'];


// Paleta configurada por el usuario / agencia.
// Se toman varios nombres posibles para no depender de una sola clave de configuración.
function ts_safe_hex_color($value, $fallback)
{
    $value = trim((string) $value);
    if ($value === '')
        return $fallback;
    if ($value[0] !== '#')
        $value = '#' . $value;
    return preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? $value : $fallback;
}

$brand_primary = ts_safe_hex_color(
    $config['primary_color']
    ?? $config['color_principal']
    ?? $config['brand_color']
    ?? $config['accent_color']
    ?? $config['main_color']
    ?? $config['color']
    ?? '',
    '#2f3437'
);

$brand_secondary = ts_safe_hex_color(
    $config['secondary_color']
    ?? $config['color_secundario']
    ?? $config['brand_secondary_color']
    ?? $config['accent_secondary_color']
    ?? '',
    $brand_primary
);

function ts_spanish_day_name($date)
{
    $dias = [
        'Monday' => 'lunes',
        'Tuesday' => 'martes',
        'Wednesday' => 'miércoles',
        'Thursday' => 'jueves',
        'Friday' => 'viernes',
        'Saturday' => 'sábado',
        'Sunday' => 'domingo'
    ];
    return $dias[$date->format('l')] ?? strtolower($date->format('l'));
}

function ts_spanish_month_name($date)
{
    $meses = [
        1 => 'enero',
        2 => 'febrero',
        3 => 'marzo',
        4 => 'abril',
        5 => 'mayo',
        6 => 'junio',
        7 => 'julio',
        8 => 'agosto',
        9 => 'septiembre',
        10 => 'octubre',
        11 => 'noviembre',
        12 => 'diciembre'
    ];
    return $meses[(int) $date->format('n')] ?? strtolower($date->format('F'));
}

function ts_full_spanish_date($dateString)
{
    if (empty($dateString))
        return '';
    try {
        $date = $dateString instanceof DateTime ? clone $dateString : new DateTime($dateString);
        return ts_spanish_day_name($date) . ', ' . (int) $date->format('d') . ' de ' . ts_spanish_month_name($date) . ' de ' . $date->format('Y');
    } catch (Exception $e) {
        return '';
    }
}

function ts_day_range_label($startDay, $duration)
{
    $startDay = (int) $startDay;
    $duration = max(1, (int) $duration);
    $endDay = $startDay + $duration - 1;

    if ($duration === 1) {
        return 'Día ' . $startDay;
    }

    if ($duration === 2) {
        return 'Días ' . $startDay . ' y ' . $endDay;
    }

    $days = range($startDay, $endDay);
    $last = array_pop($days);
    return 'Días ' . implode(', ', $days) . ' y ' . $last;
}

function ts_date_range_label($startDateString, $endDateString, $duration = 1)
{
    if (empty($startDateString))
        return '';

    try {
        $start = new DateTime($startDateString);
        $end = !empty($endDateString) ? new DateTime($endDateString) : clone $start;
        $duration = max(1, (int) $duration);

        if ($duration <= 1 || $start->format('Y-m-d') === $end->format('Y-m-d')) {
            return ts_full_spanish_date($start);
        }

        if ($start->format('Y') === $end->format('Y')) {
            if ($start->format('m') === $end->format('m')) {
                return 'del ' . ts_spanish_day_name($start) . ' ' . (int) $start->format('d') . ' al ' . ts_spanish_day_name($end) . ' ' . (int) $end->format('d') . ' de ' . ts_spanish_month_name($end) . ' de ' . $end->format('Y');
            }

            return 'del ' . ts_spanish_day_name($start) . ' ' . (int) $start->format('d') . ' de ' . ts_spanish_month_name($start) . ' al ' . ts_spanish_day_name($end) . ' ' . (int) $end->format('d') . ' de ' . ts_spanish_month_name($end) . ' de ' . $end->format('Y');
        }

        return 'del ' . ts_full_spanish_date($start) . ' al ' . ts_full_spanish_date($end);
    } catch (Exception $e) {
        return '';
    }
}



// Calcular fechas basado en fecha de llegada + días del programa
$fecha_inicio_formatted = '';
$fecha_fin_formatted = '';

if ($programa['fecha_llegada']) {
    $fecha_inicio = new DateTime($programa['fecha_llegada']);
    $fecha_inicio_formatted = $fecha_inicio->format('d M Y');

    // Calcular fecha de salida: fecha_llegada + duración_días (incluye día adicional de regreso)
    $fecha_fin = clone $fecha_inicio;
    $fecha_fin->add(new DateInterval('P' . $duracion_dias . 'D'));
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
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" rel="stylesheet">

    <style>
        :root {
            --brand-primary:
                <?= $brand_primary ?>
            ;
            --brand-secondary:
                <?= $brand_secondary ?>
            ;
            --brand-surface: #ffffff;
            --brand-surface-soft: #f8f9fa;
            --brand-border: #e9ecef;
            --brand-text: #2f3437;
            --brand-muted: var(--brand-muted);
            --brand-soft: color-mix(in srgb, var(--brand-primary) 8%, #ffffff);
            --brand-soft-strong: color-mix(in srgb, var(--brand-primary) 14%, #ffffff);
            --brand-shadow: 0 18px 45px rgba(20, 24, 28, 0.08);
            --brand-radius: 22px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: var(--brand-text);
            background: #ffffff;
        }

        /* ========================================
           HERO SECTION
           ======================================== */
        .hero-section {
            height: 100vh;
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.7)), url('<?= addslashes($imagen_portada) ?>');
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        /* Capa de fondo con animación de zoom */
        .hero-section::before {
            content: '';
            position: absolute;
            top: -5%;
            left: -5%;
            width: 110%;
            height: 110%;
            background-image: inherit;
            background-size: cover;
            background-position: center;
            animation: slowZoom 25s ease-in-out infinite alternate;
            z-index: -1;
        }

        @keyframes continuousZoom {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.1);
            }
        }

        .hero-program-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            font-weight: 600;
            margin: 15px 0 20px 0;
            color: #ffffff;
            text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.6);
            animation: fadeInUp 1s ease-out 0.3s both;
        }

        @media (max-width: 768px) {
            .hero-program-title {
                font-size: 1.8rem;
            }

            .hero-title {
                font-size: 2rem;
            }
        }

        .hero-content {
            max-width: 1000px;
            padding: 0 20px;
            animation: fadeInUp 1.2s ease-out;
        }

        .hero-subtitle {
            font-size: 1rem;
            margin-bottom: 20px;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 3px;
            font-weight: 400;
            animation: fadeInDown 1s ease-out 0.2s both;
        }

        .hero-title {
            font-weight: 700;
            font-size: 5rem;
            text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.5);
            letter-spacing: 1px;
            color: #ffffff;
        }

        .hero-description {
            font-size: 1.4rem;
            margin-bottom: 40px;
            opacity: 0.95;
            font-weight: 300;
            animation: fadeInUp 1s ease-out 0.6s both;
        }

        .hero-stats {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-bottom: 40px;
            flex-wrap: wrap;
            animation: fadeInUp 1s ease-out 0.8s both;
        }

        .hero-stat {
            text-align: center;
            background: rgba(255, 255, 255, 0.1);
            padding: 25px 30px;
            border-radius: 20px;
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            min-width: 130px;
            transition: all 0.3s ease;
        }

        .hero-stat:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
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
            color: rgba(255, 255, 255, 0.8);
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
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 15px 0;
            z-index: 1000;
            transform: translateY(-100%);
            transition: transform 0.3s ease;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
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
            color: var(--brand-text);
            text-decoration: none;
        }

        .navbar-nav {
            display: flex;
            gap: 30px;
            list-style: none;
        }

        /* Nueva clase para nivelar menú */
        .navbar-topmargin {
            margin-top: 8px;
        }

        .navbar-nav a {
            color: var(--brand-text);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .navbar-nav a:hover {
            color: var(--brand-primary);
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
            color: var(--brand-text);
            margin-bottom: 15px;
        }

        .section-subtitle {
            font-size: 1.2rem;
            color: var(--brand-muted);
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
            padding: 1px 3px;
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
            color: var(--brand-text) !important;
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
            background: color-mix(in srgb, var(--brand-primary) 18%, transparent) !important;
            color: var(--brand-primary) !important;
        }

        .VIpgJd-ZVi9od-xl07Ob-lTBxed img {
            display: none !important;
        }

        .VIpgJd-ZVi9od-xl07Ob-lTBxed span[style*="border-left"] {
            display: none !important;
        }

        .VIpgJd-ZVi9od-xl07Ob-lTBxed span[aria-hidden="true"] {
            color: var(--brand-muted) !important;
            font-size: 12px !important;
            margin-left: 6px !important;
            transition: all 0.2s ease !important;
        }

        .VIpgJd-ZVi9od-xl07Ob-lTBxed:hover span[aria-hidden="true"] {
            color: var(--brand-primary) !important;
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
            color: var(--brand-text) !important;
            padding: 12px 18px !important;
            transition: all 0.15s ease !important;
            cursor: pointer !important;
            border: none !important;
            margin: 0 8px !important;
            border-radius: 8px !important;
        }

        .goog-te-menu2-item:hover {
            background: color-mix(in srgb, var(--brand-primary) 18%, transparent) !important;
            color: var(--brand-primary) !important;
            transform: translateX(3px) !important;
        }

        .goog-te-menu2-item-selected {
            background: var(--brand-primary) !important;
            color: white !important;
            font-weight: 600 !important;
        }

        .goog-te-banner-frame.skiptranslate {
            display: none !important;
        }

        /*Cambios para selector de menu (desplegable) */
        .VIpgJd-ZVi9od-vH1Gmf {
            background: rgba(255, 255, 255, 0.95) !important;
            border: none !important;
            padding: 8px 0 !important;
        }

        .VIpgJd-ZVi9od-vH1Gmf-ibnC6b-gk6SMd div {
            color: var(--brand-primary) !important;
        }

        .VIpgJd-ZVi9od-vH1Gmf-ibnC6b div {
            color: var(--brand-text);
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
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
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
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }

        .detail-icon {
            width: 50px;
            height: 50px;
            background: var(--brand-primary);
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
            color: var(--brand-text);
        }

        .detail-info p {
            color: var(--brand-muted);
            margin: 0;
        }

        .overview-summary {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 15px;
            border-left: 5px solid var(--brand-primary);
        }

        .overview-summary h3 {
            color: var(--brand-text);
            margin-bottom: 15px;
            font-size: 1.3rem;
        }

        .overview-summary p {
            color: var(--brand-muted);
            line-height: 1.8;
        }

        /* ========================================
           MAP SECTION
           ======================================== */
        .map-container {
            background: #ffffff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            height: 500px;
            border: 1px solid #e9ecef;

        }

        #map {
            height: 500px;
            width: 100%;
        }

        /* Tooltip de instrucciones del mapa */
        .map-tooltip {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(255, 255, 255, 0.95);
            color: var(--brand-text);
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            display: none;
            align-items: center;
            gap: 8px;
            z-index: 1000;
            pointer-events: none;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.2);
            border: 1px solid color-mix(in srgb, var(--brand-primary) 18%, transparent);
        }

        .map-tooltip i {
            font-size: 16px;
            color: var(--brand-primary);
        }

        .map-tooltip strong {
            color: var(--brand-primary);
        }

        .map-container:hover .map-tooltip {
            display: flex;
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateX(-50%) translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }
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
            border: 2px solid var(--brand-primary);
            border-radius: 15px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--brand-text);
            font-weight: 700;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .day-number:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.12);
        }

        .day-number-main {
            font-size: 1.4rem;
            line-height: 1;
            color: var(--brand-text);
        }

        .day-number-label {
            font-size: 0.7rem;
            color: var(--brand-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 2px;
        }

        /* Badge minimalista para duración */
        .duration-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--brand-primary);
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
            background: var(--brand-primary);
            box-shadow: 0 2px 8px color-mix(in srgb, var(--brand-primary) 18%, transparent);
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
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
        }

        .day-content:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
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
            color: var(--brand-text);
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
            border: 2px solid var(--brand-primary);
            border-radius: 12px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px color-mix(in srgb, var(--brand-primary) 18%, transparent);
        }

        .location-icon {
            width: 40px;
            height: 40px;
            background: var(--brand-primary);
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
            color: var(--brand-text);
            font-size: 16px;
            margin-bottom: 4px;
        }

        .location-subtitle {
            color: var(--brand-muted);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Ubicaciones Secundarias como Lista Organizada */
        .secondary-locations-new {
            margin-top: 15px;
            padding: 20px;
            background: var(--brand-surface-soft);
            border-radius: 12px;
            border: 1px solid var(--brand-border);
        }

        .secondary-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--brand-border);
        }

        .secondary-header h4 {
            margin: 0;
            color: var(--brand-primary);
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
            background: var(--brand-primary);
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
            color: var(--brand-text);
            font-size: 14px;
            line-height: 1.4;
            margin-bottom: 4px;
        }

        .location-coords {
            font-size: 11px;
            color: var(--brand-muted);
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* Duration indicator minimalista */
        .duration-indicator {
            display: inline-flex;
            align-items: center;
            background: #f8f9fa;
            color: var(--brand-muted);
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
            color: var(--brand-muted);
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 500;
            border: 1px solid #e9ecef;
            margin-left: 10px;
        }

        /* Aquí nuevos estilos de fechas exactas*/

        .day-meta-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            margin: 12px 0 18px;
        }

        .day-meta-pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 7px 13px;
            border-radius: 999px;
            background: var(--brand-surface-soft);
            color: var(--brand-text);
            border: 1px solid #e2e8f0;
            font-size: 0.86rem;
            font-weight: 600;
            line-height: 1;
        }

        .day-meta-pill-primary {
            background: var(--brand-primary);
            color: #ffffff;
            border-color: transparent;
            box-shadow: 0 6px 16px color-mix(in srgb, var(--brand-primary) 18%, transparent);
        }

        .day-meta-pill i {
            font-size: 0.85rem;
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
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
        }

        .day-image::before {
            content: '🔍 Ver imagen';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
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
            background: rgba(0, 0, 0, 0.9);
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
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
        }

        .simple-modal-close {
            position: absolute;
            top: -15px;
            right: -15px;
            background: var(--brand-primary);
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
            background: var(--brand-primary);
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
            border-left: 4px solid var(--brand-primary);
        }

        .day-description p {
            margin: 0;
            color: var(--brand-muted);
            line-height: 1.7;
        }

        .stay-info-box {
            margin-top: 15px;
            padding: 15px;
            background: #ffffff;
            border-radius: 8px;
            border: 1px solid #e9ecef;
            color: var(--brand-muted);
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
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            border-color: var(--brand-primary);
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
            border-left: 4px solid var(--brand-primary);
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
            background: var(--brand-primary);
        }

        .service-icon.transporte {
            background: var(--brand-primary);
        }

        .service-icon.alojamiento {
            background: var(--brand-primary);
        }

        .service-details {
            flex: 1;
            min-width: 0;
        }

        .service-details h4 {
            font-weight: 600;
            color: var(--brand-text);
            margin-bottom: 8px;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .service-details p {
            color: var(--brand-muted);
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
            color: var(--brand-muted);
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
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
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
            background: rgba(0, 0, 0, 0.7);
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
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
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
            color: var(--brand-muted);
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
            background: var(--brand-surface-soft);
            border-radius: 12px;
            border-left: 4px solid var(--brand-primary);
            border: 1px solid var(--brand-border);
        }

        .day-meals h4 {
            margin-bottom: 15px;
            color: var(--brand-primary);
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
            color: var(--brand-primary);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--brand-border);
            transition: all 0.3s ease;
        }

        .meal-item:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .meal-item i {
            margin-right: 6px;
            color: var(--brand-primary);
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
            color: var(--brand-muted);
            font-size: 0.9rem;
            border-top: 1px solid #e9ecef;
            transition: all 0.3s ease;
        }

        .alternatives-header:hover {
            background: #e9ecef;
            color: var(--brand-text);
        }

        .alternatives-header i {
            color: var(--brand-muted);
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
            border-left: 3px solid var(--brand-muted);
            position: relative;
        }

        .service-item.alternativa:last-child {
            border-bottom: none;
        }

        .service-item.alternativa .service-icon {
            background: linear-gradient(135deg, var(--brand-muted), var(--brand-text)) !important;
            width: 45px;
            height: 45px;
            font-size: 1rem;
        }

        .alternative-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            background: var(--brand-muted);
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
            border-left: 3px solid var(--brand-muted);
            border-radius: 4px;
            font-size: 0.85rem;
            color: var(--brand-text);
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
            color: var(--brand-text);
            margin-bottom: 15px;
        }

        .pricing-header p {
            font-size: 1.1rem;
            color: var(--brand-muted);
        }

        .price-main-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 40px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
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
            color: var(--brand-muted);
        }

        .price-value {
            font-size: 3.5rem;
            font-weight: 700;
            color: var(--brand-text);
        }

        .price-per {
            font-size: 1.2rem;
            color: var(--brand-muted);
        }

        .nights-included {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px 25px;
            background: var(--brand-primary);
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
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
        }

        .pricing-accordion:hover {
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
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
            background: var(--brand-surface-soft);
        }

        .accordion-title {
            display: flex;
            align-items: center;
            gap: 15px;
            font-weight: 600;
            color: var(--brand-text);
            font-size: 1.1rem;
        }

        .accordion-title i {
            font-size: 1.3rem;
        }

        .accordion-arrow {
            color: var(--brand-muted);
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
            color: var(--brand-primary);
            margin-top: 2px;
        }

        .pricing-list.excluded i {
            color: var(--brand-primary);
            margin-top: 2px;
        }

        .pricing-list span {
            color: var(--brand-text);
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
            color: var(--brand-muted);
            border-left: 4px solid var(--brand-primary);
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
            background: color-mix(in srgb, var(--brand-primary) 10%, #ffffff);
            color: var(--brand-primary);
        }

        .status-badge.partially-accessible {
            background: var(--brand-surface-soft);
            color: var(--brand-primary);
        }

        .status-badge.not-accessible {
            background: var(--brand-surface-soft);
            color: var(--brand-primary);
        }

        .accessibility-details {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            color: var(--brand-muted);
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
            background: var(--brand-text);
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
            background: var(--brand-primary);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px color-mix(in srgb, var(--brand-primary) 18%, transparent);
            color: white;
        }

        .btn-outline {
            background: transparent;
            color: white;
            border: 2px solid white;
        }

        .btn-outline:hover {
            background: white;
            color: var(--brand-text);
        }

        .footer-bottom {
            border-top: 1px solid var(--brand-text);
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

            0%,
            20%,
            50%,
            80%,
            100% {
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
            .hero-subtitle {
                font-size: 0.8rem;
                letter-spacing: 2px;
                margin-bottom: 15px;
            }

            .hero-title {
                font-size: 2.8rem;
                line-height: 1.2;
                margin-bottom: 20px;
            }

            .hero-description {
                font-size: 1.1rem;
                margin-bottom: 30px;
            }

            .hero-stats {
                gap: 15px;
            }

            .hero-stat {
                padding: 15px 20px;
                min-width: 100px;
            }
        }

        @media (max-width: 480px) {
            .hero-title {
                font-size: 2.2rem;
            }

            .hero-description {
                font-size: 1rem;
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
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
   UBICACIONES SECUNDARIAS - DISEÑO MEJORADO
   ======================================== */
        .main-location {
            font-weight: 600;
            color: var(--brand-text);
        }

        .secondary-locations-section {
            margin-top: 15px;
            padding: 15px;
            background: var(--brand-surface-soft);
            border-radius: 12px;
            border-left: 4px solid var(--brand-primary);
            border: 1px solid var(--brand-border);
        }

        .secondary-locations-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
            font-weight: 600;
            color: var(--brand-primary);
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
            border: 1px solid var(--brand-border);
            transition: all 0.3s ease;
        }

        .secondary-location-item:hover {
            transform: translateX(3px);
            box-shadow: 0 3px 10px rgba(39, 174, 96, 0.1);
            border-color: var(--brand-primary);
        }

        .location-marker {
            width: 24px;
            height: 24px;
            background: var(--brand-primary);
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
            color: var(--brand-text);
            font-size: 0.9rem;
            line-height: 1.3;
        }

        .location-coords {
            font-size: 0.75rem;
            color: var(--brand-muted);
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .location-coords i {
            font-size: 10px;
            color: var(--brand-muted);
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
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        /* Adultos - Azul coherente con el diseño */
        .price-category.adulto {
            border-color: var(--brand-primary);
            background: linear-gradient(135deg, var(--brand-surface-soft) 0%, #ffffff 100%);
        }

        .price-category.adulto .category-icon {
            background: linear-gradient(135deg, var(--brand-primary) 0%, var(--brand-primary) 100%);
            box-shadow: 0 4px 12px color-mix(in srgb, var(--brand-primary) 18%, transparent);
        }

        /* Niños - Verde coherente con ubicaciones secundarias */
        .price-category.nino {
            border-color: var(--brand-primary);
            background: linear-gradient(135deg, var(--brand-surface-soft) 0%, #ffffff 100%);
        }

        .price-category.nino .category-icon {
            background: linear-gradient(135deg, var(--brand-primary) 0%, var(--brand-primary) 100%);
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
            color: var(--brand-muted);
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
            color: var(--brand-text);
        }

        .category-price .price-value {
            font-size: 28px;
            font-weight: 800;
            color: var(--brand-text);
        }

        .category-price .price-per {
            font-size: 12px;
            color: var(--brand-muted);
        }

        .category-subtotal {
            font-size: 12px;
            color: var(--brand-muted);
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
            background: linear-gradient(135deg, var(--brand-text) 0%, var(--brand-text) 100%);
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
            background: linear-gradient(135deg, var(--brand-primary) 0%, var(--brand-primary) 100%);
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
            border-color: var(--brand-primary);
            box-shadow: 0 8px 20px color-mix(in srgb, var(--brand-primary) 18%, transparent);
        }

        .price-category.nino:hover {
            border-color: var(--brand-primary);
            box-shadow: 0 8px 20px rgba(39, 174, 96, 0.2);
        }

        .price-category:hover .category-icon {
            transform: scale(1.05);
        }

        .price-total:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(44, 62, 80, 0.4);
        }

        /* ========== HOTEL: IMAGEN A LA IZQUIERDA CON ZOOM ========== */
        .service-details {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .hotel-content {
            display: flex;
            gap: 20px;
            align-items: flex-start;
        }

        .hotel-thumbnail {
            position: relative;
            width: 200px;
            min-width: 200px;
            height: 150px;
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            flex-shrink: 0;
        }

        .hotel-thumbnail:hover {
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
        }

        .hotel-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .hotel-thumbnail:hover img {
            transform: scale(1.1);
        }

        .hotel-thumbnail .thumbnail-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .hotel-thumbnail:hover .thumbnail-overlay {
            opacity: 1;
        }

        .hotel-text {
            flex: 1;
        }

        /* ========== SITIO WEB DEL HOTEL - MINIMALISTA ========== */
        .service-website {
            margin-top: 12px;
        }

        .service-website a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: #f8f9fa;
            color: var(--brand-text);
            text-decoration: none;
            border-radius: 6px;
            border: 1px solid var(--brand-border);
            font-weight: 500;
            font-size: 13px;
            transition: all 0.2s ease;
        }

        .service-website a:hover {
            background: #e9ecef;
            border-color: var(--brand-muted);
            color: var(--brand-text);
        }

        .service-website a i {
            font-size: 12px;
        }

        /* ========== ACTIVIDAD: GALERÍA COMPLETA CON ALTURA VISIBLE ========== */
        .activity-gallery {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 15px;
            position: relative;
        }

        /* Si solo hay 1 imagen, ocupa todo el ancho */
        .activity-gallery.single-image {
            grid-template-columns: 1fr;
        }

        /* Si hay 2 imágenes, ocupan cada una 50% */
        .activity-gallery.two-images {
            grid-template-columns: repeat(2, 1fr);
        }

        .gallery-item {
            position: relative;
            height: 200px;
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .gallery-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        }

        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .gallery-item .gallery-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 28px;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .gallery-item:hover .gallery-overlay {
            opacity: 1;
        }

        .gallery-count {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            backdrop-filter: blur(4px);
        }

        /* ========== MODAL PARA AMPLIAR IMÁGENES - MINIMALISTA ========== */
        .image-modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.98);
            animation: fadeIn 0.3s;
        }

        .image-modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }

        .modal-image-container {
            position: relative;
            max-width: 90%;
            max-height: 80%;
        }

        .modal-image-container img {
            max-width: 100%;
            max-height: 80vh;
            object-fit: contain;
            animation: zoomIn 0.3s;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }

        .modal-title {
            color: var(--brand-text);
            margin-bottom: 30px;
            font-size: 24px;
            font-weight: 600;
            text-align: center;
        }

        .modal-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.05);
            color: var(--brand-text);
            border: 1px solid rgba(0, 0, 0, 0.1);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 20px;
            transition: all 0.3s;
            backdrop-filter: blur(10px);
        }

        .modal-nav:hover {
            background: rgba(0, 0, 0, 0.1);
            transform: translateY(-50%) scale(1.1);
        }

        .modal-nav.prev {
            left: -70px;
        }

        .modal-nav.next {
            right: -70px;
        }

        .modal-close {
            position: absolute;
            top: 30px;
            right: 40px;
            color: var(--brand-text);
            font-size: 36px;
            font-weight: 300;
            cursor: pointer;
            background: rgba(0, 0, 0, 0.05);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            border: 1px solid rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }

        .modal-close:hover {
            background: rgba(0, 0, 0, 0.1);
            transform: rotate(90deg);
        }

        .modal-counter {
            color: var(--brand-muted);
            margin-top: 20px;
            font-size: 15px;
            font-weight: 500;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes zoomIn {
            from {
                transform: scale(0.9);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hotel-content {
                flex-direction: column;
            }

            .hotel-thumbnail {
                width: 100%;
                max-width: 300px;
            }

            .activity-gallery {
                grid-template-columns: 1fr;
            }

            .activity-gallery.two-images {
                grid-template-columns: 1fr;
            }

            .gallery-item {
                height: 180px;
            }

            .modal-nav {
                width: 40px;
                height: 40px;
                font-size: 16px;
            }

            .modal-nav.prev {
                left: 10px;
            }

            .modal-nav.next {
                right: 10px;
            }

            .modal-close {
                top: 20px;
                right: 20px;
                width: 44px;
                height: 44px;
            }
        }


        /* ============================================================
   ESTILOS MEJORADOS PARA PORTADA DEL PDF
   ============================================================
   Reemplazar la sección @media print del archivo itinerary.php
   específicamente la parte de .hero-section
   ============================================================ */

        @media print {

            /* ========== CONFIGURACIÓN GLOBAL ========== */
            * {
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            @page {
                size: A4 portrait;
                margin: 0;
                /* Sin márgenes para la portada */
            }

            html,
            body {
                width: 100%;
                height: auto;
                margin: 0;
                padding: 0;
                background: white;
            }

            body {
                font-size: 10pt;
                line-height: 1.4;
                color: #000;
            }

            .day-images {
                display: grid !important;
                grid-template-columns: repeat(3, 1fr) !important;
                gap: 12px !important;
                margin: 20px 0 !important;
                page-break-inside: avoid !important;
                height: auto !important;
                overflow: visible !important;
            }

            /* Si solo hay 1 imagen, ocupar todo el ancho */
            .day-images:has(.day-image:only-child) {
                grid-template-columns: 1fr !important;
            }

            /* Si solo hay 2 imágenes, 2 columnas */
            .day-images:has(.day-image:nth-child(2):last-child) {
                grid-template-columns: repeat(2, 1fr) !important;
            }

            .day-image {
                display: block !important;
                width: 100% !important;
                height: 220px !important;
                background-size: cover !important;
                background-repeat: no-repeat !important;
                background-position: center !important;
                border-radius: 8px !important;
                border: 2px solid #e9ecef !important;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1) !important;
                page-break-inside: avoid !important;
                position: relative !important;
            }

            /* La primera imagen NO ocupa 2 filas en print */
            .day-image:first-child {
                grid-row: unset !important;
                height: 220px !important;
            }

            /* Quitar efectos hover */
            .day-image::before,
            .day-image:hover::before {
                display: none !important;
                content: none !important;
            }

            .day-image:hover {
                transform: none !important;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1) !important;
            }

            /* ========== OCULTAR ELEMENTOS INNECESARIOS ========== */
            .navbar,
            .scroll-indicator,
            .pricing-actions,
            .footer-actions,
            .translate-container,
            #google_translate_element,
            .image-modal,
            .simple-image-modal,
            .modal-close,
            .modal-nav,
            button,
            .accordion-arrow,
            .alternatives-toggle,
            .map-container,
            #map,
            section:has(#map),
            .thumbnail-overlay,
            .gallery-overlay {
                display: none !important;
                visibility: hidden !important;
            }

            /* ========== ✨ PORTADA MEJORADA - PÁGINA COMPLETA ========== */
            .hero-section {
                /* Ocupar toda la primera página */
                height: 100vh !important;
                min-height: 297mm !important;
                /* A4 height */
                max-height: 297mm !important;
                page-break-after: always !important;

                /* Imagen de fondo con mejor calidad */
                background-size: cover !important;
                background-position: center center !important;
                background-repeat: no-repeat !important;

                /* Asegurar que el fondo se imprima */
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;

                /* Gradiente oscuro sobre la imagen para mejorar legibilidad */
                position: relative;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
            }

            /* Overlay oscuro para mejorar contraste del texto */
            .hero-section::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: linear-gradient(to bottom,
                        rgba(0, 0, 0, 0.3) 0%,
                        rgba(0, 0, 0, 0.5) 50%,
                        rgba(0, 0, 0, 0.7) 100%) !important;
                z-index: 1;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            /* Contenido de la portada */
            .hero-content {
                position: relative;
                z-index: 2;
                text-align: center !important;
                padding: 60px 40px !important;
                max-width: 800px !important;
                color: #ffffff !important;
            }

            /* Subtítulo de la portada */
            .hero-subtitle {
                font-size: 14pt !important;
                font-weight: 400 !important;
                letter-spacing: 3px !important;
                text-transform: uppercase !important;
                margin-bottom: 20px !important;
                color: rgba(255, 255, 255, 0.95) !important;
                text-shadow: 2px 2px 6px rgba(0, 0, 0, 0.8) !important;
            }

            /* Título principal - MÁS GRANDE Y ELEGANTE */
            .hero-title {
                font-family: 'Playfair Display', serif !important;
                font-size: 48pt !important;
                /* Aumentado de 28pt */
                font-weight: 700 !important;
                line-height: 1.2 !important;
                margin-bottom: 25px !important;
                color: #ffffff !important;
                text-shadow: 3px 3px 10px rgba(0, 0, 0, 0.9) !important;
                letter-spacing: -1px !important;
            }

            /* Descripción del viajero */
            .hero-description {
                font-size: 16pt !important;
                font-weight: 300 !important;
                margin-bottom: 50px !important;
                color: rgba(255, 255, 255, 0.95) !important;
                text-shadow: 2px 2px 6px rgba(0, 0, 0, 0.8) !important;
                line-height: 1.5 !important;
            }

            .hero-description strong {
                font-weight: 600 !important;
                color: #ffffff !important;
            }

            /* Stats del viaje - REDISEÑADOS Y MÁS VISIBLES */
            .hero-stats {
                display: flex !important;
                justify-content: center !important;
                gap: 40px !important;
                flex-wrap: wrap !important;
                margin-top: 50px !important;
                padding-top: 40px !important;
                border-top: 2px solid rgba(255, 255, 255, 0.3) !important;
            }

            .hero-stat {
                text-align: center !important;
                padding: 20px 30px !important;
                background: rgba(255, 255, 255, 0.15) !important;
                backdrop-filter: blur(10px) !important;
                border-radius: 15px !important;
                border: 1px solid rgba(255, 255, 255, 0.3) !important;
                min-width: 150px !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .hero-stat-title {
                display: block !important;
                font-size: 10pt !important;
                font-weight: 500 !important;
                text-transform: uppercase !important;
                letter-spacing: 2px !important;
                margin-bottom: 8px !important;
                color: rgba(255, 255, 255, 0.9) !important;
                text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.7) !important;
            }

            .hero-stat-number {
                display: block !important;
                font-size: 32pt !important;
                /* Aumentado */
                font-weight: 700 !important;
                color: #ffffff !important;
                margin-bottom: 5px !important;
                text-shadow: 2px 2px 6px rgba(0, 0, 0, 0.8) !important;
            }

            .hero-stat-label {
                display: block !important;
                font-size: 11pt !important;
                font-weight: 400 !important;
                color: rgba(255, 255, 255, 0.95) !important;
                text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.7) !important;
            }

            /* Logo de la agencia en la portada (opcional) */
            .hero-section::after {
                content: '<?= addslashes($company_name) ?>';
                /* Mostrar nombre de agencia */
                position: absolute;
                bottom: 30px;
                right: 40px;
                font-size: 12pt;
                font-weight: 600;
                color: rgba(255, 255, 255, 0.8);
                text-shadow: 2px 2px 6px rgba(0, 0, 0, 0.8);
                z-index: 3;
                letter-spacing: 2px;
                text-transform: uppercase;
            }

            /* ========== SEGUNDA PÁGINA EN ADELANTE ========== */
            /* Agregar márgenes normales después de la portada */
            .main-content {
                margin-top: 0 !important;
                padding-top: 20mm !important;
            }

            @page: first {
                margin: 0;
                /* Sin márgenes en la primera página (portada) */
            }

            @page {
                margin: 15mm 12mm;
                /* Márgenes normales en el resto de páginas */
            }

            /* ========== RESTO DE ESTILOS PARA EL CONTENIDO ========== */
            /* ... mantener todos los estilos existentes para overview, itinerario, etc. ... */

            .overview-section {
                page-break-inside: avoid;
                margin-bottom: 15px;
            }

            .section-title {
                font-size: 18pt !important;
                margin-bottom: 12px !important;
                page-break-after: avoid;
            }

            .section-subtitle {
                font-size: 10pt !important;
                margin-bottom: 15px !important;
            }

            /* Day cards */
            .day-card {
                page-break-inside: avoid;
                margin-bottom: 15px !important;
                border: 1px solid var(--brand-border) !important;
                padding: 12px !important;
            }

            .day-header {
                font-size: 14pt !important;
                margin-bottom: 10px !important;
            }

            .service-item {
                page-break-inside: avoid;
                margin-bottom: 10px !important;
                padding: 10px !important;
            }

            /* Footer compacto */
            .footer {
                background: var(--brand-text) !important;
                color: #fff !important;
                padding: 15px !important;
                text-align: center !important;
                page-break-inside: avoid !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .footer h3 {
                font-size: 14pt !important;
                margin-bottom: 8px !important;
            }

            .footer p {
                font-size: 9pt !important;
                margin: 4px 0 !important;
            }

            /* Optimizaciones generales */
            h1,
            h2,
            h3,
            h4,
            h5,
            h6 {
                page-break-after: avoid !important;
                page-break-inside: avoid !important;
            }

            p,
            ul,
            ol {
                orphans: 3 !important;
                widows: 3 !important;
            }

            img {
                max-width: 100% !important;
                page-break-inside: avoid !important;
            }
        }

        /* ========== CLASE PARA MODO IMPRESIÓN ========== */
        .print-mode .accordion-content,
        .print-mode .alternatives-list {
            max-height: none !important;
            display: block !important;
            overflow: visible !important;
        }


        /*Clases de acomodaciones */
        .accommodation-detail {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            margin: 6px 0 8px 0;
            padding: 6px 11px;
            border-radius: 999px;
            background: color-mix(in srgb, var(--brand-primary) 10%, #ffffff);
            color: var(--brand-primary);
            font-size: 13px;
            font-weight: 600;
        }

        .accommodation-detail i {
            font-size: 12px;
        }

        .accommodation-detail.muted {
            background: rgba(107, 114, 128, 0.10);
            color: var(--brand-muted);
        }

        .day-flights-section {
            margin: 24px 0;
            padding: 18px;
            border: 1px solid var(--brand-border);
            border-radius: 16px;
            background: var(--brand-surface-soft);
        }

        .day-flights-title {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 14px;
            font-weight: 800;
            color: var(--brand-text);
            font-size: 16px;
        }

        .day-flights-title i {
            color: var(--primary-color, var(--brand-primary));
        }

        .day-flights-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .day-flight-card {
            background: #ffffff;
            border: 1px solid var(--brand-border);
            border-left: 4px solid var(--primary-color, var(--brand-primary));
            border-radius: 12px;
            padding: 14px 16px;
        }

        .day-flight-header {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .day-flight-header strong {
            font-size: 15px;
            color: var(--brand-text);
        }

        .day-flight-header span {
            margin-left: 6px;
            color: var(--brand-muted);
            font-size: 14px;
        }

        .day-flight-header small {
            color: var(--primary-color, var(--brand-primary));
            font-weight: 700;
            white-space: nowrap;
        }

        .day-flight-route {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 10px;
        }

        .day-flight-route div {
            flex: 1;
        }

        .day-flight-route div:last-child {
            text-align: right;
        }

        .day-flight-route strong {
            display: block;
            font-size: 18px;
            color: var(--brand-text);
        }

        .day-flight-route span {
            color: var(--brand-muted);
            font-size: 13px;
        }

        .day-flight-route i {
            color: var(--primary-color, var(--brand-primary));
        }

        .day-flight-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            color: var(--brand-muted);
            font-size: 13px;
        }

        .day-flight-meta i {
            color: var(--primary-color, var(--brand-primary));
            margin-right: 4px;
        }

        @media print {
            .day-flights-section {
                break-inside: avoid;
                background: #fff;
            }

            .day-flight-card {
                break-inside: avoid;
            }
        }

        @media (max-width: 768px) {
            .day-flight-route {
                align-items: flex-start;
            }

            .day-flight-route strong {
                font-size: 16px;
            }
        }

        .clean-flight-card {
            background: #ffffff;
            border: 1px solid var(--brand-border);
            border-radius: 18px;
            padding: 18px;
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.08);
        }

        .clean-flight-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 18px;
        }

        .clean-flight-code {
            font-size: 18px;
            font-weight: 800;
            color: var(--brand-text);
        }

        .clean-flight-airline {
            margin-top: 2px;
            font-size: 13px;
            color: var(--brand-muted);
        }

        .clean-flight-order {
            background: var(--primary-color, var(--brand-primary));
            color: white;
            border-radius: 999px;
            padding: 6px 10px;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .clean-flight-route {
            display: grid;
            grid-template-columns: 1fr 1.2fr 1fr;
            align-items: center;
            gap: 18px;
            margin-bottom: 18px;
        }

        .clean-airport-code {
            font-size: 34px;
            line-height: 1;
            font-weight: 800;
            color: var(--brand-text);
        }

        .clean-airport-city {
            margin-top: 6px;
            color: var(--brand-muted);
            font-size: 13px;
        }

        .clean-flight-time {
            margin-top: 10px;
            color: var(--primary-color, var(--brand-primary));
            font-size: 22px;
            font-weight: 800;
        }

        .clean-airport-right {
            text-align: right;
        }

        .clean-flight-line {
            text-align: center;
        }

        .clean-duration {
            font-size: 12px;
            color: var(--brand-muted);
            margin-bottom: 8px;
        }

        .clean-line {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--primary-color, var(--brand-primary));
        }

        .clean-line span {
            height: 1px;
            background: var(--brand-border);
            flex: 1;
        }

        .clean-line i {
            font-size: 15px;
        }

        .clean-flight-details {
            display: grid;
            grid-template-columns: 1fr 1fr .45fr;
            gap: 12px;
            border-top: 1px solid var(--brand-border);
            padding-top: 14px;
        }

        .clean-flight-details div {
            background: var(--brand-surface-soft);
            border-radius: 12px;
            padding: 10px 12px;
        }

        .clean-flight-details strong {
            display: block;
            color: var(--brand-text);
            font-size: 11px;
            margin-bottom: 4px;
        }

        .clean-flight-details span {
            color: var(--brand-muted);
            font-size: 12px;
        }

        @media print {
            .clean-flight-card {
                box-shadow: none;
                break-inside: avoid;
            }
        }

        @media (max-width: 768px) {
            .clean-flight-route {
                grid-template-columns: 1fr;
            }

            .clean-airport-right {
                text-align: left;
            }

            .clean-flight-details {
                grid-template-columns: 1fr;
            }
        }



        /* =====================================================
   TRAVEL SOFT - REDISEÑO LIMPIO TIPO "MI VIAJE"
   Mantiene la estructura PHP, funciones, modales, mapa, precios y botones.
   Solo redefine presentación visual para una lectura más clara.
   ===================================================== */
        :root {
            /*
      Variables visuales del rediseño.
      El color principal sale de la paleta configurada por el usuario.
      Si el sistema ya define --primary-color / --secondary-color, este diseño las hereda.
    */
            --primary-color:
                <?= htmlspecialchars($brand_primary) ?>
            ;
            --secondary-color:
                <?= htmlspecialchars($brand_secondary) ?>
            ;
            --ts-brand:
                <?= htmlspecialchars($brand_primary) ?>
            ;
            --ts-brand-dark:
                <?= htmlspecialchars($brand_secondary) ?>
            ;
            --ts-bg: color-mix(in srgb, var(--ts-brand) 7%, #ffffff);
            --ts-surface: #ffffff;
            --ts-surface-soft: color-mix(in srgb, var(--ts-brand) 4%, #ffffff);
            --ts-text: #20292d;
            --ts-muted: #6b7478;
            --ts-line: color-mix(in srgb, var(--ts-brand) 14%, #e9e9e9);
            --ts-brand-soft: color-mix(in srgb, var(--ts-brand) 12%, #ffffff);
            --ts-brand-faint: color-mix(in srgb, var(--ts-brand) 6%, #ffffff);
            --ts-radius-lg: 28px;
            --ts-radius-md: 18px;
            --ts-shadow-soft: 0 18px 50px rgba(32, 41, 45, .10);
            --ts-shadow-card: 0 10px 30px rgba(32, 41, 45, .07);
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            background: var(--ts-bg) !important;
            color: var(--ts-text) !important;
            font-family: 'Inter', sans-serif !important;
        }

        .navbar {
            background: rgba(255, 255, 255, .88) !important;
            border-bottom: 1px solid var(--ts-line) !important;
            box-shadow: 0 8px 30px rgba(32, 41, 45, .06) !important;
        }

        .navbar-content {
            max-width: 1180px !important;
            padding: 0 24px !important;
        }

        .navbar-brand {
            font-family: 'Inter', sans-serif !important;
            font-weight: 800 !important;
            color: var(--ts-brand) !important;
        }

        .navbar-nav a {
            color: var(--ts-text) !important;
            font-weight: 700 !important;
            font-size: .92rem !important;
        }

        .navbar-nav a:hover {
            color: var(--ts-brand) !important;
        }

        .hero-section {
            height: 78vh !important;
            min-height: 560px !important;
            align-items: flex-end !important;
            justify-content: flex-start !important;
            text-align: left !important;
            padding: 0 24px 72px !important;
            background-image: linear-gradient(90deg, rgba(0, 0, 0, .64) 0%, rgba(0, 0, 0, .36) 48%, rgba(0, 0, 0, .10) 100%), url('<?= addslashes($imagen_portada) ?>') !important;
        }

        .hero-content {
            max-width: 1120px !important;
            width: 100% !important;
            margin: 0 auto !important;
            padding: 0 !important;
        }

        .hero-subtitle {
            width: fit-content !important;
            background: rgba(255, 255, 255, .16) !important;
            border: 1px solid rgba(255, 255, 255, .28) !important;
            border-radius: 999px !important;
            padding: 9px 15px !important;
            letter-spacing: .08em !important;
            font-size: .78rem !important;
            margin-bottom: 18px !important;
        }

        .hero-title {
            max-width: 820px !important;
            font-family: 'Playfair Display', serif !important;
            font-size: clamp(2.7rem, 6vw, 5.4rem) !important;
            line-height: .96 !important;
            letter-spacing: -.04em !important;
            text-shadow: 0 12px 35px rgba(0, 0, 0, .35) !important;
        }

        .hero-description {
            max-width: 620px !important;
            margin: 18px 0 28px !important;
            font-size: 1.05rem !important;
            color: rgba(255, 255, 255, .9) !important;
        }

        .hero-stats {
            justify-content: flex-start !important;
            gap: 12px !important;
            margin: 0 !important;
        }

        .hero-stat {
            min-width: 120px !important;
            padding: 14px 18px !important;
            border-radius: 18px !important;
            background: rgba(255, 255, 255, .92) !important;
            color: var(--ts-text) !important;
            border: 1px solid rgba(255, 255, 255, .5) !important;
            box-shadow: var(--ts-shadow-card) !important;
            backdrop-filter: blur(18px) !important;
        }

        .hero-stat-number {
            font-size: 1.8rem !important;
            color: var(--ts-brand) !important;
        }

        .hero-stat-label,
        .hero-stat-title {
            color: var(--ts-muted) !important;
            font-size: .72rem !important;
        }

        .scroll-indicator {
            display: none !important;
        }

        .main-content {
            max-width: 1180px !important;
            padding: 38px 24px 80px !important;
            background: transparent !important;
        }

        .section {
            margin-bottom: 54px !important;
        }

        .section-header {
            text-align: left !important;
            margin-bottom: 22px !important;
        }

        .section-title {
            font-family: 'Inter', sans-serif !important;
            font-size: clamp(1.65rem, 3vw, 2.35rem) !important;
            letter-spacing: -.035em !important;
            color: var(--ts-text) !important;
            margin-bottom: 6px !important;
        }

        .section-subtitle {
            margin: 0 !important;
            color: var(--ts-muted) !important;
            font-size: .98rem !important;
        }

        .overview-grid {
            display: grid !important;
            grid-template-columns: minmax(0, 1.45fr) minmax(320px, .9fr) !important;
            gap: 18px !important;
            margin-bottom: 0 !important;
        }

        .overview-content,
        .price-main-card,
        .pricing-accordion,
        .day-content,
        .map-container {
            background: var(--ts-surface) !important;
            border: 1px solid var(--ts-line) !important;
            border-radius: var(--ts-radius-lg) !important;
            box-shadow: var(--ts-shadow-card) !important;
        }

        .overview-content {
            padding: 26px !important;
        }

        .overview-details {
            gap: 12px !important;
            margin-bottom: 16px !important;
        }

        .detail-item {
            background: var(--ts-surface-soft) !important;
            border: 1px solid var(--ts-line) !important;
            border-radius: 18px !important;
            padding: 16px !important;
            align-items: flex-start !important;
        }

        .detail-icon {
            width: 42px !important;
            height: 42px !important;
            border-radius: 14px !important;
            background: var(--ts-brand-soft) !important;
            color: var(--ts-brand) !important;
            font-size: 1rem !important;
        }

        .detail-info h4 {
            color: var(--ts-text) !important;
            font-size: .86rem !important;
            margin-bottom: 3px !important;
        }

        .detail-info p {
            color: var(--ts-muted) !important;
            font-size: .92rem !important;
        }

        .overview-summary {
            background: var(--ts-brand-faint) !important;
            border: 1px solid var(--ts-line) !important;
            border-left: 0 !important;
            padding: 20px !important;
            border-radius: 20px !important;
        }

        .overview-summary h3 {
            color: var(--ts-brand-dark) !important;
        }

        #map.section {
            margin-bottom: 54px !important;
        }

        .map-container {
            height: 470px !important;
            overflow: hidden !important;
            position: relative !important;
        }

        #map {
            height: 470px !important;
        }

        .leaflet-popup-content-wrapper {
            border-radius: 18px !important;
            box-shadow: var(--ts-shadow-soft) !important;
        }

        .map-tooltip {
            border-radius: 999px !important;
            border: 1px solid var(--ts-line) !important;
            box-shadow: var(--ts-shadow-card) !important;
        }

        .itinerary-timeline::before {
            left: 20px !important;
            top: 20px !important;
            bottom: 20px !important;
            width: 2px !important;
            background: repeating-linear-gradient(to bottom, var(--ts-line), var(--ts-line) 8px, transparent 8px, transparent 14px) !important;
        }

        .day-card {
            padding-left: 58px !important;
            margin-bottom: 28px !important;
        }

        .day-number {
            left: 0 !important;
            top: 22px !important;
            width: 42px !important;
            height: 42px !important;
            border-radius: 999px !important;
            border: 2px solid var(--ts-line) !important;
            background: var(--ts-surface) !important;
            box-shadow: 0 0 0 7px var(--ts-bg) !important;
            z-index: 2 !important;
        }

        .day-number-main {
            font-size: .92rem !important;
            color: var(--ts-brand) !important;
            font-weight: 900 !important;
        }

        .day-number-label {
            display: none !important;
        }

        .duration-badge {
            background: var(--ts-brand) !important;
        }

        .day-content {
            overflow: hidden !important;
            transition: transform .25s ease, box-shadow .25s ease !important;
        }

        .day-content:hover {
            transform: translateY(-2px) !important;
            box-shadow: var(--ts-shadow-soft) !important;
        }

        .day-header {
            padding: 24px 26px !important;
            background: var(--ts-surface) !important;
            border-bottom: 1px solid var(--ts-line) !important;
        }

        .day-title {
            font-family: 'Inter', sans-serif !important;
            font-size: clamp(1.25rem, 2vw, 1.7rem) !important;
            line-height: 1.2 !important;
            letter-spacing: -.025em !important;
            margin-bottom: 12px !important;
            color: var(--ts-text) !important;
        }

        .day-meta-row {
            display: flex !important;
            gap: 8px !important;
            flex-wrap: wrap !important;
            margin: 0 0 12px !important;
        }

        .day-meta-pill {
            display: inline-flex !important;
            align-items: center !important;
            gap: 7px !important;
            border-radius: 999px !important;
            padding: 7px 11px !important;
            background: var(--ts-surface-soft) !important;
            border: 1px solid var(--ts-line) !important;
            color: var(--ts-muted) !important;
            font-size: .82rem !important;
            font-weight: 700 !important;
        }

        .day-meta-pill-primary {
            background: var(--ts-brand-soft) !important;
            color: var(--ts-brand-dark) !important;
            border-color: var(--ts-line) !important;
        }

        .secondary-locations-new {
            background: transparent !important;
            padding: 0 !important;
            border: 0 !important;
            margin-top: 10px !important;
        }

        .secondary-header {
            display: none !important;
        }

        .locations-list {
            display: flex !important;
            flex-wrap: wrap !important;
            gap: 8px !important;
            list-style: none !important;
        }

        .location-item {
            display: inline-flex !important;
            align-items: center !important;
            width: auto !important;
            gap: 8px !important;
            background: var(--ts-surface-soft) !important;
            border: 1px solid var(--ts-line) !important;
            border-radius: 999px !important;
            padding: 7px 11px !important;
        }

        .location-marker {
            width: 22px !important;
            height: 22px !important;
            min-width: 22px !important;
            border-radius: 999px !important;
            background: var(--ts-brand) !important;
            color: #fff !important;
            font-size: .72rem !important;
        }

        .location-name {
            font-size: .86rem !important;
            color: var(--ts-text) !important;
            font-weight: 800 !important;
        }

        .location-coords {
            display: none !important;
        }

        .day-images {
            display: grid !important;
            grid-template-columns: 1.4fr .8fr .8fr !important;
            gap: 8px !important;
            padding: 10px !important;
            background: var(--ts-surface) !important;
            height: 330px !important;
            margin: 0 !important;
        }

        .day-image {
            height: 100% !important;
            min-height: 0 !important;
            border-radius: 22px !important;
            box-shadow: none !important;
            border: 0 !important;
        }

        .day-image:first-child {
            grid-row: span 2 !important;
        }

        .day-images:has(.day-image:only-child) {
            grid-template-columns: 1fr !important;
        }

        .day-images:has(.day-image:only-child) .day-image {
            grid-row: auto !important;
        }

        .day-images:has(.day-image:nth-child(2):last-child) {
            grid-template-columns: 1fr 1fr !important;
        }

        .day-services {
            padding: 0 !important;
            background: var(--ts-surface) !important;
        }

        .day-description {
            margin: 0 !important;
            padding: 24px 26px !important;
            background: var(--ts-surface) !important;
            border: 0 !important;
            color: var(--ts-text) !important;
        }

        .day-description p {
            color: var(--ts-muted) !important;
            font-size: 1rem !important;
            line-height: 1.75 !important;
        }

        .stay-info-box {
            background: var(--ts-brand-soft) !important;
            border: 1px solid #d7ebe8 !important;
            color: var(--ts-brand-dark) !important;
            border-radius: 18px !important;
            padding: 16px !important;
        }

        .day-meals {
            margin: 0 26px 22px !important;
            background: var(--ts-brand-faint) !important;
            border: 1px solid var(--ts-line) !important;
            border-radius: 18px !important;
            border-left: 0 !important;
        }

        .meal-item,
        .HEYhtiHcWlyddgJIa1VU span,
        .IVSjZUG0WMuWij1KUjRW {
            border-radius: 999px !important;
            background: var(--ts-surface) !important;
            border: 1px solid var(--ts-line) !important;
            color: #9a5c1c !important;
        }

        .service-group {
            margin: 0 26px 16px !important;
            border-radius: 22px !important;
            border: 1px solid var(--ts-line) !important;
            overflow: hidden !important;
            box-shadow: none !important;
        }

        .service-item {
            padding: 18px !important;
            gap: 14px !important;
            background: var(--ts-surface-soft) !important;
        }

        .service-item.principal {
            border-left: 0 !important;
        }

        .service-icon {
            width: 42px !important;
            height: 42px !important;
            border-radius: 14px !important;
            background: var(--ts-brand-soft) !important;
            color: var(--ts-brand) !important;
        }

        .service-icon.actividad,
        .service-icon.transporte,
        .service-icon.alojamiento {
            background: var(--ts-brand-soft) !important;
            color: var(--ts-brand) !important;
        }

        .service-details h4 {
            color: var(--ts-text) !important;
            font-size: 1rem !important;
            line-height: 1.25 !important;
        }

        .service-details p {
            color: var(--ts-muted) !important;
        }

        .service-meta span {
            background: var(--ts-surface) !important;
            border: 1px solid var(--ts-line) !important;
            border-radius: 999px !important;
            padding: 6px 9px !important;
            color: var(--ts-muted) !important;
        }

        .service-image {
            width: 116px !important;
            height: 92px !important;
            border-radius: 16px !important;
            box-shadow: none !important;
        }

        .alternatives-header {
            background: var(--ts-surface) !important;
            color: var(--ts-brand) !important;
            font-weight: 800 !important;
        }

        .alternatives-list {
            background: var(--ts-surface) !important;
        }

        .alternative-item {
            background: var(--ts-surface) !important;
            border-top: 1px solid var(--ts-line) !important;
        }

        .pricing-section {
            border-radius: var(--ts-radius-lg) !important;
            background: var(--ts-brand-dark) !important;
            padding: 34px !important;
            box-shadow: var(--ts-shadow-soft) !important;
        }

        .pricing-header h2 {
            font-family: 'Inter', sans-serif !important;
            letter-spacing: -.03em !important;
        }

        .price-main-card {
            color: var(--ts-text) !important;
        }

        .price-category {
            border-radius: 20px !important;
            border: 1px solid var(--ts-line) !important;
            box-shadow: none !important;
        }

        .price-total-section {
            border-radius: 20px !important;
        }

        .pricing-accordion {
            overflow: hidden !important;
        }

        .accordion-header {
            padding: 18px 22px !important;
        }

        .footer {
            background: var(--brand-surface-soft) !important;
            border-top-left-radius: 34px !important;
            border-top-right-radius: 34px !important;
        }

        .btn {
            border-radius: 999px !important;
            font-weight: 800 !important;
            box-shadow: none !important;
        }

        .btn-outline {
            border-color: rgba(255, 255, 255, .45) !important;
        }



        /* Limpieza de color: todo lo decorativo hereda la paleta del usuario */
        .overview-content>h3,
        .pricing-header h2,
        .pricing-header p {
            color: inherit !important;
        }

        .overview-content>h3 {
            color: var(--ts-text) !important;
        }

        .detail-icon[style],
        .service-icon[style],
        .day-icon[style] {
            background: var(--ts-brand-soft) !important;
            color: var(--ts-brand) !important;
        }

        .btn-primary,
        .btn:not(.btn-outline),
        .select-accommodation-button,
        .accommodation-select-btn,
        .price-action-btn {
            background: var(--ts-brand) !important;
            border-color: var(--ts-brand) !important;
            color: #fff !important;
        }

        .btn-primary:hover,
        .btn:not(.btn-outline):hover {
            background: var(--ts-brand-dark) !important;
            border-color: var(--ts-brand-dark) !important;
        }

        .btn-outline {
            background: transparent !important;
            color: var(--ts-brand) !important;
            border-color: var(--ts-line) !important;
        }

        .btn-outline:hover {
            background: var(--ts-brand-soft) !important;
            color: var(--ts-brand-dark) !important;
        }

        .day-meals,
        .meal-item,
        .HEYhtiHcWlyddgJIa1VU span,
        .IVSjZUG0WMuWij1KUjRW {
            background: var(--ts-brand-faint) !important;
            border-color: var(--ts-line) !important;
            color: var(--ts-brand-dark) !important;
        }

        @media (max-width: 900px) {
            .hero-section {
                height: auto !important;
                min-height: 620px !important;
                padding-bottom: 44px !important;
            }

            .overview-grid {
                grid-template-columns: 1fr !important;
            }

            .overview-details {
                grid-template-columns: 1fr !important;
            }

            .navbar-content {
                padding: 0 14px !important;
            }

            .navbar-nav {
                gap: 12px !important;
                overflow-x: auto !important;
            }

            .day-card {
                padding-left: 0 !important;
            }

            .itinerary-timeline::before,
            .day-number {
                display: none !important;
            }

            .day-images {
                height: auto !important;
                grid-template-columns: 1fr !important;
            }

            .day-image,
            .day-image:first-child {
                height: 220px !important;
                grid-row: auto !important;
            }

            .service-item {
                flex-direction: column !important;
            }

            .service-image {
                width: 100% !important;
                height: 180px !important;
            }

            .pricing-section {
                padding: 24px 16px !important;
            }
        }

        @media print {
            body {
                background: var(--ts-surface) !important;
            }

            .hero-section {
                height: auto !important;
                min-height: 360px !important;
            }

            .day-card {
                padding-left: 0 !important;
                break-inside: avoid !important;
            }

            .day-number,
            .itinerary-timeline::before,
            .navbar {
                display: none !important;
            }

            .day-content,
            .overview-content,
            .price-main-card,
            .pricing-accordion {
                box-shadow: none !important;
            }
        }



        /* =====================================================
   AJUSTE FINAL: PALETA ÚNICA + FECHAS TIPO EVANEOS
   ===================================================== */
        :root {
            --ts-brand:
                <?= htmlspecialchars($brand_primary) ?>
                !important;
            --ts-brand-dark:
                <?= htmlspecialchars($brand_secondary) ?>
                !important;
            --ts-bg: color-mix(in srgb, var(--ts-brand) 5%, #fff) !important;
            --ts-surface: #fff !important;
            --ts-surface-soft: color-mix(in srgb, var(--ts-brand) 4%, #fff) !important;
            --ts-brand-soft: color-mix(in srgb, var(--ts-brand) 10%, #fff) !important;
            --ts-brand-faint: color-mix(in srgb, var(--ts-brand) 6%, #fff) !important;
            --ts-line: color-mix(in srgb, var(--ts-brand) 15%, #e9e9e9) !important;
            --ts-text: #20292d !important;
            --ts-muted: #687276 !important;
        }

        body,
        .main-content,
        .section,
        .overview-content,
        .day-content,
        .service-card,
        .accommodation-card,
        .flight-card,
        .clean-flight-card,
        .pricing-accordion,
        .price-main-card,
        .map-container,
        .footer {
            color: var(--ts-text) !important;
        }

        .hero-section {
            background-image: linear-gradient(180deg, rgba(0, 0, 0, .08) 0%, rgba(0, 0, 0, .34) 48%, rgba(0, 0, 0, .72) 100%), url('<?= addslashes($imagen_portada) ?>') !important;
        }

        .hero-subtitle,
        .hero-stat,
        .day-meta-pill,
        .location-item,
        .secondary-locations-new,
        .service-card,
        .accommodation-card,
        .flight-card,
        .clean-flight-card,
        .detail-item,
        .price-main-card,
        .pricing-accordion,
        .overview-content,
        .day-content {
            border-color: var(--ts-line) !important;
        }

        .hero-stat-number,
        .navbar-brand,
        .navbar-nav a:hover,
        .section-title,
        .overview-summary h3,
        .day-title,
        .day-main-title,
        .service-title,
        .accommodation-title,
        .price-amount,
        .clean-flight-time,
        .day-flight-header small,
        .day-flights-title i,
        .day-flight-route i,
        .day-flight-meta i,
        .detail-icon i,
        .detail-icon,
        .secondary-header i,
        .location-marker,
        .btn-link,
        .moment-card-opener-button,
        .accordion-header i,
        .footer a {
            color: var(--ts-brand) !important;
        }

        .detail-icon,
        .detail-icon[style],
        .service-icon,
        .service-icon[style],
        .clean-flight-order,
        .day-number,
        .location-marker,
        .btn-primary,
        .accommodation-selected,
        .selected-badge,
        .gallery-counter,
        .duration-badge {
            background: var(--ts-brand-soft) !important;
            color: var(--ts-brand) !important;
            border-color: var(--ts-line) !important;
            box-shadow: none !important;
        }

        .btn-primary,
        .accommodation-selected,
        .selected-badge,
        .clean-flight-order {
            background: var(--ts-brand) !important;
            color: #fff !important;
        }

        .btn-primary:hover {
            background: var(--ts-brand-dark) !important;
            color: #fff !important;
        }

        .day-card::before,
        .itinerary-timeline::before,
        .timeline-line,
        .progress-bar,
        .accordion-header::before {
            background: var(--ts-line) !important;
            border-color: var(--ts-line) !important;
        }

        .day-card {
            display: grid !important;
            grid-template-columns: 86px minmax(0, 1fr) !important;
            gap: 22px !important;
            align-items: start !important;
        }

        .day-number {
            position: sticky !important;
            top: 92px !important;
            width: 72px !important;
            min-height: 72px !important;
            border: 1px solid var(--ts-line) !important;
            border-radius: 24px !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: center !important;
            align-items: center !important;
            background: var(--ts-surface) !important;
        }

        .day-number-main {
            font-size: .98rem !important;
            line-height: 1 !important;
            color: var(--ts-brand) !important;
        }

        .day-number-label {
            color: var(--ts-muted) !important;
            font-size: .62rem !important;
            letter-spacing: .12em !important;
        }

        .duration-badge {
            position: static !important;
            margin-top: 6px !important;
            padding: 3px 7px !important;
            border-radius: 999px !important;
            font-size: .62rem !important;
            font-weight: 700 !important;
        }

        .day-content {
            overflow: hidden !important;
            padding: 0 !important;
        }

        .day-header {
            padding: 0 !important;
        }

        .day-heading-overlay {
            display: flex !important;
            align-items: flex-end !important;
            justify-content: space-between !important;
            gap: 16px !important;
            padding: 26px 28px 18px !important;
            background: linear-gradient(180deg, var(--ts-surface) 0%, var(--ts-surface-soft) 100%) !important;
            border-bottom: 1px solid var(--ts-line) !important;
        }

        .day-title {
            margin: 0 !important;
            font-family: 'Inter', sans-serif !important;
            font-size: clamp(1.45rem, 2.5vw, 2.15rem) !important;
            font-weight: 850 !important;
            letter-spacing: -.045em !important;
            line-height: 1.05 !important;
        }

        .day-date-text {
            margin: 8px 0 0 !important;
            color: var(--ts-muted) !important;
            font-size: 1rem !important;
            font-weight: 500 !important;
        }

        .day-stay-chip {
            flex: 0 0 auto !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 9px 13px !important;
            border: 1px solid var(--ts-line) !important;
            border-radius: 999px !important;
            background: #fff !important;
            color: var(--ts-brand) !important;
            font-size: .82rem !important;
            font-weight: 800 !important;
        }

        .day-main-title {
            margin: 24px 28px 8px !important;
            font-family: 'Inter', sans-serif !important;
            font-size: clamp(1.15rem, 2vw, 1.55rem) !important;
            font-weight: 800 !important;
            line-height: 1.25 !important;
            letter-spacing: -.025em !important;
        }

        .day-location,
        .day-description,
        .day-services,
        .services-section,
        .accommodations-section,
        .day-flights-section,
        .clean-flights-section {
            padding-left: 28px !important;
            padding-right: 28px !important;
        }

        .day-images {
            margin: 18px 28px 24px !important;
            border-radius: 24px !important;
            overflow: hidden !important;
            border: 1px solid var(--ts-line) !important;
        }

        .day-image::after {
            background: linear-gradient(transparent 62%, rgba(0, 0, 0, .52)) !important;
        }

        .day-meta-row {
            display: none !important;
        }

        .secondary-locations-new {
            background: transparent !important;
            border: 0 !important;
            padding: 10px 0 0 !important;
        }

        .secondary-header h4 {
            color: var(--ts-text) !important;
        }

        .locations-list {
            display: flex !important;
            flex-wrap: wrap !important;
            gap: 8px !important;
            margin-top: 10px !important;
        }

        .location-item {
            width: auto !important;
            padding: 8px 12px !important;
            border-radius: 999px !important;
            background: var(--ts-surface-soft) !important;
            gap: 8px !important;
        }

        .location-marker {
            width: 22px !important;
            height: 22px !important;
            min-width: 22px !important;
            border-radius: 999px !important;
            font-size: .72rem !important;
            background: #fff !important;
        }

        .location-name {
            color: var(--ts-text) !important;
            font-weight: 700 !important;
        }

        .location-coords {
            display: none !important;
        }

        /* Quitar restos de colores semánticos antiguos: azul, verde, amarillo, rojo, morado. */
        [class*="success"],
        [class*="warning"],
        [class*="danger"],
        [class*="info"],
        .meal-badge,
        .service-type-badge,
        .transport-badge,
        .included-badge,
        .status-badge,
        .price-badge,
        .tag,
        .badge {
            background: var(--ts-brand-soft) !important;
            color: var(--ts-brand) !important;
            border-color: var(--ts-line) !important;
        }

        svg,
        svg path,
        .fa,
        .fas,
        .far,
        .fab {
            color: inherit;
        }

        @media (max-width: 768px) {
            .day-card {
                grid-template-columns: 1fr !important;
                gap: 10px !important;
            }

            .day-number {
                position: relative !important;
                top: auto !important;
                width: fit-content !important;
                min-height: auto !important;
                flex-direction: row !important;
                gap: 6px !important;
                padding: 9px 12px !important;
                border-radius: 999px !important;
            }

            .day-heading-overlay {
                align-items: flex-start !important;
                flex-direction: column !important;
                padding: 22px 20px 16px !important;
            }

            .day-main-title,
            .day-images {
                margin-left: 20px !important;
                margin-right: 20px !important;
            }

            .day-location,
            .day-description,
            .day-services,
            .services-section,
            .accommodations-section,
            .day-flights-section,
            .clean-flights-section {
                padding-left: 20px !important;
                padding-right: 20px !important;
            }
        }
    </style>
</head>

<body>

    <!-- Navigation Bar -->
    <nav class="navbar" id="navbar">
        <div class="navbar-content">
            <a href="#" class="navbar-brand"><?= htmlspecialchars($company_name) ?></a>
            <ul class="navbar-nav">
                <li class="navbar-topmargin"><a href="#overview">Resumen</a></li>
                <li class="navbar-topmargin"><a href="#map">Mapa</a></li>
                <li class="navbar-topmargin"><a href="#itinerary">Itinerario</a></li>
                <?php if (!$vendido): ?>
                    <?php if ($mostrar_precios): ?>
                        <li class="navbar-topmargin"><a href="#pricing">Precios </a></li>
                    <?php endif; ?>
                <?php endif; ?>
                <li>
                    <div id="google_translate_element"></div>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-content">
            <div class="hero-subtitle">Tu aventura perfecta</div>
            <h1 class="hero-title"><?= htmlspecialchars($titulo_programa) ?></h1>
            <div class="hero-description">
                Diseñado especialmente para <strong><?= htmlspecialchars($nombre_viajero) ?></strong>
            </div>

            <div class="hero-stats">
                <div class="hero-stat">
                    <span class="hero-stat-number"><?= $duracion_dias ?></span>
                    <span class="hero-stat-label"><?= $duracion_dias == 1 ? 'Noche' : 'Noches' ?></span>
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
                                    <strong>Regreso:</strong> <?= $fecha_fin_formatted ?>
                                </p>
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
                                <p><?= $duracion_dias ?> <?= $duracion_dias == 1 ? 'noche increíble' : 'noches' ?></p>
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
                    <h3 style="margin-bottom: 20px; color: var(--brand-text);">Lo que incluye</h3>
                    <div style="display: flex; flex-direction: column; gap: 15px;">
                        <?php
                        $total_actividades = 0;
                        $total_alojamientos = 0;
                        $total_transportes = 0;

                        foreach ($dias as $dia) {
                            foreach ($dia['servicios'] as $servicio_grupo) {
                                $servicio = $servicio_grupo['principal'];
                                if ($servicio) {
                                    switch ($servicio['tipo_servicio']) {
                                        case 'actividad':
                                            $total_actividades++;
                                            break;
                                        case 'alojamiento':
                                            $total_alojamientos++;
                                            break;
                                        case 'transporte':
                                            $total_transportes++;
                                            break;
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
                                <p><?= $duracion_dias ?> <?= $duracion_dias == 1 ? 'noche' : 'noches' ?> de aventura</p>
                            </div>
                        </div>

                        <?php if ($total_alojamientos > 0): ?>
                            <div class="detail-item">
                                <div class="detail-icon" style="background: var(--brand-primary);">
                                    <i class="fas fa-bed"></i>
                                </div>
                                <div class="detail-info">
                                    <h4><?= $total_alojamientos ?> Alojamientos</h4>
                                    <p>Hospedaje confortable y bien ubicado</p>

                                    <?php if (!$is_public): ?>
                                        <a href="<?= APP_URL ?>/modules/bonos/preview.php?programa_id=<?= (int) $programa_id ?>"
                                            target="_blank" class="btn btn-outline"
                                            style="margin-top: 12px; display: inline-flex; align-items: center; gap: 8px; padding: 10px 14px; font-size: 13px;">
                                            <i class="fas fa-file-pdf"></i>
                                            Bono hotelero
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($total_transportes > 0): ?>
                            <div class="detail-item">
                                <div class="detail-icon">
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
                                <div class="detail-icon">
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
                    <div class="map-tooltip">
                        <i class="fas fa-mouse-pointer"></i>
                        <span>Usa <strong>Ctrl + Scroll</strong> para hacer zoom</span>
                    </div>
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
                    $duracion = max(1, (int) ($dia['duracion_estancia'] ?? 1));
                    $diaFinal = $diaActual + $duracion - 1;
                    $rangoTexto = ts_day_range_label($diaActual, $duracion);
                    $fechaInicioDia = $dia['fecha_calculada'] ?? null;
                    $fechaFinDia = $dia['fecha_fin_calculada'] ?? $fechaInicioDia;
                    $fechaTexto = ts_date_range_label($fechaInicioDia, $fechaFinDia, $duracion);
                    ?>
                    <div class="day-card" style="animation-delay: <?= $index * 0.1 ?>s;">
                        <div class="day-number">
                            <div class="day-number-main">
                                <?= $duracion === 1 ? $diaActual : $diaActual . '–' . $diaFinal ?>
                            </div>
                            <div class="day-number-label">
                                <?= $duracion === 1 ? 'DÍA' : 'DÍAS' ?>
                            </div>
                        </div>

                        <div class="day-content">
                            <div class="day-header">
                                <div class="day-heading-overlay">
                                    <div>
                                        <h3 class="day-title"><?= htmlspecialchars($rangoTexto) ?></h3>
                                        <?php if (!empty($fechaTexto)): ?>
                                            <p class="day-date-text"><?= htmlspecialchars($fechaTexto) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <h4 class="day-main-title"><?= htmlspecialchars($dia['titulo']) ?></h4>

                                <div class="day-location">

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
                                                    <div class="location-name"><?= htmlspecialchars($dia['ubicacion']) ?>
                                                    </div>
                                                </div>
                                            </li>

                                            <?php if (!empty($dia['ubicaciones_secundarias'])): ?>
                                                <!-- Resto de ubicaciones -->
                                                <?php foreach ($dia['ubicaciones_secundarias'] as $index => $ubicacion_sec): ?>
                                                    <li class="location-item">
                                                        <div class="location-marker"><?= $index + 2 ?></div>
                                                        <div class="location-details">
                                                            <div class="location-name">
                                                                <?= htmlspecialchars($ubicacion_sec['ubicacion']) ?>
                                                            </div>
                                                            <?php if ($ubicacion_sec['latitud'] && $ubicacion_sec['longitud']): ?>
                                                                <div class="location-coords">
                                                                    <i class="fas fa-crosshairs"></i>
                                                                    <?= number_format($ubicacion_sec['latitud'], 4) ?>,
                                                                    <?= number_format($ubicacion_sec['longitud'], 4) ?>
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
                                <?php
                                $imagenes_dia = array_filter([
                                    $dia['imagen1'],
                                    $dia['imagen2'],
                                    $dia['imagen3']
                                ]);
                                ?>
                                <div class="day-images">
                                    <?php if ($dia['imagen1']): ?>
                                        <div class="day-image"
                                            style="background-image: url('<?= htmlspecialchars($dia['imagen1']) ?>')"
                                            onclick="openGalleryModal(<?= htmlspecialchars(json_encode($imagenes_dia)) ?>, 0, '<?= htmlspecialchars($dia['titulo']) ?>')">
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($dia['imagen2']): ?>
                                        <div class="day-image"
                                            style="background-image: url('<?= htmlspecialchars($dia['imagen2']) ?>')"
                                            onclick="openGalleryModal(<?= htmlspecialchars(json_encode($imagenes_dia)) ?>, 1, '<?= htmlspecialchars($dia['titulo']) ?>')">
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($dia['imagen3']): ?>
                                        <div class="day-image"
                                            style="background-image: url('<?= htmlspecialchars($dia['imagen3']) ?>')"
                                            onclick="openGalleryModal(<?= htmlspecialchars(json_encode($imagenes_dia)) ?>, 2, '<?= htmlspecialchars($dia['titulo']) ?>')">
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <div class="day-services">
                                <?php if (!empty($dia['descripcion'])): ?>
                                    <div class="day-description">
                                        <p><?= nl2br(htmlspecialchars($dia['descripcion'])) ?></p>
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


                                <?php
                                $vuelos_dia = $db->fetchAll("
                                SELECT 
                                    vd.orden,
                                    cv.codigo_vuelo,
                                    cv.aerolinea,
                                    cv.ciudad_origen,
                                    cv.codigo_aeropuerto_origen,
                                    cv.aeropuerto_origen,
                                    cv.ciudad_destino,
                                    cv.codigo_aeropuerto_destino,
                                    cv.aeropuerto_destino,
                                    cv.terminal,
                                    cv.hora_salida,
                                    cv.hora_llegada
                                FROM vuelos_dias vd
                                INNER JOIN codigos_vuelos cv ON cv.id = vd.codigo_vuelo_id
                                WHERE vd.programa_dias_id = ?
                                ORDER BY vd.orden ASC
                            ", [$dia['id']]);
                                ?>

                                <?php if (!empty($dia['servicios']) || !empty($vuelos_dia)): ?>
                                    <h4
                                        style="margin-bottom: 20px; color: var(--brand-text); font-size: 1.2rem; font-weight: 600;">
                                        <i class="fas fa-list-ul"></i> Servicios incluidos
                                        <?php if ($duracion > 1): ?>
                                            <span style="font-size: 0.8rem; color: var(--brand-muted); font-weight: normal;">
                                                (Disponibles durante <?= $duracion ?> días)
                                            </span>
                                        <?php endif; ?>
                                    </h4>



                                    <?php if (!empty($vuelos_dia)): ?>
                                        <section class="day-flights-section">
                                            <div class="day-flights-title">
                                                <i class="fas fa-plane-departure"></i>
                                                <span>Vuelos del día</span>
                                            </div>

                                            <div class="day-flights-list">
                                                <?php foreach ($vuelos_dia as $vuelo): ?>
                                                    <div class="clean-flight-card">
                                                        <div class="clean-flight-header">
                                                            <div>
                                                                <div class="clean-flight-code">
                                                                    <?= htmlspecialchars($vuelo['codigo_vuelo']) ?>
                                                                </div>
                                                                <div class="clean-flight-airline">
                                                                    <?= htmlspecialchars($vuelo['aerolinea']) ?>
                                                                </div>
                                                            </div>

                                                            <div class="clean-flight-order">
                                                                Vuelo <?= (int) $vuelo['orden'] ?>
                                                            </div>
                                                        </div>

                                                        <div class="clean-flight-route">
                                                            <div class="clean-airport">
                                                                <div class="clean-airport-code">
                                                                    <?= htmlspecialchars($vuelo['codigo_aeropuerto_origen']) ?>
                                                                </div>
                                                                <div class="clean-airport-city">
                                                                    <?= htmlspecialchars($vuelo['ciudad_origen']) ?>
                                                                </div>
                                                                <div class="clean-flight-time">
                                                                    <?= htmlspecialchars(substr($vuelo['hora_salida'], 0, 5)) ?>
                                                                </div>
                                                            </div>

                                                            <div class="clean-flight-line">
                                                                <div class="clean-duration">
                                                                    Vuelo programado
                                                                </div>
                                                                <div class="clean-line">
                                                                    <span></span>
                                                                    <i class="fas fa-plane"></i>
                                                                    <span></span>
                                                                </div>
                                                            </div>

                                                            <div class="clean-airport clean-airport-right">
                                                                <div class="clean-airport-code">
                                                                    <?= htmlspecialchars($vuelo['codigo_aeropuerto_destino']) ?>
                                                                </div>
                                                                <div class="clean-airport-city">
                                                                    <?= htmlspecialchars($vuelo['ciudad_destino']) ?>
                                                                </div>
                                                                <div class="clean-flight-time">
                                                                    <?= htmlspecialchars(substr($vuelo['hora_llegada'], 0, 5)) ?>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="clean-flight-details">
                                                            <div>
                                                                <strong>Aeropuerto origen</strong>
                                                                <span><?= htmlspecialchars($vuelo['aeropuerto_origen']) ?></span>
                                                            </div>

                                                            <div>
                                                                <strong>Aeropuerto destino</strong>
                                                                <span><?= htmlspecialchars($vuelo['aeropuerto_destino']) ?></span>
                                                            </div>

                                                            <div>
                                                                <strong>Terminal</strong>
                                                                <span><?= !empty($vuelo['terminal']) ? htmlspecialchars($vuelo['terminal']) : '-' ?></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </section>
                                    <?php endif; ?>

                                    <div class="services-grid">
                                        <?php foreach ($dia['servicios'] as $servicio_grupo): ?>
                                            <?php $servicio = $servicio_grupo['principal']; ?>
                                            <?php if ($servicio): ?>
                                                <div class="service-group">
                                                    <!-- Servicio Principal -->
                                                    <div class="service-item principal">
                                                        <div class="service-icon <?= $servicio['tipo_servicio'] ?>">
                                                            <i
                                                                class="<?= getServiceIcon($servicio['tipo_servicio'], $servicio['medio_transporte']) ?>"></i>
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
                                                            <?php if ($servicio['tipo_servicio'] === 'alojamiento' && !empty($servicio['acomodacion_nombre'])): ?>
                                                                <div class="accommodation-detail">
                                                                    <i class="fas fa-bed"></i>
                                                                    <span>
                                                                        <?= htmlspecialchars($servicio['acomodacion_nombre']) ?>
                                                                        <?php if (!empty($servicio['acomodacion_capacidad'])): ?>
                                                                            · <?= (int) $servicio['acomodacion_capacidad'] ?> pax
                                                                        <?php endif; ?>
                                                                        <?php if (!empty($servicio['acomodacion_descripcion'])): ?>
                                                                            · <?= htmlspecialchars($servicio['acomodacion_descripcion']) ?>
                                                                        <?php endif; ?>
                                                                    </span>
                                                                </div>
                                                            <?php elseif ($servicio['tipo_servicio'] === 'alojamiento'): ?>
                                                                <div class="accommodation-detail muted">
                                                                    <i class="fas fa-bed"></i>
                                                                    <span>Acomodación por definir</span>
                                                                </div>
                                                            <?php endif; ?>

                                                            <!-- ✅ HOTEL: Imagen a la izquierda del texto -->
                                                            <?php if ($servicio['tipo_servicio'] == 'alojamiento'): ?>
                                                                <div class="hotel-content">
                                                                    <?php if ($servicio['alojamiento_imagen_principal']): ?>
                                                                        <div class="hotel-thumbnail"
                                                                            onclick="openImageModal('<?= htmlspecialchars($servicio['alojamiento_imagen_principal']) ?>', '<?= htmlspecialchars($servicio['nombre']) ?>')">
                                                                            <img src="<?= htmlspecialchars($servicio['alojamiento_imagen_principal']) ?>"
                                                                                alt="<?= htmlspecialchars($servicio['nombre']) ?>">
                                                                            <div class="thumbnail-overlay">
                                                                                <i class="fas fa-search-plus"></i>
                                                                            </div>
                                                                        </div>
                                                                    <?php endif; ?>

                                                                    <div class="hotel-text">
                                                                        <p><?= nl2br(htmlspecialchars($servicio['descripcion'])) ?></p>

                                                                        <div class="service-meta">
                                                                            <?php if ($servicio['ubicacion']): ?>
                                                                                <span><i class="fas fa-map-marker-alt"></i>
                                                                                    <?= htmlspecialchars($servicio['ubicacion']) ?></span>
                                                                            <?php endif; ?>

                                                                            <?php if ($servicio['categoria_alojamiento']): ?>
                                                                                <span><i class="fas fa-star"></i>
                                                                                    <?= $servicio['categoria_alojamiento'] ?> estrellas</span>
                                                                            <?php endif; ?>
                                                                        </div>

                                                                        <?php if ($servicio['alojamiento_sitio_web']): ?>
                                                                            <div class="service-website">
                                                                                <a href="<?= htmlspecialchars($servicio['alojamiento_sitio_web']) ?>"
                                                                                    target="_blank" rel="noopener noreferrer">
                                                                                    <i class="fas fa-external-link-alt"></i> Visitar sitio web
                                                                                </a>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>

                                                                <!-- ✅ ACTIVIDAD: Galería con altura completa -->
                                                            <?php elseif ($servicio['tipo_servicio'] == 'actividad'): ?>
                                                                <p><?= nl2br(htmlspecialchars($servicio['descripcion'])) ?></p>

                                                                <div class="service-meta">
                                                                    <?php if ($servicio['ubicacion']): ?>
                                                                        <span><i class="fas fa-map-marker-alt"></i>
                                                                            <?= htmlspecialchars($servicio['ubicacion']) ?></span>
                                                                    <?php endif; ?>
                                                                </div>

                                                                <?php
                                                                $imagenes = array_filter([
                                                                    $servicio['imagen'],
                                                                    $servicio['imagen2'],
                                                                    $servicio['imagen3']
                                                                ]);
                                                                ?>
                                                                <?php if (!empty($imagenes)): ?>
                                                                    <?php
                                                                    $galleryClass = 'activity-gallery';
                                                                    if (count($imagenes) == 1)
                                                                        $galleryClass .= ' single-image';
                                                                    elseif (count($imagenes) == 2)
                                                                        $galleryClass .= ' two-images';
                                                                    ?>
                                                                    <div class="<?= $galleryClass ?>">
                                                                        <?php foreach ($imagenes as $index => $imagen): ?>
                                                                            <div class="gallery-item"
                                                                                onclick="openGalleryModal(<?= htmlspecialchars(json_encode($imagenes)) ?>, <?= $index ?>, '<?= htmlspecialchars($servicio['nombre']) ?>')">
                                                                                <img src="<?= htmlspecialchars($imagen) ?>"
                                                                                    alt="<?= htmlspecialchars($servicio['nombre']) ?>">
                                                                                <div class="gallery-overlay">
                                                                                    <i class="fas fa-search-plus"></i>
                                                                                </div>
                                                                            </div>
                                                                        <?php endforeach; ?>
                                                                        <?php if (count($imagenes) > 1): ?>
                                                                            <div class="gallery-count">
                                                                                <i class="fas fa-images"></i> <?= count($imagenes) ?>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                <?php endif; ?>

                                                                <!-- ✅ TRANSPORTE: Solo texto -->
                                                            <?php else: ?>
                                                                <p><?= nl2br(htmlspecialchars($servicio['descripcion'])) ?></p>

                                                                <div class="service-meta">
                                                                    <?php if ($servicio['ubicacion']): ?>
                                                                        <span><i class="fas fa-map-marker-alt"></i>
                                                                            <?= htmlspecialchars($servicio['ubicacion']) ?></span>
                                                                    <?php endif; ?>

                                                                    <?php if ($servicio['duracion']): ?>
                                                                        <span><i class="fas fa-clock"></i>
                                                                            <?= htmlspecialchars($servicio['duracion']) ?></span>
                                                                    <?php endif; ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>

                                                    <!-- Alternativas -->
                                                    <?php if (!empty($servicio_grupo['alternativas'])): ?>
                                                        <div class="alternatives-header"
                                                            onclick="toggleAlternatives(<?= $servicio['id'] ?>)">
                                                            <i class="fas fa-sync-alt"></i>
                                                            <span><?= count($servicio_grupo['alternativas']) ?>
                                                                alternativa<?= count($servicio_grupo['alternativas']) > 1 ? 's' : '' ?>
                                                                disponible<?= count($servicio_grupo['alternativas']) > 1 ? 's' : '' ?></span>
                                                            <i class="fas fa-chevron-down alternatives-toggle"
                                                                id="toggle-<?= $servicio['id'] ?>"></i>
                                                        </div>

                                                        <div class="alternatives-list" id="alternatives-<?= $servicio['id'] ?>">
                                                            <?php foreach ($servicio_grupo['alternativas'] as $alternativa): ?>
                                                                <div class="service-item alternativa">
                                                                    <div class="alternative-badge">Alt <?= $alternativa['orden_alternativa'] ?>
                                                                    </div>

                                                                    <div class="service-icon">
                                                                        <i
                                                                            class="<?= getServiceIcon($alternativa['tipo_servicio'], $alternativa['medio_transporte']) ?>"></i>
                                                                    </div>

                                                                    <div class="service-details">
                                                                        <h4 style="color: var(--brand-text); margin-bottom: 5px;">
                                                                            <?= htmlspecialchars($alternativa['nombre']) ?>
                                                                        </h4>

                                                                        <?php if ($alternativa['descripcion']): ?>
                                                                            <p style="font-size: 0.9rem; color: var(--brand-muted);">
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

                                                                    <?php
                                                                    // Determinar qué campo de imagen usar según el tipo de servicio
                                                                    $imagen_url = null;
                                                                    if (!empty($alternativa['imagen'])) {
                                                                        $imagen_url = $alternativa['imagen'];  // Actividades
                                                                    } elseif (!empty($alternativa['alojamiento_imagen_principal'])) {
                                                                        $imagen_url = $alternativa['alojamiento_imagen_principal'];  // Alojamientos
                                                                    }
                                                                    ?>

                                                                    <?php if ($imagen_url): ?>
                                                                        <div class="service-image"
                                                                            style="width: 120px; height: 120px; background-image: url('<?= htmlspecialchars($imagen_url) ?>');"
                                                                            onclick="showImage('<?= htmlspecialchars($imagen_url) ?>')"></div>
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
        <?php if (!$vendido): ?>
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
                                    // ✅ PRIMERO: Definir las cantidades
                                    $cantidad_adultos = $precios['cantidad_adultos'] ?? 1;
                                    $cantidad_ninos = $precios['cantidad_ninos'] ?? 0;

                                    // ✅ SEGUNDO: Usar las cantidades en las condiciones
                                    $mostrar_adultos = isset($precios['precio_adulto']) && $precios['precio_adulto'] > 0;
                                    $mostrar_ninos = isset($precios['precio_nino']) && $precios['precio_nino'] > 0 && $cantidad_ninos > 0;
                                    ?>

                                    <?php if ($mostrar_adultos): ?>
                                        <div class="price-category adulto">
                                            <div class="category-icon">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <div class="category-info">
                                                <div class="category-label">
                                                    <?= $cantidad_adultos ?>             <?= $cantidad_adultos == 1 ? 'Adulto' : 'Adultos' ?>
                                                </div>
                                                <div class="category-price">
                                                    <span class="price-currency"><?= htmlspecialchars($precios['moneda']) ?></span>
                                                    <span
                                                        class="price-value"><?= number_format($precios['precio_adulto'], 0, ',', '.') ?></span>
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
                                                    <?= $cantidad_ninos ?>             <?= $cantidad_ninos == 1 ? 'Niño' : 'Niños' ?>
                                                </div>
                                                <div class="category-price">
                                                    <span class="price-currency"><?= htmlspecialchars($precios['moneda']) ?></span>
                                                    <span
                                                        class="price-value"><?= number_format($precios['precio_nino'], 0, ',', '.') ?></span>
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
                                            <span
                                                class="price-value"><?= number_format($precios['precio_total'], 0, ',', '.') ?></span>
                                        </div>
                                    </div>
                                </div>


                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                <!-- Resto del contenido de precios (incluye/no incluye/condiciones) -->
                <?php if (!$vendido): ?>
                    <div class="pricing-accordions">
                        <!-- Lo que incluye -->
                        <?php if (!empty($precios['precio_incluye'])): ?>
                            <div class="pricing-accordion">
                                <div class="accordion-header" onclick="toggleAccordion(this)">
                                    <div class="accordion-title">
                                        <i class="fas fa-check-circle" style="color: var(--brand-primary);"></i>
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
                                            if (empty($item))
                                                continue;
                                            ?>
                                            <li>
                                                <i class="fas fa-check" style="color: var(--brand-primary);"></i>
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
                                        <i class="fas fa-times-circle" style="color: var(--brand-primary);"></i>
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
                                            if (empty($item))
                                                continue;
                                            ?>
                                            <li>
                                                <i class="fas fa-times" style="color: var(--brand-primary);"></i>
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
                                        <i class="fas fa-file-contract" style="color: var(--brand-primary);"></i>
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

                <?php if (!empty($programa_id)): ?>
                    <a href="<?= APP_URL ?>/modules/bonos/preview.php?programa_id=<?= (int) $programa_id ?>"
                        class="btn btn-outline" target="_blank">
                        <i class="fas fa-file-pdf"></i>
                        Bono hotelero
                    </a>
                <?php endif; ?>


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
        window.addEventListener('scroll', function () {
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
            document.addEventListener('DOMContentLoaded', function () {
                const puntosMapa = <?= json_encode($puntos_mapa) ?>;

                if (puntosMapa.length > 0) {
                    let centerLat = puntosMapa.reduce((sum, loc) => sum + loc.lat, 0) / puntosMapa.length;
                    let centerLng = puntosMapa.reduce((sum, loc) => sum + loc.lng, 0) / puntosMapa.length;

                    const map = L.map('map', {
                        scrollWheelZoom: false
                    }).setView([centerLat, centerLng], 8);

                    // Detectar Ctrl + scroll para zoom
                    map.getContainer().addEventListener('wheel', function (e) {
                        if (e.ctrlKey) {
                            e.preventDefault();
                            map.scrollWheelZoom.enable();
                            setTimeout(() => map.scrollWheelZoom.disable(), 100);
                        }
                    });

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© OpenStreetMap contributors'
                    }).addTo(map);

                    const iconColors = {
                        'dia': 'var(--brand-primary)',
                        'ubicacion_secundaria': 'var(--brand-primary)'
                    };

                    puntosMapa.forEach(function (punto, index) {
                        const color = iconColors[punto.tipo] || 'var(--brand-muted)';

                        const customIcon = L.divIcon({
                            html: `
                            <div style="
                                background-color: ${color};
                                width: 36px;
                                height: 36px;
                                border-radius: 50%;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                color: white;
                                font-weight: bold;
                                font-size: 15px;
                                border: 3px solid white;
                                box-shadow: 0 3px 10px rgba(0,0,0,0.4);
                            ">
                                ${punto.tipo === 'dia' ? '📍' : punto.dia}
                            </div>
                        `,
                            className: 'custom-marker',
                            iconSize: [36, 36],
                            iconAnchor: [18, 18]
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

                    const colores = ['var(--brand-primary)', 'var(--brand-primary)', 'var(--brand-primary)', 'var(--brand-primary)', '#9b59b6', '#1abc9c'];

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
        function toggleAccordion(element) {
            // Obtener el header desde el elemento clickeado
            const header = element.closest ? element.closest('.accordion-header') : element;
            if (!header) {
                console.error('No se encontró el header del accordion');
                return;
            }

            // Obtener el contenido (siguiente elemento hermano)
            const content = header.nextElementSibling;
            if (!content || !content.classList.contains('accordion-content')) {
                console.error('No se encontró el contenido del accordion');
                return;
            }

            // Obtener la flecha
            const arrow = header.querySelector('.accordion-icon, i[class*="chevron"]');



            // Toggle del accordion actual
            const isActive = content.classList.contains('active');

            if (isActive) {
                content.classList.remove('active');
                if (arrow) arrow.classList.remove('rotated');
                header.classList.remove('active');
            } else {
                content.classList.add('active');
                if (arrow) arrow.classList.add('rotated');
                header.classList.add('active');
            }
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
                    <h3 style="margin-bottom: 10px; color: var(--brand-text);">¡Solicita tu cotización!</h3>
                    <p style="color: var(--brand-muted); margin-bottom: 25px; font-size: 0.9rem;">
                        Nos pondremos en contacto contigo para personalizar este increíble viaje
                    </p>
                    <div style="display: flex; gap: 10px; justify-content: center;">
                        <button onclick="this.closest('[style*=\"position: fixed\"]').remove()" 
                                style="padding: 10px 20px; background: var(--brand-muted); color: white; border: none; border-radius: 20px; cursor: pointer; font-size: 0.9rem;">
                            Cerrar
                        </button>
                        <button onclick="window.location.href='mailto:info@agencia.com?subject=Cotización Itinerario'" 
                                style="padding: 10px 20px; background: var(--brand-primary); color: white; border: none; border-radius: 20px; cursor: pointer; font-size: 0.9rem;">
                            Enviar Email
                        </button>
                    </div>
                </div>
            `;

            document.body.appendChild(modal);

            // Cerrar modal al hacer click fuera
            modal.addEventListener('click', function (e) {
                if (e.target === modal) {
                    modal.remove();
                }
            });
        }

        function downloadItinerary() {
            console.log('📄 Iniciando preparación de PDF...');

            // Preparar documento para impresión PRIMERO
            document.body.classList.add('print-mode');

            // Expandir TODOS los acordeones
            document.querySelectorAll('.accordion-content').forEach(content => {
                content.style.maxHeight = 'none';
                content.style.overflow = 'visible';
                content.style.display = 'block';
                content.classList.add('active');
            });

            // Expandir TODAS las alternativas
            document.querySelectorAll('.alternatives-list').forEach(list => {
                list.style.maxHeight = 'none';
                list.style.overflow = 'visible';
                list.style.display = 'block';
                list.classList.add('expanded');
            });

            // Marcar headers como activos
            document.querySelectorAll('.accordion-header').forEach(header => {
                header.classList.add('active');
            });

            // Scroll to top
            window.scrollTo(0, 0);

            // Esperar un momento y luego imprimir
            setTimeout(() => {
                console.log('✅ Contenido expandido, abriendo diálogo de impresión...');
                window.print();
            }, 300);
        }

        // Limpiar después de cerrar el diálogo de impresión
        window.addEventListener('afterprint', function () {
            console.log('✅ Diálogo de impresión cerrado, restaurando vista...');
            document.body.classList.remove('print-mode');
        });

        // =====================================================
        // ANIMATION ON SCROLL
        // =====================================================
        document.addEventListener('DOMContentLoaded', function () {
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.style.animationDelay = '0s';
                        entry.target.classList.add('animate');
                    }
                });
            }, observerOptions);

            // Observar elementos con animaciones
            document.querySelectorAll('.day-card, .service-group, .detail-item, .pricing-accordion').forEach(function (el) {
                observer.observe(el);
            });
        });

        // =====================================================
        // KEYBOARD ACCESSIBILITY
        // =====================================================
        document.addEventListener('keydown', function (e) {
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
        window.addEventListener('beforeprint', function () {
            console.log('Preparando documento para impresión...');

            // Expandir todos los accordions para impresión
            document.querySelectorAll('.accordion-content').forEach(function (content) {
                content.style.maxHeight = 'none';
                content.style.overflow = 'visible';
                content.style.display = 'block';
                content.style.padding = '15px';
                content.classList.add('active');
            });

            // Expandir todas las alternativas
            document.querySelectorAll('.alternatives-list').forEach(function (list) {
                list.style.maxHeight = 'none';
                list.style.overflow = 'visible';
                list.style.display = 'block';
                list.classList.add('expanded');
            });

            // Asegurar que todos los headers estén visibles
            document.querySelectorAll('.accordion-header').forEach(function (header) {
                header.classList.add('active');
            });

            // Expandir flechas
            document.querySelectorAll('.accordion-arrow, .alternatives-toggle').forEach(function (arrow) {
                arrow.classList.add('rotated');
            });

            // Aplicar clase print-mode
            document.body.classList.add('print-mode');

            console.log('Documento preparado para impresión');
        });

        window.addEventListener('afterprint', function () {
            console.log('Impresión finalizada, restaurando vista...');

            // Restaurar estado original después de impresión
            // Esperar un poco antes de colapsar para evitar flash visual
            setTimeout(() => {
                document.body.classList.remove('print-mode');

                // Solo colapsar los que NO estaban activos antes
                document.querySelectorAll('.accordion-content:not([data-was-active])').forEach(function (content) {
                    content.style.maxHeight = '0';
                    content.style.overflow = 'hidden';
                    content.classList.remove('active');
                });

                document.querySelectorAll('.alternatives-list:not([data-was-expanded])').forEach(function (list) {
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
            return function () {
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
        document.addEventListener('DOMContentLoaded', function () {
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
            // Usar el mismo modal minimalista que las actividades
            openImageModal(imageSrc, 'Imagen del día');
        }

        function closeImageModal() {
            const modal = document.getElementById('simpleImageModal');
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }

        // Cerrar con ESC o click fuera
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeImageModal();
            }
        });

        document.addEventListener('click', function (e) {
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
        document.addEventListener('keydown', function (e) {
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
        document.addEventListener('click', function (e) {
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
                outline: 2px solid var(--brand-primary);
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
        document.addEventListener('DOMContentLoaded', function () {
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
            setTimeout(function () {
                const select = document.querySelector('.goog-te-combo');
                if (select) {
                    select.addEventListener('change', function () {
                        if (this.value) {
                            sessionStorage.setItem('language', this.value);
                            localStorage.setItem('preferredLanguage', this.value);
                        }
                    });
                }
            }, 2000);
        });
    </script>

    <script>
        // ========== MODAL PARA UNA SOLA IMAGEN (HOTELES) ==========
        function openImageModal(imageSrc, title) {
            let modal = document.getElementById('single-image-modal');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'single-image-modal';
                modal.className = 'image-modal';
                modal.innerHTML = `
            <span class="modal-close" onclick="closeModals()">&times;</span>
            <div class="modal-title" id="single-modal-title"></div>
            <div class="modal-image-container">
                <img id="single-modal-image">
            </div>
        `;
                document.body.appendChild(modal);

                modal.addEventListener('click', function (e) {
                    if (e.target === modal) closeModals();
                });
            }

            document.getElementById('single-modal-title').textContent = title;
            document.getElementById('single-modal-image').src = imageSrc;
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        // ========== MODAL PARA GALERÍA (ACTIVIDADES) ==========
        let currentGallery = [];
        let currentGalleryIndex = 0;

        function openGalleryModal(images, startIndex, title) {
            currentGallery = images;
            currentGalleryIndex = startIndex;

            let modal = document.getElementById('gallery-modal');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'gallery-modal';
                modal.className = 'image-modal';
                modal.innerHTML = `
            <span class="modal-close" onclick="closeModals()">&times;</span>
            <div class="modal-title" id="gallery-modal-title"></div>
            <div class="modal-image-container">
                <button class="modal-nav prev" onclick="changeGalleryImage(-1)">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <img id="gallery-modal-image">
                <button class="modal-nav next" onclick="changeGalleryImage(1)">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            <div class="modal-counter" id="gallery-counter"></div>
        `;
                document.body.appendChild(modal);

                modal.addEventListener('click', function (e) {
                    if (e.target === modal) closeModals();
                });
            }

            document.getElementById('gallery-modal-title').textContent = title;
            updateGalleryImage();
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function changeGalleryImage(direction) {
            currentGalleryIndex += direction;

            if (currentGalleryIndex >= currentGallery.length) {
                currentGalleryIndex = 0;
            } else if (currentGalleryIndex < 0) {
                currentGalleryIndex = currentGallery.length - 1;
            }

            updateGalleryImage();
        }

        function updateGalleryImage() {
            document.getElementById('gallery-modal-image').src = currentGallery[currentGalleryIndex];
            document.getElementById('gallery-counter').textContent =
                `${currentGalleryIndex + 1} / ${currentGallery.length}`;
        }

        // ========== CERRAR MODALES ==========
        function closeModals() {
            const modals = document.querySelectorAll('.image-modal');
            modals.forEach(modal => modal.classList.remove('active'));
            document.body.style.overflow = 'auto';
        }

        // Cerrar con ESC
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeModals();
            }
            // Navegar galería con flechas
            if (document.getElementById('gallery-modal')?.classList.contains('active')) {
                if (e.key === 'ArrowLeft') changeGalleryImage(-1);
                if (e.key === 'ArrowRight') changeGalleryImage(1);
            }
        });
    </script>
    <script src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
</body>

</html>