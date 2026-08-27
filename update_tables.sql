-- ========================================================
-- SCRIPT DE ACTUALIZACIÓN / MIGRACIÓN DE TABLAS
-- TURBOSAAS - Ejecutar en phpMyAdmin para actualizar sin perder datos
-- ========================================================

-- 1. Tabla de compras/historial de costos de inventario
CREATE TABLE IF NOT EXISTS `inventory_purchases` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Tablas de Fotos y Entradas de Inventario
CREATE TABLE IF NOT EXISTS `inventory_entries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo_ingreso` varchar(50) DEFAULT NULL,
  `tipo_ingreso` enum('compra','devolucion','ajuste','traslado') DEFAULT 'compra',
  `proveedor` varchar(255) DEFAULT NULL,
  `guia_remision` varchar(100) DEFAULT NULL,
  `factura_boleta` varchar(100) DEFAULT NULL,
  `fecha_ingreso` datetime DEFAULT current_timestamp(),
  `usuario_id` int(11) NOT NULL,
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `inventory_entry_photos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entry_id` int(11) NOT NULL,
  `photo_path` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `entry_id` (`entry_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `inventory_sku_photos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sku_id` int(11) NOT NULL,
  `photo_path` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `sku_id` (`sku_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `inventory_product_photos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `photo_path` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. Tablas de Activos Vehiculares
CREATE TABLE IF NOT EXISTS `activos_vehiculos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) NOT NULL,
  `tipo_vehiculo` enum('moto','auto','camioneta','furgoneta','otro') DEFAULT 'moto',
  `placa` varchar(20) NOT NULL,
  `marca` varchar(100) DEFAULT NULL,
  `modelo` varchar(100) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `anio` int(11) DEFAULT NULL,
  `vin_chasis` varchar(100) DEFAULT NULL,
  `motor` varchar(100) DEFAULT NULL,
  `kilometraje` int(11) DEFAULT 0,
  `estado_operativo` enum('operativo','mantenimiento','inoperativo','baja') DEFAULT 'operativo',
  `asignado_a` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  UNIQUE KEY `placa` (`placa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `activos_documentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vehiculo_id` int(11) NOT NULL,
  `tipo_documento` varchar(100) NOT NULL,
  `numero_documento` varchar(100) DEFAULT NULL,
  `fecha_emision` date DEFAULT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `entidad_emisora` varchar(150) DEFAULT NULL,
  `archivo_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `vehiculo_id` (`vehiculo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `activos_historial` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vehiculo_id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `tipo_evento` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `kilometraje_registro` int(11) DEFAULT NULL,
  `costo` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `vehiculo_id` (`vehiculo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `activos_imagenes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vehiculo_id` int(11) NOT NULL,
  `imagen_path` varchar(255) NOT NULL,
  `tipo` varchar(50) DEFAULT 'general',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `vehiculo_id` (`vehiculo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4. Asegurar campos en Productos e Inventario
ALTER TABLE `inventory_products` ADD COLUMN IF NOT EXISTS `costo` decimal(10,2) DEFAULT 0.00;
ALTER TABLE `inventory_skus` ADD COLUMN IF NOT EXISTS `costo_unitario` decimal(10,2) DEFAULT 0.00;
ALTER TABLE `inventory_stock_log` ADD COLUMN IF NOT EXISTS `costo_unitario` decimal(10,2) DEFAULT 0.00;
ALTER TABLE `inventory_stock_log` ADD COLUMN IF NOT EXISTS `motivo` varchar(255) DEFAULT NULL;

-- 5. Asegurar campos en Usuarios y Seguridad
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `pin` varchar(20) DEFAULT NULL;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `barcode` varchar(100) DEFAULT NULL;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `whatsapp` varchar(50) DEFAULT NULL;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `biometric_id` int(11) DEFAULT NULL;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `profile_picture` varchar(255) DEFAULT NULL;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `cover_picture` varchar(255) DEFAULT NULL;

-- 6. Tabla de Intentos de Login
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `identifier` varchar(100) NOT NULL,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ip_identifier_time` (`ip_address`,`identifier`,`attempt_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
