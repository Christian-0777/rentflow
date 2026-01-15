<?php
// admin/payments.php
// Shows arrears, next payment, paid status

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/auth.php';

// ✅ Allow admin and treasury
require_role(['admin', 'treasury']);

// Handle mark payment status and next payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_next_payment'])) {
    $lease_id = $_POST['lease_id'];
    $action = $_POST['action'];
    $due_date = $_POST['due_date'];
    $amount_due = $_POST['amount_due'];
    
    if ($action === 'paid') {
        // Get the next due
        $due = $pdo->prepare("SELECT due_date, amount_due FROM dues WHERE lease_id = ? AND paid = 0 ORDER BY due_date ASC LIMIT 1");
        $due->execute([$lease_id]);
        $d = $due->fetch();
        if ($d) {
            // Mark as paid
            $pdo->prepare("UPDATE dues SET paid = 1 WHERE lease_id = ? AND due_date = ? AND amount_due = ? AND paid = 0");
            $pdo->prepare("UPDATE dues SET paid = 1 WHERE lease_id = ? AND paid = 0 ORDER BY due_date ASC LIMIT 1")->execute([$lease_id]);
            // Insert payment
            $pdo->prepare("INSERT INTO payments (lease_id, amount, payment_date, method, remarks) VALUES (?, ?, CURDATE(), 'manual', 'Paid')")->execute([$lease_id, $d['amount_due']]);
        }
    } elseif ($action === 'partial') {
        $amount_paid = (float)$_POST['amount_paid'];
        $due = $pdo->prepare("SELECT due_date, amount_due FROM dues WHERE lease_id = ? AND paid = 0 ORDER BY due_date ASC LIMIT 1");
        $due->execute([$lease_id]);
        $d = $due->fetch();
        if ($d && $amount_paid > 0) {
            // Insert payment with partial amount
            $pdo->prepare("INSERT INTO payments (lease_id, amount, payment_date, method, remarks) VALUES (?, ?, CURDATE(), 'partial', 'Partial Payment')")->execute([$lease_id, $amount_paid]);
            // If partial, add the difference to arrears
            if ($amount_paid < $d['amount_due']) {
                $difference = $d['amount_due'] - $amount_paid;
                $pdo->prepare("INSERT INTO arrears (lease_id, total_arrears, last_updated) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE total_arrears = total_arrears + VALUES(total_arrears), last_updated = NOW()")->execute([$lease_id, $difference]);
            } elseif ($amount_paid == $d['amount_due']) {
                // If full, mark as paid
                $pdo->prepare("UPDATE dues SET paid = 1 WHERE lease_id = ? AND due_date = ? AND amount_due = ? AND paid = 0")->execute([$lease_id, $d['due_date'], $d['amount_due']]);
            }
        }
    } elseif ($action === 'notpaid') {
        // Get the last payment amount
        $last_payment = $pdo->prepare("SELECT amount FROM payments WHERE lease_id = ? ORDER BY payment_date DESC LIMIT 1");
        $last_payment->execute([$lease_id]);
        $lp = $last_payment->fetch();
        if ($lp) {
            // Add the previous payment amount to arrears
            $pdo->prepare("INSERT INTO arrears (lease_id, total_arrears, last_updated) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE total_arrears = total_arrears + VALUES(total_arrears), last_updated = NOW()")->execute([$lease_id, $lp['amount']]);
        }
        // Insert payment record with not paid
        $pdo->prepare("INSERT INTO payments (lease_id, amount, payment_date, method, remarks) VALUES (?, 0, CURDATE(), 'manual', 'Not Paid')")->execute([$lease_id]);
    }
    
    // Insert next due
    if ($due_date && $amount_due > 0) {
        $pdo->prepare("INSERT INTO dues (lease_id, due_date, amount_due, paid) VALUES (?, ?, ?, 0)")->execute([$lease_id, $due_date, $amount_due]);
    }
    
    header("Location: payments.php");
    exit;
}

