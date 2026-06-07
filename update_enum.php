<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require 'config/db.php';
$sql = "ALTER TABLE inventory_skus MODIFY COLUMN historia ENUM('ninguno','devuelto','malogrado','antiguo','en_transito','observacion') DEFAULT 'ninguno'";
$pdo->exec($sql);
echo "Historia enum updated successfully.\n";
