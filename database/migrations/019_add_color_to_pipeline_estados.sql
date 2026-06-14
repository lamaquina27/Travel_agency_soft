-- MIGRACION 019: Agrega campo color a pipeline_estados
-- Permite que cada agencia elija el color de cada columna del pipeline
ALTER TABLE `pipeline_estados`
    ADD COLUMN `color` VARCHAR(7) NOT NULL DEFAULT '#6366f1' AFTER `nombre`;


-- Migración 004: segundo tag por lead en pipeline
ALTER TABLE `pipeline`
    ADD COLUMN `tag_id2` INT NULL DEFAULT NULL AFTER `tag_id`;
