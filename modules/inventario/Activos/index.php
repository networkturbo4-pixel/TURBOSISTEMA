<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
require_once '../../../config/db.php';
requireLogin();
// Asumiendo que el permiso es el mismo del inventario general, o 'activos'
requirePermission($pdo, 'inventario');
include '../../../includes/header.php';
include '../../../includes/sidebar.php';
?>

<link rel="stylesheet" href="<?php echo BASE_URL; ?>/modules/inventario/Activos/activos.css?v=<?php echo time(); ?>">

<!-- Cabecera de Página -->
<div class="page-header-card">
    <div class="page-header-left">
        <div class="page-header-icon" style="background: rgba(99, 102, 241, 0.1); color: var(--primary-color);">
            <i class="ph ph-car-profile"></i>
        </div>
        <div class="page-header-info">
            <h2>Control de Activos Vehiculares</h2>
            <p>Gestión de autos, camiones y motocicletas, documentos e historial.</p>
        </div>
    </div>
    <div class="page-header-actions">
        <button class="btn btn-primary" onclick="openNewVehicleModal()">
            <i class="ph ph-plus-circle"></i> Añadir Vehículo
        </button>
    </div>
</div>

<div class="settings-section" style="background: transparent; box-shadow: none; padding: 0;">
    <!-- Controles y Filtros -->
    <div class="activos-toolbar">
        <div class="activos-search">
            <i class="ph ph-magnifying-glass"></i>
            <input type="text" id="searchVehicles" class="form-control" placeholder="Buscar por placa, marca o modelo...">
        </div>
        <select class="form-select" id="filterType">
            <option value="">Todos los tipos</option>
            <option value="auto">Autos</option>
            <option value="camion">Camiones</option>
            <option value="motocicleta">Motocicletas</option>
        </select>
        <select class="form-select" id="filterStatus">
            <option value="">Todos los estados</option>
            <option value="activo">Activos</option>
            <option value="mantenimiento">En Mantenimiento</option>
            <option value="taller">En Taller</option>
            <option value="inactivo">Inactivos</option>
        </select>
    </div>

    <!-- Grid de Vehículos -->
    <div class="vehiculos-grid" id="vehiculosContainer">
        <!-- Ejemplo de Card de Vehículo (Renderizado por JS) -->
        <div class="vehiculo-card" onclick="openVehicleDetails(1)">
            <div class="vehiculo-img-wrapper">
                <img src="<?php echo BASE_URL; ?>/assets/img/placeholder-car.jpg" alt="Vehículo" class="vehiculo-img">
                <span class="vehiculo-badge badge-activo">Activo</span>
            </div>
            <div class="vehiculo-info">
                <div class="vehiculo-placa">ABC-123</div>
                <div class="vehiculo-modelo">Toyota Hilux (Auto)</div>
                <div class="vehiculo-meta">
                    <span><i class="ph ph-calendar-check"></i> Últ. Mantenimiento: 12/05/2026</span>
                </div>
            </div>
        </div>
        <!-- Fin Ejemplo -->
    </div>
</div>

