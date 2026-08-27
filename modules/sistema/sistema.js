/**
 * TurboSaaS - Módulo Sistema (Lógica Frontend)
 */

document.addEventListener('DOMContentLoaded', () => {
    initSistemaTabs();
    initRestoreDropzone();
});

/* ==========================================================================
   Gestión de Pestañas (Tabs)
   ========================================================================== */
function initSistemaTabs() {
    const tabButtons = document.querySelectorAll('.sistema-tab-btn');
    const tabPanes = document.querySelectorAll('.sistema-tab-pane');

    function activateTab(tabId) {
        tabButtons.forEach(btn => {
            if (btn.getAttribute('data-tab') === tabId) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });

        tabPanes.forEach(pane => {
            if (pane.id === 'tab-' + tabId) {
                pane.classList.add('active');
            } else {
                pane.classList.remove('active');
            }
        });

        // Guardar estado en sessionStorage
        try {
            sessionStorage.setItem('turbosaas_sistema_active_tab', tabId);
        } catch(e) {}
    }

    tabButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const targetTab = btn.getAttribute('data-tab');
            activateTab(targetTab);
        });
    });

    // Restaurar pestaña guardada o por hash
    let initialTab = 'backups';
    const hashTab = window.location.hash.replace('#', '');
    if (hashTab && document.getElementById('tab-' + hashTab)) {
        initialTab = hashTab;
    } else {
        const savedTab = sessionStorage.getItem('turbosaas_sistema_active_tab');
        if (savedTab && document.getElementById('tab-' + savedTab)) {
            initialTab = savedTab;
        }
    }
    activateTab(initialTab);
}

/* ==========================================================================
   Generación de Copias de Seguridad (Backup)
   ========================================================================== */
function realizarBackup(type) {
    const btnDb = document.getElementById('btnBackupDb');
    const btnFull = document.getElementById('btnBackupFull');
    const status = document.getElementById('backupStatus');
    
    if (btnDb) btnDb.disabled = true;
    if (btnFull) btnFull.disabled = true;
    
    if (type === 'full') {
        if (btnFull) btnFull.innerHTML = '<i class="ph ph-spinner ph-spin"></i> Generando ZIP completo...';
    } else {
        if (btnDb) btnDb.innerHTML = '<i class="ph ph-spinner ph-spin"></i> Generando SQL...';
    }
    
    const baseUrl = window.BASE_URL || '';
    window.location.href = baseUrl + '/modules/sistema/ajax/backup.php?type=' + type;
    
    setTimeout(() => {
        if (btnDb) {
            btnDb.disabled = false;
            btnDb.innerHTML = '<i class="ph ph-download-simple"></i> Descargar Base de Datos (.sql)';
        }
        if (btnFull) {
            btnFull.disabled = false;
            btnFull.innerHTML = '<i class="ph ph-download-simple"></i> Descargar Backup Completo (.zip)';
        }
        if (status) {
            status.style.display = 'flex';
            status.className = 'sistema-alert success';
            status.innerHTML = '<i class="ph ph-check-circle fs-5"></i> <div><strong>¡Descarga iniciada!</strong> Tu archivo de respaldo se ha generado exitosamente.</div>';
        }
    }, 4500);
}

/* ==========================================================================
   Zona Drag & Drop Especializada para Restauración
   ========================================================================== */
let selectedRestoreFile = null;

