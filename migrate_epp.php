<?php
require 'c:/xampp/htdocs/TURBOSAAS/config/db.php';
try {
    $pdo->exec('ALTER TABLE inventory_skus ADD COLUMN is_epp TINYINT(1) DEFAULT 0');
    echo "Added to inventory_skus\n";
} catch(PDOException $e) { echo "Error skus: " . $e->getMessage() . "\n"; }

try {
    $pdo->exec('ALTER TABLE inventory_user_stock ADD COLUMN is_epp TINYINT(1) DEFAULT 0');
    echo "Added to inventory_user_stock\n";
} catch(PDOException $e) { echo "Error user_stock: " . $e->getMessage() . "\n"; }
?>
