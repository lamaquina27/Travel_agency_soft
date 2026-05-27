<?php
// =====================================
// ARCHIVO: index.php - CORREGIR LÍNEA 16
// =====================================

require_once 'config/database.php';
require_once 'config/app.php';

App::init();

$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);

// ✅ CORRECCIÓN LÍNEA 16: Verificar que parse_url no sea null
$appUrlPath = parse_url(APP_URL, PHP_URL_PATH);
$path = str_ireplace(rtrim($appUrlPath ?: '', '/'), '', $path);
$path = $path ?: '/';

// Limpiar path de múltiples slashes
$path = preg_replace('#/+#', '/', $path);

switch ($path) {
    case '/':
    case '/login':
        if (App::isLoggedIn()) {
            App::redirect('/dashboard');
        }
        include 'pages/login.php';
        break;

    case '/auth/login':
        include 'auth/login.php';
        break;

    case '/auth/logout':
        include 'auth/logout.php';
        break;

    case '/confirm-close-session':
        include 'pages/confirm_close_session.php';
        break;

    case '/auth/force-close-session':
        include 'auth/force_close_session.php';
        break;

    case '/dashboard':
        App::requireLogin();
        $user = App::getUser();

        if (isset($_GET['redirect'])) {
            if ($user['role'] === 'admin') {
                App::redirect('/administrador');
            } else {
                include 'pages/dashboard.php';
            }
        } else {
            include 'pages/dashboard.php';
        }
        break;

    case '/superadmin/dashboard':
        App::requireRole('superadmin');
        include 'pages/superadmin_dashboard.php';
        break;

    case '/superadmin/agencias':
        App::requireRole('superadmin');
        include 'pages/superadmin_agencias.php';
        break;

    case '/superadmin/agencias/detalle':
        App::requireRole('superadmin');
        include 'pages/superadmin_agencia_detalle.php';
        break;

    case '/modules/superadmin/agencias_api.php':
        App::requireRole('superadmin');
        include 'modules/superadmin/agencias_api.php';
        break;

    case '/superadmin/usuarios':
        App::requireRole('superadmin');
        include 'pages/superadmin_usuarios_agencia.php';
        break;

    case '/modules/superadmin/usuarios_api.php':
        App::requireRole('superadmin');
        include 'modules/superadmin/usuarios_api.php';
        break;


    case '/superadmin/gestionar-superadmins':
        App::requireRole('superadmin');
        include 'pages/superadmin_gestionar_superadmins.php';
        break;

    case '/biblioteca':
        App::requireLogin();
        include 'pages/biblioteca.php';
        break;

    case '/biblioteca/api':
        App::requireLogin();
        include 'modules/biblioteca/api.php';
        break;

    case '/programa':
        App::requireLogin();
        include 'pages/programa.php';
        break;

    case '/programa/api':
        App::requireLogin();
        include 'modules/programa/api.php';
        break;

    case '/itinerarios':
        App::requireLogin();
        include 'pages/itinerarios.php';
        break;

    case (preg_match('/^\/itinerarios\/(\d+)$/', $path, $matches) ? true : false):
        App::requireLogin();
        $_GET['id'] = $matches[1];
        include 'pages/itinerarios.php';
        break;

    case '/administrador':
    case '/administrador/usuarios':
        App::requireRole('admin');
        include 'pages/admin.php';
        break;

    case '/administrador/configuracion':
        App::requireRole('admin');
        include 'pages/admin_config.php';
        break;

    case '/admin/api':
        App::requireRole('admin');
        include 'modules/admin/api.php';
        break;

    case '/perfil':
        App::requireLogin();
        // Solo permitir acceso a agentes
        $user = App::getUser();
        if ($user['role'] !== 'agent') {
            App::redirect('/dashboard');
            exit;
        }
        include 'pages/perfil.php';
        break;

    case '/perfil/api':
        App::requireLogin();
        // Solo permitir acceso a agentes  
        $user = App::getUser();
        if ($user['role'] !== 'agent') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
            exit;
        }
        include 'modules/perfil/api.php';
        break;

    case '/share':
        include 'share.php';
        break;

    case '/preview':
        require_once 'pages/preview.php';
        break;

    case '/itinerary':
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            header('Location: ' . APP_URL . '/itinerarios');
            exit;
        }
        require_once 'pages/itinerary.php';
        break;


    case '/itinerario':
    case '/mis-itinerarios':
    case '/viajes':
        App::redirect('/itinerarios');
        break;

    case '/mi-programa':
        App::redirect('/programa');
        break;

    case '/biblioteca-destinos':
    case '/destinos':
        App::redirect('/biblioteca');
        break;
    case '/pipeline':
        App::requireLogin();
        include 'pages/pipeline.php';
        break;

    case '/pipeline/api':
        App::requireLogin();
        include 'modules/pipeline/api.php';
        break;

    case '/chat':
        App::requireLogin();
        include 'pages/chat.php';
        break;

    case '/gmail/oauth':
        require_once 'modules/gmail/oauth.php';
        break;

    case '/gmail/api':
        require_once 'modules/gmail/api.php';
        break;

    case '/gmail/worker':
        require_once 'modules/gmail/worker.php';
        break;

    case '/gmail/chat':
    case '/gmail/chat/send':
        require_once 'modules/gmail/chat_api.php';
        break;

    default:
        http_response_code(404);
        include 'pages/404.php';
        break;
}