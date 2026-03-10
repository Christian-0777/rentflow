<?php
// admin/tenants.php
// Search by name/business/stall; filters and previous payments

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/auth.php';

// ✅ Admin access only (treasury role removed)
require_role('admin');

// Fetch available stalls for transfer modal
$availableStalls = $pdo->query("SELECT id, stall_no, type, location FROM stalls WHERE status='available' ORDER BY stall_no")->fetchAll();

$search = $_GET['q'] ?? '';
$category = $_GET['category'] ?? '';
$status = $_GET['status'] ?? '';

$sql = "
SELECT u.id, s.stall_no, s.type AS stall_category, CONCAT(u.first_name, ' ', u.last_name) AS full_name, u.tenant_id, u.business_name, u.status,
       l.id AS lease_id,
       COALESCE((SELECT SUM(amount) FROM payments WHERE lease_id=l.id AND remarks <> 'Marked as Not Paid'),0) AS total_paid,
       COALESCE(a.total_arrears,0) AS total_arrears,
       -- total arrears paid (payments created by arrear payment process)
       COALESCE((
           SELECT SUM(amount) FROM payments 
           WHERE lease_id=l.id AND remarks LIKE 'Arrear Payment%'
       ),0) AS total_arrears_paid
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
if ($category) {
  $sql .= " AND s.type = ?";
  $params[] = $category;
}
if ($status) {
  $sql .= " AND u.status = ?";
  $params[] = $status;
}
$sql .= " ORDER BY s.stall_no ASC";

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
    'total_arrears' => '₱' . number_format($r['total_arrears'], 2),
    'total_arrears_paid' => $r['total_arrears_paid'] > 0 ? '₱' . number_format($r['total_arrears_paid'], 2) : "None"
  ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Tenants - RentFlow</title>
  <link rel="icon" type="image/png" href="public/assets/img/icon.png">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/rentflow/public/assets/css/layout.css">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .prev-payments {
      cursor: pointer;
      text-decoration: underline;
    }
    .prev-payments:hover {
      opacity: 0.8;
    }
    .arrears-link {
      cursor: pointer;
      color: #007bff;
      text-decoration: underline;
    }
    .arrears-link:hover {
      opacity: 0.8;
    }
  </style>
</head>
<body class="admin">

<header class="header">
  <h1 class="site-title">RentFlow</h1>

  <nav class="navigation">
    <ul>
      <li><a href="dashboard.php"><i class="material-icons">dashboard</i>Dashboard</a></li>
      <li><a href="tenants.php"><i class="material-icons">people</i>Tenants</a></li>
      <li><a href="payments.php"><i class="material-icons">payments</i>Payments</a></li>
      <li><a href="reports.php"><i class="material-icons">assessment</i>Reports</a></li>
      <li><a href="stalls.php"><i class="material-icons">store</i>Stalls</a></li>
      <li><a href="messages.php" title="Messages"><i class="material-icons">mail</i>Messages</a></li>
      <li><a href="notifications.php" title="Notifications"><i class="material-icons">notifications</i>Notifications</a></li>
      <li><a href="account.php" class="nav-profile" title="Admin Account"><i class="material-icons">person</i>Account</a></li>
      <li><a href="contact.php" title="Contact Service"><i class="material-icons">contact_support</i>Contact</a></li>
    </ul>
  </nav>
</header>

