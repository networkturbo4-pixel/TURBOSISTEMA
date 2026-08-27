<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
require_once __DIR__ . '/../../../config/db.php';
requireLogin();
requirePermission($pdo, 'inventario');

// Obtener estadísticas dinámicas de activos empresariales
$totalActivos = 0;
$totalVehiculos = 0;
$totalTecnologia = 0;
$totalMobiliario = 0;
$totalHerramientas = 0;
$totalMantenimiento = 0;

try {
    $stmtStats = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN LOWER(categoria) = 'vehiculo' OR LOWER(tipo) IN ('auto','camion','motocicleta') THEN 1 ELSE 0 END) as vehiculos,
            SUM(CASE WHEN LOWER(categoria) = 'tecnologia' OR LOWER(tipo) IN ('monitor','laptop','desktop','pc','servidor','impresora','switch','router') THEN 1 ELSE 0 END) as tecnologia,
            SUM(CASE WHEN LOWER(categoria) = 'mobiliario' OR LOWER(tipo) IN ('escritorio','silla','archivador','estante','mueble','mesa') THEN 1 ELSE 0 END) as mobiliario,
            SUM(CASE WHEN LOWER(categoria) = 'herramientas' OR LOWER(tipo) IN ('fusionadora','otdr','escalera','taladro','herramienta') THEN 1 ELSE 0 END) as herramientas,
            SUM(CASE WHEN LOWER(estado) IN ('mantenimiento', 'taller', 'reparacion') THEN 1 ELSE 0 END) as en_mantenimiento
        FROM activos_vehiculos 
        WHERE estado != 'eliminado'
    ");
    $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);
    if ($stats) {
        $totalActivos = (int)$stats['total'];
        $totalVehiculos = (int)$stats['vehiculos'];
        $totalTecnologia = (int)$stats['tecnologia'];
        $totalMobiliario = (int)$stats['mobiliario'];
        $totalHerramientas = (int)$stats['herramientas'];
        $totalMantenimiento = (int)$stats['en_mantenimiento'];
    }
} catch (Exception $e) {}

include __DIR__ . '/../../../includes/header.php';
include __DIR__ . '/../../../includes/sidebar.php';
?>

<link rel="stylesheet" href="<?php echo BASE_URL; ?>/modules/inventario/Activos/activos.css?v=<?php echo time(); ?>">

