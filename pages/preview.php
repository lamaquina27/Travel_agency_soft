<?php
// ====================================================================
// ARCHIVO: pages/preview.php - VISTA PREVIA ESTILO LANDING PAGE
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

// Obtener ID del programa y generar token único
$programa_id = $_GET['id'] ?? null;
$token = $_GET['token'] ?? null;

if (!$programa_id) {
    header('Location: ' . APP_URL . '/itinerarios');
    exit;
}

try {
    ConfigManager::init();
    $company_name = ConfigManager::getCompanyName();
} catch(Exception $e) {
    $company_name = 'Travel Agency';
}

// Cargar datos del programa
try {
    $db = Database::getInstance();
    
    // Obtener datos completos del programa
    $programa = $db->fetch(
        "SELECT ps.*, pp.titulo_programa, pp.foto_portada, pp.idioma_predeterminado
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
        "SELECT * FROM programa_dias WHERE solicitud_id = ? ORDER BY dia_numero ASC", 
        [$programa_id]
    );
    
    // Obtener precios si existen
    $precios = $db->fetch(
        "SELECT * FROM programa_precios WHERE solicitud_id = ?", 
        [$programa_id]
    );
    
} catch(Exception $e) {
    error_log("Error cargando programa para preview: " . $e->getMessage());
    header('Location: ' . APP_URL . '/itinerarios');
    exit;
}

// Calcular duración del viaje ----------------Esto lo moví de posición para solucionar error

$duracion_dias = 0;
foreach ($dias as $dia) {
    $duracion_estancia = intval($dia['duracion_estancia']) ?: 1;
    $duracion_dias += $duracion_estancia;
}

// Preparar datos para la vista
$titulo_programa = $programa['titulo_programa'] ?: 'Mi Viaje a ' . $programa['destino'];
$nombre_viajero = trim($programa['nombre_viajero'] . ' ' . $programa['apellido_viajero']);
// Normalizar imagen: extraer solo el path si es URL absoluta y reconstruir
// con APP_URL local. Funciona con BD del hosting o local sin cambios.
$_foto_raw = $programa['foto_portada'] ?? '';
if ($_foto_raw) {
    if (str_starts_with($_foto_raw, 'http://') || str_starts_with($_foto_raw, 'https://')) {
        $_foto_raw = parse_url($_foto_raw, PHP_URL_PATH); // → /assets/uploads/...
    }
    $_foto_raw = rtrim(APP_URL, '/') . '/' . ltrim($_foto_raw, '/');
}
$imagen_portada = $_foto_raw ?: APP_URL . '/assets/images/default-travel.jpg';
$destino = $programa['destino'];
$num_dias = $duracion_dias; // Usar la duración calculada en lugar del conteo
$num_pasajeros = $programa['numero_pasajeros'];