<main class="content">
  <h1>Tenants</h1>

  <ul class="nav nav-tabs" id="tenantTabs" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" id="list-tab" data-bs-toggle="tab" data-bs-target="#list" type="button" role="tab" aria-controls="list" aria-selected="true">Tenant List</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="manage-tab" data-bs-toggle="tab" data-bs-target="#manage" type="button" role="tab" aria-controls="manage" aria-selected="false">Manage Tenant</button>
    </li>
  </ul>

  <div class="tab-content" id="tenantTabsContent">
    <div class="tab-pane fade show active" id="list" role="tabpanel" aria-labelledby="list-tab">
      <form class="filters mt-3" method="get">
        <input type="text" name="q" placeholder="Search by ID or name" value="<?= htmlspecialchars($search) ?>">
        <select name="category">
          <option value="">All Categories</option>
          <option value="dry" <?= $_GET['category'] === 'dry' ? 'selected' : '' ?>>Dry</option>
          <option value="wet" <?= $_GET['category'] === 'wet' ? 'selected' : '' ?>>Wet</option>
          <option value="apparel" <?= $_GET['category'] === 'apparel' ? 'selected' : '' ?>>Apparel</option>
        </select>
        <select name="status">
          <option value="">All statuses</option>
          <option value="active" <?= $status==='active'?'selected':'' ?>>Active</option>
          <option value="inactive" <?= $status==='inactive'?'selected':'' ?>>Inactive</option>
          <option value="lease_ended" <?= $status==='lease_ended'?'selected':'' ?>>Lease ended</option>
        </select>
        <button class="btn">Search</button>
      </form>

      <section class="actions mt-3">
        <form action="/rentflow/api/export_csv.php" method="post">
          <input type="hidden" name="payload" value="<?= htmlspecialchars(json_encode($exportData)) ?>">
          <input type="hidden" name="headers" value="<?= htmlspecialchars(json_encode(['Stall No.','Category','Tenant','Business','Status','Total Paid','Total Arrears','Total Arrears Paid'])) ?>">
          <input type="hidden" name="filename" value="tenants_list.csv">
          <button class="btn">Export CSV</button>
        </form>
      </section>

      <table class="table mt-3">
        <thead>
          <tr>
            <th>Stall No.</th><th>Category</th><th>Tenant Name</th><th>Business Name</th><th>Status</th><th>Total Paid</th><th>Total Arrears</th><th>Total Arrears Paid</th><th>Previous Payments</th><th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['stall_no']) ?></td>
              <td><strong><?= strtoupper(htmlspecialchars($r['stall_category'])) ?></strong></td>
              <td><a href="tenant_profile.php?id=<?= $r['id'] ?>"><?= htmlspecialchars($r['full_name']) ?> (<?= htmlspecialchars($r['tenant_id']) ?>)</a></td>
              <td><?= htmlspecialchars($r['business_name']) ?></td>
              <td><span class="badge bg-<?= $r['status'] === 'active' ? 'success' : ($r['status'] === 'inactive' ? 'warning' : 'secondary') ?>"><?= htmlspecialchars(strtoupper($r['status'])) ?></span></td>
              <td>₱<?= number_format($r['total_paid'],2) ?></td>
              <td><span class="arrears-link" onclick="showArrearsHistory(<?= $r['lease_id'] ?>)">₱<?= number_format($r['total_arrears'],2) ?></span></td>
              <td><?php
                    if ($r['total_arrears_paid'] > 0) {
                        echo '₱' . number_format($r['total_arrears_paid'],2);
                    } else {
                        echo "None";
                    }
                ?></td>
              <td>
                <span class="prev-payments" onclick="showPaymentsHistory(<?= $r['lease_id'] ?>)">
                <?php
                  $pp = $pdo->prepare("SELECT payment_date, amount FROM payments WHERE lease_id=? ORDER BY payment_date DESC LIMIT 3");
                  $pp->execute([$r['lease_id']]);
                  foreach ($pp as $p) {
                    echo "<div>" . htmlspecialchars($p['payment_date']) . " — ₱" . number_format($p['amount'],2) . "</div>";
                  }
                ?>
                </span>
              </td>
              <td>
                <select onchange="handleTenantAction(this.value, <?= $r['id'] ?>)">
                  <option value="">Select Action</option>
                  <option value="terminate">Terminate</option>
                  <option value="transfer">Transfer</option>
                  <option value="send_message">Send Message</option>
                </select>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="tab-pane fade" id="manage" role="tabpanel" aria-labelledby="manage-tab">
      <div class="mt-3">
        <button class="btn btn-primary" onclick="openAddTenantModal()">Add Tenant</button>
        <button class="btn btn-secondary" onclick="openEditTenantModal()">Edit Tenant</button>
      </div>
    </div>
  </div>
