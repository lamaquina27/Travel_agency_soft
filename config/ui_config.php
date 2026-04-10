<?php
// =====================================
// ARCHIVO: config/ui_config.php - Configuración Avanzada de Componentes UI
// =====================================

class UIConfig {
    
    /**
     * Configuraciones de logo por defecto
     */
    const LOGO_DEFAULTS = [
        'sizes' => [
            'tiny' => ['width' => '24px', 'height' => '24px', 'font' => '10px'],
            'small' => ['width' => '32px', 'height' => '32px', 'font' => '12px'],
            'medium' => ['width' => '48px', 'height' => '48px', 'font' => '16px'],
            'large' => ['width' => '64px', 'height' => '64px', 'font' => '20px'],
            'extra-large' => ['width' => '80px', 'height' => '80px', 'font' => '24px'],
            'massive' => ['width' => '120px', 'height' => '120px', 'font' => '32px']
        ],
        'border_radius' => '12px',
        'shadow' => 'rgba(0, 0, 0, 0.1)',
        'hover_effect' => true
    ];

    /**
     * Configuraciones de sidebar por rol
     */
    const SIDEBAR_CONFIG = [
        'admin' => [
            'width' => '320px',
            'header_gradient' => 'linear-gradient(135deg, rgba(229, 62, 62, 0.05) 0%, rgba(253, 116, 108, 0.05) 100%)',
            'role_badge_style' => [
                'background' => 'linear-gradient(135deg, #fed7d7 0%, #feb2b2 100%)',
                'color' => '#c53030'
            ]
        ],
        'agent' => [
            'width' => '320px',
            'header_gradient' => 'linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%)',
            'role_badge_style' => [
                'background' => 'linear-gradient(135deg, #bee3f8 0%, #90cdf4 100%)',
                'color' => '#2b6cb0'
            ]
        ]
    ];