<!-- Modal Principal de Detalles del Vehículo -->
<div class="modal-overlay" id="modalDetalleVehiculo">
    <div class="modal-content modal-lg clean-card-modal">
        <div class="modal-header">
            <h3 id="modalTitlePlaca">Detalle de Vehículo</h3>
            <button class="close-modal" onclick="closeModal('modalDetalleVehiculo')">&times;</button>
        </div>
        <div class="modal-body p-0">
            <!-- Tabs Navigation -->
            <div class="vehiculo-tabs">
                <button class="v-tab active" data-vtab="info"><i class="ph ph-info"></i> Info & Documentos</button>
                <button class="v-tab" data-vtab="galeria"><i class="ph ph-image"></i> Galería de Estado</button>
                <button class="v-tab" data-vtab="llantas"><i class="ph ph-aperture"></i> Historial Llantas</button>
                <button class="v-tab" data-vtab="mantenimiento"><i class="ph ph-wrench"></i> Mantenimiento y Arreglos</button>
            </div>

            <div class="vehiculo-tab-content p-4">
                <!-- Tab: Info & Documentos -->
                <div class="v-tab-pane active" id="vtab-info">
                    <div class="v-info-grid">
                        <div class="v-info-card">
                            <h4>
                                Datos Principales
                                <button class="btn-icon text-primary" style="float: right; margin-top: -5px;" onclick="openEditVehiculo()" title="Editar Vehículo">
                                    <i class="ph ph-pencil-simple"></i>
                                </button>
                            </h4>
                            <div class="v-info-list">
                                <p><span>Tipo:</span> <strong id="lblTipo">--</strong></p>
                                <p><span>Marca:</span> <strong id="lblMarca">--</strong></p>
                                <p><span>Modelo:</span> <strong id="lblModelo">--</strong></p>
                                <p><span>Placa:</span> <strong id="lblPlaca">--</strong></p>
                                <p><span>Estado Actual:</span> <strong id="lblEstado" class="badge-activo">--</strong></p>
                            </div>
                        </div>
                        <div class="v-docs-card">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4>Documentos Adjuntos</h4>
                                <button class="btn btn-secondary btn-sm" onclick="openUploadDocModal()"><i class="ph ph-upload-simple"></i> Subir Doc</button>
                            </div>
                            <ul class="v-doc-list" id="docsList">
                                <li>
                                    <div class="doc-icon"><i class="ph ph-file-pdf"></i></div>
                                    <div class="doc-info">
                                        <span>Brevete - Chofer Juan Perez</span>
                                        <small>Subido el 10/01/2026</small>
                                    </div>
                                    <button class="btn-icon" title="Ver" onclick="window.open('#', '_blank')"><i class="ph ph-eye"></i></button>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Tab: Galería -->
                <div class="v-tab-pane" id="vtab-galeria">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4>Galería Fotográfica (Estado del Vehículo)</h4>
                        <button class="btn btn-primary btn-sm" onclick="openUploadImageModal()"><i class="ph ph-camera"></i> Nueva Foto</button>
                    </div>
                    <div class="v-gallery-grid" id="galleryList">
                        <!-- Ejemplo imagen -->
                        <div class="v-gallery-item" onclick="openImageViewer('placeholder-car.jpg')">
                            <img src="<?php echo BASE_URL; ?>/assets/img/placeholder-car.jpg" alt="Foto">
                            <div class="v-gallery-caption">Estado frontal - 15/06</div>
                        </div>
                    </div>
                </div>

                <!-- Tab: Llantas -->
                <div class="v-tab-pane" id="vtab-llantas">
                     <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4>Historial de Cambio de Llantas</h4>
                        <button class="btn btn-secondary btn-sm" onclick="openNewEventModal('cambio_llantas')"><i class="ph ph-plus"></i> Registrar Cambio</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
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

                <!-- Tab: Mantenimiento -->
                <div class="v-tab-pane" id="vtab-mantenimiento">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4>Historial de Mantenimientos y Arreglos</h4>
                        <button class="btn btn-secondary btn-sm" onclick="openNewEventModal('mantenimiento')"><i class="ph ph-plus"></i> Registrar Mantenimiento/Arreglo</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Tipo</th>
                                    <th>Descripción</th>
                                    <th>Costo</th>
                                </tr>
                            </thead>
                            <tbody id="mantenimientoHistoryTable">
                                <!-- Data -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Visor de Imágenes Moderno -->
<div class="image-viewer-overlay" id="imageViewerOverlay" style="display:none;">
    <div class="image-viewer-close" onclick="closeImageViewer()"><i class="ph ph-x"></i></div>
    <div class="image-viewer-content">
        <img src="" id="viewerImageTarget" alt="Viewer">
    </div>
    <!-- Opcional: controles prev/next si se hace un carrusel completo en JS -->
</div>