</main>

<!-- 🔹 Integrated Footer -->
<footer class="footer">
  <p>&copy; <?= date('Y') ?> RentFlow. All rights reserved.</p>
</footer>

<!-- Terminate Tenant Modal -->
<div id="terminateModal" class="modal" style="display: none;">
  <div class="modal-content">
    <span onclick="closeTerminateModal()" style="float: right; font-size: 28px; font-weight: bold; cursor: pointer; color: #aaa;">&times;</span>
    <h3>Terminate Lease</h3>
    <p>Are you sure you want to terminate this tenant's lease? This will end their lease and make the stall available.</p>
    <form id="terminateForm" method="post" action="/rentflow/api/delete_tenant.php">
      <input type="hidden" id="terminateTenantId" name="tenant_id" value="">
      <div style="display: flex; gap: 10px; margin-top: 20px;">
        <button type="submit" class="btn danger">Yes, Terminate</button>
        <button type="button" onclick="closeTerminateModal()" class="btn">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Transfer Tenant Modal -->
<div id="transferModal" class="modal" style="display: none;">
  <div class="modal-content">
    <span onclick="closeTransferModal()" style="float: right; font-size: 28px; font-weight: bold; cursor: pointer; color: #aaa;">&times;</span>
    <h3>Transfer Tenant</h3>
    <form id="transferForm" method="post" action="/rentflow/api/transfer_tenant.php">
      <input type="hidden" id="transferTenantId" name="tenant_id" value="">
      <label for="transferStallSelect">Select New Stall:</label>
      <select id="transferStallSelect" name="stall_id" required style="width:100%; padding:8px; margin-top:5px;">
        <option value="">-- Choose a stall --</option>
      </select>
      <div style="display: flex; gap: 10px; margin-top: 20px;">
        <button type="submit" class="btn">Transfer</button>
        <button type="button" onclick="closeTransferModal()" class="btn">Cancel</button>
      </div>
    </form>
  </div>
</div>

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

<!-- ARREARS HISTORY MODAL -->
<div id="arrearsHistoryModal" class="modal" style="display: none;">
  <div class="modal-content" style="max-width:600px;">
    <span onclick="closeArrearsHistoryModal()" style="float: right; font-size: 28px; font-weight: bold; cursor: pointer; color: #aaa;">&times;</span>
    <h3>Arrears History</h3>
    <div id="arrearsHistoryContent" style="margin-top:15px;"></div>
  </div>
</div>

<!-- ADD TENANT MODAL -->
<div id="addTenantModal" class="modal fade" tabindex="-1" aria-labelledby="addTenantModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addTenantModalLabel">Add New Tenant</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="addTenantForm">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="addName" class="form-label">Full Name</label>
              <input type="text" class="form-control" id="addName" name="name" placeholder="Enter full name" required>
            </div>
            <div class="col-md-6 mb-3">
              <label for="addBusinessName" class="form-label">Business Name</label>
              <input type="text" class="form-control" id="addBusinessName" name="business_name" placeholder="Enter business name" required>
            </div>
          </div>
          <div class="mb-3">
            <label for="addEmail" class="form-label">Email Address</label>
            <input type="email" class="form-control" id="addEmail" name="email" placeholder="Enter email address" required>
          </div>
          <div class="mb-3">
            <label for="addStallSelect" class="form-label">Select Stall</label>
            <select class="form-select" id="addStallSelect" name="stall_id" required>
              <option value="">-- Choose a stall --</option>
            </select>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="addLeaseStart" class="form-label">Lease Start Date</label>
              <input type="date" class="form-control" id="addLeaseStart" name="lease_start" required>
            </div>
            <div class="col-md-6 mb-3">
              <label for="addLeaseEnd" class="form-label">Lease End Date</label>
              <input type="date" class="form-control" id="addLeaseEnd" name="lease_end" required>
            </div>
          </div>
          <div class="mb-3">
            <label for="addMonthlyRent" class="form-label">Monthly Rent (₱)</label>
            <input type="number" class="form-control" id="addMonthlyRent" name="monthly_rent" step="0.01" placeholder="0.00" required>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" form="addTenantForm" class="btn btn-primary">Add Tenant</button>
      </div>
    </div>
  </div>
