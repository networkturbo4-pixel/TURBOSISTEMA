<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require_once __DIR__ . '/includes/RouterManager.php';

echo "=== Test LocalNetworkRouter ===\n\n";

$router = RouterManager::getRouter('local');
$router->connect('', '', '', '');

echo "--- WiFi Settings ---\n";
$wifi = $router->getWifiSettings('');
print_r($wifi);

echo "\n--- Dispositivos (esto tarda ~3 segundos por el ping sweep) ---\n";
$devices = $router->getConnectedDevices('');
echo "Encontrados: " . count($devices) . " dispositivos\n\n";
foreach ($devices as $d) {
    $flags = [];
    if ($d['is_gateway']) $flags[] = 'GATEWAY';
    if ($d['is_self']) $flags[] = 'THIS PC';
    $flagStr = $flags ? ' [' . implode(', ', $flags) . ']' : '';
    echo "  {$d['name']} | {$d['ip']} | {$d['mac']}{$flagStr}\n";
}
