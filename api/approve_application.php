<?php
// api/approve_application.php
// Approves or rejects a stall application

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/auth.php';

// Require admin role
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$appId = $_POST['application_id'] ?? '';
$action = $_POST['action'] ?? ''; // 'approve' or 'reject'

if (!$appId || !in_array($action, ['approve', 'reject'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Valid application ID and action required']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Get application details
    $stmt = $pdo->prepare("
        SELECT sa.*, u.email, CONCAT(u.first_name, ' ', u.last_name) AS tenant_name
        FROM stall_applications sa
        JOIN users u ON sa.tenant_id = u.id
        WHERE sa.id = ?
    ");
    $stmt->execute([$appId]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$application) {
        throw new Exception('Application not found');
    }

    if ($application['status'] !== 'pending') {
        throw new Exception('Application has already been processed');
    }

    // Update application status
    $newStatus = $action === 'approve' ? 'approved' : 'rejected';
    $pdo->prepare("UPDATE stall_applications SET status = ? WHERE id = ?")
         ->execute([$newStatus, $appId]);

    // Send notification to tenant
    $title = $action === 'approve' ? 'Application Approved' : 'Application Rejected';
    $message = $action === 'approve'
        ? 'Your stall application has been approved. An admin will assign you a stall shortly.'
        : 'Your stall application has been rejected. Please contact support for more information.';

    $pdo->prepare("INSERT INTO notifications (sender_id, receiver_id, type, title, message) VALUES (?, ?, 'system', ?, ?)")
         ->execute([$_SESSION['user']['id'], $application['tenant_id'], $title, $message]);

    $pdo->commit();

    echo json_encode([
        'success' => 'Application ' . $newStatus . ' successfully',
        'status' => $newStatus
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Failed to process application: ' . $e->getMessage()]);
}