<?php
// Test de detección de red
$o = shell_exec('ipconfig /all');
echo "=== RAW IPCONFIG (primeros 1500 chars) ===\n";
echo substr($o, 0, 1500) . "\n\n";

echo "=== REGEX TESTS ===\n";

// Test 1: IP
if (preg_match('/IPv4[.\s:]+(\d+\.\d+\.\d+\.\d+)/i', $o, $m)) {
    echo "IP encontrada: " . $m[1] . "\n";
} else {
    echo "IP: NO ENCONTRADA\n";
}

// Test 2: Gateway
if (preg_match('/Puerta[^\r\n]+:\s*(\d+\.\d+\.\d+\.\d+)/i', $o, $m)) {
    echo "Gateway encontrada: " . $m[1] . "\n";
} else {
    echo "Gateway: NO ENCONTRADA\n";
}

// Test 3: DNS suffix
if (preg_match('/Sufijo DNS[^\r\n]+:\s*(\S+)/i', $o, $m)) {
    echo "DNS Suffix: " . $m[1] . "\n";
} else {
    echo "DNS Suffix: NO ENCONTRADO\n";
}

// Test 4: MAC
if (preg_match('/sica[.\s:]+([0-9A-Fa-f]{2}[-:][0-9A-Fa-f]{2}[-:][0-9A-Fa-f]{2}[-:][0-9A-Fa-f]{2}[-:][0-9A-Fa-f]{2}[-:][0-9A-Fa-f]{2})/i', $o, $m)) {
    echo "MAC encontrada: " . $m[1] . "\n";
} else {
    echo "MAC: NO ENCONTRADA\n";
}

// Test 5: Subnet
if (preg_match('/scara[^\r\n]+:\s*(\d+\.\d+\.\d+\.\d+)/i', $o, $m)) {
    echo "Subnet: " . $m[1] . "\n";
} else {
    echo "Subnet: NO ENCONTRADA\n";
}

echo "\n=== ARP TABLE ===\n";
$arp = shell_exec('arp -a');
echo $arp . "\n";

echo "\n=== PING SWEEP (esperando 2 seg) ===\n";
// Hacer ping rápido a varios IPs comunes
$subnet = '192.168.123';
$pings = [];
for ($i = 1; $i <= 254; $i++) {
    $pings[] = "start /b ping -n 1 -w 200 $subnet.$i >nul 2>&1";
}
// Ejecutar en lotes
$batchCmd = implode(' & ', array_slice($pings, 0, 50));
shell_exec($batchCmd);
sleep(2);

echo "=== ARP TABLE POST-PING ===\n";
$arp2 = shell_exec('arp -a');
echo $arp2 . "\n";

echo "\n=== HOSTNAME ===\n";
echo shell_exec('hostname') . "\n";
