<?php
require_once '../../config/db.php';
requireLogin();

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="main-content">
    <div class="page-header-card mb-4">
        <div class="page-header-left">
            <div class="page-header-icon"><i class="ph ph-clock"></i></div>
            <div class="page-header-info">
                <h2 class="mb-1">Control de Asistencia</h2>
                <p class="text-muted mb-0">Visualiza y sincroniza registros del reloj biométrico, y gestiona usuarios en el dispositivo.</p>
            </div>
        </div>
        <div class="page-header-actions" style="display:flex; gap:10px; flex-wrap: wrap;">
            <button class="btn btn-secondary" onclick="openZkModal()"><i class="ph ph-gear"></i> Config. ZKTeco</button>
            <button class="btn btn-success" onclick="exportToCSV()"><i class="ph ph-download-simple"></i> Exportar CSV</button>
            <button class="btn btn-primary" onclick="syncZkteco()" id="btnSync"><i class="ph ph-arrows-clockwise"></i> Sincronizar ZKTeco</button>
        </div>
    </div>

    <!-- Panel de Estado del Dispositivo -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center cursor-pointer" data-bs-toggle="collapse" data-bs-target="#deviceStatusPanel">
            <h5 class="mb-0"><i class="ph ph-hard-drives"></i> Estado del Dispositivo ZKTeco</h5>
            <div>
                <span id="deviceConnectionBadge" class="badge bg-secondary">Verificando...</span>
                <i class="ph ph-caret-down"></i>
            </div>
        </div>
        <div id="deviceStatusPanel" class="collapse show">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <small class="text-muted d-block">Dispositivo</small>
                        <strong id="stDeviceName">-</strong>
                    </div>
                    <div class="col-md-3 mb-3">
                        <small class="text-muted d-block">Número de Serie</small>
                        <strong id="stSerialNumber">-</strong>
                    </div>
                    <div class="col-md-3 mb-3">
                        <small class="text-muted d-block">IP y Puerto</small>
                        <strong id="stIpPort">-</strong>
                    </div>
                    <div class="col-md-3 mb-3">
                        <small class="text-muted d-block">Hora del Dispositivo</small>
                        <strong id="stDeviceTime">-</strong>
                        <button class="btn btn-sm btn-outline-primary ms-2 py-0" onclick="syncTime()"><i class="ph ph-clock-counter-clockwise"></i> Igualar al Servidor</button>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                    <div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="autoSyncToggle">
                            <label class="form-check-label" for="autoSyncToggle">
                                Sincronización Automática (cada 2 minutos)
                                <span id="autoSyncIndicator" class="ms-2 text-primary" style="display:none;"><i class="ph ph-spinner ph-spin"></i></span>
                            </label>
                        </div>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary" onclick="checkDeviceStatus()"><i class="ph ph-arrows-clockwise"></i> Refrescar Estado</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Gestión de Usuarios en Dispositivo -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center cursor-pointer collapsed" data-bs-toggle="collapse" data-bs-target="#deviceUsersPanel">
            <h5 class="mb-0"><i class="ph ph-users"></i> Gestión de Usuarios en ZKTeco</h5>
            <i class="ph ph-caret-down"></i>
        </div>
        <div id="deviceUsersPanel" class="collapse">
            <div class="card-body p-0">
                <div class="p-3 border-bottom d-flex justify-content-between">
                    <p class="mb-0 text-muted">Compara los usuarios del sistema (que tienen Biometric ID asignado) con los usuarios actualmente en el dispositivo físico.</p>
                    <div>
                        <button class="btn btn-sm btn-primary" onclick="pushAllUsers()"><i class="ph ph-upload-simple"></i> Enviar Todos al Dispositivo</button>
                        <button class="btn btn-sm btn-secondary" onclick="loadDeviceUsers()"><i class="ph ph-arrows-clockwise"></i> Recargar Lista</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="deviceUsersTable">
                        <thead class="bg-light">
                            <tr>
                                <th>Biometric ID</th>
                                <th>Nombre en Sistema</th>
                                <th>Estado en Dispositivo</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="4" class="text-center p-4">Haz clic en "Recargar Lista" para ver los usuarios.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Registros de Asistencia -->
    <div class="card mb-4">
        <div class="card-body border-bottom bg-light">
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
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="attendanceTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Técnico</th>
                            <th>Tipo de Evento</th>
                            <th>Fecha</th>
                            <th>Hora</th>
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
            <p class="text-muted mb-3" style="font-size:0.9rem;">Ingresa la IP local de tu reloj biométrico ZKTeco. Asegúrate de que el puerto TCP/UDP esté accesible.</p>
            <div class="form-group mb-3">
                <label>Dirección IP del Reloj</label>
                <input type="text" id="zk_ip" class="form-control" placeholder="Ej: 192.168.1.150">
            </div>
            <div class="form-group mb-3">
                <label>Puerto</label>
                <input type="text" id="zk_port" class="form-control" placeholder="Ej: 4370" value="4370">
            </div>
            <div id="testConnectionResult" class="mt-2 text-sm"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" onclick="testConnection()"><i class="ph ph-plugs"></i> Probar Conexión</button>
            <button type="button" class="btn btn-primary" onclick="saveZkSettings()">Guardar Configuración</button>
        </div>
    </div>
