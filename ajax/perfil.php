<?php
require_once '../config/db.php';
requireLogin();

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$user_id = $_SESSION['user_id'];

try {
    switch ($action) {
        case 'get_profile':
            $stmt = $pdo->prepare("SELECT id, username, name, email, whatsapp, role, profile_picture, cover_picture FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $user]);
            break;

        case 'update_profile':
            $username = trim($_POST['username'] ?? '');
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $whatsapp = trim($_POST['whatsapp'] ?? '');
            $password = $_POST['password'] ?? '';
            
            if (!$name || !$email) {
                echo json_encode(['success' => false, 'message' => 'Nombre y correo son requeridos']);
                break;
            }

            // Validar email unico
            $checkEmail = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $checkEmail->execute([$email, $user_id]);
            if ($checkEmail->fetch()) {
                echo json_encode(['success' => false, 'message' => 'El correo ya está en uso']);
                break;
            }

            $sql = "UPDATE users SET username = ?, name = ?, email = ?, whatsapp = ? WHERE id = ?";
            $params = [$username, $name, $email, $whatsapp, $user_id];
            
            if ($password) {
                $sql = "UPDATE users SET username = ?, name = ?, email = ?, whatsapp = ?, password = ? WHERE id = ?";
                $params = [$username, $name, $email, $whatsapp, password_hash($password, PASSWORD_DEFAULT), $user_id];
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            // Handle Photo Uploads
            $uploadDir = '../uploads/profiles/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

            if (!empty($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, $allowed)) {
                    $filename = 'prof_' . $user_id . '_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadDir . $filename)) {
                        $ruta = 'uploads/profiles/' . $filename;
                        $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?")->execute([$ruta, $user_id]);
                        $_SESSION['profile_picture'] = $ruta; // update session
                    }
                }
            }

            if (!empty($_FILES['cover_picture']) && $_FILES['cover_picture']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['cover_picture']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, $allowed)) {
                    $filename = 'cov_' . $user_id . '_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($_FILES['cover_picture']['tmp_name'], $uploadDir . $filename)) {
                        $ruta = 'uploads/profiles/' . $filename;
                        $pdo->prepare("UPDATE users SET cover_picture = ? WHERE id = ?")->execute([$ruta, $user_id]);
                    }
                }
            }

            // Refetch updated data to send back
            $stmt = $pdo->prepare("SELECT id, username, name, email, whatsapp, role, profile_picture, cover_picture FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $updated_user = $stmt->fetch(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'message' => 'Perfil actualizado correctamente', 'data' => $updated_user]);
            break;

        case 'get_epp':
            $sql = "SELECT s.id, s.sku_code as code, s.status, p.name as product_name, c.name as category_name, 1 as qty,
                           COALESCE(
                               (SELECT sp.ruta_archivo FROM inventory_sku_photos sp WHERE sp.sku_id = s.id ORDER BY sp.id ASC LIMIT 1),
                               p.product_image
                           ) as image
                    FROM inventory_skus s
                    JOIN inventory_products p ON s.product_id = p.id
                    LEFT JOIN inventory_categories c ON p.category_id = c.id
                    WHERE s.assigned_to = ? AND s.is_epp = 1
                    
                    UNION ALL
                    
                    SELECT u.product_id as id, p.master_sku as code, 'disponible' as status, p.name as product_name, c.name as category_name, u.quantity as qty,
                           p.product_image as image
                    FROM inventory_user_stock u
                    JOIN inventory_products p ON u.product_id = p.id
                    LEFT JOIN inventory_categories c ON p.category_id = c.id
                    WHERE u.user_id = ? AND u.quantity > 0 AND u.is_epp = 1";
                    
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id, $user_id]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $items]);
            break;

        case 'get_mochila':
            $sql = "SELECT s.id, s.sku_code as code, s.status, p.name as product_name, c.name as category_name, 1 as qty,
                           COALESCE(
                               (SELECT sp.ruta_archivo FROM inventory_sku_photos sp WHERE sp.sku_id = s.id ORDER BY sp.id ASC LIMIT 1),
                               p.product_image
                           ) as image
                    FROM inventory_skus s
                    JOIN inventory_products p ON s.product_id = p.id
                    LEFT JOIN inventory_categories c ON p.category_id = c.id
                    WHERE s.assigned_to = ? AND s.status != 'instalado' AND s.is_epp = 0
                    
                    UNION ALL
                    
                    SELECT u.product_id as id, p.master_sku as code, 'disponible' as status, p.name as product_name, c.name as category_name, u.quantity as qty,
                           p.product_image as image
                    FROM inventory_user_stock u
                    JOIN inventory_products p ON u.product_id = p.id
                    LEFT JOIN inventory_categories c ON p.category_id = c.id
                    WHERE u.user_id = ? AND u.quantity > 0 AND u.is_epp = 0";
                    
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id, $user_id]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $items]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
