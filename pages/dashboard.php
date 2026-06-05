<?php
// =====================================
// ARCHIVO: pages/dashboard.php - Dashboard con Componentes Recursivos Mejorados
// =====================================

// Incluir constantes necesarias
require_once __DIR__ . '/../config/constants.php';

// Incluir sistema de componentes
require_once __DIR__ . '/../includes/ui_components.php';

$user = App::getUser(); 
$userColors = App::getColorsForRole($user['role']);
$companyName = App::getCompanyName();
$defaultLanguage = App::getDefaultLanguage();

// ===== OBTENER DATOS DE LA AGENCIA (para admins y agents) =====
$agenciaData = null;
if ($user['role'] !== 'superadmin' && isset($_SESSION['agencia_id'])) {
    try {
        $db = Database::getInstance();
        $agenciaData = $db->fetch(
            "SELECT nombre, fecha_inicio_suscripcion, fecha_fin_suscripcion, 
                    max_usuarios, estado_suscripcion
             FROM agencias 
             WHERE id = ?",
            [$_SESSION['agencia_id']]
        );
        
        // Contar usuarios activos de la agencia
        if ($agenciaData) {
            $usuariosCount = $db->fetch(
                "SELECT COUNT(*) as total FROM users WHERE agencia_id = ? AND active = 1",
                [$_SESSION['agencia_id']]
            );
            $agenciaData['usuarios_actuales'] = $usuariosCount['total'] ?? 0;
        }
        
    } catch(Exception $e) {
        error_log("Error obteniendo datos de agencia: " . $e->getMessage());
    }
}

// Inicializar conexión a base de datos para las estadísticas
try {
    $db = Database::getInstance();
} catch(Exception $e) {
    $db = null;
}

// ── Dashboard stats & activity ──────────────────────────
$agenciaId  = $_SESSION['agencia_id'] ?? null;
$userId     = (int)($user['id'] ?? 0);
$dbStats    = ['leads'=>0,'itinerarios'=>0,'recursos'=>0,'usuarios'=>0];
$recentLeads = [];

// ¿Este usuario ve el módulo de Rooming? (admin siempre; agente según config de agencia)
$canRooming = ($user['role']==='admin')
    || ($user['role']==='agent' && class_exists('ConfigManager') && ConfigManager::roomingAgentesVisible());
$roomingAlerts = [];

if ($db && $agenciaId) {
    try {
        $dbStats['leads'] = (int)($db->fetch(
            "SELECT COUNT(*) as t FROM pipeline WHERE agencia_id = ?", [$agenciaId])['t'] ?? 0);

        $qItin = $user['role']==='admin'
            ? ["SELECT COUNT(*) as t FROM programa_solicitudes WHERE agencia_id = ?", [$agenciaId]]
            : ["SELECT COUNT(*) as t FROM programa_solicitudes WHERE agencia_id = ? AND user_id = ?", [$agenciaId,$userId]];
        $dbStats['itinerarios'] = (int)($db->fetch($qItin[0], $qItin[1])['t'] ?? 0);

        $rec = 0;
        foreach (['biblioteca_dias','biblioteca_alojamientos','biblioteca_actividades','biblioteca_transportes'] as $tbl) {
            $rec += (int)($db->fetch("SELECT COUNT(*) as t FROM $tbl WHERE agencia_id = ? AND activo = 1", [$agenciaId])['t'] ?? 0);
        }
        $dbStats['recursos'] = $rec;

        if ($user['role']==='admin') {
            $dbStats['usuarios'] = (int)($db->fetch(
                "SELECT COUNT(*) as t FROM users WHERE agencia_id = ? AND active = 1", [$agenciaId])['t'] ?? 0);
        }

        $leadWhere = $user['role']==='admin' ? "p.agencia_id = ?" : "p.agencia_id = ? AND p.usuario_id = ?";
        $leadParams = $user['role']==='admin' ? [$agenciaId] : [$agenciaId,$userId];
        $recentLeads = $db->fetchAll(
            "SELECT p.id, p.nombre_cliente, p.destino, p.created_at, e.nombre as estado_nombre
             FROM pipeline p
             LEFT JOIN pipeline_estados e ON p.estado_id = e.id
             WHERE $leadWhere
             ORDER BY p.created_at DESC LIMIT 5",
            $leadParams
        ) ?: [];

        // Alertas de Rooming: reservas vendidas que viajan pronto (≤7 días) con
        // traslados incompletos (OUT sin hora de recogida o algún traslado sin operador).
        if ($canRooming) {
            $rWhere  = "ps.agencia_id = ? AND ps.comprado = 1";
            $rParams = [$agenciaId];
            if ($user['role'] !== 'admin') { $rWhere .= " AND ps.user_id = ?"; $rParams[] = $userId; }
            $roomingAlerts = $db->fetchAll(
                "SELECT ps.id, ps.id_solicitud, ps.destino, ps.fecha_llegada,
                        DATEDIFF(DATE(ps.fecha_llegada), CURDATE()) AS dias,
                        SUM(CASE WHEN r.service_type='llevada_al_aeropuerto' AND r.pickup_time IS NULL THEN 1 ELSE 0 END) AS sin_hora,
                        SUM(CASE WHEN NOT EXISTS(SELECT 1 FROM asignacion_operadores ao WHERE ao.rooming_id=r.id) THEN 1 ELSE 0 END) AS sin_operador
                 FROM programa_solicitudes ps
                 JOIN rooming r ON r.solicitud_id = ps.id
                 WHERE $rWhere
                   AND ps.fecha_llegada IS NOT NULL
                   AND DATE(ps.fecha_llegada) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                 GROUP BY ps.id
                 HAVING sin_hora > 0 OR sin_operador > 0
                 ORDER BY dias ASC",
                $rParams
            ) ?: [];
        }
    } catch (Exception $e) {
        error_log('Dashboard stats error: '.$e->getMessage());
    }
}


