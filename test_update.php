<?php
require 'config/db.php';
$_POST['action'] = 'update_product';
$_POST['product_id'] = 1;
$_POST['name'] = 'Test';
$_POST['category_id'] = 1;
$_POST['stock_minimo'] = 10;
$_POST['stock_critico'] = 3;
$_POST['description'] = 'Test desc';
$_POST['requires_photos'] = 1;
$_POST['custom_columns'] = '[]';
$_SESSION['user_id'] = 1;

            $product_id = intval($_POST['product_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $category_id = $_POST['category_id'] ?? null;
            $stock_minimo = intval($_POST['stock_minimo'] ?? 10);
            $stock_critico = intval($_POST['stock_critico'] ?? 3);
            $description = trim($_POST['description'] ?? '');
            $custom_columns = $_POST['custom_columns'] ?? '[]';
            
            $requires_photos = isset($_POST['requires_photos']) && $_POST['requires_photos'] == '1' ? 1 : 0;
            
            try {
                $stmt = $pdo->prepare("UPDATE inventory_products SET name = ?, category_id = ?, stock_minimo = ?, stock_critico = ?, description = ?, custom_columns = ?, requires_photos = ? WHERE id = ?");
                $stmt->execute([$name, $category_id ?: null, $stock_minimo, $stock_critico, $description, $custom_columns, $requires_photos, $product_id]);
                
                // Sync product_image thumbnail with first photo
                $firstPhoto = $pdo->prepare("SELECT ruta_archivo FROM inventory_product_photos WHERE product_id = ? ORDER BY id ASC LIMIT 1");
                $firstPhoto->execute([$product_id]);
                $thumb = $firstPhoto->fetchColumn();
                $pdo->prepare("UPDATE inventory_products SET product_image = ? WHERE id = ?")->execute([$thumb ?: null, $product_id]);
                
                echo "UPDATE COMPLETE\n";
            } catch(PDOException $e) {
                echo "UPDATE EXCEPTION: " . $e->getMessage() . "\n";
            }