</div>

<script>
let autoSyncInterval = null;
const baseUrl = document.querySelector('meta[name="base-url"]')?.getAttribute('content') || '';

document.addEventListener('DOMContentLoaded', () => {
    loadUsers();
    loadAttendance();
    checkDeviceStatus();
    
    // Auto sync logic
    const autoSyncToggle = document.getElementById('autoSyncToggle');
    const isAutoSync = localStorage.getItem('zkteco_autosync') === 'true';
    autoSyncToggle.checked = isAutoSync;
    
    if (isAutoSync) startAutoSync();

    autoSyncToggle.addEventListener('change', (e) => {
        localStorage.setItem('zkteco_autosync', e.target.checked);
        if (e.target.checked) {
            startAutoSync();
        } else {
            stopAutoSync();
        }
    });

    document.getElementById('filterForm').addEventListener('submit', (e) => {
        e.preventDefault();
        loadAttendance();
    });
});

window.addEventListener('beforeunload', () => {
    stopAutoSync();
});

function startAutoSync() {
    if (autoSyncInterval) clearInterval(autoSyncInterval);
    document.getElementById('autoSyncIndicator').style.display = 'inline-block';
    
    // Ejecutar cada 2 minutos (120000 ms)
    autoSyncInterval = setInterval(() => {
        syncZkteco(true);
    }, 120000);
}

function stopAutoSync() {
    if (autoSyncInterval) clearInterval(autoSyncInterval);
    document.getElementById('autoSyncIndicator').style.display = 'none';
}

