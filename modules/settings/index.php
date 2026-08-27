<?php
require_once '../../config/db.php';
requireLogin();
requirePermission($pdo, 'settings');

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<style>
    /* Builder Styles */
    .blocks-container {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .builder-block {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 12px 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        cursor: grab;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .builder-block:active {
        cursor: grabbing;
        transform: scale(0.98);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .builder-block.dragging {
        opacity: 0.5;
    }
    .block-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .block-icon {
        color: #64748b;
        font-size: 1.2rem;
    }
    .a4-preview-wrapper {
        background: #e2e8f0;
        padding: 20px;
        border-radius: 12px;
        display: flex;
        justify-content: center;
        overflow-x: auto;
    }
    .a4-preview {
        background: white;
        width: 210mm;
        min-height: 297mm;
        padding: 20mm;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        font-family: 'Arial', sans-serif;
        color: #333;
        transform: scale(0.7);
        transform-origin: top center;
        margin-bottom: -80mm; /* Adjust for scaling */
    }
    .preview-block {
        margin-bottom: 20px;
    }
    
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
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="receipts-tab" data-bs-toggle="tab" data-bs-target="#receipts" type="button" role="tab">Diseño de Recibos</button>
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
                    <label class="form-label">Logo Colapsado Claro</label>
                    <input type="file" class="form-control" name="logo_collapsed_light" id="logo_collapsed_light" accept="image/*" data-current="">
                    <input type="hidden" name="delete_logo_collapsed_light" id="delete_logo_collapsed_light" value="0">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Logo Colapsado Oscuro</label>
                    <input type="file" class="form-control" name="logo_collapsed_dark" id="logo_collapsed_dark" accept="image/*" data-current="">
                    <input type="hidden" name="delete_logo_collapsed_dark" id="delete_logo_collapsed_dark" value="0">
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

        <!-- PESTAÑA DISEÑO DE RECIBOS -->
        <div class="tab-pane fade settings-section" id="receipts" role="tabpanel">
            <div class="d-flex justify-content-between mb-3">
                <h4>Constructor Visual de Recibos (A4)</h4>
            </div>
            
            <input type="hidden" name="settings[receipt_template_json]" id="receipt_template_json">

            <div class="row">
                <!-- Columna Izquierda: Controles -->
                <div class="col-md-5">
                    <div class="builder-controls">
                        <h5 class="mb-3">Bloques Disponibles</h5>
                        <p class="text-muted small mb-3">Arrastra y suelta los bloques (desde el ícono) para reordenarlos, o haz clic en el interruptor para mostrarlos/ocultarlos.</p>
                        
                        <div id="blocks-list" class="blocks-container">
                            <!-- Los bloques se renderizarán vía JS -->
                        </div>
                        
                        <h5 class="mt-4 mb-3">Diseño Global</h5>
                        <div class="mb-3">
                            <label class="form-label">Plantilla Visual</label>
                            <select class="form-select builder-input" id="builder_template">
                                <option value="default">Clásica (Bordes simples)</option>
                                <option value="modern">Moderna (Colores de acento)</option>
                                <option value="minimal">Minimalista (Limpia)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Logo del Recibo</label>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-primary flex-grow-1" id="btnSelectLogoGDrive">
                                    <i class="ph-fill ph-google-drive-logo"></i> Elegir Logo en Drive
                                </button>
                                <button type="button" class="btn btn-outline-danger" id="btnRemoveLogo" style="display:none;">
                                    <i class="ph ph-trash"></i>
                                </button>
                            </div>
                            <input type="hidden" class="builder-input" id="builder_logo_url">
                        </div>

                        <h5 class="mt-4 mb-3">Configuración de Textos y Filtros</h5>
                        <div class="mb-3">
                            <label class="form-label">Título del Recibo</label>
                            <input type="text" class="form-control builder-input" id="builder_title" placeholder="Ej: Comprobante de Pago">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mensaje de Encabezado</label>
                            <textarea class="form-control builder-input" id="builder_header" rows="2" placeholder="Ej: Gracias por su preferencia..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mensaje al Pie (Notas)</label>
                            <textarea class="form-control builder-input" id="builder_footer" rows="2" placeholder="Ej: Conservar este recibo para cualquier reclamo..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Filtro de Historial (Mes)</label>
                            <select class="form-select builder-input" id="builder_history_month">
                                <option value="current">Mes Actual</option>
                                <option value="last">Mes Anterior</option>
                                <option value="all">Todos los meses</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Filtro de Historial (Año)</label>
                            <select class="form-select builder-input" id="builder_history_year">
                                <option value="current">Año Actual</option>
                                <option value="last">Año Anterior</option>
                                <option value="all">Todos los años</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Columna Derecha: Live Preview A4 -->
                <div class="col-md-7">
                    <div class="preview-container">
                        <h5 class="mb-3">Vista Previa (Formato A4)</h5>
                        <div class="a4-preview-wrapper">
                            <div class="a4-preview" id="a4-preview-content">
                                <!-- Contenido A4 renderizado por JS -->
                            </div>
                        </div>
                    </div>
                </div>
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
                    <label class="form-label">PIN de Acceso (8 dígitos, Único)</label>
                    <input type="text" name="pin" id="userPinInput" class="form-control" placeholder="Ej: 12345678" maxlength="8" minlength="8" pattern="[0-9]{8}" inputmode="numeric">
                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="document.getElementById('userPinInput').value = String(Math.floor(10000000 + Math.random() * 90000000));">Generar PIN Aleatorio</button>
                </div>
                <div class="form-group">
                    <label class="form-label">ID Biométrico (ZKTeco, Opcional)</label>
                    <input type="number" name="biometric_id" id="userBiometricIdInput" class="form-control" placeholder="Ej: 5">
                    <small class="text-muted">El ID que tiene asignado este usuario en el reloj físico de huellas.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('userModal').classList.remove('active')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Usuario</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Barcode de Usuario -->
<div class="modal-overlay" id="userBarcodeModal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3>Fotocheck: <span id="userBarcodeName"></span></h3>
            <button class="btn close-modal" style="background:transparent; border:none; font-size:1.5rem; cursor:pointer; color: var(--text-color);" onclick="document.getElementById('userBarcodeModal').classList.remove('active')">&times;</button>
        </div>
        <div class="modal-body">
            <div style="display: flex; flex-direction: column; gap: 20px;">
                <!-- Barcode Section -->
                <div style="background: var(--bg-color, #f8f9fa); border: 1px solid var(--border-color, #e5e7eb); border-radius: 12px; padding: 20px; text-align: center;">
                    <h5 style="margin-bottom: 15px; color: var(--text-color);">Código de Barras</h5>
                    <div style="background: #ffffff; padding: 15px; border-radius: 8px; display: inline-flex; justify-content: center; align-items: center; width: 100%; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                        <svg id="barcodeSVG" style="max-width: 100%; height: auto;"></svg>
                    </div>
                    <div style="margin-top: 15px; display: flex; gap: 10px; justify-content: center;">
                        <button type="button" class="btn btn-outline-primary" style="flex: 1;" id="btnDownloadBarcodePNG"><i class="ph ph-image"></i> PNG</button>
                        <button type="button" class="btn btn-outline-primary" style="flex: 1;" id="btnDownloadBarcodeSVG"><i class="ph ph-vector-curve"></i> SVG</button>
                    </div>
                </div>
                
                <!-- QR Section -->
                <div style="background: var(--bg-color, #f8f9fa); border: 1px solid var(--border-color, #e5e7eb); border-radius: 12px; padding: 20px; text-align: center;">
                    <h5 style="margin-bottom: 15px; color: var(--text-color);">Código QR</h5>
                    <div style="background: #ffffff; padding: 15px; border-radius: 8px; display: inline-flex; justify-content: center; align-items: center; box-shadow: 0 2px 8px rgba(0,0,0,0.05);" id="qrContainer"></div>
                    <div style="margin-top: 15px; display: flex; gap: 10px; justify-content: center;">
                        <button type="button" class="btn btn-outline-primary" style="flex: 1;" id="btnDownloadQrPNG"><i class="ph ph-image"></i> PNG</button>
                        <button type="button" class="btn btn-outline-primary" style="flex: 1;" id="btnDownloadQrSVG"><i class="ph ph-vector-curve"></i> SVG</button>
                    </div>
                </div>
            </div>
            
            <p class="text-muted mt-4 mb-0 text-center" style="font-size: 0.9rem;">
                <i class="ph ph-info"></i> Este código es único y sirve para registrar asistencia y añadir productos rápidamente.
            </p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary close-modal" onclick="document.getElementById('userBarcodeModal').classList.remove('active')">Cerrar</button>
        </div>
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
                    
                    if (['logo_light', 'logo_dark', 'logo_collapsed_light', 'logo_collapsed_dark', 'logo_pwa', 'favicon'].includes(key) && value) {
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
        if (!document.getElementById('global_notification_push')?.checked) {
            formData.append('settings[global_notification_push]', '0');
        }
        if (!document.getElementById('receipt_show_history')?.checked) {
            formData.append('settings[receipt_show_history]', '0');
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
        const tbodyUsers = document.querySelector('#usersTable tbody');
        const tbodyClients = document.querySelector('#clientsTable tbody');
        try {
            const res = await fetch('<?php echo BASE_URL; ?>/ajax/users.php?action=list').then(r => r.json());
            if (res.success && Array.isArray(res.data)) {
                window.usersData = res.data;
                tbodyUsers.innerHTML = '';
                tbodyClients.innerHTML = '';
                
                const systemUsers = res.data.filter(u => (u.role || '').toLowerCase() !== 'cliente');
                const clientUsers = res.data.filter(u => (u.role || '').toLowerCase() === 'cliente');

                if (systemUsers.length === 0) {
                    tbodyUsers.innerHTML = '<tr><td colspan="5" class="text-center">No hay usuarios del sistema.</td></tr>';
                } else {
                    systemUsers.forEach(user => {
                        const safeName = (user.name || '').replace(/'/g, "\\'");
                        const safeRole = user.role || 'Sin rol';
                        const safeEmail = user.email || '';
                        tbodyUsers.innerHTML += `
                            <tr>
                                <td data-label="Nombre">${user.name || ''}</td>
                                <td data-label="Email">${safeEmail}</td>
                                <td data-label="Rol"><span class="table-badge-dark">${safeRole}</span></td>
                                <td data-label="PIN">${user.pin || 'Sin PIN'}</td>
                                <td data-label="Acciones">
                                    <button type="button" class="btn btn-sm btn-outline-primary" style="padding: 2px 8px; font-size: 0.8rem;" onclick="editUser(${user.id})">Editar</button>
                                    <button type="button" class="btn btn-sm btn-outline-dark" style="padding: 2px 8px; font-size: 0.8rem;" onclick="showUserBarcode(${user.id}, '${safeName}')"><i class="ph ph-barcode"></i></button>
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
                        const safeRole = user.role || 'Cliente';
                        tbodyClients.innerHTML += `
                            <tr>
                                <td data-label="Nombre">${user.name || ''}</td>
                                <td data-label="Email">${user.email || ''}</td>
                                <td data-label="Rol"><span class="table-badge-dark">${safeRole}</span></td>
                                <td data-label="DNI">${user.dni || 'Sin DNI'}</td>
                                <td data-label="Acciones">
                                    <button type="button" class="btn btn-sm btn-outline-primary" style="padding: 2px 8px; font-size: 0.8rem;" onclick="editUser(${user.id})">Editar</button>
                                    <button type="button" class="table-btn-danger" onclick="deleteUser(${user.id})">Eliminar</button>
                                </td>
                            </tr>
                        `;
                    });
                }
            } else {
                tbodyUsers.innerHTML = `<tr><td colspan="5" class="text-center text-danger">${(res && res.message) || 'Error al cargar usuarios'}</td></tr>`;
                tbodyClients.innerHTML = `<tr><td colspan="5" class="text-center text-danger">${(res && res.message) || 'Error al cargar clientes'}</td></tr>`;
            }
        } catch (e) {
            console.error('Error en loadUsers:', e);
            if (tbodyUsers) tbodyUsers.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error al cargar usuarios.</td></tr>';
            if (tbodyClients) tbodyClients.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error al cargar clientes.</td></tr>';
        }
    };

    window.editUser = (id) => {
        const user = window.usersData.find(u => u.id == id);
        if(!user) return;
        
        document.getElementById('userModalTitle').innerText = 'Editar Usuario';
        document.getElementById('user_id_input').value = user.id;
        document.querySelector('#userForm [name="name"]').value = user.name || '';
        document.querySelector('#userForm [name="email"]').value = user.email || '';
        document.querySelector('#userForm [name="role"]').value = user.role || '';
        document.querySelector('#userForm [name="pin"]').value = user.pin || '';
        document.querySelector('#userForm [name="biometric_id"]').value = user.biometric_id || '';
        
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

    window.showUserBarcode = async function(userId, userName) {
        try {
            const fd = new FormData();
            fd.append('action', 'generate');
            fd.append('user_id', userId);
            const baseUrl = document.querySelector('meta[name="base-url"]')?.getAttribute('content') || '';
            const res = await fetch(`${baseUrl}/ajax/user_barcode_ops.php`, {
                method: 'POST',
                body: fd
            });
            const data = await res.json();
            if (data.success) {
                document.getElementById('userBarcodeName').textContent = userName;
                
                // --- Barcode ---
                const barcodeSvg = document.getElementById('barcodeSVG');
                JsBarcode(barcodeSvg, data.barcode, {
                    format: "CODE128",
                    displayValue: true,
                    fontSize: 20,
                    margin: 10
                });
                
                // Create a hidden canvas to get the PNG for Barcode
                const hiddenCanvas = document.createElement('canvas');
                JsBarcode(hiddenCanvas, data.barcode, { format: "CODE128", displayValue: true, fontSize: 20, margin: 10 });
                
                // --- QR Code ---
                const qrContainer = document.getElementById('qrContainer');
                qrContainer.innerHTML = ''; // clear previous
                
                // Create a canvas for PNG
                const qrCanvas = document.createElement('canvas');
                QRCode.toCanvas(qrCanvas, data.barcode, {
                    width: 128,
                    margin: 1,
                    color: { dark: '#000000', light: '#ffffff' }
                });
                qrContainer.appendChild(qrCanvas);
                
                // Generate SVG string
                let qrSvgStr = '';
                QRCode.toString(data.barcode, {
                    type: 'svg',
                    width: 128,
                    margin: 1,
                    color: { dark: '#000000', light: '#ffffff' }
                }, function (err, string) {
                    if (!err) qrSvgStr = string;
                });
                
                document.getElementById('userBarcodeModal').classList.add('active');
                
                const safeName = userName.replace(/\s+/g, '_');
                
                // Button Events
                document.getElementById('btnDownloadBarcodePNG').onclick = function() {
                    const link = document.createElement('a');
                    link.download = `barcode_${safeName}_${data.barcode}.png`;
                    link.href = hiddenCanvas.toDataURL('image/png');
                    link.click();
                };
                
                document.getElementById('btnDownloadBarcodeSVG').onclick = function() {
                    const svgData = new XMLSerializer().serializeToString(barcodeSvg);
                    const blob = new Blob([svgData], {type: "image/svg+xml;charset=utf-8"});
                    const url = URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.download = `barcode_${safeName}_${data.barcode}.svg`;
                    link.href = url;
                    link.click();
                };
                
                document.getElementById('btnDownloadQrPNG').onclick = function() {
                    const link = document.createElement('a');
                    link.download = `qr_${safeName}_${data.barcode}.png`;
                    link.href = qrCanvas.toDataURL('image/png');
                    link.click();
                };

                document.getElementById('btnDownloadQrSVG').onclick = function() {
                    const blob = new Blob([qrSvgStr], {type: "image/svg+xml;charset=utf-8"});
                    const url = URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.download = `qr_${safeName}_${data.barcode}.svg`;
                    link.href = url;
                    link.click();
                };
            } else {
                window.showToast(data.message || 'Error al generar código', 'error');
            }
        } catch (e) {
            console.error(e);
            window.showToast('Error de conexión', 'error');
        }
    };

    // Load initial data
    loadRoles();
    loadUsers();
    // ===== CONSTRUCTOR DE RECIBOS (RECEIPT BUILDER) =====
    const builderState = {
        blocks: [
            { id: 'logo', title: 'Logo de la Empresa', visible: true, icon: 'ph-image' },
            { id: 'header_text', title: 'Texto de Encabezado', visible: true, icon: 'ph-text-align-center' },
            { id: 'client_data', title: 'Datos del Cliente', visible: true, icon: 'ph-user' },
            { id: 'services_table', title: 'Tabla de Servicios Cobrados', visible: true, icon: 'ph-table' },
            { id: 'history_table', title: 'Historial de Pagos', visible: true, icon: 'ph-clock-counter-clockwise' },
            { id: 'footer_text', title: 'Mensaje al Pie (Notas)', visible: true, icon: 'ph-text-align-justify' }
        ],
        texts: {
            title: '',
            header: '',
            footer: '',
            history_month: 'current',
            history_year: 'current',
            template: 'default',
            logo_url: ''
        }
    };

    const renderBuilderBlocks = () => {
        const container = document.getElementById('blocks-list');
        container.innerHTML = '';
        builderState.blocks.forEach((block, index) => {
            const blockEl = document.createElement('div');
            blockEl.className = 'builder-block';
            blockEl.draggable = true;
            blockEl.dataset.index = index;
            
            blockEl.innerHTML = `
                <div class="block-info">
                    <i class="ph ${block.icon} block-icon" style="cursor: grab;"></i>
                    <span style="font-weight: 500;">${block.title}</span>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input block-toggle" type="checkbox" data-index="${index}" ${block.visible ? 'checked' : ''}>
                </div>
            `;
            
            // Drag Events
            blockEl.addEventListener('dragstart', (e) => {
                blockEl.classList.add('dragging');
                e.dataTransfer.setData('text/plain', index);
            });
            blockEl.addEventListener('dragend', () => {
                blockEl.classList.remove('dragging');
                updateBuilderPreview();
            });
            
            container.appendChild(blockEl);
        });

        // Toggle Event
        document.querySelectorAll('.block-toggle').forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                const idx = e.target.dataset.index;
                builderState.blocks[idx].visible = e.target.checked;
                updateBuilderPreview();
            });
        });

        setupDragAndDrop();
    };

    const setupDragAndDrop = () => {
        const container = document.getElementById('blocks-list');
        container.addEventListener('dragover', (e) => {
            e.preventDefault();
            const afterElement = getDragAfterElement(container, e.clientY);
            const draggable = document.querySelector('.dragging');
            if (draggable) {
                if (afterElement == null) {
                    container.appendChild(draggable);
                } else {
                    container.insertBefore(draggable, afterElement);
                }
            }
        });
        
        container.addEventListener('drop', (e) => {
            e.preventDefault();
            const draggableNodes = [...container.querySelectorAll('.builder-block')];
            // Reconstruir el array de blocks
            const newBlocks = [];
            draggableNodes.forEach(node => {
                const originalIndex = parseInt(node.dataset.index);
                newBlocks.push(builderState.blocks[originalIndex]);
            });
            builderState.blocks = newBlocks;
            renderBuilderBlocks();
            updateBuilderPreview();
        });
    };

    const getDragAfterElement = (container, y) => {
        const draggableElements = [...container.querySelectorAll('.builder-block:not(.dragging)')];
        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            } else {
                return closest;
            }
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    };

    const updateBuilderPreview = () => {
        // Actualizar variables de textos desde inputs
        document.querySelectorAll('.builder-input').forEach(input => {
            const key = input.id.replace('builder_', '');
            if(builderState.texts[key] !== undefined) {
                builderState.texts[key] = input.value;
            }
        });

        const previewContainer = document.getElementById('a4-preview-content');
        previewContainer.innerHTML = '';
        
        const title = builderState.texts.title || 'COMPROBANTE DE PAGO';
        const headerText = builderState.texts.header || 'Gracias por su preferencia.';
        const footerText = builderState.texts.footer || 'Conserve este documento.';
        const template = builderState.texts.template || 'default';
        const logoUrl = builderState.texts.logo_url || '';
        const appName = document.getElementById('app_name')?.value || 'Mi Empresa SAC';

        // Definir variables de estilo basadas en la plantilla
        let st = {
            titleColor: '#1e293b',
            accentBg: '#f1f5f9',
            accentBorder: '#cbd5e1',
            boxBg: '#f8fafc',
            boxBorder: '#e2e8f0'
        };
        
        if (template === 'modern') {
            st.titleColor = '#2563eb';
            st.accentBg = '#eff6ff';
            st.accentBorder = '#bfdbfe';
            st.boxBg = '#ffffff';
            st.boxBorder = '#bfdbfe';
        } else if (template === 'minimal') {
            st.titleColor = '#000000';
            st.accentBg = '#ffffff';
            st.accentBorder = '#000000';
            st.boxBg = '#ffffff';
            st.boxBorder = '#000000';
        }

        // Título del preview
        previewContainer.innerHTML += `<div style="text-align: center; margin-bottom: 20px;"><h2 style="margin:0; font-size: 24px; color: ${st.titleColor};">${title}</h2></div>`;
        
        // Contenedor principal de bloques
        const blocksContent = document.createElement('div');
        
        builderState.blocks.forEach(block => {
            if(!block.visible) return;
            
            const div = document.createElement('div');
            div.className = 'preview-block';
            
            if(block.id === 'logo') {
                if (logoUrl) {
                    div.innerHTML = `<div style="text-align: center; margin-bottom: 20px;"><img src="${logoUrl}" style="max-height:100px; max-width:200px; object-fit:contain; border-radius:8px;"></div>`;
                } else {
                    div.innerHTML = `<div style="text-align: center; margin-bottom: 20px;"><div style="display:inline-block; width:100px; height:100px; background:#f1f5f9; border-radius:12px; display:flex; align-items:center; justify-content:center; color:#94a3b8;"><i class="ph-bold ph-image" style="font-size:3rem;"></i></div></div>`;
                }
            } else if(block.id === 'header_text') {
                div.innerHTML = `<div style="text-align: center; margin-bottom: 20px; font-size: 14px; color: #475569;">${headerText.replace(/\\n/g, '<br>')}</div>`;
            } else if(block.id === 'client_data') {
                div.innerHTML = `
                    <div style="border: 1px solid ${st.boxBorder}; border-radius: 8px; padding: 15px; margin-bottom: 20px; background: ${st.boxBg};">
                        <h4 style="margin: 0 0 10px 0; font-size: 14px; color: ${st.titleColor}; border-bottom: 1px solid ${st.boxBorder}; padding-bottom: 5px;">Datos del Cliente</h4>
                        <div style="display: flex; justify-content: space-between; font-size: 13px;">
                            <div><strong>Nombre:</strong> Juan Pérez</div>
                            <div><strong>Fecha:</strong> ${new Date().toLocaleDateString()}</div>
                        </div>
                        <div style="font-size: 13px; margin-top: 5px;"><strong>Dirección:</strong> Av. Principal 123</div>
                    </div>`;
            } else if(block.id === 'services_table') {
                div.innerHTML = `
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 13px;">
                        <thead>
                            <tr style="background: ${st.accentBg}; border-bottom: 2px solid ${st.accentBorder}; color: ${st.titleColor};">
                                <th style="text-align: left; padding: 8px;">Descripción</th>
                                <th style="text-align: center; padding: 8px;">Cant</th>
                                <th style="text-align: right; padding: 8px;">Precio</th>
                                <th style="text-align: right; padding: 8px;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr style="border-bottom: 1px solid ${st.boxBorder};">
                                <td style="padding: 8px;">Plan de Internet Fibra 100Mbps</td>
                                <td style="text-align: center; padding: 8px;">1</td>
                                <td style="text-align: right; padding: 8px;">$ 30.00</td>
                                <td style="text-align: right; padding: 8px;">$ 30.00</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" style="text-align: right; padding: 8px; font-weight: bold; color: ${st.titleColor};">TOTAL:</td>
                                <td style="text-align: right; padding: 8px; font-weight: bold; color: ${st.titleColor};">$ 30.00</td>
                            </tr>
                        </tfoot>
                    </table>`;
            } else if(block.id === 'history_table') {
                div.innerHTML = `
                    <div style="margin-bottom: 20px;">
                        <h4 style="margin: 0 0 10px 0; font-size: 14px; color: ${st.titleColor};">Historial de Pagos (Mes/Año filtrado)</h4>
                        <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                            <thead>
                                <tr style="background: ${st.boxBg}; border-bottom: 1px solid ${st.boxBorder};">
                                    <th style="text-align: left; padding: 6px;">Fecha</th>
                                    <th style="text-align: left; padding: 6px;">Concepto</th>
                                    <th style="text-align: right; padding: 6px;">Monto</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr style="border-bottom: 1px dashed ${st.boxBorder};">
                                    <td style="padding: 6px;">15/05/2026</td>
                                    <td style="padding: 6px;">Mensualidad Mayo</td>
                                    <td style="text-align: right; padding: 6px;">$ 30.00</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>`;
            } else if(block.id === 'footer_text') {
                div.innerHTML = `<div style="text-align: center; margin-top: 30px; padding-top: 15px; border-top: 1px dashed ${st.accentBorder}; font-size: 12px; color: #64748b;">${footerText.replace(/\\n/g, '<br>')}</div>`;
            }
            
            blocksContent.appendChild(div);
        });
        
        previewContainer.appendChild(blocksContent);
        
        // Guardar JSON actualizado en el input hidden
        document.getElementById('receipt_template_json').value = JSON.stringify(builderState);
    };

    // Eventos de input para actualizar el preview en vivo
    document.querySelectorAll('.builder-input').forEach(input => {
        input.addEventListener('input', updateBuilderPreview);
        input.addEventListener('change', updateBuilderPreview);
    });

    // Lógica para Logo con GDrive
    const btnSelectLogo = document.getElementById('btnSelectLogoGDrive');
    const btnRemoveLogo = document.getElementById('btnRemoveLogo');
    const logoInput = document.getElementById('builder_logo_url');

    const updateLogoButtons = () => {
        if (logoInput.value) {
            btnRemoveLogo.style.display = 'block';
            btnSelectLogo.innerHTML = '<i class="ph-fill ph-image"></i> Cambiar Logo de Drive';
        } else {
            btnRemoveLogo.style.display = 'none';
            btnSelectLogo.innerHTML = '<i class="ph-fill ph-google-drive-logo"></i> Elegir Logo en Drive';
        }
    };

    if (btnSelectLogo) {
        btnSelectLogo.addEventListener('click', () => {
            if (typeof window.GDriveManager !== 'undefined') {
                window.GDriveManager.openModal((file) => {
                    // file.url_publica if exists, else we can use base_url + file.ruta_archivo
                    let url = file.url_publica || file.ruta_archivo;
                    if (url && !url.startsWith('http')) {
                        url = '<?php echo BASE_URL; ?>/' + url;
                    }
                    logoInput.value = url;
                    updateLogoButtons();
                    updateBuilderPreview(); // Esto lanzará input event
                });
            } else {
                alert('El componente GDriveManager no está disponible.');
            }
        });
    }

    if (btnRemoveLogo) {
        btnRemoveLogo.addEventListener('click', () => {
            logoInput.value = '';
            updateLogoButtons();
            updateBuilderPreview();
        });
    }

    // Inicializar builder y cargar estado guardado
    (async () => {
        try {
            const formData = new FormData();
            formData.append('action', 'get_settings');
            const res = await fetch('<?php echo BASE_URL; ?>/ajax/settings.php', { method: 'POST', body: formData }).then(r => r.json());
            if (res.success && res.data && res.data.receipt_template_json) {
                const savedState = JSON.parse(res.data.receipt_template_json);
                if(savedState.blocks && savedState.texts) {
                    builderState.blocks = savedState.blocks;
                    builderState.texts = savedState.texts;
                    // Poblar inputs visuales
                    for (const [k, v] of Object.entries(builderState.texts)) {
                        const el = document.getElementById('builder_' + k);
                        if(el) {
                            el.value = v;
                        }
                    }
                    updateLogoButtons();
                }
            }
        } catch (e) {}
        renderBuilderBlocks();
        updateBuilderPreview();
    })();

});
</script>

<?php include '../../includes/footer.php'; ?>
