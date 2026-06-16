-- =====================================================================
-- 029_add_seleccionado_servicios.sql
-- Selección de alternativa de hotel por parte del cliente (link compartido).
--
-- QUÉ HACE:
--   Añade `seleccionado` para registrar qué opción eligió el cliente en cada
--   grupo (principal + sus alternativas). Semántica:
--     - seleccionado = 1  -> esta es la opción elegida por el cliente.
--     - Si NINGUNA fila del grupo tiene seleccionado=1 -> vale el PRINCIPAL
--       (comportamiento por defecto, sin necesidad de backfill).
--   El bono y el PDF usan la opción "efectiva" (la marcada, o el principal).
--   La elección se bloquea cuando el programa se marca como vendido (comprado=1).
--
-- Idempotente-ish: si la columna ya existe, el ALTER dará "Duplicate column";
-- en ese caso ignóralo (ya aplicada).
--
-- Ejecutar:  mysql -u root travelag_travel_agency2 < 029_add_seleccionado_servicios.sql
-- =====================================================================

ALTER TABLE `programa_dias_servicios`
    ADD COLUMN `seleccionado` TINYINT(1) NOT NULL DEFAULT 0 AFTER `variacion_precio`;
