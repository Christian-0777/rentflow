<?php
/**
 * api/pay_arrear.php
 * Pay/Record payment for a specific unpaid due (arrear)
 */

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

try {
    // Get the due
    $due = $pdo->prepare("SELECT id, amount_due FROM dues WHERE lease_id = ? AND due_date = ? AND paid = 0");
    $due->execute([$leaseId, $dueDate]);
    $d = $due->fetch(PDO::FETCH_ASSOC);

    if (!$d) {
        http_response_code(404);
        echo json_encode(['error' => 'Due not found or already paid']);
        exit;
    }

    $fullAmount = $d['amount_due'];

    // Insert payment record
    $pdo->prepare("
        INSERT INTO payments (lease_id, amount, payment_date, method, remarks) 
        VALUES (?, ?, CURDATE(), 'arrear_payment', ?)
    ")->execute([$leaseId, $amountPaid, 'Arrear Payment for ' . $dueDate]);

    if ($amountPaid >= $fullAmount) {
        // Full or overpayment - mark as paid
        $pdo->prepare("UPDATE dues SET paid = 1 WHERE id = ?")->execute([$d['id']]);
        
        $response = [
            'success' => true,
            'message' => 'Full arrear payment recorded successfully',
            'fully_paid' => true,
            'amount_paid' => $amountPaid
        ];
    } else {
        // Partial payment - add remaining to arrears
        $remaining = $fullAmount - $amountPaid;
        
        // Update or insert arrears record
        $pdo->prepare("
            INSERT INTO arrears (lease_id, total_arrears, previous_arrears, triggered_date, trigger_reason) 
            VALUES (?, ?, 0, NOW(), 'marked_not_paid')
            ON DUPLICATE KEY UPDATE 
                total_arrears = total_arrears + VALUES(total_arrears),
                last_updated = NOW()
        ")->execute([$leaseId, $remaining]);
        
        $response = [
            'success' => true,
            'message' => 'Partial arrear payment recorded successfully',
            'fully_paid' => false,
            'amount_paid' => $amountPaid,
            'remaining' => $remaining
        ];
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>