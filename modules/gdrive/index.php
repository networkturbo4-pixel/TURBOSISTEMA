<?php
/**
 * Módulo Completo: Administrador de Google Drive
 */
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once '../../config/db.php';
requireLogin();
requirePermission($pdo, 'gdrive');

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<!-- Estilos específicos del Gestor de Google Drive -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/gdrive_manager.css?v=<?php echo time(); ?>">

<div class="container-fluid py-2">

    <!-- Cabecera de Página (App-like Header) -->
    <div class="page-header-card mb-4">
        <div class="page-header-left">
            <div class="page-header-icon" style="background: rgba(34, 197, 94, 0.1); color: #16a34a;">
                <i class="ph ph-google-drive-logo"></i>
            </div>
            <div class="page-header-info">
                <h2>Google Drive</h2>
                <p>Almacena, organiza y gestiona tus imágenes, videos y documentos en la nube.</p>
            </div>
        </div>
        <div class="page-header-actions">
            <a href="<?php echo BASE_URL; ?>/test_gdrive.php" target="_blank" class="btn btn-secondary me-2">
                <i class="ph ph-wrench"></i> Diagnóstico
            </a>
            <button class="btn btn-primary" onclick="document.querySelector('#gdriveModuleContainer .gdrive-file-input').click()">
                <i class="ph ph-upload-simple"></i> Subir a Drive
            </button>
        </div>
    </div>

    <!-- Contenedor Principal del Gestor de Archivos -->
    <div id="gdriveModuleContainer" class="gdrive-wrapper">

        <!-- Toolbar Superior (En una sola línea elegante) -->
        <div class="gdrive-toolbar">
            <div class="gdrive-toolbar-left">
                <!-- Buscador -->
                <div class="gdrive-search-box">
                    <i class="ph ph-magnifying-glass"></i>
                    <input type="text" class="form-control gdrive-search-input" placeholder="Buscar archivos o carpetas...">
                </div>

                <!-- Filtros por tipo -->
                <div class="gdrive-filter-group">
                    <button class="gdrive-filter-btn active" data-filter="all">Todos</button>
                    <button class="gdrive-filter-btn" data-filter="image">Imágenes</button>
                    <button class="gdrive-filter-btn" data-filter="video">Videos</button>
                    <button class="gdrive-filter-btn" data-filter="document">Documentos</button>
                </div>
            </div>

            <div class="gdrive-toolbar-right">
                <input type="file" class="gdrive-file-input no-dropzone" multiple style="display: none;">

                <button class="btn btn-secondary gdrive-btn-new-folder">
                    <i class="ph ph-folder-plus"></i> Nueva Carpeta
                </button>

                <!-- Alternador de Vista (Cuadrícula / Lista) -->
                <div class="btn-group">
                    <button class="btn btn-light gdrive-view-grid active" title="Vista Cuadrícula"><i class="ph ph-squares-four"></i></button>
                    <button class="btn btn-light gdrive-view-list" title="Vista Lista"><i class="ph ph-list-bullets"></i></button>
                </div>
            </div>
        </div>

        <!-- Caja de Subida Interactiva (Drag & Drop / Clic) -->
        <div class="gdrive-upload-dropzone-box">
            <i class="ph ph-cloud-arrow-up" style="font-size: 2rem; color: var(--primary-color);"></i>
            <div style="font-weight: 600; margin-top: 6px;">Haz clic o arrastra archivos aquí para subir a Google Drive</div>
            <div class="text-muted" style="font-size: 0.85rem;">Soporta imágenes, videos, PDF, Office y más</div>
        </div>

        <!-- Migas de Pan (Breadcrumbs) -->
        <div class="gdrive-breadcrumbs-container">
            <i class="ph ph-folder-open text-muted"></i>
            <div class="gdrive-breadcrumbs">
                <span class="breadcrumb-item active">Cargando...</span>
            </div>
        </div>

        <!-- Área Dropzone / Lista de Archivos -->
        <div class="gdrive-dropzone">
            <div class="gdrive-items-body">
                <!-- Los elementos se cargan mediante JS -->
            </div>
        </div>

    </div>

</div>

<!-- Script del Gestor de Google Drive -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    GDriveManager.init({ isModal: false });
});
</script>

<?php include '../../includes/footer.php'; ?>
