<?php
/**
 * TurboSaaS - Actualizador Inteligente (Git CLI & GitHub Cloud Sync)
 */
require_once '../../../config/db.php';
requireLogin();
requirePermission($pdo, 'sistema');

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

// Configurar límite de tiempo para descargas y extracciones
@set_time_limit(300);
@ini_set('memory_limit', '256M');

$project_root = str_replace('\\', '/', dirname(__DIR__, 3));
$git_dir = $project_root . '/.git';
$github_repo = 'networkturbo4-pixel/TURBOSISTEMA';
$github_branch = 'main';

// ── Método Auxiliar para Descargar Archivos ──
function downloadFile($url, $destination) {
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'TurboSaaS-Updater');
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        $data = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($data !== false && ($httpCode === 200 || $httpCode === 302)) {
            return file_put_contents($destination, $data) !== false;
        }
    }

    // Fallback con stream context
    $opts = [
        'http' => [
            'method' => 'GET',
            'header' => "User-Agent: TurboSaaS-Updater\r\n",
            'timeout' => 120,
            'follow_location' => 1
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ];
    $context = stream_context_create($opts);
    $data = @file_get_contents($url, false, $context);
    if ($data !== false) {
        return file_put_contents($destination, $data) !== false;
    }
    return false;
}

// ── Método Auxiliar para Consultar API de GitHub ──
function fetchGitHubCommitInfo($repo, $branch) {
    $url = "https://api.github.com/repos/{$repo}/commits/{$branch}";
    $data = false;
    
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'TurboSaaS-Updater');
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $res = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($res !== false && $httpCode === 200) {
            $data = $res;
        }
    }
    
    if ($data === false) {
        $opts = [
            'http' => ['method' => 'GET', 'header' => "User-Agent: TurboSaaS-Updater\r\n", 'timeout' => 15],
            'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
        ];
        $data = @file_get_contents($url, false, stream_context_create($opts));
    }

    if ($data) {
        $json = json_decode($data, true);
        if ($json && isset($json['sha'])) {
            return [
                'sha' => substr($json['sha'], 0, 8),
                'message' => $json['commit']['message'] ?? 'Actualización desde GitHub',
                'author' => $json['commit']['author']['name'] ?? 'GitHub',
                'date' => isset($json['commit']['author']['date']) ? date('d/m/Y H:i', strtotime($json['commit']['author']['date'])) : date('d/m/Y H:i')
            ];
        }
    }
    return null;
}

// ── Función Recursiva de Copia Segura de Archivos ──
function copyDirectorySafe($src, $dst, &$log = []) {
    $dir = opendir($src);
    if (!$dir) return false;
    @mkdir($dst, 0777, true);

    while (false !== ($file = readdir($dir))) {
        if ($file === '.' || $file === '..') continue;

        $srcPath = $src . '/' . $file;
        $dstPath = $dst . '/' . $file;

        // 🛡️ ARCHIVOS PROTEGIDOS QUE NUNCA DEBEN SOBREESCRIBIRSE
        if ($file === 'env.php' && strpos($dstPath, 'config/env.php') !== false && file_exists($dstPath)) {
            $log[] = "[PRESERVADO] config/env.php (Credenciales de producción protegidas)";
            continue;
        }
        if ($file === '.env' && file_exists($dstPath)) {
            $log[] = "[PRESERVADO] .env";
            continue;
        }
        if ($file === 'uploads' && is_dir($dstPath)) {
            $log[] = "[PRESERVADO] Directorio /uploads (Multimedia de clientes protegido)";
            continue;
        }
        if ($file === '.git') {
            continue;
        }

        if (is_dir($srcPath)) {
            copyDirectorySafe($srcPath, $dstPath, $log);
        } else {
            if (@copy($srcPath, $dstPath)) {
                $log[] = "[ACTUALIZADO] " . substr($dstPath, strlen(dirname(__DIR__, 3)) + 1);
            }
        }
    }
    closedir($dir);
    return true;
}

function recursiveDeleteDir($dirPath) {
    if (!is_dir($dirPath)) return;
    $files = glob($dirPath . '/*', GLOB_MARK);
    foreach ($files as $file) {
        if (is_dir($file)) {
            recursiveDeleteDir($file);
        } else {
            @unlink($file);
        }
    }
    @rmdir($dirPath);
}

// =========================================================================
// 1. INTENTAR ACTUALIZACIÓN CON GIT CLI (Si el directorio .git existe)
// =========================================================================
$can_use_exec = function_exists('exec') && !in_array('exec', array_map('trim', explode(',', (string)ini_get('disable_functions'))));

