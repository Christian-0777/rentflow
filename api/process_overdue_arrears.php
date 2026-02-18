<?php
/**
 * api/process_overdue_arrears.php
 * Automatically adds overdue payments (>=7 days) to arrears
 * Should be called via CRON job daily
 */

require_once __DIR__.'/../config/db.php';

// Optional: Check for cron job authorization header
if (isset($_GET['token'])) {
    // You can add token validation here
    // if ($_GET['token'] !== CRON_TOKEN) {
    //     http_response_code(401);
    //     exit('Unauthorized');
    // }
}

try {
    // Find all unpaid dues that are 7+ days overdue
    $overdueCheck = $pdo->query("
        SELECT l.id as lease_id, d.id as due_id, d.due_date, d.amount_due
        FROM dues d
        JOIN leases l ON d.lease_id = l.id
        WHERE d.paid = 0 
        AND d.due_date <= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        AND NOT EXISTS (
            SELECT 1 FROM arrears a 
            WHERE a.lease_id = l.id 
            AND a.trigger_reason = 'overdue_7days'
            AND DATE(a.triggered_date) >= DATE_SUB(CURDATE(), INTERVAL 1 DAY)
        )
    ")->fetchAll(PDO::FETCH_ASSOC);

    $processedCount = 0;

    // Add overdue amounts to arrears
    foreach ($overdueCheck as $overdue) {
        $existing = $pdo->prepare("SELECT id, total_arrears FROM arrears WHERE lease_id = ?");
        $existing->execute([$overdue['lease_id']]);
        $arr = $existing->fetch(PDO::FETCH_ASSOC);
        
        if ($arr) {
            // Update existing arrears record
            $pdo->prepare("
                UPDATE arrears 
                SET total_arrears = total_arrears + ?, 
                    triggered_date = NOW(),
                    trigger_reason = 'overdue_7days',
                    last_updated = NOW() 
                WHERE lease_id = ?
            ")->execute([$overdue['amount_due'], $overdue['lease_id']]);
        } else {
            // Insert new arrears record
            $pdo->prepare("
                INSERT INTO arrears (lease_id, total_arrears, previous_arrears, triggered_date, trigger_reason, last_updated) 
                VALUES (?, ?, 0, NOW(), 'overdue_7days', NOW())
            ")->execute([$overdue['lease_id'], $overdue['amount_due']]);
        }
        
        $processedCount++;
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => "Processed {$processedCount} overdue payments (7+ days) and added to arrears",
        'processed_count' => $processedCount,
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
