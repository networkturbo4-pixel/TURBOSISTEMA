<?php
require_once '../config/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Auto-migrate missing columns if they don't exist yet
try {
    $colsRes = $pdo->query("SHOW COLUMNS FROM users");
    $cols = $colsRes ? $colsRes->fetchAll(PDO::FETCH_COLUMN) : [];
    
    if (!in_array('pin', $cols)) {
        @$pdo->exec("ALTER TABLE users ADD COLUMN pin VARCHAR(10) NULL");
    }
    if (!in_array('biometric_id', $cols)) {
        @$pdo->exec("ALTER TABLE users ADD COLUMN biometric_id INT NULL");
    }
} catch (Exception $e) {
    // Fail silently if ALTER TABLE is not permitted
}

// Check current available columns
try {
    $colsRes = $pdo->query("SHOW COLUMNS FROM users");
    $cols = $colsRes ? $colsRes->fetchAll(PDO::FETCH_COLUMN) : [];
} catch (Exception $e) {
    $cols = [];
}
$hasBiometric = in_array('biometric_id', $cols);
$hasPin = in_array('pin', $cols);

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
        $biometricCol = $hasBiometric ? "u.biometric_id" : "NULL AS biometric_id";
        $pinCol = $hasPin ? "u.pin" : "NULL AS pin";

        $stmt = $pdo->query("
            SELECT u.id, u.name, u.email, u.role, {$pinCol}, {$biometricCol}, u.created_at, c.dni 
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
    $biometric_id = trim($_POST['biometric_id'] ?? '');

    if (!$id || empty($name) || empty($email) || empty($role)) {
        echo json_encode(['success' => false, 'message' => 'Faltan campos obligatorios']);
        exit;
    }

    try {
        // Verificar PIN único, exceptuando el propio usuario
        if ($hasPin && !empty($pin)) {
            $checkPin = $pdo->prepare("SELECT id FROM users WHERE pin = ? AND id != ?");
            $checkPin->execute([$pin, $id]);
            if ($checkPin->fetch()) {
                echo json_encode(['success' => false, 'message' => 'El PIN ya está en uso por otro usuario.']);
                exit;
            }
        }

        $updateFields = ["name = ?", "email = ?", "role = ?"];
        $params = [$name, $email, $role];

        if ($hasPin) {
            $updateFields[] = "pin = ?";
            $params[] = empty($pin) ? null : $pin;
        }
        if ($hasBiometric) {
            $updateFields[] = "biometric_id = ?";
            $params[] = empty($biometric_id) ? null : $biometric_id;
        }

        $params[] = $id;
        $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
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
    $biometric_id = trim($_POST['biometric_id'] ?? '');
    // Asignamos una contraseña por defecto si se crea desde aquí
    $password = password_hash('12345678', PASSWORD_DEFAULT); 

    if (empty($name) || empty($email) || empty($role)) {
        echo json_encode(['success' => false, 'message' => 'Faltan campos obligatorios']);
        exit;
    }

    try {
        // Verificar PIN único
        if ($hasPin && !empty($pin)) {
            $checkPin = $pdo->prepare("SELECT id FROM users WHERE pin = ?");
            $checkPin->execute([$pin]);
            if ($checkPin->fetch()) {
                echo json_encode(['success' => false, 'message' => 'El PIN ya está en uso por otro usuario.']);
                exit;
            }
        }

        $fields = ["name", "email", "password", "role"];
        $placeholders = ["?", "?", "?", "?"];
        $params = [$name, $email, $password, $role];

        if ($hasPin) {
            $fields[] = "pin";
            $placeholders[] = "?";
            $params[] = empty($pin) ? null : $pin;
        }
        if ($hasBiometric) {
            $fields[] = "biometric_id";
            $placeholders[] = "?";
            $params[] = empty($biometric_id) ? null : $biometric_id;
        }

        $sql = "INSERT INTO users (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
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
