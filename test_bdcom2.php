<?php
/**
 * BDCOM GP1704 - Login Inteligente
 * Paso 1: Obtener challenge + common.js
 * Paso 2: Replicar la encriptación MD5
 * Paso 3: Login y explorar páginas internas
 */

$routerIp = '192.168.123.1';
$user = 'user';
$pass = '123456';
$baseUrl = "http://$routerIp";

echo "<h2>BDCOM Login Inteligente</h2>";
echo "<style>body{font-family:monospace;background:#1a1a2e;color:#e0e0e0;padding:20px;} 
pre{background:#16213e;padding:15px;border-radius:8px;overflow-x:auto;border:1px solid #0f3460;max-height:400px;overflow-y:auto;} 
h3{color:#e94560;} .ok{color:#4ecca3;} .err{color:#e94560;}</style>";

// Cookie jar para mantener la sesión
$cookieFile = sys_get_temp_dir() . '/bdcom_cookies.txt';
@unlink($cookieFile);

function bdcomGet($url, $cookieFile) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['body' => $response, 'code' => $httpCode];
}

function bdcomPost($url, $data, $cookieFile) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => is_array($data) ? http_build_query($data) : $data,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HEADER => true,
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['body' => $response, 'code' => $httpCode];
}

// === PASO 1: Obtener la página de login y el challenge ===
echo "<h3>1. Obteniendo página de login...</h3>";
$loginPage = bdcomGet("$baseUrl/admin/login.asp", $cookieFile);
echo "<p>HTTP {$loginPage['code']} - " . strlen($loginPage['body']) . " bytes</p>";

// Extraer el challenge
$challenge = '';
if (preg_match('/name="challenge"\s*value="([^"]*)"/', $loginPage['body'], $m)) {
    $challenge = $m[1];
    echo "<p class='ok'>✅ Challenge encontrado: <b>$challenge</b></p>";
} else {
    // Buscar challenge en el JS
    if (preg_match('/challenge["\s]*[=:]\s*["\']([^"\']+)/', $loginPage['body'], $m)) {
        $challenge = $m[1];
        echo "<p class='ok'>✅ Challenge (JS): <b>$challenge</b></p>";
    } else {
        echo "<p class='err'>⚠️ Challenge no encontrado en HTML, mostrando form...</p>";
        // Mostrar inputs del form
        preg_match_all('/<input[^>]+>/i', $loginPage['body'], $inputs);
        echo "<pre>Inputs encontrados:\n" . implode("\n", $inputs[0]) . "</pre>";
    }
}

// Mostrar el formulario completo
echo "<h3>2. Formulario de login</h3>";
if (preg_match('/<form[^>]*>(.*?)<\/form>/si', $loginPage['body'], $formMatch)) {
    echo "<pre>" . htmlspecialchars(substr($formMatch[0], 0, 2000)) . "</pre>";
}

// === PASO 2: Obtener common.js para entender la encriptación ===
echo "<h3>3. Descargando common.js...</h3>";
$commonJs = bdcomGet("$baseUrl/common.js", $cookieFile);
echo "<p>HTTP {$commonJs['code']} - " . strlen($commonJs['body']) . " bytes</p>";
if ($commonJs['code'] === 200 && strlen($commonJs['body']) > 100) {
    echo "<pre>" . htmlspecialchars($commonJs['body']) . "</pre>";
}

// === PASO 2b: Obtener md5.js ===
echo "<h3>4. Descargando rollups/md5.js...</h3>";
$md5Js = bdcomGet("$baseUrl/rollups/md5.js", $cookieFile);
echo "<p>HTTP {$md5Js['code']} - " . strlen($md5Js['body']) . " bytes</p>";

// === PASO 2c: Obtener php-crypt-md5.js ===
echo "<h3>5. Descargando php-crypt-md5.js...</h3>";
$cryptJs = bdcomGet("$baseUrl/php-crypt-md5.js", $cookieFile);
echo "<p>HTTP {$cryptJs['code']} - " . strlen($cryptJs['body']) . " bytes</p>";
if ($cryptJs['code'] === 200) {
    echo "<pre>" . htmlspecialchars(substr($cryptJs['body'], 0, 3000)) . "</pre>";
}

