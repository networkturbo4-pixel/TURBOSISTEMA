<?php
require_once '../config/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

$PROTECTED_ROLES = ['admin', 'administrador', 'tecnico', 'técnico', 'cliente'];

if ($action === 'delete') {
    $id = $_POST['id'] ?? null;
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID no proporcionado']);
        exit;
    }
    // Check if role is protected
    $stmtCheck = $pdo->prepare("SELECT name FROM roles WHERE id = ?");
    $stmtCheck->execute([$id]);
    $roleName = strtolower(trim($stmtCheck->fetchColumn() ?: ''));
    if (in_array($roleName, $PROTECTED_ROLES)) {
        echo json_encode(['success' => false, 'message' => 'Este rol es del sistema y no se puede eliminar.']);
        exit;
    }
    try {
        $stmt = $pdo->prepare("DELETE FROM roles WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Rol eliminado correctamente']);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            echo json_encode(['success' => false, 'message' => 'No se puede eliminar porque hay usuarios con este rol.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar rol: ' . $e->getMessage()]);
        }
    }
    exit;
}

if ($action === 'list') {
    try {
        $stmt = $pdo->query("SELECT * FROM roles ORDER BY id DESC");
        $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Fetch permissions for all roles
        foreach ($roles as &$r) {
            $stmtPerm = $pdo->prepare("SELECT module FROM role_permissions WHERE role_id = ? AND can_view = 1");
            $stmtPerm->execute([$r['id']]);
            $r['permissions'] = $stmtPerm->fetchAll(PDO::FETCH_COLUMN);
        }
        
        echo json_encode(['success' => true, 'data' => $roles]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener roles: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'update') {
    $id = $_POST['id'] ?? null;
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $permissions = isset($_POST['permissions']) ? $_POST['permissions'] : [];

    if (!$id || empty($name)) {
        echo json_encode(['success' => false, 'message' => 'ID y nombre son obligatorios']);
        exit;
    }

    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("UPDATE roles SET name = ?, description = ? WHERE id = ?");
        $stmt->execute([$name, $description, $id]);
        
        // Update permissions by deleting old ones and inserting new ones
        $stmtDel = $pdo->prepare("DELETE FROM role_permissions WHERE role_id = ?");
        $stmtDel->execute([$id]);
        
        if (!empty($permissions)) {
            $stmtPerm = $pdo->prepare("INSERT INTO role_permissions (role_id, module, can_view) VALUES (?, ?, 1)");
            foreach ($permissions as $module_key) {
                $stmtPerm->execute([$id, $module_key]);
            }
        }
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Rol actualizado correctamente']);
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error al actualizar rol: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'create') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $permissions = isset($_POST['permissions']) ? $_POST['permissions'] : [];

    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'El nombre del rol es obligatorio']);
        exit;
    }

    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("INSERT INTO roles (name, description) VALUES (?, ?)");
        $stmt->execute([$name, $description]);
        $role_id = $pdo->lastInsertId();
        
        // Insert permissions
        if (!empty($permissions)) {
            $stmtPerm = $pdo->prepare("INSERT INTO role_permissions (role_id, module, can_view) VALUES (?, ?, 1)");
            foreach ($permissions as $module_key) {
                $stmtPerm->execute([$role_id, $module_key]);
            }
        }
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Rol creado correctamente']);
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error al crear rol: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Acción no válida']);
