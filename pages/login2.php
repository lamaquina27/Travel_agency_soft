<?php
// =====================================
// ARCHIVO: pages/login.php - Login estilo Travel Suite con identidad de marca
// =====================================

require_once 'config/config_functions.php';

$companyName = App::getCompanyName();
$logo = App::getLogo();
$defaultLanguage = App::getDefaultLanguage();

// Colores de marca actuales del sistema
$primaryColor = '#2196F3';
$secondaryColor = '#1976D2';
$backgroundColor = '#061f2f';
$textColor = '#ffffff';

// Imágenes editables para el login. Puedes cambiar estas rutas cuando subas tus fotos.
$loginHeroMain = APP_URL . '/assets/uploads/login/hero-main.jpg';
$loginHeroTop = APP_URL . '/assets/uploads/login/hero-top.jpg';
$loginHeroSide = APP_URL . '/assets/uploads/login/hero-side.jpg';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($defaultLanguage) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso - <?= htmlspecialchars($companyName) ?></title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --brand-primary: <?= $primaryColor ?>;
            --brand-secondary: <?= $secondaryColor ?>;
            --login-bg: <?= $backgroundColor ?>;
            --login-text: <?= $textColor ?>;
            --white: #ffffff;
            --soft-white: #f7fafc;
            --muted: rgba(255, 255, 255, 0.78);
            --danger-bg: #fee2e2;
            --danger-text: #991b1b;
            --success-bg: #d1fae5;
            --success-text: #065f46;
            --warning-bg: #fef3c7;
            --warning-text: #92400e;
        }

        html,
        body {
            min-height: 100%;
        }

        body {
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--login-bg);
            color: var(--login-text);
            overflow-x: hidden;
        }

        .login-page {
            min-height: 100vh;
            display: grid;
            grid-template-columns: minmax(360px, 42%) minmax(420px, 58%);
            align-items: center;
            gap: 32px;
            padding: 56px clamp(28px, 7vw, 110px);
            position: relative;
        }

        .login-page::before {
            content: '';
            position: fixed;
            inset: 0;
            pointer-events: none;
            background:
                radial-gradient(circle at 18% 18%, rgba(255, 255, 255, 0.07), transparent 25%),
                radial-gradient(circle at 82% 70%, rgba(255, 255, 255, 0.05), transparent 28%);
        }

        .login-panel {
            width: 100%;
            max-width: 430px;
            position: relative;
            z-index: 2;
        }

        .company-brand {
            margin-bottom: 44px;
        }

        .brand-logo {
            max-width: 260px;
            max-height: 110px;
            width: auto;
            height: auto;
            object-fit: contain;
            display: block;
        }

        .brand-name {
            font-size: clamp(42px, 6vw, 76px);
            line-height: 0.92;
            font-weight: 900;
            letter-spacing: -4px;
            color: var(--brand-primary);
            text-transform: lowercase;
        }

        .brand-suite {
            margin-top: 10px;
            font-size: 18px;
            line-height: 1;
            font-weight: 800;
            letter-spacing: 4px;
            color: var(--brand-primary);
            text-transform: uppercase;
        }

        .form-title {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .login-form {
            width: 100%;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            position: absolute;
            width: 1px;
            height: 1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
        }

        .form-group input {
            width: 100%;
            height: 48px;
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 4px;
            padding: 0 16px;
            font-size: 16px;
            background: var(--soft-white);
            color: #1f2937;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
        }

        .form-group input:focus {
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.16);
            transform: translateY(-1px);
        }

        .form-group input::placeholder {
            color: #475569;
        }

        .login-btn {
            width: 100%;
            height: 48px;
            border: none;
            border-radius: 4px;
            background: var(--brand-primary);
            color: #071724;
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
            transition: transform 0.2s ease, filter 0.2s ease, box-shadow 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .login-btn:hover {
            filter: brightness(1.04);
            transform: translateY(-1px);
            box-shadow: 0 14px 28px rgba(0, 0, 0, 0.22);
        }

        .login-btn:disabled {
            opacity: 0.75;
            cursor: not-allowed;
            transform: none;
        }

        .loading {
            display: none;
            width: 17px;
            height: 17px;
            border: 3px solid rgba(0, 0, 0, 0.18);
            border-top-color: rgba(0, 0, 0, 0.72);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        .login-btn.loading .loading {
            display: inline-block;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .language-selector {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin: 22px 0 18px;
        }

        .language-btn {
            border: 1px solid rgba(255, 255, 255, 0.22);
            background: rgba(255, 255, 255, 0.08);
            color: var(--white);
            border-radius: 999px;
            padding: 8px 12px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s ease, color 0.2s ease, transform 0.2s ease;
        }

        .language-btn:hover,
        .language-btn.active {
            background: var(--brand-primary);
            color: #071724;
            transform: translateY(-1px);
        }

        .forgotten-password {
            display: inline-block;
            margin-top: 14px;
            color: var(--brand-primary);
            font-size: 14px;
            font-weight: 800;
            text-decoration: underline;
        }

        .message {
            margin-bottom: 16px;
            padding: 13px 15px;
            border-radius: 8px;
            font-size: 14px;
            line-height: 1.45;
        }

        .session-expired {
            background: var(--warning-bg);
            color: var(--warning-text);
        }

        .error-message {
            margin-top: 16px;
            background: var(--danger-bg);
            color: var(--danger-text);
        }

        .success-message {
            margin-top: 16px;
            background: var(--success-bg);
            color: var(--success-text);
        }

        .form-footer {
            margin-top: 26px;
            font-size: 12px;
            color: var(--muted);
        }

        .visual-panel {
            min-height: 620px;
            position: relative;
            z-index: 1;
        }

        .photo-card {
            position: absolute;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 24px 70px rgba(0, 0, 0, 0.28);
        }

        .photo-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .photo-card.main {
            width: min(48vw, 520px);
            height: 342px;
            left: 7%;
            bottom: 0;
        }

        .photo-card.top {
            width: 172px;
            height: 260px;
            left: 26%;
            top: 0;
        }

        .photo-card.side {
            width: 248px;
            height: 372px;
            right: 0;
            top: 116px;
        }

        .fallback-art {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background:
                linear-gradient(135deg, rgba(255,255,255,0.16), rgba(255,255,255,0.03)),
                radial-gradient(circle at 24% 24%, var(--brand-primary), transparent 34%),
                radial-gradient(circle at 74% 68%, var(--brand-secondary), transparent 30%);
        }

        .fallback-art svg {
            width: 72%;
            max-width: 290px;
            opacity: 0.92;
        }

        .visual-copy {
            position: absolute;
            left: 17%;
            bottom: 118px;
            z-index: 3;
            max-width: 340px;
            font-size: clamp(34px, 4vw, 54px);
            line-height: 0.92;
            font-weight: 950;
            letter-spacing: -2px;
            color: var(--brand-primary);
            text-transform: uppercase;
            text-shadow: 0 6px 28px rgba(0, 0, 0, 0.35);
        }

        .visual-badge {
            position: absolute;
            right: 8%;
            bottom: 44px;
            z-index: 4;
            padding: 12px 16px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(10px);
            color: var(--white);
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 0.5px;
        }

        @media (max-width: 980px) {
            body {
                overflow-y: auto;
            }

            .login-page {
                grid-template-columns: 1fr;
                padding: 36px 24px;
            }

            .login-panel {
                max-width: 520px;
                margin: 0 auto;
            }

            .visual-panel {
                min-height: 420px;
                max-width: 620px;
                width: 100%;
                margin: 0 auto;
            }

            .photo-card.main {
                width: 78%;
                height: 290px;
                left: 0;
            }

            .photo-card.top {
                width: 148px;
                height: 210px;
                left: 12%;
            }

            .photo-card.side {
                width: 220px;
                height: 300px;
                right: 0;
                top: 70px;
            }
        }

        @media (max-width: 620px) {
            .login-page {
                padding: 30px 18px;
            }

            .company-brand {
                margin-bottom: 32px;
            }

            .brand-logo {
                max-width: 210px;
            }

            .brand-name {
                font-size: 42px;
                letter-spacing: -2px;
            }

            .brand-suite {
                font-size: 13px;
                letter-spacing: 2px;
            }

            .language-selector {
                gap: 6px;
            }

            .language-btn {
                padding: 7px 10px;
                font-size: 11px;
            }

            .visual-panel {
                display: none;
            }
        }

        .login-visual {
            position: relative;
            width: 50%;
            height: 100%;
            background: url('<?= APP_URL ?>/assets/uploads/login/hero.jpg') center/cover no-repeat;
            display: flex;
            align-items: flex-end;
            padding: 60px;
            overflow: hidden;
        }

        /* Overlay elegante */
        .visual-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(
                180deg,
                rgba(0,0,0,0.1) 0%,
                rgba(0,0,0,0.6) 100%
            );
        }

        /* Contenido */
        .visual-content {
            position: relative;
            color: #fff;
            max-width: 400px;
        }

        .visual-content h2 {
            font-size: 36px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .visual-content p {
            font-size: 16px;
            opacity: 0.85;
        }
    </style>
</head>
<body>
    <main class="login-page">
        <section class="login-panel">
            <div class="company-brand">
                <?php if (!empty($logo)): ?>
                    <img src="<?= htmlspecialchars($logo) ?>" alt="<?= htmlspecialchars($companyName) ?>" class="brand-logo">
                <?php else: ?>
                    <div class="brand-name"><?= htmlspecialchars($companyName) ?></div>
                    <div class="brand-suite">Travel Suite</div>
                <?php endif; ?>
            </div>

            <form action="<?= APP_URL ?>/auth/login" method="POST" id="loginForm" class="login-form">
                <h1 class="form-title" data-translate="form_title">Por favor inicia sesión</h1>

                <?php if (isset($_SESSION['session_expired'])): ?>
                    <div class="message session-expired">
                        <span data-translate="session_expired">Tu sesión ha expirado por inactividad. Por favor, inicia sesión nuevamente.</span>
                    </div>
                    <?php unset($_SESSION['session_expired']); ?>
                <?php endif; ?>

                <div class="form-group">
                    <label for="email" data-translate="email_label">Correo electrónico</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        required
                        placeholder="Email"
                        data-translate-placeholder="email_placeholder"
                        autocomplete="email"
                        pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"
                        title="Ingrese un correo electrónico válido"
                        autofocus>
                </div>

                <div class="form-group">
                    <label for="password" data-translate="password_label">Contraseña</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        placeholder="Password"
                        data-translate-placeholder="password_placeholder"
                        autocomplete="current-password">
                </div>

                <button type="submit" class="login-btn" id="loginBtn">
                    <span data-translate="login_button">Iniciar sesión</span>
                    <span class="loading" id="loading" aria-hidden="true"></span>
                </button>

                <div class="language-selector" aria-label="Selector de idioma">
                    <button type="button" class="language-btn active" data-lang="es" onclick="changeLanguage('es')">ES</button>
                    <button type="button" class="language-btn" data-lang="en" onclick="changeLanguage('en')">EN</button>
                    <button type="button" class="language-btn" data-lang="fr" onclick="changeLanguage('fr')">FR</button>
                    <button type="button" class="language-btn" data-lang="pt" onclick="changeLanguage('pt')">PT</button>
                </div>

                <a href="<?= APP_URL ?>/auth/forgot-password" class="forgotten-password" data-translate="forgotten_password">¿Olvidaste tu contraseña?</a>

                <?php if (isset($_SESSION['error'])):
                    $errorMsg = $_SESSION['error'];
                    preg_match('/\((\d{2}\/\d{2}\/\d{4})\)/', $errorMsg, $matches);
                    $errorDate = isset($matches[1]) ? $matches[1] : '';
                ?>
                    <div class="message error-message" data-error-date="<?= htmlspecialchars($errorDate) ?>">
                        🚫 <span class="error-text"><?= htmlspecialchars($errorMsg) ?></span>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="message success-message">
                        ✅ <?= htmlspecialchars($_SESSION['success']) ?>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>
            </form>

            <footer class="form-footer">
                <p>© <?= date('Y') ?> <?= htmlspecialchars($companyName) ?>. <span data-translate="rights">Todos los derechos reservados</span>.</p>
            </footer>
        </section>

        <div class="login-visual">
            <div class="visual-overlay"></div>

            <div class="visual-content">
                <h2><?= __('Explora sin límites') ?></h2>
                <p><?= __('Construye experiencias únicas con tu plataforma') ?></p>
            </div>
        </div>
    </main>

    <script>
        const translations = {
            es: {
                form_title: 'Por favor inicia sesión',
                email_label: 'Correo electrónico',
                email_placeholder: 'Email',
                password_label: 'Contraseña',
                password_placeholder: 'Password',
                login_button: 'Iniciar sesión',
                forgotten_password: '¿Olvidaste tu contraseña?',
                session_expired: 'Tu sesión ha expirado por inactividad. Por favor, inicia sesión nuevamente.',
                rights: 'Todos los derechos reservados',
                visual_copy: 'Diseña viajes memorables.',
                subscription_expired: 'La suscripción de su agencia ha expirado',
                contact_admin: 'Contacte al administrador para renovarla',
                agency_inactive: 'Su agencia ha sido desactivada. Contacte al administrador',
                subscription_not_active: 'La suscripción de su agencia no está activa. Contacte al administrador',
                agency_not_found: 'Agencia no encontrada. Contacte al administrador'
            },
            en: {
                form_title: 'Please log in',
                email_label: 'Email address',
                email_placeholder: 'Email',
                password_label: 'Password',
                password_placeholder: 'Password',
                login_button: 'Log in',
                forgotten_password: 'Forgotten password?',
                session_expired: 'Your session has expired due to inactivity. Please sign in again.',
                rights: 'All rights reserved',
                visual_copy: 'Design memorable trips.',
                subscription_expired: 'Your agency subscription has expired',
                contact_admin: 'Contact the administrator to renew it',
                agency_inactive: 'Your agency has been deactivated. Contact the administrator',
                subscription_not_active: 'Your agency subscription is not active. Contact the administrator',
                agency_not_found: 'Agency not found. Contact the administrator'
            },
            fr: {
                form_title: 'Veuillez vous connecter',
                email_label: 'Adresse e-mail',
                email_placeholder: 'Email',
                password_label: 'Mot de passe',
                password_placeholder: 'Password',
                login_button: 'Se connecter',
                forgotten_password: 'Mot de passe oublié ?',
                session_expired: 'Votre session a expiré en raison d\'inactivité. Veuillez vous reconnecter.',
                rights: 'Tous droits réservés',
                visual_copy: 'Créez des voyages mémorables.',
                subscription_expired: 'L\'abonnement de votre agence a expiré',
                contact_admin: 'Contactez l\'administrateur pour le renouveler',
                agency_inactive: 'Votre agence a été désactivée. Contactez l\'administrateur',
                subscription_not_active: 'L\'abonnement de votre agence n\'est pas actif. Contactez l\'administrateur',
                agency_not_found: 'Agence introuvable. Contactez l\'administrateur'
            },
            pt: {
                form_title: 'Por favor, faça login',
                email_label: 'Endereço de e-mail',
                email_placeholder: 'Email',
                password_label: 'Senha',
                password_placeholder: 'Password',
                login_button: 'Entrar',
                forgotten_password: 'Esqueceu sua senha?',
                session_expired: 'Sua sessão expirou devido à inatividade. Por favor, faça login novamente.',
                rights: 'Todos os direitos reservados',
                visual_copy: 'Crie viagens memoráveis.',
                subscription_expired: 'A assinatura de sua agência expirou',
                contact_admin: 'Entre em contato com o administrador para renová-la',
                agency_inactive: 'Sua agência foi desativada. Entre em contato com o administrador',
                subscription_not_active: 'A assinatura de sua agência não está ativa. Entre em contato com o administrador',
                agency_not_found: 'Agência não encontrada. Entre em contato com o administrador'
            }
        };

        let currentLanguage = localStorage.getItem('loginLanguage') || '<?= htmlspecialchars($defaultLanguage ?: 'es') ?>';
        if (!translations[currentLanguage]) currentLanguage = 'es';

        function applyTranslations(lang) {
            const trans = translations[lang];
            if (!trans) return;

            document.querySelectorAll('[data-translate]').forEach(element => {
                const key = element.getAttribute('data-translate');
                if (trans[key]) element.textContent = trans[key];
            });

            document.querySelectorAll('[data-translate-placeholder]').forEach(element => {
                const key = element.getAttribute('data-translate-placeholder');
                if (trans[key]) element.placeholder = trans[key];
            });
        }

        function translateErrorMessage(errorElement) {
            if (!errorElement) return;

            const errorTextElement = errorElement.querySelector('.error-text');
            if (!errorTextElement) return;

            const errorText = errorTextElement.textContent.trim();
            const trans = translations[currentLanguage];
            const errorDate = errorElement.getAttribute('data-error-date');

            if (errorText.includes('suscripción') && errorText.includes('expirado')) {
                errorTextElement.textContent = `${trans.subscription_expired}${errorDate ? ' (' + errorDate + ')' : ''}. ${trans.contact_admin}.`;
            } else if (errorText.includes('agencia') && errorText.includes('desactivada')) {
                errorTextElement.textContent = `${trans.agency_inactive}.`;
            } else if (errorText.includes('suscripción') && errorText.includes('no está activa')) {
                errorTextElement.textContent = `${trans.subscription_not_active}.`;
            } else if (errorText.includes('Agencia no encontrada')) {
                errorTextElement.textContent = `${trans.agency_not_found}.`;
            }
        }

        function changeLanguage(lang) {
            if (!translations[lang]) return;

            currentLanguage = lang;
            localStorage.setItem('loginLanguage', lang);

            document.querySelectorAll('.language-btn').forEach(btn => {
                btn.classList.toggle('active', btn.getAttribute('data-lang') === lang);
            });

            applyTranslations(lang);
            translateErrorMessage(document.querySelector('.error-message'));
        }

        document.addEventListener('DOMContentLoaded', function() {
            changeLanguage(currentLanguage);
        });

        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('loginBtn');
            btn.classList.add('loading');
            btn.disabled = true;
        });
    </script>
</body>
</html>
