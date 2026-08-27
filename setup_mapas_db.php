<?php
require_once __DIR__ . '/config/db.php';

try {
    // Tabla de Proyectos

    // Tabla de Proyectos
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS mapas_proyectos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(255) NOT NULL,
            descripcion TEXT,
            cover_image VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // Tabla de Elementos
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS mapas_elementos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            proyecto_id INT NOT NULL,
            tipo VARCHAR(50) NOT NULL,
            nombre VARCHAR(255),
            descripcion TEXT,
            geojson TEXT NOT NULL,
            color VARCHAR(50) DEFAULT NULL,
            icono VARCHAR(100) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (proyecto_id) REFERENCES mapas_proyectos(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // Asegurar que las columnas técnicas existan (por si la tabla ya fue creada)
    try {
        $pdo->exec("
            ALTER TABLE mapas_elementos 
            ADD COLUMN capacidad_puertos INT DEFAULT 0,
            ADD COLUMN potencia_dbm VARCHAR(50) DEFAULT '',
            ADD COLUMN cable_origen VARCHAR(100) DEFAULT '',
            ADD COLUMN splitter_tipo VARCHAR(50) DEFAULT '';
        ");
    } catch (PDOException $e) {
        // Ignorar error si las columnas ya existen
    }

    // Tabla de Imágenes
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS mapas_imagenes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            elemento_id INT NOT NULL,
            ruta VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (elemento_id) REFERENCES mapas_elementos(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // Tabla de Puertos
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS mapas_puertos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            elemento_id INT NOT NULL,
            numero_puerto INT NOT NULL,
            estado VARCHAR(50) DEFAULT 'Libre',
            cliente_nombre VARCHAR(255) DEFAULT '',
            cliente_direccion TEXT,
            potencia VARCHAR(50) DEFAULT '',
            color_hilo VARCHAR(50) DEFAULT '',
            notas TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (elemento_id) REFERENCES mapas_elementos(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // Tabla de Historial de Puertos
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

    echo "Tablas creadas exitosamente.\n";
} catch (PDOException $e) {
    echo "Error creando tablas: " . $e->getMessage() . "\n";
}
?>
