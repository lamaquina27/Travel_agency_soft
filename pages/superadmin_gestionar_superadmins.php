<?php
// =====================================
// ARCHIVO: pages/superadmin_gestionar_superadmins.php
// Vista para Gestión de Superadministradores (GRUPO 4)
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
    <title>Gestionar Superadministradores - <?= htmlspecialchars($companyName) ?></title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .header {
            background: white;
            border-radius: 15px;
            padding: 25px 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .header-title {
            font-size: 28px;
            font-weight: 700;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .header-title i {
            color: #667eea;
            font-size: 32px;
        }
        
        .header-subtitle {
            color: #718096;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .header-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #e2e8f0;
            color: #4a5568;
        }
        
        .btn-secondary:hover {
            background: #cbd5e0;
        }
        
        .main-container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .info-banner {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-left: 4px solid #f59e0b;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .info-banner i {
            color: #f59e0b;
            font-size: 20px;
            margin-right: 10px;
        }
        
        .info-banner p {
            color: #78350f;
            line-height: 1.6;
            font-size: 14px;
        }
        
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .table-title {
            font-size: 22px;
            font-weight: 700;
            color: #2d3748;
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: #f7fafc;
        }
        
        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #4a5568;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
            color: #2d3748;
        }
        
        tbody tr:hover {
            background: #f7fafc;
        }
        
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .badge-active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-inactive {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .badge-protected {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .btn-action {
            padding: 8px 14px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .btn-edit {
            background: #e0e7ff;
            color: #4338ca;
        }
        
        .btn-edit:hover {
            background: #c7d2fe;
        }
        
        .btn-delete {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .btn-delete:hover {
            background: #fecaca;
        }
        
        .btn-action:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        #loading-container {
            text-align: center;
            padding: 60px 20px;
        }
        
        .spinner {
            border: 4px solid #f3f4f6;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        #empty-state {
            text-align: center;
            padding: 60px 20px;
            display: none;
        }
        
        #empty-state i {
            font-size: 64px;
            color: #cbd5e0;
            margin-bottom: 20px;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1000;
            overflow-y: auto;
            padding: 20px;
        }
        
        .modal-content {
            background: white;
            max-width: 600px;
            margin: 50px auto;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: modalSlideIn 0.3s ease;
        }
        
        @keyframes modalSlideIn {
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
            padding: 25px 30px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .modal-title {
            font-size: 24px;
            font-weight: 700;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .modal-title i {
            color: #667eea;
        }
        
        .modal-body {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-hint {
            font-size: 13px;
            color: #718096;
            margin-top: 5px;
        }
        
        .modal-footer {
            padding: 20px 30px;
            border-top: 2px solid #e2e8f0;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }
        
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 16px 24px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            gap: 12px;
            z-index: 2000;
            animation: slideInRight 0.3s ease;
            max-width: 400px;
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .toast.success {
            border-left: 4px solid #10b981;
        }
        
        .toast.error {
            border-left: 4px solid #ef4444;
        }
        
        .toast i {
            font-size: 24px;
        }
        
        .toast.success i {
            color: #10b981;
        }
        
        .toast.error i {
            color: #ef4444;
        }
        
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .table-container {
                overflow-x: scroll;
            }
            
            .action-buttons {
                flex-direction: column;
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
                    <i class="fas fa-user-shield"></i>
                    Gestionar Superadministradores
                </div>
                <div class="header-subtitle">
                    Crea y administra los usuarios con acceso completo al sistema
                </div>
            </div>
            <div class="header-actions">
                <a href="<?= APP_URL ?>/superadmin/dashboard" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver al Dashboard
                </a>
                <button class="btn btn-primary" onclick="abrirModalNuevo()" id="btn-nuevo-superadmin">
                    <i class="fas fa-plus"></i> Nuevo Superadministrador
                </button>
            </div>
        </div>
    </div>

    <!-- Contenido Principal -->
    <div class="main-container">
        <!-- Banner de información -->
        <div class="info-banner">
            <p>
                <i class="fas fa-info-circle"></i>
                <strong>Importante:</strong> Los superadministradores tienen acceso completo al sistema. 
                El superadministrador original (ID: 1) está protegido y no puede ser editado ni eliminado. 
                Al menos un superadministrador activo debe existir en el sistema en todo momento.
            </p>
        </div>

        <!-- Header de tabla -->
        <div class="table-header">
            <h2 class="table-title">
                <i class="fas fa-users-cog"></i> Lista de Superadministradores
            </h2>
        </div>

        <!-- Loading -->
        <div id="loading-container">
            <div class="spinner"></div>
            <p style="color: #718096;">Cargando superadministradores...</p>
        </div>

        <!-- Tabla -->
        <div id="table-content" style="display: none;">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Nombre Completo</th>
                            <th>Email</th>
                            <th>Fecha de Creación</th>
                            <th>Último Acceso</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="superadmins-tbody">
                        <!-- Contenido dinámico -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Empty State -->
        <div id="empty-state">
            <i class="fas fa-user-shield"></i>
            <h3 style="color: #4a5568; margin-bottom: 10px;">No hay superadministradores</h3>
            <p style="color: #718096;">Comienza creando el primer superadministrador del sistema</p>
        </div>
    </div>

    <!-- Modal: Nuevo Superadmin -->
    <div id="modal-nuevo" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">
                    <i class="fas fa-user-plus"></i>
                    Nuevo Superadministrador
                </h2>
            </div>
            <form id="form-nuevo-superadmin" onsubmit="crearSuperadmin(event)">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="nuevo-email">
                            <i class="fas fa-envelope"></i> Email *
                        </label>
                        <input 
                            type="email" 
                            id="nuevo-email" 
                            name="email" 
                            required 
                            placeholder="superadmin@ejemplo.com"
                        >
                        <div class="form-hint">Este será el email de inicio de sesión</div>
                    </div>

                    <div class="form-group">
                        <label for="nuevo-nombre">
                            <i class="fas fa-user"></i> Nombre Completo *
                        </label>
                        <input 
                            type="text" 
                            id="nuevo-nombre" 
                            name="full_name" 
                            required 
                            placeholder="Ej: Juan Pérez"
                        >
                    </div>

                    <div class="form-group">
                        <label for="nuevo-password">
                            <i class="fas fa-lock"></i> Contraseña *
                        </label>
                        <input 
                            type="password" 
                            id="nuevo-password" 
                            name="password" 
                            required 
                            placeholder="Mínimo 8 caracteres"
                        >
                        <div class="form-hint">Debe contener al menos 8 caracteres</div>
                    </div>

                    <div class="form-group">
                        <label for="nuevo-password-confirm">
                            <i class="fas fa-lock"></i> Confirmar Contraseña *
                        </label>
                        <input 
                            type="password" 
                            id="nuevo-password-confirm" 
                            name="password_confirm" 
                            required 
                            placeholder="Repite la contraseña"
                        >
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModalNuevo()">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="btn-guardar-nuevo">
                        <i class="fas fa-save"></i> Crear Superadministrador
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Editar Superadmin -->
    <div id="modal-editar" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">
                    <i class="fas fa-user-edit"></i>
                    Editar Superadministrador
                </h2>
            </div>
            <form id="form-editar-superadmin" onsubmit="actualizarSuperadmin(event)">
                <input type="hidden" id="editar-id" name="user_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="editar-email">
                            <i class="fas fa-envelope"></i> Email *
                        </label>
                        <input 
                            type="email" 
                            id="editar-email" 
                            name="email" 
                            required 
                            placeholder="superadmin@ejemplo.com"
                        >
                    </div>

                    <div class="form-group">
                        <label for="editar-nombre">
                            <i class="fas fa-user"></i> Nombre Completo *
                        </label>
                        <input 
                            type="text" 
                            id="editar-nombre" 
                            name="full_name" 
                            required 
                            placeholder="Ej: Juan Pérez"
                        >
                    </div>

                    <div class="form-group">
                        <label for="editar-password">
                            <i class="fas fa-lock"></i> Nueva Contraseña (opcional)
                        </label>
                        <input 
                            type="password" 
                            id="editar-password" 
                            name="password" 
                            placeholder="Dejar en blanco para no cambiar"
                        >
                        <div class="form-hint">Solo completa si deseas cambiar la contraseña</div>
                    </div>

                    <div class="form-group">
                        <label for="editar-password-confirm">
                            <i class="fas fa-lock"></i> Confirmar Nueva Contraseña
                        </label>
                        <input 
                            type="password" 
                            id="editar-password-confirm" 
                            name="password_confirm" 
                            placeholder="Confirma la nueva contraseña"
                        >
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModalEditar()">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="btn-guardar-editar">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const APP_URL = '<?= APP_URL ?>';
        const API_URL = APP_URL + '/modules/superadmin/usuarios_api.php';
        const CURRENT_USER_ID = <?= $user['id'] ?>;
        
        let superadmins = [];
        
        document.addEventListener('DOMContentLoaded', function() {
            cargarSuperadmins();
        });
        
        async function cargarSuperadmins() {
            try {
                const response = await fetch(`${API_URL}?action=list_superadmins`);
                const data = await response.json();
                
                if (data.success) {
                    superadmins = data.superadmins;
                    mostrarSuperadmins();
                } else {
                    mostrarError('Error al cargar superadministradores: ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarError('Error de conexión al cargar los superadministradores');
            }
        }
        
        function mostrarSuperadmins() {
            const tbody = document.getElementById('superadmins-tbody');
            const loadingContainer = document.getElementById('loading-container');
            const tableContent = document.getElementById('table-content');
            const emptyState = document.getElementById('empty-state');
            
            loadingContainer.style.display = 'none';
            
            if (superadmins.length === 0) {
                tableContent.style.display = 'none';
                emptyState.style.display = 'block';
                return;
            }
            
            emptyState.style.display = 'none';
            tableContent.style.display = 'block';
            
            tbody.innerHTML = superadmins.map(admin => {
                const isProtected = admin.id == 1;
                const isCurrentUser = admin.id == CURRENT_USER_ID;
                
                return `
                <tr>
                    <td>
                        <strong>${escapeHtml(admin.full_name)}</strong>
                        ${isProtected ? '<span class="badge badge-protected" style="margin-left: 8px;">Original</span>' : ''}
                    </td>
                    <td>${escapeHtml(admin.email)}</td>
                    <td>${formatDate(admin.created_at)}</td>
                    <td>${formatDate(admin.last_login) || 'Nunca'}</td>
                    <td>
                        <span class="badge badge-${admin.active == 1 ? 'active' : 'inactive'}">
                            ${admin.active == 1 ? 'Activo' : 'Inactivo'}
                        </span>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button 
                                class="btn-action btn-edit" 
                                onclick="abrirModalEditar(${admin.id})"
                                ${isProtected ? 'disabled title="El superadmin original no puede ser editado"' : ''}
                            >
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            <button 
                                class="btn-action btn-delete" 
                                onclick="eliminarSuperadmin(${admin.id}, '${escapeHtml(admin.full_name)}')"
                                ${isProtected || isCurrentUser ? 'disabled' : ''}
                                title="${isProtected ? 'El superadmin original no puede ser eliminado' : isCurrentUser ? 'No puedes eliminarte a ti mismo' : 'Eliminar superadministrador'}"
                            >
                                <i class="fas fa-trash"></i> Eliminar
                            </button>
                        </div>
                    </td>
                </tr>
                `;
            }).join('');
        }
        
        function abrirModalNuevo() {
            document.getElementById('modal-nuevo').style.display = 'block';
            document.getElementById('form-nuevo-superadmin').reset();
        }
        
        function cerrarModalNuevo() {
            document.getElementById('modal-nuevo').style.display = 'none';
        }
        
        async function crearSuperadmin(event) {
            event.preventDefault();
            
            const form = event.target;
            const formData = new FormData(form);
            
            const password = formData.get('password');
            const passwordConfirm = formData.get('password_confirm');
            
            if (password !== passwordConfirm) {
                mostrarError('Las contraseñas no coinciden');
                return;
            }
            
            formData.append('action', 'create_superadmin');
            
            const btnGuardar = document.getElementById('btn-guardar-nuevo');
            btnGuardar.disabled = true;
            btnGuardar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creando...';
            
            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    mostrarExito('Superadministrador creado exitosamente');
                    cerrarModalNuevo();
                    cargarSuperadmins();
                } else {
                    mostrarError(data.message || 'Error al crear el superadministrador');
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarError('Error de conexión al crear el superadministrador');
            } finally {
                btnGuardar.disabled = false;
                btnGuardar.innerHTML = '<i class="fas fa-save"></i> Crear Superadministrador';
            }
        }
        
        function abrirModalEditar(userId) {
            const admin = superadmins.find(a => a.id == userId);
            if (!admin) return;
            
            document.getElementById('editar-id').value = admin.id;
            document.getElementById('editar-email').value = admin.email;
            document.getElementById('editar-nombre').value = admin.full_name;
            document.getElementById('editar-password').value = '';
            document.getElementById('editar-password-confirm').value = '';
            
            document.getElementById('modal-editar').style.display = 'block';
        }
        
        function cerrarModalEditar() {
            document.getElementById('modal-editar').style.display = 'none';
        }
        
        async function actualizarSuperadmin(event) {
            event.preventDefault();
            
            const form = event.target;
            const formData = new FormData(form);
            
            const password = formData.get('password');
            const passwordConfirm = formData.get('password_confirm');
            
            if (password && password !== passwordConfirm) {
                mostrarError('Las contraseñas no coinciden');
                return;
            }
            
            formData.append('action', 'update_superadmin');
            
            const btnGuardar = document.getElementById('btn-guardar-editar');
            btnGuardar.disabled = true;
            btnGuardar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
            
            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    mostrarExito('Superadministrador actualizado exitosamente');
                    cerrarModalEditar();
                    cargarSuperadmins();
                } else {
                    mostrarError(data.message || 'Error al actualizar el superadministrador');
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarError('Error de conexión al actualizar el superadministrador');
            } finally {
                btnGuardar.disabled = false;
                btnGuardar.innerHTML = '<i class="fas fa-save"></i> Guardar Cambios';
            }
        }
        
        async function eliminarSuperadmin(userId, nombre) {
            if (!confirm(`¿Estás seguro de eliminar al superadministrador "${nombre}"?\n\nEsta acción no se puede deshacer.`)) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'delete_superadmin');
            formData.append('user_id', userId);
            
            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    mostrarExito('Superadministrador eliminado exitosamente');
                    cargarSuperadmins();
                } else {
                    mostrarError(data.message || 'Error al eliminar el superadministrador');
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarError('Error de conexión al eliminar el superadministrador');
            }
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString('es-ES', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        function mostrarExito(mensaje) {
            mostrarToast(mensaje, 'success');
        }
        
        function mostrarError(mensaje) {
            mostrarToast(mensaje, 'error');
        }
        
        function mostrarToast(mensaje, tipo) {
            const toast = document.createElement('div');
            toast.className = `toast ${tipo}`;
            
            const icon = tipo === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
            
            toast.innerHTML = `
                <i class="fas ${icon}"></i>
                <span>${mensaje}</span>
            `;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.remove();
            }, 4000);
        }
        
        window.onclick = function(event) {
            const modalNuevo = document.getElementById('modal-nuevo');
            const modalEditar = document.getElementById('modal-editar');
            
            if (event.target === modalNuevo) {
                cerrarModalNuevo();
            }
            if (event.target === modalEditar) {
                cerrarModalEditar();
            }
        };
    </script>
</body>
</html>