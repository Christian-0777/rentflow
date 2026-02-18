<?php
// admin/applications.php
// Displays stall applications for admin review and approval

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/auth.php';

// Require admin role
require_role('admin');

// Fetch all applications
$statusFilter = $_GET['status'] ?? '';
$sql = "
    SELECT sa.id, sa.tenant_id, sa.type, sa.business_name, sa.status, sa.created_at,
           CONCAT(u.first_name, ' ', u.last_name) AS tenant_name, u.email
    FROM stall_applications sa
    JOIN users u ON sa.tenant_id = u.id
    WHERE 1=1
";
$params = [];

if ($statusFilter && in_array($statusFilter, ['pending', 'approved', 'rejected'])) {
    $sql .= " AND sa.status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY sa.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch available stalls
$availableStalls = $pdo->query("SELECT id, stall_no, type, location FROM stalls WHERE status='available' ORDER BY stall_no")->fetchAll();

// Group stalls by type
$stallsByType = [];
foreach ($availableStalls as $stall) {
    $stallsByType[$stall['type']][] = $stall;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Stall Applications - RentFlow Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/rentflow/public/assets/css/layout.css">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <style>
    .application-card {
      padding: 16px;
      border: 1px solid #e0e0e0;
      border-radius: 8px;
      margin-bottom: 12px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: #f9f9f9;
    }

    .application-card:hover {
      background: #f0f0f0;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .application-info {
      flex: 1;
    }

    .application-info h4 {
      margin: 0 0 8px 0;
      color: #333;
    }

    .application-info p {
      margin: 4px 0;
      font-size: 12px;
      color: #666;
    }

    .status-badge {
      display: inline-block;
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
      margin-bottom: 8px;
    }

    .status-pending {
      background: #fff3cd;
      color: #856404;
    }

    .status-approved {
      background: #d4edda;
      color: #155724;
    }

    .status-rejected {
      background: #f8d7da;
      color: #721c24;
    }

    .modal {
      display: none;
      position: fixed;
      z-index: 1;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.4);
    }

    .modal.show {
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .modal-content {
      background-color: #fefefe;
      padding: 24px;
      border: 1px solid #888;
      border-radius: 8px;
      max-width: 700px;
      width: 90%;
      max-height: 90vh;
      overflow-y: auto;
    }

    .modal-content h2 {
      margin-top: 0;
      color: #333;
    }

    .detail-row {
      display: flex;
      gap: 24px;
      margin-bottom: 16px;
    }

    .detail-item {
      flex: 1;
    }

    .detail-label {
      font-weight: 600;
      color: #666;
      font-size: 12px;
      margin-bottom: 4px;
    }

    .detail-value {
      color: #333;
      font-size: 14px;
    }

    .document-preview {
      border: 1px solid #ddd;
      border-radius: 4px;
      padding: 12px;
      margin-bottom: 12px;
      text-align: center;
    }

    .document-preview img {
      max-width: 100%;
      max-height: 200px;
      object-fit: contain;
    }

    .document-link {
      color: #007bff;
      text-decoration: none;
      display: inline-block;
      margin-top: 8px;
    }

    .document-link:hover {
      text-decoration: underline;
    }

    .file-type-icon {
      font-size: 24px;
      margin-bottom: 8px;
    }

    .action-buttons {
      display: flex;
      gap: 12px;
      margin-top: 24px;
      border-top: 1px solid #e0e0e0;
      padding-top: 16px;
    }

    .modal-close {
      position: absolute;
      top: 12px;
      right: 12px;
      background: none;
      border: none;
      font-size: 28px;
      cursor: pointer;
      color: #999;
    }

    .modal-close:hover {
      color: #333;
    }

    .filter-buttons {
      display: flex;
      gap: 8px;
      margin-bottom: 20px;
    }

    .filter-buttons a {
      padding: 8px 16px;
      border: 1px solid #ddd;
      border-radius: 4px;
      text-decoration: none;
      color: #666;
      font-size: 13px;
    }

    .filter-buttons a.active {
      background: #007bff;
      color: white;
      border-color: #007bff;
    }

    .stall-selector {
      margin-top: 16px;
      padding: 16px;
      background: #f5f5f5;
      border-radius: 4px;
    }

    .stall-selector label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      color: #333;
    }

    .stall-selector select {
      width: 100%;
      padding: 8px;
      border: 1px solid #ddd;
      border-radius: 4px;
      margin-bottom: 12px;
    }

    .stall-selector input {
      width: 100%;
      padding: 8px;
      border: 1px solid #ddd;
      border-radius: 4px;
      margin-bottom: 12px;
      box-sizing: border-box;
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
      <li><a href="notifications.php" title="Notifications"><i class="material-icons">notifications</i>Notifications</a></li>
      <li><a href="account.php" class="nav-profile" title="Admin Account"><i class="material-icons">person</i>Account</a></li>
      <li><a href="contact.php" title="Contact Service"><i class="material-icons">contact_support</i>Contact</a></li>
    </ul>
  </nav>
</header>

<main class="content">
  <h1>Stall Applications</h1>

  <div class="filter-buttons">
    <a href="applications.php" class="<?= !$statusFilter ? 'active' : '' ?>">All</a>
    <a href="?status=pending" class="<?= $statusFilter === 'pending' ? 'active' : '' ?>">Pending</a>
    <a href="?status=approved" class="<?= $statusFilter === 'approved' ? 'active' : '' ?>">Approved</a>
    <a href="?status=rejected" class="<?= $statusFilter === 'rejected' ? 'active' : '' ?>">Rejected</a>
  </div>

  <?php if (empty($applications)): ?>
    <p style="color: #666; text-align: center; padding: 40px;">No applications found.</p>
  <?php else: ?>
    <div style="background: white; border-radius: 8px; padding: 16px;">
      <?php foreach ($applications as $app): ?>
        <div class="application-card">
          <div class="application-info">
            <div class="status-badge status-<?= htmlspecialchars($app['status']) ?>">
              <?= strtoupper(htmlspecialchars($app['status'])) ?>
            </div>
            <h4><?= htmlspecialchars($app['business_name']) ?></h4>
            <p><strong><?= htmlspecialchars($app['tenant_name']) ?></strong> (<?= htmlspecialchars($app['email']) ?>)</p>
            <p>Type: <strong><?= strtoupper(htmlspecialchars($app['type'])) ?></strong> | Submitted: <?= date('M d, Y H:i', strtotime($app['created_at'])) ?></p>
            <p style="color: #999;">Application ID: <?= htmlspecialchars($app['id']) ?></p>
          </div>
          <button class="btn" onclick="openApplicationModal('<?= htmlspecialchars($app['id']) ?>')">
            <i class="material-icons">visibility</i> View
          </button>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>

<!-- Application Details Modal -->
<div id="applicationModal" class="modal">
  <div class="modal-content" style="position: relative;">
    <button class="modal-close" onclick="closeApplicationModal()">&times;</button>
    
    <h2 id="modalTitle">Application Details</h2>

    <div class="detail-row">
      <div class="detail-item">
        <div class="detail-label">Tenant Name</div>
        <div class="detail-value" id="modalTenantName"></div>
      </div>
      <div class="detail-item">
        <div class="detail-label">Email</div>
        <div class="detail-value" id="modalEmail"></div>
      </div>
    </div>

    <div class="detail-row">
      <div class="detail-item">
        <div class="detail-label">Business Name</div>
        <div class="detail-value" id="modalBusinessName"></div>
      </div>
      <div class="detail-item">
        <div class="detail-label">Stall Type</div>
        <div class="detail-value" id="modalType"></div>
      </div>
    </div>

    <div class="detail-item">
      <div class="detail-label">Business Description</div>
      <div class="detail-value" id="modalDescription"></div>
    </div>

    <hr style="margin: 20px 0;">

    <h3 style="margin-top: 20px;">Documents</h3>

    <div id="logoContainer" style="display: none;">
      <label style="font-weight: 600; margin-bottom: 8px; display: block;">Business Logo</label>
      <div class="document-preview">
        <img id="logoImage" src="" alt="Business Logo">
      </div>
    </div>

    <div>
      <label style="font-weight: 600; margin-bottom: 8px; display: block;">Business Permit</label>
      <div class="document-preview">
        <div id="permitPreview" style="min-height: 60px; display: flex; align-items: center; justify-content: center;">
          <a href="#" class="document-link" target="_blank"><i class="material-icons" style="font-size: 36px; color: #666;">description</i><br>View Document</a>
        </div>
      </div>
    </div>

    <div>
      <label style="font-weight: 600; margin-bottom: 8px; display: block;">Valid ID</label>
      <div class="document-preview">
        <div id="idPreview" style="min-height: 60px; display: flex; align-items: center; justify-content: center;">
          <a href="#" class="document-link" target="_blank"><i class="material-icons" style="font-size: 36px; color: #666;">description</i><br>View Document</a>
        </div>
      </div>
    </div>

    <div>
      <label style="font-weight: 600; margin-bottom: 8px; display: block;">Digital Signature</label>
      <div class="document-preview">
        <div id="signaturePreview" style="min-height: 60px; display: flex; align-items: center; justify-content: center;">
          <a href="#" class="document-link" target="_blank"><i class="material-icons" style="font-size: 36px; color: #666;">description</i><br>View Document</a>
        </div>
      </div>
    </div>

    <div id="statusContainer" style="margin-top: 20px; padding: 16px; background: #f5f5f5; border-radius: 4px;">
      <label style="font-weight: 600; display: block; margin-bottom: 8px;">Status</label>
      <div id="modalStatus" style="padding: 8px; border-radius: 4px;"></div>
    </div>

    <div class="action-buttons" id="actionButtons">
      <button class="btn" onclick="showStallAssignmentForm()" style="flex: 1; background: #28a745;">
        <i class="material-icons" style="vertical-align: middle; margin-right: 5px; font-size: 18px;">check_circle</i> Assign Stall
      </button>
      <button class="btn" onclick="rejectApplication()" style="flex: 1; background: #dc3545;">
        <i class="material-icons" style="vertical-align: middle; margin-right: 5px; font-size: 18px;">cancel</i> Reject
      </button>
    </div>

    <!-- Stall Assignment Section -->
    <div id="stallAssignmentSection" style="display: none; margin-top: 20px; padding: 16px; background: #e8f5e9; border-radius: 4px;">
      <h3 style="margin-top: 0;">Assign Stall to Tenant</h3>
      <p style="margin-top: 0; color: #666; font-size: 14px;">Select an available stall and set the lease start date and monthly rent.</p>
      <div class="stall-selector">
        <label>Select Stall *</label>
        <select id="stallSelect" onchange="updateRentField()">
          <option value="">-- Choose a stall --</option>
          <?php foreach ($availableStalls as $s): ?>
            <option value="<?= htmlspecialchars($s['stall_no']) ?>" data-stall-id="<?= htmlspecialchars($s['id']) ?>">
              <?= htmlspecialchars($s['stall_no']) ?> - <?= strtoupper(htmlspecialchars($s['type'])) ?> (<?= htmlspecialchars($s['location']) ?>)
            </option>
          <?php endforeach; ?>
        </select>

        <label>Lease Start Date *</label>
        <input type="date" id="leaseStartDate" required>

        <label>Monthly Rent (₱) *</label>
        <input type="number" id="monthlyRent" placeholder="Enter monthly rent" step="0.01" min="0">

        <div style="display: flex; gap: 8px; margin-top: 12px;">
          <button class="btn" onclick="assignStall()" style="flex: 1; background: #007bff;">Assign Stall</button>
          <button class="btn" onclick="cancelAssignment()" style="flex: 1; background: #666;">Cancel</button>
        </div>
      </div>
    </div>
  </div>
</div>

<footer class="footer"><p>&copy; <?= date('Y') ?> RentFlow. All rights reserved.</p></footer>

<script>
let currentAppId = null;
let currentAppStatus = null;
let currentTenantId = null;

function openApplicationModal(appId) {
  currentAppId = appId;
  fetch('/rentflow/api/get_application_details.php?id=' + encodeURIComponent(appId), {
    method: 'GET',
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
      'Accept': 'application/json'
    },
    credentials: 'same-origin'
  })
    .then(res => {
      if (!res.ok) {
        return res.json().then(data => {
          throw new Error(data.error || 'HTTP ' + res.status + ': ' + res.statusText);
        }).catch(err => {
          throw new Error('HTTP ' + res.status + ': ' + res.statusText);
        });
      }
      return res.json();
    })
    .then(data => {
      if (data.error) {
        alert('Error: ' + data.error);
        return;
      }

      currentTenantId = data.tenant_id;
      currentAppStatus = data.status;

      document.getElementById('modalTitle').textContent = 'Application #' + data.id + ' - ' + data.business_name;
      document.getElementById('modalTenantName').textContent = data.tenant_name;
      document.getElementById('modalEmail').textContent = data.email;
      document.getElementById('modalBusinessName').textContent = data.business_name;
      document.getElementById('modalType').textContent = data.type.toUpperCase();
      document.getElementById('modalDescription').textContent = data.business_description;

      // Set status badge
      const statusEl = document.getElementById('modalStatus');
      const statusClass = 'status-' + data.status;
      statusEl.innerHTML = '<div style="padding: 8px; border-radius: 4px; display: inline-block;" class="status-badge ' + statusClass + '">' + data.status.toUpperCase() + '</div>';

      // Handle business logo
      if (data.business_logo_path) {
        const isImage = /\.(png|jpg|jpeg|gif|webp)$/i.test(data.business_logo_path);
        if (isImage) {
          document.getElementById('logoContainer').style.display = 'block';
          document.getElementById('logoImage').src = data.business_logo_path;
        } else {
          document.getElementById('logoContainer').style.display = 'none';
        }
      } else {
        document.getElementById('logoContainer').style.display = 'none';
      }

      // Handle documents
      const isImageFile = (path) => /\.(png|jpg|jpeg|gif)$/i.test(path);

      if (isImageFile(data.business_permit_path)) {
        document.getElementById('permitPreview').innerHTML = '<img src="' + data.business_permit_path + '" alt="Permit" style="max-width: 100%; max-height: 200px;">';
      } else {
        document.getElementById('permitPreview').innerHTML = '<a href="' + data.business_permit_path + '" class="document-link" target="_blank"><i class="material-icons" style="font-size: 36px; color: #666;">description</i><br>View Document</a>';
      }

      if (isImageFile(data.valid_id_path)) {
        document.getElementById('idPreview').innerHTML = '<img src="' + data.valid_id_path + '" alt="Valid ID" style="max-width: 100%; max-height: 200px;">';
      } else {
        document.getElementById('idPreview').innerHTML = '<a href="' + data.valid_id_path + '" class="document-link" target="_blank"><i class="material-icons" style="font-size: 36px; color: #666;">description</i><br>View Document</a>';
      }

      if (isImageFile(data.signature_path)) {
        document.getElementById('signaturePreview').innerHTML = '<img src="' + data.signature_path + '" alt="Signature" style="max-width: 100%; max-height: 200px;">';
      } else {
        document.getElementById('signaturePreview').innerHTML = '<a href="' + data.signature_path + '" class="document-link" target="_blank"><i class="material-icons" style="font-size: 36px; color: #666;">description</i><br>View Document</a>';
      }

      // Update action buttons based on status
      const actionButtons = document.getElementById('actionButtons');
      const stallAssignmentSection = document.getElementById('stallAssignmentSection');

      if (data.status === 'rejected') {
        actionButtons.style.display = 'none';
        stallAssignmentSection.style.display = 'none';
      } else {
        actionButtons.style.display = 'flex';
        stallAssignmentSection.style.display = 'none';
      }

      document.getElementById('applicationModal').classList.add('show');
    })
    .catch(err => {
      console.error('Error loading application details:', err);
      alert('Error loading application details: ' + (err.message || 'Unknown error. Check the browser console.'));
    });
}

function closeApplicationModal() {
  document.getElementById('applicationModal').classList.remove('show');
  currentAppId = null;
}

function showStallAssignmentForm() {
  if (!currentAppId || !currentTenantId) {
    alert('Error: Missing application data');
    return;
  }

  const actionButtons = document.getElementById('actionButtons');
  const stallAssignmentSection = document.getElementById('stallAssignmentSection');
  
  actionButtons.style.display = 'none';
  stallAssignmentSection.style.display = 'block';
}

function rejectApplication() {
  if (!currentAppId) return;

  if (!confirm('Are you sure you want to reject this application?')) {
    return;
  }

  const formData = new FormData();
  formData.append('application_id', currentAppId);
  formData.append('action', 'reject');

  fetch('/rentflow/api/approve_application.php', {
    method: 'POST',
    headers: {
      'X-Requested-With': 'XMLHttpRequest'
    },
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if (data.error) {
      alert('Error: ' + data.error);
      return;
    }

    alert('Application rejected successfully.');
    closeApplicationModal();
    location.reload();
  })
  .catch(err => {
    console.error('Error:', err);
    alert('Error rejecting application');
  });
}

function updateRentField() {
  // Rent field is manual entry, so no auto-update needed
}

function assignStall() {
  if (!currentAppId || !currentTenantId) return;

  const stallNo = document.getElementById('stallSelect').value;
  const leaseStartDate = document.getElementById('leaseStartDate').value;
  const monthlyRent = document.getElementById('monthlyRent').value;

  if (!stallNo || !leaseStartDate || !monthlyRent || monthlyRent <= 0) {
    alert('Please select a stall, choose a lease start date, and enter a valid monthly rent.');
    return;
  }

  const formData = new FormData();
  formData.append('application_id', currentAppId);
  formData.append('stall_no', stallNo);
  formData.append('lease_start_date', leaseStartDate);
  formData.append('monthly_rent', monthlyRent);

  fetch('/rentflow/api/assign_stall_to_application.php', {
    method: 'POST',
    headers: {
      'X-Requested-With': 'XMLHttpRequest'
    },
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if (data.error) {
      alert('Error: ' + data.error);
      return;
    }

    alert('Stall assigned successfully!');
    closeApplicationModal();
    location.reload();
  })
  .catch(err => {
    console.error('Error:', err);
    alert('Error assigning stall');
  });
}

function cancelAssignment() {
  closeApplicationModal();
}

// Close modal when clicking outside
window.onclick = function(event) {
  const modal = document.getElementById('applicationModal');
  if (event.target == modal) {
    closeApplicationModal();
  }
}
</script>

</body>
</html>
