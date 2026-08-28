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

// Liberar la sesión inmediatamente para evitar bloqueos por polling (chat)
session_write_close();

// Helper for token validation
function validateToken($pdo, $ticket_id, $token) {
    $stmt = $pdo->prepare("SELECT public_token FROM tickets WHERE id = ?");
    $stmt->execute([$ticket_id]);
    $real_token = $stmt->fetchColumn();
    return $real_token && $real_token === $token;
}

function createTicketDriveFolder($pdo, $ticket_id, $cliente_id, $cliente_nombre_manual) {
    try {
        require_once __DIR__ . '/../includes/GoogleDriveHelper.php';
        $clientName = 'Cliente General';
        if ($cliente_nombre_manual) {
            $clientName = $cliente_nombre_manual;
        } elseif ($cliente_id) {
            $stmt = $pdo->prepare("SELECT nombre_completo FROM clientes WHERE id = ?");
            $stmt->execute([$cliente_id]);
            $clientName = $stmt->fetchColumn() ?: 'Cliente General';
        }
        
        $dateStr = date('Y-m-d_H-i');
        $folderName = $clientName . ' - ' . $dateStr;
        
        $folderId = GoogleDriveHelper::getOrCreateFolder($folderName, 'Soporte');
        if ($folderId) {
            $pdo->prepare("UPDATE tickets SET gdrive_folder_id = ? WHERE id = ?")->execute([$folderId, $ticket_id]);
        }
    } catch(Exception $e) {
        error_log("Error creando carpeta GD para ticket $ticket_id: " . $e->getMessage());
    }
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
                SELECT t.*, COALESCE(NULLIF(t.cliente_nombre_manual, ''), c.nombre_completo, 'Cliente General') as cliente_nombre,
                       c.celular as cliente_celular, c.direccion as cliente_direccion, u.name as tech_name,
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

        case 'get_settings_data':
            $categories = $pdo->query("SELECT * FROM ticket_categories ORDER BY name ASC")->fetchAll();
            $priorities = $pdo->query("SELECT * FROM ticket_priorities ORDER BY level ASC")->fetchAll();
            echo json_encode(['success' => true, 'categories' => $categories, 'priorities' => $priorities]);
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
                echo json_encode(['success' => false, 'message' => 'DNI no registrado']);
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

            // Crear carpeta de Drive
            createTicketDriveFolder($pdo, $ticket_id, $cliente_id, null);

            echo json_encode(['success' => true, 'ticket_id' => $ticket_id, 'token' => $token]);
            break;

        case 'save':
            $cliente_id_raw = $_POST['cliente_id'] ?? '';
            $cliente_nombre_manual = trim($_POST['cliente_nombre_manual'] ?? '');

            $cliente_id = null;
            if ($cliente_id_raw !== 'manual' && !empty($cliente_id_raw)) {
                $cliente_id = intval($cliente_id_raw);
            }

            $asunto = $_POST['asunto'] ?? '';
            $categoria_id = !empty($_POST['categoria_id']) ? $_POST['categoria_id'] : null;
            $prioridad_id = !empty($_POST['prioridad_id']) ? $_POST['prioridad_id'] : null;
            $assigned_to = !empty($_POST['assigned_to']) ? $_POST['assigned_to'] : null;
            $descripcion = $_POST['descripcion'] ?? '';

            if (empty($asunto)) throw new Exception('El asunto es requerido');
            if (empty($cliente_id) && empty($cliente_nombre_manual)) {
                throw new Exception('Debe seleccionar un cliente del sistema o escribir un nombre manualmente.');
            }

            $stmt = $pdo->prepare("INSERT INTO tickets (cliente_id, cliente_nombre_manual, asunto, categoria_id, prioridad_id, assigned_to, descripcion) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$cliente_id, $cliente_nombre_manual ?: null, $asunto, $categoria_id, $prioridad_id, $assigned_to, $descripcion]);
            
            // Insert initial message as description
            $ticket_id = $pdo->lastInsertId();
            $stmtMsg = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, user_id, message, is_system_message) VALUES (?, ?, ?, 0)");
            $stmtMsg->execute([$ticket_id, $user_id, $descripcion]);

            // Crear carpeta de Drive
            createTicketDriveFolder($pdo, $ticket_id, $cliente_id, $cliente_nombre_manual);

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

            $older_than_id = (int)($_POST['older_than_id'] ?? 0);
            
            if ($older_than_id > 0) {
                $stmt = $pdo->prepare("
                    SELECT m.*, u.name as user_name 
                    FROM ticket_messages m
                    LEFT JOIN users u ON m.user_id = u.id
                    WHERE m.ticket_id = ? AND m.id < ?
                    ORDER BY m.id DESC LIMIT 50
                ");
                $stmt->execute([$ticket_id, $older_than_id]);
                $messages = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
            } else if ($last_id > 0) {
                $stmt = $pdo->prepare("
                    SELECT m.*, u.name as user_name 
                    FROM ticket_messages m
                    LEFT JOIN users u ON m.user_id = u.id
                    WHERE m.ticket_id = ? AND m.id > ?
                    ORDER BY m.id ASC
                ");
                $stmt->execute([$ticket_id, $last_id]);
                $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $stmt = $pdo->prepare("
                    SELECT m.*, u.name as user_name 
                    FROM ticket_messages m
                    LEFT JOIN users u ON m.user_id = u.id
                    WHERE m.ticket_id = ?
                    ORDER BY m.id DESC LIMIT 50
                ");
                $stmt->execute([$ticket_id]);
                $messages = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
            }

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

            // Get live location
            $stmtTkt = $pdo->prepare("SELECT live_lat, live_lng, live_expires_at FROM tickets WHERE id = ?");
            $stmtTkt->execute([$ticket_id]);
            $tkt = $stmtTkt->fetch(PDO::FETCH_ASSOC);
            $live_lat = null;
            $live_lng = null;
            if ($tkt && $tkt['live_expires_at'] && strtotime($tkt['live_expires_at']) > time()) {
                $live_lat = $tkt['live_lat'];
                $live_lng = $tkt['live_lng'];
            }

            echo json_encode([
                'success' => true,
                'data' => $messages,
                'live_lat' => $live_lat,
                'live_lng' => $live_lng
            ]);
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

        case 'set_typing':
            $ticket_id = $_POST['ticket_id'] ?? 0;
            if (!$has_session && !validateToken($pdo, $ticket_id, $token)) throw new Exception('No autorizado');
            
            if ($has_session) {
                $pdo->prepare("UPDATE tickets SET tech_typing_at = NOW() WHERE id = ?")->execute([$ticket_id]);
            } else {
                $pdo->prepare("UPDATE tickets SET client_typing_at = NOW() WHERE id = ?")->execute([$ticket_id]);
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
                $file = $_FILES['attachment'];
                
                require_once __DIR__ . '/../includes/GoogleDriveHelper.php';
                require_once __DIR__ . '/../includes/ImageHelper.php';
                
                $isImage = strpos($file['type'], 'image/') === 0;
                
                // Get ticket GD folder ID
                $stmtGd = $pdo->prepare("SELECT gdrive_folder_id FROM tickets WHERE id = ?");
                $stmtGd->execute([$ticket_id]);
                $ticketGdId = $stmtGd->fetchColumn();

                $uploadFilepath = $file['tmp_name'];
                $fileName = $file['name'];
                
                // Process image with Watermark and GPS if applicable
                if ($isImage) {
                    $lat = $_POST['latitude'] ?? null;
                    $lng = $_POST['longitude'] ?? null;
                    
                    // Fetch logo
                    $logoPath = null;
                    try {
                        $stmtSett = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'logo_light'");
                        $logoVal = $stmtSett->fetchColumn();
                        if ($logoVal) {
                            $logoPath = __DIR__ . '/../' . $logoVal;
                        }
                    } catch(Exception $e) {}
                    
                    $processedPath = sys_get_temp_dir() . '/' . uniqid('img_') . '.jpg';
                    if (ImageHelper::processAndWatermark($file['tmp_name'], $processedPath, $logoPath, $lat, $lng)) {
                        $uploadFilepath = $processedPath;
                        $fileName = pathinfo($file['name'], PATHINFO_FILENAME) . '.jpg';
                        $file['type'] = 'image/jpeg';
                    }
                }
                
                // Upload to Google Drive (using specific folder ID if available, otherwise fallback to 'chat')
                if ($ticketGdId) {
                    $gdriveRes = GoogleDriveHelper::uploadFile($uploadFilepath, $fileName, $file['type'], null, true, $ticketGdId);
                } else {
                    $gdriveRes = GoogleDriveHelper::uploadFile($uploadFilepath, $fileName, $file['type'], 'chat', true);
                }
                
                // Clean up processed temp file
                if ($isImage && isset($processedPath) && file_exists($processedPath)) {
                    @unlink($processedPath);
                }
                
                if (isset($gdriveRes['success']) && $gdriveRes['success']) {
                    // Para videos: usar web_view_link (se renderiza via iframe /preview)
                    // Para imágenes: usar direct_link (lh3 para carga directa rápida)
                    $isVideoFile = strpos($file['type'], 'video/') === 0;
                    if ($isVideoFile) {
                        $dbPath = $gdriveRes['web_view_link'] ?? $gdriveRes['webViewLink'] ?? '';
                        if (empty($dbPath) && isset($gdriveRes['file_id'])) {
                            $dbPath = 'https://drive.google.com/file/d/' . $gdriveRes['file_id'] . '/view';
                        }
                    } else {
                        $dbPath = $gdriveRes['direct_link'] ?? $gdriveRes['web_content_link'] ?? $gdriveRes['webContentLink'] ?? '';
                        if (empty($dbPath) && isset($gdriveRes['file_id'])) {
                            $dbPath = 'https://drive.google.com/uc?export=view&id=' . $gdriveRes['file_id'];
                        }
                    }
                    $stmtAtt = $pdo->prepare("INSERT INTO ticket_attachments (message_id, file_path, file_name) VALUES (?, ?, ?)");
                    $stmtAtt->execute([$message_id, $dbPath, $fileName]);
                } else {
                    // Fallback local
                    $uploadDir = '../uploads/tickets/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                    
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $fileName = 'tkt_' . $ticket_id . '_' . time() . '_' . uniqid() . '.' . $ext;
                    $targetPath = $uploadDir . $fileName;
                    
                    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                        $dbPath = 'uploads/tickets/' . $fileName;
                        $stmtAtt = $pdo->prepare("INSERT INTO ticket_attachments (message_id, file_path, file_name) VALUES (?, ?, ?)");
                        $stmtAtt->execute([$message_id, $dbPath, $file['name']]);
                    }
                }
            }
            
            // Actualizar updated_at del ticket
            $pdo->prepare("UPDATE tickets SET updated_at = NOW() WHERE id = ?")->execute([$ticket_id]);

            echo json_encode(['success' => true]);
            break;

        case 'delete_message':
            $message_id = $_POST['message_id'] ?? 0;
            if (!$has_session) throw new Exception('No autorizado'); // Solo usuarios del sistema por ahora

            // Verificar si el mensaje pertenece al usuario o es admin
            $stmt = $pdo->prepare("SELECT user_id, ticket_id FROM ticket_messages WHERE id = ?");
            $stmt->execute([$message_id]);
            $msgInfo = $stmt->fetch();

            if (!$msgInfo) {
                throw new Exception('Mensaje no encontrado');
            }

            if ($user_role !== 'admin' && $user_role !== 'administrador' && $msgInfo['user_id'] != $user_id) {
                throw new Exception('No tienes permiso para eliminar este mensaje');
            }

            // Opcional: Eliminar archivos adjuntos asociados si los hay
            // $stmtAtt = $pdo->prepare("DELETE FROM ticket_attachments WHERE message_id = ?");
            // $stmtAtt->execute([$message_id]);

            // Eliminar el mensaje
            $pdo->prepare("DELETE FROM ticket_messages WHERE id = ?")->execute([$message_id]);
            
            echo json_encode(['success' => true]);
            break;

        // --- AJUSTES: CATEGORÍAS Y PRIORIDADES ---
        case 'save_category':
            $id = intval($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $color = $_POST['color'] ?? '#3b82f6';
            if (empty($name)) throw new Exception('Nombre requerido');

            if ($id > 0) {
                $pdo->prepare("UPDATE ticket_categories SET name = ?, color = ? WHERE id = ?")->execute([$name, $color, $id]);
            } else {
                $pdo->prepare("INSERT INTO ticket_categories (name, color) VALUES (?, ?)")->execute([$name, $color]);
            }
            echo json_encode(['success' => true]);
            break;
            
        case 'delete_category':
            $id = $_POST['id'] ?? 0;
            $pdo->prepare("DELETE FROM ticket_categories WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true]);
            break;
            
        case 'save_priority':
            $id = intval($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $color = $_POST['color'] ?? '#eab308';
            $level = intval($_POST['level'] ?? 1);
            if (empty($name)) throw new Exception('Nombre requerido');

            if ($id > 0) {
                $pdo->prepare("UPDATE ticket_priorities SET name = ?, color = ?, level = ? WHERE id = ?")->execute([$name, $color, $level, $id]);
            } else {
                $pdo->prepare("INSERT INTO ticket_priorities (name, color, level) VALUES (?, ?, ?)")->execute([$name, $color, $level]);
            }
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

        case 'update_live_location':
            $ticket_id = $_POST['ticket_id'] ?? 0;
            $lat = $_POST['lat'] ?? 0;
            $lng = $_POST['lng'] ?? 0;
            
            if (!$has_session && !validateToken($pdo, $ticket_id, $token)) throw new Exception('No autorizado');
            
            // Current user or client
            $live_user_id = $has_session ? $user_id : null;
            // Extend expiration to 1 hour from now
            $expire = date('Y-m-d H:i:s', time() + 3600);
            
            $stmt = $pdo->prepare("UPDATE tickets SET live_lat = ?, live_lng = ?, live_user_id = ?, live_expires_at = ? WHERE id = ?");
            $stmt->execute([$lat, $lng, $live_user_id, $expire, $ticket_id]);
            
            echo json_encode(['success' => true]);
            break;


        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
