-- ============================================================
-- MIGRACIÓN 003: Normalizar rutas de imágenes en BD
-- Fecha: 2026-04-16
-- Descripción: Convierte URLs absolutas del hosting guardadas en
--              foto_portada a rutas relativas (/assets/uploads/...)
--              para que funcionen en cualquier entorno sin importar
--              el dominio (hosting, Mac local, Windows local).
--
-- ANTES: https://www.travelagentsoft.com/assets/uploads/agencia_2/...
-- DESPUÉS:                               /assets/uploads/agencia_2/...
-- ============================================================

UPDATE programa_personalizacion
SET foto_portada = CONCAT(
    '/',
    SUBSTRING(foto_portada,
        LOCATE('/assets/', foto_portada)
    )
)
WHERE foto_portada LIKE 'http%/assets/%'
  AND foto_portada NOT LIKE '/%';

-- Registrar migración como aplicada
INSERT IGNORE INTO db_migrations (migration) VALUES ('003_normalize_image_paths');
