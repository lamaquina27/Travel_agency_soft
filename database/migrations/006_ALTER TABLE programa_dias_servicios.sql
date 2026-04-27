-- MIGRACIÓN 006: Nuevo campo para vincular acomodaciones en el día a día y agregar la descripción a las acomodaciones

ALTER TABLE acomodaciones
ADD COLUMN descripcion VARCHAR(255) NULL AFTER tipo_acomodacion;

ALTER TABLE programa_dias_servicios
ADD COLUMN acomodacion_id INT NULL AFTER biblioteca_item_id,
ADD KEY idx_acomodacion_id (acomodacion_id),
ADD CONSTRAINT programa_dias_servicios_acomodacion_fk
FOREIGN KEY (acomodacion_id)
REFERENCES acomodaciones(id)
ON DELETE SET NULL;