function initRestoreDropzone() {
    const dropzone = document.getElementById('restoreDropzone');
    const fileInput = document.getElementById('restoreFileInput');
    const selectedBox = document.getElementById('restoreSelectedBox');
    const fileNameEl = document.getElementById('restoreFileName');
    const fileSizeEl = document.getElementById('restoreFileSize');
    const fileIconEl = document.getElementById('restoreFileIcon');
    const btnRemove = document.getElementById('btnRemoveRestoreFile');
    const btnSubmit = document.getElementById('btnRestoreSubmit');

    if (!dropzone || !fileInput) return;

    // Clic en la caja abre el selector
    dropzone.addEventListener('click', () => {
        fileInput.click();
    });

    // Drag & Drop visual events
    ['dragenter', 'dragover'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.add('dragover');
        });
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.remove('dragover');
        });
    });

    // Drop file
    dropzone.addEventListener('drop', (e) => {
        if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
            handleFileSelection(e.dataTransfer.files[0]);
        }
    });

    // Change input file
    fileInput.addEventListener('change', (e) => {
        if (e.target.files && e.target.files.length > 0) {
            handleFileSelection(e.target.files[0]);
        }
    });

    // Remover archivo seleccionado
    if (btnRemove) {
        btnRemove.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            resetRestoreFile();
        });
    }

    function handleFileSelection(file) {
        const ext = file.name.split('.').pop().toLowerCase();
        if (ext !== 'sql' && ext !== 'zip') {
            alert('Formato no soportado. Por favor, selecciona únicamente archivos con extensión .sql o .zip.');
            resetRestoreFile();
            return;
        }

        selectedRestoreFile = file;
        fileNameEl.textContent = file.name;
        fileSizeEl.textContent = formatBytes(file.size);

        if (ext === 'sql') {
            fileIconEl.innerHTML = '<i class="ph ph-file-sql"></i>';
            fileIconEl.style.background = '#3b82f6';
        } else {
            fileIconEl.innerHTML = '<i class="ph ph-file-zip"></i>';
            fileIconEl.style.background = '#f59e0b';
        }

        dropzone.style.display = 'none';
        selectedBox.style.display = 'flex';
        if (btnSubmit) btnSubmit.disabled = false;
    }

    function resetRestoreFile() {
        selectedRestoreFile = null;
        fileInput.value = '';
        dropzone.style.display = 'block';
        selectedBox.style.display = 'none';
        if (btnSubmit) btnSubmit.disabled = true;
    }
}

function formatBytes(bytes, decimals = 2) {
    if (!+bytes) return '0 Bytes';
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return `${parseFloat((bytes / Math.pow(k, i)).toFixed(dm))} ${sizes[i]}`;
}

/* ==========================================================================
   Confirmación y Ejecución de Restauración
   ========================================================================== */
function solicitarConfirmacionRestaurar(e) {
    e.preventDefault();
    if (!selectedRestoreFile) {
        alert('Por favor selecciona un archivo de respaldo antes de continuar.');
        return;
    }

    const modal = document.getElementById('restoreConfirmModal');
    const modalFileName = document.getElementById('modalRestoreFileName');
    if (modalFileName) {
        modalFileName.textContent = selectedRestoreFile.name;
    }
    if (modal) {
        modal.style.display = 'flex';
    }
}