<div class="activos-module-wrapper">
    <!-- Cabecera Ejecutiva de Página -->
    <div class="activos-header-card">
        <div class="header-left-content">
            <div class="header-icon-box">
                <i class="ph-bold ph-buildings"></i>
            </div>
            <div class="header-text-info">
                <h2>Control de Activos Empresariales</h2>
                <p>Gestión integral de flota vehicular, equipos de cómputo, mobiliario, herramientas de campo y patrimonio corporativo.</p>
            </div>
        </div>
        <div class="header-actions">
            <button type="button" class="btn btn-add-vehicle" onclick="openNewAssetModal()">
                <i class="ph-bold ph-plus-circle"></i> Añadir Activo
            </button>
        </div>
    </div>

    <!-- Mini KPI Stats Bar (Filtros Rápidos por Categoría) -->
    <div class="activos-stats-bar">
        <div class="stat-pill stat-all active" onclick="filterByCategoryQuick('', this)">
            <div class="stat-icon"><i class="ph-bold ph-squares-four"></i></div>
            <div class="stat-info">
                <span class="stat-val" id="kpiTotalActivos"><?php echo $totalActivos; ?></span>
                <span class="stat-lbl">Total Activos</span>
            </div>
        </div>
        <div class="stat-pill" onclick="filterByCategoryQuick('vehiculo', this)">
            <div class="stat-icon icon-orange"><i class="ph-bold ph-car-profile"></i></div>
            <div class="stat-info">
                <span class="stat-val" id="kpiTotalVehiculos"><?php echo $totalVehiculos; ?></span>
                <span class="stat-lbl">Vehículos</span>
            </div>
        </div>
        <div class="stat-pill" onclick="filterByCategoryQuick('tecnologia', this)">
            <div class="stat-icon icon-blue"><i class="ph-bold ph-desktop"></i></div>
            <div class="stat-info">
                <span class="stat-val" id="kpiTotalTecnologia"><?php echo $totalTecnologia; ?></span>
                <span class="stat-lbl">Tecnología & PC</span>
            </div>
        </div>
        <div class="stat-pill" onclick="filterByCategoryQuick('mobiliario', this)">
            <div class="stat-icon icon-purple"><i class="ph-bold ph-armchair"></i></div>
            <div class="stat-info">
                <span class="stat-val" id="kpiTotalMobiliario"><?php echo $totalMobiliario; ?></span>
                <span class="stat-lbl">Mobiliario</span>
            </div>
        </div>
        <div class="stat-pill" onclick="filterByCategoryQuick('herramientas', this)">
            <div class="stat-icon icon-emerald"><i class="ph-bold ph-hammer"></i></div>
            <div class="stat-info">
                <span class="stat-val" id="kpiTotalHerramientas"><?php echo $totalHerramientas; ?></span>
                <span class="stat-lbl">Herramientas / Red</span>
            </div>
        </div>
        <div class="stat-pill" onclick="filterByStatusQuick('mantenimiento', this)">
            <div class="stat-icon icon-amber"><i class="ph-bold ph-wrench"></i></div>
            <div class="stat-info">
                <span class="stat-val" id="kpiTotalMantenimiento"><?php echo $totalMantenimiento; ?></span>
                <span class="stat-lbl">En Mantenimiento</span>
            </div>
        </div>
    </div>

    <!-- Toolbar de Búsqueda y Filtros -->
    <div class="activos-toolbar-card">
        <div class="activos-search-wrap">
            <i class="ph-bold ph-magnifying-glass search-icon"></i>
            <input type="text" id="searchVehicles" class="form-control" placeholder="Buscar por código, placa, nombre, marca, modelo o responsable...">
            <button type="button" id="btnClearSearch" class="btn-clear-search" style="display:none;" onclick="clearSearchInput()">&times;</button>
        </div>
        <div class="activos-filter-group">
            <div class="filter-select-wrap">
                <i class="ph-bold ph-funnel filter-icon"></i>
                <select class="custom-select-filter" id="filterCategory">
                    <option value="">Todas las Categorías</option>
                    <option value="vehiculo">🚗 Vehículos & Flota</option>
                    <option value="tecnologia">💻 Tecnología & Cómputo</option>
                    <option value="mobiliario">🪑 Mobiliario & Oficina</option>
                    <option value="herramientas">🛠️ Herramientas & Red</option>
                    <option value="otro">📦 Otros Activos</option>
                </select>
            </div>
            <div class="filter-select-wrap">
                <i class="ph-bold ph-check-circle filter-icon"></i>
                <select class="custom-select-filter" id="filterStatus">
                    <option value="">Todos los estados</option>
                    <option value="activo">🟢 Activos</option>
                    <option value="mantenimiento">🟡 En Mantenimiento</option>
                    <option value="taller">🔴 En Taller / Reparación</option>
                    <option value="inactivo">⚪ Inactivos / Baja</option>
                </select>
            </div>
            <button type="button" class="btn-refresh-grid" onclick="loadVehicles()" title="Actualizar lista">
                <i class="ph-bold ph-arrows-clockwise" id="refreshIcon"></i>
            </button>
        </div>
    </div>

    <!-- Grid de Activos -->
    <div class="vehiculos-grid" id="vehiculosContainer">
        <div class="grid-loading-placeholder">
            <i class="ph-bold ph-spinner ph-spin"></i>
            <p>Cargando catálogo de activos empresariales...</p>
        </div>
    </div>
</div>

