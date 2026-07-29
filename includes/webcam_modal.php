<!-- Modal Fullscreen de Cámara (PC Webcam + Móvil Nativa) -->
<div id="webcamCaptureModal" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; width: 100%; height: 100dvh; z-index: 999999; background: #000; display: none; flex-direction: column;">

    <!-- Video Stream -->
    <video id="webcamVideo" autoplay playsinline muted style="flex: 1; width: 100%; height: 100%; object-fit: cover;"></video>
    <canvas id="webcamCanvas" style="display: none;"></canvas>

    <!-- Preview de foto capturada -->
    <img id="webcamPreview" style="display: none; flex: 1; width: 100%; height: 100%; object-fit: contain; background: #000;">
    
    <!-- Preview de video capturado -->
    <video id="webcamVideoPreview" playsinline controls style="display: none; flex: 1; width: 100%; height: 100%; object-fit: contain; background: #000;"></video>

    <!-- Top Bar -->
    <div style="position: absolute; top: 0; left: 0; right: 0; padding: 16px 16px calc(16px + env(safe-area-inset-top, 0px)); display: flex; justify-content: space-between; align-items: center; z-index: 10; background: linear-gradient(180deg, rgba(0,0,0,0.6) 0%, transparent 100%);">
        <button onclick="closeWebcamModal()" style="width: 40px; height: 40px; border-radius: 50%; background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); border: none; color: white; font-size: 1.3rem; display: flex; align-items: center; justify-content: center; cursor: pointer;">
            <i class="ph-bold ph-x"></i>
        </button>
        <button id="btnCamFlash" onclick="toggleCamFlash()" style="width: 40px; height: 40px; border-radius: 50%; background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); border: none; color: white; font-size: 1.3rem; display: flex; align-items: center; justify-content: center; cursor: pointer;">
            <i class="ph-bold ph-lightning-slash"></i>
        </button>
    </div>

    <!-- Bottom Controls -->
    <div id="webcamBottomControls" style="position: absolute; bottom: 0; left: 0; right: 0; padding: 20px 30px calc(30px + env(safe-area-inset-bottom, 0px)); display: flex; flex-direction: column; align-items: center; gap: 18px; z-index: 10; background: linear-gradient(0deg, rgba(0,0,0,0.7) 0%, transparent 100%);">
        
        <!-- Shutter + Flip Row -->
        <div style="display: flex; align-items: center; justify-content: center; gap: 40px; width: 100%;">
            
            <!-- Spacer izq -->
            <div style="width: 48px;"></div>

            <!-- Shutter Button -->
            <button id="btnShutter" onclick="takeWebcamSnapshot()" style="width: 72px; height: 72px; border-radius: 50%; border: 4px solid white; background: transparent; cursor: pointer; position: relative; display: flex; align-items: center; justify-content: center; transition: all 0.15s;">
                <div id="shutterInner" style="width: 58px; height: 58px; border-radius: 50%; background: white; transition: all 0.15s;"></div>
            </button>

            <!-- Flip Camera -->
            <button onclick="flipCamera()" style="width: 48px; height: 48px; border-radius: 50%; background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); border: none; color: white; font-size: 1.4rem; display: flex; align-items: center; justify-content: center; cursor: pointer;">
                <i class="ph-bold ph-camera-rotate"></i>
            </button>
        </div>

        <!-- Mode Tabs (Foto / Video) -->
        <div style="display: flex; gap: 4px; background: rgba(255,255,255,0.1); border-radius: 20px; padding: 3px;">
            <button id="btnModeVideo" onclick="setCameraMode('video')" style="padding: 6px 18px; border-radius: 18px; border: none; font-size: 0.82rem; font-weight: 700; cursor: pointer; transition: all 0.2s; background: transparent; color: rgba(255,255,255,0.6);">
                Video
            </button>
            <button id="btnModeFoto" onclick="setCameraMode('foto')" style="padding: 6px 18px; border-radius: 18px; border: none; font-size: 0.82rem; font-weight: 700; cursor: pointer; transition: all 0.2s; background: white; color: #000;">
                Foto
            </button>
        </div>
    </div>

    <!-- Preview Controls (after capture) -->
    <div id="webcamPreviewControls" style="display: none; position: absolute; bottom: 0; left: 0; right: 0; padding: 20px 30px calc(30px + env(safe-area-inset-bottom, 0px)); justify-content: center; gap: 20px; z-index: 10; background: linear-gradient(0deg, rgba(0,0,0,0.7) 0%, transparent 100%);">
        <button onclick="discardCapture()" style="width: 56px; height: 56px; border-radius: 50%; background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); border: none; color: white; font-size: 1.5rem; display: flex; align-items: center; justify-content: center; cursor: pointer;">
            <i class="ph-bold ph-x"></i>
        </button>
        <button onclick="confirmCapture()" style="width: 56px; height: 56px; border-radius: 50%; background: #10b981; border: none; color: white; font-size: 1.5rem; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 4px 14px rgba(16,185,129,0.4);">
            <i class="ph-bold ph-check"></i>
        </button>
    </div>

    <!-- Recording indicator -->
    <div id="recordingIndicator" style="display: none; position: absolute; top: 80px; left: 50%; transform: translateX(-50%); background: rgba(239,68,68,0.85); backdrop-filter: blur(10px); padding: 6px 16px; border-radius: 20px; color: white; font-weight: 700; font-size: 0.85rem; z-index: 10; align-items: center; gap: 8px;">
        <div style="width: 10px; height: 10px; border-radius: 50%; background: #fff; animation: recPulse 1s ease-in-out infinite;"></div>
        <span id="recordingTime">00:00</span>
    </div>

