document.addEventListener('DOMContentLoaded', () => {
    initDarkMode();
    initModals();
    initSidebar();
    initFileUploads();
});

function initFileUploads() {
    const fileInputs = document.querySelectorAll('input[type="file"]');
    
    fileInputs.forEach(input => {
        // Evitar envolver dos veces o inputs excluidos
        if (input.closest('.file-drop-area') || input.classList.contains('no-dropzone')) return;

        // Crear wrapper
        const wrapper = document.createElement('div');
        wrapper.className = 'file-drop-area';
        
        // Envolver input
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);
        
        // Contenido de la caja
        const contentDiv = document.createElement('div');
        contentDiv.className = 'file-drop-content';
        
        const icon = document.createElement('i');
        icon.className = 'ph ph-cloud-arrow-up';
        
        const msg = document.createElement('div');
        msg.className = 'file-msg';
        msg.textContent = 'Select a file or drag and drop here';
        
        const desc = document.createElement('div');
        desc.className = 'file-desc';
        desc.textContent = 'JPG, PNG or PDF, file size no more than 10MB';
        
        contentDiv.appendChild(icon);
        contentDiv.appendChild(msg);
        contentDiv.appendChild(desc);
        wrapper.appendChild(contentDiv);
        
        // Lógica de previsualización (data-current)
        const currentUrl = input.getAttribute('data-current');
        if (currentUrl && currentUrl.trim() !== '') {
            wrapper.classList.add('has-image');
            wrapper.style.backgroundImage = `url('${currentUrl}')`;
            contentDiv.style.opacity = '0';
            
            // Botón de eliminar
            const btnDelete = document.createElement('button');
            btnDelete.type = 'button';
            btnDelete.className = 'btn-delete-image';
            btnDelete.innerHTML = '&times;';
            wrapper.appendChild(btnDelete);
            
            btnDelete.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                // Limpiar previsualización
                wrapper.classList.remove('has-image');
                wrapper.style.backgroundImage = 'none';
                contentDiv.style.opacity = '1';
                input.value = ''; // Limpiar input
                
                // Actualizar input oculto de eliminación si existe
                const hiddenDelete = document.getElementById(`delete_${input.id}`);
                if (hiddenDelete) {
                    hiddenDelete.value = '1';
                }
                
                btnDelete.remove();
            });
        }
        
        // Añadir eventos de subida
        input.addEventListener('change', (e) => {
            if (e.target.files && e.target.files[0]) {
                const file = e.target.files[0];
                msg.textContent = file.name;
                
                // Restablecer hidden delete si el usuario sube algo nuevo
                const hiddenDelete = document.getElementById(`delete_${input.id}`);
                if (hiddenDelete) hiddenDelete.value = '0';
                
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = (ev) => {
                        wrapper.classList.add('has-image');
                        wrapper.style.backgroundImage = `url('${ev.target.result}')`;
                        contentDiv.style.opacity = '0';
                        
                        // Si no hay botón de borrar, añadirlo para permitirle cancelar
                        if (!wrapper.querySelector('.btn-delete-image')) {
                            const btnDelete = document.createElement('button');
                            btnDelete.type = 'button';
                            btnDelete.className = 'btn-delete-image';
                            btnDelete.innerHTML = '&times;';
                            wrapper.appendChild(btnDelete);
                            btnDelete.addEventListener('click', (ev2) => {
                                ev2.preventDefault();
                                ev2.stopPropagation();
                                wrapper.classList.remove('has-image');
                                wrapper.style.backgroundImage = 'none';
                                contentDiv.style.opacity = '1';
                                input.value = '';
                                msg.textContent = 'Select a file or drag and drop here';
                                btnDelete.remove();
                            });
                        }
                    };
                    reader.readAsDataURL(file);
                }
            } else {
                msg.textContent = 'Select a file or drag and drop here';
            }
        });
        
        input.addEventListener('dragenter', () => wrapper.classList.add('is-active'));
        input.addEventListener('dragleave', () => wrapper.classList.remove('is-active'));
        input.addEventListener('drop', () => wrapper.classList.remove('is-active'));
    });
}

function initDarkMode() {
    // Old toggle (if still present somewhere)
    const toggleInput = document.getElementById('darkModeToggle');
    // New pill toggle buttons
    const btnLight = document.getElementById('btnThemeLight');
    const btnDark = document.getElementById('btnThemeDark');
    
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const storedTheme = localStorage.getItem('theme');
    
    const isDarkTheme = storedTheme === 'dark' || (!storedTheme && prefersDark);
    
    const applyTheme = (isDark) => {
        if (isDark) {
            document.body.classList.add('dark-theme');
            localStorage.setItem('theme', 'dark');
            if(toggleInput) toggleInput.checked = true;
            if(btnDark) {
                btnDark.classList.add('active');
                btnLight.classList.remove('active');
            }
        } else {
            document.body.classList.remove('dark-theme');
            localStorage.setItem('theme', 'light');
            if(toggleInput) toggleInput.checked = false;
            if(btnLight) {
                btnLight.classList.add('active');
                btnDark.classList.remove('active');
            }
        }
    };

    // Apply on load
    applyTheme(isDarkTheme);

    // Event listeners
    if (toggleInput) {
        toggleInput.addEventListener('change', (e) => applyTheme(e.target.checked));
    }
    
    if (btnLight) {
        btnLight.addEventListener('click', () => applyTheme(false));
    }
    
    if (btnDark) {
        btnDark.addEventListener('click', () => applyTheme(true));
    }
}

