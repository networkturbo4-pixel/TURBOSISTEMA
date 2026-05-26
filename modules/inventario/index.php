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
    </div>
    <!-- History button -->
    <button class="inv-tab" style="margin-left:8px; background:rgba(99,102,241,0.1); color:#6366f1; border:1px solid rgba(99,102,241,0.2);" onclick="openHistoryModal()" title="Historial de inventario">
        <i class="ph ph-clock-counter-clockwise"></i> Historial
    </button>
    <!-- Filtros + Sheets (derecha) -->
    <div class="inv-toolbar-right">
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
                <button class="btn btn-danger btn-sm" onclick="bulkDeleteProducts()"><i class="ph ph-trash"></i> Eliminar</button>
            </div>
        </div>
        <div style="font-size:0.8rem; color:var(--text-muted);">
            <span id="prodSelectedCount">0</span> seleccionados
        </div>
    </div>

    <div class="table-responsive" id="productsTableWrap" style="display:none; height: calc(100vh - 420px); overflow: auto; border-bottom: 1px solid var(--border-color);">
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

    <div class="table-responsive" style="height: calc(100vh - 430px); overflow: auto; border-bottom: 1px solid var(--border-color);">
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

<!-- Tab: Etiquetas -->
<div class="inv-tab-pane" id="tab-etiquetas">
    <div class="inv-filter-row">
        <select class="form-select inv-filter-select" id="labelProduct" style="flex:1;max-width:300px;"><option value="">Seleccionar producto...</option></select>
        <select class="form-select inv-filter-select" id="labelType">
            <option value="barcode">Código de Barras</option>
            <option value="qr">Código QR</option>
        </select>
        <button class="btn btn-primary" id="btnGenLabels"><i class="ph ph-printer"></i> Generar</button>
        <button class="btn btn-secondary" id="btnPrint" style="display:none;" onclick="window.print()"><i class="ph ph-printer"></i> Imprimir</button>
    </div>
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
            <input type="text" id="scannerInput" class="form-control" placeholder="Buscar por SKU o nombre..." autofocus>
            <button type="button" class="btn-scan-camera" id="btnScanCamera" title="Escanear con cámara"><i class="ph ph-camera"></i></button>
        </div>
    </div>

    <!-- Scanner Result -->
    <div id="scannerResult" class="scanner-result" style="display:none;"></div>
</div>