<!-- ========================================================== -->
<!-- MODAL: REGISTRAR NUEVO ACTIVO (MÁS GRANDE Y DINÁMICO)       -->
<!-- ========================================================== -->
<div class="modal-overlay" id="modalNuevoVehiculo">
    <div class="modal-content modal-vehicle-dialog modal-asset-register-dialog">
        <div class="modal-header-styled">
            <div class="detail-header-left">
                <div class="dialog-icon-badge">
                    <i class="ph-bold ph-plus-circle"></i>
                </div>
                <div class="detail-title-group">
                    <h3>Registrar Nuevo Activo Empresarial</h3>
                    <p>Selecciona la categoría del activo para desplegar sus campos correspondientes</p>
                </div>
            </div>
            <button type="button" class="btn-dialog-close" onclick="closeModal('modalNuevoVehiculo')" title="Cerrar">&times;</button>
        </div>

        <div class="modal-body-styled">
            <form id="formNuevoVehiculo">
                <!-- 1. SELECTOR VISUAL DE MACRO-CATEGORÍA -->
                <div class="form-group-custom mb-3">
                    <label class="form-label-custom">Categoría del Activo <span class="req">*</span></label>
                    <input type="hidden" id="nvCategoria" value="vehiculo" required>
                    <div class="category-selector-cards">
                        <div class="cat-card-opt active" data-cat="vehiculo" onclick="selectAssetCategory('vehiculo', this)">
                            <div class="cat-card-icon"><i class="ph-bold ph-car-profile"></i></div>
                            <span>Vehículo</span>
                        </div>
                        <div class="cat-card-opt" data-cat="tecnologia" onclick="selectAssetCategory('tecnologia', this)">
                            <div class="cat-card-icon"><i class="ph-bold ph-desktop"></i></div>
                            <span>Tecnología / PC</span>
                        </div>
                        <div class="cat-card-opt" data-cat="mobiliario" onclick="selectAssetCategory('mobiliario', this)">
                            <div class="cat-card-icon"><i class="ph-bold ph-armchair"></i></div>
                            <span>Mobiliario</span>
                        </div>
                        <div class="cat-card-opt" data-cat="herramientas" onclick="selectAssetCategory('herramientas', this)">
                            <div class="cat-card-icon"><i class="ph-bold ph-hammer"></i></div>
                            <span>Herramientas</span>
                        </div>
                        <div class="cat-card-opt" data-cat="otro" onclick="selectAssetCategory('otro', this)">
                            <div class="cat-card-icon"><i class="ph-bold ph-package"></i></div>
                            <span>Otro Activo</span>
                        </div>
                    </div>
                </div>

                <!-- 2. SECCIÓN DINÁMICA: CAMPOS POR CATEGORÍA -->
                <div id="dynamicAssetFieldsContainer" class="dynamic-fields-wrapper">
                    <!-- Los campos dinámicos se renderizan y alternan vía JS -->
                </div>

                <!-- 3. CAMPOS GENERALES COMUNES (Estado, Ubicación, Responsable, Costo, Fecha) -->
                <div class="asset-general-section mt-3 pt-3 border-top">
                    <h5 class="section-subtitle mb-3"><i class="ph-bold ph-sliders"></i> Asignación y Estado</h5>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6 col-12">
                            <div class="form-group-custom">
                                <label class="form-label-custom">Ubicación / Área / Oficina</label>
                                <div class="input-with-icon">
                                    <i class="ph-bold ph-map-pin"></i>
                                    <input type="text" class="form-control" id="nvUbicacion" placeholder="Ej: Oficina Central - Piso 2 / Almacén">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-12">
                            <div class="form-group-custom">
                                <label class="form-label-custom">Responsable / Asignado a</label>
                                <div class="input-with-icon">
                                    <i class="ph-bold ph-user"></i>
                                    <input type="text" class="form-control" id="nvResponsable" placeholder="Ej: Juan Pérez / Área Contabilidad">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4 col-12">
                            <div class="form-group-custom">
                                <label class="form-label-custom">Estado Operativo</label>
                                <select class="form-select custom-select-filter" id="nvEstado">
                                    <option value="activo" selected>🟢 Activo / Operativo</option>
                                    <option value="mantenimiento">🟡 En Mantenimiento</option>
                                    <option value="taller">🔴 En Reparación / Taller</option>
                                    <option value="inactivo">⚪ Inactivo / Baja</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4 col-12">
                            <div class="form-group-custom">
                                <label class="form-label-custom">Valor de Compra (S/.)</label>
                                <div class="input-with-icon">
                                    <i class="ph-bold ph-currency-dollar"></i>
                                    <input type="number" step="0.01" class="form-control" id="nvValor" placeholder="0.00">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 col-12">
                            <div class="form-group-custom">
                                <label class="form-label-custom">Fecha de Adquisición</label>
                                <input type="date" class="form-control" id="nvFechaAdquisicion">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. ZONA DE CARGA DE FOTOS CON PREVIEW -->
                <div class="form-group-custom mb-3 mt-3 pt-3 border-top">
                    <label class="form-label-custom">Fotografías del Activo (Opcional)</label>
                    <div class="photo-dropzone" id="nvDropzone" onclick="document.getElementById('nvFotos').click()">
                        <input type="file" id="nvFotos" name="fotos_vehiculo[]" multiple accept="image/*" style="display:none;" onchange="handleNvPhotoPreviews(this)">
                        <div class="dropzone-content">
                            <div class="dropzone-icon">
                                <i class="ph-bold ph-cloud-arrow-up"></i>
                            </div>
                            <p class="dropzone-title">Haz clic o arrastra fotos del activo aquí</p>
                            <span class="dropzone-hint">Formatos soportados: JPG, PNG, WEBP &middot; Se estampará fecha/hora automáticamente</span>
                        </div>
                    </div>
                    <div class="photo-preview-grid" id="nvPhotoPreviews"></div>
                </div>

                <div class="modal-actions-footer">
                    <button type="button" class="btn btn-cancel-action" onclick="closeModal('modalNuevoVehiculo')">Cancelar</button>
                    <button type="submit" class="btn btn-save-action" id="btnSaveNewVehicle">
                        <i class="ph-bold ph-check"></i> Guardar Activo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================== -->
