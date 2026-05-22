/* ═══════════════════════════════════════════════════
   MÓDULO MOCHILA — JS Premium 2.0
   Off-canvas, camera capture, historial de asignaciones
═══════════════════════════════════════════════════ */

const BASE = document.querySelector('meta[name="base-url"]')?.content || '';
let allUsers = [];
let currentUserId = null;
let currentUserData = null;
let currentSkuPhotos = [];
let lightboxIndex = 0;

// Camera state
let cameraStream = null;
let capturedBlob = null;

// ── Init ──────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    loadStats();
    loadUsers();
});

// ── Stats ─────────────────────────────────────────
async function loadStats() {
    // Stats are now dynamically calculated inside filterUsers() based on the filtered list
    // to ensure they are always synchronized with the displayed cards.
}

function animateNumber(wrapperId, target) {
    const el  = document.querySelector(`#${wrapperId} .stat-num`);
    if (!el) return;
    let start = 0;
    const dur = 600;
    const step = timestamp => {
        if (!start) start = timestamp;
        const progress = Math.min((timestamp - start) / dur, 1);
        el.textContent = Math.round(progress * target);
        if (progress < 1) requestAnimationFrame(step);
    };
    requestAnimationFrame(step);
}

// ── Load & render users (grid cards) ──────────────
async function loadUsers() {
    const grid = document.getElementById('usersList');
    grid.innerHTML = `
        <div class="skeleton-pulse" style="height:180px;"></div>
        <div class="skeleton-pulse" style="height:180px;"></div>
        <div class="skeleton-pulse" style="height:180px;"></div>
    `;

    try {
        const res  = await fetch(`${BASE}/ajax/mochila.php?action=list_users`);
        const data = await res.json();
        if (data.success) {
            allUsers = data.data.filter(u => Number(u.total_items) > 0);
            
            const roles = [...new Set(allUsers.map(u => u.role))].sort();
            const roleSelect = document.getElementById('roleFilter');
            if (roleSelect) {
                const currentRole = roleSelect.value;
                roleSelect.innerHTML = '<option value="">Todos los roles</option>' + 
                    roles.map(r => `<option value="${escapeHtml(r)}">${escapeHtml(r)}</option>`).join('');
                roleSelect.value = currentRole;
            }

            filterUsers();
        }
    } catch (e) {
        grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;color:var(--text-muted);padding:40px;">Error al cargar usuarios</div>';
    }
}

function renderUserGrid(users) {
    const grid  = document.getElementById('usersList');
    const badge = document.getElementById('usersCount');
    if (badge) badge.textContent = users.length;

    if (users.length === 0) {
        grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;color:var(--text-muted);padding:40px;"><i class="ph ph-users" style="font-size:2.5rem;display:block;margin-bottom:10px;opacity:.3;"></i>No hay usuarios</div>';
        return;
    }

    grid.innerHTML = users.map(u => {
        const initials     = getInitials(u.name);
        const avatarInner  = u.profile_picture
            ? `<img src="${BASE}/${u.profile_picture}" alt="${escapeHtml(u.name)}">`
            : initials;
        const roleIcon     = getRoleIcon(u.role);
        const itemsCount   = Number(u.total_items) || 0;

        return `
            <div class="user-grid-card" id="ugc-${u.id}">
                <div class="ugc-avatar">${avatarInner}</div>
                <div class="ugc-info">
                    <div class="ugc-name" title="${escapeHtml(u.name)}">${escapeHtml(u.name)}</div>
                    <div style="margin:6px 0 4px;">
                        <span class="ugc-role-badge"><i class="${roleIcon}"></i>${escapeHtml(u.role)}</span>
                    </div>
                    <div class="ugc-items-row">
                        <i class="ph ph-backpack"></i>
                        <span class="items-badge">${itemsCount}</span>
                        <span>${itemsCount === 1 ? 'item' : 'items'} en mochila</span>
                    </div>
                </div>
                <button class="ugc-btn" onclick="openOffCanvas(${u.id})">
                    <i class="ph ph-backpack"></i> Ver Mochila
                </button>
            </div>
        `;
    }).join('');
}