async function loadUsers() {
    try {
        const fd = new FormData();
        fd.append('action', 'get_users');
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

async function checkDeviceStatus() {
    const badge = document.getElementById('deviceConnectionBadge');
    badge.className = 'badge bg-secondary';
    badge.textContent = 'Verificando...';
    
    try {
        const fd = new FormData();
        fd.append('action', 'device_status');
        const res = await fetch(`${baseUrl}/ajax/zkteco_sync.php`, { method: 'POST', body: fd });
        const data = await res.json();
        
        if (data.success) {
            badge.className = 'badge bg-success';
            badge.textContent = 'Conectado';
            document.getElementById('stDeviceName').textContent = data.data.deviceName || 'Desconocido';
            document.getElementById('stSerialNumber').textContent = data.data.serialNumber || 'Desconocido';
            document.getElementById('stIpPort').textContent = `${data.data.ip}:${data.data.port}`;
            document.getElementById('stDeviceTime').textContent = data.data.deviceTime || 'Desconocido';
        } else {
            badge.className = 'badge bg-danger';
            badge.textContent = 'Desconectado';
            document.getElementById('stDeviceName').textContent = '-';
            document.getElementById('stSerialNumber').textContent = '-';
            document.getElementById('stIpPort').textContent = '-';
            document.getElementById('stDeviceTime').textContent = '-';
        }
    } catch(e) {
        badge.className = 'badge bg-danger';
        badge.textContent = 'Error de Red';
    }
}

async function syncTime() {
    try {
        const fd = new FormData();
        fd.append('action', 'sync_time');
        const res = await fetch(`${baseUrl}/ajax/zkteco_sync.php`, { method: 'POST', body: fd });
        const data = await res.json();
        
        if (data.success) {
            window.showToast(data.message, 'success');
            document.getElementById('stDeviceTime').textContent = data.time;
        } else {
            window.showToast(data.message, 'error');
        }
    } catch(e) {
        window.showToast('Error de conexión', 'error');
    }
}

// Global variable to store system users for comparison
let systemUsersCache = [];

async function loadDeviceUsers() {
    const tbody = document.querySelector('#deviceUsersTable tbody');
    tbody.innerHTML = '<tr><td colspan="4" class="text-center p-4"><i class="ph ph-spinner ph-spin"></i> Cargando usuarios del dispositivo...</td></tr>';
    
    try {
        // 1. Get system users with biometric_id
        const sysFd = new FormData();
        sysFd.append('action', 'list');
        const sysRes = await fetch(`${baseUrl}/ajax/users.php`, { method: 'POST', body: sysFd });
        const sysData = await sysRes.json();
        
        systemUsersCache = (sysData.data || []).filter(u => u.biometric_id !== null && u.biometric_id !== '');

        // 2. Get device users
        const devFd = new FormData();
        devFd.append('action', 'get_device_users');
        const devRes = await fetch(`${baseUrl}/ajax/zkteco_sync.php`, { method: 'POST', body: devFd });
        const devData = await devRes.json();
        
        tbody.innerHTML = '';
        
        if (devData.success) {
            const deviceUsers = devData.data || {};
            
            if (systemUsersCache.length === 0 && Object.keys(deviceUsers).length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center p-4">No hay usuarios en el sistema ni en el dispositivo.</td></tr>';
                return;
            }

            // Create a merged list
            const allBiometricIds = new Set([
                ...systemUsersCache.map(u => String(u.biometric_id)),
                ...Object.keys(deviceUsers).map(String)
            ]);

            allBiometricIds.forEach(bioId => {
                const sysUser = systemUsersCache.find(u => String(u.biometric_id) === bioId);
                const devUser = deviceUsers[bioId];
                
                let statusBadge = '';
                let actions = '';
                let sysName = sysUser ? sysUser.name : '<span class="text-muted">No existe en sistema</span>';
                
                if (sysUser && devUser) {
                    // Mismatch in name check could be added here
                    statusBadge = '<span class="badge bg-success"><i class="ph ph-check-circle"></i> Sincronizado</span>';
                    actions = `
                        <button class="btn btn-sm btn-outline-primary" onclick="pushUser(${sysUser.id})" title="Sobrescribir en dispositivo"><i class="ph ph-upload-simple"></i></button>
                        <button class="btn btn-sm btn-outline-danger" onclick="removeDeviceUser(${bioId})" title="Eliminar del dispositivo"><i class="ph ph-trash"></i></button>
                    `;
                } else if (sysUser && !devUser) {
                    statusBadge = '<span class="badge bg-warning text-dark"><i class="ph ph-warning-circle"></i> Solo en Sistema</span>';
                    actions = `<button class="btn btn-sm btn-primary" onclick="pushUser(${sysUser.id})"><i class="ph ph-upload-simple"></i> Enviar a Dispositivo</button>`;
                } else if (!sysUser && devUser) {
                    statusBadge = '<span class="badge bg-danger"><i class="ph ph-x-circle"></i> Solo en Dispositivo</span>';
                    sysName = `<span class="text-muted">Nombre en disp: ${devUser.name}</span>`;
                    actions = `<button class="btn btn-sm btn-outline-danger" onclick="removeDeviceUser(${bioId})" title="Eliminar del dispositivo"><i class="ph ph-trash"></i></button>`;
                }
                
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><strong>${bioId}</strong></td>
                    <td>${sysName}</td>
                    <td>${statusBadge}</td>
                    <td class="text-end">${actions}</td>
                `;
                tbody.appendChild(tr);
            });
            
        } else {
            tbody.innerHTML = `<tr><td colspan="4" class="text-center p-4 text-danger">Error: ${devData.message}</td></tr>`;
        }
    } catch(e) {
        console.error(e);
        tbody.innerHTML = `<tr><td colspan="4" class="text-center p-4 text-danger">Error de red al conectar.</td></tr>`;
    }
}

async function pushUser(systemUserId) {
    try {
        const fd = new FormData();
        fd.append('action', 'push_user');
        fd.append('user_id', systemUserId);
        const res = await fetch(`${baseUrl}/ajax/zkteco_sync.php`, { method: 'POST', body: fd });
        const data = await res.json();
        
        if (data.success) {
            window.showToast(data.message, 'success');
            loadDeviceUsers(); // Reload table
        } else {
            window.showToast(data.message, 'error');
        }
    } catch (e) {
        window.showToast('Error de conexión', 'error');
    }
}

async function pushAllUsers() {
    if (!confirm('¿Seguro que deseas enviar todos los usuarios del sistema al dispositivo?')) return;
    try {
        const fd = new FormData();
        fd.append('action', 'push_all_users');
        const res = await fetch(`${baseUrl}/ajax/zkteco_sync.php`, { method: 'POST', body: fd });
        const data = await res.json();
        
        if (data.success) {
            window.showToast(data.message, 'success');
            loadDeviceUsers();
        } else {
            window.showToast(data.message, 'error');
        }
    } catch (e) {
        window.showToast('Error de conexión', 'error');
    }
}

async function removeDeviceUser(uid) {
    if (!confirm('¿Seguro que deseas eliminar este usuario DEL DISPOSITIVO FÍSICO? (No se borrará del sistema)')) return;
    try {
        const fd = new FormData();
        fd.append('action', 'remove_device_user');
        fd.append('uid', uid);
        const res = await fetch(`${baseUrl}/ajax/zkteco_sync.php`, { method: 'POST', body: fd });
        const data = await res.json();
        
        if (data.success) {
            window.showToast(data.message, 'success');
            loadDeviceUsers();
        } else {
            window.showToast(data.message, 'error');
        }
    } catch (e) {
        window.showToast('Error de conexión', 'error');
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
        
        const res = await fetch(`${baseUrl}/ajax/attendance_ops.php`, { method: 'POST', body: fd });
        const data = await res.json();
        
        loading.style.display = 'none';
        
        if (data.success && data.data.length > 0) {
            data.data.forEach(row => {
                const tr = document.createElement('tr');
                
                let typeBadge = '';
                if (row.type === 'entrada') {
                    typeBadge = '<span class="badge bg-success-subtle text-success" style="padding: 6px 10px;"><i class="ph-bold ph-sign-in"></i> Entrada</span>';
                } else if (row.type === 'salida') {
                    typeBadge = '<span class="badge bg-danger-subtle text-danger" style="padding: 6px 10px;"><i class="ph-bold ph-sign-out"></i> Salida</span>';
                } else if (row.type === 'inicio_refrigerio') {
                    typeBadge = '<span class="badge bg-warning-subtle text-warning" style="padding: 6px 10px;"><i class="ph-bold ph-coffee"></i> Inic. Refrigerio</span>';
                } else if (row.type === 'fin_refrigerio') {
                    typeBadge = '<span class="badge bg-info-subtle text-info" style="padding: 6px 10px;"><i class="ph-bold ph-arrow-u-down-left"></i> Fin Refrigerio</span>';
                } else {
                    typeBadge = '<span class="badge bg-secondary-subtle text-secondary" style="padding: 6px 10px;"><i class="ph-bold ph-question"></i> Desconocido</span>';
                }
                
                const dt = new Date(row.created_at);
                const dateString = dt.toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' });
                const timeString = dt.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
                
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
                    <td><strong>${dateString}</strong></td>
                    <td><span class="text-muted"><i class="ph ph-clock"></i> ${timeString}</span></td>
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

async function syncZkteco(isAuto = false) {
    const btn = document.getElementById('btnSync');
    
    if (!isAuto) {
        btn.disabled = true;
        btn.innerHTML = '<i class="ph ph-spinner ph-spin"></i> Sincronizando...';
    }
    
    try {
        const fd = new FormData();
        fd.append('action', 'sync');
        const res = await fetch(`${baseUrl}/ajax/zkteco_sync.php`, { method: 'POST', body: fd });
        const data = await res.json();
        
        if (data.success) {
            if (!isAuto || data.inserted > 0) {
                window.showToast(data.message, 'success');
                loadAttendance();
            }
            checkDeviceStatus(); // Actualiza última vez conectado
        } else {
            if (!isAuto) window.showToast(data.message || 'Error al conectar al dispositivo', 'error');
            checkDeviceStatus();
        }
    } catch (e) {
        console.error(e);
        if (!isAuto) window.showToast('Error de red al sincronizar', 'error');
    } finally {
        if (!isAuto) {
            btn.disabled = false;
            btn.innerHTML = '<i class="ph ph-arrows-clockwise"></i> Sincronizar ZKTeco';
        }
    }
}

async function openZkModal() {
    try {
        const fd = new FormData();
        fd.append('action', 'get_settings');
        const res = await fetch(`${baseUrl}/ajax/zkteco_sync.php`, { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            document.getElementById('zk_ip').value = data.data.ip || '';
            document.getElementById('zk_port').value = data.data.port || '4370';
        }
    } catch(e) {}
    
    document.getElementById('testConnectionResult').innerHTML = '';
    document.getElementById('zkModal').classList.add('active');
}

async function testConnection() {
    const ip = document.getElementById('zk_ip').value;
    const port = document.getElementById('zk_port').value;
    const resultDiv = document.getElementById('testConnectionResult');
    
    if (!ip || !port) {
        resultDiv.innerHTML = '<span class="text-danger"><i class="ph ph-warning"></i> Ingresa IP y Puerto</span>';
        return;
    }
    
    resultDiv.innerHTML = '<span class="text-primary"><i class="ph ph-spinner ph-spin"></i> Probando conexión...</span>';
    
    try {
        // Guardamos temporalmente y luego consultamos el status
        const fdSave = new FormData();
        fdSave.append('action', 'save_settings');
        fdSave.append('ip', ip);
        fdSave.append('port', port);
        await fetch(`${baseUrl}/ajax/zkteco_sync.php`, { method: 'POST', body: fdSave });
        
        const fdStatus = new FormData();
        fdStatus.append('action', 'device_status');
        const resStatus = await fetch(`${baseUrl}/ajax/zkteco_sync.php`, { method: 'POST', body: fdStatus });
        const dataStatus = await resStatus.json();
        
        if (dataStatus.success) {
            resultDiv.innerHTML = `<span class="text-success fw-bold"><i class="ph ph-check-circle"></i> Conexión exitosa a ${dataStatus.data.deviceName}</span>`;
            checkDeviceStatus();
        } else {
            resultDiv.innerHTML = `<span class="text-danger"><i class="ph ph-x-circle"></i> ${dataStatus.message}</span>`;
        }
    } catch(e) {
        resultDiv.innerHTML = '<span class="text-danger"><i class="ph ph-x-circle"></i> Error de red al probar conexión</span>';
    }
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
        const res = await fetch(`${baseUrl}/ajax/zkteco_sync.php`, { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            window.showToast('Configuración guardada', 'success');
            document.getElementById('zkModal').classList.remove('active');
            checkDeviceStatus();
        } else {
            window.showToast(data.message || 'Error', 'error');
        }
    } catch(e) {
        console.error(e);
        window.showToast('Error de red', 'error');
    }
}

function exportToCSV() {
    const table = document.getElementById('attendanceTable');
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    for (let i = 0; i < rows.length; i++) {
        let row = [], cols = rows[i].querySelectorAll('td, th');
        for (let j = 0; j < cols.length; j++) {
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
