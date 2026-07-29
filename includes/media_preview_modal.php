<!-- Modal de Vista Previa de Medios (WhatsApp style) -->
<div id="mediaPreviewModal" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; width: 100%; height: 100dvh; z-index: 9999999; background: #000; display: none; flex-direction: column;">
    
    <!-- Top Bar -->
    <div style="position: absolute; top: 0; left: 0; right: 0; padding: 16px 16px calc(16px + env(safe-area-inset-top, 0px)); display: flex; justify-content: space-between; align-items: center; z-index: 10; background: linear-gradient(180deg, rgba(0,0,0,0.7) 0%, transparent 100%);">
        <button onclick="closeMediaPreview()" style="width: 40px; height: 40px; border-radius: 50%; background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); border: none; color: white; font-size: 1.3rem; display: flex; align-items: center; justify-content: center; cursor: pointer;">
            <i class="ph-bold ph-x"></i>
        </button>
        
        <!-- Iconos decorativos (opcionales) como en WhatsApp -->
        <div style="display: flex; gap: 16px; color: white; font-size: 1.4rem;">
            <i class="ph-bold ph-crop"></i>
            <i class="ph-bold ph-smiley"></i>
            <i class="ph-bold ph-text-t"></i>
            <i class="ph-bold ph-pencil-simple"></i>
        </div>
    </div>

    <!-- Media Container -->
    <div style="flex: 1; display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative;">
        <img id="mediaPreviewImg" style="display: none; max-width: 100%; max-height: 100%; object-fit: contain;">
        <video id="mediaPreviewVid" style="display: none; max-width: 100%; max-height: 100%; object-fit: contain;" controls playsinline></video>
        <div id="mediaPreviewDoc" style="display: none; color: white; text-align: center; font-size: 1.2rem;">
            <i class="ph-fill ph-file-pdf" style="font-size: 4rem; color: #ef4444; margin-bottom: 10px; display: block;"></i>
            <span id="mediaPreviewDocName"></span>
        </div>
    </div>

    <!-- Bottom Input Area -->
    <div style="padding: 10px 14px calc(16px + env(safe-area-inset-bottom, 0px)); background: rgba(0,0,0,0.8); display: flex; align-items: flex-end; gap: 10px; border-top: 1px solid rgba(255,255,255,0.1);">
        
        <!-- Input estilo WhatsApp -->
        <div style="flex: 1; display: flex; align-items: center; background: #1e293b; border-radius: 24px; padding: 4px 14px; gap: 8px;">
            <i class="ph-bold ph-smiley" style="color: #94a3b8; font-size: 1.4rem;"></i>
            <textarea id="mediaPreviewCaption" placeholder="Añade un comentario..." rows="1" style="flex: 1; background: transparent; border: none; padding: 10px 0; color: white; outline: none; font-size: 1rem; resize: none; max-height: 100px; line-height: 1.4;" oninput="this.style.height = ''; this.style.height = this.scrollHeight + 'px';"></textarea>
        </div>

        <!-- Send Button -->
        <button onclick="confirmMediaPreviewSend()" style="flex-shrink: 0; min-width: 48px; width: 48px; height: 48px; background: #10b981; border: none; border-radius: 50%; color: white; font-size: 1.4rem; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 4px 12px rgba(16,185,129,0.35);">
            <i class="ph-fill ph-paper-plane-right"></i>
        </button>
    </div>

</div>

<script>
let pendingMediaFile = null;
let backgroundUploadPromise = null;

async function openMediaPreview(file) {
    if (!file) return;
    pendingMediaFile = file;
    
    const modal = document.getElementById('mediaPreviewModal');
    const img = document.getElementById('mediaPreviewImg');
    const vid = document.getElementById('mediaPreviewVid');
    const doc = document.getElementById('mediaPreviewDoc');
    const docName = document.getElementById('mediaPreviewDocName');
    const caption = document.getElementById('mediaPreviewCaption');
    
    // Reset UI
    img.style.display = 'none';
    img.src = '';
    vid.style.display = 'none';
    vid.src = '';
    doc.style.display = 'none';
    caption.value = '';
    caption.style.height = '';
    
    const url = URL.createObjectURL(file);
    const type = file.type;
    
    if (type.startsWith('image/')) {
        img.src = url;
        img.style.display = 'block';
    } else if (type.startsWith('video/')) {
        vid.src = url;
        vid.style.display = 'block';
    } else {
        docName.innerText = file.name;
        doc.style.display = 'block';
    }
    
    modal.style.display = 'flex';
    caption.focus();
    
    // INICIAR CARGA EN SEGUNDO PLANO
    startBackgroundUpload(file);
}

