<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require 'config/db.php';
$c = $pdo->query('SHOW COLUMNS FROM clientes')->fetchAll(PDO::FETCH_ASSOC);
$s = $pdo->query('SHOW COLUMNS FROM servicios')->fetchAll(PDO::FETCH_ASSOC);
echo "CLIENTES:\n"; print_r($c);
echo "\nSERVICIOS:\n"; print_r($s);
