<?php
require_once 'config/db.php';
requireLogin();

$user_role = $_SESSION['user_role'] ?? 'user';
$user_id = $_SESSION['user_id'] ?? 0;

if ($user_role === 'admin') {
    // Consultas reales para el dashboard ADMIN
    $stmtUsers = $pdo->query("SELECT COUNT(*) FROM users");
    $totalUsers = $stmtUsers->fetchColumn();

    $stmtActas = $pdo->query("SELECT COUNT(*) FROM actas");
    $totalActas = $stmtActas->fetchColumn();

    $stmtRoles = $pdo->query("SELECT COUNT(*) FROM roles");
    $totalRoles = $stmtRoles->fetchColumn();
} else {
    // Consultas reales para el dashboard TECNICO
    $stmtTickets = $pdo->prepare("
        SELECT t.*, c.nombre_completo as cliente_nombre 
        FROM tickets t
        LEFT JOIN clientes c ON t.cliente_id = c.id
        WHERE t.assigned_to = ? AND t.estado != 'terminado'
        ORDER BY t.created_at DESC
    ");
    $stmtTickets->execute([$user_id]);
    $assignedTickets = $stmtTickets->fetchAll(PDO::FETCH_ASSOC);

    // Contar items en mochila (normal + granel)
    $stmtMochilaNormal = $pdo->prepare("SELECT COUNT(*) FROM inventory_skus WHERE assigned_to = ? AND status != 'instalado'");
    $stmtMochilaNormal->execute([$user_id]);
    $normalCount = $stmtMochilaNormal->fetchColumn();

    $stmtMochilaBulk = $pdo->prepare("SELECT SUM(quantity) FROM inventory_user_stock WHERE user_id = ?");
    $stmtMochilaBulk->execute([$user_id]);
    $bulkCount = $stmtMochilaBulk->fetchColumn() ?: 0;

    $totalMochila = $normalCount + $bulkCount;

    // Contar Actas creadas este mes
    $stmtActasTech = $pdo->prepare("SELECT COUNT(*) FROM actas WHERE tecnico_id = ? AND MONTH(fecha_creacion) = MONTH(CURRENT_DATE()) AND YEAR(fecha_creacion) = YEAR(CURRENT_DATE())");
    $stmtActasTech->execute([$user_id]);
    $techActasMes = $stmtActasTech->fetchColumn();
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="page-header-card">
    <div class="page-header-left">
        <div class="page-header-icon">
            <i class="ph ph-squares-four"></i>
        </div>
        <div class="page-header-info">
            <h2>Panel de Control</h2>
            <p>Bienvenido de nuevo, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Usuario'); ?>.</p>
        </div>
    </div>
</div>

<?php if ($user_role === 'admin'): ?>
    <!-- VISTA ADMIN: Tarjetas de Resumen (Métricas Reales) -->
    <div class="row g-2 mb-4">
        <div class="col-md-4 col-6">
            <div class="card p-3 p-md-4 h-100 metric-card" style="border-radius: 16px; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
                <div class="d-flex flex-row align-items-center justify-content-between h-100">
                    <div class="metric-icon" style="background: rgba(13,110,253, 0.1);">
                        <i class="ph ph-users" style="color: var(--primary-color);"></i>
                    </div>
                    <div class="text-end">
                        <p class="metric-value mb-0" style="color: var(--primary-color); line-height: 1;"><?php echo $totalUsers; ?></p>
                        <h3 class="text-muted mt-1 mb-0 metric-title">Usuarios</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-6">
            <div class="card p-3 p-md-4 h-100 metric-card" style="border-radius: 16px; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
                <div class="d-flex flex-row align-items-center justify-content-between h-100">
                    <div class="metric-icon" style="background: rgba(16, 185, 129, 0.1);">
                        <i class="ph ph-clipboard-text" style="color: var(--success-color, #10b981);"></i>
                    </div>
                    <div class="text-end">
                        <p class="metric-value mb-0" style="color: var(--success-color, #10b981); line-height: 1;"><?php echo $totalActas; ?></p>
                        <h3 class="text-muted mt-1 mb-0 metric-title">Actas</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-6">
            <div class="card p-3 p-md-4 h-100 metric-card" style="border-radius: 16px; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
                <div class="d-flex flex-row align-items-center justify-content-between h-100">
                    <div class="metric-icon" style="background: rgba(245, 158, 11, 0.1);">
                        <i class="ph ph-shield-check" style="color: var(--warning-color, #f59e0b);"></i>
                    </div>
                    <div class="text-end">
                        <p class="metric-value mb-0" style="color: var(--warning-color, #f59e0b); line-height: 1;"><?php echo $totalRoles; ?></p>
                        <h3 class="text-muted mt-1 mb-0 metric-title">Roles</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- VISTA TÉCNICO -->
    
    <!-- Métricas Superiores (Igual que Admin) -->
    <div class="row g-2 mb-4">
        <div class="col-md-4 col-6">
            <div class="card p-3 p-md-4 h-100 metric-card" style="border-radius: 16px; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
                <div class="d-flex flex-row align-items-center justify-content-between h-100">
                    <div class="metric-icon" style="background: rgba(13,110,253, 0.1);">
                        <i class="ph ph-ticket" style="color: var(--primary-color);"></i>
                    </div>
                    <div class="text-end">
                        <p class="metric-value mb-0" style="color: var(--primary-color); line-height: 1;"><?php echo count($assignedTickets); ?></p>
                        <h3 class="text-muted mt-1 mb-0 metric-title">Mis Tickets Activos</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-6">
            <div class="card p-3 p-md-4 h-100 metric-card" style="border-radius: 16px; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
                <div class="d-flex flex-row align-items-center justify-content-between h-100">
                    <div class="metric-icon" style="background: rgba(16, 185, 129, 0.1);">
                        <i class="ph ph-clipboard-text" style="color: var(--success-color, #10b981);"></i>
                    </div>
                    <div class="text-end">
                        <p class="metric-value mb-0" style="color: var(--success-color, #10b981); line-height: 1;"><?php echo $techActasMes; ?></p>
                        <h3 class="text-muted mt-1 mb-0 metric-title">Mis Actas (Mes)</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-12 mt-2 mt-md-0">
            <div class="card p-3 p-md-4 h-100 metric-card" style="border-radius: 16px; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.03); cursor:pointer;" onclick="openProfileModal(); switchProfileTab('mochila', document.querySelector('.profile-tab-btn[onclick*=\'mochila\']'));">
                <div class="d-flex flex-row align-items-center justify-content-between h-100">
                    <div class="metric-icon" style="background: rgba(245, 158, 11, 0.1);">
                        <i class="ph ph-backpack" style="color: var(--warning-color, #f59e0b);"></i>
                    </div>
                    <div class="text-end">
                        <p class="metric-value mb-0" style="color: var(--warning-color, #f59e0b); line-height: 1;"><?php echo $totalMochila; ?></p>
                        <h3 class="text-muted mt-1 mb-0 metric-title">Mi Mochila (Items)</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Apartado tipo App -->
    <div class="row g-3 mb-4">
        <!-- Acciones Rápidas -->
        <div class="col-12 col-md-4">
            <div class="card p-3 h-100" style="border-radius: 16px; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
                <h5 class="mb-3 text-dark"><i class="ph ph-lightning me-2 text-warning"></i>Acciones Rápidas</h5>
                <div class="row g-2">
                    <div class="col-6">
                        <div class="card h-100 shortcut-card" style="transition: transform 0.2s, box-shadow 0.2s; cursor: pointer; border-radius: 16px; border: 1px solid var(--primary-color); background: rgba(13,110,253, 0.05);" onclick="window.location.href='modules/actas/create.php'">
                            <div class="card-body text-center p-3">
                                <i class="ph ph-plus-circle" style="color: var(--primary-color); font-size: 2rem; margin-bottom: 5px;"></i>
                                <h6 class="shortcut-title" style="color: var(--primary-color); font-weight: 600; font-size: 0.9rem;">Crear Acta</h6>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card h-100 shortcut-card" style="transition: transform 0.2s, box-shadow 0.2s; cursor: pointer; border-radius: 16px; border: 1px solid var(--border-color); background: transparent;" onclick="window.location.href='modules/actas/'">
                            <div class="card-body text-center p-3">
                                <i class="ph ph-clipboard-text" style="color: var(--text-muted); font-size: 2rem; margin-bottom: 5px;"></i>
                                <h6 class="shortcut-title" style="color: var(--text-color); font-weight: 600; font-size: 0.9rem;">Mis Actas</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tickets Asignados -->
        <div class="col-12 col-md-8" id="tickets-container">
            <div class="card p-3 h-100" style="border-radius: 16px; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.03); background: transparent;">
                <h5 class="mb-3 d-flex align-items-center"><i class="ph ph-ticket me-2 text-primary"></i>Tickets Asignados <span class="badge bg-danger ms-2 rounded-pill"><?php echo count($assignedTickets); ?></span></h5>
            <div class="row g-3">
                <?php if (count($assignedTickets) > 0): ?>
                    <?php foreach ($assignedTickets as $ticket): 
                        // Color coding based on status
                        $statusColor = 'secondary';
                        if($ticket['estado'] == 'abierto') $statusColor = 'success';
                        else if($ticket['estado'] == 'pendiente') $statusColor = 'warning';
                        else if($ticket['estado'] == 'en_proceso') $statusColor = 'primary';
                    ?>
                    <div class="col-12 col-lg-6">
                        <div class="card p-3 ticket-app-card" style="border-radius: 16px; border: 1px solid var(--border-color); box-shadow: 0 2px 10px rgba(0,0,0,0.02);">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge bg-<?php echo $statusColor; ?>-subtle text-<?php echo $statusColor; ?> rounded-pill px-3 py-2">
                                    <i class="ph ph-circle-fill me-1" style="font-size:0.5rem;"></i> <?php echo ucfirst(str_replace('_', ' ', $ticket['estado'])); ?>
                                </span>
                                <small class="text-muted"><i class="ph ph-clock me-1"></i><?php echo date('d M, H:i', strtotime($ticket['created_at'])); ?></small>
                            </div>
                            <h5 class="ticket-title mt-2 mb-1 text-truncate" style="font-weight: 600; color: var(--text-color);"><?php echo htmlspecialchars($ticket['asunto']); ?></h5>
                            <p class="text-muted mb-3 d-flex align-items-center text-truncate"><i class="ph ph-user me-2"></i><?php echo htmlspecialchars($ticket['cliente_nombre'] ?? 'Cliente Desconocido'); ?></p>
                            
                            <div class="d-flex gap-2 mt-auto">
                                <button class="btn btn-primary flex-grow-1 rounded-pill" onclick="window.location.href='ticket.php?id=<?php echo $ticket['id']; ?>&token=<?php echo $ticket['public_token']; ?>'">
                                    <i class="ph ph-chat-circle-dots me-1"></i> Chat
                                </button>
                                <button class="btn btn-light rounded-pill px-3" onclick="viewClientData(<?php echo $ticket['cliente_id']; ?>)" title="Ver Datos del Cliente">
                                    <i class="ph ph-user-list text-muted"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="card p-5 text-center" style="border-radius: 16px; border: 2px dashed var(--border-color); background: transparent;">
                            <i class="ph ph-check-circle text-success mb-2" style="font-size: 3rem;"></i>
                            <h5 class="text-muted">No tienes tickets asignados activos</h5>
                            <p class="text-muted small">¡Buen trabajo! Estás al día con tus tareas.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            </div>
        </div>
    </div>

    <!-- Modal Client Data -->
    <div class="modal-overlay" id="clientDataModal">
        <div class="modal-content" style="max-width: 400px; padding: 20px; border-radius: 20px;">
            <div class="modal-header" style="border: none; padding-bottom: 0;">
                <h4 style="margin: 0; color: var(--primary-color); display: flex; align-items: center; gap: 8px;"><i class="ph-fill ph-user-circle"></i> Datos del Cliente</h4>
                <button class="btn close-modal" style="background: transparent; border: none; font-size: 1.5rem; cursor: pointer;" onclick="document.getElementById('clientDataModal').classList.remove('active')">&times;</button>
            </div>
            <div class="modal-body" style="padding-top: 20px;" id="clientDataContent">
                <p>Cargando datos...</p>
            </div>
        </div>
    </div>

    <script>
        function viewClientData(clienteId) {
            if(!clienteId) {
                alert("Este ticket no tiene un cliente válido asociado.");
                return;
            }
            
            document.getElementById('clientDataModal').classList.add('active');
            document.getElementById('clientDataContent').innerHTML = '<div style="text-align:center; padding:20px;"><i class="ph ph-spinner ph-spin" style="font-size:2rem; color:var(--primary-color);"></i></div>';
            
            fetch(`ajax/clientes.php?action=get&id=${clienteId}`)
                .then(res => res.json())
                .then(res => {
                    if(res.success && res.data) {
                        const d = res.data;
                        document.getElementById('clientDataContent').innerHTML = `
                            <div style="background: var(--bg-color); padding: 15px; border-radius: 12px; margin-bottom: 10px;">
                                <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: bold; margin-bottom: 5px;">Nombre Completo</div>
                                <div style="font-size: 1rem; color: var(--text-color); font-weight: 500;">${d.nombre_completo}</div>
                            </div>
                            <div style="background: var(--bg-color); padding: 15px; border-radius: 12px; margin-bottom: 10px;">
                                <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: bold; margin-bottom: 5px;">DNI / RUC</div>
                                <div style="font-size: 1rem; color: var(--text-color); font-weight: 500;">${d.dni}</div>
                            </div>
                            <div style="background: var(--bg-color); padding: 15px; border-radius: 12px; margin-bottom: 10px;">
                                <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: bold; margin-bottom: 5px;">Contacto</div>
                                <div style="font-size: 1rem; color: var(--text-color); font-weight: 500;"><i class="ph ph-phone me-1"></i> ${d.celular || 'No registrado'}</div>
                                <div style="font-size: 0.9rem; color: var(--text-color); margin-top: 5px;"><i class="ph ph-envelope me-1"></i> ${d.correo || 'No registrado'}</div>
                            </div>
                            <div style="background: var(--bg-color); padding: 15px; border-radius: 12px;">
                                <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: bold; margin-bottom: 5px;">Dirección</div>
                                <div style="font-size: 1rem; color: var(--text-color); font-weight: 500;">${d.direccion || 'No registrada'}</div>
                                <div style="font-size: 0.9rem; color: var(--text-muted); margin-top: 5px;">Ref: ${d.referencia || 'N/A'}</div>
                            </div>
                        `;
                    } else {
                        document.getElementById('clientDataContent').innerHTML = '<p class="text-danger">Error al cargar datos del cliente.</p>';
                    }
                })
                .catch(() => {
                    document.getElementById('clientDataContent').innerHTML = '<p class="text-danger">Error de red.</p>';
                });
        }

        // Real-time updates for tickets
        setInterval(() => {
            fetch(window.location.href)
                .then(r => r.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newTickets = doc.getElementById('tickets-container');
                    if (newTickets) {
                        document.getElementById('tickets-container').innerHTML = newTickets.innerHTML;
                    }
                });
        }, 15000); // Actualizar cada 15 segundos
    </script>
<?php endif; ?>

<h3 class="mb-4 mt-4">Accesos Directos</h3>
<div class="row g-2 mb-4">
    <?php 
    global $system_modules;
    foreach ($system_modules as $key => $module):
        if ($key === 'dashboard') continue;
        if (hasAccess($pdo, $key)):
    ?>
    <div class="col-md-3 col-4">
        <div class="card h-100 shortcut-card" style="transition: transform 0.2s, box-shadow 0.2s; cursor: pointer; border-radius: 18px; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.03);" onclick="window.location.href='<?php echo BASE_URL . $module['url']; ?>'">
            <div class="card-body text-center p-2 p-md-3">
                <div class="shortcut-icon" style="background: rgba(13,110,253, 0.05); width: 48px; height: 48px;">
                    <i class="ph <?php echo htmlspecialchars($module['icon']); ?>" style="color: var(--primary-color); font-size: 1.5rem;"></i>
                </div>
                <h6 class="shortcut-title" style="color: var(--text-color); font-size: 0.85rem;"><?php echo htmlspecialchars($module['name']); ?></h6>
            </div>
        </div>
    </div>
    <?php 
        endif;
    endforeach; 
    ?>
</div>

<style>
    /* Estilos responsivos para métricas y accesos directos */
    .metric-title { font-size: 0.95rem; font-weight: 500; }
    .metric-value { font-size: 2.2rem; font-weight: 700; margin: 0; }
    .metric-icon { width: 55px; height: 55px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; }
    
    .shortcut-icon { width: 64px; height: 64px; border-radius: 18px; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px auto; box-shadow: inset 0 0 0 1px rgba(0,0,0,0.05); }
    .shortcut-title { font-weight: 600; margin-bottom: 0; }
    
    .ticket-app-card { transition: transform 0.2s; }
    .ticket-app-card:active { transform: scale(0.98); }

    @media (max-width: 575.98px) {
        .metric-title { font-size: 0.85rem; }
        .metric-value { font-size: 1.8rem; }
        .metric-icon { width: 45px; height: 45px; border-radius: 14px; font-size: 1.5rem; }
        
        .shortcut-icon { width: 44px; height: 44px; border-radius: 14px; margin-bottom: 6px; font-size: 1.3rem; }
        .shortcut-title { font-size: 0.8rem; margin-bottom: 0; }
    }

    .shortcut-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        border-color: var(--primary-color) !important;
    }
</style>

<?php include 'includes/footer.php'; ?>
