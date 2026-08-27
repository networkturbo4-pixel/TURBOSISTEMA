<?php
require_once '../../../config/db.php';
requireLogin();
requirePermission($pdo, 'sistema');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['backup_file'])) {
    echo json_encode(['success' => false, 'message' => 'Solicitud inválida.']);
    exit;
}

$file = $_FILES['backup_file'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Error al subir el archivo. Código: ' . $file['error']]);
    exit;
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if ($ext !== 'sql' && $ext !== 'zip') {
    echo json_encode(['success' => false, 'message' => 'Formato no soportado. Solo se admiten archivos .sql o .zip']);
    exit;
}

// Intentar encontrar mysql client
$mysqlPath = 'mysql';
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    if (file_exists('C:\\xampp\\mysql\\bin\\mysql.exe')) {
        $mysqlPath = '"C:\\xampp\\mysql\\bin\\mysql.exe"';
    }
}

$hostEscaped = escapeshellarg($host);
$userEscaped = escapeshellarg($user);
$passEscaped = !empty($pass) ? '--password=' . escapeshellarg($pass) : '';
$dbEscaped = escapeshellarg($dbname);

$successMessage = '';

if ($ext === 'sql') {
    $sqlFile = $file['tmp_name'];
    $command = "$mysqlPath --host=$hostEscaped --user=$userEscaped $passEscaped $dbEscaped < " . escapeshellarg($sqlFile);
    
    exec($command, $output, $return_var);
    
    if ($return_var !== 0) {
        echo json_encode(['success' => false, 'message' => 'Error al restaurar la base de datos. Código: ' . $return_var]);
        exit;
    }
    
    $successMessage = 'Base de datos restaurada correctamente.';
} else if ($ext === 'zip') {
    $zip = new ZipArchive;
    if ($zip->open($file['tmp_name']) === TRUE) {
        $tempExtractDir = sys_get_temp_dir() . '/restore_' . time();
        mkdir($tempExtractDir);
        $zip->extractTo($tempExtractDir);
        $zip->close();
        
        $errors = [];
        
        // 1. Restaurar BD
        if (file_exists($tempExtractDir . '/database.sql')) {
            $sqlFile = $tempExtractDir . '/database.sql';
            $command = "$mysqlPath --host=$hostEscaped --user=$userEscaped $passEscaped $dbEscaped < " . escapeshellarg($sqlFile);
            exec($command, $output, $return_var);
            if ($return_var !== 0) {
                $errors[] = 'Fallo al restaurar la base de datos.';
            }
        } else {
            $errors[] = 'El archivo zip no contiene database.sql.';
        }
        
        // 2. Restaurar uploads
        if (is_dir($tempExtractDir . '/uploads')) {
            $targetUploadsDir = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'uploads';
            if (is_dir($targetUploadsDir) || @mkdir($targetUploadsDir, 0777, true)) {
                // Función recursiva para copiar archivos
                function copyDir($src, $dst) {
                    $dir = opendir($src);
                    @mkdir($dst);
                    while(false !== ( $file = readdir($dir)) ) {
                        if (( $file != '.' ) && ( $file != '..' )) {
                            if ( is_dir($src . '/' . $file) ) {
                                copyDir($src . '/' . $file, $dst . '/' . $file);
                            } else {
                                copy($src . '/' . $file, $dst . '/' . $file);
                            }
                        }
                    }
                    closedir($dir);
                }
                copyDir($tempExtractDir . '/uploads', $targetUploadsDir);
            }
        }
        
        // Limpiar temp dir
        function deleteDir($dirPath) {
            if (! is_dir($dirPath)) {
                throw new InvalidArgumentException("$dirPath must be a directory");
            }
            if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
                $dirPath .= '/';
            }
            $files = glob($dirPath . '*', GLOB_MARK);
            foreach ($files as $file) {
                if (is_dir($file)) {
                    deleteDir($file);
                } else {
                    unlink($file);
                }
            }
            rmdir($dirPath);
        }
        try {
            deleteDir($tempExtractDir);
        } catch (Exception $e) {}
        
        if (count($errors) > 0) {
            echo json_encode(['success' => false, 'message' => 'Restauración con errores: ' . implode(' ', $errors)]);
            exit;
        }
        
        $successMessage = 'Base de datos y archivos (uploads) restaurados correctamente.';
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo abrir el archivo ZIP.']);
        exit;
    }
}

echo json_encode(['success' => true, 'message' => $successMessage]);