// Si no hay días en el programa, usar el conteo de días
if ($duracion_dias == 0) {
    $duracion_dias = count($dias);
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

// URL única para compartir
$share_token = md5($programa_id . 'travel_preview_' . date('Y-m-d'));
$share_url = APP_URL . '/preview?id=' . $programa_id . '&token=' . $share_token;


//Inicialización standar para evitar error por no inicialización
$is_public_access = $is_public_access ?? false;

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo_programa) ?> - Vista Previa</title>

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
    
    <!-- Meta tags para compartir en redes sociales -->
    <meta property="og:title" content="<?= htmlspecialchars($titulo_programa) ?>">
    <meta property="og:description" content="Programa de viaje personalizado para <?= htmlspecialchars($nombre_viajero) ?> a <?= htmlspecialchars($destino) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($imagen_portada) ?>">
    <meta property="og:url" content="<?= htmlspecialchars($share_url) ?>">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($titulo_programa) ?>">
    <meta name="twitter:description" content="Programa de viaje personalizado">
    <meta name="twitter:image" content="<?= htmlspecialchars($imagen_portada) ?>">
    
    <!-- Fonts elegantes -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html, body {
            height: 100%;
            overflow-x: hidden;
            font-family: 'Inter', sans-serif;
        }
        
        /* ========================================
           FONDO CON IMAGEN Y EFECTO ZOOM
           ======================================== */
        .hero-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background-image: url('<?= addslashes($imagen_portada) ?>');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            z-index: -2;
            
            /* Efecto zoom suave continuo */
            animation: heroZoom 25s ease-in-out infinite alternate;
            will-change: transform;
        }
        
        @keyframes heroZoom {
            0% {
                transform: scale(1);
            }
            100% {
                transform: scale(1.08);
            }
        }
        
        /* Overlay con gradiente elegante */
        .hero-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            background: linear-gradient(
                135deg,
                rgba(0, 0, 0, 0.4) 0%,
                rgba(0, 0, 0, 0.2) 50%,
                rgba(0, 0, 0, 0.6) 100%
            );
            z-index: -1;
        }
        
        /* ========================================
           CONTAINER PRINCIPAL
           ======================================== */
        .preview-container {
            min-height: 100vh;
            display: flex;
            position: relative;
            z-index: 1;
        }
        
        /* ========================================
           BARRA LATERAL ELEGANTE
           ======================================== */
        .info-sidebar {
            width: 480px;
            min-height: 100vh;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255, 255, 255, 0.2);
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 60px 50px;
            box-shadow: 0 0 50px rgba(0, 0, 0, 0.1);
            
            /* Animación de entrada */
            animation: slideInLeft 1s ease-out;
            position: relative;
        }
        
        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        /* Logo de la agencia en la esquina */
        .agency-logo {
            position: absolute;
            top: 30px;
            left: 50px;
            font-size: 14px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .agency-logo i {
            font-size: 16px;
            color: #3b82f6;
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
           CONTENIDO DEL PROGRAMA
           ======================================== */
        .program-header {
            margin-bottom: 40px;
        }
        
        .program-subtitle {
            font-size: 16px;
            font-weight: 500;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 12px;
            opacity: 0;
            animation: fadeInUp 1s ease-out 0.3s forwards;
        }
        
        .program-title {
            font-family: 'Playfair Display', serif;
            font-size: 3.2rem;
            font-weight: 700;
            color: #1e293b;
            line-height: 1.1;
            margin-bottom: 20px;
            opacity: 0;
            animation: fadeInUp 1s ease-out 0.5s forwards;
        }
        
        .program-for {
            font-size: 20px;
            color: #475569;
            font-weight: 400;
            opacity: 0;
            animation: fadeInUp 1s ease-out 0.7s forwards;
        }
        
        .traveler-name {
            color: #059669;
            font-weight: 600;
            font-size: 22px;
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
        
        /* ========================================
           DETALLES DEL VIAJE
           ======================================== */
        .trip-details {
            margin-bottom: 50px;
            opacity: 0;
            animation: fadeInUp 1s ease-out 0.9s forwards;
        }
        
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .detail-item {
            background: rgba(255, 255, 255, 0.8);
            border-radius: 16px;
            padding: 24px 20px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }
        
        .detail-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            background: rgba(255, 255, 255, 0.9);
        }
        
        .detail-icon {
            font-size: 28px;
            color: #059669;
            margin-bottom: 12px;
        }
        
        .detail-value {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 6px;
        }
        
        .detail-label {
            font-size: 13px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 500;
        }
        
        /* ========================================
           RESUMEN DEL VIAJE
           ======================================== */
        .trip-summary {
            background: linear-gradient(135deg, 
                rgba(255, 255, 255, 0.9) 0%, 
                rgba(255, 255, 255, 0.7) 100%
            );
            border-radius: 20px;
            padding: 30px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            margin-bottom: 40px;
            backdrop-filter: blur(15px);
            opacity: 0;
            animation: fadeInUp 1s ease-out 1.1s forwards;
        }
        
        .summary-item {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 16px;
            font-size: 16px;
            color: #374151;
            font-weight: 500;
        }
        
        .summary-item:last-child {
            margin-bottom: 0;
        }
        
        .summary-item i {
            font-size: 20px;
            color: #059669;
            width: 24px;
            text-align: center;
        }
        
        /* ========================================
           BOTONES PRINCIPALES
           ======================================== */
        .discover-button {
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 20px 40px;
            font-size: 18px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.4s ease;
            box-shadow: 0 10px 30px rgba(5, 150, 105, 0.3);
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            width: 100%;
            opacity: 0;
            animation: fadeInUp 1s ease-out 1.3s forwards;
        }
        
        .discover-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, 
                transparent, 
                rgba(255, 255, 255, 0.2), 
                transparent
            );
            transition: left 0.5s;
        }
        
        .discover-button:hover::before {
            left: 100%;
        }
        
        .discover-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(5, 150, 105, 0.4);
            background: linear-gradient(135deg, #047857 0%, #0d9488 100%);
        }
        
        .discover-button:active {
            transform: translateY(-1px);
        }
        
        .discover-button i {
            font-size: 20px;
        }
        
        /* Botón para ver itinerario completo */
        /* Botón para ver itinerario completo - Diseño plano */
