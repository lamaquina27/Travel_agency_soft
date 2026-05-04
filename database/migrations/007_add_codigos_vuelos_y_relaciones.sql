-- MIGRACIÓN 007: Crear tablas para manejo de códigos de vuelo

CREATE TABLE `codigos_vuelos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo_vuelo` varchar(20) NOT NULL,
  `aerolinea` varchar(100) NOT NULL,
  `ciudad_origen` varchar(100) NOT NULL,
  `codigo_aeropuerto_origen` varchar(10) NOT NULL,
  `aeropuerto_origen` varchar(200) NOT NULL,
  `ciudad_destino` varchar(100) NOT NULL,
  `codigo_aeropuerto_destino` varchar(10) NOT NULL,
  `aeropuerto_destino` varchar(200) NOT NULL,
  `terminal` varchar(50) NULL,
  `hora_salida` time NOT NULL,
  `hora_llegada` time NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Catálogo de códigos de vuelo';

CREATE TABLE `vuelos_dias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo_vuelo_id` int(11) NOT NULL,
  `programa_dias_id` int(11) NOT NULL,
  `orden` int(11) NOT NULL DEFAULT 1 COMMENT 'Orden de prioridad: 1=principal, 2=secundario...',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_vuelo_dia` (`codigo_vuelo_id`, `programa_dias_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabla intermedia: asigna códigos de vuelo a días de programa';