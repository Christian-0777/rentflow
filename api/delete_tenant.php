<?php
// api/delete_tenant.php
// Allows admin to delete (deactivate) a tenant account

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

// Require admin role
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$tenantId = (int)($_POST['tenant_id'] ?? 0);

if (!$tenantId) {
    http_response_code(400);
    echo json_encode(['error' => 'Tenant ID required']);
    exit;
}

// Check if tenant exists
$stmt = $pdo->prepare("SELECT id, status FROM users WHERE id = ? AND role = 'tenant'");
$stmt->execute([$tenantId]);
$tenant = $stmt->fetch();

if (!$tenant) {
    http_response_code(404);
    echo json_encode(['error' => 'Tenant not found']);
    exit;
}

if ($tenant['status'] === 'inactive') {
    http_response_code(400);
    echo json_encode(['error' => 'Tenant already inactive']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Set tenant status to inactive
    $pdo->prepare("UPDATE users SET status = 'inactive' WHERE id = ?")->execute([$tenantId]);

    // If tenant has an active lease, end it and make stall available
    $leaseStmt = $pdo->prepare("SELECT id, stall_id FROM leases WHERE tenant_id = ? AND lease_end IS NULL");
    $leaseStmt->execute([$tenantId]);
    $lease = $leaseStmt->fetch();

    if ($lease) {
        // End the lease
        $pdo->prepare("UPDATE leases SET lease_end = CURDATE() WHERE id = ?")->execute([$lease['id']]);

        // Make stall available
        $pdo->prepare("UPDATE stalls SET status = 'available' WHERE id = ?")->execute([$lease['stall_id']]);
    }

    $pdo->commit();

    echo json_encode(['success' => 'Tenant account deactivated successfully']);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Failed to deactivate tenant: ' . $e->getMessage()]);
}
?>