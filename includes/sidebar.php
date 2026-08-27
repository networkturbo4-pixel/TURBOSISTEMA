<?php
if (isset($_GET['embedded']) || isset($_GET['portal'])) {
    return;
}
$logoLight = $globalSettings['logo_light'] ?? '';
$logoDark = $globalSettings['logo_dark'] ?? '';
$logoCollapsedLight = $globalSettings['logo_collapsed_light'] ?? '';
$logoCollapsedDark = $globalSettings['logo_collapsed_dark'] ?? '';
$appNameSidebar = $globalSettings['app_name'] ?? 'Turbo SaaS';
?>
<aside class="sidebar">
    <script>
        (function() {
            try {
                if (window.innerWidth > 768 && localStorage.getItem('sidebar_collapsed') === 'true') {
                    document.currentScript.parentNode.classList.add('collapsed');
                }
            } catch (e) {}
        })();
    </script>
    <div class="sidebar-header" style="display: flex; align-items: center; justify-content: space-between; padding: 20px;">
        <div class="sidebar-logo-container" style="display: flex; align-items: center; flex: 1; overflow: hidden;">
            <?php if ($logoLight): ?>
                <img src="<?php echo BASE_URL . '/' . $logoLight; ?>" alt="Logo" class="img-fluid logo-light" style="max-height: 40px; max-width: 100%;">
                <img src="<?php echo BASE_URL . '/' . ($logoDark ?: $logoLight); ?>" alt="Logo" class="img-fluid logo-dark" style="max-height: 40px; max-width: 100%; display: none;">
            <?php else: ?>
                <span class="nav-text" style="font-size: 1.2rem; font-weight: bold; white-space: nowrap;"><?php echo htmlspecialchars($appNameSidebar); ?></span>
            <?php endif; ?>
        </div>
        <?php if ($logoCollapsedLight): ?>
            <img src="<?php echo BASE_URL . '/' . $logoCollapsedLight; ?>" alt="Logo Icon" class="img-fluid logo-collapsed-icon logo-light" style="max-height: 35px; max-width: 100%; display: none; margin: 0 auto;">
            <img src="<?php echo BASE_URL . '/' . ($logoCollapsedDark ?: $logoCollapsedLight); ?>" alt="Logo Icon" class="img-fluid logo-collapsed-icon logo-dark" style="max-height: 35px; max-width: 100%; display: none; margin: 0 auto;">
        <?php endif; ?>
    </div>
    <div class="sidebar-menu-wrapper">
        <nav class="sidebar-nav">
            <?php 
            global $system_modules;
            $current_path = $_SERVER['REQUEST_URI'] ?? '';
            
            foreach ($system_modules as $key => $module): 
                if (hasAccess($pdo, $key)):
                    // Determine if active
                    $isActive = false;
                    if ($module['url'] === '/' && ($current_path === BASE_URL . '/' || $current_path === BASE_URL . '/index.php')) {
                        $isActive = true;
                    } elseif ($module['url'] !== '/' && strpos($current_path, BASE_URL . $module['url']) === 0) {
                        $isActive = true;
                    }
                    
                    $hasSubmodules = isset($module['submodules']) && is_array($module['submodules']) && count($module['submodules']) > 0;
            ?>
                <?php if ($hasSubmodules): ?>
                    <div class="sidebar-item <?php echo $isActive ? 'open' : ''; ?>">
                        <a href="#" class="nav-link sidebar-toggle" title="<?php echo htmlspecialchars($module['name']); ?>">
                            <div class="nav-link-left">
                                <i class="ph <?php echo htmlspecialchars($module['icon']); ?>"></i>
                                <span class="nav-text"><?php echo htmlspecialchars($module['name']); ?></span>
                            </div>
                            <i class="ph ph-caret-down toggle-icon" style="margin-left: auto;"></i>
                        </a>
                        <div class="sidebar-submenu" style="display: <?php echo $isActive ? 'flex' : 'none'; ?>; flex-direction: column;">
                            <?php foreach ($module['submodules'] as $subKey => $subMod): 
                                $subUrl = BASE_URL . $subMod['url'];
                                if ($subMod['url'] === $module['url']) {
                                    $isSubActive = ($current_path === $subUrl || $current_path === $subUrl . '/' || $current_path === $subUrl . '/index.php');
                                } else {
                                    $isSubActive = (strpos($current_path, $subUrl) === 0);
                                }
                            ?>
                                <a href="<?php echo $subUrl; ?>" class="nav-link submenu-link <?php echo $isSubActive ? 'active' : ''; ?>" style="padding-left: 3rem;">
                                    <div class="nav-link-left"><span class="nav-text"><?php echo htmlspecialchars($subMod['name']); ?></span></div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?php echo BASE_URL . $module['url']; ?>" class="nav-link <?php echo $isActive ? 'active' : ''; ?>" title="<?php echo htmlspecialchars($module['name']); ?>">
                        <div class="nav-link-left">
                            <i class="ph <?php echo htmlspecialchars($module['icon']); ?>"></i>
                            <span class="nav-text"><?php echo htmlspecialchars($module['name']); ?></span>
                        </div>
                    </a>
                <?php endif; ?>
            <?php 
                endif;
            endforeach; 
            ?>
        </nav>

        <!-- Collapse Toggle Button (Moved to bottom) -->
        <button id="sidebarInternalToggle" class="nav-link d-none d-md-flex" style="background: transparent; border: none; width: 100%; text-align: left; cursor: pointer; margin-top: auto;" title="Colapsar menú">
            <div class="nav-link-left">
                <i class="ph ph-caret-left" style="margin: 0;"></i>
                <span class="nav-text">Colapsar menú</span>
            </div>
        </button>

        <!-- Logout Button -->
        <a href="<?php echo BASE_URL; ?>/login.php?action=logout" class="nav-link sidebar-logout-link" title="Cerrar Sesión">
            <div class="nav-link-left">
                <i class="ph ph-sign-out" style="margin: 0;"></i>
                <span class="nav-text">Cerrar Sesión</span>
            </div>
        </a>
        
        <!-- Theme Toggle Pill -->
        <div class="sidebar-theme-switch-wrapper" style="padding: 0 16px;">
            <div class="sidebar-theme-switch">
                <button type="button" class="theme-btn light active" id="btnThemeLight" title="Light Theme">
                    <i class="ph-fill ph-sun"></i> <span class="nav-text">Light</span>
                </button>
                <button type="button" class="theme-btn dark" id="btnThemeDark" title="Dark Theme">
                    <i class="ph ph-moon"></i> <span class="nav-text">Dark</span>
                </button>
            </div>
        </div>
    </div>
</aside>
<main class="main-content">
    <header class="header">
        <div class="header-left">
            <button id="sidebarToggle" class="btn-icon"><i class="ph ph-list"></i></button>
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
