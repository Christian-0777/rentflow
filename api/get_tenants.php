<?php
// api/get_tenants.php
// Get list of tenants for edit modal

require_once __DIR__.'/../config/db.php';

header('Content-Type: application/json');

$stmt = $pdo->query("SELECT id, first_name, last_name, business_name FROM users WHERE role='tenant' ORDER BY first_name, last_name");
$tenants = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['tenants' => $tenants]);
?>