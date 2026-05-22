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
        background: url('../../assets/img/chat-bg.png') repeat; /* Opcional: fondo estilo WhatsApp */
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
            <button class="btn-icon" title="Adjuntar archivo"><i class="ph ph-paperclip"></i></button>
            <div class="chat-input-wrapper">
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
    const currentUserId = <?php echo $_SESSION['user_id']; ?>;
    let lastMessageId = 0;
    let isPolling = false;

    // Función para formatear hora
    const formatTime = (dateStr) => {
        const d = new Date(dateStr);
        return d.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    };

    // Cargar lista de chats
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

    // Cargar mensajes del ticket actual
    const loadMessages = async () => {
        if(!currentTicketId || isPolling) return;
        isPolling = true;

        try {
            const fd = new FormData();
            fd.append('action', 'get_messages');
            fd.append('ticket_id', currentTicketId);
            fd.append('last_id', lastMessageId);

            const res = await fetch('<?php echo BASE_URL; ?>/ajax/soporte.php', { method: 'POST', body: fd }).then(r=>r.json());
            
            if(res.success && res.data.length > 0) {
                const container = document.getElementById('chatMessages');
                
                res.data.forEach(msg => {
                    const isMe = msg.user_id == currentUserId;
                    const bubbleClass = isMe ? 'message-sent' : 'message-received';
                    const userName = isMe ? 'Tú' : (msg.user_name || 'Cliente');
                    
                    container.innerHTML += `
                        <div class="message-bubble ${bubbleClass}">
                            ${!isMe ? `<div style="font-size:0.75rem; font-weight:700; color:var(--primary-color); margin-bottom:3px;">${userName}</div>` : ''}
                            <div>${msg.message.replace(/\n/g, '<br>')}</div>
                            <span class="message-time">${formatTime(msg.created_at)}</span>
                        </div>
                    `;
                    lastMessageId = msg.id;
                });
                
                // Auto-scroll to bottom
                container.scrollTop = container.scrollHeight;
            }
        } catch(e) {
            console.error(e);
        }
        isPolling = false;
    };

    // Enviar mensaje
    const sendMessage = async () => {
        const input = document.getElementById('messageInput');
        const text = input.value.trim();
        if(!text || !currentTicketId) return;

        input.value = '';
        input.style.height = '';

        const fd = new FormData();
        fd.append('action', 'send_message');
        fd.append('ticket_id', currentTicketId);
        fd.append('message', text);

        try {
            const res = await fetch('<?php echo BASE_URL; ?>/ajax/soporte.php', { method: 'POST', body: fd }).then(r=>r.json());
            if(res.success) {
                loadMessages(); // Refrescar rápido
            } else {
                window.showToast(res.message, 'error');
            }
        } catch(e) {}
    };

    // Cambiar estado del ticket
    window.updateTicketStatus = async (status) => {
        if(!currentTicketId) return;
        const fd = new FormData();
        fd.append('action', 'update_status');
        fd.append('ticket_id', currentTicketId);
        fd.append('estado', status);

        try {
            const res = await fetch('<?php echo BASE_URL; ?>/ajax/soporte.php', { method: 'POST', body: fd }).then(r=>r.json());
            if(res.success) {
                window.showToast('Estado actualizado', 'success');
            } else {
                window.showToast('Error al actualizar estado', 'error');
            }
        } catch(e) {}
    };

    document.addEventListener('DOMContentLoaded', () => {
        loadChatList();
        
        if (currentTicketId) {
            loadMessages();
            // Polling cada 5 segundos
            setInterval(loadMessages, 5000);

            // Evento enter para enviar
            document.getElementById('messageInput').addEventListener('keydown', (e) => {
                if(e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });

            document.getElementById('btnSendMessage').addEventListener('click', sendMessage);

            // Responsive back button
            if(window.innerWidth <= 768) {
                document.getElementById('btnBackMobile').style.display = 'block';
            }
        }
    });

</script>

<?php include '../../includes/footer.php'; ?>
