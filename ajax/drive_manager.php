<?php
/**
 * AJAX Controller para el Gestor Global de Google Drive
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/GoogleDriveHelper.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            $folder_id = $_POST['folder_id'] ?? $_GET['folder_id'] ?? null;
            $filter    = $_POST['filter'] ?? $_GET['filter'] ?? 'all';
            $query     = $_POST['q'] ?? $_GET['q'] ?? '';

            $result = GoogleDriveHelper::listFolderContents($folder_id, $filter, $query);
            echo json_encode($result);
            break;

        case 'create_folder':
            $folder_name = trim($_POST['name'] ?? '');
            $parent_id   = $_POST['parent_id'] ?? null;

            if (empty($folder_name)) {
                echo json_encode(['success' => false, 'error' => 'Nombre de carpeta no válido']);
                exit;
            }

            $folderId = GoogleDriveHelper::getOrCreateFolder($folder_name, $parent_id);
            echo json_encode(['success' => true, 'id' => $folderId, 'name' => $folder_name]);
            break;

        case 'rename':
            $item_id  = $_POST['item_id'] ?? '';
            $new_name = trim($_POST['name'] ?? '');

            $result = GoogleDriveHelper::renameItem($item_id, $new_name);
            echo json_encode($result);
            break;

        case 'delete':
            $item_id = $_POST['item_id'] ?? '';

            $result = GoogleDriveHelper::deleteItem($item_id);
            echo json_encode($result);
            break;

        case 'upload':
            if (empty($_FILES['file']['tmp_name'])) {
                echo json_encode(['success' => false, 'error' => 'No se recibió ningún archivo']);
                exit;
            }

            $parent_id = $_POST['folder_id'] ?? null;
            $tmpPath   = $_FILES['file']['tmp_name'];
            $fileName  = $_FILES['file']['name'];
            $mimeType  = $_FILES['file']['type'] ?? null;

            $result = GoogleDriveHelper::uploadFile($tmpPath, $fileName, $mimeType, $parent_id, true);
            echo json_encode($result);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Acción no reconocida']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
exit;