</div>

<style>
@keyframes recPulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}
</style>

<script>
let webcamStream = null;
let currentFacingMode = 'environment';
let cameraMode = 'foto';
let mediaRecorder = null;
let recordedChunks = [];
let recordingTimer = null;
let recordingSeconds = 0;
let capturedBlob = null;

function triggerSmartCameraInput() {
    openWebcamModal();
}

async function openWebcamModal() {
    const modal = document.getElementById('webcamCaptureModal');
    const video = document.getElementById('webcamVideo');
    if (!modal || !video) return;
    
    modal.style.display = 'flex';
    document.getElementById('webcamBottomControls').style.display = 'flex';
    document.getElementById('webcamPreviewControls').style.display = 'none';
    document.getElementById('webcamPreview').style.display = 'none';
    document.getElementById('webcamVideoPreview').style.display = 'none';
    video.style.display = 'block';
    video.muted = true;
    video.controls = false;
    
    setCameraMode('foto');
    
    try {
        // Solo video, SIN audio para evitar que se cuele el sonido en modo foto
        webcamStream = await navigator.mediaDevices.getUserMedia({ 
            video: { 
                width: { ideal: 1280 }, 
                height: { ideal: 720 }, 
                facingMode: currentFacingMode,
                frameRate: { ideal: 30 }
            },
            audio: false 
        });
        video.srcObject = webcamStream;
    } catch (err) {
        console.warn('Camera access denied:', err);
        closeWebcamModal();
        const camInput = document.getElementById('chatCameraInput');
        if (camInput) camInput.click();
    }
}

function closeWebcamModal() {
    const modal = document.getElementById('webcamCaptureModal');
    const video = document.getElementById('webcamVideo');
    const videoPreview = document.getElementById('webcamVideoPreview');
    
    stopRecording();
    
    if (webcamStream) {
        webcamStream.getTracks().forEach(track => track.stop());
        webcamStream = null;
    }
    if (video) {
        video.srcObject = null;
        video.src = '';
        video.muted = true;
        video.controls = false;
    }
    if (videoPreview) {
        videoPreview.src = '';
        videoPreview.style.display = 'none';
    }
    if (modal) modal.style.display = 'none';
    
    capturedBlob = null;
}

async function flipCamera() {
    currentFacingMode = currentFacingMode === 'user' ? 'environment' : 'user';
    const video = document.getElementById('webcamVideo');
    const needsAudio = (cameraMode === 'video');
    
    if (webcamStream) {
        webcamStream.getTracks().forEach(track => track.stop());
    }
    
    try {
        webcamStream = await navigator.mediaDevices.getUserMedia({
            video: { 
                width: { ideal: 1280 }, 
                height: { ideal: 720 }, 
                facingMode: currentFacingMode,
                frameRate: { ideal: 30 }
            },
            audio: needsAudio
        });
        video.srcObject = webcamStream;
    } catch (err) {
        console.warn('Flip camera failed:', err);
    }
}

