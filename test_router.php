<?php
// Test directo de la API de router
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Test de Router API</h2>";

// 1. Test: ¿Puede cargar db.php?
echo "<h3>1. Cargando config/db.php...</h3>";
try {
    require_once __DIR__ . '/config/db.php';
    echo "<p style='color:green'>✅ db.php cargado OK</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
    die();
}

// 2. Test: ¿Existe RouterManager?
echo "<h3>2. Cargando includes/RouterManager.php...</h3>";
try {
    require_once __DIR__ . '/includes/RouterManager.php';
    echo "<p style='color:green'>✅ RouterManager.php cargado OK</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
    die();
}

// 3. Test: Sesión del cliente
echo "<h3>3. Sesión del cliente...</h3>";
echo "<pre>public_cliente_id = " . ($_SESSION['public_cliente_id'] ?? 'NO EXISTE') . "</pre>";

// 4. Test: ¿Existen las columnas de router?
echo "<h3>4. Columnas de router en tabla clientes...</h3>";
try {
    $checkStmt = $pdo->query("SHOW COLUMNS FROM clientes LIKE 'router_os'");
    $rows = $checkStmt->fetchAll();
    if (count($rows) > 0) {
        echo "<p style='color:green'>✅ Columna router_os existe</p>";
    } else {
        echo "<p style='color:orange'>⚠️ Columna router_os NO existe</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
}

// 5. Test: MockRouter funciona?
echo "<h3>5. Test MockRouter...</h3>";
try {
    $router = RouterManager::getRouter('mock');
    $router->connect('', '', '', '');
    $wifi = $router->getWifiSettings('');
    $devices = $router->getConnectedDevices('');
    echo "<p style='color:green'>✅ MockRouter funciona</p>";
    echo "<pre>WiFi: " . json_encode($wifi, JSON_PRETTY_PRINT) . "</pre>";
    echo "<pre>Devices: " . json_encode($devices, JSON_PRETTY_PRINT) . "</pre>";
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
}

// 6. Test: Simular llamada completa a la API
echo "<h3>6. Simulando llamada API get_devices...</h3>";
if (isset($_SESSION['public_cliente_id'])) {
    $cid = $_SESSION['public_cliente_id'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
        $stmt->execute([$cid]);
        $cl = $stmt->fetch();
        echo "<pre>Cliente encontrado: " . ($cl ? 'SÍ - ' . $cl['nombre_completo'] : 'NO') . "</pre>";
    } catch (Exception $e) {
        echo "<p style='color:red'>❌ Error consultando cliente: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color:orange'>⚠️ No hay sesión de cliente activa. Primero inicia sesión en el portal.</p>";
}

// 7. Test: Llamada HTTP real a la API
echo "<h3>7. URL de la API para probar manualmente:</h3>";
$baseUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
echo "<p><a href='" . $baseUrl . "/TURBOSAAS/ajax/router_api.php?action=get_devices' target='_blank'>Abrir API get_devices</a></p>";
echo "<p><a href='" . $baseUrl . "/TURBOSAAS/ajax/router_api.php?action=get_wifi' target='_blank'>Abrir API get_wifi</a></p>";
