-- Tablas para el módulo "Mi Programa"
USE travel_agency;

-- Tabla de solicitudes de viajero
CREATE TABLE programa_solicitudes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_solicitud VARCHAR(20) UNIQUE NOT NULL, -- ID auto-generado (ej: SOL2025001)
    nombre_viajero VARCHAR(100) NOT NULL,
    apellido_viajero VARCHAR(100) NOT NULL,
    destino VARCHAR(200) NOT NULL,
    fecha_llegada DATE NOT NULL,
    fecha_salida DATE NOT NULL,
    numero_viajeros INT NOT NULL DEFAULT 1,
    acompanamiento VARCHAR(200),
    estado ENUM('borrador', 'activa', 'completada', 'cancelada') DEFAULT 'borrador',
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Tabla de personalización de programas
CREATE TABLE programa_personalizacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    solicitud_id INT NOT NULL,
    titulo_programa VARCHAR(200),
    idioma_predeterminado VARCHAR(5) DEFAULT 'es',
    foto_portada VARCHAR(255),
    configuracion_adicional JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (solicitud_id) REFERENCES programa_solicitudes(id) ON DELETE CASCADE
);

-- Tabla de itinerarios (días del programa)
CREATE TABLE programa_itinerarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    solicitud_id INT NOT NULL,
    dia_numero INT NOT NULL,
    fecha DATE NOT NULL,
    titulo_dia VARCHAR(200),
    descripcion TEXT,
    alojamiento_id INT NULL,
    actividades JSON, -- Array de IDs de actividades
    transporte_id INT NULL,
    notas TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (solicitud_id) REFERENCES programa_solicitudes(id) ON DELETE CASCADE,
    FOREIGN KEY (alojamiento_id) REFERENCES biblioteca_alojamientos(id),
    FOREIGN KEY (transporte_id) REFERENCES biblioteca_transportes(id)
);

-- Insertar datos de ejemplo
INSERT INTO programa_solicitudes (id_solicitud, nombre_viajero, apellido_viajero, destino, fecha_llegada, fecha_salida, numero_viajeros, acompanamiento, user_id) VALUES
('SOL2025001', 'María', 'García', 'París, Francia', '2025-07-15', '2025-07-22', 2, 'Pareja romántica', 2),
('SOL2025002', 'Carlos', 'Rodríguez', 'Roma, Italia', '2025-08-10', '2025-08-17', 4, 'Familia con niños', 2);

INSERT INTO programa_personalizacion (solicitud_id, titulo_programa, idioma_predeterminado) VALUES
(1, 'Escapada Romántica a París', 'es'),
(2, 'Aventura Familiar en Roma', 'es');
