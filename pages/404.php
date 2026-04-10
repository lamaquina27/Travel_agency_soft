<?php
// =====================================
// ARCHIVO: pages/404.php - PÁGINA DE ERROR 404 MEJORADA
// =====================================

require_once 'config/app.php';
require_once 'config/config_functions.php';

// Inicializar configuración
try {
    ConfigManager::init();
    $config = ConfigManager::get();
    $companyName = ConfigManager::getCompanyName();
} catch(Exception $e) {
    $config = [];
    $companyName = 'Travel Agency';
}

// Obtener usuario si está logueado
$user = null;
$isLoggedIn = App::isLoggedIn();
if ($isLoggedIn) {
    $user = App::getUser();
}

// Obtener la URL solicitada para sugerir redirecciones
$requestedPath = $_SERVER['REQUEST_URI'] ?? '';
$suggestions = [];

// Sugerir páginas similares basadas en la URL solicitada
if (strpos($requestedPath, 'itinerario') !== false) {
    $suggestions[] = ['url' => '/itinerarios', 'title' => 'Gestión de Itinerarios', 'icon' => 'fas fa-route'];
    $suggestions[] = ['url' => '/programa', 'title' => 'Mi Programa', 'icon' => 'fas fa-plane'];
} elseif (strpos($requestedPath, 'programa') !== false) {
    $suggestions[] = ['url' => '/programa', 'title' => 'Mi Programa', 'icon' => 'fas fa-plane'];
    $suggestions[] = ['url' => '/itinerarios', 'title' => 'Ver Itinerarios', 'icon' => 'fas fa-route'];
} elseif (strpos($requestedPath, 'biblioteca') !== false || strpos($requestedPath, 'destino') !== false) {
    $suggestions[] = ['url' => '/biblioteca', 'title' => 'Biblioteca de Destinos', 'icon' => 'fas fa-book'];
} elseif (strpos($requestedPath, 'admin') !== false) {
    if ($isLoggedIn && $user['role'] === 'admin') {
        $suggestions[] = ['url' => '/administrador', 'title' => 'Panel de Administración', 'icon' => 'fas fa-cogs'];
        $suggestions[] = ['url' => '/administrador/configuracion', 'title' => 'Configuración del Sistema', 'icon' => 'fas fa-sliders-h'];
    }
}

