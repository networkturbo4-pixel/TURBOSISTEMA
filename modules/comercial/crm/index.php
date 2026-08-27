<?php
require_once '../../../config/db.php';
requireLogin();
requirePermission($pdo, 'comercial');

include '../../../includes/header.php';
include '../../../includes/sidebar.php';
?>

<link rel="stylesheet" href="styles.css?v=1">
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

<div class="page-header-card">
    <div class="page-header-left">
        <div class="page-header-icon"><i class="ph ph-chart-line-up"></i></div>
        <div class="page-header-info">
            <h2>CRM Pipeline</h2>
            <p>Gestión de prospectos, embudo de ventas y cotizaciones.</p>
        </div>
    </div>
    <div class="page-header-actions">
        <button type="button" class="btn btn-outline-secondary" id="btnToggleView" title="Cambiar a Vista de Lista">
            <i class="ph ph-list-dashes"></i>
        </button>
        <button type="button" class="btn btn-primary" onclick="openProspectModal()">
            <i class="ph ph-plus"></i> Nuevo Prospecto
        </button>
    </div>
</div>

<div class="settings-section">
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="search-box" style="position: relative; display: flex; align-items: center;">
                <i class="ph ph-magnifying-glass" style="position: absolute; left: 16px; color: var(--text-muted); font-size: 1.2rem; z-index: 5;"></i>
                <input type="text" id="searchInput" class="form-control w-100" placeholder="Buscar por nombre, documento o teléfono..." style="padding-left: 45px; height: 48px; border-radius: 12px; position: relative; z-index: 1;">
            </div>
        </div>
        <div class="col-md-4">
            <select id="pipelineSelect" class="form-select" style="height: 48px; border-radius: 12px;">
                <option value="1">Ventas Generales</option>
            </select>
        </div>
    </div>

    <div id="kanbanBoard" class="kanban-board">
        <!-- Generado por JS -->
    </div>
</div>

<!-- Offcanvas / Panel Lateral Derecho -->
<div class="offcanvas" id="prospectOffcanvas">
    <div class="offcanvas-header">
        <h4 id="ocTitle" style="margin:0;">Detalles del Prospecto</h4>
        <button class="btn btn-icon" onclick="closeOffcanvas()" style="background:transparent; border:none; font-size:1.5rem;"><i class="ph ph-x"></i></button>
    </div>
    <div class="offcanvas-body">
        <div id="ocContent">
            <!-- Info del Prospecto -->
            <h3 id="ocNombre" style="margin-top:0; color:var(--text-color);"></h3>
            <p id="ocContacto" style="color:var(--text-muted); font-size:0.9rem; margin-bottom:15px;"></p>
            
            <div class="d-flex gap-2 mb-4">
                <button class="btn btn-outline-success flex-fill" onclick="openWhatsApp()" id="btnWa">
                    <i class="ph ph-whatsapp-logo"></i> WhatsApp
                </button>
                <button class="btn btn-outline-info flex-fill" onclick="checkCoverage()">
                    <i class="ph ph-map-pin"></i> Factibilidad
                </button>
            </div>

            <hr>
            
            <div class="mb-4">
                <h5>Añadir Nota / Llamada</h5>
                <select id="noteType" class="form-select mb-2">
                    <option value="nota">Nota Interna</option>
                    <option value="llamada">Registro de Llamada</option>
                </select>
                <textarea id="noteContent" class="form-control mb-2" rows="3" placeholder="Escribe tu nota aquí... Puedes usar @usuario para mencionar."></textarea>
                <button class="btn btn-primary btn-sm w-100" onclick="saveNote()">Guardar Nota</button>
            </div>

            <h5>Bitácora de Actividad</h5>
            <div id="notesContainer" class="notes-container">
                <!-- Notas via JS -->
            </div>
        </div>
    </div>
</div>

<!-- Modal Nuevo/Editar Prospecto -->
<div class="modal-overlay" id="prospectModal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3 id="prospectModalTitle">Nuevo Prospecto</h3>
            <button class="btn close-modal" onclick="closeProspectModal()" style="background:transparent; border:none; font-size:1.5rem;">&times;</button>
        </div>
        <form id="prospectForm">
            <input type="hidden" name="id" id="p_id">
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Nombre Completo *</label>
                        <input type="text" name="nombre_completo" id="p_nombre" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">DNI / RUC</label>
                        <input type="text" name="documento" id="p_documento" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Teléfono (WhatsApp)</label>
                        <input type="text" name="telefono" id="p_telefono" class="form-control">
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Correo Electrónico</label>
                        <input type="email" name="correo" id="p_correo" class="form-control">
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Dirección</label>
                        <input type="text" name="direccion" id="p_direccion" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Plan de Interés</label>
                        <select name="interest_service_id" id="p_servicio" class="form-select">
                            <option value="">Seleccione...</option>
                            <?php
                            $stmtS = $pdo->query("SELECT id, nombre, velocidad FROM servicios");
                            while($s = $stmtS->fetch(PDO::FETCH_ASSOC)){
                                echo "<option value='{$s['id']}'>{$s['nombre']} ({$s['velocidad']}Mbps)</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Fuente</label>
                        <select name="fuente" id="p_fuente" class="form-select">
                            <option value="">Seleccione...</option>
                            <option value="Facebook">Facebook</option>
                            <option value="Web">Página Web</option>
                            <option value="Referido">Referido</option>
                            <option value="Volante">Volante / Calle</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" onclick="closeProspectModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Prospecto</button>
            </div>
        </form>
    </div>
</div>

<script src="app.js?v=1"></script>
<?php include '../../../includes/footer.php'; ?>