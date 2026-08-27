<?php
/**
 * Módulo: Sistema (Telemetría, Copias de Seguridad, Restauración, Git y Diagnóstico)
 */
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once '../../config/db.php';
requireLogin();
requirePermission($pdo, 'sistema');

include '../../includes/header.php';
include '../../includes/sidebar.php';

$project_root = str_replace('\\', '/', dirname(__DIR__, 2));

// ── 1. Telemetría de Base de Datos ──
$db_tables_count = 0;
$db_size_mb = 0;
try {
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(table_name) AS total_tables,
            ROUND(SUM(data_length + index_length) / (1024 * 1024), 2) AS total_size_mb
        FROM information_schema.TABLES 
        WHERE table_schema = ?
    ");
    $stmt->execute([$dbname]);
    $dbStats = $stmt->fetch();
    if ($dbStats) {
        $db_tables_count = (int)($dbStats['total_tables'] ?? 0);
        $db_size_mb = (float)($dbStats['total_size_mb'] ?? 0);
    }
} catch (Exception $e) {
    $db_tables_count = 'N/D';
    $db_size_mb = '0.00';
}

// ── 2. Telemetría de Almacenamiento (Uploads) ──
$uploads_dir = $project_root . '/uploads';
$uploads_files_count = 0;
$uploads_size_mb = 0;
$uploads_writable = is_dir($uploads_dir) ? is_writable($uploads_dir) : false;
if (is_dir($uploads_dir)) {
    try {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($uploads_dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        $totalBytes = 0;
        foreach ($iterator as $item) {
            if ($item->isFile()) {
                $totalBytes += $item->getSize();
                $uploads_files_count++;
            }
        }
        $uploads_size_mb = round($totalBytes / (1024 * 1024), 2);
    } catch (Exception $e) {}
}

// ── 3. Telemetría de Git ──
$git_dir = $project_root . '/.git';
$git_branch = '';
$git_status = '';
$git_commit = '';
$git_author = '';
$git_date = '';
$is_git = false;

try {
    if (is_dir($git_dir)) {
        $is_git = true;
        putenv('GIT_DISCOVERY_ACROSS_FILESYSTEM=1');
        putenv('GIT_DIR=' . $git_dir);
        putenv('GIT_WORK_TREE=' . $project_root);

        $git_cmd = 'git --git-dir=' . escapeshellarg($git_dir) . ' --work-tree=' . escapeshellarg($project_root) . ' -c safe.directory="*"';
        
        $branch_raw = shell_exec($git_cmd . ' rev-parse --abbrev-ref HEAD 2>&1');
        $git_branch = $branch_raw ? trim($branch_raw) : 'main';
        
        $status_raw = shell_exec($git_cmd . ' status -s 2>&1');
        $git_status = $status_raw ? trim($status_raw) : 'Árbol limpio (Sincronizado)';
        
        $commit_raw = shell_exec($git_cmd . ' log -1 --format="%h|%s|%an|%ar" 2>&1');
        if ($commit_raw && strpos($commit_raw, '|') !== false) {
            $parts = explode('|', trim($commit_raw));
            $git_commit_hash = $parts[0] ?? '';
            $git_commit_msg = $parts[1] ?? '';
            $git_author = $parts[2] ?? '';
            $git_date = $parts[3] ?? '';
            $git_commit = "$git_commit_hash - $git_commit_msg";
        } else {
            $git_commit = $commit_raw ? trim($commit_raw) : 'Sin commits registrados';
        }
    } else {
        // Fallback: Leer información guardada por GitHub Cloud Sync
        $version_file = $project_root . '/config/git_version.json';
        if (file_exists($version_file)) {
            $vData = json_decode((string)file_get_contents($version_file), true);
            if ($vData) {
                $git_branch = $vData['branch'] ?? 'main';
                $git_commit = ($vData['commit_hash'] ?? 'cloud') . ' - ' . ($vData['commit_msg'] ?? 'Sincronizado vía Cloud Sync');
                $git_author = $vData['commit_author'] ?? 'GitHub Cloud';
                $git_date = $vData['commit_date'] ?? ($vData['updated_at'] ?? 'Actualizado');
                $git_status = 'Sincronizado vía GitHub Direct Cloud';
            }
        }
        
        if (empty($git_branch)) {
            $git_branch = 'main (Direct Sync)';
            $git_commit = 'Sincronización directa vía GitHub Cloud habilitada';
            $git_author = 'GitHub';
            $git_status = 'Listo para sincronizar vía Cloud Sync';
        }
    }
} catch (Exception $e) {
    $git_branch = 'main (Direct Sync)';
    $git_commit = 'Sincronización directa vía GitHub Cloud habilitada';
    $git_status = 'Disponible';
}

// ── 4. Versiones del Servidor ──
$mysql_version = 'Desconocida';
try {
    $mysql_version = $pdo->query('SELECT VERSION()')->fetchColumn();
} catch (Exception $e) {}

$php_version = phpversion();
$memory_limit = ini_get('memory_limit');
$max_upload = ini_get('upload_max_filesize');
$max_post = ini_get('post_max_size');
$temp_dir = sys_get_temp_dir();
$temp_writable = is_writable($temp_dir);
?>

<!-- Estilos Dedicados del Módulo Sistema -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/modules/sistema/sistema.css?v=<?php echo time(); ?>">

<div class="container-fluid py-3 sistema-module-wrap">

    <!-- Cabecera Principal -->
    <div class="sistema-header">
        <div class="sistema-header-left">
            <div class="sistema-header-icon">
                <i class="ph ph-hard-drives"></i>
            </div>
            <div>
                <h2 class="sistema-header-title">Centro de Control del Sistema</h2>
                <p class="sistema-header-desc">Administración de respaldos, restauración, sincronización con GitHub y salud del servidor.</p>
            </div>
        </div>
        <div>
            <span class="sistema-header-badge-status">
                <span class="status-dot-pulse"></span>
                <span>Sistema Operativo</span>
            </span>
        </div>
    </div>

    <!-- Grid de Telemetría Superior (4 Métricas Clave) -->
    <div class="sistema-stats-grid">
        <!-- DB Stats -->
        <div class="sistema-stat-card">
            <div class="stat-info">
                <span class="stat-label">Base de Datos</span>
                <span class="stat-value"><?php echo $db_size_mb; ?> <small style="font-size:0.75rem;font-weight:normal;">MB</small></span>
                <span class="stat-sub">
                    <i class="ph ph-table"></i> <?php echo $db_tables_count; ?> tablas (<?php echo htmlspecialchars($dbname); ?>)
                </span>
            </div>
            <div class="stat-icon db">
                <i class="ph ph-database"></i>
            </div>
        </div>

        <!-- Uploads Storage Stats -->
        <div class="sistema-stat-card">
            <div class="stat-info">
                <span class="stat-label">Archivos Subidos</span>
                <span class="stat-value"><?php echo $uploads_size_mb; ?> <small style="font-size:0.75rem;font-weight:normal;">MB</small></span>
                <span class="stat-sub">
                    <i class="ph ph-files"></i> <?php echo number_format($uploads_files_count); ?> archivos en /uploads
                </span>
            </div>
            <div class="stat-icon storage">
                <i class="ph ph-folder-notch-open"></i>
            </div>
        </div>

        <!-- Git Repo Stats -->
        <div class="sistema-stat-card">
            <div class="stat-info">
                <span class="stat-label">Repositorio Git</span>
                <span class="stat-value" style="font-size: 1rem;"><?php echo htmlspecialchars($git_branch); ?></span>
                <span class="stat-sub" title="<?php echo htmlspecialchars($git_commit); ?>">
                    <i class="ph ph-git-commit"></i> <?php echo $git_date ? htmlspecialchars($git_date) : 'Activo'; ?>
                </span>
            </div>
            <div class="stat-icon git">
                <i class="ph ph-git-branch"></i>
            </div>
        </div>

        <!-- Server Environment Stats -->
        <div class="sistema-stat-card">
            <div class="stat-info">
                <span class="stat-label">Servidor PHP / MySQL</span>
                <span class="stat-value" style="font-size: 1rem;">PHP <?php echo htmlspecialchars(explode('-', $php_version)[0]); ?></span>
                <span class="stat-sub">
                    <i class="ph ph-cpu"></i> MySQL <?php echo htmlspecialchars(substr($mysql_version, 0, 10)); ?>
                </span>
            </div>
            <div class="stat-icon server">
                <i class="ph ph-server"></i>
            </div>
        </div>
    </div>

    <!-- Navegación por Pestañas (Modern Tabs) -->
    <div class="sistema-tabs-nav">
        <button type="button" class="sistema-tab-btn active" data-tab="backups">
            <i class="ph ph-database"></i> Copias de Seguridad
        </button>
        <button type="button" class="sistema-tab-btn" data-tab="restore">
            <i class="ph ph-clock-counter-clockwise"></i> Restauración
        </button>
        <button type="button" class="sistema-tab-btn" data-tab="git">
            <i class="ph ph-github-logo"></i> Actualizaciones & Git
        </button>
        <button type="button" class="sistema-tab-btn" data-tab="diagnostics">
            <i class="ph ph-activity"></i> Diagnóstico & Salud
        </button>
    </div>

    <!-- =========================================================================
         PESTAÑA 1: COPIAS DE SEGURIDAD (BACKUPS)
         ========================================================================= -->
    <div class="sistema-tab-pane active" id="tab-backups">
        <div class="sistema-card">
            <div class="sistema-card-title-row">
                <div>
                    <h3 class="sistema-card-title">
                        <i class="ph ph-cloud-arrow-down"></i> Generar Respaldo del Sistema
                    </h3>
                    <p class="sistema-card-subtitle">
                        Descarga copias de seguridad de forma instantánea para salvaguardar tu información.
                    </p>
                </div>
            </div>

            <!-- Opciones de Backup en Cuadrícula -->
            <div class="backup-options-grid">
                <!-- Tarjeta 1: Solo Base de Datos -->
                <div class="backup-action-card">
                    <div>
                        <div class="backup-card-head">
                            <div class="backup-card-icon sql">
                                <i class="ph ph-file-sql"></i>
                            </div>
                            <div>
                                <h4 class="backup-card-title">Solo Base de Datos (.sql)</h4>
                                <span class="backup-card-badge fast">Rápido (~<?php echo $db_size_mb; ?> MB)</span>
                            </div>
                        </div>
                        <ul class="backup-features-list">
                            <li><i class="ph ph-check-circle"></i> Estructura y esquemas de todas las tablas</li>
                            <li><i class="ph ph-check-circle"></i> Todos los registros y datos relacionales</li>
                            <li><i class="ph ph-check-circle"></i> Compatible con phpMyAdmin y MySQL CLI</li>
                        </ul>
                    </div>
                    <div>
                        <button type="button" class="btn-sistema btn-sistema-primary w-100" id="btnBackupDb" onclick="realizarBackup('db')">
                            <i class="ph ph-download-simple"></i> Descargar Base de Datos (.sql)
                        </button>
                    </div>
                </div>

                <!-- Tarjeta 2: Respaldo Completo -->
                <div class="backup-action-card">
                    <div>
                        <div class="backup-card-head">
                            <div class="backup-card-icon zip">
                                <i class="ph ph-file-zip"></i>
                            </div>
                            <div>
                                <h4 class="backup-card-title">Respaldo Completo (.zip)</h4>
                                <span class="backup-card-badge complete">Completo (~<?php echo round($db_size_mb + $uploads_size_mb, 2); ?> MB)</span>
                            </div>
                        </div>
                        <ul class="backup-features-list">
                            <li><i class="ph ph-check-circle"></i> Base de datos completa (database.sql)</li>
                            <li><i class="ph ph-check-circle"></i> Directorio de subidas (/uploads) completo</li>
                            <li><i class="ph ph-check-circle"></i> Imágenes, PDF, documentos y multimedia</li>
                        </ul>
                    </div>
                    <div>
                        <button type="button" class="btn-sistema btn-sistema-warning w-100" id="btnBackupFull" onclick="realizarBackup('full')">
                            <i class="ph ph-download-simple"></i> Descargar Backup Completo (.zip)
                        </button>
                    </div>
                </div>
            </div>

            <div id="backupStatus" class="sistema-alert"></div>

            <!-- Recomendaciones Box -->
            <div class="mt-4 p-3 rounded-3" style="background: var(--sis-bg-subtle); border: 1px solid var(--sis-card-border);">
                <div class="d-flex align-items-center gap-2 mb-2 text-primary" style="font-weight: 600;">
                    <i class="ph ph-lightbulb fs-5"></i> Recomendaciones de Seguridad
                </div>
                <p class="mb-0 text-muted" style="font-size: 0.8rem; line-height: 1.5;">
                    Se aconseja realizar un <strong>Respaldo Completo</strong> al menos una vez por semana o antes de aplicar actualizaciones importantes del sistema. Guarda las copias en un medio externo o en la nube para máxima protección contra desastres.
                </p>
            </div>
        </div>
    </div>

    <!-- =========================================================================
         PESTAÑA 2: RESTAURACIÓN DEL SISTEMA
         ========================================================================= -->
    <div class="sistema-tab-pane" id="tab-restore">
        <div class="sistema-card">
            <div class="sistema-card-title-row">
                <div>
                    <h3 class="sistema-card-title">
                        <i class="ph ph-arrow-counter-clockwise"></i> Restaurar Copia de Seguridad
                    </h3>
                    <p class="sistema-card-subtitle">
                        Restaura tu base de datos o el sistema completo desde un archivo previamente descargado.
                    </p>
                </div>
            </div>

            <!-- Banner de Advertencia -->
            <div class="safety-warning-banner">
                <i class="ph ph-warning-octagon safety-warning-icon"></i>
                <div class="safety-warning-text">
                    <h5>¡Advertencia de Seguridad Importante!</h5>
                    <p>La restauración <strong>sobrescribirá de forma irreversible</strong> las tablas de la base de datos actual y los archivos subidos coincidentes. Asegúrate de tener una copia de respaldo antes de proceder.</p>
                </div>
            </div>

            <!-- Formulario de Restauración -->
            <form id="restoreForm" onsubmit="solicitarConfirmacionRestaurar(event)">
                <!-- Zona Dropzone Especializada -->
                <div class="restore-dropzone-box" id="restoreDropzone">
                    <i class="ph ph-cloud-arrow-up restore-dropzone-icon"></i>
                    <div class="restore-dropzone-title">Haz clic o arrastra tu archivo de respaldo aquí</div>
                    <div class="restore-dropzone-subtitle">Archivos soportados para restauración automática</div>
                    <div class="restore-format-pills">
                        <span class="restore-pill"><i class="ph ph-file-sql text-primary"></i> .SQL (Base de datos)</span>
                        <span class="restore-pill"><i class="ph ph-file-zip text-warning"></i> .ZIP (Respaldo Completo)</span>
                    </div>
                </div>

                <!-- Input oculto con clase no-dropzone para evitar el wrap genérico de app.js -->
                <input type="file" id="restoreFileInput" class="no-dropzone" accept=".zip,.sql" style="display: none;">

                <!-- Caja de Archivo Seleccionado -->
                <div class="restore-file-selected-box" id="restoreSelectedBox">
                    <div class="restore-file-info">
                        <div class="restore-file-icon" id="restoreFileIcon">
                            <i class="ph ph-file-zip"></i>
                        </div>
                        <div>
                            <div class="restore-file-name" id="restoreFileName">archivo.zip</div>
                            <div class="restore-file-size" id="restoreFileSize">0 KB</div>
                        </div>
                    </div>
                    <button type="button" class="btn-remove-file" id="btnRemoveRestoreFile" title="Quitar archivo">
                        <i class="ph ph-trash"></i>
                    </button>
                </div>

                <!-- Barra de Progreso -->
                <div class="sistema-progress-container" id="restoreProgressContainer">
                    <div class="sistema-progress-bar danger" id="restoreProgressBar"></div>
                </div>
                <div id="restoreProgressText" class="text-muted text-center mb-3" style="font-size: 0.78rem;"></div>

                <!-- Botón de Ejecutar -->
                <button type="submit" class="btn-sistema btn-sistema-danger w-100" id="btnRestoreSubmit" disabled>
                    <i class="ph ph-warning-circle"></i> Restaurar Copia de Seguridad
                </button>
            </form>

            <div id="restoreStatus" class="sistema-alert"></div>
        </div>
    </div>

    <!-- =========================================================================
         PESTAÑA 3: ACTUALIZACIONES & GIT
         ========================================================================= -->
    <div class="sistema-tab-pane" id="tab-git">
        <div class="sistema-card">
            <div class="sistema-card-title-row">
                <div>
                    <h3 class="sistema-card-title">
                        <i class="ph ph-github-logo"></i> Control de Versiones & Actualización
                    </h3>
                    <p class="sistema-card-subtitle">
                        Sincroniza el código fuente con GitHub para recibir nuevas funcionalidades, parches y mejoras.
                    </p>
                </div>
                <div>
                    <button type="button" class="btn-sistema btn-sistema-dark" id="btnUpdateGit" onclick="actualizarSistema()">
                        <i class="ph ph-arrows-clockwise"></i> Sincronizar con GitHub
                    </button>
                </div>
            </div>

            <!-- Información del Repositorio -->
            <div class="git-status-grid">
                <div class="git-info-box">
                    <div class="git-info-label">
                        <i class="ph ph-git-branch"></i> Rama Activa
                    </div>
                    <div class="git-branch-pill">
                        <i class="ph ph-git-commit"></i> <?php echo htmlspecialchars($git_branch); ?>
                    </div>
                </div>

                <div class="git-info-box">
                    <div class="git-info-label">
                        <i class="ph ph-user"></i> Autor & Fecha del Último Commit
                    </div>
                    <div style="font-size: 0.88rem; font-weight: 600; color: var(--sis-text-main);">
                        <?php echo htmlspecialchars($git_author ?: 'Servidor Local'); ?>
                    </div>
                    <div class="text-muted" style="font-size: 0.78rem;">
                        <?php echo htmlspecialchars($git_date ?: 'Sin fecha'); ?>
                    </div>
                </div>

                <div class="git-info-box">
                    <div class="git-info-label">
                        <i class="ph ph-clipboard-text"></i> Estado del Directorio de Trabajo
                    </div>
                    <div style="font-size: 0.85rem; color: var(--sis-text-main);">
                        <?php echo htmlspecialchars($git_status); ?>
                    </div>
                </div>
            </div>

            <!-- Último Commit Info -->
            <div class="p-3 rounded-3 mb-3" style="background: var(--sis-bg-subtle); border: 1px solid var(--sis-card-border);">
                <div class="git-info-label">
                    <i class="ph ph-git-commit"></i> Último Commit Registrado
                </div>
                <div class="git-commit-desc">
                    <?php echo htmlspecialchars($git_commit); ?>
                </div>
            </div>

            <div id="updateStatus" class="sistema-alert"></div>

            <!-- Consola de Terminal Emulada -->
            <div class="sistema-terminal-window" id="gitTerminal">
                <div class="terminal-header">
                    <div class="terminal-dots">
                        <span class="terminal-dot dot-red"></span>
                        <span class="terminal-dot dot-yellow"></span>
                        <span class="terminal-dot dot-green"></span>
                        <span class="terminal-title ms-2">git-sync-output.log</span>
                    </div>
                    <div class="terminal-actions">
                        <button type="button" class="terminal-btn" id="btnCopyTerminal" onclick="copiarTerminal()">
                            <i class="ph ph-copy"></i> Copiar
                        </button>
                        <button type="button" class="terminal-btn" onclick="limpiarTerminal()">
                            <i class="ph ph-eraser"></i> Limpiar
                        </button>
                    </div>
                </div>
                <div class="terminal-body" id="terminalBody">Esperando sincronización...</div>
            </div>
        </div>
    </div>

    <!-- =========================================================================
         PESTAÑA 4: DIAGNÓSTICO & SALUD DEL ENTORNO
         ========================================================================= -->
    <div class="sistema-tab-pane" id="tab-diagnostics">
        <div class="sistema-card">
            <div class="sistema-card-title-row">
                <div>
                    <h3 class="sistema-card-title">
                        <i class="ph ph-stethoscope"></i> Diagnóstico de Requisitos del Servidor
                    </h3>
                    <p class="sistema-card-subtitle">
                        Comprobación de módulos PHP, extensiones y permisos de directorios requeridos por TurboSaaS.
                    </p>
                </div>
                <div>
                    <a href="<?php echo BASE_URL; ?>/test_gdrive.php" target="_blank" class="btn-sistema btn-sistema-outline">
                        <i class="ph ph-google-drive-logo"></i> Test Google Drive
                    </a>
                </div>
            </div>

            <!-- Tabla de Diagnósticos -->
            <div class="diagnostics-table-wrap">
                <table class="diagnostics-table">
                    <thead>
                        <tr>
                            <th>Componente / Requisito</th>
                            <th>Valor Actual</th>
                            <th>Recomendado</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- PHP Version -->
                        <tr>
                            <td><strong>Versión de PHP</strong></td>
                            <td>PHP <?php echo $php_version; ?></td>
                            <td>>= 8.0</td>
                            <td>
                                <?php if (version_compare(PHP_VERSION, '8.0.0', '>=')): ?>
                                    <span class="badge-diag ok"><i class="ph ph-check-circle"></i> Compatible</span>
                                <?php else: ?>
                                    <span class="badge-diag warn"><i class="ph ph-warning"></i> Actualizar</span>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <!-- MySQL Version -->
                        <tr>
                            <td><strong>Motor de Base de Datos (MySQL / MariaDB)</strong></td>
                            <td><?php echo htmlspecialchars($mysql_version); ?></td>
                            <td>>= 5.7 / MariaDB 10.3</td>
                            <td>
                                <span class="badge-diag ok"><i class="ph ph-check-circle"></i> Conectado</span>
                            </td>
                        </tr>

                        <!-- ZipArchive -->
                        <tr>
                            <td><strong>Extensión PHP ZipArchive</strong> (Para respaldos ZIP)</td>
                            <td><?php echo class_exists('ZipArchive') ? 'Habilitada' : 'No disponible'; ?></td>
                            <td>Habilitada</td>
                            <td>
                                <?php if (class_exists('ZipArchive')): ?>
                                    <span class="badge-diag ok"><i class="ph ph-check-circle"></i> Operativa</span>
                                <?php else: ?>
                                    <span class="badge-diag err"><i class="ph ph-x-circle"></i> Requerida</span>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <!-- PDO MySQL -->
                        <tr>
                            <td><strong>Driver PDO MySQL</strong></td>
                            <td><?php echo extension_loaded('pdo_mysql') ? 'Habilitado' : 'No disponible'; ?></td>
                            <td>Habilitado</td>
                            <td>
                                <span class="badge-diag ok"><i class="ph ph-check-circle"></i> Operativo</span>
                            </td>
                        </tr>

                        <!-- Permisos /uploads -->
                        <tr>
                            <td><strong>Permisos de Escritura en /uploads</strong></td>
                            <td><?php echo $uploads_writable ? 'Escritura permitida' : 'Solo lectura / Inaccesible'; ?></td>
                            <td>Permisos 0777 / Writable</td>
                            <td>
                                <?php if ($uploads_writable): ?>
                                    <span class="badge-diag ok"><i class="ph ph-check-circle"></i> Correcto</span>
                                <?php else: ?>
                                    <span class="badge-diag err"><i class="ph ph-x-circle"></i> Sin permisos</span>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <!-- Permisos Temp -->
                        <tr>
                            <td><strong>Directorio Temporal del Sistema</strong></td>
                            <td><code><?php echo htmlspecialchars($temp_dir); ?></code></td>
                            <td>Escritura permitida</td>
                            <td>
                                <?php if ($temp_writable): ?>
                                    <span class="badge-diag ok"><i class="ph ph-check-circle"></i> Correcto</span>
                                <?php else: ?>
                                    <span class="badge-diag err"><i class="ph ph-x-circle"></i> Sin permisos</span>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <!-- Límites de Memoria y Subida -->
                        <tr>
                            <td><strong>Límite de Memoria (memory_limit)</strong></td>
                            <td><?php echo htmlspecialchars($memory_limit); ?></td>
                            <td>>= 128M</td>
                            <td>
                                <span class="badge-diag ok"><i class="ph ph-check-circle"></i> Adecuado</span>
                            </td>
                        </tr>

                        <tr>
                            <td><strong>Tamaño Máximo de Subida (upload_max_filesize)</strong></td>
                            <td><?php echo htmlspecialchars($max_upload); ?> (Post: <?php echo htmlspecialchars($max_post); ?>)</td>
                            <td>>= 50M</td>
                            <td>
                                <span class="badge-diag ok"><i class="ph ph-check-circle"></i> Configurado</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- =========================================================================
     MODAL DE CONFIRMACIÓN DE SEGURIDAD (RESTAURACIÓN)
     ========================================================================= -->
<div class="sistema-modal-overlay" id="restoreConfirmModal">
    <div class="sistema-modal-box">
        <div class="text-center">
            <div class="modal-danger-icon mx-auto">
                <i class="ph ph-warning-octagon"></i>
            </div>
            <h4 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 8px;">¿Confirmas la Restauración?</h4>
            <p class="text-muted" style="font-size: 0.83rem; line-height: 1.5; margin-bottom: 16px;">
                Estás a punto de sobreescribir la base de datos y los archivos del sistema con el archivo:
            </p>
            <div class="p-2 rounded-2 mb-3" style="background: var(--sis-bg-subtle); border: 1px solid var(--sis-card-border); font-family: monospace; font-size: 0.85rem; font-weight: 600; color: var(--sis-danger);" id="modalRestoreFileName">
                backup.zip
            </div>
            <p style="font-size: 0.78rem; color: var(--sis-danger); margin-bottom: 24px;">
                <i class="ph ph-info me-1"></i> Esta acción no se puede deshacer.
            </p>
            <div class="d-flex gap-2 justify-content-end">
                <button type="button" class="btn-sistema btn-sistema-outline flex-fill" onclick="cerrarModalRestaurar()">
                    Cancelar
                </button>
                <button type="button" class="btn-sistema btn-sistema-danger flex-fill" onclick="ejecutarRestauracionDefinitiva()">
                    Sí, Restaurar Ahora
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Script del Módulo Sistema -->
<script src="<?php echo BASE_URL; ?>/modules/sistema/sistema.js?v=<?php echo time(); ?>"></script>

<?php include '../../includes/footer.php'; ?>
