<?php
require_once '../../config/db.php';
requireLogin();
requirePermission($pdo, 'mochila');

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<link rel="stylesheet" href="mochila.css?v=<?php echo time(); ?>">

<!-- ══════════════════════════════════════════════
     PAGE HEADER
═══════════════════════════════════════════════ -->
<div class="page-header-card">
    <div class="page-header-left">
        <div class="page-header-icon">
            <i class="ph ph-backpack"></i>
        </div>
        <div class="page-header-info">
            <h2>Mochila de Usuarios</h2>
            <p>Control de equipos asignados y verificación fotográfica</p>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════
     STAT CARDS PREMIUM
═══════════════════════════════════════════════ -->
<div class="mochila-stats">

    <div class="mochila-stat-card stat-purple">
        <div class="stat-card-glow"></div>
        <div class="stat-icon-wrap">
            <i class="ph ph-cube"></i>
        </div>
        <div class="stat-body">
            <div class="stat-value" id="stat-en-campo">
                <span class="stat-num">—</span>
            </div>
            <div class="stat-label">Items en Campo</div>
            <div class="stat-sub">equipos asignados activos</div>
        </div>
        <div class="stat-deco">
            <i class="ph ph-cube-transparent"></i>
        </div>
    </div>

    <div class="mochila-stat-card stat-emerald">
        <div class="stat-card-glow"></div>
        <div class="stat-icon-wrap">
            <i class="ph ph-users-three"></i>
        </div>
        <div class="stat-body">
            <div class="stat-value" id="stat-usuarios">
                <span class="stat-num">—</span>
            </div>
            <div class="stat-label">Usuarios Activos</div>
            <div class="stat-sub">con equipos en campo</div>
        </div>
        <div class="stat-deco">
            <i class="ph ph-users"></i>
        </div>
    </div>

    <div class="mochila-stat-card stat-red">
        <div class="stat-card-glow"></div>
        <div class="stat-icon-wrap">
            <i class="ph ph-camera-slash"></i>
        </div>
        <div class="stat-body">
            <div class="stat-value" id="stat-sin-fotos">
                <span class="stat-num">—</span>
            </div>
            <div class="stat-label">Sin Fotos</div>
            <div class="stat-sub">requieren verificación</div>
        </div>
        <div class="stat-deco">
            <i class="ph ph-warning-circle"></i>
        </div>
    </div>

</div>

<!-- ══════════════════════════════════════════════
     USUARIOS — GRID DE CARDS
═══════════════════════════════════════════════ -->
<div class="users-section">
    <div class="users-section-header">
        <div class="users-section-title">
            <i class="ph ph-users-three"></i>
            <span>Técnicos y Usuarios</span>
            <span class="users-count-badge" id="usersCount">—</span>
        </div>
        <div class="users-search-bar" style="display:flex; gap: 8px; width: auto; max-width: 100%; flex-wrap: wrap;">
            <div style="position:relative; flex: 1; min-width: 200px;">
                <i class="ph ph-magnifying-glass" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-muted);"></i>
                <input type="text" id="usersSearchInput" placeholder="Buscar usuario o rol..." oninput="filterUsers()" style="width:100%; padding: 8px 12px 8px 36px; border: 1px solid var(--border-color); border-radius: 12px; background: var(--bg-color); color: var(--text-color); outline: none;">
            </div>
            <select id="roleFilter" class="users-search-select" onchange="filterUsers()">
                <option value="">Todos los roles</option>
            </select>
            <select id="sortFilter" class="users-search-select" onchange="filterUsers()">
                <option value="name_asc">A-Z</option>
                <option value="items_desc">Mayor cantidad de items</option>
            </select>
        </div>
    </div>

    <div class="users-grid" id="usersList">
        <!-- Se llena vía JS -->
    </div>
</div>

<!-- ══════════════════════════════════════════════
     OFF-CANVAS MOCHILA
═══════════════════════════════════════════════ -->
<div class="mochila-offcanvas-backdrop" id="offcanvasBackdrop" onclick="closeOffCanvas()"></div>

<div class="mochila-offcanvas" id="mochilaOffCanvas">

    <!-- Header off-canvas -->
    <div class="offcanvas-header">
        <div class="offcanvas-user-info">
            <div class="offcanvas-avatar" id="offcanvasAvatar"></div>
            <div>
                <div class="offcanvas-user-name" id="offcanvasUserName">—</div>
                <div class="offcanvas-user-role" id="offcanvasUserRole">—</div>
            </div>
        </div>
        <button class="offcanvas-close" onclick="closeOffCanvas()">
            <i class="ph ph-x"></i>
        </button>
    </div>

    <!-- Tab: Productos (ahora es el único contenido) -->
    <div class="offcanvas-tab-content active" id="tab-productos" style="display:block;">
        <div class="offcanvas-body" id="offcanvasProductos">
            <div class="oc-loading">
                <i class="ph ph-spinner ph-spin"></i>
                <p>Cargando mochila...</p>
            </div>
        </div>
    </div>

</div>

<!-- ══════════════════════════════════════════════
     MODAL REGISTRAR FOTO
