<?php
/**
 * Clase GoogleDriveHelper
 * 
 * Helper modular y centralizado para gestionar subidas, descargas y eliminación de archivos
 * (imágenes, videos, documentos) en Google Drive utilizando Service Account API v3.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/google_drive.php';

class GoogleDriveHelper {
    private static $service = null;
    private static $folderCache = [];

    /**
     * Obtiene una instancia autenticada del servicio de Google Drive.
     * 
     * @return Google\Service\Drive
     * @throws Exception Si el archivo de credenciales no existe o es inválido.
     */
    public static function getService() {
        if (self::$service !== null) {
            return self::$service;
        }

        $client = new Google\Client();
        $client->setApplicationName(GDRIVE_APPLICATION_NAME);
        $client->setScopes([Google\Service\Drive::DRIVE]);

        $authMode = defined('GDRIVE_AUTH_MODE') ? GDRIVE_AUTH_MODE : 'service_account';

        if ($authMode === 'oauth2' && defined('GDRIVE_CLIENT_ID') && !empty(GDRIVE_CLIENT_ID)) {
            $client->setClientId(GDRIVE_CLIENT_ID);
            $client->setClientSecret(GDRIVE_CLIENT_SECRET);
            $client->fetchAccessTokenWithRefreshToken(GDRIVE_REFRESH_TOKEN);
        } else {
            $credentialsPath = GDRIVE_CREDENTIALS_PATH;
            if (!file_exists($credentialsPath)) {
                throw new Exception("El archivo de credenciales de Google Drive no existe en: {$credentialsPath}");
            }
            $client->setAuthConfig($credentialsPath);
        }

        self::$service = new Google\Service\Drive($client);
        return self::$service;
    }

    /**
     * Obtiene o crea una carpeta en Google Drive por su nombre.
     * 
     * @param string $folderName Nombre de la subcarpeta (ej: 'Soporte', 'Productos', 'Mochila', 'Perfil')
     * @param string|null $parentFolderId ID de la carpeta padre (por defecto usa GDRIVE_ROOT_FOLDER_ID si está definido)
     * @return string ID de la carpeta creada o encontrada
     */
    public static function getOrCreateFolder($folderName, $parentFolderId = null) {
        if (empty($folderName)) {
            return !empty($parentFolderId) ? $parentFolderId : GDRIVE_ROOT_FOLDER_ID;
        }

        $parentId = !empty($parentFolderId) ? $parentFolderId : GDRIVE_ROOT_FOLDER_ID;
        $cacheKey = ($parentId ? $parentId : 'root') . '_' . $folderName;

        if (isset(self::$folderCache[$cacheKey])) {
            return self::$folderCache[$cacheKey];
        }

        $service = self::getService();

        // Buscar carpeta existente
        $query = "mimeType = 'application/vnd.google-apps.folder' and name = '" . addslashes($folderName) . "' and trashed = false";
        if (!empty($parentId)) {
            $query .= " and '" . addslashes($parentId) . "' in parents";
        }

        $results = $service->files->listFiles([
            'q' => $query,
            'spaces' => 'drive',
            'fields' => 'files(id, name)',
            'supportsAllDrives' => true,
            'includeItemsFromAllDrives' => true
        ]);

        if (count($results->getFiles()) > 0) {
            $folderId = $results->getFiles()[0]->getId();
            self::$folderCache[$cacheKey] = $folderId;
            return $folderId;
        }

        // Si no existe, crear la carpeta
        $fileMetadata = new Google\Service\Drive\DriveFile([
            'name' => $folderName,
            'mimeType' => 'application/vnd.google-apps.folder'
        ]);

        if (!empty($parentId)) {
            $fileMetadata->setParents([$parentId]);
        }

        $folder = $service->files->create($fileMetadata, [
            'fields' => 'id',
            'supportsAllDrives' => true
        ]);

        $folderId = $folder->getId();
        self::$folderCache[$cacheKey] = $folderId;

        // Establecer permiso público en la subcarpeta si aplica
        if (GDRIVE_MAKE_PUBLIC_DEFAULT) {
            try {
                $permission = new Google\Service\Drive\Permission([
                    'type' => 'anyone',
                    'role' => 'reader'
                ]);
                $service->permissions->create($folderId, $permission);
            } catch (Exception $e) {
                // Si la carpeta padre ya es pública o falla el permiso, continuar
            }
        }

        return $folderId;
    }

    /**
     * Sube un archivo a Google Drive desde una ruta local.
     * 
     * @param string $localPath Ruta absoluta del archivo local en el servidor
     * @param string|null $customFileName Nombre deseado para el archivo en Google Drive
     * @param string|null $mimeType Tipo MIME del archivo (opcional, se detecta automáticamente)
     * @param string|null $subFolderName Nombre de la subcarpeta donde almacenar (ej: 'Soporte')
     * @param bool|null $makePublic Si es true, otorga permiso de lectura pública por enlace
     * @return array Resumen del resultado con URLs e IDs del archivo
     */
    public static function uploadFile($localPath, $customFileName = null, $mimeType = null, $subFolderName = null, $makePublic = null, $explicitFolderId = null) {
        try {
            if (!file_exists($localPath)) {
                return ['success' => false, 'error' => "El archivo local no existe: {$localPath}"];
            }

            $service = self::getService();
            $fileName = !empty($customFileName) ? $customFileName : basename($localPath);
            $mimeType = !empty($mimeType) ? $mimeType : mime_content_type($localPath);
            if (!$mimeType) {
                $mimeType = 'application/octet-stream';
            }

            $makePublic = ($makePublic !== null) ? $makePublic : GDRIVE_MAKE_PUBLIC_DEFAULT;

            $fileMetadata = new Google\Service\Drive\DriveFile([
                'name' => $fileName
            ]);

            // Determinar carpeta destino
            $targetFolderId = null;
            if (!empty($explicitFolderId)) {
                $targetFolderId = $explicitFolderId;
            } elseif (!empty($subFolderName)) {
                $targetFolderId = self::getOrCreateFolder($subFolderName);
            } elseif (defined('GDRIVE_ROOT_FOLDER_ID') && !empty(GDRIVE_ROOT_FOLDER_ID)) {
                $targetFolderId = GDRIVE_ROOT_FOLDER_ID;
            }

            if (!empty($targetFolderId)) {
                $fileMetadata->setParents([$targetFolderId]);
            }

            $content = file_get_contents($localPath);
            $file = $service->files->create($fileMetadata, [
                'data' => $content,
                'mimeType' => $mimeType,
                'uploadType' => 'multipart',
                'fields' => 'id, name, mimeType, size, webViewLink, webContentLink, thumbnailLink',
                'supportsAllDrives' => true
            ]);

            $fileId = $file->getId();

            // Configurar permisos de lectura pública si está habilitado
            if ($makePublic) {
                try {
                    $permission = new Google\Service\Drive\Permission([
                        'type' => 'anyone',
                        'role' => 'reader'
                    ]);
                    $service->permissions->create($fileId, $permission, [
                        'supportsAllDrives' => true
                    ]);
                } catch (Exception $pe) {
                    // Ignorar si falla la asignación individual si la carpeta ya es pública
                }
            }

            // Generar enlaces directos útiles para incrustar en HTML
            $directLink = "https://lh3.googleusercontent.com/d/{$fileId}";
            $embedLink  = "https://drive.google.com/uc?export=view&id={$fileId}";

            return [
                'success'          => true,
                'file_id'          => $fileId,
                'name'             => $file->getName(),
                'mime_type'        => $file->getMimeType(),
                'size'             => $file->getSize(),
                'web_view_link'    => $file->getWebViewLink(),
                'web_content_link' => $file->getWebContentLink(),
                'thumbnail_link'   => $file->getThumbnailLink(),
                'direct_link'      => $directLink,
                'embed_link'       => $embedLink
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }

    /**
     * Sube un archivo recibido desde un formulario HTML ($_FILES).
     * 
     * @param array $fileArray Elemento de $_FILES (ej. $_FILES['archivo'])
     * @param string|null $subFolderName Nombre de la subcarpeta (ej: 'Documentos')
     * @param bool|null $makePublic Permiso público
     * @return array Resumen con resultado y URLs
     */
    public static function uploadFromUploadedFile($fileArray, $subFolderName = null, $makePublic = null) {
        if (!isset($fileArray['tmp_name']) || empty($fileArray['tmp_name'])) {
            return ['success' => false, 'error' => 'No se recibió ningún archivo válido.'];
        }

        if (isset($fileArray['error']) && $fileArray['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Error al subir el archivo localmente (Código: ' . $fileArray['error'] . ')'];
        }

        $tmpPath  = $fileArray['tmp_name'];
        $fileName = $fileArray['name'];
        $mimeType = isset($fileArray['type']) ? $fileArray['type'] : null;

        return self::uploadFile($tmpPath, $fileName, $mimeType, $subFolderName, $makePublic);
    }

    /**
     * Elimina un archivo o carpeta de Google Drive por su ID.
     * 
     * @param string $fileId ID del archivo o carpeta en Google Drive
     * @return array Status del borrado
     */
    public static function deleteFile($fileId) {
        return self::deleteItem($fileId);
    }

    public static function deleteItem($itemId) {
        try {
            if (empty($itemId)) {
                return ['success' => false, 'error' => 'ID de elemento no proporcionado.'];
            }

            $service = self::getService();
            $service->files->delete($itemId, ['supportsAllDrives' => true]);

            return ['success' => true, 'message' => 'Elemento eliminado correctamente de Google Drive.'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Cambia el nombre de un archivo o carpeta en Google Drive.
     * 
     * @param string $itemId ID del elemento
     * @param string $newName Nuevo nombre
     * @return array Status del renombrado
     */
    public static function renameItem($itemId, $newName) {
        try {
            if (empty($itemId) || empty($newName)) {
                return ['success' => false, 'error' => 'ID y nuevo nombre son requeridos.'];
            }

            $service = self::getService();
            $fileMetadata = new Google\Service\Drive\DriveFile(['name' => trim($newName)]);

            $updatedFile = $service->files->update($itemId, $fileMetadata, [
                'fields' => 'id, name, mimeType',
                'supportsAllDrives' => true
            ]);

            return ['success' => true, 'id' => $updatedFile->getId(), 'name' => $updatedFile->getName()];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Lista el contenido de una carpeta (subcarpetas y archivos) con filtros de tipo y búsqueda.
     * 
     * @param string|null $folderId ID de la carpeta (o null para la carpeta raíz)
     * @param string $typeFilter Filtro por tipo: 'all', 'image', 'video', 'document', 'folder'
     * @param string $searchQuery Término de búsqueda
     * @return array Estructura con items y breadcrumbs
     */
    public static function listFolderContents($folderId = null, $typeFilter = 'all', $searchQuery = '') {
        try {
            $service = self::getService();
            $rootId = defined('GDRIVE_ROOT_FOLDER_ID') && !empty(GDRIVE_ROOT_FOLDER_ID) ? GDRIVE_ROOT_FOLDER_ID : 'root';
            $targetFolderId = !empty($folderId) ? $folderId : $rootId;

            // 1. Obtener detalles de la carpeta actual
            $folderName = 'Google Drive';
            $parentsList = [];
            if ($targetFolderId !== 'root') {
                try {
                    $currFolder = $service->files->get($targetFolderId, [
                        'fields' => 'id, name, parents',
                        'supportsAllDrives' => true
                    ]);
                    $folderName = $currFolder->getName();
                } catch (Exception $e) {
                    $targetFolderId = $rootId;
                }
            }

            // 2. Construir la consulta q
            $queryParts = ["trashed = false"];

            if (!empty(trim($searchQuery))) {
                $escapedQuery = addslashes(trim($searchQuery));
                $queryParts[] = "name contains '{$escapedQuery}'";
            } else {
                $queryParts[] = "'{$targetFolderId}' in parents";
            }

            if ($typeFilter === 'folder') {
                $queryParts[] = "mimeType = 'application/vnd.google-apps.folder'";
            } elseif ($typeFilter === 'image') {
                $queryParts[] = "(mimeType contains 'image/' or mimeType = 'application/vnd.google-apps.folder')";
            } elseif ($typeFilter === 'video') {
                $queryParts[] = "(mimeType contains 'video/' or mimeType = 'application/vnd.google-apps.folder')";
            } elseif ($typeFilter === 'document') {
                $queryParts[] = "(mimeType contains 'pdf' or mimeType contains 'word' or mimeType contains 'document' or mimeType contains 'text' or mimeType contains 'sheet' or mimeType contains 'spreadsheet' or mimeType contains 'presentation' or mimeType = 'application/vnd.google-apps.folder')";
            }

            $queryStr = implode(' and ', $queryParts);

            $response = $service->files->listFiles([
                'q' => $queryStr,
                'pageSize' => 100,
                'orderBy' => 'folder, name asc',
                'fields' => 'files(id, name, mimeType, size, createdTime, modifiedTime, webViewLink, webContentLink, thumbnailLink, iconLink)',
                'supportsAllDrives' => true,
                'includeItemsFromAllDrives' => true
            ]);

            $items = [];
            foreach ($response->getFiles() as $file) {
                $isFolder = ($file->getMimeType() === 'application/vnd.google-apps.folder');
                $fId = $file->getId();

                $item = [
                    'id'               => $fId,
                    'name'             => $file->getName(),
                    'mime_type'        => $file->getMimeType(),
                    'is_folder'        => $isFolder,
                    'size'             => $file->getSize() ? (int)$file->getSize() : 0,
                    'modified_time'    => $file->getModifiedTime(),
                    'web_view_link'    => $file->getWebViewLink(),
                    'web_content_link' => $file->getWebContentLink(),
                    'thumbnail_link'   => $file->getThumbnailLink(),
                    'direct_link'      => "https://lh3.googleusercontent.com/d/{$fId}",
                    'embed_link'       => "https://drive.google.com/uc?export=view&id={$fId}"
                ];

                // Asignar tipo visual
                if ($isFolder) {
                    $item['category'] = 'folder';
                } elseif (strpos($file->getMimeType(), 'image/') === 0) {
                    $item['category'] = 'image';
                } elseif (strpos($file->getMimeType(), 'video/') === 0) {
                    $item['category'] = 'video';
                } else {
                    $item['category'] = 'document';
                }

                $items[] = $item;
            }

            return [
                'success'        => true,
                'current_folder' => [
                    'id'   => $targetFolderId,
                    'name' => $folderName
                ],
                'items'          => $items
            ];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
