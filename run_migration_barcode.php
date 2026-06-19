<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require_once 'config/db.php';

try {
    $pdo->exec("ALTER TABLE users ADD COLUMN barcode VARCHAR(100) UNIQUE DEFAULT NULL;");
    echo "Column 'barcode' added.\n";
} catch (PDOException $e) {
    echo "Error adding 'barcode': " . $e->getMessage() . "\n";
}

try {
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS attendance_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        type ENUM('entrada', 'salida') NOT NULL,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );");
    echo "Table 'attendance_logs' created.\n";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage() . "\n";
}
