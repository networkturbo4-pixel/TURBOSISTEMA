<?php
/**
 * Script de exploración de la interfaz web del BDCOM GP1704-4GC-22A
 * Conecta al router para descubrir los endpoints de su API/web interface
 */

$routerIp = '192.168.123.1';
$user = 'user';
$pass = '123456';
$baseUrl = "http://$routerIp";

echo "<h2>Exploración BDCOM GP1704-4GC-22A ($routerIp)</h2>";
echo "<style>body{font-family:monospace;background:#1a1a2e;color:#e0e0e0;padding:20px;} 
pre{background:#16213e;padding:15px;border-radius:8px;overflow-x:auto;border:1px solid #0f3460;} 
h3{color:#e94560;} .ok{color:#4ecca3;} .err{color:#e94560;}</style>";

// === 1. Test de conectividad ===
echo "<h3>1. Test de conectividad</h3>";
$ch = curl_init($baseUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 5,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HEADER => true,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "<p class='err'>❌ Error de conexión: $error</p>";
    die();
}
echo "<p class='ok'>✅ Conectado - HTTP $httpCode</p>";

// Separar headers y body
$headerSize = strpos($response, "\r\n\r\n");
$headers = substr($response, 0, $headerSize);
$body = substr($response, $headerSize + 4);

echo "<h3>2. Headers de respuesta</h3>";
echo "<pre>" . htmlspecialchars($headers) . "</pre>";

echo "<h3>3. Contenido de la página principal (primeros 3000 chars)</h3>";
echo "<pre>" . htmlspecialchars(substr($body, 0, 3000)) . "</pre>";

// === 2. Intentar login con diferentes métodos ===
echo "<h3>4. Intentando login...</h3>";

// Método A: Basic Auth
echo "<p>Método A: HTTP Basic Auth...</p>";
$ch = curl_init($baseUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 5,
    CURLOPT_USERPWD => "$user:$pass",
    CURLOPT_HTTPAUTH => CURLAUTH_BASIC | CURLAUTH_DIGEST,
    CURLOPT_HEADER => true,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$headerSize = strpos($response, "\r\n\r\n");
$bodyA = substr($response, $headerSize + 4);
echo "<p>HTTP $httpCode - Body length: " . strlen($bodyA) . "</p>";

// Método B: POST form login
echo "<p>Método B: POST form login...</p>";

// Probar varios endpoints comunes de login en ONUs BDCOM
$loginEndpoints = [
    '/boaform/admin/formLogin',
    '/login.cgi',
    '/cgi-bin/login.cgi',
    '/boaform/formLogin',
    '/goform/formLogin',
    '/GponForm/LoginForm',
    '/cgi-bin/weblogin.cgi',
];

$loginPayloads = [
    ['username' => $user, 'password' => $pass],
    ['Username' => $user, 'Password' => $pass],
    ['loginUsername' => $user, 'loginPassword' => $pass],
    ['user' => $user, 'pass' => $pass],
];

foreach ($loginEndpoints as $endpoint) {
    foreach ($loginPayloads as $payload) {
        $ch = curl_init($baseUrl . $endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 3,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($payload),
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HEADER => true,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if (!$error && $httpCode !== 404 && $httpCode !== 0) {
            echo "<p class='ok'>→ $endpoint (HTTP $httpCode) con " . json_encode($payload) . "</p>";
            $headerSize = strpos($response, "\r\n\r\n");
            $respHeaders = substr($response, 0, $headerSize);
            $respBody = substr($response, $headerSize + 4);
            echo "<pre>Headers:\n" . htmlspecialchars($respHeaders) . "\n\nBody (500 chars):\n" . htmlspecialchars(substr($respBody, 0, 500)) . "</pre>";
        }
    }
}

// === 3. Explorar URLs comunes de ONUs BDCOM ===
echo "<h3>5. Explorando URLs conocidas del BDCOM...</h3>";
$urls = [
    '/',
    '/index.html',
    '/index.asp',
    '/login.html',
    '/login.asp',
    '/status.html',
    '/wlbasic.html',
    '/wlsecurity.html',
    '/dhcpclienttbl.html',
    '/arpTable.html',
    '/wlstationlist.html',
    '/admin/status.asp',
    '/cgi-bin/net-scan.asp',
    '/boaform/formWlanSetup',
    '/info.html',
    '/devinfo.html',
];

foreach ($urls as $url) {
    $ch = curl_init($baseUrl . $url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 2,
        CURLOPT_USERPWD => "$user:$pass",
        CURLOPT_HEADER => false,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentLength = strlen($response ?: '');
    curl_close($ch);

    if ($httpCode === 200 && $contentLength > 50) {
        echo "<p class='ok'>✅ $url (HTTP $httpCode, $contentLength bytes)</p>";
        // Mostrar extracto si parece interesante
        if ($contentLength < 5000) {
            echo "<pre>" . htmlspecialchars(substr($response, 0, 1500)) . "</pre>";
        } else {
            echo "<pre>" . htmlspecialchars(substr($response, 0, 800)) . "\n...\n(truncado, $contentLength bytes total)</pre>";
        }
    } elseif ($httpCode !== 404 && $httpCode !== 0) {
        echo "<p>⚠️ $url → HTTP $httpCode ($contentLength bytes)</p>";
    }
}
