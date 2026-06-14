<?php
// ====================================================================
// ARCHIVO: pages/itinerarios.php - CON COMPONENTES UI ESTÁNDAR MEJORADO
// ====================================================================

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/app.php';

App::init();
App::requireLogin();

// Incluir ConfigManager y componentes UI
require_once 'config/config_functions.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/ui_components.php';

$user = App::getUser();
$isAdmin = $user['role'] === 'admin';

// Obtener configuración de colores según el rol del usuario
ConfigManager::init();
$userColors = ConfigManager::getColorsForRole($user['role']);
$companyName = ConfigManager::getCompanyName();
$logo = ConfigManager::getLogo();
$defaultLanguage = ConfigManager::getDefaultLanguage() ?? 'es';

function ts_hex_to_rgb_string($hex)
{
    $hex = trim((string) $hex);
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
        return '59, 130, 246';
    }
    return hexdec(substr($hex, 0, 2)) . ', ' . hexdec(substr($hex, 2, 2)) . ', ' . hexdec(substr($hex, 4, 2));
}

$primaryRgb = ts_hex_to_rgb_string($userColors['primary']);
$secondaryRgb = ts_hex_to_rgb_string($userColors['secondary']);
?>

<!DOCTYPE html>
<html lang="<?= $defaultLanguage ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Programas - <?= htmlspecialchars($companyName) ?></title>

    <!-- Incluir estilos de componentes -->
    <?= UIComponents::getComponentStyles() ?>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color:
                <?= $userColors['primary'] ?>
            ;
            --secondary-color:
                <?= $userColors['secondary'] ?>
            ;
            --primary-color-rgb:
                <?= $primaryRgb ?>
            ;
            --secondary-color-rgb:
                <?= $secondaryRgb ?>
            ;
            --primary-gradient: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            color: #333;
            min-height: 100vh;
        }

        /* Header con componentes */
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

        .VIpgJd-ZVi9od-ORHb-OEVmcd {
            left: 0;
            display: none !important;
            top: 0;
        }

        .goog-te-gadget img {
            vertical-align: middle;
            border: none;
            display: none;
        }

        /* Main Content mejorado */
        .main-content {
            margin-left: 0;
            margin-top: 70px;
            padding: 40px;
            transition: margin-left 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            min-height: calc(100vh - 70px);
        }

        .main-content.sidebar-open {
            margin-left: 320px;
        }

        /* Header de página */
        .page-header {
            text-align: center;
            margin-bottom: 40px;
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border-left: 4px solid var(--primary-color);
        }

        .page-title {
            font-size: 3rem;
            font-weight: 700;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 16px;
        }

        .page-subtitle {
            font-size: 1.2rem;
            color: #718096;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Estadísticas rápidas */
        .stats-section {
            margin-bottom: 40px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 24px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
            text-align: center;
            transition: all 0.3s ease;
            border-left: 4px solid var(--primary-color);
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 8px;
            display: block;
        }

        .stat-label {
            color: #718096;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .stat-icon {
            font-size: 1.5rem;
            color: var(--secondary-color);
            margin-bottom: 12px;
        }

        /* Acciones rápidas */
        .quick-actions {
            display: flex;
            gap: 16px;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 40px;
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: var(--primary-gradient);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
            color: white;
            text-decoration: none;
        }

        .action-btn.secondary {
            background: white;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }

        .action-btn.secondary:hover {
            background: var(--primary-color);
            color: white;
        }

        /* Sección de programas */
        .programs-section {
            background: white;
            border-radius: 15px;
            padding: 32px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
            border-left: 4px solid var(--primary-color);
            margin-bottom: 30px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .section-title {
            font-size: 1.8rem;
            font-weight: 600;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-title i {
            color: var(--primary-color);
        }

        .section-title .badge {
            background: var(--primary-color);
            color: white;
            font-size: 0.8rem;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 500;
        }

        /* Filtros y búsqueda */
        .filters-container {
            display: flex;
            gap: 16px;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-box {
            position: relative;
        }

        .search-input {
            padding: 12px 20px 12px 45px;
            border: 2px solid #e2e8f0;
            border-radius: 25px;
            font-size: 14px;
            width: 250px;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #718096;
        }

        .filter-select {
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 25px;
            font-size: 14px;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-select:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        /* Grid de programas */
        .programs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(270px, 1fr));
            gap: 16px;
            margin-top: 16px;
        }

        /* Tarjeta de programa */
        .program-card {
            background: white;
            border-radius: 11px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .program-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-gradient);
        }

        .program-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.10);
            border-color: var(--primary-color);
        }

        .program-card.readonly {
            border-color: #94a3b8;
            opacity: 0.9;
        }

        .program-card.readonly::before {
            background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%);
        }

        .program-image {
            height: 120px;
            background: var(--primary-gradient);
            position: relative;
            overflow: hidden;
        }




        .program-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 0;
            transition: transform 0.3s ease;
        }

        .program-card:hover .program-image img {
            transform: scale(1.05);
        }

        .program-image .placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: white;
            font-size: 1.5rem;
            background: var(--primary-gradient);
        }

        /* Fallback para imágenes que no cargan */
        .program-image img[src=""],
        .program-image img:not([src]) {
            display: none;
        }

        .program-image img[src=""]:after,
        .program-image img:not([src]):after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--primary-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
        }


        .program-content {
            padding: 13px 15px;
        }

        .program-header {
            margin-bottom: 10px;
        }

        .program-title {
            font-size: 1rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 5px;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .program-destination {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #718096;
            font-size: 0.82rem;
            margin-bottom: 2px;
        }

        .program-traveler {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #718096;
            font-size: 0.82rem;
        }

        .program-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin: 10px 0;
            padding: 10px 0;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
        }

        .detail-item {
            text-align: center;
        }

        .detail-label {
            font-size: 0.68rem;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
            font-weight: 600;
        }

        .detail-value {
            font-size: 0.82rem;
            font-weight: 600;
            color: #2d3748;
        }

        .detail-value.highlight {
            color: var(--primary-color);
        }

        .program-actions {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .btn-sm {
            padding: 6px 11px;
            font-size: 0.75rem;
            border-radius: 16px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .btn-primary-sm {
            background: var(--primary-color);
            color: white;
        }

        .btn-danger-sm {
            background: #ef4444;
            color: white;
        }

        .btn-danger-sm:hover {
            background: #dc2626;
            color: white;
            transform: translateY(-2px);
        }

        .btn-primary-sm:hover {
            background: var(--secondary-color);
            color: white;
            transform: translateY(-2px);
        }

        .btn-outline-sm {
            background: transparent;
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }

        .btn-outline-sm:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }

        .btn-readonly {
            background: #94a3b8;
            color: white;
            cursor: default;
        }

        .btn-readonly:hover {
            background: #94a3b8;
            transform: none;
        }

        /* Modal de creación */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 2000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            backdrop-filter: blur(5px);
        }

        .modal-overlay.show {
            opacity: 1;
            visibility: visible;
        }

        .modal {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0.9);
            background: white;
            border-radius: 20px;
            padding: 32px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        .modal-overlay.show .modal {
            transform: translate(-50%, -50%) scale(1);
        }

        .modal-header {
            text-align: center;
            margin-bottom: 24px;
        }

        .modal-title {
            font-size: 1.8rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
        }

        .modal-subtitle {
            color: #718096;
            font-size: 1rem;
        }

        .modal-options {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .modal-option {
            border: 2px solid #e2e8f0;
            border-radius: 15px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .modal-option:hover {
            border-color: var(--primary-color);
            background: rgba(102, 126, 234, 0.05);
            transform: translateY(-2px);
        }

        .modal-option.selected {
            border-color: var(--primary-color);
            background: rgba(102, 126, 234, 0.1);
        }

        .option-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            background: var(--primary-gradient);
            color: white;
            flex-shrink: 0;
        }

        .option-content {
            flex: 1;
        }

        .option-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 4px;
        }

        .option-description {
            color: #718096;
            font-size: 0.9rem;
        }

        .modal-form {
            margin-top: 24px;
            display: none;
        }

        .modal-form.show {
            display: block;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #374151;
        }

        .form-input,
        .form-select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-input:focus,
        .form-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .modal-actions {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            margin-top: 32px;
        }

        .modal-btn {
            flex: 1;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .modal-btn.primary {
            background: var(--primary-gradient);
            color: white;
        }

        .modal-btn.primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .modal-btn.secondary {
            background: #f3f4f6;
            color: #374151;
        }

        .modal-btn.secondary:hover {
            background: #e5e7eb;
        }

        /* Estados de carga */
        .loading-state,
        .empty-state,
        .error-state {
            text-align: center;
            padding: 60px 20px;
            color: #718096;
        }

        .state-icon {
            font-size: 4rem;
            margin-bottom: 24px;
            color: var(--primary-color);
        }

        .loading-state .state-icon {
            animation: spin 1s linear infinite;
        }

        .empty-state .state-icon {
            color: #718096;
        }

        .error-state .state-icon {
            color: #ef4444;
        }

        .state-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 16px;
            color: #2d3748;
        }

        .state-description {
            font-size: 1rem;
            margin-bottom: 24px;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        /* ── DASHBOARD HEADER ── */
        .dashboard-header {
            background: #fff;
            border-radius: 18px;
            padding: 24px 28px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, .06);
            border: 1px solid #e2e8f0;
            margin-bottom: 24px;
        }

        .dash-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 22px;
            gap: 16px;
            flex-wrap: wrap;
        }

        .dash-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1e293b;
            line-height: 1.2;
        }

        .dash-subtitle {
            font-size: 0.9rem;
            color: #64748b;
            margin-top: 4px;
        }

        .dash-actions {
            display: flex;
            gap: 10px;
            flex-shrink: 0;
            flex-wrap: wrap;
            align-items: center;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
        }

        .stat-mini {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            background: #f8fafc;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            transition: all .2s;
        }

        .stat-mini:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, .07);
            transform: translateY(-1px);
        }

        .stat-mini-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: rgba(var(--primary-color-rgb), .1);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .stat-mini-icon svg {
            width: 18px;
            height: 18px;
            stroke: var(--primary-color);
            fill: none;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .stat-mini-icon.amber {
            background: rgba(245, 158, 11, .12);
        }

        .stat-mini-icon.amber svg {
            stroke: #f59e0b;
        }

        .stat-mini-icon.slate {
            background: rgba(100, 116, 139, .1);
        }

        .stat-mini-icon.slate svg {
            stroke: #64748b;
        }

        .stat-mini-num {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            line-height: 1;
        }

        .stat-mini-label {
            font-size: 11px;
            color: #64748b;
            margin-top: 2px;
            font-weight: 500;
        }

        /* ── TAB SYSTEM ── */
        .tab-nav {
            display: flex;
            gap: 0;
            border-bottom: 2px solid #e2e8f0;
            margin-bottom: 20px;
            overflow-x: auto;
        }

        .tab-btn {
            display: flex;
            align-items: center;
            gap: 7px;
            padding: 12px 20px;
            border: none;
            background: none;
            cursor: pointer;
            font-size: 13.5px;
            font-weight: 500;
            color: #64748b;
            border-bottom: 2.5px solid transparent;
            margin-bottom: -2px;
            transition: all .2s;
            white-space: nowrap;
        }

        .tab-btn.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }

        .tab-btn:hover:not(.active) {
            color: #334155;
            background: #f8fafc;
        }

        .tab-btn svg {
            width: 15px;
            height: 15px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .tab-count {
            font-size: 11px;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 10px;
            background: #e2e8f0;
            color: #475569;
            transition: all .2s;
        }

        .tab-btn.active .tab-count {
            background: rgba(var(--primary-color-rgb), .12);
            color: var(--primary-color);
        }

        .tab-panel {
            display: none;
        }

        .tab-panel.active {
            display: block;
        }

        .tab-search {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        /* ── PLANTILLA CARDS ── */
        .program-card.es-plantilla::before {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }

        .plantilla-pill {
            position: absolute;
            top: 10px;
            right: 10px;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            padding: 3px 9px;
            border-radius: 6px;
            letter-spacing: .4px;
            text-transform: uppercase;
            z-index: 2;
            box-shadow: 0 1px 4px rgba(0, 0, 0, .2);
        }

        .btn-bookmark {
            position: absolute;
            top: 10px;
            left: 10px;
            width: 30px;
            height: 30px;
            border: none;
            border-radius: 8px;
            background: rgba(255, 255, 255, .92);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2;
            box-shadow: 0 1px 4px rgba(0, 0, 0, .15);
            transition: all .2s;
            backdrop-filter: blur(4px);
        }

        .btn-bookmark:hover {
            background: #fff;
            transform: scale(1.08);
        }

        .btn-bookmark svg {
            width: 14px;
            height: 14px;
            fill: none;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke: #64748b;
        }

        /* Botón de etiquetas en la tarjeta */
        .btn-tag-card {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 30px;
            height: 30px;
            border: none;
            border-radius: 8px;
            background: rgba(255, 255, 255, .92);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            z-index: 2;
            transition: transform .15s, background .15s, color .15s;
        }

        .btn-tag-card:hover {
            background: #fff;
            transform: scale(1.08);
            color: var(--primary-color);
        }

        .btn-tag-card i {
            font-size: 13px;
        }

        /* Chips de etiquetas mostrados en la tarjeta */
        .card-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 10px;
        }

        .card-tags .c-tag {
            font-size: 11px;
            font-weight: 600;
            padding: 3px 9px;
            border-radius: 20px;
        }

        /* ── PICKER MODAL ── */
        .modal.lg {
            max-width: 680px;
            width: 94vw;
        }

        .picker-search-wrap {
            position: relative;
            margin-bottom: 12px;
        }

        .picker-search-wrap svg {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            width: 14px;
            height: 14px;
            stroke: #94a3b8;
            fill: none;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .picker-search {
            width: 100%;
            padding: 9px 12px 9px 32px;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            font-size: 13px;
            outline: none;
            transition: border-color .2s;
        }

        .picker-search:focus {
            border-color: var(--primary-color);
        }

        .picker-tabs {
            display: flex;
            gap: 6px;
            margin-bottom: 12px;
        }

        .picker-tab {
            padding: 6px 14px;
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            background: #fff;
            font-size: 12px;
            font-weight: 500;
            color: #64748b;
            cursor: pointer;
            transition: all .15s;
        }

        .picker-tab.active {
            border-color: var(--primary-color);
            background: rgba(var(--primary-color-rgb), .07);
            color: var(--primary-color);
            font-weight: 600;
        }

        .picker-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            max-height: 240px;
            overflow-y: auto;
            padding-right: 2px;
        }

        .picker-card {
            padding: 11px 13px;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            cursor: pointer;
            transition: all .15s;
            position: relative;
        }

        .picker-card:hover {
            border-color: rgba(var(--primary-color-rgb), .5);
            background: rgba(var(--primary-color-rgb), .03);
        }

        .picker-card.selected {
            border-color: var(--primary-color);
            background: rgba(var(--primary-color-rgb), .07);
        }

        .picker-card.selected::after {
            content: '✓';
            position: absolute;
            top: 8px;
            right: 10px;
            color: var(--primary-color);
            font-weight: 700;
            font-size: 12px;
        }

        .picker-card-title {
            font-size: 12.5px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            padding-right: 18px;
        }

        .picker-card-meta {
            font-size: 11px;
            color: #64748b;
            line-height: 1.4;
        }

        .picker-empty {
            text-align: center;
            padding: 24px;
            color: #94a3b8;
            font-size: 13px;
            grid-column: 1/-1;
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

        /* Responsive */
        @media (max-width: 1024px) {
            .programs-grid {
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .header {
                padding: 15px 20px;
            }

            .main-content {
                padding: 20px;
            }

            .main-content.sidebar-open {
                margin-left: 0;
            }

            .page-title {
                font-size: 2rem;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .stats-row {
                grid-template-columns: repeat(2, 1fr);
            }

            .programs-grid {
                grid-template-columns: 1fr;
            }

            .section-header {
                flex-direction: column;
                align-items: stretch;
            }

            .filters-container {
                justify-content: center;
            }

            .search-input {
                width: 100%;
                max-width: 300px;
            }

            .quick-actions {
                flex-direction: column;
                align-items: center;
            }

            .action-btn {
                width: 100%;
                max-width: 300px;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .stats-row {
                grid-template-columns: repeat(2, 1fr);
            }

            .program-details {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .programs-section {
                padding: 20px;
            }
        }

        /* Toast notifications */
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
            background: linear-gradient(135deg, #10b981 0%, #047857 100%);
        }

        .toast.error {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }

        /* ===== Rediseño limpio TravelSoft - Itinerarios ===== */
        body {
            background: radial-gradient(circle at top left, rgba(var(--primary-color-rgb), 0.07), transparent 34%), #f6f8fb !important;
            color: #0f172a !important;
        }

        .main-content {
            padding: 32px !important;
            max-width: 1480px;
        }

        .page-header {
            text-align: left !important;
            padding: 30px 32px !important;
            border-radius: 28px !important;
            border: 1px solid rgba(var(--primary-color-rgb), 0.12) !important;
            border-left: 1px solid rgba(var(--primary-color-rgb), 0.12) !important;
            background: linear-gradient(135deg, #ffffff 0%, rgba(var(--primary-color-rgb), 0.055) 100%) !important;
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.075) !important;
            position: relative;
            overflow: hidden;
        }

        .page-header::after {
            content: '';
            position: absolute;
            right: -60px;
            top: -80px;
            width: 230px;
            height: 230px;
            border-radius: 999px;
            background: linear-gradient(135deg, rgba(var(--primary-color-rgb), 0.14), rgba(var(--secondary-color-rgb), 0.08));
        }

        .page-title {
            display: flex !important;
            align-items: center !important;
            gap: 14px !important;
            color: #111827 !important;
            background: none !important;
            -webkit-text-fill-color: initial !important;
            font-size: clamp(2rem, 3vw, 2.7rem) !important;
            letter-spacing: -0.045em !important;
            margin-bottom: 8px !important;
            position: relative;
            z-index: 1;
        }

        .page-title i,
        .section-title i,
        .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(var(--primary-color-rgb), 0.10);
            color: var(--primary-color) !important;
            font-size: 18px !important;
            box-shadow: 0 10px 24px rgba(var(--primary-color-rgb), 0.12);
        }

        .page-subtitle {
            margin: 0 !important;
            max-width: 720px !important;
            color: #64748b !important;
            font-size: 1rem !important;
            line-height: 1.65 !important;
            position: relative;
            z-index: 1;
        }

        .stats-grid {
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)) !important;
            gap: 18px !important;
        }

        .stat-card,
        .programs-section,
        .program-card,
        .modal {
            border-radius: 24px !important;
            border: 1px solid #e5e7eb !important;
            border-left: 1px solid #e5e7eb !important;
            background: rgba(255, 255, 255, 0.96) !important;
            box-shadow: 0 16px 38px rgba(15, 23, 42, 0.065) !important;
        }

        .stat-card {
            text-align: left !important;
            padding: 22px !important;
            display: grid;
            grid-template-columns: auto 1fr;
            align-items: center;
            column-gap: 15px;
        }

        .stat-card:hover,
        .program-card:hover {
            transform: translateY(-3px) !important;
            box-shadow: 0 22px 48px rgba(15, 23, 42, 0.10) !important;
        }

        .stat-icon {
            grid-row: span 2;
            margin-bottom: 0 !important;
        }

        .stat-number {
            color: #111827 !important;
            font-size: 2rem !important;
            line-height: 1 !important;
            margin-bottom: 4px !important;
        }

        .stat-label {
            color: #64748b !important;
            font-size: .86rem !important;
            font-weight: 700 !important;
        }

        .quick-actions {
            justify-content: flex-start !important;
            margin-bottom: 34px !important;
        }

        .action-btn,
        .modal-btn,
        .btn-sm {
            border-radius: 14px !important;
            font-weight: 750 !important;
            letter-spacing: -0.01em;
        }

        .action-btn {
            padding: 13px 18px !important;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)) !important;
            box-shadow: 0 12px 26px rgba(var(--primary-color-rgb), 0.22) !important;
        }

        .action-btn.secondary {
            background: #ffffff !important;
            color: var(--primary-color) !important;
            border: 1px solid rgba(var(--primary-color-rgb), 0.18) !important;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06) !important;
        }

        .programs-section {
            padding: 28px !important;
            margin-bottom: 26px !important;
        }

        .section-header {
            align-items: flex-start !important;
            margin-bottom: 22px !important;
        }

        .section-title {
            color: #111827 !important;
            font-size: 1.35rem !important;
            letter-spacing: -0.035em !important;
        }

        .section-title .badge {
            background: rgba(var(--primary-color-rgb), 0.10) !important;
            color: var(--primary-color) !important;
            border: 1px solid rgba(var(--primary-color-rgb), 0.14) !important;
        }

        .filters-container {
            gap: 10px !important;
        }

        .search-input,
        .filter-select,
        .form-input,
        .form-select {
            border-radius: 14px !important;
            border: 1px solid #dbe3ef !important;
            background: #ffffff !important;
            color: #0f172a !important;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.035) !important;
        }

        .search-input:focus,
        .filter-select:focus,
        .form-input:focus,
        .form-select:focus {
            border-color: rgba(var(--primary-color-rgb), 0.55) !important;
            box-shadow: 0 0 0 4px rgba(var(--primary-color-rgb), 0.10) !important;
        }

        .search-icon,
        .program-destination i,
        .program-traveler i {
            color: var(--primary-color) !important;
        }

        .programs-grid {
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)) !important;
            gap: 20px !important;
        }

        .program-card {
            overflow: hidden !important;
        }

        .program-card::before {
            height: 3px !important;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color)) !important;
        }

        .program-card.readonly::before {
            background: linear-gradient(90deg, rgba(var(--primary-color-rgb), 0.34), rgba(var(--secondary-color-rgb), 0.24)) !important;
        }

        .program-image {
            height: 150px !important;
            background: linear-gradient(135deg, rgba(var(--primary-color-rgb), 0.92), rgba(var(--secondary-color-rgb), 0.88)) !important;
        }

        .program-image .placeholder {
            background: transparent !important;
        }

        .program-content {
            padding: 18px !important;
        }

        .program-title {
            color: #111827 !important;
            font-size: 1.08rem !important;
            font-weight: 800 !important;
            letter-spacing: -0.02em !important;
        }

        .program-destination,
        .program-traveler {
            color: #64748b !important;
            font-weight: 600 !important;
        }

        .program-details {
            background: #f8fafc !important;
            border: 1px solid #edf2f7 !important;
            border-radius: 16px !important;
            padding: 14px !important;
        }

        .detail-label {
            color: #64748b !important;
        }

        .detail-value {
            color: #111827 !important;
            font-weight: 800 !important;
        }

        .program-actions {
            flex-wrap: wrap !important;
        }

        .btn-primary-sm {
            background: var(--primary-color) !important;
            color: #ffffff !important;
        }

        .btn-outline-sm {
            background: #ffffff !important;
            border: 1px solid rgba(var(--primary-color-rgb), 0.16) !important;
            color: var(--primary-color) !important;
        }

        .btn-outline-sm:hover {
            background: rgba(var(--primary-color-rgb), 0.08) !important;
            color: var(--primary-color) !important;
        }

        .btn-danger-sm {
            background: #fff1f2 !important;
            color: #be123c !important;
            border: 1px solid #fecdd3 !important;
        }

        .btn-danger-sm:hover {
            background: #ffe4e6 !important;
            color: #9f1239 !important;
        }


        /*modal de tags*/
        /* Tag management */
        .tag-list {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
            margin-bottom: 8px;
        }

        .tag-mgr-chip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px 4px 12px;
            border-radius: 20px;
            font-size: 12.5px;
            font-weight: 600;
            cursor: default;
        }

        .tag-mgr-del {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: none;
            background: rgba(0, 0, 0, .12);
            color: inherit;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            line-height: 1;
            transition: background .15s;
        }

        .tag-mgr-del:hover {
            background: rgba(0, 0, 0, .22);
        }

        .tag-new-row {
            display: flex;
            gap: 8px;
        }

        .tag-new-inp {
            flex: 1;
            height: 36px;
            padding: 0 12px;
            border: 1px solid #e2e8f0;
            border-radius: 9px;
            font-size: 13px;
            color: #1e293b;
            background: #f8fafc;
            outline: none;
            font-family: inherit;
            transition: border-color .18s;
        }

        .tag-new-inp:focus {
            border-color: var(--pr);
            background: #fff;
        }

        .btn-add-tag {
            height: 38px;
            padding: 0 20px;
            border: none;
            border-radius: 9px;
            background: var(--primary-gradient);
            color: #fff;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            flex-shrink: 0;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(var(--primary-color-rgb), 0.3);
            transition: transform .15s ease, box-shadow .15s ease, opacity .15s ease;
        }

        .btn-add-tag:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(var(--primary-color-rgb), 0.4);
            opacity: .95;
        }

        .btn-add-tag:active {
            transform: translateY(0);
        }

        /* ─── Botón engranaje para abrir config ─── */
        .btn-config {
            height: 36px;
            width: 36px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #f8fafc;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            transition: all .18s;
            flex-shrink: 0;
        }

        .btn-config:hover {
            background: #fff;
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .btn-config svg {
            width: 16px;
            height: 16px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
        }

        /* ─── CONFIG MODAL (estados + tags) ─── */
        .cfg-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, .55);
            z-index: 1100;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }

        .cfg-overlay.open {
            display: flex;
        }

        .cfg-box {
            background: #fff;
            border-radius: 18px;
            width: min(540px, 96vw);
            max-height: 88vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 24px 60px rgba(0, 0, 0, .2);
            animation: mdIn .24s cubic-bezier(.4, 0, .2, 1);
        }

        .cfg-hd {
            padding: 18px 22px 14px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        }

        .cfg-title {
            font-size: 17px;
            font-weight: 700;
            color: #0f172a;
        }

        .cfg-body {
            overflow-y: auto;
            flex: 1;
            padding: 18px 22px;
        }

        .cfg-body::-webkit-scrollbar {
            width: 4px;
        }

        .cfg-body::-webkit-scrollbar-thumb {
            background: #e2e8f0;
            border-radius: 4px;
        }

        .cfg-sec-title {
            font-size: 11px;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: .6px;
            margin-bottom: 12px;
            margin-top: 20px;
        }

        .cfg-sec-title:first-child {
            margin-top: 0;
        }

        /* Tag chips in modal */
        .tag-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            min-height: 36px;
            align-items: center;
        }

        .tag-chip {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all .15s;
            opacity: .7;
        }

        .tag-chip.selected {
            opacity: 1;
            border-color: rgba(0, 0, 0, .2);
            box-shadow: 0 2px 6px rgba(0, 0, 0, .12);
        }

        .tag-chip:hover {
            opacity: 1;
            transform: scale(1.04);
        }

        .nl-ft {
            padding: 12px 22px 16px;
            border-top: 1px solid #f1f5f9;
            display: flex;
            gap: 9px;
            justify-content: flex-end;
            flex-shrink: 0;
        }

        .btn-cancel {
            height: 38px;
            padding: 0 18px;
            border: 1px solid #e2e8f0;
            border-radius: 9px;
            background: #fff;
            font-size: 13px;
            font-weight: 500;
            color: #475569;
            cursor: pointer;
            transition: background .18s;
        }

        .btn-cancel:hover {
            background: #f8fafc;
        }

        .btn-submit {
            height: 38px;
            padding: 0 22px;
            border: none;
            border-radius: 9px;
            background: var(--grad);
            color: #fff;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: opacity .18s;
        }

        .btn-submit:hover {
            opacity: .88;
        }

        .btn-submit:disabled {
            opacity: .6;
            cursor: not-allowed;
        }


        .modal-overlay {
            background: rgba(15, 23, 42, 0.45) !important;
            backdrop-filter: blur(8px) !important;
        }

        .modal {
            padding: 30px !important;
            max-width: 560px !important;
        }

        .modal-title {
            color: #111827 !important;
            font-weight: 850 !important;
            letter-spacing: -0.035em !important;
        }

        .modal-subtitle,
        .option-description,
        .state-description {
            color: #64748b !important;
        }

        .modal-option {
            border: 1px solid #e5e7eb !important;
            border-radius: 20px !important;
            background: #ffffff !important;
        }

        .modal-option:hover,
        .modal-option.selected {
            border-color: rgba(var(--primary-color-rgb), 0.24) !important;
            background: rgba(var(--primary-color-rgb), 0.055) !important;
        }

        .option-icon {
            border-radius: 16px !important;
            background: rgba(var(--primary-color-rgb), 0.10) !important;
            color: var(--primary-color) !important;
        }

        .modal-option.selected .option-icon {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)) !important;
            color: #ffffff !important;
        }

        .loading-state,
        .empty-state,
        .error-state {
            background: #f8fafc !important;
            border: 1px dashed #dbe3ef !important;
            border-radius: 22px !important;
            padding: 46px 20px !important;
        }

        .state-icon {
            color: var(--primary-color) !important;
            font-size: 2.8rem !important;
        }

        .toast {
            border-radius: 18px !important;
            background: #ffffff !important;
            color: #111827 !important;
            border: 1px solid #e5e7eb !important;
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.14) !important;
        }

        .toast.success {
            border-left: 4px solid var(--primary-color) !important;
        }

        .toast.error {
            border-left: 4px solid #ef4444 !important;
        }

        .toast-content {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .toast-icon {
            color: var(--primary-color);
            font-size: 18px;
        }

        .toast.error .toast-icon {
            color: #ef4444;
        }

        .overlay {
            background: rgba(15, 23, 42, 0.35) !important;
            backdrop-filter: blur(3px) !important;
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 20px !important;
            }

            .page-header {
                padding: 24px !important;
            }

            .stats-grid {
                grid-template-columns: 1fr !important;
            }

            .programs-section {
                padding: 20px !important;
            }

            .section-header {
                align-items: stretch !important;
            }
        }


        /* ===== Corrección de iconografía profesional sin FontAwesome ===== */
        .ts-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: currentColor;
            line-height: 1;
        }

        .ts-icon svg {
            width: 1em;
            height: 1em;
            display: block;
            fill: none;
            stroke: currentColor;
            stroke-width: 2.15;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .page-title .ts-icon,
        .section-title .ts-icon,
        .stat-icon.ts-icon {
            width: 44px !important;
            height: 44px !important;
            min-width: 44px !important;
            border-radius: 16px !important;
            background: rgba(var(--primary-color-rgb), 0.10) !important;
            color: var(--primary-color) !important;
            font-size: 20px !important;
            box-shadow: 0 10px 24px rgba(var(--primary-color-rgb), 0.12) !important;
        }

        .stat-card .stat-icon.ts-icon {
            grid-row: span 2;
            margin-bottom: 0 !important;
        }

        .action-btn .ts-icon,
        .modal-btn .ts-icon,
        .program-action .ts-icon,
        .filter-btn .ts-icon {
            width: 17px;
            height: 17px;
            font-size: 17px;
        }

        .search-icon.ts-icon {
            color: #94a3b8 !important;
        }

        .state-icon.ts-icon {
            width: 54px !important;
            height: 54px !important;
            margin-bottom: 14px !important;
            border-radius: 18px !important;
            background: rgba(var(--primary-color-rgb), 0.10) !important;
            color: var(--primary-color) !important;
            font-size: 25px !important;
        }

        .placeholder .ts-icon {
            color: var(--primary-color) !important;
            font-size: 30px !important;
            opacity: 0.82;
        }
    </style>
</head>

<body>
    <!-- Header con componentes -->
    <?= UIComponents::renderHeader($user) ?>

    <!-- Sidebar con componentes -->
    <?= UIComponents::renderSidebar($user, '/itinerarios') ?>

    <!-- Overlay -->
    <div class="overlay" id="overlay" onclick="closeSidebar()"></div>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Dashboard Header Unificado -->
        <div class="dashboard-header">
            <div class="dash-top">
                <div>
                    <h1 class="dash-title">Programas de Viaje</h1>
                    <p class="dash-subtitle">Gestiona y visualiza todos tus itinerarios</p>
                </div>
                <div class="dash-actions">
                    <button onclick="mostrarModalCreacion()" class="action-btn">
                        <i class="fas fa-plus"></i>
                        Nuevo Programa
                    </button>
                    <button onclick="cargarProgramas()" class="action-btn secondary">
                        <i class="fas fa-sync"></i>
                        Actualizar
                    </button>
                </div>
            </div>
            <div class="stats-row">
                <div class="stat-mini">
                    <div class="stat-mini-icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M20 21a8 8 0 0 0-16 0" />
                            <circle cx="12" cy="7" r="4" />
                        </svg>
                    </div>
                    <div>
                        <div class="stat-mini-num" id="misProgramas">0</div>
                        <div class="stat-mini-label">Mis Programas</div>
                    </div>
                </div>
                <div class="stat-mini">
                    <div class="stat-mini-icon slate">
                        <svg viewBox="0 0 24 24">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                            <circle cx="9" cy="7" r="4" />
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                        </svg>
                    </div>
                    <div>
                        <div class="stat-mini-num" id="otrosProgramas">0</div>
                        <div class="stat-mini-label">Otros Programas</div>
                    </div>
                </div>
                <div class="stat-mini">
                    <div class="stat-mini-icon amber">
                        <svg viewBox="0 0 24 24">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
                            <line x1="3" y1="9" x2="21" y2="9" />
                            <line x1="9" y1="3" x2="9" y2="21" />
                        </svg>
                    </div>
                    <div>
                        <div class="stat-mini-num" id="totalPlantillas">0</div>
                        <div class="stat-mini-label">Plantillas</div>
                    </div>
                </div>
                <div class="stat-mini">
                    <div class="stat-mini-icon">
                        <svg viewBox="0 0 24 24">
                            <path
                                d="M17.8 19.2 16 11l3.5-3.5c1-1 1.3-2.5.6-3.2s-2.2-.4-3.2.6L13.4 8.4 5.2 6.6 4 7.8l6.7 3.1-3.2 3.2-2.1-.4-.9.9 3.1 1.7 1.7 3.1.9-.9-.4-2.1 3.2-3.2 3.1 6.7z" />
                        </svg>
                    </div>
                    <div>
                        <div class="stat-mini-num" id="totalProgramas">0</div>
                        <div class="stat-mini-label">Total</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sección principal con tabs -->
        <div class="programs-section">
            <div class="tab-nav">
                <button class="tab-btn active" id="tabBtnMios" onclick="switchTab('mios')">
                    <svg viewBox="0 0 24 24">
                        <path d="M20 21a8 8 0 0 0-16 0" />
                        <circle cx="12" cy="7" r="4" />
                    </svg>
                    Mis Programas <span class="tab-count" id="tabCountMios">0</span>
                </button>
                <button class="tab-btn" id="tabBtnOtros" onclick="switchTab('otros')">
                    <svg viewBox="0 0 24 24">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                        <circle cx="9" cy="7" r="4" />
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                        <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                    </svg>
                    Otros Programas <span class="tab-count" id="tabCountOtros">0</span>
                </button>
                <button class="tab-btn" id="tabBtnPlantillas" onclick="switchTab('plantillas')">
                    <svg viewBox="0 0 24 24">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
                        <line x1="3" y1="9" x2="21" y2="9" />
                        <line x1="9" y1="3" x2="9" y2="21" />
                    </svg>
                    Plantillas <span class="tab-count" id="tabCountPlantillas">0</span>
                </button>
            </div>

            <!-- Barras de búsqueda por tab -->
            <div class="tab-search" id="tabSearchMios">
                <div class="search-box" style="flex:1;min-width:180px;max-width:320px;">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="searchInputMios" class="search-input" style="width:100%;"
                        placeholder="Buscar mis programas..." oninput="filtrarProgramas('mios')">
                </div>
                <select id="filterTagMios" class="filter-select filter-tag" onchange="filtrarProgramas('mios')">
                    <option value="">Todos los tags</option>
                </select>
                <!-- Boton para configurar tags -->
                <?php if ($isAdmin): ?>
                    <button class="btn-config" onclick="openConfig()" title="Configurar Tags">
                        <svg viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="3" />
                            <path
                                d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z" />
                        </svg>
                    </button>
                <?php endif; ?>
            </div>
            <!-- Config Modal (estados + tags) — admin only -->
            <?php if ($isAdmin): ?>
                <div class="cfg-overlay" id="cfgModal">
                    <div class="cfg-box">
                        <div class="cfg-hd">
                            <span class="cfg-title">Configurar Tags</span>
                            <button class="nl-close" onclick="closeConfig()"><svg viewBox="0 0 24 24">
                                    <line x1="18" y1="6" x2="6" y2="18" />
                                    <line x1="6" y1="6" x2="18" y2="18" />
                                </svg></button>
                        </div>
                        <div class="cfg-body">


                            <div class="cfg-sec-title">Tags</div>
                            <div class="tag-list" id="tagMgrList"></div>
                            <div class="tag-new-row">
                                <input class="tag-new-inp" id="tagNewInp" type="text" placeholder="Nombre del tag…"
                                    onkeydown="if(event.key==='Enter')addTag()">
                                <button class="btn-add-tag" onclick="addTag()">+ Agregar</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Picker de etiquetas de un tour (dueño o admin) -->
            <div class="cfg-overlay" id="tagPickerModal">
                <div class="cfg-box">
                    <div class="cfg-hd">
                        <span class="cfg-title">Etiquetas del tour</span>
                        <button class="nl-close" onclick="closeTagPicker()"><svg viewBox="0 0 24 24">
                                <line x1="18" y1="6" x2="6" y2="18" />
                                <line x1="6" y1="6" x2="18" y2="18" />
                            </svg></button>
                    </div>
                    <div class="cfg-body">
                        <p style="font-size:13px;color:#64748b;margin:0 0 14px;">Selecciona las etiquetas para este
                            tour.</p>
                        <div class="tag-chips" id="tagPickerChips"></div>
                        <div style="display:flex;justify-content:flex-end;margin-top:18px;">
                            <button class="btn-add-tag" onclick="guardarTagPicker()">Guardar</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-search" id="tabSearchOtros" style="display:none;">
                <div class="search-box" style="flex:1;min-width:180px;max-width:320px;">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="searchInputOtros" class="search-input" style="width:100%;"
                        placeholder="Buscar otros programas..." oninput="filtrarProgramas('otros')">
                </div>
                <select id="filterStatusOtros" class="filter-select" onchange="filtrarProgramas('otros')">
                    <option value="">Todos los estados</option>
                    <option value="borrador">Borrador</option>
                    <option value="activo">Activo</option>
                    <option value="completado">Completado</option>
                </select>
                <select id="filterAuthor" class="filter-select" onchange="filtrarProgramas('otros')">
                    <option value="">Todos los autores</option>
                </select>
                <select id="filterTagOtros" class="filter-select filter-tag" onchange="filtrarProgramas('otros')">
                    <option value="">Todos los tags</option>
                </select>
            </div>
            <div class="tab-search" id="tabSearchPlantillas" style="display:none;">
                <div class="search-box" style="flex:1;min-width:180px;max-width:320px;">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="searchInputPlantillas" class="search-input" style="width:100%;"
                        placeholder="Buscar plantillas..." oninput="filtrarProgramas('plantillas')">
                </div>
                <select id="filterTagPlantillas" class="filter-select filter-tag"
                    onchange="filtrarProgramas('plantillas')">
                    <option value="">Todos los tags</option>
                </select>
            </div>

            <!-- Paneles de tabs -->
            <div class="tab-panel active" id="tabMios">
                <div id="misProgramasContainer">
                    <div class="loading-state">
                        <i class="fas fa-spinner state-icon"></i>
                        <h3 class="state-title">Cargando mis programas...</h3>
                        <p class="state-description">Por favor espera</p>
                    </div>
                </div>
            </div>
            <div class="tab-panel" id="tabOtros">
                <div id="otrosProgramasContainer">
                    <div class="loading-state">
                        <i class="fas fa-spinner state-icon"></i>
                        <h3 class="state-title">Cargando otros programas...</h3>
                        <p class="state-description">Por favor espera</p>
                    </div>
                </div>
            </div>
            <div class="tab-panel" id="tabPlantillas">
                <div id="plantillasContainer">
                    <div class="loading-state">
                        <i class="fas fa-spinner state-icon"></i>
                        <h3 class="state-title">Cargando plantillas...</h3>
                        <p class="state-description">Por favor espera</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Creación -->
    <div class="modal-overlay" id="modalCreacion">
        <div class="modal lg">
            <div class="modal-header">
                <h2 class="modal-title" id="modalCreacionTitle">Nuevo Programa</h2>
                <p class="modal-subtitle" id="modalCreacionSubtitle">¿Cómo quieres crear el programa de viaje?</p>
            </div>

            <!-- Step 1: 3 opciones -->
            <div class="modal-options" id="modalStep1">
                <div class="modal-option" onclick="seleccionarOpcion('desde-cero')" id="opcion-desde-cero">
                    <div class="option-icon"><i class="fas fa-plus"></i></div>
                    <div class="option-content">
                        <div class="option-title">Crear desde cero</div>
                        <div class="option-description">Comienza un programa completamente nuevo</div>
                    </div>
                </div>
                <div class="modal-option" onclick="seleccionarOpcion('desde-plantilla')" id="opcion-desde-plantilla">
                    <div class="option-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706);"><i
                            class="fas fa-copy"></i></div>
                    <div class="option-content">
                        <div class="option-title">Crear desde plantilla</div>
                        <div class="option-description">Usa una plantilla guardada como punto de partida</div>
                    </div>
                </div>
                <div class="modal-option" onclick="seleccionarOpcion('desde-existente')" id="opcion-desde-existente">
                    <div class="option-icon"><i class="fas fa-copy"></i></div>
                    <div class="option-content">
                        <div class="option-title">Copiar programa existente</div>
                        <div class="option-description">Clona uno de tus programas o de un compañero como base</div>
                    </div>
                </div>
            </div>

            <!-- Picker de plantillas -->
            <div id="modalPickerPlantilla" style="display:none;margin-top:20px;">
                <div class="picker-search-wrap">
                    <svg viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8" />
                        <path d="m21 21-4.35-4.35" />
                    </svg>
                    <input type="text" class="picker-search" id="pickerSearchPlantilla"
                        placeholder="Buscar plantilla..." oninput="renderPickerCards('plantilla')">
                </div>
                <div class="picker-grid" id="pickerGridPlantilla"></div>
            </div>

            <!-- Picker de programas existentes -->
            <div id="modalPickerExistente" style="display:none;margin-top:20px;">
                <div class="picker-search-wrap">
                    <svg viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8" />
                        <path d="m21 21-4.35-4.35" />
                    </svg>
                    <input type="text" class="picker-search" id="pickerSearchExistente" placeholder="Buscar programa..."
                        oninput="renderPickerCards('existente')">
                </div>
                <div class="picker-tabs">
                    <button class="picker-tab active" onclick="setPickerTab('mios')" id="pickerTabMios">Mis
                        programas</button>
                    <button class="picker-tab" onclick="setPickerTab('otros')" id="pickerTabOtros">Otros
                        programas</button>
                </div>
                <div class="picker-grid" id="pickerGridExistente"></div>
            </div>

            <div class="modal-actions">
                <button class="modal-btn secondary" onclick="cerrarModalCreacion()">Cancelar</button>
                <button class="modal-btn primary" onclick="procederCreacion()" id="btnProceder"
                    disabled>Proceder</button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>


        /* Iconos internos TravelSoft: reemplazan FontAwesome para evitar cuadros vacíos */
        const TS_ICON_SVGS = {
            route: '<path d="M6 19c2.5 0 2.5-4 5-4s2.5 4 5 4 2.5-4 5-4"></path><circle cx="5" cy="5" r="2"></circle><circle cx="19" cy="5" r="2"></circle><path d="M5 7v3a4 4 0 0 0 4 4h6a4 4 0 0 0 4-4V7"></path>',
            plane: '<path d="M17.8 19.2 16 11l3.5-3.5c1-1 1.3-2.5.6-3.2s-2.2-.4-3.2.6L13.4 8.4 5.2 6.6 4 7.8l6.7 3.1-3.2 3.2-2.1-.4-.9.9 3.1 1.7 1.7 3.1.9-.9-.4-2.1 3.2-3.2 3.1 6.7z"></path>',
            userEdit: '<path d="M17 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9.5" cy="7" r="4"></circle><path d="M18.5 3.5a2.1 2.1 0 0 1 3 3L16 12l-4 1 1-4z"></path>',
            users: '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>',
            plus: '<path d="M12 5v14"></path><path d="M5 12h14"></path>',
            sync: '<path d="M21 12a9 9 0 0 0-15-6.7L3 8"></path><path d="M3 3v5h5"></path><path d="M3 12a9 9 0 0 0 15 6.7l3-2.7"></path><path d="M21 21v-5h-5"></path>',
            search: '<circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.35-4.35"></path>',
            spinner: '<path d="M21 12a9 9 0 1 1-6.2-8.56"></path>',
            copy: '<rect x="9" y="9" width="13" height="13" rx="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>',
            map: '<path d="M9 18l-6 3V6l6-3 6 3 6-3v15l-6 3-6-3z"></path><path d="M9 3v15"></path><path d="M15 6v15"></path>',
            pin: '<path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0z"></path><circle cx="12" cy="10" r="3"></circle>',
            user: '<path d="M20 21a8 8 0 0 0-16 0"></path><circle cx="12" cy="7" r="4"></circle>',
            eye: '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"></path><circle cx="12" cy="12" r="3"></circle>',
            trash: '<path d="M3 6h18"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path><path d="M10 11v6"></path><path d="M14 11v6"></path>',
            edit: '<path d="M12 20h9"></path><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"></path>',
            pdf: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path><path d="M8 13h1.5a1.5 1.5 0 0 1 0 3H8v-3z"></path><path d="M13 13v3"></path><path d="M16 13h2"></path><path d="M16 16h1.5"></path>',
            close: '<path d="M18 6 6 18"></path><path d="M6 6l12 12"></path>',
            info: '<circle cx="12" cy="12" r="10"></circle><path d="M12 16v-4"></path><path d="M12 8h.01"></path>',
            external: '<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><path d="M15 3h6v6"></path><path d="M10 14 21 3"></path>',
            warning: '<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"></path><path d="M12 9v4"></path><path d="M12 17h.01"></path>',
            redo: '<path d="M21 7v6h-6"></path><path d="M21 13a9 9 0 1 1-3-6.7L21 9"></path>',
            check: '<path d="M20 6 9 17l-5-5"></path>'
        };

        function tsIconNameFromClass(className) {
            const c = String(className || '');
            if (c.includes('fa-route')) return 'route';
            if (c.includes('fa-plane')) return 'plane';
            if (c.includes('fa-user-edit')) return 'userEdit';
            if (c.includes('fa-users')) return 'users';
            if (c.includes('fa-plus')) return 'plus';
            if (c.includes('fa-sync')) return 'sync';
            if (c.includes('fa-search')) return 'search';
            if (c.includes('fa-spinner')) return 'spinner';
            if (c.includes('fa-copy')) return 'copy';
            if (c.includes('fa-map-marked') || c.includes('fa-map')) return 'map';
            if (c.includes('fa-map-marker')) return 'pin';
            if (c.includes('fa-user')) return 'user';
            if (c.includes('fa-eye')) return 'eye';
            if (c.includes('fa-trash')) return 'trash';
            if (c.includes('fa-edit')) return 'edit';
            if (c.includes('fa-file-pdf')) return 'pdf';
            if (c.includes('fa-times')) return 'close';
            if (c.includes('fa-info-circle')) return 'info';
            if (c.includes('fa-external-link-alt')) return 'external';
            if (c.includes('fa-exclamation-triangle')) return 'warning';
            if (c.includes('fa-redo')) return 'redo';
            if (c.includes('fa-check')) return 'check';
            return 'route';
        }

        function replaceTsIcons(root = document) {
            root.querySelectorAll('i.fas, i.fa').forEach((node) => {
                if (node.dataset.tsIconReady === '1') return;
                const iconName = tsIconNameFromClass(node.className);
                const span = document.createElement('span');
                span.className = String(node.className).replace(/\bfas\b|\bfa\b|\bfa-[^\s]+\b|\bfa-spin\b/g, '').replace(/\s+/g, ' ').trim();
                span.className = (span.className ? span.className + ' ' : '') + 'ts-icon';
                span.setAttribute('aria-hidden', 'true');
                if (node.getAttribute('style')) span.setAttribute('style', node.getAttribute('style'));
                span.innerHTML = `<svg viewBox="0 0 24 24">${TS_ICON_SVGS[iconName] || TS_ICON_SVGS.route}</svg>`;
                node.replaceWith(span);
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            replaceTsIcons(document);
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === 1) replaceTsIcons(node);
                    });
                });
            });
            observer.observe(document.body, { childList: true, subtree: true });
        });
        // Configuración global
        const APP_URL = '<?= APP_URL ?>';
        const DEFAULT_LANGUAGE = '<?= $defaultLanguage ?>';
        const CURRENT_USER_ID = <?= $user['id'] ?>;
        const esAdmin = <?= json_encode($user['role'] === 'admin') ?>;

        // Estado global (tags de itinerario)
        const S = { tags: [] };

        // ── TAGS: paleta, helpers y API GET ──
        const TAG_PALETTE = ['#6366f1', '#3b82f6', '#14b8a6', '#f59e0b', '#f97316', '#22c55e', '#ef4444', '#8b5cf6', '#ec4899', '#0ea5e9'];
        function tagColor(id) { return TAG_PALETTE[((id - 1) % TAG_PALETTE.length + TAG_PALETTE.length) % TAG_PALETTE.length]; }
        function esc(s) { if (!s) return ''; return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); }

        async function api(action, params = {}) {
            try {
                let url = APP_URL + '/modules/itinerarios/tags_api.php?action=' + encodeURIComponent(action);
                const qs = Object.entries(params).map(([k, v]) => k + '=' + encodeURIComponent(v)).join('&');
                if (qs) url += '&' + qs;
                const r = await fetch(url);
                return await r.json();
            } catch (e) { return { success: false, message: 'Error de red' }; }
        }

        /**
         * Normaliza cualquier URL de imagen para que use siempre el APP_URL
         * del entorno actual (local o hosting), sin importar lo que esté en BD.
         * - Acepta: URL absoluta del hosting, ruta relativa con o sin /
         */
        function normalizarImagenUrl(url) {
            if (!url) return null;
            try {
                // Si tiene dominio (http/https), extraer solo el path
                if (url.startsWith('http://') || url.startsWith('https://')) {
                    const parsed = new URL(url);
                    url = parsed.pathname; // → /assets/uploads/...
                }
                // Reconstruir con APP_URL del entorno actual
                return APP_URL + (url.startsWith('/') ? url : '/' + url);
            } catch (e) {
                return APP_URL + '/' + url;
            }
        }

        let sidebarOpen = false;
        let allProgramas = [];
        let misProgramasFiltrados = [];
        let otrosProgramasFiltrados = [];
        let opcionSeleccionada = null;
        let plantillas = [];
        let activeTab = 'mios';
        let selectedPickerProgram = null;
        let pickerTabActiva = 'mios';

        window.crearDesdeCeroRapido = crearDesdeCeroRapido;

        // Inicializar al cargar la página
        document.addEventListener('DOMContentLoaded', function () {
            console.log('Iniciando página de itinerarios mejorada...');
            cargarProgramas();
            cargarTagsFiltro();
            initializeGoogleTranslate();
        });

        // Carga los tags de itinerario y rellena el selector de filtro
        async function cargarTagsFiltro() {
            const rT = await api('get_tags');
            S.tags = rT.success ? rT.data : [];
            fillSelects();
        }

        // Funciones de sidebar CORREGIDAS
        function toggleSidebar() {
            const sidebar = document.querySelector('.enhanced-sidebar');
            const overlay = document.getElementById('overlay');
            const mainContent = document.getElementById('mainContent');

            if (!sidebar) {
                console.error('Sidebar no encontrado con clase .enhanced-sidebar');
                return;
            }

            sidebarOpen = !sidebarOpen;

            if (sidebarOpen) {
                sidebar.classList.add('open');
                if (overlay) overlay.classList.add('show');
                if (mainContent && window.innerWidth > 768) {
                    mainContent.classList.add('sidebar-open');
                }
            } else {
                sidebar.classList.remove('open');
                if (overlay) overlay.classList.remove('show');
                if (mainContent) mainContent.classList.remove('sidebar-open');
            }
        }

        function closeSidebar() {
            if (sidebarOpen) {
                toggleSidebar();
            }
        }
        function crearDesdeCeroRapido() {
            if (event) event.stopPropagation();
            opcionSeleccionada = 'desde-cero';
            window.location.href = '<?= APP_URL ?>/programa';
        }
        function toggleUserMenu() {
            if (confirm('¿Desea cerrar sesión?')) {
                window.location.href = '<?= APP_URL ?>/auth/logout';
            }
        }

        // ============================================================
        // FUNCIONES DE CARGA DE DATOS
        // ============================================================

        async function cargarProgramas() {
            console.log('Cargando programas con imágenes...');

            showLoadingState('mios');
            showLoadingState('otros');
            showLoadingState('plantillas');

            try {
                // Cargar todos los programas (incluye user_id, full_name del creador E IMÁGENES)
                const response = await fetch('<?= APP_URL ?>/programa/api?action=list_all');

                if (!response.ok) {
                    throw new Error(`Error HTTP: ${response.status}`);
                }

                const result = await response.json();
                console.log('Datos recibidos:', result);

                if (result.success) {
                    allProgramas = result.data || [];

                    // Normalizar tags: convertir los GROUP_CONCAT en arrays
                    allProgramas.forEach(p => {
                        const ids = p.tag_ids ? p.tag_ids.split(',').map(Number) : [];
                        const nombres = p.tag_nombres ? p.tag_nombres.split('||') : [];
                        p.tagIds = ids;                                          // para filtrar
                        p.tags = ids.map((id, i) => ({ id, nombre: nombres[i] })); // para los chips
                    });

                    // Debug de imágenes
                    allProgramas.forEach(programa => {
                        if (programa.foto_portada) {
                            console.log(`Programa ${programa.id} tiene imagen: ${programa.foto_portada}`);
                        } else {
                            console.log(`Programa ${programa.id} sin imagen de portada`);
                        }
                    });

                    // Separar programas por propietario
                    separarProgramas();
                    actualizarEstadisticas();
                    mostrarProgramas();
                    cargarAutoresEnFiltro();

                    console.log(`${allProgramas.length} programas cargados (${misProgramasFiltrados.length} míos, ${otrosProgramasFiltrados.length} otros)`);
                } else {
                    throw new Error(result.error || 'Error al cargar programas');
                }

            } catch (error) {
                console.error('Error cargando programas:', error);
                showErrorState('mios', error.message);
                showErrorState('otros', error.message);
                showErrorState('plantillas', error.message);
            }
        }
        async function apiJ(action, body = {}) {
            try {
                const r = await fetch(APP_URL + '/modules/itinerarios/tags_api.php?action=' + encodeURIComponent(action), {
                    method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body)
                });
                return await r.json();
            } catch (e) { return { success: false, message: 'Error de red' }; }
        }
        // ── CONFIG MODAL (TAGS) ──
        async function openConfig() {
            document.getElementById('cfgModal')?.classList.add('open');
            const rT = await api('get_tags');
            S.tags = rT.success ? rT.data : S.tags;
            renderTagMgr();
        }
        function closeConfig() { document.getElementById('cfgModal')?.classList.remove('open'); }
        // Cerrar el modal al hacer clic en el fondo o con Escape
        document.getElementById('cfgModal')?.addEventListener('click', e => { if (e.target === e.currentTarget) closeConfig(); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeConfig(); });
        function renderTagMgr() {
            const el = document.getElementById('tagMgrList'); if (!el) return;
            if (!S.tags.length) { el.innerHTML = '<span style="font-size:12px;color:#94a3b8;">Sin tags aún</span>'; return; }
            el.innerHTML = S.tags.map(t => {
                const c = tagColor(t.id);
                return `<span class="tag-mgr-chip" style="background:${c}20;color:${c};">
            <span id="tag-lbl-${t.id}">${esc(t.nombre)}</span>
            <button class="tag-mgr-del" title="Renombrar" onclick="renameTagInline(${t.id})" style="background:rgba(0,0,0,.1);margin-right:2px;">✎</button>
            <button class="tag-mgr-del" onclick="deleteTag(${t.id})">✕</button>
        </span>`;
            }).join('');
        }
        async function addTag() {
            const inp = document.getElementById('tagNewInp');
            const nombre = inp?.value.trim();
            if (!nombre) return showToast('Escribe un nombre para el tag', 'err');
            const r = await apiJ('save_tags', { nombre });
            if (r.success) {
                inp.value = '';
                const rT = await api('get_tags');
                S.tags = rT.success ? rT.data : S.tags;
                renderTagMgr(); fillSelects();
                showToast(`Tag "${esc(nombre)}" creado`, 'ok');
            } else showToast(r.message || 'Error', 'err');
        }
        async function deleteTag(id) {
            const r = await apiJ('delete_tags', { id });
            if (r.success) {
                S.tags = S.tags.filter(t => t.id != id);
                renderTagMgr(); fillSelects();
                showToast('Tag eliminado', 'ok');
            } else showToast(r.message || 'Error', 'err');
        }

        // Renombrado inline de un tag (igual que pipeline, sin referencias a leads)
        function renameTagInline(id) {
            const tag = S.tags.find(t => t.id == id); if (!tag) return;
            const lbl = document.getElementById('tag-lbl-' + id); if (!lbl) return;
            const oldName = tag.nombre;
            const inp = document.createElement('input');
            inp.value = oldName;
            inp.style.cssText = 'border:none;background:transparent;color:inherit;font-size:12.5px;font-weight:600;width:80px;outline:none;font-family:inherit;';
            lbl.replaceWith(inp); inp.focus(); inp.select();
            async function save() {
                const newName = inp.value.trim();
                if (!newName || newName === oldName) { renderTagMgr(); return; }
                const r = await apiJ('update_tags', { id, nombre: newName });
                if (r.success) {
                    tag.nombre = newName;
                    fillSelects();
                    showToast(`Tag renombrado a "${esc(newName)}"`, 'ok');
                } else showToast(r.message || 'Error', 'err');
                renderTagMgr();
            }
            inp.addEventListener('blur', save);
            inp.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); save(); } if (e.key === 'Escape') renderTagMgr(); });
        }

        // Rellena todos los selectores de tags (uno por tab) conservando su valor actual
        function fillSelects() {
            const opciones = '<option value="">Todos los tags</option>' +
                S.tags.map(t => `<option value="${t.id}">${esc(t.nombre)}</option>`).join('');
            document.querySelectorAll('.filter-tag').forEach(sel => {
                const prev = sel.value;
                sel.innerHTML = opciones;
                sel.value = prev;
            });
        }

        // ============================================================
        // PICKER DE ETIQUETAS POR TOUR (asignación en la tarjeta)
        // ============================================================
        let tagPickerProgramaId = null;
        let tagPickerSeleccion = new Set();   // ids de tags marcados

        async function openTagPicker(programaId) {
            tagPickerProgramaId = programaId;

            // Asegurar que la lista global de tags esté cargada
            if (!S.tags.length) {
                const rT = await api('get_tags');
                S.tags = rT.success ? rT.data : [];
            }

            // Cargar los tags YA asignados a este tour 
            const asignados = await fetchTagsDePrograma(programaId);
            tagPickerSeleccion = new Set((asignados || []).map(t => Number(t.id)));

            renderTagPickerChips();
            document.getElementById('tagPickerModal')?.classList.add('open');
        }

        function closeTagPicker() {
            document.getElementById('tagPickerModal')?.classList.remove('open');
            tagPickerProgramaId = null;
        }

        function renderTagPickerChips() {
            const el = document.getElementById('tagPickerChips');
            if (!el) return;
            if (!S.tags.length) {
                el.innerHTML = '<span style="font-size:12px;color:#94a3b8;">No hay tags. Créalos en ⚙ Configurar.</span>';
                return;
            }
            el.innerHTML = S.tags.map(t => {
                const sel = tagPickerSeleccion.has(Number(t.id));
                const c = tagColor(t.id);
                return `<span class="tag-chip${sel ? ' selected' : ''}"
                    style="--c:${c};${sel ? `background:${c}20;color:${c};border-color:${c};` : ''}"
                    onclick="toggleTagPickerChip(${t.id})">${esc(t.nombre)}</span>`;
            }).join('');
        }

        function toggleTagPickerChip(id) {
            id = Number(id);
            if (tagPickerSeleccion.has(id)) tagPickerSeleccion.delete(id);
            else tagPickerSeleccion.add(id);
            renderTagPickerChips();
        }

        async function guardarTagPicker() {
            if (!tagPickerProgramaId) return;
            const tagIds = [...tagPickerSeleccion];

            // Guardar la asignación  
            const r = await guardarTagsDePrograma(tagPickerProgramaId, tagIds);

            if (r && r.success) {
                showToast('Etiquetas actualizadas', 'ok');
                closeTagPicker();
                cargarProgramas();   // refresca las tarjetas (mostrará los chips cuando el back los traiga)
            } else {
                showToast((r && r.message) || 'Error al guardar', 'err');
            }
        }

        // ─────────────────────────────────────────────────────────────
        // LLAMADAS AL API
        // Endpoints disponibles en modules/itinerarios/tags_api.php:
        //   GET  get_tags_programa?programa_id=ID     → { success, data: [{id,nombre}, ...] }
        //   POST save_tags_programa { solicitud_id, tag_ids: [..] }  → { success }
        // ─────────────────────────────────────────────────────────────
        async function fetchTagsDePrograma(programaId) {
            $result = await api("get_tags_programa", { programa_id: programaId });
            return $result.data;
        }
        async function guardarTagsDePrograma(programaId, tagIds) {
            $result = await apiJ('save_tags_programa', { solicitud_id: programaId, tag_id: tagIds })
            return { success: true, message: 'tags asignados con exito' };
        }
        // Cerrar el picker al hacer clic fuera o con Escape
        document.getElementById('tagPickerModal')?.addEventListener('click', e => { if (e.target === e.currentTarget) closeTagPicker(); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeTagPicker(); });
        function separarProgramas() {
            const normales = allProgramas.filter(p => !p.plantilla || p.plantilla == 0);
            misProgramasFiltrados = normales.filter(p => p.user_id == CURRENT_USER_ID);
            otrosProgramasFiltrados = normales.filter(p => p.user_id != CURRENT_USER_ID);
            plantillas = allProgramas.filter(p => p.plantilla == 1);
        }

        function cargarAutoresEnFiltro() {
            const autores = [...new Set(otrosProgramasFiltrados.map(p => p.created_by_name))]
                .filter(name => name)
                .sort();

            const filterAuthor = document.getElementById('filterAuthor');
            filterAuthor.innerHTML = '<option value="">Todos los autores</option>';

            autores.forEach(autor => {
                const option = document.createElement('option');
                option.value = autor;
                option.textContent = autor;
                filterAuthor.appendChild(option);
            });
        }
        // ── TOAST ── (usa el estilo propio de itinerarios: .toast .show .success/.error)
        function showToast(msg, type = 'ok', dur = 3500) {
            const clase = (type === 'err' || type === 'error') ? 'error' : 'success';
            const t = document.createElement('div');
            t.className = 'toast ' + clase;
            t.innerHTML = msg;
            document.body.appendChild(t);
            requestAnimationFrame(() => t.classList.add('show'));
            setTimeout(() => {
                t.classList.remove('show');
                setTimeout(() => t.remove(), 300);
            }, dur);
        }
        function showToastUndo(msg, fn, dur = 4500) {
            const t = document.createElement('div');
            t.className = 'toast success';
            t.innerHTML = msg;
            const btn = document.createElement('button');
            btn.className = 'toast-undo';
            btn.textContent = 'Deshacer';
            btn.style.cssText = 'margin-left:12px;background:rgba(255,255,255,.25);border:none;color:#fff;padding:4px 10px;border-radius:8px;cursor:pointer;font-weight:600;';
            t.appendChild(btn);
            document.body.appendChild(t);
            requestAnimationFrame(() => t.classList.add('show'));
            const cerrar = () => { t.classList.remove('show'); setTimeout(() => t.remove(), 300); };
            const timer = setTimeout(cerrar, dur);
            btn.onclick = () => { clearTimeout(timer); cerrar(); fn(); };
        }
        // ============================================================
        // FUNCIONES DE VISUALIZACIÓN
        // ============================================================

        function mostrarProgramas() {
            mostrarProgramasSeccion('mios', misProgramasFiltrados);
            mostrarProgramasSeccion('otros', otrosProgramasFiltrados);
            mostrarProgramasSeccion('plantillas', plantillas);
        }

        function mostrarProgramasSeccion(tipo, programas) {
            const containerMap = { mios: 'misProgramasContainer', otros: 'otrosProgramasContainer', plantillas: 'plantillasContainer' };
            const containerId = containerMap[tipo] || 'misProgramasContainer';
            const container = document.getElementById(containerId);
            if (!container) return;

            if (!programas || programas.length === 0) {
                showEmptyState(tipo);
                return;
            }

            const programsGrid = document.createElement('div');
            programsGrid.className = 'programs-grid';

            programas.forEach(programa => {
                const esPlantilla = tipo === 'plantillas';
                const esReadonly = tipo === 'otros';
                const card = crearTarjetaPrograma(programa, esReadonly, esPlantilla);
                programsGrid.appendChild(card);
            });

            container.innerHTML = '';
            container.appendChild(programsGrid);
        }

        function crearTarjetaPrograma(programa, esReadonly = false, esPlantilla = false) {
            const card = document.createElement('div');
            card.className = `program-card ${esReadonly ? 'readonly' : ''} ${esPlantilla ? 'es-plantilla' : ''}`;

            if (!esReadonly && !esPlantilla) {
                card.onclick = () => editarPrograma(programa.id);
            }

            // Solo el dueño (o un admin) puede modificar/quitar una plantilla.
            // Un agente que ve una plantilla de otro solo puede usarla.
            const esPropietario = programa.user_id == CURRENT_USER_ID;
            const puedeModificarPlantilla = esAdmin || esPropietario;

            // Calcular duración
            let duracion = 'N/A';

            // Primero intentar con total_dias_real (más confiable)
            if (programa.total_dias_real && parseInt(programa.total_dias_real) > 0) {
                const dias = parseInt(programa.total_dias_real);
                duracion = `${dias} ${dias === 1 ? 'día' : 'días'}`;
            } else if (programa.fecha_llegada && programa.fecha_salida) {
                const llegada = new Date(programa.fecha_llegada + 'T00:00:00');
                const salida = new Date(programa.fecha_salida + 'T00:00:00');
                const dias = Math.round((salida - llegada) / (1000 * 60 * 60 * 24));
                duracion = dias > 0 ? `${dias} ${dias === 1 ? 'día' : 'días'}` : '1 día';
            }

            // OBTENER IMAGEN DE PORTADA DESDE LA BASE DE DATOS
            // Normalizar ruta: siempre usar APP_URL local sin importar
            // si la BD tiene URL del hosting o ruta relativa
            const rawPortada = programa.foto_portada || null;
            const imagenPortada = rawPortada ? normalizarImagenUrl(rawPortada) : null;
            const autorPrograma = programa.created_by_name || 'Usuario';

            // Validar que la imagen existe y es una URL válida
            const tieneImagen = !!imagenPortada;

            console.log(`Programa ${programa.id}: imagen = ${imagenPortada}, válida = ${tieneImagen}`);

            const bookmarkBtn = (esAdmin && !esReadonly && !esPlantilla) ?
                `<button class="btn-bookmark" title="Guardar como plantilla" onclick="event.stopPropagation();togglePlantilla(${programa.id})">
                    <svg viewBox="0 0 24 24"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
                </button>` : '';
            const plantillaPill = esPlantilla ? `<div class="plantilla-pill">Plantilla</div>` : '';

            // Botón para etiquetar (solo dueño o admin, y no en plantillas)
            const puedeEtiquetar = (esAdmin || esPropietario) && !esPlantilla;
            const tagBtn = puedeEtiquetar ?
                `<button class="btn-tag-card" title="Etiquetas" onclick="event.stopPropagation();openTagPicker(${programa.id})">
                    <i class="fas fa-tags"></i>
                </button>` : '';

            // Chips de etiquetas asignadas (requiere que el programa traiga programa.tags)
            const tagsCard = (Array.isArray(programa.tags) && programa.tags.length) ?
                `<div class="card-tags">${programa.tags.map(t => {
                    const c = tagColor(t.id);
                    return `<span class="c-tag" style="background:${c}1f;color:${c};">${esc(t.nombre)}</span>`;
                }).join('')}</div>` : '';

            card.innerHTML = `
                <div class="program-image">
                    ${tieneImagen ?
                    `<img src="${imagenPortada}" alt="Portada del programa" onerror="this.outerHTML='<div class=&quot;placeholder&quot;><i class=&quot;fas fa-map-marked-alt&quot;></i></div>';">` :
                    `<div class="placeholder"><i class="fas fa-map-marked-alt"></i></div>`
                }
                    ${bookmarkBtn}
                    ${tagBtn}
                    ${plantillaPill}
                    ${esReadonly ? `<div class="program-owner">${autorPrograma}</div>` : ''}
                </div>

                <div class="program-content">
                    <div class="program-header">
                        <h3 class="program-title">
                            ${programa.titulo_programa || `Viaje a ${programa.destino}`}
                        </h3>
                        ${tagsCard}
                        <div class="program-destination">
                            <i class="fas fa-map-marker-alt"></i>
                            ${programa.destino}
                        </div>
                        <div class="program-traveler">
                            <i class="fas fa-user"></i>
                            ${programa.nombre} ${programa.apellido}
                        </div>
                    </div>

                    <div class="program-details">
                        <div class="detail-item">
                            <div class="detail-label">Duración</div>
                            <div class="detail-value highlight">${duracion}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Viajeros</div>
                            <div class="detail-value">${programa.numero_pasajeros}</div>
                        </div>
                    </div>

                    <div class="program-actions">
                        ${esPlantilla ? `
                            <!-- PLANTILLAS (usar siempre; editar: dueño o admin; quitar: solo admin) -->
                            <button onclick="event.stopPropagation();usarPlantilla(${programa.id})" class="btn-sm btn-primary-sm">
                                <i class="fas fa-copy"></i> Usar plantilla
                            </button>
                            ${puedeModificarPlantilla ? `
                                <a href="<?= APP_URL ?>/programa?id=${programa.id}" class="btn-sm btn-outline-sm" onclick="event.stopPropagation()">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                            ` : ''}
                            ${esAdmin ? `
                                <button onclick="event.stopPropagation();togglePlantilla(${programa.id})" class="btn-sm" style="background:#f1f5f9;color:#64748b;border:none;border-radius:20px;padding:8px 12px;font-size:.8rem;cursor:pointer;">
                                    <i class="fas fa-times"></i> Quitar
                                </button>
                            ` : ''}
                        ` : esReadonly ? `
                            <!-- PROGRAMAS DE OTROS USUARIOS (solo ver y copiar) -->
                            <button onclick="event.stopPropagation(); verDetalles(${programa.id})" class="btn-sm btn-outline-sm">
                                <i class="fas fa-eye"></i>
                                Ver
                            </button>

                            <button onclick="event.stopPropagation(); copiarPrograma(${programa.id})" class="btn-sm btn-primary-sm">
                                <i class="fas fa-copy"></i>
                                Copiar
                            </button>
                            ${esAdmin ? `
                                <!-- Solo admins pueden eliminar programas de otros -->
                                <button onclick="event.stopPropagation(); confirmarEliminacion(${programa.id}, '${(programa.titulo_programa || `Viaje a ${programa.destino}`).replace(/'/g, "\\\'")}')" class="btn-sm btn-danger-sm" title="Eliminar programa">
                                    <i class="fas fa-trash"></i>
                                    Eliminar
                                </button>
                            ` : ''}
                        ` : `
                            <!-- MIS PROGRAMAS (editar, ver y eliminar) -->
                            <a href="<?= APP_URL ?>/programa?id=${programa.id}" class="btn-sm btn-primary-sm">
                                <i class="fas fa-edit"></i>
                                Editar
                            </a>
                            <button onclick="event.stopPropagation(); verDetalles(${programa.id})" class="btn-sm btn-outline-sm">
                                <i class="fas fa-eye"></i>
                                Ver
                            </button>

                            <button onclick="event.stopPropagation(); confirmarEliminacion(${programa.id}, '${(programa.titulo_programa || `Viaje a ${programa.destino}`).replace(/'/g, "\\\'")}')" class="btn-sm btn-danger-sm" title="Eliminar mi programa">
                                <i class="fas fa-trash"></i>
                                Eliminar
                            </button>
                        `}
                    </div>
                </div>
            `;

            return card;
        }

        // ── TABS ──
        function switchTab(tipo) {
            activeTab = tipo;
            ['mios', 'otros', 'plantillas'].forEach(t => {
                const capitalized = t.charAt(0).toUpperCase() + t.slice(1);
                const btn = document.getElementById('tabBtn' + capitalized);
                const panel = document.getElementById('tab' + capitalized);
                const search = document.getElementById('tabSearch' + capitalized);
                if (btn) btn.classList.toggle('active', t === tipo);
                if (panel) panel.classList.toggle('active', t === tipo);
                if (search) search.style.display = t === tipo ? 'flex' : 'none';
            });
        }

        // ── PLANTILLA TOGGLE ──
        async function togglePlantilla(programaId) {
            try {
                const fd = new FormData();
                fd.append('action', 'toggle_plantilla');
                fd.append('programa_id', programaId);
                const r = await fetch(APP_URL + '/programa/api', { method: 'POST', body: fd }).then(x => x.json());
                if (r.success) {
                    await cargarProgramas();
                    showNotification(r.plantilla ? 'Guardado como plantilla' : 'Quitado de plantillas', 'success');
                    if (r.plantilla) switchTab('plantillas');
                } else {
                    showNotification(r.error || 'Error', 'error');
                }
            } catch (e) {
                showNotification('Error de red', 'error');
            }
        }

        // ── USAR PLANTILLA ──
        async function usarPlantilla(programaId) {
            try {
                const fd = new FormData();
                fd.append('action', 'duplicate_programa');
                fd.append('programa_id', programaId);
                showNotification('Creando programa desde plantilla...', 'info');
                const r = await fetch(APP_URL + '/programa/api', { method: 'POST', body: fd }).then(x => x.json());
                if (r.success) {
                    await cargarProgramas();
                    showNotification('Programa creado. Redirigiendo...', 'success');
                    setTimeout(() => { window.location.href = APP_URL + '/programa?id=' + r.new_programa_id; }, 1200);
                } else {
                    showNotification(r.error || 'Error al crear', 'error');
                }
            } catch (e) {
                showNotification('Error de red', 'error');
            }
        }

        // FUNCIONES DE ELIMINACIÓN
        async function confirmarEliminacion(programaId, programaTitulo) {
            const confirmed = await showConfirmModal({
                title: '¿Eliminar programa?',
                message: `¿Estás seguro de eliminar el programa "${programaTitulo}"?`,
                details: 'Esta acción eliminará TODA la información: días del itinerario, servicios, precios y todo el programa. Esto NO se puede deshacer.',
                icon: 'delete',
                confirmText: 'Eliminar programa',
                cancelText: 'Cancelar'
            });

            if (confirmed) {
                eliminarPrograma(programaId);
            }
        }

        async function eliminarPrograma(programaId) {
            try {
                const formData = new FormData();
                formData.append('action', esAdmin ? 'delete_programa_admin' : 'delete_programa'); // CAMBIO
                formData.append('programa_id', programaId);

                const response = await fetch('<?= APP_URL ?>/programa/api', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showNotification('Programa eliminado exitosamente', 'success');
                    cargarProgramas();
                } else {
                    showNotification('Error: ' + result.error, 'error');
                }

            } catch (error) {
                showNotification('Error eliminando programa: ' + error.message, 'error');
            }
        }

        // ============================================================
        // FUNCIONES DE ESTADÍSTICAS
        // ============================================================

        function actualizarEstadisticas() {
            const totalNormales = misProgramasFiltrados.length + otrosProgramasFiltrados.length;
            animateCounter('totalProgramas', totalNormales);
            animateCounter('misProgramas', misProgramasFiltrados.length);
            animateCounter('otrosProgramas', otrosProgramasFiltrados.length);
            animateCounter('totalPlantillas', plantillas.length);

            const tcm = document.getElementById('tabCountMios');
            const tco = document.getElementById('tabCountOtros');
            const tcp = document.getElementById('tabCountPlantillas');
            if (tcm) tcm.textContent = misProgramasFiltrados.length;
            if (tco) tco.textContent = otrosProgramasFiltrados.length;
            if (tcp) tcp.textContent = plantillas.length;
        }

        function animateCounter(elementId, targetValue) {
            const element = document.getElementById(elementId);
            const startValue = 0;
            const duration = 1000;
            const increment = targetValue / (duration / 16);

            let currentValue = startValue;
            const timer = setInterval(() => {
                currentValue += increment;
                if (currentValue >= targetValue) {
                    currentValue = targetValue;
                    clearInterval(timer);
                }
                element.textContent = Math.floor(currentValue);
            }, 16);
        }

        // ============================================================
        // FUNCIONES DE FILTRADO Y BÚSQUEDA
        // ============================================================

        function filtrarProgramas(tipo) {
            if (tipo === 'mios') {
                const searchTerm = document.getElementById('searchInputMios').value.toLowerCase().trim();


                const programasBase = allProgramas.filter(p => p.user_id == CURRENT_USER_ID && (!p.plantilla || p.plantilla == 0));
                const tagId = document.getElementById('filterTagMios').value;


                misProgramasFiltrados = programasBase.filter(programa => {
                    // Filtro por tag
                    if (tagId && !(programa.tagIds || []).includes(Number(tagId))) return false;
                    if (!searchTerm) return true;
                    const search_term = searchTerm.split(" ").filter(term => term !== '');


                    const searchFields = [
                        programa.destino,
                        programa.nombre,
                        programa.apellido,
                        programa.numero_documento, // Documento del titular
                        programa.titulo_programa,
                        programa.id_solicitud,
                        `${programa.nombre} ${programa.apellido}`, // Nombre completo del titular
                        `Viaje a ${programa.destino}` // Título por defecto
                    ];


                    // Cada palabra debe coincidir con algún campo (orden indistinto)
                    return search_term.every(term =>
                        searchFields.some(field =>
                            field && field.toString().toLowerCase().includes(term)
                        )
                    );
                });


                mostrarProgramasSeccion('mios', misProgramasFiltrados);
                actualizarPlaceholderBusqueda('mios');
            } else if (tipo === 'otros') {
                const searchTerm = document.getElementById('searchInputOtros').value.toLowerCase().trim();
                const authorFilter = document.getElementById('filterAuthor').value;
                const tagId = document.getElementById('filterTagOtros').value;


                const programasBase = allProgramas.filter(p => p.user_id != CURRENT_USER_ID && (!p.plantilla || p.plantilla == 0));


                otrosProgramasFiltrados = programasBase.filter(programa => {
                    const search_term = searchTerm.split(" ").filter(term => term !== '');
                    // Filtro de búsqueda
                    const matchesSearch = !searchTerm || (() => {
                        const searchFields = [
                            programa.destino,
                            programa.nombre,
                            programa.apellido,
                            programa.numero_documento, // Documento del titular
                            programa.titulo_programa,
                            programa.id_solicitud,
                            programa.created_by_name,
                            `${programa.nombre} ${programa.apellido}`,
                            `Viaje a ${programa.destino}`
                        ];


                        return search_term.every(term =>
                            searchFields.some(field =>
                                field && field.toString().toLowerCase().includes(term)
                            )
                        );
                    })();


                    // Filtro de autor
                    const matchesAuthor = !authorFilter || programa.created_by_name === authorFilter;

                    // Filtro por tag
                    const matchesTag = !tagId || (programa.tagIds || []).includes(Number(tagId));


                    return matchesSearch && matchesAuthor && matchesTag;
                });


                mostrarProgramasSeccion('otros', otrosProgramasFiltrados);
                actualizarPlaceholderBusqueda('otros');
            } else if (tipo === 'plantillas') {
                const searchTerm = document.getElementById('searchInputPlantillas').value.toLowerCase().trim();


                const programasBase = allProgramas.filter(p => p.plantilla == 1);
                const tagId = document.getElementById('filterTagPlantillas').value;


                plantillas = programasBase.filter(programa => {
                    // Filtro por tag
                    if (tagId && !(programa.tagIds || []).includes(Number(tagId))) return false;
                    if (!searchTerm) return true;
                    const search_term = searchTerm.split(" ").filter(term => term !== '');


                    const searchFields = [
                        programa.destino,
                        programa.nombre,
                        programa.apellido,
                        programa.numero_documento, // Documento del titular
                        programa.titulo_programa,
                        programa.id_solicitud,
                        `${programa.nombre} ${programa.apellido}`, // Nombre completo del titular
                        `Viaje a ${programa.destino}` // Título por defecto
                    ];


                    // Cada palabra debe coincidir con algún campo (orden indistinto)
                    return search_term.every(term =>
                        searchFields.some(field =>
                            field && field.toString().toLowerCase().includes(term)
                        )
                    );
                });


                mostrarProgramasSeccion('plantillas', plantillas);
            }


            actualizarEstadisticas();


            let countLog = 0;
            if (tipo === 'mios') countLog = misProgramasFiltrados.length;
            else if (tipo === 'otros') countLog = otrosProgramasFiltrados.length;
            else if (tipo === 'plantillas') countLog = plantillas.length;


            console.log(`Filtrado ${tipo}: ${countLog} programas`);
        }


        function actualizarPlaceholderBusqueda(tipo) {
            const inputId = tipo === 'mios' ? 'searchInputMios' : 'searchInputOtros';
            const input = document.getElementById(inputId);
            const programas = tipo === 'mios' ? misProgramasFiltrados : otrosProgramasFiltrados;
            const searchTerm = input.value.trim();

            if (searchTerm) {
                input.setAttribute('data-results', `${programas.length} resultados`);
            } else {
                input.removeAttribute('data-results');
            }
        }

        // ============================================================
        // FUNCIONES DEL MODAL DE CREACIÓN
        // ============================================================

        function mostrarModalCreacion() {
            document.getElementById('modalCreacion').classList.add('show');
            opcionSeleccionada = null;
            selectedPickerProgram = null;
            pickerTabActiva = 'mios';
            ['desde-cero', 'desde-plantilla', 'desde-existente'].forEach(o => {
                const el = document.getElementById('opcion-' + o);
                if (el) el.classList.remove('selected');
            });
            document.getElementById('modalPickerPlantilla').style.display = 'none';
            document.getElementById('modalPickerExistente').style.display = 'none';
            document.getElementById('modalStep1').style.display = 'flex';
            document.getElementById('btnProceder').disabled = true;
        }

        function cerrarModalCreacion() {
            document.getElementById('modalCreacion').classList.remove('show');
        }

        function seleccionarOpcion(opcion) {
            opcionSeleccionada = opcion;
            selectedPickerProgram = null;
            ['desde-cero', 'desde-plantilla', 'desde-existente'].forEach(o => {
                const el = document.getElementById('opcion-' + o);
                if (el) el.classList.remove('selected');
            });
            const selEl = document.getElementById('opcion-' + opcion);
            if (selEl) selEl.classList.add('selected');
            document.getElementById('modalPickerPlantilla').style.display = 'none';
            document.getElementById('modalPickerExistente').style.display = 'none';

            if (opcion === 'desde-cero') {
                document.getElementById('btnProceder').disabled = false;
            } else if (opcion === 'desde-plantilla') {
                document.getElementById('modalPickerPlantilla').style.display = 'block';
                if (document.getElementById('pickerSearchPlantilla')) document.getElementById('pickerSearchPlantilla').value = '';
                renderPickerCards('plantilla');
                document.getElementById('btnProceder').disabled = true;
            } else if (opcion === 'desde-existente') {
                document.getElementById('modalPickerExistente').style.display = 'block';
                pickerTabActiva = 'mios';
                document.getElementById('pickerTabMios').classList.add('active');
                document.getElementById('pickerTabOtros').classList.remove('active');
                if (document.getElementById('pickerSearchExistente')) document.getElementById('pickerSearchExistente').value = '';
                renderPickerCards('existente');
                document.getElementById('btnProceder').disabled = true;
            }
        }

        function setPickerTab(tab) {
            pickerTabActiva = tab;
            document.getElementById('pickerTabMios').classList.toggle('active', tab === 'mios');
            document.getElementById('pickerTabOtros').classList.toggle('active', tab === 'otros');
            selectedPickerProgram = null;
            document.getElementById('btnProceder').disabled = true;
            renderPickerCards('existente');
        }

        function renderPickerCards(tipo) {
            let programas = [];
            let gridId = '';
            let searchId = '';

            if (tipo === 'plantilla') {
                programas = plantillas;
                gridId = 'pickerGridPlantilla';
                searchId = 'pickerSearchPlantilla';
            } else {
                programas = pickerTabActiva === 'mios' ? misProgramasFiltrados : otrosProgramasFiltrados;
                gridId = 'pickerGridExistente';
                searchId = 'pickerSearchExistente';
            }

            const searchInput = document.getElementById(searchId);
            const searchTerm = (searchInput ? searchInput.value : '').toLowerCase().trim();
            const grid = document.getElementById(gridId);
            if (!grid) return;

            let filtered = programas;
            if (searchTerm) {
                filtered = programas.filter(p => {
                    const fields = [p.titulo_programa, p.destino, p.nombre, p.apellido, 'Viaje a ' + p.destino];
                    return fields.some(f => f && f.toString().toLowerCase().includes(searchTerm));
                });
            }

            if (!filtered.length) {
                grid.innerHTML = '<div class="picker-empty">No se encontraron programas</div>';
                return;
            }

            grid.innerHTML = filtered.map(p => {
                const titulo = p.titulo_programa || ('Viaje a ' + p.destino);
                const isSelected = selectedPickerProgram && selectedPickerProgram.id == p.id;
                const dias = p.total_dias_real ? p.total_dias_real + ' días' : '';
                const fecha = p.fecha_salida ? formatDate(p.fecha_salida) : '';
                const meta = [p.destino, dias, fecha].filter(Boolean).join(' · ');
                return `<div class="picker-card${isSelected ? ' selected' : ''}" onclick="selectPickerProgram(${p.id},'${tipo}')">
                    <div class="picker-card-title">${titulo}</div>
                    <div class="picker-card-meta">${meta}</div>
                </div>`;
            }).join('');
        }

        function selectPickerProgram(id, tipo) {
            let prog;
            if (tipo === 'plantilla') {
                prog = plantillas.find(p => p.id == id);
            } else {
                const pool = pickerTabActiva === 'mios' ? misProgramasFiltrados : otrosProgramasFiltrados;
                prog = pool.find(p => p.id == id);
            }
            if (!prog) return;
            selectedPickerProgram = prog;
            document.getElementById('btnProceder').disabled = false;
            renderPickerCards(tipo);
        }

        async function procederCreacion() {
            if (!opcionSeleccionada) {
                showNotification('Por favor selecciona una opción', 'error');
                return;
            }
            if (opcionSeleccionada === 'desde-cero') {
                window.location.href = APP_URL + '/programa';
                return;
            }
            if (!selectedPickerProgram) {
                showNotification('Por favor selecciona un programa', 'error');
                return;
            }
            const btn = document.getElementById('btnProceder');
            btn.disabled = true;
            btn.textContent = 'Creando...';
            try {
                const fd = new FormData();
                fd.append('action', 'duplicate_programa');
                fd.append('programa_id', selectedPickerProgram.id);
                const r = await fetch(APP_URL + '/programa/api', { method: 'POST', body: fd }).then(x => x.json());
                if (r.success) {
                    cerrarModalCreacion();
                    showNotification('Programa creado exitosamente', 'success');
                    await cargarProgramas();
                    setTimeout(() => { window.location.href = APP_URL + '/programa?id=' + r.new_programa_id; }, 1200);
                } else {
                    throw new Error(r.error || 'Error al crear');
                }
            } catch (e) {
                showNotification('Error: ' + e.message, 'error');
                btn.disabled = false;
                btn.textContent = 'Proceder';
            }
        }



        // ============================================================
        // FUNCIONES DE INTERACCIÓN
        // ============================================================

        function editarPrograma(id) {
            console.log(`Editando programa ${id}`);
            window.location.href = `<?= APP_URL ?>/programa?id=${id}`;
        }

        function verDetalles(id) {
            console.log(`Viendo detalles del programa ${id}`);

            // Abrir en nueva ventana la página de itinerario (preview)
            const url = `<?= APP_URL ?>/itinerary?id=${id}`;

            // Abrir en nueva pestaña con características específicas
            const ventana = window.open(
                url,
                `programa_preview_${id}`,
                'width=1200,height=800,scrollbars=yes,resizable=yes,toolbar=no,menubar=no,status=no'
            );

            // Verificar si se abrió correctamente
            if (!ventana) {
                // Si hay bloqueador de popups, mostrar alternativa
                showNotification('Por favor, permite ventanas emergentes y vuelve a intentar', 'info');

                // Como alternativa, redirigir en la misma ventana
                setTimeout(() => {
                    if (confirm('¿Quieres ver el programa en esta ventana?')) {
                        window.location.href = url;
                    }
                }, 2000);
            } else {
                // Enfocar la nueva ventana
                ventana.focus();

                // Mostrar mensaje de éxito
                showNotification('Abriendo vista previa del programa...', 'success');
            }
        }

        // ============================================================
        // FUNCIÓN ALTERNATIVA PARA MODAL (OPCIONAL)
        // ============================================================

        function verDetallesModal(id) {
            console.log(`Viendo detalles del programa ${id} en modal`);

            // Crear modal con iframe para mostrar el itinerario
            const modalHtml = `
        <div class="modal-overlay" id="modalVistaPrevia" style="display: block; z-index: 2000;">
            <div class="modal" style="max-width: 95vw; width: 1200px; height: 90vh; padding: 0; border-radius: 12px; overflow: hidden;">
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; background: var(--primary-gradient); color: white;">
                    <h3 style="margin: 0; font-size: 1.2rem;">
                        <i class="fas fa-eye"></i>
                        Vista Previa del Programa
                    </h3>
                    <button onclick="cerrarModalVistaPrevia()" style="background: rgba(255,255,255,0.2); border: none; color: white; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div style="position: relative; height: calc(100% - 64px); background: #f8fafc;">
                    <iframe 
                        src="<?= APP_URL ?>/itinerary?id=${id}&embed=1" 
                        style="width: 100%; height: 100%; border: none; background: white;"
                        onload="this.style.background='white'"
                    ></iframe>
                    
                    <div id="loadingPreview" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; color: #718096;">
                        <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 16px; color: var(--primary-color);"></i>
                        <p>Cargando vista previa...</p>
                    </div>
                </div>
                
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 20px; background: #f8fafc; border-top: 1px solid #e2e8f0;">
                    <div style="font-size: 0.9rem; color: #718096;">
                        <i class="fas fa-info-circle"></i>
                        Vista previa del programa - Solo lectura
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <button onclick="abrirEnNuevaVentana(${id})" class="btn-sm btn-outline-sm">
                            <i class="fas fa-external-link-alt"></i>
                            Abrir en nueva ventana
                        </button>
                        <button onclick="cerrarModalVistaPrevia()" class="btn-sm btn-primary-sm">
                            <i class="fas fa-times"></i>
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

            // Agregar al DOM
            document.body.insertAdjacentHTML('beforeend', modalHtml);

            // Ocultar loading después de un tiempo
            setTimeout(() => {
                const loading = document.getElementById('loadingPreview');
                if (loading) {
                    loading.style.display = 'none';
                }
            }, 2000);
        }

        function cerrarModalVistaPrevia() {
            const modal = document.getElementById('modalVistaPrevia');
            if (modal) {
                modal.remove();
            }
        }

        function abrirEnNuevaVentana(id) {
            const url = `<?= APP_URL ?>/itinerary?id=${id}`;
            window.open(url, `programa_preview_${id}`, 'width=1200,height=800,scrollbars=yes,resizable=yes');
            cerrarModalVistaPrevia();
        }

        function copiarPrograma(id) {
            console.log(`Copiando programa ${id}`);
            window.location.href = `<?= APP_URL ?>/programa?copy_from=${id}`;
        }

        // ============================================================
        // ESTADOS DE LA INTERFAZ
        // ============================================================

        function showLoadingState(tipo) {
            const containerMap = { mios: 'misProgramasContainer', otros: 'otrosProgramasContainer', plantillas: 'plantillasContainer' };
            const el = document.getElementById(containerMap[tipo]);
            if (!el) return;
            const label = tipo === 'mios' ? 'mis programas' : tipo === 'otros' ? 'otros programas' : 'plantillas';
            el.innerHTML = `<div class="loading-state"><i class="fas fa-spinner state-icon"></i><h3 class="state-title">Cargando ${label}...</h3><p class="state-description">Por favor espera</p></div>`;
        }

        function showEmptyState(tipo) {
            const containerMap = { mios: 'misProgramasContainer', otros: 'otrosProgramasContainer', plantillas: 'plantillasContainer' };
            const el = document.getElementById(containerMap[tipo]);
            if (!el) return;
            const msgs = {
                mios: { icon: 'user', title: 'No tienes programas', desc: '¡Comienza creando tu primer programa de viaje!', btn: `<button onclick="mostrarModalCreacion()" class="action-btn"><i class="fas fa-plus"></i> Crear Nuevo Programa</button>` },
                otros: { icon: 'users', title: 'No hay otros programas', desc: 'Aún no hay programas creados por otros usuarios.', btn: '' },
                plantillas: { icon: 'copy', title: 'No hay plantillas', desc: 'Guarda un programa como plantilla para usarlo como base.', btn: '' }
            };
            const m = msgs[tipo] || msgs.mios;
            el.innerHTML = `<div class="empty-state"><i class="fas fa-${m.icon} state-icon"></i><h3 class="state-title">${m.title}</h3><p class="state-description">${m.desc}</p>${m.btn}</div>`;
        }

        function showErrorState(tipo, message) {
            const containerMap = { mios: 'misProgramasContainer', otros: 'otrosProgramasContainer', plantillas: 'plantillasContainer' };
            const el = document.getElementById(containerMap[tipo]);
            if (!el) return;
            el.innerHTML = `<div class="error-state"><i class="fas fa-exclamation-triangle state-icon"></i><h3 class="state-title">Error al cargar</h3><p class="state-description">${message}</p><button onclick="cargarProgramas()" class="action-btn"><i class="fas fa-redo"></i> Reintentar</button></div>`;
        }

        // ============================================================
        // FUNCIONES AUXILIARES
        // ============================================================

        function limpiarFiltros(tipo) {
            if (tipo === 'mios') {
                document.getElementById('searchInputMios').value = '';
            } else if (tipo === 'otros') {
                document.getElementById('searchInputOtros').value = '';
                document.getElementById('filterStatusOtros').value = '';
                document.getElementById('filterAuthor').value = '';
            } else if (tipo === 'plantillas') {
                document.getElementById('searchInputPlantillas').value = '';
            }
            filtrarProgramas(tipo);
        }

        function showNotification(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            const iconClass = type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-circle-exclamation' : 'fa-circle-info';
            toast.innerHTML = `
                <div class="toast-content">
                    <span class="toast-icon"><i class="fas ${iconClass}"></i></span>
                    <span>${message}</span>
                </div>
            `;
            document.body.appendChild(toast);
            setTimeout(() => toast.classList.add('show'), 100);
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => {
                    if (toast.parentNode) document.body.removeChild(toast);
                }, 300);
            }, 4000);
        }

        // Google Translate con idioma por defecto del sistema
        function initializeGoogleTranslate() {
            function googleTranslateElementInit() {
                new google.translate.TranslateElement({
                    pageLanguage: DEFAULT_LANGUAGE,
                    includedLanguages: 'en,fr,pt,it,de,es',
                    layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
                    autoDisplay: false
                }, 'google_translate_element');

                setTimeout(loadSavedLanguage, 1000);
            }

            function saveLanguage(lang) {
                sessionStorage.setItem('language', lang);
                localStorage.setItem('preferredLanguage', lang);
            }

            function loadSavedLanguage() {
                const saved = sessionStorage.getItem('language') ||
                    localStorage.getItem('preferredLanguage') ||
                    DEFAULT_LANGUAGE;

                if (saved && saved !== DEFAULT_LANGUAGE) {
                    const select = document.querySelector('.goog-te-combo');
                    if (select) {
                        select.value = saved;
                        select.dispatchEvent(new Event('change'));
                    }
                }
            }

            if (!window.googleTranslateElementInit) {
                window.googleTranslateElementInit = googleTranslateElementInit;
                const script = document.createElement('script');
                script.src = '//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit';
                document.head.appendChild(script);
            }

            setTimeout(function () {
                const select = document.querySelector('.goog-te-combo');
                if (select) {
                    select.addEventListener('change', function () {
                        if (this.value) saveLanguage(this.value);
                    });
                }
            }, 2000);
        }

        // ============================================================
        // FUNCIONES DE EXPORTACIÓN Y UTILIDADES
        // ============================================================

        function exportarProgramas() {
            console.log('Exportando programas...');

            if (allProgramas.length === 0) {
                showNotification('No hay programas para exportar', 'error');
                return;
            }

            const headers = ['ID', 'Título', 'Destino', 'Viajero', 'Fechas', 'Pasajeros', 'Estado', 'Creado por'];
            const csvData = allProgramas.map(programa => [
                programa.id_solicitud || programa.id,
                programa.titulo_programa || `Viaje a ${programa.destino}`,
                programa.destino,
                `${programa.nombre_viajero} ${programa.apellido_viajero}`,
                formatDateRange(programa.fecha_llegada, programa.fecha_salida),
                programa.numero_pasajeros,
                programa.estado || 'borrador',
                programa.created_by_name || 'N/A'
            ]);

            const csvContent = [headers, ...csvData]
                .map(row => row.map(cell => `"${cell}"`).join(','))
                .join('\n');

            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', `programas_${new Date().toISOString().split('T')[0]}.csv`);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            showNotification('Programas exportados exitosamente', 'success');
        }

        function formatDate(dateString) {
            if (!dateString) return 'N/A';

            const date = new Date(dateString);
            return date.toLocaleDateString('es-ES', {
                day: 'numeric',
                month: 'short',
                year: 'numeric'
            });
        }

        function formatDateRange(startDate, endDate) {
            if (!startDate || !endDate) return 'Fechas no definidas';

            const start = formatDate(startDate);
            const end = formatDate(endDate);

            return `${start} - ${end}`;
        }

        // ============================================================
        // FUNCIONES ADICIONALES PARA LA API
        // ============================================================

        // Esta función debe ser agregada al API de programa para obtener todos los programas
        // Incluye información del creador para distinguir entre "mis programas" y "otros programas"

        /*
        NOTA PARA EL DESARROLLADOR:
        
        Necesitas agregar este endpoint en tu API de programa:
        
        case 'list_all':
            $result = $this->listAllPrograms();
            break;
            
        Y la función correspondiente:
        
        private function listAllPrograms() {
            try {
                $programas = $this->db->fetchAll(
                    "SELECT ps.*, u.full_name as created_by_name
                     FROM programa_solicitudes ps
                     LEFT JOIN users u ON ps.user_id = u.id
                     ORDER BY ps.created_at DESC"
                );
                
                return [
                    'success' => true,
                    'data' => $programas
                ];
                
            } catch(Exception $e) {
                error_log("Error en listAllPrograms: " . $e->getMessage());
                return [
                    'success' => false,
                    'error' => 'Error al obtener todos los programas'
                ];
            }
        }
        */

        // Hacer funciones disponibles globalmente
        window.cargarProgramas = cargarProgramas;
        window.filtrarProgramas = filtrarProgramas;
        window.verDetalles = verDetalles;
        window.verDetallesModal = verDetallesModal;
        window.cerrarModalVistaPrevia = cerrarModalVistaPrevia;
        window.abrirEnNuevaVentana = abrirEnNuevaVentana;
        window.editarPrograma = editarPrograma;
        window.copiarPrograma = copiarPrograma;
        window.limpiarFiltros = limpiarFiltros;
        window.exportarProgramas = exportarProgramas;
        window.mostrarModalCreacion = mostrarModalCreacion;
        window.cerrarModalCreacion = cerrarModalCreacion;
        window.seleccionarOpcion = seleccionarOpcion;
        window.procederCreacion = procederCreacion;
        window.toggleSidebar = toggleSidebar;
        window.closeSidebar = closeSidebar;
        window.toggleUserMenu = toggleUserMenu;
        window.switchTab = switchTab;
        window.togglePlantilla = togglePlantilla;
        window.usarPlantilla = usarPlantilla;
        window.setPickerTab = setPickerTab;
        window.renderPickerCards = renderPickerCards;
        window.selectPickerProgram = selectPickerProgram;

        // Cerrar modal al hacer clic fuera
        document.getElementById('modalCreacion').addEventListener('click', function (e) {
            if (e.target === this) {
                cerrarModalCreacion();
            }
        });

        console.log('Script de itinerarios mejorado cargado completamente');


        function abrirBonoReserva(programaId) {
            if (!programaId) {
                alert('No se encontró el ID del programa.');
                return;
            }

            window.open(`<?= APP_URL ?>/modules/bonos/preview.php?programa_id=${programaId}`, '_blank');
        }

    </script>
</body>

</html>