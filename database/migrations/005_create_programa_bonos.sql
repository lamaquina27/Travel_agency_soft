-- MIGRACIÓN 005 | Tabla: programa_bonos

CREATE TABLE IF NOT EXISTS `programa_bonos` (
    `id`                INT(11)         NOT NULL AUTO_INCREMENT,
    `solicitud_id`      INT(11)         NOT NULL,
    `codigo_reserva`    VARCHAR(100)    DEFAULT NULL,
    `notas_adicionales` TEXT            DEFAULT NULL,
    `fecha_emision`     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_solicitud_bono` (`solicitud_id`),
    KEY `idx_codigo_reserva` (`codigo_reserva`),

    CONSTRAINT `programa_bonos_ibfk_1`
        FOREIGN KEY (`solicitud_id`)
        REFERENCES `programa_solicitudes` (`id`)
        ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO db_migrations (migration) VALUES ('005_create_programa_bonos');
