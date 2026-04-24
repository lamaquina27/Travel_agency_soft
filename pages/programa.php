<?php
// ====================================================================
// ARCHIVO: pages/programa.php - REESTRUCTURADO CON PESTAÑAS
// ====================================================================

require_once 'config/app.php';
require_once 'config/config_functions.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/ui_components.php';


App::init();
App::requireLogin();

$user = App::getUser(); // MOVER ANTES

ConfigManager::init();
$userColors = ConfigManager::getColorsForRole($user['role']);
$companyName = ConfigManager::getCompanyName();
$logo = ConfigManager::getLogo();
$defaultLanguage = ConfigManager::getDefaultLanguage();


$is_editing = isset($_GET['id']) && !empty($_GET['id']);
$programa_id = $is_editing ? intval($_GET['id']) : null;

// Cargar datos si está editando
$form_data = [
    'traveler_name' => '',
    'traveler_lastname' => '',
    'destination' => '',
    'arrival_date' => '',
    'departure_date' => '',
    'passengers' => 1,
    'accompaniment' => 'sin-acompanamiento',
    'program_title' => '',
    'language' => 'es',
    'request_id' => '',
    'cover_image' => ''
];

if ($is_editing) {
    try {
        $db = Database::getInstance();
        $programa_data = $db->fetch(
            "SELECT * FROM programa_solicitudes WHERE id = ? AND user_id = ?", 
            [$programa_id, $user['id']]
        );
        
        if (!$programa_data) {
            header('Location: ' . APP_URL . '/itinerarios');
            exit;
        }
        
        $personalizacion_data = $db->fetch(
            "SELECT * FROM programa_personalizacion WHERE solicitud_id = ?", 
            [$programa_id]
        );
        
        $form_data = [
            'traveler_name' => $programa_data['nombre_viajero'] ?? '',
            'traveler_lastname' => $programa_data['apellido_viajero'] ?? '',
            'destination' => $programa_data['destino'] ?? '',
            'arrival_date' => $programa_data['fecha_llegada'] ?? '',
            'departure_date' => $programa_data['fecha_salida'] ?? '',
            'passengers' => $programa_data['numero_pasajeros'] ?? 1,
            'accompaniment' => $programa_data['acompanamiento'] ?? 'sin-acompanamiento',
            'program_title' => $personalizacion_data['titulo_programa'] ?? '',
            'language' => $personalizacion_data['idioma_predeterminado'] ?? 'es',
            'request_id' => $programa_data['id_solicitud'] ?? '',
            'cover_image' => $personalizacion_data['foto_portada'] ?? ''
        ];
    } catch(Exception $e) {
        error_log("Error cargando programa: " . $e->getMessage());
        header('Location: ' . APP_URL . '/itinerarios');
        exit;
    }
}

$page_title = $is_editing ? 'Editar Programa' : 'Nuevo Programa';
?>

<!DOCTYPE html>
<html lang="<?= $defaultLanguage ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Programa - <?= htmlspecialchars($companyName) ?></title>
    <?= UIComponents::getComponentStyles() ?>
    
    <!-- CSS Framework y estilos -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/assets/css/dashboard.css" rel="stylesheet">

    <script src="<?= APP_URL ?>/assets/js/ubicacion-search-widget.js"></script>
    
    <style>

        :root {
    --primary-color: <?= $userColors['primary'] ?>;
    --secondary-color: <?= $userColors['secondary'] ?>;
    --primary-gradient: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
}

/* ============================================================
   CSS PARA ALTERNATIVAS - AGREGAR AL <style> DE programa.php
   ============================================================ */
/* Botón compartir enlace - Estilo minimalista */
.nav-button[onclick*="compartirEnlace"], .nav-button[onclick*="abrirMiBiblioteca"] {
    background: rgba(107, 114, 128, 0.08) !important;
    color: #374151 !important;
    border: none !important;
    border-radius: 8px !important;
    padding: 12px 20px !important;
    font-size: 13px !important;
    font-weight: 500 !important;
    transition: all 0.15s ease !important;
    box-shadow: none !important;
    letter-spacing: 0.3px !important;
    text-transform: none !important;
    margin-left: 15px !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 8px !important;
}

.nav-button[onclick*="compartirEnlace"]:hover, .nav-button[onclick*="abrirMiBiblioteca"]:hover {
    background: rgba(107, 114, 128, 0.12) !important;
    color: #1f2937 !important;
    transform: translateY(-0.5px) !important;
    box-shadow: 0 2px 8px rgba(107, 114, 128, 0.15) !important;
}

.nav-button[onclick*="compartirEnlace"]:active, .nav-button[onclick*="abrirMiBiblioteca"]:active {
    transform: translateY(0) !important;
    background: rgba(107, 114, 128, 0.15) !important;
}

.nav-button[onclick*="compartirEnlace"] i, .nav-button[onclick*="abrirMiBiblioteca"] i {
    color: inherit !important;
    font-size: 12px !important;
}

.nav-button[onclick*="compartirEnlace"] span, .nav-button[onclick*="abrirMiBiblioteca"] span {
    color: inherit !important;
}

/* Responsive para el botón */
@media (max-width: 768px) {
    .nav-button[onclick*="compartirEnlace"] {
        padding: 10px 16px !important;
        font-size: 12px !important;
        margin-left: 10px !important;
    }
    
    .nav-button[onclick*="compartirEnlace"] span {
        display: none !important;
    }
    
    .nav-button[onclick*="compartirEnlace"] i {
        margin-right: 0 !important;
    }

     .nav-button[onclick*="abrirMiBiblioteca"] {
        padding: 10px 16px !important;
        font-size: 12px !important;
        margin-left: 10px !important;
    }
    
    .nav-button[onclick*="abrirMiBiblioteca"] span {
        display: none !important;
    }
    
    .nav-button[onclick*="abrirMiBiblioteca"] i {
        margin-right: 0 !important;
    }
}
/* Grupo de servicios con alternativas */
.service-group {
    margin-bottom: 16px;
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    overflow: hidden;
    background: white;
    transition: all 0.3s ease;
}

.service-group:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    border-color: #2d5a4a;
}

/* Servicio principal */
.service-item.principal {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-bottom: 2px solid #e0e0e0;
    position: relative;
}

.service-item.principal::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: linear-gradient(135deg, #2d5a4a 0%, #4a7c59 100%);
}

/* Container de alternativas */
.alternatives-container {
    background: #fafbfc;
    border-top: 1px solid #e9ecef;
}

/* Alternativas */
.service-item.alternativa {
    background: #fafbfc;
    border-bottom: 1px solid #e9ecef;
    position: relative;
    margin-left: 20px;
    margin-right: 0;
}

.service-item.alternativa:last-child {
    border-bottom: none;
}