$rows = $pdo->query("
  SELECT l.id as lease_id, u.id as tenant_id, u.tenant_id as tenant_code, s.stall_no, CONCAT(u.first_name, ' ', u.last_name) AS full_name, u.business_name,
         a.total_arrears,
         COALESCE((
           SELECT SUM(penalty_amount) 
           FROM penalties p 
           WHERE p.lease_id = l.id 
           AND MONTH(p.applied_on) = MONTH(CURDATE()) 
           AND YEAR(p.applied_on) = YEAR(CURDATE())
         ), 0) as current_month_penalties,
         (SELECT due_date FROM dues WHERE lease_id=l.id AND paid=0 ORDER BY due_date ASC LIMIT 1) AS next_due,
         (SELECT amount_due FROM dues WHERE lease_id=l.id AND paid=0 ORDER BY due_date ASC LIMIT 1) AS next_amount,
         CASE
           WHEN EXISTS(SELECT 1 FROM dues WHERE lease_id=l.id AND paid=0 AND due_date<CURDATE())
           THEN 'Overdue'
           WHEN EXISTS(SELECT 1 FROM dues WHERE lease_id=l.id AND paid=0)
           THEN 'Pending'
           ELSE 'Paid'
         END AS paid_status
  FROM leases l
  JOIN users u ON l.tenant_id=u.id
  JOIN stalls s ON l.stall_id=s.id
  LEFT JOIN arrears a ON a.lease_id=l.id
  ORDER BY s.stall_no ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Payments - RentFlow</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/rentflow/public/assets/css/layout.css">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body class="admin">

<!-- 🔹 Integrated Header -->
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
  <h1>Payment Records</h1>
  <table class="table">
    <thead>
      <tr>
        <th>Stall</th>
        <th>Tenant</th>
        <th>Business</th>
        <th>Total Arrears</th>
        <th>Previous Arrears</th>
        <th>Next Payment</th>
        <th>Paid Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r['stall_no']) ?></td>
          <td><a href="tenant_profile.php?id=<?= $r['tenant_id'] ?>"><?= htmlspecialchars($r['full_name']) ?> (<?= htmlspecialchars($r['tenant_code']) ?>)</a></td>
          <td><?= htmlspecialchars($r['business_name']) ?></td>
          <td>₱<?= number_format($r['total_arrears'] ?? 0,2) ?></td>
          <td><a href="#" onclick="showArrearsHistory(<?= $r['lease_id'] ?>, <?= ($r['total_arrears'] ?? 0) - ($r['current_month_penalties'] ?? 0) ?>)">₱<?= number_format(($r['total_arrears'] ?? 0) - ($r['current_month_penalties'] ?? 0), 2) ?></a></td>
          <td>
            <?= $r['next_due']
              ? htmlspecialchars($r['next_due']).' — ₱'.number_format($r['next_amount'],2)
              : '—' ?>
            <?php
              $pp = $pdo->prepare("SELECT payment_date, amount, remarks FROM payments WHERE lease_id=? ORDER BY payment_date DESC LIMIT 2");
              $pp->execute([$r['lease_id']]);
              $prev = $pp->fetchAll();
              if ($prev) {
                echo '<br><small>Previous:<br>';
                foreach ($prev as $p) {
                  echo htmlspecialchars($p['payment_date']).' — ₱'.number_format($p['amount'],2).' ('.htmlspecialchars($p['remarks'] ?? 'Paid').')<br>';
                }
                echo '</small>';
              }
            ?>
          </td>
          <td><span class="badge"><?= htmlspecialchars(strtoupper($r['paid_status'])) ?></span></td>
          <td>
            <a href="notifications.php?to=<?= $r['tenant_id'] ?>" class="btn small" style="margin-right: 5px;">Message</a>
            <select onchange="handlePaymentAction(this.value, <?= $r['lease_id'] ?>)">
              <option value="">Action</option>
              <option value="paid">Mark as Paid</option>
              <option value="partial">Mark as Partial Paid</option>
              <option value="notpaid">Mark as Not Paid</option>
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

<!-- Modal for next payment -->
<div id="nextPaymentModal" style="display: none; position: fixed; z-index: 1; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.4);">
  <div style="background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 300px;">
    <span onclick="closeNextPaymentModal()" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
    <h3>Input Next Payment</h3>
    <form method="post">
      <input type="hidden" id="modalLeaseId2" name="lease_id">
      <input type="hidden" id="modalAction" name="action">
      <label for="due_date">Due Date:</label><br>
      <input type="date" id="due_date" name="due_date" required><br><br>
      <label for="amount_due">Amount Due:</label><br>
      <input type="number" step="0.01" id="amount_due" name="amount_due" required><br><br>
      <label for="amount_paid" id="amountPaidLabel" style="display:none;">Amount Paid:</label><br id="amountPaidBr" style="display:none;">
      <input type="number" step="0.01" id="amount_paid" name="amount_paid" style="display:none;" placeholder="Amount Paid"><br id="amountPaidBr2" style="display:none;"><br id="amountPaidBr3" style="display:none;">
      <button type="submit" name="submit_next_payment">Submit</button>
    </form>
  </div>
</div>

<!-- Modal for arrears history -->
<div id="arrearsHistoryModal" style="display: none; position: fixed; z-index: 1; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.4);">
  <div style="background-color: #fefefe; margin: 10% auto; padding: 20px; border: 1px solid #888; width: 500px; max-height: 70vh; overflow-y: auto;">
    <span onclick="closeArrearsHistoryModal()" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
    <h3>Arrears History</h3>
    <div id="arrearsHistoryContent">
      <p>Loading...</p>
    </div>
  </div>
</div>

<script src="/rentflow/public/assets/js/table.js"></script>
<script>
function openArrearsModal(leaseId, currentArrears) {
  document.getElementById('modalLeaseId').value = leaseId;
  document.getElementById('modalArrears').value = currentArrears;
  document.getElementById('arrearsModal').style.display = 'block';
}