function filterUsers() {
    const query = (document.getElementById('usersSearchInput')?.value || '').toLowerCase().trim();
    const role = document.getElementById('roleFilter')?.value || '';
    const sort = document.getElementById('sortFilter')?.value || 'name_asc';

    let filtered = allUsers.filter(u => {
        const matchQuery = !query || u.name.toLowerCase().includes(query) || u.role.toLowerCase().includes(query);
        const matchRole = !role || u.role === role;
        return matchQuery && matchRole;
    });

    if (sort === 'items_desc') {
        filtered.sort((a, b) => Number(b.total_items) - Number(a.total_items));
    } else {
        filtered.sort((a, b) => a.name.localeCompare(b.name));
    }

    let enCampo = 0;
    let sinFotos = 0;
    filtered.forEach(u => {
        enCampo += Number(u.total_items) || 0;
        sinFotos += Number(u.sin_fotos) || 0;
    });
    animateNumber('stat-en-campo', enCampo);
    animateNumber('stat-usuarios', filtered.length);
    animateNumber('stat-sin-fotos', sinFotos);

    renderUserGrid(filtered);
}

// ── Off-canvas ────────────────────────────────────
async function openOffCanvas(userId) {
    currentUserId = userId;
    const user = allUsers.find(u => u.id == userId);

    // Render header
    const initials    = getInitials(user?.name || '?');
    const avatarInner = user?.profile_picture
        ? `<img src="${BASE}/${user.profile_picture}" alt="">`
        : initials;
    document.getElementById('offcanvasAvatar').innerHTML   = avatarInner;
    document.getElementById('offcanvasUserName').textContent = user?.name || '—';
    document.getElementById('offcanvasUserRole').textContent = user?.role || '—';

    // Show off-canvas
    document.getElementById('offcanvasBackdrop').classList.add('active');
    document.getElementById('mochilaOffCanvas').classList.add('active');
    document.body.style.overflow = 'hidden';

    // Switch to productos tab
    switchTab('productos');

    // Load products
    await loadOffCanvasBackpack(userId);
}

function closeOffCanvas() {
    document.getElementById('offcanvasBackdrop').classList.remove('active');
    document.getElementById('mochilaOffCanvas').classList.remove('active');
    document.body.style.overflow = '';
    stopCamera();
}

function switchTab(tabName) {
    document.querySelectorAll('.offcanvas-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.offcanvas-tab-content').forEach(t => t.classList.remove('active'));

    document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
    document.getElementById(`tab-${tabName}`).classList.add('active');

    if (tabName !== 'registrar') stopCamera();
}

// ── Cargar mochila en off-canvas ──────────────────
async function loadOffCanvasBackpack(userId) {
    const container = document.getElementById('offcanvasProductos');
    container.innerHTML = `<div class="oc-loading"><i class="ph ph-spinner ph-spin"></i><p>Cargando mochila...</p></div>`;

    try {
        const form = new FormData();
        form.append('action', 'get_user_backpack');
        form.append('user_id', userId);
        const res  = await fetch(`${BASE}/ajax/mochila.php`, { method: 'POST', body: form });
        const data = await res.json();

        if (data.success) {
            currentUserData = data;
            renderOffCanvasContent(data);
            populateRegisterSkuSelect(data.normal_items || [], data.bulk_items || []);
        } else {
            container.innerHTML = `<div class="oc-empty"><i class="ph ph-warning"></i><h5>Error</h5><p>${data.message}</p></div>`;
        }
    } catch (e) {
        container.innerHTML = `<div class="oc-empty"><i class="ph ph-wifi-slash"></i><h5>Error de red</h5><p>No se pudo cargar la mochila</p></div>`;
    }
}

