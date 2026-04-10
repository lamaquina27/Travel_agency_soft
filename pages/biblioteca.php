<?php 
// =====================================
// ARCHIVO: pages/biblioteca.php - Biblioteca con Componentes UI Integrados
// =====================================

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
    <title>Biblioteca - <?= htmlspecialchars($companyName) ?></title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/modern_image_upload.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/biblioteca_carousel.css">
    <script src="<?= APP_URL ?>/assets/js/modern_image_upload.js"></script>
    <script src="<?= APP_URL ?>/assets/js/biblioteca_carousel.js"></script>
    

    
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

        .back-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 25px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
            color: white;
            text-decoration: none;
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


        .loading-spinner {
            width: 16px;
            height: 16px;
            border: 2px solid #e2e8f0;
            border-top: 2px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .search-input {
            transition: all 0.3s ease;
        }

        .search-input:focus {
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transform: scale(1.02);
        }

        .filter-select:focus {
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
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

        /* Tabs Container */
    

        .tab-btn.active {
            background: var(--primary-gradient);
            color: white;
        }

.tabs-nav {
   display: flex;
   gap: 0;
   margin-bottom: 25px;
   border-bottom: 2px solid #e2e8f0;
   padding-bottom: 0;
}

.tab-btn {
   background: none;
   border: none;
   padding: 12px 20px;
   border-radius: 0;
   cursor: pointer;
   font-size:16px;
   font-weight: 800;
   transition: all 0.3s ease;
   color: #4a5568;
   flex: 1;
   text-align: center;
   border-bottom: 3px solid transparent;
}

.tab-btn.active {
   background: var(--primary-gradient);
   color: white;
   border-bottom: 3px solid var(--primary-color);
}

.tab-btn:hover:not(.active) {
   background: #f7fafc;
}

        /* Search and Filters */
        .filters-section {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
            align-items: center;
        }

        .search-input {
            flex: 1;
            min-width: 250px;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 25px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .filter-select {
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 25px;
            font-size: 14px;
            cursor: pointer;
        }

        .filter-select:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .add-btn {
            background: var(--primary-gradient);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 500;
            transition: transform 0.3s ease;
        }

        .add-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }

        .item-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
            border: 1px solid #e2e8f0;
        }

        .item-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-gradient);
        }

        .item-card {
            position: relative;
        }

        .item-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
            border-color: var(--primary-color);
        }

        .card-image {
            width: 100%;
            height: 200px;
            background: var(--primary-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 48px;
            position: relative;
        }

        .card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .card-content {
            padding: 20px;
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
        }

        .card-description {
            color: #718096;
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 12px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .card-location {
            display: flex;
            align-items: center;
            gap: 5px;
            color: var(--primary-color);
            font-size: 13px;
            font-weight: 500;
        }

        .card-actions {
            padding: 15px 20px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            gap: 10px;
        }

        .action-btn {
            flex: 1;
            padding: 8px 15px;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            background: none;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.3s ease;
        }

        .action-btn.edit {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .action-btn.edit:hover {
            background: var(--primary-color);
            color: white;
        }

        .action-btn.delete {
            color: #e53e3e;
            border-color: #e53e3e;
        }

        .action-btn.delete:hover {
            background: #e53e3e;
            color: white;
        }

        /* Modal Styles */
        /* =====================================
   MEJORAS PARA MODALS DE BIBLIOTECA
   ===================================== */

/* Modal Principal - Mejorar backdrop y animaciones */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    z-index: 1000;
    overflow-y: auto;
    backdrop-filter: blur(8px);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.modal.show {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 100px 20px 20px 20px;
    opacity: 1;
    animation: modalFadeIn 0.3s ease-out;
}

/* Contenido del Modal - Diseño más moderno */
.modal-content {
    background: white;
    border-radius: 24px;
    padding: 0;
    max-width: 900px;
    width: 100%;
    max-height: 90vh;
    overflow: hidden;
    box-shadow: 
        0 25px 50px rgba(0, 0, 0, 0.25),
        0 10px 20px rgba(0, 0, 0, 0.15);
    transform: scale(0.9) translateY(20px);
    animation: modalSlideIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

/* Header del Modal - Más elegante */
.modal-header {
    background: linear-gradient(135deg, var(--primary-color, #667eea) 0%, var(--secondary-color, #764ba2) 100%);
    color: white;
    padding: 10px 40px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0;
    border-bottom: none;
    position: relative;
    overflow: hidden;
}

.modal-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 20"><defs><pattern id="grain" width="100" height="20" patternUnits="userSpaceOnUse"><circle cx="10" cy="5" r="0.5" fill="rgba(255,255,255,0.05)"/><circle cx="30" cy="15" r="0.3" fill="rgba(255,255,255,0.03)"/><circle cx="70" cy="8" r="0.4" fill="rgba(255,255,255,0.04)"/><circle cx="90" cy="12" r="0.2" fill="rgba(255,255,255,0.02)"/></pattern></defs><rect width="100" height="20" fill="url(%23grain)"/></svg>');
    opacity: 0.6;
}

.modal-title {
    font-size: 28px;
    font-weight: 700;
    margin: 0;
    position: relative;
    z-index: 1;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    letter-spacing: -0.5px;
}

/* Botón cerrar - Más elegante */
.close-btn {
    background: rgba(255, 255, 255, 0.2);
    border: 2px solid rgba(255, 255, 255, 0.3);
    color: white;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 20px;
    font-weight: 300;
    transition: all 0.3s ease;
    position: relative;
    z-index: 1;
}

.close-btn:hover {
    background: rgba(255, 255, 255, 0.3);
    border-color: rgba(255, 255, 255, 0.5);
    transform: scale(1.1) rotate(90deg);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

/* Contenido del formulario - Con scroll personalizado */
.modal-content form {
    padding: 40px;
    max-height: calc(90vh - 120px);
    overflow-y: auto;
}

/* Scrollbar personalizado para el modal */
.modal-content form::-webkit-scrollbar {
    width: 6px;
}

.modal-content form::-webkit-scrollbar-track {
    background: #f8fafc;
    border-radius: 3px;
}

.modal-content form::-webkit-scrollbar-thumb {
    background: linear-gradient(45deg, var(--primary-color, #667eea), var(--secondary-color, #764ba2));
    border-radius: 3px;
}

.modal-content form::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(45deg, var(--secondary-color, #764ba2), var(--primary-color, #667eea));
}

/* Grid del formulario - Mejor espaciado */
.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 25px;
    margin-bottom: 30px;
}

/* Grupos de formulario - Más modernos */
.form-group {
    display: flex;
    flex-direction: column;
    gap: 10px;
    position: relative;
}

.form-group label {
    font-weight: 600;
    color: #2d3748;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 5px;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Campos de entrada - Diseño premium */
.form-group input,
.form-group select,
.form-group textarea {
    padding: 16px 20px;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 500;
    background: #fafbfc;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--primary-color, #667eea);
    background: white;
    box-shadow: 
        0 0 0 4px rgba(102, 126, 234, 0.1),
        0 4px 12px rgba(0, 0, 0, 0.08);
    transform: translateY(-1px);
}

.form-group textarea {
    resize: vertical;
    min-height: 120px;
    font-family: inherit;
    line-height: 1.6;
}

/* Grid de imágenes - Más atractivo */
.images-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

/* Upload de imágenes - Diseño mejorado */
.image-upload {
    border: 3px dashed #cbd5e0;
    border-radius: 16px;
    padding: 30px 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    background: linear-gradient(135deg, #f8fafc 0%, #edf2f7 100%);
    min-height: 180px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
}

.image-upload::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(45deg, transparent 30%, rgba(255, 255, 255, 0.1) 50%, transparent 70%);
    transform: translateX(-100%);
    transition: transform 0.6s ease;
}

.image-upload:hover {
    border-color: var(--primary-color, #667eea);
    background: linear-gradient(135deg, #f0f4ff 0%, #e6f3ff 100%);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.15);
}

.image-upload:hover::before {
    transform: translateX(100%);
}

.upload-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    position: relative;
    z-index: 1;
}

.upload-content > div:first-child {
    font-size: 32px;
    margin-bottom: 8px;
    transition: transform 0.3s ease;
}

.image-upload:hover .upload-content > div:first-child {
    transform: scale(1.2);
}

.upload-content > div:nth-child(2) {
    font-weight: 600;
    color: #4a5568;
    font-size: 16px;
}

.upload-content > div:last-child {
    font-size: 13px;
    color: #718096;
    font-style: italic;
}



/* Acciones del formulario - Botones mejorados */
.form-actions {
    display: flex;
    gap: 20px;
    justify-content: flex-end;
    padding-top: 30px;
    border-top: 2px solid #f7fafc;
    margin-top: 40px;
}

.btn-secondary,
.btn-primary {
    padding: 16px 32px;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    font-weight: 600;
    font-size: 15px;
    letter-spacing: 0.3px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
    min-width: 120px;
}

.btn-secondary {
    background: #e2e8f0;
    color: #4a5568;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.btn-secondary:hover {
    background: #cbd5e0;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary-color, #667eea) 0%, var(--secondary-color, #764ba2) 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5);
}

.btn-primary:active {
    transform: translateY(0);
}

/* Estados de loading para botones */
.btn-primary:disabled {
    background: #a0aec0;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

/* Animaciones personalizadas */
@keyframes modalFadeIn {
    from {
        opacity: 0;
        backdrop-filter: blur(0px);
    }
    to {
        opacity: 1;
        backdrop-filter: blur(8px);
    }
}

@keyframes modalSlideIn {
    from {
        transform: scale(0.9) translateY(40px);
        opacity: 0;
    }
    to {
        transform: scale(1) translateY(0);
        opacity: 1;
    }
}

/* Responsive - Adaptaciones móviles */
@media (max-width: 768px) {
    .modal.show {
        padding: 10px;
        align-items: flex-start;
        padding-top: 40px;
    }
    
    .modal-content {
        max-width: 100%;
        max-height: 95vh;
        border-radius: 20px;
    }
    
    .modal-header {
        padding: 25px 30px 20px;
    }
    
    .modal-title {
        font-size: 24px;
    }
    
    .modal-content form {
        padding: 30px 25px;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .images-grid {
        grid-template-columns: 1fr;
    }
    
    .image-upload {
        min-height: 150px;
        padding: 25px 15px;
    }
    
    .map-container {
        height: 280px;
    }
    
    .form-actions {
        flex-direction: column-reverse;
        gap: 15px;
    }
    
    .btn-secondary,
    .btn-primary {
        width: 100%;
        padding: 18px 24px;
    }
}

/* Estados de error y éxito */
.form-group.error input,
.form-group.error select,
.form-group.error textarea {
    border-color: #e53e3e;
    background: #fef5f5;
    box-shadow: 0 0 0 3px rgba(229, 62, 62, 0.1);
}

.form-group.success input,
.form-group.success select,
.form-group.success textarea {
    border-color: #38a169;
    background: #f0fff4;
    box-shadow: 0 0 0 3px rgba(56, 161, 105, 0.1);
}

/* Mensajes de estado */
.field-message {
    font-size: 13px;
    margin-top: 5px;
    padding: 8px 12px;
    border-radius: 8px;
    font-weight: 500;
}

.field-message.error {
    background: #fed7d7;
    color: #c53030;
    border: 1px solid #feb2b2;
}

.field-message.success {
    background: #c6f6d5;
    color: #2f855a;
    border: 1px solid #9ae6b4;
}

/* Indicadores de carga en inputs */
.form-group.loading {
    position: relative;
}

.form-group.loading::after {
    content: '';
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    width: 16px;
    height: 16px;
    border: 2px solid #e2e8f0;
    border-top: 2px solid var(--primary-color, #667eea);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: translateY(-50%) rotate(0deg); }
    100% { transform: translateY(-50%) rotate(360deg); }
}

/* Mejoras para el selector de idioma */
.form-group select {
    background-image: url('data:image/svg+xml;charset=US-ASCII,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 4 5"><path fill="%23666" d="M2 0L0 2h4zm0 5L0 3h4z"/></svg>');
    background-repeat: no-repeat;
    background-position: right 16px center;
    background-size: 12px;
    appearance: none;
    padding-right: 48px;
}

/* Placeholders mejorados */
.form-group input::placeholder,
.form-group textarea::placeholder {
    color: #a0aec0;
    font-style: italic;
    font-weight: 400;
}

/* Focus visible mejorado */
.form-group input:focus-visible,
.form-group select:focus-visible,
.form-group textarea:focus-visible,
.btn-secondary:focus-visible,
.btn-primary:focus-visible,
.close-btn:focus-visible {
    outline: 3px solid rgba(102, 126, 234, 0.5);
    outline-offset: 2px;
}

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #718096;
        }

        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        /* Location suggestions */
        .location-suggestions {
            animation: slideDown 0.2s ease-out;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 2px solid #e2e8f0;
            border-top: none;
            border-radius: 0 0 10px 10px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .suggestion-item {
            padding: 12px 15px;
            cursor: pointer;
            border-bottom: 1px solid #f1f1f1;
            transition: background-color 0.2s ease;
            font-size: 14px;
        }

        .suggestion-item:last-child {
            border-bottom: none;
        }

        .suggestion-item:hover {
            background-color: #f7fafc !important;
        }

        /* Loading indicator */
        .location-loading {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            width: 16px;
            height: 16px;
            border: 2px solid #e2e8f0;
            border-top: 2px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: translateY(-50%) rotate(0deg); }
            100% { transform: translateY(-50%) rotate(360deg); }
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

            .tabs-nav {
                flex-wrap: wrap;
            }

            .filters-section {
                flex-direction: column;
                align-items: stretch;
            }

            .search-input {
                min-width: auto;
            }

            .content-grid {
                grid-template-columns: 1fr;
            }

            .modal-content {
                margin: 10px;
                padding: 20px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .images-grid {
                grid-template-columns: 1fr;
            }
        }
      
        .image-count {
            position: absolute;
            top: 8px;
            right: 8px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }

        .card-category,
        .card-type,
        .card-transport {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 4px;
            font-size: 12px;
            color: #4a5568;
        }

        .image-preview.existing {
            border-color: #10b981 !important;
        }

        .image-preview.new {
            border-color: #3b82f6 !important;
        }

        .existing-image-indicator {
            background: #10b981 !important;
        }

        .new-image-indicator {
            background: #3b82f6 !important;
        }

        /* Hover effect para cards con imágenes */
        .item-card:hover .card-image img {
            transform: scale(1.05);
            transition: transform 0.3s ease;
        }

        .card-image {
            overflow: hidden;
        }

/* Botón flotante para Itinerarios */
.floating-itinerarios-btn {
   position: fixed;
   bottom: 30px;
   right: 30px;
   width: 60px;
   height: 60px;
   background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
   border: none;
   border-radius: 50%;
   cursor: pointer;
   box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
   z-index: 1000;
   display: flex;
   align-items: center;
   justify-content: center;
   transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
   overflow: hidden;
   text-decoration: none;
   color: white;
   font-size: 24px;
   backdrop-filter: blur(10px);
   border: 2px solid rgba(255, 255, 255, 0.2);
}

.floating-itinerarios-btn::before {
   content: '';
   position: absolute;
   top: 0;
   left: 0;
   right: 0;
   bottom: 0;
   background: linear-gradient(45deg, transparent 30%, rgba(255, 255, 255, 0.1) 50%, transparent 70%);
   transform: translateX(-100%);
   transition: transform 0.6s ease;
}

.floating-itinerarios-btn:hover::before {
   transform: translateX(100%);
}

.floating-itinerarios-btn:hover {
   width: 180px;
   border-radius: 30px;
   background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
   box-shadow: 0 12px 35px rgba(102, 126, 234, 0.6);
   transform: translateY(-3px) translateX(-60px);
}

.floating-itinerarios-btn .btn-icon {
   font-size: 24px;
   transition: all 0.4s ease;
   position: relative;
   z-index: 1;
}

.floating-itinerarios-btn .btn-text {
   position: absolute;
   right: 20px;
   font-weight: 600;
   font-size: 14px;
   white-space: nowrap;
   opacity: 0;
   transform: translateX(10px);
   transition: all 0.4s ease;
   z-index: 1;
   letter-spacing: 0.5px;
}

.floating-itinerarios-btn:hover .btn-text {
   opacity: 1;
   transform: translateX(0);
}

.floating-itinerarios-btn:hover .btn-icon {
   transform: translateX(-50px) scale(1.1);
}

.floating-itinerarios-btn:active {
   transform: translateY(-1px) scale(0.95);
   box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

/* Animación de pulso sutil */
@keyframes gentlePulse {
   0%, 100% {
       box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
   }
   50% {
       box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6);
   }
}

.floating-itinerarios-btn {
   animation: gentlePulse 3s ease-in-out infinite;
}

.floating-itinerarios-btn:hover {
   animation: none;
}

/* Responsive */
@media (max-width: 768px) {
   .floating-itinerarios-btn {
       bottom: 20px;
       right: 20px;
       width: 50px;
       height: 50px;
   }
   
   .floating-itinerarios-btn .btn-icon {
       font-size: 20px;
   }
   
   .floating-itinerarios-btn:hover {
       width: 150px;
       border-radius: 25px;
       transform: translateY(-3px) translateX(-50px);
   }
   
   .floating-itinerarios-btn .btn-text {
       right: 15px;
       font-size: 13px;
   }
   
   .floating-itinerarios-btn:hover .btn-icon {
       transform: translateX(-40px) scale(1.1);
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
/* ESTILOS PARA UBICACIONES SECUNDARIAS */
.ubicaciones-secundarias-section {
    margin: 20px 0;
    padding: 20px;
    border: 2px dashed #e2e8f0;
    border-radius: 12px;
    background: #fafbfc;
    transition: all 0.3s ease;
}

.ubicaciones-secundarias-section.has-items {
    border-color: var(--primary-color, #667eea);
    border-style: solid;
    background: rgba(102, 126, 234, 0.02);
}

.ubicacion-secundaria-item {
    margin-bottom: 15px;
    padding: 15px;
    background: white;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    position: relative;
    animation: slideInUp 0.3s ease;
}

.ubicacion-input-group {
    display: flex;
    gap: 10px;
    align-items: center;
}

.ubicacion-secundaria-input {
    flex: 1;
    padding: 12px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s ease;
}

.ubicacion-secundaria-input:focus {
    outline: none;
    border-color: var(--primary-color, #667eea);
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.btn-remove-ubicacion {
    background: #fee;
    border: 1px solid #fcc;
    color: #c53030;
    padding: 8px 12px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 18px;
    min-width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-remove-ubicacion:hover {
    background: #fed7d7;
    transform: scale(1.1);
    box-shadow: 0 2px 8px rgba(197, 48, 48, 0.2);
}

.btn-add-ubicacion {
    background: var(--primary-gradient, linear-gradient(135deg, #667eea 0%, #764ba2 100%));
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-add-ubicacion:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.ubicacion-preview {
    margin-top: 8px;
    padding: 8px 12px;
    background: #f7fafc;
    border-radius: 6px;
    font-size: 12px;
    color: #4a5568;
    border-left: 3px solid var(--primary-color, #667eea);
    display: none;
}

.ubicacion-preview.show {
    display: block;
    animation: fadeInDown 0.3s ease;
}

@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeInDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
@keyframes slideOutUp {
    from {
        opacity: 1;
        transform: translateY(0);
    }
    to {
        opacity: 0;
        transform: translateY(-20px);
    }
}

@keyframes modalFadeIn {
    from {
        opacity: 0;
        backdrop-filter: blur(0px);
    }
    to {
        opacity: 1;
        backdrop-filter: blur(8px);
    }
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

/* Suggestions dropdown para ubicaciones secundarias */
.location-suggestions {
    font-family: inherit;
}

.suggestion-item:last-child {
    border-bottom: none;
}

/* Responsive para ubicaciones secundarias */
@media (max-width: 768px) {
    .ubicacion-input-group {
        flex-direction: column;
        gap: 8px;
    }
    
    .btn-remove-ubicacion {
        align-self: flex-end;
        min-width: 44px;
        height: 44px;
    }
    
    .ubicaciones-secundarias-section {
        padding: 15px;
    }
}
/* ===== SISTEMA DE CARGA MÚLTIPLE DE IMÁGENES ===== */
.multiple-image-upload-container {
    width: 100%;
}

.drop-zone-multiple {
    border: 3px dashed #cbd5e0;
    border-radius: 16px;
    padding: 40px 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    background: linear-gradient(135deg, #f8fafc 0%, #edf2f7 100%);
    margin-bottom: 20px;
}

.drop-zone-multiple:hover {
    border-color: var(--primary-color, #667eea);
    background: linear-gradient(135deg, #f0f4ff 0%, #e6f3ff 100%);
    transform: translateY(-2px);
}

.drop-zone-multiple.drag-over {
    border-color: var(--primary-color, #667eea);
    background: linear-gradient(135deg, #e6f3ff 0%, #dbeafe 100%);
    transform: scale(1.02);
}

.btn-select-images {
    background: var(--primary-color, #667eea);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 25px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-select-images:hover {
    background: var(--secondary-color, #764ba2);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.images-preview-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 20px;
}

.image-preview-item {
    position: relative;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
    background: white;
    transition: all 0.3s ease;
}

.image-preview-item:hover {
    border-color: var(--primary-color, #667eea);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.image-preview-item img {
    width: 100%;
    height: 150px;
    object-fit: cover;
}

.image-preview-info {
    padding: 12px;
    border-top: 1px solid #e2e8f0;
}

.image-preview-name {
    font-size: 13px;
    font-weight: 600;
    color: #2d3748;
    margin-bottom: 4px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.image-preview-size {
    font-size: 11px;
    color: #718096;
}

.image-remove-btn {
    position: absolute;
    top: 8px;
    right: 8px;
    background: rgba(229, 62, 62, 0.9);
    color: white;
    border: none;
    border-radius: 50%;
    width: 28px;
    height: 28px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    transition: all 0.3s ease;
}

.image-remove-btn:hover {
    background: #e53e3e;
    transform: scale(1.1);
}

.image-slot-indicator {
    position: absolute;
    bottom: 8px;
    right: 8px;
    background: rgba(102, 126, 234, 0.9);
    color: white;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 10px;
    font-weight: 600;
}
/* Estilos para imágenes existentes */
.existing-image {
    border-color: #10b981 !important;
}

.existing-image:hover {
    border-color: #059669 !important;
}
/* Contenedor para input con contador */



.input-with-counter {
    position: relative;
}

.textarea-with-counter {
    position: relative;
}

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

.textarea-with-counter .char-counter {
    top: 15px;
    transform: none;
}

.char-counter.warning {
    color: #f59e0b;
    background: rgba(252, 211, 77, 0.3);
}

.char-counter.danger {
    color: #ef4444;
    background: rgba(254, 226, 226, 0.9);
}

.input-with-counter input {
    padding-right: 60px;
}

.textarea-with-counter textarea {
    width: 100% !important;
    box-sizing: border-box;
    padding-right: 50px !important;
}



/* Responsive */
@media (max-width: 768px) {
    .char-counter {
        font-size: 10px;
        padding: 1px 4px;
        right: 8px;
    }
    
    .input-with-counter input {
        padding-right: 50px;
    }
}
.textarea-with-counter {
    position: relative;
}

.textarea-with-counter .char-counter {
    top: 15px;
    transform: none;
}

.textarea-with-counter textarea {
    width: 100% !important;
    box-sizing: border-box;
    padding-right: 50px !important;
}

/* Estilos para ubicaciones mejorados */
.ubicacion-input-wrapper {
    position: relative;
    width: 100%;
}

.ubicacion-input-wrapper input.form-control {
    width: 100% !important;
    padding: 12px 16px !important;
    border: 2px solid #e2e8f0 !important;
    border-radius: 10px !important;
    font-size: 14px !important;
    transition: all 0.3s ease !important;
}

.ubicacion-input-wrapper input.form-control:focus {
    outline: none !important;
    border-color: #4299e1 !important;
    box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1) !important;
}

.ubicacion-item {
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Asegurar que el grid del formulario no rompa las ubicaciones */
.form-group[style*="grid-column: 1 / -1"] {
    width: 100%;
}

/* Botón de agregar ubicación hover */
.btn-add-ubicacion:active {
    transform: scale(0.98);
}
    </style>
    <script src="<?= APP_URL ?>/assets/js/ubicacion-search-widget.js"></script>
</head>
<body>
    <!-- Header con componentes -->
    <?= UIComponents::renderHeader($user) ?>

    <!-- Sidebar con componentes -->
    <?= UIComponents::renderSidebar($user, '/biblioteca') ?>

    <!-- Overlay -->
    <div class="overlay" id="overlay" onclick="closeSidebar()"></div>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <div class="tabs-container">
            <!-- Tabs Navigation -->
            <div class="tabs-nav">
                <button class="tab-btn active" data-tab="dias">Días</button>
                <button class="tab-btn" data-tab="alojamientos">Alojamientos</button>
                <button class="tab-btn" data-tab="actividades">Actividades</button>
                <button class="tab-btn" data-tab="transportes">Transportes</button>
            </div>

            <!-- Filters Section -->
            <div class="filters-section">
                <input type="text" class="search-input" placeholder="Buscar por título, descripción, ubicación..." id="searchInput">
                <select class="filter-select" id="languageFilter">
                    <option value="">Todos los idiomas</option>
                    <option value="es">Español</option>
                    <option value="en">English</option>
                    <option value="fr">Français</option>
                    <option value="pt">Português</option>
                </select>
                <button class="add-btn" onclick="openModal('create')">➕ Agregar Nuevo</button>
            </div>

            <!-- Content Grid -->
            <div class="content-grid" id="contentGrid">
                <!-- El contenido se carga dinámicamente aquí -->
            </div>

            <!-- Empty State -->
            <div class="empty-state" id="emptyState" style="display: none;">
                <div class="empty-state-icon">📂</div>
                <h3>No hay recursos disponibles</h3>
                <p>Comienza agregando tu primer recurso haciendo clic en "Agregar Nuevo"</p>
            </div>
        </div>
    </div>

    <!-- Modal para Crear/Editar -->
    <div class="modal" id="resourceModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle">Agregar Nuevo Día</h2>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>

            <form id="resourceForm">
                <input type="hidden" id="resourceId">
                <input type="hidden" id="resourceType">

                <!-- Formulario común -->
                <div class="form-grid">
                    <div class="form-group">
                        <label for="idioma">Idioma</label>
                        <select id="idioma" name="idioma" required>
                            <option value="es">Español</option>
                            <option value="en">English</option>
                            <option value="fr">Français</option>
                            <option value="pt">Português</option>
                        </select>
                    </div>
                </div>

                <!-- Campos específicos se cargan dinámicamente -->
                <div id="specificFields"></div>

                

                <!-- Botones de acción -->
                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal()">Cancelar</button>
                    <button type="submit" class="btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>


    <!-- Scripts -->
    <script>
        // Configuración global - SIN API KEYS
        const APP_URL = '<?= APP_URL ?>';
        const DEFAULT_LANGUAGE = '<?= $defaultLanguage ?>';

        let currentTab = 'dias';
        let caracteresConfigurados = false;
        let sidebarOpen = false;
        let resources = {
            dias: [],
            alojamientos: [],
            actividades: [],
            transportes: []
        };

        // Inicialización
        document.addEventListener('DOMContentLoaded', function() {
            initializeTabs();
            loadResources();
            setupSearch();
            initializeGoogleTranslate();
        });
        
        // Funciones de sidebar CORREGIDAS
        function toggleSidebar() {
            // Buscar por clase, no por ID
            const sidebar = document.querySelector('.enhanced-sidebar');
            const overlay = document.getElementById('overlay');
            const mainContent = document.getElementById('mainContent');
            
            // Debug para verificar elementos
            console.log('🔍 Elementos sidebar:', {
                sidebar: !!sidebar,
                overlay: !!overlay,
                mainContent: !!mainContent
            });
            
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
                console.log('✅ Sidebar abierto');
            } else {
                sidebar.classList.remove('open');
                if (overlay) overlay.classList.remove('show');
                if (mainContent) mainContent.classList.remove('sidebar-open');
                console.log('✅ Sidebar cerrado');
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

        // ============================================= 
        // NUEVA FUNCIÓN DE MAPA CON OPENSTREETMAP
        // ============================================= 
// ================================
// FUNCIONES PARA UBICACIONES SECUNDARIAS
// ================================

let ubicacionesSecundariasCount = 0;

function agregarUbicacionSecundaria() {
    const container = document.getElementById('ubicaciones-secundarias-container');
    if (!container) {
        console.error('❌ Contenedor de ubicaciones secundarias no encontrado');
        return;
    }
    
    const index = Date.now(); // ID único
    
    const div = document.createElement('div');
    div.className = 'ubicacion-item';
    div.dataset.index = index;
    div.style.cssText = `
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 10px;
        align-items: start;
        width: 100%;
    `;
    
    div.innerHTML = `
        <div class="ubicacion-input-wrapper" style="width: 100%;">
            <input type="text" 
                   name="ubicaciones_secundarias[]" 
                   id="ubicacion-${index}"
                   class="form-control ubicacion-input"
                   placeholder="🔍 Buscar otra ubicación..."
                   style="width: 100%; padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 14px;">
            <input type="hidden" name="ubicaciones_secundarias_lat[]" id="latitud-${index}">
            <input type="hidden" name="ubicaciones_secundarias_lng[]" id="longitud-${index}">
            <div id="preview-ubicacion-${index}"></div>
        </div>
        
        <button type="button" 
                onclick="removerUbicacionSecundaria(${index})"
                class="btn-remove-ubicacion"
                style="
                    background: #e53e3e;
                    color: white;
                    border: none;
                    border-radius: 8px;
                    width: 40px;
                    height: 40px;
                    cursor: pointer;
                    font-size: 18px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    transition: all 0.2s;
                    flex-shrink: 0;
                "
                onmouseover="this.style.background='#c53030'"
                onmouseout="this.style.background='#e53e3e'"
                title="Eliminar ubicación">
            ✕
        </button>
    `;
    
    container.appendChild(div);
    
    // Inicializar array si no existe
    if (typeof widgetsUbicacionesSecundarias === 'undefined') {
        widgetsUbicacionesSecundarias = [];
    }
    
    // Inicializar widget para esta ubicación
    setTimeout(() => {
        const input = document.getElementById(`ubicacion-${index}`);
        if (input) {
            const widget = new UbicacionSearchWidget(input, {
                apiUrl: '<?= APP_URL ?>/modules/ubicaciones/ubicaciones_api.php', // ← URL CORRECTA CON PHP
                latInputId: `latitud-${index}`,
                lngInputId: `longitud-${index}`,
                placeholder: '🔍 Buscar otra ubicación...',
                showPreview: true,
                previewContainerId: `preview-ubicacion-${index}`,
                autoSave: true
            });
            
            widgetsUbicacionesSecundarias.push({
                index: index,
                widget: widget
            });
            
            console.log(`✅ Widget secundario inicializado para index ${index}`);
        }
    }, 100);
}

function removerUbicacionSecundaria(index) {
    const item = document.querySelector(`.ubicacion-item[data-index="${index}"]`);
    if (item) {
        // Destruir widget
        const widgetData = widgetsUbicacionesSecundarias.find(w => w.index === index);
        if (widgetData && widgetData.widget) {
            widgetData.widget.destroy();
        }
        
        // Remover del array
        widgetsUbicacionesSecundarias = widgetsUbicacionesSecundarias.filter(w => w.index !== index);
        
        // Remover del DOM
        item.remove();
        
        console.log(`✅ Ubicación eliminada`);
    }
}

function removerUbicacionSecundaria(index) {
    const item = document.querySelector(`.ubicacion-secundaria-item[data-index="${index}"]`);
    if (item) {
        item.style.animation = 'slideOutUp 0.3s ease';
        setTimeout(() => {
            item.remove();
            updateSecondaryLocationsSectionClass();
        }, 300);
    }
    console.log('🗑️ Ubicación secundaria removida:', index);
}

function getUbicacionSecundariaTemplate(index) {
    console.log(`📝 Generando template para índice: ${index}`);
    
    return `
        <div class="ubicacion-secundaria-item" data-index="${index}">
            <div class="ubicacion-input-group">
                <input type="text" 
                       name="ubicaciones_secundarias[]" 
                       placeholder="Ej: Plaza de Bolívar, Museo del Oro, Zona Rosa..."
                       class="form-control ubicacion-secundaria-input"
                       data-index="${index}"
                       id="ubicacion-secundaria-${index}">
                <input type="hidden" name="ubicaciones_secundarias_lat[]" 
                       class="lat-input" id="lat-secundaria-${index}">
                <input type="hidden" name="ubicaciones_secundarias_lng[]" 
                       class="lng-input" id="lng-secundaria-${index}">
                <button type="button" class="btn-remove-ubicacion" 
                        onclick="removerUbicacionSecundaria(${index})" 
                        title="Eliminar ubicación">
                    🗑️
                </button>
            </div>
            <div class="ubicacion-preview" id="preview-secundaria-${index}"></div>
        </div>
    `;
}

function initializeLocationAutocompleteSecundaria(index) {
    console.log(`Configurando autocompletado para ubicación secundaria ${index}`);
    
    const input = document.getElementById(`ubicacion-secundaria-${index}`);
    const latInput = document.getElementById(`lat-secundaria-${index}`);
    const lngInput = document.getElementById(`lng-secundaria-${index}`);
    const preview = document.getElementById(`preview-secundaria-${index}`);
    
    if (!input) {
        console.error(`No se encontró input: ubicacion-secundaria-${index}`);
        return false;
    }
    
    let searchTimeout;
    
    input.addEventListener('input', function() {
        const query = this.value.trim();
        
        clearTimeout(searchTimeout);
        clearSecondaryLocationSuggestions(this);
        
        if (query.length < 3) return;
        
        // Mostrar indicador de búsqueda
        this.style.backgroundImage = 'linear-gradient(45deg, #f0f0f0 25%, transparent 25%)';
        
        searchTimeout = setTimeout(() => {
            searchSecondaryLocation(query, this, latInput, lngInput, preview, index);
        }, 400);
    });
    
    input.addEventListener('blur', function() {
        setTimeout(() => {
            clearSecondaryLocationSuggestions(this);
            this.style.backgroundImage = '';
        }, 200);
    });
    
    console.log(`Autocompletado configurado para ubicación secundaria ${index}`);
    return true;
}

function searchLocationForSecondary(query, inputElement, latInput, lngInput, preview, index) {
    console.log(`🔍 Buscando ubicación para secundaria ${index}:`, query);
    
    // Mostrar indicador de carga
    inputElement.style.backgroundImage = 'url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'16\' height=\'16\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%23667eea\' stroke-width=\'2\'%3E%3Cpath d=\'M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z\'/%3E%3Ccircle cx=\'12\' cy=\'10\' r=\'3\'/%3E%3C/svg%3E")';
    inputElement.style.backgroundRepeat = 'no-repeat';
    inputElement.style.backgroundPosition = 'right 12px center';
    inputElement.style.backgroundSize = '16px 16px';
    
    // Usar Nominatim para búsqueda
    const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=5&countrycodes=co,es,fr,us,it,mx,pe,ar,cl&addressdetails=1&accept-language=es`;
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            console.log(`📍 Resultados para secundaria ${index}:`, data.length);
            
            // Limpiar indicador de carga
            inputElement.style.backgroundImage = '';
            
            if (data && data.length > 0) {
                showSuggestionsForSecondary(data, inputElement, latInput, lngInput, preview, index);
            } else {
                console.log(`ℹ️ No se encontraron resultados para: ${query}`);
            }
        })
        .catch(error => {
            console.error('❌ Error buscando ubicación:', error);
            inputElement.style.backgroundImage = '';
        });
}

function showSuggestionsForSecondary(locations, inputElement, latInput, lngInput, preview, index) {
    console.log(`📋 Mostrando ${locations.length} sugerencias para secundaria ${index}`);
    
    // Limpiar sugerencias anteriores
    hideSuggestionsForElement(inputElement);
    
    const suggestionsContainer = document.createElement('div');
    suggestionsContainer.className = `location-suggestions secondary-suggestions-${index}`;
    suggestionsContainer.style.cssText = `
        position: absolute;
        top: 100%;
        left: 0;
        right: 40px;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        z-index: 1000;
        max-height: 200px;
        overflow-y: auto;
    `;
    
    locations.forEach((location, suggestionIndex) => {
        const item = document.createElement('div');
        item.className = 'suggestion-item';
        item.style.cssText = `
            padding: 12px;
            cursor: pointer;
            border-bottom: 1px solid #f7fafc;
            transition: background 0.2s ease;
        `;
        
        const mainName = location.display_name.split(',')[0];
        item.innerHTML = `
            <div style="font-weight: 600; color: #2d3748;">${mainName}</div>
            <div style="font-size: 12px; color: #718096;">${location.display_name}</div>
        `;
        
        // Hover effects
        item.addEventListener('mouseenter', () => {
            item.style.background = '#f7fafc';
        });
        
        item.addEventListener('mouseleave', () => {
            item.style.background = 'white';
        });
        
        // Click handler
        item.addEventListener('click', () => {
            console.log(`✅ Ubicación secundaria ${index} seleccionada:`, location.display_name);
            
            inputElement.value = location.display_name;
            latInput.value = location.lat;
            lngInput.value = location.lon;
            
            // Mostrar preview
            showLocationPreview(preview, location.display_name);
            
            // Limpiar sugerencias
            hideSuggestionsForElement(inputElement);
        });
        
        suggestionsContainer.appendChild(item);
    });
    
    // Posicionar las sugerencias
    inputElement.parentElement.style.position = 'relative';
    inputElement.parentElement.appendChild(suggestionsContainer);
    
    console.log(`✅ Sugerencias mostradas para secundaria ${index}`);
}

function hideSuggestionsForElement(inputElement) {
    if (!inputElement || !inputElement.parentElement) return;
    
    const suggestions = inputElement.parentElement.querySelector('.location-suggestions');
    if (suggestions) {
        suggestions.remove();
    }
}


function setupLocationAutocompleteForElement(input, latInput, lngInput, preview) {
    if (!input) {
        console.error('❌ setupLocationAutocompleteForElement: input no válido');
        return;
    }
    
    console.log('🔧 Configurando autocompletado para:', input.id || input.name || 'input sin id');
    
    let timeout;
    let suggestionsList = null;
    
    input.addEventListener('input', function() {
        clearTimeout(timeout);
        const query = this.value.trim();
        
        console.log('📝 Usuario escribiendo:', query, 'en campo:', this.id);
        
        // Limpiar sugerencias anteriores
        removeSuggestionsFromInput(this);
        
        if (query.length < 3) {
            return;
        }
        
        timeout = setTimeout(() => {
            console.log('🔍 Iniciando búsqueda para:', query);
            searchLocationForInput(query, this, latInput, lngInput, preview);
        }, 500);
    });
    
    input.addEventListener('blur', function() {
        setTimeout(() => {
            removeSuggestionsFromInput(this);
        }, 200);
    });
    
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            removeSuggestionsFromInput(this);
        }
    });
    
    console.log('✅ Autocompletado configurado correctamente para:', input.id);
}


// 🧠 FUNCIÓN PARA GENERAR CONSULTAS INTELIGENTES
function generateSmartQueries(originalQuery) {
    const queries = [];
    const cleanQuery = originalQuery.trim().toLowerCase();
    
    // 1. Búsqueda original
    queries.push(cleanQuery);
    
    // 2. Si tiene espacios, buscar sin espacios y con variaciones
    if (cleanQuery.includes(' ')) {
        queries.push(cleanQuery.replace(/\s+/g, ''));
        queries.push(cleanQuery.split(' ').reverse().join(' ')); // Invertir orden
    }
    
    // 3. Agregar contexto geográfico si no lo tiene
    const geoTerms = ['colombia', 'españa', 'francia', 'méxico', 'argentina'];
    const hasGeoContext = geoTerms.some(term => cleanQuery.includes(term));
    
    if (!hasGeoContext) {
        queries.push(`${cleanQuery} colombia`);
        queries.push(`${cleanQuery} españa`);
        queries.push(`${cleanQuery} francia`);
    }
    
    // 4. Búsquedas específicas por tipo de lugar
    const placeTypes = ['plaza', 'museo', 'parque', 'centro', 'zona'];
    const hasPlaceType = placeTypes.some(type => cleanQuery.includes(type));
    
    if (!hasPlaceType && cleanQuery.length > 3) {
        queries.push(`plaza ${cleanQuery}`);
        queries.push(`centro ${cleanQuery}`);
        queries.push(`${cleanQuery} centro`);
    }
    
    // 5. Remover duplicados y retornar máximo 6 consultas
    return [...new Set(queries)].slice(0, 6);
}

// 🔍 FUNCIÓN PARA REALIZAR UNA BÚSQUEDA INDIVIDUAL
function performSingleSearch(query) {
    const url = `https://nominatim.openstreetmap.org/search?` + 
        `format=json&` +
        `q=${encodeURIComponent(query)}&` +
        `limit=8&` +
        `countrycodes=co,es,fr,us,it,mx,pe,ar,cl,br,ve&` +
        `addressdetails=1&` +
        `accept-language=es&` +
        `extratags=1&` +
        `namedetails=1`;
    
    return fetch(url)
        .then(response => response.json())
        .catch(error => {
            console.warn('⚠️ Error en búsqueda individual:', error);
            return [];
        });
}

// 🔄 FUNCIÓN PARA COMBINAR Y FILTRAR RESULTADOS
function combineAndFilterResults(resultsArray, originalQuery) {
    const allResults = [];
    const seenPlaces = new Set();
    
    // Combinar todos los resultados
    resultsArray.forEach(results => {
        if (Array.isArray(results)) {
            allResults.push(...results);
        }
    });
    
    // Filtrar duplicados basados en coordenadas
    const uniqueResults = allResults.filter(result => {
        const key = `${Math.round(result.lat * 1000)}_${Math.round(result.lon * 1000)}`;
        if (seenPlaces.has(key)) {
            return false;
        }
        seenPlaces.add(key);
        return true;
    });
    
    // Scoring y ordenamiento inteligente
    const scoredResults = uniqueResults.map(result => {
        let score = 0;
        const displayName = result.display_name.toLowerCase();
        const query = originalQuery.toLowerCase();
        
        // Puntuación por relevancia
        if (displayName.includes(query)) score += 10;
        if (displayName.startsWith(query)) score += 15;
        if (result.class === 'place') score += 5;
        if (result.type === 'city' || result.type === 'town') score += 8;
        if (result.importance) score += result.importance * 10;
        
        // Penalizar resultados muy lejanos o irrelevantes
        if (result.type === 'road' && !query.includes('calle') && !query.includes('carrera')) score -= 3;
        
        return { ...result, score };
    });
    
    // Ordenar por puntuación y retornar los mejores 10
    return scoredResults
        .sort((a, b) => b.score - a.score)
        .slice(0, 10);
}

// 📭 FUNCIÓN PARA MOSTRAR MENSAJE CUANDO NO HAY RESULTADOS
function showNoResultsMessage(inputElement) {
    const parent = inputElement.parentElement;
    const messageContainer = document.createElement('div');
    messageContainer.className = 'no-results-message';
    messageContainer.style.cssText = `
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: #fef5e7;
        border: 2px solid #fbd38d;
        border-radius: 0 0 8px 8px;
        padding: 12px 15px;
        z-index: 2000;
        font-size: 13px;
        color: #d69e2e;
        text-align: center;
    `;
    
    messageContainer.innerHTML = `
        <div style="display: flex; align-items: center; justify-content: center; gap: 8px;">
            <span>🔍</span>
            <span>No se encontraron resultados. Intenta con otro término.</span>
        </div>
    `;
    
    parent.style.position = 'relative';
    parent.appendChild(messageContainer);
    
    // Remover el mensaje después de 3 segundos
    setTimeout(() => {
        if (messageContainer.parentElement) {
            messageContainer.remove();
        }
    }, 3000);
}



function removeSuggestionsFromInput(inputElement) {
    const parent = inputElement.parentElement;
    if (parent) {
        const suggestions = parent.querySelector('.location-suggestions-universal');
        if (suggestions) {
            suggestions.remove();
        }
    }
}


function showLocationSuggestions(locations, inputElement, latInput, lngInput, preview) {
    hideSuggestions(inputElement);
    
    if (locations.length === 0) return;
    
    const suggestionsContainer = document.createElement('div');
    suggestionsContainer.className = 'location-suggestions';
    suggestionsContainer.style.cssText = `
        position: absolute;
        top: 100%;
        left: 0;
        right: 40px;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        z-index: 1000;
        max-height: 200px;
        overflow-y: auto;
    `;
    
    locations.forEach(location => {
        const item = document.createElement('div');
        item.className = 'suggestion-item';
        item.style.cssText = `
            padding: 12px;
            cursor: pointer;
            border-bottom: 1px solid #f7fafc;
            transition: background 0.2s ease;
        `;
        item.innerHTML = `
            <div style="font-weight: 600; color: #2d3748;">${location.display_name.split(',')[0]}</div>
            <div style="font-size: 12px; color: #718096;">${location.display_name}</div>
        `;
        
        item.addEventListener('mouseenter', () => {
            item.style.background = '#f7fafc';
        });
        
        item.addEventListener('mouseleave', () => {
            item.style.background = 'white';
        });
        
        item.addEventListener('click', () => {
            console.log('✅ Ubicación seleccionada:', location.display_name);
            
            inputElement.value = location.display_name;
            latInput.value = location.lat;
            lngInput.value = location.lon;
            
            showLocationPreview(preview, location.display_name);
            hideSuggestions(inputElement);
        });
        
        suggestionsContainer.appendChild(item);
    });
    
    inputElement.parentElement.style.position = 'relative';
    inputElement.parentElement.appendChild(suggestionsContainer);
}


function hideSuggestions(inputElement) {
    const suggestions = inputElement.parentElement.querySelector('.location-suggestions');
    if (suggestions) {
        suggestions.remove();
    }
}

function showLocationPreview(preview, locationText) {
    if (preview && locationText) {
        preview.innerHTML = `📍 ${locationText}`;
        preview.classList.add('show');
    }
}

function updateSecondaryLocationsSectionClass() {
    const container = document.getElementById('ubicaciones-secundarias-container');
    const section = document.getElementById('ubicaciones-secundarias-section');
    const hasItems = container.children.length > 0;
    
    if (hasItems) {
        section.classList.add('has-items');
    } else {
        section.classList.remove('has-items');
        section.style.display = 'none';
    }
}

function limpiarUbicacionesSecundarias() {
    console.log('🧹 Limpiando ubicaciones secundarias...');
    
    const container = document.getElementById('ubicaciones-secundarias-container');
    const section = document.getElementById('ubicaciones-secundarias-section');
    
    if (container) {
        container.innerHTML = '';
    }
    
    if (section) {
        section.style.display = 'none';
        section.classList.remove('has-items');
    }
    
    // RESETEAR el contador a 0
    ubicacionesSecundariasCount = 0;
    
    console.log('✅ Ubicaciones secundarias limpiadas. Contador reseteado a 0');
}


        // Configuración de tabs
        function initializeTabs() {
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    // Actualizar tabs activos
                    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Cambiar contenido
                    currentTab = this.dataset.tab;
                    loadResources();
                });
            });
        }

        // MODIFICAR la función loadResources existente
        async function loadResources() {
            const grid = document.getElementById('contentGrid');
            const emptyState = document.getElementById('emptyState');
            
            try {
                // Indicador de carga más sutil
                grid.innerHTML = `
                    <div style="grid-column: 1/-1; text-align: center; padding: 20px;">
                        <div style="display: inline-flex; align-items: center; gap: 10px; background: white; padding: 15px 25px; border-radius: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                            <div style="width: 16px; height: 16px; border: 2px solid #e2e8f0; border-top: 2px solid var(--primary-color); border-radius: 50%; animation: spin 1s linear infinite;"></div>
                            <span>Buscando recursos...</span>
                        </div>
                    </div>
                `;
                
                const params = new URLSearchParams({
                    action: 'list',
                    type: currentTab
                });
                
                const search = document.getElementById('searchInput').value.trim();
                const language = document.getElementById('languageFilter').value;
                
                if (search) params.append('search', search);
                if (language) params.append('language', language);
                
                const response = await fetch(`${APP_URL}/biblioteca/api?${params}`, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                
                if (!result.success) {
                    throw new Error(result.error || 'Error desconocido');
                }
                
                resources[currentTab] = result.data || [];
                
                // Si hay filtros activos, mostrar resultados filtrados
                if (search || language) {
                    renderFilteredResults(resources[currentTab]);
                } else {
                    renderResources();
                }
                
            } catch (error) {
                console.error('Error al cargar recursos:', error);
                showSearchError(error.message);
            }
        }

        // AGREGAR esta función
        function showSearchError(message) {
            const grid = document.getElementById('contentGrid');
            grid.innerHTML = `
                <div style="grid-column: 1/-1; text-align: center; padding: 40px; background: #fef2f2; border-radius: 15px; border: 1px solid #fecaca;">
                    <div style="font-size: 48px; margin-bottom: 20px;">⚠️</div>
                    <h3 style="color: #dc2626; margin-bottom: 10px;">Error en la búsqueda</h3>
                    <p style="color: #b91c1c; margin-bottom: 20px;">${message}</p>
                    <button onclick="loadResources()" style="background: #dc2626; color: white; border: none; padding: 12px 24px; border-radius: 25px; cursor: pointer; font-weight: 500;">
                        🔄 Intentar de nuevo
                    </button>
                </div>
            `;
        }

        // Modificar SOLO esta parte de renderResources()
        function renderResources() {
            const grid = document.getElementById('contentGrid');
            const emptyState = document.getElementById('emptyState');
            
            try {
                if (!resources[currentTab] || resources[currentTab].length === 0) {
                    grid.style.display = 'none';
                    emptyState.style.display = 'block';
                    emptyState.innerHTML = `
                        <div class="empty-state-icon">📂</div>
                        <h3>No hay recursos disponibles</h3>
                        <p>Comienza agregando tu primer recurso haciendo clic en "Agregar Nuevo"</p>
                    `;
                    return;
                }

                grid.style.display = 'grid';
                emptyState.style.display = 'none';
                
                // AGREGAR ESTA LÍNEA - Aplicar filtros si hay alguno activo
                const search = document.getElementById('searchInput').value.trim();
                const language = document.getElementById('languageFilter').value;
                
                if (search || language) {
                    filtrarRecursos();
                    return;
                }
                
                grid.innerHTML = resources[currentTab].map(item => {
                    return createResourceCard(item);
                }).join('');
                reinitCarousels();
                
            } catch (error) {
                console.error('Error al renderizar recursos:', error);
                grid.innerHTML = `
                    <div style="grid-column: 1/-1; text-align: center; padding: 40px; background: #fed7d7; border-radius: 15px;">
                        <div style="font-size: 48px; margin-bottom: 20px;">⚠️</div>
                        <h3 style="color: #e53e3e;">Error al mostrar recursos</h3>
                        <p style="color: #c53030;">${error.message}</p>
                    </div>
                `;
            }
        }

        // Función para limpiar filtros
        function clearFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('languageFilter').value = '';
            loadResources();
        }

        // NUEVA FUNCIÓN: Obtener imagen principal
function getPrimaryImage(item, type) {
    switch(type) {
        case 'dias':
        case 'actividades':
            return item.imagen1 || item.imagen2 || item.imagen3 || null;
        case 'alojamientos':
            return item.imagen || null;
        default:
            return null;
    }
}
// NUEVA FUNCIÓN: Contar imágenes
function getImageCount(item, type) {
    let count = 0;
    switch(type) {
        case 'dias':
        case 'actividades':
            if (item.imagen1) count++;
            if (item.imagen2) count++;
            if (item.imagen3) count++;
            break;
        case 'alojamientos':
            if (item.imagen) count++;
            break;
    }
    return count;
}

// NUEVA FUNCIÓN: Escape HTML
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

        // Crear card de recurso
        // Crear card de recurso
        function createResourceCard(item) {
            const icons = {
                dias: '📅',
                alojamientos: '🏨',
                actividades: '🎯',
                transportes: '🚗'
            };

            const title = item.titulo || item.nombre || 'Sin título';
            const location = item.ubicacion || `${item.lugar_salida} → ${item.lugar_llegada}` || '';
            
            // ✅ NUEVO: Crear carrusel para días y actividades
            let imageHTML = '';
            if (currentTab === 'dias' || currentTab === 'actividades') {
                const images = [];
                if (item.imagen1) images.push(item.imagen1);
                if (item.imagen2) images.push(item.imagen2);
                if (item.imagen3) images.push(item.imagen3);
                
                if (images.length > 0) {
                    imageHTML = createCarouselHTML(images);
                } else {
                    imageHTML = `<div class="card-image">${icons[currentTab]}</div>`;
                }
            } else {
                // Para alojamientos y transportes, usar lógica anterior
                const primaryImage = getPrimaryImage(item, currentTab);
                imageHTML = `
                    <div class="card-image">
                        ${primaryImage ? 
                            `<img src="${primaryImage}" alt="${title}" style="width: 100%; height: 100%; object-fit: cover;">` : 
                            icons[currentTab]
                        }
                        ${getImageCount(item, currentTab) > 0 ? `<div class="image-count">📷 ${getImageCount(item, currentTab)}</div>` : ''}
                    </div>
                `;
            }
            
            return `
                <div class="item-card" onclick="editResource(${item.id})">
                    ${imageHTML}
                    <div class="card-content">
                        <h3 class="card-title">${escapeHtml(title)}</h3>
                        <p class="card-description">${escapeHtml(item.descripcion || 'Sin descripción')}</p>
                        <div class="card-location">
                            📍 ${escapeHtml(location)}
                            ${item.ubicaciones_secundarias && item.ubicaciones_secundarias.length > 0 ? 
                                `<div style="font-size: 11px; color: #10b981; margin-top: 4px; font-weight: 600;">
                                    + ${item.ubicaciones_secundarias.length} ubicación(es) más
                                </div>` : ''
                            }
                        </div>
                        ${item.categoria ? `<div class="card-category">⭐ ${item.categoria} estrellas</div>` : ''}
                        ${item.tipo ? `<div class="card-type">🏷️ ${item.tipo}</div>` : ''}
                        ${item.medio ? `<div class="card-transport">🚗 ${item.medio}</div>` : ''}
                    </div>
                    <div class="card-actions">
                        <button class="action-btn edit" onclick="event.stopPropagation(); editResource(${item.id})">
                            ✏️ Editar
                        </button>
                        <button class="action-btn delete" onclick="event.stopPropagation(); deleteResource(${item.id})">
                            🗑️ Eliminar
                        </button>
                    </div>
                </div>
            `;
        }

        // Funciones del modal
        
// Funciones del modal
function openModal(mode, id = null) {
    console.log('🎬 OpenModal:', { mode, id, currentTab });
    
    const modal = document.getElementById('resourceModal');
    const title = document.getElementById('modalTitle');
    
    if (!modal || !title) {
        console.error('❌ Modal o título no encontrado');
        return;
    }
    
    // Configurar título según el tab activo y el modo
    const titles = {
        dias: mode === 'create' ? 'Agregar Nuevo Día' : 'Editar Día',
        alojamientos: mode === 'create' ? 'Agregar Nuevo Alojamiento' : 'Editar Alojamiento',
        actividades: mode === 'create' ? 'Agregar Nueva Actividad' : 'Editar Actividad',
        transportes: mode === 'create' ? 'Agregar Nuevo Transporte' : 'Editar Transporte'
    };
    
    title.textContent = titles[currentTab] || 'Agregar Recurso';
    document.getElementById('resourceType').value = currentTab;
    document.getElementById('resourceId').value = id || '';
    
    // Resetear flag de contadores ANTES de cargar campos
    caracteresConfigurados = false;
    
    // Cargar campos específicos del tipo de recurso
    loadSpecificFields();
    
    // Mostrar el modal
    modal.classList.add('show');
    console.log('✅ Modal mostrado');
    
    // Inicializar sistema de imágenes para días y actividades
    if (currentTab === 'dias' || currentTab === 'actividades') {
        setTimeout(() => {
            if (mode === 'create') {
                console.log(`🖼️ Inicializando imágenes para ${currentTab}`);
                initImageUploadSystem(currentTab, null, []);
            }
        }, 300);
    }
    
    // Si es modo edición, cargar los datos del recurso
    if (mode === 'edit' && id) {
        console.log(`📝 Cargando datos del recurso ID: ${id}`);
        loadResourceData(id);
    }
}

        function closeModal() {
            caracteresConfigurados = false;
            const modal = document.getElementById('resourceModal');
            modal.classList.remove('show');
            
            // Limpiar formulario
            document.getElementById('resourceForm').reset();
            
            // AGREGAR ESTA LÍNEA:
            limpiarUbicacionesSecundarias();
          
            // AGREGAR ESTA LÍNEA:
            limpiarSistemaCargaMultiple();
            // Limpiar sistema de imágenes moderno
            if (typeof cleanupImageSystem === 'function') {
                cleanupImageSystem();
                console.log('✅ Sistema de imágenes moderno limpiado');
            }
           
        }
        // NUEVA FUNCIÓN: Limpiar sistema de carga múltiple
function limpiarSistemaCargaMultiple() {
    console.log('🧹 Limpiando sistema de carga múltiple...');
    
    // Limpiar array global
    window.selectedImages = [];
    
    // Limpiar contenedor de preview
    const previewContainer = document.getElementById('imagesPreviewContainer');
    if (previewContainer) {
        previewContainer.innerHTML = '';
    }
    
  
    
    console.log('✅ Sistema de carga múltiple limpiado');
}
        

        // Submit del formulario - CORREGIDO PARA MANEJAR IMÁGENES
document.getElementById('resourceForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    
    try {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Guardando...';
        
        // Crear FormData para manejar archivos
        const formData = new FormData(this);
        
        const id = document.getElementById('resourceId').value;
        const type = document.getElementById('resourceType').value;
        
        if (id) {
            formData.append('action', 'update');
            formData.append('id', id);
        } else {
            formData.append('action', 'create');
        }
        
        formData.append('type', type);
        
        // ✅ AGREGAR ESTAS LÍNEAS AQUÍ:
        // Si es días o actividades, aplicar orden y obtener archivos
        if (type === 'dias' || type === 'actividades') {
            // Aplicar orden de imágenes si se reordenaron
            if (typeof applyImageOrder === 'function') {
                applyImageOrder();
            }
            
            // Obtener archivos del sistema de imágenes
            if (typeof getFilesForSubmit === 'function') {
                const imageFiles = getFilesForSubmit();
                
                // Agregar archivos al FormData
                Object.keys(imageFiles).forEach(fieldName => {
                    formData.append(fieldName, imageFiles[fieldName]);
                    console.log(`📤 Agregando archivo ${fieldName} al FormData`);
                });
            }
        }
        
        // Realizar petición
        const response = await fetch(`${APP_URL}/biblioteca/api`, {
            method: 'POST',
            body: formData // No establecer Content-Type, el navegador lo hará automáticamente
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.error || 'Error desconocido');
        }
        
        // Éxito
        alert(result.message || 'Operación exitosa');
        closeModal();
        loadResources();
        
    } catch (error) {
        console.error('Error:', error);
        alert('Error al guardar: ' + error.message);
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    }
});

// Función mejorada para manejar la vista previa de imágenes
function setupImagePreviews() {
    // Configurar vista previa para todos los inputs de imagen
    const imageInputs = document.querySelectorAll('input[type="file"][accept*="image"]');
    
    imageInputs.forEach(input => {
        input.addEventListener('change', function() {
            handleImagePreview(this);
        });
    });
    // ✅ CONFIGURAR DRAG & DROP PARA ALOJAMIENTOS
const alojamientoUpload = document.getElementById('alojamiento-image-upload');
const alojamientoInput = document.getElementById('imagen');

if (alojamientoUpload && alojamientoInput) {
    // Click en el área
    alojamientoUpload.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-remove-preview')) return;
        alojamientoInput.click();
    });
    
    // Drag & Drop
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        alojamientoUpload.addEventListener(eventName, function(e) {
            e.preventDefault();
            e.stopPropagation();
        });
    });
    
    ['dragenter', 'dragover'].forEach(eventName => {
        alojamientoUpload.addEventListener(eventName, function() {
            this.style.borderColor = '#667eea';
            this.style.background = 'linear-gradient(135deg, #f0f4ff 0%, #e6f3ff 100%)';
            this.style.transform = 'scale(1.02)';
        });
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        alojamientoUpload.addEventListener(eventName, function() {
            this.style.borderColor = '';
            this.style.background = '';
            this.style.transform = '';
        });
    });
    
    alojamientoUpload.addEventListener('drop', function(e) {
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            alojamientoInput.files = files;
            handleImagePreview(alojamientoInput);
        }
    });
    
    // Change event
    alojamientoInput.addEventListener('change', function() {
        handleImagePreview(this);
    });
}
    setTimeout(() => {
    if (document.getElementById('dropZoneMultiple')) {
        initializeMultipleImageUpload();
    }
}, 100);
}

// Función mejorada para manejar la vista previa de imágenes
function handleImagePreview(input) {
    const file = input.files[0];
    const container = input.closest('.image-upload') || input.parentElement;
    
    // Remover vista previa anterior
    const existingPreview = container.querySelector('.image-preview');
    const existingIndicator = container.querySelector('.existing-image-indicator');
    if (existingPreview) existingPreview.remove();
    if (existingIndicator) existingIndicator.remove();
    
    if (file) {
        // Validar archivo
        if (!file.type.startsWith('image/')) {
            alert('Por favor selecciona un archivo de imagen válido');
            input.value = '';
            return;
        }
        
        if (file.size > 5 * 1024 * 1024) {
            alert('El archivo es demasiado grande. Máximo 5MB permitido');
            input.value = '';
            return;
        }
        
        // Crear vista previa
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.createElement('img');
            preview.src = e.target.result;
            preview.className = 'image-preview new';
            preview.style.cssText = `
                max-width: 100%;
                max-height: 150px;
                border-radius: 8px;
                margin-top: 10px;
                object-fit: cover;
                border: 2px solid #3b82f6;
            `;
            
            // Agregar indicador de nueva imagen
            const indicator = document.createElement('div');
            indicator.className = 'new-image-indicator';
            indicator.style.cssText = `
                background: #3b82f6;
                color: white;
                padding: 2px 8px;
                border-radius: 4px;
                font-size: 10px;
                margin-top: 5px;
                text-align: center;
            `;
            indicator.textContent = '🆕 Nueva imagen';
            
            container.appendChild(preview);
            container.appendChild(indicator);
        };
        reader.readAsDataURL(file);
    }
}

// Función mejorada para cargar campos específicos
function loadSpecificFields() {
    const container = document.getElementById('specificFields');
    let fieldsHTML = '';
    
    switch(currentTab) {
        case 'dias':
        fieldsHTML = `
            <!-- TÍTULO -->
            <div class="form-group">
                <label for="titulo">Título de la Jornada</label>
                <div class="input-with-counter">
                    <input type="text" id="titulo" name="titulo" required 
                        placeholder="Ej: Día en París"
                        maxlength="250">
                    <div class="char-counter" id="titulo-counter">0/250</div>
                </div>
            </div>

            <!-- UBICACIONES -->
            <div class="form-group" style="grid-column: 1 / -1;">
                <label style="font-size: 15px; font-weight: 600; color: #2d3748; margin-bottom: 15px; display: block;">
                    📍 Ubicaciones
                    <small style="display: block; color: #718096; font-weight: normal; margin-top: 4px; font-size: 13px;">
                        Agrega una o más ubicaciones para este día
                    </small>
                </label>
                
                <div id="ubicaciones-container" style="display: flex; flex-direction: column; gap: 12px; width: 100%;">
                    <!-- Primera ubicación -->
                    <div class="ubicacion-item" data-index="0" style="display: grid; grid-template-columns: 1fr auto; gap: 10px; align-items: start; width: 100%;">
                        <div class="ubicacion-input-wrapper" style="width: 100%;">
                            <input type="text" 
                                name="ubicacion" 
                                id="ubicacion-principal"
                                class="form-control ubicacion-input"
                                required
                                placeholder="🔍 Buscar ciudad, lugar, monumento..."
                                style="width: 100%; padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 14px;">
                            <input type="hidden" name="latitud" id="latitud-principal">
                            <input type="hidden" name="longitud" id="longitud-principal">
                            <div id="preview-ubicacion-principal"></div>
                        </div>
                        <div style="width: 40px;"></div>
                    </div>
                    
                    <!-- Ubicaciones secundarias -->
                    <div id="ubicaciones-secundarias-container" style="display: flex; flex-direction: column; gap: 12px; width: 100%;"></div>
                </div>
                
                <button type="button" 
                        onclick="agregarUbicacionSecundaria()" 
                        class="btn-add-ubicacion"
                        style="margin-top: 12px; padding: 10px 20px; background: #48bb78; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s;"
                        onmouseover="this.style.background='#38a169'"
                        onmouseout="this.style.background='#48bb78'">
                    ➕ Agregar Otra Ubicación
                </button>
            </div>

            <!-- DESCRIPCIÓN -->
            <div class="form-group" style="grid-column: 1 / -1;">
                <label for="descripcion">Descripción de la Jornada</label>
                <div class="textarea-with-counter">
                    <textarea id="descripcion" name="descripcion" rows="4" 
                        placeholder="Describe las actividades y experiencias de este día..."
                        maxlength="3000" data-max-chars="3000"></textarea>
                    <div class="char-counter" id="descripcion-counter">0/3000</div>
                </div>
            </div>

            <div class="form-group">
                <label>📸 Imágenes del Día (máximo 3)</label>
                <div id="imageUploadContainer">
                    <!-- El sistema de imágenes se renderizará aquí -->
                </div>
            </div>

            <!-- CAMPOS OCULTOS -->
            <input type="hidden" id="ubicacion" name="ubicacion_hidden">
            <input type="hidden" id="latitud" name="latitud">
            <input type="hidden" id="longitud" name="longitud">
        `;
        break;

        
            
        case 'alojamientos':
            fieldsHTML = `
                <div class="form-grid">
                    <div class="form-group">
                        <label for="nombre">Nombre del Alojamiento</label>
                        <div class="input-with-counter">
                            <input type="text" id="nombre" name="nombre" required 
                                placeholder="Ej: Hotel Decameron" 
                                maxlength="250">
                            <div class="char-counter" id="nombre-counter">0/250</div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="ubicacion">📍 Ubicación</label>
                        <div class="ubicacion-input-wrapper">
                            <input type="text" 
                                id="ubicacion" 
                                name="ubicacion" 
                                class="form-control"
                                required 
                                placeholder="🔍 Buscar ubicación del alojamiento...">
                            <input type="hidden" name="latitud" id="latitud">
                            <input type="hidden" name="longitud" id="longitud">
                            <div id="preview-ubicacion-principal"></div>
                        </div>
                    </div>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="tipo">Tipo de Alojamiento</label>
                        <select id="tipo" name="tipo" required>
                            <option value="hotel">🏨 Hotel</option>
                            <option value="camping">⛺ Camping</option>
                            <option value="casa_huespedes">🏠 Casa de Huéspedes</option>
                            <option value="crucero">🚢 Crucero</option>
                            <option value="lodge">🏔️ Lodge</option>
                            <option value="atipico">✨ Atípico</option>
                            <option value="campamento">🏕️ Campamento</option>
                            <option value="camping_car">🚐 Camping Car</option>
                            <option value="tren">🚂 Tren</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="categoria">Categoría (Estrellas)</label>
                        <select id="categoria" name="categoria">
                            <option value="">Sin categoría</option>
                            <option value="1">⭐ 1 Estrella</option>
                            <option value="2">⭐⭐ 2 Estrellas</option>
                            <option value="3">⭐⭐⭐ 3 Estrellas</option>
                            <option value="4">⭐⭐⭐⭐ 4 Estrellas</option>
                            <option value="5">⭐⭐⭐⭐⭐ 5 Estrellas</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="sitio_web">Sitio Web (Opcional)</label>
                        <div class="input-with-counter">
                            <input type="url" id="sitio_web" name="sitio_web" 
                                placeholder="https://..."
                                maxlength="250">
                            <div class="char-counter" id="sitio_web-counter">0/250</div>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="descripcion">Descripción</label>
                    <div class="textarea-with-counter">
                        <textarea id="descripcion" name="descripcion" required 
                                placeholder="Describe el alojamiento..."
                                maxlength="1500"></textarea>
                        <div class="char-counter" id="descripcion-counter">0/1500</div>
                    </div>
                </div>
                <div class="form-group">
                    <label>🏨 Imagen del Alojamiento</label>
                    <div class="image-upload" id="alojamiento-image-upload">
                        <input type="file" id="imagen" name="imagen" 
                            accept=".jpeg,.jpg,.png,.webp,image/jpeg,image/jpg,image/png,image/webp" 
                            style="display: none;">
                        <div class="upload-content">
                            <div style="font-size: 32px; margin-bottom: 8px;">🏨</div>
                            <div>Arrastra tu imagen aquí</div>
                            <div style="font-size: 12px; color: #718096; margin-top: 8px;">
                                o haz clic para seleccionar<br>
                                JPEG, PNG, WebP | Máximo 2MB
                            </div>
                        </div>
                    </div>
                </div>
                <input type="hidden" id="latitud" name="latitud">
                <input type="hidden" id="longitud" name="longitud">
            `;
            break;
            
         case 'actividades':
            fieldsHTML = `
                <div class="form-grid">
                    <div class="form-group">
                        <label for="nombre">Nombre de la Actividad</label>
                        <div class="input-with-counter">
                            <input type="text" id="nombre" name="nombre" required 
                                placeholder="Ej: Tour Eiffel" 
                                maxlength="250">
                            <div class="char-counter" id="nombre-counter">0/250</div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="ubicacion">📍 Ubicación de la Actividad</label>
                        <div class="ubicacion-input-wrapper">
                            <input type="text" 
                                id="ubicacion" 
                                name="ubicacion" 
                                class="form-control"
                                required 
                                placeholder="🔍 Buscar lugar, monumento, parque...">
                            <input type="hidden" name="latitud" id="latitud">
                            <input type="hidden" name="longitud" id="longitud">
                            <div id="preview-ubicacion-principal"></div>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="descripcion">Descripción</label>
                    <div class="textarea-with-counter">
                        <textarea id="descripcion" name="descripcion" required 
                                placeholder="Describe la actividad..."
                                maxlength="1500"></textarea>
                        <div class="char-counter" id="descripcion-counter">0/1500</div>
                    </div>
                </div>
                <div class="form-group">
                    <label>📸 Imágenes de la Actividad (máximo 3)</label>
                    <div id="imageUploadContainer">
                        <!-- El sistema de imágenes se renderizará aquí -->
                    </div>
                </div>
                <input type="hidden" id="latitud" name="latitud">
                <input type="hidden" id="longitud" name="longitud">
            `;
            break;
                

        case 'transportes':
            fieldsHTML = `
                <div class="form-grid">
                    <div class="form-group">
                        <label for="medio">Medio de Transporte</label>
                        <select id="medio" name="medio" required>
                            <option value="">Seleccionar medio</option>
                            <option value="bus">🚌 Bus</option>
                            <option value="avion">✈️ Avión</option>
                            <option value="coche">🚗 Coche</option>
                            <option value="barco">🚢 Barco</option>
                            <option value="tren">🚂 Tren</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="titulo">Título del Transporte</label>
                        <div class="input-with-counter">
                            <input type="text" id="titulo" name="titulo" required 
                                placeholder="Ej: Vuelo París-Roma"
                                maxlength="250">
                            <div class="char-counter" id="titulo-counter">0/250</div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="lugar_salida">📍 Lugar de Salida</label>
                        <div class="ubicacion-input-wrapper">
                            <input type="text" 
                                id="lugar_salida" 
                                name="lugar_salida" 
                                class="form-control"
                                required 
                                placeholder="🔍 Buscar aeropuerto, estación, ciudad...">
                            <input type="hidden" name="lat_salida" id="lat_salida">
                            <input type="hidden" name="lng_salida" id="lng_salida">
                            <div id="preview-salida"></div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="lugar_llegada">📍 Lugar de Llegada</label>
                        <div class="ubicacion-input-wrapper">
                            <input type="text" 
                                id="lugar_llegada" 
                                name="lugar_llegada" 
                                class="form-control"
                                required 
                                placeholder="🔍 Buscar aeropuerto, estación, ciudad...">
                            <input type="hidden" name="lat_llegada" id="lat_llegada">
                            <input type="hidden" name="lng_llegada" id="lng_llegada">
                            <div id="preview-llegada"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="duracion">Duración</label>
                        <input type="text" id="duracion" name="duracion" placeholder="Ej: 2 horas 30 minutos">
                    </div>
                    <div class="form-group">
                        <label for="distancia_km">Distancia (km)</label>
                        <input type="number" id="distancia_km" name="distancia_km" step="0.01" placeholder="Distancia en kilómetros">
                    </div>
                </div>
                <div class="form-group">
                    <label for="descripcion">Descripción</label>
                    <div class="textarea-with-counter">
                        <textarea id="descripcion" name="descripcion" 
                                placeholder="Detalles adicionales del transporte..."
                                maxlength="1500"></textarea>
                        <div class="char-counter" id="descripcion-counter">0/1500</div>
                    </div>
                </div>
            `;
            break;
    }
    
    container.innerHTML = fieldsHTML;
    
    // ========================================================
    // INICIALIZAR WIDGETS DE UBICACIÓN DESPUÉS DE CARGAR HTML
    // ========================================================
    setTimeout(() => {
        if (currentTab === 'dias') {
            console.log('🔥 Inicializando ubicación principal después de cargar campos...');
            
            const inputPrincipal = document.getElementById('ubicacion-principal');
            if (inputPrincipal) {
                console.log('✅ Input principal encontrado, creando widget...');
                
                // Destruir widget anterior si existe
                if (window.widgetUbicacionPrincipal) {
                    window.widgetUbicacionPrincipal.destroy();
                }
                
                // Crear widget
                window.widgetUbicacionPrincipal = new UbicacionSearchWidget(inputPrincipal, {
                    apiUrl: '<?= APP_URL ?>/modules/ubicaciones/ubicaciones_api.php',
                    latInputId: 'latitud-principal',
                    lngInputId: 'longitud-principal',
                    placeholder: '🔍 Buscar ciudad, monumento, lugar...',
                    showPreview: true,
                    previewContainerId: 'preview-ubicacion-principal',
                    autoSave: true,
                    onSelect: (location) => {
                        console.log('✅ UBICACIÓN SELECCIONADA:', location);
                        
                        // Actualizar campos visibles
                        if (inputPrincipal) {
                            inputPrincipal.value = location.display_name;
                        }
                        
                        // Actualizar campos ocultos del widget
                        const latPrincipal = document.getElementById('latitud-principal');
                        const lngPrincipal = document.getElementById('longitud-principal');
                        if (latPrincipal) latPrincipal.value = location.lat;
                        if (lngPrincipal) lngPrincipal.value = location.lon;
                        
                        // Actualizar campos ocultos para backend
                        const latBackend = document.getElementById('latitud');
                        const lngBackend = document.getElementById('longitud');
                        const ubicBackend = document.getElementById('ubicacion');
                        if (latBackend) latBackend.value = location.lat;
                        if (lngBackend) lngBackend.value = location.lon;
                        if (ubicBackend) ubicBackend.value = location.display_name;
                        
                        console.log('✅ Todos los campos actualizados');
                    }
                });
                
                console.log('✅ Widget principal inicializado correctamente');
            } else {
                console.error('❌ No se encontró input ubicacion-principal');
            }
        }
        
        if (currentTab === 'alojamientos') {
            const inputUbicacion = document.getElementById('ubicacion');
            if (inputUbicacion) {
                if (window.widgetUbicacionPrincipal) {
                    window.widgetUbicacionPrincipal.destroy();
                }
                window.widgetUbicacionPrincipal = new UbicacionSearchWidget(inputUbicacion, {
                    apiUrl: '<?= APP_URL ?>/modules/ubicaciones/ubicaciones_api.php',
                    latInputId: 'latitud',
                    lngInputId: 'longitud',
                    placeholder: '🔍 Buscar hotel, ciudad...',
                    showPreview: true,
                    previewContainerId: 'preview-ubicacion-principal',
                    autoSave: true
                });
            }
        }
        
        if (currentTab === 'actividades') {
            const inputUbicacion = document.getElementById('ubicacion');
            if (inputUbicacion) {
                if (window.widgetUbicacionPrincipal) {
                    window.widgetUbicacionPrincipal.destroy();
                }
                window.widgetUbicacionPrincipal = new UbicacionSearchWidget(inputUbicacion, {
                    apiUrl: '<?= APP_URL ?>/modules/ubicaciones/ubicaciones_api.php',
                    latInputId: 'latitud',
                    lngInputId: 'longitud',
                    placeholder: '🔍 Buscar lugar de actividad...',
                    showPreview: true,
                    previewContainerId: 'preview-ubicacion-principal',
                    autoSave: true
                });
            }
        }
        
        if (currentTab === 'transportes') {
            const inputSalida = document.getElementById('lugar_salida');
            const inputLlegada = document.getElementById('lugar_llegada');
            
            if (inputSalida) {
                if (window.widgetUbicacionPrincipal) {
                    window.widgetUbicacionPrincipal.destroy();
                }
                window.widgetUbicacionPrincipal = new UbicacionSearchWidget(inputSalida, {
                    apiUrl: '<?= APP_URL ?>/modules/ubicaciones/ubicaciones_api.php',
                    latInputId: 'lat_salida',
                    lngInputId: 'lng_salida',
                    placeholder: '🔍 Buscar lugar de salida...',
                    showPreview: true,
                    previewContainerId: 'preview-salida',
                    autoSave: true
                });
            }
            
            if (inputLlegada) {
                const widgetLlegada = new UbicacionSearchWidget(inputLlegada, {
                    apiUrl: '<?= APP_URL ?>/modules/ubicaciones/ubicaciones_api.php',
                    latInputId: 'lat_llegada',
                    lngInputId: 'lng_llegada',
                    placeholder: '🔍 Buscar lugar de llegada...',
                    showPreview: true,
                    previewContainerId: 'preview-llegada',
                    autoSave: true
                });
            }
        }
    }, 200);
    
    // Continuar con el resto de la función...
    
    // Configurar vista previa de imágenes después de cargar los campos
    setTimeout(() => {
    setupImagePreviews();
    setupTransportLocationFields();
    
    if (currentTab === 'dias' || currentTab === 'actividades') {
        setTimeout(() => {
            setupBibliotecaCharacterCounters();
        }, 100);
    }
    
    console.log('Campos específicos cargados');
}, 200);
}
       
// Función de respaldo más directa
function forceCharCounters() {
    const titulo = document.getElementById('titulo');
    const descripcion = document.getElementById('descripcion');
    
    if (titulo) {
        titulo.oninput = function() {
            const counter = document.getElementById('titulo-counter');
            if (counter) {
                counter.textContent = `${this.value.length}/250`;
            }
        };
    }
    
    if (descripcion) {
        descripcion.oninput = function() {
            const counter = document.getElementById('descripcion-counter');
            if (counter) {
                counter.textContent = `${this.value.length}/1500`;
            }
        };
    }
}

function setupTransportLocationFields() {
    // Configurar autocompletado para lugar de salida
    const salidaField = document.getElementById('lugar_salida');
    if (salidaField) {
        setupFieldAutocomplete(salidaField, 'salida');
    }

    // Configurar autocompletado para lugar de llegada
    const llegadaField = document.getElementById('lugar_llegada');
    if (llegadaField) {
        setupFieldAutocomplete(llegadaField, 'llegada');
    }
}

function setupFieldAutocomplete(field, type) {
    let searchTimeout;

    field.addEventListener('input', function() {
        const query = this.value.trim();
        
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }

        removeSuggestions();

        if (query.length < 3) {
            return;
        }

        searchTimeout = setTimeout(() => {
            searchAndShowFieldSuggestions(query, field, type);
        }, 500);
    });

    field.addEventListener('blur', function() {
        setTimeout(() => {
            removeSuggestions();
        }, 200);
    });
}

function searchAndShowFieldSuggestions(query, inputField, type) {
    const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=5&accept-language=es`;
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data && data.length > 0) {
                showFieldSuggestions(data, inputField, type);
            }
        })
        .catch(error => {
            console.warn('Error en búsqueda:', error);
        });
}

function showFieldSuggestions(suggestions, inputField, type) {
    removeSuggestions();

    suggestionsList = document.createElement('div');
    suggestionsList.className = 'location-suggestions';
    suggestionsList.style.cssText = `
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 2px solid #e2e8f0;
        border-top: none;
        border-radius: 0 0 10px 10px;
        max-height: 200px;
        overflow-y: auto;
        z-index: 1000;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    `;

    suggestions.forEach((suggestion) => {
        const suggestionItem = document.createElement('div');
        suggestionItem.className = 'suggestion-item';
        suggestionItem.style.cssText = `
            padding: 12px 15px;
            cursor: pointer;
            border-bottom: 1px solid #f1f1f1;
            transition: background-color 0.2s ease;
            font-size: 14px;
        `;

        suggestionItem.innerHTML = `
            <div style="font-weight: 500; color: #2d3748;">
                ${getLocationTitle(suggestion)}
            </div>
            <div style="font-size: 12px; color: #718096;">
                ${suggestion.display_name}
            </div>
        `;

        suggestionItem.addEventListener('mouseenter', function() {
            this.style.backgroundColor = '#f7fafc';
        });

        suggestionItem.addEventListener('mouseleave', function() {
            this.style.backgroundColor = 'transparent';
        });

        suggestionItem.addEventListener('click', function() {
            selectFieldLocation(suggestion, inputField, type);
        });

        suggestionsList.appendChild(suggestionItem);
    });

    const inputContainer = inputField.parentElement;
    inputContainer.style.position = 'relative';
    inputContainer.appendChild(suggestionsList);
}

function selectFieldLocation(suggestion, inputField, type) {
    const lat = parseFloat(suggestion.lat);
    const lng = parseFloat(suggestion.lon);

    // Actualizar campo
    inputField.value = suggestion.display_name;

    // Actualizar coordenadas específicas según el tipo
    if (type === 'salida') {
        const latField = document.getElementById('lat_salida');
        const lngField = document.getElementById('lng_salida');
        if (latField) latField.value = lat;
        if (lngField) lngField.value = lng;
    } else if (type === 'llegada') {
        const latField = document.getElementById('lat_llegada');
        const lngField = document.getElementById('lng_llegada');
        if (latField) latField.value = lat;
        if (lngField) lngField.value = lng;
    }

    removeSuggestions();
    console.log(`📍 ${type} seleccionada:`, suggestion.display_name);
}





// Obtener título limpio para la ubicación
function getLocationTitle(suggestion) {
    // Extraer el nombre principal de la ubicación
    const parts = suggestion.display_name.split(',');
    if (parts.length > 0) {
        return parts[0].trim();
    }
    return suggestion.display_name;
}



        // Actualizar campo de categoría según tipo de alojamiento
        function updateCategoryField() {
            const tipo = document.getElementById('tipo').value;
            const categoryGroup = document.getElementById('categoryGroup');
            
            // Tipos que requieren categoría (estrellas)
            const typesWithCategory = ['hotel', 'camping', 'casa_huespedes', 'crucero', 'lodge'];
            
            if (typesWithCategory.includes(tipo)) {
                categoryGroup.style.display = 'block';
                document.getElementById('categoria').required = true;
            } else {
                categoryGroup.style.display = 'none';
                document.getElementById('categoria').required = false;
                document.getElementById('categoria').value = '';
            }
        }

        // Reemplazar la configuración de búsqueda para que funcione con la API real:
        function setupSearch() {
            const searchInput = document.getElementById('searchInput');
            const languageFilter = document.getElementById('languageFilter');
            
            let searchTimeout;
            
            function buscarAhora() {
                if (searchTimeout) {
                    clearTimeout(searchTimeout);
                }
                
                searchTimeout = setTimeout(() => {
                    filtrarRecursos();
                }, 200);
            }
            
            searchInput.addEventListener('input', buscarAhora);
            languageFilter.addEventListener('change', filtrarRecursos);
            
            // Limpiar con ESC
            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    this.value = '';
                    filtrarRecursos();
                }
            });
        }

        function filtrarRecursos() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase().trim();
            const languageFilter = document.getElementById('languageFilter').value;
            const grid = document.getElementById('contentGrid');
            const emptyState = document.getElementById('emptyState');
            
            // Si no hay datos, cargar desde API
            if (!resources[currentTab] || resources[currentTab].length === 0) {
                loadResources();
                return;
            }
            
            // Filtrar datos existentes
            let filtered = resources[currentTab];
            
            // Filtrar por búsqueda
            if (searchTerm) {
                filtered = filtered.filter(item => {
                    return (item.titulo && item.titulo.toLowerCase().includes(searchTerm)) ||
                        (item.nombre && item.nombre.toLowerCase().includes(searchTerm)) ||
                        (item.descripcion && item.descripcion.toLowerCase().includes(searchTerm)) ||
                        (item.ubicacion && item.ubicacion.toLowerCase().includes(searchTerm)) ||
                        (item.lugar_salida && item.lugar_salida.toLowerCase().includes(searchTerm)) ||
                        (item.lugar_llegada && item.lugar_llegada.toLowerCase().includes(searchTerm)) ||
                        (item.medio && item.medio.toLowerCase().includes(searchTerm));
                });
            }
            
            // Filtrar por idioma
            if (languageFilter) {
                filtered = filtered.filter(item => item.idioma === languageFilter);
            }
            
            // Mostrar resultados
            if (filtered.length === 0) {
                grid.style.display = 'none';
                emptyState.style.display = 'block';
                emptyState.innerHTML = `
                    <div class="empty-state-icon">🔍</div>
                    <h3>No se encontraron resultados</h3>
                    <p>Intenta con otros términos de búsqueda</p>
                    <button onclick="limpiarFiltros()" style="background: var(--primary-gradient); color: white; border: none; padding: 10px 20px; border-radius: 20px; margin-top: 15px; cursor: pointer;">
                        🗑️ Limpiar Filtros
                    </button>
                `;
            } else {
                grid.style.display = 'grid';
                emptyState.style.display = 'none';
                grid.innerHTML = filtered.map(item => createResourceCard(item)).join('');
            }
        }

        // Función para limpiar filtros
        function limpiarFiltros() {
            document.getElementById('searchInput').value = '';
            document.getElementById('languageFilter').value = '';
            filtrarRecursos();
        }

// Función para renderizar resultados filtrados
function renderFilteredResults(filtered) {
    const grid = document.getElementById('contentGrid');
    const emptyState = document.getElementById('emptyState');
    
    if (filtered.length === 0) {
        grid.style.display = 'none';
        emptyState.style.display = 'block';
        
        const search = document.getElementById('searchInput').value.trim();
        const language = document.getElementById('languageFilter').value;
        
        if (search || language) {
            emptyState.innerHTML = `
                <div class="empty-state-icon">🔍</div>
                <h3>No se encontraron resultados</h3>
                <p>No hay recursos que coincidan con "<strong>${escapeHtml(search)}</strong>"</p>
                <button onclick="clearAllFilters()" style="background: var(--primary-gradient); color: white; border: none; padding: 10px 20px; border-radius: 20px; margin-top: 15px; cursor: pointer;">
                    🗑️ Limpiar Búsqueda
                </button>
            `;
        }
        return;
    }

    grid.style.display = 'grid';
    emptyState.style.display = 'none';
    
    // Agregar indicador de resultados filtrados
    const searchTerm = document.getElementById('searchInput').value.trim();
    if (searchTerm) {
        grid.innerHTML = `
            <div style="grid-column: 1/-1; background: #e3f2fd; padding: 12px 20px; border-radius: 10px; margin-bottom: 20px; border-left: 4px solid var(--primary-color);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span>🔍 <strong>${filtered.length}</strong> resultado(s) para "<em>${escapeHtml(searchTerm)}</em>"</span>
                    <button onclick="clearAllFilters()" style="background: none; border: none; color: var(--primary-color); cursor: pointer; font-size: 14px;">✕ Limpiar</button>
                </div>
            </div>
            ${filtered.map(item => createResourceCard(item)).join('')}
        `;
    } else {
        grid.innerHTML = filtered.map(item => createResourceCard(item)).join('');
    }
}

// Función para limpiar todos los filtros
function clearAllFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('languageFilter').value = '';
    renderResources();
    document.getElementById('searchInput').focus();
}
        

function showSearchError(message) {
    const grid = document.getElementById('contentGrid');
    grid.innerHTML = `
        <div style="grid-column: 1/-1; text-align: center; padding: 40px; background: #fef2f2; border-radius: 15px; border: 1px solid #fecaca;">
            <div style="font-size: 48px; margin-bottom: 20px;">⚠️</div>
            <h3 style="color: #dc2626; margin-bottom: 10px;">Error en la búsqueda</h3>
            <p style="color: #b91c1c; margin-bottom: 20px;">${message}</p>
            <button onclick="loadResources()" style="background: #dc2626; color: white; border: none; padding: 12px 24px; border-radius: 25px; cursor: pointer; font-weight: 500;">
                🔄 Intentar de nuevo
            </button>
        </div>
    `;
}

        function filterResources() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const languageFilter = document.getElementById('languageFilter').value;
            
            // Filtrar recursos
            const filtered = resources[currentTab].filter(item => {
                const matchesSearch = !searchTerm || 
                    (item.titulo && item.titulo.toLowerCase().includes(searchTerm)) ||
                    (item.nombre && item.nombre.toLowerCase().includes(searchTerm)) ||
                    (item.descripcion && item.descripcion.toLowerCase().includes(searchTerm)) ||
                    (item.ubicacion && item.ubicacion.toLowerCase().includes(searchTerm));
                
                const matchesLanguage = !languageFilter || item.idioma === languageFilter;
                
                return matchesSearch && matchesLanguage;
            });
            
            // Renderizar resultados filtrados
            const grid = document.getElementById('contentGrid');
            const emptyState = document.getElementById('emptyState');
            
            if (filtered.length === 0) {
                grid.style.display = 'none';
                emptyState.style.display = 'block';
                emptyState.innerHTML = `
                    <div class="empty-state-icon">🔍</div>
                    <h3>No se encontraron resultados</h3>
                    <p>Intenta con otros términos de búsqueda</p>
                `;
            } else {
                grid.style.display = 'grid';
                emptyState.style.display = 'none';
                grid.innerHTML = filtered.map(item => createResourceCard(item)).join('');
            }
        }

        // Funciones CRUD
       function viewResource(id) {
            viewResourceDetails(id, currentTab);
        }

        function editResource(id) {
            openModal('edit', id);
            document.getElementById('ubicacion').value = data.ubicacion || '';
            document.getElementById('latitud').value = data.latitud || '';
            document.getElementById('longitud').value = data.longitud || '';
        }
        const ubicacionPrincipal = document.getElementById('ubicacion-principal');
        if (ubicacionPrincipal) {
            ubicacionPrincipal.value = data.ubicacion || '';
        }
        // Agregar esta función para manejar errores de subida de imagen de forma más elegante:
        function handleImageUploadError(field, error) {
            const container = document.getElementById(field).closest('.image-upload') || document.getElementById(field).parentElement;
            
            // Remover mensaje de error anterior
            const existingError = container.querySelector('.upload-error');
            if (existingError) existingError.remove();
            
            // Agregar mensaje de error
            const errorDiv = document.createElement('div');
            errorDiv.className = 'upload-error';
            errorDiv.style.cssText = `
                background: #fed7d7;
                color: #e53e3e;
                padding: 8px 12px;
                border-radius: 6px;
                font-size: 12px;
                margin-top: 8px;
                border: 1px solid #feb2b2;
            `;
            errorDiv.textContent = `❌ ${error}`;
            
            container.appendChild(errorDiv);
            
            // Remover el error después de 5 segundos
            setTimeout(() => {
                if (errorDiv.parentElement) {
                    errorDiv.remove();
                }
            }, 5000);
        }
        // Función mejorada para mostrar mensajes de éxito
        function showSuccessMessage(message) {
            const toast = document.createElement('div');
            toast.className = 'success-toast';
            toast.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                color: white;
                padding: 16px 20px;
                border-radius: 12px;
                box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
                z-index: 10000;
                transform: translateX(100%);
                transition: transform 0.3s ease;
                max-width: 350px;
            `;
            
            toast.innerHTML = `
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="font-size: 20px;">✅</div>
                    <div>
                        <div style="font-weight: 600; margin-bottom: 2px;">Éxito</div>
                        <div style="font-size: 13px; opacity: 0.9;">${message}</div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(toast);
            
            // Animar entrada
            setTimeout(() => {
                toast.style.transform = 'translateX(0)';
            }, 100);
            
            // Remover después de 3 segundos
            setTimeout(() => {
                toast.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    if (document.body.contains(toast)) {
                        document.body.removeChild(toast);
                    }
                }, 300);
            }, 3000);
        }

        // Función mejorada para mostrar mensajes de error
        function showErrorMessage(message) {
            const toast = document.createElement('div');
            toast.className = 'error-toast';
            toast.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: linear-gradient(135deg, #e53e3e 0%, #dc2626 100%);
                color: white;
                padding: 16px 20px;
                border-radius: 12px;
                box-shadow: 0 8px 25px rgba(229, 62, 62, 0.3);
                z-index: 10000;
                transform: translateX(100%);
                transition: transform 0.3s ease;
                max-width: 350px;
            `;
            
            toast.innerHTML = `
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="font-size: 20px;">❌</div>
                    <div>
                        <div style="font-weight: 600; margin-bottom: 2px;">Error</div>
                        <div style="font-size: 13px; opacity: 0.9;">${message}</div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(toast);
            
            // Animar entrada
            setTimeout(() => {
                toast.style.transform = 'translateX(0)';
            }, 100);
            
            // Remover después de 4 segundos
            setTimeout(() => {
                toast.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    if (document.body.contains(toast)) {
                        document.body.removeChild(toast);
                    }
                }, 300);
            }, 4000);
        }

        async function deleteResource(id) {
            const confirmed = await showConfirmModal({
                title: '¿Eliminar recurso?',
                message: '¿Estás seguro de que quieres eliminar este recurso?',
                details: 'Esta acción no se puede deshacer.',
                icon: '🗑️',
                confirmText: 'Eliminar',
                cancelText: 'Cancelar'
            });

            if (!confirmed) {
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('type', currentTab);
                formData.append('id', id);
                
                const response = await fetch(`${APP_URL}/biblioteca/api`, {
                    method: 'POST',
                    body: formData
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                
                if (!result.success) {
                    throw new Error(result.error || 'Error al eliminar recurso');
                }
                
                showMessage(result.message || 'Recurso eliminado correctamente', 'success');
                loadResources(); // Recargar la lista
                
            } catch (error) {
                console.error('Error al eliminar recurso:', error);
                showMessage('Error al eliminar el recurso: ' + error.message, 'error');
            }
        }

        // Cargar datos de recurso para editar - MEJORADO
async function loadResourceData(id) {
    try {
        console.log('🔄 Cargando datos del recurso:', id);
        
        const response = await fetch(`${APP_URL}/biblioteca/api?action=get&type=${currentTab}&id=${id}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.error || 'Error al cargar recurso');
        }
        
        const resource = result.data;
        console.log('Cargando recurso desde API:', resource);
        
        document.getElementById('resourceId').value = resource.id;
        
        // Cargar campos comunes
        const commonFields = ['idioma', 'descripcion'];
        commonFields.forEach(field => {
            const element = document.getElementById(field);
            if (element && resource[field]) {
                element.value = resource[field];
            }
        });
        
        // Cargar campos específicos por tipo
        switch(currentTab) {
case 'dias':
    console.log('📥 Cargando datos de día:', resource);
    
    // Cargar título y descripción
    setFieldValue('titulo', resource.titulo);
    setFieldValue('descripcion', resource.descripcion);
    
    // Esperar a que los campos del widget estén en el DOM
    setTimeout(() => {
        // Cargar ubicación principal en el WIDGET
        const ubicacionPrincipal = document.getElementById('ubicacion-principal');
        if (ubicacionPrincipal && resource.ubicacion) {
            ubicacionPrincipal.value = resource.ubicacion;
            console.log('✅ Ubicación principal cargada:', resource.ubicacion);
            
            // Mostrar preview
            const preview = document.getElementById('preview-ubicacion-principal');
            if (preview) {
                preview.innerHTML = `
                    <div style="margin-top: 8px; padding: 10px; background: #f0fdf4; border-radius: 8px; border-left: 3px solid #10b981; font-size: 12px;">
                        <div style="font-weight: 600; color: #065f46;">${resource.ubicacion}</div>
                        ${resource.latitud && resource.longitud ? 
                            `<div style="color: #059669; font-size: 11px; margin-top: 2px;">
                                ${parseFloat(resource.latitud).toFixed(6)}, ${parseFloat(resource.longitud).toFixed(6)}
                            </div>` : ''
                        }
                    </div>
                `;
            }
        }
        
        // Cargar coordenadas en campos ocultos
        const latPrincipal = document.getElementById('latitud-principal');
        const lngPrincipal = document.getElementById('longitud-principal');
        if (latPrincipal && resource.latitud) latPrincipal.value = resource.latitud;
        if (lngPrincipal && resource.longitud) lngPrincipal.value = resource.longitud;
        
        setFieldValue('latitud', resource.latitud);
        setFieldValue('longitud', resource.longitud);
        setFieldValue('ubicacion', resource.ubicacion);
        
        // Reinicializar widget principal
        if (typeof initUbicacionWidgetDias === 'function') {
            initUbicacionWidgetDias();
        }
    }, 600);
    
    // Cargar ubicaciones secundarias
    if (resource.ubicaciones_secundarias && resource.ubicaciones_secundarias.length > 0) {
        console.log('📍 Cargando ' + resource.ubicaciones_secundarias.length + ' ubicaciones secundarias');
        
        setTimeout(() => {
            const container = document.getElementById('ubicaciones-secundarias-container');
            if (!container) {
                console.error('❌ Container de ubicaciones secundarias NO encontrado');
                return;
            }
            
            console.log('✅ Container encontrado, limpiando...');
            container.innerHTML = '';
            
            // Destruir widgets antiguos
            if (window.widgetsUbicacionesSecundarias) {
                widgetsUbicacionesSecundarias.forEach(w => {
                    if (w.widget && w.widget.destroy) w.widget.destroy();
                });
            }
            window.widgetsUbicacionesSecundarias = [];
            
            // Agregar cada ubicación
            resource.ubicaciones_secundarias.forEach((ubic, idx) => {
                console.log(`➕ Agregando ubicación ${idx + 1}:`, ubic.ubicacion);
                
                const index = Date.now() + idx;
                const div = document.createElement('div');
                div.className = 'ubicacion-item';
                div.dataset.index = index;
                div.style.cssText = 'display: grid; grid-template-columns: 1fr auto; gap: 10px; align-items: start; width: 100%;';
                
                div.innerHTML = `
                    <div class="ubicacion-input-wrapper" style="width: 100%;">
                        <input type="text" 
                               name="ubicaciones_secundarias[]" 
                               id="ubicacion-${index}"
                               class="form-control"
                               value="${ubic.ubicacion || ''}"
                               placeholder="🔍 Buscar otra ubicación..."
                               style="width: 100%; padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 10px;">
                        <input type="hidden" name="ubicaciones_secundarias_lat[]" id="latitud-${index}" value="${ubic.latitud || ''}">
                        <input type="hidden" name="ubicaciones_secundarias_lng[]" id="longitud-${index}" value="${ubic.longitud || ''}">
                        <div id="preview-ubicacion-${index}">
                            ${ubic.ubicacion ? `
                                <div style="margin-top: 8px; padding: 8px; background: #f0fdf4; border-radius: 6px; border-left: 3px solid #10b981; font-size: 11px;">
                                    <div style="font-weight: 600; color: #065f46;">${ubic.ubicacion}</div>
                                    ${ubic.latitud && ubic.longitud ? 
                                        `<div style="color: #059669; font-size: 10px; margin-top: 2px;">
                                            ${parseFloat(ubic.latitud).toFixed(6)}, ${parseFloat(ubic.longitud).toFixed(6)}
                                        </div>` : ''
                                    }
                                </div>
                            ` : ''}
                        </div>
                    </div>
                    <button type="button" 
                            onclick="removerUbicacionSecundaria(${index})"
                            style="background: #e53e3e; color: white; border: none; border-radius: 8px; width: 40px; height: 40px; cursor: pointer; font-size: 18px;">
                        ✕
                    </button>
                `;
                
                container.appendChild(div);
                
                // Inicializar widget después de agregar al DOM
                setTimeout(() => {
                    const input = document.getElementById(`ubicacion-${index}`);
                    if (input && typeof UbicacionSearchWidget !== 'undefined') {
                        const widget = new UbicacionSearchWidget(input, {
                            apiUrl: '<?= APP_URL ?>/modules/ubicaciones/ubicaciones_api.php',
                            latInputId: `latitud-${index}`,
                            lngInputId: `longitud-${index}`,
                            placeholder: '🔍 Buscar otra ubicación...',
                            showPreview: true,
                            previewContainerId: `preview-ubicacion-${index}`,
                            autoSave: true
                        });
                        
                        if (!window.widgetsUbicacionesSecundarias) {
                            window.widgetsUbicacionesSecundarias = [];
                        }
                        widgetsUbicacionesSecundarias.push({ index, widget });
                        console.log(`✅ Widget ${idx + 1} inicializado`);
                    }
                }, 150 * (idx + 1));
            });
            
            console.log(`✅ ${resource.ubicaciones_secundarias.length} ubicaciones secundarias agregadas al DOM`);
        }, 1000);
    } else {
        console.log('ℹ️ No hay ubicaciones secundarias para este día');
    }
    
    // Cargar imágenes
    loadImagePreviews(['imagen1', 'imagen2', 'imagen3'], resource);
    setTimeout(() => {
        if (document.getElementById('dropZoneMultiple')) {
            loadExistingImagesInMultipleSystem(resource);
        }
    }, 300);
    
    break;
                
            case 'alojamientos':
                setFieldValue('nombre', resource.nombre);
                setFieldValue('ubicacion', resource.ubicacion);
                setFieldValue('tipo', resource.tipo);
                setFieldValue('categoria', resource.categoria);
                setFieldValue('sitio_web', resource.sitio_web);
                setFieldValue('latitud', resource.latitud);
                setFieldValue('longitud', resource.longitud);
                loadImagePreviews(['imagen'], resource);
                break;
                
            case 'actividades':
                setFieldValue('nombre', resource.nombre);
                setFieldValue('ubicacion', resource.ubicacion);
                setFieldValue('duracion', resource.duracion);
                setFieldValue('precio', resource.precio);
                setFieldValue('latitud', resource.latitud);
                setFieldValue('longitud', resource.longitud);
                loadImagePreviews(['imagen1', 'imagen2', 'imagen3'], resource);
                break;
                
            case 'transportes':
                setFieldValue('titulo', resource.titulo);
                setFieldValue('medio', resource.medio);
                setFieldValue('lugar_salida', resource.lugar_salida);
                setFieldValue('lugar_llegada', resource.lugar_llegada);
                setFieldValue('duracion', resource.duracion);
                setFieldValue('distancia_km', resource.distancia_km);
                setFieldValue('lat_salida', resource.lat_salida);
                setFieldValue('lng_salida', resource.lng_salida);
                setFieldValue('lat_llegada', resource.lat_llegada);
                setFieldValue('lng_llegada', resource.lng_llegada);
                break;
        }
        
        console.log('✅ Datos cargados correctamente');
        // Inicializar sistema de imágenes para editar
        if (currentTab === 'dias' || currentTab === 'actividades') {
            // Preparar array de imágenes existentes
            const existingImages = [];
            
            if (resource.imagen1) {
                existingImages.push({ 
                    url: resource.imagen1, 
                    field: 'imagen1' 
                });
            }
            if (resource.imagen2) {
                existingImages.push({ 
                    url: resource.imagen2, 
                    field: 'imagen2' 
                });
            }
            if (resource.imagen3) {
                existingImages.push({ 
                    url: resource.imagen3, 
                    field: 'imagen3' 
                });
            }
            
            // Inicializar sistema con imágenes existentes
            setTimeout(() => {
                initImageUploadSystem(currentTab, id, existingImages);
                console.log('✅ Sistema de imágenes inicializado para EDITAR con', existingImages.length, 'imágenes');
            }, 400);
        }
        
    } catch(error) {
        console.error('❌ Error cargando datos:', error);
        showToast('Error al cargar los datos del recurso', 'error');
    }
}

// Función para configurar contadores de caracteres en biblioteca
function setupBibliotecaCharacterCounters() {
    // ✅ PREVENIR MÚLTIPLES LLAMADAS
    if (caracteresConfigurados) {
        console.log('⚠️ Contadores ya configurados, omitiendo...');
        return;
    }
    
    console.log('🔧 Configurando contadores de caracteres...');
    
    let intentos = 0;
    const maxIntentos = 10;
    
    function intentarConfigurar() {
        const titulo = document.getElementById('titulo');
        const descripcion = document.getElementById('descripcion');
        const nombre = document.getElementById('nombre');
        
        console.log(`📊 Intento ${intentos + 1} - Elementos:`, {
            titulo: !!titulo,
            descripcion: !!descripcion,
            nombre: !!nombre
        });
        
        if (titulo || descripcion || nombre) {
            configurarContadores();
            caracteresConfigurados = true; // ✅ MARCAR COMO CONFIGURADO
            console.log('✅ Contadores configurados exitosamente');
        } else if (intentos < maxIntentos) {
            intentos++;
            setTimeout(intentarConfigurar, 100);
        } else {
            console.log('❌ No se encontraron elementos después de', maxIntentos, 'intentos');
        }
    }
    
    function configurarContadores() {
        // Configurar título (para días)
        const titulo = document.getElementById('titulo');
        const tituloCounter = document.getElementById('titulo-counter');
        
        if (titulo && tituloCounter) {
            configurarContador(titulo, tituloCounter, 250);
            console.log('✓ Contador de título configurado');
        }
        
        // Configurar nombre (para actividades)
        const nombre = document.getElementById('nombre');
        const nombreCounter = document.getElementById('nombre-counter');
        
        if (nombre && nombreCounter) {
            configurarContador(nombre, nombreCounter, 250);
            console.log('✓ Contador de nombre configurado');
        }
        
        // Configurar sitio web (para alojamientos)
        const sitioWeb = document.getElementById('sitio_web');
        const sitioWebCounter = document.getElementById('sitio_web-counter');
        
        if (sitioWeb && sitioWebCounter) {
            configurarContador(sitioWeb, sitioWebCounter, 250);
            console.log('✓ Contador de sitio web configurado');
        }
        
        // Configurar descripción (para todos)
        const descripcion = document.getElementById('descripcion');
        const descripcionCounter = document.getElementById('descripcion-counter');
        
        if (descripcion && descripcionCounter) {
            configurarContador(descripcion, descripcionCounter, 1500);
            console.log('✓ Contador de descripción configurado');
        }
        
        // ✅ ELIMINAR COMPLETAMENTE ESTAS LÍNEAS SI EXISTEN:
        // if (currentTab === 'dias' || currentTab === 'actividades' || ...) {
        //     setTimeout(() => {
        //         setupBibliotecaCharacterCounters();
        //     }, 100);
        // }
    }
    
    function configurarContador(elemento, contador, maximo) {
        function actualizarContador() {
            const longitud = elemento.value.length;
            contador.textContent = `${longitud}/${maximo}`;
            
            contador.classList.remove('warning', 'danger');
            
            const porcentaje = (longitud / maximo) * 100;
            if (porcentaje >= 100) {
                contador.classList.add('danger');
            } else if (porcentaje >= 80) {
                contador.classList.add('warning');
            }
        }
        
        // ✅ LIMPIAR LISTENERS ANTERIORES
        const nuevoElemento = elemento.cloneNode(true);
        elemento.parentNode.replaceChild(nuevoElemento, elemento);
        const elementoLimpio = document.getElementById(elemento.id);
        
        // Agregar listeners al elemento limpio
        elementoLimpio.addEventListener('input', actualizarContador);
        elementoLimpio.addEventListener('keyup', actualizarContador);
        elementoLimpio.addEventListener('paste', () => setTimeout(actualizarContador, 10));
        
        actualizarContador.call(elementoLimpio);
    }
    
    intentarConfigurar();
}


function setupImageValidation() {
    const imageInputs = document.querySelectorAll('input[type="file"][accept*="image"]');
    
    imageInputs.forEach(input => {
        input.addEventListener('change', function() {
            const file = this.files[0];
            if (!file) return;
            
            // Validar tipo
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
            if (!allowedTypes.includes(file.type.toLowerCase())) {
                alert('Tipo de archivo no válido. Use: JPEG, PNG, JPG, WebP');
                this.value = '';
                return;
            }
            
            // Validar tamaño (20MB)
            const maxSize = 20971520;
            if (file.size > maxSize) {
                const sizeMB = (file.size / 1024 / 1024).toFixed(2);
                alert(`Archivo muy grande: ${sizeMB}MB. Máximo: 20MB`);
                this.value = '';
                return;
            }
            
            console.log('Archivo válido:', file.name);
        });
    });
}



        // NUEVA FUNCIÓN: Establecer valor de campo
        function setFieldValue(fieldId, value) {
            const element = document.getElementById(fieldId);
            if (element && value) {
                element.value = value;
            }
        }
        // Función de notificaciones toast (igual que admin.php)
function showMessage(message, type = 'info') {
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


        // Función para mostrar imagen en modal
        function showImageModal(imageSrc, title) {
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.9);
                z-index: 10000;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            `;
            
            modal.innerHTML = `
                <div style="max-width: 90%; max-height: 90%; text-align: center;">
                    <div style="color: white; margin-bottom: 20px; font-size: 18px; font-weight: 600;">
                        ${escapeHtml(title)}
                    </div>
                    <img src="${imageSrc}" style="max-width: 100%; max-height: 80vh; border-radius: 10px; box-shadow: 0 10px 40px rgba(0,0,0,0.5);">
                    <div style="margin-top: 20px;">
                        <button onclick="this.closest('.image-modal').remove()" style="background: #e53e3e; color: white; border: none; padding: 10px 20px; border-radius: 20px; cursor: pointer;">
                            ✕ Cerrar
                        </button>
                    </div>
                </div>
            `;
            
            modal.className = 'image-modal';
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.remove();
                }
            });
            
            document.body.appendChild(modal);
        }

        // Función para remover imagen existente (marcar para eliminación)
        function removeExistingImage(field) {
            if (confirm('¿Estás seguro de que quieres eliminar esta imagen?')) {
                const input = document.getElementById(field);
                const container = input.closest('.image-upload') || input.parentElement;
                
                // Remover preview e indicador
                const preview = container.querySelector('.image-preview');
                const indicator = container.querySelector('.existing-image-indicator');
                if (preview) preview.remove();
                if (indicator) indicator.remove();
                
                // Agregar campo oculto para indicar eliminación
                const deleteInput = document.createElement('input');
                deleteInput.type = 'hidden';
                deleteInput.name = `delete_${field}`;
                deleteInput.value = '1';
                container.appendChild(deleteInput);
                
                // Mostrar mensaje de confirmación
                const confirmDiv = document.createElement('div');
                confirmDiv.style.cssText = `
                    background: #fef5e7;
                    color: #d69e2e;
                    padding: 8px 12px;
                    border-radius: 6px;
                    font-size: 12px;
                    margin-top: 8px;
                    border: 1px solid #fbd38d;
                `;
                confirmDiv.textContent = '⚠️ Esta imagen será eliminada al guardar';
                container.appendChild(confirmDiv);
            }
        }

            // Función mejorada para manejar la vista previa de imágenes existentes
            function loadImagePreviews(imageFields, resource) {
                imageFields.forEach(field => {
                    if (resource[field]) {
                        const input = document.getElementById(field);
                        if (input) {
                            const container = input.closest('.image-upload') || input.parentElement;
                            
                            // Remover vista previa anterior
                            const existingPreview = container.querySelector('.image-preview');
                            const existingIndicator = container.querySelector('.existing-image-indicator');
                            if (existingPreview) existingPreview.remove();
                            if (existingIndicator) existingIndicator.remove();
                            
                            // Crear vista previa de imagen existente
                            const preview = document.createElement('img');
                            preview.src = resource[field];
                            preview.className = 'image-preview existing';
                            preview.style.cssText = `
                                max-width: 100%;
                                max-height: 150px;
                                border-radius: 8px;
                                margin-top: 10px;
                                object-fit: cover;
                                border: 2px solid #10b981;
                                cursor: pointer;
                            `;
                            
                            // Agregar funcionalidad para ver imagen en grande
                            preview.addEventListener('click', function() {
                                showImageModal(resource[field], resource.titulo || resource.nombre || 'Imagen');
                            });
                            
                            // Agregar indicador de imagen existente
                            const indicator = document.createElement('div');
                            indicator.className = 'existing-image-indicator';
                            indicator.style.cssText = `
                                background: #10b981;
                                color: white;
                                padding: 4px 8px;
                                border-radius: 4px;
                                font-size: 10px;
                                margin-top: 5px;
                                text-align: center;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                gap: 4px;
                            `;
                            indicator.innerHTML = '✅ Imagen actual <span style="cursor: pointer;" onclick="removeExistingImage(\'' + field + '\')">🗑️</span>';
                            
                            container.appendChild(preview);
                            container.appendChild(indicator);
                        }
                    }
                });
            }

        // Submit del formulario
        document.getElementById('resourceForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = {};
            
            for (let [key, value] of formData.entries()) {
                data[key] = value;
            }
            
            const id = document.getElementById('resourceId').value;
            
            if (id) {
                const index = resources[currentTab].findIndex(item => item.id == id);
                if (index !== -1) {
                    resources[currentTab][index] = { ...resources[currentTab][index], ...data };
                }
                showMessage('Recurso actualizado correctamente', 'success');
            } else {
                data.id = Date.now();
                resources[currentTab].push(data);
                showMessage('Recurso creado correctamente', 'success');
            }
            
            closeModal();
            renderResources();
        });

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

        // Cerrar modal al hacer clic fuera
        document.getElementById('resourceModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>

    <!-- Script del sistema de autocompletado -->
    <script src="<?= APP_URL ?>/assets/js/location-autocomplete.js"></script>
    
    <script>
        // =====================================
        // INTEGRACIÓN CON EL SISTEMA EXISTENTE
        // =====================================
        
(function() {
    // Guardar referencias a las funciones originales
    const originalOpenModal = window.openModal;
    const originalCloseModal = window.closeModal;
    
    // Sobrescribir openModal con mejoras
    window.openModal = function(mode, id = null) {
        console.log('🎭 Abriendo modal mejorado:', mode, id);
        
        // Llamar función original
        if (originalOpenModal) {
            originalOpenModal.call(this, mode, id);
        }
        
        // Aplicar mejoras visuales
        setTimeout(() => {
            enhanceModalAppearance();
            addModalAnimations();
            setupFormValidation();
            setupImageUploadEnhancements();
            
            // Enfocar primer campo
            const firstInput = document.querySelector('.modal.show input:not([type="hidden"])');
            if (firstInput) {
                firstInput.focus();
            }
        }, 100);
    };
    
    // Sobrescribir closeModal con mejoras
    window.closeModal = function() {
        console.log('🎭 Cerrando modal mejorado');
        
        const modal = document.getElementById('resourceModal');
        if (modal && modal.classList.contains('show')) {
            // Animación de cierre
            modal.style.animation = 'modalFadeOut 0.3s ease-out forwards';
            
            setTimeout(() => {
                // Llamar función original después de la animación
                if (originalCloseModal) {
                    originalCloseModal.call(this);
                }
                
                // Limpiar estado
                clearFormValidation();
                modal.style.animation = '';
            }, 300);
        } else if (originalCloseModal) {
            originalCloseModal.call(this);
        }
    };
})();

function enhanceModalAppearance() {
    const modal = document.getElementById('resourceModal');
    if (!modal) return;
    
    // Añadir clase de tema
    modal.classList.add('enhanced-modal');
    
    // Mejorar el título con iconos
    const title = document.getElementById('modalTitle');
    if (title) {
        const currentTab = window.currentTab || 'dias';
        const icons = {
            'dias': '📅',
            'alojamientos': '🏨', 
            'actividades': '🎯',
            'transportes': '🚗'
        };
        
        if (!title.textContent.includes(icons[currentTab])) {
            title.textContent = `${icons[currentTab]} ${title.textContent}`;
        }
    }
    
    // Mejorar labels con iconos
    enhanceFormLabels();
}

function enhanceFormLabels() {
    const labelIcons = {
        'idioma': '🌐',
        'titulo': '📝',
        'nombre': '🏷️',
        'ubicacion': '📍',
        'descripcion': '📄',
        'tipo': '🏷️',
        'categoria': '⭐',
        'sitio_web': '🌐',
        'medio': '🚗',
        'lugar_salida': '🛫',
        'lugar_llegada': '🛬',
        'duracion': '⏱️',
        'distancia_km': '📏',
        'precio': '💰'
    };
    
    Object.keys(labelIcons).forEach(fieldName => {
        const label = document.querySelector(`label[for="${fieldName}"]`);
        if (label && !label.textContent.includes(labelIcons[fieldName])) {
            label.innerHTML = `${labelIcons[fieldName]} ${label.textContent}`;
        }
    });
}

// Función para añadir animaciones al modal
function addModalAnimations() {
    const modal = document.getElementById('resourceModal');
    if (!modal) return;
    
    // Animación de entrada para elementos internos
    const formGroups = modal.querySelectorAll('.form-group');
    formGroups.forEach((group, index) => {
        group.style.opacity = '0';
        group.style.transform = 'translateY(20px)';
        group.style.animation = `slideInUp 0.4s ease-out ${index * 0.05}s forwards`;
    });
    
    // Añadir CSS de animación
    if (!document.getElementById('toast-animations')) {
        const style = document.createElement('style');
        style.id = 'toast-animations';
        style.textContent = `
            @keyframes slideInFromRight {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            
            @keyframes slideOutToRight {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    }
}

// Función para limpiar validación del formulario
function clearFormValidation() {
    const form = document.getElementById('resourceForm');
    if (!form) return;
    
    // Remover clases de estado
    form.querySelectorAll('.form-group').forEach(group => {
        group.classList.remove('error', 'success', 'loading');
    });
    
    // Remover mensajes
    form.querySelectorAll('.field-message').forEach(message => {
        message.remove();
    });
}

// Función para mejorar la subida de imágenes
function setupImageUploadEnhancements() {
    const imageUploads = document.querySelectorAll('.image-upload');
    
    imageUploads.forEach(upload => {
        const input = upload.querySelector('input[type="file"]');
        if (!input) return;
        
        // Drag & Drop mejorado
        setupDragAndDrop(upload, input);
        
        // Preview mejorado
        input.addEventListener('change', function() {
            handleImagePreviewEnhanced(this, upload);
        });
        
        // Indicador de progreso
        setupProgressIndicator(upload, input);
    });
}

// Función para configurar drag & drop
function setupDragAndDrop(uploadArea, input) {
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, preventDefaults, false);
    });
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    ['dragenter', 'dragover'].forEach(eventName => {
        uploadArea.addEventListener(eventName, () => {
            uploadArea.classList.add('drag-over');
            uploadArea.style.borderColor = 'var(--primary-color, #667eea)';
            uploadArea.style.background = 'linear-gradient(135deg, #f0f4ff 0%, #e6f3ff 100%)';
            uploadArea.style.transform = 'scale(1.02)';
        });
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, () => {
            uploadArea.classList.remove('drag-over');
            uploadArea.style.borderColor = '';
            uploadArea.style.background = '';
            uploadArea.style.transform = '';
        });
    });
    
    uploadArea.addEventListener('drop', function(e) {
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            input.files = files;
            handleImagePreviewEnhanced(input, uploadArea);
        }
    });
}

// Función para preview de imagen mejorado
function handleImagePreviewEnhanced(input, uploadArea) {
    const file = input.files[0];
    if (!file) return;
    
    // Validar archivo
    if (!file.type.startsWith('image/')) {
        showUploadError(uploadArea, 'Solo se permiten archivos de imagen');
        input.value = '';
        return;
    }
    
    if (file.size > 5 * 1024 * 1024) {
        showUploadError(uploadArea, 'El archivo es demasiado grande (máx. 5MB)');
        input.value = '';
        return;
    }
    
    // Mostrar indicador de carga
    showUploadProgress(uploadArea);
    
    // Crear preview
    const reader = new FileReader();
    reader.onload = function(e) {
        setTimeout(() => { // Simular tiempo de procesamiento
            showImagePreview(uploadArea, e.target.result, file.name);
        }, 800);
    };
    
    reader.onerror = function() {
        showUploadError(uploadArea, 'Error al leer el archivo');
    };
    
    reader.readAsDataURL(file);
}

// Función para mostrar progreso de subida
function showUploadProgress(uploadArea) {
    const content = uploadArea.querySelector('.upload-content');
    if (!content) return;
    
    const originalContent = content.innerHTML;
    content.innerHTML = `
        <div style="display: flex; flex-direction: column; align-items: center; gap: 15px;">
            <div style="width: 40px; height: 40px; border: 3px solid #e2e8f0; border-top: 3px solid var(--primary-color, #667eea); border-radius: 50%; animation: spin 1s linear infinite;"></div>
            <div style="font-weight: 600; color: #4a5568;">Procesando imagen...</div>
            <div style="width: 100%; background: #e2e8f0; border-radius: 10px; height: 6px; overflow: hidden;">
                <div style="height: 100%; background: linear-gradient(90deg, var(--primary-color, #667eea), var(--secondary-color, #764ba2)); width: 0%; animation: progressBar 0.8s ease-out forwards;"></div>
            </div>
        </div>
    `;
    
    // Añadir CSS de progreso
    if (!document.getElementById('progress-animations')) {
        const style = document.createElement('style');
        style.id = 'progress-animations';
        style.textContent = `
            @keyframes progressBar {
                to { width: 100%; }
            }
        `;
        document.head.appendChild(style);
    }
}

// Función para mostrar preview de imagen
function showImagePreview(uploadArea, imageSrc, fileName) {
    const content = uploadArea.querySelector('.upload-content');
    if (!content) return;
    
    content.innerHTML = `
        <div style="display: flex; flex-direction: column; align-items: center; gap: 12px; width: 100%;">
            <div style="position: relative; border-radius: 12px; overflow: hidden; max-width: 100%; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                <img src="${imageSrc}" alt="${fileName}" style="max-width: 150px; max-height: 100px; object-fit: cover; border-radius: 12px;">
                <div style="position: absolute; top: 5px; right: 5px; background: rgba(0,0,0,0.7); color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 12px;" onclick="clearImagePreview(this)">×</div>
            </div>
            <div style="text-align: center;">
                <div style="font-weight: 600; color: #2d3748; font-size: 14px;">✅ Imagen cargada</div>
                <div style="font-size: 12px; color: #718096; margin-top: 2px;">${fileName}</div>
            </div>
        </div>
    `;
    
    // Animación de entrada
    const img = content.querySelector('img');
    if (img) {
        img.style.opacity = '0';
        img.style.transform = 'scale(0.8)';
        img.style.transition = 'all 0.3s ease';
        
        setTimeout(() => {
            img.style.opacity = '1';
            img.style.transform = 'scale(1)';
        }, 50);
    }
}

// Función para limpiar preview de imagen
function clearImagePreview(button) {
    const uploadArea = button.closest('.image-upload');
    const input = uploadArea.querySelector('input[type="file"]');
    const content = uploadArea.querySelector('.upload-content');
    
    if (input) input.value = '';
    
    if (content) {
        // Obtener el tipo de campo para restaurar contenido original
        const fieldName = input.name;
        const icons = {
            'imagen': '📷',
            'imagen1': '📷',
            'imagen2': '📷', 
            'imagen3': '📷'
        };
        
        content.innerHTML = `
            <div style="font-size: 32px; margin-bottom: 8px;">${icons[fieldName] || '📷'}</div>
            <div>Subir Imagen</div>
            <div style="font-size: 12px; color: #718096;">Click para seleccionar archivo</div>
        `;
    }
    
    // Remover errores
    const errorMsg = uploadArea.querySelector('.upload-error');
    if (errorMsg) errorMsg.remove();
}

// ====================================================================
// MANEJO DE MÚLTIPLES IMÁGENES PARA DÍAS
// ====================================================================

let selectedImages = []; // Array para almacenar las imágenes seleccionadas

function handleMultipleImageSelect(input) {
    const files = Array.from(input.files);
    const maxImages = 3;
    
    console.log(`📸 ${files.length} imagen(es) seleccionada(s)`);
    
    // Limpiar array de imágenes anteriores
    selectedImages = [];
    
    // Validar cantidad
    if (files.length > maxImages) {
        showAlert(`Solo puedes subir máximo ${maxImages} imágenes`, 'warning');
        files.splice(maxImages); // Mantener solo las primeras 3
    }
    
    // Validar cada archivo
    files.forEach((file, index) => {
        if (file.size > 5 * 1024 * 1024) {
            showAlert(`La imagen "${file.name}" supera los 5MB`, 'error');
            return;
        }
        
        if (!file.type.startsWith('image/')) {
            showAlert(`"${file.name}" no es una imagen válida`, 'error');
            return;
        }
        
        selectedImages.push({
            file: file,
            index: index,
            preview: URL.createObjectURL(file)
        });
    });
    
    console.log(`✅ ${selectedImages.length} imágenes válidas agregadas`);
    
    // Mostrar previsualizaciones
    displayImagePreviews();
    
    // Asignar archivos a los inputs ocultos para el backend
    assignFilesToInputs();
}

function displayImagePreviews() {
    const container = document.getElementById('imagesPreviewContainer');
    if (!container) return;
    
    container.innerHTML = '';
    
    if (selectedImages.length === 0) {
        return;
    }
    
    selectedImages.forEach((imgData, index) => {
        const previewCard = document.createElement('div');
        previewCard.className = 'image-preview-card';
        previewCard.style.cssText = `
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            background: white;
        `;
        
        previewCard.innerHTML = `
            <div style="position: relative;">
                <img src="${imgData.preview}" 
                     alt="Preview ${index + 1}"
                     style="
                        width: 100%;
                        height: 200px;
                        object-fit: cover;
                        display: block;
                     ">
                
                <!-- Badge de posición -->
                <div style="
                    position: absolute;
                    top: 10px;
                    left: 10px;
                    background: ${index === 0 ? 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' : '#48bb78'};
                    color: white;
                    padding: 5px 12px;
                    border-radius: 20px;
                    font-size: 12px;
                    font-weight: 600;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
                ">
                    ${index === 0 ? '⭐ Principal' : `#${index + 1}`}
                </div>
                
                <!-- Botón de eliminar -->
                <button type="button"
                        onclick="removeImage(${index})"
                        style="
                            position: absolute;
                            top: 10px;
                            right: 10px;
                            background: rgba(239, 68, 68, 0.95);
                            color: white;
                            border: none;
                            border-radius: 50%;
                            width: 32px;
                            height: 32px;
                            cursor: pointer;
                            font-size: 16px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            transition: all 0.2s;
                            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
                        "
                        onmouseover="this.style.background='rgba(220, 38, 38, 0.95)'; this.style.transform='scale(1.1)'"
                        onmouseout="this.style.background='rgba(239, 68, 68, 0.95)'; this.style.transform='scale(1)'">
                    ✕
                </button>
            </div>
            
            <!-- Información del archivo -->
            <div style="padding: 12px;">
                <div style="
                    font-size: 13px;
                    color: #2d3748;
                    font-weight: 600;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    margin-bottom: 5px;
                ">
                    ${imgData.file.name}
                </div>
                <div style="
                    font-size: 11px;
                    color: #718096;
                ">
                    ${(imgData.file.size / 1024).toFixed(0)} KB
                </div>
            </div>
        `;
        
        container.appendChild(previewCard);
    });
}

function removeImage(index) {
    console.log(`🗑️ Eliminando imagen en posición ${index}`);
    
    // Revocar URL del preview para liberar memoria
    if (selectedImages[index]) {
        URL.revokeObjectURL(selectedImages[index].preview);
    }
    
    // Eliminar del array
    selectedImages.splice(index, 1);
    
    // Re-indexar
    selectedImages.forEach((img, i) => {
        img.index = i;
    });
    
    // Actualizar previsualizaciones
    displayImagePreviews();
    
    // Actualizar inputs ocultos
    assignFilesToInputs();
    
    console.log(`✅ Imagen eliminada. Quedan ${selectedImages.length} imágenes`);
}

function assignFilesToInputs() {
    // Crear un DataTransfer para asignar archivos a los inputs
    const dt1 = new DataTransfer();
    const dt2 = new DataTransfer();
    const dt3 = new DataTransfer();
    
    if (selectedImages[0]) {
        dt1.items.add(selectedImages[0].file);
        document.getElementById('imagenes').files = dt1.files;
    }
    
    if (selectedImages[1]) {
        dt2.items.add(selectedImages[1].file);
        document.getElementById('imagen2').files = dt2.files;
    }
    
    if (selectedImages[2]) {
        dt3.items.add(selectedImages[2].file);
        document.getElementById('imagen3').files = dt3.files;
    }
    
    console.log('✅ Archivos asignados a inputs:', {
        imagen1: selectedImages[0]?.file.name,
        imagen2: selectedImages[1]?.file.name,
        imagen3: selectedImages[2]?.file.name
    });
}

// Drag & Drop handlers
function handleDragOver(e) {
    e.preventDefault();
    e.stopPropagation();
    e.currentTarget.style.borderColor = '#667eea';
    e.currentTarget.style.background = 'linear-gradient(135deg, #e6f3ff 0%, #f0f4ff 100%)';
}

function handleDragLeave(e) {
    e.preventDefault();
    e.stopPropagation();
    e.currentTarget.style.borderColor = '#cbd5e0';
    e.currentTarget.style.background = 'linear-gradient(135deg, #f8fafc 0%, #edf2f7 100%)';
}

function handleDrop(e) {
    e.preventDefault();
    e.stopPropagation();
    
    e.currentTarget.style.borderColor = '#cbd5e0';
    e.currentTarget.style.background = 'linear-gradient(135deg, #f8fafc 0%, #edf2f7 100%)';
    
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        const input = document.getElementById('imagenes');
        input.files = files;
        handleMultipleImageSelect(input);
    }
}

// Limpiar previsualizaciones al cerrar el modal
function limpiarImagenesPrevias() {
    selectedImages.forEach(img => {
        URL.revokeObjectURL(img.preview);
    });
    selectedImages = [];
    
    const container = document.getElementById('imagesPreviewContainer');
    if (container) {
        container.innerHTML = '';
    }
    
    // Limpiar inputs
    const input1 = document.getElementById('imagenes');
    const input2 = document.getElementById('imagen2');
    const input3 = document.getElementById('imagen3');
    if (input1) input1.value = '';
    if (input2) input2.value = '';
    if (input3) input3.value = '';
}

// Llamar a limpiarImagenesPrevias cuando se cierra el modal
const originalCloseModal = window.closeModal;
window.closeModal = function() {
    limpiarImagenesPrevias();
    if (originalCloseModal) {
        originalCloseModal();
    }
};
function setupCharCountersDirectly() {
    console.log('Configurando contadores directamente...');
    
    // Configurar contador de título
    const tituloField = document.getElementById('titulo');
    const tituloCounter = document.getElementById('titulo-counter');
    
    if (tituloField && tituloCounter) {
        console.log('Título encontrado, configurando contador...');
        
        function updateTituloCounter() {
            const length = tituloField.value.length;
            tituloCounter.textContent = `${length}/250`;
            
            // Limpiar clases
            tituloCounter.className = 'char-counter';
            
            // Agregar clase según porcentaje
            const percentage = (length / 250) * 100;
            if (percentage >= 100) {
                tituloCounter.classList.add('danger');
            } else if (percentage >= 80) {
                tituloCounter.classList.add('warning');
            }
            
            console.log(`Título: ${length}/250 caracteres`);
        }
        
        // Eventos múltiples para asegurar que funcione
        tituloField.addEventListener('input', updateTituloCounter);
        tituloField.addEventListener('keyup', updateTituloCounter);
        tituloField.addEventListener('change', updateTituloCounter);
        tituloField.addEventListener('paste', function() {
            setTimeout(updateTituloCounter, 50);
        });
        
        // Inicializar
        updateTituloCounter();
        
        console.log('Contador de título LISTO');
    } else {
        console.error('No se encontró título:', !!tituloField, 'o contador:', !!tituloCounter);
    }
    
    // Configurar contador de descripción
    const descripcionField = document.getElementById('descripcion');
    const descripcionCounter = document.getElementById('descripcion-counter');
    
    if (descripcionField && descripcionCounter) {
        console.log('Descripción encontrada, configurando contador...');
        
        function updateDescripcionCounter() {
            const length = descripcionField.value.length;
            descripcionCounter.textContent = `${length}/1500`;
            
            // Limpiar clases
            descripcionCounter.className = 'char-counter';
            
            // Agregar clase según porcentaje
            const percentage = (length / 1500) * 100;
            if (percentage >= 100) {
                descripcionCounter.classList.add('danger');
            } else if (percentage >= 80) {
                descripcionCounter.classList.add('warning');
            }
            
            console.log(`Descripción: ${length}/1500 caracteres`);
        }
        
        // Eventos múltiples para asegurar que funcione
        descripcionField.addEventListener('input', updateDescripcionCounter);
        descripcionField.addEventListener('keyup', updateDescripcionCounter);
        descripcionField.addEventListener('change', updateDescripcionCounter);
        descripcionField.addEventListener('paste', function() {
            setTimeout(updateDescripcionCounter, 50);
        });
        
        // Inicializar
        updateDescripcionCounter();
        
        console.log('Contador de descripción LISTO');
    } else {
        console.error('No se encontró descripción:', !!descripcionField, 'o contador:', !!descripcionCounter);
    }
    
    console.log('Configuración de contadores completada');
}
// Función para mostrar error de subida
function showUploadError(uploadArea, message) {
    const content = uploadArea.querySelector('.upload-content');
    if (!content) return;
    
    // Restaurar contenido original con error
    const input = uploadArea.querySelector('input[type="file"]');
    const fieldName = input?.name || 'imagen';
    
    content.innerHTML = `
        <div style="display: flex; flex-direction: column; align-items: center; gap: 10px;">
            <div style="font-size: 32px; color: #e53e3e;">⚠️</div>
            <div style="font-weight: 600; color: #e53e3e;">Error</div>
            <div style="font-size: 12px; color: #c53030; text-align: center;">${message}</div>
            <button type="button" onclick="clearImagePreview(this)" style="background: #fed7d7; color: #c53030; border: 1px solid #feb2b2; padding: 6px 12px; border-radius: 6px; font-size: 12px; cursor: pointer;">
                Intentar de nuevo
            </button>
        </div>
    `;
    
    // Animación de error
    uploadArea.style.animation = 'shake 0.5s ease-in-out';
    setTimeout(() => {
        uploadArea.style.animation = '';
    }, 500);
}

// Función para configurar indicador de progreso
function setupProgressIndicator(uploadArea, input) {
    // Esta función se puede expandir para mostrar progreso real de subida
    // Por ahora solo maneja la interfaz visual
}

// Funciones de validación auxiliares
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function isValidUrl(url) {
    try {
        new URL(url);
        return true;
    } catch {
        return false;
    }
}

// Función para mejorar el botón de envío
function enhanceSubmitButton() {
    const form = document.getElementById('resourceForm');
    const submitBtn = form?.querySelector('button[type="submit"]');
    
    if (!submitBtn) return;
    
    // Guardar texto original
    const originalText = submitBtn.textContent;
    
    // Mejorar estado de carga
    form.addEventListener('submit', function() {
        submitBtn.disabled = true;
        submitBtn.style.position = 'relative';
        submitBtn.innerHTML = `
            <span style="opacity: 0.7;">${originalText}</span>
            <div style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.3); border-top: 2px solid white; border-radius: 50%; animation: spin 1s linear infinite;"></div>
        `;
        
        // Restaurar después de un tiempo (esto debería manejarse en el callback real)
        setTimeout(() => {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        }, 3000);
    });
}

// Función para mejorar navegación por teclado
function enhanceKeyboardNavigation() {
    const modal = document.getElementById('resourceModal');
    if (!modal) return;
    
    modal.addEventListener('keydown', function(e) {
        // ESC para cerrar
        if (e.key === 'Escape') {
            closeModal();
            return;
        }
        
        // Tab mejorado
        if (e.key === 'Tab') {
            const focusableElements = modal.querySelectorAll(
                'input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), button:not([disabled])'
            );
            
            const firstElement = focusableElements[0];
            const lastElement = focusableElements[focusableElements.length - 1];
            
            if (e.shiftKey && document.activeElement === firstElement) {
                e.preventDefault();
                lastElement.focus();
            } else if (!e.shiftKey && document.activeElement === lastElement) {
                e.preventDefault();
                firstElement.focus();
            }
        }
        
        // Enter en campos que no sean textarea
        if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
            const form = modal.querySelector('form');
            const submitBtn = form?.querySelector('button[type="submit"]');
            
            if (submitBtn && !submitBtn.disabled) {
                e.preventDefault();
                submitBtn.click();
            }
        }
    });
}

// Función de inicialización principal
function initializeModalEnhancements() {
    console.log('🎨 Inicializando mejoras de modals...');
    
    // Observar cuando se abre un modal
    const modal = document.getElementById('resourceModal');
    if (modal) {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    if (modal.classList.contains('show')) {
                        setTimeout(() => {
                            enhanceSubmitButton();
                            enhanceKeyboardNavigation();
                        }, 150);
                    }
                }
            });
        });
        
        observer.observe(modal, {
            attributes: true,
            attributeFilter: ['class']
        });
    }
    
    console.log('✅ Mejoras de modals inicializadas');
}

// Auto-inicialización
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeModalEnhancements);
} else {
    initializeModalEnhancements();
}

// ====================================================================
// INICIALIZACIÓN DEL WIDGET DE UBICACIONES
// ====================================================================

let widgetUbicacionPrincipal = null;
let widgetsUbicacionesSecundarias = [];

/**
 * Inicializar widget de ubicación principal para días
 */
function initUbicacionWidgetDias() {
    const inputUbicacion = document.getElementById('ubicacion-principal');
    
    if (!inputUbicacion) {
        console.warn('⚠️ Input de ubicación principal no encontrado');
        console.log('🔍 Buscando inputs disponibles:', 
            document.querySelectorAll('input[type="text"]'));
        return;
    }
    
    console.log('✅ Input de ubicación principal encontrado:', inputUbicacion);
    
    // Destruir widget anterior si existe
    if (widgetUbicacionPrincipal) {
        widgetUbicacionPrincipal.destroy();
        widgetUbicacionPrincipal = null;
    }
    
    // Limpiar widgets secundarios
    if (widgetsUbicacionesSecundarias && widgetsUbicacionesSecundarias.length > 0) {
        widgetsUbicacionesSecundarias.forEach(w => {
            if (w.widget) {
                w.widget.destroy();
            }
        });
    }
    widgetsUbicacionesSecundarias = [];
    
    // Crear nuevo widget para ubicación principal
    widgetUbicacionPrincipal = new UbicacionSearchWidget(inputUbicacion, {
        apiUrl: '<?= APP_URL ?>/modules/ubicaciones/ubicaciones_api.php',
        latInputId: 'latitud-principal',
        lngInputId: 'longitud-principal',
        placeholder: '🔍 Buscar ciudad, monumento, lugar...',
        showPreview: true,
        previewContainerId: 'preview-ubicacion-principal',
        autoSave: true,
        onSelect: (location) => {
            console.log('✅ Ubicación principal seleccionada:', location);
            
            // Actualizar campos ocultos para backend
            const latitudField = document.getElementById('latitud');
            const longitudField = document.getElementById('longitud');
            const ubicacionField = document.getElementById('ubicacion');
            
            if (latitudField) {
                latitudField.value = location.lat;
                console.log('📍 Latitud actualizada:', location.lat);
            }
            if (longitudField) {
                longitudField.value = location.lon;
                console.log('📍 Longitud actualizada:', location.lon);
            }
            if (ubicacionField) {
                ubicacionField.value = location.display_name;
                console.log('📍 Ubicación actualizada:', location.display_name);
            }
        }
    });
    
    console.log('✅ Widget de ubicación principal inicializado correctamente');
}

/**
 * Inicializar widgets de ubicación para alojamientos
 */
function initUbicacionWidgetAlojamiento() {
    const input = document.getElementById('ubicacion');
    if (!input) return;
    
    if (widgetUbicacionPrincipal) {
        widgetUbicacionPrincipal.destroy();
    }
    
    widgetUbicacionPrincipal = new UbicacionSearchWidget(input, {
        latInputId: 'latitud',
        lngInputId: 'longitud',
        placeholder: '🔍 Buscar hotel, ciudad, dirección...',
        showPreview: true,
        previewContainerId: 'preview-ubicacion-principal',
        autoSave: true
    });
    
    console.log('✅ Widget de ubicación para alojamiento inicializado');
}

/**
 * Inicializar widgets de ubicación para actividades
 */
function initUbicacionWidgetActividad() {
    const input = document.getElementById('ubicacion');
    if (!input) return;
    
    if (widgetUbicacionPrincipal) {
        widgetUbicacionPrincipal.destroy();
    }
    
    widgetUbicacionPrincipal = new UbicacionSearchWidget(input, {
        latInputId: 'latitud',
        lngInputId: 'longitud',
        placeholder: '🔍 Buscar lugar de la actividad...',
        showPreview: true,
        previewContainerId: 'preview-ubicacion-principal',
        autoSave: true
    });
    
    console.log('✅ Widget de ubicación para actividad inicializado');
}

/**
 * Inicializar widgets para transportes (salida y llegada)
 */
function initUbicacionWidgetTransportes() {
    // Widget para lugar de salida
    const inputSalida = document.getElementById('lugar_salida');
    const inputLlegada = document.getElementById('lugar_llegada');
    
    if (inputSalida) {
        if (widgetUbicacionPrincipal) {
            widgetUbicacionPrincipal.destroy();
        }
        
        widgetUbicacionPrincipal = new UbicacionSearchWidget(inputSalida, {
            latInputId: 'lat_salida',
            lngInputId: 'lng_salida',
            placeholder: '🔍 Buscar lugar de salida...',
            showPreview: true,
            previewContainerId: 'preview-salida',
            autoSave: true
        });
    }
    
    // Widget para lugar de llegada
    if (inputLlegada) {
        const widgetLlegada = new UbicacionSearchWidget(inputLlegada, {
            latInputId: 'lat_llegada',
            lngInputId: 'lng_llegada',
            placeholder: '🔍 Buscar lugar de llegada...',
            showPreview: true,
            previewContainerId: 'preview-llegada',
            autoSave: true
        });
        
        widgetsUbicacionesSecundarias.push(widgetLlegada);
    }
    
    console.log('✅ Widgets de ubicación para transportes inicializados');
}

// Observar cuando se abre el modal para inicializar widgets
const resourceModal = document.getElementById('resourceModal');
if (resourceModal) {
    const modalObserver = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                if (resourceModal.classList.contains('show')) {
                    // Esperar a que el modal se renderice completamente
                    setTimeout(() => {
                        console.log('🎬 Modal abierto, tab activo:', currentTab);
                        
                        // Inicializar widget según el tab activo
                        switch(currentTab) {
                            case 'dias':
                                console.log('📍 Inicializando widgets de ubicación para DÍAS...');
                                initUbicacionWidgetDias();
                                break;
                            case 'alojamientos':
                                console.log('📍 Inicializando widget de ubicación para ALOJAMIENTOS...');
                                initUbicacionWidgetAlojamiento();
                                break;
                            case 'actividades':
                                console.log('📍 Inicializando widget de ubicación para ACTIVIDADES...');
                                initUbicacionWidgetActividad();
                                break;
                            case 'transportes':
                                console.log('📍 Inicializando widgets de ubicación para TRANSPORTES...');
                                initUbicacionWidgetTransportes();
                                break;
                        }
                    }, 400); // Aumentar tiempo de espera a 400ms
                }
            }
        });
    });
    
    modalObserver.observe(resourceModal, {
        attributes: true,
        attributeFilter: ['class']
    });
    
    console.log('✅ Observador de modal de ubicaciones configurado');
}

// Función para aplicar tema de colores dinámico
function applyDynamicTheme() {
    const root = document.documentElement;
    const primaryColor = getComputedStyle(root).getPropertyValue('--primary-color').trim();
    const secondaryColor = getComputedStyle(root).getPropertyValue('--secondary-color').trim();
    
    if (primaryColor && secondaryColor) {
        console.log('🎨 Aplicando tema dinámico:', { primaryColor, secondaryColor });
        
        // Los colores ya están definidos en CSS, solo necesitamos asegurar que se usen
        const style = document.createElement('style');
        style.id = 'dynamic-theme';
        style.textContent = `
            .modal-header {
                background: linear-gradient(135deg, ${primaryColor} 0%, ${secondaryColor} 100%);
            }
            
            .btn-primary {
                background: linear-gradient(135deg, ${primaryColor} 0%, ${secondaryColor} 100%);
                box-shadow: 0 4px 15px ${primaryColor}40;
            }
            
            .form-group input:focus,
            .form-group select:focus,
            .form-group textarea:focus {
                border-color: ${primaryColor};
                box-shadow: 0 0 0 4px ${primaryColor}20, 0 4px 12px rgba(0, 0, 0, 0.08);
            }
        `;
        
        // Reemplazar si ya existe
        const existing = document.getElementById('dynamic-theme');
        if (existing) existing.remove();
        
        document.head.appendChild(style);
    }
}

// Aplicar tema al cargar
document.addEventListener('DOMContentLoaded', applyDynamicTheme);

// Función global para debugging
window.debugModalEnhancements = function() {
    console.log('🔍 DEBUG - Modal Enhancements:', {
        modalExists: !!document.getElementById('resourceModal'),
        enhancementsLoaded: true,
        currentTab: window.currentTab,
        activeModals: document.querySelectorAll('.modal.show').length
    });
}; si no existe
    if (!document.getElementById('modal-animations')) {
        const style = document.createElement('style');
        style.id = 'modal-animations';
        style.textContent = `
            @keyframes slideInUp {
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            @keyframes modalFadeOut {
                from {
                    opacity: 1;
                    backdrop-filter: blur(8px);
                }
                to {
                    opacity: 0;
                    backdrop-filter: blur(0px);
                }
            }
            
            @keyframes pulse {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.05); }
            }
            
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                25% { transform: translateX(-5px); }
                75% { transform: translateX(5px); }
            }
        `;
        document.head.appendChild(style);
    }
}

// Función para configurar validación visual del formulario
function setupFormValidation() {
    const form = document.getElementById('resourceForm');
    if (!form) return;
    
    // Validación en tiempo real
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    
    inputs.forEach(input => {
        // Remover listeners anteriores
        input.removeEventListener('blur', validateField);
        input.removeEventListener('input', clearFieldError);
        
        // Añadir nuevos listeners
        input.addEventListener('blur', validateField);
        input.addEventListener('input', clearFieldError);
        
        // Validación al enviar
        form.addEventListener('submit', function(e) {
            let hasErrors = false;
            
            inputs.forEach(field => {
                if (!validateField.call(field)) {
                    hasErrors = true;
                }
            });
            
            if (hasErrors) {
                e.preventDefault();
                showValidationSummary();
                
                // Scroll al primer error
                const firstError = form.querySelector('.form-group.error');
                if (firstError) {
                    firstError.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'center' 
                    });
                }
            }
        });
    });
}

// Función para validar un campo individual
function validateField() {
    const formGroup = this.closest('.form-group');
    if (!formGroup) return true;
    
    let isValid = true;
    let message = '';
    
    // Limpiar estado anterior
    formGroup.classList.remove('error', 'success');
    const existingMessage = formGroup.querySelector('.field-message');
    if (existingMessage) {
        existingMessage.remove();
    }
    
    // Validaciones específicas
    if (this.hasAttribute('required') && !this.value.trim()) {
        isValid = false;
        message = 'Este campo es requerido';
    } else if (this.type === 'email' && this.value && !isValidEmail(this.value)) {
        isValid = false;
        message = 'Ingresa un email válido';
    } else if (this.type === 'url' && this.value && !isValidUrl(this.value)) {
        isValid = false;
        message = 'Ingresa una URL válida';
    } else if (this.type === 'number' && this.value && this.value < 0) {
        isValid = false;
        message = 'El valor no puede ser negativo';
    } else if (this.name === 'titulo' && this.value.trim().length < 3) {
        isValid = false;
        message = 'El título debe tener al menos 3 caracteres';
    }
    
    // Aplicar estado visual
    if (!isValid) {
        formGroup.classList.add('error');
        showFieldMessage(formGroup, message, 'error');
        
        // Animación de error
        this.style.animation = 'shake 0.5s ease-in-out';
        setTimeout(() => {
            this.style.animation = '';
        }, 500);
    } else if (this.value.trim()) {
        formGroup.classList.add('success');
    }
    
    return isValid;
}

// Función para limpiar errores al escribir
function clearFieldError() {
    const formGroup = this.closest('.form-group');
    if (formGroup) {
        formGroup.classList.remove('error');
        const errorMessage = formGroup.querySelector('.field-message.error');
        if (errorMessage) {
            errorMessage.remove();
        }
    }
}

// Función para mostrar mensaje en campo
function showFieldMessage(formGroup, message, type) {
    const messageDiv = document.createElement('div');
    messageDiv.className = `field-message ${type}`;
    messageDiv.textContent = message;
    
    // Icono según tipo
    const icon = type === 'error' ? '⚠️' : '✅';
    messageDiv.textContent = `${icon} ${message}`;
    
    formGroup.appendChild(messageDiv);
}

function showValidationSummary() {
   const errors = document.querySelectorAll('.form-group.error');
   if (errors.length === 0) return;
   
   // Crear toast de error
   const toast = document.createElement('div');
   toast.style.cssText = `
       position: fixed;
       top: 20px;
       right: 20px;
       background: linear-gradient(135deg, #e53e3e 0%, #c53030 100%);
       color: white;
       padding: 16px 20px;
       border-radius: 12px;
       box-shadow: 0 8px 25px rgba(229, 62, 62, 0.3);
       z-index: 10001;
       max-width: 350px;
       animation: slideInFromRight 0.3s ease-out;
   `;
   
   toast.innerHTML = `
       <div style="display: flex; align-items: center; gap: 12px;">
           <div style="font-size: 20px;">⚠️</div>
           <div>
               <div style="font-weight: 600; margin-bottom: 4px;">Revisa los campos</div>
               <div style="font-size: 13px; opacity: 0.9;">
                   ${errors.length} campo${errors.length > 1 ? 's' : ''} necesita${errors.length > 1 ? 'n' : ''} corrección
               </div>
           </div>
       </div>
   `;
   
   document.body.appendChild(toast);
   
   // Remover después de 4 segundos
   setTimeout(() => {
       toast.style.animation = 'slideOutToRight 0.3s ease-in';
       setTimeout(() => {
           if (document.body.contains(toast)) {
               document.body.removeChild(toast);
           }
       }, 300);
   }, 4000);
   
   // Añadir CSS de animación
   if (!document.getElementById('toast-animations')) {
       const style = document.createElement('style');
       style.id = 'toast-animations';
       style.textContent = `
           @keyframes slideInFromRight {
               from { transform: translateX(100%); opacity: 0; }
               to { transform: translateX(0); opacity: 1; }
           }
           
           @keyframes slideOutToRight {
               from { transform: translateX(0); opacity: 1; }
               to { transform: translateX(100%); opacity: 0; }
           }
       `;
       document.head.appendChild(style);
   }
}
    

        
        // Inicialización automática cuando se detecten campos
        document.addEventListener('DOMContentLoaded', function() {
            console.log('📚 Biblioteca con SÚPER autocompletado lista');
            
            // Verificar si ya hay campos presentes
            const existingFields = document.querySelectorAll('#ubicacion, #lugar_salida, #lugar_llegada');
            if (existingFields.length > 0) {
                setTimeout(() => {
                    initializeSuperLocationAutocomplete();
                }, 500);
            }
        });

        // Función para debugging desde consola del navegador
        window.debugBibliotecaAutocomplete = function() {
            console.log('🔍 DEBUG INFO:', {
                autocompleteLoaded: !!window.superLocationAutocomplete,
                debugInfo: window.superLocationAutocomplete ? window.superLocationAutocomplete.getDebugInfo() : null,
                fieldsFound: document.querySelectorAll('#ubicacion, #lugar_salida, #lugar_llegada').length
            });
        };

        // Agregar los estilos adicionales
document.addEventListener('DOMContentLoaded', function() {
    const style = document.createElement('style');
    style.textContent = additionalCSS;
    document.head.appendChild(style);
});
</script>
<script>

function showSuggestionsForInput(locations, inputElement, latInput, lngInput, preview) {
    console.log('📋 Mostrando', locations.length, 'sugerencias mejoradas para:', inputElement.id);
    
    // Limpiar sugerencias anteriores
    removeSuggestionsFromInput(inputElement);
    
    const suggestionsContainer = document.createElement('div');
    suggestionsContainer.className = 'location-suggestions-universal';
    suggestionsContainer.style.cssText = `
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 2px solid #e2e8f0;
        border-top: none;
        border-radius: 0 0 12px 12px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        z-index: 2000;
        max-height: 320px;
        overflow-y: auto;
    `;
    
    locations.forEach((location, index) => {
        const item = document.createElement('div');
        item.className = 'suggestion-item-universal';
        item.style.cssText = `
            padding: 14px 16px;
            cursor: pointer;
            border-bottom: 1px solid #f1f1f1;
            transition: all 0.2s ease;
            font-size: 14px;
            position: relative;
        `;
        
        // Información mejorada del lugar
        const mainName = location.display_name.split(',')[0];
        const restOfAddress = location.display_name.split(',').slice(1, 3).join(',').trim();
        const country = location.display_name.split(',').slice(-1)[0].trim();
        
        // Icono según el tipo de lugar
        const icon = getLocationIcon(location);
        
        // Indicador de relevancia
        const relevanceBar = location.score > 15 ? '🌟' : location.score > 10 ? '⭐' : '📍';
        
        item.innerHTML = `
            <div style="display: flex; align-items: start; gap: 10px;">
                <span style="font-size: 16px; margin-top: 2px;">${icon}</span>
                <div style="flex: 1;">
                    <div style="font-weight: 600; color: #2d3748; margin-bottom: 3px; display: flex; align-items: center; gap: 6px;">
                        ${mainName}
                        <span style="font-size: 12px;">${relevanceBar}</span>
                    </div>
                    <div style="font-size: 12px; color: #718096; line-height: 1.3;">
                        ${restOfAddress}
                    </div>
                    <div style="font-size: 11px; color: #a0aec0; margin-top: 2px;">
                        📍 ${country} ${location.type ? `• ${location.type}` : ''}
                    </div>
                </div>
            </div>
        `;
        
        // Hover effects mejorados
        item.addEventListener('mouseenter', () => {
            item.style.backgroundColor = '#f8fafc';
            item.style.borderLeft = '4px solid var(--primary-color, #667eea)';
            item.style.paddingLeft = '12px';
        });
        
        item.addEventListener('mouseleave', () => {
            item.style.backgroundColor = 'white';
            item.style.borderLeft = 'none';
            item.style.paddingLeft = '16px';
        });
        
        // Click handler
        item.addEventListener('click', () => {
            console.log('✅ Ubicación seleccionada (mejorada):', location.display_name, 'Score:', location.score);
            
            // Actualizar campo
            inputElement.value = location.display_name;
            
            // Actualizar coordenadas
            if (latInput) latInput.value = location.lat;
            if (lngInput) lngInput.value = location.lon;
            
            // Mostrar preview mejorado
            if (preview) {
                showLocationPreviewEnhanced(preview, location);
            }
            
            // Limpiar sugerencias
            removeSuggestionsFromInput(inputElement);
            
            // Actualizar mapa si es la ubicación principal
            if (inputElement.id === 'ubicacion' && window.map) {
                updateMapWithSelectedLocation(location.display_name, { lat: location.lat, lng: location.lon });
            }
        });
        
        suggestionsContainer.appendChild(item);
    });
    
    // Header con contador de resultados
    const header = document.createElement('div');
    header.style.cssText = `
        padding: 8px 16px;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        font-size: 12px;
        color: #4a5568;
        font-weight: 600;
    `;
    header.textContent = `🔍 ${locations.length} ubicaciones encontradas`;
    suggestionsContainer.insertBefore(header, suggestionsContainer.firstChild);
    
    // Posicionar contenedor
    const parent = inputElement.parentElement;
    parent.style.position = 'relative';
    parent.appendChild(suggestionsContainer);
    
    console.log('✅ Sugerencias mejoradas mostradas correctamente');
}

// 🎨 FUNCIÓN PARA OBTENER ICONO SEGÚN TIPO DE LUGAR
function getLocationIcon(location) {
    const type = location.type || '';
    const category = location.category || '';
    const className = location.class || '';
    
    // Iconos específicos por tipo
    const icons = {
        // Lugares administrativos
        'city': '🏙️', 'town': '🏘️', 'village': '🏡', 'hamlet': '🏠',
        
        // Lugares turísticos
        'attraction': '🎯', 'museum': '🏛️', 'monument': '🗿', 'castle': '🏰',
        'palace': '🏰', 'cathedral': '⛪', 'church': '⛪', 'temple': '🛕',
        
        // Transporte
        'airport': '✈️', 'bus_station': '🚌', 'train_station': '🚂',
        'subway_station': '🚇', 'ferry_terminal': '⛴️',
        
        // Naturaleza
        'park': '🌳', 'garden': '🌺', 'beach': '🏖️', 'mountain': '⛰️',
        'forest': '🌲', 'lake': '🏞️', 'river': '🌊',
        
        // Servicios
        'hotel': '🏨', 'restaurant': '🍽️', 'cafe': '☕', 'shop': '🛍️',
        'mall': '🏬', 'hospital': '🏥', 'school': '🎓', 'bank': '🏦',
        
        // Por categoría
        'place': '📍', 'tourism': '🎯', 'amenity': '🏢', 'natural': '🌿',
        'historic': '🏛️', 'leisure': '🎪'
    };
    
    return icons[type] || icons[category] || icons[className] || '📍';
}

// 🎨 PREVIEW MEJORADO
function showLocationPreviewEnhanced(preview, location) {
    if (!preview || !location) return;
    
    const icon = getLocationIcon(location);
    const country = location.display_name.split(',').slice(-1)[0].trim();
    
    preview.innerHTML = `
        <div style="display: flex; align-items: center; gap: 8px;">
            <span>${icon}</span>
            <div>
                <div style="font-weight: 600; color: #2d3748;">${location.display_name.split(',')[0]}</div>
                <div style="font-size: 11px; color: #718096;">${country}</div>
            </div>
        </div>
    `;
    preview.classList.add('show');
}

// ===== CORRECCIÓN 2: IMAGEN CORRECTA PARA TRANSPORTES =====

// Función para obtener icono correcto del medio de transporte
function getTransportIcon(medio) {
    const transportIcons = {
        'bus': '🚌',
        'avion': '✈️',
        'coche': '🚗',
        'barco': '🚢',
        'tren': '🚂',
        'metro': '🚇',
        'taxi': '🚕',
        'bicicleta': '🚲',
        'moto': '🏍️',
        'walking': '🚶'
    };
    
    return transportIcons[medio] || '🚗';
}

// Función mejorada para crear card de transporte
function createTransportCard(item) {
    const transportIcon = getTransportIcon(item.medio);
    const title = item.titulo || 'Transporte';
    const route = `${item.lugar_salida || 'Origen'} → ${item.lugar_llegada || 'Destino'}`;
    
    return `
        <div class="item-card transport-card" onclick="editResource(${item.id}, 'transportes')">
            <div class="card-image transport-image">
                <div class="transport-icon">${transportIcon}</div>
                <div class="transport-type">${item.medio || 'Transporte'}</div>
            </div>
            <div class="card-content">
                <h3 class="card-title">${escapeHtml(title)}</h3>
                <p class="card-description">${escapeHtml(item.descripcion || 'Sin descripción')}</p>
                <div class="card-route">🛣️ ${escapeHtml(route)}</div>
                ${item.duracion ? `<div class="card-duration">⏱️ ${escapeHtml(item.duracion)}</div>` : ''}
                ${item.precio ? `<div class="card-price">💰 ${escapeHtml(item.precio)}</div>` : ''}
            </div>
            <div class="card-actions">
                <button class="action-btn edit" onclick="event.stopPropagation(); editResource(${item.id})">
                    ✏️ Editar
                </button>
                <button class="action-btn delete" onclick="event.stopPropagation(); deleteResource(${item.id})">
                    🗑️ Eliminar
                </button>
            </div>
        </div>
    `;
}
// CSS específico para cards de transporte
const transportCardCSS = `
<style>
.transport-card .card-image {
    background: linear-gradient(135deg, var(--primary-color, #667eea) 0%, var(--secondary-color, #764ba2) 100%);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: white;
    text-align: center;
    padding: 20px;
}

.transport-icon {
    font-size: 36px;
    margin-bottom: 8px;
    animation: bounce 2s infinite;
}

.transport-type {
    font-size: 14px;
    font-weight: 600;
    text-transform: capitalize;
    opacity: 0.9;
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% {
        transform: translateY(0);
    }
    40% {
        transform: translateY(-5px);
    }
    60% {
        transform: translateY(-3px);
    }
}

.card-route {
    font-size: 13px;
    color: #666;
    margin-top: 5px;
    font-weight: 500;
}

.card-duration,
.card-price {
    font-size: 12px;
    color: #888;
    margin-top: 3px;
}
</style>
`;

// ===== CORRECCIÓN 3: CLICK DIRECTO PARA ABRIR DETALLES =====

// Función para ver detalles del recurso (reemplaza el alert)
function viewResourceDetails(id, type) {
    console.log(`📋 Abriendo detalles del ${type} con ID: ${id}`);
    
    try {
        // Buscar el recurso en los datos
        const resource = resources[type]?.find(item => item.id === id);
        
        if (!resource) {
            showErrorMessage(`No se encontró el recurso con ID: ${id}`);
            return;
        }
        
        // Crear modal de detalles
        showResourceDetailsModal(resource, type);
        
    } catch (error) {
        console.error('❌ Error abriendo detalles:', error);
        showErrorMessage('Error al cargar los detalles del recurso');
    }
}

// Función para mostrar modal de detalles del recurso
function showResourceDetailsModal(resource, type) {
    // Crear overlay del modal
    const modalOverlay = document.createElement('div');
    modalOverlay.className = 'resource-details-modal-overlay';
    modalOverlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
        animation: fadeIn 0.3s ease;
    `;
    
    // Crear contenido del modal
    const modalContent = document.createElement('div');
    modalContent.className = 'resource-details-modal';
    modalContent.style.cssText = `
        background: white;
        border-radius: 16px;
        max-width: 600px;
        width: 90%;
        max-height: 80vh;
        overflow-y: auto;
        animation: slideIn 0.3s ease;
        box-shadow: 0 20px 40px rgba(0,0,0,0.2);
    `;
    
    // Generar contenido según el tipo
    modalContent.innerHTML = generateResourceDetailsContent(resource, type);
    
    modalOverlay.appendChild(modalContent);
    
    // Cerrar modal al hacer click en el overlay
    modalOverlay.addEventListener('click', function(e) {
        if (e.target === modalOverlay) {
            closeResourceDetailsModal(modalOverlay);
        }
    });
    
    // Cerrar con ESC
    document.addEventListener('keydown', function escapeHandler(e) {
        if (e.key === 'Escape') {
            closeResourceDetailsModal(modalOverlay);
            document.removeEventListener('keydown', escapeHandler);
        }
    });
    
    document.body.appendChild(modalOverlay);
}

// Función para generar contenido del modal de detalles
function generateResourceDetailsContent(resource, type) {
    const typeConfig = {
        'dias': {
            icon: '📅',
            title: 'Detalles del Día',
            fields: [
                { key: 'titulo', label: 'Título', icon: '📝' },
                { key: 'ubicacion', label: 'Ubicación', icon: '📍' },
                { key: 'descripcion', label: 'Descripción', icon: '📄' },
                { key: 'idioma', label: 'Idioma', icon: '🌐' }
            ]
        },
        'alojamientos': {
            icon: '🏨',
            title: 'Detalles del Alojamiento',
            fields: [
                { key: 'nombre', label: 'Nombre', icon: '🏨' },
                { key: 'tipo', label: 'Tipo', icon: '🏷️' },
                { key: 'categoria', label: 'Categoría', icon: '⭐' },
                { key: 'ubicacion', label: 'Ubicación', icon: '📍' },
                { key: 'descripcion', label: 'Descripción', icon: '📄' },
                { key: 'sitio_web', label: 'Sitio Web', icon: '🌐' }
            ]
        },
        'actividades': {
            icon: '🎯',
            title: 'Detalles de la Actividad',
            fields: [
                { key: 'titulo', label: 'Título', icon: '🎯' },
                { key: 'ubicacion', label: 'Ubicación', icon: '📍' },
                { key: 'descripcion', label: 'Descripción', icon: '📄' },
                { key: 'duracion', label: 'Duración', icon: '⏱️' },
                { key: 'precio', label: 'Precio', icon: '💰' }
            ]
        },
        'transportes': {
            icon: '🚗',
            title: 'Detalles del Transporte',
            fields: [
                { key: 'titulo', label: 'Título', icon: '📝' },
                { key: 'medio', label: 'Medio de Transporte', icon: '🚗' },
                { key: 'lugar_salida', label: 'Lugar de Salida', icon: '🛫' },
                { key: 'lugar_llegada', label: 'Lugar de Llegada', icon: '🛬' },
                { key: 'duracion', label: 'Duración', icon: '⏱️' },
                { key: 'precio', label: 'Precio', icon: '💰' },
                { key: 'descripcion', label: 'Descripción', icon: '📄' }
            ]
        }
    };
    
    const config = typeConfig[type];
    if (!config) return '<p>Tipo de recurso no reconocido</p>';
    
    // Header del modal
    let html = `
        <div style="padding: 24px; border-bottom: 1px solid #e2e8f0;">
            <div style="display: flex; justify-content: between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <span style="font-size: 24px;">${config.icon}</span>
                    <h2 style="margin: 0; color: #1a202c;">${config.title}</h2>
                </div>
                <button onclick="closeResourceDetailsModal(this.closest('.resource-details-modal-overlay'))" 
                        style="background: none; border: none; font-size: 24px; cursor: pointer; color: #718096;">
                    ×
                </button>
            </div>
        </div>
        
        <div style="padding: 24px;">
    `;
    
    // Imágenes
    const images = getResourceImages(resource, type);
    if (images.length > 0) {
        html += `
            <div style="margin-bottom: 24px;">
                <h3 style="margin-bottom: 12px; color: #2d3748;">📷 Imágenes</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px;">
                    ${images.map(img => `
                        <img src="${img}" alt="Imagen" 
                             style="width: 100%; height: 120px; object-fit: cover; border-radius: 8px; cursor: pointer;"
                             onclick="showImageModal('${img}', '${escapeHtml(resource.titulo || resource.nombre || 'Imagen')}')">
                    `).join('')}
                </div>
            </div>
        `;
    }
    
    // Campos de información
    html += '<div style="display: grid; gap: 16px;">';
    
    config.fields.forEach(field => {
        const value = resource[field.key];
        if (value) {
            let displayValue = value;
            
            // Formateo especial para ciertos campos
            if (field.key === 'categoria') {
                displayValue = `${'⭐'.repeat(parseInt(value))} (${value} estrellas)`;
            } else if (field.key === 'sitio_web') {
                displayValue = `<a href="${value}" target="_blank" style="color: var(--primary-color, #667eea); text-decoration: none;">${value}</a>`;
            } else if (field.key === 'medio') {
                displayValue = `${getTransportIcon(value)} ${value}`;
            }
            
            html += `
                <div style="display: flex; align-items: start; gap: 12px; padding: 12px; background: #f8fafc; border-radius: 8px;">
                    <span style="font-size: 18px; margin-top: 2px;">${field.icon}</span>
                    <div style="flex: 1;">
                        <div style="font-weight: 600; color: #2d3748; margin-bottom: 4px;">${field.label}</div>
                        <div style="color: #4a5568;">${displayValue}</div>
                    </div>
                </div>
            `;
        }
    });
    
    html += '</div>';
    
    // Coordenadas si existen
    if (resource.latitud && resource.longitud) {
        html += `
            <div style="margin-top: 24px; padding: 16px; background: #edf2f7; border-radius: 8px;">
                <h4 style="margin: 0 0 8px 0; color: #2d3748;">📍 Coordenadas</h4>
                <div style="font-family: monospace; color: #4a5568;">
                    Latitud: ${resource.latitud}<br>
                    Longitud: ${resource.longitud}
                </div>
            </div>
        `;
    }
    
    // Botones de acción
    html += `
        </div>
        <div style="padding: 24px; border-top: 1px solid #e2e8f0; display: flex; gap: 12px; justify-content: flex-end;">
            <button onclick="editResource(${resource.id}); closeResourceDetailsModal(this.closest('.resource-details-modal-overlay'));"
                    style="background: var(--primary-color, #667eea); color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer;">
                ✏️ Editar
            </button>
            <button onclick="closeResourceDetailsModal(this.closest('.resource-details-modal-overlay'))"
                    style="background: #e2e8f0; color: #4a5568; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer;">
                Cerrar
            </button>
        </div>
    `;
    
    return html;
}

// Función para obtener imágenes del recurso
function getResourceImages(resource, type) {
    const images = [];
    
    switch(type) {
        case 'dias':
        case 'actividades':
            if (resource.imagen1) images.push(resource.imagen1);
            if (resource.imagen2) images.push(resource.imagen2);
            if (resource.imagen3) images.push(resource.imagen3);
            break;
        case 'alojamientos':
            if (resource.imagen) images.push(resource.imagen);
            break;
    }
    
    return images;
}

// Función para cerrar modal de detalles
function closeResourceDetailsModal(modalOverlay) {
    modalOverlay.style.animation = 'fadeOut 0.3s ease';
    setTimeout(() => {
        if (modalOverlay.parentElement) {
            modalOverlay.remove();
        }
    }, 300);
}

// Función para mostrar mensajes de error
function showErrorMessage(message) {
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #fed7d7;
        color: #e53e3e;
        padding: 16px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(229, 62, 62, 0.3);
        z-index: 10001;
        animation: slideInRight 0.3s ease;
    `;
    toast.textContent = `❌ ${message}`;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 5000);
}

// ===== INICIALIZACIÓN =====

// Función para inicializar todas las correcciones
function initializeBibliotecaFixes() {
    console.log('🔧 Inicializando correcciones de Biblioteca...');
    
    // Agregar CSS para transportes
    document.head.insertAdjacentHTML('beforeend', transportCardCSS);
    
    // Configurar autocompletado avanzado
    setupAdvancedLocationAutocomplete();
    
    // Agregar estilos para animaciones
    const animationCSS = `
        <style>
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideIn {
            from { transform: scale(0.9) translateY(20px); opacity: 0; }
            to { transform: scale(1) translateY(0); opacity: 1; }
        }
        
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
        
        @keyframes slideInRight {
            from { transform: translateX(100%); }
            to { transform: translateX(0); }
        }
        </style>
    `;
    
    document.head.insertAdjacentHTML('beforeend', animationCSS);
    
    console.log('✅ Correcciones de Biblioteca inicializadas');
}

// Sobrescribir la función createResourceCard para usar las nuevas funciones
const originalCreateResourceCard = window.createResourceCard;
window.createResourceCard = function(item) {
    if (currentTab === 'transportes') {
        return createTransportCard(item);
    }
    
    // Para otros tipos, usar la función original pero con click mejorado
    const card = originalCreateResourceCard ? originalCreateResourceCard(item) : '';
    return card.replace(
        'onclick="viewResource(',
        'onclick="viewResourceDetails('
    ).replace(
        '"viewResource(',
        '"viewResourceDetails('
    );
};

// Sobrescribir viewResource para usar la nueva función
window.viewResource = function(id) {
    viewResourceDetails(id, currentTab);
};

// Inicializar al cargar el DOM
document.addEventListener('DOMContentLoaded', initializeBibliotecaFixes);

// También inicializar si ya está cargado
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeBibliotecaFixes);
} else {
    initializeBibliotecaFixes();
}
const style = document.createElement('style');
style.textContent = `
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
`;
document.head.appendChild(style);

document.addEventListener('DOMContentLoaded', function() {
    const style = document.createElement('style');
    style.textContent = additionalCSS;
    document.head.appendChild(style);
});
// ===== SISTEMA DE CARGA MÚLTIPLE DE IMÁGENES =====

// 1. Función principal de inicialización
function initializeMultipleImageUpload() {
    console.log('🖼️ Inicializando sistema de carga múltiple de imágenes...');
    
    const dropZone = document.getElementById('dropZoneMultiple');
    const fileInput = document.getElementById('multipleImages');
    const previewContainer = document.getElementById('imagesPreviewContainer');
    
    if (!dropZone || !fileInput) {
        console.log('❌ Elementos no encontrados para carga múltiple');
        return;
    }
    
    // Array global para mantener las imágenes seleccionadas
    window.selectedImages = window.selectedImages || [];
    
    // Configurar eventos
    setupDropZoneEvents(dropZone, fileInput, previewContainer);
    setupFileInputEvents(fileInput, previewContainer);
    setupSelectButtonEvents(dropZone);
    
    console.log('✅ Sistema de carga múltiple inicializado');
}

// 2. Configurar eventos de la zona de arrastre
function setupDropZoneEvents(dropZone, fileInput, previewContainer) {
    // Prevenir comportamiento por defecto
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
    });
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    // Efectos visuales
    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => {
            dropZone.classList.add('drag-over');
        });
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => {
            dropZone.classList.remove('drag-over');
        });
    });
    
    // Manejar drop
    dropZone.addEventListener('drop', function(e) {
        const files = Array.from(e.dataTransfer.files);
        handleNewFiles(files, previewContainer);
    });
    
    // Click en zona de arrastre
    dropZone.addEventListener('click', function() {
        fileInput.click();
    });
}

// 3. Configurar eventos del input de archivos
function setupFileInputEvents(fileInput, previewContainer) {
    fileInput.addEventListener('change', function() {
        const files = Array.from(this.files);
        handleNewFiles(files, previewContainer);
    });
}

// 4. Configurar botón de selección
function setupSelectButtonEvents(dropZone) {
    dropZone.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-select-images')) {
            e.preventDefault();
            e.stopPropagation();
            document.getElementById('multipleImages').click();
        }
    });
}


function handleNewFiles(files, previewContainer) {
    console.log(`📁 Procesando ${files.length} archivo(s)...`);
    
    // Filtrar solo imágenes
    const imageFiles = files.filter(file => file.type.startsWith('image/'));
    
    if (imageFiles.length === 0) {
        showMessage('Por favor selecciona solo archivos de imagen', 'error');
        return;
    }
    
    // Calcular slots disponibles
    const existingCount = document.querySelectorAll('.existing-image').length;
    const newImagesCount = window.selectedImages ? window.selectedImages.length : 0;
    const availableSlots = 3 - existingCount - newImagesCount;
    
    if (availableSlots === 0) {
        showMessage('Ya tienes 3 imágenes. Elimina alguna para agregar nuevas.', 'error');
        return;
    }
    
    // Tomar solo las que caben
    const filesToAdd = imageFiles.slice(0, availableSlots);
    
    if (filesToAdd.length < imageFiles.length) {
        showMessage(`Solo se pueden agregar ${filesToAdd.length} imágenes más`, 'warning');
    }
    
    // Agregar al array global
    const selectedImages = window.selectedImages || [];
    filesToAdd.forEach(file => {
        if (validateImageFile(file)) {
            selectedImages.push(file);
        }
    });
    
    window.selectedImages = selectedImages;
    
    // Actualizar previews e inputs
    updateImagePreviews(selectedImages, previewContainer);
    updateHiddenInputs(selectedImages);
    
    console.log(`✅ ${filesToAdd.length} imagen(es) agregada(s)`);
}


// 6. Validar archivo de imagen
function validateImageFile(file) {
    // Validar tipo
    if (!file.type.startsWith('image/')) {
        showMessage(`"${file.name}" no es una imagen válida`, 'error');
        return false;
    }
    
    // Validar tamaño (5MB máximo)
    if (file.size > 5 * 1024 * 1024) {
        showMessage(`"${file.name}" es demasiado grande (máx. 5MB)`, 'error');
        return false;
    }
    
    return true;
}

// 7. Actualizar previsualizaciones
function updateImagePreviews(selectedImages, previewContainer) {
    // Limpiar contenedor
    previewContainer.innerHTML = '';
    
    // Crear preview para cada imagen
    selectedImages.forEach((file, index) => {
        createImagePreview(file, index, previewContainer);
    });
    
    // Mostrar información
    updateDropZoneInfo(getTotalImageCount());
}
function getTotalImageCount() {
    const existingCount = document.querySelectorAll('.existing-image').length;
    const newImagesCount = window.selectedImages ? window.selectedImages.length : 0;
    return existingCount + newImagesCount;
}

// 8. Crear preview individual
function createImagePreview(file, index, previewContainer) {
    const reader = new FileReader();
    
    reader.onload = function(e) {
        const previewItem = document.createElement('div');
        previewItem.className = 'image-preview-item';
        previewItem.innerHTML = `
            <img src="${e.target.result}" alt="${file.name}">
            <div class="image-preview-info">
                <div class="image-preview-name">${file.name}</div>
                <div class="image-preview-size">${formatFileSize(file.size)}</div>
            </div>
            <button type="button" class="image-remove-btn" onclick="removeImageAtIndex(${index})">
                ×
            </button>
            <div class="image-slot-indicator">
                Imagen ${index + 1}
            </div>
        `;
        
        previewContainer.appendChild(previewItem);
    };
    
    reader.readAsDataURL(file);
}

// 9. Actualizar inputs ocultos
function updateHiddenInputs(selectedImages) {
    console.log('🔧 Actualizando inputs ocultos...');
    
    // Identificar qué slots están ocupados por imágenes existentes
    const existingImages = document.querySelectorAll('.existing-image[data-field]');
    const occupiedSlots = [];
    
    existingImages.forEach(img => {
        const field = img.getAttribute('data-field');
        if (field) {
            const slotNumber = parseInt(field.replace('imagen', ''));
            occupiedSlots.push(slotNumber);
        }
    });
    
    console.log('🔒 Slots ocupados por existentes:', occupiedSlots);
    
    // Asignar nuevas imágenes a slots libres
    let fileIndex = 0;
    for (let slot = 1; slot <= 3 && fileIndex < selectedImages.length; slot++) {
        if (!occupiedSlots.includes(slot)) {
            const inputId = `imagen${slot}`;
            const input = document.getElementById(inputId);
            
            if (input && fileIndex < selectedImages.length) {
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(selectedImages[fileIndex]);
                input.files = dataTransfer.files;
                
                console.log(`📎 Archivo asignado a ${inputId}:`, selectedImages[fileIndex].name);
                fileIndex++;
            }
        }
    }
    
    console.log(`✅ ${fileIndex} archivos asignados de ${selectedImages.length}`);
}

// 10. Actualizar información de la zona de arrastre
function updateDropZoneInfo(totalCount) {
    const dropZone = document.getElementById('dropZoneMultiple');
    const content = dropZone?.querySelector('.drop-zone-content');
    
    if (content) {
        if (totalCount === 0) {
            content.innerHTML = `
                <div style="font-size: 48px; margin-bottom: 15px;">📸</div>
                <div style="font-size: 18px; font-weight: 600; margin-bottom: 8px;">
                    Arrastra hasta 3 imágenes aquí
                </div>
                <div style="font-size: 14px; color: #718096; margin-bottom: 15px;">
                    o haz clic para seleccionar archivos
                </div>
                <button type="button" class="btn-select-images">
                    📂 Seleccionar Imágenes
                </button>
            `;
        } else if (totalCount < 3) {
            content.innerHTML = `
                <div style="font-size: 36px; margin-bottom: 10px;">✅</div>
                <div style="font-size: 16px; font-weight: 600; margin-bottom: 8px;">
                    ${totalCount} imagen(es) total
                </div>
                <div style="font-size: 14px; color: #718096; margin-bottom: 15px;">
                    Puedes agregar ${3 - totalCount} más
                </div>
                <button type="button" class="btn-select-images">
                    📂 Agregar Más Imágenes
                </button>
            `;
        } else {
            content.innerHTML = `
                <div style="font-size: 36px; margin-bottom: 10px;">🎉</div>
                <div style="font-size: 16px; font-weight: 600; margin-bottom: 8px;">
                    3 imágenes completas
                </div>
                <div style="font-size: 14px; color: #718096;">
                    Límite alcanzado. Elimina alguna para cambiar.
                </div>
            `;
        }
    }
}

// 11. Función auxiliar para formatear tamaño de archivo
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// 12. Función global para remover imágenes
window.removeImageAtIndex = function(index) {
    const previewContainer = document.getElementById('imagesPreviewContainer');
    const selectedImages = window.selectedImages || [];
    
    if (index >= 0 && index < selectedImages.length) {
        // Remover del array
        selectedImages.splice(index, 1);
        
        // Actualizar array global
        window.selectedImages = selectedImages;
        
        // Actualizar previews
        updateImagePreviews(selectedImages, previewContainer);
        
        // Actualizar inputs ocultos
        updateHiddenInputs(selectedImages);
        
        console.log(`🗑️ Imagen ${index + 1} eliminada. Quedan: ${selectedImages.length}`);
    }
};

// 13. Inicializar automáticamente
window.initializeMultipleImageUpload = initializeMultipleImageUpload;
window.selectedImages = [];
// ===== FUNCIONES PARA IMÁGENES EXISTENTES =====

// Función para cargar imágenes existentes en el sistema múltiple
function loadExistingImagesInMultipleSystem(resource) {
    console.log('📄 Cargando imágenes existentes...', resource);
    
    const previewContainer = document.getElementById('imagesPreviewContainer');
    if (!previewContainer) return;
    
    // Limpiar COMPLETAMENTE el contenedor
    previewContainer.innerHTML = '';
    
    // Array de imágenes existentes - SIN DUPLICAR
    const existingImages = [];
    
    // Solo agregar si realmente existe y no está duplicada
    if (resource.imagen1 && resource.imagen1.trim()) {
        existingImages.push({ 
            url: resource.imagen1.trim(), 
            name: 'Imagen 1 existente',
            field: 'imagen1'
        });
    }
    
    if (resource.imagen2 && resource.imagen2.trim() && resource.imagen2 !== resource.imagen1) {
        existingImages.push({ 
            url: resource.imagen2.trim(), 
            name: 'Imagen 2 existente',
            field: 'imagen2'
        });
    }
    
    if (resource.imagen3 && resource.imagen3.trim() && 
        resource.imagen3 !== resource.imagen1 && 
        resource.imagen3 !== resource.imagen2) {
        existingImages.push({ 
            url: resource.imagen3.trim(), 
            name: 'Imagen 3 existente',
            field: 'imagen3'
        });
    }
    
    console.log('📸 Imágenes únicas encontradas:', existingImages.length);
    
    // Crear previews SOLO para imágenes únicas
    existingImages.forEach((img, index) => {
        createExistingImagePreviewFixed(img.url, img.name, img.field, index, previewContainer);
    });
    
    // Actualizar información
    updateDropZoneInfo(existingImages.length);
}

// Crear preview de imagen existente - VERSIÓN CORREGIDA
function createExistingImagePreviewFixed(imageUrl, imageName, fieldName, displayIndex, previewContainer) {
    // Obtener el índice real del campo (imagen1=0, imagen2=1, imagen3=2)
    const fieldIndex = parseInt(fieldName.replace('imagen', '')) - 1;
    
    const previewItem = document.createElement('div');
    previewItem.className = 'image-preview-item existing-image';
    previewItem.setAttribute('data-field', fieldName); // Para identificación
    
    previewItem.innerHTML = `
        <img src="${imageUrl}" alt="${imageName}">
        <div class="image-preview-info">
            <div class="image-preview-name">${imageName}</div>
            <div class="image-preview-size">Imagen guardada</div>
        </div>
        <button type="button" class="image-remove-btn" onclick="removeExistingImageByField('${fieldName}')">
            ×
        </button>
        <div class="image-slot-indicator" style="background: rgba(16, 185, 129, 0.9);">
            ✅ ${fieldName}
        </div>
    `;
    
    previewContainer.appendChild(previewItem);
    console.log(`📎 Preview creado para ${fieldName}`);
}

// Crear preview de imagen existente
function createExistingImagePreview(imageUrl, imageName, index, previewContainer) {
    const previewItem = document.createElement('div');
    previewItem.className = 'image-preview-item existing-image';
    previewItem.innerHTML = `
        <img src="${imageUrl}" alt="${imageName}">
        <div class="image-preview-info">
            <div class="image-preview-name">${imageName}</div>
            <div class="image-preview-size">Imagen guardada</div>
        </div>
        <button type="button" class="image-remove-btn" onclick="removeExistingImageAtIndex(${index})">
            ×
        </button>
        <div class="image-slot-indicator" style="background: rgba(16, 185, 129, 0.9);">
            ✅ Imagen ${index + 1}
        </div>
    `;
    
    previewContainer.appendChild(previewItem);
}
// Remover imagen existente por campo - VERSIÓN CORREGIDA
window.removeExistingImageByField = function(fieldName) {
    console.log('🗑️ Eliminando imagen:', fieldName);
    
    const previewContainer = document.getElementById('imagesPreviewContainer');
    const imageItem = previewContainer.querySelector(`[data-field="${fieldName}"]`);
    
    if (imageItem) {
        // Remover del DOM
        imageItem.remove();
        
        // Marcar para eliminación en el servidor
        const deleteInput = document.createElement('input');
        deleteInput.type = 'hidden';
        deleteInput.name = `delete_${fieldName}`;
        deleteInput.value = '1';
        document.getElementById('resourceForm').appendChild(deleteInput);
        
        // Actualizar contador
        const remainingCount = previewContainer.querySelectorAll('.existing-image').length;
        updateDropZoneInfo(remainingCount);
        
        console.log(`✅ ${fieldName} marcada para eliminación`);
    }
};
window.removeExistingImageAtIndex = function(index) {
    console.log('🗑️ Eliminando imagen existente índice:', index);
    
    const previewContainer = document.getElementById('imagesPreviewContainer');
    const existingItems = Array.from(previewContainer.querySelectorAll('.existing-image'));
    
    if (index >= 0 && index < existingItems.length) {
        // Remover elemento del DOM
        existingItems[index].remove();
        
        // Marcar para eliminación en el servidor
        const fieldName = `imagen${index + 1}`;
        const deleteInput = document.createElement('input');
        deleteInput.type = 'hidden';
        deleteInput.name = `delete_${fieldName}`;
        deleteInput.value = '1';
        document.getElementById('resourceForm').appendChild(deleteInput);
        
        console.log(`✅ Imagen existente ${index + 1} marcada para eliminación`);
    }
};


// NUEVO SISTEMA DE AUTOCOMPLETADO
(function() {
    let searchTimeout;
    
    function setupLocationFields() {
        const fields = ['ubicacion', 'lugar_salida', 'lugar_llegada'];
        
        fields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (!field) return;
            
            // Limpiar eventos anteriores
            const newField = field.cloneNode(true);
            field.parentNode.replaceChild(newField, field);
            
            newField.addEventListener('input', function() {
                const query = this.value.trim();
                
                clearTimeout(searchTimeout);
                clearSuggestions();
                
                if (query.length < 3) return;
                
                searchTimeout = setTimeout(() => {
                    searchLocationSimple(query, newField, fieldId);
                }, 400);
            });
            
            newField.addEventListener('blur', () => {
                setTimeout(clearSuggestions, 200);
            });
        });
    }
    
    async function searchLocationSimple(query, field, fieldType) {
        try {
            const response = await fetch(
                `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=5&addressdetails=1`
            );
            const results = await response.json();
            
            if (results.length > 0) {
                showSimpleSuggestions(results, field, fieldType);
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }
    
    function showSimpleSuggestions(results, field, fieldType) {
        const container = document.createElement('div');
        container.className = 'simple-suggestions';
        container.style.cssText = `
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            z-index: 1000;
            max-height: 200px;
            overflow-y: auto;
        `;
        
        results.forEach(result => {
            const item = document.createElement('div');
            item.style.cssText = `
                padding: 10px;
                cursor: pointer;
                border-bottom: 1px solid #eee;
            `;
            item.textContent = result.display_name;
            
            item.onmouseenter = () => item.style.backgroundColor = '#f0f0f0';
            item.onmouseleave = () => item.style.backgroundColor = 'white';
            
            item.onclick = () => {
                field.value = result.display_name;
                
                // Actualizar coordenadas
                if (fieldType === 'ubicacion') {
                    const latField = document.getElementById('latitud');
                    const lngField = document.getElementById('longitud');
                    if (latField) latField.value = result.lat;
                    if (lngField) lngField.value = result.lon;
                    
                    // Actualizar mapa
                    if (window.map && window.L) {
                        window.map.setView([result.lat, result.lon], 15);
                        if (window.currentMarker) {
                            window.map.removeLayer(window.currentMarker);
                        }
                        window.currentMarker = window.L.marker([result.lat, result.lon]).addTo(window.map);
                    }
                }
                
                clearSuggestions();
            };
            
            container.appendChild(item);
        });
        
        field.parentElement.style.position = 'relative';
        field.parentElement.appendChild(container);
        window.currentSuggestions = container;
    }
    
    function clearSuggestions() {
        if (window.currentSuggestions) {
            window.currentSuggestions.remove();
            window.currentSuggestions = null;
        }
        document.querySelectorAll('.simple-suggestions').forEach(el => el.remove());
    }
    
    // Sobrescribir funciones existentes
    window.setupLocationAutocomplete = setupLocationFields;
    
    // Inicializar en modal
    const modal = document.getElementById('resourceModal');
    if (modal) {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes' && modal.classList.contains('show')) {
                    setTimeout(setupLocationFields, 300);
                }
            });
        });
        observer.observe(modal, { attributes: true, attributeFilter: ['class'] });
    }
    
    // Inicializar inmediatamente
    setTimeout(setupLocationFields, 500);
})();

// FUNCIONES PARA UBICACIONES SECUNDARIAS
async function searchSecondaryLocation(query, input, latInput, lngInput, preview, index) {
    try {
        console.log(`Buscando ubicación secundaria ${index}: ${query}`);
        
        const response = await fetch(
            `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=5&addressdetails=1&accept-language=es`
        );
        
        if (!response.ok) throw new Error('Error en la búsqueda');
        
        const results = await response.json();
        
        // Limpiar indicador de búsqueda
        input.style.backgroundImage = '';
        
        if (results.length > 0) {
            showSecondaryLocationSuggestions(results, input, latInput, lngInput, preview, index);
        } else {
            console.log(`No se encontraron resultados para: ${query}`);
        }
        
    } catch (error) {
        console.error('Error buscando ubicación secundaria:', error);
        input.style.backgroundImage = '';
    }
}

function showSecondaryLocationSuggestions(results, input, latInput, lngInput, preview, index) {
    clearSecondaryLocationSuggestions(input);
    
    const container = document.createElement('div');
    container.className = `secondary-location-suggestions-${index}`;
    container.style.cssText = `
        position: absolute;
        top: 100%;
        left: 0;
        right: 40px;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        z-index: 1000;
        max-height: 200px;
        overflow-y: auto;
    `;
    
    results.forEach((result, suggestionIndex) => {
        const item = document.createElement('div');
        item.className = 'secondary-suggestion-item';
        item.style.cssText = `
            padding: 12px;
            cursor: pointer;
            border-bottom: 1px solid #f7fafc;
            transition: background 0.2s ease;
        `;
        
        const mainName = result.display_name.split(',')[0];
        item.innerHTML = `
            <div style="font-weight: 600; color: #2d3748;">${mainName}</div>
            <div style="font-size: 12px; color: #718096;">${result.display_name}</div>
        `;
        
        // Efectos hover
        item.addEventListener('mouseenter', () => {
            item.style.background = '#f7fafc';
        });
        
        item.addEventListener('mouseleave', () => {
            item.style.background = 'white';
        });
        
        // Click handler
        item.addEventListener('click', () => {
            selectSecondaryLocation(result, input, latInput, lngInput, preview, index);
        });
        
        container.appendChild(item);
    });
    
    // Posicionar el contenedor
    input.parentElement.style.position = 'relative';
    input.parentElement.appendChild(container);
    
    console.log(`Sugerencias mostradas para ubicación secundaria ${index}`);
}

function selectSecondaryLocation(location, input, latInput, lngInput, preview, index) {
    console.log(`Ubicación secundaria ${index} seleccionada:`, location.display_name);
    
    // Actualizar campo de texto
    input.value = location.display_name;
    
    // Actualizar coordenadas
    if (latInput) latInput.value = location.lat;
    if (lngInput) lngInput.value = location.lon;
    
    // Mostrar preview
    if (preview) {
        preview.innerHTML = `📍 ${location.display_name}`;
        preview.classList.add('show');
        preview.style.display = 'block';
    }
    
    // Limpiar sugerencias
    clearSecondaryLocationSuggestions(input);
}

function clearSecondaryLocationSuggestions(input) {
    if (!input || !input.parentElement) return;
    
    // Remover todas las sugerencias de ubicaciones secundarias
    const suggestions = input.parentElement.querySelectorAll('[class*="secondary-location-suggestions"]');
    suggestions.forEach(suggestion => {
        suggestion.remove();
    });
}
// ===== FIN SISTEMA DE CARGA MÚLTIPLE =====
</script>
<!-- Agregar antes del cierre de </body> -->
<a href="<?= APP_URL ?>/itinerarios" class="floating-itinerarios-btn" title="Ir a Itinerarios">
    <span class="btn-icon">🗺️</span>
    <span class="btn-text">ITINERARIOS</span>
</a>
</body>
</html>