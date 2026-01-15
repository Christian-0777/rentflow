<?php
// api/pay_arrear.php
// Pay a specific arrear (unpaid due)

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/auth.php';

// Require admin or treasury
require_role(['admin', 'treasury']);

$leaseId = (int)($_POST['lease_id'] ?? 0);
$dueDate = $_POST['due_date'] ?? '';
$amountPaid = (float)($_POST['amount_paid'] ?? 0);

if (!$leaseId || !$dueDate || $amountPaid <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid parameters']);
    exit;
}

// Get the due
$due = $pdo->prepare("SELECT amount_due FROM dues WHERE lease_id = ? AND due_date = ? AND paid = 0");
$due->execute([$leaseId, $dueDate]);
$d = $due->fetch();

if (!$d) {
    http_response_code(404);
    echo json_encode(['error' => 'Due not found or already paid']);
    exit;
}

$fullAmount = $d['amount_due'];

// Insert payment
$pdo->prepare("INSERT INTO payments (lease_id, amount, payment_date, method, remarks) VALUES (?, ?, CURDATE(), 'arrear_payment', 'Arrear Payment for " . $dueDate . "')")->execute([$leaseId, $amountPaid]);

if ($amountPaid >= $fullAmount) {
    // Mark as paid
    $pdo->prepare("UPDATE dues SET paid = 1 WHERE lease_id = ? AND due_date = ?")->execute([$leaseId, $dueDate]);
    // If overpaid, perhaps add to next or something, but for now, just mark paid
} else {
    // Partial, add remaining to arrears
    $remaining = $fullAmount - $amountPaid;
    $pdo->prepare("INSERT INTO arrears (lease_id, total_arrears, last_updated) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE total_arrears = total_arrears + VALUES(total_arrears), last_updated = NOW()")->execute([$leaseId, $remaining]);
}

echo json_encode(['success' => true]);
?>