<?php
// =====================================
// ARCHIVO: includes/ui_components.php - Sistema de Componentes UI Recursivos
// =====================================

// Incluir constantes adicionales
require_once __DIR__ . '/../config/constants.php';

class UIComponents {
    
    /**
     * Renderiza el logo de la empresa de forma recursiva
     * @param string $size - 'small', 'medium', 'large', 'extra-large'
     * @param array $options - Opciones adicionales de customización
     * @return string HTML del logo
     */
    public static function renderLogo($size = 'medium', $options = []) {
        $companyName = App::getCompanyName();
        $logo = App::getLogo();
        
        // Configuración de tamaños
        $sizes = [
            'small' => ['width' => '32px', 'height' => '32px', 'font' => '12px'],
            'medium' => ['width' => '48px', 'height' => '48px', 'font' => '16px'],
            'large' => ['width' => '64px', 'height' => '64px', 'font' => '20px'],
            'extra-large' => ['width' => '80px', 'height' => '80px', 'font' => '24px'],
            'custom' => $options['custom_size'] ?? ['width' => '48px', 'height' => '48px', 'font' => '16px']
        ];
        
        $sizeConfig = $sizes[$size] ?? $sizes['medium'];
        
        // Opciones adicionales
        $borderRadius = $options['border_radius'] ?? '12px';
        $shadow = $options['shadow'] ?? 'rgba(0, 0, 0, 0.1)';
        $hoverEffect = $options['hover_effect'] ?? true;
        $gradient = $options['gradient'] ?? 'var(--primary-gradient)';
        $className = $options['class'] ?? '';
        
        // Si el gradiente es transparent, no mostrar fondo ni sombra
        $backgroundStyle = $gradient === 'transparent' ? 'transparent' : $gradient;
        $shadowStyle = $gradient === 'transparent' ? 'none' : '0 4px 15px ' . $shadow;
        
        $logoHtml = '<div class="company-logo-component ' . $className . '" style="
            width: ' . $sizeConfig['width'] . ';
            height: ' . $sizeConfig['height'] . ';
            background: ' . $backgroundStyle . ';
            border-radius: ' . $borderRadius . ';
            display: flex;
            align-items: center;
            justify-content: center;
            color: ' . ($gradient === 'transparent' ? 'var(--primary-color)' : 'white') . ';
            font-weight: bold;
            font-size: ' . $sizeConfig['font'] . ';
            overflow: hidden;
            box-shadow: ' . $shadowStyle . ';
            transition: all 0.3s ease;
            position: relative;
        "';
        
        if ($hoverEffect && $gradient !== 'transparent') {
            $logoHtml .= ' onmouseover="this.style.transform=\'scale(1.05)\'; this.style.boxShadow=\'0 8px 25px ' . $shadow . '\'"';
            $logoHtml .= ' onmouseout="this.style.transform=\'scale(1)\'; this.style.boxShadow=\'0 4px 15px ' . $shadow . '\'"';
        }
        
        $logoHtml .= '>';
        
        if ($logo && fileExists($logo)) {
            $logoUrl = getPublicUrl($logo);
            $logoHtml .= '<img src="' . htmlspecialchars($logoUrl) . '" 
                               alt="' . htmlspecialchars($companyName) . '" 
                               style="width: 100%; height: 100%; object-fit: cover; border-radius: ' . $borderRadius . ';">';
        } else {
            $logoHtml .= '<span style="font-weight: 700; letter-spacing: 1px;">' . 
                         strtoupper(substr($companyName, 0, 2)) . '</span>';
        }
        
        $logoHtml .= '</div>';
        
        return $logoHtml;
    }
    
