<?php
// =====================================
// ARCHIVO: pages/superadmin_usuarios_agencia.php
// Vista de Gestión de Usuarios por Agencia (GRUPO 3)
// =====================================

require_once 'config/app.php';
require_once 'config/config_functions.php';

App::init();
App::requireRole('superadmin');

$user = App::getUser();
$companyName = App::getCompanyName();

// Verificar que venga el ID de la agencia
if (!isset($_GET['agencia_id']) || empty($_GET['agencia_id'])) {
    header('Location: ' . APP_URL . '/superadmin/agencias');
    exit;
}

$agenciaId = intval($_GET['agencia_id']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - <?= htmlspecialchars($companyName) ?></title>
    
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
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .header-subtitle {
            font-size: 14px;
            opacity: 0.9;
            margin-top: 5px;
        }
        
        .btn-back {
            background: rgba(255,255,255,0.2);
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            color: white;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .btn-back:hover {
            background: rgba(255,255,255,0.3);
        }
        
        /* Main Container */
        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px;
        }
        
        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .page-title {
            font-size: 28px;
            color: #1e293b;
            margin-bottom: 5px;
        }
        
        .page-description {
            color: #64748b;
            font-size: 14px;
        }
        
        .btn-nuevo-usuario {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .btn-nuevo-usuario:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        
        /* Info Card */
        .info-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .info-card-left h3 {
            font-size: 20px;
            color: #1e293b;
            margin-bottom: 5px;
        }
        
        .info-card-left p {
            color: #64748b;
            font-size: 14px;
        }
        
        .info-stats {
            display: flex;
            gap: 30px;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 28px;
            font-weight: 700;
            color: #667eea;
        }
        
        .stat-label {
            font-size: 12px;
            color: #64748b;
            text-transform: uppercase;
            margin-top: 5px;
        }
        
        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
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
        
        .alert-warning {
            background: #fef3c7;
            color: #92400e;
            border-left: 4px solid #f59e0b;
        }
        
        /* Table */
        .table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .usuarios-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .usuarios-table thead {
            background: #f8fafc;
        }
        
        .usuarios-table th {
            padding: 16px;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .usuarios-table td {
            padding: 16px;
            border-top: 1px solid #e2e8f0;
            font-size: 14px;
            color: #1e293b;
        }
        
        .usuarios-table tbody tr:hover {
            background: #f8fafc;
        }
        
        /* Estado Badge */
        .badge-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-inactive {
            background: #fee2e2;
            color: #991b1b;
        }
        
        /* Rol Badge */
        .badge-role {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-admin {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .badge-agent {
            background: #e0e7ff;
            color: #4338ca;
        }
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .btn-action {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s;
        }
        
        .btn-edit {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .btn-edit:hover {
            background: #bfdbfe;
        }
        
        .btn-toggle {
            background: #fef3c7;
            color: #92400e;
        }
        
        .btn-toggle:hover {
            background: #fde68a;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            backdrop-filter: blur(4px);
        }
        
        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        .modal-header {
            padding: 24px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-title {
            font-size: 20px;
            font-weight: 600;
            color: #1e293b;
        }
        
        .btn-close {
            background: none;
            border: none;
            font-size: 24px;
            color: #64748b;
            cursor: pointer;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
        }
        
        .btn-close:hover {
            background: #f1f5f9;
            color: #1e293b;
        }
        
        .modal-body {
            padding: 24px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 8px;
        }
        
        .required {
            color: #ef4444;
        }
        
        .form-input {
            width: 100%;
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-select {
            width: 100%;
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
        }
        
        /* Password Requirements */
        .password-requirements {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px;
            margin-top: 8px;
            font-size: 12px;
        }
        
        .password-requirements p {
            font-weight: 600;
            color: #475569;
            margin-bottom: 8px;
        }
        
        .requirement {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #64748b;
            margin-bottom: 4px;
        }
        
        .requirement.valid {
            color: #10b981;
        }
        
        .requirement.invalid {
            color: #ef4444;
        }
        
        .requirement i {
            font-size: 10px;
        }
        
        /* Form Row */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        
        /* Modal Footer */
        .modal-footer {
            padding: 20px 24px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
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
        
        /* Loading & Empty States */
        .loading, .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #64748b;
        }
        
        .loading i, .empty-state i {
            font-size: 48px;
            margin-bottom: 16px;
            color: #cbd5e1;
        }
        
        .loading i {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .empty-state h3 {
            color: #475569;
            margin-bottom: 8px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .main-container {
                padding: 20px;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 20px;
            }
            
            .info-card {
                flex-direction: column;
                gap: 20px;
            }
            
            .info-stats {
                width: 100%;
                justify-content: space-around;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .modal-content {
                margin: 20px;
                width: calc(100% - 40px);
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
                    <i class="fas fa-users"></i> Gestión de Usuarios
                </div>
                <div class="header-subtitle" id="agencia-nombre">Cargando...</div>
            </div>
            <a href="<?= APP_URL ?>/superadmin/agencias" class="btn-back">
                <i class="fas fa-arrow-left"></i> Volver a Agencias
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-container">
        <!-- Info Card -->
        <div class="info-card" id="info-card">
            <div class="info-card-left">
                <h3 id="agencia-titulo">Cargando...</h3>
                <p>Usuarios registrados en esta agencia</p>
            </div>
            <div class="info-stats">
                <div class="stat-item">
                    <div class="stat-number" id="total-usuarios">0</div>
                    <div class="stat-label">Usuarios Activos</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number" id="max-usuarios">0</div>
                    <div class="stat-label">Límite Máximo</div>
                </div>
            </div>
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1 class="page-title">Lista de Usuarios</h1>
                <p class="page-description">Administra los usuarios de esta agencia</p>
            </div>
            <button class="btn-nuevo-usuario" id="btn-nuevo-usuario" onclick="abrirModalNuevoUsuario()">
                <i class="fas fa-plus"></i> Agregar Usuario
            </button>
        </div>

        <!-- Alert Container -->
        <div id="alert-container"></div>

        <!-- Table Container -->
        <div class="table-container">
            <div id="loading-container" class="loading">
                <i class="fas fa-spinner"></i>
                <p>Cargando usuarios...</p>
            </div>
            
            <div id="table-content" style="display: none;">
                <table class="usuarios-table">
                    <thead>
                        <tr>
                            <th>Nombre Completo</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Último Acceso</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="usuarios-tbody">
                        <!-- Se llenará dinámicamente -->
                    </tbody>
                </table>
            </div>
            
            <div id="empty-state" style="display: none;" class="empty-state">
                <i class="fas fa-users"></i>
                <h3>No hay usuarios registrados</h3>
                <p>Comienza agregando el primer usuario a esta agencia</p>
            </div>
        </div>
    </div>

    <!-- Modal Nuevo Usuario -->
    <div id="modal-nuevo-usuario" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Agregar Nuevo Usuario</h2>
                <button class="btn-close" onclick="cerrarModalNuevoUsuario()">&times;</button>
            </div>
            
            <form id="form-nuevo-usuario">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Email <span class="required">*</span></label>
                        <input 
                            type="email" 
                            class="form-input" 
                            id="nuevo-email" 
                            name="email" 
                            required
                            placeholder="usuario@ejemplo.com"
                        >
                        <small id="email-error" style="color: #ef4444; display: none; margin-top: 5px; display: block;"></small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Nombre Completo <span class="required">*</span></label>
                        <input 
                            type="text" 
                            class="form-input" 
                            id="nuevo-fullname" 
                            name="full_name" 
                            required
                            placeholder="Juan Pérez"
                        >
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Contraseña <span class="required">*</span></label>
                            <input 
                                type="password" 
                                class="form-input" 
                                id="nuevo-password" 
                                name="password" 
                                required
                            >
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Confirmar Contraseña <span class="required">*</span></label>
                            <input 
                                type="password" 
                                class="form-input" 
                                id="nuevo-confirm-password" 
                                required
                            >
                        </div>
                    </div>
                    
                    <div class="password-requirements">
                        <p><i class="fas fa-lock"></i> Requisitos de Contraseña:</p>
                        <div class="requirement" id="req-length">
                            <i class="fas fa-circle"></i>
                            <span>Mínimo 8 caracteres</span>
                        </div>
                        <div class="requirement" id="req-upper">
                            <i class="fas fa-circle"></i>
                            <span>Al menos una letra mayúscula (A-Z)</span>
                        </div>
                        <div class="requirement" id="req-lower">
                            <i class="fas fa-circle"></i>
                            <span>Al menos una letra minúscula (a-z)</span>
                        </div>
                        <div class="requirement" id="req-number">
                            <i class="fas fa-circle"></i>
                            <span>Al menos un número (0-9)</span>
                        </div>
                        <div class="requirement" id="req-special">
                            <i class="fas fa-circle"></i>
                            <span>Al menos un carácter especial (!@#$%^&*)</span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Rol <span class="required">*</span></label>
                        <select class="form-select" id="nuevo-role" name="role" required>
                            <option value="">Seleccione un rol</option>
                            <option value="admin">Administrador</option>
                            <option value="agent">Agente</option>
                        </select>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModalNuevoUsuario()">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="btn-guardar-usuario">
                        <i class="fas fa-save"></i> Crear Usuario
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Editar Usuario -->
    <div id="modal-editar-usuario" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Editar Usuario</h2>
                <button class="btn-close" onclick="cerrarModalEditarUsuario()">&times;</button>
            </div>
            
            <form id="form-editar-usuario">
                <input type="hidden" id="editar-user-id">
                
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Email <span class="required">*</span></label>
                        <input 
                            type="email" 
                            class="form-input" 
                            id="editar-email" 
                            name="email" 
                            required
                        >
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Nombre Completo <span class="required">*</span></label>
                        <input 
                            type="text" 
                            class="form-input" 
                            id="editar-fullname" 
                            name="full_name" 
                            required
                        >
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Rol <span class="required">*</span></label>
                        <select class="form-select" id="editar-role" name="role" required>
                            <option value="admin">Administrador</option>
                            <option value="agent">Agente</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Nueva Contraseña (Opcional)</label>
                        <input 
                            type="password" 
                            class="form-input" 
                            id="editar-password" 
                            name="password"
                            placeholder="Dejar vacío para no cambiar"
                        >
                        <small style="color: #64748b; font-size: 12px; display: block; margin-top: 5px;">
                            * Solo completa este campo si deseas cambiar la contraseña
                        </small>
                    </div>
                    
                    <div id="password-requirements-edit" class="password-requirements" style="display: none;">
                        <p><i class="fas fa-lock"></i> Requisitos de Contraseña:</p>
                        <div class="requirement" id="req-length-edit">
                            <i class="fas fa-circle"></i>
                            <span>Mínimo 8 caracteres</span>
                        </div>
                        <div class="requirement" id="req-upper-edit">
                            <i class="fas fa-circle"></i>
                            <span>Al menos una letra mayúscula (A-Z)</span>
                        </div>
                        <div class="requirement" id="req-lower-edit">
                            <i class="fas fa-circle"></i>
                            <span>Al menos una letra minúscula (a-z)</span>
                        </div>
                        <div class="requirement" id="req-number-edit">
                            <i class="fas fa-circle"></i>
                            <span>Al menos un número (0-9)</span>
                        </div>
                        <div class="requirement" id="req-special-edit">
                            <i class="fas fa-circle"></i>
                            <span>Al menos un carácter especial (!@#$%^&*)</span>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModalEditarUsuario()">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="btn-actualizar-usuario">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const APP_URL = '<?= APP_URL ?>';
        const AGENCIA_ID = <?= $agenciaId ?>;
        const API_URL = APP_URL + '/modules/superadmin/usuarios_api.php';
        
        let agenciaData = null;
        let usuarios = [];
        
        // Cargar al iniciar
        document.addEventListener('DOMContentLoaded', function() {
            cargarUsuarios();
            initializeEventListeners();
        });
        
        // ===================================
        // CARGAR USUARIOS
        // ===================================
        async function cargarUsuarios() {
            try {
                const response = await fetch(`${API_URL}?action=list_by_agencia&agencia_id=${AGENCIA_ID}`);
                const data = await response.json();
                
                if (data.success) {
                    agenciaData = data.agencia;
                    usuarios = data.usuarios;
                    
                    actualizarInfoAgencia();
                    mostrarUsuarios();
                } else {
                    mostrarError('Error al cargar usuarios: ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarError('Error de conexión al cargar los usuarios');
            }
        }
        
        // ===================================
        // ACTUALIZAR INFO DE AGENCIA
        // ===================================
        function actualizarInfoAgencia() {
            document.getElementById('agencia-nombre').textContent = agenciaData.nombre;
            document.getElementById('agencia-titulo').textContent = agenciaData.nombre;
            
            const usuariosActivos = usuarios.filter(u => u.active == 1).length;
            document.getElementById('total-usuarios').textContent = usuariosActivos;
            document.getElementById('max-usuarios').textContent = agenciaData.max_usuarios;
            
            // Verificar si llegó al límite
            const btnNuevo = document.getElementById('btn-nuevo-usuario');
            if (usuariosActivos >= agenciaData.max_usuarios) {
                btnNuevo.disabled = true;
                btnNuevo.style.opacity = '0.5';
                btnNuevo.style.cursor = 'not-allowed';
                btnNuevo.title = 'Se alcanzó el límite de usuarios para esta agencia';
            } else {
                btnNuevo.disabled = false;
                btnNuevo.style.opacity = '1';
                btnNuevo.style.cursor = 'pointer';
                btnNuevo.title = '';
            }
        }
        
        // ===================================
        // MOSTRAR USUARIOS EN TABLA
        // ===================================
        function mostrarUsuarios() {
            const tbody = document.getElementById('usuarios-tbody');
            const loadingContainer = document.getElementById('loading-container');
            const tableContent = document.getElementById('table-content');
            const emptyState = document.getElementById('empty-state');
            
            loadingContainer.style.display = 'none';
            
            if (usuarios.length === 0) {
                tableContent.style.display = 'none';
                emptyState.style.display = 'block';
                return;
            }
            
            emptyState.style.display = 'none';
            tableContent.style.display = 'block';
            
            tbody.innerHTML = usuarios.map(user => `
                <tr>
                    <td>${escapeHtml(user.full_name)}</td>
                    <td>${escapeHtml(user.email)}</td>
                    <td>
                        <span class="badge-role badge-${user.role}">
                            ${user.role === 'admin' ? 'Administrador' : 'Agente'}
                        </span>
                    </td>
                    <td>
                        <span class="badge-status badge-${user.active == 1 ? 'active' : 'inactive'}">
                            ${user.active == 1 ? 'Activo' : 'Inactivo'}
                        </span>
                    </td>
                    <td>${formatDate(user.last_login) || 'Nunca'}</td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn-action btn-edit" onclick="abrirModalEditarUsuario(${user.id})">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            <button class="btn-action btn-toggle" onclick="toggleUserStatus(${user.id}, ${user.active})">
                                <i class="fas fa-${user.active == 1 ? 'ban' : 'check'}"></i>
                                ${user.active == 1 ? 'Desactivar' : 'Activar'}
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }
        
        // ===================================
        // ABRIR MODAL NUEVO USUARIO
        // ===================================
        function abrirModalNuevoUsuario() {
            // Verificar límite antes de abrir modal
            const usuariosActivos = usuarios.filter(u => u.active == 1).length;
            if (usuariosActivos >= agenciaData.max_usuarios) {
                mostrarError(`No se pueden crear más usuarios. La agencia ha alcanzado su límite de ${agenciaData.max_usuarios} usuarios.`);
                return;
            }
            
            document.getElementById('modal-nuevo-usuario').classList.add('active');
            document.getElementById('form-nuevo-usuario').reset();
            resetPasswordRequirements();
        }
        
        function cerrarModalNuevoUsuario() {
            document.getElementById('modal-nuevo-usuario').classList.remove('active');
            document.getElementById('form-nuevo-usuario').reset();
            document.getElementById('email-error').style.display = 'none';
        }
        
        // ===================================
        // ABRIR MODAL EDITAR USUARIO
        // ===================================
        async function abrirModalEditarUsuario(userId) {
            try {
                const response = await fetch(`${API_URL}?action=get&user_id=${userId}`);
                const data = await response.json();
                
                if (data.success) {
                    const user = data.user;
                    
                    document.getElementById('editar-user-id').value = user.id;
                    document.getElementById('editar-email').value = user.email;
                    document.getElementById('editar-fullname').value = user.full_name;
                    document.getElementById('editar-role').value = user.role;
                    document.getElementById('editar-password').value = '';
                    
                    document.getElementById('modal-editar-usuario').classList.add('active');
                } else {
                    mostrarError('Error al cargar datos del usuario');
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarError('Error de conexión');
            }
        }
        
        function cerrarModalEditarUsuario() {
            document.getElementById('modal-editar-usuario').classList.remove('active');
            document.getElementById('form-editar-usuario').reset();
            document.getElementById('password-requirements-edit').style.display = 'none';
        }
        
        // ===================================
        // INICIALIZAR EVENT LISTENERS
        // ===================================
        function initializeEventListeners() {
            // Form Nuevo Usuario
            document.getElementById('form-nuevo-usuario').addEventListener('submit', handleCrearUsuario);
            
            // Form Editar Usuario
            document.getElementById('form-editar-usuario').addEventListener('submit', handleEditarUsuario);
            
            // Validación de contraseña en tiempo real - Nuevo
            document.getElementById('nuevo-password').addEventListener('input', function() {
                validatePasswordRealTime(this.value);
            });
            
            // Validación de contraseña en tiempo real - Editar
            document.getElementById('editar-password').addEventListener('input', function() {
                const requirementsDiv = document.getElementById('password-requirements-edit');
                if (this.value.length > 0) {
                    requirementsDiv.style.display = 'block';
                    validatePasswordRealTimeEdit(this.value);
                } else {
                    requirementsDiv.style.display = 'none';
                }
            });
            
            // Verificar email en tiempo real
            let emailTimeout;
            document.getElementById('nuevo-email').addEventListener('input', function() {
                clearTimeout(emailTimeout);
                emailTimeout = setTimeout(() => {
                    checkEmailAvailable(this.value);
                }, 500);
            });
        }
        
        // ===================================
        // VALIDAR CONTRASEÑA EN TIEMPO REAL
        // ===================================
        function validatePasswordRealTime(password) {
            const requirements = {
                'req-length': password.length >= 8,
                'req-upper': /[A-Z]/.test(password),
                'req-lower': /[a-z]/.test(password),
                'req-number': /[0-9]/.test(password),
                'req-special': /[!@#$%^&*(),.?":{}|<>]/.test(password)
            };
            
            Object.keys(requirements).forEach(reqId => {
                const element = document.getElementById(reqId);
                if (element) {
                    element.className = requirements[reqId] ? 'requirement valid' : 'requirement invalid';
                }
            });
            
            return Object.values(requirements).every(req => req);
        }
        
        function validatePasswordRealTimeEdit(password) {
            const requirements = {
                'req-length-edit': password.length >= 8,
                'req-upper-edit': /[A-Z]/.test(password),
                'req-lower-edit': /[a-z]/.test(password),
                'req-number-edit': /[0-9]/.test(password),
                'req-special-edit': /[!@#$%^&*(),.?":{}|<>]/.test(password)
            };
            
            Object.keys(requirements).forEach(reqId => {
                const element = document.getElementById(reqId);
                if (element) {
                    element.className = requirements[reqId] ? 'requirement valid' : 'requirement invalid';
                }
            });
            
            return Object.values(requirements).every(req => req);
        }
        
        function resetPasswordRequirements() {
            const requirements = ['req-length', 'req-upper', 'req-lower', 'req-number', 'req-special'];
            requirements.forEach(reqId => {
                const element = document.getElementById(reqId);
                if (element) {
                    element.className = 'requirement';
                }
            });
        }
        
        // ===================================
        // VERIFICAR EMAIL DISPONIBLE
        // ===================================
        async function checkEmailAvailable(email) {
            const errorDiv = document.getElementById('email-error');
            
            if (!email || email.length < 5) {
                errorDiv.style.display = 'none';
                return;
            }
            
            try {
                const response = await fetch(`${API_URL}?action=check_email&email=${encodeURIComponent(email)}`);
                const data = await response.json();
                
                if (data.success) {
                    if (data.exists) {
                        errorDiv.textContent = '❌ Este email ya está registrado';
                        errorDiv.style.display = 'block';
                    } else {
                        errorDiv.style.display = 'none';
                    }
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }
        
        // ===================================
        // CREAR USUARIO
        // ===================================
        async function handleCrearUsuario(e) {
            e.preventDefault();
            
            const password = document.getElementById('nuevo-password').value;
            const confirmPassword = document.getElementById('nuevo-confirm-password').value;
            
            // Validar que las contraseñas coincidan
            if (password !== confirmPassword) {
                mostrarError('Las contraseñas no coinciden');
                return;
            }
            
            // Validar requisitos de contraseña
            if (!validatePasswordRealTime(password)) {
                mostrarError('La contraseña no cumple con todos los requisitos');
                return;
            }
            
            const btnGuardar = document.getElementById('btn-guardar-usuario');
            btnGuardar.disabled = true;
            btnGuardar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creando...';
            
            const formData = new FormData(e.target);
            formData.append('action', 'create');
            formData.append('agencia_id', AGENCIA_ID);
            
            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    mostrarExito('Usuario creado exitosamente');
                    cerrarModalNuevoUsuario();
                    await cargarUsuarios();
                } else {
                    mostrarError(data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarError('Error de conexión al crear el usuario');
            } finally {
                btnGuardar.disabled = false;
                btnGuardar.innerHTML = '<i class="fas fa-save"></i> Crear Usuario';
            }
        }
        
        // ===================================
        // EDITAR USUARIO
        // ===================================
        async function handleEditarUsuario(e) {
            e.preventDefault();
            
            const userId = document.getElementById('editar-user-id').value;
            const password = document.getElementById('editar-password').value;
            
            // Si hay contraseña, validarla
            if (password && password.length > 0) {
                if (!validatePasswordRealTimeEdit(password)) {
                    mostrarError('La nueva contraseña no cumple con todos los requisitos');
                    return;
                }
            }
            
            const btnActualizar = document.getElementById('btn-actualizar-usuario');
            btnActualizar.disabled = true;
            btnActualizar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
            
            const formData = new FormData(e.target);
            formData.append('action', 'update');
            formData.append('user_id', userId);
            
            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    mostrarExito('Usuario actualizado exitosamente');
                    cerrarModalEditarUsuario();
                    await cargarUsuarios();
                } else {
                    mostrarError(data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarError('Error de conexión al actualizar el usuario');
            } finally {
                btnActualizar.disabled = false;
                btnActualizar.innerHTML = '<i class="fas fa-save"></i> Guardar Cambios';
            }
        }
        
        // ===================================
        // ACTIVAR/DESACTIVAR USUARIO
        // ===================================
        async function toggleUserStatus(userId, currentStatus) {
            const accion = currentStatus == 1 ? 'desactivar' : 'activar';
            
            if (!confirm(`¿Estás seguro de que deseas ${accion} este usuario?`)) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'toggle_status');
            formData.append('user_id', userId);
            
            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    mostrarExito(data.message);
                    await cargarUsuarios();
                } else {
                    mostrarError(data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarError('Error de conexión');
            }
        }
        
        // ===================================
        // UTILIDADES
        // ===================================
        function mostrarExito(mensaje) {
            const alertContainer = document.getElementById('alert-container');
            alertContainer.innerHTML = `
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    ${mensaje}
                </div>
            `;
            
            setTimeout(() => {
                alertContainer.innerHTML = '';
            }, 5000);
            
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        
        function mostrarError(mensaje) {
            const alertContainer = document.getElementById('alert-container');
            alertContainer.innerHTML = `
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    ${mensaje}
                </div>
            `;
            
            setTimeout(() => {
                alertContainer.innerHTML = '';
            }, 5000);
            
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text ? text.replace(/[&<>"']/g, m => map[m]) : '';
        }
        
        function formatDate(dateString) {
            if (!dateString) return null;
            
            const date = new Date(dateString);
            const options = { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            };
            return date.toLocaleDateString('es-ES', options);
        }
    </script>
</body>
</html>