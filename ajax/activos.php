<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json; charset=utf-8');

function stampImageWithDate($filepath) {
    $info = @getimagesize($filepath);
    if(!$info) return;
    $mime = $info['mime'];
    $img = null;
    if($mime == 'image/jpeg') $img = @imagecreatefromjpeg($filepath);
    elseif($mime == 'image/png') $img = @imagecreatefrompng($filepath);
    elseif($mime == 'image/webp') $img = @imagecreatefromwebp($filepath);
    
    if(!$img) return;
    
    $width = imagesx($img);
    $height = imagesy($img);
    
    $dateText = date('Y-m-d H:i:s');
    $font = 5;
    $textW = imagefontwidth($font) * strlen($dateText);
    $textH = imagefontheight($font);
    
    $scale = max(2, round($width / 350)); 
    
    $scaledTextW = $textW * $scale;
    $scaledTextH = $textH * $scale;
    
    $margin = 10 * $scale;
    $padding = 5 * $scale;
    
    $x = $width - $scaledTextW - $margin - ($padding * 2);
    $y = $height - $scaledTextH - $margin - ($padding * 2);
    
    $bg = imagecolorallocatealpha($img, 0, 0, 0, 60);
    imagefilledrectangle($img, $x, $y, $x + $scaledTextW + ($padding*2), $y + $scaledTextH + ($padding*2), $bg);
    
    $tmpImg = imagecreatetruecolor($textW, $textH);
    $tmpBg = imagecolorallocate($tmpImg, 0, 0, 0);
    imagecolortransparent($tmpImg, $tmpBg);
    imagefill($tmpImg, 0, 0, $tmpBg);
    $white = imagecolorallocate($tmpImg, 255, 255, 255);
    imagestring($tmpImg, $font, 0, 0, $dateText, $white);
    
    imagecopyresized($img, $tmpImg, $x + $padding, $y + $padding, 0, 0, $scaledTextW, $scaledTextH, $textW, $textH);
    
    if($mime == 'image/jpeg') imagejpeg($img, $filepath, 90);
    elseif($mime == 'image/png') imagepng($img, $filepath);
    elseif($mime == 'image/webp') imagewebp($img, $filepath, 90);
    
    imagedestroy($img);
    imagedestroy($tmpImg);
}

