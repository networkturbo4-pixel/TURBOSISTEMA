<?php
$file = 'c:/xampp/htdocs/TURBOSAAS/modules/inventario/inventario_v2.js';
$c = file_get_contents($file);

// Replace 1: Add row-selected class
$t1 = 'return `<tr class="${isChecked ? \'row-selected\' : \'\'}">${rowHtml}${cells}</tr>`;';
if (strpos($c, $t1) === false) {
    $c = str_replace('return `<tr>${rowHtml}${cells}</tr>`;', $t1, $c);
}

// Replace 2: Add width:40px to # column and text-align center padding
$t2 = "'#': `<td>\${i+1}</td>`,";
$r2 = "'#': `<td style=\"width:40px;text-align:center;padding-left:4px;padding-right:4px;\">\${i+1}</td>`,";
$c = str_replace($t2, $r2, $c);

// Replace 3: Add the IIFE closing and append the export logic properly
$t3 = <<<EOT
        checkConfig
    };
})();
EOT;

$r3 = <<<EOT
        checkConfig
    };
})();

// ── Bulk Selection & Export (Stock) ──

window.toggleSkuSelection = function(cb, skuId) {
    if (cb.checked) {
        selectedSkus.add(skuId);
    } else {
        selectedSkus.delete(skuId);
        const checkAll = document.getElementById('skuCheckAll');
        if (checkAll) checkAll.checked = false;
    }
    const tr = cb.closest('tr');
    if (tr) {
        if (cb.checked) tr.classList.add('row-selected');
        else tr.classList.remove('row-selected');
    }
    updateActionBar();
};

window.toggleAllSkus = function(cb) {
    const checkboxes = document.querySelectorAll('.sku-row-check');
    checkboxes.forEach(c => {
        c.checked = cb.checked;
        const skuId = parseInt(c.value);
        if (cb.checked) {
            selectedSkus.add(skuId);
            c.closest('tr')?.classList.add('row-selected');
        } else {
            selectedSkus.delete(skuId);
            c.closest('tr')?.classList.remove('row-selected');
        }
    });
    updateActionBar();
};

window.updateActionBar = function() {
    let bar = document.getElementById('skuActionBar');
    if (!bar) {
        bar = document.createElement('div');
        bar.id = 'skuActionBar';
        bar.className = 'sku-action-bar';
        bar.style.display = 'none';
        
        bar.innerHTML = `
            <div style="display:flex;align-items:center;gap:12px;">
                <span class="sku-action-count" style="font-weight:600;font-size:0.9rem;">0 seleccionados</span>
                <button class="btn btn-primary btn-sm" onclick="exportSelectedSkusToExcel()">
                    <i class="ph ph-file-csv"></i> Descargar Excel
                </button>
                <button class="btn btn-secondary btn-sm" onclick="clearSkuSelection()">
                    <i class="ph ph-x"></i> Cancelar
                </button>
            </div>
        `;
        const tableContainer = document.querySelector('#tab-stock .table-responsive');
        if (tableContainer) {
            tableContainer.parentNode.insertBefore(bar, tableContainer);
        }
    }

    if (selectedSkus.size > 0) {
        bar.style.display = 'flex';
        bar.style.padding = '8px 16px';
        bar.style.background = 'rgba(99, 102, 241, 0.1)';
        bar.style.border = '1px solid var(--primary-color)';
        bar.style.borderRadius = '8px';
        bar.style.marginBottom = '12px';
        bar.style.alignItems = 'center';
        bar.querySelector('.sku-action-count').textContent = `\${selectedSkus.size} seleccionado\${selectedSkus.size > 1 ? 's' : ''}`;
    } else {
        bar.style.display = 'none';
        const checkAll = document.getElementById('skuCheckAll');
        if (checkAll) checkAll.checked = false;
    }
};

window.clearSkuSelection = function() {
    selectedSkus.clear();
    const checkboxes = document.querySelectorAll('.sku-row-check');
    checkboxes.forEach(c => {
        c.checked = false;
        c.closest('tr')?.classList.remove('row-selected');
    });
    const checkAll = document.getElementById('skuCheckAll');
    if (checkAll) checkAll.checked = false;
    updateActionBar();
};

window.exportSelectedSkusToExcel = function() {
    if (selectedSkus.size === 0) return;
    
    let csvContent = "\\uFEFF"; 
    const colsToExport = window._currentColumnOrder ? window._currentColumnOrder.filter(c => c !== '#' && c !== 'Acción') : ['SKU', 'Producto', 'Categoría', 'Estado'];
    csvContent += colsToExport.map(c => `"\${c.replace(/"/g, '""')}"`).join(',') + '\\n';
    
    const exportData = window.lastSkuData ? window.lastSkuData.filter(s => selectedSkus.has(s.id)) : [];
    
    exportData.forEach(s => {
        const cd = s.custom_data ? (typeof s.custom_data === 'string' ? JSON.parse(s.custom_data) : s.custom_data) : {};
        const row = colsToExport.map(col => {
            let val = '';
            if (col === 'SKU') val = s.sku_code || '';
            else if (col === 'Producto') val = s.product_name || '';
            else if (col === 'Categoría') val = s.category_name || '';
            else if (col === 'Estado') val = s.status || '';
            else if (col === 'Historia') val = s.historia || '';
            else if (col === 'Últ. Actividad') val = s.last_history_date || '';
            else if (col === 'Instalado a') val = s.acta_cliente || '';
            else if (col === 'Asignado') val = s.assigned_user_name || '';
            else if (col === 'Fecha Registro') val = s.sku_created_at ? new Date(s.sku_created_at).toLocaleString() : '';
            else val = cd[col] || '';
            return `"\${val.toString().replace(/"/g, '""')}"`;
        });
        csvContent += row.join(',') + '\\n';
    });
    
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `inventario_seleccion_\${new Date().getTime()}.csv`;
    link.style.display = 'none';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    if (window.showToast) window.showToast('Descarga completada.', 'success');
};

