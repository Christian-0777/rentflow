<?php
// admin/tenants.php
// Search by name/business/stall; filters and previous payments

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/auth.php';

// ✅ Allow admin and treasury
require_role(['admin', 'treasury']);

$search = $_GET['q'] ?? '';
$order = $_GET['order'] ?? 'stall_no_asc';
$status = $_GET['status'] ?? '';

$orderSql = [
  'stall_no_asc' => 's.stall_no ASC',
  'stall_no_desc'=> 's.stall_no DESC',
  'name_asc'     => 'CONCAT(u.first_name, \' \', u.last_name) ASC',
  'name_desc'    => 'CONCAT(u.first_name, \' \', u.last_name) DESC',
  'biz_asc'      => 'u.business_name ASC',
  'biz_desc'     => 'u.business_name DESC',
  'paid_asc'     => 'total_paid ASC',
  'paid_desc'    => 'total_paid DESC',
][$order] ?? 's.stall_no ASC';

$sql = "
SELECT u.id, s.stall_no, s.type AS stall_category, CONCAT(u.first_name, ' ', u.last_name) AS full_name, u.tenant_id, u.business_name, u.status,
       l.id AS lease_id,
       COALESCE((SELECT SUM(amount) FROM payments WHERE lease_id=l.id),0) AS total_paid,
       COALESCE(a.total_arrears,0) AS total_arrears
FROM leases l
JOIN users u ON l.tenant_id=u.id
JOIN stalls s ON l.stall_id=s.id
LEFT JOIN arrears a ON a.lease_id=l.id
WHERE 1=1
";