function closeArrearsModal() {
  document.getElementById('arrearsModal').style.display = 'none';
}

function showArrearsHistory(leaseId, previousArrears) {
  document.getElementById('arrearsHistoryContent').innerHTML = '<p>Loading...</p>';
  document.getElementById('arrearsHistoryModal').style.display = 'block';

  fetch('/rentflow/api/arrears_history.php?lease_id=' + leaseId)
    .then(response => response.json())
    .then(data => {
      if (data.error) {
        document.getElementById('arrearsHistoryContent').innerHTML = '<p>Error: ' + data.error + '</p>';
        return;
      }

      let html = '<p><strong>Previous Arrears: ₱' + previousArrears.toFixed(2) + '</strong></p>';
      html += '<p><strong>Total Penalties Applied: ₱' + (data.total_penalties || 0).toFixed(2) + '</strong></p>';

      if (data.history && data.history.length > 0) {
        html += '<table class="table" style="margin-top: 15px;">';
        html += '<thead><tr><th>Date</th><th>Amount</th><th>Type</th><th>Action</th></tr></thead>';
        html += '<tbody>';
        data.history.forEach(item => {
          html += '<tr>';
          html += '<td>' + item.date + '</td>';
          html += '<td>₱' + item.amount.toFixed(2) + '</td>';
          html += '<td>' + item.type + '</td>';
          if (item.type === 'Unpaid Due') {
            html += '<td><button class="btn small" onclick="payArrear(' + leaseId + ', \'' + item.date + '\', ' + item.amount + ')">Pay</button></td>';
          } else {
            html += '<td>-</td>';
          }
          html += '</tr>';
        });
        html += '</tbody></table>';
      } else {
        html += '<p>No penalty history found.</p>';
      }

      document.getElementById('arrearsHistoryContent').innerHTML = html;
    })
    .catch(error => {
      document.getElementById('arrearsHistoryContent').innerHTML = '<p>Error loading history: ' + error.message + '</p>';
    });
}

function closeArrearsHistoryModal() {
  document.getElementById('arrearsHistoryModal').style.display = 'none';
}

function payArrear(leaseId, dueDate, amount) {
  const amountPaid = prompt('Enter amount to pay for this arrear (₱' + amount.toFixed(2) + '):', amount.toFixed(2));
  if (amountPaid && parseFloat(amountPaid) > 0) {
    fetch('/rentflow/api/pay_arrear.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: 'lease_id=' + leaseId + '&due_date=' + encodeURIComponent(dueDate) + '&amount_paid=' + encodeURIComponent(amountPaid)
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        alert('Arrear payment recorded successfully.');
        // Refresh the history
        showArrearsHistory(leaseId, 0); // Assuming previousArrears is 0 or recalculate
      } else {
        alert('Error: ' + (data.error || 'Unknown error'));
      }
    })
    .catch(error => {
      alert('Error processing payment: ' + error.message);
    });
  }
}

function handlePaymentAction(action, leaseId) {
  if (action === 'paid' || action === 'notpaid' || action === 'partial') {
    document.getElementById('modalLeaseId2').value = leaseId;
    document.getElementById('modalAction').value = action;
    // Set default due date to next month
    const nextMonth = new Date();
    nextMonth.setMonth(nextMonth.getMonth() + 1);
    document.getElementById('due_date').value = nextMonth.toISOString().split('T')[0];
    if (action === 'partial') {
      document.getElementById('amountPaidLabel').style.display = 'block';
      document.getElementById('amountPaidBr').style.display = 'block';
      document.getElementById('amount_paid').style.display = 'block';
      document.getElementById('amount_paid').required = true;
      document.getElementById('amountPaidBr2').style.display = 'block';
      document.getElementById('amountPaidBr3').style.display = 'block';
    } else {
      document.getElementById('amountPaidLabel').style.display = 'none';
      document.getElementById('amountPaidBr').style.display = 'none';
      document.getElementById('amount_paid').style.display = 'none';
      document.getElementById('amount_paid').required = false;
      document.getElementById('amountPaidBr2').style.display = 'none';
      document.getElementById('amountPaidBr3').style.display = 'none';
    }
    document.getElementById('nextPaymentModal').style.display = 'block';
  }
}

function closeNextPaymentModal() {
  document.getElementById('nextPaymentModal').style.display = 'none';
}

// Close modals when clicking outside
window.onclick = function(event) {
  if (event.target == document.getElementById('arrearsModal')) {
    closeArrearsModal();
  }
  if (event.target == document.getElementById('nextPaymentModal')) {
    closeNextPaymentModal();
  }
  if (event.target == document.getElementById('arrearsHistoryModal')) {
    closeArrearsHistoryModal();
  }
}
</script>
</body>
</html>
