<?php
require_once 'config/db.php';
requireLogin();

$user_role = $_SESSION['user_role'] ?? 'user';
$user_id = $_SESSION['user_id'] ?? 0;
$user_name = $_SESSION['user_name'] ?? 'Usuario';

// Consultas Globales para el Panel de Control
try {
    // 1. Actas de Instalación
    $totalActas = (int)$pdo->query("SELECT COUNT(*) FROM actas")->fetchColumn();
    $actasMes = (int)$pdo->query("SELECT COUNT(*) FROM actas WHERE MONTH(fecha_creacion) = MONTH(CURRENT_DATE()) AND YEAR(fecha_creacion) = YEAR(CURRENT_DATE())")->fetchColumn();
    $actasHoy = (int)$pdo->query("SELECT COUNT(*) FROM actas WHERE DATE(fecha_creacion) = CURRENT_DATE()")->fetchColumn();

    // 2. Clientes & Planes
    $totalClientes = (int)$pdo->query("SELECT COUNT(*) FROM clientes")->fetchColumn();
    $clientesMes = (int)$pdo->query("SELECT COUNT(*) FROM clientes WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())")->fetchColumn();

    // 3. Soporte & Tickets
    $totalTickets = (int)$pdo->query("SELECT COUNT(*) FROM tickets")->fetchColumn();
    $ticketsAbiertos = (int)$pdo->query("SELECT COUNT(*) FROM tickets WHERE estado IN ('abierto', 'en_proceso', 'pendiente')")->fetchColumn();
    $ticketsTerminados = (int)$pdo->query("SELECT COUNT(*) FROM tickets WHERE estado = 'terminado'")->fetchColumn();

    // 4. Inventario & Stock
    $totalProducts = (int)$pdo->query("SELECT COUNT(*) FROM inventory_products WHERE is_deleted = 0")->fetchColumn();
    $totalSkus = (int)$pdo->query("SELECT COUNT(*) FROM inventory_skus WHERE is_deleted = 0")->fetchColumn();
    $disponiblesSkus = (int)$pdo->query("SELECT COUNT(*) FROM inventory_skus WHERE status = 'disponible' AND is_deleted = 0")->fetchColumn();
    $asignadosSkus = (int)$pdo->query("SELECT COUNT(*) FROM inventory_skus WHERE status = 'asignado' AND is_deleted = 0")->fetchColumn();
    $instaladosSkus = (int)$pdo->query("SELECT COUNT(*) FROM inventory_skus WHERE status = 'instalado' AND is_deleted = 0")->fetchColumn();

    // 5. Red de Fibra Óptica / Mapas
    $totalElementos = (int)$pdo->query("SELECT COUNT(*) FROM mapas_elementos")->fetchColumn();
    $totalPuertos = (int)$pdo->query("SELECT COUNT(*) FROM mapas_puertos")->fetchColumn();
    $puertosOcupados = (int)$pdo->query("SELECT COUNT(*) FROM mapas_puertos WHERE estado = 'ocupado'")->fetchColumn();
    $totalProyectos = (int)$pdo->query("SELECT COUNT(*) FROM mapas_proyectos")->fetchColumn();
    $pctOcupacion = $totalPuertos > 0 ? round(($puertosOcupados / $totalPuertos) * 100, 1) : 0;

    // 6. Personal & Usuarios
    $totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $totalTecnicos = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE LOWER(role) = 'tecnico' OR LOWER(role) = 'técnico'")->fetchColumn();
    $totalAdmins = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE LOWER(role) = 'admin' OR LOWER(role) = 'administrador'")->fetchColumn();

    // 7. Flota de Vehículos
    $stmtVehiculos = $pdo->query("SELECT * FROM activos_vehiculos ORDER BY id DESC LIMIT 4");
    $vehiculos = $stmtVehiculos->fetchAll(PDO::FETCH_ASSOC);
    $totalVehiculos = count($vehiculos);

    // 8. Tablas en vivo: Últimas Actas
    $stmtUltimasActas = $pdo->query("
        SELECT a.id, a.folio, a.prefijo, a.cliente_nombre, a.cliente_distrito, a.fecha_creacion, a.srv_tipo, a.srv_estado, u.name as tecnico_nombre
        FROM actas a
        LEFT JOIN users u ON a.tecnico_id = u.id
        ORDER BY a.id DESC LIMIT 6
    ");
    $ultimasActas = $stmtUltimasActas->fetchAll(PDO::FETCH_ASSOC);

    // 9. Tablas en vivo: Tickets Recientes
    $stmtUltimosTickets = $pdo->query("
        SELECT t.id, t.asunto, t.estado, t.prioridad_id, t.created_at, t.public_token,
               COALESCE(c.nombre_completo, t.cliente_nombre_manual, 'Cliente') as cliente_nombre,
               u.name as asignado_a
        FROM tickets t
        LEFT JOIN clientes c ON t.cliente_id = c.id
        LEFT JOIN users u ON t.assigned_to = u.id
        ORDER BY t.id DESC LIMIT 6
    ");
    $ultimosTickets = $stmtUltimosTickets->fetchAll(PDO::FETCH_ASSOC);

    // 10. Tablas en vivo: Clientes Recientes
    $stmtUltimosClientes = $pdo->query("
        SELECT c.id, c.nombre_completo, c.dni, c.celular, c.created_at, c.direccion, s.nombre as servicio_nombre, s.velocidad
        FROM clientes c
        LEFT JOIN servicios s ON c.servicio_id = s.id
        ORDER BY c.id DESC LIMIT 6
    ");
    $ultimosClientes = $stmtUltimosClientes->fetchAll(PDO::FETCH_ASSOC);

    // 11. Tablas en vivo: Movimientos Recientes de Inventario
    $stmtUltimosMovs = $pdo->query("
        SELECT id, created_at, action, notes, product_name, sku_code, assigned_to_name, quantity
        FROM inventory_assignment_log
        ORDER BY id DESC LIMIT 6
    ");
    $ultimosMovimientos = $stmtUltimosMovs->fetchAll(PDO::FETCH_ASSOC);

    // 12. Métricas por mes para el gráfico de actividad (Últimos 6 meses)
    $actasHistory = [];
    $ticketsHistory = [];
    $clientesHistory = [];
    $monthLabels = [];
    for ($i = 5; $i >= 0; $i--) {
        $mDate = date('Y-m', strtotime("-$i months"));
        $mLabel = date('M Y', strtotime("-$i months"));
        $monthLabels[] = $mLabel;

        $cActas = (int)$pdo->query("SELECT COUNT(*) FROM actas WHERE DATE_FORMAT(fecha_creacion, '%Y-%m') = '$mDate'")->fetchColumn();
        $cTickets = (int)$pdo->query("SELECT COUNT(*) FROM tickets WHERE DATE_FORMAT(created_at, '%Y-%m') = '$mDate'")->fetchColumn();
        $cClientes = (int)$pdo->query("SELECT COUNT(*) FROM clientes WHERE DATE_FORMAT(created_at, '%Y-%m') = '$mDate'")->fetchColumn();

        $actasHistory[] = $cActas;
        $ticketsHistory[] = $cTickets;
        $clientesHistory[] = $cClientes;
    }

} catch (PDOException $e) {
    // Si hay error en consulta, mantener valores seguros
    $totalActas = $actasMes = $actasHoy = $totalClientes = $clientesMes = $totalTickets = $ticketsAbiertos = $ticketsTerminados = 0;
    $totalProducts = $totalSkus = $disponiblesSkus = $asignadosSkus = $instaladosSkus = $totalElementos = $totalPuertos = $puertosOcupados = $totalProyectos = $pctOcupacion = 0;
    $totalUsers = $totalTecnicos = $totalAdmins = $totalVehiculos = 0;
    $vehiculos = $ultimasActas = $ultimosTickets = $ultimosClientes = $ultimosMovimientos = [];
    $monthLabels = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'];
    $actasHistory = [0,0,0,0,0,0];
    $ticketsHistory = [0,0,0,0,0,0];
    $clientesHistory = [0,0,0,0,0,0];
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<!-- ==================== DASHBOARD STYLES ==================== -->
<style>
    :root {
        --dash-orange: #f07d00;
        --dash-blue: #0e4194;
        --dash-blue-light: #2563eb;
        --dash-emerald: #10b981;
        --dash-purple: #8b5cf6;
        --dash-cyan: #06b6d4;
        --dash-amber: #f59e0b;
    }

    .dashboard-container {
        padding-bottom: 30px;
    }

    /* Executive Welcome Card */
    .welcome-hero-card {
        background: linear-gradient(135deg, rgba(14, 65, 148, 0.08) 0%, rgba(240, 125, 0, 0.06) 50%, rgba(255, 255, 255, 0.02) 100%);
        border: 1px solid rgba(0, 0, 0, 0.06);
        border-radius: 20px;
        padding: 24px 28px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
    }
    body.dark-theme .welcome-hero-card {
        background: linear-gradient(135deg, rgba(14, 65, 148, 0.25) 0%, rgba(240, 125, 0, 0.15) 50%, rgba(15, 23, 42, 0.6) 100%);
        border-color: rgba(255, 255, 255, 0.08);
    }
    .welcome-left {
        display: flex;
        align-items: center;
        gap: 16px;
    }
    .welcome-avatar {
        width: 52px;
        height: 52px;
        border-radius: 16px;
        background: linear-gradient(135deg, var(--dash-orange), var(--dash-blue));
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.6rem;
        color: #fff;
        box-shadow: 0 6px 16px rgba(240, 125, 0, 0.3);
        flex-shrink: 0;
    }
    .welcome-text h2 {
        font-size: 1.4rem;
        font-weight: 700;
        margin: 0 0 4px 0;
        color: var(--text-color);
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .welcome-text p {
        font-size: 0.88rem;
        color: var(--text-muted, #64748b);
        margin: 0;
    }
    .welcome-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    .btn-quick-action {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 14px;
        border-radius: 12px;
        font-size: 0.84rem;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s ease;
        border: 1px solid transparent;
    }
    .btn-quick-orange {
        background: rgba(240, 125, 0, 0.12);
        color: #f07d00;
        border-color: rgba(240, 125, 0, 0.25);
    }
    .btn-quick-orange:hover {
        background: #f07d00;
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(240, 125, 0, 0.3);
    }
    .btn-quick-blue {
        background: rgba(14, 65, 148, 0.1);
        color: #0e4194;
        border-color: rgba(14, 65, 148, 0.2);
    }
    body.dark-theme .btn-quick-blue {
        background: rgba(37, 99, 235, 0.2);
        color: #38bdf8;
        border-color: rgba(56, 189, 248, 0.3);
    }
    .btn-quick-blue:hover {
        background: #0e4194;
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(14, 65, 148, 0.3);
    }

    /* Modern KPI Cards Grid */
    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }
    .kpi-card {
        background: var(--bg-card, #ffffff);
        border: 1px solid rgba(0, 0, 0, 0.06);
        border-radius: 18px;
        padding: 20px;
        position: relative;
        overflow: hidden;
        transition: transform 0.25s ease, box-shadow 0.25s ease;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    body.dark-theme .kpi-card {
        background: rgba(15, 23, 42, 0.65);
        border-color: rgba(255, 255, 255, 0.07);
    }
    .kpi-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.06);
    }
    .kpi-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;
    }
    .kpi-title {
        font-size: 0.86rem;
        font-weight: 600;
        color: var(--text-muted, #64748b);
        text-transform: uppercase;
        letter-spacing: 0.4px;
        margin: 0;
    }
    .kpi-icon-box {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        flex-shrink: 0;
    }
    .kpi-value-row {
        display: flex;
        align-items: baseline;
        gap: 10px;
        margin-bottom: 8px;
    }
    .kpi-number {
        font-size: 2.1rem;
        font-weight: 800;
        line-height: 1;
        color: var(--text-color);
        margin: 0;
        font-family: 'Outfit', sans-serif;
    }
    .kpi-badge {
        font-size: 0.75rem;
        font-weight: 600;
        padding: 3px 8px;
        border-radius: 20px;
    }
    .kpi-footer {
        font-size: 0.8rem;
        color: var(--text-muted, #64748b);
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-top: 10px;
        border-top: 1px solid rgba(0, 0, 0, 0.04);
        margin-top: 4px;
    }
    body.dark-theme .kpi-footer {
        border-top-color: rgba(255, 255, 255, 0.05);
    }
    .kpi-link {
        color: inherit;
        text-decoration: none;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        transition: color 0.2s;
    }
    .kpi-link:hover {
        color: #f07d00;
    }

    /* Color variations for KPI cards */
    .kpi-emerald .kpi-icon-box { background: rgba(16, 185, 129, 0.12); color: #10b981; }
    .kpi-emerald .kpi-number { color: #10b981; }
    .kpi-emerald .kpi-badge { background: rgba(16, 185, 129, 0.12); color: #10b981; }

    .kpi-blue .kpi-icon-box { background: rgba(14, 65, 148, 0.12); color: #0e4194; }
    body.dark-theme .kpi-blue .kpi-icon-box { background: rgba(37, 99, 235, 0.2); color: #38bdf8; }
    .kpi-blue .kpi-number { color: #0e4194; }
    body.dark-theme .kpi-blue .kpi-number { color: #38bdf8; }
    .kpi-blue .kpi-badge { background: rgba(14, 65, 148, 0.1); color: #0e4194; }
    body.dark-theme .kpi-blue .kpi-badge { background: rgba(37, 99, 235, 0.2); color: #38bdf8; }

    .kpi-orange .kpi-icon-box { background: rgba(240, 125, 0, 0.12); color: #f07d00; }
    .kpi-orange .kpi-number { color: #f07d00; }
    .kpi-orange .kpi-badge { background: rgba(240, 125, 0, 0.12); color: #f07d00; }

    .kpi-purple .kpi-icon-box { background: rgba(139, 92, 246, 0.12); color: #8b5cf6; }
    .kpi-purple .kpi-number { color: #8b5cf6; }
    .kpi-purple .kpi-badge { background: rgba(139, 92, 246, 0.12); color: #8b5cf6; }

    .kpi-cyan .kpi-icon-box { background: rgba(6, 182, 212, 0.12); color: #06b6d4; }
    .kpi-cyan .kpi-number { color: #06b6d4; }
    .kpi-cyan .kpi-badge { background: rgba(6, 182, 212, 0.12); color: #06b6d4; }

    .kpi-amber .kpi-icon-box { background: rgba(245, 158, 11, 0.12); color: #f59e0b; }
    .kpi-amber .kpi-number { color: #f59e0b; }
    .kpi-amber .kpi-badge { background: rgba(245, 158, 11, 0.12); color: #f59e0b; }

    /* Section Cards (Charts & Tables) */
    .dash-section-card {
        background: var(--bg-card, #ffffff);
        border: 1px solid rgba(0, 0, 0, 0.06);
        border-radius: 18px;
        padding: 22px;
        margin-bottom: 24px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02);
    }
    body.dark-theme .dash-section-card {
        background: rgba(15, 23, 42, 0.65);
        border-color: rgba(255, 255, 255, 0.07);
    }
    .dash-section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 18px;
        flex-wrap: wrap;
        gap: 10px;
    }
    .dash-section-title {
        font-size: 1.05rem;
        font-weight: 700;
        margin: 0;
        color: var(--text-color);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* Custom Interactive Tabs */
    .dash-tabs {
        display: flex;
        gap: 6px;
        background: rgba(0, 0, 0, 0.04);
        padding: 4px;
        border-radius: 12px;
    }
    body.dark-theme .dash-tabs {
        background: rgba(255, 255, 255, 0.05);
    }
    .dash-tab-btn {
        background: transparent;
        border: none;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 0.82rem;
        font-weight: 600;
        color: var(--text-muted, #64748b);
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .dash-tab-btn.active {
        background: var(--bg-card, #ffffff);
        color: #f07d00;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }
    body.dark-theme .dash-tab-btn.active {
        background: #1e293b;
        color: #ff9800;
    }

    /* Modern Tables */
    .dash-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 0.85rem;
    }
    .dash-table th {
        background: rgba(0, 0, 0, 0.02);
        color: var(--text-muted, #64748b);
        font-weight: 600;
        padding: 10px 14px;
        text-align: left;
        border-bottom: 1px solid rgba(0, 0, 0, 0.06);
        text-transform: uppercase;
        font-size: 0.72rem;
        letter-spacing: 0.5px;
    }
    body.dark-theme .dash-table th {
        background: rgba(255, 255, 255, 0.02);
        border-bottom-color: rgba(255, 255, 255, 0.06);
    }
    .dash-table td {
        padding: 12px 14px;
        border-bottom: 1px solid rgba(0, 0, 0, 0.04);
        color: var(--text-color);
        vertical-align: middle;
    }
    body.dark-theme .dash-table td {
        border-bottom-color: rgba(255, 255, 255, 0.04);
    }
    .dash-table tr:hover td {
        background: rgba(240, 125, 0, 0.03);
    }

    /* Ticket Badge Priority */
    .badge-priority-alta { background: rgba(239, 68, 68, 0.15); color: #ef4444; }
    .badge-priority-media { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }
    .badge-priority-baja { background: rgba(16, 185, 129, 0.15); color: #10b981; }

    /* Shortcut Grid */
    .shortcuts-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(135px, 1fr));
        gap: 12px;
    }
    .shortcut-card-item {
        background: var(--bg-card, #ffffff);
        border: 1px solid rgba(0, 0, 0, 0.06);
        border-radius: 16px;
        padding: 16px 12px;
        text-align: center;
        text-decoration: none;
        transition: all 0.25s ease;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    body.dark-theme .shortcut-card-item {
        background: rgba(15, 23, 42, 0.65);
        border-color: rgba(255, 255, 255, 0.07);
    }
    .shortcut-card-item:hover {
        transform: translateY(-4px);
        border-color: #f07d00;
        box-shadow: 0 8px 20px rgba(240, 125, 0, 0.15);
    }
    .shortcut-icon-circle {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        background: rgba(14, 65, 148, 0.08);
        color: var(--primary-color, #0e4194);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        margin-bottom: 8px;
        transition: transform 0.2s ease;
    }
    .shortcut-card-item:hover .shortcut-icon-circle {
        transform: scale(1.1);
        background: rgba(240, 125, 0, 0.15);
        color: #f07d00;
    }
    .shortcut-name {
        font-size: 0.82rem;
        font-weight: 600;
        color: var(--text-color);
        margin: 0;
    }

    /* Vehicle Mini Badges */
    .vehicle-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 12px;
        border-radius: 12px;
        background: rgba(0, 0, 0, 0.02);
        margin-bottom: 8px;
        border: 1px solid rgba(0, 0, 0, 0.04);
    }
    body.dark-theme .vehicle-item {
        background: rgba(255, 255, 255, 0.03);
        border-color: rgba(255, 255, 255, 0.05);
    }
</style>

<div class="dashboard-container">

    <!-- ==================== 1. WELCOME HERO CARD ==================== -->
    <div class="welcome-hero-card">
        <div class="welcome-left">
            <div class="welcome-avatar">
                <i class="ph-bold ph-squares-four"></i>
            </div>
            <div class="welcome-text">
                <h2>¡Hola, <?php echo htmlspecialchars($user_name); ?>! 👋</h2>
                <p>Centro de mando y operaciones en tiempo real &middot; <strong><?php echo date('d/m/Y'); ?></strong></p>
            </div>
        </div>
        <div class="welcome-actions">
            <?php if (hasAccess($pdo, 'actas')): ?>
                <a href="modules/actas/create.php" class="btn-quick-action btn-quick-orange">
                    <i class="ph-bold ph-plus-circle"></i> Nueva Acta
                </a>
            <?php endif; ?>
            <?php if (hasAccess($pdo, 'clientes')): ?>
                <a href="modules/clientes/" class="btn-quick-action btn-quick-blue">
                    <i class="ph-bold ph-user-plus"></i> Clientes
                </a>
            <?php endif; ?>
            <?php if (hasAccess($pdo, 'soporte')): ?>
                <a href="modules/soporte/" class="btn-quick-action btn-quick-orange">
                    <i class="ph-bold ph-headset"></i> Soporte
                </a>
            <?php endif; ?>
            <?php if (hasAccess($pdo, 'inventario')): ?>
                <a href="modules/inventario/" class="btn-quick-action btn-quick-blue">
                    <i class="ph-bold ph-package"></i> Inventario
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- ==================== 2. CORE OPERATIONAL KPI CARDS ==================== -->
    <div class="kpi-grid">
        <!-- 1. Actas de Instalación -->
        <div class="kpi-card kpi-emerald">
            <div class="kpi-top">
                <span class="kpi-title">Actas de Instalación</span>
                <div class="kpi-icon-box">
                    <i class="ph-bold ph-clipboard-text"></i>
                </div>
            </div>
            <div class="kpi-value-row">
                <span class="kpi-number"><?php echo $totalActas; ?></span>
                <span class="kpi-badge"><?php echo $actasMes; ?> este mes</span>
            </div>
            <div class="kpi-footer">
                <span><?php echo $actasHoy; ?> registradas hoy</span>
                <a href="modules/actas/" class="kpi-link">Ver actas &rarr;</a>
            </div>
        </div>

        <!-- 2. Clientes & Servicios -->
        <div class="kpi-card kpi-blue">
            <div class="kpi-top">
                <span class="kpi-title">Clientes Registrados</span>
                <div class="kpi-icon-box">
                    <i class="ph-bold ph-users-three"></i>
                </div>
            </div>
            <div class="kpi-value-row">
                <span class="kpi-number"><?php echo $totalClientes; ?></span>
                <span class="kpi-badge"><?php echo $clientesMes; ?> nuevos</span>
            </div>
            <div class="kpi-footer">
                <span>Base activa FTTH</span>
                <a href="modules/clientes/" class="kpi-link">Gestionar &rarr;</a>
            </div>
        </div>

        <!-- 3. Soporte & Tickets -->
        <div class="kpi-card kpi-orange">
            <div class="kpi-top">
                <span class="kpi-title">Tickets de Soporte</span>
                <div class="kpi-icon-box">
                    <i class="ph-bold ph-headset"></i>
                </div>
            </div>
            <div class="kpi-value-row">
                <span class="kpi-number"><?php echo $ticketsAbiertos; ?></span>
                <span class="kpi-badge"><?php echo $ticketsTerminados; ?> resueltos</span>
            </div>
            <div class="kpi-footer">
                <span><?php echo $totalTickets; ?> tickets en total</span>
                <a href="modules/soporte/" class="kpi-link">Atender &rarr;</a>
            </div>
        </div>

        <!-- 4. Inventario & Materiales -->
        <div class="kpi-card kpi-purple">
            <div class="kpi-top">
                <span class="kpi-title">Stock en Almacén</span>
                <div class="kpi-icon-box">
                    <i class="ph-bold ph-package"></i>
                </div>
            </div>
            <div class="kpi-value-row">
                <span class="kpi-number"><?php echo $disponiblesSkus; ?></span>
                <span class="kpi-badge"><?php echo $totalProducts; ?> productos</span>
            </div>
            <div class="kpi-footer">
                <span><?php echo $totalSkus; ?> unidades serializadas</span>
                <a href="modules/inventario/" class="kpi-link">Inventario &rarr;</a>
            </div>
        </div>

        <!-- 5. Red de Fibra Óptica & Puertos -->
        <div class="kpi-card kpi-cyan">
            <div class="kpi-top">
                <span class="kpi-title">Red de Fibra FTTH</span>
                <div class="kpi-icon-box">
                    <i class="ph-bold ph-map-trifold"></i>
                </div>
            </div>
            <div class="kpi-value-row">
                <span class="kpi-number"><?php echo $totalElementos; ?></span>
                <span class="kpi-badge"><?php echo $pctOcupacion; ?>% saturación</span>
            </div>
            <div class="kpi-footer">
                <span><?php echo $puertosOcupados; ?> / <?php echo $totalPuertos; ?> puertos ocupados</span>
                <a href="modules/mapas/" class="kpi-link">Ver mapa &rarr;</a>
            </div>
        </div>

        <!-- 6. Personal & Flota -->
        <div class="kpi-card kpi-amber">
            <div class="kpi-top">
                <span class="kpi-title">Técnicos & Equipo</span>
                <div class="kpi-icon-box">
                    <i class="ph-bold ph-user-gear"></i>
                </div>
            </div>
            <div class="kpi-value-row">
                <span class="kpi-number"><?php echo $totalTecnicos; ?></span>
                <span class="kpi-badge"><?php echo $totalUsers; ?> usuarios</span>
            </div>
            <div class="kpi-footer">
                <span><?php echo $totalVehiculos; ?> vehículos en flota</span>
                <a href="modules/settings/" class="kpi-link">Equipo &rarr;</a>
            </div>
        </div>
    </div>

    <!-- ==================== 3. CHARTS & ANALYTICS SECTION ==================== -->
    <div class="row g-3 mb-4">
        <!-- Gráfico 1: Actividad de Operaciones (6 Meses) -->
        <div class="col-lg-8 col-12">
            <div class="dash-section-card h-100 mb-0">
                <div class="dash-section-header">
                    <h3 class="dash-section-title">
                        <i class="ph-bold ph-chart-line-up" style="color: #f07d00;"></i> Actividad Operativa (Últimos 6 Meses)
                    </h3>
                    <span class="text-muted small">Actas &middot; Tickets &middot; Clientes</span>
                </div>
                <div style="position: relative; height: 260px; width: 100%;">
                    <canvas id="chartOperationsActivity"></canvas>
                </div>
            </div>
        </div>

        <!-- Gráfico 2: Capacidad de Red FTTH & Stock -->
        <div class="col-lg-4 col-12">
            <div class="dash-section-card h-100 mb-0">
                <div class="dash-section-header">
                    <h3 class="dash-section-title">
                        <i class="ph-bold ph-chart-pie-slice" style="color: #0e4194;"></i> Estado de Recursos
                    </h3>
                </div>
                <div style="position: relative; height: 180px; width: 100%; margin-bottom: 12px;">
                    <canvas id="chartResourceCapacity"></canvas>
                </div>
                <div class="d-flex justify-content-between text-muted small pt-2 border-top">
                    <span><i class="ph-fill ph-circle text-success me-1"></i> Stock Disponible: <strong><?php echo $disponiblesSkus; ?></strong></span>
                    <span><i class="ph-fill ph-circle text-warning me-1"></i> Mochilas: <strong><?php echo $asignadosSkus; ?></strong></span>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== 4. LIVE TABBED OPERATIONS & TICKETS ==================== -->
    <div class="row g-3 mb-4">
        <!-- Columna Izquierda: Pestañas Interactivas (Actas / Clientes / Movimientos) -->
        <div class="col-lg-8 col-12">
            <div class="dash-section-card h-100 mb-0">
                <div class="dash-section-header">
                    <div class="dash-tabs" id="dashLiveTabs">
                        <button type="button" class="dash-tab-btn active" onclick="switchDashTab('actas', this)">
                            <i class="ph-bold ph-clipboard-text"></i> Últimas Actas
                        </button>
                        <button type="button" class="dash-tab-btn" onclick="switchDashTab('clientes', this)">
                            <i class="ph-bold ph-users"></i> Clientes
                        </button>
                        <button type="button" class="dash-tab-btn" onclick="switchDashTab('movimientos', this)">
                            <i class="ph-bold ph-arrows-left-right"></i> Movimientos Stock
                        </button>
                    </div>
                    <a href="modules/actas/" id="tabViewAllLink" class="kpi-link small">Ver todas &rarr;</a>
                </div>

                <!-- Tab 1: Últimas Actas -->
                <div id="dashTabContent-actas" class="dash-tab-pane">
                    <div class="table-responsive">
                        <table class="dash-table">
                            <thead>
                                <tr>
                                    <th>Folio</th>
                                    <th>Cliente</th>
                                    <th>Distrito</th>
                                    <th>Técnico</th>
                                    <th>Fecha</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($ultimasActas)): ?>
                                    <?php foreach ($ultimasActas as $acta): ?>
                                    <tr>
                                        <td>
                                            <strong style="color: #f07d00;"><?php echo htmlspecialchars(($acta['prefijo'] ?? '') . '-' . str_pad($acta['folio'] ?? $acta['id'], 4, '0', STR_PAD_LEFT)); ?></strong>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($acta['cliente_nombre'] ?? 'Sin nombre'); ?></strong>
                                        </td>
                                        <td><span class="text-muted"><?php echo htmlspecialchars($acta['cliente_distrito'] ?? 'N/A'); ?></span></td>
                                        <td><?php echo htmlspecialchars($acta['tecnico_nombre'] ?? 'No asignado'); ?></td>
                                        <td><small class="text-muted"><?php echo !empty($acta['fecha_creacion']) ? date('d/m/Y', strtotime($acta['fecha_creacion'])) : '-'; ?></small></td>
                                        <td>
                                            <a href="modules/actas/ver.php?id=<?php echo $acta['id']; ?>" class="btn btn-sm btn-outline-primary" style="padding: 3px 8px; border-radius: 8px; font-size: 0.78rem;">
                                                <i class="ph-bold ph-eye"></i> Ver
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="text-center text-muted py-4">No hay actas registradas recientemente.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab 2: Clientes Recientes -->
                <div id="dashTabContent-clientes" class="dash-tab-pane" style="display: none;">
                    <div class="table-responsive">
                        <table class="dash-table">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th>DNI / RUC</th>
                                    <th>Celular</th>
                                    <th>Plan</th>
                                    <th>Dirección</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($ultimosClientes)): ?>
                                    <?php foreach ($ultimosClientes as $cli): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($cli['nombre_completo']); ?></strong></td>
                                        <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($cli['dni']); ?></span></td>
                                        <td><?php echo htmlspecialchars($cli['celular'] ?: 'N/A'); ?></td>
                                        <td><span class="text-primary font-weight-bold"><?php echo htmlspecialchars($cli['servicio_nombre'] ?? 'Plan Fibra'); ?></span></td>
                                        <td><span class="text-muted text-truncate" style="max-width: 140px; display: inline-block;"><?php echo htmlspecialchars($cli['direccion'] ?: 'N/A'); ?></span></td>
                                        <td><small class="text-muted"><?php echo date('d/m/Y', strtotime($cli['created_at'])); ?></small></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="text-center text-muted py-4">No hay clientes registrados.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab 3: Movimientos de Inventario -->
                <div id="dashTabContent-movimientos" class="dash-tab-pane" style="display: none;">
                    <div class="table-responsive">
                        <table class="dash-table">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>SKU</th>
                                    <th>Asignado a</th>
                                    <th>Cantidad</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($ultimosMovimientos)): ?>
                                    <?php foreach ($ultimosMovimientos as $mov): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($mov['product_name'] ?? 'Producto'); ?></strong></td>
                                        <td><code style="color: #f07d00;"><?php echo htmlspecialchars($mov['sku_code'] ?? '-'); ?></code></td>
                                        <td><?php echo htmlspecialchars($mov['assigned_to_name'] ?? 'Técnico'); ?></td>
                                        <td><span class="badge bg-info-subtle text-info"><?php echo (float)$mov['quantity']; ?> u.</span></td>
                                        <td><small class="text-muted"><?php echo date('d/m H:i', strtotime($mov['created_at'])); ?></small></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-center text-muted py-4">No hay movimientos registrados.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Columna Derecha: Tickets Activos & Mesa de Ayuda -->
        <div class="col-lg-4 col-12">
            <div class="dash-section-card h-100 mb-0 d-flex flex-column justify-content-between">
                <div>
                    <div class="dash-section-header">
                        <h3 class="dash-section-title">
                            <i class="ph-bold ph-ticket" style="color: #f07d00;"></i> Tickets Activos
                        </h3>
                        <a href="modules/soporte/" class="kpi-link small">Ver todos &rarr;</a>
                    </div>

                    <div class="tickets-list">
                        <?php if (!empty($ultimosTickets)): ?>
                            <?php foreach ($ultimosTickets as $t): 
                                $badgeClass = 'badge-priority-media';
                                if (($t['prioridad_id'] ?? 1) == 3 || strtolower($t['estado']) === 'urgente') $badgeClass = 'badge-priority-alta';
                                if (($t['prioridad_id'] ?? 1) == 1) $badgeClass = 'badge-priority-baja';
                            ?>
                            <div class="ticket-item p-3 mb-2 rounded-3 border" style="background: rgba(0,0,0,0.02); transition: all 0.2s;">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <span class="badge <?php echo $badgeClass; ?> rounded-pill px-2 py-1" style="font-size: 0.72rem;">
                                        #<?php echo $t['id']; ?> &middot; <?php echo ucfirst($t['estado']); ?>
                                    </span>
                                    <small class="text-muted"><i class="ph ph-clock me-1"></i><?php echo date('d M, H:i', strtotime($t['created_at'])); ?></small>
                                </div>
                                <h5 class="mb-1 text-truncate" style="font-size: 0.9rem; font-weight: 600; color: var(--text-color);"><?php echo htmlspecialchars($t['asunto']); ?></h5>
                                <div class="d-flex align-items-center justify-content-between mt-2">
                                    <span class="text-muted small text-truncate" style="max-width: 170px;"><i class="ph ph-user me-1"></i><?php echo htmlspecialchars($t['cliente_nombre']); ?></span>
                                    <a href="ticket.php?id=<?php echo $t['id']; ?>&token=<?php echo $t['public_token']; ?>" class="btn btn-sm btn-primary rounded-pill px-3" style="font-size: 0.76rem;">
                                        <i class="ph-bold ph-chat-circle-dots me-1"></i> Chat
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-4 text-muted">
                                <i class="ph-bold ph-check-circle text-success" style="font-size: 2.5rem;"></i>
                                <p class="mt-2 mb-0 font-weight-bold">¡Sin tickets pendientes!</p>
                                <small>Todo el soporte está al día.</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Mini Flota & Estado del Sistema -->
                <div class="mt-3 pt-3 border-top">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="small font-weight-bold text-muted"><i class="ph ph-car me-1 text-warning"></i> Flota Operativa (<?php echo $totalVehiculos; ?>)</span>
                        <a href="modules/inventario/Activos/" class="small kpi-link">Ver activos &rarr;</a>
                    </div>
                    <?php if (!empty($vehiculos)): ?>
                        <?php foreach (array_slice($vehiculos, 0, 2) as $veh): ?>
                        <div class="vehicle-item">
                            <span class="small font-weight-bold"><?php echo htmlspecialchars($veh['marca'] . ' ' . $veh['modelo']); ?></span>
                            <span class="badge bg-dark-subtle text-dark border font-monospace"><?php echo htmlspecialchars($veh['placa']); ?></span>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== 5. ACCESOS DIRECTOS A MÓDULOS ==================== -->
    <div class="dash-section-card">
        <div class="dash-section-header">
            <h3 class="dash-section-title">
                <i class="ph-bold ph-grid-four" style="color: var(--primary-color);"></i> Accesos Directos a Módulos
            </h3>
            <span class="text-muted small">Navegación centralizada del sistema</span>
        </div>

        <div class="shortcuts-grid">
            <?php 
            global $system_modules;
            foreach ($system_modules as $key => $module):
                if ($key === 'dashboard') continue;
                if (hasAccess($pdo, $key)):
            ?>
            <a href="<?php echo BASE_URL . $module['url']; ?>" class="shortcut-card-item" title="<?php echo htmlspecialchars($module['description'] ?? ''); ?>">
                <div class="shortcut-icon-circle">
                    <i class="ph <?php echo htmlspecialchars($module['icon']); ?>"></i>
                </div>
                <h6 class="shortcut-name"><?php echo htmlspecialchars($module['name']); ?></h6>
            </a>
            <?php 
                endif;
            endforeach; 
            ?>
        </div>
    </div>

</div>

<!-- ==================== CHART.JS SCRIPTS & INTERACTIONS ==================== -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // --- Tabs Switching ---
    function switchDashTab(tabName, btn) {
        document.querySelectorAll('.dash-tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.dash-tab-pane').forEach(p => p.style.display = 'none');

        btn.classList.add('active');
        const targetPane = document.getElementById('dashTabContent-' + tabName);
        if (targetPane) targetPane.style.display = 'block';

        const viewAllLink = document.getElementById('tabViewAllLink');
        if (tabName === 'actas') {
            viewAllLink.href = 'modules/actas/';
            viewAllLink.innerText = 'Ver todas las actas →';
        } else if (tabName === 'clientes') {
            viewAllLink.href = 'modules/clientes/';
            viewAllLink.innerText = 'Ver todos los clientes →';
        } else if (tabName === 'movimientos') {
            viewAllLink.href = 'modules/inventario/historial/';
            viewAllLink.innerText = 'Ver todo el historial →';
        }
    }

    // --- Chart 1: Actividad Operativa ---
    const ctxActivity = document.getElementById('chartOperationsActivity').getContext('2d');
    const isDark = document.body.classList.contains('dark-theme');
    const textColor = isDark ? '#94a3b8' : '#64748b';
    const gridColor = isDark ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.05)';

    new Chart(ctxActivity, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($monthLabels); ?>,
            datasets: [
                {
                    label: 'Actas Registradas',
                    data: <?php echo json_encode($actasHistory); ?>,
                    backgroundColor: 'rgba(16, 185, 129, 0.75)',
                    borderColor: '#10b981',
                    borderWidth: 1.5,
                    borderRadius: 6
                },
                {
                    label: 'Tickets Atendidos',
                    data: <?php echo json_encode($ticketsHistory); ?>,
                    backgroundColor: 'rgba(240, 125, 0, 0.75)',
                    borderColor: '#f07d00',
                    borderWidth: 1.5,
                    borderRadius: 6
                },
                {
                    label: 'Nuevos Clientes',
                    data: <?php echo json_encode($clientesHistory); ?>,
                    backgroundColor: 'rgba(14, 65, 148, 0.75)',
                    borderColor: '#0e4194',
                    borderWidth: 1.5,
                    borderRadius: 6
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: { color: textColor, font: { family: 'Outfit', size: 12 } }
                }
            },
            scales: {
                x: {
                    grid: { color: gridColor },
                    ticks: { color: textColor }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: gridColor },
                    ticks: { color: textColor, stepSize: 1 }
                }
            }
        }
    });

    // --- Chart 2: Capacidad de Recursos & Stock ---
    const ctxResources = document.getElementById('chartResourceCapacity').getContext('2d');
    new Chart(ctxResources, {
        type: 'doughnut',
        data: {
            labels: ['Stock Disponible', 'En Mochilas / Asignado', 'Instalado'],
            datasets: [{
                data: [
                    <?php echo max(1, $disponiblesSkus); ?>,
                    <?php echo $asignadosSkus; ?>,
                    <?php echo $instaladosSkus; ?>
                ],
                backgroundColor: [
                    '#10b981',
                    '#f59e0b',
                    '#3b82f6'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: textColor, font: { family: 'Outfit', size: 11 } }
                }
            },
            cutout: '68%'
        }
    });
</script>

<?php include 'includes/footer.php'; ?>
