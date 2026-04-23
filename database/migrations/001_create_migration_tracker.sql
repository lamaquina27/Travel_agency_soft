-- ============================================================
-- MIGRACIÓN 001: Tabla de control de migraciones
-- Fecha: 2026-04-14
-- Descripción: Crea la tabla que registra qué migraciones
--              ya fueron aplicadas en cada entorno (local/hosting)
-- ============================================================

CREATE TABLE IF NOT EXISTS db_migrations (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    migration   VARCHAR(255) NOT NULL UNIQUE,
    applied_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
