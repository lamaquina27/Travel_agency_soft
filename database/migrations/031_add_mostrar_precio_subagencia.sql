-- =====================================================================
-- 031_add_mostrar_precio_subagencia.sql
-- La subagencia puede mostrar/ocultar el precio de forma INDEPENDIENTE
-- en su propio link, sin depender del tour de la agencia principal.
--
-- QUÉ HACE:
--   Añade `mostrar_precio` a subagencia_tour_precios. Semántica:
--     - NULL  -> hereda lo que decida el tour principal (comportamiento previo).
--     - 1     -> la subagencia MUESTRA el precio a su cliente.
--     - 0     -> la subagencia OCULTA el precio a su cliente.
--   Es NULL por defecto: las asignaciones existentes siguen igual hasta que
--   la subagencia elija explícitamente (sin necesidad de backfill).
--
-- Idempotente-ish: si la columna ya existe, el ALTER dará "Duplicate column";
-- en ese caso ignóralo (ya aplicada).
--
-- Ejecutar:  mysql -u root travelag_travel_agency2 < 031_add_mostrar_precio_subagencia.sql
-- =====================================================================

ALTER TABLE `subagencia_tour_precios`
    ADD COLUMN `mostrar_precio` TINYINT(1) NULL DEFAULT NULL AFTER `noches_incluidas`;