// Verificar login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_activos':
        case 'get_vehiculos':
            $stmt = $pdo->query("
                SELECT v.*, 
                       (SELECT url_imagen FROM activos_imagenes i WHERE i.vehiculo_id = v.id ORDER BY fecha_subida ASC LIMIT 1) as primera_foto,
                       (SELECT COUNT(*) FROM activos_documentos d WHERE d.vehiculo_id = v.id) as total_docs,
                       (SELECT COUNT(*) FROM activos_imagenes im WHERE im.vehiculo_id = v.id) as total_imgs,
                       (SELECT COUNT(*) FROM activos_historial h WHERE h.vehiculo_id = v.id) as total_historial
                FROM activos_vehiculos v 
                WHERE v.estado != 'eliminado' 
                ORDER BY v.creado_en DESC
            ");
            $activos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $activos]);
            break;

        case 'save_activo':
        case 'save_vehiculo':
            $categoria = $_POST['categoria'] ?? 'vehiculo';
            $tipo = $_POST['tipo'] ?? 'otro';
            $nombre = $_POST['nombre'] ?? '';
            $placa = $_POST['placa'] ?? $_POST['codigo_identificador'] ?? '';
            $codigo_identificador = $_POST['codigo_identificador'] ?? $placa;
            $marca = $_POST['marca'] ?? '';
            $modelo = $_POST['modelo'] ?? '';
            $ubicacion = $_POST['ubicacion'] ?? '';
            $responsable_nombre = $_POST['responsable_nombre'] ?? '';
            $detalles_extra = $_POST['detalles_extra'] ?? null;
            $valor_adquisicion = !empty($_POST['valor_adquisicion']) ? (float)$_POST['valor_adquisicion'] : 0.00;
            $fecha_adquisicion = !empty($_POST['fecha_adquisicion']) ? $_POST['fecha_adquisicion'] : null;
            $estado = $_POST['estado'] ?? 'activo';
            
            if(empty($tipo) || (empty($placa) && empty($nombre) && empty($codigo_identificador))){
                throw new Exception('El tipo y al menos la placa, código o nombre son obligatorios');
            }
            
            // Si nombre está vacío, generar nombre por defecto
            if(empty($nombre)) {
                $nombre = ucfirst($tipo) . (!empty($marca) ? " $marca" : '') . (!empty($modelo) ? " $modelo" : '');
            }
            if(empty($placa)) {
                $placa = $codigo_identificador;
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO activos_vehiculos 
                (tipo, categoria, nombre, placa, codigo_identificador, marca, modelo, ubicacion, responsable_nombre, detalles_extra, valor_adquisicion, fecha_adquisicion, estado) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $tipo, 
                $categoria, 
                $nombre, 
                $placa, 
                $codigo_identificador, 
                $marca, 
                $modelo, 
                $ubicacion, 
                $responsable_nombre, 
                $detalles_extra, 
                $valor_adquisicion, 
                $fecha_adquisicion, 
                $estado
            ]);
            
            $vehiculo_id = $pdo->lastInsertId();
            
            if(!empty($_FILES['fotos_vehiculo']['name'][0])) {
                $count = count($_FILES['fotos_vehiculo']['name']);
                for($i = 0; $i < $count; $i++) {
                    if($_FILES['fotos_vehiculo']['error'][$i] == 0) {
                        $ext = pathinfo($_FILES['fotos_vehiculo']['name'][$i], PATHINFO_EXTENSION);
                        if(in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'webp'])) {
                            $filename = 'foto_' . uniqid() . '.' . $ext;
                            $dest = __DIR__ . '/../uploads/activos/' . $filename;
                            if(!is_dir(__DIR__ . '/../uploads/activos')) mkdir(__DIR__ . '/../uploads/activos', 0777, true);
                            if(move_uploaded_file($_FILES['fotos_vehiculo']['tmp_name'][$i], $dest)) {
                                stampImageWithDate($dest);
                                $stmtImg = $pdo->prepare("INSERT INTO activos_imagenes (vehiculo_id, url_imagen, descripcion) VALUES (?, ?, 'Foto inicial de registro')");
                                $stmtImg->execute([$vehiculo_id, $filename]);
                            }
                        }
                    }
                }
            }
            
            echo json_encode(['success' => true, 'id' => $vehiculo_id]);
            break;

        case 'delete_vehiculo':
            $id = $_POST['id'] ?? 0;
            if(empty($id)) throw new Exception('ID requerido');
            
            // Hacemos soft-delete (archivar) para no perder historial
            $stmt = $pdo->prepare("UPDATE activos_vehiculos SET estado = 'eliminado' WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
            break;

        case 'get_vehiculo_detalle':
            $id = $_GET['id'] ?? 0;
            
            // Obtener Vehículo
            $stmt = $pdo->prepare("SELECT * FROM activos_vehiculos WHERE id = ?");
            $stmt->execute([$id]);
            $vehiculo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if(!$vehiculo) {
                throw new Exception('Vehículo no encontrado');
            }
            
            // Obtener Documentos
            $stmtDocs = $pdo->prepare("SELECT * FROM activos_documentos WHERE vehiculo_id = ? ORDER BY fecha_subida DESC");
            $stmtDocs->execute([$id]);
            $vehiculo['documentos'] = $stmtDocs->fetchAll(PDO::FETCH_ASSOC);
            
            // Obtener Imágenes
            $stmtImgs = $pdo->prepare("SELECT * FROM activos_imagenes WHERE vehiculo_id = ? ORDER BY fecha_subida DESC");
            $stmtImgs->execute([$id]);
            $vehiculo['imagenes'] = $stmtImgs->fetchAll(PDO::FETCH_ASSOC);
            
            // Obtener Historial (Llantas y Mantenimiento)
            $stmtHist = $pdo->prepare("
                SELECT h.*, u.name as registrador 
                FROM activos_historial h 
                LEFT JOIN users u ON h.registrado_por = u.id 
                WHERE vehiculo_id = ? 
                ORDER BY fecha_evento DESC, creado_en DESC
            ");
            $stmtHist->execute([$id]);
            $vehiculo['historial'] = $stmtHist->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $vehiculo]);
            break;
            
        case 'upload_doc':
            $vehiculo_id = $_POST['vehiculo_id'] ?? 0;
            $tipo_documento = $_POST['tipo_documento'] ?? '';
            $titulo = $_POST['titulo'] ?? '';
            
            if(empty($vehiculo_id) || empty($tipo_documento) || empty($titulo)) throw new Exception('Datos incompletos');
            if(!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) throw new Exception('Error subiendo archivo');
            
            $file = $_FILES['archivo'];
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid('doc_') . '.' . $ext;
            $dest = __DIR__ . '/../uploads/activos/' . $filename;
            
            if(!is_dir(__DIR__ . '/../uploads/activos')) mkdir(__DIR__ . '/../uploads/activos', 0777, true);
            if(!move_uploaded_file($file['tmp_name'], $dest)) throw new Exception('Error guardando archivo en servidor');
            
            $stmt = $pdo->prepare("INSERT INTO activos_documentos (vehiculo_id, tipo_documento, titulo, url_archivo) VALUES (?, ?, ?, ?)");
            $stmt->execute([$vehiculo_id, $tipo_documento, $titulo, $filename]);
            echo json_encode(['success' => true]);
            break;
            
        case 'upload_foto':
            $vehiculo_id = $_POST['vehiculo_id'] ?? 0;
            $descripcion = $_POST['descripcion'] ?? '';
            
            if(empty($vehiculo_id) || empty($descripcion)) throw new Exception('Datos incompletos');
            if(!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) throw new Exception('Error subiendo archivo');
            
            $file = $_FILES['archivo'];
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            if(!in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'webp'])) throw new Exception('Solo se permiten imágenes (jpg, png, webp)');
            
            $filename = uniqid('foto_') . '.' . $ext;
            $dest = __DIR__ . '/../uploads/activos/' . $filename;
            
            if(!is_dir(__DIR__ . '/../uploads/activos')) mkdir(__DIR__ . '/../uploads/activos', 0777, true);
            if(!move_uploaded_file($file['tmp_name'], $dest)) throw new Exception('Error guardando imagen en servidor');
            
            stampImageWithDate($dest); // Añadir marca de agua de fecha y hora
            
            $stmt = $pdo->prepare("INSERT INTO activos_imagenes (vehiculo_id, url_imagen, descripcion) VALUES (?, ?, ?)");
            $stmt->execute([$vehiculo_id, $filename, $descripcion]);
            echo json_encode(['success' => true]);
            break;
            
        case 'save_evento':
            $vehiculo_id = $_POST['vehiculo_id'] ?? 0;
            $tipo_evento = $_POST['tipo_evento'] ?? '';
            $fecha_evento = $_POST['fecha_evento'] ?? '';
            $costo = $_POST['costo'] ?? 0;
            $descripcion = $_POST['descripcion'] ?? '';
            
            if(empty($vehiculo_id) || empty($tipo_evento) || empty($fecha_evento) || empty($descripcion)) {
                throw new Exception('Datos incompletos');
            }
            
            $fotos_subidas = [];
            if(isset($_FILES['fotos_evento']) && is_array($_FILES['fotos_evento']['name'])) {
                $total = count($_FILES['fotos_evento']['name']);
                for($i = 0; $i < $total; $i++) {
                    if($_FILES['fotos_evento']['error'][$i] === UPLOAD_ERR_OK) {
                        $ext = pathinfo($_FILES['fotos_evento']['name'][$i], PATHINFO_EXTENSION);
                        if(in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'webp'])) {
                            $filename = uniqid('ev_') . '.' . $ext;
                            $dest = __DIR__ . '/../uploads/activos/' . $filename;
                            if(!is_dir(__DIR__ . '/../uploads/activos')) mkdir(__DIR__ . '/../uploads/activos', 0777, true);
                            if(move_uploaded_file($_FILES['fotos_evento']['tmp_name'][$i], $dest)) {
                                stampImageWithDate($dest);
                                $fotos_subidas[] = $filename;
                            }
                        }
                    }
                }
            }
            $fotos_json = !empty($fotos_subidas) ? json_encode($fotos_subidas) : null;
            
            $stmt = $pdo->prepare("INSERT INTO activos_historial (vehiculo_id, tipo_evento, fecha_evento, costo, descripcion, fotos_adjuntas, registrado_por) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$vehiculo_id, $tipo_evento, $fecha_evento, $costo, $descripcion, $fotos_json, $_SESSION['user_id']]);
            echo json_encode(['success' => true]);
            break;
            
        case 'edit_activo':
        case 'edit_vehiculo':
            $id = $_POST['id'] ?? 0;
            $categoria = $_POST['categoria'] ?? 'vehiculo';
            $tipo = $_POST['tipo'] ?? '';
            $nombre = $_POST['nombre'] ?? '';
            $placa = $_POST['placa'] ?? $_POST['codigo_identificador'] ?? '';
            $codigo_identificador = $_POST['codigo_identificador'] ?? $placa;
            $marca = $_POST['marca'] ?? '';
            $modelo = $_POST['modelo'] ?? '';
            $ubicacion = $_POST['ubicacion'] ?? '';
            $responsable_nombre = $_POST['responsable_nombre'] ?? '';
            $detalles_extra = $_POST['detalles_extra'] ?? null;
            $valor_adquisicion = !empty($_POST['valor_adquisicion']) ? (float)$_POST['valor_adquisicion'] : 0.00;
            $fecha_adquisicion = !empty($_POST['fecha_adquisicion']) ? $_POST['fecha_adquisicion'] : null;
            $estado = $_POST['estado'] ?? 'activo';
            
            if(empty($id) || empty($tipo) || (empty($placa) && empty($nombre) && empty($codigo_identificador))){
                throw new Exception('ID, Tipo y al menos nombre/código/placa son obligatorios');
            }
            
            if(empty($nombre)) {
                $nombre = ucfirst($tipo) . (!empty($marca) ? " $marca" : '') . (!empty($modelo) ? " $modelo" : '');
            }
            if(empty($placa)) {
                $placa = $codigo_identificador;
            }
            
            $stmt = $pdo->prepare("
                UPDATE activos_vehiculos 
                SET tipo=?, categoria=?, nombre=?, placa=?, codigo_identificador=?, marca=?, modelo=?, ubicacion=?, responsable_nombre=?, detalles_extra=?, valor_adquisicion=?, fecha_adquisicion=?, estado=? 
                WHERE id=?
            ");
            $stmt->execute([
                $tipo, 
                $categoria, 
                $nombre, 
                $placa, 
                $codigo_identificador, 
                $marca, 
                $modelo, 
                $ubicacion, 
                $responsable_nombre, 
                $detalles_extra, 
                $valor_adquisicion, 
                $fecha_adquisicion, 
                $estado, 
                $id
            ]);
            echo json_encode(['success' => true]);
            break;

        case 'edit_doc':
            $doc_id = $_POST['doc_id'] ?? 0;
            $tipo_documento = $_POST['tipo_documento'] ?? '';
            $titulo = $_POST['titulo'] ?? '';
            
            if(empty($doc_id) || empty($tipo_documento) || empty($titulo)){
                throw new Exception('Datos incompletos');
            }
            
            $stmt = $pdo->prepare("UPDATE activos_documentos SET tipo_documento=?, titulo=? WHERE id=?");
            $stmt->execute([$tipo_documento, $titulo, $doc_id]);
            echo json_encode(['success' => true]);
            break;
            
        case 'delete_doc':
            $doc_id = $_POST['doc_id'] ?? 0;
            if(empty($doc_id)) throw new Exception('ID requerido');
            
            $stmt = $pdo->prepare("SELECT url_archivo FROM activos_documentos WHERE id=?");
            $stmt->execute([$doc_id]);
            $doc = $stmt->fetch();
            if($doc) {
                $file_path = '../uploads/activos/' . $doc['url_archivo'];
                if(file_exists($file_path)) unlink($file_path);
                
                $del = $pdo->prepare("DELETE FROM activos_documentos WHERE id=?");
                $del->execute([$doc_id]);
            }
            echo json_encode(['success' => true]);
            break;

        default:
            throw new Exception('Acción no válida');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