function dashboardIcon($name) {
    $icons = [
        'users' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 11c1.66 0 3-1.57 3-3.5S17.66 4 16 4s-3 1.57-3 3.5 1.34 3.5 3 3.5Zm-8 0c1.66 0 3-1.57 3-3.5S9.66 4 8 4 5 5.57 5 7.5 6.34 11 8 11Zm0 2c-2.67 0-6 1.34-6 4v1.2c0 .44.36.8.8.8h10.4c.44 0 .8-.36.8-.8V17c0-2.66-3.33-4-6-4Zm8 0c-.33 0-.7.02-1.08.07 1.26.92 2.08 2.18 2.08 3.93v2h4.2c.44 0 .8-.36.8-.8V17c0-2.66-3.33-4-6-4Z"/></svg>',
        'settings' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19.43 12.98c.04-.32.07-.65.07-.98s-.02-.66-.07-.98l2.11-1.65c.19-.15.24-.42.12-.64l-2-3.46a.5.5 0 0 0-.61-.22l-2.49 1a7.28 7.28 0 0 0-1.69-.98L14.5 2.42A.49.49 0 0 0 14.01 2h-4a.49.49 0 0 0-.49.42L9.14 5.07c-.61.24-1.18.56-1.69.98l-2.49-1a.5.5 0 0 0-.61.22l-2 3.46a.5.5 0 0 0 .12.64l2.11 1.65c-.04.32-.08.65-.08.98s.03.66.08.98l-2.11 1.65a.5.5 0 0 0-.12.64l2 3.46c.13.22.39.31.61.22l2.49-1c.51.4 1.08.74 1.69.98l.38 2.65c.04.24.25.42.49.42h4c.24 0 .45-.18.49-.42l.38-2.65c.61-.24 1.18-.56 1.69-.98l2.49 1c.23.08.48 0 .61-.22l2-3.46a.5.5 0 0 0-.12-.64l-2.11-1.65ZM12 15.5A3.5 3.5 0 1 1 12 8a3.5 3.5 0 0 1 0 7.5Z"/></svg>',
        'library' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5.5C4 4.67 4.67 4 5.5 4H20v14.5c0 .83-.67 1.5-1.5 1.5H5.75A2.75 2.75 0 0 1 3 17.25V6.5c0-.55.45-1 1-1Zm2 0v11.75c0 .41.34.75.75.75H18V6H6v-.5Zm2 3h8v1.6H8V8.5Zm0 3h8v1.6H8v-1.6Zm0 3h5v1.6H8v-1.6Z"/></svg>',
        'plane' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 16v-2L13 9V3.5a1.5 1.5 0 0 0-3 0V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5l8 2.5Z"/></svg>',
        'pipeline' => '<svg viewBox="0 0 24 24" aria-hidden="true" fill="currentColor"><path d="M3 3h4v18H3V3zm7 0h4v11h-4V3zm7 0h4v7h-4V3z"/></svg>',
        'map' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m20.5 3-.16.03L15 5.1 9 3 3.36 4.9A.5.5 0 0 0 3 5.38V20.5c0 .35.35.6.68.47L9 18.9l6 2.1 5.64-1.9a.5.5 0 0 0 .36-.48V3.5c0-.28-.22-.5-.5-.5ZM10 5.47l4 1.4v11.66l-4-1.4V5.47Zm-5 1.08 3-1.01v11.92l-3 1.1V6.55Zm14 11.9-3 1.01V7.54l3-1.1v12.01Z"/></svg>',
        'profile' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12c2.21 0 4-2.02 4-4.5S14.21 3 12 3 8 5.02 8 7.5s1.79 4.5 4 4.5Zm0 2c-3.33 0-7 1.7-7 4.25V20c0 .55.45 1 1 1h12c.55 0 1-.45 1-1v-1.75C19 15.7 15.33 14 12 14Z"/></svg>',
        'rooming' => '<svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 17h2l1-5h12l1 5h2"/><circle cx="7.5" cy="17" r="2"/><circle cx="16.5" cy="17" r="2"/><path d="M6 12V7a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v5"/></svg>',
    ];
    return $icons[$name] ?? $icons['plane'];
}
?>
<!DOCTYPE html>
<html lang="<?= $defaultLanguage ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= htmlspecialchars($companyName) ?></title>
    
    <?= UIComponents::getComponentStyles() ?>
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        :root {
            --primary-color: <?= $userColors['primary'] ?>;
            --secondary-color: <?= $userColors['secondary'] ?>;
            --primary-gradient: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            
            /* Convertir colores hex a RGB para usar con opacity */
            --primary-color-rgb: <?= implode(', ', sscanf($userColors['primary'], "#%02x%02x%02x")) ?>;
            --secondary-color-rgb: <?= implode(', ', sscanf($userColors['secondary'], "#%02x%02x%02x")) ?>;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            color: #333;
            min-height: 100vh;
        }

        
        /* Header mejorado */
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
            gap: 20px;
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

        /* Google Translate mejorado */
        /* Google Translate en la esquina */
        /* ===== MEJORAR EL SELECTOR DE GOOGLE TRANSLATE ===== */

        /* Contenedor principal */
        .translate-container {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }

        /* Caja del widget */
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

        /* Ocultar el icono de Google */
        .goog-te-gadget-icon {
            display: none !important;
        }

        /* Contenedor del gadget */
        .goog-te-gadget-simple {
            background: transparent !important;
            border: none !important;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
        }

        /* El enlace principal */
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

        /* El texto "Seleccionar idioma" */
        .VIpgJd-ZVi9od-xl07Ob-lTBxed span:first-child {
            color: inherit !important;
            font-weight: inherit !important;
        }

        /* Ocultar las imágenes separadoras */
        .VIpgJd-ZVi9od-xl07Ob-lTBxed img {
            display: none !important;
        }

        /* Ocultar el separador */
        .VIpgJd-ZVi9od-xl07Ob-lTBxed span[style*="border-left"] {
            display: none !important;
        }

        /* Mejorar la flecha */
        .VIpgJd-ZVi9od-xl07Ob-lTBxed span[aria-hidden="true"] {
            color: #6b7280 !important;
            font-size: 12px !important;
            margin-left: 4px !important;
            transition: all 0.2s ease !important;
        }

        .VIpgJd-ZVi9od-ORHb-OEVmcd {
            left: 0;
            display: none !important;
            top: 0;
        }

        .VIpgJd-ZVi9od-xl07Ob-lTBxed:hover span[aria-hidden="true"] {
            color: #667eea !important;
            transform: translateY(1px) !important;
        }

        /* Menú desplegable cuando aparece */
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

        /* Items de la lista */
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

        /* Ocultar banner azul */
        .goog-te-banner-frame.skiptranslate { 
            display: none !important; 
        }

        body { 
            top: 0px !important; 
        }

        /* Responsive */
        @media (max-width: 768px) {
            .translate-container {
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
        }
        
        .goog-te-gadget img {
            vertical-align: middle;
            border: none;
            display: none;
        }

        /* Main Content mejorado */
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

        /* Welcome Section mejorada */
        .welcome-section {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(255, 255, 255, 0.7) 100%);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 40px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(20px);
            position: relative;
            overflow: hidden;
        }

        .welcome-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-gradient);
        }

        .welcome-title {
            font-size: 32px;
            color: #2d3748;
            margin-bottom: 15px;
            font-weight: 700;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .welcome-subtitle {
            color: #718096;
            font-size: 16px;
            line-height: 1.6;
        }

        /* Role Badge mejorado */
        .role-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 25px;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 20px;
            background: var(--primary-gradient);
            color: white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        /* Quick Actions mejoradas */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .action-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(255, 255, 255, 0.7) 100%);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(20px);
        }

        .action-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-gradient);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .action-card:hover::before {
            transform: scaleX(1);
        }

        .action-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }

        .action-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin-bottom: 20px;
            background: var(--primary-gradient);
            color: white;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .action-title {
            font-size: 20px;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 10px;
        }

        .action-description {
            color: #718096;
            font-size: 14px;
            line-height: 1.6;
        }

        /* Stats Section mejorada */
        .stats-section {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(255, 255, 255, 0.7) 100%);
            border-radius: 20px;
            padding: 35px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .stats-title {
            font-size: 24px;
            color: #2d3748;
            margin-bottom: 30px;
            font-weight: 700;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
        }

        .stat-item {
            text-align: center;
            padding: 25px 20px;
            background: rgba(255, 255, 255, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 16px;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .stat-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            background: rgba(255, 255, 255, 0.8);
        }

        .stat-number {
            font-size: 28px;
            font-weight: 800;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 12px;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        /* Overlay mejorado */
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

        /* Responsive mejorado */
        @media (max-width: 768px) {
            .header {
                padding: 15px 20px;
            }

            .main-content {
                padding: 20px;
            }

            .main-content.sidebar-open {
                margin-left: 0;
            }

            .quick-actions {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .welcome-title {
                font-size: 26px;
            }

            .welcome-section, .action-card, .stats-section {
                padding: 25px;
            }
        }

        /* Animaciones de entrada */
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-in {
            animation: slideInUp 0.6s ease forwards;
        }

        /* Fondo elegante y sencillo - Solo tonos blancos */
        .background-particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: linear-gradient(135deg, #ffffff 0%, #f1f5f9 50%, #e2e8f0 100%);
        }

        .background-particles::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                radial-gradient(circle at 25% 25%, rgba(248, 250, 252, 0.8) 0%, transparent 50%),
                radial-gradient(circle at 75% 75%, rgba(241, 245, 249, 0.6) 0%, transparent 50%);
            animation: float 30s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }


        /* ===== Dashboard rediseño ===== */
        body{background:radial-gradient(circle at top left,rgba(var(--primary-color-rgb),.06),transparent 34rem),radial-gradient(circle at bottom right,rgba(var(--secondary-color-rgb),.05),transparent 30rem),#f4f6fb;color:#172033;}
        .background-particles{display:none;}
        .main-content{padding:28px 32px;display:flex;flex-direction:column;gap:22px;}

        /* Hero header */
        .db-hero{background:#fff;border-radius:22px;border:1px solid rgba(23,32,51,.08);box-shadow:0 4px 24px rgba(15,23,42,.06);padding:28px 32px;display:flex;align-items:center;justify-content:space-between;gap:24px;flex-wrap:wrap;}
        .db-hero-left{}
        .db-role-pill{display:inline-flex;align-items:center;gap:7px;padding:5px 14px;background:rgba(var(--primary-color-rgb),.09);color:var(--primary-color);border:1px solid rgba(var(--primary-color-rgb),.16);border-radius:999px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;margin-bottom:12px;}
        .db-role-pill::before{content:'';width:7px;height:7px;border-radius:50%;background:var(--primary-color);box-shadow:0 0 0 3px rgba(var(--primary-color-rgb),.15);}
        .db-greeting{font-size:clamp(22px,3vw,30px);font-weight:800;color:#0f172a;letter-spacing:-.03em;margin:0 0 4px;}
        .db-subtitle{font-size:14px;color:#667085;margin:0;}

        /* Stats strip */
        .db-stats-row{display:flex;gap:12px;flex-wrap:wrap;}
        .db-stat{background:#f8fafc;border:1px solid rgba(23,32,51,.07);border-radius:14px;padding:14px 20px;min-width:110px;text-align:center;transition:all .2s;}
        .db-stat:hover{background:#fff;box-shadow:0 6px 18px rgba(15,23,42,.07);transform:translateY(-2px);}
        .db-stat-n{font-size:26px;font-weight:800;color:var(--primary-color);line-height:1;margin-bottom:4px;}
        .db-stat-l{font-size:11px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.4px;}

        /* Action cards grid */
        .db-rooming-alerts{background:#fff;border:1px solid rgba(23,32,51,.08);border-left:4px solid #f59e0b;border-radius:16px;padding:18px 20px;box-shadow:0 2px 8px rgba(15,23,42,.04);}
        .db-ra-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px;flex-wrap:wrap;}
        .db-ra-title{font-size:14px;font-weight:800;color:#0f172a;}
        .db-ra-list{display:flex;flex-direction:column;gap:8px;}
        .db-ra-row{display:flex;align-items:center;gap:12px;padding:10px 12px;border-radius:11px;background:#f8fafc;text-decoration:none;transition:background .15s,transform .12s;}
        .db-ra-row:hover{background:#f1f5f9;transform:translateX(2px);}
        .db-ra-dot{width:9px;height:9px;border-radius:50%;flex-shrink:0;}
        .db-ra-row.sev-red .db-ra-dot{background:#ef4444;}
        .db-ra-row.sev-amber .db-ra-dot{background:#f59e0b;}
        .db-ra-info{flex:1;min-width:0;}
        .db-ra-name{font-size:13px;font-weight:700;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
        .db-ra-sub{font-size:12px;color:#64748b;}
        .db-ra-badge{font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;white-space:nowrap;}
        .db-ra-row.sev-red .db-ra-badge{background:#fee2e2;color:#b91c1c;}
        .db-ra-row.sev-amber .db-ra-badge{background:#fef9c3;color:#a16207;}
        .db-actions{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;}
        .action-card{background:#fff;border:1px solid rgba(23,32,51,.08);border-radius:18px;padding:22px;cursor:pointer;position:relative;overflow:hidden;transition:all .25s;box-shadow:0 2px 8px rgba(15,23,42,.04);}
        .action-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:var(--primary-gradient);opacity:.9;}
        .action-card:hover{transform:translateY(-4px);box-shadow:0 16px 40px rgba(15,23,42,.10);}
        .action-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;background:rgba(var(--primary-color-rgb),.10);color:var(--primary-color);margin-bottom:14px;}
        .action-icon svg{width:22px;height:22px;fill:currentColor;}
        .action-title{font-size:15px;font-weight:700;color:#172033;margin:0 0 5px;}
        .action-description{font-size:13px;color:#667085;line-height:1.45;margin:0;}

        /* Bottom row */
        .db-bottom{display:grid;grid-template-columns:1fr 360px;gap:16px;align-items:start;}
        @media(max-width:900px){.db-bottom{grid-template-columns:1fr;}}

        /* Recent activity */
        .db-activity{background:#fff;border:1px solid rgba(23,32,51,.08);border-radius:18px;padding:22px;box-shadow:0 2px 8px rgba(15,23,42,.04);}
        .db-section-title{font-size:15px;font-weight:700;color:#0f172a;margin:0 0 16px;display:flex;align-items:center;justify-content:space-between;}
        .db-section-link{font-size:13px;font-weight:600;color:var(--primary-color);text-decoration:none;opacity:.8;}
        .db-section-link:hover{opacity:1;}
        .db-lead-row{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid #f1f5f9;}
        .db-lead-row:last-child{border-bottom:none;padding-bottom:0;}
        .db-lead-av{width:36px;height:36px;border-radius:50%;background:rgba(var(--primary-color-rgb),.10);color:var(--primary-color);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0;}
        .db-lead-info{flex:1;min-width:0;}
        .db-lead-name{font-size:14px;font-weight:600;color:#172033;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
        .db-lead-meta{font-size:12px;color:#94a3b8;margin-top:1px;}
        .db-lead-badge{font-size:11px;font-weight:600;padding:3px 9px;border-radius:20px;background:rgba(var(--primary-color-rgb),.1);color:var(--primary-color);white-space:nowrap;flex-shrink:0;}
        .db-empty{text-align:center;padding:28px;color:#cbd5e1;font-size:14px;}

        /* Side info card */
        .db-side-info{background:#fff;border:1px solid rgba(23,32,51,.08);border-radius:18px;padding:22px;box-shadow:0 2px 8px rgba(15,23,42,.04);}
        .db-info-row{display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid #f1f5f9;font-size:14px;}
        .db-info-row:last-child{border-bottom:none;}
        .db-info-label{color:#667085;font-size:13px;}
        .db-info-val{font-weight:700;color:#172033;}
        .db-info-val.ok{color:#16a34a;}
        .db-info-val.warn{color:#dc2626;}
        .db-tip{background:#f8fafc;border-radius:10px;padding:12px 14px;font-size:13px;color:#475569;line-height:1.5;margin-bottom:10px;}
        .db-tip:last-child{margin-bottom:0;}
        .db-tip strong{color:var(--primary-color);}

        @media(max-width:768px){
            .main-content{padding:16px;gap:16px;}
            .db-hero{padding:20px;flex-direction:column;align-items:flex-start;}
            .db-actions{grid-template-columns:repeat(auto-fill,minmax(160px,1fr));}
            .db-stats-row{gap:8px;}
            .db-stat{padding:10px 14px;min-width:90px;}
        }


/* ===== Sidebar limpia y profesional ===== */

.sidebar {
    background: #ffffff !important;
    border-right: 1px solid #e5e7eb !important;
    box-shadow: 8px 0 30px rgba(15, 23, 42, 0.08) !important;
}

.sidebar-header,
.sidebar .logo-section {
    background: linear-gradient(
        135deg,
        rgba(var(--primary-color-rgb), 0.08),
        rgba(var(--secondary-color-rgb), 0.06)
    ) !important;
    border-bottom: 1px solid #e5e7eb !important;
    padding: 24px 22px !important;
}

.sidebar h2,
.sidebar .logo-text,
.sidebar .brand-name {
    color: #111827 !important;
    font-size: 18px !important;
    font-weight: 800 !important;
    letter-spacing: -0.03em !important;
}

.sidebar-menu,
.sidebar nav {
    padding: 18px 14px !important;
}

.sidebar a,
.sidebar .menu-item,
.sidebar .nav-item {
    display: flex !important;
    align-items: center !important;
    gap: 12px !important;
    min-height: 46px !important;
    padding: 12px 14px !important;
    margin-bottom: 6px !important;
    border-radius: 14px !important;
    color: #475569 !important;
    font-size: 14px !important;
    font-weight: 600 !important;
    text-decoration: none !important;
    background: transparent !important;
    border: 1px solid transparent !important;
    transition: all 0.2s ease !important;
}

.sidebar a:hover,
.sidebar .menu-item:hover,
.sidebar .nav-item:hover {
    background: rgba(var(--primary-color-rgb), 0.08) !important;
    color: var(--primary-color) !important;
    border-color: rgba(var(--primary-color-rgb), 0.12) !important;
    transform: translateX(3px) !important;
}

.sidebar a.active,
.sidebar .menu-item.active,
.sidebar .nav-item.active {
    background: linear-gradient(
        135deg,
        rgba(var(--primary-color-rgb), 0.14),
        rgba(var(--secondary-color-rgb), 0.10)
    ) !important;
    color: var(--primary-color) !important;
    border-color: rgba(var(--primary-color-rgb), 0.20) !important;
    box-shadow: 0 8px 20px rgba(var(--primary-color-rgb), 0.12) !important;
}

.sidebar svg {
    width: 18px !important;
    height: 18px !important;
    flex-shrink: 0 !important;
    fill: currentColor !important;
    color: currentColor !important;
}

.sidebar-footer {
    border-top: 1px solid #e5e7eb !important;
    padding: 16px 14px !important;
    background: #f8fafc !important;
}

.overlay {
    background: rgba(15, 23, 42, 0.35) !important;
    backdrop-filter: blur(2px) !important;
}
        
    </style>
</head>
<body>
    <div class="background-particles"></div>
    
    <!-- Header con componente recursivo -->
    <?= UIComponents::renderHeader($user) ?>

    <!-- Sidebar con componente recursivo mejorado -->
    <?= UIComponents::renderSidebar($user, '/dashboard') ?>

    <!-- Overlay -->
    <div class="overlay" id="overlay" onclick="closeSidebar()"></div>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">

        <!-- Hero header con stats inline -->
        <div class="db-hero animate-in">
            <div class="db-hero-left">
                <div class="db-role-pill">
                    <?= $user['role']==='admin' ? 'Administrador' : 'Agente de Viajes' ?>
                </div>
                <h1 class="db-greeting">Hola, <?= htmlspecialchars($user['name']) ?> 👋</h1>
                <p class="db-subtitle">
                    <?= $user['role']==='admin' ? 'Panel de control de la agencia' : 'Aquí está el resumen de tu actividad' ?>
                </p>
            </div>
            <div class="db-stats-row">
                <div class="db-stat">
                    <div class="db-stat-n"><?= $dbStats['leads'] ?></div>
                    <div class="db-stat-l">Leads</div>
                </div>
                <div class="db-stat">
                    <div class="db-stat-n"><?= $dbStats['itinerarios'] ?></div>
                    <div class="db-stat-l">Programas</div>
                </div>
                <div class="db-stat">
                    <div class="db-stat-n"><?= $dbStats['recursos'] ?></div>
                    <div class="db-stat-l">Recursos</div>
                </div>
                <?php if ($user['role']==='admin'): ?>
                <div class="db-stat">
                    <div class="db-stat-n"><?= $dbStats['usuarios'] ?></div>
                    <div class="db-stat-l">Usuarios</div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($roomingAlerts)): ?>
        <!-- Alertas de Rooming: viajes próximos con traslados incompletos -->
        <div class="db-rooming-alerts animate-in">
            <div class="db-ra-head">
                <span class="db-ra-title">⚠ Traslados por completar — viajes próximos</span>
                <a class="db-section-link" href="<?= APP_URL ?>/rooming">Ir a Rooming →</a>
            </div>
            <div class="db-ra-list">
                <?php foreach ($roomingAlerts as $a):
                    $dias = (int)$a['dias'];
                    $sev  = $dias <= 3 ? 'red' : 'amber';
                    $cuando = $dias <= 0 ? 'hoy' : ($dias === 1 ? 'mañana' : "en $dias días");
                    $faltan = [];
                    if ((int)$a['sin_hora'] > 0)     $faltan[] = (int)$a['sin_hora'].' sin hora de recogida';
                    if ((int)$a['sin_operador'] > 0) $faltan[] = (int)$a['sin_operador'].' sin operador';
                ?>
                <a class="db-ra-row sev-<?= $sev ?>" href="<?= APP_URL ?>/rooming">
                    <span class="db-ra-dot"></span>
                    <div class="db-ra-info">
                        <div class="db-ra-name"><?= htmlspecialchars($a['id_solicitud'] ?: ('Reserva #'.$a['id'])) ?> · <?= htmlspecialchars($a['destino'] ?? '') ?></div>
                        <div class="db-ra-sub">Viaja <?= $cuando ?> · <?= htmlspecialchars(implode(' · ', $faltan)) ?></div>
                    </div>
                    <span class="db-ra-badge"><?= $cuando ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Acciones rápidas -->
        <div class="db-actions">
            <?php if ($user['role']==='admin'): ?>
            <div class="action-card animate-in" onclick="goTo('/pipeline')" style="animation-delay:.05s">
                <div class="action-icon"><?= dashboardIcon('pipeline') ?></div>
                <h3 class="action-title">Pipeline de Ventas</h3>
                <p class="action-description">CRM de leads y comunicación con clientes.</p>
            </div>
            <div class="action-card animate-in" onclick="goTo('/itinerarios')" style="animation-delay:.1s">
                <div class="action-icon"><?= dashboardIcon('plane') ?></div>
                <h3 class="action-title">Programas</h3>
                <p class="action-description">Todos los itinerarios de la agencia.</p>
            </div>
            <div class="action-card animate-in" onclick="goTo('/rooming')" style="animation-delay:.12s">
                <div class="action-icon"><?= dashboardIcon('rooming') ?></div>
                <h3 class="action-title">Traslados / Rooming</h3>
                <p class="action-description">Logística de traslados de aeropuerto.</p>
            </div>
            <div class="action-card animate-in" onclick="goTo('/administrador')" style="animation-delay:.15s">
                <div class="action-icon"><?= dashboardIcon('users') ?></div>
                <h3 class="action-title">Usuarios</h3>
                <p class="action-description">Gestiona agentes y permisos.</p>
            </div>
            <div class="action-card animate-in" onclick="goTo('/biblioteca')" style="animation-delay:.2s">
                <div class="action-icon"><?= dashboardIcon('library') ?></div>
                <h3 class="action-title">Biblioteca</h3>
                <p class="action-description">Días, alojamientos, actividades, transportes.</p>
            </div>
            <div class="action-card animate-in" onclick="goTo('/administrador/configuracion')" style="animation-delay:.25s">
                <div class="action-icon"><?= dashboardIcon('settings') ?></div>
                <h3 class="action-title">Configuración</h3>
                <p class="action-description">Colores, Gmail, pipeline, parámetros.</p>
            </div>
            <?php else: ?>
            <div class="action-card animate-in" onclick="goTo('/pipeline')" style="animation-delay:.05s">
                <div class="action-icon"><?= dashboardIcon('pipeline') ?></div>
                <h3 class="action-title">Pipeline de Ventas</h3>
                <p class="action-description">Tus leads y conversaciones con clientes.</p>
            </div>
            <div class="action-card animate-in" onclick="goTo('/itinerarios')" style="animation-delay:.1s">
                <div class="action-icon"><?= dashboardIcon('map') ?></div>
                <h3 class="action-title">Mis Itinerarios</h3>
                <p class="action-description">Crea y gestiona programas de viaje.</p>
            </div>
            <div class="action-card animate-in" onclick="goTo('/biblioteca')" style="animation-delay:.15s">
                <div class="action-icon"><?= dashboardIcon('library') ?></div>
                <h3 class="action-title">Biblioteca</h3>
                <p class="action-description">Tus recursos: días, alojamientos, servicios.</p>
            </div>
            <?php if ($canRooming): ?>
            <div class="action-card animate-in" onclick="goTo('/rooming')" style="animation-delay:.18s">
                <div class="action-icon"><?= dashboardIcon('rooming') ?></div>
                <h3 class="action-title">Traslados / Rooming</h3>
                <p class="action-description">Logística de traslados de aeropuerto.</p>
            </div>
            <?php endif; ?>
            <div class="action-card animate-in" onclick="goTo('/perfil')" style="animation-delay:.2s">
                <div class="action-icon"><?= dashboardIcon('profile') ?></div>
                <h3 class="action-title">Mi Perfil</h3>
                <p class="action-description">Contraseña, Gmail y preferencias.</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Fila inferior: actividad reciente + info lateral -->
        <div class="db-bottom">
            <!-- Leads recientes -->
            <div class="db-activity animate-in" style="animation-delay:.3s">
                <div class="db-section-title">
                    Leads recientes
                    <a class="db-section-link" href="<?= APP_URL ?>/pipeline">Ver todos →</a>
                </div>
                <?php if (empty($recentLeads)): ?>
                <div class="db-empty">No hay leads todavía.<br>¡Empieza creando uno en el pipeline!</div>
                <?php else: ?>
                <?php foreach ($recentLeads as $lead):
                    $initials = strtoupper(substr($lead['nombre_cliente']??'?',0,1).substr(strrchr($lead['nombre_cliente']??'',''),0,1));
                    $fechaFmt = $lead['created_at'] ? date('d M',strtotime($lead['created_at'])) : '—';
                ?>
                <div class="db-lead-row" onclick="window.location.href='<?= APP_URL ?>/pipeline'" style="cursor:pointer">
                    <div class="db-lead-av"><?= htmlspecialchars($initials) ?></div>
                    <div class="db-lead-info">
                        <div class="db-lead-name"><?= htmlspecialchars($lead['nombre_cliente']) ?></div>
                        <div class="db-lead-meta"><?= htmlspecialchars($lead['destino']??'Sin destino') ?> · <?= $fechaFmt ?></div>
                    </div>
                    <?php if ($lead['estado_nombre']): ?>
                    <span class="db-lead-badge"><?= htmlspecialchars($lead['estado_nombre']) ?></span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Info lateral -->
            <div class="db-side-info animate-in" style="animation-delay:.35s">
                <?php if ($user['role']==='admin' && $agenciaData): ?>
                <div class="db-section-title">Suscripción</div>
                <?php
                    $diasRestantes = ceil((strtotime($agenciaData['fecha_fin_suscripcion'])-time())/86400);
                    $suscOk = $diasRestantes > 7;
                ?>
                <div class="db-info-row">
                    <span class="db-info-label">Agencia</span>
                    <span class="db-info-val"><?= htmlspecialchars($agenciaData['nombre']) ?></span>
                </div>
                <div class="db-info-row">
                    <span class="db-info-label">Vence</span>
                    <span class="db-info-val <?= $suscOk?'ok':'warn' ?>">
                        <?= date('d/m/Y',strtotime($agenciaData['fecha_fin_suscripcion'])) ?>
                    </span>
                </div>
                <div class="db-info-row">
                    <span class="db-info-label">Días restantes</span>
                    <span class="db-info-val <?= $suscOk?'ok':'warn' ?>"><?= max(0,$diasRestantes) ?></span>
                </div>
                <div class="db-info-row">
                    <span class="db-info-label">Usuarios</span>
                    <span class="db-info-val"><?= $agenciaData['usuarios_actuales'] ?> / <?= $agenciaData['max_usuarios'] ?></span>
                </div>
                <?php else: ?>
                <div class="db-section-title">Tips rápidos</div>
                <div class="db-tip"><strong>Pipeline →</strong> Abre una tarjeta de lead para ver el chat de correo completo.</div>
                <div class="db-tip"><strong>Plantillas →</strong> Usa las plantillas de mensaje para responder más rápido.</div>
                <div class="db-tip"><strong>Vincular →</strong> Asocia un itinerario a cada lead para tener todo en un lugar.</div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <script>
        let sidebarOpen = false;
        const DEFAULT_LANGUAGE = '<?= $defaultLanguage ?>';

        // Sidebar functions mejoradas
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

        // Navigation con animaciones
        function goTo(path) {
            // Animación de salida
            document.querySelectorAll('.animate-in').forEach(el => {
                el.style.animation = 'slideInUp 0.3s ease reverse';
            });
            
            setTimeout(() => {
                window.location.href = '<?= APP_URL ?>' + path;
            }, 300);
        }

        function toggleUserMenu() {
            const confirmMessage = '¿Desea cerrar sesión?';
            if (confirm(confirmMessage)) {
                goTo('/auth/logout');
            }
        }

        // Google Translate mejorado
        function googleTranslateElementInit() {
            new google.translate.TranslateElement({
                pageLanguage: DEFAULT_LANGUAGE,
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
            const saved = sessionStorage.getItem('language') || 
                         localStorage.getItem('preferredLanguage') || 
                         DEFAULT_LANGUAGE;
            
            if (saved && saved !== DEFAULT_LANGUAGE) {
                const select = document.querySelector('.goog-te-combo');
                if (select) {
                    select.value = saved;
                    select.dispatchEvent(new Event('change'));
                }
            }
        }

        // Event listeners mejorados
        document.addEventListener('DOMContentLoaded', function() {
            // Detectar cambios de idioma
            setTimeout(function() {
                const select = document.querySelector('.goog-te-combo');
                if (select) {
                    select.addEventListener('change', function() {
                        if (this.value) saveLanguage(this.value);
                    });
                }
            }, 2000);

            // Responsive behavior mejorado
            window.addEventListener('resize', function() {
                if (window.innerWidth <= 768 && sidebarOpen) {
                    document.getElementById('mainContent').classList.remove('sidebar-open');
                } else if (window.innerWidth > 768 && sidebarOpen) {
                    document.getElementById('mainContent').classList.add('sidebar-open');
                }
            });

            // Efectos de hover para las cards
            const actionCards = document.querySelectorAll('.action-card');
            actionCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-8px) scale(1.02)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });

            // Contador animado para estadísticas
            animateCounters();
        });

        // Animación de contadores
        function animateCounters() {
            const counters = document.querySelectorAll('.stat-number');
            
            counters.forEach(counter => {
                const target = parseInt(counter.textContent);
                let current = 0;
                const increment = target / 50;
                
                const updateCounter = () => {
                    if (current < target) {
                        current += increment;
                        counter.textContent = Math.ceil(current);
                        requestAnimationFrame(updateCounter);
                    } else {
                        counter.textContent = target;
                    }
                };
                
                // Iniciar animación cuando el elemento sea visible
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            updateCounter();
                            observer.unobserve(entry.target);
                        }
                    });
                });
                
                observer.observe(counter);
            });
        }

        // Verificar actualizaciones de estadísticas cada 5 minutos
        setInterval(function() {
            <?php if ($user['role'] === 'admin'): ?>
            fetch('<?= APP_URL ?>/admin/api?action=statistics')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateStats(data.data);
                    }
                })
                .catch(error => console.log('Error updating stats:', error));
            <?php endif; ?>
        }, 300000); // 5 minutos

        function updateStats(stats) {
            const statNumbers = document.querySelectorAll('.stat-number');
            if (statNumbers.length >= 4) {
                animateStatUpdate(statNumbers[0], stats.totalUsers);
                animateStatUpdate(statNumbers[1], stats.totalPrograms);
                animateStatUpdate(statNumbers[2], stats.totalResources);
                animateStatUpdate(statNumbers[3], stats.activeSessions);
            }
        }

        function animateStatUpdate(element, newValue) {
            const currentValue = parseInt(element.textContent);
            let current = currentValue;
            const increment = (newValue - currentValue) / 20;
            
            const update = () => {
                if (Math.abs(current - newValue) > Math.abs(increment)) {
                    current += increment;
                    element.textContent = Math.round(current);
                    requestAnimationFrame(update);
                } else {
                    element.textContent = newValue;
                }
            };
            
            update();
        }

        // Sistema de notificaciones mejorado
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <div class="notification-content">
                    <span class="notification-icon">${type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️'}</span>
                    <span class="notification-message">${message}</span>
                </div>
            `;
            
            notification.style.cssText = `
                position: fixed;
                top: 90px;
                right: 20px;
                background: white;
                border-radius: 12px;
                padding: 15px 20px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
                z-index: 10000;
                transform: translateX(400px);
                transition: transform 0.3s ease;
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 100);
            
            setTimeout(() => {
                notification.style.transform = 'translateX(400px)';
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }

        // Verificación del estado del sistema
        function checkSystemStatus() {
            fetch('<?= APP_URL ?>/api/system-status')
                .then(response => response.json())
                .then(data => {
                    if (data.maintenance) {
                        showNotification('El sistema entrará en mantenimiento pronto', 'warning');
                    }
                })
                .catch(error => console.log('System status check failed:', error));
        }

        // Llamar verificaciones al cargar
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(checkSystemStatus, 2000);
        });
    </script>
    
    <script src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
</body>
</html>