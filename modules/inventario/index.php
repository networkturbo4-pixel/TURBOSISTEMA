<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
require_once '../../config/db.php';
requireLogin();
requirePermission($pdo, 'inventario');
include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<link rel="stylesheet" href="<?php echo BASE_URL; ?>/modules/inventario/inventario.css?v=<?php echo time(); ?>">

<script>
window.addEventListener('error', function(e) {
    const div = document.createElement('div');
    div.style.position = 'fixed';
    div.style.top = '0';
    div.style.left = '0';
    div.style.width = '100%';
    div.style.background = '#fee2e2';
    div.style.color = '#991b1b';
    div.style.padding = '15px';
    div.style.borderBottom = '3px solid #ef4444';
    div.style.zIndex = '999999';
    div.style.fontFamily = 'monospace';
    div.style.fontSize = '14px';
    div.style.whiteSpace = 'pre-wrap';
    div.innerHTML = '<strong>Error de JS detectado:</strong><br>' + e.message + ' en ' + e.filename + ':' + e.lineno + ':' + e.colno + '<br><br>Pila de ejecución:<br>' + (e.error ? e.error.stack : 'No disponible');
    document.body.appendChild(div);
});
</script>



<!-- Metric Cards -->
<div id="metricCards">
    <div class="inv-metric-card">
        <div class="inv-metric-icon" style="background:rgba(236,72,153,0.1);"><i class="ph ph-list-numbers" style="color:#ec4899;"></i></div>
        <div class="text-end"><p class="inv-metric-value" id="metricProductos" style="color:#ec4899;">0</p><h3 class="inv-metric-title">Productos Registrados</h3></div>
    </div>
    <div class="inv-metric-card">
        <div class="inv-metric-icon" style="background:rgba(99,102,241,0.1);"><i class="ph ph-cube" style="color:#6366f1;"></i></div>
        <div class="text-end"><p class="inv-metric-value" id="metricTotal" style="color:#6366f1;">0</p><h3 class="inv-metric-title">Total Unidades</h3></div>
    </div>
    <div class="inv-metric-card">
        <div class="inv-metric-icon" style="background:rgba(16,185,129,0.1);"><i class="ph ph-check-circle" style="color:#10b981;"></i></div>
        <div class="text-end"><p class="inv-metric-value" id="metricDisponible" style="color:#10b981;">0</p><h3 class="inv-metric-title">Disponibles</h3></div>
    </div>
    <div class="inv-metric-card">
        <div class="inv-metric-icon" style="background:rgba(59,130,246,0.1);"><i class="ph ph-arrow-circle-up" style="color:#3b82f6;"></i></div>
        <div class="text-end"><p class="inv-metric-value" id="metricInstalado" style="color:#3b82f6;">0</p><h3 class="inv-metric-title">Instalados</h3></div>
    </div>
    <div class="inv-metric-card">
        <div class="inv-metric-icon" style="background:rgba(245,158,11,0.1);"><i class="ph ph-warning" style="color:#f59e0b;"></i></div>
        <div class="text-end"><p class="inv-metric-value" id="metricLowStock" style="color:#f59e0b;">0</p><h3 class="inv-metric-title">Por Agotarse</h3></div>
    </div>
</div>

<!-- Card contenedor unificado -->
<div class="inv-content-card">

<!-- Unified Toolbar: Tabs + Filters -->
<div class="inv-toolbar">
    <!-- Tabs (izquierda) -->
    <div class="inv-toolbar-tabs">
        <button class="inv-tab active" data-tab="productos"><i class="ph ph-package"></i> Productos</button>
        <button class="inv-tab" data-tab="stock"><i class="ph ph-chart-bar"></i> Control de Stock</button>
        <button class="inv-tab" data-tab="etiquetas"><i class="ph ph-tag"></i> Etiquetas</button>
        <button class="inv-tab" data-tab="escaner"><i class="ph ph-barcode"></i> Escáner</button>
        <button class="inv-tab" data-tab="papelera"><i class="ph ph-trash"></i> Papelera</button>
    </div>
    <!-- History button -->
    <button class="inv-tab" style="margin-left:8px; background:rgba(99,102,241,0.1); color:#6366f1; border:1px solid rgba(99,102,241,0.2);" onclick="openHistoryModal()" title="Historial de inventario">
        <i class="ph ph-clock-counter-clockwise"></i> Historial
    </button>
    <!-- Filtros + Sheets (derecha) -->
    <div class="inv-toolbar-right">
        <!-- New Product Button (moved from FAB) -->
        <button id="btnNewProduct" style="display:flex; align-items:center; gap:6px; background:var(--primary-color); color:#fff; border:none; padding:6px 14px; border-radius:8px; font-size:0.85rem; font-weight:500; cursor:pointer; transition:all 0.2s;" title="Crear un nuevo producto">
            <i class="ph ph-plus-circle" style="font-size:1.1rem;"></i> Nuevo Producto
        </button>
        
        <div class="inv-filter-search" id="toolbarSearch">
            <i class="ph ph-magnifying-glass"></i>
            <input type="text" class="form-control" id="searchProducts" placeholder="Buscar producto..." autocomplete="off">
        </div>
        <!-- Hidden selects (mantienen IDs para JS existente) -->
        <select class="form-select inv-filter-select" id="filterProductCategory" style="display:none;">
            <option value="">Todas las categorías</option>
        </select>
        <select class="form-select inv-filter-select" id="filterProductStatus" style="display:none;">
            <option value="">Todos los estados</option>
            <option value="con_stock">Con Stock Disponible</option>
            <option value="sin_stock">Sin Stock (Agotado)</option>
            <option value="stock_critico">Stock Crítico</option>
            <option value="con_malogrados">Con Malogrados</option>
            <option value="con_observacion">Con Observación</option>
        </select>
        
        <!-- Stock Filters (visible only in stock tab) -->
        <div id="stockFiltersToolbar" style="display:none; gap:8px; align-items:center;">
            <select class="form-select inv-filter-select" id="filterProduct" style="min-width:160px;"><option value="">Todos los productos</option></select>
            <select class="form-select inv-filter-select" id="filterStatus" style="min-width:150px;">
                <option value="">Todos los estados</option>
                <option value="disponible">Disponible</option>
                <option value="instalado">Instalado</option>
                <option value="malogrado">Malogrado</option>
                <option value="reparado">Reparado</option>
                <option value="en_transito">En Tránsito</option>
                <option value="observacion">Observación</option>
            </select>
        </div>

        <!-- Filter Button -->
        <div class="inv-filter-popover-wrap" id="mainFilterPopoverWrap">
            <button type="button" class="inv-filter-btn" id="btnFilterToggle" title="Filtros">
                <i class="ph ph-funnel"></i>
                <span class="inv-filter-btn-label">Filtros</span>
                <span class="inv-filter-badge" id="filterBadge" style="display:none;">0</span>
            </button>
            <!-- Popover Panel -->
            <div class="inv-filter-popover" id="filterPopover">
                <div class="inv-fp-header">
                    <span><i class="ph ph-funnel" style="margin-right:6px;"></i>Filtros</span>
                    <button type="button" class="inv-fp-close" id="btnFilterClose"><i class="ph ph-x"></i></button>
                </div>
                <div class="inv-fp-body">
                    <!-- Categoría -->
                    <div class="inv-fp-section">
                        <label class="inv-fp-label">Categoría</label>
                        <div class="inv-fp-chips" id="fpCategoryChips">
                            <button type="button" class="inv-fp-chip active" data-cat="">Todas</button>
                        </div>
                    </div>
                    <!-- Estado (Product tab) -->
                    <div class="inv-fp-section" id="fpStatusSection_products">
                        <label class="inv-fp-label">Estado</label>
                        <div class="inv-fp-chips" id="fpStatusChips">
                            <button type="button" class="inv-fp-chip active" data-stat="">Todos</button>
                            <button type="button" class="inv-fp-chip" data-stat="con_stock"><i class="ph ph-check-circle"></i> Con Stock</button>
                            <button type="button" class="inv-fp-chip" data-stat="sin_stock"><i class="ph ph-x-circle"></i> Agotado</button>
                            <button type="button" class="inv-fp-chip" data-stat="stock_critico"><i class="ph ph-warning"></i> Crítico</button>
                            <button type="button" class="inv-fp-chip" data-stat="con_malogrados"><i class="ph ph-warning-diamond"></i> Malogrados</button>
                            <button type="button" class="inv-fp-chip" data-stat="con_observacion"><i class="ph ph-eye"></i> Observados</button>
                        </div>
                    </div>
                    <!-- Estado (Stock tab) -->
                    <div class="inv-fp-section" id="fpStatusSection_stock" style="display:none;">
                        <label class="inv-fp-label">Estado del SKU</label>
                        <div class="inv-fp-chips" id="fpStatusChipsStock">
                            <button type="button" class="inv-fp-chip active" data-stat="">Todos</button>
                            <button type="button" class="inv-fp-chip" data-stat="disponible"><i class="ph ph-check-circle"></i> Disponible</button>
                            <button type="button" class="inv-fp-chip" data-stat="instalado"><i class="ph ph-arrow-circle-up"></i> Instalado</button>
                            <button type="button" class="inv-fp-chip" data-stat="malogrado"><i class="ph ph-warning-diamond"></i> Malogrado</button>
                            <button type="button" class="inv-fp-chip" data-stat="reparado"><i class="ph ph-wrench"></i> Reparado</button>
                            <button type="button" class="inv-fp-chip" data-stat="en_transito"><i class="ph ph-truck"></i> En Tránsito</button>
                            <button type="button" class="inv-fp-chip" data-stat="observacion"><i class="ph ph-eye"></i> Observación</button>
                        </div>
                    </div>

                </div>
                <div class="inv-fp-footer">
                    <button type="button" class="inv-fp-clear" id="btnFilterClear"><i class="ph ph-arrow-counter-clockwise"></i> Limpiar</button>
                    <button type="button" class="inv-fp-apply" id="btnFilterApply"><i class="ph ph-check"></i> Aplicar Filtros</button>
                </div>
            </div>
        </div>
        <button class="btn-sheets-sync" onclick="SheetsSync.openModal()" title="Sincronizar con Google Sheets" id="btnSheetsSync">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" style="flex-shrink:0;">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M14 2v6h6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <line x1="8" y1="13" x2="16" y2="13" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <line x1="8" y1="17" x2="16" y2="17" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <polyline points="10 9 9 9 8 9" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            Google Sheets
            <span class="sheets-status-dot" id="sheetsDot"></span>
        </button>
    </div>
</div>

<!-- Tab: Productos -->
<div class="inv-tab-pane active" id="tab-productos">
    <!-- Active filters bar -->
    <div id="cfActiveBar" style="display:none; gap:8px; flex-wrap:wrap; align-items:center; padding:8px 0; animation:fadeIn 0.2s ease;">
        <span style="font-size:0.8rem; color:var(--text-muted); font-weight:600;"><i class="ph ph-funnel"></i> Filtros activos:</span>
        <div id="cfActiveTags" style="display:flex; gap:6px; flex-wrap:wrap;"></div>
        <button onclick="ColFilter.clearAll()" style="margin-left:auto; background:transparent; border:1px solid var(--border-color); border-radius:8px; padding:4px 10px; font-size:0.78rem; color:var(--text-muted); cursor:pointer; display:flex; align-items:center; gap:4px;"><i class="ph ph-x"></i> Limpiar todo</button>
    </div>

    <!-- Product bulk actions bar -->
    <div style="display:flex; justify-content:space-between; align-items:center; padding:12px 16px; background:var(--surface-color); border-bottom: 1px solid var(--border-color);">
        <div style="display:flex; gap:10px; align-items:center;">
            <div id="prodActiveActions" style="display:none; gap:6px;">
                <button class="btn btn-secondary btn-sm" onclick="exportSelectedProductsToExcel()"><i class="ph ph-file-csv"></i> Descargar Excel</button>
                <button class="btn btn-secondary btn-sm" onclick="clearProductSelection()"><i class="ph ph-x"></i> Cancelar</button>
                <button class="btn btn-danger btn-sm" onclick="bulkDeleteProducts()"><i class="ph ph-trash"></i> Eliminar</button>
            </div>
        </div>
        <div style="font-size:0.8rem; color:var(--text-muted);">
            <span id="prodSelectedCount">0</span> seleccionados
        </div>
    </div>

    <div class="table-responsive" id="productsTableWrap" style="display:none; height: calc(100vh - 280px); overflow: auto; border-bottom: 1px solid var(--border-color);">
        <table class="inv-table">
            <thead>
                <tr>
                    <th style="width:40px;text-align:center;"><input type="checkbox" class="form-check-input" id="prodCheckAll" onchange="toggleAllProducts(this)"></th>
                    <th class="cf-th" data-col="nombre">
                        <span>Producto</span>
                        <button class="cf-btn" onclick="ColFilter.open('nombre', this)" title="Filtrar"><i class="ph ph-funnel-simple"></i></button>
                    </th>
                    <th class="cf-th" data-col="categoria">
                        <span>Categoría</span>
                        <button class="cf-btn" onclick="ColFilter.open('categoria', this)" title="Filtrar"><i class="ph ph-funnel-simple"></i></button>
                    </th>
                    <th class="cf-th" data-col="total">
                        <span>Total</span>
                        <button class="cf-btn" onclick="ColFilter.open('total', this)" title="Filtrar"><i class="ph ph-funnel-simple"></i></button>
                    </th>
                    <th class="cf-th" data-col="disponibles">
                        <span>Disponibles</span>
                        <button class="cf-btn" onclick="ColFilter.open('disponibles', this)" title="Filtrar"><i class="ph ph-funnel-simple"></i></button>
                    </th>
                    <th class="cf-th" data-col="instalados">
                        <span>Instalados</span>
                        <button class="cf-btn" onclick="ColFilter.open('instalados', this)" title="Filtrar"><i class="ph ph-funnel-simple"></i></button>
                    </th>
                    <th class="cf-th" data-col="malogrados">
                        <span>Malogrados</span>
                        <button class="cf-btn" onclick="ColFilter.open('malogrados', this)" title="Filtrar"><i class="ph ph-funnel-simple"></i></button>
                    </th>
                    <th class="cf-th" data-col="observados">
                        <span>Observados</span>
                        <button class="cf-btn" onclick="ColFilter.open('observados', this)" title="Filtrar"><i class="ph ph-funnel-simple"></i></button>
                    </th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="productsGrid"></tbody>
        </table>
    </div>
    <div id="productsPagination" style="display:none; justify-content:space-between; align-items:center; padding:12px 16px; background:var(--surface-color); border-bottom-left-radius:16px; border-bottom-right-radius:16px;">
        <div style="display:flex; align-items:center; gap:8px;">
            <span style="font-size:0.85rem; color:var(--text-muted);">Mostrar</span>
            <select id="prodPerPage" class="form-select" style="padding:4px 28px 4px 8px; width:auto; font-size:0.85rem; height:32px;">
                <option value="10">10</option>
                <option value="25" selected>25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
            <span style="font-size:0.85rem; color:var(--text-muted);">registros</span>
        </div>
        <div style="font-size:0.85rem; color:var(--text-muted);" id="prodPageInfo">Mostrando 0 - 0 de 0</div>
        <div style="display:flex; gap:4px;" id="prodPaginationControls">
            <button class="btn btn-secondary btn-sm" id="btnProdPrev" style="padding:4px 8px;"><i class="ph ph-caret-left"></i></button>
            <button class="btn btn-secondary btn-sm" id="btnProdNext" style="padding:4px 8px;"><i class="ph ph-caret-right"></i></button>
        </div>
    </div>
    <div id="productsEmpty" class="empty-state" style="display:none;"><i class="ph ph-package" style="font-size:3rem;display:block;margin-bottom:12px;"></i>No hay productos registrados.</div>
