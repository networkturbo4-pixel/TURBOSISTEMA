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
            --card-bg: #1e293b;
            --text-color: #f1f5f9;
            --text-muted: #94a3b8;
            --primary: #3b82f6;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --border: rgba(255,255,255,0.08);
        }
        body {
            margin: 0; padding: 0;
            background: var(--bg-color);
            color: var(--text-color);
            font-family: 'Inter', sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        .header {
            padding: 18px 20px;
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            position: sticky; top: 0; z-index: 10;
        }
        .header h1 {
            margin: 0; font-size: 1.15rem; font-weight: 800; display: flex; align-items: center; gap: 8px;
        }
        .container {
            padding: 20px;
            max-width: 600px;
            margin: 0 auto;
        }
        .card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 16px;
            margin-bottom: 14px;
            display: flex;
            gap: 16px;
            align-items: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .card-img {
            width: 64px; height: 64px;
            border-radius: 12px;
            object-fit: cover;
            background: rgba(255,255,255,0.03);
            display: flex; align-items: center; justify-content: center; font-size: 1.8rem;
            color: var(--text-muted);
            border: 1px solid var(--border);
        }
        .card-body {
            flex: 1; min-width: 0;
        }
        .card-title {
            margin: 0 0 6px 0; font-weight: 700; font-size: 0.95rem;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
            color: #fff;
        }
        .card-sku {
            font-size: 0.8rem; color: var(--text-muted); font-weight: 600;
            display: flex; align-items: center; gap: 4px;
        }
        .card-status {
            font-size: 0.7rem; font-weight: 800; padding: 4px 10px; border-radius: 8px;
            display: inline-flex; align-items: center; margin-top: 10px; text-transform: uppercase; letter-spacing: 0.5px;
        }
        .status-disponible { background: rgba(16,185,129,0.15); color: #10b981; }
        .status-instalado { background: rgba(59,130,246,0.15); color: #3b82f6; }
        .status-malogrado { background: rgba(239,68,68,0.15); color: #ef4444; }
        .status-reparado { background: rgba(245,158,11,0.15); color: #f59e0b; }
        .status-en_transito { background: rgba(139,92,246,0.15); color: #8b5cf6; }
        
        .loading {
            text-align: center; padding: 60px 20px; color: var(--text-muted);
        }
    </style>
</head>
<body>

<div class="header">
    <h1><i class="ph-bold ph-backpack"></i> Mi Mochila</h1>
</div>

<div class="container" id="mochilaContainer">
    <div class="loading">
        <i class="ph ph-spinner ph-spin" style="font-size: 2.5rem; color: var(--primary);"></i>
        <p style="margin-top: 16px; font-size: 0.95rem; font-weight: 500;">Cargando inventario...</p>
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

async function loadMyItems() {
    try {
        const formData = new FormData();
        formData.append('action', 'get_user_items');
        formData.append('user_id', userId);
        formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
        
        const res = await fetch(`${BASE_URL}/ajax/mochila.php`, {
            method: 'POST',
            body: formData
        }).then(r => r.json());

        const container = document.getElementById('mochilaContainer');
        
        if (res.success) {
            const items = res.items || [];
            if (items.length === 0) {
                container.innerHTML = '<div class="loading"><i class="ph ph-package" style="font-size:3rem; margin-bottom:16px; color:var(--text-muted);"></i><br><span style="font-weight:600;">No tienes equipos asignados.</span></div>';
                return;
            }
            
            let html = '';
            items.forEach(item => {
                const img = item.product_image ? `<img src="${BASE_URL}/${escapeHtml(item.product_image)}" class="card-img">` : `<div class="card-img"><i class="ph ph-cube"></i></div>`;
                html += `
                    <div class="card">
                        ${img}
                        <div class="card-body">
                            <h3 class="card-title">${escapeHtml(item.product_name)}</h3>
                            <div class="card-sku"><i class="ph-fill ph-barcode"></i> ${escapeHtml(item.sku_code)}</div>
                            <div class="card-status status-${escapeHtml(item.status)}">${escapeHtml(item.status).replace('_', ' ')}</div>
                        </div>
                    </div>
                `;
            });
            container.innerHTML = html;
        } else {
            container.innerHTML = `<div class="loading" style="color:var(--danger); font-weight:600;">${escapeHtml(res.message)}</div>`;
        }
    } catch (e) {
        document.getElementById('mochilaContainer').innerHTML = '<div class="loading" style="color:var(--danger); font-weight:600;">Error de conexión.</div>';
    }
}

document.addEventListener('DOMContentLoaded', loadMyItems);
</script>

</body>
</html>
