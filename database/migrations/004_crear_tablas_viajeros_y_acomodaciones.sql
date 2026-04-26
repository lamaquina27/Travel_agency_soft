-- MIGRACIÓN 004 | Módulo: Viajeros, Relaciones y Acomodaciones

-- 1. Crear la tabla principal de viajeros
CREATE TABLE IF NOT EXISTS `viajeros` (
    `id`                INT(11)         NOT NULL AUTO_INCREMENT,
    `agencia_id`        INT(11)         NOT NULL,
    `nombre`            VARCHAR(150)    NOT NULL,
    `apellido`          VARCHAR(150)    NOT NULL,
    `tipo_documento`    TINYINT(2)      NOT NULL DEFAULT 1,
    `numero_documento`  VARCHAR(50)     NOT NULL,
    `mail`              VARCHAR(150)    DEFAULT NULL,
    `telefono`          VARCHAR(30)     DEFAULT NULL,
    `pais_nacimiento`   VARCHAR(100)    DEFAULT NULL,
    `fecha_nacimiento`  DATE            DEFAULT NULL,
    `created_at`        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_agencia_viajeros` (`agencia_id`),

    CONSTRAINT `viajeros_ibfk_1`
        FOREIGN KEY (`agencia_id`)
        REFERENCES `agencias` (`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 2. Crear la tabla pivote entre viajeros y solicitudes
CREATE TABLE IF NOT EXISTS `viajeros_solicitud` (
    `id`            INT(11)     NOT NULL AUTO_INCREMENT,
    `viajero_id`    INT(11)     NOT NULL,
    `solicitud_id`  INT(11)     NOT NULL,
    `created_at`    TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_viajero_solicitud` (`viajero_id`, `solicitud_id`),
    KEY `idx_viajero` (`viajero_id`),
    KEY `idx_solicitud` (`solicitud_id`),

    CONSTRAINT `vs_viajero_fk`
        FOREIGN KEY (`viajero_id`)
        REFERENCES `viajeros` (`id`)
        ON DELETE CASCADE,

    CONSTRAINT `vs_solicitud_fk`
        FOREIGN KEY (`solicitud_id`)
        REFERENCES `programa_solicitudes` (`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 3. Crear la tabla de acomodaciones
CREATE TABLE IF NOT EXISTS `acomodaciones` (
    `id`                INT(11)         NOT NULL AUTO_INCREMENT,
    `hotel_id`          INT(11)         NOT NULL,
    `tipo_acomodacion`  VARCHAR(100)    NOT NULL,
    `acomodacion`       INT(11)         NOT NULL DEFAULT 1,
    `created_at`        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_hotel` (`hotel_id`),

    CONSTRAINT `acomodaciones_hotel_fk`
        FOREIGN KEY (`hotel_id`)
        REFERENCES `biblioteca_alojamientos` (`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Registro de la migración (Solo registramos este archivo una vez)
INSERT IGNORE INTO db_migrations (migration) VALUES ('004_modulo_viajeros_y_acomodaciones');
