-- ============================================================
-- 1. Agregación de columnas check_in y check_out en biblioteca_alojamientos
-- ============================================================
ALTER TABLE `biblioteca_alojamientos`
    ADD COLUMN `check_in` TIME NULL DEFAULT NULL AFTER `sitio_web`,
    ADD COLUMN `check_out` TIME NULL DEFAULT NULL AFTER `check_in`;
