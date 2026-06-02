<?php
require_once 'config/db.php';
header('Content-Type: text/html; charset=utf-8');

echo "<h2>Actualización de Base de Datos</h2>";
echo "<ul>";

try {
    // 1. Añadir is_deleted a inventory_products
    try {
        $pdo->exec("ALTER TABLE inventory_products ADD COLUMN is_deleted TINYINT(1) DEFAULT 0");
        echo "<li>✅ Columna 'is_deleted' añadida a inventory_products.</li>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "<li>✅ Columna 'is_deleted' ya existía en inventory_products.</li>";
        } else {
            echo "<li>❌ Error añadiendo 'is_deleted' a inventory_products: " . htmlspecialchars($e->getMessage()) . "</li>";
        }
    }

    // 2. Añadir is_deleted a inventory_skus
    try {
        $pdo->exec("ALTER TABLE inventory_skus ADD COLUMN is_deleted TINYINT(1) DEFAULT 0");
        echo "<li>✅ Columna 'is_deleted' añadida a inventory_skus.</li>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "<li>✅ Columna 'is_deleted' ya existía en inventory_skus.</li>";
        } else {
            echo "<li>❌ Error añadiendo 'is_deleted' a inventory_skus: " . htmlspecialchars($e->getMessage()) . "</li>";
        }
    }

    // 3. Actualizar nulos a 0
    $pdo->exec("UPDATE inventory_products SET is_deleted = 0 WHERE is_deleted IS NULL");
    $pdo->exec("UPDATE inventory_skus SET is_deleted = 0 WHERE is_deleted IS NULL");
    echo "<li>✅ Valores nulos de is_deleted convertidos a 0.</li>";

    // 4. Modificar el ENUM status
    $pdo->exec("ALTER TABLE inventory_skus MODIFY COLUMN status ENUM('disponible','instalado','malogrado','reparado','en_transito','observacion') DEFAULT 'disponible'");
    echo "<li>✅ Columna 'status' actualizada para soportar 'observacion'.</li>";

    // 5. Modificar el ENUM historia
    $pdo->exec("ALTER TABLE inventory_skus MODIFY COLUMN historia ENUM('ninguno','devuelto','malogrado','antiguo','en_transito','observacion') DEFAULT 'ninguno'");
    echo "<li>✅ Columna 'historia' actualizada para soportar 'observacion'.</li>";

    echo "</ul><br><br><h3 style='color:green;'>¡TODO LISTO! Ya puedes volver al sistema.</h3>";

} catch (Exception $e) {
    echo "</ul><br><br><h3 style='color:red;'>Ocurrió un error general: " . htmlspecialchars($e->getMessage()) . "</h3>";
}
