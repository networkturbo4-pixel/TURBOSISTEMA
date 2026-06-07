<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require 'config/db.php';
$stmt = $pdo->query('SHOW COLUMNS FROM inventory_skus WHERE Field = "historia"');
print_r($stmt->fetch(PDO::FETCH_ASSOC));
