<?php
require_once '../config/db.php';
header('Content-Type: application/json');

if (!isset($_GET['doc']) || empty($_GET['doc'])) {
    echo json_encode(['success' => false, 'message' => 'Documento requerido.']);
    exit;
}

$doc = preg_replace('/[^0-9]/', '', $_GET['doc']);
$length = strlen($doc);

if ($length === 8) {
    $url = "https://api.json.pe/api/dni";
    $postData = json_encode(['dni' => $doc]);
} elseif ($length === 11) {
    $url = "https://api.json.pe/api/ruc";
    $postData = json_encode(['ruc' => $doc]);
} else {
    echo json_encode(['success' => false, 'message' => 'El documento debe tener 8 (DNI) u 11 (RUC) dígitos.']);
    exit;
}

if (!defined('JSON_PE_TOKEN') || JSON_PE_TOKEN === 'AQUI_PEGAR_TU_TOKEN') {
    echo json_encode(['success' => false, 'message' => 'Falta configurar el Token de json.pe en config/db.php']);
    exit;
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . JSON_PE_TOKEN,
    'Content-Type: application/json',
    'Accept: application/json'
]);

// Para evitar problemas de SSL en entornos de desarrollo local (XAMPP)
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión con la API: ' . $error]);
    exit;
}

if ($http_code === 200) {
    $data = json_decode($response, true);
    if (isset($data['success']) && $data['success'] === false) {
        echo json_encode(['success' => false, 'message' => $data['message'] ?? 'No se encontraron resultados.']);
    } else {
        echo json_encode(['success' => true, 'data' => $data, 'type' => ($length === 8 ? 'DNI' : 'RUC')]);
    }
} elseif ($http_code === 401) {
    echo json_encode(['success' => false, 'message' => 'Token inválido o expirado. Verifica JSON_PE_TOKEN en config/db.php']);
} elseif ($http_code === 404) {
    echo json_encode(['success' => false, 'message' => 'Documento no encontrado en la base de datos de json.pe.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al consultar la API. Código HTTP: ' . $http_code]);
}
?>
