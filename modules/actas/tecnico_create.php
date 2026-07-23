<?php
require_once '../../config/db.php';
requireLogin();

$isEdit = isset($_GET['edit']);
$editActa = null;
$editEquipos = [];
$editMateriales = [];

if ($isEdit) {
    $stmt = $pdo->prepare("SELECT * FROM actas WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editActa = $stmt->fetch();

    if ($editActa) {
        $nextFolio = $editActa['folio'];
        
        $stmtEq = $pdo->prepare("SELECT * FROM actas_equipos WHERE acta_id = ?");
        $stmtEq->execute([$editActa['id']]);
        $editEquipos = $stmtEq->fetchAll();

        $stmtMat = $pdo->prepare("SELECT * FROM actas_materiales WHERE acta_id = ?");
        $stmtMat->execute([$editActa['id']]);
        $editMateriales = $stmtMat->fetchAll();
    } else {
        $isEdit = false;
    }
} else {
    // Generar Folio temporal para mostrar (el real se genera en el backend)
    $stmt = $pdo->query("SELECT MAX(id) as max_id FROM actas");
    $row = $stmt->fetch();
    $nextFolio = str_pad(($row['max_id'] ? $row['max_id'] + 1 : 1), 6, '0', STR_PAD_LEFT);
}

// Fetch products for dropdowns
$stmtEqList = $pdo->query("SELECT id, name FROM inventory_products WHERE is_bulk = 0 ORDER BY name ASC");
$equiposList = $stmtEqList->fetchAll();

$stmtMatList = $pdo->query("SELECT id, name, unit_type FROM inventory_products WHERE is_bulk = 1 ORDER BY name ASC");
$materialesList = $stmtMatList->fetchAll();

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<style>
    .acta-section {
        background: var(--surface-color);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: var(--shadow);
    }
    .acta-section-title {
        font-size: 1.1rem;
        font-weight: 700;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
        text-transform: uppercase;
        color: var(--text-color);
    }
    .header-info {
        display: flex;
        align-items: center;
        gap: 20px;
        background: rgba(67, 97, 238, 0.1);
        padding: 10px 20px;
        border-radius: 8px;
    }
    .header-info span {
        font-weight: bold;
        color: var(--primary-color);
    }
    .header-info .folio-text {
        color: var(--danger-color);
    }
    
    .signature-pad-container {
        border: 1px dashed var(--border-color);
        border-radius: 8px;
        background: var(--bg-color);
        position: relative;
    }
    .signature-pad {
        width: 100%;
        height: 150px;
        cursor: crosshair;
        touch-action: none;
    }
    .clear-signature {
        position: absolute;
        top: 10px;
        right: 10px;
    }
    .btn-ocr {
        background: var(--surface-color);
        border: 1px solid var(--border-color);
        color: var(--text-color);
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;
    }
    .btn-ocr:hover {
        background: var(--bg-color);
        border-color: var(--primary-color);
        color: var(--primary-color);
    }
    body.dark-theme .btn-ocr {
        background: #1e293b;
        border-color: #334155;
        color: #f8fafc;
    }
    body.dark-theme .btn-ocr:hover {
        border-color: var(--primary-color);
    }
    
    body.dark-theme input[type="date"]::-webkit-calendar-picker-indicator,
    body.dark-theme input[type="time"]::-webkit-calendar-picker-indicator {
        filter: invert(1);
    }
    
    .rating-stars {
        display: flex;
        gap: 5px;
        color: #f1c40f;
        font-size: 1.5rem;
        cursor: pointer;
    }
    .rating-stars i {
        transition: transform 0.2s;
    }
    .rating-stars i:hover {
        transform: scale(1.2);
    }
    
    .btn-create-wrapper {
        position: sticky;
        bottom: 20px;
        z-index: 99;
        display: flex;
        justify-content: center;
        padding: 0 15px;
        pointer-events: none;
        margin-top: 30px;
        margin-bottom: 30px;
    }
    
    .btn-create-acta {
        pointer-events: auto;
        width: 100%;
        max-width: 300px;
        display: flex;
        padding: 14px;
        font-size: 1.1rem;
        border-radius: 50px;
        background-color: var(--primary-color);
        color: white;
        border: none;
        box-shadow: 0 8px 25px rgba(234, 88, 12, 0.4);
        font-weight: bold;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .btn-create-acta:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(234, 88, 12, 0.5);
    }
    body.dark-theme .btn-create-acta {
        color: white;
    }
    
    .success-modal {
        text-align: center;
    }
    .success-icon {
        font-size: 4rem;
        color: var(--success-color);
        margin-bottom: 20px;
    }
    .success-folio {
        font-size: 2rem;
        font-weight: 900;
        color: var(--primary-color);
        margin: 20px 0;
    }
    .btn-group-vertical {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    
    .file-input-wrapper {
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 10px;
        background: var(--bg-color);
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .tech-acta-header {
        background: var(--surface-color);
        padding: 16px 20px;
        border-radius: 12px;
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        border: 1px solid var(--border-color);
    }
    .tech-acta-header h2 {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--primary-color);
    }
    .tech-acta-folio {
        font-size: 0.85rem;
        font-weight: 800;
        background: rgba(59, 130, 246, 0.1);
        color: var(--primary-color);
        padding: 4px 10px;
        border-radius: 8px;
        letter-spacing: 0.5px;
    }
</style>

<div style="width: 100%; display: flex; flex-direction: column;">
<div class="tech-acta-header">
    <h2><i class="ph-bold ph-file-text"></i> Nueva Acta</h2>
    <div class="tech-acta-folio">FOLIO: <?php echo $nextFolio; ?></div>
</div>

<form id="actaForm">
    <input type="hidden" name="prefijo" value="LIM-">
    <?php if ($isEdit && $editActa): ?>
    <input type="hidden" name="edit_id" value="<?php echo $editActa['id']; ?>">
    <?php endif; ?>
    <input type="hidden" name="calificacion" id="inputCalificacion" value="0">
    <input type="hidden" name="firma_cliente_base64" id="firmaClienteBase64">
    <input type="hidden" name="firma_tecnico_base64" id="firmaTecnicoBase64">

    <!-- 1. DATOS DEL CLIENTE -->
    <div class="acta-section">
        <div class="acta-section-title">1. DATOS DEL CLIENTE <button type="button" class="btn-ocr"><i class="ph ph-camera"></i> Escanear DNI</button></div>
        <div class="row">
            <div class="col-md-12 mb-3 position-relative">
                <label class="form-label">Buscador de Cliente (Autocompletar)</label>
                <input type="text" class="form-control" id="searchClienteAutocomplete" placeholder="Buscar cliente por nombre o DNI..." autocomplete="off">
                <div id="autocompleteResults" class="dropdown-menu w-100" style="display: none; max-height: 200px; overflow-y: auto; position: absolute; top: 100%; left: 0; z-index: 1000;"></div>
            </div>
            <div class="col-md-12 mb-3">
                <label class="form-label">Nombre Completo / Razón Social</label>
                <input type="text" class="form-control" name="cliente_nombre" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label d-flex justify-content-between align-items-center">DNI / RUC 
                    <button type="button" class="btn-ocr" id="btnSearchReniec"><i class="ph ph-magnifying-glass"></i> <span id="textReniec">Sunat/Reniec</span></button>
                </label>
                <input type="tel" class="form-control" name="cliente_dni_ruc" id="cliente_dni_ruc" inputmode="numeric">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label d-flex justify-content-between align-items-center">Rotulado (QR) 
                    <button type="button" class="btn-ocr" onclick="openActaScanner(document.getElementById('cliente_rotulado'), 'qr')" title="Escanear QR"><i class="ph ph-qr-code"></i></button>
                </label>
                <input type="text" class="form-control" name="cliente_rotulado" id="cliente_rotulado">
            </div>
            <div class="col-md-12 mb-3">
                <label class="form-label">Dirección de Instalación</label>
                <input type="text" class="form-control" name="cliente_direccion">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Distrito</label>
                <input type="text" class="form-control" name="cliente_distrito">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Referencia</label>
                <input type="text" class="form-control" name="cliente_referencia">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">WhatsApp Principal</label>
                <input type="tel" class="form-control" name="cliente_whatsapp" inputmode="numeric">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Celular Alternativo</label>
                <input type="tel" class="form-control" name="cliente_celular_alt" inputmode="numeric">
            </div>
            <div class="col-md-12 mb-3">
                <label class="form-label d-flex justify-content-between">Geolocalización GPS <button type="button" class="btn-ocr" id="btnGPS"><i class="ph ph-map-pin"></i> Capturar GPS</button></label>
                <div class="d-flex gap-2">
                    <input type="text" class="form-control" name="cliente_gps_lat" id="gpsLat" placeholder="Latitud" readonly>
                    <input type="text" class="form-control" name="cliente_gps_lng" id="gpsLng" placeholder="Longitud" readonly>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. PLANTA EXTERNA -->
    <div class="acta-section">
        <div class="acta-section-title">2. PLANTA EXTERNA</div>
        <div class="row">
            <div class="col-md-2 mb-3">
                <label class="form-label">Nodo/Cluster</label>
                <input type="text" class="form-control" name="pe_nodo">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">NAP/Caja</label>
                <input type="text" class="form-control" name="pe_nap">
            </div>
            <div class="col-md-2 mb-3">
                <label class="form-label">Puerto</label>
                <input type="text" class="form-control" name="pe_puerto">
            </div>
            <div class="col-md-2 mb-3">
                <label class="form-label">Potencia (dBm)</label>
                <input type="tel" class="form-control" name="pe_potencia" placeholder="ej. -21.5" inputmode="decimal">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">Atenuación Empalme</label>
                <input type="tel" class="form-control" name="pe_atenuacion" placeholder="Ej: 0.05" inputmode="decimal">
            </div>
        </div>
    </div>

    <!-- 3. DETALLES DEL SERVICIO -->
    <div class="acta-section">
        <div class="acta-section-title">3. DETALLES DEL SERVICIO</div>
        <div class="row">
            <div class="col-md-3 mb-3">
                <label class="form-label">Fecha</label>
                <input type="date" class="form-control" name="srv_fecha" value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="col-md-2 mb-3">
                <label class="form-label">Hora Inicio</label>
                <input type="time" class="form-control" name="srv_hora_inicio" id="srvHoraInicio">
            </div>
            <div class="col-md-2 mb-3">
                <label class="form-label">Hora Fin</label>
                <input type="time" class="form-control" name="srv_hora_fin" id="srvHoraFin">
            </div>
            <div class="col-md-2 mb-3">
                <label class="form-label">Tipo</label>
                <select class="form-select" name="srv_tipo">
                    <option value="Instalación">Instalación</option>
                    <option value="Mantenimiento">Mantenimiento</option>
                    <option value="Traslado">Traslado</option>
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">Estado Final</label>
                <select class="form-select" name="srv_estado">
                    <option value="Finalizada (Éxito)">Finalizada (Éxito)</option>
                    <option value="Incompleta">Incompleta</option>
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">Servicio Contratado</label>
                <select class="form-select" name="servicio_id">
                    <option value="">Sin plan</option>
                    <?php
                    $stmtSrv = $pdo->query("SELECT * FROM servicios ORDER BY nombre ASC");
                    while($srv = $stmtSrv->fetch()) {
                        $sel = ($isEdit && $editActa['servicio_id'] == $srv['id']) ? 'selected' : '';
                        $velText = $srv['velocidad'] ? " (" . htmlspecialchars($srv['velocidad']) . ")" : "";
                        echo '<option value="'.$srv['id'].'" '.$sel.'>'.htmlspecialchars($srv['nombre']) . $velText.'</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">Técnico Responsable</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Técnico'); ?>" readonly style="background-color: var(--bg-color); cursor: not-allowed; color: var(--text-muted); font-weight: 600;">
                <input type="hidden" name="tecnico_id" value="<?php echo htmlspecialchars($_SESSION['user_id'] ?? 0); ?>">
            </div>
            
            <div class="col-12 mt-3 mb-2"><strong style="color:var(--text-muted)">Configuración Red / TV</strong></div>
            <div class="col-md-3 mb-3">
                <label class="form-label d-flex justify-content-between">SSID (WiFi) <button type="button" class="btn-ocr">A OCR</button></label>
                <input type="text" class="form-control" name="red_ssid">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label d-flex justify-content-between">Password <button type="button" class="btn-ocr">A OCR</button></label>
                <input type="text" class="form-control" name="red_password">
            </div>
            <div class="col-md-2 mb-3">
                <label class="form-label">Speed DL (Mbps)</label>
                <input type="tel" class="form-control" name="red_speed_dl" inputmode="decimal">
            </div>
            <div class="col-md-2 mb-3">
                <label class="form-label">Speed UL (Mbps)</label>
                <input type="tel" class="form-control" name="red_speed_ul" inputmode="decimal">
            </div>
            <div class="col-md-2 mb-3">
                <label class="form-label">N° TVs</label>
                <input type="number" class="form-control" name="red_n_tvs" inputmode="numeric">
            </div>
            <div class="col-md-2 mb-3">
                <label class="form-label">Splitters</label>
                <input type="text" class="form-control" name="red_splitters">
            </div>
            <div class="col-md-2 mb-3">
                <label class="form-label">Señal Low</label>
                <input type="tel" class="form-control" name="red_senal_low" inputmode="decimal">
            </div>
            <div class="col-md-2 mb-3">
                <label class="form-label">Señal High</label>
                <input type="tel" class="form-control" name="red_senal_high" inputmode="decimal">
            </div>
        </div>
    </div>

    <!-- 4. EQUIPOS -->
    <div class="acta-section">
        <div class="acta-section-title">4. EQUIPOS INSTALADOS Y RETIRADOS <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddEquipo">+ Agregar</button></div>
        <div class="table-responsive">
            <table class="table" id="tablaEquipos">
                <thead>
                    <tr>
                        <th>Acción</th>
                        <th>Modelo/Marca</th>
                        <th>Serie / MAC</th>
                        <th>Propiedad</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td data-label="Acción">
                            <select class="form-select" name="equipos_accion[]">
                                <option value="Instala">Instala</option>
                                <option value="Retira">Retira</option>
                                <option value="Cambio">Cambio</option>
                                <option value="Malogrado">Malogrado</option>
                                <option value="Reparado">Reparado</option>
                                <option value="En Tránsito">En Tránsito</option>
                            </select>
                        </td>
                        <td data-label="Modelo/Marca">
                            <select class="form-select" name="equipos_modelo[]">
                                <option value="">Seleccionar...</option>
                                <?php foreach($equiposList as $eq): ?>
                                <option value="<?php echo htmlspecialchars($eq['name']); ?>"><?php echo htmlspecialchars($eq['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td data-label="Serie / MAC">
                            <div class="d-flex gap-2">
                                <input type="text" class="form-control" name="equipos_serie[]" placeholder="SN/MAC" onchange="searchSkuForActa(this)">
                                <button type="button" class="btn-ocr" onclick="openActaScanner(this, 'equipo')"><i class="ph ph-qr-code"></i></button>
                            </div>
                        </td>
                        <td data-label="Propiedad">
                            <select class="form-select" name="equipos_propiedad[]">
                                <option value="Alquiler">Alquiler</option>
                                <option value="Venta">Venta</option>
                                <option value="Cliente">Cliente</option>
                            </select>
                        </td>
                        <td><button type="button" class="table-btn-danger btn-remove-row">&times;</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <button type="button" class="btn btn-outline-primary w-100 mt-3 d-md-none" style="border-style: dashed; font-weight: bold;" onclick="document.getElementById('btnAddEquipo').click()">+ Agregar Equipo</button>
    </div>

    <!-- 5. MATERIALES -->
    <datalist id="bulkMaterialsList">
        <?php foreach($materialesList as $mat): ?>
        <option value="<?php echo htmlspecialchars($mat['name']); ?>" data-unit="<?php echo htmlspecialchars($mat['unit_type']); ?>"></option>
        <?php endforeach; ?>
    </datalist>

    <div class="acta-section">
        <div class="acta-section-title">5. MATERIALES <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddMaterial">+ Agregar</button></div>
        <div class="table-responsive">
            <table class="table" id="tablaMateriales">
                <thead>
                    <tr>
                        <th>Acción</th>
                        <th>Descripción</th>
                        <th>Cant.</th>
                        <th>Und.</th>
                        <th>Propiedad</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td data-label="Acción">
                            <select class="form-select" name="mat_accion[]">
                                <option value="Instala">Instala</option>
                                <option value="Retira">Retira</option>
                            </select>
                        </td>
                        <td data-label="Descripción">
                            <div class="d-flex gap-2">
                                <input type="text" class="form-control" name="mat_desc[]" list="bulkMaterialsList" placeholder="Escribir o seleccionar..." onchange="autoFillMaterialUnit(this)">
                                <button type="button" class="btn-ocr" onclick="openActaScanner(this, 'material')"><i class="ph ph-qr-code"></i></button>
                            </div>
                        </td>
                        <td data-label="Cant."><input type="number" class="form-control" name="mat_cant[]" value="1" step="0.01"></td>
                        <td data-label="Und.">
                            <input type="text" class="form-control" name="mat_und[]" placeholder="Und">
                        </td>
                        <td data-label="Propiedad">
                            <select class="form-select" name="mat_propiedad[]">
                                <option value="Alquiler">Alquiler</option>
                                <option value="Venta">Venta</option>
                                <option value="Cliente">Cliente</option>
                            </select>
                        </td>
                        <td><button type="button" class="table-btn-danger btn-remove-row">&times;</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <button type="button" class="btn btn-outline-primary w-100 mt-3 d-md-none" style="border-style: dashed; font-weight: bold;" onclick="document.getElementById('btnAddMaterial').click()">+ Agregar Material</button>
    </div>

    <!-- 6. EVIDENCIAS Y OBSERVACIONES -->
    <div class="acta-section">
        <div class="acta-section-title">6. EVIDENCIAS Y OBSERVACIONES</div>
        
        <div class="mb-3">
            <label class="form-label"><i class="ph ph-router"></i> Foto Router / Equipos</label>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-primary w-50" onclick="document.querySelector('input[name=foto_router]').click()"><i class="ph ph-image"></i> Galería</button>
                <button type="button" class="btn btn-outline-primary w-50" onclick="window.openCameraModal('Foto Router / Equipos', 'photo', 'foto_router')"><i class="ph ph-camera"></i> Cámara</button>
            </div>
            <input type="file" name="foto_router" accept="image/*" class="d-none" onchange="previewFiles(this, 'preview_foto_router')">
            <div id="preview_foto_router" class="mt-2 d-flex gap-2 overflow-auto py-1"></div>
        </div>
        
        <div class="mb-3">
            <label class="form-label"><i class="ph ph-house"></i> Foto Exterior / Fachada</label>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-primary w-50" onclick="document.querySelector('input[name=foto_fachada]').click()"><i class="ph ph-image"></i> Galería</button>
                <button type="button" class="btn btn-outline-primary w-50" onclick="window.openCameraModal('Foto Exterior / Fachada', 'photo', 'foto_fachada')"><i class="ph ph-camera"></i> Cámara</button>
            </div>
            <input type="file" name="foto_fachada" accept="image/*" class="d-none" onchange="previewFiles(this, 'preview_foto_fachada')">
            <div id="preview_foto_fachada" class="mt-2 d-flex gap-2 overflow-auto py-1"></div>
        </div>
        
        <div class="mb-3">
            <label class="form-label"><i class="ph ph-images"></i> Fotos Adicionales</label>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-primary w-50" onclick="document.querySelector('input[name=foto_adicional]').click()"><i class="ph ph-image"></i> Galería</button>
                <button type="button" class="btn btn-outline-primary w-50" onclick="window.openCameraModal('Fotos Adicionales', 'photo', 'foto_adicional')"><i class="ph ph-camera"></i> Cámara</button>
            </div>
            <input type="file" name="foto_adicional" accept="image/*" class="d-none" multiple onchange="previewFiles(this, 'preview_foto_adicional')">
            <div id="preview_foto_adicional" class="mt-2 d-flex gap-2 overflow-auto py-1"></div>
        </div>

        <div class="mb-3 mt-4">
            <label class="form-label">Observaciones Adicionales</label>
            <textarea class="form-control" name="observaciones" rows="4"></textarea>
        </div>
    </div>

    <!-- 7. CONFORMIDAD Y FIRMAS -->
    <div class="acta-section">
        <div class="acta-section-title">7. CONFORMIDAD Y FIRMAS</div>
        
        <div class="form-check mb-4">
            <input class="form-check-input" type="checkbox" name="mantenimiento_6_meses" value="1" id="mant_6m">
            <label class="form-check-label" for="mant_6m" style="font-weight: 500;">
                Programar recordatorio de mantenimiento preventivo en 6 meses
            </label>
        </div>
        
        <div class="file-input-wrapper mb-4 d-flex justify-content-between align-items-center">
            <span style="font-weight: 500;">Calificación del Servicio (Opcional)</span>
            <div class="rating-stars" id="ratingStars">
                <i class="ph ph-star" data-value="1"></i>
                <i class="ph ph-star" data-value="2"></i>
                <i class="ph ph-star" data-value="3"></i>
                <i class="ph ph-star" data-value="4"></i>
                <i class="ph ph-star" data-value="5"></i>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-6 mb-3">
                <div class="d-flex justify-content-between mb-2">
                    <label class="form-label m-0">Firma del Cliente</label>
                    <button type="button" class="btn-ocr" onclick="clearPad(clientePad)"><i class="ph ph-eraser"></i> Borrar</button>
                </div>
                <div class="signature-pad-container">
                    <canvas id="padCliente" class="signature-pad"></canvas>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="d-flex justify-content-between mb-2">
                    <label class="form-label m-0">Firma del Técnico</label>
                    <button type="button" class="btn-ocr" onclick="clearPad(tecnicoPad)"><i class="ph ph-eraser"></i> Borrar</button>
                </div>
                <div class="signature-pad-container">
                    <canvas id="padTecnico" class="signature-pad"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Fixed Footer for Actions -->
    <div id="actaFooterBar" style="position: fixed; bottom: 0; left: 0; right: 0; z-index: 999; background: var(--bg-color); padding: 12px 15px; border-top: 1px solid var(--border-color); display: flex; gap: 12px; justify-content: flex-end; align-items: center; box-shadow: 0 -4px 10px rgba(0,0,0,0.1);">
        <!-- Cronómetro display -->
        <div id="chronoDisplay" style="display:none; margin-right:auto; font-family:'Roboto Mono',monospace; font-size:1.1rem; font-weight:bold; color:#ea580c; background:rgba(234,88,12,0.1); padding:8px 16px; border-radius:8px; border:1px solid rgba(234,88,12,0.2);">
            <i class="ph ph-timer" style="margin-right:4px;"></i> <span id="chronoTime">00:00</span>
        </div>
        
        <button type="button" class="btn d-flex align-items-center" id="btnStopwatch" style="background:transparent;color:#ea580c;border:2px solid #ea580c;font-weight:bold;padding:10px 20px;border-radius:8px;" onclick="toggleStopwatch()">
            <i class="ph ph-play" id="btnStopwatchIcon" style="font-size:1.2rem;margin-right:6px;"></i> <span id="btnStopwatchText">Iniciar</span>
        </button>
        <button type="button" class="btn d-flex align-items-center" id="btnResetStopwatch" style="display:none;background:transparent;color:#64748b;border:1px solid #64748b;font-weight:bold;padding:10px 16px;border-radius:8px;" onclick="resetStopwatch()">
            <i class="ph ph-arrow-counter-clockwise" style="font-size:1.2rem;margin-right:6px;"></i> Reiniciar
        </button>
        <button type="submit" class="btn d-flex align-items-center" style="background:#1e3a8a;color:white;font-weight:bold;padding:10px 20px;border-radius:8px;border:none;">
            <i class="ph-fill ph-check-circle" style="font-size:1.2rem;margin-right:6px;"></i> Guardar Cambios
        </button>
    </div>
</form>

<!-- Espaciador para que el footer no tape el contenido -->
<div style="height: 80px;"></div>

<!-- Modal de Cámara / Escáner -->
<div class="modal-overlay" id="cameraModal">
    <div class="modal-content" style="max-width: 400px; padding: 20px;">
        <h3 class="text-center mb-3" style="font-size: 1.2rem; font-weight: 800; color: var(--text-color);" id="cameraModalTitle">Escanear Mac/Serie</h3>
        
        <div class="camera-viewport" style="background: #1e293b; height: 300px; border-radius: 12px; position: relative; margin-bottom: 20px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
            <!-- Real Camera Video -->
            <video id="cameraVideo" autoplay playsinline style="position: absolute; width: 100%; height: 100%; object-fit: cover; z-index: 1;"></video>
            
            <!-- Marco de enfoque -->
            <div id="cameraTargetBox" style="position: absolute; width: 80%; height: 30%; border: 2px solid rgba(255,255,255,0.3); box-shadow: 0 0 0 9999px rgba(15, 23, 42, 0.6); z-index: 2; pointer-events: none;">
               <div style="position: absolute; top: -4px; left: -4px; width: 25px; height: 25px; border-top: 4px solid white; border-left: 4px solid white;"></div>
               <div style="position: absolute; top: -4px; right: -4px; width: 25px; height: 25px; border-top: 4px solid white; border-right: 4px solid white;"></div>
               <div style="position: absolute; bottom: -4px; left: -4px; width: 25px; height: 25px; border-bottom: 4px solid white; border-left: 4px solid white;"></div>
               <div style="position: absolute; bottom: -4px; right: -4px; width: 25px; height: 25px; border-bottom: 4px solid white; border-right: 4px solid white;"></div>
            </div>
            
            <button id="btnTakePhoto" type="button" class="d-none" style="position: absolute; bottom: 15px; width: 60px; height: 60px; border-radius: 50%; border: 4px solid #ea580c; background: white; cursor: pointer; z-index: 10;"></button>
        </div>
        
        <div class="p-3 rounded mb-3" style="background: var(--bg-color); border: 1px solid var(--border-color); width: 100%;">
            <div class="d-flex justify-content-between mb-2">
                <span style="font-weight: 700; font-size: 0.9rem;">Zoom</span>
                <span style="color: #ea580c; font-weight: 700; font-size: 0.9rem;" id="zoomValueText">1x</span>
            </div>
            <input type="range" id="cameraZoomSlider" style="width: 100%; accent-color: #ea580c;" min="1" max="5" value="1" step="0.1">
        </div>

        <div class="mb-3 d-none" id="cameraThumbnailsContainer" style="width: 100%; overflow-x: auto; white-space: nowrap; padding-bottom: 5px;">
            <!-- Miniaturas irán aquí -->
        </div>

        <div class="mb-4" id="cameraDetectedCodesContainer" style="width: 100%;">
            <label class="form-label" style="font-size: 0.85rem; color: var(--text-muted);">Códigos detectados:</label>
            <input type="text" class="form-control" style="border: none; border-bottom: 1px solid var(--border-color); border-radius: 0; padding-left: 0; background: transparent;">
        </div>

        <div class="d-flex gap-3 mt-4" style="width: 100%;">
            <button type="button" class="btn" style="flex: 1; background: #ef4444; color: white; font-weight: bold; border-radius: 8px; padding: 12px 0;" onclick="closeCameraModal()">Cancelar</button>
            <button type="button" id="btnCameraReady" class="btn d-none" style="flex: 1; background: #ea580c; color: white; font-weight: bold; border-radius: 8px; padding: 12px 0;" onclick="closeCameraModal()">Listo (0)</button>
        </div>
        
        <canvas id="cameraCanvas" style="display: none;"></canvas>
    </div>
</div>

<!-- Modal de Éxito -->
<div class="modal-overlay" id="successModal">
    <div class="modal-content">
        <div class="modal-header" style="justify-content: center; border-bottom: none; padding-bottom: 0;">
            <h2 style="color: var(--text-color); margin: 0; font-size: 1.5rem; text-align: center; width: 100%;">Acta Creada Exitosamente</h2>
            <button type="button" class="close-modal" style="position: absolute; right: 20px; top: 20px; background: transparent; border: none; font-size: 1.5rem; color: var(--text-muted); cursor: pointer;" onclick="window.location.href='index.php'">&times;</button>
        </div>
        <div class="modal-body text-center" style="padding-top: 10px;">
            <div class="success-icon" style="margin-bottom: 10px;"><i class="ph-fill ph-check-circle"></i></div>
            <div class="success-folio" id="successFolioText">LIM-000000</div>
            
            <div class="btn-group-vertical mt-4" style="width: 100%; max-width: 320px; margin: 0 auto; gap: 12px;">
                <button class="btn" style="background: var(--primary-color); color: white; padding: 12px; font-weight: 600; font-size: 0.95rem; justify-content: center; width: 100%; border: none;" type="button" id="btnSharePublic">
                    <i class="ph ph-link" style="font-size: 1.2rem;"></i> Compartir (Vista Pública)
                </button>
                <button class="btn" style="background: var(--bg-color); color: var(--text-color); border: 1px solid var(--border-color); padding: 12px; font-weight: 600; font-size: 0.95rem; justify-content: center; width: 100%;" type="button" id="btnCopyLink">
                    <i class="ph ph-copy" style="font-size: 1.2rem;"></i> Copiar Enlace
                </button>
                <button class="btn" style="background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); padding: 12px; font-weight: 600; font-size: 0.95rem; justify-content: center; width: 100%;" type="button" id="btnDownloadPDF">
                    <i class="ph ph-file-pdf" style="font-size: 1.2rem;"></i> Descargar PDF
                </button>
            </div>
        </div>
        <div class="modal-footer" style="justify-content: center; border-top: 1px solid var(--border-color); background: transparent;">
            <button class="btn" style="background: var(--primary-color); color: white; border: none; font-weight: 600; padding: 12px 24px; border-radius: 12px; width: 100%;" type="button" onclick="if(window.parent && window.parent.closeTechAppModal) { window.parent.closeTechAppModal(); } else { window.location.href='../actas/index.php'; }">
                Finalizar y Cerrar
            </button>
        </div>
        </div>
    </div>
</div>

<!-- Modal Reniec/Sunat -->
<div class="modal-overlay" id="reniecModal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3 id="reniecModalTitle">Buscar en RENIEC/SUNAT</h3>
            <button class="btn close-modal" style="background:transparent; border:none; font-size:1.5rem; cursor:pointer;" onclick="document.getElementById('reniecModal').classList.remove('active')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="mb-3">
                <label class="form-label">Ingrese DNI o RUC</label>
                <div class="d-flex gap-2">
                    <input type="tel" id="reniecModalInput" class="form-control" placeholder="DNI (8 dígitos) o RUC (11 dígitos)" inputmode="numeric">
                    <button type="button" class="btn btn-primary" id="btnReniecModalSearch" style="white-space: nowrap;"><i class="ph ph-magnifying-glass"></i> Buscar</button>
                </div>
            </div>
            
            <div id="reniecResultCard" style="display: none; border: 1px solid var(--border-color); border-radius: 8px; padding: 15px; background: var(--bg-color); margin-top: 20px;">
                <div class="d-flex align-items-center mb-3">
                    <div style="background: rgba(234, 88, 12, 0.1); color: #ea580c; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 12px; flex-shrink: 0;">
                        <i class="ph ph-identification-card" style="font-size: 1.5rem;" id="reniecIcon"></i>
                    </div>
                    <div>
                        <h4 id="reniecResultName" style="margin: 0; font-size: 1.1rem; font-weight: 700; color: var(--text-color);"></h4>
                        <span id="reniecResultDoc" class="text-muted" style="font-size: 0.85rem;"></span>
                    </div>
                </div>
                
                <div id="reniecResultExtra" style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 15px; display: none;">
                    <div style="margin-bottom: 5px;"><strong>Dirección:</strong> <span id="reniecResultDir"></span></div>
                    <div><strong>Distrito:</strong> <span id="reniecResultDist"></span></div>
                </div>
                
                <button type="button" class="btn btn-primary w-100" id="btnReniecModalApply">
                    <i class="ph ph-check-circle"></i> Confirmar y Agregar
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
<script src="https://unpkg.com/html5-qrcode"></script>
<script>
let clientePad, tecnicoPad;

let stopwatchInterval;
const ACTA_FOLIO = '<?php echo $nextFolio; ?>';
const ACTA_ID = '<?php echo $isEdit && $editActa ? $editActa['id'] : 'new_' . $nextFolio; ?>';
const SW_KEY_START = 'acta_sw_' + ACTA_ID + '_start';
const SW_KEY_INICIO = 'acta_sw_' + ACTA_ID + '_inicio';

// Auto-cleanup: remove any stale chronometer keys older than 24 hours
(function cleanStaleChronometers() {
    for (let i = localStorage.length - 1; i >= 0; i--) {
        const key = localStorage.key(i);
        if (key && key.startsWith('acta_sw_') && key.endsWith('_start')) {
            const val = localStorage.getItem(key);
            if (val) {
                const startDate = new Date(val);
                const hoursElapsed = (Date.now() - startDate.getTime()) / (1000 * 60 * 60);
                if (hoursElapsed > 24) {
                    const baseKey = key.replace('_start', '');
                    localStorage.removeItem(key);
                    localStorage.removeItem(baseKey + '_inicio');
                    console.log('Cleaned stale chronometer:', key);
                }
            }
        }
    }
})();

function getChronoDisplay() {
    const startStr = localStorage.getItem(SW_KEY_START);
    if (!startStr) return null;
    const startObj = new Date(startStr);
    const now = new Date();
    let diffMs = now - startObj;
    if (diffMs < 0) diffMs = 0;
    
    let totalSecs = Math.floor(diffMs / 1000);
    let hh = Math.floor(totalSecs / 3600);
    let mm = Math.floor((totalSecs % 3600) / 60);
    let ss = totalSecs % 60;
    
    let disp = '';
    if (hh > 0) disp += String(hh).padStart(2, '0') + ':';
    disp += String(mm).padStart(2, '0') + ':' + String(ss).padStart(2, '0');
    return disp;
}

function updateStopwatchDisplay() {
    const disp = getChronoDisplay();
    if (disp) {
        document.getElementById('chronoTime').textContent = disp;
    }
}

function setButtonState(state) {
    const btn = document.getElementById('btnStopwatch');
    const btnText = document.getElementById('btnStopwatchText');
    const btnIcon = document.getElementById('btnStopwatchIcon');
    const btnReset = document.getElementById('btnResetStopwatch');
    const chronoDisp = document.getElementById('chronoDisplay');
    
    if (state === 'idle') {
        btn.style.background = 'transparent';
        btn.style.color = '#ea580c';
        btn.style.borderColor = '#ea580c';
        btn.disabled = false;
        btnIcon.className = 'ph ph-play';
        btnText.textContent = 'Iniciar';
        chronoDisp.style.display = 'none';
        btnReset.style.display = 'none';
    } else if (state === 'running') {
        btn.style.background = '#fef2f2';
        btn.style.color = '#ef4444';
        btn.style.borderColor = '#ef4444';
        btn.disabled = false;
        btnIcon.className = 'ph ph-stop-circle';
        btnText.textContent = 'Parar';
        chronoDisp.style.display = 'flex';
        btnReset.style.display = 'none';
    } else if (state === 'done') {
        btn.style.background = '#f0fdf4';
        btn.style.color = '#22c55e';
        btn.style.borderColor = '#22c55e';
        btn.disabled = true;
        btnIcon.className = 'ph ph-check-circle';
        btnText.textContent = 'Finalizado';
        chronoDisp.style.display = 'flex';
        chronoDisp.style.color = '#22c55e';
        chronoDisp.style.borderColor = 'rgba(34,197,94,0.2)';
        chronoDisp.style.background = 'rgba(34,197,94,0.1)';
        btnReset.style.display = 'flex';
    }
}

window.toggleStopwatch = function() {
    const tInicio = document.getElementById('srvHoraInicio');
    const tFin = document.getElementById('srvHoraFin');
    const now = new Date();
    const hh = String(now.getHours()).padStart(2, '0');
    const mm = String(now.getMinutes()).padStart(2, '0');
    
    if (!localStorage.getItem(SW_KEY_START)) {
        // Iniciar
        tInicio.value = `${hh}:${mm}`;
        localStorage.setItem(SW_KEY_START, now.toISOString());
        localStorage.setItem(SW_KEY_INICIO, `${hh}:${mm}`);
        setButtonState('running');
        stopwatchInterval = setInterval(updateStopwatchDisplay, 1000);
    } else {
        // Parar
        clearInterval(stopwatchInterval);
        tFin.value = `${hh}:${mm}`;
        localStorage.removeItem(SW_KEY_START);
        localStorage.removeItem(SW_KEY_INICIO);
        setButtonState('done');
    }
};

window.resetStopwatch = function() {
    clearInterval(stopwatchInterval);
    document.getElementById('srvHoraInicio').value = '';
    document.getElementById('srvHoraFin').value = '';
    localStorage.removeItem(SW_KEY_START);
    localStorage.removeItem(SW_KEY_INICIO);
    setButtonState('idle');
    document.getElementById('chronoDisplay').style.color = '#ea580c';
    document.getElementById('chronoDisplay').style.borderColor = 'rgba(234,88,12,0.2)';
    document.getElementById('chronoDisplay').style.background = 'rgba(234,88,12,0.1)';
};

document.addEventListener('DOMContentLoaded', () => {
    // Extracted function so it can be called again after edit mode prefill
    window.restoreStopwatchFromDB = function() {
        if (localStorage.getItem(SW_KEY_START)) return; // Already handled by localStorage
        const horaInicio = document.getElementById('srvHoraInicio').value;
        const horaFin = document.getElementById('srvHoraFin').value;
        const isRealTime = (t) => t && t !== '' && t !== '00:00' && t !== '00:00:00';
        
        if (isRealTime(horaInicio) && isRealTime(horaFin)) {
            const [hI, mI] = horaInicio.split(':').map(Number);
            const [hF, mF] = horaFin.split(':').map(Number);
            let diffMins = (hF * 60 + mF) - (hI * 60 + mI);
            if (diffMins < 0) diffMins += 24 * 60;
            const dH = Math.floor(diffMins / 60);
            const dM = diffMins % 60;
            let disp = '';
            if (dH > 0) disp += String(dH).padStart(2, '0') + ':';
            disp += String(dM).padStart(2, '0') + ':00';
            const chronoEl = document.getElementById('chronoTime');
            if (chronoEl) chronoEl.textContent = disp;
            setButtonState('done');
        } else if (isRealTime(horaInicio) && !isRealTime(horaFin)) {
            const srvFechaInput = document.querySelector('input[name="srv_fecha"]');
            const srvFecha = srvFechaInput ? srvFechaInput.value : '';
            if (srvFecha) {
                const startISO = srvFecha + 'T' + horaInicio;
                localStorage.setItem(SW_KEY_START, new Date(startISO).toISOString());
                localStorage.setItem(SW_KEY_INICIO, horaInicio);
                setButtonState('running');
                updateStopwatchDisplay();
                stopwatchInterval = setInterval(updateStopwatchDisplay, 1000);
            }
        }
    };

    // Restaurar estado del cronómetro para ESTA acta
    if (localStorage.getItem(SW_KEY_START)) {
        const tInicio = document.getElementById('srvHoraInicio');
        const savedInicio = localStorage.getItem(SW_KEY_INICIO);
        if (savedInicio && tInicio) tInicio.value = savedInicio;
        setButtonState('running');
        updateStopwatchDisplay();
        stopwatchInterval = setInterval(updateStopwatchDisplay, 1000);
    } else {
        restoreStopwatchFromDB();
    }
    // Inicializar Canvas
    const canvasCliente = document.getElementById('padCliente');
    const canvasTecnico = document.getElementById('padTecnico');
    
    function resizeCanvas(canvas) {
        const ratio =  Math.max(window.devicePixelRatio || 1, 1);
        canvas.width = canvas.offsetWidth * ratio;
        canvas.height = canvas.offsetHeight * ratio;
        canvas.getContext("2d").scale(ratio, ratio);
    }

    resizeCanvas(canvasCliente);
    resizeCanvas(canvasTecnico);

    window.addEventListener("resize", () => {
        const clienteData = clientePad ? clientePad.toDataURL() : null;
        const tecnicoData = tecnicoPad ? tecnicoPad.toDataURL() : null;
        resizeCanvas(canvasCliente);
        resizeCanvas(canvasTecnico);
        if (clientePad && clienteData) clientePad.fromDataURL(clienteData);
        if (tecnicoPad && tecnicoData) tecnicoPad.fromDataURL(tecnicoData);
    });

    clientePad = new SignaturePad(canvasCliente, { backgroundColor: 'rgb(255, 255, 255)', penColor: 'rgb(0, 0, 0)' });
    tecnicoPad = new SignaturePad(canvasTecnico, { backgroundColor: 'rgb(255, 255, 255)', penColor: 'rgb(0, 0, 0)' });
    
    window.clearPad = (pad) => {
        pad.clear();
    };

    // Estrellas
    const stars = document.querySelectorAll('#ratingStars i');
    const inputCalificacion = document.getElementById('inputCalificacion');
    
    stars.forEach(star => {
        star.addEventListener('click', (e) => {
            const val = parseInt(e.target.getAttribute('data-value'));
            inputCalificacion.value = val;
            stars.forEach(s => {
                if (parseInt(s.getAttribute('data-value')) <= val) {
                    s.classList.replace('ph', 'ph-fill');
                } else {
                    s.classList.replace('ph-fill', 'ph');
                }
            });
        });
    });

    const isEditMode = <?php echo $isEdit ? 'true' : 'false'; ?>;
    const editActaData = <?php echo $editActa ? json_encode($editActa) : 'null'; ?>;
    const editEquipos = <?php echo json_encode($editEquipos); ?>;
    const editMateriales = <?php echo json_encode($editMateriales); ?>;

    if (isEditMode && editActaData) {
        // Prefill form fields
        const form = document.getElementById('actaForm');
        Object.keys(editActaData).forEach(key => {
            const input = form.elements[key];
            if (input && input.type !== 'file' && input.type !== 'checkbox') {
                input.value = editActaData[key];
            }
        });

        // Prefill Checkboxes
        if (editActaData.mantenimiento_6_meses == 1) {
            form.elements['mantenimiento_6_meses'].checked = true;
        }

        // Prefill Calificacion
        if (editActaData.calificacion_servicio) {
            inputCalificacion.value = editActaData.calificacion_servicio;
            stars.forEach(s => {
                if (parseInt(s.getAttribute('data-value')) <= editActaData.calificacion_servicio) {
                    s.classList.replace('ph', 'ph-fill');
                }
            });
        }

        // Signatures (Base64 is typically big, we can pre-load canvas if we have base64)
        if (editActaData.firma_cliente) {
            clientePad.fromDataURL(editActaData.firma_cliente);
        }
        if (editActaData.firma_tecnico) {
            tecnicoPad.fromDataURL(editActaData.firma_tecnico);
        }

        // Prefill Equipos
        if (editEquipos.length > 0) {
            const tbodyEq = document.querySelector('#tablaEquipos tbody');
            tbodyEq.innerHTML = '';
            editEquipos.forEach(eq => {
                tbodyEq.innerHTML += `
                    <tr>
                        <td data-label="Acción">
                            <select class="form-select" name="equipos_accion[]">
                                <option value="Instala" ${eq.accion === 'Instala' ? 'selected' : ''}>Instala</option>
                                <option value="Retira" ${eq.accion === 'Retira' ? 'selected' : ''}>Retira</option>
                            </select>
                        </td>
                        <td data-label="Modelo/Marca">
                            <select class="form-select" name="equipos_modelo[]">
                                <option value="">Seleccionar...</option>
                                <option value="Router Huawei" ${eq.modelo_marca === 'Router Huawei' ? 'selected' : ''}>Router Huawei</option>
                            </select>
                        </td>
                        <td data-label="Serie / MAC">
                            <div class="d-flex gap-2">
                                <input type="text" class="form-control" name="equipos_serie[]" value="${eq.serie_mac || ''}">
                                <button type="button" class="btn-ocr"><i class="ph ph-qr-code"></i></button>
                            </div>
                        </td>
                        <td data-label="Propiedad">
                            <select class="form-select" name="equipos_propiedad[]">
                                <option value="Alquiler" ${eq.propiedad === 'Alquiler' ? 'selected' : ''}>Alquiler</option>
                                <option value="Venta" ${eq.propiedad === 'Venta' ? 'selected' : ''}>Venta</option>
                                <option value="Cliente" ${eq.propiedad === 'Cliente' ? 'selected' : ''}>Cliente</option>
                            </select>
                        </td>
                        <td><button type="button" class="table-btn-danger btn-remove-row">&times;</button></td>
                    </tr>
                `;
            });
        }

        // Prefill Materiales
        if (editMateriales.length > 0) {
            const tbodyMat = document.querySelector('#tablaMateriales tbody');
            tbodyMat.innerHTML = '';
            editMateriales.forEach(mat => {
                tbodyMat.innerHTML += `
                    <tr>
                        <td data-label="Acción">
                            <select class="form-select" name="mat_accion[]">
                                <option value="Instala" ${mat.accion === 'Instala' ? 'selected' : ''}>Instala</option>
                                <option value="Retira" ${mat.accion === 'Retira' ? 'selected' : ''}>Retira</option>
                            </select>
                        </td>
                        <td data-label="Descripción">
                            <div class="d-flex gap-2">
                                <input type="text" class="form-control" name="mat_desc[]" list="bulkMaterialsList" value="${mat.descripcion || ''}">
                                <button type="button" class="btn-ocr" onclick="openActaScanner(this, 'material')"><i class="ph ph-qr-code"></i></button>
                            </div>
                        </td>
                        <td data-label="Cant."><input type="number" class="form-control" name="mat_cant[]" value="${mat.cantidad || 1}"></td>
                        <td data-label="Und.">
                            <input type="text" class="form-control" name="mat_und[]" value="${mat.unidad || ''}">
                        </td>
                        <td data-label="Propiedad">
                            <select class="form-select" name="mat_propiedad[]">
                                <option value="Alquiler" ${mat.propiedad === 'Alquiler' ? 'selected' : ''}>Alquiler</option>
                                <option value="Venta" ${mat.propiedad === 'Venta' ? 'selected' : ''}>Venta</option>
                                <option value="Cliente" ${mat.propiedad === 'Cliente' ? 'selected' : ''}>Cliente</option>
                            </select>
                        </td>
                        <td><button type="button" class="table-btn-danger btn-remove-row">&times;</button></td>
                    </tr>
                `;
            });
        }
        
        // Change submit button text
        const submitBtn = document.querySelector('#actaFooterBar button[type="submit"]');
        if (submitBtn) submitBtn.innerHTML = '<i class="ph-fill ph-check-circle" style="font-size:1.2rem;margin-right:6px;"></i> Guardar Cambios';

        // Restore chronometer state AFTER form fields are prefilled
        restoreStopwatchFromDB();
    }

    // Agregar filas dinámicas
    document.getElementById('btnAddEquipo').addEventListener('click', () => {
        const tbody = document.querySelector('#tablaEquipos tbody');
        const tr = document.createElement('tr');
        tr.innerHTML = tbody.querySelector('tr').innerHTML;
        // Limpiar inputs
        tr.querySelectorAll('input').forEach(i => i.value = '');
        tbody.appendChild(tr);
    });

    document.getElementById('btnAddMaterial').addEventListener('click', () => {
        const tbody = document.querySelector('#tablaMateriales tbody');
        const tr = document.createElement('tr');
        tr.innerHTML = tbody.querySelector('tr').innerHTML;
        tr.querySelectorAll('input').forEach(i => i.value = '');
        tbody.appendChild(tr);
    });

    // Remover fila
    document.addEventListener('click', (e) => {
        if(e.target.classList.contains('btn-remove-row') || e.target.closest('.btn-remove-row')) {
            const btn = e.target.classList.contains('btn-remove-row') ? e.target : e.target.closest('.btn-remove-row');
            const tbody = btn.closest('tbody');
            if (tbody.querySelectorAll('tr').length > 1) {
                btn.closest('tr').remove();
            } else {
                window.showToast('Debe dejar al menos una fila.', 'info');
            }
        }
    });

    // ── Búsqueda y Autocompletado de Inventario ──
    window.searchSkuForActa = async function(inputElem) {
        const code = inputElem.value.trim();
        if(!code) return;
        try {
            const fd = new FormData(); fd.append('action', 'search_sku'); fd.append('code', code);
            const res = await fetch('<?php echo BASE_URL; ?>/ajax/inventario.php', { method: 'POST', body: fd }).then(x => x.json());
            if(res.success && res.data) {
                const tr = inputElem.closest('tr');
                const modelSelect = tr.querySelector('select[name="equipos_modelo[]"]');
                if (modelSelect && (!modelSelect.value || modelSelect.value === '')) {
                    // Try to match by value
                    let found = false;
                    for (let i = 0; i < modelSelect.options.length; i++) {
                        if (modelSelect.options[i].value === res.data.product_name) {
                            modelSelect.selectedIndex = i;
                            found = true;
                            break;
                        }
                    }
                    if (!found) {
                        const opt = new Option(res.data.product_name, res.data.product_name, true, true);
                        modelSelect.add(opt);
                    }
                }
                window.showToast('Equipo detectado: ' + res.data.product_name, 'success');
            }
        } catch (e) { console.error(e); }
    };

    window.autoFillMaterialUnit = function(inputElem) {
        const val = inputElem.value;
        const datalist = document.getElementById('bulkMaterialsList');
        const option = Array.from(datalist.options).find(opt => opt.value === val);
        if (option) {
            const tr = inputElem.closest('tr');
            const unitInput = tr.querySelector('input[name="mat_und[]"]');
            if (unitInput) {
                unitInput.value = option.getAttribute('data-unit') || 'Und';
            }
        }
    };

    let currentScannerTargetInput = null;
    window.openActaScanner = function(btnElem, type) {
        const tr = btnElem.closest('tr');
        if (type === 'equipo') {
            currentScannerTargetInput = tr.querySelector('input[name="equipos_serie[]"]');
        } else if (type === 'material') {
            currentScannerTargetInput = tr.querySelector('input[name="mat_desc[]"]');
        }
        document.getElementById('cameraModalTitle').innerText = type === 'equipo' ? 'Escanear MAC/Serie' : 'Escanear Material';
        window.openCameraModal('Escáner', 'scanner', 'scanner_input');
    };


    // Geolocalización
    document.getElementById('btnGPS').addEventListener('click', () => {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(pos => {
                document.getElementById('gpsLat').value = pos.coords.latitude;
                document.getElementById('gpsLng').value = pos.coords.longitude;
                window.showToast('GPS Capturado', 'success');
            }, err => {
                window.showToast('No se pudo obtener ubicación', 'error');
            });
        }
    });

    let currentStream = null;
    let capturedPhotos = [];
    let currentPhotoTarget = '';

    window.previewFiles = (input, containerId) => {
        const container = document.getElementById(containerId);
        container.innerHTML = '';
        if (input.files) {
            Array.from(input.files).forEach(file => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    container.innerHTML += `<img src="${e.target.result}" style="height: 60px; width: 60px; object-fit: cover; border-radius: 8px; border: 1px solid var(--border-color);">`;
                };
                reader.readAsDataURL(file);
            });
        }
    };

    window.closeCameraModal = () => {
        document.getElementById('cameraModal').classList.remove('active');
        if (currentStream) {
            currentStream.getTracks().forEach(track => track.stop());
            currentStream = null;
        }
        if (html5QrCode) {
            html5QrCode.stop().then(() => {
                html5QrCode.clear();
                html5QrCode = null;
            }).catch(()=>{});
        }
    };

    // Agregar evento para el botón "Listo"
    document.getElementById('btnCameraReady').addEventListener('click', () => {
        if (capturedPhotos.length > 0 && currentPhotoTarget) {
            const container = document.getElementById(`preview_${currentPhotoTarget}`);
            const form = document.getElementById('actaForm');
            
            capturedPhotos.forEach((photoDataUrl, index) => {
                // Crear imagen en previsualización
                container.innerHTML += `<img src="${photoDataUrl}" style="height: 60px; width: 60px; object-fit: cover; border-radius: 8px; border: 1px solid var(--border-color);">`;
                
                // Crear input oculto
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = `${currentPhotoTarget}_base64[]`;
                hiddenInput.value = photoDataUrl;
                form.appendChild(hiddenInput);
            });
        }
        closeCameraModal();
    });

    let html5QrCode = null;

    window.openCameraModal = async (title, mode, target = '') => {
        document.getElementById('cameraModalTitle').textContent = title;
        const targetBox = document.getElementById('cameraTargetBox');
        const detectedContainer = document.getElementById('cameraDetectedCodesContainer');
        const btnReady = document.getElementById('btnCameraReady');
        const btnPhoto = document.getElementById('btnTakePhoto');
        const videoElement = document.getElementById('cameraVideo');
        const thumbsContainer = document.getElementById('cameraThumbnailsContainer');
        
        capturedPhotos = [];
        currentPhotoTarget = target;
        thumbsContainer.innerHTML = '';
        thumbsContainer.classList.add('d-none');
        btnReady.textContent = 'Listo (0)';

        document.getElementById('cameraModal').classList.add('active');

        if (mode === 'scanner') {
            targetBox.style.width = '80%';
            targetBox.style.height = '30%';
            targetBox.style.display = 'block';
            detectedContainer.style.display = 'block';
            btnReady.style.display = 'none';
            btnPhoto.style.display = 'none';
            videoElement.style.display = 'none'; // hide normal video for scanner

            if (!document.getElementById('qr-reader-acta')) {
                const qrContainer = document.createElement('div');
                qrContainer.id = 'qr-reader-acta';
                qrContainer.style.position = 'absolute';
                qrContainer.style.width = '100%';
                qrContainer.style.height = '100%';
                videoElement.parentElement.appendChild(qrContainer);
            }

            let isScanningActivo = true;
            if (html5QrCode) html5QrCode.stop().catch(()=>{});
            html5QrCode = new Html5Qrcode("qr-reader-acta");
            html5QrCode.start({ facingMode: "environment" }, { fps: 10, qrbox: { width: 250, height: 150 } },
                (decoded) => {
                    if (!isScanningActivo) return;
                    isScanningActivo = false;
                    
                    if (currentScannerTargetInput) {
                        currentScannerTargetInput.value = decoded;
                        if (currentScannerTargetInput.name === 'equipos_serie[]') {
                            searchSkuForActa(currentScannerTargetInput);
                        } else if (currentScannerTargetInput.name === 'mat_desc[]') {
                            autoFillMaterialUnit(currentScannerTargetInput);
                        }
                    }
                    closeCameraModal();
                    if (window.showToast) window.showToast('Código escaneado: ' + decoded, 'success');
                },
                () => {}
            ).catch(err => console.warn(err));
            return; // Skip normal camera initialization
        }

        // Rest of the normal camera code for photo mode...
        videoElement.style.display = 'block';
        if (document.getElementById('qr-reader-acta')) document.getElementById('qr-reader-acta').innerHTML = '';

        if (mode === 'qr' || mode === 'dni') {
            targetBox.style.width = mode === 'dni' ? '90%' : '60%';
            targetBox.style.height = mode === 'dni' ? '60%' : '60%';
            targetBox.style.display = 'block';
            detectedContainer.style.display = 'block';
            btnReady.style.display = 'none';
            btnPhoto.style.display = 'none';
        } else if (mode === 'photo') {
            targetBox.style.display = 'none';
            detectedContainer.style.display = 'none';
            btnReady.style.display = 'block';
            btnPhoto.style.display = 'block';
        }

        try {
            if(navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                currentStream = await navigator.mediaDevices.getUserMedia({ 
                    video: { facingMode: 'environment', advanced: [{zoom: 1}] } 
                });
                videoElement.srcObject = currentStream;
                
                // Configurar Zoom Slider
                const track = currentStream.getVideoTracks()[0];
                const capabilities = track.getCapabilities();
                const settings = track.getSettings();
                const zoomSlider = document.getElementById('cameraZoomSlider');
                const zoomText = document.getElementById('zoomValueText');
                
                if (capabilities.zoom) {
                    zoomSlider.min = capabilities.zoom.min;
                    zoomSlider.max = capabilities.zoom.max;
                    zoomSlider.step = capabilities.zoom.step;
                    zoomSlider.value = settings.zoom;
                    zoomText.textContent = settings.zoom + 'x';
                    
                    zoomSlider.oninput = (e) => {
                        const val = parseFloat(e.target.value);
                        zoomText.textContent = val.toFixed(1) + 'x';
                        track.applyConstraints({advanced: [{zoom: val}]});
                    };
                } else {
                    // Fallback to CSS Zoom
                    zoomSlider.disabled = false;
                    zoomSlider.min = 1;
                    zoomSlider.max = 5;
                    zoomSlider.step = 0.1;
                    zoomSlider.value = 1;
                    zoomText.textContent = '1x';
                    videoElement.style.transform = 'scale(1)';
                    
                    zoomSlider.oninput = (e) => {
                        const val = parseFloat(e.target.value);
                        zoomText.textContent = val.toFixed(1) + 'x';
                        videoElement.style.transform = `scale(${val})`;
                    };
                }
                
                // Configurar Tomar Foto
                btnPhoto.onclick = () => {
                    const canvas = document.getElementById('cameraCanvas');
                    canvas.width = videoElement.videoWidth;
                    canvas.height = videoElement.videoHeight;
                    canvas.getContext('2d').drawImage(videoElement, 0, 0, canvas.width, canvas.height);
                    
                    const dataUrl = canvas.toDataURL('image/jpeg', 0.8);
                    
                    // Agregar a memoria
                    capturedPhotos.push(dataUrl);
                    
                    // Renderizar miniatura
                    const thumbsContainer = document.getElementById('cameraThumbnailsContainer');
                    thumbsContainer.classList.remove('d-none');
                    const img = document.createElement('img');
                    img.src = dataUrl;
                    img.style.height = '60px';
                    img.style.width = '60px';
                    img.style.objectFit = 'cover';
                    img.style.borderRadius = '8px';
                    img.style.border = '2px solid var(--primary-color)';
                    img.style.marginRight = '8px';
                    thumbsContainer.appendChild(img);
                    
                    // Actualizar botón listo
                    document.getElementById('btnCameraReady').textContent = `Listo (${capturedPhotos.length})`;
                    
                    // Flash effect
                    videoElement.style.opacity = '0.3';
                    setTimeout(() => videoElement.style.opacity = '1', 150);
                };
            } else {
                window.showToast('La cámara no está soportada en este dispositivo o navegador (se requiere HTTPS).', 'error');
            }
        } catch (err) {
            console.error("Camera access denied or error:", err);
            window.showToast('Error al acceder a la cámara.', 'error');
        }
    };

    // Attach to OCR buttons (Using event delegation for dynamically added rows)
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-ocr');
        if (!btn) return;
        if (btn.id === 'btnGPS') return; // Excluir GPS
        if (btn.id === 'btnSearchReniec') return; // Excluir Reniec
        if (btn.id === 'btnSearchClienteReniec') return; // Excluir Reniec Clientes
        if (btn.hasAttribute('onclick')) return; // Excluir los que tienen onclick directo
        
        e.preventDefault();
        let text = btn.textContent.toLowerCase();
        let mode = 'scan';
        let title = 'Escanear Mac/Serie';
        
        if (text.includes('reniec') || text.includes('dni')) { mode = 'dni'; title = 'Escanear DNI/RUC/QR'; }
        else if (text.includes('qr') || btn.querySelector('.ph-qr-code')) { mode = 'qr'; title = 'Escanear QR'; }
        
        window.openCameraModal(title, mode);
    });

    // --- LÓGICA DE AUTOCOMPLETADO DE CLIENTES ---
    const searchInput = document.getElementById('searchClienteAutocomplete');
    const autocompleteResults = document.getElementById('autocompleteResults');
    let clientesData = [];

    // Cargar clientes al iniciar
    fetch('<?php echo BASE_URL; ?>/ajax/clientes.php?action=list', { credentials: 'same-origin' })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                clientesData = res.data;
            }
        }).catch(err => console.error("Error loading clientes:", err));

    searchInput.addEventListener('input', (e) => {
        const val = e.target.value.toLowerCase().trim();
        autocompleteResults.innerHTML = '';
        
        if (!val) {
            autocompleteResults.style.display = 'none';
            return;
        }

        const filtered = clientesData.filter(c => {
            const nombre = c.nombre_completo ? String(c.nombre_completo).toLowerCase() : '';
            const dniStr = c.dni ? String(c.dni).toLowerCase() : '';
            return nombre.includes(val) || dniStr.includes(val);
        });

        if (filtered.length > 0) {
            filtered.forEach(cliente => {
                const div = document.createElement('div');
                div.className = 'dropdown-item';
                div.style.cursor = 'pointer';
                div.style.padding = '10px';
                div.style.borderBottom = '1px solid var(--border-color)';
                div.style.backgroundColor = 'var(--surface-color)';
                div.innerHTML = `<strong>${cliente.nombre_completo}</strong> <span class="text-muted" style="font-size:0.85em;">- DNI: ${cliente.dni}</span>`;
                
                div.addEventListener('mouseenter', () => div.style.backgroundColor = 'rgba(0,0,0,0.05)');
                div.addEventListener('mouseleave', () => div.style.backgroundColor = 'var(--surface-color)');
                
                div.addEventListener('click', () => {
                    const form = document.getElementById('actaForm');
                    if (form.elements['cliente_nombre']) form.elements['cliente_nombre'].value = cliente.nombre_completo || '';
                    if (form.elements['cliente_dni_ruc']) form.elements['cliente_dni_ruc'].value = cliente.dni || '';
                    if (form.elements['cliente_direccion']) form.elements['cliente_direccion'].value = cliente.direccion || '';
                    if (form.elements['cliente_distrito']) form.elements['cliente_distrito'].value = cliente.distrito || '';
                    if (form.elements['cliente_referencia']) form.elements['cliente_referencia'].value = cliente.referencia || '';
                    if (form.elements['cliente_whatsapp']) form.elements['cliente_whatsapp'].value = cliente.celular || '';
                    if (form.elements['cliente_celular_alt']) form.elements['cliente_celular_alt'].value = cliente.celular || '';
                    
                    searchInput.value = cliente.nombre_completo;
                    autocompleteResults.style.display = 'none';
                });
                autocompleteResults.appendChild(div);
            });
            autocompleteResults.style.display = 'block';
        } else {
            const div = document.createElement('div');
            div.className = 'dropdown-item text-muted';
            div.style.padding = '10px';
            div.innerText = 'No se encontraron clientes con ese nombre o DNI';
            div.style.backgroundColor = 'var(--surface-color)';
            autocompleteResults.appendChild(div);
            autocompleteResults.style.display = 'block';
        }
    });

    // Cerrar resultados al hacer click fuera
    document.addEventListener('click', (e) => {
        if (e.target !== searchInput && e.target !== autocompleteResults) {
            autocompleteResults.style.display = 'none';
        }
    });
    // --- FIN AUTOCOMPLETADO ---

    // --- BUSQUEDA RENIEC/SUNAT (MODAL) ---
    const btnSearchReniec = document.getElementById('btnSearchReniec');
    const reniecModal = document.getElementById('reniecModal');
    const reniecModalInput = document.getElementById('reniecModalInput');
    const btnReniecModalSearch = document.getElementById('btnReniecModalSearch');
    const reniecResultCard = document.getElementById('reniecResultCard');
    const btnReniecModalApply = document.getElementById('btnReniecModalApply');
    
    let reniecTempData = null; // Store fetched data temporarily

    if (btnSearchReniec && reniecModal) {
        // Abrir Modal
        btnSearchReniec.addEventListener('click', () => {
            const currentDoc = document.getElementById('cliente_dni_ruc').value.trim();
            reniecModalInput.value = currentDoc;
            reniecResultCard.style.display = 'none';
            reniecTempData = null;
            reniecModal.classList.add('active');
            setTimeout(() => reniecModalInput.focus(), 100);
        });

        // Buscar en Modal
        btnReniecModalSearch.addEventListener('click', async () => {
            const docNumber = reniecModalInput.value.trim();
            
            if (docNumber.length !== 8 && docNumber.length !== 11) {
                window.showToast('El documento debe tener 8 (DNI) u 11 (RUC) dígitos', 'error');
                return;
            }
            
            const originalHtml = btnReniecModalSearch.innerHTML;
            btnReniecModalSearch.innerHTML = '<i class="ph ph-spinner fa-spin"></i>';
            btnReniecModalSearch.disabled = true;
            reniecResultCard.style.display = 'none';
            
            try {
                const res = await fetch(`<?php echo BASE_URL; ?>/ajax/api_peru.php?doc=${docNumber}`).then(r => r.json());
                
                if (res.success && res.data && res.data.success !== false) {
                    const data = res.data.data; // json.pe returns { success, message, data: { ... } }
                    reniecTempData = { type: res.type, data: data, doc: docNumber };
                    
                    document.getElementById('reniecResultDoc').textContent = `${res.type}: ${docNumber}`;
                    
                    const icon = document.getElementById('reniecIcon');
                    icon.className = res.type === 'DNI' ? 'ph ph-user' : 'ph ph-buildings';
                    
                    const extraDiv = document.getElementById('reniecResultExtra');
                    
                    if (res.type === 'DNI') {
                        document.getElementById('reniecResultName').textContent = data.nombre_completo;
                        extraDiv.style.display = 'none';
                    } else if (res.type === 'RUC') {
                        document.getElementById('reniecResultName').textContent = data.nombre_o_razon_social;
                        
                        document.getElementById('reniecResultDir').textContent = data.direccion || '-';
                        document.getElementById('reniecResultDist').textContent = data.distrito || '-';
                        extraDiv.style.display = 'block';
                    }
                    
                    reniecResultCard.style.display = 'block';
                } else {
                    window.showToast(res.data ? res.data.message : res.message || 'No se encontraron resultados', 'error');
                }
            } catch (error) {
                console.error(error);
                window.showToast('Error al conectar con el servidor', 'error');
            } finally {
                btnReniecModalSearch.innerHTML = originalHtml;
                btnReniecModalSearch.disabled = false;
            }
        });

        // Aplicar Datos
        btnReniecModalApply.addEventListener('click', () => {
            if (!reniecTempData) return;
            
            const { type, data, doc } = reniecTempData;
            const form = document.getElementById('actaForm');
            
            form.elements['cliente_dni_ruc'].value = doc;
            
            if (type === 'DNI') {
                form.elements['cliente_nombre'].value = data.nombre_completo;
            } else if (type === 'RUC') {
                form.elements['cliente_nombre'].value = data.nombre_o_razon_social;
                if (data.direccion) form.elements['cliente_direccion'].value = data.direccion;
                if (data.distrito) form.elements['cliente_distrito'].value = data.distrito;
            }
            
            window.showToast('Datos agregados al formulario', 'success');
            reniecModal.classList.remove('active');
        });
        
        // Enter en el input busca
        reniecModalInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                btnReniecModalSearch.click();
            }
        });
    }
    // --- FIN BUSQUEDA RENIEC/SUNAT (MODAL) ---

    // Form Submit
    document.getElementById('actaForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        if (!clientePad.isEmpty()) {
            document.getElementById('firmaClienteBase64').value = clientePad.toDataURL();
        }
        if (!tecnicoPad.isEmpty()) {
            document.getElementById('firmaTecnicoBase64').value = tecnicoPad.toDataURL();
        }

        const formData = new FormData(e.target);
        formData.append('action', 'create');

        // Si el cronómetro sigue corriendo, limpiar hora_fin
        if (localStorage.getItem(SW_KEY_START)) {
            formData.set('srv_hora_fin', '');
        }

        try {
            const btn = e.target.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.innerHTML = 'Guardando...';

            const res = await fetch('<?php echo BASE_URL; ?>/ajax/actas.php', {
                method: 'POST',
                body: formData
            }).then(r => r.json());

            if (res.success) {
                // Clean up chronometer localStorage on successful save
                clearInterval(stopwatchInterval);
                localStorage.removeItem(SW_KEY_START);
                localStorage.removeItem(SW_KEY_INICIO);

                // Mostrar Modal Exito
                const fullFolio = 'LIM-' + String(res.folio).padStart(6, '0');
                document.getElementById('successFolioText').textContent = fullFolio;
                
                const viewUrl = '<?php echo BASE_URL; ?>/modules/actas/view.php?folio=' + fullFolio + '&token=' + res.token;
                
                document.getElementById('btnSharePublic').onclick = () => window.open(viewUrl, '_blank');
                document.getElementById('btnCopyLink').onclick = () => {
                    // Make absolute URL if BASE_URL is relative
                    const absoluteUrl = new URL(viewUrl, window.location.origin).href;
                    navigator.clipboard.writeText(absoluteUrl);
                    window.showToast('Enlace copiado al portapapeles', 'success');
                };
                document.getElementById('btnDownloadPDF').onclick = () => window.open(viewUrl + '&pdf=1', '_blank');

                document.getElementById('successModal').classList.add('active');
            } else {
                window.showToast(res.message || 'Error al guardar', 'error');
                btn.disabled = false;
                btn.innerHTML = '<i class="ph-fill ph-check-circle" style="font-size: 1.4rem; margin-right: 8px;"></i> Crear Acta';
            }
        } catch (error) {
            console.error(error);
            window.showToast('Error en el servidor', 'error');
        }
    });
});
</script>

</div>

<?php include '../../includes/footer.php'; ?>
