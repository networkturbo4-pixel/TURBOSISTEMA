<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
require_once '../../../config/db.php';
requireLogin();
requirePermission($pdo, 'inventario');
include '../../../includes/header.php';
include '../../../includes/sidebar.php';
?>

<link rel="stylesheet" href="<?php echo BASE_URL; ?>/modules/inventario/inventario.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/modules/inventario/historial/historial.css?v=<?php echo time(); ?>">

<div class="hist-container">
    <!-- Top Submodule Header -->
    <div class="hist-header-card">
        <div class="hist-header-left">
            <div class="hist-header-icon">
                <i class="ph ph-clock-counter-clockwise"></i>
            </div>
            <div class="hist-header-info">
                <h2>Historial & Trazabilidad de Inventario</h2>
                <p>Búsqueda 360°, métricas en tiempo real, movimientos y gestión de compras con facturas.</p>
            </div>
        </div>
        <div class="hist-header-actions">
            <button type="button" class="btn-hist-purchases" onclick="showAllPurchasesView()" title="Ver historial global de compras">
                <i class="ph ph-receipt"></i> Todas las Compras
            </button>
            <button type="button" class="btn-new-purchase-cta" onclick="openNewPurchaseModal()" title="Registrar una nueva compra con factura">
                <i class="ph ph-plus-circle"></i> + Nueva Compra
            </button>
        </div>
    </div>

    <!-- Spotlight Search Hub (Center Prominent Search) -->
    <div class="hist-search-section" id="histSearchSection">
        <div class="hist-search-box">
            <div class="hist-search-input-wrap">
                <i class="ph ph-magnifying-glass search-main-icon"></i>
                <input type="text" id="histMainSearch" class="hist-search-input" placeholder="Buscar por nombre, SKU, serie o escanear..." autocomplete="off" autofocus>
                <div class="hist-search-actions">
                    <button type="button" class="btn-scan-cam" id="btnScanCam" onclick="openCameraScannerModal()" title="Escanear con cámara">
                        <i class="ph ph-camera"></i> <span>Escanear</span>
                    </button>
                    <button type="button" class="btn-clear-search" id="btnClearSearch" onclick="clearSearch()" style="display:none;" title="Limpiar">
                        <i class="ph ph-x"></i>
                    </button>
                </div>
            </div>
            <!-- Live Suggestions Dropdown -->
            <div class="hist-suggestions-popover" id="histSuggestions"></div>
        </div>
        <div class="hist-search-hints">
            <span><i class="ph ph-barcode"></i> Soporte de lector láser directo</span>
            <span><i class="ph ph-lightning"></i> Búsqueda en tiempo real</span>
            <span><i class="ph ph-tag"></i> SKUs, Series, MAC y Nombres</span>
        </div>
    </div>

    <!-- State 1: Empty Initial / Placeholder State -->
    <div class="hist-empty-placeholder" id="histEmptyPlaceholder">
        <div class="hist-placeholder-icon">
            <i class="ph ph-package"></i>
        </div>
        <h3>Escanea o busca un producto</h3>
        <p>Utiliza el buscador central para consultar el stock disponible, fechas clave, historial completo de movimientos e instalaciones en clientes.</p>
        <div class="hist-quick-pills" id="histQuickPills">
            <!-- Dynamically loaded recent products pills -->
        </div>
    </div>

    <!-- State 2: 360° Product Details Card & Timeline -->
    <div class="hist-product-view" id="histProductView" style="display:none;">
        <!-- Product Header Card -->
        <div class="hist-prod-hero-card">
            <div class="hist-prod-hero-main">
                <div class="hist-prod-img-box" id="histProdImgBox">
                    <img id="histProdImg" src="" alt="Producto" class="hist-prod-img">
                </div>
                <div class="hist-prod-details">
                    <div class="hist-prod-badges" id="histProdBadges"></div>
                    <h1 class="hist-prod-title" id="histProdTitle">Nombre del Producto</h1>
                    <div class="hist-prod-subinfo">
                        <span id="histProdCat"><i class="ph ph-folder"></i> Categoría</span>
                        <span id="histProdSku"><i class="ph ph-barcode"></i> SKU: -</span>
                        <span id="histProdCost"><i class="ph ph-money"></i> Costo Ref: S/ 0.00</span>
                    </div>
                    <!-- Active Scanned SKU / EPP Assignment Banner -->
                    <div id="histActiveSkuBanner" class="hist-active-sku-banner" style="display:none;"></div>
                </div>
            </div>
            <div class="hist-prod-hero-side">
                <div class="hist-date-card">
                    <div class="hist-date-item">
                        <span class="hist-date-label"><i class="ph ph-calendar-plus"></i> Fecha de Registro</span>
                        <strong class="hist-date-value" id="histDateCreated">-</strong>
                    </div>
                    <div class="hist-date-item">
                        <span class="hist-date-label"><i class="ph ph-clock-afternoon"></i> Última Actividad</span>
                        <strong class="hist-date-value highlight" id="histDateActivity">-</strong>
                    </div>
                </div>
                <button type="button" class="btn-hero-add-purchase" onclick="openNewPurchaseModalForCurrentProduct()">
                    <i class="ph ph-shopping-cart-simple"></i> Registrar Compra
                </button>
            </div>
        </div>

        <!-- Metric Cards Row -->
        <div class="hist-metrics-grid">
            <div class="hist-metric-card metric-total">
                <div class="hist-metric-icon"><i class="ph ph-stack"></i></div>
                <div class="hist-metric-content">
                    <span class="hist-metric-num" id="metricTotal">0</span>
                    <span class="hist-metric-name">Total Registrado</span>
                </div>
            </div>
            <div class="hist-metric-card metric-disp">
                <div class="hist-metric-icon"><i class="ph ph-check-circle"></i></div>
                <div class="hist-metric-content">
                    <span class="hist-metric-num" id="metricDisp">0</span>
                    <span class="hist-metric-name">Stock Disponible</span>
                </div>
            </div>
            <div class="hist-metric-card metric-inst">
                <div class="hist-metric-icon"><i class="ph ph-house-line"></i></div>
                <div class="hist-metric-content">
                    <span class="hist-metric-num" id="metricInst">0</span>
                    <span class="hist-metric-name">Instalados en Clientes</span>
                </div>
            </div>
            <div class="hist-metric-card metric-malo">
                <div class="hist-metric-icon"><i class="ph ph-warning-circle"></i></div>
                <div class="hist-metric-content">
                    <span class="hist-metric-num" id="metricMalo">0</span>
                    <span class="hist-metric-name">Malogrados / Bajas</span>
                </div>
            </div>
            <div class="hist-metric-card metric-obs">
                <div class="hist-metric-icon"><i class="ph ph-eye"></i></div>
                <div class="hist-metric-content">
                    <span class="hist-metric-num" id="metricObs">0</span>
                    <span class="hist-metric-name">En Observación</span>
                </div>
            </div>
            <div class="hist-metric-card metric-tech" onclick="switchProductTab('tech')">
                <div class="hist-metric-icon"><i class="ph ph-hard-hat"></i></div>
                <div class="hist-metric-content">
                    <span class="hist-metric-num" id="metricTech">0</span>
                    <span class="hist-metric-name">En Personal / EPP</span>
                </div>
            </div>
        </div>

        <!-- Tabs Section: Movimientos, Compras, Instalaciones, Técnicos -->
        <div class="hist-tabs-card">
            <div class="hist-tabs-nav">
                <button type="button" class="hist-tab-btn active" data-ptab="timeline" onclick="switchProductTab('timeline')">
                    <i class="ph ph-clock-counter-clockwise"></i> Línea de Tiempo & Movimientos
                </button>
                <button type="button" class="hist-tab-btn" data-ptab="purchases" onclick="switchProductTab('purchases')">
                    <i class="ph ph-receipt"></i> Historial de Compras & Facturas (<span id="prodPurchasesCount">0</span>)
                </button>
                <button type="button" class="hist-tab-btn" data-ptab="installations" onclick="switchProductTab('installations')">
                    <i class="ph ph-house-line"></i> Instalaciones en Clientes
                </button>
                <button type="button" class="hist-tab-btn" data-ptab="tech" onclick="switchProductTab('tech')">
                    <i class="ph ph-hard-hat"></i> Asignado a Personal & EPP (<span id="prodTechCount">0</span>)
                </button>
            </div>

            <!-- Tab Pane 1: Unified Timeline -->
            <div class="hist-tab-pane active" id="ptab-timeline">
                <div class="hist-pane-toolbar">
                    <div class="hist-timeline-filters" id="timelineFilters">
                        <button type="button" class="tl-filter-chip active" data-filter="all" onclick="filterTimeline('all')">Todos</button>
                        <button type="button" class="tl-filter-chip" data-filter="stock_entry" onclick="filterTimeline('stock_entry')">Ingresos de Stock</button>
                        <button type="button" class="tl-filter-chip" data-filter="assignment" onclick="filterTimeline('assignment')">Asignaciones & EPP</button>
                        <button type="button" class="tl-filter-chip" data-filter="installation" onclick="filterTimeline('installation')">Instalaciones</button>
                        <button type="button" class="tl-filter-chip" data-filter="purchase" onclick="filterTimeline('purchase')">Compras</button>
                        <button type="button" class="tl-filter-chip" data-filter="scan" onclick="filterTimeline('scan')">Escaneos</button>
                    </div>
                </div>
                <div class="hist-timeline-wrap" id="histTimelineWrap">
                    <!-- Timeline items injected dynamically -->
                </div>
            </div>

            <!-- Tab Pane 2: Product Purchases -->
            <div class="hist-tab-pane" id="ptab-purchases">
                <div class="hist-purchases-header">
                    <h3>Compras Registradas para este Producto</h3>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="openNewPurchaseModalForCurrentProduct()">
                        <i class="ph ph-plus"></i> Añadir Compra
                    </button>
                </div>
                <div class="table-responsive hist-table-wrap">
                    <table class="inv-table inv-table-modern" id="prodPurchasesTable">
                        <thead>
                            <tr>
                                <th>Fecha & Hora</th>
                                <th>Proveedor</th>
                                <th>N° Factura / Boleta</th>
                                <th class="text-center">Cantidad</th>
                                <th class="text-end">Costo Unit.</th>
                                <th class="text-end">Total</th>
                                <th class="text-center">Comprobante</th>
                                <th>Registrado por</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="prodPurchasesTableBody">
                            <!-- Injected dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab Pane 3: Installations in Actas -->
            <div class="hist-tab-pane" id="ptab-installations">
                <div class="table-responsive hist-table-wrap">
                    <table class="inv-table inv-table-modern" id="prodInstTable">
                        <thead>
                            <tr>
                                <th>Fecha Instalación</th>
                                <th>N° Acta / Folio</th>
                                <th>Cliente</th>
                                <th>Dirección</th>
                                <th>Serie / MAC / Detalle</th>
                                <th>Técnico</th>
                            </tr>
                        </thead>
                        <tbody id="prodInstTableBody">
                            <!-- Injected dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab Pane 4: Assigned Technicians & EPP -->
            <div class="hist-tab-pane" id="ptab-tech">
                <div class="table-responsive hist-table-wrap">
                    <table class="inv-table inv-table-modern" id="prodTechTable">
                        <thead>
                            <tr>
                                <th>Personal / Custodio</th>
                                <th>Rol / Cargo</th>
                                <th class="text-center">Tipo Asignación</th>
                                <th class="text-center">Cantidad en Posesión</th>
                                <th>SKUs / Tallas / Detalle</th>
                            </tr>
                        </thead>
                        <tbody id="prodTechTableBody">
                            <!-- Injected dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- State 3: Global Purchases Log (All Purchases View) -->
    <div class="hist-all-purchases-view" id="histAllPurchasesView" style="display:none;">
        <div class="hist-purchases-topbar">
            <div class="hist-purchases-title">
                <button type="button" class="btn-back-to-search" onclick="backToProductSearch()" title="Volver a búsqueda">
                    <i class="ph ph-arrow-left"></i>
                </button>
                <div>
                    <h2>Registro General de Compras & Facturas</h2>
                    <p>Auditoría consolidada de todas las compras de inventario con comprobantes adjuntos.</p>
                </div>
            </div>
            <div class="hist-purchases-top-actions">
                <button type="button" class="btn-new-purchase-cta" onclick="openNewPurchaseModal()">
                    <i class="ph ph-plus-circle"></i> + Nueva Compra
                </button>
            </div>
        </div>

        <!-- Summary KPI Cards for Purchases -->
        <div class="hist-purchases-kpi-row">
            <div class="hist-kpi-card">
                <span class="hist-kpi-label">Total Facturas / Compras</span>
                <strong class="hist-kpi-num" id="kpiPurchasesCount">0</strong>
            </div>
            <div class="hist-kpi-card">
                <span class="hist-kpi-label">Inversión Total Registrada</span>
                <strong class="hist-kpi-num highlight" id="kpiPurchasesAmount">S/ 0.00</strong>
            </div>
        </div>

        <!-- Filters Bar for Purchases -->
        <div class="hist-purchases-filterbar">
            <div class="hist-pf-group">
                <label>Buscar</label>
                <input type="text" id="filterPurchasesSearch" class="form-control form-control-sm" placeholder="Proveedor, N° factura, producto...">
            </div>
            <div class="hist-pf-group">
                <label>Desde</label>
                <input type="date" id="filterPurchasesDateFrom" class="form-control form-control-sm">
            </div>
            <div class="hist-pf-group">
                <label>Hasta</label>
                <input type="date" id="filterPurchasesDateTo" class="form-control form-control-sm">
            </div>
            <button type="button" class="btn btn-secondary btn-sm" onclick="loadAllPurchases()"><i class="ph ph-funnel"></i> Filtrar</button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="clearPurchasesFilters()"><i class="ph ph-x"></i> Limpiar</button>
        </div>

        <!-- All Purchases Table -->
        <div class="table-responsive hist-table-wrap">
            <table class="inv-table inv-table-modern" id="allPurchasesTable">
                <thead>
                    <tr>
                        <th>Fecha & Hora</th>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th>Proveedor</th>
                        <th>N° Factura / Boleta</th>
                        <th class="text-center">Cantidad</th>
                        <th class="text-end">Costo Unit.</th>
                        <th class="text-end">Total</th>
                        <th class="text-center">Comprobante</th>
                        <th>Registrado por</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody id="allPurchasesTableBody">
                    <!-- Injected dynamically -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal 1: Registrar Nueva Compra -->