<!-- MODAL: DETALLE COMPLETO DEL ACTIVO (DINÁMICO & LIGHTBOX)    -->
<!-- ========================================================== -->
<div class="modal-overlay" id="modalDetalleVehiculo">
    <div class="modal-content modal-vehicle-dialog modal-xl-detail">
        <div class="modal-header-styled detail-header-theme">
            <div class="detail-header-left">
                <div class="dialog-icon-badge" id="modalDetailIconBadge">
                    <i class="ph-bold ph-cube"></i>
                </div>
                <div class="detail-title-group">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <h3 id="modalTitlePlaca" class="mb-0">Detalle de Activo</h3>
                        <span id="lblEstadoBadge" class="status-badge badge-activo">Activo</span>
                    </div>
                    <p id="modalSubtitleVehicle" class="mb-0">Ficha técnica y control patrimonial</p>
                </div>
            </div>
            <div class="detail-header-actions">
                <button type="button" class="btn-icon-action" onclick="openEditVehiculo()" title="Editar Ficha">
                    <i class="ph-bold ph-pencil-simple"></i>
                </button>
                <button type="button" class="btn-dialog-close" onclick="closeModal('modalDetalleVehiculo')" title="Cerrar">&times;</button>
            </div>
        </div>

        <div class="modal-body-styled p-0">
            <!-- Tabs Navigation -->
            <div class="vehiculo-tabs-container">
                <div class="vehiculo-tabs-list">
                    <button type="button" class="v-tab active" data-vtab="info">
                        <i class="ph-bold ph-info"></i> Ficha & Documentos
                    </button>
                    <button type="button" class="v-tab" data-vtab="galeria">
                        <i class="ph-bold ph-images"></i> Galería de Estado
                    </button>
                    <button type="button" class="v-tab" data-vtab="mantenimiento">
                        <i class="ph-bold ph-wrench"></i> Mantenimiento / Servicio
                    </button>
                    <button type="button" class="v-tab" data-vtab="llantas" id="tabLlantasBtn" style="display:none;">
                        <i class="ph-bold ph-circle-dashed"></i> Historial Llantas
                    </button>
                </div>
            </div>

            <div class="vehiculo-tab-content-area">
                <!-- TAB 1: Info & Documentos -->
                <div class="v-tab-pane active" id="vtab-info">
                    <div class="v-info-cards-layout">
                        <!-- Ficha Técnica Dinámica -->
                        <div class="v-card-block">
                            <div class="block-title-row">
                                <h4><i class="ph-bold ph-identification-card" style="color: #f07d00;"></i> Datos Principales</h4>
                                <span id="lblEstadoBadgeTab" class="status-badge badge-activo">Activo</span>
                            </div>
                            <div class="v-specs-list" id="dynamicSpecsList">
                                <!-- Renderizado dinámico según categoría del activo -->
                            </div>
                        </div>

                        <!-- Documentos Adjuntos -->
                        <div class="v-card-block">
                            <div class="block-title-row">
                                <h4><i class="ph-bold ph-files" style="color: #0e4194;"></i> Documentos Adjuntos</h4>
                                <button type="button" class="btn btn-sm btn-action-pill" onclick="openUploadDocModal()">
                                    <i class="ph-bold ph-upload-simple"></i> Subir Doc
                                </button>
                            </div>
                            <ul class="v-doc-list-styled" id="docsList">
                                <!-- Renderizado dinámicamente -->
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: Galería de Estado -->
                <div class="v-tab-pane" id="vtab-galeria">
                    <div class="gallery-toolbar-row">
                        <h4><i class="ph-bold ph-camera" style="color: #f07d00;"></i> Galería Fotográfica de Estado</h4>
                        <button type="button" class="btn btn-sm btn-action-pill btn-primary-pill" onclick="openUploadImageModal()">
                            <i class="ph-bold ph-plus"></i> Nueva Foto
                        </button>
                    </div>
                    <div class="v-gallery-grid-modern" id="galleryList">
                        <!-- Renderizado dinámicamente -->
                    </div>
                </div>

                <!-- TAB 3: Mantenimientos y Arreglos -->
                <div class="v-tab-pane" id="vtab-mantenimiento">
                    <div class="gallery-toolbar-row">
                        <h4><i class="ph-bold ph-wrench" style="color: #f59e0b;"></i> Historial de Mantenimientos y Servicios</h4>
                        <button type="button" class="btn btn-sm btn-action-pill" onclick="openNewEventModal('mantenimiento')">
                            <i class="ph-bold ph-plus"></i> Registrar Servicio
                        </button>
                    </div>
                    <div class="table-responsive table-custom-container">
                        <table class="table-custom">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Tipo de Evento</th>
                                    <th>Descripción</th>
                                    <th>Costo</th>
                                    <th>Fotos</th>
                                </tr>
                            </thead>
                            <tbody id="mantenimientoHistoryTable">
                                <!-- Data -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB 4: Llantas (Solo visible para vehículos) -->
                <div class="v-tab-pane" id="vtab-llantas">
                    <div class="gallery-toolbar-row">
                        <h4><i class="ph-bold ph-circle-dashed" style="color: #8b5cf6;"></i> Historial de Cambio de Llantas</h4>
                        <button type="button" class="btn btn-sm btn-action-pill" onclick="openNewEventModal('cambio_llantas')">
                            <i class="ph-bold ph-plus"></i> Registrar Cambio
                        </button>
                    </div>
                    <div class="table-responsive table-custom-container">
                        <table class="table-custom">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Descripción</th>
                                    <th>Costo</th>
                                    <th>Registrado por</th>
                                </tr>
                            </thead>
                            <tbody id="llantasHistoryTable">
                                <!-- Data -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================== -->
