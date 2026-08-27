<?php
require_once 'config/db.php';
$sql = file_get_contents('update_crm.sql');
$sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql);
try {
    $pdo->exec($sql);
    echo 'SQL Executed successfully.';
} catch (PDOException $e) {
    echo 'Error: ' . $e->getMessage();
}
?>
