<?php
// =====================================
// ARCHIVO: pages/perfil.php - Perfil del Agente con UIComponents
// =====================================

App::requireLogin();

// Solo permitir acceso a agentes
$user = App::getUser();

// Incluir constantes necesarias
require_once __DIR__ . '/../config/constants.php';

// Incluir sistema de componentes UI (igual que dashboard)
require_once __DIR__ . '/../includes/ui_components.php';

// Obtener configuración
$userColors = App::getColorsForRole($user['role']);
$companyName = App::getCompanyName();
$defaultLanguage = App::getDefaultLanguage();

// Obtener información completa del usuario desde la base de datos
try {
    $db = Database::getInstance();
    $userInfo = $db->fetch(
        "SELECT id, username, email, full_name, role, active, last_login, created_at, updated_at
         FROM users WHERE id = ?",
        [$user['id']]
    );

    if (!$userInfo) {
        throw new Exception('Usuario no encontrado');
    }

    $gmailAccount = $db->fetch(
        "SELECT id, email, status FROM email_accounts WHERE user_id = ? AND provider = 'gmail' ORDER BY id DESC LIMIT 1",
        [$user['id']]
    );
} catch (Exception $e) {
    App::redirect('/dashboard');
    exit;
}

$flashSuccess = $_SESSION['flash_success'] ?? null; unset($_SESSION['flash_success']);
$flashError   = $_SESSION['flash_error']   ?? null; unset($_SESSION['flash_error']);
?>
<!DOCTYPE html>
<html lang="<?= $defaultLanguage ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - <?= htmlspecialchars($companyName) ?></title>

    <!-- Incluir estilos de componentes UI (igual que dashboard) -->
    <?= UIComponents::getComponentStyles() ?>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /*iframe{
            display: none;
        }*/
        .VIpgJd-ZVi9od-ORHb-OEVmcd {
            left: 0;
            display: none !important;
            top: 0;
        }

        :root {
            --primary-color:
                <?= $userColors['primary'] ?>
            ;
            --secondary-color:
                <?= $userColors['secondary'] ?>
            ;
            --primary-gradient: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            color: #333;
            min-height: 100vh;
            line-height: 1.6;
        }

        /* Header styles - copiados de biblioteca.php */
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

        .header-center {
            display: flex;
            align-items: center;
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

        /* Google Translate styles - copiados de biblioteca.php */
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

        .goog-te-gadget-icon {
            display: none !important;
        }

        .goog-te-gadget-simple {
            background: transparent !important;
            border: none !important;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
        }

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

        .VIpgJd-ZVi9od-xl07Ob-lTBxed span:first-child {
            color: inherit !important;
            font-weight: inherit !important;
        }

        .VIpgJd-ZVi9od-xl07Ob-lTBxed img {
            display: none !important;
        }

        .VIpgJd-ZVi9od-xl07Ob-lTBxed span[style*="border-left"] {
            display: none !important;
        }

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

        .goog-te-banner-frame.skiptranslate {
            display: none !important;
        }

        body {
            top: 0px !important;
        }

        .goog-te-gadget img {
            vertical-align: middle;
            border: none;
            display: none;
        }

        /* Main content - igual estructura que dashboard */
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

        .content-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 30px;
            max-width: 800px;
            margin: 0 auto;
        }

        /* Cards */
        .card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
        }

        .card h2 {
            color: var(--primary-color);
            font-size: 1.4rem;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 15px;
        }

        /* Info Grid */
        .info-grid {
            display: grid;
            gap: 20px;
        }

        .info-field {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .info-label {
            font-weight: 600;
            color: #64748b;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-value {
            padding: 12px 16px;
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            color: #334155;
            font-weight: 500;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            color: white;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            width: fit-content;
        }

        .badge-role {
            background: var(--primary-gradient);
        }

        .badge-status {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        /* Forms */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
            font-size: 0.95rem;
        }

        .password-container {
            position: relative;
        }

        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            font-size: 18px;
            color: #6b7280;
            padding: 5px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .password-toggle:hover {
            background: #f3f4f6;
            color: var(--primary-color);
        }

        /* Password requirements */
        .password-help {
            margin-top: 12px;
            padding: 16px;
            background: #f8fafc;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
        }

        .requirement {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .requirement:last-child {
            margin-bottom: 0;
        }

        .requirement.invalid {
            color: #dc2626;
        }

        .requirement.valid {
            color: #059669;
        }

        .requirement.invalid:before {
            content: "✗";
            color: #dc2626;
            font-weight: bold;
        }

        .requirement.valid:before {
            content: "✓";
            color: #059669;
            font-weight: bold;
        }

        /* Buttons */
        .btn-primary {
            background: var(--primary-gradient);
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            justify-content: center;
            width: 100%;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        /* Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
        }

        .alert-success {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .info-note {
            background: #f0fdfa;
            border: 1px solid #5eead4;
            color: #0f766e;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        /* Overlay - usando mismo estilo que dashboard */
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

            .header-left h2 {
                display: none;
            }

            .header-center {
                position: absolute;
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

            .main-content {
                padding: 20px;
                margin-top: 60px;
            }

            .main-content.sidebar-open {
                margin-left: 0;
            }

            .content-grid {
                gap: 20px;
            }

            .card {
                padding: 20px;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .user-info div:last-child {
                display: none;
            }
        }
    </style>
</head>

<body>
    <!-- Header usando UIComponents (igual que dashboard) -->
    <?= UIComponents::renderHeader($user) ?>

    <!-- Sidebar usando UIComponents (igual que dashboard) -->
    <?= UIComponents::renderSidebar($user, '/perfil') ?>

    <!-- Overlay -->
    <div class="overlay" id="overlay" onclick="closeSidebar()"></div>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Mensajes flash (OAuth redirect) -->
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

        <!-- Mensajes de respuesta -->
        <div id="messageContainer"></div>

        <div class="content-grid">
            <!-- Información Personal -->
            <div class="card">
                <h2>📋 Información Personal</h2>

                <div class="info-note">
                    💡 <strong>Información de solo lectura:</strong> Esta información no puede ser modificada. Para
                    cambios, contacte al administrador.
                </div>

                <div class="info-grid">
                    <div class="info-field">
                        <div class="info-label">👤 Nombre de Usuario</div>
                        <div class="info-value"><?= htmlspecialchars($userInfo['username']) ?></div>
                    </div>

                    <div class="info-field">
                        <div class="info-label">🏷️ Nombre Completo</div>
                        <div class="info-value"><?= htmlspecialchars($userInfo['full_name']) ?></div>
                    </div>

                    <div class="info-field">
                        <div class="info-label">✉️ Correo Electrónico</div>
                        <div class="info-value"><?= htmlspecialchars($userInfo['email']) ?></div>
                    </div>

                    <div class="info-field">
                        <div class="info-label">🎭 Rol del Sistema</div>
                        <div>
                            <span class="badge badge-role">
                                ✈️ Agente de Viajes
                            </span>
                        </div>
                    </div>

                    <div class="info-field">
                        <div class="info-label">📊 Estado de la Cuenta</div>
                        <div>
                            <span class="badge badge-status">
                                ✅ Activa
                            </span>
                        </div>
                    </div>

                    <div class="info-field">
                        <div class="info-label">🕐 Último Acceso</div>
                        <div class="info-value">
                            <?= $userInfo['last_login'] ? date('d/m/Y H:i', strtotime($userInfo['last_login'])) : 'Primer acceso' ?>
                        </div>
                    </div>

                    <div class="info-field">
                        <div class="info-label">📅 Miembro Desde</div>
                        <div class="info-value">
                            <?= date('d/m/Y', strtotime($userInfo['created_at'])) ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cambio de Contraseña -->
            <div class="card">
                <h2>🔒 Cambiar Contraseña</h2>

                <form id="passwordForm">
                    <div class="form-group">
                        <label for="current_password">🔐 Contraseña Actual *</label>
                        <div class="password-container">
                            <input type="password" id="current_password" name="current_password" required
                                placeholder="Ingrese su contraseña actual">
                            <button type="button" class="password-toggle"
                                onclick="togglePassword('current_password')">👁️</button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="new_password">🆕 Nueva Contraseña *</label>
                        <div class="password-container">
                            <input type="password" id="new_password" name="new_password" required
                                placeholder="Ingrese su nueva contraseña">
                            <button type="button" class="password-toggle"
                                onclick="togglePassword('new_password')">👁️</button>
                        </div>
                        <div class="password-help">
                            <div class="requirement invalid" id="req-length">Mínimo 8 caracteres</div>
                            <div class="requirement invalid" id="req-upper">Una letra mayúscula (A-Z)</div>
                            <div class="requirement invalid" id="req-lower">Una letra minúscula (a-z)</div>
                            <div class="requirement invalid" id="req-number">Un número (0-9)</div>
                            <div class="requirement invalid" id="req-special">Un carácter especial (!@#$%^&*)</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">✅ Confirmar Nueva Contraseña *</label>
                        <div class="password-container">
                            <input type="password" id="confirm_password" name="confirm_password" required
                                placeholder="Confirme su nueva contraseña">
                            <button type="button" class="password-toggle"
                                onclick="togglePassword('confirm_password')">👁️</button>
                        </div>
                    </div>

                    <button type="submit" class="btn-primary" id="passwordSubmitBtn">
                        🔒 Cambiar Contraseña
                    </button>
                </form>
            </div>

            <!-- Cuenta Gmail -->
            <div class="card">
                <h2>✉️ Cuenta Gmail</h2>
                <p style="font-size:14px;color:#64748b;margin-bottom:20px;line-height:1.6;">
                    Conecta tu cuenta de Gmail para enviar correos directamente desde el pipeline de ventas. Cada asesor conecta su propia cuenta.
                </p>

                <?php if ($gmailAccount && $gmailAccount['status'] === 'active'): ?>
                <div style="display:flex;align-items:center;justify-content:space-between;background:#f0fdf4;border:1px solid #86efac;border-radius:12px;padding:16px 20px;flex-wrap:wrap;gap:12px;">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div style="width:38px;height:38px;background:#dcfce7;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:18px;">✉️</div>
                        <div>
                            <div style="font-size:13px;font-weight:700;color:#166534;">Gmail conectado</div>
                            <div style="font-size:14px;color:#15803d;"><?= htmlspecialchars($gmailAccount['email']) ?></div>
                        </div>
                    </div>
                    <a href="<?= APP_URL ?>/gmail/oauth?action=disconnect"
                       onclick="return confirm('¿Desconectar esta cuenta de Gmail?')"
                       style="display:inline-flex;align-items:center;gap:6px;padding:9px 16px;background:#fee2e2;color:#dc2626;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;transition:background .15s;"
                       onmouseover="this.style.background='#fecaca'" onmouseout="this.style.background='#fee2e2'">
                        Desconectar
                    </a>
                </div>
                <?php else: ?>
                <div style="display:flex;align-items:center;justify-content:space-between;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:16px 20px;flex-wrap:wrap;gap:12px;">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div style="width:38px;height:38px;background:#f1f5f9;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:18px;">✉️</div>
                        <div>
                            <div style="font-size:13px;font-weight:700;color:#475569;">Sin cuenta Gmail</div>
                            <div style="font-size:13px;color:#94a3b8;">Conecta tu cuenta para enviar correos desde el pipeline</div>
                        </div>
                    </div>
                    <a href="<?= APP_URL ?>/gmail/oauth?action=connect"
                       style="display:inline-flex;align-items:center;gap:6px;padding:9px 18px;background:var(--primary-gradient);color:#fff;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;box-shadow:0 2px 8px rgba(0,0,0,.12);">
                        Conectar Gmail
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        const APP_URL = '<?= APP_URL ?>';
        let sidebarOpen = false;

        // Inicialización
        document.addEventListener('DOMContentLoaded', function () {
            console.log('Perfil cargado con UIComponents');
            initializePasswordValidation();
            initializeGoogleTranslate();
        });

        // Funciones de sidebar - usando mismas funciones que dashboard/biblioteca
        function toggleSidebar() {
            const sidebar = document.querySelector('.enhanced-sidebar');
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
                window.location.href = APP_URL + '/auth/logout';
            }
        }

        // Inicializar validación de contraseña
        function initializePasswordValidation() {
            const newPasswordInput = document.getElementById('new_password');
            if (newPasswordInput) {
                newPasswordInput.addEventListener('input', function () {
                    validatePasswordRealTime(this.value);
                });
            }

            // Form submit
            const passwordForm = document.getElementById('passwordForm');
            if (passwordForm) {
                passwordForm.addEventListener('submit', handlePasswordChange);
            }
        }

        // Validación de contraseña en tiempo real
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
                    element.className = requirements[reqId] ? 'requirement valid' : 'requirement invalid';
                }
            });

            return Object.values(requirements).every(req => req);
        }

        // Manejar cambio de contraseña
        async function handlePasswordChange(e) {
            e.preventDefault();

            const currentPassword = document.getElementById('current_password').value;
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const submitBtn = document.getElementById('passwordSubmitBtn');

            // Validaciones
            if (!currentPassword || !newPassword || !confirmPassword) {
                showMessage('❌ Todos los campos son obligatorios', 'error');
                return;
            }

            if (!validatePasswordRealTime(newPassword)) {
                showMessage('❌ La nueva contraseña no cumple con los requisitos de seguridad', 'error');
                return;
            }

            if (newPassword !== confirmPassword) {
                showMessage('❌ Las contraseñas no coinciden', 'error');
                return;
            }

            if (currentPassword === newPassword) {
                showMessage('❌ La nueva contraseña debe ser diferente a la actual', 'error');
                return;
            }

            try {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '⏳ Cambiando contraseña...';

                const response = await fetch(APP_URL + '/perfil/api', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        action: 'change_password',
                        current_password: currentPassword,
                        new_password: newPassword
                    })
                });

                const responseText = await response.text();
                console.log('Respuesta del servidor:', responseText);

                let data;
                try {
                    data = JSON.parse(responseText);
                } catch (jsonError) {
                    console.error('Error parsing JSON:', jsonError);
                    throw new Error('Respuesta del servidor no válida');
                }

                if (data.success) {
                    showMessage('✅ Contraseña cambiada correctamente', 'success');
                    document.getElementById('passwordForm').reset();
                    // Reset password requirements display
                    document.querySelectorAll('.requirement').forEach(req => {
                        req.className = 'requirement invalid';
                    });
                } else {
                    throw new Error(data.message || 'Error al cambiar contraseña');
                }

            } catch (error) {
                console.error('Error:', error);
                showMessage('❌ Error al cambiar contraseña: ' + error.message, 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '🔒 Cambiar Contraseña';
            }
        }

        // Toggle password visibility
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const button = input.parentNode.querySelector('.password-toggle');

            if (input.type === 'password') {
                input.type = 'text';
                button.innerHTML = '🙈';
            } else {
                input.type = 'password';
                button.innerHTML = '👁️';
            }
        }

        // Mostrar mensajes
        function showMessage(message, type) {
            const container = document.getElementById('messageContainer');

            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.innerHTML = message;

            container.innerHTML = '';
            container.appendChild(alert);

            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.remove();
                }
            }, 5000);

            // Scroll to top to show message
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Google Translate (copiado de biblioteca.php)
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
                document.head.appendChild(script);
            }
        }
    </script>
</body>

</html>