<?php
// =====================================
// ARCHIVO: pages/superadmin_dashboard.php - Dashboard del Superadministrador (GRUPO 1)
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
    <title>Panel de Superadministrador - <?= htmlspecialchars($companyName) ?></title>
    
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
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-name {
            font-weight: 600;
        }
        
        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        /* Main Content */
        .main-container {
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 40px;
        }
        
        /* Welcome Section */
        .welcome-section {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 40px;
        }
        
        .welcome-title {
            font-size: 32px;
            color: #1a202c;
            margin-bottom: 10px;
        }
        
        .welcome-text {
            font-size: 16px;
            color: #718096;
            line-height: 1.6;
        }
        
        /* Menu Cards */
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }
        
        .menu-card {
            background: white;
            padding: 35px;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .menu-card-icon {
            font-size: 48px;
            color: #667eea;
            margin-bottom: 20px;
        }
        
        .menu-card-title {
            font-size: 22px;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 10px;
        }
        
        .menu-card-description {
            font-size: 15px;
            color: #718096;
            line-height: 1.5;
        }
        
        .menu-card.disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .menu-card.disabled:hover {
            transform: none;
        }
        
        .badge-soon {
            display: inline-block;
            background: #fbbf24;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }
        
        /* Info Message */
        .info-message {
            background: #e0e7ff;
            border-left: 4px solid #667eea;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 40px;
        }
        
        .info-message p {
            color: #3730a3;
            font-size: 15px;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <div>
                <div class="header-title">
                    <i class="fas fa-crown"></i> Panel de Superadministrador
                </div>
                <div class="header-subtitle"><?= htmlspecialchars($companyName) ?></div>
            </div>
            <div class="user-info">
                <div>
                    <div class="user-name"><?= htmlspecialchars($user['name']) ?></div>
                    <div style="font-size: 12px; opacity: 0.8;">Superadministrador</div>
                </div>
                <a href="<?= APP_URL ?>/auth/logout" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-container">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <h1 class="welcome-title">¡Bienvenido, <?= htmlspecialchars($user['name']) ?>!</h1>
            <p class="welcome-text">
                Desde este panel puedes gestionar todas las agencias del sistema, crear y administrar usuarios, 
                y tener control total sobre la plataforma. Selecciona una opción del menú para comenzar.
            </p>
        </div>

        <!-- Menu Grid -->
        <div class="menu-grid">
            <!-- Gestionar Agencias -->
            <div class="menu-card" onclick="goTo('/superadmin/agencias')">
                <div class="menu-card-icon">
                    <i class="fas fa-building"></i>
                </div>
                <h2 class="menu-card-title">
                    Gestionar Agencias
                </h2>
                <p class="menu-card-description">
                    Crear, editar y administrar las agencias del sistema. 
                    Gestionar suscripciones y configuraciones de cada agencia.
                </p>
            </div>


            

            <!-- Gestionar Superadministradores (GRUPO 4) -->
            <div class="menu-card" onclick="goTo('/superadmin/gestionar-superadmins')">
                <div class="menu-card-icon">
                    <i class="fas fa-user-shield"></i>
                </div>
                <h2 class="menu-card-title">
                    Superadministradores
                    <span class="badge-new">Nuevo</span>
                </h2>
                <p class="menu-card-description">
                    Crear y gestionar otros usuarios superadministradores del sistema. 
                    Control total sobre los accesos al panel administrativo.
                </p>
            </div>
        </div>
    </div>
</body>
   <script>
        function goTo(url) {
            window.location.href = '<?= APP_URL ?>' + url;
        }
    </script>
</html>