</div>

<!-- EDIT TENANT MODAL -->
<div id="editTenantModal" class="modal fade" tabindex="-1" aria-labelledby="editTenantModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editTenantModalLabel">Edit Tenant</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="editTenantForm">
          <div class="mb-3">
            <label for="editTenantSelect" class="form-label">Select Tenant</label>
            <select class="form-select" id="editTenantSelect" name="tenant_id" required onchange="loadTenantData()">
              <option value="">-- Choose a tenant --</option>
            </select>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="editName" class="form-label">Full Name</label>
              <input type="text" class="form-control" id="editName" name="name" placeholder="Enter full name" required>
            </div>
            <div class="col-md-6 mb-3">
              <label for="editBusinessName" class="form-label">Business Name</label>
              <input type="text" class="form-control" id="editBusinessName" name="business_name" placeholder="Enter business name" required>
            </div>
          </div>
          <div class="mb-3">
            <label for="editEmail" class="form-label">Email Address</label>
            <input type="email" class="form-control" id="editEmail" name="email" placeholder="Enter email address" required>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="editLeaseStart" class="form-label">Lease Start Date</label>
              <input type="date" class="form-control" id="editLeaseStart" name="lease_start" required>
            </div>
            <div class="col-md-6 mb-3">
              <label for="editLeaseEnd" class="form-label">Lease End Date</label>
              <input type="date" class="form-control" id="editLeaseEnd" name="lease_end" required>
            </div>
          </div>
          <div class="mb-3">
            <label for="editRent" class="form-label">Monthly Rent (₱)</label>
            <input type="number" class="form-control" id="editRent" name="monthly_rent" step="0.01" placeholder="0.00" required>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" form="editTenantForm" class="btn btn-primary">Save Changes</button>
      </div>
    </div>
  </div>
</div>

<script src="/rentflow/public/assets/js/table.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// make availableStalls array available to JS
const availableStalls = <?= json_encode($availableStalls) ?>;

function handleTenantAction(action, tenantId) {
  if (action === 'terminate') {
    openTerminateModal(tenantId);
  } else if (action === 'transfer') {
    openTransferModal(tenantId);
  } else if (action === 'send_message') {
    window.location.href = 'messages.php?tenant=' + tenantId;
  }
  // Reset select
  event.target.value = '';
}

function openTerminateModal(tenantId) {
  document.getElementById('terminateTenantId').value = tenantId;
  document.getElementById('terminateModal').style.display = 'block';
}

function closeTerminateModal() {
  document.getElementById('terminateModal').style.display = 'none';
}

function openTransferModal(tenantId) {
  document.getElementById('transferTenantId').value = tenantId;
  const select = document.getElementById('transferStallSelect');
  select.innerHTML = '<option value="">-- Choose a stall --</option>';
  availableStalls.forEach(s => {
    const opt = document.createElement('option');
    opt.value = s.id;
    opt.textContent = `${s.stall_no} - ${s.type.toUpperCase()} (${s.location})`;
    select.appendChild(opt);
  });
  document.getElementById('transferModal').style.display = 'block';
}

function closeTransferModal() {
  document.getElementById('transferModal').style.display = 'none';
}

