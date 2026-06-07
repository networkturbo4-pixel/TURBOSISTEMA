<?php
/**
 * Test completo de BdcomRouter
 */
require_once __DIR__ . '/includes/RouterManager.php';

echo "<h2>Test BdcomRouter - Integración Completa</h2>";
echo "<style>body{font-family:monospace;background:#1a1a2e;color:#e0e0e0;padding:20px;} 
pre{background:#16213e;padding:15px;border-radius:8px;border:1px solid #0f3460;white-space:pre-wrap;} 
h3{color:#e94560;} .ok{color:#4ecca3;} .err{color:#e94560;}</style>";

$router = RouterManager::getRouter('bdcom');
$connected = $router->connect('192.168.123.1', '', 'user', '123456');

echo "<h3>1. Conexión</h3>";
echo "<p>" . ($connected ? "<span class='ok'>✅ Conectado al BDCOM</span>" : "<span class='err'>❌ No se pudo conectar (¿sesión activa en el navegador?)</span>") . "</p>";

echo "<h3>2. WiFi Settings</h3>";
$wifi = $router->getWifiSettings('');
echo "<pre>" . print_r($wifi, true) . "</pre>";

echo "<h3>3. Dispositivos Conectados (ARP scan ~3 seg)</h3>";
$devices = $router->getConnectedDevices('');
echo "<p>" . count($devices) . " dispositivos encontrados</p>";
foreach ($devices as $d) {
    $flags = [];
    if ($d['is_gateway']) $flags[] = 'ROUTER';
    if ($d['is_self']) $flags[] = 'THIS PC';
    $f = $flags ? ' [' . implode(', ', $flags) . ']' : '';
    echo "<p class='ok'>  • {$d['name']} | {$d['ip']} | {$d['mac']}$f</p>";
}