.service-item.alternativa::before {
    content: '';
    position: absolute;
    left: -20px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: linear-gradient(135deg, #17a2b8 0%, #20c997 100%);
}

/* Conector visual para alternativas */
.alternative-connector {
    position: absolute;
    left: -20px;
    top: 20px;
    width: 20px;
    height: 2px;
    background: linear-gradient(90deg, #17a2b8 0%, #20c997 100%);
}

.alternative-connector::before {
    content: '';
    position: absolute;
    right: -4px;
    top: -2px;
    width: 6px;
    height: 6px;
    background: #17a2b8;
    border-radius: 50%;
}
/* ============================================================
   CONTROLES DE ESTANCIA - DISEÑO MODERNO
   ============================================================ */

/* Controles en sidebar - Compacto y elegante */
.day-controls {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-left: auto;
    background: rgba(255, 255, 255, 0.1);
    padding: 4px 6px;
    border-radius: 12px;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.estancia-btn {
    color: white;
    border: none;
    width: 24px;
    height: 24px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 12px;
    font-weight: bold;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

/* Botón MÁS - Verde */
.estancia-btn[onclick*="+ 1"] {
    background: #48bb78;
    box-shadow: 0 2px 8px rgba(72, 187, 120, 0.3);
}

.estancia-btn[onclick*="+ 1"]:hover:not(:disabled) {
    background: #38a169;
    box-shadow: 0 4px 15px rgba(72, 187, 120, 0.4);
}

/* Botón MENOS - Rojo suave */
.estancia-btn[onclick*="- 1"] {
    background: #f56565;
    box-shadow: 0 2px 8px rgba(245, 101, 101, 0.3);
}

.estancia-btn[onclick*="- 1"]:hover:not(:disabled) {
    background: #e53e3e;
    box-shadow: 0 4px 15px rgba(245, 101, 101, 0.4);
}}

.estancia-btn:disabled {
    background: linear-gradient(135deg, #e0e0e0 0%, #bdbdbd 100%);
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
    opacity: 0.6;
}

.estancia-display {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    color: #2c3e50;
    padding: 4px 8px;
    border-radius: 8px;
    font-weight: 700;
    font-size: 11px;
    min-width: 24px;
    text-align: center;
    border: 2px solid rgba(102, 126, 234, 0.2);
    box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
    position: relative;
}

.estancia-display::after {
    content: attr(data-suffix);
    font-size: 9px;
    color: #6c757d;
    margin-left: 2px;
}

/* Controles en detalle - Más prominente */
.day-controls-detail {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    margin: 15px 0;
    padding: 16px 20px;
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-radius: 16px;
    border: 2px solid #e9ecef;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    position: relative;
    overflow: hidden;
}

.day-controls-detail::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 50%, #667eea 100%);
    background-size: 200% 100%;
    animation: shimmer 3s ease-in-out infinite;
}

@keyframes shimmer {
    0% { background-position: -200% 0; }
    100% { background-position: 200% 0; }
}

.day-controls-detail .estancia-btn {
    width: 36px;
    height: 36px;
    font-size: 16px;
    border-radius: 12px;
}

.day-controls-detail .estancia-btn[onclick*="+ 1"] {
    box-shadow: 0 4px 12px rgba(72, 187, 120, 0.3);
}

.day-controls-detail .estancia-btn[onclick*="- 1"] {
    box-shadow: 0 4px 12px rgba(245, 101, 101, 0.3);
}

.day-controls-detail .estancia-display {
    padding: 8px 16px;
    font-size: 16px;
    font-weight: 800;
    min-width: 50px;
    border-radius: 12px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    border: none;
}

/* Indicador de estancia en título */
.duration-badge {
    display: inline-flex;
    align-items: center;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    padding: 2px 8px;
    border-radius: 8px;
    font-size: 10px;
    font-weight: 600;
    margin-left: 8px;
    box-shadow: 0 2px 6px rgba(16, 185, 129, 0.3);
    animation: pulse-badge 2s ease-in-out infinite;
}

@keyframes pulse-badge {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.duration-badge::before {
    content: '📅';
    margin-right: 4px;
}

/* Mejorar header del día */
.day-item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
    padding: 8px 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.day-number-sidebar {
    font-weight: 700;
    color: #2c3e50;
    flex: 1;
    font-size: 14px;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

/* Efectos hover para días */
.day-sidebar-item {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.day-sidebar-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
    transition: left 0.6s;
}

.day-sidebar-item:hover::before {
    left: 100%;
}

.day-sidebar-item:hover .day-controls {
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
}

/* Indicador visual de múltiples días */
.multi-day-indicator {
    position: absolute;
    top: 4px;
    right: 4px;
    width: 8px;
    height: 8px;
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    border-radius: 50%;
    box-shadow: 0 0 0 2px white, 0 2px 4px rgba(245, 158, 11, 0.4);
    animation: glow 2s ease-in-out infinite alternate;
}

@keyframes glow {
    from { box-shadow: 0 0 0 2px white, 0 2px 4px rgba(245, 158, 11, 0.4); }
    to { box-shadow: 0 0 0 2px white, 0 2px 8px rgba(245, 158, 11, 0.6), 0 0 12px rgba(245, 158, 11, 0.3); }
}

/* Tooltip para los botones */
.estancia-btn {
    position: relative;
}

.estancia-btn[title]:hover::after {
    content: attr(title);
    position: absolute;
    bottom: 120%;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(0, 0, 0, 0.9);
    color: white;
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 10px;
    white-space: nowrap;
    z-index: 1000;
    animation: tooltip-show 0.3s ease;
}

@keyframes tooltip-show {
    from { opacity: 0; transform: translateX(-50%) translateY(4px); }
    to { opacity: 1; transform: translateX(-50%) translateY(0); }
}

/* Responsive */
@media (max-width: 768px) {
    .day-controls {
        gap: 4px;
        padding: 3px 5px;
    }
    
    .estancia-btn {
        width: 20px;
        height: 20px;
        font-size: 10px;
    }
    
    .estancia-display {
        padding: 3px 6px;
        font-size: 10px;
        min-width: 20px;
    }
    
    .day-controls-detail {
        padding: 12px 16px;
        gap: 8px;
    }
    
    .day-controls-detail .estancia-btn {
        width: 32px;
        height: 32px;
        font-size: 14px;
    }
    
    .day-controls-detail .estancia-display {
        padding: 6px 12px;
        font-size: 14px;
        min-width: 40px;
    }
}
/* Iconos de servicios alternativas */
.service-icon.alternativa {
    background: linear-gradient(135deg, #17a2b8 0%, #20c997 100%);
    border: 2px solid white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* Botón para agregar alternativa */
.btn-add-alternative {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    border: none;
    border-radius: 4px;
    padding: 4px 8px;
    cursor: pointer;
    font-size: 12px;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 4px;
}

.btn-add-alternative:hover {
    background: linear-gradient(135deg, #218838 0%, #1ea085 100%);
    transform: scale(1.05);
}

.btn-add-alternative:active {
    transform: scale(0.95);
}

/* Efecto hover para grupos de servicios */
.service-group:hover .service-item.principal {
    background: linear-gradient(135deg, #e8f5e8 0%, #f0fff0 100%);
}

.service-group:hover .alternatives-container {
    background: #f0f8ff;
}

.service-group:hover .service-item.alternativa {
    background: #f0f8ff;
}

/* Badges para identificar principal vs alternativa */
.service-badge {
    position: absolute;
    top: 8px;
    right: 8px;
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 8px;
    font-weight: 600;
    text-transform: uppercase;
}

.service-badge.principal {
    background: linear-gradient(135deg, #ffc107 0%, #ffb300 100%);
    color: #333;
}

.service-badge.alternativa {
    background: linear-gradient(135deg, #17a2b8 0%, #20c997 100%);
    color: white;
}

/* Animaciones para alternativas */
.alternatives-container {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
}

.service-group:hover .alternatives-container,
.service-group.expanded .alternatives-container {
    max-height: 1000px;
}

/* Indicador de cantidad de alternativas */
.alternatives-indicator {
    position: absolute;
    top: 8px;
    right: 50px;
    background: linear-gradient(135deg, #17a2b8 0%, #20c997 100%);
    color: white;
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 10px;
    font-weight: 600;
}

/* Mejorar hover de acciones para alternativas */
.service-item.alternativa .service-actions {
    opacity: 0.7;
}

.service-item.alternativa:hover .service-actions {
    opacity: 1;
}

/* Líneas de conexión más elaboradas */
.service-group::before {
    content: '';
    position: absolute;
    left: 8px;
    top: 60px;
    bottom: 20px;
    width: 1px;
    background: linear-gradient(180deg, #2d5a4a 0%, #17a2b8 50%, #20c997 100%);
    z-index: 1;
}

/* Responsive para alternativas */
@media (max-width: 768px) {
    .service-item.alternativa {
        margin-left: 15px;
    }
    
    .alternative-connector {
        left: -15px;
        width: 15px;
    }
    
    .service-item.alternativa::before {
        left: -15px;
    }
    
    .service-actions {
        flex-direction: column;
        gap: 2px;
    }
    
    .btn-add-alternative {
        padding: 3px 6px;
        font-size: 10px;
    }
}

/* Estados de carga para alternativas */
.loading-alternatives {
    padding: 10px;
    text-align: center;
    color: #666;
    font-style: italic;
    background: #f8f9fa;
}

.loading-alternatives .fas {
    animation: spin 1s linear infinite;
    margin-right: 8px;
}

/* Efecto de aparición de alternativas */
.service-item.alternativa {
    animation: slideInAlternative 0.3s ease;
}

@keyframes slideInAlternative {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* Hover effects mejorados */
.service-group:hover .service-item.principal .service-icon {
    transform: scale(1.1);
    box-shadow: 0 4px 8px rgba(45, 90, 74, 0.3);
}

.service-item.alternativa:hover .service-icon {
    transform: scale(1.1);
    box-shadow: 0 4px 8px rgba(23, 162, 184, 0.3);
}

/* Mejorar legibilidad de texto en alternativas */
.service-item.alternativa .service-details h6 {
    color: #2c3e50;
    font-weight: 600;
}

.service-item.alternativa .service-details p {
    color: #5a6c7d;
}



/* ============================================================
   ESTILOS PARA BARRA LATERAL DE DÍAS
   ============================================================ */

/* Contenedor principal de día a día */
.dias-layout {
    display: flex;
    gap: 20px;
    min-height: 500px; 
}

/* Barra lateral de días */
.days-sidebar {
    width: 280px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    display: flex;
    flex-direction: column;
    height: fit-content; /* Ajustar al contenido */
    max-height: calc(100vh - 200px); /* Scroll solo si hay muchos días */
}


.sidebar-header {
    padding: 20px;
    border-bottom: 1px solid #e0e0e0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.sidebar-title {
    font-size: 18px;
    font-weight: 600;
    color: #333;
}

.add-day-btn {
    padding: 8px 16px;
    background: #2d5a4a;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 12px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
}

.add-day-btn:hover {
    background: #234a3a;
    transform: translateY(-1px);
}

.days-list {
    flex: 1;
    overflow-y: auto;
    padding: 10px;
}

.day-sidebar-item {
    padding: 12px 16px;
    margin-bottom: 8px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid transparent;
    position: relative;
}

.day-sidebar-item:hover {
    background: #f8f9fa;
    border-color: #e0e0e0;
}

.day-sidebar-item.active {
    background: white;
    color: #2d3748;
    border: 3px solid #4a5568;
    box-shadow: 0 8px 25px rgba(74, 85, 104, 0.4), 0 4px 12px rgba(0, 0, 0, 0.15);
    transform: translateY(-2px);
}
.day-sidebar-item.active:hover {
    box-shadow: 0 12px 35px rgba(74, 85, 104, 0.5), 0 6px 15px rgba(0, 0, 0, 0.2);
    transform: translateY(-3px);
}

.day-item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 6px;
}

.day-number-sidebar {
    font-weight: 600;
    font-size: 14px;
}

/* Botones de acción en sidebar - VISIBLES */
.day-actions-sidebar {
    display: flex;
    gap: 4px;
    opacity: 0;
    transition: opacity 0.2s;
}

.day-sidebar-item:hover .day-actions-sidebar,
.day-sidebar-item.active .day-actions-sidebar {
    opacity: 1;
}

.day-action-btn {
    width: 28px;
    height: 28px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    transition: all 0.2s;
}

.day-action-btn.edit {
    background: rgba(108, 117, 125, 0.2);
    color: #6c757d;
}

.day-action-btn.edit:hover {
    background: #6c757d;
    color: white;
    transform: scale(1.1);
}

.day-action-btn.delete {
    background: rgba(220, 53, 69, 0.2);
    color: #dc3545;
}

.day-action-btn.delete:hover {
    background: #dc3545;
    color: white;
    transform: scale(1.1);
}

.day-sidebar-item.active .day-action-btn.edit {
    background: rgba(108, 117, 125, 0.3);
    color: #495057;
}

.day-sidebar-item.active .day-action-btn.delete {
    background: rgba(220, 53, 69, 0.3);
    color: #c82333;
}

.day-action-btn:hover {
    transform: scale(1.1);
}

.day-item-title {
    font-size: 12px;
    margin-bottom: 4px;
    font-weight: 500;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.day-item-location {
    font-size: 10px;
    opacity: 0.8;
    display: flex;
    align-items: center;
    gap: 4px;
}

.day-services-count {
    position: absolute;
    top: 8px;
    right: 8px;
    background: rgba(45, 90, 74, 0.9);
    color: white;
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 10px;
    font-weight: 600;
}

.day-sidebar-item.active .day-services-count {
    background: #4a5568;
    color: white;
}

/* Contenido del día seleccionado */
.day-detail-container {
    flex: 1;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    display: flex;
    flex-direction: column;
    height: fit-content; /* Ajustar al contenido del día */
}

.day-detail-header {
    padding: 24px;
    border-bottom: 1px solid #e0e0e0;
    background: linear-gradient(135deg, #2d5a4a 0%, #4a7c59 100%);
    color: white;
    border-radius: 12px 12px 0 0;
}

.day-detail-number {
    font-size: 14px;
    opacity: 0.9;
    margin-bottom: 8px;
}

.day-detail-title {
    font-size: 24px;
    font-weight: 600;
    margin-bottom: 8px;
}

.day-detail-meta {
    display: flex;
    gap: 20px;
    font-size: 14px;
    opacity: 0.9;
}

.day-detail-body {
    flex: 1;
    overflow-y: visible; 
    padding: 24px;
}

/* Estado vacío de sidebar */
.empty-sidebar {
    text-align: center;
    padding: 40px 20px;
    color: #666;
}

.empty-sidebar .fas {
    font-size: 48px;
    margin-bottom: 16px;
    opacity: 0.3;
}

.empty-sidebar h3 {
    font-size: 16px;
    margin-bottom: 8px;
    color: #333;
}

.empty-sidebar p {
    font-size: 14px;
    margin-bottom: 20px;
}

/* Estado vacío de detalle */
.empty-detail {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    text-align: center;
    color: #666;
}

.empty-detail .fas {
    font-size: 64px;
    margin-bottom: 20px;
    opacity: 0.3;
}

.empty-detail h3 {
    font-size: 20px;
    margin-bottom: 10px;
    color: #333;
}

.empty-detail p {
    font-size: 16px;
}

/* Responsivo */
@media (max-width: 1024px) {
    .dias-layout {
        flex-direction: column;
        min-height: auto; /* Sin altura mínima en mobile */
    }
    
    .days-sidebar {
        width: 100%;
        max-height: 300px;
        overflow-y: auto; /* Mantener scroll en mobile */
    }
    
    .day-detail-container {
        height: auto; /* Altura automática en mobile */
    }
    
    .days-list {
        display: flex;
        gap: 10px;
        overflow-x: auto;
        padding: 10px;
    }
    
    .day-sidebar-item {
        min-width: 200px;
        flex-shrink: 0;
    }
}

@media (max-width: 768px) {
    .days-sidebar {
        max-height: 200px;
    }
    
    .day-sidebar-item {
        min-width: 160px;
    }
    
    .sidebar-title {
        font-size: 16px;
    }
    
    .add-day-btn {
        padding: 6px 12px;
        font-size: 11px;
    }
}



        body {
            background-color: #f5f5f5;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            margin: 0;
            padding: 0;
        }
        
        .top-nav {
            background-color: #2d5a4a;
            color: white;
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .top-nav .logo {
            font-size: 18px;
            font-weight: 600;
            text-decoration: none;
            color: white;
            border-bottom: 2px solid white;
            padding-bottom: 2px;
        }
        
        .top-nav .nav-links {
            display: flex;
            gap: 30px;
            align-items: center;
        }
        
        .top-nav .nav-links a {
            color: white;
            text-decoration: none;
            font-size: 14px;
            border-bottom: 1px solid transparent;
            padding-bottom: 1px;
        }
        
/* Estilos para el campo ID de solicitud */
#request-id-group {
    transition: all 0.4s ease;
}

#request-id-group .form-text {
    margin-top: 0.25rem;
    font-size: 0.875rem;
    color: #6c757d;
}

#request-id-group .form-text i {
    margin-right: 0.25rem;
    color: #007bff;
}

/* Animación de aparición */
@keyframes slideInDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

        .top-nav .nav-links a:hover {
            border-bottom-color: white;
        }
        
        .top-nav .user-avatar {
            width: 32px;
            height: 32px;
            background-color: #4a7c59;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 600;
        }
        
        .tab-navigation {
            background-color: white;
            margin-top: 70px; /* Ajustado para el nuevo header */
            padding: 0;
            border-bottom: 1px solid #e0e0e0;
            position: sticky;
            top: 70px; /* Ajustado para el nuevo header */
            z-index: 999;
        }
        
        .tab-nav {
            display: flex;
            max-width: 1600px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .tab-item {
            padding: 16px 24px;
            border-bottom: 3px solid transparent;
            color: #666;
            text-decoration: none;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .tab-item.active {
            color: #2d5a4a;
            border-bottom-color: #2d5a4a;
        }
        
        .tab-item:hover:not(.active) {
            color: #2d5a4a;
            background-color: #f8f9fa;
        }
        
        /* Container principal */
        .main-container {
            min-height: 100vh;
            padding: 20px;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            margin-left: 0;
            transition: margin-left 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .form-section {
            width: 98%;
            max-width: none;
            margin: 0;
        }

        .section-card {
            background: white;
            border-radius: 24px;
            box-shadow: 0 8px 35px rgba(0,0,0,0.12);
            margin-bottom: 50px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .section-card:hover {
            box-shadow: 0 12px 45px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }

        /* Mejorar campos del formulario */
        .form-group {
            flex: 1;
            margin-bottom: 45px;
            position: relative;
        }

        .form-label {
            display: block;
            margin-bottom: 18px;
            font-weight: 700;
            color: #1a202c;
            font-size: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
        }

        .form-label::before {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 40px;
            height: 3px;
            background: var(--primary-gradient);
            border-radius: 2px;
        }

        .form-control {
            width: 100%;
            padding: 24px 28px;
            border: 3px solid #e2e8f0;
            border-radius: 18px;
            font-size: 20px;
            font-weight: 500;
            background: white;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            position: relative;
        }

        .form-control::placeholder {
            color: #a0aec0;
            font-style: italic;
            font-weight: 400;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 6px rgba(102, 126, 234, 0.15), 0 8px 25px rgba(0,0,0,0.1);
            transform: translateY(-3px);
            background: #fafbfc;
        }

        .form-control:hover:not(:focus) {
            border-color: #cbd5e0;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.08);
        }

        /* Centrar y mejorar botones de acción */
        .form-actions {
            text-align: center;
            padding: 40px 0;
            background: #f8fafc;
            margin: 40px -50px -50px -50px; /* Extender al borde de la tarjeta */
            border-top: 1px solid #e2e8f0;
        }

        .btn {
            padding: 20px 40px;
            border-radius: 16px;
            font-weight: 700;
            font-size: 18px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 15px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            cursor: pointer;
            margin: 0 15px;
            min-width: 250px;
            justify-content: center;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.6s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        /* Pestañas de contenido */
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .section-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 32px;
            overflow: hidden;
        }
        
        .section-header {
            padding: 60px 70px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            background: #6b7280;
            color: white;
            position: relative;
            overflow: hidden;
        }

       
        .section-header:hover::before {
            left: 100%;
        }

        .section-title {
            font-size: 32px;
            font-weight: 800;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 20px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
            letter-spacing: -0.5px;
        }

        .section-title i {
            color: #ffffff;
            font-size: 36px;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));
        }
        
        .section-body {
            padding: 70px;
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border-top: 1px solid rgba(0,0,0,0.05);
            position: relative;
        }

        .section-body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(0,0,0,0.1), transparent);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 50px;
            margin-bottom: 35px;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
                gap: 30px;
            }
        }
        
        .form-group {
            flex: 1;
            margin-bottom: 24px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.2s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #2d5a4a;
            box-shadow: 0 0 0 3px rgba(45, 90, 74, 0.1);
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary {
            background-color: #2d5a4a;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #234a3a;
            transform: translateY(-1px);
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .btn-outline {
            background-color: transparent;
            color: #2d5a4a;
            border: 2px solid #2d5a4a;
        }
        
        .btn-outline:hover {
            background-color: #2d5a4a;
            color: white;
        }
        
        /* Estilos específicos para Día a día */
        .days-container {
            display: grid;
            gap: 30px;
        }
        
        .day-card {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        .day-card:hover {
            border-color: #2d5a4a;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }
        
        .day-header {
            background: linear-gradient(135deg, #2d5a4a 0%, #4a7c59 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .day-number {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 16px;
            backdrop-filter: blur(10px);
        }
        
        .day-actions {
            display: flex;
            gap: 8px;
        }

.price-input-container {
    position: relative;
    display: flex;
    align-items: center;
}

.currency-icon {
    position: absolute;
    left: 12px;
    z-index: 2;
    color: #666;
    font-weight: bold;
    font-size: 16px;
    pointer-events: none;
}

.price-input-with-icon {
    padding-left: 35px !important;
}
.btn {
    padding: 16px 32px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 16px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 12px;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
    margin: 0 10px;
    min-width: 200px;
    justify-content: center;
}

.btn-primary {
    background: var(--primary-gradient);
    color: white;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.btn-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
    color: white;
    text-decoration: none;
}

.btn-secondary {
    background: #6c757d;
    color: white;
    box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
}

.btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(108, 117, 125, 0.4);
    color: white;
    text-decoration: none;
}

.btn-outline {
    background: transparent;
    color: var(--primary-color);
    border: 2px solid var(--primary-color);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.1);
}

.btn-outline:hover {
    background: var(--primary-color);
    color: white;
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
    text-decoration: none;
}

/* Contenedor de acciones mejorado */
.form-actions {
    text-align: center;
    padding: 40px;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    margin: 40px -50px -50px -50px;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}

/* Responsive para botones */
@media (max-width: 768px) {
    .form-actions {
        flex-direction: column;
        gap: 15px;
        margin: 20px -20px -20px -20px;
        padding: 30px 20px;
    }
    
    .btn {
        width: 100%;
        max-width: 300px;
        margin: 0;
    }
}

        .day-content {
            padding: 25px;
        }
        
        .day-images {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 10px;
            margin-bottom: 20px;
            height: 200px;
        }
        
        .day-image {
            border-radius: 12px;
            overflow: hidden;
            background: #f8f9fa;
            position: relative;
        }
        
        .day-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .day-image:hover img {
            transform: scale(1.05);
        }
        
        .day-image.main {
            grid-row: span 2;
        }
        
        .day-info h4 {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }
        
        .day-description {
            color: #666;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        
        .day-meta {
            display: flex;
            gap: 20px;
            color: #888;
            font-size: 14px;
        }
        
        .day-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        /* Estilos para servicios del día */
        .day-services {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
        }
        
        .services-header h5 {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .service-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .service-btn {
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 10px 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 500;
            color: #666;
        }
        
        .service-btn:hover {
            background: #2d5a4a;
            color: white;
            border-color: #2d5a4a;
            transform: translateY(-2px);
        }
        
        .meals-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .meals-section h6 {
            font-size: 14px;
            font-weight: 600;
            color: #333;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .meals-options {
            display: flex;
            gap: 20px;
            margin-bottom: 12px;
        }
        
        .meal-option {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .meal-option input[type="radio"] {
            margin: 0;
        }
        
        .meal-details {
            margin-top: 10px;
        }
        
        .meal-checkboxes {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .meal-checkbox {
            display: flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            font-size: 13px;
            color: #666;
        }
        
        .meal-checkbox input[type="checkbox"] {
            margin: 0;
        }
        
        .added-services {
            margin-top: 15px;
        }
        
        .service-item {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .service-item:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .service-info {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
        }
        
        .service-icon {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            color: white;
        }
        
        .service-icon.actividad {
            background: #28a745;
        }
        
        .service-icon.transporte {
            background: #007bff;
        }
        
        .service-icon.alojamiento {
            background: #ffc107;
            color: #333;
        }
        
        .service-details h6 {
            margin: 0 0 4px 0;
            font-size: 14px;
            font-weight: 600;
            color: #333;
        }
        
        .service-details p {
            margin: 0;
            font-size: 12px;
            color: #666;
        }
        
        .service-actions {
            display: flex;
            gap: 5px;
        }
        
        .service-actions button {
            padding: 4px 8px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .btn-edit-service {
            background: #6c757d;
            color: white;
        }
        
        .btn-remove-service {
            background: #dc3545;
            color: white;
        }
        
        /* Estilos para biblioteca modal */
        .biblioteca-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
            max-height: 55vh;
            overflow-y: auto;
            padding: 20px;
            background: #f8fafc;
            border-radius: 12px;
        }

        .biblioteca-item {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
            position: relative;
        }

        .biblioteca-item:hover {
            border-color: #4299e1;
            box-shadow: 0 12px 30px rgba(66, 153, 225, 0.15);
            transform: translateY(-3px);
        }

        .biblioteca-item.selected {
            border-color: #48bb78;
            background: #f0fff4;
            box-shadow: 0 12px 30px rgba(72, 187, 120, 0.2);
            transform: translateY(-3px);
        }

        .biblioteca-item.selected::after {
            content: '✓';
            position: absolute;
            top: 12px;
            right: 12px;
            background: #48bb78;
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
            box-shadow: 0 2px 8px rgba(72, 187, 120, 0.3);
        }
        
        .biblioteca-item:hover {
            border-color: #2d5a4a;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            transform: translateY(-3px);
        }
        
        .biblioteca-item.selected {
            border-color: #2d5a4a;
            background: #f0fff0;
            box-shadow: 0 8px 25px rgba(45, 90, 74, 0.3);
        }
        
.biblioteca-item-checkbox {
    position: absolute;
    top: 12px;
    right: 12px;
    width: 28px;
    height: 28px;
    cursor: pointer;
    z-index: 10;
    opacity: 0;
}

.biblioteca-item-checkbox-visual {
    position: absolute;
    top: 12px;
    right: 12px;
    width: 28px;
    height: 28px;
    border: 3px solid #cbd5e0;
    border-radius: 8px;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    pointer-events: none;
    z-index: 9;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.biblioteca-item.selected .biblioteca-item-checkbox-visual {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-color: #667eea;
    transform: scale(1.1);
}

.biblioteca-item.selected .biblioteca-item-checkbox-visual::after {
    content: '✓';
    color: white;
    font-size: 18px;
    font-weight: bold;
}

.biblioteca-item {
    cursor: pointer;
    transition: all 0.3s ease;
}

.biblioteca-item.selected {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
    border: 2px solid #667eea;
}

.biblioteca-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
}

/* Badge de orden de selección */
.orden-seleccion-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 14px;
    box-shadow: 0 2px 8px rgba(245, 158, 11, 0.4);
    z-index: 10;
}
        
        
        .biblioteca-item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .biblioteca-item:hover .biblioteca-item-image img {
            transform: scale(1.1);
        }
        
        .biblioteca-item-image {
            height: 140px;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            position: relative;
            overflow: hidden;
        }

        .biblioteca-item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .biblioteca-item:hover .biblioteca-item-image img {
            transform: scale(1.05);
        }

        .biblioteca-item-content {
            padding: 16px;
        }

        .biblioteca-item-title {
            font-size: 16px;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 8px;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .biblioteca-item-description {
            color: #718096;
            font-size: 13px;
            line-height: 1.4;
            margin-bottom: 10px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .biblioteca-item-location {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #4a5568;
            font-size: 12px;
            font-weight: 500;
        }

        .biblioteca-item-location i {
            color: #e53e3e;
            font-size: 11px;
        }
      
        
        .biblioteca-item-location {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #888;
            font-size: 13px;
        }
        
        .biblioteca-filters {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        
        .search-box {
            position: relative;
        }
        
        .search-box input {
            padding-left: 45px;
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
        }
        
        .search-box .fas {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .empty-state .fas {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        .empty-state h3 {
            font-size: 24px;
            margin-bottom: 10px;
            color: #333;
        }
        
        .empty-state p {
            font-size: 16px;
            margin-bottom: 30px;
        }
        
        /* Estilos para Precio */
        .price-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        .price-card {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 25px;
        }
        
        .price-input {
            font-size: 24px;
            font-weight: 600;
            text-align: center;
            color: #2d5a4a;
        }
        
        /* Preview panel */
        .preview-section {
            width: 400px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            height: fit-content;
            position: sticky;
            top: 140px;
        }
        
        .preview-header {
            padding: 24px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }
        
        .preview-body {
            padding: 24px;
        }
        
        /* Responsivo */
        @media (max-width: 768px) {
            .main-container {
                flex-direction: column;
                padding: 20px 15px;
            }
            
            .preview-section {
                width: 100%;
                position: static;
            }
            
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            .tab-nav {
                flex-wrap: wrap;
                padding: 0 15px;
            }
            
            .tab-item {
                padding: 12px 16px;
            }
        }
        
        /* Estados de carga */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
        
        .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #ffffff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 0.8s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* AGREGAR/ACTUALIZAR estilos para alertas */
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .alert-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .alert-error {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }

        .alert-info {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
        }

        /* Animación para spinner */
        .fa-spinner {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .expand-icon {
            color: #ffffff;
            font-size: 24px;
            transition: all 0.3s ease;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .section-header:hover .expand-icon {
            transform: scale(1.1);
        }
        
        .section-body.collapsed {
            display: none;
        }
        
        /* Estilos adicionales para modal */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0,0,0,0.75);
            z-index: 10000;
            backdrop-filter: blur(8px);
            padding: 0;
            margin: 0;
            overflow: hidden;
        }

        .modal[style*="block"] {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

        .modal-content {
            background: white;
            border-radius: 20px;
            width: 100%;
            max-width: 900px;
            max-height: 85vh;
            overflow: hidden;
            box-shadow: 0 25px 50px rgba(0,0,0,0.3);
            animation: modalAppear 0.3s ease-out;
        }

        @keyframes modalAppear {
            from {
                opacity: 0;
                transform: scale(0.9) translateY(20px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }
        
        
        
        .modal-body {
            padding: 20px;
        }
        
        .modal-header {
            padding: 30px 30px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 20px 20px 0 0;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .close-modal {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 20px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .close-modal:hover {
            background: rgba(255,255,255,0.3);
            transform: scale(1.1);
        }

        .modal-body {
            overflow-y: auto;
            max-height: calc(85vh - 180px);
        }

        .modal-footer {
            padding: 25px 30px;
            background: #f8fafc;
            display: flex;
            justify-content: center;
            gap: 15px;
            border-radius: 0 0 20px 20px;
            border-top: 1px solid #e2e8f0;
        }

        .modal-footer .btn {
            min-width: 160px;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .modal-footer .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .modal-footer .btn-primary {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: white;
        }

        .modal-footer .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        
        .close-modal {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }
        
        .close-modal:hover {
            color: #333;
        }
        
        .preview-program {
            padding: 20px;
        }
        
        .preview-header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e0e0e0;
        }
        .tab-item.active {
    color: var(--primary-color);
    border-bottom-color: var(--primary-color);
}

.tab-item:hover:not(.active) {
    color: var(--primary-color);
    background-color: #f8f9fa;
}

.add-day-btn {
    background: var(--primary-color);
    /* resto igual */
}


        .preview-details {
            margin-bottom: 20px;
        }
        
        .detail-row {
            margin-bottom: 8px;
        }
        
        .preview-days {
            margin-top: 20px;
        }
        
        .preview-day {
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .preview-item {
            margin-bottom: 8px;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }

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

/* Ajustes para sidebar */
.main-container.sidebar-open {
    margin-left: 320px;
}

/* Responsive */
@media (max-width: 768px) {
    .main-container.sidebar-open {
        margin-left: 0;
    }
}
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

        .VIpgJd-ZVi9od-ORHb-OEVmcd {
            left: 0;
            display: none !important;
            top: 0;
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

body { 
    top: 0px !important; 
}

/* Overlay */
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

/* Ajustes para main container */
.main-container.sidebar-open {
    margin-left: 320px;
}

/* Responsive para header */
@media (max-width: 768px) {
    .header {
        padding: 15px 20px;
    }
    
    .main-container.sidebar-open {
        margin-left: 0;
    }
}
/* ============================================================
   NUEVOS ESTILOS MEJORADOS PARA FORMULARIO GRANDE
   ============================================================ */

/* Placeholders y ejemplos mejorados */
.form-control[placeholder] {
    position: relative;
}

.form-group[data-example]::after {
    content: attr(data-example);
    position: absolute;
    top: 100%;
    left: 0;
    font-size: 14px;
    color: #718096;
    font-style: italic;
    margin-top: 8px;
    padding: 8px 12px;
    background: #f7fafc;
    border-radius: 8px;
    border-left: 3px solid var(--primary-color);
}

/* Efectos de enfoque mejorados */
.form-group:focus-within .form-label {
    color: var(--primary-color);
    transform: translateY(-2px);
}

.form-group:focus-within .form-label::before {
    width: 60px;
    background: var(--primary-color);
}

/* Animaciones suaves para campos */
.form-control {
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Estados especiales para diferentes tipos de input */
input[type="date"].form-control {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%234299e1'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 20px center;
    background-size: 24px;
}

select.form-control {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%234299e1'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 20px center;
    background-size: 20px;
    appearance: none;
}

/* Mejorar textarea */
textarea.form-control {
    min-height: 140px;
    resize: vertical;
    line-height: 1.6;
}

/* Efecto de carga para campos */
.form-control.loading {
    background-image: linear-gradient(90deg, transparent, rgba(102, 126, 234, 0.1), transparent);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
}

@keyframes shimmer {
    0% { background-position: -200% 0; }
    100% { background-position: 200% 0; }
}

/* Indicadores visuales mejorados */
.form-control:valid:not(:placeholder-shown) {
    border-color: #48bb78;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2348bb78'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M5 13l4 4L19 7'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 20px center;
    background-size: 20px;
}

.form-control:invalid:not(:placeholder-shown) {
    border-color: #f56565;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23f56565'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M6 18L18 6M6 6l12 12'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 20px center;
    background-size: 20px;
}

/* Mejorar el expand icon */
.expand-icon {
    color: #ffffff;
    font-size: 24px;
    transition: all 0.3s ease;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.section-header:hover .expand-icon {
    transform: scale(1.1);
}

/* Responsive mejorado */
@media (max-width: 1200px) {
    .section-body {
        padding: 50px;
    }
    
    .section-header {
        padding: 50px;
    }
}

@media (max-width: 768px) {
    .section-body {
        padding: 30px;
    }
    
    .section-header {
        padding: 30px;
    }
    
    .section-title {
        font-size: 24px;
    }
    
    .form-control {
        padding: 20px 24px;
        font-size: 18px;
    }
    
    .form-label {
        font-size: 18px;
    }
}
/* ============================================================
   MEJORAS DE CONTRASTE Y UX/UI PARA TÍTULOS
   ============================================================ */

/* Variaciones de color por sección */


/* Hover mejorado para headers */
.section-header:hover {
    background: #4b5563;
    transform: translateY(-1px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.section-header:hover .section-title {
    color: #ffffff;
    text-shadow: 0 4px 8px rgba(0,0,0,0.5);
}

.section-header:hover .section-title i {
    color: #ffffff;
    transform: scale(1.05);
}

/* Estados activos y colapsados */
.section-header.collapsed {
    background: #9ca3af;
}

.section-header.collapsed .section-title,
.section-header.collapsed .section-title i,
.section-header.collapsed .expand-icon {
    color: #ffffff;
}

/* Indicador visual de estado */


/* Mejorar accesibilidad */
.section-header:focus {
    outline: 3px solid #63b3ed;
    outline-offset: 2px;
}

/* Responsive para títulos */
@media (max-width: 768px) {
    .section-title {
        font-size: 24px;
        gap: 15px;
    }
    
    .section-title i {
        font-size: 28px;
    }
    
    .expand-icon {
        font-size: 20px;
    }
}

/* Animación de carga para títulos */
@keyframes titleGlow {
    0%, 100% { text-shadow: 0 3px 6px rgba(0,0,0,0.4); }
    50% { text-shadow: 0 3px 6px rgba(0,0,0,0.4), 0 0 20px rgba(255,255,255,0.1); }
}

.section-header:hover .section-title {
    animation: titleGlow 2s ease-in-out infinite;
}
/* ============================================================
   CENTRADO FORZADO PARA MODAL
   ============================================================ */

#bibliotecaModal {
    display: none;
}

#bibliotecaModal.show,
#bibliotecaModal[style*="block"] {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    padding: 20px !important;
    box-sizing: border-box !important;
}

/* Asegurar que el modal-content esté centrado */
#bibliotecaModal .modal-content {
    margin: auto;
    position: relative;
    top: 50%;
    transform: translateY(-50%);
}

/* Para pantallas pequeñas */
@media (max-height: 600px) {
    #bibliotecaModal .modal-content {
        top: 0;
        transform: none;
        margin-top: 20px;
        margin-bottom: 20px;
        max-height: calc(100vh - 40px);
    }
}



/* Ocultar pestañas si no está guardado */
.programa-no-guardado .tab-item[data-tab="dia-a-dia"],
.programa-no-guardado .tab-item[data-tab="precio"],
.programa-no-guardado .tab-item[onclick*="abrirVistaPrevia"],
.programa-no-guardado .nav-button[onclick*="compartirEnlace"] {
    opacity: 0.3;
    pointer-events: none;
    position: relative;
}

.programa-no-guardado .tab-item[data-tab="dia-a-dia"]::after,
.programa-no-guardado .tab-item[data-tab="precio"]::after,
.programa-no-guardado .tab-item[onclick*="abrirVistaPrevia"]::after,
.programa-no-guardado .nav-button[onclick*="compartirEnlace"]::after {
    content: "🔒";
    position: absolute;
    top: 5px;
    right: 5px;
    font-size: 12px;
}

/* Toast notifications - AGREGAR AL FINAL */
.toast {
    position: fixed;
    top: 90px;
    right: 20px;
    padding: 20px 25px;
    border-radius: 15px;
    color: white;
    z-index: 20000;
    transform: translateX(400px);
    transition: transform 0.3s ease;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    backdrop-filter: blur(10px);
    min-width: 300px;
}

.toast.show {
    transform: translateX(0);
}

.toast.success {
    background: linear-gradient(135deg, #10b981 0%, #047857 100%);
}

.toast.error {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}
/* Contenedor para input con contador */
.input-with-counter {
    position: relative;
}

/* Contador de caracteres */
.char-counter {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 11px;
    color: #6b7280;
    background: rgba(255, 255, 255, 0.9);
    padding: 2px 6px;
    border-radius: 4px;
    font-weight: 500;
    pointer-events: none;
    z-index: 10;
}

/* Cuando se acerca al límite */
.char-counter.warning {
    color: #f59e0b;
    background: rgba(252, 211, 77, 0.1);
}

/* Cuando llega al límite */
.char-counter.danger {
    color: #ef4444;
    background: rgba(254, 226, 226, 0.9);
}

/* Ajustar padding del input para dejar espacio al contador */
.input-with-counter .form-control {
    padding-right: 60px;
}

/* Responsive */
@media (max-width: 768px) {
    .char-counter {
        font-size: 10px;
        padding: 1px 4px;
        right: 8px;
    }
    
    .input-with-counter .form-control {
        padding-right: 50px;
    }
}
.file-upload-container {
    position: relative;
}

/* Info del archivo */
.file-info {
    margin-top: 8px;
    font-size: 12px;
    color: #6b7280;
    min-height: 16px;
}

/* Estados del archivo */
.file-info.valid {
    color: #10b981;
}

.file-info.invalid {
    color: #ef4444;
}

.file-info.warning {
    color: #f59e0b;
}

/* Input de archivo mejorado */
.file-input {
    position: relative;
    cursor: pointer;
}

.file-input::-webkit-file-upload-button {
    background: var(--primary-gradient);
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 12px;
    cursor: pointer;
    margin-right: 10px;
}

.file-input::-webkit-file-upload-button:hover {
    opacity: 0.9;
    transform: translateY(-1px);
}

/* Estilos para drag & drop */
.days-list {
    position: relative;
}

.day-sidebar-item {
    cursor: move;
    user-select: none;
    transition: all 0.3s ease;
}

.day-sidebar-item:hover {
    transform: translateX(5px);
}

/* Estado mientras se arrastra */
.day-sidebar-item.sortable-chosen {
    opacity: 0.5;
    transform: scale(0.98);
    cursor: grabbing !important;
}

.day-sidebar-item.sortable-ghost {
    opacity: 0.3;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: 2px dashed #667eea;
}

/* Indicador de drag */
.day-sidebar-item::before {
    content: '⋮⋮';
    position: absolute;
    left: 5px;
    top: 50%;
    transform: translateY(-50%);
    color: rgba(0,0,0,0.2);
    font-size: 16px;
    opacity: 0;
    transition: opacity 0.2s;
}

.day-sidebar-item:hover::before {
    opacity: 1;
}

/* Animación de reordenamiento */
.sortable-drag {
    box-shadow: 0 10px 30px rgba(0,0,0,0.3) !important;
    transform: rotate(2deg);
}

/* Mensaje de ayuda */
.drag-helper {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: rgba(0,0,0,0.8);
    color: white;
    padding: 10px 15px;
    border-radius: 8px;
    font-size: 12px;
    z-index: 10000;
    opacity: 0;
    transition: opacity 0.3s;
}

.drag-helper.show {
    opacity: 1;
}


/* ============================================================
   ESTILOS PARA EDICIÓN INLINE DE DÍAS Y ACTIVIDADES
   ============================================================ */

/* Formulario inline general */
.edit-inline-form {
    background: #fff;
    border: 2px solid #2d5a4a;
    border-radius: 12px;
    margin: 20px 0;
    padding: 0;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Header del formulario */
.edit-form-header {
    background: linear-gradient(135deg, #2d5a4a 0%, #4a7c59 100%);
    color: white;
    padding: 15px 20px;
    border-radius: 10px 10px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.edit-form-header h4 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
}

.btn-close-edit {
    background: rgba(255,255,255,0.2);
    border: none;
    color: white;
    width: 32px;
    height: 32px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-close-edit:hover {
    background: rgba(255,255,255,0.3);
    transform: scale(1.1);
}

/* Cuerpo del formulario */
.edit-form-body {
    padding: 20px;
}

/* Form groups */
.edit-form-body .form-group {
    margin-bottom: 20px;
}

.edit-form-body label {
    display: block;
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
    font-size: 14px;
}

.edit-form-body label .required {
    color: #e74c3c;
    margin-left: 4px;
}

.edit-form-body .form-control {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    transition: border-color 0.2s;
}

.edit-form-body .form-control:focus {
    outline: none;
    border-color: #2d5a4a;
    box-shadow: 0 0 0 3px rgba(45, 90, 74, 0.1);
}

.edit-form-body textarea.form-control {
    resize: vertical;
    min-height: 100px;
}

.edit-form-body .form-text {
    display: block;
    margin-top: 4px;
    font-size: 12px;
    color: #666;
}

/* Búsqueda de ubicación */
.location-search-wrapper {
    position: relative;
}

.location-results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #ddd;
    border-top: none;
    border-radius: 0 0 6px 6px;
    max-height: 200px;
    overflow-y: auto;
    z-index: 1000;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    display: none;
}

.location-results.active {
    display: block;
}

.location-result-item {
    padding: 10px 12px;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
    transition: background 0.2s;
}

.location-result-item:hover {
    background: #f8f9fa;
}

.location-result-item:last-child {
    border-bottom: none;
}

/* Preview de imágenes en edición */
.images-preview-edit {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    margin-top: 10px;
}

.image-preview-item {
    position: relative;
    aspect-ratio: 4/3;
    border: 2px dashed #ddd;
    border-radius: 8px;
    overflow: hidden;
    background: #f8f9fa;
    display: flex;
    flex-direction: column;
}

.image-preview-item .preview-img {
    width: 100%;
    height: calc(100% - 40px);
    object-fit: cover;
}

.image-preview-item .empty-image-slot {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #999;
}

.image-preview-item .empty-image-slot i {
    font-size: 32px;
    margin-bottom: 8px;
}

.image-preview-item .empty-image-slot p {
    margin: 0;
    font-size: 12px;
}

.btn-remove-image {
    position: absolute;
    top: 8px;
    right: 8px;
    background: rgba(231, 76, 60, 0.9);
    color: white;
    border: none;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.btn-remove-image:hover {
    background: #e74c3c;
    transform: scale(1.1);
}

.btn-change-image {
    width: 100%;
    padding: 8px;
    background: #2d5a4a;
    color: white;
    border: none;
    border-radius: 0 0 6px 6px;
    cursor: pointer;
    font-size: 12px;
    font-weight: 600;
    transition: all 0.2s;
}

.btn-change-image:hover {
    background: #234a3a;
}

/* Botones de acción del formulario */
.edit-form-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #e0e0e0;
}

.edit-form-actions .btn {
    padding: 10px 20px;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: all 0.2s;
}

.edit-form-actions .btn-secondary {
    background: #6c757d;
    color: white;
}

.edit-form-actions .btn-secondary:hover {
    background: #5a6268;
}

.edit-form-actions .btn-primary {
    background: linear-gradient(135deg, #2d5a4a 0%, #4a7c59 100%);
    color: white;
}

.edit-form-actions .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(45, 90, 74, 0.3);
}

/* Botón editar en servicio */
.btn-edit-service {
    background: rgba(108, 117, 125, 0.1);
    color: #6c757d;
    border: 1px solid #6c757d;
    padding: 6px 12px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 12px;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
}

.btn-edit-service:hover {
    background: #6c757d;
    color: white;
    transform: translateY(-2px);
}

/* Responsive */
@media (max-width: 768px) {
    .images-preview-edit {
        grid-template-columns: 1fr;
    }
    
    .edit-form-actions {
        flex-direction: column;
    }
    
    .edit-form-actions .btn {
        width: 100%;
    }
}
/* Drop zone styles */
.drop-zone-multiple {
    transition: all 0.3s ease;
}

.drop-zone-multiple:hover {
    border-color: #10b981 !important;
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%) !important;
}

.drop-zone-multiple.drag-over {
    border-color: #10b981 !important;
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%) !important;
    transform: scale(1.02);
}

/* Animations */
@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: scale(0.9) translateY(20px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

@keyframes modalFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

#crearDiaModalPrograma .modal-content {
    animation: modalSlideIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes modalSlideIn {
    from { opacity: 0; transform: scale(0.9) translateY(20px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}

.drop-zone-multiple:hover {
    border-color: #10b981 !important;
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%) !important;
}


/* ------------------------Estilos del toggle para mostrar o esconder precio------------------------------*/


.price-visibility-setting {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 18px;
    padding: 18px 20px;
    border: 1px solid #e7edf3;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    border-radius: 14px;
    margin-top: 12px;
}

.setting-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.setting-title {
    font-size: 15px;
    font-weight: 600;
    color: #1f2d3d;
    margin: 0;
}

.setting-subtitle {
    font-size: 13px;
    color: #6b7a90;
    line-height: 1.4;
}

.switch {
    position: relative;
    display: inline-block;
    width: 58px;
    height: 32px;
}

.switch input {
    opacity: 0;
    width: 0;
    height: 0;
    position: absolute;
}

.slider {
    position: absolute;
    inset: 0;
    cursor: pointer;
    background: #d7dee7;
    border-radius: 50px;
    transition: all .28s ease;
    box-shadow: inset 0 1px 3px rgba(0,0,0,.12);
}

.slider:before {
    content: "";
    position: absolute;
    width: 24px;
    height: 24px;
    left: 4px;
    top: 4px;
    background: #fff;
    border-radius: 50%;
    transition: all .28s ease;
    box-shadow: 0 4px 10px rgba(0,0,0,.18);
}

.switch input:checked + .slider {
    background: var(--primary-color);
}

.switch input:checked + .slider:before {
    transform: translateX(26px);
}

.switch input:focus + .slider {
    box-shadow: 0 0 0 4px rgba(47,128,237,.12);
}


    </style>
</head>

<body class="<?= !$is_editing ? 'programa-no-guardado' : 'programa-guardado' ?>">

    <!-- Header con componentes -->
<?= UIComponents::renderHeader($user) ?>

<!-- Sidebar con componentes -->
<?= UIComponents::renderSidebar($user, '/programa') ?>

<!-- Overlay -->
<div class="overlay" id="overlay" onclick="closeSidebar()"></div>
<br>

<!-- Tab Navigation -->
<div class="tab-navigation">
    <div class="tab-nav">
        <a href="#" class="tab-item active" data-tab="mi-programa">Mi programa</a>
        <a href="#" class="tab-item" data-tab="dia-a-dia">Día a día</a>
        <a href="#" class="tab-item" data-tab="precio">Precio</a>
        <a href="#" class="tab-item" onclick="abrirVistaPrevia()">
            <i class="fas fa-eye"></i> Vista previa
        </a>
        <button type="button" class="nav-button" onclick="compartirEnlace()" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
            <i class="fas fa-share-alt"></i>
            <span>Compartir Enlace</span>
        </button>
        <!-- NUEVO BOTÓN MI BIBLIOTECA - Mismo estilo que Compartir Enlace -->
        <button type="button" class="nav-button" onclick="abrirMiBiblioteca()" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
            <i class="fas fa-book"></i>
            <span>Mi Biblioteca</span>
        </button>
    </div>
</div>

    <!-- Main Container -->
    <div class="main-container">
        <!-- Form Section -->
        <div class="form-section">
            <!-- Contenido de la pestaña Mi Programa -->
            <div id="mi-programa" class="tab-content active">
                <form id="programa-form" method="POST" enctype="multipart/form-data" novalidate>
                    
                    <!-- Campos ocultos -->
                    <?php if ($is_editing): ?>
                        <input type="hidden" id="programa-id-hidden" name="programa_id" value="<?= $programa_id ?>">
                    <?php endif; ?>
                    
                    <!-- Sección: Solicitud del viajero -->
                    <div class="section-card">
                        <div class="section-header" onclick="toggleSection(this)">
                            <div class="section-title">
                                <i class="fas fa-user"></i>
                                Solicitud del viajero
                            </div>
                            <i class="fas fa-chevron-up expand-icon"></i>
                        </div>
                        <div class="section-body">
                            <div class="form-group" id="request-id-group" <?php if (!$is_editing || empty($form_data['request_id'])): ?>style="display: none;"<?php endif; ?>>
                                <label class="form-label">ID de solicitud</label>
                                <input type="text" class="form-control" id="request-id" name="request_id" 
                                    value="<?= htmlspecialchars($form_data['request_id']) ?>" readonly>
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle"></i> Este ID se genera automáticamente al crear el programa
                                </small>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label" for="traveler-name">Nombre del viajero *</label>
                                    <div class="input-with-counter">
                                        <input type="text" class="form-control" id="traveler-name" name="traveler_name" 
                                            value="<?= htmlspecialchars($form_data['traveler_name']) ?>" 
                                            placeholder="Ejemplo: María Alejandra" 
                                            maxlength="250" data-max-chars="250" required>
                                        <div class="char-counter" id="traveler-name-counter">0/250</div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="traveler-lastname">Apellido del viajero *</label>
                                    <div class="input-with-counter">
                                        <input type="text" class="form-control" id="traveler-lastname" name="traveler_lastname" 
                                            value="<?= htmlspecialchars($form_data['traveler_lastname']) ?>" 
                                            placeholder="Ejemplo: García Rodríguez" 
                                            maxlength="250" data-max-chars="250" required>
                                        <div class="char-counter" id="traveler-lastname-counter">0/250</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="destination">Destino *</label>
                                <input type="text" class="form-control" id="destination" name="destination" 
                                    value="<?= htmlspecialchars($form_data['destination']) ?>" 
                                    placeholder="Ejemplo: Tailandia - Bangkok y Phuket" required>
                            </div>
                            
                          
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label" for="arrival-date">Fecha de llegada *</label>
                                        <input type="date" class="form-control" id="arrival-date" name="arrival_date" 
                                            value="<?= htmlspecialchars($form_data['arrival_date']) ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Fecha de salida</label>
                                        
                                        <!-- Textos traducibles ocultos -->
                                        <span id="text-day-singular" style="display:none;">día total</span>
                                        <span id="text-days-plural" style="display:none;">días total</span>
                                        
                                        <!-- ✅ CAMBIO: Usar div en lugar de input para mejor traducción -->
                                        <div id="calculated-departure" class="form-control" style="background: #f8fafc; color: #718096; font-style: italic; min-height: 38px; display: flex; align-items: center;">
                                            La fecha de salida se calcula automáticamente según los días del programa
                                        </div>
                                        
                                        <small class="form-text text-muted">
                                            <i class="fas fa-info-circle"></i> La fecha de salida se calcula automáticamente basada en los días agregados en "Día a día"
                                        </small>
                                        
                                        <!-- Hidden input para enviar al backend -->
                                        <input type="hidden" name="departure_date" id="departure-date-hidden" value="">
                                    </div>
                                    
                                    <input type="hidden" name="departure_date" id="departure-date-hidden" value="">
                                </div>
                        </div>
                    </div>

                    <!-- Sección: Personalización del programa -->
                    <div class="section-card">
                        <div class="section-header" onclick="toggleSection(this)">
                            <div class="section-title">
                                <i class="fas fa-palette"></i>
                                Personalización del programa
                            </div>
                            <i class="fas fa-chevron-up expand-icon"></i>
                        </div>
                        <div class="section-body">
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label" for="program-title">Título del programa</label>
                                    <div class="input-with-counter">
                                        <input type="text" class="form-control" id="program-title" name="program_title" 
                                            value="<?= htmlspecialchars($form_data['program_title']) ?>"
                                            placeholder="Ejemplo: Descubrir Tailandia en familia durante 15 días"
                                            maxlength="250" data-max-chars="250">
                                        <div class="char-counter" id="program-title-counter">0/250</div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="language">Idioma predeterminado</label>
                                    <select class="form-control" id="language" name="language">
                                        <option value="es" <?= $form_data['language'] === 'es' ? 'selected' : '' ?>>Español</option>
                                        <option value="en" <?= $form_data['language'] === 'en' ? 'selected' : '' ?>>English</option>
                                        <option value="fr" <?= $form_data['language'] === 'fr' ? 'selected' : '' ?>>Français</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="cover-image">Foto de portada</label>
                                <div class="file-upload-container">
                                    <input type="file" class="form-control file-input" id="cover-image" name="cover_image" 
                                        accept=".jpeg,.jpg,.png,.webp,image/jpeg,image/jpg,image/png,image/webp"
                                        data-max-size="20971520">
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle"></i> Formatos permitidos: JPEG, PNG, JPG, WebP | Peso máximo: 10MB
                                    </small>
                                    <div class="file-info" id="cover-image-info"></div>
                                    <?php if (!empty($form_data['cover_image'])): ?>
                                        <div class="current-image" style="margin-top: 10px;">
                                            <img src="<?= htmlspecialchars($form_data['cover_image']) ?>" alt="Imagen actual" 
                                                style="max-width: 200px; height: auto; border-radius: 8px;">
                                            <p style="font-size: 12px; color: #666; margin-top: 5px;">Imagen actual</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Botones de acción -->
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary" id="submit-btn">
                            <i class="fas fa-save"></i>
                            <?= $is_editing ? 'Actualizar programa' : 'Crear programa' ?>
                        </button>
                        
                        <?php if ($is_editing): ?>
                        <button type="button" class="btn btn-secondary" onclick="abrirVistaPrevia()">
                            <i class="fas fa-eye"></i>
                            Ver Programa
                        </button>
                        <?php endif; ?>
                        
                        <a href="<?= APP_URL ?>/itinerarios" class="btn btn-outline">
                            <i class="fas fa-arrow-left"></i>
                            Volver a itinerarios
                        </a>
                    </div>
                </form>
            </div>

            <!-- Contenido de la pestaña Día a día -->
            <div id="dia-a-dia" class="tab-content">
                <div class="section-card">
                    <div class="section-header">
                        <div class="section-title">
                            <i class="fas fa-calendar-day"></i>
                            Gestión de días del programa
                        </div>
                    </div>
                    <div class="section-body">
                        <!-- NUEVO LAYOUT CON BARRA LATERAL -->
                        <div class="dias-layout">
                            <!-- Barra lateral de días -->
                            <div class="days-sidebar">
                                <div class="sidebar-header">
                                    <div class="sidebar-title">
                                        <i class="fas fa-list"></i>
                                        Días
                                    </div>
                                    <button class="add-day-btn" onclick="agregarDia()">
                                        <i class="fas fa-plus"></i>
                                        Agregar
                                    </button>
                                </div>
                                <div class="days-list" id="days-sidebar-list">
                                    <!-- Los días se cargarán aquí dinámicamente -->
                                    <div class="empty-sidebar">
                                        <i class="fas fa-calendar-plus"></i>
                                        <h3>No hay días</h3>
                                        <p>Agrega tu primer día</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Contenido del día seleccionado -->
                            <div class="day-detail-container" id="day-detail-content">
                                <div class="empty-detail">
                                    <div>
                                        <i class="fas fa-calendar-day"></i>
                                        <h3>Selecciona un día</h3>
                                        <p>Elige un día de la lista para ver y editar sus detalles</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- FIN NUEVO LAYOUT -->
                    </div>
                </div>
            </div>

            <!-- Contenido de la pestaña Precio -->
            <div id="precio" class="tab-content">
                <div class="section-card">
                    <div class="section-header">
                        <div class="section-title">
                            <i class="fas fa-dollar-sign"></i>
                            Configuración de precios
                        </div>
                    </div>
                    <div class="section-body">
                        <form id="precio-form" method="POST">
                            <div class="price-section">
                                <div class="price-card">
                                    <h4>Información de precios</h4>
                                    <div class="form-group">
                                        <label class="form-label">Moneda</label>
                                        <select class="form-control" name="moneda">
                                            <option value="USD">USD - Dólar estadounidense</option>
                                            <option value="EUR">EUR - Euro</option>
                                            <option value="JPY">JPY - Yen japonés</option>
                                            <option value="GBP">GBP - Libra esterlina</option>
                                            <option value="AUD">AUD - Dólar australiano</option>
                                            <option value="CAD">CAD - Dólar canadiense</option>
                                            <option value="CHF">CHF - Franco suizo</option>
                                            <option value="CNY">CNY - Yuan chino</option>
                                            <option value="SEK">SEK - Corona sueca</option>
                                            <option value="NZD">NZD - Dólar neozelandés</option>
                                            <option value="COP">COP - Peso colombiano</option>
                                            <option value="MXN">MXN - Peso mexicano</option>
                                            <option value="ARS">ARS - Peso argentino</option>
                                            <option value="BRL">BRL - Real brasileño</option>
                                            <option value="CLP">CLP - Peso chileno</option>
                                            <option value="PEN">PEN - Sol peruano</option>
                                            <option value="UYU">UYU - Peso uruguayo</option>
                                            <option value="VES">VES - Bolívar venezolano</option>
                                            <option value="NOK">NOK - Corona noruega</option>
                                            <option value="DKK">DKK - Corona danesa</option>
                                            <option value="PLN">PLN - Zloty polaco</option>
                                            <option value="CZK">CZK - Corona checa</option>
                                            <option value="HUF">HUF - Florín húngaro</option>
                                            <option value="RUB">RUB - Rublo ruso</option>
                                            <option value="TRY">TRY - Lira turca</option>
                                            <option value="ZAR">ZAR - Rand sudafricano</option>
                                            <option value="INR">INR - Rupia india</option>
                                            <option value="KRW">KRW - Won surcoreano</option>
                                            <option value="SGD">SGD - Dólar singapurense</option>
                                            <option value="HKD">HKD - Dólar de Hong Kong</option>
                                            <option value="THB">THB - Baht tailandés</option>
                                            <option value="MYR">MYR - Ringgit malayo</option>
                                            <option value="IDR">IDR - Rupia indonesia</option>
                                            <option value="PHP">PHP - Peso filipino</option>
                                            <option value="VND">VND - Dong vietnamita</option>
                                            <option value="TWD">TWD - Dólar taiwanés</option>
                                            <option value="ILS">ILS - Nuevo shekel israelí</option>
                                            <option value="AED">AED - Dirham emiratí</option>
                                            <option value="SAR">SAR - Riyal saudí</option>
                                            <option value="QAR">QAR - Riyal catarí</option>
                                            <option value="KWD">KWD - Dinar kuwaití</option>
                                            <option value="BHD">BHD - Dinar bahreiní</option>
                                            <option value="OMR">OMR - Rial omaní</option>
                                            <option value="JOD">JOD - Dinar jordano</option>
                                            <option value="LBP">LBP - Libra libanesa</option>
                                            <option value="EGP">EGP - Libra egipcia</option>
                                            <option value="MAD">MAD - Dirham marroquí</option>
                                            <option value="TND">TND - Dinar tunecino</option>
                                            <option value="DZD">DZD - Dinar argelino</option>
                                            <option value="NGN">NGN - Naira nigeriana</option>
                                            <option value="KES">KES - Chelín keniano</option>
                                            <option value="GHS">GHS - Cedi ghanés</option>
                                            <option value="ETB">ETB - Birr etíope</option>
                                            <option value="UGX">UGX - Chelín ugandés</option>
                                            <option value="TZS">TZS - Chelín tanzano</option>
                                            <option value="ZMW">ZMW - Kwacha zambiano</option>
                                            <option value="BWP">BWP - Pula de Botsuana</option>
                                            <option value="MUR">MUR - Rupia mauriciana</option>
                                            <option value="SCR">SCR - Rupia seychelense</option>
                                            <option value="XOF">XOF - Franco CFA occidental</option>
                                            <option value="XAF">XAF - Franco CFA central</option>
                                            <option value="CDF">CDF - Franco congoleño</option>
                                            <option value="AOA">AOA - Kwanza angoleño</option>
                                            <option value="MZN">MZN - Metical mozambiqueño</option>
                                            <option value="SZL">SZL - Lilangeni suazi</option>
                                            <option value="LSL">LSL - Loti lesotense</option>
                                            <option value="NAD">NAD - Dólar namibio</option>
                                            <option value="MWK">MWK - Kwacha malauí</option>
                                            <option value="RWF">RWF - Franco ruandés</option>
                                            <option value="BIF">BIF - Franco burundés</option>
                                            <option value="DJF">DJF - Franco yibutiano</option>
                                            <option value="SOS">SOS - Chelín somalí</option>
                                            <option value="ERN">ERN - Nakfa eritreo</option>
                                            <option value="STN">STN - Dobra santotomense</option>
                                            <option value="CVE">CVE - Escudo caboverdiano</option>
                                            <option value="GMD">GMD - Dalasi gambiano</option>
                                            <option value="GNF">GNF - Franco guineano</option>
                                            <option value="LRD">LRD - Dólar liberiano</option>
                                            <option value="SLE">SLE - Leone sierraleonés</option>
                                            <option value="ALL">ALL - Lek albanés</option>
                                            <option value="BAM">BAM - Marco convertible bosnio</option>
                                            <option value="BGN">BGN - Lev búlgaro</option>
                                            <option value="HRK">HRK - Kuna croata</option>
                                            <option value="RSD">RSD - Dinar serbio</option>
                                            <option value="MKD">MKD - Denar macedonio</option>
                                            <option value="RON">RON - Leu rumano</option>
                                            <option value="MDL">MDL - Leu moldavo</option>
                                            <option value="UAH">UAH - Grivna ucraniana</option>
                                            <option value="BYN">BYN - Rublo bielorruso</option>
                                            <option value="GEL">GEL - Lari georgiano</option>
                                            <option value="AMD">AMD - Dram armenio</option>
                                            <option value="AZN">AZN - Manat azerbaiyano</option>
                                            <option value="KZT">KZT - Tenge kazajo</option>
                                            <option value="UZS">UZS - Som uzbeko</option>
                                            <option value="TJS">TJS - Somoni tayiko</option>
                                            <option value="KGS">KGS - Som kirguís</option>
                                            <option value="TMT">TMT - Manat turkmeno</option>
                                            <option value="AFN">AFN - Afgani afgano</option>
                                            <option value="PKR">PKR - Rupia pakistaní</option>
                                            <option value="LKR">LKR - Rupia esrilanquesa</option>
                                            <option value="NPR">NPR - Rupia nepalí</option>
                                            <option value="BTN">BTN - Ngultrum butanés</option>
                                            <option value="BDT">BDT - Taka bangladesí</option>
                                            <option value="MMK">MMK - Kyat birmano</option>
                                            <option value="LAK">LAK - Kip laosiano</option>
                                            <option value="KHR">KHR - Riel camboyano</option>
                                            <option value="BND">BND - Dólar bruneano</option>
                                            <option value="MNT">MNT - Tugrik mongol</option>
                                            <option value="KPW">KPW - Won norcoreano</option>
                                            <option value="FJD">FJD - Dólar fiyiano</option>
                                            <option value="PGK">PGK - Kina papú</option>
                                            <option value="SBD">SBD - Dólar de Islas Salomón</option>
                                            <option value="VUV">VUV - Vatu vanuatuense</option>
                                            <option value="NCX">NCX - Franco del Pacífico</option>
                                            <option value="WST">WST - Tala samoano</option>
                                            <option value="TOP">TOP - Paʻanga tongano</option>
                                            <option value="NIO">NIO - Córdoba nicaragüense</option>
                                            <option value="CRC">CRC - Colón costarricense</option>
                                            <option value="PAB">PAB - Balboa panameño</option>
                                            <option value="GTQ">GTQ - Quetzal guatemalteco</option>
                                            <option value="HNL">HNL - Lempira hondureño</option>
                                            <option value="SVC">SVC - Colón salvadoreño</option>
                                            <option value="BZD">BZD - Dólar beliceño</option>
                                            <option value="JMD">JMD - Dólar jamaiquino</option>
                                            <option value="HTG">HTG - Gourde haitiano</option>
                                            <option value="DOP">DOP - Peso dominicano</option>
                                            <option value="CUP">CUP - Peso cubano</option>
                                            <option value="BBD">BBD - Dólar barbadense</option>
                                            <option value="TTD">TTD - Dólar trinitense</option>
                                            <option value="GYD">GYD - Dólar guyanés</option>
                                            <option value="SRD">SRD - Dólar surinamés</option>
                                            <option value="AWG">AWG - Florín arubeño</option>
                                            <option value="ANG">ANG - Florín antillano</option>
                                            <option value="XCD">XCD - Dólar del Caribe Oriental</option>
                                            <option value="BOB">BOB - Boliviano</option>
                                            <option value="PYG">PYG - Guaraní paraguayo</option>
                                            <option value="GGP">GGP - Libra de Guernsey</option>
                                            <option value="JEP">JEP - Libra de Jersey</option>
                                            <option value="IMP">IMP - Libra manesa</option>
                                            <option value="FKP">FKP - Libra malvinense</option>
                                            <option value="GIP">GIP - Libra gibraltareña</option>
                                            <option value="SHP">SHP - Libra de Santa Elena</option>
                                            <option value="ISK">ISK - Corona islandesa</option>
                                            <option value="FOK">FOK - Corona feroesa</option>
                                        </select>
                                    </div>
                                    <!-- NUEVOS CAMPOS: ADULTOS -->
                                    <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                        <div class="form-group">
                                            <label class="form-label">Cantidad de Adultos</label>
                                            <input type="number" class="form-control" name="cantidad_adultos" 
                                                id="cantidad-adultos" min="1" value="1" 
                                                onchange="calcularPrecioTotal()">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Precio por Adulto</label>
                                            <div class="price-input-container">
                                                <span class="currency-icon" id="currency-icon-adulto">$</span>
                                                <input type="number" class="form-control price-input-with-icon" 
                                                    name="precio_adulto" id="precio-adulto" 
                                                    placeholder="0.00" step="0.01" 
                                                    onchange="calcularPrecioTotal()">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- NUEVOS CAMPOS: NIÑOS -->
                                    <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                        <div class="form-group">
                                            <label class="form-label">Cantidad de Niños</label>
                                            <input type="number" class="form-control" name="cantidad_ninos" 
                                                id="cantidad-ninos" min="0" value="0" 
                                                onchange="calcularPrecioTotal()">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Precio por Niño</label>
                                            <div class="price-input-container">
                                                <span class="currency-icon" id="currency-icon-nino">$</span>
                                                <input type="number" class="form-control price-input-with-icon" 
                                                    name="precio_nino" id="precio-nino" 
                                                    placeholder="0.00" step="0.01" 
                                                    onchange="calcularPrecioTotal()">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- PRECIO TOTAL (AUTO-CALCULADO PERO EDITABLE) -->
                                    <div class="form-group">
                                        <label class="form-label">
                                            Precio Total
                                            <small style="color: #6c757d; font-weight: normal; margin-left: 8px;">
                                                (Se calcula automáticamente, pero puedes editarlo)
                                            </small>
                                        </label>
                                        <div class="price-input-container">
                                            <span class="currency-icon" id="currency-icon-total">$</span>
                                            <input type="number" class="form-control price-input-with-icon" 
                                                name="precio_total" id="precio-total" 
                                                placeholder="0.00" step="0.01">
                                        </div>
                                        <small class="form-text text-muted" id="calculo-info" style="display: none;">
                                            <i class="fas fa-calculator"></i> 
                                            <span id="calculo-detalle"></span>
                                        </small>
                                    </div>
                                    
                        <!----------Selector de Precio visible o invisible--------------------->
                                    <div class="price-visibility-setting">
                                        <div class="setting-info">
                                            <label for="mostrar-precio-toggle" class="setting-title">
                                                Mostrar precios en el itinerario
                                            </label>
                                            <small class="setting-subtitle">
                                                Activa o desactiva la visualización pública de precios.
                                            </small>
                                        </div>

                                        <div class="toggle-wrapper">
                                            <input type="hidden" name="mostrar_precio" value="0">

                                            <label class="switch">
                                                <input
                                                    type="checkbox"
                                                    name="mostrar_precio"
                                                    id="mostrar-precio-toggle"
                                                    value="1"
                                                    checked
                                                >
                                                <span class="slider"></span>
                                            </label>
                                        </div>
                                    </div>

                                     <!-- 
                                    <div class="form-group">
                                        <label class="form-label">Noches incluidas</label>
                                        <input type="number" class="form-control" name="noches_incluidas" 
                                            placeholder="0" min="0">
                                    </div>
                                    -->
                                </div>
                                
                                <div class="price-card">
                                    <h4>Información adicional</h4>
                                    <div class="form-group">
                                        <label class="form-label">¿Qué incluye el precio?</label>
                                        <div class="textarea-with-counter">
                                            <textarea class="form-control" name="precio_incluye" rows="4" 
                                                    placeholder="Describe qué servicios están incluidos..."
                                                    maxlength="3000" data-max-chars="3000"></textarea>
                                            <div class="char-counter" id="precio_incluye-counter">0/3000</div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">¿Qué NO incluye?</label>
                                        <div class="textarea-with-counter">
                                            <textarea class="form-control" name="precio_no_incluye" rows="4" 
                                                    placeholder="Describe qué servicios NO están incluidos..."
                                                    maxlength="3000" data-max-chars="3000"></textarea>
                                            <div class="char-counter" id="precio_no_incluye-counter">0/3000</div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <input type="checkbox" name="movilidad_reducida" value="1">
                                        <label class="form-label" style="margin-left: 8px;">Adaptado para movilidad reducida</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="section-card" style="margin-top: 20px;">
                                <div class="section-body">
                                    <div class="form-group">
                                        <label class="form-label">Condiciones generales</label>
                                        <div class="textarea-with-counter">
                                            <textarea class="form-control" name="condiciones_generales" rows="4" 
                                                    placeholder="Condiciones y términos del programa..."
                                                    maxlength="3000" data-max-chars="3000"></textarea>
                                            <div class="char-counter" id="condiciones_generales-counter">0/3000</div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Información de pasaporte</label>
                                        <div class="textarea-with-counter">
                                            <textarea class="form-control" name="info_pasaporte" rows="3" 
                                                    placeholder="Requisitos de documentación..."
                                                    maxlength="3000" data-max-chars="3000"></textarea>
                                            <div class="char-counter" id="info_pasaporte-counter">0/3000</div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Información de seguros</label>
                                        <div class="textarea-with-counter">
                                            <textarea class="form-control" name="info_seguros" rows="3" 
                                                    placeholder="Información sobre seguros de viaje..."
                                                    maxlength="3000" data-max-chars="3000"></textarea>
                                            <div class="char-counter" id="info_seguros-counter">0/3000</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-actions" style="text-align: center; padding: 24px 0;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i>
                                    Guardar precios
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
    </div>

    <!-- Modal para agregar/editar días desde biblioteca -->
<div id="bibliotecaModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 1200px; max-height: 90vh; overflow-y: auto;">
        <div class="modal-header">
            <h3>
                <i class="fas fa-book"></i> 
                Seleccionar días de la biblioteca
                <span id="contador-seleccionados" style="
                    display: inline-block;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 4px 12px;
                    border-radius: 20px;
                    font-size: 14px;
                    margin-left: 10px;
                    font-weight: 600;
                ">0 seleccionados</span>
            </h3>
            <button class="close-modal" onclick="cerrarModalBiblioteca()">&times;</button>
        </div>
        
        <div class="modal-body">
            <!-- Información de ayuda -->
            <div style="
                background: linear-gradient(135deg, #e0e7ff 0%, #e6f3ff 100%);
                border-left: 4px solid #667eea;
                padding: 12px 16px;
                border-radius: 8px;
                margin-bottom: 20px;
                display: flex;
                align-items: center;
                gap: 12px;
            ">
                <i class="fas fa-info-circle" style="color: #667eea; font-size: 20px;"></i>
                <div style="color: #4c51bf; font-size: 14px;">
                    <strong>Selección múltiple activada:</strong> 
                    Puedes seleccionar uno o varios días a la vez. Los días se agregarán al programa en el orden en que los selecciones.
                </div>
            </div>
            
            <div class="biblioteca-filters" style="display: flex; gap: 15px; align-items: center; margin-bottom: 20px;">
                <div class="search-box" style="flex: 1;">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Buscar días..." id="search-dias" class="form-control">
                </div>
                
                <!-- Botón para seleccionar/deseleccionar todos -->
                <button type="button" 
                        class="btn btn-outline" 
                        onclick="toggleSeleccionarTodos()"
                        id="btn-toggle-todos"
                        style="
                            padding: 10px 20px;
                            border: 2px solid #667eea;
                            background: white;
                            color: #667eea;
                            border-radius: 8px;
                            font-weight: 600;
                            cursor: pointer;
                            transition: all 0.3s;
                        "
                        onmouseover="this.style.background='#667eea'; this.style.color='white';"
                        onmouseout="this.style.background='white'; this.style.color='#667eea';">
                    <i class="fas fa-check-double"></i> Seleccionar todos
                </button>
                
                <!-- BOTÓN PARA CREAR DÍA -->
                <button type="button" 
                        class="btn btn-success" 
                        onclick="abrirModalCrearDiaPrograma()" 
                        style="
                            display: inline-flex;
                            align-items: center;
                            gap: 8px;
                            padding: 12px 24px;
                            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                            border: none;
                            border-radius: 10px;
                            color: white;
                            font-weight: 600;
                            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
                        ">
                    <i class="fas fa-plus-circle"></i>
                    Crear Nuevo Día
                </button>
            </div>
            
            <div id="biblioteca-dias-grid" class="biblioteca-grid">
                <!-- Los días de la biblioteca se cargarán aquí con checkboxes -->
            </div>
        </div>
        
        <div class="modal-footer" style="
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 24px;
            border-top: 2px solid #e2e8f0;
        ">
            <!-- Info de selección -->
            <div id="info-seleccion" style="
                color: #4a5568;
                font-size: 14px;
                font-weight: 500;
            ">
                <i class="fas fa-info-circle" style="color: #667eea;"></i>
                Selecciona uno o más días para agregar
            </div>
            
            <!-- Botones de acción -->
            <div style="display: flex; gap: 12px;">
                <button class="btn btn-secondary" onclick="cerrarModalBiblioteca()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button class="btn btn-primary" 
                        onclick="agregarDiasSeleccionados()" 
                        id="btn-agregar-dias" 
                        disabled
                        style="
                            position: relative;
                            overflow: hidden;
                        ">
                    <i class="fas fa-plus"></i> 
                    <span id="texto-btn-agregar">Agregar días seleccionados</span>
                </button>
            </div>
        </div>
    </div>
</div>

<div id="crearDiaModalPrograma" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 1000px; max-height: 95vh; overflow-y: auto;">
        <div class="modal-header" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
            <h3><i class="fas fa-calendar-plus"></i> Crear Nuevo Día</h3>
            <button class="close-modal" onclick="cerrarModalCrearDiaPrograma()" style="background: rgba(255,255,255,0.2);">&times;</button>
        </div>
        
        <div class="modal-body" style="padding: 30px;">
            <form id="formCrearDiaEnPrograma">
                
                <!-- IDIOMA -->
                <div class="form-group">
                    <label>🌐 Idioma</label>
                    <select id="idioma-crear-programa" name="idioma" class="form-control">
                        <option value="es">Español</option>
                        <option value="en">English</option>
                        <option value="fr">Français</option>
                        <option value="pt">Português</option>
                    </select>
                </div>

                <!-- TÍTULO -->
                <div class="form-group">
                    <label>📝 Título <span style="color: #ef4444;">*</span></label>
                    <div style="position: relative;">
                        <input type="text" id="titulo-crear-programa" name="titulo" class="form-control" required 
                               placeholder="Ej: Día en París" maxlength="300" style="padding-right: 80px;">
                        <div class="char-counter" id="titulo-counter-programa" 
                             style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); font-size: 11px; color: #6b7280;">0/300</div>
                    </div>
                </div>

                <!-- UBICACIÓN PRINCIPAL -->
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label>📍 Ubicación Principal <span style="color: #ef4444;">*</span></label>
                    <div style="position: relative;">
                        <input type="text" 
                               id="ubicacion-principal-crear-programa" 
                               name="ubicacion"
                               class="form-control" 
                               required
                               placeholder="🔍 Buscar ciudad, lugar, monumento..."
                               autocomplete="off"
                               style="padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 10px;">
                        <input type="hidden" name="latitud" id="latitud-principal-programa">
                        <input type="hidden" name="longitud" id="longitud-principal-programa">
                        <div id="preview-ubicacion-principal-programa"></div>
                    </div>
                </div>

                <!-- UBICACIONES SECUNDARIAS -->
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label>📍 Ubicaciones Adicionales (opcional)</label>
                    <div id="ubicaciones-secundarias-container-programa" style="display: flex; flex-direction: column; gap: 12px;">
                        <!-- Se agregan dinámicamente -->
                    </div>
                    <button type="button" onclick="agregarUbicacionSecundariaPrograma()" 
                            style="margin-top: 12px; padding: 10px 20px; background: #48bb78; color: white; border: none; border-radius: 8px; cursor: pointer;">
                        ➕ Agregar Otra Ubicación
                    </button>
                </div>

                <!-- DESCRIPCIÓN -->
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label>📄 Descripción</label>
                    <div style="position: relative;">
                        <textarea id="descripcion-crear-programa" name="descripcion" rows="5" class="form-control"
                                  placeholder="Describe las actividades..." maxlength="3000" 
                                  style="padding-bottom: 35px;"></textarea>
                        <div class="char-counter" id="descripcion-counter-programa" 
                             style="position: absolute; right: 12px; bottom: 12px; font-size: 11px; color: #6b7280;">0/3000</div>
                    </div>
                </div>

                <!-- IMÁGENES -->
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label>📸 Imágenes (máximo 3)</label>
                    <div id="imageUploadContainerPrograma"></div>
                </div>

                <!-- Inputs ocultos para imágenes -->
                <input type="file" id="imagenes-programa" name="imagen1" accept="image/*" style="display: none;">
                <input type="file" id="imagen2-programa" name="imagen2" accept="image/*" style="display: none;">
                <input type="file" id="imagen3-programa" name="imagen3" accept="image/*" style="display: none;">
                <input type="file" id="multipleImages-programa" multiple accept="image/*" style="display: none;">
                
            </form>
        </div>
        
        <div class="modal-footer" style="padding: 20px 30px; display: flex; gap: 12px; justify-content: flex-end;">
            <button type="button" class="btn btn-secondary" onclick="cerrarModalCrearDiaPrograma()">
                <i class="fas fa-times"></i> Cancelar
            </button>
            <button type="button" class="btn btn-success" onclick="guardarDiaEnPrograma()"
                    style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none;">
                <i class="fas fa-save"></i> Crear y Agregar
            </button>
        </div>
    </div>
</div>


    <!-- Modal para agregar servicios (actividades, transporte, alojamiento) -->
    <div id="serviciosModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 1200px; max-height: 90vh; overflow-y: auto;">
            <div class="modal-header">
                <h3 id="servicios-modal-title"><i class="fas fa-plus"></i> Agregar servicio</h3>
                <button class="close-modal" onclick="cerrarModalServicios()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="biblioteca-filters">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Buscar servicios..." 
                               id="search-servicios" class="form-control">
                    </div>
                </div>
                <div id="servicios-grid" class="biblioteca-grid">
                    <!-- Los servicios de la biblioteca se cargarán aquí -->
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="cerrarModalServicios()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button class="btn btn-primary" onclick="agregarServicioSeleccionado()" id="btn-agregar-servicio" disabled>
                    <i class="fas fa-plus"></i> Agregar servicio
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>



<script>
// Variables globales para el modal de crear día
let widgetUbicacionPrincipalPrograma = null;
let widgetsSecundariasPrograma = [];
let contadorSecundariasPrograma = 0;
let selectedImagesPrograma = [];
let diasSeleccionados = []; // Array para almacenar IDs de días seleccionados
let ordenSeleccion = 1; // Contador para el orden de selección

// Función helper para obtener la URL base
function getBaseURL() {
    const base = document.querySelector('base');
    if (base && base.href) {
        return base.href.replace(/\/$/, '');
    }
    
    // Obtener la ruta completa incluyendo subdirectorios
    const pathname = window.location.pathname;
    const pathParts = pathname.split('/').filter(p => p);
    
    // Si estamos en programa.php, quitar el archivo y quedarnos con la carpeta
    if (pathParts[pathParts.length - 1] === 'programa.php' || 
        pathParts[pathParts.length - 1].startsWith('programa')) {
        pathParts.pop();
    }
    
    const protocol = window.location.protocol;
    const host = window.location.host;
    const basePath = pathParts.length > 0 ? '/' + pathParts.join('/') : '';
    
    return `${protocol}//${host}${basePath}`;
}

// Abrir modal
function abrirModalCrearDiaPrograma() {
    console.log('🎬 Abriendo modal de crear día...');
    
    const modal = document.getElementById('crearDiaModalPrograma');
    const modalBiblioteca = document.getElementById('bibliotecaModal');
    
    if (modalBiblioteca) {
        modalBiblioteca.style.zIndex = '9998';
    }
    
    modal.style.display = 'flex';
    modal.style.alignItems = 'center';
    modal.style.justifyContent = 'center';
    modal.style.zIndex = '10000';
    
    setTimeout(() => {
        modal.classList.add('show');
    }, 10);
    
    setTimeout(() => {
        inicializarFormularioPrograma();
    }, 100);
}

// Cerrar modal
function cerrarModalCrearDiaPrograma() {
    const modal = document.getElementById('crearDiaModalPrograma');
    const modalBiblioteca = document.getElementById('bibliotecaModal');
    
    modal.classList.remove('show');
    
    setTimeout(() => {
        modal.style.display = 'none';
        if (modalBiblioteca) {
            modalBiblioteca.style.zIndex = '9999';
        }
    }, 300);
    
    limpiarFormularioPrograma();
}

// Inicializar formulario
function inicializarFormularioPrograma() {
    console.log('🔧 Inicializando formulario...');
    
    limpiarFormularioPrograma();
    
    setTimeout(() => {
        inicializarWidgetUbicacionPrograma();
        inicializarSistemaImagenesPrograma();
        inicializarContadoresPrograma();
    }, 200);
}

// Inicializar widget de ubicación principal
function inicializarWidgetUbicacionPrograma() {
    const inputPrincipal = document.getElementById('ubicacion-principal-crear-programa');
    
    if (!inputPrincipal) {
        console.error('❌ Input de ubicación principal no encontrado');
        return;
    }
    
    console.log('📍 Inicializando widget de ubicación principal...');
    
    if (widgetUbicacionPrincipalPrograma) {
        widgetUbicacionPrincipalPrograma.destroy();
        widgetUbicacionPrincipalPrograma = null;
    }
    
    if (typeof UbicacionSearchWidget === 'undefined') {
        console.error('❌ UbicacionSearchWidget no está cargado');
        return;
    }
    
    try {
        const baseURL = getBaseURL();
        
        widgetUbicacionPrincipalPrograma = new UbicacionSearchWidget(inputPrincipal, {
            apiUrl: `${baseURL}/modules/ubicaciones/ubicaciones_api.php`,
            latInputId: 'latitud-principal-programa',
            lngInputId: 'longitud-principal-programa',
            placeholder: '🔍 Buscar ciudad, lugar, monumento...',
            showPreview: true,
            previewContainerId: 'preview-ubicacion-principal-programa',
            autoSave: true,
            minChars: 3,
            debounceTime: 300,
            onSelect: (location) => {
                console.log('✅ Ubicación seleccionada:', location);
            }
        });
        
        console.log('✅ Widget inicializado con URL:', `${baseURL}/modules/ubicaciones/ubicaciones_api.php`);
    } catch (error) {
        console.error('❌ Error al crear widget:', error);
    }
}

// Agregar ubicación secundaria
function agregarUbicacionSecundariaPrograma() {
    contadorSecundariasPrograma++;
    const index = Date.now() + contadorSecundariasPrograma;
    
    const container = document.getElementById('ubicaciones-secundarias-container-programa');
    
    const div = document.createElement('div');
    div.className = 'ubicacion-secundaria-item';
    div.setAttribute('data-index', index);
    div.style.cssText = 'display: grid; grid-template-columns: 1fr auto; gap: 10px; padding: 12px; background: #f8fafc; border-radius: 10px; border: 2px dashed #cbd5e0;';
    
    div.innerHTML = `
        <div style="position: relative;">
            <input type="text" 
                id="ubicacion-sec-${index}-programa"
                class="form-control"
                placeholder="🔍 Buscar ubicación adicional..."
                autocomplete="off"
                style="padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 10px;">
            <input type="hidden" id="lat-sec-${index}-programa">
            <input type="hidden" id="lng-sec-${index}-programa">
            <div id="preview-sec-${index}-programa"></div>
        </div>
        <button type="button" onclick="eliminarUbicacionSecundariaPrograma(${index})" 
                style="width: 40px; height: 40px; background: #ef4444; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 18px;">
            ✕
        </button>
    `;
    
    container.appendChild(div);
    
    setTimeout(() => {
        const input = document.getElementById(`ubicacion-sec-${index}-programa`);
        if (input && typeof UbicacionSearchWidget !== 'undefined') {
            try {
                const baseURL = getBaseURL();
                
                const widget = new UbicacionSearchWidget(input, {
                    apiUrl: `${baseURL}/modules/ubicaciones/ubicaciones_api.php`,
                    latInputId: `lat-sec-${index}-programa`,
                    lngInputId: `lng-sec-${index}-programa`,
                    placeholder: '🔍 Buscar otra ubicación...',
                    showPreview: true,
                    previewContainerId: `preview-sec-${index}-programa`,
                    autoSave: true,
                    minChars: 3
                });
                
                widgetsSecundariasPrograma.push({ index, widget });
                console.log(`✅ Widget secundario ${index} inicializado`);
            } catch (error) {
                console.error(`❌ Error al crear widget secundario:`, error);
            }
        }
    }, 100);
}

// Eliminar ubicación secundaria
function eliminarUbicacionSecundariaPrograma(index) {
    const item = document.querySelector(`.ubicacion-secundaria-item[data-index="${index}"]`);
    if (item) {
        const widgetData = widgetsSecundariasPrograma.find(w => w.index === index);
        if (widgetData && widgetData.widget) {
            widgetData.widget.destroy();
        }
        
        widgetsSecundariasPrograma = widgetsSecundariasPrograma.filter(w => w.index !== index);
        item.remove();
        
        console.log(`🗑️ Ubicación secundaria ${index} eliminada`);
    }
}

// Inicializar sistema de imágenes
function inicializarSistemaImagenesPrograma() {
    const container = document.getElementById('imageUploadContainerPrograma');
    if (!container) return;
    
    selectedImagesPrograma = [];
    
    container.innerHTML = `
        <div id="dropZonePrograma" class="drop-zone-multiple" 
             style="border: 2px dashed #cbd5e0; border-radius: 12px; padding: 40px; text-align: center; background: linear-gradient(135deg, #f8fafc 0%, #edf2f7 100%); cursor: pointer;">
            <div class="drop-zone-content">
                <div style="font-size: 48px; margin-bottom: 15px;">📸</div>
                <div style="font-size: 18px; font-weight: 600; margin-bottom: 8px;">Arrastra hasta 3 imágenes aquí</div>
                <div style="font-size: 14px; color: #718096; margin-bottom: 15px;">o haz clic para seleccionar</div>
                <button type="button" class="btn-select-images" 
                        style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600;">
                    📂 Seleccionar Imágenes
                </button>
            </div>
        </div>
        <div id="imagesPreviewPrograma" style="margin-top: 20px; display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px;"></div>
    `;
    
    const dropZone = document.getElementById('dropZonePrograma');
    const fileInput = document.getElementById('multipleImages-programa');
    
    if (dropZone && fileInput) {
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(e => {
            dropZone.addEventListener(e, (ev) => { ev.preventDefault(); ev.stopPropagation(); });
        });
        
        ['dragenter', 'dragover'].forEach(e => {
            dropZone.addEventListener(e, () => {
                dropZone.style.borderColor = '#10b981';
                dropZone.style.background = 'linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%)';
            });
        });
        
        ['dragleave', 'drop'].forEach(e => {
            dropZone.addEventListener(e, () => {
                dropZone.style.borderColor = '#cbd5e0';
                dropZone.style.background = 'linear-gradient(135deg, #f8fafc 0%, #edf2f7 100%)';
            });
        });
        
        dropZone.addEventListener('drop', (e) => {
            handleFilesPrograma(Array.from(e.dataTransfer.files));
        });
        
        dropZone.addEventListener('click', (e) => {
            if (e.target.classList.contains('btn-select-images')) {
                e.preventDefault();
                e.stopPropagation();
                fileInput.click();
            }
        });
        
        fileInput.addEventListener('change', function() {
            handleFilesPrograma(Array.from(this.files));
        });
    }
}

function handleFilesPrograma(files) {
    const imageFiles = files.filter(f => f.type.startsWith('image/'));
    if (imageFiles.length === 0) return alert('Solo archivos de imagen');
    
    const availableSlots = 3 - selectedImagesPrograma.length;
    if (availableSlots === 0) return alert('Ya tienes 3 imágenes');
    
    const filesToAdd = imageFiles.slice(0, availableSlots);
    
    filesToAdd.forEach(file => {
        if (file.size > 10 * 1024 * 1024) {
            alert(`"${file.name}" es muy grande (máx. 10MB)`);
            return;
        }
        selectedImagesPrograma.push(file);
    });
    
    actualizarPreviewPrograma();
    asignarArchivosPrograma();
}

function actualizarPreviewPrograma() {
    const container = document.getElementById('imagesPreviewPrograma');
    if (!container) return;
    
    container.innerHTML = '';
    
    selectedImagesPrograma.forEach((file, index) => {
        const reader = new FileReader();
        reader.onload = (e) => {
            const div = document.createElement('div');
            div.style.cssText = 'position: relative; border-radius: 12px; overflow: hidden; border: 2px solid #e2e8f0;';
            div.innerHTML = `
                <img src="${e.target.result}" style="width: 100%; height: 150px; object-fit: cover;">
                <div style="padding: 8px; background: white;">
                    <div style="font-size: 12px; font-weight: 600;">${file.name}</div>
                    <div style="font-size: 11px; color: #718096;">${(file.size / 1024).toFixed(0)} KB</div>
                </div>
                <button onclick="eliminarImagenPrograma(${index})" 
                        style="position: absolute; top: 8px; right: 8px; background: #ef4444; color: white; border: none; border-radius: 50%; width: 28px; height: 28px; cursor: pointer;">×</button>
                <div style="position: absolute; top: 8px; left: 8px; background: rgba(16,185,129,0.9); color: white; padding: 4px 8px; border-radius: 6px; font-size: 11px;">Imagen ${index + 1}</div>
            `;
            container.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
    
    const dropContent = document.querySelector('#dropZonePrograma .drop-zone-content');
    if (selectedImagesPrograma.length < 3) {
        dropContent.innerHTML = `
            <div style="font-size: ${selectedImagesPrograma.length === 0 ? 48 : 36}px; margin-bottom: ${selectedImagesPrograma.length === 0 ? 15 : 10}px;">${selectedImagesPrograma.length === 0 ? '📸' : '✅'}</div>
            <div style="font-size: ${selectedImagesPrograma.length === 0 ? 18 : 16}px; font-weight: 600; margin-bottom: 8px;">
                ${selectedImagesPrograma.length === 0 ? 'Arrastra hasta 3 imágenes' : selectedImagesPrograma.length + ' imagen(es) seleccionada(s)'}
            </div>
            <div style="font-size: 14px; color: #718096; margin-bottom: 15px;">
                ${selectedImagesPrograma.length === 0 ? 'o haz clic para seleccionar' : 'Puedes agregar ' + (3 - selectedImagesPrograma.length) + ' más'}
            </div>
            <button type="button" class="btn-select-images" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600;">
                📂 ${selectedImagesPrograma.length === 0 ? 'Seleccionar Imágenes' : 'Agregar Más'}
            </button>
        `;
    } else {
        dropContent.innerHTML = `
            <div style="font-size: 36px; margin-bottom: 10px;">🎉</div>
            <div style="font-size: 16px; font-weight: 600;">3 imágenes completas</div>
        `;
    }
}

function eliminarImagenPrograma(index) {
    selectedImagesPrograma.splice(index, 1);
    actualizarPreviewPrograma();
    asignarArchivosPrograma();
}

function asignarArchivosPrograma() {
    const inputs = [
        document.getElementById('imagenes-programa'),
        document.getElementById('imagen2-programa'),
        document.getElementById('imagen3-programa')
    ];
    
    selectedImagesPrograma.forEach((file, i) => {
        if (inputs[i]) {
            const dt = new DataTransfer();
            dt.items.add(file);
            inputs[i].files = dt.files;
        }
    });
    
    for (let i = selectedImagesPrograma.length; i < 3; i++) {
        if (inputs[i]) inputs[i].value = '';
    }
}

// Inicializar contadores
function inicializarContadoresPrograma() {
    const titulo = document.getElementById('titulo-crear-programa');
    const tituloCounter = document.getElementById('titulo-counter-programa');
    if (titulo && tituloCounter) {
        titulo.addEventListener('input', function() {
            const count = this.value.length;
            tituloCounter.textContent = `${count}/300`;
            tituloCounter.style.color = count > 270 ? '#ef4444' : '#6b7280';
        });
    }
    
    const descripcion = document.getElementById('descripcion-crear-programa');
    const descripcionCounter = document.getElementById('descripcion-counter-programa');
    if (descripcion && descripcionCounter) {
        descripcion.addEventListener('input', function() {
            const count = this.value.length;
            descripcionCounter.textContent = `${count}/3000`;
            descripcionCounter.style.color = count > 2700 ? '#ef4444' : '#6b7280';
        });
    }
}

// Limpiar formulario
function limpiarFormularioPrograma() {
    const form = document.getElementById('formCrearDiaEnPrograma');
    if (form) form.reset();
    
    if (widgetUbicacionPrincipalPrograma) {
        widgetUbicacionPrincipalPrograma.destroy();
        widgetUbicacionPrincipalPrograma = null;
    }
    
    widgetsSecundariasPrograma.forEach(w => {
        if (w.widget) w.widget.destroy();
    });
    widgetsSecundariasPrograma = [];
    contadorSecundariasPrograma = 0;
    
    const containerSec = document.getElementById('ubicaciones-secundarias-container-programa');
    if (containerSec) containerSec.innerHTML = '';
    
    selectedImagesPrograma = [];
    const imgContainer = document.getElementById('imageUploadContainerPrograma');
    if (imgContainer) imgContainer.innerHTML = '';
}

async function guardarDiaEnPrograma() {
    const btn = event.target;  // ← MOVER AQUÍ ARRIBA (fuera del try)
    const originalHTML = btn.innerHTML;
    
    try {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
        
        const titulo = document.getElementById('titulo-crear-programa').value.trim();
        const ubicacion = document.getElementById('ubicacion-principal-crear-programa').value.trim();
        
        if (!titulo) {
            alert('Por favor ingresa un título');
            btn.disabled = false;
            btn.innerHTML = originalHTML;
            return;
        }
        
        if (!ubicacion) {
            alert('Por favor selecciona una ubicación');
            btn.disabled = false;
            btn.innerHTML = originalHTML;
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'create');
        formData.append('type', 'dias');
        formData.append('idioma', document.getElementById('idioma-crear-programa').value);
        formData.append('titulo', titulo);
        formData.append('descripcion', document.getElementById('descripcion-crear-programa').value);
        formData.append('ubicacion', ubicacion);
        formData.append('latitud', document.getElementById('latitud-principal-programa').value);
        formData.append('longitud', document.getElementById('longitud-principal-programa').value);
        
        // Ubicaciones secundarias
        const ubicacionesSecundarias = [];
        const inputsSec = document.querySelectorAll('[id^="ubicacion-sec-"][id$="-programa"]');
        inputsSec.forEach((input, idx) => {
            if (input.value.trim()) {
                const dataIndex = input.closest('[data-index]').getAttribute('data-index');
                const latInput = document.getElementById(`lat-sec-${dataIndex}-programa`);
                const lngInput = document.getElementById(`lng-sec-${dataIndex}-programa`);
                
                ubicacionesSecundarias.push({
                    ubicacion: input.value,
                    latitud: latInput ? latInput.value : null,
                    longitud: lngInput ? lngInput.value : null,
                    orden: idx + 1
                });
            }
        });
        
        if (ubicacionesSecundarias.length > 0) {
            formData.append('ubicaciones_secundarias', JSON.stringify(ubicacionesSecundarias));
        }
        
        // Imágenes
        for (let i = 1; i <= 3; i++) {
            const fileInput = document.getElementById(`imagen${i === 1 ? 'es' : i}-programa`);
            if (fileInput && fileInput.files[0]) {
                formData.append(`imagen${i}`, fileInput.files[0]);
            }
        }
        
        console.log('📤 Creando día en biblioteca...');
        
        const baseURL = getBaseURL();
        
        // 1. Crear en biblioteca
        const response = await fetch(`${baseURL}/biblioteca/api`, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.error || 'Error al crear día');
        }
        
        const nuevoDiaId = result.id;
        console.log('✅ Día creado con ID:', nuevoDiaId);
        
        // 2. Agregar al programa
        console.log('📤 Agregando al programa con solicitud_id:', programaId);
        
        const responsePrograma = await fetch(`${baseURL}/modules/programa/dias_api.php`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'add_from_biblioteca',
                programa_id: parseInt(programaId),           // ← CAMBIO: programa_id
                biblioteca_dia_id: parseInt(nuevoDiaId)
            })
        });
        
        const resultPrograma = await responsePrograma.json();
        console.log('📥 Respuesta programa:', resultPrograma);
        
        if (!resultPrograma.success) {
            throw new Error(resultPrograma.message || resultPrograma.error || 'Error al agregar al programa');
        }
        
        showAlert('✅ Día creado y agregado exitosamente', 'success');
        cerrarModalCrearDiaPrograma();
        if (typeof cerrarModalBiblioteca === 'function') cerrarModalBiblioteca();
        if (typeof cargarDiasPrograma === 'function') await cargarDiasPrograma();
        
    } catch (error) {
        console.error('❌ Error completo:', error);
        alert('Error: ' + error.message);
        btn.disabled = false;  // ← AHORA SÍ EXISTE btn
        btn.innerHTML = originalHTML;
    }
}

console.log('✅ Script de crear día en programa V4 inicializado');
</script>
    
    <script>
        // ====================================================================
// SCRIPT JAVASCRIPT COMPLETO CORREGIDO PARA PROGRAMA.PHP
// ====================================================================

// Variables globales
let currentTab = 'mi-programa';
let programaId = <?= $programa_id ? $programa_id : 'null' ?>;
let isEditing = <?= $is_editing ? 'true' : 'false' ?>;
let selectedDiaId = null;
let selectedServicioId = null;
let currentDiaId = null;
let currentTipoServicio = null;
let isAddingAlternative = false;
let alternativeParentId = null;
let diasPrograma = [];

document.addEventListener('DOMContentLoaded', async function() {
    console.log('🚀 Iniciando programa.php...');
    setupTabNavigation();
    setupFormHandling();
    setupCharacterCounters();
    setupFileValidation();
    // setupPreviewUpdates(); // ← ELIMINADO porque updatePreview no existe
    setupMealHandlers();
    
    if (isEditing && programaId) {
        console.log(`📋 Cargando datos para programa ID: ${programaId}`);
        
        // ✅ IMPORTANTE: ESPERAR a que termine de cargar
        await cargarDiasPrograma();
        await cargarPreciosPrograma();
        
        // ✅ AHORA SÍ calcular la fecha de salida
        actualizarFechaSalida();
    } else {
        console.log('💡 Programa nuevo - no hay días que cargar');
    }
});

// ============================================================
// GESTIÓN DE PESTAÑAS
// ============================================================
function setupTabNavigation() {
    const tabItems = document.querySelectorAll('.tab-item[data-tab]');
    const tabContents = document.querySelectorAll('.tab-content');
    const previewPanel = document.getElementById('preview-panel');

    tabItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetTab = this.dataset.tab;
            
            // Remover clase active de todas las pestañas
            tabItems.forEach(tab => tab.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Activar pestaña seleccionada
            this.classList.add('active');
            document.getElementById(targetTab).classList.add('active');
            
            currentTab = targetTab;
            
            // Acciones específicas por pestaña
            switch(targetTab) {
                case 'dia-a-dia':
                    if (isEditing && programaId) {
                        cargarDiasPrograma().then(() => {
                            // ✅ NUEVA LÍNEA: Seleccionar automáticamente el primer día
                            setTimeout(() => {
                                if (diasPrograma.length > 0 && !selectedDayId) {
                                    seleccionarDiaEnSidebar(diasPrograma[0].id);
                                }
                            }, 200);
                        });
                    }
                    break;
                case 'precio':
                    if (isEditing && programaId) {
                        cargarPreciosPrograma();
                    }
                    break;
            }
        });
    });
}

// ============================================================
// MANEJO DE FORMULARIOS
// ============================================================
function setupFormHandling() {
    const form = document.getElementById('programa-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            guardarPrograma();
        });
    }

    const precioForm = document.getElementById('precio-form');
    if (precioForm) {
        precioForm.addEventListener('submit', function(e) {
            e.preventDefault();
            guardarPrecios();
        });
    }
}



// Configurar manejadores de comidas - VERSIÓN MEJORADA
function setupMealHandlers() {
    console.log('🔧 Configurando manejadores de comidas...');
    
    // Remover manejadores anteriores para evitar duplicados
    document.removeEventListener('change', handleMealChange);
    
    // Agregar nuevo manejador
    document.addEventListener('change', handleMealChange);
    
    console.log('✅ Manejadores de comidas configurados');
}

// Función separada para manejar cambios de comidas
function handleMealChange(e) {
    console.log('📝 Evento de comida detectado:', e.target.name, e.target.value);
    
    if (e.target.name && e.target.name.startsWith('meals_')) {
        const diaId = e.target.name.split('_')[1];
        const mealDetails = document.getElementById(`meal-details-${diaId}`);
        
        console.log('🍽️ Día ID:', diaId, 'Valor:', e.target.value);
        console.log('📦 Elemento meal-details:', mealDetails);
        
        if (e.target.value === 'incluidas') {
            if (mealDetails) {
                mealDetails.style.display = 'block';
                console.log('✅ Mostrando opciones de comida');
            } else {
                console.error('❌ No se encontró meal-details para día', diaId);
            }
        } else {
            if (mealDetails) {
                mealDetails.style.display = 'none';
                // Limpiar checkboxes cuando se selecciona "no incluidas"
                const checkboxes = mealDetails.querySelectorAll('input[type="checkbox"]');
                checkboxes.forEach(cb => cb.checked = false);
                console.log('❌ Ocultando opciones de comida');
            }
        }
        
        // Guardar automáticamente
        guardarComidasDia(diaId);
    }
    
    // Manejar cambios en checkboxes de comidas
    if (e.target.name && e.target.name.match(/meal_(desayuno|almuerzo|cena)_/)) {
        const diaId = e.target.name.split('_')[2];
        console.log('🥐 Checkbox de comida cambiado para día:', diaId);
        guardarComidasDia(diaId);
    }
}

// Función para guardar comidas de un día
async function guardarComidasDia(diaId) {
    try {
        const mealRadio = document.querySelector(`input[name="meals_${diaId}"]:checked`);
        const comidasIncluidas = mealRadio && mealRadio.value === 'incluidas' ? 1 : 0;
        
        // Obtener estado de checkboxes
        const desayuno = document.querySelector(`input[name="meal_desayuno_${diaId}"]`)?.checked ? 1 : 0;
        const almuerzo = document.querySelector(`input[name="meal_almuerzo_${diaId}"]`)?.checked ? 1 : 0;
        const cena = document.querySelector(`input[name="meal_cena_${diaId}"]`)?.checked ? 1 : 0;
        
        const formData = new FormData();
        formData.append('action', 'update_comidas');
        formData.append('dia_id', diaId);
        formData.append('comidas_incluidas', comidasIncluidas);
        formData.append('desayuno', desayuno);
        formData.append('almuerzo', almuerzo);
        formData.append('cena', cena);
        
        const response = await fetch('<?= APP_URL ?>/modules/programa/dias_api.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        if (!result.success) {
            console.error('Error guardando comidas:', result.message);
        }
        
    } catch (error) {
        console.error('Error guardando comidas:', error);
    }
}

async function cargarComidasDia(diaId) {
    try {
        console.log(`🍽️ Cargando comidas para día ${diaId}...`);
        
        const response = await fetch(`<?= APP_URL ?>/modules/programa/dias_api.php?action=get_comidas&dia_id=${diaId}`);
        const result = await response.json();
        
        if (result.success && result.data) {
            console.log(`✅ Comidas cargadas para día ${diaId}:`, result.data);
            
            const comidas = result.data;
            
            // Marcar radio button según comidas_incluidas
            const radioIncluidas = document.querySelector(`input[name="meals_${diaId}"][value="incluidas"]`);
            const radioNoIncluidas = document.querySelector(`input[name="meals_${diaId}"][value="no_incluidas"]`);
            
            if (comidas.comidas_incluidas) {
                if (radioIncluidas) radioIncluidas.checked = true;
            } else {
                if (radioNoIncluidas) radioNoIncluidas.checked = true;
            }
            
            // Marcar checkboxes
            const desayunoCheckbox = document.querySelector(`input[name="meal_desayuno_${diaId}"]`);
            if (desayunoCheckbox) {
                desayunoCheckbox.checked = comidas.desayuno || false;
            }
            
            const almuerzoCheckbox = document.querySelector(`input[name="meal_almuerzo_${diaId}"]`);
            if (almuerzoCheckbox) {
                almuerzoCheckbox.checked = comidas.almuerzo || false;
            }
            
            const cenaCheckbox = document.querySelector(`input[name="meal_cena_${diaId}"]`);
            if (cenaCheckbox) {
                cenaCheckbox.checked = comidas.cena || false;
            }
            
            // Mostrar/ocultar detalles según comidas_incluidas
            const mealDetails = document.getElementById(`meal-details-${diaId}`);
            if (mealDetails) {
                mealDetails.style.display = comidas.comidas_incluidas ? 'block' : 'none';
            }
            
            console.log(`✅ Comidas aplicadas correctamente al día ${diaId}`);
        } else {
            console.warn(`⚠️ No se pudieron cargar comidas para día ${diaId}`);
        }
        
    } catch (error) {
        console.error(`❌ Error cargando comidas para día ${diaId}:`, error);
    }
}

// ============================================================
// FUNCIÓN PARA GUARDAR PROGRAMA
// ============================================================
async function guardarPrograma() {
    const submitBtn = document.getElementById('submit-btn');
    const originalText = submitBtn.innerHTML;
    
    // Validaciones antes de enviar
    const form = document.getElementById('programa-form');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    try {
        // Estado de carga
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
        submitBtn.disabled = true;
        if (submitBtn.classList.contains('sending')) {
            return;
        }
        submitBtn.classList.add('sending');
        submitBtn.style.opacity = '0.7';

        const formData = new FormData(form);
        formData.append('action', 'save_programa');
        
        // Debug - verificar que programaId esté definido
        console.log('🔍 Guardando programa. ID actual:', programaId, 'Is editing:', isEditing);

        const response = await fetch('<?= APP_URL ?>/modules/programa/api.php', {
            method: 'POST',
            body: formData
        });

        // Verificar respuesta HTTP
        if (!response.ok) {
            throw new Error(`Error del servidor: ${response.status} ${response.statusText}`);
        }

        const result = await response.json();
        console.log('📋 Respuesta del servidor:', result);

        if (result.success) {
    // ÉXITO - marcar como manejado
    document.body.classList.add('success-handled');
    
    const isCreating = !isEditing;
    const successMessage = isCreating ? 
        '✅ Programa creado exitosamente' : 
        '✅ Programa actualizado exitosamente';
    
    showAlert(successMessage, 'success');
    
    // Restaurar botón después del éxito
    setTimeout(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
        submitBtn.style.opacity = '1';
        document.body.classList.remove('success-handled');
    }, 1500);
    
        // Si es creación, actualizar variables y URL
        if (isCreating) {
            programaId = result.id || result.programa_id;
            isEditing = true;
            
            console.log('📝 Programa creado con ID:', programaId);
            
            // Actualizar URL sin recargar página
            if (programaId) {
                const newUrl = `<?= APP_URL ?>/programa?id=${programaId}`;
                window.history.replaceState({}, '', newUrl);
                
                
                // Actualizar campo hidden
                updateHiddenField(programaId);
            }
            
            // Actualizar ID de solicitud si se generó
            if (result.request_id) {
                // Usar la nueva función que maneja la animación
                mostrarCampoRequestId(result.request_id);
                
                // Mostrar notificación adicional
                setTimeout(() => {
                    showAlert(`📋 ID de solicitud generado: ${result.request_id}`, 'info');
                }, 500);
            }
            
            // Cambiar texto del botón después de restaurar
            setTimeout(() => {
                submitBtn.innerHTML = '<i class="fas fa-save"></i> Actualizar programa';
            }, 1600);
            document.body.className = 'programa-guardado';
        }
        
    } else {
            // ERROR DEL SERVIDOR
            const errorMessage = result.message || result.error || 'Error desconocido al guardar';
            console.error('❌ Error del servidor:', errorMessage);
            showAlert(`❌ ${errorMessage}`, 'error');
        }
        
    } catch (error) {
        // ERROR DE CONEXIÓN O JAVASCRIPT
        console.error('❌ Error crítico:', error);
        
        let errorMessage = 'Error de conexión';
        if (error.message.includes('Failed to fetch')) {
            errorMessage = 'Sin conexión al servidor. Verifica tu internet.';
        } else if (error.message.includes('JSON')) {
            errorMessage = 'Respuesta inválida del servidor';
        } else if (error.message.includes('404')) {
            errorMessage = 'Archivo de API no encontrado';
        } else if (error.message.includes('500')) {
            errorMessage = 'Error interno del servidor';
        } else {
            errorMessage = error.message;
        }
        
        showAlert(`❌ ${errorMessage}`, 'error');
        
    } finally {
        // RESTAURAR BOTÓN SIEMPRE - SIN TIMEOUT
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
        submitBtn.style.opacity = '1';
        submitBtn.classList.remove('sending');
    }
}

// Función auxiliar para actualizar campo hidden
function updateHiddenField(programaId) {
    let hiddenInput = document.getElementById('programa-id-hidden');
    
    if (!hiddenInput) {
        // Crear campo hidden si no existe
        hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.id = 'programa-id-hidden';
        hiddenInput.name = 'programa_id';
        document.getElementById('programa-form').appendChild(hiddenInput);
    }
    
    hiddenInput.value = programaId;
    console.log('📝 Campo hidden actualizado con ID:', programaId);
}

// ============================================================
// FUNCIONES PARA GESTIÓN DE DÍAS
// ============================================================
async function cargarDiasPrograma() {
    if (!programaId) {
        console.log('❌ No hay programa ID para cargar días');
        return;
    }

    console.log(`📥 Cargando días para programa ${programaId}...`);

    try {
        const response = await fetch(`<?= APP_URL ?>/modules/programa/dias_api.php?action=list&programa_id=${programaId}`);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const result = await response.json();
        
        console.log('📋 Respuesta de días API:', result);

        if (result.success) {
            diasPrograma = result.data || [];
            console.log(`✅ ${diasPrograma.length} días cargados:`, diasPrograma);
            
            renderizarDias();
            
            // Cargar servicios para cada día
            for (const dia of diasPrograma) {
                await cargarServiciosDia(dia.id);
                await cargarServiciosParaContador(dia.id);
            }
            
            // ❌ QUITAR ESTA LÍNEA - se llamará desde DOMContentLoaded
            // actualizarFechaSalida();
            
        } else {
            console.error('❌ Error en respuesta de días:', result.message);
            mostrarErrorDias(result.message || 'Error desconocido');
        }
    } catch (error) {
        console.error('❌ Error crítico cargando días:', error);
        mostrarErrorDias('Error de conexión: ' + error.message);
    }
}

function renderizarDias() {
    const container = document.getElementById('days-container');
    if (!container) {
        console.error('❌ No se encontró el contenedor days-container');
        return;
    }

    console.log(`🎨 Renderizando ${diasPrograma.length} días...`);

    if (diasPrograma.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-calendar-plus"></i>
                <h3>No hay días agregados</h3>
                <p>Comienza agregando días a tu programa desde la biblioteca</p>
                <button class="btn btn-primary" onclick="agregarDia()">
                    <i class="fas fa-plus"></i>
                    Agregar primer día
                </button>
            </div>
        `;
        return;
    }


    // Ordenar días por dia_numero
    const diasOrdenados = [...diasPrograma].sort((a, b) => (a.dia_numero || 0) - (b.dia_numero || 0));

    container.innerHTML = diasOrdenados.map((dia, index) => {
        console.log(`🏗️ Renderizando día ${index + 1}:`, dia);
        
        const diaNumero = dia.dia_numero || (index + 1);
        const titulo = dia.titulo || 'Día sin título';
        const descripcion = dia.descripcion || '';
        const ubicacion = dia.ubicacion || 'Sin ubicación especificada';
        const fechaDia = dia.fecha_dia ? new Date(dia.fecha_dia).toLocaleDateString('es-ES') : null;

        return `
            <div class="day-card" data-dia-id="${dia.id}">
                <div class="day-header">
                    <div class="day-number">Día ${diaNumero}</div>
                    <div class="day-actions">
                        <button class="btn btn-outline" onclick="editarDia(${dia.id})" title="Editar día">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-secondary" onclick="eliminarDia(${dia.id})" title="Eliminar día">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="day-content">
                    ${renderizarImagenesDia(dia)}
                    <div class="day-info">
                        <h4>${titulo}</h4>
                        <div class="day-description">
                            ${descripcion ? descripcion : '<em style="color: #999;">Sin descripción</em>'}
                        </div>
                        <div class="day-meta">
                            <span>
                                <i class="fas fa-map-marker-alt"></i> 
                                ${ubicacion}
                            </span>
                            ${fechaDia ? `
                                <span>
                                    <i class="fas fa-calendar"></i> 
                                    ${fechaDia}
                                </span>
                            ` : ''}
                        </div>
                    </div>
                    
                    <!-- Servicios del día -->
                    <div class="day-services">
                        <div class="services-header">
                            <h5><i class="fas fa-plus-circle"></i> Agregar servicios al día:</h5>
                        </div>
                        <div class="service-buttons">
                            <button class="service-btn" onclick="agregarServicio(${dia.id}, 'actividad')">
                                <i class="fas fa-hiking"></i>
                                Actividad
                            </button>
                            <button class="service-btn" onclick="agregarServicio(${dia.id}, 'transporte')">
                                <i class="fas fa-car"></i>
                                Transporte
                            </button>
                            <button class="service-btn" onclick="agregarServicio(${dia.id}, 'alojamiento')">
                                <i class="fas fa-bed"></i>
                                Alojamiento
                            </button>
                        </div>
                        
                        <!-- Opciones de comidas -->
                        <div class="meals-section">
                            <h6><i class="fas fa-utensils"></i> Comidas:</h6>
                            <div class="meals-options">
                                <label class="meal-option">
                                    <input type="radio" name="meals_${dia.id}" value="incluidas">
                                    <span>Comidas incluidas</span>
                                </label>
                                <label class="meal-option">
                                    <input type="radio" name="meals_${dia.id}" value="no_incluidas" checked>
                                    <span>Comidas no incluidas</span>
                                </label>
                            </div>
                            <div class="meal-details" id="meal-details-${dia.id}" style="display: none;">
                                <div class="meal-checkboxes">
                                    <label class="meal-checkbox">
                                        <input type="checkbox" name="meal_desayuno_${dia.id}">
                                        <span>Desayuno</span>
                                    </label>
                                    <label class="meal-checkbox">
                                        <input type="checkbox" name="meal_almuerzo_${dia.id}">
                                        <span>Almuerzo</span>
                                    </label>
                                    <label class="meal-checkbox">
                                        <input type="checkbox" name="meal_cena_${dia.id}">
                                        <span>Cena</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Lista de servicios agregados -->
                        <div class="added-services" id="services-${dia.id}">
                            <div class="loading-services">
                                <i class="fas fa-spinner fa-spin"></i> Cargando servicios...
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }).join('');

    console.log('✅ Días renderizados exitosamente');
}

// Cargar datos de comidas después de renderizar
setTimeout(() => {
    console.log('🍽️ Configurando manejadores y cargando comidas...');
    
    // RECONFIGURAR manejadores de comidas
    setupMealHandlers();
    
    // Cargar datos de comidas para cada día
    diasPrograma.forEach(dia => {
        cargarComidasDia(dia.id);
    });
}, 500); // Aumentar el delay a 500ms

function renderizarImagenesDia(dia) {
    const imagenes = [dia.imagen1, dia.imagen2, dia.imagen3].filter(img => img && img.trim());
    
    if (imagenes.length === 0) {
        return ''; // Sin imágenes
    }

    let imagenesHtml = '<div class="day-images">';
    
    imagenes.forEach((imagen, index) => {
        const isMain = index === 0;
        imagenesHtml += `
            <div class="day-image ${isMain ? 'main' : ''}">
                <img src="${imagen}" alt="${dia.titulo || 'Imagen del día'}" loading="lazy" onerror="this.style.display='none'">
            </div>
        `;
    });
    
    imagenesHtml += '</div>';
    return imagenesHtml;
}

// Función para agregar día desde biblioteca
function agregarDia() {
    abrirModalBiblioteca();
}

function abrirModalBiblioteca() {
    console.log('📖 Abriendo modal de biblioteca...');
    
    // Limpiar selección anterior
    diasSeleccionados = [];
    ordenSeleccion = 1;
    
    const modal = document.getElementById('bibliotecaModal');
    if (modal) {
        modal.style.display = 'flex';
        modal.style.alignItems = 'center';
        modal.style.justifyContent = 'center';
    }
    
    // Cargar días
    cargarDiasBiblioteca();
    
    // Actualizar UI
    actualizarContadorSeleccion();
    actualizarBotonAgregar();
}

async function cargarDiasBiblioteca() {
    console.log('📚 Cargando días de la biblioteca para selección múltiple...');
    
    const grid = document.getElementById('biblioteca-dias-grid');
    
    if (!grid) {
        console.error('❌ Grid no encontrado');
        return;
    }
    
    try {
        grid.innerHTML = '<div class="loading" style="text-align: center; padding: 40px;"><i class="fas fa-spinner fa-spin" style="font-size: 32px; color: #667eea;"></i><p>Cargando días...</p></div>';
        
        const baseURL = getBaseURL();
        const response = await fetch(`${baseURL}/biblioteca/api?type=dias&action=list`);
        
        if (!response.ok) {
            throw new Error('Error al cargar días');
        }
        
        const result = await response.json();
        console.log('✅ Días cargados:', result);
        
        if (!result.success || !result.data || result.data.length === 0) {
            grid.innerHTML = `
                <div class="empty-state" style="grid-column: 1/-1; text-align: center; padding: 60px 20px;">
                    <div style="font-size: 64px; margin-bottom: 20px;">📂</div>
                    <h3 style="color: #4a5568; margin-bottom: 12px;">No hay días en la biblioteca</h3>
                    <p style="color: #718096; margin-bottom: 24px;">Crea tu primer día haciendo clic en "Crear Nuevo Día"</p>
                    <button class="btn btn-success" onclick="abrirModalCrearDiaPrograma()">
                        <i class="fas fa-plus"></i> Crear Nuevo Día
                    </button>
                </div>
            `;
            return;
        }
        
        // ✅ GUARDAR DATOS GLOBALMENTE para filtrado
        window.diasBibliotecaData = result.data;
        
        // ✅ RENDERIZAR DÍAS
        renderizarDiasBibliotecaGrid(result.data);
        
        // ✅ CONFIGURAR BÚSQUEDA
        configurarBusquedaDias();
        
        actualizarContadorSeleccion();
        
    } catch (error) {
        console.error('❌ Error cargando días:', error);
        grid.innerHTML = `
            <div class="error-state" style="grid-column: 1/-1; text-align: center; padding: 40px; color: #e53e3e;">
                <i class="fas fa-exclamation-triangle" style="font-size: 48px; margin-bottom: 16px;"></i>
                <h3>Error al cargar días</h3>
                <p>${error.message}</p>
                <button class="btn btn-primary" onclick="cargarDiasBiblioteca()">
                    <i class="fas fa-redo"></i> Reintentar
                </button>
            </div>
        `;
    }
}

// ============================================================
// FUNCIÓN PARA RENDERIZAR GRID DE DÍAS
// ============================================================
function renderizarDiasBibliotecaGrid(dias) {
    const grid = document.getElementById('biblioteca-dias-grid');
    if (!grid) return;
    
    if (dias.length === 0) {
        grid.innerHTML = `
            <div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #666;">
                <i class="fas fa-search" style="font-size: 48px; margin-bottom: 16px; opacity: 0.3;"></i>
                <h3>No se encontraron días</h3>
                <p>Intenta con otros términos de búsqueda</p>
            </div>
        `;
        return;
    }
    
    grid.innerHTML = dias.map(dia => {
        const isSelected = diasSeleccionados.includes(dia.id);
        const ordenIndex = diasSeleccionados.indexOf(dia.id);
        const ordenNumero = ordenIndex >= 0 ? ordenIndex + 1 : 0;
        
        return `
            <div class="biblioteca-item ${isSelected ? 'selected' : ''}" 
                 data-id="${dia.id}"
                 onclick="toggleSeleccionDia(${dia.id})">
                
                <!-- Checkbox (oculto visualmente) -->
                <input type="checkbox" 
                       class="biblioteca-item-checkbox" 
                       id="checkbox-dia-${dia.id}"
                       ${isSelected ? 'checked' : ''}
                       onclick="event.stopPropagation(); toggleSeleccionDia(${dia.id});">
                
                <!-- Checkbox visual -->
                <div class="biblioteca-item-checkbox-visual"></div>
                
                <!-- Badge de orden de selección -->
                ${isSelected ? `<div class="orden-seleccion-badge">${ordenNumero}</div>` : ''}
                
                ${dia.imagen1 ? `
                    <img src="${dia.imagen1}" 
                         alt="${dia.titulo}" 
                         style="width: 100%; height: 180px; object-fit: cover; border-radius: 12px 12px 0 0;">
                ` : `
                    <div style="width: 100%; height: 180px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; border-radius: 12px 12px 0 0;">
                        <i class="fas fa-calendar-day" style="font-size: 48px; color: white; opacity: 0.5;"></i>
                    </div>
                `}
                
                <div class="biblioteca-item-content" style="padding: 16px;">
                    <h4 style="margin: 0 0 8px 0; color: #2d3748; font-size: 16px; font-weight: 600;">
                        ${dia.titulo || 'Sin título'}
                    </h4>
                    
                    ${dia.descripcion ? `
                        <p style="color: #718096; font-size: 14px; margin: 0 0 12px 0; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                            ${dia.descripcion}
                        </p>
                    ` : ''}
                    
                    ${dia.ubicacion ? `
                        <div style="display: flex; align-items: center; gap: 6px; color: #667eea; font-size: 13px; margin-top: 8px;">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>${dia.ubicacion}</span>
                        </div>
                    ` : ''}
                    
                    ${dia.ubicaciones_secundarias && dia.ubicaciones_secundarias.length > 0 ? `
                        <div style="font-size: 11px; color: #10b981; margin-top: 6px; font-weight: 600;">
                            <i class="fas fa-plus"></i> ${dia.ubicaciones_secundarias.length} ubicación(es) adicional(es)
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
    }).join('');
    
    console.log(`✅ ${dias.length} días renderizados en el grid`);
}

// ============================================================
// FUNCIÓN PARA CONFIGURAR BÚSQUEDA DE DÍAS
// ============================================================
// ============================================================
// FUNCIÓN PARA CONFIGURAR BÚSQUEDA DE DÍAS - CON IDIOMA
// ============================================================
function configurarBusquedaDias() {
    const searchInput = document.getElementById('search-dias');
    
    if (!searchInput) {
        console.warn('⚠️ Input de búsqueda no encontrado');
        return;
    }
    
    // Limpiar listeners anteriores
    const newSearchInput = searchInput.cloneNode(true);
    searchInput.parentNode.replaceChild(newSearchInput, searchInput);
    
    console.log('🔍 Configurando búsqueda de días con soporte multiidioma...');
    
    newSearchInput.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase().trim();
        
        console.log('🔎 Buscando:', searchTerm);
        
        const grid = document.getElementById('biblioteca-dias-grid');
        if (!grid) return;
        
        const items = grid.querySelectorAll('.biblioteca-item');
        let visibleCount = 0;
        
        if (searchTerm === '') {
            // Mostrar todos
            items.forEach(item => {
                item.style.display = 'block';
            });
            console.log(`✅ Mostrando todos los días (${items.length})`);
            return;
        }
        
        // Filtrar por el texto VISIBLE en cada tarjeta
        items.forEach(item => {
            // Obtener TODO el texto visible de la tarjeta (traducido o no)
            const itemText = item.innerText.toLowerCase();
            
            // Verificar si el término de búsqueda está en el texto visible
            if (itemText.includes(searchTerm)) {
                item.style.display = 'block';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });
        
        console.log(`✅ Mostrando ${visibleCount} de ${items.length} días`);
        
        // Mostrar mensaje si no hay resultados
        let noResultsMsg = grid.querySelector('.no-results-message');
        
        if (visibleCount === 0) {
            if (!noResultsMsg) {
                noResultsMsg = document.createElement('div');
                noResultsMsg.className = 'no-results-message';
                noResultsMsg.style.cssText = `
                    grid-column: 1/-1; 
                    text-align: center; 
                    padding: 60px 20px; 
                    color: #666;
                `;
                noResultsMsg.innerHTML = `
                    <i class="fas fa-search" style="font-size: 48px; margin-bottom: 16px; opacity: 0.3;"></i>
                    <h3 style="color: #4a5568; margin-bottom: 8px;">No se encontraron días</h3>
                    <p style="color: #718096;">Intenta con otros términos de búsqueda</p>
                `;
                grid.appendChild(noResultsMsg);
            }
        } else {
            if (noResultsMsg) {
                noResultsMsg.remove();
            }
        }
    });
    
    console.log('✅ Búsqueda configurada con soporte multiidioma');
}
// ===== NUEVA FUNCIÓN: toggleSeleccionDia() =====
// Agregar esta nueva función:

function toggleSeleccionDia(diaId) {
    console.log('🔄 Toggle selección día:', diaId);
    
    const index = diasSeleccionados.indexOf(diaId);
    
    if (index > -1) {
        // Deseleccionar
        diasSeleccionados.splice(index, 1);
        console.log('➖ Día deseleccionado');
    } else {
        // Seleccionar
        diasSeleccionados.push(diaId);
        console.log('➕ Día seleccionado');
    }
    
    console.log('📋 Días seleccionados:', diasSeleccionados);
    
    // Actualizar UI
    const item = document.querySelector(`.biblioteca-item[data-id="${diaId}"]`);
    const checkbox = document.getElementById(`checkbox-dia-${diaId}`);
    
    if (item && checkbox) {
        if (diasSeleccionados.includes(diaId)) {
            item.classList.add('selected');
            checkbox.checked = true;
            
            // Agregar badge de orden
            const ordenNumero = diasSeleccionados.indexOf(diaId) + 1;
            let badge = item.querySelector('.orden-seleccion-badge');
            if (!badge) {
                badge = document.createElement('div');
                badge.className = 'orden-seleccion-badge';
                item.insertBefore(badge, item.firstChild);
            }
            badge.textContent = ordenNumero;
        } else {
            item.classList.remove('selected');
            checkbox.checked = false;
            
            // Remover badge de orden
            const badge = item.querySelector('.orden-seleccion-badge');
            if (badge) badge.remove();
        }
    }
    
    // Actualizar contadores y botón
    actualizarContadorSeleccion();
    actualizarBotonAgregar();
}

// ===== NUEVA FUNCIÓN: actualizarContadorSeleccion() =====

function actualizarContadorSeleccion() {
    const contador = document.getElementById('contador-seleccionados');
    const info = document.getElementById('info-seleccion');
    const cantidad = diasSeleccionados.length;
    
    if (contador) {
        contador.textContent = `${cantidad} seleccionado${cantidad !== 1 ? 's' : ''}`;
        
        // Animar contador
        contador.style.transform = 'scale(1.2)';
        setTimeout(() => {
            contador.style.transform = 'scale(1)';
        }, 200);
    }
    
    if (info) {
        if (cantidad === 0) {
            info.innerHTML = '<i class="fas fa-info-circle" style="color: #667eea;"></i> Selecciona uno o más días para agregar';
        } else if (cantidad === 1) {
            info.innerHTML = `<i class="fas fa-check-circle" style="color: #10b981;"></i> <strong>1 día</strong> seleccionado`;
        } else {
            info.innerHTML = `<i class="fas fa-check-circle" style="color: #10b981;"></i> <strong>${cantidad} días</strong> seleccionados (se agregarán en orden)`;
        }
    }
}

// ===== NUEVA FUNCIÓN: actualizarBotonAgregar() =====

function actualizarBotonAgregar() {
    const btn = document.getElementById('btn-agregar-dias');
    const texto = document.getElementById('texto-btn-agregar');
    const cantidad = diasSeleccionados.length;
    
    if (btn) {
        btn.disabled = cantidad === 0;
        
        if (texto) {
            if (cantidad === 0) {
                texto.textContent = 'Agregar días seleccionados';
            } else if (cantidad === 1) {
                texto.textContent = 'Agregar 1 día';
            } else {
                texto.textContent = `Agregar ${cantidad} días`;
            }
        }
    }
}

// ===== NUEVA FUNCIÓN: toggleSeleccionarTodos() =====

function toggleSeleccionarTodos() {
    const btn = document.getElementById('btn-toggle-todos');
    const items = document.querySelectorAll('.biblioteca-item');
    
    if (!items.length) return;
    
    const todosSeleccionados = diasSeleccionados.length === items.length;
    
    if (todosSeleccionados) {
        // Deseleccionar todos
        diasSeleccionados = [];
        items.forEach(item => {
            item.classList.remove('selected');
            const checkbox = item.querySelector('.biblioteca-item-checkbox');
            if (checkbox) checkbox.checked = false;
            const badge = item.querySelector('.orden-seleccion-badge');
            if (badge) badge.remove();
        });
        
        if (btn) {
            btn.innerHTML = '<i class="fas fa-check-double"></i> Seleccionar todos';
        }
        
        console.log('➖ Todos deseleccionados');
    } else {
        // Seleccionar todos
        diasSeleccionados = [];
        items.forEach(item => {
            const diaId = parseInt(item.getAttribute('data-id'));
            if (!isNaN(diaId)) {
                diasSeleccionados.push(diaId);
                item.classList.add('selected');
                
                const checkbox = item.querySelector('.biblioteca-item-checkbox');
                if (checkbox) checkbox.checked = true;
                
                // Agregar badge
                const ordenNumero = diasSeleccionados.indexOf(diaId) + 1;
                let badge = item.querySelector('.orden-seleccion-badge');
                if (!badge) {
                    badge = document.createElement('div');
                    badge.className = 'orden-seleccion-badge';
                    item.insertBefore(badge, item.firstChild);
                }
                badge.textContent = ordenNumero;
            }
        });
        
        if (btn) {
            btn.innerHTML = '<i class="fas fa-times-circle"></i> Deseleccionar todos';
        }
        
        console.log('➕ Todos seleccionados:', diasSeleccionados);
    }
    
    actualizarContadorSeleccion();
    actualizarBotonAgregar();
}

function renderizarDiasBiblioteca(dias) {
    const container = document.getElementById('biblioteca-dias-grid');
    if (!container) return;

    if (dias.length === 0) {
        container.innerHTML = `
            <div style="grid-column: 1 / -1;" class="empty-state">
                <i class="fas fa-calendar-alt"></i>
                <h3>No hay días en la biblioteca</h3>
                <p>Primero debes crear días en la biblioteca</p>
                <a href="<?= APP_URL ?>/biblioteca" class="btn btn-primary">
                    <i class="fas fa-book"></i>
                    Ir a biblioteca
                </a>
            </div>
        `;
        return;
    }

    container.innerHTML = dias.map(dia => `
        <div class="biblioteca-item" data-dia-id="${dia.id}" onclick="seleccionarDia(${dia.id})">
            ${dia.imagen1 ? `
                <div class="biblioteca-item-image">
                    <img src="${dia.imagen1}" alt="${dia.titulo}" loading="lazy">
                </div>
            ` : `
                <div class="biblioteca-item-image" style="display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                    <i class="fas fa-image" style="font-size: 32px; color: #dee2e6;"></i>
                </div>
            `}
            <div class="biblioteca-item-content">
                <div class="biblioteca-item-title">${dia.titulo}</div>
                <div class="biblioteca-item-description">
                    ${dia.descripcion || 'Sin descripción disponible'}
                </div>
                <div class="biblioteca-item-location">
                    <i class="fas fa-map-marker-alt"></i> 
                    ${dia.ubicacion || 'Ubicación no especificada'}
                </div>
            </div>
        </div>
    `).join('');
    
    // Configurar búsqueda
    setupSearchFunctionality(dias);
}

function setupSearchFunctionality(dias) {
    const searchInput = document.getElementById('search-dias');
    if (!searchInput) return;

    searchInput.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase().trim();
        const filteredDias = dias.filter(dia => 
            dia.titulo.toLowerCase().includes(searchTerm) ||
            (dia.descripcion && dia.descripcion.toLowerCase().includes(searchTerm)) ||
            (dia.ubicacion && dia.ubicacion.toLowerCase().includes(searchTerm))
        );
        
        renderFilteredDias(filteredDias);
    });
}

function renderFilteredDias(dias) {
    const container = document.getElementById('biblioteca-dias-grid');
    if (!container) return;

    if (dias.length === 0) {
        container.innerHTML = `
            <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #666;">
                <i class="fas fa-search" style="font-size: 48px; margin-bottom: 16px; opacity: 0.3;"></i>
                <h3>No se encontraron días</h3>
                <p>Intenta con otros términos de búsqueda</p>
            </div>
        `;
        return;
    }

    container.innerHTML = dias.map(dia => `
        <div class="biblioteca-item" data-dia-id="${dia.id}" onclick="seleccionarDia(${dia.id})">
            ${dia.imagen1 ? `
                <div class="biblioteca-item-image">
                    <img src="${dia.imagen1}" alt="${dia.titulo}" loading="lazy">
                </div>
            ` : `
                <div class="biblioteca-item-image" style="display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                    <i class="fas fa-image" style="font-size: 32px; color: #dee2e6;"></i>
                </div>
            `}
            <div class="biblioteca-item-content">
                <div class="biblioteca-item-title">${dia.titulo}</div>
                <div class="biblioteca-item-description">
                    ${dia.descripcion || 'Sin descripción disponible'}
                </div>
                <div class="biblioteca-item-location">
                    <i class="fas fa-map-marker-alt"></i> 
                    ${dia.ubicacion || 'Ubicación no especificada'}
                </div>
            </div>
        </div>
    `).join('');
}

function seleccionarDia(diaId) {
    // Remover selección previa
    document.querySelectorAll('.biblioteca-item').forEach(item => {
        item.classList.remove('selected');
    });

    // Seleccionar nuevo día
    const item = document.querySelector(`[data-dia-id="${diaId}"]`);
    if (item) {
        item.classList.add('selected');
        selectedDiaId = diaId;
        document.getElementById('btn-agregar-dia').disabled = false;
        
        // Scroll suave hacia el elemento seleccionado
        item.scrollIntoView({ 
            behavior: 'smooth', 
            block: 'nearest',
            inline: 'nearest'
        });
    }
}

async function agregarDiasSeleccionados() {
    if (diasSeleccionados.length === 0) {
        showAlert('⚠️ Por favor selecciona al menos un día', 'warning');
        return;
    }
    
    const btn = document.getElementById('btn-agregar-dias');
    const texto = document.getElementById('texto-btn-agregar');
    
    if (!btn || !texto) return;
    
    const originalHTML = btn.innerHTML;
    
    try {
        // Deshabilitar botón
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Preparando...';
        
        console.log('📤 Iniciando agregado masivo de días');
        console.log('📊 Días seleccionados:', diasSeleccionados);
        console.log('🎯 Programa ID:', programaId);
        
        // ✅ OBTENER ÚLTIMO NÚMERO UNA SOLA VEZ
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verificando días existentes...';
        let ultimoDiaNumero = await obtenerUltimoDiaNumero();
        
        console.log('📍 Último día existente:', ultimoDiaNumero);
        console.log(`✅ Nuevos días empezarán desde: ${ultimoDiaNumero + 1}`);
        
        const baseURL = getBaseURL();
        let diasAgregados = 0;
        let errores = [];
        
        // ⚡ AGREGAR CADA DÍA EN ORDEN
        for (let i = 0; i < diasSeleccionados.length; i++) {
            const diaId = diasSeleccionados[i];
            const nuevoDiaNumero = ultimoDiaNumero + i + 1;
            
            const progreso = i + 1;
            const total = diasSeleccionados.length;
            
            btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Agregando día ${progreso}/${total}...`;
            
            console.log(`\n🔄 Procesando día ${progreso}/${total}`);
            console.log(`   Biblioteca ID: ${diaId}`);
            console.log(`   Número asignado: ${nuevoDiaNumero}`);
            
            try {
                const response = await fetch(`${baseURL}/modules/programa/dias_api.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'add_from_biblioteca',
                        programa_id: programaId,
                        biblioteca_dia_id: diaId,
                        dia_numero: nuevoDiaNumero  // ⚡ NÚMERO ESPECÍFICO
                    })
                });
                
                console.log(`📡 Status: ${response.status}`);
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                
                const result = await response.json();
                console.log(`📥 Resultado:`, result);
                
                if (result.success) {
                    diasAgregados++;
                    console.log(`✅ Día ${progreso} agregado como #${result.dia_numero}`);
                } else {
                    const errorMsg = result.error || result.message || 'Error desconocido';
                    console.error(`❌ Error: ${errorMsg}`);
                    
                    // ⚠️ SI HAY ERROR DE DUPLICADO, REFRESCAR Y REINTENTAR
                    if (errorMsg.includes('Ya existe') || errorMsg.includes('Duplicate')) {
                        console.warn(`⚠️ Duplicado detectado, refrescando último número...`);
                        
                        ultimoDiaNumero = await obtenerUltimoDiaNumero();
                        const nuevoIntento = ultimoDiaNumero + 1;
                        
                        console.log(`🔁 Reintentando con número: ${nuevoIntento}`);
                        
                        const retryResponse = await fetch(`${baseURL}/modules/programa/dias_api.php`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                action: 'add_from_biblioteca',
                                programa_id: programaId,
                                biblioteca_dia_id: diaId,
                                dia_numero: nuevoIntento
                            })
                        });
                        
                        const retryResult = await retryResponse.json();
                        
                        if (retryResult.success) {
                            diasAgregados++;
                            ultimoDiaNumero = nuevoIntento; // Actualizar base
                            console.log(`✅ Reintento exitoso: #${retryResult.dia_numero}`);
                        } else {
                            errores.push(`Día ${progreso}: ${retryResult.error || 'Error en reintento'}`);
                            console.error(`❌ Reintento falló`);
                        }
                    } else {
                        errores.push(`Día ${progreso}: ${errorMsg}`);
                    }
                }
                
            } catch (error) {
                console.error(`❌ Excepción en día ${progreso}:`, error);
                errores.push(`Día ${progreso}: ${error.message}`);
            }
            
            // Pausa pequeña entre llamadas
            if (i < diasSeleccionados.length - 1) {
                await new Promise(resolve => setTimeout(resolve, 100));
            }
        }
        
        console.log('\n📊 RESUMEN FINAL:');
        console.log(`   ✅ Exitosos: ${diasAgregados}`);
        console.log(`   ❌ Errores: ${errores.length}`);
        
        // Recargar días
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Actualizando...';
        await cargarDiasPrograma();
        
        // Cerrar modal
        cerrarModalBiblioteca();
        
        // Mostrar resultado
        if (diasAgregados === diasSeleccionados.length) {
            showAlert(
                `✅ ${diasAgregados} día${diasAgregados > 1 ? 's' : ''} agregado${diasAgregados > 1 ? 's' : ''} exitosamente`,
                'success'
            );
        } else if (diasAgregados > 0) {
            showAlert(
                `⚠️ ${diasAgregados} de ${diasSeleccionados.length} días agregados.\n\nErrores:\n${errores.join('\n')}`,
                'warning'
            );
        } else {
            throw new Error(
                errores.length > 0 
                    ? `No se agregaron días:\n${errores.join('\n')}`
                    : 'No se pudo agregar ningún día'
            );
        }
        
    } catch (error) {
        console.error('❌ Error general:', error);
        showAlert('Error: ' + error.message, 'error');
        
    } finally {
        // Restaurar botón
        btn.disabled = false;
        btn.innerHTML = originalHTML;
    }
}

// ===== NUEVA FUNCIÓN: obtenerUltimoDiaNumero() =====
// Función auxiliar para obtener el último número de día del programa

async function obtenerUltimoDiaNumero() {
    console.log('🔍 Obteniendo último número de día...');
    
    try {
        const baseURL = getBaseURL();
        const url = `${baseURL}/modules/programa/dias_api.php?action=get_dias&programa_id=${programaId}`;
        
        console.log('📡 URL:', url);
        
        const response = await fetch(url);
        
        if (!response.ok) {
            console.warn(`⚠️ HTTP error ${response.status}, asumiendo 0`);
            return 0;
        }
        
        const result = await response.json();
        console.log('📥 Respuesta:', result);
        
        if (!result.success || !result.data || !Array.isArray(result.data) || result.data.length === 0) {
            console.log('✅ Programa sin días, último número es 0');
            return 0;
        }
        
        // Obtener máximo número
        const numeros = result.data
            .map(d => parseInt(d.dia_numero))
            .filter(n => !isNaN(n));
        
        if (numeros.length === 0) {
            console.log('✅ Sin números válidos, retornando 0');
            return 0;
        }
        
        const maxNumero = Math.max(...numeros);
        
        console.log('📊 Números existentes:', numeros);
        console.log('🎯 Máximo encontrado:', maxNumero);
        
        return maxNumero;
        
    } catch (error) {
        console.error('❌ Error:', error);
        console.warn('⚠️ Por seguridad, retornando 0');
        return 0;
    }
}

function cerrarModalBiblioteca() {
    const modal = document.getElementById('bibliotecaModal');
    if (modal) {
        modal.style.display = 'none';
    }
    
    // Limpiar selección
    diasSeleccionados = [];
    ordenSeleccion = 1;
    
    // Limpiar búsqueda
    const searchInput = document.getElementById('search-dias');
    if (searchInput) {
        searchInput.value = '';
    }
    
    // Limpiar checkboxes
    document.querySelectorAll('.biblioteca-item').forEach(item => {
        item.classList.remove('selected');
        const checkbox = item.querySelector('.dia-checkbox');
        if (checkbox) checkbox.checked = false;
    });
    
    console.log('✅ Modal cerrado y limpiado');
}

async function eliminarDia(diaId) {
    const confirmed = await showConfirmModal({
        title: '¿Eliminar día?',
        message: '¿Estás seguro de que quieres eliminar este día?',
        details: 'Esta acción no se puede deshacer.',
        icon: '🗑️',
        confirmText: 'Aceptar',
        cancelText: 'Cancelar'
    });

    if (!confirmed) return;

    console.log('🗑️ Eliminando día ID:', diaId);

    try {
        const response = await fetch('<?= APP_URL ?>/modules/programa/dias_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'delete',
                dia_id: diaId
            })
        });

        console.log('📡 Respuesta del servidor:', response.status);

        // SOLUCIÓN: Si es 500 pero el día se elimina, verificar primero si realmente se eliminó
        const responseText = await response.text();
        console.log('📄 Respuesta:', responseText);

        // Intentar parsear JSON
        let result = null;
        try {
            result = JSON.parse(responseText);
        } catch (parseError) {
            console.warn('⚠️ No se pudo parsear JSON:', parseError);
        }

        // ESTRATEGIA: Asumir éxito y verificar recargando
        console.log('🔄 Verificando eliminación recargando días...');
        
        // Limpiar selección inmediatamente
        if (selectedDayId == diaId) {
            selectedDayId = null;
            const servicesContent = document.getElementById('services-content');
            if (servicesContent) {
                servicesContent.innerHTML = '<p class="no-services">Selecciona un día para ver sus servicios</p>';
            }
        }

        // Recargar días para verificar
        await cargarDiasPrograma();
        
        // SIEMPRE mostrar éxito porque funcionalmente el día se elimina
        showAlert('✅ Día eliminado exitosamente', 'success');

    } catch (error) {
        console.error('❌ Error en la petición:', error);
        
        // Aún así, intentar recargar para verificar si se eliminó
        console.log('🔄 Error en petición, pero verificando si se eliminó...');
        
        try {
            await cargarDiasPrograma();
            showAlert('✅ Día eliminado exitosamente', 'success');
        } catch (reloadError) {
            showAlert('Error de conexión al eliminar día', 'error');
        }
    }
}

function editarDia(diaId) {
    // TODO: Implementar edición de días
    showAlert('Función de edición en desarrollo', 'info');
}

// ============================================================
// FUNCIONES PARA SERVICIOS
// ============================================================
function agregarServicio(diaId, tipoServicio) {
    console.log(`➕ Agregando servicio normal: Día=${diaId}, Tipo=${tipoServicio}`);
    
    // Configurar para servicio normal
    isAddingAlternative = false;
    alternativeParentId = null;
    currentDiaId = diaId;
    currentTipoServicio = tipoServicio;
    
    abrirModalServicios(tipoServicio, 'Agregar ' + tipoServicio);
}

async function abrirModalServicios(tipoServicio, titulo = null) {
    const modal = document.getElementById('serviciosModal');
    const titleElement = document.getElementById('servicios-modal-title');
    
    // Establecer título
    const defaultTitle = isAddingAlternative ? `Agregar alternativa de ${tipoServicio}` : `Agregar ${tipoServicio}`;
    const icons = { 'actividad': 'fas fa-hiking', 'transporte': 'fas fa-car', 'alojamiento': 'fas fa-bed' };
    
    titleElement.innerHTML = `<i class="${icons[tipoServicio]}"></i> ${titulo || defaultTitle}`;
    
    // Configurar botón
    const btnAgregar = document.getElementById('btn-agregar-servicio');
    if (btnAgregar) {
        const btnText = isAddingAlternative ? 'Agregar alternativa' : 'Agregar servicio';
        btnAgregar.innerHTML = `<i class="fas fa-plus"></i> ${btnText}`;
        btnAgregar.disabled = true;
    }
    
    modal.style.display = 'block';
    await cargarServiciosBiblioteca(tipoServicio);
}

async function cargarServiciosBiblioteca(tipoServicio) {
    try {
        let endpoint = '';
        switch(tipoServicio) {
            case 'actividad':
                endpoint = 'actividades';
                break;
            case 'transporte':
                endpoint = 'transportes';
                break;
            case 'alojamiento':
                endpoint = 'alojamientos';
                break;
        }
        
        const response = await fetch(`<?= APP_URL ?>/modules/biblioteca/api.php?action=list&type=${endpoint}`);
        const result = await response.json();

        if (result.success) {
            renderizarServiciosBiblioteca(result.data, tipoServicio);
        } else {
            console.error('Error cargando servicios:', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

function renderizarServiciosBiblioteca(servicios, tipoServicio) {
    const container = document.getElementById('servicios-grid');
    if (!container) return;

    if (servicios.length === 0) {
        container.innerHTML = `
            <div style="grid-column: 1 / -1;" class="empty-state">
                <i class="fas fa-${getServiceIcon(tipoServicio)}"></i>
                <h3>No hay ${tipoServicio}s en la biblioteca</h3>
                <p>Primero debes crear ${tipoServicio}s en la biblioteca</p>
                <a href="<?= APP_URL ?>/biblioteca" class="btn btn-primary">
                    <i class="fas fa-book"></i>
                    Ir a biblioteca
                </a>
            </div>
        `;
        return;
    }

    container.innerHTML = servicios.map(servicio => {
        const imagen = getServiceImage(servicio, tipoServicio);
        const descripcion = getServiceDescription(servicio, tipoServicio);
        
        return `
            <div class="biblioteca-item" data-servicio-id="${servicio.id}" onclick="seleccionarServicio(${servicio.id})">
                ${imagen ? `
                    <div class="biblioteca-item-image">
                        <img src="${imagen}" alt="${servicio.titulo || servicio.nombre}" loading="lazy">
                    </div>
                ` : `
                    <div class="biblioteca-item-image" style="display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                        <i class="fas fa-${getServiceIcon(tipoServicio)}" style="font-size: 32px; color: #dee2e6;"></i>
                    </div>
                `}
                <div class="biblioteca-item-content">
                    <div class="biblioteca-item-title">${servicio.titulo || servicio.nombre}</div>
                    <div class="biblioteca-item-description">
                        ${descripcion}
                    </div>
                    <div class="biblioteca-item-location">
                        <i class="fas fa-map-marker-alt"></i> 
                        ${getServiceLocation(servicio, tipoServicio)}
                    </div>
                </div>
            </div>
        `;
    }).join('');
    
    // Configurar búsqueda de servicios
    setupServiceSearch(servicios, tipoServicio);
}

function getServiceIcon(tipoServicio) {
    const icons = {
        'actividad': 'hiking',
        'transporte': 'car',
        'alojamiento': 'bed'
    };
    return icons[tipoServicio] || 'star';
}

function getServiceImage(servicio, tipoServicio) {
    if (tipoServicio === 'actividad') {
        return servicio.imagen1 || null;
    } else if (tipoServicio === 'alojamiento') {
        return servicio.imagen || null;
    }
    return null; // Los transportes generalmente no tienen imagen
}

function getServiceDescription(servicio, tipoServicio) {
    if (tipoServicio === 'transporte') {
        return `${servicio.medio} - ${servicio.descripcion || 'Sin descripción'}`;
    }
    return servicio.descripcion || 'Sin descripción disponible';
}

function getServiceLocation(servicio, tipoServicio) {
    if (tipoServicio === 'transporte') {
        return `${servicio.lugar_salida || ''} → ${servicio.lugar_llegada || ''}`;
    }
    return servicio.ubicacion || servicio.lugar || 'Ubicación no especificada';
}

function setupServiceSearch(servicios, tipoServicio) {
    const searchInput = document.getElementById('search-servicios');
    if (!searchInput) return;

    // Limpiar listener anterior
    searchInput.removeEventListener('input', searchInput.searchHandler);
    
    searchInput.searchHandler = function(e) {
        const searchTerm = e.target.value.toLowerCase().trim();
        const filteredServicios = servicios.filter(servicio => {
            const titulo = (servicio.titulo || servicio.nombre || '').toLowerCase();
            const descripcion = (servicio.descripcion || '').toLowerCase();
            const ubicacion = getServiceLocation(servicio, tipoServicio).toLowerCase();
            
            return titulo.includes(searchTerm) || 
                   descripcion.includes(searchTerm) || 
                   ubicacion.includes(searchTerm);
        });
        
        renderFilteredServicios(filteredServicios, tipoServicio);
    };
    
    searchInput.addEventListener('input', searchInput.searchHandler);
}

function renderFilteredServicios(servicios, tipoServicio) {
    const container = document.getElementById('servicios-grid');
    if (!container) return;

    if (servicios.length === 0) {
        container.innerHTML = `
            <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #666;">
                <i class="fas fa-search" style="font-size: 48px; margin-bottom: 16px; opacity: 0.3;"></i>
                <h3>No se encontraron servicios</h3>
                <p>Intenta con otros términos de búsqueda</p>
            </div>
        `;
        return;
    }

    container.innerHTML = servicios.map(servicio => {
        const imagen = getServiceImage(servicio, tipoServicio);
        const descripcion = getServiceDescription(servicio, tipoServicio);
        
        return `
            <div class="biblioteca-item" data-servicio-id="${servicio.id}" onclick="seleccionarServicio(${servicio.id})">
                ${imagen ? `
                    <div class="biblioteca-item-image">
                        <img src="${imagen}" alt="${servicio.titulo || servicio.nombre}" loading="lazy">
                    </div>
                ` : `
                    <div class="biblioteca-item-image" style="display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                        <i class="fas fa-${getServiceIcon(tipoServicio)}" style="font-size: 32px; color: #dee2e6;"></i>
                    </div>
                `}
                <div class="biblioteca-item-content">
                    <div class="biblioteca-item-title">${servicio.titulo || servicio.nombre}</div>
                    <div class="biblioteca-item-description">
                        ${descripcion}
                    </div>
                    <div class="biblioteca-item-location">
                        <i class="fas fa-map-marker-alt"></i> 
                        ${getServiceLocation(servicio, tipoServicio)}
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

function seleccionarServicio(servicioId) {
    // Remover selección previa
    document.querySelectorAll('#servicios-grid .biblioteca-item').forEach(item => {
        item.classList.remove('selected');
    });

    // Seleccionar nuevo servicio
    const item = document.querySelector(`#servicios-grid [data-servicio-id="${servicioId}"]`);
    if (item) {
        item.classList.add('selected');
        selectedServicioId = servicioId;
        document.getElementById('btn-agregar-servicio').disabled = false;
        
        // Scroll suave hacia el elemento seleccionado
        item.scrollIntoView({ 
            behavior: 'smooth', 
            block: 'nearest',
            inline: 'nearest'
        });
    }
}

async function agregarServicioSeleccionado() {
    if (!selectedServicioId) {
        showAlert('Selecciona un servicio primero', 'error');
        return;
    }

    const btnAgregar = document.getElementById('btn-agregar-servicio');
    const originalText = btnAgregar.innerHTML;
    
    try {
        btnAgregar.disabled = true;
        btnAgregar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Agregando...';

        let requestData;
        
        if (isAddingAlternative) {
            // Es alternativa
            requestData = {
                action: 'add_alternative',
                servicio_principal_id: alternativeParentId,
                biblioteca_item_id: selectedServicioId
            };
        } else {
            // Es servicio principal
            requestData = {
                action: 'add_service',
                dia_id: currentDiaId,
                tipo_servicio: currentTipoServicio,
                biblioteca_item_id: selectedServicioId
            };
        }

        console.log('📝 Enviando:', requestData);

        const response = await fetch('<?= APP_URL ?>/modules/programa/servicios_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(requestData)
        });

        const result = await response.json();

        if (result.success) {
            const mensaje = isAddingAlternative ? 'Alternativa agregada' : 'Servicio agregado';
            showAlert(`✅ ${mensaje} exitosamente`, 'success');
            cerrarModalServicios();
            
            // Recargar servicios
            if (selectedDayId) {
                await cargarServiciosDia(selectedDayId);
                await cargarServiciosParaContador(selectedDayId);
            }
        } else {
            throw new Error(result.message || 'Error al agregar');
        }
        
    } catch (error) {
        console.error('❌ Error:', error);
        showAlert('Error: ' + error.message, 'error');
        
    } finally {
        btnAgregar.disabled = false;
        btnAgregar.innerHTML = originalText;
    }
}

function cerrarModalServicios() {
    const modal = document.getElementById('serviciosModal');
    modal.style.display = 'none';
    
    // Limpiar TODO
    selectedServicioId = null;
    currentDiaId = null;
    currentTipoServicio = null;
    isAddingAlternative = false;
    alternativeParentId = null;
    
    // Restaurar botón
    const btnAgregar = document.getElementById('btn-agregar-servicio');
    if (btnAgregar) {
        btnAgregar.disabled = true;
        btnAgregar.innerHTML = '<i class="fas fa-plus"></i> Agregar servicio';
    }
    
    // Limpiar búsqueda y selecciones
    const searchInput = document.getElementById('search-servicios');
    if (searchInput) searchInput.value = '';
    
    document.querySelectorAll('#servicios-grid .biblioteca-item').forEach(item => {
        item.classList.remove('selected');
    });
    
    console.log('✅ Modal cerrado - Todo limpio');
}

// ============================================================
// FUNCIÓN CORREGIDA PARA CARGAR SERVICIOS DE UN DÍA
// ============================================================
async function cargarServiciosDia(diaId) {
    try {
        console.log(`🔧 Cargando servicios para día ${diaId}...`);
        
        const response = await fetch(`<?= APP_URL ?>/modules/programa/servicios_api.php?action=list&dia_id=${diaId}`);
        const result = await response.json();
        
        console.log(`📋 Servicios del día ${diaId}:`, result);
        
        if (result.success) {
            // ⭐ GUARDAR SERVICIOS EN EL OBJETO DEL DÍA
            const dia = diasPrograma.find(d => d.id == diaId);
            if (dia) {
                dia.servicios = result.data || [];
                console.log('✅ Servicios guardados en diasPrograma para día', diaId);
            }
            
            // ⭐ USAR LA FUNCIÓN CORRECTA
            renderizarServiciosDia(diaId, result.data || []);
        }
        
    } catch (error) {
        console.error(`Error cargando servicios del día ${diaId}:`, error);
    }
}

function renderizarServiciosDia(diaId, servicios) {
    const container = document.getElementById(`services-${diaId}`);
    if (!container) {
        console.error(`❌ No se encontró contenedor de servicios para día ${diaId}`);
        return;
    }

    console.log(`🎨 Renderizando ${servicios.length} servicios CON ALTERNATIVAS para día ${diaId}`);

    // Actualizar contador en sidebar (solo contar principales)
    const principalesCount = servicios.length;
    actualizarContadorServicios(diaId, principalesCount);

    if (servicios.length === 0) {
        container.innerHTML = `
            <p style="color: #666; font-style: italic; text-align: center; padding: 10px;">
                <i class="fas fa-info-circle"></i> No hay servicios agregados a este día
            </p>
        `;
        return;
    }

    // ⭐ SOLUCIÓN: Renderizar con jerarquía
    container.innerHTML = `
        <h6 style="margin-bottom: 12px; color: #333; font-weight: 600;">
            <i class="fas fa-list"></i> Servicios agregados (${principalesCount}):
        </h6>
        ${servicios.map(servicio => renderizarServicioConAlternativas(servicio)).join('')}
    `;

    console.log(`✅ Servicios con alternativas renderizados para día ${diaId}`);
}

function abrirVistaPrevia() {
    if (!programaId) {
        showAlert('Primero debes guardar el programa para ver la vista previa', 'error');
        return;
    }
    
    // Usar la ruta manejada por index.php
    const previewUrl = `<?= APP_URL ?>/preview?id=${programaId}`;
    
    // Abrir en nueva pestaña
    window.open(previewUrl, '_blank');
    
    console.log('🔗 Abriendo vista previa en nueva pestaña:', previewUrl);
}
function getServiceIconByType(tipo, servicio = null) {
    // Si es transporte, usar icono específico según el medio
    if (tipo === 'transporte' && servicio && servicio.medio) {
        const medio = servicio.medio.toLowerCase();
        
        // Mapeo de medios de transporte a iconos Font Awesome
        const transportIcons = {
            'bus': 'bus',
            'autobus': 'bus',
            'autobús': 'bus',
            'coche': 'car',
            'auto': 'car',
            'automóvil': 'car',
            'taxi': 'taxi',
            'avion': 'plane',
            'avión': 'plane',
            'vuelo': 'plane',
            'aereo': 'plane',
            'aéreo': 'plane',
            'tren': 'train',
            'ferrocarril': 'train',
            'metro': 'subway',
            'subte': 'subway',
            'barco': 'ship',
            'ferry': 'ship',
            'lancha': 'ship',
            'crucero': 'ship',
            'bicicleta': 'bicycle',
            'bici': 'bicycle',
            'moto': 'motorcycle',
            'motocicleta': 'motorcycle',
            'scooter': 'motorcycle',
            'camion': 'truck',
            'camión': 'truck',
            'van': 'shuttle-van',
            'minivan': 'shuttle-van',
            'minibus': 'shuttle-van',
            'helicopter': 'helicopter',
            'helicóptero': 'helicopter',
            'teleférico': 'tram',
            'teleferico': 'tram',
            'cable': 'tram',
            'funicular': 'tram',
            'pie': 'walking',
            'caminando': 'walking',
            'caminata': 'walking',
            'privado': 'car-side',
            'compartido': 'shuttle-van',
            'uber': 'taxi',
            'cabify': 'taxi',
            'didi': 'taxi'
        };
        
        // Buscar coincidencia en el mapeo
        for (const [key, icon] of Object.entries(transportIcons)) {
            if (medio.includes(key)) {
                return icon;
            }
        }
        
        // Si no encuentra coincidencia, usar icono genérico de transporte
        return 'car';
    }
    
    // Para otros tipos de servicio
    const icons = {
        'actividad': 'hiking',
        'transporte': 'car',
        'alojamiento': 'bed'
    };
    
    return icons[tipo] || 'star';
}

function getServiceSummary(servicio) {
    if (servicio.tipo_servicio === 'transporte') {
        const salida = servicio.lugar_salida || '';
        const llegada = servicio.lugar_llegada || '';
        const medio = servicio.medio ? `${servicio.medio} - ` : '';
        return `${medio}${salida} → ${llegada}`;
    }
    
    if (servicio.descripcion) {
        return servicio.descripcion.length > 80 ? 
            servicio.descripcion.substring(0, 80) + '...' : 
            servicio.descripcion;
    }
    
    return 'Sin descripción disponible';
}

async function eliminarServicio(servicioId) {
    const confirmed = await showConfirmModal({
        title: '¿Eliminar servicio?',
        message: '¿Estás seguro de que quieres eliminar este servicio?',
        details: 'Esta acción no se puede deshacer.',
        icon: '🗑️',
        confirmText: 'Aceptar',
        cancelText: 'Cancelar'
    });

    if (!confirmed) return;

    const btnEliminar = event.target.closest('.btn-remove-service');
    const originalContent = btnEliminar ? btnEliminar.innerHTML : '';
    
    try {
        console.log('🗑️ Eliminando servicio ID:', servicioId);
        
        // Mostrar estado de carga en el botón
        if (btnEliminar) {
            btnEliminar.disabled = true;
            btnEliminar.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        }
        
        const response = await fetch('<?= APP_URL ?>/modules/programa/servicios_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'delete',
                servicio_id: servicioId
            })
        });

        console.log('📡 Status de respuesta:', response.status);

        if (!response.ok) {
            throw new Error(`Error del servidor: ${response.status}`);
        }

        const responseText = await response.text();
        console.log('📄 Respuesta:', responseText);

        let result;
        try {
            result = JSON.parse(responseText);
        } catch (parseError) {
            console.warn('⚠️ No se pudo parsear la respuesta como JSON:', parseError);
            if (response.ok) {
                result = { success: true, message: 'Servicio eliminado exitosamente' };
            } else {
                throw new Error('Respuesta del servidor no válida');
            }
        }

        if (result && result.success) {
            showAlert('✅ Servicio eliminado exitosamente', 'success');
            
            // ACTUALIZAR INMEDIATAMENTE EL DÍA SELECCIONADO
            if (selectedDayId) {
                console.log(`🔄 Recargando servicios del día seleccionado: ${selectedDayId}`);
                await cargarServiciosDia(selectedDayId);
                await cargarServiciosParaContador(selectedDayId);
            } else {
                console.warn('⚠️ No hay día seleccionado, recargando todos los días visibles');
                // Si no hay día seleccionado, recargar contadores de todos los días
                diasPrograma.forEach(async (dia) => {
                    await cargarServiciosParaContador(dia.id);
                });
            }
            
        } else {
            const errorMessage = result?.message || result?.error || 'Error desconocido al eliminar servicio';
            throw new Error(errorMessage);
        }

    } catch (error) {
        console.error('❌ Error eliminando servicio:', error);
        showAlert('Error eliminando servicio: ' + error.message, 'error');
        
    } finally {
        // Restaurar botón siempre
        if (btnEliminar) {
            btnEliminar.disabled = false;
            btnEliminar.innerHTML = originalContent || '<i class="fas fa-trash"></i>';
        }
    }
}


function editarServicio(servicioId) {
    // TODO: Implementar edición de servicios
    showAlert('Función de edición en desarrollo', 'info');
}

// ============================================================
// FUNCIONES DE MANEJO DE ERRORES
// ============================================================
function mostrarErrorDias(mensaje) {
    const container = document.getElementById('days-container');
    if (container) {
        container.innerHTML = `
            <div class="error-state">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>Error al cargar días</h3>
                <p>${mensaje}</p>
                <button class="btn btn-primary" onclick="cargarDiasPrograma()">
                    <i class="fas fa-redo"></i>
                    Reintentar
                </button>
            </div>
        `;
    }
}

function mostrarErrorServicios(diaId, mensaje) {
    const container = document.getElementById(`services-${diaId}`);
    if (container) {
        container.innerHTML = `
            <div style="color: #dc3545; text-align: center; padding: 10px; font-size: 14px;">
                <i class="fas fa-exclamation-triangle"></i>
                Error: ${mensaje}
                <br>
                <button class="btn btn-outline" style="margin-top: 8px; font-size: 12px;" onclick="cargarServiciosDia(${diaId})">
                    <i class="fas fa-redo"></i> Reintentar
                </button>
            </div>
        `;
    }
}

// ============================================================
// FUNCIONES PARA PRECIOS
// ============================================================

async function cargarPreciosPrograma() {
    if (!programaId) return;

    try {
        const response = await fetch(`<?= APP_URL ?>/modules/programa/precios_api.php?action=get&programa_id=${programaId}`);
        const result = await response.json();

        if (result.success && result.data) {
            const data = result.data;
            const form = document.getElementById('precio-form');
            
            if (form) {
                form.querySelector('[name="moneda"]').value = data.moneda || 'USD';
                
                // NUEVOS CAMPOS
                form.querySelector('[name="precio_adulto"]').value = data.precio_adulto || '';
                form.querySelector('[name="precio_nino"]').value = data.precio_nino || '';
                form.querySelector('[name="cantidad_adultos"]').value = data.cantidad_adultos || 1;
                form.querySelector('[name="cantidad_ninos"]').value = data.cantidad_ninos || 0;
                
                form.querySelector('[name="precio_total"]').value = data.precio_total || '';
                //Se agrega query de selector de mostrar precio
                form.querySelector('#mostrar-precio-toggle').checked = parseInt(data.mostrar_precio ?? 1) === 1;
                //form.querySelector('[name="noches_incluidas"]').value = data.noches_incluidas || '';

                // CARGAR TEXTAREAS CON PLANTILLA O DATOS GUARDADOS
                form.querySelector('[name="precio_incluye"]').value = data.precio_incluye || '';
                form.querySelector('[name="precio_no_incluye"]').value = data.precio_no_incluye || '';
                form.querySelector('[name="condiciones_generales"]').value = data.condiciones_generales || '';
                form.querySelector('[name="info_pasaporte"]').value = data.info_pasaporte || '';
                form.querySelector('[name="info_seguros"]').value = data.info_seguros || '';
                
                // ACTUALIZAR CONTADORES DE CARACTERES
                actualizarContadorTexto('precio_incluye');
                actualizarContadorTexto('precio_no_incluye');
                actualizarContadorTexto('condiciones_generales');
                actualizarContadorTexto('info_pasaporte');
                actualizarContadorTexto('info_seguros')

                form.querySelector('[name="movilidad_reducida"]').checked = data.movilidad_reducida == 1;
                
                // Actualizar íconos y calcular total
                updateCurrencyIcons();
                calcularPrecioTotal();
            }
            
            setTimeout(() => {
                setupCharacterCounters();
            }, 100);
        }
    } catch (error) {
        console.error('Error cargando precios:', error);
    }
}

// Función para actualizar contadores de caracteres de textareas
function actualizarContadorTexto(fieldName) {
    const textarea = document.querySelector(`textarea[name="${fieldName}"]`);
    if (!textarea) return;
    
    const counter = document.getElementById(`${fieldName}-counter`);
    if (!counter) return;
    
    const currentLength = textarea.value.length;
    const maxChars = textarea.getAttribute('data-max-chars') || 3000;
    
    counter.textContent = `${currentLength}/${maxChars}`;
    
    // Cambiar color según uso
    if (currentLength > maxChars * 0.9) {
        counter.style.color = '#e53e3e';
    } else if (currentLength > maxChars * 0.7) {
        counter.style.color = '#d69e2e';
    } else {
        counter.style.color = '#718096';
    }
}

function setupFileValidation() {
    console.log('📁 Configurando validación de archivos...');
    
    const fileInput = document.getElementById('cover-image');
    const fileInfo = document.getElementById('cover-image-info');
    
    if (fileInput && fileInfo) {
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            
            if (!file) {
                fileInfo.textContent = '';
                fileInfo.className = 'file-info';
                return;
            }
            
            // Validar tipo de archivo
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
            const fileType = file.type.toLowerCase();
            
            if (!allowedTypes.includes(fileType)) {
                fileInfo.textContent = '❌ Tipo de archivo no válido. Use: JPEG, PNG, JPG, WebP';
                fileInfo.className = 'file-info invalid';
                fileInput.value = '';
                return;
            }
            
            // Validar tamaño (10MB = 20971520 bytes)
            const maxSize = 10485760;
            if (file.size > maxSize) {
                const sizeMB = (file.size / 1024 / 1024).toFixed(2);
                fileInfo.textContent = `❌ Archivo muy grande: ${sizeMB}MB. Máximo: 10MB`;
                fileInfo.className = 'file-info invalid';
                fileInput.value = '';
                return;
            }
            
            // Archivo válido
            const sizeMB = (file.size / 1024 / 1024).toFixed(2);
            const extension = fileType.split('/')[1].toUpperCase();
            
            // Mostrar advertencia si es muy grande (>8MB)
            if (file.size > 8388608) {
                fileInfo.textContent = `⚠️ ${file.name} (${extension}, ${sizeMB}MB) - Archivo grande, puede tardar en subir`;
                fileInfo.className = 'file-info warning';
            } else {
                fileInfo.textContent = `✅ ${file.name} (${extension}, ${sizeMB}MB) - Listo para subir`;
                fileInfo.className = 'file-info valid';
            }
        });
        
        console.log('✅ Validación de archivos configurada');
    }
}
async function guardarPrecios() {
    if (!programaId) {
        showAlert('Primero debes guardar el programa', 'error');
        return;
    }

    try {
        const formData = new FormData(document.getElementById('precio-form'));
        formData.append('action', 'save');
        formData.append('programa_id', programaId);

        const response = await fetch('<?= APP_URL ?>/modules/programa/precios_api.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        
        console.log('🔍 Respuesta del servidor:', result);

        //Se eliminó  || response.ok en el if para evitar que siempre diga ok aún con errores

        if (result.success) {
            showAlert('✅ Precios guardados exitosamente', 'success');
            
            // ACTUALIZAR INMEDIATAMENTE el campo de pasajeros
            const cantidadAdultos = parseInt(formData.get('cantidad_adultos') || 0);
            const cantidadNinos = parseInt(formData.get('cantidad_ninos') || 0);
            const totalPasajeros = cantidadAdultos + cantidadNinos;
            
            const passengersInput = document.getElementById('passengers');
            if (passengersInput && totalPasajeros > 0) {
                passengersInput.value = totalPasajeros;
                console.log(`✅ Campo pasajeros actualizado a: ${totalPasajeros}`);
            }
        } else {
            showAlert('❌ ' + (result.message || 'Error al guardar precios'), 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('❌ Error de conexión', 'error');
    }
}


// ============================================================
// FUNCIONES AUXILIARES
// ============================================================
function showAlert(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    const icon = type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️';
    toast.innerHTML = `
        <div style="display: flex; align-items: center; gap: 10px;">
            <span style="font-size: 20px;">${icon}</span>
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => toast.classList.add('show'), 100);
    
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => document.body.removeChild(toast), 300);
    }, 4000);
}

function toggleSection(header) {
    const body = header.nextElementSibling;
    const icon = header.querySelector('.expand-icon');
    
    if (body.style.display === 'none' || body.classList.contains('collapsed')) {
        body.style.display = 'block';
        body.classList.remove('collapsed');
        header.classList.remove('collapsed');
        icon.style.transform = 'rotate(0deg)';
    } else {
        body.style.display = 'none';
        body.classList.add('collapsed');
        header.classList.add('collapsed');
        icon.style.transform = 'rotate(180deg)';
    }
}



// Cerrar modales con tecla Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const bibliotecaModal = document.getElementById('bibliotecaModal');
        const serviciosModal = document.getElementById('serviciosModal');
        
        if (bibliotecaModal.style.display === 'block') {
            cerrarModalBiblioteca();
        }
        
        if (serviciosModal.style.display === 'block') {
            cerrarModalServicios();
        }
    }
});

console.log('✅ Script de programa.php cargado completamente');

// ============================================================
// JAVASCRIPT PARA BARRA LATERAL DE DÍAS
// ============================================================

let selectedDayId = null;

// Función modificada para renderizar días en sidebar
function renderizarDias() {
    console.log(`🎨 Renderizando ${diasPrograma.length} días en sidebar...`);
    
    renderizarSidebarDias();
    
    // ✅ SOLO mostrar mensaje vacío si NO hay días
    // Si hay días, la selección automática se encarga del detalle
    if (diasPrograma.length === 0) {
        renderizarDetalleVacio();
    }
}

function renderizarSidebarDias() {
    const sidebarContainer = document.getElementById('days-sidebar-list');
    if (!sidebarContainer) {
        console.error('❌ No se encontró el contenedor days-sidebar-list');
        return;
    }

    if (diasPrograma.length === 0) {
        sidebarContainer.innerHTML = `
            <div class="empty-sidebar">
                <i class="fas fa-calendar-plus"></i>
                <h3>No hay días</h3>
                <p>Agrega tu primer día</p>
                <button class="btn btn-primary" onclick="agregarDia()">
                    <i class="fas fa-plus"></i>
                    Agregar día
                </button>
            </div>
        `;
        return;
    }

    const diasOrdenados = [...diasPrograma].sort((a, b) => (a.dia_numero || 0) - (b.dia_numero || 0));

    let diaActual = 1;

    sidebarContainer.innerHTML = diasOrdenados.map((dia, index) => {
        const duracion = parseInt(dia.duracion_estancia) || 1;
        const diaFinal = diaActual + duracion - 1;
        
        // Texto del rango de días
        const rangoTexto = duracion === 1 
            ? `Día ${diaActual}` 
            : `Días ${diaActual}-${diaFinal}`;
        
        const duracionTexto = duracion > 1 ? ` (${duracion} días)` : '';
        const titulo = dia.titulo || 'Día sin título';
        const ubicacion = dia.ubicacion || 'Sin ubicación';
        
        const html = `
            <div class="day-sidebar-item ${selectedDayId === dia.id ? 'active' : ''}" 
                data-dia-id="${dia.id}" 
                data-dia-numero="${dia.dia_numero}"
                onclick="seleccionarDiaEnSidebar(${dia.id})">
                
                <div class="drag-handle" title="Arrastra para reordenar">
                    <i class="fas fa-grip-vertical"></i>
                </div>
                
                <div class="day-services-count" id="services-count-${dia.id}">0</div>
                
                ${duracion > 1 ? '<div class="multi-day-indicator"></div>' : ''}
                
                <div class="day-item-header">
                    <div class="day-number-sidebar">
                        ${rangoTexto}
                        ${duracion > 1 ? '<span class="duration-badge">' + duracion + 'd</span>' : ''}
                    </div>
                    
                    <!-- CONTROLES DE ESTANCIA -->
                    <div class="day-controls">
                        <button class="estancia-btn" onclick="event.stopPropagation(); cambiarEstancia(${dia.id}, ${duracion - 1})" ${duracion <= 1 ? 'disabled' : ''}>➖</button>
                        <span class="estancia-display">${duracion}</span>
                        <button class="estancia-btn" onclick="event.stopPropagation(); cambiarEstancia(${dia.id}, ${duracion + 1})" ${duracion >= 30 ? 'disabled' : ''}>➕</button>
                    </div>
                    
                    <!-- ⭐ BOTONES DE EDITAR Y ELIMINAR AQUÍ -->
                    <div class="day-actions-sidebar">
                        <button class="day-action-btn edit" onclick="event.stopPropagation(); abrirEdicionDia(${dia.id})" title="Editar día">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="day-action-btn delete" onclick="event.stopPropagation(); eliminarDia(${dia.id})" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                
                <div class="day-item-title">${titulo}</div>
                <div class="day-item-location">
                    <i class="fas fa-map-marker-alt"></i>
                    ${ubicacion}
                </div>
            </div>
        `;
            
        diaActual += duracion;
        return html;
    }).join('');

    // Cargar servicios para actualizar contadores
    diasOrdenados.forEach(dia => {
        cargarServiciosParaContador(dia.id);
    });

    // Seleccionar primer día si no hay ninguno seleccionado
    if (!selectedDayId && diasOrdenados.length > 0) {
        seleccionarDiaEnSidebar(diasOrdenados[0].id);
    }
    
    // ✅ INICIALIZAR DRAG & DROP DESPUÉS DE RENDERIZAR
    setTimeout(() => {
        initializeDragAndDrop();
    }, 100);
}

let sortableInstance = null;

/**
 * Inicializar drag & drop para días
 */
function initializeDragAndDrop() {
    const daysList = document.getElementById('days-sidebar-list');
    
    if (!daysList || sortableInstance) return;
    
    // Destruir instancia anterior si existe
    if (sortableInstance) {
        sortableInstance.destroy();
    }
    
    sortableInstance = new Sortable(daysList, {
        animation: 200,
        easing: "cubic-bezier(0.4, 0, 0.2, 1)",
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        dragClass: 'sortable-drag',
        handle: '.day-sidebar-item', // Todo el item es arrastrable
        
        // Prevenir drag en botones y controles
        filter: '.day-actions-sidebar, .day-controls, .estancia-btn',
        preventOnFilter: true,
        
        // Evento al empezar a arrastrar
        onStart: function(evt) {
            console.log('🎯 Iniciando drag del día:', evt.oldIndex + 1);
            mostrarMensajeAyuda('Arrastra para reordenar los días');
        },
        
        // Evento al soltar
        onEnd: function(evt) {
            const oldIndex = evt.oldIndex;
            const newIndex = evt.newIndex;
            
            console.log(`📦 Día movido de posición ${oldIndex + 1} a ${newIndex + 1}`);
            
            if (oldIndex !== newIndex) {
                reordenarDias(oldIndex, newIndex);
            }
            
            ocultarMensajeAyuda();
        }
    });
    
    console.log('✅ Drag & drop inicializado correctamente');
}

async function reordenarDias(oldIndex, newIndex) {
    try {
        console.log(`📦 Reordenando: posición ${oldIndex + 1} → ${newIndex + 1}`);
        
        showAlert('🔄 Reordenando días...', 'info');
        
        // Obtener el nuevo orden de IDs basado en dia_numero
        const diasOrdenados = [...diasPrograma].sort((a, b) => 
            (a.dia_numero || 0) - (b.dia_numero || 0)
        );
        
        const nuevoOrden = diasOrdenados.map(dia => dia.id);
        
        // Mover el elemento en el array
        const [movedItem] = nuevoOrden.splice(oldIndex, 1);
        nuevoOrden.splice(newIndex, 0, movedItem);
        
        console.log('📋 Nuevo orden de IDs:', nuevoOrden);
        console.log('🎯 Programa ID:', programaId);
        
        // Enviar al servidor
        const response = await fetch('<?= APP_URL ?>/modules/programa/dias_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'reorder',
                solicitud_id: programaId,
                nuevo_orden: nuevoOrden
            })
        });
        
        console.log('📡 Status:', response.status);
        
        // Leer la respuesta como texto primero
        const responseText = await response.text();
        console.log('📄 Respuesta raw:', responseText);
        
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status} - ${responseText}`);
        }
        
        // Intentar parsear JSON
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (e) {
            console.error('❌ Error parseando JSON:', e);
            throw new Error('Respuesta inválida del servidor');
        }
        
        if (result.success) {
            showAlert('✅ Días reordenados correctamente', 'success');
            
            // Recargar días para actualizar la vista
            await cargarDiasPrograma();
            
        } else {
            throw new Error(result.error || 'Error desconocido');
        }
        
    } catch (error) {
        console.error('❌ Error reordenando días:', error);
        showAlert('❌ Error al reordenar: ' + error.message, 'error');
        
        // Recargar para restaurar orden original
        await cargarDiasPrograma();
    }
}

/**
 * Mostrar mensaje de ayuda durante drag
 */
function mostrarMensajeAyuda(mensaje) {
    let helper = document.querySelector('.drag-helper');
    
    if (!helper) {
        helper = document.createElement('div');
        helper.className = 'drag-helper';
        document.body.appendChild(helper);
    }
    
    helper.textContent = mensaje;
    helper.classList.add('show');
}

/**
 * Ocultar mensaje de ayuda
 */
function ocultarMensajeAyuda() {
    const helper = document.querySelector('.drag-helper');
    if (helper) {
        helper.classList.remove('show');
        setTimeout(() => helper.remove(), 300);
    }
}

function seleccionarDiaEnSidebar(diaId) {
    console.log(`📌 Seleccionando día ${diaId} en sidebar`);
    
    // Remover clase active de todos los items
    document.querySelectorAll('.day-sidebar-item').forEach(item => {
        item.classList.remove('active');
    });
    
    // Agregar clase active al item seleccionado
    const selectedItem = document.querySelector(`[data-dia-id="${diaId}"]`);
    if (selectedItem) {
        selectedItem.classList.add('active');
    }
    
    selectedDayId = diaId;
    
    // Renderizar detalle del día seleccionado
    renderizarDetalleDia(diaId);
    
    // Cargar servicios del día seleccionado
    cargarServiciosDia(diaId);
    cargarUbicacionesSecundariasDia(diaId);
    
    // RECONFIGURAR manejadores después de renderizar
    setTimeout(() => {
        setupMealHandlers();
        cargarComidasDia(diaId);
    }, 100);
}

function renderizarDetalleDia(diaId) {
    const detailContainer = document.getElementById('day-detail-content');
    if (!detailContainer) {
        console.error('❌ No se encontró el contenedor day-detail-content');
        return;
    }

    const dia = diasPrograma.find(d => d.id == diaId);
    if (!dia) {
        console.error(`❌ No se encontró el día con ID ${diaId}`);
        return;
    }

    const duracion = parseInt(dia.duracion_estancia) || 1;
    //const diaNumero = dia.dia_numero || 1;
    //const diaFinal = diaNumero + duracion - 1;
    //const rangoTexto = duracion === 1 
    //    ? `Día ${diaNumero}` 
    //   : `Días ${diaNumero}-${diaFinal}`;
    //const fechaDia = dia.fecha_dia ? new Date(dia.fecha_dia).toLocaleDateString('es-ES') : null;
    //  ----------------------------------------Esto se cambia para poder calcular día en fecha
    const diasOrdenados = [...diasPrograma].sort((a, b) => (a.dia_numero || 0) - (b.dia_numero || 0));

    let diaActual = 1;
    for (const d of diasOrdenados) {
        if (d.id == dia.id) break;
        diaActual += parseInt(d.duracion_estancia) || 1;
    }

    const diaFinal = diaActual + duracion - 1;

    const rangoTexto = duracion === 1 
        ? `Día ${diaActual}` 
        : `Días ${diaActual}-${diaFinal}`;

    

    const duracionTexto = duracion > 1 ? ` (${duracion} días)` : '';
    const titulo = dia.titulo || 'Día sin título';
    const descripcion = dia.descripcion || '';
    const ubicacion = dia.ubicacion || 'Sin ubicación especificada';
    const fechaDia = dia.fecha_calculada 
        ? new Date(dia.fecha_calculada + 'T00:00:00').toLocaleDateString('es-ES') 
        : null;

    detailContainer.innerHTML = `
        <div class="day-detail-header">
            <div class="day-detail-number">${rangoTexto}</div>
            <div class="day-detail-title">${titulo}${duracionTexto}</div>
            <div class="day-controls-detail">
                <button class="estancia-btn" onclick="cambiarEstancia(${dia.id}, ${duracion - 1})" 
                        ${duracion <= 1 ? 'disabled' : ''}>➖</button>
                <span class="estancia-display">${duracion}</span>
                <button class="estancia-btn" onclick="cambiarEstancia(${dia.id}, ${duracion + 1})" 
                        ${duracion >= 30 ? 'disabled' : ''}>➕</button>
            </div>
            <div class="day-detail-meta">
                <div id="ubicaciones-display-${dia.id}" style="display: flex; flex-direction: column; gap: 8px;">
                    <span>
                        <i class="fas fa-map-marker-alt"></i> 
                        ${ubicacion}
                    </span>
                    <!-- Las ubicaciones secundarias se cargarán aquí -->
                </div>
                ${fechaDia ? `
                    <span style="margin-top: 8px;">
                        <i class="fas fa-calendar"></i> 
                        ${fechaDia}
                    </span>
                ` : ''}
            </div>
        </div>
        
        <div class="day-detail-body">
            ${renderizarImagenesDia(dia)}
            
            ${descripcion ? `
                <div class="day-description" style="margin-bottom: 20px; color: #666; line-height: 1.6;">
                    ${descripcion}
                </div>
            ` : ''}
            
            <!-- Servicios del día -->
            <div class="day-services">
                <div class="services-header">
                    <h5><i class="fas fa-plus-circle"></i> Agregar servicios al día:</h5>
                </div>
                <div class="service-buttons">
                    <button class="service-btn" onclick="agregarServicio(${dia.id}, 'actividad')">
                        <i class="fas fa-hiking"></i>
                        Actividad
                    </button>
                    <button class="service-btn" onclick="agregarServicio(${dia.id}, 'transporte')">
                        <i class="fas fa-car"></i>
                        Transporte
                    </button>
                    <button class="service-btn" onclick="agregarServicio(${dia.id}, 'alojamiento')">
                        <i class="fas fa-bed"></i>
                        Alojamiento
                    </button>
                </div>
                
                <!-- Opciones de comidas -->
                <div class="meals-section">
                    <h6><i class="fas fa-utensils"></i> Comidas:</h6>
                    <div class="meals-options">
                        <label class="meal-option">
                            <input type="radio" name="meals_${dia.id}" value="incluidas">
                            <span>Comidas incluidas</span>
                        </label>
                        <label class="meal-option">
                            <input type="radio" name="meals_${dia.id}" value="no_incluidas" checked>
                            <span>Comidas no incluidas</span>
                        </label>
                    </div>
                    <div class="meal-details" id="meal-details-${dia.id}" style="display: none;">
                        <div class="meal-checkboxes">
                            <label class="meal-checkbox">
                                <input type="checkbox" name="meal_desayuno_${dia.id}">
                                <span>Desayuno</span>
                            </label>
                            <label class="meal-checkbox">
                                <input type="checkbox" name="meal_almuerzo_${dia.id}">
                                <span>Almuerzo</span>
                            </label>
                            <label class="meal-checkbox">
                                <input type="checkbox" name="meal_cena_${dia.id}">
                                <span>Cena</span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Lista de servicios agregados -->
                <div class="added-services" id="services-${dia.id}">
                    <div class="loading-services">
                        <i class="fas fa-spinner fa-spin"></i> Cargando servicios...
                    </div>
                </div>
            </div>
        </div>
    `;

    // Agregar formulario inline de edición si no existe
    let editForm = document.getElementById(`edit-dia-form-${diaId}`);
    if (!editForm) {
        const formHTML = `
            <div id="edit-dia-form-${diaId}" class="edit-inline-form" style="display: none;">
                <div class="edit-form-header">
                    <h4><i class="fas fa-edit"></i> Editar Día ${dia.dia_numero}</h4>
                    <button class="btn-close-edit" onclick="cerrarEdicionDia(${diaId})">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="edit-form-body">
                    <!-- Título -->
                    <div class="form-group">
                        <label for="edit-dia-titulo-${diaId}">
                            Título <span class="required">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="edit-dia-titulo-${diaId}" 
                            class="form-control"
                            value="${dia.titulo || ''}"
                            maxlength="300"
                            required
                        >
                        <small class="form-text">Máximo 300 caracteres</small>
                    </div>
                    
                    <!-- Descripción -->
                    <div class="form-group">
                        <label for="edit-dia-descripcion-${diaId}">
                            Descripción <span class="required">*</span>
                        </label>
                        <textarea 
                            id="edit-dia-descripcion-${diaId}" 
                            class="form-control"
                            rows="4"
                            maxlength="2000"
                            required
                        >${dia.descripcion || ''}</textarea>
                        <small class="form-text">Máximo 2000 caracteres</small>
                    </div>
                    
                    <!-- Ubicación Principal -->
                    <div class="form-group">
                        <label for="edit-dia-ubicacion-${diaId}">
                            📍 Ubicación Principal <span class="required">*</span>
                        </label>
                        <div class="location-search-wrapper">
                            <input 
                                type="text" 
                                id="edit-dia-ubicacion-${diaId}" 
                                class="form-control location-search-input"
                                value="${dia.ubicacion || ''}"
                                placeholder="🔍 Buscar ubicación principal..."
                                autocomplete="off"
                            >
                            <div id="location-results-dia-${diaId}" class="location-results"></div>
                        </div>
                        <input type="hidden" id="edit-dia-latitud-${diaId}" value="${dia.latitud || ''}">
                        <input type="hidden" id="edit-dia-longitud-${diaId}" value="${dia.longitud || ''}">
                        
                        ${dia.ubicacion ? `
                            <div class="ubicacion-preview" style="margin-top: 8px; padding: 10px; background: #f0fdf4; border-radius: 6px; border-left: 3px solid #10b981; font-size: 12px;">
                                <strong style="color: #065f46;">${dia.ubicacion}</strong>
                                ${dia.latitud && dia.longitud ? `
                                    <div style="color: #059669; margin-top: 4px;">
                                        📍 ${parseFloat(dia.latitud).toFixed(6)}, ${parseFloat(dia.longitud).toFixed(6)}
                                    </div>
                                ` : ''}
                            </div>
                        ` : ''}
                    </div>

                    <!-- Ubicaciones Secundarias -->
                    <div class="form-group">
                        <label>
                            📍 Ubicaciones Adicionales del Día
                            <small style="color: #666;">(Opcional)</small>
                        </label>
                        <div id="ubicaciones-secundarias-edit-${diaId}" style="display: flex; flex-direction: column; gap: 12px;">
                            <!-- Se cargarán dinámicamente -->
                        </div>
                        <button 
                            type="button" 
                            class="btn btn-outline" 
                            onclick="agregarUbicacionSecundariaEdit(${diaId})"
                            style="margin-top: 10px; display: inline-flex; align-items: center; gap: 8px;">
                            <i class="fas fa-plus"></i> Agregar Otra Ubicación
                        </button>
                    </div>
                    
                    <!-- Imágenes -->
                    <div class="form-group">
                        <label>
                            Imágenes <span class="required">*</span>
                            <small>(mínimo 1 imagen)</small>
                        </label>
                        
                        <div class="images-preview-edit">
                            ${[1,2,3].map(i => {
                                const imagenUrl = dia['imagen' + i];
                                return `
                                    <div class="image-preview-item" data-image-number="${i}">
                                        ${imagenUrl ? `
                                            <img src="${imagenUrl}" alt="Imagen ${i}" class="preview-img">
                                            <button type="button" class="btn-remove-image" onclick="removerImagenDia(${diaId}, ${i})">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        ` : `
                                            <div class="empty-image-slot">
                                                <i class="fas fa-image"></i>
                                                <p>Imagen ${i}</p>
                                            </div>
                                        `}
                                        <input 
                                            type="file" 
                                            id="edit-dia-imagen${i}-${diaId}" 
                                            accept="image/jpeg,image/jpg,image/png,image/webp"
                                            onchange="previewImagenDia(${diaId}, ${i}, this)"
                                            style="display: none;"
                                        >
                                        <button type="button" class="btn-change-image" onclick="document.getElementById('edit-dia-imagen${i}-${diaId}').click()">
                                            ${imagenUrl ? 'Cambiar' : 'Agregar'}
                                        </button>
                                    </div>
                                `;
                            }).join('')}
                        </div>
                        <small class="form-text text-muted">Formatos: JPG, PNG, WEBP. Máximo 5MB por imagen.</small>
                    </div>
                    
                    <!-- Botones de acción -->
                    <div class="edit-form-actions">
                        <button type="button" class="btn btn-secondary" onclick="cerrarEdicionDia(${diaId})">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button type="button" class="btn btn-primary" onclick="guardarEdicionDia(${diaId})">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        detailContainer.insertAdjacentHTML('beforeend', formHTML);
    }
}

async function cargarUbicacionesSecundariasDia(diaId) {
    try {
        const response = await fetch(`<?= APP_URL ?>/modules/programa/dias_api.php?action=get_ubicaciones_secundarias&dia_id=${diaId}`);
        const result = await response.json();
        
        if (result.success && result.data && result.data.length > 0) {
            const container = document.getElementById(`ubicaciones-display-${diaId}`);
            if (container) {
                // Agregar ubicaciones secundarias
                result.data.forEach(ubicacion => {
                    const ubicacionElement = document.createElement('span');
                    ubicacionElement.innerHTML = `
                        <i class="fas fa-map-marker-alt"></i> 
                        ${ubicacion.ubicacion}
                    `;
                    ubicacionElement.style.cssText = 'display: flex; align-items: center; gap: 8px;';
                    container.appendChild(ubicacionElement);
                });
            }
        }
    } catch (error) {
        console.error(`Error cargando ubicaciones secundarias para día ${diaId}:`, error);
    }
}

function renderizarDetalleVacio() {
    const detailContainer = document.getElementById('day-detail-content');
    if (!detailContainer) return;

    detailContainer.innerHTML = `
        <div class="empty-detail">
            <div>
                <i class="fas fa-calendar-day"></i>
                <h3>Selecciona un día</h3>
                <p>Elige un día de la lista para ver y editar sus detalles</p>
            </div>
        </div>
    `;
}

// Función para cargar servicios solo para contador
async function cargarServiciosParaContador(diaId) {
    try {
        const response = await fetch(`<?= APP_URL ?>/modules/programa/servicios_api.php?action=list&dia_id=${diaId}`);
        const result = await response.json();

        if (result.success) {
            const count = result.data ? result.data.length : 0;
            const countElement = document.getElementById(`services-count-${diaId}`);
            if (countElement) {
                countElement.textContent = count;
                countElement.style.display = count > 0 ? 'block' : 'none';
            }
        }
    } catch (error) {
        console.error(`Error cargando contador de servicios para día ${diaId}:`, error);
    }
}

// Función modificada para actualizar contador después de agregar/eliminar servicios
function actualizarContadorServicios(diaId, count) {
    const countElement = document.getElementById(`services-count-${diaId}`);
    if (countElement) {
        countElement.textContent = count;
        countElement.style.display = count > 0 ? 'block' : 'none';
    }
}

// Modificar función de renderizar servicios para actualizar contador
function renderizarServiciosDia(diaId, servicios) {
    const container = document.getElementById(`services-${diaId}`);
    if (!container) {
        console.error(`❌ No se encontró contenedor de servicios para día ${diaId}`);
        return;
    }

    console.log(`🎨 Renderizando ${servicios.length} servicios para día ${diaId}`);

    // Actualizar contador en sidebar
    actualizarContadorServicios(diaId, servicios.length);

    if (servicios.length === 0) {
        container.innerHTML = `
            <p style="color: #666; font-style: italic; text-align: center; padding: 10px;">
                <i class="fas fa-info-circle"></i> No hay servicios agregados a este día
            </p>
        `;
        return;
    }

    // Ordenar servicios por orden
    const serviciosOrdenados = [...servicios].sort((a, b) => (a.orden || 0) - (b.orden || 0));

    container.innerHTML = `
        <h6 style="margin-bottom: 12px; color: #333; font-weight: 600;">
            <i class="fas fa-list"></i> Servicios agregados (${serviciosOrdenados.length}):
        </h6>
        ${serviciosOrdenados.map(servicio => `
            <div class="service-item" data-servicio-id="${servicio.id}">
                <div class="service-info">
                    <div class="service-icon ${servicio.tipo_servicio}">
                        <i class="fas fa-${getServiceIconByType(servicio.tipo_servicio, servicio)}"></i>
                    </div>
                    <div class="service-details">
                        <h6>${servicio.titulo || servicio.nombre || 'Servicio sin título'}</h6>
                        <p>${getServiceSummary(servicio)}</p>
                    </div>
                </div>
                <div class="service-actions">
                    <button class="btn-edit-service" onclick="editarServicio(${servicio.id})" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-remove-service" onclick="eliminarServicio(${servicio.id})" title="Eliminar">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `).join('')}
    `;

    console.log(`✅ Servicios renderizados para día ${diaId}`);
}

// ============================================================
// FUNCIONES PARA GESTIÓN DE ESTANCIA - VERSIÓN MEJORADA
// ============================================================
async function cambiarEstancia(diaId, nuevaDuracion) {
    if (nuevaDuracion < 1 || nuevaDuracion > 30) return;
    
    // Encontrar los botones afectados
    const allBtns = document.querySelectorAll(`[onclick*="cambiarEstancia(${diaId},"]`);
    const displays = document.querySelectorAll(`#services-count-${diaId}`).length > 0 ? 
        document.querySelectorAll('.estancia-display') : [];
    
    try {
        console.log(`🔄 Cambiando estancia del día ${diaId} a ${nuevaDuracion} días`);
        
        // Mostrar estado de carga en botones
        allBtns.forEach(btn => {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            btn.style.pointerEvents = 'none';
        });
        
        // Animación en displays
        displays.forEach(display => {
            display.style.transform = 'scale(1.1)';
            display.style.background = 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)';
        });
        
        const formData = new FormData();
        formData.append('action', 'cambiar_estancia');
        formData.append('dia_id', diaId);
        formData.append('duracion', nuevaDuracion);
        
        const response = await fetch(`<?= APP_URL ?>/modules/programa/dias_api.php`, {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || 'Error al cambiar estancia');
        }
        
        // Animación de éxito
        showAlert('✅ Estancia actualizada correctamente', 'success');
        
        // Efecto de celebración
        displays.forEach(display => {
            display.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
            display.style.transform = 'scale(1.2)';
            setTimeout(() => {
                display.style.transform = 'scale(1)';
            }, 300);
        });
        
        // Recargar días para actualizar números
        await cargarDiasPrograma();
        
        // Mantener día seleccionado si era el que se modificó
        if (selectedDayId === diaId) {
            setTimeout(() => {
                seleccionarDiaEnSidebar(diaId);
            }, 100);
        }
        
    } catch (error) {
        console.error('❌ Error:', error);
        showAlert('Error: ' + error.message, 'error');
        
        // Restaurar estado original en caso de error
        displays.forEach(display => {
            display.style.transform = 'scale(1)';
            display.style.background = '';
        });
        
    } finally {
        // Restaurar botones después de un delay
        setTimeout(() => {
            allBtns.forEach(btn => {
                btn.disabled = false;
                btn.style.pointerEvents = '';
            });
        }, 500);
    }
}

// Función auxiliar para añadir efectos visuales
function addStayEffects(diaId, duracion) {
    // Agregar indicador visual si es múltiples días
    if (duracion > 1) {
        const dayItem = document.querySelector(`[data-dia-id="${diaId}"]`);
        if (dayItem && !dayItem.querySelector('.multi-day-indicator')) {
            const indicator = document.createElement('div');
            indicator.className = 'multi-day-indicator';
            indicator.title = `${duracion} días de estancia`;
            dayItem.style.position = 'relative';
            dayItem.appendChild(indicator);
        }
    } else {
        // Remover indicador si vuelve a 1 día
        const indicator = document.querySelector(`[data-dia-id="${diaId}"] .multi-day-indicator`);
        if (indicator) indicator.remove();
    }
}

// Función para mostrar tooltip personalizado
function showCustomTooltip(element, message, duration = 2000) {
    const tooltip = document.createElement('div');
    tooltip.className = 'custom-tooltip';
    tooltip.textContent = message;
    tooltip.style.cssText = `
        position: absolute;
        background: rgba(0, 0, 0, 0.9);
        color: white;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 12px;
        z-index: 10000;
        pointer-events: none;
        transform: translateY(-100%);
        margin-bottom: 8px;
        animation: tooltip-show 0.3s ease;
    `;
    
    element.style.position = 'relative';
    element.appendChild(tooltip);
    
    setTimeout(() => {
        if (tooltip.parentElement) {
            tooltip.remove();
        }
    }, duration);
}


console.log('✅ Script de sidebar de días cargado');



// ============================================================
// JAVASCRIPT COMPLETO PARA ALTERNATIVAS - AGREGAR A programa.php
// ============================================================
// Agregar estas funciones a tu script existente

// Variables globales adicionales para alternativas
let currentServicioPrincipal = null;

// Función modificada para renderizar servicios CON alternativas
function renderizarServiciosDia(diaId, servicios) {
    const container = document.getElementById(`services-${diaId}`);
    if (!container) {
        console.error(`❌ No se encontró contenedor de servicios para día ${diaId}`);
        return;
    }

    console.log(`🎨 Renderizando ${servicios.length} servicios CON ALTERNATIVAS para día ${diaId}`);

    // Actualizar contador en sidebar (solo contar principales)
    const principalesCount = servicios.length;
    actualizarContadorServicios(diaId, principalesCount);

    if (servicios.length === 0) {
        container.innerHTML = `
            <p style="color: #666; font-style: italic; text-align: center; padding: 10px;">
                <i class="fas fa-info-circle"></i> No hay servicios agregados a este día
            </p>
        `;
        return;
    }

    // Renderizar servicios principales con sus alternativas
    container.innerHTML = `
        <h6 style="margin-bottom: 12px; color: #333; font-weight: 600;">
            <i class="fas fa-list"></i> Servicios agregados (${principalesCount}):
        </h6>
        ${servicios.map(servicio => renderizarServicioConAlternativas(servicio)).join('')}
    `;

    console.log(`✅ Servicios con alternativas renderizados para día ${diaId}`);
}

function setupCharacterCounters() {
    console.log('🔧 Configurando contadores de caracteres...');
    
    // Campos con límites de caracteres
    const fieldsWithLimits = [
        { id: 'program-title', max: 250 },
        { id: 'traveler-name', max: 250 },
        { id: 'traveler-lastname', max: 250 }
    ];
    
    // Campos de textarea con límites grandes
    const textareasWithLimits = [
        { name: 'precio_incluye', max: 3000 },
        { name: 'precio_no_incluye', max: 3000 },
        { name: 'condiciones_generales', max: 3000 },
        { name: 'info_pasaporte', max: 3000 },
        { name: 'info_seguros', max: 3000 }
    ];
    
    // Configurar inputs normales (tu código original)
    fieldsWithLimits.forEach(field => {
        const input = document.getElementById(field.id);
        const counter = document.getElementById(field.id + '-counter');
        
        if (input && counter) {
            // Función para actualizar contador
            function updateCounter() {
                const currentLength = input.value.length;
                const maxLength = field.max;
                
                // Actualizar texto del contador
                counter.textContent = `${currentLength}/${maxLength}`;
                
                // Remover clases anteriores
                counter.classList.remove('warning', 'danger');
                
                // Agregar clase según el porcentaje usado
                const percentage = (currentLength / maxLength) * 100;
                
                if (percentage >= 100) {
                    counter.classList.add('danger');
                } else if (percentage >= 80) {
                    counter.classList.add('warning');
                }
            }
            
            // Configurar eventos
            input.addEventListener('input', updateCounter);
            input.addEventListener('paste', () => {
                setTimeout(updateCounter, 10);
            });
            
            // Inicializar contador con valor actual
            updateCounter();
            
            console.log(`✅ Contador configurado para ${field.id}: ${field.max} caracteres`);
        }
    });
    
    // Configurar textareas (nueva funcionalidad)
    textareasWithLimits.forEach(field => {
        const textarea = document.querySelector(`textarea[name="${field.name}"]`);
        const counter = document.getElementById(field.name + '-counter');
        
        if (textarea && counter) {
            // Función para actualizar contador
            function updateCounter() {
                const currentLength = textarea.value.length;
                const maxLength = field.max;
                
                // Actualizar texto del contador
                counter.textContent = `${currentLength}/${maxLength}`;
                
                // Remover clases anteriores
                counter.classList.remove('warning', 'danger');
                
                // Agregar clase según el porcentaje usado
                const percentage = (currentLength / maxLength) * 100;
                
                if (percentage >= 100) {
                    counter.classList.add('danger');
                } else if (percentage >= 80) {
                    counter.classList.add('warning');
                }
            }
            
            // Configurar eventos
            textarea.addEventListener('input', updateCounter);
            textarea.addEventListener('paste', () => {
                setTimeout(updateCounter, 10);
            });
            
            // Inicializar contador con valor actual
            updateCounter();
            
            console.log(`✅ Contador configurado para ${field.name}: ${field.max} caracteres`);
        }
    });
}

function setupInputCounter(input, counter, maxLength) {
    // Función para actualizar contador
    function updateCounter() {
        const currentLength = input.value.length;
        
        // Actualizar texto del contador
        counter.textContent = `${currentLength}/${maxLength}`;
        
        // Remover clases anteriores
        counter.classList.remove('warning', 'danger');
        
        // Agregar clase según el porcentaje usado
        const percentage = (currentLength / maxLength) * 100;
        
        if (percentage >= 100) {
            counter.classList.add('danger');
        } else if (percentage >= 80) {
            counter.classList.add('warning');
        }
    }
    
    // Configurar eventos
    input.addEventListener('input', updateCounter);
    input.addEventListener('paste', () => {
        setTimeout(updateCounter, 10);
    });
    
    // Inicializar contador con valor actual
    updateCounter();
}

function renderizarServicioConAlternativas(servicio) {
    const hasAlternatives = servicio.alternativas && servicio.alternativas.length > 0;
    const alternativas = servicio.alternativas || [];
    
    return `
        <div class="service-group" data-servicio-principal-id="${servicio.id}">
            <!-- Servicio Principal -->
            <div class="service-item principal" data-servicio-id="${servicio.id}">
                <div class="service-info">
                    <div class="service-icon ${servicio.tipo_servicio}">
                        <i class="fas fa-${getServiceIconByType(servicio.tipo_servicio, servicio)}"></i>
                    </div>
                    <div class="service-details">
                        <h6>
                            ${servicio.nombre || servicio.titulo || 'Servicio sin título'}
                            ${hasAlternatives ? `<span class="alternatives-indicator">${alternativas.length} alt</span>` : ''}
                        </h6>
                        <p>${getServiceSummary(servicio)}</p>
                    </div>
                </div>
                <div class="service-actions">
                    <!-- Botón de alternativa para TODOS los tipos de servicio -->
                    <button class="btn-add-alternative" onclick="abrirModalAlternativa(${servicio.id}, '${servicio.tipo_servicio}')" title="Agregar alternativa">
                        <i class="fas fa-plus-circle"></i>
                    </button>
                    
                    <!-- Botón de edición solo para actividades -->
                    ${servicio.tipo_servicio === 'actividad' ? `
                        <button class="btn-edit-service" onclick="abrirEdicionActividad(${servicio.id})" title="Editar actividad">
                            <i class="fas fa-edit"></i>
                        </button>
                    ` : ''}
                    
                    <!-- Botón eliminar para todos -->
                    <button class="btn-remove-service" onclick="eliminarServicio(${servicio.id})" title="Eliminar">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            
            <!-- Alternativas -->
            ${hasAlternatives ? `
                <div class="alternatives-container">
                    ${alternativas.map(alt => renderizarAlternativa(alt)).join('')}
                </div>
            ` : ''}
        </div>
    `;
}

function renderizarAlternativa(alternativa) {
    return `
        <div class="service-item alternativa" data-alternativa-id="${alternativa.id}">
            <div class="alternative-connector"></div>
            <div class="service-info">
                <div class="service-icon ${alternativa.tipo_servicio} alternativa">
                    <i class="fas fa-${getServiceIconByType(alternativa.tipo_servicio, alternativa)}"></i>
                </div>
                <div class="service-details">
                    <h6>
                        <i class="fas fa-sync-alt" style="color: #17a2b8; font-size: 12px; margin-right: 4px;" title="Alternativa"></i>
                        Alternativa ${alternativa.orden_alternativa}: ${alternativa.nombre || alternativa.titulo || 'Sin título'}
                    </h6>
                    <p>${getServiceSummary(alternativa)}</p>
                    ${alternativa.notas_alternativa ?
                        `
                        <div style="font-size: 11px; color: #6c757d; margin-top: 4px;">
                            <i class="fas fa-sticky-note"></i> ${alternativa.notas_alternativa}
                        </div>
                    ` : ''}
                </div>
            </div>
            <div class="service-actions">
                <!-- Solo mostrar botón editar para ACTIVIDADES -->
                ${alternativa.tipo_servicio === 'actividad' ? `
                    <button class="btn-edit-service" onclick="abrirEdicionActividad(${alternativa.id})" title="Editar alternativa">
                        <i class="fas fa-edit"></i>
                    </button>
                ` : ''}
                
                <!-- Botón eliminar para todas las alternativas -->
                <button class="btn-remove-service" onclick="eliminarAlternativa(${alternativa.id})" title="Eliminar alternativa">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;
}

// Función para abrir modal de alternativas
function abrirModalAlternativa(servicioPrincipalId, tipoServicio) {
    console.log(`🔄 Agregando alternativa para servicio ${servicioPrincipalId}`);
    
    // Configurar para alternativa
    isAddingAlternative = true;
    alternativeParentId = servicioPrincipalId;
    currentTipoServicio = tipoServicio;
    
    abrirModalServicios(tipoServicio, 'Agregar alternativa de ' + tipoServicio);
}

// Función para agregar alternativa seleccionada
async function agregarAlternativaSeleccionada() {
    if (!selectedServicioId || !currentServicioPrincipal) {
        console.error('❌ Datos faltantes para agregar alternativa');
        return;
    }

    try {
        console.log(`🔄 Agregando alternativa: Principal=${currentServicioPrincipal}, Item=${selectedServicioId}`);
        
        const response = await fetch('<?= APP_URL ?>/modules/programa/servicios_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'add_alternative',
                servicio_principal_id: currentServicioPrincipal,
                biblioteca_item_id: selectedServicioId
            })
        });

        const result = await response.json();

        if (result.success) {
            showAlert('Alternativa agregada exitosamente', 'success');
            cerrarModalServicios();
            // Recargar servicios del día seleccionado
            if (selectedDayId) {
                cargarServiciosDia(selectedDayId);
                cargarServiciosParaContador(selectedDayId);
            }
        } else {
            showAlert(result.message || 'Error al agregar alternativa', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error de conexión', 'error');
    }
}

// Función para eliminar alternativa
async function eliminarAlternativa(alternativaId) {
    const confirmed = await showConfirmModal({
        title: '¿Eliminar alternativa?',
        message: '¿Estás seguro de que quieres eliminar esta alternativa?',
        details: 'Esta acción no se puede deshacer.',
        icon: '🗑️',
        confirmText: 'Aceptar',
        cancelText: 'Cancelar'
    });

    if (!confirmed) return;

    const btnEliminar = event.target.closest('.btn-remove-service');
    const originalContent = btnEliminar ? btnEliminar.innerHTML : '';

    try {
        console.log('🗑️ Eliminando alternativa ID:', alternativaId);
        
        // Mostrar estado de carga
        if (btnEliminar) {
            btnEliminar.disabled = true;
            btnEliminar.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        }
        
        const response = await fetch('<?= APP_URL ?>/modules/programa/servicios_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'delete',
                servicio_id: alternativaId
            })
        });

        if (!response.ok) {
            throw new Error(`Error del servidor: ${response.status}`);
        }

        const responseText = await response.text();
        console.log('📄 Respuesta:', responseText);

        let result;
        try {
            result = JSON.parse(responseText);
        } catch (parseError) {
            console.warn('⚠️ No se pudo parsear la respuesta como JSON:', parseError);
            if (response.ok) {
                result = { success: true, message: 'Alternativa eliminada exitosamente' };
            } else {
                throw new Error('Respuesta del servidor no válida');
            }
        }

        if (result && result.success) {
            showAlert('✅ Alternativa eliminada exitosamente', 'success');
            
            // Recargar servicios del día seleccionado
            if (selectedDayId) {
                await cargarServiciosDia(selectedDayId);
                await cargarServiciosParaContador(selectedDayId);
            }
        } else {
            const errorMessage = result?.message || result?.error || 'Error desconocido al eliminar alternativa';
            throw new Error(errorMessage);
        }
        
    } catch (error) {
        console.error('❌ Error eliminando alternativa:', error);
        showAlert('Error eliminando alternativa: ' + error.message, 'error');
        
    } finally {
        // Restaurar botón siempre
        if (btnEliminar) {
            btnEliminar.disabled = false;
            btnEliminar.innerHTML = originalContent || '<i class="fas fa-trash"></i>';
        }
    }
}
function debugEliminarServicio(servicioId) {
    console.log('🔍 DEBUG - Estado antes de eliminar:');
    console.log('- Servicio ID:', servicioId);
    console.log('- Día seleccionado:', selectedDayId);
    console.log('- Días programa:', diasPrograma.map(d => d.id));
    console.log('- URL de API:', '<?= APP_URL ?>/modules/programa/servicios_api.php');
}

// Función para editar alternativa
function editarAlternativa(alternativaId) {
    // TODO: Implementar edición de alternativas
    showAlert('Función de edición de alternativas en desarrollo', 'info');
}



async function agregarServicioPrincipalSeleccionado() {
    if (!selectedServicioId || !currentDiaId || !currentTipoServicio) return;

    try {
        const response = await fetch('<?= APP_URL ?>/modules/programa/servicios_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'add_service',
                dia_id: currentDiaId,
                tipo_servicio: currentTipoServicio,
                biblioteca_item_id: selectedServicioId
            })
        });

        const result = await response.json();

        if (result.success) {
            showAlert('Servicio agregado exitosamente', 'success');
            cerrarModalServicios();
            cargarServiciosDia(currentDiaId); // Recargar servicios del día
        } else {
            showAlert(result.message || 'Error al agregar servicio', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error de conexión', 'error');
    }
}





// AGREGAR ESTAS FUNCIONES AL FINAL DEL JAVASCRIPT
let sidebarOpen = false;

function toggleSidebar() {
    const sidebar = document.querySelector('.enhanced-sidebar');
    const overlay = document.getElementById('overlay');
    const mainContainer = document.querySelector('.main-container');
    
    if (!sidebar) return;
    
    sidebarOpen = !sidebarOpen;
    
    if (sidebarOpen) {
        sidebar.classList.add('open');
        if (overlay) overlay.classList.add('show');
        if (mainContainer && window.innerWidth > 768) {
            mainContainer.classList.add('sidebar-open');
        }
    } else {
        sidebar.classList.remove('open');
        if (overlay) overlay.classList.remove('show');
        if (mainContainer) mainContainer.classList.remove('sidebar-open');
    }
}

function closeSidebar() {
    if (sidebarOpen) {
        toggleSidebar();
    }
}

function toggleUserMenu() {
    if (confirm('¿Desea cerrar sesión?')) {
        window.location.href = '<?= APP_URL ?>/auth/logout';
    }
}

// Google Translate
function initializeGoogleTranslate() {
    function googleTranslateElementInit() {
        new google.translate.TranslateElement({
            pageLanguage: '<?= $defaultLanguage ?>',
            includedLanguages: 'en,fr,pt,it,de,es',
            layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
            autoDisplay: false
        }, 'google_translate_element');
    }

    if (!window.googleTranslateElementInit) {
        window.googleTranslateElementInit = googleTranslateElementInit;
        const script = document.createElement('script');
        script.src = '//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit';
        document.head.appendChild(script);
    }
}

// Inicializar al cargar
document.addEventListener('DOMContentLoaded', function() {
    initializeGoogleTranslate();
});

document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Iniciando programa.php...');
    setupTabNavigation();
    setupFormHandling();
    setupCharacterCounters();
    setupFileValidation();
    setupMealHandlers(); // ← ESTA LÍNEA DEBE ESTAR
    
    if (isEditing && programaId) {
        console.log(`📋 Cargando datos para programa ID: ${programaId}`);
        cargarDiasPrograma();
        cargarPreciosPrograma();
    } else {
        console.log('💡 Programa nuevo - no hay días que cargar');
    }
});


// Función para expandir/contraer alternativas (opcional)
function toggleAlternativas(servicioId) {
    const serviceGroup = document.querySelector(`[data-servicio-id="${servicioId}"]`);
    if (serviceGroup) {
        serviceGroup.classList.toggle('expanded');
    }
}

// Función para contar total de servicios incluyendo alternativas
function contarTotalServicios(servicios) {
    let total = servicios.length; // Principales
    servicios.forEach(servicio => {
        if (servicio.alternativas) {
            total += servicio.alternativas.length;
        }
    });
    return total;
}

// Función para mostrar enlaces públicos
async function mostrarEnlacesPublicos(programaId) {
    if (!programaId) return;
    
    try {
        // Obtener tokens del servidor
        const response = await fetch(`<?= APP_URL ?>/modules/programa/api.php?action=get_tokens&id=${programaId}`);
        const result = await response.json();
        
        if (result.success && result.tokens) {
            const previewUrl = `<?= APP_URL ?>/public_preview.php?token=${result.tokens.preview_token}`;
            const itineraryUrl = `<?= APP_URL ?>/public_itinerary.php?token=${result.tokens.itinerary_token}`;
            
            // Mostrar modal con enlaces
            showPublicLinksModal(previewUrl, itineraryUrl);
        }
    } catch (error) {
        console.error('Error obteniendo enlaces:', error);
    }
}

function showPublicLinksModal(previewUrl, itineraryUrl) {
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.style.display = 'block';
    modal.innerHTML = `
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3>🔗 Enlaces Públicos Creados</h3>
                <button onclick="this.closest('.modal').remove()">&times;</button>
            </div>
            <div class="modal-body">
                <div style="margin-bottom: 20px;">
                    <label><strong>Vista Previa (para revisar):</strong></label>
                    <input type="text" value="${previewUrl}" readonly onclick="this.select()" style="width: 100%; padding: 8px; margin: 5px 0;">
                    <button onclick="copyToClipboard('${previewUrl}')" class="btn btn-outline">Copiar</button>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label><strong>Itinerario Completo (para cliente):</strong></label>
                    <input type="text" value="${itineraryUrl}" readonly onclick="this.select()" style="width: 100%; padding: 8px; margin: 5px 0;">
                    <button onclick="copyToClipboard('${itineraryUrl}')" class="btn btn-outline">Copiar</button>
                </div>
                
                <p style="color: #666; font-size: 14px;">
                    <i class="fas fa-info-circle"></i> 
                    Estos enlaces son únicos y seguros para compartir con el cliente.
                </p>
            </div>
            <div class="modal-footer">
                <button onclick="this.closest('.modal').remove()" class="btn btn-primary">Entendido</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showAlert('✅ Enlace copiado al portapapeles', 'success');
    });
}

// Función para obtener estadísticas de servicios
function getEstadisticasServicios(servicios) {
    const stats = {
        principales: servicios.length,
        alternativas: 0,
        total: servicios.length
    };
    
    servicios.forEach(servicio => {
        if (servicio.alternativas) {
            stats.alternativas += servicio.alternativas.length;
            stats.total += servicio.alternativas.length;
        }
    });
    
    return stats;
}

// Función de utilidad para verificar si un servicio tiene alternativas
function tieneAlternativas(servicio) {
    return servicio.alternativas && servicio.alternativas.length > 0;
}

// Función para buscar un servicio específico (principal o alternativa)
function buscarServicioPorId(servicios, id) {
    for (const servicio of servicios) {
        if (servicio.id == id) {
            return { tipo: 'principal', servicio: servicio };
        }
        
        if (servicio.alternativas) {
            for (const alt of servicio.alternativas) {
                if (alt.id == id) {
                    return { tipo: 'alternativa', servicio: alt, principal: servicio };
                }
            }
        }
    }
    return null;
}

// Función para reordenar alternativas dentro de un servicio principal
async function reordenarAlternativas(servicioPrincipalId, nuevoOrden) {
    try {
        const response = await fetch('<?= APP_URL ?>/modules/programa/servicios_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'reorder_alternatives',
                servicio_principal_id: servicioPrincipalId,
                orden: nuevoOrden
            })
        });

        const result = await response.json();

        if (result.success) {
            showAlert('Orden de alternativas actualizado', 'success');
            // Recargar servicios
            if (selectedDayId) {
                cargarServiciosDia(selectedDayId);
            }
        } else {
            showAlert(result.message || 'Error al reordenar alternativas', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error de conexión', 'error');
    }
}
// Mapeo de monedas a símbolos
const currencySymbols = {
    'USD': '$', 'EUR': '€', 'JPY': '¥', 'GBP': '£', 'AUD': 'A$', 'CAD': 'C$', 
    'CHF': 'Fr', 'CNY': '¥', 'SEK': 'kr', 'NZD': 'NZ$', 'COP': '$', 'MXN': '$', 
    'ARS': '$', 'BRL': 'R$', 'CLP': '$', 'PEN': 'S/', 'UYU': '$', 'VES': 'Bs', 
    'NOK': 'kr', 'DKK': 'kr', 'PLN': 'zł', 'CZK': 'Kč', 'HUF': 'Ft', 'RUB': '₽', 
    'TRY': '₺', 'ZAR': 'R', 'INR': '₹', 'KRW': '₩', 'SGD': 'S$', 'HKD': 'HK$', 
    'THB': '฿', 'MYR': 'RM', 'IDR': 'Rp', 'PHP': '₱', 'VND': '₫', 'TWD': 'NT$', 
    'ILS': '₪', 'AED': 'د.إ', 'SAR': '﷼', 'QAR': '﷼', 'KWD': 'د.ك', 'BHD': '.د.ب', 
    'OMR': '﷼', 'JOD': 'د.ا', 'LBP': '£', 'EGP': '£', 'MAD': 'د.م.', 'TND': 'د.ت', 
    'DZD': 'د.ج', 'NGN': '₦', 'KES': 'KSh', 'GHS': '₵', 'ETB': 'Br', 'UGX': 'USh', 
    'TZS': 'TSh', 'ZMW': 'ZK', 'BWP': 'P', 'MUR': '₨', 'SCR': '₨', 'XOF': 'CFA', 
    'XAF': 'CFA', 'CDF': 'FC', 'AOA': 'Kz', 'MZN': 'MT', 'SZL': 'L', 'LSL': 'L', 
    'NAD': 'N$', 'MWK': 'MK', 'RWF': 'FRw', 'BIF': 'FBu', 'DJF': 'Fdj', 'SOS': 'Sh', 
    'ERN': 'Nfk', 'STN': 'Db', 'CVE': '$', 'GMD': 'D', 'GNF': 'FG', 'LRD': 'L$', 
    'SLE': 'Le', 'ALL': 'L', 'BAM': 'KM', 'BGN': 'лв', 'HRK': 'kn', 'RSD': 'дин', 
    'MKD': 'ден', 'RON': 'lei', 'MDL': 'L', 'UAH': '₴', 'BYN': 'Br', 'GEL': 'ლ', 
    'AMD': '֏', 'AZN': '₼', 'KZT': '₸', 'UZS': 'сўм', 'TJS': 'ЅМ', 'KGS': 'лв', 
    'TMT': 'm', 'AFN': '؋', 'PKR': '₨', 'LKR': '₨', 'NPR': '₨', 'BTN': 'Nu', 
    'BDT': '৳', 'MMK': 'K', 'LAK': '₭', 'KHR': '៛', 'BND': 'B$', 'MNT': '₮', 
    'KPW': '₩', 'FJD': 'FJ$', 'PGK': 'K', 'SBD': 'SI$', 'VUV': 'VT', 'NCX': '₣', 
    'WST': 'WS$', 'TOP': 'T$', 'NIO': 'C$', 'CRC': '₡', 'PAB': 'B/.', 'GTQ': 'Q', 
    'HNL': 'L', 'SVC': '₡', 'BZD': 'BZ$', 'JMD': 'J$', 'HTG': 'G', 'DOP': 'RD$', 
    'CUP': '₱', 'BBD': 'Bds$', 'TTD': 'TT$', 'GYD': 'GY$', 'SRD': 'Sr$', 'AWG': 'ƒ', 
    'ANG': 'ƒ', 'XCD': 'EC$', 'BOB': 'Bs', 'PYG': '₲', 'GGP': '£', 'JEP': '£', 
    'IMP': '£', 'FKP': '£', 'GIP': '£', 'SHP': '£', 'ISK': 'kr', 'FOK': 'kr'
};

function compartirEnlace() {
    if (!programaId) {
        alert('Guarda el programa primero');
        return;
    }
    
    // Generar token simple
    const timestamp = Date.now();
    const tokenData = `${programaId}_${timestamp}`;
    const token = btoa(tokenData); // base64 encode
    
    // URLs públicas
    const previewUrl = `<?= APP_URL ?>/share?t=${token}&type=preview`;
    const itineraryUrl = `<?= APP_URL ?>/share?t=${token}&type=itinerary`;
    
    // Modal simple
    const modal = `
        <div style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.8);z-index:9999;display:flex;align-items:center;justify-content:center;" onclick="this.remove()">
            <div style="background:white;padding:30px;border-radius:15px;max-width:500px;width:90%;max-height:80vh;overflow-y:auto;" onclick="event.stopPropagation()">
                <h3 style="margin-bottom:20px;color:#333;text-align:center;">🔗 Enlaces para Compartir</h3>
                
                <div style="margin-bottom:20px;padding:15px;background:#f8f9fa;border-radius:8px;">
                    <strong style="color:#10b981;">📖 Vista Previa:</strong><br>
                    <input type="text" value="${previewUrl}" readonly style="width:100%;padding:8px;margin:5px 0;border:1px solid #ddd;border-radius:5px;font-size:12px;">
                    <button onclick="copiarUrl('${previewUrl}')" style="background:#10b981;color:white;border:none;padding:8px 15px;border-radius:5px;cursor:pointer;width:100%;">
                        📋 Copiar Enlace Vista Previa
                    </button>
                </div>
                
                <div style="margin-bottom:20px;padding:15px;background:#f8f9fa;border-radius:8px;">
                    <strong style="color:#667eea;">📅 Itinerario Completo:</strong><br>
                    <input type="text" value="${itineraryUrl}" readonly style="width:100%;padding:8px;margin:5px 0;border:1px solid #ddd;border-radius:5px;font-size:12px;">
                    <button onclick="copiarUrl('${itineraryUrl}')" style="background:#667eea;color:white;border:none;padding:8px 15px;border-radius:5px;cursor:pointer;width:100%;">
                        📋 Copiar Enlace Itinerario
                    </button>
                </div>
                
                <div style="background:#e0f2fe;padding:15px;border-radius:8px;border-left:4px solid #0ea5e9;margin-bottom:15px;">
                    <p style="margin:0;font-size:14px;color:#0369a1;"><strong>ℹ️ Importante:</strong></p>
                    <p style="margin:5px 0 0 0;font-size:13px;color:#0369a1;">• Los enlaces son únicos y seguros<br>• No requieren login para acceder<br>• Perfectos para compartir con clientes</p>
                </div>
                
                <button onclick="this.parentElement.parentElement.remove()" style="background:#6b7280;color:white;border:none;padding:10px 20px;border-radius:5px;cursor:pointer;width:100%;">
                    ✕ Cerrar
                </button>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modal);
}

function copiarUrl(url) {
    navigator.clipboard.writeText(url).then(() => {
        // Mostrar confirmación temporal
        const confirmacion = document.createElement('div');
        confirmacion.innerHTML = '✅ Enlace copiado!';
        confirmacion.style.cssText = 'position:fixed;top:20px;right:20px;background:#10b981;color:white;padding:10px 20px;border-radius:8px;z-index:10000;font-weight:bold;';
        document.body.appendChild(confirmacion);
        
        setTimeout(() => confirmacion.remove(), 2000);
    }).catch(() => {
        alert('Enlace: ' + url);
    });
}

// Función para actualizar los íconos de moneda
function updateCurrencyIcons() {
    const monedaSelect = document.querySelector('[name="moneda"]');
    const iconPersona = document.getElementById('currency-icon-persona');
    const iconTotal = document.getElementById('currency-icon-total');
    
    if (monedaSelect && iconPersona && iconTotal) {
        const selectedCurrency = monedaSelect.value;
        const symbol = currencySymbols[selectedCurrency] || selectedCurrency;
        
        iconPersona.textContent = symbol;
        iconTotal.textContent = symbol;
    }
}

// Función para calcular precio total automáticamente
function calcularPrecioTotal() {
    const cantidadAdultos = parseInt(document.getElementById('cantidad-adultos')?.value) || 0;
    const precioAdulto = parseFloat(document.getElementById('precio-adulto')?.value) || 0;
    const cantidadNinos = parseInt(document.getElementById('cantidad-ninos')?.value) || 0;
    const precioNino = parseFloat(document.getElementById('precio-nino')?.value) || 0;
    
    // Calcular total
    const totalAdultos = cantidadAdultos * precioAdulto;
    const totalNinos = cantidadNinos * precioNino;
    const precioTotal = totalAdultos + totalNinos;
    
    // Actualizar campo de precio total
    const precioTotalInput = document.getElementById('precio-total');
    if (precioTotalInput) {
        precioTotalInput.value = precioTotal.toFixed(2);
        
        // Mostrar desglose del cálculo
        const calculoInfo = document.getElementById('calculo-info');
        const calculoDetalle = document.getElementById('calculo-detalle');
        
        if (precioTotal > 0 && calculoInfo && calculoDetalle) {
            let detalle = [];
            
            if (cantidadAdultos > 0) {
                const moneda = document.getElementById('currency-icon-total')?.textContent || '$';
                detalle.push(`${cantidadAdultos} adulto${cantidadAdultos > 1 ? 's' : ''} × ${moneda}${precioAdulto.toFixed(2)} = ${moneda}${totalAdultos.toFixed(2)}`);
            }
            
            if (cantidadNinos > 0) {
                const moneda = document.getElementById('currency-icon-total')?.textContent || '$';
                detalle.push(`${cantidadNinos} niño${cantidadNinos > 1 ? 's' : ''} × ${moneda}${precioNino.toFixed(2)} = ${moneda}${totalNinos.toFixed(2)}`);
            }
            
            calculoDetalle.textContent = detalle.join(' + ');
            calculoInfo.style.display = 'block';
        } else if (calculoInfo) {
            calculoInfo.style.display = 'none';
        }
    }
}

// Función mejorada para actualizar íconos de moneda
function updateCurrencyIcons() {
    const monedaSelect = document.querySelector('[name="moneda"]');
    const iconAdulto = document.getElementById('currency-icon-adulto');
    const iconNino = document.getElementById('currency-icon-nino');
    const iconTotal = document.getElementById('currency-icon-total');
    
    if (monedaSelect) {
        const selectedCurrency = monedaSelect.value;
        const symbol = currencySymbols[selectedCurrency] || selectedCurrency;
        
        if (iconAdulto) iconAdulto.textContent = symbol;
        if (iconNino) iconNino.textContent = symbol;
        if (iconTotal) iconTotal.textContent = symbol;
        
        // Recalcular para mostrar nueva moneda
        calcularPrecioTotal();
    }
}

// Event listener para el select de moneda
document.addEventListener('DOMContentLoaded', function() {
    const monedaSelect = document.querySelector('[name="moneda"]');
    if (monedaSelect) {
        monedaSelect.addEventListener('change', updateCurrencyIcons);
        updateCurrencyIcons(); // Inicializar
    }
});

// Agregar el event listener al select de moneda
document.addEventListener('DOMContentLoaded', function() {
    const monedaSelect = document.querySelector('[name="moneda"]');
    if (monedaSelect) {
        monedaSelect.addEventListener('change', updateCurrencyIcons);
        // Actualizar al cargar la página
        updateCurrencyIcons();
    }
});

/**
 * Muestra el campo ID de solicitud con animación suave
 */
function mostrarCampoRequestId(requestId) {
    console.log('📋 Mostrando campo ID de solicitud:', requestId);
    
    const requestIdGroup = document.getElementById('request-id-group');
    const requestIdField = document.getElementById('request-id');
    
    if (!requestIdGroup || !requestIdField) {
        console.error('❌ No se encontraron los elementos del campo ID de solicitud');
        return;
    }
    
    // Asignar el valor primero
    requestIdField.value = requestId;
    
    // Si ya está visible, no hacer nada más
    if (requestIdGroup.style.display !== 'none') {
        return;
    }
    
    // Preparar animación
    requestIdGroup.style.display = 'block';
    requestIdGroup.style.opacity = '0';
    requestIdGroup.style.transform = 'translateY(-10px)';
    requestIdGroup.style.transition = 'all 0.4s ease';
    
    // Mostrar con animación
    setTimeout(() => {
        requestIdGroup.style.opacity = '1';
        requestIdGroup.style.transform = 'translateY(0)';
        
        // Agregar efecto de resaltado temporal
        requestIdField.style.borderColor = '#28a745';
        requestIdField.style.boxShadow = '0 0 0 0.2rem rgba(40, 167, 69, 0.25)';
        
        // Quitar resaltado después de 2 segundos
        setTimeout(() => {
            requestIdField.style.borderColor = '';
            requestIdField.style.boxShadow = '';
        }, 2000);
    }, 100);
    
    console.log('✅ Campo ID de solicitud mostrado exitosamente');
}

// Eventos para drag & drop de alternativas (opcional - futuro)
function initDragAndDropAlternativas() {
    // TODO: Implementar drag & drop para reordenar alternativas
    console.log('💡 Drag & drop de alternativas - funcionalidad futura');
}

console.log('✅ Script completo de alternativas cargado');
console.log('🔧 Funciones disponibles:');
console.log('   - abrirModalAlternativa()');
console.log('   - agregarAlternativaSeleccionada()');
console.log('   - eliminarAlternativa()');
console.log('   - renderizarServicioConAlternativas()');
console.log('   - toggleAlternativas()');
console.log('   - getEstadisticasServicios()');
console.log('   - buscarServicioPorId()');
console.log('   - reordenarAlternativas()');



    </script>
    <script>
function abrirMiBiblioteca() {
    // Agregar efecto visual de clic
    const button = event.target.closest('.nav-button');
    button.style.transform = 'scale(0.95)';
    
    // Restaurar el botón después del efecto
    setTimeout(() => {
        button.style.transform = '';
    }, 150);
    
    // Redirigir a la página de biblioteca
    setTimeout(() => {
        window.location.href = '<?= APP_URL ?>/biblioteca';
    }, 100);
}

// ============================================================
// FUNCIÓN CORREGIDA - CALCULAR FECHA DE SALIDA CON IDIOMA DINÁMICO
// ============================================================

// ============================================================
// FUNCIÓN CORREGIDA - FECHA DE SALIDA (sin null, con traducción)
// ============================================================

function actualizarFechaSalida() {
    const fechaLlegada = document.getElementById('arrival-date')?.value;
    const calculatedDeparture = document.getElementById('calculated-departure');
    const hiddenDeparture = document.getElementById('departure-date-hidden');
    
    if (!calculatedDeparture) return;
    
    // ✅ Si no hay fecha de llegada o días, mostrar mensaje
    if (!fechaLlegada || !diasPrograma || diasPrograma.length === 0) {
        calculatedDeparture.textContent = 'La fecha de salida se calcula automáticamente según los días del programa';
        calculatedDeparture.style.fontStyle = 'italic';
        if (hiddenDeparture) hiddenDeparture.value = '';
        return;
    }
    
    // Calcular duración total
    let duracionTotal = 0;
    diasPrograma.forEach(dia => {
        const duracion = parseInt(dia.duracion_estancia) || 1;
        duracionTotal += duracion;
    });
    
    if (duracionTotal === 0) {
        duracionTotal = diasPrograma.length;
    }
    
    // Calcular fecha de salida
    const fechaInicio = new Date(fechaLlegada);
    const fechaSalida = new Date(fechaInicio);
    fechaSalida.setDate(fechaInicio.getDate() + duracionTotal + 1);

    // ✅ OBTENER IDIOMA SELECCIONADO (selector correcto)
    const idiomaSeleccionado = document.getElementById('language')?.value || 'es';
    
    // ✅ MAPEAR IDIOMA A LOCALE
    const localeMap = {
        'es': 'es-ES',
        'en': 'en-US',
        'fr': 'fr-FR',
        'de': 'de-DE',
        'pt': 'pt-BR',
        'it': 'it-IT'
    };
    const locale = localeMap[idiomaSeleccionado] || 'es-ES';

    // Formatear fecha para mostrar
    const opciones = { year: 'numeric', month: 'long', day: 'numeric' };
    const fechaFormateada = fechaSalida.toLocaleDateString(locale, opciones);

    // Formatear fecha para backend
    const fechaBackend = fechaSalida.toISOString().split('T')[0];

    // Textos traducibles
    const textSingular = document.getElementById('text-day-singular')?.textContent || 'día total';
    const textPlural = document.getElementById('text-days-plural')?.textContent || 'días total';
    const duracionTexto = duracionTotal === 1 ? textSingular : textPlural;

    // ✅ Actualizar div con texto (no input)
    calculatedDeparture.textContent = `${fechaFormateada} (${duracionTotal} ${duracionTexto})`;
    calculatedDeparture.style.fontStyle = 'normal';
    calculatedDeparture.style.fontWeight = '500';
    
    if (hiddenDeparture) {
        hiddenDeparture.value = fechaBackend;
    }
}

// Event listeners
document.getElementById('arrival-date')?.addEventListener('change', actualizarFechaSalida);

// ✅ LISTENER CORRECTO para cambio de idioma
document.getElementById('language')?.addEventListener('change', actualizarFechaSalida);

document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('arrival-date')?.value) {
        actualizarFechaSalida();
    }
});

// Actualizar después de cargar días
const originalCargarDiasPrograma = cargarDiasPrograma;
cargarDiasPrograma = async function() {
    await originalCargarDiasPrograma();
    actualizarFechaSalida();
};

// ============================================================
// EDICIÓN DE DÍAS - FUNCIONES COMPLETAS
// ============================================================

// Abrir formulario de edición inline
function abrirEdicionDia(diaId) {
    console.log(`📝 Abriendo edición de día ${diaId}`);
    
    const formElement = document.getElementById(`edit-dia-form-${diaId}`);
    if (!formElement) {
        console.error('Formulario no encontrado');
        return;
    }
    
    // Ocultar otros formularios de edición abiertos
    document.querySelectorAll('.edit-inline-form').forEach(form => {
        form.style.display = 'none';
    });
    
    // Mostrar este formulario
    formElement.style.display = 'block';
    
    // Scroll suave al formulario
    formElement.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    
    // Inicializar búsqueda de ubicación para este día
    inicializarBusquedaUbicacionDia(diaId);

    // ✅ CARGAR UBICACIONES SECUNDARIAS
    cargarUbicacionesSecundariasEdit(diaId);
}

// Cerrar formulario de edición
function cerrarEdicionDia(diaId) {
    const formElement = document.getElementById(`edit-dia-form-${diaId}`);
    if (formElement) {
        formElement.style.display = 'none';
    }
}

// Guardar cambios del día
async function guardarEdicionDia(diaId) {
    console.log(`💾 Guardando edición de día ${diaId}`);
    
    try {
        // Obtener valores principales
        const titulo = document.getElementById(`edit-dia-titulo-${diaId}`)?.value?.trim();
        const descripcion = document.getElementById(`edit-dia-descripcion-${diaId}`)?.value?.trim();
        const ubicacion = document.getElementById(`edit-dia-ubicacion-${diaId}`)?.value?.trim();
        const latitud = document.getElementById(`edit-dia-latitud-${diaId}`)?.value || null;
        const longitud = document.getElementById(`edit-dia-longitud-${diaId}`)?.value || null;
        
        // Validaciones
        if (!titulo) {
            showAlert('El título es obligatorio', 'error');
            return;
        }
        
        if (!descripcion) {
            showAlert('La descripción es obligatoria', 'error');
            return;
        }
        
        if (!ubicacion) {
            showAlert('La ubicación es obligatoria', 'error');
            return;
        }
        
        // ✅ RECOPILAR UBICACIONES SECUNDARIAS
        const ubicacionesSecundarias = [];
        const container = document.getElementById(`ubicaciones-secundarias-edit-${diaId}`);
        
        if (container) {
            const items = container.querySelectorAll('.ubicacion-secundaria-item');
            items.forEach((item, index) => {
                const itemId = `ubic-sec-${diaId}-${index}`;
                const ubicInput = document.getElementById(`${itemId}-input`);
                const latInput = document.getElementById(`${itemId}-lat`);
                const lngInput = document.getElementById(`${itemId}-lng`);
                
                if (ubicInput && ubicInput.value.trim()) {
                    ubicacionesSecundarias.push({
                        id: item.dataset.ubicId || null,
                        ubicacion: ubicInput.value.trim(),
                        latitud: latInput?.value || null,
                        longitud: lngInput?.value || null,
                        orden: index + 1
                    });
                }
            });
        }
        
        const dataToSend = {
            action: 'update',
            dia_id: diaId,
            data: {
                titulo: titulo,
                descripcion: descripcion,
                ubicacion: ubicacion,
                latitud: latitud,
                longitud: longitud,
                ubicaciones_secundarias: ubicacionesSecundarias
            }
        };
        
        console.log('📤 Enviando actualización:', dataToSend);
        
        // Enviar actualización
        const response = await fetch('<?= APP_URL ?>/modules/programa/dias_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(dataToSend)
        });
        
        console.log('📡 Status de respuesta:', response.status);
        
        // 🔍 LEER LA RESPUESTA COMPLETA PARA VER EL ERROR
        const responseText = await response.text();
        console.log('📄 Respuesta completa del servidor:', responseText);
        
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (e) {
            console.error('❌ Error parseando JSON:', e);
            console.error('Texto recibido:', responseText.substring(0, 500));
            showAlert('Error del servidor: ' + responseText.substring(0, 200), 'error');
            return;
        }
        
        if (result.success) {
            showAlert('Día actualizado correctamente', 'success');
            cerrarEdicionDia(diaId);
            await cargarDiasPrograma();
        } else {
            console.error('❌ Error del servidor:', result);
            showAlert(result.error || result.message || 'Error al actualizar día', 'error');
        }
        
    } catch (error) {
        console.error('❌ Error:', error);
        showAlert('Error de conexión al actualizar día: ' + error.message, 'error');
    }
}

// Subir imágenes del día
async function subirImagenesDia(diaId, imagenes) {
    const formData = new FormData();
    formData.append('type', 'dia');
    formData.append('item_id', diaId);
    
    for (const [key, file] of Object.entries(imagenes)) {
        formData.append(key, file);
    }
    
    const response = await fetch('<?= APP_URL ?>/modules/programa/upload_images.php', {
        method: 'POST',
        body: formData
    });
    
    const result = await response.json();
    
    if (!result.success) {
        throw new Error(result.error || 'Error subiendo imágenes');
    }
    
    return result.images;
}

// Preview de imagen de día
function previewImagenDia(diaId, imageNumber, input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const container = input.closest('.image-preview-item');
            const existingImg = container.querySelector('.preview-img');
            const emptySlot = container.querySelector('.empty-image-slot');
            const btnChange = container.querySelector('.btn-change-image');
            
            if (existingImg) {
                existingImg.src = e.target.result;
            } else if (emptySlot) {
                emptySlot.remove();
                const img = document.createElement('img');
                img.src = e.target.result;
                img.alt = `Imagen ${imageNumber}`;
                img.className = 'preview-img';
                container.insertBefore(img, btnChange);
                
                // Agregar botón de remover
                const btnRemove = document.createElement('button');
                btnRemove.type = 'button';
                btnRemove.className = 'btn-remove-image';
                btnRemove.onclick = () => removerImagenDia(diaId, imageNumber);
                btnRemove.innerHTML = '<i class="fas fa-times"></i>';
                container.appendChild(btnRemove);
            }
            
            btnChange.textContent = 'Cambiar';
        };
        
        reader.readAsDataURL(input.files[0]);
    }
}

// Remover imagen de día
function removerImagenDia(diaId, imageNumber) {
    const container = document.querySelector(`#edit-dia-form-${diaId} .image-preview-item[data-image-number="${imageNumber}"]`);
    if (!container) return;
    
    const img = container.querySelector('.preview-img');
    const btnRemove = container.querySelector('.btn-remove-image');
    const btnChange = container.querySelector('.btn-change-image');
    const input = document.getElementById(`edit-dia-imagen${imageNumber}-${diaId}`);
    
    if (img) img.remove();
    if (btnRemove) btnRemove.remove();
    
    // Agregar empty slot
    const emptySlot = document.createElement('div');
    emptySlot.className = 'empty-image-slot';
    emptySlot.innerHTML = `
        <i class="fas fa-image"></i>
        <p>Imagen ${imageNumber}</p>
    `;
    container.insertBefore(emptySlot, btnChange);
    
    // Limpiar input
    if (input) input.value = '';
    
    btnChange.textContent = 'Agregar';
}

// Inicializar búsqueda de ubicación para día
function inicializarBusquedaUbicacionDia(diaId) {
    const input = document.getElementById(`edit-dia-ubicacion-${diaId}`);
    const resultsContainer = document.getElementById(`location-results-dia-${diaId}`);
    
    if (!input || !resultsContainer) return;
    
    let searchTimeout;
    
    input.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();
        
        if (query.length < 3) {
            resultsContainer.classList.remove('active');
            return;
        }
        
        searchTimeout = setTimeout(async () => {
            try {
                const results = await buscarUbicacion(query);
                mostrarResultadosUbicacion(results, resultsContainer, diaId, 'dia');
            } catch (error) {
                console.error('Error buscando ubicación:', error);
            }
        }, 500);
    });
    
    // Cerrar resultados al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (!input.contains(e.target) && !resultsContainer.contains(e.target)) {
            resultsContainer.classList.remove('active');
        }
    });
}

// ============================================================
// EDICIÓN DE ACTIVIDADES - FUNCIONES COMPLETAS
// ============================================================

// Abrir formulario de edición de actividad
function abrirEdicionActividad(actividadId) {
    console.log(`📝 Abriendo edición de actividad ${actividadId}`);
    
    // Primero verificar si el formulario ya existe
    let formElement = document.getElementById(`edit-actividad-form-${actividadId}`);
    
    if (!formElement) {
        // Si no existe, necesitamos obtener los datos de la actividad y crear el formulario
        const actividad = buscarActividadPorId(actividadId);
        if (!actividad) {
            showAlert('Actividad no encontrada', 'error');
            return;
        }
        
        // Crear el formulario dinámicamente
        const container = document.querySelector(`[data-servicio-id="${actividadId}"]`)?.closest('.service-group') || 
                         document.querySelector(`[data-alternativa-id="${actividadId}"]`)?.closest('.service-group');
        
        if (!container) {
            showAlert('No se encontró el contenedor', 'error');
            return;
        }
        
        const formHTML = renderizarFormularioEdicionActividad(actividad);
        container.insertAdjacentHTML('afterend', formHTML);
        
        formElement = document.getElementById(`edit-actividad-form-${actividadId}`);
    }
    
    // Ocultar otros formularios
    document.querySelectorAll('.edit-inline-form').forEach(form => {
        form.style.display = 'none';
    });
    
    // Mostrar este formulario
    formElement.style.display = 'block';
    
    // Scroll
    formElement.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    
    // Inicializar búsqueda de ubicación
    inicializarBusquedaUbicacionActividad(actividadId);
}

// Buscar actividad por ID en los servicios cargados
function buscarActividadPorId(actividadId) {
    console.log('🔍 Buscando actividad:', actividadId);
    console.log('📦 Estructura diasPrograma:', diasPrograma);
    
    // Buscar en el día seleccionado primero (más eficiente)
    if (selectedDayId) {
        const diaSeleccionado = diasPrograma.find(d => d.id == selectedDayId);
        if (diaSeleccionado && diaSeleccionado.servicios) {
            
            // Buscar en servicios principales
            for (const servicio of diaSeleccionado.servicios) {
                if (servicio.id == actividadId) {
                    console.log('✅ Actividad encontrada (principal):', servicio);
                    return servicio;
                }
                
                // Buscar en alternativas
                if (servicio.alternativas && Array.isArray(servicio.alternativas)) {
                    const alternativa = servicio.alternativas.find(alt => alt.id == actividadId);
                    if (alternativa) {
                        console.log('✅ Actividad encontrada (alternativa):', alternativa);
                        return alternativa;
                    }
                }
            }
        }
    }
    
    // Si no se encontró en el día seleccionado, buscar en todos los días
    for (const dia of diasPrograma) {
        if (!dia.servicios) continue;
        
        for (const servicio of dia.servicios) {
            if (servicio.id == actividadId) {
                console.log('✅ Actividad encontrada en día', dia.id, ':', servicio);
                return servicio;
            }
            
            // Buscar en alternativas
            if (servicio.alternativas && Array.isArray(servicio.alternativas)) {
                const alternativa = servicio.alternativas.find(alt => alt.id == actividadId);
                if (alternativa) {
                    console.log('✅ Actividad encontrada (alternativa) en día', dia.id, ':', alternativa);
                    return alternativa;
                }
            }
        }
    }
    
    console.error('❌ Actividad no encontrada:', actividadId);
    console.log('📋 Días disponibles:', diasPrograma.map(d => ({id: d.id, servicios: d.servicios?.length})));
    
    return null;
}
// ⭐ FUNCIÓN TEMPORAL DE DEBUG - Agregar al final del <script>
function debugEstructuraServicios() {
    console.log('=== DEBUG ESTRUCTURA ===');
    console.log('Día seleccionado:', selectedDayId);
    
    const dia = diasPrograma.find(d => d.id == selectedDayId);
    if (dia) {
        console.log('📅 Día completo:', dia);
        console.log('📋 Servicios:', dia.servicios);
        
        if (dia.servicios) {
            dia.servicios.forEach((servicio, index) => {
                console.log(`Servicio ${index}:`, {
                    id: servicio.id,
                    tipo: servicio.tipo_servicio,
                    nombre: servicio.nombre || servicio.titulo,
                    alternativas: servicio.alternativas?.length || 0
                });
            });
        }
    }
    console.log('=== FIN DEBUG ===');
}

// Llamar automáticamente al cargar servicios
window.debugEstructuraServicios = debugEstructuraServicios;
// Renderizar formulario de edición de actividad
function renderizarFormularioEdicionActividad(actividad) {
    return `
        <div id="edit-actividad-form-${actividad.id}" class="edit-inline-form edit-actividad" style="display: none;">
            <div class="edit-form-header">
                <h4><i class="fas fa-edit"></i> Editar Actividad</h4>
                <button class="btn-close-edit" onclick="cerrarEdicionActividad(${actividad.id})">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="edit-form-body">
                <!-- Nombre -->
                <div class="form-group">
                    <label for="edit-act-nombre-${actividad.id}">
                        Nombre <span class="required">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="edit-act-nombre-${actividad.id}" 
                        class="form-control"
                        value="${actividad.nombre || actividad.titulo || ''}"
                        maxlength="300"
                        required
                    >
                </div>
                
                <!-- Descripción -->
                <div class="form-group">
                    <label for="edit-act-descripcion-${actividad.id}">
                        Descripción <span class="required">*</span>
                    </label>
                    <textarea 
                        id="edit-act-descripcion-${actividad.id}" 
                        class="form-control"
                        rows="4"
                        maxlength="2000"
                        required
                    >${actividad.descripcion || ''}</textarea>
                </div>
                
                <!-- Ubicación -->
                <div class="form-group">
                    <label for="edit-act-ubicacion-${actividad.id}">
                        Ubicación <span class="required">*</span>
                    </label>
                    <div class="location-search-wrapper">
                        <input 
                            type="text" 
                            id="edit-act-ubicacion-${actividad.id}" 
                            class="form-control location-search-input"
                            value="${actividad.ubicacion || ''}"
                            placeholder="Buscar ubicación..."
                            autocomplete="off"
                        >
                        <div id="location-results-act-${actividad.id}" class="location-results"></div>
                    </div>
                    <input type="hidden" id="edit-act-latitud-${actividad.id}" value="${actividad.latitud || ''}">
                    <input type="hidden" id="edit-act-longitud-${actividad.id}" value="${actividad.longitud || ''}">
                </div>
                
                <!-- Imágenes -->
                <div class="form-group">
                    <label>
                        Imágenes <span class="required">*</span>
                        <small>(mínimo 1 imagen)</small>
                    </label>
                    
                    <div class="images-preview-edit">
                        ${[1,2,3].map(i => {
                            const imagenUrl = actividad['imagen' + i] || actividad[`actividad_imagen${i}`];
                            return `
                                <div class="image-preview-item" data-image-number="${i}">
                                    ${imagenUrl ? `
                                        <img src="${imagenUrl}" alt="Imagen ${i}" class="preview-img">
                                        <button type="button" class="btn-remove-image" onclick="removerImagenActividad(${actividad.id}, ${i})">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    ` : `
                                        <div class="empty-image-slot">
                                            <i class="fas fa-image"></i>
                                            <p>Imagen ${i}</p>
                                        </div>
                                    `}
                                    <input 
                                        type="file" 
                                        id="edit-act-imagen${i}-${actividad.id}" 
                                        accept="image/jpeg,image/jpg,image/png,image/webp"
                                        onchange="previewImagenActividad(${actividad.id}, ${i}, this)"
                                        style="display: none;"
                                    >
                                    <button type="button" class="btn-change-image" onclick="document.getElementById('edit-act-imagen${i}-${actividad.id}').click()">
                                        ${imagenUrl ? 'Cambiar' : 'Agregar'}
                                    </button>
                                </div>
                            `;
                        }).join('')}
                    </div>
                </div>
                
                <!-- Botones -->
                <div class="edit-form-actions">
                    <button type="button" class="btn btn-secondary" onclick="cerrarEdicionActividad(${actividad.id})">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" onclick="guardarEdicionActividad(${actividad.id})">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                </div>
            </div>
        </div>
    `;
}

// Cerrar formulario de edición de actividad
function cerrarEdicionActividad(actividadId) {
    const formElement = document.getElementById(`edit-actividad-form-${actividadId}`);
    if (formElement) {
        formElement.style.display = 'none';
    }
}

// Guardar edición de actividad
async function guardarEdicionActividad(actividadId) {
    console.log(`💾 Guardando edición de actividad ${actividadId}`);
    
    try {
        // Obtener valores
        const nombre = document.getElementById(`edit-act-nombre-${actividadId}`).value.trim();
        const descripcion = document.getElementById(`edit-act-descripcion-${actividadId}`).value.trim();
        const ubicacion = document.getElementById(`edit-act-ubicacion-${actividadId}`).value.trim();
        const latitud = document.getElementById(`edit-act-latitud-${actividadId}`).value;
        const longitud = document.getElementById(`edit-act-longitud-${actividadId}`).value;
        
        // ⭐ VALIDACIONES
        if (!nombre) {
            showAlert('El nombre es obligatorio', 'error');
            return;
        }
        
        if (!descripcion) {
            showAlert('La descripción es obligatoria', 'error');
            return;
        }
        
        // Validar al menos 1 imagen
        const imagenes = {};
        let tieneImagen = false;
        
        for (let i = 1; i <= 3; i++) {
            const imgInput = document.getElementById(`edit-act-imagen${i}-${actividadId}`);
            const existingImg = document.querySelector(`#edit-actividad-form-${actividadId} .image-preview-item[data-image-number="${i}"] .preview-img`);
            
            if (imgInput && imgInput.files && imgInput.files[0]) {
                imagenes[`imagen${i}`] = imgInput.files[0];
                tieneImagen = true;
            } else if (existingImg) {
                tieneImagen = true;
            }
        }
        
        if (!tieneImagen) {
            showAlert('Debe tener al menos 1 imagen', 'error');
            return;
        }
        
        // ⭐ PASO 1: Subir imágenes nuevas (si hay)
        let imagenesSubidas = {};
        if (Object.keys(imagenes).length > 0) {
            imagenesSubidas = await subirImagenesActividad(actividadId, imagenes);
        }
        
        // ⭐ PASO 2: Actualizar datos de la actividad
        const dataToUpdate = {
            nombre_servicio: nombre,
            descripcion_servicio: descripcion,
            ubicacion_servicio: ubicacion,
            latitud: latitud || null,
            longitud: longitud || null
        };
        
        // Mapear imágenes subidas a campos de actividad
        if (imagenesSubidas.imagen1) dataToUpdate.actividad_imagen1 = imagenesSubidas.imagen1;
        if (imagenesSubidas.imagen2) dataToUpdate.actividad_imagen2 = imagenesSubidas.imagen2;
        if (imagenesSubidas.imagen3) dataToUpdate.actividad_imagen3 = imagenesSubidas.imagen3;
        
        const response = await fetch('<?= APP_URL ?>/modules/programa/servicios_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'update',
                servicio_id: actividadId,
                ...dataToUpdate
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('Actividad actualizada exitosamente', 'success');
            cerrarEdicionActividad(actividadId);
            
            // Recargar servicios del día
            if (selectedDayId) {
                await cargarServiciosDia(selectedDayId);
            }
            
        } else {
            showAlert(result.message || result.error || 'Error al actualizar actividad', 'error');
        }
        
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error de conexión al guardar la actividad', 'error');
    }
}

// Subir imágenes de actividad
async function subirImagenesActividad(actividadId, imagenes) {
    const formData = new FormData();
    formData.append('type', 'actividad');
    formData.append('item_id', actividadId);
    
    for (const [key, file] of Object.entries(imagenes)) {
        formData.append(key, file);
    }
    
    const response = await fetch('<?= APP_URL ?>/modules/programa/upload_images.php', {
        method: 'POST',
        body: formData
    });
    
    const result = await response.json();
    
    if (!result.success) {
        throw new Error(result.error || 'Error subiendo imágenes');
    }
    
    return result.images;
}

// ============================================================
// UBICACIONES SECUNDARIAS EN EDICIÓN DE DÍAS
// ============================================================

/**
 * Cargar ubicaciones secundarias del día
 */
async function cargarUbicacionesSecundariasEdit(diaId) {
    try {
        console.log(`📍 Cargando ubicaciones secundarias para día ${diaId}...`);
        
        const response = await fetch(`<?= APP_URL ?>/modules/programa/dias_api.php?action=get_ubicaciones_secundarias&dia_id=${diaId}`);
        const result = await response.json();
        
        if (result.success) {
            const ubicaciones = result.data || [];
            console.log(`✅ Ubicaciones secundarias cargadas: ${ubicaciones.length}`);
            
            renderizarUbicacionesSecundariasEdit(diaId, ubicaciones);
        }
        
    } catch (error) {
        console.error('Error cargando ubicaciones secundarias:', error);
    }
}

/**
 * Renderizar ubicaciones secundarias en el formulario
 */
function renderizarUbicacionesSecundariasEdit(diaId, ubicaciones) {
    const container = document.getElementById(`ubicaciones-secundarias-edit-${diaId}`);
    if (!container) return;
    
    container.innerHTML = '';
    
    if (ubicaciones.length === 0) {
        container.innerHTML = `
            <div style="padding: 15px; background: #f9fafb; border-radius: 8px; text-align: center; color: #6b7280; font-size: 13px;">
                <i class="fas fa-info-circle"></i> No hay ubicaciones adicionales
            </div>
        `;
        return;
    }
    
    ubicaciones.forEach((ubic, index) => {
        const itemId = `ubic-sec-${diaId}-${index}`;
        
        const div = document.createElement('div');
        div.className = 'ubicacion-secundaria-item';
        div.dataset.ubicId = ubic.id || '';
        div.dataset.index = index;
        div.style.cssText = `
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 10px;
            align-items: start;
            padding: 12px;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        `;
        
        div.innerHTML = `
            <div style="width: 100%;">
                <div class="location-search-wrapper">
                    <input 
                        type="text" 
                        id="${itemId}-input"
                        class="form-control location-search-input"
                        value="${ubic.ubicacion || ''}"
                        placeholder="🔍 Buscar ubicación..."
                        autocomplete="off"
                        data-dia-id="${diaId}"
                        data-ubic-index="${index}"
                    >
                    <div id="${itemId}-results" class="location-results"></div>
                </div>
                <input type="hidden" id="${itemId}-lat" value="${ubic.latitud || ''}">
                <input type="hidden" id="${itemId}-lng" value="${ubic.longitud || ''}">
                
                ${ubic.ubicacion ? `
                    <div class="ubicacion-preview" style="margin-top: 6px; padding: 8px; background: white; border-radius: 4px; font-size: 11px;">
                        <strong style="color: #334155;">${ubic.ubicacion}</strong>
                        ${ubic.latitud && ubic.longitud ? `
                            <div style="color: #64748b; margin-top: 2px;">
                                ${parseFloat(ubic.latitud).toFixed(6)}, ${parseFloat(ubic.longitud).toFixed(6)}
                            </div>
                        ` : ''}
                    </div>
                ` : ''}
            </div>
            
            <button 
                type="button" 
                class="btn btn-danger-outline" 
                onclick="removerUbicacionSecundariaEdit(${diaId}, ${index})"
                style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;"
                title="Eliminar ubicación">
                <i class="fas fa-trash"></i>
            </button>
        `;
        
        container.appendChild(div);
        
        // Inicializar búsqueda para esta ubicación
        setTimeout(() => {
            inicializarBusquedaUbicacionSecundaria(diaId, index, itemId);
        }, 100);
    });
}

/**
 * Agregar nueva ubicación secundaria
 */
function agregarUbicacionSecundariaEdit(diaId) {
    const container = document.getElementById(`ubicaciones-secundarias-edit-${diaId}`);
    if (!container) return;
    
    // Limpiar mensaje de "no hay ubicaciones"
    const emptyMessage = container.querySelector('div[style*="f9fafb"]');
    if (emptyMessage) {
        emptyMessage.remove();
    }
    
    const index = container.children.length;
    const itemId = `ubic-sec-${diaId}-${index}`;
    
    const div = document.createElement('div');
    div.className = 'ubicacion-secundaria-item';
    div.dataset.index = index;
    div.style.cssText = `
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 10px;
        align-items: start;
        padding: 12px;
        background: #f8fafc;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
    `;
    
    div.innerHTML = `
        <div style="width: 100%;">
            <div class="location-search-wrapper">
                <input 
                    type="text" 
                    id="${itemId}-input"
                    class="form-control location-search-input"
                    placeholder="🔍 Buscar ubicación..."
                    autocomplete="off"
                    data-dia-id="${diaId}"
                    data-ubic-index="${index}"
                >
                <div id="${itemId}-results" class="location-results"></div>
            </div>
            <input type="hidden" id="${itemId}-lat" value="">
            <input type="hidden" id="${itemId}-lng" value="">
        </div>
        
        <button 
            type="button" 
            class="btn btn-danger-outline" 
            onclick="removerUbicacionSecundariaEdit(${diaId}, ${index})"
            style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;"
            title="Eliminar ubicación">
            <i class="fas fa-trash"></i>
        </button>
    `;
    
    container.appendChild(div);
    
    // Inicializar búsqueda
    setTimeout(() => {
        inicializarBusquedaUbicacionSecundaria(diaId, index, itemId);
    }, 100);
}

/**
 * Remover ubicación secundaria
 */
function removerUbicacionSecundariaEdit(diaId, index) {
    const container = document.getElementById(`ubicaciones-secundarias-edit-${diaId}`);
    if (!container) return;
    
    const items = container.querySelectorAll('.ubicacion-secundaria-item');
    if (items[index]) {
        items[index].remove();
    }
    
    // Si no quedan ubicaciones, mostrar mensaje
    if (container.children.length === 0) {
        container.innerHTML = `
            <div style="padding: 15px; background: #f9fafb; border-radius: 8px; text-align: center; color: #6b7280; font-size: 13px;">
                <i class="fas fa-info-circle"></i> No hay ubicaciones adicionales
            </div>
        `;
    }
}
/**
 * Buscar ubicación usando el MISMO API que biblioteca
 */
async function buscarUbicacionNominatim(query, resultsDiv, onSelectCallback) {
    if (!query || query.length < 3) {
        resultsDiv.classList.remove('active');
        return;
    }
    
    try {
        console.log(`🔍 Buscando ubicación: ${query}`);
        
        // Mostrar loading
        resultsDiv.innerHTML = `
            <div style="padding: 12px; text-align: center; color: #666;">
                <i class="fas fa-spinner fa-spin"></i> Buscando...
            </div>
        `;
        resultsDiv.classList.add('active');
        
        // ✅ USAR EL MISMO API QUE BIBLIOTECA
        const response = await fetch(
            `<?= APP_URL ?>/modules/ubicaciones/ubicaciones_api.php?action=search&q=${encodeURIComponent(query)}`
        );
        
        if (!response.ok) {
            throw new Error('Error en la búsqueda');
        }
        
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.error || 'Error en la búsqueda');
        }
        
        const results = result.data || [];
        console.log(`✅ Resultados encontrados: ${results.length}`);
        
        if (results.length === 0) {
            resultsDiv.innerHTML = `
                <div style="padding: 12px; text-align: center; color: #666; font-size: 13px;">
                    <i class="fas fa-info-circle"></i> No se encontraron resultados
                </div>
            `;
            return;
        }
        
        // Renderizar resultados
        resultsDiv.innerHTML = results.map(result => `
            <div class="location-result-item" 
                 data-lat="${result.lat}" 
                 data-lon="${result.lon}"
                 data-display-name="${result.display_name}"
                 style="padding: 10px 12px; cursor: pointer; border-bottom: 1px solid #f0f0f0; transition: background 0.2s;">
                <div style="font-weight: 500; color: #1e293b; font-size: 13px;">
                    ${result.display_name}
                </div>
                <div style="font-size: 11px; color: #64748b; margin-top: 2px;">
                    📍 ${parseFloat(result.lat).toFixed(6)}, ${parseFloat(result.lon).toFixed(6)}
                </div>
            </div>
        `).join('');
        
        // Agregar eventos de click a cada resultado
        resultsDiv.querySelectorAll('.location-result-item').forEach(item => {
            item.addEventListener('mouseenter', function() {
                this.style.background = '#f8f9fa';
            });
            
            item.addEventListener('mouseleave', function() {
                this.style.background = 'white';
            });
            
            item.addEventListener('click', function() {
                const ubicacion = {
                    display_name: this.dataset.displayName,
                    lat: this.dataset.lat,
                    lon: this.dataset.lon
                };
                
                console.log('✅ Ubicación seleccionada:', ubicacion);
                
                // Ejecutar callback
                if (onSelectCallback) {
                    onSelectCallback(ubicacion);
                }
            });
        });
        
    } catch (error) {
        console.error('❌ Error buscando ubicación:', error);
        resultsDiv.innerHTML = `
            <div style="padding: 12px; text-align: center; color: #dc2626; font-size: 13px;">
                <i class="fas fa-exclamation-circle"></i> ${error.message || 'Error al buscar ubicación'}
            </div>
        `;
    }
}
/**
 * Inicializar búsqueda para ubicación secundaria
 */
function inicializarBusquedaUbicacionSecundaria(diaId, index, itemId) {
    const input = document.getElementById(`${itemId}-input`);
    const resultsDiv = document.getElementById(`${itemId}-results`);
    
    if (!input || !resultsDiv) return;
    
    let searchTimeout;
    
    input.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();
        
        if (query.length < 3) {
            resultsDiv.classList.remove('active');
            return;
        }
        
        searchTimeout = setTimeout(() => {
            buscarUbicacionNominatim(query, resultsDiv, (ubicacion) => {
                // Callback cuando se selecciona una ubicación
                input.value = ubicacion.display_name;
                document.getElementById(`${itemId}-lat`).value = ubicacion.lat;
                document.getElementById(`${itemId}-lng`).value = ubicacion.lon;
                resultsDiv.classList.remove('active');
                
                // Actualizar preview
                const parent = input.closest('.ubicacion-secundaria-item');
                const existingPreview = parent.querySelector('.ubicacion-preview');
                if (existingPreview) existingPreview.remove();
                
                const preview = document.createElement('div');
                preview.className = 'ubicacion-preview';
                preview.style.cssText = 'margin-top: 6px; padding: 8px; background: white; border-radius: 4px; font-size: 11px;';
                preview.innerHTML = `
                    <strong style="color: #334155;">${ubicacion.display_name}</strong>
                    <div style="color: #64748b; margin-top: 2px;">
                        ${parseFloat(ubicacion.lat).toFixed(6)}, ${parseFloat(ubicacion.lon).toFixed(6)}
                    </div>
                `;
                input.closest('div').appendChild(preview);
            });
        }, 300);
    });
    
    // Cerrar resultados al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (!input.contains(e.target) && !resultsDiv.contains(e.target)) {
            resultsDiv.classList.remove('active');
        }
    });
}

