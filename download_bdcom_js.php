<?php
/**
 * Descargar common.js completo y buscar la función de encriptación de password
 */
$routerIp = '192.168.123.1';
$baseUrl = "http://$routerIp";

// Descargar common.js
$ch = curl_init("$baseUrl/common.js");
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5]);
$commonJs = curl_exec($ch);
curl_close($ch);

// Guardar en archivo local
file_put_contents(__DIR__ . '/bdcom_common.js', $commonJs);

// Descargar php-crypt-md5.js
$ch = curl_init("$baseUrl/admin/php-crypt-md5.js");
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5]);
$cryptJs = curl_exec($ch);
curl_close($ch);
file_put_contents(__DIR__ . '/bdcom_crypt_md5.js', $cryptJs);

// Descargar md5.js
$ch = curl_init("$baseUrl/admin/rollups/md5.js");
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5]);
$md5Js = curl_exec($ch);
curl_close($ch);
file_put_contents(__DIR__ . '/bdcom_md5.js', $md5Js);

// Descargar login.asp completo
$ch = curl_init("$baseUrl/admin/login.asp");
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5]);
$loginHtml = curl_exec($ch);
curl_close($ch);
file_put_contents(__DIR__ . '/bdcom_login.html', $loginHtml);

echo "Archivos guardados:\n";
echo "- bdcom_common.js: " . strlen($commonJs) . " bytes\n";
echo "- bdcom_crypt_md5.js: " . strlen($cryptJs) . " bytes\n";
echo "- bdcom_md5.js: " . strlen($md5Js) . " bytes\n";
echo "- bdcom_login.html: " . strlen($loginHtml) . " bytes\n";
