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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --admin-primary:
                <?= $adminColors['primary'] ?>
            ;
            --admin-secondary:
                <?= $adminColors['secondary'] ?>
            ;
            --admin-gradient: linear-gradient(135deg, var(--admin-primary) 0%, var(--admin-secondary) 100%);
            --primary-color: var(--admin-primary);
            --secondary-color: var(--admin-secondary);
            --primary-gradient: var(--admin-gradient);
            --surface: #ffffff;
            --surface-soft: #f8fafc;
            --surface-muted: #f1f5f9;
            --border: #e5e7eb;
            --border-soft: #eef2f7;
            --text: #0f172a;
            --text-soft: #475569;
            --text-muted: #64748b;
            --danger: #dc2626;
            --success: var(--admin-primary);
            --shadow-soft: 0 18px 45px rgba(15, 23, 42, 0.08);
            --shadow-card: 0 10px 30px rgba(15, 23, 42, 0.06);
        }

        body {
            font-family: Inter, 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background:
                radial-gradient(circle at top left, color-mix(in srgb, var(--admin-primary) 8%, transparent), transparent 28%),
                linear-gradient(180deg, #f8fafc 0%, #ffffff 48%, #f8fafc 100%);
            color: var(--text);
            min-height: 100vh;
        }

        .header {
            background: rgba(255, 255, 255, 0.92) !important;
            color: var(--text) !important;
            padding: 14px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 0 rgba(226, 232, 240, 0.9), 0 12px 32px rgba(15, 23, 42, 0.06);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1001;
            backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(226, 232, 240, 0.85);
        }

        .header-left,
        .header-right {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .menu-toggle,
        .back-btn,
        .nav-link {
            border: 1px solid color-mix(in srgb, var(--admin-primary) 16%, #e5e7eb) !important;
            background: color-mix(in srgb, var(--admin-primary) 8%, #ffffff) !important;
            color: var(--admin-primary) !important;
            border-radius: 14px !important;
            transition: all .2s ease !important;
            box-shadow: none !important;
            text-decoration: none !important;
        }

        .menu-toggle {
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            padding: 0;
        }

        .menu-toggle:hover,
        .back-btn:hover,
        .nav-link:hover {
            transform: translateY(-1px) !important;
            background: color-mix(in srgb, var(--admin-primary) 12%, #ffffff) !important;
            color: var(--admin-primary) !important;
        }

        #google_translate_element {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 7px 10px;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05);
            transition: all 0.2s ease;
        }

        #google_translate_element:hover {
            border-color: color-mix(in srgb, var(--admin-primary) 22%, #e5e7eb);
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
        }

        .goog-te-gadget-icon,
        .VIpgJd-ZVi9od-xl07Ob-lTBxed img,
        .VIpgJd-ZVi9od-xl07Ob-lTBxed span[style*="border-left"],
        .goog-te-banner-frame.skiptranslate,
        .VIpgJd-ZVi9od-ORHb-OEVmcd,
        .goog-te-gadget img {
            display: none !important;
        }

        .goog-te-gadget-simple {
            background: transparent !important;
            border: none !important;
            font-family: inherit !important;
        }

        .VIpgJd-ZVi9od-xl07Ob-lTBxed {
            color: var(--text-soft) !important;
            text-decoration: none !important;
            font-family: inherit !important;
            font-size: 12px !important;
            font-weight: 700 !important;
            display: flex !important;
            align-items: center !important;
            gap: 6px !important;
        }

        body {
            top: 0px !important;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            padding: 8px 14px;
            border-radius: 16px;
            transition: all 0.2s ease;
            background: #ffffff;
            border: 1px solid var(--border);
            color: var(--text);
        }

        .user-info:hover {
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        }

        .user-avatar {
            width: 38px;
            height: 38px;
            background: var(--admin-gradient);
            color: #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            border: 3px solid color-mix(in srgb, var(--admin-primary) 14%, #ffffff);
            box-shadow: 0 10px 20px color-mix(in srgb, var(--admin-primary) 22%, transparent);
        }

        .main-content {
            margin-left: 0;
            margin-top: 70px;
            padding: 34px 38px;
            transition: margin-left 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            min-height: calc(100vh - 70px);
        }

        .main-content.sidebar-open {
            margin-left: 320px;
        }

        .management-section {
            background: rgba(255, 255, 255, 0.94);
            border-radius: 28px;
            padding: 28px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-soft);
            border: 1px solid rgba(226, 232, 240, 0.9);
            position: relative;
            overflow: hidden;
        }

        .management-section::before {
            content: '';
            position: absolute;
            inset: 0 0 auto 0;
            height: 4px;
            background: var(--admin-gradient);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 24px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-soft);
        }

        .section-title-wrap {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .section-icon,
        .modal-title-icon {
            width: 44px;
            height: 44px;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: color-mix(in srgb, var(--admin-primary) 10%, #ffffff);
            color: var(--admin-primary);
            border: 1px solid color-mix(in srgb, var(--admin-primary) 14%, #e5e7eb);
            flex-shrink: 0;
        }

        .section-icon svg,
        .modal-title-icon svg,
        .btn-icon svg,
        .action-btn svg,
        .password-toggle svg {
            width: 19px;
            height: 19px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2.1;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .section-title {
            font-size: 24px;
            color: var(--text);
            font-weight: 800;
            margin: 0;
            letter-spacing: -0.03em;
            line-height: 1.1;
        }

        .section-subtitle {
            color: var(--text-muted);
            font-size: 13px;
            margin-top: 5px;
            font-weight: 500;
        }

        .add-btn,
        .btn-primary,
        .btn-secondary,
        .action-btn {
            border: none;
            cursor: pointer;
            font-weight: 700;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            white-space: nowrap;
        }

        .add-btn {
            background: var(--admin-gradient);
            color: #ffffff;
            padding: 12px 18px;
            border-radius: 16px;
            font-size: 14px;
            box-shadow: 0 14px 28px color-mix(in srgb, var(--admin-primary) 22%, transparent);
        }

        .add-btn:hover,
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 18px 34px color-mix(in srgb, var(--admin-primary) 26%, transparent);
        }

        .table-container {
            overflow-x: auto;
            background: #ffffff;
            border-radius: 22px;
            box-shadow: var(--shadow-card);
            border: 1px solid var(--border-soft);
        }

        .users-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 0;
            font-size: 13px;
        }

        .users-table th {
            background: #f8fafc;
            padding: 15px 16px;
            text-align: left;
            font-weight: 800;
            color: #64748b;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            border-bottom: 1px solid var(--border-soft);
        }

        .users-table td {
            padding: 16px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
            color: var(--text-soft);
            font-weight: 550;
        }

        .users-table tr:hover td {
            background: #fbfdff;
        }

        .users-table tr:last-child td {
            border-bottom: none;
        }

        .table-user-avatar {
            width: 38px;
            height: 38px;
            border-radius: 14px;
            background: var(--admin-gradient);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 12px;
            margin-right: 12px;
            flex-shrink: 0;
            box-shadow: 0 10px 22px color-mix(in srgb, var(--admin-primary) 18%, transparent);
        }

        .users-table .user-info {
            padding: 0;
            border: 0;
            background: transparent;
            box-shadow: none;
            cursor: default;
        }

        .user-details h4 {
            color: var(--text);
            margin: 0;
            font-weight: 800;
            font-size: 13px;
            line-height: 1.25;
        }

        .user-details p {
            color: var(--text-muted);
            font-size: 11px;
            margin: 3px 0 0 0;
            line-height: 1.2;
            font-weight: 500;
        }

        .role-badge,
        .status-badge {
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .role-admin {
            background: color-mix(in srgb, var(--admin-primary) 10%, #ffffff);
            color: var(--admin-primary);
            border: 1px solid color-mix(in srgb, var(--admin-primary) 18%, #e5e7eb);
        }

        .role-agent,
        .role-operador {
            background: color-mix(in srgb, var(--admin-secondary) 10%, #ffffff);
            color: var(--admin-secondary);
            border: 1px solid color-mix(in srgb, var(--admin-secondary) 18%, #e5e7eb);
        }

        .status-active {
            background: color-mix(in srgb, var(--admin-primary) 10%, #ffffff);
            color: var(--admin-primary);
            border: 1px solid color-mix(in srgb, var(--admin-primary) 18%, #e5e7eb);
        }

        .status-inactive {
            background: #f8fafc;
            color: #64748b;
            border: 1px solid #e5e7eb;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 8px 11px;
            border-radius: 12px;
            font-size: 11px;
            border: 1px solid transparent;
        }

        .btn-edit {
            background: color-mix(in srgb, var(--admin-primary) 8%, #ffffff);
            color: var(--admin-primary);
            border-color: color-mix(in srgb, var(--admin-primary) 14%, #e5e7eb);
        }

        .btn-toggle {
            background: color-mix(in srgb, var(--admin-primary) 8%, #ffffff);
            color: var(--admin-primary);
            border-color: color-mix(in srgb, var(--admin-primary) 14%, #e5e7eb);
        }

        .btn-toggle.inactive {
            background: color-mix(in srgb, var(--admin-secondary) 8%, #ffffff);
            color: var(--admin-secondary);
            border-color: color-mix(in srgb, var(--admin-secondary) 14%, #e5e7eb);
        }

        .action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
        }

        .date-muted {
            color: var(--text-muted);
            font-size: 12px;
        }

        .modal {
            display: none;
            position: fixed;
            inset: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.46);
            z-index: 10000;
            overflow-y: auto;
            backdrop-filter: blur(7px);
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            animation: modalFadeIn 0.22s ease;
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(18px) scale(0.98);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .modal-content {
            background: #ffffff;
            border-radius: 28px;
            padding: 30px;
            max-width: 820px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 28px 70px rgba(15, 23, 42, 0.22);
            border: 1px solid rgba(226, 232, 240, 0.9);
            animation: modalSlideIn 0.22s ease;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
            margin-bottom: 26px;
            padding-bottom: 18px;
            border-bottom: 1px solid var(--border-soft);
        }

        .modal-title {
            font-size: 24px;
            color: var(--text);
            font-weight: 850;
            display: flex;
            align-items: center;
            gap: 13px;
            letter-spacing: -0.03em;
        }

        .close-btn {
            background: #f8fafc;
            border: 1px solid var(--border);
            font-size: 26px;
            cursor: pointer;
            color: var(--text-muted);
            border-radius: 14px;
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            line-height: 1;
        }

        .close-btn:hover {
            background: color-mix(in srgb, var(--admin-primary) 10%, #ffffff);
            color: var(--admin-primary);
            border-color: color-mix(in srgb, var(--admin-primary) 18%, #e5e7eb);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
            margin-bottom: 28px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            font-weight: 750;
            color: var(--text);
            font-size: 13px;
        }

        .password-input-container {
            position: relative;
            display: flex;
            align-items: center;
        }

        .password-input-container input {
            padding-right: 52px !important;
        }

        .password-toggle {
            position: absolute;
            right: 10px;
            background: #f8fafc;
            border: 1px solid var(--border);
            color: var(--text-muted);
            cursor: pointer;
            width: 34px;
            height: 34px;
            border-radius: 11px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            z-index: 10;
        }

        .password-toggle:hover {
            background: color-mix(in srgb, var(--admin-primary) 9%, #ffffff);
            color: var(--admin-primary);
        }

        .form-group input,
        .form-group select {
            padding: 13px 15px;
            border: 1px solid var(--border);
            border-radius: 15px;
            font-size: 14px;
            transition: all 0.2s ease;
            background: #ffffff;
            color: var(--text);
            width: 100%;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: color-mix(in srgb, var(--admin-primary) 55%, #e5e7eb);
            box-shadow: 0 0 0 4px color-mix(in srgb, var(--admin-primary) 10%, transparent);
        }

        .password-help {
            margin-top: 4px;
            padding: 12px;
            background: #f8fafc;
            border-radius: 15px;
            border: 1px solid var(--border-soft);
        }

        .password-requirement {
            font-size: 12px;
            margin: 5px 0;
            color: var(--text-muted);
            transition: all 0.2s ease;
        }

        .password-requirement.valid {
            color: var(--admin-primary);
        }

        .password-requirement.invalid {
            color: #64748b;
        }

        .password-requirement.valid::before {
            content: "✓";
            margin-right: 6px;
        }

        .password-requirement.invalid::before {
            content: "×";
            margin-right: 6px;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            padding-top: 20px;
            border-top: 1px solid var(--border-soft);
        }

        .btn-secondary {
            background: #ffffff;
            color: var(--text-soft);
            border: 1px solid var(--border);
            padding: 13px 20px;
            border-radius: 15px;
            font-size: 14px;
        }

        .btn-secondary:hover {
            background: #f8fafc;
            color: var(--text);
            transform: translateY(-1px);
        }

        .btn-primary {
            background: var(--admin-gradient);
            color: #ffffff;
            padding: 13px 20px;
            border-radius: 15px;
            font-size: 14px;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 52px 20px;
            color: var(--text-muted);
        }

        .spinner {
            width: 42px;
            height: 42px;
            border: 4px solid #eef2f7;
            border-top: 4px solid var(--admin-primary);
            border-radius: 50%;
            animation: spin 0.9s linear infinite;
            margin: 0 auto 14px;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .error-message,
        .success-message {
            padding: 14px 16px;
            border-radius: 16px;
            margin: 16px 0;
            font-weight: 650;
            font-size: 13px;
        }

        .error-message {
            background: #f8fafc;
            color: #64748b;
            border: 1px solid #e5e7eb;
        }

        .success-message {
            background: color-mix(in srgb, var(--admin-primary) 8%, #ffffff);
            color: var(--admin-primary);
            border: 1px solid color-mix(in srgb, var(--admin-primary) 16%, #e5e7eb);
        }

        .toast {
            position: fixed;
            top: 88px;
            right: 22px;
            padding: 14px 16px;
            border-radius: 16px;
            color: #ffffff;
            z-index: 20000;
            transform: translateX(420px);
            transition: transform 0.25s ease;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.18);
            min-width: 280px;
            font-weight: 700;
            font-size: 13px;
        }

        .toast.show {
            transform: translateX(0);
        }

        .toast.success {
            background: var(--admin-gradient);
        }

        .toast.error {
            background: linear-gradient(135deg, var(--admin-primary), var(--admin-secondary));
        }

        .toast.info {
            background: linear-gradient(135deg, var(--admin-primary), var(--admin-secondary));
        }

        .toast-content {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .toast-dot {
            width: 9px;
            height: 9px;
            border-radius: 999px;
            background: currentColor;
            opacity: .85;
            box-shadow: 0 0 0 4px rgba(255, 255, 255, .16);
        }

        .overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.38);
            z-index: 999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.25s ease;
            backdrop-filter: blur(4px);
        }

        .overlay.show {
            opacity: 1;
            visibility: visible;
        }


        /* Ajustes visuales para mantener coherencia con dashboard/sidebar */
        .enhanced-sidebar .menu-item-icon,
        .sidebar .menu-item-icon {
            background: color-mix(in srgb, var(--admin-primary) 10%, #ffffff) !important;
            color: var(--admin-primary) !important;
            box-shadow: 0 10px 22px color-mix(in srgb, var(--admin-primary) 12%, transparent) !important;
        }

        .enhanced-sidebar .menu-item-enhanced.active .menu-item-icon,
        .sidebar .menu-item-enhanced.active .menu-item-icon {
            background: var(--admin-gradient) !important;
            color: #ffffff !important;
        }

        .enhanced-sidebar .menu-item-enhanced.active,
        .sidebar .menu-item-enhanced.active {
            background: color-mix(in srgb, var(--admin-primary) 8%, #ffffff) !important;
            border-color: color-mix(in srgb, var(--admin-primary) 16%, #e5e7eb) !important;
        }

        @media (max-width: 920px) {
            .main-content {
                padding: 24px 18px;
            }

            .main-content.sidebar-open {
                margin-left: 0;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .header {
                padding: 12px 16px;
            }

            .management-section {
                padding: 20px;
                border-radius: 22px;
            }

            .section-header {
                flex-direction: column;
                align-items: stretch;
            }

            .add-btn {
                width: 100%;
            }

            .users-table {
                font-size: 12px;
            }

            .users-table th,
            .users-table td {
                padding: 12px;
            }

            .action-buttons {
                flex-direction: column;
                align-items: stretch;
            }

            .action-btn {
                width: 100%;
            }

            .modal.show {
                padding: 14px;
                align-items: flex-start;
            }

            .modal-content {
                padding: 20px;
                border-radius: 22px;
            }

            .modal-title {
                font-size: 20px;
            }

            .form-actions {
                flex-direction: column-reverse;
            }

            .btn-primary,
            .btn-secondary {
                width: 100%;
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
                <div class="section-title-wrap">
                    <span class="section-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </span>
                    <div>
                        <h2 class="section-title">Gestión de Usuarios</h2>
                        <p class="section-subtitle">Administra cuentas, roles y accesos del sistema</p>
                    </div>
                </div>
                <button class="add-btn" onclick="openUserModal('create')">
                    <span class="btn-icon" aria-hidden="true"><svg viewBox="0 0 24 24">
                            <path d="M12 5v14"></path>
                            <path d="M5 12h14"></path>
                        </svg></span>
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
                            <th>Usuario</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Fecha creación</th>
                            <th>Último acceso</th>
                            <th>Acciones</th>
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
                    <span class="modal-title-icon" aria-hidden="true"><svg viewBox="0 0 24 24">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M19 8v6"></path>
                            <path d="M22 11h-6"></path>
                        </svg></span>
                    Nuevo Usuario
                </h2>
                <button class="close-btn" onclick="closeUserModal()">&times;</button>
            </div>

            <form id="userForm">
                <input type="hidden" id="userId">

                <div class="form-grid">
                    <div class="form-group">
                        <label for="username">Nombre de usuario *</label>
                        <input type="text" id="username" name="username" required placeholder="usuario123"
                            maxlength="50">
                    </div>

                    <div class="form-group">
                        <label for="email">Correo electrónico *</label>
                        <input type="email" id="email" name="email" required placeholder="usuario@ejemplo.com"
                            maxlength="100">
                    </div>

                    <div class="form-group">
                        <label for="full_name">Nombre completo *</label>
                        <input type="text" id="full_name" name="full_name" required placeholder="Juan Pérez García"
                            maxlength="100">
                    </div>

                    <div class="form-group">
                        <label for="role">Rol del Usuario *</label>
                        <select id="role" name="role" required>
                            <option value="">Seleccionar rol</option>
                            <option value="agent">Agente de viajes</option>
                            <option value="admin">Administrador</option>
                            <option value="operador">Operador</option>
                        </select>
                    </div>

                    <div class="form-group" id="passwordGroup">
                        <label for="password">Contraseña *</label>
                        <div class="password-input-container">
                            <input type="password" id="password" name="password"
                                placeholder="8+ caracteres, mayúscula, minúscula, número y carácter especial"
                                minlength="8">
                            <button type="button" class="password-toggle" id="passwordToggle" onclick="togglePassword()"
                                title="Mostrar contraseña">
                                <svg viewBox="0 0 24 24">
                                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </button>
                        </div>
                        <div id="passwordHelp" class="password-help">
                            <div class="password-requirement" id="req-length">Mínimo 8 caracteres</div>
                            <div class="password-requirement" id="req-upper">Una letra mayúscula (A-Z)</div>
                            <div class="password-requirement" id="req-lower">Una letra minúscula (a-z)</div>
                            <div class="password-requirement" id="req-number">Un número (0-9)</div>
                            <div class="password-requirement" id="req-special">Un carácter especial (!@#$%^&*)</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="active">Estado del Usuario</label>
                        <select id="active" name="active">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="closeUserModal()">
                        Cancelar
                    </button>
                    <button type="submit" class="btn-primary" id="submitBtn">
                        Guardar usuario
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
        setTimeout(function () {
            const select = document.querySelector('.goog-te-combo');
            if (select) {
                select.addEventListener('change', function () {
                    if (this.value) saveLanguage(this.value);
                });
            }
        }, 2000);

        // ===== INICIALIZACIÓN PRINCIPAL =====
        document.addEventListener('DOMContentLoaded', function () {
            loadStatistics();
            loadUsers();
            // NO llamar initializeGoogleTranslate() aquí - se llama automáticamente
        });

        // Validación de contraseña en tiempo real
        document.getElementById('password').addEventListener('input', function (e) {
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
            const roleMap = {
                admin: { class: "role-admin", text: "Administrador" },
                operador: { class: "role-operador", text: "Operador" },
                agent: { class: "role-agent", text: "Agente" }
            }
            const { class: roleClass, text: roleText } = roleMap[user.role] || roleMap.agent;
            const statusClass = user.active ? 'status-active' : 'status-inactive';
            const statusText = user.active ? 'Activo' : 'Inactivo';
            const initials = user.full_name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
            const lastLogin = user.last_login_formatted || 'Nunca';
            const createdAt = user.created_at_formatted || 'No disponible';

            // Lógica dinámica para botones según el estado del usuario
            let actionButtons = `
                <button class="action-btn btn-edit" onclick="editUser(${user.id})" title="Editar usuario">
                    <svg viewBox="0 0 24 24"><path d="M12 20h9"></path><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"></path></svg> Editar
                </button>
            `;

            // Solo mostrar botones de estado si no es el admin principal
            if (user.id !== 1) {
                if (user.active) {
                    actionButtons += `
                        <button class="action-btn btn-toggle" onclick="toggleUserStatus(${user.id})" title="Desactivar usuario">
                            <svg viewBox="0 0 24 24"><path d="M10 15V9"></path><path d="M14 15V9"></path><circle cx="12" cy="12" r="10"></circle></svg> Desactivar
                        </button>
                    `;
                } else {
                    actionButtons += `
                        <button class="action-btn btn-toggle inactive" onclick="toggleUserStatus(${user.id})" title="Activar usuario">
                            <svg viewBox="0 0 24 24"><polygon points="10 8 16 12 10 16 10 8"></polygon><circle cx="12" cy="12" r="10"></circle></svg> Activar
                        </button>
                    `;
                }
            } else {
                actionButtons += `
                    <button class="action-btn btn-toggle" style="opacity: 0.55; cursor: not-allowed;" title="No se puede desactivar el administrador principal">
                        <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg> Protegido
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
                    <td><span class="date-muted">${createdAt}</span></td>
                    <td><span class="date-muted">${lastLogin}</span></td>
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
                title.innerHTML = '<span class="modal-title-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M19 8v6"></path><path d="M22 11h-6"></path></svg></span> Nuevo Usuario';
                document.getElementById('userForm').reset();
                document.getElementById('userId').value = '';
                passwordField.required = true;
                passwordGroup.style.display = 'block';
                passwordGroup.querySelector('label').innerHTML = 'Contraseña *';
            } else if (mode === 'edit' && id) {
                title.innerHTML = '<span class="modal-title-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 20h9"></path><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"></path></svg></span> Editar Usuario';
                loadUserData(id);
                passwordField.required = false;
                passwordGroup.style.display = 'block';
                passwordGroup.querySelector('label').innerHTML = 'Nueva contraseña (opcional)';
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
        document.getElementById('userForm').addEventListener('submit', async function (e) {
            e.preventDefault();

            if (isLoading) return;

            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.innerHTML;

            try {
                isLoading = true;
                submitBtn.innerHTML = 'Guardando...';
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
                    icon: '',
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
            return text.replace(/[&<>"']/g, function (m) { return map[m]; });
        }

        // Mostrar notificaciones toast mejoradas
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;

            toast.innerHTML = `
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span class="toast-dot" aria-hidden="true"></span>
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
                toggleButton.innerHTML = '<svg viewBox="0 0 24 24"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20C5.5 20 2 12 2 12a18.45 18.45 0 0 1 5.06-5.94"></path><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c6.5 0 10 8 10 8a18.5 18.5 0 0 1-2.16 3.19"></path><path d="M14.12 14.12A3 3 0 0 1 9.88 9.88"></path><path d="M1 1l22 22"></path></svg>';
                toggleButton.title = 'Ocultar contraseña';
            } else {
                passwordField.type = 'password';
                toggleButton.innerHTML = '<svg viewBox="0 0 24 24"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
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
        document.addEventListener('DOMContentLoaded', function () {
            window.addEventListener('resize', function () {
                if (window.innerWidth <= 768 && sidebarOpen) {
                    document.getElementById('mainContent').classList.remove('sidebar-open');
                } else if (window.innerWidth > 768 && sidebarOpen) {
                    document.getElementById('mainContent').classList.add('sidebar-open');
                }
            });
        });

        // Actualizar estadísticas cada 5 minutos
        setInterval(function () {
            loadStatistics();
        }, 300000);
    </script>

    <!-- Google Translate Script - UNA SOLA VEZ -->
    <script src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
</body>

</html>