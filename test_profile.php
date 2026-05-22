<?php
session_start();
$_SESSION['user_id'] = 1; // admin
require 'c:/xampp/htdocs/TURBOSAAS/config/db.php';
$_POST['action'] = 'get_profile';
ob_start();
include 'c:/xampp/htdocs/TURBOSAAS/ajax/perfil.php';
$output = ob_get_clean();
echo "OUTPUT:\n" . $output;
?>
