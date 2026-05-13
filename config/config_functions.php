<?php
// =====================================
// ARCHIVO: config/config_functions.php - Funciones de Configuración Mejoradas
// ✅ CORREGIDO: Carga correcta del nombre y logo de la agencia
// =====================================

class ConfigManager {
    private static $config = null;
    private static $db = null;
    private static $agenciaData = null; // ✅ NUEVO: Almacenar datos de la agencia
    
    public static function init() {
        try {
            self::$db = Database::getInstance();
            self::loadConfig();
        } catch(Exception $e) {
            error_log("ConfigManager init error: " . $e->getMessage());
            self::$config = self::getDefaultConfig();
        }
    }

    private static function loadConfig() {
        try {
            // ✅ Obtener agencia_id del usuario logueado
            $agencia_id = null;
            if (isset($_SESSION['agencia_id'])) {
                $agencia_id = $_SESSION['agencia_id'];
            }
            
            if (!$agencia_id) {
                // Si no hay usuario logueado, usar configuración por defecto
                self::$config = self::getDefaultConfig();
                return;
            }
            
            // ✅ Cargar configuración desde la tabla agencias
            self::$agenciaData = self::$db->fetch(
                "SELECT 
                    id,
                    nombre as company_name,
                    logo_url,
                    email_contacto,
                    telefono,
                    admin_primary_color,
                    admin_secondary_color,
                    agent_primary_color,
                    agent_secondary_color
                 FROM agencias 
                 WHERE id = ? AND activa = 1",
                [$agencia_id]
            );
            
            if (!self::$agenciaData) {
                error_log("No se encontró agencia con ID: $agencia_id");
                self::$config = self::getDefaultConfig();
                return;
            }
            
            // ✅ Asignar valores de la agencia a la configuración
            self::$config = [
                'company_name' => self::$agenciaData['company_name'] ?? 'TravelSoft',
                'logo_url' => self::$agenciaData['logo_url'] ?? '/assets/uploads/TravelSoftLogo.png',
                'email_contacto' => self::$agenciaData['email_contacto'] ?? '',
                'telefono' => self::$agenciaData['telefono'] ?? '',
                'admin_primary_color' => self::$agenciaData['admin_primary_color'] ?? '#e53e3e',
                'admin_secondary_color' => self::$agenciaData['admin_secondary_color'] ?? '#fd746c',
                'agent_primary_color' => self::$agenciaData['agent_primary_color'] ?? '#667eea',
                'agent_secondary_color' => self::$agenciaData['agent_secondary_color'] ?? '#764ba2',
                'default_language' => 'es',
                'max_file_size' => 10
            ];
            
            // Debug log
            error_log("✅ ConfigManager cargado - Agencia: " . self::$config['company_name'] . " | Logo: " . self::$config['logo_url']);
            
        } catch(Exception $e) {
            error_log("Error loading config: " . $e->getMessage());
            self::$config = self::getDefaultConfig();
        }
    }
    
    private static function getDefaultConfig() {
        return [
            'company_name' => 'TravelSoft',
            'logo_url' => '/assets/uploads/TravelSoftLogo.png',
            'background_image' => '',
            'admin_primary_color' => '#e53e3e',
            'admin_secondary_color' => '#fd746c',
            'agent_primary_color' => '#667eea',
            'agent_secondary_color' => '#764ba2',
            'login_bg_color' => '#667eea',
            'login_secondary_color' => '#764ba2',
            'default_language' => 'es',
            'max_file_size' => 10,
            'backup_frequency' => 'weekly',
            'maintenance_mode' => 0
        ];
    }
    
    public static function get($key = null) {
        if (!self::$config) {
            self::init();
        }
        
        if ($key === null) {
            return self::$config;
        }
        
        return self::$config[$key] ?? null;
    }
    
    // ✅ MÉTODOS PRINCIPALES CORREGIDOS
    
    public static function getCompanyName() {
        if (!self::$config) {
            self::init();
        }
        $nombre = self::$config['company_name'] ?? 'TravelSoft';
        error_log("🏢 getCompanyName() retorna: " . $nombre);
        return $nombre;
    }
    
    public static function getLogo() {
        if (!self::$config) {
            self::init();
        }
        $logo = self::$config['logo_url'] ?? '';
        error_log("🖼️ getLogo() retorna: " . ($logo ?: '/assets/uploads/TravelSoftLogo.png'));
        return $logo;
    }
    
    public static function getDefaultLanguage() {
        return self::get('default_language') ?: 'es';
    }
    