function renderOffCanvasContent(data) {
    const container   = document.getElementById('offcanvasProductos');
    const normalItems = data.normal_items || [];
    const bulkItems   = data.bulk_items  || [];

    if (normalItems.length === 0 && bulkItems.length === 0) {
        container.innerHTML = `
            <div class="oc-empty">
                <i class="ph ph-backpack"></i>
                <h5>Mochila vacía</h5>
                <p>Este usuario no tiene equipos asignados</p>
            </div>
        `;
        return;
    }

    let html = '';

    // ─ SKU Normal Items ─
    if (normalItems.length > 0) {
        html += `<div class="oc-section-title"><i class="ph ph-barcode"></i> Equipos Individuales <span class="section-count">${normalItems.length}</span></div>`;

        normalItems.forEach(item => {
            const imgHtml = item.product_image
                ? `<img class="oc-sku-product-img" src="${BASE}/${item.product_image}" alt="">`
                : `<div class="oc-sku-product-img-placeholder"><i class="ph ph-cube"></i></div>`;

            const historia = buildHistoryHtml(item);

            const photoCountBadge = item.photo_count > 0
                ? `<span class="oc-foto-badge purple">${item.photo_count}</span>`
                : `<span class="oc-foto-badge red">0</span>`;

            html += `
                <div class="oc-sku-card" id="oc-sku-${item.id}">

                    <div class="oc-sku-card-top">
                        ${imgHtml}
                        <div class="oc-sku-meta">
                            <div class="oc-sku-code">${escapeHtml(item.sku_code)}</div>
                            <div class="oc-sku-name">${escapeHtml(item.product_name)}</div>
                            <div class="oc-sku-cat">${escapeHtml(item.category_name || 'Sin categor\u00eda')}</div>
                        </div>
                        <div class="oc-sku-card-right">
                            <select class="oc-sku-status-select ${item.status}" onchange="updateSkuStatusFromMochila(${item.id}, this.value, this)" style="background:var(--bg-color); border:1px solid var(--border-color); color:var(--text-color); border-radius:12px; padding:3px 6px; font-size:0.75rem; font-weight:700; cursor:pointer;">
                                <option value="disponible" ${item.status === 'disponible' ? 'selected' : ''}>Disponible</option>
                                <option value="instalado" ${item.status === 'instalado' ? 'selected' : ''}>Instalado</option>
                                <option value="malogrado" ${item.status === 'malogrado' ? 'selected' : ''}>Malogrado</option>
                                <option value="reparado" ${item.status === 'reparado' ? 'selected' : ''}>Reparado</option>
                                <option value="en_transito" ${item.status === 'en_transito' ? 'selected' : ''}>En Tránsito</option>
                            </select>
                            <div class="oc-sku-actions-inline">
                                <button class="oc-action-btn cam icon-only" onclick="quickPhotoFromTab(${item.id}, '${escapeHtml(item.sku_code)}', '${item.product_image || ''}')" title="Registrar foto">
                                    <i class="ph ph-camera-plus"></i>
                                </button>
                                <button class="oc-action-btn danger icon-only" onclick="returnToWarehouse(${item.id}, '${escapeHtml(item.sku_code)}')" title="Devolver al almac\u00e9n">
                                    <i class="ph ph-arrow-u-up-left"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="oc-sku-toggles">
                        ${historia}
                        <button class="oc-photos-toggle" id="toggle-photos-${item.id}" onclick="togglePhotos(${item.id}, '${escapeHtml(item.sku_code)}')">
                            <i class="ph ph-camera"></i>
                            Fotos ${photoCountBadge}
                            <i class="ph ph-caret-down toggle-chevron"></i>
                        </button>
                        <div class="oc-photos-area" id="photos-area-${item.id}" style="display:none;"></div>
                    </div>

                </div>
            `;
        });
    }

    // ─ Bulk Items ─
    if (bulkItems.length > 0) {
        html += `<div class="oc-section-title" style="margin-top:20px;"><i class="ph ph-package"></i> Productos a Granel <span class="section-count">${bulkItems.length}</span></div>`;
        bulkItems.forEach(item => {
            html += `
                <div class="oc-bulk-card">
                    <div class="oc-bulk-info">
                        <div class="oc-bulk-name">${escapeHtml(item.product_name)}</div>
                        <div class="oc-bulk-cat">${escapeHtml(item.category_name || 'Sin categoría')}</div>
                    </div>
                    <div class="oc-bulk-qty-block">
                        <div class="oc-bulk-qty">${parseFloat(item.quantity)}</div>
                        <div class="oc-bulk-unit">${escapeHtml(item.unit_type || 'Unidades')}</div>
                    </div>
                    <div class="oc-bulk-actions">
                        <button class="oc-action-btn cam icon-only" onclick="quickPhotoBulk(${item.product_id}, '${escapeHtml(item.product_name)}', '${escapeHtml(item.product_image || '')}')" title="Registrar foto del producto">
                            <i class="ph ph-camera-plus"></i>
                        </button>
                        <button class="oc-action-btn danger icon-only" onclick="returnBulk(${item.stock_id}, '${escapeHtml(item.product_name)}', ${parseFloat(item.quantity)})" title="Devolver al almacen">
                            <i class="ph ph-arrow-u-up-left"></i>
                        </button>
                    </div>
                </div>
            `;
        });
    }

    container.innerHTML = html;
}

