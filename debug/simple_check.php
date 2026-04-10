<?php
// =====================================
// ARCHIVO: debug/simple_check.php - Verificación Simple
// =====================================

// Script simple para verificar y crear la tabla company_settings

require_once '../config/database.php';

try {
    $db = Database::getInstance();
    echo "✅ Conexión exitosa\n";
    
    // Verificar si existe la tabla
    $result = $db->query("SHOW TABLES LIKE 'company_settings'");
    
    if ($result->rowCount() == 0) {
        echo "❌ Tabla company_settings no existe. Creando...\n";
        
        // Crear tabla
        $sql = "CREATE TABLE `company_settings` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `company_name` VARCHAR(100) DEFAULT 'Travel Agency',
            `logo_url` VARCHAR(255) NULL,
            `background_image` VARCHAR(255) NULL,
            `admin_primary_color` VARCHAR(7) DEFAULT '#e53e3e',
            `admin_secondary_color` VARCHAR(7) DEFAULT '#fd746c',
            `agent_primary_color` VARCHAR(7) DEFAULT '#667eea',
            `agent_secondary_color` VARCHAR(7) DEFAULT '#764ba2',
            `login_bg_color` VARCHAR(7) DEFAULT '#667eea',
            `login_secondary_color` VARCHAR(7) DEFAULT '#764ba2',
            `default_language` VARCHAR(5) DEFAULT 'es',
            `session_timeout` INT DEFAULT 60,
            `max_file_size` INT DEFAULT 10,
            `backup_frequency` ENUM('daily','weekly','monthly','never') DEFAULT 'weekly',
            `maintenance_mode` TINYINT(1) DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $db->query($sql);
        echo "✅ Tabla creada\n";
    } else {
        echo "✅ Tabla company_settings existe\n";
    }
    
    // Verificar si hay datos
    $config = $db->fetch("SELECT * FROM company_settings LIMIT 1");
    
    if (!$config) {
        echo "❌ No hay configuración. Insertando datos por defecto...\n";
        
        $defaultData = [
            'company_name' => 'Travel Agency',
            'admin_primary_color' => '#e53e3e',
            'admin_secondary_color' => '#fd746c',
            'agent_primary_color' => '#667eea',
            'agent_secondary_color' => '#764ba2',
            'login_bg_color' => '#667eea',
            'login_secondary_color' => '#764ba2',
            'default_language' => 'es',
            'session_timeout' => 60,
            'max_file_size' => 10,
            'backup_frequency' => 'weekly',
            'maintenance_mode' => 0
        ];
        
        $db->insert('company_settings', $defaultData);
        echo "✅ Configuración por defecto insertada\n";
    } else {
        echo "✅ Configuración existe: " . $config['company_name'] . "\n";
    }
    
    // Crear carpetas de uploads
    $dirs = ['../assets/uploads/', '../assets/uploads/config/'];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
            echo "✅ Carpeta creada: $dir\n";
        }
    }
    
    echo "\n🎉 Todo listo! Ahora puedes probar la configuración.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
}
?>