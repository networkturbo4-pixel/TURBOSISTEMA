// Calculamos dinámicamente la ruta base desde la URL (hasta antes de /modules/)
const baseUrl = window.location.pathname.split('/modules/')[0];

document.addEventListener('DOMContentLoaded', () => {
    // Inicializar listeners para tabs del modal
    const tabs = document.querySelectorAll('.v-tab');
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.v-tab-pane').forEach(p => p.classList.remove('active'));
            tab.classList.add('active');
            const target = tab.getAttribute('data-vtab');
            document.getElementById(`vtab-${target}`).classList.add('active');
        });
    });

    // Cargar Vehículos Inicial
    loadVehicles();
});

// Funciones de API & UI
async function loadVehicles() {
    try {
        const res = await fetch(`${baseUrl}/ajax/activos.php?action=get_vehiculos`);
        const data = await res.json();
        
        const container = document.getElementById('vehiculosContainer');
        container.innerHTML = ''; // Limpiar
        
        if (!data.success || data.data.length === 0) {
            container.innerHTML = `<div style="padding:40px; text-align:center; grid-column: 1/-1; color:var(--text-muted);">
                <i class="ph ph-car-profile" style="font-size:3rem; margin-bottom:10px; opacity:0.5;"></i><br>
                No hay vehículos registrados aún.
            </div>`;
            return;
        }

        data.data.forEach(v => {
            let badgeClass = 'badge-activo';
            if (v.estado === 'mantenimiento') badgeClass = 'badge-mantenimiento';
            if (v.estado === 'taller') badgeClass = 'badge-taller';
            if (v.estado === 'inactivo') badgeClass = 'badge-inactivo';
            const estadoTexto = v.estado.charAt(0).toUpperCase() + v.estado.slice(1);
            
            let imgSrc = v.primera_foto ? `${baseUrl}/uploads/activos/${v.primera_foto}` : 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" fill="%23f1f5f9"><rect width="100" height="100" /><text x="50" y="50" font-family="Arial" font-size="14" fill="%2394a3b8" text-anchor="middle" dominant-baseline="middle">Sin Foto</text></svg>';

            const card = document.createElement('div');
            card.className = 'vehiculo-card';
            card.onclick = () => openVehicleDetails(v.id);
            card.innerHTML = `
                <div class="vehiculo-img-wrapper">
                    <img src="${imgSrc}" alt="Vehículo" class="vehiculo-img">
                    <span class="vehiculo-badge status-badge ${badgeClass}">${estadoTexto}</span>
                </div>
                <div class="vehiculo-info">
                    <div class="vehiculo-placa">${v.placa}</div>
                    <div class="vehiculo-modelo">${v.marca} ${v.modelo} <small>(${v.tipo})</small></div>
                    <div class="vehiculo-meta">
                        <span><i class="ph ph-calendar-check"></i> Registrado: ${v.creado_en.split(' ')[0]}</span>
                    </div>
                </div>
            `;
            container.appendChild(card);
        });
    } catch (error) {
        console.error('Error cargando vehículos:', error);
    }
}

// Funciones de Modal General
function openModal(id) {
    document.getElementById(id).classList.add('active');
}
function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}

// Cerrar modales si se hace clic fuera del contenido
window.onclick = function(event) {
    if (event.target.classList.contains('modal-overlay')) {
        event.target.classList.remove('active');
    }
}

// Apertura del modal de Nuevo Vehículo
function openNewVehicleModal() {
    document.getElementById('formNuevoVehiculo').reset();
    openModal('modalNuevoVehiculo');
}

// Evento Submit de Nuevo Vehículo
document.getElementById('formNuevoVehiculo')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData();
    formData.append('action', 'save_vehiculo');
    formData.append('tipo', document.getElementById('nvTipo').value);
    formData.append('placa', document.getElementById('nvPlaca').value);
    formData.append('marca', document.getElementById('nvMarca').value);
    formData.append('modelo', document.getElementById('nvModelo').value);
    
    const fotos = document.getElementById('nvFotos').files;
    for (let i = 0; i < fotos.length; i++) {
        formData.append('fotos_vehiculo[]', fotos[i]);
    }
    
    try {
        const res = await fetch(`${baseUrl}/ajax/activos.php`, {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if(data.success) {
            closeModal('modalNuevoVehiculo');
            loadVehicles(); // Recargar grid
            // Pequeña notificación visual opcional
        } else {
            alert('Error: ' + data.error);
        }
    } catch(err) {
        alert('Error de conexión');
    }
});


