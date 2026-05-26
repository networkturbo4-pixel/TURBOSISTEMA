<?php
require_once 'config/db.php';
$sql = file_get_contents('update_servicios_maps.sql');
try {
    $pdo->exec($sql);
    echo "Migration successful.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