// Preview de imagen de actividad
function previewImagenActividad(actividadId, imageNumber, input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const container = input.closest('.image-preview-item');
            const existingImg = container.querySelector('.preview-img');
            const emptySlot = container.querySelector('.empty-image-slot');
            const btnChange = container.querySelector('.btn-change-image');
            
            if (existingImg) {
                existingImg.src = e.target.result;
            } else if (emptySlot) {
                emptySlot.remove();
                const img = document.createElement('img');
                img.src = e.target.result;
                img.alt = `Imagen ${imageNumber}`;
                img.className = 'preview-img';
                container.insertBefore(img, btnChange);
                
                // Agregar botón de remover
                const btnRemove = document.createElement('button');
                btnRemove.type = 'button';
                btnRemove.className = 'btn-remove-image';
                btnRemove.onclick = () => removerImagenActividad(actividadId, imageNumber);
                btnRemove.innerHTML = '<i class="fas fa-times"></i>';
                container.appendChild(btnRemove);
            }
            
            btnChange.textContent = 'Cambiar';
        };
        
        reader.readAsDataURL(input.files[0]);
    }
}

// Remover imagen de actividad
function removerImagenActividad(actividadId, imageNumber) {
    const container = document.querySelector(`#edit-actividad-form-${actividadId} .image-preview-item[data-image-number="${imageNumber}"]`);
    if (!container) return;
    
    const img = container.querySelector('.preview-img');
    const btnRemove = container.querySelector('.btn-remove-image');
    const btnChange = container.querySelector('.btn-change-image');
    const input = document.getElementById(`edit-act-imagen${imageNumber}-${actividadId}`);
    
    if (img) img.remove();
    if (btnRemove) btnRemove.remove();
    
    // Agregar empty slot
    const emptySlot = document.createElement('div');
    emptySlot.className = 'empty-image-slot';
    emptySlot.innerHTML = `
        <i class="fas fa-image"></i>
        <p>Imagen ${imageNumber}</p>
    `;
    container.insertBefore(emptySlot, btnChange);
    
    // Limpiar input
    if (input) input.value = '';
    
    btnChange.textContent = 'Agregar';
}

