<?php
require_once 'config/db.php';
requireLogin();

header('Content-Type: text/html; charset=utf-8');

echo "<h2>Diagnóstico de Módulos</h2>";

// 1. Sesión actual
echo "<h3>1. Datos de Sesión</h3>";
echo "<pre>";
echo "user_id: " . ($_SESSION['user_id'] ?? 'NO DEFINIDO') . "\n";
echo "user_name: " . ($_SESSION['user_name'] ?? 'NO DEFINIDO') . "\n";
echo "user_role: '" . ($_SESSION['user_role'] ?? 'NO DEFINIDO') . "'\n";
echo "user_role (lowercase/trimmed): '" . strtolower(trim($_SESSION['user_role'] ?? '')) . "'\n";
echo "user_role hex: " . bin2hex($_SESSION['user_role'] ?? '') . "\n";
echo "</pre>";

// 2. Módulos definidos
echo "<h3>2. Módulos definidos en config/modules.php</h3>";
global $system_modules;
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Key</th><th>Nombre</th><th>URL</th><th>Default Access</th><th>hasAccess()</th></tr>";
foreach ($system_modules as $key => $module) {
    $access = hasAccess($pdo, $key) ? '✅ SI' : '❌ NO';
    echo "<tr>";
    echo "<td>{$key}</td>";
    echo "<td>{$module['name']}</td>";
    echo "<td>{$module['url']}</td>";
    echo "<td>" . ($module['default_access'] ? 'true' : 'false') . "</td>";
    echo "<td>{$access}</td>";
    echo "</tr>";
}
echo "</table>";

// 3. Verificar tablas
echo "<h3>3. Tablas en BD</h3>";
try {
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>Tablas encontradas: " . count($tables) . "</p>";
    $relevantTables = ['roles', 'role_permissions', 'users', 'settings'];
    foreach ($relevantTables as $t) {
        $exists = in_array($t, $tables) ? '✅ Existe' : '❌ NO EXISTE';
        echo "<p>{$t}: {$exists}</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

// 4. Roles
echo "<h3>4. Roles en BD</h3>";
try {
    $roles = $pdo->query("SELECT * FROM roles")->fetchAll();
    echo "<pre>" . print_r($roles, true) . "</pre>";
} catch (PDOException $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

// 5. Role Permissions
echo "<h3>5. Role Permissions en BD</h3>";
try {
    $perms = $pdo->query("SELECT rp.*, r.name as role_name FROM role_permissions rp JOIN roles r ON rp.role_id = r.id")->fetchAll();
    if (count($perms) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Role</th><th>Module</th><th>Can View</th></tr>";
        foreach ($perms as $p) {
            echo "<tr><td>{$p['id']}</td><td>{$p['role_name']}</td><td>{$p['module']}</td><td>{$p['can_view']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p>⚠️ No hay registros en role_permissions</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

// 6. Usuario actual en BD
echo "<h3>6. Usuario actual en BD</h3>";
try {
    $stmt = $pdo->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    echo "<pre>" . print_r($user, true) . "</pre>";
} catch (PDOException $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?>
