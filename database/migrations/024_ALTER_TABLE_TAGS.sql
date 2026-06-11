ALTER TABLE `tags`
    ADD COLUMN `tipo` ENUM('pipeline','itinerario') NOT NULL DEFAULT 'pipeline';


CREATE TABLE IF NOT EXISTS `itinerario_tags`(
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `solicitud_id` INT(11) NOT NULL,
    `tag_id` INT(11) NOT NULL,

    PRIMARY KEY (`id`) ,
    CONSTRAINT `it_solicitud` FOREIGN KEY (`solicitud_id`) REFERENCES `programa_solicitudes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `it_tag` FOREIGN KEY  (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY `uq_tag` (`solicitud_id`, `tag_id`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;