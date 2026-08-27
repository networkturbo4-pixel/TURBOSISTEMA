/**
 * ══════════════════════════════════════════════════════════════════
 * HISTORIAL & TRAZABILIDAD DE INVENTARIO - FRONTEND CONTROLLER
 * ══════════════════════════════════════════════════════════════════
 */

(function() {
    'use strict';

    const metaBase = document.querySelector('meta[name="base-url"]');
    const BASE = (metaBase && typeof metaBase.content === 'string') 
        ? metaBase.content 
        : (typeof window.BASE_URL === 'string' ? window.BASE_URL : (window.location.pathname.includes('/TURBOSAAS') ? '/TURBOSAAS' : ''));
    
    let currentProduct = null;
    let currentTimeline = [];
    let searchDebounceTimer = null;
    let cameraStream = null;

    // ── Helper functions ──────────────────────────────────────────
    function appendCsrf(fd) {
        const csrf = window.CSRF_TOKEN || document.querySelector('meta[name="csrf-token"]')?.content || '';
        if (csrf && !fd.has('csrf_token')) {
            fd.append('csrf_token', csrf);
        }
        return fd;
    }

    function esc(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatDate(dateStr) {
        if (!dateStr) return 'N/D';
        const d = new Date(dateStr.replace(/-/g, '/'));
        if (isNaN(d.getTime())) return dateStr;
        const options = { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' };
        return d.toLocaleDateString('es-PE', options);
    }

    function formatDateOnly(dateStr) {
        if (!dateStr) return 'N/D';
        const d = new Date(dateStr.replace(/-/g, '/'));
        if (isNaN(d.getTime())) return dateStr;
        return d.toLocaleDateString('es-PE', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    // ── Document Initialization ──────────────────────────────────
    document.addEventListener('DOMContentLoaded', () => {
        setupSearchInput();
        loadRecentProductsForPills();
        loadProductsForPurchaseSelect();

        // Check URL params (?product_id=X or ?sku=Y)
        const params = new URLSearchParams(window.location.search);
        const urlProdId = params.get('product_id');
        const urlSku = params.get('sku');
        const urlTab = params.get('view');

        if (urlProdId) {
            loadProductHistory(urlProdId);
        } else if (urlSku) {
            loadProductHistory(0, urlSku);
        } else if (urlTab === 'purchases') {
            showAllPurchasesView();
        }

        // Close popover when clicking outside
        document.addEventListener('click', (e) => {
            const searchBox = document.querySelector('.hist-search-box');
            if (searchBox && !searchBox.contains(e.target)) {
                hideSuggestions();
            }
        });
    });

    // ── Spotlight Search & Hardware Scanner ──────────────────────
    function setupSearchInput() {
        const input = document.getElementById('histMainSearch');
        if (!input) return;

        let lastKeyTime = Date.now();

        input.addEventListener('input', (e) => {
            const query = input.value.trim();
            const btnClear = document.getElementById('btnClearSearch');
            if (btnClear) btnClear.style.display = query ? 'flex' : 'none';

            clearTimeout(searchDebounceTimer);
            if (query.length < 1) {
                hideSuggestions();
                return;
            }

            searchDebounceTimer = setTimeout(() => {
                fetchSuggestions(query);
            }, 180);
        });

        // Fast scanner support (Laser Barcode Reader transmits fast input + Enter)
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                const query = input.value.trim();
                if (query) {
                    executeDirectSearch(query);
                }
            }
        });
    }

    window.clearSearch = function() {
        const input = document.getElementById('histMainSearch');
        if (input) {
            input.value = '';
            input.focus();
        }
        const btnClear = document.getElementById('btnClearSearch');
        if (btnClear) btnClear.style.display = 'none';
        hideSuggestions();
    };

    function hideSuggestions() {
        const popover = document.getElementById('histSuggestions');
        if (popover) {
            popover.style.display = 'none';
            popover.innerHTML = '';
        }
    }

    async function fetchSuggestions(query) {
        try {
            const fd = new FormData();
            fd.append('action', 'search_product_history');
            fd.append('query', query);

            const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: appendCsrf(fd) }).then(r => r.json());
            const popover = document.getElementById('histSuggestions');
            if (!popover) return;

            const hasSkus = res.skus && res.skus.length > 0;
            const hasProducts = res.products && res.products.length > 0;
            const hasUsers = res.users && res.users.length > 0;

            if (!res.success || (!hasSkus && !hasProducts && !hasUsers)) {
                popover.innerHTML = `<div style="padding:14px; text-align:center; color:var(--text-muted); font-size:0.85rem;">No se encontraron resultados para "<strong>${esc(query)}</strong>"</div>`;
                popover.style.display = 'block';
                return;
            }

            let html = '';

            // 1. Coincidencias de SKUs específicos (con indicación de si está asignado a un usuario / EPP)
            if (hasSkus) {
                html += `<div class="hist-sugg-group-title"><i class="ph ph-barcode"></i> SKUs / Códigos de Inventario</div>`;
                res.skus.forEach(s => {
                    const statusColors = {
                        disponible: '#10b981',
                        instalado: '#3b82f6',
                        malogrado: '#ef4444',
                        observacion: '#06b6d4'
                    };
                    const stColor = statusColors[s.status] || '#6366f1';
                    const isEpp = (s.is_epp == 1 || (s.category_name && s.category_name.toUpperCase() === 'EPP'));
                    const eppBadge = isEpp ? `<span class="badge-epp"><i class="ph ph-hard-hat"></i> EPP</span>` : '';
                    const assignedBadge = s.assigned_user_name
                        ? `<span class="badge-mochila"><i class="ph ph-user"></i> Asignado a: <strong>${esc(s.assigned_user_name)}</strong></span>`
                        : `<span class="badge-almacen"><i class="ph ph-storefront"></i> En Almacén</span>`;

                    html += `
                        <div class="hist-sugg-item" onclick="selectSuggestion(${s.product_id}, '${s.sku_code}')">
                            <div class="hist-sugg-left">
                                <div class="hist-sugg-icon" style="color:#8b5cf6;"><i class="ph ph-barcode"></i></div>
                                <div>
                                    <div class="hist-sugg-name">
                                        ${esc(s.sku_code)} 
                                        <small style="color:var(--text-muted); font-weight:400;">(${esc(s.product_name)})</small>
                                        ${eppBadge}
                                    </div>
                                    <div class="hist-sugg-sub">
                                        <span>${esc(s.category_name || 'Sin categoría')}</span>
                                        <span style="color:${stColor}; font-weight:700; text-transform:capitalize;">● ${esc(s.status)}</span>
                                        ${assignedBadge}
                                    </div>
                                </div>
                            </div>
                            <div class="hist-sugg-right">
                                <span class="btn btn-secondary btn-sm" style="font-size:0.75rem; padding:3px 8px;">Ver Ficha</span>
                            </div>
                        </div>
                    `;
                });
            }

            // 2. Coincidencias de Productos / EPP
            if (hasProducts) {
                html += `<div class="hist-sugg-group-title"><i class="ph ph-package"></i> Productos & EPP</div>`;
                res.products.forEach(p => {
                    const imgHtml = p.product_image 
                        ? `<img src="${BASE}/${p.product_image}" class="hist-sugg-thumb">`
                        : `<div class="hist-sugg-icon"><i class="ph ph-package"></i></div>`;
                    const isEpp = (p.is_epp == 1 || (p.category_name && p.category_name.toUpperCase() === 'EPP'));
                    const eppBadge = isEpp ? `<span class="badge-epp"><i class="ph ph-hard-hat"></i> EPP</span>` : '';
                    const assignedTotal = (parseInt(p.qty_assigned_skus) || 0) + (parseFloat(p.qty_assigned_bulk) || 0);

                    html += `
                        <div class="hist-sugg-item" onclick="selectSuggestion(${p.product_id})">
                            <div class="hist-sugg-left">
                                ${imgHtml}
                                <div>
                                    <div class="hist-sugg-name">${esc(p.product_name)} ${eppBadge}</div>
                                    <div class="hist-sugg-sub">
                                        <span>${esc(p.category_name || 'Sin categoría')}</span>
                                        <span>Total: <strong>${p.total_quantity}</strong></span>
                                        <span style="color:#10b981;">Disponibles: <strong>${p.qty_disponible || 0}</strong></span>
                                        ${assignedTotal > 0 ? `<span style="color:#a78bfa;">En Personal: <strong>${assignedTotal}</strong></span>` : ''}
                                    </div>
                                </div>
                            </div>
                            <div class="hist-sugg-right">
                                <span class="btn btn-secondary btn-sm" style="font-size:0.75rem; padding:3px 8px;">Consultar</span>
                            </div>
                        </div>
                    `;
                });
            }

            // 3. Coincidencias de Personal / Usuarios
            if (hasUsers) {
                html += `<div class="hist-sugg-group-title"><i class="ph ph-users-three"></i> Personal con EPP / Stock en Posesión</div>`;
                res.users.forEach(u => {
                    const totalHolding = (parseInt(u.sku_count) || 0) + (parseFloat(u.bulk_count) || 0);
                    html += `
                        <div class="hist-sugg-item" onclick="clearSearch(); if (window.showToast) window.showToast('Mostrando personal: ${esc(u.user_name)}', 'info');">
                            <div class="hist-sugg-left">
                                <div class="hist-sugg-icon" style="color:#f59e0b;"><i class="ph ph-hard-hat"></i></div>
                                <div>
                                    <div class="hist-sugg-name">${esc(u.user_name)} <small style="color:var(--text-muted);">(${esc(u.role || 'Personal')})</small></div>
                                    <div class="hist-sugg-sub">
                                        <span>${esc(u.email || '')}</span>
                                        <span class="badge-mochila">En posesión: <strong>${totalHolding} ítems</strong></span>
                                    </div>
                                </div>
                            </div>
                            <div class="hist-sugg-right">
                                <span class="badge-epp" style="font-size:0.75rem;"><i class="ph ph-user-check"></i> Activo</span>
                            </div>
                        </div>
                    `;
                });
            }

            popover.innerHTML = html;
            popover.style.display = 'block';

        } catch (e) {
            console.error(e);
        }
    }

    window.selectSuggestion = function(productId, skuCode = null) {
        hideSuggestions();
        loadProductHistory(productId, skuCode);
    };

    async function executeDirectSearch(query) {
        try {
            const fd = new FormData();
            fd.append('action', 'search_product_history');
            fd.append('query', query);

            const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: appendCsrf(fd) }).then(r => r.json());
            if (res.success) {
                if (res.skus && res.skus.length === 1) {
                    selectSuggestion(res.skus[0].product_id, res.skus[0].sku_code);
                    return;
                }
                if (res.products && res.products.length === 1) {
                    selectSuggestion(res.products[0].product_id);
                    return;
                }
                fetchSuggestions(query);
            }
        } catch (e) {
            console.error(e);
        }
    }

    // ── Load 360° Product History & Metrics ──────────────────────
    window.loadProductHistory = async function(productId, skuCode = null) {
        try {
            const fd = new FormData();
            fd.append('action', 'get_product_history_details');
            if (productId) fd.append('product_id', productId);
            if (skuCode) fd.append('sku_code', skuCode);

            const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: appendCsrf(fd) }).then(r => r.json());
            if (!res.success) {
                if (window.showToast) window.showToast(res.message || 'No se pudo cargar la información del producto', 'error');
                return;
            }

            currentProduct = res.product;
            currentTimeline = res.timeline || [];

            // Update URL without page reload
            const newUrl = new URL(window.location);
            newUrl.searchParams.set('product_id', currentProduct.id);
            if (skuCode) newUrl.searchParams.set('sku', skuCode);
            else newUrl.searchParams.delete('sku');
            newUrl.searchParams.delete('view');
            window.history.pushState({}, '', newUrl);

            // Populate Product Hero Card
            document.getElementById('histProdTitle').textContent = currentProduct.name;
            document.getElementById('histProdCat').innerHTML = `<i class="ph ph-folder"></i> ${esc(currentProduct.category_name || 'Sin categoría')}`;
            document.getElementById('histProdSku').innerHTML = `<i class="ph ph-barcode"></i> SKU Maestro: <strong>${esc(currentProduct.master_sku || 'N/A')}</strong>`;
            document.getElementById('histProdCost').innerHTML = `<i class="ph ph-money"></i> Costo Ref: <strong>S/ ${parseFloat(currentProduct.costo_producto || 0).toFixed(2)}</strong>`;

            // Product Image
            const imgBox = document.getElementById('histProdImgBox');
            if (imgBox) {
                if (currentProduct.product_image) {
                    imgBox.innerHTML = `<img src="${BASE}/${currentProduct.product_image}" alt="${esc(currentProduct.name)}" class="hist-prod-img" onclick="window.openImgViewer && window.openImgViewer('${BASE}/${currentProduct.product_image}', '${esc(currentProduct.name).replace(/'/g, "\\'")}')">`;
                } else if (currentProduct.gallery_photo) {
                    imgBox.innerHTML = `<img src="${BASE}/${currentProduct.gallery_photo}" alt="${esc(currentProduct.name)}" class="hist-prod-img" onclick="window.openImgViewer && window.openImgViewer('${BASE}/${currentProduct.gallery_photo}', '${esc(currentProduct.name).replace(/'/g, "\\'")}')">`;
                } else {
                    imgBox.innerHTML = `<i class="ph ph-package" style="font-size:2.2rem; color:var(--text-muted); opacity:0.6;"></i>`;
                }
            }

            // Badges (Type and EPP)
            const badgesBox = document.getElementById('histProdBadges');
            let badgesHtml = '';
            const pType = currentProduct.product_type || 'normal';
            if (currentProduct.is_epp_category == 1 || (currentProduct.category_name && currentProduct.category_name.toUpperCase() === 'EPP')) {
                badgesHtml += `<span class="badge-epp" style="padding:4px 10px; font-size:0.8rem;"><i class="ph ph-hard-hat"></i> Equipo de Protección Personal (EPP)</span>`;
            }
            if (pType === 'agrupado') {
                badgesHtml += `<span class="agrupado-badge"><i class="ph ph-stack"></i> Producto Agrupado</span>`;
            } else if (pType === 'bundle') {
                badgesHtml += `<span class="bundle-badge"><i class="ph ph-squares-four"></i> Pack / Bundle</span>`;
            } else if (pType === 'granel' || currentProduct.is_bulk == 1) {
                badgesHtml += `<span class="granel-badge"><i class="ph ph-scales"></i> A Granel (${esc(currentProduct.unit_type || 'Und')})</span>`;
            } else {
                badgesHtml += `<span class="cat-badge" style="background:rgba(99,102,241,0.1); color:#6366f1; border-color:rgba(99,102,241,0.25);"><i class="ph ph-cube"></i> Producto Individual</span>`;
            }
            badgesBox.innerHTML = badgesHtml;

            // Active Scanned / Selected SKU Banner (Who is holding it)
            const skuBanner = document.getElementById('histActiveSkuBanner');
            if (skuBanner) {
                if (res.active_sku) {
                    const asku = res.active_sku;
                    const isAssigned = asku.assigned_to && asku.assigned_to_name;
                    const assignedHtml = isAssigned
                        ? `<span class="hist-sku-banner-user"><i class="ph ph-user-check"></i> Asignado a: <strong>${esc(asku.assigned_to_name)}</strong> <small style="opacity:0.8;">(${esc(asku.assigned_to_role || 'Personal')})</small></span>
                           ${asku.assigned_date ? `<span style="font-size:0.75rem; color:var(--text-muted);"><i class="ph ph-calendar"></i> Entregado: ${formatDate(asku.assigned_date)}</span>` : ''}`
                        : `<span class="badge-almacen"><i class="ph ph-storefront"></i> Disponible en Almacén Central</span>`;

                    skuBanner.innerHTML = `
                        <div class="hist-sku-banner-left">
                            <span class="hist-sku-banner-tag"><i class="ph ph-barcode"></i> SKU: ${esc(asku.sku_code)}</span>
                            <span class="hist-sku-banner-status">Estado: <strong style="text-transform:capitalize;">${esc(asku.status)}</strong></span>
                            ${assignedHtml}
                        </div>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="switchProductTab('tech')" style="font-size:0.75rem; padding:3px 8px;">
                            <i class="ph ph-hard-hat"></i> Ver Personal & EPP
                        </button>
                    `;
                    skuBanner.style.display = 'flex';
                } else {
                    skuBanner.style.display = 'none';
                    skuBanner.innerHTML = '';
                }
            }

            // Dates
            document.getElementById('histDateCreated').textContent = formatDate(currentProduct.created_at);
            document.getElementById('histDateActivity').textContent = formatDate(res.last_activity);

            // Metrics
            document.getElementById('metricTotal').textContent = res.metrics.total;
            document.getElementById('metricDisp').textContent = res.metrics.disponible;
            document.getElementById('metricInst').textContent = res.metrics.instalado;
            document.getElementById('metricMalo').textContent = res.metrics.malogrado;
            document.getElementById('metricObs').textContent = res.metrics.observacion;
            document.getElementById('metricTech').textContent = res.metrics.asignado_tecnicos;

            document.getElementById('prodPurchasesCount').textContent = (res.purchases || []).length;
            document.getElementById('prodTechCount').textContent = (res.technicians || []).length;

            // Render Timeline & Tab Data
            renderTimeline(currentTimeline);
            renderProductPurchases(res.purchases || []);
            renderTechniciansTable(res.technicians || []);

            // Installations
            const installations = currentTimeline.filter(t => t.type === 'installation' || t.type === 'material_use');
            renderInstallationsTable(installations);

            // Toggle visibility
            document.getElementById('histEmptyPlaceholder').style.display = 'none';
            document.getElementById('histAllPurchasesView').style.display = 'none';
            document.getElementById('histProductView').style.display = 'flex';

            // Scroll smoothly to product card
            document.getElementById('histProductView').scrollIntoView({ behavior: 'smooth', block: 'start' });

        } catch (e) {
            console.error(e);
        }
    };

    // ── Tab Switcher for Product View ────────────────────────────
    window.switchProductTab = function(tabName) {
        document.querySelectorAll('.hist-tab-btn').forEach(b => {
            b.classList.toggle('active', b.dataset.ptab === tabName);
        });
        document.querySelectorAll('.hist-tab-pane').forEach(p => {
            p.classList.toggle('active', p.id === `ptab-${tabName}`);
        });
    };

    // ── Timeline Renderer & Filter ───────────────────────────────
    function renderTimeline(items) {
        const wrap = document.getElementById('histTimelineWrap');
        if (!wrap) return;

        if (!items || items.length === 0) {
            wrap.innerHTML = `<div style="text-align:center; padding:30px; color:var(--text-muted);">No hay movimientos registrados para este producto.</div>`;
            return;
        }

        wrap.innerHTML = items.map(item => {
            return `
                <div class="hist-tl-item" data-event-type="${item.type}">
                    <div class="hist-tl-node ${item.badge_class}">
                        <i class="ph ${item.icon}"></i>
                    </div>
                    <div class="hist-tl-card">
                        <div class="hist-tl-header">
                            <span class="hist-tl-title">${esc(item.title)}</span>
                            <span class="hist-tl-time">${formatDate(item.date)}</span>
                        </div>
                        <p class="hist-tl-desc">${item.description}</p>
                        ${item.details ? `<div style="font-size:0.76rem; color:var(--text-muted); margin-bottom:4px;">${esc(item.details)}</div>` : ''}
                        <div class="hist-tl-footer">
                            <span><i class="ph ph-user"></i> Registrado por: <strong>${esc(item.user)}</strong></span>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    window.filterTimeline = function(type) {
        document.querySelectorAll('.tl-filter-chip').forEach(c => {
            c.classList.toggle('active', c.dataset.filter === type);
        });

        if (type === 'all') {
            renderTimeline(currentTimeline);
        } else {
            const filtered = currentTimeline.filter(t => t.type === type);
            renderTimeline(filtered);
        }
    };

    // ── Product Purchases Table ──────────────────────────────────
    function renderProductPurchases(purchases) {
        const tbody = document.getElementById('prodPurchasesTableBody');
        if (!tbody) return;

        if (!purchases || purchases.length === 0) {
            tbody.innerHTML = `<tr><td colspan="9" class="text-center" style="padding:24px; color:var(--text-muted);">No hay compras registradas para este producto.</td></tr>`;
            return;
        }

        tbody.innerHTML = purchases.map(p => {
            const curSym = p.currency === 'USD' ? '$' : 'S/';
            const docBtn = p.document_path
                ? `<button type="button" class="btn btn-secondary btn-sm" onclick="viewDoc('${BASE}/${p.document_path}', 'Factura: ${esc(p.invoice_number || 'S/N')}', '${p.document_type}')" title="Ver comprobante"><i class="ph ph-file-text" style="color:#6366f1;"></i> Ver Factura</button>`
                : `<span style="color:var(--text-muted); font-size:0.75rem;">Sin adjunto</span>`;

            return `
                <tr>
                    <td><strong>${formatDate(p.purchase_date)}</strong></td>
                    <td>${esc(p.supplier_name || 'N/D')}</td>
                    <td><span class="cat-badge">${esc(p.invoice_number || 'S/N')}</span></td>
                    <td class="text-center"><span class="inv-metric-pill pill-total">${p.quantity}</span></td>
                    <td class="text-end">${curSym} ${parseFloat(p.unit_price || 0).toFixed(2)}</td>
                    <td class="text-end" style="font-weight:700; color:#10b981;">${curSym} ${parseFloat(p.total_amount || 0).toFixed(2)}</td>
                    <td class="text-center">${docBtn}</td>
                    <td>${esc(p.user_name || 'Admin')}</td>
                    <td class="text-center">
                        <button class="inv-act-btn act-del" onclick="deletePurchase(${p.id})" title="Eliminar registro"><i class="ph ph-trash"></i></button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    // ── Installations & Technicians Table ────────────────────────
    function renderInstallationsTable(installations) {
        const tbody = document.getElementById('prodInstTableBody');
        if (!tbody) return;

        if (!installations || installations.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center" style="padding:24px; color:var(--text-muted);">No hay registros de instalaciones en actas de clientes.</td></tr>`;
            return;
        }

        tbody.innerHTML = installations.map(inst => `
            <tr>
                <td><strong>${formatDate(inst.date)}</strong></td>
                <td><span class="cat-badge" style="background:rgba(59,130,246,0.1); color:#3b82f6; border-color:rgba(59,130,246,0.3);">${esc(inst.title)}</span></td>
                <td>${inst.description}</td>
                <td>${esc(inst.details || '-')}</td>
                <td>-</td>
                <td>${esc(inst.user)}</td>
            </tr>
        `).join('');
    }

    function renderTechniciansTable(techs) {
        const tbody = document.getElementById('prodTechTableBody');
        if (!tbody) return;

        if (!techs || techs.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center" style="padding:24px; color:var(--text-muted);">No hay stock ni EPP asignado a personal en este momento.</td></tr>`;
            return;
        }

        tbody.innerHTML = techs.map(t => {
            const isEpp = (t.is_epp == 1 || t.is_epp_badge == 1);
            const typeBadge = isEpp
                ? `<span class="badge-epp"><i class="ph ph-hard-hat"></i> EPP Personal</span>`
                : `<span class="badge-mochila"><i class="ph ph-backpack"></i> Mochila de Trabajo</span>`;

            return `
                <tr>
                    <td>
                        <div style="font-weight:700; color:var(--text-color);">${esc(t.name)}</div>
                        <div style="font-size:0.75rem; color:var(--text-muted);">${esc(t.email || '-')}</div>
                    </td>
                    <td><span class="cat-badge">${esc(t.role || 'Técnico')}</span></td>
                    <td class="text-center">${typeBadge}</td>
                    <td class="text-center"><span class="inv-metric-pill pill-inst">${t.count || t.quantity || 0}</span></td>
                    <td style="max-width:300px; overflow:hidden; text-overflow:ellipsis;">
                        ${t.skus ? `<span style="font-size:0.8rem; font-family:monospace; color:var(--text-color);">${esc(t.skus)}</span>` : `<span style="color:var(--text-muted); font-size:0.8rem;">A Granel (${esc(currentProduct ? currentProduct.unit_type : 'Und')})</span>`}
                    </td>
                </tr>
            `;
        }).join('');
    }

    // ── Global Purchases Log View ────────────────────────────────
    window.showAllPurchasesView = function() {
        document.getElementById('histEmptyPlaceholder').style.display = 'none';
        document.getElementById('histProductView').style.display = 'none';
        document.getElementById('histAllPurchasesView').style.display = 'flex';
        loadAllPurchases();
    };

    window.backToProductSearch = function() {
        document.getElementById('histAllPurchasesView').style.display = 'none';
        if (currentProduct) {
            document.getElementById('histProductView').style.display = 'flex';
        } else {
            document.getElementById('histEmptyPlaceholder').style.display = 'flex';
        }
    };

    window.loadAllPurchases = async function() {
        try {
            const search = document.getElementById('filterPurchasesSearch')?.value || '';
            const dateFrom = document.getElementById('filterPurchasesDateFrom')?.value || '';
            const dateTo = document.getElementById('filterPurchasesDateTo')?.value || '';

            const fd = new FormData();
            fd.append('action', 'list_purchases');
            if (search) fd.append('search', search);
            if (dateFrom) fd.append('date_from', dateFrom);
            if (dateTo) fd.append('date_to', dateTo);

            const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: appendCsrf(fd) }).then(r => r.json());
            const tbody = document.getElementById('allPurchasesTableBody');
            if (!tbody) return;

            if (!res.success || !res.data || res.data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="11" class="text-center" style="padding:30px; color:var(--text-muted);">No se encontraron compras con los filtros seleccionados.</td></tr>`;
                document.getElementById('kpiPurchasesCount').textContent = '0';
                document.getElementById('kpiPurchasesAmount').textContent = 'S/ 0.00';
                return;
            }

            document.getElementById('kpiPurchasesCount').textContent = res.total_count || res.data.length;
            document.getElementById('kpiPurchasesAmount').textContent = 'S/ ' + parseFloat(res.total_amount || 0).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            tbody.innerHTML = res.data.map(p => {
                const curSym = p.currency === 'USD' ? '$' : 'S/';
                const docBtn = p.document_path
                    ? `<button type="button" class="btn btn-secondary btn-sm" onclick="viewDoc('${BASE}/${p.document_path}', 'Factura: ${esc(p.invoice_number || 'S/N')}', '${p.document_type}')" title="Ver comprobante"><i class="ph ph-file-text" style="color:#6366f1;"></i> Ver</button>`
                    : `<span style="color:var(--text-muted); font-size:0.75rem;">Sin adjunto</span>`;

                return `
                    <tr>
                        <td><strong>${formatDate(p.purchase_date)}</strong></td>
                        <td>
                            <a href="javascript:void(0)" onclick="loadProductHistory(${p.product_id})" style="font-weight:700; color:var(--text-color); text-decoration:underline;">
                                ${esc(p.product_name)}
                            </a>
                        </td>
                        <td><span class="cat-badge">${esc(p.category_name || 'Sin cat.')}</span></td>
                        <td>${esc(p.supplier_name || 'N/D')}</td>
                        <td><span class="cat-badge">${esc(p.invoice_number || 'S/N')}</span></td>
                        <td class="text-center"><span class="inv-metric-pill pill-total">${p.quantity}</span></td>
                        <td class="text-end">${curSym} ${parseFloat(p.unit_price || 0).toFixed(2)}</td>
                        <td class="text-end" style="font-weight:700; color:#10b981;">${curSym} ${parseFloat(p.total_amount || 0).toFixed(2)}</td>
                        <td class="text-center">${docBtn}</td>
                        <td>${esc(p.user_name || 'Admin')}</td>
                        <td class="text-center">
                            <button class="inv-act-btn act-del" onclick="deletePurchase(${p.id})" title="Eliminar registro"><i class="ph ph-trash"></i></button>
                        </td>
                    </tr>
                `;
            }).join('');

        } catch (e) {
            console.error(e);
        }
    };

    window.clearPurchasesFilters = function() {
        document.getElementById('filterPurchasesSearch').value = '';
        document.getElementById('filterPurchasesDateFrom').value = '';
        document.getElementById('filterPurchasesDateTo').value = '';
        loadAllPurchases();
    };

    // ── New Purchase Modal & Submission ──────────────────────────
    async function loadProductsForPurchaseSelect() {
        try {
            const fd = new FormData();
            fd.append('action', 'list_products');
            const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: appendCsrf(fd) }).then(r => r.json());
            if (res.success && res.data) {
                const sel = document.getElementById('purchaseProductId');
                if (!sel) return;
                sel.innerHTML = '<option value="">-- Selecciona el producto --</option>' + 
                    res.data.map(p => `<option value="${p.id}">${esc(p.name)} (${esc(p.category_name || 'Sin cat.')})</option>`).join('');
            }
        } catch (e) {
            console.error(e);
        }
    }

    window.openNewPurchaseModal = function(productId = null) {
        const modal = document.getElementById('modalNewPurchase');
        if (!modal) return;

        // Set default datetime to now
        const now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        document.getElementById('purchaseDate').value = now.toISOString().slice(0, 16);

        if (productId) {
            document.getElementById('purchaseProductId').value = productId;
        } else if (currentProduct) {
            document.getElementById('purchaseProductId').value = currentProduct.id;
        }

        modal.classList.add('active');
    };

    window.openNewPurchaseModalForCurrentProduct = function() {
        if (currentProduct) {
            openNewPurchaseModal(currentProduct.id);
        } else {
            openNewPurchaseModal();
        }
    };

    window.closeNewPurchaseModal = function() {
        const modal = document.getElementById('modalNewPurchase');
        if (modal) modal.classList.remove('active');
        document.getElementById('formNewPurchase').reset();
        removeInvoiceFile();
    };

    window.calcPurchaseTotal = function() {
        const qty = parseFloat(document.getElementById('purchaseQty').value) || 0;
        const unit = parseFloat(document.getElementById('purchaseUnitPrice').value) || 0;
        if (qty > 0 && unit > 0) {
            document.getElementById('purchaseTotal').value = (qty * unit).toFixed(2);
        }
    };

    window.previewInvoiceFile = function(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            document.getElementById('dropzonePrompt').style.display = 'none';
            document.getElementById('dropzonePreview').style.display = 'flex';
            document.getElementById('previewFileName').textContent = file.name;

            const isPdf = file.name.toLowerCase().endsWith('.pdf');
            document.getElementById('previewIcon').className = isPdf ? 'ph ph-file-pdf text-danger' : 'ph ph-file-image text-primary';
        }
    };

    window.removeInvoiceFile = function() {
        const fileInput = document.getElementById('purchaseFile');
        if (fileInput) fileInput.value = '';
        document.getElementById('dropzonePrompt').style.display = 'flex';
        document.getElementById('dropzonePreview').style.display = 'none';
    };

    window.submitNewPurchase = async function(e) {
        e.preventDefault();
        const btn = document.getElementById('btnSubmitPurchase');
        btn.disabled = true;
        btn.innerHTML = `<i class="ph ph-spinner ph-spin"></i> Guardando...`;

        try {
            const form = document.getElementById('formNewPurchase');
            const fd = new FormData(form);
            fd.append('action', 'create_purchase');

            const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: appendCsrf(fd) }).then(r => r.json());
            if (res.success) {
                if (window.showToast) window.showToast(res.message || 'Compra registrada exitosamente', 'success');
                closeNewPurchaseModal();

                // Reload current view
                if (currentProduct && currentProduct.id == fd.get('product_id')) {
                    loadProductHistory(currentProduct.id);
                }
                loadAllPurchases();
            } else {
                if (window.showToast) window.showToast(res.message || 'Error al registrar la compra', 'error');
            }
        } catch (err) {
            console.error(err);
            if (window.showToast) window.showToast('Error de conexión al servidor', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = `<i class="ph ph-floppy-disk"></i> Guardar Compra`;
        }
    };

    window.deletePurchase = async function(purchaseId) {
        if (!confirm('¿Estás seguro de eliminar este registro de compra?')) return;

        try {
            const fd = new FormData();
            fd.append('action', 'delete_purchase');
            fd.append('purchase_id', purchaseId);

            const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: appendCsrf(fd) }).then(r => r.json());
            if (res.success) {
                if (window.showToast) window.showToast('Compra eliminada correctamente', 'success');
                if (currentProduct) loadProductHistory(currentProduct.id);
                loadAllPurchases();
            } else {
                if (window.showToast) window.showToast(res.message || 'Error al eliminar', 'error');
            }
        } catch (e) {
            console.error(e);
        }
    };

    // ── Document Viewer Modal ────────────────────────────────────
    window.viewDoc = function(url, title, type) {
        const modal = document.getElementById('modalDocViewer');
        const iframe = document.getElementById('docViewerIframe');
        const img = document.getElementById('docViewerImg');
        const titleEl = document.getElementById('docViewerTitle');
        const dlBtn = document.getElementById('btnDownloadDoc');

        if (!modal) return;
        titleEl.textContent = title || 'Comprobante';
        dlBtn.href = url;

        if (type === 'pdf' || url.toLowerCase().endsWith('.pdf')) {
            iframe.src = url;
            iframe.style.display = 'block';
            img.style.display = 'none';
        } else {
            img.src = url;
            img.style.display = 'block';
            iframe.style.display = 'none';
        }

        modal.classList.add('active');
    };

    window.closeDocViewer = function() {
        const modal = document.getElementById('modalDocViewer');
        if (modal) modal.classList.remove('active');
        document.getElementById('docViewerIframe').src = '';
        document.getElementById('docViewerImg').src = '';
    };

    // ── Quick Recent Products Pills on Placeholder ──────────────
    async function loadRecentProductsForPills() {
        try {
            const fd = new FormData();
            fd.append('action', 'list_products');
            const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: appendCsrf(fd) }).then(r => r.json());
            if (res.success && res.data) {
                const pillsBox = document.getElementById('histQuickPills');
                if (!pillsBox) return;
                pillsBox.innerHTML = res.data.slice(0, 6).map(p => `
                    <button type="button" class="hist-quick-pill" onclick="loadProductHistory(${p.id})">
                        <i class="ph ph-cube"></i> ${esc(p.name)}
                    </button>
                `).join('');
            }
        } catch (e) {
            console.error(e);
        }
    }

    // ── Camera Scanner Modal ─────────────────────────────────────
    window.openCameraScannerModal = async function() {
        const modal = document.getElementById('modalCameraScanner');
        const video = document.getElementById('cameraScannerVideo');
        if (!modal || !video) return;

        modal.classList.add('active');

        try {
            cameraStream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'environment' }
            });
            video.srcObject = cameraStream;
            video.play();
        } catch (err) {
            console.warn("Camera not available or access denied:", err);
            if (window.showToast) window.showToast('No se pudo acceder a la cámara del dispositivo', 'warning');
        }
    };

    window.closeCameraScannerModal = function() {
        const modal = document.getElementById('modalCameraScanner');
        if (modal) modal.classList.remove('active');

        if (cameraStream) {
            cameraStream.getTracks().forEach(track => track.stop());
            cameraStream = null;
        }
    };

})();
