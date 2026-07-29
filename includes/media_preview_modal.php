<!-- Modal de Vista Previa de Medios (WhatsApp style) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js"></script>
<div id="mediaPreviewModal" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 9999999; background: #000; display: none; overflow: hidden;">
    
    <!-- Top Bar -->
    <div style="position: absolute; top: 0; left: 0; right: 0; padding: 16px 16px calc(16px + env(safe-area-inset-top, 0px)); display: flex; justify-content: space-between; align-items: center; z-index: 20; background: linear-gradient(180deg, rgba(0,0,0,0.7) 0%, transparent 100%); pointer-events: none;">
        <button onclick="closeMediaPreview()" style="pointer-events: auto; width: 40px; height: 40px; border-radius: 50%; background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); border: none; color: white; font-size: 1.3rem; display: flex; align-items: center; justify-content: center; cursor: pointer;">
            <i class="ph-bold ph-x"></i>
        </button>
        
        <!-- Iconos decorativos -->
        <div style="pointer-events: auto; display: flex; gap: 16px; color: white; font-size: 1.4rem;" id="mediaPreviewTopActions">
            <i class="ph-bold ph-crop" onclick="startMediaCrop()" style="cursor: pointer;" id="btnCropMedia"></i>
            <i class="ph-bold ph-smiley-sticker" onclick="openStickerSheet()" style="cursor: pointer;" id="btnStickerMedia"></i>
            <i class="ph-bold ph-text-t" onclick="startMediaText()" style="cursor: pointer;" id="btnTextMedia"></i>
            <i class="ph-bold ph-pencil-simple" style="opacity: 0.5;"></i>
        </div>
    </div>

    <!-- Controles de Texto (ocultos por defecto) -->
    <div id="mediaTextControls" style="position: absolute; top: 0; left: 0; right: 0; padding: 16px 16px calc(16px + env(safe-area-inset-top, 0px)); display: none; justify-content: space-between; align-items: center; z-index: 25; background: linear-gradient(180deg, rgba(0,0,0,0.7) 0%, transparent 100%);">
        <button onclick="saveMediaText()" style="background: rgba(0,0,0,0.5); color: white; border: 1px solid white; padding: 6px 16px; border-radius: 20px; font-weight: bold; cursor: pointer; backdrop-filter: blur(5px);">OK</button>
        <div style="display: flex; gap: 16px; color: white; font-size: 1.4rem;">
            <i class="ph-bold ph-text-align-center" id="textAlignBtn" onclick="toggleTextAlign()" style="cursor: pointer;"></i>
            <i class="ph-bold ph-square" id="textBgBtn" onclick="toggleTextBg()" style="cursor: pointer;"></i>
        </div>
    </div>

    <!-- Barra de Color (Derecha) -->
    <div id="mediaTextColorPicker" style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); display: none; flex-direction: column; z-index: 25;">
        <input type="range" id="textColorSlider" min="0" max="360" value="0" style="writing-mode: bt-lr; -webkit-appearance: slider-vertical; height: 200px; width: 10px; border-radius: 5px; background: linear-gradient(to bottom, #f00, #ff0, #0f0, #0ff, #00f, #f0f, #f00); outline: none; cursor: pointer;" oninput="updateTextColor()">
        <div id="currentColorIndicator" style="width: 20px; height: 20px; border-radius: 50%; background: red; border: 2px solid white; margin-top: 10px; align-self: center;"></div>
    </div>

    <!-- Selector de Fuentes (Abajo) -->
    <div id="mediaTextFontPicker" style="position: absolute; bottom: 20px; left: 0; right: 0; display: none; justify-content: center; gap: 15px; z-index: 25; padding: 10px; overflow-x: auto; white-space: nowrap;">
        <button onclick="setTextFont('Arial')" style="background: transparent; color: white; border: none; font-size: 1.2rem; font-family: Arial; cursor: pointer;">Aa</button>
        <button onclick="setTextFont('Impact')" style="background: transparent; color: white; border: none; font-size: 1.2rem; font-family: Impact; cursor: pointer;">Aa</button>
        <button onclick="setTextFont('Comic Sans MS')" style="background: transparent; color: white; border: none; font-size: 1.2rem; font-family: 'Comic Sans MS', cursive; cursor: pointer;">Aa</button>
        <button onclick="setTextFont('Times New Roman')" style="background: transparent; color: white; border: none; font-size: 1.2rem; font-family: 'Times New Roman', serif; cursor: pointer;">Aa</button>
        <button onclick="setTextFont('Courier New')" style="background: transparent; color: white; border: none; font-size: 1.2rem; font-family: 'Courier New', monospace; cursor: pointer;">Aa</button>
    </div>
    
    <!-- Sticker Bottom Sheet -->
    <div id="mediaStickerSheet" style="position: absolute; bottom: -80vh; left: 0; right: 0; height: 80vh; background: #111b21; border-top-left-radius: 20px; border-top-right-radius: 20px; z-index: 30; transition: bottom 0.3s ease-out; display: flex; flex-direction: column; overflow: hidden;">
        <div style="padding: 15px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.1);">
            <div style="width: 40px;"></div>
            <div style="width: 40px; height: 5px; background: rgba(255,255,255,0.3); border-radius: 5px; margin: 0 auto; cursor: pointer;" onclick="closeStickerSheet()"></div>
            <button onclick="closeStickerSheet()" style="background: transparent; color: white; border: none; font-size: 1.2rem; cursor: pointer;"><i class="ph-bold ph-x"></i></button>
        </div>
        
        <div style="flex: 1; overflow-y: auto; padding: 20px;">
            <div style="color: #8696a0; font-size: 0.9rem; font-weight: bold; margin-bottom: 15px;">Stickers dinámicos</div>
            <div style="display: flex; gap: 15px; margin-bottom: 30px;">
                <button onclick="addDynamicSticker('time')" style="background: white; border: none; border-radius: 12px; padding: 10px 15px; font-weight: bold; font-size: 1.1rem; color: #111b21; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                    <span id="stickerTimePreview">12:00</span> p. m.
                </button>
                <button onclick="addDynamicSticker('location')" style="background: white; border: none; border-radius: 12px; padding: 10px 15px; font-weight: bold; font-size: 1rem; color: #111b21; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                    <i class="ph-fill ph-map-pin" style="color: #10b981;"></i> Ubicación
                </button>
            </div>

            <div style="color: #8696a0; font-size: 0.9rem; font-weight: bold; margin-bottom: 15px;">Formas</div>
            <div style="display: flex; gap: 25px; flex-wrap: wrap;">
                <button onclick="addShape('arrow')" style="background: transparent; border: none; color: #10b981; font-size: 2.5rem; cursor: pointer;"><i class="ph-bold ph-arrow-up-right"></i></button>
                <button onclick="addShape('circle')" style="background: transparent; border: none; color: #10b981; font-size: 2.5rem; cursor: pointer;"><i class="ph-bold ph-circle"></i></button>
                <button onclick="addShape('square')" style="background: transparent; border: none; color: #10b981; font-size: 2.5rem; cursor: pointer;"><i class="ph-bold ph-square"></i></button>
            </div>
        </div>
    </div>
    <!-- Controles de Crop (ocultos por defecto) -->
    <div id="mediaCropControls" style="position: absolute; top: 0; left: 0; right: 0; padding: 16px; display: none; justify-content: space-between; align-items: center; z-index: 25; background: #000;">
        <button onclick="cancelMediaCrop()" style="background: transparent; color: white; border: none; font-size: 1rem; cursor: pointer;">Cancelar</button>
        <button onclick="saveMediaCrop()" style="background: #10b981; color: white; border: none; padding: 6px 16px; border-radius: 20px; font-weight: bold; cursor: pointer;">Hecho</button>
    </div>

    <!-- Media Container -->
    <div id="mediaPreviewContainer" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; overflow: hidden; z-index: 5; background: #000;">
        <img id="mediaPreviewImg" style="display: none; width: 100%; height: 100%; object-fit: contain; margin: 0 auto;">
        <video id="mediaPreviewVid" style="display: none; width: 100%; height: 100%; object-fit: contain; background: #000;" controls playsinline></video>
        <!-- Canvas para Fabric.js -->
        <canvas id="mediaFabricCanvas"></canvas>
        <div id="mediaPreviewDoc" style="display: none; color: white; text-align: center; font-size: 1.2rem; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
            <i class="ph-fill ph-file-pdf" style="font-size: 4rem; color: #ef4444; margin-bottom: 10px; display: block;"></i>
            <span id="mediaPreviewDocName"></span>
        </div>
    </div>

    <!-- Bottom Input Area -->
    <div id="mediaPreviewBottomArea" style="position: absolute; bottom: 0; left: 0; right: 0; padding: 10px 14px calc(16px + env(safe-area-inset-bottom, 0px)); background: rgba(0,0,0,0.8); display: flex; flex-direction: column; gap: 10px; border-top: 1px solid rgba(255,255,255,0.1); z-index: 20;">
        
        <!-- Emoji Picker -->
        <div id="mediaPreviewEmojiPicker" style="display: none; align-self: flex-start; background: #1e293b; border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; margin-bottom: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); overflow: hidden;">
            <emoji-picker class="dark" style="--background: #1e293b; --border-color: rgba(255,255,255,0.1); --num-columns: 7; --category-font-size: 0.9rem; --indicator-color: #10b981; --input-border-color: rgba(255,255,255,0.2); --input-font-color: white; height: 300px; width: 100%; max-width: 350px;"></emoji-picker>
        </div>

        <div style="display: flex; align-items: flex-end; gap: 10px; width: 100%;">

        
        <!-- Input estilo WhatsApp -->
        <div style="flex: 1; display: flex; align-items: center; background: #1e293b; border-radius: 24px; padding: 4px 14px; gap: 8px;">
            <button type="button" onclick="toggleMediaEmojiPicker()" style="background: transparent; border: none; padding: 0; cursor: pointer; display: flex;">
                <i class="ph-bold ph-smiley" style="color: #94a3b8; font-size: 1.4rem;"></i>
            </button>
            <textarea id="mediaPreviewCaption" placeholder="Añade un comentario..." rows="1" style="flex: 1; background: transparent; border: none; padding: 10px 0; color: white; outline: none; font-size: 1rem; resize: none; max-height: 100px; line-height: 1.4;" oninput="this.style.height = ''; this.style.height = this.scrollHeight + 'px';"></textarea>
        </div>

        <!-- Send Button -->
        <button onclick="confirmMediaPreviewSend()" style="flex-shrink: 0; min-width: 48px; width: 48px; height: 48px; background: #10b981; border: none; border-radius: 50%; color: white; font-size: 1.4rem; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 4px 12px rgba(16,185,129,0.35);">
            <i class="ph-fill ph-paper-plane-right"></i>
        </button>
        </div>
    </div>