// === PASO 3: Intentar login con HTTP Basic Auth (a veces funciona en Boa) ===
echo "<h3>6. Login con HTTP Basic Auth directo...</h3>";
$ch = curl_init("$baseUrl/admin/login.asp");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 5,
    CURLOPT_USERPWD => "$user:$pass",
    CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_COOKIEJAR => $cookieFile,
    CURLOPT_COOKIEFILE => $cookieFile,
    CURLOPT_HEADER => true,
]);
$basicResp = curl_exec($ch);
$basicCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "<p>HTTP $basicCode</p>";

// === PASO 4: Login con form data normal (sin encriptar) ===
echo "<h3>7. Login directo con campos del formulario...</h3>";

// Los campos típicos del form Boa/Realtek
$loginFields = [
    'challenge' => $challenge,
    'username' => $user,
    'password' => $pass,
    'postSecurityFlag' => '0',
    'submit.htm?login.htm' => 'Send',
];

$loginResp = bdcomPost("$baseUrl/boaform/admin/formLogin", $loginFields, $cookieFile);
echo "<p>HTTP {$loginResp['code']}</p>";
$loginBody = $loginResp['body'];

// Verificar si hay redirección (login exitoso generalmente redirige)
if (preg_match('/Location:\s*(.+)/i', $loginBody, $m)) {
    echo "<p class='ok'>✅ Redirección a: " . trim($m[1]) . "</p>";
}

// Verificar si hay error
if (stripos($loginBody, 'ERROR') !== false) {
    echo "<p class='err'>⚠️ Respuesta contiene ERROR</p>";
}

$headerEnd = strpos($loginBody, "\r\n\r\n");
$loginBodyContent = substr($loginBody, $headerEnd + 4);
echo "<pre>" . htmlspecialchars(substr($loginBodyContent, 0, 1000)) . "</pre>";

// === PASO 5: Intentar acceder a páginas internas post-login ===
echo "<h3>8. Explorando páginas internas (con cookies de sesión)...</h3>";
$internalPages = [
    '/admin/status.asp',
    '/admin/wlbasic.asp',
    '/wlbasic.html', 
    '/status.html',
    '/admin/wlsecurity.asp',
    '/admin/dhcpclienttbl.asp',
    '/admin/arp.asp',
    '/admin/wlstationlist.asp',
    '/wlstationlist.asp',
    '/admin/lanconfig.asp',
    '/admin/wanconfig.asp',
    '/admin/wladvanced.asp',
    '/admin/wlactrl.asp',
    '/admin/formWlanSetup',
    '/admin/formWlSiteSurvey',
    '/userconfig.html',
    '/wlsecurity.html',
    '/dhcpclienttbl.html',
    '/status.asp',
    '/wlansetup.asp',
];

foreach ($internalPages as $page) {
    $resp = bdcomGet("$baseUrl$page", $cookieFile);
    if ($resp['code'] === 200 && strlen($resp['body']) > 100) {
        $hasForm = preg_match('/<form|<input/i', $resp['body']);
        $hasTable = preg_match('/<table/i', $resp['body']);
        $isLogin = stripos($resp['body'], 'formLogin') !== false;
        
        if (!$isLogin) {
            echo "<p class='ok'>✅ $page ({$resp['code']}, " . strlen($resp['body']) . " bytes" . 
                 ($hasForm ? ', tiene form' : '') . ($hasTable ? ', tiene tabla' : '') . ")</p>";
            echo "<pre>" . htmlspecialchars(substr($resp['body'], 0, 1500)) . "</pre>";
        } else {
            echo "<p>↩️ $page → Redirige al login</p>";
        }
    }
}

// Limpiar cookies
@unlink($cookieFile);
