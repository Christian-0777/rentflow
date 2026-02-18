<?php
// api/chart_data.php
// Returns JSON data for charts (monthly/yearly revenue, stall availability)

require_once __DIR__.'/../config/db.php';
header('Content-Type: application/json');

$type = $_GET['type'] ?? 'monthly';

if ($type === 'availability') {
  $data = $pdo->query("
    SELECT type,
      SUM(status='occupied') AS occupied,
      SUM(status='available') AS available,
      SUM(status='maintenance') AS maintenance
    FROM stalls GROUP BY type
  ")->fetchAll();
  echo json_encode($data);
  exit;
}

if ($type === 'monthly') {
  $data = $pdo->query("
    SELECT DATE_FORMAT(payment_date,'%Y-%m') AS ym, SUM(amount) AS total
    FROM payments
    WHERE payment_date>=DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY ym ORDER BY ym
  ")->fetchAll();
  echo json_encode($data);
  exit;
}

if ($type === 'yearly') {
  $data = $pdo->query("
    SELECT YEAR(payment_date) AS y, SUM(amount) AS total
    FROM payments
    GROUP BY y ORDER BY y DESC LIMIT 5
  ")->fetchAll();
  echo json_encode($data);
  exit;
}

echo json_encode(['error'=>'Unknown type']);
