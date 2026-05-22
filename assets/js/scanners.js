/**
 * Global Scanners & Camera Logic
 * Requires: html5-qrcode library
 */

let sysCameraStream = null;
let sysCameraTrack = null;
let sysCameraCallback = null;

let sysQrScanner = null;
let sysQrCallback = null;

let sysBarcodeScanner = null;
let sysBarcodeCallback = null;
let sysBarcodeDetected = [];
let sysBarcodeIsScanning = false;

/* ==========================
   1. GLOBAL CAMERA (PHOTOS)
   ========================== */

function openSysCamera(callback) {
    sysCameraCallback = callback;
    const modal = document.getElementById('sysCameraModal');
    document.body.appendChild(modal); // ensure it's on top of all other modals
    modal.classList.add('active');
    
    const video = document.getElementById('sysCameraVideo');
    const zoomInput = document.getElementById('sysCameraZoom');
    zoomInput.value = 1;
    
    // Request camera
    navigator.mediaDevices.getUserMedia({ 
        video: { facingMode: 'environment', width: { ideal: 1280 }, height: { ideal: 720 } } 
    })
    .then(stream => {
        sysCameraStream = stream;
        video.srcObject = stream;
        
        // Setup zoom if available
        const track = stream.getVideoTracks()[0];
        sysCameraTrack = track;
        const capabilities = track.getCapabilities();
        
        if (capabilities.zoom) {
            zoomInput.min = capabilities.zoom.min;
            zoomInput.max = capabilities.zoom.max;
            zoomInput.step = capabilities.zoom.step;
            zoomInput.value = track.getSettings().zoom || 1;
            zoomInput.disabled = false;
        } else {
            zoomInput.disabled = true;
        }
    })
    .catch(err => {
        console.error("Error accessing camera:", err);
        alert("No se pudo acceder a la cámara. Revisa los permisos.");
    });
}

function closeSysCamera() {
    document.getElementById('sysCameraModal').classList.remove('active');
    if (sysCameraStream) {
        sysCameraStream.getTracks().forEach(track => track.stop());
        sysCameraStream = null;
        sysCameraTrack = null;
    }
}

function updateSysCameraZoom(val) {
    if (sysCameraTrack && sysCameraTrack.getCapabilities().zoom) {
        sysCameraTrack.applyConstraints({
            advanced: [{ zoom: parseFloat(val) }]
        });
    }
}

// Attach capture event
document.getElementById('btnSysCapture')?.addEventListener('click', () => {
    const video = document.getElementById('sysCameraVideo');
    const canvas = document.getElementById('sysCameraCanvas');
    if (!video.videoWidth) return;
    
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    const ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
    
    canvas.toBlob(blob => {
        if (sysCameraCallback) sysCameraCallback(blob);
        closeSysCamera();
    }, 'image/jpeg', 0.85);
});

/* ==========================
   2. GLOBAL QR SCANNER
   ========================== */

function openSysQrScanner(callback) {
    if (typeof Html5Qrcode === 'undefined') {
        alert("Librería de escáner no cargada.");
        return;
    }
    sysQrCallback = callback;
    const qrModal = document.getElementById('sysQrScannerModal');
    document.body.appendChild(qrModal);
    qrModal.classList.add('active');
    
    sysQrScanner = new Html5Qrcode("sysQrReader");
    sysQrScanner.start(
        { facingMode: "environment" },
        { fps: 10, qrbox: { width: 250, height: 250 } },
        (decodedText) => {
            // Success
            if (sysQrCallback) sysQrCallback(decodedText);
            closeSysQrScanner();
        },
        (errorMessage) => {
            // Ignore ongoing errors
        }
    ).catch(err => {
        console.error("Error starting QR scanner", err);
        alert("No se pudo iniciar el escáner de QR.");
    });
}

function closeSysQrScanner() {
    document.getElementById('sysQrScannerModal').classList.remove('active');
    if (sysQrScanner && sysQrScanner.isScanning) {
        sysQrScanner.stop().then(() => {
            sysQrScanner.clear();
        }).catch(err => console.error("Error stopping QR scanner", err));
    }
}

/* ==========================
   3. GLOBAL BARCODE SCANNER
   ========================== */

function openSysBarcodeScanner(callback) {
    if (typeof Html5Qrcode === 'undefined') {
        alert("Librería de escáner no cargada.");
        return;
    }
    sysBarcodeCallback = callback;
    sysBarcodeDetected = [];
    sysBarcodeIsScanning = true;
    
    const bcModal = document.getElementById('sysBarcodeScannerModal');
    document.body.appendChild(bcModal);
    bcModal.classList.add('active');
    document.getElementById('sysBarcodeResultsWrap').style.display = 'none';
    document.getElementById('sysBarcodeList').innerHTML = '';
    
    sysBarcodeScanner = new Html5Qrcode("sysBarcodeReader");
    sysBarcodeScanner.start(
        { facingMode: "environment" },
        { fps: 10, qrbox: { width: 300, height: 150 } },
        (decodedText) => {
            if (!sysBarcodeIsScanning) return;
            
            if (!sysBarcodeDetected.includes(decodedText)) {
                sysBarcodeDetected.push(decodedText);
                renderSysBarcodeResults();
            }
        },
        (errorMessage) => {
            // Ignore
        }
    ).catch(err => {
        console.error("Error starting Barcode scanner", err);
        alert("No se pudo iniciar el escáner de código de barras.");
    });
}

function renderSysBarcodeResults() {
    const wrap = document.getElementById('sysBarcodeResultsWrap');
    const list = document.getElementById('sysBarcodeList');
    
    if (sysBarcodeDetected.length > 0) {
        wrap.style.display = 'block';
        list.innerHTML = sysBarcodeDetected.map(code => `
            <div class="scan-picker-item" style="padding:10px; border:1px solid var(--border-color); border-radius:8px; cursor:pointer; display:flex; justify-content:space-between; align-items:center;" onclick="selectSysBarcode('${code.replace(/'/g, "\\'")}')">
                <span style="font-weight:600;"><i class="ph ph-barcode"></i> ${code}</span>
                <button class="btn btn-primary" style="padding:4px 8px; font-size:0.8rem;">Seleccionar</button>
            </div>
        `).join('');
    }
}

function selectSysBarcode(code) {
    if (sysBarcodeCallback) sysBarcodeCallback(code);
    closeSysBarcodeScanner();
}

function closeSysBarcodeScanner() {
    sysBarcodeIsScanning = false;
    document.getElementById('sysBarcodeScannerModal').classList.remove('active');
    if (sysBarcodeScanner && sysBarcodeScanner.isScanning) {
        sysBarcodeScanner.stop().then(() => {
            sysBarcodeScanner.clear();
        }).catch(err => console.error("Error stopping Barcode scanner", err));
    }
}

/* ==========================
   SHARED ZOOM LOGIC
   ========================== */

window.updateSysScannerZoom = function(val, readerId) {
    let scanner = null;
    if (readerId === 'sysQrReader') scanner = sysQrScanner;
    else if (readerId === 'sysBarcodeReader') scanner = sysBarcodeScanner;
    
    if (scanner && scanner.getState() === 2) { // 2 = scanning
        try {
            const p = scanner.applyVideoConstraints({ advanced: [{ zoom: parseFloat(val) }] });
            if (p && p.catch) {
                p.catch(e => {
                    // Ignorar errores de restricciones de hardware
                    console.warn("Zoom no soportado en este dispositivo");
                });
            }
        } catch(e) {
            console.warn("Zoom no soportado", e);
        }
    }
};
