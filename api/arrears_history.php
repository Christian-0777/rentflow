<?php
// api/arrears_history.php
// Returns arrears history for a lease (penalties applied over time)

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

// Require admin role
require_role('admin');

$leaseId = (int)($_GET['lease_id'] ?? 0);

if (!$leaseId) {
    http_response_code(400);
    echo json_encode(['error' => 'Lease ID required']);
    exit;
}

// Get penalties history for this lease
$history = $pdo->prepare("
    SELECT applied_on as date, penalty_amount as amount, 'Penalty Applied' as type
    FROM penalties
    WHERE lease_id = ?
    ORDER BY applied_on DESC
");
$history->execute([$leaseId]);
$penalties = $history->fetchAll(PDO::FETCH_ASSOC);

// Get unpaid dues as arrears
$unpaidDues = $pdo->prepare("
    SELECT due_date as date, amount_due as amount, 'Unpaid Due' as type
    FROM dues
    WHERE lease_id = ? AND paid = 0
    ORDER BY due_date DESC
");
$unpaidDues->execute([$leaseId]);
$dues = $unpaidDues->fetchAll(PDO::FETCH_ASSOC);

// Combine and sort by date
$allHistory = array_merge($penalties, $dues);
usort($allHistory, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

// Format the response
$formattedHistory = array_map(function($item) {
    return [
        'date' => $item['date'],
        'amount' => (float)$item['amount'],
        'type' => $item['type']
    ];
}, $allHistory);

echo json_encode([
    'history' => $formattedHistory,
    'total_penalties' => array_sum(array_column($penalties, 'amount'))
]);
?>