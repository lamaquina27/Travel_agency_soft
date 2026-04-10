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
        <!-- Welcome Section -->
        <div class="welcome-section animate-in">
            <div class="role-badge">
                <?= $user['role'] === 'admin' ? '👑 Administrador del Sistema' : '✈️ Agente de Viajes' ?>
            </div>
            <h1 class="welcome-title">¡Bienvenido<?= $user['role'] === 'admin' ? '' : '' ?>, <?= htmlspecialchars($user['name']) ?>!</h1>
            
            <?php if ($agenciaData && $user['role'] === 'admin'): ?>
            <div style="
                background: #ffffff;
                padding: 25px 35px;
                border-radius: 16px;
                margin: 25px 0;
                color: #2d3748;
                display: flex;
                justify-content: space-between;
                flex-wrap: wrap;
                gap: 25px;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04), 0 1px 3px rgba(0, 0, 0, 0.06);
                border: 1px solid #e2e8f0;
            ">
                <div style="flex: 1; min-width: 200px;">
                    <div style="font-size: 13px; color: #718096; margin-bottom: 8px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">🏢 Agencia</div>
                    <div style="font-size: 20px; font-weight: 700; color: #1a202c;"><?= htmlspecialchars($agenciaData['nombre']) ?></div>
                </div>
                
                <div style="flex: 1; min-width: 200px;">
                    <div style="font-size: 13px; color: #718096; margin-bottom: 8px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">📅 Suscripción</div>
                    <div style="font-size: 16px; font-weight: 600; color: #2d3748;">
                        <?= date('d/m/Y', strtotime($agenciaData['fecha_inicio_suscripcion'])) ?> - 
                        <?= date('d/m/Y', strtotime($agenciaData['fecha_fin_suscripcion'])) ?>
                    </div>
                    <div style="font-size: 13px; color: #718096; margin-top: 5px;">
                        <?php 
                        $diasRestantes = ceil((strtotime($agenciaData['fecha_fin_suscripcion']) - time()) / 86400);
                        if ($diasRestantes > 0) {
                            echo "⏱️ " . $diasRestantes . " días restantes";
                        } else {
                            echo "<span style='color: #e53e3e;'>⚠️ Suscripción vencida</span>";
                        }
                        ?>
                    </div>
                </div>
                
                <div style="flex: 1; min-width: 150px;">
                    <div style="font-size: 13px; color: #718096; margin-bottom: 8px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">👥 Usuarios</div>
                    <div style="font-size: 20px; font-weight: 700; color: #1a202c;">
                        <?= $agenciaData['usuarios_actuales'] ?> / <?= $agenciaData['max_usuarios'] ?>
                    </div>
                    <div style="font-size: 13px; color: #718096; margin-top: 5px;">
                        <?= $agenciaData['max_usuarios'] - $agenciaData['usuarios_actuales'] ?> disponibles
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <p class="welcome-subtitle">
                <?php if ($user['role'] === 'admin'): ?>
                    Administra el sistema completo, gestiona usuarios, supervisa todas las operaciones y configura la plataforma desde este panel de control avanzado.
                <?php else: ?>
                    Gestiona tus programas de viaje, crea solicitudes personalizadas para viajeros y administra todos tus recursos desde este panel de control intuitivo.
                <?php endif; ?>
            </p>
        </div>

        <!-- Quick Actions diferenciadas por rol -->
        <div class="quick-actions">
            <?php if ($user['role'] === 'admin'): ?>
            <!-- Acciones para Administrador -->
            <div class="action-card animate-in" onclick="goTo('/administrador')" style="animation-delay: 0.1s;">
                <div class="action-icon">👥</div>
                <h3 class="action-title">Gestión de Usuarios</h3>
                <p class="action-description">Administra todos los usuarios del sistema, crea nuevos agentes, gestiona permisos y supervisa la actividad completa de la plataforma.</p>
            </div>

            <div class="action-card animate-in" onclick="goTo('/administrador/configuracion')" style="animation-delay: 0.2s;">
                <div class="action-icon">⚙️</div>
                <h3 class="action-title">Configuración del Sistema</h3>
                <p class="action-description">Personaliza colores, logos, integraciones, políticas de seguridad y todos los parámetros generales del sistema.</p>
            </div>

            <div class="action-card animate-in" onclick="goTo('/biblioteca')" style="animation-delay: 0.3s;">
                <div class="action-icon">📚</div>
                <h3 class="action-title">Supervisar Biblioteca</h3>
                <p class="action-description">Supervisa y administra todos los recursos globales: días, alojamientos, actividades y transportes de todos los agentes.</p>
            </div>

            <div class="action-card animate-in" onclick="goTo('/itinerarios')" style="animation-delay: 0.4s;">
                <div class="action-icon">✈️</div>
                <h3 class="action-title">Supervisar Programas</h3>
                <p class="action-description">Revisa y supervisa todos los programas de viaje y solicitudes creadas por los agentes del sistema.</p>
            </div>

            <?php else: ?>
            <!-- Acciones para Agente - LIMITADAS según especificación -->
            <div class="action-card animate-in" onclick="goTo('/itinerarios')" style="animation-delay: 0.1s;">
                <div class="action-icon">🗺️</div>
                <h3 class="action-title">Mis Itinerarios</h3>
                <p class="action-description">Crea y gestiona itinerarios detallados para tus clientes con rutas personalizadas y experiencias únicas.</p>
            </div>

            <div class="action-card animate-in" onclick="goTo('/biblioteca')" style="animation-delay: 0.2s;">
                <div class="action-icon">📚</div>
                <h3 class="action-title">Mi Biblioteca de Recursos</h3>
                <p class="action-description">Administra tus recursos personales: días, alojamientos, actividades y transportes para usar en tus itinerarios.</p>
            </div>

            <div class="action-card animate-in" onclick="goTo('/perfil')" style="animation-delay: 0.3s;">
                <div class="action-icon">👤</div>
                <h3 class="action-title">Mi Perfil</h3>
                <p class="action-description">Configura tu información personal, preferencias y ajustes de tu cuenta de agente de viajes.</p>
            </div>

            <?php endif; ?>
        </div>

        <!-- Stats Section diferenciada por rol -->
        <div class="stats-section animate-in" style="animation-delay: 0.5s;">
            <h2 class="stats-title">
                <?= $user['role'] === 'admin' ? '📊 Estadísticas del Sistema' : '📈 Resumen de Mi Actividad' ?>
            </h2>
            <div class="stats-grid">
                <?php if ($user['role'] === 'admin'): ?>
                <!-- Stats para Administrador - SEGMENTADAS POR AGENCIA -->
                <div class="stat-item">
                    <div class="stat-number"><?php 
                        try {
                            $db = Database::getInstance();
                            $agenciaId = $_SESSION['agencia_id'] ?? null;
                            $count = $db->fetch(
                                "SELECT COUNT(*) as total FROM users WHERE agencia_id = ? AND active = 1",
                                [$agenciaId]
                            );
                            echo $count['total'] ?? 0;
                        } catch(Exception $e) {
                            echo "0";
                        }
                    ?></div>
                    <div class="stat-label">Usuarios Activos</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php 
                        try {
                            $count = $db->fetch(
                                "SELECT COUNT(*) as total FROM programa_solicitudes WHERE agencia_id = ?",
                                [$agenciaId]
                            );
                            echo $count['total'] ?? 0;
                        } catch(Exception $e) {
                            echo "0";
                        }
                    ?></div>
                    <div class="stat-label">Programas Totales</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php 
                        try {
                            $dias = $db->fetch(
                                "SELECT COUNT(*) as total FROM biblioteca_dias WHERE agencia_id = ? AND activo = 1",
                                [$agenciaId]
                            )['total'] ?? 0;
                            $alojamientos = $db->fetch(
                                "SELECT COUNT(*) as total FROM biblioteca_alojamientos WHERE agencia_id = ? AND activo = 1",
                                [$agenciaId]
                            )['total'] ?? 0;
                            $actividades = $db->fetch(
                                "SELECT COUNT(*) as total FROM biblioteca_actividades WHERE agencia_id = ? AND activo = 1",
                                [$agenciaId]
                            )['total'] ?? 0;
                            $transportes = $db->fetch(
                                "SELECT COUNT(*) as total FROM biblioteca_transportes WHERE agencia_id = ? AND activo = 1",
                                [$agenciaId]
                            )['total'] ?? 0;
                            echo ($dias + $alojamientos + $actividades + $transportes);
                        } catch(Exception $e) {
                            echo "0";
                        }
                    ?></div>
                    <div class="stat-label">Recursos en Biblioteca</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php 
                        try {
                            $count = $db->fetch(
                                "SELECT COUNT(*) as total FROM users 
                                WHERE agencia_id = ? AND last_login > DATE_SUB(NOW(), INTERVAL 7 DAY)",
                                [$agenciaId]
                            );
                            echo $count['total'] ?? 0;
                        } catch(Exception $e) {
                            echo "0";
                        }
                    ?></div>
                    <div class="stat-label">Activos (7 días)</div>
                </div>

                <?php else: ?>
                <!-- Stats para Agente - Solo funcionalidades disponibles -->
                <div class="stat-item">
                    <div class="stat-number"><?php 
                        try {
                            $userId = (int)$user['id'];
                            $count = $db->fetch("SELECT COUNT(*) as total FROM programa_solicitudes WHERE user_id = ?", [$userId]);
                            echo (int)($count['total'] ?? 0);
                        } catch(Exception $e) {
                            error_log("Error contando programas para user_id " . $user['id'] . ": " . $e->getMessage());
                            echo "0";
                        }
                    ?></div>
                    <div class="stat-label">Mis Itinerarios</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php 
                        try {
                            $count = $db->fetch("SELECT COUNT(*) as total FROM biblioteca_dias WHERE user_id = ? AND activo = 1", [$user['id']]);
                            echo $count['total'] ?? 0;
                        } catch(Exception $e) {
                            echo "0";
                        }
                    ?></div>
                    <div class="stat-label">Días Creados</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php 
                        try {
                            $count = $db->fetch("SELECT COUNT(*) as total FROM biblioteca_alojamientos WHERE user_id = ? AND activo = 1", [$user['id']]);
                            echo $count['total'] ?? 0;
                        } catch(Exception $e) {
                            echo "0";
                        }
                    ?></div>
                    <div class="stat-label">Alojamientos</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php 
                        try {
                            $userId = (int)$user['id'];
                            $count = $db->fetch("SELECT COUNT(*) as total FROM biblioteca_actividades WHERE user_id = ? AND activo = 1", [$userId]);
                            echo (int)($count['total'] ?? 0);
                        } catch(Exception $e) {
                            error_log("Error contando actividades para user_id " . $user['id'] . ": " . $e->getMessage());
                            echo "0";
                        }
                    ?></div>
                    <div class="stat-label">Actividades</div>
                </div>
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