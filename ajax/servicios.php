<?php
require_once '../config/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            $stmt = $pdo->query("SELECT * FROM servicios ORDER BY nombre ASC");
            $servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $servicios]);
            break;

        case 'save':
            $id = $_POST['id'] ?? null;
            $nombre = trim($_POST['nombre'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $velocidad = trim($_POST['velocidad'] ?? '');

            if (empty($nombre)) {
                echo json_encode(['success' => false, 'message' => 'El nombre del servicio es requerido']);
                exit;
            }

            if ($id) {
                // Update
                $stmt = $pdo->prepare("UPDATE servicios SET nombre = ?, descripcion = ?, velocidad = ? WHERE id = ?");
                $stmt->execute([$nombre, $descripcion, $velocidad, $id]);
                echo json_encode(['success' => true, 'message' => 'Servicio actualizado correctamente']);
            } else {
                // Insert
                $stmt = $pdo->prepare("INSERT INTO servicios (nombre, descripcion, velocidad) VALUES (?, ?, ?)");
                $stmt->execute([$nombre, $descripcion, $velocidad]);
                echo json_encode(['success' => true, 'message' => 'Servicio creado correctamente']);
            }
            break;

        case 'delete':
            $id = $_POST['id'] ?? null;
            if ($id) {
                // First check if it's used
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM clientes WHERE servicio_id = ?");
                $stmt->execute([$id]);
                $countClientes = $stmt->fetchColumn();

                $stmt = $pdo->prepare("SELECT COUNT(*) FROM actas WHERE servicio_id = ?");
                $stmt->execute([$id]);
                $countActas = $stmt->fetchColumn();

                if ($countClientes > 0 || $countActas > 0) {
                    echo json_encode(['success' => false, 'message' => 'No se puede eliminar el servicio porque está en uso']);
                    exit;
                }

                $stmt = $pdo->prepare("DELETE FROM servicios WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode(['success' => true, 'message' => 'Servicio eliminado correctamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'ID inválido']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
}