<!-- MODAL: VISOR DE IMÁGENES / LIGHTBOX                        -->
<!-- ========================================================== -->
<div class="image-viewer-overlay" id="imageViewerOverlay" style="display:none;" onclick="closeImageViewer()">
    <div class="image-viewer-close" onclick="closeImageViewer()"><i class="ph-bold ph-x"></i></div>
    <div class="image-viewer-content" onclick="event.stopPropagation()">
        <img src="" id="viewerImageTarget" alt="Visualizador de imagen">
    </div>
</div>

<!-- ========================================================== -->
<!-- MODAL: EDITAR ACTIVO EMPRESARIAL                          -->
<!-- ========================================================== -->
<div class="modal-overlay" id="modalEditarVehiculo" style="z-index: 9002;">
    <div class="modal-content modal-vehicle-dialog modal-asset-register-dialog">
        <div class="modal-header-styled">
            <div class="dialog-icon-badge">
                <i class="ph-bold ph-pencil-simple"></i>
            </div>
            <div>
                <h3>Editar Activo Empresarial</h3>
                <p>Modifica los datos, ubicación, estado y responsable del activo</p>
            </div>
            <button type="button" class="btn-dialog-close" onclick="closeModal('modalEditarVehiculo')">&times;</button>
        </div>
        <div class="modal-body-styled">
            <form id="formEditarVehiculo">
                <input type="hidden" id="evnCategoria" value="vehiculo">
                <div class="row g-3 mb-3">
                    <div class="col-md-6 col-12">
                        <div class="form-group-custom">
                            <label class="form-label-custom">Nombre / Título del Activo <span class="req">*</span></label>
                            <input type="text" class="form-control" id="evnNombre" required>
                        </div>
                    </div>
                    <div class="col-md-6 col-12">
                        <div class="form-group-custom">
                            <label class="form-label-custom">Código / Placa / Serial <span class="req">*</span></label>
                            <input type="text" class="form-control text-uppercase font-monospace plate-input" id="evnPlaca" required maxlength="50">
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4 col-12">
                        <div class="form-group-custom">
                            <label class="form-label-custom">Tipo de Activo</label>
                            <input type="text" class="form-control" id="evnTipo">
                        </div>
                    </div>
                    <div class="col-md-4 col-12">
                        <div class="form-group-custom">
                            <label class="form-label-custom">Marca</label>
                            <input type="text" class="form-control" id="evnMarca">
                        </div>
                    </div>
                    <div class="col-md-4 col-12">
                        <div class="form-group-custom">
                            <label class="form-label-custom">Modelo</label>
                            <input type="text" class="form-control" id="evnModelo">
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6 col-12">
                        <div class="form-group-custom">
                            <label class="form-label-custom">Ubicación / Área</label>
                            <input type="text" class="form-control" id="evnUbicacion">
                        </div>
                    </div>
                    <div class="col-md-6 col-12">
                        <div class="form-group-custom">
                            <label class="form-label-custom">Responsable / Asignado a</label>
                            <input type="text" class="form-control" id="evnResponsable">
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6 col-12">
                        <div class="form-group-custom">
                            <label class="form-label-custom">Estado</label>
                            <select class="form-select custom-select-filter" id="evnEstado" required>
                                <option value="activo">🟢 Activo</option>
                                <option value="mantenimiento">🟡 En Mantenimiento</option>
                                <option value="taller">🔴 En Reparación / Taller</option>
                                <option value="inactivo">⚪ Inactivo / Baja</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6 col-12">
                        <div class="form-group-custom">
                            <label class="form-label-custom">Valor Adquisición (S/.)</label>
                            <input type="number" step="0.01" class="form-control" id="evnValor">
                        </div>
                    </div>
                </div>

                <div class="modal-actions-footer d-flex justify-content-between">
                    <button type="button" class="btn text-danger btn-archive" onclick="archiveVehicle()">
                        <i class="ph-bold ph-archive"></i> Archivar
                    </button>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-cancel-action" onclick="closeModal('modalEditarVehiculo')">Cancelar</button>
                        <button type="submit" class="btn btn-save-action">Guardar Cambios</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================== -->