</div>

</div><!-- /.inv-content-card -->

<!-- Tab: Control de Stock -->
<div class="inv-tab-pane" id="tab-stock">
    <input type="hidden" id="searchSku" value="">
    
    <div style="display:flex; justify-content:space-between; align-items:center; padding:12px 16px; background:var(--surface-color); border-bottom: 1px solid var(--border-color);">
        <div style="display:flex; gap:10px; align-items:center;">
            <div id="skuActiveActions" style="display:none; gap:6px;">
                <button class="btn btn-secondary btn-sm" onclick="exportSkusToExcel()" title="Descargar en Excel"><i class="ph ph-file-xls" style="color:#10b981;"></i> Excel</button>
                <button class="btn btn-secondary btn-sm" onclick="bulkChangeSkuStatus()"><i class="ph ph-tag"></i> Cambiar Estado</button>
                <button class="btn btn-danger btn-sm" onclick="bulkDeleteSkus()"><i class="ph ph-trash"></i> Eliminar</button>
            </div>
        </div>
        <div style="font-size:0.8rem; color:var(--text-muted);">
            <span id="skuSelectedCount">0</span> seleccionados
        </div>
    </div>

    <!-- Active filters bar for stock -->
    <div id="skuActiveBar" style="display:none; gap:8px; flex-wrap:wrap; align-items:center; padding:8px 16px; background:var(--surface-color); border-bottom: 1px solid var(--border-color); animation:fadeIn 0.2s ease;">
        <span style="font-size:0.8rem; color:var(--text-muted); font-weight:600;"><i class="ph ph-funnel"></i> Filtros activos:</span>
        <div id="skuActiveTags" style="display:flex; gap:6px; flex-wrap:wrap;"></div>
        <button onclick="SkuColFilter.clearAll()" style="margin-left:auto; background:transparent; border:1px solid var(--border-color); border-radius:8px; padding:4px 10px; font-size:0.78rem; color:var(--text-muted); cursor:pointer; display:flex; align-items:center; gap:4px;"><i class="ph ph-x"></i> Limpiar todo</button>
    </div>

    <div class="table-responsive" style="height: calc(100vh - 290px); overflow: auto; border-bottom: 1px solid var(--border-color);">
        <table id="skuTable" class="inv-table">
            <thead>
                <tr>
                    <th class="sticky-col sticky-col-0 sticky-th" style="width:40px;text-align:center;vertical-align:middle;" data-colname="#">
                        <input type="checkbox" id="skuCheckAll" class="form-check-input" onchange="toggleAllSkus(this)">
                    </th>
                    <th class="draggable-th sortable-th sticky-col sticky-col-1 sticky-th" onclick="toggleSort('sku_code')" draggable="true" data-colidx="1" data-colname="SKU">
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span style="white-space:nowrap;">SKU <span class="sort-icon"><i class="ph ph-caret-up-down" style="opacity:0.3;"></i></span></span>
                            <button class="cf-btn" onclick="event.stopPropagation(); SkuColFilter.open('SKU', this)" title="Filtrar"><i class="ph ph-funnel-simple"></i></button>
                        </div>
                    </th>
                    <th class="draggable-th sortable-th sticky-col sticky-col-2 sticky-th" onclick="toggleSort('product_name')" draggable="true" data-colidx="2" data-colname="Producto">
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span style="white-space:nowrap;">Producto <span class="sort-icon"><i class="ph ph-caret-up-down" style="opacity:0.3;"></i></span></span>
                            <button class="cf-btn" onclick="event.stopPropagation(); SkuColFilter.open('Producto', this)" title="Filtrar"><i class="ph ph-funnel-simple"></i></button>
                        </div>
                    </th>
                    <th class="draggable-th sortable-th" onclick="toggleSort('category_name')" draggable="true" data-colidx="3" data-colname="Categoría">
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span style="white-space:nowrap;">Categoría <span class="sort-icon"><i class="ph ph-caret-up-down" style="opacity:0.3;"></i></span></span>
                            <button class="cf-btn" onclick="event.stopPropagation(); SkuColFilter.open('Categoría', this)" title="Filtrar"><i class="ph ph-funnel-simple"></i></button>
                        </div>
                    </th>
                    <th class="draggable-th sortable-th" onclick="toggleSort('status')" draggable="true" data-colidx="4" data-colname="Estado">
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span style="white-space:nowrap;">Estado <span class="sort-icon"><i class="ph ph-caret-up-down" style="opacity:0.3;"></i></span></span>
                            <button class="cf-btn" onclick="event.stopPropagation(); SkuColFilter.open('Estado', this)" title="Filtrar"><i class="ph ph-funnel-simple"></i></button>
                        </div>
                    </th>
                    <th class="draggable-th" draggable="true" data-colidx="5" data-colname="Historia">
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span style="white-space:nowrap;">Historia</span>
                            <button class="cf-btn" onclick="event.stopPropagation(); SkuColFilter.open('Historia', this)" title="Filtrar"><i class="ph ph-funnel-simple"></i></button>
                        </div>
                    </th>
                    <th class="draggable-th" draggable="true" data-colidx="6" data-colname="Últ. Actividad">
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span style="white-space:nowrap;">Últ. Actividad</span>
                            <button class="cf-btn" onclick="event.stopPropagation(); SkuColFilter.open('Últ. Actividad', this)" title="Filtrar"><i class="ph ph-funnel-simple"></i></button>
                        </div>
                    </th>
                    <th class="draggable-th" draggable="true" data-colidx="7" data-colname="Instalado a">
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span style="white-space:nowrap;">Instalado a</span>
                            <button class="cf-btn" onclick="event.stopPropagation(); SkuColFilter.open('Instalado a', this)" title="Filtrar"><i class="ph ph-funnel-simple"></i></button>
                        </div>
                    </th>
                    <th class="draggable-th" draggable="true" data-colidx="8" data-colname="Asignado">
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span style="white-space:nowrap;">Asignado</span>
                            <button class="cf-btn" onclick="event.stopPropagation(); SkuColFilter.open('Asignado', this)" title="Filtrar"><i class="ph ph-funnel-simple"></i></button>
                        </div>
                    </th>
                    <th class="draggable-th" draggable="true" data-colidx="9" data-colname="Acción">
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span style="white-space:nowrap;">Acción</span>
                        </div>
                    </th>
                    <th class="draggable-th sortable-th" onclick="toggleSort('sku_created_at')" draggable="true" data-colidx="10" data-colname="Fecha Registro">
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span style="white-space:nowrap;">Fecha Registro <span class="sort-icon"><i class="ph ph-caret-up-down" style="opacity:0.3;"></i></span></span>
                            <button class="cf-btn" onclick="event.stopPropagation(); SkuColFilter.open('Fecha Registro', this)" title="Filtrar"><i class="ph ph-funnel-simple"></i></button>
                        </div>
                    </th>
                </tr>
            </thead>
            <tbody id="skuTableBody"></tbody>
        </table>
    </div>
    <div id="skuPagination" style="display:flex; justify-content:space-between; align-items:center; padding:12px 16px; background:var(--surface-color); border-bottom-left-radius:16px; border-bottom-right-radius:16px;">
        <div style="display:flex; align-items:center; gap:8px;">
            <span style="font-size:0.85rem; color:var(--text-muted);">Mostrar</span>
            <select id="skuPerPage" class="form-select" style="padding:4px 28px 4px 8px; width:auto; font-size:0.85rem; height:32px;">
                <option value="10">10</option>
                <option value="25" selected>25</option>
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="500">500</option>
            </select>
            <span style="font-size:0.85rem; color:var(--text-muted);">registros</span>
        </div>
        <div style="font-size:0.85rem; color:var(--text-muted);" id="skuPageInfo">Mostrando 0 - 0 de 0</div>
        <div style="display:flex; gap:4px;" id="skuPaginationControls">
            <button class="btn btn-secondary btn-sm" id="btnSkuPrev" style="padding:4px 8px;"><i class="ph ph-caret-left"></i></button>
            <button class="btn btn-secondary btn-sm" id="btnSkuNext" style="padding:4px 8px;"><i class="ph ph-caret-right"></i></button>
        </div>
    </div>
    <div id="skuEmpty" class="empty-state" style="display:none;">No hay SKUs registrados.</div>
</div>