function openUpdateDocsModal(tenantId) {
  document.getElementById('updateDocsTenantId').value = tenantId;
  // clear previews
  ['ValidId','BusinessLogo','BusinessPermit','DigitalSignature'].forEach(id => {
    const el = document.getElementById('current' + id);
    if (el) el.textContent = '';
  });
  // fetch existing application docs
  fetch('/rentflow/api/get_application_details.php?tenant_id=' + tenantId)
    .then(res => res.json())
    .then(data => {
      if (data.error) return;
      const showLink = (selector, path) => {
        const el = document.getElementById(selector);
        if (!el) return;
        if (path) {
          // show clickable link or text
          let txt = path;
          const isImage = /\.(png|jpg|jpeg|gif|webp)$/i.test(path);
          if (isImage) {
            txt = '[image] ' + path;
          }
          el.innerHTML = 'Current: <a href="' + path + '" target="_blank">' + txt + '</a>';
        } else {
          el.textContent = 'No file uploaded';
        }
      };
      showLink('currentValidId', data.valid_id_path);
      showLink('currentBusinessLogo', data.business_logo_path);
      showLink('currentBusinessPermit', data.business_permit_path);
      showLink('currentDigitalSignature', data.signature_path);
    })
    .catch(err => console.error('Unable to load current docs', err));

  document.getElementById('updateDocsModal').style.display = 'block';
}

// Close any modal when clicking outside
document.addEventListener('click', function(event) {
  ['messageModal','terminateModal','transferModal','paymentsHistoryModal','arrearsHistoryModal'].forEach(id => {
    const modal = document.getElementById(id);
    if (modal && event.target == modal) {
      modal.style.display = 'none';
    }
  });
});

// Handle terminate form via AJAX to use JSON response
const terminateForm = document.getElementById('terminateForm');
terminateForm.addEventListener('submit', function(e) {
  e.preventDefault();
  const formData = new FormData(this);
  fetch('/rentflow/api/delete_tenant.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert('Lease terminated successfully');
        window.location.reload();
      } else {
        alert('Error: ' + (data.error||'Unknown'));
      }
    })
    .catch(err => alert('Request failed: ' + err.message));
});

// Transfer form
const transferForm = document.getElementById('transferForm');
transferForm.addEventListener('submit', function(e) {
  e.preventDefault();
  const formData = new FormData(this);
  fetch('/rentflow/api/transfer_tenant.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert('Tenant transferred successfully');
        window.location.reload();
      } else {
        alert('Error: ' + (data.error||'Unknown'));
      }
    })
    .catch(err => alert('Request failed: ' + err.message));
});

// ============================================================
// PAYMENTS HISTORY MODAL FUNCTIONS
// ============================================================
function showPaymentsHistory(leaseId) {
    const modal = document.getElementById('paymentsHistoryModal');
    const content = document.getElementById('paymentsHistoryContent');
    content.innerHTML = '<p>Loading...</p>';
    modal.style.display = 'block';

    fetch('/rentflow/api/payments_history.php?lease_id=' + leaseId, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            content.innerHTML = '<p style="color: #d9534f;">Error: ' + htmlEscape(data.error) + '</p>';
            return;
        }
        if (data.history && data.history.length > 0) {
            let html = '<table class="table" style="margin-top:15px;"><thead><tr><th>Date</th><th>Amount</th><th>Method</th><th>Remarks</th></tr></thead><tbody>';
            data.history.forEach(p => {
                html += '<tr>' +
                        '<td>' + htmlEscape(p.date) + '</td>' +
                        '<td>₱' + parseFloat(p.amount).toFixed(2) + '</td>' +
                        '<td>' + htmlEscape(p.method) + '</td>' +
                        '<td>' + htmlEscape(p.remarks) + '</td>' +
                        '</tr>';
            });
            html += '</tbody></table>';
            content.innerHTML = html;
        } else {
            content.innerHTML = '<p>No payment history found.</p>';
        }
    })
    .catch(err => {
        content.innerHTML = '<p style="color: #d9534f;">Error loading history: ' + htmlEscape(err.message) + '</p>';
    });
}

function closePaymentsHistoryModal() {
    document.getElementById('paymentsHistoryModal').style.display = 'none';
}