    /**
     * Renderiza la barra lateral completa con navegación basada en roles
     * @param array $user - Información del usuario actual
     * @param string $currentPage - Página actual para marcar como activa
     * @return string HTML de la sidebar
     */
    public static function renderSidebar($user, $currentPage = '') {
        ConfigManager::init();
        $companyName = ConfigManager::getCompanyName();
        $logo = ConfigManager::getLogo();
        $userColors = App::getColorsForRole($user['role']);
        
        $sidebarHtml = '
        <div class="enhanced-sidebar" id="sidebar">
            <div class="sidebar-header-enhanced">
                ' . self::renderLogo('large', [
                    'border_radius' => '16px',
                    'shadow' => 'rgba(0, 0, 0, 0.15)',
                    'class' => 'sidebar-logo',
                    'gradient' => 'transparent'
                ]) . '
                <div class="company-info">
                    <h3 class="company-name">' . htmlspecialchars($companyName) . '</h3>
                    <div class="role-indicator">
                        <span class="role-badge-sidebar ' . $user['role'] . '">
                            ' . ($user['role'] === 'admin' ? '👑 Administrador' : '✈️ Agente de Viajes') . '
                        </span>
                    </div>
                </div>
                <div class="user-info-sidebar">
                    <div class="user-avatar-sidebar">' . strtoupper(substr($user['name'], 0, 2)) . '</div>
                    <div class="user-details">
                        <div class="user-name">' . htmlspecialchars($user['name']) . '</div>
                        <div class="user-status">
                            <span class="status-dot"></span>
                            En línea
                        </div>
                    </div>
                </div>
            </div>

            <nav class="sidebar-menu-enhanced">
                ' . self::renderMenuItems($user['role'], $currentPage) . '
            </nav>
        </div>';
        
        return $sidebarHtml;
    }
    
    /**
     * Genera los elementos del menú según el rol del usuario
     * @param string $role - Rol del usuario (admin/agent)
     * @param string $currentPage - Página actual
     * @return string HTML de los elementos del menú
     */
    private static function renderMenuItems($role, $currentPage) {
        $menuItems = [];
        
        // Dashboard siempre presente para ambos roles
        $menuItems[] = [
            'url' => '/dashboard',
            'icon' => '🏠',
            'title' => 'Dashboard',
            'description' => 'Panel principal del sistema',
            'gradient' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'
        ];
        
        if ($role === 'admin') {
            // Menú COMPLETO para Administrador
            $menuItems = array_merge($menuItems, [
                [
                    'url' => '/administrador',
                    'icon' => '👥',
                    'title' => 'Sistema de Usuarios',
                    'description' => 'Gestión completa de usuarios',
                    'gradient' => 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
                    'badge' => 'Admin'
                ],
                [
                    'url' => '/administrador/configuracion',
                    'icon' => '⚙️',
                    'title' => 'Configuración de Sistema',
                    'description' => 'Ajustes y personalización global',
                    'gradient' => 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
                    'badge' => 'Config'
                ],
                [
                    'url' => '/biblioteca',
                    'icon' => '📚',
                    'title' => 'Supervisar Biblioteca',
                    'description' => 'Administrar recursos globales',
                    'gradient' => 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)'
                ],
                [
                    'url' => '/itinerarios',
                    'icon' => '🗺️',
                    'title' => 'Gestión de Itinerarios',
                    'description' => 'Administrar todos los itinerarios',
                    'gradient' => 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)'
                ]
            ]);
        } else {
            // Menú LIMITADO para Agente - Solo las opciones específicas
            $menuItems = array_merge($menuItems, [
                [
                    'url' => '/itinerarios',
                    'icon' => '🗺️',
                    'title' => 'Mis Itinerarios',
                    'description' => 'Crear y gestionar mis itinerarios',
                    'gradient' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'
                ],
                [
                    'url' => '/biblioteca',
                    'icon' => '📚',
                    'title' => 'Biblioteca',
                    'description' => 'Mis recursos y materiales',
                    'gradient' => 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)'
                ],
                [
                    'url' => '/perfil',
                    'icon' => '👤',
                    'title' => 'Mi Perfil',
                    'description' => 'Configuración personal',
                    'gradient' => 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)'
                ]
            ]);
        }
        
        // Agregar logout al final para ambos roles
        $menuItems[] = [
            'url' => '/auth/logout',
            'icon' => '🚪',
            'title' => 'Cerrar Sesión',
            'description' => 'Salir del sistema',
            'gradient' => 'linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%)',
            'special' => 'logout'
        ];
        
        $menuHtml = '';
        foreach ($menuItems as $item) {
            $isActive = strpos($_SERVER['REQUEST_URI'], $item['url']) !== false || $currentPage === $item['url'];
            $activeClass = $isActive ? 'active' : '';
            $specialClass = isset($item['special']) ? 'menu-item-' . $item['special'] : '';
            
            $menuHtml .= '
            <a href="' . APP_URL . $item['url'] . '" class="menu-item-enhanced ' . $activeClass . ' ' . $specialClass . '">
                <div class="menu-item-icon" style="background: ' . $item['gradient'] . ';">
                    ' . $item['icon'] . '
                </div>
                <div class="menu-item-content">
                    <div class="menu-item-title">' . $item['title'] . '</div>
                    <div class="menu-item-description">' . $item['description'] . '</div>
                </div>';
            
            if (isset($item['badge'])) {
                $menuHtml .= '<div class="menu-item-badge">' . $item['badge'] . '</div>';
            }
            
            if ($isActive) {
                $menuHtml .= '<div class="active-indicator"></div>';
            }
            
            $menuHtml .= '</a>';
        }
        
        return $menuHtml;
    }
    
    /**
     * Genera los estilos CSS para los componentes
     * @return string CSS de los componentes
     */
    public static function getComponentStyles() {
        return '
        <style>
        /* ===== SISTEMA DE MODALES ESTÉTICOS ===== */
        .confirm-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .confirm-modal-overlay.show {
            opacity: 1;
            visibility: visible;
        }

        .confirm-modal {
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
            max-width: 450px;
            width: 90%;
            overflow: hidden;
            transform: scale(0.8) translateY(50px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .confirm-modal-overlay.show .confirm-modal {
            transform: scale(1) translateY(0);
        }

        .confirm-modal-header {
            background: var(--primary-gradient, linear-gradient(135deg, #667eea 0%, #764ba2 100%));
            color: white;
            padding: 25px 30px 20px;
            text-align: center;
            position: relative;
        }

        .confirm-modal-icon {
            font-size: 48px;
            margin-bottom: 15px;
            display: block;
            animation: pulse 2s infinite;
        }

        .confirm-modal-title {
            font-size: 22px;
            font-weight: 600;
            margin: 0;
            line-height: 1.3;
        }

        .confirm-modal-body {
            padding: 30px;
            text-align: center;
        }

        .confirm-modal-message {
            font-size: 16px;
            line-height: 1.6;
            color: #374151;
            margin-bottom: 25px;
        }

        .confirm-modal-details {
            background: #f8fafc;
            border-radius: 12px;
            padding: 15px;
            margin: 20px 0;
            font-size: 14px;
            color: #6b7280;
            border-left: 4px solid var(--primary-color, #667eea);
        }

        .confirm-modal-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-top: 25px;
        }

        .confirm-modal-btn {
            border: none;
            border-radius: 12px;
            padding: 14px 28px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            min-width: 120px;
            position: relative;
            overflow: hidden;
        }

        .confirm-modal-btn-confirm {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4);
        }

        .confirm-modal-btn-confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.5);
        }

        .confirm-modal-btn-cancel {
            background: #f3f4f6;
            color: #374151;
            border: 2px solid #e5e7eb;
        }

        .confirm-modal-btn-cancel:hover {
            background: #e5e7eb;
            transform: translateY(-1px);
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .confirm-modal {
                max-width: 95%;
                margin: 20px;
            }
            
            .confirm-modal-header {
                padding: 20px 25px 15px;
            }
            
            .confirm-modal-icon {
                font-size: 40px;
                margin-bottom: 10px;
            }
            
            .confirm-modal-title {
                font-size: 20px;
            }
            
            .confirm-modal-body {
                padding: 25px 20px;
            }
            
            .confirm-modal-actions {
                flex-direction: column;
            }
            
            .confirm-modal-btn {
                min-width: 100%;
            }
        }
        /* Enhanced Sidebar Styles */
        .enhanced-sidebar {
            position: fixed;
            left: -320px;
            top: 70px;
            width: 320px;
            height: calc(100vh - 70px);
            background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
            box-shadow: 4px 0 25px rgba(0, 0, 0, 0.1);
            transition: left 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1000;
            overflow-y: auto;
            border-right: 1px solid rgba(0, 0, 0, 0.05);
        }

        .enhanced-sidebar.open {
            left: 0;
        }

        .sidebar-header-enhanced {
            padding: 30px 25px;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            text-align: center;
        }

        .sidebar-logo {
            margin: 0 auto 20px auto;
            background: transparent !important;
            box-shadow: none !important;
        }

        .sidebar-logo img {
            border-radius: 16px;
        }

        .sidebar-logo span {
            color: var(--primary-color) !important;
            background: transparent !important;
            font-weight: 700;
            font-size: 24px;
        }

        .company-info {
            margin-bottom: 20px;
        }

        .company-name {
            font-size: 20px;
            font-weight: 700;
            color: #2d3748;
            margin: 0 0 8px 0;
            letter-spacing: -0.5px;
        }

        .role-badge-sidebar {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .role-badge-sidebar.admin {
            background: linear-gradient(135deg, #fed7d7 0%, #feb2b2 100%);
            color: #c53030;
        }

        .role-badge-sidebar.agent {
            background: linear-gradient(135deg, #bee3f8 0%, #90cdf4 100%);
            color: #2b6cb0;
        }

        .user-info-sidebar {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 12px;
            backdrop-filter: blur(10px);
        }

        .user-avatar-sidebar {
            width: 40px;
            height: 40px;
            background: var(--primary-gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 14px;
        }

        .user-details {
            flex: 1;
        }

        .user-name {
            font-size: 14px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 2px;
        }

        .user-status {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: #68d391;
        }

        .status-dot {
            width: 6px;
            height: 6px;
            background: #68d391;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* Enhanced Menu Items */
        .sidebar-menu-enhanced {
            padding: 20px 0 40px 0;
            flex: 1;
        }

        .menu-item-enhanced {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 25px;
            color: #4a5568;
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
            margin: 0 15px 8px 15px;
            border-radius: 12px;
        }

        .menu-item-enhanced:hover {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.08) 0%, rgba(118, 75, 162, 0.08) 100%);
            transform: translateX(5px);
        }

        .menu-item-enhanced.active {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.15) 0%, rgba(118, 75, 162, 0.15) 100%);
            color: var(--primary-color);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
        }

        .menu-item-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .menu-item-content {
            flex: 1;
        }

        .menu-item-title {
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 2px;
        }

        .menu-item-description {
            font-size: 12px;
            color: #718096;
            line-height: 1.3;
        }

        .menu-item-badge {
            background: var(--primary-gradient);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
        }

        .active-indicator {
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 60%;
            background: var(--primary-gradient);
            border-radius: 2px 0 0 2px;
        }

        .menu-item-logout {
            margin-top: 20px;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            padding-top: 20px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .enhanced-sidebar {
                width: 100%;
                left: -100%;
            }
            
            .sidebar-header-enhanced {
                padding: 20px;
            }
            
            .menu-item-enhanced {
                margin: 0 10px 6px 10px;
                padding: 12px 15px;
            }
        }
        </style>

        <script>
        // ===== SISTEMA DE MODALES DE CONFIRMACIÓN ESTÉTICOS =====
        
        function showConfirmModal(options = {}) {
            return new Promise((resolve) => {
                const config = {
                    title: options.title || "¿Confirmar acción?",
                    message: options.message || "¿Estás seguro de que quieres continuar?",
                    details: options.details || null,
                    icon: options.icon || "⚠️",
                    confirmText: options.confirmText || "Confirmar",
                    cancelText: options.cancelText || "Cancelar",
                    confirmButtonStyle: options.confirmButtonStyle || "danger"
                };

                let modal = document.getElementById("globalConfirmModal");
                if (!modal) {
                    modal = createConfirmModal();
                    document.body.appendChild(modal);
                }

                updateModalContent(modal, config);
                modal.classList.add("show");
                document.body.style.overflow = "hidden";

                const confirmBtn = modal.querySelector(".confirm-modal-btn-confirm");
                const cancelBtn = modal.querySelector(".confirm-modal-btn-cancel");

                confirmBtn.replaceWith(confirmBtn.cloneNode(true));
                cancelBtn.replaceWith(cancelBtn.cloneNode(true));

                const newConfirmBtn = modal.querySelector(".confirm-modal-btn-confirm");
                const newCancelBtn = modal.querySelector(".confirm-modal-btn-cancel");

                newConfirmBtn.addEventListener("click", () => {
                    hideConfirmModal(modal);
                    resolve(true);
                });

                newCancelBtn.addEventListener("click", () => {
                    hideConfirmModal(modal);
                    resolve(false);
                });

                const escHandler = (e) => {
                    if (e.key === "Escape") {
                        hideConfirmModal(modal);
                        resolve(false);
                        document.removeEventListener("keydown", escHandler);
                    }
                };
                document.addEventListener("keydown", escHandler);

                modal.addEventListener("click", (e) => {
                    if (e.target === modal) {
                        hideConfirmModal(modal);
                        resolve(false);
                    }
                });
            });
        }

        function createConfirmModal() {
            const modal = document.createElement("div");
            modal.id = "globalConfirmModal";
            modal.className = "confirm-modal-overlay";
            
            modal.innerHTML = `
                <div class="confirm-modal">
                    <div class="confirm-modal-header">
                        <span class="confirm-modal-icon"></span>
                        <h3 class="confirm-modal-title"></h3>
                    </div>
                    <div class="confirm-modal-body">
                        <div class="confirm-modal-message"></div>
                        <div class="confirm-modal-details" style="display: none;"></div>
                        <div class="confirm-modal-actions">
                            <button class="confirm-modal-btn confirm-modal-btn-cancel">Cancelar</button>
                            <button class="confirm-modal-btn confirm-modal-btn-confirm">Confirmar</button>
                        </div>
                    </div>
                </div>
            `;
            
            return modal;
        }

        function updateModalContent(modal, config) {
            modal.querySelector(".confirm-modal-icon").textContent = config.icon;
            modal.querySelector(".confirm-modal-title").textContent = config.title;
            modal.querySelector(".confirm-modal-message").textContent = config.message;
            
            const detailsEl = modal.querySelector(".confirm-modal-details");
            if (config.details) {
                detailsEl.textContent = config.details;
                detailsEl.style.display = "block";
            } else {
                detailsEl.style.display = "none";
            }
            
            const confirmBtn = modal.querySelector(".confirm-modal-btn-confirm");
            const cancelBtn = modal.querySelector(".confirm-modal-btn-cancel");
            
            confirmBtn.textContent = config.confirmText;
            cancelBtn.textContent = config.cancelText;
        }

        function hideConfirmModal(modal) {
            modal.classList.remove("show");
            document.body.style.overflow = "";
            
            setTimeout(() => {
                if (modal.parentNode) {
                    modal.parentNode.removeChild(modal);
                }
            }, 300);
        }
        </script>';
    }
    
    /**
     * Renderiza el header con logo y controles
     * @param array $user - Información del usuario
     * @return string HTML del header
     */
    public static function renderHeader($user) {
        ConfigManager::init();
        $companyName = ConfigManager::getCompanyName();
        $logo = ConfigManager::getLogo();
        $defaultLanguage = App::getDefaultLanguage();
        
        return '
        <div class="header">
            <div class="header-left">
                <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
                ' . self::renderLogo('small', ['class' => 'header-logo']) . '
                <h2 style="margin-left: 15px;">' . htmlspecialchars($companyName) . '</h2>
            </div>
            
            <div class="header-center">
                <div id="google_translate_element"></div>
            </div>
    
            <div class="header-right">
                <div class="user-info" onclick="toggleUserMenu()">
                    <div class="user-avatar" translate="no">' . strtoupper(substr($user['name'], 0, 2)) . '</div>
                    <div>
                        <div style="font-size: 14px; font-weight: 500;">' . htmlspecialchars($user['name']) . '</div>
                        <div style="font-size: 12px; opacity: 0.8;">' . ($user['role'] === 'admin' ? 'Administrador' : 'Agente de Viajes') . '</div>
                    </div>
                </div>
            </div>
        </div>
        <script>
        (function() {
            setInterval(async function() {
                try {
                    const res = await fetch("' . APP_URL . '/auth/ping");
                    if (res.status === 401) {
                        alert("Tu sesión ha expirado. Serás redirigido al login.");
                        window.location.href = "' . APP_URL . '/login";
                    }
                } catch(e) {}
            }, 4 * 60 * 1000);
        })();
        </script>';
    }
}