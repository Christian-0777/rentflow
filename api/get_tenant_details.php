<?php
// api/get_tenant_details.php
// Get tenant details for edit modal

require_once __DIR__.'/../config/db.php';

header('Content-Type: application/json');

$tenant_id = (int)$_GET['tenant_id'];

if (!$tenant_id) {
    echo json_encode(['error' => 'Tenant ID required']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT u.first_name, u.last_name, u.business_name, u.email, l.monthly_rent, l.lease_start, l.lease_end
    FROM users u
    JOIN leases l ON u.id = l.tenant_id
    WHERE u.id=? AND u.role='tenant'
");
$stmt->execute([$tenant_id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if ($data) {
    echo json_encode($data);
} else {
    echo json_encode(['error' => 'Tenant not found']);
}
?>