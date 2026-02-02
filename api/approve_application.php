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
        ? 'Your stall application has been approved. An admin will assign you a stall soon.'
        : 'Your stall application has been rejected. Please contact support for more information.';

    $pdo->prepare("INSERT INTO notifications (sender_id, receiver_id, type, title, message) VALUES (?, ?, 'system', ?, ?)")
         ->execute([$_SESSION['user']['id'], $application['tenant_id'], $title, $message]);

    // If approved, find an available stall of the requested type and assign it
    if ($action === 'approve') {
        $availableStall = $pdo->prepare("SELECT id, stall_no FROM stalls WHERE type = ? AND status = 'available' ORDER BY stall_no ASC LIMIT 1");
        $availableStall->execute([$application['type']]);
        $stall = $availableStall->fetch(PDO::FETCH_ASSOC);

        if ($stall) {
            // Create lease
            $monthlyRent = 200.00; // Default rent, could be configurable
            $leaseStmt = $pdo->prepare("INSERT INTO leases (tenant_id, stall_id, lease_start, monthly_rent) VALUES (?, ?, CURDATE(), ?)");
            $leaseStmt->execute([$application['tenant_id'], $stall['id'], $monthlyRent]);
            $leaseId = $pdo->lastInsertId();

            // Create first due date (30 days from now)
            $dueDate = date('Y-m-d', strtotime('+30 days'));
            $pdo->prepare("INSERT INTO dues (lease_id, due_date, amount_due, paid) VALUES (?, ?, ?, 0)")
                 ->execute([$leaseId, $dueDate, $monthlyRent]);

            // Initialize arrears
            $pdo->prepare("INSERT INTO arrears (lease_id, total_arrears) VALUES (?, 0)")
                 ->execute([$leaseId]);

            // Update stall status
            $pdo->prepare("UPDATE stalls SET status = 'occupied' WHERE id = ?")
                 ->execute([$stall['id']]);

            // Notify tenant of stall assignment
            $pdo->prepare("INSERT INTO notifications (sender_id, receiver_id, type, title, message) VALUES (?, ?, 'system', 'Stall Assigned', ?)")
                 ->execute([$_SESSION['user']['id'], $application['tenant_id'], "Congratulations! You have been assigned stall {$stall['stall_no']}. Your lease starts today."]);
        } else {
            // No available stall, notify admin
            $pdo->prepare("INSERT INTO notifications (sender_id, receiver_id, type, title, message) VALUES (?, ?, 'system', 'Stall Assignment Needed', ?)")
                 ->execute([$_SESSION['user']['id'], $_SESSION['user']['id'], "Application {$appId} approved but no {$application['type']} stalls available. Manual assignment required."]);
        }
    }

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