-- ============================================================ ;
-- MIGRACION 016: Modulo Rooming y Operadores ;
-- Fecha: 2026-05-28 ;
-- Descripcion: Crea las tablas operadores, rooming y la tabla ;
--              pivote asignacion_operadores que las relaciona. ;
-- ;
-- NOTA sobre el estilo de comentarios: cada linea termina en punto y coma ;
-- para que el runner (database/migrate.php) aisle el comentario ;
-- en su propio fragmento y no descarte el CREATE que le sigue. ;
-- ============================================================ ;


-- ============================================================ ;
-- 1. OPERADORES  (un usuario que opera dentro de una agencia) ;
-- ============================================================ ;
CREATE TABLE IF NOT EXISTS `operadores` (
    `id`          INT(11)    NOT NULL AUTO_INCREMENT,
    `agencia_id`  INT(11)    NOT NULL,
    `usuario_id`  INT(11)    NOT NULL,
    `created_at`  TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),

    CONSTRAINT `fk_operadores_agencia`
        FOREIGN KEY (`agencia_id`) REFERENCES `agencias` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_operadores_usuario`
        FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    UNIQUE KEY `uq_operador_agencia_usuario` (`agencia_id`, `usuario_id`),
    INDEX `idx_operadores_agencia` (`agencia_id`),
    INDEX `idx_operadores_usuario` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================ ;
-- 2. ROOMING  (detalle operativo de servicios de una solicitud) ;
-- ============================================================ ;
CREATE TABLE IF NOT EXISTS `rooming` (
    `id`                INT(11)        NOT NULL AUTO_INCREMENT,
    `agencia_id`        INT(11)        NOT NULL,
    `solicitud_id`      INT(11)        NOT NULL,
    `programa_dia_id`   INT(11)            NULL DEFAULT NULL,
    `cantidad_pasajeros` INT(11)       NOT NULL DEFAULT 0,
    `service_type`      VARCHAR(50)    NOT NULL,
    `service_date`      DATE               NULL DEFAULT NULL,
    `city`              VARCHAR(120)       NULL DEFAULT NULL,
    `airport_code_origen`   VARCHAR(10)    NULL DEFAULT NULL,
    `airport_code_destino`  VARCHAR(10)    NULL DEFAULT NULL,
    `flight_code`       VARCHAR(20)        NULL DEFAULT NULL,
    `arrival_time`      TIME               NULL DEFAULT NULL,
    `departure_time`    TIME               NULL DEFAULT NULL,
    `pickup_time`       TIME               NULL DEFAULT NULL,
    `pickup_location`   VARCHAR(255)       NULL DEFAULT NULL,
    `dropoff_location`  VARCHAR(255)       NULL DEFAULT NULL,
    `hotel_id`          INT(11)            NULL DEFAULT NULL,
    `guide_name`        VARCHAR(150)       NULL DEFAULT NULL,
    `status`            ENUM('en_proceso','completado','cancelado')
                                       NOT NULL DEFAULT 'en_proceso',
    `internal_notes`    TEXT               NULL,
    `operator_notes`    TEXT               NULL,
    `created_at`        TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),

    CONSTRAINT `fk_rooming_agencia`
        FOREIGN KEY (`agencia_id`) REFERENCES `agencias` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_rooming_solicitud`
        FOREIGN KEY (`solicitud_id`) REFERENCES `programa_solicitudes` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_rooming_programa_dia`
        FOREIGN KEY (`programa_dia_id`) REFERENCES `programa_dias` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_rooming_hotel`
        FOREIGN KEY (`hotel_id`) REFERENCES `biblioteca_alojamientos` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,

    INDEX `idx_rooming_agencia`      (`agencia_id`),
    INDEX `idx_rooming_solicitud`    (`solicitud_id`),
    INDEX `idx_rooming_programa_dia` (`programa_dia_id`),
    INDEX `idx_rooming_hotel`        (`hotel_id`),
    INDEX `idx_rooming_status`       (`status`),
    INDEX `idx_rooming_service_date` (`service_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================ ;
-- 3. ASIGNACION_OPERADORES  (pivote rooming <-> operadores) ;
-- ============================================================ ;
CREATE TABLE IF NOT EXISTS `asignacion_operadores` (
    `id`           INT(11)    NOT NULL AUTO_INCREMENT,
    `rooming_id`   INT(11)    NOT NULL,
    `operador_id`  INT(11)    NOT NULL,
    `created_at`   TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),

    CONSTRAINT `fk_asignacion_rooming`
        FOREIGN KEY (`rooming_id`) REFERENCES `rooming` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_asignacion_operador`
        FOREIGN KEY (`operador_id`) REFERENCES `operadores` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    UNIQUE KEY `uq_asignacion_rooming_operador` (`rooming_id`, `operador_id`),
    INDEX `idx_asignacion_rooming`  (`rooming_id`),
    INDEX `idx_asignacion_operador` (`operador_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;