async function setCameraMode(mode) {
    cameraMode = mode;
    const btnFoto = document.getElementById('btnModeFoto');
    const btnVideo = document.getElementById('btnModeVideo');
    const shutterInner = document.getElementById('shutterInner');
    const btnShutter = document.getElementById('btnShutter');
    
    if (mode === 'foto') {
        btnFoto.style.background = 'white';
        btnFoto.style.color = '#000';
        btnVideo.style.background = 'transparent';
        btnVideo.style.color = 'rgba(255,255,255,0.6)';
        shutterInner.style.background = 'white';
        shutterInner.style.borderRadius = '50%';
        shutterInner.style.width = '58px';
        shutterInner.style.height = '58px';
        btnShutter.onclick = takeWebcamSnapshot;
        
        // Quitar audio track si existe (modo foto no necesita audio)
        if (webcamStream) {
            webcamStream.getAudioTracks().forEach(track => {
                track.stop();
                webcamStream.removeTrack(track);
            });
        }
    } else {
        btnVideo.style.background = 'white';
        btnVideo.style.color = '#000';
        btnFoto.style.background = 'transparent';
        btnFoto.style.color = 'rgba(255,255,255,0.6)';
        shutterInner.style.background = '#ef4444';
        shutterInner.style.borderRadius = '50%';
        shutterInner.style.width = '58px';
        shutterInner.style.height = '58px';
        btnShutter.onclick = toggleRecording;
        
        // Agregar audio track para modo video
        if (webcamStream && webcamStream.getAudioTracks().length === 0) {
            try {
                const audioStream = await navigator.mediaDevices.getUserMedia({ audio: true });
                audioStream.getAudioTracks().forEach(track => {
                    webcamStream.addTrack(track);
                });
            } catch(e) {
                console.warn('No se pudo obtener audio:', e);
            }
        }
    }
}

function takeWebcamSnapshot() {
    const video = document.getElementById('webcamVideo');
    const canvas = document.getElementById('webcamCanvas');
    const preview = document.getElementById('webcamPreview');
    if (!video || !canvas || !webcamStream) return;

    canvas.width = video.videoWidth || 1280;
    canvas.height = video.videoHeight || 720;
    const ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

    canvas.toBlob((blob) => {
        if (blob) {
            capturedBlob = blob;
            preview.src = URL.createObjectURL(blob);
            preview.style.display = 'block';
            video.style.display = 'none';
            document.getElementById('webcamBottomControls').style.display = 'none';
            document.getElementById('webcamPreviewControls').style.display = 'flex';
        }
    }, 'image/jpeg', 0.92);
}

function discardCapture() {
    capturedBlob = null;
    const video = document.getElementById('webcamVideo');
    const preview = document.getElementById('webcamPreview');
    const videoPreview = document.getElementById('webcamVideoPreview');
    
    preview.style.display = 'none';
    videoPreview.style.display = 'none';
    videoPreview.src = '';
    video.style.display = 'block';
    
    // Re-attach stream si sigue activo
    if (webcamStream && webcamStream.active) {
        video.srcObject = webcamStream;
        video.muted = true;
        video.controls = false;
    } else {
        // Re-open camera
        openWebcamModal();
        return;
    }
    
    document.getElementById('webcamBottomControls').style.display = 'flex';
    document.getElementById('webcamPreviewControls').style.display = 'none';
}

function confirmCapture() {
    if (!capturedBlob) return;
    
    const isVideo = capturedBlob.type.startsWith('video');
    const ext = isVideo ? 'mp4' : 'jpg';
    const mimeType = isVideo ? 'video/mp4' : 'image/jpeg';
    const capturedFile = new File([capturedBlob], `cam_${Date.now()}.${ext}`, { type: mimeType });
    
    // Enviar DIRECTAMENTE al chat (sin pasar por preview de selección)
    if (typeof sendCapturedFileDirectly === 'function') {
        sendCapturedFileDirectly(capturedFile);
    } else if (typeof handleFileSelect === 'function') {
        handleFileSelect({ files: [capturedFile] });
    }
    closeWebcamModal();
}

