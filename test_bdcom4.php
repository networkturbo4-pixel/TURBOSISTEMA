<?php
/**
 * BDCOM GP1704 - Login con MD5
 * Descarga common.js, replica postTableEncrypt, hace login real
 */

$routerIp = '192.168.123.1';
$user = 'user';
$pass = '123456';
$baseUrl = "http://$routerIp";

echo "<h2>BDCOM - Login con Encriptación MD5</h2>";
echo "<style>body{font-family:monospace;background:#1a1a2e;color:#e0e0e0;padding:20px;} 
pre{background:#16213e;padding:15px;border-radius:8px;overflow-x:auto;border:1px solid #0f3460;max-height:500px;overflow-y:auto;white-space:pre-wrap;word-wrap:break-word;} 
h3{color:#e94560;} .ok{color:#4ecca3;} .err{color:#e94560;} .warn{color:#f0a500;}</style>";

$cookieFile = sys_get_temp_dir() . '/bdcom_cookies3.txt';
@unlink($cookieFile);

function doGet($url, $cookieFile, $auth = '') {
    $ch = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
        CURLOPT_HEADER => true,
    ];
    if ($auth) $opts[CURLOPT_USERPWD] = $auth;
    curl_setopt_array($ch, $opts);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    return [
        'headers' => substr($response, 0, $headerSize),
        'body' => substr($response, $headerSize), 
        'code' => $httpCode
    ];
}

function doPost($url, $data, $cookieFile, $auth = '', $contentType = null) {
    $ch = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => is_array($data) ? http_build_query($data) : $data,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HEADER => true,
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
        CURLOPT_REFERER => "http://$GLOBALS[routerIp]/admin/login.asp",
    ];
    if ($auth) $opts[CURLOPT_USERPWD] = $auth;
    if ($contentType) $opts[CURLOPT_HTTPHEADER] = ["Content-Type: $contentType"];
    curl_setopt_array($ch, $opts);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    return [
        'headers' => substr($response, 0, $headerSize),
        'body' => substr($response, $headerSize),
        'code' => $httpCode
    ];
}

// === 1. Descargar common.js COMPLETO ===
echo "<h3>1. common.js completo</h3>";
$js = doGet("$baseUrl/common.js", $cookieFile);
echo "<p>HTTP {$js['code']} - " . strlen($js['body']) . " bytes</p>";

// Buscar la función postTableEncrypt
if (preg_match('/function\s+postTableEncrypt\s*\([^)]*\)\s*\{[^}]+\}/s', $js['body'], $m)) {
    echo "<p class='ok'>✅ Función postTableEncrypt encontrada:</p>";
    echo "<pre>" . htmlspecialchars($m[0]) . "</pre>";
} else {
    echo "<p class='warn'>⚠️ postTableEncrypt no encontrada con regex simple, buscando manualmente...</p>";
    $pos = strpos($js['body'], 'postTableEncrypt');
    if ($pos !== false) {
        echo "<p class='ok'>✅ Encontrada en posición $pos:</p>";
        echo "<pre>" . htmlspecialchars(substr($js['body'], $pos, 800)) . "</pre>";
    }
}

// Buscar funciones de encriptación
$searchFuncs = ['postTableEncrypt', 'setpass', 'encryptPass', 'md5', 'MD5', 'crypt_md5', 'challenge'];
foreach ($searchFuncs as $fn) {
    $count = substr_count($js['body'], $fn);
    if ($count > 0) {
        echo "<p class='ok'>• '$fn' aparece $count veces en common.js</p>";
    }
}

// Mostrar common.js completo
echo "<h3>2. common.js - Contenido completo</h3>";
echo "<pre>" . htmlspecialchars($js['body']) . "</pre>";

// === 2. Página de login completa ===
echo "<h3>3. Página de login - HTML completo</h3>";
$loginPage = doGet("$baseUrl/admin/login.asp", $cookieFile);
echo "<pre>" . htmlspecialchars($loginPage['body']) . "</pre>";

