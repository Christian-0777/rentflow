<?php
// admin/reports.php
// CSV export, pie chart of availability, monthly/yearly revenue charts

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/auth.php';

// ✅ Use plain string for role check
require_role('admin');

// Availability by type/status
$avail = $pdo->query("
  SELECT type,
    SUM(status='occupied') AS occupied,
    SUM(status='available') AS available,
    SUM(status='maintenance') AS maintenance
  FROM stalls GROUP BY type
")->fetchAll();

// Monthly revenue (last 12 months)
$monthly = $pdo->query("
  SELECT DATE_FORMAT(payment_date,'%Y-%m') AS ym, SUM(amount) AS total
  FROM payments
  WHERE payment_date>=DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
  GROUP BY ym ORDER BY ym
")->fetchAll();

// Yearly revenue (last 5 years)
$yearly = $pdo->query("
  SELECT YEAR(payment_date) AS y, SUM(amount) AS total
  FROM payments
  GROUP BY y ORDER BY y DESC LIMIT 5
")->fetchAll();

// Revenue summary for CSV
$summary = $pdo->query("
  SELECT p.payment_date AS date,
         SUM(p.amount) AS total_revenue,
         SUM(p.amount) AS total_collected,
         (SELECT SUM(total_arrears) FROM arrears) AS total_balances
  FROM payments p
  GROUP BY p.payment_date
  ORDER BY p.payment_date DESC
  LIMIT 30
")->fetchAll();

// Recently approved applications (last 30 days)
$approvedApps = $pdo->query("
  SELECT 
    sa.id,
    CONCAT(u.first_name, ' ', u.last_name) AS tenant_name,
    u.tenant_id,
    u.email,
    u.business_name,
    sa.type,
    sa.created_at AS application_date,
    sa.status
  FROM stall_applications sa
  JOIN users u ON sa.tenant_id = u.id
  WHERE sa.status = 'approved' 
  AND sa.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
  ORDER BY sa.created_at DESC
")->fetchAll();

// Prepare approved applications data for CSV export
$approvedExportData = [];
foreach ($approvedApps as $app) {
  $approvedExportData[] = [
    'Application ID' => $app['id'],
    'Tenant Name' => $app['tenant_name'],
    'Tenant ID' => $app['tenant_id'],
    'Email' => $app['email'],
    'Business Name' => $app['business_name'],
    'Stall Type' => $app['type'],
    'Application Date' => $app['application_date'],
    'Status' => $app['status']
  ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reports - RentFlow</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/rentflow/public/assets/css/layout.css">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="admin">

<!-- 🔹 Integrated Header -->
<header class="header">
  <h1 class="site-title">RentFlow</h1>

  <nav class="navigation">
    <ul>
      <li><a href="dashboard.php">Dashboard</a></li>
      <li><a href="tenants.php">Tenants</a></li>
      <li><a href="payments.php">Payments</a></li>
      <li><a href="reports.php" class="active">Reports</a></li>
      <li><a href="stalls.php">Stalls</a></li>
      <li><a href="notifications.php" title="Notifications"><i class="material-icons">notifications</i></a></li>
      <li><a href="account.php" class="nav-profile" title="Admin Account"><i class="material-icons">person</i></a></li>
      <li><a href="contact.php" title="Contact Service"><i class="material-icons">contact_support</i></a></li>
      <li><a href="login.php">Logout</a></li>
    </ul>
  </nav>
</header>

<main class="content">
  <h1>Reports</h1>

  <section class="actions">
    <button class="btn" onclick="showExportModal('revenue', 'Revenue Data')">Export Revenue Data</button>
  </section>

  <?php if (!empty($approvedApps)): ?>
  <section class="card">
    <h3>Recently Approved Applications (Last 30 Days)</h3>
    <table class="table">
      <thead>
        <tr>
          <th>Application ID</th>
          <th>Tenant Name</th>
          <th>Tenant ID</th>
          <th>Email</th>
          <th>Business Name</th>
          <th>Stall Type</th>
          <th>Application Date</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($approvedApps as $app): ?>
        <tr>
          <td><?= htmlspecialchars($app['id']) ?></td>
          <td><?= htmlspecialchars($app['tenant_name']) ?></td>
          <td><?= htmlspecialchars($app['tenant_id']) ?></td>
          <td><?= htmlspecialchars($app['email']) ?></td>
          <td><?= htmlspecialchars($app['business_name']) ?></td>
          <td><?= htmlspecialchars($app['type']) ?></td>
          <td><?= htmlspecialchars($app['application_date']) ?></td>
          <td><span class="badge success"><?= htmlspecialchars(strtoupper($app['status'])) ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <div class="export" style="margin-top: 15px; text-align: center;">
      <button class="btn" onclick="showExportModal('approved_apps', 'Approved Applications')">Export Approved Applications</button>
    </div>
  </section>
  <?php endif; ?>

  <section class="grid">
    <div class="card">
      <h3>Stall availability</h3>
      <div class="chart-controls" style="margin-bottom: 10px; text-align: center;">
        <label for="pieAvailType">Chart Type: </label>
        <select id="pieAvailType" onchange="changeChartType('pieAvail', this.value)">
          <option value="pie">Pie Chart</option>
          <option value="doughnut">Doughnut Chart</option>
          <option value="bar">Bar Chart</option>
        </select>
      </div>
      <canvas id="pieAvail"></canvas>
      <div class="export">
        <button class="btn small" onclick="showExportModal('chart', 'Stall Availability Chart', 'pieAvail')">Export Chart</button>
      </div>
    </div>

    <div class="card">
      <h3>Monthly revenue</h3>
      <div class="chart-controls" style="margin-bottom: 10px; text-align: center;">
        <label for="monthlyRevenueType">Chart Type: </label>
        <select id="monthlyRevenueType" onchange="changeChartType('monthlyRevenue', this.value)">
          <option value="bar">Bar Chart</option>
          <option value="line">Line Chart</option>
        </select>
      </div>
      <canvas id="monthlyRevenue"></canvas>
      <div class="export">
        <button class="btn small" onclick="showExportModal('chart', 'Monthly Revenue Chart', 'monthlyRevenue')">Export Chart</button>
      </div>
    </div>

    <div class="card">
      <h3>Yearly revenue</h3>
      <div class="chart-controls" style="margin-bottom: 10px; text-align: center;">
        <label for="yearlyRevenueType">Chart Type: </label>
        <select id="yearlyRevenueType" onchange="changeChartType('yearlyRevenue', this.value)">
          <option value="bar">Bar Chart</option>
          <option value="line">Line Chart</option>
        </select>
      </div>
      <canvas id="yearlyRevenue"></canvas>
      <div class="export">
        <button class="btn small" onclick="showExportModal('chart', 'Yearly Revenue Chart', 'yearlyRevenue')">Export Chart</button>
      </div>
    </div>
  </section>
</main>

<!-- 🔹 Integrated Footer -->
<footer class="footer">
  <p>&copy; <?= date('Y') ?> RentFlow. All rights reserved.</p>
</footer>

<!-- Export Modal -->
<div id="exportModal" class="modal" style="display: none;">
  <div class="modal-content" style="max-width: 400px;">
    <span onclick="closeExportModal()" style="float: right; font-size: 28px; font-weight: bold; cursor: pointer; color: #aaa;">&times;</span>
    <h3 id="exportModalTitle">Export Options</h3>
    <div id="exportOptions">
      <!-- Options will be populated by JavaScript -->
    </div>
  </div>
</div>

<!-- Hidden forms for export -->
<form id="csvExportForm" action="/rentflow/api/export_csv.php" method="post" style="display: none;">
  <input type="hidden" name="payload" id="csvPayload">
  <input type="hidden" name="headers" id="csvHeaders">
  <input type="hidden" name="filename" id="csvFilename">
</form>

<form id="excelExportForm" action="/rentflow/api/export_excel.php" method="post" style="display: none;">
  <input type="hidden" name="payload" id="excelPayload">
  <input type="hidden" name="headers" id="excelHeaders">
  <input type="hidden" name="filename" id="excelFilename">
</form>

<script>
// Export modal functionality
let currentExportData = null;

function showExportModal(type, title, chartId = null) {
  document.getElementById('exportModalTitle').textContent = `Export ${title}`;
  const optionsContainer = document.getElementById('exportOptions');
  
  // Set up export data based on type
  switch(type) {
    case 'revenue':
      currentExportData = {
        payload: <?= json_encode($summary) ?>,
        headers: ['Date','Total Revenue','Total Collected','Total Balances'],
        filename: 'rentflow_report'
      };
      break;
    case 'approved_apps':
      currentExportData = {
        payload: <?= json_encode($approvedExportData) ?>,
        headers: ['Application ID','Tenant Name','Tenant ID','Email','Business Name','Stall Type','Application Date','Status'],
        filename: 'approved_applications'
      };
      break;
    case 'chart':
      // For charts, we'll handle PNG/PDF export differently
      optionsContainer.innerHTML = `
        <button class="btn" onclick="exportPNG('${chartId}')">Export as PNG</button>
        <br><br>
        <button class="btn" onclick="exportPDF('${chartId}')">Export as PDF</button>
      `;
      document.getElementById('exportModal').style.display = 'block';
      return;
  }
  
  // For data exports (CSV/Excel)
  optionsContainer.innerHTML = `
    <button class="btn" onclick="exportAs('csv')">Export as CSV</button>
    <br><br>
    <button class="btn" onclick="exportAs('excel')">Export as Excel</button>
  `;
  
  document.getElementById('exportModal').style.display = 'block';
}

function closeExportModal() {
  document.getElementById('exportModal').style.display = 'none';
}

function exportAs(format) {
  if (!currentExportData) return;
  
  const form = document.getElementById(format === 'csv' ? 'csvExportForm' : 'excelExportForm');
  document.getElementById(format === 'csv' ? 'csvPayload' : 'excelPayload').value = JSON.stringify(currentExportData.payload);
  document.getElementById(format === 'csv' ? 'csvHeaders' : 'excelHeaders').value = JSON.stringify(currentExportData.headers);
  document.getElementById(format === 'csv' ? 'csvFilename' : 'excelFilename').value = `${currentExportData.filename}.${format === 'csv' ? 'csv' : 'xlsx'}`;
  
  form.submit();
  closeExportModal();
}

// Close modal when clicking outside
window.onclick = function(event) {
  const modal = document.getElementById('exportModal');
  if (event.target == modal) {
    closeExportModal();
  }
}

// Global chart data storage
let chartData = {};

// Build pie data from PHP
const availData = <?= json_encode($avail) ?>;
const pieLabels = availData.map(r => r.type);
const occupied = availData.map(r => Number(r.occupied));
const available = availData.map(r => Number(r.available));
const maintenance = availData.map(r => Number(r.maintenance));

// Store chart data
chartData.pieAvail = {
  type: 'pie',
  labels: pieLabels,
  series: [
    {label:'Occupied', data: occupied, color:'#8B1E1E'},
    {label:'Available', data: available, color:'#1F7A1F'},
    {label:'Maintenance', data: maintenance, color:'#F2B705'}
  ]
};

chartData.monthlyRevenue = {
  type: 'bar',
  labels: <?= json_encode(array_column($monthly,'ym')) ?>,
  data: <?= json_encode(array_map('floatval', array_column($monthly,'total'))) ?>,
  label: 'Monthly Revenue'
};

chartData.yearlyRevenue = {
  type: 'bar',
  labels: <?= json_encode(array_column($yearly,'y')) ?>,
  data: <?= json_encode(array_map('floatval', array_column($yearly,'total'))) ?>,
  label: 'Yearly Revenue'
};

// Function to change chart type
function changeChartType(canvasId, newType) {
  const data = chartData[canvasId];
  if (!data) return;
  
  // Update stored type
  data.type = newType;
  
  // Re-render chart
  if (canvasId === 'pieAvail') {
    renderChart(canvasId, newType, data.labels, null, null, data.series);
  } else {
    renderChart(canvasId, newType, data.labels, data.data, data.label);
  }
}

// Initial chart rendering
renderChart('pieAvail', 'pie', chartData.pieAvail.labels, null, null, chartData.pieAvail.series);
renderChart('monthlyRevenue', 'bar', chartData.monthlyRevenue.labels, chartData.monthlyRevenue.data, chartData.monthlyRevenue.label);
renderChart('yearlyRevenue', 'bar', chartData.yearlyRevenue.labels, chartData.yearlyRevenue.data, chartData.yearlyRevenue.label);
</script>
<script src="/rentflow/public/assets/js/table.js"></script>
<script src="/rentflow/public/assets/js/charts.js"></script>
</body>
</html>
