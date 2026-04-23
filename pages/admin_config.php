<?php
// =====================================
// ARCHIVO: pages/admin_config.php - Configuración del Sistema con Componentes UI
// =====================================

App::requireRole('admin');

// Incluir ConfigManager y componentes UI
require_once 'config/config_functions.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/ui_components.php';

$user = App::getUser();

// Inicializar ConfigManager
ConfigManager::init();
$config = ConfigManager::get();
$adminColors = ConfigManager::getColorsForRole('admin');
$companyName = ConfigManager::getCompanyName();
$logo = ConfigManager::getLogo();
$defaultLanguage = ConfigManager::getDefaultLanguage();
?>
<!DOCTYPE html>
<html lang="<?= $defaultLanguage ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - <?= htmlspecialchars($companyName) ?></title>
    
    <!-- Incluir estilos de componentes -->
    <?= UIComponents::getComponentStyles() ?>
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
     
        :root {
            --admin-primary: <?= $adminColors['primary'] ?>;
            --admin-secondary: <?= $adminColors['secondary'] ?>;
            --admin-gradient: linear-gradient(135deg, var(--admin-primary) 0%, var(--admin-secondary) 100%);
            --primary-color: var(--admin-primary);
            --secondary-color: var(--admin-secondary);
            --primary-gradient: var(--admin-gradient);
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            color: #333;
            min-height: 100vh;
        }

        /* Header con componentes */
        .header {
            background: var(--admin-gradient);
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
            .VIpgJd-ZVi9od-ORHb-OEVmcd {
                left: 0;
                display: none !important;
                top: 0;
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

        /* Configuration Sections */
        .config-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border-left: 4px solid var(--admin-primary);
        }

        .section-title {
            font-size: 20px;
            color: #2d3748;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            font-weight: 600;
            color: #4a5568;
            font-size: 14px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--admin-primary);
            box-shadow: 0 0 0 3px rgba(229, 62, 62, 0.1);
        }

        /* Color Picker */
        .color-input {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .color-picker {
            width: 60px;
            height: 40px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            overflow: hidden;
        }

        .color-text {
            flex: 1;
            font-family: monospace;
            text-transform: uppercase;
        }

        /* Image Upload */
        .image-upload {
            border: 2px dashed #e2e8f0;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .image-upload:hover {
            border-color: var(--admin-primary);
            background-color: #fef5f5;
        }

        .image-upload.dragover {
            border-color: var(--admin-primary);
            background-color: #fef5f5;
        }

        .image-upload input {
            display: none;
        }

        .upload-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .upload-icon {
            font-size: 48px;
            color: var(--admin-primary);
        }

        .image-preview {
            max-width: 100%;
            max-height: 200px;
            border-radius: 8px;
            margin-top: 10px;
        }

        /* Preview Section */
        .preview-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .preview-header {
            padding: 20px 30px;
            border-radius: 10px;
            color: white;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .preview-company {
            font-size: 24px;
            font-weight: bold;
        }

        .preview-tagline {
            opacity: 0.9;
            margin-top: 5px;
        }

        /* Role Preview Tabs */
        .preview-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .preview-tab {
            padding: 10px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }

        .preview-tab.active {
            border-color: var(--admin-primary);
            background: var(--admin-primary);
            color: white;
        }

        /* Save Button */
        .save-section {
            text-align: center;
            margin: 30px 0;
        }

        .save-btn {
            background: var(--admin-gradient);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(229, 62, 62, 0.3);
        }

        .save-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(229, 62, 62, 0.4);
        }

        .save-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        /* Success/Error Messages */
        .message {
            padding: 15px 20px;
            border-radius: 10px;
            margin: 20px 0;
            font-weight: 500;
            display: none;
        }

        .message.success {
            background: #c6f6d5;
            color: #2f855a;
            border: 1px solid #9ae6b4;
        }

        .message.error {
            background: #fed7d7;
            color: #e53e3e;
            border: 1px solid #feb2b2;
        }

        /* Advanced Settings */
        .advanced-toggle {
            background: #f7fafc;
            padding: 15px 20px;
            border-radius: 10px;
            cursor: pointer;
            border: 1px solid #e2e8f0;
            margin: 20px 0;
            transition: all 0.3s ease;
        }

        .advanced-toggle:hover {
            background: #edf2f7;
        }

        .advanced-content {
            display: none;
            margin-top: 20px;
        }

        .advanced-content.show {
            display: block;
        }

        /* Loading Spinner */
        .loading-spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid var(--admin-primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-left: 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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

            .form-grid {
                grid-template-columns: 1fr;
            }

            .config-section {
                padding: 20px;
            }
        }

        /* Toast notifications (igual que admin.php) */
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
    background: linear-gradient(135deg, var(--admin-primary) 0%, #dc2626 100%);
}
    </style>
</head>
<body>
    <!-- Header con componentes -->
    <?= UIComponents::renderHeader($user) ?>

    <!-- Sidebar con componentes -->
    <?= UIComponents::renderSidebar($user, '/administrador/configuracion') ?>

    <!-- Overlay -->
    <div class="overlay" id="overlay" onclick="closeSidebar()"></div>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Preview Section -->
        <div class="preview-section">
            <h2 class="section-title">
                <span>👁️</span>
                Vista Previa por Roles
            </h2>

            <!-- Tabs para diferentes vistas -->
            <div class="preview-tabs">
                <div class="preview-tab active" onclick="switchPreview('admin')">👑 Vista Admin</div>
                <div class="preview-tab" onclick="switchPreview('agent')">✈️ Vista Agente</div>
            </div>

            <!-- Admin Preview -->
            <div class="preview-header" id="adminPreview" style="background: linear-gradient(135deg, <?= $config['admin_primary_color'] ?> 0%, <?= $config['admin_secondary_color'] ?> 100%);">
                <div class="preview-company" id="companyPreviewAdmin"><?= htmlspecialchars($config['company_name']) ?></div>
                <div class="preview-tagline">Panel de Administración</div>
            </div>

            <!-- Agent Preview -->
            <div class="preview-header" id="agentPreview" style="background: linear-gradient(135deg, <?= $config['agent_primary_color'] ?> 0%, <?= $config['agent_secondary_color'] ?> 100%); display: none;">
                <div class="preview-company" id="companyPreviewAgent"><?= htmlspecialchars($config['company_name']) ?></div>
                <div class="preview-tagline">Sistema de Gestión de Viajes</div>
            </div>
        </div>

        <!-- Messages -->
        <div id="successMessage" class="message success"></div>
        <div id="errorMessage" class="message error"></div>

        <!-- Configuration Form -->
        <form id="configForm">
            <!-- Basic Settings -->
            <div class="config-section">
                <h2 class="section-title">
                    <span>🏢</span>
                    Información de la Empresa
                </h2>
                
                <div class="form-grid">
                    <div class="form-group">
                    <label for="company_name">Nombre de la Agencia</label>
                    <input type="text" id="company_name" name="company_name" 
                        value="<?= htmlspecialchars($config['company_name']) ?>" 
                        placeholder="Travel Agency" readonly 
                        style="background-color: #f1f5f9; cursor: not-allowed; color: #64748b;">
                    <small style="color: #64748b; font-size: 12px; display: block; margin-top: 5px;">
                        ℹ️ El nombre de la agencia solo puede ser modificado por el Superadmin desde la gestión de agencias
                    </small>
                </div>
                </div>

                <div class="form-grid" style="margin-top: 25px;">
                    <div class="form-group">
                        <label for="logo_url">Logo de la Empresa</label>
                        <div class="image-upload" onclick="document.getElementById('logoInput').click()">
                            <input type="file" id="logoInput" accept="image/*">
                            <div class="upload-content">
                                <div class="upload-icon">📷</div>
                                <div>
                                    <strong>Subir Logo</strong><br>
                                    <small>PNG, JPG, SVG o WebP (máx. <?= $config['max_file_size'] ?>MB)</small>
                                </div>
                            </div>
                            <?php if ($config['logo_url']): ?>
                            <img src="<?= htmlspecialchars($config['logo_url']) ?>" 
                                 class="image-preview" id="logoPreview">
                            <?php endif; ?>
                        </div>
                        <input type="hidden" id="logo_url" name="logo_url" value="<?= htmlspecialchars($config['logo_url'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <!-- Color Settings -->
            <div class="config-section">
                <h2 class="section-title">
                    <span>🎨</span>
                    Personalización de Colores por Roles
                </h2>
                
                <!-- Admin Colors -->
                <h3 style="margin-bottom: 15px; color: #e53e3e;">👑 Colores del Administrador</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="admin_primary_color">Color Primario Admin</label>
                        <div class="color-input">
                            <input type="color" id="admin_primary_color" name="admin_primary_color" 
                                   class="color-picker" value="<?= $config['admin_primary_color'] ?>">
                            <input type="text" class="color-text" 
                                   value="<?= $config['admin_primary_color'] ?>" readonly>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="admin_secondary_color">Color Secundario Admin</label>
                        <div class="color-input">
                            <input type="color" id="admin_secondary_color" name="admin_secondary_color" 
                                   class="color-picker" value="<?= $config['admin_secondary_color'] ?>">
                            <input type="text" class="color-text" 
                                   value="<?= $config['admin_secondary_color'] ?>" readonly>
                        </div>
                    </div>
                </div>

                <!-- Agent Colors -->
                <h3 style="margin: 25px 0 15px 0; color: #667eea;">✈️ Colores del Agente</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="agent_primary_color">Color Primario Agente</label>
                        <div class="color-input">
                            <input type="color" id="agent_primary_color" name="agent_primary_color" 
                                   class="color-picker" value="<?= $config['agent_primary_color'] ?>">
                            <input type="text" class="color-text" 
                                   value="<?= $config['agent_primary_color'] ?>" readonly>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="agent_secondary_color">Color Secundario Agente</label>
                        <div class="color-input">
                            <input type="color" id="agent_secondary_color" name="agent_secondary_color" 
                                   class="color-picker" value="<?= $config['agent_secondary_color'] ?>">
                            <input type="text" class="color-text" 
                                   value="<?= $config['agent_secondary_color'] ?>" readonly>
                        </div>
                    </div>
                </div>
            </div>


            <div class="advanced-content" id="advancedContent">
                <div class="config-section">
                    <h2 class="section-title">
                        <span>🔧</span>
                        Configuraciones Técnicas
                    </h2>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="session_timeout">⏱️ Tiempo de Sesión (minutos)</label>
                            <input type="number" id="session_timeout" name="session_timeout" 
                                   value="<?= $config['session_timeout'] ?>" min="15" max="480" placeholder="60">
                            <small style="color: #718096;">Tiempo antes de cerrar sesión automáticamente (15-480 min)</small>
                        </div>

                        <div class="form-group">
                            <label for="maintenance_mode">🚧 Modo Mantenimiento</label>
                            <select id="maintenance_mode" name="maintenance_mode">
                                <option value="0" <?= !$config['maintenance_mode'] ? 'selected' : '' ?>>✅ Desactivado</option>
                                <option value="1" <?= $config['maintenance_mode'] ? 'selected' : '' ?>>🔒 Activado</option>
                            </select>
                            <small style="color: #718096;">Bloquea el acceso a usuarios no administradores</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save Section -->
            <div class="save-section">
                <button type="submit" class="save-btn" id="saveBtn">
                    💾 Guardar Configuración
                    <div class="loading-spinner" id="loadingSpinner"></div>
                </button>
            </div>
        </form>
    </div>

    <!-- Scripts -->
    <script>
        const APP_URL = '<?= APP_URL ?>';
        let isLoading = false;
        let currentPreview = 'admin';
        let sidebarOpen = false;

        // Inicialización
        document.addEventListener('DOMContentLoaded', function() {
            initializeColorPickers();
            initializeImageUploads();
            initializeFormHandlers();
            initializeGoogleTranslate();
            applyDefaultLanguage();
        });

        // Funciones de sidebar
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            const mainContent = document.getElementById('mainContent');
            
            sidebarOpen = !sidebarOpen;
            
            if (sidebarOpen) {
                sidebar.classList.add('open');
                overlay.classList.add('show');
                if (window.innerWidth > 768) {
                    mainContent.classList.add('sidebar-open');
                }
            } else {
                sidebar.classList.remove('open');
                overlay.classList.remove('show');
                mainContent.classList.remove('sidebar-open');
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

        // Aplicar idioma por defecto del sistema
        function applyDefaultLanguage() {
            const defaultLang = '<?= $config['default_language'] ?>';
            if (defaultLang && defaultLang !== 'es') {
                setTimeout(() => {
                    const select = document.querySelector('.goog-te-combo');
                    if (select) {
                        select.value = defaultLang;
                        select.dispatchEvent(new Event('change'));
                    }
                }, 2000);
            }
        }

        // Configurar color pickers
        function initializeColorPickers() {
            const colorInputs = [
                'admin_primary_color', 'admin_secondary_color',
                'agent_primary_color', 'agent_secondary_color'
            ];

            colorInputs.forEach(inputId => {
                const colorPicker = document.getElementById(inputId);
                if (colorPicker) {
                    colorPicker.addEventListener('change', function() {
                        this.nextElementSibling.value = this.value;
                        updatePreview();
                    });
                }
            });

            // Actualizar preview cuando cambie el nombre
            document.getElementById('company_name').addEventListener('input', updatePreview);
        }

        // Cambiar vista previa
        function switchPreview(type) {
            currentPreview = type;
            
            // Actualizar tabs
            document.querySelectorAll('.preview-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // Mostrar/ocultar previews
            document.getElementById('adminPreview').style.display = type === 'admin' ? 'block' : 'none';
            document.getElementById('agentPreview').style.display = type === 'agent' ? 'block' : 'none';
        }

        // Actualizar vista previa
        function updatePreview() {
            const companyName = document.getElementById('company_name').value || 'Travel Agency';
            
            // Obtener colores
            const adminPrimary = document.getElementById('admin_primary_color').value;
            const adminSecondary = document.getElementById('admin_secondary_color').value;
            const agentPrimary = document.getElementById('agent_primary_color').value;
            const agentSecondary = document.getElementById('agent_secondary_color').value;

            // Actualizar nombres
            document.getElementById('companyPreviewAdmin').textContent = companyName;
            document.getElementById('companyPreviewAgent').textContent = companyName;

            // Actualizar fondos
            document.getElementById('adminPreview').style.background = 
                `linear-gradient(135deg, ${adminPrimary} 0%, ${adminSecondary} 100%)`;
            document.getElementById('agentPreview').style.background = 
                `linear-gradient(135deg, ${agentPrimary} 0%, ${agentSecondary} 100%)`;
        }

        // Configurar subida de imágenes
        function initializeImageUploads() {
            setupImageUpload('logoInput', 'logo_url', 'logoPreview');
        }

        function setupImageUpload(inputId, hiddenId, previewId) {
            const input = document.getElementById(inputId);
            const hiddenField = document.getElementById(hiddenId);
            
            input.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    // Validar archivo
                    const maxSize = <?= $config['max_file_size'] ?> * 1024 * 1024; // MB to bytes
                    if (file.size > maxSize) {
                        showMessage(`El archivo es demasiado grande (máximo <?= $config['max_file_size'] ?>MB)`, 'error');
                        return;
                    }

                    if (!file.type.startsWith('image/')) {
                        showMessage('Solo se permiten archivos de imagen', 'error');
                        return;
                    }

                    // Subir archivo
                    uploadImage(file, hiddenId, previewId);
                }
            });

            // Drag and drop
            const uploadDiv = input.parentElement;
            
            uploadDiv.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('dragover');
            });

            uploadDiv.addEventListener('dragleave', function(e) {
                e.preventDefault();
                this.classList.remove('dragover');
            });

            uploadDiv.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('dragover');
                
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    input.files = files;
                    input.dispatchEvent(new Event('change'));
                }
            });
        }

        // Subir imagen al servidor
        async function uploadImage(file, hiddenFieldId, previewId) {
            try {
                const formData = new FormData();
                formData.append('action', 'upload_config_image');
                formData.append('image', file);
                formData.append('type', hiddenFieldId.includes('logo') ? 'logo' : 'background');

                const response = await fetch(`${APP_URL}/admin/api`, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.error || 'Error al subir imagen');
                }

                // Actualizar campo oculto
                document.getElementById(hiddenFieldId).value = data.url;

                // Mostrar preview
                let preview = document.getElementById(previewId);
                if (!preview) {
                    preview = document.createElement('img');
                    preview.id = previewId;
                    preview.className = 'image-preview';
                    document.getElementById(hiddenFieldId).parentElement.appendChild(preview);
                }
                preview.src = data.url;

                showMessage('Imagen subida correctamente', 'success');

            } catch (error) {
                console.error('Error al subir imagen:', error);
                showMessage(`Error al subir imagen: ${error.message}`, 'error');
            }
        }

        // Configurar manejadores de formulario
        function initializeFormHandlers() {
            document.getElementById('configForm').addEventListener('submit', saveConfiguration);
        }

        // Guardar configuración
        async function saveConfiguration(e) {
            e.preventDefault();

            if (isLoading) return;

            try {
                isLoading = true;
                const saveBtn = document.getElementById('saveBtn');
                const spinner = document.getElementById('loadingSpinner');
                
                saveBtn.disabled = true;
                spinner.style.display = 'inline-block';

                const formData = new FormData(e.target);
                formData.append('action', 'save_config');

                const response = await fetch(`${APP_URL}/admin/api`, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.error || 'Error al guardar configuración');
                }

                showMessage('Configuración guardada correctamente. Los cambios se aplicarán en el próximo inicio de sesión.', 'success');

                // Actualizar el título de la página si cambió el nombre
                const newTitle = document.getElementById('company_name').value;
                document.title = `Configuración - ${newTitle}`;

                // Preguntar si desea recargar la página para aplicar cambios
                setTimeout(async () => {
                    const confirmed = await showConfirmModal({
                        title: '¡Configuración guardada!',
                        message: '¿Desea recargar la página para ver los cambios aplicados?',
                        details: 'Los cambios se aplicarán completamente al recargar la página.',
                        icon: '🔄',
                        confirmText: 'Recargar página',
                        cancelText: 'Continuar sin recargar'
                    });

                    if (confirmed) {
                        window.location.reload();
                    }
                }, 2000);

            } catch (error) {
                console.error('Error al guardar configuración:', error);
                showMessage(`Error: ${error.message}`, 'error');
            } finally {
                isLoading = false;
                document.getElementById('saveBtn').disabled = false;
                document.getElementById('loadingSpinner').style.display = 'none';
            }
        }

// Usar el sistema de notificaciones de UIComponents (igual que admin.php)
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
        // Google Translate
        function initializeGoogleTranslate() {
            function googleTranslateElementInit() {
                new google.translate.TranslateElement({
                    pageLanguage: '<?= $config['default_language'] ?>',
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
                const saved = sessionStorage.getItem('language') || localStorage.getItem('preferredLanguage') || '<?= $config['default_language'] ?>';
                if (saved && saved !== '<?= $config['default_language'] ?>') {
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

        // Event listeners responsive
        document.addEventListener('DOMContentLoaded', function() {
            window.addEventListener('resize', function() {
                if (window.innerWidth <= 768 && sidebarOpen) {
                    document.getElementById('mainContent').classList.remove('sidebar-open');
                } else if (window.innerWidth > 768 && sidebarOpen) {
                    document.getElementById('mainContent').classList.add('sidebar-open');
                }
            });
        });
    </script>
</body>
</html>