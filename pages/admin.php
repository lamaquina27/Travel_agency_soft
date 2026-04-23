<?php
// =====================================
// ARCHIVO: pages/admin.php - Panel de Usuarios con Componentes UI Mejorados
// =====================================

App::requireRole('admin');

// Incluir ConfigManager y componentes UI
require_once 'config/config_functions.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/ui_components.php';

$user = App::getUser(); 

// Obtener configuración de colores para admin
ConfigManager::init();
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
    <title>Gestión de Usuarios - <?= htmlspecialchars($companyName) ?></title>
    
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

        /* ===== ESTILOS PARA GOOGLE TRANSLATE ===== */
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

        /* Ayuda de contraseña */
        .password-help {
            margin-top: 8px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 3px solid #e2e8f0;
        }

        .password-requirement {
            font-size: 12px;
            margin: 3px 0;
            transition: all 0.3s ease;
        }

        .password-requirement.valid {
            color: #059669;
        }

        .password-requirement.invalid {
            color: #dc2626;
        }

        .password-requirement.valid::before {
            content: "✓";
            margin-right: 5px;
        }

        .password-requirement.invalid::before {
            content: "✗";
            margin-right: 5px;
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

        /* Management Section más compacta */
        .management-section {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e2e8f0;
        }

        .section-title {
            font-size: 20px;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            margin: 0;
        }

        .add-btn {
            background: var(--admin-primary);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .add-btn:hover {
            background: var(--admin-secondary);
            transform: translateY(-1px);
        }

        /* Tabla empresarial minimalista */
        .table-container {
            overflow-x: auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
            font-size: 13px;
        }

        .users-table th {
            background: #f8fafc;
            padding: 8px 12px;
            text-align: left;
            font-weight: 500;
            color: #64748b;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e2e8f0;
        }

        .users-table td {
            padding: 8px 12px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .users-table tr:hover td {
            background-color: #f8fafc;
        }

        .users-table tr:last-child td {
            border-bottom: none;
        }

        .table-user-avatar {
            width: 24px;
            height: 24px;
            border-radius: 4px;
            background: var(--admin-primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
            font-size: 9px;
            margin-right: 8px;
            flex-shrink: 0;
        }

        .user-info {
            display: flex;
            align-items: center;
        }

        .user-details h4 {
            color: #1e293b;
            margin: 0;
            font-weight: 500;
            font-size: 13px;
            line-height: 1.2;
        }

        .user-details p {
            color: #64748b;
            font-size: 11px;
            margin: 1px 0 0 0;
            line-height: 1.2;
        }

        .role-badge, .status-badge {
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .role-admin {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .role-agent {
            background: #f0f9ff;
            color: #0369a1;
            border: 1px solid #bae6fd;
        }

        .status-active {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .status-inactive {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .action-buttons {
            display: flex;
            gap: 4px;
        }

        .action-btn {
            padding: 3px 6px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 9px;
            font-weight: 500;
            transition: all 0.15s ease;
            line-height: 1;
        }

        .btn-edit {
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #dbeafe;
        }

        .btn-edit:hover {
            background: #dbeafe;
        }

        .btn-toggle {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .btn-toggle:hover {
            background: #dcfce7;
        }

        .btn-toggle.inactive {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .btn-toggle.inactive:hover {
            background: #fee2e2;
        }

        /* Modal mejorado - MÁS GRANDE Y ESTÉTICO */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 10000;
            overflow-y: auto;
            backdrop-filter: blur(10px);
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            animation: modalFadeIn 0.3s ease;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(255, 255, 255, 0.9) 100%);
            border-radius: 25px;
            padding: 40px;
            max-width: 800px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from { 
                opacity: 0; 
                transform: translateY(-50px) scale(0.9);
            }
            to { 
                opacity: 1; 
                transform: translateY(0) scale(1);
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 35px;
            padding-bottom: 20px;
            border-bottom: 3px solid var(--admin-primary);
        }

        .modal-title {
            font-size: 32px;
            color: #2d3748;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .close-btn {
            background: rgba(255, 255, 255, 0.8);
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: #718096;
            padding: 10px;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .close-btn:hover {
            background: var(--admin-primary);
            color: white;
            transform: rotate(90deg);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 35px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .form-group label {
            font-weight: 600;
            color: #2d3748;
            font-size: 16px;
        }

        /* Contenedor de contraseña con botón */
        .password-input-container {
            position: relative;
            display: flex;
            align-items: center;
        }

        .password-input-container input {
            padding-right: 50px !important;
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            padding: 5px;
            border-radius: 4px;
            transition: all 0.3s ease;
            z-index: 10;
        }

        .password-toggle:hover {
            background: rgba(0, 0, 0, 0.1);
            transform: scale(1.1);
        }

        .password-toggle:active {
            transform: scale(0.95);
        }

        .form-group input,
        .form-group select {
            padding: 16px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--admin-primary);
            box-shadow: 0 0 0 4px rgba(229, 62, 62, 0.1);
            background: rgba(255, 255, 255, 0.95);
        }

        .form-actions {
            display: flex;
            gap: 20px;
            justify-content: flex-end;
            padding-top: 20px;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e0 100%);
            color: #4a5568;
            border: none;
            padding: 16px 32px;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .btn-primary {
            background: var(--admin-gradient);
            color: white;
            border: none;
            padding: 16px 32px;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(229, 62, 62, 0.3);
        }

        /* Loading y mensajes */
        .loading {
            display: none;
            text-align: center;
            padding: 60px;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid rgba(0, 0, 0, 0.1);
            border-top: 5px solid var(--admin-primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .error-message, .success-message {
            padding: 20px;
            border-radius: 15px;
            margin: 20px 0;
            font-weight: 500;
        }

        .error-message {
            background: linear-gradient(135deg, #fed7d7 0%, #feb2b2 100%);
            color: var(--admin-primary);
            border: 2px solid #feb2b2;
        }

        .success-message {
            background: linear-gradient(135deg, #c6f6d5 0%, #9ae6b4 100%);
            color: #2f855a;
            border: 2px solid #9ae6b4;
        }

        /* Toast mejorado */
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

        /* Nav link mejorado */
        .nav-link {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 12px 25px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
            color: white;
            text-decoration: none;
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

        /* Responsive empresarial */
        @media (max-width: 768px) {
            .header {
                padding: 15px 20px;
            }

            .main-content {
                padding: 15px;
            }

            .main-content.sidebar-open {
                margin-left: 0;
            }

            .management-section {
                padding: 15px;
            }

            .section-header {
                flex-direction: column;
                gap: 10px;
                align-items: stretch;
                text-align: center;
            }

            .users-table {
                font-size: 11px;
            }

            .users-table th,
            .users-table td {
                padding: 6px 8px;
            }

            .table-user-avatar {
                width: 20px;
                height: 20px;
                font-size: 8px;
                margin-right: 6px;
            }

            .user-details h4 {
                font-size: 11px;
            }

            .user-details p {
                font-size: 9px;
            }

            .modal-content {
                margin: 10px;
                padding: 20px;
                max-width: 95%;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .action-buttons {
                flex-direction: column;
                gap: 2px;
            }

            .action-btn {
                padding: 4px 6px;
                font-size: 8px;
            }

            .modal-title {
                font-size: 20px;
            }

            .form-actions {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Header con componentes -->
    <?= UIComponents::renderHeader($user) ?>

    <!-- Sidebar con componentes -->
    <?= UIComponents::renderSidebar($user, '/administrador') ?>

    <!-- Overlay -->
    <div class="overlay" id="overlay" onclick="closeSidebar()"></div>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- User Management mejorado -->
        <div class="management-section">
            <div class="section-header">
                <h2 class="section-title">
                    <span>👥</span>
                    Gestión de Usuarios
                </h2>
                <button class="add-btn" onclick="openUserModal('create')">
                    <span>➕</span>
                    Nuevo Usuario
                </button>
            </div>

            <div class="loading" id="usersLoading">
                <div class="spinner"></div>
                <p>Cargando usuarios...</p>
            </div>

            <div id="usersError" class="error-message" style="display: none;"></div>

            <div class="table-container">
                <table class="users-table" id="usersTable" style="display: none;">
                    <thead>
                        <tr>
                            <th>👤 Usuario</th>
                            <th>✉️ Email</th>
                            <th>🎭 Rol</th>
                            <th>📊 Estado</th>
                            <th>📅 Fecha Creación</th>
                            <th>🕒 Último Acceso</th>
                            <th>⚡ Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody">
                        <!-- Los usuarios se cargan dinámicamente -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal mejorado y más grande -->
    <div class="modal" id="userModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="userModalTitle">
                    <span>👤</span>
                    Nuevo Usuario
                </h2>
                <button class="close-btn" onclick="closeUserModal()">&times;</button>
            </div>

            <form id="userForm">
                <input type="hidden" id="userId">

                <div class="form-grid">
                    <div class="form-group">
                        <label for="username">👤 Nombre de Usuario *</label>
                        <input type="text" id="username" name="username" required placeholder="usuario123" maxlength="50">
                    </div>

                    <div class="form-group">
                        <label for="email">✉️ Correo Electrónico *</label>
                        <input type="email" id="email" name="email" required placeholder="usuario@ejemplo.com" maxlength="100">
                    </div>

                    <div class="form-group">
                        <label for="full_name">🏷️ Nombre Completo *</label>
                        <input type="text" id="full_name" name="full_name" required placeholder="Juan Pérez García" maxlength="100">
                    </div>

                    <div class="form-group">
                        <label for="role">🎭 Rol del Usuario *</label>
                        <select id="role" name="role" required>
                            <option value="">Seleccionar rol</option>
                            <option value="agent">✈️ Agente de Viajes</option>
                            <option value="admin">👑 Administrador</option>
                        </select>
                    </div>

                    <div class="form-group" id="passwordGroup">
                        <label for="password">🔒 Contraseña *</label>
                        <div class="password-input-container">
                            <input type="password" id="password" name="password" placeholder="8+ caracteres, mayúscula, minúscula, número y carácter especial" minlength="8">
                            <button type="button" class="password-toggle" id="passwordToggle" onclick="togglePassword()">
                                👁️
                            </button>
                        </div>
                        <div id="passwordHelp" class="password-help">
                                <div class="password-requirement" id="req-length">✗ Mínimo 8 caracteres</div>
                                <div class="password-requirement" id="req-upper">✗ Una letra mayúscula (A-Z)</div>
                                <div class="password-requirement" id="req-lower">✗ Una letra minúscula (a-z)</div>
                                <div class="password-requirement" id="req-number">✗ Un número (0-9)</div>
                                <div class="password-requirement" id="req-special">✗ Un carácter especial (!@#$%^&*)</div>
                            </div>
                    </div>

                    <div class="form-group">
                        <label for="active">📊 Estado del Usuario</label>
                        <select id="active" name="active">
                            <option value="1">✅ Activo</option>
                            <option value="0">❌ Inactivo</option>
                        </select>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="closeUserModal()">
                        ❌ Cancelar
                    </button>
                    <button type="submit" class="btn-primary" id="submitBtn">
                        💾 Guardar Usuario
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts mejorados -->
    <!-- Scripts corregidos - SIN DUPLICACIÓN -->
    <script>
        const APP_URL = '<?= APP_URL ?>';
        let users = [];
        let isLoading = false;
        let sidebarOpen = false;

        // ===== FUNCIÓN ÚNICA DE GOOGLE TRANSLATE =====
        function googleTranslateElementInit() {
            new google.translate.TranslateElement({
                pageLanguage: '<?= $defaultLanguage ?>',
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
                         '<?= $defaultLanguage ?>';
            
            if (saved && saved !== '<?= $defaultLanguage ?>') {
                const select = document.querySelector('.goog-te-combo');
                if (select) {
                    select.value = saved;
                    select.dispatchEvent(new Event('change'));
                }
            }
        }

        // Escuchar cambios de idioma
        setTimeout(function() {
            const select = document.querySelector('.goog-te-combo');
            if (select) {
                select.addEventListener('change', function() {
                    if (this.value) saveLanguage(this.value);
                });
            }
        }, 2000);

        // ===== INICIALIZACIÓN PRINCIPAL =====
        document.addEventListener('DOMContentLoaded', function() {
            loadStatistics();
            loadUsers();
            // NO llamar initializeGoogleTranslate() aquí - se llama automáticamente
        });

        // Validación de contraseña en tiempo real
        document.getElementById('password').addEventListener('input', function(e) {
            validatePasswordRealTime(e.target.value);
        });

        function validatePasswordRealTime(password) {
            const requirements = {
                'req-length': password.length >= 8,
                'req-upper': /[A-Z]/.test(password),
                'req-lower': /[a-z]/.test(password),
                'req-number': /[0-9]/.test(password),
                'req-special': /[!@#$%^&*(),.?":{}|<>]/.test(password)
            };
            
            Object.keys(requirements).forEach(reqId => {
                const element = document.getElementById(reqId);
                if (element) {
                    element.className = requirements[reqId] ? 'password-requirement valid' : 'password-requirement invalid';
                }
            });
        }

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

        // Funciones de API
        async function apiRequest(endpoint, options = {}) {
            try {
                const response = await fetch(`${APP_URL}${endpoint}`, {
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    ...options
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.error || 'Error en la respuesta del servidor');
                }

                return data;
            } catch (error) {
                console.error('API Error:', error);
                throw error;
            }
        }

        // Cargar estadísticas con animación
        async function loadStatistics() {
            try {
                const response = await apiRequest('/admin/api?action=statistics');
                const stats = response.data;

                // Animar contadores
                animateCounter('totalUsers', stats.totalUsers);
                animateCounter('totalPrograms', stats.totalPrograms);
                animateCounter('totalResources', stats.totalResources);
                animateCounter('activeSessions', stats.activeSessions);

                // Ocultar loading
                document.querySelectorAll('.stat-loading').forEach(el => el.style.display = 'none');
            } catch (error) {
                console.error('Error al cargar estadísticas:', error);
                
                // Mostrar valores por defecto en caso de error
                const totalUsersEl = document.getElementById('totalUsers');
                const totalProgramsEl = document.getElementById('totalPrograms');
                const totalResourcesEl = document.getElementById('totalResources');
                const activeSessionsEl = document.getElementById('activeSessions');

                if (totalUsersEl) totalUsersEl.textContent = '0';
                if (totalProgramsEl) totalProgramsEl.textContent = '0';
                if (totalResourcesEl) totalResourcesEl.textContent = '0';
                if (activeSessionsEl) activeSessionsEl.textContent = '0';
                
                document.querySelectorAll('.stat-loading').forEach(el => el.style.display = 'none');
            }
        }

        // Animación de contadores
        function animateCounter(elementId, targetValue) {
            const element = document.getElementById(elementId);
            if (!element) return;
            
            const startValue = 0;
            const duration = 1000;
            const startTime = performance.now();

            function updateCounter(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                
                const currentValue = Math.floor(startValue + (targetValue - startValue) * progress);
                element.textContent = currentValue;

                if (progress < 1) {
                    requestAnimationFrame(updateCounter);
                }
            }

            requestAnimationFrame(updateCounter);
        }

        // Cargar usuarios
        async function loadUsers() {
            const loading = document.getElementById('usersLoading');
            const table = document.getElementById('usersTable');
            const errorDiv = document.getElementById('usersError');
            
            loading.style.display = 'block';
            table.style.display = 'none';
            errorDiv.style.display = 'none';
            
            try {
                const response = await apiRequest('/admin/api?action=users');
                users = response.data;
                renderUsers();
                
                loading.style.display = 'none';
                table.style.display = 'table';
            } catch (error) {
                console.error('Error al cargar usuarios:', error);
                
                loading.style.display = 'none';
                errorDiv.textContent = `Error al cargar usuarios: ${error.message}`;
                errorDiv.style.display = 'block';
            }
        }

        // Renderizar usuarios en tabla
        function renderUsers() {
            const tbody = document.getElementById('usersTableBody');
            tbody.innerHTML = users.map(user => createUserRow(user)).join('');
        }

        // Crear fila de usuario mejorada
        function createUserRow(user) {
            const roleClass = user.role === 'admin' ? 'role-admin' : 'role-agent';
            const roleText = user.role === 'admin' ? '👑 Administrador' : '✈️ Agente';
            const statusClass = user.active ? 'status-active' : 'status-inactive';
            const statusText = user.active ? '✅ Activo' : '❌ Inactivo';
            const initials = user.full_name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
            const lastLogin = user.last_login_formatted || 'Nunca';
            const createdAt = user.created_at_formatted || 'No disponible';

            // Lógica dinámica para botones según el estado del usuario
            let actionButtons = `
                <button class="action-btn btn-edit" onclick="editUser(${user.id})" title="Editar usuario">
                    ✏️ Editar
                </button>
            `;

            // Solo mostrar botones de estado si no es el admin principal
            if (user.id !== 1) {
                if (user.active) {
                    actionButtons += `
                        <button class="action-btn btn-toggle" onclick="toggleUserStatus(${user.id})" title="Desactivar usuario">
                            ⏸️ Desactivar
                        </button>
                    `;
                } else {
                    actionButtons += `
                        <button class="action-btn btn-toggle inactive" onclick="toggleUserStatus(${user.id})" title="Activar usuario">
                            ▶️ Activar
                        </button>
                    `;
                }
            } else {
                actionButtons += `
                    <button class="action-btn btn-toggle" style="opacity: 0.5; cursor: not-allowed;" title="No se puede desactivar el administrador principal">
                        🔒 Protegido
                    </button>
                `;
            }

            return `
                <tr>
                    <td>
                        <div class="user-info">
                            <div class="table-user-avatar">${initials}</div>
                            <div class="user-details">
                                <h4>${escapeHtml(user.full_name)}</h4>
                                <p>@${escapeHtml(user.username)}</p>
                            </div>
                        </div>
                    </td>
                    <td>${escapeHtml(user.email)}</td>
                    <td><span class="role-badge ${roleClass}">${roleText}</span></td>
                    <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                    <td>📅 ${createdAt}</td>
                    <td>🕒 ${lastLogin}</td>
                    <td>
                        <div class="action-buttons">
                            ${actionButtons}
                        </div>
                    </td>
                </tr>
            `;
        }

        // Funciones del modal de usuario
        function openUserModal(mode, id = null) {
            const modal = document.getElementById('userModal');
            const title = document.getElementById('userModalTitle');
            const passwordGroup = document.getElementById('passwordGroup');
            const passwordField = document.getElementById('password');

            if (mode === 'create') {
                title.innerHTML = '<span>👤</span> Nuevo Usuario';
                document.getElementById('userForm').reset();
                document.getElementById('userId').value = '';
                passwordField.required = true;
                passwordGroup.style.display = 'block';
                passwordGroup.querySelector('label').innerHTML = '🔒 Contraseña *';
            } else if (mode === 'edit' && id) {
                title.innerHTML = '<span>✏️</span> Editar Usuario';
                loadUserData(id);
                passwordField.required = false;
                passwordGroup.style.display = 'block';
                passwordGroup.querySelector('label').innerHTML = '🔒 Nueva Contraseña (opcional)';
            }

            modal.classList.add('show');
        }

        function closeUserModal() {
            document.getElementById('userModal').classList.remove('show');
        }

        function loadUserData(id) {
            const user = users.find(u => u.id == id);
            if (user) {
                console.log('Cargando usuario:', user);
                
                document.getElementById('userId').value = user.id;
                document.getElementById('username').value = user.username || '';
                document.getElementById('email').value = user.email || '';
                document.getElementById('full_name').value = user.full_name || '';
                document.getElementById('role').value = user.role || '';
                document.getElementById('active').value = user.active ? '1' : '0';
                document.getElementById('password').value = '';
            } else {
                console.error('Usuario no encontrado:', id);
                showToast('Usuario no encontrado', 'error');
            }
        }

        function editUser(id) {
            console.log('Editando usuario ID:', id, typeof id);
            openUserModal('edit', id);
        }

        // Submit del formulario de usuario
        document.getElementById('userForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            if (isLoading) return;

            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.innerHTML;
            
            try {
                isLoading = true;
                submitBtn.innerHTML = '⏳ Guardando...';
                submitBtn.disabled = true;

                const formData = new FormData(this);
                const id = document.getElementById('userId').value;

                if (id) {
                    formData.append('action', 'update_user');
                    formData.append('id', id);
                } else {
                    formData.append('action', 'create_user');
                }

                console.log('Enviando datos:', Object.fromEntries(formData.entries()));

                const response = await fetch(`${APP_URL}/admin/api`, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.error || 'Error al guardar usuario');
                }

                showToast(data.message, 'success');
                closeUserModal();
                await loadUsers();
                await loadStatistics();

            } catch (error) {
                console.error('Error al guardar usuario:', error);
                showToast(`Error al guardar usuario: ${error.message}`, 'error');
            } finally {
                isLoading = false;
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        });

        // Toggle status del usuario
async function toggleUserStatus(id) {
    // Esperar un poco para que se cargue showConfirmModal
    await new Promise(resolve => setTimeout(resolve, 100));
    
    try {
        const user = users.find(u => u.id === id);
        const action = user.active ? 'desactivar' : 'activar';
        const confirmed = await showConfirmModal({
            title: `${action.charAt(0).toUpperCase() + action.slice(1)} usuario`,
            message: `¿Estás seguro de que quieres ${action} al usuario "${user.username}"?`,
            details: user.active ? 
                'El usuario no podrá acceder al sistema hasta que lo reactives.' : 
                'El usuario podrá acceder nuevamente al sistema.',
            icon: user.active ? '⏸️' : '▶️',
            confirmText: action.charAt(0).toUpperCase() + action.slice(1),
            cancelText: 'Cancelar',
            confirmButtonStyle: 'danger'
        });

        if (!confirmed) {
            return;
        }

        // Proceder con la acción
        console.log('Enviando toggle para usuario:', user.username, 'Estado actual:', user.active);
        
        const formData = new FormData();
        formData.append('action', 'toggle_user');
        formData.append('id', id);

        console.log('FormData enviada:', Object.fromEntries(formData.entries()));

        const response = await fetch(`${APP_URL}/admin/api`, {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        console.log('Respuesta de la API:', data);

        if (!data.success) {
            throw new Error(data.error || 'Error al cambiar estado del usuario');
        }

        showToast(data.message, 'success');
        await loadUsers();
        await loadStatistics();

    } catch (error) {
        console.error('Error al cambiar estado:', error);
        showToast(`Error: ${error.message}`, 'error');
    }
}

        // Escape HTML para prevenir XSS
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }

        // Mostrar notificaciones toast mejoradas
        function showToast(message, type = 'info') {
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

        // Función para mostrar/ocultar contraseña
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleButton = document.getElementById('passwordToggle');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleButton.innerHTML = '🙈';
                toggleButton.title = 'Ocultar contraseña';
            } else {
                passwordField.type = 'password';
                toggleButton.innerHTML = '👁️';
                toggleButton.title = 'Mostrar contraseña';
            }
        }
        
        // Cerrar modal al hacer clic fuera
	    /*
        document.getElementById('userModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeUserModal();
            }
        });
	    */

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

        // Actualizar estadísticas cada 5 minutos
        setInterval(function() {
            loadStatistics();
        }, 300000);
    </script>

    <!-- Google Translate Script - UNA SOLA VEZ -->
    <script src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
</body>
</html>