<?php
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';
ob_start();
require 'modules/inventario/index.php';
$out = ob_get_clean();
echo "Length: " . strlen($out) . "\n";
$pos = strpos($out, 'inventario.js');
echo "Has script: " . ($pos !== false ? "YES" : "NO") . "\n";
file_put_contents('test_index.html', $out);
