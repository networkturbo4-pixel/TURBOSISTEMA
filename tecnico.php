<?php
require_once 'config/db.php';

// Verificar sesión de usuario
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// Obtener datos del usuario
$stmtUser = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmtUser->execute([$user_id]);
$user = $stmtUser->fetch();

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}

$user_name = $user['name'] ?? 'Técnico';
$user_role = $user['role'] ?? 'tecnico';
$profile_picture = !empty($user['profile_picture']) ? $user['profile_picture'] : null;

// Saludo dinámico según la hora
date_default_timezone_set('America/Lima');
$hour = (int)date('H');
if ($hour >= 5 && $hour < 12) {
    $saludo = "¡Buenos días";
    $emojiSaludo = "🌅";
} elseif ($hour >= 12 && $hour < 19) {
    $saludo = "¡Buenas tardes";
    $emojiSaludo = "☀️";
} else {
    $saludo = "¡Buenas noches";
    $emojiSaludo = "🌙";
}

// Estadísticas para el Técnico
$stmtAssignedCount = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE (assigned_to = ? OR active_tech_id = ?) AND estado != 'terminado'");
$stmtAssignedCount->execute([$user_id, $user_id]);
$assignedCount = $stmtAssignedCount->fetchColumn();

$stmtCompletedMonth = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE (assigned_to = ? OR active_tech_id = ?) AND estado = 'terminado' AND MONTH(updated_at) = MONTH(CURRENT_DATE()) AND YEAR(updated_at) = YEAR(CURRENT_DATE())");
$stmtCompletedMonth->execute([$user_id, $user_id]);
$completedMonth = $stmtCompletedMonth->fetchColumn();

$stmtActasTechCount = $pdo->prepare("SELECT COUNT(*) FROM actas WHERE tecnico_id = ? AND MONTH(fecha_creacion) = MONTH(CURRENT_DATE()) AND YEAR(fecha_creacion) = YEAR(CURRENT_DATE())");
$stmtActasTechCount->execute([$user_id]);
$actasMonth = $stmtActasTechCount->fetchColumn();

// Total equipos/stock en la mochila del técnico
$stmtStockCount = $pdo->prepare("SELECT COUNT(*) FROM inventory_skus WHERE assigned_to = ? AND status = 'en_transito'");
$stmtStockCount->execute([$user_id]);
$stockCount = $stmtStockCount->fetchColumn();