<!-- Tab: Etiquetas (Impresora Térmica 2 Columnas) -->
<div class="inv-tab-pane" id="tab-etiquetas" style="padding:20px;">
    <!-- Header -->
    <div class="scanner-header" style="margin-top:0; margin-bottom:20px;">
        <div class="scanner-header-left">
            <div class="scanner-icon-box" style="background:rgba(99,102,241,0.1);"><i class="ph ph-tag" style="color:#6366f1;"></i></div>
            <div>
                <h2 style="margin:0;font-size:1.15rem;font-weight:700;">Etiquetas Adhesivas</h2>
                <p style="margin:0;font-size:0.82rem;color:rgba(255,255,255,0.7);">Genera e imprime etiquetas en tu impresora térmica de 2 columnas</p>
            </div>
        </div>
    </div>

    <!-- Config Card -->
    <div style="background:var(--surface-color); border:1px solid var(--border-color); border-radius:14px; padding:20px; margin-bottom:20px;">
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
            <!-- Producto -->
            <div style="display:flex; gap:12px; width:100%;">
                <div style="flex:1;">
                    <label class="form-label" style="font-size:0.85rem; font-weight:700;"><i class="ph ph-package" style="margin-right:4px;"></i> Producto</label>
                    <select class="form-select" id="labelProduct" style="width:100%;"><option value="">Seleccionar producto...</option></select>
                </div>
                <div style="flex:1;">
                    <label class="form-label" style="font-size:0.85rem; font-weight:700;"><i class="ph ph-funnel" style="margin-right:4px;"></i> Filtro de Impresión</label>
                    <select class="form-select" id="labelPrintStatus" style="width:100%;">
                        <option value="">Todos los SKUs</option>
                        <option value="0">Solo NO Impresos</option>
                        <option value="1">Solo ya Impresos</option>
                    </select>
                </div>
            </div>
            <!-- Tipo de código -->
            <div>
                <label class="form-label" style="font-size:0.85rem; font-weight:700;"><i class="ph ph-qr-code" style="margin-right:4px;"></i> Tipo de Código</label>
                <select class="form-select" id="labelType">
                    <option value="barcode">Código de Barras</option>
                    <option value="qr">Código QR</option>
                </select>
            </div>
        </div>

        <!-- Dimensiones -->
        <div style="background:var(--bg-color); border:1px solid var(--border-color); border-radius:10px; padding:14px; margin-bottom:16px;">
            <label class="form-label" style="font-size:0.82rem; font-weight:700; margin-bottom:10px; display:flex; align-items:center; gap:6px;">
                <i class="ph ph-ruler" style="color:#8b5cf6;"></i> Dimensiones de Etiqueta
            </label>
            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px;">
                <div>
                    <label class="form-label" style="font-size:0.75rem; color:var(--text-muted);">Ancho (mm)</label>
                    <input type="number" class="form-control" id="labelWidth" value="50" min="20" max="100" style="font-size:0.9rem;">
                </div>
                <div>
                    <label class="form-label" style="font-size:0.75rem; color:var(--text-muted);">Alto (mm)</label>
                    <input type="number" class="form-control" id="labelHeight" value="30" min="15" max="80" style="font-size:0.9rem;">
                </div>
                <div>
                    <label style="font-size:0.8rem; font-weight:600; margin-bottom:4px; display:block;">Columnas</label>
                    <input type="number" id="labelCols" class="form-control" value="2" min="1" max="5">
                </div>
                <div>
                    <label style="font-size:0.8rem; font-weight:600; margin-bottom:4px; display:block;">Copias c/u</label>
                    <input type="number" id="labelCopies" class="form-control" value="1" min="1" max="100" title="Número de copias por cada código">
                </div>
            </div>
        </div>

        <!-- Contenido a mostrar -->
        <div style="display:flex; gap:16px; align-items:center; flex-wrap:wrap; margin-bottom:16px;">
            <span style="font-size:0.82rem; font-weight:700; color:var(--text-color);"><i class="ph ph-text-aa" style="margin-right:4px;"></i> Mostrar en etiqueta:</span>
            <label style="display:flex; align-items:center; gap:6px; font-size:0.85rem; cursor:pointer;">
                <input type="checkbox" class="form-check-input" id="labelShowLogo"> Logo de empresa
            </label>
            <label style="display:flex; align-items:center; gap:6px; font-size:0.85rem; cursor:pointer;">
                <input type="checkbox" class="form-check-input" id="labelShowCompanyName"> Nombre de empresa
            </label>
            <label style="display:flex; align-items:center; gap:6px; font-size:0.85rem; cursor:pointer;">
                <input type="checkbox" class="form-check-input" id="labelShowName" checked> Nombre del producto
            </label>
            <label style="display:flex; align-items:center; gap:6px; font-size:0.85rem; cursor:pointer;">
                <input type="checkbox" class="form-check-input" id="labelShowSku" checked> Código SKU
            </label>
            <label style="display:flex; align-items:center; gap:6px; font-size:0.85rem; cursor:pointer;">
                <input type="checkbox" class="form-check-input" id="labelShowDate"> Fecha de registro
            </label>
        </div>

        <!-- Botones -->
        <div style="display:flex; gap:10px;">
            <button class="btn btn-primary" id="btnGenLabels" style="flex:1; height:44px; font-weight:700;">
                <i class="ph ph-barcode" style="font-size:1.1rem;"></i> Generar Etiquetas
            </button>
            <button class="btn btn-secondary" id="btnPrint" style="display:none; height:44px; font-weight:700;" onclick="window.print()">
                <i class="ph ph-printer" style="font-size:1.1rem;"></i> Imprimir
            </button>
            <button class="btn" id="btnMarkPrinted" style="display:none; height:44px; font-weight:700; background-color:#10b981; color:white; border:none;" onclick="markGeneratedAsPrinted()">
                <i class="ph ph-check-circle" style="font-size:1.1rem;"></i> Marcar como impresas
            </button>
        </div>
    </div>

    <!-- Preview -->
    <div id="labelPreview" class="label-preview-container"></div>
</div>

<!-- Tab: Escáner -->
<div class="inv-tab-pane" id="tab-escaner" style="padding: 20px;">
    <!-- Scanner Header -->
    <div class="scanner-header" style="margin-top: 0;">
        <div class="scanner-header-left">
            <div class="scanner-icon-box"><i class="ph ph-barcode"></i></div>
            <div>
                <h2 style="margin:0;font-size:1.15rem;font-weight:700;">Escáner de Inventario</h2>
                <p style="margin:0;font-size:0.82rem;color:rgba(255,255,255,0.7);">Escanea o escribe un código SKU para buscar</p>
            </div>
        </div>
        <div class="scanner-input-wrap">
            <i class="ph ph-magnifying-glass"></i>
            <input type="text" id="scannerInput" class="form-control" placeholder="Buscar por SKU o nombre..." autocomplete="off" autofocus>
            <button type="button" class="btn-scan-camera" id="btnScanCamera" title="Escanear con cámara"><i class="ph ph-camera"></i></button>
        </div>
    </div>

    <!-- Scanner Result -->
    <div id="scannerResult" class="scanner-result" style="display:none;"></div>
</div>

<!-- Tab: Papelera -->
<div class="inv-tab-pane" id="tab-papelera" style="padding: 20px;">
    <div class="scanner-header" style="margin-top: 0;">
        <div class="scanner-header-left">
            <div class="scanner-icon-box" style="background:rgba(239,68,68,0.1);"><i class="ph ph-trash" style="color:#ef4444;"></i></div>
            <div>
                <h2 style="margin:0;font-size:1.15rem;font-weight:700;">Papelera de Reciclaje</h2>
                <p style="margin:0;font-size:0.82rem;color:rgba(255,255,255,0.7);">Restaura o elimina definitivamente productos y SKUs</p>
            </div>
        </div>
        <button class="btn btn-sm" style="background:rgba(239,68,68,0.12); color:#ef4444; border:1px solid rgba(239,68,68,0.25); font-weight:600; padding:8px 16px; border-radius:8px; cursor:pointer; display:flex; align-items:center; gap:6px;" onclick="emptyPapelera()" title="Vaciar papelera completamente">
            <i class="ph ph-trash" style="font-size:1rem;"></i> Vaciar Papelera
        </button>
    </div>
    
    <div class="table-responsive" style="height: calc(100vh - 290px); overflow: auto; border-bottom: 1px solid var(--border-color); margin-top:20px;">
        <table id="papeleraTable" class="inv-table">
            <thead>
                <tr>
                    <th style="width:110px;">Tipo</th>
                    <th>Código/SKU</th>
                    <th>Producto</th>
                    <th>Categoría</th>
                    <th style="width:100px;">Cantidad</th>
                    <th class="text-end" style="width:180px;">Acciones</th>
                </tr>
            </thead>
            <tbody id="papeleraTableBody">
                <tr><td colspan="6" class="text-center" style="padding:30px;color:rgba(255,255,255,0.5);">Cargando papelera...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- FAB Removed (moved to toolbar) -->

<!-- Modal: Selector de Tipo de Producto -->
<div class="modal-overlay" id="productTypeModal">
    <div class="modal-content" style="max-width:700px;">
        <div class="modal-header">
            <h3><i class="ph ph-package"></i> ¿Qué tipo de producto deseas crear?</h3>
            <button class="close-modal" onclick="document.getElementById('productTypeModal').classList.remove('active')">&times;</button>
        </div>
        <div class="modal-body" style="padding:24px;">
            <div class="product-type-grid">
                <!-- Normal -->
                <div class="product-type-card" onclick="selectProductType('normal')">
                    <div class="product-type-icon" style="background:linear-gradient(135deg, #3b82f6, #2563eb);">
                        <i class="ph ph-barcode"></i>
                    </div>
                    <h4>Producto Normal</h4>
                    <p>Productos con SKUs individuales y trazabilidad unitaria. Ideal para equipos, herramientas y dispositivos.</p>
                </div>
                <!-- Granel -->
                <div class="product-type-card" onclick="selectProductType('granel')">
                    <div class="product-type-icon" style="background:linear-gradient(135deg, #f59e0b, #d97706);">
                        <i class="ph ph-scales"></i>
                    </div>
                    <h4>Producto a Granel</h4>
                    <p>Materiales medidos por cantidad, metros, kilos, litros, etc. Sin SKUs individuales.</p>
                </div>
                <!-- Agrupado -->
                <div class="product-type-card" onclick="selectProductType('agrupado')">
                    <div class="product-type-icon" style="background:linear-gradient(135deg, #8b5cf6, #7c3aed);">
                        <i class="ph ph-stack"></i>
                    </div>
                    <h4>Producto Agrupado</h4>
                    <p>Agrupa varios productos en un solo kit o combo. Define variantes con marca, talla y cantidades.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: New Product -->
