<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'list') {
    try {
        $stmt = $pdo->query("SELECT c.*, u.pin as user_pin, s.nombre as servicio_nombre, s.velocidad as servicio_velocidad FROM clientes c LEFT JOIN users u ON c.user_id = u.id LEFT JOIN servicios s ON c.servicio_id = s.id ORDER BY c.id DESC");
        $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $clientes]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener clientes: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'get') {
    $id = $_POST['id'] ?? $_GET['id'] ?? '';
    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'ID requerido.']);
        exit;
    }
    try {
        $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
        $stmt->execute([$id]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($cliente) {
            echo json_encode(['success' => true, 'data' => $cliente]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Cliente no encontrado.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'save') {
    $id = $_POST['id'] ?? '';
    $nombre_completo = $_POST['nombre_completo'] ?? '';
    $dni = $_POST['dni'] ?? '';
    $celular = $_POST['celular'] ?? null;
    $correo = $_POST['correo'] ?? null;
    $direccion = $_POST['direccion'] ?? null;
    $referencia = $_POST['referencia'] ?? null;
    $detalles_plan = $_POST['detalles_plan'] ?? null;
    $servicio_id = !empty($_POST['servicio_id']) ? $_POST['servicio_id'] : null;
    $latitud = $_POST['latitud'] ?? null;
    $longitud = $_POST['longitud'] ?? null;
    
    // Si viene vacío desde el formulario, se inserta como nulo
    $fecha_servicio_contratado = !empty($_POST['fecha_servicio_contratado']) ? $_POST['fecha_servicio_contratado'] : null;
    $inicio_servicio = !empty($_POST['inicio_servicio']) ? $_POST['inicio_servicio'] : null;

    $router_os = $_POST['router_os'] ?? 'mock';
    $router_ip = $_POST['router_ip'] ?? null;
    $router_user = $_POST['router_user'] ?? null;
    $router_pass = $_POST['router_pass'] ?? null;

    if (empty($nombre_completo) || empty($dni)) {
        echo json_encode(['success' => false, 'message' => 'Nombre completo y DNI son requeridos.']);
        exit;
    }

    try {
        if (!empty($id)) {
            // Update
            $stmt = $pdo->prepare("UPDATE clientes SET 
                nombre_completo = ?, 
                dni = ?, 
                celular = ?, 
                correo = ?, 
                direccion = ?, 
                referencia = ?, 
                detalles_plan = ?, 
                servicio_id = ?,
                latitud = ?,
                longitud = ?,
                fecha_servicio_contratado = ?, 
                inicio_servicio = ?,
                router_os = ?,
                router_ip = ?,
                router_user = ?,
                router_pass = ?
                WHERE id = ?");
            $stmt->execute([
                $nombre_completo, $dni, $celular, $correo, $direccion, 
                $referencia, $detalles_plan, $servicio_id, $latitud, $longitud, $fecha_servicio_contratado, 
                $inicio_servicio, $router_os, $router_ip, $router_user, $router_pass, $id
            ]);
            
            // Actualizar o crear PIN de usuario si se envió o si se quiere generar
            if (isset($_POST['pin']) && !empty(trim($_POST['pin']))) {
                $newPin = trim($_POST['pin']);
                $newPassword = password_hash($newPin, PASSWORD_DEFAULT);
                $stmtCheck = $pdo->prepare("SELECT user_id FROM clientes WHERE id = ?");
                $stmtCheck->execute([$id]);
                $u_id = $stmtCheck->fetchColumn();
                
                if ($u_id) {
                    // Update existing user
                    $stmtUpdateUser = $pdo->prepare("UPDATE users SET pin = ?, password = ? WHERE id = ?");
                    $stmtUpdateUser->execute([$newPin, $newPassword, $u_id]);
                } else {
                    // Create new user for existing client
                    $email = !empty($correo) ? $correo : ($dni . '@cliente.turbosaas.com');
                    $stmtUser = $pdo->prepare("INSERT INTO users (name, email, password, role, pin, whatsapp) VALUES (?, ?, ?, ?, ?, ?)");
                    try {
                        $stmtUser->execute([$nombre_completo, $email, $newPassword, 'Cliente', $newPin, $celular]);
                        $u_id = $pdo->lastInsertId();
                    } catch (Exception $e) {
                        $email = 'c_' . time() . '_' . $dni . '@cliente.turbosaas.com';
                        $stmtUser->execute([$nombre_completo, $email, $newPassword, 'Cliente', $newPin, $celular]);
                        $u_id = $pdo->lastInsertId();
                    }
                    // Link to client
                    $pdo->prepare("UPDATE clientes SET user_id = ? WHERE id = ?")->execute([$u_id, $id]);
                }
            }
            
            echo json_encode(['success' => true, 'message' => 'Cliente actualizado correctamente.']);
        } else {
            // Crear usuario asociado con PIN de 8 dígitos
            $pin = !empty($_POST['pin']) ? trim($_POST['pin']) : str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
            $email = !empty($correo) ? $correo : ($dni . '@cliente.turbosaas.com');
            $password = password_hash($pin, PASSWORD_DEFAULT);
            
            // Asegurarse de que el PIN sea único (opcionalmente se podría hacer un bucle, pero es raro que colisione si no hay millones)
            $stmtUser = $pdo->prepare("INSERT INTO users (name, email, password, role, pin, whatsapp) VALUES (?, ?, ?, ?, ?, ?)");
            try {
                $stmtUser->execute([$nombre_completo, $email, $password, 'Cliente', $pin, $celular]);
                $user_id = $pdo->lastInsertId();
            } catch (Exception $e) {
                // Si el correo ya existe, intentamos con un prefijo
                $email = 'c_' . time() . '_' . $dni . '@cliente.turbosaas.com';
                $stmtUser->execute([$nombre_completo, $email, $password, 'Cliente', $pin, $celular]);
                $user_id = $pdo->lastInsertId();
            }

            // Insert Cliente
            $stmt = $pdo->prepare("INSERT INTO clientes (
                user_id, nombre_completo, dni, celular, correo, direccion, referencia, detalles_plan, servicio_id, latitud, longitud, fecha_servicio_contratado, inicio_servicio, router_os, router_ip, router_user, router_pass
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $user_id, $nombre_completo, $dni, $celular, $correo, $direccion, 
                $referencia, $detalles_plan, $servicio_id, $latitud, $longitud, $fecha_servicio_contratado, 
                $inicio_servicio, $router_os, $router_ip, $router_user, $router_pass
            ]);
            echo json_encode(['success' => true, 'message' => 'Cliente creado correctamente.', 'pin' => $pin]);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'delete') {
    $id = $_POST['id'] ?? '';
    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'ID no proporcionado.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Cliente eliminado correctamente.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Acción no válida']);
