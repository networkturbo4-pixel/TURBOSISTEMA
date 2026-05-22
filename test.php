<?php
ob_start();
$_POST['action'] = 'list_products';
$_SESSION['user_id'] = 1;
require 'ajax/inventario.php';
$output = ob_get_clean();
file_put_contents('test_output.txt', $output);
echo "Output written to test_output.txt\n";
