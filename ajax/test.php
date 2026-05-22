<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
ob_start();
$_POST['action'] = 'list_products';
$_SESSION['user_id'] = 1;
require 'inventario.php';
$output = ob_get_clean();
file_put_contents('test_output.txt', $output);
echo "Done\n";