// === 3. Intentar login con diferentes combinaciones MD5 ===
echo "<h3>4. Intentos de login con MD5</h3>";

$md5pass = md5($pass);
echo "<p>MD5('$pass') = $md5pass</p>";

$attempts = [
    // Intento 1: password como MD5
    ['desc' => 'username + MD5(password) + postSecurityFlag=1', 'data' => [
        'username' => $user, 'password' => $md5pass, 'postSecurityFlag' => '1', 'challenge' => ''
    ]],
    // Intento 2: password como MD5 con submit
    ['desc' => 'username + MD5(password) + submit', 'data' => [
        'username' => $user, 'password' => $md5pass, 'postSecurityFlag' => '1', 'challenge' => '',
        'submit.htm?login.htm' => 'Send'
    ]],
    // Intento 3: Solo username + MD5(password)
    ['desc' => 'Solo username + MD5(password)', 'data' => "username=$user&password=$md5pass"],
    // Intento 4: password en texto plano + postSecurityFlag=0
    ['desc' => 'username + password plano + flag=0', 'data' => [
        'username' => $user, 'password' => $pass, 'postSecurityFlag' => '0'
    ]],
    // Intento 5: Con Basic Auth + form
    ['desc' => 'Basic Auth + MD5 form', 'data' => [
        'username' => $user, 'password' => $md5pass, 'postSecurityFlag' => '1'
    ], 'auth' => "$user:$pass"],
    // Intento 6: challenge + md5(challenge + password)
    ['desc' => 'challenge-based: MD5(challenge+pass)', 'data' => [
        'username' => $user, 'password' => md5('' . $pass), 'postSecurityFlag' => '1', 'challenge' => ''
    ]],
];

foreach ($attempts as $attempt) {
    $auth = $attempt['auth'] ?? '';
    $resp = doPost("$baseUrl/boaform/admin/formLogin", $attempt['data'], $cookieFile, $auth);
    
    $hasError = (stripos($resp['body'], 'ERROR') !== false);
    $hasRedirect = (preg_match('/Location:\s*(.+)/i', $resp['headers'], $locMatch));
    $redirectTo = $hasRedirect ? trim($locMatch[1]) : '';
    
    $icon = $hasError ? '❌' : ($hasRedirect ? '✅' : '⚠️');
    $class = $hasError ? 'err' : ($hasRedirect ? 'ok' : 'warn');
    
    echo "<p class='$class'>$icon <b>{$attempt['desc']}</b> → HTTP {$resp['code']}";
    if ($hasRedirect) echo " → Redirect: $redirectTo";
    if ($hasError) echo " → Auth error";
    echo "</p>";
    
    // Si hubo redirect, intentar acceder a la página destino
    if ($hasRedirect && !$hasError) {
        echo "<p class='ok'>🎉 ¡LOGIN EXITOSO! Explorando páginas internas...</p>";
        
        $internalPages = [
            '/status.html', '/wlbasic.html', '/wlsecurity.html',
            '/dhcpclienttbl.html', '/wlstationlist.html', '/wlactrl.html',
            '/admin/status.asp', '/admin/wlbasic.asp', '/admin/wlsecurity.asp',
        ];
        foreach ($internalPages as $p) {
            $internal = doGet("$baseUrl$p", $cookieFile);
            if ($internal['code'] === 200 && strlen($internal['body']) > 100) {
                $isLogin = stripos($internal['body'], 'formLogin') !== false;
                if (!$isLogin) {
                    echo "<p class='ok'>  ✅ $p (" . strlen($internal['body']) . " bytes)</p>";
                    echo "<pre>" . htmlspecialchars(substr($internal['body'], 0, 2000)) . "</pre>";
                }
            }
        }
        break; // Parar si el login fue exitoso
    }
}

@unlink($cookieFile);
