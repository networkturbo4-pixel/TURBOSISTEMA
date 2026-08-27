/**
 * ══════════════════════════════════════════════════════════════════
 * TURBOSAAS - GLOBAL COMMAND PALETTE CONTROLLER (CTRL + K)
 * ══════════════════════════════════════════════════════════════════
 */

(function() {
    'use strict';

    const getBaseUrl = () => {
        const meta = document.querySelector('meta[name="base-url"]');
        if (meta && typeof meta.content === 'string') return meta.content;
        if (typeof window.BASE_URL === 'string') return window.BASE_URL;
        return window.location.pathname.includes('/TURBOSAAS') ? '/TURBOSAAS' : '';
    };

    const BASE = getBaseUrl();
    let currentFilter = 'all';
    let searchDebounceTimer = null;
    let selectedIndex = 0;
    let currentItems = [];
    let cameraStream = null;
    let cachedUsers = [];

    // Helper de escape
    function esc(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function appendCsrf(formData) {
        const csrf = window.CSRF_TOKEN || document.querySelector('meta[name="csrf-token"]')?.content || '';
        if (csrf && !formData.has('csrf_token')) {
            formData.append('csrf_token', csrf);
        }
        return formData;
    }

    // ── Inyección del DOM de la Command Palette ──────────────────────
    function injectPaletteMarkup() {
        if (document.getElementById('commandPaletteModal')) return;

        const isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
        const shortcutKey = isMac ? '⌘K' : 'Ctrl+K';

        const wrapper = document.createElement('div');
        wrapper.id = 'commandPaletteModal';
        wrapper.className = 'cp-backdrop';
        wrapper.innerHTML = `
            <div class="cp-dialog" id="commandPaletteDialog" onclick="event.stopPropagation()">
                
                <!-- Main Search View -->
                <div id="cpMainView">
                    <div class="cp-search-header">
                        <i class="ph-bold ph-magnifying-glass cp-search-icon"></i>
                        <input type="text" id="cpSearchInput" class="cp-search-input" placeholder="Buscar productos, SKUs, técnicos, activos o escribir comando..." autocomplete="off">
                        <div class="cp-header-badges">
                            <span class="cp-kbd-pill">${shortcutKey}</span>
                            <button type="button" class="cp-btn-close" onclick="closeCommandPalette()" title="Cerrar (Esc)">&times;</button>
                        </div>
                    </div>

                    <div class="cp-filter-chips">
                        <button type="button" class="cp-chip active" data-filter="all" onclick="setCpFilter('all', this)">
                            <i class="ph-bold ph-squares-four"></i> Todo
                        </button>
                        <button type="button" class="cp-chip" data-filter="products" onclick="setCpFilter('products', this)">
                            <i class="ph-bold ph-package"></i> Productos
                        </button>
                        <button type="button" class="cp-chip" data-filter="skus" onclick="setCpFilter('skus', this)">
                            <i class="ph-bold ph-barcode"></i> SKUs / Series
                        </button>
                        <button type="button" class="cp-chip" data-filter="users" onclick="setCpFilter('users', this)">
                            <i class="ph-bold ph-users"></i> Técnicos
                        </button>
                        <button type="button" class="cp-chip" data-filter="activos" onclick="setCpFilter('activos', this)">
                            <i class="ph-bold ph-car-profile"></i> Activos
                        </button>
                        <button type="button" class="cp-chip" data-filter="commands" onclick="setCpFilter('commands', this)">
                            <i class="ph-bold ph-terminal-window"></i> Comandos
                        </button>
                    </div>

                    <div class="cp-results-body" id="cpResultsBody">
                        <div class="cp-empty-state">
                            <i class="ph-bold ph-keyboard"></i>
                            <p>Escribe para buscar productos, series, técnicos o ejecutar acciones...</p>
                        </div>
                    </div>

                    <div class="cp-footer-bar">
                        <div class="cp-shortcuts-list">
                            <span class="cp-sc-item"><span class="cp-kbd-pill">↑↓</span> Navegar</span>
                            <span class="cp-sc-item"><span class="cp-kbd-pill">↵</span> Seleccionar / Abrir</span>
                            <span class="cp-sc-item"><span class="cp-kbd-pill">Esc</span> Cerrar</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span style="font-size:0.72rem; color:var(--text-muted);"><i class="ph ph-lightning" style="color:#f59e0b;"></i> Turbo Command</span>
                        </div>
                    </div>
                </div>

                <!-- Subview: Asignación Rápida -->
                <div id="cpAssignView" class="cp-subview">
                    <div class="cp-subview-header">
                        <div class="cp-subview-title">
                            <i class="ph-bold ph-user-plus" style="color:#3b82f6;"></i>
                            <span id="cpAssignTitle">Asignar a Técnico / Usuario</span>
                        </div>
                        <button type="button" class="cp-btn-close" onclick="backToMainView()">&times;</button>
                    </div>

                    <form id="cpAssignForm" onsubmit="submitCpAssign(event)">
                        <input type="hidden" id="cpAssignProductId" value="">
                        <input type="hidden" id="cpAssignSkuId" value="">
                        <input type="hidden" id="cpAssignSkuCode" value="">

                        <div class="cp-form-group mb-3">
                            <label class="cp-form-label">Elemento a Asignar</label>
                            <div id="cpAssignItemDisplay" style="padding:10px 14px; background:rgba(255,255,255,0.05); border:1px solid var(--border-color, #223046); border-radius:10px; font-weight:700; color:var(--text-color, #f8fafc); display:flex; align-items:center; gap:8px;">
                                <i class="ph-bold ph-package"></i> <span id="cpAssignItemName">Seleccionando...</span>
                            </div>
                        </div>

                        <div id="cpStockDetailPanel" style="display:none; margin-bottom:14px;">
                            <div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:10px;">
                                <div class="cp-stock-pill cp-stock-green">
                                    <i class="ph-bold ph-check-circle"></i>
                                    <span id="cpStockDisp">0</span> Disponibles
                                </div>
                                <div class="cp-stock-pill cp-stock-blue">
                                    <i class="ph-bold ph-user"></i>
                                    <span id="cpStockAsig">0</span> Asignados
                                </div>
                                <div class="cp-stock-pill cp-stock-yellow">
                                    <i class="ph-bold ph-plug"></i>
                                    <span id="cpStockInst">0</span> Instalados
                                </div>
                                <div class="cp-stock-pill cp-stock-red">
                                    <i class="ph-bold ph-warning"></i>
                                    <span id="cpStockMalo">0</span> Malogrados
                                </div>
                                <div class="cp-stock-pill cp-stock-neutral">
                                    <i class="ph-bold ph-stack"></i>
                                    Total: <span id="cpStockTotal">0</span>
                                </div>
                            </div>
                            <div id="cpStockByUser" style="max-height:140px; overflow-y:auto;"></div>
                        </div>

                        <div class="cp-form-group mb-3">
                            <label class="cp-form-label">Técnico / Usuario de Destino <span style="color:#ef4444;">*</span></label>
                            <select id="cpAssignUserId" class="cp-form-control" required>
                                <option value="">-- Selecciona el técnico --</option>
                            </select>
                        </div>

                        <div class="row g-2 mb-3" id="cpAssignQtyRow">
                            <div class="col-md-6">
                                <label class="cp-form-label">Cantidad a Asignar</label>
                                <input type="number" id="cpAssignQuantity" class="cp-form-control" min="1" step="1" value="1">
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <div style="padding:9px 12px; background:rgba(255,255,255,0.03); border:1px solid var(--border-color, #223046); border-radius:10px; width:100%;">
                                    <label style="cursor:pointer; display:flex; align-items:center; gap:8px; font-size:0.85rem; margin:0; font-weight:600;">
                                        <input type="checkbox" id="cpAssignIsEpp" value="1">
                                        <span>Asignar como EPP</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="cp-form-group mb-3">
                            <label class="cp-form-label">Notas u Observaciones (Opcional)</label>
                            <input type="text" id="cpAssignNotes" class="cp-form-control" placeholder="Ej: Entrega para instalación cliente nuevo...">
                        </div>

                        <div class="cp-subview-footer">
                            <button type="button" class="btn btn-secondary btn-sm" onclick="backToMainView()">Volver</button>
                            <button type="submit" class="btn btn-primary btn-sm" id="btnSubmitCpAssign" style="background:linear-gradient(135deg, #3b82f6, #2563eb); border:none; padding:8px 18px; font-weight:700;">
                                <i class="ph ph-check-circle"></i> Confirmar Asignación
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Subview: Escáner con Cámara -->
                <div id="cpScannerView" class="cp-subview">
                    <div class="cp-subview-header">
                        <div class="cp-subview-title">
                            <i class="ph-bold ph-barcode" style="color:#8b5cf6;"></i>
                            <span>Escanear Código / Serie</span>
                        </div>
                        <button type="button" class="cp-btn-close" onclick="backToMainView()">&times;</button>
                    </div>
                    <div style="padding:15px; text-align:center;">
                        <div style="position:relative; width:100%; max-width:380px; height:240px; margin:0 auto; background:#000; border-radius:14px; overflow:hidden; display:flex; align-items:center; justify-content:center;">
                            <video id="cpScannerVideo" playsinline style="width:100%; height:100%; object-fit:cover;"></video>
                            <div style="position:absolute; left:10%; right:10%; height:2px; background:linear-gradient(90deg, transparent, #8b5cf6, transparent); box-shadow:0 0 8px #8b5cf6; animation:scannerLaser 1.5s infinite alternate ease-in-out;"></div>
                        </div>
                        <p style="font-size:0.85rem; color:var(--text-muted); margin-top:12px;">Apunta la cámara al código QR o código de barras, o utiliza tu lector láser directo.</p>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="backToMainView()">Cancelar Escaneo</button>
                    </div>
                </div>

            </div>
        `;

        document.body.appendChild(wrapper);

        wrapper.addEventListener('click', (e) => {
            if (e.target === wrapper) closeCommandPalette();
        });

        setupPaletteSearch();
        preloadUsers();
    }

    // ── Pre-cargar lista de técnicos ────────────────────────────────
    async function preloadUsers() {
        try {
            const res = await fetch(`${BASE}/ajax/command_palette.php?action=get_users`).then(r => r.json());
            if (res.success && res.data) {
                cachedUsers = res.data;
                const sel = document.getElementById('cpAssignUserId');
                if (sel) {
                    sel.innerHTML = '<option value="">-- Selecciona el técnico --</option>' +
                        cachedUsers.map(u => `<option value="${u.id}">${esc(u.name)} (${esc(u.role)})</option>`).join('');
                }
            }
        } catch (e) {}
    }

    // ── Atajos Globales de Teclado ──────────────────────────────────
    document.addEventListener('keydown', (e) => {
        // Ctrl+K o Cmd+K
        if ((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K')) {
            e.preventDefault();
            toggleCommandPalette();
            return;
        }

        const modal = document.getElementById('commandPaletteModal');
        if (!modal || !modal.classList.contains('active')) return;

        if (e.key === 'Escape') {
            e.preventDefault();
            const assignView = document.getElementById('cpAssignView');
            const scanView = document.getElementById('cpScannerView');
            if (assignView && assignView.classList.contains('active')) {
                backToMainView();
            } else if (scanView && scanView.classList.contains('active')) {
                backToMainView();
            } else {
                closeCommandPalette();
            }
            return;
        }

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            navigateItems(1);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            navigateItems(-1);
        } else if (e.key === 'Enter') {
            const activeSub = document.querySelector('.cp-subview.active');
            if (activeSub) return; // Permitir submit regular en subviews

            e.preventDefault();
            executeSelectedItem();
        }
    });

    // ── Abrir / Cerrar / Toggle ─────────────────────────────────────
    window.openCommandPalette = function(initialQuery = '') {
        injectPaletteMarkup();
        const modal = document.getElementById('commandPaletteModal');
        if (!modal) return;

        modal.classList.add('active');
        backToMainView();

        const input = document.getElementById('cpSearchInput');
        if (input) {
            input.value = initialQuery;
            setTimeout(() => input.focus(), 50);
            performSearch(initialQuery);
        }
    };

    window.closeCommandPalette = function() {
        const modal = document.getElementById('commandPaletteModal');
        if (modal) modal.classList.remove('active');
        stopCamera();
    };

    window.toggleCommandPalette = function() {
        const modal = document.getElementById('commandPaletteModal');
        if (modal && modal.classList.contains('active')) {
            closeCommandPalette();
        } else {
            openCommandPalette();
        }
    };

    // ── Filtros por chips ───────────────────────────────────────────
    window.setCpFilter = function(filter, btn) {
        currentFilter = filter;
        document.querySelectorAll('.cp-chip').forEach(c => c.classList.remove('active'));
        if (btn) btn.classList.add('active');
        const input = document.getElementById('cpSearchInput');
        performSearch(input ? input.value : '');
    };

    // ── Eventos del Buscador Central ────────────────────────────────
    function setupPaletteSearch() {
        const input = document.getElementById('cpSearchInput');
        if (!input) return;

        input.addEventListener('input', () => {
            clearTimeout(searchDebounceTimer);
            searchDebounceTimer = setTimeout(() => {
                performSearch(input.value);
            }, 120);
        });
    }

    // ── Búsqueda AJAX y Renderizado de Resultados ───────────────────
    async function performSearch(query) {
        const resultsBody = document.getElementById('cpResultsBody');
        if (!resultsBody) return;

        try {
            const res = await fetch(`${BASE}/ajax/command_palette.php?action=search_all&q=${encodeURIComponent(query)}`)
                .then(r => r.json());

            if (!res.success) return;

            renderResults(res.data, query);
        } catch (e) {
            console.error(e);
        }
    }

    function renderResults(data, query) {
        const resultsBody = document.getElementById('cpResultsBody');
        if (!resultsBody) return;

        currentItems = [];
        let html = '';

        const showCmds = (currentFilter === 'all' || currentFilter === 'commands') && data.commands && data.commands.length > 0;
        const showProds = (currentFilter === 'all' || currentFilter === 'products') && data.products && data.products.length > 0;
        const showSkus = (currentFilter === 'all' || currentFilter === 'skus') && data.skus && data.skus.length > 0;
        const showUsers = (currentFilter === 'all' || currentFilter === 'users') && data.users && data.users.length > 0;
        const showActivos = (currentFilter === 'all' || currentFilter === 'activos') && data.activos && data.activos.length > 0;

        if (!showCmds && !showProds && !showSkus && !showUsers && !showActivos) {
            resultsBody.innerHTML = `
                <div class="cp-empty-state">
                    <i class="ph-bold ph-magnifying-glass"></i>
                    <p>No se encontraron resultados para "<strong>${esc(query)}</strong>"</p>
                </div>
            `;
            return;
        }

        // 1. Acciones y Comandos
        if (showCmds) {
            html += `<div class="cp-group-title"><i class="ph-bold ph-terminal-window"></i> Acciones & Comandos</div>`;
            data.commands.forEach(cmd => {
                const idx = currentItems.length;
                currentItems.push({ type: 'command', data: cmd });
                html += `
                    <div class="cp-item ${idx === 0 ? 'selected' : ''}" data-idx="${idx}" onclick="handleItemClick(${idx})">
                        <div class="cp-item-left">
                            <div class="cp-item-icon"><i class="ph-bold ${cmd.icon}"></i></div>
                            <div class="cp-item-info">
                                <div class="cp-item-title">${esc(cmd.title)}</div>
                                <div class="cp-item-sub">${esc(cmd.category)}</div>
                            </div>
                        </div>
                        <div class="cp-item-right">
                            <span class="cp-item-badge badge-primary">${esc(cmd.badge || 'Acción')}</span>
                        </div>
                    </div>
                `;
            });
        }

        // 2. Productos
        if (showProds) {
            html += `<div class="cp-group-title"><i class="ph-bold ph-package"></i> Productos de Inventario</div>`;
            data.products.forEach(p => {
                const idx = currentItems.length;
                currentItems.push({ type: 'product', data: p });
                const thumb = p.product_image 
                    ? `<img src="${BASE}/${p.product_image}" class="cp-item-thumb" alt="${esc(p.name)}">`
                    : `<div class="cp-item-icon"><i class="ph-bold ph-cube"></i></div>`;

                const disp = p.skus_disponibles !== undefined ? p.skus_disponibles : (p.total_quantity || 0);
                const badgeClass = disp > 0 ? 'badge-success' : 'badge-danger';
                const typeLabel = p.product_type === 'agrupado' ? ' <span style="color:#8b5cf6;font-size:0.72rem;">⊕ Agrupado</span>' : 
                                  (p.is_bulk == 1 ? ' <span style="color:#f59e0b;font-size:0.72rem;">⊟ Granel</span>' : '');

                html += `
                    <div class="cp-item ${idx === 0 ? 'selected' : ''}" data-idx="${idx}" onclick="handleItemClick(${idx})">
                        <div class="cp-item-left">
                            ${thumb}
                            <div class="cp-item-info">
                                <div class="cp-item-title">${esc(p.name)}${typeLabel}</div>
                                <div class="cp-item-sub">
                                    <span>${esc(p.category_name || 'Sin Cat.')}</span>
                                    ${p.master_sku ? `<span>• SKU: ${esc(p.master_sku)}</span>` : ''}
                                    ${p.costo_producto > 0 ? `<span>• S/ ${parseFloat(p.costo_producto).toFixed(2)}</span>` : ''}
                                </div>
                            </div>
                        </div>
                        <div class="cp-item-right">
                            <div class="cp-item-actions">
                                <button type="button" class="cp-btn-action-small" onclick="event.stopPropagation(); openQuickAssignProduct(${p.id}, '${esc(p.name).replace(/'/g, "\\'")}')" title="Asignar a técnico">
                                    <i class="ph-bold ph-user-plus"></i> Asignar
                                </button>
                                <button type="button" class="cp-btn-action-small" onclick="event.stopPropagation(); cpEditProduct(${p.id})" title="Editar producto">
                                    <i class="ph-bold ph-pencil-simple"></i> Editar
                                </button>
                                <button type="button" class="cp-btn-action-small" onclick="event.stopPropagation(); cpAddStock(${p.id}, '${esc(p.name).replace(/'/g, "\\'")}')" title="Agregar stock">
                                    <i class="ph-bold ph-plus-circle"></i> Stock
                                </button>
                                <button type="button" class="cp-btn-action-small" onclick="event.stopPropagation(); openProductHistory(${p.id})" title="Ver trazabilidad 360°">
                                    <i class="ph-bold ph-clock-counter-clockwise"></i>
                                </button>
                                <button type="button" class="cp-btn-action-small" style="color:#ef4444;" onclick="event.stopPropagation(); cpDeleteProduct(${p.id}, '${esc(p.name).replace(/'/g, "\\'")}')" title="Eliminar producto">
                                    <i class="ph-bold ph-trash"></i>
                                </button>
                            </div>
                            <span class="cp-item-badge ${badgeClass}">${disp} Disp.</span>
                        </div>
                    </div>
                `;
            });
        }

        // 3. SKUs / Series
        if (showSkus) {
            html += `<div class="cp-group-title"><i class="ph-bold ph-barcode"></i> Series / SKUs Específicos</div>`;
            data.skus.forEach(s => {
                const idx = currentItems.length;
                currentItems.push({ type: 'sku', data: s });
                const st = (s.status || 'disponible').toLowerCase();
                const stClass = st === 'disponible' ? 'badge-success' : (st === 'instalado' ? 'badge-warning' : 'badge-danger');

                html += `
                    <div class="cp-item ${idx === 0 ? 'selected' : ''}" data-idx="${idx}" onclick="handleItemClick(${idx})">
                        <div class="cp-item-left">
                            <div class="cp-item-icon" style="color:#8b5cf6;"><i class="ph-bold ph-barcode"></i></div>
                            <div class="cp-item-info">
                                <div class="cp-item-title">${esc(s.sku_code)} <span style="font-weight:400; color:var(--text-muted); font-size:0.82rem;">(${esc(s.product_name)})</span></div>
                                <div class="cp-item-sub">
                                    ${s.assigned_user_name ? `<span style="color:#38bdf8;"><i class="ph-bold ph-user"></i> En poder de: ${esc(s.assigned_user_name)}</span>` : '<span style="color:#10b981;">En Almacén Central</span>'}
                                    ${s.is_epp == 1 ? '<span class="cp-item-badge" style="background:rgba(240,125,0,0.15); color:#f07d00;">EPP</span>' : ''}
                                </div>
                            </div>
                        </div>
                        <div class="cp-item-right">
                            <div class="cp-item-actions">
                                <button type="button" class="cp-btn-action-small" onclick="event.stopPropagation(); openQuickAssignSku(${s.id}, '${esc(s.sku_code)}', '${esc(s.product_name).replace(/'/g, "\\'")}')" title="Asignar / Reasignar">
                                    <i class="ph-bold ph-user-plus"></i> Asignar
                                </button>
                                <button type="button" class="cp-btn-action-small" onclick="event.stopPropagation(); openProductHistory(${s.product_id}, '${esc(s.sku_code)}')" title="Ver trazabilidad 360°">
                                    <i class="ph-bold ph-clock-counter-clockwise"></i> Historial
                                </button>
                            </div>
                            <span class="cp-item-badge ${stClass}">${esc(s.status || 'Disponible')}</span>
                        </div>
                    </div>
                `;
            });
        }

        // 4. Usuarios / Técnicos
        if (showUsers) {
            html += `<div class="cp-group-title"><i class="ph-bold ph-users"></i> Técnicos & Usuarios</div>`;
            data.users.forEach(u => {
                const idx = currentItems.length;
                currentItems.push({ type: 'user', data: u });
                const thumb = u.profile_picture 
                    ? `<img src="${BASE}/${u.profile_picture}" class="cp-item-thumb" style="border-radius:50%;" alt="${esc(u.name)}">`
                    : `<div class="cp-item-icon" style="border-radius:50%; color:#38bdf8;"><i class="ph-bold ph-user"></i></div>`;

                html += `
                    <div class="cp-item ${idx === 0 ? 'selected' : ''}" data-idx="${idx}" onclick="handleItemClick(${idx})">
                        <div class="cp-item-left">
                            ${thumb}
                            <div class="cp-item-info">
                                <div class="cp-item-title">${esc(u.name)}</div>
                                <div class="cp-item-sub">
                                    <span>${esc(u.role)}</span>
                                    <span>• ${esc(u.email)}</span>
                                    <span>• ${u.total_skus_asignados || 0} materiales en mochila</span>
                                </div>
                            </div>
                        </div>
                        <div class="cp-item-right">
                            <button type="button" class="cp-btn-action-small" onclick="event.stopPropagation(); openAssignToUserDirect(${u.id}, '${esc(u.name).replace(/'/g, "\\'")}')" title="Asignar materiales a este técnico">
                                <i class="ph-bold ph-plus"></i> Asignar Material
                            </button>
                            <span class="cp-item-badge">${esc(u.role)}</span>
                        </div>
                    </div>
                `;
            });
        }

        // 5. Activos Empresariales
        if (showActivos) {
            html += `<div class="cp-group-title"><i class="ph-bold ph-car-profile"></i> Activos Empresariales & Vehículos</div>`;
            data.activos.forEach(a => {
                const idx = currentItems.length;
                currentItems.push({ type: 'activo', data: a });
                const disp = a.nombre || `${a.marca || ''} ${a.modelo || ''}`.trim() || `Activo #${a.id}`;
                const pl = a.codigo_identificador || a.placa || 'S/C';

                html += `
                    <div class="cp-item ${idx === 0 ? 'selected' : ''}" data-idx="${idx}" onclick="handleItemClick(${idx})">
                        <div class="cp-item-left">
                            <div class="cp-item-icon" style="color:#f07d00;"><i class="ph-bold ph-car"></i></div>
                            <div class="cp-item-info">
                                <div class="cp-item-title">${esc(disp)} <span style="font-weight:800; color:#ff9800; font-family:monospace; font-size:0.82rem;">[${esc(pl)}]</span></div>
                                <div class="cp-item-sub">
                                    <span>${esc(a.tipo || a.categoria || 'Vehículo')}</span>
                                    ${a.responsable_nombre ? `<span>• Resp: ${esc(a.responsable_nombre)}</span>` : ''}
                                </div>
                            </div>
                        </div>
                        <div class="cp-item-right">
                            <span class="cp-item-badge badge-primary">${esc(a.estado || 'Activo')}</span>
                        </div>
                    </div>
                `;
            });
        }

        resultsBody.innerHTML = html;
        selectedIndex = 0;
    }

    // ── Navegación con flechas del teclado ──────────────────────────
    function navigateItems(direction) {
        const items = document.querySelectorAll('.cp-item');
        if (!items || items.length === 0) return;

        items.forEach(el => el.classList.remove('selected'));
        selectedIndex += direction;

        if (selectedIndex < 0) selectedIndex = items.length - 1;
        if (selectedIndex >= items.length) selectedIndex = 0;

        const target = items[selectedIndex];
        if (target) {
            target.classList.add('selected');
            target.scrollIntoView({ block: 'nearest' });
        }
    }

    function executeSelectedItem() {
        if (currentItems && currentItems[selectedIndex]) {
            handleItemClick(selectedIndex);
        }
    }

    // ── Ejecución de la acción según tipo ───────────────────────────
    window.handleItemClick = function(index) {
        const item = currentItems[index];
        if (!item) return;

        if (item.type === 'command') {
            const cmd = item.data;
            if (cmd.action_type === 'navigate' && cmd.url) {
                window.location.href = cmd.url;
            } else if (cmd.action_type === 'trigger_assign') {
                openQuickAssignModalGeneral();
            } else if (cmd.action_type === 'trigger_scan') {
                openScannerView();
            }
            return;
        }

        if (item.type === 'product') {
            openProductHistory(item.data.id);
            return;
        }

        if (item.type === 'sku') {
            openProductHistory(item.data.product_id, item.data.sku_code);
            return;
        }

        if (item.type === 'user') {
            openAssignToUserDirect(item.data.id, item.data.name);
            return;
        }

        if (item.type === 'activo') {
            window.location.href = `${BASE}/modules/inventario/Activos/?id=${item.data.id}`;
            return;
        }
    };

    // ── Subview: Asignación Rápida ──────────────────────────────────
    window.openQuickAssignProduct = async function(productId, productName) {
        document.getElementById('cpMainView').style.display = 'none';
        document.getElementById('cpScannerView').classList.remove('active');
        const assignView = document.getElementById('cpAssignView');
        assignView.classList.add('active');

        document.getElementById('cpAssignTitle').textContent = 'Asignar Producto a Técnico';
        document.getElementById('cpAssignProductId').value = productId;
        document.getElementById('cpAssignSkuId').value = '';
        document.getElementById('cpAssignSkuCode').value = '';
        document.getElementById('cpAssignItemName').textContent = productName;
        document.getElementById('cpAssignQtyRow').style.display = 'flex';
        document.getElementById('cpAssignQuantity').value = '1';
        document.getElementById('cpAssignIsEpp').checked = false;
        document.getElementById('cpAssignNotes').value = '';

        // Reset & show stock panel
        const stockPanel = document.getElementById('cpStockDetailPanel');
        const byUserContainer = document.getElementById('cpStockByUser');
        if (stockPanel) stockPanel.style.display = 'block';
        if (byUserContainer) byUserContainer.innerHTML = '<div style="font-size:0.78rem; color:var(--text-muted); padding:4px 0;"><i class="ph ph-spinner ph-spin"></i> Cargando disponibilidad...</div>';

        try {
            const res = await fetch(`${BASE}/ajax/command_palette.php?action=get_product_stock_detail&product_id=${productId}`)
                .then(r => r.json());

            if (res.success) {
                const c = res.counts || {};
                const disp = c.disponibles !== undefined ? c.disponibles : (res.product?.total_quantity || 0);
                const asig = c.asignados !== undefined ? c.asignados : 0;
                const inst = c.instalados !== undefined ? c.instalados : 0;
                const malo = c.malogrados !== undefined ? c.malogrados : 0;
                const total = c.total !== undefined ? c.total : (parseInt(disp) + parseInt(asig));

                document.getElementById('cpStockDisp').textContent = disp;
                document.getElementById('cpStockAsig').textContent = asig;
                document.getElementById('cpStockInst').textContent = inst;
                document.getElementById('cpStockMalo').textContent = malo;
                document.getElementById('cpStockTotal').textContent = total;

                // Renderizar quiénes los tienen asignados / en uso
                if (res.by_user && res.by_user.length > 0) {
                    let userHtml = `<div class="cp-tech-list-title"><i class="ph-bold ph-users-three"></i> En poder de los siguientes técnicos:</div>`;
                    res.by_user.forEach(u => {
                        const avatar = u.profile_picture 
                            ? `<img src="${BASE}/${u.profile_picture}" class="tech-avatar" alt="${esc(u.user_name)}">`
                            : `<div style="width:24px;height:24px;border-radius:50%;background:rgba(59,130,246,0.2);color:#60a5fa;display:flex;align-items:center;justify-content:center;font-size:0.75rem;"><i class="ph-bold ph-user"></i></div>`;

                        userHtml += `
                            <div class="cp-tech-assigned-card">
                                <div class="tech-info">
                                    ${avatar}
                                    <div>
                                        <strong style="color:var(--text-color, #f8fafc);">${esc(u.user_name)}</strong>
                                        ${u.skus && u.skus !== 'Granel' ? `<div style="font-size:0.72rem; color:var(--text-muted);">${esc(u.skus)}</div>` : ''}
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="tech-badge">${u.cantidad} asignado(s)</span>
                                    <button type="button" class="cp-btn-action-small" onclick="selectTechnicianInAssign(${u.user_id})" title="Seleccionar para asignar más">
                                        <i class="ph-bold ph-plus"></i> Más
                                    </button>
                                </div>
                            </div>
                        `;
                    });
                    if (byUserContainer) byUserContainer.innerHTML = userHtml;
                } else {
                    if (byUserContainer) byUserContainer.innerHTML = `<div style="font-size:0.78rem; color:var(--text-muted); padding:4px 0;"><i class="ph-bold ph-check"></i> Ningún técnico tiene este producto asignado actualmente. Todo está en almacén central.</div>`;
                }
            }
        } catch (err) {
            console.error('Error fetching stock detail:', err);
            if (byUserContainer) byUserContainer.innerHTML = '';
        }
    };

    window.selectTechnicianInAssign = function(userId) {
        const sel = document.getElementById('cpAssignUserId');
        if (sel) {
            sel.value = userId;
            sel.focus();
        }
    };

    window.openQuickAssignSku = function(skuId, skuCode, productName) {
        document.getElementById('cpMainView').style.display = 'none';
        document.getElementById('cpScannerView').classList.remove('active');
        const assignView = document.getElementById('cpAssignView');
        assignView.classList.add('active');

        document.getElementById('cpAssignTitle').textContent = 'Asignar SKU / Serie a Técnico';
        document.getElementById('cpAssignProductId').value = '';
        document.getElementById('cpAssignSkuId').value = skuId;
        document.getElementById('cpAssignSkuCode').value = skuCode;
        document.getElementById('cpAssignItemName').textContent = `SKU: ${skuCode} (${productName})`;
        document.getElementById('cpAssignQtyRow').style.display = 'none';
        document.getElementById('cpAssignIsEpp').checked = false;
        document.getElementById('cpAssignNotes').value = '';

        const stockPanel = document.getElementById('cpStockDetailPanel');
        if (stockPanel) stockPanel.style.display = 'none';
    };

    window.openAssignToUserDirect = function(userId, userName) {
        openQuickAssignModalGeneral(userId, userName);
    };

    window.openQuickAssignModalGeneral = async function(presetUserId = null, presetUserName = '') {
        document.getElementById('cpMainView').style.display = 'none';
        const assignView = document.getElementById('cpAssignView');
        assignView.classList.add('active');

        document.getElementById('cpAssignTitle').textContent = presetUserName ? `Asignar a ${presetUserName}` : 'Asignación Rápida a Técnico';
        document.getElementById('cpAssignItemName').textContent = 'Selecciona o escribe el SKU/Producto';
        document.getElementById('cpAssignQtyRow').style.display = 'flex';

        const stockPanel = document.getElementById('cpStockDetailPanel');
        if (stockPanel) stockPanel.style.display = 'none';

        if (presetUserId) {
            document.getElementById('cpAssignUserId').value = presetUserId;
        }
    };

    window.backToMainView = function() {
        document.getElementById('cpMainView').style.display = 'block';
        const assignView = document.getElementById('cpAssignView');
        const scanView = document.getElementById('cpScannerView');
        if (assignView) assignView.classList.remove('active');
        if (scanView) scanView.classList.remove('active');
        stopCamera();

        const stockPanel = document.getElementById('cpStockDetailPanel');
        if (stockPanel) stockPanel.style.display = 'none';

        const input = document.getElementById('cpSearchInput');
        if (input) input.focus();
    };

    window.submitCpAssign = async function(e) {
        e.preventDefault();
        const btn = document.getElementById('btnSubmitCpAssign');
        btn.disabled = true;
        btn.innerHTML = `<i class="ph ph-spinner ph-spin"></i> Asignando...`;

        try {
            const formData = new FormData();
            formData.append('action', 'quick_assign');
            formData.append('user_id', document.getElementById('cpAssignUserId').value);
            formData.append('product_id', document.getElementById('cpAssignProductId').value);
            formData.append('sku_id', document.getElementById('cpAssignSkuId').value);
            formData.append('sku_code', document.getElementById('cpAssignSkuCode').value);
            formData.append('quantity', document.getElementById('cpAssignQuantity').value);
            if (document.getElementById('cpAssignIsEpp').checked) {
                formData.append('is_epp', '1');
            }
            formData.append('notes', document.getElementById('cpAssignNotes').value);
            appendCsrf(formData);

            const res = await fetch(`${BASE}/ajax/command_palette.php`, {
                method: 'POST',
                body: formData
            }).then(r => r.json());

            if (res.success) {
                if (window.showToast) {
                    window.showToast(res.message || 'Asignación realizada con éxito', 'success');
                } else {
                    alert(res.message || 'Asignación realizada con éxito');
                }
                closeCommandPalette();
                if (typeof window.loadStock === 'function') window.loadStock();
                if (typeof window.loadProducts === 'function') window.loadProducts();
            } else {
                alert('Error al asignar: ' + (res.message || 'Error'));
            }
        } catch (err) {
            console.error(err);
            alert('Error de conexión al asignar');
        } finally {
            btn.disabled = false;
            btn.innerHTML = `<i class="ph ph-check-circle"></i> Confirmar Asignación`;
        }
    };
    // ── Acciones Rápidas de Productos desde Command Palette ────────
    window.cpEditProduct = function(productId) {
        closeCommandPalette();
        // Navegar al inventario con el modal de edición abierto
        window.location.href = `${BASE}/modules/inventario/?edit_product=${productId}`;
    };

    window.cpDeleteProduct = function(productId, productName) {
        if (!confirm(`¿Eliminar el producto "${productName}"? Esta acción no se puede deshacer.`)) return;
        closeCommandPalette();

        const fd = new FormData();
        fd.append('action', 'delete_product');
        fd.append('product_id', productId);
        const csrf = window.CSRF_TOKEN || document.querySelector('meta[name="csrf-token"]')?.content || '';
        if (csrf) fd.append('csrf_token', csrf);

        fetch(`${BASE}/ajax/inventario.php`, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    if (window.showToast) window.showToast(res.message || 'Producto eliminado', 'success');
                    if (window.loadProducts) window.loadProducts();
                    if (window.loadMetrics) window.loadMetrics();
                } else {
                    alert('Error: ' + (res.message || 'No se pudo eliminar'));
                }
            })
            .catch(err => { console.error(err); alert('Error de conexión'); });
    };

    window.cpAddStock = function(productId, productName) {
        const qty = prompt(`¿Cuántas unidades agregar al stock de "${productName}"?`, '1');
        if (!qty || isNaN(qty) || parseInt(qty) <= 0) return;
        closeCommandPalette();

        const fd = new FormData();
        fd.append('action', 'add_stock');
        fd.append('product_id', productId);
        fd.append('quantity', parseInt(qty));
        const csrf = window.CSRF_TOKEN || document.querySelector('meta[name="csrf-token"]')?.content || '';
        if (csrf) fd.append('csrf_token', csrf);

        fetch(`${BASE}/ajax/inventario.php`, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    if (window.showToast) window.showToast(res.message || `Stock actualizado +${qty}`, 'success');
                    if (window.loadProducts) window.loadProducts();
                    if (window.loadMetrics) window.loadMetrics();
                } else {
                    alert('Error: ' + (res.message || 'No se pudo actualizar'));
                }
            })
            .catch(err => { console.error(err); alert('Error de conexión'); });
    };

    // ── Redirección a Historial 360° ────────────────────────────────
    window.openProductHistory = function(productId, skuCode = null) {
        closeCommandPalette();
        let url = `${BASE}/modules/inventario/historial/?product_id=${productId}`;
        if (skuCode) url += `&sku=${encodeURIComponent(skuCode)}`;
        window.location.href = url;
    };

    // ── Subview: Escáner ────────────────────────────────────────────
    window.openScannerView = async function() {
        document.getElementById('cpMainView').style.display = 'none';
        document.getElementById('cpAssignView').classList.remove('active');
        const scanView = document.getElementById('cpScannerView');
        scanView.classList.add('active');

        const video = document.getElementById('cpScannerVideo');
        if (!video) return;

        try {
            cameraStream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'environment' }
            });
            video.srcObject = cameraStream;
            video.play();
        } catch (err) {
            console.warn('No se pudo acceder a la cámara:', err);
        }
    };

    function stopCamera() {
        if (cameraStream) {
            cameraStream.getTracks().forEach(t => t.stop());
            cameraStream = null;
        }
    }

    // Inicializar cuando cargue el DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', injectPaletteMarkup);
    } else {
        injectPaletteMarkup();
    }

})();
