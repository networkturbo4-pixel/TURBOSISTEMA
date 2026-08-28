<?php
require_once __DIR__ . '/../config/db.php';

if (!function_exists('logScannerHistory')) {
    function logScannerHistory($pdo, $code, $product_id = null) {
        if (empty($_SESSION['user_id'])) return;
        try {
            $stmt = $pdo->prepare("INSERT INTO inventory_scans (user_id, product_id, sku_code) VALUES (?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $product_id, $code]);
        } catch (Exception $e) {
            // ignore
        }
    }
}

requireLogin();

header('Content-Type: application/json');

if (!function_exists('generateRandomCode')) {
    function generateRandomCode($length = 6) {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Sin I,O,0,1 para evitar confusión
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $code;
    }
}

// ── Auto-migration: create log tables if not exist ──
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS inventory_stock_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        sku_codes TEXT,
        user_id INT NOT NULL,
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS inventory_assignment_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sku_id INT,
        product_id INT,
        sku_code VARCHAR(50),
        product_name VARCHAR(255),
        assigned_to INT NOT NULL,
        assigned_to_name VARCHAR(255),
        assigned_by INT,
        assigned_by_name VARCHAR(255),
        quantity DECIMAL(10,2) DEFAULT 1,
        is_epp TINYINT(1) DEFAULT 0,
        action ENUM('assign','unassign') DEFAULT 'assign',
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    // Add notes column to assignment_log if it doesn't exist yet
    try {
        $pdo->exec("ALTER TABLE inventory_assignment_log ADD COLUMN IF NOT EXISTS notes TEXT AFTER action");
    } catch(Exception $e) {}
} catch(Exception $e) {}

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
                    (SELECT COUNT(*) FROM inventory_skus WHERE product_id = p.id AND status = 'disponible' AND is_deleted = 0) as qty_disponible,
                    (SELECT COUNT(*) FROM inventory_skus WHERE product_id = p.id AND status = 'instalado' AND is_deleted = 0) as qty_instalado,
                    (SELECT COUNT(*) FROM inventory_skus WHERE product_id = p.id AND status = 'malogrado' AND is_deleted = 0) as qty_malogrado,
                    (SELECT COUNT(*) FROM inventory_skus WHERE product_id = p.id AND status = 'reparado' AND is_deleted = 0) as qty_reparado,
                    (SELECT COUNT(*) FROM inventory_skus WHERE product_id = p.id AND status = 'observacion' AND is_deleted = 0) as qty_observacion,
                    (SELECT COUNT(*) FROM inventory_skus WHERE product_id = p.id AND is_deleted = 0) as real_total_quantity,
                    (SELECT COUNT(*) FROM inventory_products ch WHERE ch.parent_product_id = p.id AND ch.is_deleted = 0) as children_count
                    FROM inventory_products p
                    LEFT JOIN inventory_categories c ON p.category_id = c.id
                    WHERE (p.parent_product_id IS NULL OR p.parent_product_id = 0) AND (p.is_deleted = 0 OR p.is_deleted IS NULL)
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
                    $stmtChildrenStats = $pdo->prepare("
                        SELECT 
                            COALESCE(SUM(total_quantity), 0) as db_total,
                            COALESCE(SUM((SELECT SUM(ius.quantity) FROM inventory_user_stock ius WHERE ius.product_id = ch.id)), 0) as asignado
                        FROM inventory_products ch 
                        WHERE ch.parent_product_id = ? AND (ch.is_deleted = 0 OR ch.is_deleted IS NULL)
                    ");
                    $stmtChildrenStats->execute([$p['id']]);
                    $stats = $stmtChildrenStats->fetch(PDO::FETCH_ASSOC);

                    $db_total = floatval($stats['db_total']);
                    $asignado = floatval($stats['asignado']);

                    $p['qty_disponible'] = $db_total;
                    $p['qty_asignado'] = $asignado;
                    $p['qty_instalado'] = $asignado; 
                    $p['total_quantity'] = $db_total + $asignado;

                    $p['qty_malogrado'] = 0;
                    $p['qty_reparado'] = 0;
                    $p['qty_observacion'] = 0;
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
                    $p['qty_observacion'] = 0;
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

            $stmt = $pdo->prepare("
                SELECT p.*,
                       c.name as category_name,
                       COALESCE((SELECT SUM(ius.quantity) FROM inventory_user_stock ius WHERE ius.product_id = p.id), 0) as qty_asignado,
                       COALESCE((SELECT SUM(ius.quantity) FROM inventory_user_stock ius
                                  JOIN users u ON ius.user_id = u.id
                                  WHERE ius.product_id = p.id AND ius.quantity > 0), 0) as qty_instalado,
                       0 as qty_malogrado,
                       0 as qty_observacion
                FROM inventory_products p
                LEFT JOIN inventory_categories c ON p.category_id = c.id
                WHERE p.parent_product_id = ? AND p.is_deleted = 0
                ORDER BY p.name ASC");
            $stmt->execute([$parent_id]);
            $children = $stmt->fetchAll();
            // Decode variant_attributes and compute qty_disponible for each child
            foreach ($children as &$ch) {
                $ch['variant_attributes'] = json_decode($ch['variant_attributes'] ?? '{}', true) ?: new stdClass();
                $db_total = floatval($ch['total_quantity']);
                $qty_asignado = floatval($ch['qty_asignado']);
                $ch['qty_disponible'] = $db_total;
                $ch['total_quantity'] = $db_total + $qty_asignado;
                $ch['qty_instalado']  = $qty_asignado; // Use qty_asignado as installed
                $ch['qty_malogrado']  = floatval($ch['qty_malogrado']);
                $ch['qty_observacion']  = floatval($ch['qty_observacion']);
            }
            unset($ch);
            echo json_encode(['success' => true, 'data' => $children, 'columns' => json_decode($parentCols, true) ?: []]);
            break;

        case 'create_product':
            $name = trim($_POST['name'] ?? '');
            $category_id = $_POST['category_id'] ?? null;
            $quantity = intval($_POST['quantity'] ?? 0);
            $description = trim($_POST['description'] ?? '');
            $stock_minimo = intval($_POST['stock_minimo'] ?? 10);
            $stock_critico = intval($_POST['stock_critico'] ?? 3);
            $costo_producto = floatval($_POST['costo_producto'] ?? 0);
            $custom_columns = $_POST['custom_columns'] ?? '[]';

            $is_bulk = isset($_POST['is_bulk']) && $_POST['is_bulk'] == '1' ? 1 : 0;
            $unit_type = trim($_POST['unit_type'] ?? 'Unidades');
            $master_sku = trim($_POST['master_sku'] ?? '');
            $product_type = trim($_POST['product_type'] ?? 'normal');

            // Agrupado and Bundle: variants come as JSON
            $variants = [];
            if ($product_type === 'agrupado' || $product_type === 'bundle') {
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

            $stmt = $pdo->prepare("INSERT INTO inventory_products (name, description, category_id, total_quantity, stock_minimo, stock_critico, costo_producto, custom_columns, is_bulk, unit_type, master_sku, requires_photos, product_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $requires_photos = isset($_POST['requires_photos']) && $_POST['requires_photos'] == '1' ? 1 : 0;
            $stmt->execute([$name, $description, $category_id, $quantity, $stock_minimo, $stock_critico, $costo_producto, $custom_columns, $is_bulk, $unit_type, $master_sku, $requires_photos, $product_type]);
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
                $preview_skus = json_decode($_POST['preview_sku_codes'] ?? '[]', true) ?: [];
                $skus_generated = [];

                if (!empty($preview_skus) && is_array($preview_skus)) {
                    // Usar los SKUs provistos en el formulario / preview
                    foreach ($preview_skus as $code) {
                        $code = strtoupper(trim($code));
                        if ($code === '') {
                            $code = 'TRB-' . generateRandomCode(6);
                        }
                        // Validar unicidad en BD
                        $check = $pdo->prepare("SELECT COUNT(*) FROM inventory_skus WHERE sku_code = ? AND is_deleted = 0");
                        $check->execute([$code]);
                        if ($check->fetchColumn() > 0) {
                            $pdo->rollBack();
                            echo json_encode(['success' => false, 'message' => "El código SKU '{$code}' ya existe en el sistema."]);
                            exit;
                        }
                        $skus_generated[] = $code;
                    }
                } else {
                    // Generar SKUs únicos aleatorios
                    $attempts = 0;
                    $max_attempts = max(10, $quantity * 15);

                    while (count($skus_generated) < $quantity && $attempts < $max_attempts) {
                        $code = 'TRB-' . generateRandomCode(6);
                        $check = $pdo->prepare("SELECT COUNT(*) FROM inventory_skus WHERE sku_code = ?");
                        $check->execute([$code]);
                        if ($check->fetchColumn() == 0 && !in_array($code, $skus_generated)) {
                            $skus_generated[] = $code;
                        }
                        $attempts++;
                    }
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
                        foreach ($cols as $col) { $colName = is_array($col) ? ($col['name'] ?? '') : $col; if ($colName) $emptyCustom->{$colName} = ''; }
                        $customJson = json_encode($emptyCustom);
                    }
                    $insert->execute([$product_id, $sku, $customJson]);
                }

                // Registrar en el log de stock inicial
                try {
                    $logStmt = $pdo->prepare("INSERT INTO inventory_stock_log (product_id, quantity, sku_codes, user_id, notes) VALUES (?, ?, ?, ?, 'Creación inicial del producto')");
                    $logStmt->execute([$product_id, count($skus_generated), json_encode($skus_generated), intval($_SESSION['user_id'] ?? 0)]);
                } catch(Exception $e) {}
            }

            // Handle agrupado and bundle: create child variants with dynamic attributes
            if ($product_type === 'agrupado' || $product_type === 'bundle') {
                // Store variant column definitions in parent's custom_columns
                $variantCols = $_POST['variant_columns'] ?? '[]';
                $pdo->prepare("UPDATE inventory_products SET custom_columns = ? WHERE id = ?")->execute([$variantCols, $product_id]);

                $stmtVariant = $pdo->prepare("INSERT INTO inventory_products (name, description, category_id, total_quantity, stock_minimo, stock_critico, costo_producto, is_bulk, unit_type, product_type, parent_product_id, variant_attributes) VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?)");
                foreach ($variants as $idx => $v) {
                    $vName = trim($v['name'] ?? '');
                    $vQty = intval($v['quantity'] ?? 0);
                    if (!$vName || $vQty < 1) continue;
                    // Extract attributes (everything except name and quantity)
                    $attrs = $v['attributes'] ?? [];
                    $vCosto = isset($v['costo']) && $v['costo'] !== null ? floatval($v['costo']) : $costo_producto;
                    $vUnit = $v['unit_type'] ?? 'Unidades';
                    $stmtVariant->execute([$vName, $description, $category_id, $vQty, $stock_minimo, $stock_critico, $vCosto, $vUnit, 'granel', $product_id, json_encode($attrs, JSON_UNESCAPED_UNICODE)]);
                    $child_id = $pdo->lastInsertId();

                    // If bundle and has photo
                    if ($product_type === 'bundle' && isset($_FILES['variant_photo_' . $idx])) {
                        $f = $_FILES['variant_photo_' . $idx];
                        if ($f['error'] === UPLOAD_ERR_OK) {
                            $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
                            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                                $uploadDir = '../uploads/product_images/';
                                $filename = 'prod_' . $child_id . '_var_' . uniqid() . '.' . $ext;
                                if (move_uploaded_file($f['tmp_name'], $uploadDir . $filename)) {
                                    $ruta = 'uploads/product_images/' . $filename;
                                    $pdo->prepare("UPDATE inventory_products SET product_image = ? WHERE id = ?")->execute([$ruta, $child_id]);
                                    $pdo->prepare("INSERT INTO inventory_product_photos (product_id, ruta_archivo, uploaded_by) VALUES (?, ?, ?)")
                                        ->execute([$child_id, $ruta, $_SESSION['user_id']]);
                                }
                            }
                        }
                    }
                }
            }

            $pdo->commit();

            $msg = 'Producto creado';
            if ($product_type === 'agrupado') $msg = 'Producto agrupado creado con ' . count($variants) . ' variantes';
            elseif ($product_type === 'bundle') $msg = 'Producto bundle creado con ' . count($variants) . ' variables';
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
                // Log stock addition (bulk)
                try {
                    $logStmt = $pdo->prepare("INSERT INTO inventory_stock_log (product_id, quantity, sku_codes, user_id, notes) VALUES (?, ?, ?, ?, ?)");
                    $logStmt->execute([$product_id, $quantity, json_encode([]), intval($_SESSION['user_id'] ?? 0), '']);
                } catch(Exception $e) {}
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
                    foreach ($cols as $col) { 
                        $colName = is_array($col) ? ($col['name'] ?? '') : $col;
                        if ($colName) $emptyCustom->{$colName} = ''; 
                    }
                    $customJson = json_encode($emptyCustom);
                }
                $insert->execute([$product_id, $sku, $customJson]);
            }
            
            $pdo->commit();
            // Log stock addition
            try {
                $logStmt = $pdo->prepare("INSERT INTO inventory_stock_log (product_id, quantity, sku_codes, user_id, notes) VALUES (?, ?, ?, ?, ?)");
                $logStmt->execute([$product_id, $quantity, json_encode($skus_generated ?? []), intval($_SESSION['user_id'] ?? 0), '']);
            } catch(Exception $e) {}
            echo json_encode(['success' => true, 'message' => "Agregados {$quantity} nuevos SKUs"]);
            break;

        case 'add_multiple_stock':
            $updates = json_decode($_POST['updates'] ?? '[]', true);
            if (!is_array($updates) || empty($updates)) {
                echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
                break;
            }
            $pdo->beginTransaction();
            $total_added = 0;
            $stmt = $pdo->prepare("UPDATE inventory_products SET total_quantity = total_quantity + ? WHERE id = ?");
            foreach ($updates as $upd) {
                $q = intval($upd['qty'] ?? 0);
                $id = intval($upd['id'] ?? 0);
                if ($q > 0 && $id > 0) {
                    $stmt->execute([$q, $id]);
                    $total_added += $q;
                    try {
                        $logStmt = $pdo->prepare("INSERT INTO inventory_stock_log (product_id, quantity, sku_codes, user_id, notes) VALUES (?, ?, '[]', ?, 'Ingreso de lote agrupado')");
                        $logStmt->execute([$id, $q, intval($_SESSION['user_id'] ?? 0)]);
                    } catch(Exception $e) {}
                }
            }
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => "Agregados {$total_added} productos en total a las variantes"]);
            break;

        case 'update_product':
            $product_id = intval($_POST['product_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $category_id = $_POST['category_id'] ?? null;
            $stock_minimo = intval($_POST['stock_minimo'] ?? 10);
            $stock_critico = intval($_POST['stock_critico'] ?? 3);
            $costo_producto = floatval($_POST['costo_producto'] ?? 0);
            $description = trim($_POST['description'] ?? '');
            $custom_columns = $_POST['custom_columns'] ?? '[]';
            
            if (!$product_id || empty($name)) {
                echo json_encode(['success' => false, 'message' => 'ID y nombre son requeridos']);
                break;
            }
            
            $requires_photos = isset($_POST['requires_photos']) && $_POST['requires_photos'] == '1' ? 1 : 0;
            
            $stmt = $pdo->prepare("UPDATE inventory_products SET name = ?, category_id = ?, stock_minimo = ?, stock_critico = ?, costo_producto = ?, description = ?, custom_columns = ?, requires_photos = ? WHERE id = ?");
            $stmt->execute([$name, $category_id ?: null, $stock_minimo, $stock_critico, $costo_producto, $description, $custom_columns, $requires_photos, $product_id]);

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
            $stmt = $pdo->prepare("UPDATE inventory_products SET is_deleted = 1 WHERE id = ?");
            $stmt->execute([$id]);
            $stmtSku = $pdo->prepare("UPDATE inventory_skus SET is_deleted = 1 WHERE product_id = ?");
            $stmtSku->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Producto enviado a la papelera']);
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

        case 'get_product_columns':
            $product_id = intval($_POST['product_id'] ?? 0);
            if (!$product_id) {
                echo json_encode(['success' => false, 'message' => 'ID inválido']);
                break;
            }
            $stmt = $pdo->prepare("SELECT custom_columns FROM inventory_products WHERE id = ?");
            $stmt->execute([$product_id]);
            $colData = $stmt->fetchColumn();
            $cols = $colData ? json_decode($colData, true) : [];
            echo json_encode(['success' => true, 'data' => $cols]);
            break;

        // ── SKUs ────────────────────────────────────────────
        case 'get_product_skus':
            $product_id = intval($_POST['product_id'] ?? $_GET['product_id'] ?? 0);
            $status_filter = $_POST['status'] ?? $_GET['status'] ?? '';
            $print_status = $_POST['print_status'] ?? $_GET['print_status'] ?? '';

            // Obtener información del producto
            $stmtProd = $pdo->prepare("SELECT * FROM inventory_products WHERE id = ?");
            $stmtProd->execute([$product_id]);
            $prod = $stmtProd->fetch();

            if (!$prod) {
                echo json_encode(['success' => false, 'data' => []]);
                break;
            }

            // Buscar SKUs serializados (para este producto o sus variantes hijas)
            $sql = "SELECT s.*, p.name as product_name, p.product_image,
                           COALESCE(
                               (SELECT sp.ruta_archivo FROM inventory_sku_photos sp WHERE sp.sku_id = s.id ORDER BY sp.id ASC LIMIT 1),
                               p.product_image
                           ) as sku_thumbnail
                    FROM inventory_skus s
                    JOIN inventory_products p ON s.product_id = p.id
                    WHERE (s.product_id = ? OR s.product_id IN (SELECT id FROM inventory_products WHERE parent_product_id = ?))
                      AND s.is_deleted = 0";
            $params = [$product_id, $product_id];

            if ($status_filter) {
                $sql .= " AND s.status = ?";
                $params[] = $status_filter;
            }

            if ($print_status === '0') {
                $sql .= " AND s.is_printed = 0";
            } elseif ($print_status === '1') {
                $sql .= " AND s.is_printed = 1";
            }

            $start_date = trim($_POST['start_date'] ?? $_GET['start_date'] ?? '');
            $end_date = trim($_POST['end_date'] ?? $_GET['end_date'] ?? '');

            if ($start_date && $end_date) {
                $sql .= " AND DATE(s.created_at) BETWEEN ? AND ?";
                $params[] = $start_date;
                $params[] = $end_date;
            } elseif ($start_date) {
                $sql .= " AND DATE(s.created_at) = ?";
                $params[] = $start_date;
            } elseif ($end_date) {
                $sql .= " AND DATE(s.created_at) <= ?";
                $params[] = $end_date;
            }

            $sql .= " ORDER BY s.id ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $skus = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Si se encontraron SKUs individuales, retornarlos
            if (!empty($skus)) {
                echo json_encode(['success' => true, 'data' => $skus]);
                break;
            }

            // Si NO hay SKUs serializados (producto agrupado o granel), generar entradas de etiquetas desde las variantes / master_sku
            $stmtVariants = $pdo->prepare("
                SELECT id, name, master_sku, variant_brand, variant_size, product_type, total_quantity, created_at, is_bulk
                FROM inventory_products
                WHERE (id = ? OR parent_product_id = ?) AND is_deleted = 0
                ORDER BY (parent_product_id IS NULL OR parent_product_id = 0) DESC, id ASC
            ");
            $stmtVariants->execute([$product_id, $product_id]);
            $variants = $stmtVariants->fetchAll(PDO::FETCH_ASSOC);

            $virtualSkus = [];
            $hasChildVariants = count($variants) > 1 && $prod['product_type'] === 'agrupado';

            foreach ($variants as $v) {
                // Si el padre agrupado tiene hijos específicos, mostramos los hijos para imprimir sus etiquetas exactas
                if ($hasChildVariants && $v['id'] == $product_id) {
                    continue;
                }

                $vName = $v['name'];
                $attrParts = array_filter([$v['variant_brand'], $v['variant_size']]);
                if (!empty($attrParts)) {
                    $vName .= ' (' . implode(' - ', $attrParts) . ')';
                }

                $skuCode = !empty($v['master_sku']) ? $v['master_sku'] : ('BLK-' . $v['id']);

                $virtualSkus[] = [
                    'id' => $v['id'],
                    'product_id' => $v['id'],
                    'sku_code' => $skuCode,
                    'product_name' => $vName,
                    'status' => 'disponible',
                    'created_at' => $v['created_at'],
                    'is_printed' => 0,
                    'is_virtual' => true
                ];
            }

            // Si no hay variantes, agregar el producto principal
            if (empty($virtualSkus) && $prod) {
                $skuCode = !empty($prod['master_sku']) ? $prod['master_sku'] : ('BLK-' . $prod['id']);
                $virtualSkus[] = [
                    'id' => $prod['id'],
                    'product_id' => $prod['id'],
                    'sku_code' => $skuCode,
                    'product_name' => $prod['name'],
                    'status' => 'disponible',
                    'created_at' => $prod['created_at'],
                    'is_printed' => 0,
                    'is_virtual' => true
                ];
            }

            echo json_encode(['success' => true, 'data' => $virtualSkus]);
            break;

        case 'search_skus_for_labels':
            $q = trim($_POST['query'] ?? $_GET['query'] ?? '');
            if (!$q) {
                echo json_encode(['success' => true, 'data' => []]);
                break;
            }
            $cleanQ = str_replace([' ', '-'], '', $q);
            $searchTerm = "%{$q}%";
            $cleanSearchTerm = "%{$cleanQ}%";
            $stmt = $pdo->prepare("
                SELECT s.id, s.sku_code, s.product_id, s.status, s.created_at, s.is_printed,
                       COALESCE(p.name, 'Producto General') as product_name, p.product_image, p.product_type,
                       COALESCE(c.name, 'General') as category_name
                FROM inventory_skus s
                LEFT JOIN inventory_products p ON s.product_id = p.id
                LEFT JOIN inventory_categories c ON p.category_id = c.id
                WHERE s.is_deleted = 0 
                  AND (s.sku_code LIKE ? OR REPLACE(REPLACE(s.sku_code, ' ', ''), '-', '') LIKE ? OR p.name LIKE ? OR s.custom_data LIKE ?)
                ORDER BY (s.sku_code = ?) DESC, (s.sku_code LIKE CONCAT(?, '%')) DESC, s.id DESC
                LIMIT 15
            ");
            $stmt->execute([$searchTerm, $cleanSearchTerm, $searchTerm, $searchTerm, $q, $q]);
            $skus = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // También buscar en productos agrupados / granel por master_sku o nombre
            if (count($skus) < 10) {
                $stmtProds = $pdo->prepare("
                    SELECT p.id, COALESCE(p.master_sku, CONCAT('BLK-', p.id)) as sku_code, 
                           p.id as product_id, 'disponible' as status, p.created_at, 0 as is_printed,
                           p.name as product_name, p.product_image, p.product_type,
                           COALESCE(c.name, 'General') as category_name, p.variant_brand, p.variant_size
                    FROM inventory_products p
                    LEFT JOIN inventory_categories c ON p.category_id = c.id
                    WHERE p.is_deleted = 0
                      AND (p.master_sku LIKE ? OR p.name LIKE ?)
                    LIMIT 10
                ");
                $stmtProds->execute([$searchTerm, $searchTerm]);
                $prodResults = $stmtProds->fetchAll(PDO::FETCH_ASSOC);
                foreach ($prodResults as $pr) {
                    $vName = $pr['product_name'];
                    $attrParts = array_filter([$pr['variant_brand'], $pr['variant_size']]);
                    if (!empty($attrParts)) {
                        $vName .= ' (' . implode(' - ', $attrParts) . ')';
                    }
                    $pr['product_name'] = $vName;
                    $skus[] = $pr;
                }
            }

            echo json_encode(['success' => true, 'data' => $skus]);
            break;

        case 'get_specific_skus_for_labels':
            $ids_raw = $_POST['sku_ids'] ?? '[]';
            $ids = json_decode($ids_raw, true);
            if (!is_array($ids) || empty($ids)) {
                echo json_encode(['success' => false, 'message' => 'No se enviaron SKUs.']);
                break;
            }
            $cleanIds = array_map('intval', $ids);
            $placeholders = implode(',', array_fill(0, count($cleanIds), '?'));
            $stmt = $pdo->prepare("
                SELECT s.*, p.name as product_name, p.product_image
                FROM inventory_skus s
                JOIN inventory_products p ON s.product_id = p.id
                WHERE s.id IN ($placeholders) AND s.is_deleted = 0
                ORDER BY s.id ASC
            ");
            $stmt->execute($cleanIds);
            $foundSkus = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Si faltan IDs, buscar en inventory_products para productos agrupados/granel
            $foundIds = array_column($foundSkus, 'id');
            $missingIds = array_diff($cleanIds, $foundIds);

            if (!empty($missingIds)) {
                $pPlaceholders = implode(',', array_fill(0, count($missingIds), '?'));
                $stmtP = $pdo->prepare("
                    SELECT p.id, p.id as product_id, COALESCE(p.master_sku, CONCAT('BLK-', p.id)) as sku_code,
                           p.name as product_name, p.product_image, 'disponible' as status, p.created_at, 0 as is_printed,
                           p.variant_brand, p.variant_size
                    FROM inventory_products p
                    WHERE p.id IN ($pPlaceholders) AND p.is_deleted = 0
                ");
                $stmtP->execute(array_values($missingIds));
                $pRows = $stmtP->fetchAll(PDO::FETCH_ASSOC);
                foreach ($pRows as $pr) {
                    $vName = $pr['product_name'];
                    $attrParts = array_filter([$pr['variant_brand'], $pr['variant_size']]);
                    if (!empty($attrParts)) {
                        $vName .= ' (' . implode(' - ', $attrParts) . ')';
                    }
                    $pr['product_name'] = $vName;
                    $foundSkus[] = $pr;
                }
            }

            echo json_encode(['success' => true, 'data' => $foundSkus]);
            break;

        case 'mark_skus_printed':
            $sku_ids_raw = $_POST['sku_ids'] ?? '';
            $sku_ids = json_decode($sku_ids_raw, true);
            if (!is_array($sku_ids) || empty($sku_ids)) {
                echo json_encode(['success' => false, 'message' => 'No hay SKUs seleccionados.']);
                break;
            }
            
            // Generate placeholders for IN clause
            $placeholders = implode(',', array_fill(0, count($sku_ids), '?'));
            
            $sql = "UPDATE inventory_skus SET is_printed = 1 WHERE id IN ($placeholders)";
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute($sku_ids)) {
                echo json_encode(['success' => true, 'message' => 'Etiquetas marcadas como impresas.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al marcar como impresas.']);
            }
            break;

        case 'update_sku_status':
            $sku_id = intval($_POST['sku_id'] ?? 0);
            $status = $_POST['status'] ?? '';
            $valid = ['disponible', 'instalado', 'malogrado', 'reparado', 'en_transito', 'observacion'];

            if (!$sku_id || !in_array($status, $valid)) {
                echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
                break;
            }

            // Obtain user_id for logging if we know who it's assigned to or who is changing it
            // the user changing it is $_SESSION['user_id']. We need the assigned_to for entry.
            $stmt = $pdo->prepare("SELECT assigned_to FROM inventory_skus WHERE id = ?");
            $stmt->execute([$sku_id]);
            $skuRow = $stmt->fetch();
            $assigned_to = $skuRow ? $skuRow['assigned_to'] : $_SESSION['user_id'];

            $stmt = $pdo->prepare("UPDATE inventory_skus SET status = ?, historia = ? WHERE id = ?");
            $stmt->execute([$status, $status, $sku_id]);

            $stmtEntry = $pdo->prepare("INSERT INTO inventory_entries (sku_id, user_id, tipo, notas) VALUES (?, ?, ?, ?)");
            $stmtEntry->execute([$sku_id, $assigned_to, $status, 'Cambio de estado desde selector']);

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

        case 'update_bulk_custom':
            $product_id = intval($_POST['product_id'] ?? 0);
            $custom_data = $_POST['custom_data'] ?? '{}';

            if (!$product_id) {
                echo json_encode(['success' => false, 'message' => 'ID inválido']);
                break;
            }

            $stmt = $pdo->prepare("UPDATE inventory_products SET bulk_custom_data = ? WHERE id = ?");
            $stmt->execute([$custom_data, $product_id]);
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

            $stmt = $pdo->prepare("SELECT s.*, p.name as product_name, p.product_type, p.product_image,
                                   COALESCE(
                                       (SELECT sp.ruta_archivo FROM inventory_sku_photos sp WHERE sp.sku_id = s.id ORDER BY sp.id ASC LIMIT 1),
                                       p.product_image
                                   ) as sku_thumbnail,
                                   c.name as category_name,
                                   u.name as assigned_user_name, p.description as product_description,
                                   p.stock_minimo, p.stock_critico, p.custom_columns, p.is_bulk, p.total_quantity as stock
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
                // ── Step 1: Check if it's an agrupado parent product ──
                // (agrupado parents have product_type='agrupado' and no parent_product_id)
                // We check this FIRST because agrupado children also have is_bulk=1
                // and would otherwise be found by the bulk query below
                $stmtAgrupado = $pdo->prepare("
                    SELECT p.id as product_id, p.id as id,
                           COALESCE(p.master_sku, CONCAT('BLK-', p.id)) as sku_code,
                           p.name as product_name,
                           c.name as category_name, p.description as product_description,
                           p.stock_minimo, p.stock_critico, p.is_bulk,
                           COALESCE((SELECT SUM(ch.total_quantity) FROM inventory_products ch WHERE ch.parent_product_id = p.id), 0) as stock,
                           'disponible' as status, p.unit_type, p.product_type, p.custom_columns
                    FROM inventory_products p
                    LEFT JOIN inventory_categories c ON p.category_id = c.id
                    WHERE p.product_type = 'agrupado'
                      AND (p.parent_product_id IS NULL OR p.parent_product_id = 0)
                      AND (p.master_sku = ? OR p.name LIKE ?)
                    LIMIT 1");
                $stmtAgrupado->execute([$code, "%$code%"]);
                $resultAgrupado = $stmtAgrupado->fetch();

                if ($resultAgrupado) {
                    // Agrupado parent found — expose product_id explicitly for get_children
                    $resultAgrupado['id'] = 'bulk_' . $resultAgrupado['product_id'];
                    echo json_encode(['success' => true, 'data' => $resultAgrupado]);
                } else {
                    // ── Step 2: Check if it's a regular bulk product ──
                    // Exclude children of agrupado (parent_product_id IS NOT NULL)
                    // and exclude agrupado parents (already handled above)
                    $stmtBulk = $pdo->prepare("
                        SELECT p.id as product_id,
                               COALESCE(p.master_sku, CONCAT('BLK-', p.id)) as sku_code,
                               p.name as product_name,
                               c.name as category_name, p.description as product_description,
                               p.stock_minimo, p.stock_critico, p.is_bulk, p.total_quantity as stock,
                               'disponible' as status, p.unit_type, p.product_type, p.custom_columns
                        FROM inventory_products p
                        LEFT JOIN inventory_categories c ON p.category_id = c.id
                        WHERE p.is_bulk = 1
                          AND p.product_type != 'agrupado'
                          AND (p.parent_product_id IS NULL OR p.parent_product_id = 0)
                          AND (p.master_sku = ? OR p.name LIKE ?)
                        LIMIT 1");
                    $stmtBulk->execute([$code, "%$code%"]);
                    $resultBulk = $stmtBulk->fetch();

                    if ($resultBulk) {
                        // Regular bulk product
                        $resultBulk['id'] = 'bulk_' . $resultBulk['product_id'];
                        echo json_encode(['success' => true, 'data' => $resultBulk]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'SKU no encontrado']);
                    }
                }
            }
            break;

        // ── Stock Summary ───────────────────────────────────
        case 'get_stock_summary':
            // Solo contar SKUs que tengan un producto padre válido y no estén eliminados
            $total = $pdo->query("SELECT COUNT(*) FROM inventory_skus s JOIN inventory_products p ON s.product_id = p.id WHERE s.is_deleted = 0 AND p.is_deleted = 0")->fetchColumn();
            $disponible = $pdo->query("SELECT COUNT(*) FROM inventory_skus s JOIN inventory_products p ON s.product_id = p.id WHERE s.status = 'disponible' AND s.is_deleted = 0 AND p.is_deleted = 0")->fetchColumn();
            $instalado = $pdo->query("SELECT COUNT(*) FROM inventory_skus s JOIN inventory_products p ON s.product_id = p.id WHERE s.status = 'instalado' AND s.is_deleted = 0 AND p.is_deleted = 0")->fetchColumn();
            $malogrado = $pdo->query("SELECT COUNT(*) FROM inventory_skus s JOIN inventory_products p ON s.product_id = p.id WHERE s.status = 'malogrado' AND s.is_deleted = 0 AND p.is_deleted = 0")->fetchColumn();
            $reparado = $pdo->query("SELECT COUNT(*) FROM inventory_skus s JOIN inventory_products p ON s.product_id = p.id WHERE s.status = 'reparado' AND s.is_deleted = 0 AND p.is_deleted = 0")->fetchColumn();

            $bulk_total = $pdo->query("SELECT COALESCE(SUM(total_quantity), 0) FROM inventory_products WHERE is_bulk = 1 AND is_deleted = 0")->fetchColumn();

            $low_stock = $pdo->query("SELECT COUNT(*) FROM (
                SELECT s.product_id, COUNT(*) as cnt FROM inventory_skus s JOIN inventory_products p ON s.product_id = p.id WHERE s.status = 'disponible' AND s.is_deleted = 0 AND p.is_deleted = 0 GROUP BY s.product_id HAVING cnt <= (SELECT stock_minimo FROM inventory_products WHERE id = s.product_id)
            ) as low")->fetchColumn();

            $productos_registrados = $pdo->query("SELECT COUNT(*) FROM inventory_products WHERE is_deleted = 0")->fetchColumn();

            echo json_encode(['success' => true, 'data' => [
                'total' => intval($total) + intval($bulk_total),
                'disponible' => intval($disponible) + intval($bulk_total),
                'instalado' => intval($instalado),
                'malogrado' => intval($malogrado),
                'reparado' => intval($reparado),
                'low_stock' => intval($low_stock),
                'productos_registrados' => intval($productos_registrados)
            ]]);
            break;

        // ── All SKUs (for stock control tab) ────────────────
        case 'list_all_skus':
            $status_filter = $_POST['status'] ?? $_GET['status'] ?? '';
            $product_filter = $_POST['product_id'] ?? $_GET['product_id'] ?? '';
            $search = trim($_POST['search'] ?? $_GET['search'] ?? '');

            $sql = "SELECT s.*, s.created_at as sku_created_at, p.name as product_name, p.product_type, p.product_image,
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
                    WHERE s.is_deleted = 0";
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
            $sqlBulk = "SELECT p.id as product_id, COALESCE(p.master_sku, CONCAT('BLK-', p.id)) as sku_code, p.created_at as sku_created_at, 
                               p.name as product_name, p.product_image, c.name as category_name,
                               NULL as assigned_user_name,
                               NULL as acta_cliente,
                               NULL as last_history_date,
                               'disponible' as status,
                               'ninguno' as historia,
                               CASE WHEN p.product_type = 'agrupado'
                                    THEN COALESCE((SELECT SUM(ch.total_quantity) FROM inventory_products ch WHERE ch.parent_product_id = p.id), 0)
                                    ELSE p.total_quantity END as stock_disponible,
                               p.unit_type,
                               COALESCE(p.bulk_custom_data, '{}') as custom_data,
                               1 as is_bulk,
                               p.product_type,
                               p.custom_columns,
                               (SELECT GROUP_CONCAT(CONCAT('<i class=\"ph ph-user\"></i> ', u.name, ' (', ius.quantity, ' ', IFNULL(p.unit_type, ''), ')') SEPARATOR '<br>') 
                                FROM inventory_user_stock ius JOIN users u ON ius.user_id = u.id WHERE ius.product_id = p.id AND ius.quantity > 0) as bulk_assignments
                        FROM inventory_products p
                        LEFT JOIN inventory_categories c ON p.category_id = c.id
                        WHERE (p.is_bulk = 1 OR p.product_type = 'agrupado')
                          AND (p.parent_product_id IS NULL OR p.parent_product_id = 0)
                          AND (p.is_deleted = 0 OR p.is_deleted IS NULL)";
            
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
                // Log bulk assignment
                try {
                    $pName = $pdo->prepare("SELECT name FROM inventory_products WHERE id = ?");
                    $pName->execute([$product_id]);
                    $prodName = $pName->fetchColumn() ?: '';
                    $byId = intval($_SESSION['user_id'] ?? 0);
                    $byName = '';
                    if ($byId) { $bn = $pdo->prepare("SELECT name FROM users WHERE id = ?"); $bn->execute([$byId]); $byName = $bn->fetchColumn() ?: ''; }
                    $logStmt = $pdo->prepare("INSERT INTO inventory_assignment_log (sku_id, product_id, sku_code, product_name, assigned_to, assigned_to_name, assigned_by, assigned_by_name, quantity, is_epp, action) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
                    $logStmt->execute([null, $product_id, 'GRANEL', $prodName, $user_id, $name, $byId, $byName, $quantity, $is_epp, 'assign']);
                } catch(Exception $e) {}
                echo json_encode(['success' => true, 'message' => "Asignado {$quantity} a {$name}" . ($is_epp ? ' (como EPP)' : ''), 'user_name' => $name]);
                break;
            }

            $sku_id = intval($sku_id_raw);
            $stmt = $pdo->prepare("UPDATE inventory_skus SET assigned_to = ?, is_epp = ? WHERE id = ?");
            $stmt->execute([$user_id, $is_epp, $sku_id]);
            // Log assignment
            try {
                $skuInfo = $pdo->prepare("SELECT s.sku_code, p.name as product_name, s.product_id FROM inventory_skus s JOIN inventory_products p ON s.product_id = p.id WHERE s.id = ?");
                $skuInfo->execute([$sku_id]);
                $si = $skuInfo->fetch();
                $logStmt = $pdo->prepare("INSERT INTO inventory_assignment_log (sku_id, product_id, sku_code, product_name, assigned_to, assigned_to_name, assigned_by, assigned_by_name, quantity, is_epp, action) VALUES (?,?,?,?,?,?,?,?,1,?,?)");
                $byId = intval($_SESSION['user_id'] ?? 0);
                $byName = '';
                if ($byId) { $bn = $pdo->prepare("SELECT name FROM users WHERE id = ?"); $bn->execute([$byId]); $byName = $bn->fetchColumn() ?: ''; }
                $logStmt->execute([$sku_id, $si['product_id'] ?? 0, $si['sku_code'] ?? '', $si['product_name'] ?? '', $user_id, $name, $byId, $byName, $is_epp, 'assign']);
            } catch(Exception $e) {}
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
                // Log grouped assignment
                try {
                    $vNameLog = $pdo->prepare("SELECT name FROM inventory_products WHERE id = ?");
                    $vNameLog->execute([$variantId]);
                    $variantName = $vNameLog->fetchColumn() ?: '';
                    $byId = intval($_SESSION['user_id'] ?? 0);
                    $byName = '';
                    if ($byId) { $bn = $pdo->prepare("SELECT name FROM users WHERE id = ?"); $bn->execute([$byId]); $byName = $bn->fetchColumn() ?: ''; }
                    $logStmt = $pdo->prepare("INSERT INTO inventory_assignment_log (sku_id, product_id, sku_code, product_name, assigned_to, assigned_to_name, assigned_by, assigned_by_name, quantity, is_epp, action) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
                    $logStmt->execute([null, $variantId, 'GRANEL', $variantName, $user_id, $userName, $byId, $byName, $qty, $is_epp, 'assign']);
                } catch(Exception $e) {}
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
            // Get SKU info before unassigning for logging
            $prevInfo = $pdo->prepare("SELECT s.sku_code, s.assigned_to, s.is_epp, s.product_id, p.name as product_name, u.name as assigned_to_name FROM inventory_skus s JOIN inventory_products p ON s.product_id = p.id LEFT JOIN users u ON s.assigned_to = u.id WHERE s.id = ?");
            $prevInfo->execute([$sku_id]);
            $prev = $prevInfo->fetch();
            $stmt = $pdo->prepare("UPDATE inventory_skus SET assigned_to = NULL WHERE id = ?");
            $stmt->execute([$sku_id]);
            // Log unassignment
            try {
                if ($prev) {
                    $byId = intval($_SESSION['user_id'] ?? 0);
                    $byName = '';
                    if ($byId) { $bn = $pdo->prepare("SELECT name FROM users WHERE id = ?"); $bn->execute([$byId]); $byName = $bn->fetchColumn() ?: ''; }
                    $logStmt = $pdo->prepare("INSERT INTO inventory_assignment_log (sku_id, product_id, sku_code, product_name, assigned_to, assigned_to_name, assigned_by, assigned_by_name, quantity, is_epp, action) VALUES (?,?,?,?,?,?,?,?,1,?,?)");
                    $logStmt->execute([$sku_id, $prev['product_id'], $prev['sku_code'] ?? '', $prev['product_name'] ?? '', $prev['assigned_to'] ?? 0, $prev['assigned_to_name'] ?? '', $byId, $byName, $prev['is_epp'] ?? 0, 'unassign']);
                }
            } catch(Exception $e) {}
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
            $valid_hist = ['ninguno', 'devuelto', 'malogrado', 'antiguo', 'en_transito', 'observacion'];
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
            $sku_id_raw = $_POST['sku_id'] ?? $_GET['sku_id'] ?? '';
            
            if (strpos($sku_id_raw, 'bulk_') === 0) {
                $product_id = intval(str_replace('bulk_', '', $sku_id_raw));
                if (!$product_id) {
                    echo json_encode(['success' => false, 'message' => 'ID inválido']);
                    break;
                }
                $stmt = $pdo->prepare("
                    SELECT 
                        id,
                        IF(action = 'unassign', 'devuelto', 'asignado') as tipo,
                        IF(action = 'unassign', assigned_to_name, assigned_by_name) as user_name,
                        created_at,
                        IF(action = 'unassign',
                           CONCAT('Devolvió ', quantity, ' unidades de ', product_name, ' a Turbo'),
                           CONCAT('Asignó ', quantity, ' unidades de ', product_name, ' a ', assigned_to_name)
                        ) as notas,
                        '' as photos
                    FROM inventory_assignment_log
                    WHERE product_id = ? OR product_id IN (SELECT id FROM inventory_products WHERE parent_product_id = ?)
                    ORDER BY created_at DESC
                ");
                $stmt->execute([$product_id, $product_id]);
                echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
                break;
            }
            
            $sku_id = intval($sku_id_raw);
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
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'update_entry':
            $entry_id = intval($_POST['entry_id'] ?? 0);
            $tipo = $_POST['tipo'] ?? 'entrada';
            $notas = trim($_POST['notas'] ?? '');
            $created_at = trim($_POST['created_at'] ?? '');

            if (!$entry_id) {
                echo json_encode(['success' => false, 'message' => 'ID inválido']);
                break;
            }

            if ($created_at) {
                $stmt = $pdo->prepare("UPDATE inventory_entries SET tipo = ?, notas = ?, created_at = ? WHERE id = ?");
                $stmt->execute([$tipo, $notas, $created_at, $entry_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE inventory_entries SET tipo = ?, notas = ? WHERE id = ?");
                $stmt->execute([$tipo, $notas, $entry_id]);
            }
            echo json_encode(['success' => true, 'message' => 'Movimiento actualizado']);
            break;

        case 'delete_entry':
            $entry_id = intval($_POST['entry_id'] ?? 0);
            if (!$entry_id) {
                echo json_encode(['success' => false, 'message' => 'ID inválido']);
                break;
            }
            // Delete photos from filesystem and DB
            $photos = $pdo->prepare("SELECT ruta_archivo FROM inventory_entry_photos WHERE entry_id = ?");
            $photos->execute([$entry_id]);
            foreach ($photos->fetchAll() as $p) {
                if (file_exists('../' . $p['ruta_archivo'])) {
                    unlink('../' . $p['ruta_archivo']);
                }
            }
            $pdo->prepare("DELETE FROM inventory_entry_photos WHERE entry_id = ?")->execute([$entry_id]);
            $pdo->prepare("DELETE FROM inventory_entries WHERE id = ?")->execute([$entry_id]);
            echo json_encode(['success' => true, 'message' => 'Movimiento eliminado']);
            break;
        // ── Bulk Actions ────────────────────────────────────
        case 'bulk_update_sku_status':
            $skus_json = $_POST['skus'] ?? '[]';
            $status = $_POST['status'] ?? '';
            $valid = ['disponible', 'instalado', 'malogrado', 'reparado', 'en_transito', 'observacion'];
            
            $skus = json_decode($skus_json, true);
            if (!is_array($skus) || empty($skus) || !in_array($status, $valid)) {
                echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
                break;
            }
            
            $placeholders = implode(',', array_fill(0, count($skus), '?'));
            $params = array_merge([$status], $skus);
            $stmt = $pdo->prepare("UPDATE inventory_skus SET status = ? WHERE id IN ($placeholders)");
            $stmt->execute($params);
            
            echo json_encode(['success' => true, 'message' => 'Estados actualizados']);
            break;

        case 'bulk_change_sku_status':
            $skus_json = $_POST['skus'] ?? '[]';
            $skus = json_decode($skus_json, true);
            $status = trim($_POST['status'] ?? '');
            
            if (!is_array($skus) || empty($skus) || empty($status)) {
                echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
                break;
            }
            
            // Extract IDs from 'bulk_xxx' vs normal '123'
            $normal_ids = [];
            foreach ($skus as $id) {
                if (strpos($id, 'bulk_') !== 0) {
                    $normal_ids[] = intval($id);
                }
            }
            
            if (count($normal_ids) > 0) {
                $placeholders = implode(',', array_fill(0, count($normal_ids), '?'));
                $params = array_merge([$status], $normal_ids);
                $stmt = $pdo->prepare("UPDATE inventory_skus SET status = ? WHERE id IN ($placeholders)");
                $stmt->execute($params);
            }
            
            echo json_encode(['success' => true, 'message' => count($skus) . ' SKUs actualizados']);
            break;

        case 'bulk_assign_skus':
            $skus_json = $_POST['skus'] ?? '[]';
            $user_id = intval($_POST['user_id'] ?? 0);
            $is_epp = !empty($_POST['is_epp']) ? 1 : 0;
            $notes = trim($_POST['notes'] ?? 'Asignación masiva');
            
            $skus = json_decode($skus_json, true);
            if (!is_array($skus) || empty($skus) || !$user_id) {
                echo json_encode(['success' => false, 'message' => 'Selecciona al menos un elemento y un técnico.']);
                break;
            }
            
            $stmtU = $pdo->prepare("SELECT name FROM users WHERE id = ?");
            $stmtU->execute([$user_id]);
            $userName = $stmtU->fetchColumn();
            if (!$userName) {
                echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
                break;
            }
            
            $assignedById = $_SESSION['user_id'] ?? 0;
            $assignedByName = $_SESSION['user_name'] ?? 'Admin';
            $count = 0;
            
            foreach ($skus as $idRaw) {
                if (strpos($idRaw, 'bulk_') === 0) {
                    $prodId = intval(str_replace('bulk_', '', $idRaw));
                    $stmtP = $pdo->prepare("SELECT name, quantity FROM inventory_products WHERE id = ?");
                    $stmtP->execute([$prodId]);
                    $p = $stmtP->fetch(PDO::FETCH_ASSOC);
                    if ($p && $p['quantity'] > 0) {
                        $pdo->prepare("INSERT INTO inventory_user_stock (user_id, product_id, quantity) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE quantity = quantity + 1")->execute([$user_id, $prodId]);
                        $pdo->prepare("UPDATE inventory_products SET quantity = GREATEST(0, quantity - 1) WHERE id = ?")->execute([$prodId]);
                        try {
                            $log = $pdo->prepare("INSERT INTO inventory_assignment_log (sku_id, product_id, sku_code, product_name, assigned_to, assigned_to_name, assigned_by, assigned_by_name, quantity, is_epp, action, notes) VALUES (?, ?, 'GRANEL', ?, ?, ?, ?, ?, 1, ?, 'assign', ?)");
                            $log->execute([null, $prodId, $p['name'], $user_id, $userName, $assignedById, $assignedByName, $is_epp, $notes]);
                        } catch(Exception $e) {}
                        $count++;
                    }
                } else {
                    $skuId = intval($idRaw);
                    $stmtSku = $pdo->prepare("SELECT s.*, p.name as product_name FROM inventory_skus s JOIN inventory_products p ON s.product_id = p.id WHERE s.id = ?");
                    $stmtSku->execute([$skuId]);
                    $s = $stmtSku->fetch(PDO::FETCH_ASSOC);
                    if ($s) {
                        $pdo->prepare("UPDATE inventory_skus SET assigned_to = ?, is_epp = ? WHERE id = ?")->execute([$user_id, $is_epp, $skuId]);
                        try {
                            $log = $pdo->prepare("INSERT INTO inventory_assignment_log (sku_id, product_id, sku_code, product_name, assigned_to, assigned_to_name, assigned_by, assigned_by_name, quantity, is_epp, action, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, 'assign', ?)");
                            $log->execute([$s['id'], $s['product_id'], $s['sku_code'], $s['product_name'], $user_id, $userName, $assignedById, $assignedByName, $is_epp, $notes]);
                        } catch(Exception $e) {}
                        $count++;
                    }
                }
            }
            
            echo json_encode(['success' => true, 'message' => "Se asignaron {$count} elemento(s) a {$userName}."]);
            break;

        case 'bulk_unassign_skus':
            $skus_json = $_POST['skus'] ?? '[]';
            $skus = json_decode($skus_json, true);
            if (!is_array($skus) || empty($skus)) {
                echo json_encode(['success' => false, 'message' => 'No hay elementos seleccionados']);
                break;
            }
            
            $assignedById = $_SESSION['user_id'] ?? 0;
            $assignedByName = $_SESSION['user_name'] ?? 'Admin';
            $count = 0;
            
            foreach ($skus as $idRaw) {
                if (strpos($idRaw, 'bulk_') === 0) {
                    continue;
                }
                $skuId = intval($idRaw);
                $stmtSku = $pdo->prepare("SELECT s.*, p.name as product_name, u.name as prev_user FROM inventory_skus s JOIN inventory_products p ON s.product_id = p.id LEFT JOIN users u ON s.assigned_to = u.id WHERE s.id = ?");
                $stmtSku->execute([$skuId]);
                $s = $stmtSku->fetch(PDO::FETCH_ASSOC);
                if ($s && $s['assigned_to']) {
                    $pdo->prepare("UPDATE inventory_skus SET assigned_to = NULL, is_epp = 0 WHERE id = ?")->execute([$skuId]);
                    try {
                        $log = $pdo->prepare("INSERT INTO inventory_assignment_log (sku_id, product_id, sku_code, product_name, assigned_to, assigned_to_name, assigned_by, assigned_by_name, quantity, is_epp, action, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 0, 'unassign', 'Desasignación masiva')");
                        $log->execute([$s['id'], $s['product_id'], $s['sku_code'], $s['product_name'], $s['assigned_to'], $s['prev_user'], $assignedById, $assignedByName]);
                    } catch(Exception $e) {}
                    $count++;
                }
            }
            
            echo json_encode(['success' => true, 'message' => "Se desasignaron {$count} SKU(s) y regresaron al almacén central."]);
            break;

        case 'bulk_delete_skus':
            $skus_json = $_POST['skus'] ?? '[]';
            $skus = json_decode($skus_json, true);
            
            if (!is_array($skus) || empty($skus)) {
                echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
                break;
            }
            
            $placeholders = implode(',', array_fill(0, count($skus), '?'));
            
            // Validar que todos los SKUs sean disponibles
            $stmtDisp = $pdo->prepare("SELECT COUNT(*) FROM inventory_skus WHERE id IN ($placeholders) AND status != 'disponible'");
            $stmtDisp->execute($skus);
            if ($stmtDisp->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'message' => 'Solo se pueden eliminar SKUs con estado disponible.']);
                break;
            }
            
            $stmt = $pdo->prepare("UPDATE inventory_skus SET is_deleted = 1 WHERE id IN ($placeholders)");
            $stmt->execute($skus);
            
            echo json_encode(['success' => true, 'message' => 'SKUs enviados a la papelera']);
            break;
        // ── Adjust Product Stock (Edit Stock Modal) ─────────────
        case 'adjust_product_stock':
            $product_id = intval($_POST['product_id'] ?? 0);
            $new_total  = intval($_POST['new_total'] ?? 0);
            $notes      = trim($_POST['notes'] ?? '');
            if (!$product_id || $new_total < 0 || !isset($_POST['new_total'])) {
                echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
                break;
            }

            $pdo->beginTransaction();
            try {
                // Get product info
                $stmtProd = $pdo->prepare("SELECT is_bulk, total_quantity, custom_columns FROM inventory_products WHERE id = ? FOR UPDATE");
                $stmtProd->execute([$product_id]);
                $prod = $stmtProd->fetch();
                if (!$prod) throw new Exception("Producto no encontrado");

                if ($prod['is_bulk']) {
                    $old_total = intval($prod['total_quantity']);
                } else {
                    $old_total = (int) $pdo->query("SELECT COUNT(*) FROM inventory_skus WHERE product_id = $product_id AND is_deleted = 0")->fetchColumn();
                }
                
                $diff = $new_total - $old_total;
                $user_id = intval($_SESSION['user_id'] ?? 0);

                if ($prod['is_bulk']) {
                    // Granel: just update quantity directly
                    $pdo->prepare("UPDATE inventory_products SET total_quantity = ? WHERE id = ?")->execute([$new_total, $product_id]);
                    // Log
                    $pdo->prepare("INSERT INTO inventory_stock_log (product_id, quantity, sku_codes, user_id, notes) VALUES (?, ?, '[]', ?, ?)")
                        ->execute([$product_id, $diff, $user_id, $notes ?: "Ajuste directo de stock: {$old_total} → {$new_total}"]);
                    $pdo->commit();
                    echo json_encode(['success' => true, 'message' => "Stock actualizado: {$old_total} → {$new_total}"]);
                } else {
                    // Normal SKU product
                    $skus_affected = [];
                    
                    $target_col = $_POST['target_col'] ?? 'sku_code';
                    $auto_generate = intval($_POST['auto_generate'] ?? 0);
                    $scanned_codes_str = $_POST['scanned_codes'] ?? '[]';
                    $scanned_codes = json_decode($scanned_codes_str, true) ?: [];

                    if (!$auto_generate && count($scanned_codes) !== abs($diff) && $diff !== 0) {
                        $pdo->rollBack();
                        echo json_encode(['success' => false, 'message' => "Se esperaban " . abs($diff) . " códigos escaneados, pero se recibieron " . count($scanned_codes) . "."]);
                        break;
                    }

                    if ($diff > 0) {
                        if (!$auto_generate) {
                            $unique = array_unique($scanned_codes);
                            if (count($unique) !== count($scanned_codes)) {
                                $pdo->rollBack();
                                echo json_encode(['success' => false, 'message' => "Hay códigos duplicados en tu escaneo."]);
                                break;
                            }
                            
                            // Check existence if target is sku_code
                            if ($target_col === 'sku_code') {
                                $placeholders = implode(',', array_fill(0, count($scanned_codes), '?'));
                                $stmtChk = $pdo->prepare("SELECT sku_code FROM inventory_skus WHERE sku_code IN ($placeholders) AND is_deleted = 0");
                                $stmtChk->execute($scanned_codes);
                                $existing = $stmtChk->fetchAll(PDO::FETCH_COLUMN);
                                if (!empty($existing)) {
                                    $pdo->rollBack();
                                    echo json_encode(['success' => false, 'message' => "Los siguientes códigos ya existen: " . implode(', ', $existing)]);
                                    break;
                                }
                            }
                        }

                        $cols = json_decode($prod['custom_columns'] ?? '[]', true) ?: [];
                        $emptyCustom = new stdClass();
                        foreach ($cols as $col) { $colName = is_array($col) ? ($col['name'] ?? '') : $col; if ($colName) $emptyCustom->{$colName} = ''; }

                        $generated = [];
                        $attempts = 0;
                        $max = $diff * 10;
                        
                        $ins = $pdo->prepare("INSERT INTO inventory_skus (product_id, sku_code, status, custom_data) VALUES (?, ?, 'disponible', ?)");
                        
                        for ($i = 0; $i < $diff; $i++) {
                            $customData = clone $emptyCustom;
                            
                            if ($auto_generate) {
                                $code = '';
                                while ($attempts < $max) {
                                    $c = 'TRB-' . generateRandomCode(6);
                                    $chk = $pdo->prepare("SELECT COUNT(*) FROM inventory_skus WHERE sku_code = ?");
                                    $chk->execute([$c]);
                                    if ($chk->fetchColumn() == 0 && !in_array($c, $generated)) { $code = $c; break; }
                                    $attempts++;
                                }
                                if (!$code) throw new Exception("No se pudieron generar códigos únicos");
                                $ins->execute([$product_id, $code, json_encode($customData)]);
                                $generated[] = $code;
                            } else {
                                $scanned = $scanned_codes[$i];
                                $code = '';
                                if ($target_col === 'sku_code') {
                                    $code = $scanned;
                                } else {
                                    while ($attempts < $max) {
                                        $c = 'TRB-' . generateRandomCode(6);
                                        $chk = $pdo->prepare("SELECT COUNT(*) FROM inventory_skus WHERE sku_code = ?");
                                        $chk->execute([$c]);
                                        if ($chk->fetchColumn() == 0 && !in_array($c, $generated)) { $code = $c; break; }
                                        $attempts++;
                                    }
                                    if (!$code) throw new Exception("No se pudieron generar códigos únicos");
                                    $customData->{$target_col} = $scanned;
                                }
                                $ins->execute([$product_id, $code, json_encode($customData)]);
                                $generated[] = $code;
                            }
                        }
                        $skus_affected = $auto_generate ? $generated : $scanned_codes;
                        
                    } elseif ($diff < 0) {
                        $toDelete = abs($diff);
                        $idsToDelete = [];
                        $affectedCodes = [];
                        
                        $stmtAvail = $pdo->prepare("SELECT id, sku_code, custom_data FROM inventory_skus WHERE product_id = ? AND status = 'disponible' AND is_deleted = 0 AND (assigned_to IS NULL OR assigned_to = 0)");
                        $stmtAvail->execute([$product_id]);
                        $availableSkus = $stmtAvail->fetchAll(PDO::FETCH_ASSOC);

                        if ($auto_generate) {
                            if (count($availableSkus) < $toDelete) {
                                $pdo->rollBack();
                                echo json_encode(['success' => false, 'message' => "No hay suficientes SKUs disponibles para eliminar. Se requieren $toDelete, pero hay " . count($availableSkus) . "."]);
                                exit;
                            }
                            shuffle($availableSkus);
                            for ($i = 0; $i < $toDelete; $i++) {
                                $idsToDelete[] = $availableSkus[$i]['id'];
                                $affectedCodes[] = $availableSkus[$i]['sku_code'];
                            }
                        } else {
                            foreach ($scanned_codes as $scanned) {
                                $foundId = null;
                                foreach ($availableSkus as $idx => $sku) {
                                    if (strcasecmp($sku['sku_code'], $scanned) === 0) {
                                        $foundId = $sku['id'];
                                        unset($availableSkus[$idx]);
                                        break;
                                    } else {
                                        $cd = json_decode($sku['custom_data'] ?? '{}', true) ?: [];
                                        $foundInCustom = false;
                                        foreach ($cd as $key => $val) {
                                            if (strcasecmp((string)$val, $scanned) === 0) {
                                                $foundInCustom = true;
                                                break;
                                            }
                                        }
                                        if ($foundInCustom) {
                                            $foundId = $sku['id'];
                                            unset($availableSkus[$idx]);
                                            break;
                                        }
                                    }
                                }
                                if ($foundId) {
                                    $idsToDelete[] = $foundId;
                                    $affectedCodes[] = $scanned;
                                } else {
                                    $pdo->rollBack();
                                    echo json_encode(['success' => false, 'message' => "El código '$scanned' no se encontró disponible en este producto."]);
                                    exit;
                                }
                            }
                        }
                        
                        if (count($idsToDelete) > 0) {
                            $placeholders = implode(',', array_fill(0, count($idsToDelete), '?'));
                            $pdo->prepare("UPDATE inventory_skus SET is_deleted = 1 WHERE id IN ($placeholders)")->execute($idsToDelete);
                        }
                        $skus_affected = $affectedCodes;
                    }
                    // Log
                    if ($diff != 0) {
                        $pdo->prepare("INSERT INTO inventory_stock_log (product_id, quantity, sku_codes, user_id, notes) VALUES (?, ?, ?, ?, ?)")
                            ->execute([$product_id, $diff, json_encode($skus_affected), $user_id, $notes ?: "Ajuste directo de stock: {$old_total} → {$new_total}"]);
                    }
                    $pdo->commit();
                    $msg = $diff > 0 ? "Generados {$diff} SKUs nuevos" : ($diff < 0 ? "Eliminados " . abs($diff) . " SKUs disponibles" : "Sin cambios");
                    echo json_encode(['success' => true, 'message' => $msg]);
                }
            } catch(Exception $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;

        // ── Adjust Variant Stock (Agrupado) ────────────────────
        case 'adjust_variant_stock':
            $variants_json = $_POST['variants'] ?? '[]';
            $notes = trim($_POST['notes'] ?? '');
            $variants = json_decode($variants_json, true);
            if (!is_array($variants) || empty($variants)) {
                echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
                break;
            }
            $user_id = intval($_SESSION['user_id'] ?? 0);
            $pdo->beginTransaction();
            try {
                $stmtGet = $pdo->prepare("SELECT id, total_quantity, product_type, parent_product_id FROM inventory_products WHERE id = ?");
                $stmtSet = $pdo->prepare("UPDATE inventory_products SET total_quantity = ? WHERE id = ?");
                $stmtLog = $pdo->prepare("INSERT INTO inventory_stock_log (product_id, quantity, sku_codes, user_id, notes) VALUES (?, ?, '[]', ?, ?)");
                $changed = 0;
                foreach ($variants as $v) {
                    $vId = intval($v['id'] ?? 0);
                    $newQty = max(0, floatval($v['new_quantity'] ?? 0));
                    if (!$vId) continue;
                    $stmtGet->execute([$vId]);
                    $row = $stmtGet->fetch();
                    if (!$row) continue;
                    $oldQty = floatval($row['total_quantity']);
                    $diff = $newQty - $oldQty;
                    $stmtSet->execute([$newQty, $vId]);
                    if ($diff != 0) {
                        $stmtLog->execute([$vId, $diff, $user_id, $notes ?: "Ajuste variante: {$oldQty} → {$newQty}"]);
                        $changed++;
                    }
                }
                $pdo->commit();
                echo json_encode(['success' => true, 'message' => $changed > 0 ? "Stock actualizado en {$changed} variante(s)" : "Sin cambios"]);
            } catch(Exception $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;

        // ── Log Query Endpoints ─────────────────────────────

        case 'get_assignment_log':
            $sku_filter = trim($_POST['sku'] ?? '');
            $user_filter = intval($_POST['user_id'] ?? 0);
            $date_from = trim($_POST['date_from'] ?? '');
            $date_to = trim($_POST['date_to'] ?? '');
            $limit = intval($_POST['limit'] ?? 50);

            $where = ['1=1'];
            $params = [];
            if ($sku_filter) { $where[] = '(sku_code LIKE ? OR product_name LIKE ?)'; $params[] = "%{$sku_filter}%"; $params[] = "%{$sku_filter}%"; }
            if ($user_filter) { $where[] = 'assigned_to = ?'; $params[] = $user_filter; }
            if ($date_from) { $where[] = 'created_at >= ?'; $params[] = $date_from . ' 00:00:00'; }
            if ($date_to) { $where[] = 'created_at <= ?'; $params[] = $date_to . ' 23:59:59'; }

            $sql = "SELECT * FROM inventory_assignment_log WHERE " . implode(' AND ', $where) . " ORDER BY created_at DESC LIMIT " . $limit;
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        case 'update_assignment_log':
            $log_id = intval($_POST['log_id'] ?? 0);
            $notes = trim($_POST['notes'] ?? '');
            $created_at = trim($_POST['created_at'] ?? '');
            $log_action = trim($_POST['log_action'] ?? ''); // 'assign' or 'unassign'
            $new_assigned_to = intval($_POST['assigned_to'] ?? 0);
            $new_quantity = floatval($_POST['quantity'] ?? 0);
            if (!$log_id) { echo json_encode(['success' => false, 'message' => 'ID inválido']); break; }

            // Build dynamic SET clause
            $setClauses = ['notes = ?'];
            $params = [$notes];

            if ($created_at) {
                $setClauses[] = 'created_at = ?';
                $params[] = $created_at;
            }

            // Update action (assign/unassign)
            if ($log_action === 'assign' || $log_action === 'unassign') {
                $setClauses[] = 'action = ?';
                $params[] = $log_action;
            }

            // Update assigned_to and name if action is assign
            if ($log_action === 'assign' && $new_assigned_to > 0) {
                $uName = $pdo->prepare("SELECT name FROM users WHERE id = ?");
                $uName->execute([$new_assigned_to]);
                $uNameVal = $uName->fetchColumn() ?: '';
                $setClauses[] = 'assigned_to = ?';
                $params[] = $new_assigned_to;
                $setClauses[] = 'assigned_to_name = ?';
                $params[] = $uNameVal;
            }

            // Update quantity if provided
            if ($new_quantity > 0) {
                $setClauses[] = 'quantity = ?';
                $params[] = $new_quantity;
            }

            $params[] = $log_id;
            $sql = "UPDATE inventory_assignment_log SET " . implode(', ', $setClauses) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            echo json_encode(['success' => true, 'message' => 'Registro actualizado']);
            break;

        case 'delete_assignment_log':
            $log_id = intval($_POST['log_id'] ?? 0);
            if (!$log_id) { echo json_encode(['success' => false, 'message' => 'ID inválido']); break; }
            $stmt = $pdo->prepare("DELETE FROM inventory_assignment_log WHERE id = ?");
            $stmt->execute([$log_id]);
            echo json_encode(['success' => true, 'message' => 'Registro eliminado']);
            break;

        case 'get_stock_log':
            $product_filter = intval($_POST['product_id'] ?? 0);
            $date_from = trim($_POST['date_from'] ?? '');
            $date_to = trim($_POST['date_to'] ?? '');
            $limit = intval($_POST['limit'] ?? 50);

            $where = ['1=1'];
            $params = [];
            if ($product_filter) { $where[] = 'sl.product_id = ?'; $params[] = $product_filter; }
            if ($date_from) { $where[] = 'sl.created_at >= ?'; $params[] = $date_from . ' 00:00:00'; }
            if ($date_to) { $where[] = 'sl.created_at <= ?'; $params[] = $date_to . ' 23:59:59'; }

            $sql = "SELECT sl.*, p.name as product_name, u.name as user_name FROM inventory_stock_log sl LEFT JOIN inventory_products p ON sl.product_id = p.id LEFT JOIN users u ON sl.user_id = u.id WHERE " . implode(' AND ', $where) . " ORDER BY sl.created_at DESC LIMIT " . $limit;
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        case 'update_stock_log':
            $log_id = intval($_POST['log_id'] ?? 0);
            $notes = trim($_POST['notes'] ?? '');
            $new_quantity = intval($_POST['quantity'] ?? 0);
            if (!$log_id || $new_quantity < 0) { echo json_encode(['success' => false, 'message' => 'Datos inválidos']); break; }

            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("SELECT * FROM inventory_stock_log WHERE id = ? FOR UPDATE");
                $stmt->execute([$log_id]);
                $log = $stmt->fetch();
                if (!$log) throw new Exception("Log no encontrado");

                $old_quantity = intval($log['quantity']);
                $diff = $new_quantity - $old_quantity;
                $sku_codes = json_decode($log['sku_codes'], true) ?: [];

                $stmtProd = $pdo->prepare("SELECT is_bulk, custom_columns FROM inventory_products WHERE id = ? FOR UPDATE");
                $stmtProd->execute([$log['product_id']]);
                $prod = $stmtProd->fetch();
                
                if (!$prod) throw new Exception("Producto no encontrado");

                if ($prod['is_bulk']) {
                    if ($diff != 0) {
                        $stmtUpdate = $pdo->prepare("UPDATE inventory_products SET total_quantity = total_quantity + ? WHERE id = ?");
                        $stmtUpdate->execute([$diff, $log['product_id']]);
                    }
                } else {
                    if ($diff > 0) {
                        $new_skus = [];
                        $attempts = 0;
                        $max_attempts = $diff * 10;
                        while (count($new_skus) < $diff && $attempts < $max_attempts) {
                            $code = 'TRB-' . generateRandomCode(6);
                            $check = $pdo->prepare("SELECT COUNT(*) FROM inventory_skus WHERE sku_code = ?");
                            $check->execute([$code]);
                            if ($check->fetchColumn() == 0 && !in_array($code, $new_skus)) $new_skus[] = $code;
                            $attempts++;
                        }
                        
                        $cols = json_decode($prod['custom_columns'], true) ?: [];
                        $customDataStr = count($cols) > 0 ? json_encode(array_fill_keys(array_column($cols, 'name'), ''), JSON_UNESCAPED_UNICODE) : NULL;

                        $insert = $pdo->prepare("INSERT INTO inventory_skus (product_id, sku_code, status, custom_data) VALUES (?, ?, 'disponible', ?)");
                        foreach ($new_skus as $sku) {
                            $insert->execute([$log['product_id'], $sku, $customDataStr]);
                            $sku_codes[] = $sku;
                        }
                    } elseif ($diff < 0) {
                        $to_delete = abs($diff);
                        if (count($sku_codes) > 0) {
                            $placeholders = implode(',', array_fill(0, count($sku_codes), '?'));
                            // Usamos PDO::PARAM_INT implícitamente, pero PDO con in() a veces requiere bindValue manual.
                            // Aquí execute($params) funciona bien en mysql para strings.
                            $stmtDisp = $pdo->prepare("SELECT sku_code FROM inventory_skus WHERE sku_code IN ($placeholders) AND status = 'disponible' LIMIT ?");
                            $params = $sku_codes;
                            $params[] = $to_delete;
                            // bind parameters to ensure LIMIT works correctly
                            foreach ($sku_codes as $k => $v) {
                                $stmtDisp->bindValue($k + 1, $v);
                            }
                            $stmtDisp->bindValue(count($sku_codes) + 1, $to_delete, PDO::PARAM_INT);
                            $stmtDisp->execute();
                            $disp_skus = $stmtDisp->fetchAll(PDO::FETCH_COLUMN);
                            
                            if (count($disp_skus) < $to_delete) {
                                throw new Exception("No hay suficientes SKUs disponibles para eliminar. Ya han sido usados o asignados.");
                            }
                            
                            $delPlaceholders = implode(',', array_fill(0, count($disp_skus), '?'));
                            $stmtDel = $pdo->prepare("UPDATE inventory_skus SET is_deleted = 1 WHERE sku_code IN ($delPlaceholders)");
                            $stmtDel->execute($disp_skus);
                            
                            $sku_codes = array_values(array_diff($sku_codes, $disp_skus));
                        } else {
                            throw new Exception("Lote vacío o antiguo.");
                        }
                    }
                }

                $stmtLog = $pdo->prepare("UPDATE inventory_stock_log SET notes = ?, quantity = ?, sku_codes = ? WHERE id = ?");
                $stmtLog->execute([$notes, $new_quantity, json_encode($sku_codes), $log_id]);
                
                $pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Registro actualizado y stock ajustado', 'new_skus' => $sku_codes]);
                
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;

        case 'delete_stock_log':
            $log_id = intval($_POST['log_id'] ?? 0);
            if (!$log_id) { echo json_encode(['success' => false, 'message' => 'ID inválido']); break; }
            
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("SELECT * FROM inventory_stock_log WHERE id = ? FOR UPDATE");
                $stmt->execute([$log_id]);
                $log = $stmt->fetch();
                if (!$log) throw new Exception("Log no encontrado");

                $stmtProd = $pdo->prepare("SELECT is_bulk FROM inventory_products WHERE id = ? FOR UPDATE");
                $stmtProd->execute([$log['product_id']]);
                $prod = $stmtProd->fetch();

                if ($prod && $prod['is_bulk']) {
                    $stmtUpdate = $pdo->prepare("UPDATE inventory_products SET total_quantity = total_quantity - ? WHERE id = ?");
                    $stmtUpdate->execute([$log['quantity'], $log['product_id']]);
                } elseif ($prod) {
                    $sku_codes = json_decode($log['sku_codes'], true) ?: [];
                    if (count($sku_codes) > 0) {
                        $placeholders = implode(',', array_fill(0, count($sku_codes), '?'));
                        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM inventory_skus WHERE sku_code IN ($placeholders) AND status != 'disponible'");
                        $stmtCheck->execute($sku_codes);
                        if ($stmtCheck->fetchColumn() > 0) {
                            throw new Exception("No se puede eliminar: algunos SKUs de este lote ya han sido usados o asignados.");
                        }
                        $stmtDel = $pdo->prepare("UPDATE inventory_skus SET is_deleted = 1 WHERE sku_code IN ($placeholders)");
                        $stmtDel->execute($sku_codes);
                    }
                }

                $stmtDelLog = $pdo->prepare("DELETE FROM inventory_stock_log WHERE id = ?");
                $stmtDelLog->execute([$log_id]);
                
                $pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Registro y stock eliminados']);
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;

        case 'export_products':
            $ids = isset($_GET['ids']) ? explode(',', $_GET['ids']) : [];
            if (empty($ids)) {
                echo json_encode(['success' => false, 'message' => 'No IDs provided']);
                break;
            }
            
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $sql = "SELECT p.*, c.name as category_name,
                    (SELECT COUNT(*) FROM inventory_skus WHERE product_id = p.id AND status = 'disponible') as qty_disponible,
                    (SELECT COUNT(*) FROM inventory_skus WHERE product_id = p.id AND status = 'instalado') as qty_instalado,
                    (SELECT COUNT(*) FROM inventory_skus WHERE product_id = p.id AND status = 'malogrado') as qty_malogrado,
                    (SELECT COUNT(*) FROM inventory_skus WHERE product_id = p.id AND status = 'observacion') as qty_observacion
                    FROM inventory_products p
                    LEFT JOIN inventory_categories c ON p.category_id = c.id
                    WHERE p.id IN ($placeholders)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($ids);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=productos_exportados_' . date('Ymd_His') . '.csv');
            
            $output = fopen('php://output', 'w');
            fputcsv($output, ['ID', 'Nombre', 'Categoria', 'Total', 'Disponibles', 'Instalados', 'Malogrados', 'Observados']);
            
            foreach ($products as $p) {
                fputcsv($output, [
                    $p['id'],
                    $p['name'],
                    $p['category_name'] ?: 'Sin categoria',
                    $p['total_quantity'],
                    $p['is_bulk'] ? $p['total_quantity'] : $p['qty_disponible'],
                    $p['qty_instalado'],
                    $p['is_bulk'] ? 0 : $p['qty_malogrado'],
                    $p['is_bulk'] ? 0 : $p['qty_observacion']
                ]);
            }
            fclose($output);
            exit;

        // ── Papelera ────────────────────────────────────────
        case 'get_deleted_items':
            try {
                $sqlProd = "SELECT p.id as item_id, 'product' as item_type, p.name as name, '' as product_name, c.name as category_name, p.master_sku as code, 
                            (SELECT COUNT(*) FROM inventory_skus WHERE product_id = p.id) as quantity,
                            p.created_at as deleted_at, 0 as parent_deleted
                            FROM inventory_products p LEFT JOIN inventory_categories c ON p.category_id = c.id WHERE p.is_deleted = 1";
                $sqlSku = "SELECT s.id as item_id, 'sku' as item_type, p.name as product_name, p.name as name, c.name as category_name, s.sku_code as code, 1 as quantity,
                           s.status as sku_status, s.created_at as deleted_at, p.is_deleted as parent_deleted
                           FROM inventory_skus s JOIN inventory_products p ON s.product_id = p.id LEFT JOIN inventory_categories c ON p.category_id = c.id WHERE s.is_deleted = 1";
                
                $stmt1 = $pdo->query($sqlProd);
                $deletedProducts = $stmt1->fetchAll(PDO::FETCH_ASSOC);
                $stmt2 = $pdo->query($sqlSku);
                $deletedSkus = $stmt2->fetchAll(PDO::FETCH_ASSOC);
                
                $all = array_merge($deletedProducts, $deletedSkus);
                $json = json_encode(['success' => true, 'data' => $all]);
                if ($json === false) {
                    file_put_contents('debug.txt', 'JSON Error: ' . json_last_error_msg());
                    echo json_encode(['success' => false, 'message' => 'JSON Error: ' . json_last_error_msg()]);
                } else {
                    echo $json;
                }
            } catch (Exception $ex) {
                file_put_contents('debug.txt', $ex->getMessage());
                echo json_encode(['success' => false, 'message' => $ex->getMessage()]);
            }
            break;

        case 'restore_item':
            $item_id = intval($_POST['item_id'] ?? 0);
            $item_type = $_POST['item_type'] ?? '';
            if (!$item_id || !in_array($item_type, ['product', 'sku'])) {
                echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
                break;
            }
            if ($item_type === 'product') {
                $pdo->prepare("UPDATE inventory_products SET is_deleted = 0 WHERE id = ?")->execute([$item_id]);
                $pdo->prepare("UPDATE inventory_skus SET is_deleted = 0 WHERE product_id = ?")->execute([$item_id]);
            } else {
                $pdo->prepare("UPDATE inventory_skus SET is_deleted = 0 WHERE id = ?")->execute([$item_id]);
            }
            echo json_encode(['success' => true, 'message' => 'Elemento restaurado']);
            break;

        case 'hard_delete_item':
            $item_id = intval($_POST['item_id'] ?? 0);
            $item_type = $_POST['item_type'] ?? '';
            if (!$item_id || !in_array($item_type, ['product', 'sku'])) {
                echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
                break;
            }
            if ($item_type === 'product') {
                $pdo->prepare("DELETE FROM inventory_products WHERE id = ?")->execute([$item_id]);
            } else {
                $pdo->prepare("DELETE FROM inventory_skus WHERE id = ?")->execute([$item_id]);
            }
            echo json_encode(['success' => true, 'message' => 'Elemento eliminado permanentemente']);
            break;

        case 'empty_papelera':
            $pdo->prepare("DELETE FROM inventory_products WHERE is_deleted = 1")->execute();
            $pdo->prepare("DELETE FROM inventory_skus WHERE is_deleted = 1")->execute();
            echo json_encode(['success' => true, 'message' => 'Papelera vaciada correctamente']);
            break;

        // ══════════════════════════════════════════════════════
        // ── HISTORIAL Y GESTIÓN DE COMPRAS ────────────────────
        // ══════════════════════════════════════════════════════

        case 'search_product_history':
            $query = trim($_POST['query'] ?? $_GET['query'] ?? '');
            if (!$query) {
                echo json_encode(['success' => true, 'data' => []]);
                break;
            }
            $searchTerm = "%{$query}%";
            
            // 1. Buscar por SKU exacto o coincidencia (incluyendo si está asignado a un usuario)
            $stmtSku = $pdo->prepare("
                SELECT s.id as sku_id, s.sku_code, s.status, s.custom_data, s.assigned_to,
                       u.name as assigned_user_name, u.role as assigned_user_role,
                       p.id as product_id, p.name as product_name, p.product_type, p.product_image, p.costo_producto, p.is_bulk,
                       c.name as category_name,
                       (CASE WHEN c.name = 'EPP' THEN 1 ELSE 0 END) as is_epp
                FROM inventory_skus s
                JOIN inventory_products p ON s.product_id = p.id
                LEFT JOIN inventory_categories c ON p.category_id = c.id
                LEFT JOIN users u ON s.assigned_to = u.id
                WHERE (s.sku_code LIKE ? OR s.custom_data LIKE ?) AND s.is_deleted = 0 AND p.is_deleted = 0
                LIMIT 10
            ");
            $stmtSku->execute([$searchTerm, $searchTerm]);
            $skuResults = $stmtSku->fetchAll();

            // 2. Buscar por producto (nombre, descripción, master_sku, categoría)
            $stmtProd = $pdo->prepare("
                SELECT p.id as product_id, p.name as product_name, p.master_sku, p.product_type, p.total_quantity, p.costo_producto, p.product_image, p.is_bulk, c.name as category_name,
                       (CASE WHEN c.name = 'EPP' THEN 1 ELSE 0 END) as is_epp,
                       (SELECT COUNT(*) FROM inventory_skus s WHERE s.product_id = p.id AND s.status = 'disponible' AND s.is_deleted = 0) as qty_disponible,
                       (SELECT COUNT(*) FROM inventory_skus s WHERE s.product_id = p.id AND s.assigned_to IS NOT NULL AND s.status = 'disponible' AND s.is_deleted = 0) as qty_assigned_skus,
                       (SELECT COALESCE(SUM(us.quantity), 0) FROM inventory_user_stock us WHERE us.product_id = p.id) as qty_assigned_bulk
                FROM inventory_products p
                LEFT JOIN inventory_categories c ON p.category_id = c.id
                WHERE (p.name LIKE ? OR p.master_sku LIKE ? OR p.description LIKE ? OR c.name LIKE ?) AND p.is_deleted = 0
                LIMIT 10
            ");
            $stmtProd->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            $prodResults = $stmtProd->fetchAll();

            // 3. Buscar por Usuario / Técnico (si el usuario busca el nombre de una persona para ver sus EPPs/equipos)
            $stmtUsers = $pdo->prepare("
                SELECT u.id as user_id, u.name as user_name, u.email, u.role,
                       (SELECT COUNT(*) FROM inventory_skus s WHERE s.assigned_to = u.id AND s.status = 'disponible' AND s.is_deleted = 0) as sku_count,
                       (SELECT COALESCE(SUM(us.quantity), 0) FROM inventory_user_stock us WHERE us.user_id = u.id AND us.quantity > 0) as bulk_count
                FROM users u
                WHERE (u.name LIKE ? OR u.email LIKE ?)
                HAVING (sku_count > 0 OR bulk_count > 0)
                LIMIT 5
            ");
            $stmtUsers->execute([$searchTerm, $searchTerm]);
            $userResults = $stmtUsers->fetchAll();

            echo json_encode([
                'success' => true,
                'skus' => $skuResults,
                'products' => $prodResults,
                'users' => $userResults
            ]);
            break;

        case 'get_product_history_details':
            $product_id = intval($_POST['product_id'] ?? $_GET['product_id'] ?? 0);
            $sku_code = trim($_POST['sku_code'] ?? $_GET['sku_code'] ?? '');

            if (!$product_id && $sku_code) {
                $stmtFind = $pdo->prepare("SELECT product_id FROM inventory_skus WHERE sku_code = ? AND is_deleted = 0 LIMIT 1");
                $stmtFind->execute([$sku_code]);
                $product_id = $stmtFind->fetchColumn();
            }

            if (!$product_id) {
                echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
                break;
            }

            // Datos del producto
            $stmtP = $pdo->prepare("
                SELECT p.*, c.name as category_name,
                       (CASE WHEN c.name = 'EPP' THEN 1 ELSE 0 END) as is_epp_category,
                       (SELECT ruta_archivo FROM inventory_product_photos WHERE product_id = p.id LIMIT 1) as gallery_photo
                FROM inventory_products p
                LEFT JOIN inventory_categories c ON p.category_id = c.id
                WHERE p.id = ? AND p.is_deleted = 0
            ");
            $stmtP->execute([$product_id]);
            $product = $stmtP->fetch();

            if (!$product) {
                echo json_encode(['success' => false, 'message' => 'El producto no existe o está en papelera']);
                break;
            }

            // Obtener todos los IDs asociados (el producto mismo + variantes hijas si es agrupado/bundle)
            $stmtChildren = $pdo->prepare("SELECT id FROM inventory_products WHERE parent_product_id = ? AND is_deleted = 0");
            $stmtChildren->execute([$product_id]);
            $childIds = $stmtChildren->fetchAll(PDO::FETCH_COLUMN);
            $allProductIds = array_merge([$product_id], $childIds);
            $inPlaceholders = implode(',', array_fill(0, count($allProductIds), '?'));

            $isGroupedOrBundle = ($product['product_type'] === 'agrupado' || $product['product_type'] === 'bundle');
            $isBulk = ($product['is_bulk'] == 1 && !$isGroupedOrBundle);

            // Métricas de stock
            $metrics = [
                'total' => 0,
                'disponible' => 0,
                'instalado' => 0,
                'malogrado' => 0,
                'observacion' => 0,
                'reparado' => 0,
                'en_transito' => 0,
                'asignado_tecnicos' => 0
            ];

            // 1. Total quantity
            $stmtTotalQty = $pdo->prepare("SELECT COALESCE(SUM(total_quantity), 0) FROM inventory_products WHERE id IN ($inPlaceholders) AND is_deleted = 0");
            $stmtTotalQty->execute($allProductIds);
            $metrics['total'] = floatval($stmtTotalQty->fetchColumn());

            // 2. SKUs counts (para productos unitarios o variantes con SKUs)
            $stmtCounts = $pdo->prepare("
                SELECT status, COUNT(*) as count 
                FROM inventory_skus 
                WHERE product_id IN ($inPlaceholders) AND is_deleted = 0 
                GROUP BY status
            ");
            $stmtCounts->execute($allProductIds);
            $counts = $stmtCounts->fetchAll(PDO::FETCH_KEY_PAIR);

            $metrics['disponible'] = intval($counts['disponible'] ?? 0);
            $metrics['instalado'] = intval($counts['instalado'] ?? 0);
            $metrics['malogrado'] = intval($counts['malogrado'] ?? 0);
            $metrics['observacion'] = intval($counts['observacion'] ?? 0);
            $metrics['reparado'] = intval($counts['reparado'] ?? 0);
            $metrics['en_transito'] = intval($counts['en_transito'] ?? 0);

            // 3. Conteo de asignados a técnicos con SKU
            $stmtAssignedSkus = $pdo->prepare("
                SELECT COUNT(*) FROM inventory_skus 
                WHERE product_id IN ($inPlaceholders) AND assigned_to IS NOT NULL AND status = 'disponible' AND is_deleted = 0
            ");
            $stmtAssignedSkus->execute($allProductIds);
            $metrics['asignado_tecnicos'] = intval($stmtAssignedSkus->fetchColumn());

            // 4. Conteo para ítems o variantes a Granel
            $stmtBulkSum = $pdo->prepare("SELECT COALESCE(SUM(total_quantity), 0) FROM inventory_products WHERE id IN ($inPlaceholders) AND is_bulk = 1 AND is_deleted = 0");
            $stmtBulkSum->execute($allProductIds);
            $bulkTotalQty = floatval($stmtBulkSum->fetchColumn());

            if ($bulkTotalQty > 0) {
                $stmtBulkAssigned = $pdo->prepare("SELECT COALESCE(SUM(quantity), 0) FROM inventory_user_stock WHERE product_id IN ($inPlaceholders)");
                $stmtBulkAssigned->execute($allProductIds);
                $bulkAssigned = floatval($stmtBulkAssigned->fetchColumn());

                $metrics['disponible'] += max(0, $bulkTotalQty - $bulkAssigned);
                $metrics['asignado_tecnicos'] += $bulkAssigned;
            }

            // Datos de SKU Activo consultado directamente
            $activeSkuDetail = null;
            if ($sku_code) {
                $stmtSkuInfo = $pdo->prepare("
                    SELECT s.*, u.name as assigned_to_name, u.email as assigned_to_email, u.role as assigned_to_role,
                           (SELECT created_at FROM inventory_assignment_log WHERE sku_id = s.id AND action = 'assign' ORDER BY id DESC LIMIT 1) as assigned_date
                    FROM inventory_skus s
                    LEFT JOIN users u ON s.assigned_to = u.id
                    WHERE s.sku_code = ? AND s.is_deleted = 0
                    LIMIT 1
                ");
                $stmtSkuInfo->execute([$sku_code]);
                $activeSkuDetail = $stmtSkuInfo->fetch();
            }

            // Técnicos con stock o EPP asignado actualmente
            $technicians = [];
            // A Granel / EPP en posesión
            $stmtTechBulk = $pdo->prepare("
                SELECT u.id, u.name, u.email, u.role, SUM(us.quantity) as quantity, us.is_epp,
                       (CASE WHEN us.is_epp = 1 OR c.name = 'EPP' THEN 1 ELSE 0 END) as is_epp_badge,
                       p.name as product_name
                FROM inventory_user_stock us
                JOIN users u ON us.user_id = u.id
                JOIN inventory_products p ON us.product_id = p.id
                LEFT JOIN inventory_categories c ON p.category_id = c.id
                WHERE us.product_id IN ($inPlaceholders) AND us.quantity > 0
                GROUP BY u.id, us.is_epp
            ");
            $stmtTechBulk->execute($allProductIds);
            $bulkTechs = $stmtTechBulk->fetchAll();

            // Con SKUs en posesión
            $stmtTechSkus = $pdo->prepare("
                SELECT u.id, u.name, u.email, u.role, COUNT(s.id) as count, GROUP_CONCAT(s.sku_code SEPARATOR ', ') as skus,
                       (CASE WHEN c.name = 'EPP' THEN 1 ELSE 0 END) as is_epp_badge
                FROM inventory_skus s
                JOIN users u ON s.assigned_to = u.id
                JOIN inventory_products p ON s.product_id = p.id
                LEFT JOIN inventory_categories c ON p.category_id = c.id
                WHERE s.product_id IN ($inPlaceholders) AND s.status = 'disponible' AND s.is_deleted = 0
                GROUP BY u.id
            ");
            $stmtTechSkus->execute($allProductIds);
            $skuTechs = $stmtTechSkus->fetchAll();

            $technicians = array_merge($bulkTechs, $skuTechs);

            // Historial de Movimientos Unificado
            $timeline = [];

            // 1. Ingresos y ajustes de stock
            $stmtStockLogs = $pdo->prepare("
                SELECT sl.id, 'stock_entry' as event_type, 'Ingreso de Stock' as event_title, 
                       sl.created_at, sl.quantity, sl.sku_codes, sl.notes, u.name as user_name
                FROM inventory_stock_log sl
                LEFT JOIN users u ON sl.user_id = u.id
                WHERE sl.product_id IN ($inPlaceholders)
                ORDER BY sl.created_at DESC
                LIMIT 50
            ");
            $stmtStockLogs->execute($allProductIds);
            foreach ($stmtStockLogs->fetchAll() as $r) {
                $timeline[] = [
                    'id' => 'stock_' . $r['id'],
                    'type' => 'stock_entry',
                    'badge_class' => 'badge-stock-in',
                    'icon' => 'ph-arrow-down-left',
                    'title' => 'Ingreso de Stock',
                    'description' => "Se añadieron {$r['quantity']} " . ($product['unit_type'] ?? 'unidades') . ($r['notes'] ? " — {$r['notes']}" : ''),
                    'details' => $r['sku_codes'] ? "SKUs: " . (strlen($r['sku_codes']) > 120 ? substr($r['sku_codes'], 0, 120) . '...' : $r['sku_codes']) : null,
                    'user' => $r['user_name'] ?? 'Sistema',
                    'date' => $r['created_at'],
                    'timestamp' => strtotime($r['created_at'])
                ];
            }

            // 2. Asignaciones a personal
            $stmtAssignLogs = $pdo->prepare("
                SELECT al.id, al.action, al.sku_code, al.quantity, al.notes, al.created_at,
                       al.assigned_to_name, al.assigned_by_name
                FROM inventory_assignment_log al
                WHERE al.product_id IN ($inPlaceholders)
                ORDER BY al.created_at DESC
                LIMIT 50
            ");
            $stmtAssignLogs->execute($allProductIds);
            foreach ($stmtAssignLogs->fetchAll() as $r) {
                $isAssign = ($r['action'] === 'assign');
                $timeline[] = [
                    'id' => 'assign_' . $r['id'],
                    'type' => $isAssign ? 'assignment' : 'unassignment',
                    'badge_class' => $isAssign ? 'badge-assign' : 'badge-unassign',
                    'icon' => $isAssign ? 'ph-user-plus' : 'ph-user-minus',
                    'title' => $isAssign ? 'Asignación a Técnico' : 'Devolución de Técnico',
                    'description' => ($isAssign ? "Entregado a: " : "Devuelto por: ") . "<strong>" . htmlspecialchars($r['assigned_to_name'] ?? 'Técnico') . "</strong>" . ($r['notes'] ? " ({$r['notes']})" : ''),
                    'details' => $r['sku_code'] ? "SKU: {$r['sku_code']}" : "Cantidad: {$r['quantity']} " . ($product['unit_type'] ?? 'und'),
                    'user' => $r['assigned_by_name'] ?? 'Administrador',
                    'date' => $r['created_at'],
                    'timestamp' => strtotime($r['created_at'])
                ];
            }

            // 3. Instalaciones en Actas de Clientes (Equipos y Materiales)
            // Equipos instalados
            $stmtActasEquipos = $pdo->prepare("
                SELECT ae.id, ae.serie_mac, ae.modelo_marca, a.id as acta_id, a.folio, a.cliente_nombre, a.cliente_direccion,
                       a.fecha_creacion, u.name as tecnico_nombre
                FROM actas_equipos ae
                JOIN actas a ON ae.acta_id = a.id
                LEFT JOIN users u ON a.tecnico_id = u.id
                WHERE (ae.modelo_marca LIKE ? OR ae.serie_mac IN (SELECT sku_code FROM inventory_skus WHERE product_id IN ($inPlaceholders)))
                ORDER BY a.fecha_creacion DESC
                LIMIT 30
            ");
            $equipParams = array_merge(['%' . $product['name'] . '%'], $allProductIds);
            $stmtActasEquipos->execute($equipParams);
            foreach ($stmtActasEquipos->fetchAll() as $r) {
                $timeline[] = [
                    'id' => 'acta_eq_' . $r['id'],
                    'type' => 'installation',
                    'badge_class' => 'badge-installed',
                    'icon' => 'ph-house-line',
                    'title' => 'Instalación en Cliente',
                    'description' => "Instalado en: <strong>" . htmlspecialchars($r['cliente_nombre']) . "</strong> (Acta: <strong>" . htmlspecialchars($r['folio']) . "</strong>)",
                    'details' => "Serie/MAC/SKU: {$r['serie_mac']} | Dir: " . ($r['cliente_direccion'] ?? 'N/D'),
                    'user' => $r['tecnico_nombre'] ?? 'Técnico',
                    'date' => $r['fecha_creacion'],
                    'timestamp' => strtotime($r['fecha_creacion'])
                ];
            }

            // Materiales instalados
            $stmtActasMat = $pdo->prepare("
                SELECT am.id, am.descripcion, am.cantidad, am.unidad, a.id as acta_id, a.folio, a.cliente_nombre,
                       a.fecha_creacion, u.name as tecnico_nombre
                FROM actas_materiales am
                JOIN actas a ON am.acta_id = a.id
                LEFT JOIN users u ON a.tecnico_id = u.id
                WHERE am.descripcion LIKE ?
                ORDER BY a.fecha_creacion DESC
                LIMIT 30
            ");
            $stmtActasMat->execute(['%' . $product['name'] . '%']);
            foreach ($stmtActasMat->fetchAll() as $r) {
                $timeline[] = [
                    'id' => 'acta_mat_' . $r['id'],
                    'type' => 'material_use',
                    'badge_class' => 'badge-installed',
                    'icon' => 'ph-check-circle',
                    'title' => 'Material Utilizado en Instalación',
                    'description' => "Utilizado {$r['cantidad']} {$r['unidad']} en cliente: <strong>" . htmlspecialchars($r['cliente_nombre']) . "</strong> (Acta: <strong>" . htmlspecialchars($r['folio']) . "</strong>)",
                    'details' => null,
                    'user' => $r['tecnico_nombre'] ?? 'Técnico',
                    'date' => $r['fecha_creacion'],
                    'timestamp' => strtotime($r['fecha_creacion'])
                ];
            }

            // 4. Compras del Producto
            $stmtPurchases = $pdo->prepare("
                SELECT pur.*, u.name as user_name
                FROM inventory_purchases pur
                LEFT JOIN users u ON pur.user_id = u.id
                WHERE pur.product_id IN ($inPlaceholders)
                ORDER BY pur.purchase_date DESC
            ");
            $stmtPurchases->execute($allProductIds);
            $purchases = $stmtPurchases->fetchAll();

            foreach ($purchases as $pur) {
                $timeline[] = [
                    'id' => 'purchase_' . $pur['id'],
                    'type' => 'purchase',
                    'badge_class' => 'badge-purchase',
                    'icon' => 'ph-shopping-cart',
                    'title' => 'Compra Registrada',
                    'description' => "Compra de {$pur['quantity']} " . ($product['unit_type'] ?? 'unidades') . " a <strong>" . htmlspecialchars($pur['supplier_name'] ?? 'Proveedor') . "</strong> por " . ($pur['currency'] === 'USD' ? '$' : 'S/') . " " . number_format($pur['total_amount'], 2),
                    'details' => ($pur['invoice_number'] ? "Factura/Comprobante: {$pur['invoice_number']}" : '') . ($pur['document_path'] ? " | Comprobante Adjunto" : ''),
                    'user' => $pur['user_name'] ?? 'Administrador',
                    'date' => $pur['purchase_date'],
                    'timestamp' => strtotime($pur['purchase_date'])
                ];
            }

            // 6. Historial de Escaneos
            try {
                $stmtScans = $pdo->prepare("
                    SELECT sc.id, sc.sku_code, sc.created_at, u.name as user_name
                    FROM inventory_scans sc
                    LEFT JOIN users u ON sc.user_id = u.id
                    WHERE sc.product_id IN ($inPlaceholders)
                    ORDER BY sc.created_at DESC
                    LIMIT 50
                ");
                $stmtScans->execute($allProductIds);
                foreach ($stmtScans->fetchAll() as $r) {
                    $timeline[] = [
                        'id' => 'scan_' . $r['id'],
                        'type' => 'scan',
                        'badge_class' => 'badge-scan',
                        'icon' => 'ph-barcode',
                        'title' => 'Escaneo de Producto',
                        'description' => "Se escaneó el código: <strong>" . htmlspecialchars($r['sku_code'] ?? 'Desconocido') . "</strong>",
                        'details' => null,
                        'user' => $r['user_name'] ?? 'Usuario',
                        'date' => $r['created_at'],
                        'timestamp' => strtotime($r['created_at'])
                    ];
                }
            } catch (Exception $eScans) {
                // Si la tabla no está disponible o falla, continuar sin bloquear el timeline
            }

            // Ordenar timeline desc por fecha
            usort($timeline, function($a, $b) {
                return $b['timestamp'] - $a['timestamp'];
            });

            // Fecha de última actividad
            $lastActivity = !empty($timeline) ? $timeline[0]['date'] : $product['created_at'];

            echo json_encode([
                'success' => true,
                'product' => $product,
                'metrics' => $metrics,
                'active_sku' => $activeSkuDetail,
                'technicians' => $technicians,
                'last_activity' => $lastActivity,
                'timeline' => array_slice($timeline, 0, 80),
                'purchases' => $purchases
            ]);
            break;

        case 'list_purchases':
            $product_id = intval($_POST['product_id'] ?? $_GET['product_id'] ?? 0);
            $date_from = trim($_POST['date_from'] ?? $_GET['date_from'] ?? '');
            $date_to = trim($_POST['date_to'] ?? $_GET['date_to'] ?? '');
            $search = trim($_POST['search'] ?? $_GET['search'] ?? '');

            $where = ["1=1"];
            $params = [];

            if ($product_id > 0) {
                $where[] = "pur.product_id = ?";
                $params[] = $product_id;
            }
            if ($date_from) {
                $where[] = "pur.purchase_date >= ?";
                $params[] = $date_from . " 00:00:00";
            }
            if ($date_to) {
                $where[] = "pur.purchase_date <= ?";
                $params[] = $date_to . " 23:59:59";
            }
            if ($search) {
                $where[] = "(pur.supplier_name LIKE ? OR pur.invoice_number LIKE ? OR p.name LIKE ?)";
                $params[] = "%{$search}%";
                $params[] = "%{$search}%";
                $params[] = "%{$search}%";
            }

            $sql = "
                SELECT pur.*, p.name as product_name, p.product_image, p.unit_type, c.name as category_name, u.name as user_name
                FROM inventory_purchases pur
                JOIN inventory_products p ON pur.product_id = p.id
                LEFT JOIN inventory_categories c ON p.category_id = c.id
                LEFT JOIN users u ON pur.user_id = u.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY pur.purchase_date DESC
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $purchases = $stmt->fetchAll();

            $totalAmount = 0;
            foreach ($purchases as $p) {
                $totalAmount += floatval($p['total_amount'] ?? 0);
            }

            echo json_encode([
                'success' => true,
                'data' => $purchases,
                'total_amount' => $totalAmount,
                'total_count' => count($purchases)
            ]);
            break;

        case 'create_purchase':
            $product_id = intval($_POST['product_id'] ?? 0);
            $supplier_name = trim($_POST['supplier_name'] ?? '');
            $invoice_number = trim($_POST['invoice_number'] ?? '');
            $purchase_date = trim($_POST['purchase_date'] ?? date('Y-m-d H:i:s'));
            $quantity = floatval($_POST['quantity'] ?? 0);
            $unit_price = floatval($_POST['unit_price'] ?? 0);
            $total_amount = floatval($_POST['total_amount'] ?? ($quantity * $unit_price));
            $currency = trim($_POST['currency'] ?? 'PEN');
            $notes = trim($_POST['notes'] ?? '');
            $increase_stock = intval($_POST['increase_stock'] ?? 0);
            $user_id = $_SESSION['user_id'] ?? 1;

            if ($product_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Debes seleccionar un producto válido']);
                break;
            }
            if ($quantity <= 0) {
                echo json_encode(['success' => false, 'message' => 'La cantidad debe ser mayor a 0']);
                break;
            }

            // Procesar archivo adjunto si existe
            $document_path = null;
            $document_type = null;

            if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['document_file'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed = ['pdf', 'png', 'jpg', 'jpeg', 'webp'];

                if (!in_array($ext, $allowed)) {
                    echo json_encode(['success' => false, 'message' => 'Formato de archivo no válido. Solo se permiten PDF, PNG, JPG, WEBP.']);
                    break;
                }

                $uploadDir = __DIR__ . '/../uploads/facturas/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $filename = 'factura_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $target = $uploadDir . $filename;

                if (move_uploaded_file($file['tmp_name'], $target)) {
                    $document_path = 'uploads/facturas/' . $filename;
                    $document_type = $ext;
                }
            }

            $pdo->beginTransaction();

            $stmtIns = $pdo->prepare("
                INSERT INTO inventory_purchases 
                (product_id, supplier_name, invoice_number, purchase_date, quantity, unit_price, total_amount, currency, document_path, document_type, notes, user_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtIns->execute([
                $product_id, $supplier_name, $invoice_number, $purchase_date, $quantity, $unit_price, $total_amount, $currency, $document_path, $document_type, $notes, $user_id
            ]);
            $purchase_id = $pdo->lastInsertId();

            // Incrementar stock si se solicitó
            if ($increase_stock == 1) {
                $pdo->prepare("UPDATE inventory_products SET total_quantity = total_quantity + ? WHERE id = ?")->execute([$quantity, $product_id]);
                
                // Registrar en log de stock
                $stmtLog = $pdo->prepare("
                    INSERT INTO inventory_stock_log (product_id, quantity, user_id, notes) 
                    VALUES (?, ?, ?, ?)
                ");
                $logNote = "Ingreso por Compra Factura: " . ($invoice_number ?: 'S/N') . ($supplier_name ? " - Proveedor: $supplier_name" : "");
                $stmtLog->execute([$product_id, $quantity, $user_id, $logNote]);
            }

            // Actualizar costo_producto si unit_price > 0
            if ($unit_price > 0) {
                $pdo->prepare("UPDATE inventory_products SET costo_producto = ? WHERE id = ?")->execute([$unit_price, $product_id]);
            }

            $pdo->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Compra registrada correctamente',
                'purchase_id' => $purchase_id
            ]);
            break;

        case 'delete_purchase':
            $purchase_id = intval($_POST['purchase_id'] ?? 0);
            if ($purchase_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID de compra inválido']);
                break;
            }

            $stmtDoc = $pdo->prepare("SELECT document_path FROM inventory_purchases WHERE id = ?");
            $stmtDoc->execute([$purchase_id]);
            $docPath = $stmtDoc->fetchColumn();

            if ($docPath) {
                $fullPath = __DIR__ . '/../' . $docPath;
                if (file_exists($fullPath)) {
                    @unlink($fullPath);
                }
            }

            $pdo->prepare("DELETE FROM inventory_purchases WHERE id = ?")->execute([$purchase_id]);

            echo json_encode(['success' => true, 'message' => 'Registro de compra eliminado']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
