<?php
require_once __DIR__ . '/../config/db.php';
requireLogin();

header('Content-Type: application/json');

$userId = (int)($_SESSION['user_id'] ?? 0);
$userRole = $_SESSION['user_role'] ?? 'tecnico';

// Helper: Calcular distancia en metros entre dos coordenadas (Haversine)
function calculateHaversineDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371000; // metros
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $earthRadius * $c;
}

// Helper: Guardar imagen base64
function saveBase64Image($base64Data, $prefix = 'att') {
    if (empty($base64Data)) return null;

    $yearMonth = date('Y-m');
    $uploadDir = __DIR__ . '/../uploads/attendance/' . $yearMonth;
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $type)) {
        $data = substr($base64Data, strpos($base64Data, ',') + 1);
        $type = strtolower($type[1]);
        if ($type === 'jpeg') $type = 'jpg';
    } else {
        $data = $base64Data;
        $type = 'jpg';
    }

    $decodedData = base64_decode($data);
    if ($decodedData === false) {
        return null;
    }

    $filename = $prefix . '_' . uniqid() . '_' . time() . '.' . $type;
    $filePath = $uploadDir . '/' . $filename;
    file_put_contents($filePath, $decodedData);

    return 'uploads/attendance/' . $yearMonth . '/' . $filename;
}

// Helper: Obtener configuraciones del sistema
function getAttendanceSettings($pdo) {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM attendance_settings");
    $settings = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    return $settings;
}

