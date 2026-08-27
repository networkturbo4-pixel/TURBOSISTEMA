<?php
$_POST['action'] = 'search_sku';
$_POST['code'] = 'Mouse';
ob_start();
require 'c:/xampp/htdocs/TURBOSAAS/ajax/inventario.php';
$out = ob_get_clean();
echo $out;
