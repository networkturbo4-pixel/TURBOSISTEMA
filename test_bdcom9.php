<?php
/**
 * BDCOM - Login + Descubrir páginas (con debug)
 */
$routerIp = '192.168.123.1';
$user = 'user';
$pass = '123456';
$baseUrl = "http://$routerIp";
$cookieFile = sys_get_temp_dir() . '/bdcom_discover2.txt';
@unlink($cookieFile);

echo "<h2>BDCOM - Login + Descubrimiento (con debug)</h2>";
echo "<style>body{font-family:monospace;background:#1a1a2e;color:#e0e0e0;padding:20px;} 
pre{background:#16213e;padding:15px;border-radius:8px;overflow-x:auto;border:1px solid #0f3460;max-height:600px;overflow-y:auto;white-space:pre-wrap;} 
h3{color:#e94560;} .ok{color:#4ecca3;} .err{color:#e94560;}</style>";

function calcChecksum($fields) {
    $inputVal = '';
    foreach ($fields as $name => $value) {
        if ($name === 'postSecurityFlag') continue;
        $ev = rawurlencode($value);
        $ev = str_replace(['!', "'", '(', ')', '~', '%20'], ['%21', '%27', '%28', '%29', '%7E', '+'], $ev);
        $inputVal .= $name . '=' . $ev . '&';
    }
    $csum = 0; $len = strlen($inputVal); $i = 0;
    while ($i < $len) {
        if (($i + 4) > $len) {
            if ($i < $len) $csum += (ord($inputVal[$i]) << 24);
            if (($i+1) < $len) $csum += (ord($inputVal[$i+1]) << 16);
            if (($i+2) < $len) $csum += (ord($inputVal[$i+2]) << 8);
            break;
        } else {
            $csum += (ord($inputVal[$i]) << 24) + (ord($inputVal[$i+1]) << 16) + (ord($inputVal[$i+2]) << 8) + ord($inputVal[$i+3]);
            $i += 4;
        }
    }
    $csum = $csum & 0xFFFFFFFF;
    $csum = ($csum & 0xffff) + ($csum >> 16);
    $csum = $csum & 0xffff;
    $csum = (~$csum) & 0xffff;
    return $csum;
}

// === 1. GET Login page ===
echo "<h3>1. GET /admin/login.asp</h3>";
$ch = curl_init("$baseUrl/admin/login.asp");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5,
    CURLOPT_COOKIEJAR => $cookieFile, CURLOPT_COOKIEFILE => $cookieFile,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
    CURLOPT_HEADER => true,
]);
$resp = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$error = curl_error($ch);
curl_close($ch);

echo "<p>HTTP $code" . ($error ? " Error: $error" : '') . "</p>";
echo "<pre>Response Headers:\n" . htmlspecialchars(substr($resp, 0, $headerSize)) . "</pre>";

// === 2. POST Login ===
echo "<h3>2. POST /boaform/admin/formLogin</h3>";

$fields = [
    'challenge' => '',
    'username' => $user,
    'password' => $pass,
    'save' => 'Login',
    'submit-url' => '/admin/login.asp',
];
$checksum = calcChecksum($fields);
$fields['postSecurityFlag'] = $checksum;

$postData = http_build_query($fields);
echo "<p>POST data: <code>" . htmlspecialchars($postData) . "</code></p>";
echo "<p>Checksum: $checksum</p>";

$ch = curl_init("$baseUrl/boaform/admin/formLogin");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5,
    CURLOPT_POST => true, CURLOPT_POSTFIELDS => $postData,
    CURLOPT_FOLLOWLOCATION => false, CURLOPT_HEADER => true,
    CURLOPT_COOKIEJAR => $cookieFile, CURLOPT_COOKIEFILE => $cookieFile,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
    CURLOPT_REFERER => "$baseUrl/admin/login.asp",
]);
$resp = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
curl_close($ch);

$headers = substr($resp, 0, $headerSize);
$body = substr($resp, $headerSize);
$hasError = stripos($body, 'ERROR') !== false;
$hasRedirect = preg_match('/Location:\s*(.+)/i', $headers, $loc);

