<?php
$_SERVER['HTTP_HOST'] = 'localhost';
$_POST['action'] = 'get_deleted_items';
// fake session
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['rol'] = 'admin';
require 'inventario.php';
