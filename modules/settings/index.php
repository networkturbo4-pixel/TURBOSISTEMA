<?php
require_once '../../config/db.php';
requireLogin();
requirePermission($pdo, 'settings');

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<style>
    /* App-like Tabs */
    .nav-tabs {
        background-color: var(--bg-color, #f4f6f9);
        padding: 6px;
        border-radius: 12px;
        display: inline-flex;
        gap: 8px;
        margin-bottom: 24px;
        border: 1px solid var(--border-color);
    }
    .nav-tabs .nav-link {
        color: var(--text-muted, #6c757d);
        border: none;
        border-radius: 8px;
        padding: 10px 24px;
        font-weight: 500;
        transition: all 0.3s ease;
        background: transparent;
    }
    .nav-tabs .nav-link:hover {
        color: var(--text-color);
        background-color: rgba(0,0,0,0.02);
    }
    .nav-tabs .nav-link.active {
        color: var(--primary-color, #0d6efd);
        background-color: var(--surface-color, #fff);
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    
    /* Section Card */
    .settings-section {
        background: var(--surface-color, #fff);
        padding: 32px;
        border-radius: 16px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        border: 1px solid var(--border-color);
    }
    
    .color-picker-wrapper {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .color-picker-wrapper input[type="color"] {
        width: 50px;
        height: 40px;
        padding: 0;
        border: none;
        border-radius: 8px;
        cursor: pointer;
    }
    .color-picker-wrapper input[type="color"]::-webkit-color-swatch-wrapper {
        padding: 0;
    }
    .color-picker-wrapper input[type="color"]::-webkit-color-swatch {
        border: none;
        border-radius: 8px;
    }
</style>

<div class="page-header-card">
    <div class="page-header-left">
        <div class="page-header-icon">
            <i class="ph ph-gear"></i>
        </div>
        <div class="page-header-info">
            <h2>Configuración del Sistema</h2>
            <p>Gestiona los ajustes generales, apariencia, roles y usuarios.</p>
        </div>
    </div>
    <div class="page-header-actions">
        <button type="button" class="btn btn-primary" id="btnSaveSettings">
            <i class="ph ph-floppy-disk"></i> Guardar Cambios
        </button>
    </div>
</div>

<ul class="nav nav-tabs" id="settingsTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="company-tab" data-bs-toggle="tab" data-bs-target="#company" type="button" role="tab">Empresa</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="appearance-tab" data-bs-toggle="tab" data-bs-target="#appearance" type="button" role="tab">Apariencia</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="roles-tab" data-bs-toggle="tab" data-bs-target="#roles" type="button" role="tab">Roles</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab">Usuarios</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="clients-tab" data-bs-toggle="tab" data-bs-target="#clients" type="button" role="tab">Clientes</button>
    </li>
</ul>

<form id="settingsForm" enctype="multipart/form-data">
    <div class="tab-content" id="settingsTabsContent">
        
        <!-- PESTAÑA EMPRESA -->
        <div class="tab-pane fade show active settings-section" id="company" role="tabpanel">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Razón Social / Nombre del Sistema</label>
                    <input type="text" class="form-control" name="settings[app_name]" id="app_name">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Eslogan</label>
                    <input type="text" class="form-control" name="settings[slogan]" id="slogan">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">RUC / Identificación</label>
                    <input type="text" class="form-control" name="settings[ruc]" id="ruc">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Correo Electrónico</label>
                    <input type="email" class="form-control" name="settings[contact_email]" id="contact_email">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Sitio Web</label>
                    <input type="url" class="form-control" name="settings[website]" id="website">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Teléfono Principal</label>
                    <input type="text" class="form-control" name="settings[phone_main]" id="phone_main">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Teléfono Secundario</label>
                    <input type="text" class="form-control" name="settings[phone_secondary]" id="phone_secondary">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Moneda Global</label>
                    <select class="form-select" name="settings[currency]" id="currency">
                        <option value="USD">USD ($)</option>
                        <option value="EUR">EUR (€)</option>
                        <option value="MXN">MXN ($)</option>
                        <option value="COP">COP ($)</option>
                        <option value="PEN">PEN (S/)</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Horarios Laborables</label>
                    <input type="text" class="form-control" name="settings[work_hours]" id="work_hours" placeholder="Ej: Lun-Vie 9:00 - 18:00">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Formato de Fecha</label>
                    <select class="form-select" name="settings[date_format]" id="date_format">
                        <option value="Y-m-d">YYYY-MM-DD</option>
                        <option value="d/m/Y">DD/MM/YYYY</option>
                        <option value="m/d/Y">MM/DD/YYYY</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- PESTAÑA APARIENCIA -->
        <div class="tab-pane fade settings-section" id="appearance" role="tabpanel">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Color de Fondo</label>
                    <div class="color-picker-wrapper">
                        <input type="color" name="settings[bg_color]" id="bg_color" value="#f8f9fa">
                        <input type="text" class="form-control" id="bg_color_text" readonly>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Color de Texto Principal</label>
                    <div class="color-picker-wrapper">
                        <input type="color" name="settings[text_color]" id="text_color" value="#333333">
                        <input type="text" class="form-control" id="text_color_text" readonly>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Color de Énfasis (Modo Claro)</label>
                    <div class="color-picker-wrapper">
                        <input type="color" name="settings[primary_color_light]" id="primary_color_light" value="#111827">
                        <input type="text" class="form-control" id="primary_color_light_text" readonly>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Color de Énfasis (Modo Oscuro)</label>
                    <div class="color-picker-wrapper">
                        <input type="color" name="settings[primary_color_dark]" id="primary_color_dark" value="#4361ee">
                        <input type="text" class="form-control" id="primary_color_dark_text" readonly>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Efecto Hover General</label>
                    <select class="form-select" name="settings[hover_effect]" id="hover_effect">
                        <option value="none">Ninguno</option>
                        <option value="scale">Escalar (Scale)</option>
                        <option value="shadow">Sombra (Shadow)</option>
                        <option value="glow">Brillo (Glow)</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Tipografía (Google Fonts)</label>
                    <select class="form-select" name="settings[typography]" id="typography">
                        <option value="Inter">Inter</option>
                        <option value="Roboto">Roboto</option>
                        <option value="Outfit">Outfit</option>
                        <option value="Poppins">Poppins</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Logo Modo Claro</label>
                    <input type="file" class="form-control" name="logo_light" id="logo_light" accept="image/*" data-current="">
                    <input type="hidden" name="delete_logo_light" id="delete_logo_light" value="0">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Logo Modo Oscuro</label>
                    <input type="file" class="form-control" name="logo_dark" id="logo_dark" accept="image/*" data-current="">
                    <input type="hidden" name="delete_logo_dark" id="delete_logo_dark" value="0">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Logo PWA</label>
                    <input type="file" class="form-control" name="logo_pwa" id="logo_pwa" accept="image/*" data-current="">
                    <input type="hidden" name="delete_logo_pwa" id="delete_logo_pwa" value="0">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Favicon</label>
                    <input type="file" class="form-control" name="favicon" id="favicon" accept=".ico,.png" data-current="">
                    <input type="hidden" name="delete_favicon" id="delete_favicon" value="0">
                </div>
                
                <h5 class="mt-4 border-bottom pb-2">Notificaciones Globales</h5>
                <div class="col-md-8 mb-3">
                    <label class="form-label">Texto del Banner (Vacío para deshabilitar)</label>
                    <input type="text" class="form-control" name="settings[global_notification_banner]" id="global_notification_banner" placeholder="Ej: Mantenimiento programado hoy a las 10 PM">
                </div>
                <div class="col-md-4 mb-3 d-flex align-items-end">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="global_notification_push" name="settings[global_notification_push]" value="1">
                        <label class="form-check-label" for="global_notification_push">Habilitar Notificaciones Push</label>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Posición de Notificaciones</label>
                    <select class="form-select" name="settings[toast_position]" id="toast_position">
                        <option value="top-right">Arriba - Derecha</option>
                        <option value="top-left">Arriba - Izquierda</option>
                        <option value="top-center">Arriba - Centro</option>
                        <option value="bottom-right">Abajo - Derecha</option>
                        <option value="bottom-left">Abajo - Izquierda</option>
                        <option value="bottom-center">Abajo - Centro</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Estilo de Notificaciones</label>
                    <select class="form-select" name="settings[toast_style]" id="toast_style">
                        <option value="card">Tarjeta (Sombra Ligera)</option>
                        <option value="minimal">Minimalista</option>
                        <option value="dark">Sombra Oscura</option>
                        <option value="pastel">Pastel</option>
                        <option value="border">Borde Completo</option>
                        <option value="neon">Neón</option>
                        <option value="flat">Plano</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- PESTAÑA ROLES -->
        <div class="tab-pane fade settings-section" id="roles" role="tabpanel">
            <div class="d-flex justify-content-between mb-3">
                <h4>Gestión de Roles</h4>
                <button type="button" class="btn btn-outline-primary btn-sm" id="btnNewRole">Crear Nuevo Rol</button>
            </div>
            <p class="text-muted">La gestión avanzada de roles y permisos se cargará aquí.</p>
            <div class="table-responsive">
                <table class="table table-hover" id="rolesTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Dinámico vía AJAX -->
                        <tr><td colspan="4" class="text-center">Cargando roles...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- PESTAÑA USUARIOS -->
        <div class="tab-pane fade settings-section" id="users" role="tabpanel">
            <div class="d-flex justify-content-between mb-3">
                <h4>Gestión de Usuarios</h4>
                <button type="button" class="btn btn-outline-primary btn-sm" id="btnNewUser">Crear Usuario (con PIN)</button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover" id="usersTable">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>PIN</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Dinámico vía AJAX -->
                        <tr><td colspan="5" class="text-center">Cargando usuarios...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- PESTAÑA CLIENTES -->
        <div class="tab-pane fade settings-section" id="clients" role="tabpanel">
            <div class="d-flex justify-content-between mb-3">
                <h4>Accesos de Clientes</h4>
            </div>
            <div class="table-responsive">
                <table class="table table-hover" id="clientsTable">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>DNI</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Dinámico vía AJAX -->
                        <tr><td colspan="5" class="text-center">Cargando clientes...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</form>

<!-- Modal Nuevo Rol -->
<div class="modal-overlay" id="roleModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="roleModalTitle">Crear Nuevo Rol</h3>
            <button class="btn close-modal" style="background:transparent; border:none; font-size:1.5rem; cursor:pointer;" onclick="document.getElementById('roleModal').classList.remove('active')">&times;</button>
        </div>
        <form id="roleForm">
            <input type="hidden" name="id" id="role_id_input">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Nombre del Rol</label>
                    <input type="text" name="name" class="form-control" required placeholder="Ej: Vendedor">
                </div>
                <div class="form-group">
                    <label class="form-label">Descripción</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Descripción breve del rol"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Permisos de Acceso a Módulos</label>
                    <div class="row">
                        <?php 
                        global $system_modules;
                        foreach ($system_modules as $key => $mod): 
                        ?>
                        <div class="col-md-6 mb-2">
                            <label class="form-check d-flex align-items-center" style="gap: 8px;">
                                <input type="checkbox" class="form-check-input" name="permissions[]" value="<?php echo htmlspecialchars($key); ?>" <?php echo $mod['default_access'] ? 'checked' : ''; ?>>
                                <span><i class="<?php echo htmlspecialchars($mod['icon']); ?>"></i> <?php echo htmlspecialchars($mod['name']); ?></span>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <small class="text-muted">Selecciona los módulos a los que este rol tendrá acceso.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('roleModal').classList.remove('active')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Rol</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Nuevo Usuario -->
<div class="modal-overlay" id="userModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="userModalTitle">Crear Nuevo Usuario</h3>
            <button class="btn close-modal" style="background:transparent; border:none; font-size:1.5rem; cursor:pointer;" onclick="document.getElementById('userModal').classList.remove('active')">&times;</button>
        </div>
        <form id="userForm">
            <input type="hidden" name="id" id="user_id_input">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Nombre Completo</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Correo Electrónico</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Rol Asignado</label>
                    <select name="role" id="userRoleSelect" class="form-select" required>
                        <option value="">Cargando roles...</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">PIN de Acceso (4-6 dígitos, Opcional)</label>
                    <input type="text" name="pin" id="userPinInput" class="form-control" placeholder="Ej: 1234">
                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="document.getElementById('userPinInput').value = Math.floor(1000 + Math.random() * 9000);">Generar PIN Aleatorio</button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('userModal').classList.remove('active')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Usuario</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Lógica para pestañas (Tabs)
    const tabLinks = document.querySelectorAll('.nav-tabs .nav-link');
    const tabPanes = document.querySelectorAll('.tab-content .tab-pane');

    tabLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            
            // Quitar clase activa de todos los links y panes
            tabLinks.forEach(btn => btn.classList.remove('active'));
            tabPanes.forEach(pane => {
                pane.classList.remove('show', 'active');
            });
            
            // Agregar clase activa al link clickeado y su panel
            link.classList.add('active');
            const targetId = link.getAttribute('data-bs-target').replace('#', '');
            const targetPane = document.getElementById(targetId);
            if(targetPane) {
                targetPane.classList.add('show', 'active');
            }
        });
    });

    // Sincronizar selectores de color con inputs de texto
    const syncColor = (colorId, textId) => {
        const colorInput = document.getElementById(colorId);
        const textInput = document.getElementById(textId);
        if(colorInput && textInput) {
            textInput.value = colorInput.value;
            colorInput.addEventListener('input', () => textInput.value = colorInput.value);
            textInput.addEventListener('input', () => colorInput.value = textInput.value);
        }
    };
    syncColor('bg_color', 'bg_color_text');
    syncColor('text_color', 'text_color_text');

    // Cargar configuraciones
    const loadSettings = async () => {
        try {
            const formData = new FormData();
            formData.append('action', 'get_settings');
            const res = await fetch('<?php echo BASE_URL; ?>/ajax/settings.php', {
                method: 'POST',
                body: formData
            }).then(r => r.json());

            if (res.success && res.data) {
                for (const [key, value] of Object.entries(res.data)) {
                    const el = document.getElementById(key);
                    if (el) {
                        if (el.type === 'checkbox' || el.type === 'radio') {
                            el.checked = (value == '1' || value == true);
                        } else if (el.type !== 'file') {
                            el.value = value;
                        }
                    }
                    if (key === 'bg_color') document.getElementById('bg_color_text').value = value;
                    if (key === 'text_color') document.getElementById('text_color_text').value = value;
                    if (key === 'primary_color_light') document.getElementById('primary_color_light_text').value = value;
                    if (key === 'primary_color_dark') document.getElementById('primary_color_dark_text').value = value;
                    
                    if (['logo_light', 'logo_dark', 'logo_pwa', 'favicon'].includes(key) && value) {
                        const input = document.getElementById(key);
                        if(input) {
                            input.setAttribute('data-current', '<?php echo BASE_URL; ?>/' + value);
                            const wrapper = input.closest('.file-drop-area');
                            if(wrapper) {
                                wrapper.classList.add('has-image');
                                wrapper.style.backgroundImage = `url('<?php echo BASE_URL; ?>/${value}')`;
                                const contentDiv = wrapper.querySelector('.file-drop-content');
                                if(contentDiv) contentDiv.style.opacity = '0';
                                
                                if(!wrapper.querySelector('.btn-delete-image')) {
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
                                        if(contentDiv) contentDiv.style.opacity = '1';
                                        input.value = '';
                                        const msg = wrapper.querySelector('.file-msg');
                                        if(msg) msg.textContent = 'Select a file or drag and drop here';
                                        
                                        const hiddenDelete = document.getElementById(`delete_${input.id}`);
                                        if (hiddenDelete) hiddenDelete.value = '1';
                                        
                                        btnDelete.remove();
                                    });
                                }
                            }
                        }
                    }
                }
            }
        } catch (error) {
            console.error('Error cargando configuraciones:', error);
        }
    };

    loadSettings();

    // Guardar configuraciones
    document.getElementById('btnSaveSettings').addEventListener('click', async () => {
        const form = document.getElementById('settingsForm');
        const formData = new FormData(form);
        formData.append('action', 'save_settings');

        // Checkbox states not in formData if unchecked, we should manually append them or just let the backend handle '0' if missing.
        if (!document.getElementById('global_notification_push').checked) {
            formData.append('settings[global_notification_push]', '0');
        }

        try {
            const res = await fetch('<?php echo BASE_URL; ?>/ajax/settings.php', {
                method: 'POST',
                body: formData
            }).then(r => r.json());

            if (res.success) {
                window.showToast('Configuraciones guardadas correctamente', 'success');
                setTimeout(() => window.location.reload(), 1500); // Recargar después del toast
            } else {
                window.showToast(res.message || 'Error al guardar', 'error');
            }
        } catch (error) {
            console.error(error);
            window.showToast('Error en el servidor', 'error');
        }
    });

    // ===== LÓGICA DE ROLES =====
    window.rolesData = [];
    const loadRoles = async () => {
        try {
            const res = await fetch('<?php echo BASE_URL; ?>/ajax/roles.php?action=list').then(r => r.json());
            const tbody = document.querySelector('#rolesTable tbody');
            const select = document.getElementById('userRoleSelect');
            
            if (res.success) {
                window.rolesData = res.data;
                tbody.innerHTML = '';
                select.innerHTML = '<option value="">Selecciona un rol...</option>';
                // Add default admin role if needed
                select.innerHTML += '<option value="admin">Administrador General (admin)</option>';
                
                if (res.data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center">No hay roles personalizados creados.</td></tr>';
                } else {
                    res.data.forEach(role => {
                        tbody.innerHTML += `
                            <tr>
                                <td data-label="ID">#${role.id}</td>
                                <td data-label="Nombre">${role.name}</td>
                                <td data-label="Descripción">${role.description}</td>
                                <td data-label="Acciones">
                                    <button type="button" class="btn btn-sm btn-outline-primary" style="padding: 2px 8px; font-size: 0.8rem;" onclick="editRole(${role.id})">Editar</button>
                                    <button type="button" class="table-btn-danger" onclick="deleteRole(${role.id})">Eliminar</button>
                                </td>
                            </tr>
                        `;
                        select.innerHTML += `<option value="${role.name}">${role.name}</option>`;
                    });
                }
            }
        } catch (e) {
            console.error(e);
        }
    };

    window.editRole = (id) => {
        const role = window.rolesData.find(r => r.id == id);
        if(!role) return;
        
        document.getElementById('roleModalTitle').innerText = 'Editar Rol';
        document.getElementById('role_id_input').value = role.id;
        document.querySelector('#roleForm [name="name"]').value = role.name;
        document.querySelector('#roleForm [name="description"]').value = role.description;
        
        // Reset permissions
        document.querySelectorAll('#roleForm input[name="permissions[]"]').forEach(cb => cb.checked = false);
        // Check assigned permissions
        if (role.permissions) {
            role.permissions.forEach(p => {
                const cb = document.querySelector(`#roleForm input[name="permissions[]"][value="${p}"]`);
                if(cb) cb.checked = true;
            });
        }
        
        document.getElementById('roleModal').classList.add('active');
    };

    window.deleteRole = (id) => {
        window.showGlobalDeleteModal(async () => {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);
            try {
                const res = await fetch('<?php echo BASE_URL; ?>/ajax/roles.php', {
                    method: 'POST',
                    body: formData
                }).then(r => r.json());
                if (res.success) {
                    window.showToast(res.message, 'success');
                    loadRoles();
                } else {
                    window.showToast(res.message, 'error');
                }
            } catch (error) {
                console.error(error);
                window.showToast('Error en el servidor', 'error');
            }
        });
    };

    document.getElementById('btnNewRole').addEventListener('click', () => {
        document.getElementById('roleForm').reset();
        document.getElementById('role_id_input').value = '';
        document.getElementById('roleModalTitle').innerText = 'Crear Nuevo Rol';
        document.getElementById('roleModal').classList.add('active');
    });

    document.getElementById('roleForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const isUpdate = formData.get('id') !== '';
        formData.append('action', isUpdate ? 'update' : 'create');
        try {
            const res = await fetch('<?php echo BASE_URL; ?>/ajax/roles.php', {
                method: 'POST',
                body: formData
            }).then(r => r.json());
            if (res.success) {
                window.showToast(res.message, 'success');
                document.getElementById('roleModal').classList.remove('active');
                e.target.reset();
                loadRoles();
            } else {
                window.showToast(res.message, 'error');
            }
        } catch (error) {
            console.error(error);
            window.showToast('Error en el servidor', 'error');
        }
    });

    // ===== LÓGICA DE USUARIOS =====
    window.usersData = [];
    const loadUsers = async () => {
        try {
            const res = await fetch('<?php echo BASE_URL; ?>/ajax/users.php?action=list').then(r => r.json());
            const tbodyUsers = document.querySelector('#usersTable tbody');
            const tbodyClients = document.querySelector('#clientsTable tbody');
            if (res.success) {
                window.usersData = res.data;
                tbodyUsers.innerHTML = '';
                tbodyClients.innerHTML = '';
                
                const systemUsers = res.data.filter(u => u.role.toLowerCase() !== 'cliente');
                const clientUsers = res.data.filter(u => u.role.toLowerCase() === 'cliente');

                if (systemUsers.length === 0) {
                    tbodyUsers.innerHTML = '<tr><td colspan="5" class="text-center">No hay usuarios del sistema.</td></tr>';
                } else {
                    systemUsers.forEach(user => {
                        tbodyUsers.innerHTML += `
                            <tr>
                                <td data-label="Nombre">${user.name}</td>
                                <td data-label="Email">${user.email}</td>
                                <td data-label="Rol"><span class="table-badge-dark">${user.role}</span></td>
                                <td data-label="PIN">${user.pin || 'Sin PIN'}</td>
                                <td data-label="Acciones">
                                    <button type="button" class="btn btn-sm btn-outline-primary" style="padding: 2px 8px; font-size: 0.8rem;" onclick="editUser(${user.id})">Editar</button>
                                    <button type="button" class="table-btn-danger" onclick="deleteUser(${user.id})">Eliminar</button>
                                </td>
                            </tr>
                        `;
                    });
                }
                
                if (clientUsers.length === 0) {
                    tbodyClients.innerHTML = '<tr><td colspan="5" class="text-center">No hay clientes con acceso al sistema.</td></tr>';
                } else {
                    clientUsers.forEach(user => {
                        tbodyClients.innerHTML += `
                            <tr>
                                <td data-label="Nombre">${user.name}</td>
                                <td data-label="Email">${user.email}</td>
                                <td data-label="Rol"><span class="table-badge-dark">${user.role}</span></td>
                                <td data-label="DNI">${user.dni || 'Sin DNI'}</td>
                                <td data-label="Acciones">
                                    <button type="button" class="btn btn-sm btn-outline-primary" style="padding: 2px 8px; font-size: 0.8rem;" onclick="editUser(${user.id})">Editar</button>
                                    <button type="button" class="table-btn-danger" onclick="deleteUser(${user.id})">Eliminar</button>
                                </td>
                            </tr>
                        `;
                    });
                }
            }
        } catch (e) {
            console.error(e);
        }
    };

    window.editUser = (id) => {
        const user = window.usersData.find(u => u.id == id);
        if(!user) return;
        
        document.getElementById('userModalTitle').innerText = 'Editar Usuario';
        document.getElementById('user_id_input').value = user.id;
        document.querySelector('#userForm [name="name"]').value = user.name;
        document.querySelector('#userForm [name="email"]').value = user.email;
        document.querySelector('#userForm [name="role"]').value = user.role;
        document.querySelector('#userForm [name="pin"]').value = user.pin || '';
        
        document.getElementById('userModal').classList.add('active');
    };

    window.deleteUser = (id) => {
        window.showGlobalDeleteModal(async () => {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);
            try {
                const res = await fetch('<?php echo BASE_URL; ?>/ajax/users.php', {
                    method: 'POST',
                    body: formData
                }).then(r => r.json());
                if (res.success) {
                    window.showToast(res.message, 'success');
                    loadUsers();
                } else {
                    window.showToast(res.message, 'error');
                }
            } catch (error) {
                console.error(error);
                window.showToast('Error en el servidor', 'error');
            }
        });
    };

    document.getElementById('btnNewUser').addEventListener('click', () => {
        document.getElementById('userForm').reset();
        document.getElementById('user_id_input').value = '';
        document.getElementById('userModalTitle').innerText = 'Crear Nuevo Usuario';
        document.getElementById('userModal').classList.add('active');
    });

    document.getElementById('userForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const isUpdate = formData.get('id') !== '';
        formData.append('action', isUpdate ? 'update' : 'create');
        try {
            const res = await fetch('<?php echo BASE_URL; ?>/ajax/users.php', {
                method: 'POST',
                body: formData
            }).then(r => r.json());
            if (res.success) {
                window.showToast(res.message, 'success');
                document.getElementById('userModal').classList.remove('active');
                e.target.reset();
                loadUsers();
            } else {
                window.showToast(res.message, 'error');
            }
        } catch (error) {
            console.error(error);
            window.showToast('Error en el servidor', 'error');
        }
    });

    // Load initial data
    loadRoles();
    loadUsers();
});
</script>

<?php include '../../includes/footer.php'; ?>
