-- MariaDB dump 10.19  Distrib 10.4.28-MariaDB, for osx10.10 (x86_64)
--
-- Host: localhost    Database: travelag_travel_agency2
-- ------------------------------------------------------
-- Server version	10.4.28-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `agencias`
--

DROP TABLE IF EXISTS `agencias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agencias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `nit` varchar(50) DEFAULT NULL,
  `direccion` varchar(300) DEFAULT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  `admin_primary_color` varchar(7) DEFAULT '#e53e3e',
  `admin_secondary_color` varchar(7) DEFAULT '#fd746c',
  `agent_primary_color` varchar(7) DEFAULT '#667eea',
  `agent_secondary_color` varchar(7) DEFAULT '#764ba2',
  `email_contacto` varchar(100) DEFAULT NULL,
  `fecha_inicio_suscripcion` date NOT NULL,
  `fecha_fin_suscripcion` date NOT NULL,
  `estado_suscripcion` enum('activa','inactiva','suspendida') NOT NULL DEFAULT 'activa',
  `max_usuarios` int(11) NOT NULL DEFAULT 10,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `agencias_historial`
--

DROP TABLE IF EXISTS `agencias_historial`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agencias_historial` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agencia_id` int(11) NOT NULL,
  `tipo_evento` enum('creacion','edicion','renovacion_suscripcion','suspension','activacion','cambio_limite_usuarios','creacion_usuario','edicion_usuario','activacion_usuario','desactivacion_usuario') NOT NULL,
  `descripcion` text DEFAULT NULL,
  `usuario_superadmin_id` int(11) DEFAULT NULL,
  `datos_anteriores` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos_anteriores`)),
  `datos_nuevos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos_nuevos`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `usuario_superadmin_id` (`usuario_superadmin_id`),
  KEY `idx_agencia_id` (`agencia_id`),
  KEY `idx_tipo_evento` (`tipo_evento`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `agencias_historial_ibfk_1` FOREIGN KEY (`agencia_id`) REFERENCES `agencias` (`id`) ON DELETE CASCADE,
  CONSTRAINT `agencias_historial_ibfk_2` FOREIGN KEY (`usuario_superadmin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `biblioteca_actividades`
--

DROP TABLE IF EXISTS `biblioteca_actividades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `biblioteca_actividades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idioma` varchar(5) NOT NULL DEFAULT 'es',
  `nombre` varchar(300) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `ubicacion` varchar(255) DEFAULT NULL,
  `latitud` decimal(10,8) DEFAULT NULL,
  `longitud` decimal(11,8) DEFAULT NULL,
  `imagen1` varchar(255) DEFAULT NULL,
  `imagen2` varchar(255) DEFAULT NULL,
  `imagen3` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `user_id` int(11) DEFAULT NULL,
  `agencia_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_agencia_actividades` (`agencia_id`),
  CONSTRAINT `biblioteca_actividades_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `biblioteca_alojamientos`
--

DROP TABLE IF EXISTS `biblioteca_alojamientos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `biblioteca_alojamientos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idioma` varchar(5) NOT NULL DEFAULT 'es',
  `nombre` varchar(300) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `ubicacion` varchar(255) DEFAULT NULL,
  `latitud` decimal(10,8) DEFAULT NULL,
  `longitud` decimal(11,8) DEFAULT NULL,
  `tipo` enum('hotel','camping','casa_huespedes','crucero','lodge','atipico','campamento','camping_car','tren') NOT NULL,
  `categoria` int(11) DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `sitio_web` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `user_id` int(11) DEFAULT NULL,
  `agencia_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_agencia_alojamientos` (`agencia_id`),
  CONSTRAINT `biblioteca_alojamientos_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=81 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `biblioteca_dias`
--

DROP TABLE IF EXISTS `biblioteca_dias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `biblioteca_dias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idioma` varchar(5) NOT NULL DEFAULT 'es',
  `titulo` varchar(300) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `ubicacion` varchar(255) DEFAULT NULL,
  `latitud` decimal(10,8) DEFAULT NULL,
  `longitud` decimal(11,8) DEFAULT NULL,
  `imagen1` varchar(255) DEFAULT NULL,
  `imagen2` varchar(255) DEFAULT NULL,
  `imagen3` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `user_id` int(11) DEFAULT NULL,
  `agencia_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_agencia_dias` (`agencia_id`),
  CONSTRAINT `biblioteca_dias_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=209 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `biblioteca_dias_ubicaciones_secundarias`
--

DROP TABLE IF EXISTS `biblioteca_dias_ubicaciones_secundarias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `biblioteca_dias_ubicaciones_secundarias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dia_id` int(11) NOT NULL,
  `agencia_id` int(11) NOT NULL,
  `ubicacion` varchar(255) NOT NULL,
  `latitud` decimal(10,8) DEFAULT NULL,
  `longitud` decimal(11,8) DEFAULT NULL,
  `orden` int(11) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_dia_orden` (`dia_id`,`orden`),
  KEY `idx_agencia_ubicaciones` (`agencia_id`),
  CONSTRAINT `biblioteca_dias_ubicaciones_secundarias_ibfk_1` FOREIGN KEY (`dia_id`) REFERENCES `biblioteca_dias` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=195 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `biblioteca_plantillas_precios`
--

DROP TABLE IF EXISTS `biblioteca_plantillas_precios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `biblioteca_plantillas_precios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agencia_id` int(11) NOT NULL,
  `precio_incluye` text DEFAULT NULL,
  `precio_no_incluye` text DEFAULT NULL,
  `condiciones_generales` text DEFAULT NULL,
  `info_pasaporte` text DEFAULT NULL,
  `info_seguros` text DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_agencia_plantilla` (`agencia_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `biblioteca_plantillas_precios_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `biblioteca_plantillas_precios_ibfk_2` FOREIGN KEY (`agencia_id`) REFERENCES `agencias` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `biblioteca_transportes`
--

DROP TABLE IF EXISTS `biblioteca_transportes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `biblioteca_transportes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idioma` varchar(5) NOT NULL DEFAULT 'es',
  `medio` enum('bus','avion','coche','barco','tren') NOT NULL,
  `titulo` varchar(300) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `lugar_salida` varchar(255) DEFAULT NULL,
  `lugar_llegada` varchar(255) DEFAULT NULL,
  `lat_salida` decimal(10,8) DEFAULT NULL,
  `lng_salida` decimal(11,8) DEFAULT NULL,
  `lat_llegada` decimal(10,8) DEFAULT NULL,
  `lng_llegada` decimal(11,8) DEFAULT NULL,
  `duracion` varchar(50) DEFAULT NULL,
  `distancia_km` decimal(8,2) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `user_id` int(11) DEFAULT NULL,
  `agencia_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_agencia_transportes` (`agencia_id`),
  CONSTRAINT `biblioteca_transportes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `company_settings`
--

DROP TABLE IF EXISTS `company_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `company_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_name` varchar(100) NOT NULL DEFAULT 'Travel Agency',
  `logo_url` varchar(255) DEFAULT NULL,
  `background_image` varchar(255) DEFAULT NULL,
  `admin_primary_color` varchar(7) DEFAULT '#e53e3e',
  `admin_secondary_color` varchar(7) DEFAULT '#fd746c',
  `agent_primary_color` varchar(7) DEFAULT '#667eea',
  `agent_secondary_color` varchar(7) DEFAULT '#764ba2',
  `login_bg_color` varchar(7) DEFAULT '#667eea',
  `login_secondary_color` varchar(7) DEFAULT '#764ba2',
  `default_language` varchar(5) DEFAULT 'es',
  `session_timeout` int(11) DEFAULT 60,
  `max_file_size` int(11) DEFAULT 10,
  `backup_frequency` enum('daily','weekly','monthly','never') DEFAULT 'weekly',
  `maintenance_mode` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `config_uploads`
--

DROP TABLE IF EXISTS `config_uploads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `config_uploads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_size` int(11) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `upload_type` enum('logo','background','general') NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `uploaded_by` (`uploaded_by`),
  CONSTRAINT `config_uploads_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `db_migrations`
--

DROP TABLE IF EXISTS `db_migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `db_migrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `migration` (`migration`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `programa_dias`
--

DROP TABLE IF EXISTS `programa_dias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `programa_dias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `solicitud_id` int(11) NOT NULL,
  `dia_numero` int(11) NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `duracion_estancia` int(11) DEFAULT 1,
  `biblioteca_dia_id` int(11) DEFAULT NULL COMMENT 'Referencia histórica al día de biblioteca original',
  `ubicacion` varchar(200) DEFAULT NULL,
  `latitud` decimal(10,8) DEFAULT NULL,
  `longitud` decimal(11,8) DEFAULT NULL,
  `fecha_dia` date DEFAULT NULL,
  `imagen1` varchar(500) DEFAULT NULL,
  `imagen2` varchar(500) DEFAULT NULL,
  `imagen3` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `comidas_incluidas` tinyint(1) DEFAULT 0,
  `desayuno` tinyint(1) DEFAULT 0,
  `almuerzo` tinyint(1) DEFAULT 0,
  `cena` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_solicitud_dia` (`solicitud_id`,`dia_numero`),
  KEY `idx_biblioteca_dia` (`biblioteca_dia_id`)
) ENGINE=InnoDB AUTO_INCREMENT=436 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `programa_dias_servicios`
--

DROP TABLE IF EXISTS `programa_dias_servicios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `programa_dias_servicios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `programa_dia_id` int(11) NOT NULL,
  `tipo_servicio` enum('actividad','transporte','alojamiento') NOT NULL,
  `biblioteca_item_id` int(11) NOT NULL,
  `nombre_servicio` varchar(300) DEFAULT NULL COMMENT 'Nombre copiado del servicio',
  `descripcion_servicio` text DEFAULT NULL COMMENT 'Descripción copiada del servicio',
  `ubicacion_servicio` varchar(255) DEFAULT NULL COMMENT 'Ubicación copiada del servicio',
  `latitud` decimal(10,8) DEFAULT NULL COMMENT 'Latitud copiada',
  `longitud` decimal(11,8) DEFAULT NULL COMMENT 'Longitud copiada',
  `actividad_imagen1` varchar(255) DEFAULT NULL COMMENT 'Imagen 1 de actividad',
  `actividad_imagen2` varchar(255) DEFAULT NULL COMMENT 'Imagen 2 de actividad',
  `actividad_imagen3` varchar(255) DEFAULT NULL COMMENT 'Imagen 3 de actividad',
  `actividad_idioma` varchar(5) DEFAULT NULL COMMENT 'Idioma de actividad',
  `transporte_medio` enum('bus','avion','coche','barco','tren') DEFAULT NULL COMMENT 'Medio de transporte',
  `transporte_titulo` varchar(300) DEFAULT NULL COMMENT 'Título del transporte',
  `transporte_lugar_salida` varchar(255) DEFAULT NULL COMMENT 'Lugar de salida',
  `transporte_lugar_llegada` varchar(255) DEFAULT NULL COMMENT 'Lugar de llegada',
  `transporte_lat_salida` decimal(10,8) DEFAULT NULL COMMENT 'Latitud de salida',
  `transporte_lng_salida` decimal(11,8) DEFAULT NULL COMMENT 'Longitud de salida',
  `transporte_lat_llegada` decimal(10,8) DEFAULT NULL COMMENT 'Latitud de llegada',
  `transporte_lng_llegada` decimal(11,8) DEFAULT NULL COMMENT 'Longitud de llegada',
  `transporte_duracion` varchar(50) DEFAULT NULL COMMENT 'Duración del viaje',
  `transporte_distancia_km` decimal(8,2) DEFAULT NULL COMMENT 'Distancia en km',
  `transporte_idioma` varchar(5) DEFAULT NULL COMMENT 'Idioma del transporte',
  `alojamiento_tipo` enum('hotel','camping','casa_huespedes','crucero','lodge','atipico','campamento','camping_car','tren') DEFAULT NULL COMMENT 'Tipo de alojamiento',
  `alojamiento_categoria` int(11) DEFAULT NULL COMMENT 'Categoría/estrellas',
  `alojamiento_imagen` varchar(255) DEFAULT NULL COMMENT 'Imagen del alojamiento',
  `alojamiento_sitio_web` varchar(255) DEFAULT NULL COMMENT 'Sitio web del alojamiento',
  `alojamiento_idioma` varchar(5) DEFAULT NULL COMMENT 'Idioma del alojamiento',
  `orden` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `servicio_principal_id` int(11) DEFAULT NULL COMMENT 'ID del servicio principal (NULL si es principal)',
  `es_alternativa` tinyint(1) DEFAULT 0 COMMENT '0=Principal, 1=Alternativa',
  `orden_alternativa` int(11) DEFAULT 0 COMMENT 'Orden dentro de las alternativas (0 para principal)',
  `notas_alternativa` text DEFAULT NULL COMMENT 'Notas específicas de esta alternativa',
  PRIMARY KEY (`id`),
  KEY `idx_servicio_principal` (`servicio_principal_id`),
  KEY `idx_es_alternativa` (`es_alternativa`),
  KEY `idx_orden_alternativa` (`orden_alternativa`),
  KEY `idx_tipo_servicio` (`tipo_servicio`),
  KEY `idx_programa_dia_tipo` (`programa_dia_id`,`tipo_servicio`)
) ENGINE=InnoDB AUTO_INCREMENT=578 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `programa_dias_ubicaciones_secundarias`
--

DROP TABLE IF EXISTS `programa_dias_ubicaciones_secundarias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `programa_dias_ubicaciones_secundarias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `programa_dia_id` int(11) NOT NULL COMMENT 'FK a programa_dias',
  `ubicacion` varchar(255) NOT NULL,
  `latitud` decimal(10,8) DEFAULT NULL,
  `longitud` decimal(11,8) DEFAULT NULL,
  `orden` int(11) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_programa_dia` (`programa_dia_id`),
  KEY `idx_orden` (`programa_dia_id`,`orden`),
  CONSTRAINT `fk_prog_dias_ubic_sec_dia` FOREIGN KEY (`programa_dia_id`) REFERENCES `programa_dias` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=150 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Ubicaciones secundarias de días en programas (aisladas de biblioteca)';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `programa_personalizacion`
--

DROP TABLE IF EXISTS `programa_personalizacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `programa_personalizacion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `solicitud_id` int(11) NOT NULL,
  `agencia_id` int(11) NOT NULL,
  `titulo_programa` varchar(300) DEFAULT NULL,
  `idioma_predeterminado` varchar(5) DEFAULT 'es',
  `foto_portada` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_solicitud` (`solicitud_id`),
  KEY `idx_agencia_personalizacion` (`agencia_id`)
) ENGINE=InnoDB AUTO_INCREMENT=111 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `programa_precios`
--

DROP TABLE IF EXISTS `programa_precios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `programa_precios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `solicitud_id` int(11) NOT NULL,
  `moneda` varchar(3) DEFAULT 'USD',
  `precio_adulto` decimal(10,2) DEFAULT NULL,
  `precio_nino` decimal(10,2) DEFAULT NULL,
  `cantidad_adultos` int(11) DEFAULT 1,
  `cantidad_ninos` int(11) DEFAULT 0,
  `precio_total` decimal(10,2) DEFAULT NULL,
  `noches_incluidas` int(11) DEFAULT 0,
  `precio_incluye` text DEFAULT NULL,
  `precio_no_incluye` text DEFAULT NULL,
  `condiciones_generales` text DEFAULT NULL,
  `movilidad_reducida` tinyint(1) DEFAULT 0,
  `info_pasaporte` text DEFAULT NULL,
  `info_seguros` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_solicitud_precio` (`solicitud_id`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `programa_solicitudes`
--

DROP TABLE IF EXISTS `programa_solicitudes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `programa_solicitudes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_solicitud` varchar(50) DEFAULT NULL,
  `nombre_viajero` varchar(250) NOT NULL,
  `apellido_viajero` varchar(250) NOT NULL,
  `destino` varchar(200) NOT NULL,
  `fecha_llegada` date NOT NULL,
  `fecha_salida` date NOT NULL,
  `numero_pasajeros` int(11) NOT NULL DEFAULT 1,
  `acompanamiento` varchar(50) DEFAULT 'sin-acompanamiento',
  `user_id` int(11) NOT NULL,
  `agencia_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `public_token` varchar(32) DEFAULT NULL,
  `preview_token` varchar(32) DEFAULT NULL,
  `itinerary_token` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_id_solicitud` (`id_solicitud`),
  UNIQUE KEY `public_token` (`public_token`),
  UNIQUE KEY `preview_token` (`preview_token`),
  UNIQUE KEY `itinerary_token` (`itinerary_token`),
  KEY `idx_agencia_programas` (`agencia_id`)
) ENGINE=InnoDB AUTO_INCREMENT=112 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ubicaciones_principales`
--

DROP TABLE IF EXISTS `ubicaciones_principales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ubicaciones_principales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(300) NOT NULL,
  `nombre_completo` varchar(500) NOT NULL,
  `tipo` enum('ciudad','hotel','monumento','aeropuerto','estacion','parque','playa','restaurante','region','pais','otro') NOT NULL DEFAULT 'otro',
  `pais` varchar(100) DEFAULT NULL,
  `region` varchar(200) DEFAULT NULL,
  `latitud` decimal(10,8) NOT NULL,
  `longitud` decimal(11,8) NOT NULL,
  `place_id` varchar(100) DEFAULT NULL COMMENT 'ID de OpenStreetMap',
  `osm_type` varchar(50) DEFAULT NULL COMMENT 'Tipo en OSM (node, way, relation)',
  `agencia_id` int(11) DEFAULT NULL COMMENT 'NULL = global, INT = específica de agencia',
  `uso_count` int(11) DEFAULT 0 COMMENT 'Contador de veces usada',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_nombre` (`nombre`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_agencia` (`agencia_id`),
  KEY `idx_coords` (`latitud`,`longitud`),
  KEY `idx_uso` (`uso_count`),
  FULLTEXT KEY `idx_busqueda` (`nombre`,`nombre_completo`,`pais`,`region`),
  CONSTRAINT `ubicaciones_principales_ibfk_1` FOREIGN KEY (`agencia_id`) REFERENCES `agencias` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3563 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('superadmin','admin','agent') NOT NULL DEFAULT 'agent',
  `agencia_id` int(11) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `session_token` varchar(255) DEFAULT NULL COMMENT 'Token único de la sesión activa',
  `session_ip` varchar(50) DEFAULT NULL COMMENT 'IP de la sesión activa',
  `session_user_agent` varchar(500) DEFAULT NULL COMMENT 'Navegador y sistema de la sesión',
  `session_started_at` timestamp NULL DEFAULT NULL COMMENT 'Momento en que inició la sesión',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `unique_email` (`email`),
  KEY `agencia_id` (`agencia_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`agencia_id`) REFERENCES `agencias` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-04-16 23:20:12
