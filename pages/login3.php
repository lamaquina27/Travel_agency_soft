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

// Imagen editable para el lado visual del login. Cambia esta ruta cuando subas tu imagen.
$loginHeroImage = APP_URL . '/assets/uploads/hero_login.jpg';
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
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .hero-experience-card {
            width: min(100%, 640px);
            min-height: 560px;
            position: relative;
            overflow: hidden;
            border-radius: 34px;
            background:
                linear-gradient(135deg, rgba(255,255,255,0.14), rgba(255,255,255,0.04)),
                radial-gradient(circle at 18% 18%, rgba(255,255,255,0.16), transparent 28%),
                linear-gradient(135deg, var(--brand-secondary), var(--login-bg));
            box-shadow: 0 34px 90px rgba(0, 0, 0, 0.32);
            isolation: isolate;
        }

        .hero-experience-card::before,
        .hero-experience-card::after {
            content: '';
            position: absolute;
            border-radius: 999px;
            filter: blur(4px);
            opacity: 0.72;
            z-index: 1;
        }

        .hero-experience-card::before {
            width: 320px;
            height: 320px;
            right: -92px;
            top: -86px;
            background: color-mix(in srgb, var(--brand-primary) 76%, white 24%);
        }

        .hero-experience-card::after {
            width: 260px;
            height: 260px;
            left: -84px;
            bottom: -80px;
            background: rgba(255, 255, 255, 0.16);
        }

        .hero-photo {
            position: absolute;
            inset: 34px 34px 168px 34px;
            border-radius: 28px;
            overflow: hidden;
            z-index: 2;
            background:
                linear-gradient(135deg, rgba(255,255,255,0.18), rgba(255,255,255,0.04)),
                radial-gradient(circle at 30% 20%, var(--brand-primary), transparent 34%),
                radial-gradient(circle at 82% 74%, var(--brand-secondary), transparent 34%);
            box-shadow: 0 18px 46px rgba(0, 0, 0, 0.22);
        }

        .hero-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .hero-photo img[src=''],
        .hero-photo img:not([src]) {
            display: none;
        }

        .hero-map-lines {
            position: absolute;
            inset: 0;
            z-index: 3;
            pointer-events: none;
            opacity: 0.82;
        }

        .hero-content {
            position: absolute;
            left: 38px;
            right: 38px;
            bottom: 36px;
            z-index: 4;
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 26px;
            align-items: end;
        }

        .hero-title {
            font-size: clamp(38px, 4.6vw, 64px);
            line-height: 0.9;
            font-weight: 950;
            letter-spacing: -2.5px;
            color: var(--white);
            max-width: 410px;
        }

        .hero-title span {
            color: var(--white);
        }

        .hero-caption {
            margin-top: 16px;
            max-width: 360px;
            color: rgba(255,255,255,0.78);
            font-size: 15px;
            line-height: 1.45;
        }

        .hero-pill {
            min-width: 132px;
            padding: 14px 16px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.18);
            backdrop-filter: blur(14px);
            color: var(--white);
            text-align: center;
            box-shadow: 0 18px 40px rgba(0,0,0,0.16);
        }

        .hero-pill strong {
            display: block;
            font-size: 24px;
            line-height: 1;
            color: var(--brand-primary);
        }

        .hero-pill span {
            display: block;
            margin-top: 6px;
            font-size: 11px;
            line-height: 1.25;
            font-weight: 800;
            letter-spacing: 0.7px;
            text-transform: uppercase;
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
                min-height: 500px;
                max-width: 620px;
                width: 100%;
                margin: 0 auto;
            }

            .hero-experience-card {
                min-height: 480px;
            }

            .hero-photo {
                inset: 28px 28px 150px 28px;
            }

            .hero-content {
                left: 30px;
                right: 30px;
                bottom: 30px;
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

        <section class="visual-panel" aria-hidden="true">
            <div class="hero-experience-card">
                <div class="hero-photo">
                    <img src="<?= htmlspecialchars($loginHeroImage) ?>" alt="" onerror="this.style.display='none';">
                </div>

                <svg class="hero-map-lines" viewBox="0 0 640 560" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M82 374C170 246 252 446 362 302C438 203 505 256 570 146" stroke="white" stroke-width="2" stroke-linecap="round" stroke-dasharray="10 12" opacity="0.48"/>
                    <circle cx="82" cy="374" r="9" fill="white" opacity="0.82"/>
                    <circle cx="362" cy="302" r="9" fill="white" opacity="0.82"/>
                    <circle cx="570" cy="146" r="9" fill="white" opacity="0.82"/>
                    <path d="M464 104l40 16-34 26 6-26-12-16Z" fill="currentColor" opacity="0.92"/>
                </svg>

                <div class="hero-content">
                    <div>
                        <h2 class="hero-title"><span data-translate="visual_word">Crea</span><br><span data-translate="visual_copy">viajes memorables.</span></h2>
                        <p class="hero-caption" data-translate="visual_caption">Gestiona itinerarios, clientes y experiencias desde una plataforma diseñada para agencias modernas.</p>
                    </div>
                    <div class="hero-pill">
                        <strong>360°</strong>
                        <span data-translate="visual_badge">Travel Suite</span>
                    </div>
                </div>
            </div>
        </section>
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
                visual_word: 'Crea',
                visual_copy: 'viajes memorables.',
                visual_caption: 'Gestiona itinerarios, clientes y experiencias desde una plataforma diseñada para agencias modernas.',
                visual_badge: 'Travel Suite',
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
                visual_word: 'Create',
                visual_copy: 'memorable trips.',
                visual_caption: 'Manage itineraries, clients, and experiences from a platform designed for modern agencies.',
                visual_badge: 'Travel Suite',
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
                visual_word: 'Créez',
                visual_copy: 'des voyages mémorables.',
                visual_caption: 'Gérez les itinéraires, clients et expériences depuis une plateforme conçue pour les agences modernes.',
                visual_badge: 'Travel Suite',
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
                visual_word: 'Crie',
                visual_copy: 'viagens memoráveis.',
                visual_caption: 'Gerencie roteiros, clientes e experiências em uma plataforma criada para agências modernas.',
                visual_badge: 'Travel Suite',
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