$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($requestMethod === 'POST' || $requestMethod === 'GET') {
    $action = $_REQUEST['action'] ?? '';

    // ==========================================
    // 1. ESTADO DE TURNO DEL USUARIO HOY
    // ==========================================
    if ($action === 'get_shift_status') {
        try {
            $targetUserId = (int)($_REQUEST['user_id'] ?? $userId);
            
            // Obtener logs del día de hoy
            $today = date('Y-m-d');
            $stmt = $pdo->prepare("
                SELECT * FROM attendance_logs 
                WHERE user_id = ? AND DATE(created_at) = ? 
                ORDER BY created_at ASC
            ");
            $stmt->execute([$targetUserId, $today]);
            $logsToday = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Determinar último estado y siguiente acción sugerida
            $lastLog = end($logsToday);
            $lastType = $lastLog ? $lastLog['type'] : null;

            $statusText = 'sin_iniciar';
            $nextAction = 'entrada';
            $nextLabel = 'Iniciar Jornada';
            $isOnLunch = false;

            if (!$lastLog) {
                $statusText = 'sin_iniciar';
                $nextAction = 'entrada';
                $nextLabel = 'Iniciar Jornada';
            } elseif ($lastType === 'entrada' || $lastType === 'fin_refrigerio') {
                $statusText = 'en_jornada';
                $nextAction = 'inicio_refrigerio';
                $nextLabel = 'Salida a Refrigerio';
            } elseif ($lastType === 'inicio_refrigerio') {
                $statusText = 'en_refrigerio';
                $nextAction = 'fin_refrigerio';
                $nextLabel = 'Retorno de Refrigerio';
                $isOnLunch = true;
            } elseif ($lastType === 'salida') {
                $statusText = 'jornada_finalizada';
                $nextAction = 'entrada';
                $nextLabel = 'Iniciar Nueva Marcación';
            }

            // Geocercas activas para validación en cliente
            $stmtGeo = $pdo->query("SELECT id, name, latitude, longitude, radius_meters FROM attendance_geofences WHERE is_active = 1");
            $geofences = $stmtGeo->fetchAll(PDO::FETCH_ASSOC);

            // Configuraciones de horario
            $settings = getAttendanceSettings($pdo);

            echo json_encode([
                'success' => true,
                'status' => $statusText,
                'next_action' => $nextAction,
                'next_label' => $nextLabel,
                'last_log' => $lastLog,
                'logs_today' => $logsToday,
                'is_on_lunch' => $isOnLunch,
                'geofences' => $geofences,
                'settings' => $settings,
                'current_server_time' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error al obtener estado: ' . $e->getMessage()]);
        }
        exit;
    }

    // ==========================================
    // 2. REGISTRAR MARCACIÓN DE ASISTENCIA
    // ==========================================
    if ($action === 'register_clock') {
        try {
            $type = $_POST['type'] ?? 'entrada';
            $validTypes = ['entrada', 'inicio_refrigerio', 'fin_refrigerio', 'salida'];
            if (!in_array($type, $validTypes)) {
                $type = 'desconocido';
            }

            $photoBase64 = $_POST['photo'] ?? '';
            $latitude = !empty($_POST['latitude']) ? (float)$_POST['latitude'] : null;
            $longitude = !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null;
            $accuracy = !empty($_POST['accuracy']) ? (float)$_POST['accuracy'] : null;
            $address = $_POST['address'] ?? '';
            $outOfZoneReason = trim($_POST['out_of_zone_reason'] ?? '');
            $biometricType = $_POST['biometric_type'] ?? 'face_id';
            $biometricVerified = isset($_POST['biometric_verified']) ? (int)$_POST['biometric_verified'] : 1;
            $livenessScore = isset($_POST['liveness_score']) ? (float)$_POST['liveness_score'] : 1.0;
            $livenessAction = $_POST['liveness_action'] ?? 'liveness_pass';
            $deviceInfo = $_POST['device_info'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? 'Mobile Device');

            // Guardar foto de evidencia
            $photoPath = null;
            if (!empty($photoBase64)) {
                $photoPath = saveBase64Image($photoBase64, 'att_' . $userId);
            }

            // Validar Geocerca
            $isOutOfZone = 0;
            $matchedGeofenceId = null;

            if ($latitude !== null && $longitude !== null) {
                $stmtGeo = $pdo->query("SELECT id, name, latitude, longitude, radius_meters FROM attendance_geofences WHERE is_active = 1");
                $geofences = $stmtGeo->fetchAll(PDO::FETCH_ASSOC);

                $minDistance = null;
                foreach ($geofences as $geo) {
                    $dist = calculateHaversineDistance($latitude, $longitude, (float)$geo['latitude'], (float)$geo['longitude']);
                    if ($dist <= (float)$geo['radius_meters']) {
                        $matchedGeofenceId = $geo['id'];
                        $isOutOfZone = 0;
                        break;
                    }
                    if ($minDistance === null || $dist < $minDistance) {
                        $minDistance = $dist;
                    }
                }

                // Si no coincide con ninguna geocerca central, verificar si coincide con tickets asignados hoy
                if ($matchedGeofenceId === null && !empty($geofences)) {
                    $isOutOfZone = 1;
                }
            }

            // Calcular Tardanza si es marcación de Entrada
            $isLate = 0;
            $minutesLate = 0;
            if ($type === 'entrada') {
                $settings = getAttendanceSettings($pdo);
                $startTimeStr = $settings['work_start_time'] ?? '08:00:00';
                $toleranceMin = (int)($settings['tolerance_minutes'] ?? 15);

                $todayStart = strtotime(date('Y-m-d') . ' ' . $startTimeStr);
                $toleranceLimit = $todayStart + ($toleranceMin * 60);
                $now = time();

                if ($now > $toleranceLimit) {
                    $isLate = 1;
                    $minutesLate = (int)round(($now - $todayStart) / 60);
                }
            }

            // Insertar en attendance_logs
            $stmt = $pdo->prepare("
                INSERT INTO attendance_logs (
                    user_id, type, photo_path, latitude, longitude, accuracy, address,
                    is_out_of_zone, out_of_zone_reason, geofence_id, biometric_type,
                    biometric_verified, liveness_score, liveness_action, is_offline_sync,
                    device_info, is_late, minutes_late, created_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, 0,
                    ?, ?, ?, NOW()
                )
            ");

            $stmt->execute([
                $userId, $type, $photoPath, $latitude, $longitude, $accuracy, $address,
                $isOutOfZone, $outOfZoneReason, $matchedGeofenceId, $biometricType,
                $biometricVerified, $livenessScore, $livenessAction,
                $deviceInfo, $isLate, $minutesLate
            ]);

            $logId = $pdo->lastInsertId();

            echo json_encode([
                'success' => true,
                'message' => 'Marcación de ' . strtoupper($type) . ' registrada correctamente.',
                'log_id' => $logId,
                'is_late' => $isLate,
                'minutes_late' => $minutesLate,
                'is_out_of_zone' => $isOutOfZone,
                'photo_path' => $photoPath,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error al registrar asistencia: ' . $e->getMessage()]);
        }
        exit;
    }

    // ==========================================
    // 3. SINCRONIZACIÓN DE REGISTROS OFFLINE
    // ==========================================
    if ($action === 'sync_offline_logs') {
        try {
            $rawInput = file_get_contents('php://input');
            $data = json_decode($rawInput, true);
            $logs = $data['logs'] ?? ($_POST['logs'] ?? []);

            if (is_string($logs)) {
                $logs = json_decode($logs, true) ?? [];
            }

            $syncedCount = 0;
            $settings = getAttendanceSettings($pdo);

            $stmt = $pdo->prepare("
                INSERT INTO attendance_logs (
                    user_id, type, photo_path, latitude, longitude, accuracy, address,
                    is_out_of_zone, out_of_zone_reason, biometric_type, biometric_verified,
                    liveness_score, liveness_action, is_offline_sync, client_timestamp,
                    device_info, is_late, minutes_late, created_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, 1, ?,
                    ?, ?, ?, ?
                )
            ");

            foreach ($logs as $item) {
                $itemUserId = (int)($item['user_id'] ?? $userId);
                $type = $item['type'] ?? 'entrada';
                $photoPath = !empty($item['photo']) ? saveBase64Image($item['photo'], 'off_' . $itemUserId) : null;
                $lat = $item['latitude'] ?? null;
                $lon = $item['longitude'] ?? null;
                $acc = $item['accuracy'] ?? null;
                $address = $item['address'] ?? '';
                $isOutOfZone = !empty($item['is_out_of_zone']) ? 1 : 0;
                $reason = $item['out_of_zone_reason'] ?? '';
                $bioType = $item['biometric_type'] ?? 'face_id';
                $bioVer = isset($item['biometric_verified']) ? (int)$item['biometric_verified'] : 1;
                $liveScore = $item['liveness_score'] ?? 1.0;
                $liveAct = $item['liveness_action'] ?? 'offline_pass';
                $clientTime = $item['client_timestamp'] ?? date('Y-m-d H:i:s');
                $devInfo = $item['device_info'] ?? 'Offline Sync';
                
                $isLate = 0;
                $minLate = 0;
                if ($type === 'entrada') {
                    $itemTime = strtotime($clientTime);
                    $startTimeStr = $settings['work_start_time'] ?? '08:00:00';
                    $toleranceMin = (int)($settings['tolerance_minutes'] ?? 15);
                    $dayStart = strtotime(date('Y-m-d', $itemTime) . ' ' . $startTimeStr);
                    if ($itemTime > ($dayStart + ($toleranceMin * 60))) {
                        $isLate = 1;
                        $minLate = (int)round(($itemTime - $dayStart) / 60);
                    }
                }

                $stmt->execute([
                    $itemUserId, $type, $photoPath, $lat, $lon, $acc, $address,
                    $isOutOfZone, $reason, $bioType, $bioVer,
                    $liveScore, $liveAct, $clientTime,
                    $devInfo, $isLate, $minLate, $clientTime
                ]);
                $syncedCount++;
            }

            echo json_encode([
                'success' => true,
                'message' => "Se sincronizaron $syncedCount registros de asistencia offline correctamente.",
                'synced_count' => $syncedCount
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error al sincronizar logs: ' . $e->getMessage()]);
        }
        exit;
    }

    // ==========================================
    // 4. LISTADO DE REGISTROS DE ASISTENCIA
    // ==========================================
    if ($action === 'list') {
        try {
            $startDate = $_REQUEST['start_date'] ?? '';
            $endDate = $_REQUEST['end_date'] ?? '';
            $filterUserId = $_REQUEST['user_id'] ?? '';
            $filterLate = $_REQUEST['only_late'] ?? '';
            $filterZone = $_REQUEST['only_out_of_zone'] ?? '';

            $sql = "SELECT a.*, u.name as user_name, u.role as user_role, u.profile_picture,
                           g.name as geofence_name
                    FROM attendance_logs a 
                    JOIN users u ON a.user_id = u.id 
                    LEFT JOIN attendance_geofences g ON a.geofence_id = g.id
                    WHERE 1=1";
            $params = [];

            if (!empty($startDate)) {
                $sql .= " AND DATE(a.created_at) >= ?";
                $params[] = $startDate;
            }
            if (!empty($endDate)) {
                $sql .= " AND DATE(a.created_at) <= ?";
                $params[] = $endDate;
            }
            if (!empty($filterUserId)) {
                $sql .= " AND a.user_id = ?";
                $params[] = $filterUserId;
            }
            if ($filterLate === '1') {
                $sql .= " AND a.is_late = 1";
            }
            if ($filterZone === '1') {
                $sql .= " AND a.is_out_of_zone = 1";
            }

            $sql .= " ORDER BY a.created_at DESC LIMIT 500";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Calcular estadísticas rápidas
            $stats = [
                'total' => count($logs),
                'tardanzas' => 0,
                'fuera_zona' => 0,
                'con_biometria' => 0
            ];
            foreach ($logs as $l) {
                if (!empty($l['is_late'])) $stats['tardanzas']++;
                if (!empty($l['is_out_of_zone'])) $stats['fuera_zona']++;
                if (!empty($l['biometric_verified'])) $stats['con_biometria']++;
            }

            echo json_encode([
                'success' => true,
                'data' => $logs,
                'stats' => $stats
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error al obtener registros: ' . $e->getMessage()]);
        }
        exit;
    }

    // ==========================================
    // 5. MAPA EN TIEMPO REAL (LIVE ATTENDANCE MAP)
    // ==========================================
    if ($action === 'get_live_map_data') {
        try {
            $today = date('Y-m-d');
            
            // Obtener todos los usuarios activos
            $stmtUsers = $pdo->query("SELECT id, name, role, email, profile_picture FROM users WHERE is_active = 1 ORDER BY name ASC");
            $users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

            // Obtener última marcación de cada usuario hoy
            $liveData = [];
            foreach ($users as $u) {
                $uId = $u['id'];
                $stmtLast = $pdo->prepare("
                    SELECT a.*, g.name as geofence_name 
                    FROM attendance_logs a
                    LEFT JOIN attendance_geofences g ON a.geofence_id = g.id
                    WHERE a.user_id = ? AND DATE(a.created_at) = ?
                    ORDER BY a.created_at DESC LIMIT 1
                ");
                $stmtLast->execute([$uId, $today]);
                $lastLog = $stmtLast->fetch(PDO::FETCH_ASSOC);

                $status = 'sin_marcar';
                $statusColor = '#ef4444'; // Rojo
                $statusLabel = 'Sin Iniciar Labores';

                if ($lastLog) {
                    if ($lastLog['type'] === 'entrada' || $lastLog['type'] === 'fin_refrigerio') {
                        $status = 'activo';
                        $statusColor = '#10b981'; // Verde
                        $statusLabel = 'En Turno Activo';
                    } elseif ($lastLog['type'] === 'inicio_refrigerio') {
                        $status = 'refrigerio';
                        $statusColor = '#f59e0b'; // Amarillo
                        $statusLabel = 'En Refrigerio';
                    } elseif ($lastLog['type'] === 'salida') {
                        $status = 'finalizado';
                        $statusColor = '#64748b'; // Gris
                        $statusLabel = 'Jornada Finalizada';
                    }
                }

                $liveData[] = [
                    'user_id' => $u['id'],
                    'name' => $u['name'],
                    'role' => $u['role'],
                    'profile_picture' => $u['profile_picture'],
                    'status' => $status,
                    'status_color' => $statusColor,
                    'status_label' => $statusLabel,
                    'last_log' => $lastLog
                ];
            }

            // Geocercas para mostrar en el mapa
            $stmtGeo = $pdo->query("SELECT * FROM attendance_geofences WHERE is_active = 1");
            $geofences = $stmtGeo->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'technicians' => $liveData,
                'geofences' => $geofences
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error al cargar mapa en vivo: ' . $e->getMessage()]);
        }
        exit;
    }

    // ==========================================
    // 6. GESTIÓN DE GEOCERCAS (CRUD)
    // ==========================================
    if ($action === 'get_geofences') {
        try {
            $stmt = $pdo->query("SELECT * FROM attendance_geofences ORDER BY id DESC");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'save_geofence') {
        try {
            $geoId = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? 'Zona');
            $lat = (float)($_POST['latitude'] ?? 0);
            $lon = (float)($_POST['longitude'] ?? 0);
            $radius = (int)($_POST['radius_meters'] ?? 150);
            $isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

            if ($geoId > 0) {
                $stmt = $pdo->prepare("UPDATE attendance_geofences SET name = ?, latitude = ?, longitude = ?, radius_meters = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$name, $lat, $lon, $radius, $isActive, $geoId]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO attendance_geofences (name, latitude, longitude, radius_meters, is_active) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $lat, $lon, $radius, $isActive]);
            }

            echo json_encode(['success' => true, 'message' => 'Geocerca guardada con éxito.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error al guardar geocerca: ' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'delete_geofence') {
        try {
            $geoId = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare("DELETE FROM attendance_geofences WHERE id = ?");
            $stmt->execute([$geoId]);
            echo json_encode(['success' => true, 'message' => 'Geocerca eliminada.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // ==========================================
    // 7. GESTIÓN DE CONFIGURACIONES Y REGLAS
    // ==========================================
    if ($action === 'get_settings') {
        try {
            $settings = getAttendanceSettings($pdo);
            echo json_encode(['success' => true, 'data' => $settings]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'save_settings') {
        try {
            $settings = $_POST['settings'] ?? [];
            if (!is_array($settings)) {
                $settings = json_decode($settings, true) ?? [];
            }

            $stmt = $pdo->prepare("
                INSERT INTO attendance_settings (setting_key, setting_value) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ");

            foreach ($settings as $k => $v) {
                $stmt->execute([$k, (string)$v]);
            }

            echo json_encode(['success' => true, 'message' => 'Configuración de asistencia actualizada.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error al guardar ajustes: ' . $e->getMessage()]);
        }
        exit;
    }

    // ==========================================
    // 8. REPORTE PARA PLANILLAS (EXPORTACIÓN EXCEL/CSV)
    // ==========================================
    if ($action === 'export_payroll') {
        try {
            $startDate = $_REQUEST['start_date'] ?? date('Y-m-01');
            $endDate = $_REQUEST['end_date'] ?? date('Y-m-t');
            $filterUserId = $_REQUEST['user_id'] ?? '';

            $sql = "
                SELECT u.id as user_id, u.name as user_name, u.role,
                       DATE(a.created_at) as log_date,
                       MIN(CASE WHEN a.type = 'entrada' THEN a.created_at END) as hora_entrada,
                       MAX(CASE WHEN a.type = 'salida' THEN a.created_at END) as hora_salida,
                       MAX(a.is_late) as tuvo_tardanza,
                       SUM(a.minutes_late) as total_minutos_tardanza,
                       MAX(a.is_out_of_zone) as marco_fuera_zona
                FROM users u
                JOIN attendance_logs a ON u.id = a.user_id
                WHERE DATE(a.created_at) >= ? AND DATE(a.created_at) <= ?
            ";
            $params = [$startDate, $endDate];
            if (!empty($filterUserId)) {
                $sql .= " AND u.id = ?";
                $params[] = $filterUserId;
            }
            $sql .= " GROUP BY u.id, DATE(a.created_at) ORDER BY u.name ASC, log_date ASC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Calcular horas efectivas por día
            $report = [];
            foreach ($rows as $r) {
                $horasTrabajadas = 0;
                if (!empty($r['hora_entrada']) && !empty($r['hora_salida'])) {
                    $diffSec = strtotime($r['hora_salida']) - strtotime($r['hora_entrada']);
                    // Descontar 1 hora de almuerzo si trabajó más de 5 horas
                    if ($diffSec > 18000) {
                        $diffSec -= 3600;
                    }
                    $horasTrabajadas = round($diffSec / 3600, 2);
                }

                $report[] = [
                    'user_id' => $r['user_id'],
                    'name' => $r['user_name'],
                    'role' => $r['role'],
                    'date' => $r['log_date'],
                    'entrada' => $r['hora_entrada'] ? date('H:i:s', strtotime($r['hora_entrada'])) : '--:--',
                    'salida' => $r['hora_salida'] ? date('H:i:s', strtotime($r['hora_salida'])) : '--:--',
                    'horas_efectivas' => $horasTrabajadas,
                    'tardanza_min' => (int)$r['total_minutos_tardanza'],
                    'fuera_zona' => $r['marco_fuera_zona'] ? 'Sí' : 'No'
                ];
            }

            echo json_encode([
                'success' => true,
                'data' => $report,
                'period' => ['start' => $startDate, 'end' => $endDate]
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error al generar reporte: ' . $e->getMessage()]);
        }
        exit;
    }

    // ==========================================
    // 9. USUARIOS DISPONIBLES
    // ==========================================
    if ($action === 'get_users') {
        try {
            $stmt = $pdo->query("SELECT id, name, role FROM users WHERE is_active = 1 ORDER BY name ASC");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Acción inválida.']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
exit;
