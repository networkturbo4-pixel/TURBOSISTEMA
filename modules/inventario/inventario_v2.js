/* INVENTARIO MODULE — JavaScript */
(function() {
    const BASE = document.querySelector('meta[name="base-url"]')?.content || (function() { const s = document.querySelector('script[src*="inventario"]'); return s ? s.src.split('/modules/')[0] : ''; })();
    let customColumns = [];
    let previewCustomData = []; // [{col: value, ...}, ...] per SKU row
    let previewSkuCodes = [];
    let skuMode = 'auto'; // 'auto' or 'manual'

    // ── Custom confirm modal (replaces native confirm()) ──
    function invConfirm(title, msg) {
        return new Promise((resolve) => {
            const overlay = document.createElement('div');
            overlay.className = 'inv-confirm-overlay';
            overlay.innerHTML = `<div class="inv-confirm-box">
                <div class="confirm-icon"><i class="ph ph-warning-circle"></i></div>
                <div class="confirm-title">${title}</div>
                <div class="confirm-msg">${msg}</div>
                <div class="inv-confirm-btns">
                    <button class="btn-cancel" id="invConfirmNo">Cancelar</button>
                    <button class="btn-danger" id="invConfirmYes">Confirmar</button>
                </div>
            </div>`;
            document.body.appendChild(overlay);
            overlay.querySelector('#invConfirmYes').onclick = () => { overlay.remove(); resolve(true); };
            overlay.querySelector('#invConfirmNo').onclick = () => { overlay.remove(); resolve(false); };
            overlay.addEventListener('click', (e) => { if (e.target === overlay) { overlay.remove(); resolve(false); } });
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        // Lightbox FIRST — must always register its click handler
        try { initLightbox(); } catch(e) { console.error('initLightbox error:', e); }
        // Data loading
        try { loadMetrics(); } catch(e) { console.error('loadMetrics error:', e); }
        try { loadProducts(); } catch(e) { console.error('loadProducts error:', e); }
        try { loadCategories(); } catch(e) { console.error('loadCategories error:', e); }
        // UI init
        try { initTabs(); } catch(e) { console.error('initTabs error:', e); }
        try { initScanner(); } catch(e) { console.error('initScanner error:', e); }
        try { initProductModal(); } catch(e) { console.error('initProductModal error:', e); }
        try { initStockTab(); } catch(e) { console.error('initStockTab error:', e); }
        try { initLabelsTab(); } catch(e) { console.error('initLabelsTab error:', e); }
    });

    // ── Helper: Random Code ──
    function randomCode(length = 6) {
        const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        let result = '';
        for (let i = 0; i < length; i++) result += chars.charAt(Math.floor(Math.random() * chars.length));
        return result;
    }
    window.randomCode = randomCode;

    // ── Helper: Escape HTML ──
    function esc(str) {
        if (str == null) return '';
        return String(str).replace(/[&<>'"]/g, tag => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'
        }[tag]));
    }

    // ── Tabs ──
    function initTabs() {
        // Seleccionar tabs principales (cualquier nivel dentro de inv-toolbar-tabs)
        document.querySelectorAll('.inv-toolbar-tabs .inv-tab[data-tab]').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.inv-toolbar-tabs .inv-tab[data-tab]').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.inv-tab-pane').forEach(p => p.classList.remove('active'));
                btn.classList.add('active');
                document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
                document.getElementById('btnNewProduct').style.display = btn.dataset.tab === 'productos' ? 'flex' : 'none';
                if (btn.dataset.tab === 'stock') loadAllSkus();
                if (btn.dataset.tab === 'etiquetas') populateLabelProducts();
            });
        });
    }

    // ── Metrics ──
    async function loadMetrics() {
        try {
            const fd = new FormData(); fd.append('action', 'get_stock_summary');
            const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fd }).then(r => r.json());
            if (res.success) {
                document.getElementById('metricTotal').textContent = res.data.total;
                document.getElementById('metricDisponible').textContent = res.data.disponible;
                document.getElementById('metricInstalado').textContent = res.data.instalado;
                document.getElementById('metricLowStock').textContent = res.data.low_stock;
            }
        } catch (e) { console.error(e); }
    }

    // ── Scanner ──
    function initScanner() {
        const input = document.getElementById('scannerInput');
        let t;
        input.addEventListener('input', () => { clearTimeout(t); t = setTimeout(() => { if (input.value.trim().length >= 3) searchSku(input.value.trim()); }, 400); });
        input.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); searchSku(input.value.trim()); } });
        document.getElementById('btnScanCamera').addEventListener('click', () => {
            openSysBarcodeScanner(decoded => {
                document.getElementById('scannerInput').value = decoded;
                searchSku(decoded);
                if (window.showToast) window.showToast('Código escaneado: ' + decoded, 'success'); 
            });
        });
    }

    // ══════════════════════════════════════════════
    // ── Image Viewer / Lightbox ──────────────────
    // ══════════════════════════════════════════════
    let _lbScale = 1, _lbDragging = false, _lbDragStart = {x:0,y:0}, _lbTranslate = {x:0,y:0};

    window.openImgViewer = function(src, caption) {
        const lb   = document.getElementById('invLightbox');
        const img  = document.getElementById('invLbImg');
        const cap  = document.getElementById('invLbCaption');
        const dl   = document.getElementById('invLbDownload');
        if (!lb || !img) { console.error('Lightbox elements not found'); return; }
        lbResetZoom();
        img.src = src;
        if (cap) cap.textContent = caption || '';
        if (dl) { dl.href = src; dl.download = src.split('/').pop(); }
        lb.classList.add('lb-open');
        lb.style.display = 'flex';
        lb.style.visibility = 'visible';
        lb.style.opacity = '1';
        document.body.style.overflow = 'hidden';
    };

    window.closeLightbox = function() {
        const lb = document.getElementById('invLightbox');
        if (lb) {
            lb.classList.remove('lb-open');
            lb.style.display = 'none';
            lb.style.visibility = 'hidden';
            lb.style.opacity = '0';
        }
        document.body.style.overflow = '';
        const img = document.getElementById('invLbImg');
        if (img) img.src = '';
    };

    window.lbZoom = function(delta) {
        _lbScale = Math.min(5, Math.max(0.3, _lbScale + delta));
        document.getElementById('invLbImg').style.transform =
            `translate(${_lbTranslate.x}px,${_lbTranslate.y}px) scale(${_lbScale})`;
    };

    window.lbResetZoom = function() {
        _lbScale = 1; _lbTranslate = {x:0, y:0};
        const img = document.getElementById('invLbImg');
        if (img) img.style.transform = '';
    };

    // ── Lightbox DOM init (called from DOMContentLoaded) ──
    function initLightbox() {
        // ── Delegated click (capture phase): fires BEFORE any child stopPropagation ──
        document.addEventListener('click', e => {
            const el = e.target.closest('[data-lb-src]');
            if (!el) return;
            e.stopPropagation();
            e.preventDefault();
            openImgViewer(el.dataset.lbSrc, el.dataset.lbCaption || '');
        }, true); // <-- capture phase: runs top-down before bubbling handlers

        const wrap = document.getElementById('invLbImgWrap');
        if (!wrap) return;

        // ── Drag to pan ──
        wrap.addEventListener('mousedown', e => {
            if (e.button !== 0) return;
            _lbDragging = true;
            _lbDragStart = { x: e.clientX - _lbTranslate.x, y: e.clientY - _lbTranslate.y };
            wrap.style.cursor = 'grabbing';
        });
        document.addEventListener('mousemove', e => {
            if (!_lbDragging) return;
            _lbTranslate = { x: e.clientX - _lbDragStart.x, y: e.clientY - _lbDragStart.y };
            document.getElementById('invLbImg').style.transform =
                `translate(${_lbTranslate.x}px,${_lbTranslate.y}px) scale(${_lbScale})`;
        });
        document.addEventListener('mouseup', () => {
            _lbDragging = false;
            wrap.style.cursor = 'grab';
        });
        wrap.addEventListener('wheel', e => {
            e.preventDefault();
            lbZoom(e.deltaY < 0 ? 0.15 : -0.15);
        }, { passive: false });

        // ── Keyboard shortcuts ──
        document.addEventListener('keydown', e => {
            const lb = document.getElementById('invLightbox');
            if (!lb || !lb.classList.contains('lb-open')) return;
            if (e.key === 'Escape') closeLightbox();
            if (e.key === '+' || e.key === '=') lbZoom(0.2);
            if (e.key === '-') lbZoom(-0.2);
            if (e.key === '0') lbResetZoom();
        });
    }

    async function searchSku(code) {
        const r = document.getElementById('scannerResult');
        if (!code) { r.style.display = 'none'; return; }
        try {
            const fd = new FormData(); fd.append('action', 'search_sku'); fd.append('code', code);
            const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fd }).then(x => x.json());
            if (res.success) {
                const d = res.data;
                const status = d.status || 'disponible';
                const c = { disponible:'#10b981', instalado:'#3b82f6', malogrado:'#ef4444', reparado:'#f59e0b', en_transito:'#8b5cf6' };
                const ic = { disponible:'ph-check-circle', instalado:'ph-arrow-circle-up', malogrado:'ph-x-circle', reparado:'ph-wrench', en_transito:'ph-truck' };
                const color = c[status] || '#6366f1';
                const icon = ic[status] || 'ph-package';
                r.innerHTML = `<div class="sr-icon" style="background:${color}20;color:${color};"><i class="ph ${icon}"></i></div><div class="sr-info"><h4>${esc(d.sku_code)} — ${esc(d.product_name)}</h4><p>Categoría: ${esc(d.category_name||'Sin categoría')} · <span class="status-badge status-${esc(status)}">${esc(status).toUpperCase()}</span></p></div>`;
                r.style.display = 'none';
                openSkuDetailModal(d);
            } else {
                r.innerHTML = `<div class="sr-icon" style="background:#fef2f2;color:#ef4444;"><i class="ph ph-x-circle"></i></div><div class="sr-info"><h4>No encontrado</h4><p>El código "${esc(code)}" no existe.</p></div>`;
                r.style.display = 'flex';
            }
        } catch (e) { 
            console.error(e);
            r.innerHTML = `<div style="color:red; padding:10px; font-size:12px;">Error: ${e.message}</div>`;
            r.style.display = 'flex';
        }
    }


    // ── Products ──
    let prodCurrentPage = 1;
    window.selectedProducts = new Set();
    
    function updateProdActionBar() {
        let bar = document.getElementById('prodActiveActions');
        let count = document.getElementById('prodSelectedCount');
        let checkAll = document.getElementById('prodCheckAll');
        if (window.selectedProducts.size > 0) {
            if(bar) bar.style.display = 'flex';
            if(count) count.textContent = window.selectedProducts.size;
        } else {
            if(bar) bar.style.display = 'none';
            if(count) count.textContent = '0';
            if(checkAll) checkAll.checked = false;
        }
    }

    window.toggleProductSelection = function(cb, id) {
        if (cb.checked) {
            window.selectedProducts.add(id);
            cb.closest('tr').classList.add('row-selected');
        } else {
            window.selectedProducts.delete(id);
            cb.closest('tr').classList.remove('row-selected');
        }
        updateProdActionBar();
    };

    window.toggleAllProducts = function(cb) {
        document.querySelectorAll('.prod-row-check').forEach(c => {
            const tr = c.closest('tr');
            if (tr && tr.style.display !== 'none') {
                c.checked = cb.checked;
                const pid = parseInt(c.value);
                if (cb.checked) {
                    window.selectedProducts.add(pid);
                    tr.classList.add('row-selected');
                } else {
                    window.selectedProducts.delete(pid);
                    tr.classList.remove('row-selected');
                }
            }
        });
        updateProdActionBar();
    };

    window.bulkDeleteProducts = async function() {
        if (window.selectedProducts.size === 0) return;
        const ok = await invConfirm('¿Eliminar productos?', 'Se eliminarán los productos seleccionados y sus SKUs asociados. Esta acción no se puede deshacer.');
        if (!ok) return;
        const ids = Array.from(window.selectedProducts);
        for (let id of ids) {
             const f = new FormData(); f.append('action', 'delete_product'); f.append('id', id);
             await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: f });
        }
        window.selectedProducts.clear();
        updateProdActionBar();
        if (window.showToast) window.showToast('Productos eliminados', 'success');
        loadProducts(); loadMetrics();
    };

    window.exportSelectedProductsToExcel = function() {
        if (!window.selectedProducts || window.selectedProducts.size === 0) {
            if (window.showToast) window.showToast('No hay productos seleccionados', 'error');
            return;
        }
        const colsToExport = ['Producto', 'Categoría', 'Total', 'Disponibles', 'Instalados', 'Malogrados'];
        let csvContent = "\uFEFF";
        csvContent += colsToExport.map(c => `"${c.replace(/"/g, '""')}"`).join(',') + '\n';
        const exportData = (window.lastProductsData || []).filter(p => window.selectedProducts.has(p.id));
        exportData.forEach(p => {
            const row = [p.name || '', p.category_name || 'Sin cat.', p.total_quantity || 0, p.qty_disponible || 0, p.qty_instalado || 0, p.qty_malogrado || 0];
            csvContent += row.map(val => `"${val.toString().replace(/"/g, '""')}"`).join(',') + '\n';
        });
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `productos_seleccion_${new Date().getTime()}.csv`;
        link.style.display = 'none';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        if (window.showToast) window.showToast('Descarga completada.', 'success');
    };

    window.clearProductSelection = function() {
        if (!window.selectedProducts) window.selectedProducts = new Set();
        window.selectedProducts.clear();
        const checkboxes = document.querySelectorAll('.prod-row-check');
        checkboxes.forEach(c => {
            c.checked = false;
            const tr = c.closest('tr');
            if (tr) tr.classList.remove('row-selected');
        });
        updateProdActionBar();
    };

    function renderProductsGrid() {
        const grid = document.getElementById('productsGrid');
        if (!grid || !window.lastProductsData) return;
        
        const perPageSelect = document.getElementById('prodPerPage');
        const perPage = perPageSelect ? parseInt(perPageSelect.value) || 25 : 25;
        
        const q = (document.getElementById('searchProducts')?.value || '').toLowerCase();
        const cat = document.getElementById('filterProductCategory')?.value || '';
        const stat = document.getElementById('filterProductStatus')?.value || '';
        
        const filtered = window.lastProductsData.filter(p => {
             const matchName = ((p.name||'') + ' ' + (p.description||'') + ' ' + (p.searchable_children||'')).toLowerCase().includes(q);
             const matchCat = cat === '' || p.category_id == cat;
             let matchStat = true;
             if (stat === 'con_stock') matchStat = parseInt(p.qty_disponible||0) > 0;
             else if (stat === 'sin_stock') matchStat = parseInt(p.qty_disponible||0) === 0;
             else if (stat === 'stock_critico') matchStat = parseInt(p.qty_disponible||0) <= parseInt(p.stock_critico||0);
             else if (stat === 'con_malogrados') matchStat = parseInt(p.qty_malogrado||0) > 0;
             return matchName && matchCat && matchStat;
        });
        
        const totalRows = filtered.length;
        const totalPages = Math.ceil(totalRows / perPage) || 1;
        if (prodCurrentPage > totalPages) prodCurrentPage = totalPages;
        if (prodCurrentPage < 1) prodCurrentPage = 1;
        
        const start = (prodCurrentPage - 1) * perPage;
        const pageData = filtered.slice(start, start + perPage);
        
        const pageInfo = document.getElementById('prodPageInfo');
        if (pageInfo) pageInfo.textContent = totalRows > 0 ? `Mostrando ${start + 1} - ${Math.min(start + perPage, totalRows)} de ${totalRows}` : 'Mostrando 0 - 0 de 0';
        
        const btnPrev = document.getElementById('btnProdPrev');
        const btnNext = document.getElementById('btnProdNext');
        if (btnPrev) btnPrev.disabled = prodCurrentPage === 1;
        if (btnNext) btnNext.disabled = prodCurrentPage === totalPages;
        
        if (pageData.length === 0) {
             grid.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:20px;color:var(--text-muted);">No hay productos que coincidan con los filtros.</td></tr>';
             return;
        }

        grid.innerHTML = pageData.map((p, i) => {
            const isChecked = window.selectedProducts.has(p.id) ? 'checked' : '';
            const rowClass = isChecked ? 'product-row row-selected' : 'product-row';
            return `<tr class="${rowClass}" data-product-id="${p.id}" data-product-type="${p.product_type||'normal'}" data-name="${(p.name||'').toLowerCase()} ${(p.description||'').toLowerCase()} ${(p.searchable_children||'').toLowerCase()}" data-cat="${p.category_id||''}" data-disp="${p.qty_disponible||0}" data-crit="${p.stock_critico||0}" data-malo="${p.qty_malogrado||0}" data-total="${p.total_quantity||0}" data-inst="${p.qty_instalado||0}" style="animation: fadeIn 0.3s ease forwards; animation-delay: ${i*0.05}s; opacity: 0;">
                <td style="text-align:center;width:40px;vertical-align:middle;">
                    <input type="checkbox" class="prod-row-check form-check-input" value="${p.id}" ${isChecked} onchange="toggleProductSelection(this, ${p.id})">
                </td>
                <td data-label="Producto">
                    <div style="display:flex; align-items:center; gap:10px;">
                        ${p.product_type === 'agrupado' ? `<button class="accordion-toggle-btn" onclick="toggleProductChildren(${p.id}, this)" title="Expandir variantes"><i class="ph ph-caret-right"></i></button>` : ''}
                        ${p.display_image 
                            ? `<img src="${BASE}/${p.display_image}" class="lb-thumb" data-lb-src="${BASE}/${p.display_image}" data-lb-caption="${esc(p.name)}" onclick="event.stopPropagation(); openImgViewer('${BASE}/${p.display_image}', '${esc(p.name).replace(/'/g, "\\'")}');" style="width:36px;height:36px;border-radius:8px;object-fit:cover;flex-shrink:0;border:1px solid var(--border-color);cursor:zoom-in;">` 
                            : (p.requires_photos == 1 
                                ? '<div style="width:36px;height:36px;border-radius:8px;background:rgba(139,92,246,0.1);display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="ph ph-camera" style="color:#8b5cf6;font-size:1rem;"></i></div>'
                                : '<div style="width:36px;height:36px;border-radius:8px;background:var(--bg-color);display:flex;align-items:center;justify-content:center;flex-shrink:0;border:1px solid var(--border-color);"><i class="ph ph-package" style="color:var(--text-muted);font-size:1rem;opacity:0.5;"></i></div>'
                            )
                        }
                        <div>
                            <div style="font-weight:700;color:var(--text-color);">${esc(p.name)}</div>
                            ${p.is_bulk == 1 && p.product_type !== 'agrupado' ? '<span style="font-size:0.75rem;color:var(--text-muted);"><i class="ph ph-cube"></i> Granel</span>' : ''}
                            ${p.product_type === 'agrupado' ? '<span class="agrupado-badge"><i class="ph ph-stack"></i> Agrupado <small>(' + (p.children_count||0) + ')</small></span>' : ''}
                        </div>
                    </div>
                </td>
                <td data-label="Categoría"><span class="cat-badge">${esc(p.category_name||'Sin cat.')}</span></td>
                <td data-label="Total">
                    <span style="font-weight:700;color:#6366f1;">${p.total_quantity}</span>
                    ${p.is_bulk == 1 && p.product_type !== 'agrupado' ? `<span style="font-size:0.8rem;color:var(--text-muted);">${esc(p.unit_type||'Und')}</span>` : ''}
                </td>
                <td data-label="Disponibles"><span style="font-weight:700;color:#10b981;">${p.qty_disponible}</span></td>
                <td data-label="Instalados"><span style="font-weight:700;color:#3b82f6;">${p.qty_instalado}</span></td>
                <td data-label="Malogrados">${p.is_bulk == 1 ? '<span style="font-weight:700;color:#ef4444;">0</span>' : `<span style="font-weight:700;color:#ef4444;">${p.qty_malogrado}</span>`}</td>
                <td data-label="Acciones">
                    <div style="display:flex; gap:6px;">
                        ${p.product_type === 'agrupado' ? `<button type="button" class="btn btn-secondary btn-sm" onclick="openAssignGrouped(${p.id})" title="Asignar variantes"><i class="ph ph-users-three"></i></button>` : ''}
                        ${p.is_bulk == 1 && p.product_type !== 'agrupado' ? '' : (p.product_type !== 'agrupado' ? `<button class="btn btn-secondary btn-sm" onclick="viewProductSkus(${p.id})" title="Ver SKUs"><i class="ph ph-list-bullets"></i></button>` : '')}
                        <button class="btn btn-secondary btn-sm" onclick="openEditStockModal(${p.id}, '${p.product_type||'normal'}', ${p.is_bulk||0}, ${p.total_quantity||0}, '${esc(p.unit_type||'Und')}')" title="Editar stock" style="background:rgba(99,102,241,0.1);color:#6366f1;border-color:rgba(99,102,241,0.3);"><i class="ph ph-stack-plus"></i></button>
                        <button class="btn btn-secondary btn-sm" onclick="openEditProduct(${p.id}, this)" title="Editar"><i class="ph ph-pencil-simple"></i></button>
                        <button class="btn btn-danger btn-sm" onclick="deleteProduct(${p.id})" title="Eliminar"><i class="ph ph-trash"></i></button>
                    </div>
                </td>
            </tr>`;
        }).join('');
    }

    async function loadProducts() {
        try {
            const fd = new FormData(); fd.append('action', 'list_products');
            const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fd }).then(r => r.json());
            const wrap = document.getElementById('productsTableWrap');
            const empty = document.getElementById('productsEmpty');
            const pag = document.getElementById('productsPagination');
            if (res.success && res.data.length > 0) {
                window.lastProductsData = res.data;
                wrap.style.display = 'block'; empty.style.display = 'none';
                if(pag) pag.style.display = 'flex';
                renderProductsGrid();
            } else { 
                wrap.style.display = 'none'; empty.style.display = 'block'; 
                if(pag) pag.style.display = 'none'; 
            }
        } catch (e) { 
            const wrap = document.getElementById('productsTableWrap');
            if (wrap) {
                wrap.style.display = 'block';
                document.getElementById('productsGrid').innerHTML = `<tr><td colspan="10" style="color:red;padding:20px;">Error al cargar productos: ${esc(e.message)}</td></tr>`;
            }
            console.error(e); 
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const applyProductFilters = () => {
            prodCurrentPage = 1;
            renderProductsGrid();
        };

        const si = document.getElementById('searchProducts');
        const sc = document.getElementById('filterProductCategory');
        const ss = document.getElementById('filterProductStatus');
        const pp = document.getElementById('prodPerPage');
        const btnPrev = document.getElementById('btnProdPrev');
        const btnNext = document.getElementById('btnProdNext');
        
        if (si) {
            let skuSearchTimeout;
            si.addEventListener('input', () => {
                applyProductFilters();
                const searchSkuInput = document.getElementById('searchSku');
                if (searchSkuInput) {
                    searchSkuInput.value = si.value;
                    clearTimeout(skuSearchTimeout);
                    skuSearchTimeout = setTimeout(() => {
                        const tabStock = document.getElementById('tab-stock');
                        if (tabStock && tabStock.classList.contains('active')) {
                            if (typeof loadAllSkus === 'function') loadAllSkus();
                        }
                    }, 400);
                }
            });
        }
        if (sc) sc.addEventListener('change', applyProductFilters);
        if (ss) ss.addEventListener('change', applyProductFilters);
        if (pp) pp.addEventListener('change', () => { prodCurrentPage = 1; renderProductsGrid(); });
        if (btnPrev) btnPrev.addEventListener('click', () => { if (prodCurrentPage > 1) { prodCurrentPage--; renderProductsGrid(); } });
        if (btnNext) btnNext.addEventListener('click', () => { prodCurrentPage++; renderProductsGrid(); });
    });

    window.viewProductSkus = async function(id) {
        document.querySelectorAll('.inv-toolbar-tabs .inv-tab[data-tab]').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.inv-tab-pane').forEach(p => p.classList.remove('active'));
        document.querySelector('[data-tab="stock"]').classList.add('active');
        document.getElementById('tab-stock').classList.add('active');
        await populateProductFilter();
        document.getElementById('filterProduct').value = id;
        loadAllSkus();
    };

    window.deleteProduct = async function(id) {
        const ok = await invConfirm('¿Eliminar producto?', 'Se eliminarán todos los SKUs asociados. Esta acción no se puede deshacer.');
        if (!ok) return;
        const fd = new FormData(); fd.append('action', 'delete_product'); fd.append('id', id);
        try {
            const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fd }).then(r => r.json());
            if (res.success) { if (window.showToast) window.showToast(res.message, 'success'); loadProducts(); loadMetrics(); }
            else { if (window.showToast) window.showToast(res.message, 'error'); }
        } catch(err) { if (window.showToast) window.showToast('Error al eliminar', 'error'); }
    };

    // ── Categories ──
    async function loadCategories() {
        const fd = new FormData(); fd.append('action', 'list_categories');
        const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fd }).then(r => r.json());
        if (res.success) {
            const sel = document.getElementById('prodCategory');
            const fsel = document.getElementById('filterProductCategory');
            const esel = document.getElementById('editProductCategory');
            if (sel) sel.innerHTML = '<option value="">Sin categoría</option>';
            if (fsel) fsel.innerHTML = '<option value="">Todas las categorías</option>';
            if (esel) esel.innerHTML = '<option value="">Sin categoría</option>';
            res.data.forEach(c => { 
                if (sel) sel.innerHTML += `<option value="${c.id}">${esc(c.name)}</option>`; 
                if (fsel) fsel.innerHTML += `<option value="${c.id}">${esc(c.name)}</option>`;
                if (esel) esel.innerHTML += `<option value="${c.id}">${esc(c.name)}</option>`;
            });
        }
    }
    window.promptNewCategory = async function() {
        const name = prompt('Nombre de la nueva categoría:');
        if (!name || !name.trim()) return;
        const fd = new FormData(); fd.append('action', 'create_category'); fd.append('name', name.trim());
        const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fd }).then(r => r.json());
        if (res.success) { await loadCategories(); document.getElementById('prodCategory').value = res.id; if (window.showToast) window.showToast('Categoría creada', 'success'); }
    };

    window.openManageCategories = function() {
        const m = document.getElementById('manageCategoriesModal');
        if (m.parentElement !== document.body) document.body.appendChild(m);
        m.classList.add('active');
        loadManageCategoriesList();
    };

    window.closeManageCategories = function() {
        document.getElementById('manageCategoriesModal').classList.remove('active');
        document.getElementById('newCategoryName').value = '';
    };

    async function loadManageCategoriesList() {
        const fd = new FormData(); fd.append('action', 'list_categories');
        const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fd }).then(r => r.json());
        const tbody = document.getElementById('manageCategoriesList');
        if (!res.success) { tbody.innerHTML = '<tr><td colspan="2">Error al cargar categorías</td></tr>'; return; }
        
        if (res.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="2" class="text-center text-muted" style="padding:16px;">No hay categorías creadas.</td></tr>';
            return;
        }

        tbody.innerHTML = res.data.map(c => `
            <tr>
                <td style="vertical-align:middle; font-weight:600;">${esc(c.name)}</td>
                <td style="text-align:right; width:110px;">
                    <button class="btn btn-sm btn-secondary" style="padding:4px 8px; margin-right:4px;" onclick="editCategoryRow(${c.id}, '${esc(c.name).replace(/'/g, "\\'")}')" title="Editar"><i class="ph ph-pencil"></i></button>
                    <button class="btn btn-sm" style="padding:4px 8px; background:#fef2f2; color:#ef4444; border:1px solid #fee2e2;" onclick="deleteCategoryRow(${c.id})" title="Eliminar"><i class="ph ph-trash"></i></button>
                </td>
            </tr>
        `).join('');
    }

    window.addCategoryDirect = async function() {
        const input = document.getElementById('newCategoryName');
        const name = input.value.trim();
        if (!name) return;
        const fd = new FormData(); fd.append('action', 'create_category'); fd.append('name', name);
        const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fd }).then(r => r.json());
        if (res.success) {
            if (window.showToast) window.showToast('Categoría creada', 'success');
            input.value = '';
            loadManageCategoriesList();
            loadCategories(); 
        } else {
            if (window.showToast) window.showToast(res.message || 'Error al crear', 'error');
        }
    };

    window.editCategoryRow = async function(id, oldName) {
        const newName = prompt('Editar categoría:', oldName);
        if (!newName || newName.trim() === '' || newName.trim() === oldName) return;
        const fd = new FormData(); fd.append('action', 'update_category'); fd.append('id', id); fd.append('name', newName.trim());
        const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fd }).then(r => r.json());
        if (res.success) {
            if (window.showToast) window.showToast('Categoría actualizada', 'success');
            loadManageCategoriesList();
            loadCategories();
            loadProducts(); 
        } else {
            if (window.showToast) window.showToast(res.message || 'Error al editar', 'error');
        }
    };

    window.deleteCategoryRow = async function(id) {
        const ok = await invConfirm('¿Eliminar categoría?', 'No se podrá eliminar si hay productos usándola.');
        if (!ok) return;
        const fd = new FormData(); fd.append('action', 'delete_category'); fd.append('id', id);
        try {
            const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fd }).then(r => r.json());
            if (res.success) {
                if (window.showToast) window.showToast('Categoría eliminada', 'success');
                loadManageCategoriesList();
                loadCategories();
            } else {
                if (window.showToast) window.showToast(res.message || 'Error al eliminar', 'error');
            }
        } catch(e) {
            if (window.showToast) window.showToast('Error de red', 'error');
        }
    };

    // ── Custom Columns Manager ──────────────────────────────
    // Column objects: { name: string, type: 'text'|'number'|'date'|'select' }
    const COL_TYPE_META = {
        text:   { icon: 'ph-text-aa',      label: 'Texto',   color: '#6366f1' },
        number: { icon: 'ph-hash',          label: 'Número',  color: '#f59e0b' },
        date:   { icon: 'ph-calendar',      label: 'Fecha',   color: '#10b981' },
        select: { icon: 'ph-list-bullets',  label: 'Lista',   color: '#8b5cf6' },
    };

    function colObj(c) {
        // backward compat: accept plain string or object
        if (typeof c === 'string') return { name: c, type: 'text' };
        return { name: c.name || c, type: c.type || 'text' };
    }
    function colsToSave(arr) {
        // Save as JSON array of objects for the backend
        return JSON.stringify(arr.map(colObj));
    }
    function colsFromSaved(val) {
        if (!val) return [];
        try {
            const parsed = JSON.parse(val);
            if (Array.isArray(parsed)) return parsed.map(colObj);
        } catch(e) {}
        // Legacy: comma-separated string
        return val.split(',').filter(s=>s.trim()).map(s=>({ name: s.trim(), type: 'text' }));
    }

    function renderCustomCols() {
        const list = document.getElementById('customColsList');
        const badge = document.getElementById('colCountBadge');
        if (!list) return;
        if (badge) badge.textContent = customColumns.length + (customColumns.length === 1 ? ' columna' : ' columnas');
        if (customColumns.length === 0) {
            list.innerHTML = `<div style="text-align:center;padding:14px;color:var(--text-muted);font-size:0.82rem;border:1px dashed var(--border-color);border-radius:8px;"><i class="ph ph-columns" style="font-size:1.4rem;display:block;margin-bottom:4px;opacity:0.3;"></i>Sin columnas — añade una abajo</div>`;
            return;
        }
        list.innerHTML = customColumns.map((c, i) => {
            const col = colObj(c);
            const meta = COL_TYPE_META[col.type] || COL_TYPE_META.text;
            return `<div class="col-manager-row" id="col-row-${i}" style="display:flex;align-items:center;gap:8px;background:var(--bg-color);border:1px solid var(--border-color);border-radius:10px;padding:8px 12px;transition:border-color 0.2s;">
                <span style="width:28px;height:28px;border-radius:7px;background:${meta.color}18;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="ph ${meta.icon}" style="color:${meta.color};font-size:1rem;"></i>
                </span>
                <div style="flex:1;min-width:0;">
                    <div style="font-weight:600;font-size:0.88rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${esc(col.name)}</div>
                    <div style="font-size:0.72rem;color:var(--text-muted);">${meta.label}</div>
                </div>
                <button type="button" onclick="inlineEditCol(${i},'create')" title="Editar" style="background:transparent;border:none;color:var(--text-muted);cursor:pointer;padding:4px;border-radius:6px;transition:all 0.15s;" onmouseover="this.style.background='var(--border-color)'" onmouseout="this.style.background='transparent'"><i class="ph ph-pencil-simple" style="font-size:1rem;"></i></button>
                <button type="button" onclick="removeCustomColumn(${i})" title="Eliminar" style="background:transparent;border:none;color:#ef4444;cursor:pointer;padding:4px;border-radius:6px;transition:all 0.15s;" onmouseover="this.style.background='rgba(239,68,68,0.1)'" onmouseout="this.style.background='transparent'"><i class="ph ph-trash" style="font-size:1rem;"></i></button>
            </div>`;
        }).join('');
    }

    function renderEditCustomCols() {
        const list = document.getElementById('editCustomColsList');
        const badge = document.getElementById('editColCountBadge');
        if (!list) return;
        if (badge) badge.textContent = editCustomColumns.length + (editCustomColumns.length === 1 ? ' columna' : ' columnas');
        if (editCustomColumns.length === 0) {
            list.innerHTML = `<div style="text-align:center;padding:14px;color:var(--text-muted);font-size:0.82rem;border:1px dashed var(--border-color);border-radius:8px;"><i class="ph ph-columns" style="font-size:1.4rem;display:block;margin-bottom:4px;opacity:0.3;"></i>Sin columnas — añade una abajo</div>`;
            return;
        }
        list.innerHTML = editCustomColumns.map((c, i) => {
            const col = colObj(c);
            const meta = COL_TYPE_META[col.type] || COL_TYPE_META.text;
            return `<div class="col-manager-row" id="editcol-row-${i}" style="display:flex;align-items:center;gap:8px;background:var(--bg-color);border:1px solid var(--border-color);border-radius:10px;padding:8px 12px;transition:border-color 0.2s;">
                <span style="width:28px;height:28px;border-radius:7px;background:${meta.color}18;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="ph ${meta.icon}" style="color:${meta.color};font-size:1rem;"></i>
                </span>
                <div style="flex:1;min-width:0;">
                    <div style="font-weight:600;font-size:0.88rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${esc(col.name)}</div>
                    <div style="font-size:0.72rem;color:var(--text-muted);">${meta.label}</div>
                </div>
                <button type="button" onclick="inlineEditCol(${i},'edit')" title="Editar" style="background:transparent;border:none;color:var(--text-muted);cursor:pointer;padding:4px;border-radius:6px;transition:all 0.15s;" onmouseover="this.style.background='var(--border-color)'" onmouseout="this.style.background='transparent'"><i class="ph ph-pencil-simple" style="font-size:1rem;"></i></button>
                <button type="button" onclick="removeEditCustomColumn(${i})" title="Eliminar" style="background:transparent;border:none;color:#ef4444;cursor:pointer;padding:4px;border-radius:6px;transition:all 0.15s;" onmouseover="this.style.background='rgba(239,68,68,0.1)'" onmouseout="this.style.background='transparent'"><i class="ph ph-trash" style="font-size:1rem;"></i></button>
            </div>`;
        }).join('');
    }

    // Inline edit: replaces the row with an editable form
    window.inlineEditCol = function(idx, ctx) {
        const arr = ctx === 'edit' ? editCustomColumns : customColumns;
        const col = colObj(arr[idx]);
        const rowId = (ctx === 'edit' ? 'editcol-row-' : 'col-row-') + idx;
        const row = document.getElementById(rowId);
        if (!row) return;
        const typeOpts = Object.entries(COL_TYPE_META).map(([v, m]) =>
            `<option value="${v}" ${col.type === v ? 'selected' : ''}>${m.label}</option>`).join('');
        row.innerHTML = `
            <input type="text" class="form-control" value="${esc(col.name)}" id="inline-edit-name-${idx}" style="flex:2;min-width:80px;padding:6px 8px;font-size:0.85rem;">
            <select class="form-control" id="inline-edit-type-${idx}" style="flex:1;min-width:90px;padding:6px 8px;font-size:0.85rem;">${typeOpts}</select>
            <button type="button" onclick="saveInlineEditCol(${idx},'${ctx}')" style="background:var(--primary-color);color:#fff;border:none;border-radius:8px;padding:6px 12px;cursor:pointer;font-size:0.82rem;flex-shrink:0;"><i class="ph ph-check"></i></button>
            <button type="button" onclick="${ctx === 'edit' ? 'renderEditCustomCols' : 'renderCustomCols'}()" style="background:var(--bg-color);border:1px solid var(--border-color);border-radius:8px;padding:6px 10px;cursor:pointer;font-size:0.82rem;flex-shrink:0;"><i class="ph ph-x"></i></button>`;
        row.style.borderColor = 'var(--primary-color)';
        document.getElementById(`inline-edit-name-${idx}`)?.focus();
    };

    window.saveInlineEditCol = function(idx, ctx) {
        const name = document.getElementById(`inline-edit-name-${idx}`)?.value.trim();
        const type = document.getElementById(`inline-edit-type-${idx}`)?.value || 'text';
        if (!name) return;
        if (ctx === 'edit') {
            editCustomColumns[idx] = { name, type };
            renderEditCustomCols();
        } else {
            customColumns[idx] = { name, type };
            renderCustomCols();
            if (previewSkuCodes.length > 0) renderPreviewSkuTable();
        }
    };

    window.addCustomColumn = function() {
        const input = document.getElementById('newColName');
        const typeEl = document.getElementById('newColType');
        const name = input.value.trim();
        const type = typeEl ? typeEl.value : 'text';
        if (!name) { input.focus(); return; }
        const exists = customColumns.some(c => colObj(c).name.toLowerCase() === name.toLowerCase());
        if (exists) { if (window.showToast) window.showToast('Esa columna ya existe', 'error'); return; }
        customColumns.push({ name, type });
        input.value = '';
        if (typeEl) typeEl.value = 'text';
        renderCustomCols();
        if (previewSkuCodes.length > 0) renderPreviewSkuTable();
    };

    window.removeCustomColumn = function(idx) {
        customColumns.splice(idx, 1);
        renderCustomCols();
        if (previewSkuCodes.length > 0) renderPreviewSkuTable();
    };

    window.addPredefinedCol = function(name) {
        const exists = customColumns.some(c => colObj(c).name.toLowerCase() === name.toLowerCase());
        if (exists) { if (window.showToast) window.showToast('La columna ' + name + ' ya existe', 'error'); return; }
        customColumns.push({ name, type: 'text' });
        renderCustomCols();
        if (previewSkuCodes.length > 0) renderPreviewSkuTable();
    };

    let defaultSuggestions = ['MAC', 'SN', 'IP', 'Ubicación', 'Nota'];

    function loadSuggestions(type) {
        let saved = localStorage.getItem('inv_col_suggestions');
        let suggestions = saved ? JSON.parse(saved) : defaultSuggestions;
        const wrapId = type === 'edit' ? 'editSuggestionsWrap' : 'suggestionsWrap';
        const wrap = document.getElementById(wrapId);
        if(!wrap) return;

        Array.from(wrap.querySelectorAll('.inv-col-pill')).forEach(e => {
            const txt = e.innerText || e.textContent;
            if(!txt.includes('Nueva') && !txt.includes('sugerencia')) e.remove();
        });

        const btnCreate = wrap.querySelector('.inv-col-pill:last-child');

        suggestions.forEach((name, idx) => {
            const span = document.createElement('span');
            span.className = 'inv-col-pill';
            span.style = 'cursor:pointer; background:var(--bg-color); border:1px solid var(--border-color); position:relative; padding-right:44px;';
            span.onclick = (e) => {
                if(e.target.tagName !== 'I' || (!e.target.classList.contains('ph-x') && !e.target.classList.contains('ph-pencil-simple'))) {
                    if (type === 'edit') addEditCustomColumnStr(name);
                    else addPredefinedCol(name);
                }
            };
            span.innerHTML = `<i class="ph ph-plus"></i> ${esc(name)} <i class="ph ph-pencil-simple" style="position:absolute; right:22px; font-size:0.8rem; opacity:0.6; padding:2px;" title="Editar sugerencia" onclick="editSuggestion(${idx}, event)"></i> <i class="ph ph-x" style="position:absolute; right:4px; font-size:0.8rem; opacity:0.6; padding:2px;" title="Eliminar sugerencia" onclick="removeSuggestion(${idx}, event)"></i>`;
            wrap.insertBefore(span, btnCreate);
        });
    }

    window.removeSuggestion = function(idx, e) {
        e.stopPropagation();
        let saved = localStorage.getItem('inv_col_suggestions');
        let suggestions = saved ? JSON.parse(saved) : defaultSuggestions;
        suggestions.splice(idx, 1);
        localStorage.setItem('inv_col_suggestions', JSON.stringify(suggestions));
        loadSuggestions('new');
        loadSuggestions('edit');
    };

    window.editSuggestion = function(idx, e) {
        e.stopPropagation();
        let saved = localStorage.getItem('inv_col_suggestions');
        let suggestions = saved ? JSON.parse(saved) : defaultSuggestions;
        const newName = prompt('Editar sugerencia:', suggestions[idx]);
        if(newName && newName.trim()) {
            suggestions[idx] = newName.trim();
            localStorage.setItem('inv_col_suggestions', JSON.stringify(suggestions));
            loadSuggestions('new');
            loadSuggestions('edit');
        }
    };

    window.promptNewSuggestion = function(type) {
        const name = prompt('Nombre de la nueva sugerencia:');
        if(name && name.trim()) {
            let saved = localStorage.getItem('inv_col_suggestions');
            let suggestions = saved ? JSON.parse(saved) : defaultSuggestions;
            if(!suggestions.includes(name.trim())) {
                suggestions.push(name.trim());
                localStorage.setItem('inv_col_suggestions', JSON.stringify(suggestions));
                loadSuggestions('new');
                loadSuggestions('edit');
            }
        }
    };
    
    let currentAliases = [];
    function initAliasInput() {
        const input = document.getElementById('prodAliasInput');
        if(!input) return;
        input.addEventListener('keydown', (e) => {
            if(e.key === 'Enter') {
                e.preventDefault();
                const val = input.value.trim();
                if(val && !currentAliases.includes(val)) {
                    currentAliases.push(val);
                    renderAliases();
                    input.value = '';
                }
            }
        });
    }

    function renderAliases() {
        const container = document.getElementById('aliasTagsContainer');
        const hidden = document.getElementById('prodAliases');
        if(hidden) hidden.value = currentAliases.join(',');
        if(!container) return;
        container.innerHTML = currentAliases.map((a, i) =>
            `<span style="display:inline-flex;align-items:center;gap:4px;background:var(--primary-color);color:#fff;padding:4px 10px;border-radius:12px;font-size:0.8rem;font-weight:600;">
                ${esc(a)} <i class="ph ph-x" style="cursor:pointer;" onclick="removeAlias(${i})"></i>
            </span>`
        ).join('');
    }
    
    window.removeAlias = function(idx) {
        currentAliases.splice(idx, 1);
        renderAliases();
    };

    // @@ Product Modal @@
    let selectedProductType = 'normal';

    function initProductModal() {
        initAliasInput();
        initEditAliasInput();
        loadSuggestions('new');
        loadSuggestions('edit');

        // FAB → open product type selector
        document.getElementById('btnNewProduct').addEventListener('click', () => {
            const m = document.getElementById('productTypeModal');
            if (m.parentElement !== document.body) document.body.appendChild(m);
            m.classList.add('active');
        });

        document.getElementById('prodRequiresPhotos').addEventListener('change', () => {
            if (previewSkuCodes.length > 0) renderPreviewSkuTable();
        });

        document.getElementById('btnSaveProduct').addEventListener('click', async () => {
            const name = document.getElementById('prodName').value.trim();
            const category_id = document.getElementById('prodCategory').value;
            const quantity = previewSkuCodes.length;
            let description = document.getElementById('prodDesc').value.trim();
            const aliases = document.getElementById('prodAliases') ? document.getElementById('prodAliases').value.trim() : '';
            if (aliases) { description = description ? description + '\nNombres alternativos: ' + aliases : 'Nombres alternativos: ' + aliases; }
            const stock_minimo = parseInt(document.getElementById('prodStockMin').value) || 0;
            const stock_critico = parseInt(document.getElementById('prodStockCrit').value) || 0;
            const is_bulk = selectedProductType === 'granel' ? 1 : 0;
            const unit_type = document.getElementById('prodUnitType').value;
            const master_sku = document.getElementById('prodMasterSku').value.trim();
            if (!name) { if (window.showToast) window.showToast('El nombre es requerido', 'error'); return; }
            // Agrupado validation
            if (selectedProductType === 'agrupado') {
                const variants = collectVariants();
                if (variants.length === 0) { if (window.showToast) window.showToast('Agrega al menos una variante', 'error'); return; }
            } else if (!is_bulk && quantity < 1) {
                if (window.showToast) window.showToast('Agrega al menos 1 SKU', 'error'); return;
            }
            const btn = document.getElementById('btnSaveProduct');
            btn.disabled = true; btn.innerHTML = '<i class="ph ph-spinner"></i> Guardando...';
            const fd = new FormData();
            fd.append('action', 'create_product');
            fd.append('name', name);
            fd.append('category_id', category_id);
            fd.append('quantity', is_bulk ? (parseInt(document.getElementById('prodGranelQty').value) || 0) : quantity);
            fd.append('description', description);
            fd.append('stock_minimo', stock_minimo);
            fd.append('stock_critico', stock_critico);
            fd.append('is_bulk', is_bulk);
            fd.append('unit_type', unit_type);
            fd.append('master_sku', master_sku);
            fd.append('product_type', selectedProductType);
            fd.append('requires_photos', document.getElementById('prodRequiresPhotos').checked ? 1 : 0);
            createProductPhotos.forEach(f => fd.append('product_photos[]', f));
            fd.append('custom_columns', JSON.stringify(customColumns));
            fd.append('preview_custom_data', JSON.stringify(previewCustomData));
            if (selectedProductType === 'agrupado') {
                fd.append('variants', JSON.stringify(collectVariants()));
                fd.append('variant_columns', JSON.stringify(variantColumns));
            }
            try {
                const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fd }).then(r => r.json());
                if (res.success) { if (window.showToast) window.showToast(res.message, 'success'); closeProductModal(); loadProducts(); loadMetrics(); }
                else { if (window.showToast) window.showToast(res.message, 'error'); }
            } catch (e) { if (window.showToast) window.showToast('Error del servidor', 'error'); }
            btn.disabled = false; btn.innerHTML = '<i class="ph ph-floppy-disk"></i> Guardar Producto';
        });
    }

    // Select product type from modal
    window.selectProductType = function(type) {
        selectedProductType = type;

        // Close type selector
        document.getElementById('productTypeModal').classList.remove('active');

        // Open product creation modal
        const m = document.getElementById('newProductModal');
        if (m.parentElement !== document.body) document.body.appendChild(m);
        m.classList.add('active');

        // Reset form fields
        document.getElementById('prodName').value = '';
        const aliasInput = document.getElementById('prodAliasInput');
        if (aliasInput) aliasInput.value = '';
        currentAliases = [];
        renderAliases();
        const aliasWrap = document.getElementById('aliasWrap');
        if (aliasWrap) {
            aliasWrap.style.display = 'none';
            if (aliasWrap.previousElementSibling) aliasWrap.previousElementSibling.style.display = 'inline-block';
        }
        document.getElementById('prodQty').value = 1;
        document.getElementById('prodDesc').value = '';
        document.getElementById('prodStockMin').value = 10;
        document.getElementById('prodStockCrit').value = 3;
        document.getElementById('prodRequiresPhotos').checked = false;
        createProductPhotos = [];
        renderProductPhotoGallery('create');
        switchNewProductTab('datos');
        document.getElementById('skuPreviewWrap').style.display = 'none';
        previewCustomData = [];
        previewSkuCodes = [];
        customColumns = [];
        skuMode = 'auto';
        // Reset variants table + columns
        document.getElementById('variantsTableBody').innerHTML = '';
        variantCounter = 0;
        variantColumns = [];
        renderVariantCols();
        rebuildVariantTableHeaders();
        updateVariantCount();
        renderCustomCols();
        setSkuMode('auto');
        loadCategories();

        // Reset granel fields
        document.getElementById('prodUnitType').value = 'Unidades';
        document.getElementById('prodMasterSku').value = '';
        document.getElementById('prodGranelQty').value = 1;

        // Apply visibility based on type
        applyProductTypeVisibility(type);
    };

    window.applyProductTypeVisibility = function(type) {
        const grid = document.getElementById('newProductGrid');
        const skuRightCol = document.getElementById('skuRightCol');
        const customColsSection = document.getElementById('customColsSection');
        const granelFieldsWrap = document.getElementById('granelFieldsWrap');
        const agrupadoSection = document.getElementById('agrupadoVariantsSection');
        const badge = document.getElementById('productTypeBadge');

        // Update header badge
        const typeLabels = { normal: 'Normal', granel: 'A Granel', agrupado: 'Agrupado' };
        badge.textContent = typeLabels[type] || '';
        badge.className = 'product-type-header-badge type-' + type;

        // Update hidden is_bulk field
        document.getElementById('prodIsBulk').value = type === 'granel' ? '1' : '0';

        if (type === 'normal') {
            grid.style.gridTemplateColumns = '1fr 1fr';
            skuRightCol.style.display = 'block';
            customColsSection.style.display = 'block';
            granelFieldsWrap.style.display = 'none';
            agrupadoSection.style.display = 'none';

            document.getElementById('skuModeToggleWrap').style.display = 'block';
            document.getElementById('btnGenerateSkus').style.display = 'inline-flex';
            const qtyLabel = document.getElementById('prodQty')?.parentElement?.querySelector('label');
            if (qtyLabel) qtyLabel.textContent = 'Cantidad a generar';
            setSkuMode('auto');

        } else if (type === 'granel') {
            grid.style.gridTemplateColumns = '1fr';
            skuRightCol.style.display = 'none';
            customColsSection.style.display = 'none';
            granelFieldsWrap.style.display = 'block';
            agrupadoSection.style.display = 'none';

        } else if (type === 'agrupado') {
            grid.style.gridTemplateColumns = '1fr';
            skuRightCol.style.display = 'none';
            customColsSection.style.display = 'none';
            granelFieldsWrap.style.display = 'none';
            agrupadoSection.style.display = 'block';
            // Add first variant row if empty
            if (document.getElementById('variantsTableBody').children.length === 0) {
                addVariantRow();
            }
        }
    };

    // ── Variant Column Management for Agrupado ──
    let variantColumns = []; // [{name, type}]
    let variantCounter = 0;

    window.addVariantCol = function(name) {
        if (!name) {
            const input = document.getElementById('varColNewName');
            name = (input?.value || '').trim();
            if (input) input.value = '';
        }
        if (!name) { if (window.showToast) window.showToast('Escribe un nombre de columna', 'error'); return; }
        if (variantColumns.some(c => c.name.toLowerCase() === name.toLowerCase())) {
            if (window.showToast) window.showToast('Columna ya existe', 'error'); return;
        }
        variantColumns.push({ name, type: 'text' });
        renderVariantCols();
        rebuildVariantTableHeaders();
        rebuildVariantTableRows();
    };

    window.removeVariantCol = function(idx) {
        variantColumns.splice(idx, 1);
        renderVariantCols();
        rebuildVariantTableHeaders();
        rebuildVariantTableRows();
    };

    function renderVariantCols() {
        const list = document.getElementById('varColsList');
        const badge = document.getElementById('varColCountBadge');
        if (badge) badge.textContent = variantColumns.length + ' columna' + (variantColumns.length !== 1 ? 's' : '');
        if (!list) return;
        if (variantColumns.length === 0) {
            list.innerHTML = '';
            return;
        }
        list.innerHTML = variantColumns.map((c, i) => `
            <div style="display:flex;align-items:center;gap:8px;background:var(--bg-color);border:1px solid var(--border-color);border-radius:8px;padding:6px 10px;">
                <span style="width:24px;height:24px;border-radius:6px;background:rgba(139,92,246,0.1);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="ph ph-text-aa" style="color:#8b5cf6;font-size:0.85rem;"></i>
                </span>
                <span style="flex:1;font-weight:600;font-size:0.85rem;">${esc(c.name)}</span>
                <button type="button" onclick="removeVariantCol(${i})" title="Eliminar" style="background:transparent;border:none;color:#ef4444;cursor:pointer;padding:4px;border-radius:6px;" onmouseover="this.style.background='rgba(239,68,68,0.1)'" onmouseout="this.style.background='transparent'"><i class="ph ph-trash" style="font-size:0.9rem;"></i></button>
            </div>`).join('');
    }

    function rebuildVariantTableHeaders() {
        const thead = document.getElementById('variantsTableHead');
        if (!thead) return;
        let html = '<tr><th style="min-width:160px;">Nombre</th>';
        variantColumns.forEach(c => { html += `<th style="min-width:100px;">${esc(c.name)}</th>`; });
        html += '<th style="min-width:90px;">Cantidad</th><th style="width:50px;"></th></tr>';
        thead.innerHTML = html;
    }

    function rebuildVariantTableRows() {
        const tbody = document.getElementById('variantsTableBody');
        if (!tbody) return;
        // Preserve existing data
        const existing = [];
        tbody.querySelectorAll('.variant-input-row').forEach(row => {
            const data = { name: row.querySelector('[data-field="name"]')?.value || '', quantity: row.querySelector('[data-field="quantity"]')?.value || '1', attrs: {} };
            row.querySelectorAll('[data-attr]').forEach(inp => { data.attrs[inp.dataset.attr] = inp.value; });
            existing.push(data);
        });
        tbody.innerHTML = '';
        existing.forEach(d => {
            addVariantRowWithData(d.name, d.quantity, d.attrs);
        });
    }

    function addVariantRowWithData(name, quantity, attrs) {
        variantCounter++;
        const tbody = document.getElementById('variantsTableBody');
        const tr = document.createElement('tr');
        tr.className = 'variant-input-row';
        tr.dataset.variantId = variantCounter;
        let html = `<td><input type="text" class="form-control" data-field="name" value="${esc(name || '')}" placeholder="Nombre variante" style="font-size:0.85rem;"></td>`;
        variantColumns.forEach(c => {
            const val = (attrs && attrs[c.name]) || '';
            html += `<td><input type="text" class="form-control" data-attr="${esc(c.name)}" value="${esc(val)}" placeholder="${esc(c.name)}" style="font-size:0.85rem;"></td>`;
        });
        html += `<td><input type="number" class="form-control" data-field="quantity" min="1" value="${quantity || 1}" style="font-size:0.85rem;"></td>`;
        html += `<td><button type="button" class="btn-delete-row" onclick="removeVariantRow(this)" title="Eliminar"><i class="ph ph-x"></i></button></td>`;
        tr.innerHTML = html;
        tbody.appendChild(tr);
        updateVariantCount();
    }

    window.addVariantRow = function() {
        addVariantRowWithData('', '1', {});
        const rows = document.querySelectorAll('#variantsTableBody .variant-input-row');
        const last = rows[rows.length - 1];
        if (last) last.querySelector('[data-field="name"]')?.focus();
    };

    window.removeVariantRow = function(btn) {
        const tr = btn.closest('tr');
        if (tr) tr.remove();
        updateVariantCount();
    };

    function updateVariantCount() {
        const count = document.querySelectorAll('#variantsTableBody .variant-input-row').length;
        const badge = document.getElementById('variantCountBadge');
        if (badge) badge.textContent = count + ' variante' + (count !== 1 ? 's' : '');
    }

    window.collectVariants = function() {
        const rows = document.querySelectorAll('#variantsTableBody .variant-input-row');
        const variants = [];
        rows.forEach(row => {
            const name = (row.querySelector('[data-field="name"]')?.value || '').trim();
            const quantity = parseInt(row.querySelector('[data-field="quantity"]')?.value || 0);
            if (!name || quantity < 1) return;
            const attributes = {};
            row.querySelectorAll('[data-attr]').forEach(inp => {
                if (inp.value.trim()) attributes[inp.dataset.attr] = inp.value.trim();
            });
            variants.push({ name, quantity, attributes });
        });
        return variants;
    };

    // ── Accordion: Toggle Children (dynamic attributes) ──
    window.toggleProductChildren = async function(productId, btn) {
        const parentRow = btn.closest('tr');
        const icon = btn.querySelector('i');
        const isExpanded = parentRow.classList.contains('accordion-expanded');

        if (isExpanded) {
            parentRow.classList.remove('accordion-expanded');
            icon.className = 'ph ph-caret-right';
            let next = parentRow.nextElementSibling;
            while (next && next.classList.contains('variant-child-row')) {
                const toRemove = next;
                next = next.nextElementSibling;
                toRemove.remove();
            }
            return;
        }

        icon.className = 'ph ph-spinner ph-spin';
        try {
            const fd = new FormData();
            fd.append('action', 'get_children');
            fd.append('product_id', productId);
            const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fd }).then(r => r.json());
            if (res.success && res.data.length > 0) {
                parentRow.classList.add('accordion-expanded');
                icon.className = 'ph ph-caret-down';
                const cols = res.columns || [];
                const fragment = document.createDocumentFragment();
                res.data.forEach((child, idx) => {
                    const tr = document.createElement('tr');
                    tr.className = 'variant-child-row';
                    tr.style.animation = `fadeIn 0.2s ease forwards`;
                    tr.style.animationDelay = `${idx * 0.05}s`;
                    tr.style.opacity = '0';
                    const attrs = child.variant_attributes || {};
                    // Build inline variant label: "Azul / Micronics" from attribute values
                    const attrValues = Object.values(attrs).filter(v => v);
                    const attrBadges = Object.entries(attrs).map(([k,v]) => v ? `<span class="variant-attr-badge"><i class="ph ph-tag"></i> ${esc(k)}: ${esc(v)}</span>` : '').join('');
                    const variantSuffix = attrValues.length > 0 ? ` <span style="color:var(--text-muted);font-weight:400;">— ${attrValues.map(v => esc(v)).join(' / ')}</span>` : '';

                    const qtyTotal     = parseFloat(child.total_quantity   || 0);
                    const qtyDisp      = child.qty_disponible  != null ? parseFloat(child.qty_disponible)  : qtyTotal;
                    const qtyInst      = child.qty_instalado   != null ? parseFloat(child.qty_instalado)   : 0;
                    const qtyMalogrado = child.qty_malogrado   != null ? parseFloat(child.qty_malogrado)   : 0;

                    tr.innerHTML = `
                        <td></td>
                        <td data-label="Producto">
                            <div style="display:flex; align-items:center; gap:8px; padding-left:20px;">
                                <span style="color:var(--text-muted);font-size:1rem;flex-shrink:0;">└</span>
                                <div>
                                    <div style="font-weight:600;color:var(--text-color);font-size:0.88rem;">${esc(child.name)}</div>
                                    <div style="display:flex;gap:4px;flex-wrap:wrap;margin-top:3px;">${attrBadges}</div>
                                </div>
                            </div>
                        </td>
                        <td data-label="Categoría"></td>
                        <td data-label="Total"><span style="font-weight:700;color:#6366f1;">${qtyTotal}</span></td>
                        <td data-label="Disponibles"><span style="font-weight:700;color:#10b981;">${qtyDisp}</span></td>
                        <td data-label="Instalados"><span style="font-weight:700;color:#3b82f6;">${qtyInst}</span></td>
                        <td data-label="Malogrados"><span style="font-weight:700;color:#ef4444;">${qtyMalogrado}</span></td>
                        <td data-label="Acciones"></td>
                    `;
                    fragment.appendChild(tr);
                });
                parentRow.after(fragment);
            }
        } catch (e) { console.error(e); }
        if (icon.className.includes('spinner')) icon.className = 'ph ph-caret-right';
    };

    // ── Assign Grouped Modal ──
    let assignGroupedProductId = null;

    window.openAssignGrouped = async function(productId) {
        alert('Botón clickeado para el producto: ' + productId);
        try {
            assignGroupedProductId = productId;
            document.getElementById('assignGroupedTitle').textContent = 'Cargando...';
            document.getElementById('assignGroupedEpp').checked = false;
            
            // Open modal immediately to show it's working
            const modal = document.getElementById('assignGroupedModal');
            if (modal) modal.classList.add('active');
            
            const tbody = document.getElementById('assignGroupedBody');
            const thead = document.getElementById('assignGroupedHead');
            tbody.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:20px;"><i class="ph ph-spinner ph-spin" style="font-size:2rem;color:#8b5cf6;"></i></td></tr>';
            thead.innerHTML = '';

            // Load users
            const userSelect = document.getElementById('assignGroupedUser');
            try {
                const fd = new FormData(); fd.append('action', 'list_users');
                const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fd }).then(r => r.json());
                userSelect.innerHTML = '<option value="">Seleccionar...</option>' + (res.data||[]).map(u => `<option value="${u.id}">${esc(u.name)}</option>`).join('');
            } catch(e) { userSelect.innerHTML = '<option>Error</option>'; }

            // Load children
            const fd2 = new FormData();
            fd2.append('action', 'get_children');
            fd2.append('product_id', productId);
            const res2 = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fd2 }).then(r => r.json());

            const cols = res2.columns || [];

            // Build header
            let headHtml = '<tr><th style="width:40px;"><input type="checkbox" id="assignGroupedCheckAll" onchange="toggleAllGroupedChecks(this.checked)"></th><th>Variante</th>';
            cols.forEach(c => { headHtml += `<th>${esc(c.name)}</th>`; });
            headHtml += '<th>Disp.</th><th style="min-width:80px;">Cantidad</th></tr>';
            thead.innerHTML = headHtml;

            // Build body
            tbody.innerHTML = (res2.data || []).map(child => {
                const attrs = child.variant_attributes || {};
                let row = `<tr data-variant-id="${child.id}">
                    <td><input type="checkbox" class="assign-grouped-check" checked></td>
                    <td style="font-weight:600;">${esc(child.name)}</td>`;
                cols.forEach(c => { row += `<td>${esc(attrs[c.name] || '-')}</td>`; });
                row += `<td><span style="font-weight:700;color:#10b981;">${child.total_quantity}</span></td>
                    <td><input type="number" class="form-control assign-grouped-qty" min="0" max="${child.total_quantity}" value="0" style="font-size:0.85rem;padding:4px 8px;width:80px;"></td>
                </tr>`;
                return row;
            }).join('');
            
            // Set real title if available in the first variant
            if (res2.data && res2.data.length > 0) {
                // Name is like "Mouse Gamer" based on child names? No, child name is full variant.
                document.getElementById('assignGroupedTitle').textContent = 'Asignar Variantes';
            }

        } catch (err) {
            console.error('Error en openAssignGrouped:', err);
            if (window.showToast) window.showToast('Error abriendo modal: ' + err.message, 'error');
            else alert('Error abriendo modal: ' + err.message);
        }
    };

    window.toggleAllGroupedChecks = function(checked) {
        document.querySelectorAll('.assign-grouped-check').forEach(cb => cb.checked = checked);
    };

    window.submitGroupedAssignment = async function() {
        const userId = document.getElementById('assignGroupedUser').value;
        if (!userId) { if (window.showToast) window.showToast('Selecciona un usuario', 'error'); return; }
        const isEpp = document.getElementById('assignGroupedEpp').checked ? 1 : 0;

        const assignments = [];
        document.querySelectorAll('#assignGroupedBody tr').forEach(row => {
            const cb = row.querySelector('.assign-grouped-check');
            if (!cb || !cb.checked) return;
            const qty = parseFloat(row.querySelector('.assign-grouped-qty')?.value || 0);
            if (qty <= 0) return;
            assignments.push({ variant_id: row.dataset.variantId, quantity: qty });
        });

        if (assignments.length === 0) { if (window.showToast) window.showToast('Marca variantes y cantidades a asignar', 'error'); return; }

        const btn = document.getElementById('btnSubmitGroupedAssign');
        btn.disabled = true; btn.innerHTML = '<i class="ph ph-spinner ph-spin"></i> Asignando...';

        try {
            const fd = new FormData();
            fd.append('action', 'assign_grouped');
            fd.append('user_id', userId);
            fd.append('is_epp', isEpp);
            fd.append('assignments', JSON.stringify(assignments));
            const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fd }).then(r => r.json());
            if (res.success) {
                if (window.showToast) window.showToast(res.message, 'success');
                document.getElementById('assignGroupedModal').classList.remove('active');
                loadProducts(); loadMetrics();
            } else {
                if (window.showToast) window.showToast(res.message, 'error');
            }
        } catch(e) { if (window.showToast) window.showToast('Error del servidor', 'error'); }
        btn.disabled = false; btn.innerHTML = '<i class="ph ph-check-circle"></i> Asignar Selección';
    };

    window.toggleBulkMode = function(isBulk) {
        document.getElementById('prodUnitWrap').style.display = isBulk ? 'block' : 'none';
        document.getElementById('prodMasterSkuWrap').style.display = isBulk ? 'block' : 'none';
        const skuModeToggleWrap = document.getElementById('skuModeToggleWrap');
        const customColsSection  = document.getElementById('customColsSection');
        const autoModeWrap       = document.getElementById('autoModeWrap');
        const manualModeWrap     = document.getElementById('manualModeWrap');
        const previewWrap        = document.getElementById('skuPreviewWrap');
        if (isBulk) {
            // Ocultar sólo controles de SKU — NO las columnas completas
            if (skuModeToggleWrap) skuModeToggleWrap.style.display = 'none';
            if (customColsSection)  customColsSection.style.display  = 'none';
            if (manualModeWrap)     manualModeWrap.style.display      = 'none';
            if (previewWrap)        previewWrap.style.display         = 'none';
            if (autoModeWrap)       autoModeWrap.style.display        = 'block';
            const btnGen = document.getElementById('btnGenerateSkus');
            if (btnGen) btnGen.style.display = 'none';
            const qtyLabel = document.getElementById('prodQty')?.parentElement?.querySelector('label');
            if (qtyLabel) qtyLabel.textContent = 'Cantidad Inicial (Stock)';
        } else {
            if (skuModeToggleWrap) skuModeToggleWrap.style.display = 'block';
            if (customColsSection)  customColsSection.style.display  = 'block';
            const btnGen = document.getElementById('btnGenerateSkus');
            if (btnGen) btnGen.style.display = 'inline-flex';
            const qtyLabel = document.getElementById('prodQty')?.parentElement?.querySelector('label');
            if (qtyLabel) qtyLabel.textContent = 'Cantidad a generar';
            setSkuMode(skuMode);
        }
    };

    window.setSkuMode = function(mode) {
        skuMode = mode;
        if (document.getElementById('prodIsBulk').value === '1') return;
        document.getElementById('modeAuto').classList.toggle('active', mode === 'auto');
        document.getElementById('modeManual').classList.toggle('active', mode === 'manual');
        document.getElementById('autoModeWrap').style.display = mode === 'auto' ? 'flex' : 'none';
        document.getElementById('manualModeWrap').style.display = mode === 'manual' ? 'block' : 'none';
        if (mode === 'manual') {
            if (previewSkuCodes.length === 0) { document.getElementById('skuPreviewWrap').style.display = 'none'; }
            else { renderPreviewSkuTable(); }
        }
    };

    window.generateAutoSkus = function() {
        const qty = parseInt(document.getElementById('prodQty').value) || 0;
        if (qty < 1 || qty > 2000) { if (window.showToast) window.showToast('Cantidad entre 1 y 2000', 'error'); return; }
        previewSkuCodes = [];
        previewCustomData = [];
        for (let i = 0; i < qty; i++) {
            previewSkuCodes.push('TRB-' + randomCode(6));
            const obj = {}; customColumns.forEach(c => obj[colObj(c).name] = ''); previewCustomData.push(obj);
        }
        renderPreviewSkuTable();
    };

    window.addManualSkuRow = function() {
        previewSkuCodes.push('');
        const obj = {}; customColumns.forEach(c => obj[colObj(c).name] = ''); previewCustomData.push(obj);
        renderPreviewSkuTable();
        setTimeout(() => {
            const rows = document.querySelectorAll('#skuPreviewBody tr');
            const lastRow = rows[rows.length - 1];
            if (lastRow) { const skuCell = lastRow.querySelector('.inv-editable-sku'); if (skuCell) skuCell.click(); }
        }, 50);
    };

    window.deleteSkuRow = function(idx) {
        previewSkuCodes.splice(idx, 1);
        previewCustomData.splice(idx, 1);
        renderPreviewSkuTable();
    };

    window.editPreviewSkuCode = function(idx, el) {
        const current = previewSkuCodes[idx] || '';
        const input = document.createElement('input');
        input.className = 'inv-editable-input';
        input.value = current;
        input.placeholder = 'TRB-XXXXXX';
        input.style.maxWidth = '150px';
        input.style.fontWeight = '700';
        input.style.fontFamily = 'monospace';
        el.innerHTML = '';
        el.appendChild(input);
        input.focus();
        const save = () => {
            const val = input.value.trim();
            previewSkuCodes[idx] = val;
            el.innerHTML = val ? `<code style="font-weight:700;font-size:0.9rem;">${esc(val)}</code>` : '<em style="color:var(--text-muted);font-size:0.85rem;">Clic para escribir...</em>';
        };
        input.addEventListener('blur', save);
        input.addEventListener('keydown', e => { if (e.key === 'Enter') input.blur(); if (e.key === 'Escape') { input.value = current; input.blur(); } });
    };

    function renderPreviewSkuTable() {
        const wrap = document.getElementById('skuPreviewWrap');
        const body = document.getElementById('skuPreviewBody');
        const head = document.getElementById('skuPreviewHead');
        const title = document.getElementById('skuPreviewTitle');
        const count = document.getElementById('skuPreviewCount');
        const qty = previewSkuCodes.length;
        if (qty === 0) { wrap.style.display = 'none'; return; }
        wrap.style.display = 'block';
        title.textContent = `SKUs: ${qty}`;
        count.textContent = skuMode === 'manual' ? 'Modo manual' : 'Modo automatico';
        const requiresPhotos = document.getElementById('prodRequiresPhotos') ? document.getElementById('prodRequiresPhotos').checked : false;
        const firstProductThumb = (createProductPhotos && createProductPhotos.length > 0) ? URL.createObjectURL(createProductPhotos[0]) : null;
        previewCustomData.forEach(obj => { customColumns.forEach(c => { const key = colObj(c).name; if (!(key in obj)) obj[key] = ''; }); });
        let headHtml = '<tr><th>#</th>';
        if (requiresPhotos) headHtml += '<th style="width:44px;text-align:center;"><i class="ph ph-camera" style="color:#8b5cf6;"></i></th>';
        headHtml += '<th>SKU Code</th><th>Estado</th>';
        customColumns.forEach(c => { headHtml += `<th>${esc(colObj(c).name)}</th>`; });
        headHtml += '<th></th></tr>';
        head.innerHTML = headHtml;
        let rowsHtml = '';
        for (let i = 0; i < qty; i++) {
            const skuVal = previewSkuCodes[i];
            const skuDisplay = skuVal ? `<code style="font-weight:700;font-size:0.9rem;">${esc(skuVal)}</code>` : '<em style="color:var(--text-muted);font-size:0.85rem;">Clic para escribir...</em>';
            rowsHtml += `<tr><td data-label="#">${i+1}</td>`;
            if (requiresPhotos) {
                if (firstProductThumb) {
                    rowsHtml += `<td><img src="${firstProductThumb}" style="width:34px;height:34px;border-radius:8px;object-fit:cover;border:1px solid var(--border-color);display:block;" title="Foto del producto"></td>`;
                } else {
                    rowsHtml += `<td><div title="Sin foto - agregala en la pestana Fotos" style="width:34px;height:34px;border-radius:8px;background:var(--bg-color);border:1.5px dashed var(--border-color);display:flex;align-items:center;justify-content:center;"><i class="ph ph-camera" style="color:var(--text-muted);font-size:0.9rem;opacity:0.45;"></i></div></td>`;
                }
            }
            rowsHtml += `<td data-label="SKU Code"><span class="inv-editable inv-editable-sku" onclick="editPreviewSkuCode(${i},this)" title="Clic para editar">${skuDisplay}</span></td>`;
            rowsHtml += `<td data-label="Estado"><span class="status-badge status-disponible">DISPONIBLE</span></td>`;
            customColumns.forEach(col => {
                const colName = colObj(col).name;
                const val = previewCustomData[i][colName] || '';
                const display = val ? esc(val) : '<em style="color:var(--text-muted)">-</em>';
                rowsHtml += `<td data-label="${esc(colName)}"><div class="inv-cell-scannable"><span class="inv-editable" onclick="editPreviewCell(${i},'${esc(colName)}',this)" title="Clic para editar">${display}</span><button type="button" class="btn-scan-cell" onclick="openScanForCell(${i},'${esc(colName)}')" title="Escanear"><i class="ph ph-qr-code"></i></button></div></td>`;
            });
            rowsHtml += `<td data-label=""><button type="button" class="btn-delete-row" onclick="deleteSkuRow(${i})" title="Eliminar"><i class="ph ph-trash"></i></button></td></tr>`;
        }
        body.innerHTML = rowsHtml;
    }

    window.editPreviewCell = function(rowIdx, col, el) {
        const current = previewCustomData[rowIdx] ? (previewCustomData[rowIdx][col] || '') : '';
        const input = document.createElement('input');
        input.className = 'inv-editable-input';
        input.value = current;
        input.style.maxWidth = '120px';
        el.innerHTML = '';
        el.appendChild(input);
        input.focus();
        const save = () => {
            const val = input.value.trim();
            previewCustomData[rowIdx][col] = val;
            el.innerHTML = val ? esc(val) : '<em style="color:var(--text-muted)">-</em>';
        };
        input.addEventListener('blur', save);
        input.addEventListener('keydown', e => { if (e.key === 'Enter') input.blur(); if (e.key === 'Escape') { input.value = current; input.blur(); } });
    };

    window.openScanForCell = function(rowIdx, col) {
        openSysBarcodeScanner(value => {
            previewCustomData[rowIdx][col] = value;
            renderPreviewSkuTable();
            if (window.showToast) window.showToast('Codigo asignado: ' + value, 'success');
        });
    };

    window.closeProductModal = function() { document.getElementById('newProductModal').classList.remove('active'); };
    window.closeEditProductModal = function() { document.getElementById('editProductModal').classList.remove('active'); };

    // â”€â”€ Editar Producto (Unified Modal) â”€â”€
    let allProductsCache = [];
    let _editProductLoading = false;

    window.openEditProduct = async function(productId, triggerBtn) {
        if (_editProductLoading) return;
        _editProductLoading = true;

        const btn = triggerBtn || null;
        const originalBtnHtml = btn ? btn.innerHTML : null;
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="ph ph-spinner"></i>'; }

        try {
            const fd = new FormData();
            fd.append('action', 'list_products');
            const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fd }).then(r => r.json());
            if (!res.success) { if (window.showToast) window.showToast('Error al cargar productos', 'error'); return; }
            allProductsCache = res.data;
            const prod = res.data.find(p => p.id == productId);
            if (!prod) { if (window.showToast) window.showToast('Producto no encontrado', 'error'); return; }

            document.getElementById('editProductId').value = prod.id;
            document.getElementById('editProductTitle').textContent = prod.name;
            document.getElementById('editProductName').value = prod.name;
            document.getElementById('editProductStockMin').value = prod.stock_minimo || 10;
            document.getElementById('editProductStockCritico').value = prod.stock_critico || 3;
            document.getElementById('editRequiresPhotos').checked = (prod.requires_photos == 1);

            editProductPhotosNew = [];
            editProductPhotosToDelete = [];
            loadEditProductPhotos(prod.id);
            document.getElementById('editMultiPhotoInput').value = '';

            let desc = prod.description || '';
            let extractedAliases = [];
            const aliasMatch = desc.match(/\n?Nombres alternativos:\s*(.*)/i);
            if (aliasMatch) {
                extractedAliases = aliasMatch[1].split(',').map(s => s.trim()).filter(s => s);
                desc = desc.replace(aliasMatch[0], '');
            }
            document.getElementById('editProductDesc').value = desc;
            editCurrentAliases = extractedAliases;
            renderEditAliases();
            const aliasWrap = document.getElementById('editAliasWrap');
            if (extractedAliases.length > 0) {
                aliasWrap.style.display = 'block';
                aliasWrap.previousElementSibling.style.display = 'none';
            } else {
                aliasWrap.style.display = 'none';
                aliasWrap.previousElementSibling.style.display = 'inline-block';
            }

            let parsedCols = [];
            try { parsedCols = prod.custom_columns ? JSON.parse(prod.custom_columns) : []; } catch(ex){}
            editCustomColumns = parsedCols;
            renderEditCustomCols();

            const catFd = new FormData();
            catFd.append('action', 'list_categories');
            const catRes = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: catFd }).then(r => r.json());
            const catSel = document.getElementById('editProductCategory');
            catSel.innerHTML = '<option value="">Sin categoria</option>';
            if (catRes.success) catRes.data.forEach(c => {
                catSel.innerHTML += '<option value="' + c.id + '"' + (c.id == prod.category_id ? ' selected' : '') + '>' + c.name + '</option>';
            });

            document.getElementById('addStockProductId').value = prod.id;
            document.getElementById('addStockProductName').value = prod.name;
            document.getElementById('addStockQuantity').value = 1;
            
            // Lógica para añadir lote en agrupados
            if (prod.product_type === 'agrupado') {
                document.getElementById('addStockNormalWrap').style.display = 'none';
                document.getElementById('addStockAgrupadoWrap').style.display = 'block';
                document.getElementById('addStockVariantsList').innerHTML = '<tr><td colspan="3" style="text-align:center;"><i class="ph ph-spinner ph-spin"></i> Cargando variantes...</td></tr>';
                
                const fdVar = new FormData();
                fdVar.append('action', 'get_children');
                fdVar.append('product_id', prod.id);
                fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fdVar })
                .then(r => r.json())
                .then(res => {
                    if (res.success && res.data.length > 0) {
                        let html = '';
                        res.data.forEach(v => {
                            let attrs = [];
                            if (v.variant_attributes) {
                                for (let key in v.variant_attributes) attrs.push(`${key}: ${v.variant_attributes[key]}`);
                            }
                            let nameStr = v.name + (attrs.length ? ` <small style="color:var(--text-muted);">(${attrs.join(', ')})</small>` : '');
                            html += `<tr>
                                <td>${nameStr}</td>
                                <td>${v.total_quantity || 0}</td>
                                <td><input type="number" class="form-control var-qty-input" data-id="${v.id}" min="0" value="0" style="width:80px;padding:4px 8px;text-align:center;"></td>
                            </tr>`;
                        });
                        document.getElementById('addStockVariantsList').innerHTML = html;
                    } else {
                        document.getElementById('addStockVariantsList').innerHTML = '<tr><td colspan="3" style="text-align:center;color:var(--text-muted);">No hay variantes asignadas</td></tr>';
                    }
                }).catch(e => {
                    document.getElementById('addStockVariantsList').innerHTML = '<tr><td colspan="3" style="text-align:center;color:red;">Error al cargar</td></tr>';
                });
            } else {
                document.getElementById('addStockNormalWrap').style.display = 'block';
                document.getElementById('addStockAgrupadoWrap').style.display = 'none';
            }

            switchEditProductTab('info');

            // Mover el modal al body para escapar cualquier stacking context de overflow
            const modal = document.getElementById('editProductModal');
            if (modal.parentElement !== document.body) {
                document.body.appendChild(modal);
            }
            modal.classList.add('active');

            // Show/hide SKU photo list based on requires_photos
            const skuPhotoListEl = document.getElementById('editSkuPhotoList');
            if (prod.requires_photos == 1) {
                skuPhotoListEl.style.display = 'block';
                loadEditSkuPhotoList(prod.id);
            } else {
                skuPhotoListEl.style.display = 'none';
            }

        } catch(e) {
            console.error('openEditProduct error:', e);
            if (window.showToast) window.showToast('Error al abrir el editor: ' + e.message, 'error');
        } finally {
            _editProductLoading = false;
            if (btn && originalBtnHtml !== null) {
                btn.disabled = false;
                btn.innerHTML = originalBtnHtml;
            }
        }
    }; // end openEditProduct

    // ── Edit modal: toggle requires photos → show/hide SKU list ──
    (function() {
        const cb = document.getElementById('editRequiresPhotos');
        if (cb) {
            cb.addEventListener('change', function() {
                const productId = document.getElementById('editProductId').value;
                const listEl = document.getElementById('editSkuPhotoList');
                if (this.checked && productId) {
                    listEl.style.display = 'block';
                    loadEditSkuPhotoList(productId);
                } else {
                    listEl.style.display = 'none';
                }
            });
        }
    })();

    // ── Load SKU list with per-SKU photo buttons (for edit modal Datos tab) ──
    async function loadEditSkuPhotoList(productId) {
        const body = document.getElementById('editSkuPhotoListBody');
        const countEl = document.getElementById('editSkuPhotoListCount');
        if (!body) return;
        body.innerHTML = '<div style="text-align:center;padding:14px;"><i class="ph ph-spinner ph-spin" style="color:#8b5cf6;font-size:1.4rem;"></i></div>';

        try {
            const fd = new FormData();
            fd.append('action', 'get_product_skus');
            fd.append('product_id', productId);
            const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fd }).then(r => r.json());

            if (res.success && res.data.length > 0) {
                if (countEl) countEl.textContent = res.data.length + ' SKU' + (res.data.length !== 1 ? 's' : '');
                body.innerHTML = res.data.map((s) => {
                    const hasPhoto = !!s.sku_thumbnail;
                    const thumbHtml = hasPhoto
                        ? `<img src="${BASE}/${s.sku_thumbnail}" data-sku-img="${s.id}" class="lb-thumb" data-lb-src="${BASE}/${s.sku_thumbnail}" data-lb-caption="${esc(s.sku_code)}" style="width:36px;height:36px;border-radius:8px;object-fit:cover;border:1px solid var(--border-color);cursor:zoom-in;flex-shrink:0;">`
                        : `<div data-sku-img="${s.id}" style="width:36px;height:36px;border-radius:8px;background:var(--bg-color);border:1.5px dashed var(--border-color);display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="ph ph-camera" style="color:var(--text-muted);font-size:0.9rem;opacity:0.45;"></i></div>`;

                    const statusColors = { disponible:'#10b981', instalado:'#3b82f6', malogrado:'#ef4444', reparado:'#f59e0b', en_transito:'#f59e0b' };
                    const statusColor = statusColors[s.status] || '#888';

                    return `<div style="display:flex;align-items:center;gap:10px;padding:8px 12px;border-bottom:1px solid var(--border-color);transition:background 0.15s;" onmouseover="this.style.background='var(--bg-color)'" onmouseout="this.style.background='transparent'">
                        ${thumbHtml}
                        <div style="flex:1;min-width:0;">
                            <div style="font-weight:700;font-size:0.85rem;font-family:monospace;color:var(--text-color);">${esc(s.sku_code)}</div>
                            <div style="font-size:0.72rem;color:${statusColor};font-weight:600;text-transform:uppercase;">${s.status}</div>
                        </div>
                        <button class="btn-scan-cell" onclick="openSkuPhotoUpload(${s.id}, '${esc(s.sku_code)}')" title="Fotos del SKU" style="color:#8b5cf6;background:rgba(139,92,246,0.08);border-radius:8px;padding:6px;">
                            <i class="ph ph-camera"></i>
                        </button>
                    </div>`;
                }).join('');
            } else {
                if (countEl) countEl.textContent = '0 SKUs';
                body.innerHTML = '<div style="text-align:center;padding:14px;color:var(--text-muted);font-size:0.85rem;"><i class="ph ph-package" style="font-size:1.8rem;display:block;margin-bottom:6px;opacity:0.3;"></i>No hay SKUs en este producto</div>';
            }
        } catch(e) {
            body.innerHTML = '<div style="text-align:center;padding:10px;color:#ef4444;font-size:0.85rem;">Error al cargar SKUs</div>';
        }
    }

    let editCustomColumns = [];
    window.addEditCustomColumnStr = function(name, type = 'text') {
        if (!name) return;
        const exists = editCustomColumns.some(c => colObj(c).name.toLowerCase() === name.toLowerCase());
        if (exists) { if (window.showToast) window.showToast('La columna ya existe', 'error'); return; }
        editCustomColumns.push({ name, type });
        renderEditCustomCols();
    };
    window.addEditCustomColumn = function() {
        const input = document.getElementById('editNewColName');
        const typeEl = document.getElementById('editNewColType');
        const name = input.value.trim();
        const type = typeEl ? typeEl.value : 'text';
        if(!name) { input.focus(); return; }
        addEditCustomColumnStr(name, type);
        input.value = '';
        if (typeEl) typeEl.value = 'text';
    };
    window.removeEditCustomColumn = function(idx) {
        editCustomColumns.splice(idx, 1);
        renderEditCustomCols();
    };

    let editCurrentAliases = [];
    function initEditAliasInput() {
        const input = document.getElementById('editProdAliasInput');
        if(!input) return;
        input.addEventListener('keydown', (e) => {
            if(e.key === 'Enter') {
                e.preventDefault();
                const val = input.value.trim();
                if(val && !editCurrentAliases.includes(val)) {
                    editCurrentAliases.push(val);
                    renderEditAliases();
                    input.value = '';
                }
            }
        });
    }

    function renderEditAliases() {
        const container = document.getElementById('editAliasTagsContainer');
        const hidden = document.getElementById('editProdAliases');
        if(hidden) hidden.value = editCurrentAliases.join(',');
        if(!container) return;
        container.innerHTML = editCurrentAliases.map((a, i) => 
            `<span style="display:inline-flex;align-items:center;gap:4px;background:var(--primary-color);color:#fff;padding:4px 10px;border-radius:12px;font-size:0.8rem;font-weight:600;">
                ${esc(a)} <i class="ph ph-x" style="cursor:pointer;" onclick="removeEditAlias(${i})"></i>
            </span>`
        ).join('');
    }
    
    window.removeEditAlias = function(idx) {
        editCurrentAliases.splice(idx, 1);
        renderEditAliases();
    };

    window.submitEditProduct = async function(btn) {
        let desc = document.getElementById('editProductDesc').value.trim();
        const aliases = document.getElementById('editProdAliases') ? document.getElementById('editProdAliases').value.trim() : '';
        if (aliases) {
            desc = desc ? desc + '\nNombres alternativos: ' + aliases : 'Nombres alternativos: ' + aliases;
        }

        const fd = new FormData();
        fd.append('action', 'update_product');
        fd.append('product_id', document.getElementById('editProductId').value);
        fd.append('name', document.getElementById('editProductName').value);
        fd.append('category_id', document.getElementById('editProductCategory').value);
        fd.append('stock_minimo', document.getElementById('editProductStockMin').value);
        fd.append('stock_critico', document.getElementById('editProductStockCritico').value);
        fd.append('description', desc);
        fd.append('requires_photos', document.getElementById('editRequiresPhotos').checked ? 1 : 0);
        // Append new product photos
        editProductPhotosNew.forEach(f => fd.append('product_photos[]', f));
        // Photos to delete
        if (editProductPhotosToDelete.length > 0) fd.append('delete_photo_ids', JSON.stringify(editProductPhotosToDelete));
        fd.append('custom_columns', JSON.stringify(editCustomColumns));
        
        const saveBtn = btn || document.querySelector('#eptab-info .btn-primary');
        const originalHtml = saveBtn ? saveBtn.innerHTML : '';
        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="ph ph-spinner ph-spin"></i> Guardando...';
        }

        try {
            const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fd }).then(r => r.json());
            if (res.success) {
                if (window.showToast) window.showToast(res.message, 'success');
                closeEditProductModal();
                loadProducts();
            } else {
                if (window.showToast) window.showToast(res.message, 'error');
            }
        } catch (e) { 
            console.error(e);
            if (window.showToast) window.showToast('Error de conexión', 'error'); 
        } finally {
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.innerHTML = originalHtml;
            }
        }
    };

    // Legacy aliases for add stock (still used inside the unified modal)
    window.openAddStock = function(productId, productName) {
        openEditProduct(productId); // open unified modal, user can switch to stock tab
    };
    window.closeAddStockModal = function() {
        closeEditProductModal();
    };

    window.submitAddStock = async function() {
        const productId = document.getElementById('addStockProductId').value;
        const btn = document.getElementById('btnSaveAddStock');
        btn.disabled = true; btn.innerHTML = '<i class="ph ph-spinner ph-spin"></i> Guardando...';
        
        const isAgrupado = document.getElementById('addStockAgrupadoWrap').style.display === 'block';
        
        if (isAgrupado) {
            const inputs = document.querySelectorAll('.var-qty-input');
            let updates = [];
            inputs.forEach(inp => {
                const q = parseInt(inp.value) || 0;
                if (q > 0) updates.push({ id: inp.dataset.id, qty: q });
            });
            
            if (updates.length === 0) {
                if (window.showToast) window.showToast('Ingresa al menos una cantidad mayor a 0', 'error');
                btn.disabled = false; btn.innerHTML = '<i class="ph ph-check"></i> A\u00f1adir Stock';
                return;
            }
            
            const fd = new FormData();
            fd.append('action', 'add_multiple_stock');
            fd.append('updates', JSON.stringify(updates));
            
            try {
                const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fd }).then(r => r.json());
                if (res.success) {
                    if (window.showToast) window.showToast(res.message, 'success');
                    closeEditProductModal();
                    loadProducts();
                    loadMetrics();
                } else {
                    if (window.showToast) window.showToast(res.message, 'error');
                }
            } catch (e) {
                if (window.showToast) window.showToast('Error de conexi\u00f3n', 'error');
            }
        } else {
            const qty = parseInt(document.getElementById('addStockQuantity').value) || 0;
            if (qty < 1) { 
                if (window.showToast) window.showToast('Cantidad inv\u00e1lida', 'error'); 
                btn.disabled = false; btn.innerHTML = '<i class="ph ph-check"></i> A\u00f1adir Stock';
                return; 
            }
            
            const fd = new FormData();
            fd.append('action', 'add_product_stock');
            fd.append('product_id', productId);
            fd.append('quantity', qty);
            
            try {
                const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fd }).then(r => r.json());
                if (res.success) {
                    if (window.showToast) window.showToast(res.message, 'success');
                    closeEditProductModal();
                    loadProducts();
                    loadMetrics();
                } else {
                    if (window.showToast) window.showToast(res.message, 'error');
                }
            } catch (e) {
                if (window.showToast) window.showToast('Error de conexi\u00f3n', 'error');
            }
        }
        btn.disabled = false; btn.innerHTML = '<i class="ph ph-check"></i> A\u00f1adir Stock';
    };

    // ── Stock Control Tab ──
    function initStockTab() {
        document.getElementById('filterStatus').addEventListener('change', loadAllSkus);
        document.getElementById('filterProduct').addEventListener('change', loadAllSkus);
        let st; document.getElementById('searchSku').addEventListener('input', () => { clearTimeout(st); st = setTimeout(loadAllSkus, 400); });
    }

    // ── Sortable columns ──
    let sortColumn = '';
    let sortDirection = 'asc';

    function sortSkus(data, column, dir) {
        return [...data].sort((a, b) => {
            let va = '', vb = '';
            switch(column) {
                case 'sku_code': va = a.sku_code || ''; vb = b.sku_code || ''; break;
                case 'product_name': va = a.product_name || ''; vb = b.product_name || ''; break;
                case 'category_name': va = a.category_name || ''; vb = b.category_name || ''; break;
                case 'status': va = a.status || ''; vb = b.status || ''; break;
                case 'sku_created_at': va = a.sku_created_at || ''; vb = b.sku_created_at || ''; break;
                default: return 0;
            }
            if (dir === 'asc') return va.localeCompare(vb);
            return vb.localeCompare(va);
        });
    }

    let lastSkuData = [];

    async function loadAllSkus() {
        const fd = new FormData();
        fd.append('action', 'list_all_skus');
        fd.append('status', document.getElementById('filterStatus').value);
        fd.append('product_id', document.getElementById('filterProduct').value);
        fd.append('search', document.getElementById('searchSku').value.trim());

        try {
            const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fd }).then(r => r.json());
            const tbody = document.getElementById('skuTableBody');
            const empty = document.getElementById('skuEmpty');
            if (res.success && res.data.length > 0) {
                lastSkuData = res.data;
                let displayData = sortColumn ? sortSkus(res.data, sortColumn, sortDirection) : res.data;
                document.getElementById('skuTable').style.display = '';
                empty.style.display = 'none';
                renderSkuTable(displayData);
            } else { document.getElementById('skuTable').style.display = 'none'; empty.style.display = 'block'; lastSkuData = []; }
        } catch (e) { console.error(e); }
        populateProductFilter();
    }

    // ── Column order management ──
    const STICKY_COUNT = 3; // First 3 columns are pinned
    let columnOrder = null; // will be set on first render
    const STORAGE_KEY = 'inv_col_order';

    function getDefaultColumnOrder(customKeys) {
        const base = ['#','SKU','Producto','Categor\u00eda','Estado','Historia','\u00dalt. Actividad','Instalado a','Asignado','Acci\u00f3n','Fecha Registro'];
        customKeys.forEach(k => { if (!base.includes(k)) base.push(k); });
        return base;
    }

    function loadColumnOrder(customKeys) {
        try {
            const saved = localStorage.getItem(STORAGE_KEY);
            if (saved) {
                const arr = JSON.parse(saved);
                // Ensure all current columns exist
                const def = getDefaultColumnOrder(customKeys);
                def.forEach(c => { if (!arr.includes(c)) arr.push(c); });
                // Remove columns that no longer exist
                return arr.filter(c => def.includes(c));
            }
        } catch(e) {}
        return getDefaultColumnOrder(customKeys);
    }

    function saveColumnOrder() {
        if (columnOrder) localStorage.setItem(STORAGE_KEY, JSON.stringify(columnOrder));
    }

    function renderSkuTable(data) {
        // Determine custom keys from ALL records (not just data[0])
        let customKeysSet = new Set();
        data.forEach(s => {
            try {
                const cd = s.custom_data ? (typeof s.custom_data === 'string' ? JSON.parse(s.custom_data) : s.custom_data) : {};
                if (cd && typeof cd === 'object' && !Array.isArray(cd)) {
                    Object.keys(cd).forEach(k => customKeysSet.add(k));
                }
            } catch(e) {}
        });
        let customKeys = [...customKeysSet];
        if (!columnOrder) columnOrder = loadColumnOrder(customKeys);
        // Ensure new custom keys are included
        customKeys.forEach(k => { if (!columnOrder.includes(k)) columnOrder.push(k); });

        const si = (col) => {
            if (sortColumn !== col) return '<i class="ph ph-caret-up-down" style="opacity:0.3;"></i>';
            return sortDirection === 'asc' ? '<i class="ph ph-caret-up"></i>' : '<i class="ph ph-caret-down"></i>';
        };

        // Build cell map for each row
        const tbody = document.getElementById('skuTableBody');
        const rows = data.map((s, i) => {
            const cd = s.custom_data ? (typeof s.custom_data === 'string' ? JSON.parse(s.custom_data) : s.custom_data) : {};
            const hist = s.historia || 'ninguno';
            const assignedHtml = s.assigned_user_name
                ? `<span class="assigned-cell"><i class="ph ph-user"></i> ${esc(s.assigned_user_name)}</span>`
                : '<span style="color:var(--text-muted);font-size:0.82rem;">\u2014</span>';
            const lastHistDate = s.last_history_date || '<span style="color:var(--text-muted);">\u2014</span>';
            const instaladoA = s.acta_cliente ? `<i class="ph ph-user"></i> ${esc(s.acta_cliente)}` : '<span style="color:var(--text-muted);">\u2014</span>';
            const fechaReg = s.sku_created_at ? new Date(s.sku_created_at).toLocaleString('es-PE',{day:'2-digit',month:'2-digit',year:'numeric',hour:'2-digit',minute:'2-digit'}) : '\u2014';

            let bulkAssignHtml = '';
            if (s.product_type === 'agrupado') {
                // Agrupado: show variant stock summary instead of generic warehouse badge
                bulkAssignHtml = `<div class="assign-badge none" style="background:rgba(139,92,246,0.08);border-color:rgba(139,92,246,0.3);color:#8b5cf6;font-size:0.8rem;">
                    <i class="ph ph-stack"></i> ${s.stock_disponible} unidades en variantes
                </div>`;
                if (s.bulk_assignments) {
                    bulkAssignHtml += `<div style="margin-top:6px;font-size:0.8rem;color:var(--text-muted);line-height:1.4;">${s.bulk_assignments}</div>`;
                }
            } else {
                bulkAssignHtml = `<div class="assign-badge none"><i class="ph ph-package"></i> Almac\u00e9n (${s.stock_disponible} ${s.unit_type||''})</div>`;
                if (s.is_bulk && s.bulk_assignments) {
                    bulkAssignHtml += `<div style="margin-top:6px;font-size:0.8rem;color:var(--text-muted);line-height:1.4;">${s.bulk_assignments}</div>`;
                }
            }

            let cellMap = {
                '#': `<td>${i+1}</td>`,
                'SKU': s.is_bulk ? `<td><code style=\"font-weight:700;\">${s.sku_code}</code></td>` : `<td><span class=\"inv-editable\" onclick=\"editSkuCode(${s.id}, this)\" title=\"Clic para editar\"><code style=\"font-weight:700;\">${s.sku_code}</code></span></td>`,
                'Producto': `<td><div style="display:flex;align-items:center;gap:8px;">${
                    s.product_type === 'agrupado'
                        ? `<button class="accordion-toggle-btn" onclick="toggleSkuChildrenRows(${s.product_id}, this)" title="Expandir variantes" style="flex-shrink:0;"><i class="ph ph-caret-right"></i></button>`
                        : ''
                }${
                    s.sku_thumbnail
                        ? `<img src="${BASE}/${s.sku_thumbnail}" data-sku-img="${s.id}" class="lb-thumb" data-lb-src="${BASE}/${s.sku_thumbnail}" data-lb-caption="${esc(s.product_name)}" style="width:36px;height:36px;border-radius:8px;object-fit:cover;flex-shrink:0;border:1px solid var(--border-color);cursor:zoom-in;">`
                        : `<div data-sku-img="${s.id}" style="width:36px;height:36px;border-radius:8px;background:var(--bg-color);display:flex;align-items:center;justify-content:center;flex-shrink:0;border:1px solid var(--border-color);"><i class="ph ph-image" style="color:var(--text-muted);font-size:1rem;opacity:0.4;"></i></div>`
                }<span class="sku-product-name">${esc(s.product_name)}</span>${s.is_bulk ? (s.product_type === 'agrupado' ? '<br><small style="color:#8b5cf6;font-size:0.75rem;"><i class="ph ph-stack"></i> Agrupado</small>' : '<br><small style="color:var(--text-muted);font-size:0.75rem;"><i class="ph ph-package"></i> Granel</small>') : ''}</div></td>`,
                'Categor\u00eda': `<td>${esc(s.category_name||'\u2014')}</td>`,
                'Estado': `<td><span class=\"status-badge status-${s.status}\">${s.status.toUpperCase()}</span></td>`,
                'Historia': s.is_bulk ? `<td><span style=\"color:var(--text-muted);\">\u2014</span></td>` : `<td><select class=\"status-select historia-select\" onchange=\"updateHistoria(${s.id}, this.value)\"><option value=\"ninguno\" ${hist==='ninguno'?'selected':''}>\u2014</option><option value=\"devuelto\" ${hist==='devuelto'?'selected':''}>Devuelto</option><option value=\"malogrado\" ${hist==='malogrado'?'selected':''}>Malogrado</option><option value=\"antiguo\" ${hist==='antiguo'?'selected':''}>Antiguo</option><option value=\"en_transito\" ${hist==='en_transito'?'selected':''}>En Tr\u00e1nsito</option></select></td>`,
                '\u00dalt. Actividad': s.is_bulk ? `<td><span style=\"color:var(--text-muted);\">\u2014</span></td>` : `<td>${lastHistDate}</td>`,
                'Instalado a': s.is_bulk ? `<td><span style=\"color:var(--text-muted);\">\u2014</span></td>` : `<td>${instaladoA}</td>`,
                'Asignado': s.is_bulk ? `<td>${bulkAssignHtml}</td>` : `<td>${assignedHtml}</td>`,
                'Acci\u00f3n': s.is_bulk ? `<td><span style=\"color:var(--text-muted);\">\u2014</span></td>` : `<td><div style=\"display:flex;gap:4px;align-items:center;\"><select class=\"status-select\" onchange=\"updateSkuStatus(${s.id}, this.value)\"><option value=\"disponible\" ${s.status==='disponible'?'selected':''}>Disponible</option><option value=\"instalado\" ${s.status==='instalado'?'selected':''}>Instalado</option><option value=\"malogrado\" ${s.status==='malogrado'?'selected':''}>Malogrado</option><option value=\"reparado\" ${s.status==='reparado'?'selected':''}>Reparado</option><option value=\"en_transito\" ${s.status==='en_transito'?'selected':''}>En Tr\u00e1nsito</option></select><button class=\"btn-scan-cell\" onclick=\"openSkuPhotoUpload(${s.id}, '${esc(s.sku_code)}')\" title=\"Fotos del SKU\" style=\"color:#8b5cf6;\"><i class=\"ph ph-camera\"></i></button></div></td>`,
                'Fecha Registro': `<td>${fechaReg}</td>`
            };
            customKeys.forEach(key => {
                const val = esc(cd[key]) || '<em style="color:var(--text-muted)">\u2014</em>';
                if (s.is_bulk) {
                    cellMap[key] = `<td><div class="inv-cell-scannable"><span class="inv-editable" onclick="editBulkCustom(${s.product_id}, '${esc(key)}', this)" title="Clic para editar">${val}</span><button class="btn-scan-cell" onclick="scanForBulkField(${s.product_id}, '${esc(key)}', this)" title="Escanear"><i class="ph ph-qr-code"></i></button></div></td>`;
                } else {
                    cellMap[key] = `<td><div class="inv-cell-scannable"><span class="inv-editable" onclick="editSkuCustom(${s.id}, '${esc(key)}', this)" title="Clic para editar">${val}</span><button class="btn-scan-cell" onclick="scanForCustomField(${s.id}, '${esc(key)}', this)" title="Escanear"><i class="ph ph-qr-code"></i></button></div></td>`;
                }
            });
            return cellMap;
        });

        tbody.innerHTML = rows.map(cellMap => {
            const cells = columnOrder.map((col, ci) => {
                let cell = cellMap[col] || '<td>\u2014</td>';
                if (ci < STICKY_COUNT) cell = cell.replace('<td', `<td class="sticky-col sticky-col-${ci}"`);
                return cell;
            }).join('');
            return `<tr>${cells}</tr>`;
        }).join('');

        // Render headers in column order
        const sortableColumns = {'SKU':'sku_code','Producto':'product_name','Categor\u00eda':'category_name','Estado':'status','Fecha Registro':'sku_created_at'};
        const thead = document.querySelector('#skuTable thead tr');
        thead.innerHTML = columnOrder.map((col, ci) => {
            const sortKey = sortableColumns[col];
            const sortHtml = sortKey ? ` ${si(sortKey)}` : '';
            const sortClass = sortKey ? ' sortable-th' : '';
            const sortClick = sortKey ? ` onclick="toggleSort('${sortKey}')"` : '';
            const stickyClass = ci < STICKY_COUNT ? ` sticky-col sticky-col-${ci} sticky-th` : '';
            const dragAttr = `draggable="true" data-colidx="${ci}"`;
            return `<th class="draggable-th${sortClass}${stickyClass}"${sortClick} ${dragAttr}>${col}${sortHtml}</th>`;
        }).join('');

        // Setup drag-and-drop on headers
        setupColumnDragDrop();
    }

    // ── Column drag-and-drop ──
    // ── Toggle variant rows inside SKU table (Control de Stock) ──
    window.toggleSkuChildrenRows = async function(productId, btn) {
        const parentRow = btn.closest('tr');
        const icon = btn.querySelector('i');
        const isExpanded = parentRow.dataset.skuExpanded === '1';

        if (isExpanded) {
            parentRow.dataset.skuExpanded = '0';
            icon.className = 'ph ph-caret-right';
            let next = parentRow.nextElementSibling;
            while (next && next.classList.contains('variant-child-row')) {
                const toRemove = next; next = next.nextElementSibling; toRemove.remove();
            }
            return;
        }

        icon.className = 'ph ph-spinner ph-spin';
        try {
            const fd = new FormData();
            fd.append('action', 'get_children');
            fd.append('product_id', productId);
            const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fd }).then(r => r.json());
            if (res.success && res.data.length > 0) {
                parentRow.dataset.skuExpanded = '1';
                icon.className = 'ph ph-caret-down';
                const colCount = document.querySelectorAll('#skuTable thead th').length;
                const fragment = document.createDocumentFragment();
                res.data.forEach((child, idx) => {
                    const tr = document.createElement('tr');
                    tr.className = 'variant-child-row';
                    tr.style.cssText = `animation:fadeIn 0.2s ease forwards;animation-delay:${idx*0.05}s;opacity:0;`;
                    const attrs = child.variant_attributes || {};
                    const attrBadges = Object.entries(attrs).map(([k,v]) => v ? `<span class="variant-attr-badge"><i class="ph ph-tag"></i> ${esc(k)}: ${esc(v)}</span>` : '').join('');
                    const qtyTotal     = parseFloat(child.total_quantity || 0);
                    const qtyDisp      = child.qty_disponible != null ? parseFloat(child.qty_disponible) : qtyTotal;
                    const qtyInst      = child.qty_instalado  != null ? parseFloat(child.qty_instalado)  : 0;
                    const qtyMalogrado = child.qty_malogrado  != null ? parseFloat(child.qty_malogrado)  : 0;
                    // Fill empty cells for extra columns in the middle
                    const emptyCells = Array(Math.max(0, colCount - 7)).fill('<td></td>').join('');
                    tr.innerHTML = `
                        <td></td>
                        <td>
                            <div style="display:flex;align-items:center;gap:8px;padding-left:20px;">
                                <span style="color:var(--text-muted);font-size:1rem;flex-shrink:0;">└</span>
                                <div>
                                    <div style="font-weight:600;color:var(--text-color);font-size:0.88rem;">${esc(child.name)}</div>
                                    <div style="display:flex;gap:4px;flex-wrap:wrap;margin-top:3px;">${attrBadges}</div>
                                </div>
                            </div>
                        </td>
                        ${emptyCells}
                        <td><span style="font-weight:700;color:#6366f1;">${qtyTotal}</span></td>
                        <td><span style="font-weight:700;color:#10b981;">${qtyDisp}</span></td>
                        <td><span style="font-weight:700;color:#3b82f6;">${qtyInst}</span></td>
                        <td><span style="font-weight:700;color:#ef4444;">${qtyMalogrado}</span></td>
                        <td></td>`;
                    fragment.appendChild(tr);
                });
                parentRow.after(fragment);
            }
        } catch(e) { console.error(e); }
        if (icon.className.includes('spinner')) icon.className = 'ph ph-caret-right';
    };

    let dragColIdx = null;
    function setupColumnDragDrop() {
        const ths = document.querySelectorAll('#skuTable thead th.draggable-th');
        ths.forEach(th => {
            th.addEventListener('dragstart', (e) => {
                dragColIdx = parseInt(th.dataset.colidx);
                th.classList.add('dragging-th');
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', dragColIdx);
            });
            th.addEventListener('dragend', () => {
                th.classList.remove('dragging-th');
                document.querySelectorAll('#skuTable thead th').forEach(h => h.classList.remove('drag-over-th'));
            });
            th.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                th.classList.add('drag-over-th');
            });
            th.addEventListener('dragleave', () => {
                th.classList.remove('drag-over-th');
            });
            th.addEventListener('drop', (e) => {
                e.preventDefault();
                th.classList.remove('drag-over-th');
                const dropIdx = parseInt(th.dataset.colidx);
                if (dragColIdx !== null && dragColIdx !== dropIdx && columnOrder) {
                    const moved = columnOrder.splice(dragColIdx, 1)[0];
                    columnOrder.splice(dropIdx, 0, moved);
                    saveColumnOrder();
                    if (lastSkuData.length > 0) {
                        const displayData = sortColumn ? sortSkus(lastSkuData, sortColumn, sortDirection) : lastSkuData;
                        renderSkuTable(displayData);
                    }
                }
                dragColIdx = null;
            });
        });
    }

    window.toggleSort = function(col) {
        if (sortColumn === col) {
            sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            sortColumn = col;
            sortDirection = 'asc';
        }
        if (lastSkuData.length > 0) {
            renderSkuTable(sortSkus(lastSkuData, sortColumn, sortDirection));
        }
    };

    async function populateProductFilter() {
        const sel = document.getElementById('filterProduct');
        if (sel.options.length > 1) return;
        const fd = new FormData(); fd.append('action', 'list_products');
        const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fd }).then(r => r.json());
        if (res.success) { const cur = sel.value; sel.innerHTML = '<option value="">Todos los productos</option>'; res.data.forEach(p => { sel.innerHTML += `<option value="${p.id}">${esc(p.name)}</option>`; }); sel.value = cur; }
    }

    window.updateSkuStatus = async function(skuId, status) {
        const fd = new FormData(); fd.append('action', 'update_sku_status'); fd.append('sku_id', skuId); fd.append('status', status);
        const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fd }).then(r => r.json());
        if (res.success) { if (window.showToast) window.showToast('Estado actualizado', 'success'); loadAllSkus(); loadMetrics(); loadProducts(); }
    };

    // ── Inline Edit SKU Code (floating popover with ✓/✗ buttons) ──
    window.editSkuCode = function(skuId, el) {
        // Remove any existing popover first
        document.querySelectorAll('.inline-edit-popover').forEach(p => p.remove());
        document.querySelectorAll('.inv-cell-editing').forEach(c => c.classList.remove('inv-cell-editing'));

        const current = el.textContent.trim();
        const td = el.closest('td');

        td.classList.add('inv-cell-editing');

        // Create floating popover on body (to escape table overflow)
        const popover = document.createElement('div');
        popover.className = 'inline-edit-popover';
        popover.style.position = 'fixed';

        const input = document.createElement('input');
        input.type = 'text';
        input.value = current;
        input.placeholder = 'Código SKU...';

        const saveBtn = document.createElement('button');
        saveBtn.className = 'inline-edit-btn btn-save';
        saveBtn.innerHTML = '<i class="ph ph-check"></i>';
        saveBtn.title = 'Guardar';

        const cancelBtn = document.createElement('button');
        cancelBtn.className = 'inline-edit-btn btn-delete';
        cancelBtn.innerHTML = '<i class="ph ph-x"></i>';
        cancelBtn.title = 'Cancelar';

        popover.appendChild(input);
        popover.appendChild(saveBtn);
        popover.appendChild(cancelBtn);
        document.body.appendChild(popover);

        // Position relative to cell
        const positionPopover = () => {
            const rect = td.getBoundingClientRect();
            popover.style.left = (rect.left + rect.width / 2) + 'px';
            popover.style.top = (rect.top + rect.height / 2) + 'px';
        };
        positionPopover();

        input.focus();
        input.select();

        const cleanup = () => {
            td.classList.remove('inv-cell-editing');
            popover.remove();
            document.removeEventListener('mousedown', outsideHandler);
        };

        const save = async () => {
            const val = input.value.trim();
            cleanup();
            if (!val || val === current) { el.innerHTML = `<code style="font-weight:700;">${esc(current)}</code>`; return; }
            el.innerHTML = '<i class="ph ph-spinner ph-spin" style="color:var(--primary-color);"></i>';
            const fd = new FormData(); fd.append('action', 'update_sku_code'); fd.append('sku_id', skuId); fd.append('sku_code', val);
            const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fd }).then(r => r.json());
            if (res.success) { if (window.showToast) window.showToast('SKU actualizado', 'success'); loadAllSkus(); }
            else { if (window.showToast) window.showToast(res.message, 'error'); el.innerHTML = `<code style="font-weight:700;">${esc(current)}</code>`; }
        };

        const cancel = () => {
            cleanup();
            el.innerHTML = `<code style="font-weight:700;">${esc(current)}</code>`;
        };

        saveBtn.onclick = (e) => { e.stopPropagation(); save(); };
        cancelBtn.onclick = (e) => { e.stopPropagation(); cancel(); };
        input.addEventListener('keydown', e => {
            if (e.key === 'Enter') { e.preventDefault(); save(); }
            if (e.key === 'Escape') { e.preventDefault(); cancel(); }
        });

        const outsideHandler = (e) => {
            if (!popover.contains(e.target) && e.target !== el) {
                cancel();
            }
        };
        setTimeout(() => document.addEventListener('mousedown', outsideHandler), 100);
    };

    // ── Inline Edit SKU Custom Data (floating popover with ✓/✗ buttons) ──
    window.editSkuCustom = function(skuId, key, el) {
        // Remove any existing popover first
        document.querySelectorAll('.inline-edit-popover').forEach(p => p.remove());
        document.querySelectorAll('.inv-cell-editing').forEach(c => c.classList.remove('inv-cell-editing'));

        const current = el.textContent.trim() === '—' ? '' : el.textContent.trim();
        const td = el.closest('td');

        // Add editing state to the cell
        td.classList.add('inv-cell-editing');

        // Create floating popover on body (to escape table overflow)
        const popover = document.createElement('div');
        popover.className = 'inline-edit-popover';
        popover.style.position = 'fixed';

        const input = document.createElement('input');
        input.type = 'text';
        input.value = current;
        input.placeholder = key + '...';

        // Scan button
        const scanBtn = document.createElement('button');
        scanBtn.className = 'inline-edit-btn btn-scan';
        scanBtn.innerHTML = '<i class="ph ph-qr-code"></i>';
        scanBtn.title = 'Escanear código';
        scanBtn.onclick = (e) => {
            e.stopPropagation();
            scanPickerCallback = (code) => {
                input.value = code;
                input.focus();
            };
            scanPickerDetected = [];
            document.getElementById('scanPickerResults').style.display = 'none';
            document.getElementById('scanPickerList').innerHTML = '';
            document.getElementById('scanPickerManual').value = '';
            document.getElementById('scanPickerStatus').innerHTML = '<i class="ph ph-camera"></i> Apunta la cámara al código...';
            const spm1 = document.getElementById('scanPickerModal');
            if (spm1.parentElement !== document.body) document.body.appendChild(spm1);
            spm1.classList.add('active');
            startScanPicker();
        };

        // Save button (✓)
        const saveBtn = document.createElement('button');
        saveBtn.className = 'inline-edit-btn btn-save';
        saveBtn.innerHTML = '<i class="ph ph-check"></i>';
        saveBtn.title = 'Guardar';

        // Cancel button (✗)
        const cancelBtn = document.createElement('button');
        cancelBtn.className = 'inline-edit-btn btn-delete';
        cancelBtn.innerHTML = '<i class="ph ph-x"></i>';
        cancelBtn.title = 'Cancelar';

        popover.appendChild(input);
        popover.appendChild(scanBtn);
        popover.appendChild(saveBtn);
        popover.appendChild(cancelBtn);
        document.body.appendChild(popover);

        // Position relative to cell
        const positionPopover = () => {
            const rect = td.getBoundingClientRect();
            popover.style.left = (rect.left + rect.width / 2) + 'px';
            popover.style.top = (rect.top + rect.height / 2) + 'px';
        };
        positionPopover();

        input.focus();
        input.select();

        const cleanup = () => {
            td.classList.remove('inv-cell-editing');
            popover.remove();
            document.removeEventListener('mousedown', outsideHandler);
        };

        const save = async () => {
            const val = input.value.trim();
            cleanup();
            // Show saving indicator
            el.innerHTML = '<i class="ph ph-spinner ph-spin" style="color:var(--primary-color);"></i>';
            const row = el.closest('tr');
            const skuCode = row.querySelector('code')?.textContent || '';
            const fd1 = new FormData(); fd1.append('action', 'search_sku'); fd1.append('code', skuCode);
            const r1 = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fd1 }).then(r => r.json());
            let cd = {};
            if (r1.success && r1.data.custom_data) { cd = typeof r1.data.custom_data === 'string' ? JSON.parse(r1.data.custom_data) : r1.data.custom_data; }
            cd[key] = val;
            const fd = new FormData(); fd.append('action', 'update_sku_custom'); fd.append('sku_id', skuId); fd.append('custom_data', JSON.stringify(cd));
            const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fd }).then(r => r.json());
            if (res.success) {
                el.innerHTML = val ? esc(val) : '<em style=color:var(--text-muted)>—</em>';
                if (window.showToast) window.showToast('Campo actualizado', 'success');
            } else {
                el.innerHTML = current ? esc(current) : '<em style=color:var(--text-muted)>—</em>';
                if (window.showToast) window.showToast('Error al guardar', 'error');
            }
        };

        const cancel = () => {
            cleanup();
            el.innerHTML = current ? esc(current) : '<em style=color:var(--text-muted)>—</em>';
        };

        saveBtn.onclick = (e) => { e.stopPropagation(); save(); };
        cancelBtn.onclick = (e) => { e.stopPropagation(); cancel(); };
        input.addEventListener('keydown', e => {
            if (e.key === 'Enter') { e.preventDefault(); save(); }
            if (e.key === 'Escape') { e.preventDefault(); cancel(); }
        });

        // Close on click outside
        const outsideHandler = (e) => {
            if (!popover.contains(e.target) && e.target !== el) {
                cancel();
            }
        };
        setTimeout(() => document.addEventListener('mousedown', outsideHandler), 100);
    };


    // ── Scan button in table cell for custom columns ──
    window.scanForCustomField = function(skuId, key, btnEl) {
        scanPickerCallback = async (code) => {
            // Get current row's SKU code
            const row = btnEl.closest('tr');
            const skuCode = row.querySelector('code')?.textContent || '';
            // Fetch current custom_data
            const fd1 = new FormData(); fd1.append('action', 'search_sku'); fd1.append('code', skuCode);
            const r1 = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fd1 }).then(r => r.json());
            let cd = {};
            if (r1.success && r1.data.custom_data) { cd = typeof r1.data.custom_data === 'string' ? JSON.parse(r1.data.custom_data) : r1.data.custom_data; }
            cd[key] = code;
            // Save
            const fd = new FormData(); fd.append('action', 'update_sku_custom'); fd.append('sku_id', skuId); fd.append('custom_data', JSON.stringify(cd));
            const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fd }).then(r => r.json());
            if (res.success) {
                if (window.showToast) window.showToast('Campo actualizado con escaneo', 'success');
                loadAllSkus();
            } else {
                if (window.showToast) window.showToast('Error al guardar', 'error');
            }
        };
        scanPickerDetected = [];
        document.getElementById('scanPickerResults').style.display = 'none';
        document.getElementById('scanPickerList').innerHTML = '';
        document.getElementById('scanPickerManual').value = '';
        document.getElementById('scanPickerStatus').innerHTML = '<i class="ph ph-camera"></i> Apunta la c\u00e1mara al c\u00f3digo...';
        const spm2 = document.getElementById('scanPickerModal');
        if (spm2.parentElement !== document.body) document.body.appendChild(spm2);
        spm2.classList.add('active');
        startScanPicker();
    };

    // ── Inline Edit BULK Product Custom Data (floating popover with ✓/✗ buttons) ──
    window.editBulkCustom = function(productId, key, el) {
        // Remove any existing popover first
        document.querySelectorAll('.inline-edit-popover').forEach(p => p.remove());
        document.querySelectorAll('.inv-cell-editing').forEach(c => c.classList.remove('inv-cell-editing'));

        const current = el.textContent.trim() === '—' ? '' : el.textContent.trim();
        const td = el.closest('td');

        td.classList.add('inv-cell-editing');

        const popover = document.createElement('div');
        popover.className = 'inline-edit-popover';
        popover.style.position = 'fixed';

        const input = document.createElement('input');
        input.type = 'text';
        input.value = current;
        input.placeholder = key + '...';

        const scanBtn = document.createElement('button');
        scanBtn.className = 'inline-edit-btn btn-scan';
        scanBtn.innerHTML = '<i class="ph ph-qr-code"></i>';
        scanBtn.title = 'Escanear código';
        scanBtn.onclick = (e) => {
            e.stopPropagation();
            scanPickerCallback = (code) => { input.value = code; input.focus(); };
            scanPickerDetected = [];
            document.getElementById('scanPickerResults').style.display = 'none';
            document.getElementById('scanPickerList').innerHTML = '';
            document.getElementById('scanPickerManual').value = '';
            document.getElementById('scanPickerStatus').innerHTML = '<i class="ph ph-camera"></i> Apunta la cámara al código...';
            const spm1 = document.getElementById('scanPickerModal');
            if (spm1.parentElement !== document.body) document.body.appendChild(spm1);
            spm1.classList.add('active');
            startScanPicker();
        };

        const saveBtn = document.createElement('button');
        saveBtn.className = 'inline-edit-btn btn-save';
        saveBtn.innerHTML = '<i class="ph ph-check"></i>';
        saveBtn.title = 'Guardar';

        const cancelBtn = document.createElement('button');
        cancelBtn.className = 'inline-edit-btn btn-delete';
        cancelBtn.innerHTML = '<i class="ph ph-x"></i>';
        cancelBtn.title = 'Cancelar';

        popover.appendChild(input);
        popover.appendChild(scanBtn);
        popover.appendChild(saveBtn);
        popover.appendChild(cancelBtn);
        document.body.appendChild(popover);

        const positionPopover = () => {
            const rect = td.getBoundingClientRect();
            popover.style.left = (rect.left + rect.width / 2) + 'px';
            popover.style.top = (rect.top + rect.height / 2) + 'px';
        };
        positionPopover();

        input.focus();
        input.select();

        const cleanup = () => {
            td.classList.remove('inv-cell-editing');
            popover.remove();
            document.removeEventListener('mousedown', outsideHandler);
        };

        const save = async () => {
            const val = input.value.trim();
            cleanup();
            el.innerHTML = '<i class="ph ph-spinner ph-spin" style="color:var(--primary-color);"></i>';
            // Fetch current bulk_custom_data from the row data
            const row = el.closest('tr');
            // Find the matching item in lastSkuData
            let cd = {};
            if (lastSkuData) {
                const bulkItem = lastSkuData.find(s => s.is_bulk && s.product_id == productId);
                if (bulkItem && bulkItem.custom_data) {
                    cd = typeof bulkItem.custom_data === 'string' ? JSON.parse(bulkItem.custom_data) : bulkItem.custom_data;
                    if (Array.isArray(cd)) cd = {};
                }
            }
            cd[key] = val;
            const fd = new FormData();
            fd.append('action', 'update_bulk_custom');
            fd.append('product_id', productId);
            fd.append('custom_data', JSON.stringify(cd));
            const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fd }).then(r => r.json());
            if (res.success) {
                el.innerHTML = val ? esc(val) : '<em style=color:var(--text-muted)>—</em>';
                // Update the cached data
                if (lastSkuData) {
                    const bulkItem = lastSkuData.find(s => s.is_bulk && s.product_id == productId);
                    if (bulkItem) bulkItem.custom_data = JSON.stringify(cd);
                }
                if (window.showToast) window.showToast('Campo actualizado', 'success');
            } else {
                el.innerHTML = current ? esc(current) : '<em style=color:var(--text-muted)>—</em>';
                if (window.showToast) window.showToast('Error al guardar', 'error');
            }
        };

        const cancel = () => {
            cleanup();
            el.innerHTML = current ? esc(current) : '<em style=color:var(--text-muted)>—</em>';
        };

        saveBtn.onclick = (e) => { e.stopPropagation(); save(); };
        cancelBtn.onclick = (e) => { e.stopPropagation(); cancel(); };
        input.addEventListener('keydown', e => {
            if (e.key === 'Enter') { e.preventDefault(); save(); }
            if (e.key === 'Escape') { e.preventDefault(); cancel(); }
        });

        const outsideHandler = (e) => {
            if (!popover.contains(e.target) && e.target !== el) {
                cancel();
            }
        };
        setTimeout(() => document.addEventListener('mousedown', outsideHandler), 100);
    };

    // ── Scan button for BULK product custom columns ──
    window.scanForBulkField = function(productId, key, btnEl) {
        if (typeof openSysBarcodeScanner !== 'function') {
            if (window.showToast) window.showToast('Escáner no disponible', 'error');
            return;
        }
        openSysBarcodeScanner(async function(code) {
            let cd = {};
            if (lastSkuData) {
                const bulkItem = lastSkuData.find(s => s.is_bulk && s.product_id == productId);
                if (bulkItem && bulkItem.custom_data) {
                    cd = typeof bulkItem.custom_data === 'string' ? JSON.parse(bulkItem.custom_data) : bulkItem.custom_data;
                    if (Array.isArray(cd)) cd = {};
                }
            }
            cd[key] = code;
            const fd = new FormData();
            fd.append('action', 'update_bulk_custom');
            fd.append('product_id', productId);
            fd.append('custom_data', JSON.stringify(cd));
            const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fd }).then(r => r.json());
            if (res.success) {
                if (window.showToast) window.showToast('Campo actualizado con escaneo', 'success');
                loadAllSkus();
            } else {
                if (window.showToast) window.showToast('Error al guardar', 'error');
            }
        });
    };

    // ── Labels Tab ──
    async function populateLabelProducts() {
        const sel = document.getElementById('labelProduct');
        const fd = new FormData(); fd.append('action', 'list_products');
        const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fd }).then(r => r.json());
        if (res.success) { sel.innerHTML = '<option value="">Seleccionar producto...</option>'; res.data.forEach(p => { sel.innerHTML += `<option value="${p.id}">${esc(p.name)} (${p.total_quantity} uds)</option>`; }); }
    }
    function initLabelsTab() { document.getElementById('btnGenLabels').addEventListener('click', generateLabels); }

    async function generateLabels() {
        const productId = document.getElementById('labelProduct').value;
        const labelType = document.getElementById('labelType').value;
        const preview = document.getElementById('labelPreview');
        if (!productId) { if (window.showToast) window.showToast('Selecciona un producto', 'error'); return; }
        const fd = new FormData(); fd.append('action', 'get_product_skus'); fd.append('product_id', productId);
        const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fd }).then(r => r.json());
        if (!res.success || !res.data.length) { preview.innerHTML = '<div class="empty-state">No hay SKUs.</div>'; return; }

        let html = '<div class="label-sheet">';
        res.data.forEach(s => {
            const id = 'lbl-' + s.id;
            html += labelType === 'barcode'
                ? `<div class="label-item"><svg id="${id}"></svg><div class="label-sku-text">${s.sku_code}</div></div>`
                : `<div class="label-item"><div id="${id}" style="margin:0 auto;"></div><div class="label-sku-text">${s.sku_code}</div></div>`;
        });
        html += '</div>';
        preview.innerHTML = html;

        requestAnimationFrame(() => {
            res.data.forEach(s => {
                const id = 'lbl-' + s.id, el = document.getElementById(id);
                if (!el) return;
                if (labelType === 'barcode') { try { JsBarcode('#'+id, s.sku_code, { format:'CODE128', width:1.2, height:35, displayValue:false, margin:2 }); } catch(e){} }
                else { try { new QRCode(el, { text: s.sku_code, width:60, height:60, colorDark:'#000', colorLight:'#fff', correctLevel: QRCode.CorrectLevel.M }); } catch(e){} }
            });
        });
        document.getElementById('btnPrint').style.display = '';
    }
    // ── SKU Detail Modal ──────────────────────────────────
    let currentSkuData = null;
    window.currentSkuChildren = [];
    let entryPhotoFiles = [];

    function openSkuDetailModal(data) {
        currentSkuData = data;
        document.getElementById('skuDetailTitle').textContent = data.sku_code + ' — ' + data.product_name;

        // ── Imagen del producto en el header ──
        const headerImg = document.getElementById('skuDetailHeaderImg');
        const statusColors = { disponible:'#10b981', instalado:'#3b82f6', malogrado:'#ef4444', reparado:'#f59e0b', en_transito:'#8b5cf6' };
        const status = data.status || 'disponible';
        const col = statusColors[status] || '#6366f1';
        const thumbSrc = data.sku_thumbnail || data.product_image; // prefer SKU-specific photo
        if (thumbSrc) {
            headerImg.innerHTML = `<img src="${BASE}/${thumbSrc}" class="lb-thumb" data-lb-src="${BASE}/${thumbSrc}" data-lb-caption="${esc(data.product_name)}" style="width:100%;height:100%;object-fit:cover;cursor:zoom-in;" alt="${esc(data.product_name)}">`;
            headerImg.style.background = 'transparent';
            headerImg.style.border = '2px solid ' + col;
        } else {
            headerImg.innerHTML = `<i class="ph ph-package" style="font-size:1.6rem;color:${col};"></i>`;
            headerImg.style.background = col + '18';
            headerImg.style.border = '1px solid ' + col + '44';
        }

        // Reset the EPP checkbox
        const eppCheck = document.getElementById('skuAssignIsEpp');
        if (eppCheck) eppCheck.checked = false;

        document.getElementById('skuDetailStatus').value = status;

        // Edit tab info
        const info = document.getElementById('skuEditInfo');
        let bulkExtra = '';
        if (data.product_type === 'agrupado') {
            bulkExtra = `<div class="sku-info-item"><div class="sii-label">Tipo</div><div class="sii-value"><span style="background:rgba(139,92,246,0.15);color:#8b5cf6;padding:2px 8px;border-radius:6px;font-size:0.8rem;font-weight:700;"><i class="ph ph-stack"></i> Agrupado</span></div></div>
                         <div class="sku-info-item"><div class="sii-label">Stock Total</div><div class="sii-value" style="font-weight:bold; color:#8b5cf6;">${data.stock} ${data.unit_type||'Unidades'}</div></div>`;
        } else if (data.is_bulk == 1) {
            bulkExtra = `<div class="sku-info-item"><div class="sii-label">Stock Almacén</div><div class="sii-value" style="font-weight:bold; color:var(--primary-color);">${data.stock} ${data.unit_type||''}</div></div>`;
        }
        info.innerHTML = `
            <div class="sku-info-item"><div class="sii-label">SKU Code</div><div class="sii-value"><code>${esc(data.sku_code)}</code></div></div>
            <div class="sku-info-item"><div class="sii-label">Producto</div><div class="sii-value">${esc(data.product_name)}</div></div>
            <div class="sku-info-item"><div class="sii-label">Categoría</div><div class="sii-value">${esc(data.category_name || 'Sin categoría')}</div></div>
            ${data.is_bulk == 1 ? '' : `<div class="sku-info-item"><div class="sii-label">Estado</div><div class="sii-value"><span class="status-badge status-${esc(status)}">${esc(status).toUpperCase()}</span></div></div>`}
            ${data.is_bulk == 1 ? '' : `<div class="sku-info-item"><div class="sii-label">Asignado a</div><div class="sii-value">${data.assigned_user_name ? esc(data.assigned_user_name) : '<span style="color:var(--text-muted)">Sin asignar</span>'}</div></div>`}
            ${bulkExtra}
            <div class="sku-info-item"><div class="sii-label">Descripción</div><div class="sii-value">${esc(data.product_description || '—')}</div></div>`;

        if (data.is_bulk == 1) {
            document.getElementById('skuDetailStatus').parentElement.style.display = 'none';
        } else {
            document.getElementById('skuDetailStatus').parentElement.style.display = 'block';
        }


        // Assign tab
        const assignCurrent = document.getElementById('skuAssignCurrent');
        window.currentSkuChildren = []; // Reset

        // Detect agrupado: check product_type from data directly
        const isAgrupado = (data.product_type === 'agrupado');

        if (isAgrupado) {
            assignCurrent.innerHTML = `<div class="assign-badge none" style="background:rgba(139,92,246,0.1);border:1px solid rgba(139,92,246,0.3);color:#8b5cf6;"><i class="ph ph-stack"></i> Producto Agrupado &mdash; selecciona variante a asignar</div><div id="agrupadoAssignOptions" style="margin-top:12px;background:rgba(99,102,241,0.05);padding:12px;border-radius:8px;border:1px solid rgba(99,102,241,0.2);"><div style="display:flex;align-items:center;gap:8px;color:var(--text-muted);font-size:0.85rem;"><i class="ph ph-spinner ph-spin"></i> Cargando variantes...</div></div>`;
            
            const fd2 = new FormData();
            // Use product_id if available, otherwise strip 'bulk_' prefix from id
            const realProductId = data.product_id ? data.product_id : data.id.toString().replace('bulk_', '');
            fd2.append('action', 'get_children');
            fd2.append('product_id', realProductId);
            
            fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fd2 })
                .then(r => r.json())
                .then(res2 => {
                    const optionsEl = document.getElementById('agrupadoAssignOptions');
                    if (!optionsEl) return;
                    if (res2.success) {
                        window.currentSkuChildren = res2.data;
                        const cols = res2.columns || [];

                        if (cols.length === 0 && res2.data.length === 0) {
                            optionsEl.innerHTML = '<p style="color:var(--text-muted);font-size:0.85rem;margin:0;"><i class="ph ph-warning"></i> Este producto no tiene variantes configuradas.</p>';
                            return;
                        }

                        if (cols.length > 0) {
                            // ── Cascading dropdowns ──
                            // Render the container and let renderCascadeSelects fill it
                            optionsEl.innerHTML = `
                                <div id="cascadeSelectsWrap" style="display:flex;flex-direction:column;gap:10px;"></div>
                                <div id="cascadeMatchPreview" style="margin-top:10px;display:none;"></div>`;

                            window._agrupadoCols     = cols.map(c => c.name || c);
                            window._agrupadoChildren = res2.data;

                            // Function to re-render all selects from 'fromIdx' onward
                            window.renderCascadeSelects = function(changedIdx) {
                                const wrap = document.getElementById('cascadeSelectsWrap');
                                if (!wrap) return;

                                const colNames   = window._agrupadoCols;
                                const allChildren= window._agrupadoChildren;

                                // Collect current selections up to changedIdx
                                const currentSelections = {};
                                wrap.querySelectorAll('.agrupado-variant-select').forEach(sel => {
                                    const col = sel.getAttribute('data-col');
                                    if (sel.value) currentSelections[col] = sel.value;
                                });

                                // Re-build selects from changedIdx onwards — keep earlier ones intact
                                // Get existing selects to know how many already exist
                                const existingSelects = wrap.querySelectorAll('.agrupado-cascade-row');
                                const existingCount   = existingSelects.length;

                                if (existingCount < colNames.length) {
                                    // First render — build all from scratch
                                    wrap.innerHTML = '';
                                    colNames.forEach((colName, idx) => renderOneCascadeRow(wrap, colName, idx, {}, allChildren));
                                } else {
                                    // Update from changedIdx+1 onward
                                    for (let idx = changedIdx + 1; idx < colNames.length; idx++) {
                                        // Build filter from selections before this col
                                        const prevFilter = {};
                                        for (let p = 0; p < idx; p++) {
                                            const prevSel = wrap.querySelector(`.agrupado-variant-select[data-col="${CSS.escape(colNames[p])}"]`);
                                            if (prevSel && prevSel.value) prevFilter[colNames[p]] = prevSel.value;
                                        }
                                        // Filter children by prevFilter
                                        const filtered = allChildren.filter(ch => {
                                            const attrs = ch.variant_attributes || {};
                                            return Object.keys(prevFilter).every(k => String(attrs[k]) === String(prevFilter[k]));
                                        });
                                        // Update that row's select
                                        const rowEl = existingSelects[idx];
                                        if (!rowEl) continue;
                                        const sel = rowEl.querySelector('.agrupado-variant-select');
                                        const prevVal = currentSelections[colNames[idx]];
                                        const uniqueVals = [...new Set(filtered.map(ch => (ch.variant_attributes||{})[colNames[idx]]).filter(v => v != null && v !== ''))];
                                        sel.innerHTML = `<option value="">— Seleccionar ${esc(colNames[idx])} —</option>` +
                                            uniqueVals.map(v => {
                                                const stock = filtered.filter(ch => String((ch.variant_attributes||{})[colNames[idx]]) === String(v)).reduce((s,ch)=>s+parseFloat(ch.total_quantity||0),0);
                                                const selected = prevVal === v ? 'selected' : '';
                                                return `<option value="${esc(v)}" ${selected}>${esc(v)} (${stock} disp.)</option>`;
                                            }).join('');
                                        // Style: dim if no options available
                                        sel.disabled = uniqueVals.length === 0;
                                        sel.style.opacity = uniqueVals.length === 0 ? '0.4' : '1';
                                    }
                                }

                                updateCascadePreview();
                            };

                            function renderOneCascadeRow(wrap, colName, idx, prevFilter, allChildren) {
                                const filtered = allChildren.filter(ch => {
                                    const attrs = ch.variant_attributes || {};
                                    return Object.keys(prevFilter).every(k => String(attrs[k]) === String(prevFilter[k]));
                                });
                                const uniqueVals = [...new Set(filtered.map(ch => (ch.variant_attributes||{})[colName]).filter(v => v != null && v !== ''))];

                                const row = document.createElement('div');
                                row.className = 'agrupado-cascade-row';
                                row.style.cssText = 'position:relative;';
                                row.innerHTML = `
                                    <label style="font-size:0.82rem;font-weight:700;margin-bottom:5px;display:flex;align-items:center;gap:5px;color:var(--text-color);">
                                        <i class="ph ph-tag" style="color:#8b5cf6;"></i> ${esc(colName)}
                                    </label>
                                    <select class="form-select form-select-sm agrupado-variant-select" data-col="${esc(colName)}" data-idx="${idx}" style="border-color:rgba(139,92,246,0.4);">
                                        <option value="">— Seleccionar ${esc(colName)} —</option>
                                        ${uniqueVals.map(v => {
                                            const stock = filtered.filter(ch => String((ch.variant_attributes||{})[colName]) === String(v)).reduce((s,ch)=>s+parseFloat(ch.total_quantity||0),0);
                                            return `<option value="${esc(v)}">${esc(v)} (${stock} disp.)</option>`;
                                        }).join('')}
                                    </select>
                                    <div class="cascade-match-hint" style="margin-top:4px;min-height:18px;font-size:0.75rem;color:var(--text-muted);"></div>`;
                                wrap.appendChild(row);

                                row.querySelector('select').addEventListener('change', function() {
                                    window.renderCascadeSelects(idx);
                                });
                            }

                            function updateCascadePreview() {
                                const wrap    = document.getElementById('cascadeSelectsWrap');
                                const preview = document.getElementById('cascadeMatchPreview');
                                if (!wrap || !preview) return;

                                const colNames    = window._agrupadoCols;
                                const allChildren = window._agrupadoChildren;

                                // Collect all selected values
                                const selections = {};
                                let allSelected = true;
                                colNames.forEach(col => {
                                    const sel = wrap.querySelector(`.agrupado-variant-select[data-col="${CSS.escape(col)}"]`);
                                    if (sel && sel.value) selections[col] = sel.value;
                                    else allSelected = false;
                                });

                                // Update hint on each row showing how many rows match so far
                                const rows = wrap.querySelectorAll('.agrupado-cascade-row');
                                rows.forEach((row, idx) => {
                                    const hint = row.querySelector('.cascade-match-hint');
                                    if (!hint) return;
                                    // Filter by selections UP TO AND INCLUDING this col
                                    const partialFilter = {};
                                    for (let p = 0; p <= idx; p++) {
                                        const colSel = wrap.querySelector(`.agrupado-variant-select[data-idx="${p}"]`);
                                        if (colSel && colSel.value) partialFilter[colNames[p]] = colSel.value;
                                    }
                                    if (Object.keys(partialFilter).length === 0) { hint.textContent = ''; return; }
                                    const matches = allChildren.filter(ch => {
                                        const attrs = ch.variant_attributes || {};
                                        return Object.keys(partialFilter).every(k => String(attrs[k]) === String(partialFilter[k]));
                                    });
                                    const totalStock = matches.reduce((s,ch)=>s+parseFloat(ch.total_quantity||0),0);
                                    if (matches.length === 0) {
                                        hint.innerHTML = '<span style="color:#ef4444;"><i class="ph ph-x-circle"></i> Sin coincidencias</span>';
                                    } else {
                                        hint.innerHTML = `<span style="color:#10b981;"><i class="ph ph-check-circle"></i> ${matches.length} variante${matches.length>1?'s':''} · <strong>${totalStock}</strong> en stock</span>`;
                                    }
                                });

                                // Show bottom match card if all selected
                                if (allSelected) {
                                    const match = allChildren.find(ch => {
                                        const attrs = ch.variant_attributes || {};
                                        return Object.keys(selections).every(k => String(attrs[k]) === String(selections[k]));
                                    });
                                    if (match) {
                                        preview.style.display = 'block';
                                        preview.innerHTML = `
                                            <div style="background:rgba(16,185,129,0.08);border:1px solid rgba(16,185,129,0.3);border-radius:8px;padding:10px 14px;display:flex;align-items:center;justify-content:space-between;gap:10px;">
                                                <div>
                                                    <div style="font-size:0.78rem;color:var(--text-muted);margin-bottom:2px;"><i class="ph ph-check-circle" style="color:#10b981;"></i> Variante encontrada</div>
                                                    <div style="font-weight:700;color:var(--text-color);font-size:0.9rem;">${esc(match.name)}</div>
                                                </div>
                                                <div style="text-align:right;flex-shrink:0;">
                                                    <div style="font-size:0.75rem;color:var(--text-muted);">Disponible</div>
                                                    <div style="font-weight:800;font-size:1.1rem;color:#10b981;">${match.total_quantity}</div>
                                                </div>
                                            </div>`;
                                    } else {
                                        preview.style.display = 'block';
                                        preview.innerHTML = `<div style="background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.3);border-radius:8px;padding:10px 14px;font-size:0.85rem;color:#ef4444;"><i class="ph ph-x-circle"></i> No existe una variante con esta combinación</div>`;
                                    }
                                } else {
                                    preview.style.display = 'none';
                                }
                            }

                            // Initial render
                            window.renderCascadeSelects(-1);

                        } else {
                            // No column definitions — simple list select
                            optionsEl.innerHTML = `<div>
                                <label style="font-size:0.82rem;font-weight:700;margin-bottom:5px;display:block;">Variante</label>
                                <select class="form-select form-select-sm agrupado-variant-select" data-col="__variant__">
                                    <option value="">— Seleccionar variante —</option>
                                    ${res2.data.map(ch => `<option value="${ch.id}">${esc(ch.name)} (${ch.total_quantity} disp.)</option>`).join('')}
                                </select>
                            </div>`;
                        }

                    } else {
                        optionsEl.innerHTML = `<p style="color:#ef4444;font-size:0.85rem;margin:0;"><i class="ph ph-x-circle"></i> Error: ${esc(res2.message || 'No se pudieron cargar las variantes')}</p>`;
                    }
                }).catch(() => {
                    document.getElementById('agrupadoAssignOptions').innerHTML = 'Error de conexión.';
                });
        } else if (data.is_bulk == 1) {
            assignCurrent.innerHTML = `<div class="assign-badge none"><i class="ph ph-package"></i> Producto a Granel (Stock: ${data.stock} ${data.unit_type||''})</div>`;
        } else if (data.assigned_user_name) {
            assignCurrent.innerHTML = `<div class="assign-badge"><i class="ph ph-user-circle"></i> Asignado a: <strong>${esc(data.assigned_user_name)}</strong></div>`;
        } else {
            assignCurrent.innerHTML = `<div class="assign-badge none"><i class="ph ph-user-circle"></i> Sin asignar</div>`;
        }
        loadUsersForAssign();

        // Entry tab reset
        document.getElementById('entryType').value = 'entrada';
        document.getElementById('entryNotas').value = '';
        document.getElementById('photoPreviewList').innerHTML = '';
        entryPhotoFiles = [];
        loadEntryHistory(data.id);

        // Show modal, default to edit tab
        switchDetailTab('edit');
        const skuDetailModal = document.getElementById('skuDetailModal');
        if (skuDetailModal.parentElement !== document.body) {
            document.body.appendChild(skuDetailModal);
        }
        skuDetailModal.classList.add('active');
    }

    window.closeSkuDetail = function() {
        document.getElementById('skuDetailModal').classList.remove('active');
        currentSkuData = null;
    };

    window.switchDetailTab = function(tab) {
        document.querySelectorAll('#skuDetailModal .inv-tab').forEach(t => t.classList.toggle('active', t.dataset.dtab === tab));
        document.querySelectorAll('.sdt-pane').forEach(p => p.classList.remove('active'));
        const targetPane = document.getElementById('dtab-' + tab);
        if (targetPane) targetPane.classList.add('active');
    };

    // ── Detail: Update Status ──
    window.updateSkuDetailStatus = async function() {
        if (!currentSkuData) return;
        const status = document.getElementById('skuDetailStatus').value;
        const fd = new FormData(); fd.append('action', 'update_sku_status'); fd.append('sku_id', currentSkuData.id); fd.append('status', status);
        const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fd }).then(r => r.json());
        if (res.success) {
            currentSkuData.status = status;
            if (window.showToast) window.showToast('Estado actualizado', 'success');
            loadAllSkus(); loadMetrics(); loadProducts();
            // Update info display
            const badge = document.querySelector('#skuEditInfo .status-badge');
            if (badge) { badge.className = 'status-badge status-' + status; badge.textContent = status.toUpperCase(); }
        }
    };

    // ── Detail: Load users for assign ──
    async function loadUsersForAssign() {
        const fd = new FormData(); fd.append('action', 'list_users');
        const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fd }).then(r => r.json());
        if (res.success) {
            const sel = document.getElementById('skuAssignUser');
            sel.innerHTML = '<option value="">Seleccionar usuario...</option>';
            res.data.forEach(u => {
                const selected = currentSkuData && currentSkuData.assigned_to == u.id ? 'selected' : '';
                const roleLabel = u.role ? ` [${esc(u.role)}]` : "";
                sel.innerHTML += `<option value="${u.id}" ${selected}>${esc(u.name)}${roleLabel} — ${esc(u.email)}</option>`;
            });
        }
    }

    window.assignSkuToUser = async function() {
        if (!currentSkuData) return;
        const userId = document.getElementById('skuAssignUser').value;
        if (!userId) { if (window.showToast) window.showToast('Selecciona un usuario', 'error'); return; }
        
        let targetSkuId = currentSkuData.id;
        let availableStock = currentSkuData.stock;
        
        if (currentSkuData.product_type === 'agrupado') {
            const selects = document.querySelectorAll('#agrupadoAssignOptions .agrupado-variant-select');
            let selectedAttrs = {};
            let missing = false;
            selects.forEach(sel => {
                if(!sel.value) missing = true;
                selectedAttrs[sel.getAttribute('data-col')] = sel.value;
            });
            
            if (missing) {
                if (window.showToast) window.showToast('Selecciona todas las opciones de variante', 'error');
                return;
            }
            
            // Find child matching these attrs
            const match = window.currentSkuChildren.find(child => {
                const attrs = child.variant_attributes || {};
                return Object.keys(selectedAttrs).every(k => k === '__variant__' || String(attrs[k]) === String(selectedAttrs[k]));
            });
            
            if (!match) {
                if (window.showToast) window.showToast('No existe una variante con esa combinación', 'error');
                return;
            }
            if (parseFloat(match.total_quantity) <= 0) {
                if (window.showToast) window.showToast('Esta variante no tiene stock disponible', 'error');
                return;
            }
            targetSkuId = 'bulk_' + match.id;
            availableStock = match.total_quantity;
        }

        let quantity = 0;
        // For agrupado products OR bulk products, ask for quantity
        const needsQty = currentSkuData.product_type === 'agrupado' || currentSkuData.is_bulk == 1;
        if (needsQty) {
            const unitLabel = currentSkuData.unit_type || 'Unidades';
            const qtyStr = prompt(`Ingresa la cantidad a asignar (Disponible: ${availableStock} ${unitLabel}):`);
            if (!qtyStr) return;
            quantity = parseFloat(qtyStr);
            if (isNaN(quantity) || quantity <= 0) {
                if (window.showToast) window.showToast('Cantidad inválida', 'error');
                return;
            }
            if (quantity > parseFloat(availableStock)) {
                if (window.showToast) window.showToast(`Stock insuficiente. Disponible: ${availableStock}`, 'error');
                return;
            }
        }

        const fd = new FormData(); fd.append('action', 'assign_sku'); fd.append('sku_id', targetSkuId); fd.append('user_id', userId);
        const isEpp = document.getElementById('skuAssignIsEpp')?.checked ? 1 : 0;
        fd.append('is_epp', isEpp);
        if (needsQty) fd.append('quantity', quantity);

        const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fd }).then(r => r.json());
        if (res.success) {
            if (window.showToast) window.showToast(res.message, 'success');
            if (currentSkuData.product_type === 'agrupado' || currentSkuData.is_bulk == 1) {
                closeSkuDetail();
                loadProducts(); // Refresh warehouse stock
            } else {
                currentSkuData.assigned_to = userId;
                currentSkuData.assigned_user_name = res.user_name;
                document.getElementById('skuAssignCurrent').innerHTML = `<div class="assign-badge"><i class="ph ph-user-circle"></i> Asignado a: <strong>${esc(res.user_name)}</strong></div>`;
                loadAllSkus();
            }
        } else {
            if (window.showToast) window.showToast(res.message, 'error');
        }
    };


    window.unassignSku = async function() {
        if (!currentSkuData) return;
        const fd = new FormData(); fd.append('action', 'unassign_sku'); fd.append('sku_id', currentSkuData.id);
        const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fd }).then(r => r.json());
        if (res.success) {
            if (window.showToast) window.showToast('Asignación removida', 'success');
            currentSkuData.assigned_to = null;
            currentSkuData.assigned_user_name = null;
            document.getElementById('skuAssignCurrent').innerHTML = `<div class="assign-badge none"><i class="ph ph-user-circle"></i> Sin asignar</div>`;
            loadAllSkus();
        }
    };

    // ── Detail: Photo upload preview ──
    window.takeEntryPhoto = function() {
        openSysCamera(blob => {
            const file = new File([blob], "camera_capture_" + Date.now() + ".jpg", { type: "image/jpeg" });
            entryPhotoFiles.push(file);
            const reader = new FileReader();
            reader.onload = e => {
                const fileIdx = entryPhotoFiles.length - 1;
                document.getElementById('photoPreviewList').innerHTML += `
                    <div class="photo-preview-item" id="preview-photo-${fileIdx}" style="position:relative; width:60px; height:60px; border-radius:6px; overflow:hidden; border:1px solid var(--border-color);">
                        <img src="${e.target.result}" style="width:100%; height:100%; object-fit:cover;">
                        <button type="button" onclick="removeEntryPhoto(${fileIdx})" style="position:absolute; top:2px; right:2px; background:rgba(239,68,68,0.9); color:white; border:none; border-radius:50%; width:18px; height:18px; font-size:10px; display:flex; align-items:center; justify-content:center; cursor:pointer;"><i class="ph ph-x"></i></button>
                    </div>`;
            };
            reader.readAsDataURL(blob);
        });
    };

    window.previewEntryPhotos = function(input) {
        const list = document.getElementById('photoPreviewList');
        const files = Array.from(input.files);
        files.forEach((file, idx) => {
            entryPhotoFiles.push(file);
            const reader = new FileReader();
            reader.onload = (e) => {
                const fileIdx = entryPhotoFiles.length - files.length + idx;
                const thumb = document.createElement('div');
                thumb.className = 'inv-photo-thumb';
                thumb.innerHTML = `<img src="${e.target.result}"><button class="remove-photo" onclick="removeEntryPhoto(${fileIdx},this.parentElement)">&times;</button>`;
                list.appendChild(thumb);
            };
            reader.readAsDataURL(file);
        });
    };

    window.removeEntryPhoto = function(idx, el) {
        entryPhotoFiles.splice(idx, 1);
        el.remove();
    };

    // ── Detail: Submit Entry ──
    window.submitEntry = async function() {
        if (!currentSkuData) return;
        const tipo = document.getElementById('entryType').value;
        const notas = document.getElementById('entryNotas').value.trim();

        const fd = new FormData();
        fd.append('action', 'create_entry');
        fd.append('sku_id', currentSkuData.id);
        fd.append('tipo', tipo);
        fd.append('notas', notas);
        entryPhotoFiles.forEach(f => fd.append('photos[]', f));

        try {
            const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fd }).then(r => r.json());
            if (res.success) {
                if (window.showToast) window.showToast('Movimiento registrado', 'success');
                document.getElementById('entryNotas').value = '';
                document.getElementById('photoPreviewList').innerHTML = '';
                entryPhotoFiles = [];
                loadEntryHistory(currentSkuData.id);
                loadAllSkus(); loadMetrics(); loadProducts();
            } else {
                if (window.showToast) window.showToast(res.message, 'error');
            }
        } catch (e) { if (window.showToast) window.showToast('Error del servidor', 'error'); }
    };

    // ── Update Historia from table ──
    window.updateHistoria = async function(skuId, historia) {
        const fd = new FormData();
        fd.append('action', 'update_historia');
        fd.append('sku_id', skuId);
        fd.append('historia', historia);
        const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fd }).then(r => r.json());
        if (res.success) {
            if (window.showToast) window.showToast('Historia actualizada', 'success');
        }
    };

    // ── Detail: Load Entry History ──
    async function loadEntryHistory(skuId) {
        const fd = new FormData(); fd.append('action', 'get_sku_entries'); fd.append('sku_id', skuId);
        const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fd }).then(r => r.json());
        const list = document.getElementById('entryHistoryList');
        if (res.success && res.data.length > 0) {
            const tipoLabel = { entrada: '📥 Entrada', salida: '📤 Salida', devolucion: '🔄 Devolución', reparacion: '🔧 Reparación' };
            list.innerHTML = res.data.map(e => {
                let photosHtml = '';
                if (e.photos) {
                    const arr = e.photos.split(',');
                    photosHtml = `<div class="inv-entry-photos">${arr.map(p => `<img src="${BASE}/${p}" onclick="window.open('${BASE}/${p}','_blank')">`).join('')}</div>`;
                }
                const dateForInput = e.created_at ? e.created_at.replace(' ', 'T').substring(0, 16) : '';
                return `<div class="inv-entry-item" style="position:relative;">
                    <div class="entry-header" style="padding-right:64px;">
                        <span class="entry-type">${tipoLabel[e.tipo] || e.tipo}</span>
                        <span class="entry-date">${e.created_at}</span>
                    </div>
                    <div style="position:absolute;top:8px;right:8px;display:flex;gap:4px;">
                        <button onclick="openEditEntryModal(${e.id},'${esc(e.tipo)}','${esc(e.notas||'').replace(/'/g,"'")}','${dateForInput}')" title="Editar" style="background:rgba(99,102,241,0.12);border:none;border-radius:6px;padding:5px 8px;cursor:pointer;color:#6366f1;font-size:0.85rem;transition:background 0.2s;" onmouseover="this.style.background='rgba(99,102,241,0.22)'" onmouseout="this.style.background='rgba(99,102,241,0.12)'"><i class="ph ph-pencil"></i></button>
                        <button onclick="deleteEntryItem(${e.id})" title="Eliminar" style="background:rgba(239,68,68,0.12);border:none;border-radius:6px;padding:5px 8px;cursor:pointer;color:#ef4444;font-size:0.85rem;transition:background 0.2s;" onmouseover="this.style.background='rgba(239,68,68,0.22)'" onmouseout="this.style.background='rgba(239,68,68,0.12)'"><i class="ph ph-trash"></i></button>
                    </div>
                    <div class="entry-user"><i class="ph ph-user"></i> ${esc(e.user_name)}</div>
                    ${e.notas ? `<div class="entry-notes">${esc(e.notas)}</div>` : ''}
                    ${photosHtml}
                </div>`;
            }).join('');
        } else {
            list.innerHTML = '<p style="text-align:center;color:var(--text-muted);padding:16px;font-size:0.85rem;">Sin registros aún.</p>';
        }
    }

    // ── Edit Entry Modal (SKU detail panel) ──
    window.openEditEntryModal = function(id, tipo, notas, dateStr) {
        document.getElementById('editEntryId').value = id;
        document.getElementById('editEntryType').value = tipo || 'entrada';
        document.getElementById('editEntryNotas').value = notas || '';
        document.getElementById('editEntryDate').value = dateStr || '';
        const m = document.getElementById('editEntryModal');
        if (m.parentElement !== document.body) document.body.appendChild(m);
        m.classList.add('active');
    };

    window.closeEditEntryModal = function() {
        document.getElementById('editEntryModal').classList.remove('active');
    };

    window.saveEditEntry = async function() {
        const id = document.getElementById('editEntryId').value;
        const tipo = document.getElementById('editEntryType').value;
        const notas = document.getElementById('editEntryNotas').value;
        const date = document.getElementById('editEntryDate').value;
        if (!id) return;
        const btn = document.querySelector('#editEntryModal .btn-primary');
        const origText = btn.innerHTML;
        btn.disabled = true; btn.innerHTML = '<i class="ph ph-spinner ph-spin"></i> Guardando...';
        const fd = new FormData();
        fd.append('action', 'update_entry');
        fd.append('entry_id', id);
        fd.append('tipo', tipo);
        fd.append('notas', notas);
        if (date) fd.append('created_at', date.replace('T', ' ') + ':00');
        try {
            const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fd }).then(r => r.json());
            if (res.success) {
                if (window.showToast) window.showToast('Movimiento actualizado', 'success');
                closeEditEntryModal();
                if (typeof currentSkuData !== 'undefined' && currentSkuData && currentSkuData.id) {
                    loadEntryHistory(currentSkuData.id);
                }
            } else {
                if (window.showToast) window.showToast(res.message || 'Error', 'error');
            }
        } catch(e) {
            if (window.showToast) window.showToast('Error de conexión', 'error');
        }
        btn.disabled = false; btn.innerHTML = origText;
    };

    window.deleteEntryItem = async function(id) {
        const ok = await invConfirm('¿Eliminar movimiento?', 'Se eliminará este registro del historial.');
        if (!ok) return;
        const fd = new FormData(); fd.append('action', 'delete_entry'); fd.append('entry_id', id);
        const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fd }).then(r => r.json());
        if (res.success) {
            if (window.showToast) window.showToast('Movimiento eliminado', 'success');
            if (typeof currentSkuData !== 'undefined' && currentSkuData && currentSkuData.id) {
                loadEntryHistory(currentSkuData.id);
            }
        } else {
            if (window.showToast) window.showToast(res.message || 'Error', 'error');
        }
    };

    // ── Product Photo Functions (Multi-Photo) ──
    let createProductPhotos = []; // Files for new product
    let editProductPhotosNew = []; // New files for edit
    let editProductPhotosExisting = []; // Existing photos from server
    let editProductPhotosToDelete = []; // IDs to delete

    // Tab switching for New Product modal
    window.switchEditProductTab = function(tab) {
        document.querySelectorAll('#editProductModal .inv-tab').forEach(t => t.classList.toggle('active', t.dataset.eptab === tab));
        document.querySelectorAll('.ep-pane').forEach(p => p.classList.remove('active'));
        const activePane = document.getElementById('eptab-' + tab);
        if (activePane) activePane.classList.add('active');
    };

    window.switchNewProductTab = function(tab) {
        document.querySelectorAll('.np-pane').forEach(p => p.classList.remove('active'));
        document.getElementById('nptab-' + tab)?.classList.add('active');
        document.querySelectorAll('[data-nptab]').forEach(b => {
            b.classList.toggle('active', b.dataset.nptab === tab);
        });
    };

    window.handleProductPhotoSelect = function(event, mode) {
        const files = Array.from(event.target.files).filter(f => f.type.startsWith('image/'));
        if (mode === 'create') {
            createProductPhotos.push(...files);
        } else {
            editProductPhotosNew.push(...files);
        }
        renderProductPhotoGallery(mode);
        event.target.value = '';
    };

    window.handleProductPhotoDrop = function(event, mode) {
        event.preventDefault();
        event.currentTarget.classList.remove('dragover');
        const files = Array.from(event.dataTransfer.files).filter(f => f.type.startsWith('image/'));
        if (mode === 'create') {
            createProductPhotos.push(...files);
        } else {
            editProductPhotosNew.push(...files);
        }
        renderProductPhotoGallery(mode);
    };

    window.removeNewProductPhoto = function(mode, index) {
        if (mode === 'create') {
            createProductPhotos.splice(index, 1);
        } else {
            editProductPhotosNew.splice(index, 1);
        }
        renderProductPhotoGallery(mode);
    };

    window.removeExistingProductPhoto = function(photoId) {
        editProductPhotosToDelete.push(photoId);
        editProductPhotosExisting = editProductPhotosExisting.filter(p => p.id != photoId);
        renderProductPhotoGallery('edit');
    };

    window.renderProductPhotoGallery = function(mode) {
        const galleryId = mode === 'create' ? 'prodPhotoGallery' : 'editPhotoGallery';
        const gallery = document.getElementById(galleryId);
        if (!gallery) return;
        let html = '';

        // Existing photos (edit mode only)
        if (mode === 'edit') {
            editProductPhotosExisting.forEach(photo => {
                html += `<div class="prod-photo-item">
                    <img src="${BASE}/${photo.ruta_archivo}" alt="Foto">
                    <button class="remove-prod-photo" onclick="event.stopPropagation(); removeExistingProductPhoto(${photo.id})" title="Eliminar"><i class="ph ph-x"></i></button>
                </div>`;
            });
        }

        // New files
        const files = mode === 'create' ? createProductPhotos : editProductPhotosNew;
        files.forEach((file, idx) => {
            const url = URL.createObjectURL(file);
            html += `<div class="prod-photo-item">
                <img src="${url}" alt="Preview">
                <button class="remove-prod-photo" onclick="event.stopPropagation(); removeNewProductPhoto('${mode}', ${idx})" title="Quitar"><i class="ph ph-x"></i></button>
            </div>`;
        });

        if (!html) {
            html = '<div style="grid-column:1/-1;text-align:center;padding:12px;color:var(--text-muted);font-size:0.85rem;"><i class="ph ph-image" style="font-size:1.5rem;display:block;margin-bottom:4px;opacity:0.3;"></i>Sin fotos aún</div>';
        }
        gallery.innerHTML = html;
    };

    window.captureProductPhoto = function(mode) {
        const inputId = mode === 'edit' ? 'editProdPhotoInput' : 'prodPhotoInput';
        const input = document.getElementById(inputId);
        input.setAttribute('capture', 'environment');
        input.click();
        setTimeout(() => input.removeAttribute('capture'), 500);
    };

    // Load existing product photos for edit mode
    async function loadEditProductPhotos(productId) {
        try {
            const fd = new FormData();
            fd.append('action', 'get_product_photos');
            fd.append('product_id', productId);
            const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fd }).then(r => r.json());
            if (res.success) {
                editProductPhotosExisting = res.data || [];
            } else {
                editProductPhotosExisting = [];
            }
        } catch(e) {
            editProductPhotosExisting = [];
        }
        renderProductPhotoGallery('edit');
    }

    // ── SKU Photo Modal (from Control de Stock table) ──
    let skuPhotoModalId = null;
    let skuPhotoNewFiles = [];

    window.openSkuPhotoUpload = async function(skuId, skuCode) {
        skuPhotoModalId = skuId;
        skuPhotoNewFiles = [];

        // Paso 1: abrir el picker modal
        document.getElementById('skuPickerCode').textContent = skuCode;
        document.getElementById('skuPhotoCode').textContent  = skuCode;
        document.getElementById('skuPhotoNewFiles').innerHTML = '';

        // Mover modals al body para evitar stacking context
        ['skuPhotoPickerModal', 'skuPhotoModal'].forEach(id => {
            const m = document.getElementById(id);
            if (m && m.parentElement !== document.body) document.body.appendChild(m);
        });

        document.getElementById('skuPhotoPickerModal').classList.add('active');

        // Pre-cargar la galería existente en segundo plano
        _loadSkuGallery(skuId);
    };

    async function _loadSkuGallery(skuId) {
        const gallery = document.getElementById('skuPhotoGallery');
        gallery.innerHTML = '<div style="text-align:center;padding:14px;"><i class="ph ph-spinner ph-spin" style="color:var(--primary-color);font-size:1.4rem;"></i></div>';
        try {
            const fd = new FormData();
            fd.append('action', 'get_sku_photos');
            fd.append('sku_id', skuId);
            const res = await fetch(BASE + '/ajax/mochila.php', { method: 'POST', body: fd }).then(r => r.json());
            if (res.success && res.data.length > 0) {
                gallery.innerHTML = res.data.map(p => `
                    <div class="prod-photo-item">
                        <img src="${BASE}/${p.ruta_archivo}" alt="Foto SKU" data-lb-src="${BASE}/${p.ruta_archivo}" data-lb-caption="Foto SKU" class="lb-thumb" style="cursor:zoom-in;">
                        <button class="remove-prod-photo" data-del-id="${p.id}" title="Eliminar"><i class="ph ph-x"></i></button>
                    </div>
                `).join('');
            gallery.querySelectorAll('[data-del-id]').forEach(btn => {
                btn.addEventListener('click', e => { e.stopPropagation(); deleteSkuPhotoInline(+btn.dataset.delId); });
            });
            } else {
                gallery.innerHTML = '<div style="text-align:center;padding:14px;color:var(--text-muted);font-size:0.85rem;"><i class="ph ph-image" style="font-size:1.8rem;display:block;margin-bottom:6px;opacity:0.3;"></i>Sin fotos aún</div>';
            }
        } catch(e) {
            gallery.innerHTML = '<div style="text-align:center;padding:10px;color:#ef4444;font-size:0.85rem;">Error al cargar fotos</div>';
        }
    }

    // Llamado al elegir cámara o galería en el picker
    window.pickSkuPhotoSource = function(source) {
        // Cerrar picker
        document.getElementById('skuPhotoPickerModal').classList.remove('active');

        if (source === 'camera') {
            // Abrir cámara directamente y cuando tome la foto abrir el modal principal
            const input = document.getElementById('skuPhotoCaptureInput');
            input.value = '';
            input.onchange = function() {
                handleSkuPhotoFiles(this.files);
                document.getElementById('skuPhotoModal').classList.add('active');
            };
            input.click();
        } else {
            // Abrir galería de archivos y luego abrir el modal principal
            const input = document.getElementById('skuPhotoFileInput');
            input.value = '';
            input.onchange = function() {
                handleSkuPhotoFiles(this.files);
                document.getElementById('skuPhotoModal').classList.add('active');
            };
            input.click();
        }
    };

    window.handleSkuPhotoFiles = function(fileList) {
        const files = Array.from(fileList).filter(f => f.type.startsWith('image/'));
        skuPhotoNewFiles.push(...files);
        renderSkuNewFiles();
    };

    function renderSkuNewFiles() {
        const container = document.getElementById('skuPhotoNewFiles');
        container.innerHTML = skuPhotoNewFiles.map((f, i) => {
            const url = URL.createObjectURL(f);
            return `<div class="prod-photo-item">
                <img src="${url}" alt="Preview">
                <button class="remove-prod-photo" onclick="event.stopPropagation(); removeSkuNewFile(${i})" title="Quitar"><i class="ph ph-x"></i></button>
            </div>`;
        }).join('');
    }

    window.removeSkuNewFile = function(idx) {
        skuPhotoNewFiles.splice(idx, 1);
        renderSkuNewFiles();
    };

    window.captureSkuPhoto = function() {
        document.getElementById('skuPhotoCaptureInput').click();
    };

    window.saveSkuPhotos = async function() {
        if (skuPhotoNewFiles.length === 0 || !skuPhotoModalId) {
            if (window.showToast) window.showToast('No hay fotos nuevas para guardar', 'info');
            return;
        }
        const btn = document.getElementById('btnSaveSkuPhotos');
        btn.disabled = true;
        btn.innerHTML = '<i class="ph ph-spinner ph-spin"></i> Guardando...';

        const fd = new FormData();
        fd.append('action', 'upload_sku_photo');
        fd.append('sku_id', skuPhotoModalId);
        fd.append('nota', '');
        skuPhotoNewFiles.forEach(f => fd.append('photos[]', f));

        try {
            const res = await fetch(BASE + '/ajax/mochila.php', { method: 'POST', body: fd }).then(r => r.json());
            if (res.success) {
                if (window.showToast) window.showToast('Fotos guardadas', 'success');
                skuPhotoNewFiles = [];
                const thumbUrl = res.sku_photo || res.product_image;
                if (thumbUrl) {
                    _updateSkuTableThumbnail(skuPhotoModalId, BASE + '/' + thumbUrl);
                    const editSkuListEl = document.getElementById('editSkuPhotoList');
                    if (editSkuListEl && editSkuListEl.style.display !== 'none') {
                        const editProdId = document.getElementById('editProductId') ? document.getElementById('editProductId').value : null;
                        if (editProdId) loadEditSkuPhotoList(editProdId);
                    }
                }
        openSkuPhotoUpload(skuPhotoModalId, document.getElementById('skuPhotoCode').textContent);
            } else {
                if (window.showToast) window.showToast(res.message || 'Error', 'error');
            }
        } catch(e) {
            if (window.showToast) window.showToast('Error de red', 'error');
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="ph ph-floppy-disk"></i> Guardar Fotos';
    };

    window.deleteSkuPhotoInline = async function(photoId) {
        if (!await invConfirm('Eliminar foto', '¿Seguro que deseas eliminar esta foto?')) return;
        const fd = new FormData();
        fd.append('action', 'delete_sku_photo');
        fd.append('photo_id', photoId);
        try {
            const res = await fetch(BASE + '/ajax/mochila.php', { method: 'POST', body: fd }).then(r => r.json());
            if (res.success) {
                if (window.showToast) window.showToast('Foto eliminada', 'success');
                if (skuPhotoModalId) _loadSkuGallery(skuPhotoModalId);
            } else {
                if (window.showToast) window.showToast(res.message || 'Error al eliminar', 'error');
            }
        } catch(e) {
            if (window.showToast) window.showToast('Error de red', 'error');
        }
    };

    // ── Helpers ──
    function esc(s) { if (!s) return ''; const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
    function randomCode(n) { const c = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; let r = ''; for (let i=0;i<n;i++) r+=c[Math.floor(Math.random()*c.length)]; return r; }

    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
    // Smart Column Filters - ColFilterManager
    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
    window.ColFilter = (function() {
        var state = {};
        var currentCol = null;
        var popover = null;

        var prodCols = {
            nombre:     { label: 'Producto',    type: 'text' },
            categoria:  { label: 'Categoria',   type: 'checkboxes', optionsFn: getCategoryOptions },
            total:      { label: 'Total',        type: 'range',
                          presets: [{label:'> 0', fn: function(v) { return v > 0; }}, {label:'= 0', fn: function(v) { return v === 0; }}, {label:'> 50', fn: function(v) { return v > 50; }}, {label:'> 100', fn: function(v) { return v > 100; }}] },
            disponibles:{ label: 'Disponibles', type: 'range',
                          presets: [{label:'Con stock', fn: function(v) { return v > 0; }}, {label:'Sin stock', fn: function(v) { return v === 0; }}, {label:'Stock critico', fn: function(v, row) { return v <= parseInt(row.dataset.crit || 0); }}] },
            instalados: { label: 'Instalados',  type: 'range',
                          presets: [{label:'Con instalados', fn: function(v) { return v > 0; }}, {label:'Sin instalados', fn: function(v) { return v === 0; }}] },
            malogrados: { label: 'Malogrados',  type: 'range',
                          presets: [{label:'Con malogrados', fn: function(v) { return v > 0; }}, {label:'Sin malogrados', fn: function(v) { return v === 0; }}] }
        };

        function getCategoryOptions() {
            var opts = [];
            document.querySelectorAll('#filterProductCategory option').forEach(function(o) {
                if (o.value !== '') opts.push({ value: o.value, label: o.textContent });
            });
            return opts;
        }

        function open(col, anchorBtn) {
            currentCol = col;
            closePopover();
            var def = prodCols[col];
            if (!def) return;
            popover = document.createElement('div');
            popover.id = 'cfPopover';
            popover.innerHTML = buildPopoverHTML(col, def);
            document.body.appendChild(popover);
            positionPopover(anchorBtn);
            wirePopover(col, def);
            setTimeout(function() { document.addEventListener('click', outsideClick, true); }, 10);
        }

        function buildPopoverHTML(col, def) {
            var cur = state[col] || {};
            var body = '';
            if (def.type === 'text') {
                body += '<div class="cf-section-label">Buscar</div><div class="cf-text-input"><i class="ph ph-magnifying-glass"></i><input id="cfInput_' + col + '" type="text" placeholder="Escribir para filtrar..." value="' + (cur.text || '') + '" autocomplete="off"></div>';
            }
            if (def.type === 'range') {
                if (def.presets && def.presets.length) {
                    body += '<div class="cf-section-label">Acceso rapido</div><div class="cf-presets">';
                    def.presets.forEach(function(p, i) {
                        var active = cur.preset === i ? ' cf-chip-active' : '';
                        body += '<span class="cf-chip' + active + '" data-preset="' + i + '">' + p.label + '</span>';
                    });
                    body += '</div>';
                }
                body += '<div class="cf-section-label">Rango libre</div><div class="cf-range-row"><input id="cfMin_' + col + '" type="number" placeholder="Min" value="' + (cur.min !== undefined ? cur.min : '') + '" min="0"><input id="cfMax_' + col + '" type="number" placeholder="Max" value="' + (cur.max !== undefined ? cur.max : '') + '" min="0"></div>';
            }
            if (def.type === 'checkboxes') {
                var opts = def.optionsFn ? def.optionsFn() : [];
                var selected = cur.checked || [];
                body += '<div class="cf-section-label">Seleccionar</div><div class="cf-check-list">';
                if (opts.length === 0) {
                    body += '<span style="font-size:0.82rem;color:var(--text-muted);">No hay opciones</span>';
                } else {
                    opts.forEach(function(o) {
                        var checked = selected.includes(o.value) ? 'checked' : '';
                        body += '<label class="cf-check-item"><input type="checkbox" value="' + o.value + '" ' + checked + '><span>' + o.label + '</span></label>';
                    });
                }
                body += '</div>';
            }
            var hasFilter = isActive(col);
            var clearBtn = hasFilter ? '<div class="cf-pop-clear"><button onclick="ColFilter.clearCol(\'' + col + '\')"><i class="ph ph-eraser"></i> Limpiar este filtro</button></div>' : '';
            return '<div class="cf-pop-header"><span><i class="ph ph-funnel-simple" style="color:var(--primary-color);margin-right:4px;"></i>' + def.label + '</span><button onclick="ColFilter.close()"><i class="ph ph-x"></i></button></div><div class="cf-pop-body">' + body + clearBtn + '</div>';
        }

        function positionPopover(anchorBtn) {
            if (!popover) return;
            var rect = anchorBtn.getBoundingClientRect();
            var pw = popover.offsetWidth || 260;
            var ph = popover.offsetHeight || 200;
            var top = rect.bottom + 6;
            var left = rect.left;
            if (left + pw > window.innerWidth - 10) left = window.innerWidth - pw - 10;
            if (top + ph > window.innerHeight - 10) top = rect.top - ph - 6;
            popover.style.top = top + 'px';
            popover.style.left = left + 'px';
        }

        function wirePopover(col, def) {
            if (!popover) return;
            if (def.type === 'text') {
                var inp = popover.querySelector('#cfInput_' + col);
                if (inp) {
                    inp.focus();
                    inp.addEventListener('input', function() {
                        if (!state[col]) state[col] = {};
                        state[col].text = inp.value.trim();
                        applyFilters(); updateHeaderUI(col); updateActiveBar();
                    });
                }
            }
            if (def.type === 'range') {
                popover.querySelectorAll('.cf-chip').forEach(function(chip) {
                    chip.addEventListener('click', function() {
                        var idx = parseInt(chip.dataset.preset);
                        if (!state[col]) state[col] = {};
                        var wasActive = state[col].preset === idx;
                        if (wasActive) { delete state[col].preset; }
                        else {
                            state[col].preset = idx;
                            delete state[col].min; delete state[col].max;
                            var minI = popover.querySelector('#cfMin_' + col);
                            var maxI = popover.querySelector('#cfMax_' + col);
                            if (minI) minI.value = ''; if (maxI) maxI.value = '';
                        }
                        popover.querySelectorAll('.cf-chip').forEach(function(c) { c.classList.remove('cf-chip-active'); });
                        if (!wasActive) chip.classList.add('cf-chip-active');
                        applyFilters(); updateHeaderUI(col); updateActiveBar();
                    });
                });
                var minInp = popover.querySelector('#cfMin_' + col);
                var maxInp = popover.querySelector('#cfMax_' + col);
                var onRange = function() {
                    if (!state[col]) state[col] = {};
                    var mn = minInp ? minInp.value.trim() : '';
                    var mx = maxInp ? maxInp.value.trim() : '';
                    state[col].min = mn !== '' ? parseFloat(mn) : undefined;
                    state[col].max = mx !== '' ? parseFloat(mx) : undefined;
                    delete state[col].preset;
                    popover.querySelectorAll('.cf-chip').forEach(function(c) { c.classList.remove('cf-chip-active'); });
                    applyFilters(); updateHeaderUI(col); updateActiveBar();
                };
                if (minInp) minInp.addEventListener('input', onRange);
                if (maxInp) maxInp.addEventListener('input', onRange);
            }
            if (def.type === 'checkboxes') {
                popover.querySelectorAll('.cf-check-list input[type="checkbox"]').forEach(function(cb) {
                    cb.addEventListener('change', function() {
                        if (!state[col]) state[col] = {};
                        var checked = Array.from(popover.querySelectorAll('.cf-check-list input:checked')).map(function(c) { return c.value; });
                        state[col].checked = checked;
                        applyFilters(); updateHeaderUI(col); updateActiveBar();
                    });
                });
            }
        }

        function applyFilters() {
            var q = (document.getElementById('searchProducts') ? document.getElementById('searchProducts').value : '').toLowerCase();
            var catGlobal = document.getElementById('filterProductCategory') ? document.getElementById('filterProductCategory').value : '';
            var statGlobal = document.getElementById('filterProductStatus') ? document.getElementById('filterProductStatus').value : '';
            document.querySelectorAll('.product-row').forEach(function(row) {
                var show = true;
                if (q && !row.dataset.name.includes(q)) show = false;
                if (catGlobal && row.dataset.cat != catGlobal) show = false;
                if (statGlobal) {
                    var disp = parseInt(row.dataset.disp);
                    var crit = parseInt(row.dataset.crit || 0);
                    var malo = parseInt(row.dataset.malo);
                    if (statGlobal === 'con_stock'      && !(disp > 0))     show = false;
                    if (statGlobal === 'sin_stock'      && !(disp === 0))   show = false;
                    if (statGlobal === 'stock_critico'  && !(disp <= crit)) show = false;
                    if (statGlobal === 'con_malogrados' && !(malo > 0))     show = false;
                }
                if (show) {
                    Object.entries(state).forEach(function(entry) {
                        if (!show) return;
                        var col = entry[0]; var f = entry[1];
                        var def = prodCols[col];
                        if (!def) return;
                        if (def.type === 'text' && f.text) {
                            var txt = f.text.toLowerCase();
                            if (col === 'nombre' && !row.dataset.name.includes(txt)) show = false;
                        }
                        if (def.type === 'range') {
                            var val = getRowValue(col, row);
                            if (f.preset !== undefined) {
                                var preset = def.presets[f.preset];
                                if (preset && !preset.fn(val, row)) show = false;
                            }
                            if (f.min !== undefined && val < f.min) show = false;
                            if (f.max !== undefined && val > f.max) show = false;
                        }
                        if (def.type === 'checkboxes' && f.checked && f.checked.length > 0) {
                            if (col === 'categoria' && !f.checked.includes(row.dataset.cat)) show = false;
                        }
                    });
                }
                row.style.display = show ? '' : 'none';
            });
            var visibleRows = document.querySelectorAll('.product-row:not([style*="display: none"]):not([style*="display:none"])');
            var empty = document.getElementById('productsEmpty');
            var wrap  = document.getElementById('productsTableWrap');
            if (visibleRows.length === 0 && wrap && wrap.style.display !== 'none') {
                if (empty) empty.style.display = 'block';
            } else {
                if (empty) empty.style.display = 'none';
            }
        }

        function getRowValue(col, row) {
            if (col === 'total')       return parseInt(row.dataset.total || 0);
            if (col === 'disponibles') return parseInt(row.dataset.disp  || 0);
            if (col === 'instalados')  return parseInt(row.dataset.inst  || 0);
            if (col === 'malogrados')  return parseInt(row.dataset.malo  || 0);
            return 0;
        }

        function updateHeaderUI(col) {
            var active = isActive(col);
            var th = document.querySelector('.cf-th[data-col="' + col + '"]');
            var btn = th ? th.querySelector('.cf-btn') : null;
            if (!btn) return;
            btn.classList.toggle('cf-active', active);
            var badge = th.querySelector('.cf-badge');
            if (active) {
                if (!badge) { badge = document.createElement('span'); badge.className = 'cf-badge'; th.appendChild(badge); }
                badge.textContent = String.fromCharCode(0x25CF); badge.style.display = 'inline-flex';
            } else {
                if (badge) badge.style.display = 'none';
            }
        }

        function updateActiveBar() {
            var bar = document.getElementById('cfActiveBar');
            var tagsEl = document.getElementById('cfActiveTags');
            if (!bar || !tagsEl) return;
            var activeCols = Object.entries(state).filter(function(e) { return isActive(e[0]); });
            if (activeCols.length === 0) { bar.style.display = 'none'; return; }
            bar.style.display = 'flex';
            tagsEl.innerHTML = activeCols.map(function(e) {
                var col = e[0];
                var def = prodCols[col];
                var label = buildFilterLabel(col);
                return '<span class="cf-active-tag"><i class="ph ph-funnel-simple"></i><span>' + def.label + ': <strong>' + label + '</strong></span><button onclick="ColFilter.clearCol(\'' + col + '\')" title="Quitar"><i class="ph ph-x"></i></button></span>';
            }).join('');
        }

        function buildFilterLabel(col) {
            var f = state[col] || {};
            var def = prodCols[col];
            if (def.type === 'text') return '"' + f.text + '"';
            if (def.type === 'range') {
                if (f.preset !== undefined) return def.presets[f.preset].label;
                var parts = [];
                if (f.min !== undefined) parts.push('>= ' + f.min);
                if (f.max !== undefined) parts.push('<= ' + f.max);
                return parts.join(' y ') || '?';
            }
            if (def.type === 'checkboxes' && f.checked && f.checked.length) {
                var opts = def.optionsFn ? def.optionsFn() : [];
                var labels = f.checked.map(function(v) { var o = opts.find(function(x) { return x.value == v; }); return o ? o.label : v; });
                return labels.slice(0, 2).join(', ') + (labels.length > 2 ? ' +' + (labels.length - 2) : '');
            }
            return '';
        }

        function isActive(col) {
            var f = state[col];
            if (!f) return false;
            if (f.text) return true;
            if (f.preset !== undefined) return true;
            if (f.min !== undefined || f.max !== undefined) return true;
            if (f.checked && f.checked.length > 0) return true;
            return false;
        }

        function closePopover() {
            if (popover) { popover.remove(); popover = null; }
            document.removeEventListener('click', outsideClick, true);
        }

        function outsideClick(e) {
            if (popover && !popover.contains(e.target) && !e.target.closest('.cf-btn')) {
                closePopover();
            }
        }

        function clearCol(col) {
            delete state[col];
            closePopover();
            applyFilters();
            updateHeaderUI(col);
            updateActiveBar();
        }

        function clearAll() {
            Object.keys(state).forEach(function(k) { delete state[k]; });
            closePopover();
            applyFilters();
            Object.keys(prodCols).forEach(function(col) { updateHeaderUI(col); });
            updateActiveBar();
        }

        document.addEventListener('DOMContentLoaded', function() {
            ['searchProducts', 'filterProductCategory', 'filterProductStatus'].forEach(function(id) {
                var el = document.getElementById(id);
                if (el) {
                    el.addEventListener('input',  function() { applyFilters(); });
                    el.addEventListener('change', function() { applyFilters(); });
                }
            });
        });

        return { open: open, close: closePopover, clearCol: clearCol, clearAll: clearAll, applyFilters: applyFilters };
    })();

    // ══════════════════════════════════════════════════════════════
    // Edit Stock Modal — openEditStockModal / saveEditStockModal
    // ══════════════════════════════════════════════════════════════

    window.openEditStockModal = async function(productId, productType, isBulk, currentStock, unitType) {
        // Store values
        document.getElementById('esProductId').value = productId;
        document.getElementById('esProductType').value = productType;
        document.getElementById('esIsBulk').value = isBulk;
        document.getElementById('esCurrentStock').value = currentStock;
        document.getElementById('esNotes').value = '';

        // Set title
        const typeLabel = productType === 'agrupado' ? 'Agrupado' : (isBulk == 1 ? 'Granel' : 'Normal (SKU)');
        document.getElementById('editStockModalTitle').textContent = 'Editar Stock';
        document.getElementById('editStockModalSub').textContent = `Tipo: ${typeLabel}`;

        const m = document.getElementById('editStockModal');
        if (m.parentElement !== document.body) document.body.appendChild(m);
        m.classList.add('active');

        if (productType === 'agrupado') {
            // Show agrupado section, hide normal
            document.getElementById('esNormalWrap').style.display = 'none';
            document.getElementById('esAgrupadoWrap').style.display = '';

            // Load variants
            const tbody = document.getElementById('esVariantsList');
            tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:20px;"><i class="ph ph-spinner ph-spin"></i> Cargando variantes...</td></tr>';
            esUpdateAgrupadoTotal();

            try {
                const fd = new FormData();
                fd.append('action', 'get_children');
                fd.append('product_id', productId);
                const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fd }).then(r => r.json());
                if (res.success && res.data.length > 0) {
                    tbody.innerHTML = res.data.map(v => {
                        const attrs = v.variant_attributes ? Object.values(v.variant_attributes).filter(Boolean).join(' / ') : '';
                        return `<tr>
                            <td>
                                <div style="font-weight:600;">${esc(v.name)}</div>
                                ${attrs ? `<div style="font-size:0.78rem;color:var(--text-muted);">${esc(attrs)}</div>` : ''}
                            </td>
                            <td style="text-align:center;">
                                <span style="font-weight:700;color:#6366f1;">${parseFloat(v.total_quantity||0)}</span>
                                <div style="font-size:0.72rem;color:var(--text-muted);">${esc(v.unit_type||'Und')}</div>
                            </td>
                            <td style="text-align:center;padding:6px 8px;">
                                <div style="display:flex;gap:4px;align-items:center;justify-content:center;">
                                    <button type="button" onclick="esVariantAdjust(${v.id}, -1)" style="width:28px;height:28px;border:1px solid var(--border-color);background:var(--bg-color);border-radius:6px;font-size:1rem;cursor:pointer;color:var(--text-color);">−</button>
                                    <input type="number" id="esVariant_${v.id}" data-original="${parseFloat(v.total_quantity||0)}" data-name="${esc(v.name)}" value="${parseFloat(v.total_quantity||0)}" min="0" step="1" style="width:70px;text-align:center;padding:4px;border:1px solid var(--border-color);border-radius:6px;background:var(--surface-color);color:var(--text-color);font-size:0.9rem;font-weight:600;" oninput="esUpdateAgrupadoTotal()">
                                    <button type="button" onclick="esVariantAdjust(${v.id}, 1)" style="width:28px;height:28px;border:1px solid var(--border-color);background:var(--bg-color);border-radius:6px;font-size:1rem;cursor:pointer;color:var(--text-color);">+</button>
                                </div>
                            </td>
                            <td style="text-align:center;" id="esVariantChange_${v.id}">
                                <span style="font-size:0.85rem;font-weight:700;color:var(--text-muted);">±0</span>
                            </td>
                        </tr>`;
                    }).join('');
                    esUpdateAgrupadoTotal();
                } else {
                    tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:16px;color:var(--text-muted);">No hay variantes.</td></tr>';
                }
            } catch(e) {
                tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:16px;color:red;">Error al cargar variantes.</td></tr>';
            }
        } else {
            // Show normal/granel section
            document.getElementById('esNormalWrap').style.display = '';
            document.getElementById('esAgrupadoWrap').style.display = 'none';

            const cur = parseFloat(currentStock) || 0;
            document.getElementById('esCurrentStockDisplay').textContent = cur;
            document.getElementById('esNewStockDisplay').textContent = cur;
            document.getElementById('esNewQty').value = cur;
            document.getElementById('esUnitLabel').textContent = isBulk == 1 ? (unitType || 'Und') : 'SKUs';
            document.getElementById('esChangeBadge').innerHTML = '';
            document.getElementById('esDeleteWarning').style.display = 'none';
            document.getElementById('esGenerateInfo').style.display = 'none';
        }
    };

    window.closeEditStockModal = function() {
        document.getElementById('editStockModal').classList.remove('active');
    };

    window.esAdjust = function(delta) {
        const inp = document.getElementById('esNewQty');
        const cur = parseFloat(inp.value) || 0;
        inp.value = Math.max(0, cur + delta);
        esUpdatePreview();
    };

    window.esUpdatePreview = function() {
        const inp = document.getElementById('esNewQty');
        const newVal = parseFloat(inp.value) || 0;
        const original = parseFloat(document.getElementById('esCurrentStock').value) || 0;
        const isBulk = document.getElementById('esIsBulk').value == 1;
        const productType = document.getElementById('esProductType').value;
        const diff = newVal - original;

        document.getElementById('esNewStockDisplay').textContent = newVal;

        const badge = document.getElementById('esChangeBadge');
        if (diff === 0) {
            badge.innerHTML = '';
        } else if (diff > 0) {
            badge.innerHTML = `<span style="color:#10b981;">▲ +${diff}</span>`;
        } else {
            badge.innerHTML = `<span style="color:#ef4444;">▼ ${diff}</span>`;
        }

        const delWarn = document.getElementById('esDeleteWarning');
        const genInfo = document.getElementById('esGenerateInfo');
        delWarn.style.display = 'none';
        genInfo.style.display = 'none';

        if (!isBulk && productType !== 'agrupado') {
            // SKU product
            if (diff < 0) {
                document.getElementById('esDeleteWarningText').textContent = `Se eliminarán ${Math.abs(diff)} SKU(s) disponibles del sistema. Los SKUs asignados/instalados no se verán afectados.`;
                delWarn.style.display = '';
            } else if (diff > 0) {
                document.getElementById('esGenerateInfoText').textContent = `Se generarán ${diff} nuevo(s) SKU(s) con código TRB-XXXXXX automáticamente.`;
                genInfo.style.display = '';
            }
        }
    };

    window.esVariantAdjust = function(variantId, delta) {
        const inp = document.getElementById(`esVariant_${variantId}`);
        if (!inp) return;
        const cur = parseFloat(inp.value) || 0;
        inp.value = Math.max(0, cur + delta);
        esUpdateAgrupadoTotal();
    };

    window.esUpdateAgrupadoTotal = function() {
        let total = 0;
        document.querySelectorAll('#esVariantsList input[type="number"]').forEach(inp => {
            const newVal = parseFloat(inp.value) || 0;
            const orig = parseFloat(inp.dataset.original) || 0;
            const diff = newVal - orig;
            total += newVal;

            const variantId = inp.id.replace('esVariant_', '');
            const changeCell = document.getElementById(`esVariantChange_${variantId}`);
            if (changeCell) {
                if (diff === 0) {
                    changeCell.innerHTML = '<span style="font-size:0.85rem;font-weight:700;color:var(--text-muted);">±0</span>';
                } else if (diff > 0) {
                    changeCell.innerHTML = `<span style="font-size:0.85rem;font-weight:700;color:#10b981;">+${diff}</span>`;
                } else {
                    changeCell.innerHTML = `<span style="font-size:0.85rem;font-weight:700;color:#ef4444;">${diff}</span>`;
                }
            }
        });
        const totalEl = document.getElementById('esAgrupadoTotalVal');
        if (totalEl) totalEl.textContent = total;
    };

    window.saveEditStockModal = async function() {
        const productId = document.getElementById('esProductId').value;
        const productType = document.getElementById('esProductType').value;
        const isBulk = document.getElementById('esIsBulk').value == 1;
        const notes = document.getElementById('esNotes').value;
        const btn = document.getElementById('esSaveBtn');
        const origText = btn.innerHTML;

        btn.disabled = true;
        btn.innerHTML = '<i class="ph ph-spinner ph-spin"></i> Guardando...';

        try {
            if (productType === 'agrupado') {
                // Build list of variant updates
                const variants = [];
                document.querySelectorAll('#esVariantsList input[type="number"]').forEach(inp => {
                    variants.push({
                        id: inp.id.replace('esVariant_', ''),
                        new_quantity: parseFloat(inp.value) || 0
                    });
                });

                const fd = new FormData();
                fd.append('action', 'adjust_variant_stock');
                fd.append('variants', JSON.stringify(variants));
                fd.append('notes', notes);

                const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fd }).then(r => r.json());
                if (res.success) {
                    if (window.showToast) window.showToast(res.message || 'Stock actualizado', 'success');
                    closeEditStockModal();
                    loadProducts();
                    if (window.loadMetrics) loadMetrics();
                } else {
                    if (window.showToast) window.showToast(res.message || 'Error', 'error');
                }
            } else {
                const newQty = parseFloat(document.getElementById('esNewQty').value) || 0;
                const fd = new FormData();
                fd.append('action', 'adjust_product_stock');
                fd.append('product_id', productId);
                fd.append('new_total', newQty);
                fd.append('notes', notes);

                const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fd }).then(r => r.json());
                if (res.success) {
                    if (window.showToast) window.showToast(res.message || 'Stock actualizado', 'success');
                    closeEditStockModal();
                    loadProducts();
                    if (window.loadMetrics) loadMetrics();
                    if (typeof loadAllSkus === 'function') loadAllSkus();
                } else {
                    if (window.showToast) window.showToast(res.message || 'Error', 'error');
                }
            }
        } catch(e) {
            if (window.showToast) window.showToast('Error de conexión', 'error');
        }

        btn.disabled = false;
        btn.innerHTML = origText;
    };

    // ── History Modal Logic ──

    window.openHistoryModal = function() {
        console.log("-> openHistoryModal called");
        const hm = document.getElementById('historyModal');
        if (!hm) {
            console.error("-> ERROR: historyModal element not found!");
            if (window.showToast) window.showToast('Error: Modal de historial no encontrado', 'error');
            return;
        }
        console.log("-> historyModal found, current parent:", hm.parentElement.tagName);
        if (hm.parentElement !== document.body) {
            console.log("-> Moving historyModal to document.body");
            document.body.appendChild(hm);
        }
        hm.classList.add('active');
        console.log("-> Added 'active' class to historyModal");
        
        try {
            populateHistoryFilters();
            if (!window.historyLoaded) {
                console.log("-> Loading history data for the first time");
                loadAssignmentHistory();
                loadStockLog();
                window.historyLoaded = true;
            }
        } catch (e) {
            console.error("-> Error initializing history:", e);
        }
    };

    window.switchHistoryTab = function(tab) {
        document.querySelectorAll('.inv-history-tab').forEach(t => t.classList.toggle('active', t.dataset.htab === tab));
        document.querySelectorAll('.inv-history-pane').forEach(p => p.classList.remove('active'));
        document.getElementById('htab-' + tab).classList.add('active');
    };

    async function populateHistoryFilters() {
        // Populate histFilterUser
        const fdUser = new FormData(); fdUser.append('action', 'list_users');
        const resUser = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fdUser }).then(r => r.json());
        if (resUser.success) {
            const sel = document.getElementById('histFilterUser');
            sel.innerHTML = '<option value="">Todos</option>';
            resUser.data.forEach(u => sel.innerHTML += `<option value="${u.id}">${esc(u.name)}</option>`);
        }

        // Populate histStockProduct
        if (window.lastProductsData) {
            const selProd = document.getElementById('histStockProduct');
            selProd.innerHTML = '<option value="">Todos</option>';
            window.lastProductsData.forEach(p => {
                selProd.innerHTML += `<option value="${p.id}">${esc(p.name)}</option>`;
            });
        }
    }

    window.loadAssignmentHistory = async function() {
        const sku = document.getElementById('histFilterSku').value;
        const user = document.getElementById('histFilterUser').value;
        const date = document.getElementById('histFilterDateFrom').value;
        
        const fd = new FormData();
        fd.append('action', 'get_assignment_log');
        if (sku) fd.append('sku', sku);
        if (user) fd.append('user_id', user);
        if (date) fd.append('date_from', date);

        const body = document.getElementById('histAssignBody');
        body.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:20px;">Cargando...</td></tr>';
        
        try {
            const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fd }).then(r => r.json());
            if (res.success && res.data.length > 0) {
                let html = '';
                res.data.forEach(d => {
                    const isUnassign = d.action === 'unassign';
                    const dateForInput = d.created_at ? d.created_at.replace(' ', 'T').substring(0, 16) : '';
                    const notesEsc = (d.notes || '').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                    // Determine product type from sku_code prefix or store it
                    const isBulkSku = (d.sku_code === 'GRANEL' || (d.sku_id == null && !d.sku_code?.startsWith('TRB-')));
                    const productType = isBulkSku ? 'granel' : 'normal';
                    html += `<tr>
                        <td>${esc(d.created_at)}</td>
                        <td><code>${esc(d.sku_code || '')}</code></td>
                        <td>${esc(d.product_name || '')}</td>
                        <td>${esc(d.assigned_to_name || '')}</td>
                        <td>
                            <span class="status-badge" style="background:${isUnassign?'#fef2f2':'#ecfdf5'}; color:${isUnassign?'#ef4444':'#10b981'};">
                                ${isUnassign ? 'Desasignado' : 'Asignado'} (${d.quantity}${d.is_epp == 1 ? ' EPP' : ''})
                            </span>
                        </td>
                        <td>${esc(d.assigned_by_name || 'Sistema')}</td>
                        <td>
                            <div style="display:flex;gap:4px;">
                                <button onclick="openEditAssignLog(${d.id},'${dateForInput}','${notesEsc}',${d.assigned_to||0},${d.quantity||1},'${productType}','${isUnassign?'unassign':'assign'}')" title="Editar" class="btn btn-sm" style="background:rgba(99,102,241,0.12);color:#6366f1;border:none;padding:5px 8px;border-radius:6px;cursor:pointer;" onmouseover="this.style.background='rgba(99,102,241,0.25)'" onmouseout="this.style.background='rgba(99,102,241,0.12)'"><i class="ph ph-pencil"></i></button>
                                <button onclick="deleteAssignLog(${d.id})" title="Eliminar" class="btn btn-sm" style="background:rgba(239,68,68,0.12);color:#ef4444;border:none;padding:5px 8px;border-radius:6px;cursor:pointer;" onmouseover="this.style.background='rgba(239,68,68,0.25)'" onmouseout="this.style.background='rgba(239,68,68,0.12)'"><i class="ph ph-trash"></i></button>
                            </div>
                        </td>
                    </tr>`;
                });
                body.innerHTML = html;
            } else {
                body.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:20px;color:var(--text-muted);">No se encontraron registros.</td></tr>';
            }
        } catch (e) {
            body.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:20px;color:red;">Error de conexión.</td></tr>';
        }
    };

    // ── Edit/Delete Assignment Log ──
    // Cache for users list
    let _editAssignUsers = null;

    async function _loadEditAssignUsers() {
        if (_editAssignUsers) return _editAssignUsers;
        const fd = new FormData(); fd.append('action', 'list_users');
        const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fd }).then(r => r.json());
        if (res.success) { _editAssignUsers = res.data; }
        return _editAssignUsers || [];
    }

    window.openEditAssignLog = async function(id, dateStr, notes, assignedToId, quantity, productType, action) {
        document.getElementById('editAssignLogId').value = id;
        document.getElementById('editAssignLogDate').value = dateStr || '';
        document.getElementById('editAssignLogNotes').value = notes || '';
        document.getElementById('editAssignLogProductType').value = productType || 'normal';
        document.getElementById('editAssignLogIsUnassign').value = (action === 'unassign') ? '1' : '0';
        document.getElementById('editAssignLogQty').value = quantity || 1;

        // Show/hide quantity field and hint based on product type
        const qtyWrap = document.getElementById('editAssignLogQtyWrap');
        const qtyHint = document.getElementById('editAssignLogQtyHint');
        if (productType === 'granel') {
            qtyWrap.style.display = '';
            qtyHint.textContent = 'Producto a granel/agrupado — puedes editar la cantidad asignada.';
        } else {
            // For normal SKU products, quantity is 1 per SKU, but allow editing
            qtyWrap.style.display = '';
            qtyHint.textContent = 'SKU individual — normalmente la cantidad es 1.';
        }

        // Show/hide unassign hint
        const hint = document.getElementById('editAssignLogUnassignHint');
        const userSel = document.getElementById('editAssignLogUser');
        if (action === 'unassign') {
            hint.style.display = 'block';
            userSel.disabled = true;
        } else {
            hint.style.display = 'none';
            userSel.disabled = false;
        }

        // Open modal first
        const m = document.getElementById('editAssignLogModal');
        if (m.parentElement !== document.body) document.body.appendChild(m);
        m.classList.add('active');

        // Load users into select
        userSel.innerHTML = '<option value="">Cargando...</option>';
        const users = await _loadEditAssignUsers();
        userSel.innerHTML = '<option value="">— Seleccionar usuario —</option>';
        users.forEach(u => {
            const opt = document.createElement('option');
            opt.value = u.id;
            opt.textContent = u.name;
            if (u.id == assignedToId) opt.selected = true;
            userSel.appendChild(opt);
        });
        if (action === 'unassign') userSel.disabled = true;
    };

    window.editAssignLogSetUnassign = function() {
        document.getElementById('editAssignLogIsUnassign').value = '1';
        document.getElementById('editAssignLogUnassignHint').style.display = 'block';
        document.getElementById('editAssignLogUser').disabled = true;
    };

    window.editAssignLogCancelUnassign = function() {
        document.getElementById('editAssignLogIsUnassign').value = '0';
        document.getElementById('editAssignLogUnassignHint').style.display = 'none';
        document.getElementById('editAssignLogUser').disabled = false;
    };

    window.closeEditAssignLog = function() {
        document.getElementById('editAssignLogModal').classList.remove('active');
    };

    window.saveEditAssignLog = async function() {
        const id = document.getElementById('editAssignLogId').value;
        const date = document.getElementById('editAssignLogDate').value;
        const notes = document.getElementById('editAssignLogNotes').value;
        const userId = document.getElementById('editAssignLogUser').value;
        const qty = document.getElementById('editAssignLogQty').value;
        const isUnassign = document.getElementById('editAssignLogIsUnassign').value === '1';
        if (!id) return;
        const btn = document.querySelector('#editAssignLogModal .btn-primary');
        const origText = btn.innerHTML;
        btn.disabled = true; btn.innerHTML = '<i class="ph ph-spinner ph-spin"></i> Guardando...';
        const fd = new FormData();
        fd.append('action', 'update_assignment_log');
        fd.append('log_id', id);
        fd.append('notes', notes);
        if (date) fd.append('created_at', date.replace('T', ' ') + ':00');
        if (isUnassign) {
            fd.append('log_action', 'unassign');
        } else {
            fd.append('log_action', 'assign');
            if (userId) fd.append('assigned_to', userId);
        }
        if (qty) fd.append('quantity', qty);
        try {
            const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fd }).then(r => r.json());
            if (res.success) {
                if (window.showToast) window.showToast('Registro actualizado', 'success');
                closeEditAssignLog();
                loadAssignmentHistory();
            } else {
                if (window.showToast) window.showToast(res.message || 'Error', 'error');
            }
        } catch(e) {
            if (window.showToast) window.showToast('Error de conexión', 'error');
        }
        btn.disabled = false; btn.innerHTML = origText;
    };

    window.deleteAssignLog = async function(id) {
        const ok = await invConfirm('¿Eliminar registro?', 'Se eliminará este registro del historial de asignaciones. No afecta las asignaciones actuales.');
        if (!ok) return;
        const fd = new FormData(); fd.append('action', 'delete_assignment_log'); fd.append('log_id', id);
        const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fd }).then(r => r.json());
        if (res.success) {
            if (window.showToast) window.showToast('Registro eliminado', 'success');
            loadAssignmentHistory();
        } else {
            if (window.showToast) window.showToast(res.message || 'Error', 'error');
        }
    };

    window.loadStockLog = async function() {
        const product = document.getElementById('histStockProduct').value;
        const date = document.getElementById('histStockDateFrom').value;
        
        const fd = new FormData();
        fd.append('action', 'get_stock_log');
        if (product) fd.append('product_id', product);
        if (date) fd.append('date_from', date);

        const body = document.getElementById('histStockBody');
        body.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:20px;">Cargando...</td></tr>';
        
        try {
            const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fd }).then(r => r.json());
            if (res.success && res.data.length > 0) {
                let html = '';
                res.data.forEach(d => {
                    let skus = [];
                    try { skus = JSON.parse(d.sku_codes || '[]'); } catch(e){}
                    const notesEsc = (d.notes || '').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                    html += `<tr>
                        <td>${esc(d.created_at)}</td>
                        <td>${esc(d.product_name || '')}</td>
                        <td><span style="font-weight:600;color:var(--primary-color);">${d.quantity > 0 ? '+'+d.quantity : d.quantity}</span></td>
                        <td><div style="max-height:60px;overflow-y:auto;font-size:0.8rem;">${skus.map(s=>`<code>${esc(s)}</code>`).join(', ') || '—'}</div></td>
                        <td>${esc(d.user_name || '')}</td>
                        <td>${esc(d.notes || '—')}</td>
                        <td>
                            <div style="display:flex;gap:4px;">
                                <button onclick="openEditStockLog(${d.id},${d.quantity},'${notesEsc}')" title="Editar" style="background:rgba(139,92,246,0.12);color:#8b5cf6;border:none;padding:5px 8px;border-radius:6px;cursor:pointer;" onmouseover="this.style.background='rgba(139,92,246,0.25)'" onmouseout="this.style.background='rgba(139,92,246,0.12)'"><i class="ph ph-pencil"></i></button>
                                <button class="btn btn-danger btn-sm" onclick="deleteStockLog(${d.id})" title="Eliminar"><i class="ph ph-trash"></i></button>
                            </div>
                        </td>
                    </tr>`;
                });
                body.innerHTML = html;
            } else {
                body.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:20px;color:var(--text-muted);">No se encontraron registros.</td></tr>';
            }
        } catch (e) {
            body.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:20px;color:red;">Error de conexión.</td></tr>';
        }
    };

    // ── Edit Stock Log Modal ──
    window.openEditStockLog = function(id, qty, notes) {
        document.getElementById('editStockLogId').value = id;
        document.getElementById('editStockLogQty').value = qty;
        document.getElementById('editStockLogNotes').value = notes || '';
        const m = document.getElementById('editStockLogModal');
        if (m.parentElement !== document.body) document.body.appendChild(m);
        m.classList.add('active');
    };

    window.closeEditStockLog = function() {
        document.getElementById('editStockLogModal').classList.remove('active');
    };

    window.saveEditStockLog = async function() {
        const id = document.getElementById('editStockLogId').value;
        const qty = document.getElementById('editStockLogQty').value;
        const notes = document.getElementById('editStockLogNotes').value;
        if (!id) return;
        const btn = document.querySelector('#editStockLogModal .btn-primary');
        const origText = btn.innerHTML;
        btn.disabled = true; btn.innerHTML = '<i class="ph ph-spinner ph-spin"></i> Guardando...';
        const fd = new FormData();
        fd.append('action', 'update_stock_log');
        fd.append('log_id', id);
        fd.append('quantity', qty);
        fd.append('notes', notes);
        try {
            const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fd }).then(r => r.json());
            if (res.success) {
                if (window.showToast) window.showToast('Registro actualizado', 'success');
                closeEditStockLog();
                loadStockLog();
                if (window.loadProducts) loadProducts();
                if (window.loadMetrics) loadMetrics();
            } else {
                if (window.showToast) window.showToast(res.message || 'Error', 'error');
            }
        } catch(e) {
            if (window.showToast) window.showToast('Error de conexión', 'error');
        }
        btn.disabled = false; btn.innerHTML = origText;
    };

    window.deleteStockLog = async function(id) {
        const ok = await invConfirm('¿Eliminar registro?', 'Se restará el stock y se eliminarán los SKUs generados (si no han sido usados).');
        if (!ok) return;
        const fd = new FormData();
        fd.append('action', 'delete_stock_log');
        fd.append('log_id', id);
        const res = await fetch(BASE + '/ajax/inventario.php', { method: 'POST', body: fd }).then(r => r.json());
        if (res.success) {
            if (window.showToast) window.showToast('Registro eliminado', 'success');
            loadStockLog();
            if (window.loadProducts) loadProducts();
            if (window.loadMetrics) loadMetrics();
        } else {
            if (window.showToast) window.showToast(res.message, 'error');
        }
    };

})();

