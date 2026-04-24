<?php
// =====================================
// ARCHIVO: pages/login.php - Login Moderno para Agencia de Viajes
// =====================================

// Incluir ConfigManager para configuración
require_once 'config/config_functions.php';

// Obtener configuración básica de la empresa
$companyName = App::getCompanyName();
$logo = App::getLogo();
$defaultLanguage = App::getDefaultLanguage();

// COLORES ESTÁNDAR DEL SISTEMA
$primaryColor = '#2196F3'; // Azul moderno
$secondaryColor = '#1976D2'; // Azul más oscuro
?>
<!DOCTYPE html>
<html lang="<?= $defaultLanguage ?>">
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
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            height: 100vh;
            overflow: hidden;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Contenedor principal */
        .login-container {
            display: flex;
            width: 90%;
            max-width: 1100px;
            height: 600px;
            background: white;
            border-radius: 30px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }
        
        /* Panel izquierdo - Bienvenida */
        .welcome-panel {
            flex: 1;
            background: linear-gradient(135deg, <?= $primaryColor ?> 0%, <?= $secondaryColor ?> 100%);
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        /* Decoración circular */
        .welcome-panel::before {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            bottom: -150px;
            right: -150px;
        }
        
        .welcome-panel::after {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 50%;
            top: -100px;
            left: -100px;
        }
        
        .welcome-content {
            position: relative;
            z-index: 1;
        }
        
        .welcome-content h1 {
            font-size: 42px;
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .welcome-divider {
            width: 80px;
            height: 4px;
            background: white;
            margin: 0 auto 30px;
            border-radius: 2px;
        }
        
        .welcome-description {
            font-size: 17px;
            line-height: 1.8;
            opacity: 0.95;
            max-width: 400px;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        /* Panel derecho - Formulario */
        .form-panel {
            flex: 1;
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: white;
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 50px;
        }
        
        .company-logo {
            margin-bottom: 25px;
        }
        
        .company-logo img {
            max-width: 160px;
            height: auto;
        }
        
        .company-logo-text {
            font-size: 36px;
            font-weight: 700;
            color: #2d3748;
            letter-spacing: -0.5px;
            margin-bottom: 25px;
        }
        
        .form-title {
            font-size: 28px;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 10px;
        }
        
        .form-subtitle {
            font-size: 15px;
            color: #718096;
        }
        
        /* Selector de Idioma */
        .language-selector {
            display: flex;
            gap: 8px;
            justify-content: center;
            margin-bottom: 35px;
            flex-wrap: wrap;
        }
        
        .language-btn {
            background: rgba(33, 150, 243, 0.08);
            border: 2px solid transparent;
            border-radius: 10px;
            padding: 10px 16px;
            font-size: 13px;
            font-weight: 500;
            color: #4a5568;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .language-btn:hover {
            background: rgba(33, 150, 243, 0.15);
            color: <?= $primaryColor ?>;
            transform: translateY(-2px);
        }
        
        .language-btn.active {
            background: linear-gradient(135deg, <?= $primaryColor ?> 0%, <?= $secondaryColor ?> 100%);
            color: white;
            border-color: <?= $primaryColor ?>;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(33, 150, 243, 0.3);
        }
        
        .language-btn.active:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(33, 150, 243, 0.4);
        }
        
        /* Formulario */
        .login-form {
            width: 100%;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            font-weight: 500;
            color: #4a5568;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 16px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: #f7fafc;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: <?= $primaryColor ?>;
            background: white;
            box-shadow: 0 0 0 4px rgba(33, 150, 243, 0.1);
        }
        
        .form-group input::placeholder {
            color: #a0aec0;
        }
        
        /* Botón de login */
        .login-btn {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, <?= $primaryColor ?> 0%, <?= $secondaryColor ?> 100%);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 15px rgba(33, 150, 243, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(33, 150, 243, 0.4);
        }
        
        .login-btn:active {
            transform: translateY(0);
        }
        
        .login-btn.loading {
            pointer-events: none;
            opacity: 0.8;
        }
        
        .loading {
            display: none;
            width: 18px;
            height: 18px;
            border: 3px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-left: 10px;
            vertical-align: middle;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .login-btn.loading .loading {
            display: inline-block;
        }
        
        /* Mensajes */
        .session-expired {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-left: 4px solid #f59e0b;
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-size: 14px;
            color: #92400e;
            box-shadow: 0 2px 8px rgba(245, 158, 11, 0.15);
        }
        
        .error-message {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border-left: 4px solid #ef4444;
            padding: 16px 20px;
            border-radius: 12px;
            margin-top: 20px;
            font-size: 14px;
            color: #991b1b;
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.15);
        }
        
        .success-message {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border-left: 4px solid #10b981;
            padding: 16px 20px;
            border-radius: 12px;
            margin-top: 20px;
            font-size: 14px;
            color: #065f46;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.15);
        }
        
        /* Footer */
        .form-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            font-size: 13px;
            color: #718096;
        }
        
        /* Responsive */
        @media (max-width: 900px) {
            .login-container {
                flex-direction: column;
                height: auto;
                max-height: 95vh;
                overflow-y: auto;
            }
            
            .welcome-panel {
                padding: 40px 30px;
                min-height: 300px;
            }
            
            .welcome-content h1 {
                font-size: 32px;
            }
            
            .welcome-description {
                font-size: 15px;
            }
            
            .form-panel {
                padding: 40px 30px;
            }
            
            .language-selector {
                gap: 6px;
            }
            
            .language-btn {
                padding: 8px 12px;
                font-size: 12px;
            }
        }
        
        @media (max-width: 600px) {
            .login-container {
                width: 95%;
                border-radius: 20px;
            }
            
            .welcome-panel {
                padding: 30px 20px;
                min-height: 250px;
            }
            
            .welcome-content h1 {
                font-size: 28px;
            }
            
            .form-panel {
                padding: 30px 20px;
            }
            
            .form-title {
                font-size: 24px;
            }
            
            .company-logo-text {
                font-size: 28px;
            }
            
            .language-selector {
                gap: 4px;
            }
            
            .language-btn {
                padding: 8px 10px;
                font-size: 11px;
                gap: 4px;
            }
        }


        .company-brand {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 14px;
            margin-bottom: 25px;
        }

        .login-logo {
            max-width: 150px;
            max-height: 80px;
            object-fit: contain;
            display: block;
        }

        .company-logo-text {
            font-size: 32px;
            font-weight: 700;
            color: #2d3748;
            letter-spacing: -0.5px;
            margin-bottom: 0;
        }


        .welcome-logo {
            max-width: 360px;
            max-height: 180px;
            object-fit: cover;
            margin-bottom: 10px;
            filter: brightness(0) invert(1);
        }

    </style>
</head>
<body>
    <div class="login-container">
        <!-- Panel izquierdo - Bienvenida -->
        <div class="welcome-panel">
            <div class="welcome-content">
                <?php if (!empty($logo)): ?>
                    <img src="<?= htmlspecialchars($logo) ?>" alt="<?= htmlspecialchars($companyName) ?>" class="welcome-logo">
                <?php endif; ?>
                <h1><span data-translate="welcome_title">Bienvenido a</span><br><?= htmlspecialchars($companyName) ?></h1>
                <div class="welcome-divider"></div>
                <p class="welcome-description" data-translate="welcome_description">
                    Un software profesional para agencias de viajes que te permite crear itinerarios personalizados, gestionar reservas y brindar experiencias inolvidables a tus clientes.
                </p>
            </div>
        </div>

        <!-- Panel derecho - Formulario -->
        <div class="form-panel">
            <div class="form-header">
                <div class="company-brand">
                    <!-- Cambio para nombre  -->
                </div>
                <h2 class="form-title">Iniciar Sesión</h2>
            </div>

            <!-- Selector de Idioma -->
            <div class="language-selector">
                <button type="button" class="language-btn active" data-lang="es" onclick="changeLanguage('es')">
                    🇪🇸 Español
                </button>
                <button type="button" class="language-btn" data-lang="en" onclick="changeLanguage('en')">
                    🇺🇸 English
                </button>
                <button type="button" class="language-btn" data-lang="fr" onclick="changeLanguage('fr')">
                    🇫🇷 Français
                </button>
                <button type="button" class="language-btn" data-lang="pt" onclick="changeLanguage('pt')">
                    🇧🇷 Português
                </button>
            </div>

            <!-- Mensaje de sesión expirada -->
            <?php if (isset($_SESSION['session_expired'])): ?>
                <div class="session-expired">
                    ⏰ <span data-translate="session_expired">Tu sesión ha expirado por inactividad. Por favor, inicia sesión nuevamente.</span>
                </div>
                <?php unset($_SESSION['session_expired']); ?>
            <?php endif; ?>

            <!-- Formulario de Login -->
            <form action="<?= APP_URL ?>/auth/login" method="POST" id="loginForm" class="login-form">
                <div class="form-group">
                    <label for="email" data-translate="email_label">Ingrese correo electrónico</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        required 
                        placeholder="usuario@ejemplo.com" 
                        data-translate-placeholder="email_placeholder"
                        autocomplete="email"
                        pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"
                        title="Ingrese un correo electrónico válido">
                </div>

                <div class="form-group">
                    <label for="password" data-translate="password_label">Ingrese contraseña</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required 
                        placeholder="••••••••" 
                        data-translate-placeholder="password_placeholder"
                        autocomplete="current-password">
                </div>

                <button type="submit" class="login-btn" id="loginBtn">
                    <span data-translate="login_button">Iniciar Sesión</span>
                    <span class="loading" id="loading"></span>
                </button>

                <?php if (isset($_SESSION['error'])): 
                    $errorMsg = $_SESSION['error'];
                    $isSubscriptionError = (
                        strpos($errorMsg, 'suscripción') !== false && 
                        strpos($errorMsg, 'expirado') !== false
                    );
                    
                    // Extraer fecha si existe
                    preg_match('/\((\d{2}\/\d{2}\/\d{4})\)/', $errorMsg, $matches);
                    $errorDate = isset($matches[1]) ? $matches[1] : '';
                ?>
                    <div class="error-message" 
                        data-is-subscription-error="<?= $isSubscriptionError ? '1' : '0' ?>"
                        data-error-date="<?= htmlspecialchars($errorDate) ?>">
                        🚫 <span class="error-text"><?= htmlspecialchars($errorMsg) ?></span>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="success-message">
                        ✅ <?= htmlspecialchars($_SESSION['success']) ?>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>
            </form>

            <!-- Footer -->
            <div class="form-footer">
                <p>© <?= date('Y') ?> <?= htmlspecialchars($companyName) ?>. <span data-translate="rights">Todos los derechos reservados</span>.</p>
            </div>
        </div>
    </div>

    <script>
        // Traducciones del sistema
        const translations = {
            es: {
                email_label: 'Ingrese correo electrónico',
                email_placeholder: 'usuario@ejemplo.com',
                password_label: 'Ingrese contraseña',
                password_placeholder: '••••••••',
                login_button: 'Iniciar Sesión',
                session_expired: 'Tu sesión ha expirado por inactividad. Por favor, inicia sesión nuevamente.',
                rights: 'Todos los derechos reservados',
                welcome_title: 'Bienvenido a',
                welcome_description: 'Un software profesional para agencias de viajes que te permite crear itinerarios personalizados, gestionar reservas y brindar experiencias inolvidables a tus clientes.'
                error_fields_required: 'Por favor complete todos los campos',
                error_invalid_email: 'Por favor ingrese un correo electrónico válido',
                error_invalid_credentials: 'Correo electrónico o contraseña incorrectos',
                subscription_expired_prefix: 'La suscripción de su agencia ha expirado',
                subscription_expired_suffix: 'Contacte al administrador para renovarla'
            },
            en: {
                email_label: 'Enter email address',
                email_placeholder: 'user@example.com',
                password_label: 'Enter password',
                password_placeholder: '••••••••',
                login_button: 'Sign In',
                session_expired: 'Your session has expired due to inactivity. Please sign in again.',
                rights: 'All rights reserved',
                welcome_title: 'Welcome to',
                welcome_description: 'A professional software for travel agencies that allows you to create personalized itineraries, manage bookings and provide unforgettable experiences to your clients.'
                error_fields_required: 'Please complete all fields',
                error_invalid_email: 'Please enter a valid email address',
                error_invalid_credentials: 'Incorrect email or password',
                subscription_expired_prefix: 'Your agency subscription has expired',
                subscription_expired_suffix: 'Contact the administrator to renew it'
            },
            fr: {
                email_label: 'Entrez l\'adresse e-mail',
                email_placeholder: 'utilisateur@exemple.com',
                password_label: 'Entrez le mot de passe',
                password_placeholder: '••••••••',
                login_button: 'Se connecter',
                session_expired: 'Votre session a expiré en raison d\'inactivité. Veuillez vous reconnecter.',
                rights: 'Tous droits réservés',
                welcome_title: 'Bienvenue à',
                welcome_description: 'Un logiciel professionnel pour les agences de voyages qui vous permet de créer des itinéraires personnalisés, de gérer les réservations et d\'offrir des expériences inoubliables à vos clients.'
                error_fields_required: 'Veuillez remplir tous les champs',
                error_invalid_email: 'Veuillez entrer une adresse e-mail valide',
                error_invalid_credentials: 'E-mail ou mot de passe incorrect',
                subscription_expired_prefix: 'L\'abonnement de votre agence a expiré',
                subscription_expired_suffix: 'Contactez l\'administrateur pour le renouveler'
            },
            pt: {
                email_label: 'Digite o endereço de e-mail',
                email_placeholder: 'usuario@exemplo.com',
                password_label: 'Digite a senha',
                password_placeholder: '••••••••',
                login_button: 'Entrar',
                session_expired: 'Sua sessão expirou devido à inatividade. Por favor, faça login novamente.',
                rights: 'Todos os direitos reservados',
                welcome_title: 'Bem-vindo ao',
                welcome_description: 'Um software profissional para agências de viagens que permite criar itinerários personalizados, gerenciar reservas e proporcionar experiências inesquecíveis aos seus clientes.'
                error_fields_required: 'Por favor, preencha todos os campos',
                error_invalid_email: 'Por favor, insira um endereço de e-mail válido',
                error_invalid_credentials: 'E-mail ou senha incorretos',
                subscription_expired_prefix: 'A assinatura de sua agência expirou',
                subscription_expired_suffix: 'Entre em contato com o administrador para renová-la'
            }
        };

        // Idioma actual
        let currentLanguage = localStorage.getItem('loginLanguage') || 'es';

        // Aplicar traducciones
        function applyTranslations(lang) {
            const trans = translations[lang];
            if (!trans) return;

            // Traducir elementos con data-translate
            document.querySelectorAll('[data-translate]').forEach(element => {
                const key = element.getAttribute('data-translate');
                if (trans[key]) {
                    element.textContent = trans[key];
                }
            });

            // Traducir placeholders
            document.querySelectorAll('[data-translate-placeholder]').forEach(element => {
                const key = element.getAttribute('data-translate-placeholder');
                if (trans[key]) {
                    element.placeholder = trans[key];
                }
            });

            // Traducir descripción de bienvenida
            const welcomeDesc = document.querySelector('.welcome-description');
            if (welcomeDesc && trans.welcome_description) {
                welcomeDesc.textContent = trans.welcome_description;
            }
        }

function translateErrorIfNeeded() {
    const errorDiv = document.querySelector('.error-message');
    if (!errorDiv) return;
    
    const errorCode = errorDiv.getAttribute('data-error-code');
    const errorDate = errorDiv.getAttribute('data-error-date');
    const errorSpan = errorDiv.querySelector('.error-text');
    const trans = translations[currentLanguage];
    
    let message = '';
    
    switch(errorCode) {
        case 'ERROR_FIELDS_REQUIRED':
            message = trans.error_fields_required;
            break;
        case 'ERROR_INVALID_EMAIL':
            message = trans.error_invalid_email;
            break;
        case 'ERROR_INVALID_CREDENTIALS':
            message = trans.error_invalid_credentials;
            break;
        case 'ERROR_SUBSCRIPTION_EXPIRED':
            message = trans.subscription_expired_prefix;
            if (errorDate) {
                const date = new Date(errorDate);
                const day = String(date.getDate()).padStart(2, '0');
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const year = date.getFullYear();
                message += ` (${day}/${month}/${year})`;
            }
            message += `. ${trans.subscription_expired_suffix}.`;
            break;
        default:
            message = errorCode;
    }
    
    errorSpan.textContent = message;
}

        // Cambiar idioma
        function changeLanguage(lang) {
            if (!translations[lang]) return;

            currentLanguage = lang;
            localStorage.setItem('loginLanguage', lang);

            // Actualizar botones activos
            document.querySelectorAll('.language-btn').forEach(btn => {
                btn.classList.remove('active');
                if (btn.getAttribute('data-lang') === lang) {
                    btn.classList.add('active');
                }
            });

            // Aplicar traducciones
            applyTranslations(lang);
        }

        // Inicializar idioma guardado
        document.addEventListener('DOMContentLoaded', function() {
            applyTranslations(currentLanguage);
            translateErrorIfNeeded();
            
            // Marcar botón activo
            document.querySelectorAll('.language-btn').forEach(btn => {
                if (btn.getAttribute('data-lang') === currentLanguage) {
                    btn.classList.add('active');
                }
            });
        });

        // Mostrar loading al enviar formulario
        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('loginBtn');
            btn.classList.add('loading');
            btn.disabled = true;
        });
    </script>

    <script>
