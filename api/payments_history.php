<?php
// api/payments_history.php
// Returns payment history for a lease

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

// Require admin role (treasury role removed)
require_role('admin');

$leaseId = (int)($_GET['lease_id'] ?? 0);

if (!$leaseId) {
    http_response_code(400);
    echo json_encode(['error' => 'Lease ID required']);
    exit;
}

$stmt = $pdo->prepare(
    "SELECT payment_date as date, amount, method, remarks 
     FROM payments 
     WHERE lease_id = ? 
     ORDER BY payment_date DESC"
);
$stmt->execute([$leaseId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// cast amounts to float for consistency
$formatted = array_map(function($r) {
    return [
        'date' => $r['date'],
        'amount' => (float)$r['amount'],
        'method' => $r['method'],
        'remarks' => $r['remarks']
    ];
}, $rows);

echo json_encode(['history' => $formatted]);
