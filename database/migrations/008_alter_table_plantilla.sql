-- MIGRACIÓN 008: Nuevo campo para asignar una solicitud como plantilla

ALTER TABLE programa_solicitudes
ADD COLUMN plantilla BOOLEAN NOT NULL DEFAULT 0 AFTER comprado;