</div>

<script>
let pendingMediaFile = null;
let backgroundUploadPromise = null;
let cropperInstance = null;
let fabricCanvas = null;
let currentTextObj = null;
let currentTextAlign = 'center';
let currentTextBgState = 0; // 0: clear, 1: solid, 2: translucent

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
    document.getElementById('mediaPreviewEmojiPicker').style.display = 'none';
    
    // Reset Fabric wrapper si existe
    const fabricWrapper = document.querySelector('.canvas-container');
    if (fabricWrapper) fabricWrapper.style.display = 'none';
    
    // Ocultar icono de crop y texto si no es imagen
    const isImage = file.type.startsWith('image/');
    const cropIcon = document.getElementById('btnCropMedia');
    const textIcon = document.getElementById('btnTextMedia');
    if (cropIcon) cropIcon.style.display = isImage ? 'block' : 'none';
    if (textIcon) textIcon.style.display = isImage ? 'block' : 'none';
    
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
    if (cropperInstance) {
        cropperInstance.destroy();
        cropperInstance = null;
    }
}

// === Emojis ===
function toggleMediaEmojiPicker() {
    const picker = document.getElementById('mediaPreviewEmojiPicker');
    picker.style.display = picker.style.display === 'none' ? 'block' : 'none';
}

document.querySelector('#mediaPreviewEmojiPicker emoji-picker').addEventListener('emoji-click', event => {
    const input = document.getElementById('mediaPreviewCaption');
    input.value += event.detail.unicode;
    input.focus();
    input.style.height = ''; 
    input.style.height = input.scrollHeight + 'px';
    toggleMediaEmojiPicker();
});

