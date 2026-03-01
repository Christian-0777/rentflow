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

<!-- Update Documents Modal -->
<div id="updateDocsModal" class="modal" style="display: none;">
  <div class="modal-content">
    <span onclick="closeUpdateDocsModal()" style="float: right; font-size: 28px; font-weight: bold; cursor: pointer; color: #aaa;">&times;</span>
    <h3>Update Tenant Documents</h3>
    <form id="updateDocsForm" method="post" action="/rentflow/api/update_tenant_docs.php" enctype="multipart/form-data">
      <input type="hidden" id="updateDocsTenantId" name="tenant_id" value="">
      <label for="validId">Valid ID:</label><br>
      <div id="currentValidId" style="margin-bottom:4px;color:#555;font-size:12px;"></div>
      <input type="file" id="validId" name="valid_id" accept=".pdf,.png,.jpg,.jpeg,.doc,.docx"><br>
      <label for="businessLogo">Business Logo:</label><br>
      <div id="currentBusinessLogo" style="margin-bottom:4px;color:#555;font-size:12px;"></div>
      <input type="file" id="businessLogo" name="business_logo" accept=".png,.jpg,.jpeg,.gif,.webp"><br>
      <label for="businessPermit">Business Permit:</label><br>
      <div id="currentBusinessPermit" style="margin-bottom:4px;color:#555;font-size:12px;"></div>
      <input type="file" id="businessPermit" name="business_permit" accept=".pdf,.png,.jpg,.jpeg,.doc,.docx"><br>
      <label for="digitalSignature">Digital Signature:</label><br>
      <div id="currentDigitalSignature" style="margin-bottom:4px;color:#555;font-size:12px;"></div>
      <input type="file" id="digitalSignature" name="digital_signature" accept=".pdf,.png,.jpg,.jpeg,.doc,.docx"><br>
      <div style="display: flex; gap: 10px; margin-top: 20px;">
        <button type="submit" class="btn">Submit</button>
        <button type="button" onclick="closeUpdateDocsModal()" class="btn">Cancel</button>
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

<script src="/rentflow/public/assets/js/table.js"></script>
<script>
// make availableStalls array available to JS
const availableStalls = <?= json_encode($availableStalls) ?>;

function handleTenantAction(action, tenantId) {
  if (action === 'terminate') {
    openTerminateModal(tenantId);
  } else if (action === 'transfer') {
    openTransferModal(tenantId);
  } else if (action === 'update_documents') {
    openUpdateDocsModal(tenantId);
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

function closeUpdateDocsModal() {
  document.getElementById('updateDocsModal').style.display = 'none';
}

// Close any modal when clicking outside
document.addEventListener('click', function(event) {
  ['messageModal','terminateModal','transferModal','updateDocsModal'].forEach(id => {
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

// Update documents form
const updateDocsForm = document.getElementById('updateDocsForm');
updateDocsForm.addEventListener('submit', function(e) {
  e.preventDefault();
  const formData = new FormData(this);
  fetch('/rentflow/api/update_tenant_docs.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert('Documents updated successfully');
        closeUpdateDocsModal();
      } else {
        alert('Error: ' + (data.error||'Unknown'));
      }
    })
    .catch(err => alert('Request failed: ' + err.message));
});
</script>
</body>
</html>
