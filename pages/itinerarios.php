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

// Obtener configuración de colores según el rol del usuario
ConfigManager::init();
$userColors = ConfigManager::getColorsForRole($user['role']);
$companyName = ConfigManager::getCompanyName();
$logo = ConfigManager::getLogo();
$defaultLanguage = ConfigManager::getDefaultLanguage();
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
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        :root {
            --primary-color: <?= $userColors['primary'] ?>;
            --secondary-color: <?= $userColors['secondary'] ?>;
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
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 24px;
            margin-top: 24px;
        }

        /* Tarjeta de programa */
        .program-card {
            background: white;
            border-radius: 15px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
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
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
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
            height: 160px;
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
            font-size: 2rem;
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
            padding: 20px;
        }

        .program-header {
            margin-bottom: 16px;
        }

        .program-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .program-destination {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #718096;
            font-size: 0.9rem;
            margin-bottom: 4px;
        }

        .program-traveler {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #718096;
            font-size: 0.9rem;
        }

        .program-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin: 16px 0;
            padding: 16px 0;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
        }

        .detail-item {
            text-align: center;
        }

        .detail-label {
            font-size: 0.75rem;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
            font-weight: 600;
        }

        .detail-value {
            font-size: 0.9rem;
            font-weight: 600;
            color: #2d3748;
        }

        .detail-value.highlight {
            color: var(--primary-color);
        }

        .program-actions {
            display: flex;
            gap: 8px;
        }

        .btn-sm {
            padding: 8px 15px;
            font-size: 0.8rem;
            border-radius: 20px;
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

        .form-input, .form-select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-input:focus, .form-select:focus {
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
        .loading-state, .empty-state, .error-state {
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
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
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
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
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
        <!-- Header de Página -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-route"></i>
                Mis Programas de Viaje
            </h1>
            <p class="page-subtitle">
                Gestiona y visualiza todos tus itinerarios de manera elegante y eficiente
            </p>
        </div>

        <!-- Estadísticas Rápidas -->
        <div class="stats-section">
            <div class="stats-grid">
                <div class="stat-card">
                    <i class="fas fa-plane stat-icon"></i>
                    <span class="stat-number" id="totalProgramas">0</span>
                    <div class="stat-label">Total Programas</div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-user-edit stat-icon"></i>
                    <span class="stat-number" id="misProgramas">0</span>
                    <div class="stat-label">Mis Programas</div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-users stat-icon"></i>
                    <span class="stat-number" id="otrosProgramas">0</span>
                    <div class="stat-label">Otros Programas</div>
                </div>
            </div>

            <!-- Acciones Rápidas -->
            <div class="quick-actions">
                <button onclick="mostrarModalCreacion()" class="action-btn">
                    <i class="fas fa-plus"></i>
                    Crear Nuevo Programa
                </button>
                <button onclick="cargarProgramas()" class="action-btn secondary">
                    <i class="fas fa-sync"></i>
                    Actualizar Lista
                </button>
            </div>
        </div>

        <!-- Sección MIS PROGRAMAS -->
        <div class="programs-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-user-edit"></i>
                    Mis Programas
                    <span class="badge" id="misProgramasBadge">0</span>
                </h2>
                
                <div class="filters-container">
                    <div class="search-box">
                        <i class="fas fa-search search-icon"></i>
                        <input 
                            type="text" 
                            id="searchInputMios" 
                            class="search-input" 
                            placeholder="Buscar mis programas..."
                            oninput="filtrarProgramas('mios')"
                        >
                    </div>
                </div>
            </div>

            <!-- Container de MIS Programas -->
            <div id="misProgramasContainer">
                <div class="loading-state">
                    <i class="fas fa-spinner state-icon"></i>
                    <h3 class="state-title">Cargando mis programas...</h3>
                    <p class="state-description">Por favor espera mientras obtenemos tus programas</p>
                </div>
            </div>
        </div>

        <!-- Sección OTROS PROGRAMAS -->
        <div class="programs-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-users"></i>
                    Otros Programas
                    <span class="badge" id="otrosProgramasBadge">0</span>
                </h2>
                
                <div class="filters-container">
                    <div class="search-box">
                        <i class="fas fa-search search-icon"></i>
                        <input 
                            type="text" 
                            id="searchInputOtros" 
                            class="search-input" 
                            placeholder="Buscar otros programas..."
                            oninput="filtrarProgramas('otros')"
                        >
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
                </div>
            </div>

            <!-- Container de OTROS Programas -->
            <div id="otrosProgramasContainer">
                <div class="loading-state">
                    <i class="fas fa-spinner state-icon"></i>
                    <h3 class="state-title">Cargando otros programas...</h3>
                    <p class="state-description">Por favor espera mientras obtenemos los programas de otros usuarios</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Creación -->
    <div class="modal-overlay" id="modalCreacion">
        <div class="modal">
            <div class="modal-header">
                <h2 class="modal-title">Crear Nuevo Programa</h2>
                <p class="modal-subtitle">Elige cómo quieres crear tu programa de viaje</p>
            </div>

            <div class="modal-options">
                <div class="modal-option" onclick="seleccionarOpcion('desde-cero')" id="opcion-desde-cero">
                    <div class="option-icon">
                        <i class="fas fa-plus"></i>
                    </div>
                    <div class="option-content">
                        <div class="option-title">Crear desde cero</div>
                        <div class="option-description">Comienza un programa completamente nuevo con información básica</div>
                    </div>
                </div>

                <div class="modal-option" onclick="seleccionarOpcion('desde-existente')" id="opcion-desde-existente">
                    <div class="option-icon">
                        <i class="fas fa-copy"></i>
                    </div>
                    <div class="option-content">
                        <div class="option-title">Crear desde programa existente</div>
                        <div class="option-description">Usa un programa existente como base y personalízalo</div>
                    </div>
                </div>
            </div>

            <!-- Formulario para selección de programa base -->
            <div class="modal-form" id="formSeleccionPrograma">
                <div class="form-group">
                    <label class="form-label">Seleccionar programa base:</label>
                    <select class="form-select" id="programaBase">
                        <option value="">Cargando programas...</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Vista previa:</label>
                    <div id="previstaPrograma" style="padding: 12px; background: #f8fafc; border-radius: 8px; color: #718096; font-size: 0.9rem;">
                        Selecciona un programa para ver la vista previa
                    </div>
                </div>
            </div>

            <div class="modal-actions">
                <button class="modal-btn secondary" onclick="cerrarModalCreacion()">Cancelar</button>
                <button class="modal-btn primary" onclick="procederCreacion()" id="btnProceder">Proceder</button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        // Configuración global
        const APP_URL = '<?= APP_URL ?>';
        const DEFAULT_LANGUAGE = '<?= $defaultLanguage ?>';
        const CURRENT_USER_ID = <?= $user['id'] ?>;

        let sidebarOpen = false;
        let allProgramas = [];
        let misProgramasFiltrados = [];
        let otrosProgramasFiltrados = [];
        let opcionSeleccionada = null;
        
        // Inicializar al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 Iniciando página de itinerarios mejorada...');
            cargarProgramas();
            initializeGoogleTranslate();
        });

        // Funciones de sidebar CORREGIDAS
        function toggleSidebar() {
            const sidebar = document.querySelector('.enhanced-sidebar');
            const overlay = document.getElementById('overlay');
            const mainContent = document.getElementById('mainContent');
            
            if (!sidebar) {
                console.error('❌ Sidebar no encontrado con clase .enhanced-sidebar');
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

        function toggleUserMenu() {
            if (confirm('¿Desea cerrar sesión?')) {
                window.location.href = '<?= APP_URL ?>/auth/logout';
            }
        }

        // ============================================================
        // FUNCIONES DE CARGA DE DATOS
        // ============================================================
        
        async function cargarProgramas() {
            console.log('📥 Cargando programas (míos y otros) con imágenes...');
            
            showLoadingState('mios');
            showLoadingState('otros');
            
            try {
                // Cargar todos los programas (incluye user_id, full_name del creador E IMÁGENES)
                const response = await fetch('<?= APP_URL ?>/programa/api?action=list_all');
                
                if (!response.ok) {
                    throw new Error(`Error HTTP: ${response.status}`);
                }
                
                const result = await response.json();
                console.log('📋 Datos recibidos:', result);
                
                if (result.success) {
                    allProgramas = result.data || [];
                    
                    // Debug de imágenes
                    allProgramas.forEach(programa => {
                        if (programa.foto_portada) {
                            console.log(`🖼️ Programa ${programa.id} tiene imagen: ${programa.foto_portada}`);
                        } else {
                            console.log(`📷 Programa ${programa.id} SIN imagen de portada`);
                        }
                    });
                    
                    // Separar programas por propietario
                    separarProgramas();
                    actualizarEstadisticas();
                    mostrarProgramas();
                    cargarAutoresEnFiltro();
                    
                    console.log(`✅ ${allProgramas.length} programas cargados (${misProgramasFiltrados.length} míos, ${otrosProgramasFiltrados.length} otros)`);
                } else {
                    throw new Error(result.error || 'Error al cargar programas');
                }
                
            } catch (error) {
                console.error('❌ Error cargando programas:', error);
                showErrorState('mios', error.message);
                showErrorState('otros', error.message);
            }
        }

        function separarProgramas() {
            const misProgramas = allProgramas.filter(p => p.user_id == CURRENT_USER_ID);
            const otrosProgramas = allProgramas.filter(p => p.user_id != CURRENT_USER_ID);
            
            misProgramasFiltrados = [...misProgramas];
            otrosProgramasFiltrados = [...otrosProgramas];
            
            console.log(`📊 Separación: ${misProgramas.length} míos, ${otrosProgramas.length} otros`);
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

        // ============================================================
        // FUNCIONES DE VISUALIZACIÓN
        // ============================================================
        
        function mostrarProgramas() {
            mostrarProgramasSeccion('mios', misProgramasFiltrados);
            mostrarProgramasSeccion('otros', otrosProgramasFiltrados);
        }
        
        function mostrarProgramasSeccion(tipo, programas) {
            const containerId = tipo === 'mios' ? 'misProgramasContainer' : 'otrosProgramasContainer';
            const container = document.getElementById(containerId);
            
            if (!programas || programas.length === 0) {
                showEmptyState(tipo);
                return;
            }
            
            const programsGrid = document.createElement('div');
            programsGrid.className = 'programs-grid';
            
            programas.forEach(programa => {
                const card = crearTarjetaPrograma(programa, tipo === 'otros');
                programsGrid.appendChild(card);
            });
            
            container.innerHTML = '';
            container.appendChild(programsGrid);
        }
        
        function crearTarjetaPrograma(programa, esReadonly = false) {
            const card = document.createElement('div');
            card.className = `program-card ${esReadonly ? 'readonly' : ''}`;
            
            if (!esReadonly) {
                card.onclick = () => editarPrograma(programa.id);
            }
            
            // Calcular duración
            let duracion = 'N/A';
            if (programa.fecha_llegada && programa.fecha_salida) {
                const llegada = new Date(programa.fecha_llegada);
                const salida = new Date(programa.fecha_salida);
                const dias = Math.ceil((salida - llegada) / (1000 * 60 * 60 * 24));
                duracion = dias > 0 ? `${dias} días` : '1 día';
            }
            
            
            
            // OBTENER IMAGEN DE PORTADA DESDE LA BASE DE DATOS
            const imagenPortada = programa.foto_portada || null;
            const autorPrograma = programa.created_by_name || 'Usuario';
            
            // Validar que la imagen existe y es una URL válida
            const tieneImagen = imagenPortada && 
                               (imagenPortada.startsWith('http://') || 
                                imagenPortada.startsWith('https://') || 
                                imagenPortada.startsWith('/'));
            
            console.log(`🖼️ Programa ${programa.id}: imagen = ${imagenPortada}, válida = ${tieneImagen}`);
            const esAdmin = <?= json_encode($user['role'] === 'admin') ?>;
            
            card.innerHTML = `
                <div class="program-image">
                    ${tieneImagen ? 
                        `<img src="${imagenPortada}" alt="Portada del programa" onerror="this.parentElement.innerHTML='<div class=&quot;placeholder&quot;><i class=&quot;fas fa-map-marked-alt&quot;></i></div>';">` : 
                        `<div class="placeholder"><i class="fas fa-map-marked-alt"></i></div>`
                    }
                    ${esReadonly ? `<div class="program-owner">${autorPrograma}</div>` : ''}
                </div>
                
                <div class="program-content">
                    <div class="program-header">
                        <h3 class="program-title">
                            ${programa.titulo_programa || `Viaje a ${programa.destino}`}
                        </h3>
                        <div class="program-destination">
                            <i class="fas fa-map-marker-alt"></i>
                            ${programa.destino}
                        </div>
                        <div class="program-traveler">
                            <i class="fas fa-user"></i>
                            ${programa.nombre_viajero} ${programa.apellido_viajero}
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
                        ${esReadonly ? `
                            <button onclick="event.stopPropagation(); verDetalles(${programa.id})" class="btn-sm btn-outline-sm">
                                <i class="fas fa-eye"></i>
                                Ver
                            </button>
                            <button onclick="event.stopPropagation(); copiarPrograma(${programa.id})" class="btn-sm btn-primary-sm">
                                <i class="fas fa-copy"></i>
                                Copiar
                            </button>
                            ${esAdmin ? `
                                <button onclick="event.stopPropagation(); confirmarEliminacion(${programa.id}, '${(programa.titulo_programa || `Viaje a ${programa.destino}`).replace(/'/g, "\\\'")}')" class="btn-sm btn-danger-sm" title="Eliminar programa">
                                    <i class="fas fa-trash"></i>
                                    Eliminar
                                </button>
                            ` : ''}
                        ` : `
                            <a href="<?= APP_URL ?>/programa?id=${programa.id}" class="btn-sm btn-primary-sm">
                                <i class="fas fa-edit"></i>
                                Editar
                            </a>
                            <button onclick="event.stopPropagation(); verDetalles(${programa.id})" class="btn-sm btn-outline-sm">
                                <i class="fas fa-eye"></i>
                                Ver
                            </button>
                            ${esAdmin ? `
                                <button onclick="event.stopPropagation(); confirmarEliminacion(${programa.id}, '${(programa.titulo_programa || `Viaje a ${programa.destino}`).replace(/'/g, "\\\'")}')" class="btn-sm btn-danger-sm" title="Eliminar programa">
                                    <i class="fas fa-trash"></i>
                                    Eliminar
                                </button>
                            ` : ''}
                        `}
                    </div>
                </div>
            `;
            
            return card;
        }

        // FUNCIONES DE ELIMINACIÓN
        async function confirmarEliminacion(programaId, programaTitulo) {
            const confirmed = await showConfirmModal({
                title: '¿Eliminar programa?',
                message: `¿Estás seguro de eliminar el programa "${programaTitulo}"?`,
                details: 'Esta acción eliminará TODA la información: días del itinerario, servicios, precios y todo el programa. Esto NO se puede deshacer.',
                icon: '🗑️',
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
                formData.append('action', 'delete_programa_admin');
                formData.append('programa_id', programaId);
                
                const response = await fetch('<?= APP_URL ?>/programa/api', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification('✅ Programa eliminado exitosamente', 'success');
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
            const total = allProgramas.length;
            const mios = misProgramasFiltrados.length;
            const otros = otrosProgramasFiltrados.length;
            
            animateCounter('totalProgramas', total);
            animateCounter('misProgramas', mios);
            animateCounter('otrosProgramas', otros);
            
            // Actualizar badges
            document.getElementById('misProgramasBadge').textContent = mios;
            document.getElementById('otrosProgramasBadge').textContent = otros;
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
                
                const programasBase = allProgramas.filter(p => p.user_id == CURRENT_USER_ID);
                
                misProgramasFiltrados = programasBase.filter(programa => {
                    if (!searchTerm) return true;
                    
                    const searchFields = [
                        programa.destino,
                        programa.nombre_viajero,
                        programa.apellido_viajero,
                        programa.titulo_programa,
                        programa.id_solicitud,
                        `${programa.nombre_viajero} ${programa.apellido_viajero}`, // Nombre completo
                        `Viaje a ${programa.destino}` // Título por defecto
                    ];
                    
                    return searchFields.some(field => 
                        field && field.toString().toLowerCase().includes(searchTerm)
                    );
                });
                
                mostrarProgramasSeccion('mios', misProgramasFiltrados);
                actualizarPlaceholderBusqueda('mios');
            } else if (tipo === 'otros') {
                const searchTerm = document.getElementById('searchInputOtros').value.toLowerCase().trim();
                const authorFilter = document.getElementById('filterAuthor').value;
                
                const programasBase = allProgramas.filter(p => p.user_id != CURRENT_USER_ID);
                
                otrosProgramasFiltrados = programasBase.filter(programa => {
                    // Filtro de búsqueda
                    const matchesSearch = !searchTerm || (() => {
                        const searchFields = [
                            programa.destino,
                            programa.nombre_viajero,
                            programa.apellido_viajero,
                            programa.titulo_programa,
                            programa.id_solicitud,
                            programa.created_by_name,
                            `${programa.nombre_viajero} ${programa.apellido_viajero}`,
                            `Viaje a ${programa.destino}`
                        ];
                        
                        return searchFields.some(field => 
                            field && field.toString().toLowerCase().includes(searchTerm)
                        );
                    })();
                    
                    // Filtro de autor
                    const matchesAuthor = !authorFilter || programa.created_by_name === authorFilter;
                    
                    return matchesSearch && matchesAuthor;
                });
                
                mostrarProgramasSeccion('otros', otrosProgramasFiltrados);
                actualizarPlaceholderBusqueda('otros');
}
            
            actualizarEstadisticas();
            console.log(`🔍 Filtrado ${tipo}: ${tipo === 'mios' ? misProgramasFiltrados.length : otrosProgramasFiltrados.length} programas`);
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
            const modal = document.getElementById('modalCreacion');
            modal.classList.add('show');
            
            // Reset estado
            opcionSeleccionada = null;
            document.getElementById('opcion-desde-cero').classList.remove('selected');
            document.getElementById('opcion-desde-existente').classList.remove('selected');
            document.getElementById('formSeleccionPrograma').classList.remove('show');
            document.getElementById('btnProceder').disabled = true;
            
            console.log('📝 Modal de creación mostrado');
        }
        
        function cerrarModalCreacion() {
            const modal = document.getElementById('modalCreacion');
            modal.classList.remove('show');
        }
        
        function seleccionarOpcion(opcion) {
            opcionSeleccionada = opcion;
            
            // Reset visual
            document.getElementById('opcion-desde-cero').classList.remove('selected');
            document.getElementById('opcion-desde-existente').classList.remove('selected');
            document.getElementById('formSeleccionPrograma').classList.remove('show');
            
            // Marcar seleccionado
            document.getElementById(`opcion-${opcion}`).classList.add('selected');
            
            if (opcion === 'desde-existente') {
                cargarProgramasParaSeleccion();
                document.getElementById('formSeleccionPrograma').classList.add('show');
                document.getElementById('btnProceder').disabled = true;
            } else {
                document.getElementById('btnProceder').disabled = false;
            }
            
            console.log(`📋 Opción seleccionada: ${opcion}`);
        }

        async function procederCreacion() {
    if (!opcionSeleccionada) {
        showNotification('Por favor selecciona una opción', 'error');
        return;
    }
    
    if (opcionSeleccionada === 'desde-cero') {
        window.location.href = '<?= APP_URL ?>/programa';
    } else if (opcionSeleccionada === 'desde-existente') {
        const programaBaseId = document.getElementById('programaBase').value;
        if (!programaBaseId) {
            showNotification('Por favor selecciona un programa base', 'error');
            return;
        }
        
        // Crear copia automáticamente
        const btnProceder = document.getElementById('btnProceder');
        btnProceder.disabled = true;
        btnProceder.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creando copia...';
        
        try {
            const formData = new FormData();
            formData.append('action', 'duplicate_programa');
            formData.append('programa_id', programaBaseId);
            
            const response = await fetch('<?= APP_URL ?>/programa/api', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                cerrarModalCreacion();
                showNotification('✅ Programa duplicado exitosamente', 'success');
                await cargarProgramas();
                
                // Ir al editor del nuevo programa
                setTimeout(() => {
                    window.location.href = `<?= APP_URL ?>/programa?id=${result.new_programa_id}`;
                }, 1500);
            } else {
                throw new Error(result.error || 'Error al duplicar');
            }
        } catch (error) {
            showNotification('Error: ' + error.message, 'error');
            btnProceder.disabled = false;
            btnProceder.innerHTML = 'Proceder';
        }
    }
}
        
        function cargarProgramasParaSeleccion() {
            const select = document.getElementById('programaBase');
            select.innerHTML = '<option value="">Selecciona un programa base...</option>';
            
            // Agregar todos los programas (míos y otros)
            allProgramas.forEach(programa => {
                const option = document.createElement('option');
                option.value = programa.id;
                const titulo = programa.titulo_programa || `Viaje a ${programa.destino}`;
                const autor = programa.user_id == CURRENT_USER_ID ? 'Mío' : programa.created_by_name;
                option.textContent = `${titulo} (${autor})`;
                option.dataset.programa = JSON.stringify(programa);
                select.appendChild(option);
            });
            
            // Listener para vista previa
            select.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const preview = document.getElementById('previstaPrograma');
                
                if (selectedOption.value) {
                    const programa = JSON.parse(selectedOption.dataset.programa);
                    preview.innerHTML = `
                        <strong>${programa.titulo_programa || `Viaje a ${programa.destino}`}</strong><br>
                        📍 ${programa.destino}<br>
                        👤 ${programa.nombre_viajero} ${programa.apellido_viajero}<br>
                        👥 ${programa.numero_pasajeros} viajeros<br>
                        👨‍💼 Creado por: ${programa.user_id == CURRENT_USER_ID ? 'Ti' : programa.created_by_name}
                    `;
                    document.getElementById('btnProceder').disabled = false;
                } else {
                    preview.textContent = 'Selecciona un programa para ver la vista previa';
                    document.getElementById('btnProceder').disabled = true;
                }
            });
        }
        

        // ============================================================
        // FUNCIONES DE INTERACCIÓN
        // ============================================================
        
        function editarPrograma(id) {
            console.log(`✏️ Editando programa ${id}`);
            window.location.href = `<?= APP_URL ?>/programa?id=${id}`;
        }
        
        function verDetalles(id) {
    console.log(`👁️ Viendo detalles del programa ${id}`);
    
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
        showNotification('📖 Abriendo vista previa del programa...', 'success');
    }
}

// ============================================================
// FUNCIÓN ALTERNATIVA PARA MODAL (OPCIONAL)
// ============================================================

function verDetallesModal(id) {
    console.log(`👁️ Viendo detalles del programa ${id} en modal`);
    
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
            console.log(`📋 Copiando programa ${id}`);
            window.location.href = `<?= APP_URL ?>/programa?copy_from=${id}`;
        }

        // ============================================================
        // ESTADOS DE LA INTERFAZ
        // ============================================================
        
        function showLoadingState(tipo) {
            const containerId = tipo === 'mios' ? 'misProgramasContainer' : 'otrosProgramasContainer';
            const tipoText = tipo === 'mios' ? 'mis programas' : 'otros programas';
            
            document.getElementById(containerId).innerHTML = `
                <div class="loading-state">
                    <i class="fas fa-spinner state-icon"></i>
                    <h3 class="state-title">Cargando ${tipoText}...</h3>
                    <p class="state-description">Por favor espera mientras obtenemos los programas</p>
                </div>
            `;
        }
        
        function showEmptyState(tipo) {
            const containerId = tipo === 'mios' ? 'misProgramasContainer' : 'otrosProgramasContainer';
            const programas = tipo === 'mios' ? misProgramasFiltrados : otrosProgramasFiltrados;
            const programasBase = tipo === 'mios' ? 
                allProgramas.filter(p => p.user_id == CURRENT_USER_ID) : 
                allProgramas.filter(p => p.user_id != CURRENT_USER_ID);
            
            const isFiltered = programas.length !== programasBase.length;
            
            document.getElementById(containerId).innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-${tipo === 'mios' ? 'user-edit' : 'users'} state-icon"></i>
                    <h3 class="state-title">${isFiltered ? 'No se encontraron programas' : tipo === 'mios' ? 'No tienes programas' : 'No hay otros programas'}</h3>
                    <p class="state-description">
                        ${isFiltered ? 
                            'No se encontraron programas que coincidan con los filtros aplicados.' :
                            tipo === 'mios' ? 
                                '¡Comienza creando tu primer programa de viaje personalizado!' :
                                'Aún no hay programas creados por otros usuarios.'
                        }
                    </p>
                    ${isFiltered ? 
                        `<button onclick="limpiarFiltros('${tipo}')" class="action-btn">Limpiar Filtros</button>` :
                        tipo === 'mios' ? 
                            '<button onclick="mostrarModalCreacion()" class="action-btn"><i class="fas fa-plus"></i> Crear Nuevo Programa</button>' :
                            ''
                    }
                </div>
            `;
        }
        
        function showErrorState(tipo, message) {
            const containerId = tipo === 'mios' ? 'misProgramasContainer' : 'otrosProgramasContainer';
            
            document.getElementById(containerId).innerHTML = `
                <div class="error-state">
                    <i class="fas fa-exclamation-triangle state-icon"></i>
                    <h3 class="state-title">Error al cargar</h3>
                    <p class="state-description">${message}</p>
                    <button onclick="cargarProgramas()" class="action-btn">
                        <i class="fas fa-redo"></i>
                        Reintentar
                    </button>
                </div>
            `;
        }

        // ============================================================
        // FUNCIONES AUXILIARES
        // ============================================================
        
        function limpiarFiltros(tipo) {
            if (tipo === 'mios') {
                document.getElementById('searchInputMios').value = '';
                document.getElementById('filterStatusMios').value = '';
            } else {
                document.getElementById('searchInputOtros').value = '';
                document.getElementById('filterStatusOtros').value = '';
                document.getElementById('filterAuthor').value = '';
            }
            filtrarProgramas(tipo);
        }
        
        function showNotification(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            
            const icon = type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️';
            toast.innerHTML = `
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 20px;">${icon}</span>
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

            setTimeout(function() {
                const select = document.querySelector('.goog-te-combo');
                if (select) {
                    select.addEventListener('change', function() {
                        if (this.value) saveLanguage(this.value);
                    });
                }
            }, 2000);
        }

        // ============================================================
        // FUNCIONES DE EXPORTACIÓN Y UTILIDADES
        // ============================================================
        
        function exportarProgramas() {
            console.log('📤 Exportando programas...');
            
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
            
            showNotification('✅ Programas exportados exitosamente', 'success');
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
        
        // Cerrar modal al hacer clic fuera
        document.getElementById('modalCreacion').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModalCreacion();
            }
        });
        
        console.log('✅ Script de itinerarios mejorado cargado completamente');
        
    </script>
</body>
</html>