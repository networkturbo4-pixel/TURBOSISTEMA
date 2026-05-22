<?php
require_once '../../config/db.php';
requireLogin();
requirePermission($pdo, 'actas');

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<style>
    /* Estilos para el Buscador y Filtros */
    .filter-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 24px;
        background: var(--surface-color);
        padding: 16px;
        border-radius: 12px;
        border: 1px solid var(--border-color);
        box-shadow: var(--shadow);
        align-items: center;
    }
    .filter-search {
        flex: 1;
        min-width: 250px;
        position: relative;
    }
    .filter-search i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted);
    }
    .filter-search input {
        padding-left: 36px;
    }
    .filter-item {
        min-width: 150px;
    }
    .btn-clear {
        background-color: var(--bg-color);
        color: var(--text-color);
        border: 1px solid var(--border-color);
    }
    .btn-clear:hover {
        background-color: #e2e8f0;
    }
    body.dark-theme .btn-clear:hover {
        background-color: #333;
    }

    /* Floating Action Button (FAB) */
    .fab {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background-color: #ff7f00; /* Naranja/Primario */
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        box-shadow: 0 4px 15px rgba(255, 127, 0, 0.4);
        cursor: pointer;
        transition: transform 0.2s, box-shadow 0.2s;
        z-index: 1000;
        text-decoration: none;
    }
    .fab:hover {
        transform: scale(1.05);
        color: white;
        box-shadow: 0 6px 20px rgba(255, 127, 0, 0.6);
    }

    /* Grid de Actas */
    .actas-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
    }
    /* Nuevos Estilos de Tarjeta Moderna (Imagen 4) */
    .acta-card-modern {
        background: #ffffff;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.03);
        border: 1px solid #f1f5f9;
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    
    body.dark-theme .acta-card-modern {
        background: var(--surface-color);
        border-color: var(--border-color);
    }

    .acta-header-modern {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }

    .acta-header-modern .folio-text {
        font-size: 0.8rem;
        font-weight: 700;
        color: #94a3b8;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        display: block;
        margin-bottom: 4px;
    }

    .acta-header-modern .client-name {
        font-size: 1.3rem;
        font-weight: 800;
        color: var(--text-color);
        margin: 0;
        line-height: 1.2;
    }

    .status-badge-modern {
        background: #ecfdf5;
        color: #10b981;
        border: 1px solid #a7f3d0;
        padding: 6px 12px;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 800;
        text-transform: uppercase;
    }
    
    body.dark-theme .status-badge-modern {
        background: rgba(16, 185, 129, 0.1);
        border-color: rgba(16, 185, 129, 0.2);
    }

    .tech-box {
        background: #f8fafc;
        border-radius: 12px;
        padding: 12px 16px;
        display: flex;
        align-items: center;
        gap: 12px;
        border: 1px solid #f1f5f9;
    }

    body.dark-theme .tech-box {
        background: rgba(255,255,255,0.02);
        border-color: var(--border-color);
    }

    .tech-icon {
        width: 36px;
        height: 36px;
        background: white;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #f97316;
        font-size: 1.2rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }
    
    body.dark-theme .tech-icon {
        background: #333;
    }

    .tech-info {
        display: flex;
        flex-direction: column;
    }

    .tech-label {
        font-size: 0.7rem;
        font-weight: 700;
        color: #94a3b8;
        text-transform: uppercase;
    }

    .tech-name {
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--text-color);
    }

    .meta-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.9rem;
        color: #64748b;
        font-weight: 600;
        padding: 0 4px;
    }

    .meta-item {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .action-buttons-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }

    .btn-grid-action {
        padding: 10px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 0.85rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        cursor: pointer;
        border: 1px solid transparent;
        transition: all 0.2s;
        text-transform: uppercase;
    }
    .btn-grid-action:hover {
        transform: translateY(-2px);
    }
    
    .btn-pdf { background: #fef2f2; color: #ef4444; border-color: #fee2e2; }
    .btn-ver { background: #eff6ff; color: #3b82f6; border-color: #dbeafe; }
    .btn-editar { background: #fefce8; color: #eab308; border-color: #fef08a; }
    .btn-whatsapp { background: #f0fdf4; color: #22c55e; border-color: #dcfce7; }
    .btn-copiar { background: #f8fafc; color: #475569; border-color: #e2e8f0; }
    .btn-eliminar { background: transparent; color: #94a3b8; border: 1px dashed #cbd5e1; }
    
    body.dark-theme .btn-pdf { background: rgba(239, 68, 68, 0.1); border-color: rgba(239, 68, 68, 0.2); }
    body.dark-theme .btn-ver { background: rgba(59, 130, 246, 0.1); border-color: rgba(59, 130, 246, 0.2); }
    body.dark-theme .btn-editar { background: rgba(234, 179, 8, 0.1); border-color: rgba(234, 179, 8, 0.2); }
    body.dark-theme .btn-whatsapp { background: rgba(34, 197, 94, 0.1); border-color: rgba(34, 197, 94, 0.2); }
    body.dark-theme .btn-copiar { background: rgba(255,255,255,0.05); color: #cbd5e1; border-color: rgba(255,255,255,0.1); }
    body.dark-theme .btn-eliminar { color: #64748b; border-color: #334155; }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: var(--text-muted);
        font-size: 1.1rem;
    }

    /* Estilos del Cronómetro Moderno (App-like) */
    .modern-chronometer {
        width: 100%;
        background: linear-gradient(135deg, rgba(234, 88, 12, 0.05), rgba(249, 115, 22, 0.1));
        border: 1px solid rgba(234, 88, 12, 0.2);
        border-radius: 14px;
        padding: 12px 16px;
        margin-top: 5px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: relative;
        overflow: hidden;
    }
    
    body.dark-theme .modern-chronometer {
        background: linear-gradient(135deg, rgba(234, 88, 12, 0.1), rgba(249, 115, 22, 0.15));
        border-color: rgba(234, 88, 12, 0.3);
    }

    .modern-chronometer::before {
        content: '';
        position: absolute;
        top: 0; left: 0; width: 100%; height: 2px;
        background: linear-gradient(90deg, transparent, #ea580c, transparent);
        animation: scanline 2s linear infinite;
    }

    @keyframes scanline {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }

    .chrono-left {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .chrono-icon-wrapper {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: #ea580c;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        box-shadow: 0 0 10px rgba(234, 88, 12, 0.4);
        animation: pulse-glow 2s infinite;
    }

    @keyframes pulse-glow {
        0% { box-shadow: 0 0 0 0 rgba(234, 88, 12, 0.6); }
        70% { box-shadow: 0 0 0 12px rgba(234, 88, 12, 0); }
        100% { box-shadow: 0 0 0 0 rgba(234, 88, 12, 0); }
    }

    .chrono-text {
        font-size: 0.75rem;
        text-transform: uppercase;
        font-weight: 800;
        color: #ea580c;
        letter-spacing: 0.5px;
        line-height: 1.2;
    }

    .chrono-timer {
        font-family: 'SF Mono', 'Roboto Mono', 'Consolas', monospace;
        font-size: 1.3rem;
        font-weight: 800;
        color: var(--text-color);
        letter-spacing: 1px;
    }
</style>

<div class="page-header-card" style="display:none;"></div> <!-- Para mantener la estructura si el JS global lo espera -->

<!-- Barra de Filtros -->
<div class="filter-bar">
    <div class="filter-search">
        <i class="ph ph-magnifying-glass"></i>
        <input type="text" class="form-control" id="searchFilter" placeholder="Buscar por cliente, folio o DNI...">
    </div>
    <div class="filter-item">
        <select class="form-select" id="techFilter">
            <option value="">Todos los Técnicos</option>
            <!-- Llenar vía AJAX o PHP -->
        </select>
    </div>
    <div class="filter-item" style="max-width: 150px;">
        <input type="date" class="form-control" id="dateFilter">
    </div>
    <div class="filter-item" style="max-width: 120px;">
        <input type="text" class="form-control" id="dniFilter" placeholder="DNI...">
    </div>
    <div class="filter-item" style="max-width: 120px;">
        <input type="text" class="form-control" id="folioFilter" placeholder="Folio...">
    </div>
    <button class="btn btn-clear" id="btnClear"><i class="ph ph-eraser"></i> Limpiar</button>
</div>

<!-- Contenedor Principal -->
<div id="actasContainer">
    <div class="empty-state" id="emptyState">
        No hay actas registradas.
    </div>
    <div class="actas-grid" id="actasGrid" style="display: none;">
        <!-- Tarjetas dinámicas aquí -->
    </div>
</div>

<a href="<?php echo BASE_URL; ?>/modules/actas/create.php" class="fab" title="Crear Acta">
    <i class="ph ph-plus"></i>
</a>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const actasGrid = document.getElementById('actasGrid');
    const emptyState = document.getElementById('emptyState');

    const loadActas = async () => {
        try {
            // Se llamará al backend. Por ahora simulado si no hay actas.
            const formData = new FormData();
            formData.append('action', 'list');
            
            const res = await fetch('<?php echo BASE_URL; ?>/ajax/actas.php', {
                method: 'POST',
                body: formData
            }).then(r => r.json());

            if (res.success && res.data.length > 0) {
                emptyState.style.display = 'none';
                actasGrid.style.display = 'grid';
                actasGrid.innerHTML = '';
                
                res.data.forEach(acta => {
                    const fullFolio = acta.prefijo + acta.folio;
                    const viewUrl = '<?php echo BASE_URL; ?>/modules/actas/view.php?folio=' + fullFolio + '&token=' + acta.token;
                    // Asegurarnos de que url absoluta este disponible
                    const absoluteUrl = new URL(viewUrl, window.location.origin).href;
                    
                    const whatsappMsg = encodeURIComponent('Hola ' + (acta.cliente_nombre || '') + ', puedes ver tu acta de instalación aquí: ' + absoluteUrl);

                    actasGrid.innerHTML += `
                        <div class="acta-card-modern">
                            <div class="acta-header-modern">
                                <div>
                                    <span class="folio-text">FOLIO #${fullFolio}</span>
                                    <h3 class="client-name">${acta.cliente_nombre || 'Sin Nombre'}</h3>
                                </div>
                                <div class="status-badge-modern">${acta.srv_estado || 'REGISTRADA'}</div>
                            </div>
                            
                            <div class="tech-box">
                                <div class="tech-icon"><i class="ph-fill ph-users"></i></div>
                                <div class="tech-info">
                                    <span class="tech-label">TÉCNICO</span>
                                    <span class="tech-name">${acta.tecnico_nombre || 'Sin asignar'}</span>
                                </div>
                            </div>
                            
                            ${(acta.srv_hora_inicio && acta.srv_hora_inicio !== '00:00:00' && acta.srv_hora_inicio !== '00:00' && (!acta.srv_hora_fin || acta.srv_hora_fin === '00:00:00' || acta.srv_hora_fin === '00:00' || acta.srv_hora_fin.trim() === '') && (() => { const startDate = new Date(acta.srv_fecha + 'T' + acta.srv_hora_inicio); return (Date.now() - startDate.getTime()) < 24 * 60 * 60 * 1000; })()) ? `
                            <div class="modern-chronometer live-chronometer" data-start="${acta.srv_fecha}T${acta.srv_hora_inicio}">
                                <div class="chrono-left">
                                    <div class="chrono-icon-wrapper">
                                        <i class="ph-bold ph-timer"></i>
                                    </div>
                                    <div class="chrono-text">Servicio<br>En Curso</div>
                                </div>
                                <div class="chrono-timer">00:00:00</div>
                            </div>
                            ` : ''}
                            
                            <div class="meta-row">
                                <div class="meta-item"><i class="ph-fill ph-map-pin"></i> ${acta.cliente_distrito || 'Sin distrito'}</div>
                                <div class="meta-item"><i class="ph-fill ph-calendar-blank"></i> ${acta.srv_fecha || acta.fecha_creacion.split(' ')[0]}</div>
                            </div>
                            
                            <div class="action-buttons-grid">
                                <button class="btn-grid-action btn-pdf" onclick="window.open('${viewUrl}&pdf=1','_blank')"><i class="ph-fill ph-file-pdf"></i> PDF</button>
                                <button class="btn-grid-action btn-ver" onclick="window.open('${viewUrl}','_blank')"><i class="ph-bold ph-corners-out"></i> VER</button>
                                <button class="btn-grid-action btn-editar" onclick="window.location.href='create.php?edit=${acta.id}'"><i class="ph-fill ph-pencil-simple"></i> EDITAR</button>
                                <button class="btn-grid-action btn-whatsapp" onclick="window.open('https://wa.me/${acta.cliente_whatsapp}?text=${whatsappMsg}','_blank')"><i class="ph-fill ph-whatsapp-logo"></i> WHATSAPP</button>
                                <button class="btn-grid-action btn-copiar" onclick="copyActaLink('${absoluteUrl}')" style="grid-column: span 2;"><i class="ph-bold ph-link"></i> COPIAR ENLACE</button>
                                <button class="btn-grid-action btn-eliminar" onclick="deleteActa(${acta.id})" style="grid-column: span 2;"><i class="ph-fill ph-trash"></i> ELIMINAR ACTA</button>
                            </div>
                        </div>
                    `;
                });
            } else {
                emptyState.style.display = 'block';
                actasGrid.style.display = 'none';
            }
        } catch (e) {
            console.error('Error cargando actas:', e);
            emptyState.style.display = 'block';
            actasGrid.style.display = 'none';
        }
    };

    loadActas();
    
    // Función para actualizar los cronómetros en vivo
    setInterval(() => {
        const chronos = document.querySelectorAll('.live-chronometer');
        const now = new Date();
        chronos.forEach(chrono => {
            const startStr = chrono.getAttribute('data-start');
            if (startStr) {
                const startObj = new Date(startStr);
                let diffMs = now - startObj;
                if (diffMs < 0) diffMs = 0;
                
                let totalSecs = Math.floor(diffMs / 1000);
                let hh = Math.floor(totalSecs / 3600);
                let mm = Math.floor((totalSecs % 3600) / 60);
                let ss = totalSecs % 60;
                
                const disp = String(hh).padStart(2, '0') + ':' + String(mm).padStart(2, '0') + ':' + String(ss).padStart(2, '0');
                chrono.querySelector('.chrono-timer').textContent = disp;
            }
        });
    }, 1000);



    window.copyActaLink = (url) => {
        navigator.clipboard.writeText(url);
        window.showToast('Enlace copiado al portapapeles', 'success');
    };

    window.deleteActa = async (id) => {
        if(confirm('¿Está seguro de eliminar esta acta? Esta acción no se puede deshacer.')) {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);
            try {
                const res = await fetch('<?php echo BASE_URL; ?>/ajax/actas.php', {
                    method: 'POST',
                    body: formData
                }).then(r => r.json());
                if(res.success) {
                    window.showToast('Acta eliminada', 'success');
                    loadActas();
                } else {
                    window.showToast(res.message, 'error');
                }
            } catch(e) {
                window.showToast('Error del servidor', 'error');
            }
        }
    };
});
</script>

<?php include '../../includes/footer.php'; ?>
