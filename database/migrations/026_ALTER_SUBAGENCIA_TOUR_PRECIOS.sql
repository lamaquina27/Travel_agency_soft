-- ============================================================
-- MIGRACION 026: subagencia_tour_precios — campos extra
-- Espeja las 6 solapas adicionales de programa_precios (migración 010)
-- y añade el nombre del cliente para personalizar el link compartido.
-- ============================================================
ALTER TABLE `subagencia_tour_precios`
    ADD COLUMN `nombre_cliente`         VARCHAR(150) NULL AFTER `solicitud_id`,
    ADD COLUMN `visados_entrada`        TEXT NULL,
    ADD COLUMN `requisitos_sanitarios`  TEXT NULL,
    ADD COLUMN `llegada_punto_encuentro` TEXT NULL,
    ADD COLUMN `asistencia_emergencia`  TEXT NULL,
    ADD COLUMN `info_hoteles_servicios` TEXT NULL,
    ADD COLUMN `informacion_practica`   TEXT NULL;
