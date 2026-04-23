-- ============================================================
-- MIGRACIÓN 002: Campo mostrar_precio en programa_precios
-- Fecha: 2026-04-14
-- Descripción: Agrega campo para controlar si el precio es
--              visible en la vista pública del itinerario.
--              0 = oculto (default), 1 = visible
-- Responsable: Juan David Sánchez / Kevin Soler
-- ============================================================

ALTER TABLE programa_precios
    ADD COLUMN IF NOT EXISTS mostrar_precio TINYINT(1) NOT NULL DEFAULT 0
    AFTER info_seguros;

-- Registrar migración como aplicada
INSERT IGNORE INTO db_migrations (migration) VALUES ('002_add_mostrar_precio');
