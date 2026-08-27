<?php
require_once '../../config/db.php';
requireLogin();
requirePermission($pdo, 'soporte');

$ticket_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Verificar acceso al ticket
$ticket = null;
if ($ticket_id > 0) {
    $stmt = $pdo->prepare("
        SELECT t.*, c.nombre_completo as cliente_nombre, c.dni as cliente_dni, u.name as tech_name
        FROM tickets t
        LEFT JOIN clientes c ON t.cliente_id = c.id
        LEFT JOIN users u ON t.assigned_to = u.id
        WHERE t.id = ?
    ");
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch();
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>
<!-- Añadir script de Google Maps -->
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAzf2GmB9lw1k7ONXk1VHScmd-pe-FtMtE&libraries=places"></script>

<style>
    .chat-layout {
        display: flex;
        height: calc(100vh - 100px);
        background: var(--surface-color);
        border-radius: 16px;
        overflow: hidden;
        border: 1px solid var(--border-color);
        box-shadow: var(--shadow);
    }

    /* COLUMNA IZQUIERDA: LISTA DE TICKETS */
    .chat-sidebar {
        width: 350px;
        border-right: 1px solid var(--border-color);
        display: flex;
        flex-direction: column;
        background: var(--bg-color);
    }
    .chat-sidebar-header {
        padding: 15px 20px;
        border-bottom: 1px solid var(--border-color);
        background: var(--surface-color);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .chat-sidebar-header h3 {
        margin: 0;
        font-size: 1.2rem;
        font-weight: 700;
    }
    .chat-search {
        padding: 10px;
        border-bottom: 1px solid var(--border-color);
    }
    .chat-search input {
        width: 100%;
        padding: 10px 15px;
        border-radius: 20px;
        border: 1px solid var(--border-color);
        background: var(--surface-color);
        color: var(--text-color);
        outline: none;
    }
    .chat-list {
        flex: 1;
        overflow-y: auto;
    }
    .chat-list-item {
        display: flex;
        padding: 15px;
        border-bottom: 1px solid var(--border-color);
        cursor: pointer;
        transition: background 0.2s;
        text-decoration: none;
        color: inherit;
    }
    .chat-list-item:hover, .chat-list-item.active {
        background: rgba(0,0,0,0.03);
    }
    body.dark-theme .chat-list-item:hover, body.dark-theme .chat-list-item.active {
        background: rgba(255,255,255,0.05);
    }
    .chat-list-avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: var(--primary-color);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1.2rem;
        margin-right: 15px;
        flex-shrink: 0;
    }
    .chat-list-info {
        flex: 1;
        overflow: hidden;
    }
    .chat-list-name {
        font-weight: 700;
        margin-bottom: 3px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .chat-list-preview {
        font-size: 0.85rem;
        color: var(--text-muted);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .chat-list-time {
        font-size: 0.75rem;
        color: var(--text-muted);
    }

    /* COLUMNA DERECHA: CONVERSACIÓN */
    .chat-main {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: url('<?php echo BASE_URL; ?>/assets/img/chat-bg.png') repeat;
        background-color: #efeae2;
    }
    body.dark-theme .chat-main {
        background-color: #0b141a;
        background-image: none;
    }

    .chat-main-header {
        padding: 15px 20px;
        background: var(--surface-color);
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .chat-main-profile {
        display: flex;
        align-items: center;
    }
    .chat-main-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--primary-color);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        margin-right: 15px;
    }
    .chat-main-name {
        font-weight: 700;
        font-size: 1.1rem;
    }
    .chat-main-status {
        font-size: 0.8rem;
        color: var(--text-muted);
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
        max-width: 65%;
        padding: 10px 15px;
        border-radius: 12px;
        position: relative;
        font-size: 0.95rem;
        line-height: 1.4;
        box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        word-wrap: break-word;
    }
    /* Recibido (Cliente/Otro) */
    .message-received {
        align-self: flex-start;
        background: #ffffff;
        color: #111b21;
        border-top-left-radius: 0;
    }
    /* Enviado (Yo) */
    .message-sent {
        align-self: flex-end;
        background: #d9fdd3;
        color: #111b21;
        border-top-right-radius: 0;
    }
    
    body.dark-theme .message-received {
        background: #202c33;
        color: #e9edef;
    }
    body.dark-theme .message-sent {
        background: #005c4b;
        color: #e9edef;
    }

    .message-time {
        font-size: 0.65rem;
        color: rgba(0,0,0,0.45);
        text-align: right;
        margin-top: 5px;
        display: block;
    }
    body.dark-theme .message-time {
        color: rgba(255,255,255,0.6);
    }

    .message-delete-btn {
        position: absolute;
        top: 5px;
        right: 5px;
        background: rgba(255,255,255,0.8);
        color: #ef4444;
        border: none;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        opacity: 0;
        transition: opacity 0.2s;
        font-size: 1rem;
        z-index: 10;
        box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    }
    .message-bubble:hover .message-delete-btn {
        opacity: 1;
    }
    body.dark-theme .message-delete-btn {
        background: rgba(0,0,0,0.6);
    }

    .chat-input-area {
        padding: 15px;
        background: var(--surface-color);
        border-top: 1px solid var(--border-color);
        display: flex;
        align-items: flex-end;
        gap: 10px;
    }
    .chat-input-area .btn-icon {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background: transparent;
        color: var(--text-muted);
        border: none;
        font-size: 1.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background 0.2s;
    }
    .chat-input-area .btn-icon:hover {
        background: rgba(0,0,0,0.05);
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
    
    .chat-input-area .btn-send {
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
    }

    .empty-chat {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        color: var(--text-muted);
        text-align: center;
        background: var(--surface-color);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .chat-sidebar {
            display: <?php echo $ticket_id > 0 ? 'none' : 'flex'; ?>;
            width: 100%;
        }
        .chat-main {
            display: <?php echo $ticket_id > 0 ? 'flex' : 'none'; ?>;
        }
        .message-delete-btn {
            opacity: 1; /* Siempre visible en móviles */
            background: rgba(255,255,255,0.6);
        }
    }
</style>

<div class="chat-layout">
    <!-- BARRA LATERAL -->
    <div class="chat-sidebar">
        <div class="chat-sidebar-header">
            <h3>Chats de Soporte</h3>
            <a href="index.php" class="btn btn-sm btn-outline-secondary" title="Volver al Dashboard"><i class="ph ph-squares-four"></i></a>
        </div>
        <div class="chat-search">
            <input type="text" id="chatSearchInput" placeholder="Buscar ticket o cliente...">
        </div>
        <div class="chat-list" id="chatList">
            <!-- Cargado vía AJAX -->
            <div style="padding: 20px; text-align: center; color: var(--text-muted);">Cargando chats...</div>
        </div>
    </div>

    <!-- ÁREA PRINCIPAL -->
    <?php if ($ticket): ?>
    <div class="chat-main">
        <div id="collisionAlert" style="display:none; background:#fee2e2; color:#ef4444; padding:10px; text-align:center; font-weight:bold; border-bottom:1px solid #fca5a5;">
            ⚠️ <span id="collisionText">Alguien ya está respondiendo este ticket</span>
        </div>
        <div class="chat-main-header">
            <div class="chat-main-profile">
                <!-- Botón Volver solo visible en móvil -->
                <button class="btn btn-sm" style="background:transparent; border:none; font-size:1.5rem; margin-right:10px; display:none;" id="btnBackMobile" onclick="window.location.href='chat.php'"><i class="ph ph-arrow-left"></i></button>
                
                <div class="chat-main-avatar">
                    <?php echo strtoupper(substr($ticket['cliente_nombre'], 0, 1)); ?>
                </div>
                <div>
                    <div class="chat-main-name"><?php echo htmlspecialchars($ticket['cliente_nombre']); ?></div>
                    <div class="chat-main-status">TKT-<?php echo str_pad($ticket['id'], 4, '0', STR_PAD_LEFT); ?> | <?php echo htmlspecialchars($ticket['asunto']); ?></div>
                    <div id="typingIndicator" style="display:none; color:var(--primary-color); font-size:0.8rem; font-style:italic; margin-top:2px;">Cliente está escribiendo...</div>
                </div>
            </div>
            <div>
                <select id="estadoTicket" class="form-select form-select-sm" style="width:auto; display:inline-block;" onchange="updateTicketStatus(this.value)">
                    <option value="abierto" <?php echo $ticket['estado'] == 'abierto' ? 'selected' : ''; ?>>Abierto</option>
                    <option value="pendiente" <?php echo $ticket['estado'] == 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                    <option value="en_proceso" <?php echo $ticket['estado'] == 'en_proceso' ? 'selected' : ''; ?>>En Proceso</option>
                    <option value="terminado" <?php echo $ticket['estado'] == 'terminado' ? 'selected' : ''; ?>>Terminado</option>
                </select>
            </div>
        </div>

        <div class="chat-messages" id="chatMessages">
            <!-- Mensajes cargados vía AJAX -->
        </div>

        <div class="chat-input-area">
            <input type="file" id="chatFileInput" style="display:none;" accept="image/*,video/*,application/pdf,.doc,.docx,.xls,.xlsx">
            <button class="btn-icon" title="Adjuntar archivo" onclick="document.getElementById('chatFileInput').click();"><i class="ph ph-paperclip"></i></button>
            <button class="btn-icon" title="Enviar Ubicación" onclick="openLocationModal();"><i class="ph ph-map-pin"></i></button>
            <div class="chat-input-wrapper">
                <div id="chatFilePreview" style="display:none; font-size:0.8rem; background:#f1f5f9; padding:4px 8px; border-radius:4px; margin-bottom:4px; font-weight:600; color:#3b82f6;"></div>
                <textarea id="messageInput" placeholder="Escribe un mensaje..." rows="1" oninput="this.style.height = ''; this.style.height = this.scrollHeight + 'px'"></textarea>
            </div>
            <button class="btn-send" id="btnSendMessage"><i class="ph-fill ph-paper-plane-right"></i></button>
        </div>
    </div>
    <?php else: ?>
    <div class="empty-chat">
        <i class="ph ph-chats" style="font-size: 5rem; color: var(--border-color); margin-bottom: 20px;"></i>
        <h3>Selecciona un chat para comenzar</h3>
        <p>Los tickets de soporte aparecerán en el panel izquierdo.</p>
    </div>
    <?php endif; ?>
</div>

<script>
    const currentTicketId = <?php echo $ticket_id; ?>;
    const currentUserId = <?php echo $_SESSION['user_id'] ?? 0; ?>;
    const currentUserRole = '<?php echo $_SESSION['user_role'] ?? 'user'; ?>';
    let lastMessageId = 0;
    let sseSource = null;
    let isFetchingOlder = false;
    let noMoreMessages = false;
    let typingTimeout = null;

    const formatTime = (dateStr) => {
        const d = new Date(dateStr);
        return d.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    };

    const loadChatList = async () => {
        try {
            const formData = new FormData();
            formData.append('action', 'list');
            const res = await fetch('<?php echo BASE_URL; ?>/ajax/soporte.php', { method: 'POST', body: formData }).then(r=>r.json());
            
            if (res.success) {
                const list = document.getElementById('chatList');
                list.innerHTML = '';
                if(res.data.length === 0) {
                    list.innerHTML = '<div style="padding: 20px; text-align: center; color: var(--text-muted);">No hay tickets disponibles.</div>';
                    return;
                }
                res.data.forEach(t => {
                    const initials = t.cliente_nombre ? t.cliente_nombre.substring(0,1).toUpperCase() : '?';
                    const activeClass = t.id == currentTicketId ? 'active' : '';
                    list.innerHTML += `
                        <a href="chat.php?id=${t.id}" class="chat-list-item ${activeClass}">
                            <div class="chat-list-avatar">${initials}</div>
                            <div class="chat-list-info">
                                <div style="display:flex; justify-content:space-between;">
                                    <div class="chat-list-name">${t.cliente_nombre || 'Desconocido'}</div>
                                    <div class="chat-list-time">${t.created_at.split(' ')[0]}</div>
                                </div>
                                <div class="chat-list-preview">${t.asunto}</div>
                            </div>
                        </a>
                    `;
                });
            }
        } catch(e) {}
    };

    const renderMessage = (msg, prepend = false) => {
        const container = document.getElementById('chatMessages');
        if(document.getElementById(`msg-${msg.id}`)) return; // Evitar duplicados
        
        const isMe = msg.user_id == currentUserId;
        const bubbleClass = isMe ? 'message-sent' : 'message-received';
        const userName = isMe ? 'Tú' : (msg.user_name || 'Cliente');
        
        let attHtml = '';
        if (msg.attachments && msg.attachments.length > 0) {
            msg.attachments.forEach(att => {
                const isImg = att.file_name.match(/\.(jpg|jpeg|png|gif|webp)$/i);
                if (isImg) {
                    attHtml += `<div style="margin-bottom:8px;"><a href="${att.file_path}" target="_blank"><img src="${att.file_path}" style="max-width:100%; border-radius:8px; cursor:pointer;" alt="adjunto"></a></div>`;
                } else {
                    attHtml += `<div style="margin-bottom:8px;"><a href="${att.file_path}" target="_blank" class="btn btn-sm btn-light" style="display:block; text-align:center;"><i class="ph ph-download-simple"></i> ${att.file_name}</a></div>`;
                }
            });
        }

        let checksHtml = '';
        if (isMe) {
            const checkColor = msg.is_read == 1 ? '#3b82f6' : '#9ca3af';
            checksHtml = `<span class="message-checks" style="color:${checkColor}; margin-left:5px; font-size:0.8rem;"><i class="ph-bold ph-checks"></i></span>`;
        }

        const canDelete = isMe || currentUserRole === 'admin' || currentUserRole === 'administrador';

        let msgContent = msg.message ? msg.message.replace(/\n/g, '<br>') : '';
        if (msg.message && msg.message.startsWith('[LOCATION:')) {
            const coords = msg.message.replace('[LOCATION:', '').replace(']', '').split(',');
            const lat = coords[0];
            const lng = coords[1];
            const mapUrl = `https://maps.googleapis.com/maps/api/staticmap?center=${lat},${lng}&zoom=15&size=300x150&markers=color:red%7C${lat},${lng}&key=AIzaSyAzf2GmB9lw1k7ONXk1VHScmd-pe-FtMtE`;
            msgContent = `<div style="margin-bottom:8px;"><a href="https://maps.google.com/?q=${lat},${lng}" target="_blank"><img src="${mapUrl}" style="max-width:100%; border-radius:8px; cursor:pointer;" alt="Ubicación estática"></a><br><small>Ubicación (haz clic para abrir)</small></div>`;
        } else if (msg.message && msg.message.startsWith('[LIVE_LOCATION:')) {
            const parts = msg.message.replace('[LIVE_LOCATION:', '').replace(']', '').split(',');
            const lat = parts[0];
            const lng = parts[1];
            const mapUrl = `https://maps.googleapis.com/maps/api/staticmap?center=${lat},${lng}&zoom=15&size=300x150&markers=color:blue%7C${lat},${lng}&key=AIzaSyAzf2GmB9lw1k7ONXk1VHScmd-pe-FtMtE`;
            msgContent = `<div style="margin-bottom:8px;" class="live-location-container" data-user="${msg.user_id || 'client'}"><a href="https://maps.google.com/?q=${lat},${lng}" target="_blank"><img src="${mapUrl}" style="max-width:100%; border-radius:8px; cursor:pointer;" alt="Ubicación en tiempo real"></a><br><small style="color:var(--primary-color);">📍 Ubicación en tiempo real iniciada</small></div>`;
        }

        const html = `
            <div class="message-bubble ${bubbleClass}" id="msg-${msg.id}">
                ${canDelete ? `<button class="message-delete-btn" onclick="deleteMessage(${msg.id})" title="Eliminar mensaje"><i class="ph-bold ph-trash"></i></button>` : ''}
                ${!isMe ? `<div style="font-size:0.75rem; font-weight:700; color:var(--primary-color); margin-bottom:3px;">${userName}</div>` : ''}
                ${attHtml}
                <div>${msgContent}</div>
                <div style="display:flex; justify-content:flex-end; align-items:center; margin-top:5px;">
                    <span class="message-time">${formatTime(msg.created_at)}</span>
                    ${checksHtml}
                </div>
            </div>
        `;
        
        if (prepend) {
            const oldScrollHeight = container.scrollHeight;
            container.insertAdjacentHTML('afterbegin', html);
            container.scrollTop = container.scrollHeight - oldScrollHeight;
        } else {
            container.insertAdjacentHTML('beforeend', html);
            container.scrollTop = container.scrollHeight;
        }
    };

    const loadInitialMessages = async () => {
        if(!currentTicketId) return;
        try {
            const fd = new FormData();
            fd.append('action', 'get_messages');
            fd.append('ticket_id', currentTicketId);
            const res = await fetch('<?php echo BASE_URL; ?>/ajax/soporte.php', { method: 'POST', body: fd }).then(r=>r.json());
            if(res.success && res.data.length > 0) {
                res.data.forEach(msg => {
                    renderMessage(msg, false);
                    lastMessageId = Math.max(lastMessageId, msg.id);
                });
            }
            setupSSE();
        } catch(e) { console.error(e); }
    };

    const loadOlderMessages = async () => {
        if (!currentTicketId || isFetchingOlder || noMoreMessages) return;
        const container = document.getElementById('chatMessages');
        const firstMsgEl = container.querySelector('.message-bubble');
        if (!firstMsgEl) return;
        
        const firstId = firstMsgEl.id.replace('msg-', '');
        isFetchingOlder = true;
        
        try {
            const fd = new FormData();
            fd.append('action', 'get_messages');
            fd.append('ticket_id', currentTicketId);
            fd.append('older_than_id', firstId);
            const res = await fetch('<?php echo BASE_URL; ?>/ajax/soporte.php', { method: 'POST', body: fd }).then(r=>r.json());
            if (res.success && res.data.length > 0) {
                for (let i = res.data.length - 1; i >= 0; i--) {
                    renderMessage(res.data[i], true);
                }
            } else {
                noMoreMessages = true;
            }
        } catch(e) {}
        isFetchingOlder = false;
    };

    const setupSSE = () => {
        if(sseSource) sseSource.close();
        sseSource = new EventSource(`<?php echo BASE_URL; ?>/ajax/sse_soporte.php?ticket_id=${currentTicketId}&last_id=${lastMessageId}`);
        
        sseSource.addEventListener('new_messages', (e) => {
            const messages = JSON.parse(e.data);
            let hasNew = false;
            messages.forEach(msg => {
                if(msg.id > lastMessageId) {
                    renderMessage(msg, false);
                    lastMessageId = msg.id;
                    hasNew = true;
                }
            });
            if(hasNew) markAsRead();
        });

        sseSource.addEventListener('status_update', (e) => {
            const status = JSON.parse(e.data);
            document.getElementById('typingIndicator').style.display = status.is_typing ? 'block' : 'none';
            if (status.last_read_id > 0) {
                document.querySelectorAll('.message-sent .message-checks').forEach(el => {
                    const msgDiv = el.closest('.message-bubble');
                    const mId = parseInt(msgDiv.id.replace('msg-', ''));
                    if (mId <= status.last_read_id) {
                        el.style.color = '#3b82f6';
                    }
                });
            }
            if (status.live_lat && status.live_lng) {
                document.querySelectorAll('.live-location-container img').forEach(img => {
                    // Update only if it's the other person's live location (or both for simplicity)
                    const mapUrl = `https://maps.googleapis.com/maps/api/staticmap?center=${status.live_lat},${status.live_lng}&zoom=15&size=300x150&markers=color:blue%7C${status.live_lat},${status.live_lng}&key=AIzaSyAzf2GmB9lw1k7ONXk1VHScmd-pe-FtMtE`;
                    if (img.src !== mapUrl) {
                        img.src = mapUrl;
                        img.parentElement.href = `https://maps.google.com/?q=${status.live_lat},${status.live_lng}`;
                    }
                });
            }
        });
    };

    const markAsRead = () => {
        const fd = new FormData();
        fd.append('action', 'mark_as_read');
        fd.append('ticket_id', currentTicketId);
        fetch('<?php echo BASE_URL; ?>/ajax/soporte.php', { method: 'POST', body: fd });
    };

    const notifyTyping = () => {
        const fd = new FormData();
        fd.append('action', 'set_typing');
        fd.append('ticket_id', currentTicketId);
        fetch('<?php echo BASE_URL; ?>/ajax/soporte.php', { method: 'POST', body: fd });
    };

    const chatPing = async () => {
        const fd = new FormData();
        fd.append('action', 'chat_ping');
        fd.append('ticket_id', currentTicketId);
        try {
            const res = await fetch('<?php echo BASE_URL; ?>/ajax/soporte.php', { method: 'POST', body: fd }).then(r=>r.json());
            const alertEl = document.getElementById('collisionAlert');
            const inputEl = document.getElementById('messageInput');
            const btnEl = document.getElementById('btnSendMessage');
            
            if(res.success && res.locked) {
                alertEl.style.display = 'block';
                document.getElementById('collisionText').innerText = res.locked_by + ' ya está respondiendo este ticket';
                inputEl.disabled = true;
                btnEl.disabled = true;
            } else {
                alertEl.style.display = 'none';
                inputEl.disabled = false;
                btnEl.disabled = false;
            }
        } catch(e){}
    };

    let selectedChatFile = null;
    document.getElementById('chatFileInput').addEventListener('change', (e) => {
        if(e.target.files && e.target.files[0]) {
            selectedChatFile = e.target.files[0];
            const pv = document.getElementById('chatFilePreview');
            pv.innerText = 'Adjunto: ' + selectedChatFile.name;
            pv.style.display = 'block';
        }
    });

    const sendMessage = async () => {
        const input = document.getElementById('messageInput');
        const text = input.value.trim();
        
        if(!text && !selectedChatFile) return;

        input.value = '';
        input.style.height = '';
        
        const fd = new FormData();
        fd.append('action', 'send_message');
        fd.append('ticket_id', currentTicketId);
        fd.append('message', text);

        const btnSend = document.getElementById('btnSendMessage');
        btnSend.disabled = true;

        const executeSend = async () => {
            try {
                const res = await fetch('<?php echo BASE_URL; ?>/ajax/soporte.php', { method: 'POST', body: fd }).then(r=>r.json());
                if(res.success) {
                    selectedChatFile = null;
                    document.getElementById('chatFileInput').value = '';
                    document.getElementById('chatFilePreview').style.display = 'none';
                    // SSE will auto-fetch the new message shortly
                } else {
                    window.showToast(res.message, 'error');
                }
            } catch(e) {
                window.showToast('Error de conexión', 'error');
            }
            btnSend.disabled = false;
        };

        if (selectedChatFile) {
            fd.append('attachment', selectedChatFile);
            if (selectedChatFile.type.startsWith('image/')) {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        (pos) => {
                            fd.append('latitude', pos.coords.latitude);
                            fd.append('longitude', pos.coords.longitude);
                            executeSend();
                        },
                        (err) => executeSend(),
                        { timeout: 5000 }
                    );
                    return;
                }
            }
        }
        
        executeSend();
    };

    window.deleteMessage = async (messageId) => {
        if (!confirm('¿Seguro que deseas eliminar este mensaje?')) return;
        
        const fd = new FormData();
        fd.append('action', 'delete_message');
        fd.append('message_id', messageId);
        
        try {
            const res = await fetch('<?php echo BASE_URL; ?>/ajax/soporte.php', { method: 'POST', body: fd }).then(r=>r.json());
            if (res.success) {
                const msgEl = document.getElementById(`msg-${messageId}`);
                if (msgEl) msgEl.remove();
                window.showToast('Mensaje eliminado', 'success');
            } else {
                window.showToast(res.message || 'Error al eliminar', 'error');
            }
        } catch(e) {
            window.showToast('Error de conexión', 'error');
        }
    };

    window.updateTicketStatus = async (status) => {
        if(!currentTicketId) return;
        const fd = new FormData();
        fd.append('action', 'update_status');
        fd.append('ticket_id', currentTicketId);
        fd.append('estado', status);
        try {
            const res = await fetch('<?php echo BASE_URL; ?>/ajax/soporte.php', { method: 'POST', body: fd }).then(r=>r.json());
            if(res.success) window.showToast('Estado actualizado', 'success');
        } catch(e) {}
    };

    document.addEventListener('DOMContentLoaded', () => {
        loadChatList();
        
        if (currentTicketId) {
            loadInitialMessages();
            chatPing();
            setInterval(chatPing, 8000); // Check collision every 8s

            const msgContainer = document.getElementById('chatMessages');
            msgContainer.addEventListener('scroll', () => {
                if (msgContainer.scrollTop === 0) {
                    loadOlderMessages();
                }
            });

            const input = document.getElementById('messageInput');
            input.addEventListener('keydown', (e) => {
                if(e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                } else {
                    clearTimeout(typingTimeout);
                    typingTimeout = setTimeout(notifyTyping, 500);
                }
            });

            document.getElementById('btnSendMessage').addEventListener('click', sendMessage);

            if(window.innerWidth <= 768) {
                document.getElementById('btnBackMobile').style.display = 'block';
            }
        }
    });

    window.addEventListener('beforeunload', () => {
        if (currentTicketId) {
            const fd = new FormData();
            fd.append('action', 'chat_leave');
            fd.append('ticket_id', currentTicketId);
            navigator.sendBeacon('<?php echo BASE_URL; ?>/ajax/soporte.php', fd);
        }
    });

    // --- Lógica del Modal de Ubicación y Tiempo Real ---
    let locationMap = null;
    let locationMarker = null;
    let selectedLat = 0;
    let selectedLng = 0;
    
    function openLocationModal() {
        if (!navigator.geolocation) {
            window.showToast('Geolocalización no soportada por el navegador', 'error');
            return;
        }
        
        document.getElementById('locationModal').style.display = 'flex';
        
        navigator.geolocation.getCurrentPosition((pos) => {
            selectedLat = pos.coords.latitude;
            selectedLng = pos.coords.longitude;
            initMap(selectedLat, selectedLng);
        }, (err) => {
            window.showToast('No se pudo obtener la ubicación actual', 'warning');
            selectedLat = -12.046374;
            selectedLng = -77.042793;
            initMap(selectedLat, selectedLng);
        });
    }

    function initMap(lat, lng) {
        const center = { lat: lat, lng: lng };
        if (!locationMap) {
            locationMap = new google.maps.Map(document.getElementById("mapContainer"), {
                zoom: 15,
                center: center,
                mapTypeControl: false,
                streetViewControl: false
            });
            locationMarker = new google.maps.Marker({
                position: center,
                map: locationMap,
                draggable: true,
                title: "Tu ubicación"
            });
            
            google.maps.event.addListener(locationMarker, 'dragend', function() {
                const pos = locationMarker.getPosition();
                selectedLat = pos.lat();
                selectedLng = pos.lng();
            });
            
            google.maps.event.addListener(locationMap, 'click', function(event) {
                locationMarker.setPosition(event.latLng);
                selectedLat = event.latLng.lat();
                selectedLng = event.latLng.lng();
            });
        } else {
            locationMap.setCenter(center);
            locationMarker.setPosition(center);
        }
    }

    function closeLocationModal() {
        document.getElementById('locationModal').style.display = 'none';
    }

    document.getElementById('btnSendLocationModal').addEventListener('click', () => {
        if (selectedLat && selectedLng) {
            sendLocationMessage(selectedLat, selectedLng, false);
            closeLocationModal();
        }
    });

    document.getElementById('btnLiveLocation').addEventListener('click', () => {
        if (selectedLat && selectedLng) {
            sendLocationMessage(selectedLat, selectedLng, true);
            closeLocationModal();
            startLiveLocationUpdates();
        }
    });

    const sendLocationMessage = async (lat, lng, isLive) => {
        const text = isLive ? `[LIVE_LOCATION:${lat},${lng}]` : `[LOCATION:${lat},${lng}]`;
        const fd = new FormData();
        fd.append('action', 'send_message');
        fd.append('ticket_id', currentTicketId);
        fd.append('message', text);
        try {
            await fetch('<?php echo BASE_URL; ?>/ajax/soporte.php', { method: 'POST', body: fd }).then(r=>r.json());
        } catch(e) {}
    };

    let liveLocationInterval = null;
    function startLiveLocationUpdates() {
        if (liveLocationInterval) return;
        window.showToast('Ubicación en tiempo real iniciada (1h)', 'success');
        
        const sendUpdate = () => {
            navigator.geolocation.getCurrentPosition((pos) => {
                const fd = new FormData();
                fd.append('action', 'update_live_location');
                fd.append('ticket_id', currentTicketId);
                fd.append('lat', pos.coords.latitude);
                fd.append('lng', pos.coords.longitude);
                fetch('<?php echo BASE_URL; ?>/ajax/soporte.php', { method: 'POST', body: fd });
            });
        };
        
        sendUpdate(); // Send initial update
        liveLocationInterval = setInterval(sendUpdate, 15000); // Update every 15 seconds
        
        // stop after 1 hour
        setTimeout(() => {
            if(liveLocationInterval) {
                clearInterval(liveLocationInterval);
                liveLocationInterval = null;
                window.showToast('Ubicación en tiempo real finalizada', 'info');
            }
        }, 60 * 60 * 1000);
    }
</script>

<!-- Modal de Ubicación -->
<div id="locationModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:var(--surface-color); padding:20px; border-radius:12px; width:90%; max-width:500px; box-shadow:var(--shadow);">
        <h4 style="margin-top:0; margin-bottom:15px; font-weight:bold;">Enviar Ubicación</h4>
        <div id="mapContainer" style="width:100%; height:300px; background:#e2e8f0; border-radius:8px; margin-bottom:15px;"></div>
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <div>
                <button class="btn btn-sm btn-outline-primary" id="btnLiveLocation">Compartir en Tiempo Real</button>
            </div>
            <div>
                <button class="btn btn-sm btn-secondary" onclick="closeLocationModal()">Cancelar</button>
                <button class="btn btn-sm btn-primary" id="btnSendLocationModal">Enviar</button>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
