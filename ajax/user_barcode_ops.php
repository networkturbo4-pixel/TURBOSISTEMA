<?php
require_once '../config/db.php';
requireLogin();

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

try {
    if ($action === 'generate') {
        $user_id = intval($_POST['user_id'] ?? 0);
        if (!$user_id) throw new Exception("User ID inválido.");

        $stmt = $pdo->prepare("SELECT barcode FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $current = $stmt->fetchColumn();

        if ($current) {
            echo json_encode(['success' => true, 'barcode' => $current]);
            exit;
        }

        // Generate a random 6-character alphanumeric code
        $random = strtoupper(bin2hex(random_bytes(3)));
        $barcode = "USR-" . $random;

        // Ensure uniqueness
        $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE barcode = ?");
        $check->execute([$barcode]);
        while ($check->fetchColumn() > 0) {
            $random = strtoupper(bin2hex(random_bytes(3)));
            $barcode = "USR-" . $random;
            $check->execute([$barcode]);
        }

        $update = $pdo->prepare("UPDATE users SET barcode = ? WHERE id = ?");
        $update->execute([$barcode, $user_id]);

        echo json_encode(['success' => true, 'barcode' => $barcode]);
        exit;
    }

    if ($action === 'lookup') {
        $barcode = $_POST['barcode'] ?? '';
        if (!$barcode) throw new Exception("Código de barras no proporcionado.");

        $stmt = $pdo->prepare("SELECT id, name, profile_picture FROM users WHERE barcode = ?");
        $stmt->execute([$barcode]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            throw new Exception("Usuario no encontrado.");
        }

        echo json_encode(['success' => true, 'user' => $user]);
        exit;
    }

    if ($action === 'attendance') {
        $user_id = intval($_POST['user_id'] ?? 0);
        $type = $_POST['type'] ?? '';
        if (!$user_id || !in_array($type, ['entrada', 'salida'])) {
            throw new Exception("Datos inválidos para asistencia.");
        }

        $stmt = $pdo->prepare("INSERT INTO attendance_logs (user_id, type) VALUES (?, ?)");
        $stmt->execute([$user_id, $type]);

        echo json_encode(['success' => true, 'message' => "Asistencia ($type) registrada correctamente."]);
        exit;
    }

    if ($action === 'assign_sku') {
        $user_id = intval($_POST['user_id'] ?? 0);
        $sku_code = trim($_POST['sku_code'] ?? '');

        if (!$user_id || !$sku_code) {
            throw new Exception("Datos incompletos.");
        }

        // Fetch SKU and Product
        $stmt = $pdo->prepare("
            SELECT s.id, s.product_id, s.sku_code, s.assigned_to, p.name as product_name, p.is_bulk, s.is_epp
            FROM inventory_skus s 
            JOIN inventory_products p ON s.product_id = p.id 
            WHERE s.sku_code = ? AND s.is_deleted = 0
        ");
        $stmt->execute([$sku_code]);
        $sku = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$sku) {
            throw new Exception("Código de producto (SKU) no encontrado.");
        }

        if ($sku['assigned_to'] == $user_id) {
            throw new Exception("El producto ya está asignado a este usuario.");
        }

        // Get names for log
        $userStmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
        $userStmt->execute([$user_id]);
        $newUserName = $userStmt->fetchColumn();

        $byName = $_SESSION['user_name'] ?? 'Sistema';
        $byId = $_SESSION['user_id'];

        $oldUserName = '';
        if ($sku['assigned_to']) {
            $userStmt->execute([$sku['assigned_to']]);
            $oldUserName = $userStmt->fetchColumn() ?: '';
        }

        $pdo->beginTransaction();

        $update = $pdo->prepare("UPDATE inventory_skus SET assigned_to = ? WHERE id = ?");
        $update->execute([$user_id, $sku['id']]);

        // Add to log
        $logStmt = $pdo->prepare("
            INSERT INTO inventory_assignment_log 
            (sku_id, product_id, sku_code, product_name, assigned_to, assigned_to_name, assigned_by, assigned_by_name, quantity, is_epp, action) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, 'assign')
        ");
        $logStmt->execute([
            $sku['id'], 
            $sku['product_id'], 
            $sku['sku_code'], 
            $sku['product_name'], 
            $user_id, 
            $newUserName, 
            $byId, 
            $byName, 
            $sku['is_epp'] ?? 0
        ]);

        $pdo->commit();

        echo json_encode([
            'success' => true, 
            'message' => "Producto asignado a $newUserName.",
            'product_name' => $sku['product_name']
        ]);
        exit;
    }

    throw new Exception("Acción no válida.");
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