// ── Historia de asignaciones (toggle async) ──────
function buildHistoryHtml(item) {
    // Show a collapse toggle; actual data loads on demand
    const historiaLabel = {
        'ninguno':    '',
        'devuelto':   '🔁 Devuelto',
        'malogrado':  '⚠️ Malogrado',
        'antiguo':    '📦 Antiguo',
        'en_transito': '🚚 En tránsito'
    }[item.historia] || '';

    return `
        <div style="padding: 0 16px;">
            <button class="oc-photos-toggle" id="toggle-hist-${item.id}" onclick="toggleHistory(${item.id})">
                <i class="ph ph-clock-clockwise"></i>
                Historial de asignaciones
                ${historiaLabel ? `<span style="font-size:.7rem;color:var(--text-muted);">${historiaLabel}</span>` : ''}
                <i class="ph ph-caret-down toggle-chevron"></i>
            </button>
            <div id="hist-area-${item.id}" style="display:none;padding-bottom:10px;"></div>
        </div>
    `;
}

async function toggleHistory(skuId) {
    const area   = document.getElementById(`hist-area-${skuId}`);
    const toggle = document.getElementById(`toggle-hist-${skuId}`);
    const isOpen = area.style.display !== 'none';

    if (isOpen) {
        area.style.display = 'none';
        toggle.classList.remove('open');
        return;
    }

    toggle.classList.add('open');
    area.style.display = 'block';

    if (area.dataset.loaded) return; // Already loaded
    area.innerHTML = '<div style="padding:8px;text-align:center;"><i class="ph ph-spinner ph-spin" style="color:var(--primary-color);"></i></div>';

    try {
        const form = new FormData();
        form.append('action', 'get_sku_history');
        form.append('sku_id', skuId);
        const res  = await fetch(`${BASE}/ajax/mochila.php`, { method: 'POST', body: form });
        const data = await res.json();

        if (data.success && data.data.length > 0) {
            const tipoLabel = {
                entrada:     '📥 Asignado',
                salida:      '📤 Enviado',
                devolucion:  '↩️ Devuelto',
                reparacion:  '🔧 En reparación'
            };
            const items = data.data.map(h => {
                let photosHtml = '';
                if (h.photos) {
                    const arr = h.photos.split(',');
                    photosHtml = `<div style="display:flex;gap:6px;margin-top:6px;overflow-x:auto;padding-bottom:4px;">${arr.map(p => `<img src="${BASE}/${p}" style="height:40px;border-radius:4px;cursor:pointer;object-fit:cover;" onclick="window.open('${BASE}/${p}','_blank')">`).join('')}</div>`;
                }
                return `
                <div class="oc-history-item">
                    <div class="hi-date">${formatDate(h.created_at)}</div>
                    <div class="hi-text">${tipoLabel[h.tipo] || h.tipo}${h.user_name ? ` — <em>${escapeHtml(h.user_name)}</em>` : ''}${h.notas ? `<br><span style="color:var(--text-muted);font-size:.7rem;">${escapeHtml(h.notas)}</span>` : ''}</div>
                    ${photosHtml}
                </div>
                `;
            }).join('');
            area.innerHTML = `<div class="oc-history-timeline" style="margin-top:8px;">${items}</div>`;
        } else if (data.success) {
            area.innerHTML = '<div style="padding:8px;color:var(--text-muted);font-size:.78rem;text-align:center;">Sin movimientos registrados</div>';
        } else {
            area.innerHTML = '<div style="padding:8px;color:#ef4444;font-size:.78rem;">Error al cargar historial</div>';
        }
        area.dataset.loaded = '1';
    } catch (e) {
        area.innerHTML = '<div style="padding:8px;color:#ef4444;font-size:.78rem;">Error de red</div>';
    }
}