function toggleRecording() {
    if (mediaRecorder && mediaRecorder.state === 'recording') {
        stopRecording();
    } else {
        startRecording();
    }
}

function startRecording() {
    if (!webcamStream) return;
    
    recordedChunks = [];
    
    // Seleccionar el mejor formato disponible
    let selectedMimeType = 'video/webm';
    const mimeTypes = ['video/mp4', 'video/webm;codecs=h264,opus', 'video/webm;codecs=vp9,opus', 'video/webm;codecs=vp8,opus', 'video/webm'];
    for (const mt of mimeTypes) {
        if (MediaRecorder.isTypeSupported(mt)) {
            selectedMimeType = mt;
            break;
        }
    }
    
    try {
        mediaRecorder = new MediaRecorder(webcamStream, { 
            mimeType: selectedMimeType,
            videoBitsPerSecond: 2500000 
        });
    } catch (e) {
        mediaRecorder = new MediaRecorder(webcamStream);
    }
    
    mediaRecorder.ondataavailable = (e) => {
        if (e.data && e.data.size > 0) recordedChunks.push(e.data);
    };
    
    mediaRecorder.onstop = () => {
        const mimeUsed = mediaRecorder.mimeType || 'video/webm';
        const blob = new Blob(recordedChunks, { type: mimeUsed });
        capturedBlob = blob;
        
        // Mostrar preview en un elemento de video separado
        const video = document.getElementById('webcamVideo');
        const videoPreview = document.getElementById('webcamVideoPreview');
        
        video.style.display = 'none';
        videoPreview.src = URL.createObjectURL(blob);
        videoPreview.style.display = 'block';
        videoPreview.play();
        
        document.getElementById('webcamBottomControls').style.display = 'none';
        document.getElementById('webcamPreviewControls').style.display = 'flex';
    };
    
    mediaRecorder.start(1000);
    recordingSeconds = 0;
    updateRecordingTimer();
    recordingTimer = setInterval(updateRecordingTimer, 1000);
    
    // UI changes
    document.getElementById('recordingIndicator').style.display = 'flex';
    const shutterInner = document.getElementById('shutterInner');
    shutterInner.style.borderRadius = '8px';
    shutterInner.style.width = '30px';
    shutterInner.style.height = '30px';
}

function stopRecording() {
    if (mediaRecorder && mediaRecorder.state === 'recording') {
        mediaRecorder.stop();
    }
    if (recordingTimer) {
        clearInterval(recordingTimer);
        recordingTimer = null;
    }
    const indicator = document.getElementById('recordingIndicator');
    if (indicator) indicator.style.display = 'none';
    
    const shutterInner = document.getElementById('shutterInner');
    if (shutterInner) {
        shutterInner.style.borderRadius = '50%';
        shutterInner.style.width = '58px';
        shutterInner.style.height = '58px';
    }
}

function updateRecordingTimer() {
    recordingSeconds++;
    const mins = String(Math.floor(recordingSeconds / 60)).padStart(2, '0');
    const secs = String(recordingSeconds % 60).padStart(2, '0');
    const el = document.getElementById('recordingTime');
    if (el) el.textContent = `${mins}:${secs}`;
}

function toggleCamFlash() {
    if (!webcamStream) return;
    const track = webcamStream.getVideoTracks()[0];
    if (!track) return;
    
    const capabilities = track.getCapabilities ? track.getCapabilities() : {};
    if (capabilities.torch) {
        const settings = track.getSettings();
        track.applyConstraints({ advanced: [{ torch: !settings.torch }] });
        const icon = document.querySelector('#btnCamFlash i');
        if (icon) {
            icon.className = settings.torch ? 'ph-bold ph-lightning-slash' : 'ph-bold ph-lightning';
        }
    }
}

document.addEventListener('click', function(e) {
    const modal = document.getElementById('webcamCaptureModal');
    if (modal && e.target === modal) {
        closeWebcamModal();
    }
});
</script>
