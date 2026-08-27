<?php
$file = 'c:/xampp/htdocs/TURBOSAAS/modules/inventario/inventario_v2.js';
$c = file_get_contents($file);

$t1 = <<<EOT
            });
            return cellMap;
        });

        tbody.innerHTML = rows.map(cellMap => {
            const cells = columnOrder.map((col, ci) => {
                let cell = cellMap[col] || '<td>\u2014</td>';
                if (ci < STICKY_COUNT) cell = cell.replace('<td', `<td class="sticky-col sticky-col-\${ci}"`);
                return cell;
            }).join('');
            return `<tr>\${cells}</tr>`;
        }).join('');

        // Render headers in column order
        const sortableColumns = {'SKU':'sku_code','Producto':'product_name','Categoría':'category_name','Estado':'status','Fecha Registro':'sku_created_at'};
        const thead = document.querySelector('#skuTable thead tr');
        thead.innerHTML = columnOrder.map((col, ci) => {
            const sortKey = sortableColumns[col];
            const sortHtml = sortKey ? ` \${si(sortKey)}` : '';
            const sortClass = sortKey ? ' sortable-th' : '';
            const sortClick = sortKey ? ` onclick="toggleSort('\${sortKey}')"` : '';
            const stickyClass = ci < STICKY_COUNT ? ` sticky-col sticky-col-\${ci} sticky-th` : '';
            const dragAttr = `draggable="true" data-colidx="\${ci}"`;
            return `<th class="draggable-th\${sortClass}\${stickyClass}"\${sortClick} \${dragAttr}>\${col}\${sortHtml}</th>`;
        }).join('');
EOT;

$r1 = <<<EOT
            });
            return { s, cellMap, index: i };
        });

        tbody.innerHTML = rows.map(({s, cellMap, index}) => {
            const isChecked = window.selectedSkus.has(s.id) ? 'checked' : '';
            let rowHtml = `<td class="sticky-col sticky-col-0" style="text-align:center;width:40px;vertical-align:middle;"><input type="checkbox" class="sku-row-check form-check-input" value="\${s.id}" \${isChecked} onchange="toggleSkuSelection(this, \${s.id})"></td>`;
            
            const cells = columnOrder.map((col, ci) => {
                let cell = cellMap[col] || '<td>\u2014</td>';
                if (ci < STICKY_COUNT) cell = cell.replace('<td', `<td class="sticky-col sticky-col-\${ci+1}"`);
                return cell;
            }).join('');
            return `<tr class="\${isChecked ? 'row-selected' : ''}">\${rowHtml}\${cells}</tr>`;
        }).join('');

        // Render headers in column order
        const sortableColumns = {'SKU':'sku_code','Producto':'product_name','Categoría':'category_name','Estado':'status','Fecha Registro':'sku_created_at'};
        const thead = document.querySelector('#skuTable thead tr');
        
        let theadHtml = `<th class="sticky-col sticky-col-0 sticky-th" style="width:40px;text-align:center;padding:12px 8px;vertical-align:middle;"><input type="checkbox" class="form-check-input" id="skuCheckAll" onchange="toggleAllSkus(this)" \${window.lastSkuData.length > 0 && window.selectedSkus.size === window.lastSkuData.length ? 'checked' : ''}></th>`;
        
        theadHtml += columnOrder.map((col, ci) => {
            const sortKey = sortableColumns[col];
            const sortHtml = sortKey ? ` \${si(sortKey)}` : '';
            const sortClass = sortKey ? ' sortable-th' : '';
            const sortClick = sortKey ? ` onclick="toggleSort('\${sortKey}')"` : '';
            const stickyClass = ci < STICKY_COUNT ? ` sticky-col sticky-col-\${ci+1} sticky-th` : '';
            const dragAttr = `draggable="true" data-colidx="\${ci}"`;
            
            const filterVal = window._colFilters[col] || '';
            const isFiltered = filterVal.trim() !== '';
            const filterHtml = col !== '#' && col !== 'Acción' 
                ? `<button class="cf-btn \${isFiltered ? 'cf-btn-active' : ''}" style="\${isFiltered ? 'background:var(--primary-color);color:#fff;border-color:var(--primary-color);' : ''}" onclick="event.stopPropagation(); openSkuFilter('\${col}', this)" title="Filtrar"><i class="ph ph-funnel-simple"></i></button>`
                : '';

            return `<th class="cf-th draggable-th\${sortClass}\${stickyClass}"\${sortClick} \${dragAttr}>
                <span>\${col}\${sortHtml}</span>
                \${filterHtml}
            </th>`;
        }).join('');
        thead.innerHTML = theadHtml;
EOT;

$c = str_replace($t1, $r1, $c);
file_put_contents($file, $c);
echo "done";
?>
