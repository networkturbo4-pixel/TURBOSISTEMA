<?php
require_once '../config/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'list') {
    try {
        $stmt = $pdo->query("SELECT a.*, u.name as tecnico_nombre FROM actas a LEFT JOIN users u ON a.tecnico_id = u.id ORDER BY a.id DESC");
        $actas = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $actas]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'delete') {
    $id = $_POST['id'] ?? 0;
    try {
        $stmt = $pdo->prepare("DELETE FROM actas WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Acta eliminada']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'create') {
    try {
        $pdo->beginTransaction();

        $isEdit = !empty($_POST['edit_id']);
        $editId = $_POST['edit_id'] ?? null;

        if (!$isEdit) {
            // Obtener prefijo y generar folio único
            $prefijo = $_POST['prefijo'] ?? 'LIM-';
            $stmt_count = $pdo->query("SELECT MAX(id) as max_id FROM actas");
            $row = $stmt_count->fetch();
            $nextId = $row['max_id'] ? $row['max_id'] + 1 : 1;
            $folio = str_pad($nextId, 6, '0', STR_PAD_LEFT);
            $token = bin2hex(random_bytes(16));
        } else {
            // Retrieve existing folio and token
            $stmtEx = $pdo->prepare("SELECT folio, prefijo, token FROM actas WHERE id = ?");
            $stmtEx->execute([$editId]);
            $exRow = $stmtEx->fetch();
            if (!$exRow) throw new Exception("Acta no encontrada para editar");
            $folio = $exRow['folio'];
            $prefijo = $exRow['prefijo'];
            $token = $exRow['token'];
        }

        if (!$isEdit) {
            // Guardar el acta nueva
        $sqlActa = "INSERT INTO actas (
            folio, prefijo, token,
            cliente_nombre, cliente_dni_ruc, cliente_rotulado, cliente_direccion, cliente_distrito, cliente_referencia,
            cliente_whatsapp, cliente_celular_alt, cliente_gps_lat, cliente_gps_lng,
            pe_nodo, pe_nap, pe_puerto, pe_potencia, pe_atenuacion,
            srv_fecha, srv_hora_inicio, srv_hora_fin, srv_tipo, srv_estado, tecnico_id,
            red_ssid, red_password, red_speed_dl, red_speed_ul, red_n_tvs, red_splitters, red_senal_low, red_senal_high,
            observaciones, mantenimiento_6_meses, calificacion_servicio,
            firma_cliente, firma_tecnico
        ) VALUES (
            ?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?,
            ?, ?
        )";

        $stmtActa = $pdo->prepare($sqlActa);
        $stmtActa->execute([
            $folio, $prefijo, $token,
            $_POST['cliente_nombre'] ?? '', $_POST['cliente_dni_ruc'] ?? '', $_POST['cliente_rotulado'] ?? '', $_POST['cliente_direccion'] ?? '', $_POST['cliente_distrito'] ?? '', $_POST['cliente_referencia'] ?? '',
            $_POST['cliente_whatsapp'] ?? '', $_POST['cliente_celular_alt'] ?? '', $_POST['cliente_gps_lat'] ?? '', $_POST['cliente_gps_lng'] ?? '',
            $_POST['pe_nodo'] ?? '', $_POST['pe_nap'] ?? '', $_POST['pe_puerto'] ?? '', $_POST['pe_potencia'] ?? '', $_POST['pe_atenuacion'] ?? '',
            $_POST['srv_fecha'] ?? date('Y-m-d'), $_POST['srv_hora_inicio'] ?? '', $_POST['srv_hora_fin'] ?? '', $_POST['srv_tipo'] ?? '', $_POST['srv_estado'] ?? '', (int)($_POST['tecnico_id'] ?? 0),
            $_POST['red_ssid'] ?? '', $_POST['red_password'] ?? '', $_POST['red_speed_dl'] ?? '', $_POST['red_speed_ul'] ?? '', (int)($_POST['red_n_tvs'] ?? 0), $_POST['red_splitters'] ?? '', $_POST['red_senal_low'] ?? '', $_POST['red_senal_high'] ?? '',
            $_POST['observaciones'] ?? '', isset($_POST['mantenimiento_6_meses']) ? 1 : 0, (int)($_POST['calificacion'] ?? 0),
            $_POST['firma_cliente_base64'] ?? '', $_POST['firma_tecnico_base64'] ?? ''
        ]);
        
        $acta_id = $pdo->lastInsertId();

        } else {
            // Actualizar acta existente
            $sqlActaUpdate = "UPDATE actas SET 
                cliente_nombre=?, cliente_dni_ruc=?, cliente_rotulado=?, cliente_direccion=?, cliente_distrito=?, cliente_referencia=?,
                cliente_whatsapp=?, cliente_celular_alt=?, cliente_gps_lat=?, cliente_gps_lng=?,
                pe_nodo=?, pe_nap=?, pe_puerto=?, pe_potencia=?, pe_atenuacion=?,
                srv_fecha=?, srv_hora_inicio=?, srv_hora_fin=?, srv_tipo=?, srv_estado=?, tecnico_id=?,
                red_ssid=?, red_password=?, red_speed_dl=?, red_speed_ul=?, red_n_tvs=?, red_splitters=?, red_senal_low=?, red_senal_high=?,
                observaciones=?, mantenimiento_6_meses=?, calificacion_servicio=?";
            
            $paramsUpdate = [
                $_POST['cliente_nombre'] ?? '', $_POST['cliente_dni_ruc'] ?? '', $_POST['cliente_rotulado'] ?? '', $_POST['cliente_direccion'] ?? '', $_POST['cliente_distrito'] ?? '', $_POST['cliente_referencia'] ?? '',
                $_POST['cliente_whatsapp'] ?? '', $_POST['cliente_celular_alt'] ?? '', $_POST['cliente_gps_lat'] ?? '', $_POST['cliente_gps_lng'] ?? '',
                $_POST['pe_nodo'] ?? '', $_POST['pe_nap'] ?? '', $_POST['pe_puerto'] ?? '', $_POST['pe_potencia'] ?? '', $_POST['pe_atenuacion'] ?? '',
                $_POST['srv_fecha'] ?? date('Y-m-d'), $_POST['srv_hora_inicio'] ?? '', $_POST['srv_hora_fin'] ?? '', $_POST['srv_tipo'] ?? '', $_POST['srv_estado'] ?? '', (int)($_POST['tecnico_id'] ?? 0),
                $_POST['red_ssid'] ?? '', $_POST['red_password'] ?? '', $_POST['red_speed_dl'] ?? '', $_POST['red_speed_ul'] ?? '', (int)($_POST['red_n_tvs'] ?? 0), $_POST['red_splitters'] ?? '', $_POST['red_senal_low'] ?? '', $_POST['red_senal_high'] ?? '',
                $_POST['observaciones'] ?? '', isset($_POST['mantenimiento_6_meses']) ? 1 : 0, (int)($_POST['calificacion'] ?? 0)
            ];

            if (!empty($_POST['firma_cliente_base64'])) {
                $sqlActaUpdate .= ", firma_cliente=?";
                $paramsUpdate[] = $_POST['firma_cliente_base64'];
            }
            if (!empty($_POST['firma_tecnico_base64'])) {
                $sqlActaUpdate .= ", firma_tecnico=?";
                $paramsUpdate[] = $_POST['firma_tecnico_base64'];
            }

            $sqlActaUpdate .= " WHERE id=?";
            $paramsUpdate[] = $editId;

            $stmtActa = $pdo->prepare($sqlActaUpdate);
            $stmtActa->execute($paramsUpdate);
            
            $acta_id = $editId;
            
            // Delete old dynamic tables for replacement
            $pdo->prepare("DELETE FROM actas_equipos WHERE acta_id = ?")->execute([$acta_id]);
            $pdo->prepare("DELETE FROM actas_materiales WHERE acta_id = ?")->execute([$acta_id]);
        }


        // Técnico
        $tecnico_id_val = (int)($_POST['tecnico_id'] ?? 0);

        // Actualizar Inventario para Rotulado
        $cliente_rotulado = trim($_POST['cliente_rotulado'] ?? '');
        if (!empty($cliente_rotulado)) {
            $pdo->prepare("UPDATE inventory_skus SET status = 'instalado', assigned_to = NULL WHERE sku_code = ?")->execute([$cliente_rotulado]);
            $pdo->prepare("INSERT INTO inventory_entries (sku_id, user_id, tipo, notas) SELECT id, ?, 'salida', ? FROM inventory_skus WHERE sku_code = ?")
                ->execute([$tecnico_id_val ?: 1, "Acta (Rotulado): $folio", $cliente_rotulado]);
        }

        // Guardar Equipos
        if (isset($_POST['equipos_accion']) && is_array($_POST['equipos_accion'])) {
            $stmtEq = $pdo->prepare("INSERT INTO actas_equipos (acta_id, accion, modelo_marca, serie_mac, propiedad) VALUES (?, ?, ?, ?, ?)");
            foreach ($_POST['equipos_accion'] as $index => $accion) {
                if (!empty($_POST['equipos_serie'][$index]) || !empty($_POST['equipos_modelo'][$index])) {
                    $serie_mac = $_POST['equipos_serie'][$index] ?? '';
                    $stmtEq->execute([
                        $acta_id, 
                        $accion, 
                        $_POST['equipos_modelo'][$index] ?? '', 
                        $serie_mac, 
                        $_POST['equipos_propiedad'][$index] ?? ''
                    ]);

                    // Actualizar Inventario (SKU)
                    if (!empty($serie_mac)) {
                        $inv_status = '';
                        $inv_hist = null;
                        $assign_to = null;

                        if ($accion === 'Instala') {
                            $inv_status = 'instalado';
                            $assign_to = null; // Pierde asignación
                        } elseif ($accion === 'Retira' || $accion === 'Cambio') {
                            $inv_status = 'disponible';
                            $assign_to = $tecnico_id_val; // Gana asignación
                        } elseif ($accion === 'Malogrado') {
                            $inv_status = 'malogrado';
                            $inv_hist = 'malogrado';
                            $assign_to = $tecnico_id_val;
                        } elseif ($accion === 'Reparado') {
                            $inv_status = 'reparado';
                            $assign_to = $tecnico_id_val;
                        } elseif ($accion === 'En Tránsito') {
                            $inv_status = 'en_transito';
                            $inv_hist = 'en_transito';
                            $assign_to = $tecnico_id_val;
                        }

                        if ($inv_status !== '') {
                            $updateQuery = "UPDATE inventory_skus SET status = ?, assigned_to = ?";
                            $paramsUpdateSku = [$inv_status, $assign_to ?: null];
                            if ($inv_hist) {
                                $updateQuery .= ", historia = ?";
                                $paramsUpdateSku[] = $inv_hist;
                            }
                            $updateQuery .= " WHERE sku_code = ?";
                            $paramsUpdateSku[] = $serie_mac;
                            $pdo->prepare($updateQuery)->execute($paramsUpdateSku);
                            
                            // Log movement
                            $pdo->prepare("INSERT INTO inventory_entries (sku_id, user_id, tipo, notas) SELECT id, ?, ?, ? FROM inventory_skus WHERE sku_code = ?")
                                ->execute([$tecnico_id_val ?: 1, $accion === 'Instala' ? 'salida' : 'entrada', "Acta: $folio", $serie_mac]);
                        }
                    }
                }
            }
        }

        // Guardar Materiales
        if (isset($_POST['mat_desc']) && is_array($_POST['mat_desc'])) {
            $stmtMat = $pdo->prepare("INSERT INTO actas_materiales (acta_id, descripcion, cantidad, unidad, accion, propiedad) VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($_POST['mat_desc'] as $index => $desc) {
                if (!empty($desc)) {
                    $cant = (float)($_POST['mat_cant'][$index] ?? 0);
                    $stmtMat->execute([
                        $acta_id, 
                        $desc, 
                        $cant, 
                        $_POST['mat_und'][$index] ?? '',
                        $_POST['mat_accion'][$index] ?? '',
                        $_POST['mat_propiedad'][$index] ?? ''
                    ]);

                    // Descontar material a granel de la mochila del técnico
                    if ($cant > 0 && $tecnico_id_val > 0) {
                        $stmtProdId = $pdo->prepare("SELECT id FROM inventory_products WHERE name = ? AND is_bulk = 1");
                        $stmtProdId->execute([$desc]);
                        $prodId = $stmtProdId->fetchColumn();

                        if ($prodId) {
                            $pdo->prepare("UPDATE inventory_user_stock SET quantity = quantity - ? WHERE user_id = ? AND product_id = ?")
                                ->execute([$cant, $tecnico_id_val, $prodId]);
                        }
                    }
                }
            }
        }
        
        // Sincronizar Cliente
        $c_nombre = trim($_POST['cliente_nombre'] ?? '');
        $c_dni = trim($_POST['cliente_dni_ruc'] ?? '');
        if (!empty($c_nombre) && !empty($c_dni)) {
            // Preparar fechas (combinar fecha y hora)
            $srv_fecha = $_POST['srv_fecha'] ?? date('Y-m-d');
            $srv_hora = $_POST['srv_hora_inicio'] ?? '00:00:00';
            if (strlen($srv_hora) === 5) $srv_hora .= ':00'; // Formato HH:MM a HH:MM:SS
            $datetime_srv = $srv_fecha . ' ' . $srv_hora;

            // Revisar si el cliente existe por DNI
            $stmtCheckClient = $pdo->prepare("SELECT id FROM clientes WHERE dni = ? LIMIT 1");
            $stmtCheckClient->execute([$c_dni]);
            $clientExists = $stmtCheckClient->fetchColumn();

            // Plan details from acta
            $plan_details = '';
            if (!empty($_POST['red_speed_dl'])) $plan_details .= $_POST['red_speed_dl'] . 'Mbps ';
            if (!empty($_POST['red_ssid'])) $plan_details .= 'SSID: ' . $_POST['red_ssid'];
            
            $c_celular = !empty($_POST['cliente_whatsapp']) ? $_POST['cliente_whatsapp'] : ($_POST['cliente_celular_alt'] ?? '');
            $c_direccion = $_POST['cliente_direccion'] ?? '';
            $c_referencia = $_POST['cliente_referencia'] ?? '';

            if ($clientExists) {
                // Actualizar
                $pdo->prepare("UPDATE clientes SET 
                    nombre_completo = ?, 
                    celular = ?, 
                    direccion = ?, 
                    referencia = ?, 
                    fecha_servicio_contratado = ?, 
                    inicio_servicio = ?
                    WHERE id = ?")
                ->execute([
                    $c_nombre,
                    $c_celular,
                    $c_direccion,
                    $c_referencia,
                    $datetime_srv,
                    $datetime_srv,
                    $clientExists
                ]);
            } else {
                // Crear usuario
                $pin = !empty($_POST['cliente_pin']) ? $_POST['cliente_pin'] : str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $email = $c_dni . '@cliente.turbosaas.com';
                $password = password_hash($pin, PASSWORD_DEFAULT);
                
                $stmtUser = $pdo->prepare("INSERT INTO users (name, email, password, role, pin, whatsapp) VALUES (?, ?, ?, ?, ?, ?)");
                try {
                    $stmtUser->execute([$c_nombre, $email, $password, 'Cliente', $pin, $c_celular]);
                    $user_id = $pdo->lastInsertId();
                } catch (Exception $e) {
                    $email = 'c_' . time() . '_' . $c_dni . '@cliente.turbosaas.com';
                    $stmtUser->execute([$c_nombre, $email, $password, 'Cliente', $pin, $c_celular]);
                    $user_id = $pdo->lastInsertId();
                }

                // Insertar
                $pdo->prepare("INSERT INTO clientes (
                    user_id, nombre_completo, dni, celular, direccion, referencia, detalles_plan, fecha_servicio_contratado, inicio_servicio
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute([
                    $user_id,
                    $c_nombre,
                    $c_dni,
                    $c_celular,
                    $c_direccion,
                    $c_referencia,
                    trim($plan_details),
                    $datetime_srv,
                    $datetime_srv
                ]);
            }
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'folio' => $folio, 'id' => $acta_id, 'token' => $token]);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Acción no válida']);
