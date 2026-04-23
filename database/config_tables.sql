-- Actualizar tabla de configuración de empresa
USE travel_agency;

-- Eliminar tabla actual si existe y recrear con nuevos campos
DROP TABLE IF EXISTS company_settings;

CREATE TABLE company_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(100) NOT NULL DEFAULT 'Travel Agency',
    logo_url VARCHAR(255) NULL,
    background_image VARCHAR(255) NULL,
    
    -- Colores para diferentes roles
    admin_primary_color VARCHAR(7) DEFAULT '#e53e3e',
    admin_secondary_color VARCHAR(7) DEFAULT '#fd746c',
    agent_primary_color VARCHAR(7) DEFAULT '#667eea',
    agent_secondary_color VARCHAR(7) DEFAULT '#764ba2',
    login_bg_color VARCHAR(7) DEFAULT '#667eea',
    login_secondary_color VARCHAR(7) DEFAULT '#764ba2',
    
    -- Configuración de idioma
    default_language VARCHAR(5) DEFAULT 'es',
    
    -- Configuración de sesión
    session_timeout INT DEFAULT 60, -- minutos
    
    -- Configuraciones técnicas
    max_file_size INT DEFAULT 10, -- MB
    backup_frequency ENUM('daily', 'weekly', 'monthly', 'never') DEFAULT 'weekly',
    maintenance_mode BOOLEAN DEFAULT FALSE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insertar configuración por defecto
INSERT INTO company_settings (
    company_name,
    admin_primary_color,
    admin_secondary_color,
    agent_primary_color,
    agent_secondary_color,
    login_bg_color,
    login_secondary_color,
    default_language,
    session_timeout
) VALUES (
    'Travel Agency',
    '#e53e3e',
    '#fd746c',
    '#667eea',
    '#764ba2',
    '#667eea',
    '#764ba2',
    'es',
    60
);

-- Tabla para almacenar uploads de configuración
CREATE TABLE config_uploads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    upload_type ENUM('logo', 'background', 'general') NOT NULL,
    uploaded_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
);