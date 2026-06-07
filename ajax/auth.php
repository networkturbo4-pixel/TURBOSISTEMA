<?php
require_once '../config/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        $login_type = $_POST['login_type'] ?? 'email';
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $identifier = $login_type === 'pin' ? ($_POST['pin'] ?? '') : ($_POST['email'] ?? '');

        // Create table for login attempts if it doesn't exist
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS login_attempts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ip_address VARCHAR(45) NOT NULL,
                identifier VARCHAR(255) NOT NULL,
                attempt_time DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            
            // Clean up old attempts (older than 15 mins)
            $pdo->exec("DELETE FROM login_attempts WHERE attempt_time < NOW() - INTERVAL 15 MINUTE");
        } catch (Exception $e) {}

        // Check if locked out (max 5 attempts in 15 mins)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE (ip_address = ? OR identifier = ?) AND attempt_time >= NOW() - INTERVAL 15 MINUTE");
        $stmt->execute([$ip_address, $identifier]);
        if ($stmt->fetchColumn() >= 5) {
            echo json_encode(['success' => false, 'message' => 'Demasiados intentos fallidos. Por favor, espera 15 minutos.']);
            exit;
        }

        if ($login_type === 'pin') {
            $pin = $_POST['pin'] ?? '';
            $stmt = $pdo->prepare("SELECT * FROM users WHERE pin = ?");
            $stmt->execute([$pin]);
            $user = $stmt->fetch();
            
            if ($user && !empty($pin)) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['profile_picture'] = $user['profile_picture'] ?? null;
                
                // Clear attempts on success
                $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ? OR identifier = ?")->execute([$ip_address, $identifier]);
                
                if (strtolower($user['role']) === 'cliente') {
                    $stmtC = $pdo->prepare("SELECT id FROM clientes WHERE user_id = ?");
                    $stmtC->execute([$user['id']]);
                    $cliente_id = $stmtC->fetchColumn();
                    if ($cliente_id) {
                        $_SESSION['public_cliente_id'] = $cliente_id;
                        $_SESSION['public_cliente_nombre'] = $user['name'];
                        echo json_encode(['success' => true, 'redirect' => 'portal.php']);
                        exit;
                    }
                }
                
                // Tecnico redirect (same dashboard, different view)
                echo json_encode(['success' => true, 'redirect' => 'index.php']);
            } else {
                // Log failed attempt
                $pdo->prepare("INSERT INTO login_attempts (ip_address, identifier) VALUES (?, ?)")->execute([$ip_address, $identifier]);
                echo json_encode(['success' => false, 'message' => 'PIN incorrecto o no existe.']);
            }
            exit;
        } else {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['profile_picture'] = $user['profile_picture'] ?? null;
                
                // Clear attempts on success
                $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ? OR identifier = ?")->execute([$ip_address, $identifier]);
                
                if (strtolower($user['role']) === 'cliente') {
                    $stmtC = $pdo->prepare("SELECT id FROM clientes WHERE user_id = ?");
                    $stmtC->execute([$user['id']]);
                    $cliente_id = $stmtC->fetchColumn();
                    if ($cliente_id) {
                        $_SESSION['public_cliente_id'] = $cliente_id;
                        $_SESSION['public_cliente_nombre'] = $user['name'];
                        echo json_encode(['success' => true, 'redirect' => 'portal.php']);
                        exit;
                    }
                }
                
                echo json_encode(['success' => true, 'redirect' => 'index.php']);
            } else {
                // Log failed attempt
                $pdo->prepare("INSERT INTO login_attempts (ip_address, identifier) VALUES (?, ?)")->execute([$ip_address, $identifier]);
                echo json_encode(['success' => false, 'message' => 'Credenciales inválidas']);
            }
            exit;
        }
    }
}
echo json_encode(['success' => false, 'message' => 'Invalid request']);