// === Crop ===
function startMediaCrop() {
    const img = document.getElementById('mediaPreviewImg');
    if (!pendingMediaFile || !pendingMediaFile.type.startsWith('image/') || !img.src) return;
    
    document.getElementById('mediaPreviewTopActions').style.display = 'none';
    document.getElementById('mediaPreviewBottomArea').style.display = 'none';
    document.getElementById('mediaCropControls').style.display = 'flex';
    
    cropperInstance = new Cropper(img, {
        viewMode: 1,
        dragMode: 'move',
        background: false,
        guides: true,
        highlight: false,
        autoCropArea: 0.9,
    });
}

function cancelMediaCrop() {
    if (cropperInstance) {
        cropperInstance.destroy();
        cropperInstance = null;
    }
    document.getElementById('mediaPreviewTopActions').style.display = 'flex';
    document.getElementById('mediaPreviewBottomArea').style.display = 'flex';
    document.getElementById('mediaCropControls').style.display = 'none';
}

function saveMediaCrop() {
    if (!cropperInstance) return;
    
    cropperInstance.getCroppedCanvas({
        maxWidth: 1920,
        maxHeight: 1080
    }).toBlob((blob) => {
        if (blob) {
            // Actualizar archivo pendiente
            pendingMediaFile = new File([blob], `cropped_${Date.now()}.jpg`, { type: 'image/jpeg' });
            
            // Actualizar preview
            const img = document.getElementById('mediaPreviewImg');
            img.src = URL.createObjectURL(blob);
            
            cancelMediaCrop(); // Destruye instancia y restaura UI
            startBackgroundUpload(pendingMediaFile); // Restart upload temp if applicable
        }
    }, 'image/jpeg', 0.9);
}

