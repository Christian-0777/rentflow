<?php
// api/payments_record.php
// Records tenant payments, updates dues and arrears, generates receipt

require_once __DIR__.'/../config/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$leaseId = (int)($_POST['lease_id'] ?? 0);
$amount = (float)($_POST['amount'] ?? 0);
$method = $_POST['method'] ?? 'cash';
$txnId  = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);

if (!$leaseId || $amount<=0) {
  header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/admin/payments.php'));
  exit;
}

// Insert payment
$pdo->prepare("INSERT INTO payments (lease_id, amount, payment_date, method, transaction_id, remarks) VALUES (?,?,CURDATE(),?,?, 'Recorded')")
    ->execute([$leaseId, $amount, $method, $txnId]);
$paymentId = $pdo->lastInsertId();

// Mark nearest due as paid if amount covers it (simple logic)
$due = $pdo->prepare("SELECT id, amount_due FROM dues WHERE lease_id=? AND paid=0 ORDER BY due_date ASC LIMIT 1");
$due->execute([$leaseId]);
$d = $due->fetch();

if ($d && $amount >= (float)$d['amount_due']) {
  $pdo->prepare("UPDATE dues SET paid=1 WHERE id=?")->execute([$d['id']]);
}

// Reduce arrears if any
$pdo->prepare("UPDATE arrears SET total_arrears = GREATEST(total_arrears - ?, 0), last_updated=NOW() WHERE lease_id=?")
    ->execute([$amount, $leaseId]);

// Generate receipt path (placeholder)
$receiptPath = "/public/receipts/receipt-$paymentId.html";
$pdo->prepare("UPDATE payments SET receipt_path=? WHERE id=?")->execute([$receiptPath, $paymentId]);

header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/admin/payments.php'));
