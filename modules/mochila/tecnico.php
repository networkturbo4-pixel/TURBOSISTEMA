<?php
require_once '../../config/db.php';
requireLogin();

// Vista nativa para técnicos, sin dependencias de admin
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Mochila</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <meta name="base-url" content="<?php echo BASE_URL; ?>">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
    <style>
        :root {
            --bg-color: #0b0f19;
            --card-bg: rgba(30, 41, 59, 0.7);
            --text-color: #f8fafc;
            --text-muted: #94a3b8;
            --primary: #3b82f6;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --border: rgba(255,255,255,0.08);
            --accent-gradient: linear-gradient(135deg, #3b82f6, #2563eb);
        }
        body {
            margin: 0; padding: 0;
            background: var(--bg-color);
            color: var(--text-color);
            font-family: 'Inter', system-ui, sans-serif;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }
        .header {
            padding: 20px;
            background: rgba(11, 15, 25, 0.85);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border);
            position: sticky; top: 0; z-index: 10;
        }
        .header h1 {
            margin: 0; font-size: 1.25rem; font-weight: 800; display: flex; align-items: center; gap: 10px;
            background: linear-gradient(90deg, #f8fafc, #94a3b8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .header h1 i {
            -webkit-text-fill-color: #3b82f6;
        }
        .container {
            padding: 24px 20px;
            max-width: 680px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .card {
            background: linear-gradient(145deg, rgba(30, 41, 59, 0.95), rgba(15, 23, 42, 0.95));
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 16px;
            display: flex;
            gap: 16px;
            align-items: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.25);
            backdrop-filter: blur(12px);
            transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1), border-color 0.2s;
        }
        .card:hover {
            transform: translateY(-2px);
            border-color: rgba(59, 130, 246, 0.4);
        }
        .card-img-wrapper {
            position: relative;
        }
        .card-img {
            width: 68px; height: 68px;
            border-radius: 14px;
            object-fit: cover;
            background: rgba(255,255,255,0.05);
            display: flex; align-items: center; justify-content: center; font-size: 2rem;
            color: var(--text-muted);
            border: 1px solid rgba(255,255,255,0.1);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        .qty-badge {
            position: absolute;
            bottom: -6px;
            right: -6px;
            background: var(--accent-gradient);
            color: white;
            font-size: 0.7rem;
            font-weight: 800;
            padding: 3px 8px;
            border-radius: 10px;
            border: 2px solid var(--bg-color);
            box-shadow: 0 2px 8px rgba(0,0,0,0.5);
        }
        .card-body {
            flex: 1; min-width: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .card-title {
            margin: 0 0 6px 0; font-weight: 700; font-size: 1.05rem;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
            color: #f8fafc;
        }
        .card-sku {
            font-size: 0.8rem; color: #cbd5e1; font-weight: 600;
            display: flex; align-items: center; gap: 6px;
            margin-bottom: 8px;
        }
        .sku-icon {
            color: #3b82f6;
        }
        .card-status {
            font-size: 0.72rem; font-weight: 800; padding: 4px 10px; border-radius: 8px;
            display: inline-flex; align-items: center; text-transform: uppercase; letter-spacing: 0.5px;
            border: 1px solid transparent;
            width: fit-content;
        }
        .status-disponible { background: rgba(16,185,129,0.15); color: #34d399; border-color: rgba(16,185,129,0.3); }
        .status-instalado { background: rgba(59,130,246,0.15); color: #60a5fa; border-color: rgba(59,130,246,0.3); }
        .status-malogrado { background: rgba(239,68,68,0.15); color: #f87171; border-color: rgba(239,68,68,0.3); }
        .status-reparado { background: rgba(245,158,11,0.15); color: #fbbf24; border-color: rgba(245,158,11,0.3); }
        .status-en_transito { background: rgba(139,92,246,0.15); color: #a78bfa; border-color: rgba(139,92,246,0.3); }
        .status-granel { background: rgba(99,102,241,0.15); color: #818cf8; border-color: rgba(99,102,241,0.3); }
        
        .loading {
            text-align: center; padding: 80px 20px; color: var(--text-muted);
            display: flex; flex-direction: column; align-items: center; gap: 16px;
        }
        .spinner {
            font-size: 3rem;
            color: var(--primary);
            animation: spin 1s linear infinite;
        }
        @keyframes spin { 100% { transform: rotate(360deg); } }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: rgba(30, 41, 59, 0.4);
            border-radius: 20px;
            border: 1px dashed rgba(255,255,255,0.1);
        }
        .empty-state i {
            font-size: 3.5rem;
            color: rgba(255,255,255,0.15);
            margin-bottom: 16px;
        }
        .empty-state h3 {
            margin: 0 0 8px 0;
            color: #f8fafc;
            font-size: 1.1rem;
        }
        .empty-state p {
            margin: 0;
            color: #94a3b8;
            font-size: 0.9rem;
        }
        .section-label {
            font-size: 0.85rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #94a3b8;
            margin: 10px 0 4px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .section-label::after {
            content: "";
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, rgba(255,255,255,0.1), transparent);
        }
    </style>
</head>
<body>

<div class="header">
    <h1><i class="ph-bold ph-backpack"></i> Mi Mochila</h1>
</div>

<div class="container" id="mochilaContainer">
    <div class="loading">
        <i class="ph ph-spinner spinner"></i>
        <p style="margin: 0; font-size: 1rem; font-weight: 500; color: #e2e8f0;">Cargando inventario...</p>
    </div>
</div>

<script>
const BASE_URL = document.querySelector('meta[name="base-url"]').content;
const userId = <?php echo $_SESSION['user_id']; ?>;

function escapeHtml(unsafe) {
    return (unsafe || '').toString()
         .replace(/&/g, "&amp;")
         .replace(/</g, "&lt;")
         .replace(/>/g, "&gt;")
         .replace(/"/g, "&quot;")
         .replace(/'/g, "&#039;");
}

function renderItemCard(item, type) {
    let skuText, statusClass, statusText, qtyBadge = '';
    
    if (type === 'normal') {
        skuText = `<i class="ph-fill ph-barcode sku-icon"></i> ${escapeHtml(item.sku_code)}`;
        statusClass = `status-${escapeHtml(item.status)}`;
        statusText = escapeHtml(item.status).replace('_', ' ');
    } else {
        skuText = `<i class="ph-fill ph-circles-four sku-icon"></i> ${type === 'bulk' ? 'GRANEL' : 'AGRUPADO'} - ${escapeHtml(item.unit_type || 'Unidad(es)')}`;
        statusClass = 'status-granel';
        statusText = 'EN STOCK';
        qtyBadge = `<div class="qty-badge">x${item.quantity}</div>`;
    }

    const imgUrl = item.product_image ? `${BASE_URL}/${escapeHtml(item.product_image)}` : null;
    const imgHtml = imgUrl 
        ? `<img src="${imgUrl}" class="card-img" alt="${escapeHtml(item.product_name)}">` 
        : `<div class="card-img"><i class="ph-duotone ph-package"></i></div>`;

    return `
        <div class="card">
            <div class="card-img-wrapper">
                ${imgHtml}
                ${qtyBadge}
            </div>
            <div class="card-body">
                <h3 class="card-title">${escapeHtml(item.product_name)}</h3>
                <div class="card-sku">${skuText}</div>
                <div class="card-status ${statusClass}">${statusText}</div>
            </div>
        </div>
    `;
}

async function loadMyItems() {
    try {
        const formData = new FormData();
        formData.append('action', 'get_user_backpack');
        formData.append('user_id', userId);
        formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
        
        const res = await fetch(`${BASE_URL}/ajax/mochila.php`, {
            method: 'POST',
            body: formData
        }).then(r => r.json());

        const container = document.getElementById('mochilaContainer');
        
        if (res.success) {
            const normalItems = res.normal_items || [];
            const bulkItems = res.bulk_items || [];
            const groupedItems = res.grouped_items || [];
            
            if (normalItems.length === 0 && bulkItems.length === 0 && groupedItems.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="ph-duotone ph-backpack"></i>
                        <h3>Mochila Vacía</h3>
                        <p>No tienes equipos ni materiales asignados actualmente.</p>
                    </div>
                `;
                return;
            }
            
            let html = '';
            
            if (normalItems.length > 0) {
                html += `<div class="section-label">Equipos (Seriados)</div>`;
                normalItems.forEach(item => {
                    html += renderItemCard(item, 'normal');
                });
            }
            
            if (bulkItems.length > 0 || groupedItems.length > 0) {
                html += `<div class="section-label" style="margin-top: 10px;">Materiales (Granel/Agrupado)</div>`;
                bulkItems.forEach(item => {
                    html += renderItemCard(item, 'bulk');
                });
                groupedItems.forEach(item => {
                    html += renderItemCard(item, 'grouped');
                });
            }
            
            container.innerHTML = html;
        } else {
            container.innerHTML = `
                <div class="empty-state" style="border-color: rgba(239,68,68,0.3);">
                    <i class="ph-duotone ph-warning-circle" style="color: #ef4444;"></i>
                    <h3 style="color: #ef4444;">Error</h3>
                    <p>${escapeHtml(res.message)}</p>
                </div>
            `;
        }
    } catch (e) {
        document.getElementById('mochilaContainer').innerHTML = `
            <div class="empty-state" style="border-color: rgba(239,68,68,0.3);">
                <i class="ph-duotone ph-wifi-slash" style="color: #ef4444;"></i>
                <h3 style="color: #ef4444;">Sin Conexión</h3>
                <p>No se pudo conectar con el servidor.</p>
            </div>
        `;
    }
}

document.addEventListener('DOMContentLoaded', loadMyItems);
</script>

</body>
</html>