// ============================================================
// ARREARS HISTORY MODAL FUNCTIONS
// ============================================================
function showArrearsHistory(leaseId) {
    const modal = document.getElementById('arrearsHistoryModal');
    const content = document.getElementById('arrearsHistoryContent');
    content.innerHTML = '<p>Loading...</p>';
    modal.style.display = 'block';

    fetch('/rentflow/api/arrears_history.php?lease_id=' + leaseId, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            content.innerHTML = '<p style="color: #d9534f;">Error: ' + htmlEscape(data.error) + '</p>';
            return;
        }
        if (data.history && data.history.length > 0) {
            let html = '<table class="table" style="margin-top:15px;"><thead><tr><th>Date</th><th>Amount</th><th>Type</th></tr></thead><tbody>';
            data.history.forEach(a => {
                html += '<tr>' +
                        '<td>' + htmlEscape(a.date) + '</td>' +
                        '<td>₱' + parseFloat(a.amount).toFixed(2) + '</td>' +
                        '<td>' + htmlEscape(a.type) + '</td>' +
                        '</tr>';
            });
            html += '</tbody></table>';
            content.innerHTML = html;
        } else {
            content.innerHTML = '<p>No arrears history found.</p>';
        }
    })
    .catch(err => {
        content.innerHTML = '<p style="color: #d9534f;">Error loading history: ' + htmlEscape(err.message) + '</p>';
    });
}

function closeArrearsHistoryModal() {
    document.getElementById('arrearsHistoryModal').style.display = 'none';
}

// ============================================================
// ADD TENANT MODAL FUNCTIONS
// ============================================================
function openAddTenantModal() {
    const select = document.getElementById('addStallSelect');
    select.innerHTML = '<option value="">-- Choose a stall --</option>';
    availableStalls.forEach(s => {
        const opt = document.createElement('option');
        opt.value = s.id;
        opt.textContent = `${s.stall_no} - ${s.type.toUpperCase()} (${s.location})`;
        select.appendChild(opt);
    });
    const modal = new bootstrap.Modal(document.getElementById('addTenantModal'));
    modal.show();
}

// ============================================================
// EDIT TENANT MODAL FUNCTIONS
// ============================================================
function openEditTenantModal() {
    // Load tenant list
    fetch('/rentflow/api/get_tenants.php')
    .then(res => res.json())
    .then(data => {
        const select = document.getElementById('editTenantSelect');
        select.innerHTML = '<option value="">-- Choose a tenant --</option>';
        data.tenants.forEach(t => {
            const opt = document.createElement('option');
            opt.value = t.id;
            opt.textContent = `${t.first_name} ${t.last_name} (${t.business_name})`;
            select.appendChild(opt);
        });
        const modal = new bootstrap.Modal(document.getElementById('editTenantModal'));
        modal.show();
    });
}

function loadTenantData() {
    const tenantId = document.getElementById('editTenantSelect').value;
    if (!tenantId) return;
    
    fetch('/rentflow/api/get_tenant_details.php?tenant_id=' + tenantId)
    .then(res => res.json())
    .then(data => {
        document.getElementById('editName').value = data.first_name + ' ' + data.last_name;
        document.getElementById('editBusinessName').value = data.business_name;
        document.getElementById('editEmail').value = data.email;
        document.getElementById('editRent').value = data.monthly_rent;
        document.getElementById('editLeaseStart').value = data.lease_start;
        document.getElementById('editLeaseEnd').value = data.lease_end;
    });
}

// Add tenant form
const addTenantForm = document.getElementById('addTenantForm');
addTenantForm.addEventListener('submit', function(e) {
  e.preventDefault();
  const formData = new FormData(this);
  fetch('/rentflow/api/add_tenant.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert('Tenant added successfully');
        window.location.reload();
      } else {
        alert('Error: ' + (data.error||'Unknown'));
      }
    })
    .catch(err => alert('Request failed: ' + err.message));
});

// Edit tenant form
const editTenantForm = document.getElementById('editTenantForm');
editTenantForm.addEventListener('submit', function(e) {
  e.preventDefault();
  const formData = new FormData(this);
  fetch('/rentflow/api/edit_tenant.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert('Tenant updated successfully');
        window.location.reload();
      } else {
        alert('Error: ' + (data.error||'Unknown'));
      }
    })
    .catch(err => alert('Request failed: ' + err.message));
});

function htmlEscape(text) {
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return text.replace(/[&<>\"']/g, function(m) { return map[m]; });
}

</script>
</body>
</html>
