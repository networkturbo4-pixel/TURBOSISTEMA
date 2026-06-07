<?php
/**
 * BDCOM GP1704 - Login con PHP crypt MD5
 */
$routerIp = '192.168.123.1';
$user = 'user';
$pass = '123456';
$baseUrl = "http://$routerIp";

echo "<h2>BDCOM - Login con crypt-MD5</h2>";
echo "<style>body{font-family:monospace;background:#1a1a2e;color:#e0e0e0;padding:20px;} 
pre{background:#16213e;padding:15px;border-radius:8px;overflow-x:auto;border:1px solid #0f3460;max-height:500px;overflow-y:auto;white-space:pre-wrap;} 
h3{color:#e94560;} .ok{color:#4ecca3;} .err{color:#e94560;}</style>";

$cookieFile = sys_get_temp_dir() . '/bdcom_crypt.txt';

function tryLogin($baseUrl, $user, $passValue, $desc, $cookieFile) {
    @unlink($cookieFile);
    
    // GET login page first for session cookie
    $ch = curl_init("$baseUrl/admin/login.asp");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 3,
        CURLOPT_COOKIEJAR => $cookieFile, CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_USERAGENT => 'Mozilla/5.0',
    ]);
    curl_exec($ch); curl_close($ch);
    
    // POST login
    $ch = curl_init("$baseUrl/boaform/admin/formLogin");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => "username=$user&password=" . urlencode($passValue),
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HEADER => true,
        CURLOPT_COOKIEJAR => $cookieFile, CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_USERAGENT => 'Mozilla/5.0',
        CURLOPT_REFERER => "$baseUrl/admin/login.asp",
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    $hasError = stripos($body, 'ERROR') !== false;
    $hasRedirect = preg_match('/Location:\s*(.+)/i', $headers, $loc);
    
    $icon = $hasError ? '❌' : ($hasRedirect ? '🎉' : '⚠️');
    $class = $hasError ? 'err' : 'ok';
    
    echo "<p class='$class'>$icon <b>$desc</b></p>";
    echo "<p>  Password sent: <code>" . htmlspecialchars(substr($passValue, 0, 80)) . "</code></p>";
    echo "<p>  HTTP $httpCode" . ($hasRedirect ? " → " . trim($loc[1]) : '') . ($hasError ? " → AUTH ERROR" : '') . "</p>";
    
    if (!$hasError) {
        echo "<pre>" . htmlspecialchars(substr($body, 0, 500)) . "</pre>";
        
        // Try accessing internal pages
        $testPages = ['/status.html', '/admin/status.asp', '/wlbasic.html', '/admin/wlbasic.asp'];
        foreach ($testPages as $p) {
            $ch = curl_init("$baseUrl$p");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 3,
                CURLOPT_COOKIEJAR => $cookieFile, CURLOPT_COOKIEFILE => $cookieFile,
            ]);
            $r = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
            $isLogin = stripos($r, 'formLogin') !== false;
            if ($c == 200 && !$isLogin && strlen($r) > 100) {
                echo "<p class='ok'>  ✅ $p accesible!</p>";
                echo "<pre>" . htmlspecialchars(substr($r, 0, 1500)) . "</pre>";
            }
        }
    }
    
    return !$hasError;
}

// === Generar diferentes formatos de hash ===
echo "<h3>Probando múltiples formatos de contraseña...</h3>";

// 1. Plain text
tryLogin($baseUrl, $user, $pass, "1. Texto plano", $cookieFile);

// 2. Simple MD5
tryLogin($baseUrl, $user, md5($pass), "2. MD5 simple", $cookieFile);

// 3. PHP crypt MD5 with empty salt
$crypt1 = crypt($pass, '$1$$');
tryLogin($baseUrl, $user, $crypt1, "3. crypt(\$pass, '\$1\$\$')", $cookieFile);

// 4. PHP crypt MD5 with various salts
$salts = ['', 'user', 'admin', '12345678', 'bdcom', 'gpon'];
foreach ($salts as $i => $salt) {
    $crypted = crypt($pass, '$1$' . $salt . '$');
    tryLogin($baseUrl, $user, $crypted, "4.$i. crypt with salt='$salt'", $cookieFile);
}

// 5. MD5 of username+password
tryLogin($baseUrl, $user, md5($user . $pass), "5. MD5(user+pass)", $cookieFile);

// 6. MD5 of password+username
tryLogin($baseUrl, $user, md5($pass . $user), "6. MD5(pass+user)", $cookieFile);

// 7. SHA256
tryLogin($baseUrl, $user, hash('sha256', $pass), "7. SHA256", $cookieFile);

// 8. Base64
tryLogin($baseUrl, $user, base64_encode($pass), "8. Base64", $cookieFile);

// 9. Double MD5
tryLogin($baseUrl, $user, md5(md5($pass)), "9. MD5(MD5(pass))", $cookieFile);

// 10. Maybe the user changed password - try common ones
echo "<h3>Probando otras credenciales comunes...</h3>";
$commonUsers = [
    ['user', '123456'], ['admin', 'admin'], ['admin', '123456'], 
    ['admin', 'admin123'], ['root', 'root'], ['user', 'user'],
    ['admin', 'password'], ['admin', ''], ['user', ''],
];
foreach ($commonUsers as $cred) {
    tryLogin($baseUrl, $cred[0], $cred[1], "Login: {$cred[0]}/{$cred[1]}", $cookieFile);
}

@unlink($cookieFile);
