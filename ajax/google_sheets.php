<?php
/**
 * Google Sheets Sync — Ajax Endpoint
 * Acciones: check_config, export, import, save_sheet_id, get_sheet_url
 */

// Capturar cualquier output previo (warnings/notices de PHP) para que no rompa el JSON
ob_start();

require_once __DIR__ . '/../config/db.php';
requireLogin();

// Limpiar cualquier output previo y suprimir display de errores
ob_clean();
ini_set('display_errors', '0');
error_reporting(0);

header('Content-Type: application/json; charset=utf-8');

// ── Auto-loader ──────────────────────────────────────────────
$vendorAutoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($vendorAutoload)) {
    echo json_encode(['success' => false, 'message' => 'La librería de Google no está instalada.']);
    exit;
}
require_once $vendorAutoload;
require_once __DIR__ . '/../config/google_sheets.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ── Helper: crear cliente Google ─────────────────────────────
function makeGoogleClient(): ?Google\Client {
    if (!file_exists(GSHEETS_CREDENTIALS_PATH)) return null;
    $client = new Google\Client();
    $client->setApplicationName(GSHEETS_APPLICATION_NAME);
    $client->setScopes([Google\Service\Sheets::SPREADSHEETS]);
    $client->setAuthConfig(GSHEETS_CREDENTIALS_PATH);
    return $client;
}

function getSheetService(): ?Google\Service\Sheets {
    $client = makeGoogleClient();
    if (!$client) return null;
    return new Google\Service\Sheets($client);
}

// ── Helper: recolectar todas las columnas personalizadas ──────
function getAllCustomColumns(PDO $pdo): array {
    $cols = [];
    $stmt = $pdo->query("SELECT custom_columns FROM inventory_products WHERE custom_columns IS NOT NULL AND custom_columns != '[]' AND custom_columns != ''");
    foreach ($stmt as $row) {
        $decoded = json_decode($row['custom_columns'], true);
        if (!is_array($decoded)) continue;
        foreach ($decoded as $item) {
            // Soporta formato string: "NombreCol"
            // Soporta formato objeto: {"name":"NombreCol","type":"text"}
            if (is_string($item) && $item !== '') {
                $name = $item;
            } elseif (is_array($item) && !empty($item['name'])) {
                $name = $item['name'];
            } else {
                continue;
            }
            if (!in_array($name, $cols)) $cols[] = $name;
        }
    }
    return $cols;
}

// ── Helper: extraer lista de nombres de columnas de un producto ─
function extractProdColNames($json): array {
    $decoded = json_decode($json ?? '[]', true);
    if (!is_array($decoded)) return [];
    $names = [];
    foreach ($decoded as $item) {
        if (is_string($item) && $item !== '') {
            $names[] = $item;
        } elseif (is_array($item) && !empty($item['name'])) {
            $names[] = $item['name'];
        }
    }
    return $names;
}

