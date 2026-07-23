<?php
/**
 * Endpoint AJAX Centralizado para Subir Archivos a Google Drive
 * 
 * Recibe FormData mediante POST con:
 * - `file` o `archivo`: (File) El archivo a subir.
 * - `subfolder` / `folder`: (Opcional) Nombre de la subcarpeta (ej. 'Soporte', 'Productos', 'Perfil').
 * - `public`: (Opcional) "true"/"false" para forzar o quitar visibilidad pública.
 * 
 * Responde JSON con:
 * {
 *   "success": true,
 *   "file_id": "...",
 *   "direct_link": "https://lh3.googleusercontent.com/d/...",
 *   "web_view_link": "...",
 *   "web_content_link": "..."
 * }
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/GoogleDriveHelper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método HTTP no permitido. Usar POST.']);
    exit;
}

$fileKey = null;
if (isset($_FILES['file'])) {
    $fileKey = 'file';
} elseif (isset($_FILES['archivo'])) {
    $fileKey = 'archivo';
} else {
    // Si se enviaron varios archivos o con otra clave, tomar el primero
    foreach ($_FILES as $key => $fileInfo) {
        $fileKey = $key;
        break;
    }
}

if (!$fileKey || empty($_FILES[$fileKey]['tmp_name'])) {
    echo json_encode(['success' => false, 'error' => 'No se ha adjuntado ningún archivo en la solicitud.']);
    exit;
}

$subFolder = !empty($_POST['subfolder']) ? $_POST['subfolder'] : (!empty($_POST['folder']) ? $_POST['folder'] : 'General');
$makePublic = isset($_POST['public']) ? filter_var($_POST['public'], FILTER_VALIDATE_BOOLEAN) : null;

$result = GoogleDriveHelper::uploadFromUploadedFile($_FILES[$fileKey], $subFolder, $makePublic);

echo json_encode($result);
exit;
