<?php
$file = 'c:/xampp/htdocs/TURBOSAAS/modules/inventario/inventario.js';
$c = file_get_contents($file);
$c = str_replace('.inv-tabs-bar', '.inv-toolbar-tabs', $c);
file_put_contents($file, $c);
echo "done";
?>