<!-- Modal: Nuevo Vehículo -->
<div class="modal-overlay" id="modalNuevoVehiculo">
    <div class="modal-content clean-card-modal">
        <div class="modal-header">
            <h3>Registrar Nuevo Vehículo</h3>
            <button class="close-modal" onclick="closeModal('modalNuevoVehiculo')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="formNuevoVehiculo">
                <div class="form-group mb-3">
                    <label>Tipo <span class="text-danger">*</span></label>
                    <select class="form-select" id="nvTipo" required>
                        <option value="auto">Auto</option>
                        <option value="camion">Camión</option>
                        <option value="motocicleta">Motocicleta</option>
                    </select>
                </div>
                <div class="form-group mb-3">
                    <label>Placa <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nvPlaca" required placeholder="Ej: ABC-123">
                </div>
                <div class="form-group mb-3">
                    <label>Marca</label>
                    <input type="text" class="form-control" id="nvMarca" placeholder="Ej: Toyota">
                </div>
                <div class="form-group mb-3">
                    <label>Modelo</label>
                    <input type="text" class="form-control" id="nvModelo" placeholder="Ej: Hilux">
                </div>
                <div class="form-group mb-3">
                    <label>Fotos Iniciales (Opcional)</label>
                    <input type="file" class="form-control" id="nvFotos" name="nvFotos[]" multiple accept="image/*" capture="environment">
                </div>
                <div class="modal-footer" style="padding: 20px 0 0 0; margin-top:20px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('modalNuevoVehiculo')">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Vehículo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Editar Vehículo -->
<div class="modal-overlay" id="modalEditarVehiculo" style="z-index: 9002;">
    <div class="modal-content clean-card-modal">
        <div class="modal-header">
            <h3>Editar Vehículo</h3>
            <button class="close-modal" onclick="closeModal('modalEditarVehiculo')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="formEditarVehiculo">
                <div class="form-group mb-3">
                    <label>Tipo <span class="text-danger">*</span></label>
                    <select class="form-select" id="evnTipo" required>
                        <option value="auto">Auto</option>
                        <option value="camion">Camión</option>
                        <option value="motocicleta">Motocicleta</option>
                    </select>
                </div>
                <div class="form-group mb-3">
                    <label>Placa <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="evnPlaca" required>
                </div>
                <div class="form-group mb-3">
                    <label>Marca</label>
                    <input type="text" class="form-control" id="evnMarca">
                </div>
                <div class="form-group mb-3">
                    <label>Modelo</label>
                    <input type="text" class="form-control" id="evnModelo">
                </div>
                <div class="form-group mb-3">
                    <label>Estado</label>
                    <select class="form-select" id="evnEstado" required>
                        <option value="activo">Activo</option>
                        <option value="mantenimiento">En Mantenimiento</option>
                        <option value="inactivo">Inactivo / Baja</option>
                    </select>
                </div>
                <div class="modal-footer" style="padding: 20px 0 0 0; margin-top:20px; display: flex; justify-content: space-between;">
                    <button type="button" class="btn text-danger" onclick="archiveVehicle()"><i class="ph ph-archive"></i> Archivar</button>
                    <div>
                        <button type="button" class="btn btn-secondary" onclick="closeModal('modalEditarVehiculo')">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Editar Documento -->
<div class="modal-overlay" id="modalEditarDoc" style="z-index: 9002;">
    <div class="modal-content clean-card-modal">
        <div class="modal-header">
            <h3>Editar Documento</h3>
            <button class="close-modal" onclick="closeModal('modalEditarDoc')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="formEditarDoc">
                <input type="hidden" id="edDocId">
                <div class="form-group mb-3">
                    <label>Tipo de Documento <span class="text-danger">*</span></label>
                    <select class="form-select" id="edDocTipo" required>
                        <option value="brevete">Brevete</option>
                        <option value="soat">SOAT</option>
                        <option value="tarjeta_propiedad">Tarjeta de Propiedad</option>
                        <option value="revision_tecnica">Revisión Técnica</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>
                <div class="form-group mb-3">
                    <label>Título / Descripción corta <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="edDocTitulo" required>
                </div>
                <div class="modal-footer" style="padding: 20px 0 0 0; margin-top:20px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('modalEditarDoc')">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Subir Documento -->