// === Fabric.js Text Overlay ===
function startMediaText() {
    const img = document.getElementById('mediaPreviewImg');
    if (!pendingMediaFile || !pendingMediaFile.type.startsWith('image/') || !img.src) return;
    
    // Ocultar controles principales
    document.getElementById('mediaPreviewTopActions').style.display = 'none';
    document.getElementById('mediaPreviewBottomArea').style.display = 'none';
    
    // Mostrar controles de texto
    document.getElementById('mediaTextControls').style.display = 'flex';
    document.getElementById('mediaTextColorPicker').style.display = 'flex';
    document.getElementById('mediaTextFontPicker').style.display = 'flex';
    document.getElementById('textAlignBtn').style.display = 'inline-block';
    document.getElementById('textBgBtn').style.display = 'inline-block';
    
    // Ocultar la imagen original
    img.style.display = 'none';
    
    // Dimensiones de pantalla
    const container = document.getElementById('mediaPreviewContainer');
    const screenW = container.clientWidth;
    const screenH = container.clientHeight;
    
    // Obtener dimensiones reales de la imagen cargada
    const tempImg = new Image();
    tempImg.onload = () => {
        const imgW = tempImg.width;
        const imgH = tempImg.height;
        
        // Calcular dimensiones renderizadas (simulando object-fit: contain)
        const imgAspect = imgW / imgH;
        const screenAspect = screenW / screenH;
        
        let renderW, renderH;
        if (imgAspect > screenAspect) {
            renderW = screenW;
            renderH = screenW / imgAspect;
        } else {
            renderH = screenH;
            renderW = screenH * imgAspect;
        }
        
        // Inicializar Canvas de Fabric si no existe
        if (!fabricCanvas) {
            fabricCanvas = new fabric.Canvas('mediaFabricCanvas', {
                width: renderW,
                height: renderH,
                selection: false
            });
            
            // Centrar el canvas en la pantalla usando CSS en el wrapper que crea Fabric
            const wrapper = document.querySelector('.canvas-container');
            wrapper.style.position = 'absolute';
            wrapper.style.top = '50%';
            wrapper.style.left = '50%';
            wrapper.style.transform = 'translate(-50%, -50%)';
            wrapper.style.display = 'block';
            
            // Cargar imagen como fondo y luego inicializar
            fabric.Image.fromURL(tempImg.src, (fImg) => {
                fImg.scaleToWidth(renderW);
                fabricCanvas.setBackgroundImage(fImg, fabricCanvas.renderAll.bind(fabricCanvas));
                addInitialText(renderW, renderH);
            });
        } else {
            addInitialText(renderW, renderH);
        }
        
        function addInitialText(w, h) {
            currentTextObj = new fabric.IText('Añade texto', {
                left: w / 2,
                top: h / 2,
                originX: 'center',
                originY: 'center',
                fontFamily: 'Arial',
                fill: '#ff0000',
                fontSize: 40,
                textAlign: 'center',
                transparentCorners: false,
                cornerColor: '#10b981',
                cornerStyle: 'circle',
                borderColor: '#10b981',
                cursorColor: '#10b981',
                padding: 10
            });
            
            fabricCanvas.add(currentTextObj);
            fabricCanvas.setActiveObject(currentTextObj);
            currentTextObj.enterEditing();
            currentTextObj.selectAll();
            fabricCanvas.renderAll();
            
            setupFabricSelectionEvents();
        }
    };
    tempImg.src = img.src;
}