// Agregar sugerencias generales si está logueado
if ($isLoggedIn) {
    if (empty($suggestions)) {
        $suggestions[] = ['url' => '/dashboard', 'title' => 'Dashboard Principal', 'icon' => 'fas fa-home'];
        $suggestions[] = ['url' => '/programa', 'title' => 'Mi Programa', 'icon' => 'fas fa-plane'];
        $suggestions[] = ['url' => '/biblioteca', 'title' => 'Biblioteca', 'icon' => 'fas fa-book'];
    }
} else {
    $suggestions[] = ['url' => '/login', 'title' => 'Iniciar Sesión', 'icon' => 'fas fa-sign-in-alt'];
}
?>
<!DOCTYPE html>
<html lang="<?= $config['default_language'] ?? 'es' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página no encontrada - <?= htmlspecialchars($companyName) ?></title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Fuentes de Google -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }


        .VIpgJd-ZVi9od-ORHb-OEVmcd {
            left: 0;
            display: none !important;
            top: 0;
        }
        
        :root {
            --primary-color: <?= $config['login_bg_color'] ?? '#667eea' ?>;
            --secondary-color: <?= $config['login_secondary_color'] ?? '#764ba2' ?>;
            --primary-gradient: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        }

        body {
            background: var(--primary-gradient);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .error-container {
            text-align: center;
            padding: 2rem;
            max-width: 600px;
            margin: 0 auto;
        }

        .error-code {
            font-size: 8rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 0 4px 8px rgba(0,0,0,0.3);
            background: linear-gradient(45deg, rgba(255,255,255,0.9), rgba(255,255,255,0.6));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .error-title {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 1rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .error-description {
            font-size: 1.1rem;
            margin-bottom: 2rem;
            opacity: 0.9;
            line-height: 1.6;
        }

        .error-url {
            background: rgba(255,255,255,0.2);
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            word-break: break-all;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
        }

        .suggestions {
            margin-top: 2rem;
        }

        .suggestions-title {
            font-size: 1.2rem;
            font-weight: 500;
            margin-bottom: 1rem;
            opacity: 0.9;
        }

        .suggestions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .suggestion-card {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 12px;
            padding: 1.5rem;
            text-decoration: none;
            color: white;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .suggestion-card:hover {
            transform: translateY(-4px);
            background: rgba(255,255,255,0.25);
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        }

        .suggestion-icon {
            font-size: 2rem;
            margin-bottom: 0.75rem;
            opacity: 0.9;
        }

        .suggestion-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .back-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 8px;
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .action-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }

        .action-btn.primary {
            background: rgba(255,255,255,0.9);
            color: var(--primary-color);
        }

        .action-btn.primary:hover {
            background: white;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .error-container {
                padding: 1rem;
            }

            .error-code {
                font-size: 5rem;
            }

            .error-title {
                font-size: 1.5rem;
            }

            .error-description {
                font-size: 1rem;
            }

            .suggestions-grid {
                grid-template-columns: 1fr;
            }

            .back-actions {
                flex-direction: column;
                align-items: center;
            }

            .action-btn {
                width: 100%;
                max-width: 300px;
                justify-content: center;
            }
        }

        /* Animación de entrada */
        .error-container {
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">404</div>
        
        <h1 class="error-title">Página no encontrada</h1>
        
        <p class="error-description">
            Lo sentimos, la página que estás buscando no existe o ha sido movida.
        </p>

        <?php if ($requestedPath): ?>
        <div class="error-url">
            <strong>URL solicitada:</strong> <?= htmlspecialchars($requestedPath) ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($suggestions)): ?>
        <div class="suggestions">
            <h2 class="suggestions-title">¿Quizás estabas buscando?</h2>
            <div class="suggestions-grid">
                <?php foreach ($suggestions as $suggestion): ?>
                <a href="<?= APP_URL . $suggestion['url'] ?>" class="suggestion-card">
                    <i class="<?= $suggestion['icon'] ?> suggestion-icon"></i>
                    <div class="suggestion-title"><?= $suggestion['title'] ?></div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="back-actions">
            <button onclick="history.back()" class="action-btn">
                <i class="fas fa-arrow-left"></i>
                Página Anterior
            </button>
            
            <?php if ($isLoggedIn): ?>
            <a href="<?= APP_URL ?>/dashboard" class="action-btn primary">
                <i class="fas fa-home"></i>
                Ir al Dashboard
            </a>
            <?php else: ?>
            <a href="<?= APP_URL ?>/login" class="action-btn primary">
                <i class="fas fa-sign-in-alt"></i>
                Iniciar Sesión
            </a>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Reportar error 404 (opcional, para analytics)
        console.warn('Error 404 - Página no encontrada:', window.location.href);
        
        // Auto-redirigir en algunos casos específicos después de mostrar el mensaje
        setTimeout(() => {
            const path = window.location.pathname.toLowerCase();
            
            // Redirecciones automáticas para URLs comunes mal escritas
            if (path.includes('itinerario') && !path.includes('itinerarios')) {
                if (confirm('¿Te gustaría ir a la página de Itinerarios?')) {
                    window.location.href = '<?= APP_URL ?>/itinerarios';
                }
            } else if (path.includes('programa') && !path.includes('/programa')) {
                if (confirm('¿Te gustaría ir a Mi Programa?')) {
                    window.location.href = '<?= APP_URL ?>/programa';
                }
            }
        }, 3000);
    </script>
</body>
</html>