-- MIGRACION 021: Reglas de asignación automática de operadores (Rooming)
-- Cada regla, por agencia, asigna un operador a los traslados que cumplan
-- las condiciones (todas opcionales, combinadas con AND):
--   airport_code: aeropuerto relevante (destino si IN, origen si OUT)
--   service_type: 'llevada_al_hotel' (IN) / 'llevada_al_aeropuerto' (OUT) / NULL = cualquiera
--   city: ciudad del traslado
CREATE TABLE IF NOT EXISTS `rooming_reglas` (
    `id`           INT(11)     NOT NULL AUTO_INCREMENT,
    `agencia_id`   INT(11)     NOT NULL,
    `operador_id`  INT(11)     NOT NULL,
    `airport_code` VARCHAR(10)     NULL DEFAULT NULL,
    `service_type` VARCHAR(50)     NULL DEFAULT NULL,
    `city`         VARCHAR(120)    NULL DEFAULT NULL,
    `activa`       TINYINT(1)  NOT NULL DEFAULT 1,
    `created_at`   TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    CONSTRAINT `fk_reglas_agencia`
        FOREIGN KEY (`agencia_id`) REFERENCES `agencias` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_reglas_operador`
        FOREIGN KEY (`operador_id`) REFERENCES `operadores` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX `idx_reglas_agencia` (`agencia_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