// ── Hoja "Productos" — mismas columnas que la tabla del sistema ─
function getProductsData(PDO $pdo): array {
    $customCols = getAllCustomColumns($pdo);

    // Headers: idénticos a los <th> del sistema
    $headers = ['Producto', 'Categoría', 'Total', 'Disponibles', 'Instalados', 'Malogrados'];
    foreach ($customCols as $col) $headers[] = $col;
    $rows = [$headers];

    $stmt = $pdo->query("
        SELECT p.id, p.name, p.is_bulk, p.unit_type,
               p.stock_minimo, p.stock_critico, p.custom_columns,
               c.name AS category_name,
               (SELECT COUNT(*) FROM inventory_skus WHERE product_id = p.id) AS total_skus,
               (SELECT COUNT(*) FROM inventory_skus WHERE product_id = p.id AND status='disponible') AS qty_disponible,
               (SELECT COUNT(*) FROM inventory_skus WHERE product_id = p.id AND status='instalado')  AS qty_instalado,
               (SELECT COUNT(*) FROM inventory_skus WHERE product_id = p.id AND status='malogrado')  AS qty_malogrado,
               p.total_quantity AS bulk_qty
        FROM inventory_products p
        LEFT JOIN inventory_categories c ON c.id = p.category_id
        ORDER BY p.created_at DESC
    ");

    foreach ($stmt as $p) {
        $isBulk     = $p['is_bulk'] == 1;
        $total      = $isBulk ? intval($p['bulk_qty']) : intval($p['total_skus']);
        $disponible = $isBulk ? intval($p['bulk_qty']) : intval($p['qty_disponible']);
        $instalado  = $isBulk ? 0 : intval($p['qty_instalado']);
        $malogrado  = $isBulk ? 0 : intval($p['qty_malogrado']);
        $nombre     = $p['name'] . ($isBulk ? ' [Granel - ' . $p['unit_type'] . ']' : '');

        $row = [
            (string)$nombre,
            (string)($p['category_name'] ?? '—'),
            (int)$total,
            (int)$disponible,
            (int)$instalado,
            (int)$malogrado,
        ];

        // Columnas personalizadas: ✓ si el producto las tiene, vacío si no
        $prodCols = extractProdColNames($p['custom_columns'] ?? '[]');
        foreach ($customCols as $col) {
            $row[] = in_array($col, $prodCols) ? '✓' : '';
        }

        $rows[] = $row;
    }
    return $rows;
}

// ── Hoja "Control de Stock" — mismas columnas del sistema + custom ─
function getSkusData(PDO $pdo): array {
    $customCols = getAllCustomColumns($pdo);

    // Headers: idénticos a los <th> del sistema
    $headers = ['#', 'SKU', 'Producto', 'Categoría', 'Estado', 'Historia',
                'Últ. Actividad', 'Instalado a', 'Asignado', 'Fecha Registro'];
    foreach ($customCols as $col) $headers[] = $col;
    $rows = [$headers];

    $stmt = $pdo->query("
        SELECT s.id, s.sku_code, s.status, s.historia, s.custom_data, s.created_at,
               p.name AS product_name,
               c.name AS category_name,
               u.name AS assigned_user,
               (SELECT a.cliente_nombre FROM actas a
                JOIN actas_equipos ae ON a.id = ae.acta_id
                WHERE ae.serie_mac = s.sku_code
                ORDER BY a.fecha_creacion DESC LIMIT 1) AS acta_cliente,
               (SELECT MAX(e.created_at) FROM inventory_entries e WHERE e.sku_id = s.id) AS last_activity
        FROM inventory_skus s
        JOIN inventory_products p ON p.id = s.product_id
        LEFT JOIN inventory_categories c ON c.id = p.category_id
        LEFT JOIN users u ON u.id = s.assigned_to
        ORDER BY s.created_at DESC
        LIMIT 5000
    ");

    $i = 1;
    foreach ($stmt as $s) {
        $customData = json_decode($s['custom_data'] ?? '{}', true) ?: [];

        $row = [
            $i++,
            $s['sku_code'],
            $s['product_name'],
            $s['category_name'] ?? '—',
            ucfirst($s['status']),
            $s['historia'] ?? 'ninguno',
            $s['last_activity'] ? date('d/m/Y H:i', strtotime($s['last_activity'])) : '—',
            $s['acta_cliente'] ?? '—',
            $s['assigned_user'] ?? '—',
            date('d/m/Y H:i', strtotime($s['created_at'])),
        ];

        // Valor real de cada columna personalizada — siempre string
        foreach ($customCols as $col) {
            $val = $customData[$col] ?? '';
            // Si el valor es un array/objeto, convertir a string
            if (is_array($val) || is_object($val)) $val = json_encode($val);
            $row[] = (string)$val;
        }

        $rows[] = $row;
    }
    return $rows;
}

// ── Hoja "Resumen" — métricas del dashboard ──────────────────
function getSummaryData(PDO $pdo): array {
    $now  = date('d/m/Y H:i');
    $rows = [['Métrica', 'Valor', 'Actualizado']];

    $m = $pdo->query("
        SELECT
            COUNT(DISTINCT p.id) AS total_products,
            (SELECT COUNT(*) FROM inventory_skus) AS total_stock,
            (SELECT COUNT(*) FROM inventory_skus WHERE status='disponible') AS disponible,
            (SELECT COUNT(*) FROM inventory_skus WHERE status='instalado')  AS instalado,
            (SELECT COUNT(*) FROM inventory_skus WHERE status='malogrado')  AS malogrado,
            (SELECT COUNT(*) FROM inventory_skus WHERE status='reparado')   AS reparado,
            (SELECT COALESCE(SUM(total_quantity),0) FROM inventory_products WHERE is_bulk=1) AS bulk_total
        FROM inventory_products p
    ")->fetch();

    $rows[] = ['Total Productos',       $m['total_products'],               $now];
    $rows[] = ['Total SKUs',            intval($m['total_stock']),           $now];
    $rows[] = ['Disponibles',           intval($m['disponible']),            $now];
    $rows[] = ['Instalados',            intval($m['instalado']),             $now];
    $rows[] = ['Malogrados',            intval($m['malogrado']),             $now];
    $rows[] = ['Reparados',             intval($m['reparado']),              $now];
    $rows[] = ['Stock Granel (unid.)',  intval($m['bulk_total']),            $now];

    $low = $pdo->query("
        SELECT COUNT(*) FROM (
            SELECT p.id FROM inventory_products p
            WHERE (SELECT COUNT(*) FROM inventory_skus WHERE product_id = p.id AND status='disponible')
                  <= p.stock_critico AND p.is_bulk = 0
        ) t
    ")->fetchColumn();
    $rows[] = ['Productos Stock Crítico', intval($low), $now];

    return $rows;
}

// ── Helper: escribir una hoja con formato ────────────────────
function writeSheet(Google\Service\Sheets $service, string $spreadsheetId, string $tabName, array $data): void {
    $spreadsheet = $service->spreadsheets->get($spreadsheetId);
    $sheetExists = false;
    $sheetIdFound = null;

    foreach ($spreadsheet->getSheets() as $sheet) {
        if ($sheet->getProperties()->getTitle() === $tabName) {
            $sheetExists  = true;
            $sheetIdFound = $sheet->getProperties()->getSheetId();
            break;
        }
    }

    if (!$sheetExists) {
        $res = $service->spreadsheets->batchUpdate($spreadsheetId, new Google\Service\Sheets\BatchUpdateSpreadsheetRequest([
            'requests' => [new Google\Service\Sheets\Request(['addSheet' => ['properties' => ['title' => $tabName]]])]
        ]));
        $sheetIdFound = $res->getReplies()[0]->getAddSheet()->getProperties()->getSheetId();
    }

    // Limpiar y escribir datos
    $service->spreadsheets_values->clear($spreadsheetId, $tabName . '!A:ZZ', new Google\Service\Sheets\ClearValuesRequest());
    $service->spreadsheets_values->update(
        $spreadsheetId,
        $tabName . '!A1',
        new Google\Service\Sheets\ValueRange(['values' => $data]),
        ['valueInputOption' => 'RAW']
    );

    // Formato: encabezado en negrita con fondo oscuro
    if ($sheetIdFound !== null) {
        $service->spreadsheets->batchUpdate($spreadsheetId, new Google\Service\Sheets\BatchUpdateSpreadsheetRequest([
            'requests' => [new Google\Service\Sheets\Request([
                'repeatCell' => [
                    'range' => ['sheetId' => $sheetIdFound, 'startRowIndex' => 0, 'endRowIndex' => 1],
                    'cell'  => ['userEnteredFormat' => [
                        'textFormat'       => ['bold' => true, 'foregroundColor' => ['red' => 1, 'green' => 1, 'blue' => 1]],
                        'backgroundColor'  => ['red' => 0.13, 'green' => 0.37, 'blue' => 0.84],
                    ]],
                    'fields' => 'userEnteredFormat(textFormat,backgroundColor)'
                ]
            ])]
        ]));
    }
}

// ═══════════════════════════════════════════════════════════════
// ACCIONES
// ═══════════════════════════════════════════════════════════════

switch ($action) {

    // ── check_config ──
    case 'check_config':
        $credOk = file_exists(GSHEETS_CREDENTIALS_PATH);
        $idOk   = !empty(GSHEETS_SPREADSHEET_ID);
        $libOk  = file_exists($vendorAutoload);
        echo json_encode([
            'success'        => $credOk && $idOk && $libOk,
            'credentials'    => $credOk,
            'spreadsheet_id' => $idOk,
            'library'        => $libOk,
            'sheet_id'       => GSHEETS_SPREADSHEET_ID,
        ]);
        break;

    // ── export ──
    case 'export':
        if (empty(GSHEETS_SPREADSHEET_ID)) {
            echo json_encode(['success' => false, 'message' => 'No has configurado el ID del Google Sheet.']); exit;
        }
        $service = getSheetService();
        if (!$service) {
            echo json_encode(['success' => false, 'message' => 'No se encontró google-credentials.json en config/.']); exit;
        }
        try {
            writeSheet($service, GSHEETS_SPREADSHEET_ID, GSHEETS_TAB_PRODUCTS, getProductsData($pdo));
            writeSheet($service, GSHEETS_SPREADSHEET_ID, GSHEETS_TAB_SKUS,     getSkusData($pdo));
            writeSheet($service, GSHEETS_SPREADSHEET_ID, GSHEETS_TAB_SUMMARY,  getSummaryData($pdo));
            $url = 'https://docs.google.com/spreadsheets/d/' . GSHEETS_SPREADSHEET_ID . '/edit';
            echo json_encode(['success' => true, 'message' => 'Inventario exportado correctamente a Google Sheets', 'url' => $url]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error al exportar: ' . $e->getMessage()]);
        }
        break;

    // ── import ──
    case 'import':
        if (empty(GSHEETS_SPREADSHEET_ID)) {
            echo json_encode(['success' => false, 'message' => 'No has configurado el ID del Google Sheet.']); exit;
        }
        $service = getSheetService();
        if (!$service) {
            echo json_encode(['success' => false, 'message' => 'No se encontró google-credentials.json en config/.']); exit;
        }
        try {
            $results = [];

            // ══════════════════════════════════════════
            // 1. IMPORTAR HOJA "Productos"
            // ══════════════════════════════════════════
            try {
                $resp = $service->spreadsheets_values->get(GSHEETS_SPREADSHEET_ID, GSHEETS_TAB_PRODUCTS . '!A:ZZ');
                $prodRows = $resp->getValues();
            } catch (Exception $e) {
                $prodRows = [];
            }

            $prodImported = 0; $prodUpdated = 0;
            $prodLog = [];

            if (!empty($prodRows) && count($prodRows) >= 2) {
                // Normalizar encabezados para evitar problemas con acentos/encoding
                $rawHeader = $prodRows[0];
                array_shift($prodRows);

                // Construir mapa col-nombre → índice (con normalización)
                $colMap = [];
                foreach ($rawHeader as $i => $h) {
                    $normalized = mb_strtolower(trim($h), 'UTF-8');
                    $colMap[$normalized] = $i;
                }

                // Función para obtener índice de columna con fallback
                $getIdx = function(string $name, int $fallback) use ($colMap): int {
                    $key = mb_strtolower($name, 'UTF-8');
                    return $colMap[$key] ?? $fallback;
                };

                // Mapear los 6 índices principales (con y sin tilde para seguridad)
                $idxNombre    = $getIdx('producto',     0);
                $idxCategoria = $getIdx('categoría',    1);
                if (!isset($colMap['categoría'])) $idxCategoria = $getIdx('categoria', 1);
                $idxTotal     = $getIdx('total',        2);
                $idxDisp      = $getIdx('disponibles',  3);
                $idxInst      = $getIdx('instalados',   4);
                $idxMalog     = $getIdx('malogrados',   5);

                // Columnas personalizadas = las que no son del sistema
                $sysKeys = ['producto','categoría','categoria','total','disponibles','instalados','malogrados'];
                $customColsFromSheet = [];
                foreach ($rawHeader as $i => $h) {
                    if (!in_array(mb_strtolower(trim($h), 'UTF-8'), $sysKeys)) {
                        $customColsFromSheet[] = trim($h);
                    }
                }

                foreach ($prodRows as $row) {
                    $name        = trim($row[$idxNombre]    ?? '');
                    $catName     = trim($row[$idxCategoria] ?? '');
                    $sheetTotal  = max(0, intval($row[$idxTotal]  ?? 0));
                    $sheetDisp   = max(0, intval($row[$idxDisp]   ?? 0));
                    $sheetInst   = max(0, intval($row[$idxInst]   ?? 0));
                    $sheetMalog  = max(0, intval($row[$idxMalog]  ?? 0));

                    if (empty($name)) continue;

                    // Limpiar sufijo [Granel - ...] que agrega la exportación
                    $cleanName = trim(preg_replace('/\s*\[Granel[^\]]*\]/u', '', $name));
                    if (empty($cleanName)) continue;

                    // Resolver categoría
                    $catId = null;
                    if (!empty($catName) && $catName !== '—') {
                        $cs = $pdo->prepare("SELECT id FROM inventory_categories WHERE LOWER(name) = LOWER(?) LIMIT 1");
                        $cs->execute([$catName]);
                        $cat = $cs->fetch();
                        if (!$cat) {
                            $pdo->prepare("INSERT INTO inventory_categories (name) VALUES (?)")->execute([$catName]);
                            $catId = $pdo->lastInsertId();
                        } else {
                            $catId = $cat['id'];
                        }
                    }

                    // Buscar producto en BD
                    $ps = $pdo->prepare("SELECT id, is_bulk, total_quantity, custom_columns FROM inventory_products WHERE LOWER(TRIM(name)) = LOWER(TRIM(?)) LIMIT 1");
                    $ps->execute([$cleanName]);
                    $existing = $ps->fetch();

                    if (!$existing) {
                        $pdo->prepare("INSERT INTO inventory_products (name, category_id, stock_minimo, stock_critico, custom_columns, total_quantity, is_bulk) VALUES (?,?,10,3,?,0,0)")
                            ->execute([$cleanName, $catId, json_encode($customColsFromSheet)]);
                        $prodImported++;
                        $prodLog[] = "✅ NUEVO: $cleanName";
                        continue;
                    }

                    $pid          = $existing['id'];
                    $isBulk       = $existing['is_bulk'] == 1;
                    $existingCols = extractProdColNames($existing['custom_columns'] ?? '[]');
                    $mergedCols   = array_values(array_unique(array_merge($existingCols, $customColsFromSheet)));

                    // ──────────────────────────────────────────────────────
                    // GRANEL: total_quantity = Disponibles (stock almacén)
                    // ──────────────────────────────────────────────────────
                    if ($isBulk) {
                        $oldQty = intval($existing['total_quantity']);
                        $pdo->prepare("UPDATE inventory_products SET category_id=?, custom_columns=?, total_quantity=? WHERE id=?")
                            ->execute([$catId, json_encode($mergedCols), $sheetDisp, $pid]);
                        $prodLog[] = "♻️ GRANEL «$cleanName»: $oldQty → $sheetDisp unidades";

                    // ──────────────────────────────────────────────────────
                    // SKU: rebalancear estados para coincidir con Sheet
                    // ──────────────────────────────────────────────────────
                    } else {
                        // 1. Contar SKUs actuales por estado
                        $cntStmt = $pdo->prepare("
                            SELECT status, COUNT(*) as cnt
                            FROM inventory_skus
                            WHERE product_id = ?
                            GROUP BY status
                        ");
                        $cntStmt->execute([$pid]);
                        $cntByStatus = [];
                        foreach ($cntStmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                            $cntByStatus[$r['status']] = intval($r['cnt']);
                        }
                        $curTotal  = array_sum($cntByStatus);
                        $curDisp   = $cntByStatus['disponible']  ?? 0;
                        $curInst   = $cntByStatus['instalado']   ?? 0;
                        $curMalog  = $cntByStatus['malogrado']   ?? 0;

                        $logParts = [];

                        // 2. Si el Sheet pide más Total → agregar nuevos SKUs disponibles
                        if ($sheetTotal > $curTotal) {
                            $toAdd = $sheetTotal - $curTotal;
                            $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
                            $inserted = 0; $attempts = 0;
                            $emptyCustom = new stdClass();
                            foreach ($existingCols as $col) $emptyCustom->$col = '';
                            $emptyJson = json_encode($emptyCustom);
                            while ($inserted < $toAdd && $attempts < max($toAdd * 20, 300)) {
                                $code = 'IMP-';
                                for ($c = 0; $c < 6; $c++) $code .= $chars[random_int(0, strlen($chars) - 1)];
                                $chk = $pdo->prepare("SELECT COUNT(*) FROM inventory_skus WHERE sku_code = ?");
                                $chk->execute([$code]);
                                if ((int)$chk->fetchColumn() === 0) {
                                    $pdo->prepare("INSERT INTO inventory_skus (product_id, sku_code, status, custom_data) VALUES (?, ?, 'disponible', ?)")
                                        ->execute([$pid, $code, $emptyJson]);
                                    $inserted++;
                                    $curDisp++;
                                    $curTotal++;
                                }
                                $attempts++;
                            }
                            if ($inserted > 0) $logParts[] = "+$inserted SKUs";
                        }

                        // 3. Rebalancear Instalados
                        $diffInst = $sheetInst - $curInst;
                        if ($diffInst > 0 && $curDisp > 0) {
                            // Disponible → Instalado
                            $toChange = min($diffInst, $curDisp);
                            $pdo->prepare("
                                UPDATE inventory_skus SET status='instalado'
                                WHERE product_id=? AND status='disponible'
                                LIMIT $toChange
                            ")->execute([$pid]);
                            $curDisp -= $toChange; $curInst += $toChange;
                            $logParts[] = "$toChange disp→inst";
                        } elseif ($diffInst < 0) {
                            // Instalado → Disponible
                            $toChange = min(abs($diffInst), $curInst);
                            $pdo->prepare("
                                UPDATE inventory_skus SET status='disponible'
                                WHERE product_id=? AND status='instalado'
                                LIMIT $toChange
                            ")->execute([$pid]);
                            $curDisp += $toChange; $curInst -= $toChange;
                            $logParts[] = "$toChange inst→disp";
                        }

                        // 4. Rebalancear Malogrados
                        $diffMalog = $sheetMalog - $curMalog;
                        if ($diffMalog > 0 && $curDisp > 0) {
                            // Disponible → Malogrado
                            $toChange = min($diffMalog, $curDisp);
                            $pdo->prepare("
                                UPDATE inventory_skus SET status='malogrado'
                                WHERE product_id=? AND status='disponible'
                                LIMIT $toChange
                            ")->execute([$pid]);
                            $curDisp -= $toChange; $curMalog += $toChange;
                            $logParts[] = "$toChange disp→malog";
                        } elseif ($diffMalog < 0) {
                            // Malogrado → Disponible
                            $toChange = min(abs($diffMalog), $curMalog);
                            $pdo->prepare("
                                UPDATE inventory_skus SET status='disponible'
                                WHERE product_id=? AND status='malogrado'
                                LIMIT $toChange
                            ")->execute([$pid]);
                            $curDisp += $toChange; $curMalog -= $toChange;
                            $logParts[] = "$toChange malog→disp";
                        }

                        $pdo->prepare("UPDATE inventory_products SET category_id=?, custom_columns=? WHERE id=?")
                            ->execute([$catId, json_encode($mergedCols), $pid]);

                        $summary = empty($logParts) ? 'sin cambios' : implode(', ', $logParts);
                        $prodLog[] = "🔄 SKU «$cleanName»: $summary (ahora: disp=$curDisp, inst=$curInst, malog=$curMalog)";
                    }
                    $prodUpdated++;
                }
            }
            $results[] = "📦 Productos: $prodImported nuevos · $prodUpdated actualizados";
            if (!empty($prodLog)) $results[] = implode('<br>', $prodLog);

            // ══════════════════════════════════════════
            // 2. IMPORTAR HOJA "Control de Stock" (SKUs)
            // ══════════════════════════════════════════
            try {
                $respSkus = $service->spreadsheets_values->get(GSHEETS_SPREADSHEET_ID, GSHEETS_TAB_SKUS . '!A:ZZ');
                $skuRows  = $respSkus->getValues();
            } catch (Exception $e) {
                $skuRows = [];
            }

            $skuUpdated = 0; $skuErrors = 0;

            if (!empty($skuRows) && count($skuRows) >= 2) {
                $skuHeader = array_map('trim', $skuRows[0]);
                $skuColIdx = array_flip($skuHeader);
                array_shift($skuRows);

                // Columnas estándar del sistema en SKUs
                $sysSkuCols = ['#','SKU','Producto','Categoría','Estado','Historia','Últ. Actividad','Instalado a','Asignado','Fecha Registro'];
                $customSkuCols = array_values(array_filter($skuHeader, fn($h) => !in_array($h, $sysSkuCols)));

                // Mapeo de estado: normalizar texto del Sheet al valor de BD
                $statusMap = [
                    'disponible'  => 'disponible',
                    'instalado'   => 'instalado',
                    'malogrado'   => 'malogrado',
                    'reparado'    => 'reparado',
                    'en_transito' => 'en_transito',
                    'en transito' => 'en_transito',
                ];
                $historiaMap = [
                    'ninguno'     => 'ninguno',
                    'devuelto'    => 'devuelto',
                    'malogrado'   => 'malogrado',
                    'antiguo'     => 'antiguo',
                    'en_transito' => 'en_transito',
                    'en transito' => 'en_transito',
                ];

                foreach ($skuRows as $row) {
                    $skuCode = trim($row[$skuColIdx['SKU'] ?? 1] ?? '');
                    if (empty($skuCode) || str_starts_with($skuCode, '#')) continue;

                    // Buscar SKU en la BD
                    $ss = $pdo->prepare("SELECT id, custom_data, status, historia FROM inventory_skus WHERE sku_code = ? LIMIT 1");
                    $ss->execute([$skuCode]);
                    $sku = $ss->fetch();
                    if (!$sku) { $skuErrors++; continue; }

                    // Nuevo estado (solo si es válido)
                    $rawStatus  = strtolower(trim($row[$skuColIdx['Estado'] ?? 4] ?? ''));
                    $rawHistoria = strtolower(trim($row[$skuColIdx['Historia'] ?? 5] ?? ''));
                    $newStatus  = $statusMap[$rawStatus]   ?? $sku['status'];
                    $newHistoria = $historiaMap[$rawHistoria] ?? $sku['historia'];

                    // Actualizar custom_data con valores de columnas personalizadas
                    $customData = json_decode($sku['custom_data'] ?? '{}', true) ?: [];
                    foreach ($customSkuCols as $col) {
                        if (isset($skuColIdx[$col]) && isset($row[$skuColIdx[$col]])) {
                            $customData[$col] = trim($row[$skuColIdx[$col]]);
                        }
                    }

                    $pdo->prepare("UPDATE inventory_skus SET status=?, historia=?, custom_data=? WHERE id=?")
                        ->execute([$newStatus, $newHistoria, json_encode($customData), $sku['id']]);
                    $skuUpdated++;
                }
            }
            $results[] = "🔖 SKUs actualizados: $skuUpdated" . ($skuErrors > 0 ? " · $skuErrors no encontrados" : "");

            echo json_encode([
                'success'  => true,
                'message'  => implode('<br>', $results),
                'imported' => $prodImported,
                'updated'  => $prodUpdated + $skuUpdated,
            ]);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error al importar: ' . $e->getMessage()]);
        }
        break;

    // ── save_sheet_id ──
    case 'save_sheet_id':
        $newId = trim($_POST['sheet_id'] ?? '');
        if (empty($newId)) { echo json_encode(['success' => false, 'message' => 'ID vacío']); exit; }
        $configFile = __DIR__ . '/../config/google_sheets.php';
        $content = file_get_contents($configFile);
        $updated = preg_replace(
            "/define\('GSHEETS_SPREADSHEET_ID',\s*'[^']*'\)/",
            "define('GSHEETS_SPREADSHEET_ID', '" . addslashes($newId) . "')",
            $content
        );
        if ($updated && $updated !== $content) {
            file_put_contents($configFile, $updated);
            echo json_encode(['success' => true, 'message' => 'ID guardado correctamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se pudo actualizar. Edita config/google_sheets.php manualmente.']);
        }
        break;

    // ── get_sheet_url ──
    case 'get_sheet_url':
        if (empty(GSHEETS_SPREADSHEET_ID)) {
            echo json_encode(['success' => false]);
        } else {
            echo json_encode(['success' => true, 'url' => 'https://docs.google.com/spreadsheets/d/' . GSHEETS_SPREADSHEET_ID . '/edit']);
        }
        break;

    // ── debug_import: diagnóstico sin modificar BD ──
    case 'debug_import':
        $service = getSheetService();
        if (!$service) { echo json_encode(['success'=>false,'message'=>'Sin credenciales']); break; }
        try {
            $resp = $service->spreadsheets_values->get(GSHEETS_SPREADSHEET_ID, GSHEETS_TAB_PRODUCTS . '!A:ZZ');
            $rows = $resp->getValues();
        } catch (Exception $e) {
            echo json_encode(['success'=>false,'message'=>$e->getMessage()]); break;
        }

        if (empty($rows)) { echo json_encode(['success'=>false,'message'=>'Hoja vacía']); break; }

        $rawHeader = $rows[0];
        $debug = [
            'raw_headers'  => $rawHeader,
            'total_rows'   => count($rows) - 1,
            'products'     => [],
        ];

        // Mapear índices
        $colMap2 = [];
        foreach ($rawHeader as $i => $h) $colMap2[mb_strtolower(trim($h),'UTF-8')] = $i;
        $getI = fn($n,$f) => $colMap2[mb_strtolower($n,'UTF-8')] ?? $f;

        $debug['col_indices'] = [
            'Producto'    => $getI('producto',    0),
            'Categoría'   => $getI('categoría',   1),
            'Total'       => $getI('total',        2),
            'Disponibles' => $getI('disponibles',  3),
            'Instalados'  => $getI('instalados',   4),
            'Malogrados'  => $getI('malogrados',   5),
        ];

        array_shift($rows);
        foreach (array_slice($rows, 0, 20) as $row) {
            $rawName   = $row[$debug['col_indices']['Producto']]    ?? '(vacío)';
            $cleanN    = trim(preg_replace('/\s*\[Granel[^\]]*\]/u','', $rawName));
            $shTotal   = intval($row[$debug['col_indices']['Total']]       ?? 0);
            $shDisp    = intval($row[$debug['col_indices']['Disponibles']] ?? 0);
            $shInst    = intval($row[$debug['col_indices']['Instalados']]  ?? 0);
            $shMalog   = intval($row[$debug['col_indices']['Malogrados']]  ?? 0);

            $ps = $pdo->prepare("SELECT id, is_bulk, total_quantity FROM inventory_products WHERE LOWER(TRIM(name))=LOWER(TRIM(?)) LIMIT 1");
            $ps->execute([$cleanN]);
            $found = $ps->fetch(PDO::FETCH_ASSOC);

            $dbInfo = null;
            if ($found) {
                if ($found['is_bulk']) {
                    $dbInfo = ['tipo'=>'GRANEL','total_quantity'=>$found['total_quantity']];
                } else {
                    $cnt = $pdo->prepare("SELECT status,COUNT(*) c FROM inventory_skus WHERE product_id=? GROUP BY status");
                    $cnt->execute([$found['id']]);
                    $dbInfo = ['tipo'=>'SKU','por_estado'=>[]];
                    foreach ($cnt->fetchAll(PDO::FETCH_ASSOC) as $r) $dbInfo['por_estado'][$r['status']] = $r['c'];
                }
            }

            $debug['products'][] = [
                'nombre_raw'   => $rawName,
                'nombre_clean' => $cleanN,
                'sheet'        => ['total'=>$shTotal,'disp'=>$shDisp,'inst'=>$shInst,'malog'=>$shMalog],
                'en_bd'        => $found ? true : false,
                'bd_datos'     => $dbInfo,
            ];
        }
        echo json_encode(['success'=>true, 'debug'=>$debug], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Acción desconocida']);
}

// Asegurar que todo el output sea JSON limpio
if (ob_get_level()) ob_end_flush();
