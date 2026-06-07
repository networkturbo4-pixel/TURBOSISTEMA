<?php
$file = 'modules/inventario/inventario_v2.js';
$c = file_get_contents($file);
echo 'Open: ' . substr_count($c, '{') . "\n";
echo 'Close: ' . substr_count($c, '}') . "\n";
