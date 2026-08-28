<?php
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['action'] = 'get_board';
$_GET['pipeline_id'] = 1;
require 'ajax.php';
?>