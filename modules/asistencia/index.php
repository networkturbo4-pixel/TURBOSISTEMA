<?php
require_once '../../config/db.php';
requireLogin();

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="main-content">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
        <div>
            <h2 class="mb-1"><i class="ph ph-clock"></i> Control de Asistencia</h2>
            <p class="text-muted mb-0">Visualiza y sincroniza los registros del reloj biométrico (Entradas, Salidas, Refrigerios).</p>
        </div>
        <div style="display:flex; gap:10px; flex-wrap: wrap;">
            <button class="btn btn-outline-primary" onclick="openZkModal()"><i class="ph ph-gear"></i> Config. ZKTeco</button>
            <button class="btn btn-primary" onclick="syncZkteco()" id="btnSync"><i class="ph ph-arrows-clockwise"></i> Sincronizar ZKTeco</button>
            <button class="btn btn-success" onclick="exportToCSV()"><i class="ph ph-download-simple"></i> Exportar CSV</button>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form id="filterForm" style="display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 150px;">
                    <label class="form-label">Fecha Inicio</label>
                    <input type="date" class="form-control" id="startDate" name="start_date" value="<?php echo date('Y-m-01'); ?>">
                </div>
                <div style="flex: 1; min-width: 150px;">
                    <label class="form-label">Fecha Fin</label>
                    <input type="date" class="form-control" id="endDate" name="end_date" value="<?php echo date('Y-m-t'); ?>">
                </div>
                <div style="flex: 2; min-width: 200px;">
                    <label class="form-label">Usuario</label>
                    <select class="form-select" id="userId" name="user_id">
                        <option value="">Todos los usuarios</option>
                    </select>
                </div>
                <div style="min-width: 130px;">
                    <button type="submit" class="btn btn-primary w-100"><i class="ph ph-funnel"></i> Filtrar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="attendanceTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Técnico</th>
                            <th>Tipo de Evento</th>
                            <th>Fecha y Hora</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be loaded here -->
                    </tbody>
                </table>
            </div>
            <div id="loadingIndicator" class="text-center p-4" style="display: none;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
            </div>
            <div id="noDataMessage" class="text-center p-4 text-muted" style="display: none;">
                No se encontraron registros en este rango de fechas.
            </div>
        </div>
    </div>
</div>

<!-- Modal Config ZKTeco -->
<div class="modal-overlay" id="zkModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Configurar Conexión ZKTeco</h3>
            <button class="btn close-modal" style="background:transparent; border:none; font-size:1.5rem; cursor:pointer;" onclick="document.getElementById('zkModal').classList.remove('active')">&times;</button>
        </div>
        <div class="modal-body">
            <p class="text-muted mb-3" style="font-size:0.9rem;">Ingresa la IP local o pública de tu reloj biométrico ZKTeco. Asegúrate de que el puerto (usualmente 4370) esté abierto o sea accesible desde este servidor.</p>
            <div class="form-group mb-3">
                <label>Dirección IP del Reloj</label>
                <input type="text" id="zk_ip" class="form-control" placeholder="Ej: 192.168.1.201 o tu IP Pública">
            </div>
            <div class="form-group mb-3">
                <label>Puerto (UDP/TCP)</label>
                <input type="text" id="zk_port" class="form-control" placeholder="Ej: 4370" value="4370">
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('zkModal').classList.remove('active')">Cancelar</button>
            <button type="button" class="btn btn-primary" onclick="saveZkSettings()">Guardar Configuración</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    loadUsers();
    loadAttendance();

    document.getElementById('filterForm').addEventListener('submit', (e) => {
        e.preventDefault();
        loadAttendance();
    });
});

async function loadUsers() {
    try {
        const fd = new FormData();
        fd.append('action', 'get_users');
        const baseUrl = document.querySelector('meta[name="base-url"]')?.getAttribute('content') || '';
        const res = await fetch(`${baseUrl}/ajax/attendance_ops.php`, { method: 'POST', body: fd });
        const data = await res.json();
        
        if (data.success) {
            const select = document.getElementById('userId');
            data.data.forEach(u => {
                const opt = document.createElement('option');
                opt.value = u.id;
                opt.textContent = u.name;
                select.appendChild(opt);
            });
        }
    } catch(e) {
        console.error(e);
    }
}

