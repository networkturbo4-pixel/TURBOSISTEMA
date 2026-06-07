<?php
/**
 * BDCOM GP1704 - Login con checksum postSecurityFlag
 * Replica exacta del algoritmo postTableEncrypt de common.js
 */
$routerIp = '192.168.123.1';
$user = 'user';
$pass = '123456';
$baseUrl = "http://$routerIp";

echo "<h2>BDCOM - Login con postSecurityFlag Checksum</h2>";
echo "<style>body{font-family:monospace;background:#1a1a2e;color:#e0e0e0;padding:20px;} 
pre{background:#16213e;padding:15px;border-radius:8px;overflow-x:auto;border:1px solid #0f3460;max-height:500px;overflow-y:auto;white-space:pre-wrap;} 
h3{color:#e94560;} .ok{color:#4ecca3;} .err{color:#e94560;}</style>";

/**
 * Replica la función postTableEncrypt de common.js
 * Calcula el checksum del payload serializado
 */
function calculatePostSecurityFlag($fields) {
    // Serializar los campos como lo hace postTableEncrypt
    // Excluye: postSecurityFlag, csrftoken, submit/reset/button types
    $inputVal = '';
    foreach ($fields as $name => $value) {
        if ($name === 'postSecurityFlag' || $name === 'csrftoken') continue;
        
        // URL encode similar al JS
        $encodedName = str_replace(['[', ']'], ['%5B', '%5D'], $name);
        $encodedValue = rawurlencode($value);
        // Encode additional chars like JS does
        $encodedValue = str_replace(['!', "'", '(', ')', '~', '%20'], ['%21', '%27', '%28', '%29', '%7E', '+'], $encodedValue);
        
        $inputVal .= $encodedName . '=' . $encodedValue . '&';
    }
    
    echo "<p>Serialized payload: <code>" . htmlspecialchars($inputVal) . "</code></p>";
    
    // Calcular checksum (replica exacta del JS)
    $csum = 0;
    $len = strlen($inputVal);
    $i = 0;
    
    while ($i < $len) {
        if (($i + 4) > $len) {
            if ($i < $len) $csum += (ord($inputVal[$i]) << 24);
            if (($i + 1) < $len) $csum += (ord($inputVal[$i + 1]) << 16);
            if (($i + 2) < $len) $csum += (ord($inputVal[$i + 2]) << 8);
            break;
        } else {
            $csum += (ord($inputVal[$i]) << 24) + (ord($inputVal[$i + 1]) << 16) + (ord($inputVal[$i + 2]) << 8) + ord($inputVal[$i + 3]);
            $i += 4;
        }
    }
    
    // PHP integers can be 64-bit, need to handle overflow like JS (32-bit)
    $csum = $csum & 0xFFFFFFFF; // Truncate to 32 bits
    $csum = ($csum & 0xffff) + ($csum >> 16);
    $csum = $csum & 0xffff;
    $csum = (~$csum) & 0xffff;
    
    echo "<p>Checksum: <b>$csum</b></p>";
    return (string)$csum;
}

$cookieFile = sys_get_temp_dir() . '/bdcom_checksum.txt';
@unlink($cookieFile);

// === PASO 1: GET login page para cookie de sesión ===
echo "<h3>1. Obteniendo sesión...</h3>";
$ch = curl_init("$baseUrl/admin/login.asp");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5,
    CURLOPT_COOKIEJAR => $cookieFile, CURLOPT_COOKIEFILE => $cookieFile,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
    CURLOPT_HEADER => true,
]);
$loginPageResp = curl_exec($ch);
$loginCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "<p>Login page HTTP $loginCode</p>";

// Mostrar cookies obtenidas
if (file_exists($cookieFile)) {
    echo "<pre>Cookies: " . htmlspecialchars(file_get_contents($cookieFile)) . "</pre>";
}

// === PASO 2: Construir payload y calcular checksum ===
echo "<h3>2. Calculando checksum...</h3>";

// Los campos del form en el orden que aparecen en el HTML
$fields = [
    'challenge' => '',
    'username' => $user,
    'password' => $pass,
    'save' => 'Login',         // El botón submit (isclick=1, se incluye)
    'submit-url' => '/admin/login.asp',
];

$checksum = calculatePostSecurityFlag($fields);

// === PASO 3: POST con el checksum calculado ===
echo "<h3>3. Login con checksum...</h3>";

