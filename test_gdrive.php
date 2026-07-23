<?php
/**
 * Test de Conexión y Subida a Google Drive API
 */

require_once __DIR__ . '/includes/GoogleDriveHelper.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h2>Prueba de Integración: Google Drive API</h2>";

try {
    echo "<p>1. Verificando inicialización de GoogleDriveHelper...</p>";
    $service = GoogleDriveHelper::getService();
    echo "<p style='color:green;'>✓ Autenticación exitosa con Service Account.</p>";

    echo "<p>1.1. Verificando acceso a la carpeta raíz (" . GDRIVE_ROOT_FOLDER_ID . ")...</p>";
    try {
        $rootFolder = $service->files->get(GDRIVE_ROOT_FOLDER_ID, ['fields' => 'id, name, permissions', 'supportsAllDrives' => true]);
        echo "<p style='color:green;'>✓ Acceso confirmado a la carpeta: '<strong>" . htmlspecialchars($rootFolder->getName()) . "</strong>'</p>";
    } catch (Exception $fe) {
        echo "<p style='color:red;'>✗ No se pudo acceder a la carpeta raíz: " . htmlspecialchars($fe->getMessage()) . "</p>";
        echo "<p style='color:orange;'>Asegúrate de compartir la carpeta en Google Drive con el correo:<br><code>sistematurbo@hazel-logic-477416-m7.iam.gserviceaccount.com</code> como <strong>Editor</strong>.</p>";
    }

    echo "<p>2. Creando archivo temporal de prueba...</p>";
    $tmpFile = tempnam(sys_get_temp_dir(), 'gdrive_test_');
    $testContent = "Prueba de subida desde TurboSaaS - " . date('Y-m-d H:i:s');
    file_put_contents($tmpFile, $testContent);

    echo "<p>3. Subiendo archivo a Google Drive (Subcarpeta: 'Pruebas_System')...</p>";
    $uploadResult = GoogleDriveHelper::uploadFile($tmpFile, 'test_turbosaas_' . time() . '.txt', 'text/plain', 'Pruebas_System', true);

    @unlink($tmpFile);

    if ($uploadResult['success']) {
        echo "<h3 style='color:green;'>✓ ¡Subida a Google Drive Exitosa!</h3>";
        echo "<ul>";
        echo "<li><strong>ID del Archivo:</strong> " . htmlspecialchars($uploadResult['file_id']) . "</li>";
        echo "<li><strong>Nombre:</strong> " . htmlspecialchars($uploadResult['name']) . "</li>";
        echo "<li><strong>URL de Visualización en Drive:</strong> <a href='" . htmlspecialchars($uploadResult['web_view_link']) . "' target='_blank'>Ver en Drive</a></li>";
        echo "<li><strong>Enlace Directo / Embed:</strong> <a href='" . htmlspecialchars($uploadResult['direct_link']) . "' target='_blank'>Abrir Enlace Directo</a></li>";
        echo "</ul>";
    } else {
        echo "<h3 style='color:red;'>✗ Error en la subida:</h3>";
        echo "<pre style='color:red; background:#f4f4f4; padding:10px;'>" . htmlspecialchars($uploadResult['error']) . "</pre>";
    }

} catch (Exception $e) {
    echo "<h3 style='color:red;'>✗ Excepción capturada:</h3>";
    echo "<pre style='color:red; background:#f4f4f4; padding:10px;'>" . htmlspecialchars($e->getMessage()) . "</pre>";
}
