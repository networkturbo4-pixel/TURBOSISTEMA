<!-- Modal de Captura de Cámara Webcam para PC / Laptop -->
<div class="modal-overlay" id="webcamCaptureModal" style="z-index: 10000; background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(8px); display: none;">
    <div class="modal-content" style="max-width: 500px; width: 92%; padding: 0; overflow: hidden; border-radius: 20px; border: 1px solid rgba(255,255,255,0.1); box-shadow: 0 25px 60px rgba(0,0,0,0.5); background: #0f172a; color: white; animation: scaleUpUber 0.25s cubic-bezier(0.16, 1, 0.3, 1);">
        
        <!-- Header -->
        <div style="padding: 16px 20px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.1); background: #0f172a;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 36px; height: 36px; background: rgba(16,185,129,0.15); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                    <i class="ph-fill ph-camera" style="font-size: 1.3rem; color: #10b981;"></i>
                </div>
                <div>
                    <div style="font-weight: 700; font-size: 1rem; color: #fff;">Cámara Webcam en Vivo</div>
                    <div style="font-size: 0.75rem; color: #94a3b8;">Captura tu foto desde la computadora</div>
                </div>
            </div>
            <button onclick="closeWebcamModal()" style="background: rgba(255,255,255,0.1); border: none; color: white; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.2)'" onmouseout="this.style.background='rgba(255,255,255,0.1)'">
                <i class="ph-bold ph-x" style="font-size: 1rem;"></i>
            </button>
        </div>

        <!-- Video Stream & Canvas Container -->
        <div style="position: relative; width: 100%; height: 320px; background: #000; display: flex; align-items: center; justify-content: center; overflow: hidden;">
            <video id="webcamVideo" autoplay playsinline style="width: 100%; height: 100%; object-fit: cover; transform: scaleX(-1);"></video>
            <canvas id="webcamCanvas" style="display: none;"></canvas>
            <div id="webcamLoadingText" style="position: absolute; color: #94a3b8; font-weight: 600; font-size: 0.9rem; display: flex; align-items: center; gap: 8px;">
                <i class="ph ph-circle-notch spinner" style="font-size: 1.2rem; color: #10b981;"></i> Iniciando cámara webcam...
            </div>
        </div>

        <!-- Footer Actions -->
        <div style="padding: 16px 20px; display: flex; gap: 12px; justify-content: space-between; align-items: center; background: #0f172a; border-top: 1px solid rgba(255,255,255,0.08);">
            <button type="button" onclick="closeWebcamModal()" style="padding: 10px 18px; background: #334155; color: white; border: none; border-radius: 10px; font-weight: 600; cursor: pointer;">
                Cancelar
            </button>
            <button type="button" onclick="takeWebcamSnapshot()" style="flex: 1; padding: 12px 20px; background: linear-gradient(135deg, #10b981, #059669); color: white; border: none; border-radius: 10px; font-weight: 700; font-size: 0.92rem; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 4px 14px rgba(16,185,129,0.35); transition: all 0.2s;">
                <i class="ph-fill ph-aperture" style="font-size: 1.2rem;"></i> Capturar Foto
            </button>
        </div>
    </div>
</div>

<script>
let webcamStream = null;

function triggerSmartCameraInput() {
    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    
    if (isMobile) {
        // En celulares y tablets, usar el disparador nativo de la cámara del SO
        if (typeof chatCameraInput !== 'undefined') {
            chatCameraInput.click();
        }
    } else {
        // En PC/Laptop, abrir la cámara webcam en vivo en el visor modal WebRTC
        openWebcamModal();
    }
}

async function openWebcamModal() {
    const modal = document.getElementById('webcamCaptureModal');
    const video = document.getElementById('webcamVideo');
    const loadingText = document.getElementById('webcamLoadingText');

    if (!modal || !video) return;
    
    modal.style.display = 'flex';
    modal.classList.add('active');
    if (loadingText) loadingText.style.display = 'flex';

    try {
        webcamStream = await navigator.mediaDevices.getUserMedia({ 
            video: { width: { ideal: 1280 }, height: { ideal: 720 }, facingMode: 'user' } 
        });
        video.srcObject = webcamStream;
        if (loadingText) loadingText.style.display = 'none';
    } catch (err) {
        console.warn('getUserMedia falló o fue denegado, recurriendo a la carga nativa:', err);
        closeWebcamModal();
        if (typeof chatCameraInput !== 'undefined') {
            chatCameraInput.click();
        }
    }
}

function closeWebcamModal() {
    const modal = document.getElementById('webcamCaptureModal');
    const video = document.getElementById('webcamVideo');
    
    if (webcamStream) {
        webcamStream.getTracks().forEach(track => track.stop());
        webcamStream = null;
    }
    if (video) video.srcObject = null;
    if (modal) {
        modal.classList.remove('active');
        modal.style.display = 'none';
    }
}

function takeWebcamSnapshot() {
    const video = document.getElementById('webcamVideo');
    const canvas = document.getElementById('webcamCanvas');
    if (!video || !canvas || !webcamStream) return;

    canvas.width = video.videoWidth || 640;
    canvas.height = video.videoHeight || 480;
    const ctx = canvas.getContext('2d');
    
    // Vista espejo horizontal
    ctx.translate(canvas.width, 0);
    ctx.scale(-1, 1);
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

    canvas.toBlob((blob) => {
        if (blob) {
            const capturedFile = new File([blob], `cam_${Date.now()}.jpg`, { type: 'image/jpeg' });
            if (typeof handleFileSelect === 'function') {
                handleFileSelect({ files: [capturedFile] });
            }
        }
        closeWebcamModal();
    }, 'image/jpeg', 0.92);
}

document.addEventListener('click', function(e) {
    const modal = document.getElementById('webcamCaptureModal');
    if (modal && e.target === modal) {
        closeWebcamModal();
    }
});
</script>
