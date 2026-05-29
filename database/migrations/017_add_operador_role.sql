-- MIGRACION 017: Agrega el rol 'operador' al ENUM de users.role ;
-- El operador gestiona en campo los traslados de rooming asignados a el. ;

ALTER TABLE `users`
    MODIFY COLUMN `role` ENUM('superadmin','admin','agent','operador') NOT NULL DEFAULT 'agent';
