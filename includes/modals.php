<!-- Modal de Eliminación Universal -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Confirmar Eliminación</h3>
            <button class="btn close-modal" style="background:transparent; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
        </div>
        <div class="modal-body">
            <p>¿Estás seguro de que deseas eliminar este registro? Esta acción no se puede deshacer.</p>
        </div>
        <div class="modal-footer">
            <button class="btn close-modal" onclick="document.getElementById('deleteModal').classList.remove('active')">Cancelar</button>
            <button class="btn btn-danger" id="btnConfirmDelete">Eliminar</button>
        </div>
    </div>
</div>

<!-- Modal de Perfil de Usuario (Módulo) -->
<style>
.profile-tabs {
    display: flex;
    border-bottom: 1px solid var(--border-color);
    margin: 0;
    padding: 0;
    list-style: none;
    background: transparent;
}
.profile-tab-btn {
    flex: 1;
    background: transparent;
    border: none;
    padding: 12px 10px;
    cursor: pointer;
    color: var(--text-color);
    font-weight: 600;
    font-size: 0.9rem;
    border-bottom: 2px solid transparent;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
}
.profile-tab-btn:hover {
    background: var(--hover-color, rgba(0,0,0,0.02));
}
.profile-tab-btn.active {
    border-bottom-color: var(--primary-color);
    color: var(--primary-color);
}
.profile-tab-content {
    display: none;
    padding: 20px;
    height: 450px;
    overflow-y: auto;
}
.profile-tab-content.active {
    display: block;
}
.logout-btn-modal {
    color: var(--danger-color, #dc3545);
    background: transparent;
    border: 1px solid var(--danger-color, #dc3545);
    padding: 6px 12px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 5px;
    transition: all 0.2s;
}
.logout-btn-modal:hover {
    background: var(--danger-color, #dc3545);
    color: white;
}
.profile-modal-footer {
    display: flex;
    justify-content: space-between;
    width: 100%;
}
@media (max-width: 600px) {
    .profile-modal-footer {
        flex-direction: column-reverse;
        gap: 12px;
    }
    .profile-modal-footer .btn {
        width: 100%;
        max-width: 100% !important;
        justify-content: center;
    }
}
</style>
<div class="modal-overlay" id="profileModal">
    <div class="modal-content">
        <div class="modal-header" style="border-bottom: none; padding-bottom: 10px;">
            <h3>Módulo de Usuario</h3>
            <button type="button" class="btn close-modal" onclick="document.getElementById('profileModal').classList.remove('active')">&times;</button>
        </div>
        
        <div class="profile-tabs" style="padding: 0 32px;">
            <button class="profile-tab-btn active" onclick="switchProfileTab('mochila', this)"><i class="ph ph-backpack"></i> Mi Mochila</button>
            <button class="profile-tab-btn" onclick="switchProfileTab('soporte', this)"><i class="ph ph-headset"></i> Soporte</button>
            <button class="profile-tab-btn" onclick="switchProfileTab('perfil', this)"><i class="ph ph-user-circle"></i> Mi Perfil</button>
        </div>

        <div class="modal-body" style="height: 400px; padding-top: 20px;">
            <!-- Tab: Mi Mochila -->
            <div id="tab-mochila" class="profile-tab-content active" style="padding:0; height:auto;">
                <div id="mochila-list">
                    <div class="text-center text-muted" style="padding: 20px;">
                        <i class="ph ph-package" style="font-size: 2rem;"></i>
                        <p>Cargando mochila...</p>
                    </div>
                </div>
            </div>

            <!-- Tab: Soporte -->
            <div id="tab-soporte" class="profile-tab-content" style="padding:0; height:auto;">
                <div class="text-center text-muted" style="padding: 40px 20px;">
                    <i class="ph ph-wrench" style="font-size: 3rem; margin-bottom: 15px; color: var(--primary-color);"></i>
                    <h4 style="margin-top:0;">Soporte Técnico</h4>
                    <p>Próximamente podrás solicitar soporte y crear tickets desde aquí.</p>
                </div>
            </div>

            <!-- Tab: Mi Perfil -->
            <div id="tab-perfil" class="profile-tab-content" style="padding:0; height:auto;">
                <form id="profileForm" onsubmit="submitProfileForm(event)" enctype="multipart/form-data">
                <div class="text-center mb-4">
                    <div class="profile-pic-preview" id="profilePicPreview" style="width: 100px; height: 100px; border-radius: 50%; background-color: var(--primary-color); color: white; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto; cursor: pointer; background-size: cover; background-position: center; border: 2px solid var(--border-color);" onclick="document.getElementById('profilePicInput').click()">
                        <i class="ph ph-camera"></i>
                    </div>
                    <input type="file" class="no-dropzone" id="profilePicInput" name="profile_picture" accept="image/*" style="display: none;" onchange="previewProfilePic(this)">
                    <small class="text-muted d-block mt-2">Haz clic para cambiar tu foto</small>
                </div>
                    
                <div class="form-group">
                    <label class="form-label">Nombre / Usuario <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="name" id="profileName" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Correo Electrónico <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" name="email" id="profileEmail" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Número de WhatsApp</label>
                    <input type="text" class="form-control" name="whatsapp" id="profileWhatsapp" placeholder="+51 999 999 999">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Cambiar Contraseña</label>
                    <input type="password" class="form-control" name="password" id="profilePassword" placeholder="Dejar en blanco para no cambiar">
                </div>
                
                </form>
            </div>
        </div>
        <div class="modal-footer profile-modal-footer">
            <a href="<?php echo BASE_URL; ?>/login.php?action=logout" class="btn btn-danger" style="background: #ef4444 !important; border-color: #ef4444 !important; color: white !important; font-weight: bold; flex: 1; max-width: 100%; justify-content: center; text-align: center;" title="Cerrar Sesión">
                <i class="ph ph-sign-out"></i> Cerrar Sesión
            </a>
            <button type="submit" form="profileForm" class="btn btn-primary" id="btnSaveProfile" style="display:none; flex: 1; max-width: 100%; justify-content: center;">Guardar Cambios</button>
        </div>
    </div>
</div>

<script>
    function switchProfileTab(tabId, btnElement) {
        // Update tab buttons
        document.querySelectorAll('.profile-tab-btn').forEach(btn => btn.classList.remove('active'));
        if(btnElement) btnElement.classList.add('active');
        
        // Update tab contents
        document.querySelectorAll('.profile-tab-content').forEach(content => content.classList.remove('active'));
        document.getElementById('tab-' + tabId).classList.add('active');

        // Toggle Save Profile Button visibility
        const saveBtn = document.getElementById('btnSaveProfile');
        if (tabId === 'perfil') {
            saveBtn.style.display = 'flex';
        } else {
            saveBtn.style.display = 'none';
        }
    }

    function openProfileModal() {
        const modal = document.getElementById('profileModal');
        modal.classList.add('active');
        
        // Cargar datos actuales
        const formData = new FormData();
        formData.append('action', 'get');
        
        fetch('<?php echo BASE_URL; ?>/ajax/profile.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                document.getElementById('profileName').value = res.data.name || '';
                document.getElementById('profileEmail').value = res.data.email || '';
                document.getElementById('profileWhatsapp').value = res.data.whatsapp || '';
                document.getElementById('profilePassword').value = '';
                
                const preview = document.getElementById('profilePicPreview');
                if (res.data.profile_picture) {
                    preview.style.backgroundImage = `url('<?php echo BASE_URL; ?>/${res.data.profile_picture}')`;
                    preview.innerHTML = '';
                } else {
                    preview.style.backgroundImage = 'none';
                    preview.innerHTML = '<i class="ph ph-camera"></i>';
                }

                // Cargar Mochila
                const mochilaList = document.getElementById('mochila-list');
                if (res.data.mochila && res.data.mochila.length > 0) {
                    let html = '<h4 style="margin-top:0; margin-bottom: 15px;">Productos Asignados</h4>';
                    res.data.mochila.forEach(item => {
                        let qtyDisplay = '';
                        if (item.bulk_quantity) {
                            qtyDisplay = `<span style="display:inline-block; margin-left: 8px; background:var(--primary-color); color:#fff; padding: 2px 6px; border-radius: 4px; font-size:0.75rem; font-weight:bold;">${item.bulk_quantity} ${item.unit_type||''}</span>`;
                        }
                        html += `
                        <div style="padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; margin-bottom: 10px; display: flex; align-items: center; gap: 12px;">
                            <div style="background: var(--bg-color); color: var(--primary-color); width: 45px; height: 45px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                                <i class="ph ph-package"></i>
                            </div>
                            <div>
                                <h6 style="margin: 0; font-size: 0.95rem; font-weight: 600;">${item.product_name}${qtyDisplay}</h6>
                                <small class="text-muted" style="display: block; margin-top: 2px;">SKU: <strong>${item.sku_code}</strong> | ${item.category_name || 'Sin Categoría'}</small>
                            </div>
                        </div>`;
                    });
                    mochilaList.innerHTML = html;
                } else {
                    mochilaList.innerHTML = `
                    <div class="text-center text-muted" style="padding: 20px;">
                        <i class="ph ph-package" style="font-size: 3rem; margin-bottom: 10px; color: var(--border-color);"></i>
                        <p>No hay productos asignados a tu mochila en este momento.</p>
                    </div>`;
                }
            } else {
                window.showToast ? window.showToast('Error al cargar datos del perfil', 'error') : alert('Error al cargar datos del perfil');
            }
        })
        .catch(err => console.error(err));
    }
    
    function previewProfilePic(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('profilePicPreview');
                preview.style.backgroundImage = `url('${e.target.result}')`;
                preview.innerHTML = '';
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    function submitProfileForm(e) {
        e.preventDefault();
        const form = e.target;
        const btn = document.getElementById('btnSaveProfile');
        
        btn.disabled = true;
        btn.innerHTML = 'Guardando...';
        
        const formData = new FormData(form);
        formData.append('action', 'update');
        
        fetch('<?php echo BASE_URL; ?>/ajax/profile.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(res => {
            btn.disabled = false;
            btn.innerHTML = 'Guardar Cambios';
            
            if (res.success) {
                window.showToast ? window.showToast(res.message, 'success') : alert(res.message);
                document.getElementById('profileModal').classList.remove('active');
                
                // Update avatar in sidebar dynamically
                if (res.profile_picture) {
                    const avatars = document.querySelectorAll('.profile-menu .avatar');
                    avatars.forEach(av => {
                        av.style.backgroundImage = `url('<?php echo BASE_URL; ?>/${res.profile_picture}')`;
                        av.style.backgroundSize = 'cover';
                        av.style.backgroundPosition = 'center';
                        av.style.color = 'transparent';
                        av.innerHTML = '';
                    });
                }
            } else {
                window.showToast ? window.showToast(res.message, 'error') : alert(res.message);
            }
        })
        .catch(err => {
            console.error(err);
            btn.disabled = false;
            btn.innerHTML = 'Guardar Cambios';
            window.showToast ? window.showToast('Error de conexión', 'error') : alert('Error de conexión');
        });
    }
</script>

<!-- ============================================== -->
<!-- MODALES GLOBALES DE ESCÁNER Y CÁMARA           -->
<!-- ============================================== -->

<!-- 1. Modal de Cámara Global (Solo Fotos) -->
<div class="modal-overlay" id="sysCameraModal" style="z-index: 19000;">
    <div class="modal-content" style="max-width:500px;">
        <div class="modal-header" style="border-bottom:none; padding-bottom:10px;">
            <h3><i class="ph ph-camera"></i> Tomar Foto</h3>
            <button type="button" class="btn close-modal" onclick="closeSysCamera()">&times;</button>
        </div>
        <div class="modal-body" style="padding-top:0;">
            <div style="width:100%; border-radius:12px; overflow:hidden; margin-bottom:12px; background:#000; position:relative;">
                <video id="sysCameraVideo" autoplay playsinline style="width:100%; display:block;"></video>
            </div>
            <div style="display:flex; align-items:center; gap:10px; margin-bottom: 12px;">
                <i class="ph ph-magnifying-glass-minus"></i>
                <input type="range" id="sysCameraZoom" class="form-range" min="1" max="5" step="0.1" value="1" style="flex:1;" oninput="updateSysCameraZoom(this.value)">
                <i class="ph ph-magnifying-glass-plus"></i>
            </div>
            <div style="text-align:center;">
                <button type="button" class="btn btn-primary" id="btnSysCapture" style="width:100%; font-size:1.1rem; padding:12px;"><i class="ph ph-aperture"></i> Capturar Foto</button>
            </div>
            <canvas id="sysCameraCanvas" style="display:none;"></canvas>
        </div>
    </div>
</div>

<!-- 2. Modal de Escáner QR Global -->
<div class="modal-overlay" id="sysQrScannerModal" style="z-index: 19000;">
    <div class="modal-content" style="max-width:500px;">
        <div class="modal-header" style="border-bottom:none; padding-bottom:10px;">
            <h3><i class="ph ph-qr-code"></i> Escanear Código QR</h3>
            <button type="button" class="btn close-modal" onclick="closeSysQrScanner()">&times;</button>
        </div>
        <div class="modal-body" style="padding-top:0;">
            <div id="sysQrReader" style="width:100%; border-radius:12px; overflow:hidden; margin-bottom:12px;"></div>
            <div style="display:flex; align-items:center; gap:10px; margin-bottom: 12px;">
                <i class="ph ph-magnifying-glass-minus"></i>
                <input type="range" id="sysQrZoom" class="form-range" min="1" max="5" step="0.1" value="1" style="flex:1;" oninput="updateSysScannerZoom(this.value, 'sysQrReader')">
                <i class="ph ph-magnifying-glass-plus"></i>
            </div>
            <div style="text-align:center; color:var(--text-muted); font-size:0.85rem;">
                <i class="ph ph-camera"></i> Apunta la cámara al código QR para leer la URL o texto...
            </div>
        </div>
    </div>
</div>

<!-- 3. Modal de Escáner de Código de Barras Global (Multi-detect) -->
<div class="modal-overlay" id="sysBarcodeScannerModal" style="z-index: 19000;">
    <div class="modal-content" style="max-width:500px;">
        <div class="modal-header" style="border-bottom:none; padding-bottom:10px;">
            <h3><i class="ph ph-barcode"></i> Escanear Código de Barras</h3>
            <button type="button" class="btn close-modal" onclick="closeSysBarcodeScanner()">&times;</button>
        </div>
        <div class="modal-body" style="padding-top:0;">
            <div id="sysBarcodeReader" style="width:100%; border-radius:12px; overflow:hidden; margin-bottom:12px;"></div>
            <div style="display:flex; align-items:center; gap:10px; margin-bottom: 12px;">
                <i class="ph ph-magnifying-glass-minus"></i>
                <input type="range" id="sysBarcodeZoom" class="form-range" min="1" max="5" step="0.1" value="1" style="flex:1;" oninput="updateSysScannerZoom(this.value, 'sysBarcodeReader')">
                <i class="ph ph-magnifying-glass-plus"></i>
            </div>
            <div style="text-align:center; color:var(--text-muted); font-size:0.85rem; margin-bottom:12px;" id="sysBarcodeStatus">
                <i class="ph ph-camera"></i> Apunta la cámara a los códigos de barras...
            </div>
            <div id="sysBarcodeResultsWrap" style="display:none;">
                <label class="form-label" style="font-size:0.85rem;">Códigos detectados — selecciona uno:</label>
                <div id="sysBarcodeList" class="scan-picker-list" style="display:flex; flex-direction:column; gap:6px;"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Acción Global de Usuario (Escáner de Fotocheck) -->
<div class="modal-overlay" id="globalUserActionModal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3><i class="ph ph-user-focus"></i> Acción Rápida: <span id="globalUserActionName">Usuario</span></h3>
            <button class="btn close-modal" style="background:transparent; border:none; font-size:1.5rem; cursor:pointer;" onclick="document.getElementById('globalUserActionModal').classList.remove('active')">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="globalUserActionId" value="">
            
            <div class="row g-2 mb-4">
                <div class="col-6">
                    <button class="btn btn-outline-success w-100 py-3 d-flex flex-column align-items-center" onclick="submitGlobalAttendance('entrada')" style="height: 100%;">
                        <i class="ph ph-sign-in" style="font-size: 2rem;"></i>
                        <span class="mt-2">Marcar Entrada</span>
                    </button>
                </div>
                <div class="col-6">
                    <button class="btn btn-outline-danger w-100 py-3 d-flex flex-column align-items-center" onclick="submitGlobalAttendance('salida')" style="height: 100%;">
                        <i class="ph ph-sign-out" style="font-size: 2rem;"></i>
                        <span class="mt-2">Marcar Salida</span>
                    </button>
                </div>
            </div>

            <hr class="my-4">

            <div class="text-center mb-3">
                <h5>Añadir a Mochila</h5>
                <p class="text-muted" style="font-size: 0.85rem;">Para asignar un producto o material a este usuario, escanea el código (SKU) del producto ahora.</p>
            </div>
            
            <div class="form-group mb-0 position-relative">
                <i class="ph ph-barcode position-absolute" style="left: 15px; top: 50%; transform: translateY(-50%); font-size: 1.2rem; color: var(--text-muted);"></i>
                <input type="text" id="globalSkuAssignInput" class="form-control form-control-lg text-center" placeholder="Escanea código del producto..." style="padding-left: 45px; letter-spacing: 2px;" autocomplete="off">
            </div>
        </div>
    </div>
</div>

<!-- Modal Global de Google Drive, Ubicación InDrive/Uber y Cámara Webcam -->
<?php require_once __DIR__ . '/gdrive_modal.php'; ?>
<?php require_once __DIR__ . '/location_modal.php'; ?>
<?php require_once __DIR__ . '/webcam_modal.php'; ?>

