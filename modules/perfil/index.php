<?php
require_once '../../config/db.php';
requireLogin();
requirePermission($pdo, 'perfil');
include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<link rel="stylesheet" href="<?php echo BASE_URL; ?>/modules/perfil/perfil.css?v=<?php echo time(); ?>">

<div class="profile-container">

<!-- Profile Header (Cover & Avatar) -->
<div class="profile-header">
    <div class="profile-cover" id="coverContainer" onclick="document.getElementById('coverInput').click()">
        <div class="cover-overlay" id="coverOverlay">
            <i class="ph ph-camera"></i>
            <span>Cambiar Portada</span>
        </div>
        <input type="file" id="coverInput" class="no-dropzone" accept="image/*" style="display:none;" onchange="handleCoverSelect(event)">
    </div>
    <div class="profile-info-container">
        <div class="profile-avatar-wrap" onclick="document.getElementById('avatarInput').click()">
            <div class="profile-avatar" id="avatarContainer">
                <i class="ph ph-user"></i>
                <div class="avatar-hover-overlay">
                    <i class="ph ph-camera"></i>
                </div>
            </div>
            <input type="file" id="avatarInput" class="no-dropzone" accept="image/*" style="display:none;" onchange="handleAvatarSelect(event)">
        </div>
        <div class="profile-user-details">
            <h1 id="profNameDisplay">Cargando...</h1>
            <p id="profRoleDisplay">...</p>
        </div>
    </div>
</div>

<!-- Tabs -->
<div class="profile-tabs">
    <button class="prof-tab active" data-tab="datos"><i class="ph ph-identification-card"></i> Mi Perfil</button>
    <button class="prof-tab" data-tab="epp"><i class="ph ph-shield-check"></i> Mi EPP</button>
    <button class="prof-tab" data-tab="mochila"><i class="ph ph-backpack"></i> Mi Mochila</button>
</div>

<div class="profile-content">
    
    <!-- Tab: Mi Perfil (Datos) -->
    <div class="prof-pane active" id="ptab-datos">
        <form id="profileForm" onsubmit="saveProfile(event)">
            <div class="profile-form-grid">
                <div class="prof-form-group">
                    <label>Usuario (Alias)</label>
                    <input type="text" id="profUsername" class="form-control" placeholder="Ej: rachelderek">
                </div>
                <div class="prof-form-group">
                    <label>Nombre y Apellidos</label>
                    <input type="text" id="profName" class="form-control" required placeholder="Tu nombre completo">
                </div>
                <div class="prof-form-group">
                    <label>Correo Electrónico</label>
                    <input type="email" id="profEmail" class="form-control" required placeholder="correo@ejemplo.com">
                </div>
                <div class="prof-form-group">
                    <label>Teléfono / WhatsApp</label>
                    <input type="text" id="profPhone" class="form-control" placeholder="+51 999 999 999">
                </div>
                <div class="prof-form-group">
                    <label>Nueva Contraseña <small>(Dejar en blanco para no cambiar)</small></label>
                    <input type="password" id="profPassword" class="form-control" placeholder="********">
                </div>
            </div>
            
            <div style="margin-top:24px; text-align:right;">
                <button type="submit" class="btn btn-primary" id="btnSaveProfile"><i class="ph ph-floppy-disk"></i> Guardar Cambios</button>
            </div>
        </form>
    </div>

    <!-- Tab: Mi EPP -->
    <div class="prof-pane" id="ptab-epp">
        <div class="items-grid" id="eppGrid">
            <!-- Cargado por JS -->
            <div class="empty-state"><i class="ph ph-spinner ph-spin" style="font-size:2rem;display:block;margin-bottom:10px;"></i>Cargando...</div>
        </div>
    </div>

    <!-- Tab: Mi Mochila -->
    <div class="prof-pane" id="ptab-mochila">
        <div class="items-grid" id="mochilaGrid">
            <!-- Cargado por JS -->
            <div class="empty-state"><i class="ph ph-spinner ph-spin" style="font-size:2rem;display:block;margin-bottom:10px;"></i>Cargando...</div>
        </div>
    </div>

</div>

</div>

<script src="<?php echo BASE_URL; ?>/modules/perfil/perfil.js?v=<?php echo time(); ?>"></script>
<?php include '../../includes/footer.php'; ?>
