<?php
require_once '../../config/db.php';
requireLogin();

include '../../includes/header.php';
include '../../includes/sidebar.php';

$mapboxToken = defined('MAPBOX_TOKEN') ? MAPBOX_TOKEN : '';
?>

<!-- Mapbox GL JS CDN -->
<script src="https://api.mapbox.com/mapbox-gl-js/v3.8.0/mapbox-gl.js"></script>
<link href="https://api.mapbox.com/mapbox-gl-js/v3.8.0/mapbox-gl.css" rel="stylesheet">
<script>
    mapboxgl.accessToken = "<?php echo $mapboxToken; ?>";
</script>

<style>
/* ==========================================================================
   ESTILOS MODERNOS: MÓDULO DE ASISTENCIA BIOMÉTRICA & RRHH
   ========================================================================== */

.asistencia-container {
    padding: 24px;
    max-width: 1600px;
    margin: 0 auto;
}

/* KPI Cards Grid */
.att-kpi-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}

@media (max-width: 1200px) {
    .att-kpi-grid { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 768px) {
    .att-kpi-grid { grid-template-columns: 1fr; }
}

.att-kpi-card {
    background: var(--surface-color);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    padding: 18px 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    transition: var(--transition);
    box-shadow: var(--shadow);
}

.att-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-glow);
}