// Traducciones completas del sistema de login
const translations = {
    es: {
        email_label: 'Ingrese correo electrónico',
        email_placeholder: 'usuario@ejemplo.com',
        password_label: 'Ingrese contraseña',
        password_placeholder: '••••••••',
        login_button: 'Iniciar Sesión',
        session_expired: 'Tu sesión ha expirado por inactividad. Por favor, inicia sesión nuevamente.',
        rights: 'Todos los derechos reservados',
        welcome_title: 'Bienvenido a',
        welcome_description: 'Un software profesional para agencias de viajes que te permite crear itinerarios personalizados, gestionar reservas y brindar experiencias inolvidables a tus clientes.',
        
        // Mensajes de error de suscripción
        subscription_expired: 'La suscripción de su agencia ha expirado',
        contact_admin: 'Contacte al administrador para renovarla',
        agency_inactive: 'Su agencia ha sido desactivada. Contacte al administrador',
        subscription_not_active: 'La suscripción de su agencia no está activa. Contacte al administrador',
        agency_not_found: 'Agencia no encontrada. Contacte al administrador'
    },
    en: {
        email_label: 'Enter email address',
        email_placeholder: 'user@example.com',
        password_label: 'Enter password',
        password_placeholder: '••••••••',
        login_button: 'Sign In',
        session_expired: 'Your session has expired due to inactivity. Please sign in again.',
        rights: 'All rights reserved',
        welcome_title: 'Welcome to',
        welcome_description: 'A professional software for travel agencies that allows you to create personalized itineraries, manage bookings and provide unforgettable experiences to your clients.',
        
        // Subscription error messages
        subscription_expired: 'Your agency subscription has expired',
        contact_admin: 'Contact the administrator to renew it',
        agency_inactive: 'Your agency has been deactivated. Contact the administrator',
        subscription_not_active: 'Your agency subscription is not active. Contact the administrator',
        agency_not_found: 'Agency not found. Contact the administrator'
    },
    fr: {
        email_label: 'Entrez l\'adresse e-mail',
        email_placeholder: 'utilisateur@exemple.com',
        password_label: 'Entrez le mot de passe',
        password_placeholder: '••••••••',
        login_button: 'Se connecter',
        session_expired: 'Votre session a expiré en raison d\'inactivité. Veuillez vous reconnecter.',
        rights: 'Tous droits réservés',
        welcome_title: 'Bienvenue à',
        welcome_description: 'Un logiciel professionnel pour les agences de voyages qui vous permet de créer des itinéraires personnalisés, de gérer les réservations et d\'offrir des expériences inoubliables à vos clients.',
        
        // Messages d'erreur d'abonnement
        subscription_expired: 'L\'abonnement de votre agence a expiré',
        contact_admin: 'Contactez l\'administrateur pour le renouveler',
        agency_inactive: 'Votre agence a été désactivée. Contactez l\'administrateur',
        subscription_not_active: 'L\'abonnement de votre agence n\'est pas actif. Contactez l\'administrateur',
        agency_not_found: 'Agence introuvable. Contactez l\'administrateur'
    },
    pt: {
        email_label: 'Digite o endereço de e-mail',
        email_placeholder: 'usuario@exemplo.com',
        password_label: 'Digite a senha',
        password_placeholder: '••••••••',
        login_button: 'Entrar',
        session_expired: 'Sua sessão expirou devido à inatividade. Por favor, faça login novamente.',
        rights: 'Todos os direitos reservados',
        welcome_title: 'Bem-vindo ao',
        welcome_description: 'Um software profissional para agências de viagens que permite criar itinerários personalizados, gerenciar reservas e proporcionar experiências inesquecíveis aos seus clientes.',
        
        // Mensagens de erro de assinatura
        subscription_expired: 'A assinatura de sua agência expirou',
        contact_admin: 'Entre em contato com o administrador para renová-la',
        agency_inactive: 'Sua agência foi desativada. Entre em contato com o administrador',
        subscription_not_active: 'A assinatura de sua agência não está ativa. Entre em contato com o administrador',
        agency_not_found: 'Agência não encontrada. Entre em contato com o administrador'
    }
};

