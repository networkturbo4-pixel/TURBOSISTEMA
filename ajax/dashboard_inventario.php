<?php
require_once '../config/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'get_dashboard_data') {
    $startDate = $_POST['start_date'] ?? date('Y-m-01'); // Primer día del mes actual por defecto
    $endDate = $_POST['end_date'] ?? date('Y-m-d'); // Día de hoy por defecto

    // Asegurarse de que endDate incluya hasta el final del día
    $endDateFull = $endDate . ' 23:59:59';
    $startDateFull = $startDate . ' 00:00:00';

    $response = [
        'success' => true,
        'data' => [
            'most_used' => [],
            'least_used' => [],
            'lowest_stock' => [],
            'status_stats' => []
        ]
    ];

    try {
        // 1. Productos Más Usados (Asignaciones)
        $stmtMostUsed = $pdo->prepare("
            SELECT product_id, product_name, SUM(quantity) as total_used 
            FROM inventory_assignment_log 
            WHERE action = 'assign' 
              AND created_at >= ? AND created_at <= ? 
            GROUP BY product_id 
            ORDER BY total_used DESC 
            LIMIT 5
        ");
        $stmtMostUsed->execute([$startDateFull, $endDateFull]);
        $response['data']['most_used'] = $stmtMostUsed->fetchAll(PDO::FETCH_ASSOC);

        // 2. Productos Menos Usados (Asignaciones)
        // Solo cuenta los que tuvieron al menos 1 movimiento, o podemos hacer un LEFT JOIN, pero lo haremos con los que tuvieron movimiento para no saturar con productos sin usar.
        $stmtLeastUsed = $pdo->prepare("
            SELECT product_id, product_name, SUM(quantity) as total_used 
            FROM inventory_assignment_log 
            WHERE action = 'assign' 
              AND created_at >= ? AND created_at <= ? 
            GROUP BY product_id 
            ORDER BY total_used ASC 
            LIMIT 5
        ");
        $stmtLeastUsed->execute([$startDateFull, $endDateFull]);
        $response['data']['least_used'] = $stmtLeastUsed->fetchAll(PDO::FETCH_ASSOC);

        // 3. Productos con menos stock (Este es un dato global actual, no tiene sentido aplicarle fechas pasadas para "menos stock actual", pero podemos usar stock actual)
        $stmtLowStock = $pdo->prepare("
            SELECT name as product_name, total_quantity as stock, stock_minimo 
            FROM inventory_products 
            WHERE is_deleted = 0 
            ORDER BY total_quantity ASC 
            LIMIT 5
        ");
        $stmtLowStock->execute();
        $response['data']['lowest_stock'] = $stmtLowStock->fetchAll(PDO::FETCH_ASSOC);

        // 4. Estados Históricos (Cambios de estado en el rango de fechas)
        // Contamos cuántas veces se marcó un producto como malogrado, observacion, etc. en este periodo.
        $stmtStatus = $pdo->prepare("
            SELECT tipo as status, COUNT(*) as count 
            FROM inventory_entries 
            WHERE created_at >= ? AND created_at <= ?
              AND tipo IN ('disponible', 'instalado', 'malogrado', 'reparado', 'observacion')
            GROUP BY tipo
        ");
        $stmtStatus->execute([$startDateFull, $endDateFull]);
        $statusCounts = $stmtStatus->fetchAll(PDO::FETCH_ASSOC);
        
        // Mapear los resultados
        $stats = [
            'disponible' => 0,
            'instalado' => 0,
            'malogrado' => 0,
            'reparado' => 0,
            'observacion' => 0
        ];
        
        foreach ($statusCounts as $row) {
            $stats[$row['status']] = intval($row['count']);
        }

        // Además, si queremos ver las instalaciones desde actas_materiales (que es otra fuente válida)
        // pero como inventory_entries registra 'instalado', podemos basarnos en eso o sumar ambos.
        // Nos basaremos en inventory_entries para consistencia.

        // 5. Métricas Globales en el rango de fechas
        // Productos registrados en el rango
        $productosRegistrados = $pdo->prepare("SELECT COUNT(*) FROM inventory_products WHERE created_at >= ? AND created_at <= ? AND is_deleted = 0");
        $productosRegistrados->execute([$startDateFull, $endDateFull]);
        $countProductos = $productosRegistrados->fetchColumn();

        // SKUs agregados en el rango (Total Unidades)
        $skusAgregados = $pdo->prepare("SELECT COUNT(*) FROM inventory_skus WHERE created_at >= ? AND created_at <= ? AND is_deleted = 0");
        $skusAgregados->execute([$startDateFull, $endDateFull]);
        $countUnidades = $skusAgregados->fetchColumn();

        // Unidades en Bulk agregadas en el rango (Usaremos una aproximación: cantidad actual de productos bulk creados en ese rango, o podríamos buscar en history si existiera)
        $bulkAgregados = $pdo->prepare("SELECT COALESCE(SUM(total_quantity), 0) FROM inventory_products WHERE is_bulk = 1 AND created_at >= ? AND created_at <= ? AND is_deleted = 0");
        $bulkAgregados->execute([$startDateFull, $endDateFull]);
        $countBulk = $bulkAgregados->fetchColumn();

        $totalUnidades = intval($countUnidades) + intval($countBulk);

        // Para Disponible, Instalado, Por Agotarse: usaremos los stats históricos que ya calculamos (inventory_entries) 
        // o si es la métrica actual, pero el usuario pidió "filtrado por fechas".
        // Usaremos $stats['disponible'] y $stats['instalado'] como los movimientos en ese rango.
        
        // 5. Métricas Globales y Comparativas
        
        // Determinar periodo anterior
        $startDt = new DateTime($startDate);
        $endDt = new DateTime($endDate);
        $interval = $startDt->diff($endDt);
        $days = $interval->days + 1;
        
        $prevEndDateObj = clone $startDt;
        $prevEndDateObj->modify('-1 day');
        $prevStartDateObj = clone $prevEndDateObj;
        $prevStartDateObj->modify('-' . ($days - 1) . ' days');
        
        $prevStartDateFull = $prevStartDateObj->format('Y-m-d 00:00:00');
        $prevEndDateFull = $prevEndDateObj->format('Y-m-d 23:59:59');

        // Productos registrados
        $countProductos = $pdo->query("SELECT COUNT(*) FROM inventory_products WHERE created_at >= '$startDateFull' AND created_at <= '$endDateFull' AND is_deleted = 0")->fetchColumn();
        $countProductosPrev = $pdo->query("SELECT COUNT(*) FROM inventory_products WHERE created_at >= '$prevStartDateFull' AND created_at <= '$prevEndDateFull' AND is_deleted = 0")->fetchColumn();

        // Total Unidades
        $countUnidades = $pdo->query("SELECT COUNT(*) FROM inventory_skus WHERE created_at >= '$startDateFull' AND created_at <= '$endDateFull' AND is_deleted = 0")->fetchColumn();
        $countBulk = $pdo->query("SELECT COALESCE(SUM(total_quantity), 0) FROM inventory_products WHERE is_bulk = 1 AND created_at >= '$startDateFull' AND created_at <= '$endDateFull' AND is_deleted = 0")->fetchColumn();
        $totalUnidades = intval($countUnidades) + intval($countBulk);
        
        $countUnidadesPrev = $pdo->query("SELECT COUNT(*) FROM inventory_skus WHERE created_at >= '$prevStartDateFull' AND created_at <= '$prevEndDateFull' AND is_deleted = 0")->fetchColumn();
        $countBulkPrev = $pdo->query("SELECT COALESCE(SUM(total_quantity), 0) FROM inventory_products WHERE is_bulk = 1 AND created_at >= '$prevStartDateFull' AND created_at <= '$prevEndDateFull' AND is_deleted = 0")->fetchColumn();
        $totalUnidadesPrev = intval($countUnidadesPrev) + intval($countBulkPrev);

        // Valor Total (Cost * Quantity)
        $valorTotal = $pdo->query("SELECT COALESCE(SUM(total_quantity * costo_producto), 0) FROM inventory_products WHERE is_deleted = 0")->fetchColumn();
        $capitalInmovilizado = $pdo->query("SELECT COALESCE(SUM(total_quantity * costo_producto), 0) FROM inventory_products WHERE is_deleted = 0 AND total_quantity <= stock_minimo")->fetchColumn();

        // 6. Nuevos Gráficos Operativos
        // Top 5 Técnicos
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
        $response['data']['top_technicians'] = $stmtTopTechs->fetchAll(PDO::FETCH_ASSOC);

        // Tasa de Retorno (Devoluciones)
        $stmtReturns = $pdo->prepare("
            SELECT DATE(created_at) as return_date, SUM(quantity) as total_returned 
            FROM inventory_assignment_log 
            WHERE action = 'return' 
              AND created_at >= ? AND created_at <= ? 
            GROUP BY return_date 
            ORDER BY return_date ASC
        ");
        $stmtReturns->execute([$startDateFull, $endDateFull]);
        $response['data']['returns_over_time'] = $stmtReturns->fetchAll(PDO::FETCH_ASSOC);

        // 7. Evolución del Valor del Inventario (Aproximación mensual/semanal en base a entradas/salidas * costo_producto actual)
        // Para simplificar, obtenemos los movimientos diarios de stock y estimamos el cambio en valor.
        $stmtValueEvol = $pdo->prepare("
            SELECT DATE(created_at) as date, 
                   SUM(CASE WHEN tipo IN ('entrada', 'devolucion', 'disponible') THEN 1 ELSE -1 END) * (SELECT AVG(costo_producto) FROM inventory_products) as value_change
            FROM inventory_entries 
            WHERE created_at >= ? AND created_at <= ?
            GROUP BY date
            ORDER BY date ASC
        ");
        $stmtValueEvol->execute([$startDateFull, $endDateFull]);
        $response['data']['value_evolution'] = $stmtValueEvol->fetchAll(PDO::FETCH_ASSOC);

        $response['data']['status_stats'] = $stats;
        
        $calcTrend = function($current, $prev) {
            if ($prev == 0) return $current > 0 ? 100 : 0;
            return round((($current - $prev) / $prev) * 100, 1);
        };

        // Stats históricos de devoluciones en el rango (para comparar)
        $statsPrev = $pdo->prepare("SELECT tipo as status, COUNT(*) as count FROM inventory_entries WHERE created_at >= ? AND created_at <= ? GROUP BY tipo");
        $statsPrev->execute([$prevStartDateFull, $prevEndDateFull]);
        $sp = ['disponible'=>0, 'instalado'=>0];
        foreach ($statsPrev->fetchAll() as $r) { $sp[$r['status']] = $r['count']; }

        $response['data']['metrics'] = [
            'productos_registrados' => [
                'current' => intval($countProductos),
                'trend' => $calcTrend(intval($countProductos), intval($countProductosPrev))
            ],
            'total_unidades' => [
                'current' => $totalUnidades,
                'trend' => $calcTrend($totalUnidades, $totalUnidadesPrev)
            ],
            'disponibles' => [
                'current' => $stats['disponible'] ?? 0,
                'trend' => $calcTrend($stats['disponible'] ?? 0, $sp['disponible'])
            ],
            'instalados' => [
                'current' => $stats['instalado'] ?? 0,
                'trend' => $calcTrend($stats['instalado'] ?? 0, $sp['instalado'])
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
    // Obtener últimos movimientos para ese producto
    $stmt = $pdo->prepare("
        SELECT action, assigned_to_name, quantity, created_at, notes 
        FROM inventory_assignment_log 
        WHERE product_name = ? 
        ORDER BY created_at DESC LIMIT 20
    ");
    $stmt->execute([$productName]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Y la foto
    $stmtPhoto = $pdo->prepare("SELECT product_image FROM inventory_products WHERE name = ? LIMIT 1");
    $stmtPhoto->execute([$productName]);
    $photo = $stmtPhoto->fetchColumn();
    
    echo json_encode(['success' => true, 'data' => $data, 'photo' => $photo]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Acción no válida']);