<div class="modal-overlay" id="newProductModal">
    <div class="modal-content" style="max-width:1300px; width:98%;">
        <div class="modal-header">
            <h3><i class="ph ph-package"></i> Nuevo Producto <span id="productTypeBadge" class="product-type-header-badge"></span></h3>
            <button class="close-modal" onclick="closeProductModal()">&times;</button>
        </div>
        <div class="modal-body" style="padding:0;">
            <!-- ═══ Tab: Datos ═══ -->
            <div class="np-pane active" id="nptab-datos" style="padding:20px;">

                <!-- Grid layout (1, 2, or 3 columns depending on type) -->
                <div id="newProductGrid" class="np-dynamic-grid" style="display:grid; gap:24px; align-items:start;">

                    <!-- ── Columna Izquierda ── -->
                    <div>
                        <!-- Nombre del Producto -->
                        <div class="inv-form-field" style="margin-bottom:14px;">
                            <label class="form-label">Nombre del Producto</label>
                            <input type="text" class="form-control" id="prodName" required placeholder="Ej: Router TP-Link">
                            <div style="margin-top:6px; font-size:0.85rem;">
                                <a href="javascript:void(0)" onclick="document.getElementById('aliasWrap').style.display='block'; this.style.display='none';" style="color:var(--primary-color); text-decoration:none;"><i class="ph ph-plus"></i> Añadir nombre alternativo</a>
                                <div id="aliasWrap" style="display:none; margin-top:6px;">
                                    <label class="form-label" style="font-size:0.8rem; color:var(--text-muted);">Nombres Alternativos — Presiona Enter para agregar</label>
                                    <div class="inv-tags-input" style="border:1px solid var(--border-color); border-radius:8px; padding:6px; background:var(--surface-color); display:flex; flex-wrap:wrap; gap:6px;">
                                        <div id="aliasTagsContainer" style="display:flex; gap:6px; flex-wrap:wrap;"></div>
                                        <input type="text" id="prodAliasInput" placeholder="Ej: Router negro" style="border:none; background:transparent; outline:none; flex:1; min-width:120px; font-size:0.9rem; color:var(--text-color);">
                                        <input type="hidden" id="prodAliases" value="">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Categoría -->
                        <div class="inv-form-field" style="margin-bottom:14px;">
                            <label class="form-label">Categoría</label>
                            <div style="display:flex;gap:6px;">
                                <select class="form-select" id="prodCategory" style="flex:1;"><option value="">Sin categoría</option></select>
                                <button type="button" class="btn btn-secondary" onclick="promptNewCategory()" title="Nueva categoría" style="padding:10px;flex-shrink:0;"><i class="ph ph-plus"></i></button>
                                <button type="button" class="btn btn-secondary" onclick="openManageCategories()" title="Gestionar categorías" style="padding:10px;flex-shrink:0;"><i class="ph ph-gear"></i></button>
                            </div>
                        </div>

                        <!-- Stock Mínimo + Crítico -->
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:14px;">
                            <div class="inv-form-field">
                                <label class="form-label">Stock Mínimo</label>
                                <input type="number" class="form-control" id="prodStockMin" min="0" value="10" placeholder="10">
                            </div>
                            <div class="inv-form-field">
                                <label class="form-label">Stock Crítico</label>
                                <input type="number" class="form-control" id="prodStockCrit" min="0" value="3" placeholder="3">
                            </div>
                        </div>

                        <!-- Foto Principal -->
                        <div style="margin-bottom:14px;">
                            <label class="form-label" style="font-weight:700; display:flex; align-items:center; gap:6px; margin-bottom:10px;">
                                <i class="ph ph-images" style="color:#8b5cf6; font-size:1.1rem;"></i> Foto Principal
                            </label>
                            <div style="display:flex; gap:8px; margin-bottom:12px;">
                                <button type="button" class="btn btn-secondary" onclick="document.getElementById('prodMultiPhotoInput').click()" style="flex:1;"><i class="ph ph-upload-simple"></i> Subir</button>
                                <button type="button" class="btn btn-secondary" onclick="captureProductPhoto('create')" style="flex:1;"><i class="ph ph-camera"></i> Cámara</button>
                            </div>
                            <div class="prod-photo-dropzone" id="prodPhotoDropzone" style="padding:20px;"
                                 onclick="document.getElementById('prodMultiPhotoInput').click()"
                                 ondrop="handleProductPhotoDrop(event, 'create')"
                                 ondragover="event.preventDefault(); this.classList.add('dragover')"
                                 ondragleave="this.classList.remove('dragover')">
                                <i class="ph ph-cloud-arrow-up" style="font-size:2rem; color:var(--text-muted); opacity:0.4;"></i>
                                <p style="margin:4px 0 0; font-size:0.8rem; color:var(--text-muted);">Arrastra o haz clic</p>
                            </div>
                            <input type="file" id="prodMultiPhotoInput" class="no-dropzone" multiple accept="image/*" style="position:absolute; left:-9999px; width:1px; height:1px;" onchange="handleProductPhotoSelect(event, 'create')">
                            <input type="file" id="prodPhotoInput" class="no-dropzone" accept="image/*" style="position:absolute; left:-9999px; width:1px; height:1px;" onchange="handleProductPhotoSelect(event, 'create')">
                            <div id="prodPhotoGallery" class="prod-photo-gallery" style="margin-top:12px;"></div>
                        </div>

                        <!-- Bulk (hidden checkbox, auto-set by product type) -->
                        <input type="hidden" id="prodIsBulk" value="0">
                        <div id="granelFieldsWrap" data-show-for="granel" style="display:none;">
                            <div class="inv-form-field" style="margin-bottom:14px;">
                                <label class="form-label">Cantidad Inicial (Stock)</label>
                                <input type="number" class="form-control" id="prodGranelQty" min="1" value="1" placeholder="Ej: 100">
                            </div>
                            <div style="background:var(--bg-color); padding:12px; border-radius:10px; margin-bottom:16px; border:1px solid var(--border-color);">
                                <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
                                    <i class="ph ph-check-circle" style="color:#10b981; font-size:1.2rem;"></i>
                                    <span style="font-weight:700;">Es material a granel (No usa SKUs individuales)</span>
                                </div>
                                <small style="color:var(--text-muted);display:block;margin-top:4px;">Selecciona si el producto se maneja por metros, litros, kilos, etc.</small>
                                <div id="prodUnitWrap" style="margin-top:10px;">
                                    <label class="form-label">Unidad</label>
                                    <select class="form-select" id="prodUnitType">
                                        <option value="Unidades">Unidades</option>
                                        <option value="Metros (m)">Metros (m)</option>
                                        <option value="Centímetros (cm)">Centímetros (cm)</option>
                                        <option value="Milímetros (mm)">Milímetros (mm)</option>
                                        <option value="Kilómetros (km)">Kilómetros (km)</option>
                                        <option value="Gramos (g)">Gramos (g)</option>
                                        <option value="Kilogramos (kg)">Kilogramos (kg)</option>
                                        <option value="Litros (L)">Litros (L)</option>
                                    </select>
                                </div>
                                <div id="prodMasterSkuWrap" style="margin-top:10px;">
                                    <label class="form-label">SKU Maestro (Opcional)</label>
                                    <div style="display:flex;gap:6px;">
                                        <input type="text" class="form-control" id="prodMasterSku" placeholder="Ej: CABLE-UTP-01">
                                        <button type="button" class="btn btn-secondary" onclick="document.getElementById('prodMasterSku').value = 'BLK-' + randomCode(6);" title="Generar automático" style="padding:10px;flex-shrink:0;"><i class="ph ph-lightning"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Custom Columns Manager -->
                        <div id="customColsSection" class="inv-custom-cols-wrap" data-show-for="normal">
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                                <label class="form-label" style="margin:0;"><i class="ph ph-columns"></i> Columnas Personalizadas <small style="color:var(--text-muted);font-weight:400;">(por SKU)</small></label>
                                <span style="font-size:0.75rem;color:var(--text-muted);" id="colCountBadge">0 columnas</span>
                            </div>
                            <div style="margin-bottom:10px; display:flex; gap:6px; flex-wrap:wrap; font-size:0.78rem; align-items:center;" id="suggestionsWrap">
                                <span style="color:var(--text-muted);font-size:0.75rem;">Añadir rápido:</span>
                                <span class="inv-col-pill" style="cursor:pointer; background:var(--bg-color); border:1px dashed var(--primary-color); color:var(--primary-color);" onclick="promptNewSuggestion('create')"><i class="ph ph-plus"></i> Nueva</span>
                            </div>
                            <div id="customColsList" style="display:flex;flex-direction:column;gap:6px;margin-bottom:10px;"></div>
                            <div id="addColForm" style="background:var(--bg-color);border:1px dashed var(--border-color);border-radius:10px;padding:10px;">
                                <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                                    <input type="text" class="form-control" id="newColName" placeholder="Nombre de la columna..." style="flex:2;min-width:100px;" onkeydown="if(event.key==='Enter'){event.preventDefault();addCustomColumn();}">
                                    <select class="form-control" id="newColType" style="flex:1;min-width:90px;">
                                        <option value="text">📝 Texto</option>
                                        <option value="number">🔢 Número</option>
                                        <option value="date">📅 Fecha</option>
                                        <option value="select">📋 Lista</option>
                                    </select>
                                    <button type="button" class="btn btn-primary" onclick="addCustomColumn()" style="flex-shrink:0;padding:8px 12px;"><i class="ph ph-plus"></i> Añadir</button>
                                </div>
                            </div>
                        </div>

                    </div><!-- end left col -->

                    <!-- Agrupado: Variant Columns + Dynamic Table -->
                    <div id="agrupadoVariantsSection" data-show-for="agrupado" style="display:none;">
                        <!-- Column Manager -->
                        <div style="margin-bottom:14px;">
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                                <label class="form-label" style="margin:0;"><i class="ph ph-columns" style="color:#8b5cf6;"></i> Columnas de Variantes <span id="varColCountBadge" style="font-size:0.75rem;color:var(--text-muted);font-weight:400;">0 columnas</span></label>
                            </div>
                            <div style="margin-bottom:8px; display:flex; gap:6px; flex-wrap:wrap; font-size:0.78rem; align-items:center;" id="varColSuggestions">
                                <span style="color:var(--text-muted);">Añadir rápido:</span>
                                <button type="button" class="btn-suggestion" onclick="addVariantCol('Marca')">+ Marca</button>
                                <button type="button" class="btn-suggestion" onclick="addVariantCol('Talla')">+ Talla</button>
                                <button type="button" class="btn-suggestion" onclick="addVariantCol('Color')">+ Color</button>
                                <button type="button" class="btn-suggestion" onclick="addVariantCol('Material')">+ Material</button>
                                <button type="button" class="btn-suggestion" onclick="addVariantCol('Peso')">+ Peso</button>
                            </div>
                            <div id="varColsList" style="display:flex;flex-direction:column;gap:6px;margin-bottom:8px;"></div>
                            <div style="display:flex;gap:6px;">
                                <input type="text" class="form-control" id="varColNewName" placeholder="Nombre de columna" style="flex:1;font-size:0.85rem;">
                                <button type="button" class="btn btn-primary" onclick="addVariantCol()" style="flex-shrink:0;padding:8px 12px;"><i class="ph ph-plus"></i> Añadir</button>
                            </div>
                        </div>

                        <!-- Variants Table (dynamic columns) -->
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                            <label class="form-label" style="margin:0;"><i class="ph ph-stack" style="color:#8b5cf6;"></i> Variantes <span id="variantCountBadge" style="font-size:0.75rem;color:var(--text-muted);font-weight:400;">0 variantes</span></label>
                        </div>
                        <div class="table-responsive" id="variantsTableWrap" style="border:1px solid var(--border-color);border-radius:10px;max-height:350px;overflow-y:auto;">
                            <table class="inv-table" style="margin:0;">
                                <thead id="variantsTableHead">
                                    <tr>
                                        <th style="min-width:160px;">Nombre</th>
                                        <th style="min-width:90px;">Cantidad</th>
                                        <th style="width:50px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="variantsTableBody"></tbody>
                            </table>
                        </div>
                        <button type="button" class="btn btn-primary w-100" onclick="addVariantRow()" style="margin-top:10px;height:40px;">
                            <i class="ph ph-plus-circle"></i> Agregar Variante
                        </button>
                    </div>

                    <!-- ── Columna Derecha ── -->
                    <div id="skuRightCol" data-show-for="normal">
                        <!-- SKU Generation Mode -->
                        <div id="skuModeToggleWrap" style="margin-bottom:14px;">
                            <label class="form-label"><i class="ph ph-list-numbers"></i> SKUs del Producto</label>
                            <div class="inv-mode-toggle">
                                <button type="button" class="inv-mode-btn active" id="modeAuto" onclick="setSkuMode('auto')"><i class="ph ph-lightning"></i> Automático</button>
                                <button type="button" class="inv-mode-btn" id="modeManual" onclick="setSkuMode('manual')"><i class="ph ph-pencil-line"></i> Manual</button>
                            </div>
                        </div>
                        <div id="autoModeWrap" style="margin-bottom:14px;">
                            <div class="inv-form-field" style="margin-bottom:10px;">
                                <label class="form-label">Cantidad a generar</label>
                                <input type="number" class="form-control" id="prodQty" min="1" value="1" placeholder="500">
                            </div>
                            <button type="button" class="btn btn-primary w-100" id="btnGenerateSkus" onclick="generateAutoSkus()" style="height:44px;"><i class="ph ph-lightning"></i> Generar SKUs</button>
                        </div>
                        <div id="manualModeWrap" style="display:none;margin-bottom:14px;">
                            <button type="button" class="btn btn-primary w-100" onclick="addManualSkuRow()"><i class="ph ph-plus-circle"></i> Agregar Fila</button>
                        </div>
                        <!-- SKU Preview Table -->
                        <div id="skuPreviewWrap" style="display:none;">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                                <h4 style="font-size:0.95rem;font-weight:700;margin:0;" id="skuPreviewTitle">SKUs: 0</h4>
                                <span id="skuPreviewCount" style="font-size:0.8rem;color:var(--text-muted);"></span>
                            </div>
                            <div class="table-responsive" style="max-height:420px;overflow-y:auto;border:1px solid var(--border-color);border-radius:10px;">
                                <table>
                                    <thead id="skuPreviewHead"><tr><th>#</th><th>SKU Code</th><th>Estado</th><th></th></tr></thead>
                                    <tbody id="skuPreviewBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div><!-- end right col -->

                    <!-- ── Columna Derecha: Escáner Continuo (Solo Normal) ── -->
                    <div id="skuScannerCol" data-show-for="normal" style="display:flex; flex-direction:column;">
                        <div style="background:var(--bg-color); border:1px solid var(--border-color); border-radius:12px; padding:16px; flex:1;">
                            <label class="form-label"><i class="ph ph-qr-code"></i> Escaneo Continuo</label>
                            <p style="font-size:0.8rem; color:var(--text-muted); margin-bottom:12px; line-height:1.4;">
                                Selecciona una columna personalizada y escanea los códigos. El sistema los asignará automáticamente a los SKUs generados.
                            </p>
                            
                            <div class="inv-form-field" style="margin-bottom:12px;">
                                <label class="form-label" style="font-size:0.8rem;">Columna destino</label>
                                <select class="form-select" id="continuousScanColumn">
                                    <option value="">Selecciona una columna...</option>
                                </select>
                            </div>

                            <div class="inv-form-field" style="margin-bottom:16px;">
                                <label class="form-label" style="font-size:0.8rem;">Escanear Código</label>
                                <div style="display:flex; gap:8px;">
                                    <input type="text" class="form-control" id="continuousScanInput" placeholder="Escanea o escribe aquí..." autocomplete="off">
                                </div>
                            </div>

                            <div style="background:var(--surface-color); border-radius:8px; padding:12px; border:1px solid var(--border-color);">
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                                    <span style="font-size:0.85rem; font-weight:600;">Progreso de escaneo</span>
                                    <span id="scanProgressCount" style="font-size:0.85rem; font-weight:700; color:var(--primary-color);">0 / 0</span>
                                </div>
                                <div style="width:100%; height:6px; background:var(--bg-color); border-radius:3px; overflow:hidden;">
                                    <div id="scanProgressBar" style="height:100%; width:0%; background:var(--primary-color); transition:width 0.3s ease;"></div>
                                </div>
                                <p id="scanProgressText" style="font-size:0.75rem; color:var(--text-muted); margin:8px 0 0; text-align:center;">Genera SKUs primero</p>
                            </div>
                        </div>
                        <input type="checkbox" id="prodRequiresPhotos" style="display:none;" value="0">
                    </div><!-- end scanner col -->

                </div><!-- end grid -->
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeProductModal()">Cancelar</button>
            <button class="btn btn-primary" id="btnSaveProduct"><i class="ph ph-floppy-disk"></i> Guardar Producto</button>
        </div>
    </div>