    public static function getColorsForRole($role) {
        if (!self::$config) {
            self::init();
        }
        
        $config = self::$config;
        
        if ($role === 'admin') {
            return [
                'primary' => $config['admin_primary_color'] ?? '#e53e3e',
                'secondary' => $config['admin_secondary_color'] ?? '#fd746c'
            ];
        } else {
            return [
                'primary' => $config['agent_primary_color'] ?? '#667eea',
                'secondary' => $config['agent_secondary_color'] ?? '#764ba2'
            ];
        }
    }
    
    public static function getLoginColors() {
        $config = self::get();
        return [
            'primary' => $config['login_bg_color'] ?? '#667eea',
            'secondary' => $config['login_secondary_color'] ?? '#764ba2'
        ];
    }
    
    // ✅ NUEVO: Método para obtener datos completos de la agencia
    public static function getAgenciaData() {
        if (!self::$agenciaData) {
            self::init();
        }
        return self::$agenciaData;
    }
    
    public static function update($data) {
        try {
            if (!self::$db) {
                self::init();
            }
            
            $currentConfig = self::get();
            if (!$currentConfig || !isset($currentConfig['id'])) {
                // Crear nueva configuración
                self::$db->insert('company_settings', $data);
            } else {
                // Actualizar existente
                $setParts = [];
                $params = [];
                
                foreach ($data as $key => $value) {
                    $setParts[] = "`{$key}` = ?";
                    $params[] = $value;
                }
                
                $params[] = $currentConfig['id'];
                $sql = "UPDATE company_settings SET " . implode(', ', $setParts) . " WHERE id = ?";
                
                self::$db->query($sql, $params);
            }
            
            // Recargar configuración
            self::$config = null;
            self::init();
            
            return true;
        } catch(Exception $e) {
            error_log("Error updating config: " . $e->getMessage());
            return false;
        }
    }
}

// =====================================
// FUNCIONES GLOBALES DE UTILIDAD
// =====================================

/**
 * Obtiene la ruta física para uploads de una agencia
 */
function getAgenciaUploadPath($agencia_id, $tipo, $subtipo = null) {
    $basePath = dirname(__DIR__) . '/assets/uploads/agencia_' . $agencia_id . '/' . $tipo;
    
    if ($subtipo) {
        $basePath .= '/' . $subtipo;
    }
    
    // Agregar año y mes
    $fullPath = $basePath . '/' . date('Y') . '/' . date('m');
    
    // Crear directorio si no existe
    if (!is_dir($fullPath)) {
        mkdir($fullPath, 0755, true);
    }
    
    return $fullPath;
}

/**
 * Obtiene la URL completa para un archivo de una agencia
 */
function getAgenciaUploadUrl($agencia_id, $tipo, $subtipo, $filename) {
    $urlPath = '/assets/uploads/agencia_' . $agencia_id . '/' . $tipo;

    if ($subtipo) {
        $urlPath .= '/' . $subtipo;
    }

    $urlPath .= '/' . date('Y') . '/' . date('m') . '/' . $filename;

    // ✅ Retornar ruta relativa (sin dominio) para que funcione en
    // cualquier entorno: localhost Mac, localhost Windows y hosting.
    // El dominio (APP_URL) se agrega en la vista cuando se necesita.
    return $urlPath;
}

/**
 * Valida y procesa la subida de una imagen para una agencia (BIBLIOTECA)
 */
function uploadAgenciaImageBiblioteca($file, $agencia_id, $tipo, $resourceId, $field) {
    // Validar archivo
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array(strtolower($file['type']), $allowedTypes)) {
        throw new Exception('Tipo de archivo no permitido: ' . $file['type']);
    }
    
    // ⚠️ AUMENTAR LÍMITE SI QUIERES IMÁGENES DE ALTA CALIDAD
    $maxSize = 20 * 1024 * 1024; // 20MB para alta calidad
    if ($file['size'] > $maxSize) {
        throw new Exception('Archivo demasiado grande (máx 20MB)');
    }
    
    // Obtener ruta física
    $uploadPath = getAgenciaUploadPath($agencia_id, 'biblioteca', $tipo);
    
    // Generar nombre único
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $fileName = $tipo . '_' . $resourceId . '_' . $field . '_' . time() . '.' . $extension;
    $filePath = $uploadPath . '/' . $fileName;
    
    // ✅ MOVER ARCHIVO SIN MODIFICAR (Calidad original)
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        throw new Exception('Error moviendo archivo a: ' . $filePath);
    }
    
    // Verificar que el archivo se creó
    if (!file_exists($filePath)) {
        throw new Exception('El archivo no se creó correctamente');
    }
    
    // ✅ OPCIONAL: Optimizar sin perder calidad visible
    optimizeImageWithoutQualityLoss($filePath, $extension);
    
    // Retornar URL
    return getAgenciaUploadUrl($agencia_id, 'biblioteca', $tipo, $fileName);
}