function initModals() {
    const modalTriggers = document.querySelectorAll('[data-modal]');
    const closeBtns = document.querySelectorAll('.close-modal');

    modalTriggers.forEach(trigger => {
        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            const modalId = trigger.getAttribute('data-modal');
            const modal = document.getElementById(modalId);
            if (modal) {
                // Si es un modal de confirmación dinámica
                if(trigger.hasAttribute('data-action-url')) {
                    const confirmBtn = modal.querySelector('.btn-confirm');
                    if(confirmBtn) {
                        confirmBtn.href = trigger.getAttribute('data-action-url');
                    }
                }
                modal.classList.add('active');
            }
        });
    });

    closeBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            btn.closest('.modal-overlay').classList.remove('active');
        });
    });

    // Close on click outside — only if both mousedown and mouseup are on the overlay itself
    let modalMouseDownTarget = null;
    document.addEventListener('mousedown', (e) => { modalMouseDownTarget = e.target; });
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('mouseup', (e) => {
            if (e.target === modal && modalMouseDownTarget === modal) {
                modal.classList.remove('active');
            }
            modalMouseDownTarget = null;
        });
    });
}

// Global UI Helper Functions
window.showGlobalDeleteModal = (callback) => {
    const modal = document.getElementById('deleteModal');
    if (!modal) return;
    
    const btnConfirm = document.getElementById('btnConfirmDelete');
    
    // Quitar event listeners anteriores clonando el botón
    const newBtnConfirm = btnConfirm.cloneNode(true);
    btnConfirm.parentNode.replaceChild(newBtnConfirm, btnConfirm);
    
    newBtnConfirm.addEventListener('click', async () => {
        newBtnConfirm.disabled = true;
        newBtnConfirm.innerHTML = 'Eliminando...';
        await callback();
        newBtnConfirm.disabled = false;
        newBtnConfirm.innerHTML = 'Eliminar';
        modal.classList.remove('active');
    });
    
    modal.classList.add('active');
};

function initSidebar() {
    const toggleBtn = document.getElementById('sidebarToggle');
    const internalBtn = document.getElementById('sidebarInternalToggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (sidebar) {
        // Initialize collapsed state on desktop
        try {
            if (window.innerWidth > 768 && localStorage.getItem('sidebar_collapsed') === 'true') {
                sidebar.classList.add('collapsed');
            }
        } catch (e) { console.warn('localStorage not available'); }

        const toggleAction = (e) => {
            e.stopPropagation();
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('active');
            } else {
                sidebar.classList.toggle('collapsed');
                try {
                    localStorage.setItem('sidebar_collapsed', sidebar.classList.contains('collapsed'));
                } catch (err) {}
            }
        };

        if (toggleBtn) toggleBtn.addEventListener('click', toggleAction);
        if (internalBtn) internalBtn.addEventListener('click', toggleAction);
    }

    // Cerrar sidebar al hacer clic fuera (móvil)
    document.addEventListener('click', (e) => {
        if (sidebar && sidebar.classList.contains('active')) {
            // Si el clic no fue dentro del sidebar ni en el botón toggle
            if (!sidebar.contains(e.target) && (!toggleBtn || !toggleBtn.contains(e.target))) {
                sidebar.classList.remove('active');
            }
        }
    });

    // Sidebar Submenu Logic
    const sidebarToggles = document.querySelectorAll('.sidebar-toggle');
    sidebarToggles.forEach(toggle => {
        toggle.addEventListener('click', (e) => {
            e.preventDefault();
            const parentItem = toggle.closest('.sidebar-item');
            if (!parentItem) return;

            const submenu = parentItem.querySelector('.sidebar-submenu');
            if (!submenu) return;

            const isOpen = parentItem.classList.contains('open');

            // Optionally close other open submenus if you want an accordion effect
            /*
            document.querySelectorAll('.sidebar-item.open').forEach(item => {
                if (item !== parentItem) {
                    item.classList.remove('open');
                    const sub = item.querySelector('.sidebar-submenu');
                    if(sub) $(sub).slideUp(200); // or use standard JS for slideUp
                }
            });
            */

            if (isOpen) {
                parentItem.classList.remove('open');
                // Simple toggle for now (could add smooth height animation later)
                submenu.style.display = 'none';
            } else {
                parentItem.classList.add('open');
                submenu.style.display = 'flex';
            }
        });
    });
}

// Helper para llamadas AJAX (Fetch API)
async function fetchPost(url, data) {
    try {
        const response = await fetch(url, {
            method: 'POST',
            body: data
        });
        return await response.json();
    } catch (error) {
        console.error('Error fetching data:', error);
        return { success: false, message: 'Network error' };
    }
}

// Sistema de Notificaciones Globales (Toast)
window.showToast = function(message, type = 'success') {
    const config = window.AppConfig || { toastPosition: 'top-right', toastStyle: 'card' };
    
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        document.body.appendChild(container);
    }
    
    // Apply position class to container
    container.className = `toast-pos-${config.toastPosition}`;

    const toast = document.createElement('div');
    toast.className = `app-toast toast-${type} toast-style-${config.toastStyle}`;
    
    // Icon depends on type
    const icon = type === 'success' ? 'ph-check-circle' : 'ph-warning-circle';
    
    toast.innerHTML = `
        <div class="toast-icon"><i class="ph ${icon}"></i></div>
        <div class="toast-message">${message}</div>
        <button class="toast-close">&times;</button>
    `;

    container.appendChild(toast);

    // Trigger animation
    setTimeout(() => toast.classList.add('show'), 10);

    // Close button
    toast.querySelector('.toast-close').addEventListener('click', () => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    });

    // Auto dismiss
    setTimeout(() => {
        if (toast.parentNode) {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }
    }, 3000);
};
