<?php
$logoLight = $globalSettings['logo_light'] ?? '';
$logoDark = $globalSettings['logo_dark'] ?? '';
$appNameSidebar = $globalSettings['app_name'] ?? 'Turbo SaaS';
?>
<aside class="sidebar">
    <div class="sidebar-header" style="text-align:center; padding: 20px;">
        <?php if ($logoLight): ?>
            <img src="<?php echo BASE_URL . '/' . $logoLight; ?>" alt="Logo" class="img-fluid logo-light" style="max-height: 40px; max-width: 100%;">
            <img src="<?php echo BASE_URL . '/' . ($logoDark ?: $logoLight); ?>" alt="Logo" class="img-fluid logo-dark" style="max-height: 40px; max-width: 100%; display: none;">
        <?php else: ?>
            <span><?php echo htmlspecialchars($appNameSidebar); ?></span>
        <?php endif; ?>
    </div>
    <div class="sidebar-menu-wrapper">
        <nav class="sidebar-nav">
            <?php 
            global $system_modules;
            $current_path = $_SERVER['REQUEST_URI'];
            
            foreach ($system_modules as $key => $module): 
                if (hasAccess($pdo, $key)):
                    // Determine if active
                    $isActive = false;
                    if ($module['url'] === '/' && ($current_path === BASE_URL . '/' || $current_path === BASE_URL . '/index.php')) {
                        $isActive = true;
                    } elseif ($module['url'] !== '/' && strpos($current_path, BASE_URL . $module['url']) === 0) {
                        $isActive = true;
                    }
            ?>
                <a href="<?php echo BASE_URL . $module['url']; ?>" class="nav-link <?php echo $isActive ? 'active' : ''; ?>">
                    <div class="nav-link-left">
                        <i class="ph <?php echo htmlspecialchars($module['icon']); ?>"></i>
                        <span><?php echo htmlspecialchars($module['name']); ?></span>
                    </div>
                </a>
            <?php 
                endif;
            endforeach; 
            ?>
        </nav>

        <!-- Logout Button -->
        <a href="<?php echo BASE_URL; ?>/login.php?action=logout" class="nav-link sidebar-logout-link" title="Cerrar Sesión">
            <div class="nav-link-left">
                <i class="ph ph-sign-out"></i>
                <span>Cerrar Sesión</span>
            </div>
        </a>
        
        <!-- Theme Toggle Pill -->
        <div class="sidebar-theme-switch">
            <button type="button" class="theme-btn light active" id="btnThemeLight">
                <i class="ph-fill ph-sun"></i> Light
            </button>
            <button type="button" class="theme-btn dark" id="btnThemeDark">
                <i class="ph ph-moon"></i> Dark
            </button>
        </div>
    </div>
</aside>
<main class="main-content">
    <header class="header">
        <div class="header-left">
            <button id="sidebarToggle" class="btn-icon mobile-only"><i class="ph ph-list"></i></button>
            <div class="mobile-logo mobile-only">
                <?php if ($logoLight): ?>
                    <img src="<?php echo BASE_URL . '/' . $logoLight; ?>" alt="Logo" style="max-height: 30px;">
                <?php else: ?>
                    <?php echo htmlspecialchars($appNameSidebar); ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="header-right">
            <a href="<?php echo BASE_URL; ?>/login.php?action=logout" class="btn-icon header-logout-btn" title="Cerrar Sesión" style="background: rgba(239, 68, 68, 0.1); color: #ef4444; width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: all 0.2s; margin-right: 8px;">
                <i class="ph ph-sign-out" style="font-size: 1.2rem;"></i>
            </a>
            <div class="profile-menu">
                <a href="<?php echo BASE_URL; ?>/modules/perfil" class="avatar" style="cursor: pointer; display:block; text-decoration:none; <?php echo !empty($_SESSION['profile_picture']) ? 'background-image: url(\''.BASE_URL.'/'.$_SESSION['profile_picture'].'\'); background-size: cover; background-position: center; color: transparent;' : ''; ?>" title="Mi Perfil">
                    <?php if (empty($_SESSION['profile_picture'])): ?>
                        <i class="ph ph-user"></i>
                    <?php endif; ?>
                </a>
            </div>
        </div>
    </header>
    <div class="content-wrapper">
