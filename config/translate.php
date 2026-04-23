<?php
// =====================================================
// ARCHIVO: config/translate.php - SISTEMA UNIFICADO DE TRADUCCIÓN
// =====================================================

class TranslateSystem {
    
    private static $instance = null;
    private static $defaultLanguage = 'es';
    private static $supportedLanguages = [
        'es' => ['name' => 'Español', 'flag' => '🇪🇸'],
        'en' => ['name' => 'English', 'flag' => '🇺🇸'], 
        'fr' => ['name' => 'Français', 'flag' => '🇫🇷'],
        'pt' => ['name' => 'Português', 'flag' => '🇧🇷'],
        'it' => ['name' => 'Italiano', 'flag' => '🇮🇹'],
        'de' => ['name' => 'Deutsch', 'flag' => '🇩🇪']
    ];
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Inicializar sistema de traducción
     */
    public static function init() {
        // Obtener idioma por defecto de configuración
        try {
            ConfigManager::init();
            self::$defaultLanguage = ConfigManager::getDefaultLanguage() ?: 'es';
        } catch(Exception $e) {
            self::$defaultLanguage = 'es';
        }
        
        // Detectar idioma de URL, sesión o configuración
        $detectedLang = self::detectLanguage();
        
        // Guardar en sesión para usuarios autenticados
        if (App::isLoggedIn()) {
            $_SESSION['app_language'] = $detectedLang;
        }
        
        return $detectedLang;
    }
    
    /**
     * Detectar idioma actual
     */
    public static function detectLanguage() {
        // 1. Parámetro de URL (?lang=en)
        if (isset($_GET['lang']) && self::isValidLanguage($_GET['lang'])) {
            return $_GET['lang'];
        }
        
        // 2. Sesión de usuario autenticado
        if (App::isLoggedIn() && isset($_SESSION['app_language'])) {
            return $_SESSION['app_language'];
        }
        
        // 3. Idioma por defecto del sistema
        return self::$defaultLanguage;
    }
    
    /**
     * Validar si idioma es soportado
     */
    public static function isValidLanguage($lang) {
        return array_key_exists($lang, self::$supportedLanguages);
    }
    
    /**
     * Obtener idiomas soportados
     */
    public static function getSupportedLanguages() {
        return self::$supportedLanguages;
    }
    
    /**
     * Obtener idioma actual
     */
    public static function getCurrentLanguage() {
        return self::detectLanguage();
    }
    
    /**
     * Obtener idioma por defecto
     */
    public static function getDefaultLanguage() {
        return self::$defaultLanguage;
    }
    
    /**
     * Generar selector de idioma HTML
     */
    public static function renderLanguageSelector($type = 'dropdown', $showFlags = true, $className = '') {
        $currentLang = self::getCurrentLanguage();
        $languages = self::getSupportedLanguages();
        
        if ($type === 'dropdown') {
            return self::renderDropdownSelector($currentLang, $languages, $showFlags, $className);
        } else {
            return self::renderButtonSelector($currentLang, $languages, $showFlags, $className);
        }
    }
    
    /**
     * Selector tipo dropdown
     */
    private static function renderDropdownSelector($currentLang, $languages, $showFlags, $className) {
        $html = '<div class="language-selector-dropdown ' . $className . '">';
        $html .= '<select id="languageSelector" class="language-select" onchange="changeLanguage(this.value)">';
        
        foreach ($languages as $code => $info) {
            $selected = ($code === $currentLang) ? 'selected' : '';
            $flag = $showFlags ? $info['flag'] . ' ' : '';
            $html .= "<option value=\"{$code}\" {$selected}>{$flag}{$info['name']}</option>";
        }
        
        $html .= '</select></div>';
        return $html;
    }
    
    /**
     * Selector tipo botones
     */
    private static function renderButtonSelector($currentLang, $languages, $showFlags, $className) {
        $html = '<div class="language-selector-buttons ' . $className . '">';
        
        foreach ($languages as $code => $info) {
            $active = ($code === $currentLang) ? 'active' : '';
            $flag = $showFlags ? $info['flag'] : '';
            $html .= "<button class=\"lang-btn {$active}\" onclick=\"changeLanguage('{$code}')\" title=\"{$info['name']}\">";
            $html .= $flag;
            $html .= "</button>";
        }
        
        $html .= '</div>';
        return $html;
    }
    
