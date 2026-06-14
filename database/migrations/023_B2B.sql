-- ============================================================
-- MIGRACION 023: B2B - Subagencias


-- ------------------------------------------------------------
-- 1. Rol 'subagencia' en el ENUM de users.role
--    (se conservan los roles previos, incluido 'operador')
-- ------------------------------------------------------------
ALTER TABLE `users`
    MODIFY COLUMN `role`
        ENUM('superadmin','admin','agent','operador','subagencia')
        NOT NULL DEFAULT 'agent';

-- ------------------------------------------------------------
-- 2. Marca propia de la subagencia
--    Una fila por usuario subagencia (1 usuario = 1 subagencia).
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `config_sub_agencias` (
    `id`              INT(11)      NOT NULL AUTO_INCREMENT,
    `user_id`         INT(11)      NOT NULL  COMMENT 'Usuario subagencia dueño de la configuracion',
    `nombre`          VARCHAR(200) DEFAULT NULL  COMMENT 'Nombre comercial de la subagencia',
    `logo_url`        VARCHAR(255) DEFAULT NULL  COMMENT 'Logo de la subagencia',
    `primary_color`   VARCHAR(7)   DEFAULT '#667eea'  COMMENT 'Color primario de marca',
    `secondary_color` VARCHAR(7)   DEFAULT '#764ba2'  COMMENT 'Color secundario de marca',
    `divisa`          VARCHAR(3)   DEFAULT 'USD'  COMMENT 'Divisa preferida (ISO 4217)',
    `email_contacto`  VARCHAR(100) DEFAULT NULL  COMMENT 'Correo de contacto publico',
    `telefono`        VARCHAR(50)  DEFAULT NULL  COMMENT 'Telefono de contacto publico',
    `created_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_config_sub_user` (`user_id`),
    CONSTRAINT `fk_config_sub_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 3. Override de precios/condiciones por subagencia y tour
--    - La existencia de una fila = el tour esta asignado a esa
--      subagencia.
--    - Espeja la seccion editable de `programa_precios`.
--    - `public_token` = link propio que comparte la subagencia.
--    - NO altera `programa_precios` original del tour.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `subagencia_tour_precios` (
    `id`                    INT(11)        NOT NULL AUTO_INCREMENT,
    `user_id`               INT(11)        NOT NULL  COMMENT 'Usuario subagencia dueño del override',
    `solicitud_id`          INT(11)        NOT NULL  COMMENT 'Tour asignado (programa_solicitudes.id)',
    -- ---- copia editable de la seccion de precios ----
    `precio_adulto`         DECIMAL(10,2)  DEFAULT NULL,
    `precio_nino`           DECIMAL(10,2)  DEFAULT NULL,
    `cantidad_adultos`      INT(11)        DEFAULT 1,
    `cantidad_ninos`        INT(11)        DEFAULT 0,
    `precio_total`          DECIMAL(10,2)  DEFAULT NULL,
    `noches_incluidas`      INT(11)        DEFAULT 0,
    `precio_incluye`        TEXT           DEFAULT NULL,
    `precio_no_incluye`     TEXT           DEFAULT NULL,
    `condiciones_generales` TEXT           DEFAULT NULL,
    `movilidad_reducida`    TINYINT(1)     DEFAULT 0,
    `info_pasaporte`        TEXT           DEFAULT NULL,
    `info_seguros`          TEXT           DEFAULT NULL,
    -- ---- link propio para compartir ----
    `public_token`          VARCHAR(32)    DEFAULT NULL  COMMENT 'Token del link que comparte la subagencia',
    `created_at`            TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_subagencia_tour` (`user_id`, `solicitud_id`),
    UNIQUE KEY `uq_subagencia_public_token` (`public_token`),
    INDEX `idx_stp_user` (`user_id`),
    INDEX `idx_stp_solicitud` (`solicitud_id`),
    CONSTRAINT `fk_stp_user`
        FOREIGN KEY (`user_id`)      REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_stp_solicitud`
        FOREIGN KEY (`solicitud_id`) REFERENCES `programa_solicitudes` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `programa_adjuntos` (
    `id`                    INT(11)        NOT NULL AUTO_INCREMENT,
    `solicitud_id`          INT(11)        NOT NULL ,
    -- COLUMNAS DE ADJUNTOS --
    `archivo`               VARCHAR(2048)        NULL,
    `enlace`               VARCHAR(2048)        NULL,
    `created_at`            TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_adjuntos`
        FOREIGN KEY (`solicitud_id`) REFERENCES `programa_solicitudes` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