echo "<p>HTTP $code</p>";
echo "<pre>Response Headers:\n" . htmlspecialchars($headers) . "</pre>";
echo "<pre>Response Body:\n" . htmlspecialchars(substr($body, 0, 500)) . "</pre>";

if ($hasRedirect) {
    echo "<p class='ok'>🎉 LOGIN OK → Redirect: " . trim($loc[1]) . "</p>";
} elseif ($hasError) {
    echo "<p class='err'>❌ AUTH ERROR - ¿Estás logueado en el navegador? Haz logout primero.</p>";
} else {
    echo "<p class='err'>⚠️ Respuesta inesperada</p>";
}

// Si login exitoso, seguir con la exploración
if ($hasRedirect || ($code == 200 && !$hasError)) {
    echo "<h3>3. Explorando páginas internas...</h3>";
    
    $pages = [
        '/admin/status.asp' => 'Status del dispositivo',
        '/admin/wlbasic.asp' => 'WiFi Basic (SSID)',
        '/admin/wlsecurity.asp' => 'WiFi Security (contraseña)',
        '/admin/wlsecurity.asp?wlan_idx=0' => 'WiFi Security 2.4G',
        '/admin/wlstationlist.asp' => 'Station List',
        '/admin/wlstationlist.asp?wlan_idx=0' => 'Station List 2.4G',
        '/admin/tcpiplan.asp' => 'LAN Config',
        '/admin/wlactrl.asp' => 'Access Control (MAC filter)',
        '/admin/dhcpclienttbl.asp' => 'DHCP Client Table',
        '/admin/arp.asp' => 'ARP Table',
        '/admin/fw-macfilter.asp' => 'Firewall MAC Filter',
        '/admin/password.asp' => 'Password Change',
        '/admin/saveconf.asp' => 'Save Config',
        '/admin/wlbasic_5g.asp' => 'WiFi 5G Basic',
        '/admin/wlsecurity_5g.asp' => 'WiFi 5G Security',
        '/admin/multi_wan.asp' => 'Multi WAN',
        '/admin/ponsts.asp' => 'PON Status',
        '/admin/routetbl.asp' => 'Route Table',
        '/admin/syslog.asp' => 'System Log',
    ];
    
    $found = [];
    foreach ($pages as $page => $desc) {
        $ch = curl_init("$baseUrl$page");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 3,
            CURLOPT_COOKIEJAR => $cookieFile, CURLOPT_COOKIEFILE => $cookieFile,
            CURLOPT_USERAGENT => 'Mozilla/5.0',
        ]);
        $r = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
        $isLogin = stripos($r ?? '', 'formLogin') !== false;
        
        if ($c == 200 && !$isLogin && strlen($r) > 100) {
            $found[] = $page;
            // Buscar datos clave
            $hasSSID = preg_match('/ssid[^=]*=\s*["\']([^"\']+)/i', $r, $ssidMatch);
            $hasPass = preg_match('/pskValue[^=]*=\s*["\']([^"\']+)/i', $r, $pskMatch);
            if (!$hasPass) $hasPass = preg_match('/wpaPSK[^=]*=\s*["\']([^"\']+)/i', $r, $pskMatch);
            if (!$hasPass) $hasPass = preg_match('/passphrase[^=]*=\s*["\']([^"\']+)/i', $r, $pskMatch);
            $hasMac = preg_match_all('/([0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2})/i', $r, $macMatches);
            
            $tags = [];
            if ($hasSSID) $tags[] = "📶 SSID=" . $ssidMatch[1];
            if ($hasPass) $tags[] = "🔑 PSK=" . $pskMatch[1];
            if ($hasMac) $tags[] = "🔤 " . $hasMac . " MACs";
            
            echo "<p class='ok'>✅ <b>$desc</b> ($page, " . strlen($r) . " bytes) " . implode(' | ', $tags) . "</p>";
            echo "<pre>" . htmlspecialchars(substr($r, 0, 4000)) . "</pre>";
        } else {
            echo "<p>❌ $desc ($page) → HTTP $c" . ($isLogin ? ' (login)' : '') . "</p>";
        }
    }
    
    echo "<h3>Resumen: " . count($found) . " páginas accesibles</h3>";
}

@unlink($cookieFile);
