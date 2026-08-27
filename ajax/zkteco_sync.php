<?php
require_once '../config/db.php';
require_once '../vendor/autoload.php';
requireLogin();

header('Content-Type: application/json');
ini_set('display_errors', '0'); // Evitar que warnings de PHP rompan el JSON

use Rats\Zkteco\Lib\ZKTeco;

function getZkSettings($pdo) {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('zkteco_ip', 'zkteco_port')");
    $settings = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    return [
        'ip' => $settings['zkteco_ip'] ?? '192.168.1.150', // Por defecto a la mostrada en la foto
        'port' => $settings['zkteco_port'] ?? '4370'
    ];
}

function connectZk($ip, $port) {
    session_write_close(); // Evitar bloqueo de sesión
    ini_set('default_socket_timeout', 5);
    $zk = new ZKTeco($ip, $port);
    if (!$zk->connect()) {
        throw new Exception("No se pudo conectar al dispositivo ZKTeco en $ip:$port.");
    }
    return $zk;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    // Obtener la configuración (Para el frontend)
    if ($action === 'get_settings') {
        $settings = getZkSettings($pdo);
        echo json_encode(['success' => true, 'data' => $settings]);
        exit;
    }

    // Guardar configuración
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

    // Obtener estado del dispositivo
    if ($action === 'device_status') {
        try {
            $settings = getZkSettings($pdo);
            $zk = connectZk($settings['ip'], $settings['port']);
            
            $data = [
                'deviceName' => $zk->deviceName(),
                'serialNumber' => $zk->serialNumber(),
                'version' => $zk->version(),
                'platform' => $zk->platform(),
                'deviceTime' => $zk->getTime(),
                'ip' => $settings['ip'],
                'port' => $settings['port']
            ];
            $zk->disconnect();
            echo json_encode(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // Sincronizar Asistencia
    if ($action === 'sync') {
        try {
            $settings = getZkSettings($pdo);
            $zk = connectZk($settings['ip'], $settings['port']);
            
            $zk->disableDevice();
            $attendance = $zk->getAttendance();
            $zk->enableDevice();
            $zk->disconnect();

            if (empty($attendance)) {
                echo json_encode(['success' => true, 'message' => 'No hay nuevos registros en el dispositivo.', 'inserted' => 0]);
                exit;
            }

            $stateMap = [
                0 => 'entrada',
                1 => 'salida',
                2 => 'inicio_refrigerio',
                3 => 'fin_refrigerio',
                4 => 'inicio_refrigerio',
                5 => 'fin_refrigerio'
            ];

            $usersMap = [];
            try {
                $stmt = $pdo->query("SELECT id, biometric_id FROM users WHERE biometric_id IS NOT NULL");
                while ($u = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    if (!empty($u['biometric_id'])) {
                        $usersMap[(string)$u['biometric_id']] = $u['id'];
                    }
                }
            } catch (Exception $e) {}

            $insertedCount = 0;
            $checkStmt = $pdo->prepare("SELECT id FROM attendance_logs WHERE user_id = ? AND created_at = ?");
            $insertStmt = $pdo->prepare("INSERT INTO attendance_logs (user_id, type, created_at) VALUES (?, ?, ?)");

            foreach ($attendance as $log) {
                $bioId = (string)$log['id'];
                if (!isset($usersMap[$bioId])) {
                    continue;
                }

                $userId = $usersMap[$bioId];
                $type = $stateMap[$log['state']] ?? 'desconocido';
                $timestamp = $log['timestamp'];

                $checkStmt->execute([$userId, $timestamp]);
                if (!$checkStmt->fetch()) {
                    $insertStmt->execute([$userId, $type, $timestamp]);
                    $insertedCount++;
                }
            }

            echo json_encode(['success' => true, 'message' => "Sincronización completa. $insertedCount nuevos registros agregados.", 'inserted' => $insertedCount]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error durante la sincronización: ' . $e->getMessage()]);
        }
        exit;
    }

    // Obtener usuarios del dispositivo
    if ($action === 'get_device_users') {
        try {
            $settings = getZkSettings($pdo);
            $zk = connectZk($settings['ip'], $settings['port']);
            
            $zk->disableDevice();
            $deviceUsers = $zk->getUser(); // Devuelve array
            $zk->enableDevice();
            $zk->disconnect();

            echo json_encode(['success' => true, 'data' => $deviceUsers]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // Enviar un usuario al dispositivo
    if ($action === 'push_user') {
        $user_id = $_POST['user_id'] ?? null;
        if (!$user_id) {
            echo json_encode(['success' => false, 'message' => 'User ID no proporcionado']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("SELECT id, name, biometric_id FROM users WHERE id = ? AND biometric_id IS NOT NULL");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                echo json_encode(['success' => false, 'message' => 'El usuario no existe o no tiene un ID biométrico asignado.']);
                exit;
            }

            $settings = getZkSettings($pdo);
            $zk = connectZk($settings['ip'], $settings['port']);
            
            // $uid, $userid, $name, $password, $role
            $uid = (int)$user['biometric_id'];
            $userid = (string)$user['biometric_id']; // Suele ser igual
            $name = substr($user['name'], 0, 24); // ZKTeco limita nombre a 24 caracteres

            // role: 0 = User, 14 = Admin (Util::LEVEL_USER)
            $zk->setUser($uid, $userid, $name, '', 0);
            
            $zk->disconnect();
            echo json_encode(['success' => true, 'message' => 'Usuario enviado al dispositivo.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // Enviar todos los usuarios con biometric_id al dispositivo
    if ($action === 'push_all_users') {
        try {
            $stmt = $pdo->query("SELECT id, name, biometric_id FROM users WHERE biometric_id IS NOT NULL");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($users)) {
                echo json_encode(['success' => false, 'message' => 'No hay usuarios con ID biométrico asignado en el sistema.']);
                exit;
            }

            $settings = getZkSettings($pdo);
            $zk = connectZk($settings['ip'], $settings['port']);
            $zk->disableDevice();

            $pushed = 0;
            foreach ($users as $user) {
                $uid = (int)$user['biometric_id'];
                $userid = (string)$user['biometric_id'];
                $name = substr($user['name'], 0, 24);
                $zk->setUser($uid, $userid, $name, '', 0);
                $pushed++;
            }
            
            $zk->enableDevice();
            $zk->disconnect();
            echo json_encode(['success' => true, 'message' => "$pushed usuarios enviados al dispositivo."]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // Eliminar un usuario del dispositivo
    if ($action === 'remove_device_user') {
        $uid = $_POST['uid'] ?? null;
        if (!$uid) {
            echo json_encode(['success' => false, 'message' => 'UID no proporcionado']);
            exit;
        }

        try {
            $settings = getZkSettings($pdo);
            $zk = connectZk($settings['ip'], $settings['port']);
            
            $zk->removeUser((int)$uid);
            
            $zk->disconnect();
            echo json_encode(['success' => true, 'message' => 'Usuario eliminado del dispositivo.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // Sincronizar hora
    if ($action === 'sync_time') {
        try {
            $settings = getZkSettings($pdo);
            $zk = connectZk($settings['ip'], $settings['port']);
            
            // Format: Y-m-d H:i:s
            $now = date('Y-m-d H:i:s');
            $zk->setTime($now);
            
            $zk->disconnect();
            echo json_encode(['success' => true, 'message' => 'Hora del dispositivo sincronizada.', 'time' => $now]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}
