<?php
// =====================================
// ARCHIVO: pages/404.php
// PROPUESTA DE REDISEÑO v3 — Viajero perdido, paleta clara
// =====================================

require_once 'config/app.php';
require_once 'config/config_functions.php';

try {
    ConfigManager::init();
    $config = ConfigManager::get();
    $companyName = ConfigManager::getCompanyName();
} catch (Exception $e) {
    $config = [];
    $companyName = 'Travel Agency';
}

$user = null;
$isLoggedIn = App::isLoggedIn();
if ($isLoggedIn) {
    $user = App::getUser();
}

$rawPath = $_SERVER['REQUEST_URI'] ?? '';
$requestedPath = parse_url($rawPath, PHP_URL_PATH) ?: $rawPath;

error_log(sprintf(
    '[404] path=%s method=%s referer=%s ip=%s',
    $rawPath,
    $_SERVER['REQUEST_METHOD'] ?? '-',
    $_SERVER['HTTP_REFERER'] ?? '-',
    $_SERVER['REMOTE_ADDR'] ?? '-'
));

$brandPrimary   = $config['login_bg_color']        ?? '#667eea';
$brandSecondary = $config['login_secondary_color'] ?? '#764ba2';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($config['default_language'] ?? 'es') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 · Te perdiste · <?= htmlspecialchars($companyName) ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        *, *::before, *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            /* Colores del branding (config de la agencia) */
            --brand-primary:   <?= htmlspecialchars($brandPrimary) ?>;
            --brand-secondary: <?= htmlspecialchars($brandSecondary) ?>;

            /* Paleta clara base */
            --bg:              #FAFAFD;
            --bg-tinted:       #F4F1FB;
            --surface:         #FFFFFF;
            --border:          #E5E7EB;
            --border-strong:   #D1D5DB;

            --text-primary:    #1E1B4B;
            --text-secondary:  #4B5563;
            --text-muted:      #9CA3AF;

            --shadow-sm:       0 1px 3px rgba(30, 27, 75, 0.06);
            --shadow-md:       0 8px 24px rgba(30, 27, 75, 0.08);
            --shadow-lg:       0 20px 50px rgba(30, 27, 75, 0.10);

            --font-display:    'Space Grotesk', 'Inter', sans-serif;
            --font-body:       'Inter', sans-serif;

            --radius-md:       12px;
            --radius-lg:       16px;
        }

        /* Oculta el banner del widget de Google Translate si está activo */
        .VIpgJd-ZVi9od-ORHb-OEVmcd { display: none !important; }

        html, body { height: 100%; }

        body {
            font-family: var(--font-body);
            background: var(--bg);
            color: var(--text-primary);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            line-height: 1.5;
            overflow-x: hidden;
        }

        /* Halos suaves de fondo en colores del branding */
        body::before {
            content: '';
            position: fixed;
            top: -20%;
            right: -10%;
            width: 60%;
            height: 70%;
            background: radial-gradient(circle, var(--brand-primary) 0%, transparent 70%);
            opacity: 0.10;
            pointer-events: none;
            z-index: 0;
        }

        body::after {
            content: '';
            position: fixed;
            bottom: -20%;
            left: -10%;
            width: 55%;
            height: 60%;
            background: radial-gradient(circle, var(--brand-secondary) 0%, transparent 70%);
            opacity: 0.08;
            pointer-events: none;
            z-index: 0;
        }

        /* ─────────────────────────────────────────────
           Layout principal — 2 columnas, texto centrado en su columna
        ───────────────────────────────────────────── */
        .page {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1.05fr 1fr;
            align-items: center;
            gap: 3rem;
            padding: 3rem 5rem;
            max-width: 1280px;
            margin: 0 auto;
        }

        @media (max-width: 1024px) {
            .page {
                grid-template-columns: 1fr;
                padding: 2.5rem 2rem;
                gap: 2rem;
            }
            .text-block { order: 2; }
            .illustration { order: 1; max-width: 380px; margin: 0 auto; }
        }

        @media (max-width: 600px) {
            .page { padding: 1.5rem 1.25rem; }
        }

        /* ─────────────────────────────────────────────
           Bloque de texto — centrado dentro de su columna
        ───────────────────────────────────────────── */
        .text-block {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1.25rem;
            max-width: 540px;
            text-align: center;
            justify-self: center;
        }

        /* Sello de pasaporte grande de fondo (watermark central) */
        .stamp-watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-8deg);
            width: 700px;
            max-width: 95vw;
            max-height: 95vh;
            opacity: 0.08;
            z-index: 0;
            pointer-events: none;
        }

        .stamp-watermark svg {
            width: 100%;
            height: auto;
            display: block;
        }

        .error-code {
            font-family: var(--font-display);
            font-size: clamp(7rem, 16vw, 14rem);
            font-weight: 700;
            line-height: 0.9;
            letter-spacing: -0.05em;
            background: linear-gradient(135deg, var(--brand-primary) 0%, var(--brand-secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            color: transparent;
            margin-bottom: 0.5rem;
        }

        .error-title {
            font-family: var(--font-display);
            font-size: clamp(1.75rem, 3.5vw, 2.5rem);
            font-weight: 600;
            color: var(--text-primary);
            letter-spacing: -0.02em;
            line-height: 1.15;
        }

        .error-description {
            font-size: clamp(0.9375rem, 1.4vw, 1.0625rem);
            color: var(--text-secondary);
            font-weight: 400;
            max-width: 440px;
        }

        .url-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0.875rem;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            font-family: ui-monospace, 'SF Mono', Menlo, monospace;
            font-size: 0.8125rem;
            color: var(--text-secondary);
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            box-shadow: var(--shadow-sm);
        }

        .url-chip-label {
            color: var(--text-muted);
            font-family: var(--font-body);
            font-weight: 500;
        }

        /* ─────────────────────────────────────────────
           Botones
        ───────────────────────────────────────────── */
        .actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 0.5rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.875rem 1.5rem;
            border-radius: var(--radius-lg);
            font-family: var(--font-body);
            font-size: 0.9375rem;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: transform 0.15s ease, background 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
            border: 1px solid transparent;
        }

        .btn-ghost {
            background: var(--surface);
            color: var(--text-secondary);
            border-color: var(--border);
        }

        .btn-ghost:hover {
            background: var(--bg-tinted);
            color: var(--text-primary);
            border-color: var(--border-strong);
            transform: translateY(-2px);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--brand-primary) 0%, var(--brand-secondary) 100%);
            color: #FFFFFF;
            box-shadow: var(--shadow-md);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            filter: brightness(1.05);
        }

        /* ─────────────────────────────────────────────
           Ilustración
        ───────────────────────────────────────────── */
        .illustration {
            position: relative;
            width: 100%;
            max-width: 520px;
            justify-self: center;
        }

        .illustration svg {
            width: 100%;
            height: auto;
            display: block;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50%      { transform: translateY(-6px); }
        }

        @keyframes drift {
            0%, 100% { transform: translate(0, 0) rotate(0); }
            50%      { transform: translate(4px, -8px) rotate(-2deg); }
        }

        .float-traveler { animation: float 4s ease-in-out infinite; transform-origin: center; transform-box: fill-box; }
        .float-plane    { animation: drift 5s ease-in-out infinite; transform-origin: center; transform-box: fill-box; }
        .float-tag      { animation: float 4.5s ease-in-out infinite reverse; transform-origin: center; transform-box: fill-box; }

        @media (prefers-reduced-motion: reduce) {
            .float-traveler, .float-plane, .float-tag { animation: none; }
            .btn { transition: none; }
        }
    </style>
