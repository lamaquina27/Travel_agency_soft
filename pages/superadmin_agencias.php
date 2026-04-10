<?php
// =====================================
// ARCHIVO: pages/superadmin_agencias.php - Gestión de Agencias (GRUPO 2)
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Agencias - <?= htmlspecialchars($companyName) ?></title>
    
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
        
        .header-actions {
            display: flex;
            gap: 15px;
            align-items: center;
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
        
        /* Page Header */
        .page-header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .page-title {
            font-size: 28px;
            color: #1a202c;
            margin-bottom: 5px;
        }
        
        .page-description {
            color: #718096;
            font-size: 14px;
        }
        
        .btn-nueva-agencia {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .btn-nueva-agencia:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        
        /* Table Container */
        .table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        
        .agencias-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .agencias-table thead {
            background: #f8fafc;
        }
        
        .agencias-table th {
            padding: 16px 20px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .agencias-table td {
            padding: 18px 20px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 14px;
            color: #334155;
        }
        
        .agencias-table tr:hover td {
            background-color: #f8fafc;
        }
        
        /* Badges */
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
        
        /* Action Buttons */
        .action-btns {
            display: flex;
            gap: 8px;
        }
        
        .btn-action {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-editar {
            background: #e0e7ff;
            color: #4338ca;
        }
        
        .btn-editar:hover {
            background: #c7d2fe;
        }
        
        .btn-detalles {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .btn-detalles:hover {
            background: #bfdbfe;
        }
        
        .btn-usuarios {
            background: #fef3c7;
            color: #92400e;
        }
        
        .btn-usuarios:hover {
            background: #fde68a;
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
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
        }
        
        .empty-state i {
            font-size: 64px;
            color: #cbd5e1;
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            font-size: 20px;
            color: #475569;
            margin-bottom: 10px;
        }
        
        .empty-state p {
            color: #94a3b8;
            font-size: 14px;
        }
        
        /* Alert Messages */
        .alert {
            padding: 16px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
        
        .alert i {
            font-size: 18px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 20px;
            }
            
            .agencias-table {
                font-size: 12px;
            }
            
            .agencias-table th,
            .agencias-table td {
                padding: 12px 10px;
            }
            
            .action-btns {
                flex-direction: column;
            }
        }
        /* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.modal-content {
    background-color: white;
    margin: 50px auto;
    border-radius: 16px;
    width: 90%;
    max-width: 700px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        transform: translateY(-50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.modal-header {
    padding: 24px 30px;
    border-bottom: 2px solid #f1f5f9;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h2 {
    font-size: 22px;
    color: #1e293b;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.modal-close {
    background: none;
    border: none;
    font-size: 32px;
    color: #94a3b8;
    cursor: pointer;
    line-height: 1;
    transition: color 0.2s ease;
}

.modal-close:hover {
    color: #ef4444;
}

.modal-body {
    padding: 30px;
}

.modal-footer {
    padding: 20px 30px;
    border-top: 2px solid #f1f5f9;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

/* Form Styles */
.form-section {
    margin-bottom: 30px;
}

.form-section:last-child {
    margin-bottom: 0;
}

.form-section-title {
    font-size: 16px;
    color: #475569;
    font-weight: 600;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #f1f5f9;
}

.form-group {
    margin-bottom: 20px;
}

.form-label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: #334155;
    margin-bottom: 8px;
}

.form-label.required::after {
    content: " *";
    color: #ef4444;
}

.form-input,
.form-textarea {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    color: #1e293b;
    transition: all 0.2s ease;
}

.form-input:focus,
.form-textarea:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-textarea {
    resize: vertical;
    font-family: inherit;
}

.form-help {
    display: block;
    font-size: 12px;
    color: #64748b;
    margin-top: 6px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

/* Buttons */
.btn-primary,
.btn-secondary {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.btn-secondary {
    background: #f1f5f9;
    color: #475569;
}

.btn-secondary:hover {
    background: #e2e8f0;
}

/* Responsive */
@media (max-width: 768px) {
    .modal-content {
        margin: 20px;
        width: calc(100% - 40px);
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
}
.alert-info {
    background: #dbeafe;
    color: #1e40af;
    border-left: 4px solid #3b82f6;
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 13px;
}

.form-input:disabled {
    background-color: #f1f5f9;
    cursor: not-allowed;
    opacity: 0.6;
}
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <div>
                <div class="header-title">
                    <i class="fas fa-building"></i> Gestión de Agencias
                </div>
                <div class="header-subtitle"><?= htmlspecialchars($companyName) ?></div>
            </div>
            <div class="header-actions">
                <a href="<?= APP_URL ?>/superadmin/dashboard" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Volver al Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-container">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1 class="page-title">Agencias del Sistema</h1>
                <p class="page-description">Gestiona todas las agencias, suscripciones y configuraciones</p>
            </div>
            <button class="btn-nueva-agencia" onclick="abrirModalNuevaAgencia()">
                <i class="fas fa-plus"></i> Nueva Agencia
            </button>
        </div>

        <!-- Alert Messages (se mostrarán dinámicamente) -->
        <div id="alert-container"></div>

        <!-- Table Container -->
        <div class="table-container">
            <div id="loading-container" class="loading">
                <i class="fas fa-spinner"></i>
                <p>Cargando agencias...</p>
            </div>
            
            <div id="table-content" style="display: none;">
                <table class="agencias-table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>NIT</th>
                            <th>Estado</th>
                            <th>Usuarios</th>
                            <th>Fin Suscripción</th>
                            <th>Días Restantes</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="agencias-tbody">
                        <!-- Se llenará dinámicamente -->
                    </tbody>
                </table>
            </div>
            
            <div id="empty-state" style="display: none;" class="empty-state">
                <i class="fas fa-building"></i>
                <h3>No hay agencias registradas</h3>
                <p>Comienza creando tu primera agencia haciendo clic en "Nueva Agencia"</p>
            </div>
        </div>
    </div>

    <script>
        // URL de la API
        const API_URL = '<?= APP_URL ?>/modules/superadmin/agencias_api.php';
        
        // Cargar agencias al iniciar la página
        document.addEventListener('DOMContentLoaded', function() {
            cargarAgencias();
        });
        
        // Función para cargar todas las agencias
        function cargarAgencias() {
            fetch(API_URL + '?action=list')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        mostrarAgencias(data.agencias);
                    } else {
                        mostrarError('Error al cargar agencias: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    mostrarError('Error de conexión al cargar las agencias');
                });
        }
        
        // Función para mostrar las agencias en la tabla
        function mostrarAgencias(agencias) {
            const loadingContainer = document.getElementById('loading-container');
            const tableContent = document.getElementById('table-content');
            const emptyState = document.getElementById('empty-state');
            const tbody = document.getElementById('agencias-tbody');
            
            loadingContainer.style.display = 'none';
            
            if (agencias.length === 0) {
                emptyState.style.display = 'block';
                tableContent.style.display = 'none';
                return;
            }
            
            emptyState.style.display = 'none';
            tableContent.style.display = 'block';
            
            tbody.innerHTML = '';
            
            agencias.forEach(agencia => {
                const tr = document.createElement('tr');
                
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
                
                // Color de días restantes
                let diasColor = '#065f46';
                if (agencia.dias_restantes < 30) {
                    diasColor = '#dc2626';
                } else if (agencia.dias_restantes < 90) {
                    diasColor = '#ea580c';
                }
                
                tr.innerHTML = `
                    <td><strong>${agencia.nombre}</strong></td>
                    <td>${agencia.nit || '-'}</td>
                    <td>${estadoBadge}</td>
                    <td>${agencia.total_usuarios} / ${agencia.max_usuarios}</td>
                    <td>${formatearFecha(agencia.fecha_fin_suscripcion)}</td>
                    <td style="color: ${diasColor}; font-weight: 600;">
                        ${agencia.dias_restantes > 0 ? agencia.dias_restantes + ' días' : 'Vencida'}
                    </td>
                    <td>
                        <div class="action-btns">
                            <button class="btn-action btn-editar" onclick="editarAgencia(${agencia.id})">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            <button class="btn-action btn-detalles" onclick="verDetalles(${agencia.id})">
                                <i class="fas fa-eye"></i> Detalles
                            </button>
                            <button class="btn-action btn-usuarios" onclick="gestionarUsuarios(${agencia.id})">
                                <i class="fas fa-users"></i> Usuarios
                            </button>
                        </div>
                    </td>
                `;
                
                tbody.appendChild(tr);
            });
        }
        
        // Función para formatear fechas
        function formatearFecha(fecha) {
            const date = new Date(fecha);
            return date.toLocaleDateString('es-ES', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric' 
            });
        }

// Función para abrir el modal de nueva agencia
function abrirModalNuevaAgencia() {
    const modal = document.getElementById('modalNuevaAgencia');
    modal.style.display = 'block';
    
    // Establecer fecha de inicio como hoy
    const hoy = new Date().toISOString().split('T')[0];
    document.getElementById('fechaInicio').value = hoy;
    
    // Establecer fecha de fin como hoy + 1 año
    const fechaFin = new Date();
    fechaFin.setFullYear(fechaFin.getFullYear() + 1);
    document.getElementById('fechaFin').value = fechaFin.toISOString().split('T')[0];
}

// Función para cerrar el modal
function cerrarModalNuevaAgencia() {
    const modal = document.getElementById('modalNuevaAgencia');
    modal.style.display = 'none';
    document.getElementById('formNuevaAgencia').reset();
}

// Cerrar modal al hacer clic fuera de él
window.onclick = function(event) {
    const modal = document.getElementById('modalNuevaAgencia');
    if (event.target === modal) {
        cerrarModalNuevaAgencia();
    }
}

// Función para guardar nueva agencia
function guardarNuevaAgencia(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    formData.append('action', 'create');
    
    // Validar fechas
    const fechaInicio = new Date(formData.get('fecha_inicio_suscripcion'));
    const fechaFin = new Date(formData.get('fecha_fin_suscripcion'));
    
    if (fechaFin <= fechaInicio) {
        mostrarAlerta('La fecha de fin debe ser posterior a la fecha de inicio', 'error');
        return;
    }
    
    // Deshabilitar botón de envío
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    
    fetch(API_URL, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarAlerta('Agencia creada exitosamente', 'success');
            cerrarModalNuevaAgencia();
            cargarAgencias(); // Recargar la tabla
        } else {
            mostrarAlerta('Error: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarAlerta('Error de conexión al guardar la agencia', 'error');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}
        
        // Función para mostrar alertas
        function mostrarAlerta(mensaje, tipo = 'success') {
            const container = document.getElementById('alert-container');
            const icon = tipo === 'success' ? 'check-circle' : 'exclamation-circle';
            const alertClass = tipo === 'success' ? 'alert-success' : 'alert-error';
            
            container.innerHTML = `
                <div class="alert ${alertClass}">
                    <i class="fas fa-${icon}"></i>
                    <span>${mensaje}</span>
                </div>
            `;
            
            setTimeout(() => {
                container.innerHTML = '';
            }, 5000);
        }
        
        function mostrarError(mensaje) {
            mostrarAlerta(mensaje, 'error');
        }
        
        
        
       function editarAgencia(id) {
    // Obtener datos de la agencia
    fetch(API_URL + '?action=get&id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const agencia = data.agencia;
                
                // Llenar el formulario con los datos
                document.getElementById('edit_id').value = agencia.id;
                document.getElementById('edit_nombre').value = agencia.nombre || '';
                document.getElementById('edit_descripcion').value = agencia.descripcion || '';
                document.getElementById('edit_nit').value = agencia.nit || '';
                document.getElementById('edit_telefono').value = agencia.telefono || '';
                document.getElementById('edit_email_contacto').value = agencia.email_contacto || '';
                document.getElementById('edit_direccion').value = agencia.direccion || '';
                document.getElementById('edit_fecha_inicio').value = agencia.fecha_inicio_suscripcion;
                document.getElementById('edit_fecha_fin').value = agencia.fecha_fin_suscripcion;
                document.getElementById('edit_max_usuarios').value = agencia.max_usuarios;
                document.getElementById('edit_estado').value = agencia.estado_suscripcion;
                
                // Mostrar el modal
                document.getElementById('modalEditarAgencia').style.display = 'block';
            } else {
                mostrarAlerta('Error al cargar los datos de la agencia', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarAlerta('Error de conexión al cargar la agencia', 'error');
        });
}

// Función para cerrar el modal de edición
function cerrarModalEditarAgencia() {
    const modal = document.getElementById('modalEditarAgencia');
    modal.style.display = 'none';
    document.getElementById('formEditarAgencia').reset();
}

// Cerrar modal de edición al hacer clic fuera
window.addEventListener('click', function(event) {
    const modalEditar = document.getElementById('modalEditarAgencia');
    if (event.target === modalEditar) {
        cerrarModalEditarAgencia();
    }
});

// Función para guardar cambios de la agencia
function guardarEdicionAgencia(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    formData.append('action', 'update');
    
    // Validar fecha de fin
    const fechaInicio = new Date(document.getElementById('edit_fecha_inicio').value);
    const fechaFin = new Date(formData.get('fecha_fin_suscripcion'));
    
    if (fechaFin <= fechaInicio) {
        mostrarAlerta('La fecha de fin debe ser posterior a la fecha de inicio', 'error');
        return;
    }
    
    // Deshabilitar botón de envío
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    
    fetch(API_URL, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarAlerta('Agencia actualizada exitosamente', 'success');
            cerrarModalEditarAgencia();
            cargarAgencias(); // Recargar la tabla
        } else {
            mostrarAlerta('Error: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarAlerta('Error de conexión al actualizar la agencia', 'error');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}
        
function verDetalles(id) {
    window.location.href = '<?= APP_URL ?>/superadmin/agencias/detalle?id=' + id;
}
        
        function gestionarUsuarios(id) {
            window.location.href = '<?= APP_URL ?>/superadmin/usuarios?agencia_id=' + id;
        }
    </script>
    <!-- Modal Nueva Agencia -->
<div id="modalNuevaAgencia" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-plus-circle"></i> Nueva Agencia</h2>
            <button class="modal-close" onclick="cerrarModalNuevaAgencia()">&times;</button>
        </div>
        <form id="formNuevaAgencia" onsubmit="guardarNuevaAgencia(event)">
            <div class="modal-body">
                <!-- Información Básica -->
                <div class="form-section">
                    <h3 class="form-section-title">Información Básica</h3>
                    
                    <div class="form-group">
                        <label class="form-label required">Nombre de la Agencia</label>
                        <input type="text" name="nombre" class="form-input" required maxlength="200" 
                               placeholder="Ej: Viajes Paraíso">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Descripción</label>
                        <textarea name="descripcion" class="form-textarea" rows="3" 
                                  placeholder="Descripción breve de la agencia (opcional)"></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">NIT</label>
                            <input type="text" name="nit" class="form-input" maxlength="50" 
                                   placeholder="Ej: 900123456-7">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Teléfono</label>
                            <input type="tel" name="telefono" class="form-input" maxlength="50" 
                                   placeholder="Ej: +57 300 1234567">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Email de Contacto</label>
                        <input type="email" name="email_contacto" class="form-input" maxlength="100" 
                               placeholder="contacto@agencia.com">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Dirección</label>
                        <input type="text" name="direccion" class="form-input" maxlength="300" 
                               placeholder="Dirección completa de la agencia">
                    </div>
                </div>
                
                <!-- Configuración de Suscripción -->
                <div class="form-section">
                    <h3 class="form-section-title">Configuración de Suscripción</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label required">Fecha Inicio</label>
                            <input type="date" name="fecha_inicio_suscripcion" class="form-input" 
                                   required id="fechaInicio">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label required">Fecha Fin</label>
                            <input type="date" name="fecha_fin_suscripcion" class="form-input" 
                                   required id="fechaFin">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required">Máximo de Usuarios</label>
                        <input type="number" name="max_usuarios" class="form-input" 
                               required min="1" max="999" value="10">
                        <small class="form-help">Número máximo de usuarios permitidos en esta agencia</small>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="cerrarModalNuevaAgencia()">
                    Cancelar
                </button>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save"></i> Guardar Agencia
                </button>
            </div>
        </form>
    </div>
</div>
<!-- Modal Editar Agencia -->
<div id="modalEditarAgencia" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-edit"></i> Editar Agencia</h2>
            <button class="modal-close" onclick="cerrarModalEditarAgencia()">&times;</button>
        </div>
        <form id="formEditarAgencia" onsubmit="guardarEdicionAgencia(event)">
            <input type="hidden" name="id" id="edit_id">
            
            <div class="modal-body">
                <!-- Información Básica -->
                <div class="form-section">
                    <h3 class="form-section-title">Información Básica</h3>
                    
                    <div class="form-group">
                        <label class="form-label required">Nombre de la Agencia</label>
                        <input type="text" name="nombre" id="edit_nombre" class="form-input" required maxlength="200">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Descripción</label>
                        <textarea name="descripcion" id="edit_descripcion" class="form-textarea" rows="3"></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">NIT</label>
                            <input type="text" name="nit" id="edit_nit" class="form-input" maxlength="50">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Teléfono</label>
                            <input type="tel" name="telefono" id="edit_telefono" class="form-input" maxlength="50">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Email de Contacto</label>
                        <input type="email" name="email_contacto" id="edit_email_contacto" class="form-input" maxlength="100">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Dirección</label>
                        <input type="text" name="direccion" id="edit_direccion" class="form-input" maxlength="300">
                    </div>
                </div>
                
                <!-- Configuración de Suscripción -->
                <div class="form-section">
                    <h3 class="form-section-title">Configuración de Suscripción</h3>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <span>La fecha de inicio no puede modificarse una vez creada la agencia.</span>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Fecha Inicio</label>
                            <input type="date" id="edit_fecha_inicio" class="form-input" disabled>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label required">Fecha Fin</label>
                            <input type="date" name="fecha_fin_suscripcion" id="edit_fecha_fin" class="form-input" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label required">Máximo de Usuarios</label>
                            <input type="number" name="max_usuarios" id="edit_max_usuarios" class="form-input" 
                                   required min="1" max="999">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label required">Estado de Suscripción</label>
                            <select name="estado_suscripcion" id="edit_estado" class="form-input" required>
                                <option value="activa">Activa</option>
                                <option value="inactiva">Inactiva</option>
                                <option value="suspendida">Suspendida</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="cerrarModalEditarAgencia()">
                    Cancelar
                </button>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>


</body>
</html>