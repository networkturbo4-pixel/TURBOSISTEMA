<?php
require_once '../../../config/db.php';
requireLogin();
requirePermission($pdo, 'sistema');

$type = $_GET['type'] ?? 'db';

// Intentar encontrar mysqldump
$mysqldumpPath = 'mysqldump';
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    if (file_exists('C:\\xampp\\mysql\\bin\\mysqldump.exe')) {
        $mysqldumpPath = '"C:\\xampp\\mysql\\bin\\mysqldump.exe"';
    }
}

$hostEscaped = escapeshellarg($host);
$userEscaped = escapeshellarg($user);
$passEscaped = !empty($pass) ? '--password=' . escapeshellarg($pass) : '';
$dbEscaped = escapeshellarg($dbname);
$command = "$mysqldumpPath --host=$hostEscaped --user=$userEscaped $passEscaped $dbEscaped";

if ($type === 'full') {
    $filename = 'backup_full_' . $dbname . '_' . date('Y-m-d_H-i-s') . '.zip';
    
    // Create temp SQL file
    $tempSqlFile = tempnam(sys_get_temp_dir(), 'sql');
    exec($command . ' > ' . escapeshellarg($tempSqlFile));
    
    $zipFile = tempnam(sys_get_temp_dir(), 'zip');
    $zip = new ZipArchive();
    if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        // Add SQL file
        $zip->addFile($tempSqlFile, 'database.sql');
        
        // Add uploads directory
        $uploadsDir = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'uploads';
        if (is_dir($uploadsDir)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($uploadsDir),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
            
            foreach ($files as $name => $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = 'uploads/' . substr($filePath, strlen($uploadsDir) + 1);
                    $zip->addFile($filePath, $relativePath);
                }
            }
        }
        $zip->close();
    }
    
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($zipFile));
    header('Pragma: no-cache');
    header('Expires: 0');
    
    readfile($zipFile);
    
    unlink($tempSqlFile);
    unlink($zipFile);
    exit;

} else {
    $filename = 'backup_db_' . $dbname . '_' . date('Y-m-d_H-i-s') . '.sql';
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    passthru($command, $result_code);

    if ($result_code !== 0) {
        echo "\n-- Error al generar el backup.\n";
        echo "-- Código de resultado: $result_code\n";
    }
    exit;
}