<div class="modal-overlay" id="modalSubirDoc" style="z-index: 9002;">
    <div class="modal-content clean-card-modal">
        <div class="modal-header">
            <h3>Subir Documento</h3>
            <button class="close-modal" onclick="closeModal('modalSubirDoc')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="formSubirDoc">
                <div class="form-group mb-3">
                    <label>Tipo de Documento <span class="text-danger">*</span></label>
                    <select class="form-select" id="docTipo" required>
                        <option value="brevete">Brevete</option>
                        <option value="soat">SOAT</option>
                        <option value="tarjeta_propiedad">Tarjeta de Propiedad</option>
                        <option value="revision_tecnica">Revisión Técnica</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>
                <div class="form-group mb-3">
                    <label>Título / Descripción corta <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="docTitulo" required placeholder="Ej: Brevete Juan Perez">
                </div>
                <div class="form-group mb-3">
                    <label>Archivo (PDF o Imagen) <span class="text-danger">*</span></label>
                    <input type="file" id="docArchivo" name="archivo" accept=".pdf,image/*" required>
                </div>
                <div class="modal-footer" style="padding: 20px 0 0 0; margin-top:20px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('modalSubirDoc')">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Subir Documento</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Subir Foto -->
<div class="modal-overlay" id="modalSubirFoto" style="z-index: 9002;">
    <div class="modal-content clean-card-modal">
        <div class="modal-header">
            <h3>Añadir Foto a la Galería</h3>
            <button class="close-modal" onclick="closeModal('modalSubirFoto')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="formSubirFoto">
                <div class="form-group mb-3">
                    <label>Descripción de la foto <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="fotoDesc" required placeholder="Ej: Estado lateral izquierdo - Raspón">
                </div>
                <div class="form-group mb-3">
                    <label>Archivo de Imagen <span class="text-danger">*</span></label>
                    <input type="file" id="fotoArchivo" name="archivo" accept="image/*" capture="environment" required>
                </div>
                <div class="modal-footer" style="padding: 20px 0 0 0; margin-top:20px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('modalSubirFoto')">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Foto</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Registrar Evento (Historial) -->
<div class="modal-overlay" id="modalRegistrarEvento" style="z-index: 9002;">
    <div class="modal-content clean-card-modal">
        <div class="modal-header">
            <h3 id="tituloModalEvento">Registrar Evento</h3>
            <button class="close-modal" onclick="closeModal('modalRegistrarEvento')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="formRegistrarEvento">
                <input type="hidden" id="evTipoEvento">
                <div class="form-group mb-3">
                    <label>Fecha del Evento <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="evFecha" required>
                </div>
                <div class="form-group mb-3">
                    <label>Costo Asociado (Opcional)</label>
                    <input type="number" step="0.01" class="form-control" id="evCosto" placeholder="0.00">
                </div>
                <div class="form-group mb-3">
                    <label>Descripción / Detalles <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="evDesc" rows="3" required placeholder="Ej: Se cambiaron las 4 llantas por desgaste..."></textarea>
                </div>
                <div class="form-group mb-3">
                    <label>Adjuntar Fotos (Opcional)</label>
                    <input type="file" id="evFotos" name="fotos_evento[]" accept="image/*" capture="environment" multiple>
                    <small class="text-muted d-block mt-1">Puedes seleccionar o tomar varias fotos.</small>
                </div>
                <div class="modal-footer" style="padding: 20px 0 0 0; margin-top:20px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('modalRegistrarEvento')">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Registrar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?php echo BASE_URL; ?>/modules/inventario/Activos/activos.js?v=<?php echo time(); ?>"></script>

<?php include '../../../includes/footer.php'; ?>
