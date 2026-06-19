<?php
require_once '../../config/db.php';
requireLogin();

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="ph ph-clock"></i> Control de Asistencia</h2>
            <p class="text-muted mb-0">Visualiza y exporta los registros de entradas y salidas del personal.</p>
        </div>
        <div>
            <button class="btn btn-success" onclick="exportToCSV()"><i class="ph ph-download-simple"></i> Exportar CSV</button>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form id="filterForm" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Fecha Inicio</label>
                    <input type="date" class="form-control" id="startDate" name="start_date" value="<?php echo date('Y-m-01'); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fecha Fin</label>
                    <input type="date" class="form-control" id="endDate" name="end_date" value="<?php echo date('Y-m-t'); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Usuario</label>
                    <select class="form-select" id="userId" name="user_id">
                        <option value="">Todos los usuarios</option>
                    </select>
                </div>
                <div class="col-md-2">
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
                    typeBadge = '<span class="badge bg-success-subtle text-success"><i class="ph ph-sign-in"></i> Entrada</span>';
                } else {
                    typeBadge = '<span class="badge bg-danger-subtle text-danger"><i class="ph ph-sign-out"></i> Salida</span>';
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
</script>

<?php include '../../includes/footer.php'; ?>
