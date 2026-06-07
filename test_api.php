<?php
$_SERVER['HTTP_HOST'] = 'localhost';
session_start();
$_SESSION['public_cliente_id'] = 1; // Ajusta según sea el ID real de tu cliente en la BD
$_GET['action'] = 'get_devices';

ob_start();
require 'ajax/router_api.php';
$output = ob_get_clean();
echo "Salida del API: \n" . $output;