async function loadAttendance() {
    const tbody = document.querySelector('#attendanceTable tbody');
    const loading = document.getElementById('loadingIndicator');
    const noData = document.getElementById('noDataMessage');
    
    tbody.innerHTML = '';
    loading.style.display = 'block';
    noData.style.display = 'none';
    
    try {
        const fd = new FormData();
        fd.append('action', 'list');
        fd.append('start_date', document.getElementById('startDate').value);
        fd.append('end_date', document.getElementById('endDate').value);
        fd.append('user_id', document.getElementById('userId').value);
        
        const baseUrl = document.querySelector('meta[name="base-url"]')?.getAttribute('content') || '';
        const res = await fetch(`${baseUrl}/ajax/attendance_ops.php`, { method: 'POST', body: fd });
        const data = await res.json();
        
        loading.style.display = 'none';
        
        if (data.success && data.data.length > 0) {
            data.data.forEach(row => {
                const tr = document.createElement('tr');
                
                let typeBadge = '';
                if (row.type === 'entrada') {
                    typeBadge = '<span class="badge bg-success-subtle text-success" style="padding: 6px 10px; font-size:0.85rem;"><i class="ph-bold ph-sign-in"></i> Entrada</span>';
                } else if (row.type === 'salida') {
                    typeBadge = '<span class="badge bg-danger-subtle text-danger" style="padding: 6px 10px; font-size:0.85rem;"><i class="ph-bold ph-sign-out"></i> Salida</span>';
                } else if (row.type === 'inicio_refrigerio') {
                    typeBadge = '<span class="badge bg-warning-subtle text-warning" style="padding: 6px 10px; font-size:0.85rem;"><i class="ph-bold ph-coffee"></i> Inic. Refrigerio</span>';
                } else if (row.type === 'fin_refrigerio') {
                    typeBadge = '<span class="badge bg-info-subtle text-info" style="padding: 6px 10px; font-size:0.85rem;"><i class="ph-bold ph-arrow-u-down-left"></i> Fin Refrigerio</span>';
                } else {
                    typeBadge = '<span class="badge bg-secondary-subtle text-secondary" style="padding: 6px 10px; font-size:0.85rem;"><i class="ph-bold ph-question"></i> Desconocido</span>';
                }
                
                // Format date nicely
                const dt = new Date(row.created_at);
                const dtString = dt.toLocaleString('es-ES', { dateStyle: 'short', timeStyle: 'short' });
                
                tr.innerHTML = `
                    <td>${row.id}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="avatar bg-primary-subtle text-primary me-2 rounded-circle" style="width:32px; height:32px; display:flex; align-items:center; justify-content:center; font-weight:bold;">
                                ${row.user_name.charAt(0).toUpperCase()}
                            </div>
                            <div>
                                <div class="fw-bold">${row.user_name}</div>
                                <div class="text-muted" style="font-size:0.75rem;">${row.user_role}</div>
                            </div>
                        </div>
                    </td>
                    <td>${typeBadge}</td>
                    <td>${dtString}</td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            noData.style.display = 'block';
        }
    } catch(e) {
        console.error(e);
        loading.style.display = 'none';
        window.showToast('Error al cargar datos', 'error');
    }
}

function exportToCSV() {
    const table = document.getElementById('attendanceTable');
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    for (let i = 0; i < rows.length; i++) {
        let row = [], cols = rows[i].querySelectorAll('td, th');
        
        for (let j = 0; j < cols.length; j++) {
            // Clean text to avoid newlines or weird formatting
            let text = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, " ").replace(/"/g, '""');
            row.push('"' + text + '"');
        }
        csv.push(row.join(','));
    }
    
    const csvFile = new Blob([csv.join('\n')], {type: "text/csv;charset=utf-8;"});
    const downloadLink = document.createElement("a");
    downloadLink.download = "Asistencia_" + new Date().toISOString().slice(0,10) + ".csv";
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}

function openZkModal() {
    // Optionally fetch current settings
    document.getElementById('zkModal').classList.add('active');
}

async function saveZkSettings() {
    const ip = document.getElementById('zk_ip').value;
    const port = document.getElementById('zk_port').value;
    if (!ip || !port) {
        window.showToast('IP y Puerto son requeridos', 'error');
        return;
    }
    const fd = new FormData();
    fd.append('action', 'save_settings');
    fd.append('ip', ip);
    fd.append('port', port);
    try {
        const baseUrl = document.querySelector('meta[name="base-url"]')?.getAttribute('content') || '';
        const res = await fetch(`${baseUrl}/ajax/zkteco_sync.php`, { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            window.showToast('Configuración guardada', 'success');
            document.getElementById('zkModal').classList.remove('active');
        } else {
            window.showToast(data.message || 'Error', 'error');
        }
    } catch(e) {
        console.error(e);
        window.showToast('Error de red', 'error');
    }
}

async function syncZkteco() {
    const btn = document.getElementById('btnSync');
    btn.disabled = true;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="ph ph-spinner ph-spin"></i> Sincronizando...';
    
    try {
        const fd = new FormData();
        fd.append('action', 'sync');
        const baseUrl = document.querySelector('meta[name="base-url"]')?.getAttribute('content') || '';
        const res = await fetch(`${baseUrl}/ajax/zkteco_sync.php`, { method: 'POST', body: fd });
        const data = await res.json();
        
        if (data.success) {
            window.showToast(data.message, 'success');
            loadAttendance();
        } else {
            window.showToast(data.message || 'Error al conectar al dispositivo', 'error');
        }
    } catch (e) {
        console.error(e);
        window.showToast('Error de red o timeout intentando conectar al dispositivo ZKTeco', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}
</script>

<?php include '../../includes/footer.php'; ?>