<!-- MODAL: SUBIR DOCUMENTO                                    -->
<!-- ========================================================== -->
<div class="modal-overlay" id="modalSubirDoc" style="z-index: 9002;">
    <div class="modal-content modal-vehicle-dialog">
        <div class="modal-header-styled">
            <div class="dialog-icon-badge">
                <i class="ph-bold ph-file-plus"></i>
            </div>
            <div>
                <h3>Subir Documento del Activo</h3>
                <p>Factura, Acta de Entrega, SOAT, Garantía, etc.</p>
            </div>
            <button type="button" class="btn-dialog-close" onclick="closeModal('modalSubirDoc')">&times;</button>
        </div>
        <div class="modal-body-styled">
            <form id="formSubirDoc">
                <div class="form-group-custom mb-3">
                    <label class="form-label-custom">Tipo de Documento <span class="req">*</span></label>
                    <select class="form-select custom-select-filter" id="docTipo" required>
                        <option value="factura">Factura / Boleta de Compra</option>
                        <option value="acta_entrega">Acta de Asignación / Entrega</option>
                        <option value="garantia">Certificado de Garantía</option>
                        <option value="soat">SOAT / Póliza de Seguro</option>
                        <option value="tarjeta_propiedad">Tarjeta de Propiedad / Registro</option>
                        <option value="brevete">Brevete / Licencia</option>
                        <option value="revision_tecnica">Revisión Técnica / Calibración</option>
                        <option value="guia_remision">Guía de Remisión</option>
                        <option value="otro">Otro Documento</option>
                    </select>
                </div>
                <div class="form-group-custom mb-3">
                    <label class="form-label-custom">Título / Descripción <span class="req">*</span></label>
                    <input type="text" class="form-control" id="docTitulo" required placeholder="Ej: Factura de compra F001-9821">
                </div>
                <div class="form-group-custom mb-3">
                    <label class="form-label-custom">Archivo (PDF o Imagen) <span class="req">*</span></label>
                    <input type="file" class="form-control" id="docArchivo" name="archivo" accept=".pdf,image/*" required>
                </div>
                <div class="modal-actions-footer">
                    <button type="button" class="btn btn-cancel-action" onclick="closeModal('modalSubirDoc')">Cancelar</button>
                    <button type="submit" class="btn btn-save-action">Subir Documento</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================== -->
