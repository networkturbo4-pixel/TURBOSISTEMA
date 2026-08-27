-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: turbosaas_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

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
-- Table structure for table `actas`
--

DROP TABLE IF EXISTS `actas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `actas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `folio` varchar(20) NOT NULL,
  `prefijo` varchar(10) DEFAULT 'LIM-',
  `token` varchar(64) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `cliente_nombre` varchar(255) DEFAULT NULL,
  `cliente_dni_ruc` varchar(50) DEFAULT NULL,
  `cliente_direccion` text DEFAULT NULL,
  `cliente_distrito` varchar(100) DEFAULT NULL,
  `cliente_referencia` text DEFAULT NULL,
  `cliente_whatsapp` varchar(50) DEFAULT NULL,
  `cliente_celular_alt` varchar(50) DEFAULT NULL,
  `cliente_gps_lat` varchar(50) DEFAULT NULL,
  `cliente_gps_lng` varchar(50) DEFAULT NULL,
  `foto_rostro_path` varchar(255) DEFAULT NULL,
  `pe_nodo` varchar(100) DEFAULT NULL,
  `pe_nap` varchar(100) DEFAULT NULL,
  `pe_puerto` varchar(50) DEFAULT NULL,
  `pe_potencia` varchar(50) DEFAULT NULL,
  `pe_atenuacion` varchar(50) DEFAULT NULL,
  `srv_fecha` date DEFAULT NULL,
  `srv_hora_inicio` time DEFAULT NULL,
  `srv_hora_fin` time DEFAULT NULL,
  `srv_tipo` varchar(100) DEFAULT NULL,
  `srv_estado` varchar(100) DEFAULT NULL,
  `tecnico_id` int(11) DEFAULT NULL,
  `red_ssid` varchar(100) DEFAULT NULL,
  `red_password` varchar(100) DEFAULT NULL,
  `red_speed_dl` varchar(50) DEFAULT NULL,
  `red_speed_ul` varchar(50) DEFAULT NULL,
  `red_n_tvs` int(11) DEFAULT NULL,
  `red_splitters` varchar(100) DEFAULT NULL,
  `red_senal_low` varchar(50) DEFAULT NULL,
  `red_senal_high` varchar(50) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `mantenimiento_6_meses` tinyint(1) DEFAULT 0,
  `calificacion_servicio` int(11) DEFAULT 0,
  `firma_cliente` text DEFAULT NULL,
  `firma_tecnico` text DEFAULT NULL,
  `cliente_rotulado` varchar(255) DEFAULT NULL,
  `servicio_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `folio` (`folio`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `actas_equipos`
--

DROP TABLE IF EXISTS `actas_equipos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `actas_equipos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `acta_id` int(11) NOT NULL,
  `accion` varchar(50) DEFAULT NULL,
  `modelo_marca` varchar(150) DEFAULT NULL,
  `serie_mac` varchar(150) DEFAULT NULL,
  `propiedad` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acta_id` (`acta_id`),
  CONSTRAINT `actas_equipos_ibfk_1` FOREIGN KEY (`acta_id`) REFERENCES `actas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `actas_fotos`
--

DROP TABLE IF EXISTS `actas_fotos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `actas_fotos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `acta_id` int(11) NOT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `ruta_archivo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acta_id` (`acta_id`),
  CONSTRAINT `actas_fotos_ibfk_1` FOREIGN KEY (`acta_id`) REFERENCES `actas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `actas_materiales`
--

DROP TABLE IF EXISTS `actas_materiales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `actas_materiales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `acta_id` int(11) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `cantidad` decimal(10,2) DEFAULT NULL,
  `unidad` varchar(50) DEFAULT NULL,
  `accion` varchar(50) DEFAULT NULL,
  `propiedad` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acta_id` (`acta_id`),
  CONSTRAINT `actas_materiales_ibfk_1` FOREIGN KEY (`acta_id`) REFERENCES `actas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `activos_documentos`
--