<!-- Modal: Editar Producto (Unified: Edit + Añadir Lote) -->
<div class="modal-overlay" id="editProductModal">
    <div class="modal-content" style="max-width:900px; width:95%;">
        <div class="modal-header">
            <h3><i class="ph ph-pencil-simple"></i> <span id="editProductTitle">Editar Producto</span></h3>
            <button class="close-modal" onclick="closeEditProductModal()">&times;</button>
        </div>
        <div class="modal-body" style="padding:0;">
            <div class="inv-tabs-bar" style="margin-bottom:0;border-radius:0;border:none;border-bottom:1px solid var(--border-color);">
                <button class="inv-tab active" data-eptab="info" onclick="switchEditProductTab('info')"><i class="ph ph-pencil-simple"></i> Datos</button>
                <button class="inv-tab" data-eptab="fotos" onclick="switchEditProductTab('fotos')"><i class="ph ph-camera"></i> Fotos</button>
                <button class="inv-tab" data-eptab="stock" onclick="switchEditProductTab('stock')"><i class="ph ph-plus-circle"></i> Añadir Lote</button>
            </div>
            <!-- Tab: Info -->
            <div class="ep-pane active" id="eptab-info" style="padding:20px;">
                <input type="hidden" id="editProductId" value="">
                
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px; align-items:start;">
                    <!-- Columna Izquierda -->
                    <div>
                        <div class="inv-form-field">
                    <label class="form-label">Nombre del Producto</label>
                    <input type="text" class="form-control" id="editProductName">
                    <div style="margin-top:6px; font-size:0.85rem;">
                        <a href="javascript:void(0)" onclick="document.getElementById('editAliasWrap').style.display='block'; this.style.display='none';" style="color:var(--primary-color); text-decoration:none;"><i class="ph ph-plus"></i> Añadir nombre alternativo</a>
                        <div id="editAliasWrap" style="display:none; margin-top:6px;">
                            <label class="form-label" style="font-size:0.8rem; color:var(--text-muted);">Nombres Alternativos (Aliases) - Presiona Enter para agregar</label>
                            <div class="inv-tags-input" style="border: 1px solid var(--border-color); border-radius: 8px; padding: 6px; background: var(--surface-color); display:flex; flex-wrap:wrap; gap:6px;">
                                <div id="editAliasTagsContainer" style="display:flex; gap:6px; flex-wrap:wrap;"></div>
                                <input type="text" id="editProdAliasInput" placeholder="Ej: Router negro" style="border:none; background:transparent; outline:none; flex:1; min-width:120px; font-size:0.9rem; color:var(--text-color);">
                                <input type="hidden" id="editProdAliases" value="">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="inv-form-field" style="margin-top:12px;">
                    <label class="form-label">Categoría</label>
                    <div style="display:flex;gap:6px;">
                        <select class="form-select" id="editProductCategory" style="flex:1;"></select>
                        <button type="button" class="btn btn-secondary" onclick="promptNewCategory()" title="Nueva categoría" style="padding:10px;flex-shrink:0;"><i class="ph ph-plus"></i></button>
                        <button type="button" class="btn btn-secondary" onclick="openManageCategories()" title="Gestionar categorías" style="padding:10px;flex-shrink:0;"><i class="ph ph-gear"></i></button>
                    </div>
                </div>
                <div class="row" style="margin-top:12px;">
                    <div class="col-6">
                        <div class="inv-form-field">
                            <label class="form-label">Stock Mínimo</label>
                            <input type="number" class="form-control" id="editProductStockMin" value="10">
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="inv-form-field">
                            <label class="form-label">Stock Crítico</label>
                            <input type="number" class="form-control" id="editProductStockCritico" value="3">
                        </div>
                    </div>
                </div>
                <div class="inv-form-field" style="margin-top:12px;">
                    <label class="form-label">Descripción</label>
                    <textarea class="form-control" id="editProductDesc" rows="2"></textarea>
                </div>
                <!-- Custom Columns Manager (Edit) -->
                <div style="margin-top:16px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                        <label class="form-label" style="margin:0;"><i class="ph ph-columns"></i> Columnas Personalizadas <small style="color:var(--text-muted);font-weight:400;">(por SKU)</small></label>
                        <span style="font-size:0.75rem;color:var(--text-muted);" id="editColCountBadge">0 columnas</span>
                    </div>
                    <!-- Sugerencias rápidas -->
                    <div style="margin-bottom:10px; display:flex; gap:6px; flex-wrap:wrap; font-size:0.78rem; align-items:center;" id="editSuggestionsWrap">
                        <span style="color:var(--text-muted);font-size:0.75rem;">Añadir rápido:</span>
                        <span class="inv-col-pill" style="cursor:pointer; background:var(--bg-color); border:1px dashed var(--primary-color); color:var(--primary-color);" onclick="promptNewSuggestion('edit')"><i class="ph ph-plus"></i> Nueva</span>
                    </div>
                    <!-- Columnas activas -->
                    <div id="editCustomColsList" style="display:flex;flex-direction:column;gap:6px;margin-bottom:10px;"></div>
                    <!-- Formulario añadir -->
                    <div style="background:var(--bg-color);border:1px dashed var(--border-color);border-radius:10px;padding:10px;">
                        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                            <input type="text" class="form-control" id="editNewColName" placeholder="Nombre de la columna..." style="flex:2;min-width:120px;" onkeydown="if(event.key==='Enter'){event.preventDefault();addEditCustomColumn();}">
                            <select class="form-control" id="editNewColType" style="flex:1;min-width:100px;">
                                <option value="text">📝 Texto</option>
                                <option value="number">🔢 Número</option>
                                <option value="date">📅 Fecha</option>
                                <option value="select">📋 Lista</option>
                            </select>
                            <button type="button" class="btn btn-primary" onclick="addEditCustomColumn()" style="flex-shrink:0;padding:8px 14px;"><i class="ph ph-plus"></i> Añadir</button>
                        </div>
                    </div>
                </div>
                    </div>
                    <!-- Columna Derecha -->
                    <div style="background:var(--bg-color); padding:20px; border-radius:12px; border:1px solid var(--border-color);">
                        <h4 style="font-size:0.95rem;font-weight:700;margin-top:0;margin-bottom:14px;"><i class="ph ph-info"></i> Opciones de Edición</h4>
                        <div style="color:var(--text-muted); font-size:0.85rem; line-height:1.6; margin-bottom:16px;">
                            <p>Estás editando la información base del producto. Los cambios se aplicarán a todos los SKUs existentes de este modelo.</p>
                            <p>Para ingresar más stock o generar nuevos SKUs, ve a la pestaña <strong><i class="ph ph-plus-circle"></i> Añadir Lote</strong>.</p>
                        </div>
                        
                        <!-- Mover SKU List with Photos aquí -->
                        <div id="editSkuPhotoList" style="display:none; margin-top:16px;">
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                                <label class="form-label" style="margin:0;font-size:0.88rem;"><i class="ph ph-camera" style="color:#8b5cf6;"></i> Fotos por SKU</label>
                                <span id="editSkuPhotoListCount" style="font-size:0.75rem;color:var(--text-muted);"></span>
                            </div>
                            <div id="editSkuPhotoListBody" style="max-height:260px;overflow-y:auto;border:1px solid var(--border-color);border-radius:10px;">
                                <!-- populated by JS -->
                            </div>
                        </div>
                    </div>
                </div>

                <button class="btn btn-primary w-100" style="margin-top:16px;" onclick="submitEditProduct(this)"><i class="ph ph-floppy-disk"></i> Guardar Cambios</button>
            </div>
            <!-- Tab: Fotos -->
            <div class="ep-pane" id="eptab-fotos" style="padding:20px;">
                <!-- Requires Photos toggle -->
                <div style="background:linear-gradient(135deg, rgba(139,92,246,0.05), rgba(139,92,246,0.02)); padding:12px; border-radius:12px; margin-bottom:16px; border: 1px solid rgba(139,92,246,0.2);">
                    <div class="form-check" style="margin-bottom:0;">
                        <input class="form-check-input" type="checkbox" id="editRequiresPhotos" style="border-color: #8b5cf6;">
                        <label class="form-check-label" for="editRequiresPhotos" style="font-weight:700;"><i class="ph ph-identification-badge" style="color:#8b5cf6;margin-right:4px;"></i> Fotos individuales por SKU</label>
                        <small style="color:var(--text-muted);display:block;margin-top:2px;">Al activar, cada SKU tendrá su propia galería de fotos en el módulo Mochila.</small>
                    </div>
                </div>
                <!-- Multi-Photo Upload -->
                <label class="form-label" style="font-weight:700; display:flex; align-items:center; gap:6px; margin-bottom:10px;">
                    <i class="ph ph-images" style="color:#8b5cf6; font-size:1.1rem;"></i> Fotos del Producto
                </label>
                <div style="display:flex; gap:8px; margin-bottom:12px;">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('editMultiPhotoInput').click()" style="flex:1;"><i class="ph ph-upload-simple"></i> Subir fotos</button>
                    <button type="button" class="btn btn-secondary" onclick="captureProductPhoto('edit')" style="flex:1;"><i class="ph ph-camera"></i> Tomar foto</button>
                </div>
                <div class="prod-photo-dropzone" id="editPhotoDropzone"
                     onclick="document.getElementById('editMultiPhotoInput').click()"
                     ondrop="handleProductPhotoDrop(event, 'edit')"
                     ondragover="event.preventDefault(); this.classList.add('dragover')"
                     ondragleave="this.classList.remove('dragover')">
                    <i class="ph ph-cloud-arrow-up" style="font-size:2.5rem; color:var(--text-muted); opacity:0.4;"></i>
                    <p style="margin:4px 0 0; font-size:0.85rem; color:var(--text-muted);">Arrastra fotos aquí o <strong style="color:var(--primary-color);">haz clic</strong></p>
                    <span style="font-size:0.75rem; color:var(--text-muted);">JPG, PNG o WebP</span>
                </div>
                <input type="file" id="editMultiPhotoInput" class="no-dropzone" multiple accept="image/*" style="position:absolute; left:-9999px; width:1px; height:1px;" onchange="handleProductPhotoSelect(event, 'edit')">
                <input type="file" id="editProdPhotoInput" class="no-dropzone" accept="image/*" style="position:absolute; left:-9999px; width:1px; height:1px;" onchange="handleProductPhotoSelect(event, 'edit')">
                <!-- Existing + new photos gallery -->
                <div id="editPhotoGallery" class="prod-photo-gallery" style="margin-top:12px;"></div>
            </div>
            <!-- Tab: Añadir Lote -->
            <div class="ep-pane" id="eptab-stock" style="padding:20px;">
                <input type="hidden" id="addStockProductId" value="">
                <div class="inv-form-field">
                    <label class="form-label">Producto</label>
                    <input type="text" class="form-control" id="addStockProductName" readonly style="background:var(--bg-color); color:var(--text-color);">
                </div>
                <div id="addStockNormalWrap">
                    <div class="inv-form-field" style="margin-top:16px;">
                        <label class="form-label">Cantidad a añadir (SKUs automáticos)</label>
                        <input type="number" class="form-control" id="addStockQuantity" min="1" value="1">
                    </div>
                </div>
                <div id="addStockAgrupadoWrap" style="display:none; margin-top:16px;">
                    <label class="form-label" style="margin-bottom:8px;"><i class="ph ph-stack" style="color:#8b5cf6;"></i> Variantes (Ingreso múltiple)</label>
                    <div class="table-responsive" style="border:1px solid var(--border-color);border-radius:10px;max-height:300px;overflow-y:auto;">
                        <table class="inv-table" style="margin:0;">
                            <thead>
                                <tr>
                                    <th>Variante</th>
                                    <th style="width:100px;">Actual</th>
                                    <th style="width:120px;">Añadir</th>
                                </tr>
                            </thead>
                            <tbody id="addStockVariantsList"></tbody>
                        </table>
                    </div>
                </div>
                <button class="btn btn-primary w-100" style="margin-top:16px;" id="btnSaveAddStock" onclick="submitAddStock()"><i class="ph ph-check"></i> Añadir Stock</button>
            </div>
        </div>
    </div>
</div>
<style>
.ep-pane { display: none; }
.ep-pane.active { display: block; }
</style>


