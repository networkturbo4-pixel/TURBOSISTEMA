<?php
require_once '../config/db.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$token = $_POST['token'] ?? '';
$is_public = in_array($action, ['public_login', 'create_public_ticket']);
$has_session = isset($_SESSION['user_id']);

if (!$is_public && !$has_session && empty($token)) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? 'user';

// Helper for token validation
function validateToken($pdo, $ticket_id, $token) {
    $stmt = $pdo->prepare("SELECT public_token FROM tickets WHERE id = ?");
    $stmt->execute([$ticket_id]);
    $real_token = $stmt->fetchColumn();
    return $real_token && $real_token === $token;
}

try {
    switch ($action) {
        case 'list':
            if (!$has_session) throw new Exception('No autorizado');
            
            // Auto-cleanup: Eliminar permanentemente tickets en papelera de más de 15 días
            try { $pdo->query("DELETE FROM tickets WHERE estado = 'eliminado' AND updated_at < DATE_SUB(NOW(), INTERVAL 15 DAY)"); } catch(Exception $e) { /* ignore */ }
            
            $where = "1=1";
            $is_trash = intval($_POST['is_trash'] ?? 0);
            if ($is_trash === 1) {
                $where .= " AND t.estado = 'eliminado'";
            } else {
                $where .= " AND t.estado != 'eliminado'";
            }
            
            if ($user_role !== 'admin' && $user_role !== 'administrador') {
                $where .= " AND (t.assigned_to = $user_id OR t.assigned_to IS NULL)"; 
            }

            $stmt = $pdo->prepare("
                SELECT t.*, c.nombre_completo as cliente_nombre, c.celular as cliente_celular, c.direccion as cliente_direccion, u.name as tech_name,
                       tc.name as cat_name, tc.color as cat_color,
                       tp.name as pri_name, tp.color as pri_color,
                       (SELECT COUNT(*) FROM ticket_messages m WHERE m.ticket_id = t.id AND (m.user_id != ? OR m.user_id IS NULL) AND m.is_read = FALSE) as unread_count
                FROM tickets t
                LEFT JOIN clientes c ON t.cliente_id = c.id
                LEFT JOIN users u ON t.assigned_to = u.id
                LEFT JOIN ticket_categories tc ON t.categoria_id = tc.id
                LEFT JOIN ticket_priorities tp ON t.prioridad_id = tp.id
                WHERE $where
                ORDER BY t.created_at DESC
            ");
            $stmt->execute([$user_id]);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        case 'public_login':
            $dni = $_POST['dni'] ?? '';
            $stmt = $pdo->prepare("SELECT id, nombre_completo, celular FROM clientes WHERE dni = ?");
            $stmt->execute([$dni]);
            $cliente = $stmt->fetch();
            if ($cliente) {
                $_SESSION['public_cliente_id'] = $cliente['id'];
                $_SESSION['public_cliente_nombre'] = $cliente['nombre_completo'];
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'No es cliente de la empresa o DNI incorrecto.']);
            }
            break;

        case 'create_public_ticket':
            $cliente_id = !empty($_POST['cliente_id']) ? $_POST['cliente_id'] : null;
            $asunto = $_POST['asunto'] ?? '';
            $categoria_id = !empty($_POST['categoria_id']) ? $_POST['categoria_id'] : null;
            $prioridad_id = !empty($_POST['prioridad_id']) ? $_POST['prioridad_id'] : null;
            $descripcion = $_POST['descripcion'] ?? '';

            if (empty($asunto) || empty($cliente_id) || empty($descripcion)) {
                throw new Exception('Campos requeridos faltantes.');
            }

            $token = bin2hex(random_bytes(16));

            $stmt = $pdo->prepare("INSERT INTO tickets (cliente_id, asunto, categoria_id, prioridad_id, public_token, descripcion) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$cliente_id, $asunto, $categoria_id, $prioridad_id, $token, $descripcion]);
            
            $ticket_id = $pdo->lastInsertId();
            $stmtMsg = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, user_id, message, is_system_message) VALUES (?, NULL, ?, 0)");
            $stmtMsg->execute([$ticket_id, $descripcion]); // user_id is NULL for client messages created from public portal

            echo json_encode(['success' => true, 'ticket_id' => $ticket_id, 'token' => $token]);
            break;

        case 'save':
            $cliente_id = !empty($_POST['cliente_id']) ? $_POST['cliente_id'] : null;
            $asunto = $_POST['asunto'] ?? '';
            $categoria_id = !empty($_POST['categoria_id']) ? $_POST['categoria_id'] : null;
            $prioridad_id = !empty($_POST['prioridad_id']) ? $_POST['prioridad_id'] : null;
            $assigned_to = !empty($_POST['assigned_to']) ? $_POST['assigned_to'] : null;
            $descripcion = $_POST['descripcion'] ?? '';

            if (empty($asunto)) throw new Exception('El asunto es requerido');

            $stmt = $pdo->prepare("INSERT INTO tickets (cliente_id, asunto, categoria_id, prioridad_id, assigned_to, descripcion) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$cliente_id, $asunto, $categoria_id, $prioridad_id, $assigned_to, $descripcion]);
            
            // Insert initial message as description
            $ticket_id = $pdo->lastInsertId();
            $stmtMsg = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, user_id, message, is_system_message) VALUES (?, ?, ?, 0)");
            $stmtMsg->execute([$ticket_id, $user_id, $descripcion]);

            echo json_encode(['success' => true, 'message' => 'Ticket creado']);
            break;

        case 'update_status':
            $ticket_id = $_POST['ticket_id'] ?? 0;
            $estado = $_POST['estado'] ?? 'abierto';
            $stmt = $pdo->prepare("UPDATE tickets SET estado = ? WHERE id = ?");
            $stmt->execute([$estado, $ticket_id]);
            
            // System message
            $msg = "El estado del ticket ha cambiado a: " . strtoupper($estado);
            $stmtSys = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, user_id, message, is_system_message) VALUES (?, NULL, ?, 1)");
            $stmtSys->execute([$ticket_id, $msg]);

            echo json_encode(['success' => true]);
            break;

        case 'trash_ticket':
            $ticket_id = $_POST['ticket_id'] ?? 0;
            $stmt = $pdo->prepare("UPDATE tickets SET estado = 'eliminado', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$ticket_id]);
            echo json_encode(['success' => true, 'message' => 'Ticket movido a la papelera']);
            break;

        case 'hard_delete_ticket':
            $ticket_id = $_POST['ticket_id'] ?? 0;
            $stmt = $pdo->prepare("DELETE FROM tickets WHERE id = ?");
            $stmt->execute([$ticket_id]);
            echo json_encode(['success' => true, 'message' => 'Ticket eliminado permanentemente']);
            break;

        case 'assign_ticket':
            $ticket_id = $_POST['ticket_id'] ?? 0;
            $assigned_to = !empty($_POST['assigned_to']) ? $_POST['assigned_to'] : null;
            $stmt = $pdo->prepare("UPDATE tickets SET assigned_to = ? WHERE id = ?");
            $stmt->execute([$assigned_to, $ticket_id]);
            
            // System message
            if ($assigned_to) {
                $stmtTech = $pdo->prepare("SELECT name FROM users WHERE id = ?");
                $stmtTech->execute([$assigned_to]);
                $techName = $stmtTech->fetchColumn();
                $msg = "El ticket ha sido asignado a: " . $techName;
            } else {
                $msg = "El ticket ya no tiene un técnico asignado.";
            }
            $stmtSys = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, user_id, message, is_system_message) VALUES (?, NULL, ?, 1)");
            $stmtSys->execute([$ticket_id, $msg]);

            echo json_encode(['success' => true]);
            break;

        case 'get_messages':
            $ticket_id = $_POST['ticket_id'] ?? 0;
            $last_id = $_POST['last_id'] ?? 0;
            
            if (!$has_session && !validateToken($pdo, $ticket_id, $token)) throw new Exception('No autorizado');

            // Marcar como leídos los que no sean del usuario actual (o nulo si es admin, etc)
            if ($has_session) {
                $pdo->prepare("UPDATE ticket_messages SET is_read = TRUE WHERE ticket_id = ? AND (user_id != ? OR user_id IS NULL) AND is_read = FALSE")->execute([$ticket_id, $user_id]);
            } else {
                // El cliente marca como leídos los mensajes que tienen un user_id (que son de técnicos/admin)
                $pdo->prepare("UPDATE ticket_messages SET is_read = TRUE WHERE ticket_id = ? AND user_id IS NOT NULL AND is_read = FALSE")->execute([$ticket_id]);
            }

            $stmt = $pdo->prepare("
                SELECT m.*, u.name as user_name 
                FROM ticket_messages m
                LEFT JOIN users u ON m.user_id = u.id
                WHERE m.ticket_id = ? AND m.id > ?
                ORDER BY m.id ASC
            ");
            $stmt->execute([$ticket_id, $last_id]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Fetch attachments
            if (!empty($messages)) {
                $msg_ids = array_column($messages, 'id');
                $in  = str_repeat('?,', count($msg_ids) - 1) . '?';
                $stmtAtt = $pdo->prepare("SELECT * FROM ticket_attachments WHERE message_id IN ($in)");
                $stmtAtt->execute($msg_ids);
                $atts = $stmtAtt->fetchAll(PDO::FETCH_ASSOC);
                
                $atts_by_msg = [];
                foreach($atts as $a) {
                    $atts_by_msg[$a['message_id']][] = $a;
                }
                
                foreach($messages as &$msg) {
                    $msg['attachments'] = $atts_by_msg[$msg['id']] ?? [];
                }
            }

            echo json_encode(['success' => true, 'data' => $messages]);
            break;

        case 'mark_as_read':
            $ticket_id = $_POST['ticket_id'] ?? 0;
            if (!$has_session && !validateToken($pdo, $ticket_id, $token)) throw new Exception('No autorizado');

            if ($has_session) {
                $pdo->prepare("UPDATE ticket_messages SET is_read = TRUE WHERE ticket_id = ? AND (user_id != ? OR user_id IS NULL) AND is_read = FALSE")->execute([$ticket_id, $user_id]);
            } else {
                $pdo->prepare("UPDATE ticket_messages SET is_read = TRUE WHERE ticket_id = ? AND user_id IS NOT NULL AND is_read = FALSE")->execute([$ticket_id]);
            }
            echo json_encode(['success' => true]);
            break;

        case 'send_message':
            $ticket_id = $_POST['ticket_id'] ?? 0;
            $message = $_POST['message'] ?? '';
            
            $has_attachment = isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK;
            
            if (empty($message) && !$has_attachment) throw new Exception('Mensaje inválido');
            if (!$has_session && !validateToken($pdo, $ticket_id, $token)) throw new Exception('No autorizado');

            $sender_id = $has_session ? $user_id : null;

            $stmt = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, user_id, message) VALUES (?, ?, ?)");
            $stmt->execute([$ticket_id, $sender_id, $message]);
            $message_id = $pdo->lastInsertId();
            
            // Handle Attachment
            if ($has_attachment) {
                $uploadDir = '../uploads/tickets/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                
                $file = $_FILES['attachment'];
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $fileName = 'tkt_' . $ticket_id . '_' . time() . '_' . uniqid() . '.' . $ext;
                $targetPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                    $dbPath = 'uploads/tickets/' . $fileName;
                    $stmtAtt = $pdo->prepare("INSERT INTO ticket_attachments (message_id, file_path, file_name) VALUES (?, ?, ?)");
                    $stmtAtt->execute([$message_id, $dbPath, $file['name']]);
                }
            }
            
            // Actualizar updated_at del ticket
            $pdo->prepare("UPDATE tickets SET updated_at = NOW() WHERE id = ?")->execute([$ticket_id]);

            echo json_encode(['success' => true]);
            break;

        // --- AJUSTES: CATEGORÍAS Y PRIORIDADES ---
        case 'save_category':
            $name = $_POST['name'] ?? '';
            $color = $_POST['color'] ?? '#3b82f6';
            if (empty($name)) throw new Exception('Nombre requerido');
            $pdo->prepare("INSERT INTO ticket_categories (name, color) VALUES (?, ?)")->execute([$name, $color]);
            echo json_encode(['success' => true]);
            break;
            
        case 'delete_category':
            $id = $_POST['id'] ?? 0;
            $pdo->prepare("DELETE FROM ticket_categories WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true]);
            break;
            
        case 'save_priority':
            $name = $_POST['name'] ?? '';
            $color = $_POST['color'] ?? '#eab308';
            $level = $_POST['level'] ?? 1;
            if (empty($name)) throw new Exception('Nombre requerido');
            $pdo->prepare("INSERT INTO ticket_priorities (name, color, level) VALUES (?, ?, ?)")->execute([$name, $color, $level]);
            echo json_encode(['success' => true]);
            break;
            
        case 'delete_priority':
            $id = $_POST['id'] ?? 0;
            $pdo->prepare("DELETE FROM ticket_priorities WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true]);
            break;

        case 'chat_ping':
            $ticket_id = $_POST['ticket_id'] ?? 0;
            if (!$has_session) {
                // If it's a client, they don't lock the chat. Just return success.
                echo json_encode(['success' => true, 'locked' => false]);
                break;
            }

            // Check current lock status
            $stmt = $pdo->prepare("SELECT active_tech_id, active_tech_ping, u.name as tech_name FROM tickets t LEFT JOIN users u ON t.active_tech_id = u.id WHERE t.id = ?");
            $stmt->execute([$ticket_id]);
            $lock = $stmt->fetch();

            if (!$lock) {
                echo json_encode(['success' => false, 'message' => 'Ticket no encontrado']);
                break;
            }

            $now = time();
            $ping_time = $lock['active_tech_ping'] ? strtotime($lock['active_tech_ping']) : 0;
            $is_locked_by_other = ($lock['active_tech_id'] && $lock['active_tech_id'] != $user_id && ($now - $ping_time) <= 10);

            if ($is_locked_by_other) {
                echo json_encode(['success' => true, 'locked' => true, 'locked_by' => $lock['tech_name']]);
            } else {
                // Take or renew the lock
                $pdo->prepare("UPDATE tickets SET active_tech_id = ?, active_tech_ping = NOW() WHERE id = ?")->execute([$user_id, $ticket_id]);
                echo json_encode(['success' => true, 'locked' => false]);
            }
            break;

        case 'chat_leave':
            $ticket_id = $_POST['ticket_id'] ?? 0;
            if ($has_session) {
                $pdo->prepare("UPDATE tickets SET active_tech_id = NULL, active_tech_ping = NULL WHERE id = ? AND active_tech_id = ?")->execute([$ticket_id, $user_id]);
            }
            echo json_encode(['success' => true]);
            break;


        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
