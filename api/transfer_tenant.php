<?php
// api/transfer_tenant.php
// Moves a tenant from one stall to another and frees up the old stall

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/auth.php';

require_role('admin');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$tenantId = (int)($_POST['tenant_id'] ?? 0);
$newStallId = (int)($_POST['stall_id'] ?? 0);

if (!$tenantId || !$newStallId) {
    http_response_code(400);
    echo json_encode(['error' => 'Tenant and new stall are required']);
    exit;
}

try {
    $pdo->beginTransaction();

    // get current lease
    $leaseStmt = $pdo->prepare("SELECT id, stall_id FROM leases WHERE tenant_id = ? AND lease_end IS NULL");
    $leaseStmt->execute([$tenantId]);
    $lease = $leaseStmt->fetch(PDO::FETCH_ASSOC);
    if (!$lease) {
        throw new Exception('Active lease not found for tenant');
    }
    $oldStallId = $lease['stall_id'];

    // check new stall availability
    $stallStmt = $pdo->prepare("SELECT status, stall_no FROM stalls WHERE id = ?");
    $stallStmt->execute([$newStallId]);
    $stall = $stallStmt->fetch(PDO::FETCH_ASSOC);
    if (!$stall) {
        throw new Exception('Selected stall not found');
    }
    if ($stall['status'] !== 'available') {
        throw new Exception('Selected stall is not available');
    }

    // perform updates
    $pdo->prepare("UPDATE leases SET stall_id = ? WHERE id = ?")->execute([$newStallId, $lease['id']]);
    $pdo->prepare("UPDATE stalls SET status = 'available' WHERE id = ?")->execute([$oldStallId]);
    $pdo->prepare("UPDATE stalls SET status = 'occupied' WHERE id = ?")->execute([$newStallId]);

    // send notification to tenant about transfer
    $tenantStmt = $pdo->prepare("SELECT CONCAT(first_name,' ',last_name) AS name FROM users WHERE id=?");
    $tenantStmt->execute([$tenantId]);
    $tenantName = $tenantStmt->fetchColumn() ?: '';

    $pdo->prepare("INSERT INTO notifications (sender_id, receiver_id, type, title, message) VALUES (?, ?, 'system', 'Stall Transfer', ?)")
        ->execute([$_SESSION['user']['id'], $tenantId, "Your stall has been changed to {$stall['stall_no']}. Please check your account."]);

    $pdo->commit();
    echo json_encode(['success' => 'Tenant transferred successfully']);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