// Listado de tickets asignados al técnico
$stmtTickets = $pdo->prepare("
    SELECT t.*, 
           COALESCE(NULLIF(t.cliente_nombre_manual, ''), c.nombre_completo, 'Cliente General') as cliente_nombre_final,
           c.direccion as cliente_direccion,
           c.celular as cliente_celular,
           tc.name as cat_name, tc.color as cat_color,
           tp.name as pri_name, tp.color as pri_color,
           (SELECT COUNT(*) FROM ticket_messages tm WHERE tm.ticket_id = t.id AND tm.is_read = 0 AND tm.user_id IS NULL) as unread_count
    FROM tickets t
    LEFT JOIN clientes c ON t.cliente_id = c.id
    LEFT JOIN ticket_categories tc ON t.categoria_id = tc.id
    LEFT JOIN ticket_priorities tp ON t.prioridad_id = tp.id
    WHERE t.assigned_to = ? OR t.active_tech_id = ?
    ORDER BY CASE WHEN t.estado = 'en_proceso' THEN 1 WHEN t.estado = 'nuevo' THEN 2 WHEN t.estado = 'pendiente' THEN 3 ELSE 4 END, t.updated_at DESC
");
$stmtTickets->execute([$user_id, $user_id]);
$tickets = $stmtTickets->fetchAll();

// Obtener inventario asignado en mochila
$stmtUserEquip = $pdo->prepare("
    SELECT s.sku_code, p.name as product_name, s.status, s.historia
    FROM inventory_skus s
    JOIN inventory_products p ON s.product_id = p.id
    WHERE s.assigned_to = ? AND s.status = 'en_transito'
    LIMIT 10
");
$stmtUserEquip->execute([$user_id]);
$equiposMochila = $stmtUserEquip->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
    <title>Portal de Técnico - Turbo SaaS</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        :root {
            --app-bg: #0b0f19;
            --app-card-bg: rgba(30, 41, 59, 0.7);
            --app-card-border: rgba(255, 255, 255, 0.08);
            --app-text-main: #f8fafc;
            --app-text-sub: #94a3b8;
            --app-accent: #10b981;
            --app-accent-gradient: linear-gradient(135deg, #10b981, #059669);
        }

        html, body, body.dark-theme {
            display: block !important;
            height: auto !important;
            min-height: 100vh !important;
            overflow-x: hidden !important;
            overflow-y: auto !important;
            background-color: #0b0f19 !important;
            color: #f8fafc !important;
            margin: 0 !important;
            padding: 0 !important;
            padding-bottom: 20px !important;
            font-family: 'Inter', system-ui, -apple-system, sans-serif !important;
            -webkit-tap-highlight-color: transparent;
        }

        /* Top Bar Header */
        .app-header {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(11, 15, 25, 0.95);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--app-card-border);
            padding: 14px 20px;
            width: 100%;
            box-sizing: border-box;
        }

        .header-top {
            max-width: 680px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
        }

        .user-profile-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .avatar-container {
            position: relative;
            width: 48px;
            height: 48px;
            flex-shrink: 0;
        }

        .avatar-img {
            width: 100%;
            height: 100%;
            border-radius: 14px;
            object-fit: cover;
            border: 2px solid var(--app-accent);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);
        }

        .avatar-placeholder {
            width: 100%;
            height: 100%;
            border-radius: 14px;
            background: var(--app-accent-gradient);
            color: white;
            font-weight: 800;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .status-dot {
            position: absolute;
            bottom: -2px;
            right: -2px;
            width: 14px;
            height: 14px;
            background: #10b981;
            border: 2px solid var(--app-bg);
            border-radius: 50%;
            box-shadow: 0 0 8px #10b981;
        }

        .greeting-text {
            font-size: 1.15rem;
            font-weight: 800;
            letter-spacing: -0.3px;
            color: #ffffff;
            margin: 0;
            line-height: 1.2;
        }

        .greeting-subtext {
            font-size: 0.78rem;
            color: var(--app-text-sub);
            margin-top: 2px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .role-badge {
            background: rgba(16, 185, 129, 0.15);
            color: #10b981;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .icon-btn-app {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--app-card-border);
            color: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }

        .icon-btn-app:active {
            transform: scale(0.92);
            background: rgba(255, 255, 255, 0.1);
        }

        /* Contenedor Principal Centrado */
        .tech-portal-wrapper {
            display: block !important;
            max-width: 680px !important;
            width: 100% !important;
            margin: 0 auto !important;
            padding: 20px 16px 100px 16px !important;
            box-sizing: border-box !important;
        }

        .bottom-nav-inner {
            max-width: 680px;
            margin: 0 auto;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-around;
        }

        /* Stats Cards Carousel / Grid */
        .stats-grid {
            display: grid !important;
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 12px !important;
            margin-bottom: 24px !important;
            width: 100% !important;
        }

        .stat-card-app {
            background: rgba(30, 41, 59, 0.8) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            border-radius: 16px !important;
            padding: 16px !important;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3) !important;
            backdrop-filter: blur(10px) !important;
        }

        .stat-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            margin-bottom: 10px;
        }

        .stat-val {
            font-size: 1.6rem;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 4px;
            color: #ffffff;
        }

        .stat-label {
            font-size: 0.75rem;
            color: var(--app-text-sub);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Quick Action Grid */
        .section-title {
            font-size: 0.88rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--app-text-sub);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .quick-actions-grid {
            display: grid !important;
            grid-template-columns: repeat(4, 1fr) !important;
            gap: 10px !important;
            margin-bottom: 24px !important;
            width: 100% !important;
        }

        .quick-action-item {
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 8px !important;
            text-decoration: none !important;
            background: transparent !important;
            border: none !important;
            cursor: pointer !important;
            width: 100% !important;
            padding: 0 !important;
        }

        .quick-action-icon {
            width: 56px !important;
            height: 56px !important;
            border-radius: 16px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 1.5rem !important;
            color: #ffffff !important;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.4) !important;
            transition: transform 0.2s !important;
        }

        .quick-action-item:active .quick-action-icon {
            transform: scale(0.92) !important;
        }

        .quick-action-label {
            font-size: 0.74rem !important;
            font-weight: 600 !important;
            color: #cbd5e1 !important;
            text-align: center !important;
            line-height: 1.2 !important;
        }

        /* Feed Filter Pills */
        .feed-filter-pills {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding-bottom: 10px;
            margin-bottom: 14px;
            scrollbar-width: none;
        }

        .feed-filter-pills::-webkit-scrollbar { display: none; }

        .pill-btn {
            padding: 8px 16px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--app-card-border);
            color: var(--app-text-sub);
            font-size: 0.8rem;
            font-weight: 600;
            white-space: nowrap;
            cursor: pointer;
            transition: all 0.2s;
        }

        .pill-btn.active {
            background: var(--app-accent-gradient);
            color: white;
            border-color: transparent;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        /* --- Tarjeta de Trabajo Ultra-Moderna --- */
        .job-card-modern {
            background: linear-gradient(145deg, rgba(30, 41, 59, 0.95) 0%, rgba(15, 23, 42, 0.98) 100%) !important;
            border: 1px solid rgba(255, 255, 255, 0.12) !important;
            border-radius: 20px !important;
            padding: 18px !important;
            margin-bottom: 16px !important;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35) !important;
            backdrop-filter: blur(14px) !important;
            position: relative !important;
            transition: transform 0.2s, border-color 0.2s !important;
        }

        .job-card-modern:hover {
            border-color: rgba(16, 185, 129, 0.4) !important;
            transform: translateY(-2px) !important;
        }

        .ticket-id-badge {
            background: rgba(16, 185, 129, 0.15);
            color: #10b981;
            font-weight: 800;
            font-size: 0.78rem;
            padding: 3px 8px;
            border-radius: 8px;
            border: 1px solid rgba(16, 185, 129, 0.3);
            font-family: monospace;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-dot-inline {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
        }

        .status-nuevo { background: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3); }
        .status-en_proceso { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }
        .status-terminado { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
        .status-pendiente { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }

        .job-card-title {
            font-size: 1.05rem !important;
            font-weight: 800 !important;
            color: #ffffff !important;
            margin: 8px 0 4px 0 !important;
            line-height: 1.3 !important;
        }

        .job-client-name {
            font-size: 0.88rem !important;
            font-weight: 700 !important;
            color: #3b82f6 !important;
            display: flex !important;
            align-items: center !important;
            gap: 6px !important;
        }

        .job-address-card {
            background: rgba(15, 23, 42, 0.8);
            border-radius: 12px;
            padding: 10px 12px;
            margin: 12px 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            border: 1px solid rgba(255, 255, 255, 0.06);
        }

        .address-icon-box {
            width: 32px;
            height: 32px;
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .address-text {
            font-size: 0.8rem;
            color: #cbd5e1;
            font-weight: 500;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .btn-map-nav {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.78rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3);
            flex-shrink: 0;
        }

        .job-meta-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 0;
            border-top: 1px dashed rgba(255, 255, 255, 0.08);
            font-size: 0.78rem;
            color: #94a3b8;
        }

        .meta-tag span {
            font-weight: 700;
            color: #e2e8f0;
        }

        .job-card-actions {
            display: flex;
            gap: 10px;
            margin-top: 12px;
        }

        .app-main {
            flex: 1;
            padding: 20px 20px 30px 20px;
            overflow-y: auto;
            position: relative;
            z-index: 1;
        }

        .btn-app-action {
            flex: 1;
            padding: 11px;
            border-radius: 12px;
            font-size: 0.86rem;
            font-weight: 700;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-chat-live {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: white;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35);
        }

        .btn-acta {
            background: linear-gradient(135deg, #059669, #10b981);
            color: white;
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35);
        }

        .badge-unread {
            background: #ef4444;
            color: white;
            border-radius: 10px;
            padding: 2px 7px;
            font-size: 0.7rem;
            font-weight: 800;
        }

        /* Bottom App Navigation Bar */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 65px;
            background: rgba(11, 15, 25, 0.95);
            backdrop-filter: blur(20px);
            border-top: 1px solid var(--app-card-border);
            z-index: 1000;
            padding-bottom: env(safe-area-inset-bottom);
        }

        .nav-tab {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 3px;
            color: var(--app-text-sub);
            text-decoration: none;
            font-size: 0.7rem;
            font-weight: 600;
            transition: color 0.2s;
        }

        .nav-tab i {
            font-size: 1.4rem;
        }

        .nav-tab.active {
            color: #10b981;
        }
        /* Upload Banner Styles */
        .chat-upload-banner {
            background: linear-gradient(145deg, #1e293b, #0f172a);
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 16px;
            padding: 16px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(255,255,255,0.05) inset;
            animation: slideUpFade 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        .chat-upload-content {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 12px;
        }

        .chat-upload-spinner {
            width: 32px;
            height: 32px;
            border: 3px solid rgba(59, 130, 246, 0.2);
            border-top-color: #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            flex-shrink: 0;
            box-shadow: 0 0 10px rgba(59, 130, 246, 0.2);
        }

        .chat-upload-text {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .upload-title {
            color: #f8fafc;
            font-weight: 700;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .upload-title i {
            color: #3b82f6;
            font-size: 1.1rem;
            animation: bounce 2s infinite;
        }

        .upload-filename {
            color: #94a3b8;
            font-size: 0.8rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-weight: 500;
        }

        .chat-upload-progress {
            height: 8px;
            background: rgba(15, 23, 42, 0.8);
            border-radius: 6px;
            overflow: hidden;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.5);
            border: 1px solid rgba(255,255,255,0.05);
        }

        .progress-bar-inner {
            height: 100%;
            background: linear-gradient(90deg, #3b82f6, #60a5fa, #3b82f6);
            background-size: 200% 100%;
            border-radius: 6px;
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            animation: shimmerProgress 2s linear infinite;
            box-shadow: 0 0 10px rgba(59, 130, 246, 0.5);
        }

        @keyframes shimmerProgress {
            0% { background-position: 100% 0; }
            100% { background-position: -100% 0; }
        }

        @keyframes slideUpFade {
            from { opacity: 0; transform: translateY(15px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-3px); }
        }
    </style>
</head>
<body class="tecnico-portal-page">

    <!-- Header Estilo App con Saludo Dinámico -->
    <header class="app-header">
        <div class="header-top">
            <div class="user-profile-info">
                <div class="avatar-container">
                    <?php if ($profile_picture): ?>
                        <img src="<?php echo BASE_URL . '/' . htmlspecialchars($profile_picture); ?>" class="avatar-img" alt="Perfil">
                    <?php else: ?>
                        <div class="avatar-placeholder">
                            <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    <div class="status-dot"></div>
                </div>
                <div>
                    <h1 class="greeting-text"><?php echo $saludo; ?>, <?php echo htmlspecialchars(explode(' ', $user_name)[0]); ?>! <?php echo $emojiSaludo; ?></h1>
                    <div class="greeting-subtext">
                        <span class="role-badge"><?php echo htmlspecialchars($user_role); ?></span>
                        <span>• En servicio 🟢</span>
                    </div>
                </div>
            </div>
            <div class="header-actions">
                <button class="icon-btn-app" onclick="location.reload();" title="Actualizar">
                    <i class="ph-bold ph-arrows-clockwise"></i>
                </button>
                <a href="<?php echo BASE_URL; ?>/login.php?action=logout" class="icon-btn-app" style="color: #ef4444;" title="Cerrar Sesión">
                    <i class="ph-bold ph-sign-out"></i>
                </a>
            </div>
        </div>
    </header>

    <div class="tech-portal-wrapper">

        <!-- Grid de Tarjetas de Métricas -->
        <div class="stats-grid">
            <div class="stat-card-app">
                <div class="stat-icon" style="background: rgba(59,130,246,0.15); color: #3b82f6;">
                    <i class="ph-fill ph-ticket"></i>
                </div>
                <div class="stat-val"><?php echo $assignedCount; ?></div>
                <div class="stat-label">Trabajos Activos</div>
            </div>
            <div class="stat-card-app">
                <div class="stat-icon" style="background: rgba(16,185,129,0.15); color: #10b981;">
                    <i class="ph-fill ph-check-circle"></i>
                </div>
                <div class="stat-val"><?php echo $completedMonth; ?></div>
                <div class="stat-label">Resueltos este Mes</div>
            </div>
            <div class="stat-card-app">
                <div class="stat-icon" style="background: rgba(245,158,11,0.15); color: #f59e0b;">
                    <i class="ph-fill ph-file-text"></i>
                </div>
                <div class="stat-val"><?php echo $actasMonth; ?></div>
                <div class="stat-label">Actas de Servicio</div>
            </div>
            <div class="stat-card-app">
                <div class="stat-icon" style="background: rgba(139,92,246,0.15); color: #8b5cf6;">
                    <i class="ph-fill ph-backpack"></i>
                </div>
                <div class="stat-val"><?php echo $stockCount; ?></div>
                <div class="stat-label">Equipos en Mochila</div>
            </div>
        </div>

        <!-- Accesos Rápidos Tipo App Launchers -->
        <div class="section-title">
            <span>Accesos Rápidos</span>
            <i class="ph-bold ph-squares-four"></i>
        </div>

        <div class="quick-actions-grid">
            <?php if (hasAccess($pdo, 'actas')): ?>
            <button type="button" onclick="openTechAppModule('<?php echo BASE_URL; ?>/modules/actas/tecnico_create.php', 'Nueva Acta de Servicio')" class="quick-action-item">
                <div class="quick-action-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                    <i class="ph-fill ph-file-plus"></i>
                </div>
                <span class="quick-action-label">Nueva Acta</span>
            </button>
            <?php endif; ?>

            <?php if (hasAccess($pdo, 'mochila')): ?>
            <button type="button" onclick="openTechAppModule('<?php echo BASE_URL; ?>/modules/mochila/tecnico.php', 'Mi Mochila de Materiales')" class="quick-action-item">
                <div class="quick-action-icon" style="background: linear-gradient(135deg, #8b5cf6, #6d28d9);">
                    <i class="ph-fill ph-backpack"></i>
                </div>
                <span class="quick-action-label">Mi Mochila</span>
            </button>
            <?php endif; ?>

            <button type="button" onclick="triggerSmartCameraInput()" class="quick-action-item">
                <div class="quick-action-icon" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8);">
                    <i class="ph-fill ph-camera"></i>
                </div>
                <span class="quick-action-label">Cámara</span>
            </button>

            <?php if (hasAccess($pdo, 'mapas')): ?>
            <button type="button" onclick="openTechAppModule('<?php echo BASE_URL; ?>/modules/mapas/index.php', 'Mapa de Cobertura y Nodos')" class="quick-action-item">
                <div class="quick-action-icon" style="background: linear-gradient(135deg, #ef4444, #b91c1c);">
                    <i class="ph-fill ph-map-trifold"></i>
                </div>
                <span class="quick-action-label">Mapa Nodos</span>
            </button>
            <?php endif; ?>

            <?php if (hasAccess($pdo, 'actas')): ?>
            <button type="button" onclick="openTechAppModule('<?php echo BASE_URL; ?>/modules/actas/tecnico_index.php', 'Mis Actas de Servicio')" class="quick-action-item">
                <div class="quick-action-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                    <i class="ph-fill ph-files"></i>
                </div>
                <span class="quick-action-label">Actas</span>
            </button>
            <?php endif; ?>

            <?php if (hasAccess($pdo, 'soporte')): ?>
            <button type="button" onclick="document.querySelector('.section-title:nth-of-type(2)').scrollIntoView({behavior: 'smooth'})" class="quick-action-item">
                <div class="quick-action-icon" style="background: linear-gradient(135deg, #06b6d4, #0891b2);">
                    <i class="ph-fill ph-chat-circle-text"></i>
                </div>
                <span class="quick-action-label">Soporte</span>
            </button>
            <?php endif; ?>

            <!-- Perfil visible por defecto -->
            <button type="button" onclick="openTechAppModule('<?php echo BASE_URL; ?>/modules/perfil/index.php', 'Mi Perfil')" class="quick-action-item">
                <div class="quick-action-icon" style="background: linear-gradient(135deg, #64748b, #475569);">
                    <i class="ph-fill ph-user-circle"></i>
                </div>
                <span class="quick-action-label">Perfil</span>
            </button>

            <!-- Módulos Adicionales Dinámicos -->
            <?php 
            global $system_modules;
            $custom_handled = ['actas', 'mochila', 'mapas', 'soporte', 'perfil', 'dashboard'];
            foreach ($system_modules as $key => $mod): 
                if (in_array($key, $custom_handled)) continue;
                if (hasAccess($pdo, $key)):
                    $mod_url = BASE_URL . $mod['url'] . (strpos($mod['url'], '.php') === false && $mod['url'] !== '/' ? '/index.php' : '');
                    $colors = [
                        ['#3b82f6', '#1d4ed8'],
                        ['#10b981', '#059669'],
                        ['#f59e0b', '#d97706'],
                        ['#8b5cf6', '#6d28d9'],
                        ['#ec4899', '#be185d'],
                        ['#6366f1', '#4338ca']
                    ];
                    $c = $colors[crc32($key) % count($colors)];
            ?>
            <button type="button" onclick="openTechAppModule('<?php echo $mod_url; ?>', '<?php echo htmlspecialchars($mod['name'], ENT_QUOTES); ?>')" class="quick-action-item">
                <div class="quick-action-icon" style="background: linear-gradient(135deg, <?php echo $c[0]; ?>, <?php echo $c[1]; ?>);">
                    <i class="ph-fill <?php echo htmlspecialchars($mod['icon']); ?>"></i>
                </div>
                <span class="quick-action-label"><?php echo htmlspecialchars($mod['name']); ?></span>
            </button>
            <?php 
                endif;
            endforeach; 
            ?>
        </div>

        <!-- Feed de Trabajos y Tickets Asignados -->
        <div class="section-title">
            <span>Mis Trabajos Asignados</span>
            <span style="font-size: 0.75rem; color: #10b981; font-weight: 700;"><?php echo count($tickets); ?> Total</span>
        </div>

        <!-- Filtros Rápidos -->
        <div class="feed-filter-pills">
            <button class="pill-btn active" onclick="filterJobs('all', this)">⚡ Todos</button>
            <button class="pill-btn" onclick="filterJobs('en_proceso', this)">🟡 En Proceso</button>
            <button class="pill-btn" onclick="filterJobs('nuevo', this)">🔵 Nuevos</button>
            <button class="pill-btn" onclick="filterJobs('terminado', this)">🟢 Terminados</button>
        </div>

        <!-- Lista de Tarjetas de Trabajo Ultra-Modernas -->
        <div id="jobsContainer">
            <?php if (empty($tickets)): ?>
                <div style="text-align: center; padding: 40px 20px; background: rgba(30, 41, 59, 0.7); border-radius: 20px; border: 1px dashed rgba(255,255,255,0.1);">
                    <i class="ph-fill ph-check-circle" style="font-size: 3rem; color: #10b981; margin-bottom: 10px;"></i>
                    <div style="font-size: 1.1rem; font-weight: 700;">¡Todo al día!</div>
                    <div style="font-size: 0.82rem; color: #94a3b8; margin-top: 4px;">No tienes trabajos o tickets pendientes asignados en este momento.</div>
                </div>
            <?php else: ?>
                <?php foreach ($tickets as $t): ?>
                    <div class="job-card-modern job-item-card" data-status="<?php echo $t['estado']; ?>">
                        <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; margin-bottom: 8px;">
                            <div>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span class="ticket-id-badge">#<?php echo str_pad($t['id'], 4, '0', STR_PAD_LEFT); ?></span>
                                    <span class="status-pill status-<?php echo $t['estado']; ?>">
                                        <span class="status-dot-inline"></span>
                                        <?php echo str_replace('_', ' ', strtoupper($t['estado'])); ?>
                                    </span>
                                </div>
                                <h3 class="job-card-title"><?php echo htmlspecialchars($t['asunto']); ?></h3>
                                <div class="job-client-name">
                                    <i class="ph-fill ph-user-circle"></i> <?php echo htmlspecialchars($t['cliente_nombre_final']); ?>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($t['cliente_direccion'])): ?>
                            <div class="job-address-card">
                                <div style="display: flex; align-items: center; gap: 8px; overflow: hidden; flex: 1;">
                                    <div class="address-icon-box"><i class="ph-fill ph-map-pin"></i></div>
                                    <span class="address-text"><?php echo htmlspecialchars($t['cliente_direccion']); ?></span>
                                </div>
                                <button type="button" onclick="openLocationViewer('-12.046374, -77.042793')" class="btn-map-nav">
                                    <i class="ph-fill ph-navigation-arrow"></i> Mapa
                                </button>
                            </div>
                        <?php endif; ?>

                        <div class="job-meta-row">
                            <div class="meta-tag"><i class="ph-fill ph-tag"></i> Categoría: <span><?php echo htmlspecialchars($t['cat_name'] ?? 'General'); ?></span></div>
                            <div class="meta-tag"><i class="ph-fill ph-warning-circle"></i> Prioridad: <span style="color: <?php echo $t['pri_color'] ?? '#f59e0b'; ?>;"><?php echo htmlspecialchars($t['pri_name'] ?? 'Normal'); ?></span></div>
                        </div>

                        <div class="job-card-actions">
                            <button type="button" 
                                    data-ticket-id="<?php echo $t['id']; ?>" 
                                    data-asunto="<?php echo htmlspecialchars($t['asunto'], ENT_QUOTES); ?>" 
                                    data-cliente="<?php echo htmlspecialchars($t['cliente_nombre_final'], ENT_QUOTES); ?>" 
                                    data-status="<?php echo $t['estado']; ?>"
                                    onclick="triggerTechChatFromButton(this)" 
                                    class="btn-app-action btn-chat-live">
                                <i class="ph-fill ph-chat-circle-dots" style="font-size: 1.1rem;"></i> Chat Live
                                <?php if ($t['unread_count'] > 0): ?>
                                    <span class="badge-unread"><?php echo $t['unread_count']; ?></span>
                                <?php endif; ?>
                            </button>
                            <button type="button" onclick="openTechAppModule('<?php echo BASE_URL; ?>/modules/actas/tecnico_create.php?cliente=<?php echo urlencode($t['cliente_nombre_final']); ?>', 'Generar Acta de Servicio')" class="btn-app-action btn-acta">
                                <i class="ph-fill ph-file-text" style="font-size: 1.1rem;"></i> Generar Acta
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Equipos en la Mochila del Técnico -->
        <?php if (!empty($equiposMochila)): ?>
            <div class="section-title" style="margin-top: 25px;">
                <span>Materiales en Mi Mochila</span>
                <button type="button" onclick="openTechAppModule('<?php echo BASE_URL; ?>/modules/mochila/tecnico.php', 'Mi Mochila')" style="background:none; border:none; font-size: 0.75rem; color: #3b82f6; cursor:pointer; font-weight: 700;">Ver todos &rarr;</button>
            </div>

            <div style="display: flex; gap: 10px; overflow-x: auto; padding-bottom: 10px; scrollbar-width: none;">
                <?php foreach ($equiposMochila as $eq): ?>
                    <div style="background: rgba(30, 41, 59, 0.8); border: 1px solid rgba(255,255,255,0.08); border-radius: 14px; padding: 12px 14px; min-width: 170px; flex-shrink: 0; backdrop-filter: blur(8px);">
                        <div style="font-size: 0.75rem; color: #10b981; font-weight: 700; font-family: monospace;"><?php echo htmlspecialchars($eq['sku_code']); ?></div>
                        <div style="font-size: 0.85rem; font-weight: 700; color: #fff; margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($eq['product_name']); ?></div>
                        <div style="font-size: 0.7rem; color: #94a3b8; margin-top: 4px;"><i class="ph-fill ph-tag"></i> En Tránsito</div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>

    <!-- Modal de Chat En Vivo para Técnico (100% Dentro del Portal) -->
    <div class="modal-overlay" id="techChatModal" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; height: 100dvh; width: 100%; z-index: 99999; background: rgba(11, 15, 25, 0.96); backdrop-filter: blur(14px); display: none; padding: 0;">
        <div style="width: 100%; height: 100%; max-width: 680px; margin: 0 auto; display: flex; flex-direction: column; background: #0f172a; color: white;">
            
            <!-- Header Chat App -->
            <div style="padding: 14px 18px; background: #1e293b; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: space-between; flex-shrink: 0;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <button onclick="closeTechChat()" style="background: rgba(255,255,255,0.1); border: none; color: white; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer;">
                        <i class="ph-bold ph-arrow-left" style="font-size: 1.2rem;"></i>
                    </button>
                    <div>
                        <div id="techChatTicketTitle" style="font-weight: 800; font-size: 0.98rem; color: #fff;">#0012 - Ticket</div>
                        <div id="techChatClientName" style="font-size: 0.78rem; color: #10b981; font-weight: 600; display: flex; align-items: center; gap: 4px;">
                            <i class="ph-fill ph-user-circle"></i> Cliente
                        </div>
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 8px;">
                    <select id="techTicketStatusSelect" onchange="updateTechTicketStatusFromChat(this.value)" style="background: #0f172a; color: #34d399; border: 1px solid rgba(16,185,129,0.4); padding: 6px 10px; border-radius: 10px; font-size: 0.78rem; font-weight: 700; outline: none; cursor: pointer;">
                        <option value="en_proceso">🟡 En Proceso</option>
                        <option value="terminado">🟢 Terminado</option>
                        <option value="pendiente">🔴 Pendiente</option>
                    </select>
                </div>
            </div>

            <!-- Chat Stream Area -->
            <div id="techChatMessages" style="flex: 1; overflow-y: auto; padding: 16px; display: flex; flex-direction: column; gap: 10px; background-image: radial-gradient(rgba(255,255,255,0.03) 1px, transparent 0); background-size: 20px 20px;">
            </div>

            <!-- File Preview Container -->
            <div id="techFilePreviewContainer" style="display: none; padding: 8px 16px; background: #1e293b; border-top: 1px solid rgba(255,255,255,0.08); align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 8px; color: #3b82f6; font-size: 0.85rem; font-weight: 600;">
                    <i class="ph-fill ph-file"></i>
                    <span id="techFilePreviewName" style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"></span>
                </div>
                <button onclick="clearTechFileSelection()" style="background: rgba(255,255,255,0.1); border: none; color: white; border-radius: 50%; width: 22px; height: 22px; cursor: pointer;">✕</button>
            </div>

            <!-- Banner de Carga Animado -->
            <div id="techChatUploadingBanner" class="chat-upload-banner" style="display: none; position: relative; bottom: 0; left: 0; right: 0; margin: 10px;">
                <div class="chat-upload-content">
                    <div class="chat-upload-spinner"></div>
                    <div class="chat-upload-text">
                        <div class="upload-title"><i class="ph-bold ph-cloud-arrow-up"></i> Subiendo a Google Drive...</div>
                        <div class="upload-filename" id="techChatUploadFilename">archivo.png</div>
                    </div>
                    <span id="techChatUploadPercentText" style="font-size: 0.8rem; font-weight: 700; color: #3b82f6;">0%</span>
                </div>
                <div class="chat-upload-progress">
                    <div class="progress-bar-inner" id="techChatUploadProgressFill" style="width: 0%;"></div>
                </div>
            </div>

            <!-- Audio Recording Bar -->
            <div id="techAudioRecordingUi" style="display: none; padding: 12px 18px; background: rgba(239, 68, 68, 0.2); border-top: 1px solid rgba(239, 68, 68, 0.4); align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 10px; color: #ef4444; font-weight: 800; font-size: 0.9rem;">
                    <div style="width: 10px; height: 10px; background: #ef4444; border-radius: 50%; animation: pulse-red 1s infinite;"></div>
                    <span id="techRecordingTimer">00:00</span>
                </div>
                <button onclick="cancelTechRecording()" style="background: transparent; border: none; color: #ef4444; cursor: pointer; font-size: 1.2rem;"><i class="ph-fill ph-trash"></i></button>
            </div>

            <!-- Input Area Footer -->
            <div style="box-sizing: border-box; width: 100%; padding: 10px 10px calc(16px + env(safe-area-inset-bottom, 0px)); background: #0f172a; display: flex; align-items: flex-end; gap: 6px; position: relative; flex-wrap: nowrap; border-top: 1px solid rgba(255,255,255,0.05); overflow: visible; flex-shrink: 0;">
                
                <!-- Inputs ocultos -->
                <input type="file" id="techFileInput" accept="image/*,video/*,application/pdf" style="display:none;" onchange="handleFileSelect(this)">
                <input type="file" id="chatCameraInput" accept="image/*" capture="environment" style="display:none;" onchange="handleFileSelect(this)">
                
                <!-- Menú de Adjuntos (Estilo Grid Bottom) -->
                <div id="techChatActionMenu" style="display: none; position: absolute; bottom: 100%; left: 14px; right: 70px; margin-bottom: 12px; background: #1e293b; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); border: 1px solid rgba(255,255,255,0.1); padding: 20px; z-index: 100;">
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px 10px; text-align: center;">
                        
                        <div onclick="openGalleryInput(); toggleTechActionMenu();" style="cursor: pointer; display: flex; flex-direction: column; align-items: center; gap: 8px; transition: transform 0.1s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                            <div style="width: 52px; height: 52px; border-radius: 50%; background: linear-gradient(135deg, #3b82f6, #2563eb); display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(59,130,246,0.3);">
                                <i class="ph-fill ph-image" style="font-size: 1.6rem; color: #fff;"></i>
                            </div>
                            <span style="font-size: 0.78rem; font-weight: 600; color: #cbd5e1;">Galería</span>
                        </div>

                        <div onclick="triggerSmartCameraInput(); toggleTechActionMenu();" style="cursor: pointer; display: flex; flex-direction: column; align-items: center; gap: 8px; transition: transform 0.1s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                            <div style="width: 52px; height: 52px; border-radius: 50%; background: linear-gradient(135deg, #ec4899, #db2777); display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(236,72,153,0.3);">
                                <i class="ph-fill ph-camera" style="font-size: 1.6rem; color: #fff;"></i>
                            </div>
                            <span style="font-size: 0.78rem; font-weight: 600; color: #cbd5e1;">Cámara</span>
                        </div>

                        <div onclick="sendTechLocation(); toggleTechActionMenu();" style="cursor: pointer; display: flex; flex-direction: column; align-items: center; gap: 8px; transition: transform 0.1s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                            <div style="width: 52px; height: 52px; border-radius: 50%; background: linear-gradient(135deg, #10b981, #059669); display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(16,185,129,0.3);">
                                <i class="ph-fill ph-map-pin" style="font-size: 1.6rem; color: #fff;"></i>
                            </div>
                            <span style="font-size: 0.78rem; font-weight: 600; color: #cbd5e1;">Ubicación</span>
                        </div>
                        
                    </div>
                </div>

                <!-- Emoji Picker -->
                <div id="techEmojiPicker" style="display: none; position: absolute; bottom: 100%; left: 14px; margin-bottom: 12px; background: #1e293b; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); border: 1px solid rgba(255,255,255,0.1); padding: 14px; z-index: 100; width: 280px; max-height: 220px; overflow-y: auto;">
                    <div style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 10px; text-align: center;">
                        <span style="cursor: pointer; font-size: 1.5rem; transition: transform 0.1s;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'" onclick="insertEmoji('😀')">😀</span>
                        <span style="cursor: pointer; font-size: 1.5rem; transition: transform 0.1s;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'" onclick="insertEmoji('😂')">😂</span>
                        <span style="cursor: pointer; font-size: 1.5rem; transition: transform 0.1s;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'" onclick="insertEmoji('😍')">😍</span>
                        <span style="cursor: pointer; font-size: 1.5rem; transition: transform 0.1s;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'" onclick="insertEmoji('🙏')">🙏</span>
                        <span style="cursor: pointer; font-size: 1.5rem; transition: transform 0.1s;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'" onclick="insertEmoji('👍')">👍</span>
                        <span style="cursor: pointer; font-size: 1.5rem; transition: transform 0.1s;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'" onclick="insertEmoji('🔥')">🔥</span>
                        <span style="cursor: pointer; font-size: 1.5rem; transition: transform 0.1s;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'" onclick="insertEmoji('✅')">✅</span>
                        <span style="cursor: pointer; font-size: 1.5rem; transition: transform 0.1s;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'" onclick="insertEmoji('❌')">❌</span>
                        <span style="cursor: pointer; font-size: 1.5rem; transition: transform 0.1s;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'" onclick="insertEmoji('😅')">😅</span>
                        <span style="cursor: pointer; font-size: 1.5rem; transition: transform 0.1s;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'" onclick="insertEmoji('😎')">😎</span>
                        <span style="cursor: pointer; font-size: 1.5rem; transition: transform 0.1s;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'" onclick="insertEmoji('🎉')">🎉</span>
                        <span style="cursor: pointer; font-size: 1.5rem; transition: transform 0.1s;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'" onclick="insertEmoji('🤔')">🤔</span>
                        <span style="cursor: pointer; font-size: 1.5rem; transition: transform 0.1s;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'" onclick="insertEmoji('🙌')">🙌</span>
                        <span style="cursor: pointer; font-size: 1.5rem; transition: transform 0.1s;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'" onclick="insertEmoji('💡')">💡</span>
                        <span style="cursor: pointer; font-size: 1.5rem; transition: transform 0.1s;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'" onclick="insertEmoji('🔧')">🔧</span>
                        <span style="cursor: pointer; font-size: 1.5rem; transition: transform 0.1s;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'" onclick="insertEmoji('🛠️')">🛠️</span>
                        <span style="cursor: pointer; font-size: 1.5rem; transition: transform 0.1s;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'" onclick="insertEmoji('🚗')">🚗</span>
                        <span style="cursor: pointer; font-size: 1.5rem; transition: transform 0.1s;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'" onclick="insertEmoji('📱')">📱</span>
                    </div>
                </div>

                <!-- Input Bubble (WhatsApp style) -->
                <div style="flex: 1; display: flex; align-items: flex-end; background: #1e293b; border-radius: 24px; padding: 4px 6px; gap: 4px; border: 1px solid rgba(255,255,255,0.05); box-shadow: inset 0 2px 5px rgba(0,0,0,0.2);">
                    
                    <button type="button" onclick="toggleEmojiPicker()" style="background: transparent; border: none; font-size: 1.4rem; color: #94a3b8; cursor: pointer; padding: 6px; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: color 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#94a3b8'">
                        <i class="ph-bold ph-smiley"></i>
                    </button>
                    
                    <textarea id="techMessageInput" placeholder="Mensaje" rows="1" style="flex: 1; background: transparent; border: none; padding: 8px 4px; color: white; outline: none; font-size: 1rem; resize: none; max-height: 120px; line-height: 1.4; align-self: center;" oninput="this.style.height = ''; this.style.height = this.scrollHeight + 'px'; updateTechMainButton();"></textarea>
                    
                    <div style="display: flex; align-items: center; padding-bottom: 2px;">
                        <button type="button" onclick="toggleTechActionMenu()" style="background: transparent; border: none; font-size: 1.4rem; color: #94a3b8; cursor: pointer; padding: 6px; border-radius: 50%; display: flex; align-items: center; justify-content: center; transform: rotate(-45deg); transition: color 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#94a3b8'">
                            <i class="ph-bold ph-paperclip"></i>
                        </button>
                        <button type="button" onclick="triggerSmartCameraInput()" style="background: transparent; border: none; font-size: 1.4rem; color: #94a3b8; cursor: pointer; padding: 6px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 4px; transition: color 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#94a3b8'">
                            <i class="ph-fill ph-camera"></i>
                        </button>
                    </div>
                </div>

                <!-- Send / Mic Button outside -->
                <button type="button" id="btnTechSend" onclick="handleTechMainAction()" style="flex-shrink: 0; min-width: 44px; width: 44px; height: 44px; background: #10b981; border: none; border-radius: 50%; color: white; font-size: 1.3rem; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 4px 12px rgba(16,185,129,0.35); transition: background 0.2s;">
                    <i id="btnTechSendIcon" class="ph-fill ph-microphone"></i>
                </button>
            </div>

        </div>
    </div>

    <!-- Modales de Mapa InDrive/Uber y Cámara Webcam -->
    <?php require_once __DIR__ . '/includes/location_modal.php'; ?>
    <?php require_once __DIR__ . '/includes/webcam_modal.php'; ?>

    <script>
        let currentTechTicketId = null;
        let techChatPollInterval = null;
        let techLastMessageId = 0;
        let isTechPolling = false;
        let techSelectedFile = null;

        const escapeHtml = (str) => String(str || '').replace(/[&<>"']/g, s => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[s]));

        function filterJobs(status, btn) {
            document.querySelectorAll('.feed-filter-pills .pill-btn').forEach(p => p.classList.remove('active'));
            if (btn) btn.classList.add('active');

            const cards = document.querySelectorAll('.job-item-card');
            cards.forEach(card => {
                if (status === 'all' || card.dataset.status === status) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        function triggerTechChatFromButton(btn) {
            const ticketId = btn.getAttribute('data-ticket-id');
            const asunto = btn.getAttribute('data-asunto');
            const cliente = btn.getAttribute('data-cliente');
            const status = btn.getAttribute('data-status');
            openTechChat(ticketId, asunto, cliente, status);
        }

        function openTechChat(ticketId, ticketTitle, clientName, currentStatus = 'en_proceso') {
            currentTechTicketId = ticketId;
            techLastMessageId = 0;
            document.getElementById('techChatTicketTitle').textContent = `#${String(ticketId).padStart(4, '0')} - ${ticketTitle}`;
            document.getElementById('techChatClientName').innerHTML = `<i class="ph-fill ph-user-circle"></i> ${escapeHtml(clientName)}`;
            document.getElementById('techTicketStatusSelect').value = currentStatus;
            document.getElementById('techChatMessages').innerHTML = '<div style="text-align:center; padding:30px; color:#94a3b8;"><i class="ph ph-spinner spinner" style="font-size:1.5rem;"></i><br><span style="font-size:0.85rem; margin-top:6px; display:inline-block;">Cargando conversación...</span></div>';
            
            const modal = document.getElementById('techChatModal');
            if (modal) {
                modal.style.display = 'flex';
                modal.classList.add('active');
            }
            
            history.pushState({modal: 'techChatModal'}, '', '#chat');
            
            loadTechChatMessages();
            if (techChatPollInterval) clearInterval(techChatPollInterval);
            techChatPollInterval = setInterval(loadTechChatMessages, 1200);
        }

        function closeTechChat(fromHistory = false) {
            const modal = document.getElementById('techChatModal');
            if (modal) {
                modal.style.display = 'none';
                modal.classList.remove('active');
            }
            if (techChatPollInterval) {
                clearInterval(techChatPollInterval);
                techChatPollInterval = null;
            }
            currentTechTicketId = null;
            if (!fromHistory && window.location.hash === '#chat') {
                history.back();
            }
        }

        async function loadTechChatMessages() {
            if (!currentTechTicketId || isTechPolling) return;
            isTechPolling = true;

            try {
                const fd = new FormData();
                fd.append('action', 'get_messages');
                fd.append('ticket_id', currentTechTicketId);
                fd.append('last_id', techLastMessageId);

                fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
                const res = await fetch('<?php echo BASE_URL; ?>/ajax/soporte.php', { method: 'POST', body: fd }).then(r=>r.json());
                
                if (res.success) {
                    const container = document.getElementById('techChatMessages');
                    
                    if (techLastMessageId === 0 && (!res.data || res.data.length === 0)) {
                        container.innerHTML = `
                            <div style="text-align:center; padding:40px 20px; color:#94a3b8;">
                                <i class="ph-fill ph-chat-circle-dots" style="font-size:2.5rem; color:#3b82f6; margin-bottom:8px;"></i>
                                <div style="font-weight:700; color:#fff;">Inicio de Conversación</div>
                                <div style="font-size:0.8rem; margin-top:4px;">No hay mensajes previos en este ticket. ¡Escribe un mensaje para iniciar!</div>
                            </div>
                        `;
                    } else if (res.data && res.data.length > 0) {
                        const isFirstLoad = (techLastMessageId === 0);
                        if (isFirstLoad) container.innerHTML = '';

                        let htmlBuffer = '';
                        res.data.forEach(msg => {
                            if (msg.is_system_message == 1) {
                                htmlBuffer += `<div style="text-align:center; margin:8px 0; font-size:0.75rem; color:#94a3b8; background:rgba(255,255,255,0.05); padding:4px 12px; border-radius:12px; align-self:center;">${escapeHtml(msg.message)}</div>`;
                                techLastMessageId = msg.id;
                            } else {
                                const isMe = msg.user_id !== null;
                                const userName = isMe ? 'Tú (Técnico)' : (msg.user_name || 'Cliente');
                                
                                let msgContent = escapeHtml(msg.message).replace(/\n/g, '<br>');
                                
                                if (msgContent.startsWith('[LOCATION:') && msgContent.endsWith(']')) {
                                    const coords = msgContent.replace('[LOCATION:', '').replace(']', '');
                                    msgContent = `
                                        <div onclick="openLocationViewer('${coords}')" class="loc-card" style="cursor: pointer; background: rgba(15, 23, 42, 0.6); padding: 10px; border-radius: 12px; display: flex; align-items: center; gap: 10px; border: 1px solid rgba(255,255,255,0.1);">
                                            <div style="background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 10px; border-radius: 10px; display: flex; align-items: center; justify-content: center;"><i class="ph-fill ph-navigation-arrow" style="font-size: 1.3rem;"></i></div>
                                            <div>
                                                <div style="font-weight: 700; font-size: 0.88rem; color: #fff;">Ubicación compartida</div>
                                                <div style="font-size: 0.75rem; color: #10b981; font-weight: 600; display: flex; align-items: center; gap: 4px; margin-top: 2px;"><i class="ph-fill ph-map-pin"></i> Ver en Mapa App</div>
                                            </div>
                                        </div>
                                    `;
                                }

                                let attHtml = '';
                                if (msg.attachments && msg.attachments.length > 0) {
                                    msg.attachments.forEach(att => {
                                        let url = att.file_path;
                                        if (!url.startsWith('http://') && !url.startsWith('https://')) {
                                            url = `<?php echo BASE_URL; ?>/` + url;
                                        }
                                        const ext = att.file_name.split('.').pop().toLowerCase();
                                        const isVideo = ['mp4', 'mov', 'avi', 'mkv', 'webm'].includes(ext) || (ext === 'webm' && !att.file_name.includes('Nota de Voz'));
                                        const isAudio = ['mp3', 'ogg', 'wav', 'm4a'].includes(ext) || (ext === 'webm' && att.file_name.includes('Nota de Voz'));
                                        
                                        if (isVideo) {
                                            const isDriveUrl = url.includes('drive.google.com');
                                            let videoSrc = url;
                                            
                                            if (isDriveUrl) {
                                                const fileIdMatch = url.match(/\/d\/([a-zA-Z0-9_-]+)/);
                                                const ucIdMatch = url.match(/[?&]id=([a-zA-Z0-9_-]+)/);
                                                const driveFileId = fileIdMatch ? fileIdMatch[1] : (ucIdMatch ? ucIdMatch[1] : null);
                                                
                                                if (driveFileId) {
                                                    // Usamos enlace directo de descarga para evitar el mensaje de "Procesando" de Drive
                                                    // y permitir reproducción inmediata en el reproductor nativo
                                                    videoSrc = `https://drive.google.com/uc?export=download&id=${driveFileId}`;
                                                }
                                            }
                                            
                                            attHtml += `<video controls playsinline preload="metadata" style="max-width: 100%; border-radius: 10px; margin-top: 6px; border: 1px solid rgba(255,255,255,0.1); background: #000;">
                                                <source src="${videoSrc}" type="video/${ext === 'webm' ? 'webm' : ext === 'mov' ? 'quicktime' : 'mp4'}">Tu navegador no soporta video.
                                            </video>`;
                                        } else if (isAudio) {
                                            attHtml += `<audio controls src="${url}" style="max-width: 100%; margin-top: 5px; outline: none; height: 35px;"></audio>`;
                                        } else if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) {
                                            attHtml += `<img src="${url}" referrerpolicy="no-referrer" onclick="openLightbox('${url}')" style="cursor: pointer; max-width: 100%; border-radius: 10px; margin-top: 6px; border: 1px solid rgba(255,255,255,0.1);">`;
                                        } else {
                                            attHtml += `<div style="margin-top: 6px;"><a href="${url}" target="_blank" style="color: inherit; text-decoration: underline; font-weight: 600;"><i class="ph-fill ph-file"></i> ${escapeHtml(att.file_name)}</a></div>`;
                                        }
                                    });
                                }
                                
                                const alignSelf = isMe ? 'flex-end' : 'flex-start';
                                const bgMsg = isMe ? 'linear-gradient(135deg, #1e3a8a, #1d4ed8)' : 'rgba(30, 41, 59, 0.9)';

                                htmlBuffer += `
                                    <div style="align-self: ${alignSelf}; max-width: 82%; background: ${bgMsg}; padding: 10px 14px; border-radius: 16px; border: 1px solid rgba(255,255,255,0.08); font-size: 0.88rem;">
                                        <div style="font-size: 0.72rem; font-weight: 700; color: ${isMe ? '#93c5fd' : '#10b981'}; margin-bottom: 3px;">${userName}</div>
                                        <div>${msgContent}</div>
                                        ${attHtml}
                                    </div>
                                `;
                                techLastMessageId = msg.id;
                            }
                        });
                        
                        if (htmlBuffer !== '') {
                            container.insertAdjacentHTML('beforeend', htmlBuffer);
                            container.scrollTop = container.scrollHeight;
                        }
                    }
                }
            } catch(e) {
                console.error("Error al cargar mensajes del chat:", e);
            } finally {
                isTechPolling = false;
            }
        }

        async function updateTechTicketStatusFromChat(newStatus) {
            if (!currentTechTicketId) return;
            const fd = new FormData();
            fd.append('action', 'update_status');
            fd.append('ticket_id', currentTechTicketId);
            fd.append('status', newStatus);

            try {
            fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
            const res = await fetch('<?php echo BASE_URL; ?>/ajax/soporte.php', { method: 'POST', body: fd }).then(r=>r.json());
                if (res.success) {
                    loadTechChatMessages();
                }
            } catch(e) {}
        }

        const toggleTechActionMenu = () => {
            const menu = document.getElementById('techChatActionMenu');
            menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
        };

        const toggleEmojiPicker = () => {
            const picker = document.getElementById('techEmojiPicker');
            picker.style.display = picker.style.display === 'none' ? 'block' : 'none';
        };

        const insertEmoji = (emoji) => {
            const input = document.getElementById('techMessageInput');
            input.value += emoji;
            input.style.height = ''; 
            input.style.height = input.scrollHeight + 'px';
            updateTechMainButton();
            toggleEmojiPicker();
            input.focus();
        };

        const updateTechMainButton = () => {
            const text = document.getElementById('techMessageInput').value.trim();
            const btnIcon = document.getElementById('btnTechSendIcon');
            if (text || techSelectedFile) {
                btnIcon.className = 'ph-fill ph-paper-plane-right';
            } else {
                btnIcon.className = 'ph-fill ph-microphone';
            }
        };

        const clearTechFileSelection = () => {
            techSelectedFile = null;
            document.getElementById('techFilePreviewContainer').style.display = 'none';
            updateTechMainButton();
        };

        function handleFileSelect(input) {
            if (input.files && input.files[0]) {
                techSelectedFile = input.files[0];
                document.getElementById('techFilePreviewName').innerText = techSelectedFile.name;
                document.getElementById('techFilePreviewContainer').style.display = 'flex';
                updateTechMainButton();
            }
        }

        // Envío directo desde cámara (sin paso intermedio de preview)
        async function sendCapturedFileDirectly(file) {
            if (!file || !currentTechTicketId) return;
            
            let fileToSend = file;
            
            // Comprimir solo si es imagen
            if (file.type.startsWith('image/')) {
                fileToSend = await compressImage(file);
            }
            
            const fd = new FormData();
            fd.append('action', 'send_message');
            fd.append('ticket_id', currentTechTicketId);
            fd.append('message', '');
            fd.append('attachment', fileToSend);
            
            try {
                const res = await sendTechChatAjaxWithProgress(fd, fileToSend.name);
                if (res.success) {
                    loadTechChatMessages();
                } else {
                    alert(res.error || res.message || 'Error al enviar');
                }
            } catch(e) {
                alert('Error de conexión al enviar.');
            }
        }

        const openGalleryInput = () => {
            document.getElementById('techFileInput').click();
        };

        const sendTechLocation = () => {
            if (!navigator.geolocation) {
                alert('Tu dispositivo no soporta geolocalización');
                return;
            }
            navigator.geolocation.getCurrentPosition(
                async (position) => {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    const fd = new FormData();
                    fd.append('action', 'send_message');
                    fd.append('ticket_id', currentTechTicketId);
                    fd.append('message', `[LOCATION:${lat},${lng}]`);
                    try {
                        fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
                        const res = await fetch('<?php echo BASE_URL; ?>/ajax/soporte.php', { method: 'POST', body: fd }).then(r=>r.json());
                        if(res.success) loadTechChatMessages();
                    } catch(e) {}
                },
                (error) => {
                    alert('No se pudo obtener la ubicación.');
                }
            );
        };

        const handleTechMainAction = () => {
            const btnIcon = document.getElementById('btnTechSendIcon').className;
            if (btnIcon.includes('ph-microphone')) {
                if (isTechRecording) {
                    techMediaRecorder.stop();
                } else {
                    startTechRecording();
                }
            } else {
                sendTechTextMessage();
            }
        };

        const showTechUploadBanner = (filename) => {
            const banner = document.getElementById('techChatUploadingBanner');
            const filenameEl = document.getElementById('techChatUploadFilename');
            const fillEl = document.getElementById('techChatUploadProgressFill');
            const percentEl = document.getElementById('techChatUploadPercentText');
            if (banner) {
                filenameEl.textContent = filename || 'Archivo multimedia';
                fillEl.style.width = '10%';
                if (percentEl) percentEl.textContent = '10%';
                banner.style.display = 'block';
            }
        };

        const updateTechUploadProgress = (percent) => {
            const fillEl = document.getElementById('techChatUploadProgressFill');
            const percentEl = document.getElementById('techChatUploadPercentText');
            const p = Math.min(100, Math.max(10, Math.round(percent)));
            if (fillEl) fillEl.style.width = p + '%';
            if (percentEl) percentEl.textContent = p + '%';
        };

        const hideTechUploadBanner = () => {
            const banner = document.getElementById('techChatUploadingBanner');
            if (banner) banner.style.display = 'none';
        };

        const sendTechChatAjaxWithProgress = (formData, filename = null) => {
            return new Promise((resolve, reject) => {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                if (csrfToken && !formData.has('csrf_token')) {
                    formData.append('csrf_token', csrfToken);
                }

                if (filename) showTechUploadBanner(filename);
                
                const xhr = new XMLHttpRequest();
                xhr.open('POST', '<?php echo BASE_URL; ?>/ajax/soporte.php', true);
                if (csrfToken) {
                    xhr.setRequestHeader('X-CSRF-Token', csrfToken);
                }

                if (filename && xhr.upload) {
                    xhr.upload.onprogress = (e) => {
                        if (e.lengthComputable) {
                            const percent = (e.loaded / e.total) * 100;
                            updateTechUploadProgress(percent);
                        }
                    };
                }

                xhr.onload = () => {
                    hideTechUploadBanner();
                    if (xhr.status >= 200 && xhr.status < 300) {
                        try {
                            resolve(JSON.parse(xhr.responseText));
                        } catch(e) {
                            reject(e);
                        }
                    } else {
                        reject(new Error(xhr.statusText));
                    }
                };
                xhr.onerror = () => {
                    hideTechUploadBanner();
                    reject(new Error("Network Error"));
                };
                xhr.send(formData);
            });
        };

        const compressImage = (file, maxWidth = 1600, maxHeight = 1600, quality = 0.8) => {
            return new Promise((resolve) => {
                if (!file.type.startsWith('image/') || file.type === 'image/gif') {
                    resolve(file);
                    return;
                }
                const reader = new FileReader();
                reader.readAsDataURL(file);
                reader.onload = (event) => {
                    const img = new Image();
                    img.src = event.target.result;
                    img.onload = () => {
                        let width = img.width;
                        let height = img.height;
                        if (width > maxWidth || height > maxHeight) {
                            if (width > height) {
                                height = Math.round((height *= maxWidth / width));
                                width = maxWidth;
                            } else {
                                width = Math.round((width *= maxHeight / height));
                                height = maxHeight;
                            }
                        }
                        const canvas = document.createElement('canvas');
                        canvas.width = width;
                        canvas.height = height;
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(img, 0, 0, width, height);
                        canvas.toBlob((blob) => {
                            if (blob) {
                                const newFile = new File([blob], file.name.replace(/\.[^/.]+$/, "") + ".jpg", { type: 'image/jpeg', lastModified: Date.now() });
                                resolve(newFile);
                            } else {
                                resolve(file);
                            }
                        }, 'image/jpeg', quality);
                    };
                    img.onerror = () => resolve(file);
                };
                reader.onerror = () => resolve(file);
            });
        };

        const sendTechTextMessage = async () => {
            const input = document.getElementById('techMessageInput');
            const text = input.value.trim();
            let fileToSend = techSelectedFile;
            if (!text && !fileToSend || !currentTechTicketId) return;

            input.value = '';
            input.style.height = '';
            if (fileToSend) clearTechFileSelection();

            if (fileToSend && fileToSend.type.startsWith('image/')) {
                fileToSend = await compressImage(fileToSend);
            }

            const fd = new FormData();
            fd.append('action', 'send_message');
            fd.append('ticket_id', currentTechTicketId);
            fd.append('message', text);
            if (fileToSend) {
                fd.append('attachment', fileToSend);
            }

            try {
                const res = await sendTechChatAjaxWithProgress(fd, fileToSend ? fileToSend.name : null);
                if (res.success) {
                    loadTechChatMessages();
                    updateTechMainButton();
                } else {
                    alert(res.error || res.message || 'Error al enviar');
                }
            } catch(e) {
                alert('Error de conexión al enviar.');
            }
        };

        // Audio Recording Logic
        let isTechRecording = false;
        let techMediaRecorder = null;
        let techAudioChunks = [];
        let techRecordingTimerInterval = null;
        let techRecordingSeconds = 0;

        const startTechRecording = async () => {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                techMediaRecorder = new MediaRecorder(stream);
                techAudioChunks = [];
                
                techMediaRecorder.addEventListener("dataavailable", event => {
                    techAudioChunks.push(event.data);
                });
                
                techMediaRecorder.addEventListener("stop", () => {
                    if (isTechRecording) {
                        const audioBlob = new Blob(techAudioChunks, { type: 'audio/webm' });
                        sendTechAudioMessage(audioBlob);
                    }
                    isTechRecording = false;
                    stream.getTracks().forEach(track => track.stop());
                    
                    document.getElementById('techAudioRecordingUi').style.display = 'none';
                    document.getElementById('techMessageInput').style.display = 'block';
                    document.querySelector('button[onclick="toggleTechActionMenu()"]').style.display = 'block';
                    updateTechMainButton();
                    clearInterval(techRecordingTimerInterval);
                });

                techMediaRecorder.start();
                isTechRecording = true;
                
                document.getElementById('techMessageInput').style.display = 'none';
                document.querySelector('button[onclick="toggleTechActionMenu()"]').style.display = 'none';
                document.getElementById('techAudioRecordingUi').style.display = 'flex';
                document.getElementById('btnTechSendIcon').className = 'ph-fill ph-paper-plane-right';
                
                techRecordingSeconds = 0;
                document.getElementById('techRecordingTimer').textContent = '00:00';
                techRecordingTimerInterval = setInterval(() => {
                    techRecordingSeconds++;
                    const m = String(Math.floor(techRecordingSeconds / 60)).padStart(2, '0');
                    const s = String(techRecordingSeconds % 60).padStart(2, '0');
                    document.getElementById('techRecordingTimer').textContent = `${m}:${s}`;
                }, 1000);

            } catch (err) {
                alert('No se pudo acceder al micrófono. Por favor, revisa los permisos del navegador.');
            }
        };

        const cancelTechRecording = () => {
            if (isTechRecording && techMediaRecorder) {
                isTechRecording = false; // flag to not send
                techMediaRecorder.stop();
            }
        };

        const sendTechAudioMessage = async (audioBlob) => {
            const btnSend = document.getElementById('btnTechSend');
            if (btnSend) btnSend.disabled = true;

            const tempId = 'opt_audio_' + Date.now();
            const container = document.getElementById('techChatMessages');
            container.innerHTML += `
                <div style="align-self: flex-end; background: #1e293b; color: white; padding: 12px 16px; border-radius: 16px 16px 0 16px; max-width: 80%; border: 1px solid rgba(255,255,255,0.08); display: flex; flex-direction: column; gap: 8px;">
                    <div style="font-size:0.85rem; font-weight:600;"><i class="ph-fill ph-microphone"></i> Grabación de audio...</div>
                    <div style="font-size: 0.75rem; color: #3b82f6;"><i class="ph ph-spinner spinner"></i> Subiendo a Google Drive...</div>
                </div>`;
            container.scrollTop = container.scrollHeight;

            const fd = new FormData();
            fd.append('action', 'send_message');
            fd.append('ticket_id', currentTechTicketId);
            fd.append('message', '');
            fd.append('attachment', audioBlob, 'audio_record.webm');
            fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

            try {
                const res = await sendTechChatAjaxWithProgress(fd, 'Nota de Voz.webm');
                if (res.success) {
                    loadTechChatMessages();
                } else {
                    alert(res.error || 'Error al enviar audio');
                }
            } catch(e) {
                alert('Error de conexión');
            } finally {
                if (btnSend) btnSend.disabled = false;
                const optEl = document.getElementById(tempId);
                if (optEl) optEl.remove();
            }
        };
    </script>

    <!-- Modal de Vista App Embebida (Para Crear Actas, Mochila, Mapas, etc. 100% Dentro del Portal) -->
    <div class="modal-overlay" id="techAppViewModal" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; height: 100vh; width: 100vw; z-index: 100000; background: rgba(11, 15, 25, 0.96); backdrop-filter: blur(14px); display: none; padding: 0;">
        <div style="width: 100%; height: 100%; max-width: 680px; margin: 0 auto; display: flex; flex-direction: column; background: #0f172a; color: white;">
            
            <!-- Header Drawer App -->
            <div style="padding: 14px 18px; background: #1e293b; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: space-between; flex-shrink: 0;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <button type="button" onclick="closeTechAppModal()" style="background: rgba(255,255,255,0.1); border: none; color: white; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer;">
                        <i class="ph-bold ph-arrow-left" style="font-size: 1.2rem;"></i>
                    </button>
                    <div id="techAppModalTitle" style="font-weight: 800; font-size: 0.98rem; color: #fff;">Módulo</div>
                </div>
                <button type="button" onclick="closeTechAppModal()" style="background: #334155; border: none; color: white; padding: 6px 14px; border-radius: 10px; font-size: 0.78rem; font-weight: 700; cursor: pointer;">
                    Volver al Portal
                </button>
            </div>

            <!-- Frame Content -->
            <div style="flex: 1; position: relative; background: #0f172a;">
                <iframe id="techAppModalIframe" src="" style="width: 100%; height: 100%; border: none; background: #0f172a;" loading="lazy"></iframe>
            </div>
        </div>
    </div>

    <script>
        function openTechAppModule(url, title = 'Módulo') {
            document.getElementById('techAppModalTitle').innerText = title;
            document.getElementById('techAppModalIframe').src = url + (url.includes('?') ? '&' : '?') + 'embedded=1';
            const modal = document.getElementById('techAppViewModal');
            if (modal) {
                modal.style.display = 'flex';
                modal.classList.add('active');
            }
            history.pushState({modal: 'techAppViewModal'}, '', '#modulo');
        }

        function closeTechAppModal(fromHistory = false) {
            const modal = document.getElementById('techAppViewModal');
            const iframe = document.getElementById('techAppModalIframe');
            if (modal) {
                modal.style.display = 'none';
                modal.classList.remove('active');
                if (iframe) iframe.src = '';
            }
            if (!fromHistory && window.location.hash === '#modulo') {
                history.back();
            }
        }

        // Manejar el botón "Atrás" del navegador/celular
        window.addEventListener('popstate', function(event) {
            if (document.getElementById('techChatModal') && document.getElementById('techChatModal').style.display === 'flex') {
                closeTechChat(true);
            }
            if (document.getElementById('techAppViewModal') && document.getElementById('techAppViewModal').style.display === 'flex') {
                closeTechAppModal(true);
            }
            if (document.getElementById('techLightboxModal') && document.getElementById('techLightboxModal').style.display === 'flex') {
                closeLightbox();
            }
        });
    </script>

    <!-- Modal Lightbox para imágenes del Chat -->
    <div id="techLightboxModal" onclick="closeLightbox()" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0, 0, 0, 0.9); z-index: 999999; justify-content: center; align-items: center; cursor: zoom-out;">
        <span style="position: absolute; top: 20px; right: 30px; color: white; font-size: 40px; cursor: pointer;">&times;</span>
        <img id="techLightboxImage" src="" style="max-width: 95%; max-height: 95%; border-radius: 10px; box-shadow: 0 0 30px rgba(0,0,0,0.5);">
    </div>

    <script>
        function openLightbox(url) {
            document.getElementById('techLightboxImage').src = url;
            document.getElementById('techLightboxModal').style.display = 'flex';
        }
        function closeLightbox() {
            document.getElementById('techLightboxModal').style.display = 'none';
            document.getElementById('techLightboxImage').src = '';
        }
    </script>
</body>
</html>
