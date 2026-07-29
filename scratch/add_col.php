<?php
require_once __DIR__ . '/../config/db.php';
try {
    $msg = [];
    
    // Check cliente_nombre_manual
    $cols = $pdo->query("SHOW COLUMNS FROM tickets LIKE 'cliente_nombre_manual'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE tickets ADD COLUMN cliente_nombre_manual VARCHAR(255) NULL AFTER cliente_id");
        $msg[] = "Columna 'cliente_nombre_manual' creada exitosamente.";
    } else {
        $msg[] = "La columna 'cliente_nombre_manual' ya existe.";
    }

    // Check gdrive_folder_id
    $cols2 = $pdo->query("SHOW COLUMNS FROM tickets LIKE 'gdrive_folder_id'")->fetchAll();
    if (empty($cols2)) {
        $pdo->exec("ALTER TABLE tickets ADD COLUMN gdrive_folder_id VARCHAR(255) NULL");
        $msg[] = "Columna 'gdrive_folder_id' creada exitosamente.";
    } else {
        $msg[] = "La columna 'gdrive_folder_id' ya existe.";
    }
    
    echo "<h1>Actualizacion de Base de Datos</h1><br>";
    echo implode("<br>", $msg);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