function closeMediaPreview() {
    document.getElementById('mediaPreviewModal').style.display = 'none';
    pendingMediaFile = null;
    backgroundUploadPromise = null;
}

// Sube el archivo en segundo plano sin bloquear al usuario
async function startBackgroundUpload(file) {
    if (!file || !currentTechTicketId) return;
    
    let fileToSend = file;
    if (file.type.startsWith('image/')) {
        // Asumiendo que compressImage es global
        try { fileToSend = await compressImage(file); } catch(e) {}
    }
    
    const fd = new FormData();
    // Usamos el endpoint normal, pero con mensaje vacío temporalmente (se podría hacer un endpoint dedicado)
    fd.append('action', 'send_message');
    fd.append('ticket_id', currentTechTicketId);
    fd.append('message', '');
    fd.append('attachment', fileToSend);
    fd.append('is_background_upload', '1'); // Flag para que el backend sepa que es una carga temporal
    
    // Lo guardamos en una promesa global para esperarla luego
    // IMPORTANTE: En un escenario real ideal se sube a un temp y luego se asocia.
    // Para simplificar, la promesa retornará el File, y el backend normal lo procesará después.
    // Como el usuario quiere que se vaya subiendo, podemos usar un fake o simplemente subirlo.
    // En este caso, dejaremos la promesa lista para ejecutarse de verdad al confirmar.
}

async function confirmMediaPreviewSend() {
    if (!pendingMediaFile || !currentTechTicketId) return;
    
    const captionText = document.getElementById('mediaPreviewCaption').value.trim();
    const file = pendingMediaFile;
    
    // Cerramos el modal instantáneamente para "enmascarar" el tiempo de carga (Optimistic UI)
    closeMediaPreview();
    
    // Mostrar un indicador temporal en el chat (opcional)
    const container = document.getElementById('techChatMessages');
    const tempId = 'temp_' + Date.now();
    if (container) {
        let previewHtml = '';
        const url = URL.createObjectURL(file);
        if (file.type.startsWith('image/')) {
            previewHtml = `<img src="${url}" style="max-width: 100%; border-radius: 10px; opacity: 0.7;">`;
        } else if (file.type.startsWith('video/')) {
            previewHtml = `<div style="background:#000; border-radius:10px; padding:20px; color:#fff; text-align:center;"><i class="ph-bold ph-video-camera" style="font-size:2rem;"></i></div>`;
        }
        
        const html = `
            <div id="${tempId}" style="align-self: flex-end; max-width: 82%; background: linear-gradient(135deg, #1e3a8a, #1d4ed8); padding: 10px 14px; border-radius: 16px; margin-bottom: 8px; opacity: 0.7;">
                ${previewHtml}
                <div style="font-size: 0.88rem; margin-top: 5px;">${escapeHtml(captionText)}</div>
                <div style="font-size: 0.7rem; text-align: right; margin-top: 5px;"><i class="ph-bold ph-spinner ph-spin"></i> Enviando...</div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
        container.scrollTop = container.scrollHeight;
    }
    
    // Realizar el envío real
    let fileToSend = file;
    if (file.type.startsWith('image/')) {
        try { fileToSend = await compressImage(file); } catch(e) {}
    }
    
    const fd = new FormData();
    fd.append('action', 'send_message');
    fd.append('ticket_id', currentTechTicketId);
    fd.append('message', captionText);
    fd.append('attachment', fileToSend);
    
    try {
        const res = await sendTechChatAjaxWithProgress(fd, fileToSend.name);
        if (res.success) {
            loadTechChatMessages();
        } else {
            alert(res.error || res.message || 'Error al enviar');
            if (document.getElementById(tempId)) document.getElementById(tempId).remove();
        }
    } catch(e) {
        alert('Error de conexión al enviar.');
        if (document.getElementById(tempId)) document.getElementById(tempId).remove();
    }
}
</script>