// Idioma actual
let currentLanguage = localStorage.getItem('loginLanguage') || 'es';

// Aplicar traducciones a elementos de la interfaz
function applyTranslations(lang) {
    const trans = translations[lang];
    if (!trans) return;

    // Traducir elementos con data-translate
    document.querySelectorAll('[data-translate]').forEach(element => {
        const key = element.getAttribute('data-translate');
        if (trans[key]) {
            element.textContent = trans[key];
        }
    });

    // Traducir placeholders
    document.querySelectorAll('[data-translate-placeholder]').forEach(element => {
        const key = element.getAttribute('data-translate-placeholder');
        if (trans[key]) {
            element.placeholder = trans[key];
        }
    });

    // Traducir descripción de bienvenida
    const welcomeDesc = document.querySelector('.welcome-description');
    if (welcomeDesc && trans.welcome_description) {
        welcomeDesc.textContent = trans.welcome_description;
    }
}

// ✅ NUEVA FUNCIÓN: Traducir mensajes de error dinámicamente
function translateErrorMessage(errorElement) {
    if (!errorElement) return;
    
    const errorText = errorElement.textContent.trim();
    const trans = translations[currentLanguage];
    
    // Detectar tipo de error y traducir
    if (errorText.includes('suscripción') && errorText.includes('expirado')) {
        // Extraer fecha si existe
        const dateMatch = errorText.match(/\((\d{2}\/\d{2}\/\d{4})\)/);
        const date = dateMatch ? dateMatch[1] : '';
        
        errorElement.innerHTML = `🚫 ${trans.subscription_expired}${date ? ' (' + date + ')' : ''}. ${trans.contact_admin}.`;
    }
    else if (errorText.includes('agencia') && errorText.includes('desactivada')) {
        errorElement.innerHTML = `🚫 ${trans.agency_inactive}.`;
    }
    else if (errorText.includes('suscripción') && errorText.includes('no está activa')) {
        errorElement.innerHTML = `🚫 ${trans.subscription_not_active}.`;
    }
    else if (errorText.includes('Agencia no encontrada')) {
        errorElement.innerHTML = `🚫 ${trans.agency_not_found}.`;
    }
    // Mantener otros errores como están (credenciales incorrectas, etc.)
}