<div class="inv-modal-backdrop" id="modalNewPurchase">
    <div class="inv-modal-dialog" style="max-width: 680px;">
        <div class="inv-modal-header">
            <div class="inv-modal-title">
                <i class="ph ph-receipt" style="color:#f59e0b;"></i>
                <span>Registrar Compra & Factura</span>
            </div>
            <button type="button" class="inv-modal-close" onclick="closeNewPurchaseModal()">&times;</button>
        </div>
        <form id="formNewPurchase" onsubmit="submitNewPurchase(event)" enctype="multipart/form-data" style="display: flex; flex-direction: column; flex: 1 1 auto; min-height: 0; overflow: hidden; margin: 0;">
            <div class="inv-modal-body" style="flex: 1 1 auto; overflow-y: auto; max-height: calc(90vh - 130px);">
                <div class="row g-3">
                    <!-- Producto -->
                    <div class="col-12">
                        <label class="form-label" style="font-weight:700;">Producto <span class="text-danger">*</span></label>
                        <select name="product_id" id="purchaseProductId" class="form-select" required>
                            <option value="">-- Selecciona el producto --</option>
                        </select>
                    </div>

                    <!-- Proveedor & N° Factura -->
                    <div class="col-md-6">
                        <label class="form-label" style="font-weight:600;">Proveedor / Distribuidor</label>
                        <input type="text" name="supplier_name" id="purchaseSupplier" class="form-control" placeholder="Ej. Distribuidora Telecom S.A.C.">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" style="font-weight:600;">N° de Factura / Boleta / Guía</label>
                        <input type="text" name="invoice_number" id="purchaseInvoice" class="form-control" placeholder="Ej. F001-0004829">
                    </div>

                    <!-- Fecha y Hora de Compra -->
                    <div class="col-md-6">
                        <label class="form-label" style="font-weight:600;">Fecha y Hora de Compra <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="purchase_date" id="purchaseDate" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" style="font-weight:600;">Moneda</label>
                        <select name="currency" id="purchaseCurrency" class="form-select">
                            <option value="PEN" selected>Soles (S/ - PEN)</option>
                            <option value="USD">Dólares ($ - USD)</option>
                        </select>
                    </div>

                    <!-- Cantidad, Costo Unitario y Total -->
                    <div class="col-md-4">
                        <label class="form-label" style="font-weight:700;">Cantidad <span class="text-danger">*</span></label>
                        <input type="number" step="any" name="quantity" id="purchaseQty" class="form-control" min="0.01" placeholder="0" required oninput="calcPurchaseTotal()">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" style="font-weight:600;">Costo Unitario</label>
                        <input type="number" step="0.01" name="unit_price" id="purchaseUnitPrice" class="form-control" min="0" placeholder="0.00" oninput="calcPurchaseTotal()">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" style="font-weight:700;">Monto Total <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="total_amount" id="purchaseTotal" class="form-control" min="0" placeholder="0.00" required>
                    </div>

                    <!-- Subir Comprobante / Factura -->
                    <div class="col-12">
                        <label class="form-label" style="font-weight:600;">Subir Documento o Foto de la Factura (PDF, PNG, JPG)</label>
                        <div class="hist-file-dropzone" id="facturaDropzone">
                            <input type="file" name="document_file" id="purchaseFile" class="no-dropzone" accept=".pdf,.png,.jpg,.jpeg,.webp" onchange="previewInvoiceFile(this)">
                            <div class="hist-dropzone-content" id="dropzonePrompt">
                                <i class="ph ph-file-arrow-up"></i>
                                <span>Arrastra o haz clic para subir la factura en PDF o Imagen</span>
                                <small>Formatos permitidos: PDF, JPG, PNG, WEBP (Hasta 15MB)</small>
                            </div>
                            <div class="hist-file-preview" id="dropzonePreview" style="display:none;">
                                <div class="hist-preview-info">
                                    <i class="ph ph-file-pdf" id="previewIcon"></i>
                                    <span id="previewFileName">archivo.pdf</span>
                                </div>
                                <button type="button" class="btn-remove-file" onclick="removeInvoiceFile()">&times;</button>
                            </div>
                        </div>
                    </div>

                    <!-- Auto-aumento de stock -->
                    <div class="col-12">
                        <div class="form-check p-3" style="background:var(--bg-color); border:1px solid var(--border-color); border-radius:10px;">
                            <input class="form-check-input" type="checkbox" name="increase_stock" value="1" id="purchaseIncreaseStock" checked>
                            <label class="form-check-label" for="purchaseIncreaseStock" style="font-weight:600; cursor:pointer;">
                                Aumentar el stock del producto automáticamente con esta cantidad ingresada
                            </label>
                            <small class="text-muted d-block" style="margin-left: 24px;">Si está marcado, incrementará la cantidad total del producto en el inventario central.</small>
                        </div>
                    </div>

                    <!-- Notas -->
                    <div class="col-12">
                        <label class="form-label" style="font-weight:600;">Notas u Observaciones</label>
                        <textarea name="notes" id="purchaseNotes" class="form-control" rows="2" placeholder="Detalles adicionales de la compra o condiciones de garantía..."></textarea>
                    </div>
                </div>
            </div>
            <div class="inv-modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeNewPurchaseModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnSubmitPurchase" style="background:linear-gradient(135deg, #f59e0b, #d97706); border:none; padding:10px 20px; font-weight:700; border-radius:10px; display:inline-flex; align-items:center; gap:8px;">
                    <i class="ph ph-floppy-disk"></i> Guardar Compra
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: Visor de Factura / Documento -->
<div class="inv-modal-backdrop" id="modalDocViewer">
    <div class="inv-modal-dialog" style="max-width: 900px; height: 90vh; display: flex; flex-direction: column;">
        <div class="inv-modal-header">
            <div class="inv-modal-title">
                <i class="ph ph-file-text" style="color:#6366f1;"></i>
                <span id="docViewerTitle">Comprobante de Compra</span>
            </div>
            <div style="display:flex; gap:8px; align-items:center;">
                <a id="btnDownloadDoc" href="#" target="_blank" download class="btn btn-secondary btn-sm" title="Descargar archivo">
                    <i class="ph ph-download-simple"></i> Descargar
                </a>
                <button type="button" class="inv-modal-close" onclick="closeDocViewer()">&times;</button>
            </div>
        </div>
        <div class="inv-modal-body" style="flex:1; padding:0; overflow:hidden; background:#111; display:flex; align-items:center; justify-content:center;">
            <iframe id="docViewerIframe" src="" style="width:100%; height:100%; border:none; display:none;"></iframe>
            <img id="docViewerImg" src="" alt="Factura" style="max-width:100%; max-height:100%; object-fit:contain; display:none; border-radius:8px;">
        </div>
    </div>
