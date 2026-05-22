<?php
require_once 'config/db.php';

echo "=== Migración: Producto Agrupado ===\n";

try {
    // 1. parent_product_id
    $stmt = $pdo->query("SHOW COLUMNS FROM inventory_products LIKE 'parent_product_id'");
    if ($stmt->rowCount() > 0) {
        echo "Columna parent_product_id ya existe.\n";
    } else {
        $pdo->exec("ALTER TABLE inventory_products ADD COLUMN parent_product_id INT NULL AFTER product_type");
        $pdo->exec("ALTER TABLE inventory_products ADD CONSTRAINT fk_parent_product FOREIGN KEY (parent_product_id) REFERENCES inventory_products(id) ON DELETE CASCADE");
        echo "Columna parent_product_id agregada con FK CASCADE.\n";
    }

    // 2. variant_brand
    $stmt = $pdo->query("SHOW COLUMNS FROM inventory_products LIKE 'variant_brand'");
    if ($stmt->rowCount() > 0) {
        echo "Columna variant_brand ya existe.\n";
    } else {
        $pdo->exec("ALTER TABLE inventory_products ADD COLUMN variant_brand VARCHAR(100) NULL AFTER parent_product_id");
        echo "Columna variant_brand agregada.\n";
    }

    // 3. variant_size
    $stmt = $pdo->query("SHOW COLUMNS FROM inventory_products LIKE 'variant_size'");
    if ($stmt->rowCount() > 0) {
        echo "Columna variant_size ya existe.\n";
    } else {
        $pdo->exec("ALTER TABLE inventory_products ADD COLUMN variant_size VARCHAR(100) NULL AFTER variant_brand");
        echo "Columna variant_size agregada.\n";
    }

    echo "=== Migración completada ===\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