function setupFabricSelectionEvents() {
    fabricCanvas.off('selection:created');
    fabricCanvas.off('selection:updated');
    
    const handleSelect = (e) => {
        const obj = e.selected[0];
        if (obj) {
            currentTextObj = obj; // Tratar formas y texto de manera similar para el color
        }
    };
    
    fabricCanvas.on('selection:created', handleSelect);
    fabricCanvas.on('selection:updated', handleSelect);
}

function updateTextColor() {
    if (!currentTextObj) return;
    const hue = document.getElementById('textColorSlider').value;
    const color = `hsl(${hue}, 100%, 50%)`;
    document.getElementById('currentColorIndicator').style.background = color;
    
    if (currentTextObj.type === 'i-text' || currentTextObj.type === 'text') {
        currentTextObj.set('fill', color);
        if (currentTextBgState > 0) {
            applyTextBgState(color);
        }
    } else {
        // Para formas (círculo, cuadrado, flecha)
        currentTextObj.set('stroke', color);
    }
    
    fabricCanvas.renderAll();
}

function setTextFont(font) {
    if (!currentTextObj) return;
    currentTextObj.set('fontFamily', font);
    fabricCanvas.renderAll();
}

function toggleTextAlign() {
    if (!currentTextObj) return;
    const aligns = ['left', 'center', 'right'];
    const icons = ['ph-text-align-left', 'ph-text-align-center', 'ph-text-align-right'];
    
    let idx = aligns.indexOf(currentTextAlign);
    idx = (idx + 1) % 3;
    currentTextAlign = aligns[idx];
    
    currentTextObj.set('textAlign', currentTextAlign);
    
    const btn = document.getElementById('textAlignBtn');
    btn.className = `ph-bold ${icons[idx]}`;
    
    fabricCanvas.renderAll();
}