// ── Toggle fotos ──────────────────────────────────
async function togglePhotos(skuId, skuCode) {
    const area    = document.getElementById(`photos-area-${skuId}`);
    const toggle  = document.getElementById(`toggle-photos-${skuId}`);
    const isOpen  = area.style.display !== 'none';

    if (isOpen) {
        area.style.display = 'none';
        toggle.classList.remove('open');
        return;
    }

    toggle.classList.add('open');
    area.style.display = 'block';
    area.innerHTML = '<div style="padding:10px;text-align:center;"><i class="ph ph-spinner ph-spin" style="color:var(--primary-color);"></i></div>';

    try {
        const form = new FormData();
        form.append('action', 'get_sku_photos');
        form.append('sku_id', skuId);
        const res  = await fetch(`${BASE}/ajax/mochila.php`, { method: 'POST', body: form });
        const data = await res.json();

        if (data.success) {
            currentSkuPhotos = data.data;
            if (data.data.length === 0) {
                area.innerHTML = '<div style="padding:10px;text-align:center;color:var(--text-muted);font-size:.8rem;"><i class="ph ph-image" style="font-size:1.8rem;display:block;opacity:.3;margin-bottom:6px;"></i>Sin fotos aún</div>';
            } else {
                let g = '<div class="oc-photo-gallery">';
                data.data.forEach((photo, idx) => {
                    g += `
                        <div class="oc-photo-thumb" onclick="openLightbox(${idx})">
                            <img src="${BASE}/${photo.ruta_archivo}" alt="Foto" loading="lazy">
                        </div>
                    `;
                });
                g += '</div>';
                area.innerHTML = g;
            }
        }
    } catch (e) {
        area.innerHTML = '<div style="color:#ef4444;padding:8px;font-size:.8rem;">Error al cargar fotos</div>';
    }
}

// ── Quick photo: ir a tab registrar con sku presel. ──
function quickPhotoFromTab(skuId, skuCode, productImage) {
    switchTab('registrar');
    const sel = document.getElementById('registerSkuSelect');
    if (!sel) return;

    if (skuId) {
        // Find option by value (always string compare in DOM)
        const targetVal = String(skuId);
        let found = false;
        for (const opt of sel.options) {
            if (opt.value === targetVal) {
                opt.selected = true;
                found = true;
                break;
            }
        }
        if (found) {
            // Get image from map or from passed parameter
            const entry = window._registerSkuMap && window._registerSkuMap[targetVal];
            const img = productImage || (entry ? entry.image : '') || '';
            updateRegisterProductPreview(img);
        } else {
            sel.value = '';
            updateRegisterProductPreview('');
        }
    } else {
        sel.value = '';
        updateRegisterProductPreview('');
    }

    // Dispatch change to update any other listeners
    sel.dispatchEvent(new Event('change'));
    checkRegisterReady();
}

// ── Populate register select (normal SKUs + bulk products) ──
function populateRegisterSkuSelect(normalItems, bulkItems = []) {
    const sel = document.getElementById('registerSkuSelect');
    if (!sel) return;

    // Map: value_key -> { image, name }
    window._registerSkuMap = {};

    sel.innerHTML = '<option value="">— Elige un producto —</option>';

    // ─ Equipos individuales (SKUs) ─
    if (normalItems.length > 0) {
        const grpNormal = document.createElement('optgroup');
        grpNormal.label = '📦 Equipos individuales';
        normalItems.forEach(item => {
            window._registerSkuMap[String(item.id)] = { image: item.product_image || '', name: `${item.product_name} (${item.sku_code})` };
            const opt = document.createElement('option');
            opt.value = item.id;
            opt.textContent = `${item.product_name} (${item.sku_code})`;
            grpNormal.appendChild(opt);
        });
        sel.appendChild(grpNormal);
    }

    // ─ Productos a granel ─
    if (bulkItems.length > 0) {
        const grpBulk = document.createElement('optgroup');
        grpBulk.label = '📦 Materiales a granel';
        bulkItems.forEach(item => {
            const key = `bulk_${item.product_id}`;
            window._registerSkuMap[key] = { image: item.product_image || '', name: item.product_name };
            const opt = document.createElement('option');
            opt.value = key;
            opt.textContent = `${item.product_name} — ${parseFloat(item.quantity)} ${item.unit_type || 'uds'}`;
            grpBulk.appendChild(opt);
        });
        sel.appendChild(grpBulk);
    }

    sel.onchange = () => {
        const entry = window._registerSkuMap[sel.value];
        updateRegisterProductPreview(entry ? entry.image : '');
        checkRegisterReady();
    };
}

// ── Ir a registrar foto desde item a granel ──
function quickPhotoBulk(productId, productName, productImage) {
    switchTab('registrar');
    const sel = document.getElementById('registerSkuSelect');
    if (!sel) return;
    const targetVal = `bulk_${productId}`;
    let found = false;
    for (const opt of sel.options) {
        if (opt.value === targetVal) {
            opt.selected = true;
            found = true;
            break;
        }
    }
    updateRegisterProductPreview(found ? productImage : '');
    sel.dispatchEvent(new Event('change'));
    checkRegisterReady();
}

