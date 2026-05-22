-- =====================================================
-- MIGRACIÓN DE BASE DE DATOS PARA PRODUCCIÓN
-- Ejecutar en phpMyAdmin o terminal MySQL
-- Fecha: 2026-05-22
-- =====================================================

-- ── 0. Columnas faltantes en users (para perfil) ──
ALTER TABLE `users` 
  ADD COLUMN IF NOT EXISTS `username` VARCHAR(100) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `cover_picture` VARCHAR(255) DEFAULT NULL;

-- ── 1. Columnas faltantes en inventory_products ──
ALTER TABLE `inventory_products` 
  ADD COLUMN IF NOT EXISTS `requires_photos` TINYINT(1) DEFAULT 0 AFTER `unit_type`,
  ADD COLUMN IF NOT EXISTS `product_type` VARCHAR(50) DEFAULT 'normal' AFTER `requires_photos`,
  ADD COLUMN IF NOT EXISTS `product_image` VARCHAR(255) DEFAULT NULL AFTER `product_type`,
  ADD COLUMN IF NOT EXISTS `parent_product_id` INT(11) DEFAULT NULL AFTER `product_image`,
  ADD COLUMN IF NOT EXISTS `variant_attributes` LONGTEXT DEFAULT NULL AFTER `parent_product_id`;

-- ── 2. Columna faltante en inventory_skus ──
ALTER TABLE `inventory_skus` 
  ADD COLUMN IF NOT EXISTS `is_epp` TINYINT(1) DEFAULT 0 AFTER `historia`;

-- ── 3. Columna faltante en inventory_user_stock ──
ALTER TABLE `inventory_user_stock` 
  ADD COLUMN IF NOT EXISTS `is_epp` TINYINT(1) DEFAULT 0 AFTER `quantity`;

-- ── 4. Tabla: inventory_sku_photos (fotos por SKU) ──
CREATE TABLE IF NOT EXISTS `inventory_sku_photos` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `sku_id` INT(11) NOT NULL,
  `ruta_archivo` VARCHAR(255) NOT NULL,
  `uploaded_by` INT(11) DEFAULT NULL,
  `nota` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `sku_id` (`sku_id`),
  CONSTRAINT `inventory_sku_photos_ibfk_1` FOREIGN KEY (`sku_id`) REFERENCES `inventory_skus` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── 5. Tabla: inventory_product_photos (fotos del producto) ──
CREATE TABLE IF NOT EXISTS `inventory_product_photos` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `product_id` INT(11) NOT NULL,
  `ruta_archivo` VARCHAR(255) NOT NULL,
  `uploaded_by` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `inventory_product_photos_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `inventory_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── 6. Índice para parent_product_id ──
-- (Solo si no existe ya)
SET @dbname = DATABASE();
SET @tablename = 'inventory_products';
SET @indexname = 'idx_parent_product_id';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.STATISTICS 
   WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND INDEX_NAME = @indexname) > 0,
  'SELECT 1',
  'ALTER TABLE `inventory_products` ADD INDEX `idx_parent_product_id` (`parent_product_id`)'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- =====================================================
-- VERIFICACIÓN: Ejecuta esto para confirmar que todo está OK
-- =====================================================
-- SELECT COLUMN_NAME FROM information_schema.COLUMNS 
-- WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'inventory_products';
--
-- SHOW TABLES LIKE 'inventory_%';
-- =====================================================