function toggleTextBg() {
    if (!currentTextObj) return;
    currentTextBgState = (currentTextBgState + 1) % 3; // 0: clear, 1: translucent, 2: solid
    
    const hue = document.getElementById('textColorSlider').value;
    const color = `hsl(${hue}, 100%, 50%)`;
    
    applyTextBgState(color);
    fabricCanvas.renderAll();
}

function applyTextBgState(baseColor) {
    if (!currentTextObj) return;
    
    // Usamos el opuesto (o negro/blanco) para el texto si hay fondo, o simplemente mantenemos el texto y ponemos fondo negro/blanco
    // Por simplicidad: si hay fondo, el texto es blanco/negro y el fondo es el color elegido.
    
    if (currentTextBgState === 0) {
        currentTextObj.set('textBackgroundColor', '');
        currentTextObj.set('fill', baseColor);
    } else if (currentTextBgState === 1) {
        // Translucent background
        const rgb = hslToRgb(document.getElementById('textColorSlider').value / 360, 1, 0.5);
        currentTextObj.set('textBackgroundColor', `rgba(${rgb[0]},${rgb[1]},${rgb[2]},0.5)`);
        currentTextObj.set('fill', '#ffffff');
    } else if (currentTextBgState === 2) {
        // Solid background
        currentTextObj.set('textBackgroundColor', baseColor);
        currentTextObj.set('fill', '#ffffff');
    }
}

// Función helper para HSL a RGB
function hslToRgb(h, s, l) {
    var r, g, b;
    if (s == 0) { r = g = b = l; } 
    else {
        var hue2rgb = function hue2rgb(p, q, t) {
            if (t < 0) t += 1;
            if (t > 1) t -= 1;
            if (t < 1/6) return p + (q - p) * 6 * t;
            if (t < 1/2) return q;
            if (t < 2/3) return p + (q - p) * (2/3 - t) * 6;
            return p;
        }
        var q = l < 0.5 ? l * (1 + s) : l + s - l * s;
        var p = 2 * l - q;
        r = hue2rgb(p, q, h + 1/3);
        g = hue2rgb(p, q, h);
        b = hue2rgb(p, q, h - 1/3);
    }
    return [Math.round(r * 255), Math.round(g * 255), Math.round(b * 255)];
}

function saveMediaText() {
    if (!fabricCanvas) return;
    
    // Deseleccionar el texto para quitar los bordes de edición antes de exportar
    fabricCanvas.discardActiveObject();
    fabricCanvas.renderAll();
    
    // Calcular el multiplicador para exportar a la resolución original
    const img = document.getElementById('mediaPreviewImg');
    const tempImg = new Image();
    tempImg.onload = () => {
        const originalW = tempImg.width;
        const renderW = fabricCanvas.width;
        const multiplier = originalW / renderW;
        
        const dataUrl = fabricCanvas.toDataURL({
            format: 'jpeg',
            quality: 0.9,
            multiplier: multiplier
        });
        
        // Convertir dataURL a Blob
        fetch(dataUrl).then(res => res.blob()).then(blob => {
            // Actualizar archivo pendiente
            pendingMediaFile = new File([blob], `texted_${Date.now()}.jpg`, { type: 'image/jpeg' });
            
            // Actualizar preview
            img.src = URL.createObjectURL(blob);
            
            // Ocultar Fabric y mostrar UI normal
            document.querySelector('.canvas-container').style.display = 'none';
            img.style.display = 'block';
            
            document.getElementById('mediaPreviewTopActions').style.display = 'flex';
            document.getElementById('mediaPreviewBottomArea').style.display = 'flex';
            
            document.getElementById('mediaTextControls').style.display = 'none';
            document.getElementById('mediaTextColorPicker').style.display = 'none';
            document.getElementById('mediaTextFontPicker').style.display = 'none';
            
            fabricCanvas.dispose();
            fabricCanvas = null;
            
            startBackgroundUpload(pendingMediaFile); // Restart upload temp
        });
    };
    tempImg.src = img.src;
}

