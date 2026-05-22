<?php
require_once 'config/db.php';

echo "=== Migración: variant_attributes ===\n";

try {
    // 1. Add variant_attributes column
    $stmt = $pdo->query("SHOW COLUMNS FROM inventory_products LIKE 'variant_attributes'");
    if ($stmt->rowCount() > 0) {
        echo "Columna variant_attributes ya existe.\n";
    } else {
        $pdo->exec("ALTER TABLE inventory_products ADD COLUMN variant_attributes LONGTEXT NULL AFTER variant_size");
        echo "Columna variant_attributes agregada.\n";
    }

    // 2. Migrate existing variant_brand/variant_size data to variant_attributes JSON
    $stmt = $pdo->query("SELECT id, variant_brand, variant_size FROM inventory_products WHERE parent_product_id IS NOT NULL AND (variant_brand IS NOT NULL OR variant_size IS NOT NULL)");
    $rows = $stmt->fetchAll();
    $update = $pdo->prepare("UPDATE inventory_products SET variant_attributes = ? WHERE id = ?");
    $migrated = 0;
    foreach ($rows as $row) {
        $attrs = [];
        if (!empty($row['variant_brand'])) $attrs['Marca'] = $row['variant_brand'];
        if (!empty($row['variant_size'])) $attrs['Talla'] = $row['variant_size'];
        if (!empty($attrs)) {
            $update->execute([json_encode($attrs, JSON_UNESCAPED_UNICODE), $row['id']]);
            $migrated++;
        }
    }
    echo "Registros migrados: $migrated\n";

    // 3. Update parent products: set custom_columns with Marca/Talla if they have children with those
    $parents = $pdo->query("SELECT DISTINCT parent_product_id FROM inventory_products WHERE parent_product_id IS NOT NULL");
    $updateParent = $pdo->prepare("UPDATE inventory_products SET custom_columns = ? WHERE id = ? AND (custom_columns IS NULL OR custom_columns = '[]')");
    foreach ($parents->fetchAll() as $p) {
        $cols = [['name' => 'Marca', 'type' => 'text'], ['name' => 'Talla', 'type' => 'text']];
        $updateParent->execute([json_encode($cols), $p['parent_product_id']]);
    }
    echo "Padres actualizados con columnas por defecto.\n";

    echo "=== Migración completada ===\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
