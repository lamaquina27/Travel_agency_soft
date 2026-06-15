-- =====================================================================
-- 027_FIX_SOURCE_PIPELINE.sql
-- FIX de la migración 025 (NO se modifica la 025; esta la sustituye
-- "lógicamente": consigue el mismo objetivo final pero de forma segura).
--
-- OBJETIVO (igual que 025): que pipeline.source deje de ser texto libre
-- y pase a ser INT(11) con FOREIGN KEY a pipeline_sources(id).
--
-- POR QUÉ FALLABA LA 025:
--   La 025 hacía, en un solo ALTER, dos cosas sobre `pipeline.source`
--   (que es VARCHAR(80), creada así en la 009):
--       MODIFY COLUMN `source` INT(11) NULL,
--       ADD CONSTRAINT `fk_source` FOREIGN KEY (`source`) -> pipeline_sources(id)
--   Pero la columna contiene una MEZCLA de valores:
--     (a) ids numéricos ('3', '7'...) de leads creados ya con el catálogo
--         pipeline_sources, y
--     (b) TEXTO LIBRE legacy ('Web', 'Stand cc', 'Referido', ''...) de leads
--         creados ANTES de que existiera el catálogo.
--   Al convertir a INT, el texto legacy no se puede castear -> error 1366
--   "Incorrect integer value" (en modo estricto) o se volvería 0. Y aunque
--   pasara, al AÑADIR la FK, esos valores (texto->0, o ids que no existen en
--   la tabla pipeline_sources recién creada/vacía) no tienen fila padre ->
--   error 1452 "Cannot add or update a child row: a foreign key constraint
--   fails". Como las dos cláusulas van en el MISMO ALTER, todo se revierte:
--   la columna se queda VARCHAR(80) y SIN la FK. Solo cuajó el CREATE TABLE
--   pipeline_sources (por eso esa tabla sí existe).
--
-- QUÉ HACE ESTE FIX (en orden): primero NORMALIZA los datos y solo entonces
--   cambia el tipo y añade la FK.
--     1) Asegura que pipeline_sources existe (idempotente).
--     2) Vacíos/espacios -> NULL.
--     3) Texto legacy -> crea su fila en pipeline_sources (por agencia) y
--        reemplaza el texto por el id correspondiente.
--     4) Ids numéricos huérfanos (sin fila padre) -> NULL (evita el 1452).
--     5) Quita la FK fk_source si por algún intento previo existiera.
--     6) Convierte la columna a INT(11).
--     7) Añade la FK fk_source.
--
-- Es re-ejecutable (idempotente). Pensado para correr a mano en tu MySQL:
--     mysql -u root travelag_travel_agency2 < 027_FIX_SOURCE_PIPELINE.sql
-- Recomendado: haz un backup antes ->
--     mysqldump -u root travelag_travel_agency2 pipeline pipeline_sources > backup_source.sql
-- =====================================================================

-- ── 1) Red de seguridad: la tabla catálogo debe existir (la creó la 025) ──
CREATE TABLE IF NOT EXISTS pipeline_sources (
    id            INT(11)      NOT NULL AUTO_INCREMENT,
    agencia_id    INT(11)      NOT NULL,
    nombre        VARCHAR(100) NOT NULL,
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_source_agencia
        FOREIGN KEY (agencia_id) REFERENCES agencias (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_source_agencia (agencia_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── (Opcional) Diagnóstico ANTES: mira qué hay en la columna source ──
-- Quita el comentario para inspeccionar antes de ejecutar el resto.
-- SELECT
--     CASE
--         WHEN source IS NULL OR TRIM(source) = '' THEN '(vacío/NULL)'
--         WHEN source REGEXP '^[0-9]+$'            THEN '(id numérico)'
--         ELSE '(texto legacy)'
--     END AS tipo_valor,
--     source, COUNT(*) AS filas
-- FROM pipeline
-- GROUP BY tipo_valor, source
-- ORDER BY tipo_valor, filas DESC;

-- ── 2) Normalizar vacíos y espacios a NULL ──
UPDATE pipeline
SET source = NULL
WHERE source IS NOT NULL AND TRIM(source) = '';

-- (Opcional) recorta espacios sobrantes del texto legacy para que mapee bien
UPDATE pipeline
SET source = TRIM(source)
WHERE source IS NOT NULL AND source <> TRIM(source);

-- ── 3a) Crear en el catálogo los orígenes de TEXTO legacy que falten ──
--     Uno por cada (agencia_id, nombre) que aún no exista en pipeline_sources.
INSERT INTO pipeline_sources (agencia_id, nombre)
SELECT DISTINCT p.agencia_id, p.source
FROM pipeline p
WHERE p.source IS NOT NULL
  AND p.source NOT REGEXP '^[0-9]+$'                 -- es texto, no un id
  AND p.agencia_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM pipeline_sources s
      WHERE s.agencia_id = p.agencia_id
        AND s.nombre     = p.source
  );

-- ── 3b) Reemplazar el texto legacy por el id del catálogo ──
UPDATE pipeline p
JOIN pipeline_sources s
     ON s.agencia_id = p.agencia_id
    AND s.nombre     = p.source
SET p.source = s.id
WHERE p.source IS NOT NULL
  AND p.source NOT REGEXP '^[0-9]+$';

-- ── 4) Ids numéricos que NO tienen fila padre en el catálogo -> NULL ──
--     (evita el error 1452 al añadir la FK). Conserva "Sin origen" como NULL.
UPDATE pipeline p
LEFT JOIN pipeline_sources s ON s.id = CAST(p.source AS UNSIGNED)
SET p.source = NULL
WHERE p.source IS NOT NULL
  AND p.source REGEXP '^[0-9]+$'
  AND s.id IS NULL;

-- ── 5) Quitar la FK fk_source si existiera (hace el script re-ejecutable) ──
SET @fk_exists := (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME        = 'pipeline'
      AND CONSTRAINT_NAME   = 'fk_source'
      AND CONSTRAINT_TYPE   = 'FOREIGN KEY'
);
SET @sql_drop := IF(@fk_exists > 0,
    'ALTER TABLE pipeline DROP FOREIGN KEY fk_source',
    'SELECT 1');
PREPARE st FROM @sql_drop; EXECUTE st; DEALLOCATE PREPARE st;

-- ── 6) Ahora todos los valores son NULL o un id válido: convertir a INT ──
ALTER TABLE pipeline
    MODIFY COLUMN `source` INT(11) NULL;

-- ── 7) Añadir la FK definitiva (idéntica a la que pretendía la 025) ──
ALTER TABLE pipeline
    ADD CONSTRAINT `fk_source`
        FOREIGN KEY (`source`) REFERENCES `pipeline_sources` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE;

-- ── (Opcional) Verificación DESPUÉS ──
-- SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE
-- FROM information_schema.COLUMNS
-- WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pipeline' AND COLUMN_NAME = 'source';
-- SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
-- FROM information_schema.KEY_COLUMN_USAGE
-- WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pipeline' AND CONSTRAINT_NAME = 'fk_source';