// ── Show product image preview in register tab ─────
function updateRegisterProductPreview(productImage) {
    const box = document.getElementById('registerProductPreview');
    const img = document.getElementById('registerProductImg');
    if (!box || !img) return;
    if (productImage) {
        img.src = `${BASE}/${productImage}`;
        box.style.display = 'flex';
    } else {
        box.style.display = 'none';
        img.src = '';
    }
}

// ── Camera ────────────────────────────────────────
async function activateCamera() {
    try {
        stopCamera();
        capturedBlob = null;

        // Reset UI
        document.getElementById('capturedPreview').style.display = 'none';
        document.getElementById('cameraPlaceholder').style.display = 'none';
        document.getElementById('cameraError').style.display = 'none';

        cameraStream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: { ideal: 'environment' } },
            audio: false
        });

        const video = document.getElementById('cameraStream');
        video.srcObject = cameraStream;
        video.style.display = 'block';
        document.getElementById('btnActivateCam').style.display = 'none';
        document.getElementById('btnSnap').style.display = 'flex';
        document.getElementById('btnRetake').style.display = 'none';
        checkRegisterReady();

    } catch (e) {
        // Show friendly error inside the camera area
        const errBox = document.getElementById('cameraError');
        let msg = 'No se pudo acceder a la cámara.';
        if (e.name === 'NotAllowedError')   msg = 'Permiso de cámara denegado. Habilítalo en el navegador.';
        if (e.name === 'NotFoundError')     msg = 'No se encontró una cámara en este dispositivo.';
        if (e.name === 'NotReadableError')  msg = 'La cámara está siendo usada por otra aplicación. Ciérrala e intenta de nuevo.';
        if (e.name === 'OverconstrainedError') msg = 'No se encontró cámara trasera. Intenta de nuevo.';

        errBox.querySelector('.cam-error-msg').textContent = msg;
        errBox.style.display = 'flex';
        document.getElementById('cameraPlaceholder').style.display = 'none';
        document.getElementById('btnActivateCam').style.display = 'flex';
        document.getElementById('btnSnap').style.display = 'none';
    }
}

function snapPhoto() {
    const video  = document.getElementById('cameraStream');
    const canvas = document.getElementById('cameraCanvas');
    canvas.width  = video.videoWidth  || 640;
    canvas.height = video.videoHeight || 480;
    canvas.getContext('2d').drawImage(video, 0, 0);

    canvas.toBlob(blob => {
        capturedBlob = blob;
        const preview = document.getElementById('capturedPreview');
        preview.src = URL.createObjectURL(blob);
        preview.style.display = 'block';
        video.style.display   = 'none';
        document.getElementById('btnSnap').style.display   = 'none';
        document.getElementById('btnRetake').style.display = 'flex';
        stopCamera();
        checkRegisterReady();
    }, 'image/jpeg', 0.92);
}

function retakePhoto() {
    capturedBlob = null;
    document.getElementById('capturedPreview').style.display = 'none';
    document.getElementById('cameraPlaceholder').style.display = 'flex';
    document.getElementById('btnActivateCam').style.display = 'flex';
    document.getElementById('btnRetake').style.display = 'none';
    document.getElementById('btnSnap').style.display   = 'none';
    checkRegisterReady();
}

function stopCamera() {
    if (cameraStream) {
        cameraStream.getTracks().forEach(t => t.stop());
        cameraStream = null;
    }
    const video = document.getElementById('cameraStream');
    if (video) {
        video.srcObject = null;
        video.style.display = 'none';
    }
}

function triggerRegisterFile() {
    document.getElementById('registerFileInput').click();
}

function handleRegisterFile(e) {
    const file = e.target.files[0];
    if (!file) return;
    capturedBlob = file;
    const preview = document.getElementById('capturedPreview');
    preview.src = URL.createObjectURL(file);
    preview.style.display = 'block';
    document.getElementById('cameraPlaceholder').style.display = 'none';
    document.getElementById('cameraStream').style.display      = 'none';
    document.getElementById('btnSnap').style.display           = 'none';
    document.getElementById('btnActivateCam').style.display    = 'flex';
    document.getElementById('btnRetake').style.display         = 'flex';
    stopCamera();
    checkRegisterReady();
    e.target.value = '';
}

function checkRegisterReady() {
    const skuSel = document.getElementById('registerSkuSelect');
    const btn    = document.getElementById('btnRegisterSubmit');
    if (!skuSel || !btn) return;
    btn.disabled = !(skuSel.value && capturedBlob);
}

