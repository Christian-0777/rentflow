<?php
// admin/reports.php
// Admin reports with revenue analytics, stall availability, and new tenants

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/auth.php';

// ✅ Use plain string for role check
require_role('admin');

// Handle CSV/Excel export
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['export']) && $_GET['export'] === 'revenue_csv') {
    // Get revenue data
    $revenue_data = $pdo->query("
        SELECT 
            DATE(p.payment_date) as date,
            SUM(p.amount) as total_revenue,
            (SELECT SUM(amount) FROM payments WHERE DATE(payment_date) = DATE(p.payment_date)) as total_collected,
            (SELECT COALESCE(SUM(total_arrears), 0) FROM arrears WHERE DATE(last_updated) <= DATE(p.payment_date)) as total_balances
        FROM payments p
        GROUP BY DATE(p.payment_date)
        ORDER BY DATE(p.payment_date) DESC
    ")->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="revenue_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Date', 'Total Revenue', 'Total Collected', 'Total Balances']);
    
    foreach ($revenue_data as $row) {
        fputcsv($output, [
            $row['date'],
            number_format($row['total_revenue'], 2),
            number_format($row['total_collected'], 2),
            number_format($row['total_balances'], 2)
        ]);
    }
    
    fclose($output);
    exit;
}

// Handle XLSX export using simple method (tab-separated for Excel compatibility)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['export']) && $_GET['export'] === 'revenue_xlsx') {
    $revenue_data = $pdo->query("
        SELECT 
            DATE(p.payment_date) as date,
            SUM(p.amount) as total_revenue,
            (SELECT SUM(amount) FROM payments WHERE DATE(payment_date) = DATE(p.payment_date)) as total_collected,
            (SELECT COALESCE(SUM(total_arrears), 0) FROM arrears WHERE DATE(last_updated) <= DATE(p.payment_date)) as total_balances
        FROM payments p
        GROUP BY DATE(p.payment_date)
        ORDER BY DATE(p.payment_date) DESC
    ")->fetchAll();

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="revenue_report_' . date('Y-m-d') . '.xlsx"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Date', 'Total Revenue', 'Total Collected', 'Total Balances'], "\t");
    
    foreach ($revenue_data as $row) {
        fputcsv($output, [
            $row['date'],
            number_format($row['total_revenue'], 2),
            number_format($row['total_collected'], 2),
            number_format($row['total_balances'], 2)
        ], "\t");
    }
    
    fclose($output);
    exit;
}

// Get overall revenue statistics
$stats = $pdo->query("
    SELECT 
        SUM(p.amount) as total_revenue,
        SUM(p.amount) as total_collected,
        COALESCE(SUM(a.total_arrears), 0) as total_balances
    FROM payments p
    LEFT JOIN arrears a ON 1=1
")->fetch();

// Get stall availability breakdown
$stall_availability = $pdo->query("
    SELECT 
        type,
        status,
        COUNT(*) as count
    FROM stalls
    GROUP BY type, status
")->fetchAll();

// Process stall data for pie chart
$stall_breakdown = [];
foreach ($stall_availability as $row) {
    $key = ucfirst($row['type']);
    if (!isset($stall_breakdown[$key])) {
        $stall_breakdown[$key] = ['occupied' => 0, 'available' => 0, 'maintenance' => 0, 'total' => 0];
    }
    $stall_breakdown[$key][$row['status']] = $row['count'];
    $stall_breakdown[$key]['total'] += $row['count'];
}

// Get new tenants (last 30 days)
$new_tenants = $pdo->query("
    SELECT 
        u.id,
        CONCAT(u.first_name, ' ', u.last_name) as tenant_name,
        u.business_name,
        l.lease_start,
        s.stall_no,
        s.type,
        s.location
    FROM users u
    JOIN leases l ON u.id = l.tenant_id
    JOIN stalls s ON l.stall_id = s.id
    WHERE u.role = 'tenant' 
    AND l.lease_start >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ORDER BY l.lease_start DESC
")->fetchAll();

// Get monthly revenue data for chart
$monthly_revenue = $pdo->query("
    SELECT 
        DATE_FORMAT(p.payment_date, '%Y-%m') as month,
        DATE_FORMAT(p.payment_date, '%M %Y') as month_label,
        SUM(p.amount) as total
    FROM payments p
    WHERE p.payment_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(p.payment_date, '%Y-%m')
    ORDER BY DATE_FORMAT(p.payment_date, '%Y-%m') ASC
")->fetchAll();

// Get yearly revenue data for chart
$yearly_revenue = $pdo->query("
    SELECT 
        YEAR(p.payment_date) as year,
        SUM(p.amount) as total
    FROM payments p
    GROUP BY YEAR(p.payment_date)
    ORDER BY YEAR(p.payment_date) ASC
")->fetchAll();

// Prepare chart data as JSON
$monthly_labels = array_map(fn($r) => $r['month_label'], $monthly_revenue);
$monthly_values = array_map(fn($r) => (float)$r['total'], $monthly_revenue);
$yearly_labels = array_map(fn($r) => $r['year'], $yearly_revenue);
$yearly_values = array_map(fn($r) => (float)$r['total'], $yearly_revenue);

// Prepare stall chart labels and values
$stall_labels = [];
$stall_values = [];
foreach ($stall_breakdown as $type => $data) {
  $stall_labels[] = $type . ' - Occupied';
  $stall_values[] = $data['occupied'];
  $stall_labels[] = $type . ' - Available';
  $stall_values[] = $data['available'];
  $stall_labels[] = $type . ' - Maintenance';
  $stall_values[] = $data['maintenance'];
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
  <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/html2pdf@0.10.1/dist/html2pdf.bundle.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/docx/8.5.0/docx.min.js"></script>
</head>
<body class="admin">

<!-- 🔹 Integrated Header -->
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
  <h1>Reports & Analytics</h1>

  <!-- � Full Page Export Buttons -->
  <section class="export-full-page">
    <h3>Export Full Report</h3>
    <button class="btn" onclick="exportPageAsWord()">📄 Export as Word</button>
    <button class="btn" onclick="exportPageAsPDF()">📑 Export as PDF</button>
    <button class="btn" onclick="exportPageAsGoogleDocs()">📗 Open in Google Docs</button>
  </section>

  <!-- 👥 New Tenants Section -->
  <section class="report-section">
    <h2>New Tenants (Last 30 Days)</h2>
    <table class="table">
      <thead>
        <tr>
          <th>Lease Start Date</th>
          <th>Tenant Name</th>
          <th>Business Name</th>
          <th>Stall No</th>
          <th>Stall Type</th>
          <th>Location</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($new_tenants) > 0): ?>
          <?php foreach ($new_tenants as $tenant): ?>
            <tr>
              <td><?= htmlspecialchars($tenant['lease_start']) ?></td>
              <td><?= htmlspecialchars($tenant['tenant_name']) ?></td>
              <td><?= htmlspecialchars($tenant['business_name'] ?? '—') ?></td>
              <td><?= htmlspecialchars($tenant['stall_no']) ?></td>
              <td><span class="badge"><?= ucfirst(htmlspecialchars($tenant['type'])) ?></span></td>
              <td><?= htmlspecialchars($tenant['location']) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="6" style="text-align: center; color: #666;">No new tenants in the last 30 days</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </section>

  <!-- 🥧 Stall Availability Chart -->
  <section class="report-section">
    <div class="chart-header">
      <div>
        <h2>Stall Availability Breakdown</h2>
        <div class="chart-type-toggle">
          <button class="chart-type-btn active" data-type="doughnut" onclick="switchChartType('stallAvailabilityChart', 'doughnut', this)">Pie Chart</button>
          <button class="chart-type-btn" data-type="bar" onclick="switchChartType('stallAvailabilityChart', 'bar', this)">Bar Chart</button>
          <button class="chart-type-btn" data-type="line" onclick="switchChartType('stallAvailabilityChart', 'line', this)">Line Chart</button>
        </div>
      </div>
      <div>
        <button class="btn small" onclick="exportChartAsPNG('stallAvailabilityChart', 'stall_availability')">📊 Export PNG</button>
        <button class="btn small" onclick="exportChartAsPDF('stallAvailabilityChart', 'stall_availability')">📄 Export PDF</button>
      </div>
    </div>
    
    <div class="stall-chart-container">
      <canvas id="stallAvailabilityChart" width="100" height="80"></canvas>
    </div>

    <div class="stall-breakdown-table">
      <h3>Stall Availability Details</h3>
      <table class="table">
        <thead>
          <tr>
            <th>Type</th>
            <th>Occupied</th>
            <th>Available</th>
            <th>Maintenance</th>
            <th>Total</th>
            <th>Occupied %</th>
            <th>Available %</th>
            <th>Maintenance %</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($stall_breakdown as $type => $data): 
            $total = $data['total'];
            $occupied_pct = $total > 0 ? round(($data['occupied'] / $total) * 100, 1) : 0;
            $available_pct = $total > 0 ? round(($data['available'] / $total) * 100, 1) : 0;
            $maintenance_pct = $total > 0 ? round(($data['maintenance'] / $total) * 100, 1) : 0;
          ?>
            <tr>
              <td><strong><?= htmlspecialchars($type) ?></strong></td>
              <td><?= $data['occupied'] ?></td>
              <td><?= $data['available'] ?></td>
              <td><?= $data['maintenance'] ?></td>
              <td><?= $total ?></td>
              <td><?= $occupied_pct ?>%</td>
              <td><?= $available_pct ?>%</td>
              <td><?= $maintenance_pct ?>%</td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <!-- 📈 Monthly Revenue Chart -->
  <section class="report-section">
    <div class="chart-header">
      <h2>Monthly Revenue</h2>
      <button class="btn small" onclick="exportChartAsPNG('monthlyRevenueChart', 'monthly_revenue')">📊 Export PNG</button>
      <button class="btn small" onclick="exportChartAsPDF('monthlyRevenueChart', 'monthly_revenue')">📄 Export PDF</button>
    </div>
    <canvas id="monthlyRevenueChart" width="100" height="40"></canvas>
  </section>

  <!-- 📈 Yearly Revenue Chart -->
  <section class="report-section">
    <div class="chart-header">
      <h2>Yearly Revenue</h2>
      <button class="btn small" onclick="exportChartAsPNG('yearlyRevenueChart', 'yearly_revenue')">📊 Export PNG</button>
      <button class="btn small" onclick="exportChartAsPDF('yearlyRevenueChart', 'yearly_revenue')">📄 Export PDF</button>
    </div>
    <canvas id="yearlyRevenueChart" width="100" height="40"></canvas>
  </section>

  <!-- �📊 Revenue Summary Section -->
  <section class="report-section">
    <h2>Revenue Summary</h2>
    <div class="revenue-stats">
      <div class="stat-card">
        <h3>Total Revenue</h3>
        <p class="stat-value">₱<?= number_format($stats['total_revenue'] ?? 0, 2) ?></p>
      </div>
      <div class="stat-card">
        <h3>Total Collected</h3>
        <p class="stat-value">₱<?= number_format($stats['total_collected'] ?? 0, 2) ?></p>
      </div>
      <div class="stat-card">
        <h3>Total Balances</h3>
        <p class="stat-value">₱<?= number_format($stats['total_balances'] ?? 0, 2) ?></p>
      </div>
    </div>

    <div class="export-buttons">
      <a href="?export=revenue_csv" class="btn">📥 CSV Export</a>
      <a href="?export=revenue_xlsx" class="btn">📥 Excel Export</a>
    </div>
  </section>

</main>

<!-- 🔹 Integrated Footer -->
<footer class="footer">
  <p>&copy; <?= date('Y') ?> RentFlow. All rights reserved.</p>
</footer>

<style>
  .export-full-page {
    margin: 20px 0;
    padding: 20px;
    background: linear-gradient(135deg, #0B3C5D 0%, #083051 100%);
    color: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  }

  .export-full-page h3 {
    margin-top: 0;
    margin-bottom: 15px;
  }

  .export-full-page .btn {
    margin-right: 10px;
    margin-bottom: 10px;
    background-color: rgba(255,255,255,0.2);
    border: 1px solid rgba(255,255,255,0.3);
  }

  .export-full-page .btn:hover {
    background-color: rgba(255,255,255,0.3);
  }

  .report-section {
    margin: 30px 0;
    padding: 20px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  }

  .revenue-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
  }

  .stat-card {
    padding: 20px;
    background: linear-gradient(135deg, #0B3C5D 0%, #083051 100%);
    color: white;
    border-radius: 8px;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s ease;
  }

  .stat-card:hover {
    transform: translateY(-5px);
  }

  .stat-card h3 {
    margin: 0 0 10px 0;
    font-size: 14px;
    font-weight: 500;
    opacity: 0.9;
  }

  .stat-value {
    margin: 0;
    font-size: 28px;
    font-weight: bold;
  }

  .export-buttons {
    display: flex;
    gap: 10px;
    margin: 20px 0;
    flex-wrap: wrap;
  }

  .export-buttons .btn {
    margin: 0;
  }

  .chart-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 10px;
  }

  .chart-header h2 {
    margin: 0 0 10px 0;
  }

  .chart-header > div {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
  }

  .chart-header .btn {
    margin: 0 5px 0 0;
  }

  .chart-type-toggle {
    display: flex;
    gap: 5px;
    margin-top: 10px;
  }

  .chart-type-btn {
    padding: 6px 12px;
    border: 1px solid #0B3C5D;
    background: white;
    color: #0B3C5D;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    font-weight: 500;
    transition: all 0.2s ease;
  }

  .chart-type-btn:hover {
    background: #f0f0f0;
  }

  .chart-type-btn.active {
    background: #0B3C5D;
    color: white;
  }

  .stall-chart-container {
    max-width: 600px;
    margin: 20px auto;
    padding: 20px;
    background: #f9f9f9;
    border-radius: 8px;
  }

  .stall-breakdown-table {
    margin-top: 30px;
  }

  .stall-breakdown-table h3 {
    margin-bottom: 15px;
    color: #0B3C5D;
  }

  .report-section canvas {
    max-height: 400px;
  }

  .footer {
    text-align: center;
    padding: 20px;
    background: #f5f5f5;
    margin-top: 40px;
    border-top: 1px solid #ddd;
    font-size: 14px;
    color: #666;
  }

  /* Responsive adjustments for reports */
  @media (max-width: 768px) {
    .revenue-stats {
      grid-template-columns: 1fr;
    }

    .chart-header {
      flex-direction: column;
      align-items: flex-start;
    }

    .chart-header .btn {
      width: 100%;
    }

    .stall-chart-container {
      max-width: 100%;
    }

    .table {
      font-size: 12px;
    }

    .table th,
    .table td {
      padding: 8px 10px;
    }

    .chart-type-toggle {
      width: 100%;
    }

    .chart-type-btn {
      flex: 1;
    }
  }
</style>

<script>
  // Store chart instances
  let charts = {
    stallAvailabilityChart: null,
    monthlyRevenueChart: null,
    yearlyRevenueChart: null
  };

  // Chart data
  const stallChartData = {
    labels: <?= json_encode($stall_labels) ?>,
    values: <?= json_encode($stall_values) ?>,
    colors: [
      'rgba(31, 122, 31, 0.8)',
      'rgba(242, 183, 5, 0.8)',
      'rgba(139, 30, 30, 0.8)',
      'rgba(11, 60, 93, 0.8)',
      'rgba(31, 122, 31, 0.6)',
      'rgba(242, 183, 5, 0.6)',
      'rgba(255, 107, 107, 0.8)',
      'rgba(78, 205, 196, 0.8)',
      'rgba(69, 183, 209, 0.8)'
    ],
    borderColors: [
      'rgb(31, 122, 31)',
      'rgb(242, 183, 5)',
      'rgb(139, 30, 30)',
      'rgb(11, 60, 93)',
      'rgb(31, 122, 31)',
      'rgb(242, 183, 5)',
      'rgb(255, 107, 107)',
      'rgb(78, 205, 196)',
      'rgb(69, 183, 209)'
    ]
  };

  // Initialize Stall Chart
  function initStallChart(type = 'doughnut') {
    const ctx = document.getElementById('stallAvailabilityChart').getContext('2d');
    
    if (charts.stallAvailabilityChart) {
      charts.stallAvailabilityChart.destroy();
    }

    charts.stallAvailabilityChart = new Chart(ctx, {
      type: type,
      data: {
        labels: stallChartData.labels,
        datasets: [{
          data: stallChartData.values,
          backgroundColor: stallChartData.colors,
          borderColor: stallChartData.borderColors,
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
          legend: {
            position: type === 'doughnut' ? 'right' : 'top',
            labels: {
              padding: 15,
              font: { size: 12 }
            }
          }
        },
        scales: type === 'doughnut' ? {} : {
          y: {
            beginAtZero: true
          }
        }
      }
    });
  }

  // Switch chart type
  function switchChartType(chartId, newType, buttonElement) {
    // Update active button
    document.querySelectorAll('.chart-type-btn').forEach(btn => {
      btn.classList.remove('active');
    });
    buttonElement.classList.add('active');

    // Reinitialize chart
    if (chartId === 'stallAvailabilityChart') {
      initStallChart(newType);
    }
  }

  // Monthly Revenue Chart
  const monthlyCtx = document.getElementById('monthlyRevenueChart').getContext('2d');
  charts.monthlyRevenueChart = new Chart(monthlyCtx, {
    type: 'bar',
    data: {
      labels: <?= json_encode($monthly_labels) ?>,
      datasets: [{
        label: 'Monthly Revenue (₱)',
        data: <?= json_encode($monthly_values) ?>,
        backgroundColor: 'rgba(11, 60, 93, 0.7)',
        borderColor: 'rgba(11, 60, 93, 1)',
        borderWidth: 1,
        borderRadius: 4
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        legend: {
          display: true,
          position: 'top'
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: function(value) {
              return '₱' + value.toLocaleString();
            }
          }
        }
      }
    }
  });

  // Yearly Revenue Chart
  const yearlyCtx = document.getElementById('yearlyRevenueChart').getContext('2d');
  charts.yearlyRevenueChart = new Chart(yearlyCtx, {
    type: 'bar',
    data: {
      labels: <?= json_encode($yearly_labels) ?>,
      datasets: [{
        label: 'Yearly Revenue (₱)',
        data: <?= json_encode($yearly_values) ?>,
        backgroundColor: 'rgba(31, 122, 31, 0.7)',
        borderColor: 'rgba(31, 122, 31, 1)',
        borderWidth: 1,
        borderRadius: 4
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        legend: {
          display: true,
          position: 'top'
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: function(value) {
              return '₱' + value.toLocaleString();
            }
          }
        }
      }
    }
  });

  // Initialize stall chart
  initStallChart('doughnut');

  // Export chart as PNG
  function exportChartAsPNG(canvasId, filename) {
    const canvas = document.getElementById(canvasId).parentElement;
    html2canvas(canvas, { scale: 2 }).then(newCanvas => {
      const link = document.createElement('a');
      link.href = newCanvas.toDataURL();
      link.download = filename + '_' + new Date().toISOString().split('T')[0] + '.png';
      link.click();
    });
  }

  // Export chart as PDF
  function exportChartAsPDF(canvasId, filename) {
    const canvas = document.getElementById(canvasId).parentElement;
    html2canvas(canvas, { scale: 2 }).then(newCanvas => {
      const { jsPDF } = window.jspdf;
      const pdf = new jsPDF({
        orientation: 'landscape',
        unit: 'mm',
        format: 'a4'
      });
      
      const imgData = newCanvas.toDataURL('image/png');
      const imgWidth = 280;
      const imgHeight = (newCanvas.height * imgWidth) / newCanvas.width;
      
      pdf.addImage(imgData, 'PNG', 10, 10, imgWidth, imgHeight);
      pdf.save(filename + '_' + new Date().toISOString().split('T')[0] + '.pdf');
    });
  }

  // Export full page as PDF
  function exportPageAsPDF() {
    const element = document.querySelector('main.content');
    if (!element) {
      alert('Error: Could not find report content to export.');
      return;
    }
    
    const opt = {
      margin: 10,
      filename: 'rentflow_report_' + new Date().toISOString().split('T')[0] + '.pdf',
      image: { type: 'jpeg', quality: 0.98 },
      html2canvas: { scale: 2 },
      jsPDF: { orientation: 'portrait', unit: 'mm', format: 'a4' }
    };
    
    html2pdf()
      .set(opt)
      .from(element)
      .save()
      .catch(error => {
        console.error('PDF export error:', error);
        alert('Error exporting PDF. Please try again.');
      });
  }

  // Export full page as Word (DOCX)
  function exportPageAsWord() {
    const element = document.querySelector('main.content');
    const html = element.innerHTML;
    
    const htmlString = `
      <!DOCTYPE html>
      <html>
      <head>
        <meta charset="UTF-8">
        <title>RentFlow Report</title>
        <style>
          body { font-family: Arial, sans-serif; margin: 20px; }
          h1 { color: #0B3C5D; }
          h2 { color: #0B3C5D; margin-top: 20px; }
          table { border-collapse: collapse; width: 100%; margin: 10px 0; }
          th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
          th { background-color: #E6F2F8; color: #0B3C5D; }
          .stat-card { background-color: #E6F2F8; padding: 10px; margin: 5px 0; border-radius: 4px; }
          .badge { padding: 4px 8px; border-radius: 4px; }
        </style>
      </head>
      <body>
        <h1>RentFlow Reports & Analytics</h1>
        <p>Generated on: ${new Date().toLocaleString()}</p>
        ${html}
      </body>
      </html>
    `;

    const blob = new Blob([htmlString], { type: 'application/msword' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'rentflow_report_' + new Date().toISOString().split('T')[0] + '.doc';
    link.click();
    window.URL.revokeObjectURL(url);
  }

  // Export to Google Docs
  function exportPageAsGoogleDocs() {
    const title = 'RentFlow Report - ' + new Date().toLocaleDateString();
    const content = document.querySelector('main.content').innerText;
    
    // Create a simple HTML table from content
    const htmlContent = document.querySelector('main.content').innerHTML;
    const htmlData = `
      <html><head><title>${title}</title></head><body>
      <h1>${title}</h1>
      ${htmlContent}
      </body></html>
    `;

    // Encode for Google Docs
    const encodedHtml = encodeURIComponent(htmlData);
    const googleDocsUrl = `https://docs.google.com/document/create?title=${encodeURIComponent(title)}&body=${encodedHtml}`;
    
    // Alternative: Use a simple approach with data URI
    const dataUri = 'data:text/html;charset=utf-8,' + encodedHtml;
    const blob = new Blob([htmlData], { type: 'text/html' });
    const url = window.URL.createObjectURL(blob);
    
    // Open in new tab for user to save
    const win = window.open(url, '_blank');
    
    // Try to trigger Google Docs import (requires Google account)
    setTimeout(() => {
      alert('To import this into Google Docs:\n1. Copy the content from the new window\n2. Go to Google Docs\n3. Create a new document and paste');
    }, 500);
  }
</script>

</body>
</html>
