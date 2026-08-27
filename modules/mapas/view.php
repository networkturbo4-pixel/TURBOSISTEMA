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

<!-- Google Maps API -->
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBNXLdtgdStVUGqNeDFdaHRTpCjaVHF6RE&libraries=drawing,places,geometry,elevation,marker"></script>
<!-- Turf.js para mediciÃƒÂ³n de distancias y polÃƒÂ­gonos -->
<script src="https://unpkg.com/@turf/turf@6/turf.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/togeojson/0.16.0/togeojson.min.js"></script>

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

    /* Modal Elemento (Restringido al ÃƒÂ¡rea del mapa) */
    .element-modal {
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        width: 100%; height: 100%;
        max-width: none;
        background: rgba(15, 23, 42, 0.95); /* Modern deep background */
        backdrop-filter: blur(15px);
        -webkit-backdrop-filter: blur(15px);
        color: var(--text-color, white);
        z-index: 9999;
        display: none;
        flex-direction: column;
        opacity: 0;
        transform: translateY(20px) scale(0.98);
        transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .element-modal.active {
        display: flex;
        opacity: 1;
        transform: translateY(0) scale(1);
    }

    /* Variante Modal PequeÃƒÂ±o (Para LÃƒÂ­neas y PolÃƒÂ­gonos) */
    .element-modal.modal-compact {
        width: 420px;
        height: auto;
        max-height: 90vh;
        top: 50%;
        left: 50%;
        bottom: auto;
        right: auto;
        border-radius: 24px;
        border: 1px solid rgba(255, 255, 255, 0.08);
        box-shadow: 0 30px 60px rgba(0,0,0,0.6);
        transform: translate(-50%, -40%) scale(0.95);
    }
    .element-modal.modal-compact.active {
        transform: translate(-50%, -50%) scale(1);
    }
    .element-modal.modal-compact .modal-layout {
        flex-direction: column;
    }
    .element-modal.modal-compact .modal-col-left {
        width: 100%;
        border-right: none;
        max-height: calc(90vh - 80px);
    }

    /* Layout 2 Columnas */
    .modal-layout {
        display: flex; flex-direction: column; flex: 1; overflow-y: auto;
    }
    .modal-col-left { padding: 24px; display: flex; flex-direction: column; gap: 18px; }
    .modal-col-right { padding: 24px; background: rgba(0,0,0,0.15); flex: 1; box-shadow: inset 1px 0 0 rgba(255,255,255,0.05); }
    
    @media (min-width: 768px) {
        .modal-layout { flex-direction: row; overflow: hidden; }
        .modal-col-left { width: 420px; overflow-y: auto; }
        .modal-col-right { overflow-y: auto; }
    }

    /* Puertos Grid */
    .ports-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(95px, 1fr)); gap: 12px; margin-top: 20px; }
    .port-card { 
        background: rgba(255,255,255,0.02); 
        border: 1px solid rgba(255,255,255,0.05); 
        border-radius: 14px; 
        padding: 16px 8px; 
        text-align: center; 
        cursor: pointer; 
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); 
        position: relative; 
    }
    .port-card:hover { transform: translateY(-4px) scale(1.02); background: rgba(255,255,255,0.05); box-shadow: 0 10px 20px rgba(0,0,0,0.2); }
    .port-card.disponible { border-color: rgba(74, 222, 128, 0.3); color: #4ade80; background: rgba(74, 222, 128, 0.05); }
    .port-card.ocupado { border-color: rgba(248, 113, 113, 0.3); color: #f87171; background: rgba(248, 113, 113, 0.05); }
    .port-card.mantenimiento { border-color: rgba(56, 189, 248, 0.3); color: #38bdf8; background: rgba(56, 189, 248, 0.05); }
    .port-card.defectuoso { border-color: rgba(251, 191, 36, 0.3); color: #fbbf24; background: rgba(251, 191, 36, 0.05); }
    .port-number { font-size: 1.4rem; font-weight: 800; margin-bottom: 6px; letter-spacing: -0.5px; }
    .port-client { font-size: 0.75rem; color: #cbd5e1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; padding: 0 5px; font-weight: 500; }

    .tech-field { 
        background: rgba(0,0,0,0.2); 
        border: 1px solid rgba(255,255,255,0.05); 
        border-radius: 12px; 
        padding: 10px 16px; 
        margin-bottom: 8px; 
        display: flex; align-items: center; gap: 12px; 
        transition: all 0.2s;
    }
    .tech-field:focus-within { border-color: #38bdf8; background: rgba(0,0,0,0.3); box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.1); }
    .tech-field input, .tech-field select { background: transparent; border: none; color: white; outline: none; width: 100%; font-size: 0.95rem; }
    .tech-field i { font-size: 1.2rem; }

    .modal-header {
        padding: 20px 24px;
        display: flex; justify-content: space-between; align-items: center;
        border-bottom: 1px solid rgba(255,255,255,0.05);
    }
    .modal-header input {
        background: transparent; border: none; color: white; font-weight: 800; font-size: 1.25rem; width: 100%;
        border-bottom: 1px dashed transparent; padding-bottom: 2px;
    }
    .modal-header input:focus { outline: none; border-bottom: 1px dashed #38bdf8; }
    
    .modal-desc {
        background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.05); color: #e2e8f0; font-size: 0.95rem;
        width: 100%; padding: 12px 16px; border-radius: 12px; resize: none; outline: none; transition: all 0.2s;
    }
    .modal-desc:focus { border-color: #38bdf8; background: rgba(0,0,0,0.3); box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.1); }
    
    .btn-close-modal { 
        background: rgba(255,255,255,0.05); border: none; color: #94a3b8; cursor: pointer; font-size: 1.2rem; 
        width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
        transition: all 0.2s;
    }
    .btn-close-modal:hover { background: rgba(255,255,255,0.1); color: white; transform: rotate(90deg); }

    .carousel {
        height: 220px; background: rgba(0,0,0,0.3); border-radius: 16px; position: relative; overflow: hidden; display: flex; align-items: center; justify-content: center;
        transition: all 0.2s; border: 1px solid rgba(255,255,255,0.05);
    }
    .carousel.drag-over {
        border-color: #38bdf8;
        background: rgba(56, 189, 248, 0.1);
    }
    .carousel img { max-width: 100%; max-height: 100%; object-fit: contain; }
    .carousel-btn {
        position: absolute; top: 50%; transform: translateY(-50%); background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); color: white; border: none; padding: 10px; cursor: pointer; border-radius: 50%; transition: all 0.2s;
    }
    .carousel-btn:hover { background: rgba(0,0,0,0.8); transform: translateY(-50%) scale(1.1); }
    .carousel-btn.prev { left: 10px; }
    .carousel-btn.next { right: 10px; }
    .no-image { color: #94a3b8; display: flex; flex-direction: column; align-items: center; gap: 10px; font-weight: 500; }

    .coord-box {
        background: rgba(0,0,0,0.2); padding: 8px 16px; border-radius: 50px; font-size: 0.85rem; display: flex; align-items: center; gap: 8px; color: #cbd5e1; border: 1px solid rgba(255,255,255,0.05); width: fit-content; font-family: monospace;
    }
    .btn-whatsapp-share {
        background: rgba(37, 211, 102, 0.1); color: #25D366; border: 1px solid rgba(37, 211, 102, 0.3);
        width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
        cursor: pointer; transition: all 0.2s; font-size: 1.1rem;
    }
    .btn-whatsapp-share:hover { background: rgba(37, 211, 102, 0.2); transform: scale(1.1); box-shadow: 0 4px 10px rgba(37, 211, 102, 0.2); }

    .action-bar {
        display: flex; justify-content: space-between; padding: 16px 0 0 0; border-top: 1px solid rgba(255,255,255,0.05); background: transparent; margin-top: 10px;
    }
    .action-btn {
        background: rgba(255,255,255,0.05); border: none; color: #94a3b8; font-size: 1.2rem; cursor: pointer; transition: all 0.2s;
        width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
    }
    .action-btn:hover { color: white; background: rgba(255,255,255,0.1); transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
    .btn-nav { color: #38bdf8; background: rgba(56, 189, 248, 0.1); }
    .btn-nav:hover { background: rgba(56, 189, 248, 0.2); color: #38bdf8; }
    .btn-del { color: #ef4444; background: rgba(239, 68, 68, 0.1); }
    .btn-del:hover { background: rgba(239, 68, 68, 0.2); color: #ef4444; }

    #fileUploadInput { display: none; }

    /* Visor de ImÃƒÂ¡genes (Lightbox) */
    #lightboxOverlay {
        position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
        background: rgba(0, 0, 0, 0.95); backdrop-filter: blur(10px);
        z-index: 9999; display: none; align-items: center; justify-content: center;
        opacity: 0; transition: opacity 0.3s; overflow: hidden;
    }
    #lightboxOverlay.active { display: flex; opacity: 1; }
    
    #lightboxImgContainer {
        width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;
        cursor: grab;
    }
    #lightboxImgContainer:active { cursor: grabbing; }
    
    #lightboxImg {
        max-width: 90vw; max-height: 90vh; border-radius: 8px; box-shadow: 0 0 30px rgba(0,0,0,0.5);
        transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1); transform-origin: center center;
        user-select: none; -webkit-user-drag: none;
    }
    
    .lb-btn {
        position: absolute; background: rgba(255,255,255,0.1); border: none; color: white;
        border-radius: 50%; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;
        cursor: pointer; font-size: 1.5rem; transition: all 0.2s; z-index: 10000;
        backdrop-filter: blur(5px);
    }
    .lb-btn:hover { background: rgba(255,255,255,0.2); transform: scale(1.1); }
    .lb-close { top: 20px; right: 20px; font-size: 2rem; background: rgba(239, 68, 68, 0.8); }
    .lb-close:hover { background: rgba(239, 68, 68, 1); }
    .lb-prev { left: 20px; top: 50%; transform: translateY(-50%); }
    .lb-next { right: 20px; top: 50%; transform: translateY(-50%); }
    .lb-prev:hover, .lb-next:hover { transform: translateY(-50%) scale(1.1); }
    
    .lb-zoom-controls {
        position: absolute; bottom: 30px; left: 50%; transform: translateX(-50%);
        background: rgba(0,0,0,0.6); padding: 10px 20px; border-radius: 50px;
        display: flex; gap: 15px; align-items: center; z-index: 10000; border: 1px solid rgba(255,255,255,0.1);
        backdrop-filter: blur(5px);
    }
    .lb-btn-small { background: transparent; border: none; color: white; cursor: pointer; font-size: 1.5rem; transition: color 0.2s; display: flex; align-items: center; justify-content: center; }
    .lb-btn-small:hover { color: #38bdf8; }
    #lbZoomLevel { color: white; font-size: 0.9rem; min-width: 45px; text-align: center; }

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
        position: absolute; top: 20px; left: 50%; transform: translateX(-50%);
        display: flex; gap: 8px; align-items: center; z-index: 10;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        background: rgba(15, 23, 42, 0.9);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        padding: 6px 10px;
        border-radius: 50px;
        border: 1px solid rgba(255, 107, 0, 0.35);
        box-shadow: 0 12px 35px rgba(0, 0, 0, 0.5), 0 0 20px rgba(255, 107, 0, 0.2);
        pointer-events: auto;
    }
    
    .toolbar-tools {
        display: flex; background: transparent; gap: 4px;
        box-shadow: none; border-radius: 0; align-items: center;
    }
    
    .tool-btn {
        background: transparent; border: none; padding: 10px 14px; cursor: pointer; color: #cbd5e1;
        font-size: 1.2rem; display: flex; align-items: center; justify-content: center;
        border-radius: 50px; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .tool-btn:hover { background: rgba(255, 107, 0, 0.15); color: #ff7a00; transform: translateY(-1px); }
    .tool-btn.active { background: linear-gradient(135deg, #ff6b00 0%, #ff2a5f 100%); color: #fff; box-shadow: 0 4px 14px rgba(255, 107, 0, 0.45); }
    
    .tool-btn-back { background: rgba(255, 107, 0, 0.15); color: #ff7a00; }
    .tool-btn-back:hover { background: rgba(255, 107, 0, 0.25); color: #ff6b00; }

    .toolbar-divider { width: 1px; height: 24px; background: rgba(255, 107, 0, 0.25); margin: 0 4px; align-self: center; }

    .geocoder-container { flex-grow: 1; max-width: 400px; display: flex; align-items: center; margin-left: 5px; margin-right: 5px; }
    .geocoder-container input {
        background: rgba(0, 0, 0, 0.3) !important;
        color: white !important;
        border: 1px solid rgba(255, 255, 255, 0.12) !important;
        border-radius: 30px !important;
        padding: 10px 18px !important;
        box-shadow: none !important;
        outline: none !important;
        width: 250px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .geocoder-container input:focus {
        border-color: #ff6b00 !important;
        background: rgba(0, 0, 0, 0.5) !important;
        width: 320px;
        box-shadow: 0 0 0 3px rgba(255, 107, 0, 0.2), 0 0 14px rgba(255, 107, 0, 0.25) !important;
    }
    .geocoder-container input::placeholder { color: #94a3b8 !important; }
    
    /* Distancia de MediciÃƒÂ³n */
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
            left: 50%;
            top: 10px;
            width: calc(100% - 20px);
            flex-direction: column;
            border-radius: 12px;
            padding: 10px;
            align-items: stretch;
            gap: 10px;
        }
        .geocoder-container input { width: 100% !important; }
        .geocoder-container input:focus { width: 100% !important; }
        .mobile-only-tool { display: flex !important; }
        .geocoder-container { max-width: 100%; margin: 0; }
        .toolbar-tools { justify-content: center; flex-wrap: wrap; }
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
    
    body:not(.dark-theme) .top-toolbar {
        background: rgba(255, 255, 255, 0.95);
        border: 1px solid rgba(0, 0, 0, 0.1);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }
    body:not(.dark-theme) .tool-btn { color: #64748b; }
    body:not(.dark-theme) .tool-btn:hover { background: #f1f5f9; color: #0f172a; }
    body:not(.dark-theme) .tool-btn.active { background: #38bdf8; color: white; box-shadow: 0 4px 10px rgba(56, 189, 248, 0.3); }
    body:not(.dark-theme) .tool-btn-back { background: #e0f2fe; color: #0284c7; }
    body:not(.dark-theme) .toolbar-divider { background: rgba(0,0,0,0.1); }
    body:not(.dark-theme) .geocoder-container input {
        background: #f8fafc !important;
        color: #0f172a !important;
        border: 1px solid #e2e8f0 !important;
    }
    body:not(.dark-theme) .geocoder-container input:focus {
        border-color: #38bdf8 !important;
        background: white !important;
        box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.2) !important;
    }
    body:not(.dark-theme) .geocoder-container input::placeholder { color: #94a3b8 !important; }

    /* Estilo general para selects dinÃƒÂ¡micos */
    select.map-select { background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.1); }
    select.map-select option { color: black; }
    body:not(.dark-theme) select.map-select { background: white; color: #0f172a; border-color: rgba(0,0,0,0.1); }

    /* Google Earth Style Panel */
    .earth-panel {
        position: absolute;
        top: 20px;
        right: 20px;
        width: 350px;
        background: #1e1e1e; /* Dark theme */
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        color: #fff;
        font-family: 'Roboto', sans-serif;
        box-shadow: 0 4px 15px rgba(0,0,0,0.5);
        z-index: 10;
        display: none;
        flex-direction: column;
        overflow: hidden;
    }
    .earth-panel.active { display: flex; }
    .earth-panel-header {
        background: rgba(255, 255, 255, 0.05);
        padding: 15px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: bold;
    }
    .earth-panel-body { padding: 15px; display: flex; flex-direction: column; gap: 15px; }
    .earth-stat { display: flex; flex-direction: column; margin-bottom: 10px;}
    .earth-stat-label { font-size: 0.8rem; color: #a0a0a0; margin-bottom: 4px; }
    .earth-stat-value { font-size: 1.1rem; font-weight: 500; }
    .elevation-chart { width: 100%; height: 100px; background: rgba(255,255,255,0.05); border-radius: 8px; margin-top: 10px; }
    .earth-btn-save {
        background: #0b57d0; color: white; border: none; padding: 10px; border-radius: 20px;
        cursor: pointer; font-weight: 500; margin-top: 10px; display: flex; justify-content: center; align-items: center; gap: 5px;
    }
    .earth-btn-save:hover { background: #0842a0; }
    
    /* No need for top-toolbar overrides here */
    
    /* Hide some mapbox specific classes */
    .mapboxgl-ctrl-geocoder { display: none; }

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
                <option value="mapbox://styles/mapbox/satellite-streets-v12">SatÃƒÂ©lite</option>
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
            <!-- Izquierda: Detalles TÃƒÂ©cnicos -->
            <div class="modal-col-left">
                <textarea id="modalDesc" class="modal-desc" rows="2" placeholder="AÃƒÂ±adir descripciÃƒÂ³n o notas..."></textarea>
                
                <div class="carousel" id="carousel">
                    <div class="no-image">
                        <i class="ph-bold ph-image" style="font-size: 2rem;"></i>
                        <span>Sin fotos</span>
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px; align-items: center;">
                    <div class="coord-box">
                        <i class="ph-fill ph-map-pin"></i> <span id="modalCoords">-11.84, -77.11</span>
                    </div>
                    <button class="btn-whatsapp-share" onclick="shareLocationWhatsApp()" title="Compartir ubicaciÃƒÂ³n por WhatsApp">
                        <i class="ph-bold ph-whatsapp-logo"></i>
                    </button>
                </div>
                
                <div id="techPointContainer" style="margin-top: 10px;">
                    <h5 style="color: #94a3b8; font-size: 0.85rem; margin-bottom: 10px;">DATOS TÃƒâ€°CNICOS</h5>
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
                    <input type="file" id="fileUploadInput" class="no-dropzone" accept="image/*" capture="environment" style="display:none;" multiple>
                    <button class="action-btn" title="Cambiar Color" onclick="toggleStylePicker(event)"><i class="ph-bold ph-palette"></i></button>
                    <button class="action-btn btn-nav" title="CÃƒÂ³mo llegar" id="btnNav"><i class="ph-bold ph-navigation-arrow"></i></button>
                    <button class="action-btn btn-del" title="Eliminar" onclick="deleteElement()"><i class="ph-bold ph-trash"></i></button>
                </div>
            </div>
            
            <!-- Derecha: GestiÃƒÂ³n de Hilos -->
            <div class="modal-col-right" id="portsContainer">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h4 style="margin:0;"><i class="ph-fill ph-graph"></i> GestiÃƒÂ³n de Hilos</h4>
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
                <div class="style-section-title">ÃƒÂcono</div>
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
            <button class="tool-btn tool-btn-back" onclick="window.location.href='index.php'" title="Volver a Proyectos"><i class="ph-bold ph-arrow-left"></i></button>
            <div class="toolbar-divider"></div>
            <button class="tool-btn" onclick="document.querySelector('.map-sidebar').classList.toggle('sidebar-active');" title="MenÃƒÂº"><i class="ph-bold ph-list"></i></button>
            <button class="tool-btn active" id="tool-hand" title="Mover mapa (Esc)" onclick="setTool('simple_select', this)"><i class="ph-bold ph-hand-palm"></i></button>
            <button class="tool-btn" id="tool-point" title="AÃƒÂ±adir Punto" onclick="setTool('draw_point', this)"><i class="ph-bold ph-map-pin-plus"></i></button>
            <button class="tool-btn" id="tool-line" title="Dibujar LÃƒÂ­nea" onclick="setTool('draw_line_string', this)"><i class="ph-bold ph-trend-up"></i></button>
            <button class="tool-btn" id="tool-polygon" title="Dibujar PolÃƒÂ­gono" onclick="setTool('draw_polygon', this)"><i class="ph-bold ph-hexagon"></i></button>
            <button class="tool-btn" id="tool-measure" title="Medir Distancia" onclick="toggleMeasure(this)"><i class="ph-bold ph-ruler"></i></button>
        </div>
        <div class="geocoder-container" id="geocoder">
            <input type="text" id="googleSearchInput" class="form-control" placeholder="Buscar lugares, cajas o cables...">
        </div>
    </div>
    
    <div class="measurement-box" id="measurementBox">0 m</div>
    <!-- Panel Estilo Google Earth -->
    <div class="earth-panel" id="earthPanel">
        <div class="earth-panel-header">
            <span id="epTitle">Ruta o polÃƒÂ­gono</span>
            <button onclick="cancelDrawing()" style="background:none;border:none;color:white;cursor:pointer;"><i class="ph-bold ph-x"></i></button>
        </div>
        <div class="earth-panel-body">
            <div id="epLengthArea"></div>
            
            <div id="epElevation" style="display:none; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 15px;">
                <div class="earth-stat-label">Perfil de elevaciÃƒÂ³n</div>
                <div class="earth-stat-label" style="margin-top: 5px;">
                    MÃƒÂ­n.: <span id="epMinElev" style="color:white">-</span> | Media: <span id="epAvgElev" style="color:white">-</span> | MÃƒÂ¡x.: <span id="epMaxElev" style="color:white">-</span>
                </div>
                <canvas id="elevationChart" class="elevation-chart"></canvas>
            </div>
            
            <button class="earth-btn-save" onclick="saveCurrentDrawing()"><i class="ph-bold ph-floppy-disk"></i> Guardar en proyecto</button>
        </div>
    </div>

</div>

<!-- Visor de ImÃƒÂ¡genes a Pantalla Completa -->
<div id="lightboxOverlay" onclick="closeLightbox(event)">
    <button id="lightboxClose" class="lb-btn lb-close" onclick="closeLightbox(event)">&times;</button>
    <button class="lb-btn lb-prev" onclick="lbChangeImg(-1, event)"><i class="ph-bold ph-caret-left"></i></button>
    <button class="lb-btn lb-next" onclick="lbChangeImg(1, event)"><i class="ph-bold ph-caret-right"></i></button>
    
    <div class="lb-zoom-controls" onclick="event.stopPropagation()">
        <button class="lb-btn-small" onclick="lbZoom(-1, event)"><i class="ph-bold ph-minus"></i></button>
        <span id="lbZoomLevel">100%</span>
        <button class="lb-btn-small" onclick="lbZoom(1, event)"><i class="ph-bold ph-plus"></i></button>
        <button class="lb-btn-small" onclick="lbResetZoom(event)" style="margin-left: 5px;"><i class="ph-bold ph-corners-out"></i></button>
    </div>

    <div id="lightboxImgContainer">
        <img id="lightboxImg" src="" alt="Visor">
    </div>
</div>

<script>
const proyectoId = <?php echo $proyecto_id; ?>;
const isSystemDark = document.body.classList.contains('dark-theme');

let map, drawingManager, elevationService;
let currentGeoJson = { type: 'FeatureCollection', features: [] };
let activeElementId = null;
let activeImages = [];
let currentImageIndex = 0;
let currentMarkers = [];
let currentDrawing = null; // Guardar el overlay temporal de dibujo
let currentDrawingFeature = null; // Geojson temp

document.addEventListener("DOMContentLoaded", () => {
    initMap();
});

function initMap() {
    map = new google.maps.Map(document.getElementById("map"), {
        center: { lat: -11.865, lng: -77.086 },
        zoom: 15,
        mapTypeId: google.maps.MapTypeId.HYBRID, // SatÃƒÂ©lite con calles
        tilt: 45, // Modo Earth 3D
        streetViewControl: true,
        mapTypeControl: false,
        fullscreenControl: false,
        zoomControl: true,
        zoomControlOptions: {
            position: google.maps.ControlPosition.RIGHT_BOTTOM,
        },
        streetViewControlOptions: {
            position: google.maps.ControlPosition.RIGHT_BOTTOM,
        }
    });

    elevationService = new google.maps.ElevationService();

    // Data Layer para cargar GeoJSON
    map.data.setStyle(function(feature) {
        let type = feature.getGeometry().getType();
        let color = feature.getProperty('color') || '#a78bfa';
        
        if (type === 'LineString') {
            return { strokeColor: color, strokeWeight: 4 };
        } else if (type === 'Polygon') {
            return { fillColor: color, fillOpacity: 0.3, strokeColor: color, strokeWeight: 2 };
        }
        return { visible: false }; // Markers manejados por separado
    });

    map.data.addListener('click', function(event) {
        let f = currentGeoJson.features.find(x => x.properties.id == event.feature.getProperty('id'));
        if(f) {
            let center;
            if (f.geometry.type === 'Polygon') center = f.geometry.coordinates[0][0];
            else center = f.geometry.coordinates[0];
            openModal(f.properties.id, center, f.properties.name, f.properties.color, f.properties.icono, f.properties.descripcion, f.geometry.type, f);
        }
    });

    // Drawing Manager
    drawingManager = new google.maps.drawing.DrawingManager({
        drawingMode: null,
        drawingControl: false,
        polygonOptions: { fillColor: '#38bdf8', fillOpacity: 0.3, strokeWeight: 2, strokeColor: '#38bdf8', editable: true },
        polylineOptions: { strokeColor: '#38bdf8', strokeWeight: 4, editable: true }
    });
    drawingManager.setMap(map);

    google.maps.event.addListener(drawingManager, 'overlaycomplete', function(event) {
        drawingManager.setDrawingMode(null);
        document.querySelectorAll('.tool-btn').forEach(b => b.classList.remove('active'));
        document.getElementById('tool-hand').classList.add('active');
        
        if(currentDrawing) currentDrawing.setMap(null); // Borrar anterior si hay
        currentDrawing = event.overlay;

        let tipo = event.type === 'polyline' ? 'LineString' : (event.type === 'polygon' ? 'Polygon' : 'Point');
        
        // Crear GeoJSON temporal para Turf
        if(tipo === 'LineString') {
            let path = currentDrawing.getPath().getArray().map(p => [p.lng(), p.lat()]);
            currentDrawingFeature = turf.lineString(path);
            showEarthPanel(currentDrawingFeature, 'LineString');
            
            // Re-calcular si el usuario edita la lÃƒÂ­nea
            google.maps.event.addListener(currentDrawing.getPath(), 'set_at', () => updateEarthPanelStats());
            google.maps.event.addListener(currentDrawing.getPath(), 'insert_at', () => updateEarthPanelStats());
            
        } else if (tipo === 'Polygon') {
            let path = currentDrawing.getPath().getArray().map(p => [p.lng(), p.lat()]);
            path.push(path[0]); // Cerrar polÃƒÂ­gono
            currentDrawingFeature = turf.polygon([path]);
            showEarthPanel(currentDrawingFeature, 'Polygon');
            
            google.maps.event.addListener(currentDrawing.getPath(), 'set_at', () => updateEarthPanelStats());
            google.maps.event.addListener(currentDrawing.getPath(), 'insert_at', () => updateEarthPanelStats());
            
        } else if (tipo === 'Point') {
            currentDrawingFeature = turf.point([currentDrawing.getPosition().lng(), currentDrawing.getPosition().lat()]);
            // Guardar directo para punto
            saveCurrentDrawing(true); // true = auto
        }
    });

    // Buscador Google Places Autocomplete
    const input = document.getElementById("googleSearchInput");
    const autocomplete = new google.maps.places.Autocomplete(input);
    autocomplete.bindTo("bounds", map);

    autocomplete.addListener("place_changed", () => {
        const place = autocomplete.getPlace();
        if (!place.geometry || !place.geometry.location) {
            // Buscar en BD local si no es lugar de Google
            let res = currentGeoJson.features.find(f => f.properties.name.toLowerCase().includes(input.value.toLowerCase()));
            if(res) {
                let center = res.geometry.type === 'Point' ? res.geometry.coordinates : res.geometry.coordinates[0];
                map.setCenter({lng: center[0], lat: center[1]});
                map.setZoom(18);
                openModal(res.properties.id, center, res.properties.name, res.properties.color, res.properties.icono, res.properties.descripcion, res.geometry.type, res);
            }
            return;
        }
        map.setCenter(place.geometry.location);
        map.setZoom(17);
    });

    loadMapData();
}

// PANEL EARTH
function showEarthPanel(feature, type) {
    document.getElementById('earthPanel').classList.add('active');
    document.getElementById('epTitle').innerText = type === 'LineString' ? 'Ruta de Cable' : 'Zona PolÃƒÂ­gono';
    updateEarthPanelStats();
}

function updateEarthPanelStats() {
    if(!currentDrawing || !currentDrawingFeature) return;
    
    let html = '';
    let tipo = currentDrawingFeature.geometry.type;
    
    if (tipo === 'LineString') {
        let path = currentDrawing.getPath().getArray().map(p => [p.lng(), p.lat()]);
        currentDrawingFeature = turf.lineString(path); // Update feature
        
        let len = turf.length(currentDrawingFeature, {units: 'meters'});
        let lenTxt = len > 1000 ? (len/1000).toFixed(2) + ' km' : len.toFixed(2) + ' m';
        
        html = `<div class="earth-stat">
                    <span class="earth-stat-label">Longitud</span>
                    <span class="earth-stat-value">${lenTxt}</span>
                </div>`;
                
        document.getElementById('epLengthArea').innerHTML = html;
        document.getElementById('epElevation').style.display = 'block';
        calculateElevation(currentDrawing.getPath().getArray());
        
    } else if (tipo === 'Polygon') {
        let path = currentDrawing.getPath().getArray().map(p => [p.lng(), p.lat()]);
        path.push(path[0]);
        currentDrawingFeature = turf.polygon([path]); // Update feature
        
        let area = turf.area(currentDrawingFeature);
        let perim = turf.length(turf.polygonToLine(currentDrawingFeature), {units: 'meters'});
        
        let areaTxt = area > 1000000 ? (area/1000000).toFixed(2) + ' kmÃ‚Â²' : area.toFixed(2) + ' mÃ‚Â²';
        let perimTxt = perim > 1000 ? (perim/1000).toFixed(2) + ' km' : perim.toFixed(2) + ' m';
        
        html = `<div class="earth-stat">
                    <span class="earth-stat-label">PerÃƒÂ­metro</span>
                    <span class="earth-stat-value">${perimTxt}</span>
                </div>
                <div class="earth-stat">
                    <span class="earth-stat-label">ÃƒÂrea</span>
                    <span class="earth-stat-value">${areaTxt}</span>
                </div>`;
                
        document.getElementById('epLengthArea').innerHTML = html;
        document.getElementById('epElevation').style.display = 'none';
    }
}

function calculateElevation(pathArray) {
    if(pathArray.length < 2) return;
    
    elevationService.getElevationAlongPath({
        path: pathArray,
        samples: 50
    }, function(results, status) {
        if (status === 'OK' && results) {
            let elevations = results.map(r => r.elevation);
            let min = Math.min(...elevations);
            let max = Math.max(...elevations);
            let avg = elevations.reduce((a,b)=>a+b,0) / elevations.length;
            
            document.getElementById('epMinElev').innerText = min.toFixed(1) + 'm';
            document.getElementById('epMaxElev').innerText = max.toFixed(1) + 'm';
            document.getElementById('epAvgElev').innerText = avg.toFixed(1) + 'm';
            
            drawElevationChart(elevations, min, max);
        }
    });
}

function drawElevationChart(elevations, min, max) {
    const canvas = document.getElementById('elevationChart');
    const ctx = canvas.getContext('2d');
    canvas.width = canvas.offsetWidth;
    canvas.height = canvas.offsetHeight;
    
    ctx.clearRect(0,0, canvas.width, canvas.height);
    if(elevations.length === 0) return;
    
    const range = max - min;
    const padding = range === 0 ? 10 : range * 0.2; // Evitar division por cero
    const graphMin = min - padding;
    const graphMax = max + padding;
    
    ctx.beginPath();
    ctx.moveTo(0, canvas.height);
    
    let w = canvas.width / (elevations.length - 1);
    elevations.forEach((e, i) => {
        let x = i * w;
        let y = canvas.height - ((e - graphMin) / (graphMax - graphMin)) * canvas.height;
        ctx.lineTo(x, y);
    });
    
    ctx.lineTo(canvas.width, canvas.height);
    ctx.closePath();
    
    let gradient = ctx.createLinearGradient(0, 0, 0, canvas.height);
    gradient.addColorStop(0, "rgba(56, 189, 248, 0.8)");
    gradient.addColorStop(1, "rgba(56, 189, 248, 0.1)");
    
    ctx.fillStyle = gradient;
    ctx.fill();
    ctx.strokeStyle = '#38bdf8';
    ctx.lineWidth = 2;
    ctx.stroke();
}

function cancelDrawing() {
    if(currentDrawing) currentDrawing.setMap(null);
    currentDrawing = null;
    currentDrawingFeature = null;
    document.getElementById('earthPanel').classList.remove('active');
}

function saveCurrentDrawing(isAuto = false) {
    if(!currentDrawingFeature) return;
    
    const tipo = currentDrawingFeature.geometry.type;
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
    formData.append('geojson', JSON.stringify(currentDrawingFeature.geometry));
    
    fetch('api.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if(res.success) {
                cancelDrawing(); // Limpiar UI temporal
                loadMapData(); // Recargar datos reales
                
                let center;
                if (isPoint) center = currentDrawingFeature.geometry.coordinates;
                else if (tipo === 'Polygon') center = currentDrawingFeature.geometry.coordinates[0][0];
                else center = currentDrawingFeature.geometry.coordinates[0];
                
                openModal(res.id, center, nombre, '#a78bfa', 'ph-map-pin', '', tipo, currentDrawingFeature);
            } else {
                alert('Error al guardar el elemento');
            }
        });
}

function setTool(mode, btn) {
    document.querySelectorAll('.tool-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    
    if (mode === 'simple_select') drawingManager.setDrawingMode(null);
    if (mode === 'draw_point') drawingManager.setDrawingMode(google.maps.drawing.OverlayType.MARKER);
    if (mode === 'draw_line_string') drawingManager.setDrawingMode(google.maps.drawing.OverlayType.POLYLINE);
    if (mode === 'draw_polygon') drawingManager.setDrawingMode(google.maps.drawing.OverlayType.POLYGON);
}

function toggleMeasure(btn) {
    // La medidÃƒÂ³n se maneja usando draw_line_string que ahora abre el panel Earth
    setTool('draw_line_string', btn);
}

function loadMapData() {
    fetch(`api.php?action=get_elements&proyecto_id=${proyectoId}`)
        .then(r => r.json())
        .then(res => {
            if(res.success) {
                currentGeoJson = res.geojson;
                // Actualizar Data Layer
                map.data.forEach(function(feature) {
                    map.data.remove(feature);
                });
                map.data.addGeoJson(currentGeoJson);
                renderMarkers();
                fitMapToBounds();
            }
        });
}

function renderMarkers() {
    currentMarkers.forEach(m => m.setMap(null));
    currentMarkers = [];
    
    currentGeoJson.features.forEach(f => {
        if (f.geometry.type === 'Point') {
            const coords = f.geometry.coordinates;
            const icono = f.properties.icono || 'ph-map-pin';
            const color = f.properties.color || '#a78bfa';
            
            // Usar AdvancedMarkerElement si es posible, o Marker clÃƒÂ¡sico
            let iconUrl;
            if (icono.includes('/')) {
                iconUrl = '../../' + icono; // Ruta a PNG
            } else {
                // Generar icono en canvas temporal (simplificado para SVG)
                iconUrl = `data:image/svg+xml;charset=UTF-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 256 256' width='32' height='32'%3E%3Cpath fill='${encodeURIComponent(color)}' d='M128,64a40,40,0,1,0,40,40A40,40,0,0,0,128,64Zm0,64a24,24,0,1,1,24-24A24,24,0,0,1,128,128Zm0-112a88.1,88.1,0,0,0-88,88c0,31.4,14.51,64.68,42,96.25a254.19,254.19,0,0,0,41.45,38.3,8,8,0,0,0,9.18,0A254.19,254.19,0,0,0,174,200.25c27.45-31.57,42-64.85,42-96.25A88.1,88.1,0,0,0,128,16Zm0,206.5c-35.39-27.18-72-65.75-72-118.5a72,72,0,0,1,144,0C200,156.75,163.39,195.32,128,222.5Z'/%3E%3C/svg%3E`;
            }
            
            const marker = new google.maps.Marker({
                position: { lat: coords[1], lng: coords[0] },
                map: map,
                icon: {
                    url: iconUrl,
                    scaledSize: new google.maps.Size(32, 32)
                },
                title: f.properties.name
            });
            
            marker.addListener('click', () => {
                openModal(f.properties.id, coords, f.properties.name, f.properties.color, f.properties.icono, f.properties.descripcion, 'Point', f);
            });
                
            currentMarkers.push(marker);
        }
    });
}

function fitMapToBounds() {
    if(currentGeoJson.features.length === 0) return;
    const bounds = new google.maps.LatLngBounds();
    currentGeoJson.features.forEach(f => {
        if(f.geometry.type === 'Point') bounds.extend({lat: f.geometry.coordinates[1], lng: f.geometry.coordinates[0]});
        else if (f.geometry.type === 'LineString') f.geometry.coordinates.forEach(c => bounds.extend({lat: c[1], lng: c[0]}));
        else if (f.geometry.type === 'Polygon') f.geometry.coordinates[0].forEach(c => bounds.extend({lat: c[1], lng: c[0]}));
    });
    map.fitBounds(bounds);
}

document.getElementById('styleSwitcher').addEventListener('change', function(e) {
    if(this.value.includes('dark')) {
        map.setMapTypeId(google.maps.MapTypeId.ROADMAP);
        map.setOptions({styles: [
          { elementType: "geometry", stylers: [{ color: "#242f3e" }] },
          { elementType: "labels.text.stroke", stylers: [{ color: "#242f3e" }] },
          { elementType: "labels.text.fill", stylers: [{ color: "#746855" }] }
        ]}); // Estilo oscuro simplificado
    } else if (this.value.includes('satellite')) {
        map.setMapTypeId(google.maps.MapTypeId.HYBRID);
        map.setOptions({styles: []});
    } else {
        map.setMapTypeId(google.maps.MapTypeId.ROADMAP);
        map.setOptions({styles: []});
    }
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

// MODAL LOGIC (Se mantiene prÃƒÂ¡cticamente igual)
let activePuertos = [];

function shareLocationWhatsApp() {
    if (!activeElementId) return;
    const coordsText = document.getElementById('modalCoords').innerText;
    const title = document.getElementById('modalTitle').value || 'UbicaciÃƒÂ³n';
    const url = `https://www.google.com/maps/search/?api=1&query=${coordsText.replace(/ /g, '')}`;
    const message = `*${title}*\nCoordenadas: ${coordsText}\nMapa: ${url}`;
    const whatsappUrl = `https://wa.me/?text=${encodeURIComponent(message)}`;
    window.open(whatsappUrl, '_blank');
}

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
            document.getElementById('techArea').value = area > 1000000 ? (area/1000000).toFixed(2) + ' kmÃ‚Â²' : area.toFixed(2) + ' mÃ‚Â²';
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
                
                // Datos TÃƒÂ©cnicos
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

// LÃƒâ€œGICA DE PUERTOS
document.getElementById('techCapacidad').addEventListener('change', () => {
    closeModal();
    setTimeout(() => {
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
        nuevoCliente = ''; 
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
                activeIcon = res.ruta; 
                closeModal(); 
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
    let html = `<img src="../../${img.ruta}" alt="Foto" onclick="openLightbox(${currentImageIndex})" style="cursor:pointer;">`;
    if(activeImages.length > 1) {
        html += `<button class="carousel-btn prev" onclick="changeImg(-1)"><i class="ph-bold ph-caret-left"></i></button>`;
        html += `<button class="carousel-btn next" onclick="changeImg(1)"><i class="ph-bold ph-caret-right"></i></button>`;
        html += `<div style="position:absolute; bottom:5px; background:rgba(0,0,0,0.5); padding:2px 8px; border-radius:10px; font-size:0.8rem;">${currentImageIndex+1} / ${activeImages.length}</div>`;
    }
    c.innerHTML = html;
}

let lbZoomLevel = 1;
let lbIsDragging = false;
let lbStartX = 0, lbStartY = 0;
let lbTranslateX = 0, lbTranslateY = 0;

function openLightbox(index) {
    if(!activeImages || activeImages.length === 0) return;
    currentImageIndex = index;
    updateLightboxImg();
    resetLightboxTransform();
    document.getElementById('lightboxOverlay').classList.add('active');
}

function updateLightboxImg() {
    if(!activeImages || activeImages.length === 0) return;
    document.getElementById('lightboxImg').src = '../../' + activeImages[currentImageIndex].ruta;
    document.querySelector('.lb-prev').style.display = activeImages.length > 1 ? 'flex' : 'none';
    document.querySelector('.lb-next').style.display = activeImages.length > 1 ? 'flex' : 'none';
}

function lbChangeImg(dir, e) {
    if(e) e.stopPropagation();
    currentImageIndex += dir;
    if(currentImageIndex < 0) currentImageIndex = activeImages.length - 1;
    if(currentImageIndex >= activeImages.length) currentImageIndex = 0;
    
    resetLightboxTransform();
    updateLightboxImg();
    renderCarousel(); // Sync background carousel
}

function closeLightbox(e) {
    if(e && e.target.id !== 'lightboxOverlay' && e.target.id !== 'lightboxClose' && e.target.id !== 'lightboxImgContainer') {
        return;
    }
    document.getElementById('lightboxOverlay').classList.remove('active');
    document.getElementById('lightboxImg').src = '';
    resetLightboxTransform();
}

function resetLightboxTransform() {
    lbZoomLevel = 1;
    lbTranslateX = 0;
    lbTranslateY = 0;
    applyLightboxTransform();
}

function applyLightboxTransform() {
    const img = document.getElementById('lightboxImg');
    img.style.transform = `translate(${lbTranslateX}px, ${lbTranslateY}px) scale(${lbZoomLevel})`;
    document.getElementById('lbZoomLevel').innerText = Math.round(lbZoomLevel * 100) + '%';
}

function lbZoom(dir, e) {
    if(e) e.stopPropagation();
    const zoomStep = 0.25;
    if(dir > 0) lbZoomLevel += zoomStep;
    else lbZoomLevel -= zoomStep;
    
    if(lbZoomLevel < 0.25) lbZoomLevel = 0.25;
    if(lbZoomLevel > 5) lbZoomLevel = 5;
    
    applyLightboxTransform();
}

function lbResetZoom(e) {
    if(e) e.stopPropagation();
    resetLightboxTransform();
}

window.changeImg = function(dir) {
    currentImageIndex += dir;
    if(currentImageIndex < 0) currentImageIndex = activeImages.length - 1;
    if(currentImageIndex >= activeImages.length) currentImageIndex = 0;
    renderCarousel();
}

// Drag & Drop / Scroll de Lightbox
document.addEventListener('DOMContentLoaded', () => {
    const lbContainer = document.getElementById('lightboxImgContainer');
    if (lbContainer) {
        lbContainer.addEventListener('mousedown', (e) => {
            if(e.target.id === 'lightboxImgContainer') return;
            if(lbZoomLevel <= 1) return;
            lbIsDragging = true;
            lbStartX = e.clientX - lbTranslateX;
            lbStartY = e.clientY - lbTranslateY;
            e.preventDefault();
        });
        
        window.addEventListener('mousemove', (e) => {
            if(!lbIsDragging) return;
            lbTranslateX = e.clientX - lbStartX;
            lbTranslateY = e.clientY - lbStartY;
            document.getElementById('lightboxImg').style.transition = 'none';
            applyLightboxTransform();
        });
        
        window.addEventListener('mouseup', () => {
            if(lbIsDragging) {
                lbIsDragging = false;
                document.getElementById('lightboxImg').style.transition = 'transform 0.2s cubic-bezier(0.4, 0, 0.2, 1)';
            }
        });

        lbContainer.addEventListener('wheel', (e) => {
            e.preventDefault();
            if(e.deltaY < 0) lbZoom(1);
            else lbZoom(-1);
        }, {passive: false});
    }
});

// Tecla ESC para cerrar modal y lightbox
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        const lb = document.getElementById('lightboxOverlay');
        if (lb && lb.classList.contains('active')) {
            closeLightbox({target: {id: 'lightboxOverlay'}});
        } else {
            const modal = document.getElementById('elementModal');
            if (modal && modal.classList.contains('active')) {
                closeModal();
            }
        }
    }
});

async function uploadFiles(files) {
    if(!files || files.length === 0 || !activeElementId) return;

    const c = document.getElementById('carousel');
    c.innerHTML = '<div class="no-image"><i class="ph ph-spinner ph-spin" style="font-size: 2rem;"></i><span>Subiendo...</span></div>';

    let hasErrors = false;
    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        const formData = new FormData();
        formData.append('action', 'upload_image');
        formData.append('elemento_id', activeElementId);
        formData.append('file', file);

        try {
            const r = await fetch('api.php', { method: 'POST', body: formData });
            const res = await r.json();
            if(res.success) {
                activeImages.unshift({ ruta: res.ruta });
            } else {
                alert(`Error al subir ${file.name}: ` + res.message);
                hasErrors = true;
            }
        } catch(e) {
            alert(`Error de red al subir ${file.name}`);
            hasErrors = true;
        }
    }
    currentImageIndex = 0;
    renderCarousel();
}

document.getElementById('fileUploadInput').addEventListener('change', function(e) {
    uploadFiles(e.target.files);
});

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
        uploadFiles(e.dataTransfer.files);
    }
});

function deleteElement() {
    if(confirm('Ã‚Â¿Seguro que deseas eliminar este elemento del mapa?')) {
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

initStylePicker();
</script>


<?php include '../../includes/footer.php'; ?>