<!-- MODAL: EDITAR DOCUMENTO                                    -->
<!-- ========================================================== -->
<div class="modal-overlay" id="modalEditarDoc" style="z-index: 9002;">
    <div class="modal-content modal-vehicle-dialog">
        <div class="modal-header-styled">
            <div class="dialog-icon-badge">
                <i class="ph-bold ph-pencil-simple"></i>
            </div>
            <div>
                <h3>Editar Documento</h3>
                <p>Modifica el tipo y descripción del documento</p>
            </div>
            <button type="button" class="btn-dialog-close" onclick="closeModal('modalEditarDoc')">&times;</button>
        </div>
        <div class="modal-body-styled">
            <form id="formEditarDoc">
                <input type="hidden" id="edDocId">
                <div class="form-group-custom mb-3">
                    <label class="form-label-custom">Tipo de Documento <span class="req">*</span></label>
                    <select class="form-select custom-select-filter" id="edDocTipo" required>
                        <option value="factura">Factura / Boleta de Compra</option>
                        <option value="acta_entrega">Acta de Asignación / Entrega</option>
                        <option value="garantia">Certificado de Garantía</option>
                        <option value="soat">SOAT / Póliza</option>
                        <option value="tarjeta_propiedad">Tarjeta de Propiedad</option>
                        <option value="brevete">Brevete</option>
                        <option value="revision_tecnica">Revisión Técnica</option>
                        <option value="otro">Otro Documento</option>
                    </select>
                </div>
                <div class="form-group-custom mb-3">
                    <label class="form-label-custom">Título / Descripción <span class="req">*</span></label>
                    <input type="text" class="form-control" id="edDocTitulo" required>
                </div>
                <div class="modal-actions-footer">
                    <button type="button" class="btn btn-cancel-action" onclick="closeModal('modalEditarDoc')">Cancelar</button>
                    <button type="submit" class="btn btn-save-action">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================== -->
