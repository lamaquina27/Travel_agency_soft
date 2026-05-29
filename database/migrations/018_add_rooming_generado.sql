-- MIGRACION 018: Bandera de generacion del Rooming List por programa ;
-- rooming_generado evita regenerar o duplicar el rooming de un programa ya procesado ;
-- Se pone en 1 al generar y se resetea a 0 si el programa se desmarca como vendido ;

ALTER TABLE `programa_solicitudes`
    ADD COLUMN `rooming_generado` TINYINT(1) NOT NULL DEFAULT 0 AFTER `comprado`;
