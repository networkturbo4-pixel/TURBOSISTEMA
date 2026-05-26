<?php
$file = 'c:/xampp/htdocs/TURBOSAAS/modules/inventario/inventario_v2.js';
$c = file_get_contents($file);

// 1. Ensure window._colFilters exists globally
if (strpos($c, 'window._colFilters = {};') === false) {
    $c = "window._colFilters = {};\n" . $c;
}

// 2. Ensure updateColumnFilter exists
$updateFunc = <<<EOT

window.updateColumnFilter = function(col, val) {
    if (val.trim() === '') {
        delete window._colFilters[col];
    } else {
        window._colFilters[col] = val.trim();
    }
    // Refresh table with filters applied
    if (typeof loadAllSkus === 'function') {
        loadAllSkus();
    }
};

EOT;

if (strpos($c, 'window.updateColumnFilter = function') === false) {
    $c = $updateFunc . $c;
}

// 3. We also need to apply the filter logic in loadAllSkus BEFORE renderSkuTable
// In loadAllSkus: let displayData = sortColumn ? sortSkus(res.data, sortColumn, sortDirection) : res.data;
$targetLoad = "let displayData = sortColumn ? sortSkus(res.data, sortColumn, sortDirection) : res.data;";
$replaceLoad = <<<EOT
let displayData = res.data;
if (window._colFilters && Object.keys(window._colFilters).length > 0) {
    displayData = displayData.filter(s => {
        const cd = s.custom_data ? (typeof s.custom_data === 'string' ? JSON.parse(s.custom_data) : s.custom_data) : {};
        for (const [col, val] of Object.entries(window._colFilters)) {
            const search = val.toLowerCase();
            let sval = '';
            if (col === 'SKU') sval = s.sku_code || '';
            else if (col === 'Producto') sval = s.product_name || '';
            else if (col === 'Categoría') sval = s.category_name || '';
            else if (col === 'Estado') sval = s.status || '';
            else if (col === 'Historia') sval = s.historia || '';
            else if (col === 'Últ. Actividad') sval = s.last_history_date || '';
            else if (col === 'Instalado a') sval = s.acta_cliente || '';
            else if (col === 'Asignado') sval = s.assigned_user_name || '';
            else if (col === 'Fecha Registro') sval = s.sku_created_at ? new Date(s.sku_created_at).toLocaleString() : '';
            else sval = cd[col] || '';
            
            if (!sval.toString().toLowerCase().includes(search)) return false;
        }
        return true;
    });
}
if (sortColumn) displayData = sortSkus(displayData, sortColumn, sortDirection);
EOT;

if (strpos($c, $targetLoad) !== false) {
    $c = str_replace($targetLoad, $replaceLoad, $c);
}

file_put_contents($file, $c);
echo "done";
?>
