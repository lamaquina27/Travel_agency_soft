-- MIGRACIÓN 004 | Tabla: viajeros

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
    KEY `idx_documento` (`tipo_documento`, `numero_documento`),

    CONSTRAINT `viajeros_ibfk_1`
        FOREIGN KEY (`agencia_id`)
        REFERENCES `agencias` (`id`)
        ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO db_migrations (migration) VALUES ('004_create_viajeros');
