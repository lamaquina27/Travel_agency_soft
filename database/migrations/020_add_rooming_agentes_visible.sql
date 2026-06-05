-- MIGRACION 020: Visibilidad del módulo Rooming para agentes
-- Permite que cada agencia decida si sus agentes ven el módulo de Traslados/Rooming.
-- Por defecto 0 (oculto): el admin siempre lo ve; los agentes solo si se activa.
ALTER TABLE `agencias`
    ADD COLUMN `rooming_agentes_visible` TINYINT(1) NOT NULL DEFAULT 0 AFTER `agent_secondary_color`;
