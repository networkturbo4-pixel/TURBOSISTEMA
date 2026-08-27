<?php
$file = 'c:/xampp/htdocs/TURBOSAAS/modules/inventario/index.php';
$c = file_get_contents($file);

$target = '<script src="<?php echo BASE_URL; ?>/modules/inventario/inventario_v2.js?v=<?php echo time(); ?>"></script>';
if (strpos($c, $target) !== false) {
    $replace = <<<EOT
<script src="<?php echo BASE_URL; ?>/modules/inventario/inventario.js?v=<?php echo time(); ?>"></script>
<script src="<?php echo BASE_URL; ?>/modules/inventario/inventario_v2.js?v=<?php echo time(); ?>"></script>
EOT;
    $c = str_replace($target, $replace, $c);
} else {
    // maybe without time
    $target2 = '<script src="<?php echo BASE_URL; ?>/modules/inventario/inventario_v2.js"></script>';
    $replace2 = <<<EOT
<script src="<?php echo BASE_URL; ?>/modules/inventario/inventario.js?v=<?php echo time(); ?>"></script>
<script src="<?php echo BASE_URL; ?>/modules/inventario/inventario_v2.js?v=<?php echo time(); ?>"></script>
EOT;
    $c = str_replace($target2, $replace2, $c);
}

file_put_contents($file, $c);
echo "done";
?>
