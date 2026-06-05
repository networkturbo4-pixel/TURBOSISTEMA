<?php
require_once '../../config/db.php';
requireLogin();
requirePermission($pdo, 'mapas');

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<style>
    .projects-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
    }
    
    .projects-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 24px;
    }

    .project-card {
        background: var(--surface-color, #fff);
        border: 1px solid var(--border-color, #e2e8f0);
        border-radius: 12px;
        overflow: hidden;
        transition: transform 0.2s, box-shadow 0.2s;
        cursor: pointer;
        display: flex;
        flex-direction: column;
    }

    .project-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }

    body.dark-theme .project-card {
        background: var(--surface-color, #1e293b);
        border-color: var(--border-color, #334155);
    }

    .project-cover {
        height: 160px;
        background-color: #f1f5f9;
        background-image: url('https://images.unsplash.com/photo-1524661135-423995f22d0b?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80');
        background-size: cover;
        background-position: center;
        position: relative;
    }
    
    body.dark-theme .project-cover { background-color: #0f172a; }

    .project-info {
        padding: 16px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .project-title {
        font-weight: 700;
        font-size: 1.1rem;
        color: var(--text-color);
        margin: 0 0 8px 0;
    }

    .project-meta {
        font-size: 0.8rem;
        color: var(--text-muted);
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .btn-create-project {
        background: #ef4444; /* Rojo estilo botón crear mapa Google */
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        transition: background 0.2s;
    }
    .btn-create-project:hover { background: #dc2626; }
</style>

<div class="container-fluid">
    <div class="projects-header mt-4">
        <h2>Mis Mapas de Red</h2>
        <button class="btn-create-project" onclick="crearProyecto()">
            <i class="ph-bold ph-plus"></i> Crear un nuevo mapa
        </button>
    </div>

    <div class="projects-grid" id="projects-grid">
        <!-- Tarjetas cargadas vía AJAX -->
        <div style="text-align:center; grid-column: 1 / -1; padding: 40px; color: var(--text-muted);">
            <i class="ph ph-spinner ph-spin" style="font-size: 2rem;"></i><br>Cargando mapas...
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', loadProjects);

function loadProjects() {
    fetch('api.php?action=list_projects')
        .then(r => r.json())
        .then(res => {
            const grid = document.getElementById('projects-grid');
            grid.innerHTML = '';
            
            if (res.success && res.data.length > 0) {
                res.data.forEach(p => {
                    const date = new Date(p.updated_at).toLocaleDateString();
                    grid.innerHTML += `
                        <div class="project-card" onclick="window.location.href='view.php?id=${p.id}'">
                            <div class="project-cover"></div>
                            <div class="project-info">
                                <h3 class="project-title">${p.nombre}</h3>
                                <div class="project-meta">
                                    <i class="ph-fill ph-clock"></i> ${date}
                                </div>
                            </div>
                        </div>
                    `;
                });
            } else {
                grid.innerHTML = `
                    <div style="text-align:center; grid-column: 1 / -1; padding: 60px; color: var(--text-muted); background: var(--surface-color); border-radius: 12px; border: 1px dashed var(--border-color);">
                        <i class="ph-fill ph-map-trifold" style="font-size: 3rem; margin-bottom: 15px; color: #cbd5e1;"></i>
                        <h4>No tienes mapas todavía</h4>
                        <p>Crea tu primer proyecto para empezar a gestionar tu red de fibra.</p>
                    </div>
                `;
            }
        });
}

function crearProyecto() {
    const nombre = prompt('Ingresa el nombre de tu nuevo proyecto de red:', 'Proyecto Zapallal');
    if (!nombre) return;
    
    const formData = new FormData();
    formData.append('action', 'create_project');
    formData.append('nombre', nombre);
    
    fetch('api.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                window.location.href = 'view.php?id=' + res.id;
            } else {
                alert('Error: ' + (res.message || 'No se pudo crear el proyecto'));
            }
        });
}
</script>

<?php include '../../includes/footer.php'; ?>
