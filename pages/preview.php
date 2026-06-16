<?php
// ====================================================================
// ARCHIVO: pages/preview.php - VISTA PREVIA LIMPIA CON MARCA DINÁMICA
// ====================================================================

require_once 'config/app.php';
require_once 'config/config_functions.php';

$is_public = isset($_GET['public']) && $_GET['public'] == '1';

if (!$is_public) {
    if (!App::isLoggedIn()) {
        header('Location: ' . APP_URL . '/login');
        exit;
    }
} else {
    unset($_SESSION['temp_public_access']);
}

$programa_id = $_GET['id'] ?? null;
$token = $_GET['token'] ?? null;

if (!$programa_id) {
    header('Location: ' . APP_URL . '/itinerarios');
    exit;
}
$is_sub = isset($_GET['sub']) && $_GET['sub'] == '1'
       && isset($_SESSION['subagencia_context'])
       && (int)($_SESSION['subagencia_context']['solicitud_id'] ?? 0) === (int)$programa_id;

// --------------------------------------------------------------------
// Helpers
// --------------------------------------------------------------------
function preview_config_value(array $keys, $fallback = '')
{
    foreach ($keys as $key) {
        try {
            if (class_exists('ConfigManager') && method_exists('ConfigManager', $key)) {
                $value = ConfigManager::$key();
                if ($value !== null && $value !== '')
                    return $value;
            }
        } catch (Throwable $e) {
            error_log("Preview ConfigManager::{$key}: " . $e->getMessage());
        }
    }
    return $fallback;
}

function preview_sanitize_hex($hex, string $fallback = '#1f2937'): string
{
    $hex = trim((string) $hex);
    if ($hex === '')
        return $fallback;
    if ($hex[0] !== '#')
        $hex = '#' . $hex;
    return preg_match('/^#[0-9a-fA-F]{6}$/', $hex) ? $hex : $fallback;
}

// Marca (nombre, logo, colores) de la agencia dueña del programa. Independiente de la
// sesión → funciona en acceso público. Prioriza la paleta de agente y cae a la de admin
// (igual que el renderer del PDF).
function preview_agency_brand($db, $agencia_id): array
{
    if (!$agencia_id) return [];
    try {
        $a = $db->fetch(
            "SELECT nombre, logo_url, agent_primary_color, admin_primary_color, agent_secondary_color, admin_secondary_color
             FROM agencias WHERE id = ? LIMIT 1",
            [(int) $agencia_id]
        );
    } catch (Throwable $e) {
        error_log('preview_agency_brand: ' . $e->getMessage());
        return [];
    }
    if (!$a) return [];
    return [
        'nombre'    => $a['nombre'] ?? '',
        'logo_url'  => $a['logo_url'] ?? '',
        'primary'   => $a['agent_primary_color'] ?: ($a['admin_primary_color'] ?? ''),
        'secondary' => $a['agent_secondary_color'] ?: ($a['admin_secondary_color'] ?? ''),
    ];
}

function preview_hex_to_rgb(string $hex): array
{
    $hex = ltrim(preview_sanitize_hex($hex), '#');
    return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
}

function preview_readable_text(string $hex): string
{
    [$r, $g, $b] = preview_hex_to_rgb($hex);
    $luminance = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
    return $luminance > 150 ? '#111827' : '#ffffff';
}

function preview_asset_url(?string $raw, string $fallback = ''): string
{
    $raw = trim((string) $raw);

    if ($raw === '') {
        return $fallback;
    }

    // Normaliza rutas tipo \assets\uploads\logo.png
    $raw = str_replace('\\', '/', $raw);

    // Si viene como ruta absoluta del servidor, intenta recortar desde /assets/
    $assetsPos = strpos($raw, '/assets/');
    if ($assetsPos !== false) {
        $raw = substr($raw, $assetsPos);
    }

    if (str_starts_with($raw, 'http://') || str_starts_with($raw, 'https://')) {
        $path = parse_url($raw, PHP_URL_PATH);

        if ($path) {
            $path = str_replace('\\', '/', $path);

            if (str_starts_with($path, '/assets/')) {
                return rtrim(APP_URL, '/') . $path;
            }
        }

        return $raw;
    }

    return rtrim(APP_URL, '/') . '/' . ltrim($raw, '/');
}

