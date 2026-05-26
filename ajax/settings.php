<?php
require_once '../config/db.php';

header('Content-Type: application/json');

// Verificar permisos (asumimos que solo el admin puede cambiar la config, o basados en el rol)
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'get_settings') {
    try {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        echo json_encode(['success' => true, 'data' => $settings]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener configuraciones: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'save_settings') {
    $settings = $_POST['settings'] ?? [];
    if (is_string($settings)) {
        $settings = json_decode($settings, true);
    }

    // Handle file uploads
    $upload_dir = '../uploads/settings/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $files_to_upload = ['logo_light', 'logo_dark', 'logo_collapsed_light', 'logo_collapsed_dark', 'logo_pwa', 'favicon'];
    foreach ($files_to_upload as $file_key) {
        if (isset($_POST["delete_$file_key"]) && $_POST["delete_$file_key"] === '1') {
            $settings[$file_key] = '';
        }
        
        if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES[$file_key]['tmp_name'];
            $name = basename($_FILES[$file_key]['name']);
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $allowed_ext = ['png', 'jpg', 'jpeg', 'svg', 'webp', 'ico'];
            
            if (in_array($ext, $allowed_ext)) {
                $new_filename = $file_key . '_' . time() . '.' . $ext;
                $target_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($tmp_name, $target_path)) {
                    $settings[$file_key] = 'uploads/settings/' . $new_filename;
                }
            }
        }
    }

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        
        foreach ($settings as $key => $value) {
            $stmt->execute([$key, $value, $value]);
        }
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Configuración guardada correctamente.']);
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error al guardar: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Acción no válida']);