async function submitRegisterPhoto() {
    const selVal = document.getElementById('registerSkuSelect').value;
    const nota   = document.getElementById('registerNota').value;
    const fb     = document.getElementById('registerFeedback');

    if (!selVal || !capturedBlob) return;

    const isBulk = selVal.startsWith('bulk_');
    const idVal  = isBulk ? selVal.replace('bulk_', '') : selVal;

    const btn = document.getElementById('btnRegisterSubmit');
    btn.disabled = true;
    btn.innerHTML = '<i class="ph ph-spinner ph-spin"></i> Guardando...';
    fb.className = 'register-feedback';
    fb.textContent = '';

    const form = new FormData();
    if (isBulk) {
        form.append('action', 'upload_product_photo');
        form.append('product_id', idVal);
    } else {
        form.append('action', 'upload_sku_photo');
        form.append('sku_id', idVal);
        const statusVal = document.getElementById('registerStatusSelect')?.value;
        if (statusVal) {
            form.append('status', statusVal);
        }
    }
    form.append('nota', nota);
    form.append('photos[]', capturedBlob, 'photo.jpg');

    try {
        const res  = await fetch(`${BASE}/ajax/mochila.php`, { method: 'POST', body: form });
        const data = await res.json();

        if (data.success) {
            fb.className = 'register-feedback success';
            fb.textContent = '✓ Foto guardada correctamente';
            // Reset
            capturedBlob = null;
            document.getElementById('capturedPreview').style.display = 'none';
            document.getElementById('cameraPlaceholder').style.display = 'flex';
            document.getElementById('btnActivateCam').style.display   = 'flex';
            document.getElementById('btnRetake').style.display        = 'none';
            document.getElementById('registerNota').value = '';
            document.getElementById('registerSkuSelect').value = '';
            loadStats();
            // Reload backpack to update photo counts
            setTimeout(() => loadOffCanvasBackpack(currentUserId), 600);
        } else {
            fb.className = 'register-feedback error';
            fb.textContent = data.message || 'Error al guardar';
        }
    } catch (e) {
        fb.className = 'register-feedback error';
        fb.textContent = 'Error de red';
    }

    btn.innerHTML = '<i class="ph ph-cloud-arrow-up"></i> Guardar Foto';
    checkRegisterReady();
}

// ── Lightbox ──────────────────────────────────────
function openLightbox(index) {
    lightboxIndex = index;
    updateLightboxImage();
    document.getElementById('photoLightbox').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeLightbox() {
    document.getElementById('photoLightbox').classList.remove('active');
    document.body.style.overflow = '';
}

function updateLightboxImage() {
    if (!currentSkuPhotos.length) return;
    const photo = currentSkuPhotos[lightboxIndex];
    document.getElementById('lightboxImage').src = `${BASE}/${photo.ruta_archivo}`;
    document.getElementById('lightboxInfo').textContent =
        `${lightboxIndex + 1} / ${currentSkuPhotos.length}${photo.nota ? ' — ' + photo.nota : ''}`;
}

function lightboxPrev() {
    lightboxIndex = (lightboxIndex - 1 + currentSkuPhotos.length) % currentSkuPhotos.length;
    updateLightboxImage();
}

function lightboxNext() {
    lightboxIndex = (lightboxIndex + 1) % currentSkuPhotos.length;
    updateLightboxImage();
}

document.addEventListener('keydown', e => {
    if (!document.getElementById('photoLightbox').classList.contains('active')) return;
    if (e.key === 'Escape')      closeLightbox();
    if (e.key === 'ArrowLeft')   lightboxPrev();
    if (e.key === 'ArrowRight')  lightboxNext();
});

// ── Devolver al almacén ───────────────────────────
async function returnToWarehouse(skuId, skuCode) {
    if (!confirm(`¿Devolver ${skuCode} al almacén?`)) return;

    const form = new FormData();
    form.append('action', 'return_to_warehouse');
    form.append('sku_id', skuId);

    try {
        const res  = await fetch(`${BASE}/ajax/mochila.php`, { method: 'POST', body: form });
        const data = await res.json();
        if (data.success) {
            loadOffCanvasBackpack(currentUserId);
            loadUsers();
            loadStats();
        } else {
            alert(data.message);
        }
    } catch (e) { alert('Error de red'); }
}

function returnBulk(stockId, productName, maxQty) {
    const qty = prompt(`¿Cuánto devolver de "${productName}" al almacén?\nMáximo: ${maxQty}`, maxQty);
    if (qty === null) return;
    const qtyNum = parseFloat(qty);
    if (isNaN(qtyNum) || qtyNum <= 0 || qtyNum > maxQty) { alert('Cantidad inválida'); return; }

    const form = new FormData();
    form.append('action', 'return_to_warehouse');
    form.append('sku_id', `bulk_${stockId}`);
    form.append('quantity', qtyNum);

    fetch(`${BASE}/ajax/mochila.php`, { method: 'POST', body: form })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                loadOffCanvasBackpack(currentUserId);
                loadUsers();
                loadStats();
            } else { alert(data.message); }
        })
        .catch(() => alert('Error de red'));
}

