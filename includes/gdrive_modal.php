<!-- Modal Global de Explorador de Google Drive -->
<div class="modal-overlay" id="gdriveModal" style="z-index: 18000;">
    <div class="modal-content" style="max-width: 950px; width: 95%;">
        <div class="modal-header">
            <h3 style="display:flex; align-items:center; gap:8px; margin:0;">
                <i class="ph ph-google-drive-logo" style="color: #16a34a;"></i> Explorador de Google Drive
            </h3>
            <button type="button" class="btn close-modal" onclick="GDriveManager.closeModal()">&times;</button>
        </div>
        <div class="modal-body" style="padding: 15px;">
            <div id="gdriveModalContainer" class="gdrive-wrapper">
                <!-- Toolbar del Modal (Una sola línea) -->
                <div class="gdrive-toolbar">
                    <div class="gdrive-toolbar-left">
                        <div class="gdrive-search-box">
                            <i class="ph ph-magnifying-glass"></i>
                            <input type="text" class="form-control gdrive-search-input" placeholder="Buscar en Drive...">
                        </div>

                        <div class="gdrive-filter-group">
                            <button class="gdrive-filter-btn active" data-filter="all">Todos</button>
                            <button class="gdrive-filter-btn" data-filter="image">Imágenes</button>
                            <button class="gdrive-filter-btn" data-filter="video">Videos</button>
                            <button class="gdrive-filter-btn" data-filter="document">Docs</button>
                        </div>
                    </div>

                    <div class="gdrive-toolbar-right">
                        <input type="file" class="gdrive-file-input no-dropzone" multiple style="display: none;">

                        <button class="btn btn-secondary gdrive-btn-new-folder">
                            <i class="ph ph-folder-plus"></i> Nueva Carpeta
                        </button>

                        <div class="btn-group">
                            <button class="btn btn-light gdrive-view-grid active" title="Vista Cuadrícula"><i class="ph ph-squares-four"></i></button>
                            <button class="btn btn-light gdrive-view-list" title="Vista Lista"><i class="ph ph-list-bullets"></i></button>
                        </div>
                    </div>
                </div>

                <!-- Migas de pan / Navegación -->
                <div class="gdrive-breadcrumbs-container">
                    <i class="ph ph-folder"></i>
                    <div class="gdrive-breadcrumbs">
                        <span class="breadcrumb-item active">Cargando...</span>
                    </div>
                </div>

                <!-- Dropzone / Items Container -->
                <div class="gdrive-dropzone">
                    <div class="gdrive-items-body">
                        <!-- Los archivos se cargan aquí dinámicamente mediante JS -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal 1: Crear / Renombrar Carpeta o Archivo -->
<div class="modal-overlay" id="gdriveFolderModal" style="z-index: 19000;">
    <div class="modal-content" style="max-width: 450px;">
        <div class="modal-header">
            <h3 id="gdriveFolderModalTitle"><i class="ph ph-folder-plus"></i> Nueva Carpeta</h3>
            <button type="button" class="btn close-modal" onclick="document.getElementById('gdriveFolderModal').classList.remove('active')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group mb-3">
                <label class="form-label">Nombre del elemento <span class="text-danger">*</span></label>
                <input type="text" id="gdriveFolderNameInput" class="form-control" placeholder="Ej: Documentos 2026" autocomplete="off">
            </div>
        </div>
        <div class="modal-footer" style="display:flex; justify-content:flex-end; gap:10px;">
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('gdriveFolderModal').classList.remove('active')">Cancelar</button>
            <button type="button" class="btn btn-primary" id="btnSaveGDriveFolder">Guardar</button>
        </div>
    </div>
</div>

<!-- Modal 2: Confirmar Eliminación -->
<div class="modal-overlay" id="gdriveDeleteModal" style="z-index: 19000;">
    <div class="modal-content" style="max-width: 450px;">
        <div class="modal-header">
            <h3 style="color:#ef4444;"><i class="ph ph-trash"></i> Confirmar Eliminación</h3>
            <button type="button" class="btn close-modal" onclick="document.getElementById('gdriveDeleteModal').classList.remove('active')">&times;</button>
        </div>
        <div class="modal-body">
            <p style="margin:0; font-size:0.95rem; color:var(--text-color);">¿Estás seguro de que deseas eliminar este elemento de Google Drive? Esta acción no se puede deshacer.</p>
        </div>
        <div class="modal-footer" style="display:flex; justify-content:flex-end; gap:10px;">
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('gdriveDeleteModal').classList.remove('active')">Cancelar</button>
            <button type="button" class="btn btn-danger" id="btnConfirmGDriveDelete" style="background:#ef4444; border-color:#ef4444; color:#fff;">Eliminar</button>
        </div>
    </div>
</div>

<!-- Modal 3: Visor Interno del Sistema (Imágenes, Videos, PDF y Documentos) -->
<div class="modal-overlay" id="gdriveViewerModal" style="z-index: 20000;">
    <div class="modal-content" style="max-width: 1050px; width: 95%; height: 90vh; display: flex; flex-direction: column;">
        <div class="modal-header d-flex align-items-center justify-content-between" style="padding: 14px 20px;">
            <h3 id="gdriveViewerTitle" style="display:flex; align-items:center; gap:8px; margin:0; font-size:1.1rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                <i class="ph ph-eye"></i> Visor de Archivo
            </h3>
            <div class="d-flex align-items-center gap-2">
                <a id="gdriveViewerDownloadBtn" href="#" target="_blank" class="btn btn-sm btn-primary" title="Descargar / Abrir en pestaña">
                    <i class="ph ph-download-simple"></i> Descargar
                </a>
                <button type="button" class="btn close-modal" onclick="GDriveManager.closeViewer()">&times;</button>
            </div>
        </div>
        <div class="modal-body" id="gdriveViewerBody" style="flex:1; padding: 0; background: #0f172a; border-radius: 0 0 12px 12px; display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative;">
            <!-- Contenido del Visor dinámico -->
        </div>
    </div>
</div>
