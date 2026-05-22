<?php
/**
 * Ejecutar migración de BD para agregar columnas y tablas faltantes
 */
$_SERVER['HTTP_HOST'] = 'localhost'; // Force local DB
require_once __DIR__ . '/config/db.php';

$migrations = [
    // 0. users - columnas faltantes para perfil
    "ALTER TABLE `users` ADD COLUMN `username` VARCHAR(100) DEFAULT NULL",
    "ALTER TABLE `users` ADD COLUMN `cover_picture` VARCHAR(255) DEFAULT NULL",

    // 1. inventory_products - columnas faltantes
    "ALTER TABLE `inventory_products` ADD COLUMN `requires_photos` TINYINT(1) DEFAULT 0",
    "ALTER TABLE `inventory_products` ADD COLUMN `product_type` VARCHAR(50) DEFAULT 'normal'",
    "ALTER TABLE `inventory_products` ADD COLUMN `product_image` VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE `inventory_products` ADD COLUMN `parent_product_id` INT(11) DEFAULT NULL",
    "ALTER TABLE `inventory_products` ADD COLUMN `variant_attributes` LONGTEXT DEFAULT NULL",
    "ALTER TABLE `inventory_products` ADD INDEX `idx_parent_product_id` (`parent_product_id`)",

    // 2. inventory_skus - columna faltante
    "ALTER TABLE `inventory_skus` ADD COLUMN `is_epp` TINYINT(1) DEFAULT 0",

    // 3. inventory_user_stock - columna faltante
    "ALTER TABLE `inventory_user_stock` ADD COLUMN `is_epp` TINYINT(1) DEFAULT 0",

    // 4. Tabla inventory_sku_photos
    "CREATE TABLE IF NOT EXISTS `inventory_sku_photos` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `sku_id` INT(11) NOT NULL,
        `ruta_archivo` VARCHAR(255) NOT NULL,
        `uploaded_by` INT(11) DEFAULT NULL,
        `nota` TEXT DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `sku_id` (`sku_id`),
        CONSTRAINT `inventory_sku_photos_ibfk_1` FOREIGN KEY (`sku_id`) REFERENCES `inventory_skus` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

    // 5. Tabla inventory_product_photos
    "CREATE TABLE IF NOT EXISTS `inventory_product_photos` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `product_id` INT(11) NOT NULL,
        `ruta_archivo` VARCHAR(255) NOT NULL,
        `uploaded_by` INT(11) DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `product_id` (`product_id`),
        CONSTRAINT `inventory_product_photos_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `inventory_products` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
];

echo "=== EJECUTANDO MIGRACION ===\n\n";

$success = 0;
$skipped = 0;
$errors = 0;

foreach ($migrations as $sql) {
    // Extract a short description
    $short = substr(trim($sql), 0, 80) . '...';
    try {
        $pdo->exec($sql);
        echo "[OK] $short\n";
        $success++;
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false || 
            strpos($e->getMessage(), 'Duplicate key') !== false ||
            strpos($e->getMessage(), 'already exists') !== false) {
            echo "[SKIP] Ya existe: $short\n";
            $skipped++;
        } else {
            echo "[ERROR] $short\n  -> " . $e->getMessage() . "\n";
            $errors++;
        }
    }
}

echo "\n=== RESULTADO ===\n";
echo "Exitosos: $success | Omitidos (ya existían): $skipped | Errores: $errors\n";

// Verificar columnas actuales
echo "\n=== VERIFICACION: inventory_products ===\n";
$stmt = $pdo->query("DESCRIBE inventory_products");
while ($row = $stmt->fetch()) {
    echo "  - {$row['Field']} ({$row['Type']})\n";
}

echo "\n=== VERIFICACION: inventory_skus ===\n";
$stmt = $pdo->query("DESCRIBE inventory_skus");
while ($row = $stmt->fetch()) {
    echo "  - {$row['Field']} ({$row['Type']})\n";
}

echo "\n=== TABLAS inventory_* ===\n";
$stmt = $pdo->query("SHOW TABLES LIKE 'inventory_%'");
while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
    echo "  - {$row[0]}\n";
}
