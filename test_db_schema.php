<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require_once 'c:/xampp/htdocs/TURBOSAAS/config/db.php';
try {
    $stmt = $pdo->query("DESCRIBE inventory_products");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
