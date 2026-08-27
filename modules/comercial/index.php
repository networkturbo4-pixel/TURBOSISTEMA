<?php
require_once '../../config/db.php';
requireLogin();
requirePermission($pdo, 'comercial');
header('Location: ' . BASE_URL . '/modules/comercial/crm');
exit;

