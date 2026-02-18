<?php
// api/pay_arrear.php
// Pay a specific arrear entry

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
    
    // Insert payment record
    $pdo->prepare("
        INSERT INTO payments (lease_id, amount, payment_date, method, remarks) 
        VALUES (?, ?, CURDATE(), 'arrear_payment', 'Arrear Payment for " . $dueDate . "')
    ")->execute([$leaseId, $amountPaid]);
    
    if ($amountPaid >= $fullAmount) {
        // Mark arrear entry as paid
        $pdo->prepare("
            UPDATE arrear_entries 
            SET is_paid = 1, paid_on = CURDATE() 
            WHERE id = ?
        ")->execute([$entry['id']]);
        
        // Update total arrears
        $pdo->prepare("
            UPDATE arrears 
            SET total_arrears = total_arrears - ? 
            WHERE lease_id = ?
        ")->execute([$fullAmount, $leaseId]);
    } else {
        // Partial payment - create new arrear entry for remaining amount
        $remaining = $fullAmount - $amountPaid;
        
        $pdo->prepare("
            UPDATE arrear_entries 
            SET amount = amount - ?, is_paid = 0
            WHERE id = ?
        ")->execute([$amountPaid, $entry['id']]);
        
        // Update total arrears
        $pdo->prepare("
            UPDATE arrears 
            SET total_arrears = total_arrears - ? 
            WHERE lease_id = ?
        ")->execute([$amountPaid, $leaseId]);
    }
    
    $pdo->commit();
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>