<?php
require_once '../config/db.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'list') {
        try {
            $startDate = $_POST['start_date'] ?? '';
            $endDate = $_POST['end_date'] ?? '';
            $userId = $_POST['user_id'] ?? '';

            $sql = "SELECT a.*, u.name as user_name, u.role as user_role 
                    FROM attendance_logs a 
                    JOIN users u ON a.user_id = u.id 
                    WHERE 1=1";
            $params = [];

            if (!empty($startDate)) {
                $sql .= " AND DATE(a.created_at) >= ?";
                $params[] = $startDate;
            }
            if (!empty($endDate)) {
                $sql .= " AND DATE(a.created_at) <= ?";
                $params[] = $endDate;
            }
            if (!empty($userId)) {
                $sql .= " AND a.user_id = ?";
                $params[] = $userId;
            }

            $sql .= " ORDER BY a.created_at DESC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'data' => $logs]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error al obtener registros: ' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'get_users') {
        try {
            $stmt = $pdo->query("SELECT id, name FROM users ORDER BY name ASC");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $users]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error al obtener usuarios: ' . $e->getMessage()]);
        }
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Acción inválida.']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
exit;