<!-- MODAL: SUBIR FOTO A GALERÍA                                -->
<!-- ========================================================== -->
<div class="modal-overlay" id="modalSubirFoto" style="z-index: 9002;">
    <div class="modal-content modal-vehicle-dialog">
        <div class="modal-header-styled">
            <div class="dialog-icon-badge">
                <i class="ph-bold ph-camera-plus"></i>
            </div>
            <div>
                <h3>Añadir Foto a la Galería</h3>
                <p>Se estampará la fecha y hora de captura</p>
            </div>
            <button type="button" class="btn-dialog-close" onclick="closeModal('modalSubirFoto')">&times;</button>
        </div>
        <div class="modal-body-styled">
            <form id="formSubirFoto">
                <div class="form-group-custom mb-3">
                    <label class="form-label-custom">Descripción de la Foto <span class="req">*</span></label>
                    <input type="text" class="form-control" id="fotoDesc" required placeholder="Ej: Estado físico frontal / Etiqueta de serie visible">
                </div>
                <div class="form-group-custom mb-3">
                    <label class="form-label-custom">Archivo de Imagen <span class="req">*</span></label>
                    <input type="file" class="form-control" id="fotoArchivo" name="archivo" accept="image/*" capture="environment" required>
                </div>
                <div class="modal-actions-footer">
                    <button type="button" class="btn btn-cancel-action" onclick="closeModal('modalSubirFoto')">Cancelar</button>
                    <button type="submit" class="btn btn-save-action">Guardar Foto</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================== -->
<!-- MODAL: REGISTRAR SERVICIO / MANTENIMIENTO                  -->
<!-- ========================================================== -->
<div class="modal-overlay" id="modalRegistrarEvento" style="z-index: 9002;">
    <div class="modal-content modal-vehicle-dialog">
        <div class="modal-header-styled">
            <div class="dialog-icon-badge">
                <i class="ph-bold ph-wrench"></i>
            </div>
            <div>
                <h3 id="tituloModalEvento">Registrar Servicio / Mantenimiento</h3>
                <p>Registro de servicio técnico, costo y fotos</p>
            </div>
            <button type="button" class="btn-dialog-close" onclick="closeModal('modalRegistrarEvento')">&times;</button>
        </div>
        <div class="modal-body-styled">
            <form id="formRegistrarEvento">
                <input type="hidden" id="evTipoEvento">
                <div class="row g-3 mb-3">
                    <div class="col-md-6 col-12">
                        <div class="form-group-custom">
                            <label class="form-label-custom">Fecha del Evento <span class="req">*</span></label>
                            <input type="date" class="form-control" id="evFecha" required>
                        </div>
                    </div>
                    <div class="col-md-6 col-12">
                        <div class="form-group-custom">
                            <label class="form-label-custom">Costo (S/.)</label>
                            <input type="number" step="0.01" class="form-control" id="evCosto" placeholder="0.00">
                        </div>
                    </div>
                </div>
                <div class="form-group-custom mb-3">
                    <label class="form-label-custom">Descripción / Detalles <span class="req">*</span></label>
                    <textarea class="form-control" id="evDesc" rows="3" required placeholder="Ej: Mantenimiento preventivo, limpieza de componentes, cambio de pasta térmica..."></textarea>
                </div>
                <div class="form-group-custom mb-3">
                    <label class="form-label-custom">Adjuntar Fotos (Opcional)</label>
                    <input type="file" class="form-control" id="evFotos" name="fotos_evento[]" accept="image/*" capture="environment" multiple>
                </div>
                <div class="modal-actions-footer">
                    <button type="button" class="btn btn-cancel-action" onclick="closeModal('modalRegistrarEvento')">Cancelar</button>
                    <button type="submit" class="btn btn-save-action">Guardar Registro</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?php echo BASE_URL; ?>/modules/inventario/Activos/activos.js?v=<?php echo time(); ?>"></script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
