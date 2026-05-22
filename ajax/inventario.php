<?php
require_once '../config/db.php';
requireLogin();

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {

        // ── Categorías ──────────────────────────────────────
        case 'list_categories':
            $stmt = $pdo->query("SELECT * FROM inventory_categories ORDER BY name ASC");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        case 'create_category':
            $name = trim($_POST['name'] ?? '');
            if (!$name) {
                echo json_encode(['success' => false, 'message' => 'El nombre es requerido']);
                break;
            }
            $stmt = $pdo->prepare("INSERT INTO inventory_categories (name) VALUES (?)");
            $stmt->execute([$name]);
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId(), 'message' => 'Categoría creada']);
            break;

        case 'update_category':
            $id = intval($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            if (!$id || !$name) {
                echo json_encode(['success' => false, 'message' => 'ID y nombre son requeridos']);
                break;
            }
            $stmt = $pdo->prepare("UPDATE inventory_categories SET name = ? WHERE id = ?");
            $stmt->execute([$name, $id]);
            echo json_encode(['success' => true, 'message' => 'Categoría actualizada']);
            break;

        case 'delete_category':
            $id = intval($_POST['id'] ?? 0);
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID requerido']);
                break;
            }
            // Check if category is used
            $check = $pdo->prepare("SELECT COUNT(*) FROM inventory_products WHERE category_id = ?");
            $check->execute([$id]);
            if ($check->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'message' => 'No se puede eliminar porque hay productos usando esta categoría.']);
                break;
            }
            $stmt = $pdo->prepare("DELETE FROM inventory_categories WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Categoría eliminada']);
            break;

        // ── Productos ───────────────────────────────────────
        case 'list_products':
            $sql = "SELECT p.*, c.name as category_name,
                    COALESCE(
                        p.product_image,
                        (SELECT sp.ruta_archivo FROM inventory_sku_photos sp 
                         JOIN inventory_skus sk ON sp.sku_id = sk.id 
                         WHERE sk.product_id = p.id ORDER BY sp.id ASC LIMIT 1)
                    ) as display_image,
                    (SELECT COUNT(*) FROM inventory_skus WHERE product_id = p.id AND status = 'disponible') as qty_disponible,
                    (SELECT COUNT(*) FROM inventory_skus WHERE product_id = p.id AND status = 'instalado') as qty_instalado,
                    (SELECT COUNT(*) FROM inventory_skus WHERE product_id = p.id AND status = 'malogrado') as qty_malogrado,
                    (SELECT COUNT(*) FROM inventory_skus WHERE product_id = p.id AND status = 'reparado') as qty_reparado,
                    (SELECT COUNT(*) FROM inventory_skus WHERE product_id = p.id) as real_total_quantity,
                    (SELECT COUNT(*) FROM inventory_products ch WHERE ch.parent_product_id = p.id) as children_count
                    FROM inventory_products p
                    LEFT JOIN inventory_categories c ON p.category_id = c.id
                    WHERE p.parent_product_id IS NULL
                    ORDER BY p.created_at DESC";
            $stmt = $pdo->query($sql);
            $products = $stmt->fetchAll();

            $stmtUserStock = $pdo->prepare("SELECT COALESCE(SUM(quantity), 0) FROM inventory_user_stock WHERE product_id = ?");
            $stmtInstalado = $pdo->prepare("SELECT COALESCE(SUM(cantidad), 0) FROM actas_materiales WHERE descripcion = ?");
            $stmtChildrenTotals = $pdo->prepare("SELECT COALESCE(SUM(total_quantity), 0) FROM inventory_products WHERE parent_product_id = ?");

            // Prepare searchable text for agrupado
            $stmtChildSearch = $pdo->prepare("SELECT GROUP_CONCAT(CONCAT_WS(' ', ch.name, COALESCE(ch.variant_attributes,'')) SEPARATOR ' ') FROM inventory_products ch WHERE ch.parent_product_id = ?");

            foreach ($products as &$p) {
                if ($p['product_type'] === 'agrupado') {
                    $stmtChildrenTotals->execute([$p['id']]);
                    $childTotal = (int)$stmtChildrenTotals->fetchColumn();
                    $p['total_quantity'] = $childTotal;
                    $p['qty_disponible'] = $childTotal;
                    $p['qty_instalado'] = 0;
                    $p['qty_malogrado'] = 0;
                    $p['qty_reparado'] = 0;
                    // Searchable text includes child names + attributes
                    $stmtChildSearch->execute([$p['id']]);
                    $p['searchable_children'] = $stmtChildSearch->fetchColumn() ?: '';
                } elseif ($p['is_bulk']) {
                    $stmtUserStock->execute([$p['id']]);
                    $qty_asignado = $stmtUserStock->fetchColumn();

                    $stmtInstalado->execute([$p['name']]);
                    $qty_instalado = $stmtInstalado->fetchColumn();

                    $qty_disponible = $p['total_quantity'];

                    $p['qty_instalado'] = $qty_instalado;
                    $p['qty_asignado'] = $qty_asignado; 
                    $p['qty_malogrado'] = 0; 
                    $p['qty_reparado'] = 0;
                    $p['qty_disponible'] = $qty_disponible;
                    $p['total_quantity'] = $qty_disponible + $qty_asignado + $qty_instalado;
                } else {
                    $p['total_quantity'] = $p['real_total_quantity'];
                }
            }
            echo json_encode(['success' => true, 'data' => $products]);
            break;

        case 'get_children':
            $parent_id = intval($_POST['product_id'] ?? 0);
            if (!$parent_id) {
                echo json_encode(['success' => false, 'message' => 'ID de producto requerido']);
                break;
            }
            // Get parent's custom_columns for column definitions
            $stmtParent = $pdo->prepare("SELECT custom_columns FROM inventory_products WHERE id = ?");
            $stmtParent->execute([$parent_id]);
            $parentCols = $stmtParent->fetchColumn() ?: '[]';

            $stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM inventory_products p LEFT JOIN inventory_categories c ON p.category_id = c.id WHERE p.parent_product_id = ? ORDER BY p.name ASC");
            $stmt->execute([$parent_id]);
            $children = $stmt->fetchAll();
            // Decode variant_attributes for each child
            foreach ($children as &$ch) {
                $ch['variant_attributes'] = json_decode($ch['variant_attributes'] ?? '{}', true) ?: new stdClass();
            }
            echo json_encode(['success' => true, 'data' => $children, 'columns' => json_decode($parentCols, true) ?: []]);
            break;

        case 'create_product':
            $name = trim($_POST['name'] ?? '');
            $category_id = $_POST['category_id'] ?? null;
            $quantity = intval($_POST['quantity'] ?? 0);
            $description = trim($_POST['description'] ?? '');
            $stock_minimo = intval($_POST['stock_minimo'] ?? 10);
            $stock_critico = intval($_POST['stock_critico'] ?? 3);
            $custom_columns = $_POST['custom_columns'] ?? '[]';

            $is_bulk = isset($_POST['is_bulk']) && $_POST['is_bulk'] == '1' ? 1 : 0;
            $unit_type = trim($_POST['unit_type'] ?? 'Unidades');
            $master_sku = trim($_POST['master_sku'] ?? '');
            $product_type = trim($_POST['product_type'] ?? 'normal');

            // Agrupado: variants come as JSON
            $variants = [];
            if ($product_type === 'agrupado') {
                $variants = json_decode($_POST['variants'] ?? '[]', true) ?: [];
                if (empty($variants)) {
                    echo json_encode(['success' => false, 'message' => 'Agrega al menos una variante']);
                    break;
                }
            } elseif (!$name || ($quantity < 1 && !$is_bulk)) {
                echo json_encode(['success' => false, 'message' => 'Nombre y cantidad son requeridos']);
                break;
            }

            if ($category_id === '' || $category_id === null) $category_id = null;

            $pdo->beginTransaction();

            if ($is_bulk) {
                if (empty($master_sku)) {
                    // Generar automáticamente si está vacío
                    $master_sku = 'BLK-' . generateRandomCode(5);
                } else {
                    // Validar unicidad
                    $check = $pdo->prepare("SELECT COUNT(*) FROM inventory_products WHERE master_sku = ?");
                    $check->execute([$master_sku]);
                    if ($check->fetchColumn() > 0) {
                        $pdo->rollBack();
                        echo json_encode(['success' => false, 'message' => 'El SKU maestro ya existe para otro producto a granel']);
                        exit;
                    }
                }
            } else {
                $master_sku = null;
            }

            $stmt = $pdo->prepare("INSERT INTO inventory_products (name, description, category_id, total_quantity, stock_minimo, stock_critico, custom_columns, is_bulk, unit_type, master_sku, requires_photos, product_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $requires_photos = isset($_POST['requires_photos']) && $_POST['requires_photos'] == '1' ? 1 : 0;
            $stmt->execute([$name, $description, $category_id, $quantity, $stock_minimo, $stock_critico, $custom_columns, $is_bulk, $unit_type, $master_sku, $requires_photos, $product_type]);
            $product_id = $pdo->lastInsertId();

            // Handle multiple product photo uploads
            if (!empty($_FILES['product_photos'])) {
                $uploadDir = '../uploads/product_images/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                $firstPhoto = null;

                foreach ($_FILES['product_photos']['tmp_name'] as $i => $tmpName) {
                    if ($_FILES['product_photos']['error'][$i] !== UPLOAD_ERR_OK) continue;
                    $ext = strtolower(pathinfo($_FILES['product_photos']['name'][$i], PATHINFO_EXTENSION));
                    if (!in_array($ext, $allowed)) continue;
                    $filename = 'prod_' . $product_id . '_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($tmpName, $uploadDir . $filename)) {
                        $ruta = 'uploads/product_images/' . $filename;
                        $pdo->prepare("INSERT INTO inventory_product_photos (product_id, ruta_archivo, uploaded_by) VALUES (?, ?, ?)")
                            ->execute([$product_id, $ruta, $_SESSION['user_id']]);
                        if ($firstPhoto === null) $firstPhoto = $ruta;
                    }
                }
                // Set first photo as product_image thumbnail
                if ($firstPhoto) {
                    $pdo->prepare("UPDATE inventory_products SET product_image = ? WHERE id = ?")->execute([$firstPhoto, $product_id]);
                }
            }

            if (!$is_bulk) {
                // Generar SKUs únicos
                $skus_generated = [];
                $attempts = 0;
                $max_attempts = $quantity * 10;

                while (count($skus_generated) < $quantity && $attempts < $max_attempts) {
                    $code = 'TRB-' . generateRandomCode(6);
                    $check = $pdo->prepare("SELECT COUNT(*) FROM inventory_skus WHERE sku_code = ?");
                    $check->execute([$code]);
                    if ($check->fetchColumn() == 0 && !in_array($code, $skus_generated)) {
                        $skus_generated[] = $code;
                    }
                    $attempts++;
                }

                // Insertar SKUs con custom_data (from preview or empty)
                $cols = json_decode($custom_columns, true) ?: [];
                $preview_data = json_decode($_POST['preview_custom_data'] ?? '[]', true) ?: [];

                $insert = $pdo->prepare("INSERT INTO inventory_skus (product_id, sku_code, status, custom_data) VALUES (?, ?, 'disponible', ?)");
                foreach ($skus_generated as $idx => $sku) {
                    // Use preview data if available for this index, otherwise empty
                    if (isset($preview_data[$idx]) && is_array($preview_data[$idx])) {
                        $customJson = json_encode((object)$preview_data[$idx]);
                    } else {
                        $emptyCustom = new stdClass();
                        foreach ($cols as $col) { $emptyCustom->{$col} = ''; }
                        $customJson = json_encode($emptyCustom);
                    }
                    $insert->execute([$product_id, $sku, $customJson]);
                }
            }

            // Handle agrupado: create child variants with dynamic attributes
            if ($product_type === 'agrupado') {
                // Store variant column definitions in parent's custom_columns
                $variantCols = $_POST['variant_columns'] ?? '[]';
                $pdo->prepare("UPDATE inventory_products SET custom_columns = ? WHERE id = ?")->execute([$variantCols, $product_id]);

                $stmtVariant = $pdo->prepare("INSERT INTO inventory_products (name, description, category_id, total_quantity, stock_minimo, stock_critico, is_bulk, unit_type, product_type, parent_product_id, variant_attributes) VALUES (?, ?, ?, ?, ?, ?, 1, 'Unidades', 'granel', ?, ?)");
                foreach ($variants as $v) {
                    $vName = trim($v['name'] ?? '');
                    $vQty = intval($v['quantity'] ?? 0);
                    if (!$vName || $vQty < 1) continue;
                    // Extract attributes (everything except name and quantity)
                    $attrs = $v['attributes'] ?? [];
                    $stmtVariant->execute([$vName, $description, $category_id, $vQty, $stock_minimo, $stock_critico, $product_id, json_encode($attrs, JSON_UNESCAPED_UNICODE)]);
                }
            }

            $pdo->commit();

            $msg = 'Producto creado';
            if ($product_type === 'agrupado') $msg = 'Producto agrupado creado con ' . count($variants) . ' variantes';
            elseif ($is_bulk) $msg = 'Producto a granel guardado';
            else $msg = "Producto creado con {$quantity} SKUs";

            echo json_encode([
                'success' => true,
                'message' => $msg,
                'product_id' => $product_id,
                'skus_count' => $is_bulk ? 0 : count($skus_generated ?? [])
            ]);
            break;

        case 'add_product_stock':
            $product_id = intval($_POST['product_id'] ?? 0);
            $quantity = intval($_POST['quantity'] ?? 0);
            
            if (!$product_id || $quantity < 1) {
                echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
                break;
            }
            
            $pdo->beginTransaction();
            
            // Check if it's bulk
            $stmt = $pdo->prepare("SELECT is_bulk, custom_columns FROM inventory_products WHERE id = ?");
            $stmt->execute([$product_id]);
            $prod = $stmt->fetch();
            
            if (!$prod) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
                break;
            }
            
            if ($prod['is_bulk']) {
                $stmt = $pdo->prepare("UPDATE inventory_products SET total_quantity = total_quantity + ? WHERE id = ?");
                $stmt->execute([$quantity, $product_id]);
                $pdo->commit();
                echo json_encode(['success' => true, 'message' => "Stock agregado ({$quantity})"]);
                break;
            }
            
            $skus_generated = [];
            $attempts = 0;
            $max_attempts = $quantity * 10;
            while (count($skus_generated) < $quantity && $attempts < $max_attempts) {
                $code = 'TRB-' . generateRandomCode(6);
                $check = $pdo->prepare("SELECT COUNT(*) FROM inventory_skus WHERE sku_code = ?");
                $check->execute([$code]);
                if ($check->fetchColumn() == 0 && !in_array($code, $skus_generated)) {
                    $skus_generated[] = $code;
                }
                $attempts++;
            }
            
            $cols = json_decode($prod['custom_columns'], true) ?: [];
            $preview_data = json_decode($_POST['preview_custom_data'] ?? '[]', true) ?: [];
            
            $insert = $pdo->prepare("INSERT INTO inventory_skus (product_id, sku_code, status, custom_data) VALUES (?, ?, 'disponible', ?)");
            foreach ($skus_generated as $idx => $sku) {
                if (isset($preview_data[$idx]) && is_array($preview_data[$idx])) {
                    $customJson = json_encode((object)$preview_data[$idx]);
                } else {
                    $emptyCustom = new stdClass();
                    foreach ($cols as $col) { $emptyCustom->{$col} = ''; }
                    $customJson = json_encode($emptyCustom);
                }
                $insert->execute([$product_id, $sku, $customJson]);
            }
            
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => "Agregados {$quantity} nuevos SKUs"]);
            break;

        case 'update_product':
            $product_id = intval($_POST['product_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $category_id = $_POST['category_id'] ?? null;
            $stock_minimo = intval($_POST['stock_minimo'] ?? 10);
            $stock_critico = intval($_POST['stock_critico'] ?? 3);
            $description = trim($_POST['description'] ?? '');
            $custom_columns = $_POST['custom_columns'] ?? '[]';
            
            if (!$product_id || empty($name)) {
                echo json_encode(['success' => false, 'message' => 'ID y nombre son requeridos']);
                break;
            }
            
            $requires_photos = isset($_POST['requires_photos']) && $_POST['requires_photos'] == '1' ? 1 : 0;
            
            $stmt = $pdo->prepare("UPDATE inventory_products SET name = ?, category_id = ?, stock_minimo = ?, stock_critico = ?, description = ?, custom_columns = ?, requires_photos = ? WHERE id = ?");
            $stmt->execute([$name, $category_id ?: null, $stock_minimo, $stock_critico, $description, $custom_columns, $requires_photos, $product_id]);

            // Handle multiple product photo uploads
            if (!empty($_FILES['product_photos'])) {
                $uploadDir = '../uploads/product_images/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

                foreach ($_FILES['product_photos']['tmp_name'] as $i => $tmpName) {
                    if ($_FILES['product_photos']['error'][$i] !== UPLOAD_ERR_OK) continue;
                    $ext = strtolower(pathinfo($_FILES['product_photos']['name'][$i], PATHINFO_EXTENSION));
                    if (!in_array($ext, $allowed)) continue;
                    $filename = 'prod_' . $product_id . '_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($tmpName, $uploadDir . $filename)) {
                        $ruta = 'uploads/product_images/' . $filename;
                        $pdo->prepare("INSERT INTO inventory_product_photos (product_id, ruta_archivo, uploaded_by) VALUES (?, ?, ?)")
                            ->execute([$product_id, $ruta, $_SESSION['user_id']]);
                    }
                }
            }

            // Handle photo deletions
            if (!empty($_POST['delete_photo_ids'])) {
                $ids = json_decode($_POST['delete_photo_ids'], true);
                if (is_array($ids)) {
                    foreach ($ids as $photoId) {
                        $photoId = intval($photoId);
                        $p = $pdo->prepare("SELECT ruta_archivo FROM inventory_product_photos WHERE id = ? AND product_id = ?");
                        $p->execute([$photoId, $product_id]);
                        $path = $p->fetchColumn();
                        if ($path && file_exists('../' . $path)) unlink('../' . $path);
                        $pdo->prepare("DELETE FROM inventory_product_photos WHERE id = ?")->execute([$photoId]);
                    }
                }
            }

            // Sync product_image thumbnail with first photo
            $firstPhoto = $pdo->prepare("SELECT ruta_archivo FROM inventory_product_photos WHERE product_id = ? ORDER BY id ASC LIMIT 1");
            $firstPhoto->execute([$product_id]);
            $thumb = $firstPhoto->fetchColumn();
            $pdo->prepare("UPDATE inventory_products SET product_image = ? WHERE id = ?")->execute([$thumb ?: null, $product_id]);

            echo json_encode(['success' => true, 'message' => 'Producto actualizado']);
            break;

        case 'delete_product':
            $id = intval($_POST['id'] ?? 0);
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID inválido']);
                break;
            }
            $stmt = $pdo->prepare("DELETE FROM inventory_products WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Producto eliminado']);
            break;
        case 'get_product_photos':
            $product_id = intval($_POST['product_id'] ?? 0);
            if (!$product_id) {
                echo json_encode(['success' => false, 'message' => 'ID inválido']);
                break;
            }
            $stmt = $pdo->prepare("SELECT id, ruta_archivo, created_at FROM inventory_product_photos WHERE product_id = ? ORDER BY id ASC");
            $stmt->execute([$product_id]);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        // ── SKUs ────────────────────────────────────────────
        case 'get_product_skus':
            $product_id = intval($_POST['product_id'] ?? $_GET['product_id'] ?? 0);
            $status_filter = $_POST['status'] ?? $_GET['status'] ?? '';

            // Revisar si el producto es a granel
            $stmtProd = $pdo->prepare("SELECT * FROM inventory_products WHERE id = ?");
            $stmtProd->execute([$product_id]);
            $prod = $stmtProd->fetch();

            if ($prod && $prod['is_bulk'] == 1) {
                echo json_encode(['success' => true, 'data' => [
                    [
                        'id' => $prod['id'],
                        'product_id' => $prod['id'],
                        'sku_code' => $prod['master_sku'],
                        'product_name' => $prod['name'],
                        'status' => 'disponible'
                    ]
                ]]);
                break;
            }

            $sql = "SELECT s.*, p.name as product_name, p.product_image,
                           COALESCE(
                               (SELECT sp.ruta_archivo FROM inventory_sku_photos sp WHERE sp.sku_id = s.id ORDER BY sp.id ASC LIMIT 1),
                               p.product_image
                           ) as sku_thumbnail
                    FROM inventory_skus s
                    JOIN inventory_products p ON s.product_id = p.id
                    WHERE s.product_id = ?";
            $params = [$product_id];

            if ($status_filter) {
                $sql .= " AND s.status = ?";
                $params[] = $status_filter;
            }

            $sql .= " ORDER BY s.id ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        case 'update_sku_status':
            $sku_id = intval($_POST['sku_id'] ?? 0);
            $status = $_POST['status'] ?? '';
            $valid = ['disponible', 'instalado', 'malogrado', 'reparado', 'en_transito'];

            if (!$sku_id || !in_array($status, $valid)) {
                echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
                break;
            }

            $stmt = $pdo->prepare("UPDATE inventory_skus SET status = ? WHERE id = ?");
            $stmt->execute([$status, $sku_id]);
            echo json_encode(['success' => true, 'message' => 'Estado actualizado']);
            break;

        case 'update_sku_custom':
            $sku_id = intval($_POST['sku_id'] ?? 0);
            $custom_data = $_POST['custom_data'] ?? '{}';

            if (!$sku_id) {
                echo json_encode(['success' => false, 'message' => 'ID inválido']);
                break;
            }

            $stmt = $pdo->prepare("UPDATE inventory_skus SET custom_data = ? WHERE id = ?");
            $stmt->execute([$custom_data, $sku_id]);
            echo json_encode(['success' => true, 'message' => 'Datos actualizados']);
            break;

        case 'update_sku_code':
            $sku_id = intval($_POST['sku_id'] ?? 0);
            $new_code = trim($_POST['sku_code'] ?? '');

            if (!$sku_id || !$new_code) {
                echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
                break;
            }

            // Check uniqueness
            $check = $pdo->prepare("SELECT COUNT(*) FROM inventory_skus WHERE sku_code = ? AND id != ?");
            $check->execute([$new_code, $sku_id]);
            if ($check->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'message' => 'Ese código SKU ya existe']);
                break;
            }

            $stmt = $pdo->prepare("UPDATE inventory_skus SET sku_code = ? WHERE id = ?");
            $stmt->execute([$new_code, $sku_id]);
            echo json_encode(['success' => true, 'message' => 'SKU actualizado']);
            break;

        case 'search_sku':
            $code = trim($_POST['code'] ?? $_GET['code'] ?? '');
            if (!$code) {
                echo json_encode(['success' => false, 'message' => 'Código requerido']);
                break;
            }

            $stmt = $pdo->prepare("SELECT s.*, p.name as product_name, p.product_image,
                                   COALESCE(
                                       (SELECT sp.ruta_archivo FROM inventory_sku_photos sp WHERE sp.sku_id = s.id ORDER BY sp.id ASC LIMIT 1),
                                       p.product_image
                                   ) as sku_thumbnail,
                                   c.name as category_name,
                                   u.name as assigned_user_name, p.description as product_description,
                                   p.stock_minimo, p.stock_critico, p.custom_columns, p.is_bulk
                                   FROM inventory_skus s
                                   JOIN inventory_products p ON s.product_id = p.id
                                   LEFT JOIN inventory_categories c ON p.category_id = c.id
                                   LEFT JOIN users u ON s.assigned_to = u.id
                                   WHERE s.sku_code = ? OR s.custom_data LIKE ? OR p.name LIKE ? OR u.name LIKE ? LIMIT 1");
            $stmt->execute([$code, "%$code%", "%$code%", "%$code%"]);
            $result = $stmt->fetch();

            if ($result) {
                echo json_encode(['success' => true, 'data' => $result]);
            } else {
                // Check if it's a bulk product's master_sku
                $stmtBulk = $pdo->prepare("SELECT p.id as product_id, p.master_sku as sku_code, p.name as product_name,
                                           c.name as category_name, p.description as product_description,
                                           p.stock_minimo, p.stock_critico, p.is_bulk, p.total_quantity as stock,
                                           'disponible' as status, p.unit_type
                                           FROM inventory_products p
                                           LEFT JOIN inventory_categories c ON p.category_id = c.id
                                           WHERE p.is_bulk = 1 AND (p.master_sku = ? OR p.name LIKE ?) LIMIT 1");
                $stmtBulk->execute([$code, "%$code%"]);
                $resultBulk = $stmtBulk->fetch();

                if ($resultBulk) {
                    // It's a bulk product. We structure it similarly so the frontend can handle it.
                    // We set is_bulk to 1, and we might not have 'id' (since there's no sku row).
                    // We use product_id as id but with a flag.
                    $resultBulk['id'] = 'bulk_' . $resultBulk['product_id'];
                    echo json_encode(['success' => true, 'data' => $resultBulk]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'SKU no encontrado']);
                }
            }
            break;

        // ── Stock Summary ───────────────────────────────────
        case 'get_stock_summary':
            // Solo contar SKUs que tengan un producto padre válido
            $total = $pdo->query("SELECT COUNT(*) FROM inventory_skus s JOIN inventory_products p ON s.product_id = p.id")->fetchColumn();
            $disponible = $pdo->query("SELECT COUNT(*) FROM inventory_skus s JOIN inventory_products p ON s.product_id = p.id WHERE s.status = 'disponible'")->fetchColumn();
            $instalado = $pdo->query("SELECT COUNT(*) FROM inventory_skus s JOIN inventory_products p ON s.product_id = p.id WHERE s.status = 'instalado'")->fetchColumn();
            $malogrado = $pdo->query("SELECT COUNT(*) FROM inventory_skus s JOIN inventory_products p ON s.product_id = p.id WHERE s.status = 'malogrado'")->fetchColumn();
            $reparado = $pdo->query("SELECT COUNT(*) FROM inventory_skus s JOIN inventory_products p ON s.product_id = p.id WHERE s.status = 'reparado'")->fetchColumn();

            // Sumar también productos bulk
            $bulk_total = $pdo->query("SELECT COALESCE(SUM(total_quantity), 0) FROM inventory_products WHERE is_bulk = 1")->fetchColumn();

            $low_stock = $pdo->query("SELECT COUNT(*) FROM (
                SELECT s.product_id, COUNT(*) as cnt FROM inventory_skus s JOIN inventory_products p ON s.product_id = p.id WHERE s.status = 'disponible' GROUP BY s.product_id HAVING cnt <= (SELECT stock_minimo FROM inventory_products WHERE id = s.product_id)
            ) as low")->fetchColumn();

            echo json_encode(['success' => true, 'data' => [
                'total' => intval($total) + intval($bulk_total),
                'disponible' => intval($disponible) + intval($bulk_total),
                'instalado' => intval($instalado),
                'malogrado' => intval($malogrado),
                'reparado' => intval($reparado),
                'low_stock' => intval($low_stock)
            ]]);
            break;

        // ── All SKUs (for stock control tab) ────────────────
        case 'list_all_skus':
            $status_filter = $_POST['status'] ?? $_GET['status'] ?? '';
            $product_filter = $_POST['product_id'] ?? $_GET['product_id'] ?? '';
            $search = trim($_POST['search'] ?? $_GET['search'] ?? '');

            $sql = "SELECT s.*, s.created_at as sku_created_at, p.name as product_name, p.product_image,
                           COALESCE(
                               (SELECT sp.ruta_archivo FROM inventory_sku_photos sp WHERE sp.sku_id = s.id ORDER BY sp.id ASC LIMIT 1),
                               p.product_image
                           ) as sku_thumbnail,
                           c.name as category_name, u.name as assigned_user_name,
                           (SELECT a.cliente_nombre FROM actas a JOIN actas_equipos ae ON a.id = ae.acta_id WHERE ae.serie_mac = s.sku_code ORDER BY a.fecha_creacion DESC LIMIT 1) as acta_cliente,
                           (SELECT MAX(created_at) FROM inventory_entries e WHERE e.sku_id = s.id) as last_history_date
                    FROM inventory_skus s
                    JOIN inventory_products p ON s.product_id = p.id
                    LEFT JOIN inventory_categories c ON p.category_id = c.id
                    LEFT JOIN users u ON s.assigned_to = u.id
                    WHERE 1=1";
            $params = [];

            if ($status_filter) {
                $sql .= " AND s.status = ?";
                $params[] = $status_filter;
            }
            if ($product_filter) {
                $sql .= " AND s.product_id = ?";
                $params[] = intval($product_filter);
            }
            if ($search) {
                $sql .= " AND (s.sku_code LIKE ? OR p.name LIKE ? OR u.name LIKE ?)";
                $params[] = "%{$search}%";
                $params[] = "%{$search}%";
                $params[] = "%{$search}%";
            }

            $sql .= " ORDER BY s.created_at DESC LIMIT 500";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $skus = $stmt->fetchAll();

            // Append bulk items
            $sqlBulk = "SELECT p.id as product_id, p.master_sku as sku_code, p.created_at as sku_created_at, 
                               p.name as product_name, p.product_image, c.name as category_name,
                               NULL as assigned_user_name,
                               NULL as acta_cliente,
                               NULL as last_history_date,
                               'disponible' as status,
                               'ninguno' as historia,
                               p.total_quantity as stock_disponible,
                               p.unit_type,
                               '{}' as custom_data,
                               1 as is_bulk,
                               (SELECT GROUP_CONCAT(CONCAT('<i class=\"ph ph-user\"></i> ', u.name, ' (', ius.quantity, ' ', IFNULL(p.unit_type, ''), ')') SEPARATOR '<br>') 
                                FROM inventory_user_stock ius JOIN users u ON ius.user_id = u.id WHERE ius.product_id = p.id AND ius.quantity > 0) as bulk_assignments
                        FROM inventory_products p
                        LEFT JOIN inventory_categories c ON p.category_id = c.id
                        WHERE p.is_bulk = 1";
            
            $paramsBulk = [];
            if ($product_filter) {
                $sqlBulk .= " AND p.id = ?";
                $paramsBulk[] = intval($product_filter);
            }
            if ($search) {
                $sqlBulk .= " AND (p.master_sku LIKE ? OR p.name LIKE ?)";
                $paramsBulk[] = "%{$search}%";
                $paramsBulk[] = "%{$search}%";
            }
            
            if (!$status_filter || $status_filter === 'disponible') {
                $stmtBulk = $pdo->prepare($sqlBulk);
                $stmtBulk->execute($paramsBulk);
                $bulkItems = $stmtBulk->fetchAll();
                foreach ($bulkItems as $b) {
                    $b['id'] = 'bulk_' . $b['product_id'];
                    $b['product_description'] = ''; 
                    $skus[] = $b;
                }
            }

            usort($skus, function($a, $b) {
                return strtotime($b['sku_created_at']) - strtotime($a['sku_created_at']);
            });

            echo json_encode(['success' => true, 'data' => $skus]);
            break;

        // ── Users list (for assignment) ─────────────────────
        case 'list_users':
            $stmt = $pdo->query("SELECT id, name, email, role FROM users WHERE role != 'Cliente' ORDER BY name ASC");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        // ── Assign SKU to user ──────────────────────────────
        case 'assign_sku':
            $sku_id_raw = $_POST['sku_id'] ?? '';
            $user_id = intval($_POST['user_id'] ?? 0);
            $is_epp = isset($_POST['is_epp']) && $_POST['is_epp'] == '1' ? 1 : 0;
            if (!$sku_id_raw || !$user_id) {
                echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
                break;
            }

            // Get user name
            $uname = $pdo->prepare("SELECT name FROM users WHERE id = ?");
            $uname->execute([$user_id]);
            $name = $uname->fetchColumn();

            if (strpos($sku_id_raw, 'bulk_') === 0) {
                // Bulk assignment
                $product_id = intval(str_replace('bulk_', '', $sku_id_raw));
                $quantity = floatval($_POST['quantity'] ?? 0);
                
                if ($quantity <= 0) {
                    echo json_encode(['success' => false, 'message' => 'Cantidad inválida para producto a granel']);
                    break;
                }

                $pdo->beginTransaction();
                
                // Deduct from warehouse
                $stmt = $pdo->prepare("UPDATE inventory_products SET total_quantity = total_quantity - ? WHERE id = ? AND total_quantity >= ?");
                $stmt->execute([$quantity, $product_id, $quantity]);
                
                if ($stmt->rowCount() === 0) {
                    $pdo->rollBack();
                    echo json_encode(['success' => false, 'message' => 'Stock insuficiente en almacén para la cantidad solicitada']);
                    break;
                }

                // Add to user stock with is_epp
                $stmtStock = $pdo->prepare("INSERT INTO inventory_user_stock (user_id, product_id, quantity, is_epp) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + ?, is_epp = ?");
                $stmtStock->execute([$user_id, $product_id, $quantity, $is_epp, $quantity, $is_epp]);

                $pdo->commit();
                echo json_encode(['success' => true, 'message' => "Asignado {$quantity} a {$name}" . ($is_epp ? ' (como EPP)' : ''), 'user_name' => $name]);
                break;
            }

            $sku_id = intval($sku_id_raw);
            $stmt = $pdo->prepare("UPDATE inventory_skus SET assigned_to = ?, is_epp = ? WHERE id = ?");
            $stmt->execute([$user_id, $is_epp, $sku_id]);
            echo json_encode(['success' => true, 'message' => "Asignado a {$name}" . ($is_epp ? ' (como EPP)' : ''), 'user_name' => $name]);
            break;

        // ── Assign Grouped (multiple variants at once) ──
        case 'assign_grouped':
            $user_id = intval($_POST['user_id'] ?? 0);
            $is_epp = isset($_POST['is_epp']) && $_POST['is_epp'] == '1' ? 1 : 0;
            $assignments = json_decode($_POST['assignments'] ?? '[]', true) ?: [];

            if (!$user_id || empty($assignments)) {
                echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
                break;
            }

            $uname = $pdo->prepare("SELECT name FROM users WHERE id = ?");
            $uname->execute([$user_id]);
            $userName = $uname->fetchColumn();

            $pdo->beginTransaction();
            $totalAssigned = 0;
            $errors = [];

            foreach ($assignments as $a) {
                $variantId = intval($a['variant_id'] ?? 0);
                $qty = floatval($a['quantity'] ?? 0);
                if ($variantId <= 0 || $qty <= 0) continue;

                // Deduct from warehouse
                $stmt = $pdo->prepare("UPDATE inventory_products SET total_quantity = total_quantity - ? WHERE id = ? AND total_quantity >= ?");
                $stmt->execute([$qty, $variantId, $qty]);

                if ($stmt->rowCount() === 0) {
                    $vName = $pdo->prepare("SELECT name FROM inventory_products WHERE id = ?");
                    $vName->execute([$variantId]);
                    $errors[] = $vName->fetchColumn() . ': stock insuficiente';
                    continue;
                }

                // Add to user stock
                $stmtStock = $pdo->prepare("INSERT INTO inventory_user_stock (user_id, product_id, quantity, is_epp) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + ?, is_epp = ?");
                $stmtStock->execute([$user_id, $variantId, $qty, $is_epp, $qty, $is_epp]);
                $totalAssigned++;
            }

            $pdo->commit();

            if ($totalAssigned > 0) {
                $msg = "{$totalAssigned} variante(s) asignada(s) a {$userName}" . ($is_epp ? ' (como EPP)' : '');
                if (!empty($errors)) $msg .= '. Errores: ' . implode(', ', $errors);
                echo json_encode(['success' => true, 'message' => $msg]);
            } else {
                echo json_encode(['success' => false, 'message' => 'No se pudo asignar: ' . implode(', ', $errors)]);
            }
            break;

        // ── Unassign SKU ────────────────────────────────────
        case 'unassign_sku':
            $sku_id = intval($_POST['sku_id'] ?? 0);
            if (!$sku_id) {
                echo json_encode(['success' => false, 'message' => 'ID inválido']);
                break;
            }
            $stmt = $pdo->prepare("UPDATE inventory_skus SET assigned_to = NULL WHERE id = ?");
            $stmt->execute([$sku_id]);
            echo json_encode(['success' => true, 'message' => 'Asignación removida']);
            break;

        // ── Create entry (with photos) ──────────────────────
        case 'create_entry':
            $sku_id = intval($_POST['sku_id'] ?? 0);
            $user_id = intval($_SESSION['user_id'] ?? 0);
            $tipo = $_POST['tipo'] ?? 'entrada';
            $notas = trim($_POST['notas'] ?? '');
            $valid_tipos = ['entrada', 'salida', 'devolucion', 'reparacion'];

            if (!$sku_id || !in_array($tipo, $valid_tipos)) {
                echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
                break;
            }

            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO inventory_entries (sku_id, user_id, tipo, notas) VALUES (?, ?, ?, ?)");
            $stmt->execute([$sku_id, $user_id, $tipo, $notas]);
            $entry_id = $pdo->lastInsertId();

            // Movement logic: auto-update status and historia
            switch ($tipo) {
                case 'entrada':
                    $pdo->prepare("UPDATE inventory_skus SET status = 'disponible' WHERE id = ?")->execute([$sku_id]);
                    break;
                case 'salida':
                    $pdo->prepare("UPDATE inventory_skus SET status = 'en_transito', historia = 'en_transito' WHERE id = ?")->execute([$sku_id]);
                    break;
                case 'devolucion':
                    $pdo->prepare("UPDATE inventory_skus SET status = 'disponible', historia = 'devuelto' WHERE id = ?")->execute([$sku_id]);
                    break;
                case 'reparacion':
                    $pdo->prepare("UPDATE inventory_skus SET historia = 'malogrado' WHERE id = ?")->execute([$sku_id]);
                    break;
            }

            // Handle photo uploads
            $uploadDir = '../uploads/inventario/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            if (!empty($_FILES['photos'])) {
                $photos = $_FILES['photos'];
                $count = is_array($photos['name']) ? count($photos['name']) : 0;
                for ($i = 0; $i < $count; $i++) {
                    if ($photos['error'][$i] === UPLOAD_ERR_OK) {
                        $ext = pathinfo($photos['name'][$i], PATHINFO_EXTENSION);
                        $filename = 'inv_' . $entry_id . '_' . uniqid() . '.' . $ext;
                        if (move_uploaded_file($photos['tmp_name'][$i], $uploadDir . $filename)) {
                            $ruta = 'uploads/inventario/' . $filename;
                            $pdo->prepare("INSERT INTO inventory_entry_photos (entry_id, ruta_archivo) VALUES (?, ?)")
                                ->execute([$entry_id, $ruta]);
                        }
                    }
                }
            }

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Movimiento registrado', 'entry_id' => $entry_id]);
            break;

        // ── Update Historia ──────────────────────────────────
        case 'update_historia':
            $sku_id = intval($_POST['sku_id'] ?? 0);
            $historia = $_POST['historia'] ?? 'ninguno';
            $valid_hist = ['ninguno', 'devuelto', 'malogrado', 'antiguo', 'en_transito'];
            if (!$sku_id || !in_array($historia, $valid_hist)) {
                echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
                break;
            }
            $stmt = $pdo->prepare("UPDATE inventory_skus SET historia = ? WHERE id = ?");
            $stmt->execute([$historia, $sku_id]);
            echo json_encode(['success' => true, 'message' => 'Historia actualizada']);
            break;

        // ── Get SKU entries history ─────────────────────────
        case 'get_sku_entries':
            $sku_id = intval($_POST['sku_id'] ?? $_GET['sku_id'] ?? 0);
            if (!$sku_id) {
                echo json_encode(['success' => false, 'message' => 'ID inválido']);
                break;
            }
            $stmt = $pdo->prepare("SELECT e.*, u.name as user_name,
                                   (SELECT GROUP_CONCAT(ruta_archivo) FROM inventory_entry_photos WHERE entry_id = e.id) as photos
                                   FROM inventory_entries e
                                   JOIN users u ON e.user_id = u.id
                                   WHERE e.sku_id = ?
                                   ORDER BY e.created_at DESC");
            $stmt->execute([$sku_id]);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

/**
 * Genera un código aleatorio alfanumérico
 */
function generateRandomCode($length = 6) {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Sin I,O,0,1 para evitar confusión
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $code;
}