    /**
     * Gradientes predefinidos para iconos de menú
     */
    const MENU_GRADIENTS = [
        'blue' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
        'pink' => 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
        'cyan' => 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
        'green' => 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
        'orange' => 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
        'purple' => 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)',
        'yellow' => 'linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%)',
        'red' => 'linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%)'
    ];

    /**
     * Obtiene la configuración del logo según el tamaño
     */
    public static function getLogoConfig($size = 'medium', $overrides = []) {
        $config = self::LOGO_DEFAULTS;
        $sizeConfig = $config['sizes'][$size] ?? $config['sizes']['medium'];
        
        return array_merge([
            'width' => $sizeConfig['width'],
            'height' => $sizeConfig['height'],
            'font' => $sizeConfig['font'],
            'border_radius' => $config['border_radius'],
            'shadow' => $config['shadow'],
            'hover_effect' => $config['hover_effect']
        ], $overrides);
    }

    /**
     * Obtiene la configuración de sidebar según el rol
     */
    public static function getSidebarConfig($role) {
        return self::SIDEBAR_CONFIG[$role] ?? self::SIDEBAR_CONFIG['agent'];
    }

    /**
     * Obtiene un gradiente predefinido
     */
    public static function getGradient($name) {
        return self::MENU_GRADIENTS[$name] ?? self::MENU_GRADIENTS['blue'];
    }

    /**
     * Genera CSS custom properties dinámicas
     */
    public static function generateCSSVariables($userRole, $userColors) {
        $sidebarConfig = self::getSidebarConfig($userRole);
        
        return "
        :root {
            --primary-color: {$userColors['primary']};
            --secondary-color: {$userColors['secondary']};
            --primary-gradient: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            --sidebar-width: {$sidebarConfig['width']};
            --sidebar-header-bg: {$sidebarConfig['header_gradient']};
            --role-badge-bg: {$sidebarConfig['role_badge_style']['background']};
            --role-badge-color: {$sidebarConfig['role_badge_style']['color']};
        }";
    }

    /**
     * Configuración de animaciones
     */
    const ANIMATIONS = [
        'slide_in_up' => [
            'name' => 'slideInUp',
            'duration' => '0.6s',
            'easing' => 'ease',
            'keyframes' => '
                @keyframes slideInUp {
                    from {
                        opacity: 0;
                        transform: translateY(30px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }'
        ],
        'fade_in' => [
            'name' => 'fadeIn',
            'duration' => '0.4s',
            'easing' => 'ease',
            'keyframes' => '
                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }'
        ],
        'scale_in' => [
            'name' => 'scaleIn',
            'duration' => '0.3s',
            'easing' => 'cubic-bezier(0.4, 0, 0.2, 1)',
            'keyframes' => '
                @keyframes scaleIn {
                    from { 
                        opacity: 0; 
                        transform: scale(0.8);
                    }
                    to { 
                        opacity: 1; 
                        transform: scale(1);
                    }
                }'
        ]
    ];

    /**
     * Genera CSS de animaciones
     */
    public static function getAnimationCSS($animations = ['slide_in_up', 'fade_in', 'scale_in']) {
        $css = '';
        foreach ($animations as $animName) {
            if (isset(self::ANIMATIONS[$animName])) {
                $css .= self::ANIMATIONS[$animName]['keyframes'] . "\n";
            }
        }
        return $css;
    }

    /**
     * Configuración de breakpoints responsive
     */
    const BREAKPOINTS = [
        'mobile' => '480px',
        'tablet' => '768px',
        'desktop' => '1024px',
        'large' => '1200px'
    ];

    /**
     * Genera media queries responsive
     */
    public static function getResponsiveCSS() {
        return "
        @media (max-width: " . self::BREAKPOINTS['tablet'] . ") {
            .enhanced-sidebar {
                width: 100%;
                left: -100%;
            }
            
            .main-content.sidebar-open {
                margin-left: 0;
            }
            
            .header {
                padding: 15px 20px;
            }
            
            .main-content {
                padding: 20px;
            }
        }
        
        @media (max-width: " . self::BREAKPOINTS['mobile'] . ") {
            .sidebar-header-enhanced {
                padding: 20px;
            }
            
            .menu-item-enhanced {
                margin: 0 10px 6px 10px;
                padding: 12px 15px;
            }
            
            .page-header, .content-section {
                padding: 20px;
            }
        }";
    }

    /**
     * Temas predefinidos para diferentes secciones
     */
    const THEMES = [
        'dashboard' => [
            'background' => 'linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%)',
            'card_bg' => 'linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(255, 255, 255, 0.7) 100%)',
            'shadow' => '0 10px 40px rgba(0, 0, 0, 0.1)'
        ],
        'admin_panel' => [
            'background' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
            'card_bg' => 'linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(255, 255, 255, 0.85) 100%)',
            'shadow' => '0 15px 50px rgba(0, 0, 0, 0.2)'
        ],
        'reports' => [
            'background' => 'linear-gradient(135deg, #74b9ff 0%, #0984e3 100%)',
            'card_bg' => 'linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(255, 255, 255, 0.8) 100%)',
            'shadow' => '0 12px 45px rgba(0, 0, 0, 0.15)'
        ]
    ];

    /**
     * Obtiene un tema específico
     */
    public static function getTheme($name) {
        return self::THEMES[$name] ?? self::THEMES['dashboard'];
    }

    /**
     * Configuración de iconos del sistema
     */
    const SYSTEM_ICONS = [
        'dashboard' => '🏠',
        'users' => '👥',
        'settings' => '⚙️',
        'library' => '📚',
        'travel' => '✈️',
        'map' => '🗺️',
        'reports' => '📊',
        'profile' => '👤',
        'logout' => '🚪',
        'admin' => '👑',
        'agent' => '✈️',
        'notification' => '🔔',
        'search' => '🔍',
        'filter' => '🔽',
        'add' => '➕',
        'edit' => '✏️',
        'delete' => '🗑️',
        'save' => '💾',
        'cancel' => '❌',
        'success' => '✅',
        'warning' => '⚠️',
        'error' => '❌',
        'info' => 'ℹ️'
    ];

    /**
     * Obtiene un icono del sistema
     */
    public static function getIcon($name) {
        return self::SYSTEM_ICONS[$name] ?? '📋';
    }

    /**
     * Configuración de estados de componentes
     */
    const COMPONENT_STATES = [
        'loading' => [
            'opacity' => '0.7',
            'cursor' => 'wait',
            'pointer_events' => 'none'
        ],
        'disabled' => [
            'opacity' => '0.5',
            'cursor' => 'not-allowed',
            'pointer_events' => 'none'
        ],
        'active' => [
            'transform' => 'scale(1.02)',
            'box_shadow' => '0 8px 25px rgba(0, 0, 0, 0.15)'
        ],
        'hover' => [
            'transform' => 'translateY(-2px)',
            'box_shadow' => '0 8px 25px rgba(0, 0, 0, 0.15)'
        ]
    ];

    /**
     * Aplica estados a un componente
     */
    public static function applyState($state) {
        if (!isset(self::COMPONENT_STATES[$state])) {
            return '';
        }
        
        $config = self::COMPONENT_STATES[$state];
        $css = '';
        
        foreach ($config as $property => $value) {
            $css .= str_replace('_', '-', $property) . ': ' . $value . '; ';
        }
        
        return $css;
    }

    /**
     * Configuración de notificaciones
     */
    const NOTIFICATION_STYLES = [
        'success' => [
            'background' => 'linear-gradient(135deg, #68d391 0%, #48bb78 100%)',
            'icon' => '✅',
            'duration' => 3000
        ],
        'error' => [
            'background' => 'linear-gradient(135deg, #fc8181 0%, #e53e3e 100%)',
            'icon' => '❌',
            'duration' => 5000
        ],
        'warning' => [
            'background' => 'linear-gradient(135deg, #f6e05e 0%, #d69e2e 100%)',
            'icon' => '⚠️',
            'duration' => 4000
        ],
        'info' => [
            'background' => 'linear-gradient(135deg, #63b3ed 0%, #4299e1 100%)',
            'icon' => 'ℹ️',
            'duration' => 3000
        ]
    ];

    /**
     * Obtiene configuración de notificación
     */
    public static function getNotificationConfig($type) {
        return self::NOTIFICATION_STYLES[$type] ?? self::NOTIFICATION_STYLES['info'];
    }
}