let currentVehiculoId = null;

// Apertura de Detalles del Vehículo
async function openVehicleDetails(vehiculoId) {
    currentVehiculoId = vehiculoId;
    try {
        const res = await fetch(`${baseUrl}/ajax/activos.php?action=get_vehiculo_detalle&id=${vehiculoId}`);
        const data = await res.json();
        
        if(!data.success) throw new Error(data.error);
        
        const v = data.data;
        
        // Poblar Info Base
        document.getElementById('lblPlaca').innerText = v.placa;
        document.getElementById('modalTitlePlaca').innerText = `Detalle de Vehículo: ${v.placa}`;
        document.getElementById('lblTipo').innerText = v.tipo.toUpperCase();
        document.getElementById('lblMarca').innerText = v.marca || 'N/A';
        document.getElementById('lblModelo').innerText = v.modelo || 'N/A';
        
        const badgeEl = document.getElementById('lblEstado');
        badgeEl.innerText = v.estado;
        badgeEl.className = `status-badge badge-${v.estado.toLowerCase()}`;
        
        // Poblar Documentos
        const docsHtml = v.documentos.map(d => `
            <li>
                <div class="doc-icon"><i class="ph ph-file-pdf"></i></div>
                <div class="doc-info">
                    <span>${d.titulo}</span>
                    <small>Subido el ${d.fecha_subida.split(' ')[0]}</small>
                </div>
                <div style="display:flex; gap: 5px;">
                    <button class="btn-icon" title="Ver" onclick="window.open('${baseUrl}/uploads/activos/${d.url_archivo}', '_blank')"><i class="ph ph-eye"></i></button>
                    <button class="btn-icon text-primary" title="Editar" onclick="openEditDoc(${d.id}, '${d.tipo_documento}', '${d.titulo}')"><i class="ph ph-pencil-simple"></i></button>
                    <button class="btn-icon text-danger" title="Eliminar" onclick="deleteDoc(${d.id})"><i class="ph ph-trash"></i></button>
                </div>
            </li>
        `).join('');
        document.getElementById('docsList').innerHTML = docsHtml || '<li style="justify-content:center; color:var(--text-muted); border:none;">Sin documentos</li>';
        
        // Poblar Galería
        const galeriaHtml = v.imagenes.map(i => `
            <div class="v-gallery-item" onclick="openImageViewer('${baseUrl}/uploads/activos/${i.url_imagen}')">
                <img src="${baseUrl}/uploads/activos/${i.url_imagen}" alt="Foto">
                <div class="v-gallery-caption">${i.descripcion}</div>
            </div>
        `).join('');
        document.getElementById('galleryList').innerHTML = galeriaHtml || '<div style="grid-column:1/-1; color:var(--text-muted); text-align:center; padding:20px;">Sin fotos en la galería</div>';
        
        // Poblar Historial Llantas y Mantenimiento
        const llantas = v.historial.filter(h => h.tipo_evento === 'cambio_llantas');
        const mantenimientos = v.historial.filter(h => h.tipo_evento !== 'cambio_llantas');
        
        document.getElementById('llantasHistoryTable').innerHTML = llantas.map(h => {
            let fotosBtn = '';
            if(h.fotos_adjuntas) {
                try {
                    const fotos = JSON.parse(h.fotos_adjuntas);
                    if(fotos.length > 0) {
                        fotosBtn = `<button class="btn-icon text-primary" title="Ver ${fotos.length} fotos" onclick="openImageViewer('${baseUrl}/uploads/activos/${fotos[0]}')"><i class="ph ph-image"></i></button>`;
                    }
                } catch(e){}
            }
            return `
            <tr>
                <td>${h.fecha_evento}</td>
                <td>${h.descripcion}</td>
                <td>S/ ${h.costo}</td>
                <td>${h.registrador || '-'} ${fotosBtn}</td>
            </tr>
            `;
        }).join('') || '<tr><td colspan="4" class="text-center text-muted">No hay registros de llantas</td></tr>';
        
        document.getElementById('mantenimientoHistoryTable').innerHTML = mantenimientos.map(h => {
            let fotosBtn = '';
            if(h.fotos_adjuntas) {
                try {
                    const fotos = JSON.parse(h.fotos_adjuntas);
                    if(fotos.length > 0) {
                        fotosBtn = `<button class="btn-icon text-primary" title="Ver ${fotos.length} fotos" onclick="openImageViewer('${baseUrl}/uploads/activos/${fotos[0]}')"><i class="ph ph-image"></i></button>`;
                    }
                } catch(e){}
            }
            return `
            <tr>
                <td>${h.fecha_evento}</td>
                <td><span class="tag-pill">${h.tipo_evento}</span></td>
                <td>${h.descripcion} ${fotosBtn}</td>
                <td>S/ ${h.costo}</td>
            </tr>
            `;
        }).join('') || '<tr><td colspan="4" class="text-center text-muted">No hay registros de mantenimiento</td></tr>';
        
        // Resetea a la primera pestaña
        document.querySelector('.v-tab[data-vtab="info"]').click();

        openModal('modalDetalleVehiculo');
    } catch(error) {
        console.error(error);
        alert('No se pudo cargar el detalle del vehículo');
    }
}