<!-- Modal: SKU Detail (Unified Layout) -->
<div class="modal-overlay" id="skuDetailModal">
    <div class="modal-content" id="skuDetailContentWrap" style="max-width:1100px; padding:0; background:var(--bg-color);">
        <div class="modal-header" style="gap:12px;align-items:center;background:var(--surface-color);padding:20px;border-bottom:1px solid var(--border-color);">
            <div id="skuDetailHeaderImg" style="width:52px;height:52px;border-radius:12px;overflow:hidden;flex-shrink:0;display:flex;align-items:center;justify-content:center;background:rgba(99,102,241,0.1);border:1px solid var(--border-color);">
                <i class="ph ph-package" style="font-size:1.6rem;color:#6366f1;"></i>
            </div>
            <div style="flex:1;min-width:0;">
                <h3 style="margin:0;font-size:1.2rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:var(--text-color);">
                    <span id="skuDetailTitle">Detalle SKU</span>
                </h3>
            </div>
            <button class="close-modal" onclick="closeSkuDetail()">&times;</button>
        </div>
        <div class="modal-body" style="padding:20px;">
            <!-- Green Box: Cards -->
            <div id="skuEditInfo" class="info-cards-grid" style="margin-bottom:20px;"></div>

            <!-- Orange Boxes: Columns -->
            <div class="modal-body-split">
                <!-- Left Column -->
                <div class="modal-col-left" style="background:var(--surface-color); padding:20px; border-radius:12px; border:1px solid var(--border-color);">
                    <!-- Assign -->
                    <h4 style="font-size:0.9rem;font-weight:700;margin-bottom:10px;"><i class="ph ph-user"></i> Asignar a usuario</h4>
                    <div id="skuAssignCurrent" style="margin-bottom:12px;"></div>
                    <select class="form-select" id="skuAssignUser">
                        <option value="">Seleccionar usuario...</option>
                    </select>
                    <div class="form-check" style="margin-top:12px;">
                        <input class="form-check-input" type="checkbox" id="skuAssignIsEpp" style="border-color:#8b5cf6;">
                        <label class="form-check-label" for="skuAssignIsEpp" style="font-weight:600;"><i class="ph ph-shield-check" style="color:#8b5cf6;"></i> Asignar como EPP (Equipo de Protección Personal)</label>
                    </div>
                    <div style="display:flex;gap:8px;margin-top:12px;margin-bottom:20px;">
                        <button class="btn btn-primary" onclick="assignSkuToUser()" style="flex:1;"><i class="ph ph-user-plus"></i> Asignar</button>
                        <button class="btn btn-secondary" onclick="unassignSku()" style="flex:1;"><i class="ph ph-user-minus"></i> Desasignar</button>
                    </div>
                    <hr style="border-color:var(--border-color);margin:16px 0;">
                    
                    <!-- Status -->
                    <div id="statusWrapper" style="margin-bottom:20px;">
                        <h4 style="font-size:0.9rem;font-weight:700;margin-bottom:10px;"><i class="ph ph-tag"></i> Cambiar Estado</h4>
                        <select class="form-select" id="skuDetailStatus" onchange="updateSkuDetailStatus()">
                            <option value="disponible">Disponible</option>
                            <option value="instalado">Instalado</option>
                            <option value="malogrado">Malogrado</option>
                            <option value="reparado">Reparado</option>
                            <option value="en_transito">En Tránsito</option>
                            <option value="observacion">Observación</option>
                        </select>
                    </div>
                    <hr style="border-color:var(--border-color);margin:16px 0;">

                    <!-- Movement -->
                    <h4 style="font-size:0.9rem;font-weight:700;margin-bottom:10px;"><i class="ph ph-swap"></i> Registrar Movimiento</h4>
                    <div style="margin-bottom:12px;">
                        <label class="form-label">Tipo de Movimiento</label>
                        <select class="form-select" id="entryType">
                            <option value="entrada">📥 Entrada</option>
                            <option value="salida">📤 Salida</option>
                            <option value="devolucion">🔄 Devolución</option>
                            <option value="reparacion">🔧 Reparación</option>
                        </select>
                    </div>
                    <div style="margin-bottom:12px;">
                        <label class="form-label">Notas</label>
                        <textarea class="form-control" id="entryNotas" rows="4" placeholder="Descripción del movimiento..."></textarea>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="modal-col-right" style="background:var(--surface-color); border-radius:12px; border:1px solid var(--border-color); display:flex; flex-direction:column; overflow:hidden;">
                    <div style="padding:20px; overflow-y:auto; flex:1;">
                        <div style="margin-bottom:20px;">
                            <h4 style="font-size:0.9rem;font-weight:700;margin-bottom:10px;"><i class="ph ph-camera"></i> Fotos de Evidencia</h4>
                            <div class="inv-photo-buttons">
                                <button type="button" class="btn btn-secondary" onclick="takeEntryPhoto()" style="flex:1;"><i class="ph ph-camera"></i> Tomar Foto</button>
                                <button type="button" class="btn btn-secondary" onclick="document.getElementById('entryPhotosGallery').click()" style="flex:1;"><i class="ph ph-images"></i> Galería</button>
                            </div>
                            <div id="photoPreviewList" class="inv-photo-previews"></div>
                            <input type="file" id="entryPhotosGallery" class="no-dropzone" multiple accept="image/*" onchange="previewEntryPhotos(this)" style="position:absolute;width:0;height:0;overflow:hidden;opacity:0;pointer-events:none;">
                        </div>
                        <div>
                            <h4 style="font-size:0.9rem;font-weight:700;margin-bottom:10px;"><i class="ph ph-clock-counter-clockwise"></i> Historial</h4>
                            <div id="entryHistoryList" class="inv-entry-history"></div>
                        </div>
                    </div>
                    <!-- Sticky footer within the flex container -->
                    <div style="background:var(--surface-color); padding:16px 20px; border-top:1px solid var(--border-color); z-index:10; flex-shrink:0;">
                        <button class="btn btn-primary" onclick="submitEntry()" style="width:100%;"><i class="ph ph-floppy-disk"></i> Guardar Movimiento</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Editar Movimiento -->
<div class="modal-overlay" id="editEntryModal" style="z-index: 19001;">
    <div class="modal-content" style="max-width:400px;">
        <div class="modal-header">
            <h3><i class="ph ph-pencil-simple"></i> Editar Movimiento</h3>
            <button class="close-modal" onclick="closeEditEntryModal()">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="editEntryId">
            <div class="inv-form-field" style="margin-bottom:12px;">
                <label class="form-label">Fecha y Hora</label>
                <input type="datetime-local" class="form-control" id="editEntryDate">
            </div>
            <div class="inv-form-field" style="margin-bottom:12px;">
                <label class="form-label">Tipo de Movimiento</label>
                <select class="form-select" id="editEntryType">
                    <option value="entrada">📥 Entrada</option>
                    <option value="salida">📤 Salida</option>
                    <option value="devolucion">🔄 Devolución</option>
                    <option value="reparacion">🔧 Reparación</option>
                </select>
            </div>
            <div class="inv-form-field" style="margin-bottom:16px;">
                <label class="form-label">Notas</label>
                <textarea class="form-control" id="editEntryNotas" rows="3"></textarea>
            </div>
            <button type="button" class="btn btn-primary w-100" onclick="saveEditEntry()"><i class="ph ph-floppy-disk"></i> Guardar Cambios</button>
        </div>
    </div>
</div>

<!-- Modal: Editar Registro de Stock -->
<div class="modal-overlay" id="editStockLogModal" style="z-index: 19002;">
    <div class="modal-content" style="max-width:420px;">
        <div class="modal-header">
            <h3><i class="ph ph-pencil-simple" style="color:#8b5cf6;"></i> Editar Registro de Stock</h3>
            <button class="close-modal" onclick="closeEditStockLog()">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="editStockLogId">
            <div style="background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.3);border-radius:10px;padding:10px 14px;margin-bottom:16px;font-size:0.82rem;color:#b45309;">
                <i class="ph ph-warning"></i> Cambiar la cantidad puede crear o eliminar SKUs del sistema.
            </div>
            <div class="inv-form-field" style="margin-bottom:12px;">
                <label class="form-label">Cantidad</label>
                <input type="number" class="form-control" id="editStockLogQty" min="1">
            </div>
            <div class="inv-form-field" style="margin-bottom:16px;">
                <label class="form-label">Notas</label>
                <textarea class="form-control" id="editStockLogNotes" rows="3"></textarea>
            </div>
            <button type="button" class="btn btn-primary w-100" onclick="saveEditStockLog()"><i class="ph ph-floppy-disk"></i> Guardar Cambios</button>
        </div>
    </div>
</div>
<!-- Modal: Editar Stock de Producto -->
<div class="modal-overlay" id="editStockModal" style="z-index: 19003;">
    <div class="modal-content" style="max-width:560px; width:96%;">
        <div class="modal-header">
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:38px;height:38px;border-radius:10px;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="ph ph-stack-plus" style="font-size:1.2rem;color:#fff;"></i>
                </div>
                <div>
                    <h3 style="margin:0;font-size:1.05rem;" id="editStockModalTitle">Editar Stock</h3>
                    <p style="margin:0;font-size:0.78rem;color:var(--text-muted);" id="editStockModalSub"></p>
                </div>
            </div>
            <button class="close-modal" onclick="closeEditStockModal()">&times;</button>
        </div>
        <div class="modal-body" style="padding:20px;">
            <input type="hidden" id="esProductId">
            <input type="hidden" id="esProductType">
            <input type="hidden" id="esIsBulk">
            <input type="hidden" id="esCurrentStock">

            <!-- ── NORMAL / GRANEL ── -->
            <div id="esNormalWrap">
                <!-- Modo Toggle (Solo para productos No Granel) -->
                <div id="esModeToggleWrap" style="display:none; margin-bottom:16px;">
                    <div style="display:flex; background:rgba(255,255,255,0.05); border:1px solid var(--border-color); border-radius:10px; padding:4px;">
                        <button type="button" class="inv-tab active" id="btnModeManual" onclick="esSetMode('manual')" style="flex:1; padding:8px; border-radius:6px; text-align:center; transition:all 0.2s;"><i class="ph ph-hand-pointing"></i> Manualmente</button>
                        <button type="button" class="inv-tab" id="btnModeScan" onclick="esSetMode('scan')" style="flex:1; padding:8px; border-radius:6px; text-align:center; transition:all 0.2s;"><i class="ph ph-barcode"></i> Escanear</button>
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px;">
                    <div style="background:var(--bg-color);border:1px solid var(--border-color);border-radius:12px;padding:14px;text-align:center;">
                        <div style="font-size:0.75rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:4px;">Stock Actual</div>
                        <div style="font-size:1.8rem;font-weight:800;color:var(--text-color);" id="esCurrentStockDisplay">—</div>
                        <div style="font-size:0.75rem;color:var(--text-muted);" id="esUnitLabel"></div>
                    </div>
                    <div style="background:linear-gradient(135deg,rgba(99,102,241,0.08),rgba(139,92,246,0.05));border:1px solid rgba(99,102,241,0.25);border-radius:12px;padding:14px;text-align:center;">
                        <div style="font-size:0.75rem;color:#6366f1;font-weight:600;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:4px;">Nuevo Stock</div>
                        <div style="font-size:1.8rem;font-weight:800;color:#6366f1;" id="esNewStockDisplay">—</div>
                        <div id="esChangeBadge" style="font-size:0.78rem;font-weight:700;margin-top:2px;"></div>
                    </div>
                </div>

                <!-- MANUAL MODE -->
                <div id="esManualSection">
                    <div style="margin-bottom:16px;">
                        <label class="form-label" style="font-weight:600;">Nueva cantidad total</label>
                        <div style="display:flex;gap:8px;align-items:center;">
                            <button type="button" onclick="esAdjust(-1)" style="width:40px;height:40px;border:1px solid var(--border-color);background:var(--bg-color);border-radius:8px;font-size:1.2rem;cursor:pointer;flex-shrink:0;color:var(--text-color);">−</button>
                            <input type="number" id="esNewQty" class="form-control" min="0" step="1" style="text-align:center;font-size:1.1rem;font-weight:700;" oninput="esUpdatePreview()">
                            <button type="button" onclick="esAdjust(1)" style="width:40px;height:40px;border:1px solid var(--border-color);background:var(--bg-color);border-radius:8px;font-size:1.2rem;cursor:pointer;flex-shrink:0;color:var(--text-color);">+</button>
                        </div>
                    </div>
                </div>

                <!-- SCAN MODE -->
                <div id="esScanSection" style="display:none; margin-bottom:16px;">
                    <div style="background:var(--bg-color);border:1px solid var(--border-color);border-radius:10px;padding:14px;">
                        
                        <div style="display:flex; gap:8px; margin-bottom:12px;">
                            <button type="button" id="btnScanActionAdd" class="btn btn-primary" style="flex:1; font-size:0.85rem;" onclick="esSetScanAction('add')"><i class="ph ph-plus-circle"></i> Añadir Stock</button>
                            <button type="button" id="btnScanActionRemove" class="btn btn-secondary" style="flex:1; font-size:0.85rem;" onclick="esSetScanAction('remove')"><i class="ph ph-minus-circle"></i> Retirar Stock</button>
                        </div>

                        <div id="esScanAddConfig" style="margin-bottom:12px;">
                            <label class="form-label" style="font-size:0.82rem;font-weight:600;">¿Dónde guardar los códigos escaneados?</label>
                            <select id="esScanTargetCol" class="form-control" style="font-size:0.85rem;padding:6px 10px;height:auto;">
                                <option value="sku_code">Código Principal (SKU)</option>
                            </select>
                        </div>

                        <div id="esScanArea">
                            <label class="form-label" style="margin-bottom:6px;font-size:0.82rem;font-weight:600;"><i class="ph ph-barcode"></i> <span id="esScanInputLabel">Escanea para añadir</span></label>
                            <input type="text" id="esScanInputLive" class="form-control" placeholder="Haz clic aquí y usa tu escáner..." style="font-family:monospace;font-size:0.9rem;" autocomplete="off" onkeydown="esHandleLiveScan(event)">
                            <div id="esScanWarning" style="margin-top:6px;font-size:0.75rem;color:#ef4444;display:none;"></div>
                        </div>

                        <div id="esScannedTableWrap" style="margin-top:14px; display:none; border:1px solid var(--border-color); border-radius:8px; overflow:hidden;">
                            <table class="inv-table" style="margin:0; font-size:0.8rem;">
                                <thead>
                                    <tr>
                                        <th style="padding:6px 10px;">Código Escaneado</th>
                                        <th style="width:40px; text-align:center; padding:6px 10px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="esScannedList"></tbody>
                            </table>
                        </div>

                    </div>
                </div>

            </div>

            <!-- ── AGRUPADO ── -->
            <div id="esAgrupadoWrap" style="display:none;">
                <div style="font-size:0.82rem;color:var(--text-muted);margin-bottom:12px;">
                    <i class="ph ph-info" style="color:#6366f1;"></i> Ajusta la cantidad de cada variante. Los cambios se aplican al guardar.
                </div>
                <div style="border:1px solid var(--border-color);border-radius:12px;overflow:hidden;max-height:340px;overflow-y:auto;">
                    <table class="inv-table" style="margin:0;">
                        <thead>
                            <tr>
                                <th>Variante</th>
                                <th style="width:100px;text-align:center;">Actual</th>
                                <th style="width:160px;text-align:center;">Nueva cantidad</th>
                                <th style="width:80px;text-align:center;">Cambio</th>
                            </tr>
                        </thead>
                        <tbody id="esVariantsList"></tbody>
                    </table>
                </div>
                <div id="esAgrupadoTotal" style="display:flex;justify-content:space-between;align-items:center;margin-top:12px;padding:10px 14px;background:var(--bg-color);border-radius:10px;border:1px solid var(--border-color);">
                    <span style="font-weight:600;font-size:0.88rem;color:var(--text-muted);">Total agrupado:</span>
                    <span style="font-weight:800;font-size:1.1rem;color:#6366f1;" id="esAgrupadoTotalVal">—</span>
                </div>
            </div>

            <div style="margin-top:4px;">
                <label class="form-label" style="font-weight:600;">Notas (opcional)</label>
                <textarea class="form-control" id="esNotes" rows="2" placeholder="Motivo del ajuste de stock..."></textarea>
            </div>
        </div>
        <div class="modal-footer" style="padding:14px 20px;border-top:1px solid var(--border-color);display:flex;gap:10px;justify-content:flex-end;">
            <button type="button" class="btn btn-secondary" onclick="closeEditStockModal()">Cancelar</button>
            <button type="button" class="btn btn-primary" id="esSaveBtn" onclick="saveEditStockModal()">
                <i class="ph ph-floppy-disk"></i> Guardar Cambios
            </button>
        </div>
    </div>
