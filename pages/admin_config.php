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

// Gmail account del admin actual
$db = Database::getInstance();
$gmailAccount = $db->fetch(
    "SELECT id, email, status FROM email_accounts WHERE user_id = ? AND provider = 'gmail' ORDER BY id DESC LIMIT 1",
    [$user['id']]
);
$flashSuccess = $_SESSION['flash_success'] ?? null; unset($_SESSION['flash_success']);
$flashError   = $_SESSION['flash_error']   ?? null; unset($_SESSION['flash_error']);

// Inicializar ConfigManager
ConfigManager::init();
$config = ConfigManager::get();
$adminColors = ConfigManager::getColorsForRole('admin');
$companyName = ConfigManager::getCompanyName();
$logo = ConfigManager::getLogo();
$defaultLanguage = ConfigManager::getDefaultLanguage();

if (!function_exists('adminConfigIcon')) {
    function adminConfigIcon($name)
    {
        $icons = [
            'eye' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"></path><circle cx="12" cy="12" r="3"></circle></svg>',
            'building' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 21h18"></path><path d="M6 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16"></path><path d="M9 8h1"></path><path d="M14 8h1"></path><path d="M9 12h1"></path><path d="M14 12h1"></path><path d="M10 21v-4h4v4"></path></svg>',
            'palette' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 22a10 10 0 1 1 10-10 4 4 0 0 1-4 4h-1.5a2 2 0 0 0-1.7 3.05A2 2 0 0 1 13.1 22H12z"></path><circle cx="7.5" cy="10.5" r="1"></circle><circle cx="10.5" cy="7.5" r="1"></circle><circle cx="14.5" cy="7.5" r="1"></circle><circle cx="16.5" cy="10.5" r="1"></circle></svg>',
            'settings' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 15.5A3.5 3.5 0 1 0 12 8a3.5 3.5 0 0 0 0 7.5z"></path><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6 1.7 1.7 0 0 0-.4 1.1V21a2 2 0 1 1-4 0v-.09A1.7 1.7 0 0 0 8.6 19.4a1.7 1.7 0 0 0-1.88.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-.6-1 1.7 1.7 0 0 0-1.1-.4H3a2 2 0 1 1 0-4h.09A1.7 1.7 0 0 0 4.6 8.6a1.7 1.7 0 0 0-.34-1.88l-.06-.06A2 2 0 1 1 7.03 3.83l.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-.6 1.7 1.7 0 0 0 .4-1.1V3a2 2 0 1 1 4 0v.09A1.7 1.7 0 0 0 15.4 4.6a1.7 1.7 0 0 0 1.88-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.7 1.7 0 0 0 19.4 9c.2.37.5.7.9.9.3.2.7.3 1.1.3H21a2 2 0 1 1 0 4h-.09A1.7 1.7 0 0 0 19.4 15z"></path></svg>',
            'upload' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><path d="M17 8l-5-5-5 5"></path><path d="M12 3v12"></path></svg>',
            'shield' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>',
            'plane' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 2L11 13"></path><path d="M22 2l-7 20-4-9-9-4 20-7z"></path></svg>',
            'save' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><path d="M17 21v-8H7v8"></path><path d="M7 3v5h8"></path></svg>',
            'clock' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg>',
            'lock' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"></rect><path d="M8 11V7a4 4 0 0 1 8 0v4"></path></svg>',
            'info' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><path d="M12 16v-4"></path><path d="M12 8h.01"></path></svg>',
        ];
        return $icons[$name] ?? $icons['settings'];
    }
}

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
            --shadow-soft: 0 18px 45px rgba(15, 23, 42, 0.08);
            --shadow-card: 0 10px 30px rgba(15, 23, 42, 0.06);
        }

        body {
            font-family: Inter, 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background:
                radial-gradient(circle at top left, color-mix(in srgb, var(--admin-primary) 8%, transparent), transparent 30%),
                linear-gradient(180deg, #f8fafc 0%, #ffffff 48%, #f8fafc 100%);
            color: var(--text);
            min-height: 100vh;
            top: 0 !important;
        }

        .header {
            background: rgba(255, 255, 255, .92) !important;
            color: var(--text) !important;
            padding: 14px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 0 rgba(226, 232, 240, .9), 0 12px 32px rgba(15, 23, 42, .06);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1001;
            backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(226, 232, 240, .85);
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
            box-shadow: 0 10px 24px rgba(15, 23, 42, .08);
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
        }

        #google_translate_element {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 7px 10px;
            box-shadow: 0 8px 18px rgba(15, 23, 42, .05);
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

        .main-content {
            margin-left: 0;
            margin-top: 70px;
            padding: 34px 38px;
            transition: margin-left .35s cubic-bezier(.4, 0, .2, 1);
            min-height: calc(100vh - 70px);
        }

        .main-content.sidebar-open {
            margin-left: 320px;
        }

        .preview-section,
        .config-section {
            background: rgba(255, 255, 255, .94);
            border-radius: 28px;
            padding: 28px;
            margin-bottom: 28px;
            box-shadow: var(--shadow-soft);
            border: 1px solid rgba(226, 232, 240, .9);
            position: relative;
            overflow: hidden;
        }

        .preview-section::before,
        .config-section::before {
            content: '';
            position: absolute;
            inset: 0 0 auto 0;
            height: 4px;
            background: var(--admin-gradient);
            opacity: .95;
        }

        .section-title {
            font-size: 22px;
            color: var(--text);
            margin-bottom: 22px;
            display: flex;
            align-items: center;
            gap: 12px;
            letter-spacing: -.03em;
            font-weight: 800;
        }

        .section-title .section-icon,
        .section-icon {
            width: 40px;
            height: 40px;
            border-radius: 14px;
            background: color-mix(in srgb, var(--admin-primary) 10%, #ffffff);
            color: var(--admin-primary);
            display: inline-grid;
            place-items: center;
            border: 1px solid color-mix(in srgb, var(--admin-primary) 16%, #e5e7eb);
            flex-shrink: 0;
        }

        .section-icon svg,
        .field-icon svg,
        .upload-icon svg,
        .save-btn svg {
            width: 20px;
            height: 20px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 22px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 9px;
        }

        .form-group label {
            font-weight: 700;
            color: var(--text-soft);
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group small,
        .helper-text {
            color: var(--text-muted) !important;
            font-size: 12px !important;
            line-height: 1.45;
        }

        .field-icon {
            width: 22px;
            height: 22px;
            display: inline-grid;
            place-items: center;
            color: var(--admin-primary);
        }

        .field-icon svg {
            width: 16px;
            height: 16px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 13px 15px;
            border: 1px solid var(--border);
            border-radius: 14px;
            font-size: 14px;
            transition: all .2s ease;
            color: var(--text);
            background: #ffffff;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: color-mix(in srgb, var(--admin-primary) 45%, #e5e7eb);
            box-shadow: 0 0 0 4px color-mix(in srgb, var(--admin-primary) 11%, transparent);
        }

        input[readonly] {
            background: #f8fafc !important;
            cursor: not-allowed;
            color: #64748b !important;
        }

        .color-input {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px;
            border: 1px solid var(--border);
            border-radius: 16px;
            background: #fff;
        }

        .color-picker {
            width: 54px;
            height: 42px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            overflow: hidden;
            background: transparent;
        }

        .color-text {
            flex: 1;
            font-family: 'SFMono-Regular', Consolas, monospace;
            text-transform: uppercase;
            background: #f8fafc !important;
        }

        .role-title {
            margin: 26px 0 15px 0;
            color: var(--text);
            font-size: 15px;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .role-title:first-of-type {
            margin-top: 0;
        }

        .image-upload {
            border: 1.5px dashed color-mix(in srgb, var(--admin-primary) 26%, #e2e8f0);
            border-radius: 22px;
            padding: 24px;
            text-align: center;
            cursor: pointer;
            transition: all .2s ease;
            position: relative;
            background: linear-gradient(180deg, #fff, #f8fafc);
        }

        .image-upload:hover,
        .image-upload.dragover {
            border-color: var(--admin-primary);
            background: color-mix(in srgb, var(--admin-primary) 5%, #ffffff);
            transform: translateY(-1px);
        }

        .image-upload input {
            display: none;
        }

        .upload-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            color: var(--text-soft);
        }

        .upload-icon {
            width: 54px;
            height: 54px;
            border-radius: 18px;
            display: grid;
            place-items: center;
            color: #fff;
            background: var(--admin-gradient);
            box-shadow: 0 14px 28px color-mix(in srgb, var(--admin-primary) 22%, transparent);
        }

        .upload-content strong {
            color: var(--text);
            font-size: 15px;
        }

        .image-preview {
            max-width: 100%;
            max-height: 180px;
            border-radius: 16px;
            margin-top: 16px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-card);
            object-fit: contain;
            background: #fff;
        }

        .preview-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }

        .preview-tab {
            padding: 11px 18px;
            border: 1px solid var(--border);
            border-radius: 999px;
            cursor: pointer;
            transition: all .2s ease;
            background: #fff;
            color: var(--text-soft);
            font-weight: 700;
            font-size: 13px;
        }

        .preview-tab:hover {
            border-color: color-mix(in srgb, var(--admin-primary) 26%, #e5e7eb);
            color: var(--admin-primary);
        }

        .preview-tab.active {
            border-color: color-mix(in srgb, var(--admin-primary) 22%, transparent);
            background: color-mix(in srgb, var(--admin-primary) 11%, #ffffff);
            color: var(--admin-primary);
            box-shadow: 0 12px 24px rgba(15, 23, 42, .06);
        }

        .preview-header {
            padding: 26px 30px;
            border-radius: 24px;
            color: white;
            margin-bottom: 4px;
            transition: all .2s ease;
            box-shadow: 0 16px 36px rgba(15, 23, 42, .12);
        }

        .preview-company {
            font-size: 26px;
            font-weight: 850;
            letter-spacing: -.04em;
        }

        .preview-tagline {
            opacity: .9;
            margin-top: 6px;
            font-size: 14px;
        }

        .advanced-content {
            display: none;
            margin-top: 20px;
        }

        .advanced-content.show {
            display: block;
        }

        .save-section {
            text-align: center;
            margin: 34px 0 8px;
        }

        .save-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: var(--admin-gradient);
            color: white;
            border: none;
            padding: 15px 30px;
            min-width: 245px;
            border-radius: 18px;
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
            transition: all .2s ease;
            box-shadow: 0 16px 30px color-mix(in srgb, var(--admin-primary) 26%, transparent);
        }

        .save-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 36px color-mix(in srgb, var(--admin-primary) 32%, transparent);
        }

        .save-btn:disabled {
            opacity: .62;
            cursor: not-allowed;
            transform: none;
        }

        .message {
            padding: 15px 18px;
            border-radius: 16px;
            margin: 20px 0;
            font-weight: 700;
            display: none;
            border: 1px solid var(--border);
        }

        .message.success {
            background: color-mix(in srgb, var(--admin-primary) 9%, #ffffff);
            color: var(--admin-primary);
            border-color: color-mix(in srgb, var(--admin-primary) 20%, #e5e7eb);
        }

        .message.error {
            background: #fef2f2;
            color: var(--danger);
            border-color: #fecaca;
        }

        .loading-spinner {
            display: none;
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255, 255, 255, .45);
            border-top: 2px solid #fff;
            border-radius: 50%;
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

        .overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, .35);
            z-index: 999;
            opacity: 0;
            visibility: hidden;
            transition: all .3s ease;
            backdrop-filter: blur(3px);
        }

        .overlay.show {
            opacity: 1;
            visibility: visible;
        }

        .toast {
            position: fixed;
            top: 90px;
            right: 20px;
            padding: 16px 18px;
            border-radius: 18px;
            color: var(--text);
            z-index: 20000;
            transform: translateX(420px);
            transition: transform .3s ease;
            box-shadow: 0 18px 42px rgba(15, 23, 42, .16);
            backdrop-filter: blur(12px);
            min-width: 300px;
            max-width: 420px;
            background: #fff;
            border: 1px solid var(--border);
            font-weight: 650;
        }

        .toast.show {
            transform: translateX(0);
        }

        .toast.success {
            border-color: color-mix(in srgb, var(--admin-primary) 22%, #e5e7eb);
            color: var(--admin-primary);
        }

        .toast.error {
            border-color: #fecaca;
            color: var(--danger);
        }

        .toast.info {
            border-color: color-mix(in srgb, var(--admin-primary) 16%, #e5e7eb);
            color: var(--text-soft);
        }

        .toast-dot {
            width: 10px;
            height: 10px;
            border-radius: 999px;
            background: currentColor;
            flex: 0 0 10px;
        }

        @media (max-width:768px) {
            .header {
                padding: 13px 18px;
            }

            .main-content {
                padding: 22px 16px;
            }

            .main-content.sidebar-open {
                margin-left: 0;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .preview-section,
            .config-section {
                padding: 22px 18px;
                border-radius: 22px;
            }

            .preview-company {
                font-size: 22px;
            }

            .toast {
                left: 16px;
                right: 16px;
                min-width: 0;
                transform: translateY(-140px);
            }

            .toast.show {
                transform: translateY(0);
            }
        }

        /* ===== Pipeline: Estados / Tags ===== */
        .pl-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 4px 0 20px;
        }

        .pl-tab {
            padding: 11px 18px;
            border: 1px solid var(--border);
            border-radius: 999px;
            cursor: pointer;
            transition: all .2s ease;
            background: #fff;
            color: var(--text-soft);
            font-weight: 700;
            font-size: 13px;
        }

        .pl-tab:hover {
            border-color: color-mix(in srgb, var(--admin-primary) 26%, #e5e7eb);
            color: var(--admin-primary);
        }

        .pl-tab.active {
            border-color: color-mix(in srgb, var(--admin-primary) 22%, transparent);
            background: color-mix(in srgb, var(--admin-primary) 11%, #ffffff);
            color: var(--admin-primary);
            box-shadow: 0 12px 24px rgba(15, 23, 42, .06);
        }

        .pl-panel {
            display: none;
        }

        .pl-panel.active {
            display: block;
        }

        .pl-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }

        .pl-hint {
            color: var(--text-muted);
            font-size: 13px;
            line-height: 1.45;
        }

        .pl-add-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: color-mix(in srgb, var(--admin-primary) 10%, #ffffff);
            color: var(--admin-primary);
            border: 1px solid color-mix(in srgb, var(--admin-primary) 18%, #e5e7eb);
            padding: 10px 16px;
            border-radius: 14px;
            font-weight: 800;
            font-size: 13px;
            cursor: pointer;
            transition: all .2s ease;
            white-space: nowrap;
        }

        .pl-add-btn:hover {
            transform: translateY(-1px);
            background: color-mix(in srgb, var(--admin-primary) 16%, #ffffff);
        }

        .pl-add-btn svg {
            width: 16px;
            height: 16px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2.4;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .estado-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .estado-row {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 13px 16px;
            border: 1px solid var(--border);
            border-radius: 16px;
            background: #fff;
            transition: box-shadow .2s ease, border-color .2s ease, opacity .2s ease;
        }

        .estado-row:hover {
            box-shadow: var(--shadow-card);
        }

        .estado-row.dragging {
            opacity: .5;
            border-style: dashed;
            border-color: var(--admin-primary);
        }

        .estado-row.editing {
            border-color: color-mix(in srgb, var(--admin-primary) 45%, #e5e7eb);
            box-shadow: 0 0 0 4px color-mix(in srgb, var(--admin-primary) 11%, transparent);
        }

        .estado-handle {
            cursor: grab;
            color: var(--text-muted);
            font-size: 18px;
            line-height: 1;
            display: inline-grid;
            place-items: center;
            width: 20px;
            flex-shrink: 0;
            user-select: none;
        }

        .estado-handle:active {
            cursor: grabbing;
        }

        .estado-pos {
            width: 26px;
            height: 26px;
            flex-shrink: 0;
            display: grid;
            place-items: center;
            border-radius: 8px;
            background: var(--surface-muted);
            color: var(--text-soft);
            font-weight: 800;
            font-size: 12px;
        }

        .estado-name {
            font-weight: 700;
            color: var(--text);
            white-space: nowrap;
        }

        .estado-desc {
            color: var(--text-muted);
            font-size: 13px;
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .estado-final {
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .02em;
            padding: 3px 9px;
            border-radius: 999px;
            background: color-mix(in srgb, var(--admin-primary) 12%, #fff);
            color: var(--admin-primary);
            border: 1px solid color-mix(in srgb, var(--admin-primary) 20%, #e5e7eb);
            flex-shrink: 0;
        }

        .estado-final[hidden] {
            display: none;
        }

        .estado-actions {
            display: flex;
            gap: 6px;
            flex-shrink: 0;
        }

        .icon-btn {
            width: 34px;
            height: 34px;
            display: grid;
            place-items: center;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: #fff;
            color: var(--text-soft);
            cursor: pointer;
            transition: all .2s ease;
        }

        .icon-btn:hover {
            color: var(--admin-primary);
            border-color: color-mix(in srgb, var(--admin-primary) 30%, #e5e7eb);
            transform: translateY(-1px);
        }

        .icon-btn.danger:hover {
            color: var(--danger);
            border-color: #fecaca;
        }

        .icon-btn svg {
            width: 16px;
            height: 16px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .estado-form {
            border: 1px solid color-mix(in srgb, var(--admin-primary) 22%, #e5e7eb);
            border-radius: 16px;
            padding: 18px;
            background: var(--surface-soft);
            margin-top: 12px;
        }

        .estado-form[hidden] {
            display: none;
        }

        .estado-form h4 {
            font-size: 14px;
            font-weight: 800;
            color: var(--text);
            margin-bottom: 14px;
        }

        .estado-form .form-grid {
            gap: 16px;
        }

        .checkbox-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 14px;
            color: var(--text-soft);
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
        }

        .checkbox-row input {
            width: 18px;
            height: 18px;
            accent-color: var(--admin-primary);
            cursor: pointer;
        }

        .estado-form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 18px;
        }

        .btn-ghost,
        .btn-solid {
            padding: 11px 18px;
            border-radius: 12px;
            font-weight: 800;
            font-size: 13px;
            cursor: pointer;
            transition: all .2s ease;
            border: 1px solid var(--border);
        }

        .btn-ghost {
            background: #fff;
            color: var(--text-soft);
        }

        .btn-ghost:hover {
            color: var(--text);
            box-shadow: var(--shadow-card);
        }

        .btn-solid {
            background: var(--admin-gradient);
            color: #fff;
            border: none;
            box-shadow: 0 12px 24px color-mix(in srgb, var(--admin-primary) 22%, transparent);
        }

        .btn-solid:hover {
            transform: translateY(-1px);
        }

        .tag-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 18px;
        }

        .tag-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border: 1px solid var(--border);
            border-radius: 999px;
            background: #fff;
            color: var(--text);
            font-weight: 700;
            font-size: 13px;
        }

        .tag-chip button {
            width: 18px;
            height: 18px;
            display: grid;
            place-items: center;
            border: none;
            background: transparent;
            color: var(--text-muted);
            cursor: pointer;
            border-radius: 50%;
            font-size: 16px;
            line-height: 1;
            padding: 0;
        }

        .tag-chip button:hover {
            color: var(--danger);
            background: #fef2f2;
        }

        .tag-empty {
            color: var(--text-muted);
            font-size: 13px;
        }

        .tag-input-wrap {
            max-width: 380px;
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
                <span class="section-icon"><?= adminConfigIcon('eye') ?></span>
                Vista Previa por Roles
            </h2>

            <!-- Tabs para diferentes vistas -->
            <div class="preview-tabs">
                <div class="preview-tab active" onclick="switchPreview('admin')">Vista Admin</div>
                <div class="preview-tab" onclick="switchPreview('agent')">Vista Agente</div>
            </div>

            <!-- Admin Preview -->
            <div class="preview-header" id="adminPreview"
                style="background: linear-gradient(135deg, <?= $config['admin_primary_color'] ?> 0%, <?= $config['admin_secondary_color'] ?> 100%);">
                <div class="preview-company" id="companyPreviewAdmin"><?= htmlspecialchars($config['company_name']) ?>
                </div>
                <div class="preview-tagline">Panel de Administración</div>
            </div>

            <!-- Agent Preview -->
            <div class="preview-header" id="agentPreview"
                style="background: linear-gradient(135deg, <?= $config['agent_primary_color'] ?> 0%, <?= $config['agent_secondary_color'] ?> 100%); display: none;">
                <div class="preview-company" id="companyPreviewAgent"><?= htmlspecialchars($config['company_name']) ?>
                </div>
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
                    <span class="section-icon"><?= adminConfigIcon('building') ?></span>
                    Información de la Empresa
                </h2>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="company_name">Nombre de la Agencia</label>
                        <input type="text" id="company_name" name="company_name"
                            value="<?= htmlspecialchars($config['company_name']) ?>" placeholder="Travel Agency"
                            readonly style="background-color: #f1f5f9; cursor: not-allowed; color: #64748b;">
                        <small style="color: #64748b; font-size: 12px; display: block; margin-top: 5px;">
                            El nombre de la agencia solo puede ser modificado por el Superadmin desde la gestión de
                            agencias
                        </small>
                    </div>
                </div>

                <div class="form-grid" style="margin-top: 25px;">
                    <div class="form-group">
                        <label for="logo_url">Logo de la Empresa</label>
                        <div class="image-upload" onclick="document.getElementById('logoInput').click()">
                            <input type="file" id="logoInput" accept="image/*">
                            <div class="upload-content">
                                <div class="upload-icon"><?= adminConfigIcon('upload') ?></div>
                                <div>
                                    <strong>Subir Logo</strong><br>
                                    <small>PNG, JPG, SVG o WebP (máx. <?= $config['max_file_size'] ?>MB)</small>
                                </div>
                            </div>
                            <?php if ($config['logo_url']): ?>
                                <img src="<?= htmlspecialchars($config['logo_url']) ?>" class="image-preview"
                                    id="logoPreview">
                            <?php endif; ?>
                        </div>
                        <input type="hidden" id="logo_url" name="logo_url"
                            value="<?= htmlspecialchars($config['logo_url'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <!-- Color Settings -->
            <div class="config-section">
                <h2 class="section-title">
                    <span class="section-icon"><?= adminConfigIcon('palette') ?></span>
                    Personalización de Colores por Roles
                </h2>

                <!-- Admin Colors -->
                <h3 class="role-title"><span class="field-icon"><?= adminConfigIcon('shield') ?></span>Colores del
                    Administrador</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="admin_primary_color">Color Primario Admin</label>
                        <div class="color-input">
                            <input type="color" id="admin_primary_color" name="admin_primary_color" class="color-picker"
                                value="<?= $config['admin_primary_color'] ?>">
                            <input type="text" class="color-text" value="<?= $config['admin_primary_color'] ?>"
                                readonly>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="admin_secondary_color">Color Secundario Admin</label>
                        <div class="color-input">
                            <input type="color" id="admin_secondary_color" name="admin_secondary_color"
                                class="color-picker" value="<?= $config['admin_secondary_color'] ?>">
                            <input type="text" class="color-text" value="<?= $config['admin_secondary_color'] ?>"
                                readonly>
                        </div>
                    </div>
                </div>

                <!-- Agent Colors -->
                <h3 class="role-title"><span class="field-icon"><?= adminConfigIcon('plane') ?></span>Colores del Agente
                </h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="agent_primary_color">Color Primario Agente</label>
                        <div class="color-input">
                            <input type="color" id="agent_primary_color" name="agent_primary_color" class="color-picker"
                                value="<?= $config['agent_primary_color'] ?>">
                            <input type="text" class="color-text" value="<?= $config['agent_primary_color'] ?>"
                                readonly>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="agent_secondary_color">Color Secundario Agente</label>
                        <div class="color-input">
                            <input type="color" id="agent_secondary_color" name="agent_secondary_color"
                                class="color-picker" value="<?= $config['agent_secondary_color'] ?>">
                            <input type="text" class="color-text" value="<?= $config['agent_secondary_color'] ?>"
                                readonly>
                        </div>
                    </div>
                </div>
            </div>


            <div class="advanced-content" id="advancedContent">
                <div class="config-section">
                    <h2 class="section-title">
                        <span class="section-icon"><?= adminConfigIcon('settings') ?></span>
                        Configuraciones Técnicas
                    </h2>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="maintenance_mode"><span
                                    class="field-icon"><?= adminConfigIcon('lock') ?></span>Modo Mantenimiento</label>
                            <select id="maintenance_mode" name="maintenance_mode">
                                <option value="0" <?= !$config['maintenance_mode'] ? 'selected' : '' ?>>Desactivado
                                </option>
                                <option value="1" <?= $config['maintenance_mode'] ? 'selected' : '' ?>>Activado</option>
                            </select>
                            <small style="color: #718096;">Bloquea el acceso a usuarios no administradores</small>
                        </div>
                    </div>
                </div>
            </div>

        </form>

        <!-- ============================================================ -->
        <!-- Gmail — Cuenta de correo del administrador                  -->
        <!-- ============================================================ -->
        <div class="config-section">
            <h2 class="section-title">
                <span class="section-icon"><?= adminConfigIcon('plane') ?></span>
                Cuenta Gmail
            </h2>
            <?php if ($flashSuccess): ?>
            <div style="background:#dcfce7;border:1px solid #86efac;color:#166534;padding:12px 18px;border-radius:10px;margin-bottom:16px;font-size:14px;font-weight:500;">
                <?= htmlspecialchars($flashSuccess) ?>
            </div>
            <?php endif; ?>
            <?php if ($flashError): ?>
            <div style="background:#fee2e2;border:1px solid #fca5a5;color:#991b1b;padding:12px 18px;border-radius:10px;margin-bottom:16px;font-size:14px;font-weight:500;">
                <?= htmlspecialchars($flashError) ?>
            </div>
            <?php endif; ?>
            <p style="font-size:14px;color:#64748b;margin-bottom:20px;line-height:1.6;">
                Conecta tu cuenta de Gmail para que el pipeline capture los correos de leads entrantes y puedas responder desde la plataforma. Esta cuenta es la principal de la agencia.
            </p>
            <?php if ($gmailAccount && $gmailAccount['status'] === 'active'): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;background:#f0fdf4;border:1px solid #86efac;border-radius:12px;padding:16px 20px;flex-wrap:wrap;gap:12px;">
                <div style="display:flex;align-items:center;gap:12px;">
                    <div style="width:38px;height:38px;background:#dcfce7;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:20px;">✉️</div>
                    <div>
                        <div style="font-size:13px;font-weight:700;color:#166534;">Gmail conectado</div>
                        <div style="font-size:14px;color:#15803d;"><?= htmlspecialchars($gmailAccount['email']) ?></div>
                    </div>
                </div>
                <a href="<?= APP_URL ?>/gmail/oauth?action=disconnect"
                   onclick="return confirm('¿Desconectar esta cuenta de Gmail?')"
                   style="display:inline-flex;align-items:center;gap:6px;padding:9px 16px;background:#fee2e2;color:#dc2626;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;">
                    Desconectar
                </a>
            </div>
            <?php else: ?>
            <div style="display:flex;align-items:center;justify-content:space-between;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:16px 20px;flex-wrap:wrap;gap:12px;">
                <div style="display:flex;align-items:center;gap:12px;">
                    <div style="width:38px;height:38px;background:#f1f5f9;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:20px;">✉️</div>
                    <div>
                        <div style="font-size:13px;font-weight:700;color:#475569;">Sin cuenta Gmail</div>
                        <div style="font-size:13px;color:#94a3b8;">Conecta Gmail para capturar leads y responder correos</div>
                    </div>
                </div>
                <a href="<?= APP_URL ?>/gmail/oauth?action=connect"
                   style="display:inline-flex;align-items:center;gap:6px;padding:9px 18px;background:var(--primary-gradient);color:#fff;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;box-shadow:0 2px 8px rgba(0,0,0,.12);">
                    Conectar Gmail
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- ============================================================ -->
        <!-- Pipeline: Estados y Tags                                     -->
        <!-- SOLO FRONTEND: el muestreo de datos reales y el guardado     -->
        <!-- quedan pendientes (ver TODOs en el script).                  -->
        <!-- ============================================================ -->
        <div class="config-section">
            <h2 class="section-title">
                <span class="section-icon"><?= adminConfigIcon('settings') ?></span>
                Configuración del Pipeline
            </h2>

            <div class="pl-tabs">
                <div class="pl-tab active" onclick="switchPipelineTab('estados', this)">Estados</div>
                <div class="pl-tab" onclick="switchPipelineTab('tags', this)">Tags</div>
            </div>

            <!-- Panel Estados -->
            <div class="pl-panel active" id="plPanelEstados">
                <div class="pl-toolbar">
                    <p class="pl-hint">Define y ordena las columnas del tablero Kanban. Arrastra con &#9135; para
                        reordenar.</p>
                    <button type="button" class="pl-add-btn" onclick="openEstadoCreate()">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 5v14"></path>
                            <path d="M5 12h14"></path>
                        </svg>
                        Estado
                    </button>
                </div>

                <!-- Las filas se generan en JS desde SAMPLE_ESTADOS (placeholder) -->
                <div class="estado-list" id="estadoList"></div>

                <!-- Form inline reutilizable (alta/edición) -->
                <div class="estado-form" id="estadoForm" hidden>
                    <h4 id="estadoFormTitle">Nuevo estado</h4>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="estadoNombre">Nombre *</label>
                            <input type="text" id="estadoNombre" placeholder="Ej. Cotizado" maxlength="100">
                        </div>
                        <div class="form-group">
                            <label for="estadoDesc">Descripción</label>
                            <input type="text" id="estadoDesc" placeholder="Texto de ayuda opcional">
                        </div>
                    </div>
                    <label class="checkbox-row">
                        <input type="checkbox" id="estadoFinal">
                        Es estado final (cierra el lead)
                    </label>
                    <div class="estado-form-actions">
                        <button type="button" class="btn-ghost" onclick="closeEstadoForm()">Cancelar</button>
                        <button type="button" class="btn-solid" onclick="saveEstadoForm()">Guardar estado</button>
                    </div>
                </div>
            </div>

            <!-- Panel Tags -->
            <div class="pl-panel" id="plPanelTags">
                <p class="pl-hint" style="margin-bottom:16px;">Etiquetas libres para clasificar los leads.</p>

                <!-- Los chips se generan en JS desde SAMPLE_TAGS (placeholder) -->
                <div class="tag-list" id="tagList"></div>

                <div class="form-group tag-input-wrap">
                    <input type="text" id="tagInput" placeholder="Escribe un nombre y presiona Enter…" maxlength="80"
                        onkeydown="handleTagInput(event)">
                </div>
            </div>
        </div>

        <!-- Save Section (queda al final; envía #configForm vía atributo form=) -->
        <div class="save-section">
            <button type="submit" form="configForm" class="save-btn" id="saveBtn">
                <?= adminConfigIcon('save') ?>
                Guardar Configuración
                <div class="loading-spinner" id="loadingSpinner"></div>
            </button>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        const APP_URL = '<?= APP_URL ?>';
        let isLoading = false;
        let currentPreview = 'admin';
        let sidebarOpen = false;

        // Inicialización
        document.addEventListener('DOMContentLoaded', function () {
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
                    colorPicker.addEventListener('change', function () {
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

            input.addEventListener('change', function (e) {
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

            uploadDiv.addEventListener('dragover', function (e) {
                e.preventDefault();
                this.classList.add('dragover');
            });

            uploadDiv.addEventListener('dragleave', function (e) {
                e.preventDefault();
                this.classList.remove('dragover');
            });

            uploadDiv.addEventListener('drop', function (e) {
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
                        icon: '',
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

            const indicatorClass = type === 'success' ? 'success' : type === 'error' ? 'error' : 'info';
            toast.innerHTML = `
        <div style="display: flex; align-items: center; gap: 10px;">
            <span class="toast-dot"></span>
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

            setTimeout(function () {
                const select = document.querySelector('.goog-te-combo');
                if (select) {
                    select.addEventListener('change', function () {
                        if (this.value) saveLanguage(this.value);
                    });
                }
            }, 2000);
        }

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

        // ============================================================
        // PIPELINE: Estados / Tags 
        // ============================================================

        let editingEstadoRow = null;

        document.addEventListener('DOMContentLoaded', function () {
            initEstadoDnd();
            getEstados();
            getTags();
        });

        // ---- Tabs ----
        function switchPipelineTab(tab, el) {
            document.querySelectorAll('.pl-tab').forEach(t => t.classList.remove('active'));
            el.classList.add('active');
            document.getElementById('plPanelEstados').classList.toggle('active', tab === 'estados');
            document.getElementById('plPanelTags').classList.toggle('active', tab === 'tags');
            closeEstadoForm();
        }

        // ---- Estados: render ----
        async function getEstados() {

            const response = await fetch(`${APP_URL}/pipeline/api?action=get_estados`, {
                method: 'GET'
            });
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const result = await response.json();
            const data = result.data;
            const list = document.getElementById('estadoList');
            list.innerHTML = '';
            data.forEach(e => list.appendChild(buildEstadoRow(e)));
            renumberEstados();


        }

        function buildEstadoRow({ id, nombre, descripcion, es_final }) {
            const row = document.createElement('div');
            row.className = 'estado-row';
            row.draggable = true;
            if (id != null) row.dataset.id = id;
            row.dataset.final = es_final ? '1' : '0';
            row.innerHTML =
                '<span class="estado-handle" title="Arrastrar para reordenar">&#9135;</span>' +
                '<span class="estado-pos">1</span>' +
                '<span class="estado-name"></span>' +
                '<span class="estado-desc"></span>' +
                '<span class="estado-final"' + (es_final ? '' : ' hidden') + '>FINAL</span>' +
                '<span class="estado-actions">' +
                '<button type="button" class="icon-btn" title="Editar" onclick="openEstadoEdit(this)">' +
                '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9"></path><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"></path></svg>' +
                '</button>' +
                '<button type="button" class="icon-btn danger" title="Eliminar" onclick="deleteEstado(this)">' +
                '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path></svg>' +
                '</button>' +
                '</span>';
            // textContent evita inyección de HTML en nombre/descripción
            row.querySelector('.estado-name').textContent = nombre;
            row.querySelector('.estado-desc').textContent = descripcion || '';
            attachDragHandlers(row);
            return row;
        }

        function renumberEstados() {
            document.querySelectorAll('#estadoList .estado-row').forEach((row, i) => {
                row.querySelector('.estado-pos').textContent = i + 1;
            });
        }

        // ---- Estados: drag & drop para reordenar ----
        function initEstadoDnd() {
            const list = document.getElementById('estadoList');
            list.addEventListener('dragover', function (e) {
                e.preventDefault();
                const dragging = list.querySelector('.dragging');
                if (!dragging) return;
                const after = getDragAfterElement(list, e.clientY);
                if (after == null) list.appendChild(dragging);
                else list.insertBefore(dragging, after);
            });
        }

        function attachDragHandlers(row) {
            row.addEventListener('dragstart', function () {
                closeEstadoForm();
                row.classList.add('dragging');
            });
            row.addEventListener('dragend', function () {
                row.classList.remove('dragging');
                renumberEstados();
                persistEstadoOrder();
            });
        }

        async function persistEstadoOrder() {
            const ids = [...document.querySelectorAll('#estadoList .estado-row')]
                .map(r => r.dataset.id)
                .filter(v => v != null && v !== '')
                .map(Number);
            if (ids.length === 0) return; // filas sin id real (placeholder): no persiste

            try {
                const res = await fetch(`${APP_URL}/pipeline/api?action=reordenar_estados`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ orden: ids })
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.message || 'No se pudo guardar el orden');
                showMessage('Orden actualizado', 'success');
            } catch (e) {
                showMessage('Error al guardar el orden: ' + e.message, 'error');
            }
        }

        function getDragAfterElement(list, y) {
            const els = [...list.querySelectorAll('.estado-row:not(.dragging)')];
            return els.reduce((closest, child) => {
                const box = child.getBoundingClientRect();
                const offset = y - box.top - box.height / 2;
                if (offset < 0 && offset > closest.offset) return { offset, element: child };
                return closest;
            }, { offset: Number.NEGATIVE_INFINITY }).element || null;
        }

        function openEstadoCreate() {
            editingEstadoRow = null;
            document.querySelectorAll('.estado-row.editing').forEach(r => r.classList.remove('editing'));
            const form = document.getElementById('estadoForm');
            document.getElementById('estadoFormTitle').textContent = 'Nuevo estado';
            document.getElementById('estadoNombre').value = '';
            document.getElementById('estadoDesc').value = '';
            document.getElementById('estadoFinal').checked = false;
            document.getElementById('estadoList').after(form); // posición base: bajo la lista
            form.hidden = false;
            document.getElementById('estadoNombre').focus();
            form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        function openEstadoEdit(btn) {
            const row = btn.closest('.estado-row');
            editingEstadoRow = row;
            const form = document.getElementById('estadoForm');
            document.getElementById('estadoFormTitle').textContent = 'Editar estado';
            document.getElementById('estadoNombre').value = row.querySelector('.estado-name').textContent;
            document.getElementById('estadoDesc').value = row.querySelector('.estado-desc').textContent;
            document.getElementById('estadoFinal').checked = row.dataset.final === '1';
            document.querySelectorAll('.estado-row.editing').forEach(r => r.classList.remove('editing'));
            row.classList.add('editing');
            row.after(form); // se expande inline, justo debajo de la fila
            form.hidden = false;
            document.getElementById('estadoNombre').focus();
        }

        function closeEstadoForm() {
            const form = document.getElementById('estadoForm');
            if (!form) return;
            form.hidden = true;
            if (editingEstadoRow) editingEstadoRow.classList.remove('editing');
            editingEstadoRow = null;
            document.getElementById('estadoList').after(form); // devolver a posición base
        }

        async function saveEstadoForm() {
            const nombre = document.getElementById('estadoNombre').value.trim();
            if (!nombre) { showMessage('El nombre del estado es obligatorio', 'error'); return; }
            const descripcion = document.getElementById('estadoDesc').value.trim();
            const esFinal = document.getElementById('estadoFinal').checked;
            const data = {
                id: editingEstadoRow ? Number(editingEstadoRow.dataset.id) : null,
                nombre: nombre,
                descripcion: descripcion,
                es_final: esFinal
            }
            if (editingEstadoRow) {
                const response = await fetch(`${APP_URL}/pipeline/api?action=update_estados`, {
                    method: "POST",
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data),
                })
                const result = await response.json();

                if (result.success) {
                    editingEstadoRow.querySelector('.estado-name').textContent = nombre;
                    editingEstadoRow.querySelector('.estado-desc').textContent = descripcion;
                    editingEstadoRow.dataset.final = esFinal ? '1' : '0';
                    editingEstadoRow.querySelector('.estado-final').hidden = !esFinal;

                } else {
                    alert("Error al crear estado");
                }


            } else {
                const response = await fetch(`${APP_URL}/pipeline/api?action=save_estados`, {
                    method: "POST",
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                })
                const result = await response.json();
                if (result.success) {
                    const row = buildEstadoRow({ id: result.id, nombre, descripcion, es_final: esFinal });
                    document.getElementById('estadoList').appendChild(row);

                }

            }
            closeEstadoForm();
            renumberEstados();
        }

        async function deleteEstado(btn) {
            const row = btn.closest('.estado-row');
            const nombre = row.querySelector('.estado-name').textContent;
            const data = {
                id: Number(row.dataset.id)
            }
            if (!confirm('¿Eliminar el estado "' + nombre + '"?')) return;
            const response = await fetch(`${APP_URL}/pipeline/api?action=delete_estados`, {
                method: "POST",
                body: JSON.stringify(data)
            });
            const result = await response.json();

            if (result.success) {


                row.remove();
                renumberEstados();
            } else {
                showMessage(result.message || "no se pudo eliminar");
            }
        }

        // ---- Tags ----
        async function getTags() {
            const response = await fetch(`${APP_URL}/pipeline/api?action=get_tags`, {
                method: 'GET'
            });
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const result = await response.json();

            const data = result.data;
            console.log(data);
            const list = document.getElementById('tagList');
            list.innerHTML = '';
            data.forEach(e => list.appendChild(buildTagChip(e["nombre"], e['id'])));


        }

        function buildTagChip(nombre, id) {
            const chip = document.createElement('span');
            chip.className = 'tag-chip';
            chip.id = id;
            const label = document.createElement('span');
            label.textContent = nombre;
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.title = 'Eliminar';
            btn.textContent = '×';
            btn.onclick = async function () {
                const data = { id: chip.id }
                const response = await fetch(`${APP_URL}/pipeline/api?action=delete_tags`, {
                    method: "POST",
                    body: JSON.stringify(data)
                })
                const result = await response.json();

                if (result.success) {
                    chip.remove();
                    refreshTagEmpty();

                } else {
                    showMessage(result.message || "error al eliminar");
                }


            };
            chip.append(label, btn);
            return chip;
        }

        async function handleTagInput(e) {
            if (e.key !== 'Enter') return;
            e.preventDefault();
            const input = e.target;
            const nombre = input.value.trim();
            if (!nombre) return;
            const exists = [...document.querySelectorAll('#tagList .tag-chip > span:first-child')]
                .some(s => s.textContent.toLowerCase() === nombre.toLowerCase());
            if (exists) { showMessage('Esa etiqueta ya existe', 'error'); return; }
            const data = {
                nombre: nombre
            }
            const response = await fetch(`${APP_URL}/pipeline/api?action=save_tags`, {
                method: "POST",
                body: JSON.stringify(data)
            })
            const result = await response.json();
            if (result.success) {
                document.getElementById('tagList').appendChild(buildTagChip(nombre));
                input.value = '';
                refreshTagEmpty();
            }


        }

        function refreshTagEmpty() {
            const list = document.getElementById('tagList');
            const hasChips = list.querySelector('.tag-chip');
            let empty = list.querySelector('.tag-empty');
            if (!hasChips && !empty) {
                empty = document.createElement('span');
                empty.className = 'tag-empty';
                empty.textContent = 'No hay etiquetas todavía.';
                list.appendChild(empty);
            } else if (hasChips && empty) {
                empty.remove();
            }
        }
    </script>
</body>

</html>