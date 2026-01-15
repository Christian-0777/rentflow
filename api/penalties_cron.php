<?php
// api/penalties_cron.php
// Adds penalty when CURDATE() > due_date and updates arrears.total_arrears

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/constants.php';

// Find overdue dues
$overdue = $pdo->query("
  SELECT d.id AS due_id, d.lease_id, d.amount_due, d.due_date
  FROM dues d
  WHERE d.paid=0 AND d.due_date < CURDATE()
")->fetchAll();

foreach ($overdue as $o) {
  // Check if penalty already applied for this due
  $exists = $pdo->prepare("SELECT id FROM penalties WHERE due_id = ?");
  $exists->execute([$o['due_id']]);
  if ($exists->fetch()) continue; // Skip if already penalized

  // Compute days overdue
  $days = (new DateTime())->diff(new DateTime($o['due_date']))->days;
  $penalty = $o['amount_due'] * PENALTY_RATE * $days;

  // Log penalty
  $pdo->prepare("INSERT INTO penalties (lease_id, due_id, penalty_amount, applied_on) VALUES (?,?,?,CURDATE())")
      ->execute([$o['lease_id'], $o['due_id'], $penalty]);

  // Update arrears: add the due amount + penalty
  $pdo->prepare("UPDATE arrears SET total_arrears = total_arrears + ? + ?, last_updated=NOW() WHERE lease_id=?")
      ->execute([$o['amount_due'], $penalty, $o['lease_id']]);
}

// Optional: notify tenants
$leases = array_unique(array_column($overdue, 'lease_id'));
if ($leases) {
  $in = implode(',', array_fill(0, count($leases), '?'));
  $stmt = $pdo->prepare("SELECT l.id, u.id AS tenant_id FROM leases l JOIN users u ON l.tenant_id=u.id WHERE l.id IN ($in)");
  $stmt->execute($leases);
  foreach ($stmt as $row) {
    $pdo->prepare("INSERT INTO notifications (sender_id, receiver_id, type, title, message) VALUES (1, ?, 'system', 'Payment overdue', 'Late penalty applied to your account.')")
        ->execute([$row['tenant_id']]);
  }
}

echo "Penalties applied.";