/* ================= Visor de Imágenes Moderno ================= */
function openImageViewer(imageSrc) {
    // Si viene solo el nombre (placeholder), se asume ruta de assets
    let fullSrc = imageSrc;
    if(!imageSrc.includes('http') && !imageSrc.includes('/')) {
        // Ajusta BASE_URL si es necesario o pasa la ruta completa desde PHP
        fullSrc = `../../../assets/img/${imageSrc}`;
    } else if(imageSrc === 'placeholder-car.jpg'){ // fallback seguro
        const imgEl = document.querySelector('.v-gallery-item img');
        if(imgEl) fullSrc = imgEl.src;
    }

    const viewer = document.getElementById('imageViewerOverlay');
    const target = document.getElementById('viewerImageTarget');
    target.src = fullSrc;
    viewer.style.display = 'flex';
    
    // Evitar scroll del body
    document.body.style.overflow = 'hidden';
}

function closeImageViewer() {
    const viewer = document.getElementById('imageViewerOverlay');
    viewer.style.display = 'none';
    document.getElementById('viewerImageTarget').src = '';
    
    // Restaurar scroll del body
    document.body.style.overflow = '';
}

// Cerrar visor con ESC
document.addEventListener('keydown', function(event) {
    if (event.key === "Escape") {
        closeImageViewer();
    }
});


/* === Nuevas Funcionalidades === */
function openUploadDocModal() {
    document.getElementById('formSubirDoc').reset();
    openModal('modalSubirDoc');
}

function openUploadImageModal() {
    document.getElementById('formSubirFoto').reset();
    openModal('modalSubirFoto');
}

function openNewEventModal(tipo) {
    document.getElementById('formRegistrarEvento').reset();
    document.getElementById('evTipoEvento').value = tipo;
    let titulo = tipo === 'cambio_llantas' ? 'Cambio de Llantas' : 'Mantenimiento/Arreglo';
    document.getElementById('tituloModalEvento').innerText = `Registrar: ${titulo}`;
    
    // Set today as default date
    document.getElementById('evFecha').valueAsDate = new Date();
    
    openModal('modalRegistrarEvento');
}

// Submits genéricos para adjuntos
async function submitAssetForm(urlAction, formId, closeId) {
    const form = document.getElementById(formId);
    if (!currentVehiculoId) return alert("Error: ID del vehículo no seleccionado.");
    
    const formData = new FormData(form);
    formData.append('action', urlAction);
    formData.append('vehiculo_id', currentVehiculoId);
    
    // Add manual fields if file inputs
    if(formId === 'formSubirDoc') {
        formData.append('tipo_documento', document.getElementById('docTipo').value);
        formData.append('titulo', document.getElementById('docTitulo').value);
    } else if (formId === 'formSubirFoto') {
        formData.append('descripcion', document.getElementById('fotoDesc').value);
    } else if (formId === 'formRegistrarEvento') {
        formData.append('tipo_evento', document.getElementById('evTipoEvento').value);
        formData.append('fecha_evento', document.getElementById('evFecha').value);
        formData.append('costo', document.getElementById('evCosto').value || 0);
        formData.append('descripcion', document.getElementById('evDesc').value);
    }
    
    try {
        const res = await fetch(`${baseUrl}/ajax/activos.php`, { method: 'POST', body: formData });
        const data = await res.json();
        if(data.success) {
            closeModal(closeId);
            openVehicleDetails(currentVehiculoId); // Recargar los detalles del vehículo actual
        } else {
            alert('Error: ' + data.error);
        }
    } catch(err) {
        alert('Error conectando al servidor');
    }
}

