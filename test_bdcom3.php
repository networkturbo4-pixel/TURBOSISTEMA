<?php
/**
 * BDCOM GP1704 - Exploración con Basic Auth
 * Usa HTTP Basic Auth para navegar las páginas internas del router
 */

$routerIp = '192.168.123.1';
$user = 'user';
$pass = '123456';
$baseUrl = "http://$routerIp";

echo "<h2>BDCOM - Exploración con Basic Auth</h2>";
echo "<style>body{font-family:monospace;background:#1a1a2e;color:#e0e0e0;padding:20px;} 
pre{background:#16213e;padding:15px;border-radius:8px;overflow-x:auto;border:1px solid #0f3460;max-height:500px;overflow-y:auto;white-space:pre-wrap;word-wrap:break-word;} 
h3{color:#e94560;} .ok{color:#4ecca3;} .err{color:#e94560;} .warn{color:#f0a500;}</style>";

$cookieFile = sys_get_temp_dir() . '/bdcom_cookies2.txt';
@unlink($cookieFile);

function authGet($url, $user, $pass, $cookieFile) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_USERPWD => "$user:$pass",
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);
    return ['body' => $response, 'code' => $httpCode, 'url' => $finalUrl];
}

// === Páginas a explorar (exhaustivo para Boa/Realtek ONUs) ===
$pages = [
    // Status pages
    '/status.html', '/status.asp', '/admin/status.asp',
    '/info.html', '/devinfo.html', '/sysinfo.html',
    
    // WiFi pages
    '/wlbasic.html', '/wlbasic.asp', '/admin/wlbasic.asp',
    '/wlsecurity.html', '/wlsecurity.asp', '/admin/wlsecurity.asp',
    '/wladvanced.html', '/wladvanced.asp', '/admin/wladvanced.asp',
    '/wlactrl.html', '/wlactrl.asp', '/admin/wlactrl.asp',
    '/wlstationlist.html', '/wlstationlist.asp', '/admin/wlstationlist.asp',
    '/wifi.html', '/wireless.html', '/wifi_basic.html',
    
    // WLAN 5G
    '/wlbasic_5g.html', '/admin/wlbasic_5g.asp',
    '/wlsecurity_5g.html', '/admin/wlsecurity_5g.asp',
    
    // LAN / DHCP / ARP
    '/dhcpclienttbl.html', '/admin/dhcpclienttbl.asp',
    '/arp.html', '/admin/arp.asp', '/arptable.html',
    '/tcpip_lan.html', '/admin/tcpip_lan.asp',
    '/lan.html', '/lanconfig.html',
    
    // Device management / MAC filter
    '/macfilter.html', '/admin/macfilter.asp',
    '/wlmacflt.html', '/admin/wlmacflt.asp',
    '/wlactrl.html', '/admin/wlactrl.asp',
    '/fw-macfilter.html', '/admin/fw-macfilter.asp',
    '/firewall.html', '/admin/firewall.asp',
    
    // WAN
    '/wanconfig.html', '/admin/wanconfig.asp',
    '/wan.html',
    
    // Admin
    '/userconfig.html', '/admin/userconfig.asp',
    '/password.html', '/admin/password.asp',
    '/saveconf.html', '/admin/saveconf.asp',
    '/reboot.html', '/admin/reboot.asp',
    
    // PON
    '/pon_status.html', '/admin/pon_status.asp',
    '/gpon_status.html', '/admin/gpon_status.asp',
    
    // Otros
    '/menu-bar.html', '/admin/menu-bar.asp',
    '/left-menu.html', '/admin/left-menu.asp',
    '/main.html', '/home.html',
    '/index.html', '/admin/index.asp',
];

$found = [];
$notFound = [];

echo "<h3>Escaneando " . count($pages) . " páginas...</h3>";

foreach ($pages as $page) {
    $resp = authGet("$baseUrl$page", $user, $pass, $cookieFile);
    
    if ($resp['code'] === 200 && strlen($resp['body']) > 100) {
        // Verificar que no sea la página de login
        $isLogin = (stripos($resp['body'], 'formLogin') !== false || stripos($resp['body'], 'cmlogin') !== false);
        $isError = (stripos($resp['body'], 'ERROR') !== false && strlen($resp['body']) < 500);
        
        if (!$isLogin && !$isError) {
            $found[] = $page;
            $hasSSID = (stripos($resp['body'], 'ssid') !== false || stripos($resp['body'], 'SSID') !== false);
            $hasMac = (stripos($resp['body'], 'mac') !== false || stripos($resp['body'], 'MAC') !== false);
            $hasIP = preg_match('/192\.168\.\d+\.\d+/', $resp['body']);
            $hasDHCP = (stripos($resp['body'], 'dhcp') !== false || stripos($resp['body'], 'DHCP') !== false);
            $hasWlan = (stripos($resp['body'], 'wlan') !== false || stripos($resp['body'], 'wireless') !== false);
            $hasPass = (stripos($resp['body'], 'passphrase') !== false || stripos($resp['body'], 'pskValue') !== false || stripos($resp['body'], 'wpa') !== false);
            
            $tags = [];
            if ($hasSSID) $tags[] = '📶 SSID';
            if ($hasPass) $tags[] = '🔑 PASSWORD';
            if ($hasMac) $tags[] = '🔤 MAC';
            if ($hasIP) $tags[] = '🌐 IP';
            if ($hasDHCP) $tags[] = '📋 DHCP';
            if ($hasWlan) $tags[] = '📡 WLAN';
            $tagStr = $tags ? ' [' . implode(', ', $tags) . ']' : '';
            
            echo "<p class='ok'>✅ <b>$page</b> (" . strlen($resp['body']) . " bytes)$tagStr</p>";
            echo "<pre>" . htmlspecialchars(substr($resp['body'], 0, 3000)) . "</pre>";
        } else {
            echo "<p class='warn'>↩️ $page → Redirige al login o error</p>";
        }
    }
}

echo "<h3>Resumen: " . count($found) . " páginas encontradas</h3>";
foreach ($found as $p) {
    echo "<p class='ok'>• $p</p>";
}

@unlink($cookieFile);
