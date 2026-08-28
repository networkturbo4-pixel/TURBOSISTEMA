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
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/modules/inventario/dashboard/dashboard.css?v=<?php echo time(); ?>">

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





<!-- Card contenedor unificado -->
<div class="inv-content-card">

<!-- Unified Toolbar: Tabs + Filters -->
<?php $activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'productos'; ?>
<div class="inv-toolbar">
    <div class="inv-toolbar-tabs">
        <button class="inv-tab <?php echo $activeTab === 'productos' ? 'active' : ''; ?>" data-tab="productos"><i class="ph ph-package"></i> <span>Productos</span></button>
        <button class="inv-tab <?php echo $activeTab === 'dashboard' ? 'active' : ''; ?>" data-tab="dashboard"><i class="ph ph-chart-pie-slice"></i> <span>Dashboard</span></button>
        <button class="inv-tab <?php echo $activeTab === 'stock' ? 'active' : ''; ?>" data-tab="stock"><i class="ph ph-chart-bar"></i> <span>Control de Stock</span></button>
        <button class="inv-tab <?php echo $activeTab === 'etiquetas' ? 'active' : ''; ?>" data-tab="etiquetas"><i class="ph ph-tag"></i> <span>Etiquetas</span></button>
        <button class="inv-tab <?php echo $activeTab === 'escaner' ? 'active' : ''; ?>" data-tab="escaner"><i class="ph ph-barcode"></i> <span>Escáner</span></button>
        <button class="inv-tab <?php echo $activeTab === 'papelera' ? 'active' : ''; ?>" data-tab="papelera"><i class="ph ph-trash"></i> <span>Papelera</span></button>
    </div>
    <!-- History button -->
    <a id="btnHistorial" href="<?php echo BASE_URL; ?>/modules/inventario/historial" class="inv-btn-history" style="text-decoration:none;" title="Historial y trazabilidad de inventario">
        <i class="ph ph-clock-counter-clockwise"></i> <span>Historial</span>
    </a>
    <!-- Filtros + Sheets (derecha) -->
    <div class="inv-toolbar-right" style="<?php echo $activeTab === 'productos' ? 'display:flex;' : 'display:none;'; ?>">
        <!-- New Product Button -->
        <button id="btnNewProduct" class="btn-new-product-cta" title="Crear un nuevo producto">
            <i class="ph ph-plus-circle"></i> <span>Nuevo Producto</span>
        </button>
        
        <div class="inv-filter-search" id="toolbarSearch">
            <i class="ph ph-magnifying-glass"></i>
            <input type="text" class="form-control" id="searchProducts" placeholder="Buscar por nombre, SKU, marca..." autocomplete="off">
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
            <span>Google Sheets</span>
            <span class="sheets-status-dot" id="sheetsDot"></span>
        </button>
    </div>
</div>

<!-- Tab: Productos -->
<div class="inv-tab-pane <?php echo $activeTab === 'productos' ? 'active' : ''; ?>" id="tab-productos">
    <!-- Active filters bar -->
    <div id="cfActiveBar" style="display:none; gap:8px; flex-wrap:wrap; align-items:center; padding:8px 16px; background:var(--bg-color); border-bottom:1px solid var(--border-color); animation:fadeIn 0.2s ease;">
        <span style="font-size:0.8rem; color:var(--text-muted); font-weight:600;"><i class="ph ph-funnel"></i> Filtros activos:</span>
        <div id="cfActiveTags" style="display:flex; gap:6px; flex-wrap:wrap;"></div>
        <button onclick="ColFilter.clearAll()" style="margin-left:auto; background:transparent; border:1px solid var(--border-color); border-radius:8px; padding:4px 10px; font-size:0.78rem; color:var(--text-muted); cursor:pointer; display:flex; align-items:center; gap:4px;"><i class="ph ph-x"></i> Limpiar todo</button>
    </div>

    <!-- Product Subbar / Selection Toolbar -->
    <div class="inv-tab-subbar">
        <div class="inv-tab-subbar-left">
            <div id="prodActiveActions" class="inv-bulk-actions-group" style="display:none;">
                <button class="btn btn-secondary btn-sm" onclick="exportSelectedProductsToExcel()"><i class="ph ph-file-csv" style="color:#10b981;"></i> Descargar Excel</button>
                <button class="btn btn-secondary btn-sm" onclick="clearProductSelection()"><i class="ph ph-x"></i> Cancelar</button>
                <button class="btn btn-danger btn-sm" onclick="bulkDeleteProducts()"><i class="ph ph-trash"></i> Eliminar</button>
            </div>
        </div>
        <div class="inv-tab-subbar-right">
            <span class="inv-selection-pill" id="prodSelectedCountWrap"><strong id="prodSelectedCount">0</strong> seleccionados</span>
        </div>
    </div>

    <div class="table-responsive inv-table-scroll-wrap" id="productsTableWrap" style="display:none;">
        <table class="inv-table inv-table-modern">
            <thead>
                <tr>
                    <th style="width:44px; text-align:center;"><input type="checkbox" class="form-check-input" id="prodCheckAll" onchange="toggleAllProducts(this)"></th>
                    <th class="cf-th" data-col="nombre" style="min-width:240px;">
                        <span>Producto</span>
                        <button class="cf-btn" onclick="ColFilter.open('nombre', this)" title="Filtrar"><i class="ph ph-funnel-simple"></i></button>
                    </th>
                    <th class="cf-th" data-col="categoria" style="min-width:130px;">
                        <span>Categoría</span>
                        <button class="cf-btn" onclick="ColFilter.open('categoria', this)" title="Filtrar"><i class="ph ph-funnel-simple"></i></button>
                    </th>
                    <th class="cf-th text-end" data-col="costo" style="min-width:110px;">
                        <span>Costo Ref.</span>
                        <button class="cf-btn" onclick="ColFilter.open('costo', this)" title="Filtrar"><i class="ph ph-funnel-simple"></i></button>
                    </th>
                    <th class="cf-th text-center" data-col="total" style="min-width:90px;">
                        <span>Total</span>
                        <button class="cf-btn" onclick="ColFilter.open('total', this)" title="Filtrar"><i class="ph ph-funnel-simple"></i></button>
                    </th>
                    <th class="cf-th text-center" data-col="disponibles" style="min-width:105px;">
                        <span>Disponibles</span>
                        <button class="cf-btn" onclick="ColFilter.open('disponibles', this)" title="Filtrar"><i class="ph ph-funnel-simple"></i></button>
                    </th>
                    <th class="cf-th text-center" data-col="instalados" style="min-width:100px;">
                        <span>Instalados</span>
                        <button class="cf-btn" onclick="ColFilter.open('instalados', this)" title="Filtrar"><i class="ph ph-funnel-simple"></i></button>
                    </th>
                    <th class="cf-th text-center" data-col="malogrados" style="min-width:105px;">
                        <span>Malogrados</span>
                        <button class="cf-btn" onclick="ColFilter.open('malogrados', this)" title="Filtrar"><i class="ph ph-funnel-simple"></i></button>
                    </th>
                    <th class="cf-th text-center" data-col="observados" style="min-width:105px;">
                        <span>Observados</span>
                        <button class="cf-btn" onclick="ColFilter.open('observados', this)" title="Filtrar"><i class="ph ph-funnel-simple"></i></button>
                    </th>
                    <th class="text-center" style="width:160px; min-width:160px;">Acciones</th>
                </tr>
            </thead>
            <tbody id="productsGrid"></tbody>
        </table>
    </div>

    <!-- Table Footer / Pagination -->
    <div id="productsPagination" class="inv-table-footer" style="display:none;">
        <div class="inv-tf-left">
            <span class="inv-tf-text">Mostrar</span>
            <select id="prodPerPage" class="form-select inv-select-page-size">
                <option value="10">10</option>
                <option value="25" selected>25</option>
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="200">200</option>
            </select>
            <span class="inv-tf-text">por página</span>
        </div>
        <div class="inv-tf-center" id="prodPageInfo">Mostrando 0 - 0 de 0</div>
        <div class="inv-tf-right" id="prodPaginationControls">
            <button class="inv-page-nav-btn" id="btnProdPrev" title="Página anterior"><i class="ph ph-caret-left"></i></button>
            <button class="inv-page-nav-btn" id="btnProdNext" title="Página siguiente"><i class="ph ph-caret-right"></i></button>
        </div>
    </div>
    <div id="productsEmpty" class="empty-state" style="display:none;"><i class="ph ph-package" style="font-size:3rem;display:block;margin-bottom:12px;"></i>No hay productos registrados.</div>
</div>

<!-- Tab: Control de Stock -->
<div class="inv-tab-pane" id="tab-stock">
    <input type="hidden" id="searchSku" value="">
    
    <div class="inv-tab-subbar">
        <div class="inv-tab-subbar-left">
            <div id="skuActiveActions" class="inv-bulk-actions-group" style="display:none;">
                <button class="btn btn-secondary btn-sm" onclick="exportSkusToExcel()" title="Descargar en Excel"><i class="ph ph-file-xls" style="color:#10b981;"></i> Descargar Excel</button>
                <button class="btn btn-secondary btn-sm" onclick="bulkChangeSkuStatus()"><i class="ph ph-tag"></i> Cambiar Estado</button>
                <button class="btn btn-danger btn-sm" onclick="bulkDeleteSkus()"><i class="ph ph-trash"></i> Eliminar</button>
            </div>
        </div>
        <div class="inv-tab-subbar-right">
            <span class="inv-selection-pill" id="skuSelectedCountWrap"><strong id="skuSelectedCount">0</strong> seleccionados</span>
        </div>
    </div>

    <!-- Active filters bar for stock -->
    <div id="skuActiveBar" style="display:none; gap:8px; flex-wrap:wrap; align-items:center; padding:8px 16px; background:var(--bg-color); border-bottom: 1px solid var(--border-color); animation:fadeIn 0.2s ease;">
        <span style="font-size:0.8rem; color:var(--text-muted); font-weight:600;"><i class="ph ph-funnel"></i> Filtros activos:</span>
        <div id="skuActiveTags" style="display:flex; gap:6px; flex-wrap:wrap;"></div>
        <button onclick="SkuColFilter.clearAll()" style="margin-left:auto; background:transparent; border:1px solid var(--border-color); border-radius:8px; padding:4px 10px; font-size:0.78rem; color:var(--text-muted); cursor:pointer; display:flex; align-items:center; gap:4px;"><i class="ph ph-x"></i> Limpiar todo</button>
    </div>

    <div class="table-responsive inv-table-scroll-wrap" id="skuTableWrap">
        <table id="skuTable" class="inv-table inv-table-modern">
            <thead>
                <tr>
                    <th class="sticky-col sticky-col-0 sticky-th" style="width:44px;text-align:center;vertical-align:middle;" data-colname="#">
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
    <!-- Stock Table Footer / Pagination -->
    <div id="skuPagination" class="inv-table-footer">
        <div class="inv-tf-left">
            <span class="inv-tf-text">Mostrar</span>
            <select id="skuPerPage" class="form-select inv-select-page-size">
                <option value="10">10</option>
                <option value="25" selected>25</option>
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="500">500</option>
            </select>
            <span class="inv-tf-text">por página</span>
        </div>
        <div class="inv-tf-center" id="skuPageInfo">Mostrando 0 - 0 de 0</div>
        <div class="inv-tf-right" id="skuPaginationControls">
            <button class="inv-page-nav-btn" id="btnSkuPrev" title="Página anterior"><i class="ph ph-caret-left"></i></button>
            <button class="inv-page-nav-btn" id="btnSkuNext" title="Página siguiente"><i class="ph ph-caret-right"></i></button>
        </div>
    </div>
    <div id="skuEmpty" class="empty-state" style="display:none;">No hay SKUs registrados.</div>
