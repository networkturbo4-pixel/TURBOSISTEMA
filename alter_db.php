<?php
require 'config/db.php';

$migrations = [
    "ALTER TABLE actas ADD COLUMN cliente_rotulado VARCHAR(255) DEFAULT '' AFTER cliente_dni_ruc",
];

echo "<h2>Migraciones de Base de Datos</h2><ul>";
foreach ($migrations as $sql) {
    try {
        $pdo->exec($sql);
        echo "<li style='color:green'>✅ OK: " . htmlspecialchars($sql) . "</li>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "<li style='color:orange'>⚠️ Ya existe: " . htmlspecialchars($sql) . "</li>";
        } else {
            echo "<li style='color:red'>❌ Error: " . $e->getMessage() . "</li>";
        }
    }
}
echo "</ul><p><b>Listo. Ahora puedes volver a crear actas.</b></p>";
