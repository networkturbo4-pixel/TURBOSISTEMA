const BASE = document.querySelector('meta[name="base-url"]')?.getAttribute('content') || '';
let currentUser = {};

document.addEventListener('DOMContentLoaded', () => {
    loadProfile();

    // Tab switching logic
    document.querySelectorAll('.prof-tab').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.prof-tab').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.prof-pane').forEach(p => p.classList.remove('active'));
            
            btn.classList.add('active');
            const tabId = btn.getAttribute('data-tab');
            document.getElementById('ptab-' + tabId).classList.add('active');

            if (tabId === 'epp') loadEpp();
            if (tabId === 'mochila') loadMochila();
        });
    });
});

async function loadProfile() {
    try {
        const res = await fetch(`${BASE}/ajax/perfil.php?action=get_profile`).then(r => r.json());
        if (res.success && res.data) {
            currentUser = res.data;
            fillProfileData();
        }
    } catch (e) { console.error('Error loading profile:', e); }
}

function fillProfileData() {
    document.getElementById('profNameDisplay').textContent = currentUser.name || 'Usuario';
    document.getElementById('profRoleDisplay').innerHTML = `<i class="ph ph-shield"></i> ${currentUser.role || 'Usuario'}`;
    
    document.getElementById('profUsername').value = currentUser.username || '';
    document.getElementById('profName').value = currentUser.name || '';
    document.getElementById('profEmail').value = currentUser.email || '';
    document.getElementById('profPhone').value = currentUser.whatsapp || '';

    // Avatar
    const avContainer = document.getElementById('avatarContainer');
    if (currentUser.profile_picture) {
        avContainer.style.backgroundImage = `url('${BASE}/${currentUser.profile_picture}')`;
        avContainer.innerHTML = `
            <div class="avatar-hover-overlay">
                <i class="ph ph-camera"></i>
            </div>
        `;
    }

    // Cover
    const covContainer = document.getElementById('coverContainer');
    if (currentUser.cover_picture) {
        covContainer.style.backgroundImage = `url('${BASE}/${currentUser.cover_picture}')`;
        covContainer.classList.add('has-image');
    }
}

async function saveProfile(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSaveProfile');
    btn.innerHTML = '<i class="ph ph-spinner ph-spin"></i> Guardando...';
    btn.disabled = true;

    const fd = new FormData();
    fd.append('action', 'update_profile');
    fd.append('username', document.getElementById('profUsername').value);
    fd.append('name', document.getElementById('profName').value);
    fd.append('email', document.getElementById('profEmail').value);
    fd.append('whatsapp', document.getElementById('profPhone').value);
    
    const pwd = document.getElementById('profPassword').value;
    if (pwd) fd.append('password', pwd);

    const avatarFile = document.getElementById('avatarInput').files[0];
    if (avatarFile) fd.append('profile_picture', avatarFile);

    const coverFile = document.getElementById('coverInput').files[0];
    if (coverFile) fd.append('cover_picture', coverFile);

    try {
        const res = await fetch(`${BASE}/ajax/perfil.php`, { method: 'POST', body: fd }).then(r => r.json());
        if (res.success) {
            if(window.showToast) window.showToast(res.message, 'success');
            currentUser = res.data;
            fillProfileData();
            document.getElementById('profPassword').value = '';
            
            // update header avatar if needed
            const hAvatar = document.querySelector('.header-right .avatar');
            if (hAvatar && currentUser.profile_picture) {
                hAvatar.style.backgroundImage = `url('${BASE}/${currentUser.profile_picture}')`;
                hAvatar.innerHTML = '';
            }
        } else {
            if(window.showToast) window.showToast(res.message, 'error');
        }
    } catch (err) {
        if(window.showToast) window.showToast('Error de red', 'error');
    } finally {
        btn.innerHTML = '<i class="ph ph-floppy-disk"></i> Guardar Cambios';
        btn.disabled = false;
    }
}

function handleAvatarSelect(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(evt) {
            const avContainer = document.getElementById('avatarContainer');
            avContainer.style.backgroundImage = `url('${evt.target.result}')`;
            avContainer.innerHTML = `
                <div class="avatar-hover-overlay">
                    <i class="ph ph-camera"></i>
                </div>
            `;
        }
        reader.readAsDataURL(file);
    }
}

function handleCoverSelect(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(evt) {
            const covContainer = document.getElementById('coverContainer');
            covContainer.style.backgroundImage = `url('${evt.target.result}')`;
            covContainer.classList.add('has-image');
        }
        reader.readAsDataURL(file);
    }
}

async function loadEpp() {
    const grid = document.getElementById('eppGrid');
    grid.innerHTML = '<div class="empty-state"><i class="ph ph-spinner ph-spin" style="font-size:2rem;display:block;margin-bottom:10px;"></i>Cargando...</div>';
    
    try {
        const res = await fetch(`${BASE}/ajax/perfil.php?action=get_epp`).then(r => r.json());
        if (res.success) {
            renderItems(res.data, grid, 'No tienes EPP asignado.');
        }
    } catch (e) {
        grid.innerHTML = '<div class="empty-state">Error cargando EPP</div>';
    }
}

async function loadMochila() {
    const grid = document.getElementById('mochilaGrid');
    grid.innerHTML = '<div class="empty-state"><i class="ph ph-spinner ph-spin" style="font-size:2rem;display:block;margin-bottom:10px;"></i>Cargando...</div>';
    
    try {
        const res = await fetch(`${BASE}/ajax/perfil.php?action=get_mochila`).then(r => r.json());
        if (res.success) {
            renderItems(res.data, grid, 'No tienes productos en tu mochila.');
        }
    } catch (e) {
        grid.innerHTML = '<div class="empty-state">Error cargando Mochila</div>';
    }
}

function renderItems(items, container, emptyMsg) {
    if (!items || items.length === 0) {
        container.innerHTML = `<div class="empty-state">
            <i class="ph ph-package" style="font-size:3rem;color:var(--border-color);margin-bottom:10px;display:block;"></i>
            ${emptyMsg}
        </div>`;
        return;
    }

    container.innerHTML = items.map((item, i) => {
        const img = item.image 
            ? `<img src="${BASE}/${item.image}" alt="">` 
            : `<i class="ph ph-package"></i>`;
            
        let statText = item.status.replace('_', ' ').toUpperCase();
        let statClass = 'status-' + item.status;
        
        return `
            <div class="item-card" style="animation: fadeIn 0.3s ease forwards; animation-delay: ${i*0.05}s; opacity: 0;">
                <div class="item-img">
                    ${img}
                </div>
                <div class="item-info">
                    <p style="font-size:0.75rem;margin:0 0 2px 0;color:var(--primary-color);font-weight:600;">
                        ${esc(item.code)}
                    </p>
                    <h4 title="${esc(item.product_name)}">${esc(item.product_name)}</h4>
                    <p>${esc(item.category_name || 'Sin Categoría')}</p>
                    <div class="item-meta">
                        <span class="status-badge ${statClass}">${statText}</span>
                        ${item.qty > 1 ? `<span class="item-qty">x${item.qty}</span>` : ''}
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

function esc(str) {
    if (!str) return '';
    return str.toString().replace(/[&<>'"]/g, 
        tag => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            "'": '&#39;',
            '"': '&quot;'
        }[tag] || tag)
    );
}
