-- ============================================================
-- 1. Agregacion de columna para suplementos en programa_dias_servicios
-- ============================================================


ALTER TABLE `programa_dias_servicios`
    ADD COLUMN `variacion_precio` DECIMAL(12,2)  NULL AFTER `orden_alternativa`;