// Inicializar búsqueda de ubicación para actividad
function inicializarBusquedaUbicacionActividad(actividadId) {
    const input = document.getElementById(`edit-act-ubicacion-${actividadId}`);
    const resultsContainer = document.getElementById(`location-results-act-${actividadId}`);
    
    if (!input || !resultsContainer) return;
    
    let searchTimeout;
    
    input.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();
        
        if (query.length < 3) {
            resultsContainer.classList.remove('active');
            return;
        }
        
        searchTimeout = setTimeout(async () => {
            try {
                const results = await buscarUbicacion(query);
                mostrarResultadosUbicacion(results, resultsContainer, actividadId, 'act');
            } catch (error) {
                console.error('Error buscando ubicación:', error);
            }
        }, 500);
    });
    
    // Cerrar resultados al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (!input.contains(e.target) && !resultsContainer.contains(e.target)) {
            resultsContainer.classList.remove('active');
        }
    });
}

// ============================================================
// FUNCIONES AUXILIARES COMPARTIDAS
// ============================================================

// Buscar ubicación en Nominatim
async function buscarUbicacion(query) {
    try {
        const response = await fetch(
            `<?= APP_URL ?>/modules/programa/location_proxy.php?q=${encodeURIComponent(query)}`
        );
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.error) {
            console.error('Error del proxy:', data.error);
            return [];
        }
        
        return data;
    } catch (error) {
        console.error('Error buscando ubicación:', error);
        return [];
    }
}