</div>

<!-- Tab: Etiquetas (Impresora Térmica 2 Columnas) -->
<div class="inv-tab-pane" id="tab-etiquetas" style="padding: 16px 20px 40px; position:relative; overflow-y: auto;">
    
    <div class="labels-split-layout">
        <!-- ── Left Column: Configuration & Controls ── -->
        <div class="labels-config-panel">
            <div class="labels-card">
                <div class="labels-card-header">
                    <div class="labels-header-icon"><i class="ph ph-tag"></i></div>
                    <div>
                        <h3 class="labels-header-title">Etiquetas Adhesivas</h3>
                        <p class="labels-header-sub">Impresión térmica de códigos de barras / QR</p>
                    </div>
                </div>

                <div class="labels-card-body">
                    <!-- Segmented Mode Tabs: Lote vs Reimpresión -->
                    <div class="labels-mode-tabs">
                        <button type="button" class="lbl-mode-tab active" id="lblTabModeBatch" onclick="setLabelPrintSourceMode('batch', this)">
                            <i class="ph ph-package"></i> Por Producto / Lote
                        </button>
                        <button type="button" class="lbl-mode-tab" id="lblTabModeReprint" onclick="setLabelPrintSourceMode('reprint', this)">
                            <i class="ph ph-arrow-counter-clockwise"></i> Reimpresión de SKU
                        </button>
                    </div>

                    <!-- ── MODO 1: Por Producto / Lote ── -->
                    <div id="lblSourceModeBatchWrap">
                        <!-- Form Group: Product & Filter -->
                        <div class="labels-form-group">
                            <label class="labels-label"><i class="ph ph-package"></i> Producto</label>
                            <select class="form-select" id="labelProduct">
                                <option value="">Seleccionar producto...</option>
                            </select>
                        </div>

                        <div class="labels-grid-2">
                            <div class="labels-form-group">
                                <label class="labels-label"><i class="ph ph-funnel"></i> Filtro de SKUs</label>
                                <select class="form-select" id="labelPrintStatus">
                                    <option value="">Todos los SKUs</option>
                                    <option value="0">Solo NO Impresos</option>
                                    <option value="1">Solo ya Impresos</option>
                                </select>
                            </div>
                            <div class="labels-form-group">
                                <label class="labels-label"><i class="ph ph-qr-code"></i> Tipo de Código</label>
                                <select class="form-select" id="labelType">
                                    <option value="barcode">Código de Barras (128)</option>
                                    <option value="qr">Código QR</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- ── MODO 2: Reimpresión de SKU Específico ── -->
                    <div id="lblSourceModeReprintWrap" style="display:none;">
                        <div class="labels-form-group">
                            <label class="labels-label" style="color:#8b5cf6;"><i class="ph ph-magnifying-glass"></i> Buscar SKU / Escanear Etiqueta Dañada</label>
                            <div class="reprint-search-input-wrap">
                                <i class="ph ph-barcode"></i>
                                <input type="text" class="form-control" id="reprintSkuInput" placeholder="Digita o escanea un SKU (ej: TRB-62M4MQ)..." autocomplete="off">
                                <button type="button" class="btn btn-sm btn-primary" id="btnSearchAddReprint" onclick="quickAddReprintInput()"><i class="ph ph-plus-circle"></i> Agregar</button>
                            </div>
                            <!-- Autocomplete Dropdown List -->
                            <div id="reprintSearchResults" class="reprint-results-dropdown" style="display:none;"></div>
                        </div>

                        <!-- Reprint Queue Box -->
                        <div class="reprint-queue-box">
                            <div class="reprint-queue-header">
                                <span><i class="ph ph-stack"></i> Cola de Reimpresión (<strong id="reprintCount">0</strong>)</span>
                                <button type="button" class="reprint-clear-btn" onclick="clearReprintQueue()"><i class="ph ph-trash"></i> Limpiar</button>
                            </div>
                            <div id="reprintQueueList" class="reprint-queue-list">
                                <div class="reprint-empty-hint">
                                    <i class="ph ph-qr-code"></i>
                                    <span>Escanea o busca arriba los SKUs que deseas reimprimir.</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Dimensions Box -->
                    <div class="labels-dim-box">
                        <div class="labels-dim-header">
                            <i class="ph ph-ruler"></i>
                            <span>Dimensiones & Disposición</span>
                        </div>
                        <div class="labels-grid-4">
                            <div>
                                <label class="labels-sublabel">Ancho (mm)</label>
                                <input type="number" class="form-control form-control-sm" id="labelWidth" value="50" min="20" max="100">
                            </div>
                            <div>
                                <label class="labels-sublabel">Alto (mm)</label>
                                <input type="number" class="form-control form-control-sm" id="labelHeight" value="30" min="15" max="80">
                            </div>
                            <div>
                                <label class="labels-sublabel">Columnas</label>
                                <input type="number" class="form-control form-control-sm" id="labelCols" value="2" min="1" max="5">
                            </div>
                            <div>
                                <label class="labels-sublabel">Copias c/u</label>
                                <input type="number" class="form-control form-control-sm" id="labelCopies" value="1" min="1" max="100">
                            </div>
                        </div>
                    </div>

                    <!-- Filtro por Fecha de Registro SKU -->
                    <div class="labels-date-box">
                        <div class="labels-dim-header" style="color:#06b6d4;">
                            <i class="ph ph-calendar"></i>
                            <span>Filtro por Fecha de Registro</span>
                        </div>
                        <div class="labels-date-chips-bar">
                            <button type="button" class="lbl-date-chip active" data-mode="all" onclick="setLabelDateMode('all', this)"><i class="ph ph-infinity"></i> Todas</button>
                            <button type="button" class="lbl-date-chip" data-mode="today" onclick="setLabelDateMode('today', this)"><i class="ph ph-sun"></i> Hoy</button>
                            <button type="button" class="lbl-date-chip" data-mode="single" onclick="setLabelDateMode('single', this)"><i class="ph ph-calendar-blank"></i> Día Exacto</button>
                            <button type="button" class="lbl-date-chip" data-mode="range" onclick="setLabelDateMode('range', this)"><i class="ph ph-calendar-range"></i> Rango</button>
                        </div>
                        <div id="lblDateSingleWrap" style="display:none; margin-top:8px;">
                            <input type="date" class="form-control form-control-sm" id="labelSingleDate" style="background:var(--surface-color);">
                        </div>
                        <div id="lblDateRangeWrap" style="display:none; margin-top:8px;" class="labels-grid-2">
                            <div>
                                <label class="labels-sublabel">Desde</label>
                                <input type="date" class="form-control form-control-sm" id="labelStartDate" style="background:var(--surface-color);">
                            </div>
                            <div>
                                <label class="labels-sublabel">Hasta</label>
                                <input type="date" class="form-control form-control-sm" id="labelEndDate" style="background:var(--surface-color);">
                            </div>
                        </div>
                    </div>

                    <!-- Switches Modernos: Elementos Visibles en la Etiqueta -->
                    <div class="labels-switches-box">
                        <div class="labels-dim-header" style="color:#f59e0b;">
                            <i class="ph ph-sliders"></i>
                            <span>Elementos Visibles en la Etiqueta</span>
                        </div>
                        <div class="labels-switches-grid">
                            <label class="ms-toggle-row">
                                <span class="ms-label"><i class="ph ph-buildings"></i> Logo empresa</span>
                                <input type="checkbox" class="ms-input" id="labelShowLogo">
                                <span class="ms-track"><span class="ms-thumb"></span></span>
                            </label>
                            <label class="ms-toggle-row">
                                <span class="ms-label"><i class="ph ph-text-t"></i> Nombre empresa</span>
                                <input type="checkbox" class="ms-input" id="labelShowCompanyName">
                                <span class="ms-track"><span class="ms-thumb"></span></span>
                            </label>
                            <label class="ms-toggle-row">
                                <span class="ms-label"><i class="ph ph-package"></i> Nombre producto</span>
                                <input type="checkbox" class="ms-input" id="labelShowName" checked>
                                <span class="ms-track"><span class="ms-thumb"></span></span>
                            </label>
                            <label class="ms-toggle-row">
                                <span class="ms-label"><i class="ph ph-barcode"></i> Código SKU</span>
                                <input type="checkbox" class="ms-input" id="labelShowSku" checked>
                                <span class="ms-track"><span class="ms-thumb"></span></span>
                            </label>
                            <label class="ms-toggle-row" style="grid-column: 1 / -1;">
                                <span class="ms-label"><i class="ph ph-calendar-check"></i> Fecha de registro</span>
                                <input type="checkbox" class="ms-input" id="labelShowDate">
                                <span class="ms-track"><span class="ms-thumb"></span></span>
                            </label>
                        </div>

                        <!-- Campo para Subir Logo Monocromático de Etiquetas -->
                        <div class="labels-logo-box" id="labelLogoUploadWrap" style="margin-top:12px; padding:10px 12px; background:var(--surface-color); border:1px solid var(--border-color); border-radius:10px;">
                            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px;">
                                <span style="font-size:0.78rem; font-weight:700; color:var(--text-color); display:flex; align-items:center; gap:6px;">
                                    <i class="ph ph-image" style="color:#6366f1;"></i> Logo Térmico (Monocromático)
                                </span>
                                <button type="button" class="btn btn-xs btn-outline-danger" id="btnResetLabelLogo" onclick="resetCustomLabelLogo()" style="display:none; padding:2px 8px; font-size:0.72rem;" title="Restablecer logo por defecto">
                                    <i class="ph ph-arrow-counter-clockwise"></i> Restablecer
                                </button>
                            </div>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <div id="labelLogoThumbWrap" style="width:54px; height:34px; background:#ffffff; border:1px solid #cbd5e1; border-radius:6px; display:flex; align-items:center; justify-content:center; padding:2px; overflow:hidden; flex-shrink:0;">
                                    <img id="labelLogoThumb" src="" alt="Logo" style="max-width:100%; max-height:100%; object-fit:contain;">
                                </div>
                                <div style="flex:1;">
                                    <input type="file" id="labelLogoFileInput" accept="image/png, image/jpeg, image/svg+xml, image/webp" style="display:none;" onchange="handleCustomLabelLogo(this)">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" style="width:100%; height:34px; font-size:0.78rem; display:flex; align-items:center; justify-content:center; gap:6px;" onclick="document.getElementById('labelLogoFileInput').click()">
                                        <i class="ph ph-upload-simple"></i> Subir Logo Monocromático
                                    </button>
                                </div>
                            </div>
                            <small style="font-size:0.71rem; color:var(--text-muted); display:block; margin-top:6px; line-height:1.35;">
                                <i class="ph ph-info"></i> Sube un logo con texto en <strong>negro puro (#000000)</strong> para que la impresora térmica imprima todas las letras nítidas.
                            </small>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="labels-actions-stack">
                        <button class="btn btn-primary btn-gen-labels" id="btnGenLabels">
                            <i class="ph ph-barcode"></i> Generar Etiquetas
                        </button>
                        <div class="labels-actions-secondary" style="display:flex; gap:8px;">
                            <button class="btn btn-secondary" id="btnPrint" style="display:none; flex:1; height:42px; font-weight:700;" onclick="printThermalLabels()">
                                <i class="ph ph-printer"></i> Imprimir
                            </button>
                            <button class="btn" id="btnMarkPrinted" style="display:none; flex:1; height:42px; font-weight:700; background-color:#10b981; color:white; border:none; border-radius:10px;" onclick="markGeneratedAsPrinted()">
                                <i class="ph ph-check-circle"></i> Marcar Impresas
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Right Column: Live Sheet Preview ── -->
        <div class="labels-preview-panel">
            <div class="labels-preview-card">
                <div class="labels-preview-header">
                    <div class="labels-ph-left">
                        <i class="ph ph-eye"></i>
                        <span>Vista Previa de Impresión</span>
                    </div>
                    <div class="labels-ph-right" id="labelPreviewMeta">
                        <span style="font-size:0.8rem; color:var(--text-muted);"><i class="ph ph-sliders-horizontal"></i> Plantilla Térmica</span>
                    </div>
                </div>
                <div class="labels-preview-body">
                    <div id="labelPreview" class="label-preview-container">
                        <div class="labels-empty-placeholder">
                            <div class="lep-icon"><i class="ph ph-printer"></i></div>
                            <h4>Vista Previa de Etiquetas</h4>
                            <p>Selecciona un producto a la izquierda y presiona <strong>Generar Etiquetas</strong> para visualizar la plantilla térmica lista para imprimir.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tab: Escáner Inteligente -->
<div class="inv-tab-pane" id="tab-escaner" style="padding: 20px;">
    <!-- Scanner Hub Terminal Header -->
    <div class="scanner-terminal-card">
        <div class="scanner-terminal-header">
            <div class="scanner-terminal-title-box">
                <div class="scanner-terminal-icon">
                    <i class="ph ph-barcode"></i>
                    <span class="scanner-laser-beam"></span>
                </div>
                <div>
                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                        <h2 class="scanner-terminal-title">Escáner Inteligente de Inventario</h2>
                        <span class="scanner-ready-badge"><span class="scanner-ready-dot"></span> Lector Láser Listo</span>
                    </div>
                    <p class="scanner-terminal-subtitle">Escanea con lector láser, cámara o escribe el código SKU para abrir y gestionar la ficha del producto automáticamente.</p>
                </div>
            </div>
            <!-- Toggles de Configuración del Escáner -->
            <div class="scanner-terminal-toggles">
                <label class="scanner-toggle-label" title="Abre automáticamente el modal de detalle del producto al detectar un código">
                    <input type="checkbox" id="scannerAutoOpen" checked>
                    <span class="scanner-toggle-slider"></span>
                    <span class="scanner-toggle-text"><i class="ph ph-lightning" style="color:#f59e0b;"></i> Auto-abrir ficha</span>
                </label>
                <label class="scanner-toggle-label" title="Emite un sonido de confirmación al escanear">
                    <input type="checkbox" id="scannerSound" checked>
                    <span class="scanner-toggle-slider"></span>
                    <span class="scanner-toggle-text"><i class="ph ph-speaker-high" style="color:#3b82f6;"></i> Sonido Beep</span>
                </label>
            </div>
        </div>

        <!-- Terminal Input Bar -->
        <div class="scanner-terminal-input-wrap">
            <div class="scanner-input-box">
                <i class="ph ph-qr-code scanner-input-icon"></i>
                <input type="text" id="scannerInput" class="form-control scanner-terminal-input" placeholder="Escanea código de barras con lector láser, QR o escribe SKU / nombre..." autocomplete="off" autofocus>
                <button type="button" class="scanner-btn-clear" id="btnScannerClear" title="Limpiar campo" style="display:none;">
                    <i class="ph ph-x"></i>
                </button>
            </div>
            <button type="button" class="btn btn-primary scanner-btn-camera" id="btnScanCamera" title="Abrir cámara del dispositivo">
                <i class="ph ph-camera"></i> <span>Escanear con Cámara</span>
            </button>
        </div>

        <div class="scanner-terminal-tips">
            <span><i class="ph ph-lightning"></i> <strong>Lector Láser:</strong> Conecta tu lector USB/Bluetooth y escanea cualquier código; se detectará instantáneamente.</span>
            <span><i class="ph ph-keyboard"></i> <strong>Atajo:</strong> Presiona <kbd>Enter</kbd> para buscar o <kbd>Esc</kbd> para limpiar.</span>
        </div>
    </div>

    <!-- Scanner Live Result Container -->
    <div id="scannerResultContainer" class="scanner-result-container">
        <!-- Idle State Placeholder -->
        <div id="scannerIdleState" class="scanner-idle-box">
            <div class="scanner-idle-circle">
                <i class="ph ph-barcode"></i>
            </div>
            <h3 class="scanner-idle-title">Esperando Escaneo...</h3>
            <p class="scanner-idle-desc">Apunta el lector láser al código de barra/QR de un producto o SKU para consultar su información en tiempo real.</p>
        </div>

        <!-- Rich Scanned Product Card -->
        <div id="scannerResultCard" class="scanner-result-card" style="display:none;">
            <!-- Contenido dinámico inyectado por JavaScript -->
        </div>
    </div>

    <!-- Historial de Escaneos Recientes de la Sesión -->
    <div class="scanner-history-section" style="display: none;">
        <div class="scanner-history-header">
            <div class="scanner-history-title">
                <i class="ph ph-clock-counter-clockwise"></i>
                <span>Historial de Escaneos Recientes</span>
                <span id="scannerHistoryBadge" class="scanner-history-badge">0 escaneos</span>
            </div>
            <button type="button" class="btn btn-sm btn-secondary scanner-btn-clear-history" id="btnScannerClearHistory" title="Limpiar historial de escaneos">
                <i class="ph ph-trash"></i> Limpiar Historial
            </button>
        </div>
        <div class="table-responsive scanner-history-table-wrap">
            <table class="inv-table scanner-history-table">
                <thead>
                    <tr>
                        <th style="width:70px;">Hora</th>
                        <th style="width:50px;text-align:center;">Foto</th>
                        <th>Código SKU / Identificador</th>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th>Estado</th>
                        <th>Custodia / Técnico</th>
                        <th style="text-align:center;width:100px;">Acción</th>
                    </tr>
                </thead>
                <tbody id="scannerHistoryBody">
                    <tr>
                        <td colspan="8" style="text-align:center;padding:24px;color:var(--text-muted);">
                            <i class="ph ph-barcode" style="font-size:1.8rem;display:block;margin-bottom:6px;opacity:0.3;"></i>
                            Aún no se han escaneado productos en esta sesión.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Tab: Papelera -->
<div class="inv-tab-pane" id="tab-papelera" style="padding: 16px;">
    <div class="scanner-header" style="margin-top: 0; margin-bottom: 14px;">
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
    
    <div class="table-responsive inv-table-scroll-wrap" id="papeleraTableWrap">
        <table id="papeleraTable" class="inv-table inv-table-modern">
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

    <div class="inv-tab-pane <?php echo $activeTab === 'dashboard' ? 'active' : ''; ?>" id="tab-dashboard" style="padding: 16px 20px 40px; position:relative;">
        
        <!-- Dashboard Header & Modern Toolbar -->
        <div class="dash-top-toolbar">
            <!-- Left: View Mode Tabs -->
            <div class="dash-view-tabs">
                <button type="button" class="dash-view-btn d-tab-btn active" data-dtab="general">
                    <i class="ph ph-chart-pie-slice"></i> <span>Resumen General</span>
                </button>
                <button type="button" class="dash-view-btn d-tab-btn" data-dtab="financiero">
                    <i class="ph ph-currency-dollar"></i> <span>Financiero & Valoración</span>
                </button>
                <button type="button" class="dash-view-btn d-tab-btn" data-dtab="operativo">
                    <i class="ph ph-hard-hat"></i> <span>Operaciones & Rotación</span>
                </button>
            </div>
            
            <!-- Right: Date Range & Filter Bar -->
            <div class="dash-filter-bar">
                <div class="dash-quick-chips">
                    <button type="button" class="dash-chip" onclick="setDashFilter('today')">Hoy</button>
                    <button type="button" class="dash-chip" onclick="setDashFilter('week')">7 Días</button>
                    <button type="button" class="dash-chip active" onclick="setDashFilter('month')">Este Mes</button>
                    <button type="button" class="dash-chip" onclick="setDashFilter('year')">Este Año</button>
                </div>
                <form id="dashboard-filter-form" class="dash-date-form">
                    <div class="dash-date-inputs">
                        <input type="date" class="dash-date-field" id="filter_start_date" name="start_date" value="<?php echo date('Y-m-01'); ?>">
                        <span class="dash-date-sep">a</span>
                        <input type="date" class="dash-date-field" id="filter_end_date" name="end_date" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <button type="submit" class="dash-btn-apply" title="Aplicar Rango">
                        <i class="ph ph-funnel"></i>
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Tab Content 1: Resumen General -->
        <div class="d-tab-content active" id="dtab-general">
            <!-- Modern 6 KPIs Grid -->
            <div class="dash-kpi-grid">
                <!-- KPI 1: Total Productos -->
                <div class="dash-kpi-card kpi-purple">
                    <div class="dash-kpi-header">
                        <span class="dash-kpi-label">Catálogo Activo</span>
                        <div class="dash-kpi-icon"><i class="ph ph-cube"></i></div>
                    </div>
                    <div class="dash-kpi-body">
                        <h3 class="dash-kpi-value" id="metricProductos">0</h3>
                        <div class="dash-kpi-foot">
                            <span>Total de productos en catálogo</span>
                        </div>
                    </div>
                </div>

                <!-- KPI 2: Disponibles en Almacén -->
                <div class="dash-kpi-card kpi-emerald">
                    <div class="dash-kpi-header">
                        <span class="dash-kpi-label">Stock Disponible</span>
                        <div class="dash-kpi-icon"><i class="ph ph-check-circle"></i></div>
                    </div>
                    <div class="dash-kpi-body">
                        <h3 class="dash-kpi-value" id="metricDisponible">0</h3>
                        <div class="dash-kpi-foot">
                            <span class="text-emerald">En almacén central</span>
                        </div>
                    </div>
                </div>

                <!-- KPI 3: Instalados en Clientes -->
                <div class="dash-kpi-card kpi-blue">
                    <div class="dash-kpi-header">
                        <span class="dash-kpi-label">Instalados en Clientes</span>
                        <div class="dash-kpi-icon"><i class="ph ph-house-line"></i></div>
                    </div>
                    <div class="dash-kpi-body">
                        <h3 class="dash-kpi-value" id="metricInstalado">0</h3>
                        <div class="dash-kpi-foot">
                            <span class="text-blue">Equipos activos en actas</span>
                        </div>
                    </div>
                </div>

                <!-- KPI 4: En Personal / EPP -->
                <div class="dash-kpi-card kpi-amber">
                    <div class="dash-kpi-header">
                        <span class="dash-kpi-label">En Personal & EPP</span>
                        <div class="dash-kpi-icon"><i class="ph ph-hard-hat"></i></div>
                    </div>
                    <div class="dash-kpi-body">
                        <h3 class="dash-kpi-value" id="metricEnPersonal">0</h3>
                        <div class="dash-kpi-foot">
                            <span class="text-amber">En mochilas y custodio</span>
                        </div>
                    </div>
                </div>

                <!-- KPI 5: Stock Crítico / Alertas -->
                <div class="dash-kpi-card kpi-rose">
                    <div class="dash-kpi-header">
                        <span class="dash-kpi-label">Alertas de Stock</span>
                        <div class="dash-kpi-icon"><i class="ph ph-warning"></i></div>
                    </div>
                    <div class="dash-kpi-body">
                        <h3 class="dash-kpi-value" id="metricLowStock">0</h3>
                        <div class="dash-kpi-foot">
                            <span class="text-rose">Nivel mínimo o crítico</span>
                        </div>
                    </div>
                </div>

                <!-- KPI 6: Valorización Total -->
                <div class="dash-kpi-card kpi-indigo">
                    <div class="dash-kpi-header">
                        <span class="dash-kpi-label">Valorización de Stock</span>
                        <div class="dash-kpi-icon"><i class="ph ph-money"></i></div>
                    </div>
                    <div class="dash-kpi-body">
                        <h3 class="dash-kpi-value" id="metricValorTotal">S/ 0.00</h3>
                        <div class="dash-kpi-foot">
                            <span class="text-indigo">Inversión total en almacén</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modern 2x2 Charts Grid -->
            <div class="dash-charts-grid">
                <!-- Chart 1: Distribución de Estados (Donut Chart con Centro) -->
                <div class="dash-chart-card">
                    <div class="dash-chart-header">
                        <div class="dash-chart-title">
                            <i class="ph ph-chart-donut" style="color:#10b981;"></i>
                            <h4>Distribución de Estados del Inventario</h4>
                        </div>
                        <span class="dash-chart-sub">Estado de unidades físicas en tiempo real</span>
                    </div>
                    <div class="dash-chart-body" style="height: 310px; position: relative;">
                        <canvas id="chart-status"></canvas>
                        <div id="empty-state-status" class="dash-empty-state" style="display:none;">
                            <i class="ph ph-chart-donut"></i>
                            <p>Sin registros de stock para mostrar</p>
                        </div>
                    </div>
                </div>

                <!-- Chart 2: Top Alertas de Reposición (Stock Crítico) -->
                <div class="dash-chart-card">
                    <div class="dash-chart-header">
                        <div class="dash-chart-title">
                            <i class="ph ph-warning-octagon" style="color:#ef4444;"></i>
                            <h4>Alertas de Reposición (Menor Stock)</h4>
                        </div>
                        <span class="dash-chart-sub">Productos con stock cercano o inferior al mínimo</span>
                    </div>
                    <div class="dash-chart-body" style="height: 310px; position: relative;">
                        <canvas id="chart-low-stock"></canvas>
                        <div id="empty-state-low-stock" class="dash-empty-state" style="display:none;">
                            <i class="ph ph-check-circle" style="color:#10b981;"></i>
                            <p>Todo el inventario cuenta con stock óptimo</p>
                        </div>
                    </div>
                </div>

                <!-- Chart 3: Top Rotación y Asignaciones -->
                <div class="dash-chart-card">
                    <div class="dash-chart-header">
                        <div class="dash-chart-title">
                            <i class="ph ph-arrows-left-right" style="color:#8b5cf6;"></i>
                            <h4>Mayor Rotación (Top Asignaciones & Salidas)</h4>
                        </div>
                        <span class="dash-chart-sub">Productos con mayor movimiento hacia técnicos</span>
                    </div>
                    <div class="dash-chart-body" style="height: 310px; position: relative;">
                        <canvas id="chart-most-used"></canvas>
                        <div id="empty-state-most-used" class="dash-empty-state" style="display:none;">
                            <i class="ph ph-cube-transparent"></i>
                            <p>No se registraron movimientos en este período</p>
                        </div>
                    </div>
                </div>

                <!-- Chart 4: Distribución de Stock por Categoría -->
                <div class="dash-chart-card">
                    <div class="dash-chart-header">
                        <div class="dash-chart-title">
                            <i class="ph ph-folder" style="color:#f59e0b;"></i>
                            <h4>Stock por Categoría de Producto</h4>
                        </div>
                        <span class="dash-chart-sub">Volumen de existencias según rubro / familia</span>
                    </div>
                    <div class="dash-chart-body" style="height: 310px; position: relative;">
                        <canvas id="chart-category-stats"></canvas>
                        <div id="empty-state-categories" class="dash-empty-state" style="display:none;">
                            <i class="ph ph-folder"></i>
                            <p>Sin categorías registradas</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Content 2: Financiero & Valoración -->
        <div class="d-tab-content" id="dtab-financiero" style="display:none;">
            <div class="dash-kpi-grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); margin-bottom: 20px;">
                <div class="dash-kpi-card kpi-emerald">
                    <div class="dash-kpi-header">
                        <span class="dash-kpi-label">Valor Total del Inventario</span>
                        <div class="dash-kpi-icon"><i class="ph ph-currency-dollar"></i></div>
                    </div>
                    <div class="dash-kpi-body">
                        <h3 class="dash-kpi-value" id="metricValorTotalFin">S/ 0.00</h3>
                        <div class="dash-kpi-foot"><span class="text-emerald">Capital invertido en almacén</span></div>
                    </div>
                </div>

                <div class="dash-kpi-card kpi-rose">
                    <div class="dash-kpi-header">
                        <span class="dash-kpi-label">Capital en Stock Crítico</span>
                        <div class="dash-kpi-icon"><i class="ph ph-lock-key"></i></div>
                    </div>
                    <div class="dash-kpi-body">
                        <h3 class="dash-kpi-value" id="metricCapitalInmovilizado">S/ 0.00</h3>
                        <div class="dash-kpi-foot"><span class="text-rose">Comprometido en ítems con stock mínimo</span></div>
                    </div>
                </div>
            </div>

            <div class="dash-chart-card">
                <div class="dash-chart-header">
                    <div class="dash-chart-title">
                        <i class="ph ph-chart-line-up" style="color:#10b981;"></i>
                        <h4>Inversión en Compras de Inventario (Período Seleccionado)</h4>
                    </div>
                    <span class="dash-chart-sub">Montos acumulados en facturas de compras registradas</span>
                </div>
                <div class="dash-chart-body" style="height: 360px; position: relative;">
                    <canvas id="chart-purchases-evolution"></canvas>
                    <div id="empty-state-purchases" class="dash-empty-state" style="display:none;">
                        <i class="ph ph-receipt"></i>
                        <p>No hay compras registradas en este período</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Content 3: Operaciones & Rotación -->
        <div class="d-tab-content" id="dtab-operativo" style="display:none;">
            <div class="dash-charts-grid">
                <!-- Top Técnicos -->
                <div class="dash-chart-card">
                    <div class="dash-chart-header">
                        <div class="dash-chart-title">
                            <i class="ph ph-users-three" style="color:#8b5cf6;"></i>
                            <h4>Top Personal con Mayor Stock Asignado</h4>
                        </div>
                        <span class="dash-chart-sub">Técnicos con mayor volumen de equipos o EPP en custodia</span>
                    </div>
                    <div class="dash-chart-body" style="height: 320px; position: relative;">
                        <canvas id="chart-top-techs"></canvas>
                        <div id="empty-state-techs" class="dash-empty-state" style="display:none;">
                            <i class="ph ph-users"></i>
                            <p>Sin asignaciones registradas</p>
                        </div>
                    </div>
                </div>

                <!-- Devoluciones & Retornos -->
                <div class="dash-chart-card">
                    <div class="dash-chart-header">
                        <div class="dash-chart-title">
                            <i class="ph ph-arrow-u-down-left" style="color:#f59e0b;"></i>
                            <h4>Historial de Devoluciones & Retornos</h4>
                        </div>
                        <span class="dash-chart-sub">Equipos y materiales devueltos al almacén</span>
                    </div>
                    <div class="dash-chart-body" style="height: 320px; position: relative;">
                        <canvas id="chart-returns"></canvas>
                        <div id="empty-state-returns" class="dash-empty-state" style="display:none;">
                            <i class="ph ph-clock-counter-clockwise"></i>
                            <p>No se registraron devoluciones en este período</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Custom HTML Tooltip Container -->
        <div id="chartjs-tooltip" style="opacity: 0; position: absolute; background: rgba(15,23,42,0.95); color: white; border-radius: 8px; pointer-events: none; padding: 10px; transition: all 0.1s ease; z-index: 100; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
            <table style="margin:0; font-size: 13px; font-family: Inter;"></table>
        </div>

    </div>

    <!-- Modal para Drill-Down de Gráficos -->
    <div class="modal-overlay" id="drilldownModal">
        <div class="modal-content" style="max-width:800px;">
            <div class="modal-header">
                <h3><i class="ph ph-chart-bar"></i> Detalle del Producto</h3>
                <button class="close-modal" onclick="document.getElementById('drilldownModal').classList.remove('active')">&times;</button>
            </div>
            <div class="modal-body" style="max-height:60vh; overflow-y:auto; padding: 20px;">
                <div style="display:flex; gap:20px; align-items:flex-start; margin-bottom:20px;">
                    <img id="ddProductImage" src="" alt="Producto" style="width:100px; height:100px; object-fit:cover; border-radius:8px; border:1px solid #e2e8f0; display:none;">
                    <div>
                        <h4 id="ddProductName" style="margin-bottom:5px; color:var(--text-color);">Nombre del Producto</h4>
                        <p style="color:#64748b; font-size:0.9rem;">Últimos 20 movimientos</p>
                    </div>
                </div>
                <table class="inv-table" style="width:100%;">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Acción</th>
                            <th>Técnico/Usuario</th>
                            <th>Cantidad</th>
                            <th>Notas</th>
                        </tr>
                    </thead>
                    <tbody id="ddTableBody">
                        <!-- Llenado dinámicamente -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div><!-- /.inv-content-card -->

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="<?php echo BASE_URL; ?>/modules/inventario/dashboard/dashboard.js"></script>


<!-- Modal: Selector de Tipo de Producto -->
<div class="modal-overlay" id="productTypeModal">
    <div class="modal-content np-type-picker-modal">
        <div class="np-type-picker-header">
            <div class="np-type-picker-title-group">
                <div class="np-type-picker-avatar">
                    <i class="ph ph-squares-four"></i>
                </div>
                <div>
                    <h3 class="np-type-picker-title">¿Qué tipo de producto deseas crear?</h3>
                    <p class="np-type-picker-subtitle">Selecciona la estructura adecuada para el control de inventario de este ítem</p>
                </div>
            </div>
            <button type="button" class="np-type-picker-close" onclick="document.getElementById('productTypeModal').classList.remove('active')" title="Cerrar">
                <i class="ph ph-x"></i>
            </button>
        </div>
        
        <div class="np-type-picker-body">
            <div class="np-type-cards-grid">
                <!-- 1. Producto Normal -->
                <div class="np-type-card-item card-theme-blue" onclick="selectProductType('normal')">
                    <div class="np-type-card-glow"></div>
                    <div class="np-type-card-head">
                        <div class="np-type-icon-wrapper">
                            <i class="ph ph-barcode"></i>
                        </div>
                        <span class="np-type-chip">Con SKUs / Seriales</span>
                    </div>
                    <div class="np-type-card-main">
                        <h4 class="np-type-name">Producto Normal</h4>
                        <p class="np-type-description">Unidades con series únicas (SN/MAC) y soporte de escáner continuo.</p>
                    </div>
                    <div class="np-type-card-tags">
                        <span class="np-type-tag"><i class="ph ph-cpu"></i> Routers & ONTs</span>
                        <span class="np-type-tag"><i class="ph ph-wrench"></i> Herramientas</span>
                    </div>
                    <div class="np-type-card-bottom">
                        <span class="np-type-btn-label">Configurar SKUs</span>
                        <div class="np-type-btn-arrow"><i class="ph ph-arrow-right"></i></div>
                    </div>
                </div>

                <!-- 2. Producto a Granel -->
                <div class="np-type-card-item card-theme-amber" onclick="selectProductType('granel')">
                    <div class="np-type-card-glow"></div>
                    <div class="np-type-card-head">
                        <div class="np-type-icon-wrapper">
                            <i class="ph ph-scales"></i>
                        </div>
                        <span class="np-type-chip">Metros / Kilos / Litros</span>
                    </div>
                    <div class="np-type-card-main">
                        <h4 class="np-type-name">Producto a Granel</h4>
                        <p class="np-type-description">Materiales continuos, metraje o volumen sin seriales unitarios.</p>
                    </div>
                    <div class="np-type-card-tags">
                        <span class="np-type-tag"><i class="ph ph-arrows-left-right"></i> Bobinas & Cables</span>
                        <span class="np-type-tag"><i class="ph ph-drop"></i> Insumos / Metros</span>
                    </div>
                    <div class="np-type-card-bottom">
                        <span class="np-type-btn-label">Configurar Granel</span>
                        <div class="np-type-btn-arrow"><i class="ph ph-arrow-right"></i></div>
                    </div>
                </div>

                <!-- 3. Producto Agrupado -->
                <div class="np-type-card-item card-theme-purple" onclick="selectProductType('agrupado')">
                    <div class="np-type-card-glow"></div>
                    <div class="np-type-card-head">
                        <div class="np-type-icon-wrapper">
                            <i class="ph ph-stack"></i>
                        </div>
                        <span class="np-type-chip">Kits & Variantes</span>
                    </div>
                    <div class="np-type-card-main">
                        <h4 class="np-type-name">Producto Agrupado</h4>
                        <p class="np-type-description">Agrupa variantes en un producto padre con combinaciones múltiples.</p>
                    </div>
                    <div class="np-type-card-tags">
                        <span class="np-type-tag"><i class="ph ph-tree-structure"></i> Padre e Hijos</span>
                        <span class="np-type-tag"><i class="ph ph-sliders"></i> Atributos Dinámicos</span>
                    </div>
                    <div class="np-type-card-bottom">
                        <span class="np-type-btn-label">Configurar Variantes</span>
                        <div class="np-type-btn-arrow"><i class="ph ph-arrow-right"></i></div>
                    </div>
                </div>

                <!-- 4. Producto Bundle -->
                <div class="np-type-card-item card-theme-emerald" onclick="selectProductType('bundle')">
                    <div class="np-type-card-glow"></div>
                    <div class="np-type-card-head">
                        <div class="np-type-icon-wrapper">
                            <i class="ph ph-package"></i>
                        </div>
                        <span class="np-type-chip">Variantes con Foto</span>
                    </div>
                    <div class="np-type-card-main">
                        <h4 class="np-type-name">Producto Bundle</h4>
                        <p class="np-type-description">Variantes con fotos individuales y stock propio independiente.</p>
                    </div>
                    <div class="np-type-card-tags">
                        <span class="np-type-tag"><i class="ph ph-image"></i> Foto por Variante</span>
                        <span class="np-type-tag"><i class="ph ph-cube"></i> Stock Propio</span>
                    </div>
                    <div class="np-type-card-bottom">
                        <span class="np-type-btn-label">Configurar Bundle</span>
                        <div class="np-type-btn-arrow"><i class="ph ph-arrow-right"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: New Product (Studio Layout Multitipo) -->
<div class="modal-overlay" id="newProductModal">
    <div class="modal-content np-modal-container">
        <div class="modal-header np-modal-header">
            <div class="np-header-title-box">
                <div class="np-header-icon-badge" id="npHeaderTypeIcon">
                    <i class="ph ph-package"></i>
                </div>
                <div>
                    <h3 class="np-modal-title">Nuevo Producto <span id="productTypeBadge" class="product-type-header-badge"></span></h3>
                    <p class="np-modal-subtitle" id="npModalSubtitle">Configura los detalles del producto, personaliza atributos y gestiona los códigos SKU</p>
                </div>
            </div>
            <button class="close-modal np-close-btn" onclick="closeProductModal()"><i class="ph ph-x"></i></button>
        </div>

        <div class="modal-body np-modal-body">
            <!-- ═══ Tab: Datos ═══ -->
            <div class="np-pane active" id="nptab-datos">

                <!-- Grid layout (Studio 2-Column Layout) -->
                <div id="newProductGrid" class="np-dynamic-grid np-studio-grid">

                    <!-- ── PANEL IZQUIERDO: Ficha del Producto & Atributos ── -->
                    <div class="np-panel np-panel-left">

                        <!-- Card 1: Ficha Principal del Producto (Común para todos los tipos) -->
                        <div class="np-card">
                            <div class="np-card-header">
                                <div class="np-card-title">
                                    <span class="np-card-icon np-icon-purple"><i class="ph ph-cube"></i></span>
                                    <span>Información del Producto</span>
                                </div>
                            </div>
                            <div class="np-card-body">
                                <!-- Nombre del Producto -->
                                <div class="inv-form-field">
                                    <label class="form-label">Nombre del Producto <span style="color:#ef4444;">*</span></label>
                                    <input type="text" class="form-control np-input-highlight" id="prodName" required placeholder="Ej: Router TP-Link / Bobina Cable UTP">
                                    <div style="margin-top:6px; font-size:0.83rem;">
                                        <a href="javascript:void(0)" class="np-alias-toggle" onclick="document.getElementById('aliasWrap').style.display='block'; this.style.display='none';"><i class="ph ph-plus-circle"></i> Añadir nombre alternativo</a>
                                        <div id="aliasWrap" style="display:none; margin-top:6px;">
                                            <label class="form-label" style="font-size:0.78rem; color:var(--text-muted);">Nombres Alternativos (Presiona Enter para agregar)</label>
                                            <div class="inv-tags-input np-tags-container">
                                                <div id="aliasTagsContainer" style="display:flex; gap:6px; flex-wrap:wrap;"></div>
                                                <input type="text" id="prodAliasInput" placeholder="Ej: Router negro" class="np-tag-input">
                                                <input type="hidden" id="prodAliases" value="">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Categoría -->
                                <div class="inv-form-field" style="margin-top:12px;">
                                    <label class="form-label">Categoría</label>
                                    <div class="np-category-group">
                                        <select class="form-select" id="prodCategory"><option value="">Sin categoría</option></select>
                                        <button type="button" class="btn btn-secondary np-btn-icon" onclick="promptNewCategory()" title="Nueva categoría"><i class="ph ph-plus"></i></button>
                                        <button type="button" class="btn btn-secondary np-btn-icon" onclick="openManageCategories()" title="Gestionar categorías"><i class="ph ph-gear"></i></button>
                                    </div>
                                </div>

                                <!-- Métrica 3 Columnas: Stock Mín, Crítico, Costo -->
                                <div class="np-metrics-grid" style="margin-top:14px;">
                                    <div class="np-metric-box">
                                        <label class="np-metric-label"><i class="ph ph-shield-warning" style="color:#f59e0b;"></i> Stock Mín.</label>
                                        <input type="number" class="form-control np-metric-input" id="prodStockMin" min="0" value="10" placeholder="10">
                                    </div>
                                    <div class="np-metric-box">
                                        <label class="np-metric-label"><i class="ph ph-warning-octagon" style="color:#ef4444;"></i> Stock Crítico</label>
                                        <input type="number" class="form-control np-metric-input" id="prodStockCrit" min="0" value="3" placeholder="3">
                                    </div>
                                    <div class="np-metric-box">
                                        <label class="np-metric-label"><i class="ph ph-currency-dollar" style="color:#10b981;"></i> Costo Base (S/)</label>
                                        <input type="number" class="form-control np-metric-input" id="prodCosto" step="0.01" min="0" value="0.00" placeholder="0.00" oninput="updateGranelCalculation()">
                                    </div>
                                </div>

                                <!-- Fotos del Producto -->
                                <div class="np-photo-section" style="margin-top:14px;">
                                    <label class="form-label" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                                        <span style="display:flex; align-items:center; gap:6px;"><i class="ph ph-images" style="color:#8b5cf6;"></i> Fotos Principales</span>
                                        <span style="font-size:0.75rem; color:var(--text-muted);">Opcional</span>
                                    </label>
                                    
                                    <div class="np-photo-dropzone" id="prodPhotoDropzone"
                                         onclick="document.getElementById('prodMultiPhotoInput').click()"
                                         ondrop="handleProductPhotoDrop(event, 'create')"
                                         ondragover="event.preventDefault(); this.classList.add('dragover')"
                                         ondragleave="this.classList.remove('dragover')">
                                        <div class="np-dropzone-left">
                                            <i class="ph ph-cloud-arrow-up np-dropzone-icon"></i>
                                            <div class="np-dropzone-text">
                                                <span>Arrastra fotos aquí o <strong>haz clic</strong></span>
                                            </div>
                                        </div>
                                        <div class="np-dropzone-actions" onclick="event.stopPropagation();">
                                            <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('prodMultiPhotoInput').click()"><i class="ph ph-upload-simple"></i> Subir</button>
                                            <button type="button" class="btn btn-secondary btn-sm" onclick="captureProductPhoto('create')"><i class="ph ph-camera"></i> Cámara</button>
                                        </div>
                                    </div>
                                    
                                    <input type="file" id="prodMultiPhotoInput" class="no-dropzone" multiple accept="image/*" style="position:absolute; left:-9999px; width:1px; height:1px;" onchange="handleProductPhotoSelect(event, 'create')">
                                    <input type="file" id="prodPhotoInput" class="no-dropzone" accept="image/*" style="position:absolute; left:-9999px; width:1px; height:1px;" onchange="handleProductPhotoSelect(event, 'create')">
                                    <div id="prodPhotoGallery" class="prod-photo-gallery" style="margin-top:10px;"></div>
                                </div>

                                <input type="hidden" id="prodIsBulk" value="0">
                            </div>
                        </div>

                        <!-- Card 2: Columnas Personalizadas (Solo Normal) -->
                        <div id="customColsSection" class="np-card np-custom-cols-card" data-show-for="normal" style="margin-top:16px;">
                            <div class="np-card-header">
                                <div class="np-card-title">
                                    <span class="np-card-icon np-icon-indigo"><i class="ph ph-columns"></i></span>
                                    <span>Atributos / Columnas por SKU</span>
                                </div>
                                <span class="np-badge-count" id="colCountBadge">0 columnas</span>
                            </div>
                            <div class="np-card-body">
                                <p style="font-size:0.8rem; color:var(--text-muted); margin-bottom:10px;">
                                    Define campos personalizados para cada unidad (MAC, Serie SN, IP, Ubicación, etc.).
                                </p>
                                <!-- Sugerencias rápidas -->
                                <div class="np-suggestions-container">
                                    <span style="color:var(--text-muted);font-size:0.75rem;font-weight:600;">Añadir rápido:</span>
                                    <div id="suggestionsWrap" class="np-suggestions-wrap">
                                        <span class="inv-col-pill np-pill-add-new" onclick="promptNewSuggestion('create')"><i class="ph ph-plus"></i> Nueva</span>
                                    </div>
                                </div>

                                <!-- Lista de columnas añadidas -->
                                <div id="customColsList" class="np-custom-cols-list"></div>

                                <!-- Formulario de creación de columna -->
                                <div id="addColForm" class="np-add-col-form">
                                    <div class="np-add-col-row">
                                        <input type="text" class="form-control" id="newColName" placeholder="Nombre de columna..." onkeydown="if(event.key==='Enter'){event.preventDefault();addCustomColumn();}">
                                        <select class="form-control" id="newColType">
                                            <option value="text">📝 Texto</option>
                                            <option value="number">🔢 Número</option>
                                            <option value="date">📅 Fecha</option>
                                            <option value="select">📋 Lista</option>
                                        </select>
                                        <button type="button" class="btn btn-primary np-btn-add-col" onclick="addCustomColumn()"><i class="ph ph-plus"></i> Añadir</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 3: Gestor de Columnas de Variantes (Solo Agrupado y Bundle) -->
                        <div id="agrupadoVariantsColsSection" class="np-card" data-show-for="agrupado bundle" style="display:none; margin-top:16px;">
                            <div class="np-card-header">
                                <div class="np-card-title">
                                    <span class="np-card-icon np-icon-purple"><i class="ph ph-columns"></i></span>
                                    <span>Columnas de Variantes</span>
                                </div>
                                <span id="varColCountBadge" class="np-badge-count">0 columnas</span>
                            </div>
                            <div class="np-card-body">
                                <p style="font-size:0.8rem; color:var(--text-muted); margin-bottom:10px;">
                                    Define los atributos que diferencian cada variante (Marca, Color, Talla, Longitud, etc.).
                                </p>
                                <div class="np-suggestions-container">
                                    <span style="color:var(--text-muted);font-size:0.75rem;font-weight:600;">Añadir rápido:</span>
                                    <div id="varColSuggestions" class="np-suggestions-wrap">
                                        <button type="button" class="btn-suggestion" onclick="addVariantCol('Marca')">+ Marca</button>
                                        <button type="button" class="btn-suggestion" onclick="addVariantCol('Talla')">+ Talla</button>
                                        <button type="button" class="btn-suggestion" onclick="addVariantCol('Color')">+ Color</button>
                                        <button type="button" class="btn-suggestion" onclick="addVariantCol('Material')">+ Material</button>
                                        <button type="button" class="btn-suggestion" onclick="addVariantCol('Peso')">+ Peso</button>
                                    </div>
                                </div>
                                <div id="varColsList" class="np-custom-cols-list"></div>
                                <div class="np-add-col-form">
                                    <div class="np-add-col-row">
                                        <input type="text" class="form-control" id="varColNewName" placeholder="Nueva columna de variante..." style="flex:2;" onkeydown="if(event.key==='Enter'){event.preventDefault();addVariantCol();}">
                                        <button type="button" class="btn btn-primary np-btn-add-col" onclick="addVariantCol()"><i class="ph ph-plus"></i> Añadir</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div><!-- end left panel -->

                    <!-- ── PANEL DERECHO: WORKSPACES ESPECIALIZADOS ── -->

                    <!-- ── WORKSPACE 1: Normal Product (SKUs Generator + Scanner + Table) ── -->
                    <div id="skuRightCol" class="np-panel np-panel-right" data-show-for="normal">
                        <div class="np-card np-skus-workspace-card">
                            <!-- Card Header con Switch Auto / Manual -->
                            <div class="np-card-header np-skus-header">
                                <div class="np-card-title">
                                    <span class="np-card-icon np-icon-emerald"><i class="ph ph-list-numbers"></i></span>
                                    <span>Control y Registro de SKUs</span>
                                </div>
                                <div id="skuModeToggleWrap" class="inv-mode-toggle">
                                    <button type="button" class="inv-mode-btn active" id="modeAuto" onclick="setSkuMode('auto')"><i class="ph ph-lightning"></i> Automático</button>
                                    <button type="button" class="inv-mode-btn" id="modeManual" onclick="setSkuMode('manual')"><i class="ph ph-pencil-line"></i> Manual</button>
                                </div>
                            </div>

                            <div class="np-card-body np-skus-body">
                                <!-- Barra de generación de SKUs -->
                                <div class="np-generator-toolbar">
                                    <div id="autoModeWrap" class="np-auto-mode-wrap">
                                        <div class="np-qty-control">
                                            <label class="np-qty-label"><i class="ph ph-hash"></i> Cantidad:</label>
                                            <input type="number" class="form-control np-qty-input" id="prodQty" min="1" value="1" placeholder="10">
                                        </div>
                                        <button type="button" class="btn btn-primary np-btn-generate-skus" id="btnGenerateSkus" onclick="generateAutoSkus()">
                                            <i class="ph ph-lightning"></i> Generar SKUs
                                        </button>
                                    </div>
                                    <div id="manualModeWrap" class="np-manual-mode-wrap" style="display:none;">
                                        <div class="np-manual-controls-group">
                                            <button type="button" class="btn btn-primary np-btn-manual-add" onclick="addManualSkuRow()">
                                                <i class="ph ph-plus-circle"></i> Agregar Fila Manual
                                            </button>
                                            <div class="np-manual-scan-input-wrap">
                                                <i class="ph ph-barcode"></i>
                                                <input type="text" class="form-control np-manual-scan-field" id="manualSkuScanInput" placeholder="Escanear código de barra / lector láser..." autocomplete="off">
                                            </div>
                                        </div>
                                        <span class="np-manual-helper"><i class="ph ph-info"></i> Escribe o escanea códigos para agregar filas inmediatamente a la lista.</span>
                                    </div>
                                </div>

                                <!-- Barra Integrada de Escaneo Continuo para Columnas Personalizadas -->
                                <div id="skuScannerCol" class="np-scanner-toolbar" data-show-for="normal" style="display:none;">
                                    <div class="np-scanner-inputs-group">
                                        <div class="np-scanner-col-box">
                                            <label class="np-scanner-label"><i class="ph ph-columns"></i> Columna Destino:</label>
                                            <select class="form-select np-scanner-select" id="continuousScanColumn">
                                                <option value="">Selecciona columna...</option>
                                            </select>
                                        </div>
                                        <div class="np-scanner-scan-box">
                                            <label class="np-scanner-label"><i class="ph ph-barcode"></i> Escaneo de Atributos:</label>
                                            <div class="np-scanner-input-wrap">
                                                <i class="ph ph-qr-code"></i>
                                                <input type="text" class="form-control np-scanner-input" id="continuousScanInput" placeholder="Escanea atributo y presiona Enter..." autocomplete="off">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="np-scanner-progress-box">
                                        <div class="np-scan-progress-header">
                                            <span class="np-scan-progress-title"><i class="ph ph-check-circle" style="color:var(--primary-color);"></i> Progreso Atributos</span>
                                            <span id="scanProgressCount" class="np-scan-progress-count">0 / 0</span>
                                        </div>
                                        <div class="np-scan-progress-bar-track">
                                            <div id="scanProgressBar" class="np-scan-progress-bar-fill" style="width:0%;"></div>
                                        </div>
                                        <p id="scanProgressText" class="np-scan-progress-hint">Selecciona una columna</p>
                                    </div>
                                    <input type="checkbox" id="prodRequiresPhotos" style="display:none;" value="0">
                                </div>

                                <!-- Empty State cuando no hay SKUs -->
                                <div id="skuPreviewEmptyState" class="np-skus-empty-state">
                                    <div class="np-empty-icon-circle">
                                        <i class="ph ph-barcode"></i>
                                    </div>
                                    <h4 id="skuEmptyStateTitle" class="np-empty-title">Sin SKUs generados</h4>
                                    <p id="skuEmptyStateDesc" class="np-empty-desc">Indica la cantidad deseada y haz clic en <strong>Generar SKUs</strong> para crear los códigos automáticamente.</p>
                                </div>

                                <!-- Tabla Spreadsheet de SKUs -->
                                <div id="skuPreviewWrap" class="np-sku-table-wrap" style="display:none;">
                                    <div class="np-sku-table-topbar">
                                        <div class="np-sku-table-info">
                                            <h4 id="skuPreviewTitle" class="np-sku-count-title">SKUs: 0</h4>
                                            <span id="skuPreviewCount" class="np-sku-mode-badge">Modo automático</span>
                                        </div>
                                        <button type="button" class="btn btn-secondary btn-sm np-btn-add-quick-row" id="npBtnAddQuickRow" onclick="addManualSkuRow()" style="display:none;" title="Añadir una fila extra">
                                            <i class="ph ph-plus"></i> Añadir Fila
                                        </button>
                                    </div>
                                    <div class="table-responsive np-sku-table-scroll">
                                        <table class="inv-table np-spreadsheet-table">
                                            <thead id="skuPreviewHead">
                                                <tr><th>#</th><th>SKU Code</th><th>Estado</th><th></th></tr>
                                            </thead>
                                            <tbody id="skuPreviewBody"></tbody>
                                        </table>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div><!-- end normal workspace -->

                    <!-- ── WORKSPACE 2: Granel Product (Stock & Unidad & SKU Maestro) ── -->
                    <div id="granelRightCol" class="np-panel np-panel-right" data-show-for="granel" style="display:none;">
                        <div class="np-card np-granel-workspace-card">
                            <div class="np-card-header">
                                <div class="np-card-title">
                                    <span class="np-card-icon" style="background:rgba(245,158,11,0.12); color:#f59e0b;"><i class="ph ph-scales"></i></span>
                                    <span>Control de Stock a Granel & Identificación</span>
                                </div>
                                <span class="np-type-badge np-badge-amber">Sin SKUs Unitarios</span>
                            </div>

                            <div class="np-card-body">
                                <!-- Banner informativo Granel -->
                                <div class="np-info-banner-amber">
                                    <div class="np-info-banner-icon">
                                        <i class="ph ph-info"></i>
                                    </div>
                                    <div class="np-info-banner-content">
                                        <strong>Material Continuo / Granel</strong>
                                        <p>Este producto no genera códigos individuales por cada unidad. El stock se descuenta y controla por metraje, peso o volumen en los despachos y obras.</p>
                                    </div>
                                </div>

                                <div class="np-granel-grid-layout" style="margin-top:16px; display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                                    <!-- Stock Inicial -->
                                    <div class="np-card" style="background:var(--bg-color); border:1px solid var(--border-color); padding:16px;">
                                        <label class="form-label" style="font-weight:700; display:flex; align-items:center; gap:6px; margin-bottom:8px;">
                                            <i class="ph ph-stack-plus" style="color:#f59e0b; font-size:1.1rem;"></i> Cantidad / Stock Inicial
                                        </label>
                                        <div style="display:flex; align-items:center; gap:8px;">
                                            <input type="number" class="form-control np-granel-qty-input" id="prodGranelQty" min="1" value="100" placeholder="Ej: 500" oninput="updateGranelCalculation()" style="font-size:1.4rem; font-weight:800; text-align:center; height:50px;">
                                        </div>
                                        <div class="np-qty-quick-buttons" style="display:flex; gap:6px; margin-top:8px;">
                                            <button type="button" class="btn btn-secondary btn-sm" onclick="setGranelQuickQty(50)">50</button>
                                            <button type="button" class="btn btn-secondary btn-sm" onclick="setGranelQuickQty(100)">100</button>
                                            <button type="button" class="btn btn-secondary btn-sm" onclick="setGranelQuickQty(500)">500</button>
                                            <button type="button" class="btn btn-secondary btn-sm" onclick="setGranelQuickQty(1000)">1000</button>
                                        </div>
                                    </div>

                                    <!-- Unidad de Medida -->
                                    <div class="np-card" style="background:var(--bg-color); border:1px solid var(--border-color); padding:16px;">
                                        <label class="form-label" style="font-weight:700; display:flex; align-items:center; gap:6px; margin-bottom:8px;">
                                            <i class="ph ph-ruler" style="color:#6366f1; font-size:1.1rem;"></i> Unidad de Medida
                                        </label>
                                        <select class="form-select" id="prodUnitType" onchange="updateGranelCalculation()" style="font-weight:600; height:50px; font-size:0.95rem;">
                                            <option value="Metros (m)" selected>📏 Metros (m)</option>
                                            <option value="Centímetros (cm)">Centímetros (cm)</option>
                                            <option value="Kilómetros (km)">Kilómetros (km)</option>
                                            <option value="Kilogramos (kg)">⚖️ Kilogramos (kg)</option>
                                            <option value="Gramos (g)">Gramos (g)</option>
                                            <option value="Litros (L)">🧴 Litros (L)</option>
                                            <option value="Unidades">📦 Unidades (Und)</option>
                                            <option value="Cajas">📦 Cajas</option>
                                            <option value="Rollos / Bobinas">🌀 Rollos / Bobinas</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- SKU Maestro / Lote -->
                                <div class="np-card" style="margin-top:16px; background:var(--bg-color); border:1px solid var(--border-color); padding:16px;">
                                    <label class="form-label" style="font-weight:700; display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                                        <span style="display:flex; align-items:center; gap:6px;"><i class="ph ph-qr-code" style="color:#8b5cf6;"></i> SKU Maestro / Código de Barra de Bobina o Contenedor</span>
                                        <span style="font-size:0.75rem; color:var(--text-muted);">Opcional</span>
                                    </label>
                                    <div style="display:flex; gap:8px;">
                                        <input type="text" class="form-control" id="prodMasterSku" placeholder="Ej: BLK-CABLE-01 o escanea código..." style="font-family:monospace; font-weight:700; font-size:0.95rem; height:42px;">
                                        <button type="button" class="btn btn-secondary" onclick="document.getElementById('prodMasterSku').value = 'BLK-' + randomCode(6);" title="Generar código automático" style="padding:0 16px; flex-shrink:0; font-weight:600; display:inline-flex; align-items:center; gap:6px;">
                                            <i class="ph ph-lightning"></i> Generar
                                        </button>
                                    </div>
                                    <small style="color:var(--text-muted); display:block; margin-top:6px;">Puedes pegar o escanear el código de barra impreso en la etiqueta de la bobina para identificarla rápidamente en el lector de inventario.</small>
                                </div>

                                <!-- Card Resumen de Valorización -->
                                <div class="np-valorization-card" style="margin-top:16px;">
                                    <div class="np-valorization-header">
                                        <span class="np-valorization-title"><i class="ph ph-calculator"></i> Estimación de Valorización en Inventario</span>
                                    </div>
                                    <div class="np-valorization-grid">
                                        <div class="np-valorization-item">
                                            <span class="np-val-lbl">Stock Inicial</span>
                                            <strong id="granelValQtyDisplay" class="np-val-val">100 Metros (m)</strong>
                                        </div>
                                        <div class="np-valorization-item">
                                            <span class="np-val-lbl">Costo Base Ref.</span>
                                            <strong id="granelValCostDisplay" class="np-val-val">S/ 0.00</strong>
                                        </div>
                                        <div class="np-valorization-item np-valorization-total">
                                            <span class="np-val-lbl">Valor Total en Stock</span>
                                            <strong id="granelValTotalDisplay" class="np-val-val-total">S/ 0.00</strong>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div><!-- end granel workspace -->

                    <!-- ── WORKSPACE 3: Agrupado & Bundle (Variants Table Matrix) ── -->
                    <div id="variantsRightCol" class="np-panel np-panel-right" data-show-for="agrupado bundle" style="display:none;">
                        <div class="np-card np-variants-workspace-card">
                            <div class="np-card-header">
                                <div class="np-card-title">
                                    <span class="np-card-icon" style="background:rgba(139,92,246,0.12); color:#8b5cf6;"><i class="ph ph-stack"></i></span>
                                    <span id="variantsWorkspaceTitle">Matriz de Variantes del Producto</span>
                                </div>
                                <div style="display:flex; align-items:center; gap:12px;">
                                    <span id="variantCountBadge" class="np-badge-count">0 variantes</span>
                                    <label style="display:flex; align-items:center; gap:6px; font-size:0.83rem; cursor:pointer; color:var(--text-color); margin:0; font-weight:600;">
                                        <input type="checkbox" class="form-check-input" id="varHasCustomCost" onchange="toggleVariantCustomCost()"> 
                                        Costo distinto por variante
                                    </label>
                                </div>
                            </div>

                            <div class="np-card-body np-skus-body">
                                <div class="np-sku-table-topbar" style="border-radius:10px 10px 0 0;">
                                    <div class="np-sku-table-info">
                                        <span class="np-manual-helper"><i class="ph ph-info"></i> Cada fila representa una opción con su propio stock inicial.</span>
                                    </div>
                                    <button type="button" class="btn btn-primary btn-sm np-btn-add-quick-row" onclick="addVariantRow()">
                                        <i class="ph ph-plus"></i> Agregar Variante
                                    </button>
                                </div>

                                <!-- Tabla Dinámica de Variantes -->
                                <div class="table-responsive np-sku-table-scroll" id="variantsTableWrap" style="border-radius:0 0 10px 10px;">
                                    <table class="inv-table np-spreadsheet-table">
                                        <thead id="variantsTableHead">
                                            <tr>
                                                <th style="min-width:160px;">Nombre de Variante</th>
                                                <th style="min-width:90px; text-align:center;">Cantidad</th>
                                                <th style="width:50px; text-align:center;"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="variantsTableBody"></tbody>
                                    </table>
                                </div>

                                <div id="variantsEmptyState" class="np-skus-empty-state" style="display:none; margin-top:12px;">
                                    <div class="np-empty-icon-circle">
                                        <i class="ph ph-stack"></i>
                                    </div>
                                    <h4 class="np-empty-title">Sin variantes agregadas</h4>
                                    <p class="np-empty-desc">Haz clic en <strong>Agregar Variante</strong> para configurar las opciones de este producto.</p>
                                </div>

                                <button type="button" class="btn btn-secondary w-100" onclick="addVariantRow()" style="margin-top:12px; height:38px; font-weight:600; display:inline-flex; align-items:center; justify-content:center; gap:6px;">
                                    <i class="ph ph-plus-circle"></i> Añadir otra variante
                                </button>
                            </div>
                        </div>
                    </div><!-- end agrupado & bundle workspace -->

                </div><!-- end grid -->
            </div>
        </div>

        <div class="modal-footer np-modal-footer">
            <button class="btn btn-secondary np-btn-cancel" onclick="closeProductModal()">Cancelar</button>
            <button class="btn btn-primary np-btn-save" id="btnSaveProduct"><i class="ph ph-floppy-disk"></i> Guardar Producto</button>
        </div>
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
                <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px; margin-top:12px; margin-bottom:14px;">
                        <div>
                            <label class="form-label">Stock Mínimo</label>
                            <input type="number" class="form-control" id="editProductStockMin" value="10">
                        </div>
                        <div>
                            <label class="form-label">Stock Crítico</label>
                            <input type="number" class="form-control" id="editProductStockCritico" value="3">
                        </div>
                        <div>
                            <label class="form-label">Costo Referencial</label>
                            <input type="number" class="form-control" id="editProductCosto" step="0.01" min="0" value="0.00">
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


<!-- Modal: SKU Detail (Unified Large Modern Layout) -->
<div class="modal-overlay" id="skuDetailModal">
    <div class="modal-content sku-detail-modal-large" id="skuDetailContentWrap">
        <!-- Modern Header -->
        <div class="sku-detail-header-modern">
            <div class="sdh-left">
                <div id="skuDetailHeaderImg" class="sdh-img-wrap">
                    <i class="ph ph-package"></i>
                </div>
                <div class="sdh-info">
                    <div class="sdh-badges-row">
                        <span class="sdh-sku-pill" id="sdhSkuCodePill" onclick="copyModalSkuCode()" title="Clic para copiar SKU">
                            <i class="ph ph-barcode"></i>
                            <code id="sdhSkuText">TRB-000000</code>
                            <i class="ph ph-copy sdh-copy-icon"></i>
                        </span>
                        <span class="sdh-cat-pill" id="sdhCatPill"><i class="ph ph-folder"></i> <span id="sdhCatText">Categoría</span></span>
                        <span id="sdhStatusPill" class="sdh-status-pill"><i class="ph ph-circle"></i> <span id="sdhStatusText">DISPONIBLE</span></span>
                    </div>
                    <h3 class="sdh-title" id="skuDetailTitle">Detalle SKU</h3>
                </div>
            </div>
            <div class="sdh-actions">
                <button type="button" class="sdh-btn sdh-btn-label" onclick="printModalSkuLabel()" title="Generar e imprimir etiqueta para este SKU">
                    <i class="ph ph-printer"></i> <span>Etiqueta</span>
                </button>
                <button type="button" class="close-modal sdh-close-btn" onclick="closeSkuDetail()">&times;</button>
            </div>
        </div>

        <!-- Scrollable Modal Body -->
        <div class="sku-detail-body-modern">
            <!-- Attribute & Technical Specs Cards -->
            <div class="sd-section-card">
                <div class="sd-card-header">
                    <div class="sd-card-title"><i class="ph ph-cpu"></i> Especificaciones & Atributos Técnicos</div>
                </div>
                <div id="skuEditInfo" class="sku-specs-grid"></div>
            </div>

            <!-- Two-Column Operational Split -->
            <div class="sku-detail-split-grid">
                <!-- Left Column: Operations & Custody -->
                <div class="sd-col-operations">
                    <!-- Custody Card -->
                    <div class="sd-section-card">
                        <div class="sd-card-header">
                            <div class="sd-card-title"><i class="ph ph-user-circle"></i> Asignación a Usuario / Custodia</div>
                        </div>
                        <div class="sd-card-content">
                            <div id="skuAssignCurrent" class="sd-current-assign-box"></div>
                            
                            <div class="sd-form-group">
                                <label class="sd-form-label"><i class="ph ph-user"></i> Seleccionar Técnico / Usuario</label>
                                <select class="form-select sd-input" id="skuAssignUser">
                                    <option value="">Seleccionar usuario...</option>
                                </select>
                            </div>

                            <div class="sd-epp-toggle-row">
                                <label class="ms-switch-wrap">
                                    <input type="checkbox" id="skuAssignIsEpp" class="ms-input">
                                    <span class="ms-track"><span class="ms-thumb"></span></span>
                                </label>
                                <span class="sd-epp-label"><i class="ph ph-shield-check" style="color:#8b5cf6;"></i> Asignar como EPP / Herramienta de Trabajo</span>
                            </div>

                            <div class="sd-assign-actions">
                                <button type="button" class="btn btn-primary sd-btn-assign" onclick="assignSkuToUser()"><i class="ph ph-user-plus"></i> Asignar Activo</button>
                                <button type="button" class="btn btn-secondary sd-btn-unassign" onclick="unassignSku()"><i class="ph ph-user-minus"></i> Liberar Custodia</button>
                            </div>
                        </div>
                    </div>

                    <!-- Status & Movement Card -->
                    <div class="sd-section-card">
                        <div class="sd-card-header">
                            <div class="sd-card-title"><i class="ph ph-arrows-left-right"></i> Estado & Registrar Movimiento</div>
                        </div>
                        <div class="sd-card-content">
                            <!-- Status Wrapper -->
                            <div id="statusWrapper" class="sd-form-group">
                                <label class="sd-form-label"><i class="ph ph-tag"></i> Cambiar Estado Actual</label>
                                <select class="form-select sd-input" id="skuDetailStatus" onchange="updateSkuDetailStatus()">
                                    <option value="disponible">🟢 Disponible</option>
                                    <option value="instalado">🔵 Instalado</option>
                                    <option value="malogrado">🔴 Malogrado</option>
                                    <option value="reparado">🟡 Reparado</option>
                                    <option value="en_transito">🟣 En Tránsito</option>
                                    <option value="observacion">🟠 Observación</option>
                                </select>
                            </div>

                            <div class="sd-form-group">
                                <label class="sd-form-label"><i class="ph ph-swap"></i> Tipo de Movimiento (Kardex)</label>
                                <select class="form-select sd-input" id="entryType">
                                    <option value="entrada">📥 Entrada / Ingreso</option>
                                    <option value="salida">📤 Salida / Entrega</option>
                                    <option value="devolucion">🔄 Devolución a Almacén</option>
                                    <option value="reparacion">🔧 Mantenimiento / Reparación</option>
                                </select>
                            </div>

                            <div class="sd-form-group">
                                <label class="sd-form-label"><i class="ph ph-chat-text"></i> Notas / Motivo</label>
                                <textarea class="form-control sd-input" id="entryNotas" rows="3" placeholder="Ingresa detalles u observaciones del movimiento..."></textarea>
                            </div>

                            <button type="button" class="btn btn-primary sd-btn-save-movement" onclick="submitEntry()">
                                <i class="ph ph-floppy-disk"></i> Registrar Movimiento
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Evidence Photos & Timeline History -->
                <div class="sd-col-evidence-history">
                    <!-- Photos Card -->
                    <div class="sd-section-card">
                        <div class="sd-card-header">
                            <div class="sd-card-title"><i class="ph ph-camera"></i> Fotos de Evidencia</div>
                        </div>
                        <div class="sd-card-content">
                            <div class="sd-photo-buttons">
                                <button type="button" class="btn btn-secondary sd-photo-btn" onclick="takeEntryPhoto()"><i class="ph ph-camera"></i> Tomar Foto</button>
                                <button type="button" class="btn btn-secondary sd-photo-btn" onclick="document.getElementById('entryPhotosGallery').click()"><i class="ph ph-images"></i> Galería / Archivos</button>
                            </div>
                            <div id="photoPreviewList" class="inv-photo-previews"></div>
                            <input type="file" id="entryPhotosGallery" class="no-dropzone" multiple accept="image/*" onchange="previewEntryPhotos(this)" style="position:absolute;width:0;height:0;overflow:hidden;opacity:0;pointer-events:none;">
                        </div>
                    </div>

                    <!-- Timeline History Card -->
                    <div class="sd-section-card sd-history-card">
                        <div class="sd-card-header">
                            <div class="sd-card-title"><i class="ph ph-clock-counter-clockwise"></i> Historial de Movimientos & Trazabilidad</div>
                        </div>
                        <div class="sd-card-content sd-history-scroll-box">
                            <div id="entryHistoryList" class="inv-entry-history"></div>
                        </div>
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

<!-- Floating Bulk Action Bar -->
<div id="invFloatingBulkBar" class="inv-floating-bulk-bar">
    <div class="fbb-counter-pill">
        <i class="ph-bold ph-check-square-offset"></i>
        <span id="fbbCount">0</span> seleccionados
    </div>
    <div class="fbb-actions-group">
        <button type="button" class="btn-fbb-action fbb-assign" onclick="openBulkAssignModal()" title="Asignar seleccionados a un técnico">
            <i class="ph-bold ph-user-plus"></i> Asignar
        </button>
        <button type="button" class="btn-fbb-action fbb-unassign" onclick="executeBulkUnassign()" title="Quitar asignación y devolver a disponible">
            <i class="ph-bold ph-arrow-u-up-left"></i> Desasignar
        </button>
        <button type="button" class="btn-fbb-action fbb-status" onclick="openBulkStatusModal()" title="Cambiar estado en lote">
            <i class="ph-bold ph-tag"></i> Cambiar Estado
        </button>
        <button type="button" class="btn-fbb-action" onclick="exportCurrentBulkSelection()" title="Exportar seleccionados a Excel">
            <i class="ph-bold ph-file-xls" style="color:#10b981;"></i> Excel
        </button>
        <button type="button" class="btn-fbb-action fbb-delete" onclick="executeBulkDelete()" title="Eliminar seleccionados">
            <i class="ph-bold ph-trash"></i> Eliminar
        </button>
        <button type="button" class="btn-fbb-clear" onclick="clearCurrentSelection()" title="Deseleccionar todo">
            <i class="ph-bold ph-x"></i>
        </button>
    </div>
</div>

<!-- Modal: Asignación Masiva a Técnico -->
<div class="modal-overlay" id="modalBulkAssign" style="z-index:19005;">
    <div class="modal-content" style="max-width:500px; width:95%; border-radius:18px;">
        <div class="modal-header">
            <h3 style="display:flex; align-items:center; gap:8px; margin:0; font-size:1.15rem; font-weight:700;">
                <i class="ph-bold ph-user-plus" style="color:#3b82f6;"></i>
                <span>Asignar Elementos Seleccionados</span>
            </h3>
            <button type="button" class="close-btn" onclick="closeBulkAssignModal()">&times;</button>
        </div>
        <form id="formBulkAssign" onsubmit="submitBulkAssign(event)">
            <div class="modal-body" style="padding:20px;">
                <div style="background:rgba(59,130,246,0.12); border:1px solid rgba(59,130,246,0.3); color:#93c5fd; font-size:0.85rem; border-radius:10px; padding:10px 14px; margin-bottom:15px; display:flex; align-items:center; gap:8px;">
                    <i class="ph-bold ph-info" style="font-size:1.2rem; flex-shrink:0;"></i>
                    <span>Vas a asignar <strong id="bulkAssignItemCount">0</strong> elemento(s) al técnico seleccionado.</span>
                </div>
                <div class="form-group mb-3">
                    <label style="font-weight:700; margin-bottom:6px; display:block;">Técnico / Usuario de Destino <span style="color:#ef4444;">*</span></label>
                    <select id="bulkAssignUserId" class="form-control" style="background:var(--bg-color); border:1px solid var(--border-color); color:var(--text-color); border-radius:8px; padding:8px 12px; width:100%;" required>
                        <option value="">-- Selecciona el técnico --</option>
                    </select>
                </div>
                <div class="form-group mb-3">
                    <div style="padding:10px 14px; background:rgba(255,255,255,0.03); border:1px solid var(--border-color); border-radius:10px;">
                        <label style="cursor:pointer; display:flex; align-items:center; gap:8px; margin:0; font-weight:600; font-size:0.9rem;">
                            <input type="checkbox" id="bulkAssignIsEpp" value="1">
                            <span>Asignar como Equipo de Protección (EPP)</span>
                        </label>
                    </div>
                </div>
                <div class="form-group mb-3">
                    <label style="font-weight:700; margin-bottom:6px; display:block;">Notas u Observaciones (Opcional)</label>
                    <input type="text" id="bulkAssignNotes" class="form-control" style="background:var(--bg-color); border:1px solid var(--border-color); color:var(--text-color); border-radius:8px; padding:8px 12px; width:100%;" placeholder="Ej: Entrega por inicio de operaciones...">
                </div>
            </div>
            <div class="modal-footer" style="padding:14px 20px; border-top:1px solid var(--border-color); display:flex; gap:10px; justify-content:flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeBulkAssignModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnSubmitBulkAssign" style="background:linear-gradient(135deg, #3b82f6, #2563eb); border:none; font-weight:700; padding:8px 18px; border-radius:8px;">
                    <i class="ph-bold ph-check"></i> Asignar Ahora
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Cambiar Estado Masivo -->
<div class="modal-overlay" id="modalBulkStatus" style="z-index:19005;">
    <div class="modal-content" style="max-width:440px; width:95%; border-radius:18px;">
        <div class="modal-header">
            <h3 style="display:flex; align-items:center; gap:8px; margin:0; font-size:1.15rem; font-weight:700;">
                <i class="ph-bold ph-tag" style="color:#8b5cf6;"></i>
                <span>Cambiar Estado en Lote</span>
            </h3>
            <button type="button" class="close-btn" onclick="closeBulkStatusModal()">&times;</button>
        </div>
        <form id="formBulkStatus" onsubmit="submitBulkStatus(event)">
            <div class="modal-body" style="padding:20px;">
                <div style="background:rgba(139,92,246,0.12); border:1px solid rgba(139,92,246,0.3); color:#c4b5fd; font-size:0.85rem; border-radius:10px; padding:10px 14px; margin-bottom:15px; display:flex; align-items:center; gap:8px;">
                    <i class="ph-bold ph-info" style="font-size:1.2rem; flex-shrink:0;"></i>
                    <span>Se actualizará el estado de <strong id="bulkStatusItemCount">0</strong> SKU(s).</span>
                </div>
                <div class="form-group mb-3">
                    <label style="font-weight:700; margin-bottom:6px; display:block;">Nuevo Estado <span style="color:#ef4444;">*</span></label>
                    <select id="bulkStatusSelect" class="form-control" style="background:var(--bg-color); border:1px solid var(--border-color); color:var(--text-color); border-radius:8px; padding:8px 12px; width:100%;" required>
                        <option value="disponible">🟢 Disponible (Almacén)</option>
                        <option value="instalado">🟡 Instalado (Cliente)</option>
                        <option value="malogrado">🔴 Malogrado / Averiado</option>
                        <option value="observado">🟠 Observado</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer" style="padding:14px 20px; border-top:1px solid var(--border-color); display:flex; gap:10px; justify-content:flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeBulkStatusModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnSubmitBulkStatus" style="background:linear-gradient(135deg, #8b5cf6, #7c3aed); border:none; font-weight:700; padding:8px 18px; border-radius:8px;">
                    <i class="ph-bold ph-check"></i> Aplicar Cambio
                </button>
            </div>
        </form>
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
    var agm = document.getElementById('assignGroupedModal');
    if (agm && agm.parentElement !== document.body) {
        document.body.appendChild(agm);
    }
    
    // Abrir Escáner con la tecla 'K'
    document.addEventListener('keydown', function(e) {
        if (e.key && e.key.toLowerCase() === 'k' && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') {
            const escanerBtn = document.querySelector('button[data-tab="escaner"]');
            if (escanerBtn) {
                escanerBtn.click();
            }
        }
    });
});
</script>

<?php include '../../includes/footer.php'; ?>