// ══════════════════════════════════════════════════════════════
// Google Sheets Sync Manager — SheetsSync
// ══════════════════════════════════════════════════════════════
window.SheetsSync = (function() {
    const BASE = (function() {
        const s = document.querySelector('script[src*="inventario.js"]');
        return s ? s.src.split('/modules/')[0] : '';
    })();
    const AJAX = BASE + '/ajax/google_sheets.php';

    let _configOk = false;
    let _sheetUrl = '#';

    // ── Open modal ──
    function openModal() {
        const m = document.getElementById('sheetsSyncModal');
        if (!m) return;
        if (m.parentElement !== document.body) document.body.appendChild(m);
        m.classList.add('active');
        checkConfig();
    }

    function closeModal() {
        document.getElementById('sheetsSyncModal')?.classList.remove('active');
    }

    // ── Check config status ──
    async function checkConfig() {
        setCheck('chkLibrary', 'loading');
        setCheck('chkCreds',   'loading');
        setCheck('chkSheet',   'loading');
        document.getElementById('sheetsActions').style.display    = 'none';
        document.getElementById('sheetsSetupGuide').style.display = 'none';
        document.getElementById('sheetsIdInputWrap').style.display= 'none';
        document.getElementById('sheetsLog').style.display        = 'none';

        try {
            const fd = new FormData(); fd.append('action', 'check_config');
            const res = await fetch(AJAX, { method: 'POST', body: fd }).then(r => r.json());
            setCheck('chkLibrary', res.library   ? 'ok' : 'error');
            setCheck('chkCreds',   res.credentials? 'ok' : 'error');
            setCheck('chkSheet',   res.spreadsheet_id ? 'ok' : 'error');

            _configOk = res.success;
            updateDot(_configOk ? 'connected' : 'error');

            if (_configOk) {
                _sheetUrl = 'https://docs.google.com/spreadsheets/d/' + res.sheet_id + '/edit';
                document.getElementById('sheetsOpenLink').href = _sheetUrl;
                const act = document.getElementById('sheetsActions');
                act.style.display = 'flex';
            } else {
                document.getElementById('sheetsSetupGuide').style.display = 'block';
                // Show email hint from cred file
                try {
                    const fd2 = new FormData(); fd2.append('action', 'get_service_email');
                    // Non-critical — ignore errors
                } catch(e) {}
            }
        } catch(e) {
            setCheck('chkLibrary', 'error');
            setCheck('chkCreds',   'error');
            setCheck('chkSheet',   'error');
            updateDot('error');
        }
    }

    function setCheck(id, state) {
        const el = document.getElementById(id);
        if (!el) return;
        const icons = { loading: 'ph-spinner sheets-spin', ok: 'ph-check-circle', error: 'ph-x-circle' };
        el.querySelector('i').className = 'ph ' + icons[state];
    }

    function updateDot(state) {
        const dot = document.getElementById('sheetsDot');
        if (!dot) return;
        dot.className = 'sheets-status-dot' + (state !== 'none' ? ' ' + state : '');
    }

    // ── Export ──
    async function doExport() {
        const btn = document.getElementById('btnSheetsExport');
        btn.disabled = true;
        btn.innerHTML = '<i class="ph ph-spinner sheets-spin"></i> Exportando...';
        showLog('<i class="ph ph-spinner sheets-spin"></i> Exportando inventario a Google Sheets...', 'info');
        try {
            const fd = new FormData(); fd.append('action', 'export');
            const res = await fetch(AJAX, { method: 'POST', body: fd }).then(r => r.json());
            if (res.success) {
                showLog('✅ ' + res.message, 'success');
                if (res.url) {
                    document.getElementById('sheetsOpenLink').href = res.url;
                }
                if (window.showToast) window.showToast(res.message, 'success');
            } else {
                showLog('❌ ' + res.message, 'error');
                if (window.showToast) window.showToast(res.message, 'error');
            }
        } catch(e) {
            showLog('❌ Error de conexión: ' + e.message, 'error');
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="ph ph-upload"></i> Exportar';
    }

    // ── Import ──
    async function doImport() {
        const btn = document.getElementById('btnSheetsImport');
        btn.disabled = true;
        btn.innerHTML = '<i class="ph ph-spinner sheets-spin"></i> Importando...';
        showLog('<i class="ph ph-spinner sheets-spin"></i> Leyendo hoja Productos desde Google Sheets...', 'info');
        try {
            const fd = new FormData(); fd.append('action', 'import');
            const res = await fetch(AJAX, { method: 'POST', body: fd }).then(r => r.json());
            if (res.success) {
                showLog(`✅ ${res.message}<br><small style="color:var(--text-muted);">${res.imported} productos nuevos · ${res.updated} actualizados</small>`, 'success');
                if (window.showToast) window.showToast(res.message, 'success');
                // Refresh products table
                if (typeof loadProducts === 'function') loadProducts();
            } else {
                showLog('❌ ' + res.message, 'error');
                if (window.showToast) window.showToast(res.message, 'error');
            }
        } catch(e) {
            showLog('❌ Error de conexión: ' + e.message, 'error');
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="ph ph-download"></i> Importar';
    }

    // ── Show/hide Sheet ID input ──
    function openConfigFile() {
        const w = document.getElementById('sheetsIdInputWrap');
        w.style.display = w.style.display === 'flex' ? 'none' : 'flex';
        document.getElementById('sheetsIdInput').focus();
    }

    // ── Save Sheet ID via AJAX (writes to config file via PHP) ──
    async function saveSheetId() {
        const id = document.getElementById('sheetsIdInput').value.trim();
        // Extract ID from full URL if pasted
        const match = id.match(/\/d\/([a-zA-Z0-9_-]+)/);
        const sheetId = match ? match[1] : id;
        if (!sheetId) {
            showLog('⚠️ Pega el ID o URL del Google Sheet.', 'warn');
            return;
        }
        try {
            const fd = new FormData(); fd.append('action', 'save_sheet_id'); fd.append('sheet_id', sheetId);
            const res = await fetch(AJAX, { method: 'POST', body: fd }).then(r => r.json());
            if (res.success) {
                showLog('✅ ID guardado. Recargando estado...', 'success');
                document.getElementById('sheetsIdInputWrap').style.display = 'none';
                setTimeout(checkConfig, 800);
            } else {
                showLog('❌ ' + (res.message || 'No se pudo guardar. Edita config/google_sheets.php manualmente.'), 'error');
            }
        } catch(e) {
            showLog('❌ Error: ' + e.message, 'error');
        }
    }

    // ── Log display ──
    function showLog(html, type) {
        const el = document.getElementById('sheetsLog');
        if (!el) return;
        const colors = { success: '#0f9d58', error: '#ef4444', warn: '#f59e0b', info: 'var(--text-muted)' };
        el.style.display = 'block';
        el.style.color = colors[type] || 'var(--text-color)';
        el.innerHTML = html;
    }

    return {
        openModal,
        closeModal,
        export: doExport,
        import: doImport,
        openConfigFile,
        saveSheetId,
        checkConfig
    };
})();
