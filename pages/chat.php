<?php
// =====================================
// ARCHIVO: pages/chat.php - Chat para hablar con usuarios
// =====================================

App::requireLogin();

$pipeline_id = $_GET['id'] ?? null; // Asumimos que entramos por ?id=123
if (!$pipeline_id) {
    die("ID de cotización requerido.");
}

$db = Database::getInstance();
$cuenta_row = $db->fetch("SELECT id FROM email_accounts WHERE user_id = ? AND provider = 'gmail' AND status = 'active' LIMIT 1", [$_SESSION['user_id']]);
$cuenta_id = $cuenta_row ? $cuenta_row['id'] : 0;
$pipeline_data = $db->fetch("SELECT solicitud_id FROM pipeline WHERE id = ?", [$pipeline_id]);

// Extraemos la columna del array de forma segura
$sol_id = $pipeline_data['solicitud_id'] ?? null;

// =====================================
// Colores dinámicos según la agencia del usuario
// =====================================
$user = App::getUser();
$userColors = App::getColorsForRole($user['role'] ?? 'agent');
$primaryColor = $userColors['primary'] ?? '#3b82f6';
$secondaryColor = $userColors['secondary'] ?? '#2563eb';
$primaryRgb = implode(', ', sscanf($primaryColor, "#%02x%02x%02x"));

// Luminancia: si el color de la agencia es casi blanco, usamos un acento
// oscuro de respaldo para que el texto/bordes no se "escondan" sobre fondo blanco.
$lum = function ($hex) {
    $hex = ltrim($hex, '#');
    return 0.299 * hexdec(substr($hex, 0, 2))
        + 0.587 * hexdec(substr($hex, 2, 2))
        + 0.114 * hexdec(substr($hex, 4, 2));
};
$primaryIsLight = $lum($primaryColor) > 210;
$accentColor = $primaryIsLight ? '#334155' : $primaryColor; // texto/bordes sobre fondo claro
$onPrimary = $primaryIsLight ? '#1e293b' : '#ffffff';      // texto sobre fondo de color
?>

