<?php
session_start();

if ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1') {
    define('BASE_URL', '/TURBOSAAS');
    $host = 'localhost';
    $dbname = 'turbosaas_db';
    $user = 'root';
    $pass = '';
} else {
    define('BASE_URL', '');
    // En producción, carga las credenciales desde env.php
    require_once __DIR__ . '/env.php';
    $host = defined('DB_HOST') ? DB_HOST : 'localhost';
    $dbname = defined('DB_NAME') ? DB_NAME : 'tu_base_de_datos_cpanel';
    $user = defined('DB_USER') ? DB_USER : 'tu_usuario_cpanel';
    $pass = defined('DB_PASS') ? DB_PASS : 'tu_contrasena_cpanel';
}

if (!defined('JSON_PE_TOKEN') && file_exists(__DIR__ . '/env.php')) {
    require_once __DIR__ . '/env.php';
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // Zona horaria: Perú (UTC-5)
    date_default_timezone_set('America/Lima');
    $pdo->exec("SET time_zone = '-05:00'");
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Helper para verificar si está logueado
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . BASE_URL . "/login.php");
        exit;
    }
}

require_once __DIR__ . '/modules.php';
?>