/**
 * ✅ NUEVA FUNCIÓN: Optimizar imagen sin perder calidad visible
 * Reduce tamaño de archivo pero mantiene alta calidad
 */
function optimizeImageWithoutQualityLoss($filePath, $extension) {
    try {
        // Leer imagen según tipo
        switch(strtolower($extension)) {
            case 'jpg':
            case 'jpeg':
                $image = imagecreatefromjpeg($filePath);
                if ($image) {
                    // ✅ Guardar con calidad 95 (casi sin pérdida)
                    imagejpeg($image, $filePath, 95);
                    imagedestroy($image);
                }
                break;
                
            case 'png':
                $image = imagecreatefrompng($filePath);
                if ($image) {
                    // ✅ PNG sin pérdida (0 = sin compresión, 9 = máxima compresión)
                    imagepng($image, $filePath, 6); // Compresión media sin pérdida
                    imagedestroy($image);
                }
                break;
                
            case 'webp':
                $image = imagecreatefromwebp($filePath);
                if ($image) {
                    // ✅ WebP con calidad 95
                    imagewebp($image, $filePath, 95);
                    imagedestroy($image);
                }
                break;
        }
    } catch (Exception $e) {
        // Si falla la optimización, mantener original
        error_log("No se pudo optimizar imagen: " . $e->getMessage());
    }
}

/**
 * Valida y procesa la subida de portada de programa
 */
function uploadAgenciaImagePrograma($file, $agencia_id, $programa_id) {
    // Validar archivo
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 10 * 1024 * 1024; // 10MB

    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Tipo de archivo no permitido: ' . $file['type']);
    }

    if ($file['size'] > $maxSize) {
        throw new Exception('Archivo demasiado grande. Máximo 10MB');
    }
    
    // Obtener ruta física
    $uploadPath = getAgenciaUploadPath($agencia_id, 'programa', 'portadas');
    
    // Generar nombre único
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $fileName = 'programa_' . $programa_id . '_cover_' . time() . '.' . $extension;
    $filePath = $uploadPath . '/' . $fileName;
    
    // Mover archivo
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        throw new Exception('Error al subir el archivo');
    }
    
    // Retornar URL
    return getAgenciaUploadUrl($agencia_id, 'programa', 'portadas', $fileName);
}

/**
 * Elimina un archivo de uploads
 */
function deleteAgenciaFile($fileUrl) {
    try {
        // Convertir URL a ruta física
        $filePath = str_replace(APP_URL, dirname(__DIR__), $fileUrl);
        
        if (file_exists($filePath)) {
            unlink($filePath);
            return true;
        }
        return false;
    } catch(Exception $e) {
        error_log("Error eliminando archivo: " . $e->getMessage());
        return false;
    }
}

/**
 * Convierte color hexadecimal a RGB
 */
function hex2rgb($hex) {
    $hex = str_replace("#", "", $hex);
    
    if (strlen($hex) == 3) {
        $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
        $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
        $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
    } else {
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
    }
    
    return "$r, $g, $b";
}

/**
 * Verifica si un archivo existe (útil para logos)
 */
function fileExists($filePath) {
    if (empty($filePath)) {
        return false;
    }
    
    // Si es una URL, convertir a ruta física
    if (strpos($filePath, 'http') === 0) {
        $filePath = str_replace(APP_URL, dirname(__DIR__), $filePath);
    }
    
    return file_exists($filePath);
}

/**
 * Convierte ruta física a URL pública
 */
function getPublicUrl($filePath) {
    if (strpos($filePath, 'http') === 0) {
        return $filePath; // Ya es una URL
    }
    
    // Si es una ruta relativa, convertir a URL
    if (strpos($filePath, '/assets/') === 0) {
        return APP_URL . $filePath;
    }
    
    return $filePath;
}

/**
 * Genera iniciales del nombre de la empresa
 */
function getCompanyInitials($companyName) {
    $words = explode(' ', $companyName);
    if (count($words) >= 2) {
        return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
    }
    return strtoupper(substr($companyName, 0, 2));
}