function preview_format_date(?string $date): string
{
    if (!$date)
        return '';
    try {
        $dt = new DateTime($date);
        $months = [
            '01' => 'ene',
            '02' => 'feb',
            '03' => 'mar',
            '04' => 'abr',
            '05' => 'may',
            '06' => 'jun',
            '07' => 'jul',
            '08' => 'ago',
            '09' => 'sep',
            '10' => 'oct',
            '11' => 'nov',
            '12' => 'dic'
        ];
        return $dt->format('d') . ' ' . $months[$dt->format('m')] . ' ' . $dt->format('Y');
    } catch (Throwable $e) {
        return '';
    }
}

try {
    ConfigManager::init();
} catch (Throwable $e) {
    error_log('Preview ConfigManager init: ' . $e->getMessage());
}

$company_name = preview_config_value(['getCompanyName', 'getNombreEmpresa'], 'Travel Soft');
$company_logo = preview_asset_url(preview_config_value(['getLogo', 'getCompanyLogo', 'getLogoUrl'], ''), '');

// Colores de marca. No se usan colores decorativos fijos: toda la interfaz toma estos valores.
$brand_primary = preview_sanitize_hex(preview_config_value([
    'getPrimaryColor',
    'getColorPrimario',
    'getBrandColor',
    'getMainColor',
    'getColorPrincipal'
], '#1f2937'));

$brand_secondary = preview_sanitize_hex(preview_config_value([
    'getSecondaryColor',
    'getColorSecundario',
    'getAccentColor',
    'getColorAcento'
], $brand_primary), $brand_primary);

$brand_text = preview_readable_text($brand_primary);
[$brand_r, $brand_g, $brand_b] = preview_hex_to_rgb($brand_primary);
[$brand2_r, $brand2_g, $brand2_b] = preview_hex_to_rgb($brand_secondary);

// --------------------------------------------------------------------
// Cargar datos del programa
// --------------------------------------------------------------------
try {
    $db = Database::getInstance();

    $programa = $db->fetch(
        "SELECT ps.*, pp.titulo_programa, pp.foto_portada, pp.idioma_predeterminado,
                (SELECT COUNT(*) FROM viajeros_solicitud vs WHERE vs.solicitud_id = ps.id) as viajeros_count
         FROM programa_solicitudes ps
         LEFT JOIN programa_personalizacion pp ON ps.id = pp.solicitud_id
         WHERE ps.id = ?",
        [$programa_id]
    );

    if (!$programa) {
        throw new Exception('Programa no encontrado');
    }

    $dias = $db->fetchAll(
        "SELECT * FROM programa_dias WHERE solicitud_id = ? ORDER BY dia_numero ASC",
        [$programa_id]
    );

    $precios = $db->fetch(
        "SELECT * FROM programa_precios WHERE solicitud_id = ?",
        [$programa_id]
    );

    $sub_config  = null;
    $sub_precios = null;

    if ($is_sub) {
        $subCtx = $_SESSION['subagencia_context'];
        $sub_config = $db->fetch(
            "SELECT nombre, logo_url, primary_color, secondary_color, divisa
             FROM config_sub_agencias WHERE user_id = ?",
            [(int)$subCtx['user_id']]
        );
        $sub_precios = $db->fetch(
            "SELECT * FROM subagencia_tour_precios WHERE user_id = ? AND solicitud_id = ?",
            [(int)$subCtx['user_id'], (int)$programa_id]
        );
    }

} catch (Exception $e) {
    error_log('Error cargando programa para preview: ' . $e->getMessage());
    header('Location: ' . APP_URL . '/itinerarios');
    exit;
}

