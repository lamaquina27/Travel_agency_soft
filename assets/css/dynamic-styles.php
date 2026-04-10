<?php
// =====================================
// ARCHIVO: assets/css/dynamic-styles.php - Estilos Dinámicos
// =====================================

header('Content-Type: text/css');

// Incluir configuración
require_once '../../config/database.php';
require_once '../../config/app.php';
require_once '../../config/config_functions.php';

// Inicializar
App::init();

// Obtener el rol del usuario si está logueado
$role = null;
if (App::isLoggedIn()) {
    $user = App::getUser();
    $role = $user['role'];
}

// Obtener colores según el contexto
if ($role) {
    $colors = App::getColorsForRole($role);
} else {
    $colors = App::getLoginColors();
}

// Obtener configuración adicional
try {
    $config = ConfigManager::get();
    $companyName = ConfigManager::getCompanyName();
    $logo = ConfigManager::getLogo();
} catch(Exception $e) {
    $config = [];
    $companyName = 'Travel Agency';
    $logo = '';
}

?>
/* =====================================
   ESTILOS DINÁMICOS DEL SISTEMA
   Generado automáticamente según configuración
   ===================================== */

:root {
    --primary-color: <?= $colors['primary'] ?>;
    --secondary-color: <?= $colors['secondary'] ?>;
    --primary-gradient: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
    --primary-rgb: <?= hex2rgb($colors['primary']) ?>;
    --secondary-rgb: <?= hex2rgb($colors['secondary']) ?>;
}

/* ===== COMPONENTES PRINCIPALES ===== */

.header,
.admin-header,
.agent-header {
    background: var(--primary-gradient) !important;
}

.btn-primary,
.save-btn,
.add-btn,
.login-btn,
.action-btn.primary {
    background: var(--primary-gradient) !important;
    border-color: var(--primary-color) !important;
}

.btn-primary:hover,
.save-btn:hover,
.add-btn:hover,
.login-btn:hover {
    box-shadow: 0 5px 15px rgba(var(--primary-rgb), 0.3) !important;
}

/* ===== FORMULARIOS ===== */

input:focus,
select:focus,
textarea:focus,
.form-control:focus {
    border-color: var(--primary-color) !important;
    box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1) !important;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color: var(--primary-color) !important;
}

/* ===== NAVEGACIÓN ===== */

.menu-item:hover,
.menu-item.active {
    color: var(--primary-color) !important;
    border-left-color: var(--primary-color) !important;
}

.sidebar-header {
    <?php if ($role === 'admin'): ?>
    background: linear-gradient(135deg, #fed7d7 0%, #feb2b2 100%) !important;
    <?php else: ?>
    background: linear-gradient(135deg, #edf2f7 0%, #e2e8f0 100%) !important;
    <?php endif; ?>
}

/* ===== TARJETAS Y COMPONENTES ===== */

.action-card:hover {
    border-color: var(--primary-color) !important;
}

.action-card::before,
.config-section,
.welcome-section {
    border-left-color: var(--primary-color) !important;
}

.role-badge {
    background: var(--primary-gradient) !important;
    color: white !important;
}

.stat-number {
    color: var(--primary-color) !important;
}

/* ===== BADGES Y ESTADOS ===== */

<?php if ($role === 'admin'): ?>
.role-admin {
    background: #fed7d7 !important;
    color: var(--primary-color) !important;
}
<?php else: ?>
.role-agent {
    background: #c6f6d5 !important;
    color: #2f855a !important;
}
<?php endif; ?>

/* ===== MODALES ===== */

.modal-header {
    border-bottom-color: var(--primary-color) !important;
}

.close-btn:hover {
    color: var(--primary-color) !important;
}

/* ===== TABLAS ===== */

.users-table th {
    border-bottom-color: var(--primary-color) !important;
}

.users-table tr:hover {
    background-color: rgba(var(--primary-rgb), 0.05) !important;
}

/* ===== COLOR PICKERS ===== */

.color-picker {
    border: 2px solid var(--primary-color) !important;
}

/* ===== LOADING Y ANIMACIONES ===== */

.loading-spinner {
    border-top-color: var(--primary-color) !important;
}

.spinner {
    border-top-color: var(--primary-color) !important;
}

/* ===== GOOGLE TRANSLATE ===== */

#google_translate_element {
    background: rgba(var(--primary-rgb), 0.2) !important;
}

/* ===== RESPONSIVE ESPECÍFICO POR ROL ===== */

@media (max-width: 768px) {
    .header {
        background: var(--primary-gradient) !important;
    }
}

/* ===== MODO OSCURO (OPCIONAL) ===== */

@media (prefers-color-scheme: dark) {
    :root {
        --primary-color: <?= lightenColor($colors['primary'], 20) ?>;
        --secondary-color: <?= lightenColor($colors['secondary'], 20) ?>;
    }
}

/* ===== ANIMACIONES PERSONALIZADAS ===== */

@keyframes primaryPulse {
    0% {
        box-shadow: 0 0 0 0 rgba(var(--primary-rgb), 0.7);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(var(--primary-rgb), 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(var(--primary-rgb), 0);
    }
}

.pulse-primary {
    animation: primaryPulse 2s infinite !important;
}

/* ===== EFECTOS HOVER ESPECÍFICOS ===== */

.action-card:hover .action-icon {
    background: var(--primary-gradient) !important;
    transform: scale(1.1) !important;
}

/* ===== PERSONALIZACIÓN DE SCROLLBAR ===== */

::-webkit-scrollbar-thumb {
    background: var(--primary-color) !important;
}

::-webkit-scrollbar-thumb:hover {
    background: var(--secondary-color) !important;
}

<?php
// =====================================
// FUNCIONES AUXILIARES
// =====================================

function hex2rgb($hex) {
    $hex = str_replace('#', '', $hex);
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    return "$r, $g, $b";
}

function lightenColor($hex, $percent) {
    $hex = str_replace('#', '', $hex);
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    $r = min(255, $r + ($percent * 255 / 100));
    $g = min(255, $g + ($percent * 255 / 100));
    $b = min(255, $b + ($percent * 255 / 100));
    
    return sprintf("#%02x%02x%02x", $r, $g, $b);
}
?>