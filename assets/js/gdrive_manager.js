/**
 * Gestor Global de Google Drive para TurboSaaS
 * Soporta modo Módulo Completo y modo Modal Flotante (File Picker)
 */

window.GDriveManager = window.GDriveManager || {
    currentFolderId: '',
    folderHistory: [], // [{ id, name }]
    currentFilter: 'all',
    searchQuery: '',
    viewMode: 'grid', // 'grid' | 'list'
    onSelectCallback: null,
    isModalMode: false,

    getBaseUrl: function () {
        if (window.BASE_URL) return window.BASE_URL;
        const meta = document.querySelector('meta[name="base-url"]');
        if (meta && meta.getAttribute('content')) return meta.getAttribute('content');
        return '/TURBOSAAS';
    },

    init: function (options = {}) {
        this.isModalMode = !!options.isModal;
        if (options.onSelect) {
            this.onSelectCallback = options.onSelect;
        }

        this.bindEvents();
        this.loadFolder(options.initialFolderId || '');
    },

    bindEvents: function () {
        const self = this;
        const container = self.getContainer();
        if (!container) return;

        // Búsqueda
        const searchInput = container.querySelector('.gdrive-search-input');
        if (searchInput) {
            let timeout = null;
            searchInput.addEventListener('input', function (e) {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    self.searchQuery = e.target.value;
                    self.loadFolder(self.currentFolderId);
                }, 300);
            });
        }

        // Filtros por Tipo
        const filterBtns = container.querySelectorAll('.gdrive-filter-btn');
        filterBtns.forEach(btn => {
            btn.addEventListener('click', function () {
                filterBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                self.currentFilter = this.dataset.filter || 'all';
                self.loadFolder(self.currentFolderId);
            });
        });

        // Alternar Vista (Grid/List)
        const viewGridBtn = container.querySelector('.gdrive-view-grid');
        const viewListBtn = container.querySelector('.gdrive-view-list');
        if (viewGridBtn && viewListBtn) {
            viewGridBtn.addEventListener('click', function () {
                self.viewMode = 'grid';
                viewGridBtn.classList.add('active');
                viewListBtn.classList.remove('active');
                self.renderItems(self.lastItemsData || []);
            });
            viewListBtn.addEventListener('click', function () {
                self.viewMode = 'list';
                viewListBtn.classList.add('active');
                viewGridBtn.classList.remove('active');
                self.renderItems(self.lastItemsData || []);
            });
        }

        // Crear Carpeta
        const newFolderBtn = container.querySelector('.gdrive-btn-new-folder');
        if (newFolderBtn) {
            newFolderBtn.addEventListener('click', function () {
                self.promptCreateFolder();
            });
        }

        // Subir Archivo - Evento Change
        const uploadInput = container.querySelector('.gdrive-file-input');
        if (uploadInput) {
            uploadInput.addEventListener('change', function () {
                if (this.files && this.files.length > 0) {
                    self.uploadFiles(this.files);
                    this.value = '';
                }
            });
        }

        // Clic en la caja .gdrive-upload-dropzone-box
        const uploadBox = container.querySelector('.gdrive-upload-dropzone-box');
        if (uploadBox && uploadInput) {
            uploadBox.addEventListener('click', function () {
                uploadInput.click();
            });
        }

        // Botón opcional de subir archivo
        const uploadBtn = container.querySelector('.gdrive-btn-upload');
        if (uploadBtn && uploadInput) {
            uploadBtn.addEventListener('click', function () {
                uploadInput.click();
            });
        }

        // Zonas Drag & Drop (Tanto la caja como el contenedor principal)
        const dropZones = container.querySelectorAll('.gdrive-upload-dropzone-box, .gdrive-dropzone');
        dropZones.forEach(dropZone => {
            ['dragenter', 'dragover'].forEach(eventName => {
                dropZone.addEventListener(eventName, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    dropZone.classList.add('drag-over');
                }, false);
            });
            ['dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    dropZone.classList.remove('drag-over');
                }, false);
            });
            dropZone.addEventListener('drop', (e) => {
                const files = e.dataTransfer.files;
                if (files && files.length > 0) {
                    self.uploadFiles(files);
                }
            });
        });
    },

    getContainer: function () {
        return this.isModalMode ? document.getElementById('gdriveModalContainer') : document.getElementById('gdriveModuleContainer');
    },

    loadFolder: function (folderId) {
        const self = this;
        const container = self.getContainer();
        if (!container) return;

        const body = container.querySelector('.gdrive-items-body');
        if (body) {
            body.innerHTML = '<div class="gdrive-loading"><i class="ph ph-spinner spinner"></i> Cargando archivos...</div>';
        }

        const formData = new FormData();
        formData.append('action', 'list');
        if (folderId) formData.append('folder_id', folderId);
        if (self.currentFilter) formData.append('filter', self.currentFilter);
        if (self.searchQuery) formData.append('q', self.searchQuery);

        const apiUrl = self.getBaseUrl() + '/ajax/drive_manager.php';

        fetch(apiUrl, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                self.currentFolderId = data.current_folder.id;
                self.updateBreadcrumbs(data.current_folder);
                self.lastItemsData = data.items;
                self.renderItems(data.items);
            } else {
                if (body) body.innerHTML = `<div class="gdrive-empty-state text-danger"><i class="ph ph-warning-circle"></i> ${data.error || 'Error al cargar contenido'}</div>`;
            }
        })
        .catch(err => {
            if (body) body.innerHTML = `<div class="gdrive-empty-state text-danger"><i class="ph ph-warning-circle"></i> Error de conexión (${err.message})</div>`;
        });
    },

    updateBreadcrumbs: function (currFolder) {
        const container = this.getContainer();
        if (!container) return;
        const breadcrumbEl = container.querySelector('.gdrive-breadcrumbs');
        if (!breadcrumbEl) return;

        const self = this;
        // Ajustar historial
        const existingIdx = self.folderHistory.findIndex(f => f.id === currFolder.id);
        if (existingIdx !== -1) {
            self.folderHistory = self.folderHistory.slice(0, existingIdx + 1);
        } else {
            self.folderHistory.push({ id: currFolder.id, name: currFolder.name });
        }

        let html = '';
        self.folderHistory.forEach((f, idx) => {
            const isLast = (idx === self.folderHistory.length - 1);
            if (isLast) {
                html += `<span class="breadcrumb-item active">${f.name}</span>`;
            } else {
                html += `<a href="javascript:void(0)" class="breadcrumb-item" data-id="${f.id}">${f.name}</a> <i class="ph ph-caret-right text-muted"></i> `;
            }
        });

        breadcrumbEl.innerHTML = html;
        breadcrumbEl.querySelectorAll('a.breadcrumb-item').forEach(a => {
            a.addEventListener('click', function () {
                self.loadFolder(this.dataset.id);
            });
        });
    },

    renderItems: function (items) {
        const self = this;
        const container = self.getContainer();
        if (!container) return;
        const body = container.querySelector('.gdrive-items-body');
        if (!body) return;

        if (items.length === 0) {
            body.innerHTML = `
                <div class="gdrive-empty-state">
                    <i class="ph ph-folder-open"></i>
                    <h4>No hay archivos en esta carpeta</h4>
                    <p>Puedes subir archivos arrastrándolos aquí.</p>
                </div>`;
            return;
        }

        if (self.viewMode === 'grid') {
            let html = '<div class="gdrive-grid-view">';
            items.forEach(item => {
                html += self.buildGridCard(item);
            });
            html += '</div>';
            body.innerHTML = html;
        } else {
            let html = `
                <div class="table-responsive">
                    <table class="table gdrive-list-table">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Tipo</th>
                                <th>Tamaño</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>`;
            items.forEach(item => {
                html += self.buildListRow(item);
            });
            html += '</tbody></table></div>';
            body.innerHTML = html;
        }

        self.attachItemEvents(body);
    },

    buildGridCard: function (item) {
        const self = this;
        let iconOrThumb = '';
        let badgeType = '';

        if (item.is_folder) {
            iconOrThumb = '<div class="folder-media-wrap"><i class="ph-fill ph-folder folder-icon"></i></div>';
            badgeType = '<span class="gdrive-type-badge badge-folder"><i class="ph-fill ph-folder"></i> Carpeta</span>';
        } else if (item.category === 'image') {
            let thumbUrl = item.thumbnail_link ? item.thumbnail_link.replace(/=s\d+/, '=s400') : item.direct_link;
            iconOrThumb = `<img src="${thumbUrl}" class="card-thumb" referrerpolicy="no-referrer" onerror="this.outerHTML='<i class=\\'ph-fill ph-image doc-icon\\' style=\\'color:#3b82f6;\\'></i>';" />`;
            badgeType = '<span class="gdrive-type-badge badge-image"><i class="ph-fill ph-image"></i> Imagen</span>';
        } else if (item.category === 'video') {
            let thumbUrl = item.thumbnail_link ? item.thumbnail_link.replace(/=s\d+/, '=s400') : '';
            if (thumbUrl) {
                iconOrThumb = `<div class="video-media-wrap"><img src="${thumbUrl}" class="card-thumb" referrerpolicy="no-referrer" /><i class="ph-fill ph-play-circle play-overlay"></i></div>`;
            } else {
                iconOrThumb = '<div class="video-media-wrap"><i class="ph-fill ph-video-camera video-icon"></i></div>';
            }
            badgeType = '<span class="gdrive-type-badge badge-video"><i class="ph-fill ph-video-camera"></i> Video</span>';
        } else {
            let docIcon = 'ph-fill ph-file-text';
            let docColor = '#3b82f6';
            let categoryName = 'Documento';

            const ext = item.name.split('.').pop().toLowerCase();
            if (ext === 'pdf') {
                docIcon = 'ph-fill ph-file-pdf';
                docColor = '#ef4444';
                categoryName = 'PDF';
            } else if (['doc', 'docx'].includes(ext)) {
                docIcon = 'ph-fill ph-file-doc';
                docColor = '#2563eb';
                categoryName = 'Word';
            } else if (['xls', 'xlsx', 'csv'].includes(ext)) {
                docIcon = 'ph-fill ph-file-xls';
                docColor = '#16a34a';
                categoryName = 'Excel';
            } else if (['zip', 'rar', '7z', 'tar', 'gz'].includes(ext)) {
                docIcon = 'ph-fill ph-file-zip';
                docColor = '#d97706';
                categoryName = 'ZIP';
            }

            if (item.thumbnail_link) {
                let thumbUrl = item.thumbnail_link.replace(/=s\d+/, '=s400');
                iconOrThumb = `<img src="${thumbUrl}" class="card-thumb" referrerpolicy="no-referrer" onerror="this.outerHTML='<i class=\\'${docIcon} doc-icon\\' style=\\'color:${docColor};\\'></i>'" />`;
            } else {
                iconOrThumb = `<i class="${docIcon} doc-icon" style="color: ${docColor};"></i>`;
            }

            badgeType = `<span class="gdrive-type-badge badge-doc"><i class="${docIcon}"></i> ${categoryName}</span>`;
        }

        const sizeFormatted = item.is_folder ? 'Carpeta' : self.formatBytes(item.size);

        return `
            <div class="gdrive-card ${item.is_folder ? 'is-folder' : 'is-file'}" data-id="${item.id}" data-is-folder="${item.is_folder}" data-link="${item.direct_link}" data-view="${item.web_view_link}">
                <div class="card-media">
                    ${iconOrThumb}
                </div>
                <div class="card-info">
                    <span class="card-title" title="${self.escapeHtml(item.name)}">${self.escapeHtml(item.name)}</span>
                    <div class="d-flex align-items-center justify-content-between mt-1 w-100">
                        ${badgeType}
                        <span class="card-meta">${sizeFormatted}</span>
                    </div>
                </div>
                <div class="card-actions">
                    ${!item.is_folder && self.onSelectCallback ? `<button class="btn-action btn-select" title="Seleccionar"><i class="ph ph-check"></i></button>` : ''}
                    ${!item.is_folder ? `<button class="btn-action btn-view" title="Previsualizar en el sistema"><i class="ph ph-eye"></i></button>` : ''}
                    <button class="btn-action btn-rename" title="Renombrar"><i class="ph ph-pencil-simple"></i></button>
                    <a href="${item.web_view_link}" target="_blank" class="btn-action" title="Abrir en Drive"><i class="ph ph-arrow-square-out"></i></a>
                    <button class="btn-action btn-delete text-danger" title="Eliminar"><i class="ph ph-trash"></i></button>
                </div>
            </div>`;
    },

    buildListRow: function (item) {
        const self = this;
        let icon = item.is_folder ? '<i class="ph-fill ph-folder text-warning fs-5"></i>' :
                   (item.category === 'image' ? '<i class="ph-fill ph-image text-primary fs-5"></i>' :
                   (item.category === 'video' ? '<i class="ph-fill ph-video-camera text-purple fs-5"></i>' : '<i class="ph-fill ph-file-text text-secondary fs-5"></i>'));

        return `
            <tr data-id="${item.id}" data-is-folder="${item.is_folder}" data-link="${item.direct_link}" data-view="${item.web_view_link}">
                <td data-label="Nombre" class="gdrive-name-cell">
                    <span class="item-name-click">${icon} ${self.escapeHtml(item.name)}</span>
                </td>
                <td data-label="Tipo"><span class="tag-pill">${item.is_folder ? 'CARPETA' : item.category.toUpperCase()}</span></td>
                <td data-label="Tamaño">${item.is_folder ? '-' : self.formatBytes(item.size)}</td>
                <td data-label="Acciones" class="text-end">
                    ${!item.is_folder && self.onSelectCallback ? `<button class="btn btn-sm btn-primary btn-select me-1">Seleccionar</button>` : ''}
                    ${!item.is_folder ? `<button class="btn-action btn-view" title="Previsualizar en el sistema"><i class="ph ph-eye"></i></button>` : ''}
                    <button class="btn-action btn-rename" title="Renombrar"><i class="ph ph-pencil-simple"></i></button>
                    <a href="${item.web_view_link}" target="_blank" class="btn-action" title="Abrir en Drive"><i class="ph ph-arrow-square-out"></i></a>
                    <button class="btn-action btn-delete text-danger" title="Eliminar"><i class="ph ph-trash"></i></button>
                </td>
            </tr>`;
    },

    attachItemEvents: function (container) {
        const self = this;

        // Clic en carpetas o archivos
        container.querySelectorAll('.gdrive-card, .gdrive-name-cell').forEach(el => {
            el.addEventListener('click', function (e) {
                if (e.target.closest('.card-actions') || e.target.closest('.btn-action') || e.target.closest('a')) return;
                const card = el.closest('[data-id]');
                if (!card) return;
                const isFolder = card.dataset.isFolder === 'true';
                const id = card.dataset.id;

                if (isFolder) {
                    self.loadFolder(id);
                } else {
                    const item = (self.lastItemsData || []).find(i => i.id === id);
                    if (item) self.openViewer(item);
                }
            });
        });

        // Botón Previsualizar Visor Interno
        container.querySelectorAll('.btn-view').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                const card = btn.closest('[data-id]');
                const id = card.dataset.id;
                const item = (self.lastItemsData || []).find(i => i.id === id);
                if (item) self.openViewer(item);
            });
        });

        // Botón Seleccionar
        container.querySelectorAll('.btn-select').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                const card = btn.closest('[data-id]');
                if (self.onSelectCallback) {
                    self.onSelectCallback({
                        id: card.dataset.id,
                        direct_link: card.dataset.link,
                        web_view_link: card.dataset.view
                    });
                    if (self.isModalMode) self.closeModal();
                }
            });
        });

        // Clic en la caja de subida interactiva .gdrive-upload-dropzone-box
        const uploadBox = container.querySelector('.gdrive-upload-dropzone-box');
        if (uploadBox && uploadInput) {
            uploadBox.addEventListener('click', () => uploadInput.click());
        }

        // Botón Renombrar
        container.querySelectorAll('.btn-rename').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                const card = btn.closest('[data-id]');
                const currentName = card.querySelector('.card-title, .item-name-click').textContent.trim();
                self.openFolderModal('rename', card.dataset.id, currentName);
            });
        });

        // Botón Eliminar
        container.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                const card = btn.closest('[data-id]');
                self.openDeleteModal(card.dataset.id);
            });
        });
    },

    promptCreateFolder: function () {
        this.openFolderModal('create');
    },

    openFolderModal: function (mode, itemId = null, currentName = '') {
        const self = this;
        const modal = document.getElementById('gdriveFolderModal');
        const titleEl = document.getElementById('gdriveFolderModalTitle');
        const inputEl = document.getElementById('gdriveFolderNameInput');
        const saveBtn = document.getElementById('btnSaveGDriveFolder');

        if (!modal || !inputEl || !saveBtn) return;

        if (mode === 'create') {
            titleEl.innerHTML = '<i class="ph ph-folder-plus"></i> Nueva Carpeta';
            inputEl.value = '';
        } else {
            titleEl.innerHTML = '<i class="ph ph-pencil-simple"></i> Renombrar Elemento';
            inputEl.value = currentName;
        }

        modal.classList.add('active');
        setTimeout(() => inputEl.focus(), 100);

        // Remover handler previo
        const newSaveBtn = saveBtn.cloneNode(true);
        saveBtn.parentNode.replaceChild(newSaveBtn, saveBtn);

        newSaveBtn.addEventListener('click', function () {
            const name = inputEl.value.trim();
            if (!name) {
                alert('El nombre es requerido.');
                return;
            }

            modal.classList.remove('active');

            if (mode === 'create') {
                const formData = new FormData();
                formData.append('action', 'create_folder');
                formData.append('name', name);
                if (self.currentFolderId) formData.append('parent_id', self.currentFolderId);

                const apiUrl = self.getBaseUrl() + '/ajax/drive_manager.php';
                fetch(apiUrl, { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            if (window.showToast) window.showToast('Carpeta creada correctamente', 'success');
                            self.loadFolder(self.currentFolderId);
                        } else {
                            alert(data.error || 'Error al crear la carpeta');
                        }
                    });
            } else {
                self.renameItem(itemId, name);
            }
        });
    },

    openDeleteModal: function (itemId) {
        const self = this;
        const modal = document.getElementById('gdriveDeleteModal');
        const confirmBtn = document.getElementById('btnConfirmGDriveDelete');

        if (!modal || !confirmBtn) return;

        modal.classList.add('active');

        // Remover handler previo
        const newConfirmBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

        newConfirmBtn.addEventListener('click', function () {
            modal.classList.remove('active');
            self.deleteItem(itemId);
        });
    },

    renameItem: function (itemId, newName) {
        const self = this;
        const formData = new FormData();
        formData.append('action', 'rename');
        formData.append('item_id', itemId);
        formData.append('name', newName);

        const apiUrl = self.getBaseUrl() + '/ajax/drive_manager.php';

        fetch(apiUrl, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (window.showToast) window.showToast('Elemento renombrado', 'success');
                    self.loadFolder(self.currentFolderId);
                } else {
                    alert(data.error || 'Error al renombrar');
                }
            });
    },

    deleteItem: function (itemId) {
        const self = this;
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('item_id', itemId);

        const apiUrl = self.getBaseUrl() + '/ajax/drive_manager.php';

        fetch(apiUrl, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (window.showToast) window.showToast('Elemento eliminado de Drive', 'success');
                    self.loadFolder(self.currentFolderId);
                } else {
                    alert(data.error || 'Error al eliminar');
                }
            });
    },

    uploadFiles: function (files) {
        const self = this;
        const container = self.getContainer();
        const body = container ? container.querySelector('.gdrive-items-body') : null;

        if (body) {
            body.insertAdjacentHTML('afterbegin', `<div class="gdrive-uploading-banner"><i class="ph ph-cloud-arrow-up spinner"></i> Subiendo ${files.length} archivo(s) a Google Drive...</div>`);
        }

        const apiUrl = self.getBaseUrl() + '/ajax/drive_manager.php';
        let completed = 0;
        Array.from(files).forEach(file => {
            const formData = new FormData();
            formData.append('action', 'upload');
            formData.append('file', file);
            if (self.currentFolderId) formData.append('folder_id', self.currentFolderId);

            fetch(apiUrl, { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    completed++;
                    if (completed === files.length) {
                        if (window.showToast) window.showToast('Archivos subidos exitosamente', 'success');
                        self.loadFolder(self.currentFolderId);
                    }
                })
                .catch(() => {
                    completed++;
                    if (completed === files.length) self.loadFolder(self.currentFolderId);
                });
        });
    },

    openViewer: function (item) {
        if (!item || item.is_folder) return;

        const modalEl = document.getElementById('gdriveViewerModal');
        const titleEl = document.getElementById('gdriveViewerTitle');
        const bodyEl = document.getElementById('gdriveViewerBody');
        const downloadBtn = document.getElementById('gdriveViewerDownloadBtn');

        if (!modalEl || !bodyEl) return;

        let iconClass = 'ph-fill ph-file-text';
        if (item.category === 'image') iconClass = 'ph-fill ph-image text-primary';
        else if (item.category === 'video') iconClass = 'ph-fill ph-video-camera text-purple';

        if (titleEl) {
            titleEl.innerHTML = `<i class="${iconClass}"></i> ${this.escapeHtml(item.name)}`;
        }

        if (downloadBtn) {
            downloadBtn.href = item.web_content_link || item.direct_link || item.web_view_link;
        }

        bodyEl.innerHTML = '<div class="text-light p-4" style="color:#ffffff;"><i class="ph ph-spinner spinner fs-2"></i> Cargando visor...</div>';
        modalEl.classList.add('active');

        setTimeout(() => {
            if (item.category === 'image') {
                const imgUrl = item.thumbnail_link ? item.thumbnail_link.replace(/=s\d+/, '=s1200') : item.direct_link;
                bodyEl.innerHTML = `<img src="${imgUrl}" referrerpolicy="no-referrer" style="max-width: 100%; max-height: 100%; object-fit: contain; padding: 20px; box-sizing: border-box;" alt="${this.escapeHtml(item.name)}" />`;
            } else if (item.category === 'video') {
                const videoUrl = item.web_content_link || item.direct_link;
                bodyEl.innerHTML = `
                    <video controls autoplay style="width: 100%; height: 100%; max-height: 80vh; outline: none;">
                        <source src="${videoUrl}" type="${item.mime_type}">
                        <iframe src="https://drive.google.com/file/d/${item.id}/preview" style="width: 100%; height: 100%; border: none;"></iframe>
                    </video>`;
            } else {
                // Documentos (PDF, Word, Excel, Docs, TXT)
                const embedUrl = `https://drive.google.com/file/d/${item.id}/preview`;
                bodyEl.innerHTML = `<iframe src="${embedUrl}" style="width: 100%; height: 100%; border: none; background: #ffffff;"></iframe>`;
            }
        }, 100);
    },

    closeViewer: function () {
        const modalEl = document.getElementById('gdriveViewerModal');
        const bodyEl = document.getElementById('gdriveViewerBody');
        if (modalEl) {
            modalEl.classList.remove('active');
        }
        if (bodyEl) {
            bodyEl.innerHTML = '';
        }
    },

    openModal: function (onSelectCallback = null) {
        this.isModalMode = true;
        this.onSelectCallback = onSelectCallback;
        let modalEl = document.getElementById('gdriveModal');
        if (modalEl) {
            modalEl.classList.add('active');
            this.init({ isModal: true });
        }
    },

    closeModal: function () {
        let modalEl = document.getElementById('gdriveModal');
        if (modalEl) {
            modalEl.classList.remove('active');
        }
    },

    formatBytes: function (bytes, decimals = 1) {
        if (!bytes || bytes === 0) return '0 B';
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    },

    escapeHtml: function (string) {
        return String(string).replace(/[&<>"']/g, function (s) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[s];
        });
    }
};
