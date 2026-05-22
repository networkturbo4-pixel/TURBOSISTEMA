<?php
require_once __DIR__ . '/config/db.php';

echo "<div style='font-family: sans-serif; max-width: 800px; margin: 0 auto; padding: 20px;'>";
echo "<h1>🛠️ Actualización de Base de Datos en Producción</h1>";
echo "<ul>";

// Lista de columnas a crear
$migrations = [
    "ALTER TABLE actas ADD COLUMN cliente_rotulado VARCHAR(255) DEFAULT '' AFTER cliente_dni_ruc",
    "ALTER TABLE inventory_skus ADD COLUMN is_epp TINYINT(1) DEFAULT 0",
    "ALTER TABLE inventory_user_stock ADD COLUMN is_epp TINYINT(1) DEFAULT 0",
    "ALTER TABLE inventory_products ADD COLUMN product_type VARCHAR(20) DEFAULT 'normal' AFTER master_sku",
    "ALTER TABLE inventory_products ADD COLUMN parent_product_id INT NULL AFTER product_type",
    "ALTER TABLE inventory_products ADD COLUMN variant_brand VARCHAR(100) NULL AFTER parent_product_id",
    "ALTER TABLE inventory_products ADD COLUMN variant_size VARCHAR(100) NULL AFTER variant_brand",
    "ALTER TABLE inventory_products ADD COLUMN variant_attributes LONGTEXT NULL AFTER variant_size",
];

// 1. Ejecutar las alteraciones de tablas
foreach ($migrations as $sql) {
    try {
        $pdo->exec($sql);
        echo "<li style='color:green'>✅ OK: " . htmlspecialchars($sql) . "</li>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false || strpos($e->getMessage(), 'already exists') !== false) {
            echo "<li style='color:orange'>⚠️ Ya existe: " . htmlspecialchars($sql) . "</li>";
        } else {
            echo "<li style='color:red'>❌ Error: " . $e->getMessage() . "</li>";
        }
    }
}

// 2. Agregar Clave Foránea (si no existe)
try {
    $pdo->exec("ALTER TABLE inventory_products ADD CONSTRAINT fk_parent_product FOREIGN KEY (parent_product_id) REFERENCES inventory_products(id) ON DELETE CASCADE");
    echo "<li style='color:green'>✅ Relación fk_parent_product agregada.</li>";
} catch(Exception $e) {
    echo "<li style='color:orange'>⚠️ Relación fk_parent_product (Probablemente ya existe).</li>";
}

// 3. Migración de Datos (Product Type a Granel)
try {
    $updated = $pdo->exec("UPDATE inventory_products SET product_type = 'granel' WHERE is_bulk = 1 AND (product_type IS NULL OR product_type = 'normal')");
    echo "<li style='color:blue'>ℹ️ Registros actualizados a granel: $updated</li>";
} catch(Exception $e) {}

// 4. Migración de Atributos JSON
try {
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
    echo "<li style='color:blue'>ℹ️ Atributos JSON migrados: $migrated</li>";

    // Actualizar padres
    $parents = $pdo->query("SELECT DISTINCT parent_product_id FROM inventory_products WHERE parent_product_id IS NOT NULL");
    $updateParent = $pdo->prepare("UPDATE inventory_products SET custom_columns = ? WHERE id = ? AND (custom_columns IS NULL OR custom_columns = '[]')");
    $parentsUpdated = 0;
    foreach ($parents->fetchAll() as $p) {
        $cols = [['name' => 'Marca', 'type' => 'text'], ['name' => 'Talla', 'type' => 'text']];
        $updateParent->execute([json_encode($cols), $p['parent_product_id']]);
        $parentsUpdated++;
    }
    echo "<li style='color:blue'>ℹ️ Padres actualizados: $parentsUpdated</li>";
} catch(Exception $e) {
    echo "<li style='color:red'>❌ Error al migrar atributos: " . $e->getMessage() . "</li>";
}

echo "</ul>";
echo "<hr><p style='color:red; font-weight:bold;'>⚠️ ¡IMPORTANTE! Por razones de seguridad, cuando termines de ejecutar este archivo y verifiques que tu sistema funciona bien, debes eliminar el archivo <code>update_bd_produccion.php</code>.</p>";
echo "</div>";
?>