window.updateSkuStatusFromMochila = async function(skuId, newStatus, selectEl) {
    selectEl.disabled = true;
    selectEl.innerHTML = `<option value="">Guardando...</option>`;
    
    const fd = new FormData();
    fd.append('action', 'update_sku_status');
    fd.append('sku_id', skuId);
    fd.append('status', newStatus);

    try {
        const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fd }).then(r => r.json());
        if (res.success) {
            if (window.showToast) window.showToast('Estado actualizado', 'success');
            // Refresh to get correct colors/badges and update stats
            loadOffCanvasBackpack(currentUserId);
            loadStats();
        } else {
            if (window.showToast) window.showToast(res.message, 'error');
            loadOffCanvasBackpack(currentUserId);
        }
    } catch (e) {
        if (window.showToast) window.showToast('Error de conexión', 'error');
        loadOffCanvasBackpack(currentUserId);
    }
};

// ── Reasignar ─────────────────────────────────────
async function reassignSku(skuId, skuCode) {
    const otherUsers = allUsers.filter(u => u.id != currentUserId);
    if (otherUsers.length === 0) { alert('No hay otros usuarios disponibles'); return; }

    const listHtml = otherUsers.map(u => {
        const initials = getInitials(u.name);
        return `
            <div class="reassign-user-option" onclick="doReassign(${skuId}, ${u.id})">
                <div class="user-card-avatar" style="width:32px;height:32px;border-radius:8px;font-size:.7rem;flex-shrink:0;">${initials}</div>
                <div>
                    <div style="font-weight:600;font-size:.85rem;">${escapeHtml(u.name)}</div>
                    <div style="font-size:.7rem;color:var(--text-muted);">${escapeHtml(u.role)} · ${u.total_items} items</div>
                </div>
            </div>
        `;
    }).join('');

    document.getElementById('reassignSkuInfo').textContent = skuCode;
    document.getElementById('reassignUserList').innerHTML   = listHtml;
    document.getElementById('reassignModal').classList.add('active');
}

async function doReassign(skuId, newUserId) {
    const form = new FormData();
    form.append('action', 'reassign_sku');
    form.append('sku_id', skuId);
    form.append('new_user_id', newUserId);

    try {
        const res  = await fetch(`${BASE}/ajax/mochila.php`, { method: 'POST', body: form });
        const data = await res.json();
        if (data.success) {
            closeReassignModal();
            loadOffCanvasBackpack(currentUserId);
            loadUsers();
            loadStats();
        } else { alert(data.message); }
    } catch (e) { alert('Error de red'); }
}

function closeReassignModal() {
    document.getElementById('reassignModal').classList.remove('active');
}

// ── Helpers ───────────────────────────────────────
function getInitials(name) {
    return (name || '?').split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
}

function getRoleIcon(role) {
    const r = (role || '').toLowerCase();
    if (r.includes('admin'))   return 'ph ph-shield-check';
    if (r.includes('gerente')) return 'ph ph-briefcase';
    if (r.includes('tecnico') || r.includes('técnico')) return 'ph ph-wrench';
    return 'ph ph-user';
}

function formatStatus(status) {
    return {
        disponible:  'Disponible',
        instalado:   'Instalado',
        malogrado:   'Malogrado',
        reparado:    'Reparado',
        en_transito: 'En Tránsito'
    }[status] || status;
}

function formatDate(dateStr) {
    if (!dateStr) return '—';
    const d = new Date(dateStr);
    if (isNaN(d)) return dateStr;
    return d.toLocaleDateString('es-PE', { day: '2-digit', month: 'short', year: 'numeric' });
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

