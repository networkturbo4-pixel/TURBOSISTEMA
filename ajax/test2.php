<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
$_SERVER['HTTP_HOST'] = 'localhost';
$_POST['action'] = 'get_deleted_items';

ob_start();
require 'inventario.php';
$output = ob_get_clean();

echo "OUTPUT:\n" . $output . "\n";
echo "JSON ERROR:\n" . json_last_error_msg() . "\n";
