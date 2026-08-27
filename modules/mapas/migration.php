<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require_once '../../config/db.php'; // Usa la misma conexión que api.php

try {
    // 1. Añadir columnas técnicas a mapas_elementos si no existen
    $pdo->exec("
        ALTER TABLE mapas_elementos 
        ADD COLUMN IF NOT EXISTS capacidad_puertos INT DEFAULT 0,
        ADD COLUMN IF NOT EXISTS potencia_dbm VARCHAR(50) DEFAULT '',
        ADD COLUMN IF NOT EXISTS cable_origen VARCHAR(100) DEFAULT '',
        ADD COLUMN IF NOT EXISTS splitter_tipo VARCHAR(50) DEFAULT '';
    ");
    echo "Columnas en mapas_elementos añadidas.\n";

    // 2. Crear tabla mapas_puertos
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS mapas_puertos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            elemento_id INT NOT NULL,
            numero_puerto INT NOT NULL,
            estado VARCHAR(20) DEFAULT 'Disponible',
            cliente_nombre VARCHAR(255) DEFAULT '',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (elemento_id) REFERENCES mapas_elementos(id) ON DELETE CASCADE,
            UNIQUE KEY unique_puerto (elemento_id, numero_puerto)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "Tabla mapas_puertos creada.\n";

    // 3. Crear tabla mapas_puertos_historial
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS mapas_puertos_historial (
            id INT AUTO_INCREMENT PRIMARY KEY,
            puerto_id INT NOT NULL,
            accion VARCHAR(50),
            cliente_nombre VARCHAR(255),
            fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (puerto_id) REFERENCES mapas_puertos(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "Tabla mapas_puertos_historial creada.\n";

    echo "Migración completada con éxito.\n";

} catch (PDOException $e) {
    echo "Error en migración: " . $e->getMessage() . "\n";
}
?>