// Marca real desde la agencia dueña del programa (programa_solicitudes.agencia_id).
// Antes la marca venía de ConfigManager, que solo funciona con sesión de login → en el
// link público (sin sesión) caía al branding por defecto. Esto lo corrige.
$agencyBrand = preview_agency_brand($db, $programa['agencia_id'] ?? null);
if (!empty($agencyBrand['nombre']))   { $company_name = $agencyBrand['nombre']; }
if (!empty($agencyBrand['logo_url'])) { $company_logo = preview_asset_url($agencyBrand['logo_url'], $company_logo); }
if (!empty($agencyBrand['primary'])) {
    $brand_primary    = preview_sanitize_hex($agencyBrand['primary'], $brand_primary);
    $brand_secondary  = preview_sanitize_hex($agencyBrand['secondary'] ?? '', $brand_primary);
    $brand_text       = preview_readable_text($brand_primary);
    [$brand_r, $brand_g, $brand_b]    = preview_hex_to_rgb($brand_primary);
    [$brand2_r, $brand2_g, $brand2_b] = preview_hex_to_rgb($brand_secondary);
}

if ($is_sub && $sub_config) {
    if (!empty($sub_config['nombre']))
        $company_name = $sub_config['nombre'];
    if (!empty($sub_config['logo_url']))
        $company_logo = preview_asset_url($sub_config['logo_url']);
    if (!empty($sub_config['primary_color'])) {
        $brand_primary   = preview_sanitize_hex($sub_config['primary_color']);
        $brand_secondary = preview_sanitize_hex($sub_config['secondary_color'] ?? $brand_primary, $brand_primary);
        $brand_text      = preview_readable_text($brand_primary);
        [$brand_r, $brand_g, $brand_b]   = preview_hex_to_rgb($brand_primary);
        [$brand2_r, $brand2_g, $brand2_b] = preview_hex_to_rgb($brand_secondary);
    }
}

if ($is_sub && $sub_precios) {
    $precios = array_merge($precios ?? [], [
        'precio_adulto'         => $sub_precios['precio_adulto'],
        'precio_nino'           => $sub_precios['precio_nino'],
        'precio_total'          => $sub_precios['precio_total'],
        'moneda'                => $sub_config['divisa']               ?? ($precios['moneda'] ?? ''),
        'precio_incluye'        => $sub_precios['precio_incluye'],
        'precio_no_incluye'     => $sub_precios['precio_no_incluye'],
        'condiciones_generales' => $sub_precios['condiciones_generales'],
        'movilidad_reducida'    => $sub_precios['movilidad_reducida'],
        'info_pasaporte'        => $sub_precios['info_pasaporte'],
        'info_seguros'          => $sub_precios['info_seguros'],
        // La subagencia decide de forma independiente; si no eligió (NULL/columna ausente)
        // hereda el ajuste del tour principal.
        'mostrar_precio'        => (!array_key_exists('mostrar_precio', $sub_precios) || $sub_precios['mostrar_precio'] === null)
            ? ($precios['mostrar_precio'] ?? 1)
            : (int) $sub_precios['mostrar_precio'],
    ]);
    // Nombre del cliente personalizado por la subagencia
    if (!empty($sub_precios['nombre_cliente'])) {
        $programa['nombre']   = $sub_precios['nombre_cliente'];
        $programa['apellido'] = '';
    }
}

$duracion_dias = 0;
$num_noches = 0;
foreach ($dias as $dia) {
    $duracion_estancia = intval($dia['duracion_estancia'] ?? 1) ?: 1;
    $duracion_dias += $duracion_estancia;
}
if ($duracion_dias === 0) {
    $duracion_dias = count($dias);
}
$num_noches = $duracion_dias - 1;

$destino = $programa['destino'] ?? 'tu destino';
$titulo_programa = $programa['titulo_programa'] ?: 'Mi viaje a ' . $destino;
$nombre_viajero = trim(
    ($programa['nombre'] ?? $programa['nombre_viajero'] ?? '') . ' ' .
    ($programa['apellido'] ?? $programa['apellido_viajero'] ?? '')
);
if ($nombre_viajero === '') {
    $nombre_viajero = 'tu viaje';
}

$imagen_portada = preview_asset_url(
    $programa['foto_portada'] ?? '',
    APP_URL . '/assets/images/default-travel.jpg'
);

$num_pasajeros = (int) ($programa['numero_pasajeros'] ?? 1);
if ($num_pasajeros <= 0)
    $num_pasajeros = 1;

