<?php
session_start();

$host = 'localhost';
$dbname = 'turbosaas_db';
$user = 'root';
$pass = '';

if ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1') {
    define('BASE_URL', '/TURBOSAAS');
} else {
    define('BASE_URL', '');
}
require_once __DIR__ . '/env.php';

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
