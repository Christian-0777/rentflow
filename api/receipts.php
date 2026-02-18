<?php
// api/receipts.php
// Generates and serves payment receipts (simple HTML; swap with PDF generator if needed)

require_once __DIR__.'/../config/db.php';

$paymentId = (int)($_GET['id'] ?? 0);
if (!$paymentId) { die('Invalid payment.'); }

$stmt = $pdo->prepare("
  SELECT p.*, CONCAT(u.first_name, ' ', u.last_name) AS full_name, u.business_name, s.stall_no
  FROM payments p
  JOIN leases l ON p.lease_id=l.id
  JOIN users u ON l.tenant_id=u.id
  JOIN stalls s ON l.stall_id=s.id
  WHERE p.id=?
");
$stmt->execute([$paymentId]);
$p = $stmt->fetch();
if (!$p) { die('Not found.'); }

?>
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Receipt #<?= $paymentId ?></title></head>
<body>
  <h1>RentFlow Receipt</h1>
  <p><strong>Tenant:</strong> <?= htmlspecialchars($p['full_name']) ?> (<?= htmlspecialchars($p['business_name']) ?>)</p>
  <p><strong>Stall:</strong> <?= htmlspecialchars($p['stall_no']) ?></p>
  <p><strong>Amount:</strong> ₱<?= number_format($p['amount'],2) ?></p>
  <p><strong>Date:</strong> <?= htmlspecialchars($p['payment_date']) ?></p>
  <p><strong>Method:</strong> <?= htmlspecialchars($p['method']) ?></p>
  <p><strong>Transaction ID:</strong> <?= htmlspecialchars($p['transaction_id']) ?></p>
  <hr>
  <p>Thank you for your payment.</p>
</body>
</html>