$fecha_inicio_formatted = preview_format_date($programa['fecha_llegada'] ?? null);
$fecha_fin_formatted = '';
if (!empty($programa['fecha_llegada'])) {
    try {
        $fecha_fin = new DateTime($programa['fecha_llegada']);
        $fecha_fin->add(new DateInterval('P' . max($num_noches, 1) . 'D'));
        $fecha_fin_formatted = preview_format_date($fecha_fin->format('Y-m-d'));
    } catch (Throwable $e) {
        $fecha_fin_formatted = '';
    }
}

$mostrar_precios = $precios && (!isset($precios['mostrar_precio']) || (int) $precios['mostrar_precio'] === 1);
$precio_adulto = $precios['precio_adulto'] ?? null;
$moneda = $precios['moneda'] ?? '';
$vendido = ($programa['comprado']) ? 1 : 0;
$share_token = md5($programa_id . 'travel_preview_' . date('Y-m-d'));
$share_url = APP_URL . '/preview?id=' . urlencode((string) $programa_id) . '&token=' . $share_token;
$is_public_access = $is_public_access ?? false;
$idioma = $programa['idioma_predeterminado'] ?? 'es';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <title><?= htmlspecialchars($titulo_programa) ?> - Vista Previa</title>

    <meta property="og:title" content="<?= htmlspecialchars($titulo_programa) ?>">
    <meta property="og:description"
        content="Programa de viaje personalizado para <?= htmlspecialchars($nombre_viajero) ?> a <?= htmlspecialchars($destino) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($imagen_portada) ?>">
    <meta property="og:url" content="<?= htmlspecialchars($share_url) ?>">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($titulo_programa) ?>">
    <meta name="twitter:description" content="Programa de viaje personalizado">
    <meta name="twitter:image" content="<?= htmlspecialchars($imagen_portada) ?>">
    <meta name="theme-color" content="<?= htmlspecialchars($brand_primary) ?>">

    <script type="text/javascript">
        function googleTranslateElementInit() {
            new google.translate.TranslateElement({
                pageLanguage: '<?= htmlspecialchars($idioma) ?>',
                includedLanguages: 'en,fr,pt,it,de,es',
                layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
                autoDisplay: false
            }, 'google_translate_element');
        }
    </script>

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700;800&display=swap"
        rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --brand-primary:
                <?= htmlspecialchars($brand_primary) ?>
            ;
            --brand-secondary:
                <?= htmlspecialchars($brand_secondary) ?>
            ;
            --brand-text:
                <?= htmlspecialchars($brand_text) ?>
            ;
            --brand-rgb:
                <?= $brand_r ?>
                ,
                <?= $brand_g ?>
                ,
                <?= $brand_b ?>
            ;
            --brand-secondary-rgb:
                <?= $brand2_r ?>
                ,
                <?= $brand2_g ?>
                ,
                <?= $brand2_b ?>
            ;
            --surface: rgba(255, 255, 255, 0.94);
            --surface-soft: rgba(255, 255, 255, 0.72);
            --text-main: #1f2937;
            --text-soft: #64748b;
            --border-soft: rgba(var(--brand-rgb), 0.14);
            --shadow-soft: 0 24px 70px rgba(var(--brand-rgb), 0.18);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html,
        body {
            min-height: 100%;
        }

        body {
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
            background: var(--brand-primary);
            overflow-x: hidden;
            top: 0 !important;
        }

        .page {
            height: 100vh;
            overflow: hidden;
            display: grid;
            grid-template-columns: minmax(360px, 430px) 1fr;
            background:
                linear-gradient(rgba(15, 23, 42, 0.30), rgba(15, 23, 42, 0.30)),
                url('<?= addslashes($imagen_portada) ?>') center / cover no-repeat;
        }

        .panel {
            height: 100vh;
            padding: 34px 38px;
            background: var(--surface);
            border-right: 1px solid var(--border-soft);
            box-shadow: var(--shadow-soft);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 18px;
            overflow: hidden;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            min-height: 42px;
            flex-shrink: 0;
        }

        .brand-logo {
            max-width: 150px;
            max-height: 46px;
            object-fit: contain;
            object-position: left center;
            display: block;
        }

        .brand-fallback {
            width: 38px;
            height: 38px;
            border-radius: 999px;
            background: var(--brand-primary);
            color: var(--brand-text);
            display: grid;
            place-items: center;
            font-weight: 800;
            letter-spacing: -0.04em;
        }

        .brand-name {
            font-size: 13px;
            font-weight: 700;
            color: var(--text-soft);
            letter-spacing: 0.02em;
            line-height: 1.2;
        }

        .content {
            display: grid;
            gap: 16px;
            /* Absorbe el sobrante: si el contenido (título/nombre muy largos) no cabe,
               el scroll ocurre AQUÍ dentro y el botón de abajo nunca se recorta ni se va de pantalla. */
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
        }

        .eyebrow {
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: var(--brand-primary);
            margin-bottom: 10px;
        }

        .title {
            font-family: 'Playfair Display', serif;
            font-size: clamp(24px, 4vw, 35px);
            line-height: 1;
            letter-spacing: -0.05em;
            color: var(--text-main);
            margin-bottom: 10px;
            /* Tope de líneas para que un destino larguísimo no empuje el resto del panel */
            display: -webkit-box;
            -webkit-line-clamp: 4;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .traveler {
            font-size: 25px;
            line-height: 1.5;
            color: var(--text-soft);
            font-weight: 600;
        }

        .traveler strong {
            color: var(--text-main);
            font-weight: 800;
        }

        .divider {
            width: 64px;
            height: 1px;
            background: var(--brand-primary);
            opacity: 0.38;
        }

        .intro {
            font-size: 14.5px;
            line-height: 1.5;
            color: var(--text-soft);
            font-weight: 600;
            max-width: 320px;
            margin: 0;
        }

        .facts {
            display: grid;
            gap: 10px;
        }

        .fact,
        .summary-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 13px;
            border: 1px solid var(--border-soft);
            border-radius: 14px;
            background: var(--surface-soft);
        }

        .fact i,
        .summary-item i {
            width: 20px;
            color: var(--brand-primary);
            text-align: center;
        }

        .fact span,
        .summary-item span {
            font-size: 14px;
            line-height: 1.35;
            color: var(--text-main);
            font-weight: 650;
        }

        .actions {
            display: grid;
            gap: 12px;
            flex-shrink: 0;
        }

        .primary-button,
        .secondary-button {
            width: 100%;
            border: 0;
            border-radius: 16px;
            padding: 16px 18px;
            font-family: inherit;
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
            transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .primary-button {
            background: var(--brand-primary);
            color: var(--brand-text);
            box-shadow: 0 16px 34px rgba(var(--brand-rgb), 0.26);
        }

        .secondary-button {
            background: rgba(var(--brand-rgb), 0.08);
            color: var(--brand-primary);
            border: 1px solid var(--border-soft);
        }

        .primary-button:hover,
        .secondary-button:hover {
            transform: translateY(-1px);
        }

        .primary-button:active,
        .secondary-button:active {
            transform: translateY(0) scale(0.99);
        }

        .visual {
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px;
        }

        .center-mark {
            color: rgba(255, 255, 255, 0.92);
            text-align: center;
            text-shadow: 0 18px 55px rgba(var(--brand-rgb), 0.42);
            max-width: 620px;
        }

        .center-mark h2 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(42px, 7vw, 96px);
            line-height: 0.95;
            letter-spacing: -0.055em;
            font-weight: 800;
        }

        .center-mark p {
            margin-top: 18px;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            opacity: 0.88;
        }

        .translate-container {
            position: fixed;
            top: 18px;
            right: 18px;
            z-index: 9999;
        }

        #google_translate_element {
            background: var(--surface);
            border: 1px solid var(--border-soft);
            border-radius: 999px;
            padding: 8px 12px;
            box-shadow: 0 12px 30px rgba(var(--brand-rgb), 0.16);
            backdrop-filter: blur(14px);
        }

        .goog-te-gadget-icon,
        .VIpgJd-ZVi9od-xl07Ob-lTBxed img,
        .VIpgJd-ZVi9od-xl07Ob-lTBxed span[style*="border-left"],
        .VIpgJd-ZVi9od-ORHb-OEVmcd,
        .goog-te-banner-frame.skiptranslate {
            display: none !important;
        }

        .goog-te-gadget-simple {
            background: transparent !important;
            border: none !important;
            font-family: 'Inter', sans-serif !important;
        }

        .VIpgJd-ZVi9od-xl07Ob-lTBxed {
            color: var(--brand-primary) !important;
            text-decoration: none !important;
            font-family: inherit !important;
            font-size: 13px !important;
            font-weight: 800 !important;
        }

        @media (max-width: 900px) {

            /* En móvil la portada respira y puede hacer scroll si el contenido no cabe (ya no se recorta) */
            .page {
                grid-template-columns: 1fr;
                height: auto;
                min-height: 100vh;
                min-height: 100svh;
                overflow: visible;
                background:
                    linear-gradient(180deg, rgba(15, 23, 42, 0.45), rgba(15, 23, 42, 0.18)),
                    url('<?= addslashes($imagen_portada) ?>') center / cover no-repeat;
            }

            .panel {
                height: auto;
                min-height: 100vh;
                min-height: 100svh;
                overflow: visible;
                width: min(100%, 560px);
                margin: 0;
                padding: 34px 26px;
                gap: 24px;
            }

            /* En móvil el contenido fluye con el scroll natural de la página, sin scroll interno */
            .content {
                overflow: visible;
                min-height: auto;
            }

            .visual {
                display: none;
            }

            .title {
                font-size: clamp(42px, 14vw, 64px);
            }

            .translate-container {
                top: 14px;
                right: 14px;
            }
        }

        @media (max-width: 480px) {
            .panel {
                padding: 26px 20px 30px;
                gap: 20px;
            }

            .title {
                font-size: clamp(38px, 13vw, 52px);
            }

            .traveler {
                font-size: 20px;
            }

            .intro {
                max-width: 100%;
                font-size: 14px;
            }

            .fact,
            .summary-item {
                padding: 11px 13px;
            }

            .primary-button,
            .secondary-button {
                padding: 15px 16px;
            }

            #google_translate_element {
                padding: 6px 10px;
            }
        }
    </style>
