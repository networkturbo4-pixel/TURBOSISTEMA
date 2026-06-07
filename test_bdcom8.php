<?php
/**
 * BDCOM - Descubrir páginas de seguridad y dispositivos conectados
 */
$routerIp = '192.168.123.1';
$user = 'user';
$pass = '123456';
$baseUrl = "http://$routerIp";
$cookieFile = sys_get_temp_dir() . '/bdcom_discover.txt';

echo "<h2>BDCOM - Descubrimiento de endpoints</h2>";
echo "<style>body{font-family:monospace;background:#1a1a2e;color:#e0e0e0;padding:20px;} 
pre{background:#16213e;padding:15px;border-radius:8px;overflow-x:auto;border:1px solid #0f3460;max-height:600px;overflow-y:auto;white-space:pre-wrap;} 
h3{color:#e94560;} .ok{color:#4ecca3;} .err{color:#e94560;}</style>";

// === Login ===
function bdcomLogin($baseUrl, $user, $pass, $cookieFile) {
    @unlink($cookieFile);
    $ch = curl_init("$baseUrl/admin/login.asp");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5,
        CURLOPT_COOKIEJAR => $cookieFile, CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_USERAGENT => 'Mozilla/5.0',
    ]);
    curl_exec($ch); curl_close($ch);

    $fields = ['challenge' => '', 'username' => $user, 'password' => $pass, 'save' => 'Login', 'submit-url' => '/admin/login.asp'];
    $inputVal = '';
    foreach ($fields as $name => $value) {
        $encodedValue = rawurlencode($value);
        $encodedValue = str_replace(['!', "'", '(', ')', '~', '%20'], ['%21', '%27', '%28', '%29', '%7E', '+'], $encodedValue);
        $inputVal .= $name . '=' . $encodedValue . '&';
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

    $postData = http_build_query(array_merge($fields, ['postSecurityFlag' => $csum]));
    $ch = curl_init("$baseUrl/boaform/admin/formLogin");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5, CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData, CURLOPT_FOLLOWLOCATION => false, CURLOPT_HEADER => true,
        CURLOPT_COOKIEJAR => $cookieFile, CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_USERAGENT => 'Mozilla/5.0', CURLOPT_REFERER => "$baseUrl/admin/login.asp",
    ]);
    $r = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    return ($c == 301 || $c == 302);
}

function getPage($baseUrl, $path, $cookieFile) {
    $ch = curl_init("$baseUrl$path");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5,
        CURLOPT_COOKIEJAR => $cookieFile, CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_USERAGENT => 'Mozilla/5.0',
    ]);
    $r = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    return ['body' => $r, 'code' => $c];
}

// Login
$loggedIn = bdcomLogin($baseUrl, $user, $pass, $cookieFile);
echo "<p>" . ($loggedIn ? "<span class='ok'>✅ Login exitoso</span>" : "<span class='err'>❌ Login fallido</span>") . "</p>";

if (!$loggedIn) die();

// === Explorar TODAS las páginas posibles ===
echo "<h3>Escaneando páginas internas...</h3>";
$pages = [
    // WiFi Security (different interfaces)
    '/admin/wlsecurity.asp', '/admin/wlsecurity.asp?wlan_idx=0',
    '/admin/wlsecurity.asp?wlan_id=0', '/admin/wlsecurity.asp?interface=wlan0',
    '/admin/wl_security.asp', '/admin/wlEncrypt.asp',
    
    // WiFi station list  
    '/admin/wlstationlist.asp', '/admin/wlstationlist.asp?wlan_idx=0',
    '/admin/wlstatbl.asp', '/admin/wl_station_list.asp',
    
    // DHCP client list
    '/admin/dhcpclienttbl.asp', '/admin/dhcpclient.asp', '/admin/dhcpd.asp',
    '/admin/dhcpclientinfo.asp', '/admin/dhcp_client.asp',
    
    // ARP table
    '/admin/arp.asp', '/admin/arptable.asp', '/admin/arp_table.asp',
    
    // WAN status
    '/admin/wanstatus.asp', '/admin/wan.asp',
    
    // Route table
    '/admin/routetbl.asp',
    
    // Admin pages
    '/admin/syslog.asp', '/admin/password.asp', '/admin/userconfig.asp',
    '/admin/saveconf.asp', '/admin/reboot.asp',
    
    // PON status
    '/admin/pon_status.asp', '/admin/gpon.asp', '/admin/ponsts.asp',
    
    // Firewall / NAT
    '/admin/firewall.asp', '/admin/nat.asp', '/admin/portfw.asp',
    '/admin/fw-macfilter.asp', '/admin/ipfilter.asp',
    
    // Other
    '/admin/menu.asp', '/admin/head.asp', '/admin/top.asp',
    '/admin/left.asp', '/admin/multi_wan.asp',
];

$foundPages = [];
foreach ($pages as $page) {
    $resp = getPage($baseUrl, $page, $cookieFile);
    if ($resp['code'] == 200 && strlen($resp['body']) > 100) {
        $isLogin = stripos($resp['body'], 'formLogin') !== false;
        if (!$isLogin) {
            $foundPages[] = $page;
            $hasSSID = stripos($resp['body'], 'ssid') !== false;
            $hasMAC = preg_match('/[0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2}/i', $resp['body']);
            $hasIP = preg_match('/192\.168\.\d+\.\d+/', $resp['body']);
            $hasPass = stripos($resp['body'], 'passphrase') !== false || stripos($resp['body'], 'pskValue') !== false || stripos($resp['body'], 'wpaPSK') !== false;
            $hasDHCP = stripos($resp['body'], 'dhcp') !== false;
            
            $tags = [];
            if ($hasSSID) $tags[] = '📶SSID';
            if ($hasPass) $tags[] = '🔑PASS';
            if ($hasMAC) $tags[] = '🔤MAC';
            if ($hasIP) $tags[] = '🌐IP';
            if ($hasDHCP) $tags[] = '📋DHCP';
            
            echo "<p class='ok'>✅ <b>$page</b> (" . strlen($resp['body']) . " bytes) " . implode(' ', $tags) . "</p>";
            echo "<pre>" . htmlspecialchars(substr($resp['body'], 0, 3000)) . "</pre>";
        }
    }
}

echo "<h3>Resumen: " . count($foundPages) . " páginas encontradas</h3>";
foreach ($foundPages as $p) echo "<p class='ok'>• $p</p>";

@unlink($cookieFile);
