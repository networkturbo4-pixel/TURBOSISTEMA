<?php
// Evita el almacenamiento en caché y configura las cabeceras para SSE
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
// Deshabilita el buffering de salida para SSE
if (ob_get_level()) ob_end_clean();

require_once '../config/db.php';

// ¡IMPORTANTE! Liberar la sesión para no bloquear otras peticiones AJAX del mismo usuario
session_write_close();

$ticket_id = isset($_GET['ticket_id']) ? (int)$_GET['ticket_id'] : 0;
$last_id = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;
$token = $_GET['token'] ?? '';
$user_id = $_SESSION['user_id'] ?? null;
$is_client = !$user_id;

if ($ticket_id <= 0) {
    echo "data: {\"error\": \"Invalid ticket\"}\n\n";
    flush();
    exit;
}

function isTokenValid($pdo, $ticket_id, $token) {
    $stmt = $pdo->prepare("SELECT public_token FROM tickets WHERE id = ?");
    $stmt->execute([$ticket_id]);
    $real = $stmt->fetchColumn();
    return $real && $real === $token;
}

if ($is_client && !isTokenValid($pdo, $ticket_id, $token)) {
    echo "data: {\"error\": \"Unauthorized\"}\n\n";
    flush();
    exit;
}

// Límite de ejecución de 5 minutos, el navegador reconectará automáticamente
set_time_limit(300);
$start_time = time();

while (true) {
    // 1. Buscar nuevos mensajes
    $stmt = $pdo->prepare("
        SELECT m.*, u.name as user_name 
        FROM ticket_messages m
        LEFT JOIN users u ON m.user_id = u.id
        WHERE m.ticket_id = ? AND m.id > ?
        ORDER BY m.id ASC
    ");
    $stmt->execute([$ticket_id, $last_id]);
    $new_messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($new_messages)) {
        // Adjuntos
        $msg_ids = array_column($new_messages, 'id');
        $in  = str_repeat('?,', count($msg_ids) - 1) . '?';
        $stmtAtt = $pdo->prepare("SELECT * FROM ticket_attachments WHERE message_id IN ($in)");
        $stmtAtt->execute($msg_ids);
        $atts = $stmtAtt->fetchAll(PDO::FETCH_ASSOC);
        
        $atts_by_msg = [];
        foreach($atts as $a) {
            $atts_by_msg[$a['message_id']][] = $a;
        }
        
        foreach($new_messages as &$msg) {
            $msg['attachments'] = $atts_by_msg[$msg['id']] ?? [];
            $last_id = max($last_id, $msg['id']);
        }

        echo "event: new_messages\n";
        echo "data: " . json_encode($new_messages) . "\n\n";
    }

    // 2. Verificar mensajes leídos (Read Receipts)
    // Buscamos si algún mensaje que el usuario actual envió (y estaba sin leer) ha sido leído
    if ($is_client) {
        // El cliente verifica si el técnico ha leído sus mensajes (donde user_id es null)
        $stmtRead = $pdo->prepare("SELECT id FROM ticket_messages WHERE ticket_id = ? AND user_id IS NULL AND is_read = 1 AND id <= ? ORDER BY id DESC LIMIT 1");
        $stmtRead->execute([$ticket_id, $last_id]);
    } else {
        // El técnico verifica si el cliente ha leído sus mensajes (donde user_id = $user_id)
        $stmtRead = $pdo->prepare("SELECT id FROM ticket_messages WHERE ticket_id = ? AND user_id = ? AND is_read = 1 AND id <= ? ORDER BY id DESC LIMIT 1");
        $stmtRead->execute([$ticket_id, $user_id, $last_id]);
    }
    
    $last_read_id = $stmtRead->fetchColumn() ?: 0;

    // 3. Verificar estado "Escribiendo..."
    $stmtTkt = $pdo->prepare("SELECT tech_typing_at, client_typing_at FROM tickets WHERE id = ?");
    $stmtTkt->execute([$ticket_id]);
    $tkt = $stmtTkt->fetch(PDO::FETCH_ASSOC);
    
    $is_typing = false;
    if ($tkt) {
        $now = time();
        if ($is_client) {
            if ($tkt['tech_typing_at'] && ($now - strtotime($tkt['tech_typing_at']) <= 3)) $is_typing = true;
        } else {
            if ($tkt['client_typing_at'] && ($now - strtotime($tkt['client_typing_at']) <= 3)) $is_typing = true;
        }
    }

    $status_data = [
        'is_typing' => $is_typing,
        'last_read_id' => $last_read_id
    ];
    
    echo "event: status_update\n";
    echo "data: " . json_encode($status_data) . "\n\n";

    flush();
    
    if (time() - $start_time > 270) {
        // Romper el ciclo después de 4.5 minutos para forzar reconexión y evitar procesos zombis colgados.
        break;
    }

    // Esperar 1.5 segundos antes de volver a consultar
    usleep(1500000);
}
?>
