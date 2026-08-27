<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? 'search_all';

try {
    switch ($action) {
        case 'search_all':
            $q = trim($_GET['q'] ?? $_POST['q'] ?? '');
            
            $results = [
                'commands' => [],
                'products' => [],
                'skus'     => [],
                'users'    => [],
                'activos'  => []
            ];

            // 1. Comandos y Accesos Rápidos del Sistema
            $allCommands = [
                [
                    'id'          => 'cmd_assign',
                    'title'       => 'Asignar Producto o Material a Técnico',
                    'category'    => 'Acciones de Inventario',
                    'icon'        => 'ph-user-plus',
                    'action_type' => 'trigger_assign',
                    'badge'       => 'Asignación Rápida'
                ],
                [
                    'id'          => 'cmd_history',
                    'title'       => 'Ver Historial & Trazabilidad 360°',
                    'category'    => 'Acciones de Inventario',
                    'icon'        => 'ph-clock-counter-clockwise',
                    'action_type' => 'navigate',
                    'url'         => BASE_URL . '/modules/inventario/historial',
                    'badge'       => 'Historial'
                ],
                [
                    'id'          => 'cmd_scan',
                    'title'       => 'Escanear Código con Cámara o Lector',
                    'category'    => 'Escáner',
                    'icon'        => 'ph-barcode',
                    'action_type' => 'trigger_scan',
                    'badge'       => 'Escáner'
                ],
                [
                    'id'          => 'cmd_new_product',
                    'title'       => 'Crear Nuevo Producto en Inventario',
                    'category'    => 'Acciones de Inventario',
                    'icon'        => 'ph-plus-circle',
                    'action_type' => 'navigate',
                    'url'         => BASE_URL . '/modules/inventario/?open_modal=new_product',
                    'badge'       => 'Nuevo'
                ],
                [
                    'id'          => 'cmd_new_purchase',
                    'title'       => 'Registrar Nueva Compra / Factura',
                    'category'    => 'Acciones de Inventario',
                    'icon'        => 'ph-receipt',
                    'action_type' => 'navigate',
                    'url'         => BASE_URL . '/modules/inventario/historial/?view=purchases&open=new',
                    'badge'       => 'Facturas'
                ],
                [
                    'id'          => 'cmd_activos',
                    'title'       => 'Ir a Control de Activos Empresariales',
                    'category'    => 'Navegación',
                    'icon'        => 'ph-car-profile',
                    'action_type' => 'navigate',
                    'url'         => BASE_URL . '/modules/inventario/Activos',
                    'badge'       => 'Módulo'
                ],
                [
                    'id'          => 'cmd_dashboard',
                    'title'       => 'Ir a Panel de Control / Dashboard',
                    'category'    => 'Navegación',
                    'icon'        => 'ph-squares-four',
                    'action_type' => 'navigate',
                    'url'         => BASE_URL . '/index.php',
                    'badge'       => 'Inicio'
                ],
                [
                    'id'          => 'cmd_clientes',
                    'title'       => 'Ir a Gestión de Clientes',
                    'category'    => 'Navegación',
                    'icon'        => 'ph-users',
                    'action_type' => 'navigate',
                    'url'         => BASE_URL . '/modules/clientes',
                    'badge'       => 'Módulo'
                ],
                [
                    'id'          => 'cmd_soporte',
                    'title'       => 'Ir a Mesa de Ayuda / Soporte',
                    'category'    => 'Navegación',
                    'icon'        => 'ph-headset',
                    'action_type' => 'navigate',
                    'url'         => BASE_URL . '/modules/soporte',
                    'badge'       => 'Tickets'
                ],
                [
                    'id'          => 'cmd_sistema',
                    'title'       => 'Ir a Sistema & Copias de Seguridad',
                    'category'    => 'Navegación',
                    'icon'        => 'ph-database',
                    'action_type' => 'navigate',
                    'url'         => BASE_URL . '/modules/sistema',
                    'badge'       => 'Ajustes'
                ]
            ];

            if (empty($q)) {
                $results['commands'] = array_slice($allCommands, 0, 5);
            } else {
                $qLower = mb_strtolower($q, 'UTF-8');
                foreach ($allCommands as $cmd) {
                    if (strpos(mb_strtolower($cmd['title'], 'UTF-8'), $qLower) !== false ||
                        strpos(mb_strtolower($cmd['category'], 'UTF-8'), $qLower) !== false) {
                        $results['commands'][] = $cmd;
                    }
                }
            }

            if (!empty($q)) {
                $paramLike = "%$q%";

                // 2. Productos (columnas reales: name, total_quantity, costo_producto, product_image, master_sku)
                try {
                    $stmtProd = $pdo->prepare("
                        SELECT p.id, p.name, p.master_sku, p.total_quantity, p.product_image, p.costo_producto,
                               p.product_type, p.is_bulk, p.unit_type,
                               c.name as category_name,
                               (SELECT COUNT(*) FROM inventory_skus s WHERE s.product_id = p.id AND s.status = 'disponible' AND s.is_deleted = 0) as skus_disponibles,
                               (SELECT COUNT(*) FROM inventory_skus s WHERE s.product_id = p.id AND s.status = 'instalado' AND s.is_deleted = 0) as skus_instalados,
                               (SELECT COUNT(*) FROM inventory_skus s WHERE s.product_id = p.id AND s.is_deleted = 0) as skus_total
                        FROM inventory_products p
                        LEFT JOIN inventory_categories c ON p.category_id = c.id
                        WHERE p.is_deleted = 0
                          AND (p.name LIKE ? OR p.master_sku LIKE ? OR c.name LIKE ?)
                        ORDER BY p.name ASC
                        LIMIT 10
                    ");
                    $stmtProd->execute([$paramLike, $paramLike, $paramLike]);
                    $results['products'] = $stmtProd->fetchAll(PDO::FETCH_ASSOC);
                } catch (Exception $e) {
                    $results['products'] = [];
                }

                // 3. SKUs / Series Específicas
                try {
                    $stmtSkus = $pdo->prepare("
                        SELECT s.id, s.sku_code, s.status, s.assigned_to, s.product_id, s.is_epp,
                               p.name as product_name, p.product_image,
                               u.name as assigned_user_name
                        FROM inventory_skus s
                        JOIN inventory_products p ON s.product_id = p.id
                        LEFT JOIN users u ON s.assigned_to = u.id
                        WHERE s.is_deleted = 0 AND s.sku_code LIKE ?
                        ORDER BY s.id DESC
                        LIMIT 10
                    ");
                    $stmtSkus->execute([$paramLike]);
                    $results['skus'] = $stmtSkus->fetchAll(PDO::FETCH_ASSOC);
                } catch (Exception $e) {
                    $results['skus'] = [];
                }

                // 4. Usuarios / Técnicos
                try {
                    $stmtUsers = $pdo->prepare("
                        SELECT u.id, u.name, u.email, u.role, u.profile_picture,
                               (SELECT COUNT(*) FROM inventory_skus s WHERE s.assigned_to = u.id AND s.is_deleted = 0) as total_skus_asignados
                        FROM users u
                        WHERE u.name LIKE ? OR u.email LIKE ? OR u.role LIKE ?
                        ORDER BY u.name ASC
                        LIMIT 6
                    ");
                    $stmtUsers->execute([$paramLike, $paramLike, $paramLike]);
                    $results['users'] = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);
                } catch (Exception $e) {
                    $results['users'] = [];
                }

                // 5. Activos Empresariales / Vehículos
                try {
                    $stmtAct = $pdo->prepare("
                        SELECT v.id, v.tipo, v.categoria, v.nombre, v.placa, v.codigo_identificador, v.marca, v.modelo, v.estado, v.responsable_nombre,
                               (SELECT url_imagen FROM activos_imagenes i WHERE i.vehiculo_id = v.id ORDER BY fecha_subida ASC LIMIT 1) as primera_foto
                        FROM activos_vehiculos v
                        WHERE (v.placa LIKE ? OR v.codigo_identificador LIKE ? OR v.nombre LIKE ? OR v.marca LIKE ? OR v.modelo LIKE ?)
                          AND v.estado != 'eliminado'
                        LIMIT 6
                    ");
                    $stmtAct->execute([$paramLike, $paramLike, $paramLike, $paramLike, $paramLike]);
                    $results['activos'] = $stmtAct->fetchAll(PDO::FETCH_ASSOC);
                } catch (Exception $e) {
                    $results['activos'] = [];
                }
            }

            echo json_encode([
                'success' => true,
                'data'    => $results,
                'query'   => $q
            ]);
            break;

        case 'get_product_stock_detail':
            $productId = (int)($_GET['product_id'] ?? 0);
            if (!$productId) throw new Exception('ID de producto requerido.');

            // Info del producto
            $stmtP = $pdo->prepare("
                SELECT p.id, p.name, p.total_quantity, p.product_type, p.is_bulk, p.unit_type, p.product_image,
                       c.name as category_name
                FROM inventory_products p
                LEFT JOIN inventory_categories c ON p.category_id = c.id
                WHERE p.id = ?
            ");
            $stmtP->execute([$productId]);
            $product = $stmtP->fetch(PDO::FETCH_ASSOC);
            if (!$product) throw new Exception('Producto no encontrado.');

            // Conteos de SKUs
            $stmtCounts = $pdo->prepare("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'disponible' AND (assigned_to IS NULL OR assigned_to = 0) THEN 1 ELSE 0 END) as disponibles,
                    SUM(CASE WHEN assigned_to IS NOT NULL AND assigned_to > 0 THEN 1 ELSE 0 END) as asignados,
                    SUM(CASE WHEN status = 'instalado' THEN 1 ELSE 0 END) as instalados,
                    SUM(CASE WHEN status = 'malogrado' THEN 1 ELSE 0 END) as malogrados,
                    SUM(CASE WHEN status = 'observacion' THEN 1 ELSE 0 END) as observados
                FROM inventory_skus
                WHERE product_id = ? AND is_deleted = 0
            ");
            $stmtCounts->execute([$productId]);
            $counts = $stmtCounts->fetch(PDO::FETCH_ASSOC);

            // Desglose por usuario asignado (SKUs serializados)
            $stmtByUser = $pdo->prepare("
                SELECT u.id as user_id, u.name as user_name, u.profile_picture,
                       COUNT(s.id) as cantidad,
                       GROUP_CONCAT(s.sku_code ORDER BY s.sku_code SEPARATOR ', ') as skus
                FROM inventory_skus s
                JOIN users u ON s.assigned_to = u.id
                WHERE s.product_id = ? AND s.is_deleted = 0 AND s.assigned_to IS NOT NULL AND s.assigned_to > 0
                GROUP BY u.id, u.name, u.profile_picture
                ORDER BY cantidad DESC
            ");
            $stmtByUser->execute([$productId]);
            $byUser = $stmtByUser->fetchAll(PDO::FETCH_ASSOC);

            // Si es producto a granel o no tiene SKUs individuales, buscar en inventory_user_stock
            if (empty($byUser)) {
                try {
                    $stmtBulkUsers = $pdo->prepare("
                        SELECT u.id as user_id, u.name as user_name, u.profile_picture,
                               us.quantity as cantidad,
                               'Granel' as skus
                        FROM inventory_user_stock us
                        JOIN users u ON us.user_id = u.id
                        WHERE us.product_id = ? AND us.quantity > 0
                        ORDER BY us.quantity DESC
                    ");
                    $stmtBulkUsers->execute([$productId]);
                    $bulkUsers = $stmtBulkUsers->fetchAll(PDO::FETCH_ASSOC);
                    if (!empty($bulkUsers)) {
                        $byUser = $bulkUsers;
                        $totalAssignedBulk = array_sum(array_column($bulkUsers, 'cantidad'));
                        $counts['asignados'] = $totalAssignedBulk;
                        $counts['disponibles'] = $product['total_quantity'];
                        $counts['total'] = $product['total_quantity'] + $totalAssignedBulk;
                    }
                } catch (Exception $e) {}
            }

            echo json_encode([
                'success' => true,
                'product' => $product,
                'counts'  => $counts,
                'by_user' => $byUser
            ]);
            break;

        case 'get_users':
            $stmt = $pdo->query("
                SELECT id, name, email, role, profile_picture 
                FROM users 
                WHERE role != 'Cliente' 
                ORDER BY name ASC
            ");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $users]);
            break;

        case 'get_assignable_items':
            // Obtener lista de productos con stock disponible y SKUs disponibles
            $stmtProds = $pdo->query("
                SELECT p.id, p.name, p.total_quantity, c.name as category_name,
                       (SELECT COUNT(*) FROM inventory_skus s WHERE s.product_id = p.id AND s.status = 'disponible' AND s.assigned_to IS NULL AND s.is_deleted = 0) as available_skus_count
                FROM inventory_products p
                LEFT JOIN inventory_categories c ON p.category_id = c.id
                WHERE p.is_deleted = 0
                ORDER BY p.name ASC
            ");
            $prods = $stmtProds->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success'   => true,
                'products'  => $prods
            ]);
            break;

        case 'quick_assign':
            $userId = (int)($_POST['user_id'] ?? 0);
            $productId = (int)($_POST['product_id'] ?? 0);
            $skuId = (int)($_POST['sku_id'] ?? 0);
            $skuCode = trim($_POST['sku_code'] ?? '');
            $quantity = max(1, (float)($_POST['quantity'] ?? 1));
            $isEpp = !empty($_POST['is_epp']) ? 1 : 0;
            $notes = trim($_POST['notes'] ?? 'Asignado desde Command Palette');

            if (!$userId) {
                throw new Exception('Debes seleccionar el técnico o usuario de destino.');
            }

            // Obtener datos del usuario
            $stmtU = $pdo->prepare("SELECT name FROM users WHERE id = ?");
            $stmtU->execute([$userId]);
            $userName = $stmtU->fetchColumn();
            if (!$userName) throw new Exception('Usuario no encontrado.');

            $assignedById = $_SESSION['user_id'];
            $assignedByName = $_SESSION['user_name'] ?? 'Admin';

            // 1. Asignar por SKU específico
            if ($skuId > 0 || !empty($skuCode)) {
                $stmtSku = $skuId > 0 
                    ? $pdo->prepare("SELECT s.*, p.name as product_name FROM inventory_skus s JOIN inventory_products p ON s.product_id = p.id WHERE s.id = ?")
                    : $pdo->prepare("SELECT s.*, p.name as product_name FROM inventory_skus s JOIN inventory_products p ON s.product_id = p.id WHERE s.sku_code = ?");
                
                $stmtSku->execute([$skuId > 0 ? $skuId : $skuCode]);
                $sku = $stmtSku->fetch(PDO::FETCH_ASSOC);

                if (!$sku) {
                    throw new Exception('El código SKU / Serie no fue encontrado.');
                }

                // Actualizar SKU
                $upd = $pdo->prepare("UPDATE inventory_skus SET assigned_to = ?, is_epp = ? WHERE id = ?");
                $upd->execute([$userId, $isEpp, $sku['id']]);

                // Registrar en assignment_log
                try {
                    $log = $pdo->prepare("
                        INSERT INTO inventory_assignment_log 
                        (sku_id, product_id, sku_code, product_name, assigned_to, assigned_to_name, assigned_by, assigned_by_name, quantity, is_epp, action, notes)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, 'assign', ?)
                    ");
                    $log->execute([$sku['id'], $sku['product_id'], $sku['sku_code'], $sku['product_name'], $userId, $userName, $assignedById, $assignedByName, $isEpp, $notes]);
                } catch (Exception $e) {}

                echo json_encode([
                    'success' => true,
                    'message' => "SKU {$sku['sku_code']} asignado exitosamente a {$userName}."
                ]);
                break;
            }

            // 2. Asignar producto a granel / cantidad
            if ($productId > 0) {
                $stmtP = $pdo->prepare("SELECT name, total_quantity FROM inventory_products WHERE id = ?");
                $stmtP->execute([$productId]);
                $prod = $stmtP->fetch(PDO::FETCH_ASSOC);

                if (!$prod) throw new Exception('Producto no encontrado.');

                // Asignar en inventory_user_stock
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS inventory_user_stock (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        product_id INT NOT NULL,
                        quantity DECIMAL(10,2) NOT NULL DEFAULT 0,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        UNIQUE KEY user_prod (user_id, product_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                ");

                $stmtStock = $pdo->prepare("
                    INSERT INTO inventory_user_stock (user_id, product_id, quantity) 
                    VALUES (?, ?, ?) 
                    ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)
                ");
                $stmtStock->execute([$userId, $productId, $quantity]);

                // Descontar del inventario central
                $pdo->prepare("UPDATE inventory_products SET total_quantity = GREATEST(0, total_quantity - ?) WHERE id = ?")
                    ->execute([$quantity, $productId]);

                // Registrar log
                try {
                    $log = $pdo->prepare("
                        INSERT INTO inventory_assignment_log 
                        (sku_id, product_id, sku_code, product_name, assigned_to, assigned_to_name, assigned_by, assigned_by_name, quantity, is_epp, action, notes)
                        VALUES (?, ?, 'GRANEL', ?, ?, ?, ?, ?, ?, ?, 'assign', ?)
                    ");
                    $log->execute([null, $productId, $prod['name'], $userId, $userName, $assignedById, $assignedByName, $quantity, $isEpp, $notes]);
                } catch (Exception $e) {}

                echo json_encode([
                    'success' => true,
                    'message' => "Se asignaron {$quantity} unidad(es) de '{$prod['name']}' a {$userName}."
                ]);
                break;
            }

            throw new Exception('Debes indicar un producto o código SKU.');

        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
