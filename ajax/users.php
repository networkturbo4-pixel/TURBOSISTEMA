<?php
require_once '../config/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'delete') {
    $id = $_POST['id'] ?? null;
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID no proporcionado']);
        exit;
    }
    // Prevent deleting your own account
    if ($id == $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'No puedes eliminar tu propia cuenta']);
        exit;
    }
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Usuario eliminado correctamente']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar usuario: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'list') {
    try {
        $stmt = $pdo->query("
            SELECT u.id, u.name, u.email, u.role, u.pin, u.created_at, c.dni 
            FROM users u 
            LEFT JOIN clientes c ON u.id = c.user_id 
            ORDER BY u.id DESC
        ");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $users]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener usuarios: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'update') {
    $id = $_POST['id'] ?? null;
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $pin = trim($_POST['pin'] ?? '');

    if (!$id || empty($name) || empty($email) || empty($role)) {
        echo json_encode(['success' => false, 'message' => 'Faltan campos obligatorios']);
        exit;
    }

    try {
        // Verificar PIN único, exceptuando el propio usuario
        if (!empty($pin)) {
            $checkPin = $pdo->prepare("SELECT id FROM users WHERE pin = ? AND id != ?");
            $checkPin->execute([$pin, $id]);
            if ($checkPin->fetch()) {
                echo json_encode(['success' => false, 'message' => 'El PIN ya está en uso por otro usuario.']);
                exit;
            }
        }

        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ?, pin = ? WHERE id = ?");
        $stmt->execute([$name, $email, $role, empty($pin) ? null : $pin, $id]);
        echo json_encode(['success' => true, 'message' => 'Usuario actualizado correctamente']);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            echo json_encode(['success' => false, 'message' => 'El correo electrónico ya está registrado por otro usuario.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar usuario: ' . $e->getMessage()]);
        }
    }
    exit;
}

if ($action === 'create') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $pin = trim($_POST['pin'] ?? '');
    // Asignamos una contraseña por defecto si se crea desde aquí
    $password = password_hash('12345678', PASSWORD_DEFAULT); 

    if (empty($name) || empty($email) || empty($role)) {
        echo json_encode(['success' => false, 'message' => 'Faltan campos obligatorios']);
        exit;
    }

    try {
        // Verificar PIN único
        if (!empty($pin)) {
            $checkPin = $pdo->prepare("SELECT id FROM users WHERE pin = ?");
            $checkPin->execute([$pin]);
            if ($checkPin->fetch()) {
                echo json_encode(['success' => false, 'message' => 'El PIN ya está en uso por otro usuario.']);
                exit;
            }
        }

        $stmt = $pdo->prepare("INSERT INTO users (name, email, pin, password, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, empty($pin) ? null : $pin, $password, $role]);
        echo json_encode(['success' => true, 'message' => 'Usuario creado correctamente']);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Constraint violation (Duplicate entry)
            echo json_encode(['success' => false, 'message' => 'El correo electrónico ya está registrado.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al crear usuario: ' . $e->getMessage()]);
        }
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Acción no válida']);
