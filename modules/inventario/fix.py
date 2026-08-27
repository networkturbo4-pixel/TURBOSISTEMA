import re

with open("c:/xampp/htdocs/TURBOSAAS/modules/inventario/inventario.js", "r", encoding="utf8") as f:
    c = f.read()

# Replace 1
t1 = """            if (res.success && res.data.length > 0) {
                wrap.style.display = 'block'; empty.style.display = 'none';
                grid.innerHTML = res.data.map((p, i) => `"""
r1 = """            if (res.success && res.data.length > 0) {
                if (!window.selectedProducts) window.selectedProducts = new Set();
                window.lastProductsData = res.data;
                wrap.style.display = 'block'; empty.style.display = 'none';
                grid.innerHTML = res.data.map((p, i) => `"""
c = c.replace(t1, r1)

# Replace 2
t2 = """<tr class="product-row" data-product-id="${p.id}\""""
r2 = """<tr class="product-row ${window.selectedProducts && window.selectedProducts.has(p.id) ? 'row-selected' : ''}" data-product-id="${p.id}\""""
c = c.replace(t2, r2)

# Replace 3
t3 = """<td data-label="Producto">"""
r3 = """<td style="text-align:center;width:40px;vertical-align:middle;">
                            <input type="checkbox" class="prod-row-check form-check-input" value="${p.id}" ${window.selectedProducts && window.selectedProducts.has(p.id) ? 'checked' : ''} onchange="toggleProductSelection(this, ${p.id})">
                        </td>
                        <td data-label="Producto">"""
c = c.replace(t3, r3, 1)

# Replace 4
t4 = """        checkConfig
    };
})();"""
r4 = """        checkConfig
    };
})();

// ── Bulk Selection & Export (Products) ──

window.toggleProductSelection = function(cb, prodId) {
    if (!window.selectedProducts) window.selectedProducts = new Set();
    if (cb.checked) {
        window.selectedProducts.add(prodId);
    } else {
        window.selectedProducts.delete(prodId);
        const checkAll = document.getElementById('prodCheckAll');
        if (checkAll) checkAll.checked = false;
    }
    const tr = cb.closest('tr');
    if (tr) {
        if (cb.checked) tr.classList.add('row-selected');
        else tr.classList.remove('row-selected');
    }
    updateProdActionBar();
};

window.toggleAllProducts = function(cb) {
    if (!window.selectedProducts) window.selectedProducts = new Set();
    const checkboxes = document.querySelectorAll('.prod-row-check');
    checkboxes.forEach(c => {
        const tr = c.closest('tr');
        if (tr && tr.style.display !== 'none') {
            c.checked = cb.checked;
            const prodId = parseInt(c.value);
            if (cb.checked) {
                window.selectedProducts.add(prodId);
                tr.classList.add('row-selected');
            } else {
                window.selectedProducts.delete(prodId);
                tr.classList.remove('row-selected');
            }
        }
    });
    updateProdActionBar();
};

window.updateProdActionBar = function() {
    if (!window.selectedProducts) window.selectedProducts = new Set();
    let bar = document.getElementById('prodActionBar');
    if (!bar) {
        bar = document.createElement('div');
        bar.id = 'prodActionBar';
        bar.className = 'sku-action-bar';
        bar.style.display = 'none';
        bar.innerHTML = `
            <div style="display:flex;align-items:center;gap:12px;">
                <span class="prod-action-count" style="font-weight:600;font-size:0.9rem;">0 seleccionados</span>
                <button class="btn btn-primary btn-sm" onclick="exportSelectedProductsToExcel()">
                    <i class="ph ph-file-csv"></i> Descargar Excel
                </button>
                <button class="btn btn-secondary btn-sm" onclick="clearProductSelection()">
                    <i class="ph ph-x"></i> Cancelar
                </button>
            </div>
        `;
        const tableContainer = document.querySelector('#tab-productos .table-responsive');
        if (tableContainer) tableContainer.parentNode.insertBefore(bar, tableContainer);
    }
    if (window.selectedProducts.size > 0) {
        bar.style.display = 'flex';
        bar.style.padding = '8px 16px';
        bar.style.background = 'rgba(99, 102, 241, 0.1)';
        bar.style.border = '1px solid var(--primary-color)';
        bar.style.borderRadius = '8px';
        bar.style.marginBottom = '12px';
        bar.style.alignItems = 'center';
        bar.querySelector('.prod-action-count').textContent = `${window.selectedProducts.size} seleccionado${window.selectedProducts.size > 1 ? 's' : ''}`;
    } else {
        bar.style.display = 'none';
        const checkAll = document.getElementById('prodCheckAll');
        if (checkAll) checkAll.checked = false;
    }
};

window.clearProductSelection = function() {
    if (!window.selectedProducts) window.selectedProducts = new Set();
    window.selectedProducts.clear();
    const checkboxes = document.querySelectorAll('.prod-row-check');
    checkboxes.forEach(c => {
        c.checked = false;
        c.closest('tr')?.classList.remove('row-selected');
    });
    const checkAll = document.getElementById('prodCheckAll');
    if (checkAll) checkAll.checked = false;
    updateProdActionBar();
};

window.exportSelectedProductsToExcel = function() {
    if (!window.selectedProducts || window.selectedProducts.size === 0) return;
    const colsToExport = ['Producto', 'Categoría', 'Total', 'Disponibles', 'Instalados', 'Malogrados'];
    let csvContent = "\\uFEFF";
    csvContent += colsToExport.map(c => `"${c.replace(/"/g, '""')}"`).join(',') + '\\n';
    const exportData = (window.lastProductsData || []).filter(p => window.selectedProducts.has(p.id));
    exportData.forEach(p => {
        const row = [p.name || '', p.category_name || 'Sin cat.', p.total_quantity || 0, p.qty_disponible || 0, p.qty_instalado || 0, p.qty_malogrado || 0];
        csvContent += row.map(val => `"${val.toString().replace(/"/g, '""')}"`).join(',') + '\\n';
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
};"""
c = c.replace(t4, r4)

with open("c:/xampp/htdocs/TURBOSAAS/modules/inventario/inventario.js", "w", encoding="utf8") as f:
    f.write(c)
print("done")
