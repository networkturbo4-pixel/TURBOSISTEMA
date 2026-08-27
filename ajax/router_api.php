<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/RouterManager.php';

header('Content-Type: application/json');

if (!isset($_SESSION['public_cliente_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$cliente_id = $_SESSION['public_cliente_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Obtener datos del router del cliente - con fallback robusto
$cliente = null;

// Primero verificar si las columnas de router existen
$columnsExist = true;
try {
    $checkStmt = $pdo->query("SHOW COLUMNS FROM clientes LIKE 'router_os'");
    if ($checkStmt->rowCount() === 0) {
        $columnsExist = false;
    }
} catch (Exception $e) {
    $columnsExist = false;
}

if (!$columnsExist) {
    // Crear las columnas que faltan
    $cols = [
        "router_os VARCHAR(50) DEFAULT 'mock'",
        "router_ip VARCHAR(100) DEFAULT NULL",
        "router_port VARCHAR(20) DEFAULT NULL",
        "router_user VARCHAR(100) DEFAULT NULL",
        "router_pass VARCHAR(255) DEFAULT NULL",
        "router_mac_or_id VARCHAR(100) DEFAULT NULL"
    ];
    foreach ($cols as $col) {
        try {
            $pdo->exec("ALTER TABLE clientes ADD COLUMN $col");
        } catch (Exception $ignore) {}
    }
}

// Ahora intentar obtener datos del cliente
try {
    $stmt = $pdo->prepare("SELECT router_os, router_ip, router_port, router_user, router_pass, router_mac_or_id FROM clientes WHERE id = ?");
    $stmt->execute([$cliente_id]);
    $cliente = $stmt->fetch();
} catch (Exception $e) {
    // Último recurso: verificar que el cliente existe y usar mock
    try {
        $stmtCheck = $pdo->prepare("SELECT id FROM clientes WHERE id = ?");
        $stmtCheck->execute([$cliente_id]);
        if ($stmtCheck->fetch()) {
            $cliente = [
                'router_os' => 'mock',
                'router_ip' => '',
                'router_port' => '',
                'router_user' => '',
                'router_pass' => '',
                'router_mac_or_id' => ''
            ];
        }
    } catch (Exception $ignore) {}
}

if (!$cliente) {
    echo json_encode(['success' => false, 'message' => 'Cliente no encontrado']);
    exit;
}

// Si no tiene configurado un router OS, usamos "bdcom" por defecto (para BDCOM GP1704/GP1705)
$routerOs = !empty($cliente['router_os']) ? $cliente['router_os'] : 'bdcom';

// Obtener instancia del router
$router = RouterManager::getRouter($routerOs);

// Intentar conectar
$connected = $router->connect(
    $cliente['router_ip'] ?? '192.168.123.1',
    $cliente['router_port'] ?? '',
    $cliente['router_user'] ?? 'user',
    $cliente['router_pass'] ?? '123456'
);

if (!$connected && !in_array($routerOs, ['mock', 'local', 'bdcom'])) {
    echo json_encode(['success' => false, 'message' => 'No se pudo conectar al equipo del cliente']);
    exit;
}

try {
    switch ($action) {
        case 'get_wifi':
            $settings = $router->getWifiSettings($cliente['router_mac_or_id'] ?? '');
            echo json_encode(['success' => true, 'data' => $settings]);
            break;

        case 'set_wifi':
            $ssid = $_POST['ssid'] ?? '';
            $password = $_POST['password'] ?? '';

            if (empty($ssid) || empty($password)) {
                echo json_encode(['success' => false, 'message' => 'El nombre y contraseña no pueden estar vacíos']);
                exit;
            }

            $result = $router->setWifiSettings($cliente['router_mac_or_id'] ?? '', $ssid, $password);

            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Configuración Wi-Fi actualizada correctamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al actualizar configuración en el equipo']);
            }
            break;

        case 'get_devices':
            $devices = $router->getConnectedDevices($cliente['router_mac_or_id'] ?? '');
            echo json_encode(['success' => true, 'data' => $devices]);
            break;

        case 'rename_device':
            $mac = $_POST['mac'] ?? '';
            $newName = $_POST['name'] ?? '';

            if (empty($mac) || empty($newName)) {
                echo json_encode(['success' => false, 'message' => 'Faltan datos']);
                exit;
            }

            $result = $router->renameDevice($mac, $newName);

            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Dispositivo renombrado correctamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al renombrar el dispositivo']);
            }
            break;

        case 'block_device':
            $mac = $_POST['mac'] ?? '';
            $block = filter_var($_POST['block'] ?? 'true', FILTER_VALIDATE_BOOLEAN);

            if (empty($mac)) {
                echo json_encode(['success' => false, 'message' => 'Falta la dirección MAC del dispositivo']);
                exit;
            }

            $result = $router->blockDevice($mac, $block);

            if ($result) {
                $statusMsg = $block ? 'bloqueado' : 'desbloqueado';
                echo json_encode(['success' => true, 'message' => "Dispositivo $statusMsg correctamente"]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al modificar el estado del dispositivo']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Ocurrió un error: ' . $e->getMessage()]);
}
