<?php
require_once '../../config/db.php';
requireLogin();
requirePermission($pdo, 'soporte');

include '../../includes/header.php';
include '../../includes/sidebar.php';

// Obtener categorías y prioridades y técnicos para los select
$categorias = $pdo->query("SELECT * FROM ticket_categories ORDER BY name")->fetchAll();
$prioridades = $pdo->query("SELECT * FROM ticket_priorities ORDER BY level DESC, name ASC")->fetchAll();
$tecnicos = $pdo->query("SELECT id, name FROM users WHERE role != 'user' ORDER BY name")->fetchAll(); // Asumiendo 'user' es cliente
$clientes = $pdo->query("SELECT id, nombre_completo, dni FROM clientes ORDER BY nombre_completo")->fetchAll();

?>

<style>
    .kanban-board {
        display: flex;
        gap: 20px;
        overflow-x: auto;
        padding-bottom: 20px;
        align-items: flex-start;
    }
    .kanban-column {
        flex: 0 0 300px;
        background: var(--surface-color);
        border-radius: 12px;
        border: 1px solid var(--border-color);
        display: flex;
        flex-direction: column;
        max-height: calc(100vh - 250px);
    }
    .kanban-header {
        padding: 15px;
        font-weight: 700;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
        text-transform: uppercase;
        font-size: 0.9rem;
    }
    .kanban-header .badge {
        background: var(--bg-color);
        color: var(--text-color);
        border-radius: 50%;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
    }
    .kanban-body {
        padding: 15px;
        overflow-y: auto;
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    .ticket-card {
        background: var(--bg-color);
        border: 1px solid var(--border-color);
        border-radius: 10px;
        padding: 15px;
        cursor: pointer;
        transition: transform 0.2s, box-shadow 0.2s;
        position: relative;
    }
    .ticket-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow);
    }
    .ticket-card.ticket-glow {
        animation: glow 2s infinite alternate;
        border-color: #3b82f6;
    }
    @keyframes glow {
        from { box-shadow: 0 0 5px rgba(59, 130, 246, 0.2); }
        to { box-shadow: 0 0 15px rgba(59, 130, 246, 0.8); }
    }
    .btn-trash-ticket {
        position: absolute;
        top: 15px;
        right: 15px;
        background: transparent;
        border: none;
        color: var(--text-muted);
        cursor: pointer;
        transition: color 0.2s;
        z-index: 10;
        padding: 0;
    }
    .btn-trash-ticket:hover {
        color: #ef4444;
    }
    body.dark-theme .ticket-card {
        background: #1e293b;
    }
    .ticket-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
    }
    .ticket-id {
        font-size: 0.8rem;
        font-weight: 700;
        color: var(--text-muted);
    }
    .ticket-title {
        font-weight: 600;
        margin-bottom: 8px;
        font-size: 0.95rem;
        line-height: 1.3;
    }
    .ticket-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 10px;
        font-size: 0.8rem;
    }
    .ticket-tag {
        padding: 3px 8px;
        border-radius: 4px;
        font-weight: 600;
        font-size: 0.75rem;
    }
    .ticket-assignee {
        display: flex;
        align-items: center;
        gap: 5px;
        color: var(--text-muted);
    }
    .ticket-assignee i {
        font-size: 1rem;
    }
    .filter-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 24px;
        background: var(--surface-color);
        padding: 16px;
        border-radius: 12px;
        border: 1px solid var(--border-color);
        box-shadow: var(--shadow);
        align-items: center;
    }
    
    .status-abierto { border-top: 3px solid #3b82f6; }
    .status-pendiente { border-top: 3px solid #eab308; }
    .status-en_proceso { border-top: 3px solid #8b5cf6; }
    .status-terminado { border-top: 3px solid #10b981; }

    /* Custom scrollbar para kanban */
    .kanban-body::-webkit-scrollbar { width: 6px; }
    .kanban-body::-webkit-scrollbar-thumb { background-color: var(--border-color); border-radius: 10px; }

    /* Chat Offcanvas */
    .chat-offcanvas {
        position: fixed;
        top: 0;
        right: -450px;
        width: 400px;
        height: 100vh;
        background: url('<?php echo BASE_URL; ?>/assets/img/chat-bg.png') repeat, var(--surface-color);
        background-color: #efeae2;
        box-shadow: -5px 0 15px rgba(0,0,0,0.1);
        z-index: 1050;
        transition: right 0.3s ease;
        display: flex;
        flex-direction: column;
        border-left: 1px solid var(--border-color);
    }
    body.dark-theme .chat-offcanvas {
        background-color: #0b141a;
        background-image: none;
    }
    .chat-offcanvas.open {
        right: 0;
    }
    .chat-header {
        padding: 15px;
        background: var(--surface-color);
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .chat-messages {
        flex: 1;
        padding: 20px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .message-bubble {
        max-width: 75%;
        padding: 10px 15px;
        border-radius: 12px;
        position: relative;
        font-size: 0.95rem;
        line-height: 1.4;
        box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        word-wrap: break-word;
    }
    .message-received {
        align-self: flex-start;
        background: #ffffff;
        color: #111b21;
        border-top-left-radius: 0;
    }
    .message-sent {
        align-self: flex-end;
        background: #d9fdd3;
        color: #111b21;
        border-top-right-radius: 0;
    }
    body.dark-theme .message-received { background: #202c33; color: #e9edef; }
    body.dark-theme .message-sent { background: #005c4b; color: #e9edef; }
    .message-time {
        font-size: 0.65rem;
        color: rgba(0,0,0,0.45);
        text-align: right;
        margin-top: 5px;
        display: block;
    }
    body.dark-theme .message-time { color: rgba(255,255,255,0.6); }
    
    .chat-input-area {
        padding: 15px;
        background: var(--surface-color);
        border-top: 1px solid var(--border-color);
        display: flex;
        align-items: flex-end;
        gap: 10px;
    }
    .chat-input-wrapper {
        flex: 1;
        background: var(--bg-color);
        border-radius: 20px;
        padding: 0 15px;
        border: 1px solid var(--border-color);
    }
    .chat-input-wrapper textarea {
        width: 100%;
        border: none;
        background: transparent;
        padding: 12px 0;
        max-height: 120px;
        outline: none;
        resize: none;
        color: var(--text-color);
        font-family: inherit;
    }
    .btn-send {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background: #00a884;
        color: white;
        border: none;
        font-size: 1.2rem;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        flex-shrink: 0;
    }
    .unread-badge {
        background: #ef4444;
        color: white;
        font-size: 0.75rem;
        font-weight: bold;
        padding: 2px 6px;
        border-radius: 10px;
        margin-left: 5px;
    }
</style>

<div class="page-header-card">
    <div class="page-header-left">
        <div class="page-header-icon">
            <i class="ph ph-headset"></i>
        </div>
        <div class="page-header-info">
            <h2>Soporte</h2>
            <p>Gestión de tickets y atención al cliente.</p>
        </div>
    </div>
    <div class="page-header-actions">
        <button type="button" class="btn btn-secondary" onclick="openAjustesModal()">
            <i class="ph ph-gear"></i> Ajustes
        </button>
        <button type="button" class="btn btn-primary" onclick="openNuevoTicketModal()">
            <i class="ph ph-plus"></i> Nuevo Ticket
        </button>
    </div>
</div>

<div class="filter-bar">
    <div class="search-box flex-grow-1" style="position: relative; min-width: 250px;">
        <i class="ph ph-magnifying-glass" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
        <input type="text" id="searchInput" class="form-control" placeholder="Buscar ticket por asunto o cliente..." style="padding-left: 40px;">
    </div>
    <select class="form-select" id="filterCategoria" style="width: auto;">
        <option value="">Todas las Categorías</option>
        <?php foreach($categorias as $cat): ?>
            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
        <?php endforeach; ?>
    </select>
    <select class="form-select" id="filterPrioridad" style="width: auto;">
        <option value="">Todas las Prioridades</option>
        <?php foreach($prioridades as $pri): ?>
            <option value="<?php echo $pri['id']; ?>"><?php echo htmlspecialchars($pri['name']); ?></option>
        <?php endforeach; ?>
    </select>
    <button class="btn btn-outline-secondary" onclick="loadTickets()"><i class="ph ph-arrows-clockwise"></i> Refrescar</button>
    <button class="btn btn-outline-danger" onclick="openTrashModal()"><i class="ph ph-trash"></i> Papelera</button>
</div>

<div class="kanban-board">
    <!-- NUEVO -->
    <div class="kanban-column status-nuevo">
        <div class="kanban-header">
            <span style="border-bottom: 3px solid #3b82f6;">NUEVO</span>
            <span class="badge" id="count-nuevo">0</span>
        </div>
        <div class="kanban-body" id="col-nuevo"></div>
    </div>
    <!-- PENDIENTE -->
    <div class="kanban-column status-pendiente">
        <div class="kanban-header">
            <span>Pendiente</span>
            <span class="badge" id="count-pendiente">0</span>
        </div>
        <div class="kanban-body" id="col-pendiente"></div>
    </div>
    <!-- EN PROCESO -->
    <div class="kanban-column status-en_proceso">
        <div class="kanban-header">
            <span>En Proceso</span>
            <span class="badge" id="count-en_proceso">0</span>
        </div>
        <div class="kanban-body" id="col-en_proceso"></div>
    </div>
    <!-- TERMINADO -->
    <div class="kanban-column status-terminado">
        <div class="kanban-header">
            <span>Terminado</span>
            <span class="badge" id="count-terminado">0</span>
        </div>
        <div class="kanban-body" id="col-terminado"></div>
    </div>
</div>

<!-- Modal Nuevo Ticket -->
<div class="modal-overlay" id="ticketModal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3 id="ticketModalTitle">Nuevo Ticket</h3>
            <button class="btn close-modal" style="background:transparent; border:none; font-size:1.5rem; cursor:pointer;" onclick="document.getElementById('ticketModal').classList.remove('active')">&times;</button>
        </div>
        <form id="ticketForm">
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Cliente *</label>
                        <select name="cliente_id" id="modal_cliente_select" class="form-select" onchange="toggleManualClientInput(this.value)" required>
                            <option value="">Seleccione un cliente...</option>
                            <option value="manual" style="font-weight: bold; color: var(--primary-color);">✍️ Escribir nombre de persona aparte (no registrar)</option>
                            <?php foreach($clientes as $cli): ?>
                                <option value="<?php echo $cli['id']; ?>"><?php echo htmlspecialchars($cli['nombre_completo'] . ' (' . $cli['dni'] . ')'); ?></option>
                            <?php endforeach; ?>
                        </select>
                        
                        <div id="modal_manual_cliente_wrapper" style="display: none; margin-top: 10px;">
                            <label class="form-label text-primary" style="font-size: 0.85rem; font-weight: 600;">Nombre Completo de la Persona *</label>
                            <input type="text" name="cliente_nombre_manual" id="modal_cliente_nombre_manual" class="form-control" placeholder="Escribe el nombre completo (ej. Juan Pérez)...">
                        </div>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Asunto</label>
                        <input type="text" name="asunto" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Categoría</label>
                        <select name="categoria_id" class="form-select">
                            <option value="">Sin Categoría</option>
                            <?php foreach($categorias as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Prioridad</label>
                        <select name="prioridad_id" class="form-select">
                            <option value="">Sin Prioridad</option>
                            <?php foreach($prioridades as $pri): ?>
                                <option value="<?php echo $pri['id']; ?>"><?php echo htmlspecialchars($pri['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Asignar a (Técnico)</label>
                        <select name="assigned_to" class="form-select">
                            <option value="">Sin Asignar</option>
                            <?php foreach($tecnicos as $tec): ?>
                                <option value="<?php echo $tec['id']; ?>"><?php echo htmlspecialchars($tec['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Descripción del Problema</label>
                        <textarea name="descripcion" class="form-control" rows="4" required></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('ticketModal').classList.remove('active')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Crear Ticket</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Ajustes -->
<div class="modal-overlay" id="ajustesModal">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h3>Ajustes de Soporte</h3>
            <button class="btn close-modal" style="background:transparent; border:none; font-size:1.5rem; cursor:pointer;" onclick="document.getElementById('ajustesModal').classList.remove('active')">&times;</button>
        </div>
        <div class="modal-body">
            <ul class="nav nav-tabs" style="margin-bottom: 20px; border-bottom: 1px solid var(--border-color); display: flex; list-style: none; padding: 0;">
                <li style="margin-right: 10px;">
                    <button type="button" class="btn btn-outline-primary active" id="tabCat" onclick="switchTab('cat')">Categorías</button>
                </li>
                <li>
                    <button type="button" class="btn btn-outline-primary" id="tabPri" onclick="switchTab('pri')">Prioridades</button>
                </li>
            </ul>
            
            <!-- Tab Categorías -->
            <div id="contentCat">
                <form id="catForm" class="d-flex gap-2 mb-3">
                    <input type="text" name="name" class="form-control" placeholder="Nueva Categoría" required>
                    <input type="color" name="color" class="form-control" style="width: 50px; padding: 0;" value="#3b82f6">
                    <button type="submit" class="btn btn-primary">Añadir</button>
                </form>
                <div class="table-responsive">
                    <table class="table">
                        <thead><tr><th>Nombre</th><th>Color</th><th>Acciones</th></tr></thead>
                        <tbody id="catTableBody">
                            <?php foreach($categorias as $cat): ?>
                            <tr id="cat_row_<?php echo $cat['id']; ?>">
                                <td><?php echo htmlspecialchars($cat['name']); ?></td>
                                <td><div style="width: 20px; height: 20px; background: <?php echo $cat['color']; ?>; border-radius: 4px;"></div></td>
                                <td>
                                    <button class="btn btn-sm btn-warning me-1" onclick="startEditSetting('category', <?php echo $cat['id']; ?>)"><i class="ph ph-pencil"></i> Editar</button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteSetting('category', <?php echo $cat['id']; ?>)">Eliminar</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab Prioridades -->
            <div id="contentPri" style="display: none;">
                <form id="priForm" class="d-flex gap-2 mb-3">
                    <input type="text" name="name" class="form-control" placeholder="Nueva Prioridad" required>
                    <input type="color" name="color" class="form-control" style="width: 50px; padding: 0;" value="#eab308">
                    <input type="number" name="level" class="form-control" placeholder="Nivel (Ej: 1, 2, 3)" style="width: 100px;" value="1" required>
                    <button type="submit" class="btn btn-primary">Añadir</button>
                </form>
                <div class="table-responsive">
                    <table class="table">
                        <thead><tr><th>Nombre</th><th>Nivel</th><th>Color</th><th>Acciones</th></tr></thead>
                        <tbody id="priTableBody">
                            <?php foreach($prioridades as $pri): ?>
                            <tr id="pri_row_<?php echo $pri['id']; ?>">
                                <td><?php echo htmlspecialchars($pri['name']); ?></td>
                                <td><?php echo $pri['level']; ?></td>
                                <td><div style="width: 20px; height: 20px; background: <?php echo $pri['color']; ?>; border-radius: 4px;"></div></td>
                                <td>
                                    <button class="btn btn-sm btn-warning me-1" onclick="startEditSetting('priority', <?php echo $pri['id']; ?>)"><i class="ph ph-pencil"></i> Editar</button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteSetting('priority', <?php echo $pri['id']; ?>)">Eliminar</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            </div>
        </div>
    </div>
</div>

<!-- Chat Offcanvas -->
<div class="chat-offcanvas" id="chatSidebar">
    <div class="chat-header" style="flex-direction: column; align-items: stretch; padding: 10px 15px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--primary-color); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold;" id="chatAvatar">C</div>
                <div>
                    <div style="font-weight: bold; font-size: 0.95rem; line-height: 1.1;" id="chatTitle">Cliente</div>
                    <div style="font-size: 0.75rem; color: var(--text-muted);" id="chatSubtitle">TKT-0000</div>
                </div>
            </div>
            <div style="display: flex; gap: 5px;">
                <a href="#" target="_blank" id="btnWhatsapp" class="btn btn-sm" style="background:#25D366; color:white; padding: 5px; border-radius: 50%;" title="WhatsApp"><i class="ph-fill ph-whatsapp-logo" style="font-size:1.2rem;"></i></a>
                <a href="#" id="btnCall" class="btn btn-sm" style="background:#3b82f6; color:white; padding: 5px; border-radius: 50%;" title="Llamar"><i class="ph-fill ph-phone" style="font-size:1.2rem;"></i></a>
                <a href="#" target="_blank" id="btnMap" class="btn btn-sm" style="background:#ef4444; color:white; padding: 5px; border-radius: 50%;" title="Ubicación"><i class="ph-fill ph-map-pin" style="font-size:1.2rem;"></i></a>
                <button class="btn close-modal" style="background:transparent; border:none; font-size:1.5rem; cursor:pointer; padding: 0 5px;" onclick="closeChat()">&times;</button>
            </div>
        </div>
        <div style="display: flex; gap: 10px; border-top: 1px solid var(--border-color); padding-top: 10px;">
            <select id="chatAssignSelect" class="form-select" style="padding: 4px 8px; font-size: 0.8rem;" onchange="updateTicketAssignee(this.value)">
                <option value="">Sin Asignar</option>
                <?php foreach($tecnicos as $tec): ?>
                    <option value="<?php echo $tec['id']; ?>"><?php echo htmlspecialchars($tec['name']); ?></option>
                <?php endforeach; ?>
            </select>
            <select id="chatStatusSelect" class="form-select" style="padding: 4px 8px; font-size: 0.8rem;" onchange="updateTicketStatus(this.value)">
                <option value="nuevo">Nuevo</option>
                <option value="pendiente">Pendiente</option>
                <option value="en_proceso">En Proceso</option>
                <option value="terminado">Terminado</option>
            </select>
        </div>
    </div>
    <div class="chat-messages" id="chatMessages">
        <!-- Mensajes -->
    </div>
    <div class="chat-input-area" style="position: relative;">
        <!-- Menú de Acciones Flotante -->
        <div id="chatActionMenu" style="display: none; position: absolute; bottom: 100%; left: 15px; margin-bottom: 10px; background: var(--surface-color, #fff); border-radius: 14px; box-shadow: 0 10px 30px rgba(0,0,0,0.18); border: 1px solid var(--border-color, #e2e8f0); padding: 8px; z-index: 100; min-width: 220px;">
            <button type="button" onclick="openCameraInput(); toggleActionMenu();" style="display: flex; align-items: center; gap: 10px; width: 100%; padding: 10px 12px; background: transparent; border: none; text-align: left; cursor: pointer; border-radius: 8px; font-size: 0.88rem; font-weight: 600; color: var(--text-color, #1e293b);" onmouseover="this.style.background='rgba(16,185,129,0.1)'" onmouseout="this.style.background='transparent'">
                <i class="ph-fill ph-camera" style="font-size: 1.3rem; color: #10b981;"></i> Tomar Foto con Cámara
            </button>
            <button type="button" onclick="openGalleryInput(); toggleActionMenu();" style="display: flex; align-items: center; gap: 10px; width: 100%; padding: 10px 12px; background: transparent; border: none; text-align: left; cursor: pointer; border-radius: 8px; font-size: 0.88rem; font-weight: 600; color: var(--text-color, #1e293b);" onmouseover="this.style.background='rgba(59,130,246,0.1)'" onmouseout="this.style.background='transparent'">
                <i class="ph-fill ph-image" style="font-size: 1.3rem; color: #3b82f6;"></i> Enviar Foto / Archivo
            </button>
            <button type="button" onclick="sendLocation(); toggleActionMenu();" style="display: flex; align-items: center; gap: 10px; width: 100%; padding: 10px 12px; background: transparent; border: none; text-align: left; cursor: pointer; border-radius: 8px; font-size: 0.88rem; font-weight: 600; color: var(--text-color, #1e293b);" onmouseover="this.style.background='rgba(239,68,68,0.1)'" onmouseout="this.style.background='transparent'">
                <i class="ph-fill ph-map-pin" style="font-size: 1.3rem; color: #ef4444;"></i> Enviar Ubicación
            </button>
        </div>

        <button onclick="toggleActionMenu()" style="background: transparent; border: none; font-size: 1.5rem; color: var(--text-muted); cursor: pointer; padding: 0 10px;">
            <i class="ph ph-plus-circle"></i>
        </button>
        
        <!-- Banner de Animación de Subida Moderno -->
        <div id="chatUploadingBanner" class="chat-upload-banner" style="display: none;">
            <div class="chat-upload-content">
                <div class="chat-upload-spinner"></div>
                <div class="chat-upload-text">
                    <div class="upload-title"><i class="ph-bold ph-cloud-arrow-up"></i> Subiendo a Google Drive...</div>
                    <div class="upload-filename" id="chatUploadFilename">archivo.png</div>
                </div>
                <span id="chatUploadPercentText" style="font-size: 0.8rem; font-weight: 700; color: #3b82f6;">0%</span>
            </div>
            <div class="chat-upload-progress">
                <div class="progress-bar-inner" id="chatUploadProgressFill" style="width: 0%;"></div>
            </div>
        </div>

        <div id="filePreviewContainer" style="display: none; position: absolute; bottom: 100%; left: 50px; margin-bottom: 8px; background: #3b82f6; color: white; padding: 6px 12px; border-radius: 20px; font-size: 0.8rem; align-items: center; gap: 8px; box-shadow: 0 2px 8px rgba(59,130,246,0.3); z-index: 50;">
            <i class="ph-fill ph-image"></i>
            <span id="filePreviewName" style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-weight: 500;"></span>
            <button onclick="clearFileSelection()" style="background: rgba(255,255,255,0.2); border:none; color:white; cursor:pointer; padding: 2px; border-radius: 50%; display:flex; align-items:center; justify-content:center; width: 18px; height: 18px;"><i class="ph-bold ph-x" style="font-size: 0.6rem;"></i></button>
        </div>
        
        <div class="chat-input-wrapper" style="flex: 1; display: flex; flex-direction: column;">
            <textarea id="messageInput" placeholder="Escribe un mensaje..." rows="1" oninput="this.style.height = ''; this.style.height = this.scrollHeight + 'px'; updateMainButton();"></textarea>
        </div>
        
        <div id="audioRecordingUi" style="display: none; flex: 1; align-items: center; justify-content: space-between; background: #fee2e2; border-radius: 20px; padding: 0 15px; border: 1px solid #fca5a5; height: 40px;">
            <div style="display: flex; align-items: center; gap: 10px; color: #ef4444; font-weight: bold; font-size: 0.9rem;">
                <div style="width: 10px; height: 10px; background: #ef4444; border-radius: 50%; animation: pulse-red 1s infinite;"></div>
                <span id="recordingTimer">00:00</span>
            </div>
            <button onclick="cancelRecording()" style="background: transparent; border: none; color: #ef4444; cursor: pointer; font-size: 1.2rem;"><i class="ph-fill ph-trash"></i></button>
        </div>

        <button class="btn-send" onclick="handleMainAction()" id="btnSendMessage"><i id="btnSendMessageIcon" class="ph-fill ph-microphone"></i></button>
    </div>
</div>

<!-- Lightbox Modal -->
<div id="imageLightbox" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:99999; align-items:center; justify-content:center; flex-direction:column; backdrop-filter: blur(5px);">
    <button onclick="document.getElementById('imageLightbox').style.display='none'" style="position:absolute; top:20px; right:20px; background:rgba(255,255,255,0.1); border:none; color:white; font-size:1.5rem; cursor:pointer; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: background 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.2)'" onmouseout="this.style.background='rgba(255,255,255,0.1)'"><i class="ph-bold ph-x"></i></button>
    <img id="lightboxImg" referrerpolicy="no-referrer" style="max-width:90%; max-height:90%; border-radius:12px; box-shadow: 0 10px 40px rgba(0,0,0,0.5); object-fit: contain;">
</div>

<!-- Modal Papelera -->
<div class="modal-overlay" id="trashModal">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h3><i class="ph ph-trash"></i> Papelera de Tickets</h3>
            <button class="btn close-modal" style="background:transparent; border:none; font-size:1.5rem; cursor:pointer;" onclick="document.getElementById('trashModal').classList.remove('active')">&times;</button>
        </div>
        <div class="modal-body">
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 15px;">Los tickets aquí se eliminarán permanentemente después de 15 días.</p>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>ID</th><th>Asunto</th><th>Cliente</th><th>Acciones</th></tr></thead>
                    <tbody id="trashTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    @keyframes pulse-red {
        0% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.5); opacity: 0.5; }
        100% { transform: scale(1); opacity: 1; }
    }
    .sys-message {
        text-align: center;
        margin: 10px 0;
        font-size: 0.75rem;
        color: #64748b;
        background: rgba(0,0,0,0.05);
        padding: 5px 15px;
        border-radius: 20px;
        align-self: center;
        display: inline-block;
    }
    .chat-upload-banner {
        position: absolute;
        bottom: 105%;
        left: 15px;
        right: 15px;
        background: rgba(15, 23, 42, 0.95);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(59, 130, 246, 0.4);
        border-radius: 12px;
        padding: 10px 14px;
        z-index: 150;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3), 0 0 15px rgba(59, 130, 246, 0.2);
        animation: slideUpFade 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .chat-upload-content {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .chat-upload-spinner {
        width: 20px;
        height: 20px;
        border: 3px solid rgba(59, 130, 246, 0.2);
        border-top-color: #3b82f6;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
        flex-shrink: 0;
    }
    .chat-upload-text {
        flex: 1;
        overflow: hidden;
    }
    .chat-upload-text .upload-title {
        font-size: 0.8rem;
        font-weight: 700;
        color: #3b82f6;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .chat-upload-text .upload-filename {
        font-size: 0.75rem;
        color: #94a3b8;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .chat-upload-progress {
        width: 100%;
        height: 4px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 2px;
        margin-top: 6px;
        overflow: hidden;
    }
    .progress-bar-inner {
        height: 100%;
        background: linear-gradient(90deg, #3b82f6, #8b5cf6, #ec4899);
        background-size: 200% 100%;
        animation: gradientMove 1.5s linear infinite;
        border-radius: 2px;
        transition: width 0.15s ease;
    }
    .message-bubble.sending-optimistic {
        opacity: 0.75;
        position: relative;
        border: 1px dashed rgba(255, 255, 255, 0.4);
    }
    .sending-status-tag {
        font-size: 0.7rem;
        color: var(--text-muted, #94a3b8);
        display: flex;
        align-items: center;
        gap: 4px;
        margin-top: 4px;
    }
    @keyframes gradientMove {
        0% { background-position: 0% 50%; }
        100% { background-position: 200% 50%; }
    }
    @keyframes slideUpFade {
        from { transform: translateY(10px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    .loc-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 10px;
        margin-top: 5px;
        display: flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
        color: inherit;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    .loc-card:hover { background: #f1f5f9; }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    @keyframes scaleIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }
</style>

<script>
    let ticketsData = [];
    let currentChatId = null;
    let lastMessageId = 0;
    const currentUserId = <?php echo $_SESSION['user_id']; ?>;
    const escapeHtml = (str) => String(str || '').replace(/[&<>"']/g, s => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[s]));

    const loadTickets = async () => {
        try {
            const formData = new FormData();
            formData.append('action', 'list');
            const res = await fetch('<?php echo BASE_URL; ?>/ajax/soporte.php', {
                method: 'POST',
                body: formData,
                cache: 'no-store'
            }).then(r => r.json());
            if (res.success) {
                ticketsData = res.data;
                renderKanban();
            }
        } catch (e) {
            console.error(e);
        }
    };

    const renderKanban = () => {
        const search = document.getElementById('searchInput').value.toLowerCase();
        const catFilter = document.getElementById('filterCategoria').value;
        const priFilter = document.getElementById('filterPrioridad').value;

        const filtered = ticketsData.filter(t => {
            const matchSearch = (t.asunto && t.asunto.toLowerCase().includes(search)) || (t.cliente_nombre && t.cliente_nombre.toLowerCase().includes(search));
            const matchCat = catFilter === '' || t.categoria_id == catFilter;
            const matchPri = priFilter === '' || t.prioridad_id == priFilter;
            return matchSearch && matchCat && matchPri;
        });

        const columns = ['nuevo', 'pendiente', 'en_proceso', 'terminado'];
        columns.forEach(col => {
            document.getElementById(`col-${col}`).innerHTML = '';
            document.getElementById(`count-${col}`).innerText = '0';
        });

        filtered.forEach(t => {
            const container = document.getElementById(`col-${t.estado}`);
            if(!container) return;

            let catHtml = t.cat_name ? `<span class="ticket-tag" style="background:${t.cat_color}20; color:${t.cat_color};">${t.cat_name}</span>` : '';
            let priHtml = t.pri_name ? `<span class="ticket-tag" style="background:${t.pri_color}20; color:${t.pri_color};">${t.pri_name}</span>` : '';
            let assignHtml = t.tech_name ? `<div class="ticket-assignee"><i class="ph ph-user"></i> ${t.tech_name}</div>` : `<div class="ticket-assignee">Sin asignar</div>`;
            let unreadHtml = (t.unread_count && t.unread_count > 0) ? `<span class="unread-badge">${t.unread_count}</span>` : '';

            const isNuevo = t.estado === 'nuevo' ? 'ticket-glow' : '';
            const card = document.createElement('div');
            card.className = `ticket-card ${isNuevo}`;
            card.onclick = () => openChat(t);
            card.innerHTML = `
                <button class="btn-trash-ticket" onclick="trashTicket(event, ${t.id})" title="Mover a papelera"><i class="ph ph-trash"></i></button>
                <div class="ticket-header" style="padding-right: 20px;">
                    <span class="ticket-id">#TKT-${t.id.toString().padStart(4, '0')}</span>
                    ${priHtml}
                </div>
                <div class="ticket-title">${t.asunto} ${unreadHtml}</div>
                <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 10px;">
                    <i class="ph-fill ph-user-circle"></i> ${t.cliente_nombre || 'Cliente Anónimo'}
                </div>
                <div class="ticket-meta">
                    ${catHtml}
                    ${assignHtml}
                </div>
            `;
            container.appendChild(card);
            
            const countEl = document.getElementById(`count-${t.estado}`);
            countEl.innerText = parseInt(countEl.innerText) + 1;
        });
    };

    document.getElementById('searchInput').addEventListener('input', renderKanban);
    document.getElementById('filterCategoria').addEventListener('change', renderKanban);
    document.getElementById('filterPrioridad').addEventListener('change', renderKanban);

    // Alternar entrada manual de nombre de cliente
    const toggleManualClientInput = (val) => {
        const wrapper = document.getElementById('modal_manual_cliente_wrapper');
        const input = document.getElementById('modal_cliente_nombre_manual');
        if (val === 'manual') {
            wrapper.style.display = 'block';
            input.required = true;
            input.focus();
        } else {
            wrapper.style.display = 'none';
            input.required = false;
            input.value = '';
        }
    };

    // Modal Nuevo Ticket
    const openNuevoTicketModal = () => {
        document.getElementById('ticketForm').reset();
        toggleManualClientInput('');
        document.getElementById('ticketModal').classList.add('active');
    };

    document.getElementById('ticketForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        formData.append('action', 'save');
        try {
            const res = await fetch('<?php echo BASE_URL; ?>/ajax/soporte.php', {
                method: 'POST',
                body: formData
            }).then(r => r.json());
            if (res.success) {
                window.showToast('Ticket creado exitosamente', 'success');
                document.getElementById('ticketModal').classList.remove('active');
                loadTickets();
            } else {
                window.showToast(res.message || 'Error al crear ticket', 'error');
            }
        } catch (err) {
            window.showToast('Error del servidor', 'error');
        }
    });

    // Ajustes
    const openAjustesModal = () => {
        document.getElementById('ajustesModal').classList.add('active');
        refreshSettingsData();
    };

    const switchTab = (tab) => {
        if(tab === 'cat') {
            document.getElementById('contentCat').style.display = 'block';
            document.getElementById('contentPri').style.display = 'none';
            document.getElementById('tabCat').classList.add('active');
            document.getElementById('tabPri').classList.remove('active');
        } else {
            document.getElementById('contentCat').style.display = 'none';
            document.getElementById('contentPri').style.display = 'block';
            document.getElementById('tabCat').classList.remove('active');
            document.getElementById('tabPri').classList.add('active');
        }
    };

    let currentSettingsData = { categories: [], priorities: [] };

    // Recargar datos de ajustes dinámicamente sin parpadeos ni recargas de página
    const refreshSettingsData = async () => {
        try {
            const formData = new FormData();
            formData.append('action', 'get_settings_data');
            const res = await fetch('<?php echo BASE_URL; ?>/ajax/soporte.php', { method: 'POST', body: formData }).then(r => r.json());
            if (res.success) {
                currentSettingsData = res;

                // 1. Actualizar tabla categorías en modal
                const catTbody = document.getElementById('catTableBody');
                if (catTbody) {
                    let catRows = '';
                    res.categories.forEach(cat => {
                        catRows += `
                            <tr id="cat_row_${cat.id}">
                                <td>${escapeHtml(cat.name)}</td>
                                <td><div style="width: 20px; height: 20px; background: ${cat.color}; border-radius: 4px;"></div></td>
                                <td>
                                    <button class="btn btn-sm btn-warning me-1" onclick="startEditSetting('category', ${cat.id})"><i class="ph ph-pencil"></i> Editar</button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteSetting('category', ${cat.id})">Eliminar</button>
                                </td>
                            </tr>`;
                    });
                    catTbody.innerHTML = catRows || '<tr><td colspan="3" class="text-center text-muted">No hay categorías</td></tr>';
                }

                // 2. Actualizar tabla prioridades en modal
                const priTbody = document.getElementById('priTableBody');
                if (priTbody) {
                    let priRows = '';
                    res.priorities.forEach(pri => {
                        priRows += `
                            <tr id="pri_row_${pri.id}">
                                <td>${escapeHtml(pri.name)}</td>
                                <td>${pri.level}</td>
                                <td><div style="width: 20px; height: 20px; background: ${pri.color}; border-radius: 4px;"></div></td>
                                <td>
                                    <button class="btn btn-sm btn-warning me-1" onclick="startEditSetting('priority', ${pri.id})"><i class="ph ph-pencil"></i> Editar</button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteSetting('priority', ${pri.id})">Eliminar</button>
                                </td>
                            </tr>`;
                    });
                    priTbody.innerHTML = priRows || '<tr><td colspan="4" class="text-center text-muted">No hay prioridades</td></tr>';
                }

                // 3. Actualizar selects de categorías y prioridades en modal de ticket
                updateCategoryAndPrioritySelects(res.categories, res.priorities);
            }
        } catch (err) {
            console.error(err);
        }
    };

    window.startEditSetting = (type, id) => {
        if (type === 'category') {
            const cat = (currentSettingsData.categories || []).find(c => c.id == id);
            const tr = document.getElementById(`cat_row_${id}`);
            if (!tr) return;
            const curName = cat ? cat.name : tr.children[0].innerText.trim();
            const curColor = cat ? cat.color : '#3b82f6';
            tr.innerHTML = `
                <td><input type="text" id="edit_cat_name_${id}" class="form-control form-control-sm" value="${escapeHtml(curName)}"></td>
                <td><input type="color" id="edit_cat_color_${id}" class="form-control form-control-sm" style="width: 40px; padding:0;" value="${curColor}"></td>
                <td>
                    <button class="btn btn-sm btn-success me-1" onclick="saveSettingEdit('category', ${id})">Guardar</button>
                    <button class="btn btn-sm btn-secondary" onclick="refreshSettingsData()">Cancelar</button>
                </td>`;
        } else {
            const pri = (currentSettingsData.priorities || []).find(p => p.id == id);
            const tr = document.getElementById(`pri_row_${id}`);
            if (!tr) return;
            const curName = pri ? pri.name : tr.children[0].innerText.trim();
            const curLevel = pri ? pri.level : (tr.children[1].innerText.trim() || 1);
            const curColor = pri ? pri.color : '#eab308';
            tr.innerHTML = `
                <td><input type="text" id="edit_pri_name_${id}" class="form-control form-control-sm" value="${escapeHtml(curName)}"></td>
                <td><input type="number" id="edit_pri_level_${id}" class="form-control form-control-sm" style="width: 65px;" value="${curLevel}"></td>
                <td><input type="color" id="edit_pri_color_${id}" class="form-control form-control-sm" style="width: 40px; padding:0;" value="${curColor}"></td>
                <td>
                    <button class="btn btn-sm btn-success me-1" onclick="saveSettingEdit('priority', ${id})">Guardar</button>
                    <button class="btn btn-sm btn-secondary" onclick="refreshSettingsData()">Cancelar</button>
                </td>`;
        }
    };

    window.saveSettingEdit = async (type, id) => {
        const fd = new FormData();
        fd.append('id', id);

        if (type === 'category') {
            const nameEl = document.getElementById(`edit_cat_name_${id}`);
            const colorEl = document.getElementById(`edit_cat_color_${id}`);
            if (!nameEl || !nameEl.value.trim()) { window.showToast('El nombre es requerido', 'error'); return; }
            fd.append('action', 'save_category');
            fd.append('name', nameEl.value.trim());
            fd.append('color', colorEl ? colorEl.value : '#3b82f6');
        } else {
            const nameEl = document.getElementById(`edit_pri_name_${id}`);
            const levelEl = document.getElementById(`edit_pri_level_${id}`);
            const colorEl = document.getElementById(`edit_pri_color_${id}`);
            if (!nameEl || !nameEl.value.trim()) { window.showToast('El nombre es requerido', 'error'); return; }
            fd.append('action', 'save_priority');
            fd.append('name', nameEl.value.trim());
            fd.append('level', levelEl ? levelEl.value : 1);
            fd.append('color', colorEl ? colorEl.value : '#eab308');
        }

        try {
            const res = await fetch('<?php echo BASE_URL; ?>/ajax/soporte.php', { method: 'POST', body: fd }).then(r => r.json());
            if (res.success) {
                window.showToast('Actualizado exitosamente', 'success');
                await refreshSettingsData();
            } else {
                window.showToast(res.message || 'Error al actualizar', 'error');
            }
        } catch (err) {
            window.showToast('Error de conexión', 'error');
        }
    };

    const updateCategoryAndPrioritySelects = (categories, priorities) => {
        const catSelect = document.querySelector('#ticketForm select[name="categoria_id"]');
        const priSelect = document.querySelector('#ticketForm select[name="prioridad_id"]');
        const filterCat = document.getElementById('filterCategoria');
        const filterPri = document.getElementById('filterPrioridad');

        if (catSelect) {
            let options = '<option value="">Sin Categoría</option>';
            categories.forEach(cat => { options += `<option value="${cat.id}">${escapeHtml(cat.name)}</option>`; });
            catSelect.innerHTML = options;
        }
        if (filterCat) {
            let options = '<option value="">Todas las Categorías</option>';
            categories.forEach(cat => { options += `<option value="${cat.id}">${escapeHtml(cat.name)}</option>`; });
            filterCat.innerHTML = options;
        }

        if (priSelect) {
            let options = '<option value="">Sin Prioridad</option>';
            priorities.forEach(pri => { options += `<option value="${pri.id}">${escapeHtml(pri.name)}</option>`; });
            priSelect.innerHTML = options;
        }
        if (filterPri) {
            let options = '<option value="">Todas las Prioridades</option>';
            priorities.forEach(pri => { options += `<option value="${pri.id}">${escapeHtml(pri.name)}</option>`; });
            filterPri.innerHTML = options;
        }
    };

    document.getElementById('catForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(e.target);
        fd.append('action', 'save_category');
        try {
            const res = await fetch('<?php echo BASE_URL; ?>/ajax/soporte.php', { method: 'POST', body: fd }).then(r=>r.json());
            if(res.success) {
                e.target.reset();
                window.showToast('Categoría guardada exitosamente', 'success');
                await refreshSettingsData();
            } else {
                window.showToast(res.message || 'Error al guardar categoría', 'error');
            }
        } catch(err) {
            window.showToast('Error de conexión', 'error');
        }
    });

    document.getElementById('priForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(e.target);
        fd.append('action', 'save_priority');
        try {
            const res = await fetch('<?php echo BASE_URL; ?>/ajax/soporte.php', { method: 'POST', body: fd }).then(r=>r.json());
            if(res.success) {
                e.target.reset();
                window.showToast('Prioridad guardada exitosamente', 'success');
                await refreshSettingsData();
            } else {
                window.showToast(res.message || 'Error al guardar prioridad', 'error');
            }
        } catch(err) {
            window.showToast('Error de conexión', 'error');
        }
    });

    window.deleteSetting = async (type, id) => {
        if(confirm('¿Seguro que desea eliminar este elemento?')) {
            const fd = new FormData();
            fd.append('action', type === 'category' ? 'delete_category' : 'delete_priority');
            fd.append('id', id);
            try {
                const res = await fetch('<?php echo BASE_URL; ?>/ajax/soporte.php', { method: 'POST', body: fd }).then(r=>r.json());
                if(res.success) {
                    window.showToast('Elemento eliminado', 'success');
                    await refreshSettingsData();
                } else {
                    window.showToast(res.message || 'Error al eliminar', 'error');
                }
            } catch(err) {}
        }
    };

    document.addEventListener('DOMContentLoaded', () => {
        loadTickets();
        setInterval(loadTickets, 10000); // Poll kanban updates
        setInterval(loadMessages, 1200); // Ultra-fast chat polling (1.2s)
        
        document.getElementById('messageInput').addEventListener('keydown', (e) => {
            if(e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
    });

    const showUploadBanner = (filename) => {
        const banner = document.getElementById('chatUploadingBanner');
        const filenameEl = document.getElementById('chatUploadFilename');
        const fillEl = document.getElementById('chatUploadProgressFill');
        const percentEl = document.getElementById('chatUploadPercentText');
        if (banner) {
            filenameEl.textContent = filename || 'Archivo multimedia';
            fillEl.style.width = '10%';
            if (percentEl) percentEl.textContent = '10%';
            banner.style.display = 'block';
        }
    };

    const updateUploadProgress = (percent) => {
        const fillEl = document.getElementById('chatUploadProgressFill');
        const percentEl = document.getElementById('chatUploadPercentText');
        const p = Math.min(100, Math.max(10, Math.round(percent)));
        if (fillEl) fillEl.style.width = p + '%';
        if (percentEl) percentEl.textContent = p + '%';
    };

    const hideUploadBanner = () => {
        const banner = document.getElementById('chatUploadingBanner');
        if (banner) banner.style.display = 'none';
    };

    const sendChatAjaxWithProgress = (formData, filename = null) => {
        return new Promise((resolve, reject) => {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (csrfToken && !formData.has('csrf_token')) {
                formData.append('csrf_token', csrfToken);
            }

            if (filename) showUploadBanner(filename);
            
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo BASE_URL; ?>/ajax/soporte.php', true);
            if (csrfToken) {
                xhr.setRequestHeader('X-CSRF-Token', csrfToken);
            }

            if (filename && xhr.upload) {
                xhr.upload.onprogress = (e) => {
                    if (e.lengthComputable) {
                        const percent = (e.loaded / e.total) * 100;
                        updateUploadProgress(percent);
                    }
                };
            }

            xhr.onload = () => {
                hideUploadBanner();
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        resolve(JSON.parse(xhr.responseText));
                    } catch (err) {
                        reject(err);
                    }
                } else {
                    reject(new Error('Error en el servidor'));
                }
            };

            xhr.onerror = () => {
                hideUploadBanner();
                reject(new Error('Error de red'));
            };

            xhr.send(formData);
        });
    };

    const sendAudioMessage = async (audioBlob) => {
        const btnSend = document.getElementById('btnSendMessage');
        if (btnSend) btnSend.disabled = true;

        const tempId = 'opt_audio_' + Date.now();
        const container = document.getElementById('chatMessages');
        container.innerHTML += `
            <div class="message-bubble message-sent sending-optimistic" id="${tempId}">
                <div style="font-size:0.75rem; font-weight:700; color:var(--primary-color); margin-bottom:3px;">Tú</div>
                <div style="font-size:0.85rem; font-weight:600;"><i class="ph-fill ph-microphone"></i> Grabación de audio...</div>
                <div class="sending-status-tag"><i class="ph ph-spinner spinner"></i> Subiendo a Google Drive...</div>
            </div>`;
        container.scrollTop = container.scrollHeight;

        const fd = new FormData();
        fd.append('action', 'send_message');
        fd.append('ticket_id', currentChatId);
        fd.append('message', '');
        fd.append('attachment', audioBlob, 'audio_record.webm');

        try {
            const res = await sendChatAjaxWithProgress(fd, 'Nota de Voz.webm');
            const optEl = document.getElementById(tempId);
            if (optEl) optEl.remove();
            if (res.success) {
                loadMessages();
            } else {
                window.showToast(res.message || 'Error al enviar audio', 'error');
            }
        } catch(e) {
            const optEl = document.getElementById(tempId);
            if (optEl) optEl.remove();
            window.showToast('Error al enviar audio', 'error');
        }
        
        if (btnSend) btnSend.disabled = false;
    };

    const sendMessage = async () => {
        const input = document.getElementById('messageInput');
        const text = input.value.trim();
        const fileToSend = selectedFile;

        if((!text && !fileToSend) || !currentChatId) return;

        input.value = '';
        input.style.height = '';
        if (fileToSend) clearFileSelection();
        const btnSend = document.getElementById('btnSendMessage');
        if (btnSend) btnSend.disabled = true;

        const tempId = 'opt_msg_' + Date.now();
        const container = document.getElementById('chatMessages');
        let fileText = fileToSend ? `<div style="font-size:0.85rem; margin-top:4px; font-weight:600; color:#3b82f6;"><i class="ph ph-file"></i> ${escapeHtml(fileToSend.name)}</div>` : '';
        container.innerHTML += `
            <div class="message-bubble message-sent sending-optimistic" id="${tempId}">
                <div style="font-size:0.75rem; font-weight:700; color:var(--primary-color); margin-bottom:3px;">Tú</div>
                <div>${escapeHtml(text)}</div>
                ${fileText}
                <div class="sending-status-tag"><i class="ph ph-spinner spinner"></i> ${fileToSend ? 'Subiendo a Google Drive...' : 'Enviando...'}</div>
            </div>`;
        container.scrollTop = container.scrollHeight;

        const fd = new FormData();
        fd.append('action', 'send_message');
        fd.append('ticket_id', currentChatId);
        fd.append('message', text);
        
        const executeSend = async () => {
            try {
                const res = await sendChatAjaxWithProgress(fd, fileToSend ? fileToSend.name : null);
                const optEl = document.getElementById(tempId);
                if (optEl) optEl.remove();

                if(res.success) {
                    loadMessages();
                    updateMainButton();
                } else {
                    window.showToast(res.message || 'Error al enviar mensaje', 'error');
                }
            } catch(e) {
                const optEl = document.getElementById(tempId);
                if (optEl) optEl.remove();
                if (btnSend) btnSend.disabled = false;
                window.showToast('Error de conexión', 'error');
            }
        };

        if (fileToSend) {
            fd.append('attachment', fileToSend);
            if (fileToSend.type.startsWith('image/')) {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        (pos) => {
                            fd.append('latitude', pos.coords.latitude);
                            fd.append('longitude', pos.coords.longitude);
                            executeSend();
                        },
                        (err) => {
                            // GPS denegado o error, igual enviamos sin coordenadas
                            executeSend();
                        },
                        { timeout: 5000 }
                    );
                    return; // Wait for callback
                }
            }
        }
        
        executeSend();
        
        if (btnSend) btnSend.disabled = false;
    };

    const formatTime = (dateStr) => {
        const d = new Date(dateStr);
        return d.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    };

    const openChat = (ticket) => {
        currentChatId = ticket.id;
        lastMessageId = 0;
        const clientName = ticket.cliente_nombre || 'Cliente Anónimo';
        document.getElementById('chatAvatar').innerText = clientName.substring(0,1).toUpperCase();
        document.getElementById('chatTitle').innerText = clientName;
        document.getElementById('chatSubtitle').innerText = `TKT-${ticket.id.toString().padStart(4, '0')}`;
        
        // Cargar botones de contacto
        const btnWsp = document.getElementById('btnWhatsapp');
        const btnCall = document.getElementById('btnCall');
        const btnMap = document.getElementById('btnMap');
        
        if (ticket.cliente_celular) {
            btnWsp.href = `https://wa.me/${ticket.cliente_celular.replace(/\D/g, '')}`;
            btnWsp.style.display = 'inline-flex';
            btnCall.href = `tel:${ticket.cliente_celular}`;
            btnCall.style.display = 'inline-flex';
        } else {
            btnWsp.style.display = 'none';
            btnCall.style.display = 'none';
        }

        if (ticket.cliente_direccion) {
            btnMap.href = `https://maps.google.com/?q=${encodeURIComponent(ticket.cliente_direccion)}`;
            btnMap.style.display = 'inline-flex';
        } else {
            btnMap.style.display = 'none';
        }

        // Cargar selects
        document.getElementById('chatAssignSelect').value = ticket.assigned_to || '';
        document.getElementById('chatStatusSelect').value = ticket.estado;

        document.getElementById('chatMessages').innerHTML = '';
        document.getElementById('chatSidebar').classList.add('open');
        
        // Handle input area visibility dynamically
        const inputArea = document.querySelector('.chat-input-area');
        if (ticket.estado === 'terminado') {
            inputArea.style.display = 'none';
            if (!document.getElementById('terminatedWarningAdmin')) {
                inputArea.insertAdjacentHTML('beforebegin', '<div id="terminatedWarningAdmin" style="text-align:center; padding:15px; color:#ef4444; font-weight:bold; background:#fee2e2; border-top:1px solid #fca5a5;">El ticket está TERMINADO. No puedes enviar más mensajes.</div>');
            }
            document.getElementById('terminatedWarningAdmin').style.display = 'block';
        } else {
            inputArea.style.display = 'flex';
            if (document.getElementById('terminatedWarningAdmin')) document.getElementById('terminatedWarningAdmin').style.display = 'none';
        }
        
        // Auto-change to en_proceso if it is nuevo
        if (ticket.estado === 'nuevo') {
            updateTicketStatus('en_proceso', false);
            ticket.estado = 'en_proceso';
            document.getElementById('chatStatusSelect').value = 'en_proceso';
        }
        
        // Mark as read immediately
        const fd = new FormData();
        fd.append('action', 'mark_as_read');
        fd.append('ticket_id', ticket.id);
        fetch('<?php echo BASE_URL; ?>/ajax/soporte.php', { method: 'POST', body: fd }).then(r=>r.json()).then(()=>{
            loadTickets(); // Refresh badges
            loadMessages();
        });
    };

    const updateTicketStatus = async (status, reload = true) => {
        if(!currentChatId) return;
        const fd = new FormData();
        fd.append('action', 'update_status');
        fd.append('ticket_id', currentChatId);
        fd.append('estado', status);
        const res = await fetch('<?php echo BASE_URL; ?>/ajax/soporte.php', { method: 'POST', body: fd }).then(r=>r.json());
        
        // Handle input area visibility dynamically
        const inputArea = document.querySelector('.chat-input-area');
        if (status === 'terminado') {
            inputArea.style.display = 'none';
            if (!document.getElementById('terminatedWarningAdmin')) {
                inputArea.insertAdjacentHTML('beforebegin', '<div id="terminatedWarningAdmin" style="text-align:center; padding:15px; color:#ef4444; font-weight:bold; background:#fee2e2; border-top:1px solid #fca5a5;">El ticket está TERMINADO. No puedes enviar más mensajes.</div>');
            }
            document.getElementById('terminatedWarningAdmin').style.display = 'block';
        } else {
            inputArea.style.display = 'flex';
            if (document.getElementById('terminatedWarningAdmin')) document.getElementById('terminatedWarningAdmin').style.display = 'none';
        }
        
        if(res.success && reload) {
            window.showToast('Estado actualizado', 'success');
            loadTickets();
        }
    };

    const updateTicketAssignee = async (assignee) => {
        if(!currentChatId) return;
        const fd = new FormData();
        fd.append('action', 'assign_ticket');
        fd.append('ticket_id', currentChatId);
        fd.append('assigned_to', assignee);
        const res = await fetch('<?php echo BASE_URL; ?>/ajax/soporte.php', { method: 'POST', body: fd }).then(r=>r.json());
        if(res.success) {
            window.showToast('Asignación actualizada', 'success');
            loadTickets();
        }
    };

    const closeChat = () => {
        document.getElementById('chatSidebar').classList.remove('open');
        currentChatId = null;
    };

    let isPollingMessages = false;
    const loadMessages = async () => {
        if(!currentChatId || isPollingMessages) return;
        isPollingMessages = true;
        try {
            const fd = new FormData();
            fd.append('action', 'get_messages');
            fd.append('ticket_id', currentChatId);
            fd.append('last_id', lastMessageId);

            const res = await fetch('<?php echo BASE_URL; ?>/ajax/soporte.php', { method: 'POST', body: fd, cache: 'no-store' }).then(r=>r.json());
            if(res.success && res.data.length > 0) {
                const container = document.getElementById('chatMessages');
                res.data.forEach(msg => {
                    if (msg.is_system_message == 1) {
                        container.innerHTML += `<div class="sys-message">${msg.message}</div>`;
                    } else {
                        const isMe = msg.user_id == currentUserId;
                        const bubbleClass = isMe ? 'message-sent' : 'message-received';
                        const userName = isMe ? 'Tú' : (msg.user_name || 'Cliente');
                        
                        let msgContent = msg.message.replace(/\n/g, '<br>');
                        
                        // Parse Location
                        if (msgContent.startsWith('[LOCATION:') && msgContent.endsWith(']')) {
                            const coords = msgContent.replace('[LOCATION:', '').replace(']', '');
                            msgContent = `
                                <div onclick="openLocationViewer('${coords}')" class="loc-card" style="cursor: pointer;">
                                    <div style="background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 10px; border-radius: 10px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(16,185,129,0.3);"><i class="ph-fill ph-navigation-arrow" style="font-size: 1.3rem;"></i></div>
                                    <div>
                                        <div style="font-weight: 700; font-size: 0.88rem;">Ubicación compartida</div>
                                        <div style="font-size: 0.75rem; color: #10b981; font-weight: 600; display: flex; align-items: center; gap: 4px; margin-top: 2px;"><i class="ph-fill ph-map-pin"></i> Ver en Mapa App Interactivo</div>
                                    </div>
                                </div>
                            `;
                        }

                        // Attachments
                        let attHtml = '';
                        if (msg.attachments && msg.attachments.length > 0) {
                            msg.attachments.forEach(att => {
                                let url = att.file_path;
                                if (!url.startsWith('http://') && !url.startsWith('https://')) {
                                    url = `<?php echo BASE_URL; ?>/` + url;
                                }
                                const ext = att.file_name.split('.').pop().toLowerCase();
                                if (['webm', 'mp3', 'ogg', 'wav', 'm4a'].includes(ext)) {
                                    attHtml += `<audio controls src="${url}" style="max-width: 100%; margin-top: 5px; outline: none; height: 35px;"></audio>`;
                                } else {
                                    attHtml += `<img src="${url}" referrerpolicy="no-referrer" onclick="openLightbox('${url}')" style="cursor: pointer; max-width: 100%; border-radius: 8px; margin-top: 5px; border: 1px solid rgba(0,0,0,0.1); transition: opacity 0.2s;" onmouseover="this.style.opacity=0.9" onmouseout="this.style.opacity=1">`;
                                }
                            });
                        }
                        
                        container.innerHTML += `
                            <div class="message-bubble ${bubbleClass}">
                                ${!isMe ? `<div style="font-size:0.75rem; font-weight:700; color:var(--primary-color); margin-bottom:3px;">${userName}</div>` : ''}
                                <div>${msgContent}</div>
                                ${attHtml}
                                <span class="message-time">${formatTime(msg.created_at)}</span>
                            </div>
                        `;
                    }
                    lastMessageId = msg.id;
                });
                container.scrollTop = container.scrollHeight;
                
                // Si cargó nuevos mensajes, refrescar los tickets para que se quite el globo rojo si estamos leyendo.
                loadTickets();
            }
        } catch(e) {}
        isPollingMessages = false;
    };

    const stopPollingMessages = () => {
        isPollingMessages = false;
    };

    // Lightbox handling
    const openLightbox = (src) => {
        document.getElementById('lightboxImg').src = src;
        document.getElementById('imageLightbox').style.display = 'flex';
    };

    let selectedFile = null;
    
    // Cámara en Vivo (Cámara trasera por defecto)
    const chatCameraInput = document.createElement('input');
    chatCameraInput.type = 'file';
    chatCameraInput.accept = 'image/*';
    chatCameraInput.capture = 'environment';
    chatCameraInput.onchange = (e) => handleFileSelect(e.target);

    // Selección de Galería / Documentos
    const chatGalleryInput = document.createElement('input');
    chatGalleryInput.type = 'file';
    chatGalleryInput.accept = 'image/*,video/*,application/pdf,.doc,.docx,.xls,.xlsx';
    chatGalleryInput.onchange = (e) => handleFileSelect(e.target);

    const openCameraInput = () => triggerSmartCameraInput();
    const openGalleryInput = () => chatGalleryInput.click();

    const toggleActionMenu = () => {
        const menu = document.getElementById('chatActionMenu');
        menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
    };

    const clearFileSelection = () => {
        selectedFile = null;
        chatCameraInput.value = '';
        chatGalleryInput.value = '';
        document.getElementById('filePreviewContainer').style.display = 'none';
        updateMainButton();
    };

    const handleFileSelect = (input) => {
        if (input.files && input.files[0]) {
            selectedFile = input.files[0];
            document.getElementById('filePreviewName').innerText = selectedFile.name;
            document.getElementById('filePreviewContainer').style.display = 'flex';
            updateMainButton();
        }
    };

    const sendLocation = () => {
        if (!navigator.geolocation) {
            window.showToast('Tu navegador no soporta geolocalización', 'error');
            return;
        }
        navigator.geolocation.getCurrentPosition(
            async (position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                const fd = new FormData();
                fd.append('action', 'send_message');
                fd.append('ticket_id', currentChatId);
                fd.append('message', `[LOCATION:${lat},${lng}]`);
                try {
                    const res = await fetch('<?php echo BASE_URL; ?>/ajax/soporte.php', { method: 'POST', body: fd }).then(r=>r.json());
                    if(res.success) loadMessages();
                } catch(e) {}
            },
            (error) => {
                window.showToast('No se pudo obtener la ubicación', 'error');
            }
        );
    };

    // Voice Notes Logic
    let isAudioRecording = false;
    let audioMediaRecorder = null;
    let audioRecordChunks = [];
    let audioRecordingTimerInterval = null;
    let audioRecordingSeconds = 0;

    window.updateMainButton = () => {
        const text = document.getElementById('messageInput')?.value.trim() || '';
        const btnIcon = document.getElementById('btnSendMessageIcon');
        if (!btnIcon) return;
        if (text || selectedFile) {
            btnIcon.className = 'ph-fill ph-paper-plane-right';
        } else {
            btnIcon.className = 'ph-fill ph-microphone';
        }
    };

    window.handleMainAction = () => {
        const text = document.getElementById('messageInput')?.value.trim() || '';
        if (text || selectedFile) {
            sendMessage();
        } else {
            if (isAudioRecording) {
                stopRecordingAndSend();
            } else {
                startRecording();
            }
        }
    };

    window.startRecording = async () => {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            audioMediaRecorder = new MediaRecorder(stream);
            audioRecordChunks = [];
            
            audioMediaRecorder.addEventListener("dataavailable", event => {
                if (event.data && event.data.size > 0) {
                    audioRecordChunks.push(event.data);
                }
            });
            
            audioMediaRecorder.addEventListener("stop", () => {
                if (isAudioRecording) {
                    const mimeType = audioMediaRecorder?.mimeType || 'audio/webm';
                    const audioBlob = new Blob(audioRecordChunks, { type: mimeType });
                    sendAudioMessage(audioBlob);
                }
                isAudioRecording = false;
                stream.getTracks().forEach(track => track.stop());
                
                const recUi = document.getElementById('audioRecordingUi');
                if (recUi) recUi.style.display = 'none';
                const inputWrap = document.querySelector('.chat-input-wrapper');
                if (inputWrap) inputWrap.style.display = 'flex';
                updateMainButton();
                clearInterval(audioRecordingTimerInterval);
            });
            
            isAudioRecording = true;
            audioMediaRecorder.start();
            
            const inputWrap = document.querySelector('.chat-input-wrapper');
            if (inputWrap) inputWrap.style.display = 'none';
            const recUi = document.getElementById('audioRecordingUi');
            if (recUi) recUi.style.display = 'flex';
            const btnIcon = document.getElementById('btnSendMessageIcon');
            if (btnIcon) btnIcon.className = 'ph-fill ph-paper-plane-right';
            
            audioRecordingSeconds = 0;
            const timerEl = document.getElementById('recordingTimer');
            if (timerEl) timerEl.innerText = '00:00';
            audioRecordingTimerInterval = setInterval(() => {
                audioRecordingSeconds++;
                const m = String(Math.floor(audioRecordingSeconds / 60)).padStart(2, '0');
                const s = String(audioRecordingSeconds % 60).padStart(2, '0');
                if (timerEl) timerEl.innerText = `${m}:${s}`;
            }, 1000);
            
        } catch (e) {
            console.error('Mic access error:', e);
            window.showToast('No se pudo acceder al micrófono. Verifica los permisos.', 'error');
        }
    };

    window.cancelRecording = () => {
        isAudioRecording = false;
        if (audioMediaRecorder && audioMediaRecorder.state !== 'inactive') {
            audioMediaRecorder.stop();
        }
    };

    window.stopRecordingAndSend = () => {
        if (audioMediaRecorder && audioMediaRecorder.state !== 'inactive') {
            audioMediaRecorder.stop();
        }
    };



    // Custom confirm modal helper
    function soporteConfirm(title, msg) {
        return new Promise((resolve) => {
            const overlay = document.createElement('div');
            overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);backdrop-filter:blur(4px);z-index:99999;display:flex;align-items:center;justify-content:center;animation:fadeIn 0.15s ease;';
            overlay.innerHTML = `<div style="background:var(--surface-color,#1e293b);border-radius:14px;padding:30px;max-width:400px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,0.3);text-align:center;animation:scaleIn 0.2s ease;">
                <div style="font-size:2.5rem;color:#ef4444;margin-bottom:12px;"><i class="ph ph-warning-circle"></i></div>
                <div style="font-size:1.1rem;font-weight:700;margin-bottom:8px;">${title}</div>
                <div style="font-size:0.9rem;color:var(--text-muted);margin-bottom:20px;">${msg}</div>
                <div style="display:flex;gap:10px;justify-content:center;">
                    <button id="scNo" style="padding:10px 24px;border-radius:8px;font-weight:600;cursor:pointer;border:1px solid var(--border-color);background:#334155;color:var(--text-color);transition:all 0.2s;">Cancelar</button>
                    <button id="scYes" style="padding:10px 24px;border-radius:8px;font-weight:600;cursor:pointer;border:1px solid #ef4444;background:#ef4444;color:#fff;transition:all 0.2s;">Confirmar</button>
                </div>
            </div>`;
            document.body.appendChild(overlay);
            overlay.querySelector('#scYes').onclick = () => { overlay.remove(); resolve(true); };
            overlay.querySelector('#scNo').onclick = () => { overlay.remove(); resolve(false); };
            overlay.addEventListener('click', (e) => { if (e.target === overlay) { overlay.remove(); resolve(false); } });
        });
    }

    window.trashTicket = async (e, id) => {
        e.stopPropagation();
        const ok = await soporteConfirm('\u00bfMover a la papelera?', 'El ticket se podr\u00e1 recuperar desde la papelera por 15 d\u00edas.');
        if(!ok) return;
        const fd = new FormData(); fd.append('action', 'trash_ticket'); fd.append('ticket_id', id);
        try {
            const res = await fetch('<?php echo BASE_URL; ?>/ajax/soporte.php', { method: 'POST', body: fd }).then(r=>r.json());
            if(res.success) { window.showToast(res.message, 'success'); loadTickets(); } else window.showToast(res.message, 'error');
        } catch(err) { window.showToast('Error', 'error'); }
    };

    window.openTrashModal = async () => {
        document.getElementById('trashModal').classList.add('active');
        loadTrashTickets();
    };

    window.loadTrashTickets = async () => {
        const fd = new FormData(); fd.append('action', 'list'); fd.append('is_trash', 1);
        try {
            const res = await fetch('<?php echo BASE_URL; ?>/ajax/soporte.php', { method: 'POST', body: fd }).then(r=>r.json());
            if(res.success) {
                const tbody = document.getElementById('trashTableBody');
                if (res.data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center">La papelera está vacía.</td></tr>';
                } else {
                    const esc = str => (str||'').replace(/[&<>'"]/g, tag => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'}[tag]));
                    tbody.innerHTML = res.data.map(t => `
                        <tr>
                            <td>#TKT-${t.id.toString().padStart(4, '0')}</td>
                            <td>${esc(t.asunto)}</td>
                            <td>${esc(t.cliente_nombre || 'Anónimo')}</td>
                            <td>
                                <button class="btn btn-sm btn-danger" onclick="hardDeleteTicket(${t.id})" title="Eliminar permanentemente"><i class="ph ph-trash"></i></button>
                            </td>
                        </tr>
                    `).join('');
                }
            }
        } catch(err) { console.error(err); }
    };

    window.hardDeleteTicket = async (id) => {
        const ok = await soporteConfirm('\u00bfEliminar permanentemente?', 'Esta acci\u00f3n no se puede deshacer. El ticket se borrar\u00e1 para siempre.');
        if(!ok) return;
        const fd = new FormData(); fd.append('action', 'hard_delete_ticket'); fd.append('ticket_id', id);
        try {
            const res = await fetch('<?php echo BASE_URL; ?>/ajax/soporte.php', { method: 'POST', body: fd }).then(r=>r.json());
            if(res.success) { window.showToast(res.message, 'success'); loadTrashTickets(); } else window.showToast(res.message, 'error');
        } catch(err) { window.showToast('Error', 'error'); }
    };

</script>

<?php include '../../includes/footer.php'; ?>