// Construir el POST data exactamente como lo haría el navegador
$postData = http_build_query([
    'challenge' => '',
    'username' => $user,
    'password' => $pass,
    'save' => 'Login',
    'submit-url' => '/admin/login.asp',
    'postSecurityFlag' => $checksum,
]);

echo "<p>POST data: <code>" . htmlspecialchars($postData) . "</code></p>";

$ch = curl_init("$baseUrl/boaform/admin/formLogin");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $postData,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_HEADER => true,
    CURLOPT_COOKIEJAR => $cookieFile, CURLOPT_COOKIEFILE => $cookieFile,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
    CURLOPT_REFERER => "$baseUrl/admin/login.asp",
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
curl_close($ch);

$headers = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);

$hasError = stripos($body, 'ERROR') !== false;
$hasRedirect = preg_match('/Location:\s*(.+)/i', $headers, $locMatch);
$redirect = $hasRedirect ? trim($locMatch[1]) : '';

if ($hasError) {
    echo "<p class='err'>❌ AUTH ERROR</p>";
    echo "<pre>" . htmlspecialchars(substr($body, 0, 500)) . "</pre>";
    
    // Intentar sin el botón submit en el checksum
    echo "<h3>3b. Retry sin botón 'save' en el checksum...</h3>";
    $fields2 = [
        'challenge' => '',
        'username' => $user,
        'password' => $pass,
        'submit-url' => '/admin/login.asp',
    ];
    $checksum2 = calculatePostSecurityFlag($fields2);
    
    $postData2 = http_build_query([
        'challenge' => '',
        'username' => $user,
        'password' => $pass,
        'save' => 'Login',
        'submit-url' => '/admin/login.asp',
        'postSecurityFlag' => $checksum2,
    ]);
    
    $ch = curl_init("$baseUrl/boaform/admin/formLogin");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5,
        CURLOPT_POST => true, CURLOPT_POSTFIELDS => $postData2,
        CURLOPT_FOLLOWLOCATION => false, CURLOPT_HEADER => true,
        CURLOPT_COOKIEJAR => $cookieFile, CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_USERAGENT => 'Mozilla/5.0',
        CURLOPT_REFERER => "$baseUrl/admin/login.asp",
    ]);
    $response2 = curl_exec($ch);
    $httpCode2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize2 = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    $headers2 = substr($response2, 0, $headerSize2);
    $body2 = substr($response2, $headerSize2);
    $hasError2 = stripos($body2, 'ERROR') !== false;
    $hasRedirect2 = preg_match('/Location:\s*(.+)/i', $headers2, $locMatch2);
    
    echo "<p>" . ($hasError2 ? "<span class='err'>❌ AUTH ERROR</span>" : "<span class='ok'>✅ HTTP $httpCode2</span>") . "</p>";
    if ($hasRedirect2) echo "<p class='ok'>🎉 Redirect: " . trim($locMatch2[1]) . "</p>";
    echo "<pre>" . htmlspecialchars(substr($body2, 0, 500)) . "</pre>";
} else {
    echo "<p class='ok'>🎉 HTTP $httpCode" . ($hasRedirect ? " → Redirect: $redirect" : '') . "</p>";
}

// === PASO 4: Si login exitoso, explorar páginas internas ===
echo "<h3>4. Explorando páginas internas...</h3>";

$internalPages = [
    '/admin/status.asp' => 'Status',
    '/admin/wlbasic.asp' => 'WiFi Basic Settings',
    '/admin/wlsecurity.asp' => 'WiFi Security',
    '/admin/tcpiplan.asp' => 'LAN Config',
    '/admin/wlstationlist.asp' => 'WiFi Station List',
    '/admin/wlactrl.asp' => 'WiFi Access Control',
];

foreach ($internalPages as $page => $desc) {
    $ch = curl_init("$baseUrl$page");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 3,
        CURLOPT_COOKIEJAR => $cookieFile, CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_USERAGENT => 'Mozilla/5.0',
    ]);
    $r = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    $isLogin = stripos($r, 'formLogin') !== false;
    
    if ($c == 200 && !$isLogin && strlen($r) > 100) {
        echo "<p class='ok'>✅ <b>$desc</b> ($page) - " . strlen($r) . " bytes</p>";
        echo "<pre>" . htmlspecialchars(substr($r, 0, 2000)) . "</pre>";
    } else {
        echo "<p class='err'>❌ $desc ($page) → " . ($isLogin ? 'Redirige al login' : "HTTP $c") . "</p>";
    }
}

@unlink($cookieFile);