// Cambiar idioma
function changeLanguage(lang) {
    if (!translations[lang]) return;

    currentLanguage = lang;
    localStorage.setItem('loginLanguage', lang);

    // Actualizar botones activos
    document.querySelectorAll('.language-btn').forEach(btn => {
        btn.classList.remove('active');
        if (btn.getAttribute('data-lang') === lang) {
            btn.classList.add('active');
        }
    });

    // Aplicar traducciones
    applyTranslations(lang);
    translateErrorIfNeeded(); 
    
    // ✅ Traducir mensaje de error si existe
    const errorElement = document.querySelector('.error-message');
    if (errorElement) {
        translateErrorMessage(errorElement);
    }
}

// Inicializar idioma guardado
document.addEventListener('DOMContentLoaded', function() {
    applyTranslations(currentLanguage);
    
    // ✅ Traducir mensaje de error si existe al cargar
    const errorElement = document.querySelector('.error-message');
    if (errorElement) {
        translateErrorMessage(errorElement);
    }
    
    // Marcar botón activo
    document.querySelectorAll('.language-btn').forEach(btn => {
        if (btn.getAttribute('data-lang') === currentLanguage) {
            btn.classList.add('active');
        }
    });
});

// Mostrar loading al enviar formulario
document.getElementById('loginForm').addEventListener('submit', function() {
    const btn = document.getElementById('loginBtn');
    btn.classList.add('loading');
    btn.disabled = true;
});
</script>
</body>
</html>