</div>

<!-- Modal: Editar Registro de Asignación -->
<div class="modal-overlay" id="editAssignLogModal" style="z-index: 19002;">
    <div class="modal-content" style="max-width:480px;">
        <div class="modal-header">
            <h3><i class="ph ph-pencil-simple" style="color:#6366f1;"></i> Editar Asignación</h3>
            <button class="close-modal" onclick="closeEditAssignLog()">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="editAssignLogId">
            <input type="hidden" id="editAssignLogProductType">
            <input type="hidden" id="editAssignLogIsUnassign" value="0">
            <div class="inv-form-field" style="margin-bottom:12px;">
                <label class="form-label">Fecha y Hora</label>
                <input type="datetime-local" class="form-control" id="editAssignLogDate">
            </div>
            <div class="inv-form-field" style="margin-bottom:12px;">
                <label class="form-label"><i class="ph ph-user" style="color:#6366f1;"></i> Asignado a</label>
                <div style="display:flex;gap:8px;align-items:center;">
                    <select class="form-select" id="editAssignLogUser" style="flex:1;">
                        <option value="">Cargando...</option>
                    </select>
                    <button type="button" class="btn btn-secondary" title="Marcar como desasignado" onclick="editAssignLogSetUnassign()" style="padding:8px 12px;flex-shrink:0;" id="editAssignLogUnassignBtn">
                        <i class="ph ph-user-minus"></i>
                    </button>
                </div>
                <div id="editAssignLogUnassignHint" style="display:none;margin-top:6px;background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.3);border-radius:8px;padding:8px 12px;font-size:0.82rem;color:#ef4444;">
                    <i class="ph ph-warning"></i> Se marcará como <strong>DESASIGNADO</strong>
                    <button type="button" onclick="editAssignLogCancelUnassign()" style="background:none;border:none;color:#ef4444;cursor:pointer;font-size:0.8rem;float:right;text-decoration:underline;">Cancelar</button>
                </div>
            </div>
            <div class="inv-form-field" id="editAssignLogQtyWrap" style="margin-bottom:12px;">
                <label class="form-label"><i class="ph ph-stack" style="color:#6366f1;"></i> Cantidad</label>
                <input type="number" class="form-control" id="editAssignLogQty" min="0.01" step="0.01" value="1">
                <small style="color:var(--text-muted);font-size:0.78rem;" id="editAssignLogQtyHint"></small>
            </div>
            <div class="inv-form-field" style="margin-bottom:16px;">
                <label class="form-label">Notas</label>
                <textarea class="form-control" id="editAssignLogNotes" rows="3" placeholder="Notas adicionales sobre esta asignación..."></textarea>
            </div>
            <button type="button" class="btn btn-primary w-100" onclick="saveEditAssignLog()"><i class="ph ph-floppy-disk"></i> Guardar Cambios</button>
        </div>
    </div>
</div>

<!-- Modal: Gestionar Categorías -->
<div class="modal-overlay" id="manageCategoriesModal" style="z-index: 19000;">
    <div class="modal-content" style="max-width:500px;">
        <div class="modal-header">
            <h3><i class="ph ph-list"></i> Gestionar Categorías</h3>
            <button class="close-modal" onclick="closeManageCategories()">&times;</button>
        </div>
        <div class="modal-body">
            <div style="display:flex; gap:8px; margin-bottom:16px;">
                <input type="text" id="newCategoryName" class="form-control" placeholder="Nombre de nueva categoría..." style="flex:1;">
                <button class="btn btn-primary" onclick="addCategoryDirect()"><i class="ph ph-plus"></i> Agregar</button>
            </div>
            <div class="table-responsive" style="max-height:300px; overflow-y:auto; border:1px solid var(--border-color); border-radius:10px;">
                <table class="inv-table" style="margin-bottom:0;">
                    <tbody id="manageCategoriesList"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Selector de fuente de foto (paso 1) -->
<div class="modal-overlay" id="skuPhotoPickerModal">
    <div class="modal-content" style="max-width:420px;">
        <div class="modal-header" style="padding-bottom:8px;">
            <div>
                <h3 style="margin:0;font-size:1.1rem;"><i class="ph ph-camera" style="color:#8b5cf6;"></i> Añadir foto</h3>
                <p style="margin:4px 0 0;font-size:0.82rem;color:var(--text-muted);">SKU: <strong id="skuPickerCode" style="color:#8b5cf6;"></strong></p>
            </div>
            <button class="close-modal" onclick="document.getElementById('skuPhotoPickerModal').classList.remove('active')">&times;</button>
        </div>
        <div class="modal-body" style="padding-top:8px;">
            <p style="font-size:0.9rem;color:var(--text-muted);margin:0 0 16px;text-align:center;">¿Cómo quieres subir la foto?</p>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <!-- Cámara -->
                <button class="sku-photo-source-btn" id="btnPickCamera" onclick="pickSkuPhotoSource('camera')">
                    <div class="sku-source-icon" style="background:linear-gradient(135deg,#8b5cf6,#6366f1);">
                        <i class="ph ph-camera"></i>
                    </div>
                    <span class="sku-source-title">Tomar foto</span>
                    <span class="sku-source-desc">Usa la cámara de tu dispositivo</span>
                </button>
                <!-- Galería -->
                <button class="sku-photo-source-btn" id="btnPickGallery" onclick="pickSkuPhotoSource('gallery')">
                    <div class="sku-source-icon" style="background:linear-gradient(135deg,#10b981,#059669);">
                        <i class="ph ph-images"></i>
                    </div>
                    <span class="sku-source-title">Elegir de galería</span>
                    <span class="sku-source-desc">Selecciona una o varias fotos</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Hidden inputs para captura -->
<input type="file" id="skuPhotoFileInput"    class="no-dropzone" multiple accept="image/*"                 style="position:absolute;left:-9999px;width:1px;height:1px;" onchange="handleSkuPhotoFiles(this.files)">
<input type="file" id="skuPhotoCaptureInput" class="no-dropzone"          accept="image/*" capture="environment" style="position:absolute;left:-9999px;width:1px;height:1px;" onchange="handleSkuPhotoFiles(this.files)">

<!-- Modal: SKU Photo Upload (paso 2) -->
<div class="modal-overlay" id="skuPhotoModal">
    <div class="modal-content" style="max-width:550px;">
        <div class="modal-header">
            <h3><i class="ph ph-images" style="color:#8b5cf6;"></i> Fotos del SKU: <span id="skuPhotoCode" style="color:#8b5cf6;"></span></h3>
            <button class="close-modal" onclick="document.getElementById('skuPhotoModal').classList.remove('active')">&times;</button>
        </div>
        <div class="modal-body">
            <div style="display:flex; gap:8px; margin-bottom:12px;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('skuPhotoFileInput').click()" style="flex:1;"><i class="ph ph-images"></i> Galería</button>
                <button type="button" class="btn btn-secondary" onclick="captureSkuPhoto()" style="flex:1;"><i class="ph ph-camera"></i> Cámara</button>
            </div>
            <div id="skuPhotoGallery" class="prod-photo-gallery"></div>
            <div id="skuPhotoNewFiles" class="prod-photo-gallery" style="margin-top:8px;"></div>
            <button type="button" class="btn btn-primary w-100" style="margin-top:12px;" id="btnSaveSkuPhotos" onclick="saveSkuPhotos()"><i class="ph ph-floppy-disk"></i> Guardar Fotos</button>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════ -->
<!-- Image Viewer / Lightbox                    -->
<!-- ═══════════════════════════════════════════ -->
<div id="invLightbox" style="display:none;" onclick="if(event.target===this)closeLightbox()">
    <button id="invLbClose" onclick="closeLightbox()" title="Cerrar (ESC)"><i class="ph ph-x"></i></button>
    <div id="invLbCaption"></div>
    <div id="invLbImgWrap">
        <img id="invLbImg" src="" alt="" draggable="false">
    </div>
    <div id="invLbActions">
        <button id="invLbZoomIn"  onclick="lbZoom(0.2)"  title="Zoom +"><i class="ph ph-magnifying-glass-plus"></i></button>
        <button id="invLbZoomOut" onclick="lbZoom(-0.2)" title="Zoom -"><i class="ph ph-magnifying-glass-minus"></i></button>
        <button id="invLbReset"   onclick="lbResetZoom()" title="Ajustar"><i class="ph ph-arrows-out"></i></button>
        <a id="invLbDownload" href="#" download title="Descargar"><i class="ph ph-download-simple"></i></a>
    </div>
</div>

<!-- Libraries -->
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<!-- ═══════════════════════════════════════════════════════
     Google Sheets Sync Modal
     ═══════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="sheetsSyncModal">
  <div class="modal-content" style="max-width:500px; width:90%; max-height:90vh; overflow-y:auto;">
    <div class="modal-header" style="background:linear-gradient(135deg,#0f9d58 0%,#0b7a45 100%); border-radius: 16px 16px 0 0; padding: 18px 20px; flex-shrink:0;">
      <div style="display:flex; align-items:center; gap:12px;">
        <div style="width:40px;height:40px;border-radius:10px;background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M14 2v6h6" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <line x1="8" y1="13" x2="16" y2="13" stroke="white" stroke-width="2" stroke-linecap="round"/>
            <line x1="8" y1="17" x2="16" y2="17" stroke="white" stroke-width="2" stroke-linecap="round"/>
          </svg>
        </div>
        <div>
          <h3 style="margin:0;font-size:1.05rem;font-weight:700;">Google Sheets Sync</h3>
          <p style="margin:0;font-size:0.8rem;opacity:0.8;">Exporta e importa tu inventario</p>
        </div>
      </div>
      <button onclick="SheetsSync.closeModal()" style="background:rgba(255,255,255,0.15);border:none;color:#fff;width:32px;height:32px;border-radius:8px;cursor:pointer;font-size:1.1rem;display:flex;align-items:center;justify-content:center;"><i class="ph ph-x"></i></button>
    </div>
    <div class="modal-body" style="padding:20px; display:flex; flex-direction:column; gap:16px;">

      <!-- Config status -->
      <div id="sheetsConfigStatus" style="border-radius:12px;padding:14px 16px;border:1.5px solid var(--border-color);background:var(--bg-color);">
        <div style="font-size:0.78rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:10px;">Estado de configuración</div>
        <div style="display:flex;flex-direction:column;gap:8px;" id="sheetsChecks">
          <div class="sheets-check-row" id="chkLibrary"><i class="ph ph-spinner sheets-spin"></i> <span>Librería Google</span></div>
          <div class="sheets-check-row" id="chkCreds"><i class="ph ph-spinner sheets-spin"></i> <span>Credenciales JSON</span></div>
          <div class="sheets-check-row" id="chkSheet"><i class="ph ph-spinner sheets-spin"></i> <span>ID del Google Sheet</span></div>
        </div>
      </div>

      <!-- Setup guide (shown if not configured) -->
      <div id="sheetsSetupGuide" style="display:none; border-radius:12px;padding:14px 16px;background:rgba(99,102,241,0.05);border:1.5px solid rgba(99,102,241,0.2);">
        <div style="font-size:0.82rem;font-weight:700;color:var(--primary-color);margin-bottom:10px;"><i class="ph ph-info"></i> Pasos para configurar:</div>
        <ol style="margin:0;padding-left:18px;font-size:0.82rem;line-height:1.8;color:var(--text-color);">
          <li>Descarga el JSON de tu Service Account de Google Cloud</li>
          <li>Coloca el archivo como <code style="background:var(--bg-color);padding:1px 5px;border-radius:4px;">config/google-credentials.json</code></li>
          <li>Crea un Google Sheet y copia su ID desde la URL</li>
          <li>Pega el ID en <code style="background:var(--bg-color);padding:1px 5px;border-radius:4px;">config/google_sheets.php</code></li>
          <li>Comparte el Sheet con el email de tu Service Account</li>
        </ol>
        <div style="margin-top:10px;">
          <label style="font-size:0.8rem;font-weight:600;color:var(--text-muted);">Email de tu Service Account:</label>
          <div id="sheetsServiceEmail" style="font-size:0.82rem;background:var(--bg-color);border:1px solid var(--border-color);border-radius:8px;padding:6px 10px;margin-top:4px;font-family:monospace;word-break:break-all;">—</div>
        </div>
        <button onclick="SheetsSync.openConfigFile()" style="margin-top:10px;background:var(--primary-color);color:#fff;border:none;border-radius:8px;padding:7px 14px;font-size:0.82rem;cursor:pointer;display:flex;align-items:center;gap:6px;">
          <i class="ph ph-pencil"></i> Pegar ID del Sheet
        </button>
      </div>

      <!-- Sheet ID input (inline) -->
      <div id="sheetsIdInputWrap" style="display:none; flex-direction:column; gap:8px;">
        <label style="font-size:0.82rem;font-weight:600;color:var(--text-muted);">ID del Google Sheet</label>
        <div style="display:flex;gap:8px;">
          <input type="text" id="sheetsIdInput" placeholder="Pega el ID de la URL de tu Sheet..." style="flex:1;padding:8px 12px;border:1.5px solid var(--border-color);border-radius:9px;background:var(--bg-color);color:var(--text-color);font-size:0.88rem;">
          <button onclick="SheetsSync.saveSheetId()" style="background:var(--primary-color);color:#fff;border:none;border-radius:9px;padding:8px 14px;cursor:pointer;font-size:0.88rem;white-space:nowrap;"><i class="ph ph-floppy-disk"></i> Guardar</button>
        </div>
        <p style="margin:0;font-size:0.75rem;color:var(--text-muted);">Ejemplo: docs.google.com/spreadsheets/d/<strong>[ESTE_ID]</strong>/edit</p>
      </div>

      <!-- Actions (shown when configured) -->
      <div id="sheetsActions" style="display:none; display:none; gap:12px; flex-direction:column;">

        <!-- Export card -->
        <div style="border-radius:12px;border:1.5px solid var(--border-color);padding:14px 16px;">
          <div style="display:flex;align-items:center;justify-content:space-between;">
            <div>
              <div style="font-weight:700;font-size:0.9rem;"><i class="ph ph-arrow-square-out" style="color:#0f9d58;"></i> Exportar a Sheets</div>
              <div style="font-size:0.78rem;color:var(--text-muted);margin-top:2px;">Sobreescribe las hojas: Productos · Control de Stock · Resumen</div>
            </div>
            <button onclick="SheetsSync.export()" id="btnSheetsExport" class="btn-sheets-action export">
              <i class="ph ph-upload"></i> Exportar
            </button>
          </div>
        </div>

        <!-- Import card -->
        <div style="border-radius:12px;border:1.5px solid var(--border-color);padding:14px 16px;">
          <div style="display:flex;align-items:center;justify-content:space-between;">
            <div>
              <div style="font-weight:700;font-size:0.9rem;"><i class="ph ph-arrow-square-in" style="color:#6366f1;"></i> Importar desde Sheets</div>
              <div style="font-size:0.78rem;color:var(--text-muted);margin-top:2px;">Lee la hoja Productos y crea/actualiza en la base de datos</div>
            </div>
            <button onclick="SheetsSync.import()" id="btnSheetsImport" class="btn-sheets-action import">
              <i class="ph ph-download"></i> Importar
            </button>
          </div>
        </div>

        <!-- Open in Sheets -->
        <a id="sheetsOpenLink" href="#" target="_blank" style="display:flex;align-items:center;justify-content:center;gap:8px;padding:10px;border-radius:10px;background:rgba(15,157,88,0.07);border:1.5px solid rgba(15,157,88,0.2);color:#0f9d58;font-size:0.85rem;font-weight:600;text-decoration:none;transition:all 0.2s;" onmouseover="this.style.background='rgba(15,157,88,0.12)'" onmouseout="this.style.background='rgba(15,157,88,0.07)'">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" stroke="#0f9d58" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><polyline points="15 3 21 3 21 9" stroke="#0f9d58" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><line x1="10" y1="14" x2="21" y2="3" stroke="#0f9d58" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Abrir Google Sheet
        </a>
      </div>

      <!-- Log output -->
      <div id="sheetsLog" style="display:none;border-radius:10px;padding:12px 14px;background:var(--bg-color);border:1px solid var(--border-color);font-size:0.82rem;line-height:1.6;"></div>

    </div><!-- /modal-body -->
  </div><!-- /modal-content -->
