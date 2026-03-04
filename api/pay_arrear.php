<?php
// api/pay_arrear.php
// Pay a specific arrear entry

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/auth.php';
require_once __DIR__.'/../config/mailer.php';

// Require admin role (treasury role removed)
require_role('admin');

$leaseId = (int)($_POST['lease_id'] ?? 0);
$dueDate = $_POST['due_date'] ?? '';
$amountPaid = (float)($_POST['amount_paid'] ?? 0);
$penaltyPaid = (float)($_POST['penalty'] ?? 0);

if (!$leaseId || !$dueDate || $amountPaid <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid parameters']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Get the arrear entry
    $arrearEntry = $pdo->prepare("
        SELECT id, amount FROM arrear_entries 
        WHERE lease_id = ? AND created_on = ? AND is_paid = 0
        LIMIT 1
    ");
    $arrearEntry->execute([$leaseId, $dueDate]);
    $entry = $arrearEntry->fetch();
    
    if (!$entry) {
        http_response_code(404);
        echo json_encode(['error' => 'Arrear entry not found or already paid']);
        exit;
    }
    
    $fullAmount = $entry['amount'];
    
    // compute combined payment including any penalty
    $totalPaid = $amountPaid + $penaltyPaid;
    $remarks = 'Arrear Payment for ' . $dueDate;
    if ($penaltyPaid > 0) {
        $remarks .= ' + Penalty ' . number_format($penaltyPaid, 2);
    }
    
    // Insert payment record (method must fit ENUM)
    $pdo->prepare("
        INSERT INTO payments (lease_id, amount, payment_date, method, remarks) 
        VALUES (?, ?, CURDATE(), 'manual', ?)
    ")->execute([$leaseId, $totalPaid, $remarks]);
    
    if ($amountPaid >= $fullAmount) {
        // Mark arrear entry as paid
        $pdo->prepare("
            UPDATE arrear_entries 
            SET is_paid = 1, paid_on = CURDATE() 
            WHERE id = ?
        ")->execute([$entry['id']]);
        
        // Update total arrears (deduct arrear amount and penalty if any)
        $pdo->prepare("
            UPDATE arrears 
            SET total_arrears = total_arrears - ? - ? 
            WHERE lease_id = ?
        ")->execute([$fullAmount, $penaltyPaid, $leaseId]);
    } else {
        // Partial payment - create new arrear entry for remaining amount
        $remaining = $fullAmount - $amountPaid;
        
        $pdo->prepare("
            UPDATE arrear_entries 
            SET amount = amount - ?, is_paid = 0
            WHERE id = ?
        ")->execute([$amountPaid, $entry['id']]);
        
        // Update total arrears (deduct paid amount and penalty)
        $pdo->prepare("
            UPDATE arrears 
            SET total_arrears = total_arrears - ? - ? 
            WHERE lease_id = ?
        ")->execute([$amountPaid, $penaltyPaid, $leaseId]);
    }
    
    $pdo->commit();
    
    // Notify tenant about arrear payment
    $tenantStmt = $pdo->prepare(
        "SELECT u.id, u.email, CONCAT(u.first_name,' ',u.last_name) AS name \
         FROM users u JOIN leases l ON u.id = l.tenant_id WHERE l.id = ?"
    );
    $tenantStmt->execute([$leaseId]);
    $tenant = $tenantStmt->fetch();
    if ($tenant) {
        $tId = $tenant['id'];
        $email = $tenant['email'];
        $title = 'Arrears Payment Received';
        $message = "A payment of ₱" . number_format($amountPaid + $penaltyPaid,2) . " has been applied to your arrears for due date " . htmlspecialchars($dueDate) . ".";

        if ($amountPaid < $fullAmount) {
            $message .= " Remaining ₱" . number_format($fullAmount - $amountPaid,2) . " is still owed.";
        }

        $pdo->prepare("INSERT INTO notifications (sender_id, receiver_id, type, title, message) VALUES (?, ?, 'system', ?, ?)")
            ->execute([$_SESSION['user']['id'], $tId, $title, $message]);

        if ($email) {
            $subject = "[RentFlow] " . $title;
            $body = "Hello " . htmlspecialchars($tenant['name']) . ",\n\n" . $message . "\n\nRegards,\nRentFlow Administration";
            send_mail($email, $subject, $body);
        }
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>