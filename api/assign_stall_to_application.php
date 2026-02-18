<?php
// api/assign_stall_to_application.php
// Assigns a stall to a tenant after their application is approved

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
$stallNo = $_POST['stall_no'] ?? '';
$leaseStartDate = $_POST['lease_start_date'] ?? '';
$monthlyRent = $_POST['monthly_rent'] ?? 0;

if (!$appId || !$stallNo || !$leaseStartDate || $monthlyRent <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'All fields are required']);
    exit;
}

// Validate lease start date format
if (!strtotime($leaseStartDate)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid lease start date']);
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

    if (!in_array($application['status'], ['pending', 'approved'])) {
        throw new Exception('Cannot assign stall to this application');
    }

    // Get stall details
    $stall = $pdo->prepare("SELECT id, status FROM stalls WHERE stall_no = ?");
    $stall->execute([$stallNo]);
    $stallData = $stall->fetch(PDO::FETCH_ASSOC);

    if (!$stallData) {
        throw new Exception('Stall not found');
    }

    if ($stallData['status'] !== 'available') {
        throw new Exception('Stall is not available');
    }

    // Update application status to approved
    $pdo->prepare("UPDATE stall_applications SET status = 'approved' WHERE id = ?")
        ->execute([$appId]);

    // Create lease
    $pdo->prepare("INSERT INTO leases (tenant_id, stall_id, lease_start, monthly_rent) VALUES (?, ?, ?, ?)")
        ->execute([$application['tenant_id'], $stallData['id'], $leaseStartDate, $monthlyRent]);
    $leaseId = $pdo->lastInsertId();

    // Create first due date (30 days from lease start)
    $firstDueDate = date('Y-m-d', strtotime($leaseStartDate . ' +30 days'));
    $pdo->prepare("INSERT INTO dues (lease_id, due_date, amount_due, paid) VALUES (?, ?, ?, 0)")
        ->execute([$leaseId, $firstDueDate, $monthlyRent]);

    // Initialize arrears
    $pdo->prepare("INSERT INTO arrears (lease_id, total_arrears) VALUES (?, 0)")
        ->execute([$leaseId]);

    // Update stall status
    $pdo->prepare("UPDATE stalls SET status = 'occupied' WHERE id = ?")
        ->execute([$stallData['id']]);

    // Send notification to tenant
    $pdo->prepare("INSERT INTO notifications (sender_id, receiver_id, type, title, message) VALUES (?, ?, 'system', 'Stall Assigned', ?)")
        ->execute([$_SESSION['user']['id'], $application['tenant_id'], "Congratulations! You have been assigned stall {$stallNo}. Your lease starts on " . date('M d, Y', strtotime($leaseStartDate)) . "."]);

    $pdo->commit();

    echo json_encode([
        'success' => 'Stall assigned successfully',
        'lease_id' => $leaseId
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
