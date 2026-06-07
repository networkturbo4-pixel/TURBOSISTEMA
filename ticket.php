<?php
require_once 'config/db.php';

$ticket_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$token = $_GET['token'] ?? '';

// Verificar ticket y token
$stmt = $pdo->prepare("
    SELECT t.*, c.nombre_completo as cliente_nombre 
    FROM tickets t
    LEFT JOIN clientes c ON t.cliente_id = c.id
    WHERE t.id = ? AND t.public_token = ?
");
$stmt->execute([$ticket_id, $token]);
$ticket = $stmt->fetch();

if (!$ticket) {
    die("Ticket no encontrado o enlace inválido.");
}

$has_session = isset($_SESSION['user_id']);
$primaryColor = '#064e3b'; // Default
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
    <title>Chat de Soporte - Ticket #<?php echo $ticket_id; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script>
        // CSRF Fetch Interceptor
        const originalFetch = window.fetch;
        window.fetch = async function() {
            let [resource, config] = arguments;
            if (!config) config = {};
            if (config.method && config.method.toUpperCase() === 'POST') {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                if (csrfToken) {
                    if (config.body instanceof FormData) {
                        if(!config.body.has('csrf_token')) config.body.append('csrf_token', csrfToken);
                    } else if (typeof config.body === 'string') {
                        if (config.headers && config.headers['Content-Type'] === 'application/json') {
                            try {
                                let json = JSON.parse(config.body);
                                json.csrf_token = csrfToken;
                                config.body = JSON.stringify(json);
                            } catch(e) {}
                        } else if (config.headers && config.headers['Content-Type'] === 'application/x-www-form-urlencoded') {
                            config.body += config.body ? '&csrf_token=' + encodeURIComponent(csrfToken) : 'csrf_token=' + encodeURIComponent(csrfToken);
                        }
                    } else if (!config.body) {
                        config.body = new FormData();
                        config.body.append('csrf_token', csrfToken);
                    }
                }
            }
            return originalFetch(resource, config);
        };
    </script>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #efeae2;
            font-family: 'Inter', sans-serif;
            height: 100vh;
            height: 100dvh;
            display: flex;
            flex-direction: column;
        }
        .public-banner {
            background-color: #fef08a;
            color: #854d0e;
            padding: 10px 20px;
            text-align: center;
            font-size: 0.9rem;
            font-weight: 600;
            border-bottom: 1px solid #fde047;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .chat-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            max-width: 800px;
            margin: 0 auto;
            width: 100%;
            background: url('<?php echo BASE_URL; ?>/assets/img/chat-bg.png') repeat, #efeae2;
            box-shadow: 0 0 15px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        .chat-header {
            padding: 15px 20px;
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
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
        
        /* En la vista pública, los mensajes con user_id NULO son del cliente (Enviado),
           y los que tienen user_id son del técnico (Recibido). */
        .message-sent {
            align-self: flex-end;
            background: #d9fdd3;
            color: #111b21;
            border-top-right-radius: 0;
        }
        .message-received {
            align-self: flex-start;
            background: #ffffff;
            color: #111b21;
            border-top-left-radius: 0;
        }
        .message-time {
            font-size: 0.65rem;
            color: rgba(0,0,0,0.45);
            text-align: right;
            margin-top: 5px;
            display: block;
        }
        
        .chat-input-area {
            padding: 15px;
            background: #f0f2f5;
            display: flex;
            align-items: flex-end;
            gap: 10px;
        }
        .chat-input-wrapper {
            flex: 1;
            background: #ffffff;
            border-radius: 20px;
            padding: 0 15px;
        }
        .chat-input-wrapper textarea {
            width: 100%;
            border: none;
            background: transparent;
            padding: 12px 0;
            max-height: 120px;
            outline: none;
            resize: none;
            color: #333;
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
        
        @keyframes pulse-red {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.5); opacity: 0.5; }
            100% { transform: scale(1); opacity: 1; }
        }
    </style>
</head>
<body>

<?php if (isset($_SESSION['user_id']) && strtolower($_SESSION['user_role'] ?? '') !== 'cliente'): ?>
<div style="background-color: #dbeafe; color: #1e40af; padding: 10px; text-align: center; font-size: 0.9rem;">
    Estás viendo la vista pública del ticket. <a href="<?php echo BASE_URL; ?>/modules/soporte/index.php" style="color:#1e3a8a; font-weight:bold;">Volver al Panel Administrativo</a>
</div>
<?php elseif (isset($_SESSION['public_cliente_id'])): ?>
<div style="background-color: #e0f2fe; color: #0369a1; padding: 10px; text-align: center; font-size: 0.9rem; display: flex; align-items: center; justify-content: center; gap: 10px;">
    <i class="ph-fill ph-info"></i> Estás en la sala de chat de tu ticket. <a href="portal.php" style="color:#0284c7; font-weight:bold;">Volver al Portal del Cliente</a>
</div>
<?php else: ?>
<div class="public-banner">
    <i class="ph-fill ph-warning-circle" style="font-size: 1.2rem;"></i>
    Atención: Recomendamos iniciar sesión en el <a href="soporte.php" style="color:#854d0e; text-decoration:underline; font-weight:bold;">Portal del Cliente</a> para no perder el historial de su ticket.
</div>
<?php endif; ?>

<div class="chat-container">
    <div class="chat-header">
        <div style="display: flex; align-items: center; gap: 15px;">
            <?php if (isset($_SESSION['public_cliente_id'])): ?>
                <a href="portal.php" style="color: #64748b; font-size: 1.5rem; text-decoration: none;"><i class="ph ph-arrow-left"></i></a>
            <?php else: ?>
                <a href="<?php echo BASE_URL; ?>/modules/soporte/index.php" style="color: #64748b; font-size: 1.5rem; text-decoration: none;"><i class="ph ph-arrow-left"></i></a>
            <?php endif; ?>
            <div>
                <div style="font-weight: bold; font-size: 1.1rem;">Soporte Técnico</div>
                <div style="font-size: 0.8rem; color: #64748b;">Ticket #<?php echo str_pad($ticket_id, 4, '0', STR_PAD_LEFT); ?> | <?php echo htmlspecialchars($ticket['asunto']); ?></div>
            </div>
        </div>
        <div style="background: #e2e8f0; padding: 5px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; text-transform: uppercase;">
            <?php echo $ticket['estado']; ?>
        </div>
    </div>

    <div class="chat-messages" id="chatMessages">
        <!-- Messages loaded via AJAX -->
    </div>

    <?php if ($ticket['estado'] === 'terminado'): ?>
        <div style="text-align:center; padding:15px; color:#ef4444; font-weight:bold; background:#fee2e2; border-top:1px solid #fca5a5; margin-top: auto;">
            El ticket ha sido marcado como TERMINADO. Ya no puedes enviar más mensajes.
        </div>
    <?php else: ?>
    <div class="chat-input-area" style="position: relative;">
        <!-- Menú de Acciones Flotante -->
        <div id="chatActionMenu" style="display: none; position: absolute; bottom: 100%; left: 15px; margin-bottom: 10px; background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border: 1px solid #e2e8f0; padding: 10px; z-index: 100;">
            <button onclick="chatFileInput.click(); toggleActionMenu();" style="display: flex; align-items: center; gap: 10px; width: 100%; padding: 8px 12px; background: transparent; border: none; text-align: left; cursor: pointer; border-radius: 8px; font-size: 0.9rem;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='transparent'">
                <i class="ph-fill ph-camera" style="font-size: 1.2rem; color: #3b82f6;"></i> Enviar Foto
            </button>
            <button onclick="sendLocation(); toggleActionMenu();" style="display: flex; align-items: center; gap: 10px; width: 100%; padding: 8px 12px; background: transparent; border: none; text-align: left; cursor: pointer; border-radius: 8px; font-size: 0.9rem;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='transparent'">
                <i class="ph-fill ph-map-pin" style="font-size: 1.2rem; color: #ef4444;"></i> Enviar Ubicación
            </button>
        </div>

        <button onclick="toggleActionMenu()" style="background: transparent; border: none; font-size: 1.5rem; color: #94a3b8; cursor: pointer; padding: 0 10px;">
            <i class="ph ph-plus-circle"></i>
        </button>
        
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
        
        <!-- Chat Lock Overlay -->
        <div id="chatLockOverlay" style="display: none; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.9); z-index: 200; align-items: center; justify-content: center; border-radius: 12px; backdrop-filter: blur(2px);">
            <div style="text-align: center; color: #64748b; font-weight: 600; padding: 0 20px;">
                <i class="ph-fill ph-lock-key" style="font-size: 2rem; color: #ef4444; margin-bottom: 5px;"></i><br>
                Chat siendo atendido por <span id="lockedByTechName" style="color:#0f172a;"></span>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Lightbox Modal -->
<div id="imageLightbox" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:9999; align-items:center; justify-content:center; flex-direction:column; backdrop-filter: blur(5px);">
    <button onclick="document.getElementById('imageLightbox').style.display='none'" style="position:absolute; top:20px; right:20px; background:rgba(255,255,255,0.1); border:none; color:white; font-size:1.5rem; cursor:pointer; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: background 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.2)'" onmouseout="this.style.background='rgba(255,255,255,0.1)'"><i class="ph-bold ph-x"></i></button>
    <img id="lightboxImg" style="max-width:90%; max-height:90%; border-radius:12px; box-shadow: 0 10px 40px rgba(0,0,0,0.5); object-fit: contain;">
</div>

<script>
    const currentTicketId = <?php echo $ticket_id; ?>;
    const token = '<?php echo $token; ?>';
    let lastMessageId = 0;
    let isPollingMessages = false;

    const formatTime = (dateStr) => {
        const d = new Date(dateStr);
        return d.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    };

    const loadMessages = async () => {
        if(isPollingMessages) return;
        isPollingMessages = true;
        try {
            const fd = new FormData();
            fd.append('action', 'get_messages');
            fd.append('ticket_id', currentTicketId);
            fd.append('token', token);
            fd.append('last_id', lastMessageId);

            const res = await fetch('<?php echo BASE_URL; ?>/ajax/soporte.php', { method: 'POST', body: fd, cache: 'no-store' }).then(r=>r.json());
            if(res.success && res.data.length > 0) {
                const container = document.getElementById('chatMessages');
                res.data.forEach(msg => {
                    if (msg.is_system_message == 1) {
                        container.innerHTML += `<div class="sys-message">${msg.message}</div>`;
                    } else {
                        const isMe = msg.user_id === null;
                        const bubbleClass = isMe ? 'message-sent' : 'message-received';
                        const userName = isMe ? 'Tú' : (msg.user_name || 'Soporte Técnico');
                        
                        let msgContent = msg.message.replace(/\n/g, '<br>');
                        
                        // Parse Location
                        if (msgContent.startsWith('[LOCATION:') && msgContent.endsWith(']')) {
                            const coords = msgContent.replace('[LOCATION:', '').replace(']', '');
                            msgContent = `
                                <a href="https://maps.google.com/?q=${coords}" target="_blank" class="loc-card">
                                    <div style="background: #ef4444; color: white; padding: 10px; border-radius: 8px;"><i class="ph-fill ph-map-pin" style="font-size: 1.5rem;"></i></div>
                                    <div>
                                        <div style="font-weight: 600; font-size: 0.9rem;">Ubicación compartida</div>
                                        <div style="font-size: 0.75rem; color: #64748b;">Toca para abrir el mapa</div>
                                    </div>
                                </a>
                            `;
                        }

                        // Attachments
                        let attHtml = '';
                        if (msg.attachments && msg.attachments.length > 0) {
                            msg.attachments.forEach(att => {
                                const url = `${'<?php echo BASE_URL; ?>/'}${att.file_path}`;
                                const ext = att.file_name.split('.').pop().toLowerCase();
                                if (['webm', 'mp3', 'ogg', 'wav', 'm4a'].includes(ext)) {
                                    attHtml += `<audio controls src="${url}" style="max-width: 100%; margin-top: 5px; outline: none; height: 35px;"></audio>`;
                                } else {
                                    attHtml += `<img src="${url}" onclick="openLightbox('${url}')" style="cursor: pointer; max-width: 100%; border-radius: 8px; margin-top: 5px; border: 1px solid rgba(0,0,0,0.1); transition: opacity 0.2s;" onmouseover="this.style.opacity=0.9" onmouseout="this.style.opacity=1">`;
                                }
                            });
                        }
                        
                        container.innerHTML += `
                            <div class="message-bubble ${bubbleClass}">
                                ${!isMe ? `<div style="font-size:0.75rem; font-weight:700; color:#064e3b; margin-bottom:3px;">${userName}</div>` : ''}
                                <div>${msgContent}</div>
                                ${attHtml}
                                <span class="message-time">${formatTime(msg.created_at)}</span>
                            </div>
                        `;
                    }
                    lastMessageId = msg.id;
                });
                container.scrollTop = container.scrollHeight;
            }
        } catch(e) {}
        isPollingMessages = false;
    };

    // Lightbox handling
    const openLightbox = (src) => {
        document.getElementById('lightboxImg').src = src;
        document.getElementById('imageLightbox').style.display = 'flex';
    };

    let selectedFile = null;

    // Create file input dynamically to avoid global UI plugins (like Dropify) auto-initializing it
    const chatFileInput = document.createElement('input');
    chatFileInput.type = 'file';
    chatFileInput.accept = 'image/*';
    chatFileInput.capture = 'environment';
    chatFileInput.onchange = (e) => handleFileSelect(e.target);

    const toggleActionMenu = () => {
        const menu = document.getElementById('chatActionMenu');
        menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
    };

    const clearFileSelection = () => {
        selectedFile = null;
        chatFileInput.value = '';
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
            alert('Tu navegador no soporta geolocalización');
            return;
        }
        navigator.geolocation.getCurrentPosition(
            async (position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                const fd = new FormData();
                fd.append('action', 'send_message');
                fd.append('ticket_id', currentTicketId);
                fd.append('token', token);
                fd.append('message', `[LOCATION:${lat},${lng}]`);
                try {
                    const res = await fetch('<?php echo BASE_URL; ?>/ajax/soporte.php', { method: 'POST', body: fd }).then(r=>r.json());
                    if(res.success) loadMessages();
                } catch(e) {}
            },
            (error) => {
                alert('No se pudo obtener la ubicación. Verifica los permisos.');
            }
        );
    };

    // Voice Notes Logic
    let isRecording = false;
    let mediaRecorder = null;
    let audioChunks = [];
    let recordingTimerInterval = null;
    let recordingSeconds = 0;

    const updateMainButton = () => {
        const text = document.getElementById('messageInput').value.trim();
        const btnIcon = document.getElementById('btnSendMessageIcon');
        if (text || selectedFile) {
            btnIcon.className = 'ph-fill ph-paper-plane-right';
        } else {
            btnIcon.className = 'ph-fill ph-microphone';
        }
    };

    const handleMainAction = () => {
        const text = document.getElementById('messageInput').value.trim();
        if (text || selectedFile) {
            sendMessage();
        } else {
            if (isRecording) {
                stopRecordingAndSend();
            } else {
                startRecording();
            }
        }
    };

    const startRecording = async () => {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            mediaRecorder = new MediaRecorder(stream);
            audioChunks = [];
            
            mediaRecorder.addEventListener("dataavailable", event => {
                audioChunks.push(event.data);
            });
            
            mediaRecorder.addEventListener("stop", () => {
                if (isRecording) {
                    const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
                    sendAudioMessage(audioBlob);
                }
                isRecording = false;
                stream.getTracks().forEach(track => track.stop());
                
                document.getElementById('audioRecordingUi').style.display = 'none';
                document.querySelector('.chat-input-wrapper').style.display = 'flex';
                updateMainButton();
                clearInterval(recordingTimerInterval);
            });
            
            isRecording = true;
            mediaRecorder.start();
            
            document.querySelector('.chat-input-wrapper').style.display = 'none';
            document.getElementById('audioRecordingUi').style.display = 'flex';
            document.getElementById('btnSendMessageIcon').className = 'ph-fill ph-paper-plane-right';
            
            recordingSeconds = 0;
            document.getElementById('recordingTimer').innerText = '00:00';
            recordingTimerInterval = setInterval(() => {
                recordingSeconds++;
                const m = String(Math.floor(recordingSeconds / 60)).padStart(2, '0');
                const s = String(recordingSeconds % 60).padStart(2, '0');
                document.getElementById('recordingTimer').innerText = `${m}:${s}`;
            }, 1000);
            
        } catch (e) {
            alert('No se pudo acceder al micrófono. Verifica los permisos.');
        }
    };

    const cancelRecording = () => {
        isRecording = false;
        if (mediaRecorder && mediaRecorder.state !== 'inactive') {
            mediaRecorder.stop();
        }
    };

    const stopRecordingAndSend = () => {
        if (mediaRecorder && mediaRecorder.state !== 'inactive') {
            mediaRecorder.stop();
        }
    };

    const sendAudioMessage = async (audioBlob) => {
        const btnSend = document.getElementById('btnSendMessage');
        btnSend.disabled = true;

        const fd = new FormData();
        fd.append('action', 'send_message');
        fd.append('ticket_id', currentTicketId);
        fd.append('token', token);
        fd.append('message', '');
        fd.append('attachment', audioBlob, 'audio_record.webm');

        try {
            const res = await fetch('<?php echo BASE_URL; ?>/ajax/soporte.php', { method: 'POST', body: fd }).then(r=>r.json());
            if(res.success) {
                loadMessages();
            }
        } catch(e) {}
        
        btnSend.disabled = false;
    };

    const sendMessage = async () => {
        const input = document.getElementById('messageInput');
        const text = input.value.trim();
        if((!text && !selectedFile)) return;

        input.value = '';
        input.style.height = '';
        const btnSend = document.getElementById('btnSendMessage');
        btnSend.disabled = true;

        const fd = new FormData();
        fd.append('action', 'send_message');
        fd.append('ticket_id', currentTicketId);
        fd.append('token', token);
        fd.append('message', text);
        if (selectedFile) {
            fd.append('attachment', selectedFile);
            clearFileSelection();
        }

        try {
            const res = await fetch('<?php echo BASE_URL; ?>/ajax/soporte.php', { method: 'POST', body: fd }).then(r=>r.json());
            if(res.success) {
                loadMessages();
                updateMainButton();
            } else {
                alert(res.message || 'Error al enviar');
            }
        } catch(e) {
            alert('Error de conexión');
        }
        
        btnSend.disabled = false;
    };

    document.addEventListener('DOMContentLoaded', () => {
        loadMessages();
        setInterval(loadMessages, 3000);
        
        <?php if ($has_session): ?>
        // Ping to keep the chat locked for this technician
        const chatPing = async () => {
            const fd = new FormData();
            fd.append('action', 'chat_ping');
            fd.append('ticket_id', currentTicketId);
            fd.append('token', token);
            
            try {
                const res = await fetch('<?php echo BASE_URL; ?>/ajax/soporte.php', { method: 'POST', body: fd }).then(r=>r.json());
                const overlay = document.getElementById('chatLockOverlay');
                if (overlay) {
                    if (res.locked) {
                        document.getElementById('lockedByTechName').innerText = res.locked_by;
                        overlay.style.display = 'flex';
                    } else {
                        overlay.style.display = 'none';
                    }
                }
            } catch(e) {}
        };
        
        chatPing(); // Initial ping
        setInterval(chatPing, 5000); // Ping every 5 seconds

        window.addEventListener('beforeunload', () => {
            const fd = new FormData();
            fd.append('action', 'chat_leave');
            fd.append('ticket_id', currentTicketId);
            navigator.sendBeacon('<?php echo BASE_URL; ?>/ajax/soporte.php', fd);
        });
        <?php endif; ?>

        document.getElementById('messageInput').addEventListener('keydown', (e) => {
            if(e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
    });
</script>

</body>
</html>