<!-- FAB -->
<button class="fab" id="btnNewProduct" title="Nuevo Producto"><i class="ph ph-plus"></i></button>

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
    <div class="modal-content" style="max-width:900px; width:95%;">
        <div class="modal-header">
            <h3><i class="ph ph-package"></i> Nuevo Producto <span id="productTypeBadge" class="product-type-header-badge"></span></h3>
            <button class="close-modal" onclick="closeProductModal()">&times;</button>
        </div>
        <!-- Tab Bar -->
        <div class="inv-tabs-bar" style="margin-bottom:0;border-radius:0;border:none;border-bottom:1px solid var(--border-color);">
            <button class="inv-tab active" data-nptab="datos" onclick="switchNewProductTab('datos')"><i class="ph ph-list-dashes"></i> Datos</button>
            <button class="inv-tab" data-nptab="fotos" onclick="switchNewProductTab('fotos')"><i class="ph ph-camera"></i> Fotos</button>
        </div>
        <div class="modal-body" style="padding:0;">
            <!-- ═══ Tab: Datos ═══ -->
            <div class="np-pane active" id="nptab-datos" style="padding:20px;">

                <!-- Two-column layout -->
                <div id="newProductGrid" style="display:grid; grid-template-columns:1fr 1fr; gap:24px; align-items:start;">

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

                        <!-- Descripción -->
                        <div class="inv-form-field" style="margin-bottom:14px;">
                            <label class="form-label">Descripción</label>
                            <input type="text" class="form-control" id="prodDesc" placeholder="Descripción breve del producto...">
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
                    </div><!-- end left col -->

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

                </div><!-- end grid -->
            </div>

            <!-- ═══ Tab: Fotos ═══ -->
            <div class="np-pane" id="nptab-fotos" style="padding:20px;">
                <!-- Two-column layout -->
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px; align-items:start;">

                    <!-- ── Columna Izquierda: Foto principal del producto ── -->
                    <div>
                        <label class="form-label" style="font-weight:700; display:flex; align-items:center; gap:6px; margin-bottom:10px;">
                            <i class="ph ph-images" style="color:#8b5cf6; font-size:1.1rem;"></i> Foto Principal del Producto
                        </label>
                        <div style="display:flex; gap:8px; margin-bottom:12px;">
                            <button type="button" class="btn btn-secondary" onclick="document.getElementById('prodMultiPhotoInput').click()" style="flex:1;"><i class="ph ph-upload-simple"></i> Subir fotos</button>
                            <button type="button" class="btn btn-secondary" onclick="captureProductPhoto('create')" style="flex:1;"><i class="ph ph-camera"></i> Tomar foto</button>
                        </div>
                        <div class="prod-photo-dropzone" id="prodPhotoDropzone"
                             onclick="document.getElementById('prodMultiPhotoInput').click()"
                             ondrop="handleProductPhotoDrop(event, 'create')"
                             ondragover="event.preventDefault(); this.classList.add('dragover')"
                             ondragleave="this.classList.remove('dragover')">
                            <i class="ph ph-cloud-arrow-up" style="font-size:2.5rem; color:var(--text-muted); opacity:0.4;"></i>
                            <p style="margin:4px 0 0; font-size:0.85rem; color:var(--text-muted);">Arrastra fotos aquí o <strong style="color:var(--primary-color);">haz clic</strong></p>
                            <span style="font-size:0.75rem; color:var(--text-muted);">JPG, PNG o WebP</span>
                        </div>
                        <input type="file" id="prodMultiPhotoInput" class="no-dropzone" multiple accept="image/*" style="position:absolute; left:-9999px; width:1px; height:1px;" onchange="handleProductPhotoSelect(event, 'create')">
                        <input type="file" id="prodPhotoInput" class="no-dropzone" accept="image/*" style="position:absolute; left:-9999px; width:1px; height:1px;" onchange="handleProductPhotoSelect(event, 'create')">
                        <div id="prodPhotoGallery" class="prod-photo-gallery" style="margin-top:12px;"></div>
                    </div>

                    <!-- ── Columna Derecha: Fotos individuales por SKU ── -->
                    <div>
                        <label class="form-label" style="font-weight:700; display:flex; align-items:center; gap:6px; margin-bottom:10px;">
                            <i class="ph ph-identification-badge" style="color:#8b5cf6; font-size:1.1rem;"></i> Fotos por SKU
                        </label>
                        <div style="background:linear-gradient(135deg, rgba(139,92,246,0.07), rgba(99,102,241,0.04)); padding:20px; border-radius:14px; border:1px solid rgba(139,92,246,0.25);">
                            <div class="form-check" style="margin-bottom:0;">
                                <input class="form-check-input" type="checkbox" id="prodRequiresPhotos" style="border-color:#8b5cf6; width:18px; height:18px;">
                                <label class="form-check-label" for="prodRequiresPhotos" style="font-weight:700; font-size:0.95rem; padding-left:4px;">
                                    <i class="ph ph-camera" style="color:#8b5cf6; margin-right:4px;"></i> Fotos individuales por SKU
                                </label>
                            </div>
                            <small style="color:var(--text-muted);display:block;margin-top:10px;line-height:1.6;">Al activar, cada SKU tendrá su propia galería de fotos en el módulo Mochila.</small>
                        </div>
                    </div>

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
                <div class="inv-form-field" style="margin-top:16px;">
                    <label class="form-label">Cantidad a añadir (SKUs automáticos)</label>
                    <input type="number" class="form-control" id="addStockQuantity" min="1" value="1">
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
<div id="invLightbox" onclick="if(event.target===this)closeLightbox()">
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
            </tr>
          </thead>
          <tbody id="histAssignBody">
            <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--text-muted);"><i class="ph ph-clock" style="font-size:2rem;display:block;margin-bottom:8px;opacity:0.3;"></i>Haz clic en Filtrar para cargar el historial</td></tr>
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

<script src="<?php echo BASE_URL; ?>/modules/inventario/inventario_v2.js?v=<?php echo time(); ?>"></script>

<?php include '../../includes/footer.php'; ?>
