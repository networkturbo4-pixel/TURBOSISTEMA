<?php
require_once 'config/db.php';

if (!isset($_SESSION['public_cliente_id'])) {
    header('Location: soporte.php');
    exit;
}

$cliente_id = $_SESSION['public_cliente_id'];

// Get client data
$stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
$stmt->execute([$cliente_id]);
$cliente = $stmt->fetch();

if (!$cliente) {
    session_destroy();
    header('Location: soporte.php');
    exit;
}

// Get tickets
$stmtTickets = $pdo->prepare("
    SELECT t.*, tc.name as cat_name, tp.name as pri_name 
    FROM tickets t 
    LEFT JOIN ticket_categories tc ON t.categoria_id = tc.id 
    LEFT JOIN ticket_priorities tp ON t.prioridad_id = tp.id 
    WHERE t.cliente_id = ? AND t.estado != 'eliminado' ORDER BY t.created_at DESC
");
$stmtTickets->execute([$cliente_id]);
$tickets = $stmtTickets->fetchAll();

$categorias = $pdo->query("SELECT * FROM ticket_categories ORDER BY name")->fetchAll();
$prioridades = $pdo->query("SELECT * FROM ticket_priorities ORDER BY level DESC, name ASC")->fetchAll();

$primaryColor = '#064e3b'; // Default

// Handle logout via GET parameter
if (isset($_GET['logout'])) {
    unset($_SESSION['public_cliente_id']);
    unset($_SESSION['public_cliente_nombre']);
    header('Location: soporte.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
    <title>Portal del Cliente - <?php echo htmlspecialchars($cliente['nombre_completo']); ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body {
            background-color: #f1f5f9;
            font-family: 'Inter', sans-serif;
            color: #334155;
            margin: 0;
            padding: 0;
            display: block !important; 
            height: auto !important;
            overflow: auto !important;
        }

        .portal-header {
            background: linear-gradient(135deg, <?php echo htmlspecialchars($primaryColor); ?> 0%, #020617 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .portal-header-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .portal-avatar {
            width: 50px;
            height: 50px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
        }

        .portal-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 20px;
        }

        @media (max-width: 992px) {
            .portal-container {
                grid-template-columns: 1fr;
            }
        }

        .portal-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
            padding: 25px;
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
        }

        .portal-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e2e8f0;
        }

        .portal-card-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-abierto { background: #fee2e2; color: #ef4444; }
        .status-pendiente { background: #fef3c7; color: #d97706; }
        .status-en_proceso { background: #dbeafe; color: #3b82f6; }
        .status-terminado { background: #dcfce3; color: #10b981; }

        .btn-portal {
            background: <?php echo htmlspecialchars($primaryColor); ?>;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-portal:hover { opacity: 0.9; color: white; }

        /* Modal Styles */
        .modal-overlay {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5); display: none;
            align-items: center; justify-content: center; z-index: 1000;
        }
        .modal-overlay.active { display: flex; }
        .modal-content {
            background: white; border-radius: 16px; padding: 30px; width: 100%; max-width: 500px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        .ticket-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .ticket-list-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.2s;
        }

        .ticket-list-item:hover {
            border-color: #cbd5e1;
            background: #f1f5f9;
        }

        .ticket-item-left {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .ticket-item-title {
            font-weight: 600;
            color: #0f172a;
            font-size: 1.05rem;
        }

        .ticket-item-meta {
            font-size: 0.85rem;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .ticket-item-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        @media (max-width: 600px) {
            .portal-header {
                padding: 15px;
            }
            .portal-container {
                margin: 15px auto;
                padding: 0 15px;
                gap: 15px;
            }
            .portal-card {
                padding: 20px 15px;
            }
            .ticket-list-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            .ticket-item-right {
                width: 100%;
                justify-content: space-between;
            }
            .btn-portal {
                padding: 8px 15px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>

<div class="portal-header">
    <div class="portal-header-info">
        <div class="portal-avatar"><?php echo strtoupper(substr($cliente['nombre_completo'], 0, 1)); ?></div>
        <div>
            <div style="font-size: 1.2rem; font-weight: bold;"><?php echo htmlspecialchars($cliente['nombre_completo']); ?></div>
            <div style="font-size: 0.9rem; color: #cbd5e1;">Portal de Cliente</div>
        </div>
    </div>
    <a href="portal.php?logout=1" style="color: white; text-decoration: none; display: flex; align-items: center; gap: 5px; font-weight: 600;">
        <i class="ph ph-sign-out"></i> Salir
    </a>
</div>

<div class="portal-container">
    <div class="main-column">
        
        <!-- Tarjeta: Mi Red Wi-Fi -->
        <div class="portal-card" style="border-top: 4px solid <?php echo htmlspecialchars($primaryColor); ?>;">
            <div class="portal-card-header">
                <div class="portal-card-title"><i class="ph-fill ph-wifi-high"></i> Mi WiFi</div>
                <button class="btn-portal" onclick="openWifiModal()">
                    <i class="ph ph-pencil-simple"></i> Configurar
                </button>
            </div>
            <div style="display: flex; align-items: center; gap: 20px; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 150px;">
                    <div style="font-size: 0.85rem; color: #64748b; margin-bottom: 4px;">Nombre del WiFi</div>
                    <div id="wifiSsidDisplay" style="font-size: 1.3rem; font-weight: bold; color: #0f172a;">Cargando...</div>
                </div>
                <div style="flex: 1; min-width: 120px;">
                    <div style="font-size: 0.85rem; color: #64748b; margin-bottom: 4px;">Gateway</div>
                    <div id="wifiGatewayDisplay" style="font-size: 1rem; font-weight: 600; color: #334155; font-family: monospace;">-</div>
                </div>
                <div style="flex: 1; min-width: 120px;">
                    <div style="font-size: 0.85rem; color: #64748b; margin-bottom: 4px;">Tu IP Local</div>
                    <div id="wifiLocalIpDisplay" style="font-size: 1rem; font-weight: 600; color: #334155; font-family: monospace;">-</div>
                </div>
                <div>
                    <div id="wifiStatusBadge" style="display: inline-flex; align-items: center; gap: 5px; background: #dcfce3; color: #10b981; padding: 5px 12px; border-radius: 20px; font-weight: 600; font-size: 0.85rem;">
                        <i class="ph-fill ph-check-circle"></i> En Línea
                    </div>
                </div>
            </div>
        </div>

        <!-- Tarjeta: Dispositivos Conectados -->
        <div class="portal-card">
            <div class="portal-card-header">
                <div class="portal-card-title"><i class="ph-fill ph-devices"></i> Dispositivos Conectados</div>
                <button class="btn-portal" onclick="loadDevices()" style="background: #e2e8f0; color: #0f172a;">
                    <i class="ph ph-arrows-clockwise"></i> Actualizar
                </button>
            </div>
            <div id="devicesContainer" class="ticket-list">
                <div style="text-align: center; padding: 20px; color: #64748b;">Cargando dispositivos...</div>
            </div>
        </div>
        
        <div class="portal-card">
            <div class="portal-card-header">
                <div class="portal-card-title"><i class="ph-fill ph-ticket"></i> Mis Tickets de Soporte</div>
                <button class="btn-portal" onclick="document.getElementById('ticketModal').classList.add('active')">
                    <i class="ph ph-plus"></i> Nuevo Ticket
                </button>
            </div>
            
            <?php if (empty($tickets)): ?>
                <div style="text-align: center; padding: 40px 20px; color: #64748b;">
                    <i class="ph ph-check-circle" style="font-size: 3rem; color: #10b981; margin-bottom: 15px;"></i>
                    <p>No tienes tickets de soporte activos. ¡Todo funciona excelente!</p>
                </div>
            <?php else: ?>
                <div class="ticket-list">
                    <?php foreach($tickets as $t): ?>
                    <div class="ticket-list-item">
                        <div class="ticket-item-left">
                            <div class="ticket-item-title"><?php echo htmlspecialchars($t['asunto']); ?></div>
                            <div class="ticket-item-meta">
                                <span style="font-weight: 600;">#TKT-<?php echo str_pad($t['id'], 4, '0', STR_PAD_LEFT); ?></span>
                                <span>&bull;</span>
                                <span><?php echo htmlspecialchars($t['cat_name'] ?: 'Sin Categoría'); ?></span>
                                <span>&bull;</span>
                                <span><?php echo date('d/m/Y H:i', strtotime($t['created_at'])); ?></span>
                            </div>
                        </div>
                        <div class="ticket-item-right">
                            <span class="status-badge status-<?php echo strtolower($t['estado']); ?>"><?php echo htmlspecialchars($t['estado']); ?></span>
                            <a href="ticket.php?id=<?php echo $t['id']; ?>&token=<?php echo $t['public_token']; ?>" class="btn-portal" style="padding: 8px 15px; font-size: 0.85rem; background: #e2e8f0; color: #0f172a;">
                                Ver Chat
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
    </div>
    
    <div class="side-column">
        
        <div class="portal-card" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: white;">
            <div class="portal-card-header" style="border-color: rgba(255,255,255,0.1); margin-bottom: 10px; padding-bottom: 10px;">
                <div class="portal-card-title" style="color: white;"><i class="ph-fill ph-rocket"></i> Mi Plan Actual</div>
            </div>
            <div style="font-size: 1.5rem; font-weight: bold; margin-bottom: 5px; color: #38bdf8;">
                <?php echo !empty($cliente['detalles_plan']) ? htmlspecialchars($cliente['detalles_plan']) : 'Plan no especificado'; ?>
            </div>
            <div style="font-size: 0.9rem; color: #cbd5e1; margin-bottom: 15px;">
                Inicio de servicio: <?php echo !empty($cliente['inicio_servicio']) ? date('d/m/Y', strtotime($cliente['inicio_servicio'])) : 'Pendiente'; ?>
            </div>
        </div>

        <div class="portal-card">
            <div class="portal-card-header" style="margin-bottom: 10px; padding-bottom: 10px;">
                <div class="portal-card-title"><i class="ph-fill ph-receipt"></i> Próximos Recibos</div>
            </div>
            <div style="text-align: center; padding: 20px 0; color: #64748b;">
                <i class="ph ph-check-circle" style="font-size: 2.5rem; color: #10b981; margin-bottom: 10px;"></i>
                <div style="font-weight: 600;">Estás al día</div>
                <div style="font-size: 0.85rem;">No tienes recibos pendientes.</div>
            </div>
        </div>
        
    </div>
</div>

<!-- Modal Create Ticket -->
<div class="modal-overlay" id="ticketModal">
    <div class="modal-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; font-size: 1.4rem;">Nuevo Ticket</h3>
            <button style="background: none; border: none; font-size: 1.5rem; cursor: pointer;" onclick="document.getElementById('ticketModal').classList.remove('active')">&times;</button>
        </div>
        
        <form id="newTicketForm">
            <input type="hidden" name="cliente_id" value="<?php echo $cliente_id; ?>">
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Asunto</label>
                <input type="text" name="asunto" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; box-sizing: border-box;">
            </div>
            <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                <div style="flex: 1;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Categoría</label>
                    <select name="categoria_id" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px;">
                        <option value="">Seleccione...</option>
                        <?php foreach($categorias as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="flex: 1;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Prioridad</label>
                    <select name="prioridad_id" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px;">
                        <option value="">Seleccione...</option>
                        <?php foreach($prioridades as $pri): ?>
                            <option value="<?php echo $pri['id']; ?>"><?php echo htmlspecialchars($pri['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Descripción detallada</label>
                <textarea name="descripcion" rows="4" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; box-sizing: border-box; resize: none;"></textarea>
            </div>
            <button type="submit" class="btn-portal" style="width: 100%; justify-content: center;" id="btnSubmitForm">
                <i class="ph ph-paper-plane-right"></i> Generar Ticket
            </button>
        </form>
    </div>
</div>

<!-- Modal Configure Wi-Fi -->
<div class="modal-overlay" id="wifiModal">
    <div class="modal-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; font-size: 1.4rem;"><i class="ph-fill ph-wifi-high"></i> Configurar Wi-Fi</h3>
            <button style="background: none; border: none; font-size: 1.5rem; cursor: pointer;" onclick="document.getElementById('wifiModal').classList.remove('active')">&times;</button>
        </div>
        
        <form id="wifiForm">
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Nombre de la Red (SSID)</label>
                <input type="text" name="ssid" id="wifiInputSsid" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; box-sizing: border-box;">
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Nueva Contraseña</label>
                <input type="password" name="password" id="wifiInputPassword" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; box-sizing: border-box;" placeholder="Mínimo 8 caracteres">
            </div>
            <button type="submit" class="btn-portal" style="width: 100%; justify-content: center;" id="btnSubmitWifi">
                <i class="ph ph-check-circle"></i> Guardar Cambios
            </button>
        </form>
    </div>
</div>

<!-- Modal Rename Device -->
<div class="modal-overlay" id="renameModal">
    <div class="modal-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; font-size: 1.4rem;"><i class="ph-fill ph-pencil-simple"></i> Renombrar Dispositivo</h3>
            <button style="background: none; border: none; font-size: 1.5rem; cursor: pointer;" onclick="document.getElementById('renameModal').classList.remove('active')">&times;</button>
        </div>
        
        <form id="renameForm">
            <input type="hidden" name="mac" id="renameInputMac">
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Nombre del Dispositivo</label>
                <input type="text" name="name" id="renameInputName" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; box-sizing: border-box;" placeholder="Ej: Celular de María">
            </div>
            <button type="submit" class="btn-portal" style="width: 100%; justify-content: center;" id="btnSubmitRename">
                <i class="ph ph-check-circle"></i> Guardar
            </button>
        </form>
    </div>
</div>

<script>
    // === TICKET FORM ===
    document.getElementById('newTicketForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(e.target);
        fd.append('action', 'create_public_ticket');
        const btn = document.getElementById('btnSubmitForm');
        btn.disabled = true;
        btn.innerHTML = 'Procesando...';
        try {
            const res = await fetch('<?php echo BASE_URL; ?>/ajax/soporte.php', { method: 'POST', body: fd }).then(r=>r.json());
            if(res.success) {
                window.location.href = `ticket.php?id=${res.ticket_id}&token=${res.token}`;
            } else {
                alert(res.message || 'Error al crear el ticket.');
                btn.disabled = false;
                btn.innerHTML = '<i class="ph ph-paper-plane-right"></i> Generar Ticket';
            }
        } catch(err) {
            alert('Error de conexión.');
            btn.disabled = false;
            btn.innerHTML = '<i class="ph ph-paper-plane-right"></i> Generar Ticket';
        }
    });

    // === WIFI Y DISPOSITIVOS ===
    const API_URL = '<?php echo BASE_URL; ?>/ajax/router_api.php';

    async function apiCall(action, postData) {
        const url = postData ? API_URL : API_URL + '?action=' + action;
        const opts = {};
        if (postData) {
            const fd = new FormData();
            fd.append('action', action);
            for (const k in postData) fd.append(k, postData[k]);
            opts.method = 'POST';
            opts.body = fd;
        }
        const response = await fetch(url, opts);
        const text = await response.text();
        try {
            return JSON.parse(text);
        } catch(e) {
            console.error('API response not JSON for action=' + action + ':', text.substring(0, 500));
            return { success: false, message: 'Respuesta inválida del servidor' };
        }
    }

    // --- WIFI ---
    async function loadWifi() {
        try {
            const res = await apiCall('get_wifi');
            if (res.success && res.data) {
                document.getElementById('wifiSsidDisplay').innerText = res.data.ssid || 'Sin nombre';
                if (document.getElementById('wifiInputSsid')) document.getElementById('wifiInputSsid').value = res.data.ssid || '';
                if (document.getElementById('wifiInputPassword')) document.getElementById('wifiInputPassword').value = res.data.password || '';
                if (res.data.gateway_ip && document.getElementById('wifiGatewayDisplay')) {
                    document.getElementById('wifiGatewayDisplay').innerText = res.data.gateway_ip;
                }
                if (res.data.local_ip && document.getElementById('wifiLocalIpDisplay')) {
                    document.getElementById('wifiLocalIpDisplay').innerText = res.data.local_ip;
                }
            } else {
                document.getElementById('wifiSsidDisplay').innerText = res.message || 'No disponible';
            }
        } catch(e) {
            console.error('loadWifi error:', e);
            document.getElementById('wifiSsidDisplay').innerText = 'Error al cargar';
        }
    }

    function openWifiModal() {
        document.getElementById('wifiModal').classList.add('active');
    }

    document.getElementById('wifiForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = document.getElementById('btnSubmitWifi');
        btn.disabled = true; btn.innerHTML = 'Guardando...';
        try {
            const res = await apiCall('set_wifi', {
                ssid: document.getElementById('wifiInputSsid').value,
                password: document.getElementById('wifiInputPassword').value
            });
            alert(res.message || (res.success ? 'Guardado' : 'Error'));
            if (res.success) {
                document.getElementById('wifiModal').classList.remove('active');
                loadWifi();
            }
        } catch(e) { alert('Error de conexión'); }
        btn.disabled = false; btn.innerHTML = '<i class="ph ph-check-circle"></i> Guardar Cambios';
    });

    // --- DISPOSITIVOS ---
    async function loadDevices() {
        const container = document.getElementById('devicesContainer');
        container.innerHTML = '<div style="text-align:center;padding:30px;color:#64748b;"><i class="ph ph-spinner" style="font-size:1.5rem;animation:spin 1s linear infinite;"></i><div style="margin-top:8px;">Escaneando red...</div></div>';

        try {
            const res = await apiCall('get_devices');
            if (res.success && res.data) {
                if (res.data.length === 0) {
                    container.innerHTML = '<div style="text-align:center;padding:30px;color:#64748b;"><i class="ph ph-wifi-slash" style="font-size:2rem;margin-bottom:8px;"></i><div>No se encontraron dispositivos.</div></div>';
                    return;
                }
                let html = '<div style="font-size:0.85rem;color:#64748b;margin-bottom:10px;font-weight:600;">' + res.data.length + ' dispositivo(s) encontrado(s)</div>';
                res.data.forEach(dev => {
                    let icon = 'ph-desktop';
                    let iconBg = '#e2e8f0';
                    let iconColor = '#475569';
                    if (dev.device_type === 'phone') icon = 'ph-device-mobile';
                    if (dev.device_type === 'tv') icon = 'ph-television';
                    if (dev.device_type === 'router' || dev.is_gateway) {
                        icon = 'ph-globe-simple';
                        iconBg = '#dbeafe';
                        iconColor = '#2563eb';
                    }
                    if (dev.is_self) {
                        icon = 'ph-monitor';
                        iconBg = '#dcfce7';
                        iconColor = '#16a34a';
                    }

                    let borderColor = dev.blocked ? '#ef4444' : (dev.is_gateway ? '#3b82f6' : (dev.is_self ? '#10b981' : '#e2e8f0'));

                    let actions = '';
                    if (!dev.is_gateway && !dev.is_self) {
                        let blockBtn = dev.blocked
                            ? `<button onclick="toggleBlock('${dev.mac}', false)" type="button" class="btn-portal" style="padding:6px 12px;font-size:0.8rem;background:#10b981;"><i class="ph ph-lock-key-open"></i> Desbloquear</button>`
                            : `<button onclick="toggleBlock('${dev.mac}', true)" type="button" class="btn-portal" style="padding:6px 12px;font-size:0.8rem;background:#ef4444;"><i class="ph ph-lock-key"></i> Bloquear</button>`;
                        actions = `
                            <button onclick="openRenameModal('${dev.mac}', '${(dev.name||'').replace(/'/g, "\\'")}')" type="button" class="btn-portal" style="padding:6px 12px;font-size:0.8rem;background:#e2e8f0;color:#0f172a;"><i class="ph ph-pencil-simple"></i> Renombrar</button>
                            ${blockBtn}`;
                    }

                    let trafficInfo = '';
                    if (dev.tx && dev.rx && dev.tx !== '-' && dev.rx !== '-') {
                        trafficInfo = `<div class="ticket-item-meta" style="margin-top:3px;color:#3b82f6;">
                            <i class="ph ph-arrow-down"></i> ${dev.rx} &nbsp;&nbsp; <i class="ph ph-arrow-up"></i> ${dev.tx}
                        </div>`;
                    }

                    html += `
                    <div class="ticket-list-item" style="opacity:${dev.blocked?'0.5':'1'};border-left:4px solid ${borderColor};">
                        <div class="ticket-item-left" style="flex-direction:row;align-items:center;gap:15px;">
                            <div style="width:42px;height:42px;background:${iconBg};border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.3rem;color:${iconColor};flex-shrink:0;">
                                <i class="ph-fill ${icon}"></i>
                            </div>
                            <div>
                                <div class="ticket-item-title" style="font-size:0.95rem;">${dev.name || 'Dispositivo Desconocido'}</div>
                                <div class="ticket-item-meta" style="margin-top:3px;">
                                    <span style="font-family:monospace;font-size:0.8rem;">${dev.mac}</span> &bull; <span>${dev.ip || 'Sin IP'}</span>
                                </div>
                                ${trafficInfo}
                            </div>
                        </div>
                        <div class="ticket-item-right" style="flex-wrap:wrap;gap:8px;">
                            ${actions}
                        </div>
                    </div>`;
                });
                container.innerHTML = html;
            } else {
                container.innerHTML = `<div style="text-align:center;padding:20px;color:#ef4444;">${res.message || 'Error al cargar'}</div>`;
            }
        } catch(e) {
            console.error('loadDevices error:', e);
            container.innerHTML = '<div style="text-align:center;padding:20px;color:#ef4444;">Error de conexión al servidor.</div>';
        }
    }

    // --- RENOMBRAR ---
    function openRenameModal(mac, name) {
        document.getElementById('renameInputMac').value = mac;
        document.getElementById('renameInputName').value = name;
        document.getElementById('renameModal').classList.add('active');
    }

    document.getElementById('renameForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        try {
            const res = await apiCall('rename_device', {
                mac: document.getElementById('renameInputMac').value,
                name: document.getElementById('renameInputName').value
            });
            if (res.success) {
                document.getElementById('renameModal').classList.remove('active');
                loadDevices();
            } else alert(res.message);
        } catch(e) { alert('Error de conexión'); }
    });

    // --- BLOQUEAR ---
    async function toggleBlock(mac, block) {
        if (!confirm(`¿Seguro que deseas ${block ? 'bloquear' : 'desbloquear'} este dispositivo?`)) return;
        try {
            const res = await apiCall('block_device', { mac, block });
            if (res.success) loadDevices();
            else alert(res.message);
        } catch(e) { alert('Error de conexión'); }
    }

    // === INICIALIZACIÓN - A PRUEBA DE FALLOS ===
    function initPortal() {
        console.log('Portal: Iniciando carga de WiFi y dispositivos...');
        loadWifi();
        loadDevices();
    }

    // Intentar con DOMContentLoaded, y también con un setTimeout como respaldo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPortal);
    } else {
        // DOM ya está listo
        initPortal();
    }
</script>

<style>
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
</style>

</body>
</html>