</head>
<body>
    <!-- Sello de pasaporte grande de fondo (watermark central) -->
    <div class="stamp-watermark" aria-hidden="true">
        <svg viewBox="0 0 600 600" xmlns="http://www.w3.org/2000/svg">
            <g transform="translate(300, 300)">
                <circle cx="0" cy="0" r="270" fill="none" stroke="var(--brand-secondary)" stroke-width="8"/>
                <circle cx="0" cy="0" r="230" fill="none" stroke="var(--brand-secondary)" stroke-width="2"/>
                <text x="0" y="-20"
                      fill="var(--brand-secondary)"
                      font-family="Space Grotesk, sans-serif"
                      font-size="58" font-weight="700"
                      text-anchor="middle"
                      letter-spacing="6">NOT FOUND</text>
                <text x="0" y="80"
                      fill="var(--brand-secondary)"
                      font-family="Space Grotesk, sans-serif"
                      font-size="78" font-weight="800"
                      text-anchor="middle"
                      letter-spacing="2">· 404 ·</text>
            </g>
        </svg>
    </div>

    <main class="page">
        <div class="text-block">
            <h1 class="error-code">404</h1>
            <h2 class="error-title">Te perdiste en el camino</h2>
            <p class="error-description">
                Esta dirección no existe en nuestro mapa. Tal vez tomaste una ruta equivocada
                o la página fue movida.
            </p>

            <?php if ($requestedPath): ?>
                <div class="url-chip" title="<?= htmlspecialchars($requestedPath) ?>">
                    <span class="url-chip-label">URL:</span>
                    <span><?= htmlspecialchars($requestedPath) ?></span>
                </div>
            <?php endif; ?>

            <div class="actions">
                <button onclick="history.back()" class="btn btn-ghost" type="button">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    Página anterior
                </button>

                <?php if ($isLoggedIn): ?>
                    <a href="<?= htmlspecialchars(APP_URL) ?>/dashboard" class="btn btn-primary">
                        Volver al Dashboard
                    </a>
                <?php else: ?>
                    <a href="<?= htmlspecialchars(APP_URL) ?>/login" class="btn btn-primary">
                        Iniciar Sesión
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- ─── Ilustración: composición centrada y cohesiva ─── -->
        <div class="illustration" aria-hidden="true">
            <svg viewBox="0 0 500 500" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <!-- Gradient para el globo (usa colores del branding) -->
                    <linearGradient id="globeGrad" x1="50%" y1="0%" x2="50%" y2="100%">
                        <stop offset="0%"  stop-color="var(--brand-primary)"   stop-opacity="0.95"/>
                        <stop offset="100%" stop-color="var(--brand-secondary)" stop-opacity="0.95"/>
                    </linearGradient>

                    <!-- Halo suave detrás del globo -->
                    <radialGradient id="halo" cx="50%" cy="50%" r="50%">
                        <stop offset="0%"  stop-color="var(--brand-primary)" stop-opacity="0.18"/>
                        <stop offset="100%" stop-color="var(--brand-primary)" stop-opacity="0"/>
                    </radialGradient>
                </defs>

                <!-- Halo de fondo -->
                <circle cx="250" cy="280" r="220" fill="url(#halo)"/>

                <!-- Estrellas/puntos decorativos suaves -->
                <g fill="var(--brand-primary)" opacity="0.25">
                    <circle cx="60"  cy="100" r="3"/>
                    <circle cx="420" cy="80"  r="2.5"/>
                    <circle cx="80"  cy="380" r="2.5"/>
                    <circle cx="450" cy="400" r="3"/>
                </g>

                <!-- Línea curva punteada (ruta del avión) — asimétrica con un desvío -->
                <path d="M40 100 Q140 50 240 90 Q290 110 270 145 Q300 180 430 95"
                      fill="none"
                      stroke="var(--brand-primary)"
                      stroke-width="2"
                      stroke-dasharray="2 6"
                      stroke-linecap="round"
                      opacity="0.4"/>

                <!-- Sombra suave debajo del globo -->
                <ellipse cx="250" cy="468" rx="120" ry="11"
                         fill="#1E1B4B" opacity="0.10"/>
                <ellipse cx="250" cy="468" rx="80" ry="6"
                         fill="#1E1B4B" opacity="0.10"/>

                <!-- Anillo del planeta -->
                <ellipse cx="250" cy="320" rx="180" ry="32"
                         fill="none"
                         stroke="var(--brand-secondary)"
                         stroke-width="3"
                         opacity="0.5"/>

                <!-- Globo principal -->
                <circle cx="250" cy="320" r="130" fill="url(#globeGrad)"/>

                <!-- Meridianos y paralelos del globo (líneas blancas sutiles) -->
                <g fill="none" stroke="#FFFFFF" stroke-width="1.2" opacity="0.35">
                    <ellipse cx="250" cy="320" rx="40"  ry="130"/>
                    <ellipse cx="250" cy="320" rx="80"  ry="130"/>
                    <ellipse cx="250" cy="320" rx="120" ry="130"/>
                    <ellipse cx="250" cy="320" rx="130" ry="32"/>
                    <ellipse cx="250" cy="320" rx="130" ry="75"/>
                    <ellipse cx="250" cy="320" rx="130" ry="110"/>
                </g>

                <!-- Silueta de África: 70% del tamaño, opacidad 0.22, rotada -15°, desplazada arriba-izq del globo -->
                <g transform="translate(235, 290) rotate(-15) scale(0.7) translate(-260, -317)" opacity="0.22">
                    <path d="M225 248
                             Q255 244 285 250
                             Q295 254 300 262
                             Q310 270 308 280
                             Q303 286 293 286
                             Q295 305 287 325
                             Q280 350 268 372
                             Q252 390 238 388
                             Q227 380 224 365
                             Q218 350 217 332
                             Q213 315 218 300
                             Q212 285 218 272
                             Q216 258 222 250
                             Q221 248 225 248 Z"
                          fill="#FFFFFF"/>
                </g>

                <!-- Avión de papel al final de la ruta punteada (su cola conecta con el trazo) -->
                <g class="float-plane">
                    <g transform="translate(425, 84) rotate(-20)">
                        <path d="M0 0 L42 12 L0 24 L11 12 Z"
                              fill="#FFFFFF"
                              stroke="var(--brand-primary)"
                              stroke-width="1.5"
                              stroke-linejoin="round"/>
                        <path d="M11 12 L22 16"
                              stroke="var(--brand-primary)"
                              stroke-width="1.2"
                              opacity="0.5"/>
                    </g>
                </g>

                <!-- Viajero sobre el globo (CENTRADO Y SOBRE EL GLOBO) -->
                <g class="float-traveler">
                    <g transform="translate(232, 138)">
                        <!-- mochila -->
                        <rect x="-3" y="32" width="26" height="38" rx="6"
                              fill="var(--brand-secondary)"/>
                        <rect x="2" y="40" width="16" height="4" rx="1" fill="rgba(255,255,255,0.4)"/>
                        <!-- correas -->
                        <path d="M3 34 Q-1 48 6 64"
                              fill="none" stroke="rgba(0,0,0,0.25)" stroke-width="2"/>
                        <path d="M17 34 Q21 48 14 64"
                              fill="none" stroke="rgba(0,0,0,0.25)" stroke-width="2"/>
                        <!-- cuerpo -->
                        <ellipse cx="16" cy="42" rx="16" ry="26" fill="#3B3F5C"/>
                        <!-- camisa banda -->
                        <ellipse cx="16" cy="52" rx="16" ry="7" fill="var(--brand-primary)" opacity="0.85"/>
                        <!-- cabeza -->
                        <circle cx="16" cy="14" r="12" fill="#F4D5B8"/>
                        <!-- ojos -->
                        <circle cx="12" cy="15" r="1.3" fill="#1E1B4B"/>
                        <circle cx="20" cy="15" r="1.3" fill="#1E1B4B"/>
                        <!-- pequeña sonrisa -->
                        <path d="M13 19 Q16 21 19 19"
                              fill="none" stroke="#1E1B4B"
                              stroke-width="1.3" stroke-linecap="round"/>
                        <!-- sombrero -->
                        <ellipse cx="16" cy="6" rx="19" ry="3.5" fill="#3B3F5C"/>
                        <ellipse cx="16" cy="4"  rx="11" ry="7" fill="var(--brand-primary)"/>
                        <ellipse cx="16" cy="6"  rx="11" ry="1.8" fill="var(--brand-secondary)"/>
                        <!-- brazo levantado (mano en la frente) -->
                        <path d="M26 38 Q42 22 45 14"
                              fill="none" stroke="#3B3F5C" stroke-width="6" stroke-linecap="round"/>
                        <circle cx="45" cy="14" r="4.5" fill="#F4D5B8"/>
                        <!-- brazo bajado -->
                        <path d="M5 44 Q-3 58 -2 70"
                              fill="none" stroke="#3B3F5C" stroke-width="6" stroke-linecap="round"/>
                        <!-- piernas -->
                        <line x1="10" y1="66" x2="6"  y2="92" stroke="#3B3F5C" stroke-width="6" stroke-linecap="round"/>
                        <line x1="22" y1="66" x2="26" y2="92" stroke="#3B3F5C" stroke-width="6" stroke-linecap="round"/>
                        <!-- zapatos -->
                        <ellipse cx="5"  cy="93" rx="5" ry="2.5" fill="#1E1B4B"/>
                        <ellipse cx="27" cy="93" rx="5" ry="2.5" fill="#1E1B4B"/>
                    </g>
                </g>

                <!-- Maleta colgando de la mano izquierda del viajero — comentada temporalmente para probar look sin maleta
                <g class="float-tag">
                    <g transform="translate(213, 213) scale(0.6)">
                        <path d="M14 0 Q14 -10 26 -10 Q38 -10 38 0"
                              fill="none" stroke="#3B3F5C" stroke-width="3"/>
                        <rect x="0" y="0" width="52" height="40" rx="6"
                              fill="var(--brand-primary)"
                              stroke="#3B3F5C" stroke-width="1.5"/>
                        <line x1="0" y1="14" x2="52" y2="14" stroke="rgba(0,0,0,0.18)" stroke-width="1"/>
                        <text x="26" y="32"
                              fill="#FFFFFF"
                              font-family="Space Grotesk, sans-serif"
                              font-size="16"
                              font-weight="700"
                              text-anchor="middle"
                              letter-spacing="-0.5">404</text>
                    </g>
                </g>
                -->
            </svg>
        </div>
    </main>
</body>
</html>
