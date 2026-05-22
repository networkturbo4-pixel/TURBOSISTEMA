<?php
require_once 'config/db.php';

echo "=== Migración: Agregar columna product_type a inventory_products ===\n";

try {
    // Check if column already exists
    $stmt = $pdo->query("SHOW COLUMNS FROM inventory_products LIKE 'product_type'");
    if ($stmt->rowCount() > 0) {
        echo "La columna product_type ya existe. Saltando...\n";
    } else {
        $pdo->exec("ALTER TABLE inventory_products ADD COLUMN product_type VARCHAR(20) DEFAULT 'normal' AFTER master_sku");
        echo "Columna product_type agregada exitosamente.\n";
    }

    // Migrate existing data: is_bulk=1 → product_type='granel'
    $updated = $pdo->exec("UPDATE inventory_products SET product_type = 'granel' WHERE is_bulk = 1 AND (product_type IS NULL OR product_type = 'normal')");
    echo "Registros migrados (granel): $updated\n";

    echo "=== Migración completada ===\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