═══════════════════════════════════════════════ -->
<div class="upload-modal-overlay" id="registrarFotoModal" onclick="closeRegistrarFotoModal()">
    <div class="upload-modal" onclick="event.stopPropagation()" style="max-width: 500px; padding: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h4><i class="ph ph-camera-plus" style="color: var(--primary-color);"></i> Registrar Foto</h4>
            <button class="offcanvas-close" onclick="closeRegistrarFotoModal()" style="position: relative; top: 0; right: 0;">
                <i class="ph ph-x"></i>
            </button>
        </div>

        <div class="offcanvas-body" style="padding: 0; overflow: visible;">
            <!-- Selector de SKU -->
            <div class="register-step">
                <div class="register-step-label">
                    <span class="step-number">1</span> Selecciona el equipo
                </div>
                <select id="registerSkuSelect" class="register-select">
                    <option value="">— Elige un SKU —</option>
                </select>

                <div class="register-step-label" style="margin-top:12px;">
                    Estado (opcional, se actualizará en inventario)
                </div>
                <select id="registerStatusSelect" class="register-select">
                    <option value="">(Mantener estado actual)</option>
                    <option value="disponible">Disponible</option>
                    <option value="instalado">Instalado</option>
                    <option value="malogrado">Malogrado</option>
                    <option value="reparado">Reparado</option>
                    <option value="en_transito">En Tránsito</option>
                </select>

                <!-- Foto del producto seleccionado -->
                <div class="register-product-preview" id="registerProductPreview" style="display:none; margin-top:12px;">
                    <img id="registerProductImg" src="" alt="Foto del producto">
                    <div class="register-product-preview-label">
                        <i class="ph ph-image"></i> Foto de referencia del producto
                    </div>
                </div>
            </div>

            <!-- Cámara / Foto -->
            <div class="register-step">
                <div class="register-step-label">
                    <span class="step-number">2</span> Toma o sube una foto
                </div>

                <div class="camera-area" id="cameraArea">
                    <!-- Vista previa cámara / imagen capturada -->
                    <video id="cameraStream" autoplay playsinline style="display:none;"></video>
                    <canvas id="cameraCanvas" style="display:none;"></canvas>
                    <img id="capturedPreview" style="display:none;" alt="Foto capturada">

                    <div class="camera-placeholder" id="cameraPlaceholder">
                        <i class="ph ph-camera"></i>
                        <p>Activa la cámara para tomar una foto</p>
                    </div>

                    <!-- Error box -->
                    <div class="camera-error-box" id="cameraError" style="display:none;">
                        <i class="ph ph-warning-circle"></i>
                        <span class="cam-error-msg"></span>
                        <button class="cam-error-retry" onclick="activateCamera()">
                            <i class="ph ph-arrow-clockwise"></i> Reintentar
                        </button>
                    </div>
                </div>

                <div class="camera-controls">
                    <button class="cam-btn cam-btn-primary" id="btnActivateCam" onclick="activateCamera()">
                        <i class="ph ph-camera"></i> Activar Cámara
                    </button>
                    <button class="cam-btn cam-btn-snap" id="btnSnap" onclick="snapPhoto()" style="display:none;">
                        <i class="ph ph-aperture"></i> Capturar
                    </button>
                    <button class="cam-btn cam-btn-danger" id="btnRetake" onclick="retakePhoto()" style="display:none;">
                        <i class="ph ph-arrow-counter-clockwise"></i> Retomar
                    </button>
                </div>
            </div>

            <!-- Nota -->
            <div class="register-step">
                <div class="register-step-label">
                    <span class="step-number">3</span> Nota (opcional)
                </div>
                <input type="text" id="registerNota" class="register-input" placeholder="ej: Foto frontal, número de serie...">
            </div>

            <!-- Botón guardar -->
            <button class="register-submit-btn" id="btnRegisterSubmit" onclick="submitRegisterPhoto()" disabled>
                <i class="ph ph-cloud-arrow-up"></i> Guardar Foto
            </button>

            <div class="register-feedback" id="registerFeedback"></div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════
     LIGHTBOX
═══════════════════════════════════════════════ -->
<div class="photo-lightbox" id="photoLightbox" onclick="closeLightbox()">
    <button class="close-lightbox" onclick="closeLightbox()"><i class="ph ph-x"></i></button>
    <button class="lightbox-nav prev" onclick="event.stopPropagation(); lightboxPrev()"><i class="ph ph-caret-left"></i></button>
    <img id="lightboxImage" src="" alt="Foto" onclick="event.stopPropagation()">
    <button class="lightbox-nav next" onclick="event.stopPropagation(); lightboxNext()"><i class="ph ph-caret-right"></i></button>
    <div class="lightbox-info" id="lightboxInfo"></div>
</div>

<!-- ══════════════════════════════════════════════
     MODAL REASIGNAR
═══════════════════════════════════════════════ -->
<div class="upload-modal-overlay" id="reassignModal" onclick="closeReassignModal()">
    <div class="upload-modal" onclick="event.stopPropagation()" style="max-width: 400px;">
        <h4><i class="ph ph-arrows-left-right" style="color: var(--primary-color);"></i> Reasignar SKU</h4>
        <p class="upload-subtitle">SKU: <strong id="reassignSkuInfo"></strong></p>
        <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 12px;">Selecciona el usuario destino:</p>
        <div class="reassign-user-list" id="reassignUserList"></div>
        <div class="upload-modal-actions" style="margin-top: 16px;">
            <button class="btn btn-cancel" onclick="closeReassignModal()">Cancelar</button>
        </div>
    </div>
</div>

<script src="mochila.js?v=<?php echo time(); ?>"></script>



<?php include '../../includes/footer.php'; ?>