</div>

<!-- Modal 3: Escáner con Cámara -->
<div class="inv-modal-backdrop" id="modalCameraScanner">
    <div class="inv-modal-dialog" style="max-width: 480px;">
        <div class="inv-modal-header">
            <div class="inv-modal-title">
                <i class="ph ph-camera" style="color:#8b5cf6;"></i>
                <span>Escanear Código con Cámara</span>
            </div>
            <button type="button" class="inv-modal-close" onclick="closeCameraScannerModal()">&times;</button>
        </div>
        <div class="inv-modal-body text-center" style="padding: 20px;">
            <div id="cameraScannerVideoContainer" style="position:relative; width:100%; max-width:380px; height:280px; margin:0 auto; background:#000; border-radius:12px; overflow:hidden; display:flex; align-items:center; justify-content:center;">
                <video id="cameraScannerVideo" playsinline style="width:100%; height:100%; object-fit:cover;"></video>
                <div class="scanner-laser-line"></div>
            </div>
            <p class="text-muted mt-3" style="font-size:0.85rem;">Apunta la cámara al código de barras o QR del producto.</p>
        </div>
    </div>
</div>

<script src="<?php echo BASE_URL; ?>/modules/inventario/historial/historial.js?v=<?php echo time(); ?>"></script>

<?php include '../../../includes/footer.php'; ?>
