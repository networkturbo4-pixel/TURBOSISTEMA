<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';
ob_start();
require 'index.php';
$out = ob_get_clean();
echo strlen($out);
