<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        $pin = trim($_POST['pin'] ?? '');
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        // Clean up old attempts (older than 15 mins)
        try {
            $pdo->exec("DELETE FROM login_attempts WHERE attempt_time < NOW() - INTERVAL 15 MINUTE");
        } catch (Exception $e) {}

        // Check if locked out (max 5 attempts in 15 mins)
        $stmtAttempts = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE (ip_address = ? OR identifier = ?) AND attempt_time >= NOW() - INTERVAL 15 MINUTE");
        $stmtAttempts->execute([$ip_address, $pin]);
        $attemptsCount = (int)$stmtAttempts->fetchColumn();

        if ($attemptsCount >= 5) {
            echo json_encode([
                'success' => false, 
                'locked' => true,
                'message' => 'Demasiados intentos fallidos. Por seguridad, espera 15 minutos.'
            ]);
            exit;
        }

        if (empty($pin)) {
            echo json_encode(['success' => false, 'message' => 'Por favor, ingresa tu PIN de 8 dígitos.']);
            exit;
        }

        if (!preg_match('/^[0-9]{8}$/', $pin)) {
            echo json_encode(['success' => false, 'message' => 'El PIN debe ser un código numérico de 8 dígitos.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT * FROM users WHERE pin = ?");
        $stmt->execute([$pin]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Success: establish session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['profile_picture'] = $user['profile_picture'] ?? null;

            // Clear attempts on success
            $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ? OR identifier = ?")->execute([$ip_address, $pin]);

            $roleLower = strtolower(trim($user['role'] ?? ''));

            if ($roleLower === 'cliente') {
                $stmtC = $pdo->prepare("SELECT id FROM clientes WHERE user_id = ?");
                $stmtC->execute([$user['id']]);
                $cliente_id = $stmtC->fetchColumn();
                if ($cliente_id) {
                    $_SESSION['public_cliente_id'] = $cliente_id;
                    $_SESSION['public_cliente_nombre'] = $user['name'];
                    echo json_encode(['success' => true, 'redirect' => 'portal.php', 'user_name' => $user['name']]);
                    exit;
                }
            } elseif (in_array($roleLower, ['tecnico', 'técnico'])) {
                echo json_encode(['success' => true, 'redirect' => 'tecnico.php', 'user_name' => $user['name']]);
                exit;
            }

            echo json_encode(['success' => true, 'redirect' => 'index.php', 'user_name' => $user['name']]);
            exit;
        } else {
            // Log failed attempt
            $pdo->prepare("INSERT INTO login_attempts (ip_address, identifier) VALUES (?, ?)")->execute([$ip_address, $pin]);
            $remaining = max(0, 5 - ($attemptsCount + 1));
            $msg = 'PIN incorrecto o no registrado.';
            if ($remaining > 0) {
                $msg .= " Te quedan {$remaining} intento(s).";
            } else {
                $msg = 'Has superado el límite de intentos. Espera 15 minutos.';
            }
            echo json_encode(['success' => false, 'message' => $msg, 'remaining' => $remaining]);
            exit;
        }
    }
}

echo json_encode(['success' => false, 'message' => 'Petición inválida']);
