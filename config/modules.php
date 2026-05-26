<?php
/**
 * Configuración Centralizada de Módulos
 * Aquí se definen todos los módulos del sistema.
 * Agrega un nuevo elemento a este array para que aparezca automáticamente
 * en el Sidebar, Dashboard y la Configuración de Permisos de Roles.
 */

$system_modules = [
    'dashboard' => [
        'name' => 'Panel de Control',
        'icon' => 'ph-squares-four',
        'url' => '/',
        'description' => 'Vista principal con métricas y accesos directos',
        'default_access' => true // El dashboard siempre debería ser visible para quienes tienen login
    ],
    'actas' => [
        'name' => 'Actas de Instalación',
        'icon' => 'ph-clipboard-text',
        'url' => '/modules/actas',
        'description' => 'Gestión de actas, equipos, materiales y fotos',
        'default_access' => false
    ],
    'clientes' => [
        'name' => 'Clientes',
        'icon' => 'ph-users',
        'url' => '/modules/clientes',
        'description' => 'Registro y visualización de clientes',
        'default_access' => false
    ],
    'mochila' => [
        'name' => 'Mochila',
        'icon' => 'ph-backpack',
        'url' => '/modules/mochila',
        'description' => 'Vista de mochilas de usuarios y control fotográfico de equipos',
        'default_access' => false
    ],
    'soporte' => [
        'name' => 'Soporte',
        'icon' => 'ph-headset',
        'url' => '/modules/soporte',
        'description' => 'Gestión de tickets y chat de soporte',
        'default_access' => false
    ],
    'inventario' => [
        'name' => 'Inventario',
        'icon' => 'ph-package',
        'url' => '/modules/inventario',
        'description' => 'Gestión de productos, control de stock y etiquetas',
        'default_access' => false
    ],
    'perfil' => [
        'name' => 'Mi Perfil',
        'icon' => 'ph-user-circle',
        'url' => '/modules/perfil',
        'description' => 'Gestión de perfil, EPP y mochila',
        'default_access' => true // Everyone should access their own profile
    ],
    'settings' => [
        'name' => 'Configuración',
        'icon' => 'ph-gear',
        'url' => '/modules/settings',
        'description' => 'Ajustes globales, roles, usuarios y apariencia',
        'default_access' => false
    ]
];

/**
 * Helper para verificar si el usuario tiene acceso a un módulo específico
 */
function hasAccess($pdo, $module_key) {
    global $system_modules;
    
    if (!isset($_SESSION['user_id'])) return false;
    
    // El administrador tiene acceso a todo (case-insensitive y variaciones)
    if (isset($_SESSION['user_role'])) {
        $role_lower = strtolower(trim($_SESSION['user_role']));
        if ($role_lower === 'admin' || $role_lower === 'administrador') {
            return true;
        }
    }
    
    // Si el módulo está configurado para ser público (para logueados)
    if (isset($system_modules[$module_key]) && $system_modules[$module_key]['default_access'] === true) {
        return true;
    }
    
    // Si no es admin, chequear permisos en BD
    try {
        $stmt = $pdo->prepare("
            SELECT rp.can_view 
            FROM role_permissions rp
            JOIN roles r ON rp.role_id = r.id
            WHERE r.name = ? AND rp.module = ?
        ");
        $stmt->execute([$_SESSION['user_role'], $module_key]);
        $perm = $stmt->fetch();
        
        return ($perm && $perm['can_view'] == 1);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Helper para bloquear el acceso a una página
 */
function requirePermission($pdo, $module_key) {
    if (!hasAccess($pdo, $module_key)) {
        header("HTTP/1.1 403 Forbidden");
        echo "<h2>403 - Acceso Denegado</h2><p>No tienes permiso para ver este módulo.</p><a href='" . BASE_URL . "'>Volver al inicio</a>";
        exit;
    }
}
?>