// === Funciones de Stickers y Formas ===

function openStickerSheet() {
    const img = document.getElementById('mediaPreviewImg');
    if (!pendingMediaFile || !pendingMediaFile.type.startsWith('image/') || !img.src) return;
    
    // Configurar la hora actual en el preview
    const now = new Date();
    let hours = now.getHours();
    let ampm = hours >= 12 ? 'p. m.' : 'a. m.';
    hours = hours % 12;
    hours = hours ? hours : 12; 
    let minutes = now.getMinutes().toString().padStart(2, '0');
    document.getElementById('stickerTimePreview').innerText = `${hours}:${minutes}`;
    document.getElementById('stickerTimePreview').nextSibling.nodeValue = ` ${ampm}`;

    document.getElementById('mediaStickerSheet').style.bottom = '0';
}

function closeStickerSheet() {
    document.getElementById('mediaStickerSheet').style.bottom = '-80vh';
}

function initFabricForShapeOrSticker(callback) {
    closeStickerSheet();
    
    const img = document.getElementById('mediaPreviewImg');
    document.getElementById('mediaPreviewTopActions').style.display = 'none';
    document.getElementById('mediaPreviewBottomArea').style.display = 'none';
    document.getElementById('mediaTextControls').style.display = 'flex';
    document.getElementById('mediaTextColorPicker').style.display = 'flex';
    // Ocultar fuentes y opciones de texto que no aplican
    document.getElementById('mediaTextFontPicker').style.display = 'none';
    document.getElementById('textAlignBtn').style.display = 'none';
    document.getElementById('textBgBtn').style.display = 'none';
    
    img.style.display = 'none';
    
    const container = document.getElementById('mediaPreviewContainer');
    const tempImg = new Image();
    tempImg.onload = () => {
        let renderW = fabricCanvas ? fabricCanvas.width : 0;
        let renderH = fabricCanvas ? fabricCanvas.height : 0;
        
        if (!fabricCanvas) {
            const imgAspect = tempImg.width / tempImg.height;
            const screenAspect = container.clientWidth / container.clientHeight;
            if (imgAspect > screenAspect) {
                renderW = container.clientWidth;
                renderH = container.clientWidth / imgAspect;
            } else {
                renderH = container.clientHeight;
                renderW = container.clientHeight * imgAspect;
            }
            
            fabricCanvas = new fabric.Canvas('mediaFabricCanvas', { width: renderW, height: renderH, selection: false });
            const wrapper = document.querySelector('.canvas-container');
            wrapper.style.position = 'absolute';
            wrapper.style.top = '50%';
            wrapper.style.left = '50%';
            wrapper.style.transform = 'translate(-50%, -50%)';
            wrapper.style.display = 'block';
            
            fabric.Image.fromURL(tempImg.src, (fImg) => {
                fImg.scaleToWidth(renderW);
                fabricCanvas.setBackgroundImage(fImg, fabricCanvas.renderAll.bind(fabricCanvas));
                callback(renderW, renderH);
            });
        } else {
            document.querySelector('.canvas-container').style.display = 'block';
            callback(renderW, renderH);
        }
        setupFabricSelectionEvents();
    };
    tempImg.src = img.src;
}