document.getElementById('formSubirDoc')?.addEventListener('submit', function(e) {
    e.preventDefault();
    submitAssetForm('upload_doc', 'formSubirDoc', 'modalSubirDoc');
});

document.getElementById('formSubirFoto')?.addEventListener('submit', function(e) {
    e.preventDefault();
    submitAssetForm('upload_foto', 'formSubirFoto', 'modalSubirFoto');
});

document.getElementById('formRegistrarEvento')?.addEventListener('submit', function(e) {
    e.preventDefault();
    submitAssetForm('save_evento', 'formRegistrarEvento', 'modalRegistrarEvento');
});

/* === Edición Vehículo === */
function openEditVehiculo() {
    if(!currentVehiculoId) return;
    document.getElementById('evnTipo').value = document.getElementById('lblTipo').innerText.toLowerCase();
    document.getElementById('evnPlaca').value = document.getElementById('lblPlaca').innerText;
    document.getElementById('evnMarca').value = document.getElementById('lblMarca').innerText !== 'N/A' ? document.getElementById('lblMarca').innerText : '';
    document.getElementById('evnModelo').value = document.getElementById('lblModelo').innerText !== 'N/A' ? document.getElementById('lblModelo').innerText : '';
    document.getElementById('evnEstado').value = document.getElementById('lblEstado').innerText.toLowerCase();
    openModal('modalEditarVehiculo');
}

document.getElementById('formEditarVehiculo')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData();
    formData.append('action', 'edit_vehiculo');
    formData.append('id', currentVehiculoId);
    formData.append('tipo', document.getElementById('evnTipo').value);
    formData.append('placa', document.getElementById('evnPlaca').value);
    formData.append('marca', document.getElementById('evnMarca').value);
    formData.append('modelo', document.getElementById('evnModelo').value);
    formData.append('estado', document.getElementById('evnEstado').value);
    
    try {
        const res = await fetch(`${baseUrl}/ajax/activos.php`, { method: 'POST', body: formData });
        const data = await res.json();
        if(data.success) {
            closeModal('modalEditarVehiculo');
            loadVehicles(); // actualiza grid 
            openVehicleDetails(currentVehiculoId); // refresca modal actual
        } else alert('Error: ' + data.error);
    } catch(e) { alert('Error de conexión'); }
});

async function archiveVehicle() {
    if(!currentVehiculoId) return;
    if(!confirm("¿Estás seguro de archivar este vehículo? Desaparecerá de la lista principal.")) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_vehiculo');
    formData.append('id', currentVehiculoId);
    
    try {
        const res = await fetch(`${baseUrl}/ajax/activos.php`, { method: 'POST', body: formData });
        const data = await res.json();
        if(data.success) {
            closeModal('modalEditarVehiculo');
            closeModal('modalDetalleVehiculo');
            loadVehicles(); 
        } else alert('Error: ' + data.error);
    } catch(e) { alert('Error de conexión'); }
}

/* === Edición y Eliminación Documentos === */
function openEditDoc(id, tipo, titulo) {
    document.getElementById('edDocId').value = id;
    document.getElementById('edDocTipo').value = tipo;
    document.getElementById('edDocTitulo').value = titulo;
    openModal('modalEditarDoc');
}

document.getElementById('formEditarDoc')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData();
    formData.append('action', 'edit_doc');
    formData.append('doc_id', document.getElementById('edDocId').value);
    formData.append('tipo_documento', document.getElementById('edDocTipo').value);
    formData.append('titulo', document.getElementById('edDocTitulo').value);
    
    try {
        const res = await fetch(`${baseUrl}/ajax/activos.php`, { method: 'POST', body: formData });
        const data = await res.json();
        if(data.success) {
            closeModal('modalEditarDoc');
            openVehicleDetails(currentVehiculoId);
        } else alert('Error: ' + data.error);
    } catch(e) { alert('Error de conexión'); }
});

async function deleteDoc(id) {
    if(!confirm("¿Estás seguro de eliminar este documento?")) return;
    const formData = new FormData();
    formData.append('action', 'delete_doc');
    formData.append('doc_id', id);
    try {
        const res = await fetch(`${baseUrl}/ajax/activos.php`, { method: 'POST', body: formData });
        const data = await res.json();
        if(data.success) {
            openVehicleDetails(currentVehiculoId);
        } else alert('Error: ' + data.error);
    } catch(e) { alert('Error de conexión'); }
}

