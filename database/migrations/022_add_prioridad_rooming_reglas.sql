-- MIGRACION 022: Prioridad en reglas de asignación de Rooming
-- Menor número = mayor prioridad. Al aplicar reglas, gana la primera que
-- coincida según este orden (regla más específica/prioritaria primero).
ALTER TABLE `rooming_reglas`
    ADD COLUMN `prioridad` INT(11) NOT NULL DEFAULT 0 AFTER `city`;
