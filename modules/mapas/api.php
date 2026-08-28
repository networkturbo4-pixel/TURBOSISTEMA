<?php
require_once __DIR__ . '/../../config/db.php';
requireLogin();

// Verificar permiso
if (!hasAccess($pdo, 'mapas')) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

// IMPORTANTE: Liberar la sesión inmediatamente. 
// Como las consultas de mapas (GeoJSON) pueden tardar segundos en cargar miles de nodos,
// si no cerramos la sesión, PHP bloquea CUALQUIER otra petición AJAX o recarga de pestaña
// del mismo usuario, haciendo que "todo el sistema se ponga lento" mientras carga el mapa.
session_write_close();

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'list_projects':
            // Intentar añadir la columna archivado si no existe (falla silenciosamente si ya existe)
            try { $pdo->exec("ALTER TABLE mapas_proyectos ADD COLUMN archivado TINYINT(1) DEFAULT 0"); } catch (Exception $e) {}

            $show_archived = $_GET['show_archived'] ?? 0;
            $where_clause = $show_archived ? "p.archivado = 1" : "(p.archivado = 0 OR p.archivado IS NULL)";

            $stmt = $pdo->query("
                SELECT p.*, 
                (SELECT geojson FROM mapas_elementos e WHERE e.proyecto_id = p.id AND e.tipo = 'Point' LIMIT 1) as preview_geojson
                FROM mapas_proyectos p 
                WHERE $where_clause
                ORDER BY p.updated_at DESC
            ");
            $projects = $stmt->fetchAll();
            echo json_encode(['success' => true, 'data' => $projects]);
            break;

        case 'edit_project':
            $id = $_POST['id'] ?? 0;
            $nombre = $_POST['nombre'] ?? '';
            if ($id && $nombre) {
                $stmt = $pdo->prepare("UPDATE mapas_proyectos SET nombre = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$nombre, $id]);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
            }
            break;

        case 'archive_project':
            $id = $_POST['id'] ?? 0;
            if ($id) {
                try { $pdo->exec("ALTER TABLE mapas_proyectos ADD COLUMN archivado TINYINT(1) DEFAULT 0"); } catch (Exception $e) {}
                $stmt = $pdo->prepare("UPDATE mapas_proyectos SET archivado = 1 WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'ID no proporcionado']);
            }
            break;

        case 'unarchive_project':
            $id = $_POST['id'] ?? 0;
            if ($id) {
                $stmt = $pdo->prepare("UPDATE mapas_proyectos SET archivado = 0 WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'ID no proporcionado']);
            }
            break;

        case 'delete_project':
            $id = $_POST['id'] ?? 0;
            if ($id) {
                // Borrar dependencias manualmente por si no hay ON DELETE CASCADE
                $pdo->prepare("DELETE FROM mapas_fotos WHERE elemento_id IN (SELECT id FROM mapas_elementos WHERE proyecto_id = ?)")->execute([$id]);
                $pdo->prepare("DELETE FROM mapas_hilos WHERE elemento_id IN (SELECT id FROM mapas_elementos WHERE proyecto_id = ?)")->execute([$id]);
                $pdo->prepare("DELETE FROM mapas_elementos WHERE proyecto_id = ?")->execute([$id]);
                $pdo->prepare("DELETE FROM mapas_proyectos WHERE id = ?")->execute([$id]);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'ID no proporcionado']);
            }
            break;

        case 'create_project':
            $nombre = $_POST['nombre'] ?? 'Nuevo Proyecto';
            $desc = $_POST['descripcion'] ?? '';
            $stmt = $pdo->prepare("INSERT INTO mapas_proyectos (nombre, descripcion) VALUES (?, ?)");
            $stmt->execute([$nombre, $desc]);
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
            break;

        case 'import_geojson':
            $proyecto_id = $_POST['proyecto_id'] ?? 0;
            $features_json = $_POST['features'] ?? '[]';
            $features = json_decode($features_json, true);

            if (!$proyecto_id || !is_array($features)) {
                echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO mapas_elementos (proyecto_id, tipo, nombre, descripcion, geojson, color) VALUES (?, ?, ?, ?, ?, ?)");
            $count = 0;

            foreach ($features as $f) {
                $tipo = $f['geometry']['type'] ?? 'Point';
                $nombre = $f['properties']['name'] ?? 'Elemento';
                $desc = $f['properties']['description'] ?? '';
                // Color por defecto basado en nombre
                $color = '#a78bfa';
                if (stripos($nombre, 'NAP') !== false) $color = '#facc15';
                else if (stripos($nombre, 'PRINCIPAL') !== false) $color = '#ef4444';
                else if (stripos($nombre, 'MUFA') !== false) $color = '#fb923c';
                else if ($tipo === 'LineString') $color = '#38bdf8';

                $geojson = json_encode($f['geometry']);

                $stmt->execute([$proyecto_id, $tipo, $nombre, $desc, $geojson, $color]);
                $count++;
            }

            echo json_encode(['success' => true, 'message' => "$count elementos importados"]);
            break;

        case 'create_element':
            $proyecto_id = $_POST['proyecto_id'] ?? 0;
            $tipo = $_POST['tipo'] ?? 'Point';
            $nombre = $_POST['nombre'] ?? 'Nuevo Elemento';
            $descripcion = $_POST['descripcion'] ?? '';
            $geojson = $_POST['geojson'] ?? '{}';
            $color = $_POST['color'] ?? '#a78bfa';
            $icono = $_POST['icono'] ?? 'ph-map-pin';

            if (!$proyecto_id) {
                echo json_encode(['success' => false, 'message' => 'Proyecto inválido']);
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO mapas_elementos (proyecto_id, tipo, nombre, descripcion, geojson, color, icono) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$proyecto_id, $tipo, $nombre, $descripcion, $geojson, $color, $icono]);
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
            break;

        case 'get_elements':
            $proyecto_id = $_GET['proyecto_id'] ?? 0;
            $stmt = $pdo->prepare("SELECT * FROM mapas_elementos WHERE proyecto_id = ?");
            $stmt->execute([$proyecto_id]);
            $elementos = $stmt->fetchAll();
            
            // Construir GeoJSON
            $features = [];
            foreach ($elementos as $el) {
                $features[] = [
                    'type' => 'Feature',
                    'geometry' => json_decode($el['geojson'], true),
                    'properties' => [
                        'id' => $el['id'],
                        'name' => $el['nombre'],
                        'description' => $el['descripcion'],
                        'color' => $el['color'],
                        'icono' => $el['icono']
                    ]
                ];
            }
            
            echo json_encode([
                'success' => true, 
                'geojson' => [
                    'type' => 'FeatureCollection',
                    'features' => $features
                ]
            ]);
            break;

        case 'get_element_details':
            $elemento_id = $_GET['id'] ?? 0;
            $stmt = $pdo->prepare("SELECT * FROM mapas_elementos WHERE id = ?");
            $stmt->execute([$elemento_id]);
            $elemento = $stmt->fetch();

            if ($elemento) {
                $stmtImg = $pdo->prepare("SELECT * FROM mapas_imagenes WHERE elemento_id = ? ORDER BY created_at DESC");
                $stmtImg->execute([$elemento_id]);
                $elemento['imagenes'] = $stmtImg->fetchAll();
                
                $stmtPorts = $pdo->prepare("SELECT * FROM mapas_puertos WHERE elemento_id = ? ORDER BY numero_puerto ASC");
                $stmtPorts->execute([$elemento_id]);
                $elemento['puertos'] = $stmtPorts->fetchAll();
                
                echo json_encode(['success' => true, 'data' => $elemento]);
            } else {
                echo json_encode(['success' => false, 'message' => 'No encontrado']);
            }
            break;

        case 'update_element':
            $id = $_POST['id'] ?? 0;
            $nombre = $_POST['nombre'] ?? '';
            $color = $_POST['color'] ?? '';
            $icono = $_POST['icono'] ?? '';
            $descripcion = $_POST['descripcion'] ?? '';
            
            $capacidad = (int)($_POST['capacidad_puertos'] ?? 0);
            $potencia = $_POST['potencia_dbm'] ?? '';
            $cable = $_POST['cable_origen'] ?? '';
            $splitter = $_POST['splitter_tipo'] ?? '';
            
            $stmt = $pdo->prepare("UPDATE mapas_elementos SET nombre = ?, color = ?, icono = ?, descripcion = ?, capacidad_puertos = ?, potencia_dbm = ?, cable_origen = ?, splitter_tipo = ? WHERE id = ?");
            $stmt->execute([$nombre, $color, $icono, $descripcion, $capacidad, $potencia, $cable, $splitter, $id]);
            
            // Generar puertos si se indicó capacidad
            if ($capacidad > 0) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM mapas_puertos WHERE elemento_id = ?");
                $stmt->execute([$id]);
                $count = $stmt->fetchColumn();
                if ($count < $capacidad) {
                    $stmtIns = $pdo->prepare("INSERT IGNORE INTO mapas_puertos (elemento_id, numero_puerto) VALUES (?, ?)");
                    for ($i = 1; $i <= $capacidad; $i++) {
                        $stmtIns->execute([$id, $i]);
                    }
                }
            }
            
            echo json_encode(['success' => true]);
            break;

        case 'update_puerto':
            $puerto_id = $_POST['puerto_id'] ?? 0;
            $estado = $_POST['estado'] ?? 'Disponible';
            $cliente = $_POST['cliente_nombre'] ?? '';
            
            $stmt = $pdo->prepare("UPDATE mapas_puertos SET estado = ?, cliente_nombre = ? WHERE id = ?");
            $stmt->execute([$estado, $cliente, $puerto_id]);
            
            // Historial
            $accion = "Cambio a " . $estado;
            $stmt = $pdo->prepare("INSERT INTO mapas_puertos_historial (puerto_id, accion, cliente_nombre) VALUES (?, ?, ?)");
            $stmt->execute([$puerto_id, $accion, $cliente]);
            
            echo json_encode(['success' => true]);
            break;

        case 'get_puerto_historial':
            $puerto_id = $_GET['puerto_id'] ?? 0;
            $stmt = $pdo->prepare("SELECT * FROM mapas_puertos_historial WHERE puerto_id = ? ORDER BY fecha DESC");
            $stmt->execute([$puerto_id]);
            $historial = $stmt->fetchAll();
            echo json_encode(['success' => true, 'data' => $historial]);
            break;

        case 'upload_icon':
            if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
                if (strtolower($ext) !== 'png') {
                    echo json_encode(['success' => false, 'message' => 'Solo se permiten archivos PNG']);
                    exit;
                }
                
                // Asegurar directorio icons
                $icons_dir = '../../uploads/mapas/icons/';
                if (!is_dir($icons_dir)) {
                    mkdir($icons_dir, 0777, true);
                }
                
                $filename = uniqid('icon_') . '.' . $ext;
                $path = $icons_dir . $filename;
                
                if (move_uploaded_file($_FILES['file']['tmp_name'], $path)) {
                    $db_path = 'uploads/mapas/icons/' . $filename;
                    echo json_encode(['success' => true, 'ruta' => $db_path]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error al mover archivo']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'No se recibió archivo']);
            }
            break;

        case 'upload_image':
            $elemento_id = $_POST['elemento_id'] ?? 0;
            if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
                $filename = uniqid('map_') . '.' . $ext;
                $path = '../../uploads/mapas/' . $filename;
                
                if (move_uploaded_file($_FILES['file']['tmp_name'], $path)) {
                    $db_path = 'uploads/mapas/' . $filename;
                    $stmt = $pdo->prepare("INSERT INTO mapas_imagenes (elemento_id, ruta) VALUES (?, ?)");
                    $stmt->execute([$elemento_id, $db_path]);
                    
                    echo json_encode(['success' => true, 'ruta' => $db_path, 'id' => $pdo->lastInsertId()]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error al mover archivo']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'No se recibió archivo']);
            }
            break;
            
        case 'get_global_stats':
            $total_proyectos = $pdo->query("SELECT COUNT(*) FROM mapas_proyectos WHERE archivado = 0 OR archivado IS NULL")->fetchColumn() ?: 0;
            $archivados = $pdo->query("SELECT COUNT(*) FROM mapas_proyectos WHERE archivado = 1")->fetchColumn() ?: 0;
            $total_nodos = $pdo->query("SELECT COUNT(*) FROM mapas_elementos e JOIN mapas_proyectos p ON e.proyecto_id = p.id WHERE e.tipo = 'Point' AND (p.archivado = 0 OR p.archivado IS NULL)")->fetchColumn() ?: 0;
            $total_naps = $pdo->query("SELECT COUNT(*) FROM mapas_elementos e JOIN mapas_proyectos p ON e.proyecto_id = p.id WHERE e.tipo = 'Point' AND e.nombre LIKE '%NAP%' AND (p.archivado = 0 OR p.archivado IS NULL)")->fetchColumn() ?: 0;
            $total_mufas = $pdo->query("SELECT COUNT(*) FROM mapas_elementos e JOIN mapas_proyectos p ON e.proyecto_id = p.id WHERE e.tipo = 'Point' AND e.nombre LIKE '%MUFA%' AND (p.archivado = 0 OR p.archivado IS NULL)")->fetchColumn() ?: 0;
            $total_lineas = $pdo->query("SELECT COUNT(*) FROM mapas_elementos e JOIN mapas_proyectos p ON e.proyecto_id = p.id WHERE e.tipo = 'LineString' AND (p.archivado = 0 OR p.archivado IS NULL)")->fetchColumn() ?: 0;

            echo json_encode([
                'success' => true,
                'data' => [
                    'total_proyectos' => (int)$total_proyectos,
                    'archivados' => (int)$archivados,
                    'total_nodos' => (int)$total_nodos,
                    'total_naps' => (int)$total_naps,
                    'total_mufas' => (int)$total_mufas,
                    'total_lineas' => (int)$total_lineas
                ]
            ]);
            break;

        case 'get_all_elements_summary':
            $stmt = $pdo->query("
                SELECT e.id, e.proyecto_id, e.tipo, e.nombre, e.descripcion, e.geojson, e.color, e.icono, e.potencia_dbm, e.capacidad_puertos,
                       p.nombre as proyecto_nombre, e.created_at
                FROM mapas_elementos e
                JOIN mapas_proyectos p ON e.proyecto_id = p.id
                WHERE (p.archivado = 0 OR p.archivado IS NULL)
                ORDER BY e.id DESC
            ");
            $elementos = $stmt->fetchAll();

            $features = [];
            $gps_nodes = [];

            foreach ($elementos as $el) {
                $geo = json_decode($el['geojson'], true);
                if (!$geo) continue;

                $features[] = [
                    'type' => 'Feature',
                    'geometry' => $geo,
                    'properties' => [
                        'id' => $el['id'],
                        'proyecto_id' => $el['proyecto_id'],
                        'proyecto_nombre' => $el['proyecto_nombre'],
                        'name' => $el['nombre'],
                        'tipo' => $el['tipo'],
                        'description' => $el['descripcion'],
                        'color' => $el['color'],
                        'icono' => $el['icono'],
                        'potencia_dbm' => $el['potencia_dbm'],
                        'capacidad_puertos' => $el['capacidad_puertos']
                    ]
                ];

                if ($el['tipo'] === 'Point' && isset($geo['coordinates']) && is_array($geo['coordinates'])) {
                    $lng = $geo['coordinates'][0] ?? null;
                    $lat = $geo['coordinates'][1] ?? null;
                    if ($lat !== null && $lng !== null) {
                        $gps_nodes[] = [
                            'id' => $el['id'],
                            'proyecto_id' => $el['proyecto_id'],
                            'proyecto_nombre' => $el['proyecto_nombre'],
                            'nombre' => $el['nombre'] ?: 'Nodo de Fibra',
                            'descripcion' => $el['descripcion'],
                            'color' => $el['color'],
                            'lat' => $lat,
                            'lng' => $lng,
                            'potencia_dbm' => $el['potencia_dbm'],
                            'capacidad_puertos' => $el['capacidad_puertos'],
                            'created_at' => $el['created_at']
                        ];
                    }
                }
            }

            echo json_encode([
                'success' => true,
                'geojson' => [
                    'type' => 'FeatureCollection',
                    'features' => $features
                ],
                'gps_nodes' => $gps_nodes
            ]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Acción desconocida']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