<head>
    <style>
        /* Colores dinámicos de la agencia */
        :root {
            --primary-color:
                <?= $primaryColor ?>
            ;
            --secondary-color:
                <?= $secondaryColor ?>
            ;
            --primary-gradient: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            --primary-rgb:
                <?= $primaryRgb ?>
            ;
            --accent-color:
                <?= $accentColor ?>
            ;
            /* color seguro para texto/bordes sobre blanco */
            --on-primary:
                <?= $onPrimary ?>
            ;
            /* texto legible sobre el color de la agencia */
        }

        /* Agrega esto a tu index.css o un archivo específico de chat */
        .chat-container {
            display: flex;
            height: calc(100vh - 80px);
            /* Ajusta según tu navbar */
            background-color: #f4f7fc;
            font-family: 'Inter', sans-serif;
        }

        /* Panel Izquierdo */
        .chat-sidebar {
            width: 320px;
            background: #ffffff;
            border-right: 1px solid #e2e8f0;
            padding: 20px 16px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.02);
            z-index: 10;
            overflow-y: auto;
        }

        .sidebar-header h2 {
            font-size: 20px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .badge {
            background: rgba(var(--primary-rgb), 0.1);
            color: var(--accent-color);
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .info-group {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            border-radius: 10px;
            transition: background 0.2s;
        }



        .info-group-icon {
            width: 36px;
            height: 36px;
            border-radius: 9px;

            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: #858585ff;
        }

        .info-group-content {
            flex: 1;
            min-width: 0;
        }

        .info-group-content label {
            font-size: 11px;
            text-transform: uppercase;
            color: #94a3b8;
            font-weight: 600;
            letter-spacing: 0.05em;
            display: block;
            margin-bottom: 2px;
        }

        .info-group-content input {
            font-size: 14px;
            color: #1e293b;
            font-weight: 500;
            background: transparent;
            border: none;
            border-bottom: 1.5px solid transparent;
            padding: 4px;
            margin-left: -4px;
            width: calc(100% + 8px);
            outline: none;
            font-family: inherit;
            transition: all 0.2s ease;
            border-radius: 4px;
        }

        .info-group-content input:hover,
        .info-select:hover {
            background-color: #f1f5f9;
            /* Hover gris claro */
        }

        .info-group-content input:focus {
            background-color: transparent;
            border-bottom-color: var(--accent-color);
        }

        .info-group-content input::placeholder {
            color: #cbd5e1;
        }

        .info-select {
            font-size: 14px;
            color: #1e293b;
            font-weight: 500;
            background: transparent;
            border: none;
            border-bottom: 1.5px solid transparent;
            padding: 4px;
            margin-left: -4px;
            width: calc(100% + 8px);
            outline: none;
            font-family: inherit;
            cursor: pointer;
            -webkit-appearance: none;
            appearance: none;
            transition: all 0.2s ease;
            border-radius: 4px;
        }

        .info-select:focus {
            background-color: transparent;
            border-bottom-color: var(--accent-color);
        }

        /* Opciones desplegables personalizadas */
        .custom-select-wrapper {
            position: relative;
            width: 100%;
        }

        .custom-select-options {
            position: absolute;
            top: 100%;
            left: -4px;
            right: -4px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            margin-top: 4px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 50;
            display: none;
            padding: 4px 0;
        }

        .custom-select-wrapper.open .custom-select-options {
            display: block;
        }

        .custom-option {
            padding: 8px 12px;
            font-size: 14px;
            color: #1e293b;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
        }

        .custom-option:hover {
            background: var(--accent-color);
            color: #ffffff;
        }

        .dates-summary {
            font-size: 14px;
            color: #1e293b;
            font-weight: 500;
            padding: 2px 0;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .dates-summary:hover {
            color: var(--accent-color);
        }

        .dates-summary svg {
            opacity: 0.4;
            transition: opacity 0.2s;
        }

        .dates-summary:hover svg {
            opacity: 1;
        }

        .dates-expanded-fields {
            display: none;
            flex-direction: column;
            gap: 8px;
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px dashed #e2e8f0;
        }

        .date-field {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .date-field-label {
            font-size: 10px;
            text-transform: uppercase;
            color: #94a3b8;
            font-weight: 700;
            letter-spacing: 0.06em;
        }

        .date-field input[type="date"] {
            font-size: 13px;
            color: #1e293b;
            font-weight: 500;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 7px;
            padding: 5px 8px;
            outline: none;
            font-family: inherit;
            width: 100%;
            cursor: pointer;
            transition: border-color 0.2s;
        }

        .date-field input[type="date"]:focus {
            border-color: var(--accent-color);
            background: rgba(var(--primary-rgb), 0.08);
        }

        .sidebar-divider {
            height: 1px;
            background: #f1f5f9;
            margin: 10px 0;
        }

        .lead-name-block {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 12px;
        }



        .lead-name-info {
            flex: 1;
            min-width: 0;
        }

        .lead-name-text {
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }



        /* Panel Derecho (Chat) */
        .chat-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #fafaf9;
        }

        .chat-header {
            padding: 20px 30px;
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
        }

        .chat-header h3 {
            font-size: 18px;
            font-weight: 600;
            color: #0f172a;
        }

        .subtitle {
            font-size: 13px;
            color: #64748b;
        }

        .chat-messages {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        /* Burbujas de Chat */
        .message {
            max-width: 70%;
            padding: 16px;
            border-radius: 16px;
            font-size: 15px;
            line-height: 1.5;
            animation: fadeIn 0.3s ease-out;
        }

        .message.inbound {
            align-self: flex-start;
            background: #ffffff;
            color: #334155;
            border: 1px solid #e2e8f0;
            border-bottom-left-radius: 4px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
        }

        .message.outbound {
            align-self: flex-end;
            background: var(--primary-gradient);
            /* Color de la agencia */
            color: var(--on-primary);
            border-bottom-right-radius: 4px;
            box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.2);
        }

        .message-time {
            display: block;
            font-size: 11px;
            margin-top: 8px;
            opacity: 0.7;
        }

        /* Adjuntos dentro de una burbuja */
        .chat-attachments {
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px solid rgba(148, 163, 184, 0.3);
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .chat-attachment {
            font-size: 13px;
        }

        .chat-attachment a {
            color: inherit;
            text-decoration: underline;
            word-break: break-all;
        }

        /* =============================================
           Área de Composición de Mensajes (Editor)
        ============================================= */
        .chat-composer {
            background: #ffffff;
            border-top: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
        }

        /* Toolbar de formato */
        .composer-format-bar {
            display: flex;
            align-items: center;
            gap: 2px;
            padding: 8px 16px;
            border-bottom: 1px solid #f1f5f9;
            background: #f8fafc;
        }

        .fmt-btn {
            width: 30px;
            height: 28px;
            border: none;
            background: transparent;
            border-radius: 5px;
            cursor: pointer;
            color: #64748b;
            font-size: 13px;
            font-family: 'Georgia', serif;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.15s, color 0.15s;
            flex-shrink: 0;
        }

        .fmt-btn:hover {
            background: #e2e8f0;
            color: #0f172a;
        }

        .fmt-btn.active {
            background: rgba(var(--primary-rgb), 0.12);
            color: var(--accent-color);
        }

        .fmt-btn b {
            font-weight: 800;
            font-size: 14px;
        }

        .fmt-btn i {
            font-style: italic;
            font-size: 14px;
        }

        .fmt-btn.fmt-u {
            text-decoration: underline;
            font-size: 14px;
            font-weight: 600;
        }

        .fmt-btn.fmt-s {
            text-decoration: line-through;
            font-size: 14px;
            font-weight: 600;
        }

        .fmt-divider {
            width: 1px;
            height: 18px;
            background: #e2e8f0;
            margin: 0 4px;
            flex-shrink: 0;
        }

        /* Área de texto (contenteditable) */
        .composer-textarea-wrap {
            padding: 12px 16px;
            flex: 1;
        }

        .composer-editor {
            width: 100%;
            min-height: 72px;
            max-height: 160px;
            border: none;
            outline: none;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            color: #1e293b;
            background: transparent;
            line-height: 1.6;
            overflow-y: auto;
            word-break: break-word;
            white-space: pre-wrap;
        }

        /* Placeholder del contenteditable */
        .composer-editor:empty::before {
            content: attr(data-placeholder);
            color: #cbd5e1;
            pointer-events: none;
        }

        /* Zona de archivos adjuntos */
        .composer-attachments {
            display: none;
            flex-wrap: wrap;
            gap: 6px;
            padding: 0 16px 8px 16px;
        }

        .composer-attachments.has-files {
            display: flex;
        }

        .attachment-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 4px 10px;
            font-size: 12px;
            color: #475569;
            font-weight: 500;
            max-width: 200px;
        }

        .attachment-chip span {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .attachment-chip-remove {
            border: none;
            background: transparent;
            cursor: pointer;
            color: #94a3b8;
            padding: 0;
            line-height: 1;
            font-size: 15px;
            flex-shrink: 0;
            transition: color 0.15s;
        }

        .attachment-chip-remove:hover {
            color: #ef4444;
        }

        .attachment-chip svg {
            width: 13px;
            height: 13px;
            flex-shrink: 0;
            stroke: #64748b;
        }

        /* Barra inferior de acciones */
        .composer-action-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 12px 10px 12px;
            gap: 8px;
        }

        .composer-actions-left {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .composer-actions-right {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Botones de acción del composer (clip, template, color...) */
        .action-btn {
            width: 34px;
            height: 34px;
            border: none;
            background: transparent;
            border-radius: 7px;
            cursor: pointer;
            color: #94a3b8;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.15s, color 0.15s;
            flex-shrink: 0;
            position: relative;
        }

        .action-btn:hover {
            background: #f1f5f9;
            color: #475569;
        }

        .action-btn svg {
            width: 18px;
            height: 18px;
            stroke-width: 1.8;
        }

        .action-bar-divider {
            width: 1px;
            height: 20px;
            background: #e2e8f0;
            margin: 0 4px;
        }

        /* Botón enviar */
        .btn-send {
            background: var(--primary-gradient);
            color: var(--on-primary);
            border: none;
            padding: 0 20px;
            height: 36px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 7px;
            transition: background 0.2s, transform 0.1s, box-shadow 0.2s;
            font-family: inherit;
            white-space: nowrap;
        }

        .btn-send:hover {
            filter: brightness(0.95);
            box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.3);
        }

        .btn-send:active {
            transform: scale(0.97);
        }

        .btn-send svg {
            width: 15px;
            height: 15px;
            stroke-width: 2.5;
        }

        /* Tooltip del botón template */
        .action-btn[title]:hover::after {
            content: attr(title);
            position: absolute;
            bottom: calc(100% + 6px);
            left: 50%;
            transform: translateX(-50%);
            background: #1e293b;
            color: #fff;
            font-size: 11px;
            font-weight: 500;
            padding: 4px 8px;
            border-radius: 5px;
            white-space: nowrap;
            pointer-events: none;
            z-index: 100;
            font-family: 'Inter', sans-serif;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Botón de Volver */
        .btn-back,
        .btn-itinerary {
            background: transparent;
            border: 1px solid #e2e8f0;
            border-radius: 10%;
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #64748b !important;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }

        .btn-back:hover,
        .btn-itinerary:hover {
            background: #f8fafc;
            color: #0f172a;
            border-color: #cbd5e1;
            transform: scale(1.05);
        }

        /* === Modal Itinerario (estilo TiPi) === */
        .tipi-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);
            backdrop-filter: blur(3px);
            z-index: 1000;
            display: none;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.2s ease;
        }

        .tipi-overlay.active {
            display: flex;
        }

        .tipi-modal {
            background: #fff;
            border-radius: 16px;
            width: 80%;
            font-family: 'Inter', sans-serif;

            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.25);
            overflow: hidden;
            animation: slideUp 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes slideUp {
            from {
                transform: translateY(30px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .tipi-header {
            background: var(--primary-gradient);
            padding: 28px 28px 20px;
            color: var(--on-primary);
            position: relative;
            overflow: hidden;
        }

        .tipi-header h2 {
            font-size: 18px;
            font-weight: 700;
            margin: 0 0 4px;
            font-family: 'Inter', sans-serif;
        }

        .tipi-header p {
            font-size: 13px;
            opacity: 0.75;
            margin: 0;
        }

        .tipi-header-illustration {
            position: absolute;
            right: 20px;
            bottom: 0;
            opacity: 0.35;
        }

        .tipi-close {
            position: absolute;
            top: 14px;
            right: 14px;
            background: rgba(255, 255, 255, 0.15);
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            color: #fff;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            line-height: 1;
            transition: background 0.2s;
        }

        .tipi-close:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .tipi-body {
            padding: 20px 24px 24px;
        }

        .tipi-section-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--accent-color);
            margin: 0 0 12px;
        }

        .tipi-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 0;
        }

        .tipi-row-text h4 {
            font-size: 14px;
            font-weight: 600;
            color: #0f172a;
            margin: 0 0 2px;
        }

        .tipi-row-text p {
            font-size: 12px;
            color: #64748b;
            margin: 0;
        }

        .tipi-btn {
            background: #fff;
            border: 1.5px solid #cbd5e1;
            border-radius: 8px;
            padding: 7px 16px;
            font-size: 13px;
            font-weight: 600;
            color: #0f172a;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.15s;
            font-family: 'Inter', sans-serif;
        }

        .tipi-btn:hover {
            border-color: var(--accent-color);
            color: var(--accent-color);
            background: rgba(var(--primary-rgb), 0.06);
        }

        .tipi-divider {
            height: 1px;
            background: #f1f5f9;
            margin: 8px 0;
        }

        /* Selector expandible */
        .tipi-select-container {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease, opacity 0.2s ease;
            opacity: 0;
        }

        .tipi-select-container.open {
            max-height: 160px;
            opacity: 1;
        }

        .tipi-select-inner {
            display: flex;
            gap: 8px;
            align-items: center;
            padding: 10px 0 4px;
        }

        .tipi-select {
            flex: 1;
            padding: 8px 12px;
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            font-size: 13px;
            font-family: 'Inter', sans-serif;
            color: #0f172a;
            background: #f8fafc;
            outline: none;
            transition: border-color 0.15s;
        }

        .tipi-select:focus {
            border-color: var(--accent-color);
        }

        .tipi-btn-confirm {
            background: var(--primary-gradient);
            color: var(--on-primary);
            border: none;
            border-radius: 8px;
            padding: 8px 18px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
            font-family: 'Inter', sans-serif;
            transition: background 0.15s;
        }

        .tipi-btn-confirm:hover {
            filter: brightness(0.92);
        }

        .tipi-btn-confirm:disabled {
            background: #94a3b8;
            cursor: not-allowed;
        }

        /* =============================================
           Modal de Templates (plantillas de mensaje)
        ============================================= */
        .tpl-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);
            backdrop-filter: blur(3px);
            z-index: 1000;
            display: none;
            align-items: flex-start;
            justify-content: center;
            padding-top: 80px;
            animation: fadeIn 0.2s ease;
        }

        .tpl-overlay.active {
            display: flex;
        }

        .tpl-modal {
            background: #fff;
            border-radius: 14px;
            width: 90%;
            font-family: 'Inter', sans-serif;
            max-width: 900px;
            max-height: 75vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.25);
            overflow: hidden;
            animation: slideUp 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .tpl-modal-header {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 22px 24px;
            border-bottom: 1px solid #f1f5f9;
            flex-shrink: 0;
        }

        .tpl-modal-header h2 {
            font-size: 22px;
            font-weight: 800;
            color: #1e293b;
            margin: 0;
            font-family: 'Inter', sans-serif;
            white-space: nowrap;
        }

        .tpl-modal-header #tpl-search {
            flex: 1;
            background: #f4f5f7;
            border: 1px solid transparent;
            border-radius: 8px;
            padding: 11px 14px;
            font-size: 14px;
            color: #1e293b;
            outline: none;
            font-family: inherit;
            transition: border-color 0.15s, background 0.15s;
        }

        .tpl-modal-header #tpl-search:focus {
            background: #fff;
            border-color: #cbd5e1;
        }

        .tpl-create-btn {
            background: var(--primary-gradient);
            color: var(--on-primary);
            border: none;
            border-radius: 8px;
            padding: 11px 20px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
            font-family: 'Inter', sans-serif;
            transition: background 0.15s;
        }

        .tpl-create-btn:hover {
            filter: brightness(0.95);
        }

        .tpl-close {
            background: transparent;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            font-size: 26px;
            line-height: 1;
            padding: 0 4px;
            transition: color 0.15s;
        }

        .tpl-close:hover {
            color: #1e293b;
        }

        .tpl-list {
            overflow-y: auto;
            flex: 1;
        }

        .tpl-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 14px 24px;
            font-size: 15px;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.12s;
        }

        .tpl-row:hover {
            background: #f8fafc;
        }

        .tpl-row-name {
            flex: 1;
            min-width: 0;
            cursor: pointer;
            padding: 2px 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            transition: color 0.12s;
        }

        .tpl-row-name:hover {
            color: var(--accent-color);
        }

        .tpl-row-actions {
            display: flex;
            gap: 4px;
            flex-shrink: 0;
        }

        .tpl-icon-btn {
            width: 30px;
            height: 30px;
            border: none;
            background: transparent;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            transition: background 0.15s, color 0.15s;
        }

        .tpl-icon-btn.edit:hover {
            background: #eff6ff;
            color: #3b82f6;
        }

        .tpl-icon-btn.del:hover {
            background: #fee2e2;
            color: #ef4444;
        }

        .tpl-icon-btn svg {
            width: 15px;
            height: 15px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
        }

        .tpl-empty {
            padding: 40px 24px;
            text-align: center;
            color: #94a3b8;
            font-size: 14px;
        }

        /* Formulario de creación dentro del modal */
        .tpl-form {
            flex-direction: column;
            gap: 10px;
            padding: 20px 24px;
            border-top: 1px solid #f1f5f9;
            background: #fafafa;
            flex-shrink: 0;
        }

        .tpl-form input,
        .tpl-form textarea {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 14px;
            font-family: inherit;
            color: #1e293b;
            outline: none;
            resize: vertical;
            transition: border-color 0.15s;
        }

        .tpl-form input:focus,
        .tpl-form textarea:focus {
            border-color: var(--accent-color);
        }

        .tpl-form textarea {
            min-height: 90px;
        }

        .tpl-form-title {
            font-size: 13px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .tpl-form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }

        .tpl-form-actions button {
            background: var(--primary-gradient);
            color: var(--on-primary);
            border: none;
            border-radius: 8px;
            padding: 9px 22px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            transition: background 0.15s;
        }

        .tpl-form-actions button:hover {
            filter: brightness(0.95);
        }

        .tpl-form-actions .tpl-cancel-btn {
            background: #fff;
            color: #64748b;
            border: 1px solid #e2e8f0;
        }

        .tpl-form-actions .tpl-cancel-btn:hover {
            background: #f8fafc;
            filter: none;
        }
    </style>