function cerrarModalRestaurar() {
    const modal = document.getElementById('restoreConfirmModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function ejecutarRestauracionDefinitiva() {
    cerrarModalRestaurar();
    if (!selectedRestoreFile) return;

    const btnSubmit = document.getElementById('btnRestoreSubmit');
    const status = document.getElementById('restoreStatus');
    const progressContainer = document.getElementById('restoreProgressContainer');
    const progressBar = document.getElementById('restoreProgressBar');
    const progressText = document.getElementById('restoreProgressText');

    if (btnSubmit) {
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<i class="ph ph-spinner ph-spin"></i> Procesando Restauración...';
    }

    if (status) status.style.display = 'none';
    if (progressContainer) progressContainer.style.display = 'block';
    if (progressBar) progressBar.style.width = '0%';
    if (progressText) progressText.textContent = 'Subiendo archivo de respaldo (0%)...';

    const formData = new FormData();
    formData.append('backup_file', selectedRestoreFile);
    formData.append('csrf_token', window.CSRF_TOKEN || '');

    const xhr = new XMLHttpRequest();
    const baseUrl = window.BASE_URL || '';
    xhr.open('POST', baseUrl + '/modules/sistema/ajax/restore.php', true);

    xhr.upload.onprogress = function(e) {
        if (e.lengthComputable) {
            const percent = Math.round((e.loaded / e.total) * 100);
            if (progressBar) progressBar.style.width = percent + '%';
            if (progressText) {
                if (percent < 100) {
                    progressText.textContent = `Subiendo archivo al servidor (${percent}%)...`;
                } else {
                    progressText.textContent = 'Descomprimiendo y restaurando base de datos (Espere por favor)...';
                }
            }
        }
    };

    xhr.onload = function() {
        if (btnSubmit) {
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = '<i class="ph ph-warning-circle"></i> Restaurar Copia de Seguridad';
        }
        if (progressContainer) progressContainer.style.display = 'none';

        try {
            const data = JSON.parse(xhr.responseText);
            if (status) {
                status.style.display = 'flex';
                if (data.success) {
                    status.className = 'sistema-alert success';
                    status.innerHTML = `<i class="ph ph-check-circle fs-5"></i> <div><strong>¡Restauración exitosa!</strong> ${data.message}</div>`;
                    
                    // Resetear formulario
                    const btnRemove = document.getElementById('btnRemoveRestoreFile');
                    if (btnRemove) btnRemove.click();

                    setTimeout(() => {
                        window.location.reload();
                    }, 2500);
                } else {
                    status.className = 'sistema-alert danger';
                    status.innerHTML = `<i class="ph ph-x-circle fs-5"></i> <div><strong>Error en restauración:</strong> ${data.message}</div>`;
                }
            }
        } catch (err) {
            if (status) {
                status.style.display = 'flex';
                status.className = 'sistema-alert danger';
                status.innerHTML = '<i class="ph ph-x-circle fs-5"></i> <div><strong>Error crítico:</strong> Respuesta inesperada del servidor.</div>';
            }
        }
    };

    xhr.onerror = function() {
        if (btnSubmit) {
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = '<i class="ph ph-warning-circle"></i> Restaurar Copia de Seguridad';
        }
        if (progressContainer) progressContainer.style.display = 'none';
        if (status) {
            status.style.display = 'flex';
            status.className = 'sistema-alert danger';
            status.innerHTML = '<i class="ph ph-wifi-x fs-5"></i> <div><strong>Error de red:</strong> No se pudo conectar con el servidor.</div>';
        }
    };

    xhr.send(formData);
}

/* ==========================================================================
   Actualizaciones y Git Center
   ========================================================================== */
function actualizarSistema() {
    if (!confirm('¿Deseas sincronizar y descargar los últimos cambios desde el repositorio de GitHub?')) {
        return;
    }

    const btn = document.getElementById('btnUpdateGit');
    const status = document.getElementById('updateStatus');
    const terminal = document.getElementById('gitTerminal');
    const terminalBody = document.getElementById('terminalBody');

    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="ph ph-spinner ph-spin"></i> Sincronizando con GitHub...';
    }

    if (status) status.style.display = 'none';
    if (terminal) terminal.style.display = 'block';
    if (terminalBody) terminalBody.textContent = 'Iniciando sincronización con el repositorio remoto...\n$ git fetch origin\n$ git pull\n';

    const formData = new FormData();
    formData.append('csrf_token', window.CSRF_TOKEN || '');

    const baseUrl = window.BASE_URL || '';
    fetch(baseUrl + '/modules/sistema/ajax/update.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="ph ph-arrows-clockwise"></i> Sincronizar con GitHub';
        }

        if (status) {
            status.style.display = 'flex';
            if (data.success) {
                status.className = 'sistema-alert success';
                status.innerHTML = `<i class="ph ph-check-circle fs-5"></i> <div><strong>¡Actualización exitosa!</strong> ${data.message}</div>`;
            } else {
                status.className = 'sistema-alert danger';
                status.innerHTML = `<i class="ph ph-x-circle fs-5"></i> <div><strong>Error de sincronización:</strong> ${data.message}</div>`;
            }
        }

        if (data.output && terminalBody) {
            terminalBody.textContent += '\n--- Resultado de la Ejecución ---\n' + data.output + '\n';
            terminalBody.scrollTop = terminalBody.scrollHeight;
        }

        if (data.success) {
            setTimeout(() => {
                window.location.reload();
            }, 3000);
        }
    })
    .catch(err => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="ph ph-arrows-clockwise"></i> Sincronizar con GitHub';
        }
        if (status) {
            status.style.display = 'flex';
            status.className = 'sistema-alert danger';
            status.innerHTML = `<i class="ph ph-wifi-x fs-5"></i> <div><strong>Error:</strong> ${err.message}</div>`;
        }
        if (terminalBody) {
            terminalBody.textContent += '\n[ERROR] Falló la petición: ' + err.message + '\n';
        }
    });
}

function copiarTerminal() {
    const terminalBody = document.getElementById('terminalBody');
    if (!terminalBody) return;
    
    navigator.clipboard.writeText(terminalBody.textContent).then(() => {
        const btn = document.getElementById('btnCopyTerminal');
        if (btn) {
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="ph ph-check"></i> ¡Copiado!';
            setTimeout(() => {
                btn.innerHTML = originalText;
            }, 2000);
        }
    }).catch(() => {
        alert('No se pudo copiar el registro.');
    });
}

function limpiarTerminal() {
    const terminalBody = document.getElementById('terminalBody');
    if (terminalBody) {
        terminalBody.textContent = 'Terminal limpiada.';
    }
}