.itinerary-button {
    background: #374151;
    color: white;
    border: none;
    border-radius: 12px;
    padding: 16px 32px;
    font-size: 15px;
    font-weight: 500;
    text-transform: none;
    letter-spacing: 0.5px;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(55, 65, 81, 0.15);
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    width: 100%;
    margin-top: 15px;
    opacity: 0;
    animation: fadeInUp 1s ease-out 1.5s forwards;
}

.itinerary-button:hover {
    background: #4b5563;
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(55, 65, 81, 0.25);
}

.itinerary-button:active {
    transform: translateY(0);
    box-shadow: 0 1px 5px rgba(55, 65, 81, 0.2);
}

.itinerary-button i {
    font-size: 16px;
}
        
        .itinerary-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, 
                transparent, 
                rgba(255, 255, 255, 0.2), 
                transparent
            );
            transition: left 0.5s;
        }
        
        .itinerary-button:hover::before {
            left: 100%;
        }
        
        .itinerary-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(102, 126, 234, 0.4);
            background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
        }
        
        .itinerary-button:active {
            transform: translateY(-1px);
        }
        
        .itinerary-button i {
            font-size: 18px;
        }
        
        /* ========================================
           ÁREA DE CONTENIDO (resto de la pantalla)
           ======================================== */
        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 60px;
            position: relative;
        }
        
        /* Elementos decorativos flotantes */
        .floating-elements {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            overflow: hidden;
        }
        
        .floating-element {
            position: absolute;
            width: 8px;
            height: 8px;
            background: rgba(255, 255, 255, 0.6);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }
        
        .floating-element:nth-child(1) {
            top: 20%;
            left: 20%;
            animation-delay: 0s;
            animation-duration: 8s;
        }
        
        .floating-element:nth-child(2) {
            top: 60%;
            left: 80%;
            animation-delay: 2s;
            animation-duration: 10s;
        }
        
        .floating-element:nth-child(3) {
            top: 80%;
            left: 30%;
            animation-delay: 4s;
            animation-duration: 6s;
        }
        
        @keyframes float {
            0%, 100% { 
                transform: translateY(0px) rotate(0deg); 
                opacity: 0.5;
            }
            50% { 
                transform: translateY(-20px) rotate(180deg); 
                opacity: 1;
            }
        }
        
        /* ========================================
           RESPONSIVE DESIGN
           ======================================== */
        @media (max-width: 1024px) {
            .info-sidebar {
                width: 420px;
                padding: 50px 40px;
            }
            
            .program-title {
                font-size: 2.6rem;
            }
        }
        
        @media (max-width: 768px) {
            .preview-container {
                flex-direction: column;
            }
            
            .info-sidebar {
                width: 100%;
                min-height: 100vh;
                padding: 40px 30px;
                justify-content: center;
            }
            
            .main-content {
                display: none;
            }
            
            .program-title {
                font-size: 2.2rem;
            }
            
            .details-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .agency-logo {
                left: 30px;
                top: 20px;
                font-size: 12px;
            }
        }
        
        /* ========================================
           EFECTOS ADICIONALES
           ======================================== */
        
        /* Efecto de cristal para elementos */
        .glass-effect {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        /* Animación de pulso para elementos interactivos */
        .pulse-animation {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(5, 150, 105, 0.7);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(5, 150, 105, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(5, 150, 105, 0);
            }
        }
        
        /* ========================================
           PERSONALIZACIÓN POR TEMA
           ======================================== */
        .theme-tropical {
            --primary-color: #10b981;
            --secondary-color: #059669;
        }
        
        .theme-city {
            --primary-color: #3b82f6;
            --secondary-color: #1d4ed8;
        }
        
        .theme-adventure {
            --primary-color: #f59e0b;
            --secondary-color: #d97706;
        }
    </style>
</head>

<body>

<div class="translate-container">
        <div id="google_translate_element"></div>
    </div>
    <!-- Fondo con imagen y efecto zoom -->
    <div class="hero-background"></div>
    <div class="hero-overlay"></div>
    
    <!-- Container principal -->
    <div class="preview-container">
        <!-- Barra lateral con información -->
        <div class="info-sidebar">
            <!-- Logo de la agencia -->
            <div class="agency-logo">
                <i class="fas fa-plane"></i>
                <?= htmlspecialchars($company_name) ?>
            </div>
            
            <!-- Encabezado del programa -->
            <div class="program-header">
                <div class="program-subtitle">Mi viaje a</div>
                <h1 class="program-title"><?= htmlspecialchars($destino) ?></h1>
                <div class="program-for">
                    para <span class="traveler-name"><?= htmlspecialchars($nombre_viajero) ?></span>
                </div>
            </div>
            
            <!-- Detalles del viaje -->
            <div class="trip-details">
                <div class="details-grid">
                    <div class="detail-item">
                        <div class="detail-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="detail-value"><?= $duracion_dias ?></div>
                        <div class="detail-label"><?= $duracion_dias == 1 ? 'Noche' : 'Noches' ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="detail-value"><?= $num_pasajeros ?></div>
                        <div class="detail-label"><?= $num_pasajeros == 1 ? 'Viajero' : 'Viajeros' ?></div>
                    </div>
                </div>
                
                <?php if ($fecha_inicio_formatted && $fecha_fin_formatted): ?>
                <div class="details-grid">
                    <div class="detail-item">
                        <div class="detail-icon">
                            <i class="fas fa-plane-departure"></i>
                        </div>
                        <div class="detail-value" style="font-size: 16px;"><?= $fecha_inicio_formatted ?></div>
                        <div class="detail-label">Salida</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-icon">
                            <i class="fas fa-plane-arrival"></i>
                        </div>
                        <div class="detail-value" style="font-size: 16px;"><?= $fecha_fin_formatted ?></div>
                        <div class="detail-label">Regreso</div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Resumen del viaje -->
            <div class="trip-summary">
                <div class="summary-item">
                    <i class="fas fa-route"></i>
                    <span class="hero-title">Itinerario personalizado de <?= $duracion_dias ?> <?= $duracion_dias == 1 ? 'día' : 'días' ?></span>
                </div>
                <div class="summary-item">
                    <i class="fas fa-heart"></i>
                    <span>Diseñado especialmente para ti</span>
                </div>
                <div class="summary-item">
                    <i class="fas fa-shield-alt"></i>
                    <span>Viaje seguro e inolvidable</span>
                </div>

                <!------- Cambios por el selector de mostrar precio ---------->

                <?php
                $mostrar_precios = $precios && (!isset($precios['mostrar_precio']) || (int)$precios['mostrar_precio'] === 1);
                ?>
                <?php if ($mostrar_precios && !empty($precios['precio_adulto'])): ?>
                <div class="summary-item">
                    <i class="fas fa-tag"></i>
                    <span>Desde <?= number_format($precios['precio_adulto'], 0) ?> <?= $precios['moneda'] ?> por persona</span>
                </div>
                <?php endif; ?>
            </div>
            
            
            
            <!-- Nuevo botón para ver itinerario completo -->
            <button class="itinerary-button" onclick="verItinerarioCompleto()">
                <i class="fas fa-route"></i>
                Ver itinerario completo
            </button>
        </div>
        
        <!-- Área principal de contenido -->
        <div class="main-content">
            <!-- Elementos decorativos flotantes -->
            <div class="floating-elements">
                <div class="floating-element"></div>
                <div class="floating-element"></div>
                <div class="floating-element"></div>
            </div>
        </div>
    </div>
    
    <script>
        // ========================================
        // FUNCIONES JAVASCRIPT
        // ========================================
        
        // Función para abrir el programa en modo edición
        function abrirPrograma() {
            // Agregar efecto de clic
            const button = document.querySelector('.discover-button');
            button.style.transform = 'scale(0.95)';
            
            setTimeout(() => {
                window.location.href = '<?= APP_URL ?>/programa?id=<?= $programa_id ?>';
            }, 150);
        }
        
        // Nueva función para ver el itinerario completo
        function verItinerarioCompleto() {
            // Verificar si es acceso público
            const isPublic = new URLSearchParams(window.location.search).get('public') === '1';
            const programaId = '<?= $programa_id ?>';
            
            // Agregar efecto de clic
            const button = document.querySelector('.itinerary-button');
            button.style.transform = 'scale(0.95)';
            
            setTimeout(() => {
                if (isPublic) {
                    // Si es público, generar enlace de compartir para itinerary
                    const timestamp = Date.now();
                    const tokenData = `${programaId}_${timestamp}`;
                    const token = btoa(tokenData);
                    const itineraryUrl = `<?= APP_URL ?>/share?t=${token}&type=itinerary`;
                    window.location.href = itineraryUrl;
                } else {
                    // Si es acceso normal, abrir directamente
                    window.open(`<?= APP_URL ?>/itinerary?id=${programaId}`, '_blank');
                }
            }, 150);
        }
        
        // Efecto parallax sutil en el fondo
        let isScrolling = false;
        window.addEventListener('scroll', () => {
            if (!isScrolling) {
                window.requestAnimationFrame(() => {
                    const scrolled = window.pageYOffset;
                    const background = document.querySelector('.hero-background');
                    if (background) {
                        background.style.transform = `scale(1.08) translateY(${scrolled * 0.3}px)`;
                    }
                    isScrolling = false;
                });
                isScrolling = true;
            }
        });
        
        // Funciones para compartir en redes sociales
        function compartirWhatsApp() {
            const texto = encodeURIComponent(`¡Mira mi programa de viaje personalizado! ${document.title}`);
            const url = encodeURIComponent(window.location.href);
            window.open(`https://wa.me/?text=${texto} ${url}`, '_blank');
        }
        
        function compartirFacebook() {
            const url = encodeURIComponent(window.location.href);
            window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}`, '_blank');
        }
        
        function compartirTwitter() {
            const texto = encodeURIComponent(`¡Mira mi programa de viaje personalizado!`);
            const url = encodeURIComponent(window.location.href);
            window.open(`https://twitter.com/intent/tweet?text=${texto}&url=${url}`, '_blank');
        }
        
        // Copiar enlace al portapapeles
        function copiarEnlace() {
            navigator.clipboard.writeText(window.location.href).then(() => {
                mostrarNotificacion('Enlace copiado al portapapeles');
            });
        }
        
        // Mostrar notificación
        function mostrarNotificacion(mensaje) {
            const notificacion = document.createElement('div');
            notificacion.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #059669;
                color: white;
                padding: 12px 20px;
                border-radius: 8px;
                font-weight: 500;
                z-index: 10000;
                animation: slideInRight 0.3s ease;
            `;
            notificacion.textContent = mensaje;
            document.body.appendChild(notificacion);
            
            setTimeout(() => {
                notificacion.remove();
            }, 3000);
        }
        
        // Detectar dispositivo móvil
        function esMobile() {
            return window.innerWidth <= 768;
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            // Precargar imagen de fondo si no está cargada
            const img = new Image();
            img.src = '<?= addslashes($imagen_portada) ?>';
            
            // Añadir clase de cargado cuando todo esté listo
            window.addEventListener('load', () => {
                document.body.classList.add('loaded');
            });
        });
        
        
        console.log('🌟 Vista previa del programa cargada exitosamente');
        console.log('📍 Destino: <?= addslashes($destino) ?>');
        console.log('👤 Viajero: <?= addslashes($nombre_viajero) ?>');
        console.log('🆔 Programa ID: <?= $programa_id ?>');
    </script>
    
    <!-- Estilos CSS para animaciones adicionales -->
    <style>
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        /* Mejoras de carga */
        body:not(.loaded) .info-sidebar {
            opacity: 0;
        }
        
        body.loaded .info-sidebar {
            opacity: 1;
            transition: opacity 0.5s ease;
        }
        
        /* Efecto shine para el botón de itinerario */
        .itinerary-button::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
            pointer-events: none;
        }
        
        .itinerary-button:hover::after {
            left: 100%;
        }
        
        /* Responsive adicional para botones */
        @media (max-width: 480px) {
            .discover-button, .itinerary-button {
                padding: 16px 30px;
                font-size: 15px;
            }
            
            .program-title {
                font-size: 2rem;
            }
            
            .detail-value {
                font-size: 20px;
            }
            
            .summary-item {
                font-size: 14px;
            }
        }
    </style>
    <?php if ($is_public_access): ?>
<script>
// Limpiar sesión pública cuando se cierre la ventana
window.addEventListener('beforeunload', function() {
    fetch('<?= APP_URL ?>/api/clear_public_session.php');
});
</script>
<?php 
// Limpiar sesión después de mostrar la página
unset($_SESSION['public_programa_id']);
unset($_SESSION['is_public_access']);
endif; 
?>
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