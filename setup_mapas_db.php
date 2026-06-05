<?php
$host = 'localhost';
$dbname = 'turbosaas_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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

    echo "Tablas creadas exitosamente.\n";
} catch (PDOException $e) {
    echo "Error creando tablas: " . $e->getMessage() . "\n";
}
?>