DROP TABLE IF EXISTS `activos_documentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activos_documentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vehiculo_id` int(11) NOT NULL,
  `tipo_documento` varchar(50) NOT NULL,
  `titulo` varchar(100) DEFAULT NULL,
  `url_archivo` varchar(255) NOT NULL,
  `fecha_subida` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `vehiculo_id` (`vehiculo_id`),
  CONSTRAINT `activos_documentos_ibfk_1` FOREIGN KEY (`vehiculo_id`) REFERENCES `activos_vehiculos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `activos_historial`
--

DROP TABLE IF EXISTS `activos_historial`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activos_historial` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vehiculo_id` int(11) NOT NULL,
  `tipo_evento` varchar(50) NOT NULL DEFAULT 'mantenimiento',
  `descripcion` text NOT NULL,
  `fotos_adjuntas` text DEFAULT NULL,
  `fecha_evento` date NOT NULL,
  `costo` decimal(10,2) DEFAULT 0.00,
  `registrado_por` int(11) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `vehiculo_id` (`vehiculo_id`),
  CONSTRAINT `activos_historial_ibfk_1` FOREIGN KEY (`vehiculo_id`) REFERENCES `activos_vehiculos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `activos_imagenes`
--

DROP TABLE IF EXISTS `activos_imagenes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activos_imagenes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vehiculo_id` int(11) NOT NULL,
  `url_imagen` varchar(255) NOT NULL,
  `tipo` varchar(50) DEFAULT 'general',
  `descripcion` text DEFAULT NULL,
  `fecha_subida` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `vehiculo_id` (`vehiculo_id`),
  CONSTRAINT `activos_imagenes_ibfk_1` FOREIGN KEY (`vehiculo_id`) REFERENCES `activos_vehiculos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `activos_vehiculos`
--

DROP TABLE IF EXISTS `activos_vehiculos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activos_vehiculos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo` varchar(50) NOT NULL DEFAULT 'vehiculo',
  `categoria` varchar(50) NOT NULL DEFAULT 'vehiculo',
  `nombre` varchar(150) DEFAULT NULL,
  `marca` varchar(100) DEFAULT NULL,
  `modelo` varchar(100) DEFAULT NULL,
  `ubicacion` varchar(150) DEFAULT NULL,
  `responsable_nombre` varchar(150) DEFAULT NULL,
  `detalles_extra` longtext DEFAULT NULL,
  `valor_adquisicion` decimal(10,2) DEFAULT 0.00,
  `fecha_adquisicion` date DEFAULT NULL,
  `placa` varchar(100) NOT NULL,
  `codigo_identificador` varchar(100) DEFAULT NULL,
  `estado` varchar(50) NOT NULL DEFAULT 'activo',
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `attendance_logs`
--

DROP TABLE IF EXISTS `attendance_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendance_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` enum('entrada','inicio_refrigerio','fin_refrigerio','salida','desconocido') NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `attendance_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `clientes`
--

DROP TABLE IF EXISTS `clientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_completo` varchar(255) NOT NULL,
  `dni` varchar(20) NOT NULL,
  `celular` varchar(20) DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `referencia` text DEFAULT NULL,
  `detalles_plan` text DEFAULT NULL,
  `fecha_servicio_contratado` datetime DEFAULT NULL,
  `inicio_servicio` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_id` int(11) DEFAULT NULL,
  `servicio_id` int(11) DEFAULT NULL,
  `latitud` varchar(50) DEFAULT NULL,
  `longitud` varchar(50) DEFAULT NULL,
  `router_os` varchar(50) DEFAULT 'mock',
  `router_ip` varchar(100) DEFAULT NULL,
  `router_port` varchar(20) DEFAULT NULL,
  `router_user` varchar(100) DEFAULT NULL,
  `router_pass` varchar(255) DEFAULT NULL,
  `router_mac_or_id` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `inventory_assignment_log`
--

DROP TABLE IF EXISTS `inventory_assignment_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_assignment_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sku_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `sku_code` varchar(50) DEFAULT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `assigned_to` int(11) NOT NULL,
  `assigned_to_name` varchar(255) DEFAULT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `assigned_by_name` varchar(255) DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT 1.00,
  `is_epp` tinyint(1) DEFAULT 0,
  `action` enum('assign','unassign') DEFAULT 'assign',
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `inventory_categories`
--

