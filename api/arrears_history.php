<?php
// api/arrears_history.php
// Returns arrears history for a lease (penalties and arrear entries)

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

// Get arrear entries (unpaid dues, partial payments, marked as not paid)
$arrearEntries = $pdo->prepare("
    SELECT created_on as date, amount, 
           CASE source
               WHEN 'unpaid_due' THEN 'Unpaid Due'
               WHEN 'marked_not_paid' THEN 'Marked Not Paid'
               WHEN 'partial_payment' THEN 'Partial Payment'
               WHEN 'overdue_7days' THEN 'Overdue (7+ days)'
           END as type
    FROM arrear_entries
    WHERE lease_id = ? AND is_paid = 0
    ORDER BY created_on DESC
");
$arrearEntries->execute([$leaseId]);
$arrears = $arrearEntries->fetchAll(PDO::FETCH_ASSOC);

// Get penalties history for this lease
$penalties = $pdo->prepare("
    SELECT applied_on as date, penalty_amount as amount, 'Penalty Applied' as type
    FROM penalties
    WHERE lease_id = ?
    ORDER BY applied_on DESC
");
$penalties->execute([$leaseId]);
$penaltyList = $penalties->fetchAll(PDO::FETCH_ASSOC);

// Combine and sort by date
$allHistory = array_merge($arrears, $penaltyList);
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
    'total_penalties' => array_sum(array_column($penaltyList, 'amount'))
]);
?>