.att-kpi-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.icon-blue { background: rgba(59, 130, 246, 0.12); color: #3b82f6; }
.icon-green { background: rgba(16, 185, 129, 0.12); color: #10b981; }
.icon-amber { background: rgba(245, 158, 11, 0.12); color: #f59e0b; }
.icon-red { background: rgba(239, 68, 68, 0.12); color: #ef4444; }
.icon-purple { background: rgba(139, 92, 246, 0.12); color: #8b5cf6; }

.att-kpi-val {
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--text-color);
    line-height: 1.1;
}

.att-kpi-lbl {
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--text-muted);
    margin-top: 2px;
}

/* Tab Navigation Bar */
.att-tabs-nav {
    display: flex;
    gap: 8px;
    background: var(--surface-color);
    border: 1px solid var(--border-color);
    padding: 6px;
    border-radius: 14px;
    margin-bottom: 24px;
    overflow-x: auto;
    scrollbar-width: none;
}
.att-tabs-nav::-webkit-scrollbar { display: none; }

.att-tab-btn {
    background: transparent;
    border: none;
    padding: 10px 18px;
    border-radius: 10px;
    color: var(--text-muted);
    font-size: 0.85rem;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: var(--transition);
    white-space: nowrap;
}

.att-tab-btn:hover {
    color: var(--text-color);
    background: rgba(255,255,255,0.03);
}

.att-tab-btn.active {
    background: var(--primary-color, #ff6b00);
    color: #fff;
    box-shadow: 0 4px 14px rgba(255, 107, 0, 0.25);
}

/* Panel Sections */
.att-tab-panel {
    display: none;
    background: var(--surface-color);
    border: 1px solid var(--border-color);
    border-radius: 18px;
    overflow: hidden;
    box-shadow: var(--shadow);
    animation: fadeIn 0.25s ease;
}

.att-tab-panel.active {
    display: block;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(6px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Filter Toolbar */
.att-toolbar {
    padding: 18px 22px;
    background: rgba(0, 0, 0, 0.1);
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 14px;
}

.att-filter-form {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.att-form-control {
    background: var(--bg-color);
    border: 1px solid var(--border-color);
    color: var(--text-color);
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 0.84rem;
    outline: none;
}

.att-form-control:focus {
    border-color: var(--primary-color, #ff6b00);
}

/* Tables */
.att-table {
    width: 100%;
    border-collapse: collapse;
}

.att-table th {
    background: rgba(0, 0, 0, 0.15);
    padding: 14px 18px;
    font-size: 0.72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: var(--text-muted);
    border-bottom: 1px solid var(--border-color);
    text-align: left;
}

.att-table td {
    padding: 14px 18px;
    border-bottom: 1px solid var(--border-color);
    font-size: 0.85rem;
    color: var(--text-color);
    vertical-align: middle;
}

.att-table tr:hover td {
    background: rgba(255, 255, 255, 0.015);
}

/* Selfie Preview Thumbnail */
.selfie-thumb-wrapper {
    position: relative;
    width: 44px;
    height: 44px;
    border-radius: 10px;
    overflow: hidden;
    border: 2px solid var(--border-color);
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    transition: transform 0.2s ease;
}

.selfie-thumb-wrapper:hover {
    transform: scale(1.1);
    border-color: #00f0ff;
}

.selfie-thumb-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* Badges */
.badge-bio {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 0.7rem;
    font-weight: 700;
}
.badge-bio-face { background: rgba(0, 240, 255, 0.12); color: #00f0ff; }
.badge-bio-finger { background: rgba(16, 185, 129, 0.12); color: #10b981; }

.badge-event {
    padding: 4px 10px;
    border-radius: 8px;
    font-weight: 800;
    font-size: 0.72rem;
    text-transform: uppercase;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.badge-entrada { background: rgba(16, 185, 129, 0.15); color: #10b981; }
.badge-refrigerio { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }
.badge-salida { background: rgba(239, 68, 68, 0.15); color: #ef4444; }

/* Live Map View */
#liveAttendanceMap {
    width: 100%;
    height: 600px;
    border-radius: 0 0 18px 18px;
}

.map-tech-marker {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: 3px solid #fff;
    box-shadow: 0 4px 14px rgba(0,0,0,0.4);
    background-size: cover;
    background-position: center;
    cursor: pointer;
    transition: transform 0.2s ease;
}

.map-tech-marker:hover {
    transform: scale(1.25);
    z-index: 999;
}

/* Popup Lightbox Modal */
.att-lightbox-modal {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.85);
    backdrop-filter: blur(10px);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 99999;
    padding: 20px;
}

.att-lightbox-content {
    background: var(--surface-color);
    border: 1px solid var(--border-color);
    border-radius: 20px;
    max-width: 500px;
    width: 100%;
    overflow: hidden;
    box-shadow: 0 25px 60px rgba(0,0,0,0.6);
}

.att-lightbox-header {
    padding: 16px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--border-color);
}

.att-lightbox-body {
    padding: 20px;
    text-align: center;
}

.att-lightbox-img {
    width: 100%;
    max-height: 380px;
    object-fit: contain;
    border-radius: 12px;
    background: #000;
}

.att-forensic-data {
    margin-top: 14px;
    background: rgba(0,0,0,0.2);
    padding: 12px;
    border-radius: 10px;
    font-size: 0.8rem;
    text-align: left;
    display: flex;
    flex-direction: column;
    gap: 6px;
    color: var(--text-muted);
}
</style>

<div class="main-content">
    <div class="asistencia-container">

        <!-- Page Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
            <div>
                <h2 style="font-size: 1.6rem; font-weight: 800; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 10px;">
                    <i class="ph-fill ph-fingerprint text-primary"></i> Asistencia Biométrica Móvil & RRHH
                </h2>
                <p style="margin: 4px 0 0 0; color: var(--text-muted); font-size: 0.85rem;">
                    Monitoreo en tiempo real, Face ID / Huella, Prueba de Vida (Liveness), Geocercas y Planillas.
                </p>
            </div>
            <div style="display: flex; gap: 10px;">
                <button class="btn btn-primary" onclick="loadAttendanceLogs()">
                    <i class="ph ph-arrows-clockwise"></i> Actualizar Datos
                </button>
                <button class="btn btn-secondary" onclick="exportPayrollData()">
                    <i class="ph-bold ph-file-xls"></i> Exportar Planillas (Excel)
                </button>
            </div>
        </div>

        <!-- 5 KPI Cards Grid -->
        <div class="att-kpi-grid">
            <div class="att-kpi-card">
                <div class="att-kpi-icon icon-blue"><i class="ph-fill ph-check-square-offset"></i></div>
                <div>
                    <div class="att-kpi-val" id="kpiTotalLogs">0</div>
                    <div class="att-kpi-lbl">Marcaciones Hoy</div>
                </div>
            </div>
            <div class="att-kpi-card">
                <div class="att-kpi-icon icon-green"><i class="ph-fill ph-user-circle-gear"></i></div>
                <div>
                    <div class="att-kpi-val" id="kpiActiveTechs">0</div>
                    <div class="att-kpi-lbl">En Turno Activo</div>
                </div>
            </div>
            <div class="att-kpi-card">
                <div class="att-kpi-icon icon-amber"><i class="ph-fill ph-fork-knife"></i></div>
                <div>
                    <div class="att-kpi-val" id="kpiLunchTechs">0</div>
                    <div class="att-kpi-lbl">En Refrigerio</div>
                </div>
            </div>
            <div class="att-kpi-card">
                <div class="att-kpi-icon icon-red"><i class="ph-fill ph-alarm"></i></div>
                <div>
                    <div class="att-kpi-val" id="kpiLateCount">0</div>
                    <div class="att-kpi-lbl">Tardanzas Registradas</div>
                </div>
            </div>
            <div class="att-kpi-card">
                <div class="att-kpi-icon icon-purple"><i class="ph-fill ph-map-pin-line"></i></div>
                <div>
                    <div class="att-kpi-val" id="kpiOutOfZone">0</div>
                    <div class="att-kpi-lbl">Fuera de Geocerca</div>
                </div>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="att-tabs-nav">
            <button class="att-tab-btn active" onclick="switchAttTab('tabLogs', this)">
                <i class="ph-bold ph-list-bullets"></i> 📋 Registros de Asistencia
            </button>
            <button class="att-tab-btn" onclick="switchAttTab('tabLiveMap', this)">
                <i class="ph-bold ph-map-trifold"></i> 🗺️ Mapa en Tiempo Real
            </button>
            <button class="att-tab-btn" onclick="switchAttTab('tabGeofences', this)">
                <i class="ph-bold ph-polygon"></i> 📍 Geocercas & Zonas
            </button>
            <button class="att-tab-btn" onclick="switchAttTab('tabSettings', this)">
                <i class="ph-bold ph-clock"></i> ⏱️ Horarios & Tolerancias
            </button>
            <button class="att-tab-btn" onclick="switchAttTab('tabPayroll', this)">
                <i class="ph-bold ph-calculator"></i> 📊 Reporte de Planillas
            </button>
        </div>

        <!-- ================================================================== -->
        <!-- TAB 1: REGISTROS DE ASISTENCIA -->
        <!-- ================================================================== -->
        <div class="att-tab-panel active" id="tabLogs">
            <div class="att-toolbar">
                <form id="filterLogsForm" class="att-filter-form" onsubmit="event.preventDefault(); loadAttendanceLogs();">
                    <div>
                        <label class="zk-form-label">Desde</label>
                        <input type="date" class="att-form-control" id="filterStart" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div>
                        <label class="zk-form-label">Hasta</label>
                        <input type="date" class="att-form-control" id="filterEnd" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div>
                        <label class="zk-form-label">Usuario</label>
                        <select class="att-form-control" id="filterUser">
                            <option value="">Todos los usuarios</option>
                        </select>
                    </div>
                    <div>
                        <label class="zk-form-label">Filtro Especial</label>
                        <select class="att-form-control" id="filterSpecial">
                            <option value="">Todos los registros</option>
                            <option value="late">Solo Tardanzas</option>
                            <option value="out_of_zone">Solo Fuera de Zona</option>
                        </select>
                    </div>
                    <div style="align-self: flex-end;">
                        <button type="submit" class="btn btn-primary" style="height: 38px;">
                            <i class="ph-bold ph-funnel"></i> Filtrar
                        </button>
                    </div>
                </form>
            </div>

            <div style="overflow-x: auto;">
                <table class="att-table" id="tableLogs">
                    <thead>
                        <tr>
                            <th style="width: 60px;">Foto</th>
                            <th>Colaborador / Técnico</th>
                            <th>Evento</th>
                            <th>Biometría</th>
                            <th>Geolocalización / Geocerca</th>
                            <th>Tardanza</th>
                            <th>Fecha & Hora</th>
                        </tr>
                    </thead>
                    <tbody id="logsTableBody">
                        <tr><td colspan="7" style="text-align:center; padding: 30px;"><i class="ph ph-spinner ph-spin text-primary"></i> Cargando registros...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ================================================================== -->
        <!-- TAB 2: MAPA EN TIEMPO REAL -->
        <!-- ================================================================== -->
        <div class="att-tab-panel" id="tabLiveMap">
            <div style="padding: 16px 20px; background: rgba(0,0,0,0.1); border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                <div style="font-size: 0.85rem; color: var(--text-muted);">
                    <span style="color:#10b981;">●</span> En Turno &nbsp;|&nbsp; 
                    <span style="color:#f59e0b;">●</span> En Refrigerio &nbsp;|&nbsp; 
                    <span style="color:#ef4444;">●</span> Sin Iniciar &nbsp;|&nbsp; 
                    <span style="color:#64748b;">●</span> Finalizado
                </div>
                <button class="btn btn-sm btn-primary" onclick="initLiveMap()"><i class="ph ph-arrows-clockwise"></i> Refrescar Mapa</button>
            </div>
            <div id="liveAttendanceMap"></div>
        </div>

        <!-- ================================================================== -->
        <!-- TAB 3: GEOCERCAS Y ZONAS -->
        <!-- ================================================================== -->
        <div class="att-tab-panel" id="tabGeofences">
            <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                <h4 style="margin: 0; font-size: 1rem; color: var(--text-color);">Zonas y Geocercas Permitidas</h4>
                <button class="btn btn-sm btn-primary" onclick="openNewGeofenceModal()"><i class="ph ph-plus"></i> Nueva Geocerca</button>
            </div>
            <div style="overflow-x: auto;">
                <table class="att-table" id="geofencesTable">
                    <thead>
                        <tr>
                            <th>Nombre de la Zona</th>
                            <th>Latitud</th>
                            <th>Longitud</th>
                            <th>Radio de Tolerancia</th>
                            <th>Estado</th>
                            <th style="text-align: right;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="geofencesTableBody"></tbody>
                </table>
            </div>
        </div>

        <!-- ================================================================== -->
        <!-- TAB 4: HORARIOS Y TOLERANCIAS -->
        <!-- ================================================================== -->
        <div class="att-tab-panel" id="tabSettings">
            <div style="padding: 24px; max-width: 600px;">
                <h4 style="margin: 0 0 16px 0; font-size: 1.1rem; color: var(--text-color);">Reglas de Asistencia y Tolerancias</h4>
                
                <form id="settingsForm" onsubmit="saveAttendanceSettings(event)">
                    <div style="margin-bottom: 16px;">
                        <label class="zk-form-label">Hora Oficial de Entrada</label>
                        <input type="time" class="att-form-control w-100" id="settingStartTime" step="1" required>
                    </div>

                    <div style="margin-bottom: 16px;">
                        <label class="zk-form-label">Minutos de Tolerancia (Antes de marcar tardanza)</label>
                        <input type="number" class="att-form-control w-100" id="settingTolerance" min="0" max="60" required>
                    </div>

                    <div style="margin-bottom: 16px;">
                        <label class="zk-form-label">Tiempo Máximo de Refrigerio (Minutos)</label>
                        <input type="number" class="att-form-control w-100" id="settingLunchDuration" min="15" max="120" required>
                    </div>

                    <div style="margin-bottom: 16px;">
                        <label class="zk-form-label">Hora Oficial de Salida</label>
                        <input type="time" class="att-form-control w-100" id="settingEndTime" step="1" required>
                    </div>

                    <button type="submit" class="btn btn-primary"><i class="ph-bold ph-floppy-disk"></i> Guardar Configuraciones</button>
                </form>
            </div>
        </div>

        <!-- ================================================================== -->
        <!-- TAB 5: REPORTE PARA PLANILLAS -->
        <!-- ================================================================== -->
        <div class="att-tab-panel" id="tabPayroll">
            <div class="att-toolbar">
                <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                    <div>
                        <label class="zk-form-label">Mes</label>
                        <input type="month" class="att-form-control" id="payrollMonth" value="<?php echo date('Y-m'); ?>" onchange="loadPayrollReport()">
                    </div>
                    <div style="align-self: flex-end;">
                        <button class="btn btn-primary" onclick="loadPayrollReport()"><i class="ph ph-magnifying-glass"></i> Consultar</button>
                    </div>
                </div>
                <div>
                    <button class="btn btn-secondary" onclick="exportPayrollCSV()"><i class="ph-bold ph-file-csv"></i> Descargar Planilla CSV</button>
                </div>
            </div>
            <div style="overflow-x: auto;">
                <table class="att-table" id="payrollTable">
                    <thead>
                        <tr>
                            <th>Colaborador</th>
                            <th>Fecha</th>
                            <th>Hora Entrada</th>
                            <th>Hora Salida</th>
                            <th>Horas Efectivas</th>
                            <th>Tardanza (min)</th>
                            <th>Marcó Fuera Zona</th>
                        </tr>
                    </thead>
                    <tbody id="payrollTableBody"></tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<!-- Lightbox Modal para Fotos Selfie Forenses -->
<div class="att-lightbox-modal" id="attLightboxModal" onclick="if(event.target === this) closeAttLightbox();">
    <div class="att-lightbox-content">
        <div class="att-lightbox-header">
            <h4 style="margin: 0; font-size: 0.95rem; color: var(--text-color);"><i class="ph-fill ph-camera"></i> Evidencia Fotográfica Biométrica</h4>
            <button style="background:none; border:none; color:var(--text-muted); font-size:1.5rem; cursor:pointer;" onclick="closeAttLightbox()">&times;</button>
        </div>
        <div class="att-lightbox-body">
            <img src="" id="lightboxImg" class="att-lightbox-img" alt="Evidencia">
            <div class="att-forensic-data" id="lightboxForensics">
                <!-- Datos Forenses -->
            </div>
        </div>
    </div>
</div>

<script>
const baseUrl = "<?php echo BASE_URL; ?>";
let mapInstance = null;
let mapMarkers = [];

// =========================================================================
// GESTIÓN DE PESTAÑAS
// =========================================================================
function switchAttTab(tabId, btn) {
    document.querySelectorAll('.att-tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.att-tab-btn').forEach(b => b.classList.remove('active'));
    
    document.getElementById(tabId).classList.add('active');
    btn.classList.add('active');

    if (tabId === 'tabLiveMap') {
        setTimeout(initLiveMap, 200);
    } else if (tabId === 'tabGeofences') {
        loadGeofences();
    } else if (tabId === 'tabSettings') {
        loadAttendanceSettings();
    } else if (tabId === 'tabPayroll') {
        loadPayrollReport();
    }
}

// =========================================================================
// CARGA DE LOGS Y KPIS
// =========================================================================
async function loadAttendanceLogs() {
    const tbody = document.getElementById('logsTableBody');
    tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding: 30px;"><i class="ph ph-spinner ph-spin text-primary" style="font-size:1.8rem;"></i><br>Cargando registros...</td></tr>`;

    const start = document.getElementById('filterStart').value;
    const end = document.getElementById('filterEnd').value;
    const user = document.getElementById('filterUser').value;
    const special = document.getElementById('filterSpecial').value;

    const fd = new FormData();
    fd.append('action', 'list');
    fd.append('start_date', start);
    fd.append('end_date', end);
    fd.append('user_id', user);
    if (special === 'late') fd.append('only_late', '1');
    if (special === 'out_of_zone') fd.append('only_out_of_zone', '1');

    try {
        const res = await fetch(`${baseUrl}/ajax/attendance_ops.php`, { method: 'POST', body: fd });
        const result = await res.json();

        if (!result.success || !result.data || result.data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding: 40px; color: var(--text-muted);"><i class="ph ph-calendar-x" style="font-size: 2rem; display:block; margin-bottom:8px;"></i>No se encontraron marcaciones en este período.</td></tr>`;
            return;
        }

        // Actualizar KPIs
        if (result.stats) {
            document.getElementById('kpiTotalLogs').innerText = result.stats.total;
            document.getElementById('kpiLateCount').innerText = result.stats.tardanzas;
            document.getElementById('kpiOutOfZone').innerText = result.stats.fuera_zona;
        }

        tbody.innerHTML = result.data.map(log => {
            const photoUrl = log.photo_path ? `${baseUrl}/${log.photo_path}` : `${baseUrl}/assets/img/avatar.png`;
            const dateObj = new Date(log.created_at);
            const dateStr = dateObj.toLocaleDateString('es-PE');
            const timeStr = dateObj.toLocaleTimeString('es-PE', { hour12: false });

            let eventBadge = '';
            if (log.type === 'entrada') eventBadge = `<span class="badge-event badge-entrada"><i class="ph-bold ph-arrow-square-in"></i> Entrada</span>`;
            else if (log.type === 'inicio_refrigerio') eventBadge = `<span class="badge-event badge-refrigerio"><i class="ph-bold ph-fork-knife"></i> Inicio Refrigerio</span>`;
            else if (log.type === 'fin_refrigerio') eventBadge = `<span class="badge-event badge-refrigerio"><i class="ph-bold ph-arrow-counter-clockwise"></i> Fin Refrigerio</span>`;
            else if (log.type === 'salida') eventBadge = `<span class="badge-event badge-salida"><i class="ph-bold ph-arrow-square-out"></i> Salida</span>`;
            else eventBadge = `<span class="badge-event">${log.type}</span>`;

            const bioBadge = log.biometric_type === 'face_id' 
                ? `<span class="badge-bio badge-bio-face"><i class="ph-bold ph-scan"></i> Face ID / Apple</span>`
                : `<span class="badge-bio badge-bio-finger"><i class="ph-bold ph-fingerprint"></i> Huella / Android</span>`;

            const livenessBadge = `<div style="font-size:0.72rem; color:#10b981; margin-top:3px;"><i class="ph-bold ph-shield-check"></i> Liveness OK</div>`;

            let geoBadge = '';
            if (log.is_out_of_zone == 1) {
                geoBadge = `<span class="badge-tag warning"><i class="ph-bold ph-warning"></i> Fuera de Zona</span><div style="font-size:0.72rem; color:var(--text-muted); margin-top:2px;">${log.out_of_zone_reason || 'Sin motivo'}</div>`;
            } else {
                geoBadge = `<span class="badge-tag success"><i class="ph-bold ph-check"></i> En Zona</span>`;
            }

            if (log.latitude && log.longitude) {
                geoBadge += `<div style="margin-top:4px;"><a href="https://maps.google.com/?q=${log.latitude},${log.longitude}" target="_blank" style="font-size:0.72rem; color:#00f0ff;"><i class="ph ph-map-pin"></i> Ver GPS (${parseFloat(log.latitude).toFixed(4)}, ${parseFloat(log.longitude).toFixed(4)})</a></div>`;
            }

            const lateBadge = log.is_late == 1 
                ? `<span class="badge-tag danger"><i class="ph-bold ph-alarm"></i> Tardanza (+${log.minutes_late} min)</span>`
                : `<span class="badge-tag success">A tiempo</span>`;

            const rawJson = encodeURIComponent(JSON.stringify(log));

            return `
                <tr>
                    <td>
                        <div class="selfie-thumb-wrapper" onclick="openAttLightbox('${photoUrl}', '${rawJson}')">
                            <img src="${photoUrl}" class="selfie-thumb-img" alt="Selfie">
                        </div>
                    </td>
                    <td>
                        <strong>${log.user_name}</strong>
                        <div style="font-size:0.75rem; color:var(--text-muted);">${log.user_role}</div>
                    </td>
                    <td>${eventBadge}</td>
                    <td>${bioBadge} ${livenessBadge}</td>
                    <td>${geoBadge}</td>
                    <td>${lateBadge}</td>
                    <td>
                        <strong>${timeStr}</strong>
                        <div style="font-size:0.75rem; color:var(--text-muted);">${dateStr}</div>
                    </td>
                </tr>
            `;
        }).join('');

    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding: 30px; color:#ef4444;">Error al cargar registros: ${e.message}</td></tr>`;
    }
}

// =========================================================================
// MAPA EN TIEMPO REAL (MAPBOX)
// =========================================================================
async function initLiveMap() {
    const res = await fetch(`${baseUrl}/ajax/attendance_ops.php?action=get_live_map_data`);
    const data = await res.json();
    if (!data.success) return;

    // Actualizar KPIs de técnicos activos y en refrigerio
    let activeCount = 0;
    let lunchCount = 0;
    data.technicians.forEach(t => {
        if (t.status === 'activo') activeCount++;
        if (t.status === 'refrigerio') lunchCount++;
    });
    document.getElementById('kpiActiveTechs').innerText = activeCount;
    document.getElementById('kpiLunchTechs').innerText = lunchCount;

    if (!mapInstance) {
        mapInstance = new mapboxgl.Map({
            container: 'liveAttendanceMap',
            style: 'mapbox://styles/mapbox/dark-v11',
            center: [-77.042793, -12.046374], // Lima, Perú default
            zoom: 12
        });
        mapInstance.addControl(new mapboxgl.NavigationControl(), 'top-right');
    }

    // Limpiar marcadores existentes
    mapMarkers.forEach(m => m.remove());
    mapMarkers = [];

    const bounds = new mapboxgl.LngLatBounds();
    let hasCoords = false;

    data.technicians.forEach(tech => {
        if (tech.last_log && tech.last_log.latitude && tech.last_log.longitude) {
            const lng = parseFloat(tech.last_log.longitude);
            const lat = parseFloat(tech.last_log.latitude);
            hasCoords = true;
            bounds.extend([lng, lat]);

            const el = document.createElement('div');
            el.className = 'map-tech-marker';
            el.style.borderColor = tech.status_color;
            const photo = tech.last_log.photo_path ? `${baseUrl}/${tech.last_log.photo_path}` : `${baseUrl}/assets/img/avatar.png`;
            el.style.backgroundImage = `url('${photo}')`;

            const popupHTML = `
                <div style="padding: 10px; color: #12141a;">
                    <div style="font-weight: 800; font-size: 0.9rem;">${tech.name}</div>
                    <div style="font-size: 0.75rem; color: #64748b;">${tech.role}</div>
                    <div style="margin-top: 6px; font-weight: 700; color: ${tech.status_color};">${tech.status_label}</div>
                    <div style="font-size: 0.72rem; color: #475569; margin-top: 4px;">🕒 ${tech.last_log.created_at}</div>
                </div>
            `;

            const marker = new mapboxgl.Marker(el)
                .setLngLat([lng, lat])
                .setPopup(new mapboxgl.Popup({ offset: 25 }).setHTML(popupHTML))
                .addTo(mapInstance);

            mapMarkers.push(marker);
        }
    });

    if (hasCoords) {
        mapInstance.fitBounds(bounds, { padding: 80, maxZoom: 15 });
    }
}

// =========================================================================
// LIGHTBOX FORENSE
// =========================================================================
function openAttLightbox(photoUrl, rawJsonEncoded) {
    const log = JSON.parse(decodeURIComponent(rawJsonEncoded));
    const modal = document.getElementById('attLightboxModal');
    document.getElementById('lightboxImg').src = photoUrl;

    const info = document.getElementById('lightboxForensics');
    info.innerHTML = `
        <div><strong>👤 Colaborador:</strong> ${log.user_name} (${log.user_role})</div>
        <div><strong>🕒 Fecha & Hora:</strong> ${log.created_at}</div>
        <div><strong>📍 Coordenadas:</strong> Lat: ${log.latitude || 'N/A'}, Lon: ${log.longitude || 'N/A'} (Precisión: ±${log.accuracy || 0}m)</div>
        <div><strong>🛡️ Prueba de Vida:</strong> Score: ${log.liveness_score || 1.0} (${log.liveness_action})</div>
        <div><strong>📱 Dispositivo:</strong> ${log.device_info || 'Móvil'}</div>
        <div><strong>🏢 Geocerca:</strong> ${log.is_out_of_zone == 1 ? '⚠️ Fuera de Zona (' + (log.out_of_zone_reason || 'Sin motivo') + ')' : '🟢 En Zona'}</div>
    `;

    modal.style.display = 'flex';
}

function closeAttLightbox() {
    document.getElementById('attLightboxModal').style.display = 'none';
}

// =========================================================================
// GEOCERCAS CRUD
// =========================================================================
async function loadGeofences() {
    const tbody = document.getElementById('geofencesTableBody');
    tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding: 20px;">Cargando geocercas...</td></tr>`;

    const res = await fetch(`${baseUrl}/ajax/attendance_ops.php?action=get_geofences`);
    const data = await res.json();
    if (!data.success || !data.data) return;

    tbody.innerHTML = data.data.map(g => `
        <tr>
            <td><strong>${g.name}</strong></td>
            <td>${g.latitude}</td>
            <td>${g.longitude}</td>
            <td><span class="badge-tag success">${g.radius_meters} metros</span></td>
            <td>${g.is_active == 1 ? '<span class="badge-tag success">Activa</span>' : '<span class="badge-tag secondary">Inactiva</span>'}</td>
            <td style="text-align: right;">
                <button class="btn btn-sm btn-outline btn-danger-outline" onclick="deleteGeofence(${g.id})"><i class="ph ph-trash"></i></button>
            </td>
        </tr>
    `).join('');
}

async function deleteGeofence(id) {
    if (!confirm('¿Deseas eliminar esta geocerca?')) return;
    const fd = new FormData();
    fd.append('action', 'delete_geofence');
    fd.append('id', id);
    const res = await fetch(`${baseUrl}/ajax/attendance_ops.php`, { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) loadGeofences();
}

function openNewGeofenceModal() {
    const name = prompt('Nombre de la zona (ej: Base Norte):');
    if (!name) return;
    const lat = prompt('Latitud (ej: -12.0463):');
    if (!lat) return;
    const lon = prompt('Longitud (ej: -77.0427):');
    if (!lon) return;
    const radius = prompt('Radio en metros (ej: 200):', '200');

    const fd = new FormData();
    fd.append('action', 'save_geofence');
    fd.append('name', name);
    fd.append('latitude', lat);
    fd.append('longitude', lon);
    fd.append('radius_meters', radius);
    fd.append('is_active', 1);

    fetch(`${baseUrl}/ajax/attendance_ops.php`, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if (d.success) loadGeofences();
        });
}

// =========================================================================
// CONFIGURACIÓN & TOLERANCIAS
// =========================================================================
async function loadAttendanceSettings() {
    const res = await fetch(`${baseUrl}/ajax/attendance_ops.php?action=get_settings`);
    const data = await res.json();
    if (data.success && data.data) {
        document.getElementById('settingStartTime').value = data.data.work_start_time || '08:00:00';
        document.getElementById('settingTolerance').value = data.data.tolerance_minutes || '15';
        document.getElementById('settingLunchDuration').value = data.data.lunch_duration_minutes || '60';
        document.getElementById('settingEndTime').value = data.data.work_end_time || '18:00:00';
    }
}

async function saveAttendanceSettings(e) {
    e.preventDefault();
    const settings = {
        work_start_time: document.getElementById('settingStartTime').value,
        tolerance_minutes: document.getElementById('settingTolerance').value,
        lunch_duration_minutes: document.getElementById('settingLunchDuration').value,
        work_end_time: document.getElementById('settingEndTime').value
    };

    const fd = new FormData();
    fd.append('action', 'save_settings');
    fd.append('settings', JSON.stringify(settings));

    const res = await fetch(`${baseUrl}/ajax/attendance_ops.php`, { method: 'POST', body: fd });
    const data = await res.json();
    alert(data.message || 'Configuración guardada.');
}

// =========================================================================
// REPORTE DE PLANILLAS
// =========================================================================
let currentPayrollData = [];

async function loadPayrollReport() {
    const month = document.getElementById('payrollMonth').value;
    const start = `${month}-01`;
    const end = `${month}-31`;

    const res = await fetch(`${baseUrl}/ajax/attendance_ops.php?action=export_payroll&start_date=${start}&end_date=${end}`);
    const data = await res.json();
    if (!data.success) return;

    currentPayrollData = data.data || [];
    const tbody = document.getElementById('payrollTableBody');

    if (currentPayrollData.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding: 30px; color:var(--text-muted);">Sin registros en este mes.</td></tr>`;
        return;
    }

    tbody.innerHTML = currentPayrollData.map(r => `
        <tr>
            <td><strong>${r.name}</strong> <span style="font-size:0.75rem; color:var(--text-muted);">(${r.role})</span></td>
            <td>${r.date}</td>
            <td><span class="badge-tag success">${r.entrada}</span></td>
            <td><span class="badge-tag secondary">${r.salida}</span></td>
            <td><strong>${r.horas_efectivas} hrs</strong></td>
            <td>${r.tardanza_min > 0 ? `<span class="badge-tag danger">+${r.tardanza_min} min</span>` : '<span class="badge-tag success">0 min</span>'}</td>
            <td>${r.fuera_zona === 'Sí' ? '<span class="badge-tag warning">Sí</span>' : '<span class="badge-tag success">No</span>'}</td>
        </tr>
    `).join('');
}

function exportPayrollCSV() {
    if (!currentPayrollData || currentPayrollData.length === 0) {
        alert('No hay datos para exportar.');
        return;
    }

    let csv = 'Colaborador,Rol,Fecha,Entrada,Salida,Horas Efectivas,Minutos Tardanza,Marco Fuera de Zona\n';
    currentPayrollData.forEach(r => {
        csv += `"${r.name}","${r.role}","${r.date}","${r.entrada}","${r.salida}","${r.horas_efectivas}","${r.tardanza_min}","${r.fuera_zona}"\n`;
    });

    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `Planilla_Asistencia_${document.getElementById('payrollMonth').value}.csv`;
    a.click();
}

function exportPayrollData() {
    switchAttTab('tabPayroll', document.querySelectorAll('.att-tab-btn')[4]);
    setTimeout(exportPayrollCSV, 600);
}

// Cargar usuarios para el filtro y registros al iniciar
document.addEventListener('DOMContentLoaded', async () => {
    try {
        const res = await fetch(`${baseUrl}/ajax/attendance_ops.php?action=get_users`);
        const data = await res.json();
        if (data.success && data.data) {
            const select = document.getElementById('filterUser');
            data.data.forEach(u => {
                const opt = document.createElement('option');
                opt.value = u.id;
                opt.textContent = u.name;
                select.appendChild(opt);
            });
        }
    } catch (e) {}

    loadAttendanceLogs();
});
</script>

<?php include '../../includes/footer.php'; ?>