if (is_dir($git_dir) && $can_use_exec) {
    try {
        putenv('GIT_DISCOVERY_ACROSS_FILESYSTEM=1');
        putenv('GIT_DIR=' . $git_dir);
        putenv('GIT_WORK_TREE=' . $project_root);

        $git_cmd = 'git --git-dir=' . escapeshellarg($git_dir) . ' --work-tree=' . escapeshellarg($project_root) . ' -c safe.directory="*"';
        
        $current_branch = trim((string)shell_exec($git_cmd . ' rev-parse --abbrev-ref HEAD 2>&1'));
        if (empty($current_branch) || stripos($current_branch, 'fatal:') !== false) {
            $current_branch = $github_branch;
        }

        $output = [];
        $return_var = 0;
        
        exec($git_cmd . ' fetch origin 2>&1', $output, $return_var);

        $pull_output = [];
        $pull_return = 0;
        exec($git_cmd . ' pull origin ' . escapeshellarg($current_branch) . ' 2>&1', $pull_output, $pull_return);
        
        // Si hay conflictos o cambios locales, forzar reset hard
        if ($pull_return !== 0) {
            $reset_output = [];
            $reset_return = 0;
            exec($git_cmd . ' reset --hard origin/' . escapeshellarg($current_branch) . ' 2>&1', $reset_output, $reset_return);
            
            if ($reset_return === 0) {
                $output = array_merge($output, $reset_output);
                $return_var = 0;
            } else {
                // Probar con rama main
                exec($git_cmd . ' reset --hard origin/' . $github_branch . ' 2>&1', $reset_output, $reset_return);
                $output = array_merge($output, $reset_output);
                $return_var = $reset_return;
            }
        } else {
            $output = array_merge($output, $pull_output);
            $return_var = 0;
        }

        if ($return_var === 0) {
            echo json_encode([
                'success' => true,
                'message' => 'El sistema se ha sincronizado correctamente vía Git CLI.',
                'output' => implode("\n", $output)
            ]);
            exit;
        }
    } catch (Exception $e) {
        // Si falla Git CLI, proceder con el motor de Cloud Sync
    }
}

// =========================================================================
// 2. MOTOR UNIVERSAL: ACTUALIZACIÓN DIRECTA VÍA GITHUB CLOUD (ZIP)
// =========================================================================
try {
    if (!class_exists('ZipArchive')) {
        echo json_encode([
            'success' => false,
            'message' => 'La extensión PHP ZipArchive es requerida para la actualización directa. Por favor habilítala en tu cPanel / php.ini.'
        ]);
        exit;
    }

    $tempZip = sys_get_temp_dir() . '/turbosaas_update_' . time() . '.zip';
    $extractDir = sys_get_temp_dir() . '/turbosaas_extract_' . time();
    $zipUrl = "https://github.com/{$github_repo}/archive/refs/heads/{$github_branch}.zip";

    // 1. Descargar ZIP del repositorio
    $downloaded = downloadFile($zipUrl, $tempZip);
    if (!$downloaded || !file_exists($tempZip) || filesize($tempZip) < 1000) {
        echo json_encode([
            'success' => false,
            'message' => 'No se pudo descargar el paquete de actualización desde GitHub. Verifica tu conexión a internet o los permisos salientes de cURL.'
        ]);
        @unlink($tempZip);
        exit;
    }

    // 2. Extraer archivo ZIP
    $zip = new ZipArchive();
    if ($zip->open($tempZip) !== true) {
        @unlink($tempZip);
        echo json_encode([
            'success' => false,
            'message' => 'Error al abrir el paquete de actualización ZIP descargado.'
        ]);
        exit;
    }

    @mkdir($extractDir, 0777, true);
    $zip->extractTo($extractDir);
    $zip->close();
    @unlink($tempZip);

    // Encontrar la carpeta interna (ej: TURBOSISTEMA-main)
    $extractedFolders = glob($extractDir . '/*', GLOB_ONLYDIR);
    $sourceDir = (!empty($extractedFolders) && is_dir($extractedFolders[0])) ? $extractedFolders[0] : $extractDir;

    // 3. Copiar archivos actualizados de forma segura
    $updateLog = [];
    $updateLog[] = "[INICIO] Sincronización Directa con GitHub ({$github_repo})";
    $updateLog[] = "[INFO] Descargando rama {$github_branch}...";

    copyDirectorySafe($sourceDir, $project_root, $updateLog);

    // 4. Obtener datos del último commit para registrar la versión
    $commitInfo = fetchGitHubCommitInfo($github_repo, $github_branch);
    $versionData = [
        'branch' => $github_branch,
        'commit_hash' => $commitInfo['sha'] ?? 'latest',
        'commit_msg' => $commitInfo['message'] ?? 'Actualización directa desde GitHub Cloud',
        'commit_author' => $commitInfo['author'] ?? 'GitHub Sync',
        'commit_date' => $commitInfo['date'] ?? date('d/m/Y H:i'),
        'updated_at' => date('d/m/Y H:i:s'),
        'method' => 'github_direct_sync'
    ];

    @file_put_contents($project_root . '/config/git_version.json', json_encode($versionData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    // Limpiar directorio temporal
    recursiveDeleteDir($extractDir);

    $updateLog[] = "[OK] Archivos aplicados correctamente.";
    $updateLog[] = "[VERSION] Commit activo: " . $versionData['commit_hash'] . " - " . $versionData['commit_msg'];

    echo json_encode([
        'success' => true,
        'message' => '¡Sistema actualizado exitosamente desde GitHub Cloud Sync!',
        'output' => implode("\n", $updateLog)
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error inesperado durante la actualización: ' . $e->getMessage()
    ]);
}
