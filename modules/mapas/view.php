<?php
require_once '../../config/db.php';
requireLogin();
requirePermission($pdo, 'mapas');

$proyecto_id = $_GET['id'] ?? 0;
if (!$proyecto_id) {
    header("Location: index.php");
    exit;
}

// Obtener nombre del proyecto
$stmt = $pdo->prepare("SELECT * FROM mapas_proyectos WHERE id = ?");
$stmt->execute([$proyecto_id]);
$proyecto = $stmt->fetch();
if (!$proyecto) {
    header("Location: index.php");
    exit;
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<!-- Mapbox GL JS -->
<script src='https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js'></script>
<link href='https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css' rel='stylesheet' />
<script src="https://cdnjs.cloudflare.com/ajax/libs/togeojson/0.16.0/togeojson.min.js"></script>

<!-- Mapbox Geocoder -->
<script src="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v5.0.0/mapbox-gl-geocoder.min.js"></script>
<link rel="stylesheet" href="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v5.0.0/mapbox-gl-geocoder.css" type="text/css">

<!-- Mapbox Draw -->
<script src="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-draw/v1.4.3/mapbox-gl-draw.js"></script>
<link rel="stylesheet" href="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-draw/v1.4.3/mapbox-gl-draw.css" type="text/css">

<!-- Turf.js para medición de distancias -->
<script src="https://unpkg.com/@turf/turf@6/turf.min.js"></script>

<style>
    .page-header-card { display: none; }
    
    /* Eliminar paddings del contenedor global para que el mapa ocupe el 100% */
    .content-wrapper {
        padding: 0 !important;
        display: flex;
        flex-direction: column;
        overflow: hidden !important;
    }
    body { overflow: hidden !important; }
    
    .map-wrapper {
        position: relative;
        width: 100%;
        flex: 1; /* Llenar todo el alto disponible del content-wrapper */
        border-radius: 0; /* Quitar bordes redondos para aspecto de pantalla completa */
        overflow: hidden;
        border: none;
    }
    #map { position: absolute; top: 0; bottom: 0; width: 100%; }

    /* Panel Flotante */
    .map-sidebar {
        position: absolute;
        top: 20px;
        left: 20px;
        width: 320px;
        background: var(--surface-color, #1e1e1e);
        border: 1px solid var(--border-color, rgba(255, 255, 255, 0.1));
        border-radius: 20px;
        padding: 24px;
        color: var(--text-color, #f8fafc);
        z-index: 1;
        box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        display: flex;
        flex-direction: column;
        gap: 15px;
        transform: translateX(-150%);
        opacity: 0;
        pointer-events: none;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .map-sidebar.sidebar-active {
        transform: translateX(0);
        opacity: 1;
        pointer-events: auto;
    }

    .map-sidebar h3 {
        margin: 0; font-size: 1.2rem; font-weight: 700; color: var(--text-color, #fff);
    }
    
    .layer-group {
        background: rgba(255,255,255,0.05);
        border-radius: 12px;
        padding: 12px;
    }
    .layer-group h4 { margin: 0 0 10px 0; font-size: 0.8rem; text-transform: uppercase; color: #94a3b8; }
    .layer-item { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 0.9rem; }
    
    /* Switch */
    .switch { position: relative; display: inline-block; width: 36px; height: 18px; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(255,255,255,0.2); transition: .4s; border-radius: 34px; }
    .slider:before { position: absolute; content: ""; height: 14px; width: 14px; left: 2px; bottom: 2px; background-color: white; transition: .4s; border-radius: 50%; }
    input:checked + .slider { background-color: #38bdf8; }
    input:checked + .slider:before { transform: translateX(18px); }

    .custom-file-upload {
        display: inline-block; padding: 6px 12px; cursor: pointer; border-radius: 6px;
        background: rgba(255,255,255,0.1); color: white; transition: 0.3s;
        border: 2px dashed transparent;
        text-align: center;
        width: 100%;
    }
    .custom-file-upload:hover { background: rgba(255,255,255,0.2); }
    .custom-file-upload.drag-over {
        border-color: #38bdf8;
        background: rgba(56, 189, 248, 0.2);
    }

    /* Modal Elemento (Restringido al área del mapa) */
    .element-modal {
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        width: 100%; height: 100%;
        max-width: none;
        background: var(--surface-color, #1e1e1e); /* Fondo oscuro o claro del sistema */
        color: var(--text-color, white);
        z-index: 9999;
        display: none;
        flex-direction: column;
        opacity: 0;
        transform: translateY(20px);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .element-modal.active {
        display: flex;
        opacity: 1;
        transform: translateY(0);
    }

    /* Variante Modal Pequeño (Para Líneas y Polígonos) */
    .element-modal.modal-compact {
        width: 400px;
        height: auto;
        max-height: 90vh;
        top: 50%;
        left: 50%;
        bottom: auto;
        right: auto;
        border-radius: 20px;
        border: 1px solid var(--border-color, rgba(255, 255, 255, 0.1));
        box-shadow: 0 25px 50px rgba(0,0,0,0.5);
        transform: translate(-50%, -45%);
    }
    .element-modal.modal-compact.active {
        transform: translate(-50%, -50%);
    }
    .element-modal.modal-compact .modal-layout {
        flex-direction: column;
    }
    .element-modal.modal-compact .modal-col-left {
        width: 100%;
        border-right: none;
        max-height: calc(90vh - 70px);
    }

    /* Layout 2 Columnas */
    .modal-layout {
        display: flex; flex-direction: column; flex: 1; overflow-y: auto;
    }
    .modal-col-left { padding: 20px; display: flex; flex-direction: column; gap: 15px; }
    .modal-col-right { padding: 20px; background: rgba(0,0,0,0.2); flex: 1; }
    
    @media (min-width: 768px) {
        .modal-layout { flex-direction: row; overflow: hidden; }
        .modal-col-left { width: 400px; overflow-y: auto; border-right: 1px solid rgba(255,255,255,0.05); }
        .modal-col-right { overflow-y: auto; }
    }

    /* Puertos Grid */
    .ports-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(90px, 1fr)); gap: 15px; margin-top: 20px; }
    .port-card { background: rgba(255,255,255,0.03); border: 2px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 15px 5px; text-align: center; cursor: pointer; transition: 0.2s; position: relative; }
    .port-card:hover { transform: translateY(-2px); background: rgba(255,255,255,0.08); }
    .port-card.disponible { border-color: #4ade80; color: #4ade80; }
    .port-card.ocupado { border-color: #f87171; color: #f87171; }
    .port-card.mantenimiento { border-color: #38bdf8; color: #38bdf8; }
    .port-card.defectuoso { border-color: #fbbf24; color: #fbbf24; }
    .port-number { font-size: 1.5rem; font-weight: bold; margin-bottom: 5px; }
    .port-client { font-size: 0.75rem; color: #e2e8f0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; padding: 0 5px; }

    .tech-field { background: rgba(255,255,255,0.05); border-radius: 8px; padding: 8px 12px; margin-bottom: 10px; display: flex; align-items: center; gap: 10px; }
    .tech-field input, .tech-field select { background: transparent; border: none; color: white; outline: none; width: 100%; }

    .modal-header {
        padding: 16px 20px;
        display: flex; justify-content: space-between; align-items: center;
        border-bottom: 1px solid rgba(255,255,255,0.05);
    }
    .modal-header input {
        background: transparent; border: none; color: white; font-weight: bold; font-size: 1.1rem; width: 100%;
        border-bottom: 1px dashed transparent;
    }
    .modal-header input:focus { outline: none; border-bottom: 1px dashed #38bdf8; }
    
    .modal-desc {
        background: rgba(255,255,255,0.05); border: 1px dashed transparent; color: #cbd5e1; font-size: 0.9rem;
        width: 100%; padding: 10px; border-radius: 8px; resize: none; outline: none; transition: 0.2s;
    }
    .modal-desc:focus { border-color: #38bdf8; background: rgba(0,0,0,0.2); }
    
    .btn-close-modal { background: none; border: none; color: #94a3b8; cursor: pointer; font-size: 1.2rem; }

    .modal-body { padding: 20px; display: flex; flex-direction: column; gap: 15px; }
    
    .carousel {
        height: 200px; background: #000; border-radius: 12px; position: relative; overflow: hidden; display: flex; align-items: center; justify-content: center;
        transition: all 0.2s; border: 2px dashed transparent;
    }
    .carousel.drag-over {
        border-color: #38bdf8;
        background: rgba(56, 189, 248, 0.1);
    }
    .carousel img { max-width: 100%; max-height: 100%; object-fit: contain; }
    .carousel-btn {
        position: absolute; top: 50%; transform: translateY(-50%); background: rgba(0,0,0,0.5); color: white; border: none; padding: 10px; cursor: pointer; border-radius: 50%;
    }
    .carousel-btn.prev { left: 10px; }
    .carousel-btn.next { right: 10px; }
    .no-image { color: #94a3b8; display: flex; flex-direction: column; align-items: center; gap: 10px; }

    .coord-box {
        background: rgba(255,255,255,0.05); padding: 10px; border-radius: 8px; font-size: 0.85rem; display: flex; align-items: center; gap: 8px; color: #cbd5e1;
    }

    .action-bar {
        display: flex; justify-content: space-between; padding: 16px 20px; border-top: 1px solid rgba(255,255,255,0.05); background: rgba(0,0,0,0.2);
    }
    .action-btn {
        background: transparent; border: none; color: #94a3b8; font-size: 1.2rem; cursor: pointer; transition: color 0.2s;
    }
    .action-btn:hover { color: white; }
    .btn-nav { color: #38bdf8; }
    .btn-del { color: #ef4444; }

    #fileUploadInput { display: none; }

    /* Visor de Imágenes (Lightbox) */
    #lightboxOverlay {
        position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
        background: rgba(0, 0, 0, 0.9); backdrop-filter: blur(5px);
        z-index: 9999; display: none; align-items: center; justify-content: center;
        opacity: 0; transition: opacity 0.3s;
    }
    #lightboxOverlay.active { display: flex; opacity: 1; }
    #lightboxImg { max-width: 90vw; max-height: 90vh; border-radius: 8px; box-shadow: 0 0 30px rgba(0,0,0,0.5); }
    #lightboxClose:hover { transform: scale(1.1); }

    /* Style Picker Popover */
    .style-picker {
        position: absolute; bottom: 70px; right: 20px;
        background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(16px);
        border: 1px solid rgba(255,255,255,0.1); border-radius: 16px;
        padding: 16px; width: 260px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        display: none; flex-direction: column; gap: 12px; z-index: 101;
    }
    .style-picker.active { display: flex; }
    .style-section-title { font-size: 0.8rem; color: #94a3b8; text-transform: uppercase; margin-bottom: 6px; }
    
    .color-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px; }
    .color-btn { width: 24px; height: 24px; border-radius: 50%; border: 2px solid transparent; cursor: pointer; transition: transform 0.1s; }
    .color-btn:hover { transform: scale(1.2); }
    .color-btn.active { border-color: white; box-shadow: 0 0 8px currentColor; }
    
    .icon-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 8px; }
    .icon-btn { 
        width: 32px; height: 32px; border-radius: 8px; background: rgba(255,255,255,0.05);
        display: flex; align-items: center; justify-content: center; cursor: pointer;
        color: #cbd5e1; font-size: 1.2rem; transition: all 0.2s; border: 1px solid transparent;
    }
    .icon-btn:hover { background: rgba(255,255,255,0.1); color: white; }
    .icon-btn.active { border-color: #38bdf8; color: #38bdf8; background: rgba(56, 189, 248, 0.1); }

    /* Barra Superior (Buscador y Herramientas) */
    .top-toolbar {
        position: absolute; top: 20px; left: 20px; right: 60px;
        display: flex; gap: 15px; align-items: flex-start; z-index: 10;
        pointer-events: none;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .top-toolbar.sidebar-active {
        left: 360px;
    }
    
    .toolbar-tools {
        display: flex; background: white; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        overflow: hidden; pointer-events: auto;
    }
    
    .tool-btn {
        background: white; border: none; padding: 10px 15px; cursor: pointer; color: #475569;
        font-size: 1.2rem; display: flex; align-items: center; justify-content: center;
        border-right: 1px solid #e2e8f0; transition: all 0.2s;
    }
    .tool-btn:last-child { border-right: none; }
    .tool-btn:hover { background: #f8fafc; color: #0f172a; }
    .tool-btn.active { background: #eff6ff; color: #2563eb; }

    .geocoder-container { flex-grow: 1; max-width: 400px; pointer-events: auto; }
    .mapboxgl-ctrl-geocoder { width: 100% !important; max-width: none !important; border-radius: 8px !important; box-shadow: 0 4px 15px rgba(0,0,0,0.15) !important; }
    
    /* Distancia de Medición */
    .measurement-box {
        position: absolute; bottom: 30px; left: 50%; transform: translateX(-50%);
        background: rgba(15, 23, 42, 0.9); backdrop-filter: blur(8px);
        color: white; padding: 10px 20px; border-radius: 30px; font-weight: bold;
        z-index: 10; display: none; box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    }
    /* Mobile Responsive */
    @media (max-width: 768px) {
        .map-sidebar {
            width: calc(100% - 20px);
            left: 10px;
            bottom: 20px;
            top: auto;
            max-height: 40vh;
            overflow-y: auto;
            transform: translateY(150%);
            opacity: 0;
            pointer-events: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .map-sidebar.sidebar-active {
            transform: translateY(0);
            opacity: 1;
            pointer-events: auto;
        }
        .top-toolbar {
            left: 10px;
            right: 10px;
            top: 10px;
            flex-direction: column;
            align-items: stretch;
            gap: 10px;
        }
        .top-toolbar.sidebar-active {
            left: 10px;
        }
        .mobile-only-tool { display: flex !important; }
        .geocoder-container { max-width: 100%; }
        .toolbar-tools { justify-content: center; }
    }
    
    /* =========================================
       LIGHT THEME OVERRIDES
       ========================================= */
    body:not(.dark-theme) .map-sidebar {
        background: rgba(255, 255, 255, 0.95);
        color: #0f172a;
        border-color: rgba(0,0,0,0.1);
        box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    }
    body:not(.dark-theme) .map-sidebar h3 { color: #0f172a; }
    body:not(.dark-theme) .layer-group { background: rgba(0,0,0,0.03); }
    body:not(.dark-theme) .layer-group h4 { color: #475569; }
    body:not(.dark-theme) .custom-file-upload { background: white; color: #0f172a; border-color: #e2e8f0; }
    body:not(.dark-theme) .custom-file-upload:hover { background: #f8fafc; }
    body:not(.dark-theme) .slider { background-color: #cbd5e1; }
    body:not(.dark-theme) input:checked + .slider { background-color: #0ea5e9; }
    
    body:not(.dark-theme) .element-modal { background: var(--surface-color, #ffffff); color: var(--text-color, #333); }
    body:not(.dark-theme) .modal-header { border-bottom-color: rgba(0,0,0,0.05); }
    body:not(.dark-theme) .modal-header input { color: #0f172a; }
    body:not(.dark-theme) .modal-col-right { background: white; }
    body:not(.dark-theme) .modal-col-left { border-right-color: rgba(0,0,0,0.05); }
    body:not(.dark-theme) .modal-desc { background: white; border-color: rgba(0,0,0,0.1); color: #475569; }
    body:not(.dark-theme) .modal-desc:focus { background: white; }
    body:not(.dark-theme) .coord-box { background: white; border: 1px solid rgba(0,0,0,0.1); color: #475569; }
    body:not(.dark-theme) .tech-field { background: white; border: 1px solid rgba(0,0,0,0.1); }
    body:not(.dark-theme) .tech-field input, body:not(.dark-theme) .tech-field select { color: #0f172a; }
    body:not(.dark-theme) .port-card { background: white; border-color: rgba(0,0,0,0.1); box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
    body:not(.dark-theme) .port-card:hover { background: #f8fafc; }
    body:not(.dark-theme) .port-client { color: #475569; }
    body:not(.dark-theme) .action-bar { background: #f1f5f9; border-top-color: rgba(0,0,0,0.05); }
    body:not(.dark-theme) .action-btn { color: #64748b; }
    body:not(.dark-theme) .action-btn:hover { color: #0f172a; }
    body:not(.dark-theme) .style-picker { background: rgba(255, 255, 255, 0.95); border-color: rgba(0,0,0,0.1); }
    body:not(.dark-theme) .style-section-title { color: #475569; }
    body:not(.dark-theme) .icon-btn { color: #475569; background: #f1f5f9; }
    body:not(.dark-theme) .icon-btn:hover { background: #e2e8f0; color: #0f172a; }
    body:not(.dark-theme) .measurement-box { background: rgba(255,255,255,0.9); color: #0f172a; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
    
    /* Toolbar ya usa fondo blanco, lo ajustamos para modo oscuro */
    body.dark-theme .toolbar-tools { background: #1e293b; border: 1px solid rgba(255,255,255,0.1); }
    body.dark-theme .tool-btn { background: transparent; color: #cbd5e1; border-right-color: rgba(255,255,255,0.1); }
    body.dark-theme .tool-btn:hover { background: rgba(255,255,255,0.05); color: white; }
    body.dark-theme .tool-btn.active { background: rgba(56, 189, 248, 0.2); color: #38bdf8; }

    /* Estilo general para selects dinámicos */
    select.map-select { background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.1); }
    select.map-select option { color: black; }
    body:not(.dark-theme) select.map-select { background: white; color: #0f172a; border-color: rgba(0,0,0,0.1); }
</style>

<div class="map-wrapper">
    <div id="map"></div>
    
    <div class="map-sidebar">
        <h3><i class="ph-fill ph-map-trifold"></i> <?php echo htmlspecialchars($proyecto['nombre']); ?></h3>
        
        <div class="layer-group">
            <h4>Importar</h4>
            <label class="custom-file-upload" id="kmlDropZone">
                <input type="file" id="kmlUpload" class="no-dropzone" accept=".kml" style="display:none;" />
                <i class="ph-bold ph-upload-simple"></i> Subir KML (Guardar en BD)
            </label>
        </div>

        <div class="layer-group">
            <h4>Vista del Mapa</h4>
            <select id="styleSwitcher" class="form-select form-select-sm map-select">
                <option value="mapbox://styles/mapbox/dark-v11">Oscuro Premium</option>
                <option value="mapbox://styles/mapbox/satellite-streets-v12">Satélite</option>
                <option value="mapbox://styles/mapbox/streets-v12">Calles</option>
            </select>
        </div>
    </div>

    <!-- Modal del Elemento -->
    <div class="element-modal" id="elementModal">
        <div class="modal-header">
            <input type="text" id="modalTitle" value="Caja NAP">
            <button class="btn-close-modal" onclick="closeModal()"><i class="ph-bold ph-x"></i></button>
        </div>
        <div class="modal-layout">
            <!-- Izquierda: Detalles Técnicos -->
            <div class="modal-col-left">
                <textarea id="modalDesc" class="modal-desc" rows="2" placeholder="Añadir descripción o notas..."></textarea>
                
                <div class="carousel" id="carousel">
                    <div class="no-image">
                        <i class="ph-bold ph-image" style="font-size: 2rem;"></i>
                        <span>Sin fotos</span>
                    </div>
                </div>
                
                <div class="coord-box">
                    <i class="ph-fill ph-map-pin"></i> <span id="modalCoords">-11.84, -77.11</span>
                </div>
                
                <div id="techPointContainer" style="margin-top: 10px;">
                    <h5 style="color: #94a3b8; font-size: 0.85rem; margin-bottom: 10px;">DATOS TÉCNICOS</h5>
                    <div class="tech-field">
                        <i class="ph-fill ph-plugs" style="color: #38bdf8;"></i>
                        <input type="text" id="techCable" placeholder="Tubo/Hilo Troncal (Ej: Tubo Verde, Hilo Azul)">
                    </div>
                    <div class="tech-field">
                        <i class="ph-fill ph-lightning" style="color: #fbbf24;"></i>
                        <input type="text" id="techDbm" placeholder="Potencia dBm (Ej: -17.5 dBm)">
                    </div>
                    <div class="tech-field">
                        <i class="ph-fill ph-git-branch" style="color: #a78bfa;"></i>
                        <select id="techSplitter">
                            <option value="" style="color:black;">Tipo de Splitter...</option>
                            <option value="1x2" style="color:black;">1x2</option>
                            <option value="1x4" style="color:black;">1x4</option>
                            <option value="1x8" style="color:black;">1x8</option>
                            <option value="1x16" style="color:black;">1x16</option>
                            <option value="Desbalanceado" style="color:black;">Desbalanceado</option>
                        </select>
                    </div>
                </div>

                <div id="techLineContainer" style="margin-top: 10px; display: none;">
                    <h5 style="color: #94a3b8; font-size: 0.85rem; margin-bottom: 10px;">DATOS DEL CABLE</h5>
                    <div class="tech-field">
                        <i class="ph-fill ph-ruler" style="color: #38bdf8;"></i>
                        <input type="text" id="techLength" readonly placeholder="Calculando..." style="background: transparent; color: var(--text-color);">
                    </div>
                </div>

                <div id="techPolygonContainer" style="margin-top: 10px; display: none;">
                    <h5 style="color: #94a3b8; font-size: 0.85rem; margin-bottom: 10px;">DATOS DE LA ZONA</h5>
                    <div class="tech-field">
                        <i class="ph-fill ph-hexagon" style="color: #a78bfa;"></i>
                        <input type="text" id="techArea" readonly placeholder="Calculando..." style="background: transparent; color: var(--text-color);">
                    </div>
                </div>
                
                <div class="action-bar" style="margin-top: auto; border-top: none; padding: 0;">
                    <label for="fileUploadInput" class="action-btn" title="Subir / Tomar Foto">
                        <i class="ph-bold ph-camera"></i>
                    </label>
                    <input type="file" id="fileUploadInput" class="no-dropzone" accept="image/*" capture="environment" style="display:none;">
                    <button class="action-btn" title="Cambiar Color" onclick="toggleStylePicker(event)"><i class="ph-bold ph-palette"></i></button>
                    <button class="action-btn btn-nav" title="Cómo llegar" id="btnNav"><i class="ph-bold ph-navigation-arrow"></i></button>
                    <button class="action-btn btn-del" title="Eliminar" onclick="deleteElement()"><i class="ph-bold ph-trash"></i></button>
                </div>
            </div>
            
            <!-- Derecha: Gestión de Hilos -->
            <div class="modal-col-right" id="portsContainer">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h4 style="margin:0;"><i class="ph-fill ph-graph"></i> Gestión de Hilos</h4>
                    <select id="techCapacidad" class="form-select form-select-sm map-select" style="width: auto;">
                        <option value="0">Sin Hilos</option>
                        <option value="8">Caja de 8 Hilos</option>
                        <option value="16">Caja de 16 Hilos</option>
                        <option value="24">Caja de 24 Hilos</option>
                    </select>
                </div>
                
                <div class="ports-grid" id="portsGrid">
                    <div style="color: #94a3b8; font-size: 0.9rem; grid-column: 1 / -1;">Cargando...</div>
                </div>
            </div>
        </div>

        <!-- Style Picker (Popover) -->
        <div class="style-picker" id="stylePicker">
            <div>
                <div class="style-section-title">Color</div>
                <div class="color-grid" id="colorGrid"></div>
            </div>
            <div>
                <div class="style-section-title">Ícono</div>
                <div class="icon-grid" id="iconGrid"></div>
                <div style="margin-top: 10px;">
                    <label class="btn btn-sm btn-outline-primary w-100" style="cursor: pointer;">
                        <input type="file" class="no-dropzone" style="display: none;" accept="image/png" onchange="uploadIconFile(this.files[0])">
                        <i class="ph-bold ph-upload-simple"></i> Subir PNG Propio
                    </label>
                </div>
            </div>
        </div>
    </div>

    <!-- Barra Superior (Herramientas y Buscador) -->
    <div class="top-toolbar">
        <div class="toolbar-tools">
            <button class="tool-btn" onclick="window.location.href='index.php'" title="Volver a Proyectos" style="background: rgba(56, 189, 248, 0.1); color: #38bdf8;"><i class="ph-bold ph-arrow-left"></i></button>
            <button class="tool-btn" onclick="document.querySelector('.map-sidebar').classList.toggle('sidebar-active'); document.querySelector('.top-toolbar').classList.toggle('sidebar-active');" title="Menú"><i class="ph-bold ph-list"></i></button>
            <button class="tool-btn active" id="tool-hand" title="Mover mapa (Esc)" onclick="setTool('simple_select', this)"><i class="ph-bold ph-hand-palm"></i></button>
            <button class="tool-btn" id="tool-point" title="Añadir Punto" onclick="setTool('draw_point', this)"><i class="ph-bold ph-map-pin-plus"></i></button>
            <button class="tool-btn" id="tool-line" title="Dibujar Línea" onclick="setTool('draw_line_string', this)"><i class="ph-bold ph-trend-up"></i></button>
            <button class="tool-btn" id="tool-polygon" title="Dibujar Polígono" onclick="setTool('draw_polygon', this)"><i class="ph-bold ph-hexagon"></i></button>
            <button class="tool-btn" id="tool-measure" title="Medir Distancia" onclick="toggleMeasure(this)"><i class="ph-bold ph-ruler"></i></button>
        </div>
        <div class="geocoder-container" id="geocoder"></div>
    </div>
    
    <div class="measurement-box" id="measurementBox">0 m</div>
</div>

<!-- Visor de Imágenes a Pantalla Completa -->
<div id="lightboxOverlay" onclick="closeLightbox(event)">
    <button id="lightboxClose" onclick="closeLightbox(event)">&times;</button>
    <img id="lightboxImg" src="" alt="Visor">
</div>

<script>
mapboxgl.accessToken = 'pk.eyJ1IjoidHVyYm8yNjI2IiwiYSI6ImNtcHhkaHdlYTA5bTIycnEwdjU5MGd4bDYifQ.e9wcWZh66hPGgtv7ie_1YA';
const proyectoId = <?php echo $proyecto_id; ?>;

const isSystemDark = document.body.classList.contains('dark-theme');
const initialStyle = isSystemDark ? 'mapbox://styles/mapbox/dark-v11' : 'mapbox://styles/mapbox/streets-v12';

const map = new mapboxgl.Map({
    container: 'map',
    style: initialStyle,
    center: [-77.086, -11.865],
    zoom: 15,
    pitch: 45
});

document.addEventListener("DOMContentLoaded", () => {
    document.getElementById('styleSwitcher').value = initialStyle;
});

const themeObserver = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
        if (mutation.attributeName === 'class') {
            const isNowDark = document.body.classList.contains('dark-theme');
            const newStyle = isNowDark ? 'mapbox://styles/mapbox/dark-v11' : 'mapbox://styles/mapbox/streets-v12';
            
            const currentStyle = document.getElementById('styleSwitcher').value;
            if (currentStyle !== 'mapbox://styles/mapbox/satellite-streets-v12') {
                map.setStyle(newStyle);
                document.getElementById('styleSwitcher').value = newStyle;
            }
        }
    });
});
themeObserver.observe(document.body, { attributes: true });
map.addControl(new mapboxgl.NavigationControl(), 'top-right');

// Inicializar Geocoder (Buscador)
function searchDatabase(query) {
    const matchingFeatures = [];
    if (currentGeoJson && currentGeoJson.features) {
        for (const feature of currentGeoJson.features) {
            if (feature.properties && feature.properties.name) {
                if (feature.properties.name.toLowerCase().includes(query.toLowerCase())) {
                    const center = feature.geometry.type === 'Point' 
                        ? feature.geometry.coordinates 
                        : feature.geometry.coordinates[0];
                        
                    // Copiamos la feature para no ensuciar la original del mapa
                    const geoFeature = Object.assign({}, feature);
                    geoFeature['place_name'] = '💾 ' + feature.properties.name;
                    geoFeature['center'] = center;
                    geoFeature['place_type'] = ['place'];
                    matchingFeatures.push(geoFeature);
                }
            }
        }
    }
    return matchingFeatures;
}

const geocoder = new MapboxGeocoder({
    accessToken: mapboxgl.accessToken,
    mapboxgl: mapboxgl,
    placeholder: 'Buscar cajas NAP, cables o lugares...',
    localGeocoder: searchDatabase,
    marker: false
});
document.getElementById('geocoder').appendChild(geocoder.onAdd(map));

geocoder.on('result', function(e) {
    const f = e.result;
    if (f.properties && f.properties.id) {
        // Es un elemento de nuestra base de datos
        const center = f.geometry.type === 'Point' ? f.geometry.coordinates : f.geometry.coordinates[0];
        openModal(f.properties.id, center, f.properties.name, f.properties.color, f.properties.icono);
    }
});

// Inicializar Mapbox Draw (Herramientas de Dibujo)
const draw = new MapboxDraw({
    displayControlsDefault: false,
    controls: {}, // No usamos los controles por defecto
    styles: [
        {
            'id': 'gl-draw-line',
            'type': 'line',
            'filter': ['all', ['==', '$type', 'LineString'], ['!=', 'mode', 'static']],
            'layout': { 'line-cap': 'round', 'line-join': 'round' },
            'paint': { 'line-color': '#38bdf8', 'line-width': 4 }
        },
        {
            'id': 'gl-draw-point-inactive',
            'type': 'circle',
            'filter': ['all', ['==', '$type', 'Point'], ['==', 'meta', 'feature'], ['!=', 'mode', 'static']],
            'paint': { 'circle-radius': 7, 'circle-color': '#a78bfa' }
        }
    ]
});
map.addControl(draw);

let isMeasuring = false;
let measureGeoJson = { type: 'FeatureCollection', features: [] };
let measureLineId = 'measure-line';
let measurePointsId = 'measure-points';

function setTool(mode, btn) {
    if(isMeasuring) toggleMeasure(); // Apagar medida si estaba activa
    
    document.querySelectorAll('.tool-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    draw.changeMode(mode);
}

function toggleMeasure(btn) {
    isMeasuring = !isMeasuring;
    const box = document.getElementById('measurementBox');
    
    if(isMeasuring) {
        document.querySelectorAll('.tool-btn').forEach(b => b.classList.remove('active'));
        if(btn) btn.classList.add('active');
        
        draw.changeMode('draw_line_string');
        map.getCanvas().style.cursor = 'crosshair';
        box.style.display = 'block';
        box.innerText = '0 m';
    } else {
        draw.trash(); // limpiar dibujo
        draw.changeMode('simple_select');
        map.getCanvas().style.cursor = '';
        box.style.display = 'none';
        document.getElementById('tool-hand').classList.add('active');
        if(btn) btn.classList.remove('active');
    }
}

// Escuchar actualizaciones de dibujo para la medición
map.on('draw.create', handleDrawComplete);
map.on('draw.update', handleDrawComplete);
map.on('draw.render', (e) => {
    if(isMeasuring) {
        const data = draw.getAll();
        if(data.features.length > 0) {
            const line = data.features[0];
            if(line.geometry.coordinates.length > 1) {
                const distance = turf.length(line, {units: 'meters'});
                let text = distance > 1000 ? (distance/1000).toFixed(2) + ' km' : distance.toFixed(0) + ' m';
                document.getElementById('measurementBox').innerText = text;
            }
        }
    }
});

function handleDrawComplete(e) {
    if(isMeasuring) return; // No guardar si estamos midiendo
    
    const feature = e.features[0];
    const tipo = feature.geometry.type;
    const isPoint = tipo === 'Point';
    
    let nombre = 'Nuevo Elemento';
    if (tipo === 'Point') nombre = 'Nueva Caja';
    else if (tipo === 'LineString') nombre = 'Nuevo Cable';
    else if (tipo === 'Polygon') nombre = 'Nueva Zona';

    const formData = new FormData();
    formData.append('action', 'create_element');
    formData.append('proyecto_id', proyectoId);
    formData.append('tipo', tipo);
    formData.append('nombre', nombre);
    formData.append('geojson', JSON.stringify(feature.geometry));
    
    fetch('api.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if(res.success) {
                draw.trash(); // Limpiar el dibujo temporal
                draw.changeMode('simple_select');
                document.querySelectorAll('.tool-btn').forEach(b => b.classList.remove('active'));
                document.getElementById('tool-hand').classList.add('active');
                
                loadMapData(); // Recargar mapa real
                if(isPoint || tipo === 'LineString' || tipo === 'Polygon') {
                    // Abrir el modal inmediatamente con los datos por defecto
                    let center;
                    if (isPoint) center = feature.geometry.coordinates;
                    else if (tipo === 'Polygon') center = feature.geometry.coordinates[0][0];
                    else center = feature.geometry.coordinates[0];
                    openModal(res.id, center, nombre, '#a78bfa', 'ph-map-pin', '', tipo, feature);
                }
            } else {
                alert('Error al guardar el elemento');
            }
        });
}

let currentGeoJson = { type: 'FeatureCollection', features: [] };
let activeElementId = null;
let activeImages = [];
let currentImageIndex = 0;

map.on('load', () => {
    // Cerrar el menú principal de TURBOSAAS en móviles si se hace clic en el mapa
    map.on('click', () => {
        const mainSidebar = document.querySelector('.sidebar');
        if (mainSidebar && mainSidebar.classList.contains('active')) {
            mainSidebar.classList.remove('active');
        }
    });

    // Observar cambios de tamaño en el contenedor (ej: al colapsar el menú lateral)
    new ResizeObserver(() => {
        map.resize();
    }).observe(document.getElementById('map'));

    loadMapData();
});

function loadMapData() {
    fetch(`api.php?action=get_elements&proyecto_id=${proyectoId}`)
        .then(r => r.json())
        .then(res => {
            if(res.success) {
                currentGeoJson = res.geojson;
                updateMapSources();
                fitMapToBounds();
            }
        });
}

function updateMapSources() {
    if (!map.getSource('network-data')) {
        map.addSource('network-data', { type: 'geojson', data: currentGeoJson });
        
        map.addLayer({
            'id': 'cables-glow',
            'type': 'line',
            'source': 'network-data',
            'filter': ['==', ['geometry-type'], 'LineString'],
            'layout': { 'line-join': 'round', 'line-cap': 'round' },
            'paint': { 'line-color': ['get', 'color'], 'line-width': 6, 'line-blur': 4, 'line-opacity': 0.6 }
        });
        
        map.addLayer({
            'id': 'cables',
            'type': 'line',
            'source': 'network-data',
            'filter': ['==', ['geometry-type'], 'LineString'],
            'layout': { 'line-join': 'round', 'line-cap': 'round' },
            'paint': { 'line-color': ['get', 'color'], 'line-width': 2 }
        });
        
        // Polígonos
        map.addLayer({
            'id': 'polygons-fill',
            'type': 'fill',
            'source': 'network-data',
            'filter': ['==', ['geometry-type'], 'Polygon'],
            'paint': { 'fill-color': ['get', 'color'], 'fill-opacity': 0.3 }
        });
        map.addLayer({
            'id': 'polygons-outline',
            'type': 'line',
            'source': 'network-data',
            'filter': ['==', ['geometry-type'], 'Polygon'],
            'paint': { 'line-color': ['get', 'color'], 'line-width': 2 }
        });
        
        // Detectar click en líneas
        map.on('click', 'cables', (e) => {
            if (isMeasuring || (typeof draw !== 'undefined' && draw.getMode() !== 'simple_select')) return;
            const clickedF = e.features[0];
            const f = currentGeoJson.features.find(x => x.properties.id == clickedF.properties.id) || clickedF;
            openModal(f.properties.id, f.geometry.coordinates[0], f.properties.name, f.properties.color, f.properties.icono, f.properties.descripcion, 'LineString', f);
        });
        map.on('mouseenter', 'cables', () => map.getCanvas().style.cursor = 'pointer');
        map.on('mouseleave', 'cables', () => map.getCanvas().style.cursor = '');

        // Detectar click en polígonos
        map.on('click', 'polygons-fill', (e) => {
            if (isMeasuring || (typeof draw !== 'undefined' && draw.getMode() !== 'simple_select')) return;
            const clickedF = e.features[0];
            const f = currentGeoJson.features.find(x => x.properties.id == clickedF.properties.id) || clickedF;
            openModal(f.properties.id, f.geometry.coordinates[0][0], f.properties.name, f.properties.color, f.properties.icono, f.properties.descripcion, 'Polygon', f);
        });
        map.on('mouseenter', 'polygons-fill', () => map.getCanvas().style.cursor = 'pointer');
        map.on('mouseleave', 'polygons-fill', () => map.getCanvas().style.cursor = '');

        // Terminar de dibujar/medir al hacer clic derecho en cualquier parte
        map.on('contextmenu', (e) => {
            if (typeof draw !== 'undefined') {
                const mode = draw.getMode();
                if (mode === 'draw_line_string' || mode === 'draw_polygon' || mode === 'draw_point') {
                    e.preventDefault();
                    draw.changeMode('simple_select');
                }
            }
        });
    } else {
        map.getSource('network-data').setData(currentGeoJson);
    }
    
    renderMarkers();
}

let currentMarkers = [];

function renderMarkers() {
    // Limpiar marcadores antiguos
    currentMarkers.forEach(m => m.remove());
    currentMarkers = [];
    
    currentGeoJson.features.forEach(f => {
        if (f.geometry.type === 'Point') {
            const el = document.createElement('div');
            el.className = 'custom-marker';
            el.style.cursor = 'pointer';
            
            const icono = f.properties.icono || 'ph-map-pin';
            const color = f.properties.color || '#a78bfa';
            
            if (icono.includes('/')) {
                // PNG
                el.innerHTML = `<img src="../../${icono}" style="width: 30px; height: 30px; object-fit: contain; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.5));">`;
            } else {
                // Phosphor
                el.innerHTML = `<i class="ph-fill ${icono}" style="font-size: 24px; color: ${color}; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.5));"></i>`;
            }
            
            const openElementModal = (e) => {
                e.stopPropagation();
                if (e.type === 'touchend') e.preventDefault(); // Evitar doble evento (ghost click)
                if (isMeasuring || (typeof draw !== 'undefined' && draw.getMode() !== 'simple_select')) return;
                openModal(f.properties.id, f.geometry.coordinates, f.properties.name, f.properties.color, f.properties.icono, f.properties.descripcion);
            };
            
            el.addEventListener('click', openElementModal);
            el.addEventListener('touchend', openElementModal);
            
            const marker = new mapboxgl.Marker({element: el})
                .setLngLat(f.geometry.coordinates)
                .addTo(map);
                
            currentMarkers.push(marker);
        }
    });
}

function fitMapToBounds() {
    if(currentGeoJson.features.length === 0) return;
    const bounds = new mapboxgl.LngLatBounds();
    currentGeoJson.features.forEach(f => {
        if(f.geometry.type === 'Point') bounds.extend(f.geometry.coordinates);
        else if (f.geometry.type === 'LineString') f.geometry.coordinates.forEach(c => bounds.extend(c));
        else if (f.geometry.type === 'Polygon') f.geometry.coordinates[0].forEach(c => bounds.extend(c));
    });
    map.fitBounds(bounds, { padding: 50 });
}

document.getElementById('styleSwitcher').addEventListener('change', function(e) {
    map.setStyle(this.value);
});
map.on('style.load', () => {
    if(currentGeoJson.features.length > 0) updateMapSources();
});

// Importar KML
function processKmlFile(file) {
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function(event) {
        const kmlText = event.target.result;
        const parser = new DOMParser();
        const kmlDom = parser.parseFromString(kmlText, 'text/xml');
        const geojson = toGeoJSON.kml(kmlDom);
        
        // Enviar al server
        const formData = new FormData();
        formData.append('action', 'import_geojson');
        formData.append('proyecto_id', proyectoId);
        formData.append('features', JSON.stringify(geojson.features));

        fetch('api.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => {
                if(res.success) {
                    alert('KML Importado Exitosamente');
                    loadMapData();
                } else {
                    alert('Error importando: ' + res.message);
                }
            });
    };
    reader.readAsText(file);
}

document.getElementById('kmlUpload').addEventListener('change', function(e) {
    processKmlFile(e.target.files[0]);
    this.value = ''; // Reset input
});

const kmlDropZone = document.getElementById('kmlDropZone');
kmlDropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    kmlDropZone.classList.add('drag-over');
});
kmlDropZone.addEventListener('dragleave', (e) => {
    e.preventDefault();
    kmlDropZone.classList.remove('drag-over');
});
kmlDropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    kmlDropZone.classList.remove('drag-over');
    if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
        processKmlFile(e.dataTransfer.files[0]);
    }
});

// MODAL LOGIC
let activePuertos = [];

function openModal(id, coords, name, color, icono, descripcion, type = 'Point', feature = null) {
    activeElementId = id;
    activeColor = color || '#a78bfa';
    activeIcon = icono || 'ph-map-pin';
    updatePickerUI();
    
    const modalEl = document.getElementById('elementModal');
    if (type !== 'Point') {
        modalEl.classList.add('modal-compact');
    } else {
        modalEl.classList.remove('modal-compact');
    }
    
    document.getElementById('modalTitle').value = name;
    document.getElementById('modalDesc').value = descripcion || '';
    document.getElementById('modalCoords').innerText = `${coords[1].toFixed(5)}, ${coords[0].toFixed(5)}`;
    document.getElementById('btnNav').onclick = () => window.open(`https://www.google.com/maps/dir/?api=1&destination=${coords[1]},${coords[0]}`);
    
    // Configurar visibilidad de campos por tipo
    const techPoint = document.getElementById('techPointContainer');
    const techLine = document.getElementById('techLineContainer');
    const techPoly = document.getElementById('techPolygonContainer');
    const portsCont = document.getElementById('portsContainer');
    
    if (techPoint) techPoint.style.display = (type === 'Point') ? 'block' : 'none';
    if (portsCont) portsCont.style.display = (type === 'Point') ? 'block' : 'none';
    
    if (techLine) {
        if (type === 'LineString' && feature) {
            techLine.style.display = 'block';
            let len = turf.length(feature, {units: 'meters'});
            document.getElementById('techLength').value = len > 1000 ? (len/1000).toFixed(2) + ' km' : len.toFixed(2) + ' m';
        } else {
            techLine.style.display = 'none';
        }
    }
    
    if (techPoly) {
        if (type === 'Polygon' && feature) {
            techPoly.style.display = 'block';
            let area = turf.area(feature);
            document.getElementById('techArea').value = area > 1000000 ? (area/1000000).toFixed(2) + ' km²' : area.toFixed(2) + ' m²';
        } else {
            techPoly.style.display = 'none';
        }
    }
    
    // Reset technical fields
    document.getElementById('techCable').value = '';
    document.getElementById('techDbm').value = '';
    document.getElementById('techSplitter').value = '';
    document.getElementById('techCapacidad').value = '0';
    activePuertos = [];
    renderPuertos();
    
    document.getElementById('elementModal').classList.add('active');
    
    fetch(`api.php?action=get_element_details&id=${id}`)
        .then(r => r.json())
        .then(res => {
            if(res.success) {
                // Fotos
                if(res.data.imagenes) {
                    activeImages = res.data.imagenes;
                    currentImageIndex = 0;
                    renderCarousel();
                }
                
                // Datos Técnicos
                document.getElementById('techCable').value = res.data.cable_origen || '';
                document.getElementById('techDbm').value = res.data.potencia_dbm || '';
                document.getElementById('techSplitter').value = res.data.splitter_tipo || '';
                document.getElementById('techCapacidad').value = res.data.capacidad_puertos || '0';
                
                // Puertos
                if (res.data.puertos) {
                    activePuertos = res.data.puertos;
                }
                renderPuertos();
            }
        });
}

function closeModal() {
    document.getElementById('elementModal').classList.remove('active');
    
    const newTitle = document.getElementById('modalTitle').value;
    const newDesc = document.getElementById('modalDesc').value;
    const capacidad = document.getElementById('techCapacidad').value;
    const cable = document.getElementById('techCable').value;
    const dbm = document.getElementById('techDbm').value;
    const splitter = document.getElementById('techSplitter').value;
    
    const formData = new FormData();
    formData.append('action', 'update_element');
    formData.append('id', activeElementId);
    formData.append('nombre', newTitle);
    formData.append('descripcion', newDesc);
    formData.append('color', activeColor);
    formData.append('icono', activeIcon);
    formData.append('capacidad_puertos', capacidad);
    formData.append('cable_origen', cable);
    formData.append('potencia_dbm', dbm);
    formData.append('splitter_tipo', splitter);
    
    fetch('api.php', {method: 'POST', body: formData}).then(() => loadMapData());
}

// LÓGICA DE PUERTOS
document.getElementById('techCapacidad').addEventListener('change', () => {
    // Si cambia la capacidad, guardamos para que el backend genere los hilos y recargamos el modal
    closeModal();
    setTimeout(() => {
        // Encontrar el elemento en currentGeoJson para reabrirlo
        const f = currentGeoJson.features.find(x => x.properties.id == activeElementId);
        if (f) {
            let center;
            if (f.geometry.type === 'Point') center = f.geometry.coordinates;
            else if (f.geometry.type === 'Polygon') center = f.geometry.coordinates[0][0];
            else center = f.geometry.coordinates[0];
            openModal(f.properties.id, center, f.properties.name, f.properties.color, f.properties.icono, f.properties.descripcion, f.geometry.type, f);
        }
    }, 500);
});

function renderPuertos() {
    const grid = document.getElementById('portsGrid');
    const capacidad = parseInt(document.getElementById('techCapacidad').value);
    
    if (capacidad === 0 || activePuertos.length === 0) {
        grid.innerHTML = '<div style="color: #94a3b8; font-size: 0.9rem; grid-column: 1 / -1;">Seleccione una capacidad para generar los hilos.</div>';
        return;
    }
    
    let html = '';
    activePuertos.forEach(p => {
        const estadoClase = p.estado.toLowerCase();
        let icono = 'ph-check-circle';
        if (estadoClase === 'ocupado') icono = 'ph-user';
        if (estadoClase === 'mantenimiento') icono = 'ph-wrench';
        if (estadoClase === 'defectuoso') icono = 'ph-warning';
        
        html += `
        <div class="port-card ${estadoClase}" onclick="gestionarPuerto(${p.id}, '${p.estado}', '${p.cliente_nombre || ''}')">
            <div class="port-number">#${p.numero_puerto}</div>
            <i class="ph-fill ${icono}" style="font-size: 1.5rem; margin-bottom: 5px;"></i>
            <div class="port-client">${p.cliente_nombre || p.estado}</div>
        </div>`;
    });
    grid.innerHTML = html;
}

window.gestionarPuerto = function(puertoId, estadoActual, clienteActual) {
    const nuevoEstado = prompt(`Estado actual: ${estadoActual}\nCambiar a (Escribe: Disponible, Ocupado, Mantenimiento o Defectuoso):`, estadoActual);
    if (!nuevoEstado) return;
    
    let nuevoCliente = clienteActual;
    if (nuevoEstado.toLowerCase() === 'ocupado') {
        nuevoCliente = prompt("Nombre del Cliente:", clienteActual);
        if (nuevoCliente === null) return;
    } else {
        nuevoCliente = ''; // Si no está ocupado, limpiamos el cliente
    }
    
    const formData = new FormData();
    formData.append('action', 'update_puerto');
    formData.append('puerto_id', puertoId);
    formData.append('estado', nuevoEstado);
    formData.append('cliente_nombre', nuevoCliente);
    
    fetch('api.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if(res.success) {
                // Actualizar array local y re-renderizar
                const p = activePuertos.find(x => x.id == puertoId);
                if (p) {
                    p.estado = nuevoEstado;
                    p.cliente_nombre = nuevoCliente;
                }
                renderPuertos();
            } else {
                alert('Error al actualizar el hilo.');
            }
        });
}

window.uploadIconFile = function(file) {
    if(!file || !activeElementId) return;
    const formData = new FormData();
    formData.append('action', 'upload_icon');
    formData.append('file', file);
    
    fetch('api.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if(res.success) {
                activeIcon = res.ruta; // Asignar la ruta del PNG
                closeModal(); // Guardar todo y recargar marcadores
                document.getElementById('stylePicker').classList.remove('active');
            } else {
                alert(res.message);
            }
        });
}

function renderCarousel() {
    const c = document.getElementById('carousel');
    if(activeImages.length === 0) {
        c.innerHTML = '<div class="no-image"><i class="ph-bold ph-image" style="font-size: 2rem;"></i><span>Sin fotos</span></div>';
        return;
    }
    const img = activeImages[currentImageIndex];
    let html = `<img src="../../${img.ruta}" alt="Foto" onclick="openLightbox('../../${img.ruta}')" style="cursor:pointer;">`;
    if(activeImages.length > 1) {
        html += `<button class="carousel-btn prev" onclick="changeImg(-1)"><i class="ph-bold ph-caret-left"></i></button>`;
        html += `<button class="carousel-btn next" onclick="changeImg(1)"><i class="ph-bold ph-caret-right"></i></button>`;
        html += `<div style="position:absolute; bottom:5px; background:rgba(0,0,0,0.5); padding:2px 8px; border-radius:10px; font-size:0.8rem;">${currentImageIndex+1} / ${activeImages.length}</div>`;
    }
    c.innerHTML = html;
}

function openLightbox(url) {
    document.getElementById('lightboxImg').src = url;
    document.getElementById('lightboxOverlay').classList.add('active');
}

function closeLightbox(e) {
    if(e.target.id === 'lightboxOverlay' || e.target.id === 'lightboxClose') {
        document.getElementById('lightboxOverlay').classList.remove('active');
        document.getElementById('lightboxImg').src = '';
    }
}

window.changeImg = function(dir) {
    currentImageIndex += dir;
    if(currentImageIndex < 0) currentImageIndex = activeImages.length - 1;
    if(currentImageIndex >= activeImages.length) currentImageIndex = 0;
    renderCarousel();
}

function uploadFile(file) {
    if(!file || !activeElementId) return;

    const formData = new FormData();
    formData.append('action', 'upload_image');
    formData.append('elemento_id', activeElementId);
    formData.append('file', file);

    const c = document.getElementById('carousel');
    c.innerHTML = '<div class="no-image"><i class="ph ph-spinner ph-spin" style="font-size: 2rem;"></i><span>Subiendo...</span></div>';

    fetch('api.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if(res.success) {
                activeImages.unshift({ ruta: res.ruta });
                currentImageIndex = 0;
                renderCarousel();
            } else {
                alert('Error al subir: ' + res.message);
                renderCarousel();
            }
        });
}

// Subir Foto por Input
document.getElementById('fileUploadInput').addEventListener('change', function(e) {
    uploadFile(e.target.files[0]);
});

// Drag and Drop en el Carrusel
const carouselEl = document.getElementById('carousel');
carouselEl.addEventListener('dragover', (e) => {
    e.preventDefault();
    carouselEl.classList.add('drag-over');
});
carouselEl.addEventListener('dragleave', (e) => {
    e.preventDefault();
    carouselEl.classList.remove('drag-over');
});
carouselEl.addEventListener('drop', (e) => {
    e.preventDefault();
    carouselEl.classList.remove('drag-over');
    if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
        uploadFile(e.dataTransfer.files[0]);
    }
});

function deleteElement() {
    if(confirm('¿Seguro que deseas eliminar este elemento del mapa?')) {
        const formData = new FormData();
        formData.append('action', 'delete_element');
        formData.append('id', activeElementId);
        fetch('api.php', { method: 'POST', body: formData })
            .then(() => {
                closeModal();
                loadMapData();
            });
    }
}

// STYLE PICKER LOGIC
const paletteColors = [
    '#f87171', '#fb923c', '#fbbf24', '#facc15', '#a3e635', '#4ade80', '#34d399',
    '#2dd4bf', '#38bdf8', '#60a5fa', '#818cf8', '#a78bfa', '#e879f9', '#f472b6'
];
const paletteIcons = [
    'ph-map-pin', 'ph-wifi-high', 'ph-broadcast', 'ph-lightning', 'ph-camera', 'ph-house',
    'ph-buildings', 'ph-storefront', 'ph-tree', 'ph-warning-circle', 'ph-star', 'ph-check-circle'
];

let activeColor = '#a78bfa';
let activeIcon = 'ph-map-pin';

function initStylePicker() {
    const cg = document.getElementById('colorGrid');
    cg.innerHTML = paletteColors.map(c => `<div class="color-btn" style="background-color: ${c}" onclick="setStyle('color', '${c}')"></div>`).join('');
    
    const ig = document.getElementById('iconGrid');
    ig.innerHTML = paletteIcons.map(i => `<div class="icon-btn" onclick="setStyle('icon', '${i}')"><i class="ph-fill ${i}"></i></div>`).join('');
}

function toggleStylePicker(e) {
    e.stopPropagation();
    document.getElementById('stylePicker').classList.toggle('active');
    updatePickerUI();
}

function setStyle(type, value) {
    if (type === 'color') activeColor = value;
    if (type === 'icon') activeIcon = value;
    
    updatePickerUI();
    
    // Save to server
    const newTitle = document.getElementById('modalTitle').value;
    const formData = new FormData();
    formData.append('action', 'update_element');
    formData.append('id', activeElementId);
    formData.append('nombre', newTitle);
    formData.append('color', activeColor);
    formData.append('icono', activeIcon);
    fetch('api.php', {method: 'POST', body: formData}).then(() => loadMapData());
}

function updatePickerUI() {
    document.querySelectorAll('.color-btn').forEach(btn => {
        btn.classList.remove('active');
        if(btn.style.backgroundColor === activeColor || btn.style.backgroundColor === hexToRgb(activeColor)) {
            btn.classList.add('active');
        }
    });
    document.querySelectorAll('.icon-btn').forEach(btn => {
        btn.classList.remove('active');
        if(btn.innerHTML.includes(activeIcon)) {
            btn.classList.add('active');
        }
    });
}

function hexToRgb(hex) {
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result ? `rgb(${parseInt(result[1], 16)}, ${parseInt(result[2], 16)}, ${parseInt(result[3], 16)})` : hex;
}

document.addEventListener('click', (e) => {
    const picker = document.getElementById('stylePicker');
    if (picker.classList.contains('active') && !picker.contains(e.target)) {
        picker.classList.remove('active');
    }
});

// Initialize picker
initStylePicker();
</script>

<?php include '../../includes/footer.php'; ?>
