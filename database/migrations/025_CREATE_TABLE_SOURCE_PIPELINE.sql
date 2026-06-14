CREATE TABLE IF NOT EXISTS pipeline_sources (
    id            int(11)    NOT NULL AUTO_INCREMENT,
    agencia_id    int(11)    NOT NULL,
    nombre        VARCHAR(100)      NOT NULL,
    created_at    TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_source_agencia
        FOREIGN KEY (agencia_id) REFERENCES agencias (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_source_agencia (agencia_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 

ALTER TABLE `pipeline`
    MODIFY COLUMN `source` INT(11) NULL,
    ADD CONSTRAINT `fk_source` FOREIGN KEY (`source`) REFERENCES `pipeline_sources` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;