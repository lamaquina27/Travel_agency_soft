-- ============================================================
-- 1. Agregacion de columnas para solapas en programa_precios 
-- ============================================================

ALTER TABLE `programa_precios`
    ADD COLUMN `visados_entrada` TEXT DEFAULT NULL,
    ADD COLUMN `requisitos_sanitarios` TEXT DEFAULT NULL,
    ADD COLUMN `llegada_punto_encuentro` TEXT DEFAULT NULL,
    ADD COLUMN `asistencia_emergencia` TEXT DEFAULT NULL,
    ADD COLUMN `info_hoteles_servicios` TEXT DEFAULT NULL,
    ADD COLUMN `informacion_practica` TEXT DEFAULT NULL;



-- ============================================================
-- 2. Agregacion de columnas para solapas en biblioteca_plantillas_precios 
-- ============================================================

ALTER TABLE `biblioteca_plantillas_precios`
    ADD COLUMN `visados_entrada` TEXT DEFAULT NULL,
    ADD COLUMN `requisitos_sanitarios` TEXT DEFAULT NULL,
    ADD COLUMN `llegada_punto_encuentro` TEXT DEFAULT NULL,
    ADD COLUMN `asistencia_emergencia` TEXT DEFAULT NULL,
    ADD COLUMN `info_hoteles_servicios` TEXT DEFAULT NULL,
    ADD COLUMN `informacion_practica` TEXT DEFAULT NULL;