DROP TABLE IF EXISTS `inventory_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `inventory_entries`
--

DROP TABLE IF EXISTS `inventory_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_entries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sku_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `sku_id` (`sku_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `inventory_entries_ibfk_1` FOREIGN KEY (`sku_id`) REFERENCES `inventory_skus` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_entries_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=58 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `inventory_entry_photos`
--

DROP TABLE IF EXISTS `inventory_entry_photos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_entry_photos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entry_id` int(11) NOT NULL,
  `ruta_archivo` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `entry_id` (`entry_id`),
  CONSTRAINT `inventory_entry_photos_ibfk_1` FOREIGN KEY (`entry_id`) REFERENCES `inventory_entries` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `inventory_product_photos`
--

DROP TABLE IF EXISTS `inventory_product_photos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_product_photos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `ruta_archivo` varchar(255) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `uploaded_by` (`uploaded_by`),
  CONSTRAINT `inventory_product_photos_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `inventory_products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_product_photos_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `inventory_products`
--

DROP TABLE IF EXISTS `inventory_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `master_sku` varchar(50) DEFAULT NULL,
  `product_type` varchar(20) DEFAULT 'normal',
  `parent_product_id` int(11) DEFAULT NULL,
  `variant_brand` varchar(100) DEFAULT NULL,
  `variant_size` varchar(100) DEFAULT NULL,
  `variant_attributes` longtext DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `costo_producto` decimal(10,2) DEFAULT 0.00,
  `category_id` int(11) DEFAULT NULL,
  `total_quantity` int(11) DEFAULT 0,
  `stock_minimo` int(11) DEFAULT 10,
  `stock_critico` int(11) DEFAULT 3,
  `custom_columns` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`custom_columns`)),
  `bulk_custom_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_bulk` tinyint(1) DEFAULT 0,
  `unit_type` varchar(50) DEFAULT 'Unidades',
  `requires_photos` tinyint(1) DEFAULT 0,
  `product_image` varchar(255) DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `master_sku` (`master_sku`),
  KEY `category_id` (`category_id`),
  KEY `idx_parent_product_id` (`parent_product_id`),
  CONSTRAINT `fk_parent_product` FOREIGN KEY (`parent_product_id`) REFERENCES `inventory_products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `inventory_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=70 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `inventory_purchases`
--

DROP TABLE IF EXISTS `inventory_purchases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_purchases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `supplier_name` varchar(255) DEFAULT NULL,
  `invoice_number` varchar(100) DEFAULT NULL,
  `purchase_date` datetime NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 1.00,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(10) DEFAULT 'PEN',
  `document_path` varchar(255) DEFAULT NULL,
  `document_type` varchar(20) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `purchase_date` (`purchase_date`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `inventory_sku_photos`
--

DROP TABLE IF EXISTS `inventory_sku_photos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_sku_photos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sku_id` int(11) NOT NULL,
  `ruta_archivo` varchar(255) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `nota` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `sku_id` (`sku_id`),
  KEY `uploaded_by` (`uploaded_by`),
  CONSTRAINT `inventory_sku_photos_ibfk_1` FOREIGN KEY (`sku_id`) REFERENCES `inventory_skus` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_sku_photos_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `inventory_skus`
--

DROP TABLE IF EXISTS `inventory_skus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_skus` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `sku_code` varchar(20) NOT NULL,
  `status` enum('disponible','instalado','malogrado','reparado','en_transito','observacion') DEFAULT 'disponible',
  `custom_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`custom_data`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `assigned_to` int(11) DEFAULT NULL,
  `historia` enum('ninguno','devuelto','malogrado','antiguo','en_transito','observacion') DEFAULT 'ninguno',
  `is_epp` tinyint(1) DEFAULT 0,
  `is_deleted` tinyint(1) DEFAULT 0,
  `is_printed` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku_code` (`sku_code`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `inventory_skus_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `inventory_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1787 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `inventory_stock_log`
--

DROP TABLE IF EXISTS `inventory_stock_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_stock_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `sku_codes` text DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `inventory_user_stock`
--

DROP TABLE IF EXISTS `inventory_user_stock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_user_stock` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` decimal(10,2) DEFAULT 0.00,
  `is_epp` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_product` (`user_id`,`product_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `inventory_user_stock_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_user_stock_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `inventory_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `login_attempts`
--

DROP TABLE IF EXISTS `login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `identifier` varchar(255) NOT NULL,
  `attempt_time` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mapas_elementos`
--

DROP TABLE IF EXISTS `mapas_elementos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mapas_elementos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `proyecto_id` int(11) NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `nombre` varchar(255) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `geojson` text NOT NULL,
  `color` varchar(50) DEFAULT NULL,
  `icono` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `capacidad_puertos` int(11) DEFAULT 0,
  `potencia_dbm` varchar(50) DEFAULT '',
  `cable_origen` varchar(100) DEFAULT '',
  `splitter_tipo` varchar(50) DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `proyecto_id` (`proyecto_id`),
  CONSTRAINT `mapas_elementos_ibfk_1` FOREIGN KEY (`proyecto_id`) REFERENCES `mapas_proyectos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=102 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mapas_imagenes`
--

DROP TABLE IF EXISTS `mapas_imagenes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mapas_imagenes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `elemento_id` int(11) NOT NULL,
  `ruta` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `elemento_id` (`elemento_id`),
  CONSTRAINT `mapas_imagenes_ibfk_1` FOREIGN KEY (`elemento_id`) REFERENCES `mapas_elementos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mapas_proyectos`
--

DROP TABLE IF EXISTS `mapas_proyectos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mapas_proyectos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `archivado` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mapas_puertos`
--

DROP TABLE IF EXISTS `mapas_puertos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mapas_puertos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `elemento_id` int(11) NOT NULL,
  `numero_puerto` int(11) NOT NULL,
  `estado` varchar(20) DEFAULT 'Disponible',
  `cliente_nombre` varchar(255) DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_puerto` (`elemento_id`,`numero_puerto`),
  CONSTRAINT `mapas_puertos_ibfk_1` FOREIGN KEY (`elemento_id`) REFERENCES `mapas_elementos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=73 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mapas_puertos_historial`
--

DROP TABLE IF EXISTS `mapas_puertos_historial`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mapas_puertos_historial` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `puerto_id` int(11) NOT NULL,
  `accion` varchar(50) DEFAULT NULL,
  `cliente_nombre` varchar(255) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `puerto_id` (`puerto_id`),
  CONSTRAINT `mapas_puertos_historial_ibfk_1` FOREIGN KEY (`puerto_id`) REFERENCES `mapas_puertos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `role_permissions`
--

DROP TABLE IF EXISTS `role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `module` varchar(100) NOT NULL,
  `can_view` tinyint(1) DEFAULT 1,
  `can_create` tinyint(1) DEFAULT 0,
  `can_edit` tinyint(1) DEFAULT 0,
  `can_delete` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `servicios`
--

DROP TABLE IF EXISTS `servicios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `servicios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `velocidad` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ticket_attachments`
--

DROP TABLE IF EXISTS `ticket_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ticket_attachments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `message_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `message_id` (`message_id`),
  CONSTRAINT `ticket_attachments_ibfk_1` FOREIGN KEY (`message_id`) REFERENCES `ticket_messages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ticket_categories`
--

DROP TABLE IF EXISTS `ticket_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ticket_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `color` varchar(20) DEFAULT '#3b82f6',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ticket_messages`
--

DROP TABLE IF EXISTS `ticket_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ticket_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `is_system_message` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `ticket_messages_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ticket_messages_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=105 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ticket_priorities`
--

DROP TABLE IF EXISTS `ticket_priorities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ticket_priorities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `color` varchar(20) DEFAULT '#eab308',
  `level` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tickets`
--

DROP TABLE IF EXISTS `tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` int(11) DEFAULT NULL,
  `cliente_nombre_manual` varchar(255) DEFAULT NULL,
  `asunto` varchar(255) NOT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `prioridad_id` int(11) DEFAULT NULL,
  `gdrive_folder_id` varchar(255) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `estado` enum('nuevo','pendiente','en_proceso','terminado','eliminado') DEFAULT 'nuevo',
  `descripcion` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `public_token` varchar(64) DEFAULT NULL,
  `active_tech_id` int(11) DEFAULT NULL,
  `active_tech_ping` timestamp NULL DEFAULT NULL,
  `tech_typing_at` datetime DEFAULT NULL,
  `client_typing_at` datetime DEFAULT NULL,
  `live_lat` decimal(10,8) DEFAULT NULL,
  `live_lng` decimal(11,8) DEFAULT NULL,
  `live_user_id` int(11) DEFAULT NULL,
  `live_expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cliente_id` (`cliente_id`),
  KEY `categoria_id` (`categoria_id`),
  KEY `prioridad_id` (`prioridad_id`),
  KEY `assigned_to` (`assigned_to`),
  CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tickets_ibfk_2` FOREIGN KEY (`categoria_id`) REFERENCES `ticket_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tickets_ibfk_3` FOREIGN KEY (`prioridad_id`) REFERENCES `ticket_priorities` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tickets_ibfk_4` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `pin` varchar(20) DEFAULT NULL,
  `whatsapp` varchar(50) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `cover_picture` varchar(255) DEFAULT NULL,
  `barcode` varchar(100) DEFAULT NULL,
  `biometric_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `pin` (`pin`),
  UNIQUE KEY `barcode` (`barcode`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

-- ============================================================
-- DATOS INICIALES / SEMILLAS ESENCIALES DEL SISTEMA
-- ============================================================

-- Roles del Sistema
INSERT INTO `roles` (`id`, `name`, `description`) VALUES
(1, 'Gerente', 'Acceso total al sistema y reportes ejecutivos'),
(2, 'Tecnico', 'Operaciones de campo, tickets y actas'),
(3, 'Almacen', 'Gestión de inventarios, stock y materiales'),
(4, 'Administración', 'Gestión administrativa y facturación'),
(5, 'Cliente', 'Acceso limitado para clientes')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- Permisos iniciales por rol
INSERT INTO `role_permissions` (`role_id`, `module`, `can_view`, `can_create`, `can_edit`, `can_delete`) VALUES
(1, 'dashboard', 1, 1, 1, 1),
(1, 'actas', 1, 1, 1, 1),
(1, 'inventario', 1, 1, 1, 1),
(1, 'mochila', 1, 1, 1, 1),
(1, 'soporte', 1, 1, 1, 1),
(1, 'clientes', 1, 1, 1, 1),
(1, 'mapas', 1, 1, 1, 1),
(1, 'settings', 1, 1, 1, 1),
(1, 'sistema', 1, 1, 1, 1),
(2, 'dashboard', 1, 0, 0, 0),
(2, 'actas', 1, 1, 1, 0),
(2, 'mochila', 1, 1, 1, 0),
(2, 'soporte', 1, 1, 1, 0),
(3, 'dashboard', 1, 0, 0, 0),
(3, 'actas', 1, 0, 0, 0),
(3, 'mochila', 1, 1, 1, 0),
(3, 'inventario', 1, 1, 1, 1),
(4, 'dashboard', 1, 0, 0, 0),
(4, 'actas', 1, 1, 1, 0),
(4, 'clientes', 1, 1, 1, 0),
(5, 'dashboard', 1, 0, 0, 0);

-- Configuración General del Sistema
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('app_name', 'Turbo Perú'),
('bg_color', '#ffffff'),
('contact_email', 'contacto@turbosaas.com'),
('currency', 'PEN'),
('date_format', 'Y-m-d'),
('favicon', ''),
('global_notification_banner', ''),
('global_notification_push', '0'),
('hover_effect', 'shadow'),
('logo_collapsed_dark', ''),
('logo_collapsed_light', ''),
('logo_dark', ''),
('logo_light', ''),
('logo_pwa', ''),
('maintenance_mode', '0'),
('phone_main', ''),
('phone_secondary', ''),
('primary_color_dark', '#f07d00'),
('primary_color_light', '#0e4194'),
('ruc', ''),
('slogan', 'Internet y Telecomunicaciones'),
('social_links', '{}'),
('text_color', '#333333'),
('toast_position', 'bottom-right'),
('toast_style', 'card'),
('typography', 'Outfit'),
('website', ''),
('work_hours', '08:00 - 18:00'),
('zkteco_ip', '192.168.1.201'),
('zkteco_port', '4370')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

-- Categorías de Tickets
INSERT INTO `ticket_categories` (`id`, `name`, `color`) VALUES
(1, 'AVERIA', '#ff5900'),
(2, 'MANTENIMIENTO', '#6714ff'),
(3, 'SOPORTE', '#63db00')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- Prioridades de Tickets
INSERT INTO `ticket_priorities` (`id`, `name`, `color`, `level`) VALUES
(1, 'Baja', '#082ee7', 1),
(2, 'Media', '#ff9500', 2),
(3, 'Alta', '#fe011b', 3)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- Categorías Base de Inventario
INSERT INTO `inventory_categories` (`id`, `name`) VALUES
(1, 'Modem / ONU'),
(2, 'Router / Switch'),
(3, 'Herramientas'),
(4, 'Materiales y Fibra'),
(5, 'EPP y Seguridad')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- Usuario Administrador por Defecto
-- Credenciales de acceso:
-- PIN de Acceso (8 dígitos): 12345678
-- Email: admin@turbosaas.com
-- Contraseña: admin123
INSERT INTO `users` (`id`, `name`, `username`, `email`, `password`, `role`, `pin`) VALUES
(1, 'Administrador General', 'admin', 'admin@turbosaas.com', '$2y$10$QPiIkQcMaZr/8H0GcxpC8.P2Aee9rgfQGwD4.5calKsZ5MNVBz7de', 'admin', '12345678')
ON DUPLICATE KEY UPDATE `email` = VALUES(`email`), `pin` = VALUES(`pin`);

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-23  1:13:11
