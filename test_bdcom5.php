<?php
/**
 * BDCOM GP1704 - Login definitivo
 */
$routerIp = '192.168.123.1';
$user = 'user';
$pass = '123456';
$baseUrl = "http://$routerIp";

echo "<h2>BDCOM - Login Definitivo</h2>";
echo "<style>body{font-family:monospace;background:#1a1a2e;color:#e0e0e0;padding:20px;} 
pre{background:#16213e;padding:15px;border-radius:8px;overflow-x:auto;border:1px solid #0f3460;max-height:600px;overflow-y:auto;white-space:pre-wrap;} 
h3{color:#e94560;} .ok{color:#4ecca3;} .err{color:#e94560;} .warn{color:#f0a500;}</style>";

$cookieFile = sys_get_temp_dir() . '/bdcom_final.txt';

function attempt($url, $postData, $cookieFile, $desc, $auth = '') {
    @unlink($cookieFile);
    global $baseUrl;
    
    // Primero GET al login para obtener cookie de sesión
    $ch = curl_init("$baseUrl/admin/login.asp");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 3,
        CURLOPT_COOKIEJAR => $cookieFile, CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_USERAGENT => 'Mozilla/5.0',
    ]);
    curl_exec($ch); curl_close($ch);
    
    // Ahora POST al login
    $ch = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HEADER => true,
        CURLOPT_COOKIEJAR => $cookieFile, CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_USERAGENT => 'Mozilla/5.0',
        CURLOPT_REFERER => "$baseUrl/admin/login.asp",
    ];
    if ($auth) $opts[CURLOPT_USERPWD] = $auth;
    curl_setopt_array($ch, $opts);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    $hasError = stripos($body, 'ERROR') !== false;
    $hasRedirect = preg_match('/Location:\s*(.+)/i', $headers, $loc);
    $redirect = $hasRedirect ? trim($loc[1]) : '';
    
    $status = $hasError ? 'err' : ($hasRedirect ? 'ok' : 'warn');
    $icon = $hasError ? '❌' : ($hasRedirect ? '🎉' : '⚠️');
    
    echo "<p class='$status'>$icon <b>$desc</b> → HTTP $httpCode";
    if ($hasRedirect) echo " → Location: $redirect";
    if ($hasError) echo " → AUTH ERROR";
    echo "</p>";
    
    if (!$hasError && ($httpCode == 200 || $httpCode == 302)) {
        echo "<pre>Headers:\n" . htmlspecialchars($headers) . "\n\nBody (500):\n" . htmlspecialchars(substr($body, 0, 500)) . "</pre>";
    }
    
    return ['success' => !$hasError && ($hasRedirect || $httpCode == 200), 'code' => $httpCode, 'body' => $body, 'headers' => $headers];
}

// === Descargar JS desde rutas alternativas ===
echo "<h3>0. Buscando archivos JS...</h3>";
$jsPaths = [
    '/rollups/md5.js', '/admin/rollups/md5.js', '/js/md5.js',
    '/php-crypt-md5.js', '/admin/php-crypt-md5.js', '/js/php-crypt-md5.js',
    '/LoginFiles/md5.js', '/LoginFiles/php-crypt-md5.js',
];
foreach ($jsPaths as $p) {
    $ch = curl_init("$baseUrl$p");
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 2]);
    $r = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    if ($c == 200 && strlen($r) > 50) {
        echo "<p class='ok'>✅ $p (" . strlen($r) . " bytes)</p>";
        echo "<pre>" . htmlspecialchars(substr($r, 0, 1000)) . "</pre>";
    }
}

// === Intentos de login ===
echo "<h3>1. Intentos de login al endpoint /boaform/admin/formLogin</h3>";

$loginUrl = "$baseUrl/boaform/admin/formLogin";

// A: Solo username y password plano (raw string)
attempt($loginUrl, "username=$user&password=$pass", $cookieFile, "Plain text password (raw string)");

// B: Solo username y MD5 password (raw string)
attempt($loginUrl, "username=$user&password=" . md5($pass), $cookieFile, "MD5 password (raw string)");

// C: Con Basic Auth y plain password
attempt($loginUrl, "username=$user&password=$pass", $cookieFile, "Plain + Basic Auth", "$user:$pass");

// D: Con Basic Auth y MD5 password
attempt($loginUrl, "username=$user&password=" . md5($pass), $cookieFile, "MD5 + Basic Auth", "$user:$pass");

// E: Solo Basic Auth, sin body
echo "<h3>2. Intentos solo con Basic Auth (GET)</h3>";
$authPages = ['/', '/admin/status.asp', '/status.html', '/index.html', '/admin/index.asp', '/home.html'];
foreach ($authPages as $p) {
    @unlink($cookieFile);
    $ch = curl_init("$baseUrl$p");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 3,
        CURLOPT_USERPWD => "$user:$pass", CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEJAR => $cookieFile, CURLOPT_COOKIEFILE => $cookieFile,
    ]);
    $r = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); $fu = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL); curl_close($ch);
    $isLogin = stripos($r, 'formLogin') !== false;
    if ($c == 200 && !$isLogin && strlen($r) > 100) {
        echo "<p class='ok'>✅ $p → HTTP $c (" . strlen($r) . " bytes) - ¡CONTENIDO INTERNO!</p>";
        echo "<pre>" . htmlspecialchars(substr($r, 0, 2000)) . "</pre>";
    } else {
        echo "<p class='warn'>$p → HTTP $c" . ($isLogin ? ' (redirige al login)' : '') . " final=$fu</p>";
    }
}

// F: Digest Auth
echo "<h3>3. Intentos con Digest Auth</h3>";
foreach (['/', '/admin/status.asp'] as $p) {
    $ch = curl_init("$baseUrl$p");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 3,
        CURLOPT_USERPWD => "$user:$pass", CURLOPT_HTTPAUTH => CURLAUTH_DIGEST,
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    $r = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    $isLogin = stripos($r, 'formLogin') !== false;
    echo "<p>" . ($isLogin ? '↩️' : ($c==200 ? '✅' : '⚠️')) . " Digest $p → HTTP $c" . ($isLogin ? ' (login)' : '') . "</p>";
    if ($c == 200 && !$isLogin && strlen($r) > 200) {
        echo "<pre>" . htmlspecialchars(substr($r, 0, 2000)) . "</pre>";
    }
}

// G: Probar login con credenciales que vimos en el form (marianascsl@gmail.com)
echo "<h3>4. Login con las credenciales visibles en el screenshot</h3>";
// En el screenshot se veia un campo con marianascsl@gmail.com  
attempt($loginUrl, "username=admin&password=" . md5("admin"), $cookieFile, "admin/MD5(admin)");
attempt($loginUrl, "username=admin&password=admin", $cookieFile, "admin/admin plain");

@unlink($cookieFile);
