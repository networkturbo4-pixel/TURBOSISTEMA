<?php
require_once __DIR__ . '/../config/db.php';
try {
    $cols = $pdo->query("SHOW COLUMNS FROM tickets LIKE 'cliente_nombre_manual'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE tickets ADD COLUMN cliente_nombre_manual VARCHAR(255) NULL AFTER cliente_id");
        echo "Column cliente_nombre_manual created successfully.";
    } else {
        echo "Column cliente_nombre_manual already exists.";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
