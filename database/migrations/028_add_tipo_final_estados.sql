-- =====================================================================
-- 028_add_tipo_final_estados.sql
-- Estados FINALES del pipeline con distinción Ganado / Perdido.
-- QUÉ HACE:
--   Añade `tipo_final` que CALIFICA a los estados con es_final = 1.
--     - es_final = 0            -> estado normal (en curso). tipo_final = NULL.
--     - es_final = 1 + 'ganado' -> cierre ganado (venta concretada).
--     - es_final = 1 + 'perdido'-> cierre perdido (lead caído).
--   `es_final` se conserva tal cual (no se toca su lógica existente).
--
-- Idempotente-ish: si la columna ya existe, este ALTER dará error de
-- "Duplicate column"; en ese caso simplemente ignóralo (ya está aplicada).
-- =====================================================================

ALTER TABLE `pipeline_estados`
    ADD COLUMN `tipo_final` ENUM('ganado','perdido') NULL DEFAULT NULL AFTER `es_final`;
