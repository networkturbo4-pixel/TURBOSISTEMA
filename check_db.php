<?php
/**
 * DIAGNÓSTICO DE BASE DE DATOS
 * Verifica qué columnas y tablas faltan
 */
session_start();
$isLocal = isset($_SERVER['HTTP_HOST']) && in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1']);

if ($isLocal) {
    $host = 'localhost'; $dbname = 'turbosaas_db'; $user = 'root'; $pass = '';
} else {
    require_once __DIR__ . '/config/env.php';
    $host = defined('DB_HOST') ? DB_HOST : 'localhost';
    $dbname = defined('DB_NAME') ? DB_NAME : '';
    $user = defined('DB_USER') ? DB_USER : '';
    $pass = defined('DB_PASS') ? DB_PASS : '';
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("<h2 style='color:red'>Error conexión: " . $e->getMessage() . "</h2>");
}

echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Diagnóstico DB</title>';
echo '<style>body{background:#111;color:#eee;font-family:monospace;padding:30px;max-width:1000px;margin:0 auto}';
echo '.ok{color:#10b981}.miss{color:#ef4444}.warn{color:#f59e0b}h1{color:#f97316}h2{color:#818cf8;margin-top:30px}';
echo 'table{border-collapse:collapse;width:100%;margin:10px 0}td,th{padding:8px 12px;border:1px solid #333;text-align:left}';
echo 'th{background:#1a1a2e;color:#f97316}.btn{display:inline-block;padding:12px 24px;background:#f97316;color:#fff;text-decoration:none;border-radius:8px;font-weight:bold;margin-top:20px}</style></head><body>';

echo '<h1>🔍 Diagnóstico de Base de Datos</h1>';

// Check required columns
$required = [
    'inventory_products' => ['id','name','description','category_id','total_quantity','stock_minimo','stock_critico',
        'custom_columns','created_at','is_bulk','unit_type','master_sku','requires_photos','product_type',
        'product_image','parent_product_id','variant_attributes'],
    'inventory_skus' => ['id','product_id','sku_code','status','custom_data','created_at','assigned_to','historia','is_epp'],
    'inventory_user_stock' => ['id','user_id','product_id','quantity','is_epp'],
    'users' => ['id','name','email','password','role','created_at','pin','whatsapp','profile_picture','username','cover_picture'],
];

$missing_total = 0;

foreach ($required as $table => $cols) {
    echo "<h2>📋 $table</h2>";
    
    // Check if table exists
    $check = $pdo->query("SHOW TABLES LIKE '$table'");
    if ($check->rowCount() === 0) {
        echo "<p class='miss'>❌ TABLA NO EXISTE</p>";
        $missing_total += count($cols);
        continue;
    }
    
    $existing = [];
    $stmt = $pdo->query("DESCRIBE `$table`");
    while ($row = $stmt->fetch()) $existing[] = $row['Field'];
    
    echo '<table><tr><th>Columna</th><th>Estado</th></tr>';
    foreach ($cols as $col) {
        if (in_array($col, $existing)) {
            echo "<tr><td>$col</td><td class='ok'>✅ Existe</td></tr>";
        } else {
            echo "<tr><td>$col</td><td class='miss'>❌ FALTA</td></tr>";
            $missing_total++;
        }
    }
    echo '</table>';
}

// Check required tables
$req_tables = ['inventory_sku_photos', 'inventory_product_photos'];
echo "<h2>📋 Tablas adicionales</h2>";
echo '<table><tr><th>Tabla</th><th>Estado</th></tr>';
foreach ($req_tables as $t) {
    $check = $pdo->query("SHOW TABLES LIKE '$t'");
    if ($check->rowCount() > 0) {
        echo "<tr><td>$t</td><td class='ok'>✅ Existe</td></tr>";
    } else {
        echo "<tr><td>$t</td><td class='miss'>❌ FALTA</td></tr>";
        $missing_total++;
    }
}
echo '</table>';

// Test queries
echo "<h2>🧪 Test de queries</h2>";
$tests = [
    'list_products' => "SELECT p.*, c.name as category_name,
        COALESCE(p.product_image, (SELECT sp.ruta_archivo FROM inventory_sku_photos sp JOIN inventory_skus sk ON sp.sku_id = sk.id WHERE sk.product_id = p.id ORDER BY sp.id ASC LIMIT 1)) as display_image,
        (SELECT COUNT(*) FROM inventory_products ch WHERE ch.parent_product_id = p.id) as children_count
        FROM inventory_products p LEFT JOIN inventory_categories c ON p.category_id = c.id WHERE p.parent_product_id IS NULL LIMIT 1",
    'mochila_list_users' => "SELECT u.id, u.name, u.email, u.role, u.profile_picture,
        (SELECT COUNT(*) FROM inventory_skus WHERE assigned_to = u.id AND status != 'instalado') as normal_items,
        (SELECT COALESCE(SUM(quantity), 0) FROM inventory_user_stock WHERE user_id = u.id) as bulk_items,
        (SELECT COUNT(*) FROM inventory_skus s JOIN inventory_products p ON s.product_id = p.id WHERE s.assigned_to = u.id AND s.status != 'instalado' AND p.requires_photos = 1 AND (SELECT COUNT(*) FROM inventory_sku_photos WHERE sku_id = s.id) = 0) as sin_fotos
        FROM users u WHERE LOWER(u.role) NOT IN ('cliente', 'client') LIMIT 1",
    'stock_summary' => "SELECT COUNT(*) as total FROM inventory_skus s JOIN inventory_products p ON s.product_id = p.id",
];

echo '<table><tr><th>Query</th><th>Resultado</th></tr>';
foreach ($tests as $name => $sql) {
    try {
        $stmt = $pdo->query($sql);
        $count = $stmt->rowCount();
        echo "<tr><td>$name</td><td class='ok'>✅ OK ($count filas)</td></tr>";
    } catch (PDOException $e) {
        echo "<tr><td>$name</td><td class='miss'>❌ ERROR: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
    }
}
echo '</table>';

// Summary
echo '<hr style="border-color:#333;margin:30px 0">';
if ($missing_total === 0) {
    echo '<h2 class="ok">✅ Todo correcto — no faltan columnas ni tablas</h2>';
} else {
    echo "<h2 class='miss'>❌ Faltan $missing_total elementos — ejecuta la migración:</h2>";
    echo '<a class="btn" href="run_migration.php">🔧 Ejecutar Migración</a>';
}

echo '</body></html>';
