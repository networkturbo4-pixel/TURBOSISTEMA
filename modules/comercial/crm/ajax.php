<?php
require_once '../../../config/db.php';
requireLogin();
requirePermission($pdo, 'comercial');

header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {
        case 'get_board':
            $pipeline_id = (int)($_GET['pipeline_id'] ?? 1);
            
            // Etapas
            $stmt = $pdo->prepare("SELECT * FROM crm_stages WHERE pipeline_id = ? ORDER BY order_index ASC");
            $stmt->execute([$pipeline_id]);
            $stages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Prospectos
            $stmt = $pdo->prepare("SELECT p.*, s.nombre as plan_name, u.name as agent_name 
                                     FROM crm_prospects p 
                                     LEFT JOIN servicios s ON p.interest_service_id = s.id
                                     LEFT JOIN users u ON p.assigned_to = u.id
                                     WHERE p.pipeline_id = ?");
            $stmt->execute([$pipeline_id]);
            $prospects = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'stages' => $stages, 'prospects' => $prospects]);
            break;
            
        case 'move_prospect':
            $prospect_id = (int)$_POST['prospect_id'];
            $stage_id = (int)$_POST['stage_id'];
            
            // Obtener datos de la etapa para ver si es 'Ganado'
            $stmtStage = $pdo->prepare("SELECT is_won FROM crm_stages WHERE id = ?");
            $stmtStage->execute([$stage_id]);
            $stage = $stmtStage->fetch(PDO::FETCH_ASSOC);
            
            $stmt = $pdo->prepare("UPDATE crm_prospects SET stage_id = ?, last_activity_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$stage_id, $prospect_id]);
            
            echo json_encode(['success' => true, 'is_won' => (bool)$stage['is_won']]);
            break;
            
        case 'convert_to_client':
            $prospect_id = (int)$_POST['prospect_id'];
            
            $stmtP = $pdo->prepare("SELECT * FROM crm_prospects WHERE id = ?");
            $stmtP->execute([$prospect_id]);
            $p = $stmtP->fetch(PDO::FETCH_ASSOC);
            
            if (!$p) throw new Exception("Prospecto no encontrado");
            
            // Comprobar si ya existe en clientes
            $stmtCheck = $pdo->prepare("SELECT id FROM clientes WHERE dni = ? AND dni != ''");
            $stmtCheck->execute([$p['documento']]);
            if ($stmtCheck->fetch()) {
                throw new Exception("El cliente con DNI " . $p['documento'] . " ya existe en la base de clientes.");
            }
            
            // Insertar cliente
            $stmt = $pdo->prepare("INSERT INTO clientes (nombre_completo, dni, celular, correo, direccion, latitud, longitud, servicio_id, estado, created_at)
                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'activo', CURRENT_TIMESTAMP)");
            $stmt->execute([
                $p['nombre_completo'],
                $p['documento'],
                $p['telefono'],
                $p['correo'],
                $p['direccion'],
                $p['latitud'],
                $p['longitud'],
                $p['interest_service_id']
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Prospecto convertido a cliente exitosamente.']);
            break;

        case 'save_prospect':
            $id = (int)($_POST['id'] ?? 0);
            $nombre = $_POST['nombre_completo'] ?? '';
            $documento = $_POST['documento'] ?? '';
            $telefono = $_POST['telefono'] ?? '';
            $correo = $_POST['correo'] ?? '';
            $direccion = $_POST['direccion'] ?? '';
            $fuente = $_POST['fuente'] ?? '';
            $servicio_id = !empty($_POST['interest_service_id']) ? (int)$_POST['interest_service_id'] : null;
            $pipeline_id = (int)($_POST['pipeline_id'] ?? 1);
            
            // Validar Duplicados
            if (!empty($documento) || !empty($telefono)) {
                $sqlCheck = "SELECT id FROM crm_prospects WHERE id != ? AND ( (documento = ? AND documento != '') OR (telefono = ? AND telefono != '') )";
                $stmtCheck = $pdo->prepare($sqlCheck);
                $stmtCheck->execute([$id, $documento, $telefono]);
                if ($stmtCheck->fetch()) {
                    throw new Exception("Ya existe un prospecto con ese DNI o Teléfono.");
                }
            }

            // Calculo de Score simple
            $score = 20;
            if (!empty($telefono)) $score += 20;
            if (!empty($correo)) $score += 20;
            if (!empty($documento)) $score += 10;
            if (!empty($direccion)) $score += 10;
            if ($servicio_id) $score += 20;

            if ($id > 0) {
                // Update
                $stmt = $pdo->prepare("UPDATE crm_prospects SET nombre_completo=?, documento=?, telefono=?, correo=?, direccion=?, fuente=?, interest_service_id=?, score=? WHERE id=?");
                $stmt->execute([$nombre, $documento, $telefono, $correo, $direccion, $fuente, $servicio_id, $score, $id]);
            } else {
                // Insert & Round Robin
                // Encontrar etapa inicial
                $stmtStage = $pdo->prepare("SELECT id FROM crm_stages WHERE pipeline_id = ? ORDER BY order_index ASC LIMIT 1");
                $stmtStage->execute([$pipeline_id]);
                $first_stage = $stmtStage->fetchColumn();

                // Round robin simple: usuario con menos asignaciones
                $stmtRR = $pdo->query("SELECT u.id, COUNT(p.id) as total FROM users u 
                                         LEFT JOIN crm_prospects p ON u.id = p.assigned_to 
                                         WHERE u.role IN ('admin', 'vendedor', 'comercial')
                                         GROUP BY u.id ORDER BY total ASC LIMIT 1");
                $assigned_to = $stmtRR->fetchColumn();
                if (!$assigned_to) $assigned_to = $_SESSION['user_id']; // Fallback a mi mismo

                $stmt = $pdo->prepare("INSERT INTO crm_prospects (pipeline_id, stage_id, nombre_completo, documento, telefono, correo, direccion, fuente, interest_service_id, score, assigned_to) 
                                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$pipeline_id, $first_stage, $nombre, $documento, $telefono, $correo, $direccion, $fuente, $servicio_id, $score, $assigned_to]);
            }
            
            echo json_encode(['success' => true]);
            break;

        case 'save_note':
            $prospect_id = (int)$_POST['prospect_id'];
            $content = trim($_POST['content'] ?? '');
            $type = $_POST['type'] ?? 'nota';
            $call_result = $_POST['call_result'] ?? null;
            $call_duration = !empty($_POST['call_duration']) ? (int)$_POST['call_duration'] : null;
            $user_id = $_SESSION['user_id'];

            if (empty($content) && $type == 'nota') throw new Exception("La nota no puede estar vacía");

            $stmt = $pdo->prepare("INSERT INTO crm_notes (prospect_id, user_id, type, content, call_result, call_duration) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$prospect_id, $user_id, $type, $content, $call_result, $call_duration]);

            // Menciones (@usuario)
            preg_match_all('/@([a-zA-Z0-9_]+)/', $content, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $username) {
                    $stmtU = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                    $stmtU->execute([$username]);
                    $uid = $stmtU->fetchColumn();
                    if ($uid) {
                        $msg = "Te han mencionado en el prospecto #$prospect_id";
                        $stmtN = $pdo->prepare("INSERT INTO notifications (user_id, type, message, link_url) VALUES (?, 'mention', ?, ?)");
                        $stmtN->execute([$uid, $msg, "/modules/comercial/crm"]);
                    }
                }
            }
            
            echo json_encode(['success' => true]);
            break;

        case 'get_notes':
            $prospect_id = (int)$_GET['prospect_id'];
            $stmt = $pdo->prepare("SELECT n.*, u.name as user_name FROM crm_notes n LEFT JOIN users u ON n.user_id = u.id WHERE n.prospect_id = ? ORDER BY n.id DESC");
            $stmt->execute([$prospect_id]);
            echo json_encode(['success' => true, 'notes' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;
            
        case 'get_prospect':
            $id = (int)$_GET['id'];
            $stmt = $pdo->prepare("SELECT * FROM crm_prospects WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'data' => $stmt->fetch(PDO::FETCH_ASSOC)]);
            break;

        default:
            throw new Exception("Acción no válida");
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>