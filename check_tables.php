<?php
require 'config/db.php';
$stmt = $pdo->query("SHOW TABLES LIKE 'inventory_product_photos'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
