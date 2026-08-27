<?php
require 'config/db.php';
$stmt = $pdo->query("DESCRIBE actas");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
