<?php
require 'config/db.php';
$stmt = $pdo->query("SHOW COLUMNS FROM inventory_products");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