</div><!-- /modal-overlay #sheetsSyncModal -->

<!-- ═══ Modal: Asignar Producto Agrupado ═══ -->
<div class="modal-overlay" id="assignGroupedModal">
  <div class="modal-content" style="max-width:700px;">
    <div class="modal-header">
      <h3><i class="ph ph-users-three" style="color:#8b5cf6;"></i> Asignar Producto Agrupado: <span id="assignGroupedTitle"></span></h3>
      <button class="modal-close" onclick="document.getElementById('assignGroupedModal').classList.remove('active')">&times;</button>
    </div>
    <div class="modal-body">
      <div style="display:flex;gap:12px;margin-bottom:16px;flex-wrap:wrap;">
        <div style="flex:1;min-width:200px;">
          <label class="form-label">Usuario</label>
          <select class="form-select" id="assignGroupedUser"></select>
        </div>
        <div style="display:flex;align-items:flex-end;gap:8px;">
          <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:0.85rem;padding:8px 12px;border:1px solid var(--border-color);border-radius:8px;">
            <input type="checkbox" id="assignGroupedEpp"> Es EPP
          </label>
        </div>
      </div>
      <div class="table-responsive" style="border:1px solid var(--border-color);border-radius:10px;max-height:350px;overflow-y:auto;">
        <table class="inv-table" style="margin:0;">
          <thead id="assignGroupedHead"></thead>
          <tbody id="assignGroupedBody"></tbody>
        </table>
      </div>
    </div>
    <div class="modal-footer" style="display:flex;justify-content:flex-end;gap:8px;padding:12px 20px;border-top:1px solid var(--border-color);">
      <button class="btn btn-secondary" onclick="document.getElementById('assignGroupedModal').classList.remove('active')">Cancelar</button>
      <button class="btn btn-primary" id="btnSubmitGroupedAssign" onclick="submitGroupedAssignment()"><i class="ph ph-check-circle"></i> Asignar Selección</button>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════ -->
<!-- History Modal -->
<!-- ══════════════════════════════════════════════════════ -->
<div class="inv-modal" id="historyModal">
  <div class="inv-modal-content" style="max-width:900px; width:95%;">
    <div class="modal-header" style="display:flex;justify-content:space-between;align-items:center;padding:16px 20px;border-bottom:1px solid var(--border-color);">
      <h3 style="margin:0;font-size:1.1rem;font-weight:700;display:flex;align-items:center;gap:8px;color:var(--text-color);">
        <i class="ph ph-clock-counter-clockwise" style="color:#6366f1;"></i> Historial de Inventario
      </h3>
      <button class="modal-close-btn" onclick="document.getElementById('historyModal').classList.remove('active')">
        <i class="ph ph-x"></i>
      </button>
    </div>
    
    <!-- Tabs -->
    <div style="display:flex;gap:0;border-bottom:1px solid var(--border-color);padding:0 20px;">
      <button class="inv-history-tab active" data-htab="assignments" onclick="switchHistoryTab('assignments')">
        <i class="ph ph-users"></i> Historial de Asignaciones
      </button>
      <button class="inv-history-tab" data-htab="stock" onclick="switchHistoryTab('stock')">
        <i class="ph ph-package"></i> Historial de Stock
      </button>
    </div>
    
    <!-- Tab: Assignments -->
    <div class="inv-history-pane active" id="htab-assignments">
      <div style="display:flex;gap:10px;flex-wrap:wrap;padding:16px 20px;background:var(--bg-color);border-bottom:1px solid var(--border-color);align-items:flex-end;">
        <div style="flex:1;min-width:160px;">
          <label style="font-size:0.75rem;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px;">SKU / Producto</label>
          <input type="text" class="form-control" id="histFilterSku" placeholder="Buscar por SKU o producto..." style="height:34px;font-size:0.85rem;">
        </div>
        <div style="flex:1.5;min-width:200px;">
          <label style="font-size:0.75rem;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px;">Usuario Asignado</label>
          <select class="form-select" id="histFilterUser" style="height:34px;font-size:0.85rem;text-overflow:ellipsis;">
            <option value="">Todos</option>
          </select>
        </div>
        <div style="flex:1;min-width:140px;">
          <label style="font-size:0.75rem;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px;">Desde</label>
          <input type="date" class="form-control" id="histFilterDateFrom" style="height:34px;font-size:0.85rem;">
        </div>
        <button class="btn btn-primary btn-sm" onclick="loadAssignmentHistory()" style="height:34px;padding:0 16px;">
          <i class="ph ph-magnifying-glass"></i> Filtrar
        </button>
      </div>
      <div style="max-height:400px;overflow-y:auto;padding:0;">
        <table class="inv-table" style="margin:0;">
          <thead>
            <tr>
              <th>Fecha</th>
              <th>SKU</th>
              <th>Producto</th>
              <th>Usuario</th>
              <th>Acción</th>
              <th>Asignado por</th>
              <th style="width:90px;">Acciones</th>
            </tr>
          </thead>
          <tbody id="histAssignBody">
            <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text-muted);"><i class="ph ph-clock" style="font-size:2rem;display:block;margin-bottom:8px;opacity:0.3;"></i>Haz clic en Filtrar para cargar el historial</td></tr>
          </tbody>
        </table>
      </div>
    </div>
    
    <!-- Tab: Stock -->
    <div class="inv-history-pane" id="htab-stock">
      <div style="display:flex;gap:10px;flex-wrap:wrap;padding:16px 20px;background:var(--bg-color);border-bottom:1px solid var(--border-color);align-items:flex-end;">
        <div style="flex:1;min-width:160px;">
          <label style="font-size:0.75rem;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px;">Producto</label>
          <select class="form-select" id="histStockProduct" style="height:34px;font-size:0.85rem;">
            <option value="">Todos</option>
          </select>
        </div>
        <div style="flex:1;min-width:140px;">
          <label style="font-size:0.75rem;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px;">Desde</label>
          <input type="date" class="form-control" id="histStockDateFrom" style="height:34px;font-size:0.85rem;">
        </div>
        <button class="btn btn-primary btn-sm" onclick="loadStockLog()" style="height:34px;padding:0 16px;">
          <i class="ph ph-magnifying-glass"></i> Filtrar
        </button>
      </div>
      <div style="max-height:400px;overflow-y:auto;padding:0;">
        <table class="inv-table" style="margin:0;">
          <thead>
            <tr>
              <th>Fecha / Hora</th>
              <th>Producto</th>
              <th>Cantidad</th>
              <th>SKUs Generados</th>
              <th>Usuario</th>
              <th>Notas</th>
              <th style="width:80px;">Acciones</th>
            </tr>
          </thead>
          <tbody id="histStockBody">
            <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text-muted);"><i class="ph ph-clock" style="font-size:2rem;display:block;margin-bottom:8px;opacity:0.3;"></i>Haz clic en Filtrar para cargar el historial</td></tr>
          </tbody>
        </table>
      </div>
    </div>
    
  </div>
</div>

<!-- Modal Escáner QR para Campos Custom -->
<div class="modal-overlay" id="scanPickerModal" style="display:none; align-items:center; justify-content:center; z-index:9999;">
  <div class="modal-content" style="max-width:400px; width:90%; padding:20px; border-radius:16px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
      <h3 style="margin:0; font-size:1.1rem; display:flex; align-items:center; gap:8px;"><i class="ph ph-qr-code" style="color:var(--primary-color);"></i> Escanear Código</h3>
      <button class="btn-icon" onclick="if(window.stopScanPicker) window.stopScanPicker(); else { document.getElementById('scanPickerModal').classList.remove('active'); document.getElementById('scanPickerModal').style.display='none'; }"><i class="ph ph-x"></i></button>
    </div>
    
    <div style="background:#000; border-radius:12px; overflow:hidden; position:relative; aspect-ratio:1; margin-bottom:15px; display:flex; flex-direction:column; justify-content:center; align-items:center;">
      <div id="scanPickerReader" style="width:100%; height:100%;"></div>
      <div id="scanPickerStatus" style="position:absolute; bottom:10px; left:10px; right:10px; background:rgba(0,0,0,0.7); color:#fff; padding:6px 10px; border-radius:8px; font-size:0.85rem; text-align:center; z-index:10;"><i class="ph ph-camera"></i> Iniciando cámara...</div>
    </div>
    
    <div style="display:flex; gap:8px; margin-bottom:15px;">
      <input type="text" id="scanPickerManual" class="form-control" placeholder="Ingreso manual..." autocomplete="off" style="flex:1;">
      <button class="btn-primary" style="padding:0 15px;" onclick="if(typeof scanPickerCallback === 'function' && document.getElementById('scanPickerManual').value.trim()) { scanPickerCallback(document.getElementById('scanPickerManual').value.trim()); if(window.stopScanPicker) window.stopScanPicker(); }">OK</button>
    </div>
    
    <div id="scanPickerResults" style="display:none; max-height:150px; overflow-y:auto; border:1px solid var(--border-color); border-radius:8px;">
      <div id="scanPickerList" style="display:flex; flex-direction:column;"></div>
    </div>
  </div>
</div>

<script src="<?php echo BASE_URL; ?>/modules/inventario/inventario_v2.js?v=<?php echo time(); ?>"></script>

<script>
// Move modals to body root to escape any stacking context / overflow clipping
document.addEventListener('DOMContentLoaded', function() {
    var lb = document.getElementById('invLightbox');
    if (lb && lb.parentElement !== document.body) {
        document.body.appendChild(lb);
        lb.style.display = 'none';
    }
    var hm = document.getElementById('historyModal');
    if (hm && hm.parentElement !== document.body) {
        document.body.appendChild(hm);
    }
});
</script>

<?php include '../../includes/footer.php'; ?>

