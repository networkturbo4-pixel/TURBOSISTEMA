<?php
$dest = 'produccion_final_v4.sql';

$sql = "\n\n-- Cambios para el Perfil de Usuario\n";
$sql .= "ALTER TABLE users ADD COLUMN IF NOT EXISTS username VARCHAR(50) DEFAULT NULL AFTER id;\n";
$sql .= "ALTER TABLE users ADD COLUMN IF NOT EXISTS cover_picture VARCHAR(255) DEFAULT NULL AFTER profile_picture;\n\n";

$sql .= "-- Cambios para las Actas\n";
$sql .= "ALTER TABLE actas ADD COLUMN IF NOT EXISTS cliente_rotulado TINYINT(1) DEFAULT 0 AFTER cliente_whatsapp;\n\n";

$sql .= "-- Cambios para Inventario y Mochila (EPP)\n";
$sql .= "ALTER TABLE inventory_skus ADD COLUMN IF NOT EXISTS is_epp TINYINT(1) DEFAULT 0 AFTER status;\n";
$sql .= "ALTER TABLE inventory_user_stock ADD COLUMN IF NOT EXISTS is_epp TINYINT(1) DEFAULT 0 AFTER quantity;\n\n";

$sql .= "-- Cambios para las Variantes de Productos\n";
$sql .= "ALTER TABLE inventory_products ADD COLUMN IF NOT EXISTS product_type VARCHAR(20) DEFAULT 'simple' AFTER is_bulk;\n";
$sql .= "ALTER TABLE inventory_products ADD COLUMN IF NOT EXISTS parent_product_id INT(11) DEFAULT NULL AFTER product_type;\n";
$sql .= "ALTER TABLE inventory_products ADD COLUMN IF NOT EXISTS variant_brand VARCHAR(100) DEFAULT NULL AFTER parent_product_id;\n";
$sql .= "ALTER TABLE inventory_products ADD COLUMN IF NOT EXISTS variant_size VARCHAR(50) DEFAULT NULL AFTER variant_brand;\n";
$sql .= "ALTER TABLE inventory_products ADD COLUMN IF NOT EXISTS variant_attributes TEXT DEFAULT NULL AFTER variant_size;\n";

file_put_contents($dest, $sql, FILE_APPEND);
echo "Archivo v4 modificado correctamente";
?>
