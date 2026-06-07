<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require_once 'config/db.php';

try {
    $pdo->exec("ALTER TABLE clientes ADD COLUMN router_os VARCHAR(50) DEFAULT 'mock'");
    $pdo->exec("ALTER TABLE clientes ADD COLUMN router_ip VARCHAR(100) DEFAULT NULL");
    $pdo->exec("ALTER TABLE clientes ADD COLUMN router_port VARCHAR(20) DEFAULT NULL");
    $pdo->exec("ALTER TABLE clientes ADD COLUMN router_user VARCHAR(100) DEFAULT NULL");
    $pdo->exec("ALTER TABLE clientes ADD COLUMN router_pass VARCHAR(255) DEFAULT NULL");
    $pdo->exec("ALTER TABLE clientes ADD COLUMN router_mac_or_id VARCHAR(100) DEFAULT NULL");
    echo "Columnas de router añadidas a clientes exitosamente.\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Las columnas ya existen.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
