<?php
// =====================================
// ARCHIVO: pages/superadmin_agencia_detalle.php - Vista de Detalles de Agencia (GRUPO 2)
// =====================================

require_once 'config/app.php';
require_once 'config/config_functions.php';

// Inicializar aplicación
App::init();

// Verificar que el usuario esté logueado y sea superadmin
App::requireRole('superadmin');

// Obtener datos del usuario
$user = App::getUser();
$companyName = App::getCompanyName();

// Verificar que venga el ID de la agencia
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: ' . APP_URL . '/superadmin/agencias');
    exit;
}

$agenciaId = intval($_GET['id']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles de Agencia - <?= htmlspecialchars($companyName) ?></title>
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            min-height: 100vh;
        }
        
        /* Header */
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header-title {
            font-size: 24px;
            font-weight: 700;
        }
        
        .header-subtitle {
            font-size: 14px;
            opacity: 0.9;
            margin-top: 5px;
        }
        
        .btn-back {
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-back:hover {
            background: rgba(255,255,255,0.3);
        }
        
        /* Main Content */
        .main-container {
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 40px;
        }
        
        /* Loading */
        .loading {
            text-align: center;
            padding: 60px 20px;
            color: #94a3b8;
        }
        
        .loading i {
            font-size: 48px;
            margin-bottom: 20px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Info Cards Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .info-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .info-card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f1f5f9;
        }
        
        .info-card-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .icon-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .icon-success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .icon-warning {
            background: #fef3c7;
            color: #92400e;
        }
        
        .icon-info {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .info-card-title {
            font-size: 16px;
            color: #475569;
            font-weight: 600;
        }
        
        .info-card-body {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .info-label {
            font-size: 13px;
            color: #64748b;
        }
        
        .info-value {
            font-size: 14px;
            color: #1e293b;
            font-weight: 600;
        }
        
        .info-value-large {
            font-size: 32px;
            color: #1e293b;
            font-weight: 700;
        }
        
        /* Badge */
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-activa {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-inactiva {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .badge-suspendida {
            background: #fef3c7;
            color: #92400e;
        }
        
        /* Section */
        .section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f1f5f9;
        }
        
        .section-title {
            font-size: 20px;
            color: #1e293b;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* Timeline */
        .timeline {
            position: relative;
            padding-left: 40px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e2e8f0;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .timeline-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .timeline-icon {
            position: absolute;
            left: -32px;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            border: 3px solid white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .timeline-icon-creacion {
            background: #10b981;
            color: white;
        }
        
        .timeline-icon-edicion {
            background: #3b82f6;
            color: white;
        }
        
        .timeline-icon-renovacion {
            background: #8b5cf6;
            color: white;
        }
        
        .timeline-icon-suspension {
            background: #ef4444;
            color: white;
        }
        
        .timeline-icon-activacion {
            background: #10b981;
            color: white;
        }
        
        .timeline-icon-cambio {
            background: #f59e0b;
            color: white;
        }
        
        .timeline-content {
            margin-left: 15px;
        }
        
        .timeline-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
        }
        
        .timeline-title {
            font-size: 15px;
            font-weight: 600;
            color: #1e293b;
        }
        
        .timeline-date {
            font-size: 12px;
            color: #64748b;
        }
        
        .timeline-description {
            font-size: 14px;
            color: #475569;
            margin-bottom: 10px;
        }
        
        .timeline-user {
            font-size: 13px;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .empty-state i {
            font-size: 48px;
            color: #cbd5e1;
            margin-bottom: 15px;
        }
        
        .empty-state p {
            color: #64748b;
            font-size: 14px;
        }
        
        /* Alert */
        .alert {
            padding: 16px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .timeline {
                padding-left: 30px;
            }
            
            .timeline-icon {
                left: -27px;
                width: 26px;
                height: 26px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <div>
                <div class="header-title">
                    <i class="fas fa-building"></i> Detalles de Agencia
                </div>
                <div class="header-subtitle"><?= htmlspecialchars($companyName) ?></div>
            </div>
            <div>
                <a href="<?= APP_URL ?>/superadmin/agencias" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Volver a Agencias
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-container">
        <!-- Loading -->
        <div id="loading-container" class="loading">
            <i class="fas fa-spinner"></i>
            <p>Cargando información de la agencia...</p>
        </div>
        
        <!-- Error Container -->
        <div id="error-container" style="display: none;"></div>
        
        <!-- Content Container -->
        <div id="content-container" style="display: none;">
            <!-- Info Cards Grid -->
            <div class="info-grid">
                <!-- Card: Información General -->
                <div class="info-card">
                    <div class="info-card-header">
                        <div class="info-card-icon icon-primary">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="info-card-title">Información General</div>
                    </div>
                    <div class="info-card-body">
                        <div class="info-item">
                            <span class="info-label">Nombre:</span>
                            <span class="info-value" id="info-nombre">-</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">NIT:</span>
                            <span class="info-value" id="info-nit">-</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Estado:</span>
                            <span id="info-estado-badge"></span>
                        </div>
                    </div>
                </div>
                
                <!-- Card: Suscripción -->
                <div class="info-card">
                    <div class="info-card-header">
                        <div class="info-card-icon icon-warning">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="info-card-title">Suscripción</div>
                    </div>
                    <div class="info-card-body">
                        <div class="info-item">
                            <span class="info-label">Inicio:</span>
                            <span class="info-value" id="info-fecha-inicio">-</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Fin:</span>
                            <span class="info-value" id="info-fecha-fin">-</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Días Restantes:</span>
                            <span class="info-value" id="info-dias-restantes" style="font-size: 24px;">-</span>
                        </div>
                    </div>
                </div>
                
                <!-- Card: Usuarios -->
                <div class="info-card">
                    <div class="info-card-header">
                        <div class="info-card-icon icon-info">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="info-card-title">Usuarios</div>
                    </div>
                    <div class="info-card-body">
                        <div style="text-align: center; margin: 20px 0;">
                            <div class="info-value-large" id="info-total-usuarios">0</div>
                            <div class="info-label">Usuarios Activos</div>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Límite:</span>
                            <span class="info-value" id="info-max-usuarios">-</span>
                        </div>
                        <div style="margin-top: 20px;">
                            <button onclick="goToUsuarios()" style="width: 100%; padding: 12px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.3s;">
                                <i class="fas fa-users-cog"></i>
                                Gestionar Usuarios
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            
            
            <!-- Información de Contacto -->
            <div class="section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-address-card"></i> Información de Contacto
                    </h2>
                </div>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label"><i class="fas fa-envelope"></i> Email:</span>
                        <span class="info-value" id="info-email">-</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label"><i class="fas fa-phone"></i> Teléfono:</span>
                        <span class="info-value" id="info-telefono">-</span>
                    </div>
                    <div class="info-item" style="grid-column: 1 / -1;">
                        <span class="info-label"><i class="fas fa-map-marker-alt"></i> Dirección:</span>
                        <span class="info-value" id="info-direccion">-</span>
                    </div>
                    <div class="info-item" style="grid-column: 1 / -1;">
                        <span class="info-label"><i class="fas fa-info-circle"></i> Descripción:</span>
                        <span class="info-value" id="info-descripcion">-</span>
                    </div>
                </div>
            </div>
            
            <!-- Historial de Cambios -->
            <div class="section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-history"></i> Historial de Cambios
                    </h2>
                </div>
                
                <div id="historial-loading" class="loading" style="padding: 40px;">
                    <i class="fas fa-spinner"></i>
                    <p>Cargando historial...</p>
                </div>
                
                <div id="historial-timeline" class="timeline" style="display: none;"></div>
                
                <div id="historial-empty" class="empty-state" style="display: none;">
                    <i class="fas fa-history"></i>
                    <p>No hay cambios registrados en el historial</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        const API_URL = '<?= APP_URL ?>/modules/superadmin/agencias_api.php';
        const AGENCIA_ID = <?= $agenciaId ?>;
        
        // Cargar datos al iniciar
        document.addEventListener('DOMContentLoaded', function() {
            cargarDatosAgencia();
            cargarHistorial();
        });
        
        // Cargar datos de la agencia
        function cargarDatosAgencia() {
            fetch(API_URL + '?action=get&id=' + AGENCIA_ID)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        mostrarDatosAgencia(data.agencia);
                    } else {
                        mostrarError('No se pudo cargar la información de la agencia');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    mostrarError('Error de conexión al cargar la agencia');
                });
        }
        
        // Mostrar datos de la agencia
        function mostrarDatosAgencia(agencia) {
            document.getElementById('loading-container').style.display = 'none';
            document.getElementById('content-container').style.display = 'block';
            
            // Información General
            document.getElementById('info-nombre').textContent = agencia.nombre;
            document.getElementById('info-nit').textContent = agencia.nit || 'No especificado';
            
            // Badge de estado
            let estadoBadge = '';
            switch(agencia.estado_suscripcion) {
                case 'activa':
                    estadoBadge = '<span class="badge badge-activa">Activa</span>';
                    break;
                case 'inactiva':
                    estadoBadge = '<span class="badge badge-inactiva">Inactiva</span>';
                    break;
                case 'suspendida':
                    estadoBadge = '<span class="badge badge-suspendida">Suspendida</span>';
                    break;
            }
            document.getElementById('info-estado-badge').innerHTML = estadoBadge;
            
            // Suscripción
            document.getElementById('info-fecha-inicio').textContent = formatearFecha(agencia.fecha_inicio_suscripcion);
            document.getElementById('info-fecha-fin').textContent = formatearFecha(agencia.fecha_fin_suscripcion);
            
            const diasRestantes = agencia.dias_restantes;
            const diasElement = document.getElementById('info-dias-restantes');
            diasElement.textContent = diasRestantes > 0 ? diasRestantes : '0';
            
            if (diasRestantes < 30) {
                diasElement.style.color = '#dc2626';
            } else if (diasRestantes < 90) {
                diasElement.style.color = '#ea580c';
            } else {
                diasElement.style.color = '#10b981';
            }
            
            // Usuarios
            document.getElementById('info-total-usuarios').textContent = agencia.total_usuarios;
            document.getElementById('info-max-usuarios').textContent = agencia.max_usuarios;
            
            // Contacto
            document.getElementById('info-email').textContent = agencia.email_contacto || 'No especificado';
            document.getElementById('info-telefono').textContent = agencia.telefono || 'No especificado';
            document.getElementById('info-direccion').textContent = agencia.direccion || 'No especificada';
            document.getElementById('info-descripcion').textContent = agencia.descripcion || 'Sin descripción';
        }
        
        // Cargar historial
        function cargarHistorial() {
            fetch(API_URL + '?action=get_historial&agencia_id=' + AGENCIA_ID)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        mostrarHistorial(data.historial);
                    } else {
                        document.getElementById('historial-loading').style.display = 'none';
                        document.getElementById('historial-empty').style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('historial-loading').style.display = 'none';
                    document.getElementById('historial-empty').style.display = 'block';
                });
        }
        
        // Mostrar historial
        function mostrarHistorial(historial) {
            document.getElementById('historial-loading').style.display = 'none';
            
            if (historial.length === 0) {
                document.getElementById('historial-empty').style.display = 'block';
                return;
            }
            
            const timeline = document.getElementById('historial-timeline');
            timeline.style.display = 'block';
            timeline.innerHTML = '';
            
            historial.forEach(item => {
                const div = document.createElement('div');
                div.className = 'timeline-item';
                
                // Icono según tipo de evento
                let iconClass = 'timeline-icon-edicion';
                let iconSymbol = 'edit';
                
                switch(item.tipo_evento) {
                    case 'creacion':
                        iconClass = 'timeline-icon-creacion';
                        iconSymbol = 'plus';
                        break;
                    case 'renovacion_suscripcion':
                        iconClass = 'timeline-icon-renovacion';
                        iconSymbol = 'sync';
                        break;
                    case 'suspension':
                        iconClass = 'timeline-icon-suspension';
                        iconSymbol = 'pause';
                        break;
                    case 'activacion':
                        iconClass = 'timeline-icon-activacion';
                        iconSymbol = 'play';
                        break;
                    case 'cambio_limite_usuarios':
                        iconClass = 'timeline-icon-cambio';
                        iconSymbol = 'users';
                        break;
                }
                
                div.innerHTML = `
                    <div class="timeline-icon ${iconClass}">
                        <i class="fas fa-${iconSymbol}"></i>
                    </div>
                    <div class="timeline-content">
                        <div class="timeline-header">
                            <div class="timeline-title">${getTipoEventoNombre(item.tipo_evento)}</div>
                            <div class="timeline-date">${formatearFechaHora(item.created_at)}</div>
                        </div>
                        <div class="timeline-description">${item.descripcion}</div>
                        <div class="timeline-user">
                            <i class="fas fa-user"></i>
                            ${item.superadmin_nombre || 'Sistema'}
                        </div>
                    </div>
                `;
                
                timeline.appendChild(div);
            });
        }
        
        // Obtener nombre del tipo de evento
        function getTipoEventoNombre(tipo) {
            const nombres = {
                'creacion': 'Agencia Creada',
                'edicion': 'Información Actualizada',
                'renovacion_suscripcion': 'Suscripción Renovada',
                'suspension': 'Agencia Suspendida',
                'activacion': 'Agencia Activada',
                'cambio_limite_usuarios': 'Límite de Usuarios Modificado'
            };
            return nombres[tipo] || tipo;
        }
        
        // Formatear fecha
        function formatearFecha(fecha) {
            const date = new Date(fecha);
            return date.toLocaleDateString('es-ES', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
        }
        
        // Formatear fecha y hora
        function formatearFechaHora(fecha) {
            const date = new Date(fecha);
            return date.toLocaleDateString('es-ES', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        // Mostrar error
        function mostrarError(mensaje) {
            document.getElementById('loading-container').style.display = 'none';
            const errorContainer = document.getElementById('error-container');
            errorContainer.style.display = 'block';
            errorContainer.innerHTML = `
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>${mensaje}</span>
                </div>
            `;
        }
        function goToUsuarios() {
            window.location.href = '<?= APP_URL ?>/superadmin/usuarios?agencia_id=' + AGENCIA_ID;
        }
    </script>
</body>
</html>