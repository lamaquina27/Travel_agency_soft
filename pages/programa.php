<?php
// ====================================================================
// ARCHIVO: pages/programa.php - REESTRUCTURADO CON PESTAÑAS
// ====================================================================

require_once 'config/app.php';
require_once 'config/config_functions.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/ui_components.php';


App::init();
App::requireLogin();

$user = App::getUser(); // MOVER ANTES

ConfigManager::init();
$userColors = ConfigManager::getColorsForRole($user['role']);
$companyName = ConfigManager::getCompanyName();
$logo = ConfigManager::getLogo();
$defaultLanguage = ConfigManager::getDefaultLanguage();


$is_editing = isset($_GET['id']) && !empty($_GET['id']);
$programa_id = $is_editing ? intval($_GET['id']) : null;

// Cargar datos si está editando
$form_data = [
    'traveler_name' => '',
    'traveler_lastname' => '',
    'destination' => '',
    'arrival_date' => '',
    'departure_date' => '',
    'passengers' => 1,
    'accompaniment' => 'sin-acompanamiento',
    'program_title' => '',
    'language' => 'es',
    'request_id' => '',
    'cover_image' => '',
    'titular_id' => null,
    'viajeros' => [],
    'viajeros_ids' => [],
    'comprado' => 0
];

if ($is_editing) {
    try {
        $db = Database::getInstance();
        $programa_data = $db->fetch(
            "SELECT * FROM programa_solicitudes WHERE id = ? AND user_id = ?",
            [$programa_id, $user['id']]
        );

        if (!$programa_data) {
            header('Location: ' . APP_URL . '/itinerarios');
            exit;
        }

        $personalizacion_data = $db->fetch(
            "SELECT * FROM programa_personalizacion WHERE solicitud_id = ?",
            [$programa_id]
        );

        $viajeros_asociados = $db->fetchAll(
            "SELECT v.*
            FROM viajeros_solicitud vs
            INNER JOIN viajeros v ON vs.viajero_id = v.id
            WHERE vs.solicitud_id = ?
            ORDER BY v.nombre ASC, v.apellido ASC",
            [$programa_id]
        );

        $titular_data = null;

        if (!empty($programa_data['titular_id'])) {
            $titular_data = $db->fetch(
                "SELECT *
                FROM viajeros
                WHERE id = ?",
                [$programa_data['titular_id']]
            );
        }

        $viajeros_programa = $db->fetchAll(
            "SELECT v.*
            FROM viajeros_solicitud vs
            INNER JOIN viajeros v ON vs.viajero_id = v.id
            WHERE vs.solicitud_id = ?
            ORDER BY v.nombre ASC, v.apellido ASC",
            [$programa_id]
        );

        $form_data = [
            'traveler_name' => $programa_data['nombre'] ?? $programa_data['nombre_viajero'] ?? '',
            'traveler_lastname' => $programa_data['apellido'] ?? $programa_data['apellido_viajero'] ?? '',
            'destination' => $programa_data['destino'] ?? '',
            'arrival_date' => !empty($programa_data['fecha_llegada']) ? substr($programa_data['fecha_llegada'], 0, 10) : '',
            'departure_date' => $programa_data['fecha_salida'] ?? '',
            'passengers' => $programa_data['numero_pasajeros'] ?? 1,
            'accompaniment' => $programa_data['acompanamiento'] ?? 'sin-acompanamiento',
            'program_title' => $personalizacion_data['titulo_programa'] ?? '',
            'language' => $personalizacion_data['idioma_predeterminado'] ?? 'es',
            'request_id' => $programa_data['id_solicitud'] ?? '',
            'cover_image' => $personalizacion_data['foto_portada'] ?? '',
            'titular_id' => $programa_data['titular_id'] ?? null,
            'titular_data' => $titular_data,
            'viajeros_asociados' => $viajeros_asociados,
            'viajeros_ids' => array_column($viajeros_asociados, 'id'),
            'comprado' => $programa_data['comprado'] ?? 0
        ];
    } catch (Exception $e) {
        error_log("Error cargando programa: " . $e->getMessage());
        header('Location: ' . APP_URL . '/itinerarios');
        exit;
    }
}

$page_title = $is_editing ? 'Editar Programa' : 'Nuevo Programa';
?>

<!DOCTYPE html>
<html lang="<?= $defaultLanguage ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Programa - <?= htmlspecialchars($companyName) ?></title>
    <?= UIComponents::getComponentStyles() ?>

    <!-- CSS Framework y estilos -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/assets/css/dashboard.css" rel="stylesheet">

    <script>
        const APP_URL = '<?= APP_URL ?>';
    </script>

    <script src="<?= APP_URL ?>/assets/js/ubicacion-search-widget.js"></script>
    <script src="<?= APP_URL ?>/assets/js/api-connections.js"></script>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/css/intlTelInput.css">
    <script src="https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/js/intlTelInput.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/js/utils.js"></script>

    <style>
        :root {
            --primary-color:
                <?= $userColors['primary'] ?>
            ;
            --secondary-color:
                <?= $userColors['secondary'] ?>
            ;
            --primary-gradient: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        }

        /* ============================================================
   CSS PARA ALTERNATIVAS - AGREGAR AL <style> DE programa.php
   ============================================================ */
        /* Botón compartir enlace - Estilo minimalista */
        .nav-button[onclick*="compartirEnlace"],
        .nav-button[onclick*="abrirMiBiblioteca"],
        .nav-button[onclick*="abrirBonoReservaPrograma"] {
            background: rgba(107, 114, 128, 0.08) !important;
            color: #374151 !important;
            border: none !important;
            border-radius: 8px !important;
            padding: 12px 20px !important;
            font-size: 13px !important;
            font-weight: 500 !important;
            transition: all 0.15s ease !important;
            box-shadow: none !important;
            letter-spacing: 0.3px !important;
            text-transform: none !important;
            margin-left: 15px !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 8px !important;
        }

        .nav-button[onclick*="compartirEnlace"]:hover,
        .nav-button[onclick*="abrirMiBiblioteca"]:hover,
        .nav-button[onclick*="abrirBonoReservaPrograma"]:hover {
            background: rgba(107, 114, 128, 0.12) !important;
            color: #1f2937 !important;
            transform: translateY(-0.5px) !important;
            box-shadow: 0 2px 8px rgba(107, 114, 128, 0.15) !important;
        }

        .nav-button[onclick*="compartirEnlace"]:active,
        .nav-button[onclick*="abrirMiBiblioteca"]:active,
        .nav-button[onclick*="abrirBonoReservaPrograma"]:active {
            transform: translateY(0) !important;
            background: rgba(107, 114, 128, 0.15) !important;
        }

        .nav-button[onclick*="compartirEnlace"] i,
        .nav-button[onclick*="abrirMiBiblioteca"] i,
        .nav-button[onclick*="abrirBonoReservaPrograma"] i {
            color: inherit !important;
            font-size: 12px !important;
        }

        .nav-button[onclick*="compartirEnlace"] span,
        .nav-button[onclick*="abrirMiBiblioteca"] span,
        .nav-button[onclick*="abrirBonoReservaPrograma"] span {
            color: inherit !important;
        }

        /* Responsive para el botón */
        @media (max-width: 768px) {
            .nav-button[onclick*="compartirEnlace"] {
                padding: 10px 16px !important;
                font-size: 12px !important;
                margin-left: 10px !important;
            }

            .nav-button[onclick*="compartirEnlace"] span {
                display: none !important;
            }

            .nav-button[onclick*="compartirEnlace"] i {
                margin-right: 0 !important;
            }

            .nav-button[onclick*="abrirMiBiblioteca"] {
                padding: 10px 16px !important;
                font-size: 12px !important;
                margin-left: 10px !important;
            }

            .nav-button[onclick*="abrirMiBiblioteca"] span {
                display: none !important;
            }

            .nav-button[onclick*="abrirMiBiblioteca"] i {
                margin-right: 0 !important;
            }


            .nav-button[onclick*="abrirBonoReservaPrograma"] {
                padding: 10px 16px !important;
                font-size: 12px !important;
                margin-left: 10px !important;
            }

            .nav-button[onclick*="abrirBonoReservaPrograma"] span {
                display: none !important;
            }

            .nav-button[onclick*="abrirBonoReservaPrograma"] i {
                margin-right: 0 !important;
            }
        }

        /* Grupo de servicios con alternativas */
        .service-group {
            margin-bottom: 16px;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            overflow: hidden;
            background: white;
            transition: all 0.3s ease;
        }

        .service-group:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-color: var(--primary-color);
        }

        /* Servicio principal */
        .service-item.principal {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border-bottom: 2px solid #e0e0e0;
            position: relative;
        }

        .service-item.principal::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        }

        /* Container de alternativas */
        .alternatives-container {
            background: #fafbfc;
            border-top: 1px solid #e9ecef;
        }

        /* Alternativas */
        .service-item.alternativa {
            background: #fafbfc;
            border-bottom: 1px solid #e9ecef;
            position: relative;
            margin-left: 20px;
            margin-right: 0;
        }

        .service-item.alternativa:last-child {
            border-bottom: none;
        }

        .service-item.alternativa::before {
            content: '';
            position: absolute;
            left: -20px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(135deg, var(--secondary-color) 0%, var(--secondary-color) 100%);
        }

        /* Conector visual para alternativas */
        .alternative-connector {
            position: absolute;
            left: -20px;
            top: 20px;
            width: 20px;
            height: 2px;
            background: linear-gradient(90deg, var(--secondary-color) 0%, var(--secondary-color) 100%);
        }

        .alternative-connector::before {
            content: '';
            position: absolute;
            right: -4px;
            top: -2px;
            width: 6px;
            height: 6px;
            background: var(--secondary-color);
            border-radius: 50%;
        }

        /* ============================================================
   CONTROLES DE ESTANCIA - DISEÑO MODERNO
   ============================================================ */

        /* Controles en sidebar - Compacto y elegante */
        .day-controls {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-left: auto;
            background: rgba(255, 255, 255, 0.1);
            padding: 4px 6px;
            border-radius: 12px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .estancia-btn {
            color: white;
            border: none;
            width: 24px;
            height: 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 12px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        /* Botón MÁS - Verde */
        .estancia-btn[onclick*="+ 1"] {
            background: #48bb78;
            box-shadow: 0 2px 8px rgba(72, 187, 120, 0.3);
        }

        .estancia-btn[onclick*="+ 1"]:hover:not(:disabled) {
            background: #38a169;
            box-shadow: 0 4px 15px rgba(72, 187, 120, 0.4);
        }

        /* Botón MENOS - Rojo suave */
        .estancia-btn[onclick*="- 1"] {
            background: #f56565;
            box-shadow: 0 2px 8px rgba(245, 101, 101, 0.3);
        }

        .estancia-btn[onclick*="- 1"]:hover:not(:disabled) {
            background: #e53e3e;
            box-shadow: 0 4px 15px rgba(245, 101, 101, 0.4);
        }
        }

        .estancia-btn:disabled {
            background: linear-gradient(135deg, #e0e0e0 0%, #bdbdbd 100%);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
            opacity: 0.6;
        }

        .estancia-display {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            color: #2c3e50;
            padding: 4px 8px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 11px;
            min-width: 24px;
            text-align: center;
            border: 2px solid rgba(102, 126, 234, 0.2);
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .estancia-display::after {
            content: attr(data-suffix);
            font-size: 9px;
            color: #6c757d;
            margin-left: 2px;
        }

        /* Controles en detalle - Más prominente */
        .day-controls-detail {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin: 15px 0;
            padding: 16px 20px;
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border-radius: 16px;
            border: 2px solid #e9ecef;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            max-width: 50%;
        }

        .day-controls-detail::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background-size: 200% 100%;
            animation: shimmer 3s ease-in-out infinite;
        }

        @keyframes shimmer {
            0% {
                background-position: -200% 0;
            }

            100% {
                background-position: 200% 0;
            }
        }

        .day-controls-detail .estancia-btn {
            width: 36px;
            height: 36px;
            font-size: 16px;
            border-radius: 12px;
        }

        .day-controls-detail .estancia-btn[onclick*="+ 1"] {
            box-shadow: 0 4px 12px rgba(72, 187, 120, 0.3);
        }

        .day-controls-detail .estancia-btn[onclick*="- 1"] {
            box-shadow: 0 4px 12px rgba(245, 101, 101, 0.3);
        }

        .day-controls-detail .estancia-display {
            padding: 8px 16px;
            font-size: 16px;
            font-weight: 800;
            min-width: 50px;
            border-radius: 12px;
            color: #000;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
            border: none;
        }

        /* Indicador de estancia en título */
        .duration-badge {
            display: inline-flex;
            align-items: center;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 2px 8px;
            border-radius: 8px;
            font-size: 10px;
            font-weight: 600;
            margin-left: 8px;
            box-shadow: 0 2px 6px rgba(16, 185, 129, 0.3);
            animation: pulse-badge 2s ease-in-out infinite;
        }

        @keyframes pulse-badge {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }
        }

        .duration-badge::before {
            content: '📅';
            margin-right: 4px;
        }

        /* Mejorar header del día */
        .day-item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .day-number-sidebar {
            font-weight: 700;
            color: #2c3e50;
            flex: 1;
            font-size: 14px;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        /* Efectos hover para días */
        .day-sidebar-item {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .day-sidebar-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.6s;
        }

        .day-sidebar-item:hover::before {
            left: 100%;
        }

        .day-sidebar-item:hover .day-controls {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
        }

        /* Indicador visual de múltiples días */
        .multi-day-indicator {
            position: absolute;
            top: 4px;
            right: 4px;
            width: 8px;
            height: 8px;
            background: linear-gradient(135deg, var(--secondary-color) 0%, var(--secondary-color) 100%);
            border-radius: 50%;
            box-shadow: 0 0 0 2px white, 0 2px 4px rgba(245, 158, 11, 0.4);
            animation: glow 2s ease-in-out infinite alternate;
        }

        @keyframes glow {
            from {
                box-shadow: 0 0 0 2px white, 0 2px 4px rgba(245, 158, 11, 0.4);
            }

            to {
                box-shadow: 0 0 0 2px white, 0 2px 8px rgba(245, 158, 11, 0.6), 0 0 12px rgba(245, 158, 11, 0.3);
            }
        }

        /* Tooltip para los botones */
        .estancia-btn {
            position: relative;
        }

        .estancia-btn[title]:hover::after {
            content: attr(title);
            position: absolute;
            bottom: 120%;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.9);
            color: white;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 10px;
            white-space: nowrap;
            z-index: 1000;
            animation: tooltip-show 0.3s ease;
        }

        @keyframes tooltip-show {
            from {
                opacity: 0;
                transform: translateX(-50%) translateY(4px);
            }

            to {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .day-controls {
                gap: 4px;
                padding: 3px 5px;
            }

            .estancia-btn {
                width: 20px;
                height: 20px;
                font-size: 10px;
            }

            .estancia-display {
                padding: 3px 6px;
                font-size: 10px;
                min-width: 20px;
            }

            .day-controls-detail {
                padding: 12px 16px;
                gap: 8px;
            }

            .day-controls-detail .estancia-btn {
                width: 32px;
                height: 32px;
                font-size: 14px;
            }

            .day-controls-detail .estancia-display {
                padding: 6px 12px;
                font-size: 14px;
                min-width: 40px;
            }
        }

        /* Iconos de servicios alternativas */
        .service-icon.alternativa {
            background: linear-gradient(135deg, var(--secondary-color) 0%, var(--secondary-color) 100%);
            border: 2px solid white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        /* Botón para agregar alternativa */
        .btn-add-alternative {
            background: linear-gradient(135deg, #28a745 0%, var(--secondary-color) 100%);
            color: white;
            border: none;
            border-radius: 4px;
            padding: 4px 8px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .btn-add-alternative:hover {
            background: linear-gradient(135deg, #218838 0%, #1ea085 100%);
            transform: scale(1.05);
        }

        .btn-add-alternative:active {
            transform: scale(0.95);
        }

        /* Efecto hover para grupos de servicios */
        .service-group:hover .service-item.principal {
            background: linear-gradient(135deg, #e8f5e8 0%, #f0fff0 100%);
        }

        .service-group:hover .alternatives-container {
            background: #f0f8ff;
        }

        .service-group:hover .service-item.alternativa {
            background: #f0f8ff;
        }

        /* Badges para identificar principal vs alternativa */
        .service-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 8px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .service-badge.principal {
            background: linear-gradient(135deg, #ffc107 0%, #ffb300 100%);
            color: #333;
        }

        .service-badge.alternativa {
            background: linear-gradient(135deg, var(--secondary-color) 0%, var(--secondary-color) 100%);
            color: white;
        }

        /* Animaciones para alternativas */
        .alternatives-container {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }

        .service-group:hover .alternatives-container,
        .service-group.expanded .alternatives-container {
            max-height: 1000px;
        }

        /* Indicador de cantidad de alternativas */
        .alternatives-indicator {
            position: absolute;
            top: 8px;
            right: 50px;
            background: linear-gradient(135deg, var(--secondary-color) 0%, var(--secondary-color) 100%);
            color: white;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 10px;
            font-weight: 600;
        }

        /* Mejorar hover de acciones para alternativas */
        .service-item.alternativa .service-actions {
            opacity: 0.7;
        }

        .service-item.alternativa:hover .service-actions {
            opacity: 1;
        }

        /* Líneas de conexión más elaboradas */
        .service-group::before {
            content: '';
            position: absolute;
            left: 8px;
            top: 60px;
            bottom: 20px;
            width: 1px;
            background: linear-gradient(180deg, var(--primary-color) 0%, var(--secondary-color) 50%, var(--secondary-color) 100%);
            z-index: 1;
        }

        /* Responsive para alternativas */
        @media (max-width: 768px) {
            .service-item.alternativa {
                margin-left: 15px;
            }

            .alternative-connector {
                left: -15px;
                width: 15px;
            }

            .service-item.alternativa::before {
                left: -15px;
            }

            .service-actions {
                flex-direction: column;
                gap: 2px;
            }

            .btn-add-alternative {
                padding: 3px 6px;
                font-size: 10px;
            }
        }

        /* Estados de carga para alternativas */
        .loading-alternatives {
            padding: 10px;
            text-align: center;
            color: #666;
            font-style: italic;
            background: #f8f9fa;
        }

        .loading-alternatives .fas {
            animation: spin 1s linear infinite;
            margin-right: 8px;
        }

        /* Efecto de aparición de alternativas */
        .service-item.alternativa {
            animation: slideInAlternative 0.3s ease;
        }

        @keyframes slideInAlternative {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Hover effects mejorados */
        .service-group:hover .service-item.principal .service-icon {
            transform: scale(1.1);
            box-shadow: 0 4px 8px rgba(45, 90, 74, 0.3);
        }

        .service-item.alternativa:hover .service-icon {
            transform: scale(1.1);
            box-shadow: 0 4px 8px rgba(23, 162, 184, 0.3);
        }

        /* Mejorar legibilidad de texto en alternativas */
        .service-item.alternativa .service-details h6 {
            color: #2c3e50;
            font-weight: 600;
        }

        .service-item.alternativa .service-details p {
            color: #5a6c7d;
        }



        /* ============================================================
   ESTILOS PARA BARRA LATERAL DE DÍAS
   ============================================================ */

        /* Contenedor principal de día a día */
        .dias-layout {
            display: flex;
            gap: 20px;
            min-height: 500px;
        }

        /* Barra lateral de días */
        .days-sidebar {
            width: 280px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            display: flex;
            flex-direction: column;
            height: fit-content;
            /* Ajustar al contenido */
            max-height: calc(200vh - 100px);
            /* Scroll solo si hay muchos días */
        }


        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .sidebar-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }

        .add-day-btn {
            padding: 8px 16px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }

        .add-day-btn:hover {
            background: #234a3a;
            transform: translateY(-1px);
        }

        /* Botón flotante "Agregar día": vive dentro de #dia-a-dia, por lo que
           solo se muestra cuando esa pestaña está activa. */
        .fab-add-day {
            position: fixed;
            left: 24px;
            bottom: 28px;
            z-index: 1500;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 14px 22px;
            background: var(--primary-color);
            color: #fff;
            border: none;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 8px 22px rgba(0, 0, 0, 0.22);
            transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }

        .fab-add-day:hover {
            background: #234a3a;
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.28);
        }

        .fab-add-day i {
            font-size: 16px;
        }

        @media (max-width: 768px) {
            .fab-add-day {
                left: 16px;
                bottom: 20px;
                padding: 12px 18px;
                font-size: 13px;
            }
        }

        .days-list {
            flex: 1;
            overflow-y: auto;
            padding: 10px;
        }

        .day-sidebar-item {
            padding: 12px 16px;
            margin-bottom: 8px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            position: relative;
        }

        .day-sidebar-item:hover {
            background: #f8f9fa;
            border-color: #e0e0e0;
        }

        .day-sidebar-item.active {
            background: white;
            color: #2d3748;
            border: 3px solid #4a5568;
            box-shadow: 0 8px 25px rgba(74, 85, 104, 0.4), 0 4px 12px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }

        .day-sidebar-item.active:hover {
            box-shadow: 0 12px 35px rgba(74, 85, 104, 0.5), 0 6px 15px rgba(0, 0, 0, 0.2);
            transform: translateY(-3px);
        }

        .day-item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 6px;
        }

        .day-number-sidebar {
            font-weight: 600;
            font-size: 14px;
        }

        /* Botones de acción en sidebar - VISIBLES */
        .day-actions-sidebar {
            display: flex;
            gap: 4px;
            opacity: 0;
            transition: opacity 0.2s;
        }

        .day-sidebar-item:hover .day-actions-sidebar,
        .day-sidebar-item.active .day-actions-sidebar {
            opacity: 1;
        }

        .day-action-btn {
            width: 28px;
            height: 28px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            transition: all 0.2s;
        }

        .day-action-btn.edit {
            background: rgba(108, 117, 125, 0.2);
            color: #6c757d;
        }

        .day-action-btn.edit:hover {
            background: #6c757d;
            color: white;
            transform: scale(1.1);
        }

        .day-action-btn.delete {
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
        }

        .day-action-btn.delete:hover {
            background: #dc3545;
            color: white;
            transform: scale(1.1);
        }

        .day-sidebar-item.active .day-action-btn.edit {
            background: rgba(108, 117, 125, 0.3);
            color: #495057;
        }

        .day-sidebar-item.active .day-action-btn.delete {
            background: rgba(220, 53, 69, 0.3);
            color: #c82333;
        }

        .day-action-btn:hover {
            transform: scale(1.1);
        }

        .day-item-title {
            font-size: 12px;
            margin-bottom: 4px;
            font-weight: 500;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .day-item-location {
            font-size: 10px;
            opacity: 0.8;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .day-services-count {
            position: absolute;
            top: 8px;
            right: 8px;
            background: rgba(45, 90, 74, 0.9);
            color: white;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 10px;
            font-weight: 600;
        }

        .day-sidebar-item.active .day-services-count {
            background: #4a5568;
            color: white;
        }

        /* Contenido del día seleccionado */
        .day-detail-container {
            flex: 1;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            display: flex;
            flex-direction: column;
            height: fit-content;
            /* Ajustar al contenido del día */
        }

        .day-detail-header {
            padding: 24px;
            border-bottom: 1px solid #e0e0e0;
            color: #000;
        }

        .day-detail-number {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 8px;
        }

        .day-detail-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .day-flexy {
            display: flex !important;
            align-items: center;
            /* alinea verticalmente */
            gap: 24px;
            /* espacio entre elementos */
            flex-wrap: nowrap;
        }

        .day-flexy2 {
            display: block !important;
            align-items: center;
        }

        .day-fecha {
            display: flex !important;
            font-size: 14px;
            padding: 2px;
            opacity: 0.9;
        }

        .day-detail-meta {
            display: flex !important;
            font-size: 14px;
            padding: 2px;
            opacity: 0.9;
        }

        .day-detail-body {
            flex: 1;
            overflow-y: visible;
            padding: 24px;
        }

        /* Estado vacío de sidebar */
        .empty-sidebar {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }

        .empty-sidebar .fas {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.3;
        }

        .empty-sidebar h3 {
            font-size: 16px;
            margin-bottom: 8px;
            color: #333;
        }

        .empty-sidebar p {
            font-size: 14px;
            margin-bottom: 20px;
        }

        /* Estado vacío de detalle */
        .empty-detail {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            text-align: center;
            color: #666;
        }

        .empty-detail .fas {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        .empty-detail h3 {
            font-size: 20px;
            margin-bottom: 10px;
            color: #333;
        }

        .empty-detail p {
            font-size: 16px;
        }

        /* Responsivo */
        @media (max-width: 1024px) {
            .dias-layout {
                flex-direction: column;
                min-height: auto;
                /* Sin altura mínima en mobile */
            }

            .days-sidebar {
                width: 100%;
                max-height: 300px;
                overflow-y: auto;
                /* Mantener scroll en mobile */
            }

            .day-detail-container {
                height: auto;
                /* Altura automática en mobile */
            }

            .days-list {
                display: flex;
                gap: 10px;
                overflow-x: auto;
                padding: 10px;
            }

            .day-sidebar-item {
                min-width: 200px;
                flex-shrink: 0;
            }
        }

        @media (max-width: 768px) {
            .days-sidebar {
                max-height: 200px;
            }

            .day-sidebar-item {
                min-width: 160px;
            }

            .sidebar-title {
                font-size: 16px;
            }

            .add-day-btn {
                padding: 6px 12px;
                font-size: 11px;
            }
        }



        body {
            background-color: #f5f5f5;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            margin: 0;
            padding: 0;
        }

        .top-nav {
            background-color: var(--primary-color);
            color: white;
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .top-nav .logo {
            font-size: 18px;
            font-weight: 600;
            text-decoration: none;
            color: white;
            border-bottom: 2px solid white;
            padding-bottom: 2px;
        }

        .top-nav .nav-links {
            display: flex;
            gap: 30px;
            align-items: center;
        }

        .top-nav .nav-links a {
            color: white;
            text-decoration: none;
            font-size: 14px;
            border-bottom: 1px solid transparent;
            padding-bottom: 1px;
        }

        /* Estilos para el campo ID de solicitud */
        #request-id-group {
            transition: all 0.4s ease;
        }

        #request-id-group .form-text {
            margin-top: 0.25rem;
            font-size: 0.875rem;
            color: #6c757d;
        }

        #request-id-group .form-text i {
            margin-right: 0.25rem;
            color: var(--primary-color);
        }

        /* Animación de aparición */
        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .top-nav .nav-links a:hover {
            border-bottom-color: white;
        }

        .top-nav .user-avatar {
            width: 32px;
            height: 32px;
            background-color: var(--secondary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 600;
        }

        .tab-navigation {
            background-color: white;
            margin-top: 70px;
            /* Ajustado para el nuevo header */
            padding: 0;
            border-bottom: 1px solid #e0e0e0;
            position: sticky;
            top: 70px;
            /* Ajustado para el nuevo header */
            z-index: 999;
        }

        .tab-nav {
            display: flex;
            max-width: 1600px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .tab-item {
            padding: 16px 24px;
            border-bottom: 3px solid transparent;
            color: #666;
            text-decoration: none;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .tab-item.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }

        .tab-item:hover:not(.active) {
            color: var(--primary-color);
            background-color: #f8f9fa;
        }

        /* Container principal */
        .main-container {
            min-height: 100vh;
            padding: 20px;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            margin-left: 0;
            transition: margin-left 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .form-section {
            width: 98%;
            max-width: none;
            margin: 0;
        }

        .section-card {
            background: white;
            border-radius: 24px;
            box-shadow: 0 8px 35px rgba(0, 0, 0, 0.12);
            margin-bottom: 50px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .section-card:hover {
            box-shadow: 0 12px 45px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }

        /* Mejorar campos del formulario */
        .form-group {
            flex: 1;
            margin-bottom: 45px;
            position: relative;
        }

        /* ── Etiquetas inline del editor ── */
        .editor-tag-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            min-height: 44px;
        }

        .editor-tag-create {
            display: flex;
            gap: 8px;
            margin-top: 10px;
            align-items: center;
        }

        .editor-tag-create #editorTagNew {
            flex: 1 1 auto;
            max-width: 260px;
        }

        .editor-tag-create-btn {
            flex-shrink: 0;
            padding: 9px 14px;
            border-radius: 10px;
            border: 1.5px solid #e2e8f0;
            background: #f8fafc;
            color: #334155;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            transition: all .15s ease;
        }

        .editor-tag-create-btn:hover {
            background: #eef2f7;
            border-color: #cbd5e1;
        }

        .editor-tag-create-btn:disabled {
            opacity: .6;
            cursor: default;
        }

        .editor-tag-chips .etc-empty {
            font-size: 14px;
            color: #94a3b8;
        }

        .etc-chip {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 9px 16px;
            border-radius: 999px;
            border: 1.5px solid #e2e8f0;
            background: #fff;
            color: #475569;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            user-select: none;
            transition: all .15s ease;
        }

        .etc-chip:hover {
            border-color: var(--c, #94a3b8);
            transform: translateY(-1px);
        }

        .etc-chip::before {
            content: '';
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--c, #cbd5e1);
            flex-shrink: 0;
        }

        .etc-chip.selected {
            background: color-mix(in srgb, var(--c, #6366f1) 14%, #fff);
            border-color: var(--c, #6366f1);
            color: var(--c, #4338ca);
        }

        .editor-tag-hint {
            display: block;
            margin-top: 10px;
            font-size: 13px;
            color: #94a3b8;
            text-transform: none;
            letter-spacing: 0;
            font-weight: 500;
        }

        /* ── Lead vinculado (editor) ── */
        .lead-vinculo-box { font-size: 14px; }
        .lead-vinculo-box .lv-empty { color: #94a3b8; }
        .lv-linked { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; background: #f8fafc; border: 1px solid #e8edf2; border-radius: 12px; padding: 12px 14px; }
        .lv-info { display: flex; flex-direction: column; gap: 2px; flex: 1; min-width: 160px; }
        .lv-info strong { color: #0f172a; font-size: 15px; }
        .lv-info span { color: #64748b; font-size: 13px; }
        .lv-actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .lv-btn { height: 36px; padding: 0 14px; border: none; border-radius: 9px; font-size: 13px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 7px; }
        .lv-btn-primary { background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff; }
        .lv-btn-ghost { background: #eef2f7; color: #475569; }
        .lv-btn-ghost:hover { background: #e2e8f0; }
        .lv-picker { margin-top: 10px; }
        .lv-search { width: 100%; height: 40px; border: 1px solid #cbd5e1; border-radius: 9px; padding: 0 12px; font-size: 14px; }
        .lv-results { margin-top: 8px; max-height: 220px; overflow-y: auto; display: flex; flex-direction: column; gap: 6px; }
        .lv-result { text-align: left; background: #fff; border: 1px solid #e8edf2; border-radius: 9px; padding: 9px 12px; cursor: pointer; }
        .lv-result:hover { border-color: #8b5cf6; background: #faf9ff; }
        .lv-result strong { display: block; font-size: 14px; color: #0f172a; }
        .lv-result span { font-size: 12.5px; color: #64748b; }
        .lv-result.linked { opacity: .55; cursor: default; }

        .form-label {
            display: block;
            margin-bottom: 18px;
            font-weight: 700;
            color: #1a202c;
            font-size: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
        }

        .form-label::before {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 40px;
            height: 3px;
            background: var(--primary-gradient);
            border-radius: 2px;
        }

        .form-control {
            width: 100%;
            padding: 24px 28px;
            border: 3px solid #e2e8f0;
            border-radius: 18px;
            font-size: 20px;
            font-weight: 500;
            background: white;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            position: relative;
        }

        .form-control::placeholder {
            color: #a0aec0;
            font-style: italic;
            font-weight: 400;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 6px rgba(102, 126, 234, 0.15), 0 8px 25px rgba(0, 0, 0, 0.1);
            transform: translateY(-3px);
            background: #fafbfc;
        }

        .form-control:hover:not(:focus) {
            border-color: #cbd5e0;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
        }

        /* Centrar y mejorar botones de acción */
        .form-actions {
            text-align: center;
            padding: 40px 0;
            background: #f8fafc;
            margin: 40px -50px -50px -50px;
            /* Extender al borde de la tarjeta */
            border-top: 1px solid #e2e8f0;
        }

        .btn {
            padding: 20px 40px;
            border-radius: 16px;
            font-weight: 700;
            font-size: 18px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 15px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            cursor: pointer;
            margin: 0 15px;
            min-width: 250px;
            justify-content: center;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.6s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        /* Pestañas de contenido */
        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .section-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            margin-bottom: 32px;
            overflow: hidden;
        }

        .section-header {
            padding: 60px 70px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            background: #6b7280;
            color: white;
            position: relative;
            overflow: hidden;
        }


        .section-header:hover::before {
            left: 100%;
        }

        .section-title {
            font-size: 32px;
            font-weight: 800;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 20px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            letter-spacing: -0.5px;
        }

        .section-title i {
            color: #ffffff;
            font-size: 36px;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
        }

        .section-body {
            padding: 70px;
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            position: relative;
        }

        .section-body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(0, 0, 0, 0.1), transparent);
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 50px;
            margin-bottom: 35px;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
                gap: 30px;
            }
        }

        .form-group {
            flex: 1;
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(45, 90, 74, 0.1);
        }

        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: #234a3a;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-outline {
            background-color: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }

        .btn-outline:hover {
            background-color: var(--primary-color);
            color: white;
        }

        /* Estilos específicos para Día a día */
        .days-container {
            display: grid;
            gap: 30px;
        }

        .day-card {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .day-card:hover {
            border-color: var(--primary-color);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }

        .day-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .day-number {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 16px;
            backdrop-filter: blur(10px);
        }

        .day-actions {
            display: flex;
            gap: 8px;
        }

        .price-input-container {
            position: relative;
            display: flex;
            align-items: center;
        }

        .currency-icon {
            position: absolute;
            left: 12px;
            z-index: 2;
            color: #666;
            font-weight: bold;
            font-size: 16px;
            pointer-events: none;
        }

        .price-input-with-icon {
            padding-left: 35px !important;
            margin-left: 32px !important;
        }

        .btn {
            padding: 16px 32px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 16px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            margin: 0 10px;
            min-width: 200px;
            justify-content: center;
        }

        .btn-primary {
            background: var(--primary-gradient);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            color: white;
            text-decoration: none;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(108, 117, 125, 0.4);
            color: white;
            text-decoration: none;
        }

        .btn-outline {
            background: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.1);
        }

        .btn-outline:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
            text-decoration: none;
        }

        /* Contenedor de acciones mejorado */
        .form-actions {
            text-align: center;
            padding: 40px;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            margin: 40px -50px -50px -50px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        /* Responsive para botones */
        @media (max-width: 768px) {
            .form-actions {
                flex-direction: column;
                gap: 15px;
                margin: 20px -20px -20px -20px;
                padding: 30px 20px;
            }

            .btn {
                width: 100%;
                max-width: 300px;
                margin: 0;
            }
        }

        .day-content {
            padding: 25px;
        }

        .day-images {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 10px;
            margin-bottom: 20px;
            height: 200px;
        }

        .day-image {
            border-radius: 12px;
            overflow: hidden;
            background: #f8f9fa;
            position: relative;
        }

        .day-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .day-image:hover img {
            transform: scale(1.05);
        }

        .day-image.main {
            grid-row: span 2;
        }

        .day-info h4 {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }

        .day-description {
            color: #666;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .day-meta {
            display: flex;
            gap: 20px;
            color: #888;
            font-size: 14px;
        }

        .day-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* Estilos para servicios del día */
        .day-services {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
        }

        .services-header h5 {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .service-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .service-btn {
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 10px 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 500;
            color: #666;
        }

        .service-btn:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
            transform: translateY(-2px);
        }

        .meals-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .meals-section h6 {
            font-size: 14px;
            font-weight: 600;
            color: #333;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .meals-options {
            display: flex;
            gap: 20px;
            margin-bottom: 12px;
        }

        .meal-option {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-size: 14px;
        }

        .meal-option input[type="radio"] {
            margin: 0;
        }

        .meal-details {
            margin-top: 10px;
        }

        .meal-checkboxes {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .meal-checkbox {
            display: flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            font-size: 13px;
            color: #666;
        }

        .meal-checkbox input[type="checkbox"] {
            margin: 0;
        }

        .added-services {
            margin-top: 15px;
        }

        .service-item {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }

        .service-item:hover {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .service-info {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
        }

        .service-icon {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            color: white;
        }

        .service-icon.actividad {
            background: #28a745;
        }

        .service-icon.transporte {
            background: var(--primary-color);
        }

        .service-icon.alojamiento {
            background: #ffc107;
            color: #333;
        }

        .service-details h6 {
            margin: 0 0 4px 0;
            font-size: 14px;
            font-weight: 600;
            color: #333;
        }

        .service-details p {
            margin: 0;
            font-size: 12px;
            color: #666;
        }

        .service-actions {
            display: flex;
            gap: 5px;
        }

        .service-actions button {
            padding: 4px 8px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }

        .btn-edit-service {
            background: #6c757d;
            color: white;
        }

        .btn-remove-service {
            background: #dc3545;
            color: white;
        }

        /* Estilos para biblioteca modal */
        .biblioteca-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
            max-height: 55vh;
            overflow-y: auto;
            padding: 20px;
            background: #f8fafc;
            border-radius: 12px;
        }

        .biblioteca-item {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .biblioteca-item:hover {
            border-color: #4299e1;
            box-shadow: 0 12px 30px rgba(66, 153, 225, 0.15);
            transform: translateY(-3px);
        }

        .biblioteca-item.selected {
            border-color: #48bb78;
            background: #f0fff4;
            box-shadow: 0 12px 30px rgba(72, 187, 120, 0.2);
            transform: translateY(-3px);
        }

        .biblioteca-item.selected::after {
            content: '✓';
            position: absolute;
            top: 12px;
            right: 12px;
            background: #48bb78;
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
            box-shadow: 0 2px 8px rgba(72, 187, 120, 0.3);
        }

        .biblioteca-item:hover {
            border-color: var(--primary-color);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            transform: translateY(-3px);
        }

        .biblioteca-item.selected {
            border-color: var(--primary-color);
            background: #f0fff0;
            box-shadow: 0 8px 25px rgba(45, 90, 74, 0.3);
        }

        .biblioteca-item-checkbox {
            position: absolute;
            top: 12px;
            right: 12px;
            width: 28px;
            height: 28px;
            cursor: pointer;
            z-index: 10;
            opacity: 0;
        }

        .biblioteca-item-checkbox-visual {
            position: absolute;
            top: 12px;
            right: 12px;
            width: 28px;
            height: 28px;
            border: 3px solid #cbd5e0;
            border-radius: 8px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            pointer-events: none;
            z-index: 9;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .biblioteca-item.selected .biblioteca-item-checkbox-visual {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: #667eea;
            transform: scale(1.1);
        }

        .biblioteca-item.selected .biblioteca-item-checkbox-visual::after {
            content: '✓';
            color: white;
            font-size: 18px;
            font-weight: bold;
        }

        .biblioteca-item {
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .biblioteca-item.selected {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            border: 2px solid #667eea;
        }

        .biblioteca-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        /* Badge de orden de selección */
        .orden-seleccion-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            background: linear-gradient(135deg, var(--secondary-color) 0%, var(--secondary-color) 100%);
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
            box-shadow: 0 2px 8px rgba(245, 158, 11, 0.4);
            z-index: 10;
        }


        .biblioteca-item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .biblioteca-item:hover .biblioteca-item-image img {
            transform: scale(1.1);
        }

        .biblioteca-item-image {
            height: 140px;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            position: relative;
            overflow: hidden;
        }

        .biblioteca-item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .biblioteca-item:hover .biblioteca-item-image img {
            transform: scale(1.05);
        }

        .biblioteca-item-content {
            padding: 16px;
        }

        .biblioteca-item-title {
            font-size: 16px;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 8px;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .biblioteca-item-description {
            color: #718096;
            font-size: 13px;
            line-height: 1.4;
            margin-bottom: 10px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .biblioteca-item-location {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #4a5568;
            font-size: 12px;
            font-weight: 500;
        }

        .biblioteca-item-location i {
            color: #e53e3e;
            font-size: 11px;
        }


        .biblioteca-item-location {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #888;
            font-size: 13px;
        }

        .biblioteca-filters {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .search-box {
            position: relative;
        }

        .search-box input {
            padding-left: 45px;
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
        }

        .search-box .fas {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-state .fas {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        .empty-state h3 {
            font-size: 24px;
            margin-bottom: 10px;
            color: #333;
        }

        .empty-state p {
            font-size: 16px;
            margin-bottom: 30px;
        }

        /* Estilos para Precio */
        .price-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        .price-card {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 25px;
        }

        .price-input {
            font-size: 24px;
            font-weight: 600;
            text-align: center;
            color: var(--primary-color);
        }

        /* Preview panel */
        .preview-section {
            width: 400px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            height: fit-content;
            position: sticky;
            top: 140px;
        }

        .preview-header {
            padding: 24px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }

        .preview-body {
            padding: 24px;
        }

        /* Responsivo */
        @media (max-width: 768px) {
            .main-container {
                flex-direction: column;
                padding: 20px 15px;
            }

            .preview-section {
                width: 100%;
                position: static;
            }

            .form-row {
                flex-direction: column;
                gap: 0;
            }

            .tab-nav {
                flex-wrap: wrap;
                padding: 0 15px;
            }

            .tab-item {
                padding: 12px 16px;
            }
        }

        /* Estados de carga */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #ffffff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 0.8s ease-in-out infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* AGREGAR/ACTUALIZAR estilos para alertas */
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .alert-success {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
        }

        .alert-error {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
        }

        .alert-info {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
        }

        /* Animación para spinner */
        .fa-spinner {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        .expand-icon {
            color: #ffffff;
            font-size: 24px;
            transition: all 0.3s ease;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .section-header:hover .expand-icon {
            transform: scale(1.1);
        }

        .section-body.collapsed {
            display: none;
        }

        /* Estilos adicionales para modal */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.75);
            z-index: 10000;
            backdrop-filter: blur(8px);
            padding: 0;
            margin: 0;
            overflow: hidden;
        }

        .modal[style*="block"] {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

        .modal-content {
            background: white;
            border-radius: 20px;
            width: 100%;
            max-width: 900px;
            max-height: 85vh;
            overflow: hidden;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
            animation: modalAppear 0.3s ease-out;
        }

        @keyframes modalAppear {
            from {
                opacity: 0;
                transform: scale(0.9) translateY(20px);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }



        .modal-body {
            padding: 20px;
        }

        .modal-header {
            padding: 30px 30px 20px;
            background: var(--primary-color);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 20px 20px 0 0;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .close-modal {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 20px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .close-modal:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }

        .modal-body {
            overflow-y: auto;
            max-height: calc(85vh - 180px);
        }

        .modal-footer {
            padding: 25px 30px;
            background: #f8fafc;
            display: flex;
            justify-content: center;
            gap: 15px;
            border-radius: 0 0 20px 20px;
            border-top: 1px solid #e2e8f0;
        }

        .modal-footer .btn {
            min-width: 160px;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .modal-footer .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .modal-footer .btn-primary {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: white;
        }

        .modal-footer .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }

        .close-modal:hover {
            color: #333;
        }

        .preview-program {
            padding: 20px;
        }

        .preview-header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e0e0e0;
        }

        .tab-item.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }

        .tab-item:hover:not(.active) {
            color: var(--primary-color);
            background-color: #f8f9fa;
        }

        .add-day-btn {
            background: var(--primary-color);
            /* resto igual */
        }


        .preview-details {
            margin-bottom: 20px;
        }

        .detail-row {
            margin-bottom: 8px;
        }

        .preview-days {
            margin-top: 20px;
        }

        .preview-day {
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .preview-item {
            margin-bottom: 8px;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.4s ease;
            backdrop-filter: blur(5px);
        }

        .overlay.show {
            opacity: 1;
            visibility: visible;
        }

        /* Ajustes para sidebar */
        .main-container.sidebar-open {
            margin-left: 320px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-container.sidebar-open {
                margin-left: 0;
            }
        }

        .header {
            background: var(--primary-gradient);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1001;
            backdrop-filter: blur(10px);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .menu-toggle {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            font-size: 18px;
            cursor: pointer;
            padding: 10px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .menu-toggle:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.05);
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            padding: 8px 15px;
            border-radius: 12px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.1);
        }

        .user-info:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        /* Google Translate mejorado */
        /* Google Translate en la esquina */
        /* ===== MEJORAR EL SELECTOR DE GOOGLE TRANSLATE ===== */

        /* Contenedor principal */
        .translate-container {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }

        .VIpgJd-ZVi9od-ORHb-OEVmcd {
            left: 0;
            display: none !important;
            top: 0;
        }

        /* Google Translate inyecta un banner superior y empuja el <body> hacia abajo
           (body { top: 40px }), descuadrando toda la maqueta. Neutralizamos ese efecto:
           ocultamos el iframe del banner y forzamos el body a su posición original. */
        body { top: 0 !important; }
        iframe.goog-te-banner-frame,
        iframe.skiptranslate { display: none !important; }

        /* Caja del widget */
        #google_translate_element {
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.4);
            border-radius: 10px;
            padding: 8px 12px;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        #google_translate_element:hover {
            background: rgba(255, 255, 255, 1);
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.15);
        }

        /* Ocultar el icono de Google */
        .goog-te-gadget-icon {
            display: none !important;
        }

        /* Contenedor del gadget */
        .goog-te-gadget-simple {
            background: transparent !important;
            border: none !important;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
        }

        /* El enlace principal */
        .VIpgJd-ZVi9od-xl07Ob-lTBxed {
            background: transparent !important;
            border: none !important;
            color: #2d3748 !important;
            text-decoration: none !important;
            font-family: inherit !important;
            font-size: 13px !important;
            font-weight: 600 !important;
            cursor: pointer !important;
            padding: 4px 8px !important;
            border-radius: 6px !important;
            transition: all 0.2s ease !important;
            display: flex !important;
            align-items: center !important;
            gap: 6px !important;
        }

        .VIpgJd-ZVi9od-xl07Ob-lTBxed:hover {
            background: rgba(102, 126, 234, 0.1) !important;
            color: #667eea !important;
        }

        /* El texto "Seleccionar idioma" */
        .VIpgJd-ZVi9od-xl07Ob-lTBxed span:first-child {
            color: inherit !important;
            font-weight: inherit !important;
        }

        /* Ocultar las imágenes separadoras */
        .VIpgJd-ZVi9od-xl07Ob-lTBxed img {
            display: none !important;
        }

        /* Ocultar el separador */
        .VIpgJd-ZVi9od-xl07Ob-lTBxed span[style*="border-left"] {
            display: none !important;
        }

        /* Mejorar la flecha */
        .VIpgJd-ZVi9od-xl07Ob-lTBxed span[aria-hidden="true"] {
            color: #6b7280 !important;
            font-size: 12px !important;
            margin-left: 4px !important;
            transition: all 0.2s ease !important;
        }

        .VIpgJd-ZVi9od-xl07Ob-lTBxed:hover span[aria-hidden="true"] {
            color: #667eea !important;
            transform: translateY(1px) !important;
        }

        /* Menú desplegable cuando aparece */
        .goog-te-menu-frame {
            border: none !important;
            border-radius: 10px !important;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15) !important;
            backdrop-filter: blur(10px) !important;
            overflow: hidden !important;
            margin-top: 4px !important;
        }

        .goog-te-menu2 {
            background: rgba(255, 255, 255, 0.98) !important;
            border: none !important;
            padding: 8px 0 !important;
        }

        /* Items de la lista */
        .goog-te-menu2-item {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
            font-size: 13px !important;
            font-weight: 500 !important;
            color: #374151 !important;
            padding: 10px 16px !important;
            transition: all 0.15s ease !important;
            cursor: pointer !important;
            border: none !important;
            margin: 0 6px !important;
            border-radius: 6px !important;
        }

        .goog-te-menu2-item:hover {
            background: rgba(102, 126, 234, 0.1) !important;
            color: #667eea !important;
            transform: translateX(2px) !important;
        }

        .goog-te-menu2-item:active {
            transform: translateX(2px) scale(0.98) !important;
        }

        .goog-te-menu2-item-selected {
            background: #667eea !important;
            color: white !important;
            font-weight: 600 !important;
        }

        /* Ocultar banner azul */
        .goog-te-banner-frame.skiptranslate {
            display: none !important;
        }

        body {
            top: 0px !important;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .translate-container {
                top: 10px;
                right: 10px;
            }

            #google_translate_element {
                padding: 6px 10px;
            }

            .VIpgJd-ZVi9od-xl07Ob-lTBxed {
                font-size: 12px !important;
                padding: 3px 6px !important;
            }

            .goog-te-menu2-item {
                font-size: 12px !important;
                padding: 8px 14px !important;
            }
        }

        .goog-te-gadget img {
            vertical-align: middle;
            border: none;
            display: none;
        }

        body {
            top: 0px !important;
        }

        /* Overlay */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.4s ease;
            backdrop-filter: blur(5px);
        }

        .overlay.show {
            opacity: 1;
            visibility: visible;
        }

        /* Ajustes para main container */
        .main-container.sidebar-open {
            margin-left: 320px;
        }

        /* Responsive para header */
        @media (max-width: 768px) {
            .header {
                padding: 15px 20px;
            }

            .main-container.sidebar-open {
                margin-left: 0;
            }
        }

        /* ============================================================
   NUEVOS ESTILOS MEJORADOS PARA FORMULARIO GRANDE
   ============================================================ */

        /* Placeholders y ejemplos mejorados */
        .form-control[placeholder] {
            position: relative;
        }

        .form-group[data-example]::after {
            content: attr(data-example);
            position: absolute;
            top: 100%;
            left: 0;
            font-size: 14px;
            color: #718096;
            font-style: italic;
            margin-top: 8px;
            padding: 8px 12px;
            background: #f7fafc;
            border-radius: 8px;
            border-left: 3px solid var(--primary-color);
        }

        /* Efectos de enfoque mejorados */
        .form-group:focus-within .form-label {
            color: var(--primary-color);
            transform: translateY(-2px);
        }

        .form-group:focus-within .form-label::before {
            width: 60px;
            background: var(--primary-color);
        }

        /* Animaciones suaves para campos */
        .form-control {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Estados especiales para diferentes tipos de input */
        input[type="date"].form-control {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%234299e1'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 20px center;
            background-size: 24px;
        }

        select.form-control {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%234299e1'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 20px center;
            background-size: 20px;
            appearance: none;
        }

        /* Mejorar textarea */
        textarea.form-control {
            min-height: 140px;
            resize: vertical;
            line-height: 1.6;
        }

        /* Efecto de carga para campos */
        .form-control.loading {
            background-image: linear-gradient(90deg, transparent, rgba(102, 126, 234, 0.1), transparent);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
        }

        @keyframes shimmer {
            0% {
                background-position: -200% 0;
            }

            100% {
                background-position: 200% 0;
            }
        }

        /* Indicadores visuales mejorados */
        .form-control:valid:not(:placeholder-shown) {
            border-color: #48bb78;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2348bb78'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M5 13l4 4L19 7'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 20px center;
            background-size: 20px;
        }

        .form-control:invalid:not(:placeholder-shown) {
            border-color: #f56565;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23f56565'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M6 18L18 6M6 6l12 12'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 20px center;
            background-size: 20px;
        }

        /* Mejorar el expand icon */
        .expand-icon {
            color: #ffffff;
            font-size: 24px;
            transition: all 0.3s ease;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .section-header:hover .expand-icon {
            transform: scale(1.1);
        }

        /* Responsive mejorado */
        @media (max-width: 1200px) {
            .section-body {
                padding: 50px;
            }

            .section-header {
                padding: 50px;
            }
        }

        @media (max-width: 768px) {
            .section-body {
                padding: 30px;
            }

            .section-header {
                padding: 30px;
            }

            .section-title {
                font-size: 24px;
            }

            .form-control {
                padding: 20px 24px;
                font-size: 18px;
            }

            .form-label {
                font-size: 18px;
            }
        }

        /* ============================================================
   MEJORAS DE CONTRASTE Y UX/UI PARA TÍTULOS
   ============================================================ */

        /* Variaciones de color por sección */


        /* Hover mejorado para headers */
        .section-header:hover {
            background: #4b5563;
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .section-header:hover .section-title {
            color: #ffffff;
            text-shadow: 0 4px 8px rgba(0, 0, 0, 0.5);
        }

        .section-header:hover .section-title i {
            color: #ffffff;
            transform: scale(1.05);
        }

        /* Estados activos y colapsados */
        .section-header.collapsed {
            background: #9ca3af;
        }

        .section-header.collapsed .section-title,
        .section-header.collapsed .section-title i,
        .section-header.collapsed .expand-icon {
            color: #ffffff;
        }

        /* Indicador visual de estado */


        /* Mejorar accesibilidad */
        .section-header:focus {
            outline: 3px solid #63b3ed;
            outline-offset: 2px;
        }

        /* Responsive para títulos */
        @media (max-width: 768px) {
            .section-title {
                font-size: 24px;
                gap: 15px;
            }

            .section-title i {
                font-size: 28px;
            }

            .expand-icon {
                font-size: 20px;
            }
        }

        /* Animación de carga para títulos */
        @keyframes titleGlow {

            0%,
            100% {
                text-shadow: 0 3px 6px rgba(0, 0, 0, 0.4);
            }

            50% {
                text-shadow: 0 3px 6px rgba(0, 0, 0, 0.4), 0 0 20px rgba(255, 255, 255, 0.1);
            }
        }

        .section-header:hover .section-title {
            animation: titleGlow 2s ease-in-out infinite;
        }

        /* ============================================================
   CENTRADO FORZADO PARA MODAL
   ============================================================ */

        #bibliotecaModal {
            display: none;
        }

        #bibliotecaModal.show,
        #bibliotecaModal[style*="block"] {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 20px !important;
            box-sizing: border-box !important;
        }

        /* Asegurar que el modal-content esté centrado */
        #bibliotecaModal .modal-content {
            margin: auto;
            position: relative;
            top: 50%;
            transform: translateY(-50%);
        }

        /* Para pantallas pequeñas */
        @media (max-height: 600px) {
            #bibliotecaModal .modal-content {
                top: 0;
                transform: none;
                margin-top: 20px;
                margin-bottom: 20px;
                max-height: calc(100vh - 40px);
            }
        }



        /* Ocultar pestañas si no está guardado */
        .programa-no-guardado .tab-item[data-tab="dia-a-dia"],
        .programa-no-guardado .tab-item[data-tab="precio"],
        .programa-no-guardado .tab-item[data-tab="viajeros"],
        .tab-item[data-tab="adjuntos"],
        .programa-no-guardado .tab-item[onclick*="abrirVistaPrevia"],
        .programa-no-guardado .nav-button[onclick*="compartirEnlace"],
        .programa-no-guardado .nav-button[onclick*="abrirBonoReservaPrograma"] {
            opacity: 0.3;
            pointer-events: none;
            position: relative;
        }

        .programa-no-guardado .tab-item[data-tab="dia-a-dia"]::after,
        .programa-no-guardado .tab-item[data-tab="precio"]::after,
        .programa-no-guardado .tab-item[data-tab="viajeros"]::after,
        .tab-item[data-tab="adjuntos"]::after,
        .programa-no-guardado .tab-item[onclick*="abrirVistaPrevia"]::after,
        .programa-no-guardado .nav-button[onclick*="compartirEnlace"]::after,
        .programa-no-guardado .nav-button[onclick*="abrirBonoReservaPrograma"]::after {
            content: "🔒";
            position: absolute;
            top: 5px;
            right: 5px;
            font-size: 12px;
        }

        /* Toast notifications - AGREGAR AL FINAL */
        .toast {
            position: fixed;
            top: 90px;
            right: 20px;
            padding: 20px 25px;
            border-radius: 15px;
            color: white;
            z-index: 20000;
            transform: translateX(400px);
            transition: transform 0.3s ease;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            min-width: 300px;
        }

        .toast.show {
            transform: translateX(0);
        }

        .toast.success {
            background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
        }

        .toast.error {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
        }

        /* Antes no existía estilo para los toast informativos (los de "día agregado"),
           así que quedaban con texto blanco sin fondo → invisibles sobre el fondo claro. */
        .toast.info {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
        }

        /* Contenedor para input con contador */
        .input-with-counter {
            position: relative;
        }

        /* Contador de caracteres */
        .char-counter {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 11px;
            color: #6b7280;
            background: rgba(255, 255, 255, 0.9);
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 500;
            pointer-events: none;
            z-index: 10;
        }

        /* Cuando se acerca al límite */
        .char-counter.warning {
            color: var(--secondary-color);
            background: rgba(252, 211, 77, 0.1);
        }

        /* Cuando llega al límite */
        .char-counter.danger {
            color: var(--primary-color);
            background: rgba(254, 226, 226, 0.9);
        }

        /* Ajustar padding del input para dejar espacio al contador */
        .input-with-counter .form-control {
            padding-right: 60px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .char-counter {
                font-size: 10px;
                padding: 1px 4px;
                right: 8px;
            }

            .input-with-counter .form-control {
                padding-right: 50px;
            }
        }

        .file-upload-container {
            position: relative;
        }

        /* Info del archivo */
        .file-info {
            margin-top: 8px;
            font-size: 12px;
            color: #6b7280;
            min-height: 16px;
        }

        /* Estados del archivo */
        .file-info.valid {
            color: var(--primary-color);
        }

        .file-info.invalid {
            color: var(--primary-color);
        }

        .file-info.warning {
            color: var(--secondary-color);
        }

        /* Input de archivo mejorado */
        .file-input {
            position: relative;
            cursor: pointer;
        }

        .file-input::-webkit-file-upload-button {
            background: var(--primary-gradient);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            margin-right: 10px;
        }

        .file-input::-webkit-file-upload-button:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        /* Estilos para drag & drop */
        .days-list {
            position: relative;
        }

        .day-sidebar-item {
            cursor: move;
            user-select: none;
            transition: all 0.3s ease;
        }

        .day-sidebar-item:hover {
            transform: translateX(5px);
        }

        /* Estado mientras se arrastra */
        .day-sidebar-item.sortable-chosen {
            opacity: 0.5;
            transform: scale(0.98);
            cursor: grabbing !important;
        }

        .day-sidebar-item.sortable-ghost {
            opacity: 0.3;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: 2px dashed #667eea;
        }

        /* Indicador de drag */
        .day-sidebar-item::before {
            content: '⋮⋮';
            position: absolute;
            left: 5px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(0, 0, 0, 0.2);
            font-size: 16px;
            opacity: 0;
            transition: opacity 0.2s;
        }

        .day-sidebar-item:hover::before {
            opacity: 1;
        }

        /* Animación de reordenamiento */
        .sortable-drag {
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3) !important;
            transform: rotate(2deg);
        }

        /* Mensaje de ayuda */
        .drag-helper {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 12px;
            z-index: 10000;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .drag-helper.show {
            opacity: 1;
        }


        /* ============================================================
   ESTILOS PARA EDICIÓN INLINE DE DÍAS Y ACTIVIDADES
   ============================================================ */

        /* Formulario inline general */
        .edit-inline-form {
            background: #fff;
            border: 2px solid var(--primary-color);
            border-radius: 12px;
            margin: 20px 0;
            padding: 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Header del formulario */
        .edit-form-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 10px 10px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .edit-form-header h4 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
        }

        .btn-close-edit {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-close-edit:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }

        /* Cuerpo del formulario */
        .edit-form-body {
            padding: 20px;
        }

        /* Form groups */
        .edit-form-body .form-group {
            margin-bottom: 20px;
        }

        .edit-form-body label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .edit-form-body label .required {
            color: #e74c3c;
            margin-left: 4px;
        }

        .edit-form-body .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.2s;
        }

        .edit-form-body .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(45, 90, 74, 0.1);
        }

        .edit-form-body textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }

        .edit-form-body .form-text {
            display: block;
            margin-top: 4px;
            font-size: 12px;
            color: #666;
        }

        /* Búsqueda de ubicación */
        .location-search-wrapper {
            position: relative;
        }

        .location-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 6px 6px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            display: none;
        }

        .location-results.active {
            display: block;
        }

        .location-result-item {
            padding: 10px 12px;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.2s;
        }

        .location-result-item:hover {
            background: #f8f9fa;
        }

        .location-result-item:last-child {
            border-bottom: none;
        }

        /* Preview de imágenes en edición */
        .images-preview-edit {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 10px;
        }

        .image-preview-item {
            position: relative;
            aspect-ratio: 4/3;
            border: 2px dashed #ddd;
            border-radius: 8px;
            overflow: hidden;
            background: #f8f9fa;
            display: flex;
            flex-direction: column;
        }

        .image-preview-item .preview-img {
            width: 100%;
            height: calc(100% - 40px);
            object-fit: cover;
        }

        .image-preview-item .empty-image-slot {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #999;
        }

        .image-preview-item .empty-image-slot i {
            font-size: 32px;
            margin-bottom: 8px;
        }

        .image-preview-item .empty-image-slot p {
            margin: 0;
            font-size: 12px;
        }

        .btn-remove-image {
            position: absolute;
            top: 8px;
            right: 8px;
            background: rgba(231, 76, 60, 0.9);
            color: white;
            border: none;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .btn-remove-image:hover {
            background: #e74c3c;
            transform: scale(1.1);
        }

        .btn-change-image {
            width: 100%;
            padding: 8px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 0 0 6px 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.2s;
        }

        .btn-change-image:hover {
            background: #234a3a;
        }

        /* Botones de acción del formulario */
        .edit-form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }

        .edit-form-actions .btn {
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }

        .edit-form-actions .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .edit-form-actions .btn-secondary:hover {
            background: #5a6268;
        }

        .edit-form-actions .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
        }

        .edit-form-actions .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(45, 90, 74, 0.3);
        }

        /* Botón editar en servicio */
        .btn-edit-service {
            background: rgba(108, 117, 125, 0.1);
            color: #6c757d;
            border: 1px solid #6c757d;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }

        .btn-edit-service:hover {
            background: #6c757d;
            color: white;
            transform: translateY(-2px);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .images-preview-edit {
                grid-template-columns: 1fr;
            }

            .edit-form-actions {
                flex-direction: column;
            }

            .edit-form-actions .btn {
                width: 100%;
            }
        }

        /* Drop zone styles */
        .drop-zone-multiple {
            transition: all 0.3s ease;
        }

        .drop-zone-multiple:hover {
            border-color: var(--primary-color) !important;
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%) !important;
        }

        .drop-zone-multiple.drag-over {
            border-color: var(--primary-color) !important;
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%) !important;
            transform: scale(1.02);
        }

        /* Animations */
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: scale(0.9) translateY(20px);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        #crearDiaModalPrograma .modal-content {
            animation: modalSlideIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: scale(0.9) translateY(20px);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .drop-zone-multiple:hover {
            border-color: var(--primary-color) !important;
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%) !important;
        }


        /* ------------------------Estilos del toggle para mostrar o esconder precio------------------------------*/


        .price-visibility-setting,
        .sell-visibility-setting {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
            padding: 18px 20px;
            border: 1px solid #e7edf3;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 14px;
            margin-top: 12px;
        }

        /* Variante de acceso rápido al inicio del formulario (#23) */
        .sell-visibility-top {
            margin-top: 0;
            margin-bottom: 18px;
            border-left: 4px solid var(--primary-color, #0f766e);
        }

        .setting-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .setting-title {
            font-size: 15px;
            font-weight: 600;
            color: #1f2d3d;
            margin: 0;
        }

        .setting-subtitle {
            font-size: 13px;
            color: #6b7a90;
            line-height: 1.4;
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 58px;
            height: 32px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
            position: absolute;
        }

        .slider {
            position: absolute;
            inset: 0;
            cursor: pointer;
            background: #d7dee7;
            border-radius: 50px;
            transition: all .28s ease;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, .12);
        }

        .slider:before {
            content: "";
            position: absolute;
            width: 24px;
            height: 24px;
            left: 4px;
            top: 4px;
            background: #fff;
            border-radius: 50%;
            transition: all .28s ease;
            box-shadow: 0 4px 10px rgba(0, 0, 0, .18);
        }

        .switch input:checked+.slider {
            background: var(--primary-color);
        }

        .switch input:checked+.slider:before {
            transform: translateX(26px);
        }

        .switch input:focus+.slider {
            box-shadow: 0 0 0 4px rgba(47, 128, 237, .12);
        }

        /* ============================================================
   PESTAÑA VIAJEROS - VISTA COMPACTA
============================================================ */

        .viajeros-summary-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 18px;
        }

        .viajeros-summary-card {
            background: var(--card-bg, #ffffff);
            border: 1px solid var(--border-color, #e5e7eb);
            border-radius: 18px;
            padding: 22px;
            box-shadow: var(--shadow-md, 0 10px 25px rgba(15, 23, 42, 0.06));
        }

        .summary-card-header {
            display: flex;
            justify-content: space-between;
            gap: 18px;
            align-items: flex-start;
            margin-bottom: 18px;
        }

        .summary-card-header h4 {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0 0 6px 0;
            color: var(--text-primary, #111827);
            font-size: 17px;
            font-weight: 700;
        }

        .summary-card-header h4 i {
            color: var(--primary-color);
        }

        .summary-card-header p {
            margin: 0;
            color: var(--text-secondary, #6b7280);
            font-size: 13px;
            line-height: 1.4;
        }

        .empty-state-inline,
        .empty-viajeros {
            color: var(--text-secondary, #6b7280);
            border: 1px dashed var(--border-color, #d1d5db);
            background: var(--bg-light, #f9fafb);
        }

        .empty-state-inline {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 16px;
            border-radius: 14px;
        }

        .empty-state-inline i,
        .empty-viajeros i {
            color: var(--primary-color);
            opacity: 0.65;
        }

        .titular-summary-box,
        .viajero-row {
            background: var(--bg-light, #f9fafb);
            border: 1px solid var(--border-color, #e5e7eb);
            border-radius: 14px;
        }

        .titular-summary-box {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 14px;
            align-items: center;
            padding: 16px;
        }

        .titular-summary-name,
        .viajero-name {
            font-weight: 700;
            color: var(--text-primary, #111827);
            font-size: 15px;
        }

        .titular-summary-meta,
        .viajero-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 5px;
            color: var(--text-secondary, #6b7280);
            font-size: 13px;
        }

        .checkbox-line {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 14px 0 0 0;
            font-size: 14px;
            color: var(--text-primary, #374151);
            cursor: pointer;
        }

        .checkbox-line input {
            width: 16px;
            height: 16px;
        }

        .viajeros-seleccionados-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .viajero-row {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 14px;
            align-items: center;
            padding: 14px 16px;
        }

        .viajero-main {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .viajero-badge {
            display: inline-flex;
            align-items: center;
            width: fit-content;
            padding: 4px 9px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            background: color-mix(in srgb, var(--primary-color) 12%, white);
            color: var(--primary-color);
            margin-right: 6px;
        }

        .viajero-badge.titular {
            background: var(--primary-gradient);
            color: white;
        }

        .viajero-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-viajero-action {
            border: none;
            border-radius: 10px;
            padding: 8px 10px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.2s ease;
        }

        .btn-viajero-action.primary {
            background: color-mix(in srgb, var(--primary-color) 12%, white);
            color: var(--primary-color);
        }

        .btn-viajero-action.danger {
            background: #fee2e2;
            color: var(--primary-color);
        }

        .btn-viajero-action:hover {
            transform: translateY(-1px);
            filter: brightness(0.97);
        }

        .empty-viajeros {
            text-align: center;
            padding: 26px 18px;
            border-radius: 16px;
        }

        .empty-viajeros i {
            display: block;
            font-size: 28px;
            margin-bottom: 8px;
        }

        /* Modal viajeros */

        #modal-viajero.modal {
            position: fixed;
            inset: 0;
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(4px);
        }

        #modal-viajero .modal-content {
            width: min(720px, 94vw);
            max-height: 90vh;
            overflow-y: auto;
            background: var(--card-bg, #fff);
            border-radius: var(--border-radius-lg, 18px);
            box-shadow: var(--shadow-xl, 0 25px 60px rgba(15, 23, 42, 0.25));
            margin: 0;
            position: relative;
        }

        #modal-viajero .modal-header {
            background: var(--primary-gradient);
            color: white;
            border-radius: var(--border-radius-lg, 18px) var(--border-radius-lg, 18px) 0 0;
        }

        .phone-input-group {
            display: grid;
            grid-template-columns: 135px 1fr;
            gap: 10px;
        }

        .country-code-select {
            min-width: 120px;
        }

        .viajero-status {
            display: none;
            margin: 10px 0 16px 0;
            padding: 12px 14px;
            border-radius: 12px;
            font-size: 14px;
            line-height: 1.4;
        }

        .viajero-status.success {
            display: block;
            background: color-mix(in srgb, var(--primary-color) 10%, white);
            color: var(--primary-color);
            border: 1px solid color-mix(in srgb, var(--primary-color) 28%, white);
        }

        .viajero-status.info {
            display: block;
            background: color-mix(in srgb, var(--secondary-color) 10%, white);
            color: var(--secondary-color);
            border: 1px solid color-mix(in srgb, var(--secondary-color) 28%, white);
        }

        .viajero-status.warning {
            display: block;
            background: #fffbeb;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        .viajero-status.error {
            display: block;
            background: #fef2f2;
            color: var(--primary-color);
            border: 1px solid #fecaca;
        }

        @media (max-width: 900px) {
            .summary-card-header {
                flex-direction: column;
            }

            .summary-card-header .btn {
                width: 100%;
            }

            .viajero-row,
            .titular-summary-box {
                grid-template-columns: 1fr;
            }

            .phone-input-group {
                grid-template-columns: 1fr;
            }

            .viajero-actions {
                justify-content: flex-start;
                flex-wrap: wrap;
            }
        }

        .iti {
            width: 100%;
        }

        .iti__country-list {
            z-index: 10000;
        }



        .acomodaciones-selector-wrapper {
            margin-top: 18px;
        }

        .acomodaciones-selector-card {
            background: var(--card-bg, #fff);
            border: 1px solid var(--border-color, #e5e7eb);
            border-radius: 16px;
            padding: 18px;
            box-shadow: var(--shadow-sm, 0 4px 12px rgba(15, 23, 42, 0.06));
        }

        .acomodaciones-selector-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 14px;
        }

        .acomodaciones-selector-header h4 {
            margin: 0 0 4px 0;
            font-size: 16px;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .acomodaciones-selector-header h4 i {
            color: var(--primary-color);
        }

        .acomodaciones-selector-header p {
            margin: 0;
            color: var(--text-secondary);
            font-size: 13px;
        }

        #modal-crear-acomodacion-programa.modal {
            position: fixed;
            inset: 0;
            z-index: 10050 !important;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(4px);
        }

        #modal-crear-acomodacion-programa .modal-content {
            background: var(--card-bg, #fff);
            border-radius: 18px;
            width: min(560px, 94vw);
            max-height: 90vh;
            overflow-y: auto;
        }

        #modal-editar-alojamiento.modal {
            position: fixed;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: rgba(15, 23, 42, .45);
            z-index: 10001;
        }

        .readonly-hotel-box {
            background: var(--bg-light, #f8fafc);
            border: 1px solid var(--border-color, #e5e7eb);
            border-radius: 12px;
            padding: 14px 16px;
            color: var(--text-primary);
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .readonly-hotel-box span {
            color: var(--text-secondary);
            font-size: 13px;
        }

        .btn-acomodacion {
            background: color-mix(in srgb, var(--primary-color) 12%, white) !important;
            color: var(--primary-color) !important;
        }

        /* ================================
   Vuelos por día
================================ */

        .flights-section {
            margin: 18px 0;
            padding: 18px;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
        }

        .flights-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 14px;
        }

        .flights-title {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #1f2937;
            font-weight: 700;
            font-size: 15px;
        }

        .flights-title i {
            color: var(--primary-color, #667eea);
        }

        .flight-search-row {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .flight-code-input {
            flex: 1;
            min-width: 180px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 14px;
            text-transform: uppercase;
            background: #fff;
        }

        .flight-code-input:focus {
            outline: none;
            border-color: var(--primary-color, #667eea);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, .12);
        }

        .flight-search-btn {
            border: none;
            border-radius: 10px;
            padding: 10px 14px;
            background: var(--primary-gradient);
            color: white;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
        }

        .flight-search-btn:disabled {
            opacity: .6;
            cursor: not-allowed;
        }

        .flight-preview {
            display: none;
            margin-top: 12px;
            padding: 14px;
            border-radius: 12px;
            background: #ffffff;
            border: 1px solid #dbeafe;
        }

        .flight-preview-card {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            align-items: center;
        }

        .flight-route-main {
            font-weight: 800;
            color: #111827;
            margin-bottom: 4px;
        }

        .flight-route-meta {
            color: #6b7280;
            font-size: 13px;
            line-height: 1.45;
        }

        .flight-confirm-btn {
            border: none;
            border-radius: 9px;
            padding: 9px 12px;
            background: var(--primary-color);
            color: white;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
        }

        .flights-list {
            margin-top: 12px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .flight-item {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-left: 4px solid var(--primary-color, #667eea);
            border-radius: 12px;
            padding: 12px 14px;
        }

        .flight-item-top {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: flex-start;
        }

        .flight-code {
            font-weight: 800;
            color: #111827;
            font-size: 14px;
        }

        .flight-airline {
            color: #6b7280;
            font-size: 13px;
        }

        .flight-route {
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #374151;
            font-size: 13px;
            flex-wrap: wrap;
        }

        .flight-time {
            margin-top: 6px;
            color: #6b7280;
            font-size: 13px;
        }

        .flight-delete-btn {
            border: none;
            background: #fee2e2;
            color: var(--primary-color);
            width: 30px;
            height: 30px;
            border-radius: 8px;
            cursor: pointer;
        }

        .flight-empty,
        .flight-loading {
            color: #6b7280;
            font-size: 13px;
            padding: 8px 2px;
        }

        @media (max-width: 768px) {

            .flight-search-row,
            .flight-preview-card {
                flex-direction: column;
                align-items: stretch;
            }

            .flight-search-btn,
            .flight-confirm-btn {
                width: 100%;
            }
        }



        /* ============================================================
   REDISEÑO LIMPIO DEL PANEL PROGRAMA
   Usa únicamente la paleta configurada por la agencia + neutros.
   No cambia IDs, names, endpoints ni funciones JS existentes.
============================================================ */
        :root {
            --brand-primary-soft: color-mix(in srgb, var(--primary-color) 10%, white);
            --brand-primary-softer: color-mix(in srgb, var(--primary-color) 5%, white);
            --brand-secondary-soft: color-mix(in srgb, var(--secondary-color) 12%, white);
            --brand-border: color-mix(in srgb, var(--primary-color) 16%, #e5e7eb);
            --brand-shadow: 0 18px 45px rgba(15, 23, 42, .07);
            --brand-shadow-soft: 0 8px 24px rgba(15, 23, 42, .055);
            --panel-bg: #f7f8fb;
            --panel-card: #ffffff;
            --panel-text: #182033;
            --panel-muted: #6b7280;
            --panel-line: #e9edf3;
            --panel-radius: 20px;
        }

        body {
            background: var(--panel-bg) !important;
            color: var(--panel-text) !important;
        }

        .main-container {
            background:
                radial-gradient(circle at top left, var(--brand-primary-soft), transparent 34%),
                linear-gradient(180deg, #fbfcfe 0%, var(--panel-bg) 100%) !important;
            padding: 28px 28px 56px !important;
            min-height: calc(100vh - 70px) !important;
        }

        .form-section {
            width: min(1480px, 100%) !important;
            margin: 0 auto !important;
        }

        .tab-navigation {
            background: rgba(255, 255, 255, .92) !important;
            border-bottom: 1px solid var(--panel-line) !important;
            backdrop-filter: blur(14px) !important;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .04) !important;
        }

        .tab-nav {
            max-width: 1480px !important;
            gap: 8px !important;
            align-items: center !important;
            padding: 10px 20px !important;
            overflow-x: auto !important;
            scrollbar-width: thin !important;
        }

        .tab-item,
        .nav-button {
            min-height: 42px !important;
            border-radius: 999px !important;
            border: 1px solid transparent !important;
            background: transparent !important;
            color: var(--panel-muted) !important;
            padding: 10px 16px !important;
            font-size: 13px !important;
            font-weight: 650 !important;
            letter-spacing: 0 !important;
            text-transform: none !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 8px !important;
            white-space: nowrap !important;
            box-shadow: none !important;
            margin: 0 !important;
        }

        .tab-item:hover,
        .nav-button:hover {
            background: var(--brand-primary-softer) !important;
            border-color: var(--brand-border) !important;
            color: var(--primary-color) !important;
            transform: none !important;
        }

        .tab-item.active {
            background: var(--primary-gradient) !important;
            color: #fff !important;
            border-color: transparent !important;
            box-shadow: 0 10px 24px color-mix(in srgb, var(--primary-color) 24%, transparent) !important;
        }

        .tab-item.active i,
        .nav-button i {
            color: inherit !important;
        }

        .nav-button[onclick*="compartirEnlace"],
        .nav-button[onclick*="abrirMiBiblioteca"],
        .nav-button[onclick*="abrirBonoReservaPrograma"] {
            background: #fff !important;
            color: var(--primary-color) !important;
            border: 1px solid var(--brand-border) !important;
            box-shadow: 0 6px 18px rgba(15, 23, 42, .045) !important;
        }

        .nav-button[onclick*="compartirEnlace"]:hover,
        .nav-button[onclick*="abrirMiBiblioteca"]:hover,
        .nav-button[onclick*="abrirBonoReservaPrograma"]:hover {
            background: var(--brand-primary-soft) !important;
            color: var(--primary-color) !important;
        }

        .section-card,
        .viajeros-summary-card,
        .acomodaciones-selector-card,
        .flights-section,
        .price-visibility-setting,
        .readonly-hotel-box,
        .titular-summary-box,
        .viajero-row,
        .day-card,
        .service-card,
        .price-card,
        .modal-content {
            background: var(--panel-card) !important;
            border: 1px solid var(--panel-line) !important;
            border-radius: var(--panel-radius) !important;
            box-shadow: var(--brand-shadow-soft) !important;
        }

        .section-card {
            overflow: hidden !important;
            margin-bottom: 22px !important;
            transition: border-color .2s ease, box-shadow .2s ease !important;
        }

        .section-card:hover {
            transform: none !important;
            border-color: var(--brand-border) !important;
            box-shadow: var(--brand-shadow) !important;
        }

        .section-header,
        .section-header.collapsed {
            background: #fff !important;
            color: var(--panel-text) !important;
            padding: 22px 28px !important;
            border-bottom: 1px solid var(--panel-line) !important;
            cursor: pointer !important;
            box-shadow: none !important;
        }

        .section-header:hover {
            background: var(--brand-primary-softer) !important;
            transform: none !important;
            box-shadow: none !important;
        }

        .section-title,
        .section-header.collapsed .section-title {
            color: var(--panel-text) !important;
            font-size: 20px !important;
            font-weight: 750 !important;
            letter-spacing: -.02em !important;
            gap: 12px !important;
            text-shadow: none !important;
            animation: none !important;
        }

        .section-title i,
        .section-header.collapsed .section-title i {
            width: 38px !important;
            height: 38px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            border-radius: 12px !important;
            background: var(--brand-primary-soft) !important;
            color: var(--primary-color) !important;
            font-size: 16px !important;
            filter: none !important;
        }

        .expand-icon,
        .section-header.collapsed .expand-icon {
            color: var(--panel-muted) !important;
            font-size: 14px !important;
        }

        .section-body {
            padding: 28px !important;
            background: #fff !important;
            border-top: 0 !important;
        }

        .section-body::before,
        .section-header::before,
        .form-label::before,
        .btn::before {
            display: none !important;
        }

        .form-row {
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)) !important;
            gap: 18px !important;
            margin-bottom: 0 !important;
        }

        .form-group {
            margin-bottom: 18px !important;
        }

        .form-label,
        .acomodaciones-selector-header h4,
        .summary-card-header h4,
        .setting-title {
            color: var(--panel-text) !important;
            font-size: 13px !important;
            font-weight: 700 !important;
            letter-spacing: 0 !important;
            text-transform: none !important;
            margin-bottom: 8px !important;
        }

        .form-text,
        .setting-subtitle,
        .summary-card-header p,
        .acomodaciones-selector-header p,
        .file-info,
        .titular-summary-meta,
        .viajero-meta {
            color: var(--panel-muted) !important;
        }

        .form-control,
        select.form-control,
        textarea.form-control,
        input[type="date"].form-control,
        .input-with-counter .form-control,
        .country-code-select {
            min-height: 46px !important;
            padding: 11px 14px !important;
            border: 1px solid var(--panel-line) !important;
            border-radius: 14px !important;
            background: #fff !important;
            color: var(--panel-text) !important;
            font-size: 14px !important;
            font-weight: 500 !important;
            box-shadow: none !important;
            transition: border-color .18s ease, box-shadow .18s ease, background .18s ease !important;
        }

        .input-with-counter .form-control {
            padding-right: 64px !important;
        }

        .form-control:hover:not(:focus) {
            border-color: var(--brand-border) !important;
            transform: none !important;
            box-shadow: none !important;
        }

        .form-control:focus,
        .country-code-select:focus {
            border-color: var(--primary-color) !important;
            background: #fff !important;
            box-shadow: 0 0 0 4px var(--brand-primary-soft) !important;
            transform: none !important;
            outline: none !important;
        }

        #calculated-departure {
            background: var(--brand-primary-softer) !important;
            border-color: var(--brand-border) !important;
            color: var(--panel-muted) !important;
        }

        .char-counter {
            color: var(--panel-muted) !important;
            background: rgba(255, 255, 255, .92) !important;
            border: 1px solid var(--panel-line) !important;
            border-radius: 999px !important;
        }

        .btn,
        .btn-primary,
        .edit-form-actions .btn-primary,
        .file-input::-webkit-file-upload-button,
        button[style*="var(--primary-color)"],
        button[style*="var(--secondary-color)"] {
            background: var(--primary-gradient) !important;
            color: #fff !important;
            border: 1px solid transparent !important;
            border-radius: 14px !important;
            padding: 11px 18px !important;
            min-width: auto !important;
            font-size: 14px !important;
            font-weight: 700 !important;
            letter-spacing: 0 !important;
            text-transform: none !important;
            box-shadow: 0 10px 22px color-mix(in srgb, var(--primary-color) 22%, transparent) !important;
            transform: none !important;
        }

        .btn:hover,
        .btn-primary:hover,
        .edit-form-actions .btn-primary:hover {
            filter: brightness(.98) !important;
            transform: translateY(-1px) !important;
        }

        .btn-secondary,
        .edit-form-actions .btn-secondary,
        .btn-outline,
        .btn-viajero-action.primary,
        .btn-acomodacion {
            background: var(--brand-primary-soft) !important;
            color: var(--primary-color) !important;
            border: 1px solid var(--brand-border) !important;
            box-shadow: none !important;
        }

        .btn-danger,
        .btn-viajero-action.danger,
        .btn-edit-service,
        button[style*="background: var(--primary-color);"][style*="color: white"] {
            background: var(--brand-secondary-soft) !important;
            color: var(--secondary-color) !important;
            border: 1px solid color-mix(in srgb, var(--secondary-color) 22%, white) !important;
            box-shadow: none !important;
        }

        .form-actions {
            margin: 20px -28px -28px !important;
            padding: 22px 28px !important;
            background: #fbfcfe !important;
            border-top: 1px solid var(--panel-line) !important;
            text-align: right !important;
        }

        .price-visibility-setting,
        .empty-state-inline,
        .empty-viajeros,
        .readonly-hotel-box,
        .titular-summary-box,
        .viajero-row {
            background: #fbfcfe !important;
            border-color: var(--panel-line) !important;
        }

        .viajero-badge,
        .viajero-badge.titular,
        .viajero-status.success,
        .viajero-status.info,
        .viajero-status.warning,
        .viajero-status.error {
            background: var(--brand-primary-soft) !important;
            color: var(--primary-color) !important;
            border-color: var(--brand-border) !important;
        }

        .switch input:checked+.slider {
            background: var(--primary-gradient) !important;
        }

        .drop-zone-multiple:hover,
        .drop-zone-multiple.drag-over {
            border-color: var(--primary-color) !important;
            background: var(--brand-primary-softer) !important;
            transform: none !important;
        }

        .modal-header,
        #modal-viajero .modal-header,
        #bibliotecaModal .modal-header,
        #crearDiaModalPrograma .modal-header {
            background: var(--primary-gradient) !important;
            color: var(--text-primary);
            border: 0 !important;
        }

        .toast.success,
        .toast.error {
            background: var(--primary-gradient) !important;
            color: var(--text-primary) !important;
        }


        /* ====== ADJUNTOS: zona de añadir ====== */
        .adj-add-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 28px;
            margin-bottom: 36px;
        }

        @media (max-width: 900px) {
            .adj-add-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
        }

        /* Dropzone (arrastrar o clic) */
        .adj-dropzone {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 180px;
            padding: 28px;
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            background: #f8fafc;
            cursor: pointer;
            text-align: center;
            transition: all 0.2s ease;
        }

        .adj-dropzone:hover {
            border-color: var(--primary-color);
            background: #fff;
        }

        /* Estado activo al arrastrar encima (toggle con JS: classList 'is-dragover') */
        .adj-dropzone.is-dragover {
            border-color: var(--primary-color);
            background: rgba(45, 90, 74, 0.06);
            transform: scale(1.01);
        }

        .adj-dropzone i {
            font-size: 44px;
            color: var(--primary-color);
        }

        .adj-dz-title {
            font-size: 17px;
            font-weight: 700;
            color: #1f2937;
        }

        .adj-dz-sub {
            font-size: 13px;
            color: #6b7280;
        }

        /* Caja de enlace */
        .adj-link-box {
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding: 24px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            background: #fff;
            justify-content: center;
        }

        .adj-link-label {
            font-size: 15px;
            font-weight: 700;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .adj-link-label i {
            color: var(--primary-color);
        }

        .adj-link-row {
            display: flex;
            gap: 10px;
        }

        .adj-link-input {
            flex: 1;
            font-size: 15px;
            padding: 12px 14px;
        }

        .adj-link-btn {
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 0 20px;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            color: #fff;
            cursor: pointer;
            white-space: nowrap;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            transition: opacity 0.2s, transform 0.15s;
        }

        .adj-link-btn:hover {
            opacity: 0.92;
        }

        .adj-link-btn:active {
            transform: scale(0.97);
        }

        .adj-link-hint {
            font-size: 12px;
            color: #9ca3af;
        }

        /* ====== ADJUNTOS: lista ====== */
        .adj-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .adj-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 14px 18px;
            border: 1px solid #eef0f3;
            border-radius: 10px;
            background: #fff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            transition: box-shadow 0.2s, border-color 0.2s, transform 0.15s;
        }

        .adj-item:hover {
            border-color: #d7dce3;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transform: translateY(-1px);
        }

        .adj-item-icon {
            flex-shrink: 0;
            width: 46px;
            height: 46px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            font-size: 20px;
            color: #fff;
        }

        .adj-icon-file {
            background: linear-gradient(135deg, #ef4444 0%, #f97316 100%);
        }

        .adj-icon-link {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        }

        .adj-item-info {
            flex: 1;
            min-width: 0;
            /* permite el truncado */
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .adj-item-name {
            font-size: 15px;
            font-weight: 600;
            color: #1f2937;
            text-decoration: none;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .adj-item-name:hover {
            color: var(--primary-color);
            text-decoration: underline;
        }

        .adj-item-meta {
            font-size: 12.5px;
            color: #9ca3af;
        }

        .adj-item-action {
            flex-shrink: 0;
            width: 38px;
            height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            border-radius: 8px;
            background: #f3f4f6;
            color: #4b5563;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.2s, color 0.2s;
        }

        .adj-item-action:hover {
            background: #e5e7eb;
            color: #111827;
        }

        .adj-item-delete:hover {
            background: #fee2e2;
            color: #dc2626;
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 18px 14px 36px !important;
            }

            .tab-navigation {
                top: 64px !important;
                margin-top: 64px !important;
            }

            .tab-nav {
                padding: 8px 12px !important;
            }

            .tab-item,
            .nav-button {
                padding: 9px 12px !important;
                font-size: 12px !important;
            }

            .section-header {
                padding: 18px !important;
            }

            .section-body {
                padding: 18px !important;
            }

            .section-title {
                font-size: 17px !important;
            }

            .form-actions {
                text-align: center !important;
            }

            .form-actions .btn {
                width: 100% !important;
                margin: 6px 0 !important;
                justify-content: center !important;
            }
        }
    </style>


</head>

<body class="<?= !$is_editing ? 'programa-no-guardado' : 'programa-guardado' ?>">

    <!-- Header con componentes -->
    <?= UIComponents::renderHeader($user) ?>

    <!-- Sidebar con componentes -->
    <?= UIComponents::renderSidebar($user, '/programa') ?>

    <!-- Overlay -->
    <div class="overlay" id="overlay" onclick="closeSidebar()"></div>
    <br>

    <!-- Tab Navigation -->
    <div class="tab-navigation">
        <div class="tab-nav">
            <a href="#" class="tab-item active" data-tab="mi-programa">Mi programa</a>
            <a href="#" class="tab-item" data-tab="dia-a-dia">Día a día</a>
            <a href="#" class="tab-item" data-tab="precio">Precio</a>
            <a href="#" class="tab-item" data-tab="viajeros">Viajeros</a>
            <a href="#" class="tab-item" data-tab="informacion">Informacion Adicional</a>
            <a href="#" class="tab-item" onclick="abrirVistaPrevia()">
                <i class="fas fa-eye"></i> Vista previa
            </a>
            <button type="button" class="nav-button" onclick="compartirEnlace()"
                style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);">
                <i class="fas fa-share-alt"></i>
                <span>Compartir Enlace</span>
            </button>


            <button type="button" class="nav-button" onclick="abrirMiBiblioteca()"
                style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);">
                <i class="fas fa-book"></i>
                <span>Mi Biblioteca</span>
            </button>
        </div>
    </div>

    <!-- Main Container -->
    <div class="main-container">
        <!-- Form Section -->
        <div class="form-section">
            <!-- Contenido de la pestaña Mi Programa -->
            <div id="mi-programa" class="tab-content active">
                <form id="programa-form" method="POST" enctype="multipart/form-data" novalidate>
                    <input type="hidden" name="pipeline_id" value="<?php echo $_GET['pipeline_id'] ?? ''; ?>">
                    <!-- Campos ocultos -->
                    <?php if ($is_editing): ?>
                        <input type="hidden" id="programa-id-hidden" name="programa_id" value="<?= $programa_id ?>">
                    <?php endif; ?>

                    <!-- Control "vendido" ARRIBA para acceso rápido (#23). Mismo id/name que antes
                         para no romper el guardado (comprado), la confirmación al desmarcar ni el Rooming. -->
                    <div class="sell-visibility-setting sell-visibility-top">
                        <div class="setting-info">
                            <label for="vendido-toggle" class="setting-title">
                                ¿El itinerario ya fue vendido?
                            </label>
                            <small class="setting-subtitle">
                                Actívalo solo cuando la venta esté confirmada.
                            </small>
                        </div>

                        <div class="toggle-wrapper">
                            <input type="hidden" name="comprado" value="0">

                            <label class="switch">
                                <input type="checkbox" name="comprado" id="vendido-toggle" value="1"
                                    <?= (!empty($form_data['comprado']) && $form_data['comprado'] == 1) ? 'checked' : '' ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                        <script>window.ORIG_COMPRADO = <?= (!empty($form_data['comprado']) && $form_data['comprado'] == 1) ? 1 : 0 ?>;</script>
                    </div>

                    <!-- Sección: Solicitud del viajero -->
                    <div class="section-card">
                        <div class="section-header" onclick="toggleSection(this)">
                            <div class="section-title">
                                <i class="fas fa-user"></i>
                                Solicitud del viajero
                            </div>
                            <i class="fas fa-chevron-up expand-icon"></i>
                        </div>
                        <div class="section-body">
                            <div class="form-group" id="request-id-group" <?php if (!$is_editing || empty($form_data['request_id'])): ?>style="display: none;" <?php endif; ?>>
                                <label class="form-label">ID de solicitud</label>
                                <input type="text" class="form-control" id="request-id" name="request_id"
                                    value="<?= htmlspecialchars($form_data['request_id']) ?>" readonly>
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle"></i> Este ID se genera automáticamente al crear el
                                    programa
                                </small>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label" for="traveler-name">Nombre del viajero *</label>
                                    <div class="input-with-counter">
                                        <input type="text" class="form-control" id="traveler-name" name="traveler_name"
                                            value="<?= htmlspecialchars($form_data['traveler_name']) ?>"
                                            placeholder="Ejemplo: María Alejandra" maxlength="250" data-max-chars="250"
                                            required>
                                        <div class="char-counter" id="traveler-name-counter">0/250</div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="traveler-lastname">Apellido del viajero *</label>
                                    <div class="input-with-counter">
                                        <input type="text" class="form-control" id="traveler-lastname"
                                            name="traveler_lastname"
                                            value="<?= htmlspecialchars($form_data['traveler_lastname']) ?>"
                                            placeholder="Ejemplo: García Rodríguez" maxlength="250" data-max-chars="250"
                                            required>
                                        <div class="char-counter" id="traveler-lastname-counter">0/250</div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="destination">Destino *</label>
                                <input type="text" class="form-control" id="destination" name="destination"
                                    value="<?= htmlspecialchars($form_data['destination']) ?>"
                                    placeholder="Ejemplo: Tailandia - Bangkok y Phuket" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Etiquetas</label>
                                <div id="editorTagChips" class="editor-tag-chips">
                                    <span class="etc-empty">Cargando etiquetas…</span>
                                </div>
                                <small id="editorTagHint" class="editor-tag-hint">Clic para etiquetar este
                                    programa.</small>
                                <div class="editor-tag-create">
                                    <input type="text" id="editorTagNew" class="form-control" maxlength="40"
                                        placeholder="Nueva etiqueta…"
                                        onkeydown="if(event.key==='Enter'){event.preventDefault();crearEtiquetaEditor();}">
                                    <button type="button" id="editorTagCreateBtn" class="editor-tag-create-btn"
                                        onclick="crearEtiquetaEditor()">
                                        <i class="fas fa-plus"></i> Crear etiqueta
                                    </button>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Lead vinculado</label>
                                <div id="leadVinculoBox" class="lead-vinculo-box">
                                    <span class="lv-empty">Cargando…</span>
                                </div>
                                <small class="editor-tag-hint">Vincula este itinerario a un lead del pipeline para
                                    abrir su chat. El vínculo es bidireccional con el pipeline.</small>
                            </div>



                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label" for="arrival-date">Fecha de llegada *</label>
                                    <input type="date" class="form-control" id="arrival-date" name="arrival_date"
                                        value="<?= htmlspecialchars($form_data['arrival_date']) ?>" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Fecha de salida</label>

                                    <!-- Textos traducibles ocultos -->
                                    <span id="text-day-singular" style="display:none;">día total</span>
                                    <span id="text-days-plural" style="display:none;">días total</span>

                                    <!-- ✅ CAMBIO: Usar div en lugar de input para mejor traducción -->
                                    <div id="calculated-departure" class="form-control"
                                        style="background: #f8fafc; color: #718096; font-style: italic; min-height: 38px; display: flex; align-items: center;">
                                        La fecha de salida se calcula automáticamente según los días del programa
                                    </div>

                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle"></i> La fecha de salida se calcula automáticamente
                                        basada en los días agregados en "Día a día"
                                    </small>

                                    <!-- Hidden input para enviar al backend -->
                                    <input type="hidden" name="departure_date" id="departure-date-hidden" value="">
                                </div>

                                <input type="hidden" name="departure_date" id="departure-date-hidden" value="">
                            </div>
                        </div>
                    </div>

                    <!-- Sección: Personalización del programa -->
                    <div class="section-card">
                        <div class="section-header" onclick="toggleSection(this)">
                            <div class="section-title">
                                <i class="fas fa-palette"></i>
                                Personalización del programa
                            </div>
                            <i class="fas fa-chevron-up expand-icon"></i>
                        </div>
                        <div class="section-body">
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label" for="program-title">Título del programa</label>
                                    <div class="input-with-counter">
                                        <input type="text" class="form-control" id="program-title" name="program_title"
                                            value="<?= htmlspecialchars($form_data['program_title']) ?>"
                                            placeholder="Ejemplo: Descubrir Tailandia en familia durante 15 días"
                                            maxlength="250" data-max-chars="250">
                                        <div class="char-counter" id="program-title-counter">0/250</div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="language">Idioma predeterminado</label>
                                    <select class="form-control" id="language" name="language">
                                        <option value="es" <?= $form_data['language'] === 'es' ? 'selected' : '' ?>>Español
                                        </option>
                                        <option value="en" <?= $form_data['language'] === 'en' ? 'selected' : '' ?>>English
                                        </option>
                                        <option value="fr" <?= $form_data['language'] === 'fr' ? 'selected' : '' ?>>
                                            Français</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="cover-image">Foto de portada</label>
                                <div class="file-upload-container">
                                    <input type="file" class="form-control file-input" id="cover-image"
                                        name="cover_image"
                                        accept=".jpeg,.jpg,.png,.webp,image/jpeg,image/jpg,image/png,image/webp"
                                        data-max-size="20971520">
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle"></i> Formatos permitidos: JPEG, PNG, JPG, WebP |
                                        Peso máximo: 10MB
                                    </small>
                                    <div class="file-info" id="cover-image-info"></div>
                                    <?php if (!empty($form_data['cover_image'])): ?>
                                        <div class="current-image" style="margin-top: 10px;">
                                            <img src="<?= htmlspecialchars($form_data['cover_image']) ?>"
                                                alt="Imagen actual"
                                                style="max-width: 200px; height: auto; border-radius: 8px;">
                                            <p style="font-size: 12px; color: #666; margin-top: 5px;">Imagen actual</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <!-- (El control "¿El itinerario ya fue vendido?" se movió al inicio del formulario para acceso rápido — #23) -->
                        </div>
                    </div>

                    <!-- Botones de acción -->
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary" id="submit-btn">
                            <i class="fas fa-save"></i>
                            <?= $is_editing ? 'Actualizar programa' : 'Crear programa' ?>
                        </button>

                        <?php if ($is_editing): ?>
                            <button type="button" class="btn btn-secondary" onclick="abrirVistaPrevia()">
                                <i class="fas fa-eye"></i>
                                Ver Programa
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="abrirModalSubagencias()">
                                <i class="fas fa-share-alt"></i>
                                Compartir con subagencias
                            </button>
                        <?php endif; ?>

                        <a href="<?= APP_URL ?>/itinerarios" class="btn btn-outline">
                            <i class="fas fa-arrow-left"></i>
                            Volver a itinerarios
                        </a>
                    </div>
                </form>
            </div>

            <!-- Contenido de la pestaña Día a día -->
            <div id="dia-a-dia" class="tab-content">
                <!-- Botón flotante siempre accesible al hacer scroll -->
                <button type="button" class="fab-add-day" onclick="agregarDia()" title="Agregar un nuevo día">
                    <i class="fas fa-plus"></i>
                    Agregar día
                </button>
                <div class="section-card">
                    <div class="section-header">
                        <div class="section-title">
                            <i class="fas fa-calendar-day"></i>
                            Gestión de días del programa
                        </div>
                    </div>
                    <div class="section-body">
                        <!-- NUEVO LAYOUT CON BARRA LATERAL -->
                        <div class="dias-layout">
                            <!-- Barra lateral de días -->
                            <div class="days-sidebar">
                                <div class="sidebar-header">
                                    <div class="sidebar-title">
                                        <i class="fas fa-list"></i>
                                        Días
                                    </div>
                                    <button class="add-day-btn" onclick="agregarDia()">
                                        <i class="fas fa-plus"></i>
                                        Agregar
                                    </button>
                                </div>
                                <div class="days-list" id="days-sidebar-list">
                                    <!-- Los días se cargarán aquí dinámicamente -->
                                    <div class="empty-sidebar">
                                        <i class="fas fa-calendar-plus"></i>
                                        <h3>No hay días</h3>
                                        <p>Agrega tu primer día</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Contenido del día seleccionado -->
                            <div class="day-detail-container" id="day-detail-content">
                                <div class="empty-detail">
                                    <div>
                                        <i class="fas fa-calendar-day"></i>
                                        <h3>Selecciona un día</h3>
                                        <p>Elige un día de la lista para ver y editar sus detalles</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- FIN NUEVO LAYOUT -->
                    </div>
                </div>
            </div>

            <!-- Contenido de la pestaña Precio -->
            <div id="precio" class="tab-content">
                <div class="section-card">
                    <div class="section-header">
                        <div class="section-title">
                            <i class="fas fa-dollar-sign"></i>
                            Configuración de precios
                        </div>
                    </div>
                    <div class="section-body">
                        <form id="precio-form" method="POST">
                            <div class="price-section">
                                <div class="price-card">
                                    <h4>Información de precios</h4>
                                    <div class="form-group">
                                        <label class="form-label">Moneda</label>
                                        <select class="form-control" name="moneda">
                                            <option value="USD">USD - Dólar estadounidense</option>
                                            <option value="EUR">EUR - Euro</option>
                                            <option value="JPY">JPY - Yen japonés</option>
                                            <option value="GBP">GBP - Libra esterlina</option>
                                            <option value="AUD">AUD - Dólar australiano</option>
                                            <option value="CAD">CAD - Dólar canadiense</option>
                                            <option value="CHF">CHF - Franco suizo</option>
                                            <option value="CNY">CNY - Yuan chino</option>
                                            <option value="SEK">SEK - Corona sueca</option>
                                            <option value="NZD">NZD - Dólar neozelandés</option>
                                            <option value="COP">COP - Peso colombiano</option>
                                            <option value="MXN">MXN - Peso mexicano</option>
                                            <option value="ARS">ARS - Peso argentino</option>
                                            <option value="BRL">BRL - Real brasileño</option>
                                            <option value="CLP">CLP - Peso chileno</option>
                                            <option value="PEN">PEN - Sol peruano</option>
                                            <option value="UYU">UYU - Peso uruguayo</option>
                                            <option value="VES">VES - Bolívar venezolano</option>
                                            <option value="NOK">NOK - Corona noruega</option>
                                            <option value="DKK">DKK - Corona danesa</option>
                                            <option value="PLN">PLN - Zloty polaco</option>
                                            <option value="CZK">CZK - Corona checa</option>
                                            <option value="HUF">HUF - Florín húngaro</option>
                                            <option value="RUB">RUB - Rublo ruso</option>
                                            <option value="TRY">TRY - Lira turca</option>
                                            <option value="ZAR">ZAR - Rand sudafricano</option>
                                            <option value="INR">INR - Rupia india</option>
                                            <option value="KRW">KRW - Won surcoreano</option>
                                            <option value="SGD">SGD - Dólar singapurense</option>
                                            <option value="HKD">HKD - Dólar de Hong Kong</option>
                                            <option value="THB">THB - Baht tailandés</option>
                                            <option value="MYR">MYR - Ringgit malayo</option>
                                            <option value="IDR">IDR - Rupia indonesia</option>
                                            <option value="PHP">PHP - Peso filipino</option>
                                            <option value="VND">VND - Dong vietnamita</option>
                                            <option value="TWD">TWD - Dólar taiwanés</option>
                                            <option value="ILS">ILS - Nuevo shekel israelí</option>
                                            <option value="AED">AED - Dirham emiratí</option>
                                            <option value="SAR">SAR - Riyal saudí</option>
                                            <option value="QAR">QAR - Riyal catarí</option>
                                            <option value="KWD">KWD - Dinar kuwaití</option>
                                            <option value="BHD">BHD - Dinar bahreiní</option>
                                            <option value="OMR">OMR - Rial omaní</option>
                                            <option value="JOD">JOD - Dinar jordano</option>
                                            <option value="LBP">LBP - Libra libanesa</option>
                                            <option value="EGP">EGP - Libra egipcia</option>
                                            <option value="MAD">MAD - Dirham marroquí</option>
                                            <option value="TND">TND - Dinar tunecino</option>
                                            <option value="DZD">DZD - Dinar argelino</option>
                                            <option value="NGN">NGN - Naira nigeriana</option>
                                            <option value="KES">KES - Chelín keniano</option>
                                            <option value="GHS">GHS - Cedi ghanés</option>
                                            <option value="ETB">ETB - Birr etíope</option>
                                            <option value="UGX">UGX - Chelín ugandés</option>
                                            <option value="TZS">TZS - Chelín tanzano</option>
                                            <option value="ZMW">ZMW - Kwacha zambiano</option>
                                            <option value="BWP">BWP - Pula de Botsuana</option>
                                            <option value="MUR">MUR - Rupia mauriciana</option>
                                            <option value="SCR">SCR - Rupia seychelense</option>
                                            <option value="XOF">XOF - Franco CFA occidental</option>
                                            <option value="XAF">XAF - Franco CFA central</option>
                                            <option value="CDF">CDF - Franco congoleño</option>
                                            <option value="AOA">AOA - Kwanza angoleño</option>
                                            <option value="MZN">MZN - Metical mozambiqueño</option>
                                            <option value="SZL">SZL - Lilangeni suazi</option>
                                            <option value="LSL">LSL - Loti lesotense</option>
                                            <option value="NAD">NAD - Dólar namibio</option>
                                            <option value="MWK">MWK - Kwacha malauí</option>
                                            <option value="RWF">RWF - Franco ruandés</option>
                                            <option value="BIF">BIF - Franco burundés</option>
                                            <option value="DJF">DJF - Franco yibutiano</option>
                                            <option value="SOS">SOS - Chelín somalí</option>
                                            <option value="ERN">ERN - Nakfa eritreo</option>
                                            <option value="STN">STN - Dobra santotomense</option>
                                            <option value="CVE">CVE - Escudo caboverdiano</option>
                                            <option value="GMD">GMD - Dalasi gambiano</option>
                                            <option value="GNF">GNF - Franco guineano</option>
                                            <option value="LRD">LRD - Dólar liberiano</option>
                                            <option value="SLE">SLE - Leone sierraleonés</option>
                                            <option value="ALL">ALL - Lek albanés</option>
                                            <option value="BAM">BAM - Marco convertible bosnio</option>
                                            <option value="BGN">BGN - Lev búlgaro</option>
                                            <option value="HRK">HRK - Kuna croata</option>
                                            <option value="RSD">RSD - Dinar serbio</option>
                                            <option value="MKD">MKD - Denar macedonio</option>
                                            <option value="RON">RON - Leu rumano</option>
                                            <option value="MDL">MDL - Leu moldavo</option>
                                            <option value="UAH">UAH - Grivna ucraniana</option>
                                            <option value="BYN">BYN - Rublo bielorruso</option>
                                            <option value="GEL">GEL - Lari georgiano</option>
                                            <option value="AMD">AMD - Dram armenio</option>
                                            <option value="AZN">AZN - Manat azerbaiyano</option>
                                            <option value="KZT">KZT - Tenge kazajo</option>
                                            <option value="UZS">UZS - Som uzbeko</option>
                                            <option value="TJS">TJS - Somoni tayiko</option>
                                            <option value="KGS">KGS - Som kirguís</option>
                                            <option value="TMT">TMT - Manat turkmeno</option>
                                            <option value="AFN">AFN - Afgani afgano</option>
                                            <option value="PKR">PKR - Rupia pakistaní</option>
                                            <option value="LKR">LKR - Rupia esrilanquesa</option>
                                            <option value="NPR">NPR - Rupia nepalí</option>
                                            <option value="BTN">BTN - Ngultrum butanés</option>
                                            <option value="BDT">BDT - Taka bangladesí</option>
                                            <option value="MMK">MMK - Kyat birmano</option>
                                            <option value="LAK">LAK - Kip laosiano</option>
                                            <option value="KHR">KHR - Riel camboyano</option>
                                            <option value="BND">BND - Dólar bruneano</option>
                                            <option value="MNT">MNT - Tugrik mongol</option>
                                            <option value="KPW">KPW - Won norcoreano</option>
                                            <option value="FJD">FJD - Dólar fiyiano</option>
                                            <option value="PGK">PGK - Kina papú</option>
                                            <option value="SBD">SBD - Dólar de Islas Salomón</option>
                                            <option value="VUV">VUV - Vatu vanuatuense</option>
                                            <option value="NCX">NCX - Franco del Pacífico</option>
                                            <option value="WST">WST - Tala samoano</option>
                                            <option value="TOP">TOP - Paʻanga tongano</option>
                                            <option value="NIO">NIO - Córdoba nicaragüense</option>
                                            <option value="CRC">CRC - Colón costarricense</option>
                                            <option value="PAB">PAB - Balboa panameño</option>
                                            <option value="GTQ">GTQ - Quetzal guatemalteco</option>
                                            <option value="HNL">HNL - Lempira hondureño</option>
                                            <option value="SVC">SVC - Colón salvadoreño</option>
                                            <option value="BZD">BZD - Dólar beliceño</option>
                                            <option value="JMD">JMD - Dólar jamaiquino</option>
                                            <option value="HTG">HTG - Gourde haitiano</option>
                                            <option value="DOP">DOP - Peso dominicano</option>
                                            <option value="CUP">CUP - Peso cubano</option>
                                            <option value="BBD">BBD - Dólar barbadense</option>
                                            <option value="TTD">TTD - Dólar trinitense</option>
                                            <option value="GYD">GYD - Dólar guyanés</option>
                                            <option value="SRD">SRD - Dólar surinamés</option>
                                            <option value="AWG">AWG - Florín arubeño</option>
                                            <option value="ANG">ANG - Florín antillano</option>
                                            <option value="XCD">XCD - Dólar del Caribe Oriental</option>
                                            <option value="BOB">BOB - Boliviano</option>
                                            <option value="PYG">PYG - Guaraní paraguayo</option>
                                            <option value="GGP">GGP - Libra de Guernsey</option>
                                            <option value="JEP">JEP - Libra de Jersey</option>
                                            <option value="IMP">IMP - Libra manesa</option>
                                            <option value="FKP">FKP - Libra malvinense</option>
                                            <option value="GIP">GIP - Libra gibraltareña</option>
                                            <option value="SHP">SHP - Libra de Santa Elena</option>
                                            <option value="ISK">ISK - Corona islandesa</option>
                                            <option value="FOK">FOK - Corona feroesa</option>
                                        </select>
                                    </div>
                                    <!-- NUEVOS CAMPOS: ADULTOS -->
                                    <div class="form-row"
                                        style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                        <div class="form-group">
                                            <label class="form-label">Cantidad de Adultos</label>
                                            <input type="number" class="form-control" name="cantidad_adultos"
                                                id="cantidad-adultos" min="1" value="1"
                                                onchange="calcularPrecioTotal()">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Precio por Adulto</label>
                                            <div class="price-input-container">
                                                <span class="currency-icon" id="currency-icon-adulto">$</span>
                                                <input type="number" class="form-control price-input-with-icon"
                                                    name="precio_adulto" id="precio-adulto" placeholder="0.00"
                                                    step="0.01" onchange="calcularPrecioTotal()">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- NUEVOS CAMPOS: NIÑOS -->
                                    <div class="form-row"
                                        style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                        <div class="form-group">
                                            <label class="form-label">Cantidad de Niños</label>
                                            <input type="number" class="form-control" name="cantidad_ninos"
                                                id="cantidad-ninos" min="0" value="0" onchange="calcularPrecioTotal()">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Precio por Niño</label>
                                            <div class="price-input-container">
                                                <span class="currency-icon" id="currency-icon-nino">$</span>
                                                <input type="number" class="form-control price-input-with-icon"
                                                    name="precio_nino" id="precio-nino" placeholder="0.00" step="0.01"
                                                    onchange="calcularPrecioTotal()">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- PRECIO TOTAL (AUTO-CALCULADO PERO EDITABLE) -->
                                    <div class="form-group">
                                        <label class="form-label">
                                            Precio Total
                                            <small style="color: #6c757d; font-weight: normal; margin-left: 8px;">
                                                (Se calcula automáticamente, pero puedes editarlo)
                                            </small>
                                        </label>
                                        <div class="price-input-container">
                                            <span class="currency-icon" id="currency-icon-total">$</span>
                                            <input type="number" class="form-control price-input-with-icon"
                                                name="precio_total" id="precio-total" placeholder="0.00" step="0.01">
                                        </div>
                                        <small class="form-text text-muted" id="calculo-info" style="display: none;">
                                            <i class="fas fa-calculator"></i>
                                            <span id="calculo-detalle"></span>
                                        </small>
                                    </div>

                                    <!----------Selector de Precio visible o invisible--------------------->
                                    <div class="price-visibility-setting">
                                        <div class="setting-info">
                                            <label for="mostrar-precio-toggle" class="setting-title">
                                                Mostrar precios en el itinerario
                                            </label>
                                            <small class="setting-subtitle">
                                                Activa o desactiva la visualización pública de precios.
                                            </small>
                                        </div>

                                        <div class="toggle-wrapper">
                                            <input type="hidden" name="mostrar_precio" value="0">

                                            <label class="switch">
                                                <input type="checkbox" name="mostrar_precio" id="mostrar-precio-toggle"
                                                    value="1" checked>
                                                <span class="slider"></span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- 
                                    <div class="form-group">
                                        <label class="form-label">Noches incluidas</label>
                                        <input type="number" class="form-control" name="noches_incluidas" 
                                            placeholder="0" min="0">
                                    </div>
                                    -->
                                </div>

                                <div class="price-card">
                                    <h4>Información adicional</h4>
                                    <div class="form-group">
                                        <label class="form-label">¿Qué incluye el precio?</label>
                                        <div class="textarea-with-counter">
                                            <textarea class="form-control" name="precio_incluye" rows="4"
                                                placeholder="Describe qué servicios están incluidos..." maxlength="3000"
                                                data-max-chars="3000"></textarea>
                                            <div class="char-counter" id="precio_incluye-counter">0/3000</div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">¿Qué NO incluye?</label>
                                        <div class="textarea-with-counter">
                                            <textarea class="form-control" name="precio_no_incluye" rows="4"
                                                placeholder="Describe qué servicios NO están incluidos..."
                                                maxlength="3000" data-max-chars="3000"></textarea>
                                            <div class="char-counter" id="precio_no_incluye-counter">0/3000</div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <input type="checkbox" name="movilidad_reducida" value="1">
                                        <label class="form-label" style="margin-left: 8px;">Adaptado para movilidad
                                            reducida</label>
                                    </div>
                                </div>
                            </div>

                            <div class="section-card" style="margin-top: 20px;">
                                <div class="section-body">
                                    <div class="form-group">
                                        <label class="form-label">Condiciones generales</label>
                                        <div class="textarea-with-counter">
                                            <textarea class="form-control" name="condiciones_generales" rows="4"
                                                placeholder="Condiciones y términos del programa..." maxlength="3000"
                                                data-max-chars="3000"></textarea>
                                            <div class="char-counter" id="condiciones_generales-counter">0/3000</div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Información de pasaporte</label>
                                        <div class="textarea-with-counter">
                                            <textarea class="form-control" name="info_pasaporte" rows="3"
                                                placeholder="Requisitos de documentación..." maxlength="3000"
                                                data-max-chars="3000"></textarea>
                                            <div class="char-counter" id="info_pasaporte-counter">0/3000</div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Información de seguros</label>
                                        <div class="textarea-with-counter">
                                            <textarea class="form-control" name="info_seguros" rows="3"
                                                placeholder="Información sobre seguros de viaje..." maxlength="3000"
                                                data-max-chars="3000"></textarea>
                                            <div class="char-counter" id="info_seguros-counter">0/3000</div>
                                        </div>
                                    </div>
                                    <?php foreach ([
                                        ['visados_entrada', 'Visados y requisitos de entrada', 'Documentación y trámites de entrada al país...'],
                                        ['requisitos_sanitarios', 'Requisitos sanitarios', 'Vacunas, certificados sanitarios...'],
                                        ['llegada_punto_encuentro', 'Llegada y punto de encuentro', 'Dónde y cómo es el encuentro a la llegada...'],
                                        ['asistencia_emergencia', 'Asistencia y emergencias', 'Contactos y protocolo de emergencias...'],
                                        ['info_hoteles_servicios', 'Información de hoteles y servicios', 'Datos de hoteles y servicios incluidos...'],
                                        ['informacion_practica', 'Información práctica', 'Moneda, clima, enchufes, recomendaciones...'],
                                    ] as $sf): ?>
                                        <div class="form-group">
                                            <label class="form-label"><?= $sf[1] ?></label>
                                            <div class="textarea-with-counter">
                                                <textarea class="form-control" name="<?= $sf[0] ?>" rows="3"
                                                    placeholder="<?= $sf[2] ?>" maxlength="3000"
                                                    data-max-chars="3000"></textarea>
                                                <div class="char-counter" id="<?= $sf[0] ?>-counter">0/3000</div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="form-actions" style="text-align: center; padding: 24px 0;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i>
                                    Guardar precios
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contenido de la pestaña Viajeros -->
        <div id="viajeros" class="tab-content">
            <div class="section-card">
                <div class="section-header">
                    <div class="section-title">
                        <i class="fas fa-users"></i>
                        Viajeros
                    </div>
                </div>

                <div class="section-body">
                    <p class="form-text text-muted" style="margin-bottom: 20px;">
                        Define el titular de la solicitud y las personas que viajarán. Puedes dejar esta sección vacía
                        si aún es una cotización preliminar.
                    </p>

                    <div class="viajeros-summary-grid">
                        <div class="viajeros-summary-card">
                            <div class="summary-card-header">
                                <div>
                                    <h4><i class="fas fa-user-tie"></i> Titular de la solicitud</h4>
                                    <p>Responsable o contacto principal. No necesariamente viaja.</p>
                                </div>
                                <button type="button" class="btn btn-primary btn-sm"
                                    onclick="abrirModalViajero('titular')">
                                    Seleccionar / crear
                                </button>
                            </div>

                            <div id="titular-resumen" class="titular-resumen empty-state-inline">
                                <i class="fas fa-user-circle"></i>
                                <span>Sin titular seleccionado</span>
                            </div>

                            <label class="checkbox-line compact">
                                <input type="checkbox" id="titular-tambien-viaja" checked>
                                <span>Este titular también viaja</span>
                            </label>
                        </div>

                        <div class="viajeros-summary-card">
                            <div class="summary-card-header">
                                <div>
                                    <h4><i class="fas fa-suitcase-rolling"></i> Viajeros asociados</h4>
                                    <p>Personas que sí harán parte del viaje.</p>
                                </div>
                                <button type="button" class="btn btn-primary btn-sm"
                                    onclick="abrirModalViajero('viajero')">
                                    + Agregar
                                </button>
                            </div>

                            <div id="viajeros-seleccionados-list" class="viajeros-seleccionados-list compact-list">
                                <div class="empty-viajeros">
                                    <i class="fas fa-users"></i>
                                    <p>Aún no hay viajeros asociados.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>



        <!-- Contenido de la pestaña Informacion adicional -->
        <div id="informacion" class="tab-content">
            <div class="section-card">
                <div class="section-header">
                    <div class="section-title">
                        <i class="fas fa-users"></i>
                        Informacion adicional
                    </div>
                </div>

                <div class="section-body">

                    <!-- ZONA DE AÑADIR: archivo (izq) + enlace (der) -->
                    <div class="adj-add-grid">
                        <!-- Dropzone: arrastrar O clic -->
                        <label class="adj-dropzone" for="adj-file-input">
                            <input type="file" id="adj-file-input" multiple hidden
                                accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx,application/pdf,image/jpeg,image/png,image/webp,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <span class="adj-dz-title">Arrastra tus archivos aquí</span>
                            <span class="adj-dz-sub">o haz clic para buscar · PDF, JPG, PNG, WebP, Word (.doc/.docx), Excel (.xls/.xlsx) · máx. 10 MB</span>
                        </label>

                        <!-- Enlace: pegar + Enter / botón -->
                        <div class="adj-link-box">
                            <span class="adj-link-label"><i class="fas fa-link"></i> Añadir un enlace</span>
                            <input type="text" id="adj-link-titulo" class="form-control adj-link-input"
                                maxlength="255" placeholder="Título (opcional)" style="margin-bottom:8px;">
                            <div class="adj-link-row">
                                <input type="url" id="adj-link-input" class="form-control adj-link-input"
                                    placeholder="https://… y presiona Enter">
                                <button type="button" class="adj-link-btn">
                                    <i class="fas fa-plus"></i> Añadir
                                </button>
                            </div>
                            <span class="adj-link-hint">Reservas, vuelos, documentos compartidos…</span>
                        </div>
                    </div>

                    <!-- LISTA DE ADJUNTOS -->
                    <div class="adj-list" id="adj-list">



                    </div>

                    <!-- ESTADO VACÍO (mostrar solo si no hay adjuntos) -->
                    <div class="empty-state adj-empty" id="adj-empty" style="display:none;">
                        <i class="fas fa-folder-open"></i>
                        <h3>Aún no hay archivos ni enlaces</h3>
                        <p>Arrastra un archivo o pega un enlace para empezar.</p>
                    </div>

                </div>
            </div>
        </div>
        <!-- Modal: compartir tour con subagencias -->
        <div id="modal-subagencias" class="modal" style="display: none;">
            <div class="modal-content" style="max-width:520px;">
                <div class="modal-header">
                    <h3>Compartir con subagencias</h3>
                    <button type="button" class="modal-close" onclick="cerrarModalSubagencias()">&times;</button>
                </div>
                <div class="modal-body">
                    <p style="font-size:13px;color:#64748b;margin:0 0 14px;">Marca las subagencias que pueden revender este tour. Cada una lo verá en su panel con sus propios precios y marca.</p>
                    <div id="subagChips" style="display:flex;flex-wrap:wrap;gap:8px;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="cerrarModalSubagencias()">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="guardarSubagencias()"><i class="fas fa-save"></i> Guardar</button>
                </div>
            </div>
        </div>

        <!-- Modal para crear/seleccionar viajeros -->
        <div id="modal-viajero" class="modal" style="display: none;">
            <div class="modal-content modal-viajero-content">
                <div class="modal-header">
                    <h3 id="modal-viajero-title">Agregar viajero</h3>
                    <button type="button" class="modal-close" onclick="cerrarModalViajero()">&times;</button>
                </div>

                <div class="modal-body">
                    <input type="hidden" id="modal-viajero-contexto" value="viajero">

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Tipo de documento</label>
                            <select class="form-control" id="modal-tipo-documento">
                                <option value="1">Cédula de ciudadanía</option>
                                <option value="2">Pasaporte</option>
                                <option value="3">Cédula de extranjería</option>
                                <option value="4">Tarjeta de identidad</option>
                                <option value="5">Registro civil</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Número de documento</label>
                            <input type="text" class="form-control" id="modal-numero-documento"
                                placeholder="Escribe y presiona Enter">
                        </div>
                    </div>

                    <div id="modal-viajero-status" class="viajero-status"></div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="modal-nombre">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Apellido</label>
                            <input type="text" class="form-control" id="modal-apellido">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Fecha de nacimiento</label>
                            <input type="date" class="form-control" id="modal-fecha-nacimiento">
                        </div>

                        <div class="form-group">
                            <label class="form-label">País / nacionalidad</label>
                            <input type="text" class="form-control" id="modal-pais-nacimiento">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Correo <small>(opcional)</small></label>
                            <input type="email" class="form-control" id="modal-mail">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Teléfono <small>(opcional)</small></label>
                            <input type="tel" class="form-control" id="modal-telefono" inputmode="numeric"
                                placeholder="3004005060">
                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModalViajero()">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="guardarViajeroDesdeModal()">
                        Guardar
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal para agregar/editar días desde biblioteca -->
        <div id="bibliotecaModal" class="modal" style="display: none;">
            <div class="modal-content" style="max-width: 1200px; max-height: 90vh; overflow-y: auto;">
                <div class="modal-header">
                    <h3>
                        <i class="fas fa-book"></i>
                        Seleccionar días de la biblioteca
                        <span id="contador-seleccionados" style="
                    display: inline-block;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 4px 12px;
                    border-radius: 20px;
                    font-size: 14px;
                    margin-left: 10px;
                    font-weight: 600;
                ">0 seleccionados</span>
                    </h3>
                    <button class="close-modal" onclick="cerrarModalBiblioteca()">&times;</button>
                </div>

                <div class="modal-body">
                    <!-- Información de ayuda -->
                    <div style="
                background: linear-gradient(135deg, #e0e7ff 0%, #e6f3ff 100%);
                border-left: 4px solid #667eea;
                padding: 12px 16px;
                border-radius: 8px;
                margin-bottom: 20px;
                display: flex;
                align-items: center;
                gap: 12px;
            ">
                        <i class="fas fa-info-circle" style="color: #667eea; font-size: 20px;"></i>
                        <div style="color: #4c51bf; font-size: 14px;">
                            <strong>Selección múltiple activada:</strong>
                            Puedes seleccionar uno o varios días a la vez. Los días se agregarán al programa en el orden
                            en que los selecciones.
                        </div>
                    </div>

                    <div class="biblioteca-filters"
                        style="display: flex; gap: 15px; align-items: center; margin-bottom: 20px;">
                        <div class="search-box" style="flex: 1;">
                            <i class="fas fa-search"></i>
                            <input type="text" placeholder="Buscar días..." id="search-dias" class="form-control">
                        </div>

                        <!-- Botón para seleccionar/deseleccionar todos -->
                        <button type="button" class="btn btn-outline" onclick="toggleSeleccionarTodos()"
                            id="btn-toggle-todos" style="
                            padding: 10px 20px;
                            border: 2px solid #667eea;
                            background: white;
                            color: #667eea;
                            border-radius: 8px;
                            font-weight: 600;
                            cursor: pointer;
                            transition: all 0.3s;
                        " onmouseover="this.style.background='#667eea'; this.style.color='white';"
                            onmouseout="this.style.background='white'; this.style.color='#667eea';">
                            <i class="fas fa-check-double"></i> Seleccionar todos
                        </button>

                        <!-- BOTÓN PARA CREAR DÍA -->
                        <button type="button" class="btn btn-success" onclick="abrirModalCrearDiaPrograma()" style="
                            display: inline-flex;
                            align-items: center;
                            gap: 8px;
                            padding: 12px 24px;
                            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
                            border: none;
                            border-radius: 10px;
                            color: white;
                            font-weight: 600;
                            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
                        ">
                            <i class="fas fa-plus-circle"></i>
                            Crear Nuevo Día
                        </button>
                    </div>

                    <div id="biblioteca-dias-grid" class="biblioteca-grid">
                        <!-- Los días de la biblioteca se cargarán aquí con checkboxes -->
                    </div>
                </div>

                <div class="modal-footer" style="
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 24px;
            border-top: 2px solid #e2e8f0;
        ">
                    <!-- Info de selección -->
                    <div id="info-seleccion" style="
                color: #4a5568;
                font-size: 14px;
                font-weight: 500;
            ">
                        <i class="fas fa-info-circle" style="color: #667eea;"></i>
                        Selecciona uno o más días para agregar
                    </div>

                    <!-- Botones de acción -->
                    <div style="display: flex; gap: 12px;">
                        <button class="btn btn-secondary" onclick="cerrarModalBiblioteca()">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button class="btn btn-primary" onclick="agregarDiasSeleccionados()" id="btn-agregar-dias"
                            disabled style="
                            position: relative;
                            overflow: hidden;
                        ">
                            <i class="fas fa-plus"></i>
                            <span id="texto-btn-agregar">Agregar días seleccionados</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="crearDiaModalPrograma" class="modal" style="display: none;">
            <div class="modal-content" style="max-width: 1000px; max-height: 95vh; overflow-y: auto;">
                <div class="modal-header"
                    style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);">
                    <h3><i class="fas fa-calendar-plus"></i> Crear Nuevo Día</h3>
                    <button class="close-modal" onclick="cerrarModalCrearDiaPrograma()"
                        style="background: rgba(255,255,255,0.2);">&times;</button>
                </div>

                <div class="modal-body" style="padding: 30px;">
                    <form id="formCrearDiaEnPrograma">

                        <!-- IDIOMA -->
                        <div class="form-group">
                            <label>Idioma</label>
                            <select id="idioma-crear-programa" name="idioma" class="form-control">
                                <option value="es">Español</option>
                                <option value="en">English</option>
                                <option value="fr">Français</option>
                                <option value="pt">Português</option>
                            </select>
                        </div>

                        <!-- TÍTULO -->
                        <div class="form-group">
                            <label>Título <span style="color: var(--primary-color);">*</span></label>
                            <div style="position: relative;">
                                <input type="text" id="titulo-crear-programa" name="titulo" class="form-control"
                                    required placeholder="Ej: Día en París" maxlength="300"
                                    style="padding-right: 80px;">
                                <div class="char-counter" id="titulo-counter-programa"
                                    style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); font-size: 11px; color: #6b7280;">
                                    0/300</div>
                            </div>
                        </div>

                        <!-- UBICACIÓN PRINCIPAL -->
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label>Ubicación Principal <span style="color: var(--primary-color);">*</span></label>
                            <div style="position: relative;">
                                <input type="text" id="ubicacion-principal-crear-programa" name="ubicacion"
                                    class="form-control" required placeholder="Buscar ciudad, lugar, monumento..."
                                    autocomplete="off"
                                    style="padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 10px;">
                                <input type="hidden" name="latitud" id="latitud-principal-programa">
                                <input type="hidden" name="longitud" id="longitud-principal-programa">
                                <div id="preview-ubicacion-principal-programa"></div>
                            </div>
                        </div>

                        <!-- UBICACIONES SECUNDARIAS -->
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label>Ubicaciones Adicionales (opcional)</label>
                            <div id="ubicaciones-secundarias-container-programa"
                                style="display: flex; flex-direction: column; gap: 12px;">
                                <!-- Se agregan dinámicamente -->
                            </div>
                            <button type="button" onclick="agregarUbicacionSecundariaPrograma()"
                                style="margin-top: 12px; padding: 10px 20px; background: #48bb78; color: white; border: none; border-radius: 8px; cursor: pointer;">
                                ➕ Agregar Otra Ubicación
                            </button>
                        </div>

                        <!-- DESCRIPCIÓN -->
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label>Descripción</label>
                            <div style="position: relative;">
                                <textarea id="descripcion-crear-programa" name="descripcion" rows="5"
                                    class="form-control" placeholder="Describe las actividades..." maxlength="3000"
                                    style="padding-bottom: 35px;"></textarea>
                                <div class="char-counter" id="descripcion-counter-programa"
                                    style="position: absolute; right: 12px; bottom: 12px; font-size: 11px; color: #6b7280;">
                                    0/3000</div>
                            </div>
                        </div>

                        <!-- IMÁGENES -->
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label>Imágenes (máximo 3)</label>
                            <div id="imageUploadContainerPrograma"></div>
                        </div>

                        <!-- Inputs ocultos para imágenes -->
                        <input type="file" id="imagenes-programa" name="imagen1" accept="image/*"
                            style="display: none;">
                        <input type="file" id="imagen2-programa" name="imagen2" accept="image/*" style="display: none;">
                        <input type="file" id="imagen3-programa" name="imagen3" accept="image/*" style="display: none;">
                        <input type="file" id="multipleImages-programa" multiple accept="image/*"
                            style="display: none;">

                    </form>
                </div>

                <div class="modal-footer"
                    style="padding: 20px 30px; display: flex; gap: 12px; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModalCrearDiaPrograma()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-success" onclick="guardarDiaEnPrograma()"
                        style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); border: none;">
                        <i class="fas fa-save"></i> Crear y Agregar
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal inline para crear un TRANSPORTE nuevo y asignarlo al día -->
        <div id="crearTransporteModalPrograma" class="modal" style="display: none;">
            <div class="modal-content" style="max-width: 900px; max-height: 95vh; overflow-y: auto;">
                <div class="modal-header"
                    style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);">
                    <h3><i class="fas fa-car"></i> Crear Nuevo Transporte</h3>
                    <button class="close-modal" onclick="cerrarCrearTransportePrograma()"
                        style="background: rgba(255,255,255,0.2);">&times;</button>
                </div>

                <div class="modal-body" style="padding: 30px;">
                    <form id="formCrearTransporteEnPrograma">
                        <div class="form-group">
                            <label>Medio de Transporte <span style="color: var(--primary-color);">*</span></label>
                            <select id="medio-crear-transporte" class="form-control" required>
                                <option value="">Seleccionar medio</option>
                                <option value="bus">Bus</option>
                                <option value="avion">Avión</option>
                                <option value="coche">Coche</option>
                                <option value="barco">Barco</option>
                                <option value="tren">Tren</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Título <span style="color: var(--primary-color);">*</span></label>
                            <input type="text" id="titulo-crear-transporte" class="form-control" required
                                placeholder="Ej: Vuelo París-Roma" maxlength="250">
                        </div>

                        <div class="form-group">
                            <label>Lugar de Salida <span style="color: var(--primary-color);">*</span></label>
                            <div style="position: relative;">
                                <input type="text" id="lugar-salida-crear-transporte" class="form-control" required
                                    placeholder="Buscar aeropuerto, estación, ciudad..." autocomplete="off">
                                <input type="hidden" id="lat-salida-crear-transporte">
                                <input type="hidden" id="lng-salida-crear-transporte">
                                <div id="preview-salida-crear-transporte"></div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Lugar de Llegada <span style="color: var(--primary-color);">*</span></label>
                            <div style="position: relative;">
                                <input type="text" id="lugar-llegada-crear-transporte" class="form-control" required
                                    placeholder="Buscar aeropuerto, estación, ciudad..." autocomplete="off">
                                <input type="hidden" id="lat-llegada-crear-transporte">
                                <input type="hidden" id="lng-llegada-crear-transporte">
                                <div id="preview-llegada-crear-transporte"></div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>⏱️ Duración</label>
                            <input type="text" id="duracion-crear-transporte" class="form-control"
                                placeholder="Ej: 2 horas 30 minutos">
                        </div>

                        <div class="form-group">
                            <label>Distancia (km)</label>
                            <input type="number" id="distancia-crear-transporte" class="form-control" step="0.01"
                                placeholder="Distancia en kilómetros">
                        </div>

                        <div class="form-group">
                            <label>Descripción</label>
                            <textarea id="descripcion-crear-transporte" rows="4" class="form-control"
                                placeholder="Detalles adicionales del transporte..." maxlength="3000"></textarea>
                        </div>
                    </form>
                </div>

                <div class="modal-footer"
                    style="padding: 20px 30px; display: flex; gap: 12px; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="cerrarCrearTransportePrograma()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-success" onclick="guardarTransporteEnPrograma()"
                        style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); border: none;">
                        <i class="fas fa-save"></i> Crear y Asignar
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal inline para crear una ACTIVIDAD nueva y asignarla al día -->
        <div id="crearActividadModalPrograma" class="modal" style="display: none;">
            <div class="modal-content" style="max-width: 900px; max-height: 95vh; overflow-y: auto;">
                <div class="modal-header"
                    style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);">
                    <h3><i class="fas fa-hiking"></i> Crear Nueva Actividad</h3>
                    <button class="close-modal" onclick="cerrarCrearActividadPrograma()"
                        style="background: rgba(255,255,255,0.2);">&times;</button>
                </div>

                <div class="modal-body" style="padding: 30px;">
                    <form id="formCrearActividadEnPrograma">
                        <div class="form-group">
                            <label>Nombre de la Actividad <span style="color: var(--primary-color);">*</span></label>
                            <input type="text" id="nombre-crear-actividad" class="form-control" required
                                placeholder="Ej: Tour Eiffel" maxlength="250">
                        </div>

                        <div class="form-group">
                            <label>Ubicación <span style="color: var(--primary-color);">*</span></label>
                            <div style="position: relative;">
                                <input type="text" id="ubicacion-crear-actividad" class="form-control" required
                                    placeholder="Buscar lugar, monumento, parque..." autocomplete="off">
                                <input type="hidden" id="lat-crear-actividad">
                                <input type="hidden" id="lng-crear-actividad">
                                <div id="preview-ubicacion-crear-actividad"></div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Descripción</label>
                            <textarea id="descripcion-crear-actividad" rows="5" class="form-control"
                                placeholder="Describe la actividad..." maxlength="3000"></textarea>
                        </div>

                        <div class="form-group">
                            <label>Imágenes (máximo 3, opcional)</label>
                            <div id="imageUploadActividad"></div>
                        </div>
                    </form>
                </div>

                <div class="modal-footer"
                    style="padding: 20px 30px; display: flex; gap: 12px; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="cerrarCrearActividadPrograma()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-success" onclick="guardarActividadEnPrograma()"
                        style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); border: none;">
                        <i class="fas fa-save"></i> Crear y Asignar
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal inline para crear un ALOJAMIENTO nuevo y asignarlo al día -->
        <div id="crearAlojamientoModalPrograma" class="modal" style="display: none;">
            <div class="modal-content" style="max-width: 900px; max-height: 95vh; overflow-y: auto;">
                <div class="modal-header"
                    style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);">
                    <h3><i class="fas fa-bed"></i> Crear Nuevo Alojamiento</h3>
                    <button class="close-modal" onclick="cerrarCrearAlojamientoPrograma()"
                        style="background: rgba(255,255,255,0.2);">&times;</button>
                </div>

                <div class="modal-body" style="padding: 30px;">
                    <form id="formCrearAlojamientoEnPrograma">
                        <div class="form-group">
                            <label>Nombre <span style="color: var(--primary-color);">*</span></label>
                            <input type="text" id="nombre-crear-alojamiento" class="form-control" required
                                placeholder="Ej: Hotel Le Marais" maxlength="250">
                        </div>

                        <div class="form-group">
                            <label>Tipo <span style="color: var(--primary-color);">*</span></label>
                            <select id="tipo-crear-alojamiento" class="form-control" required
                                onchange="toggleCategoriaAlojamiento()">
                                <option value="hotel">Hotel</option>
                                <option value="camping">Camping</option>
                                <option value="casa_huespedes">Casa de huéspedes</option>
                                <option value="crucero">Crucero</option>
                                <option value="lodge">Lodge</option>
                                <option value="atipico">Atípico</option>
                                <option value="campamento">Campamento</option>
                                <option value="camping_car">Camping car</option>
                                <option value="tren">Tren</option>
                            </select>
                        </div>

                        <div class="form-group" id="categoria-alojamiento-group">
                            <label>⭐ Categoría (estrellas)</label>
                            <select id="categoria-crear-alojamiento" class="form-control">
                                <option value="">Sin categoría</option>
                                <option value="1">1 estrella</option>
                                <option value="2">2 estrellas</option>
                                <option value="3">3 estrellas</option>
                                <option value="4">4 estrellas</option>
                                <option value="5">5 estrellas</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Ubicación</label>
                            <div style="position: relative;">
                                <input type="text" id="ubicacion-crear-alojamiento" class="form-control"
                                    placeholder="Buscar dirección, ciudad..." autocomplete="off">
                                <input type="hidden" id="lat-crear-alojamiento">
                                <input type="hidden" id="lng-crear-alojamiento">
                                <div id="preview-ubicacion-crear-alojamiento"></div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Sitio web</label>
                            <input type="text" id="sitio-web-crear-alojamiento" class="form-control"
                                placeholder="https://...">
                        </div>

                        <div class="form-group">
                            <label>Descripción <span style="color: var(--primary-color);">*</span></label>
                            <textarea id="descripcion-crear-alojamiento" rows="5" class="form-control" required
                                placeholder="Describe el alojamiento..." maxlength="3000"></textarea>
                        </div>

                        <div class="form-group">
                            <label>Imagen (opcional)</label>
                            <div id="imageUploadAlojamiento"></div>
                        </div>
                    </form>
                </div>

                <div class="modal-footer"
                    style="padding: 20px 30px; display: flex; gap: 12px; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="cerrarCrearAlojamientoPrograma()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-success" onclick="guardarAlojamientoEnPrograma()"
                        style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); border: none;">
                        <i class="fas fa-save"></i> Crear y Asignar
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal para agregar servicios (actividades, transporte, alojamiento) -->
        <div id="serviciosModal" class="modal" style="display: none;">
            <div class="modal-content" style="max-width: 1200px; max-height: 90vh; overflow-y: auto;">
                <div class="modal-header">
                    <h3 id="servicios-modal-title"><i class="fas fa-plus"></i> Agregar servicio</h3>
                    <button class="close-modal" onclick="cerrarModalServicios()">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="biblioteca-filters">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" placeholder="Buscar servicios..." id="search-servicios"
                                class="form-control">
                        </div>
                    </div>
                    <div id="servicios-grid" class="biblioteca-grid">
                        <!-- Los servicios de la biblioteca se cargarán aquí -->
                    </div>
                    <div id="acomodaciones-selector-wrapper" class="acomodaciones-selector-wrapper"
                        style="display: none;">
                        <div class="acomodaciones-selector-card">
                            <div class="acomodaciones-selector-header">
                                <div>
                                    <h4><i class="fas fa-bed"></i> Acomodación</h4>
                                    <p>Opcional. Puedes agregar el alojamiento sin acomodación y definirla después.</p>
                                </div>
                                <button type="button" class="btn btn-secondary btn-sm"
                                    onclick="abrirCrearAcomodacionDesdePrograma()">
                                    <i class="fas fa-plus"></i> Nueva acomodación
                                </button>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Seleccionar acomodación</label>
                                <select id="select-acomodacion-servicio" class="form-control">
                                    <option value="">Sin acomodación por ahora</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="crearNuevoServicioInline()" id="btn-crear-nuevo-servicio"
                        style="margin-right:auto;">
                        <i class="fas fa-plus-circle"></i> Crear nuevo
                    </button>
                    <button class="btn btn-secondary" onclick="cerrarModalServicios()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button class="btn btn-primary" onclick="agregarServicioSeleccionado()" id="btn-agregar-servicio"
                        disabled>
                        <i class="fas fa-plus"></i> Agregar servicio
                    </button>
                </div>
            </div>
        </div>



        <!-- Modal para agregar Acomodación) -->
        <div id="modal-editar-alojamiento" class="modal" style="display:none;">
            <div class="modal-content" style="max-width:560px;">
                <div class="modal-header">
                    <h3><i class="fas fa-bed"></i> Acomodación del alojamiento</h3>
                    <button class="close-modal" onclick="cerrarModalEditarAlojamiento()">&times;</button>
                </div>

                <div class="modal-body">
                    <input type="hidden" id="edit-alojamiento-id">

                    <div class="form-group">
                        <label class="form-label">Alojamiento</label>
                        <div id="edit-alojamiento-nombre-display" class="readonly-hotel-box">
                            Hotel seleccionado
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Acomodación</label>
                        <div style="display:flex; gap:10px;">
                            <select id="edit-alojamiento-acomodacion" class="form-control">
                                <option value="">Sin acomodación</option>
                            </select>

                            <button type="button" class="btn btn-secondary"
                                onclick="abrirCrearAcomodacionDesdeEditor()">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        <small class="form-text text-muted">
                            Puedes dejarlo sin acomodación y definirla después.
                        </small>
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="cerrarModalEditarAlojamiento()">Cancelar</button>
                    <button class="btn btn-primary" onclick="guardarEdicionAlojamiento()">
                        <i class="fas fa-save"></i> Guardar acomodación
                    </button>
                </div>
            </div>
        </div>

        <div id="modal-crear-acomodacion-programa" class="modal" style="display: none;">
            <div class="modal-content" style="max-width: 560px;">
                <div class="modal-header">
                    <h3><i class="fas fa-bed"></i> Nueva acomodación</h3>
                    <button type="button" class="close-modal"
                        onclick="cerrarCrearAcomodacionDesdePrograma()">&times;</button>
                </div>

                <div class="modal-body">
                    <p class="form-text text-muted">
                        Esta acomodación se guardará en la biblioteca del alojamiento seleccionado.
                    </p>

                    <div class="form-group">
                        <label class="form-label">Tipo de acomodación</label>
                        <input type="text" id="nueva-acomodacion-tipo" class="form-control"
                            placeholder="Ej: Habitación doble">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Descripción <small>(opcional)</small></label>
                        <input type="text" id="nueva-acomodacion-descripcion" class="form-control"
                            placeholder="Ej: 1 cama doble o 2 sencillas">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Capacidad</label>
                        <input type="number" id="nueva-acomodacion-capacidad" class="form-control" min="1" value="1">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary"
                        onclick="cerrarCrearAcomodacionDesdePrograma()">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="guardarNuevaAcomodacionDesdePrograma()">
                        <i class="fas fa-save"></i> Guardar acomodación
                    </button>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
        <!-- Scripts -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>



        <script>
            // Variables globales para el modal de crear día
            let widgetUbicacionPrincipalPrograma = null;
            let widgetsSecundariasPrograma = [];
            let contadorSecundariasPrograma = 0;
            let selectedImagesPrograma = [];
            let diasSeleccionados = []; // Array para almacenar IDs de días seleccionados
            let ordenSeleccion = 1; // Contador para el orden de selección

            // Función helper para obtener la URL base
            function getBaseURL() {
                const base = document.querySelector('base');
                if (base && base.href) {
                    return base.href.replace(/\/$/, '');
                }

                // Obtener la ruta completa incluyendo subdirectorios
                const pathname = window.location.pathname;
                const pathParts = pathname.split('/').filter(p => p);

                // Si estamos en programa.php, quitar el archivo y quedarnos con la carpeta
                if (pathParts[pathParts.length - 1] === 'programa.php' ||
                    pathParts[pathParts.length - 1].startsWith('programa')) {
                    pathParts.pop();
                }

                const protocol = window.location.protocol;
                const host = window.location.host;
                const basePath = pathParts.length > 0 ? '/' + pathParts.join('/') : '';

                return `${protocol}//${host}${basePath}`;
            }

            // Abrir modal
            function abrirModalCrearDiaPrograma() {
                console.log('🎬 Abriendo modal de crear día...');

                const modal = document.getElementById('crearDiaModalPrograma');
                const modalBiblioteca = document.getElementById('bibliotecaModal');

                if (modalBiblioteca) {
                    modalBiblioteca.style.zIndex = '9998';
                }

                modal.style.display = 'flex';
                modal.style.alignItems = 'center';
                modal.style.justifyContent = 'center';
                modal.style.zIndex = '10000';

                setTimeout(() => {
                    modal.classList.add('show');
                }, 10);

                setTimeout(() => {
                    inicializarFormularioPrograma();
                }, 100);
            }

            // Cerrar modal
            function cerrarModalCrearDiaPrograma() {
                const modal = document.getElementById('crearDiaModalPrograma');
                const modalBiblioteca = document.getElementById('bibliotecaModal');

                modal.classList.remove('show');

                setTimeout(() => {
                    modal.style.display = 'none';
                    if (modalBiblioteca) {
                        modalBiblioteca.style.zIndex = '9999';
                    }
                }, 300);

                limpiarFormularioPrograma();
            }

            // Inicializar formulario
            function inicializarFormularioPrograma() {
                console.log('🔧 Inicializando formulario...');

                limpiarFormularioPrograma();

                setTimeout(() => {
                    inicializarWidgetUbicacionPrograma();
                    inicializarSistemaImagenesPrograma();
                    inicializarContadoresPrograma();
                }, 200);
            }

            // Inicializar widget de ubicación principal
            function inicializarWidgetUbicacionPrograma() {
                const inputPrincipal = document.getElementById('ubicacion-principal-crear-programa');

                if (!inputPrincipal) {
                    console.error('❌ Input de ubicación principal no encontrado');
                    return;
                }

                console.log('📍 Inicializando widget de ubicación principal...');

                if (widgetUbicacionPrincipalPrograma) {
                    widgetUbicacionPrincipalPrograma.destroy();
                    widgetUbicacionPrincipalPrograma = null;
                }

                if (typeof UbicacionSearchWidget === 'undefined') {
                    console.error('❌ UbicacionSearchWidget no está cargado');
                    return;
                }

                try {
                    const baseURL = getBaseURL();

                    widgetUbicacionPrincipalPrograma = new UbicacionSearchWidget(inputPrincipal, {
                        apiUrl: `${baseURL}/modules/ubicaciones/ubicaciones_api.php`,
                        latInputId: 'latitud-principal-programa',
                        lngInputId: 'longitud-principal-programa',
                        placeholder: 'Buscar ciudad, lugar, monumento...',
                        showPreview: true,
                        previewContainerId: 'preview-ubicacion-principal-programa',
                        autoSave: true,
                        minChars: 3,
                        debounceTime: 300,
                        onSelect: (location) => {
                            console.log('✅ Ubicación seleccionada:', location);
                        }
                    });

                    console.log('✅ Widget inicializado con URL:', `${baseURL}/modules/ubicaciones/ubicaciones_api.php`);
                } catch (error) {
                    console.error('❌ Error al crear widget:', error);
                }
            }

            // Agregar ubicación secundaria
            function agregarUbicacionSecundariaPrograma() {
                contadorSecundariasPrograma++;
                const index = Date.now() + contadorSecundariasPrograma;

                const container = document.getElementById('ubicaciones-secundarias-container-programa');

                const div = document.createElement('div');
                div.className = 'ubicacion-secundaria-item';
                div.setAttribute('data-index', index);
                div.style.cssText = 'display: grid; grid-template-columns: 1fr auto; gap: 10px; padding: 12px; background: #f8fafc; border-radius: 10px; border: 2px dashed #cbd5e0;';

                div.innerHTML = `
        <div style="position: relative;">
            <input type="text" 
                id="ubicacion-sec-${index}-programa"
                class="form-control"
                placeholder="Buscar ubicación adicional..."
                autocomplete="off"
                style="padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 10px;">
            <input type="hidden" id="lat-sec-${index}-programa">
            <input type="hidden" id="lng-sec-${index}-programa">
            <div id="preview-sec-${index}-programa"></div>
        </div>
        <button type="button" onclick="eliminarUbicacionSecundariaPrograma(${index})" 
                style="width: 40px; height: 40px; background: var(--primary-color); color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 18px;">
            ✕
        </button>
    `;

                container.appendChild(div);

                setTimeout(() => {
                    const input = document.getElementById(`ubicacion-sec-${index}-programa`);
                    if (input && typeof UbicacionSearchWidget !== 'undefined') {
                        try {
                            const baseURL = getBaseURL();

                            const widget = new UbicacionSearchWidget(input, {
                                apiUrl: `${baseURL}/modules/ubicaciones/ubicaciones_api.php`,
                                latInputId: `lat-sec-${index}-programa`,
                                lngInputId: `lng-sec-${index}-programa`,
                                placeholder: 'Buscar otra ubicación...',
                                showPreview: true,
                                previewContainerId: `preview-sec-${index}-programa`,
                                autoSave: true,
                                minChars: 3
                            });

                            widgetsSecundariasPrograma.push({ index, widget });
                            console.log(`✅ Widget secundario ${index} inicializado`);
                        } catch (error) {
                            console.error(`❌ Error al crear widget secundario:`, error);
                        }
                    }
                }, 100);
            }

            // Eliminar ubicación secundaria
            function eliminarUbicacionSecundariaPrograma(index) {
                const item = document.querySelector(`.ubicacion-secundaria-item[data-index="${index}"]`);
                if (item) {
                    const widgetData = widgetsSecundariasPrograma.find(w => w.index === index);
                    if (widgetData && widgetData.widget) {
                        widgetData.widget.destroy();
                    }

                    widgetsSecundariasPrograma = widgetsSecundariasPrograma.filter(w => w.index !== index);
                    item.remove();

                    console.log(`🗑️ Ubicación secundaria ${index} eliminada`);
                }
            }

            // Inicializar sistema de imágenes
            function inicializarSistemaImagenesPrograma() {
                const container = document.getElementById('imageUploadContainerPrograma');
                if (!container) return;

                selectedImagesPrograma = [];

                container.innerHTML = `
        <div id="dropZonePrograma" class="drop-zone-multiple" 
             style="border: 2px dashed #cbd5e0; border-radius: 12px; padding: 40px; text-align: center; background: linear-gradient(135deg, #f8fafc 0%, #edf2f7 100%); cursor: pointer;">
            <div class="drop-zone-content">
                <div style="font-size: 48px; margin-bottom: 15px;">📸</div>
                <div style="font-size: 18px; font-weight: 600; margin-bottom: 8px;">Arrastra hasta 3 imágenes aquí</div>
                <div style="font-size: 14px; color: #718096; margin-bottom: 15px;">o haz clic para seleccionar</div>
                <button type="button" class="btn-select-images" 
                        style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600;">
                    📂 Seleccionar Imágenes
                </button>
            </div>
        </div>
        <div id="imagesPreviewPrograma" style="margin-top: 20px; display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px;"></div>
    `;

                const dropZone = document.getElementById('dropZonePrograma');
                const fileInput = document.getElementById('multipleImages-programa');

                if (dropZone && fileInput) {
                    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(e => {
                        dropZone.addEventListener(e, (ev) => { ev.preventDefault(); ev.stopPropagation(); });
                    });

                    ['dragenter', 'dragover'].forEach(e => {
                        dropZone.addEventListener(e, () => {
                            dropZone.style.borderColor = 'var(--primary-color)';
                            dropZone.style.background = 'linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%)';
                        });
                    });

                    ['dragleave', 'drop'].forEach(e => {
                        dropZone.addEventListener(e, () => {
                            dropZone.style.borderColor = '#cbd5e0';
                            dropZone.style.background = 'linear-gradient(135deg, #f8fafc 0%, #edf2f7 100%)';
                        });
                    });

                    dropZone.addEventListener('drop', (e) => {
                        handleFilesPrograma(Array.from(e.dataTransfer.files));
                    });

                    dropZone.addEventListener('click', (e) => {
                        if (e.target.classList.contains('btn-select-images')) {
                            e.preventDefault();
                            e.stopPropagation();
                            fileInput.click();
                        }
                    });

                    fileInput.addEventListener('change', function () {
                        handleFilesPrograma(Array.from(this.files));
                    });
                }
            }

            function handleFilesPrograma(files) {
                const imageFiles = files.filter(f => f.type.startsWith('image/'));
                if (imageFiles.length === 0) return alert('Solo archivos de imagen');

                const availableSlots = 3 - selectedImagesPrograma.length;
                if (availableSlots === 0) return alert('Ya tienes 3 imágenes');

                const filesToAdd = imageFiles.slice(0, availableSlots);

                filesToAdd.forEach(file => {
                    if (file.size > 10 * 1024 * 1024) {
                        alert(`"${file.name}" es muy grande (máx. 10MB)`);
                        return;
                    }
                    selectedImagesPrograma.push(file);
                });

                actualizarPreviewPrograma();
                asignarArchivosPrograma();
            }

            function actualizarPreviewPrograma() {
                const container = document.getElementById('imagesPreviewPrograma');
                if (!container) return;

                container.innerHTML = '';

                selectedImagesPrograma.forEach((file, index) => {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        const div = document.createElement('div');
                        div.style.cssText = 'position: relative; border-radius: 12px; overflow: hidden; border: 2px solid #e2e8f0;';
                        div.innerHTML = `
                <img src="${e.target.result}" style="width: 100%; height: 150px; object-fit: cover;">
                <div style="padding: 8px; background: white;">
                    <div style="font-size: 12px; font-weight: 600;">${file.name}</div>
                    <div style="font-size: 11px; color: #718096;">${(file.size / 1024).toFixed(0)} KB</div>
                </div>
                <button onclick="eliminarImagenPrograma(${index})" 
                        style="position: absolute; top: 8px; right: 8px; background: var(--primary-color); color: white; border: none; border-radius: 50%; width: 28px; height: 28px; cursor: pointer;">×</button>
                <div style="position: absolute; top: 8px; left: 8px; background: rgba(16,185,129,0.9); color: white; padding: 4px 8px; border-radius: 6px; font-size: 11px;">Imagen ${index + 1}</div>
            `;
                        container.appendChild(div);
                    };
                    reader.readAsDataURL(file);
                });

                const dropContent = document.querySelector('#dropZonePrograma .drop-zone-content');
                if (selectedImagesPrograma.length < 3) {
                    dropContent.innerHTML = `
            <div style="font-size: ${selectedImagesPrograma.length === 0 ? 48 : 36}px; margin-bottom: ${selectedImagesPrograma.length === 0 ? 15 : 10}px;">${selectedImagesPrograma.length === 0 ? '📸' : '✅'}</div>
            <div style="font-size: ${selectedImagesPrograma.length === 0 ? 18 : 16}px; font-weight: 600; margin-bottom: 8px;">
                ${selectedImagesPrograma.length === 0 ? 'Arrastra hasta 3 imágenes' : selectedImagesPrograma.length + ' imagen(es) seleccionada(s)'}
            </div>
            <div style="font-size: 14px; color: #718096; margin-bottom: 15px;">
                ${selectedImagesPrograma.length === 0 ? 'o haz clic para seleccionar' : 'Puedes agregar ' + (3 - selectedImagesPrograma.length) + ' más'}
            </div>
            <button type="button" class="btn-select-images" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600;">
                📂 ${selectedImagesPrograma.length === 0 ? 'Seleccionar Imágenes' : 'Agregar Más'}
            </button>
        `;
                } else {
                    dropContent.innerHTML = `
            <div style="font-size: 36px; margin-bottom: 10px;">🎉</div>
            <div style="font-size: 16px; font-weight: 600;">3 imágenes completas</div>
        `;
                }
            }

            function eliminarImagenPrograma(index) {
                selectedImagesPrograma.splice(index, 1);
                actualizarPreviewPrograma();
                asignarArchivosPrograma();
            }

            function asignarArchivosPrograma() {
                const inputs = [
                    document.getElementById('imagenes-programa'),
                    document.getElementById('imagen2-programa'),
                    document.getElementById('imagen3-programa')
                ];

                selectedImagesPrograma.forEach((file, i) => {
                    if (inputs[i]) {
                        const dt = new DataTransfer();
                        dt.items.add(file);
                        inputs[i].files = dt.files;
                    }
                });

                for (let i = selectedImagesPrograma.length; i < 3; i++) {
                    if (inputs[i]) inputs[i].value = '';
                }
            }

            // Inicializar contadores
            function inicializarContadoresPrograma() {
                const titulo = document.getElementById('titulo-crear-programa');
                const tituloCounter = document.getElementById('titulo-counter-programa');
                if (titulo && tituloCounter) {
                    titulo.addEventListener('input', function () {
                        const count = this.value.length;
                        tituloCounter.textContent = `${count}/300`;
                        tituloCounter.style.color = count > 270 ? 'var(--primary-color)' : '#6b7280';
                    });
                }

                const descripcion = document.getElementById('descripcion-crear-programa');
                const descripcionCounter = document.getElementById('descripcion-counter-programa');
                if (descripcion && descripcionCounter) {
                    descripcion.addEventListener('input', function () {
                        const count = this.value.length;
                        descripcionCounter.textContent = `${count}/3000`;
                        descripcionCounter.style.color = count > 2700 ? 'var(--primary-color)' : '#6b7280';
                    });
                }
            }

            // Limpiar formulario
            function limpiarFormularioPrograma() {
                const form = document.getElementById('formCrearDiaEnPrograma');
                if (form) form.reset();

                if (widgetUbicacionPrincipalPrograma) {
                    widgetUbicacionPrincipalPrograma.destroy();
                    widgetUbicacionPrincipalPrograma = null;
                }

                widgetsSecundariasPrograma.forEach(w => {
                    if (w.widget) w.widget.destroy();
                });
                widgetsSecundariasPrograma = [];
                contadorSecundariasPrograma = 0;

                const containerSec = document.getElementById('ubicaciones-secundarias-container-programa');
                if (containerSec) containerSec.innerHTML = '';

                selectedImagesPrograma = [];
                const imgContainer = document.getElementById('imageUploadContainerPrograma');
                if (imgContainer) imgContainer.innerHTML = '';
            }

            async function guardarDiaEnPrograma() {
                const btn = event.target;  // ← MOVER AQUÍ ARRIBA (fuera del try)
                const originalHTML = btn.innerHTML;

                try {
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

                    const titulo = document.getElementById('titulo-crear-programa').value.trim();
                    const ubicacion = document.getElementById('ubicacion-principal-crear-programa').value.trim();

                    if (!titulo) {
                        alert('Por favor ingresa un título');
                        btn.disabled = false;
                        btn.innerHTML = originalHTML;
                        return;
                    }

                    if (!ubicacion) {
                        alert('Por favor selecciona una ubicación');
                        btn.disabled = false;
                        btn.innerHTML = originalHTML;
                        return;
                    }

                    const formData = new FormData();
                    formData.append('action', 'create');
                    formData.append('type', 'dias');
                    formData.append('idioma', document.getElementById('idioma-crear-programa').value);
                    formData.append('titulo', titulo);
                    formData.append('descripcion', document.getElementById('descripcion-crear-programa').value);
                    formData.append('ubicacion', ubicacion);
                    formData.append('latitud', document.getElementById('latitud-principal-programa').value);
                    formData.append('longitud', document.getElementById('longitud-principal-programa').value);

                    // Ubicaciones secundarias
                    const ubicacionesSecundarias = [];
                    const inputsSec = document.querySelectorAll('[id^="ubicacion-sec-"][id$="-programa"]');
                    inputsSec.forEach((input, idx) => {
                        if (input.value.trim()) {
                            const dataIndex = input.closest('[data-index]').getAttribute('data-index');
                            const latInput = document.getElementById(`lat-sec-${dataIndex}-programa`);
                            const lngInput = document.getElementById(`lng-sec-${dataIndex}-programa`);

                            ubicacionesSecundarias.push({
                                ubicacion: input.value,
                                latitud: latInput ? latInput.value : null,
                                longitud: lngInput ? lngInput.value : null,
                                orden: idx + 1
                            });
                        }
                    });

                    if (ubicacionesSecundarias.length > 0) {
                        formData.append('ubicaciones_secundarias', JSON.stringify(ubicacionesSecundarias));
                    }

                    // Imágenes
                    for (let i = 1; i <= 3; i++) {
                        const fileInput = document.getElementById(`imagen${i === 1 ? 'es' : i}-programa`);
                        if (fileInput && fileInput.files[0]) {
                            formData.append(`imagen${i}`, fileInput.files[0]);
                        }
                    }

                    console.log('📤 Creando día en biblioteca...');

                    const baseURL = getBaseURL();

                    // 1. Crear en biblioteca
                    const response = await fetch(`${baseURL}/biblioteca/api`, {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (!result.success) {
                        throw new Error(result.error || 'Error al crear día');
                    }

                    const nuevoDiaId = result.id;
                    console.log('✅ Día creado con ID:', nuevoDiaId);

                    // 2. Agregar al programa
                    console.log('📤 Agregando al programa con solicitud_id:', programaId);

                    const responsePrograma = await fetch(`${baseURL}/modules/programa/dias_api.php`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'add_from_biblioteca',
                            programa_id: parseInt(programaId),           // ← CAMBIO: programa_id
                            biblioteca_dia_id: parseInt(nuevoDiaId)
                        })
                    });

                    const resultPrograma = await responsePrograma.json();
                    console.log('📥 Respuesta programa:', resultPrograma);

                    if (!resultPrograma.success) {
                        throw new Error(resultPrograma.message || resultPrograma.error || 'Error al agregar al programa');
                    }

                    showAlert('✅ Día creado y agregado exitosamente', 'success');
                    cerrarModalCrearDiaPrograma();
                    if (typeof cerrarModalBiblioteca === 'function') cerrarModalBiblioteca();
                    if (typeof cargarDiasPrograma === 'function') await cargarDiasPrograma();

                } catch (error) {
                    console.error('❌ Error completo:', error);
                    alert('Error: ' + error.message);
                    btn.disabled = false;  // ← AHORA SÍ EXISTE btn
                    btn.innerHTML = originalHTML;
                }
            }

            console.log('✅ Script de crear día en programa V4 inicializado');
        </script>

        <script>
            // ====================================================================
            // SCRIPT JAVASCRIPT COMPLETO CORREGIDO PARA PROGRAMA.PHP
            // ====================================================================

            // Variables globales
            let currentTab = 'mi-programa';
            let programaId = <?= $programa_id ? $programa_id : 'null' ?>;
            let isEditing = <?= $is_editing ? 'true' : 'false' ?>;

            // ====================================================================
            // ETIQUETAS INLINE DEL EDITOR (reusa modules/itinerarios/tags_api.php)
            // ====================================================================
            const TAG_PALETTE_ED = ['#6366f1', '#0ea5e9', '#10b981', '#f59e0b', '#ef4444', '#ec4899', '#8b5cf6', '#14b8a6', '#f97316', '#64748b'];
            function etColor(id) { const n = TAG_PALETTE_ED.length; return TAG_PALETTE_ED[((id - 1) % n + n) % n]; }
            function escEditorTag(s) { return String(s == null ? '' : s).replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c])); }
            let editorAllTags = [];
            let editorSelTags = new Set();
            let editorTagsDirty = false; // hay selección sin persistir (programa aún sin id)

            async function initEditorTags() {
                const cont = document.getElementById('editorTagChips');
                if (!cont) return;
                try {
                    const rAll = await fetch(APP_URL + '/modules/itinerarios/tags_api.php?action=get_tags');
                    const dAll = await rAll.json();
                    editorAllTags = (dAll && dAll.success) ? (dAll.data || []) : [];
                    editorSelTags = new Set();
                    if (programaId) {
                        const rA = await fetch(APP_URL + '/modules/itinerarios/tags_api.php?action=get_tags_programa&programa_id=' + encodeURIComponent(programaId));
                        const dA = await rA.json();
                        if (dA && dA.success) (dA.data || []).forEach(t => editorSelTags.add(Number(t.id)));
                    }
                } catch (e) { editorAllTags = []; }
                renderEditorTagChips();
            }

            function renderEditorTagChips() {
                const cont = document.getElementById('editorTagChips');
                if (!cont) return;
                if (!editorAllTags.length) {
                    cont.innerHTML = '<span class="etc-empty">No hay etiquetas todavía. Crea una abajo.</span>';
                    return;
                }
                cont.innerHTML = editorAllTags.map(t => {
                    const id = Number(t.id);
                    const sel = editorSelTags.has(id);
                    const c = etColor(id);
                    return `<span class="etc-chip${sel ? ' selected' : ''}" style="--c:${c}" onclick="toggleEditorTag(${id})">${escEditorTag(t.nombre)}</span>`;
                }).join('');
            }

            function toggleEditorTag(id) {
                id = Number(id);
                if (editorSelTags.has(id)) editorSelTags.delete(id); else editorSelTags.add(id);
                renderEditorTagChips();
                const hint = document.getElementById('editorTagHint');
                if (programaId) {
                    persistEditorTags().then(ok => { if (hint) hint.textContent = ok ? 'Etiquetas guardadas ✓' : 'No se pudieron guardar las etiquetas'; });
                } else {
                    editorTagsDirty = true;
                    if (hint) hint.textContent = 'Se guardarán al crear el programa.';
                }
            }

            async function persistEditorTags() {
                if (!programaId) return false;
                try {
                    const r = await fetch(APP_URL + '/modules/itinerarios/tags_api.php?action=save_tags_programa', {
                        method: 'POST', headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ solicitud_id: programaId, tag_id: [...editorSelTags] })
                    });
                    const d = await r.json();
                    if (d && d.success) editorTagsDirty = false;
                    return !!(d && d.success);
                } catch (e) { return false; }
            }

            // Persiste las etiquetas elegidas en un programa recién creado (llamado desde guardarPrograma)
            function flushEditorTagsAfterCreate() {
                if (programaId && editorTagsDirty && editorSelTags.size) {
                    persistEditorTags().then(ok => {
                        const h = document.getElementById('editorTagHint');
                        if (h) h.textContent = ok ? 'Etiquetas guardadas ✓' : 'Vuelve a tocar una etiqueta para guardarla';
                    });
                }
            }

            // Crear una etiqueta nueva DESDE programa (antes solo se podían crear en el listado).
            // Usa la misma API (tags_api.php?action=save_tags) y recarga la lista SIN perder
            // la selección actual; auto-selecciona la recién creada.
            async function crearEtiquetaEditor() {
                const inp = document.getElementById('editorTagNew');
                const hint = document.getElementById('editorTagHint');
                const btn = document.getElementById('editorTagCreateBtn');
                const nombre = (inp && inp.value || '').trim();
                if (!nombre) { if (hint) hint.textContent = 'Escribe un nombre para la etiqueta.'; if (inp) inp.focus(); return; }
                if (btn) btn.disabled = true;
                try {
                    const r = await fetch(APP_URL + '/modules/itinerarios/tags_api.php?action=save_tags', {
                        method: 'POST', headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ nombre })
                    });
                    const d = await r.json();
                    if (!d || !d.success) {
                        if (hint) hint.textContent = (d && d.message) ? d.message : 'No se pudo crear la etiqueta.';
                        return;
                    }
                    // Recargar la lista de etiquetas sin tocar editorSelTags (no perder selección)
                    const rAll = await fetch(APP_URL + '/modules/itinerarios/tags_api.php?action=get_tags');
                    const dAll = await rAll.json();
                    if (dAll && dAll.success) editorAllTags = dAll.data || editorAllTags;
                    // Auto-seleccionar la recién creada (match por nombre, mayor id)
                    const nueva = editorAllTags
                        .filter(t => String(t.nombre).trim().toLowerCase() === nombre.toLowerCase())
                        .sort((a, b) => Number(b.id) - Number(a.id))[0];
                    if (nueva) editorSelTags.add(Number(nueva.id));
                    if (inp) inp.value = '';
                    renderEditorTagChips();
                    if (programaId) {
                        const ok = await persistEditorTags();
                        if (hint) hint.textContent = ok ? 'Etiqueta creada y aplicada ✓' : 'Etiqueta creada (no se pudo aplicar)';
                    } else {
                        editorTagsDirty = true;
                        if (hint) hint.textContent = 'Etiqueta creada. Se aplicará al crear el programa.';
                    }
                } catch (e) {
                    if (hint) hint.textContent = 'Error al crear la etiqueta.';
                } finally {
                    if (btn) btn.disabled = false;
                }
            }
            window.crearEtiquetaEditor = crearEtiquetaEditor;

            if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initEditorTags);
            else initEditorTags();

            // ====================================================================
            // LEAD VINCULADO (editor): vincular un lead del pipeline + abrir su chat.
            // El vínculo vive en pipeline.solicitud_id → bidireccional con el pipeline.
            // ====================================================================
            const PIPE_API = APP_URL + '/pipeline/api';
            function lvEsc(s) { return String(s == null ? '' : s).replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c])); }

            async function initLeadVinculo() {
                const box = document.getElementById('leadVinculoBox');
                if (!box) return;
                if (!programaId) {
                    box.innerHTML = '<span class="lv-empty">Guarda el programa primero para poder vincular un lead.</span>';
                    return;
                }
                box.innerHTML = '<span class="lv-empty">Cargando…</span>';
                let leads = [];
                try {
                    const r = await fetch(PIPE_API + '?action=filtrar_pipeline&solicitud_id=' + encodeURIComponent(programaId));
                    const d = await r.json();
                    leads = (d && d.success) ? (d.data || []) : [];
                } catch (e) { leads = []; }
                renderLeadVinculo(leads);
            }

            function renderLeadVinculo(leads) {
                const box = document.getElementById('leadVinculoBox');
                if (!box) return;
                if (leads && leads.length) {
                    box.innerHTML = leads.map(l => `
                        <div class="lv-linked">
                            <div class="lv-info"><strong>${lvEsc(l.nombre_cliente || 'Lead')}</strong><span>${lvEsc(l.email_cliente || '')}</span></div>
                            <div class="lv-actions">
                                <button type="button" class="lv-btn lv-btn-primary" onclick="lvAbrirChat(${l.id})"><i class="fas fa-comments"></i> Abrir chat</button>
                                <button type="button" class="lv-btn lv-btn-ghost" onclick="lvDesvincular(${l.id})"><i class="fas fa-link-slash"></i> Desvincular</button>
                            </div>
                        </div>`).join('');
                    return;
                }
                box.innerHTML = `
                    <button type="button" class="lv-btn lv-btn-primary" onclick="lvAbrirPicker()"><i class="fas fa-link"></i> Vincular lead</button>
                    <div id="lvPicker" class="lv-picker" style="display:none;">
                        <input type="text" class="lv-search" placeholder="Buscar lead por nombre, email o destino…" oninput="lvBuscar(this.value)">
                        <div id="lvResults" class="lv-results"></div>
                    </div>`;
            }

            function lvAbrirPicker() {
                const p = document.getElementById('lvPicker');
                if (p) { p.style.display = 'block'; p.querySelector('.lv-search')?.focus(); lvBuscar(''); }
            }

            let _lvTimer = null;
            function lvBuscar(term) {
                clearTimeout(_lvTimer);
                _lvTimer = setTimeout(async () => {
                    const cont = document.getElementById('lvResults');
                    if (!cont) return;
                    cont.innerHTML = '<span class="lv-empty">Buscando…</span>';
                    let leads = [];
                    try {
                        const qs = term ? ('&buscar=' + encodeURIComponent(term)) : '';
                        const r = await fetch(PIPE_API + '?action=filtrar_pipeline' + qs);
                        const d = await r.json();
                        leads = (d && d.success) ? (d.data || []) : [];
                    } catch (e) { leads = []; }
                    if (!leads.length) { cont.innerHTML = '<span class="lv-empty">Sin resultados.</span>'; return; }
                    cont.innerHTML = leads.slice(0, 30).map(l => {
                        const yaAqui = String(l.solicitud_id || '') === String(programaId);
                        const otro = l.solicitud_id && !yaAqui;
                        return `<button type="button" class="lv-result${yaAqui ? ' linked' : ''}" ${yaAqui ? '' : `onclick="lvVincular(${l.id})"`}>
                            <strong>${lvEsc(l.nombre_cliente || 'Lead')}${yaAqui ? ' · (ya vinculado aquí)' : ''}</strong>
                            <span>${lvEsc(l.email_cliente || '')}${l.destino ? (' · ' + lvEsc(l.destino)) : ''}${otro ? ' · (vinculado a otro itinerario)' : ''}</span>
                        </button>`;
                    }).join('');
                }, 250);
            }

            async function lvVincular(pipelineId) {
                if (!programaId) return;
                try {
                    const r = await fetch(PIPE_API + '?action=asignar_itinerario', {
                        method: 'POST', headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ pipeline_id: pipelineId, solicitud_id: programaId })
                    });
                    const d = await r.json();
                    if (d && d.success) { if (typeof showAlert === 'function') showAlert('Lead vinculado', 'success'); initLeadVinculo(); }
                    else if (typeof showAlert === 'function') showAlert((d && d.message) || 'No se pudo vincular', 'error');
                } catch (e) { if (typeof showAlert === 'function') showAlert('Error de red', 'error'); }
            }

            async function lvDesvincular(pipelineId) {
                try {
                    const r = await fetch(PIPE_API + '?action=desvincular_itinerario', {
                        method: 'POST', headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ pipeline_id: pipelineId })
                    });
                    const d = await r.json();
                    if (d && d.success) { if (typeof showAlert === 'function') showAlert('Lead desvinculado', 'success'); initLeadVinculo(); }
                    else if (typeof showAlert === 'function') showAlert((d && d.message) || 'No se pudo desvincular', 'error');
                } catch (e) { if (typeof showAlert === 'function') showAlert('Error de red', 'error'); }
            }

            // Abre el lead en el pipeline (con su chat integrado), no el chat.php standalone
            function lvAbrirChat(pipelineId) { window.open(APP_URL + '/pipeline?lead=' + pipelineId, '_blank'); }

            if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initLeadVinculo);
            else initLeadVinculo();

            let selectedDiaId = null;
            let selectedServicioId = null;
            let currentDiaId = null;
            let currentTipoServicio = null;
            let selectedAcomodacionId = null;
            let acomodacionesHotelActual = [];
            let isAddingAlternative = false;
            let alternativeParentId = null;
            let diasPrograma = [];
            let titularId = <?= !empty($form_data['titular_id']) ? intval($form_data['titular_id']) : 'null' ?>;
            let viajerosSeleccionados = <?= json_encode($form_data['viajeros_asociados'] ?? [], JSON_UNESCAPED_UNICODE) ?>;
            let titularTambienViaja = viajerosSeleccionados.some(v => titularId && parseInt(v.id) === parseInt(titularId));
            let titularData = <?= json_encode($form_data['titular_data'] ?? null, JSON_UNESCAPED_UNICODE) ?>;
            let telefonoInputInstance = null;
            let alojamientoEditando = null;

            document.addEventListener('DOMContentLoaded', async function () {
                console.log('🚀 Iniciando programa.php...');
                setupTabNavigation();
                setupAdjuntos();
                setupFormHandling();
                setupCharacterCounters();
                setupFileValidation();
                // setupPreviewUpdates(); // ← ELIMINADO porque updatePreview no existe
                setupMealHandlers();
                setupViajerosHandlers();

                if (isEditing && programaId) {
                    console.log(`📋 Cargando datos para programa ID: ${programaId}`);

                    // ✅ IMPORTANTE: ESPERAR a que termine de cargar
                    await cargarDiasPrograma();
                    await cargarPreciosPrograma();
                    cargarArchivos();   // ← carga adjuntos al recargar la página

                    // ✅ AHORA SÍ calcular la fecha de salida
                    actualizarFechaSalida();
                } else {
                    console.log('💡 Programa nuevo - no hay días que cargar');
                }
            });

            // ============================================================
            // GESTIÓN DE PESTAÑAS
            // ============================================================
            function setupTabNavigation() {
                const tabItems = document.querySelectorAll('.tab-item[data-tab]');
                const tabContents = document.querySelectorAll('.tab-content');
                const previewPanel = document.getElementById('preview-panel');

                tabItems.forEach(item => {
                    item.addEventListener('click', function (e) {
                        e.preventDefault();

                        const targetTab = this.dataset.tab;

                        // Remover clase active de todas las pestañas
                        tabItems.forEach(tab => tab.classList.remove('active'));
                        tabContents.forEach(content => content.classList.remove('active'));

                        // Activar pestaña seleccionada
                        this.classList.add('active');
                        document.getElementById(targetTab).classList.add('active');

                        currentTab = targetTab;

                        // Acciones específicas por pestaña
                        switch (targetTab) {
                            case 'dia-a-dia':
                                if (isEditing && programaId) {
                                    cargarDiasPrograma().then(() => {
                                        // ✅ NUEVA LÍNEA: Seleccionar automáticamente el primer día
                                        setTimeout(() => {
                                            if (diasPrograma.length > 0 && !selectedDayId) {
                                                seleccionarDiaEnSidebar(diasPrograma[0].id);
                                            }
                                        }, 200);
                                    });
                                }
                                break;
                            case 'precio':
                                if (isEditing && programaId) {
                                    cargarPreciosPrograma();
                                }
                                break;
                            case 'adjuntos':
                                if (isEditing && programaId) {
                                    cargarArchivos();
                                }
                                break;
                        }
                    });
                });
            }

            // ============================================================
            // MANEJO DE FORMULARIOS
            // ============================================================
            function setupFormHandling() {
                const form = document.getElementById('programa-form');
                if (form) {
                    form.addEventListener('submit', function (e) {
                        e.preventDefault();
                        guardarPrograma();
                    });
                }

                const precioForm = document.getElementById('precio-form');
                if (precioForm) {
                    precioForm.addEventListener('submit', function (e) {
                        e.preventDefault();
                        guardarPrecios();
                    });
                }
            }
            function setupAdjuntos() {
                const fileInput = document.getElementById('adj-file-input');
                const linkInput = document.getElementById('adj-link-input');
                const linkBtn = document.querySelector('.adj-link-btn');
                const dropzone = document.querySelector('.adj-dropzone');
                if (!fileInput) return;   // el tab no está en el DOM, salir

                // Elegir archivo desde el explorador → subir
                fileInput.addEventListener('change', guardarArchivos);

                // Botón "Añadir" enlace
                linkBtn.addEventListener('click', guardarArchivos);

                // Enter en el input de enlace
                linkInput.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') { e.preventDefault(); guardarArchivos(); }
                });

                // Drag & drop sobre la dropzone
                ['dragover', 'dragenter'].forEach(ev =>
                    dropzone.addEventListener(ev, (e) => {
                        e.preventDefault();
                        dropzone.classList.add('is-dragover');
                    }));
                ['dragleave', 'drop'].forEach(ev =>
                    dropzone.addEventListener(ev, (e) => {
                        e.preventDefault();
                        dropzone.classList.remove('is-dragover');
                    }));
                dropzone.addEventListener('drop', (e) => {
                    fileInput.files = e.dataTransfer.files;   // pasa los archivos soltados
                    guardarArchivos();
                });
            }


            // Configurar manejadores de comidas - VERSIÓN MEJORADA
            function setupMealHandlers() {
                console.log('🔧 Configurando manejadores de comidas...');

                // Remover manejadores anteriores para evitar duplicados
                document.removeEventListener('change', handleMealChange);

                // Agregar nuevo manejador
                document.addEventListener('change', handleMealChange);

                console.log('✅ Manejadores de comidas configurados');
            }

            // Función separada para manejar cambios de comidas
            function handleMealChange(e) {
                if (event.target.matches('[data-flight-input="true"], .flight-code-input')) {
                    return;
                }
                console.log('📝 Evento de comida detectado:', e.target.name, e.target.value);

                if (e.target.name && e.target.name.startsWith('meals_')) {
                    const diaId = e.target.name.split('_')[1];
                    const mealDetails = document.getElementById(`meal-details-${diaId}`);

                    console.log('🍽️ Día ID:', diaId, 'Valor:', e.target.value);
                    console.log('📦 Elemento meal-details:', mealDetails);

                    if (e.target.value === 'incluidas') {
                        if (mealDetails) {
                            mealDetails.style.display = 'block';
                            console.log('✅ Mostrando opciones de comida');
                        } else {
                            console.error('❌ No se encontró meal-details para día', diaId);
                        }
                    } else {
                        if (mealDetails) {
                            mealDetails.style.display = 'none';
                            // Limpiar checkboxes cuando se selecciona "no incluidas"
                            const checkboxes = mealDetails.querySelectorAll('input[type="checkbox"]');
                            checkboxes.forEach(cb => cb.checked = false);
                            console.log('❌ Ocultando opciones de comida');
                        }
                    }

                    // Guardar automáticamente
                    guardarComidasDia(diaId);
                }

                // Manejar cambios en checkboxes de comidas
                if (e.target.name && e.target.name.match(/meal_(desayuno|almuerzo|cena)_/)) {
                    const diaId = e.target.name.split('_')[2];
                    console.log('🥐 Checkbox de comida cambiado para día:', diaId);
                    guardarComidasDia(diaId);
                }
            }

            // Función para guardar comidas de un día
            async function guardarComidasDia(diaId) {
                try {
                    const mealRadio = document.querySelector(`input[name="meals_${diaId}"]:checked`);
                    const comidasIncluidas = mealRadio && mealRadio.value === 'incluidas' ? 1 : 0;

                    // Obtener estado de checkboxes
                    const desayuno = document.querySelector(`input[name="meal_desayuno_${diaId}"]`)?.checked ? 1 : 0;
                    const almuerzo = document.querySelector(`input[name="meal_almuerzo_${diaId}"]`)?.checked ? 1 : 0;
                    const cena = document.querySelector(`input[name="meal_cena_${diaId}"]`)?.checked ? 1 : 0;

                    const formData = new FormData();
                    formData.append('action', 'update_comidas');
                    formData.append('dia_id', diaId);
                    formData.append('comidas_incluidas', comidasIncluidas);
                    formData.append('desayuno', desayuno);
                    formData.append('almuerzo', almuerzo);
                    formData.append('cena', cena);

                    const response = await fetch('<?= APP_URL ?>/modules/programa/dias_api.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();
                    if (!result.success) {
                        console.error('Error guardando comidas:', result.message);
                    }

                } catch (error) {
                    console.error('Error guardando comidas:', error);
                }
            }

            async function cargarComidasDia(diaId) {
                try {
                    console.log(`🍽️ Cargando comidas para día ${diaId}...`);

                    const response = await fetch(`<?= APP_URL ?>/modules/programa/dias_api.php?action=get_comidas&dia_id=${diaId}`);
                    const result = await response.json();

                    if (result.success && result.data) {
                        console.log(`✅ Comidas cargadas para día ${diaId}:`, result.data);

                        const comidas = result.data;

                        // Marcar radio button según comidas_incluidas
                        const radioIncluidas = document.querySelector(`input[name="meals_${diaId}"][value="incluidas"]`);
                        const radioNoIncluidas = document.querySelector(`input[name="meals_${diaId}"][value="no_incluidas"]`);

                        if (comidas.comidas_incluidas) {
                            if (radioIncluidas) radioIncluidas.checked = true;
                        } else {
                            if (radioNoIncluidas) radioNoIncluidas.checked = true;
                        }

                        // Marcar checkboxes
                        const desayunoCheckbox = document.querySelector(`input[name="meal_desayuno_${diaId}"]`);
                        if (desayunoCheckbox) {
                            desayunoCheckbox.checked = comidas.desayuno || false;
                        }

                        const almuerzoCheckbox = document.querySelector(`input[name="meal_almuerzo_${diaId}"]`);
                        if (almuerzoCheckbox) {
                            almuerzoCheckbox.checked = comidas.almuerzo || false;
                        }

                        const cenaCheckbox = document.querySelector(`input[name="meal_cena_${diaId}"]`);
                        if (cenaCheckbox) {
                            cenaCheckbox.checked = comidas.cena || false;
                        }

                        // Mostrar/ocultar detalles según comidas_incluidas
                        const mealDetails = document.getElementById(`meal-details-${diaId}`);
                        if (mealDetails) {
                            mealDetails.style.display = comidas.comidas_incluidas ? 'block' : 'none';
                        }

                        console.log(`✅ Comidas aplicadas correctamente al día ${diaId}`);
                    } else {
                        console.warn(`⚠️ No se pudieron cargar comidas para día ${diaId}`);
                    }

                } catch (error) {
                    console.error(`❌ Error cargando comidas para día ${diaId}:`, error);
                }
            }

            // ============================================================
            // FUNCIONES DE VIAJEROS - MODAL COMPACTO
            // ============================================================

            let modalViajeroContexto = 'viajero';
            let viajeroModalTemporal = null;

            function setupViajerosHandlers() {
                const numeroDocumento = document.getElementById('modal-numero-documento');
                const tipoDocumento = document.getElementById('modal-tipo-documento');
                const titularCheckbox = document.getElementById('titular-tambien-viaja');

                const telefonoInput = document.getElementById('modal-telefono');

                if (telefonoInput && window.intlTelInput && !telefonoInputInstance) {
                    telefonoInputInstance = window.intlTelInput(telefonoInput, {
                        initialCountry: "co",
                        preferredCountries: ["co", "us", "mx", "es", "ar", "cl", "pe", "ec", "br", "th"],
                        separateDialCode: true,
                        nationalMode: false,
                        utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/js/utils.js"
                    });

                    telefonoInput.addEventListener('input', function () {
                        this.value = this.value.replace(/[^0-9]/g, '');
                    });
                }


                if (numeroDocumento) {
                    numeroDocumento.addEventListener('blur', buscarViajeroDesdeModal);
                    numeroDocumento.addEventListener('keydown', function (e) {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            buscarViajeroDesdeModal();
                        }
                    });
                }

                if (tipoDocumento) {
                    tipoDocumento.addEventListener('change', function () {
                        if (numeroDocumento && numeroDocumento.value.trim()) {
                            buscarViajeroDesdeModal();
                        }
                    });
                }

                if (titularCheckbox) {
                    titularCheckbox.addEventListener('change', function () {
                        titularTambienViaja = this.checked;

                        if (titularData && titularTambienViaja) {
                            agregarViajeroSeleccionado(titularData);
                        }

                        if (titularData && !titularTambienViaja) {
                            viajerosSeleccionados = viajerosSeleccionados.filter(v => parseInt(v.id) !== parseInt(titularData.id));
                            renderViajerosSeleccionados();
                        }

                        renderTitularResumen();
                    });
                }

                renderTitularResumen();
                renderViajerosSeleccionados();
            }

            function abrirModalViajero(contexto = 'viajero') {
                modalViajeroContexto = contexto;
                viajeroModalTemporal = null;

                const modal = document.getElementById('modal-viajero');
                const title = document.getElementById('modal-viajero-title');
                const contextoInput = document.getElementById('modal-viajero-contexto');

                if (!modal) {
                    console.error('No existe #modal-viajero en el HTML.');
                    return;
                }

                if (contextoInput) {
                    contextoInput.value = contexto;
                }

                if (title) {
                    title.textContent = contexto === 'titular'
                        ? 'Seleccionar / crear titular'
                        : 'Agregar viajero';
                }

                limpiarModalViajero();
                modal.style.display = 'flex';

                setTimeout(() => {
                    document.getElementById('modal-numero-documento')?.focus();
                }, 100);
            }

            function cerrarModalViajero() {
                const modal = document.getElementById('modal-viajero');

                if (modal) {
                    modal.style.display = 'none';
                }

                limpiarModalViajero();
            }

            function limpiarModalViajero() {
                viajeroModalTemporal = null;

                setInputValue('modal-tipo-documento', '1');
                setInputValue('modal-numero-documento', '');
                setInputValue('modal-nombre', '');
                setInputValue('modal-apellido', '');
                setInputValue('modal-fecha-nacimiento', '');
                setInputValue('modal-pais-nacimiento', '');
                setInputValue('modal-mail', '');
                setInputValue('modal-telefono', '');
                setInputValue('modal-country-code', '+57');

                const status = document.getElementById('modal-viajero-status');
                if (status) {
                    status.className = 'viajero-status';
                    status.textContent = '';
                }
            }

            async function buscarViajeroDesdeModal() {
                const tipoDocumento = getInputValue('modal-tipo-documento');
                const numeroDocumento = getInputValue('modal-numero-documento');

                if (!tipoDocumento || !numeroDocumento) {
                    return;
                }

                mostrarEstadoModalViajero('info', 'Buscando viajero en tu agencia...');

                try {
                    const formData = new FormData();
                    formData.append('action', 'find_by_document');
                    formData.append('tipo_documento', tipoDocumento);
                    formData.append('numero_documento', numeroDocumento);

                    const response = await fetch('<?= APP_URL ?>/modules/viajeros/api.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (!result.success) {
                        mostrarEstadoModalViajero('error', result.message || 'No se pudo buscar el viajero.');
                        return;
                    }

                    if (result.found && result.data) {
                        viajeroModalTemporal = result.data;
                        llenarModalViajero(result.data);
                        mostrarEstadoModalViajero('success', 'Viajero encontrado. Los datos fueron autocompletados.');
                    } else {
                        viajeroModalTemporal = null;
                        limpiarDatosPersonalesModal(false);
                        mostrarEstadoModalViajero('warning', 'No existe un viajero con este documento. Completa los datos para crearlo.');
                    }
                } catch (error) {
                    console.error('Error buscando viajero:', error);
                    mostrarEstadoModalViajero('error', 'Error de conexión al buscar el viajero.');
                }
            }

            function llenarModalViajero(viajero) {
                setInputValue('modal-tipo-documento', viajero.tipo_documento || '1');
                setInputValue('modal-numero-documento', viajero.numero_documento || '');
                setInputValue('modal-nombre', viajero.nombre || '');
                setInputValue('modal-apellido', viajero.apellido || '');
                setInputValue('modal-fecha-nacimiento', normalizarFechaInput(viajero.fecha_nacimiento));
                setInputValue('modal-pais-nacimiento', viajero.pais_nacimiento || viajero.nacionalidad || '');
                setInputValue('modal-mail', viajero.mail || viajero.email || '');
                llenarTelefonoModal(viajero.telefono || '');
            }

            function llenarTelefonoModal(telefonoCompleto) {
                const countrySelect = document.getElementById('modal-country-code');
                const phoneInput = document.getElementById('modal-telefono');

                if (!countrySelect || !phoneInput) return;

                const limpio = String(telefonoCompleto || '').replace(/\s+/g, '');

                const codigos = Array.from(countrySelect.options)
                    .map(option => option.value)
                    .sort((a, b) => b.length - a.length);

                const codigoEncontrado = codigos.find(codigo => limpio.startsWith(codigo));

                if (codigoEncontrado) {
                    countrySelect.value = codigoEncontrado;
                    phoneInput.value = limpio.replace(codigoEncontrado, '').replace(/[^0-9]/g, '');
                } else {
                    countrySelect.value = '+57';
                    phoneInput.value = limpio.replace(/[^0-9]/g, '');
                }
            }

            function obtenerTelefonoCompletoModal() {
                const telefono = getInputValue('modal-telefono').replace(/[^0-9]/g, '');

                if (!telefono) {
                    return '';
                }

                if (telefonoInputInstance) {
                    const dialCode = telefonoInputInstance.getSelectedCountryData().dialCode;
                    return `+${dialCode}${telefono}`;
                }

                return telefono;
            }

            function llenarTelefonoModal(telefonoCompleto) {
                const phoneInput = document.getElementById('modal-telefono');
                if (!phoneInput) return;

                const limpio = String(telefonoCompleto || '').replace(/\s+/g, '');

                if (telefonoInputInstance && limpio.startsWith('+')) {
                    telefonoInputInstance.setNumber(limpio);
                    return;
                }

                phoneInput.value = limpio.replace(/[^0-9]/g, '');
            }

            function validarEmailModal() {
                const email = getInputValue('modal-mail');

                if (!email) {
                    return true;
                }

                return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
            }

            function validarTelefonoModal() {
                const telefono = getInputValue('modal-telefono');

                if (!telefono) {
                    return true;
                }

                return /^[0-9]{6,15}$/.test(telefono);
            }

            function limpiarDatosPersonalesModal(limpiarDocumento = true) {
                if (limpiarDocumento) {
                    setInputValue('modal-numero-documento', '');
                }

                setInputValue('modal-nombre', '');
                setInputValue('modal-apellido', '');
                setInputValue('modal-fecha-nacimiento', '');
                setInputValue('modal-pais-nacimiento', '');
                setInputValue('modal-mail', '');
                setInputValue('modal-telefono', '');
            }

            async function guardarViajeroDesdeModal() {
                const tipoDocumento = getInputValue('modal-tipo-documento');
                const numeroDocumento = getInputValue('modal-numero-documento');
                const nombre = getInputValue('modal-nombre');
                const apellido = getInputValue('modal-apellido');
                const fechaNacimiento = getInputValue('modal-fecha-nacimiento');

                if (!tipoDocumento || !numeroDocumento || !nombre || !apellido || !fechaNacimiento) {
                    mostrarEstadoModalViajero('error', 'Completa tipo de documento, número, nombre, apellido y fecha de nacimiento.');
                    return;
                }

                if (!validarEmailModal()) {
                    mostrarEstadoModalViajero('error', 'Ingresa un correo válido o deja el campo vacío.');
                    return;
                }

                if (!validarTelefonoModal()) {
                    mostrarEstadoModalViajero('error', 'El teléfono debe contener solo números y tener entre 6 y 15 dígitos.');
                    return;
                }

                let viajero = null;

                if (
                    viajeroModalTemporal &&
                    parseInt(viajeroModalTemporal.tipo_documento) === parseInt(tipoDocumento) &&
                    String(viajeroModalTemporal.numero_documento) === String(numeroDocumento)
                ) {
                    viajero = {
                        ...viajeroModalTemporal,
                        nombre,
                        apellido,
                        fecha_nacimiento: fechaNacimiento,
                        pais_nacimiento: getInputValue('modal-pais-nacimiento'),
                        mail: getInputValue('modal-mail'),
                        telefono: obtenerTelefonoCompletoModal()
                    };
                } else {
                    viajero = await crearViajeroDesdeModal();
                }

                if (!viajero || !viajero.id) {
                    return;
                }

                if (modalViajeroContexto === 'titular') {
                    titularId = parseInt(viajero.id);
                    titularData = viajero;

                    setInputValue('traveler-name', viajero.nombre);
                    setInputValue('traveler-lastname', viajero.apellido);

                    if (document.getElementById('titular-tambien-viaja')?.checked) {
                        titularTambienViaja = true;

                        const agregado = agregarViajeroSeleccionado(viajero);

                        if (!agregado) {
                            titularTambienViaja = false;
                            return;
                        }
                    }

                    renderTitularResumen();
                } else {
                    const agregado = agregarViajeroSeleccionado(viajero);

                    if (!agregado) {
                        return;
                    }
                }

                const guardado = await persistirViajerosEnPrograma();

                if (guardado) {
                    showAlert('Viajeros actualizados correctamente.', 'success');
                    cerrarModalViajero();
                }
            }

            async function crearViajeroDesdeModal() {
                try {
                    const formData = new FormData();
                    formData.append('action', 'create');
                    formData.append('tipo_documento', getInputValue('modal-tipo-documento'));
                    formData.append('numero_documento', getInputValue('modal-numero-documento'));
                    formData.append('nombre', getInputValue('modal-nombre'));
                    formData.append('apellido', getInputValue('modal-apellido'));
                    formData.append('fecha_nacimiento', getInputValue('modal-fecha-nacimiento'));
                    formData.append('pais_nacimiento', getInputValue('modal-pais-nacimiento'));
                    formData.append('mail', getInputValue('modal-mail'));
                    formData.append('telefono', obtenerTelefonoCompletoModal());

                    const response = await fetch('<?= APP_URL ?>/modules/viajeros/api.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (!result.success) {
                        mostrarEstadoModalViajero('error', result.message || 'No se pudo guardar el viajero.');
                        return null;
                    }

                    return result.data || null;
                } catch (error) {
                    console.error('Error creando viajero:', error);
                    mostrarEstadoModalViajero('error', 'Error de conexión al guardar el viajero.');
                    return null;
                }
            }

            function renderTitularResumen() {
                const container = document.getElementById('titular-resumen');

                if (!container) {
                    return;
                }

                if (!titularData && titularId && viajerosSeleccionados.length > 0) {
                    titularData = viajerosSeleccionados.find(v => parseInt(v.id) === parseInt(titularId)) || null;
                }

                if (!titularData) {
                    container.className = 'titular-resumen empty-state-inline';
                    container.innerHTML = `
            <i class="fas fa-user-circle"></i>
            <span>Sin titular seleccionado</span>
        `;
                    return;
                }

                const edadTipo = calcularTipoPorEdad(titularData.fecha_nacimiento);

                container.className = 'titular-resumen titular-summary-box';
                container.innerHTML = `
        <div>
            <div class="titular-summary-name">
                ${escapeHtml(titularData.nombre || '')} ${escapeHtml(titularData.apellido || '')}
            </div>
            <div class="titular-summary-meta">
                <span>Doc. ${escapeHtml(titularData.numero_documento || 'Sin documento')}</span>
                <span>${escapeHtml(edadTipo)}</span>
                ${titularData.mail ? `<span>${escapeHtml(titularData.mail)}</span>` : ''}
                ${titularData.telefono ? `<span>${escapeHtml(titularData.telefono)}</span>` : ''}
            </div>
        </div>
        <button type="button" class="btn-viajero-action primary" onclick="abrirModalViajero('titular')">
            Cambiar
        </button>
    `;
            }

            function agregarViajeroSeleccionado(viajero) {
                if (!viajero || !viajero.id) return false;
                if (!validarCupoViajero(viajero)) return false;

                const existe = viajerosSeleccionados.some(v => parseInt(v.id) === parseInt(viajero.id));

                if (!existe) {
                    viajerosSeleccionados.push(viajero);
                } else {
                    viajerosSeleccionados = viajerosSeleccionados.map(v => {
                        return parseInt(v.id) === parseInt(viajero.id) ? { ...v, ...viajero } : v;
                    });
                }

                renderViajerosSeleccionados();
                return true;
            }

            function quitarViajeroSeleccionado(viajeroId) {
                viajerosSeleccionados = viajerosSeleccionados.filter(v => parseInt(v.id) !== parseInt(viajeroId));

                if (parseInt(titularId) === parseInt(viajeroId)) {
                    const checkbox = document.getElementById('titular-tambien-viaja');
                    if (checkbox) checkbox.checked = false;
                    titularTambienViaja = false;
                }

                renderViajerosSeleccionados();
                renderTitularResumen();
                persistirViajerosEnPrograma();
            }

            function marcarComoTitular(viajeroId) {
                const viajero = viajerosSeleccionados.find(v => parseInt(v.id) === parseInt(viajeroId));

                if (!viajero) {
                    return;
                }

                titularId = parseInt(viajero.id);
                titularData = viajero;

                setInputValue('traveler-name', viajero.nombre);
                setInputValue('traveler-lastname', viajero.apellido);

                const checkbox = document.getElementById('titular-tambien-viaja');
                if (checkbox) checkbox.checked = true;

                titularTambienViaja = true;

                renderTitularResumen();
                renderViajerosSeleccionados();
                persistirViajerosEnPrograma();
            }

            function renderViajerosSeleccionados() {
                const container = document.getElementById('viajeros-seleccionados-list');

                if (!container) {
                    return;
                }

                if (!viajerosSeleccionados || viajerosSeleccionados.length === 0) {
                    container.innerHTML = `
            <div class="empty-viajeros">
                <i class="fas fa-users"></i>
                <p>Aún no hay viajeros asociados.</p>
            </div>
        `;
                    return;
                }

                container.innerHTML = viajerosSeleccionados.map(viajero => {
                    const edadTipo = calcularTipoPorEdad(viajero.fecha_nacimiento);
                    const esTitular = titularId && parseInt(titularId) === parseInt(viajero.id);

                    return `
            <div class="viajero-row">
                <div class="viajero-main">
                    <div class="viajero-name">
                        ${escapeHtml(viajero.nombre || '')} ${escapeHtml(viajero.apellido || '')}
                    </div>
                    <div class="viajero-meta">
                        <span>Doc. ${escapeHtml(viajero.numero_documento || 'Sin documento')}</span>
                        ${viajero.fecha_nacimiento ? `<span>${escapeHtml(normalizarFechaInput(viajero.fecha_nacimiento))}</span>` : ''}
                        ${viajero.telefono ? `<span>${escapeHtml(viajero.telefono)}</span>` : ''}
                        ${viajero.mail ? `<span>${escapeHtml(viajero.mail)}</span>` : ''}
                    </div>
                    <div>
                        <span class="viajero-badge">${edadTipo}</span>
                        ${esTitular ? '<span class="viajero-badge titular">Titular</span>' : ''}
                    </div>
                </div>
                <div class="viajero-actions">
                    ${!esTitular ? `<button type="button" class="btn-viajero-action primary" onclick="marcarComoTitular(${parseInt(viajero.id)})">Marcar titular</button>` : ''}
                    <button type="button" class="btn-viajero-action danger" onclick="quitarViajeroSeleccionado(${parseInt(viajero.id)})">Quitar</button>
                </div>
            </div>
        `;
                }).join('');
            }

            function mostrarEstadoModalViajero(type, message) {
                const status = document.getElementById('modal-viajero-status');

                if (!status) {
                    return;
                }

                status.className = `viajero-status ${type}`;
                status.textContent = message;
            }

            function setInputValue(id, value) {
                const el = document.getElementById(id);
                if (el) {
                    el.value = value || '';
                }
            }

            function getInputValue(id) {
                return document.getElementById(id)?.value?.trim() || '';
            }

            function normalizarFechaInput(fecha) {
                if (!fecha) return '';
                return String(fecha).substring(0, 10);
            }

            // Normaliza texto para búsquedas flexibles: quita acentos/diacríticos y minúsculas.
            function normalizarBusqueda(s){ return (s==null?'':String(s)).normalize('NFD').replace(/[̀-ͯ]/g,'').toLowerCase().trim(); }

            function calcularTipoPorEdad(fechaNacimiento) {
                if (!fechaNacimiento) return 'Edad no definida';

                const birth = new Date(fechaNacimiento);
                if (isNaN(birth.getTime())) return 'Edad no definida';

                const today = new Date();
                let age = today.getFullYear() - birth.getFullYear();
                const m = today.getMonth() - birth.getMonth();

                if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) {
                    age--;
                }

                if (age <= 1) return 'Bebé';
                if (age <= 11) return 'Niño';
                return 'Adulto';
            }

            function escapeHtml(value) {
                return String(value || '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }


            function obtenerLimitesViajerosPorPrecio() {
                const adultosInput = document.querySelector('#precio-form [name="cantidad_adultos"]');
                const ninosInput = document.querySelector('#precio-form [name="cantidad_ninos"]');

                return {
                    adultos: parseInt(adultosInput?.value || 0),
                    ninos: parseInt(ninosInput?.value || 0)
                };
            }

            function contarViajerosPorTipo() {
                return viajerosSeleccionados.reduce((acc, viajero) => {
                    const tipo = calcularTipoPorEdad(viajero.fecha_nacimiento);

                    if (tipo === 'Niño' || tipo === 'Bebé') {
                        acc.ninos++;
                    } else if (tipo === 'Adulto') {
                        acc.adultos++;
                    }

                    return acc;
                }, { adultos: 0, ninos: 0 });
            }

            function validarCupoViajero(viajero) {
                const limites = obtenerLimitesViajerosPorPrecio();
                const actuales = contarViajerosPorTipo();
                const tipo = calcularTipoPorEdad(viajero.fecha_nacimiento);

                const yaExiste = viajerosSeleccionados.some(v => parseInt(v.id) === parseInt(viajero.id));
                if (yaExiste) return true;

                if (tipo === 'Adulto' && actuales.adultos >= limites.adultos) {
                    showAlert(`Ya agregaste el máximo de adultos permitido: ${limites.adultos}`, 'error');
                    return false;
                }

                if ((tipo === 'Niño' || tipo === 'Bebé') && actuales.ninos >= limites.ninos) {
                    showAlert(`Ya agregaste el máximo de niños permitido: ${limites.ninos}`, 'error');
                    return false;
                }

                return true;
            }

            //================================================
            // Función ara gaurdar los viajeros y que persistan
            //====================================================
            async function persistirViajerosEnPrograma() {
                if (!programaId) {
                    return;
                }

                const form = document.getElementById('programa-form');
                if (!form) {
                    return;
                }

                try {
                    const formData = new FormData(form);
                    formData.append('action', 'save_programa');

                    if (titularId) {
                        formData.append('titular_id', titularId);
                    }

                    viajerosSeleccionados.forEach(viajero => {
                        if (viajero && viajero.id) {
                            formData.append('viajeros_ids[]', viajero.id);
                        }
                    });

                    const response = await fetch('<?= APP_URL ?>/modules/programa/api.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (!result.success) {
                        showAlert(result.message || 'No se pudieron guardar los viajeros en la solicitud.', 'error');
                        return false;
                    }

                    return true;
                } catch (error) {
                    console.error('Error persistiendo viajeros:', error);
                    showAlert('Error guardando los viajeros en la solicitud.', 'error');
                    return false;
                }
            }

            // ============================================================
            // FUNCIÓN PARA GUARDAR PROGRAMA
            // ============================================================
            async function guardarPrograma() {
                const submitBtn = document.getElementById('submit-btn');
                const originalText = submitBtn.innerHTML;

                // Validaciones antes de enviar
                const form = document.getElementById('programa-form');
                if (!form.checkValidity()) {
                    form.reportValidity();
                    return;
                }

                // Confirmación al DESMARCAR como vendido: sus traslados saldrán del Rooming
                const vendidoToggle = document.getElementById('vendido-toggle');
                if (window.ORIG_COMPRADO === 1 && vendidoToggle && !vendidoToggle.checked) {
                    if (!confirm('Vas a marcar esta reserva como NO vendida. Sus traslados saldrán del Rooming List (no se borran; reaparecen si la vuelves a marcar como vendida). ¿Continuar?')) {
                        return;
                    }
                }

                try {
                    // Estado de carga
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
                    submitBtn.disabled = true;
                    if (submitBtn.classList.contains('sending')) {
                        return;
                    }
                    submitBtn.classList.add('sending');
                    submitBtn.style.opacity = '0.7';

                    const formData = new FormData(form);
                    formData.append('action', 'save_programa');

                    if (titularId) {
                        formData.append('titular_id', titularId);
                    }

                    viajerosSeleccionados.forEach(viajero => {
                        if (viajero && viajero.id) {
                            formData.append('viajeros_ids[]', viajero.id);
                        }
                    });

                    // Debug - verificar que programaId esté definido
                    console.log('🔍 Guardando programa. ID actual:', programaId, 'Is editing:', isEditing);

                    const response = await fetch('<?= APP_URL ?>/modules/programa/api.php', {
                        method: 'POST',
                        body: formData
                    });

                    // Verificar respuesta HTTP
                    if (!response.ok) {
                        throw new Error(`Error del servidor: ${response.status} ${response.statusText}`);
                    }

                    const result = await response.json();
                    console.log('📋 Respuesta del servidor:', result);

                    if (result.success) {
                        // ÉXITO - marcar como manejado
                        document.body.classList.add('success-handled');

                        const isCreating = !isEditing;
                        const successMessage = isCreating ?
                            '✅ Programa creado exitosamente' :
                            '✅ Programa actualizado exitosamente';

                        showAlert(successMessage, 'success');

                        // Restaurar botón después del éxito
                        setTimeout(() => {
                            submitBtn.innerHTML = originalText;
                            submitBtn.disabled = false;
                            submitBtn.style.opacity = '1';
                            document.body.classList.remove('success-handled');
                        }, 1500);

                        // Si es creación, actualizar variables y URL
                        if (isCreating) {
                            programaId = result.id || result.programa_id;
                            isEditing = true;

                            console.log('📝 Programa creado con ID:', programaId);

                            // Actualizar URL sin recargar página
                            if (programaId) {
                                const newUrl = `<?= APP_URL ?>/programa?id=${programaId}`;
                                window.history.replaceState({}, '', newUrl);


                                // Actualizar campo hidden
                                updateHiddenField(programaId);

                                // Persistir las etiquetas elegidas antes de tener id
                                flushEditorTagsAfterCreate();
                                // Refrescar el panel de lead vinculado (ya hay id)
                                if (typeof initLeadVinculo === 'function') initLeadVinculo();
                            }

                            // Actualizar ID de solicitud si se generó
                            if (result.request_id) {
                                // Usar la nueva función que maneja la animación
                                mostrarCampoRequestId(result.request_id);

                                // Mostrar notificación adicional
                                setTimeout(() => {
                                    showAlert(`📋 ID de solicitud generado: ${result.request_id}`, 'info');
                                }, 500);
                            }

                            // Cambiar texto del botón después de restaurar
                            setTimeout(() => {
                                submitBtn.innerHTML = '<i class="fas fa-save"></i> Actualizar programa';
                            }, 1600);
                            document.body.className = 'programa-guardado';
                        }

                    } else {
                        // ERROR DEL SERVIDOR
                        const errorMessage = result.message || result.error || 'Error desconocido al guardar';
                        console.error('❌ Error del servidor:', errorMessage);
                        showAlert(`❌ ${errorMessage}`, 'error');
                    }

                } catch (error) {
                    // ERROR DE CONEXIÓN O JAVASCRIPT
                    console.error('❌ Error crítico:', error);

                    let errorMessage = 'Error de conexión';
                    if (error.message.includes('Failed to fetch')) {
                        errorMessage = 'Sin conexión al servidor. Verifica tu internet.';
                    } else if (error.message.includes('JSON')) {
                        errorMessage = 'Respuesta inválida del servidor';
                    } else if (error.message.includes('404')) {
                        errorMessage = 'Archivo de API no encontrado';
                    } else if (error.message.includes('500')) {
                        errorMessage = 'Error interno del servidor';
                    } else {
                        errorMessage = error.message;
                    }

                    showAlert(`❌ ${errorMessage}`, 'error');

                } finally {
                    // RESTAURAR BOTÓN SIEMPRE - SIN TIMEOUT
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                    submitBtn.style.opacity = '1';
                    submitBtn.classList.remove('sending');
                }
            }

            // Función auxiliar para actualizar campo hidden
            function updateHiddenField(programaId) {
                let hiddenInput = document.getElementById('programa-id-hidden');

                if (!hiddenInput) {
                    // Crear campo hidden si no existe
                    hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.id = 'programa-id-hidden';
                    hiddenInput.name = 'programa_id';
                    document.getElementById('programa-form').appendChild(hiddenInput);
                }

                hiddenInput.value = programaId;
                console.log('📝 Campo hidden actualizado con ID:', programaId);
            }

            // ============================================================
            // FUNCIONES PARA GESTIÓN DE DÍAS
            // ============================================================
            async function cargarDiasPrograma() {
                if (!programaId) {
                    console.log('❌ No hay programa ID para cargar días');
                    return;
                }

                console.log(`📥 Cargando días para programa ${programaId}...`);

                try {
                    const response = await fetch(`<?= APP_URL ?>/modules/programa/dias_api.php?action=list&programa_id=${programaId}`);

                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }

                    const result = await response.json();

                    console.log('📋 Respuesta de días API:', result);

                    if (result.success) {
                        diasPrograma = result.data || [];
                        console.log(`✅ ${diasPrograma.length} días cargados:`, diasPrograma);

                        renderizarDias();

                        // Cargar servicios para cada día
                        for (const dia of diasPrograma) {
                            await cargarServiciosDia(dia.id);
                            await cargarServiciosParaContador(dia.id);
                        }

                        // ❌ QUITAR ESTA LÍNEA - se llamará desde DOMContentLoaded
                        // actualizarFechaSalida();

                    } else {
                        console.error('❌ Error en respuesta de días:', result.message);
                        mostrarErrorDias(result.message || 'Error desconocido');
                    }
                } catch (error) {
                    console.error('❌ Error crítico cargando días:', error);
                    mostrarErrorDias('Error de conexión: ' + error.message);
                }
            }

            function renderizarDias() {
                const container = document.getElementById('days-container');
                if (!container) {
                    console.error('❌ No se encontró el contenedor days-container');
                    return;
                }

                console.log(`🎨 Renderizando ${diasPrograma.length} días...`);

                if (diasPrograma.length === 0) {
                    container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-calendar-plus"></i>
                <h3>No hay días agregados</h3>
                <p>Comienza agregando días a tu programa desde la biblioteca</p>
                <button class="btn btn-primary" onclick="agregarDia()">
                    <i class="fas fa-plus"></i>
                    Agregar primer día
                </button>
            </div>
        `;
                    return;
                }


                // Ordenar días por dia_numero
                const diasOrdenados = [...diasPrograma].sort((a, b) => (a.dia_numero || 0) - (b.dia_numero || 0));

                container.innerHTML = diasOrdenados.map((dia, index) => {
                    console.log(`🏗️ Renderizando día ${index + 1}:`, dia);

                    const diaNumero = dia.dia_numero || (index + 1);
                    const titulo = dia.titulo || 'Día sin título';
                    const descripcion = dia.descripcion || '';
                    const ubicacion = dia.ubicacion || 'Sin ubicación especificada';
                    const fechaDia = dia.fecha_dia ? new Date(dia.fecha_dia).toLocaleDateString('es-ES') : null;

                    return `
            <div class="day-card" data-dia-id="${dia.id}">
                <div class="day-header">
                    <div class="day-number">Día ${diaNumero}</div>
                    <div class="day-actions">
                        <button class="btn btn-outline" onclick="editarDia(${dia.id})" title="Editar día">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-secondary" onclick="eliminarDia(${dia.id})" title="Eliminar día">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="day-content">
                    ${renderizarImagenesDia(dia)}
                    <div class="day-info">
                        <h4>${titulo}</h4>
                        <div class="day-description">
                            ${descripcion ? descripcion : '<em style="color: #999;">Sin descripción</em>'}
                        </div>
                        <div class="day-meta">
                            <span>
                                <i class="fas fa-map-marker-alt"></i> 
                                ${ubicacion}
                            </span>
                            ${fechaDia ? `
                                <span>
                                    <i class="fas fa-calendar"></i> 
                                    ${fechaDia}
                                </span>
                            ` : ''}
                        </div>
                    </div>
                    
                    <!-- Servicios del día -->
                    <div class="day-services">
                        <div class="services-header">
                            <h5><i class="fas fa-plus-circle"></i> Agregar servicios al día:</h5>
                        </div>
                        <div class="service-buttons">
                            <button class="service-btn" onclick="agregarServicio(${dia.id}, 'actividad')">
                                <i class="fas fa-hiking"></i>
                                Actividad
                            </button>
                            <button class="service-btn" onclick="agregarServicio(${dia.id}, 'transporte')">
                                <i class="fas fa-car"></i>
                                Transporte
                            </button>
                            <button class="service-btn" onclick="agregarServicio(${dia.id}, 'alojamiento')">
                                <i class="fas fa-bed"></i>
                                Alojamiento
                            </button>
                        </div>
                        
                        <!-- Opciones de comidas -->
                        <div class="meals-section">
                            <h6><i class="fas fa-utensils"></i> Comidas:</h6>
                            <div class="meals-options">
                                <label class="meal-option">
                                    <input type="radio" name="meals_${dia.id}" value="incluidas">
                                    <span>Comidas incluidas</span>
                                </label>
                                <label class="meal-option">
                                    <input type="radio" name="meals_${dia.id}" value="no_incluidas" checked>
                                    <span>Comidas no incluidas</span>
                                </label>
                            </div>
                            <div class="meal-details" id="meal-details-${dia.id}" style="display: none;">
                                <div class="meal-checkboxes">
                                    <label class="meal-checkbox">
                                        <input type="checkbox" name="meal_desayuno_${dia.id}">
                                        <span>Desayuno</span>
                                    </label>
                                    <label class="meal-checkbox">
                                        <input type="checkbox" name="meal_almuerzo_${dia.id}">
                                        <span>Almuerzo</span>
                                    </label>
                                    <label class="meal-checkbox">
                                        <input type="checkbox" name="meal_cena_${dia.id}">
                                        <span>Cena</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Lista de servicios agregados -->
                        <div class="added-services" id="services-${dia.id}">
                            <div class="loading-services">
                                <i class="fas fa-spinner fa-spin"></i> Cargando servicios...
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
                }).join('');

                console.log('✅ Días renderizados exitosamente');
            }

            // Cargar datos de comidas después de renderizar
            setTimeout(() => {
                console.log('🍽️ Configurando manejadores y cargando comidas...');

                // RECONFIGURAR manejadores de comidas
                setupMealHandlers();

                // Cargar datos de comidas para cada día
                diasPrograma.forEach(dia => {
                    cargarComidasDia(dia.id);
                });
            }, 500); // Aumentar el delay a 500ms

            function renderizarImagenesDia(dia) {
                const imagenes = [dia.imagen1, dia.imagen2, dia.imagen3].filter(img => img && img.trim());

                if (imagenes.length === 0) {
                    return ''; // Sin imágenes
                }

                let imagenesHtml = '<div class="day-images">';

                imagenes.forEach((imagen, index) => {
                    const isMain = index === 0;
                    imagenesHtml += `
            <div class="day-image ${isMain ? 'main' : ''}">
                <img src="${imagen}" alt="${dia.titulo || 'Imagen del día'}" loading="lazy" onerror="this.style.display='none'">
            </div>
        `;
                });

                imagenesHtml += '</div>';
                return imagenesHtml;
            }

            // Función para agregar día desde biblioteca
            function agregarDia() {
                abrirModalBiblioteca();
            }

            function abrirModalBiblioteca() {
                console.log('📖 Abriendo modal de biblioteca...');

                // Limpiar selección anterior
                diasSeleccionados = [];
                ordenSeleccion = 1;

                const modal = document.getElementById('bibliotecaModal');
                if (modal) {
                    modal.style.display = 'flex';
                    modal.style.alignItems = 'center';
                    modal.style.justifyContent = 'center';
                }

                // Cargar días
                cargarDiasBiblioteca();

                // Actualizar UI
                actualizarContadorSeleccion();
                actualizarBotonAgregar();
            }

            async function cargarDiasBiblioteca() {
                console.log('📚 Cargando días de la biblioteca para selección múltiple...');

                const grid = document.getElementById('biblioteca-dias-grid');

                if (!grid) {
                    console.error('❌ Grid no encontrado');
                    return;
                }

                try {
                    grid.innerHTML = '<div class="loading" style="text-align: center; padding: 40px;"><i class="fas fa-spinner fa-spin" style="font-size: 32px; color: #667eea;"></i><p>Cargando días...</p></div>';

                    const baseURL = getBaseURL();
                    const response = await fetch(`${baseURL}/biblioteca/api?type=dias&action=list`);

                    if (!response.ok) {
                        throw new Error('Error al cargar días');
                    }

                    const result = await response.json();
                    console.log('✅ Días cargados:', result);

                    if (!result.success || !result.data || result.data.length === 0) {
                        grid.innerHTML = `
                <div class="empty-state" style="grid-column: 1/-1; text-align: center; padding: 60px 20px;">
                    <div style="font-size: 64px; margin-bottom: 20px;">📂</div>
                    <h3 style="color: #4a5568; margin-bottom: 12px;">No hay días en la biblioteca</h3>
                    <p style="color: #718096; margin-bottom: 24px;">Crea tu primer día haciendo clic en "Crear Nuevo Día"</p>
                    <button class="btn btn-success" onclick="abrirModalCrearDiaPrograma()">
                        <i class="fas fa-plus"></i> Crear Nuevo Día
                    </button>
                </div>
            `;
                        return;
                    }

                    // ✅ GUARDAR DATOS GLOBALMENTE para filtrado
                    window.diasBibliotecaData = result.data;

                    // ✅ RENDERIZAR DÍAS
                    renderizarDiasBibliotecaGrid(result.data);

                    // ✅ CONFIGURAR BÚSQUEDA
                    configurarBusquedaDias();

                    actualizarContadorSeleccion();

                } catch (error) {
                    console.error('❌ Error cargando días:', error);
                    grid.innerHTML = `
            <div class="error-state" style="grid-column: 1/-1; text-align: center; padding: 40px; color: #e53e3e;">
                <i class="fas fa-exclamation-triangle" style="font-size: 48px; margin-bottom: 16px;"></i>
                <h3>Error al cargar días</h3>
                <p>${error.message}</p>
                <button class="btn btn-primary" onclick="cargarDiasBiblioteca()">
                    <i class="fas fa-redo"></i> Reintentar
                </button>
            </div>
        `;
                }
            }

            // ============================================================
            // FUNCIÓN PARA RENDERIZAR GRID DE DÍAS
            // ============================================================
            function renderizarDiasBibliotecaGrid(dias) {
                const grid = document.getElementById('biblioteca-dias-grid');
                if (!grid) return;

                if (dias.length === 0) {
                    grid.innerHTML = `
            <div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #666;">
                <i class="fas fa-search" style="font-size: 48px; margin-bottom: 16px; opacity: 0.3;"></i>
                <h3>No se encontraron días</h3>
                <p>Intenta con otros términos de búsqueda</p>
            </div>
        `;
                    return;
                }

                grid.innerHTML = dias.map(dia => {
                    const isSelected = diasSeleccionados.includes(dia.id);
                    const ordenIndex = diasSeleccionados.indexOf(dia.id);
                    const ordenNumero = ordenIndex >= 0 ? ordenIndex + 1 : 0;

                    return `
            <div class="biblioteca-item ${isSelected ? 'selected' : ''}" 
                 data-id="${dia.id}"
                 onclick="toggleSeleccionDia(${dia.id})">
                
                <!-- Checkbox (oculto visualmente) -->
                <input type="checkbox" 
                       class="biblioteca-item-checkbox" 
                       id="checkbox-dia-${dia.id}"
                       ${isSelected ? 'checked' : ''}
                       onclick="event.stopPropagation(); toggleSeleccionDia(${dia.id});">
                
                <!-- Checkbox visual -->
                <div class="biblioteca-item-checkbox-visual"></div>
                
                <!-- Badge de orden de selección -->
                ${isSelected ? `<div class="orden-seleccion-badge">${ordenNumero}</div>` : ''}
                
                ${dia.imagen1 ? `
                    <img src="${dia.imagen1}" 
                         alt="${dia.titulo}" 
                         style="width: 100%; height: 180px; object-fit: cover; border-radius: 12px 12px 0 0;">
                ` : `
                    <div style="width: 100%; height: 180px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; border-radius: 12px 12px 0 0;">
                        <i class="fas fa-calendar-day" style="font-size: 48px; color: white; opacity: 0.5;"></i>
                    </div>
                `}
                
                <div class="biblioteca-item-content" style="padding: 16px;">
                    <h4 style="margin: 0 0 8px 0; color: #2d3748; font-size: 16px; font-weight: 600;">
                        ${dia.titulo || 'Sin título'}
                    </h4>
                    
                    ${dia.descripcion ? `
                        <p style="color: #718096; font-size: 14px; margin: 0 0 12px 0; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                            ${dia.descripcion}
                        </p>
                    ` : ''}
                    
                    ${dia.ubicacion ? `
                        <div style="display: flex; align-items: center; gap: 6px; color: #667eea; font-size: 13px; margin-top: 8px;">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>${dia.ubicacion}</span>
                        </div>
                    ` : ''}
                    
                    ${dia.ubicaciones_secundarias && dia.ubicaciones_secundarias.length > 0 ? `
                        <div style="font-size: 11px; color: var(--primary-color); margin-top: 6px; font-weight: 600;">
                            <i class="fas fa-plus"></i> ${dia.ubicaciones_secundarias.length} ubicación(es) adicional(es)
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
                }).join('');

                console.log(`✅ ${dias.length} días renderizados en el grid`);
            }

            // ============================================================
            // FUNCIÓN PARA CONFIGURAR BÚSQUEDA DE DÍAS
            // ============================================================
            // ============================================================
            // FUNCIÓN PARA CONFIGURAR BÚSQUEDA DE DÍAS - CON IDIOMA
            // ============================================================
            function configurarBusquedaDias() {
                const searchInput = document.getElementById('search-dias');

                if (!searchInput) {
                    console.warn('⚠️ Input de búsqueda no encontrado');
                    return;
                }

                // Limpiar listeners anteriores
                const newSearchInput = searchInput.cloneNode(true);
                searchInput.parentNode.replaceChild(newSearchInput, searchInput);


                newSearchInput.addEventListener('input', function (e) {
                    const searchTerm = normalizarBusqueda(e.target.value).split(/\s+/).filter(Boolean);



                    const grid = document.getElementById('biblioteca-dias-grid');
                    if (!grid) return;

                    const items = grid.querySelectorAll('.biblioteca-item');

                    let visibleCount = 0;

                    if (searchTerm === '') {
                        // Mostrar todos
                        items.forEach(item => {
                            item.style.display = 'block';
                        });
                        return;
                    }

                    // Filtrar por el texto VISIBLE en cada tarjeta
                    items.forEach(item => {
                        // Obtener TODO el texto visible de la tarjeta (traducido o no)
                        const itemText = normalizarBusqueda(item.querySelector('h4').innerText);

                        // Verificar si el término de búsqueda está en el texto visible
                        if (searchTerm.every(palabra => itemText.includes(palabra))) {
                            item.style.display = 'block';
                            visibleCount++;
                        } else {
                            item.style.display = 'none';
                        }
                    });


                    // Mostrar mensaje si no hay resultados
                    let noResultsMsg = grid.querySelector('.no-results-message');

                    if (visibleCount === 0) {
                        if (!noResultsMsg) {
                            noResultsMsg = document.createElement('div');
                            noResultsMsg.className = 'no-results-message';
                            noResultsMsg.style.cssText = `
                    grid-column: 1/-1; 
                    text-align: center; 
                    padding: 60px 20px; 
                    color: #666;
                `;
                            noResultsMsg.innerHTML = `
                    <i class="fas fa-search" style="font-size: 48px; margin-bottom: 16px; opacity: 0.3;"></i>
                    <h3 style="color: #4a5568; margin-bottom: 8px;">No se encontraron días</h3>
                    <p style="color: #718096;">Intenta con otros términos de búsqueda</p>
                `;
                            grid.appendChild(noResultsMsg);
                        }
                    } else {
                        if (noResultsMsg) {
                            noResultsMsg.remove();
                        }
                    }
                });

                console.log('✅ Búsqueda configurada con soporte multiidioma');
            }
            // ===== NUEVA FUNCIÓN: toggleSeleccionDia() =====
            // Agregar esta nueva función:

            function toggleSeleccionDia(diaId) {
                console.log('🔄 Toggle selección día:', diaId);

                const index = diasSeleccionados.indexOf(diaId);

                if (index > -1) {
                    // Deseleccionar
                    diasSeleccionados.splice(index, 1);
                    console.log('➖ Día deseleccionado');
                } else {
                    // Seleccionar
                    diasSeleccionados.push(diaId);
                    console.log('➕ Día seleccionado');
                }

                console.log('📋 Días seleccionados:', diasSeleccionados);

                // Actualizar UI
                const item = document.querySelector(`.biblioteca-item[data-id="${diaId}"]`);
                const checkbox = document.getElementById(`checkbox-dia-${diaId}`);

                if (item && checkbox) {
                    if (diasSeleccionados.includes(diaId)) {
                        item.classList.add('selected');
                        checkbox.checked = true;

                        // Agregar badge de orden
                        const ordenNumero = diasSeleccionados.indexOf(diaId) + 1;
                        let badge = item.querySelector('.orden-seleccion-badge');
                        if (!badge) {
                            badge = document.createElement('div');
                            badge.className = 'orden-seleccion-badge';
                            item.insertBefore(badge, item.firstChild);
                        }
                        badge.textContent = ordenNumero;
                    } else {
                        item.classList.remove('selected');
                        checkbox.checked = false;

                        // Remover badge de orden
                        const badge = item.querySelector('.orden-seleccion-badge');
                        if (badge) badge.remove();
                    }
                }

                // Actualizar contadores y botón
                actualizarContadorSeleccion();
                actualizarBotonAgregar();
            }

            // ===== NUEVA FUNCIÓN: actualizarContadorSeleccion() =====

            function actualizarContadorSeleccion() {
                const contador = document.getElementById('contador-seleccionados');
                const info = document.getElementById('info-seleccion');
                const cantidad = diasSeleccionados.length;

                if (contador) {
                    contador.textContent = `${cantidad} seleccionado${cantidad !== 1 ? 's' : ''}`;

                    // Animar contador
                    contador.style.transform = 'scale(1.2)';
                    setTimeout(() => {
                        contador.style.transform = 'scale(1)';
                    }, 200);
                }

                if (info) {
                    if (cantidad === 0) {
                        info.innerHTML = '<i class="fas fa-info-circle" style="color: #667eea;"></i> Selecciona uno o más días para agregar';
                    } else if (cantidad === 1) {
                        info.innerHTML = `<i class="fas fa-check-circle" style="color: var(--primary-color);"></i> <strong>1 día</strong> seleccionado`;
                    } else {
                        info.innerHTML = `<i class="fas fa-check-circle" style="color: var(--primary-color);"></i> <strong>${cantidad} días</strong> seleccionados (se agregarán en orden)`;
                    }
                }
            }

            // ===== NUEVA FUNCIÓN: actualizarBotonAgregar() =====

            function actualizarBotonAgregar() {
                const btn = document.getElementById('btn-agregar-dias');
                const texto = document.getElementById('texto-btn-agregar');
                const cantidad = diasSeleccionados.length;

                if (btn) {
                    btn.disabled = cantidad === 0;

                    if (texto) {
                        if (cantidad === 0) {
                            texto.textContent = 'Agregar días seleccionados';
                        } else if (cantidad === 1) {
                            texto.textContent = 'Agregar 1 día';
                        } else {
                            texto.textContent = `Agregar ${cantidad} días`;
                        }
                    }
                }
            }

            // ===== NUEVA FUNCIÓN: toggleSeleccionarTodos() =====

            function toggleSeleccionarTodos() {
                const btn = document.getElementById('btn-toggle-todos');
                const items = document.querySelectorAll('.biblioteca-item');

                if (!items.length) return;

                const todosSeleccionados = diasSeleccionados.length === items.length;

                if (todosSeleccionados) {
                    // Deseleccionar todos
                    diasSeleccionados = [];
                    items.forEach(item => {
                        item.classList.remove('selected');
                        const checkbox = item.querySelector('.biblioteca-item-checkbox');
                        if (checkbox) checkbox.checked = false;
                        const badge = item.querySelector('.orden-seleccion-badge');
                        if (badge) badge.remove();
                    });

                    if (btn) {
                        btn.innerHTML = '<i class="fas fa-check-double"></i> Seleccionar todos';
                    }

                    console.log('➖ Todos deseleccionados');
                } else {
                    // Seleccionar todos
                    diasSeleccionados = [];
                    items.forEach(item => {
                        const diaId = parseInt(item.getAttribute('data-id'));
                        if (!isNaN(diaId)) {
                            diasSeleccionados.push(diaId);
                            item.classList.add('selected');

                            const checkbox = item.querySelector('.biblioteca-item-checkbox');
                            if (checkbox) checkbox.checked = true;

                            // Agregar badge
                            const ordenNumero = diasSeleccionados.indexOf(diaId) + 1;
                            let badge = item.querySelector('.orden-seleccion-badge');
                            if (!badge) {
                                badge = document.createElement('div');
                                badge.className = 'orden-seleccion-badge';
                                item.insertBefore(badge, item.firstChild);
                            }
                            badge.textContent = ordenNumero;
                        }
                    });

                    if (btn) {
                        btn.innerHTML = '<i class="fas fa-times-circle"></i> Deseleccionar todos';
                    }

                    console.log('➕ Todos seleccionados:', diasSeleccionados);
                }

                actualizarContadorSeleccion();
                actualizarBotonAgregar();
            }

            function renderizarDiasBiblioteca(dias) {
                const container = document.getElementById('biblioteca-dias-grid');
                if (!container) return;

                if (dias.length === 0) {
                    container.innerHTML = `
            <div style="grid-column: 1 / -1;" class="empty-state">
                <i class="fas fa-calendar-alt"></i>
                <h3>No hay días en la biblioteca</h3>
                <p>Primero debes crear días en la biblioteca</p>
                <a href="<?= APP_URL ?>/biblioteca" class="btn btn-primary">
                    <i class="fas fa-book"></i>
                    Ir a biblioteca
                </a>
            </div>
        `;
                    return;
                }

                container.innerHTML = dias.map(dia => `
        <div class="biblioteca-item" data-dia-id="${dia.id}" onclick="seleccionarDia(${dia.id})">
            ${dia.imagen1 ? `
                <div class="biblioteca-item-image">
                    <img src="${dia.imagen1}" alt="${dia.titulo}" loading="lazy">
                </div>
            ` : `
                <div class="biblioteca-item-image" style="display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                    <i class="fas fa-image" style="font-size: 32px; color: #dee2e6;"></i>
                </div>
            `}
            <div class="biblioteca-item-content">
                <div class="biblioteca-item-title">${dia.titulo}</div>
                <div class="biblioteca-item-description">
                    ${dia.descripcion || 'Sin descripción disponible'}
                </div>
                <div class="biblioteca-item-location">
                    <i class="fas fa-map-marker-alt"></i> 
                    ${dia.ubicacion || 'Ubicación no especificada'}
                </div>
            </div>
        </div>
    `).join('');

                // Configurar búsqueda
                setupSearchFunctionality(dias);
            }

            function setupSearchFunctionality(dias) {
                const searchInput = document.getElementById('search-dias');
                if (!searchInput) return;

                searchInput.addEventListener('input', function (e) {
                    const searchTerm = normalizarBusqueda(e.target.value);
                    const filteredDias = dias.filter(dia =>
                        normalizarBusqueda(dia.titulo).includes(searchTerm) ||
                        (dia.descripcion && normalizarBusqueda(dia.descripcion).includes(searchTerm)) ||
                        (dia.ubicacion && normalizarBusqueda(dia.ubicacion).includes(searchTerm))
                    );

                    renderFilteredDias(filteredDias);
                });
            }

            function renderFilteredDias(dias) {
                const container = document.getElementById('biblioteca-dias-grid');
                if (!container) return;

                if (dias.length === 0) {
                    container.innerHTML = `
            <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #666;">
                <i class="fas fa-search" style="font-size: 48px; margin-bottom: 16px; opacity: 0.3;"></i>
                <h3>No se encontraron días</h3>
                <p>Intenta con otros términos de búsqueda</p>
            </div>
        `;
                    return;
                }

                container.innerHTML = dias.map(dia => `
        <div class="biblioteca-item" data-dia-id="${dia.id}" onclick="seleccionarDia(${dia.id})">
            ${dia.imagen1 ? `
                <div class="biblioteca-item-image">
                    <img src="${dia.imagen1}" alt="${dia.titulo}" loading="lazy">
                </div>
            ` : `
                <div class="biblioteca-item-image" style="display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                    <i class="fas fa-image" style="font-size: 32px; color: #dee2e6;"></i>
                </div>
            `}
            <div class="biblioteca-item-content">
                <div class="biblioteca-item-title">${dia.titulo}</div>
                <div class="biblioteca-item-description">
                    ${dia.descripcion || 'Sin descripción disponible'}
                </div>
                <div class="biblioteca-item-location">
                    <i class="fas fa-map-marker-alt"></i> 
                    ${dia.ubicacion || 'Ubicación no especificada'}
                </div>
            </div>
        </div>
    `).join('');
            }

            function seleccionarDia(diaId) {
                // Remover selección previa
                document.querySelectorAll('.biblioteca-item').forEach(item => {
                    item.classList.remove('selected');
                });

                // Seleccionar nuevo día
                const item = document.querySelector(`[data-dia-id="${diaId}"]`);
                if (item) {
                    item.classList.add('selected');
                    selectedDiaId = diaId;
                    document.getElementById('btn-agregar-dia').disabled = false;

                    // Scroll suave hacia el elemento seleccionado
                    item.scrollIntoView({
                        behavior: 'smooth',
                        block: 'nearest',
                        inline: 'nearest'
                    });
                }
            }

            async function agregarDiasSeleccionados() {
                if (diasSeleccionados.length === 0) {
                    showAlert('⚠️ Por favor selecciona al menos un día', 'warning');
                    return;
                }

                const btn = document.getElementById('btn-agregar-dias');
                const texto = document.getElementById('texto-btn-agregar');

                if (!btn || !texto) return;

                const originalHTML = btn.innerHTML;

                try {
                    // Deshabilitar botón
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Preparando...';

                    console.log('📤 Iniciando agregado masivo de días');
                    console.log('📊 Días seleccionados:', diasSeleccionados);
                    console.log('🎯 Programa ID:', programaId);

                    // ✅ OBTENER ÚLTIMO NÚMERO UNA SOLA VEZ
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verificando días existentes...';
                    let ultimoDiaNumero = await obtenerUltimoDiaNumero();

                    console.log('📍 Último día existente:', ultimoDiaNumero);
                    console.log(`✅ Nuevos días empezarán desde: ${ultimoDiaNumero + 1}`);

                    const baseURL = getBaseURL();
                    let diasAgregados = 0;
                    let errores = [];

                    // ⚡ AGREGAR CADA DÍA EN ORDEN
                    for (let i = 0; i < diasSeleccionados.length; i++) {
                        const diaId = diasSeleccionados[i];
                        const nuevoDiaNumero = ultimoDiaNumero + i + 1;

                        const progreso = i + 1;
                        const total = diasSeleccionados.length;

                        btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Agregando día ${progreso}/${total}...`;

                        console.log(`\n🔄 Procesando día ${progreso}/${total}`);
                        console.log(`   Biblioteca ID: ${diaId}`);
                        console.log(`   Número asignado: ${nuevoDiaNumero}`);

                        try {
                            const response = await fetch(`${baseURL}/modules/programa/dias_api.php`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify({
                                    action: 'add_from_biblioteca',
                                    programa_id: programaId,
                                    biblioteca_dia_id: diaId,
                                    dia_numero: nuevoDiaNumero  // ⚡ NÚMERO ESPECÍFICO
                                })
                            });

                            console.log(`📡 Status: ${response.status}`);

                            if (!response.ok) {
                                throw new Error(`HTTP ${response.status}`);
                            }

                            const result = await response.json();
                            console.log(`📥 Resultado:`, result);

                            if (result.success) {
                                diasAgregados++;
                                console.log(`✅ Día ${progreso} agregado como #${result.dia_numero}`);
                            } else {
                                const errorMsg = result.error || result.message || 'Error desconocido';
                                console.error(`❌ Error: ${errorMsg}`);

                                // ⚠️ SI HAY ERROR DE DUPLICADO, REFRESCAR Y REINTENTAR
                                if (errorMsg.includes('Ya existe') || errorMsg.includes('Duplicate')) {
                                    console.warn(`⚠️ Duplicado detectado, refrescando último número...`);

                                    ultimoDiaNumero = await obtenerUltimoDiaNumero();
                                    const nuevoIntento = ultimoDiaNumero + 1;

                                    console.log(`🔁 Reintentando con número: ${nuevoIntento}`);

                                    const retryResponse = await fetch(`${baseURL}/modules/programa/dias_api.php`, {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/json' },
                                        body: JSON.stringify({
                                            action: 'add_from_biblioteca',
                                            programa_id: programaId,
                                            biblioteca_dia_id: diaId,
                                            dia_numero: nuevoIntento
                                        })
                                    });

                                    const retryResult = await retryResponse.json();

                                    if (retryResult.success) {
                                        diasAgregados++;
                                        ultimoDiaNumero = nuevoIntento; // Actualizar base
                                        console.log(`✅ Reintento exitoso: #${retryResult.dia_numero}`);
                                    } else {
                                        errores.push(`Día ${progreso}: ${retryResult.error || 'Error en reintento'}`);
                                        console.error(`❌ Reintento falló`);
                                    }
                                } else {
                                    errores.push(`Día ${progreso}: ${errorMsg}`);
                                }
                            }

                        } catch (error) {
                            console.error(`❌ Excepción en día ${progreso}:`, error);
                            errores.push(`Día ${progreso}: ${error.message}`);
                        }

                        // Pausa pequeña entre llamadas
                        if (i < diasSeleccionados.length - 1) {
                            await new Promise(resolve => setTimeout(resolve, 100));
                        }
                    }

                    console.log('\n📊 RESUMEN FINAL:');
                    console.log(`   ✅ Exitosos: ${diasAgregados}`);
                    console.log(`   ❌ Errores: ${errores.length}`);

                    // Recargar días
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Actualizando...';
                    await cargarDiasPrograma();

                    // Cerrar modal
                    cerrarModalBiblioteca();

                    // Mostrar resultado
                    if (diasAgregados === diasSeleccionados.length) {
                        showAlert(
                            `✅ ${diasAgregados} día${diasAgregados > 1 ? 's' : ''} agregado${diasAgregados > 1 ? 's' : ''} exitosamente`,
                            'success'
                        );
                    } else if (diasAgregados > 0) {
                        showAlert(
                            `⚠️ ${diasAgregados} de ${diasSeleccionados.length} días agregados.\n\nErrores:\n${errores.join('\n')}`,
                            'warning'
                        );
                    } else {
                        throw new Error(
                            errores.length > 0
                                ? `No se agregaron días:\n${errores.join('\n')}`
                                : 'No se pudo agregar ningún día'
                        );
                    }

                } catch (error) {
                    console.error('❌ Error general:', error);
                    showAlert('Error: ' + error.message, 'error');

                } finally {
                    // Restaurar botón
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                }
            }

            // ===== NUEVA FUNCIÓN: obtenerUltimoDiaNumero() =====
            // Función auxiliar para obtener el último número de día del programa

            async function obtenerUltimoDiaNumero() {
                console.log('🔍 Obteniendo último número de día...');

                try {
                    const baseURL = getBaseURL();
                    const url = `${baseURL}/modules/programa/dias_api.php?action=get_dias&programa_id=${programaId}`;

                    console.log('📡 URL:', url);

                    const response = await fetch(url);

                    if (!response.ok) {
                        console.warn(`⚠️ HTTP error ${response.status}, asumiendo 0`);
                        return 0;
                    }

                    const result = await response.json();
                    console.log('📥 Respuesta:', result);

                    if (!result.success || !result.data || !Array.isArray(result.data) || result.data.length === 0) {
                        console.log('✅ Programa sin días, último número es 0');
                        return 0;
                    }

                    // Obtener máximo número
                    const numeros = result.data
                        .map(d => parseInt(d.dia_numero))
                        .filter(n => !isNaN(n));

                    if (numeros.length === 0) {
                        console.log('✅ Sin números válidos, retornando 0');
                        return 0;
                    }

                    const maxNumero = Math.max(...numeros);

                    console.log('📊 Números existentes:', numeros);
                    console.log('🎯 Máximo encontrado:', maxNumero);

                    return maxNumero;

                } catch (error) {
                    console.error('❌ Error:', error);
                    console.warn('⚠️ Por seguridad, retornando 0');
                    return 0;
                }
            }

            function cerrarModalBiblioteca() {
                const modal = document.getElementById('bibliotecaModal');
                if (modal) {
                    modal.style.display = 'none';
                }

                // Limpiar selección
                diasSeleccionados = [];
                ordenSeleccion = 1;

                // Limpiar búsqueda
                const searchInput = document.getElementById('search-dias');
                if (searchInput) {
                    searchInput.value = '';
                }

                // Limpiar checkboxes
                document.querySelectorAll('.biblioteca-item').forEach(item => {
                    item.classList.remove('selected');
                    const checkbox = item.querySelector('.dia-checkbox');
                    if (checkbox) checkbox.checked = false;
                });

                console.log('✅ Modal cerrado y limpiado');
            }

            async function eliminarDia(diaId) {
                const confirmed = await showConfirmModal({
                    title: '¿Eliminar día?',
                    message: '¿Estás seguro de que quieres eliminar este día?',
                    details: 'Esta acción no se puede deshacer.',
                    icon: '<i class="fas fa-trash"></i>',
                    confirmText: 'Aceptar',
                    cancelText: 'Cancelar'
                });

                if (!confirmed) return;

                console.log('🗑️ Eliminando día ID:', diaId);

                try {
                    const response = await fetch('<?= APP_URL ?>/modules/programa/dias_api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'delete',
                            dia_id: diaId
                        })
                    });

                    console.log('📡 Respuesta del servidor:', response.status);

                    // SOLUCIÓN: Si es 500 pero el día se elimina, verificar primero si realmente se eliminó
                    const responseText = await response.text();
                    console.log('📄 Respuesta:', responseText);

                    // Intentar parsear JSON
                    let result = null;
                    try {
                        result = JSON.parse(responseText);
                    } catch (parseError) {
                        console.warn('⚠️ No se pudo parsear JSON:', parseError);
                    }

                    // ESTRATEGIA: Asumir éxito y verificar recargando
                    console.log('🔄 Verificando eliminación recargando días...');

                    // Limpiar selección inmediatamente
                    if (selectedDayId == diaId) {
                        selectedDayId = null;
                        const servicesContent = document.getElementById('services-content');
                        if (servicesContent) {
                            servicesContent.innerHTML = '<p class="no-services">Selecciona un día para ver sus servicios</p>';
                        }
                    }

                    // Recargar días para verificar
                    await cargarDiasPrograma();

                    // SIEMPRE mostrar éxito porque funcionalmente el día se elimina
                    showAlert('✅ Día eliminado exitosamente', 'success');

                } catch (error) {
                    console.error('❌ Error en la petición:', error);

                    // Aún así, intentar recargar para verificar si se eliminó
                    console.log('🔄 Error en petición, pero verificando si se eliminó...');

                    try {
                        await cargarDiasPrograma();
                        showAlert('✅ Día eliminado exitosamente', 'success');
                    } catch (reloadError) {
                        showAlert('Error de conexión al eliminar día', 'error');
                    }
                }
            }

            function editarDia(diaId) {
                // TODO: Implementar edición de días
                showAlert('Función de edición en desarrollo', 'info');
            }

            // ============================================================
            // FUNCIONES PARA SERVICIOS
            // ============================================================
            function agregarServicio(diaId, tipoServicio) {
                console.log(`➕ Agregando servicio normal: Día=${diaId}, Tipo=${tipoServicio}`);

                // Configurar para servicio normal
                isAddingAlternative = false;
                alternativeParentId = null;
                currentDiaId = diaId;
                currentTipoServicio = tipoServicio;

                abrirModalServicios(tipoServicio, 'Agregar ' + tipoServicio);
            }

            // ============================================================
            // CREAR RECURSO NUEVO INLINE Y ASIGNARLO AL DÍA
            // (mismo patrón que "Crear Nuevo Día": crea en biblioteca y asigna,
            //  sin salir de la página ni cambiar de pestaña)
            // ============================================================
            let _crearServicioDiaId = null;
            let _crearServicioTipo = null;
            let widgetSalidaTransporte = null;
            let widgetLlegadaTransporte = null;

            function crearNuevoServicioInline() {
                if (isAddingAlternative) {
                    showAlert('Para crear una alternativa, hazlo desde la biblioteca', 'warning');
                    return;
                }
                if (!programaId) {
                    showAlert('Guarda el programa antes de crear recursos nuevos', 'warning');
                    return;
                }
                if (!currentDiaId || !currentTipoServicio) {
                    showAlert('No se pudo determinar el día de destino', 'error');
                    return;
                }
                // Capturar el contexto antes de abrir el modal de creación.
                _crearServicioDiaId = currentDiaId;
                _crearServicioTipo = currentTipoServicio;

                if (currentTipoServicio === 'transporte') {
                    abrirCrearTransportePrograma();
                } else if (currentTipoServicio === 'actividad') {
                    abrirCrearActividadPrograma();
                } else if (currentTipoServicio === 'alojamiento') {
                    abrirCrearAlojamientoPrograma();
                } else {
                    showAlert('Tipo de servicio no soportado', 'error');
                }
            }

            function abrirCrearTransportePrograma() {
                const modal = document.getElementById('crearTransporteModalPrograma');
                const serv = document.getElementById('serviciosModal');
                if (serv) serv.style.zIndex = '9998';

                modal.style.display = 'flex';
                modal.style.alignItems = 'center';
                modal.style.justifyContent = 'center';
                modal.style.zIndex = '10001';
                setTimeout(() => modal.classList.add('show'), 10);

                const form = document.getElementById('formCrearTransporteEnPrograma');
                if (form) form.reset();

                setTimeout(() => inicializarWidgetsTransportePrograma(), 200);
            }

            function cerrarCrearTransportePrograma() {
                const modal = document.getElementById('crearTransporteModalPrograma');
                modal.classList.remove('show');

                if (widgetSalidaTransporte) { widgetSalidaTransporte.destroy(); widgetSalidaTransporte = null; }
                if (widgetLlegadaTransporte) { widgetLlegadaTransporte.destroy(); widgetLlegadaTransporte = null; }

                setTimeout(() => {
                    modal.style.display = 'none';
                    const serv = document.getElementById('serviciosModal');
                    if (serv) serv.style.zIndex = '';
                }, 300);
            }

            function inicializarWidgetsTransportePrograma() {
                if (typeof UbicacionSearchWidget === 'undefined') {
                    console.error('❌ UbicacionSearchWidget no está cargado');
                    return;
                }
                const baseURL = getBaseURL();
                const apiUrl = `${baseURL}/modules/ubicaciones/ubicaciones_api.php`;

                if (widgetSalidaTransporte) { widgetSalidaTransporte.destroy(); widgetSalidaTransporte = null; }
                if (widgetLlegadaTransporte) { widgetLlegadaTransporte.destroy(); widgetLlegadaTransporte = null; }

                const inS = document.getElementById('lugar-salida-crear-transporte');
                const inL = document.getElementById('lugar-llegada-crear-transporte');

                if (inS) {
                    widgetSalidaTransporte = new UbicacionSearchWidget(inS, {
                        apiUrl, latInputId: 'lat-salida-crear-transporte', lngInputId: 'lng-salida-crear-transporte',
                        showPreview: true, previewContainerId: 'preview-salida-crear-transporte',
                        autoSave: true, minChars: 3, debounceTime: 300
                    });
                }
                if (inL) {
                    widgetLlegadaTransporte = new UbicacionSearchWidget(inL, {
                        apiUrl, latInputId: 'lat-llegada-crear-transporte', lngInputId: 'lng-llegada-crear-transporte',
                        showPreview: true, previewContainerId: 'preview-llegada-crear-transporte',
                        autoSave: true, minChars: 3, debounceTime: 300
                    });
                }
            }

            async function guardarTransporteEnPrograma() {
                const btn = event.target;
                const originalHTML = btn.innerHTML;
                try {
                    const medio = document.getElementById('medio-crear-transporte').value;
                    const titulo = document.getElementById('titulo-crear-transporte').value.trim();
                    const salida = document.getElementById('lugar-salida-crear-transporte').value.trim();
                    const llegada = document.getElementById('lugar-llegada-crear-transporte').value.trim();

                    if (!medio) { alert('Selecciona el medio de transporte'); return; }
                    if (!titulo) { alert('Ingresa un título'); return; }
                    if (!salida || !llegada) { alert('Indica lugar de salida y de llegada'); return; }

                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

                    const baseURL = getBaseURL();

                    // 1. Crear el transporte en la biblioteca
                    const formData = new FormData();
                    formData.append('action', 'create');
                    formData.append('type', 'transportes');
                    formData.append('medio', medio);
                    formData.append('titulo', titulo);
                    formData.append('lugar_salida', salida);
                    formData.append('lat_salida', document.getElementById('lat-salida-crear-transporte').value);
                    formData.append('lng_salida', document.getElementById('lng-salida-crear-transporte').value);
                    formData.append('lugar_llegada', llegada);
                    formData.append('lat_llegada', document.getElementById('lat-llegada-crear-transporte').value);
                    formData.append('lng_llegada', document.getElementById('lng-llegada-crear-transporte').value);
                    formData.append('duracion', document.getElementById('duracion-crear-transporte').value);
                    formData.append('distancia_km', document.getElementById('distancia-crear-transporte').value);
                    formData.append('descripcion', document.getElementById('descripcion-crear-transporte').value);

                    const response = await fetch(`${baseURL}/biblioteca/api`, { method: 'POST', body: formData });
                    const result = await response.json();
                    if (!result.success) throw new Error(result.error || 'Error al crear el transporte');
                    const nuevoId = result.id;

                    // 2. Asignarlo al día desde donde se accedió
                    const diaId = parseInt(_crearServicioDiaId);
                    const resp2 = await fetch(`${baseURL}/modules/programa/servicios_api.php`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'add_service',
                            dia_id: diaId,
                            tipo_servicio: 'transporte',
                            biblioteca_item_id: parseInt(nuevoId)
                        })
                    });
                    const result2 = await resp2.json();
                    if (!result2.success) throw new Error(result2.message || 'Error al asignar al día');

                    showAlert('✅ Transporte creado y asignado al día', 'success');
                    cerrarCrearTransportePrograma();
                    cerrarModalServicios();

                    seleccionarDiaEnSidebar(diaId);
                    await cargarServiciosDia(diaId);
                    await cargarServiciosParaContador(diaId);

                } catch (error) {
                    console.error('❌ Error al crear/asignar transporte:', error);
                    alert('Error: ' + error.message);
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                }
            }

            // ── ACTIVIDAD ──────────────────────────────────────────────
            let widgetUbicacionActividad = null;

            function abrirCrearActividadPrograma() {
                const modal = document.getElementById('crearActividadModalPrograma');
                const serv = document.getElementById('serviciosModal');
                if (serv) serv.style.zIndex = '9998';

                modal.style.display = 'flex';
                modal.style.alignItems = 'center';
                modal.style.justifyContent = 'center';
                modal.style.zIndex = '10001';
                setTimeout(() => modal.classList.add('show'), 10);

                const form = document.getElementById('formCrearActividadEnPrograma');
                if (form) form.reset();
                initImgSelector('actividad', 'imageUploadActividad', 3);

                setTimeout(() => {
                    if (typeof UbicacionSearchWidget === 'undefined') return;
                    const baseURL = getBaseURL();
                    const inp = document.getElementById('ubicacion-crear-actividad');
                    if (widgetUbicacionActividad) { widgetUbicacionActividad.destroy(); widgetUbicacionActividad = null; }
                    if (inp) {
                        widgetUbicacionActividad = new UbicacionSearchWidget(inp, {
                            apiUrl: `${baseURL}/modules/ubicaciones/ubicaciones_api.php`,
                            latInputId: 'lat-crear-actividad', lngInputId: 'lng-crear-actividad',
                            showPreview: true, previewContainerId: 'preview-ubicacion-crear-actividad',
                            autoSave: true, minChars: 3, debounceTime: 300
                        });
                    }
                }, 200);
            }

            function cerrarCrearActividadPrograma() {
                const modal = document.getElementById('crearActividadModalPrograma');
                modal.classList.remove('show');
                if (widgetUbicacionActividad) { widgetUbicacionActividad.destroy(); widgetUbicacionActividad = null; }
                setTimeout(() => {
                    modal.style.display = 'none';
                    const serv = document.getElementById('serviciosModal');
                    if (serv) serv.style.zIndex = '';
                }, 300);
            }

            async function guardarActividadEnPrograma() {
                const btn = event.target;
                const originalHTML = btn.innerHTML;
                try {
                    const nombre = document.getElementById('nombre-crear-actividad').value.trim();
                    const ubicacion = document.getElementById('ubicacion-crear-actividad').value.trim();
                    if (!nombre) { alert('Ingresa el nombre de la actividad'); return; }
                    if (!ubicacion) { alert('Selecciona una ubicación'); return; }

                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

                    const baseURL = getBaseURL();

                    const formData = new FormData();
                    formData.append('action', 'create');
                    formData.append('type', 'actividades');
                    formData.append('nombre', nombre);
                    formData.append('ubicacion', ubicacion);
                    formData.append('latitud', document.getElementById('lat-crear-actividad').value);
                    formData.append('longitud', document.getElementById('lng-crear-actividad').value);
                    formData.append('descripcion', document.getElementById('descripcion-crear-actividad').value);

                    const files = getImgSelectorFiles('actividad');
                    for (let i = 0; i < Math.min(files.length, 3); i++) {
                        const comprimida = await _comprimirImagenParaSubida(files[i]);
                        formData.append(`imagen${i + 1}`, comprimida);
                    }

                    const response = await fetch(`${baseURL}/biblioteca/api`, { method: 'POST', body: formData });
                    const result = await response.json();
                    if (!result.success) throw new Error(result.error || 'Error al crear la actividad');

                    await _asignarServicioCreado(result.id, 'actividad');
                    showAlert('✅ Actividad creada y asignada al día', 'success');
                    cerrarCrearActividadPrograma();
                    cerrarModalServicios();
                    await _refrescarDiaCreado();

                } catch (error) {
                    console.error('❌ Error al crear/asignar actividad:', error);
                    alert('Error: ' + error.message);
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                }
            }

            // ── ALOJAMIENTO ────────────────────────────────────────────
            let widgetUbicacionAlojamiento = null;

            function toggleCategoriaAlojamiento() {
                const tipo = document.getElementById('tipo-crear-alojamiento').value;
                const group = document.getElementById('categoria-alojamiento-group');
                if (group) group.style.display = (tipo === 'hotel') ? '' : 'none';
            }

            function abrirCrearAlojamientoPrograma() {
                const modal = document.getElementById('crearAlojamientoModalPrograma');
                const serv = document.getElementById('serviciosModal');
                if (serv) serv.style.zIndex = '9998';

                modal.style.display = 'flex';
                modal.style.alignItems = 'center';
                modal.style.justifyContent = 'center';
                modal.style.zIndex = '10001';
                setTimeout(() => modal.classList.add('show'), 10);

                const form = document.getElementById('formCrearAlojamientoEnPrograma');
                if (form) form.reset();
                toggleCategoriaAlojamiento();
                initImgSelector('alojamiento', 'imageUploadAlojamiento', 1);

                setTimeout(() => {
                    if (typeof UbicacionSearchWidget === 'undefined') return;
                    const baseURL = getBaseURL();
                    const inp = document.getElementById('ubicacion-crear-alojamiento');
                    if (widgetUbicacionAlojamiento) { widgetUbicacionAlojamiento.destroy(); widgetUbicacionAlojamiento = null; }
                    if (inp) {
                        widgetUbicacionAlojamiento = new UbicacionSearchWidget(inp, {
                            apiUrl: `${baseURL}/modules/ubicaciones/ubicaciones_api.php`,
                            latInputId: 'lat-crear-alojamiento', lngInputId: 'lng-crear-alojamiento',
                            showPreview: true, previewContainerId: 'preview-ubicacion-crear-alojamiento',
                            autoSave: true, minChars: 3, debounceTime: 300
                        });
                    }
                }, 200);
            }

            function cerrarCrearAlojamientoPrograma() {
                const modal = document.getElementById('crearAlojamientoModalPrograma');
                modal.classList.remove('show');
                if (widgetUbicacionAlojamiento) { widgetUbicacionAlojamiento.destroy(); widgetUbicacionAlojamiento = null; }
                setTimeout(() => {
                    modal.style.display = 'none';
                    const serv = document.getElementById('serviciosModal');
                    if (serv) serv.style.zIndex = '';
                }, 300);
            }

            async function guardarAlojamientoEnPrograma() {
                const btn = event.target;
                const originalHTML = btn.innerHTML;
                try {
                    const nombre = document.getElementById('nombre-crear-alojamiento').value.trim();
                    const tipo = document.getElementById('tipo-crear-alojamiento').value;
                    const descripcion = document.getElementById('descripcion-crear-alojamiento').value.trim();
                    if (!nombre) { alert('Ingresa el nombre del alojamiento'); return; }
                    if (!tipo) { alert('Selecciona el tipo de alojamiento'); return; }
                    if (!descripcion) { alert('Ingresa una descripción'); return; }

                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

                    const baseURL = getBaseURL();

                    const formData = new FormData();
                    formData.append('action', 'create');
                    formData.append('type', 'alojamientos');
                    formData.append('nombre', nombre);
                    formData.append('tipo', tipo);
                    formData.append('categoria', tipo === 'hotel' ? document.getElementById('categoria-crear-alojamiento').value : '');
                    formData.append('ubicacion', document.getElementById('ubicacion-crear-alojamiento').value.trim());
                    formData.append('latitud', document.getElementById('lat-crear-alojamiento').value);
                    formData.append('longitud', document.getElementById('lng-crear-alojamiento').value);
                    formData.append('sitio_web', document.getElementById('sitio-web-crear-alojamiento').value.trim());
                    formData.append('descripcion', descripcion);

                    const imgFile = getImgSelectorFiles('alojamiento')[0];
                    if (imgFile) {
                        const comprimida = await _comprimirImagenParaSubida(imgFile);
                        formData.append('imagen', comprimida);
                    }

                    const response = await fetch(`${baseURL}/biblioteca/api`, { method: 'POST', body: formData });
                    const result = await response.json();
                    if (!result.success) throw new Error(result.error || 'Error al crear el alojamiento');

                    await _asignarServicioCreado(result.id, 'alojamiento');
                    showAlert('✅ Alojamiento creado y asignado al día', 'success');
                    cerrarCrearAlojamientoPrograma();
                    cerrarModalServicios();
                    await _refrescarDiaCreado();

                } catch (error) {
                    console.error('❌ Error al crear/asignar alojamiento:', error);
                    alert('Error: ' + error.message);
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                }
            }

            // Helpers compartidos: asignar el recurso recién creado al día y refrescar.
            async function _asignarServicioCreado(itemId, tipoServicio) {
                const baseURL = getBaseURL();
                const diaId = parseInt(_crearServicioDiaId);
                const resp = await fetch(`${baseURL}/modules/programa/servicios_api.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'add_service',
                        dia_id: diaId,
                        tipo_servicio: tipoServicio,
                        biblioteca_item_id: parseInt(itemId)
                    })
                });
                const r = await resp.json();
                if (!r.success) throw new Error(r.message || 'Error al asignar al día');
            }

            async function _refrescarDiaCreado() {
                const diaId = parseInt(_crearServicioDiaId);
                if (!diaId) return;
                seleccionarDiaEnSidebar(diaId);
                await cargarServiciosDia(diaId);
                await cargarServiciosParaContador(diaId);
            }

            // Redimensiona/comprime una imagen en el navegador antes de subirla,
            // para no agotar la memoria de PHP al procesarla en el servidor.
            // Si algo falla, devuelve el archivo original.
            function _comprimirImagenParaSubida(file, maxDim = 1920, quality = 0.85) {
                return new Promise((resolve) => {
                    if (!file || !file.type || !file.type.startsWith('image/') || file.type === 'image/gif') {
                        resolve(file);
                        return;
                    }
                    const url = URL.createObjectURL(file);
                    const img = new Image();
                    img.onload = () => {
                        let width = img.naturalWidth;
                        let height = img.naturalHeight;
                        if (width > maxDim || height > maxDim) {
                            if (width >= height) {
                                height = Math.round(height * maxDim / width);
                                width = maxDim;
                            } else {
                                width = Math.round(width * maxDim / height);
                                height = maxDim;
                            }
                        }
                        const canvas = document.createElement('canvas');
                        canvas.width = width;
                        canvas.height = height;
                        canvas.getContext('2d').drawImage(img, 0, 0, width, height);
                        URL.revokeObjectURL(url);
                        canvas.toBlob((blob) => {
                            if (!blob) { resolve(file); return; }
                            const nombre = (file.name || 'imagen').replace(/\.[^.]+$/, '') + '.jpg';
                            resolve(new File([blob], nombre, { type: 'image/jpeg' }));
                        }, 'image/jpeg', quality);
                    };
                    img.onerror = () => { URL.revokeObjectURL(url); resolve(file); };
                    img.src = url;
                });
            }

            // ── SELECTOR DE IMÁGENES REUTILIZABLE (estilo "Crear Nuevo Día") ──
            // Drag & drop + previsualización + eliminar, para N imágenes.
            const _imgSelectores = {};

            function initImgSelector(key, containerId, maxImages) {
                const container = document.getElementById(containerId);
                if (!container) return;
                _imgSelectores[key] = { files: [], max: maxImages, containerId };

                container.innerHTML = `
                    <div class="drop-zone-multiple"
                         style="border: 2px dashed #cbd5e0; border-radius: 12px; padding: 28px; text-align: center; background: linear-gradient(135deg, #f8fafc 0%, #edf2f7 100%); cursor: pointer;">
                        <div class="img-dz-content">
                            <div style="font-size: 40px; margin-bottom: 10px;">📸</div>
                            <div style="font-size: 16px; font-weight: 600; margin-bottom: 6px;">
                                ${maxImages > 1 ? 'Arrastra hasta ' + maxImages + ' imágenes aquí' : 'Arrastra una imagen aquí'}
                            </div>
                            <div style="font-size: 13px; color: #718096; margin-bottom: 12px;">o haz clic para seleccionar</div>
                            <button type="button" class="img-dz-btn"
                                style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer;">
                                📂 Seleccionar
                            </button>
                        </div>
                    </div>
                    <input type="file" class="img-dz-input" accept="image/*" ${maxImages > 1 ? 'multiple' : ''} style="display:none;">
                    <div class="img-dz-preview" style="margin-top: 16px; display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px;"></div>
                `;

                const dz = container.querySelector('.drop-zone-multiple');
                const input = container.querySelector('.img-dz-input');

                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(e =>
                    dz.addEventListener(e, ev => { ev.preventDefault(); ev.stopPropagation(); }));
                ['dragenter', 'dragover'].forEach(e =>
                    dz.addEventListener(e, () => { dz.style.borderColor = 'var(--primary-color)'; }));
                ['dragleave', 'drop'].forEach(e =>
                    dz.addEventListener(e, () => { dz.style.borderColor = '#cbd5e0'; }));
                dz.addEventListener('drop', e => _imgSelectorAdd(key, Array.from(e.dataTransfer.files)));
                dz.addEventListener('click', () => input.click());
                input.addEventListener('change', function () { _imgSelectorAdd(key, Array.from(this.files)); this.value = ''; });

                _imgSelectorRender(key);
            }

            function _imgSelectorAdd(key, files) {
                const s = _imgSelectores[key];
                if (!s) return;
                const imgs = files.filter(f => f.type.startsWith('image/'));
                if (!imgs.length) { alert('Solo se permiten archivos de imagen'); return; }
                const libres = s.max - s.files.length;
                if (libres <= 0) { alert('Ya alcanzaste el máximo de ' + s.max + ' imagen(es)'); return; }
                imgs.slice(0, libres).forEach(f => {
                    if (f.size > 20 * 1024 * 1024) { alert(`"${f.name}" es muy grande (máx. 20MB)`); return; }
                    s.files.push(f);
                });
                _imgSelectorRender(key);
            }

            function _imgSelectorRender(key) {
                const s = _imgSelectores[key];
                if (!s) return;
                const container = document.getElementById(s.containerId);
                if (!container) return;
                const preview = container.querySelector('.img-dz-preview');
                preview.innerHTML = '';
                s.files.forEach((file, index) => {
                    const reader = new FileReader();
                    reader.onload = e => {
                        const div = document.createElement('div');
                        div.style.cssText = 'position: relative; border-radius: 10px; overflow: hidden; border: 2px solid #e2e8f0;';
                        div.innerHTML = `
                            <img src="${e.target.result}" style="width: 100%; height: 120px; object-fit: cover;">
                            <div style="position:absolute; top:6px; left:6px; background: rgba(16,185,129,0.9); color:white; padding:3px 7px; border-radius:6px; font-size:11px;">${index + 1}</div>
                            <button type="button" onclick="quitarImgSelector('${key}', ${index})"
                                style="position:absolute; top:6px; right:6px; background: var(--primary-color); color:white; border:none; border-radius:50%; width:26px; height:26px; cursor:pointer;">×</button>`;
                        preview.appendChild(div);
                    };
                    reader.readAsDataURL(file);
                });
            }

            function quitarImgSelector(key, index) {
                const s = _imgSelectores[key];
                if (!s) return;
                s.files.splice(index, 1);
                _imgSelectorRender(key);
            }

            function getImgSelectorFiles(key) {
                return (_imgSelectores[key] && _imgSelectores[key].files) || [];
            }

            async function abrirModalServicios(tipoServicio, titulo = null) {
                const modal = document.getElementById('serviciosModal');
                const titleElement = document.getElementById('servicios-modal-title');

                // Establecer título
                const defaultTitle = isAddingAlternative ? `Agregar alternativa de ${tipoServicio}` : `Agregar ${tipoServicio}`;
                const icons = { 'actividad': 'fas fa-hiking', 'transporte': 'fas fa-car', 'alojamiento': 'fas fa-bed' };

                titleElement.innerHTML = `<i class="${icons[tipoServicio]}"></i> ${titulo || defaultTitle}`;

                // Configurar botón
                const btnAgregar = document.getElementById('btn-agregar-servicio');
                if (btnAgregar) {
                    const btnText = isAddingAlternative ? 'Agregar alternativa' : 'Agregar servicio';
                    btnAgregar.innerHTML = `<i class="fas fa-plus"></i> ${btnText}`;
                    btnAgregar.disabled = true;
                }

                resetAcomodacionesSelector();
                modal.style.display = 'block';
                await cargarServiciosBiblioteca(tipoServicio);
            }

            async function cargarServiciosBiblioteca(tipoServicio) {
                try {
                    let endpoint = '';
                    switch (tipoServicio) {
                        case 'actividad':
                            endpoint = 'actividades';
                            break;
                        case 'transporte':
                            endpoint = 'transportes';
                            break;
                        case 'alojamiento':
                            endpoint = 'alojamientos';
                            break;
                    }

                    const response = await fetch(`<?= APP_URL ?>/modules/biblioteca/api.php?action=list&type=${endpoint}`);
                    const result = await response.json();

                    if (result.success) {
                        renderizarServiciosBiblioteca(result.data, tipoServicio);
                    } else {
                        console.error('Error cargando servicios:', result.message);
                    }
                } catch (error) {
                    console.error('Error:', error);
                }
            }

            function renderizarServiciosBiblioteca(servicios, tipoServicio) {
                const container = document.getElementById('servicios-grid');
                if (!container) return;

                if (servicios.length === 0) {
                    container.innerHTML = `
            <div style="grid-column: 1 / -1;" class="empty-state">
                <i class="fas fa-${getServiceIcon(tipoServicio)}"></i>
                <h3>No hay ${tipoServicio}s en la biblioteca</h3>
                <p>Primero debes crear ${tipoServicio}s en la biblioteca</p>
                <a href="<?= APP_URL ?>/biblioteca" class="btn btn-primary">
                    <i class="fas fa-book"></i>
                    Ir a biblioteca
                </a>
            </div>
        `;
                    return;
                }

                container.innerHTML = servicios.map(servicio => {
                    const imagen = getServiceImage(servicio, tipoServicio);
                    const descripcion = getServiceDescription(servicio, tipoServicio);

                    return `
            <div class="biblioteca-item" data-servicio-id="${servicio.id}" onclick="seleccionarServicio(${servicio.id})">
                ${imagen ? `
                    <div class="biblioteca-item-image">
                        <img src="${imagen}" alt="${servicio.titulo || servicio.nombre}" loading="lazy">
                    </div>
                ` : `
                    <div class="biblioteca-item-image" style="display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                        <i class="fas fa-${getServiceIcon(tipoServicio)}" style="font-size: 32px; color: #dee2e6;"></i>
                    </div>
                `}
                <div class="biblioteca-item-content">
                    <div class="biblioteca-item-title">${servicio.titulo || servicio.nombre}</div>
                    <div class="biblioteca-item-description">
                        ${descripcion}
                    </div>
                    <div class="biblioteca-item-location">
                        <i class="fas fa-map-marker-alt"></i> 
                        ${getServiceLocation(servicio, tipoServicio)}
                    </div>
                </div>
            </div>
        `;
                }).join('');

                // Configurar búsqueda de servicios
                setupServiceSearch(servicios, tipoServicio);
            }

            function getServiceIcon(tipoServicio) {
                const icons = {
                    'actividad': 'hiking',
                    'transporte': 'car',
                    'alojamiento': 'bed'
                };
                return icons[tipoServicio] || 'star';
            }

            function getServiceImage(servicio, tipoServicio) {
                if (tipoServicio === 'actividad') {
                    return servicio.imagen1 || null;
                } else if (tipoServicio === 'alojamiento') {
                    return servicio.imagen || null;
                }
                return null; // Los transportes generalmente no tienen imagen
            }

            function getServiceDescription(servicio, tipoServicio) {
                if (tipoServicio === 'transporte') {
                    return `${servicio.medio} - ${servicio.descripcion || 'Sin descripción'}`;
                }
                return servicio.descripcion || 'Sin descripción disponible';
            }

            function getServiceLocation(servicio, tipoServicio) {
                if (tipoServicio === 'transporte') {
                    return `${servicio.lugar_salida || ''} → ${servicio.lugar_llegada || ''}`;
                }
                return servicio.ubicacion || servicio.lugar || 'Ubicación no especificada';
            }

            function setupServiceSearch(servicios, tipoServicio) {
                const searchInput = document.getElementById('search-servicios');
                if (!searchInput) return;

                // Limpiar listener anterior
                searchInput.removeEventListener('input', searchInput.searchHandler);

                searchInput.searchHandler = function (e) {
                    const searchTerm = normalizarBusqueda(e.target.value);
                    const filteredServicios = servicios.filter(servicio => {
                        const titulo = normalizarBusqueda(servicio.titulo || servicio.nombre || '');
                        const descripcion = normalizarBusqueda(servicio.descripcion || '');
                        const ubicacion = normalizarBusqueda(getServiceLocation(servicio, tipoServicio));

                        return titulo.includes(searchTerm) ||
                            descripcion.includes(searchTerm) ||
                            ubicacion.includes(searchTerm);
                    });

                    renderFilteredServicios(filteredServicios, tipoServicio);
                };

                searchInput.addEventListener('input', searchInput.searchHandler);
            }

            function renderFilteredServicios(servicios, tipoServicio) {
                const container = document.getElementById('servicios-grid');
                if (!container) return;

                if (servicios.length === 0) {
                    container.innerHTML = `
            <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #666;">
                <i class="fas fa-search" style="font-size: 48px; margin-bottom: 16px; opacity: 0.3;"></i>
                <h3>No se encontraron servicios</h3>
                <p>Intenta con otros términos de búsqueda</p>
            </div>
        `;
                    return;
                }

                container.innerHTML = servicios.map(servicio => {
                    const imagen = getServiceImage(servicio, tipoServicio);
                    const descripcion = getServiceDescription(servicio, tipoServicio);

                    return `
            <div class="biblioteca-item" data-servicio-id="${servicio.id}" onclick="seleccionarServicio(${servicio.id})">
                ${imagen ? `
                    <div class="biblioteca-item-image">
                        <img src="${imagen}" alt="${servicio.titulo || servicio.nombre}" loading="lazy">
                    </div>
                ` : `
                    <div class="biblioteca-item-image" style="display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                        <i class="fas fa-${getServiceIcon(tipoServicio)}" style="font-size: 32px; color: #dee2e6;"></i>
                    </div>
                `}
                <div class="biblioteca-item-content">
                    <div class="biblioteca-item-title">${servicio.titulo || servicio.nombre}</div>
                    <div class="biblioteca-item-description">
                        ${descripcion}
                    </div>
                    <div class="biblioteca-item-location">
                        <i class="fas fa-map-marker-alt"></i> 
                        ${getServiceLocation(servicio, tipoServicio)}
                    </div>
                </div>
            </div>
        `;
                }).join('');
            }

            function seleccionarServicio(servicioId) {
                // Remover selección previa
                document.querySelectorAll('#servicios-grid .biblioteca-item').forEach(item => {
                    item.classList.remove('selected');
                });

                // Seleccionar nuevo servicio
                const item = document.querySelector(`#servicios-grid [data-servicio-id="${servicioId}"]`);
                if (item) {
                    item.classList.add('selected');
                    selectedServicioId = servicioId;
                    document.getElementById('btn-agregar-servicio').disabled = false;

                    if (currentTipoServicio === 'alojamiento' && !isAddingAlternative) {
                        cargarAcomodacionesDelHotel(servicioId);
                    } else {
                        resetAcomodacionesSelector();
                    }

                    // Scroll suave hacia el elemento seleccionado
                    item.scrollIntoView({
                        behavior: 'smooth',
                        block: 'nearest',
                        inline: 'nearest'
                    });
                }
            }

            function resetAcomodacionesSelector() {
                selectedAcomodacionId = null;
                acomodacionesHotelActual = [];

                const wrapper = document.getElementById('acomodaciones-selector-wrapper');
                const select = document.getElementById('select-acomodacion-servicio');

                if (wrapper) wrapper.style.display = 'none';

                if (select) {
                    select.innerHTML = '<option value="">Sin acomodación por ahora</option>';
                    select.value = '';
                }
            }

            async function cargarAcomodacionesDelHotel(hotelId) {
                const wrapper = document.getElementById('acomodaciones-selector-wrapper');
                const select = document.getElementById('select-acomodacion-servicio');

                if (!wrapper || !select) return;

                wrapper.style.display = 'block';
                select.innerHTML = '<option value="">Cargando acomodaciones...</option>';
                selectedAcomodacionId = null;

                try {
                    const response = await fetch(`<?= APP_URL ?>/modules/biblioteca/api.php?action=get_acomodaciones&hotel_id=${hotelId}`);
                    const result = await response.json();

                    if (!result.success) {
                        select.innerHTML = '<option value="">Sin acomodación por ahora</option>';
                        showAlert(result.message || 'No se pudieron cargar las acomodaciones', 'error');
                        return;
                    }

                    acomodacionesHotelActual = result.data || [];

                    select.innerHTML = '<option value="">Sin acomodación por ahora</option>';

                    acomodacionesHotelActual.forEach(acomodacion => {
                        const label = formatearAcomodacionLabel(acomodacion);
                        select.innerHTML += `<option value="${acomodacion.id}">${escapeHtml(label)}</option>`;
                    });

                    select.onchange = function () {
                        selectedAcomodacionId = this.value ? parseInt(this.value) : null;
                    };

                } catch (error) {
                    console.error('Error cargando acomodaciones:', error);
                    select.innerHTML = '<option value="">Sin acomodación por ahora</option>';
                    showAlert('Error de conexión cargando acomodaciones', 'error');
                }
            }

            function formatearAcomodacionLabel(acomodacion) {
                const tipo = acomodacion.tipo_acomodacion || 'Acomodación';
                const capacidad = acomodacion.acomodacion ? `${acomodacion.acomodacion} pax` : '';
                const descripcion = acomodacion.descripcion ? ` · ${acomodacion.descripcion}` : '';

                return `${tipo}${capacidad ? ` (${capacidad})` : ''}${descripcion}`;
            }

            function abrirCrearAcomodacionDesdePrograma() {
                if (!selectedServicioId || currentTipoServicio !== 'alojamiento') {
                    showAlert('Primero selecciona un alojamiento', 'error');
                    return;
                }

                setInputValue('nueva-acomodacion-tipo', '');
                setInputValue('nueva-acomodacion-descripcion', '');
                setInputValue('nueva-acomodacion-capacidad', '1');

                const modal = document.getElementById('modal-crear-acomodacion-programa');
                if (modal) modal.style.display = 'flex';
            }

            function cerrarCrearAcomodacionDesdePrograma() {
                const modal = document.getElementById('modal-crear-acomodacion-programa');

                if (modal) {
                    modal.style.display = 'none';
                    delete modal.dataset.origen;
                    delete modal.dataset.hotelId;
                }
            }

            async function guardarNuevaAcomodacionDesdePrograma() {
                const tipo = getInputValue('nueva-acomodacion-tipo');
                const descripcion = getInputValue('nueva-acomodacion-descripcion');
                const capacidad = parseInt(getInputValue('nueva-acomodacion-capacidad') || 1);

                const modalAcomodacion = document.getElementById('modal-crear-acomodacion-programa');
                const hotelId = modalAcomodacion?.dataset?.hotelId || selectedServicioId;

                if (!hotelId) {
                    showAlert('Primero selecciona un alojamiento', 'error');
                    return;
                }

                if (!tipo) {
                    showAlert('El tipo de acomodación es obligatorio', 'error');
                    return;
                }

                if (!capacidad || capacidad < 1) {
                    showAlert('La capacidad debe ser mínimo 1', 'error');
                    return;
                }

                try {
                    const formData = new FormData();
                    formData.append('action', 'create_acomodacion');
                    formData.append('hotel_id', hotelId);
                    formData.append('tipo_acomodacion', tipo);
                    formData.append('descripcion', descripcion);
                    formData.append('acomodacion', capacidad);

                    const response = await fetch('<?= APP_URL ?>/modules/biblioteca/api.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (!result.success) {
                        console.error('Error real creando acomodación:', result);
                        showAlert(result.message || result.error || 'No se pudo crear la acomodación', 'error');
                        return;
                    }

                    const nueva = result.data;
                    cerrarCrearAcomodacionDesdePrograma();

                    if (alojamientoEditando) {
                        await cargarAcomodacionesEditor(alojamientoEditando);

                        if (nueva && nueva.id) {
                            const selectEditor = document.getElementById('edit-alojamiento-acomodacion');
                            if (selectEditor) {
                                selectEditor.value = nueva.id;
                            }
                        }
                    } else {
                        await cargarAcomodacionesDelHotel(hotelId);

                        if (nueva && nueva.id) {
                            const select = document.getElementById('select-acomodacion-servicio');
                            if (select) {
                                select.value = nueva.id;
                                selectedAcomodacionId = parseInt(nueva.id);
                            }
                        }
                    }

                    showAlert('Acomodación creada y seleccionada', 'success');

                } catch (error) {
                    console.error('Error creando acomodación:', error);
                    showAlert('Error de conexión creando acomodación', 'error');
                }
            }

            async function agregarServicioSeleccionado() {
                if (!selectedServicioId) {
                    showAlert('Selecciona un servicio primero', 'error');
                    return;
                }

                const btnAgregar = document.getElementById('btn-agregar-servicio');
                const originalText = btnAgregar.innerHTML;

                try {
                    btnAgregar.disabled = true;
                    btnAgregar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Agregando...';

                    let requestData;

                    if (isAddingAlternative) {
                        // Es alternativa — para hotel, pedir la diferencia de precio vs principal
                        const variacion = pedirVariacionAlternativa(currentTipoServicio);
                        if (variacion === null) {
                            btnAgregar.disabled = false;
                            btnAgregar.innerHTML = '<i class="fas fa-plus"></i> Agregar alternativa';
                            return;
                        }
                        requestData = {
                            action: 'add_alternative',
                            servicio_principal_id: alternativeParentId,
                            biblioteca_item_id: selectedServicioId,
                            variacion_precio: variacion
                        };
                    } else {
                        // Es servicio principal
                        requestData = {
                            action: 'add_service',
                            dia_id: currentDiaId,
                            tipo_servicio: currentTipoServicio,
                            biblioteca_item_id: selectedServicioId
                        };

                        if (currentTipoServicio === 'alojamiento' && selectedAcomodacionId) {
                            requestData.acomodacion_id = selectedAcomodacionId;
                        }
                    }

                    console.log('📝 Enviando:', requestData);

                    const response = await fetch('<?= APP_URL ?>/modules/programa/servicios_api.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(requestData)
                    });

                    const result = await response.json();

                    if (result.success) {
                        const mensaje = isAddingAlternative ? 'Alternativa agregada' : 'Servicio agregado';
                        showAlert(`✅ ${mensaje} exitosamente`, 'success');
                        cerrarModalServicios();

                        // Recargar servicios
                        if (selectedDayId) {
                            await cargarServiciosDia(selectedDayId);
                            await cargarServiciosParaContador(selectedDayId);
                        }
                    } else {
                        throw new Error(result.message || 'Error al agregar');
                    }

                } catch (error) {
                    console.error('❌ Error:', error);
                    showAlert('Error: ' + error.message, 'error');

                } finally {
                    btnAgregar.disabled = false;
                    btnAgregar.innerHTML = originalText;
                }
            }

            function cerrarModalServicios() {
                const modal = document.getElementById('serviciosModal');
                modal.style.display = 'none';

                // Limpiar TODO
                selectedServicioId = null;
                currentDiaId = null;
                currentTipoServicio = null;
                isAddingAlternative = false;
                alternativeParentId = null;

                // Restaurar botón
                const btnAgregar = document.getElementById('btn-agregar-servicio');
                if (btnAgregar) {
                    btnAgregar.disabled = true;
                    btnAgregar.innerHTML = '<i class="fas fa-plus"></i> Agregar servicio';
                }

                // Limpiar búsqueda y selecciones
                const searchInput = document.getElementById('search-servicios');
                if (searchInput) searchInput.value = '';

                document.querySelectorAll('#servicios-grid .biblioteca-item').forEach(item => {
                    item.classList.remove('selected');
                });

                console.log('✅ Modal cerrado - Todo limpio');
            }

            // ============================================================
            // FUNCIÓN CORREGIDA PARA CARGAR SERVICIOS DE UN DÍA
            // ============================================================
            async function cargarServiciosDia(diaId) {
                try {
                    console.log(`🔧 Cargando servicios para día ${diaId}...`);

                    const response = await fetch(`<?= APP_URL ?>/modules/programa/servicios_api.php?action=list&dia_id=${diaId}`);
                    const result = await response.json();

                    console.log(`📋 Servicios del día ${diaId}:`, result);

                    if (result.success) {
                        // ⭐ GUARDAR SERVICIOS EN EL OBJETO DEL DÍA
                        const dia = diasPrograma.find(d => d.id == diaId);
                        if (dia) {
                            dia.servicios = result.data || [];
                            console.log('✅ Servicios guardados en diasPrograma para día', diaId);
                        }

                        // ⭐ USAR LA FUNCIÓN CORRECTA
                        renderizarServiciosDia(diaId, result.data || []);
                    }

                } catch (error) {
                    console.error(`Error cargando servicios del día ${diaId}:`, error);
                }
            }

            function renderizarServiciosDia(diaId, servicios) {
                const container = document.getElementById(`services-${diaId}`);
                if (!container) {
                    console.error(`❌ No se encontró contenedor de servicios para día ${diaId}`);
                    return;
                }

                console.log(`🎨 Renderizando ${servicios.length} servicios CON ALTERNATIVAS para día ${diaId}`);

                // Actualizar contador en sidebar (solo contar principales)
                const principalesCount = servicios.length;
                actualizarContadorServicios(diaId, principalesCount);

                if (servicios.length === 0) {
                    container.innerHTML = `
            <p style="color: #666; font-style: italic; text-align: center; padding: 10px;">
                <i class="fas fa-info-circle"></i> No hay servicios agregados a este día
            </p>
        `;
                    return;
                }

                // ⭐ SOLUCIÓN: Renderizar con jerarquía
                container.innerHTML = `
        <h6 style="margin-bottom: 12px; color: #333; font-weight: 600;">
            <i class="fas fa-list"></i> Servicios agregados (${principalesCount}):
        </h6>
        ${servicios.map(servicio => renderizarServicioConAlternativas(servicio)).join('')}
    `;

                console.log(`✅ Servicios con alternativas renderizados para día ${diaId}`);
            }

            function abrirVistaPrevia() {
                if (!programaId) {
                    showAlert('Primero debes guardar el programa para ver la vista previa', 'error');
                    return;
                }

                // Usar la ruta manejada por index.php
                const previewUrl = `<?= APP_URL ?>/preview?id=${programaId}`;

                // Abrir en nueva pestaña
                window.open(previewUrl, '_blank');

                console.log('🔗 Abriendo vista previa en nueva pestaña:', previewUrl);
            }
            function getServiceIconByType(tipo, servicio = null) {
                // Si es transporte, usar icono específico según el medio
                if (tipo === 'transporte' && servicio && servicio.medio) {
                    const medio = servicio.medio.toLowerCase();

                    // Mapeo de medios de transporte a iconos Font Awesome
                    const transportIcons = {
                        'bus': 'bus',
                        'autobus': 'bus',
                        'autobús': 'bus',
                        'coche': 'car',
                        'auto': 'car',
                        'automóvil': 'car',
                        'taxi': 'taxi',
                        'avion': 'plane',
                        'avión': 'plane',
                        'vuelo': 'plane',
                        'aereo': 'plane',
                        'aéreo': 'plane',
                        'tren': 'train',
                        'ferrocarril': 'train',
                        'metro': 'subway',
                        'subte': 'subway',
                        'barco': 'ship',
                        'ferry': 'ship',
                        'lancha': 'ship',
                        'crucero': 'ship',
                        'bicicleta': 'bicycle',
                        'bici': 'bicycle',
                        'moto': 'motorcycle',
                        'motocicleta': 'motorcycle',
                        'scooter': 'motorcycle',
                        'camion': 'truck',
                        'camión': 'truck',
                        'van': 'shuttle-van',
                        'minivan': 'shuttle-van',
                        'minibus': 'shuttle-van',
                        'helicopter': 'helicopter',
                        'helicóptero': 'helicopter',
                        'teleférico': 'tram',
                        'teleferico': 'tram',
                        'cable': 'tram',
                        'funicular': 'tram',
                        'pie': 'walking',
                        'caminando': 'walking',
                        'caminata': 'walking',
                        'privado': 'car-side',
                        'compartido': 'shuttle-van',
                        'uber': 'taxi',
                        'cabify': 'taxi',
                        'didi': 'taxi'
                    };

                    // Buscar coincidencia en el mapeo
                    for (const [key, icon] of Object.entries(transportIcons)) {
                        if (medio.includes(key)) {
                            return icon;
                        }
                    }

                    // Si no encuentra coincidencia, usar icono genérico de transporte
                    return 'car';
                }

                // Para otros tipos de servicio
                const icons = {
                    'actividad': 'hiking',
                    'transporte': 'car',
                    'alojamiento': 'bed'
                };

                return icons[tipo] || 'star';
            }

            function getServiceSummary(servicio) {
                if (servicio.tipo_servicio === 'transporte') {
                    const salida = servicio.lugar_salida || '';
                    const llegada = servicio.lugar_llegada || '';
                    const medio = servicio.medio ? `${servicio.medio} - ` : '';
                    return `${medio}${salida} → ${llegada}`;
                }

                if (servicio.descripcion) {
                    return servicio.descripcion.length > 80 ?
                        servicio.descripcion.substring(0, 80) + '...' :
                        servicio.descripcion;
                }

                return 'Sin descripción disponible';
            }

            async function eliminarServicio(servicioId) {
                const confirmed = await showConfirmModal({
                    title: '¿Eliminar servicio?',
                    message: '¿Estás seguro de que quieres eliminar este servicio?',
                    details: 'Esta acción no se puede deshacer.',
                    icon: '<i class="fas fa-trash"></i>',
                    confirmText: 'Aceptar',
                    cancelText: 'Cancelar'
                });

                if (!confirmed) return;

                const btnEliminar = event.target.closest('.btn-remove-service');
                const originalContent = btnEliminar ? btnEliminar.innerHTML : '';

                try {
                    console.log('🗑️ Eliminando servicio ID:', servicioId);

                    // Mostrar estado de carga en el botón
                    if (btnEliminar) {
                        btnEliminar.disabled = true;
                        btnEliminar.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                    }

                    const response = await fetch('<?= APP_URL ?>/modules/programa/servicios_api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'delete',
                            servicio_id: servicioId
                        })
                    });

                    console.log('📡 Status de respuesta:', response.status);

                    if (!response.ok) {
                        throw new Error(`Error del servidor: ${response.status}`);
                    }

                    const responseText = await response.text();
                    console.log('📄 Respuesta:', responseText);

                    let result;
                    try {
                        result = JSON.parse(responseText);
                    } catch (parseError) {
                        console.warn('⚠️ No se pudo parsear la respuesta como JSON:', parseError);
                        if (response.ok) {
                            result = { success: true, message: 'Servicio eliminado exitosamente' };
                        } else {
                            throw new Error('Respuesta del servidor no válida');
                        }
                    }

                    if (result && result.success) {
                        showAlert('✅ Servicio eliminado exitosamente', 'success');

                        // ACTUALIZAR INMEDIATAMENTE EL DÍA SELECCIONADO
                        if (selectedDayId) {
                            console.log(`🔄 Recargando servicios del día seleccionado: ${selectedDayId}`);
                            await cargarServiciosDia(selectedDayId);
                            await cargarServiciosParaContador(selectedDayId);
                        } else {
                            console.warn('⚠️ No hay día seleccionado, recargando todos los días visibles');
                            // Si no hay día seleccionado, recargar contadores de todos los días
                            diasPrograma.forEach(async (dia) => {
                                await cargarServiciosParaContador(dia.id);
                            });
                        }

                    } else {
                        const errorMessage = result?.message || result?.error || 'Error desconocido al eliminar servicio';
                        throw new Error(errorMessage);
                    }

                } catch (error) {
                    console.error('❌ Error eliminando servicio:', error);
                    showAlert('Error eliminando servicio: ' + error.message, 'error');

                } finally {
                    // Restaurar botón siempre
                    if (btnEliminar) {
                        btnEliminar.disabled = false;
                        btnEliminar.innerHTML = originalContent || '<i class="fas fa-trash"></i>';
                    }
                }
            }


            function editarServicio(servicioId) {
                // TODO: Implementar edición de servicios
                showAlert('Función de edición en desarrollo', 'info');
            }

            // ============================================================
            // FUNCIONES DE MANEJO DE ERRORES
            // ============================================================
            function mostrarErrorDias(mensaje) {
                const container = document.getElementById('days-container');
                if (container) {
                    container.innerHTML = `
            <div class="error-state">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>Error al cargar días</h3>
                <p>${mensaje}</p>
                <button class="btn btn-primary" onclick="cargarDiasPrograma()">
                    <i class="fas fa-redo"></i>
                    Reintentar
                </button>
            </div>
        `;
                }
            }

            function mostrarErrorServicios(diaId, mensaje) {
                const container = document.getElementById(`services-${diaId}`);
                if (container) {
                    container.innerHTML = `
            <div style="color: #dc3545; text-align: center; padding: 10px; font-size: 14px;">
                <i class="fas fa-exclamation-triangle"></i>
                Error: ${mensaje}
                <br>
                <button class="btn btn-outline" style="margin-top: 8px; font-size: 12px;" onclick="cargarServiciosDia(${diaId})">
                    <i class="fas fa-redo"></i> Reintentar
                </button>
            </div>
        `;
                }
            }

            // ============================================================
            // FUNCIONES PARA PRECIOS
            // ============================================================

            async function cargarPreciosPrograma() {
                if (!programaId) return;

                try {
                    const response = await fetch(`<?= APP_URL ?>/modules/programa/precios_api.php?action=get&programa_id=${programaId}`);
                    const result = await response.json();

                    if (result.success && result.data) {
                        const data = result.data;
                        const form = document.getElementById('precio-form');

                        if (form) {
                            form.querySelector('[name="moneda"]').value = data.moneda || 'USD';

                            // NUEVOS CAMPOS
                            form.querySelector('[name="precio_adulto"]').value = data.precio_adulto || '';
                            form.querySelector('[name="precio_nino"]').value = data.precio_nino || '';
                            form.querySelector('[name="cantidad_adultos"]').value = data.cantidad_adultos || 1;
                            form.querySelector('[name="cantidad_ninos"]').value = data.cantidad_ninos || 0;

                            form.querySelector('[name="precio_total"]').value = data.precio_total || '';
                            //Se agrega query de selector de mostrar precio
                            form.querySelector('#mostrar-precio-toggle').checked = parseInt(data.mostrar_precio ?? 1) === 1;
                            //form.querySelector('[name="noches_incluidas"]').value = data.noches_incluidas || '';

                            // CARGAR TEXTAREAS CON PLANTILLA O DATOS GUARDADOS
                            form.querySelector('[name="precio_incluye"]').value = data.precio_incluye || '';
                            form.querySelector('[name="precio_no_incluye"]').value = data.precio_no_incluye || '';
                            form.querySelector('[name="condiciones_generales"]').value = data.condiciones_generales || '';
                            form.querySelector('[name="info_pasaporte"]').value = data.info_pasaporte || '';
                            form.querySelector('[name="info_seguros"]').value = data.info_seguros || '';
                            // Solapas adicionales (migración 010)
                            ['visados_entrada', 'requisitos_sanitarios', 'llegada_punto_encuentro', 'asistencia_emergencia', 'info_hoteles_servicios', 'informacion_practica'].forEach(function (f) {
                                const el = form.querySelector('[name="' + f + '"]');
                                if (el) { el.value = data[f] || ''; actualizarContadorTexto(f); }
                            });

                            // ACTUALIZAR CONTADORES DE CARACTERES
                            actualizarContadorTexto('precio_incluye');
                            actualizarContadorTexto('precio_no_incluye');
                            actualizarContadorTexto('condiciones_generales');
                            actualizarContadorTexto('info_pasaporte');
                            actualizarContadorTexto('info_seguros')

                            form.querySelector('[name="movilidad_reducida"]').checked = data.movilidad_reducida == 1;

                            // Actualizar íconos y calcular total
                            updateCurrencyIcons();
                            calcularPrecioTotal();
                        }

                        setTimeout(() => {
                            setupCharacterCounters();
                        }, 100);
                    }
                } catch (error) {
                    console.error('Error cargando precios:', error);
                }
            }

            // Función para actualizar contadores de caracteres de textareas
            function actualizarContadorTexto(fieldName) {
                const textarea = document.querySelector(`textarea[name="${fieldName}"]`);
                if (!textarea) return;

                const counter = document.getElementById(`${fieldName}-counter`);
                if (!counter) return;

                const currentLength = textarea.value.length;
                const maxChars = textarea.getAttribute('data-max-chars') || 3000;

                counter.textContent = `${currentLength}/${maxChars}`;

                // Cambiar color según uso
                if (currentLength > maxChars * 0.9) {
                    counter.style.color = '#e53e3e';
                } else if (currentLength > maxChars * 0.7) {
                    counter.style.color = '#d69e2e';
                } else {
                    counter.style.color = '#718096';
                }
            }

            function setupFileValidation() {
                console.log('📁 Configurando validación de archivos...');

                const fileInput = document.getElementById('cover-image');
                const fileInfo = document.getElementById('cover-image-info');

                if (fileInput && fileInfo) {
                    fileInput.addEventListener('change', function (e) {
                        const file = e.target.files[0];

                        if (!file) {
                            fileInfo.textContent = '';
                            fileInfo.className = 'file-info';
                            return;
                        }

                        // Validar tipo de archivo
                        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
                        const fileType = file.type.toLowerCase();

                        if (!allowedTypes.includes(fileType)) {
                            fileInfo.textContent = '❌ Tipo de archivo no válido. Use: JPEG, PNG, JPG, WebP';
                            fileInfo.className = 'file-info invalid';
                            fileInput.value = '';
                            return;
                        }

                        // Validar tamaño (10MB = 20971520 bytes)
                        const maxSize = 10485760;
                        if (file.size > maxSize) {
                            const sizeMB = (file.size / 1024 / 1024).toFixed(2);
                            fileInfo.textContent = `❌ Archivo muy grande: ${sizeMB}MB. Máximo: 10MB`;
                            fileInfo.className = 'file-info invalid';
                            fileInput.value = '';
                            return;
                        }

                        // Archivo válido
                        const sizeMB = (file.size / 1024 / 1024).toFixed(2);
                        const extension = fileType.split('/')[1].toUpperCase();

                        // Mostrar advertencia si es muy grande (>8MB)
                        if (file.size > 8388608) {
                            fileInfo.textContent = `⚠️ ${file.name} (${extension}, ${sizeMB}MB) - Archivo grande, puede tardar en subir`;
                            fileInfo.className = 'file-info warning';
                        } else {
                            fileInfo.textContent = `✅ ${file.name} (${extension}, ${sizeMB}MB) - Listo para subir`;
                            fileInfo.className = 'file-info valid';
                        }
                    });

                    console.log('✅ Validación de archivos configurada');
                }
            }
            async function guardarPrecios() {
                if (!programaId) {
                    showAlert('Primero debes guardar el programa', 'error');
                    return;
                }

                try {
                    const formData = new FormData(document.getElementById('precio-form'));
                    formData.append('action', 'save');
                    formData.append('programa_id', programaId);

                    const response = await fetch('<?= APP_URL ?>/modules/programa/precios_api.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    console.log('🔍 Respuesta del servidor:', result);

                    //Se eliminó  || response.ok en el if para evitar que siempre diga ok aún con errores

                    if (result.success) {
                        showAlert('✅ Precios guardados exitosamente', 'success');

                        // ACTUALIZAR INMEDIATAMENTE el campo de pasajeros
                        const cantidadAdultos = parseInt(formData.get('cantidad_adultos') || 0);
                        const cantidadNinos = parseInt(formData.get('cantidad_ninos') || 0);
                        const totalPasajeros = cantidadAdultos + cantidadNinos;

                        const passengersInput = document.getElementById('passengers');
                        if (passengersInput && totalPasajeros > 0) {
                            passengersInput.value = totalPasajeros;
                            console.log(`✅ Campo pasajeros actualizado a: ${totalPasajeros}`);
                        }
                    } else {
                        showAlert('❌ ' + (result.message || 'Error al guardar precios'), 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showAlert('❌ Error de conexión', 'error');
                }
            }

            // ============================================================
            // FUNCION PARA INFORMACION Adicional
            // ============================================================

            function cargarArchivos() {
                if (!programaId) return;
                fetch(`<?= APP_URL ?>/modules/programa/archivos_api.php?action=get&programa_id=${programaId}`)
                    .then(r => r.json())
                    .then(result => {
                        const lista = document.getElementById('adj-list');
                        const vacio = document.getElementById('adj-empty');
                        lista.innerHTML = '';
                        console.log("holaa");
                        const items = (result.success && result.data) ? result.data : [];
                        vacio.style.display = items.length ? 'none' : 'block';

                        items.forEach(item => {
                            lista.insertAdjacentHTML('beforeend', item.enlace
                                ? renderEnlace(item)
                                : renderArchivo(item));
                        });
                    })
                    .catch(err => console.error('Error cargando adjuntos:', err));
            }
            function renderEnlace(item) {
                const url = escapeHtml(item.enlace);
                const titulo = item.titulo ? escapeHtml(item.titulo) : '';
                const nombre = titulo || url;
                const meta = titulo ? url : 'Enlace';
                return `
                    <div class="adj-item" data-id="${item.id}">
                        <div class="adj-item-icon adj-icon-link"><i class="fas fa-link"></i></div>
                        <div class="adj-item-info">
                            <a href="${url}" class="adj-item-name" target="_blank" rel="noopener">${nombre}</a>
                            <span class="adj-item-meta">${meta}</span>
                        </div>
                        <button type="button" class="adj-item-action" title="Editar título" onclick="editarTituloAdjunto(${item.id})">
                            <i class="fas fa-pen"></i>
                        </button>
                        <a href="${url}" class="adj-item-action" title="Abrir" target="_blank" rel="noopener">
                            <i class="fas fa-external-link-alt"></i>
                        </a>
                        <button type="button" class="adj-item-action adj-item-delete"
                                title="Eliminar" onclick="eliminarArchivos(${item.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>`;
            }
            function renderArchivo(item) {
                const url = escapeHtml(item.archivo);
                const ext = (item.archivo.split('.').pop() || '').toLowerCase();
                const fileName = escapeHtml(decodeURIComponent(item.archivo.split('/').pop() || item.archivo));
                const titulo = item.titulo ? escapeHtml(item.titulo) : '';
                const nombre = titulo || fileName;
                const meta = titulo ? `${fileName} · ${ext.toUpperCase()}` : `Archivo · ${ext.toUpperCase()}`;
                const icono = iconoPorExtension(ext);
                return `
                    <div class="adj-item" data-id="${item.id}">
                        <div class="adj-item-icon adj-icon-file"><i class="fas ${icono}"></i></div>
                        <div class="adj-item-info">
                            <a href="${url}" class="adj-item-name" target="_blank" rel="noopener">${nombre}</a>
                            <span class="adj-item-meta">${meta}</span>
                        </div>
                        <button type="button" class="adj-item-action" title="Editar título" onclick="editarTituloAdjunto(${item.id})">
                            <i class="fas fa-pen"></i>
                        </button>
                        <a href="${url}" class="adj-item-action" title="Descargar" download target="_blank" rel="noopener">
                            <i class="fas fa-download"></i>
                        </a>
                        <button type="button" class="adj-item-action adj-item-delete"
                                title="Eliminar" onclick="eliminarArchivos(${item.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>`;
            }
            function iconoPorExtension(ext) {
                const mapa = {
                    pdf: 'fa-file-pdf',
                    doc: 'fa-file-word', docx: 'fa-file-word',
                    xls: 'fa-file-excel', xlsx: 'fa-file-excel', csv: 'fa-file-csv',
                    ppt: 'fa-file-powerpoint', pptx: 'fa-file-powerpoint',
                    jpg: 'fa-file-image', jpeg: 'fa-file-image', png: 'fa-file-image',
                    webp: 'fa-file-image', gif: 'fa-file-image',
                    zip: 'fa-file-archive', rar: 'fa-file-archive',
                    txt: 'fa-file-lines'
                };
                return mapa[ext] || 'fa-file';
            }

            // Editar (o quitar) el título de un adjunto ya subido — archivo o enlace.
            async function editarTituloAdjunto(id) {
                if (!id) return;
                const actual = (document.querySelector(`.adj-item[data-id="${id}"] .adj-item-name`)?.textContent || '').trim();
                const titulo = prompt('Título del adjunto (deja vacío para quitarlo):', actual);
                if (titulo === null) return; // canceló
                try {
                    const fd = new FormData();
                    fd.append('id', id);
                    fd.append('titulo', titulo.trim());
                    const r = await fetch(`<?= APP_URL ?>/modules/programa/archivos_api.php?action=update_titulo`, { method: 'POST', body: fd });
                    const d = await r.json();
                    if (d && d.success) { cargarArchivos(); showAlert('Título actualizado', 'success'); }
                    else { showAlert((d && d.message) || 'No se pudo actualizar el título', 'error'); }
                } catch (e) { showAlert('Error al actualizar el título', 'error'); }
            }

            async function guardarArchivos() {
                if (!programaId) return;

                try {
                    const archivos = document.getElementById('adj-file-input').files;
                    const enlace = document.getElementById('adj-link-input').value.trim();
                    const tituloEnlace = (document.getElementById('adj-link-titulo')?.value || '').trim();
                    const fd = new FormData();
                    fd.append('programa_id', programaId);
                    if (enlace) fd.append('enlace', enlace);
                    if (tituloEnlace) fd.append('titulo', tituloEnlace); // título opcional para el enlace
                    for (const f of archivos) fd.append('archivos[]', f);   // multipart real

                    if (!enlace && archivos.length === 0) {
                        showAlert('Añade un archivo o un enlace', 'error');
                        return;
                    }
                    const response = await fetch(`<?= APP_URL ?>/modules/programa/archivos_api.php?action=save&programa_id=${programaId}`, {
                        method: 'POST',
                        body: fd
                    }
                    );
                    const result = await response.json();

                    if (result.success) {
                        document.getElementById('adj-file-input').value = "";
                        document.getElementById('adj-link-input').value = "";
                        const tEl = document.getElementById('adj-link-titulo'); if (tEl) tEl.value = "";
                        cargarArchivos();
                    } else {
                        showAlert(result.message || 'Error al guardar', 'error');
                    }
                } catch (error) {
                    console.log(error);
                }
            }
            async function eliminarArchivos(adjuntoId) {
                if (!adjuntoId) return;
                if (!confirm('¿Eliminar este adjunto?')) return;

                const fd = new FormData();
                fd.append('id', adjuntoId);

                const resp = await fetch(`<?= APP_URL ?>/modules/programa/archivos_api.php?action=delete`, {
                    method: 'POST',
                    body: fd
                });
                const result = await resp.json();
                if (result.success) {
                    cargarArchivos();
                    showAlert('Adjunto eliminado', 'success');
                }
            }

            // ============================================================
            // FUNCIONES AUXILIARES
            // ============================================================
            function showAlert(message, type = 'info') {
                const toast = document.createElement('div');
                toast.className = `toast ${type}`;

                const iconClass = type === 'success' ? 'fa-circle-check' : type === 'error' ? 'fa-circle-xmark' : 'fa-circle-info';
                toast.innerHTML = `
        <div style="display: flex; align-items: center; gap: 10px;">
            <i class="fas ${iconClass}" style="font-size: 20px;"></i>
            <span>${message}</span>
        </div>
    `;

                document.body.appendChild(toast);

                setTimeout(() => toast.classList.add('show'), 100);

                setTimeout(() => {
                    toast.classList.remove('show');
                    setTimeout(() => document.body.removeChild(toast), 300);
                }, 4000);
            }

            // ============================================================
            // COMPARTIR TOUR CON SUBAGENCIAS (modules/subagencias/api.php)
            // ============================================================
            let subagLista = [];
            let subagSeleccion = new Set();
            let subagOriginal = new Set();

            async function subagApi(action, params = {}, method = 'GET') {
                try {
                    if (method === 'GET') {
                        const qs = new URLSearchParams({ action, ...params }).toString();
                        const r = await fetch(`${APP_URL}/subagencias/api?${qs}`);
                        return await r.json();
                    }
                    const fd = new FormData();
                    fd.append('action', action);
                    Object.entries(params).forEach(([k, v]) => fd.append(k, v));
                    const r = await fetch(`${APP_URL}/subagencias/api`, { method: 'POST', body: fd });
                    return await r.json();
                } catch (e) { return { success: false, message: 'Error de red' }; }
            }

            async function abrirModalSubagencias() {
                if (!programaId) { showAlert('Guarda el programa antes de compartirlo', 'error'); return; }

                const rL = await subagApi('list_subagencias');
                subagLista = (rL && rL.success) ? (rL.data || []) : [];

                if (!subagLista.length) {
                    showAlert('No hay subagencias. Créalas en Usuarios (rol Subagencia).', 'info');
                    return;
                }

                const rA = await subagApi('list_tour_subagencias', { solicitud_id: programaId });
                const asignadas = (rA && rA.success) ? (rA.data || []) : [];
                subagSeleccion = new Set(asignadas.map(Number));
                subagOriginal = new Set(asignadas.map(Number));

                renderSubagChips();
                document.getElementById('modal-subagencias').style.display = 'flex';
            }

            function cerrarModalSubagencias() {
                document.getElementById('modal-subagencias').style.display = 'none';
            }

            function renderSubagChips() {
                const el = document.getElementById('subagChips');
                el.innerHTML = subagLista.map(s => {
                    const sel = subagSeleccion.has(Number(s.id));
                    const nombre = s.nombre_comercial || s.full_name || s.username || ('Subagencia #' + s.id);
                    const base = 'display:inline-flex;align-items:center;gap:6px;padding:7px 13px;border-radius:20px;font-size:13px;font-weight:600;cursor:pointer;border:1px solid;transition:all .15s;';
                    const style = sel
                        ? base + 'background:#7c3aed20;color:#7c3aed;border-color:#7c3aed;'
                        : base + 'background:#f1f5f9;color:#64748b;border-color:#e2e8f0;';
                    const check = sel ? '<i class="fas fa-check" style="font-size:11px;"></i>' : '';
                    return `<span style="${style}" onclick="toggleSubagChip(${s.id})">${check}${escapeHtmlSub(nombre)}</span>`;
                }).join('');
            }

            function escapeHtmlSub(s) {
                return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
            }

            function toggleSubagChip(id) {
                id = Number(id);
                if (subagSeleccion.has(id)) subagSeleccion.delete(id);
                else subagSeleccion.add(id);
                renderSubagChips();
            }

            async function guardarSubagencias() {
                if (!programaId) return;
                const aAsignar = [...subagSeleccion].filter(id => !subagOriginal.has(id));
                const aQuitar = [...subagOriginal].filter(id => !subagSeleccion.has(id));

                let ok = true;
                for (const subId of aAsignar) {
                    const r = await subagApi('assign_tour', { sub_user_id: subId, solicitud_id: programaId }, 'POST');
                    if (!r || !r.success) ok = false;
                }
                for (const subId of aQuitar) {
                    const r = await subagApi('unassign_tour', { sub_user_id: subId, solicitud_id: programaId }, 'POST');
                    if (!r || !r.success) ok = false;
                }

                if (ok) { showAlert('Subagencias actualizadas', 'success'); cerrarModalSubagencias(); }
                else showAlert('Algunos cambios no se pudieron guardar', 'error');
            }

            function toggleSection(header) {
                const body = header.nextElementSibling;
                const icon = header.querySelector('.expand-icon');

                if (body.style.display === 'none' || body.classList.contains('collapsed')) {
                    body.style.display = 'block';
                    body.classList.remove('collapsed');
                    header.classList.remove('collapsed');
                    icon.style.transform = 'rotate(0deg)';
                } else {
                    body.style.display = 'none';
                    body.classList.add('collapsed');
                    header.classList.add('collapsed');
                    icon.style.transform = 'rotate(180deg)';
                }
            }



            // Cerrar modales con tecla Escape
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    const bibliotecaModal = document.getElementById('bibliotecaModal');
                    const serviciosModal = document.getElementById('serviciosModal');

                    if (bibliotecaModal.style.display === 'block') {
                        cerrarModalBiblioteca();
                    }

                    if (serviciosModal.style.display === 'block') {
                        cerrarModalServicios();
                    }
                }
            });

            console.log('✅ Script de programa.php cargado completamente');

            // ============================================================
            // JAVASCRIPT PARA BARRA LATERAL DE DÍAS
            // ============================================================

            let selectedDayId = null;

            // Función modificada para renderizar días en sidebar
            function renderizarDias() {
                console.log(`🎨 Renderizando ${diasPrograma.length} días en sidebar...`);

                renderizarSidebarDias();

                // ✅ SOLO mostrar mensaje vacío si NO hay días
                // Si hay días, la selección automática se encarga del detalle
                if (diasPrograma.length === 0) {
                    renderizarDetalleVacio();
                }
            }

            function renderizarSidebarDias() {
                const sidebarContainer = document.getElementById('days-sidebar-list');
                if (!sidebarContainer) {
                    console.error('❌ No se encontró el contenedor days-sidebar-list');
                    return;
                }

                if (diasPrograma.length === 0) {
                    sidebarContainer.innerHTML = `
            <div class="empty-sidebar">
                <i class="fas fa-calendar-plus"></i>
                <h3>No hay días</h3>
                <p>Agrega tu primer día</p>
                <button class="btn btn-primary" onclick="agregarDia()">
                    <i class="fas fa-plus"></i>
                    Agregar día
                </button>
            </div>
        `;
                    return;
                }

                const diasOrdenados = [...diasPrograma].sort((a, b) => (a.dia_numero || 0) - (b.dia_numero || 0));

                let diaActual = 1;

                sidebarContainer.innerHTML = diasOrdenados.map((dia, index) => {
                    const duracion = parseInt(dia.duracion_estancia) || 1;
                    const diaFinal = diaActual + duracion - 1;

                    // Texto del rango de días
                    const rangoTexto = duracion === 1
                        ? `Día ${diaActual}`
                        : `Días ${diaActual}-${diaFinal}`;

                    const duracionTexto = duracion > 1 ? ` (${duracion} días)` : '';
                    const titulo = dia.titulo || 'Día sin título';
                    const ubicacion = dia.ubicacion || 'Sin ubicación';

                    const html = `
            <div class="day-sidebar-item ${selectedDayId === dia.id ? 'active' : ''}" 
                data-dia-id="${dia.id}" 
                data-dia-numero="${dia.dia_numero}"
                onclick="seleccionarDiaEnSidebar(${dia.id})">
                
                <div class="drag-handle" title="Arrastra para reordenar">
                    <i class="fas fa-grip-vertical"></i>
                </div>
                
                <div class="day-services-count" id="services-count-${dia.id}">0</div>
                
                ${duracion > 1 ? '<div class="multi-day-indicator"></div>' : ''}
                
                <div class="day-item-header">
                    <div class="day-number-sidebar">
                        ${rangoTexto}
                        ${duracion > 1 ? '<span class="duration-badge">' + duracion + 'd</span>' : ''}
                    </div>
                    
                
                    
                    <!-- ⭐ BOTONES DE EDITAR Y ELIMINAR AQUÍ -->
                    <div class="day-actions-sidebar">
                        <button class="day-action-btn edit" onclick="event.stopPropagation(); abrirEdicionDia(${dia.id})" title="Editar día">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="day-action-btn delete" onclick="event.stopPropagation(); eliminarDia(${dia.id})" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                
                <div class="day-item-title">${titulo}</div>
                <div class="day-item-location">
                    <i class="fas fa-map-marker-alt"></i>
                    ${ubicacion}
                </div>
            </div>
        `;

                    diaActual += duracion;
                    return html;
                }).join('');

                // Cargar servicios para actualizar contadores
                diasOrdenados.forEach(dia => {
                    cargarServiciosParaContador(dia.id);
                });

                // Seleccionar primer día si no hay ninguno seleccionado
                if (!selectedDayId && diasOrdenados.length > 0) {
                    seleccionarDiaEnSidebar(diasOrdenados[0].id);
                }

                // ✅ INICIALIZAR DRAG & DROP DESPUÉS DE RENDERIZAR
                setTimeout(() => {
                    initializeDragAndDrop();
                }, 100);
            }

            let sortableInstance = null;

            // ── AUTOSCROLL MANUAL DURANTE EL DRAG DE DÍAS ──
            // SortableJS no puede autoscrollear de forma fiable aquí porque el
            // contenedor real que se desplaza es la ventana. Lo manejamos a mano.
            let _diaAutoScrollRAF = null;
            let _diaDragPointerY = 0;

            function _diaTrackPointer(e) {
                // dragover (mouse) y touchmove (táctil)
                const y = e.touches ? (e.touches[0] && e.touches[0].clientY) : e.clientY;
                if (typeof y === 'number') _diaDragPointerY = y;
            }

            function _iniciarAutoScrollDia() {
                document.addEventListener('dragover', _diaTrackPointer, true);
                document.addEventListener('touchmove', _diaTrackPointer, { capture: true, passive: true });

                const EDGE = 100;       // distancia al borde (px) donde empieza el scroll
                const MAX_SPEED = 165;   // velocidad máxima (px por frame)

                const step = () => {
                    const vh = window.innerHeight;
                    let speed = 0;
                    if (_diaDragPointerY < EDGE) {
                        speed = -Math.ceil(((EDGE - _diaDragPointerY) / EDGE) * MAX_SPEED);
                    } else if (_diaDragPointerY > vh - EDGE) {
                        speed = Math.ceil(((_diaDragPointerY - (vh - EDGE)) / EDGE) * MAX_SPEED);
                    }
                    if (speed !== 0) window.scrollBy(0, speed);
                    _diaAutoScrollRAF = requestAnimationFrame(step);
                };
                _diaAutoScrollRAF = requestAnimationFrame(step);
            }

            function _detenerAutoScrollDia() {
                if (_diaAutoScrollRAF) cancelAnimationFrame(_diaAutoScrollRAF);
                _diaAutoScrollRAF = null;
                document.removeEventListener('dragover', _diaTrackPointer, true);
                document.removeEventListener('touchmove', _diaTrackPointer, true);
            }

            /**
             * Inicializar drag & drop para días
             */
            function initializeDragAndDrop() {
                const daysList = document.getElementById('days-sidebar-list');

                if (!daysList || sortableInstance) return;

                // Destruir instancia anterior si existe
                if (sortableInstance) {
                    sortableInstance.destroy();
                }

                sortableInstance = new Sortable(daysList, {
                    animation: 200,
                    easing: "cubic-bezier(0.4, 0, 0.2, 1)",
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    dragClass: 'sortable-drag',
                    handle: '.day-sidebar-item', // Todo el item es arrastrable

                    // Desactivamos el autoscroll nativo de SortableJS (provoca
                    // temblor con este layout) y usamos el manual de abajo.
                    scroll: false,

                    // Prevenir drag en botones y controles
                    filter: '.day-actions-sidebar, .day-controls, .estancia-btn',
                    preventOnFilter: true,

                    // Evento al empezar a arrastrar
                    onStart: function (evt) {
                        console.log('🎯 Iniciando drag del día:', evt.oldIndex + 1);
                        mostrarMensajeAyuda('Arrastra para reordenar los días');
                        _iniciarAutoScrollDia();
                    },

                    // Evento al soltar
                    onEnd: function (evt) {
                        _detenerAutoScrollDia();

                        const oldIndex = evt.oldIndex;
                        const newIndex = evt.newIndex;

                        console.log(`📦 Día movido de posición ${oldIndex + 1} a ${newIndex + 1}`);

                        if (oldIndex !== newIndex) {
                            reordenarDias(oldIndex, newIndex);
                        }

                        ocultarMensajeAyuda();
                    }
                });

                console.log('✅ Drag & drop inicializado correctamente');
            }

            async function reordenarDias(oldIndex, newIndex) {
                try {
                    console.log(`📦 Reordenando: posición ${oldIndex + 1} → ${newIndex + 1}`);

                    showAlert('🔄 Reordenando días...', 'info');

                    // Obtener el nuevo orden de IDs basado en dia_numero
                    const diasOrdenados = [...diasPrograma].sort((a, b) =>
                        (a.dia_numero || 0) - (b.dia_numero || 0)
                    );

                    const nuevoOrden = diasOrdenados.map(dia => dia.id);

                    // Mover el elemento en el array
                    const [movedItem] = nuevoOrden.splice(oldIndex, 1);
                    nuevoOrden.splice(newIndex, 0, movedItem);

                    console.log('📋 Nuevo orden de IDs:', nuevoOrden);
                    console.log('🎯 Programa ID:', programaId);

                    // Enviar al servidor
                    const response = await fetch('<?= APP_URL ?>/modules/programa/dias_api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'reorder',
                            solicitud_id: programaId,
                            nuevo_orden: nuevoOrden
                        })
                    });

                    console.log('📡 Status:', response.status);

                    // Leer la respuesta como texto primero
                    const responseText = await response.text();
                    console.log('📄 Respuesta raw:', responseText);

                    if (!response.ok) {
                        throw new Error(`Error HTTP: ${response.status} - ${responseText}`);
                    }

                    // Intentar parsear JSON
                    let result;
                    try {
                        result = JSON.parse(responseText);
                    } catch (e) {
                        console.error('❌ Error parseando JSON:', e);
                        throw new Error('Respuesta inválida del servidor');
                    }

                    if (result.success) {
                        showAlert('✅ Días reordenados correctamente', 'success');

                        // Recargar días para actualizar la vista
                        await cargarDiasPrograma();

                    } else {
                        throw new Error(result.error || 'Error desconocido');
                    }

                } catch (error) {
                    console.error('❌ Error reordenando días:', error);
                    showAlert('❌ Error al reordenar: ' + error.message, 'error');

                    // Recargar para restaurar orden original
                    await cargarDiasPrograma();
                }
            }

            /**
             * Mostrar mensaje de ayuda durante drag
             */
            function mostrarMensajeAyuda(mensaje) {
                let helper = document.querySelector('.drag-helper');

                if (!helper) {
                    helper = document.createElement('div');
                    helper.className = 'drag-helper';
                    document.body.appendChild(helper);
                }

                helper.textContent = mensaje;
                helper.classList.add('show');
            }

            /**
             * Ocultar mensaje de ayuda
             */
            function ocultarMensajeAyuda() {
                const helper = document.querySelector('.drag-helper');
                if (helper) {
                    helper.classList.remove('show');
                    setTimeout(() => helper.remove(), 300);
                }
            }

            function seleccionarDiaEnSidebar(diaId) {
                console.log(`📌 Seleccionando día ${diaId} en sidebar`);

                // Remover clase active de todos los items
                document.querySelectorAll('.day-sidebar-item').forEach(item => {
                    item.classList.remove('active');
                });

                // Agregar clase active al item seleccionado
                const selectedItem = document.querySelector(`[data-dia-id="${diaId}"]`);
                if (selectedItem) {
                    selectedItem.classList.add('active');
                }

                selectedDayId = diaId;

                // Renderizar detalle del día seleccionado
                renderizarDetalleDia(diaId);

                cargarVuelosDia(diaId);

                // Cargar servicios del día seleccionado
                cargarServiciosDia(diaId);
                cargarUbicacionesSecundariasDia(diaId);

                // RECONFIGURAR manejadores después de renderizar
                setTimeout(() => {
                    setupMealHandlers();
                    cargarComidasDia(diaId);
                }, 100);
            }

            function renderizarDetalleDia(diaId) {
                const detailContainer = document.getElementById('day-detail-content');
                if (!detailContainer) {
                    console.error('❌ No se encontró el contenedor day-detail-content');
                    return;
                }

                const dia = diasPrograma.find(d => d.id == diaId);
                if (!dia) {
                    console.error(`❌ No se encontró el día con ID ${diaId}`);
                    return;
                }

                const duracion = parseInt(dia.duracion_estancia) || 1;
                //const diaNumero = dia.dia_numero || 1;
                //const diaFinal = diaNumero + duracion - 1;
                //const rangoTexto = duracion === 1 
                //    ? `Día ${diaNumero}` 
                //   : `Días ${diaNumero}-${diaFinal}`;
                //const fechaDia = dia.fecha_dia ? new Date(dia.fecha_dia).toLocaleDateString('es-ES') : null;
                //  ----------------------------------------Esto se cambia para poder calcular día en fecha
                const diasOrdenados = [...diasPrograma].sort((a, b) => (a.dia_numero || 0) - (b.dia_numero || 0));

                let diaActual = 1;
                for (const d of diasOrdenados) {
                    if (d.id == dia.id) break;
                    diaActual += parseInt(d.duracion_estancia) || 1;
                }

                const diaFinal = diaActual + duracion - 1;

                const rangoTexto = duracion === 1
                    ? `Día ${diaActual}`
                    : `Días ${diaActual}-${diaFinal}`;



                const duracionTexto = duracion > 1 ? ` (${duracion} días)` : '';
                const titulo = dia.titulo || 'Día sin título';
                const descripcion = dia.descripcion || '';
                const ubicacion = dia.ubicacion || 'Sin ubicación especificada';
                const fechaDia = dia.fecha_calculada
                    ? new Date(dia.fecha_calculada + 'T00:00:00').toLocaleDateString('es-ES')
                    : null;

                detailContainer.innerHTML = `
        <div class="day-detail-header">
            <div class="day-detail-number">${rangoTexto}</div>
            <div class="day-detail-title">${titulo}${duracionTexto}</div>
            <div class="day-flexy">
            <div class="day-controls-detail">
                <button class="estancia-btn" onclick="cambiarEstancia(${dia.id}, ${duracion - 1})" 
                        ${duracion <= 1 ? 'disabled' : ''}>➖</button>
                <span class="estancia-display">${duracion}</span>
                <button class="estancia-btn" onclick="cambiarEstancia(${dia.id}, ${duracion + 1})" 
                        ${duracion >= 30 ? 'disabled' : ''}>➕</button>
            </div>
            <div class="day-flexy2">
            <div class="day-detail-meta">
                <div id="ubicaciones-display-${dia.id}" style="display: flex; flex-direction: column; gap: 8px;">
                    <span>
                        <i class="fas fa-map-marker-alt"></i> 
                        ${ubicacion}
                    </span>
                    <!-- Las ubicaciones secundarias se cargarán aquí -->
                </div>
                </div><div class="day-fecha">
                ${fechaDia ? `
                    <span style="margin-top: 8px;">
                        <i class="fas fa-calendar"></i> 
                        ${fechaDia}
                    </span>
                ` : ''}
            </div></div>
            </div>
        </div>
        
        <div class="day-detail-body">
            ${renderizarImagenesDia(dia)}
            
            ${descripcion ? `
                <div class="day-description" style="margin-bottom: 20px; color: #666; line-height: 1.6;">
                    ${descripcion}
                </div>
            ` : ''}
            
            <!-- Servicios del día -->
            <div class="day-services">
                <div class="services-header">
                    <h5><i class="fas fa-plus-circle"></i> Agregar servicios al día:</h5>
                </div>
                <div class="service-buttons">
                    <button class="service-btn" onclick="agregarServicio(${dia.id}, 'actividad')">
                        <i class="fas fa-hiking"></i>
                        Actividad
                    </button>
                    <button class="service-btn" onclick="agregarServicio(${dia.id}, 'transporte')">
                        <i class="fas fa-car"></i>
                        Transporte
                    </button>
                    <button class="service-btn" onclick="agregarServicio(${dia.id}, 'alojamiento')">
                        <i class="fas fa-bed"></i>
                        Alojamiento
                    </button>
                </div>

                ${renderizarBloqueVuelosDia(dia.id)}
                
                <!-- Opciones de comidas -->
                <div class="meals-section">
                    <h6><i class="fas fa-utensils"></i> Comidas:</h6>
                    <div class="meals-options">
                        <label class="meal-option">
                            <input type="radio" name="meals_${dia.id}" value="incluidas">
                            <span>Comidas incluidas</span>
                        </label>
                        <label class="meal-option">
                            <input type="radio" name="meals_${dia.id}" value="no_incluidas" checked>
                            <span>Comidas no incluidas</span>
                        </label>
                    </div>
                    <div class="meal-details" id="meal-details-${dia.id}" style="display: none;">
                        <div class="meal-checkboxes">
                            <label class="meal-checkbox">
                                <input type="checkbox" name="meal_desayuno_${dia.id}">
                                <span>Desayuno</span>
                            </label>
                            <label class="meal-checkbox">
                                <input type="checkbox" name="meal_almuerzo_${dia.id}">
                                <span>Almuerzo</span>
                            </label>
                            <label class="meal-checkbox">
                                <input type="checkbox" name="meal_cena_${dia.id}">
                                <span>Cena</span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Lista de servicios agregados -->
                <div class="added-services" id="services-${dia.id}">
                    <div class="loading-services">
                        <i class="fas fa-spinner fa-spin"></i> Cargando servicios...
                    </div>
                </div>
            </div>
        </div>
    `;

                // Agregar formulario inline de edición si no existe
                let editForm = document.getElementById(`edit-dia-form-${diaId}`);
                if (!editForm) {
                    const formHTML = `
            <div id="edit-dia-form-${diaId}" class="edit-inline-form" style="display: none;">
                <div class="edit-form-header">
                    <h4><i class="fas fa-edit"></i> Editar Día ${dia.dia_numero}</h4>
                    <button class="btn-close-edit" onclick="cerrarEdicionDia(${diaId})">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="edit-form-body">
                    <!-- Título -->
                    <div class="form-group">
                        <label for="edit-dia-titulo-${diaId}">
                            Título <span class="required">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="edit-dia-titulo-${diaId}" 
                            class="form-control"
                            value="${dia.titulo || ''}"
                            maxlength="300"
                            required
                        >
                        <small class="form-text">Máximo 300 caracteres</small>
                    </div>
                    
                    <!-- Descripción -->
                    <div class="form-group">
                        <label for="edit-dia-descripcion-${diaId}">
                            Descripción <span class="required">*</span>
                        </label>
                        <textarea 
                            id="edit-dia-descripcion-${diaId}" 
                            class="form-control"
                            rows="4"
                            maxlength="2000"
                            required
                        >${dia.descripcion || ''}</textarea>
                        <small class="form-text">Máximo 2000 caracteres</small>
                    </div>
                    
                    <!-- Ubicación Principal -->
                    <div class="form-group">
                        <label for="edit-dia-ubicacion-${diaId}">
                            📍 Ubicación Principal <span class="required">*</span>
                        </label>
                        <div class="location-search-wrapper">
                            <input 
                                type="text" 
                                id="edit-dia-ubicacion-${diaId}" 
                                class="form-control location-search-input"
                                value="${dia.ubicacion || ''}"
                                placeholder="Buscar ubicación principal..."
                                autocomplete="off"
                            >
                            <div id="location-results-dia-${diaId}" class="location-results"></div>
                        </div>
                        <input type="hidden" id="edit-dia-latitud-${diaId}" value="${dia.latitud || ''}">
                        <input type="hidden" id="edit-dia-longitud-${diaId}" value="${dia.longitud || ''}">
                        
                        ${dia.ubicacion ? `
                            <div class="ubicacion-preview" style="margin-top: 8px; padding: 10px; background: #f0fdf4; border-radius: 6px; border-left: 3px solid var(--primary-color); font-size: 12px;">
                                <strong style="color: #065f46;">${dia.ubicacion}</strong>
                                ${dia.latitud && dia.longitud ? `
                                    <div style="color: var(--secondary-color); margin-top: 4px;">
                                        📍 ${parseFloat(dia.latitud).toFixed(6)}, ${parseFloat(dia.longitud).toFixed(6)}
                                    </div>
                                ` : ''}
                            </div>
                        ` : ''}
                    </div>

                    <!-- Ubicaciones Secundarias -->
                    <div class="form-group">
                        <label>
                            📍 Ubicaciones Adicionales del Día
                            <small style="color: #666;">(Opcional)</small>
                        </label>
                        <div id="ubicaciones-secundarias-edit-${diaId}" style="display: flex; flex-direction: column; gap: 12px;">
                            <!-- Se cargarán dinámicamente -->
                        </div>
                        <button 
                            type="button" 
                            class="btn btn-outline" 
                            onclick="agregarUbicacionSecundariaEdit(${diaId})"
                            style="margin-top: 10px; display: inline-flex; align-items: center; gap: 8px;">
                            <i class="fas fa-plus"></i> Agregar Otra Ubicación
                        </button>
                    </div>
                    
                    <!-- Imágenes -->
                    <div class="form-group">
                        <label>
                            Imágenes <span class="required">*</span>
                            <small>(mínimo 1 imagen)</small>
                        </label>
                        
                        <div class="images-preview-edit">
                            ${[1, 2, 3].map(i => {
                        const imagenUrl = dia['imagen' + i];
                        return `
                                    <div class="image-preview-item" data-image-number="${i}">
                                        ${imagenUrl ? `
                                            <img src="${imagenUrl}" alt="Imagen ${i}" class="preview-img">
                                            <button type="button" class="btn-remove-image" onclick="removerImagenDia(${diaId}, ${i})">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        ` : `
                                            <div class="empty-image-slot">
                                                <i class="fas fa-image"></i>
                                                <p>Imagen ${i}</p>
                                            </div>
                                        `}
                                        <input 
                                            type="file" 
                                            id="edit-dia-imagen${i}-${diaId}" 
                                            accept="image/jpeg,image/jpg,image/png,image/webp"
                                            onchange="previewImagenDia(${diaId}, ${i}, this)"
                                            style="display: none;"
                                        >
                                        <button type="button" class="btn-change-image" onclick="document.getElementById('edit-dia-imagen${i}-${diaId}').click()">
                                            ${imagenUrl ? 'Cambiar' : 'Agregar'}
                                        </button>
                                    </div>
                                `;
                    }).join('')}
                        </div>
                        <small class="form-text text-muted">Formatos: JPG, PNG, WEBP. Máximo 5MB por imagen.</small>
                    </div>
                    
                    <!-- Botones de acción -->
                    <div class="edit-form-actions">
                        <button type="button" class="btn btn-secondary" onclick="cerrarEdicionDia(${diaId})">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button type="button" class="btn btn-primary" onclick="guardarEdicionDia(${diaId})">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                    </div>
                </div>
            </div>
        `;

                    detailContainer.insertAdjacentHTML('beforeend', formHTML);
                }
            }

            //New funcion para vuelos code

            function renderizarBloqueVuelosDia(diaId) {
                return `
        <div class="flights-section" id="flights-section-${diaId}">
            <div class="flights-header">
                <div class="flights-title">
                    <i class="fas fa-plane-departure"></i>
                    <span>Vuelos del día</span>
                </div>
            </div>

            <div class="flight-search-row">
                <input 
                    type="text"
                    class="flight-code-input"
                    id="flight-code-${diaId}"
                    data-flight-input="true"
                    placeholder="Código de vuelo, ej: EK330"
                    maxlength="12"
                    onkeydown="handleFlightInputKey(event, ${diaId})"
                >

                <button 
                    type="button"
                    class="flight-search-btn"
                    id="flight-search-btn-${diaId}"
                    onclick="buscarPreviewVuelo(${diaId})">
                    <i class="fas fa-search"></i>
                    Buscar
                </button>
            </div>

            <div class="flight-preview" id="flight-preview-${diaId}"></div>

            <div class="flights-list" id="flights-list-${diaId}">
                <div class="flight-loading">
                    <i class="fas fa-spinner fa-spin"></i>
                    Cargando vuelos...
                </div>
            </div>
        </div>
    `;
            }

            async function cargarUbicacionesSecundariasDia(diaId) {
                try {
                    const response = await fetch(`<?= APP_URL ?>/modules/programa/dias_api.php?action=get_ubicaciones_secundarias&dia_id=${diaId}`);
                    const result = await response.json();

                    if (result.success && result.data && result.data.length > 0) {
                        const container = document.getElementById(`ubicaciones-display-${diaId}`);
                        if (container) {
                            // Agregar ubicaciones secundarias
                            result.data.forEach(ubicacion => {
                                const ubicacionElement = document.createElement('span');
                                ubicacionElement.innerHTML = `
                        <i class="fas fa-map-marker-alt"></i> 
                        ${ubicacion.ubicacion}
                    `;
                                ubicacionElement.style.cssText = 'display: flex; align-items: center; gap: 8px;';
                                container.appendChild(ubicacionElement);
                            });
                        }
                    }
                } catch (error) {
                    console.error(`Error cargando ubicaciones secundarias para día ${diaId}:`, error);
                }
            }

            function renderizarDetalleVacio() {
                const detailContainer = document.getElementById('day-detail-content');
                if (!detailContainer) return;

                detailContainer.innerHTML = `
        <div class="empty-detail">
            <div>
                <i class="fas fa-calendar-day"></i>
                <h3>Selecciona un día</h3>
                <p>Elige un día de la lista para ver y editar sus detalles</p>
            </div>
        </div>
    `;
            }

            // Función para cargar servicios solo para contador
            async function cargarServiciosParaContador(diaId) {
                try {
                    const response = await fetch(`<?= APP_URL ?>/modules/programa/servicios_api.php?action=list&dia_id=${diaId}`);
                    const result = await response.json();

                    if (result.success) {
                        const count = result.data ? result.data.length : 0;
                        const countElement = document.getElementById(`services-count-${diaId}`);
                        if (countElement) {
                            countElement.textContent = count;
                            countElement.style.display = count > 0 ? 'block' : 'none';
                        }
                    }
                } catch (error) {
                    console.error(`Error cargando contador de servicios para día ${diaId}:`, error);
                }
            }

            // Función modificada para actualizar contador después de agregar/eliminar servicios
            function actualizarContadorServicios(diaId, count) {
                const countElement = document.getElementById(`services-count-${diaId}`);
                if (countElement) {
                    countElement.textContent = count;
                    countElement.style.display = count > 0 ? 'block' : 'none';
                }
            }

            // Modificar función de renderizar servicios para actualizar contador
            function renderizarServiciosDia(diaId, servicios) {
                const container = document.getElementById(`services-${diaId}`);
                if (!container) {
                    console.error(`❌ No se encontró contenedor de servicios para día ${diaId}`);
                    return;
                }

                console.log(`🎨 Renderizando ${servicios.length} servicios para día ${diaId}`);

                // Actualizar contador en sidebar
                actualizarContadorServicios(diaId, servicios.length);

                if (servicios.length === 0) {
                    container.innerHTML = `
            <p style="color: #666; font-style: italic; text-align: center; padding: 10px;">
                <i class="fas fa-info-circle"></i> No hay servicios agregados a este día
            </p>
        `;
                    return;
                }

                // Ordenar servicios por orden
                const serviciosOrdenados = [...servicios].sort((a, b) => (a.orden || 0) - (b.orden || 0));

                container.innerHTML = `
        <h6 style="margin-bottom: 12px; color: #333; font-weight: 600;">
            <i class="fas fa-list"></i> Servicios agregados (${serviciosOrdenados.length}):
        </h6>
        ${serviciosOrdenados.map(servicio => `
            <div class="service-item" data-servicio-id="${servicio.id}">
                <div class="service-info">
                    <div class="service-icon ${servicio.tipo_servicio}">
                        <i class="fas fa-${getServiceIconByType(servicio.tipo_servicio, servicio)}"></i>
                    </div>
                    <div class="service-details">
                        <h6>${servicio.titulo || servicio.nombre || 'Servicio sin título'}</h6>
                        <p>${getServiceSummary(servicio)}</p>
                    </div>
                </div>
                <div class="service-actions">
                    <button class="btn-edit-service" onclick="editarServicio(${servicio.id})" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-remove-service" onclick="eliminarServicio(${servicio.id})" title="Eliminar">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `).join('')}
    `;

                console.log(`✅ Servicios renderizados para día ${diaId}`);
            }

            // ============================================================
            // FUNCIONES PARA GESTIÓN DE ESTANCIA - VERSIÓN MEJORADA
            // ============================================================
            async function cambiarEstancia(diaId, nuevaDuracion) {
                if (nuevaDuracion < 1 || nuevaDuracion > 30) return;

                // Encontrar los botones afectados
                const allBtns = document.querySelectorAll(`[onclick*="cambiarEstancia(${diaId},"]`);
                const displays = document.querySelectorAll(`#services-count-${diaId}`).length > 0 ?
                    document.querySelectorAll('.estancia-display') : [];

                try {
                    console.log(`🔄 Cambiando estancia del día ${diaId} a ${nuevaDuracion} días`);

                    // Mostrar estado de carga en botones
                    allBtns.forEach(btn => {
                        btn.disabled = true;
                        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                        btn.style.pointerEvents = 'none';
                    });

                    // Animación en displays
                    displays.forEach(display => {
                        display.style.transform = 'scale(1.1)';
                        display.style.background = 'linear-gradient(135deg, var(--secondary-color) 0%, var(--secondary-color) 100%)';
                    });

                    const formData = new FormData();
                    formData.append('action', 'cambiar_estancia');
                    formData.append('dia_id', diaId);
                    formData.append('duracion', nuevaDuracion);

                    const response = await fetch(`<?= APP_URL ?>/modules/programa/dias_api.php`, {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (!data.success) {
                        throw new Error(data.error || 'Error al cambiar estancia');
                    }

                    // Animación de éxito
                    showAlert('✅ Estancia actualizada correctamente', 'success');

                    // Efecto de celebración
                    displays.forEach(display => {
                        display.style.background = 'linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%)';
                        display.style.transform = 'scale(1.2)';
                        setTimeout(() => {
                            display.style.transform = 'scale(1)';
                        }, 300);
                    });

                    // Recargar días para actualizar números
                    await cargarDiasPrograma();

                    // Mantener día seleccionado si era el que se modificó
                    if (selectedDayId === diaId) {
                        setTimeout(() => {
                            seleccionarDiaEnSidebar(diaId);
                        }, 100);
                    }

                } catch (error) {
                    console.error('❌ Error:', error);
                    showAlert('Error: ' + error.message, 'error');

                    // Restaurar estado original en caso de error
                    displays.forEach(display => {
                        display.style.transform = 'scale(1)';
                        display.style.background = '';
                    });

                } finally {
                    // Restaurar botones después de un delay
                    setTimeout(() => {
                        allBtns.forEach(btn => {
                            btn.disabled = false;
                            btn.style.pointerEvents = '';
                        });
                    }, 500);
                }
            }

            // Función auxiliar para añadir efectos visuales
            function addStayEffects(diaId, duracion) {
                // Agregar indicador visual si es múltiples días
                if (duracion > 1) {
                    const dayItem = document.querySelector(`[data-dia-id="${diaId}"]`);
                    if (dayItem && !dayItem.querySelector('.multi-day-indicator')) {
                        const indicator = document.createElement('div');
                        indicator.className = 'multi-day-indicator';
                        indicator.title = `${duracion} días de estancia`;
                        dayItem.style.position = 'relative';
                        dayItem.appendChild(indicator);
                    }
                } else {
                    // Remover indicador si vuelve a 1 día
                    const indicator = document.querySelector(`[data-dia-id="${diaId}"] .multi-day-indicator`);
                    if (indicator) indicator.remove();
                }
            }

            // Función para mostrar tooltip personalizado
            function showCustomTooltip(element, message, duration = 2000) {
                const tooltip = document.createElement('div');
                tooltip.className = 'custom-tooltip';
                tooltip.textContent = message;
                tooltip.style.cssText = `
        position: absolute;
        background: rgba(0, 0, 0, 0.9);
        color: white;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 12px;
        z-index: 10000;
        pointer-events: none;
        transform: translateY(-100%);
        margin-bottom: 8px;
        animation: tooltip-show 0.3s ease;
    `;

                element.style.position = 'relative';
                element.appendChild(tooltip);

                setTimeout(() => {
                    if (tooltip.parentElement) {
                        tooltip.remove();
                    }
                }, duration);
            }


            console.log('✅ Script de sidebar de días cargado');



            // ============================================================
            // JAVASCRIPT COMPLETO PARA ALTERNATIVAS - AGREGAR A programa.php
            // ============================================================
            // Agregar estas funciones a tu script existente

            // Variables globales adicionales para alternativas
            let currentServicioPrincipal = null;

            // Función modificada para renderizar servicios CON alternativas
            function renderizarServiciosDia(diaId, servicios) {
                const container = document.getElementById(`services-${diaId}`);
                if (!container) {
                    console.error(`❌ No se encontró contenedor de servicios para día ${diaId}`);
                    return;
                }

                console.log(`🎨 Renderizando ${servicios.length} servicios CON ALTERNATIVAS para día ${diaId}`);

                // Actualizar contador en sidebar (solo contar principales)
                const principalesCount = servicios.length;
                actualizarContadorServicios(diaId, principalesCount);

                if (servicios.length === 0) {
                    container.innerHTML = `
            <p style="color: #666; font-style: italic; text-align: center; padding: 10px;">
                <i class="fas fa-info-circle"></i> No hay servicios agregados a este día
            </p>
        `;
                    return;
                }

                // Renderizar servicios principales con sus alternativas
                container.innerHTML = `
        <h6 style="margin-bottom: 12px; color: #333; font-weight: 600;">
            <i class="fas fa-list"></i> Servicios agregados (${principalesCount}):
        </h6>
        ${servicios.map(servicio => renderizarServicioConAlternativas(servicio)).join('')}
    `;

                console.log(`✅ Servicios con alternativas renderizados para día ${diaId}`);
            }

            function setupCharacterCounters() {
                console.log('🔧 Configurando contadores de caracteres...');

                // Campos con límites de caracteres
                const fieldsWithLimits = [
                    { id: 'program-title', max: 250 },
                    { id: 'traveler-name', max: 250 },
                    { id: 'traveler-lastname', max: 250 }
                ];

                // Campos de textarea con límites grandes
                const textareasWithLimits = [
                    { name: 'precio_incluye', max: 3000 },
                    { name: 'precio_no_incluye', max: 3000 },
                    { name: 'condiciones_generales', max: 3000 },
                    { name: 'info_pasaporte', max: 3000 },
                    { name: 'info_seguros', max: 3000 },
                    { name: 'visados_entrada', max: 3000 },
                    { name: 'requisitos_sanitarios', max: 3000 },
                    { name: 'llegada_punto_encuentro', max: 3000 },
                    { name: 'asistencia_emergencia', max: 3000 },
                    { name: 'info_hoteles_servicios', max: 3000 },
                    { name: 'informacion_practica', max: 3000 }
                ];

                // Configurar inputs normales (tu código original)
                fieldsWithLimits.forEach(field => {
                    const input = document.getElementById(field.id);
                    const counter = document.getElementById(field.id + '-counter');

                    if (input && counter) {
                        // Función para actualizar contador
                        function updateCounter() {
                            const currentLength = input.value.length;
                            const maxLength = field.max;

                            // Actualizar texto del contador
                            counter.textContent = `${currentLength}/${maxLength}`;

                            // Remover clases anteriores
                            counter.classList.remove('warning', 'danger');

                            // Agregar clase según el porcentaje usado
                            const percentage = (currentLength / maxLength) * 100;

                            if (percentage >= 100) {
                                counter.classList.add('danger');
                            } else if (percentage >= 80) {
                                counter.classList.add('warning');
                            }
                        }

                        // Configurar eventos
                        input.addEventListener('input', updateCounter);
                        input.addEventListener('paste', () => {
                            setTimeout(updateCounter, 10);
                        });

                        // Inicializar contador con valor actual
                        updateCounter();

                        console.log(`✅ Contador configurado para ${field.id}: ${field.max} caracteres`);
                    }
                });

                // Configurar textareas (nueva funcionalidad)
                textareasWithLimits.forEach(field => {
                    const textarea = document.querySelector(`textarea[name="${field.name}"]`);
                    const counter = document.getElementById(field.name + '-counter');

                    if (textarea && counter) {
                        // Función para actualizar contador
                        function updateCounter() {
                            const currentLength = textarea.value.length;
                            const maxLength = field.max;

                            // Actualizar texto del contador
                            counter.textContent = `${currentLength}/${maxLength}`;

                            // Remover clases anteriores
                            counter.classList.remove('warning', 'danger');

                            // Agregar clase según el porcentaje usado
                            const percentage = (currentLength / maxLength) * 100;

                            if (percentage >= 100) {
                                counter.classList.add('danger');
                            } else if (percentage >= 80) {
                                counter.classList.add('warning');
                            }
                        }

                        // Configurar eventos
                        textarea.addEventListener('input', updateCounter);
                        textarea.addEventListener('paste', () => {
                            setTimeout(updateCounter, 10);
                        });

                        // Inicializar contador con valor actual
                        updateCounter();

                        console.log(`✅ Contador configurado para ${field.name}: ${field.max} caracteres`);
                    }
                });
            }

            function setupInputCounter(input, counter, maxLength) {
                // Función para actualizar contador
                function updateCounter() {
                    const currentLength = input.value.length;

                    // Actualizar texto del contador
                    counter.textContent = `${currentLength}/${maxLength}`;

                    // Remover clases anteriores
                    counter.classList.remove('warning', 'danger');

                    // Agregar clase según el porcentaje usado
                    const percentage = (currentLength / maxLength) * 100;

                    if (percentage >= 100) {
                        counter.classList.add('danger');
                    } else if (percentage >= 80) {
                        counter.classList.add('warning');
                    }
                }

                // Configurar eventos
                input.addEventListener('input', updateCounter);
                input.addEventListener('paste', () => {
                    setTimeout(updateCounter, 10);
                });

                // Inicializar contador con valor actual
                updateCounter();
            }

            function renderizarServicioConAlternativas(servicio) {
                const hasAlternatives = servicio.alternativas && servicio.alternativas.length > 0;
                const alternativas = servicio.alternativas || [];

                return `
        <div class="service-group" data-servicio-principal-id="${servicio.id}">
            <!-- Servicio Principal -->
            <div class="service-item principal" data-servicio-id="${servicio.id}">
                <div class="service-info">
                    <div class="service-icon ${servicio.tipo_servicio}">
                        <i class="fas fa-${getServiceIconByType(servicio.tipo_servicio, servicio)}"></i>
                    </div>
                    <div class="service-details">
                        <h6>
                            ${servicio.nombre || servicio.titulo || 'Servicio sin título'}
                            ${hasAlternatives ? `<span class="alternatives-indicator">${alternativas.length} alt</span>` : ''}
                        </h6>
                        <p>${getServiceSummary(servicio)}</p>
                    </div>
                </div>
                <div class="service-actions">
                    <!-- Botón de alternativa para TODOS los tipos de servicio -->
                    <button class="btn-add-alternative" onclick="abrirModalAlternativa(${servicio.id}, '${servicio.tipo_servicio}')" title="Agregar alternativa">
                        <i class="fas fa-plus-circle"></i>
                    </button>

                    ${servicio.tipo_servicio === 'alojamiento' ? `
                    <button type="button"
                            class="service-action-btn btn-acomodacion-inline"
                            title="Gestionar acomodación"
                            onclick="abrirEditorAlojamiento(${servicio.id})">
                        <i class="fas fa-bed"></i>
                    </button>
                ` : ''}
                    
                    <!-- Botón de edición solo para actividades -->
                    ${servicio.tipo_servicio === 'actividad' ? `
                        <button class="btn-edit-service" onclick="abrirEdicionActividad(${servicio.id})" title="Editar actividad">
                            <i class="fas fa-edit"></i>
                        </button>
                    ` : ''}
                    
                    <!-- Botón eliminar para todos -->
                    <button class="btn-remove-service" onclick="eliminarServicio(${servicio.id})" title="Eliminar">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>

            
            <!-- Alternativas -->
            ${hasAlternatives ? `
                <div class="alternatives-container">
                    ${alternativas.map(alt => renderizarAlternativa(alt)).join('')}
                </div>
            ` : ''}
        </div>
    `;
            }

            function renderizarAlternativa(alternativa) {
                return `
        <div class="service-item alternativa" data-alternativa-id="${alternativa.id}">
            <div class="alternative-connector"></div>
            <div class="service-info">
                <div class="service-icon ${alternativa.tipo_servicio} alternativa">
                    <i class="fas fa-${getServiceIconByType(alternativa.tipo_servicio, alternativa)}"></i>
                </div>
                <div class="service-details">
                    <h6>
                        <i class="fas fa-sync-alt" style="color: var(--secondary-color); font-size: 12px; margin-right: 4px;" title="Alternativa"></i>
                        Alternativa ${alternativa.orden_alternativa}: ${alternativa.nombre || alternativa.titulo || 'Sin título'}
                    </h6>
                    <p>${getServiceSummary(alternativa)}</p>
                    ${alternativa.notas_alternativa ?
                        `
                        <div style="font-size: 11px; color: #6c757d; margin-top: 4px;">
                            <i class="fas fa-sticky-note"></i> ${alternativa.notas_alternativa}
                        </div>
                    ` : ''}
                </div>
            </div>
            <div class="service-actions">
                <!-- Solo mostrar botón editar para ACTIVIDADES -->
                ${alternativa.tipo_servicio === 'actividad' ? `
                    <button class="btn-edit-service" onclick="abrirEdicionActividad(${alternativa.id})" title="Editar alternativa">
                        <i class="fas fa-edit"></i>
                    </button>
                ` : ''}
                
                <!-- Botón eliminar para todas las alternativas -->
                <button class="btn-remove-service" onclick="eliminarAlternativa(${alternativa.id})" title="Eliminar alternativa">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;
            }

            // Pide la diferencia de precio de una alternativa de HOTEL respecto al principal.
            // Devuelve el número (+/-), 0 si no aplica, o null si el usuario cancela.
            function pedirVariacionAlternativa(tipoServicio) {
                if (tipoServicio !== 'alojamiento') return 0;
                const varRaw = prompt('¿Cuánto varía el precio de esta alternativa respecto al hotel principal?\n\n• Positivo si cuesta MÁS (ej. 50)\n• Negativo si cuesta MENOS (ej. -30)\n• 0 si cuesta igual', '0');
                if (varRaw === null) return null; // cancelado
                return parseFloat(String(varRaw).replace(',', '.')) || 0;
            }

            // Función para abrir modal de alternativas
            function abrirModalAlternativa(servicioPrincipalId, tipoServicio) {
                console.log(`🔄 Agregando alternativa para servicio ${servicioPrincipalId}`);

                // Configurar para alternativa
                isAddingAlternative = true;
                alternativeParentId = servicioPrincipalId;
                currentTipoServicio = tipoServicio;

                abrirModalServicios(tipoServicio, 'Agregar alternativa de ' + tipoServicio);
            }

            // Función para agregar alternativa seleccionada
            async function agregarAlternativaSeleccionada() {
                if (!selectedServicioId || !currentServicioPrincipal) {
                    console.error('❌ Datos faltantes para agregar alternativa');
                    return;
                }

                try {
                    console.log(`🔄 Agregando alternativa: Principal=${currentServicioPrincipal}, Item=${selectedServicioId}`);

                    const variacion = pedirVariacionAlternativa(currentTipoServicio);
                    if (variacion === null) return; // cancelado

                    const response = await fetch('<?= APP_URL ?>/modules/programa/servicios_api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'add_alternative',
                            servicio_principal_id: currentServicioPrincipal,
                            biblioteca_item_id: selectedServicioId,
                            variacion_precio: variacion
                        })
                    });

                    const result = await response.json();

                    if (result.success) {
                        showAlert('Alternativa agregada exitosamente', 'success');
                        cerrarModalServicios();
                        // Recargar servicios del día seleccionado
                        if (selectedDayId) {
                            cargarServiciosDia(selectedDayId);
                            cargarServiciosParaContador(selectedDayId);
                        }
                    } else {
                        showAlert(result.message || 'Error al agregar alternativa', 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showAlert('Error de conexión', 'error');
                }
            }

            // Función para eliminar alternativa
            async function eliminarAlternativa(alternativaId) {
                const confirmed = await showConfirmModal({
                    title: '¿Eliminar alternativa?',
                    message: '¿Estás seguro de que quieres eliminar esta alternativa?',
                    details: 'Esta acción no se puede deshacer.',
                    icon: '<i class="fas fa-trash"></i>',
                    confirmText: 'Aceptar',
                    cancelText: 'Cancelar'
                });

                if (!confirmed) return;

                const btnEliminar = event.target.closest('.btn-remove-service');
                const originalContent = btnEliminar ? btnEliminar.innerHTML : '';

                try {
                    console.log('🗑️ Eliminando alternativa ID:', alternativaId);

                    // Mostrar estado de carga
                    if (btnEliminar) {
                        btnEliminar.disabled = true;
                        btnEliminar.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                    }

                    const response = await fetch('<?= APP_URL ?>/modules/programa/servicios_api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'delete',
                            servicio_id: alternativaId
                        })
                    });

                    if (!response.ok) {
                        throw new Error(`Error del servidor: ${response.status}`);
                    }

                    const responseText = await response.text();
                    console.log('📄 Respuesta:', responseText);

                    let result;
                    try {
                        result = JSON.parse(responseText);
                    } catch (parseError) {
                        console.warn('⚠️ No se pudo parsear la respuesta como JSON:', parseError);
                        if (response.ok) {
                            result = { success: true, message: 'Alternativa eliminada exitosamente' };
                        } else {
                            throw new Error('Respuesta del servidor no válida');
                        }
                    }

                    if (result && result.success) {
                        showAlert('✅ Alternativa eliminada exitosamente', 'success');

                        // Recargar servicios del día seleccionado
                        if (selectedDayId) {
                            await cargarServiciosDia(selectedDayId);
                            await cargarServiciosParaContador(selectedDayId);
                        }
                    } else {
                        const errorMessage = result?.message || result?.error || 'Error desconocido al eliminar alternativa';
                        throw new Error(errorMessage);
                    }

                } catch (error) {
                    console.error('❌ Error eliminando alternativa:', error);
                    showAlert('Error eliminando alternativa: ' + error.message, 'error');

                } finally {
                    // Restaurar botón siempre
                    if (btnEliminar) {
                        btnEliminar.disabled = false;
                        btnEliminar.innerHTML = originalContent || '<i class="fas fa-trash"></i>';
                    }
                }
            }
            function debugEliminarServicio(servicioId) {
                console.log('🔍 DEBUG - Estado antes de eliminar:');
                console.log('- Servicio ID:', servicioId);
                console.log('- Día seleccionado:', selectedDayId);
                console.log('- Días programa:', diasPrograma.map(d => d.id));
                console.log('- URL de API:', '<?= APP_URL ?>/modules/programa/servicios_api.php');
            }

            // Función para editar alternativa
            function editarAlternativa(alternativaId) {
                // TODO: Implementar edición de alternativas
                showAlert('Función de edición de alternativas en desarrollo', 'info');
            }



            async function agregarServicioPrincipalSeleccionado() {
                if (!selectedServicioId || !currentDiaId || !currentTipoServicio) return;

                try {
                    const response = await fetch('<?= APP_URL ?>/modules/programa/servicios_api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'add_service',
                            dia_id: currentDiaId,
                            tipo_servicio: currentTipoServicio,
                            biblioteca_item_id: selectedServicioId
                        })
                    });

                    const result = await response.json();

                    if (result.success) {
                        showAlert('Servicio agregado exitosamente', 'success');
                        cerrarModalServicios();
                        cargarServiciosDia(currentDiaId); // Recargar servicios del día
                    } else {
                        showAlert(result.message || 'Error al agregar servicio', 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showAlert('Error de conexión', 'error');
                }
            }





            // AGREGAR ESTAS FUNCIONES AL FINAL DEL JAVASCRIPT
            let sidebarOpen = false;

            function toggleSidebar() {
                const sidebar = document.querySelector('.enhanced-sidebar');
                const overlay = document.getElementById('overlay');
                const mainContainer = document.querySelector('.main-container');

                if (!sidebar) return;

                sidebarOpen = !sidebarOpen;

                if (sidebarOpen) {
                    sidebar.classList.add('open');
                    if (overlay) overlay.classList.add('show');
                    if (mainContainer && window.innerWidth > 768) {
                        mainContainer.classList.add('sidebar-open');
                    }
                } else {
                    sidebar.classList.remove('open');
                    if (overlay) overlay.classList.remove('show');
                    if (mainContainer) mainContainer.classList.remove('sidebar-open');
                }
            }

            function closeSidebar() {
                if (sidebarOpen) {
                    toggleSidebar();
                }
            }

            function toggleUserMenu() {
                if (confirm('¿Desea cerrar sesión?')) {
                    window.location.href = '<?= APP_URL ?>/auth/logout';
                }
            }

            // Google Translate
            function initializeGoogleTranslate() {
                function googleTranslateElementInit() {
                    new google.translate.TranslateElement({
                        pageLanguage: '<?= $defaultLanguage ?>',
                        includedLanguages: 'en,fr,pt,it,de,es',
                        layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
                        autoDisplay: false
                    }, 'google_translate_element');
                }

                if (!window.googleTranslateElementInit) {
                    window.googleTranslateElementInit = googleTranslateElementInit;
                    const script = document.createElement('script');
                    script.src = '//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit';
                    script.async = true;
                    // Si Google no responde o está bloqueado, no romper ni colgar la página.
                    script.onerror = function () { console.warn('Google Translate no disponible (se ignora).'); };
                    document.head.appendChild(script);
                }
            }

            // Cargar el widget SOLO cuando la página ya terminó de cargar, para que el script
            // externo de Google (lento/poco fiable) no compita con la carga ni deje el
            // navegador en "carga infinita". Antes se inyectaba en DOMContentLoaded.
            if (document.readyState === 'complete') {
                setTimeout(initializeGoogleTranslate, 0);
            } else {
                window.addEventListener('load', function () { setTimeout(initializeGoogleTranslate, 0); });
            }

            document.addEventListener('DOMContentLoaded', function () {
                console.log('🚀 Iniciando programa.php...');
                setupTabNavigation();
                setupFormHandling();
                setupCharacterCounters();
                setupFileValidation();
                setupMealHandlers(); // ← ESTA LÍNEA DEBE ESTAR

                if (isEditing && programaId) {
                    console.log(`📋 Cargando datos para programa ID: ${programaId}`);
                    cargarDiasPrograma();
                    cargarPreciosPrograma();
                } else {
                    console.log('💡 Programa nuevo - no hay días que cargar');
                }
            });


            // Función para expandir/contraer alternativas (opcional)
            function toggleAlternativas(servicioId) {
                const serviceGroup = document.querySelector(`[data-servicio-id="${servicioId}"]`);
                if (serviceGroup) {
                    serviceGroup.classList.toggle('expanded');
                }
            }

            // Función para contar total de servicios incluyendo alternativas
            function contarTotalServicios(servicios) {
                let total = servicios.length; // Principales
                servicios.forEach(servicio => {
                    if (servicio.alternativas) {
                        total += servicio.alternativas.length;
                    }
                });
                return total;
            }

            // Función para mostrar enlaces públicos
            async function mostrarEnlacesPublicos(programaId) {
                if (!programaId) return;

                try {
                    // Obtener tokens del servidor
                    const response = await fetch(`<?= APP_URL ?>/modules/programa/api.php?action=get_tokens&id=${programaId}`);
                    const result = await response.json();

                    if (result.success && result.tokens) {
                        const previewUrl = `<?= APP_URL ?>/public_preview.php?token=${result.tokens.preview_token}`;
                        const itineraryUrl = `<?= APP_URL ?>/public_itinerary.php?token=${result.tokens.itinerary_token}`;

                        // Mostrar modal con enlaces
                        showPublicLinksModal(previewUrl, itineraryUrl);
                    }
                } catch (error) {
                    console.error('Error obteniendo enlaces:', error);
                }
            }

            function showPublicLinksModal(previewUrl, itineraryUrl) {
                const modal = document.createElement('div');
                modal.className = 'modal';
                modal.style.display = 'block';
                modal.innerHTML = `
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3>Enlaces Públicos Creados</h3>
                <button onclick="this.closest('.modal').remove()">&times;</button>
            </div>
            <div class="modal-body">
                <div style="margin-bottom: 20px;">
                    <label><strong>Vista Previa (para revisar):</strong></label>
                    <input type="text" value="${previewUrl}" readonly onclick="this.select()" style="width: 100%; padding: 8px; margin: 5px 0;">
                    <button onclick="copyToClipboard('${previewUrl}')" class="btn btn-outline">Copiar</button>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label><strong>Itinerario Completo (para cliente):</strong></label>
                    <input type="text" value="${itineraryUrl}" readonly onclick="this.select()" style="width: 100%; padding: 8px; margin: 5px 0;">
                    <button onclick="copyToClipboard('${itineraryUrl}')" class="btn btn-outline">Copiar</button>
                </div>
                
                <p style="color: #666; font-size: 14px;">
                    <i class="fas fa-info-circle"></i> 
                    Estos enlaces son únicos y seguros para compartir con el cliente.
                </p>
            </div>
            <div class="modal-footer">
                <button onclick="this.closest('.modal').remove()" class="btn btn-primary">Entendido</button>
            </div>
        </div>
    `;

                document.body.appendChild(modal);
            }

            function copyToClipboard(text) {
                navigator.clipboard.writeText(text).then(() => {
                    showAlert('✅ Enlace copiado al portapapeles', 'success');
                });
            }

            // Función para obtener estadísticas de servicios
            function getEstadisticasServicios(servicios) {
                const stats = {
                    principales: servicios.length,
                    alternativas: 0,
                    total: servicios.length
                };

                servicios.forEach(servicio => {
                    if (servicio.alternativas) {
                        stats.alternativas += servicio.alternativas.length;
                        stats.total += servicio.alternativas.length;
                    }
                });

                return stats;
            }

            // Función de utilidad para verificar si un servicio tiene alternativas
            function tieneAlternativas(servicio) {
                return servicio.alternativas && servicio.alternativas.length > 0;
            }

            // Función para buscar un servicio específico (principal o alternativa)
            function buscarServicioPorId(servicios, id) {
                for (const servicio of servicios) {
                    if (servicio.id == id) {
                        return { tipo: 'principal', servicio: servicio };
                    }

                    if (servicio.alternativas) {
                        for (const alt of servicio.alternativas) {
                            if (alt.id == id) {
                                return { tipo: 'alternativa', servicio: alt, principal: servicio };
                            }
                        }
                    }
                }
                return null;
            }

            // Función para reordenar alternativas dentro de un servicio principal
            async function reordenarAlternativas(servicioPrincipalId, nuevoOrden) {
                try {
                    const response = await fetch('<?= APP_URL ?>/modules/programa/servicios_api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'reorder_alternatives',
                            servicio_principal_id: servicioPrincipalId,
                            orden: nuevoOrden
                        })
                    });

                    const result = await response.json();

                    if (result.success) {
                        showAlert('Orden de alternativas actualizado', 'success');
                        // Recargar servicios
                        if (selectedDayId) {
                            cargarServiciosDia(selectedDayId);
                        }
                    } else {
                        showAlert(result.message || 'Error al reordenar alternativas', 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showAlert('Error de conexión', 'error');
                }
            }
            // Mapeo de monedas a símbolos
            const currencySymbols = {
                'USD': '$', 'EUR': '€', 'JPY': '¥', 'GBP': '£', 'AUD': 'A$', 'CAD': 'C$',
                'CHF': 'Fr', 'CNY': '¥', 'SEK': 'kr', 'NZD': 'NZ$', 'COP': '$', 'MXN': '$',
                'ARS': '$', 'BRL': 'R$', 'CLP': '$', 'PEN': 'S/', 'UYU': '$', 'VES': 'Bs',
                'NOK': 'kr', 'DKK': 'kr', 'PLN': 'zł', 'CZK': 'Kč', 'HUF': 'Ft', 'RUB': '₽',
                'TRY': '₺', 'ZAR': 'R', 'INR': '₹', 'KRW': '₩', 'SGD': 'S$', 'HKD': 'HK$',
                'THB': '฿', 'MYR': 'RM', 'IDR': 'Rp', 'PHP': '₱', 'VND': '₫', 'TWD': 'NT$',
                'ILS': '₪', 'AED': 'د.إ', 'SAR': '﷼', 'QAR': '﷼', 'KWD': 'د.ك', 'BHD': '.د.ب',
                'OMR': '﷼', 'JOD': 'د.ا', 'LBP': '£', 'EGP': '£', 'MAD': 'د.م.', 'TND': 'د.ت',
                'DZD': 'د.ج', 'NGN': '₦', 'KES': 'KSh', 'GHS': '₵', 'ETB': 'Br', 'UGX': 'USh',
                'TZS': 'TSh', 'ZMW': 'ZK', 'BWP': 'P', 'MUR': '₨', 'SCR': '₨', 'XOF': 'CFA',
                'XAF': 'CFA', 'CDF': 'FC', 'AOA': 'Kz', 'MZN': 'MT', 'SZL': 'L', 'LSL': 'L',
                'NAD': 'N$', 'MWK': 'MK', 'RWF': 'FRw', 'BIF': 'FBu', 'DJF': 'Fdj', 'SOS': 'Sh',
                'ERN': 'Nfk', 'STN': 'Db', 'CVE': '$', 'GMD': 'D', 'GNF': 'FG', 'LRD': 'L$',
                'SLE': 'Le', 'ALL': 'L', 'BAM': 'KM', 'BGN': 'лв', 'HRK': 'kn', 'RSD': 'дин',
                'MKD': 'ден', 'RON': 'lei', 'MDL': 'L', 'UAH': '₴', 'BYN': 'Br', 'GEL': 'ლ',
                'AMD': '֏', 'AZN': '₼', 'KZT': '₸', 'UZS': 'сўм', 'TJS': 'ЅМ', 'KGS': 'лв',
                'TMT': 'm', 'AFN': '؋', 'PKR': '₨', 'LKR': '₨', 'NPR': '₨', 'BTN': 'Nu',
                'BDT': '৳', 'MMK': 'K', 'LAK': '₭', 'KHR': '៛', 'BND': 'B$', 'MNT': '₮',
                'KPW': '₩', 'FJD': 'FJ$', 'PGK': 'K', 'SBD': 'SI$', 'VUV': 'VT', 'NCX': '₣',
                'WST': 'WS$', 'TOP': 'T$', 'NIO': 'C$', 'CRC': '₡', 'PAB': 'B/.', 'GTQ': 'Q',
                'HNL': 'L', 'SVC': '₡', 'BZD': 'BZ$', 'JMD': 'J$', 'HTG': 'G', 'DOP': 'RD$',
                'CUP': '₱', 'BBD': 'Bds$', 'TTD': 'TT$', 'GYD': 'GY$', 'SRD': 'Sr$', 'AWG': 'ƒ',
                'ANG': 'ƒ', 'XCD': 'EC$', 'BOB': 'Bs', 'PYG': '₲', 'GGP': '£', 'JEP': '£',
                'IMP': '£', 'FKP': '£', 'GIP': '£', 'SHP': '£', 'ISK': 'kr', 'FOK': 'kr'
            };

            function compartirEnlace() {
                if (!programaId) {
                    alert('Guarda el programa primero');
                    return;
                }

                // Generar token simple
                const timestamp = Date.now();
                const tokenData = `${programaId}_${timestamp}`;
                const token = btoa(tokenData); // base64 encode

                // URLs públicas
                const previewUrl = `<?= APP_URL ?>/share?t=${token}&type=preview`;
                const itineraryUrl = `<?= APP_URL ?>/share?t=${token}&type=itinerary`;

                // Modal simple
                const modal = `
        <div style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.8);z-index:9999;display:flex;align-items:center;justify-content:center;" onclick="this.remove()">
            <div style="background:white;padding:30px;border-radius:15px;max-width:500px;width:90%;max-height:80vh;overflow-y:auto;" onclick="event.stopPropagation()">
                <h3 style="margin-bottom:20px;color:#333;text-align:center;">Enlaces para Compartir</h3>
                
                <div style="margin-bottom:20px;padding:15px;background:#f8f9fa;border-radius:8px;">
                    <strong style="color:var(--primary-color);">📖 Vista Previa:</strong><br>
                    <input type="text" value="${previewUrl}" readonly style="width:100%;padding:8px;margin:5px 0;border:1px solid #ddd;border-radius:5px;font-size:12px;">
                    <button onclick="copiarUrl('${previewUrl}')" style="background:var(--primary-color);color:white;border:none;padding:8px 15px;border-radius:5px;cursor:pointer;width:100%;">
                        📋 Copiar Enlace Vista Previa
                    </button>
                </div>
                
                <div style="margin-bottom:20px;padding:15px;background:#f8f9fa;border-radius:8px;">
                    <strong style="color:#667eea;">📅 Itinerario Completo:</strong><br>
                    <input type="text" value="${itineraryUrl}" readonly style="width:100%;padding:8px;margin:5px 0;border:1px solid #ddd;border-radius:5px;font-size:12px;">
                    <button onclick="copiarUrl('${itineraryUrl}')" style="background:#667eea;color:white;border:none;padding:8px 15px;border-radius:5px;cursor:pointer;width:100%;">
                        📋 Copiar Enlace Itinerario
                    </button>
                </div>
                
                <div style="background:#e0f2fe;padding:15px;border-radius:8px;border-left:4px solid #0ea5e9;margin-bottom:15px;">
                    <p style="margin:0;font-size:14px;color:#0369a1;"><strong>ℹ️ Importante:</strong></p>
                    <p style="margin:5px 0 0 0;font-size:13px;color:#0369a1;">• Los enlaces son únicos y seguros<br>• No requieren login para acceder<br>• Perfectos para compartir con clientes</p>
                </div>
                
                <button onclick="this.parentElement.parentElement.remove()" style="background:#6b7280;color:white;border:none;padding:10px 20px;border-radius:5px;cursor:pointer;width:100%;">
                    ✕ Cerrar
                </button>
            </div>
        </div>
    `;

                document.body.insertAdjacentHTML('beforeend', modal);
            }

            function copiarUrl(url) {
                navigator.clipboard.writeText(url).then(() => {
                    // Mostrar confirmación temporal
                    const confirmacion = document.createElement('div');
                    confirmacion.innerHTML = '✅ Enlace copiado!';
                    confirmacion.style.cssText = 'position:fixed;top:20px;right:20px;background:var(--primary-color);color:white;padding:10px 20px;border-radius:8px;z-index:10000;font-weight:bold;';
                    document.body.appendChild(confirmacion);

                    setTimeout(() => confirmacion.remove(), 2000);
                }).catch(() => {
                    alert('Enlace: ' + url);
                });
            }

            // Función para actualizar los íconos de moneda
            function updateCurrencyIcons() {
                const monedaSelect = document.querySelector('[name="moneda"]');
                const iconPersona = document.getElementById('currency-icon-persona');
                const iconTotal = document.getElementById('currency-icon-total');

                if (monedaSelect && iconPersona && iconTotal) {
                    const selectedCurrency = monedaSelect.value;
                    const symbol = currencySymbols[selectedCurrency] || selectedCurrency;

                    iconPersona.textContent = symbol;
                    iconTotal.textContent = symbol;
                }
            }

            // Función para calcular precio total automáticamente
            function calcularPrecioTotal() {
                const cantidadAdultos = parseInt(document.getElementById('cantidad-adultos')?.value) || 0;
                const precioAdulto = parseFloat(document.getElementById('precio-adulto')?.value) || 0;
                const cantidadNinos = parseInt(document.getElementById('cantidad-ninos')?.value) || 0;
                const precioNino = parseFloat(document.getElementById('precio-nino')?.value) || 0;

                // Calcular total
                const totalAdultos = cantidadAdultos * precioAdulto;
                const totalNinos = cantidadNinos * precioNino;
                const precioTotal = totalAdultos + totalNinos;

                // Actualizar campo de precio total
                const precioTotalInput = document.getElementById('precio-total');
                if (precioTotalInput) {
                    precioTotalInput.value = precioTotal.toFixed(2);

                    // Mostrar desglose del cálculo
                    const calculoInfo = document.getElementById('calculo-info');
                    const calculoDetalle = document.getElementById('calculo-detalle');

                    if (precioTotal > 0 && calculoInfo && calculoDetalle) {
                        let detalle = [];

                        if (cantidadAdultos > 0) {
                            const moneda = document.getElementById('currency-icon-total')?.textContent || '$';
                            detalle.push(`${cantidadAdultos} adulto${cantidadAdultos > 1 ? 's' : ''} × ${moneda}${precioAdulto.toFixed(2)} = ${moneda}${totalAdultos.toFixed(2)}`);
                        }

                        if (cantidadNinos > 0) {
                            const moneda = document.getElementById('currency-icon-total')?.textContent || '$';
                            detalle.push(`${cantidadNinos} niño${cantidadNinos > 1 ? 's' : ''} × ${moneda}${precioNino.toFixed(2)} = ${moneda}${totalNinos.toFixed(2)}`);
                        }

                        calculoDetalle.textContent = detalle.join(' + ');
                        calculoInfo.style.display = 'block';
                    } else if (calculoInfo) {
                        calculoInfo.style.display = 'none';
                    }
                }
            }

            // Función mejorada para actualizar íconos de moneda
            function updateCurrencyIcons() {
                const monedaSelect = document.querySelector('[name="moneda"]');
                const iconAdulto = document.getElementById('currency-icon-adulto');
                const iconNino = document.getElementById('currency-icon-nino');
                const iconTotal = document.getElementById('currency-icon-total');

                if (monedaSelect) {
                    const selectedCurrency = monedaSelect.value;
                    const symbol = currencySymbols[selectedCurrency] || selectedCurrency;

                    if (iconAdulto) iconAdulto.textContent = symbol;
                    if (iconNino) iconNino.textContent = symbol;
                    if (iconTotal) iconTotal.textContent = symbol;

                    // Recalcular para mostrar nueva moneda
                    calcularPrecioTotal();
                }
            }

            // Event listener para el select de moneda
            document.addEventListener('DOMContentLoaded', function () {
                const monedaSelect = document.querySelector('[name="moneda"]');
                if (monedaSelect) {
                    monedaSelect.addEventListener('change', updateCurrencyIcons);
                    updateCurrencyIcons(); // Inicializar
                }
            });

            // Agregar el event listener al select de moneda
            document.addEventListener('DOMContentLoaded', function () {
                const monedaSelect = document.querySelector('[name="moneda"]');
                if (monedaSelect) {
                    monedaSelect.addEventListener('change', updateCurrencyIcons);
                    // Actualizar al cargar la página
                    updateCurrencyIcons();
                }
            });

            /**
             * Muestra el campo ID de solicitud con animación suave
             */
            function mostrarCampoRequestId(requestId) {
                console.log('📋 Mostrando campo ID de solicitud:', requestId);

                const requestIdGroup = document.getElementById('request-id-group');
                const requestIdField = document.getElementById('request-id');

                if (!requestIdGroup || !requestIdField) {
                    console.error('❌ No se encontraron los elementos del campo ID de solicitud');
                    return;
                }

                // Asignar el valor primero
                requestIdField.value = requestId;

                // Si ya está visible, no hacer nada más
                if (requestIdGroup.style.display !== 'none') {
                    return;
                }

                // Preparar animación
                requestIdGroup.style.display = 'block';
                requestIdGroup.style.opacity = '0';
                requestIdGroup.style.transform = 'translateY(-10px)';
                requestIdGroup.style.transition = 'all 0.4s ease';

                // Mostrar con animación
                setTimeout(() => {
                    requestIdGroup.style.opacity = '1';
                    requestIdGroup.style.transform = 'translateY(0)';

                    // Agregar efecto de resaltado temporal
                    requestIdField.style.borderColor = '#28a745';
                    requestIdField.style.boxShadow = '0 0 0 0.2rem rgba(40, 167, 69, 0.25)';

                    // Quitar resaltado después de 2 segundos
                    setTimeout(() => {
                        requestIdField.style.borderColor = '';
                        requestIdField.style.boxShadow = '';
                    }, 2000);
                }, 100);

                console.log('✅ Campo ID de solicitud mostrado exitosamente');
            }

            // Eventos para drag & drop de alternativas (opcional - futuro)
            function initDragAndDropAlternativas() {
                // TODO: Implementar drag & drop para reordenar alternativas
                console.log('💡 Drag & drop de alternativas - funcionalidad futura');
            }

            console.log('✅ Script completo de alternativas cargado');
            console.log('🔧 Funciones disponibles:');
            console.log('   - abrirModalAlternativa()');
            console.log('   - agregarAlternativaSeleccionada()');
            console.log('   - eliminarAlternativa()');
            console.log('   - renderizarServicioConAlternativas()');
            console.log('   - toggleAlternativas()');
            console.log('   - getEstadisticasServicios()');
            console.log('   - buscarServicioPorId()');
            console.log('   - reordenarAlternativas()');



        </script>
        <script>
            function abrirMiBiblioteca() {
                // Agregar efecto visual de clic
                const button = event.target.closest('.nav-button');
                button.style.transform = 'scale(0.95)';

                // Restaurar el botón después del efecto
                setTimeout(() => {
                    button.style.transform = '';
                }, 150);

                // Redirigir a la página de biblioteca
                setTimeout(() => {
                    window.open('<?= APP_URL ?>/biblioteca', '_blank')
                    //window.location.href = '<?= APP_URL ?>/biblioteca';
                }, 100);
            }

            // ============================================================
            // FUNCIÓN CORREGIDA - CALCULAR FECHA DE SALIDA CON IDIOMA DINÁMICO
            // ============================================================

            // ============================================================
            // FUNCIÓN CORREGIDA - FECHA DE SALIDA (sin null, con traducción)
            // ============================================================

            function actualizarFechaSalida() {
                const fechaLlegada = document.getElementById('arrival-date')?.value;
                const calculatedDeparture = document.getElementById('calculated-departure');
                const hiddenDeparture = document.getElementById('departure-date-hidden');

                if (!calculatedDeparture) return;

                // ✅ Si no hay fecha de llegada o días, mostrar mensaje
                if (!fechaLlegada || !diasPrograma || diasPrograma.length === 0) {
                    calculatedDeparture.textContent = 'La fecha de salida se calcula automáticamente según los días del programa';
                    calculatedDeparture.style.fontStyle = 'italic';
                    if (hiddenDeparture) hiddenDeparture.value = '';
                    return;
                }

                // Calcular duración total
                let duracionTotal = 0;
                diasPrograma.forEach(dia => {
                    const duracion = parseInt(dia.duracion_estancia) || 1;
                    duracionTotal += duracion;
                });

                if (duracionTotal === 0) {
                    duracionTotal = diasPrograma.length;
                }

                // Calcular fecha de salida
                const fechaInicio = new Date(fechaLlegada);
                const fechaSalida = new Date(fechaInicio);
                fechaSalida.setDate(fechaInicio.getDate() + duracionTotal);

                // ✅ OBTENER IDIOMA SELECCIONADO (selector correcto)
                const idiomaSeleccionado = document.getElementById('language')?.value || 'es';

                // ✅ MAPEAR IDIOMA A LOCALE
                const localeMap = {
                    'es': 'es-ES',
                    'en': 'en-US',
                    'fr': 'fr-FR',
                    'de': 'de-DE',
                    'pt': 'pt-BR',
                    'it': 'it-IT'
                };
                const locale = localeMap[idiomaSeleccionado] || 'es-ES';

                // Formatear fecha para mostrar
                const opciones = { year: 'numeric', month: 'long', day: 'numeric' };
                const fechaFormateada = fechaSalida.toLocaleDateString(locale, opciones);

                // Formatear fecha para backend
                const fechaBackend = fechaSalida.toISOString().split('T')[0];

                // Textos traducibles
                const textSingular = document.getElementById('text-day-singular')?.textContent || 'día total';
                const textPlural = document.getElementById('text-days-plural')?.textContent || 'días total';
                const duracionTexto = duracionTotal === 1 ? textSingular : textPlural;

                // ✅ Actualizar div con texto (no input)
                calculatedDeparture.textContent = `${fechaFormateada} (${duracionTotal} ${duracionTexto})`;
                calculatedDeparture.style.fontStyle = 'normal';
                calculatedDeparture.style.fontWeight = '500';

                if (hiddenDeparture) {
                    hiddenDeparture.value = fechaBackend;
                }
            }

            // Event listeners
            document.getElementById('arrival-date')?.addEventListener('change', actualizarFechaSalida);

            // ✅ LISTENER CORRECTO para cambio de idioma
            document.getElementById('language')?.addEventListener('change', actualizarFechaSalida);

            document.addEventListener('DOMContentLoaded', function () {
                if (document.getElementById('arrival-date')?.value) {
                    actualizarFechaSalida();
                }
            });

            // Actualizar después de cargar días
            const originalCargarDiasPrograma = cargarDiasPrograma;
            cargarDiasPrograma = async function () {
                await originalCargarDiasPrograma();
                actualizarFechaSalida();
            };

            // ============================================================
            // EDICIÓN DE DÍAS - FUNCIONES COMPLETAS
            // ============================================================

            // Abrir formulario de edición inline
            function abrirEdicionDia(diaId) {
                console.log(`📝 Abriendo edición de día ${diaId}`);

                const formElement = document.getElementById(`edit-dia-form-${diaId}`);
                if (!formElement) {
                    console.error('Formulario no encontrado');
                    return;
                }

                // Ocultar otros formularios de edición abiertos
                document.querySelectorAll('.edit-inline-form').forEach(form => {
                    form.style.display = 'none';
                });

                // Mostrar este formulario
                formElement.style.display = 'block';

                // Scroll suave al formulario
                formElement.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

                // Inicializar búsqueda de ubicación para este día
                inicializarBusquedaUbicacionDia(diaId);

                // ✅ CARGAR UBICACIONES SECUNDARIAS
                cargarUbicacionesSecundariasEdit(diaId);
            }

            // Cerrar formulario de edición
            function cerrarEdicionDia(diaId) {
                const formElement = document.getElementById(`edit-dia-form-${diaId}`);
                if (formElement) {
                    formElement.style.display = 'none';
                }
            }

            // Guardar cambios del día
            async function guardarEdicionDia(diaId) {
                console.log(`💾 Guardando edición de día ${diaId}`);

                try {
                    // Obtener valores principales
                    const titulo = document.getElementById(`edit-dia-titulo-${diaId}`)?.value?.trim();
                    const descripcion = document.getElementById(`edit-dia-descripcion-${diaId}`)?.value?.trim();
                    const ubicacion = document.getElementById(`edit-dia-ubicacion-${diaId}`)?.value?.trim();
                    const latitud = document.getElementById(`edit-dia-latitud-${diaId}`)?.value || null;
                    const longitud = document.getElementById(`edit-dia-longitud-${diaId}`)?.value || null;

                    // Validaciones
                    if (!titulo) {
                        showAlert('El título es obligatorio', 'error');
                        return;
                    }

                    if (!descripcion) {
                        showAlert('La descripción es obligatoria', 'error');
                        return;
                    }

                    if (!ubicacion) {
                        showAlert('La ubicación es obligatoria', 'error');
                        return;
                    }

                    // ✅ RECOPILAR UBICACIONES SECUNDARIAS
                    const ubicacionesSecundarias = [];
                    const container = document.getElementById(`ubicaciones-secundarias-edit-${diaId}`);

                    if (container) {
                        const items = container.querySelectorAll('.ubicacion-secundaria-item');
                        items.forEach((item, index) => {
                            const itemId = `ubic-sec-${diaId}-${index}`;
                            const ubicInput = document.getElementById(`${itemId}-input`);
                            const latInput = document.getElementById(`${itemId}-lat`);
                            const lngInput = document.getElementById(`${itemId}-lng`);

                            if (ubicInput && ubicInput.value.trim()) {
                                ubicacionesSecundarias.push({
                                    id: item.dataset.ubicId || null,
                                    ubicacion: ubicInput.value.trim(),
                                    latitud: latInput?.value || null,
                                    longitud: lngInput?.value || null,
                                    orden: index + 1
                                });
                            }
                        });
                    }

                    const dataToSend = {
                        action: 'update',
                        dia_id: diaId,
                        data: {
                            titulo: titulo,
                            descripcion: descripcion,
                            ubicacion: ubicacion,
                            latitud: latitud,
                            longitud: longitud,
                            ubicaciones_secundarias: ubicacionesSecundarias
                        }
                    };

                    // ✅ RECOPILAR IMÁGENES NUEVAS Y DETECTAR ELIMINADAS
                    const imagenes = {};
                    for (let i = 1; i <= 3; i++) {
                        const imgInput = document.getElementById(`edit-dia-imagen${i}-${diaId}`);
                        const existingImg = document.querySelector(`#edit-dia-form-${diaId} .image-preview-item[data-image-number="${i}"] .preview-img`);

                        if (imgInput && imgInput.files && imgInput.files[0]) {
                            // Tiene una imagen nueva para subir
                            imagenes[`imagen${i}`] = imgInput.files[0];
                        } else if (!existingImg) {
                            // NO hay imagen nueva y NO hay preview -> fue eliminada
                            dataToSend.data[`imagen${i}`] = '';
                        }
                    }

                    // ✅ SUBIR IMÁGENES NUEVAS AL SERVIDOR (si hay)
                    let imagenesSubidas = {};
                    if (Object.keys(imagenes).length > 0) {
                        console.log('📸 Subiendo imágenes del día...');
                        imagenesSubidas = await subirImagenesDia(diaId, imagenes);
                        console.log('✅ Imágenes subidas:', imagenesSubidas);
                    }

                    // ✅ AGREGAR URLs DE IMÁGENES SUBIDAS AL DATA
                    if (imagenesSubidas.imagen1) dataToSend.data.imagen1 = imagenesSubidas.imagen1;
                    if (imagenesSubidas.imagen2) dataToSend.data.imagen2 = imagenesSubidas.imagen2;
                    if (imagenesSubidas.imagen3) dataToSend.data.imagen3 = imagenesSubidas.imagen3;

                    console.log('📤 Enviando actualización:', dataToSend);

                    // Enviar actualización
                    const response = await fetch('<?= APP_URL ?>/modules/programa/dias_api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(dataToSend)
                    });

                    console.log('📡 Status de respuesta:', response.status);

                    // 🔍 LEER LA RESPUESTA COMPLETA PARA VER EL ERROR
                    const responseText = await response.text();
                    console.log('📄 Respuesta completa del servidor:', responseText);

                    let result;
                    try {
                        result = JSON.parse(responseText);
                    } catch (e) {
                        console.error('❌ Error parseando JSON:', e);
                        console.error('Texto recibido:', responseText.substring(0, 500));
                        showAlert('Error del servidor: ' + responseText.substring(0, 200), 'error');
                        return;
                    }

                    if (result.success) {
                        showAlert('Día actualizado correctamente', 'success');
                        cerrarEdicionDia(diaId);
                        await cargarDiasPrograma();

                        // Refrescar la vista de detalle para reflejar los cambios (incluyendo el formulario limpio)
                        if (typeof selectedDayId !== 'undefined' && selectedDayId == diaId) {
                            seleccionarDiaEnSidebar(diaId);
                        }
                    } else {
                        console.error('❌ Error del servidor:', result);
                        showAlert(result.error || result.message || 'Error al actualizar día', 'error');
                    }

                } catch (error) {
                    console.error('❌ Error:', error);
                    showAlert('Error de conexión al actualizar día: ' + error.message, 'error');
                }
            }

            // Subir imágenes del día
            async function subirImagenesDia(diaId, imagenes) {
                const formData = new FormData();
                formData.append('type', 'dia');
                formData.append('item_id', diaId);

                for (const [key, file] of Object.entries(imagenes)) {
                    formData.append(key, file);
                }

                const response = await fetch('<?= APP_URL ?>/modules/programa/upload_images.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.error || 'Error subiendo imágenes');
                }

                return result.images;
            }

            // Preview de imagen de día
            function previewImagenDia(diaId, imageNumber, input) {
                if (input.files && input.files[0]) {
                    const reader = new FileReader();

                    reader.onload = function (e) {
                        const container = input.closest('.image-preview-item');
                        const existingImg = container.querySelector('.preview-img');
                        const emptySlot = container.querySelector('.empty-image-slot');
                        const btnChange = container.querySelector('.btn-change-image');

                        if (existingImg) {
                            existingImg.src = e.target.result;
                        } else if (emptySlot) {
                            emptySlot.remove();
                            const img = document.createElement('img');
                            img.src = e.target.result;
                            img.alt = `Imagen ${imageNumber}`;
                            img.className = 'preview-img';
                            container.insertBefore(img, btnChange);

                            // Agregar botón de remover
                            const btnRemove = document.createElement('button');
                            btnRemove.type = 'button';
                            btnRemove.className = 'btn-remove-image';
                            btnRemove.onclick = () => removerImagenDia(diaId, imageNumber);
                            btnRemove.innerHTML = '<i class="fas fa-times"></i>';
                            container.appendChild(btnRemove);
                        }

                        btnChange.textContent = 'Cambiar';
                    };

                    reader.readAsDataURL(input.files[0]);
                }
            }

            // Remover imagen de día
            function removerImagenDia(diaId, imageNumber) {
                const container = document.querySelector(`#edit-dia-form-${diaId} .image-preview-item[data-image-number="${imageNumber}"]`);
                if (!container) return;

                const img = container.querySelector('.preview-img');
                const btnRemove = container.querySelector('.btn-remove-image');
                const btnChange = container.querySelector('.btn-change-image');
                const input = document.getElementById(`edit-dia-imagen${imageNumber}-${diaId}`);

                if (img) img.remove();
                if (btnRemove) btnRemove.remove();

                // Agregar empty slot
                const emptySlot = document.createElement('div');
                emptySlot.className = 'empty-image-slot';
                emptySlot.innerHTML = `
        <i class="fas fa-image"></i>
        <p>Imagen ${imageNumber}</p>
    `;
                container.insertBefore(emptySlot, btnChange);

                // Limpiar input
                if (input) input.value = '';

                btnChange.textContent = 'Agregar';
            }

            // Inicializar búsqueda de ubicación para día
            function inicializarBusquedaUbicacionDia(diaId) {
                const input = document.getElementById(`edit-dia-ubicacion-${diaId}`);
                const resultsContainer = document.getElementById(`location-results-dia-${diaId}`);

                if (!input || !resultsContainer) return;

                let searchTimeout;

                input.addEventListener('input', function () {
                    clearTimeout(searchTimeout);
                    const query = this.value.trim();

                    if (query.length < 3) {
                        resultsContainer.classList.remove('active');
                        return;
                    }

                    searchTimeout = setTimeout(async () => {
                        try {
                            const results = await buscarUbicacion(query);
                            mostrarResultadosUbicacion(results, resultsContainer, diaId, 'dia');
                        } catch (error) {
                            console.error('Error buscando ubicación:', error);
                        }
                    }, 500);
                });

                // Cerrar resultados al hacer clic fuera
                document.addEventListener('click', function (e) {
                    if (!input.contains(e.target) && !resultsContainer.contains(e.target)) {
                        resultsContainer.classList.remove('active');
                    }
                });
            }

            // ============================================================
            // EDICIÓN DE ACTIVIDADES - FUNCIONES COMPLETAS
            // ============================================================

            // Abrir formulario de edición de actividad
            function abrirEdicionActividad(actividadId) {
                console.log(`📝 Abriendo edición de actividad ${actividadId}`);

                // Primero verificar si el formulario ya existe
                let formElement = document.getElementById(`edit-actividad-form-${actividadId}`);

                if (!formElement) {
                    // Si no existe, necesitamos obtener los datos de la actividad y crear el formulario
                    const actividad = buscarActividadPorId(actividadId);
                    if (!actividad) {
                        showAlert('Actividad no encontrada', 'error');
                        return;
                    }

                    // Crear el formulario dinámicamente
                    const container = document.querySelector(`[data-servicio-id="${actividadId}"]`)?.closest('.service-group') ||
                        document.querySelector(`[data-alternativa-id="${actividadId}"]`)?.closest('.service-group');

                    if (!container) {
                        showAlert('No se encontró el contenedor', 'error');
                        return;
                    }

                    const formHTML = renderizarFormularioEdicionActividad(actividad);
                    container.insertAdjacentHTML('afterend', formHTML);

                    formElement = document.getElementById(`edit-actividad-form-${actividadId}`);
                }

                // Ocultar otros formularios
                document.querySelectorAll('.edit-inline-form').forEach(form => {
                    form.style.display = 'none';
                });

                // Mostrar este formulario
                formElement.style.display = 'block';

                // Scroll
                formElement.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

                // Inicializar búsqueda de ubicación
                inicializarBusquedaUbicacionActividad(actividadId);
            }

            // Buscar actividad por ID en los servicios cargados
            function buscarActividadPorId(actividadId) {
                console.log('🔍 Buscando actividad:', actividadId);
                console.log('📦 Estructura diasPrograma:', diasPrograma);

                // Buscar en el día seleccionado primero (más eficiente)
                if (selectedDayId) {
                    const diaSeleccionado = diasPrograma.find(d => d.id == selectedDayId);
                    if (diaSeleccionado && diaSeleccionado.servicios) {

                        // Buscar en servicios principales
                        for (const servicio of diaSeleccionado.servicios) {
                            if (servicio.id == actividadId) {
                                console.log('✅ Actividad encontrada (principal):', servicio);
                                return servicio;
                            }

                            // Buscar en alternativas
                            if (servicio.alternativas && Array.isArray(servicio.alternativas)) {
                                const alternativa = servicio.alternativas.find(alt => alt.id == actividadId);
                                if (alternativa) {
                                    console.log('✅ Actividad encontrada (alternativa):', alternativa);
                                    return alternativa;
                                }
                            }
                        }
                    }
                }

                // Si no se encontró en el día seleccionado, buscar en todos los días
                for (const dia of diasPrograma) {
                    if (!dia.servicios) continue;

                    for (const servicio of dia.servicios) {
                        if (servicio.id == actividadId) {
                            console.log('✅ Actividad encontrada en día', dia.id, ':', servicio);
                            return servicio;
                        }

                        // Buscar en alternativas
                        if (servicio.alternativas && Array.isArray(servicio.alternativas)) {
                            const alternativa = servicio.alternativas.find(alt => alt.id == actividadId);
                            if (alternativa) {
                                console.log('✅ Actividad encontrada (alternativa) en día', dia.id, ':', alternativa);
                                return alternativa;
                            }
                        }
                    }
                }

                console.error('❌ Actividad no encontrada:', actividadId);
                console.log('📋 Días disponibles:', diasPrograma.map(d => ({ id: d.id, servicios: d.servicios?.length })));

                return null;
            }
            // ⭐ FUNCIÓN TEMPORAL DE DEBUG - Agregar al final del <script>
            function debugEstructuraServicios() {
                console.log('=== DEBUG ESTRUCTURA ===');
                console.log('Día seleccionado:', selectedDayId);

                const dia = diasPrograma.find(d => d.id == selectedDayId);
                if (dia) {
                    console.log('📅 Día completo:', dia);
                    console.log('📋 Servicios:', dia.servicios);

                    if (dia.servicios) {
                        dia.servicios.forEach((servicio, index) => {
                            console.log(`Servicio ${index}:`, {
                                id: servicio.id,
                                tipo: servicio.tipo_servicio,
                                nombre: servicio.nombre || servicio.titulo,
                                alternativas: servicio.alternativas?.length || 0
                            });
                        });
                    }
                }
                console.log('=== FIN DEBUG ===');
            }

            // Llamar automáticamente al cargar servicios
            window.debugEstructuraServicios = debugEstructuraServicios;
            // Renderizar formulario de edición de actividad
            function renderizarFormularioEdicionActividad(actividad) {
                return `
        <div id="edit-actividad-form-${actividad.id}" class="edit-inline-form edit-actividad" style="display: none;">
            <div class="edit-form-header">
                <h4><i class="fas fa-edit"></i> Editar Actividad</h4>
                <button class="btn-close-edit" onclick="cerrarEdicionActividad(${actividad.id})">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="edit-form-body">
                <!-- Nombre -->
                <div class="form-group">
                    <label for="edit-act-nombre-${actividad.id}">
                        Nombre <span class="required">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="edit-act-nombre-${actividad.id}" 
                        class="form-control"
                        value="${actividad.nombre || actividad.titulo || ''}"
                        maxlength="300"
                        required
                    >
                </div>
                
                <!-- Descripción -->
                <div class="form-group">
                    <label for="edit-act-descripcion-${actividad.id}">
                        Descripción <span class="required">*</span>
                    </label>
                    <textarea 
                        id="edit-act-descripcion-${actividad.id}" 
                        class="form-control"
                        rows="4"
                        maxlength="2000"
                        required
                    >${actividad.descripcion || ''}</textarea>
                </div>
                
                <!-- Ubicación -->
                <div class="form-group">
                    <label for="edit-act-ubicacion-${actividad.id}">
                        Ubicación <span class="required">*</span>
                    </label>
                    <div class="location-search-wrapper">
                        <input 
                            type="text" 
                            id="edit-act-ubicacion-${actividad.id}" 
                            class="form-control location-search-input"
                            value="${actividad.ubicacion || ''}"
                            placeholder="Buscar ubicación..."
                            autocomplete="off"
                        >
                        <div id="location-results-act-${actividad.id}" class="location-results"></div>
                    </div>
                    <input type="hidden" id="edit-act-latitud-${actividad.id}" value="${actividad.latitud || ''}">
                    <input type="hidden" id="edit-act-longitud-${actividad.id}" value="${actividad.longitud || ''}">
                </div>
                
                <!-- Imágenes -->
                <div class="form-group">
                    <label>
                        Imágenes <span class="required">*</span>
                        <small>(mínimo 1 imagen)</small>
                    </label>
                    
                    <div class="images-preview-edit">
                        ${[1, 2, 3].map(i => {
                    const imagenUrl = actividad['imagen' + i] || actividad[`actividad_imagen${i}`];
                    return `
                                <div class="image-preview-item" data-image-number="${i}">
                                    ${imagenUrl ? `
                                        <img src="${imagenUrl}" alt="Imagen ${i}" class="preview-img">
                                        <button type="button" class="btn-remove-image" onclick="removerImagenActividad(${actividad.id}, ${i})">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    ` : `
                                        <div class="empty-image-slot">
                                            <i class="fas fa-image"></i>
                                            <p>Imagen ${i}</p>
                                        </div>
                                    `}
                                    <input 
                                        type="file" 
                                        id="edit-act-imagen${i}-${actividad.id}" 
                                        accept="image/jpeg,image/jpg,image/png,image/webp"
                                        onchange="previewImagenActividad(${actividad.id}, ${i}, this)"
                                        style="display: none;"
                                    >
                                    <button type="button" class="btn-change-image" onclick="document.getElementById('edit-act-imagen${i}-${actividad.id}').click()">
                                        ${imagenUrl ? 'Cambiar' : 'Agregar'}
                                    </button>
                                </div>
                            `;
                }).join('')}
                    </div>
                </div>
                
                <!-- Botones -->
                <div class="edit-form-actions">
                    <button type="button" class="btn btn-secondary" onclick="cerrarEdicionActividad(${actividad.id})">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" onclick="guardarEdicionActividad(${actividad.id})">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                </div>
            </div>
        </div>
    `;
            }

            // Cerrar formulario de edición de actividad
            function cerrarEdicionActividad(actividadId) {
                const formElement = document.getElementById(`edit-actividad-form-${actividadId}`);
                if (formElement) {
                    formElement.style.display = 'none';
                }
            }

            // Guardar edición de actividad
            async function guardarEdicionActividad(actividadId) {
                console.log(`💾 Guardando edición de actividad ${actividadId}`);

                try {
                    // Obtener valores
                    const nombre = document.getElementById(`edit-act-nombre-${actividadId}`).value.trim();
                    const descripcion = document.getElementById(`edit-act-descripcion-${actividadId}`).value.trim();
                    const ubicacion = document.getElementById(`edit-act-ubicacion-${actividadId}`).value.trim();
                    const latitud = document.getElementById(`edit-act-latitud-${actividadId}`).value;
                    const longitud = document.getElementById(`edit-act-longitud-${actividadId}`).value;

                    // ⭐ VALIDACIONES
                    if (!nombre) {
                        showAlert('El nombre es obligatorio', 'error');
                        return;
                    }

                    if (!descripcion) {
                        showAlert('La descripción es obligatoria', 'error');
                        return;
                    }

                    // Validar al menos 1 imagen
                    const imagenes = {};
                    let tieneImagen = false;

                    for (let i = 1; i <= 3; i++) {
                        const imgInput = document.getElementById(`edit-act-imagen${i}-${actividadId}`);
                        const existingImg = document.querySelector(`#edit-actividad-form-${actividadId} .image-preview-item[data-image-number="${i}"] .preview-img`);

                        if (imgInput && imgInput.files && imgInput.files[0]) {
                            imagenes[`imagen${i}`] = imgInput.files[0];
                            tieneImagen = true;
                        } else if (existingImg) {
                            tieneImagen = true;
                        }
                    }

                    if (!tieneImagen) {
                        showAlert('Debe tener al menos 1 imagen', 'error');
                        return;
                    }

                    // ⭐ PASO 1: Subir imágenes nuevas (si hay)
                    let imagenesSubidas = {};
                    if (Object.keys(imagenes).length > 0) {
                        imagenesSubidas = await subirImagenesActividad(actividadId, imagenes);
                    }

                    // ⭐ PASO 2: Actualizar datos de la actividad
                    const dataToUpdate = {
                        nombre_servicio: nombre,
                        descripcion_servicio: descripcion,
                        ubicacion_servicio: ubicacion,
                        latitud: latitud || null,
                        longitud: longitud || null
                    };

                    // Detectar imágenes eliminadas explícitamente (sin nueva imagen y sin preview)
                    for (let i = 1; i <= 3; i++) {
                        const imgInput = document.getElementById(`edit-act-imagen${i}-${actividadId}`);
                        const existingImg = document.querySelector(`#edit-actividad-form-${actividadId} .image-preview-item[data-image-number="${i}"] .preview-img`);

                        if ((!imgInput || !imgInput.files || !imgInput.files[0]) && !existingImg) {
                            // Fue eliminada
                            dataToUpdate[`actividad_imagen${i}`] = '';
                        }
                    }

                    // Mapear imágenes subidas a campos de actividad
                    if (imagenesSubidas.imagen1) dataToUpdate.actividad_imagen1 = imagenesSubidas.imagen1;
                    if (imagenesSubidas.imagen2) dataToUpdate.actividad_imagen2 = imagenesSubidas.imagen2;
                    if (imagenesSubidas.imagen3) dataToUpdate.actividad_imagen3 = imagenesSubidas.imagen3;

                    const response = await fetch('<?= APP_URL ?>/modules/programa/servicios_api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'update',
                            servicio_id: actividadId,
                            ...dataToUpdate
                        })
                    });

                    const result = await response.json();

                    if (result.success) {
                        showAlert('Actividad actualizada exitosamente', 'success');
                        cerrarEdicionActividad(actividadId);

                        // Recargar servicios del día
                        if (selectedDayId) {
                            await cargarServiciosDia(selectedDayId);
                        }

                    } else {
                        showAlert(result.message || result.error || 'Error al actualizar actividad', 'error');
                    }

                } catch (error) {
                    console.error('Error:', error);
                    showAlert('Error de conexión al guardar la actividad', 'error');
                }
            }

            // Subir imágenes de actividad
            async function subirImagenesActividad(actividadId, imagenes) {
                const formData = new FormData();
                formData.append('type', 'actividad');
                formData.append('item_id', actividadId);

                for (const [key, file] of Object.entries(imagenes)) {
                    formData.append(key, file);
                }

                const response = await fetch('<?= APP_URL ?>/modules/programa/upload_images.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.error || 'Error subiendo imágenes');
                }

                return result.images;
            }

            // ============================================================
            // UBICACIONES SECUNDARIAS EN EDICIÓN DE DÍAS
            // ============================================================

            /**
             * Cargar ubicaciones secundarias del día
             */
            async function cargarUbicacionesSecundariasEdit(diaId) {
                try {
                    console.log(`📍 Cargando ubicaciones secundarias para día ${diaId}...`);

                    const response = await fetch(`<?= APP_URL ?>/modules/programa/dias_api.php?action=get_ubicaciones_secundarias&dia_id=${diaId}`);
                    const result = await response.json();

                    if (result.success) {
                        const ubicaciones = result.data || [];
                        console.log(`✅ Ubicaciones secundarias cargadas: ${ubicaciones.length}`);

                        renderizarUbicacionesSecundariasEdit(diaId, ubicaciones);
                    }

                } catch (error) {
                    console.error('Error cargando ubicaciones secundarias:', error);
                }
            }

            /**
             * Renderizar ubicaciones secundarias en el formulario
             */
            function renderizarUbicacionesSecundariasEdit(diaId, ubicaciones) {
                const container = document.getElementById(`ubicaciones-secundarias-edit-${diaId}`);
                if (!container) return;

                container.innerHTML = '';

                if (ubicaciones.length === 0) {
                    container.innerHTML = `
            <div style="padding: 15px; background: #f9fafb; border-radius: 8px; text-align: center; color: #6b7280; font-size: 13px;">
                <i class="fas fa-info-circle"></i> No hay ubicaciones adicionales
            </div>
        `;
                    return;
                }

                ubicaciones.forEach((ubic, index) => {
                    const itemId = `ubic-sec-${diaId}-${index}`;

                    const div = document.createElement('div');
                    div.className = 'ubicacion-secundaria-item';
                    div.dataset.ubicId = ubic.id || '';
                    div.dataset.index = index;
                    div.style.cssText = `
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 10px;
            align-items: start;
            padding: 12px;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        `;

                    div.innerHTML = `
            <div style="width: 100%;">
                <div class="location-search-wrapper">
                    <input 
                        type="text" 
                        id="${itemId}-input"
                        class="form-control location-search-input"
                        value="${ubic.ubicacion || ''}"
                        placeholder="Buscar ubicación..."
                        autocomplete="off"
                        data-dia-id="${diaId}"
                        data-ubic-index="${index}"
                    >
                    <div id="${itemId}-results" class="location-results"></div>
                </div>
                <input type="hidden" id="${itemId}-lat" value="${ubic.latitud || ''}">
                <input type="hidden" id="${itemId}-lng" value="${ubic.longitud || ''}">
                
                ${ubic.ubicacion ? `
                    <div class="ubicacion-preview" style="margin-top: 6px; padding: 8px; background: white; border-radius: 4px; font-size: 11px;">
                        <strong style="color: #334155;">${ubic.ubicacion}</strong>
                        ${ubic.latitud && ubic.longitud ? `
                            <div style="color: #64748b; margin-top: 2px;">
                                ${parseFloat(ubic.latitud).toFixed(6)}, ${parseFloat(ubic.longitud).toFixed(6)}
                            </div>
                        ` : ''}
                    </div>
                ` : ''}
            </div>
            
            <button 
                type="button" 
                class="btn btn-danger-outline" 
                onclick="removerUbicacionSecundariaEdit(${diaId}, ${index})"
                style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;"
                title="Eliminar ubicación">
                <i class="fas fa-trash"></i>
            </button>
        `;

                    container.appendChild(div);

                    // Inicializar búsqueda para esta ubicación
                    setTimeout(() => {
                        inicializarBusquedaUbicacionSecundaria(diaId, index, itemId);
                    }, 100);
                });
            }

            /**
             * Agregar nueva ubicación secundaria
             */
            function agregarUbicacionSecundariaEdit(diaId) {
                const container = document.getElementById(`ubicaciones-secundarias-edit-${diaId}`);
                if (!container) return;

                // Limpiar mensaje de "no hay ubicaciones"
                const emptyMessage = container.querySelector('div[style*="f9fafb"]');
                if (emptyMessage) {
                    emptyMessage.remove();
                }

                const index = container.children.length;
                const itemId = `ubic-sec-${diaId}-${index}`;

                const div = document.createElement('div');
                div.className = 'ubicacion-secundaria-item';
                div.dataset.index = index;
                div.style.cssText = `
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 10px;
        align-items: start;
        padding: 12px;
        background: #f8fafc;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
    `;

                div.innerHTML = `
        <div style="width: 100%;">
            <div class="location-search-wrapper">
                <input 
                    type="text" 
                    id="${itemId}-input"
                    class="form-control location-search-input"
                    placeholder="Buscar ubicación..."
                    autocomplete="off"
                    data-dia-id="${diaId}"
                    data-ubic-index="${index}"
                >
                <div id="${itemId}-results" class="location-results"></div>
            </div>
            <input type="hidden" id="${itemId}-lat" value="">
            <input type="hidden" id="${itemId}-lng" value="">
        </div>
        
        <button 
            type="button" 
            class="btn btn-danger-outline" 
            onclick="removerUbicacionSecundariaEdit(${diaId}, ${index})"
            style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;"
            title="Eliminar ubicación">
            <i class="fas fa-trash"></i>
        </button>
    `;

                container.appendChild(div);

                // Inicializar búsqueda
                setTimeout(() => {
                    inicializarBusquedaUbicacionSecundaria(diaId, index, itemId);
                }, 100);
            }

            /**
             * Remover ubicación secundaria
             */
            function removerUbicacionSecundariaEdit(diaId, index) {
                const container = document.getElementById(`ubicaciones-secundarias-edit-${diaId}`);
                if (!container) return;

                const items = container.querySelectorAll('.ubicacion-secundaria-item');
                if (items[index]) {
                    items[index].remove();
                }

                // Si no quedan ubicaciones, mostrar mensaje
                if (container.children.length === 0) {
                    container.innerHTML = `
            <div style="padding: 15px; background: #f9fafb; border-radius: 8px; text-align: center; color: #6b7280; font-size: 13px;">
                <i class="fas fa-info-circle"></i> No hay ubicaciones adicionales
            </div>
        `;
                }
            }
            /**
             * Buscar ubicación usando el MISMO API que biblioteca
             */
            async function buscarUbicacionNominatim(query, resultsDiv, onSelectCallback) {
                if (!query || query.length < 3) {
                    resultsDiv.classList.remove('active');
                    return;
                }

                try {
                    console.log(`🔍 Buscando ubicación: ${query}`);

                    // Mostrar loading
                    resultsDiv.innerHTML = `
            <div style="padding: 12px; text-align: center; color: #666;">
                <i class="fas fa-spinner fa-spin"></i> Buscando...
            </div>
        `;
                    resultsDiv.classList.add('active');

                    // ✅ USAR EL MISMO API QUE BIBLIOTECA
                    const response = await fetch(
                        `<?= APP_URL ?>/modules/ubicaciones/ubicaciones_api.php?action=search&q=${encodeURIComponent(query)}`
                    );

                    if (!response.ok) {
                        throw new Error('Error en la búsqueda');
                    }

                    const result = await response.json();

                    if (!result.success) {
                        throw new Error(result.error || 'Error en la búsqueda');
                    }

                    const results = result.data || [];
                    console.log(`✅ Resultados encontrados: ${results.length}`);

                    if (results.length === 0) {
                        resultsDiv.innerHTML = `
                <div style="padding: 12px; text-align: center; color: #666; font-size: 13px;">
                    <i class="fas fa-info-circle"></i> No se encontraron resultados
                </div>
            `;
                        return;
                    }

                    // Renderizar resultados
                    resultsDiv.innerHTML = results.map(result => `
            <div class="location-result-item" 
                 data-lat="${result.lat}" 
                 data-lon="${result.lon}"
                 data-display-name="${result.display_name}"
                 style="padding: 10px 12px; cursor: pointer; border-bottom: 1px solid #f0f0f0; transition: background 0.2s;">
                <div style="font-weight: 500; color: #1e293b; font-size: 13px;">
                    ${result.display_name}
                </div>
                <div style="font-size: 11px; color: #64748b; margin-top: 2px;">
                    📍 ${parseFloat(result.lat).toFixed(6)}, ${parseFloat(result.lon).toFixed(6)}
                </div>
            </div>
        `).join('');

                    // Agregar eventos de click a cada resultado
                    resultsDiv.querySelectorAll('.location-result-item').forEach(item => {
                        item.addEventListener('mouseenter', function () {
                            this.style.background = '#f8f9fa';
                        });

                        item.addEventListener('mouseleave', function () {
                            this.style.background = 'white';
                        });

                        item.addEventListener('click', function () {
                            const ubicacion = {
                                display_name: this.dataset.displayName,
                                lat: this.dataset.lat,
                                lon: this.dataset.lon
                            };

                            console.log('✅ Ubicación seleccionada:', ubicacion);

                            // Ejecutar callback
                            if (onSelectCallback) {
                                onSelectCallback(ubicacion);
                            }
                        });
                    });

                } catch (error) {
                    console.error('❌ Error buscando ubicación:', error);
                    resultsDiv.innerHTML = `
            <div style="padding: 12px; text-align: center; color: var(--secondary-color); font-size: 13px;">
                <i class="fas fa-exclamation-circle"></i> ${error.message || 'Error al buscar ubicación'}
            </div>
        `;
                }
            }
            /**
             * Inicializar búsqueda para ubicación secundaria
             */
            function inicializarBusquedaUbicacionSecundaria(diaId, index, itemId) {
                const input = document.getElementById(`${itemId}-input`);
                const resultsDiv = document.getElementById(`${itemId}-results`);

                if (!input || !resultsDiv) return;

                let searchTimeout;

                input.addEventListener('input', function () {
                    clearTimeout(searchTimeout);
                    const query = this.value.trim();

                    if (query.length < 3) {
                        resultsDiv.classList.remove('active');
                        return;
                    }

                    searchTimeout = setTimeout(() => {
                        buscarUbicacionNominatim(query, resultsDiv, (ubicacion) => {
                            // Callback cuando se selecciona una ubicación
                            input.value = ubicacion.display_name;
                            document.getElementById(`${itemId}-lat`).value = ubicacion.lat;
                            document.getElementById(`${itemId}-lng`).value = ubicacion.lon;
                            resultsDiv.classList.remove('active');

                            // Actualizar preview
                            const parent = input.closest('.ubicacion-secundaria-item');
                            const existingPreview = parent.querySelector('.ubicacion-preview');
                            if (existingPreview) existingPreview.remove();

                            const preview = document.createElement('div');
                            preview.className = 'ubicacion-preview';
                            preview.style.cssText = 'margin-top: 6px; padding: 8px; background: white; border-radius: 4px; font-size: 11px;';
                            preview.innerHTML = `
                    <strong style="color: #334155;">${ubicacion.display_name}</strong>
                    <div style="color: #64748b; margin-top: 2px;">
                        ${parseFloat(ubicacion.lat).toFixed(6)}, ${parseFloat(ubicacion.lon).toFixed(6)}
                    </div>
                `;
                            input.closest('div').appendChild(preview);
                        });
                    }, 300);
                });

                // Cerrar resultados al hacer clic fuera
                document.addEventListener('click', function (e) {
                    if (!input.contains(e.target) && !resultsDiv.contains(e.target)) {
                        resultsDiv.classList.remove('active');
                    }
                });
            }

            // Preview de imagen de actividad
            function previewImagenActividad(actividadId, imageNumber, input) {
                if (input.files && input.files[0]) {
                    const reader = new FileReader();

                    reader.onload = function (e) {
                        const container = input.closest('.image-preview-item');
                        const existingImg = container.querySelector('.preview-img');
                        const emptySlot = container.querySelector('.empty-image-slot');
                        const btnChange = container.querySelector('.btn-change-image');

                        if (existingImg) {
                            existingImg.src = e.target.result;
                        } else if (emptySlot) {
                            emptySlot.remove();
                            const img = document.createElement('img');
                            img.src = e.target.result;
                            img.alt = `Imagen ${imageNumber}`;
                            img.className = 'preview-img';
                            container.insertBefore(img, btnChange);

                            // Agregar botón de remover
                            const btnRemove = document.createElement('button');
                            btnRemove.type = 'button';
                            btnRemove.className = 'btn-remove-image';
                            btnRemove.onclick = () => removerImagenActividad(actividadId, imageNumber);
                            btnRemove.innerHTML = '<i class="fas fa-times"></i>';
                            container.appendChild(btnRemove);
                        }

                        btnChange.textContent = 'Cambiar';
                    };

                    reader.readAsDataURL(input.files[0]);
                }
            }

            // Remover imagen de actividad
            function removerImagenActividad(actividadId, imageNumber) {
                const container = document.querySelector(`#edit-actividad-form-${actividadId} .image-preview-item[data-image-number="${imageNumber}"]`);
                if (!container) return;

                const img = container.querySelector('.preview-img');
                const btnRemove = container.querySelector('.btn-remove-image');
                const btnChange = container.querySelector('.btn-change-image');
                const input = document.getElementById(`edit-act-imagen${imageNumber}-${actividadId}`);

                if (img) img.remove();
                if (btnRemove) btnRemove.remove();

                // Agregar empty slot
                const emptySlot = document.createElement('div');
                emptySlot.className = 'empty-image-slot';
                emptySlot.innerHTML = `
        <i class="fas fa-image"></i>
        <p>Imagen ${imageNumber}</p>
    `;
                container.insertBefore(emptySlot, btnChange);

                // Limpiar input
                if (input) input.value = '';

                btnChange.textContent = 'Agregar';
            }

            // Inicializar búsqueda de ubicación para actividad
            function inicializarBusquedaUbicacionActividad(actividadId) {
                const input = document.getElementById(`edit-act-ubicacion-${actividadId}`);
                const resultsContainer = document.getElementById(`location-results-act-${actividadId}`);

                if (!input || !resultsContainer) return;

                let searchTimeout;

                input.addEventListener('input', function () {
                    clearTimeout(searchTimeout);
                    const query = this.value.trim();

                    if (query.length < 3) {
                        resultsContainer.classList.remove('active');
                        return;
                    }

                    searchTimeout = setTimeout(async () => {
                        try {
                            const results = await buscarUbicacion(query);
                            mostrarResultadosUbicacion(results, resultsContainer, actividadId, 'act');
                        } catch (error) {
                            console.error('Error buscando ubicación:', error);
                        }
                    }, 500);
                });

                // Cerrar resultados al hacer clic fuera
                document.addEventListener('click', function (e) {
                    if (!input.contains(e.target) && !resultsContainer.contains(e.target)) {
                        resultsContainer.classList.remove('active');
                    }
                });
            }

            // ============================================================
            // FUNCIONES AUXILIARES COMPARTIDAS
            // ============================================================

            // Buscar ubicación en Nominatim
            async function buscarUbicacion(query) {
                try {
                    const response = await fetch(
                        `<?= APP_URL ?>/modules/programa/location_proxy.php?q=${encodeURIComponent(query)}`
                    );

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const data = await response.json();

                    if (data.error) {
                        console.error('Error del proxy:', data.error);
                        return [];
                    }

                    return data;
                } catch (error) {
                    console.error('Error buscando ubicación:', error);
                    return [];
                }
            }

            // Mostrar resultados de búsqueda
            function mostrarResultadosUbicacion(results, container, itemId, tipo) {
                if (!results || results.length === 0) {
                    container.innerHTML = '<div class="location-result-item">No se encontraron resultados</div>';
                    container.classList.add('active');
                    return;
                }

                container.innerHTML = results.map(result => `
        <div class="location-result-item" onclick="seleccionarUbicacion('${itemId}', '${tipo}', ${result.lat}, ${result.lon}, \`${result.display_name.replace(/`/g, '')}\`)">
            ${result.display_name}
        </div>
    `).join('');

                container.classList.add('active');
            }

            // Seleccionar ubicación
            function seleccionarUbicacion(itemId, tipo, lat, lon, displayName) {
                const prefix = tipo === 'dia' ? 'edit-dia' : 'edit-act';

                document.getElementById(`${prefix}-ubicacion-${itemId}`).value = displayName;
                document.getElementById(`${prefix}-latitud-${itemId}`).value = lat;
                document.getElementById(`${prefix}-longitud-${itemId}`).value = lon;

                // Cerrar resultados
                const resultsContainer = document.getElementById(`location-results-${tipo}-${itemId}`);
                if (resultsContainer) {
                    resultsContainer.classList.remove('active');
                }
            }

            console.log('✅ Funciones de edición de días y actividades cargadas');

            //Rditor acomodacion
            async function abrirEditorAlojamiento(servicioId) {
                const servicio = buscarServicioEnDias(servicioId);

                if (!servicio) {
                    showAlert('No se encontró el alojamiento', 'error');
                    return;
                }

                alojamientoEditando = servicio;

                setInputValue('edit-alojamiento-id', servicio.id);

                const nombreHotel = servicio.nombre || servicio.nombre_servicio || 'Alojamiento seleccionado';
                const display = document.getElementById('edit-alojamiento-nombre-display');

                if (display) {
                    display.innerHTML = `
            <strong>${escapeHtml(nombreHotel)}</strong>
            ${servicio.ubicacion || servicio.ubicacion_servicio ? `
                <span>${escapeHtml(servicio.ubicacion || servicio.ubicacion_servicio)}</span>
            ` : ''}
        `;
                }

                await cargarAcomodacionesEditor(servicio);

                const modal = document.getElementById('modal-editar-alojamiento');
                if (modal) {
                    modal.style.display = 'flex';
                }
            }

            function cerrarModalEditarAlojamiento() {
                document.getElementById('modal-editar-alojamiento').style.display = 'none';
                alojamientoEditando = null;
            }

            function buscarServicioEnDias(servicioId) {
                for (const dia of diasPrograma) {
                    const servicios = dia.servicios || [];
                    const encontrado = servicios.find(s => parseInt(s.id) === parseInt(servicioId));
                    if (encontrado) return encontrado;
                }
                return null;
            }

            async function cargarAcomodacionesEditor(servicio) {
                const hotelId = servicio.biblioteca_item_id;
                const select = document.getElementById('edit-alojamiento-acomodacion');

                select.innerHTML = '<option value="">Cargando...</option>';

                try {
                    const response = await fetch(`<?= APP_URL ?>/modules/biblioteca/api.php?action=get_acomodaciones&hotel_id=${hotelId}`);
                    const result = await response.json();

                    select.innerHTML = '<option value="">Sin acomodación</option>';

                    if (result.success) {
                        result.data.forEach(item => {
                            const label = formatearAcomodacionLabel(item);
                            select.innerHTML += `<option value="${item.id}">${escapeHtml(label)}</option>`;
                        });

                        if (servicio.acomodacion_id) {
                            select.value = servicio.acomodacion_id;
                        }
                    }

                } catch (error) {
                    console.error(error);
                    select.innerHTML = '<option value="">Sin acomodación</option>';
                }
            }

            async function guardarEdicionAlojamiento() {
                if (!alojamientoEditando) return;

                try {
                    const payload = {
                        action: 'update',
                        servicio_id: alojamientoEditando.id,
                        nombre_servicio: alojamientoEditando.nombre || alojamientoEditando.nombre_servicio || 'Alojamiento',
                        descripcion_servicio: alojamientoEditando.descripcion || alojamientoEditando.descripcion_servicio || 'Alojamiento',
                        ubicacion_servicio: alojamientoEditando.ubicacion || alojamientoEditando.ubicacion_servicio || '',
                        acomodacion_id: getInputValue('edit-alojamiento-acomodacion') || null
                    };

                    const response = await fetch('<?= APP_URL ?>/modules/programa/servicios_api.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });

                    const result = await response.json();

                    if (!result.success) {
                        showAlert(result.message || result.error || 'No se pudo actualizar la acomodación', 'error');
                        return;
                    }

                    showAlert('Acomodación actualizada correctamente', 'success');
                    cerrarModalEditarAlojamiento();

                    if (selectedDayId) {
                        await cargarServiciosDia(selectedDayId);
                    }

                } catch (error) {
                    console.error(error);
                    showAlert('Error de conexión', 'error');
                }
            }

            function abrirCrearAcomodacionDesdeEditor() {
                console.log('alojamientoEditando:', alojamientoEditando);

                const hotelId = alojamientoEditando?.biblioteca_item_id || alojamientoEditando?.biblioteca_id || alojamientoEditando?.hotel_id;

                if (!hotelId) {
                    showAlert('No se encontró el ID del alojamiento de biblioteca', 'error');
                    return;
                }

                selectedServicioId = hotelId;
                currentTipoServicio = 'alojamiento';

                setInputValue('nueva-acomodacion-tipo', '');
                setInputValue('nueva-acomodacion-descripcion', '');
                setInputValue('nueva-acomodacion-capacidad', '1');

                const modal = document.getElementById('modal-crear-acomodacion-programa');

                if (modal) {
                    modal.dataset.origen = 'editor';
                    modal.dataset.hotelId = hotelId;

                    const modalEditor = document.getElementById('modal-editar-alojamiento');
                    if (modalEditor) {
                        modalEditor.style.display = 'none';
                    }

                    modal.style.display = 'flex';
                }
            }


            function abrirBonoReservaPrograma() {
                const programaId = <?= $is_editing ? (int) $programa_id : 'null' ?>;

                if (!programaId) {
                    alert('Primero debes guardar el programa para generar el bono.');
                    return;
                }

                window.open(`<?= APP_URL ?>/modules/bonos/preview.php?programa_id=${programaId}`, '_blank');
            }

            function escapeHtml(value) {
                if (value === null || value === undefined) return '';

                return String(value)
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#039;');
            }

            function normalizarCodigoVuelo(codigo) {
                return String(codigo || '')
                    .trim()
                    .toUpperCase()
                    .replace(/\s+/g, '');
            }

            function handleFlightInputKey(event, diaId) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    buscarPreviewVuelo(diaId);
                }
            }

            async function buscarPreviewVuelo(diaId) {
                const input = document.getElementById(`flight-code-${diaId}`);
                const previewContainer = document.getElementById(`flight-preview-${diaId}`);
                const button = document.getElementById(`flight-search-btn-${diaId}`);

                console.log('CLICK buscar vuelo', diaId);
                console.log('VALOR INPUT:', input.value);

                if (!input || !previewContainer || !button) {
                    alert('No se encontró el bloque de vuelos.');
                    return;
                }

                const codigo = normalizarCodigoVuelo(input.value);

                if (!codigo) {
                    mostrarMensajeVuelo('Ingresa un código de vuelo.', 'error');
                    return;
                }

                if (!/^[A-Z0-9]{2,3}\d{1,5}$/.test(codigo)) {
                    mostrarMensajeVuelo('Formato inválido. Ej: EK521.', 'error');
                    return;
                }



                input.value = codigo;
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Buscando...';

                try {
                    const response = await API.previewVuelo(codigo, diaId);
                    const vuelo = response.vuelo;

                    previewContainer.style.display = 'block';
                    previewContainer.innerHTML = `
            <div class="flight-preview-card">
                <div>
                    <div class="flight-route-main">
                        ${escapeHtml(vuelo.codigo_vuelo)} · ${escapeHtml(vuelo.aerolinea)}
                    </div>

                    <div class="flight-route-meta">
                        ${escapeHtml(vuelo.ciudad_origen)} (${escapeHtml(vuelo.codigo_aeropuerto_origen)})
                        →
                        ${escapeHtml(vuelo.ciudad_destino)} (${escapeHtml(vuelo.codigo_aeropuerto_destino)})
                        <br>
                        Salida: ${escapeHtml(vuelo.hora_salida)}
                        · Llegada: ${escapeHtml(vuelo.hora_llegada)}
                        ${vuelo.terminal ? `<br>Terminal: ${escapeHtml(vuelo.terminal)}` : ''}
                    </div>
                </div>

                <button 
                    type="button"
                    class="flight-confirm-btn"
                    onclick="guardarVueloDia('${escapeHtml(vuelo.codigo_vuelo)}', ${diaId})">
                    <i class="fas fa-check"></i>
                    Agregar vuelo
                </button>
            </div>
        `;

                } catch (error) {
                    console.error('Error preview vuelo:', error);
                    previewContainer.style.display = 'none';
                    previewContainer.innerHTML = '';
                    UIHelpers.showMessage(error.message || 'No se encontró el vuelo para la fecha del día.', 'error');
                } finally {
                    button.disabled = false;
                    button.innerHTML = '<i class="fas fa-search"></i> Buscar';
                }
            }

            async function guardarVueloDia(codigoVuelo, diaId) {
                try {
                    await API.saveVuelo(codigoVuelo, diaId);

                    UIHelpers.showMessage('Vuelo agregado correctamente.', 'success');

                    const input = document.getElementById(`flight-code-${diaId}`);
                    const previewContainer = document.getElementById(`flight-preview-${diaId}`);

                    if (input) input.value = '';
                    if (previewContainer) {
                        previewContainer.style.display = 'none';
                        previewContainer.innerHTML = '';
                    }

                    await cargarVuelosDia(diaId);

                } catch (error) {
                    console.error('Error guardando vuelo:', error);
                    UIHelpers.showMessage(error.message || 'No se pudo guardar el vuelo.', 'error');
                }
            }

            async function cargarVuelosDia(diaId) {
                const container = document.getElementById(`flights-list-${diaId}`);
                if (!container) return;

                container.innerHTML = `
        <div class="flight-loading">
            <i class="fas fa-spinner fa-spin"></i>
            Cargando vuelos...
        </div>
    `;

                try {
                    const response = await API.getVuelos(diaId);
                    const vuelos = response.vuelos || [];

                    if (vuelos.length === 0) {
                        container.innerHTML = `
                <div class="flight-empty">
                    No hay vuelos asignados a este día.
                </div>
            `;
                        return;
                    }

                    container.innerHTML = vuelos.map(vuelo => renderizarVueloItem(vuelo, diaId)).join('');

                } catch (error) {
                    console.error('Error cargando vuelos:', error);
                    container.innerHTML = `
            <div class="flight-empty" style="color:var(--primary-color);">
                No se pudieron cargar los vuelos.
            </div>
        `;
                }
            }

            function renderizarVueloItem(vuelo, diaId) {
                return `
        <div class="flight-item">
            <div class="flight-item-top">
                <div>
                    <div class="flight-code">
                        ${escapeHtml(vuelo.orden)}. ${escapeHtml(vuelo.codigo_vuelo)}
                    </div>
                    <div class="flight-airline">
                        ${escapeHtml(vuelo.aerolinea)}
                    </div>
                </div>

                <button 
                    type="button"
                    class="flight-delete-btn"
                    title="Eliminar vuelo"
                    onclick="eliminarVueloDia(${parseInt(vuelo.vuelo_dia_id)}, ${diaId})">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="flight-route">
                <span>
                    <strong>${escapeHtml(vuelo.codigo_aeropuerto_origen)}</strong>
                    ${escapeHtml(vuelo.ciudad_origen)}
                </span>

                <i class="fas fa-arrow-right"></i>

                <span>
                    <strong>${escapeHtml(vuelo.codigo_aeropuerto_destino)}</strong>
                    ${escapeHtml(vuelo.ciudad_destino)}
                </span>
            </div>

            <div class="flight-time">
                <i class="far fa-clock"></i>
                ${escapeHtml(vuelo.hora_salida)}
                →
                ${escapeHtml(vuelo.hora_llegada)}
                ${vuelo.terminal ? `· Terminal ${escapeHtml(vuelo.terminal)}` : ''}
            </div>
        </div>
    `;
            }

            async function eliminarVueloDia(vueloDiaId, diaId) {
                if (!confirm('¿Eliminar este vuelo del día?')) return;

                try {
                    await API.deleteVuelo(vueloDiaId);
                    UIHelpers.showMessage('Vuelo eliminado correctamente.', 'success');
                    await cargarVuelosDia(diaId);

                } catch (error) {
                    console.error('Error eliminando vuelo:', error);
                    UIHelpers.showMessage(error.message || 'No se pudo eliminar el vuelo.', 'error');
                }

            }

            function mostrarMensajeVuelo(message, type = 'info') {
                console.log('MENSAJE VUELO:', message, type);

                if (window.UIHelpers && typeof window.UIHelpers.showMessage === 'function') {
                    window.UIHelpers.showMessage(message, type);

                }

                alert(message);
            }

        </script>
</body>

</html>