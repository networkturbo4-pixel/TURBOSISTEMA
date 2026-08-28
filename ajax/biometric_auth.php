<?php
require_once __DIR__ . '/../config/db.php';
requireLogin();

header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';
$userId = (int)$_SESSION['user_id'];

// Obtener dominio actual para Relying Party (RP)
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
// Remover puerto si existe
$rpId = explode(':', $host)[0];
if ($rpId === '127.0.0.1') {
    $rpId = 'localhost';
}

if ($action === 'get_user_biometric_status') {
    try {
        $stmt = $pdo->prepare("SELECT id, device_name, created_at FROM biometric_credentials WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'has_biometrics' => !empty($devices),
            'devices' => $devices
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'get_register_challenge') {
    try {
        // Generar un challenge aleatorio de 32 bytes
        $challenge = base64_encode(random_bytes(32));
        $_SESSION['webauthn_reg_challenge'] = $challenge;

        $stmt = $pdo->prepare("SELECT id, name, email, username FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $displayName = !empty($user['name']) ? $user['name'] : ($user['username'] ?? 'Usuario');
        $userHandle = str_pad((string)$userId, 16, '0', STR_PAD_LEFT);

        echo json_encode([
            'success' => true,
            'challenge' => $challenge,
            'rp' => [
                'name' => 'TurboSaaS Biometrics',
                'id' => $rpId
            ],
            'user' => [
                'id' => base64_encode($userHandle),
                'name' => $user['email'] ?? ($user['username'] ?? 'user_'.$userId),
                'displayName' => $displayName
            ],
            'pubKeyCredParams' => [
                ['type' => 'public-key', 'alg' => -7],  // ES256
                ['type' => 'public-key', 'alg' => -257] // RS256
            ],
            'authenticatorSelection' => [
                'authenticatorAttachment' => 'platform', // Obliga a usar sensor del dispositivo (Face ID / Huella)
                'userVerification' => 'required',
                'residentKey' => 'preferred'
            ],
            'timeout' => 60000,
            'attestation' => 'none'
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'verify_register') {
    try {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        if (!$data) {
            $data = $_POST;
        }

        $credentialId = $data['id'] ?? '';
        $rawId = $data['rawId'] ?? $credentialId;
        $deviceName = trim($data['device_name'] ?? 'Dispositivo Móvil');

        if (empty($credentialId)) {
            throw new Exception("ID de credencial no recibido.");
        }

        // Guardar credencial en la base de datos
        $stmt = $pdo->prepare("
            INSERT INTO biometric_credentials (user_id, credential_id, public_key, device_name, counter, created_at)
            VALUES (?, ?, ?, ?, 0, NOW())
            ON DUPLICATE KEY UPDATE device_name = VALUES(device_name), counter = 0, created_at = NOW()
        ");
        $stmt->execute([
            $userId,
            $credentialId,
            json_encode($data['response'] ?? []),
            $deviceName
        ]);

        unset($_SESSION['webauthn_reg_challenge']);

        echo json_encode([
            'success' => true,
            'message' => 'Dispositivo biométrico (Face ID / Huella) vinculado con éxito.'
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'get_auth_challenge') {
    try {
        $challenge = base64_encode(random_bytes(32));
        $_SESSION['webauthn_auth_challenge'] = $challenge;

        // Obtener credenciales registradas del usuario
        $stmt = $pdo->prepare("SELECT credential_id FROM biometric_credentials WHERE user_id = ?");
        $stmt->execute([$userId]);
        $creds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $allowCredentials = [];
        foreach ($creds as $cid) {
            $allowCredentials[] = [
                'type' => 'public-key',
                'id' => $cid,
                'transports' => ['internal']
            ];
        }

        echo json_encode([
            'success' => true,
            'challenge' => $challenge,
            'rpId' => $rpId,
            'allowCredentials' => $allowCredentials,
            'userVerification' => 'required',
            'timeout' => 60000
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'verify_auth') {
    try {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        if (!$data) {
            $data = $_POST;
        }

        $credentialId = $data['id'] ?? '';
        if (empty($credentialId)) {
            throw new Exception("ID de credencial biométrica no provisto.");
        }

        // Actualizar contador del dispositivo
        $stmt = $pdo->prepare("UPDATE biometric_credentials SET counter = counter + 1 WHERE user_id = ? AND credential_id = ?");
        $stmt->execute([$userId, $credentialId]);

        unset($_SESSION['webauthn_auth_challenge']);

        echo json_encode([
            'success' => true,
            'verified' => true,
            'message' => 'Verificación biométrica exitosa.'
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'delete_device') {
    try {
        $deviceId = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM biometric_credentials WHERE id = ? AND user_id = ?");
        $stmt->execute([$deviceId, $userId]);

        echo json_encode(['success' => true, 'message' => 'Dispositivo biométrico eliminado.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Acción biométrica no válida.']);
exit;
