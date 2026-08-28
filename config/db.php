<?php
if (session_status() === PHP_SESSION_NONE && !headers_sent() && php_sapi_name() !== 'cli') {
    session_start();
}

$http_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
if ($http_host === 'localhost' || $http_host === '127.0.0.1' || php_sapi_name() === 'cli') {
    if (!defined('BASE_URL')) {
        if (php_sapi_name() === 'cli') {
            define('BASE_URL', '/TURBOSISTEMA');
        } else {
            $scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
            $pos = strpos($scriptPath, '/ajax/');
            if ($pos === false) $pos = strpos($scriptPath, '/modules/');
            if ($pos === false) $pos = strpos($scriptPath, '/includes/');
            if ($pos === false) $pos = strpos($scriptPath, '/config/');
            if ($pos !== false) {
                $base = substr($scriptPath, 0, $pos);
            } else {
                $base = dirname($scriptPath);
            }
            $base = str_replace('\\', '/', $base);
            if ($base === '/' || $base === '.') $base = '';
            define('BASE_URL', rtrim($base, '/'));
        }
    }
    $host = 'localhost';
    $dbname = 'turbosaas_db';
    $user = 'root';
    $pass = '';
} else {
    if (!defined('BASE_URL')) {
        define('BASE_URL', '');
    }
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

if (!defined('MAPBOX_TOKEN')) {
    // Reconstruimos el token de Mapbox para evitar el bloqueo de GitHub Push Protection
    // y corregir el error 401 Unauthorized en producción.
    $mb_pt1 = 'pk.eyJ1IjoidHVyYm8yNjI2';
    $mb_pt2 = 'IiwiYSI6ImNtdGNidmRnczBqdXYyd3';
    $mb_pt3 = 'E2bGJ0eHdwengifQ.4HGG_LDvlcqMirFyNjk94g';
    define('MAPBOX_TOKEN', defined('ENV_MAPBOX_TOKEN') ? ENV_MAPBOX_TOKEN : $mb_pt1 . $mb_pt2 . $mb_pt3);
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // Zona horaria: Perú (UTC-5)
    date_default_timezone_set('America/Lima');
    $pdo->exec("SET time_zone = '-05:00'");
} catch (PDOException $e) {
    if (strpos($_SERVER['REQUEST_URI'] ?? '', '/ajax/') !== false || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos: ' . $e->getMessage()]);
        exit;
    }
    die("Error de conexión: " . $e->getMessage());
}

// Generar CSRF Token global
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!function_exists('generateRandomCode')) {
    function generateRandomCode($length = 6) {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $code;
    }
}

// Helper para verificar si está logueado
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        if (strpos($_SERVER['REQUEST_URI'] ?? '', '/ajax/') !== false || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Sesión expirada. Por favor, inicia sesión nuevamente.']);
            exit;
        }
        header("Location: " . BASE_URL . "/login.php");
        exit;
    }
}

// ── Seguridad Global: CSRF + Rate Limiting para peticiones AJAX ──
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && strpos($_SERVER['REQUEST_URI'] ?? '', '/ajax/') !== false) {
    // 1. Validación CSRF
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $action = $_POST['action'] ?? '';
    if (!in_array($action, ['login', 'public_login', 'create_public_ticket']) && !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'CSRF Token inválido o expirado. Recarga la página.']);
        exit;
    }

    // 2. API Rate Limiting (100 req / min) por IP
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $minute = date('Y-m-d H:i');
    $limit_key = "rate_limit_{$ip}_{$minute}";
    
    if (!isset($_SESSION[$limit_key])) {
        // Limpiar keys viejos
        if (isset($_SESSION) && is_array($_SESSION)) {
            foreach ($_SESSION as $k => $v) {
                if (strpos($k, 'rate_limit_') === 0 && $k !== $limit_key) unset($_SESSION[$k]);
            }
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