</head>

<body>
    <div class="translate-container">
        <div id="google_translate_element"></div>
    </div>

    <main class="page">
        <aside class="panel">
            <div class="brand">
                <?php if ($company_logo): ?>
                    <img class="brand-logo" src="<?= htmlspecialchars($company_logo) ?>"
                        alt="<?= htmlspecialchars($company_name) ?>"
                        onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';">
                    <div class="brand-fallback" style="display:none;">
                        <?= htmlspecialchars(mb_strtoupper(mb_substr($company_name, 0, 1))) ?>
                    </div>
                <?php else: ?>
                    <div class="brand-fallback"><?= htmlspecialchars(mb_strtoupper(mb_substr($company_name, 0, 1))) ?></div>
                    <div class="brand-name"><?= htmlspecialchars($company_name) ?></div>
                <?php endif; ?>
            </div>

            <section class="content">
                <div>
                    <div class="eyebrow">Mi viaje a</div>
                    <h1 class="title"><?= htmlspecialchars($destino) ?></h1>
                    <p class="traveler">Preparado para <strong><?= htmlspecialchars($nombre_viajero) ?></strong></p>
                </div>

                <div class="divider"></div>

                <p class="intro">Un itinerario a medida, claro y pensado para disfrutar tu viaje de principio a fin.</p>

                <div class="facts">
                    <div class="fact">
                        <i class="fas fa-calendar-days"></i>
                        <span><?= $duracion_dias ?> <?= $duracion_dias == 1 ? 'día' : 'días' ?> / <?= $num_noches ?>
                            <?= $num_noches == 1 ? 'noche' : 'noches' ?></span>
                    </div>

                    <div class="fact">
                        <i class="fas fa-user-group"></i>
                        <span><?= $num_pasajeros ?> <?= $num_pasajeros == 1 ? 'viajero' : 'viajeros' ?></span>
                    </div>

                    <?php if ($fecha_inicio_formatted && $fecha_fin_formatted): ?>
                        <div class="fact">
                            <i class="fas fa-plane-departure"></i>
                            <span><?= htmlspecialchars($fecha_inicio_formatted) ?> —
                                <?= htmlspecialchars($fecha_fin_formatted) ?></span>
                        </div>
                    <?php endif; ?>


                    <?php if (!$vendido): ?>

                        <?php if ($mostrar_precios && !empty($precios['precio_adulto'])): ?>
                            <div class="summary-item">
                                <i class="fas fa-tag"></i>
                                <span>Desde <?= number_format($precios['precio_adulto'], 0) ?>         <?= $precios['moneda'] ?> por
                                    persona</span>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                </div>
            </section>

            <div class="actions">
                <button class="primary-button" type="button" onclick="verItinerarioCompleto()">
                    Ver itinerario completo
                    <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </aside>

        <section class="visual" aria-hidden="true">
            <div class="center-mark">
                <h2><?= htmlspecialchars($company_name) ?></h2>
                <p><?= htmlspecialchars($titulo_programa) ?></p>
            </div>
        </section>
    </main>

    <script>
        function abrirPrograma() {
            window.location.href = '<?= APP_URL ?>/programa?id=<?= urlencode((string) $programa_id) ?>';
        }

        function verItinerarioCompleto() {
            const isPublic = new URLSearchParams(window.location.search).get('public') === '1';
            const programaId = '<?= addslashes((string) $programa_id) ?>';
            const isSub = <?= $is_sub ? 'true' : 'false' ?>;

            if (isSub) {
                // Contexto de subagencia: ir directo conservando la sesión y el flag sub=1.
                // (Pasar por /share con token base64 borraría subagencia_context.)
                window.location.href = `<?= APP_URL ?>/itinerary?id=${programaId}&public=1&sub=1`;
            } else if (isPublic) {
                const timestamp = Date.now();
                const tokenData = `${programaId}_${timestamp}`;
                const token = btoa(tokenData);
                window.location.href = `<?= APP_URL ?>/share?t=${token}&type=itinerary`;
            } else {
                window.open(`<?= APP_URL ?>/itinerary?id=${programaId}`, '_blank');
            }
        }

        function compartirWhatsApp() {
            const texto = encodeURIComponent(`Mira mi programa de viaje personalizado: ${document.title}`);
            const url = encodeURIComponent(window.location.href);
            window.open(`https://wa.me/?text=${texto} ${url}`, '_blank');
        }

        function compartirFacebook() {
            const url = encodeURIComponent(window.location.href);
            window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}`, '_blank');
        }

        function compartirTwitter() {
            const texto = encodeURIComponent('Mira mi programa de viaje personalizado');
            const url = encodeURIComponent(window.location.href);
            window.open(`https://twitter.com/intent/tweet?text=${texto}&url=${url}`, '_blank');
        }

        function copiarEnlace() {
            navigator.clipboard.writeText(window.location.href).then(() => {
                mostrarNotificacion('Enlace copiado');
            });
        }

        function mostrarNotificacion(mensaje) {
            const notificacion = document.createElement('div');
            notificacion.style.cssText = `
                position: fixed;
                top: 20px;
                left: 50%;
                transform: translateX(-50%);
                background: var(--brand-primary);
                color: var(--brand-text);
                padding: 11px 16px;
                border-radius: 999px;
                font: 700 13px Inter, sans-serif;
                z-index: 10000;
                box-shadow: 0 12px 30px rgba(var(--brand-rgb), .22);
            `;
            notificacion.textContent = mensaje;
            document.body.appendChild(notificacion);
            setTimeout(() => notificacion.remove(), 2600);
        }

        document.addEventListener('DOMContentLoaded', function () {
            const img = new Image();
            img.src = '<?= addslashes($imagen_portada) ?>';

            setTimeout(() => {
                const savedLang = sessionStorage.getItem('language') ||
                    localStorage.getItem('preferredLanguage') ||
                    '<?= addslashes($idioma) ?>';

                if (savedLang && savedLang !== '<?= addslashes($idioma) ?>') {
                    const select = document.querySelector('.goog-te-combo');
                    if (select) {
                        select.value = savedLang;
                        select.dispatchEvent(new Event('change'));
                    }
                }
            }, 1000);

            setTimeout(() => {
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

    <?php if ($is_public_access): ?>
        <script>
            window.addEventListener('beforeunload', function () {
                fetch('<?= APP_URL ?>/api/clear_public_session.php');
            });
        </script>
        <?php
        unset($_SESSION['public_programa_id']);
        unset($_SESSION['is_public_access']);
    endif;
    ?>

    <script src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
</body>

</html>