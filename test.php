<?php
require_once 'config/db.php';
$stmt = $pdo->prepare("SELECT p.*, s.nombre as plan_name, u.name as agent_name 
                                     FROM crm_prospects p 
                                     LEFT JOIN servicios s ON p.interest_service_id = s.id
                                     LEFT JOIN users u ON p.assigned_to = u.id
                                     WHERE p.pipeline_id = ?");
$stmt->execute([1]);
$prospects = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "JSON: " . json_encode($prospects) . "\n";
?>