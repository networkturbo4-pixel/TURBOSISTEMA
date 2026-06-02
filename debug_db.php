<?php
require_once 'config/db.php';
header('Content-Type: text/plain; charset=utf-8');

echo "--- DEBUG DATABASE ---\n\n";

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM inventory_products");
    $total = $stmt->fetchColumn();
    echo "1. Total de productos en la tabla inventory_products: $total\n";

    $stmt = $pdo->query("SELECT COUNT(*) FROM inventory_products WHERE is_deleted = 0 OR is_deleted IS NULL");
    $total_not_deleted = $stmt->fetchColumn();
    echo "2. Total de productos NO eliminados (is_deleted = 0 o NULL): $total_not_deleted\n";

    $stmt = $pdo->query("SELECT COUNT(*) FROM inventory_products WHERE parent_product_id IS NULL OR parent_product_id = 0");
    $total_parents = $stmt->fetchColumn();
    echo "3. Total de productos principales (parent_product_id nulo o 0): $total_parents\n";

    $stmt = $pdo->query("SELECT COUNT(*) FROM inventory_products WHERE (parent_product_id IS NULL OR parent_product_id = 0) AND (is_deleted = 0 OR is_deleted IS NULL)");
    $total_visible = $stmt->fetchColumn();
    echo "4. Total de productos que deberían mostrarse en la pantalla: $total_visible\n";

    if ($total_visible > 0) {
        $stmt = $pdo->query("SELECT id, name, parent_product_id, is_deleted FROM inventory_products WHERE (parent_product_id IS NULL OR parent_product_id = 0) AND (is_deleted = 0 OR is_deleted IS NULL) LIMIT 5");
        $sample = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "\n5. Muestra de productos que DEBERÍAN salir (hasta 5):\n";
        print_r($sample);
    }

    echo "\n\n--- FIN DEL DEBUG ---";
} catch (Exception $e) {
    echo "ERROR SQL: " . $e->getMessage();
}
