<?php
require_once '../config/db.php';
require_once '../vendor/autoload.php';
requireLogin();

header('Content-Type: application/json');

use Rats\Zkteco\Lib\ZKTeco;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'sync') {
        try {
            // Get settings
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('zkteco_ip', 'zkteco_port')");
            $settings = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }

            $ip = $settings['zkteco_ip'] ?? '192.168.1.201';
            $port = $settings['zkteco_port'] ?? '4370';

            $zk = new ZKTeco($ip, $port);
            if (!$zk->connect()) {
                echo json_encode(['success' => false, 'message' => "No se pudo conectar al dispositivo ZKTeco en $ip:$port. Verifica que esté en red."]);
                exit;
            }

            // Opcional: Deshabilitar el dispositivo mientras leemos
            $zk->disableDevice();
            $attendance = $zk->getAttendance();
            $zk->enableDevice();
            $zk->disconnect();

            if (empty($attendance)) {
                echo json_encode(['success' => true, 'message' => 'No hay nuevos registros en el dispositivo.', 'inserted' => 0]);
                exit;
            }

            // Map ZKTeco states to our enum
            // States: 0 = Check In, 1 = Check Out, 4 = Break Out, 5 = Break In (Some devices use 2 and 3)
            // We will map: 0->entrada, 1->salida, 4->inicio_refrigerio, 5->fin_refrigerio, 2->inicio_refrigerio, 3->fin_refrigerio
            $stateMap = [
                0 => 'entrada',
                1 => 'salida',
                2 => 'inicio_refrigerio',
                3 => 'fin_refrigerio',
                4 => 'inicio_refrigerio',
                5 => 'fin_refrigerio'
            ];

            // Get users with biometric_id
            $stmt = $pdo->query("SELECT id, biometric_id FROM users WHERE biometric_id IS NOT NULL");
            $usersMap = [];
            while ($u = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $usersMap[(string)$u['biometric_id']] = $u['id'];
            }

            $insertedCount = 0;
            $checkStmt = $pdo->prepare("SELECT id FROM attendance_logs WHERE user_id = ? AND created_at = ?");
            $insertStmt = $pdo->prepare("INSERT INTO attendance_logs (user_id, type, created_at) VALUES (?, ?, ?)");

            foreach ($attendance as $log) {
                // $log['id'] is the User ID in ZKTeco
                // $log['state'] is the punch state
                // $log['timestamp'] is the time "Y-m-d H:i:s"
                $bioId = (string)$log['id'];
                if (!isset($usersMap[$bioId])) {
                    continue; // Usuario no mapeado en el sistema
                }

                $userId = $usersMap[$bioId];
                $type = $stateMap[$log['state']] ?? 'desconocido';
                $timestamp = $log['timestamp'];

                // Evitar duplicados
                $checkStmt->execute([$userId, $timestamp]);
                if (!$checkStmt->fetch()) {
                    $insertStmt->execute([$userId, $type, $timestamp]);
                    $insertedCount++;
                }
            }

            // Optional: $zk->clearAttendance() if we want to empty device memory
            echo json_encode(['success' => true, 'message' => "Sincronización completa. $insertedCount nuevos registros agregados.", 'inserted' => $insertedCount]);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error durante la sincronización: ' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'save_settings') {
        $ip = trim($_POST['ip'] ?? '');
        $port = trim($_POST['port'] ?? '');
        if($ip && $port) {
            $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'zkteco_ip'")->execute([$ip]);
            $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'zkteco_port'")->execute([$port]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'IP y Puerto son requeridos.']);
        }
        exit;
    }
}
