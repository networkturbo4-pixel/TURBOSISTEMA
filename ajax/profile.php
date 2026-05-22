<?php
require_once '../config/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'get') {
    try {
        $stmt = $pdo->prepare("SELECT name, email, whatsapp, profile_picture FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Obtener productos asignados a la mochila del usuario
            $stmtMochila = $pdo->prepare("
                SELECT s.sku_code, p.name as product_name, c.name as category_name 
                FROM inventory_skus s 
                JOIN inventory_products p ON s.product_id = p.id 
                LEFT JOIN inventory_categories c ON p.category_id = c.id 
                WHERE s.assigned_to = ? AND s.status != 'instalado'
            ");
            $stmtMochila->execute([$user_id]);
            $normalItems = $stmtMochila->fetchAll(PDO::FETCH_ASSOC);

            // Obtener productos a granel asignados a la mochila del usuario
            $stmtBulk = $pdo->prepare("
                SELECT p.master_sku as sku_code, p.name as product_name, c.name as category_name, us.quantity as bulk_quantity, p.unit_type
                FROM inventory_user_stock us
                JOIN inventory_products p ON us.product_id = p.id
                LEFT JOIN inventory_categories c ON p.category_id = c.id
                WHERE us.user_id = ? AND us.quantity > 0
            ");
            $stmtBulk->execute([$user_id]);
            $bulkItems = $stmtBulk->fetchAll(PDO::FETCH_ASSOC);

            $user['mochila'] = array_merge($normalItems, $bulkItems);

            echo json_encode(['success' => true, 'data' => $user]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'update') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $whatsapp = trim($_POST['whatsapp'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($name) || empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Nombre y correo son obligatorios']);
        exit;
    }

    try {
        // Upload profile picture if provided
        $profile_picture = null;
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/profiles/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileExtension = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            
            if (in_array($fileExtension, $allowedExtensions)) {
                $fileName = 'user_' . $user_id . '_' . time() . '.' . $fileExtension;
                $targetFile = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetFile)) {
                    $profile_picture = 'uploads/profiles/' . $fileName;
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error al subir la imagen']);
                    exit;
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Formato de imagen no permitido']);
                exit;
            }
        }

        // Build query dynamically based on what's being updated
        $query = "UPDATE users SET name = ?, email = ?, whatsapp = ?";
        $params = [$name, $email, $whatsapp];

        if (!empty($password)) {
            $query .= ", password = ?";
            $params[] = password_hash($password, PASSWORD_DEFAULT);
        }

        if ($profile_picture) {
            $query .= ", profile_picture = ?";
            $params[] = $profile_picture;
            $_SESSION['profile_picture'] = $profile_picture; // Update session
        }

        $query .= " WHERE id = ?";
        $params[] = $user_id;

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);

        // Update session name
        $_SESSION['user_name'] = $name;

        echo json_encode([
            'success' => true, 
            'message' => 'Perfil actualizado exitosamente',
            'profile_picture' => $profile_picture ?? ($_SESSION['profile_picture'] ?? null)
        ]);

    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            echo json_encode(['success' => false, 'message' => 'El correo ya está registrado por otro usuario.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar: ' . $e->getMessage()]);
        }
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Acción no válida']);