// ── Sku Column Filter Popover ──
window.openSkuFilter = function(col, anchorBtn) {
    if (window.skuFilterPopover) { window.skuFilterPopover.remove(); window.skuFilterPopover = null; }
    
    const popover = document.createElement('div');
    popover.id = 'cfPopover'; 
    popover.style.position = 'absolute';
    popover.style.zIndex = '9999';
    
    // We get current filter directly from ColFilter if possible or global
    const currentVal = window._colFilters ? (window._colFilters[col] || '') : '';
    const bodyHtml = `<div style="padding:12px;"><input type="text" id="skuFilterInput" class="form-control" placeholder="Buscar en \${col}..." value="\${currentVal}" style="font-size:0.85rem;padding:6px 10px;"></div>`;
    const clearBtn = currentVal ? `<div class="cf-pop-clear"><button onclick="window.clearSkuFilter('\${col}')"><i class="ph ph-eraser"></i> Limpiar este filtro</button></div>` : '';
    
    popover.innerHTML = `<div class="cf-pop-header"><span><i class="ph ph-funnel-simple" style="color:var(--primary-color);margin-right:4px;"></i>\${col}</span><button onclick="window.closeSkuFilter()"><i class="ph ph-x"></i></button></div><div class="cf-pop-body">\${bodyHtml}\${clearBtn}</div>`;
    
    document.body.appendChild(popover);
    window.skuFilterPopover = popover;
    
    const rect = anchorBtn.getBoundingClientRect();
    let left = rect.left + window.scrollX;
    let top = rect.bottom + window.scrollY + 8;
    popover.style.left = left + 'px';
    popover.style.top = top + 'px';
    
    setTimeout(() => {
        const input = document.getElementById('skuFilterInput');
        if (input) {
            input.focus();
            input.setSelectionRange(input.value.length, input.value.length);
        }
    }, 50);
    
    document.getElementById('skuFilterInput').addEventListener('input', (e) => {
        // Find updateColumnFilter function in global scope or expose it
        if (typeof window.updateColumnFilter === 'function') {
            window.updateColumnFilter(col, e.target.value);
        }
        const cb = popover.querySelector('.cf-pop-clear');
        if (e.target.value && !cb) {
            const newCb = document.createElement('div');
            newCb.className = 'cf-pop-clear';
            newCb.innerHTML = `<button onclick="window.clearSkuFilter('\${col}')"><i class="ph ph-eraser"></i> Limpiar este filtro</button>`;
            popover.querySelector('.cf-pop-body').appendChild(newCb);
        } else if (!e.target.value && cb) {
            cb.remove();
        }
    });
    
    if (window.skuFilterOutsideClick) document.removeEventListener('click', window.skuFilterOutsideClick);
    window.skuFilterOutsideClick = (e) => {
        if (!popover.contains(e.target) && !anchorBtn.contains(e.target)) {
            window.closeSkuFilter();
        }
    };
    setTimeout(() => document.addEventListener('click', window.skuFilterOutsideClick), 10);
};

window.closeSkuFilter = function() {
    if (window.skuFilterPopover) { window.skuFilterPopover.remove(); window.skuFilterPopover = null; }
    if (window.skuFilterOutsideClick) document.removeEventListener('click', window.skuFilterOutsideClick);
};

window.clearSkuFilter = function(col) {
    if (typeof window.updateColumnFilter === 'function') {
        window.updateColumnFilter(col, '');
    }
    window.closeSkuFilter();
};
EOT;

$c = str_replace($t3, $r3, $c);

// We need to make columnFilters and updateColumnFilter globally accessible so openSkuFilter can access them.
$c = str_replace('let columnFilters = {};', 'window._colFilters = {}; let columnFilters = window._colFilters;', $c);
$c = str_replace('function updateColumnFilter(col, val) {', 'window.updateColumnFilter = function(col, val) {', $c);

// We also need to expose columnOrder and lastSkuData
$c = str_replace('let columnOrder = null;', 'window._currentColumnOrder = null; let columnOrder = window._currentColumnOrder;', $c);
$c = str_replace('columnOrder = loadColumnOrder(customKeys);', 'columnOrder = loadColumnOrder(customKeys); window._currentColumnOrder = columnOrder;', $c);

$c = str_replace('let selectedSkus = new Set();', 'window.selectedSkus = new Set(); let selectedSkus = window.selectedSkus;', $c);
$c = str_replace('let lastSkuData = [];', 'window.lastSkuData = []; let lastSkuData = window.lastSkuData;', $c);

file_put_contents($file, $c);
echo "done";
?>
