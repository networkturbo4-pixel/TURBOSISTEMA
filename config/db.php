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

// Generar CSRF Token global
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Helper para verificar si está logueado
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . BASE_URL . "/login.php");
        exit;
    }
}

// ── Seguridad Global: CSRF + Rate Limiting para peticiones AJAX ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['REQUEST_URI'], '/ajax/') !== false) {
    // 1. Validación CSRF
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    // Bypass CSRF token ONLY for the public login endpoint if necessary, or enforce globally.
    // For now, enforce globally. If no token, reject.
    $action = $_POST['action'] ?? '';
    if (!in_array($action, ['public_login', 'create_public_ticket']) && !hash_equals($_SESSION['csrf_token'], $token)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'CSRF Token inválido o expirado. Recarga la página.']);
        exit;
    }

    // 2. API Rate Limiting (100 req / min) por IP
    $ip = $_SERVER['REMOTE_ADDR'];
    $minute = date('Y-m-d H:i');
    $limit_key = "rate_limit_{$ip}_{$minute}";
    
    if (!isset($_SESSION[$limit_key])) {
        // Limpiar keys viejos
        foreach ($_SESSION as $k => $v) {
            if (strpos($k, 'rate_limit_') === 0 && $k !== $limit_key) unset($_SESSION[$k]);
        }
        $_SESSION[$limit_key] = 1;
    } else {
        $_SESSION[$limit_key]++;
    }

    if ($_SESSION[$limit_key] > 100) {
        header('Content-Type: application/json');
        http_response_code(429);
        echo json_encode(['success' => false, 'message' => 'Demasiadas peticiones. Por favor, espera un minuto.']);
        exit;
    }
}

require_once __DIR__ . '/modules.php';
?>
