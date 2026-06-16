-- =====================================================================
-- 030_add_titulo_to_programa_adjuntos.sql
-- Título opcional para los adjuntos (archivos y enlaces) de un programa.
--
-- QUÉ HACE:
--   Añade `titulo` a programa_adjuntos para que el usuario pueda nombrar
--   cada archivo/enlace (más útil que mostrar el nombre crudo o la URL).
--   Es NULL por defecto: si no hay título, el front muestra el nombre del
--   archivo o la URL como hasta ahora (sin necesidad de backfill).
--
-- Idempotente-ish: si la columna ya existe, el ALTER dará "Duplicate column";
-- en ese caso ignóralo (ya aplicada).
--
-- Ejecutar:  mysql -u root travelag_travel_agency2 < 030_add_titulo_to_programa_adjuntos.sql
-- =====================================================================

ALTER TABLE `programa_adjuntos`
    ADD COLUMN `titulo` VARCHAR(255) NULL DEFAULT NULL AFTER `enlace`;