$params = [];
if ($search) {
  $sql .= " AND (CONCAT(u.first_name, ' ', u.last_name) LIKE ? OR u.business_name LIKE ? OR s.stall_no LIKE ? OR u.tenant_id LIKE ?)";
  $params = ["%$search%","%$search%","%$search%","%$search%"];
}
if ($status) {
  $sql .= " AND u.status = ?";
  $params[] = $status;
}
$sql .= " ORDER BY $orderSql";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Prepare data for CSV export
$exportData = [];
foreach ($rows as $r) {
  $exportData[] = [
    'stall_no' => $r['stall_no'],
    'category' => $r['stall_category'],
    'tenant' => $r['full_name'] . ' (' . $r['tenant_id'] . ')',
    'business' => $r['business_name'],
    'status' => $r['status'],
    'total_paid' => '₱' . number_format($r['total_paid'], 2),
    'total_arrears' => '₱' . number_format($r['total_arrears'], 2)
  ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Tenants - RentFlow</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/rentflow/public/assets/css/layout.css">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body class="admin">

<header class="header">
  <h1 class="site-title">RentFlow</h1>

  <nav class="navigation">
    <ul>
      <li><a href="dashboard.php">Dashboard</a></li>
      <li><a href="tenants.php">Tenants</a></li>
      <li><a href="payments.php" class="active">Payments</a></li>
      <li><a href="reports.php">Reports</a></li>
      <li><a href="stalls.php">Stalls</a></li>
      <li><a href="notifications.php" title="Notifications"><i class="material-icons">notifications</i></a></li>
      <li><a href="account.php" class="nav-profile" title="Admin Account"><i class="material-icons">person</i></a></li>
      <li><a href="contact.php" title="Contact Service"><i class="material-icons">contact_support</i></a></li>
      <li><a href="login.php">Logout</a></li>
    </ul>
  </nav>
</header>

<main class="content">
  <h1>Tenants</h1>

  <form class="filters" method="get">
    <input type="text" name="q" placeholder="Search name, business, stall, tenant ID" value="<?= htmlspecialchars($search) ?>">
    <select name="order">
      <option value="stall_no_asc">Stall No ↑</option>
      <option value="stall_no_desc" <?= $order==='stall_no_desc'?'selected':'' ?>>Stall No ↓</option>
      <option value="name_asc" <?= $order==='name_asc'?'selected':'' ?>>Name ↑</option>
      <option value="name_desc" <?= $order==='name_desc'?'selected':'' ?>>Name ↓</option>
      <option value="biz_asc" <?= $order==='biz_asc'?'selected':'' ?>>Business ↑</option>
      <option value="biz_desc" <?= $order==='biz_desc'?'selected':'' ?>>Business ↓</option>
      <option value="paid_asc" <?= $order==='paid_asc'?'selected':'' ?>>Total Paid ↑</option>
      <option value="paid_desc" <?= $order==='paid_desc'?'selected':'' ?>>Total Paid ↓</option>
    </select>
    <select name="status">
      <option value="">All statuses</option>
      <option value="active" <?= $status==='active'?'selected':'' ?>>Active</option>
      <option value="inactive" <?= $status==='inactive'?'selected':'' ?>>Inactive</option>
      <option value="lease_ended" <?= $status==='lease_ended'?'selected':'' ?>>Lease ended</option>
    </select>
    <button class="btn">Apply</button>
  </form>

  <section class="actions">
    <form action="/rentflow/api/export_csv.php" method="post">
      <input type="hidden" name="payload" value="<?= htmlspecialchars(json_encode($exportData)) ?>">
      <input type="hidden" name="headers" value="<?= htmlspecialchars(json_encode(['Stall No.','Category','Tenant','Business','Status','Total Paid','Total Arrears'])) ?>">
      <input type="hidden" name="filename" value="tenants_list.csv">
      <button class="btn">Export CSV</button>
    </form>
  </section>

  <table class="table">
    <thead>
      <tr>
        <th>Stall No.</th><th>Category</th><th>Tenant</th><th>Business</th><th>Status</th><th>Total Paid</th><th>Total Arrears</th><th>Previous Payments</th><th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r['stall_no']) ?></td>
          <td><strong><?= strtoupper(htmlspecialchars($r['stall_category'])) ?></strong></td>
          <td><a href="tenant_profile.php?id=<?= $r['id'] ?>"><?= htmlspecialchars($r['full_name']) ?> (<?= htmlspecialchars($r['tenant_id']) ?>)</a></td>
          <td><?= htmlspecialchars($r['business_name']) ?></td>
          <td><span class="badge"><?= htmlspecialchars(strtoupper($r['status'])) ?></span></td>
          <td>₱<?= number_format($r['total_paid'],2) ?></td>
          <td>₱<?= number_format($r['total_arrears'],2) ?></td>
          <td>
            <?php
              $pp = $pdo->prepare("SELECT payment_date, amount FROM payments WHERE lease_id=? ORDER BY payment_date DESC LIMIT 3");
              $pp->execute([$r['lease_id']]);
              foreach ($pp as $p) {
                echo "<div>".htmlspecialchars($p['payment_date'])." — ₱".number_format($p['amount'],2)."</div>";
              }
            ?>
          </td>
          <td>
            <select onchange="handleTenantAction(this.value, <?= $r['id'] ?>)">
              <option value="">Select Action</option>
              <option value="terminate">Terminate</option>
              <option value="transfer">Transfer</option>
              <option value="update_documents">Update Documents</option>
              <option value="send_message">Send Message</option>
            </select>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</main>

<!-- 🔹 Integrated Footer -->
<footer class="footer">
  <p>&copy; <?= date('Y') ?> RentFlow. All rights reserved.</p>
</footer>

<!-- Message Modal -->
<div id="messageModal" class="modal" style="display: none;">
  <div class="modal-content">
    <span onclick="closeMessageModal()" style="float: right; font-size: 28px; font-weight: bold; cursor: pointer; color: #aaa;">&times;</span>
    <h3>Send Message to Tenant</h3>
    <form action="/rentflow/api/chat_send.php" method="post">
      <input type="hidden" id="messageTo" name="receiver_id" value="">
      <label for="message">Message:</label><br>
      <textarea id="message" name="message" rows="4" placeholder="Type your message..." required></textarea><br><br>
      <button type="submit" class="btn">Send</button>
      <button type="button" onclick="closeMessageModal()" class="btn">Cancel</button>
    </form>
  </div>
</div>

<script src="/rentflow/public/assets/js/table.js"></script>
<script>
function handleTenantAction(action, tenantId) {
  if (action === 'terminate') {
    if (confirm('Are you sure you want to terminate this tenant?')) {
      // Implement terminate logic
      alert('Terminate action for tenant ' + tenantId);
    }
  } else if (action === 'transfer') {
    // Implement transfer logic
    alert('Transfer action for tenant ' + tenantId);
  } else if (action === 'update_documents') {
    // Implement update documents logic
    alert('Update documents action for tenant ' + tenantId);
  } else if (action === 'send_message') {
    // Open message modal
    document.getElementById('messageTo').value = tenantId;
    document.getElementById('messageModal').style.display = 'block';
  }
  // Reset select
  event.target.value = '';
}

function closeMessageModal() {
  document.getElementById('messageModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
  if (event.target == document.getElementById('messageModal')) {
    closeMessageModal();
  }
}
</script>
</body>
</html>
