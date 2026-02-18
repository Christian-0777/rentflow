<?php
/**
 * admin/payments.php
 * Reworked Payments Management System
 * Shows payment records and arrears with comprehensive management options
 */

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/auth.php';

// ✅ Allow admin and treasury
require_role(['admin', 'treasury']);

// ============================================================
// PROCESS PAYMENT ACTIONS
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_payment_action'])) {
    $lease_id = (int)$_POST['lease_id'];
    $action = $_POST['action'];
    $next_due_date = $_POST['next_due_date'] ?? null;
    $next_amount = (float)($_POST['next_amount'] ?? 0);
    $partial_amount = (float)($_POST['partial_amount'] ?? 0);
    
    try {
        // Get current unpaid due
        $current_due = $pdo->prepare("
            SELECT id, due_date, amount_due FROM dues 
            WHERE lease_id = ? AND paid = 0 
            ORDER BY due_date ASC LIMIT 1
        ");
        $current_due->execute([$lease_id]);
        $due = $current_due->fetch(PDO::FETCH_ASSOC);
        
        if (!$due) {
            throw new Exception("No unpaid due found for this lease");
        }
        
        if ($action === 'paid') {
            // Mark current due as paid
            $pdo->prepare("UPDATE dues SET paid = 1 WHERE id = ?")->execute([$due['id']]);
            
            // Record the payment
            $pdo->prepare("
                INSERT INTO payments (lease_id, amount, payment_date, method, remarks) 
                VALUES (?, ?, CURDATE(), 'cash', 'Full Payment - Marked Paid')
            ")->execute([$lease_id, $due['amount_due']]);
            
        } elseif ($action === 'partial') {
            // Record partial payment
            $pdo->prepare("
                INSERT INTO payments (lease_id, amount, payment_date, method, remarks) 
                VALUES (?, ?, CURDATE(), 'partial', 'Partial Payment')
            ")->execute([$lease_id, $partial_amount]);
            
            // Calculate remaining amount as arrears
            $remaining = $due['amount_due'] - $partial_amount;
            
            if ($remaining > 0) {
                // Add remaining as arrears
                $pdo->prepare("
                    INSERT INTO arrears (lease_id, total_arrears, previous_arrears, triggered_date, trigger_reason) 
                    VALUES (?, ?, 0, NOW(), 'marked_not_paid')
                    ON DUPLICATE KEY UPDATE 
                        total_arrears = total_arrears + VALUES(total_arrears),
                        last_updated = NOW()
                ")->execute([$lease_id, $remaining]);
            }
            
            // If partial covers full amount, mark as paid
            if ($partial_amount >= $due['amount_due']) {
                $pdo->prepare("UPDATE dues SET paid = 1 WHERE id = ?")->execute([$due['id']]);
            }
            
        } elseif ($action === 'notpaid') {
            // Mark as not paid - add full due amount to arrears
            $pdo->prepare("
                INSERT INTO arrears (lease_id, total_arrears, previous_arrears, triggered_date, trigger_reason) 
                VALUES (?, ?, 0, NOW(), 'marked_not_paid')
                ON DUPLICATE KEY UPDATE 
                    total_arrears = total_arrears + VALUES(total_arrears),
                    last_updated = NOW()
            ")->execute([$lease_id, $due['amount_due']]);
            
            // Record payment with 0 amount
            $pdo->prepare("
                INSERT INTO payments (lease_id, amount, payment_date, method, remarks) 
                VALUES (?, 0, CURDATE(), 'manual', 'Marked as Not Paid')
            ")->execute([$lease_id, 0]);
        }
        
        // Add next due if provided
        if ($next_due_date && $next_amount > 0) {
            $pdo->prepare("
                INSERT INTO dues (lease_id, due_date, amount_due, paid) 
                VALUES (?, ?, ?, 0)
            ")->execute([$lease_id, $next_due_date, $next_amount]);
        }
        
        header("Location: payments.php?success=1");
        exit;
        
    } catch (Exception $e) {
        $error = "Error processing payment: " . htmlspecialchars($e->getMessage());
    }
}

// ============================================================
// FETCH ACTIVE PAYMENTS DATA
// ============================================================

$activePayments = $pdo->query("
    SELECT 
        l.id as lease_id, 
        u.id as tenant_id, 
        u.tenant_id as tenant_code, 
        s.stall_no, 
        CONCAT(u.first_name, ' ', u.last_name) AS full_name, 
        u.business_name,
        a.total_arrears,
        (SELECT due_date FROM dues WHERE lease_id=l.id AND paid=0 ORDER BY due_date ASC LIMIT 1) AS next_due,
        (SELECT amount_due FROM dues WHERE lease_id=l.id AND paid=0 ORDER BY due_date ASC LIMIT 1) AS next_amount,
        (SELECT payment_date FROM payments WHERE lease_id=l.id ORDER BY payment_date DESC LIMIT 1) AS last_payment_date,
        (SELECT amount FROM payments WHERE lease_id=l.id ORDER BY payment_date DESC LIMIT 1) AS last_payment_amount,
        (SELECT remarks FROM payments WHERE lease_id=l.id ORDER BY payment_date DESC LIMIT 1) AS last_payment_remarks,
        CASE
            WHEN EXISTS(SELECT 1 FROM dues WHERE lease_id=l.id AND paid=0 AND due_date<CURDATE())
            THEN 'Overdue'
            WHEN EXISTS(SELECT 1 FROM dues WHERE lease_id=l.id AND paid=0)
            THEN 'Pending'
            ELSE 'Paid'
        END AS payment_status
    FROM leases l
    JOIN users u ON l.tenant_id=u.id
    JOIN stalls s ON l.stall_id=s.id
    LEFT JOIN arrears a ON a.lease_id=l.id
    WHERE u.status = 'active'
    ORDER BY s.stall_no ASC
")->fetchAll(PDO::FETCH_ASSOC);

// ============================================================
// FETCH ARREARS DATA
// ============================================================

$arrearsData = $pdo->query("
    SELECT 
        l.id as lease_id, 
        u.id as tenant_id, 
        u.tenant_id as tenant_code, 
        s.stall_no, 
        CONCAT(u.first_name, ' ', u.last_name) AS full_name, 
        u.business_name,
        a.total_arrears,
        a.previous_arrears,
        a.triggered_date,
        a.trigger_reason,
        (SELECT COUNT(*) FROM dues WHERE lease_id=l.id AND paid=0) as unpaid_dues_count,
        (SELECT SUM(penalty_amount) FROM penalties WHERE lease_id=l.id) as total_penalties
    FROM leases l
    JOIN users u ON l.tenant_id=u.id
    JOIN stalls s ON l.stall_id=s.id
    JOIN arrears a ON a.lease_id=l.id
    WHERE u.status = 'active' AND a.total_arrears > 0
    ORDER BY a.total_arrears DESC, s.stall_no ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payments & Arrears - RentFlow</title>
    <link rel="stylesheet" href="/rentflow/public/assets/css/layout.css">
    <link rel="stylesheet" href="/rentflow/public/assets/css/payments.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body class="admin">

<header class="header">
    <h1 class="site-title">RentFlow</h1>
    <nav class="navigation">
        <ul>
            <li><a href="dashboard.php"><i class="material-icons">dashboard</i>Dashboard</a></li>
            <li><a href="tenants.php"><i class="material-icons">people</i>Tenants</a></li>
            <li><a href="payments.php" class="active"><i class="material-icons">payments</i>Payments</a></li>
            <li><a href="reports.php"><i class="material-icons">assessment</i>Reports</a></li>
            <li><a href="stalls.php"><i class="material-icons">store</i>Stalls</a></li>
            <li><a href="notifications.php" title="Notifications"><i class="material-icons">notifications</i>Notifications</a></li>
            <li><a href="account.php" class="nav-profile" title="Account"><i class="material-icons">person</i>Account</a></li>
            <li><a href="contact.php" title="Support"><i class="material-icons">contact_support</i>Contact</a></li>
        </ul>
    </nav>
</header>

<main class="content">
    <div class="page-header">
        <h1>Payments & Arrears Management</h1>
        <p class="subtitle">Manage tenant payments and track arrears</p>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <i class="material-icons">check_circle</i>
            Payment action completed successfully.
            <span class="close-alert" onclick="this.parentElement.style.display='none';">×</span>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-error">
            <i class="material-icons">error</i>
            <?= htmlspecialchars($error) ?>
            <span class="close-alert" onclick="this.parentElement.style.display='none';">×</span>
        </div>
    <?php endif; ?>

    <!-- Tabs Navigation -->
    <div class="tabs-container">
        <button class="tab-btn active" onclick="showTab('payments')">
            <i class="material-icons">pending_actions</i>
            Active Payments
            <span class="tab-count"><?= count($activePayments) ?></span>
        </button>
        <button class="tab-btn" onclick="showTab('arrears')">
            <i class="material-icons">warning</i>
            Arrears
            <span class="tab-count"><?= count($arrearsData) ?></span>
        </button>
    </div>

    <!-- PAYMENTS TABLE -->
    <div id="payments-tab" class="tab-content active">
        <table class="table payments-table">
            <thead>
                <tr>
                    <th>Stall</th>
                    <th>Tenant</th>
                    <th>Business</th>
                    <th>Previous Payment</th>
                    <th>Status</th>
                    <th>Next Payment</th>
                    <th>Next Amount</th>
                    <th>Remarks</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($activePayments as $payment): ?>
                    <tr>
                        <td class="stall-cell"><?= htmlspecialchars($payment['stall_no']) ?></td>
                        <td class="tenant-cell">
                            <a href="tenant_profile.php?id=<?= $payment['tenant_id'] ?>" class="tenant-link">
                                <?= htmlspecialchars($payment['full_name']) ?>
                            </a>
                            <small class="tenant-code"><?= htmlspecialchars($payment['tenant_code']) ?></small>
                        </td>
                        <td class="business-cell"><?= htmlspecialchars($payment['business_name'] ?? 'N/A') ?></td>
                        <td class="previous-payment">
                            <?php if ($payment['last_payment_date']): ?>
                                <span class="date"><?= htmlspecialchars($payment['last_payment_date']) ?></span>
                                <span class="amount">₱<?= number_format($payment['last_payment_amount'], 2) ?></span>
                            <?php else: ?>
                                <span class="no-data">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="status-cell">
                            <span class="badge badge-<?= strtolower($payment['payment_status']) ?>">
                                <?= htmlspecialchars($payment['payment_status']) ?>
                            </span>
                        </td>
                        <td class="next-payment-date">
                            <?= $payment['next_due'] ? htmlspecialchars($payment['next_due']) : '—' ?>
                        </td>
                        <td class="next-amount">
                            <?php if ($payment['next_amount']): ?>
                                ₱<?= number_format($payment['next_amount'], 2) ?>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td class="remarks-cell">
                            <small><?= htmlspecialchars($payment['last_payment_remarks'] ?? 'No remarks') ?></small>
                        </td>
                        <td class="actions-cell">
                            <a href="notifications.php?to=<?= $payment['tenant_id'] ?>" class="btn btn-small btn-message" title="Send message">
                                <i class="material-icons">mail</i>
                            </a>
                            <div class="action-dropdown">
                                <select class="action-select" onchange="handlePaymentAction(this, <?= $payment['lease_id'] ?>)">
                                    <option value="">Mark as...</option>
                                    <option value="paid">Paid</option>
                                    <option value="partial">Partial Paid</option>
                                    <option value="notpaid">Not Paid</option>
                                </select>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (empty($activePayments)): ?>
            <div class="empty-state">
                <i class="material-icons">trending_up</i>
                <p>No active leases found</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- ARREARS TABLE -->
    <div id="arrears-tab" class="tab-content">
        <table class="table arrears-table">
            <thead>
                <tr>
                    <th>Stall</th>
                    <th>Tenant</th>
                    <th>Business</th>
                    <th>Total Arrears</th>
                    <th>Previous Arrears</th>
                    <th>Triggered Date</th>
                    <th>Trigger Reason</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($arrearsData as $arrear): ?>
                    <tr>
                        <td class="stall-cell"><?= htmlspecialchars($arrear['stall_no']) ?></td>
                        <td class="tenant-cell">
                            <a href="tenant_profile.php?id=<?= $arrear['tenant_id'] ?>" class="tenant-link">
                                <?= htmlspecialchars($arrear['full_name']) ?>
                            </a>
                            <small class="tenant-code"><?= htmlspecialchars($arrear['tenant_code']) ?></small>
                        </td>
                        <td class="business-cell"><?= htmlspecialchars($arrear['business_name'] ?? 'N/A') ?></td>
                        <td class="total-arrears">
                            <strong class="amount-total">₱<?= number_format($arrear['total_arrears'], 2) ?></strong>
                        </td>
                        <td class="previous-arrears">
                            <a href="#" class="arrears-history-link" onclick="showArrearsHistory(<?= $arrear['lease_id'] ?>)">
                                ₱<?= number_format($arrear['previous_arrears'], 2) ?>
                                <i class="material-icons">history</i>
                            </a>
                        </td>
                        <td class="triggered-date">
                            <?= $arrear['triggered_date'] ? substr($arrear['triggered_date'], 0, 10) : '—' ?>
                        </td>
                        <td class="trigger-reason">
                            <span class="badge badge-reason">
                                <?= htmlspecialchars($arrear['trigger_reason'] ?? 'Unknown') ?>
                            </span>
                        </td>
                        <td class="actions-cell">
                            <button class="btn btn-small btn-message" onclick="showArrearsHistory(<?= $arrear['lease_id'] ?>)" title="View arrears history">
                                <i class="material-icons">visibility</i>
                            </button>
                            <a href="notifications.php?to=<?= $arrear['tenant_id'] ?>" class="btn btn-small" title="Send reminder">
                                <i class="material-icons">mail</i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (empty($arrearsData)): ?>
            <div class="empty-state">
                <i class="material-icons">check_circle</i>
                <p>No outstanding arrears</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<!-- MODALS -->

<!-- Modal: Mark as Paid -->
<div id="paidModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Mark Payment as Paid</h2>
            <button class="close-btn" onclick="closeModal('paidModal')">×</button>
        </div>
        <form method="POST" class="payment-form">
            <input type="hidden" name="lease_id" id="paidLeaseId" value="">
            <input type="hidden" name="submit_payment_action" value="1">
            <input type="hidden" name="action" value="paid">
            
            <div class="form-group">
                <label for="paidDueDate">Next Payment Due Date:</label>
                <input type="date" id="paidDueDate" name="next_due_date" required>
            </div>
            
            <div class="form-group">
                <label for="paidNextAmount">Next Payment Amount (₱):</label>
                <input type="number" id="paidNextAmount" name="next_amount" step="0.01" min="0" required>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Submit</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('paidModal')">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Mark as Partial Paid -->
<div id="partialModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Mark Payment as Partial Paid</h2>
            <button class="close-btn" onclick="closeModal('partialModal')">×</button>
        </div>
        <form method="POST" class="payment-form">
            <input type="hidden" name="lease_id" id="partialLeaseId" value="">
            <input type="hidden" name="submit_payment_action" value="1">
            <input type="hidden" name="action" value="partial">
            
            <div class="form-group">
                <label for="partialPaidAmount">Partial Amount Paid (₱):</label>
                <input type="number" id="partialPaidAmount" name="partial_amount" step="0.01" min="0" required>
            </div>
            
            <div class="form-group">
                <label for="partialDueDate">Next Payment Due Date:</label>
                <input type="date" id="partialDueDate" name="next_due_date" required>
            </div>
            
            <div class="form-group">
                <label for="partialNextAmount">Next Payment Amount (₱):</label>
                <input type="number" id="partialNextAmount" name="next_amount" step="0.01" min="0" required>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Submit</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('partialModal')">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Mark as Not Paid -->
<div id="notpaidModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Mark Payment as Not Paid</h2>
            <button class="close-btn" onclick="closeModal('notpaidModal')">×</button>
        </div>
        <form method="POST" class="payment-form">
            <input type="hidden" name="lease_id" id="notpaidLeaseId" value="">
            <input type="hidden" name="submit_payment_action" value="1">
            <input type="hidden" name="action" value="notpaid">
            
            <div class="form-group">
                <label for="notpaidDueDate">Next Payment Due Date:</label>
                <input type="date" id="notpaidDueDate" name="next_due_date" required>
            </div>
            
            <div class="form-group">
                <label for="notpaidNextAmount">Next Payment Amount (₱):</label>
                <input type="number" id="notpaidNextAmount" name="next_amount" step="0.01" min="0" required>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Submit</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('notpaidModal')">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Arrears History -->
<div id="arrearsHistoryModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2>Arrears History</h2>
            <button class="close-btn" onclick="closeModal('arrearsHistoryModal')">×</button>
        </div>
        <div id="arrearsHistoryContent" class="arrears-history-content">
            <p>Loading...</p>
        </div>
    </div>
</div>

<footer class="footer">
    <p>&copy; <?= date('Y') ?> RentFlow. All rights reserved.</p>
</footer>

<!-- Scripts -->
<script src="/rentflow/public/assets/js/table.js"></script>
<script src="/rentflow/public/assets/js/payments.js"></script>

</body>
</html>
