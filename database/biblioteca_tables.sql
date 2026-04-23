<?php
// =====================================
// ARCHIVO: database/biblioteca_tables.sql
// =====================================
?>
-- Ejecutar estas consultas después de las tablas existentes
USE travel_agency;

-- Tabla para días
CREATE TABLE biblioteca_dias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    idioma VARCHAR(5) NOT NULL DEFAULT 'es',
    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT,
    ubicacion VARCHAR(255),
    latitud DECIMAL(10, 8) NULL,
    longitud DECIMAL(11, 8) NULL,
    imagen1 VARCHAR(255) NULL,
    imagen2 VARCHAR(255) NULL,
    imagen3 VARCHAR(255) NULL,
    activo BOOLEAN DEFAULT TRUE,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Tabla para alojamientos
CREATE TABLE biblioteca_alojamientos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    idioma VARCHAR(5) NOT NULL DEFAULT 'es',
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT,
    ubicacion VARCHAR(255),
    latitud DECIMAL(10, 8) NULL,
    longitud DECIMAL(11, 8) NULL,
    tipo ENUM('hotel', 'camping', 'casa_huespedes', 'crucero', 'lodge', 'atipico', 'campamento', 'camping_car', 'tren') NOT NULL,
    categoria INT NULL, -- 1-5 estrellas para tipos que lo requieren
    imagen VARCHAR(255) NULL,
    sitio_web VARCHAR(255) NULL,
    activo BOOLEAN DEFAULT TRUE,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Tabla para actividades
CREATE TABLE biblioteca_actividades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    idioma VARCHAR(5) NOT NULL DEFAULT 'es',
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT,
    ubicacion VARCHAR(255),
    latitud DECIMAL(10, 8) NULL,
    longitud DECIMAL(11, 8) NULL,
    imagen1 VARCHAR(255) NULL,
    imagen2 VARCHAR(255) NULL,
    imagen3 VARCHAR(255) NULL,
    activo BOOLEAN DEFAULT TRUE,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Tabla para transportes
CREATE TABLE biblioteca_transportes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    idioma VARCHAR(5) NOT NULL DEFAULT 'es',
    medio ENUM('bus', 'avion', 'coche', 'barco', 'tren') NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT,
    lugar_salida VARCHAR(255),
    lugar_llegada VARCHAR(255),
    lat_salida DECIMAL(10, 8) NULL,
    lng_salida DECIMAL(11, 8) NULL,
    lat_llegada DECIMAL(10, 8) NULL,
    lng_llegada DECIMAL(11, 8) NULL,
    duracion VARCHAR(50), -- ej: "2 horas 30 minutos"
    distancia_km DECIMAL(8, 2), -- distancia en kilómetros
    activo BOOLEAN DEFAULT TRUE,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Insertar datos de ejemplo
INSERT INTO biblioteca_dias (titulo, descripcion, ubicacion, latitud, longitud, user_id) VALUES
('Día en París', 'Recorrido completo por los principales monumentos de París', 'París, Francia', 48.8566, 2.3522, 1),
('Día en Roma', 'Visita al Coliseo, Foro Romano y Vaticano', 'Roma, Italia', 41.9028, 12.4964, 1);

INSERT INTO biblioteca_alojamientos (nombre, descripcion, ubicacion, tipo, categoria, latitud, longitud, user_id) VALUES
('Hotel París Centro', 'Hotel 4 estrellas en el centro de París', 'París, Francia', 'hotel', 4, 48.8566, 2.3522, 1),
('Camping Costa Brava', 'Camping familiar cerca de la playa', 'Costa Brava, España', 'camping', 3, 41.9794, 3.0441, 1);

INSERT INTO biblioteca_actividades (nombre, descripcion, ubicacion, latitud, longitud, user_id) VALUES
('Tour Eiffel', 'Visita guiada a la Torre Eiffel con subida incluida', 'París, Francia', 48.8584, 2.2945, 1),
('Coliseo Romano', 'Entrada y visita guiada al Coliseo de Roma', 'Roma, Italia', 41.8902, 12.4922, 1);

INSERT INTO biblioteca_transportes (medio, titulo, descripcion, lugar_salida, lugar_llegada, lat_salida, lng_salida, lat_llegada, lng_llegada, duracion, distancia_km, user_id) VALUES
('avion', 'Vuelo París-Roma', 'Vuelo directo París Charles de Gaulle a Roma Fiumicino', 'París CDG, Francia', 'Roma FCO, Italia', 49.0097, 2.5479, 41.8003, 12.2389, '2 horas 15 minutos', 1105.00, 1);
