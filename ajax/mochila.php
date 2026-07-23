<?php
require_once '../config/db.php';
requireLogin();

header('Content-Type: application/json');

// Verificar permisos al módulo
if (!hasAccess($pdo, 'mochila')) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado. No tienes permisos para este módulo.']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {

        // ── Lista de usuarios con conteo de mochila (excluye clientes) ──
        case 'list_users':
            $stmt = $pdo->query("
                SELECT u.id, u.name, u.email, u.whatsapp, u.role, u.profile_picture,
                    (SELECT COUNT(*) FROM inventory_skus WHERE assigned_to = u.id AND status != 'instalado') as normal_items,
                    (SELECT COALESCE(SUM(quantity), 0) FROM inventory_user_stock WHERE user_id = u.id) as bulk_items,
                    (SELECT COUNT(*) FROM inventory_skus s 
                     JOIN inventory_products p ON s.product_id = p.id 
                     WHERE s.assigned_to = u.id AND s.status != 'instalado' 
                     AND p.requires_photos = 1 
                     AND (SELECT COUNT(*) FROM inventory_sku_photos WHERE sku_id = s.id) = 0) as sin_fotos
                FROM users u
                WHERE LOWER(u.role) NOT IN ('cliente', 'client')
                ORDER BY u.name ASC
            ");
            $users = $stmt->fetchAll();
            
            foreach ($users as &$u) {
                $u['total_items'] = intval($u['normal_items']) + intval($u['bulk_items']);
            }
            
            echo json_encode(['success' => true, 'data' => $users]);
            break;

        // ── Mochila completa de un usuario ──
        case 'get_user_backpack':
            $target_user_id = intval($_POST['user_id'] ?? $_GET['user_id'] ?? 0);
            if (!$target_user_id) {
                echo json_encode(['success' => false, 'message' => 'ID de usuario requerido']);
                break;
            }

            // Info del usuario
            $stmtUser = $pdo->prepare("SELECT id, name, email, role, profile_picture FROM users WHERE id = ?");
            $stmtUser->execute([$target_user_id]);
            $userData = $stmtUser->fetch();

            if (!$userData) {
                echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
                break;
            }

            // SKUs normales asignados
            $stmtNormal = $pdo->prepare("
                SELECT s.id, s.sku_code, s.status, s.historia, s.custom_data, s.created_at,
                       p.id as product_id, p.name as product_name, p.requires_photos, p.product_image,
                       c.name as category_name,
                       (SELECT COUNT(*) FROM inventory_sku_photos WHERE sku_id = s.id) as photo_count
                FROM inventory_skus s
                JOIN inventory_products p ON s.product_id = p.id
                LEFT JOIN inventory_categories c ON p.category_id = c.id
                WHERE s.assigned_to = ? AND s.status != 'instalado'
                ORDER BY p.name ASC, s.sku_code ASC
            ");
            $stmtNormal->execute([$target_user_id]);
            $normalItems = $stmtNormal->fetchAll();

            // Productos a granel asignados (product_type = 'granel' or is_bulk = 1)
            $stmtBulk = $pdo->prepare("
                SELECT us.id as stock_id, us.quantity, 
                       p.id as product_id, p.name as product_name, p.master_sku, p.unit_type, p.product_image, p.created_at,
                       c.name as category_name
                FROM inventory_user_stock us
                JOIN inventory_products p ON us.product_id = p.id
                LEFT JOIN inventory_categories c ON p.category_id = c.id
                WHERE us.user_id = ? AND us.quantity > 0 AND (p.product_type = 'granel' OR p.is_bulk = 1)
                ORDER BY p.name ASC
            ");
            $stmtBulk->execute([$target_user_id]);
            $bulkItems = $stmtBulk->fetchAll();

            // Productos agrupados asignados (product_type = 'agrupado')
            $stmtGrouped = $pdo->prepare("
                SELECT us.id as stock_id, us.quantity, 
                       p.id as product_id, p.name as product_name, p.master_sku, p.unit_type, p.product_image, p.created_at,
                       c.name as category_name
                FROM inventory_user_stock us
                JOIN inventory_products p ON us.product_id = p.id
                LEFT JOIN inventory_categories c ON p.category_id = c.id
                WHERE us.user_id = ? AND us.quantity > 0 AND p.product_type = 'agrupado'
                ORDER BY p.name ASC
            ");
            $stmtGrouped->execute([$target_user_id]);
            $groupedItems = $stmtGrouped->fetchAll();

            echo json_encode([
                'success' => true,
                'user' => $userData,
                'normal_items' => $normalItems,
                'bulk_items' => $bulkItems,
                'grouped_items' => $groupedItems
            ]);
            break;

        // ── Obtener fotos de un SKU ──
        case 'get_sku_photos':
            $sku_id = intval($_POST['sku_id'] ?? $_GET['sku_id'] ?? 0);
            if (!$sku_id) {
                echo json_encode(['success' => false, 'message' => 'ID de SKU requerido']);
                break;
            }

            $stmt = $pdo->prepare("
                SELECT sp.*, u.name as uploaded_by_name 
                FROM inventory_sku_photos sp
                JOIN users u ON sp.uploaded_by = u.id
                WHERE sp.sku_id = ?
                ORDER BY sp.created_at DESC
            ");
            $stmt->execute([$sku_id]);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        // ── Subir fotos a un SKU (solo admin) ──
        case 'upload_sku_photo':
            $sku_id = intval($_POST['sku_id'] ?? 0);
            $nota = trim($_POST['nota'] ?? '');
            $uploaded_by = $_SESSION['user_id'];

            if (!$sku_id) {
                echo json_encode(['success' => false, 'message' => 'ID de SKU requerido']);
                break;
            }

            // Verificar que el SKU existe
            $check = $pdo->prepare("SELECT id FROM inventory_skus WHERE id = ?");
            $check->execute([$sku_id]);
            if (!$check->fetch()) {
                echo json_encode(['success' => false, 'message' => 'SKU no encontrado']);
                break;
            }

            $status = $_POST['status'] ?? '';
            $isStatusUpdate = ($status && in_array($status, ['disponible', 'instalado', 'malogrado', 'reparado', 'en_transito']));
            $entry_id = null;

            if ($isStatusUpdate) {
                // Actualizar status e historia
                $pdo->prepare("UPDATE inventory_skus SET status = ?, historia = ? WHERE id = ?")->execute([$status, $status, $sku_id]);
                // Crear entrada en historial de asignaciones
                $stmt = $pdo->prepare("INSERT INTO inventory_entries (sku_id, user_id, tipo, notas) VALUES (?, ?, ?, ?)");
                $stmt->execute([$sku_id, $uploaded_by, $status, $nota ?: null]);
                $entry_id = $pdo->lastInsertId();
            }

            $uploadDir = '../uploads/sku_photos/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $uploaded = 0;
            $lastUploadedPath = null;
            if (!empty($_FILES['photos'])) {
                $photos = $_FILES['photos'];
                $count = is_array($photos['name']) ? count($photos['name']) : 1;

                for ($i = 0; $i < $count; $i++) {
                    $name = is_array($photos['name']) ? $photos['name'][$i] : $photos['name'];
                    $tmpName = is_array($photos['tmp_name']) ? $photos['tmp_name'][$i] : $photos['tmp_name'];
                    $error = is_array($photos['error']) ? $photos['error'][$i] : $photos['error'];

                    if ($error === UPLOAD_ERR_OK) {
                        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                        if (!in_array($ext, $allowed)) continue;

                        $filename = 'sku_' . $sku_id . '_' . uniqid() . '.' . $ext;
                        if (move_uploaded_file($tmpName, $uploadDir . $filename)) {
                            $ruta = 'uploads/sku_photos/' . $filename;
                            
                            if ($isStatusUpdate && $entry_id) {
                                // Guardar como foto del historial de asignaciones
                                $stmt = $pdo->prepare("INSERT INTO inventory_entry_photos (entry_id, ruta_archivo) VALUES (?, ?)");
                                $stmt->execute([$entry_id, $ruta]);
                            } else {
                                // Guardar como foto normal del SKU
                                $stmt = $pdo->prepare("INSERT INTO inventory_sku_photos (sku_id, ruta_archivo, uploaded_by, nota) VALUES (?, ?, ?, ?)");
                                $stmt->execute([$sku_id, $ruta, $uploaded_by, $nota ?: null]);
                            }
                            
                            $lastUploadedPath = $ruta;
                            $uploaded++;
                        }
                    }
                }
            }

            if ($uploaded > 0) {
                // Obtener product_id del SKU
                $skuRow = $pdo->prepare("SELECT product_id FROM inventory_skus WHERE id = ?");
                $skuRow->execute([$sku_id]);
                $skuData = $skuRow->fetch();
                $product_image = null;

                if ($skuData && !$isStatusUpdate) {
                    // Tomar la primera foto normal del SKU como thumbnail del producto
                    $firstSkuPhoto = $pdo->prepare("SELECT ruta_archivo FROM inventory_sku_photos WHERE sku_id = ? ORDER BY id ASC LIMIT 1");
                    $firstSkuPhoto->execute([$sku_id]);
                    $firstPhotoRow = $firstSkuPhoto->fetch();
                    if ($firstPhotoRow) {
                        $product_image = $firstPhotoRow['ruta_archivo'];
                        // Actualizar product_image solo si el producto no tiene ya una foto de producto
                        $curImg = $pdo->prepare("SELECT product_image FROM inventory_products WHERE id = ?");
                        $curImg->execute([$skuData['product_id']]);
                        $curImgRow = $curImg->fetch();
                        if (!$curImgRow['product_image']) {
                            $pdo->prepare("UPDATE inventory_products SET product_image = ? WHERE id = ?")
                               ->execute([$product_image, $skuData['product_id']]);
                        }
                    }
                }

                echo json_encode([
                    'success'       => true,
                    'message'       => "$uploaded foto(s) subida(s)",
                    'count'         => $uploaded,
                    'product_id'    => $skuData['product_id'] ?? null,
                    'product_image' => $product_image,
                    'sku_photo'     => $lastUploadedPath
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'No se pudo subir ninguna foto']);
            }
            break;

        // ── Subir foto de un producto a granel (actualiza product_image) ──
        case 'upload_product_photo':
            $product_id  = intval($_POST['product_id'] ?? 0);
            $nota        = trim($_POST['nota'] ?? '');

            if (!$product_id) {
                echo json_encode(['success' => false, 'message' => 'ID de producto requerido']);
                break;
            }

            // Verificar que el producto existe
            $checkP = $pdo->prepare("SELECT id, name FROM inventory_products WHERE id = ?");
            $checkP->execute([$product_id]);
            $productRow = $checkP->fetch();
            if (!$productRow) {
                echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
                break;
            }

            $uploadDir = '../uploads/product_photos/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $savedPath = null;
            if (!empty($_FILES['photos'])) {
                $photos  = $_FILES['photos'];
                $tmpName = is_array($photos['tmp_name']) ? $photos['tmp_name'][0] : $photos['tmp_name'];
                $name    = is_array($photos['name'])     ? $photos['name'][0]     : $photos['name'];
                $error   = is_array($photos['error'])    ? $photos['error'][0]    : $photos['error'];

                if ($error === UPLOAD_ERR_OK) {
                    $ext     = strtolower(pathinfo($name, PATHINFO_EXTENSION)) ?: 'jpg';
                    $allowed = ['jpg','jpeg','png','webp','gif'];
                    if (in_array($ext, $allowed)) {
                        $filename  = 'product_' . $product_id . '_' . uniqid() . '.' . $ext;
                        if (move_uploaded_file($tmpName, $uploadDir . $filename)) {
                            $savedPath = 'uploads/product_photos/' . $filename;
                            // Actualizar imagen del producto
                            $pdo->prepare("UPDATE inventory_products SET product_image = ? WHERE id = ?")
                                ->execute([$savedPath, $product_id]);
                        }
                    }
                }
            }

            if ($savedPath) {
                echo json_encode([
                    'success'       => true,
                    'message'       => 'Foto del producto guardada',
                    'product_image' => $savedPath,
                    'product_id'    => $product_id
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'No se pudo subir la foto']);
            }
            break;

        // ── Eliminar foto de SKU ──
        case 'delete_sku_photo':
            $photo_id = intval($_POST['photo_id'] ?? 0);
            if (!$photo_id) {
                echo json_encode(['success' => false, 'message' => 'ID de foto requerido']);
                break;
            }

            // Get file path before deleting
            $stmt = $pdo->prepare("SELECT ruta_archivo FROM inventory_sku_photos WHERE id = ?");
            $stmt->execute([$photo_id]);
            $photo = $stmt->fetch();

            if ($photo) {
                $filePath = '../' . $photo['ruta_archivo'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                $pdo->prepare("DELETE FROM inventory_sku_photos WHERE id = ?")->execute([$photo_id]);
                echo json_encode(['success' => true, 'message' => 'Foto eliminada']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Foto no encontrada']);
            }
            break;

        // ── Devolver SKU al almacén ──
        case 'return_to_warehouse':
            $sku_id_raw = $_POST['sku_id'] ?? '';

            if (strpos($sku_id_raw, 'bulk_') === 0) {
                // Devolución de granel
                $stock_id = intval(str_replace('bulk_', '', $sku_id_raw));
                $quantity = floatval($_POST['quantity'] ?? 0);

                if ($quantity <= 0) {
                    echo json_encode(['success' => false, 'message' => 'Cantidad inválida']);
                    break;
                }

                $pdo->beginTransaction();

                // Get product_id and user_id from stock
                $stmtStock = $pdo->prepare("SELECT user_id, product_id, quantity FROM inventory_user_stock WHERE id = ?");
                $stmtStock->execute([$stock_id]);
                $stock = $stmtStock->fetch();

                if (!$stock || $stock['quantity'] < $quantity) {
                    $pdo->rollBack();
                    echo json_encode(['success' => false, 'message' => 'Stock insuficiente del usuario']);
                    break;
                }

                // Restar del usuario
                $pdo->prepare("UPDATE inventory_user_stock SET quantity = quantity - ? WHERE id = ?")->execute([$quantity, $stock_id]);
                // Sumar al almacén
                $pdo->prepare("UPDATE inventory_products SET total_quantity = total_quantity + ? WHERE id = ?")->execute([$quantity, $stock['product_id']]);

                $pdo->commit();
                echo json_encode(['success' => true, 'message' => "Devuelto $quantity al almacén"]);
                break;
            }

            $sku_id = intval($sku_id_raw);
            if (!$sku_id) {
                echo json_encode(['success' => false, 'message' => 'ID inválido']);
                break;
            }

            $stmt = $pdo->prepare("UPDATE inventory_skus SET assigned_to = NULL, status = 'disponible' WHERE id = ?");
            $stmt->execute([$sku_id]);
            echo json_encode(['success' => true, 'message' => 'SKU devuelto al almacén']);
            break;

        // ── Reasignar SKU a otro usuario ──
        case 'reassign_sku':
            $sku_id = intval($_POST['sku_id'] ?? 0);
            $new_user_id = intval($_POST['new_user_id'] ?? 0);

            if (!$sku_id || !$new_user_id) {
                echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
                break;
            }

            $uname = $pdo->prepare("SELECT name FROM users WHERE id = ?");
            $uname->execute([$new_user_id]);
            $name = $uname->fetchColumn();

            $stmt = $pdo->prepare("UPDATE inventory_skus SET assigned_to = ? WHERE id = ?");
            $stmt->execute([$new_user_id, $sku_id]);
            echo json_encode(['success' => true, 'message' => "Reasignado a $name"]);
            break;

        // ── Métricas generales ──
        case 'get_stats':
            // Total items en campo (asignados a usuarios)
            $totalNormal = $pdo->query("SELECT COUNT(*) FROM inventory_skus WHERE assigned_to IS NOT NULL AND status != 'instalado'")->fetchColumn();
            $totalBulk = $pdo->query("SELECT COALESCE(SUM(quantity), 0) FROM inventory_user_stock WHERE quantity > 0")->fetchColumn();
            $totalEnCampo = intval($totalNormal) + intval($totalBulk);

            // Usuarios con mochila activa
            $usersActivos = $pdo->query("
                SELECT COUNT(DISTINCT user_id) FROM (
                    SELECT assigned_to as user_id FROM inventory_skus WHERE assigned_to IS NOT NULL AND status != 'instalado'
                    UNION
                    SELECT user_id FROM inventory_user_stock WHERE quantity > 0
                ) as activos
            ")->fetchColumn();

            // Items sin fotos (solo de productos que requieren fotos)
            $sinFotos = $pdo->query("
                SELECT COUNT(*) FROM inventory_skus s
                JOIN inventory_products p ON s.product_id = p.id
                WHERE s.assigned_to IS NOT NULL 
                AND s.status != 'instalado'
                AND p.requires_photos = 1
                AND (SELECT COUNT(*) FROM inventory_sku_photos WHERE sku_id = s.id) = 0
            ")->fetchColumn();

            echo json_encode(['success' => true, 'data' => [
                'total_en_campo' => $totalEnCampo,
                'usuarios_activos' => intval($usersActivos),
                'sin_fotos' => intval($sinFotos)
            ]]);
            break;

        // ── Historial de asignaciones de un SKU ──
        case 'get_sku_history':
            $sku_id = intval($_POST['sku_id'] ?? $_GET['sku_id'] ?? 0);
            if (!$sku_id) {
                echo json_encode(['success' => false, 'message' => 'ID de SKU requerido']);
                break;
            }

            $stmt = $pdo->prepare("
                SELECT e.id, e.tipo, e.notas, e.created_at,
                       u.name as user_name, u.role as user_role,
                       (SELECT GROUP_CONCAT(ruta_archivo) FROM inventory_entry_photos WHERE entry_id = e.id) as photos
                FROM inventory_entries e
                JOIN users u ON e.user_id = u.id
                WHERE e.sku_id = ?
                ORDER BY e.created_at DESC
                LIMIT 20
            ");
            $stmt->execute([$sku_id]);
            $entries = $stmt->fetchAll();

            // También incluir el campo historia del SKU
            $skuStmt = $pdo->prepare("SELECT historia, created_at FROM inventory_skus WHERE id = ?");
            $skuStmt->execute([$sku_id]);
            $skuRow = $skuStmt->fetch();

            echo json_encode([
                'success' => true,
                'data'    => $entries,
                'historia_estado' => $skuRow['historia'] ?? 'ninguno',
                'created_at' => $skuRow['created_at'] ?? null
            ]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