function addShape(type) {
    initFabricForShapeOrSticker((w, h) => {
        let shape;
        const color = '#10b981';
        
        if (type === 'circle') {
            shape = new fabric.Circle({
                radius: 50, left: w/2, top: h/2, originX: 'center', originY: 'center',
                fill: 'transparent', stroke: color, strokeWidth: 8
            });
        } else if (type === 'square') {
            shape = new fabric.Rect({
                width: 100, height: 100, left: w/2, top: h/2, originX: 'center', originY: 'center',
                fill: 'transparent', stroke: color, strokeWidth: 8
            });
        } else if (type === 'arrow') {
            // Flecha simple usando un path
            const path = "M 0 50 L 100 50 M 70 20 L 100 50 L 70 80";
            shape = new fabric.Path(path, {
                left: w/2, top: h/2, originX: 'center', originY: 'center',
                fill: 'transparent', stroke: color, strokeWidth: 8, strokeLineCap: 'round', strokeLineJoin: 'round'
            });
        }
        
        if (shape) {
            shape.set({
                transparentCorners: false, cornerColor: '#ffffff', borderColor: '#ffffff',
                cornerStrokeColor: '#000000', padding: 10
            });
            fabricCanvas.add(shape);
            fabricCanvas.setActiveObject(shape);
            fabricCanvas.renderAll();
        }
    });
}

function addDynamicSticker(type) {
    initFabricForShapeOrSticker((w, h) => {
        if (type === 'time') {
            const now = new Date();
            let hours = now.getHours();
            let ampm = hours >= 12 ? 'p.m.' : 'a.m.';
            hours = hours % 12;
            hours = hours ? hours : 12; 
            let minutes = now.getMinutes().toString().padStart(2, '0');
            const timeStr = `${hours}:${minutes} ${ampm}`;
            
            const text = new fabric.Text(timeStr, {
                fontSize: 30, fontFamily: 'Arial', fontWeight: 'bold', fill: '#000000',
                originX: 'center', originY: 'center'
            });
            
            const bg = new fabric.Rect({
                width: text.width + 40, height: text.height + 20, rx: 20, ry: 20,
                fill: '#ffffff', originX: 'center', originY: 'center'
            });
            
            const group = new fabric.Group([bg, text], {
                left: w/2, top: h/2, originX: 'center', originY: 'center',
                transparentCorners: false, cornerColor: '#ffffff', borderColor: '#ffffff', padding: 10
            });
            
            fabricCanvas.add(group);
            fabricCanvas.setActiveObject(group);
            fabricCanvas.renderAll();
        } else if (type === 'location') {
            createLocationSticker(w, h, "Buscando...");
            
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(pos => {
                    const lat = pos.coords.latitude;
                    const lon = pos.coords.longitude;
                    fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}&zoom=14`)
                        .then(res => res.json())
                        .then(data => {
                            let locName = data.address.city || data.address.town || data.address.village || data.address.suburb || "Ubicación";
                            updateLocationSticker(locName);
                        })
                        .catch(() => updateLocationSticker("Ubicación actual"));
                }, () => updateLocationSticker("Ubicación actual"));
            } else {
                updateLocationSticker("Ubicación actual");
            }
        }
    });
}

let tempLocGroup = null;
function createLocationSticker(w, h, textStr) {
    const text = new fabric.Text("📍 " + textStr, {
        fontSize: 24, fontFamily: 'Arial', fontWeight: 'bold', fill: '#000000',
        originX: 'center', originY: 'center'
    });
    const bg = new fabric.Rect({
        width: text.width + 40, height: text.height + 20, rx: 20, ry: 20,
        fill: '#ffffff', originX: 'center', originY: 'center'
    });
    tempLocGroup = new fabric.Group([bg, text], {
        left: w/2, top: h/2, originX: 'center', originY: 'center',
        transparentCorners: false, cornerColor: '#ffffff', borderColor: '#ffffff', padding: 10
    });
    fabricCanvas.add(tempLocGroup);
    fabricCanvas.setActiveObject(tempLocGroup);
    fabricCanvas.renderAll();
}

function updateLocationSticker(newText) {
    if (!tempLocGroup || !fabricCanvas) return;
    const textObj = tempLocGroup.item(1);
    const bgObj = tempLocGroup.item(0);
    
    textObj.set('text', "📍 " + newText);
    bgObj.set('width', textObj.width + 40);
    
    tempLocGroup.addWithUpdate();
    fabricCanvas.renderAll();
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
