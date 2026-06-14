-- MIGRACIÓN 005 | Modificaciones a programa_solicitudes

-- 1. Alterar la estructura de la tabla
ALTER TABLE `programa_solicitudes`
    -- Renombrar columnas
    CHANGE `nombre_viajero` `nombre` VARCHAR(250) NOT NULL,
    CHANGE `apellido_viajero` `apellido` VARCHAR(250) NOT NULL,
    
    -- Cambiar tipos de fecha para incluir horas (usamos DATETIME en vez de TIMESTAMP por la limitación del año 2038)
    MODIFY `fecha_llegada` DATETIME NOT NULL,
    MODIFY `fecha_salida` DATETIME NOT NULL,
    
    -- Añadir la columna de la Clave Foránea del titular
    ADD COLUMN `titular_id` INT(11) DEFAULT NULL AFTER `apellido`,
    
    -- Añadir el boleano para saber si fue comprado
    ADD COLUMN `comprado` TINYINT(1) NOT NULL DEFAULT 0;

-- 2. Añadir la restricción de Clave Foránea
ALTER TABLE `programa_solicitudes`
    ADD CONSTRAINT `ps_titular_fk` 
    FOREIGN KEY (`titular_id`) 
    REFERENCES `viajeros`(`id`) 
    ON DELETE SET NULL;

-- 3. Registro de la migración
INSERT IGNORE INTO db_migrations (migration) VALUES ('005_alter_programa_solicitudes');
