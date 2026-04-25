-- MIGRACIÓN 006 | Tabla: programa_bono_viajeros

CREATE TABLE IF NOT EXISTS `programa_bono_viajeros` (
    `id`            INT(11)     NOT NULL AUTO_INCREMENT,
    `bono_id`       INT(11)     NOT NULL,
    `viajero_id`    INT(11)     NOT NULL,
    `orden`         INT(11)     NOT NULL DEFAULT 1,
    `created_at`    TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_bono_viajero` (`bono_id`, `viajero_id`),
    KEY `idx_bono`    (`bono_id`),
    KEY `idx_viajero` (`viajero_id`),

    CONSTRAINT `programa_bono_viajeros_ibfk_1`
        FOREIGN KEY (`bono_id`)
        REFERENCES `programa_bonos` (`id`)
        ON DELETE CASCADE,

    CONSTRAINT `programa_bono_viajeros_ibfk_2`
        FOREIGN KEY (`viajero_id`)
        REFERENCES `viajeros` (`id`)
        ON DELETE RESTRICT

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO db_migrations (migration) VALUES ('006_create_programa_bono_viajeros');
