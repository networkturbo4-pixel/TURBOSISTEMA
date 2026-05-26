<?php
$file = 'c:/xampp/htdocs/TURBOSAAS/modules/inventario/inventario.js';
$c = file_get_contents($file);

// Replace 1: grid mapping
$t1 = "wrap.style.display = 'block'; empty.style.display = 'none';\n                grid.innerHTML = res.data.map((p, i) => `";
if (strpos($c, $t1) === false) {
    $t1 = "wrap.style.display = 'block'; empty.style.display = 'none';\r\n                grid.innerHTML = res.data.map((p, i) => `";
}

$r1 = "if (!window.selectedProducts) window.selectedProducts = new Set();\n                window.lastProductsData = res.data;\n                wrap.style.display = 'block'; empty.style.display = 'none';\n                grid.innerHTML = res.data.map((p, i) => `";
$c = str_replace($t1, $r1, $c);

// Replace 2: row-selected class
$t2 = '<tr class="product-row" data-product-id="${p.id}"';
$r2 = '<tr class="product-row ${window.selectedProducts && window.selectedProducts.has(p.id) ? \'row-selected\' : \'\'}" data-product-id="${p.id}"';
$c = str_replace($t2, $r2, $c);

// Replace 3: Checkbox column in Products
$t3 = '<td data-label="Producto">';
$r3 = <<<EOT
<td style="text-align:center;width:40px;vertical-align:middle;">
                            <input type="checkbox" class="prod-row-check form-check-input" value="\${p.id}" \${window.selectedProducts && window.selectedProducts.has(p.id) ? 'checked' : ''} onchange="toggleProductSelection(this, \${p.id})">
                        </td>
                        <td data-label="Producto">
EOT;
if (strpos($c, 'toggleProductSelection') === false) {
    $c = implode($r3, explode($t3, $c, 2));
}

// Replace 4: Append logic
$t4 = "})();\r\n";
if (strpos($c, $t4) === false) $t4 = "})();\n";
// Let's replace ONLY the LAST occurrence of "})();"
$r4 = <<<EOT
})();

// Bulk Selection & Export (Products)

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
        bar.querySelector('.prod-action-count').textContent = `\${window.selectedProducts.size} seleccionado\${window.selectedProducts.size > 1 ? 's' : ''}`;
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
    csvContent += colsToExport.map(c => `"\${c.replace(/"/g, '""')}"`).join(',') + '\\n';
    const exportData = (window.lastProductsData || []).filter(p => window.selectedProducts.has(p.id));
    exportData.forEach(p => {
        const row = [p.name || '', p.category_name || 'Sin cat.', p.total_quantity || 0, p.qty_disponible || 0, p.qty_instalado || 0, p.qty_malogrado || 0];
        csvContent += row.map(val => `"\${val.toString().replace(/"/g, '""')}"`).join(',') + '\\n';
    });
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `productos_seleccion_\${new Date().getTime()}.csv`;
    link.style.display = 'none';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    if (window.showToast) window.showToast('Descarga completada.', 'success');
};
EOT;

if (strpos($c, 'exportSelectedProductsToExcel') === false) {
    $pos = strrpos($c, "})();");
    if ($pos !== false) {
        $c = substr_replace($c, $r4, $pos, 5);
    }
}

file_put_contents($file, $c);
echo "done";
?>
