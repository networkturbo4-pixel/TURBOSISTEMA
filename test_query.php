<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require_once 'config/db.php';
try {
    $sql = "CREATE INDEX idx_serie_mac ON actas_equipos (serie_mac)";
    $pdo->exec($sql);
    echo "Index created successfully.\n";
    echo "Query executed successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
