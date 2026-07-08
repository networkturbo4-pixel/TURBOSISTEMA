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
        gap: 16px;
    }
    .projects-header-actions {
        display: flex; gap: 10px;
    }
    
    @media (max-width: 768px) {
        .projects-header {
            flex-direction: column;
            align-items: flex-start;
        }
        .projects-header-actions {
            width: 100%;
            justify-content: space-between;
        }
        .projects-header-actions button {
            flex: 1;
            justify-content: center;
            font-size: 0.9rem;
            padding: 10px 8px;
        }
    }
    
    .projects-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 24px;
    }

    .project-card {
        background: var(--surface-color, #fff);
        border: 1px solid var(--border-color, #e2e8f0);
        border-radius: 16px;
        overflow: hidden;
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.3s;
        cursor: pointer;
        display: flex;
        flex-direction: column;
        position: relative;
    }

    .project-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.15);
    }

    body.dark-theme .project-card {
        background: var(--surface-color, #1e293b);
        border-color: var(--border-color, #334155);
    }

    .project-cover {
        height: 180px;
        background-color: #f1f5f9;
        background-image: url('https://images.unsplash.com/photo-1524661135-423995f22d0b?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80');
        background-size: cover;
        background-position: center;
        position: relative;
    }
    
    body.dark-theme .project-cover { background-color: #0f172a; }

    .card-actions {
        position: absolute;
        top: 12px; right: 12px;
        z-index: 10;
    }
    
    .btn-card-action {
        background: rgba(0,0,0,0.4);
        border: none; color: white;
        width: 36px; height: 36px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
        transition: all 0.2s; font-size: 1.2rem;
    }
    .btn-card-action:hover { background: rgba(0,0,0,0.8); transform: scale(1.1); }
    
    .card-menu {
        position: absolute;
        top: 45px; right: 0;
        background: var(--surface-color, #1e293b);
        border: 1px solid var(--border-color, #334155);
        border-radius: 12px;
        box-shadow: 0 15px 30px rgba(0,0,0,0.3);
        display: none; flex-direction: column;
        overflow: hidden; z-index: 20; min-width: 140px;
    }
    .card-menu.show { display: flex; }
    .card-menu button {
        background: transparent; border: none; padding: 12px 16px; text-align: left;
        color: var(--text-color, white); font-size: 0.95rem; cursor: pointer; transition: 0.2s;
        display: flex; align-items: center; gap: 10px; font-weight: 500;
    }
    .card-menu button i { font-size: 1.1rem; }
    .card-menu button:hover { background: rgba(100,116,139,0.1); }
    .card-menu button.text-red { color: #ef4444; }
    .card-menu button.text-red:hover { background: rgba(239,68,68,0.1); }

    .project-info {
        padding: 20px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .project-title {
        font-weight: 800;
        font-size: 1.2rem;
        color: var(--text-color);
        margin: 0 0 10px 0;
        letter-spacing: -0.3px;
    }

    .project-meta {
        font-size: 0.85rem;
        color: var(--text-muted);
        display: flex;
        align-items: center;
        gap: 6px; font-weight: 500;
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
        <div class="projects-header-actions">
            <button class="btn-create-project" style="background: var(--surface-color); color: var(--text-color); border: 1px solid var(--border-color);" onclick="toggleArchived()" id="btnToggleArchived">
                <i class="ph-bold ph-archive"></i> Ver Archivados
            </button>
            <button class="btn-create-project" onclick="crearProyectoModal()">
                <i class="ph-bold ph-plus"></i> Crear un nuevo mapa
            </button>
        </div>
    </div>

    <div class="projects-grid" id="projects-grid">
        <!-- Tarjetas cargadas vía AJAX -->
        <div style="text-align:center; grid-column: 1 / -1; padding: 40px; color: var(--text-muted);">
            <i class="ph ph-spinner ph-spin" style="font-size: 2rem;"></i><br>Cargando mapas...
        </div>
    </div>
</div>

<style>
/* Custom Edit/Create Modal */
.edit-modal-overlay {
    position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
    background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);
    display: none; align-items: center; justify-content: center; z-index: 1000;
}
.edit-modal-overlay.active { display: flex; }
.edit-modal-content {
    background: var(--surface-color, #1e293b);
    border: 1px solid var(--border-color, #334155);
    border-radius: 16px; padding: 24px; width: 400px; max-width: 90%;
    box-shadow: 0 20px 40px rgba(0,0,0,0.4);
}
.edit-modal-content h3 { margin-top: 0; color: var(--text-color); margin-bottom: 20px; font-weight: 800; }
.edit-modal-content input {
    width: 100%; padding: 12px 16px; border-radius: 10px;
    border: 1px solid var(--border-color, #334155); background: rgba(0,0,0,0.1);
    color: var(--text-color); font-size: 1rem; margin-bottom: 24px; transition: 0.2s;
}
.edit-modal-content input:focus { outline: none; border-color: #38bdf8; box-shadow: 0 0 0 3px rgba(56,189,248,0.2); }
.edit-modal-actions { display: flex; justify-content: flex-end; gap: 12px; }
.edit-modal-actions button {
    padding: 10px 18px; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; transition: 0.2s;
}
.btn-cancel { background: transparent; color: var(--text-muted); }
.btn-cancel:hover { background: rgba(255,255,255,0.05); }
.btn-save { background: #38bdf8; color: #fff; }
.btn-save:hover { background: #0284c7; }
</style>

<div class="edit-modal-overlay" id="editModalOverlay">
    <div class="edit-modal-content">
        <h3 id="editModalTitle">Editar nombre del proyecto</h3>
        <input type="text" id="editProjectNameInput" placeholder="Nombre del proyecto">
        <div class="edit-modal-actions">
            <button class="btn-cancel" onclick="closeEditModal()">Cancelar</button>
            <button class="btn-save" onclick="saveEditProject()">Guardar</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', loadProjects);

let isShowingArchived = false;

function toggleArchived() {
    isShowingArchived = !isShowingArchived;
    const btn = document.getElementById('btnToggleArchived');
    if (isShowingArchived) {
        btn.innerHTML = '<i class="ph-bold ph-arrow-left"></i> Volver a Activos';
        btn.style.borderColor = '#38bdf8';
        btn.style.color = '#38bdf8';
    } else {
        btn.innerHTML = '<i class="ph-bold ph-archive"></i> Ver Archivados';
        btn.style.borderColor = 'var(--border-color)';
        btn.style.color = 'var(--text-color)';
    }
    loadProjects();
}

function loadProjects() {
    fetch(`api.php?action=list_projects&show_archived=${isShowingArchived ? 1 : 0}`)
        .then(r => r.json())
        .then(res => {
            const grid = document.getElementById('projects-grid');
            grid.innerHTML = '';
            
            if (res.success && res.data.length > 0) {
                res.data.forEach(p => {
                    const date = new Date(p.updated_at).toLocaleDateString();
                    
                    // Previsualización de mapa
                    let bgImage = "url('https://images.unsplash.com/photo-1524661135-423995f22d0b?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80')";
                    if (p.preview_geojson) {
                        try {
                            const geo = JSON.parse(p.preview_geojson);
                            if (geo.type === 'Point' && geo.coordinates) {
                                const lng = geo.coordinates[0];
                                const lat = geo.coordinates[1];
                                bgImage = `url('https://maps.googleapis.com/maps/api/staticmap?center=${lat},${lng}&zoom=15&size=400x180&maptype=satellite&key=AIzaSyAzf2GmB9lw1k7ONXk1VHScmd-pe-FtMtE')`;
                            }
                        } catch(e) {}
                    }

                    let archiveBtn = isShowingArchived 
                        ? `<button onclick="unarchiveProject(${p.id}, event)"><i class="ph-bold ph-upload"></i> Desarchivar</button>` 
                        : `<button onclick="archiveProject(${p.id}, event)"><i class="ph-bold ph-archive"></i> Archivar</button>`;

                    grid.innerHTML += `
                        <div class="project-card">
                            <div class="card-actions">
                                <button class="btn-card-action" onclick="toggleMenu(${p.id}, event)"><i class="ph-bold ph-dots-three-vertical"></i></button>
                                <div class="card-menu" id="menu-${p.id}">
                                    <button onclick="openEditModal(${p.id}, '${p.nombre.replace(/'/g, "\\'")}', event)"><i class="ph-bold ph-pencil"></i> Editar Nombre</button>
                                    ${archiveBtn}
                                    <button class="text-red" onclick="deleteProject(${p.id}, event)"><i class="ph-bold ph-trash"></i> Eliminar</button>
                                </div>
                            </div>
                            <div class="project-cover" style="background-image: ${bgImage};" onclick="window.location.href='view.php?id=${p.id}'"></div>
                            <div class="project-info" onclick="window.location.href='view.php?id=${p.id}'">
                                <h3 class="project-title">${p.nombre}</h3>
                                <div class="project-meta">
                                    <i class="ph-fill ph-clock"></i> Actualizado el ${date}
                                </div>
                            </div>
                        </div>
                    `;
                });
            } else {
                let msg = isShowingArchived ? "No tienes mapas archivados" : "No tienes mapas todavía";
                grid.innerHTML = `
                    <div style="text-align:center; grid-column: 1 / -1; padding: 60px; color: var(--text-muted); background: var(--surface-color); border-radius: 12px; border: 1px dashed var(--border-color);">
                        <i class="ph-fill ph-map-trifold" style="font-size: 3rem; margin-bottom: 15px; color: #cbd5e1;"></i>
                        <h4>${msg}</h4>
                        <p>${isShowingArchived ? 'Los mapas que archives aparecerán aquí.' : 'Crea tu primer proyecto para empezar a gestionar tu red de fibra.'}</p>
                    </div>
                `;
            }
        });
}

function toggleMenu(id, e) {
    e.stopPropagation();
    // Cerrar todos los demas
    document.querySelectorAll('.card-menu').forEach(m => {
        if(m.id !== 'menu-'+id) m.classList.remove('show');
    });
    document.getElementById('menu-'+id).classList.toggle('show');
}

// Cerrar menu al hacer click fuera
document.addEventListener('click', () => {
    document.querySelectorAll('.card-menu').forEach(m => m.classList.remove('show'));
});

// -- NUEVO MODAL CUSTOM --
let editProjectId = null;

function openEditModal(id, nombreActual, e) {
    if(e) e.stopPropagation();
    if(document.getElementById('menu-'+id)) document.getElementById('menu-'+id).classList.remove('show');
    
    editProjectId = id;
    document.getElementById('editModalTitle').innerText = 'Editar nombre del proyecto';
    document.getElementById('editProjectNameInput').value = nombreActual;
    document.getElementById('editModalOverlay').classList.add('active');
    document.getElementById('editProjectNameInput').focus();
}

function crearProyectoModal() {
    editProjectId = 'NEW';
    document.getElementById('editModalTitle').innerText = 'Crear nuevo mapa';
    document.getElementById('editProjectNameInput').value = 'Proyecto Nuevo';
    document.getElementById('editModalOverlay').classList.add('active');
    document.getElementById('editProjectNameInput').focus();
    document.getElementById('editProjectNameInput').select();
}

function closeEditModal() {
    document.getElementById('editModalOverlay').classList.remove('active');
    editProjectId = null;
}

function saveEditProject() {
    if(!editProjectId) return;
    const nuevoNombre = document.getElementById('editProjectNameInput').value.trim();
    if(!nuevoNombre) return;

    if (editProjectId === 'NEW') {
        const formData = new FormData();
        formData.append('action', 'create_project');
        formData.append('nombre', nuevoNombre);
        
        fetch('api.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    window.location.href = 'view.php?id=' + res.id;
                } else {
                    alert('Error: ' + (res.message || 'No se pudo crear'));
                }
            });
    } else {
        const formData = new FormData();
        formData.append('action', 'edit_project');
        formData.append('id', editProjectId);
        formData.append('nombre', nuevoNombre);
        
        fetch('api.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => {
                if(res.success) {
                    closeEditModal();
                    loadProjects();
                } else {
                    alert('Error: ' + res.message);
                }
            });
    }
}

// Permitir guardar con Enter
document.getElementById('editProjectNameInput').addEventListener('keyup', function(e) {
    if(e.key === 'Enter') saveEditProject();
    if(e.key === 'Escape') closeEditModal();
});

function archiveProject(id, e) {
    e.stopPropagation();
    document.getElementById('menu-'+id).classList.remove('show');
    if(!confirm('¿Estás seguro de archivar este mapa? Se ocultará de la lista principal.')) return;
    
    const formData = new FormData();
    formData.append('action', 'archive_project');
    formData.append('id', id);
    
    fetch('api.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if(res.success) loadProjects();
            else alert('Error: ' + res.message);
        });
}

function unarchiveProject(id, e) {
    e.stopPropagation();
    document.getElementById('menu-'+id).classList.remove('show');
    if(!confirm('¿Deseas desarchivar este mapa y devolverlo a la lista principal?')) return;
    
    const formData = new FormData();
    formData.append('action', 'unarchive_project');
    formData.append('id', id);
    
    fetch('api.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if(res.success) loadProjects();
            else alert('Error: ' + res.message);
        });
}

function deleteProject(id, e) {
    e.stopPropagation();
    document.getElementById('menu-'+id).classList.remove('show');
    if(!confirm('¡ATENCIÓN! ¿Estás totalmente seguro de eliminar este mapa? Se borrarán todos los puntos, hilos y fotos. Esto no se puede deshacer.')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_project');
    formData.append('id', id);
    
    fetch('api.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if(res.success) loadProjects();
            else alert('Error: ' + res.message);
        });
}
</script>

<?php include '../../includes/footer.php'; ?>
