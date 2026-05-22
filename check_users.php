<?php
require 'c:/xampp/htdocs/TURBOSAAS/config/db.php';
try {
    $pdo->exec('ALTER TABLE users ADD COLUMN username VARCHAR(100) NULL AFTER name');
    echo "Added username to users\n";
} catch(PDOException $e) { echo "Error username: " . $e->getMessage() . "\n"; }

try {
    $pdo->exec('ALTER TABLE users ADD COLUMN cover_picture VARCHAR(255) NULL');
    echo "Added cover_picture to users\n";
} catch(PDOException $e) { echo "Error cover: " . $e->getMessage() . "\n"; }
?>
