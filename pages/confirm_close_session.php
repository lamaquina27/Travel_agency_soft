<?php
// ====================================================================
// ARCHIVO: pages/confirm_close_session.php
// DESCRIPCIÓN: Confirmación para cerrar sesión anterior
// ====================================================================

require_once 'config/app.php';

// Verificar que haya una solicitud de login pendiente
if (!isset($_SESSION['pending_login'])) {
    App::redirect('/login');
}

$pendingLogin = $_SESSION['pending_login'];
$existingSession = $pendingLogin['existing_session'];

// Función helper para obtener información del navegador
function getBrowserInfo($userAgent) {
    if (strpos($userAgent, 'Firefox') !== false) return 'Firefox';
    if (strpos($userAgent, 'Chrome') !== false) return 'Chrome';
    if (strpos($userAgent, 'Safari') !== false) return 'Safari';
    if (strpos($userAgent, 'Edge') !== false) return 'Edge';
    if (strpos($userAgent, 'Opera') !== false) return 'Opera';
    return 'Navegador desconocido';
}

function getOSInfo($userAgent) {
    if (strpos($userAgent, 'Windows') !== false) return 'Windows';
    if (strpos($userAgent, 'Mac') !== false) return 'macOS';
    if (strpos($userAgent, 'Linux') !== false) return 'Linux';
    if (strpos($userAgent, 'Android') !== false) return 'Android';
    if (strpos($userAgent, 'iOS') !== false) return 'iOS';
    return 'Sistema desconocido';
}

$browserInfo = getBrowserInfo($existingSession['user_agent']);
$osInfo = getOSInfo($existingSession['user_agent']);
$lastActivity = date('d/m/Y H:i:s', strtotime($existingSession['started_at']));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sesión Activa Detectada</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 500px;
            width: 100%;
            padding: 40px;
        }
        
        .icon {
            width: 80px;
            height: 80px;
            background: #ffeaa7;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 40px;
        }
        
        h1 {
            text-align: center;
            color: #2d3436;
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .subtitle {
            text-align: center;
            color: #636e72;
            margin-bottom: 30px;
        }
        
        .session-info {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .session-info-item {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            font-size: 14px;
        }
        
        .session-info-item:last-child {
            margin-bottom: 0;
        }
        
        .session-info-item strong {
            color: #2d3436;
            min-width: 120px;
        }
        
        .session-info-item span {
            color: #636e72;
        }
        
        .buttons {
            display: flex;
            gap: 12px;
            flex-direction: column;
        }
        
        .btn {
            padding: 14px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            text-decoration: none;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: white;
            color: #636e72;
            border: 2px solid #dfe6e9;
        }
        
        .btn-secondary:hover {
            background: #f8f9fa;
        }
        
        @media (max-width: 600px) {
            .container {
                padding: 30px 20px;
            }
            
            h1 {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon"></div>
        <h1>Sesión Activa Detectada</h1>
        <p class="subtitle">Ya tienes una sesión abierta en otro dispositivo</p>
        
        <div class="session-info">
            <div class="session-info-item">
                <strong>Dispositivo:</strong>
                <span><?= htmlspecialchars($osInfo) ?></span>
            </div>
            <div class="session-info-item">
                <strong>Navegador:</strong>
                <span><?= htmlspecialchars($browserInfo) ?></span>
            </div>
            <div class="session-info-item">
                <strong>Dirección IP:</strong>
                <span><?= htmlspecialchars($existingSession['ip']) ?></span>
            </div>
            <div class="session-info-item">
                <strong>Última actividad:</strong>
                <span><?= htmlspecialchars($lastActivity) ?></span>
            </div>
        </div>
        
        <div class="buttons">
            <form action="<?= APP_URL ?>/auth/force-close-session" method="POST">
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Sí, cerrar sesión anterior e iniciar aquí
                </button>
            </form>
            
            <a href="<?= APP_URL ?>/login" class="btn btn-secondary">
                Cancelar y volver al login
            </a>
        </div>
    </div>
</body>
</html>