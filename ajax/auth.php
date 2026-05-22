<?php
require_once '../config/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        $login_type = $_POST['login_type'] ?? 'email';
        
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
                echo json_encode(['success' => false, 'message' => 'Credenciales inválidas']);
            }
            exit;
        }
    }
}
echo json_encode(['success' => false, 'message' => 'Invalid request']);
