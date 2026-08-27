let currentProspectId = null;
let currentPhone = '';

document.addEventListener('DOMContentLoaded', () => {
    loadBoard();
    
    document.getElementById('searchInput').addEventListener('input', (e) => {
        const term = e.target.value.toLowerCase();
        document.querySelectorAll('.kanban-card').forEach(card => {
            const text = card.innerText.toLowerCase();
            card.style.display = text.includes(term) ? 'block' : 'none';
        });
    });

    document.getElementById('prospectForm').addEventListener('submit', (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        formData.append('action', 'save_prospect');
        
        fetch('ajax.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                closeProspectModal();
                loadBoard();
            } else {
                alert(data.message || 'Error al guardar');
            }
        });
    });
});

function loadBoard() {
    fetch('ajax.php?action=get_board&pipeline_id=1')
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            renderBoard(data.stages, data.prospects);
        }
    });
}

function renderBoard(stages, prospects) {
    const board = document.getElementById('kanbanBoard');
    board.innerHTML = '';
    
    stages.forEach(stage => {
        const col = document.createElement('div');
        col.className = 'kanban-column';
        col.innerHTML = `
            <div class="kanban-column-header" style="border-bottom-color: ${stage.color}">
                <span>${stage.name}</span>
                <span class="badge" style="background: rgba(0,0,0,0.1); color: var(--text-color);">0</span>
            </div>
            <div class="kanban-cards" id="stage-${stage.id}" data-id="${stage.id}"></div>
        `;
        board.appendChild(col);
        
        // Inicializar SortableJS
        new Sortable(col.querySelector('.kanban-cards'), {
            group: 'shared',
            animation: 150,
            onEnd: function (evt) {
                const itemEl = evt.item;
                const toStage = evt.to.dataset.id;
                const prospectId = itemEl.dataset.id;
                moveProspect(prospectId, toStage);
            }
        });
    });
    
    // Distribuir prospectos
    prospects.forEach(p => {
        const container = document.getElementById('stage-' + p.stage_id);
        if (container) {
            const isOld = (new Date() - new Date(p.last_activity_at)) > 86400000 * 3; // 3 dias
            const card = document.createElement('div');
            card.className = 'kanban-card ' + (isOld ? 'aging-warning' : '');
            card.dataset.id = p.id;
            card.onclick = () => openOffcanvas(p);
            
            card.innerHTML = `
                <div class="card-score">${p.score} pts</div>
                <div class="card-title">${p.nombre_completo}</div>
                <div class="card-info"><i class="ph ph-phone"></i> ${p.telefono || 'Sin tfno'}</div>
                <div class="card-info"><i class="ph ph-user"></i> ${p.agent_name || 'Sin asignar'}</div>
                <div class="card-badges">
                    ${p.plan_name ? '<span class="badge" style="background: var(--primary-color);">'+p.plan_name+'</span>' : ''}
                    ${p.fuente ? '<span class="badge" style="background: var(--dash-blue);">'+p.fuente+'</span>' : ''}
                </div>
            `;
            container.appendChild(card);
        }
    });
    
    // Actualizar conteos
    stages.forEach(stage => {
        const container = document.getElementById('stage-' + stage.id);
        if(container) {
            container.previousElementSibling.querySelector('.badge').innerText = container.children.length;
        }
    });
}

function moveProspect(prospectId, stageId) {
    const fd = new FormData();
    fd.append('action', 'move_prospect');
    fd.append('prospect_id', prospectId);
    fd.append('stage_id', stageId);
    
    fetch('ajax.php', { method: 'POST', body: fd })
    .then(res => res.json())
    .then(data => {
        if (data.is_won) {
            if(confirm("¡Venta Ganada! ¿Deseas convertir este prospecto en Cliente ahora?")) {
                convertToClient(prospectId);
            }
        }
        loadBoard(); // Recargar para actualizar colores de envejecimiento y conteos
    });
}

function convertToClient(prospectId) {
    const fd = new FormData();
    fd.append('action', 'convert_to_client');
    fd.append('prospect_id', prospectId);
    
    fetch('ajax.php', { method: 'POST', body: fd })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            window.location.href = '/modules/clientes'; // Redirigir a clientes
        } else {
            alert(data.message);
        }
    });
}

function openProspectModal() {
    document.getElementById('prospectForm').reset();
    document.getElementById('p_id').value = '';
    document.getElementById('prospectModalTitle').innerText = 'Nuevo Prospecto';
    document.getElementById('prospectModal').classList.add('active');
}

function closeProspectModal() {
    document.getElementById('prospectModal').classList.remove('active');
}

function openOffcanvas(p) {
    currentProspectId = p.id;
    currentPhone = p.telefono;
    
    document.getElementById('ocNombre').innerText = p.nombre_completo;
    document.getElementById('ocContacto').innerHTML = (p.documento ? `DNI: ${p.documento}<br>` : '') + 
                                                      (p.telefono ? `Tel: ${p.telefono}<br>` : '') + 
                                                      (p.correo ? `Email: ${p.correo}` : '');
    
    document.getElementById('btnWa').style.display = p.telefono ? 'block' : 'none';
    
    document.getElementById('prospectOffcanvas').classList.add('show');
    loadNotes();
}

function closeOffcanvas() {
    document.getElementById('prospectOffcanvas').classList.remove('show');
    currentProspectId = null;
}

function openWhatsApp() {
    if(!currentPhone) return;
    let number = currentPhone.replace(/\D/g, '');
    if(!number.startsWith('51') && number.length === 9) number = '51' + number;
    
    // Auto registrar evento
    const fd = new FormData();
    fd.append('action', 'save_note');
    fd.append('prospect_id', currentProspectId);
    fd.append('type', 'whatsapp');
    fd.append('content', 'Inició conversación por WhatsApp');
    fetch('ajax.php', { method: 'POST', body: fd }).then(() => loadNotes());
    
    window.open('https://api.whatsapp.com/send?phone=' + number, '_blank');
}

function saveNote() {
    const content = document.getElementById('noteContent').value;
    const type = document.getElementById('noteType').value;
    if(!content.trim()) return alert("La nota está vacía");
    
    const fd = new FormData();
    fd.append('action', 'save_note');
    fd.append('prospect_id', currentProspectId);
    fd.append('type', type);
    fd.append('content', content);
    
    fetch('ajax.php', { method: 'POST', body: fd })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            document.getElementById('noteContent').value = '';
            loadNotes();
        } else {
            alert(data.message);
        }
    });
}

function loadNotes() {
    if(!currentProspectId) return;
    fetch('ajax.php?action=get_notes&prospect_id=' + currentProspectId)
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            const cont = document.getElementById('notesContainer');
            cont.innerHTML = '';
            data.notes.forEach(n => {
                const el = document.createElement('div');
                el.className = 'note-item type-' + n.type;
                el.innerHTML = `
                    <div class="note-meta">
                        <span><strong>${n.user_name}</strong> (${n.type})</span>
                        <span>${n.created_at}</span>
                    </div>
                    <div>${n.content}</div>
                `;
                cont.appendChild(el);
            });
        }
    });
}

function checkCoverage() {
    alert("Redirigiendo a Mapas para validación...");
    window.open('/modules/mapas', '_blank');
}