</head>
<div class="chat-container">
    <!-- Panel Izquierdo: Info del Lead -->
    <aside class="chat-sidebar">
        <div class="sidebar-header"
            style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px; justify-content: space-between;">
            <!-- Botón Volver -->
            <button class="btn-back" onclick="history.back()" title="Volver atrás">
                <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none"
                    stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>

            </button>

            <!-- itinerario -->
            <button class="btn-itinerary" onclick="abrir_modal_itinerario()" title="Itinerario">
                <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                    <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                </svg>

            </button>
        </div>
        <!-- Nombre del cliente -->
        <div class="lead-name-block">
            <div class="lead-name-info">
                <p class="lead-name-text" id="lead-name">JUAN DAVID</p>

            </div>
        </div>

        <div class="sidebar-divider"></div>

        <!-- Estado -->
        <div class="info-group">
            <div class="info-group-icon icon-status">
                <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"
                    stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10" />
                    <polyline points="12 6 12 12 16 14" />
                </svg>
            </div>
            <div class="info-group-content">
                <label>Estado</label>
                <div class="custom-select-wrapper" id="status-wrapper">
                    <div class="info-select" id="status-display" onclick="toggleCustomSelect(event, 'status-wrapper')">
                        --</div>
                    <div class="custom-select-options" id="lead-status-options"></div>
                    <input type="hidden" id="lead-status" value="">
                </div>
            </div>
        </div>

        <!-- Tags -->
        <div class="info-group">
            <div class="info-group-icon icon-tag">
                <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z" />
                    <line x1="7" y1="7" x2="7.01" y2="7" />
                </svg>
            </div>
            <div class="info-group-content">
                <label>Tags</label>
                <div class="custom-select-wrapper" id="stags-wrapper">
                    <div class="info-select" id="stags-display" onclick="toggleCustomSelect(event, 'stags-wrapper')">--
                    </div>
                    <div class="custom-select-options" id="lead-stags-options"></div>
                    <input type="hidden" id="lead-stags" value="">
                </div>
            </div>
        </div>

        <!-- Presupuesto -->
        <div class="info-group">
            <div class="info-group-icon icon-budget">
                <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"
                    stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="1" x2="12" y2="23" />
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
                </svg>
            </div>
            <div class="info-group-content">
                <label>Presupuesto</label>
                <input type="text" id="lead-budget" placeholder="--" onblur="guardar_data()" />
            </div>
        </div>

        <!-- Agente -->
        <div class="info-group">
            <div class="info-group-icon icon-agent">
                <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                    <circle cx="12" cy="7" r="4" />
                    <polyline points="16 11 18 13 22 9" />
                </svg>
            </div>
            <div class="info-group-content">
                <label>Agente</label>
                <div class="custom-select-wrapper" id="agentes-wrapper">
                    <div class="info-select" id="agentes-display"
                        onclick="toggleCustomSelect(event, 'agentes-wrapper')">--</div>
                    <div class="custom-select-options" id="lead-agentes-options"></div>
                    <input type="hidden" id="lead-agentes" value="">
                </div>
            </div>
        </div>

        <!-- Teléfono -->
        <div class="info-group">
            <div class="info-group-icon icon-phone">
                <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path
                        d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.38 2 2 0 0 1 3.6 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.59a16 16 0 0 0 6 6l.95-.95a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z" />
                </svg>
            </div>
            <div class="info-group-content">
                <label>Teléfono</label>
                <input type="tel" id="lead-phone" placeholder="--" onblur="guardar_data()" />
            </div>
        </div>

        <!-- Viajeros (PAX) -->
        <div class="info-group">
            <div class="info-group-icon icon-pax">
                <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                    <circle cx="9" cy="7" r="4" />
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                    <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                </svg>
            </div>
            <div class="info-group-content">
                <label>Viajeros (PAX)</label>
                <input type="number" id="lead-travelers" placeholder="--" min="1" onblur="guardar_data()" />
            </div>
        </div>

        <!-- Fechas (colapsable) -->
        <div class="info-group dates-group">
            <div class="info-group-icon icon-date" style="margin-top:2px;">
                <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"
                    stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                    <line x1="16" y1="2" x2="16" y2="6" />
                    <line x1="8" y1="2" x2="8" y2="6" />
                    <line x1="3" y1="10" x2="21" y2="10" />
                </svg>
            </div>
            <div class="info-group-content">
                <label>Fechas</label>
                <div class="dates-summary" id="dates-summary" onclick="toggleDates()">
                    <span id="dates-display-text"></span>
                    <svg viewBox="0 0 24 24" width="13" height="13" stroke="currentColor" stroke-width="2.5" fill="none"
                        stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9" />
                    </svg>
                </div>
                <div class="dates-expanded-fields" id="dates-expanded">
                    <div class="date-field">
                        <span class="date-field-label">Salida</span>
                        <input type="date" id="lead-date-start" oninput="updateDatesSummary()"
                            onclick="event.stopPropagation()" onblur="guardar_data()" />
                    </div>
                    <div class="date-field">
                        <span class="date-field-label">Regreso</span>
                        <input type="date" id="lead-date-end" oninput="updateDatesSummary()"
                            onclick="event.stopPropagation()" onblur="guardar_data()" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Destino -->
        <div class="info-group">
            <div class="info-group-icon icon-dest">
                <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0 1 18 0z" />
                    <circle cx="12" cy="10" r="3" />
                </svg>
            </div>
            <div class="info-group-content">
                <label>Destino</label>
                <span id="lead-destination"> </span>
            </div>
        </div>
        <div class="info-group">
            <div class="info-group-icon icon-dest">
                <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                    <polyline points="16 2 16 8 20 8" />
                    <line x1="8" y1="11" x2="16" y2="11" />
                    <line x1="8" y1="15" x2="16" y2="15" />
                    <line x1="8" y1="19" x2="16" y2="19" />
                </svg>
            </div>
            <div class="info-group-content">
                <label>Notas</label>
                <span id="lead-description"></span>
            </div>
        </div>
    </aside>
    <!-- Panel Derecho: Conversación -->
    <main class="chat-main">
        <header class="chat-header">
            <h3>Conversación (Vía Gmail)</h3>

        </header>
        <div class="chat-messages" id="chat-messages">
            <!-- Los mensajes se inyectarán aquí con JS -->
        </div>
        <!-- Composer: área de escritura enriquecida -->
        <div class="chat-composer">

            <!-- Barra de formato -->
            <div class="composer-format-bar">
                <button class="fmt-btn" id="fmt-bold" title="Negrita" onclick="applyFormat('bold')">
                    <b>B</b>
                </button>
                <button class="fmt-btn fmt-i" id="fmt-italic" title="Cursiva" onclick="applyFormat('italic')">
                    <i>I</i>
                </button>
                <button class="fmt-btn fmt-u" id="fmt-underline" title="Subrayado" onclick="applyFormat('underline')">
                    U
                </button>
                <button class="fmt-btn fmt-s" id="fmt-strike" title="Tachado" onclick="applyFormat('strikethrough')">
                    S
                </button>
            </div>

            <!-- Área de edición enriquecida -->
            <div class="composer-textarea-wrap">
                <div id="message-input" class="composer-editor" contenteditable="true"
                    data-placeholder="Escribe un mensaje al cliente..."></div>
            </div>

            <!-- Preview de archivos adjuntos -->
            <div class="composer-attachments" id="attachments-preview"></div>

            <!-- Input file oculto -->
            <input type="file" id="file-input" multiple style="display:none">

            <!-- Barra de acciones inferior -->
            <div class="composer-action-bar">
                <div class="composer-actions-left">
                    <!-- Adjuntar archivo -->
                    <button class="action-btn" title="Adjuntar archivo" onclick="triggerFileInput()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path
                                d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66L9.41 17.4a2 2 0 0 1-2.83-2.83l8.49-8.48" />
                        </svg>
                    </button>

                    <!-- Templates de mensajes -->
                    <button class="action-btn" id="btn-templates" title="Plantillas de mensaje"
                        onclick="abrir_modal_templates()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                            <polyline points="14 2 14 8 20 8" />
                            <line x1="9" y1="13" x2="15" y2="13" />
                            <line x1="9" y1="17" x2="13" y2="17" />
                        </svg>
                    </button>

                    <div class="action-bar-divider"></div>


                </div>

                <div class="composer-actions-right">


                    <!-- Botón Enviar -->
                    <button id="btn-send" class="btn-send" onclick="enviar_mensaje()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"
                            stroke-linejoin="round">
                            <line x1="22" y1="2" x2="11" y2="13" />
                            <polygon points="22 2 15 22 11 13 2 9 22 2" />
                        </svg>
                        Enviar
                    </button>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
    const APP_URL = '<?= APP_URL ?>';
    const PIPELINE_ID = <?= json_encode($pipeline_id) ?>;

    let estadosData = [], tagsData = [], agentesData = [];

    document.addEventListener('click', function (e) {
        document.querySelectorAll('.custom-select-wrapper').forEach(w => {
            if (!w.contains(e.target)) w.classList.remove('open');
        });
    });

    function toggleCustomSelect(event, wrapperId) {
        event.stopPropagation();
        document.querySelectorAll('.custom-select-wrapper').forEach(w => {
            if (w.id !== wrapperId) w.classList.remove('open');
        });
        document.getElementById(wrapperId).classList.toggle('open');
    }

    function selectCustomOption(inputId, displayId, wrapperId, value, text) {
        document.getElementById(inputId).value = value;
        document.getElementById(displayId).textContent = text;
        document.getElementById(wrapperId).classList.remove('open');
        guardar_data();
    }

    function updateCustomSelectUI(inputId, value, dataArray, idField, nameField, displayId) {
        document.getElementById(inputId).value = value || '';
        if (!value) {
            document.getElementById(displayId).textContent = '--';
            return;
        }
        const item = dataArray.find(a => a[idField] == value);
        document.getElementById(displayId).textContent = item ? item[nameField] : '--';
    }

    function toggleDates() {
        const expanded = document.getElementById('dates-expanded');
        const chevron = document.querySelector('.dates-summary svg');
        const isOpen = expanded.style.display === 'flex';
        expanded.style.display = isOpen ? 'none' : 'flex';
        chevron.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';
        chevron.style.transition = 'transform 0.2s';
    }

    function updateDatesSummary() {
        const start = document.getElementById('lead-date-start').value;
        const end = document.getElementById('lead-date-end').value;
        const display = document.getElementById('dates-display-text');

        const fmt = (dateStr) => {
            if (!dateStr) return '';
            const d = new Date(dateStr + 'T00:00:00');
            return d.toLocaleDateString('es-ES', { day: 'numeric', month: 'short', year: 'numeric' });
        };

        if (start && end) {
            display.textContent = fmt(start) + ' → ' + fmt(end);
        } else if (start) {
            display.textContent = fmt(start);
        } else if (end) {
            display.textContent = '→ ' + fmt(end);
        } else {
            display.textContent = '--';
        }
    }

    async function llamar_estados() {
        try {
            const response = await fetch(`${APP_URL}/pipeline/api?action=get_estados`, { method: 'GET' });
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            const result = await response.json();
            estadosData = result.data;
            const container = document.getElementById('lead-status-options');
            container.innerHTML = '';
            estadosData.forEach(item => {
                const option = document.createElement('div');
                option.className = 'custom-option';
                option.textContent = item.nombre;
                option.onclick = () => selectCustomOption('lead-status', 'status-display', 'status-wrapper', item.id, item.nombre);
                container.appendChild(option);
            });
        } catch (error) { console.error('Error:', error); }
    }

    async function llamar_tags() {
        try {
            const response = await fetch(`${APP_URL}/pipeline/api?action=get_tags`, { method: 'GET' });
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            const result = await response.json();
            tagsData = result.data;
            const container = document.getElementById('lead-stags-options');
            container.innerHTML = '';
            tagsData.forEach(item => {
                const option = document.createElement('div');
                option.className = 'custom-option';
                option.textContent = item.nombre;
                option.onclick = () => selectCustomOption('lead-stags', 'stags-display', 'stags-wrapper', item.id, item.nombre);
                container.appendChild(option);
            });
        } catch (error) { console.error('Error:', error); }
    }

    async function llamar_agentes() {
        try {
            const response = await fetch(`${APP_URL}/pipeline/api?action=get_agentes`, { method: 'GET' });
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            const result = await response.json();
            agentesData = result.data;
            const container = document.getElementById('lead-agentes-options');
            container.innerHTML = '';
            agentesData.forEach(item => {
                const option = document.createElement('div');
                option.className = 'custom-option';
                option.textContent = item.username;
                option.onclick = () => selectCustomOption('lead-agentes', 'agentes-display', 'agentes-wrapper', item.id, item.username);
                container.appendChild(option);
            });
        } catch (error) { console.error('Error:', error); }
    }
    // ============================================================
    // TEMPLATES DE MENSAJE
    // ============================================================
    let templatesData = [];

    function abrir_modal_templates() {
        document.getElementById('tpl-overlay').classList.add('active');
        document.getElementById('tpl-search').value = '';
        cancelar_form_template();
        llamar_templates();
    }

    function cerrar_modal_templates(e) {
        // Si viene de un click en el overlay, cerrar solo si fue sobre el fondo
        if (e && e.target !== document.getElementById('tpl-overlay')) return;
        document.getElementById('tpl-overlay').classList.remove('active');
    }

    async function llamar_templates() {
        try {
            const response = await fetch(`${APP_URL}/pipeline/api?action=get_templates`, { method: 'GET' });
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            const result = await response.json();
            templatesData = result.data || [];
            render_templates(templatesData);
        } catch (error) {
            console.error('Error cargando templates:', error);
        }
    }

    function render_templates(lista) {
        const container = document.getElementById('tpl-list');
        container.innerHTML = '';

        if (!lista.length) {
            container.innerHTML = '<div class="tpl-empty">No hay templates todavía. Crea el primero.</div>';
            return;
        }

        lista.forEach(t => {
            const row = document.createElement('div');
            row.className = 'tpl-row';

            const name = document.createElement('div');
            name.className = 'tpl-row-name';
            name.textContent = t.nombre;
            name.title = 'Insertar en el mensaje';
            name.onclick = () => usar_template(t);

            const acts = document.createElement('div');
            acts.className = 'tpl-row-actions';

            const editBtn = document.createElement('button');
            editBtn.className = 'tpl-icon-btn edit';
            editBtn.title = 'Editar';
            editBtn.innerHTML = '<svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>';
            editBtn.onclick = (e) => { e.stopPropagation(); abrir_form_template(t); };

            const delBtn = document.createElement('button');
            delBtn.className = 'tpl-icon-btn del';
            delBtn.title = 'Eliminar';
            delBtn.innerHTML = '<svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>';
            delBtn.onclick = (e) => { e.stopPropagation(); eliminar_template(t); };

            acts.appendChild(editBtn);
            acts.appendChild(delBtn);
            row.appendChild(name);
            row.appendChild(acts);
            container.appendChild(row);
        });
    }

    function filtrar_templates() {
        const q = document.getElementById('tpl-search').value.toLowerCase();
        render_templates(templatesData.filter(t => (t.nombre || '').toLowerCase().includes(q)));
    }

    // Seleccionar un template => mostrarlo en el composer del chat
    function usar_template(t) {
        const editor = document.getElementById('message-input');
        editor.innerHTML = t.texto || '';
        editor.focus();
        cerrar_modal_templates();
    }

    // Abre el formulario. Si recibe un template, entra en modo edición (pre-rellena).
    function abrir_form_template(t) {
        const form = document.getElementById('tpl-form');
        const isEdit = t && t.id;
        document.getElementById('tpl-form-title').textContent = isEdit ? 'Editar template' : 'Nuevo template';
        document.getElementById('tpl-edit-id').value = isEdit ? t.id : '';
        document.getElementById('tpl-nombre').value = isEdit ? t.nombre : '';
        document.getElementById('tpl-texto').value = isEdit ? t.texto : '';
        form.style.display = 'flex';
        document.getElementById('tpl-nombre').focus();
    }

    function cancelar_form_template() {
        document.getElementById('tpl-form').style.display = 'none';
        document.getElementById('tpl-edit-id').value = '';
        document.getElementById('tpl-nombre').value = '';
        document.getElementById('tpl-texto').value = '';
    }

    async function guardar_template() {
        const nombre = document.getElementById('tpl-nombre').value.trim();
        const texto = document.getElementById('tpl-texto').value.trim();
        const editId = document.getElementById('tpl-edit-id').value;
        if (!nombre || !texto) {
            alert('El nombre y el contenido son obligatorios');
            return;
        }
        try {
            const fd = new FormData();
            fd.append('nombre', nombre);
            fd.append('texto', texto);
            const action = editId ? 'update_template' : 'crear_template';
            if (editId) fd.append('id', editId);

            const response = await fetch(`${APP_URL}/pipeline/api?action=${action}`, {
                method: 'POST',
                body: fd
            });
            const result = await response.json();
            if (result.success) {
                cancelar_form_template();
                llamar_templates();
            } else {
                alert(result.message || 'Error al guardar el template');
            }
        } catch (error) {
            console.error('Error guardando template:', error);
        }
    }

    async function eliminar_template(t) {
        if (!confirm(`¿Eliminar el template "${t.nombre}"?`)) return;
        try {
            const fd = new FormData();
            fd.append('id', t.id);
            const response = await fetch(`${APP_URL}/pipeline/api?action=delete_template`, {
                method: 'POST',
                body: fd
            });
            const result = await response.json();
            if (result.success) {
                llamar_templates();
            } else {
                alert(result.message || 'Error al eliminar el template');
            }
        } catch (error) {
            console.error('Error eliminando template:', error);
        }
    }
    async function carga_datos() {
        try {
            const response = await fetch(`${APP_URL}/pipeline/api?action=get_pipeline&id=${PIPELINE_ID}`, {
                method: 'GET'
            });
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();

            if (result.success && result.data) {
                const data = result.data;

                // Text elements
                document.getElementById('lead-name').textContent = data.nombre_cliente || '--';

                // Inputs & Selects
                updateCustomSelectUI('lead-status', data.estado_id, estadosData, 'id', 'nombre', 'status-display');
                updateCustomSelectUI('lead-stags', data.tag_id, tagsData, 'id', 'nombre', 'stags-display');
                updateCustomSelectUI('lead-agentes', data.usuario_id, agentesData, 'id', 'username', 'agentes-display');

                document.getElementById('lead-budget').value = data.budget || '';
                document.getElementById('lead-phone').value = data.telefono_cliente || '';
                document.getElementById('lead-travelers').value = data.viajeros || '';
                document.getElementById('dates-display-text').textContent = data.fecha_salida + ' - ' + data.fecha_llegada || '';
                document.getElementById('lead-date-start').value = data.fecha_salida || '';
                document.getElementById('lead-date-end').value = data.fecha_llegada || '';
                document.getElementById('lead-destination').textContent = data.destino || '--';
                document.getElementById('lead-description').textContent = data.descripcion || '';

                // Update date summary UI
                updateDatesSummary();
            }
        }
        catch (error) {
            console.error('Error cargando datos del pipeline:', error);
        }
    }
    async function guardar_data() {
        try {
            const data = {
                estado_id: document.getElementById('lead-status').value,
                tag_id: document.getElementById('lead-stags').value,
                usuario_id: document.getElementById('lead-agentes').value,
                budget: document.getElementById('lead-budget').value,
                telefono_cliente: document.getElementById('lead-phone').value,
                viajeros: document.getElementById('lead-travelers').value,
                fecha_salida: document.getElementById('lead-date-start').value,
                fecha_llegada: document.getElementById('lead-date-end').value,
            }
            const response = await fetch(`${APP_URL}/pipeline/api?action=save_pipeline&id=${PIPELINE_ID}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
        } catch (error) {
            console.error('Error guardando:', error);
        }
    }
    document.addEventListener('DOMContentLoaded', async () => {
        // Wait for all select options to load before setting the pipeline data
        await Promise.all([
            llamar_estados(),
            llamar_tags(),
            llamar_agentes()
        ]);

        await carga_datos();

        // Cargar historial del chat y refrescar periódicamente para recibir nuevos mensajes
        await cargar_chat();
        setInterval(cargar_chat, 10000);
    });

    // ============================================================
    // FORMATO DE TEXTO (contenteditable + execCommand)
    // ============================================================
    function applyFormat(type) {
        const editor = document.getElementById('message-input');
        editor.focus();

        const cmdMap = {
            'bold': 'bold',
            'italic': 'italic',
            'underline': 'underline',
            'strikethrough': 'strikeThrough'
        };
        const btnIdMap = {
            'bold': 'fmt-bold',
            'italic': 'fmt-italic',
            'underline': 'fmt-underline',
            'strikethrough': 'fmt-strike'
        };

        const cmd = cmdMap[type];
        if (!cmd) return;

        document.execCommand(cmd, false, null);

        // Actualizar estado visual (active) del botón
        const isActive = document.queryCommandState(cmd);
        const btn = document.getElementById(btnIdMap[type]);
        if (btn) btn.classList.toggle('active', isActive);
    }

    // Sincronizar estado de botones al cambiar selección
    document.addEventListener('selectionchange', () => {
        const sel = window.getSelection();
        if (!sel || sel.rangeCount === 0) return;
        const editor = document.getElementById('message-input');
        if (!editor || !editor.contains(sel.anchorNode)) return;

        const checks = [
            { cmd: 'bold', btnId: 'fmt-bold' },
            { cmd: 'italic', btnId: 'fmt-italic' },
            { cmd: 'underline', btnId: 'fmt-underline' },
            { cmd: 'strikeThrough', btnId: 'fmt-strike' }
        ];
        checks.forEach(({ cmd, btnId }) => {
            const btn = document.getElementById(btnId);
            if (btn) btn.classList.toggle('active', document.queryCommandState(cmd));
        });
    });

    // ============================================================
    // ADJUNTAR ARCHIVOS
    // ============================================================
    let attachedFiles = []; // Array de File objects

    function triggerFileInput() {
        document.getElementById('file-input').click();
    }

    // Límites de tamaño (Gmail tope ~25MB por correo)
    const MAX_FILE_SIZE = 20 * 1024 * 1024;  // 20 MB por archivo
    const MAX_TOTAL_SIZE = 25 * 1024 * 1024; // 25 MB en total

    document.getElementById('file-input').addEventListener('change', function () {
        const newFiles = Array.from(this.files);
        newFiles.forEach(file => {
            // Evitar duplicados por nombre+tamaño
            const exists = attachedFiles.some(f => f.name === file.name && f.size === file.size);
            if (exists) return;

            if (file.size > MAX_FILE_SIZE) {
                alert(`"${file.name}" supera el máximo de 20 MB y no se adjuntó.`);
                return;
            }
            const totalActual = attachedFiles.reduce((s, f) => s + f.size, 0);
            if (totalActual + file.size > MAX_TOTAL_SIZE) {
                alert(`No se puede adjuntar "${file.name}": se superaría el máximo total de 25 MB.`);
                return;
            }
            attachedFiles.push(file);
        });
        this.value = ''; // Reset para permitir seleccionar el mismo archivo de nuevo
        renderAttachments();
    });

    function renderAttachments() {
        const preview = document.getElementById('attachments-preview');
        preview.innerHTML = '';

        if (attachedFiles.length === 0) {
            preview.classList.remove('has-files');
            return;
        }
        preview.classList.add('has-files');

        attachedFiles.forEach((file, index) => {
            const chip = document.createElement('div');
            chip.className = 'attachment-chip';

            // Icono según tipo
            const isImage = file.type.startsWith('image/');
            const iconSvg = isImage
                ? `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>`
                : `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>`;

            const sizeFmt = file.size > 1024 * 1024
                ? (file.size / (1024 * 1024)).toFixed(1) + ' MB'
                : Math.round(file.size / 1024) + ' KB';

            chip.innerHTML = `
                ${iconSvg}
                <span title="${file.name}">${file.name}</span>
                <span style="color:#94a3b8;font-weight:400">(${sizeFmt})</span>
                <button class="attachment-chip-remove" onclick="removeAttachment(${index})" title="Quitar">&times;</button>
            `;
            preview.appendChild(chip);
        });
    }

    function removeAttachment(index) {
        attachedFiles.splice(index, 1);
        renderAttachments();
    }

    // ============================================================
    // LIMPIAR COMPOSITOR DESPUÉS DE ENVIAR
    // ============================================================
    function clearComposer() {
        document.getElementById('message-input').innerHTML = '';
        attachedFiles = [];
        renderAttachments();
    }

    // ============================================================
    // HELPER: obtener el contenido del editor para enviar
    // ============================================================
    function getMessageContent() {
        return document.getElementById('message-input').innerHTML.trim();
    }
    function getMessageText() {
        return document.getElementById('message-input').innerText.trim();
    }

    const EMAIL_ACCOUNT_ID = <?= json_encode($cuenta_id) ?>;

    async function enviar_mensaje() {
        const message = getMessageContent();
        if (!message || message === '') {
            alert("El mensaje no puede estar vacío");
            return;
        }
        if (!EMAIL_ACCOUNT_ID) {
            alert("No hay una cuenta de Gmail activa configurada para enviar el mensaje.");
            return;
        }

        const btnEnviar = document.getElementById('btn-send');
        const textoOriginalBtn = btnEnviar.innerHTML;
        btnEnviar.innerHTML = 'Enviando...';
        btnEnviar.disabled = true;

        try {
            let response;
            if (attachedFiles.length > 0) {
                // Con adjuntos: multipart/form-data (no fijar Content-Type, el navegador pone el boundary)
                const fd = new FormData();
                fd.append('pipeline_id', PIPELINE_ID);
                fd.append('message_body', message);
                fd.append('email_account_id', EMAIL_ACCOUNT_ID);
                attachedFiles.forEach(file => fd.append('attachments[]', file, file.name));

                response = await fetch(`${APP_URL}/modules/gmail/chat_api.php?action=send`, {
                    method: "POST",
                    body: fd
                });
            } else {
                // Sin adjuntos: JSON
                const payload = {
                    pipeline_id: PIPELINE_ID,
                    message_body: message,
                    email_account_id: EMAIL_ACCOUNT_ID
                };
                response = await fetch(`${APP_URL}/modules/gmail/chat_api.php?action=send`, {
                    method: "POST",
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
            }
            const result = await response.json();

            if (response.ok && result.success) {
                clearComposer();
                _lastMessageCount = -1; // forzar re-render
                await cargar_chat();
            } else {
                alert(result.error || "Error al enviar el mensaje");
            }
        } catch (error) {
            console.error("Error en el envío de mensaje:", error);
            alert("Error de red al enviar el mensaje");
        } finally {
            btnEnviar.innerHTML = textoOriginalBtn;
            btnEnviar.disabled = false;
        }
    }

    // ============================================================
    // CARGA Y RENDER DEL HISTORIAL DE CHAT
    // ============================================================
    let _lastMessageCount = 0;

    function fmtMessageTime(ts) {
        if (!ts) return '';
        const d = new Date(String(ts).replace(' ', 'T'));
        if (isNaN(d.getTime())) return ts;
        return d.toLocaleString('es-ES', {
            day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit'
        });
    }

    // Elimina la cita del mensaje anterior que Gmail (y otros clientes) añaden al responder,
    // para mostrar solo el texto nuevo de cada mensaje.
    function stripQuotedReply(html) {
        const tmp = document.createElement('div');
        tmp.innerHTML = html || '';
        // Contenedores de cita de Gmail + atribución ("On ... wrote:") + blockquotes genéricos
        tmp.querySelectorAll('.gmail_quote_container, .gmail_quote, .gmail_attr, blockquote').forEach(el => el.remove());
        return tmp.innerHTML.trim();
    }

    function renderMessage(msg) {
        const div = document.createElement('div');
        div.className = 'message ' + (msg.direction === 'outbound' ? 'outbound' : 'inbound');
        const time = document.createElement('span');
        time.className = 'message-time';
        time.textContent = fmtMessageTime(msg.received_at);
        // El body ya viene como HTML desde el backend (correo / mensaje del agente)
        const content = document.createElement('div');
        content.innerHTML = stripQuotedReply(msg.body || '');
        div.appendChild(content);
        div.appendChild(time);
        return div;
    }

    async function cargar_chat() {
        try {
            const response = await fetch(`${APP_URL}/modules/gmail/chat_api.php?pipeline_id=${PIPELINE_ID}`);
            if (!response.ok) return;
            const data = await response.json();

            const all = [];
            if (data.origin) {
                all.push({
                    direction: 'inbound',
                    body: data.origin.body,
                    received_at: data.origin.received_at
                });
            }
            (data.messages || []).forEach(m => all.push(m));

            // Solo re-renderizar si cambió el número de mensajes (evita parpadeo en el polling)
            if (all.length === _lastMessageCount) return;
            _lastMessageCount = all.length;

            const container = document.getElementById('chat-messages');
            container.innerHTML = '';

            if (!all.length) {
                container.innerHTML = '<div style="margin:auto;color:#94a3b8;font-size:14px;">No hay mensajes todavía.</div>';
                return;
            }

            all.forEach(m => container.appendChild(renderMessage(m)));
            container.scrollTop = container.scrollHeight;
        } catch (error) {
            console.error('Error cargando el chat:', error);
        }
    }

    // ============================================================
    // MODAL ITINERARIO
    // ============================================================
    let _programas_cargados = false;

    function abrir_modal_itinerario() {
        document.getElementById('tipi-overlay').classList.add('active');
        document.body.style.overflow = 'hidden';
        if (!_programas_cargados) {
            cargar_programas();
        }
    }

    function cerrar_modal_itinerario(e) {
        // Si se llama con evento (click en overlay), cerrar solo si el click fue en el fondo
        if (e && e.target !== document.getElementById('tipi-overlay')) return;
        document.getElementById('tipi-overlay').classList.remove('active');
        document.body.style.overflow = '';
    }

    async function cargar_programas() {
        const select = document.getElementById('tipi-programa-select');
        try {
            const res = await fetch(`${APP_URL}/pipeline/api?action=get_programas`);
            const result = await res.json();
            if (!result.success) throw new Error(result.message);
            const SOLICITUD_ID = "<?= $sol_id ?>";

            const asignacion = typeof SOLICITUD_ID !== 'undefined';
            const programas = result.data;
            if (!asignacion) {
                select.innerHTML = '<option value="">-- Elige un itinerario --</option>';
            }
            programas.forEach(p => {
                const opt = document.createElement('option');
                opt.value = p.id;
                const fecha = p.fecha_salida ? ` · ${p.fecha_salida.slice(0, 7)}` : '';
                opt.textContent = `${p.nombre} (${p.destino}${fecha})`;
                if (asignacion && Number(p.id) === Number(SOLICITUD_ID)) {
                    opt.selected = true;
                }
                select.appendChild(opt);
            });
            _programas_cargados = true;

            // Habilitar botón Asignar cuando el usuario elija uno
            select.addEventListener('change', () => {
                document.getElementById('btn-confirmar-asignacion').disabled = !select.value;
            });
        } catch (err) {
            select.innerHTML = '<option value="">Error al cargar programas</option>';
            console.error('cargar_programas:', err);
        }
    }

    function mostrar_selector_programas() {
        const container = document.getElementById('tipi-select-container');
        container.classList.toggle('open');
    }

    async function confirmar_asignacion() {
        const solicitud_id = document.getElementById('tipi-programa-select').value;
        if (!solicitud_id) return;

        const btn = document.getElementById('btn-confirmar-asignacion');

        btn.disabled = true;

        try {
            const res = await fetch(`${APP_URL}/pipeline/api?action=asignar_itinerario`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ pipeline_id: PIPELINE_ID, solicitud_id: parseInt(solicitud_id) })
            });
            const result = await res.json();
            if (!result.success) throw new Error(result.message);

            cerrar_modal_itinerario();
            // Feedback visual
            const flash = document.createElement('div');
            flash.textContent = '✓ Itinerario asignado correctamente';
            flash.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:var(--primary-gradient);color:var(--on-primary);padding:12px 24px;border-radius:10px;font-size:14px;font-weight:600;z-index:9999;box-shadow:0 8px 24px rgba(var(--primary-rgb),.35);';
            document.body.appendChild(flash);
            setTimeout(() => flash.remove(), 3000);
        } catch (err) {
            alert('Error al asignar: ' + err.message);
            btn.textContent = 'Asignar';
            btn.disabled = false;
        }
    }

    function ir_crear_itinerario() {
        window.location.href = `${APP_URL}/programa?pipeline_id=${PIPELINE_ID}`;
    }

</script>

<!-- Modal Itinerario -->
<div class="tipi-overlay" id="tipi-overlay" onclick="cerrar_modal_itinerario(event)">
    <div class="tipi-modal">
        <!-- Cabecera -->
        <div class="tipi-header">
            <button class="tipi-close" onclick="cerrar_modal_itinerario()">&times;</button>
            <h2>Asignar Itinerario</h2>
            <p>Conecta este lead con un programa de viaje.</p>
            <svg class="tipi-header-illustration" width="110" height="70" viewBox="0 0 110 70" fill="none">
                <rect x="5" y="30" width="20" height="40" rx="2" fill="#fff" opacity=".2" />
                <rect x="10" y="20" width="10" height="10" rx="1" fill="#fff" opacity=".25" />
                <rect x="30" y="20" width="24" height="50" rx="2" fill="#fff" opacity=".2" />
                <rect x="36" y="10" width="12" height="12" rx="1" fill="#fff" opacity=".25" />
                <rect x="60" y="35" width="18" height="35" rx="2" fill="#fff" opacity=".2" />
                <rect x="83" y="28" width="22" height="42" rx="2" fill="#fff" opacity=".2" />
                <rect x="88" y="18" width="12" height="12" rx="1" fill="#fff" opacity=".25" />
                <path d="M0 68 Q55 45 110 68" stroke="#fff" stroke-width="1.5" opacity=".3" fill="none" />
            </svg>
        </div>

        <!-- Cuerpo -->
        <div class="tipi-body">

            <!-- Sección 1: Itinerario existente -->
            <p class="tipi-section-label">Usar un itinerario existente</p>
            <div class="tipi-row">
                <div class="tipi-row-text">
                    <h4>Cargar un itinerario anterior</h4>
                    <p>Puedes asignar un programa que ya tengas creado.</p>
                </div>
                <button class="tipi-btn" id="btn-mostrar-selector" onclick="mostrar_selector_programas()">
                    Seleccionar
                </button>
            </div>

            <!-- Select expandible -->
            <div class="tipi-select-container" id="tipi-select-container">
                <div class="tipi-select-inner">
                    <select class="tipi-select" id="tipi-programa-select">
                        <option value="">-- Cargando... --</option>
                    </select>
                    <button class="tipi-btn-confirm" id="btn-confirmar-asignacion" onclick="confirmar_asignacion()">
                        Asignar
                    </button>
                </div>
            </div>

            <div class="tipi-divider"></div>

            <!-- Sección 2: Crear nuevo -->
            <p class="tipi-section-label">Crear nuevo itinerario</p>
            <div class="tipi-row">
                <div class="tipi-row-text">
                    <h4>Crear un nuevo programa</h4>
                    <p>Empieza desde cero; el itinerario quedará ligado a este lead automáticamente.</p>
                </div>
                <button class="tipi-btn" onclick="ir_crear_itinerario()">
                    Crear
                </button>
            </div>

            <div class="tipi-divider"></div>

            <!-- Sección 3: Biblioteca -->
            <div class="tipi-row">
                <div class="tipi-row-text">
                    <h4>Biblioteca de contenido</h4>
                    <p>Administra tus días, actividades y alojamientos reutilizables.</p>
                </div>
                <button class="tipi-btn" onclick="window.open(`${APP_URL}/biblioteca`, '_blank')">
                    Biblioteca
                </button>
            </div>

        </div>
    </div>
</div>

<!-- Modal Templates -->
<div class="tpl-overlay" id="tpl-overlay" onclick="cerrar_modal_templates(event)">
    <div class="tpl-modal">
        <div class="tpl-modal-header">
            <h2>Templates</h2>
            <input type="text" id="tpl-search" placeholder="Buscar un template..." oninput="filtrar_templates()">
            <button class="tpl-create-btn" onclick="abrir_form_template()">Crear template</button>
            <button class="tpl-close" onclick="cerrar_modal_templates()">&times;</button>
        </div>

        <div class="tpl-list" id="tpl-list"></div>

        <!-- Formulario de creación/edición (oculto por defecto) -->
        <div class="tpl-form" id="tpl-form" style="display:none">
            <span class="tpl-form-title" id="tpl-form-title">Nuevo template</span>
            <input type="hidden" id="tpl-edit-id" value="">
            <input type="text" id="tpl-nombre" placeholder="Nombre del template">
            <textarea id="tpl-texto" placeholder="Contenido del mensaje..."></textarea>
            <div class="tpl-form-actions">
                <button type="button" class="tpl-cancel-btn" onclick="cancelar_form_template()">Cancelar</button>
                <button type="button" onclick="guardar_template()">Guardar template</button>
            </div>
        </div>
    </div>
</div>