    /**
     * Obtener scripts JavaScript necesarios
     */
    public static function getJavaScriptCode($pageType = 'authenticated') {
        $currentLang = self::getCurrentLanguage();
        $defaultLang = self::$defaultLanguage;
        $isAuthenticated = App::isLoggedIn() ? 'true' : 'false';
        
        return "
        <script>
        // Variables globales del sistema de traducción
        window.TranslateConfig = {
            defaultLanguage: '{$defaultLang}',
            currentLanguage: '{$currentLang}',
            isAuthenticated: {$isAuthenticated},
            pageType: '{$pageType}',
            supportedLanguages: " . json_encode(self::$supportedLanguages) . "
        };
        
        // Función para cambiar idioma
        function changeLanguage(lang) {
            if (!TranslateConfig.supportedLanguages[lang]) {
                console.error('Idioma no soportado:', lang);
                return;
            }
            
            // Guardar preferencia
            sessionStorage.setItem('selectedLanguage', lang);
            localStorage.setItem('preferredLanguage', lang);
            
            // Para vistas públicas, recargar con parámetro
            if (!TranslateConfig.isAuthenticated) {
                const url = new URL(window.location);
                url.searchParams.set('lang', lang);
                window.location.href = url.toString();
                return;
            }
            
            // Para usuarios autenticados, usar Google Translate
            const googleSelect = document.querySelector('.goog-te-combo');
            if (googleSelect) {
                googleSelect.value = lang;
                googleSelect.dispatchEvent(new Event('change'));
                
                // Guardar en sesión del servidor
                fetch('" . APP_URL . "/modules/translate/api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ language: lang })
                });
            }
        }
        
        // Inicializar Google Translate
        function initializeGoogleTranslate() {
            function googleTranslateElementInit() {
                new google.translate.TranslateElement({
                    pageLanguage: TranslateConfig.defaultLanguage,
                    includedLanguages: Object.keys(TranslateConfig.supportedLanguages).join(','),
                    layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
                    autoDisplay: false
                }, 'google_translate_element');
                
                setTimeout(applyStoredLanguage, 1000);
            }
            
            function applyStoredLanguage() {
                const stored = sessionStorage.getItem('selectedLanguage') || 
                              localStorage.getItem('preferredLanguage') || 
                              TranslateConfig.currentLanguage;
                              
                if (stored && stored !== TranslateConfig.defaultLanguage) {
                    const googleSelect = document.querySelector('.goog-te-combo');
                    if (googleSelect) {
                        googleSelect.value = stored;
                        googleSelect.dispatchEvent(new Event('change'));
                    }
                }
            }
            
            // Cargar script de Google Translate
            if (!window.googleTranslateElementInit) {
                window.googleTranslateElementInit = googleTranslateElementInit;
                const script = document.createElement('script');
                script.src = '//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit';
                document.head.appendChild(script);
            }
            
            // Escuchar cambios en Google Translate
            setTimeout(() => {
                const googleSelect = document.querySelector('.goog-te-combo');
                if (googleSelect) {
                    googleSelect.addEventListener('change', function() {
                        if (this.value) {
                            sessionStorage.setItem('selectedLanguage', this.value);
                            localStorage.setItem('preferredLanguage', this.value);
                        }
                    });
                }
            }, 2000);
        }
        
        // Auto-inicializar
        document.addEventListener('DOMContentLoaded', initializeGoogleTranslate);
        </script>
        ";
    }
    
    /**
     * Obtener CSS para los selectores
     */
    public static function getCSS() {
        return "
        <style>
        /* ===== SELECTOR DROPDOWN ===== */
        .language-selector-dropdown {
            position: relative;
            display: inline-block;
        }
        
        .language-select {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            color: white;
            padding: 8px 12px;
            font-size: 14px;
            cursor: pointer;
            outline: none;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }
        
        .language-select:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
        }
        
        .language-select option {
            background: #333;
            color: white;
        }
        
        /* ===== SELECTOR BOTONES ===== */
        .language-selector-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .lang-btn {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 6px;
            color: white;
            padding: 6px 10px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            min-width: 40px;
        }
        
        .lang-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }
        
        .lang-btn.active {
            background: rgba(255, 255, 255, 0.9);
            color: #333;
            font-weight: bold;
        }
        
        /* ===== OCULAR GOOGLE TRANSLATE WIDGET ===== */
        #google_translate_element {
            display: none !important;
        }
        
        .goog-te-banner-frame {
            display: none !important;
        }
        
        .goog-te-menu-frame {
            display: none !important;
        }
        
        body {
            top: 0 !important;
        }
        
        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .language-selector-buttons {
                justify-content: center;
            }
            
            .lang-btn {
                padding: 8px 12px;
                font-size: 14px;
            }
        }
        </style>
        ";
    }
}
