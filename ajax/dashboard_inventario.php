<?php
require_once '../config/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'get_dashboard_data') {
    $startDate = $_POST['start_date'] ?? date('Y-m-01');
    $endDate = $_POST['end_date'] ?? date('Y-m-d');

    $endDateFull = $endDate . ' 23:59:59';
    $startDateFull = $startDate . ' 00:00:00';

    $response = [
        'success' => true,
        'data' => [
            'most_used' => [],
            'least_used' => [],
            'lowest_stock' => [],
            'status_stats' => [],
            'category_stats' => [],
            'top_technicians' => [],
            'returns_over_time' => [],
            'value_evolution' => [],
            'metrics' => []
        ]
    ];

    try {
        // ── 1. Productos Más Usados / Asignados (en el rango de fechas) ──
        $stmtMostUsed = $pdo->prepare("
            SELECT product_id, product_name, SUM(quantity) as total_used 
            FROM inventory_assignment_log 
            WHERE action = 'assign' 
              AND created_at >= ? AND created_at <= ? 
            GROUP BY product_id, product_name
            ORDER BY total_used DESC 
            LIMIT 5
        ");
        $stmtMostUsed->execute([$startDateFull, $endDateFull]);
        $mostUsed = $stmtMostUsed->fetchAll(PDO::FETCH_ASSOC);

        // Fallback si no hay movimientos en ese rango específico
        $stmtMostUsedAll = $pdo->query("
            SELECT product_id, product_name, SUM(quantity) as total_used 
            FROM inventory_assignment_log 
            WHERE action = 'assign' 
            GROUP BY product_id, product_name
            ORDER BY total_used DESC 
            LIMIT 5
        ");
        $mostUsedAll = $stmtMostUsedAll->fetchAll(PDO::FETCH_ASSOC);

        $response['data']['most_used'] = $mostUsed;
        $response['data']['most_used_all_time'] = $mostUsedAll;

        // ── 2. Top Técnicos con más Asignaciones en el rango ──
        $stmtTopTechs = $pdo->prepare("
            SELECT assigned_to_name as technician_name, SUM(quantity) as total_assigned 
            FROM inventory_assignment_log 
            WHERE action = 'assign' 
              AND created_at >= ? AND created_at <= ? 
            GROUP BY assigned_to_name 
            ORDER BY total_assigned DESC 
            LIMIT 5
        ");
        $stmtTopTechs->execute([$startDateFull, $endDateFull]);
        $topTechs = $stmtTopTechs->fetchAll(PDO::FETCH_ASSOC);
        if (empty($topTechs)) {
            $topTechs = $pdo->query("
                SELECT assigned_to_name as technician_name, SUM(quantity) as total_assigned 
                FROM inventory_assignment_log 
                WHERE action = 'assign' 
                GROUP BY assigned_to_name 
                ORDER BY total_assigned DESC 
                LIMIT 5
            ")->fetchAll(PDO::FETCH_ASSOC);
        }
        $response['data']['top_technicians'] = $topTechs;

        // ── 3. Productos con Menor Stock (Alertas de Reposición) ──
        // Excluyendo filas padre con stock 0 de productos agrupados si tienen variantes
        $stmtLowStock = $pdo->query("
            SELECT p.id, p.name as product_name, p.total_quantity as stock, p.stock_minimo, p.stock_critico, p.product_type,
                   p.variant_attributes, p.variant_size, p.variant_brand,
                   c.name as category_name
            FROM inventory_products p
            LEFT JOIN inventory_categories c ON p.category_id = c.id
            WHERE p.is_deleted = 0 
              AND (p.product_type != 'agrupado' OR (p.parent_product_id IS NOT NULL))
            ORDER BY (p.total_quantity <= p.stock_minimo) DESC, p.total_quantity ASC 
            LIMIT 6
        ");
        $lowStockRows = $stmtLowStock->fetchAll(PDO::FETCH_ASSOC);
        foreach ($lowStockRows as &$ls) {
            $attrs = [];
            if (!empty($ls['variant_attributes'])) {
                $dec = json_decode($ls['variant_attributes'], true);
                if (is_array($dec)) {
                    foreach ($dec as $k => $v) {
                        if (!empty($v)) $attrs[] = "$k: $v";
                    }
                }
            } elseif (!empty($ls['variant_size'])) {
                $attrs[] = $ls['variant_size'];
            }
            if (!empty($attrs)) {
                $ls['display_name'] = $ls['product_name'] . ' (' . implode(', ', array_slice($attrs, 0, 2)) . ')';
            } else {
                $ls['display_name'] = $ls['product_name'];
            }
        }
        $response['data']['lowest_stock'] = $lowStockRows;

        // ── 4. Distribución de Stock por Categorías ──
        $stmtCatStats = $pdo->query("
            SELECT COALESCE(c.name, 'Sin Categoría') as category_name,
                   COALESCE(SUM(p.total_quantity), 0) as total_stock,
                   COUNT(p.id) as product_count
            FROM inventory_products p
            LEFT JOIN inventory_categories c ON p.category_id = c.id
            WHERE p.is_deleted = 0 
              AND (p.product_type != 'agrupado' OR p.parent_product_id IS NOT NULL)
            GROUP BY c.id, c.name
            ORDER BY total_stock DESC
            LIMIT 8
        ");
        $response['data']['category_stats'] = $stmtCatStats->fetchAll(PDO::FETCH_ASSOC);

        // ── 5. Métricas de Stock en Vivo & Estados Consolidados ──
        // A. Total Productos en Catálogo
        $countProductos = $pdo->query("
            SELECT COUNT(*) FROM inventory_products 
            WHERE is_deleted = 0 AND parent_product_id IS NULL
        ")->fetchColumn();

        // B. Total Unidades Físicas Registradas
        $totalUnidades = $pdo->query("
            SELECT COALESCE(SUM(total_quantity), 0) FROM inventory_products 
            WHERE is_deleted = 0 AND (parent_product_id IS NOT NULL OR product_type != 'agrupado')
        ")->fetchColumn();

        // C. SKUs por Estado en Vivo
        $skuCountsStmt = $pdo->query("
            SELECT status, COUNT(*) as count 
            FROM inventory_skus 
            WHERE is_deleted = 0 
            GROUP BY status
        ");
        $skuCounts = $skuCountsStmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // D. Asignados a Técnicos con SKU (status disponible pero assigned_to no nulo)
        $skusAssignedToTechs = $pdo->query("
            SELECT COUNT(*) FROM inventory_skus 
            WHERE assigned_to IS NOT NULL AND status = 'disponible' AND is_deleted = 0
        ")->fetchColumn();

        // E. A Granel Asignados a Técnicos
        $bulkAssignedToTechs = $pdo->query("
            SELECT COALESCE(SUM(quantity), 0) FROM inventory_user_stock WHERE quantity > 0
        ")->fetchColumn();

        // F. Total a Granel Registrado
        $bulkTotal = $pdo->query("
            SELECT COALESCE(SUM(total_quantity), 0) FROM inventory_products 
            WHERE is_bulk = 1 AND is_deleted = 0
        ")->fetchColumn();

        // Stock disponible en almacén (libre de asignación)
        $skusDisponiblesLibres = max(0, intval($skuCounts['disponible'] ?? 0) - intval($skusAssignedToTechs));
        $bulkDisponiblesLibres = max(0, floatval($bulkTotal) - floatval($bulkAssignedToTechs));
        $disponiblesEnAlmacen = $skusDisponiblesLibres + $bulkDisponiblesLibres;

        // Total en Posesión de Personal / EPP
        $totalEnPersonal = intval($skusAssignedToTechs) + floatval($bulkAssignedToTechs);

        // Instalados en Clientes (desde SKUs instalados + actas)
        $instaladosSkus = intval($skuCounts['instalado'] ?? 0);
        $actasEquiposCount = $pdo->query("SELECT COUNT(*) FROM actas_equipos")->fetchColumn();
        $totalInstalados = max($instaladosSkus, intval($actasEquiposCount));

        // Malogrados y Observados
        $totalMalogrados = intval($skuCounts['malogrado'] ?? 0);
        $totalObservacion = intval($skuCounts['observacion'] ?? 0);
        $totalReparado = intval($skuCounts['reparado'] ?? 0);

        // Desglose de Estados para el Donut Chart
        $statusStats = [
            'disponible' => $disponiblesEnAlmacen,
            'personal_epp' => $totalEnPersonal,
            'instalado' => $totalInstalados,
            'malogrado' => $totalMalogrados,
            'observacion' => $totalObservacion,
            'reparado' => $totalReparado
        ];
        $response['data']['status_stats'] = $statusStats;

        // F. Alertas de Stock Bajo / Por Agotarse
        $countLowStock = $pdo->query("
            SELECT COUNT(*) FROM inventory_products 
            WHERE is_deleted = 0 
              AND (parent_product_id IS NOT NULL OR product_type != 'agrupado')
              AND total_quantity <= stock_minimo
        ")->fetchColumn();

        // G. Valor Total del Inventario & Capital Inmovilizado
        $valorTotal = $pdo->query("
            SELECT COALESCE(SUM(total_quantity * costo_producto), 0) 
            FROM inventory_products 
            WHERE is_deleted = 0 AND (parent_product_id IS NOT NULL OR product_type != 'agrupado')
        ")->fetchColumn();

        $capitalInmovilizado = $pdo->query("
            SELECT COALESCE(SUM(total_quantity * costo_producto), 0) 
            FROM inventory_products 
            WHERE is_deleted = 0 
              AND (parent_product_id IS NOT NULL OR product_type != 'agrupado')
              AND total_quantity <= stock_minimo
        ")->fetchColumn();

        // ── 6. Devoluciones & Retornos en el Rango ──
        $stmtReturns = $pdo->prepare("
            SELECT DATE(created_at) as return_date, SUM(quantity) as total_returned 
            FROM inventory_assignment_log 
            WHERE action IN ('unassign', 'return') 
              AND created_at >= ? AND created_at <= ? 
            GROUP BY return_date 
            ORDER BY return_date ASC
        ");
        $stmtReturns->execute([$startDateFull, $endDateFull]);
        $response['data']['returns_over_time'] = $stmtReturns->fetchAll(PDO::FETCH_ASSOC);

        // ── 7. Compras e Inversión Histórica ──
        $stmtPurchasesEvol = $pdo->prepare("
            SELECT DATE(purchase_date) as purchase_day, SUM(total_amount) as total_spent, COUNT(*) as invoice_count
            FROM inventory_purchases
            WHERE purchase_date >= ? AND purchase_date <= ?
            GROUP BY purchase_day
            ORDER BY purchase_day ASC
        ");
        $stmtPurchasesEvol->execute([$startDateFull, $endDateFull]);
        $response['data']['purchases_evolution'] = $stmtPurchasesEvol->fetchAll(PDO::FETCH_ASSOC);

        // ── 8. Empaquetar Métricas Finales ──
        $response['data']['metrics'] = [
            'productos_registrados' => [
                'current' => intval($countProductos),
                'trend' => 0
            ],
            'total_unidades' => [
                'current' => floatval($totalUnidades),
                'trend' => 0
            ],
            'disponibles' => [
                'current' => floatval($disponiblesEnAlmacen),
                'trend' => 0
            ],
            'instalados' => [
                'current' => intval($totalInstalados),
                'trend' => 0
            ],
            'asignado_personal' => [
                'current' => floatval($totalEnPersonal),
                'trend' => 0
            ],
            'por_agotarse' => [
                'current' => intval($countLowStock),
                'trend' => 0
            ],
            'valor_total' => [
                'current' => floatval($valorTotal)
            ],
            'capital_inmovilizado' => [
                'current' => floatval($capitalInmovilizado)
            ]
        ];

    } catch (Exception $e) {
        $response['success'] = false;
        $response['message'] = $e->getMessage();
    }

    echo json_encode($response);
    exit;
}

if ($action === 'get_drilldown_data') {
    $productName = $_POST['product_name'] ?? '';
    $stmt = $pdo->prepare("
        SELECT action, assigned_to_name, quantity, created_at, notes 
        FROM inventory_assignment_log 
        WHERE product_name = ? 
        ORDER BY created_at DESC LIMIT 20
    ");
    $stmt->execute([$productName]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmtPhoto = $pdo->prepare("SELECT product_image FROM inventory_products WHERE name = ? AND product_image IS NOT NULL LIMIT 1");
    $stmtPhoto->execute([$productName]);
    $photo = $stmtPhoto->fetchColumn();
    
    echo json_encode(['success' => true, 'data' => $data, 'photo' => $photo]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Acción no válida']);
