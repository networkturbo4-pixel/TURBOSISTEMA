<?php
require_once 'config/db.php';

echo "<h1>Actualizando tabla USERS...</h1>";

try {
    // Verificar si existe la columna username
    $checkUsername = $pdo->query("SHOW COLUMNS FROM users LIKE 'username'");
    if ($checkUsername->rowCount() == 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN username VARCHAR(50) DEFAULT NULL AFTER id");
        echo "<p>✅ Columna 'username' agregada a la tabla 'users'.</p>";
    } else {
        echo "<p>✅ Columna 'username' ya existe.</p>";
    }

    // Verificar si existe la columna cover_picture
    $checkCover = $pdo->query("SHOW COLUMNS FROM users LIKE 'cover_picture'");
    if ($checkCover->rowCount() == 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN cover_picture VARCHAR(255) DEFAULT NULL AFTER profile_picture");
        echo "<p>✅ Columna 'cover_picture' agregada a la tabla 'users'.</p>";
    } else {
        echo "<p>✅ Columna 'cover_picture' ya existe.</p>";
    }

    echo "<h3>🎉 La tabla users ha sido actualizada exitosamente.</h3>";
    echo "<p><a href='modules/perfil/'>Volver a mi perfil</a></p>";
} catch (PDOException $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>