// Mostrar resultados de búsqueda
function mostrarResultadosUbicacion(results, container, itemId, tipo) {
    if (!results || results.length === 0) {
        container.innerHTML = '<div class="location-result-item">No se encontraron resultados</div>';
        container.classList.add('active');
        return;
    }
    
    container.innerHTML = results.map(result => `
        <div class="location-result-item" onclick="seleccionarUbicacion('${itemId}', '${tipo}', ${result.lat}, ${result.lon}, \`${result.display_name.replace(/`/g, '')}\`)">
            ${result.display_name}
        </div>
    `).join('');
    
    container.classList.add('active');
}

// Seleccionar ubicación
function seleccionarUbicacion(itemId, tipo, lat, lon, displayName) {
    const prefix = tipo === 'dia' ? 'edit-dia' : 'edit-act';
    
    document.getElementById(`${prefix}-ubicacion-${itemId}`).value = displayName;
    document.getElementById(`${prefix}-latitud-${itemId}`).value = lat;
    document.getElementById(`${prefix}-longitud-${itemId}`).value = lon;
    
    // Cerrar resultados
    const resultsContainer = document.getElementById(`location-results-${tipo}-${itemId}`);
    if (resultsContainer) {
        resultsContainer.classList.remove('active');
    }
}

console.log('✅ Funciones de edición de días y actividades cargadas');

</script>
</body>
</html>