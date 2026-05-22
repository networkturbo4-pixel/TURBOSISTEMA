<?php
/**
 * MIGRACIÓN DE BASE DE DATOS
 * Ejecutar desde navegador: https://tu-dominio.com/run_migration.php
 * Eliminar después de ejecutar.
 */
session_start();

// Detectar entorno
$isLocal = (php_sapi_name() === 'cli') || 
           (isset($_SERVER['HTTP_HOST']) && in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1']));

if ($isLocal) {
    $host = 'localhost';
    $dbname = 'turbosaas_db';
    $user = 'root';
    $pass = '';
} else {
    // Producción: cargar credenciales
    require_once __DIR__ . '/config/env.php';
    $host = defined('DB_HOST') ? DB_HOST : 'localhost';
    $dbname = defined('DB_NAME') ? DB_NAME : '';
    $user = defined('DB_USER') ? DB_USER : '';
    $pass = defined('DB_PASS') ? DB_PASS : '';
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("<h2 style='color:red;'>Error de conexión: " . $e->getMessage() . "</h2>");
}

// Desactivar FK checks para evitar problemas de charset
$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

$migrations = [
    // 0. users
    "ALTER TABLE `users` ADD COLUMN `username` VARCHAR(100) DEFAULT NULL",
    "ALTER TABLE `users` ADD COLUMN `cover_picture` VARCHAR(255) DEFAULT NULL",

    // 1. inventory_products
    "ALTER TABLE `inventory_products` ADD COLUMN `requires_photos` TINYINT(1) DEFAULT 0",
    "ALTER TABLE `inventory_products` ADD COLUMN `product_type` VARCHAR(50) DEFAULT 'normal'",
    "ALTER TABLE `inventory_products` ADD COLUMN `product_image` VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE `inventory_products` ADD COLUMN `parent_product_id` INT(11) DEFAULT NULL",
    "ALTER TABLE `inventory_products` ADD COLUMN `variant_attributes` LONGTEXT DEFAULT NULL",
    "ALTER TABLE `inventory_products` ADD INDEX `idx_parent_product_id` (`parent_product_id`)",

    // 2. inventory_skus
    "ALTER TABLE `inventory_skus` ADD COLUMN `is_epp` TINYINT(1) DEFAULT 0",

    // 3. inventory_user_stock
    "ALTER TABLE `inventory_user_stock` ADD COLUMN `is_epp` TINYINT(1) DEFAULT 0",


    // 4. inventory_sku_photos (sin FK para evitar errno 150)
    "CREATE TABLE IF NOT EXISTS `inventory_sku_photos` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `sku_id` INT(11) NOT NULL,
        `ruta_archivo` VARCHAR(255) NOT NULL,
        `uploaded_by` INT(11) DEFAULT NULL,
        `nota` TEXT DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `sku_id` (`sku_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

    // 5. inventory_product_photos (sin FK para evitar errno 150)
    "CREATE TABLE IF NOT EXISTS `inventory_product_photos` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `product_id` INT(11) NOT NULL,
        `ruta_archivo` VARCHAR(255) NOT NULL,
        `uploaded_by` INT(11) DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `product_id` (`product_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
];

// Reactivar FK checks
$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

// Output HTML for browser
$isBrowser = php_sapi_name() !== 'cli';
if ($isBrowser) {
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Migración DB</title>';
    echo '<style>body{background:#111;color:#eee;font-family:monospace;padding:30px;max-width:900px;margin:0 auto}';
    echo '.ok{color:#10b981}.skip{color:#f59e0b}.err{color:#ef4444}h1{color:#f97316}pre{background:#1a1a2e;padding:15px;border-radius:10px;overflow-x:auto;border:1px solid #333}</style></head><body>';
    echo '<h1>🔧 Migración de Base de Datos</h1><pre>';
}

$success = 0; $skipped = 0; $errors = 0;

foreach ($migrations as $sql) {
    $short = substr(trim($sql), 0, 85) . '...';
    try {
        $pdo->exec($sql);
        if ($isBrowser) echo "<span class='ok'>[✅ OK]</span> $short\n";
        else echo "[OK] $short\n";
        $success++;
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false || 
            strpos($e->getMessage(), 'Duplicate key') !== false ||
            strpos($e->getMessage(), 'already exists') !== false) {
            if ($isBrowser) echo "<span class='skip'>[⏭ SKIP]</span> Ya existe: $short\n";
            else echo "[SKIP] Ya existe: $short\n";
            $skipped++;
        } else {
            if ($isBrowser) echo "<span class='err'>[❌ ERROR]</span> $short\n  → " . htmlspecialchars($e->getMessage()) . "\n";
            else echo "[ERROR] $short\n  -> " . $e->getMessage() . "\n";
            $errors++;
        }
    }
}

echo "\n══════════════════════════════════\n";
echo "Exitosos: $success | Omitidos: $skipped | Errores: $errors\n";
echo "══════════════════════════════════\n";

// Verificación
echo "\n📋 COLUMNAS inventory_products:\n";
$stmt = $pdo->query("DESCRIBE inventory_products");
while ($row = $stmt->fetch()) echo "  • {$row['Field']} ({$row['Type']})\n";

echo "\n📋 TABLAS inventory_*:\n";
$stmt = $pdo->query("SHOW TABLES LIKE 'inventory_%'");
while ($row = $stmt->fetch(PDO::FETCH_NUM)) echo "  • {$row[0]}\n";

if ($isBrowser) {
    echo '</pre>';
    if ($errors === 0) {
        echo '<h2 style="color:#10b981">✅ Migración completada. ¡Recarga la app!</h2>';
    } else {
        echo '<h2 style="color:#ef4444">⚠️ Hubo errores. Revisa los detalles arriba.</h2>';
    }
    echo '<p style="color:#f59e0b;margin-top:20px">⚠️ <b>IMPORTANTE:</b> Elimina este archivo (run_migration.php) después de ejecutar.</p>';
    echo '</body></html>';
}
