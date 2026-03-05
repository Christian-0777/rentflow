<?php
// admin/payments.php
// Payments and Arrears Management

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/auth.php';

// detect AJAX calls before any output (matches logic in auth.php functions)
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// ✅ Admin access only (treasury role removed)
require_role('admin');

// ============================================================
// HANDLE PAYMENT ACTIONS (Mark as Paid, Partial, Not Paid)
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'])) {
    // guard against duplicate form submissions or browser refresh
    $submittedToken = $_POST['form_token'] ?? '';
    // token must match the value stored when the form was generated
    if (empty($submittedToken) || $submittedToken !== ($_SESSION['last_payment_form'] ?? '')) {
        // either missing or doesn't match – treat as invalid/duplicate
        header("Location: payments.php");
        exit;
    }
    // consume the token immediately so a browser back/refresh can't reuse it
    unset($_SESSION['last_payment_form']);

    $action_type = $_POST['action_type'];
    $lease_id = (int)$_POST['lease_id'];
    $next_due_date = $_POST['next_due_date'] ?? null;
    $next_amount_due = (float)($_POST['next_amount_due'] ?? 0);
    
    try {
        $pdo->beginTransaction();
        
        // Get current unpaid due
        $current_due = $pdo->prepare("
            SELECT id, amount_due, due_date FROM dues 
            WHERE lease_id = ? AND paid = 0 
            ORDER BY due_date ASC LIMIT 1
        ");
        $current_due->execute([$lease_id]);
        $due_record = $current_due->fetch();
        
        if (!$due_record) {
            throw new Exception("No unpaid due found for this lease");
        }
        
        if ($action_type === 'paid') {
            // avoid duplicate full payment entry for the same lease/date and due
            $dup = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE lease_id=? AND due_id=? AND payment_date=CURDATE() AND remarks='Full Payment'");
            $dup->execute([$lease_id, $due_record['id']]);
            if ($dup->fetchColumn() === 0) {
                // Mark current due as paid
                $pdo->prepare("UPDATE dues SET paid = 1 WHERE id = ?")->execute([$due_record['id']]);
                
                // Insert full payment record (store due_id)
                $pdo->prepare("
                    INSERT INTO payments (lease_id, due_id, amount, payment_date, method, remarks) 
                    VALUES (?, ?, ?, CURDATE(), 'manual', 'Full Payment')
                ")->execute([$lease_id, $due_record['id'], $due_record['amount_due']]);
            }
        
        } elseif ($action_type === 'partial') {
            $amount_paid = (float)($_POST['amount_paid'] ?? 0);
            
            if ($amount_paid <= 0 || $amount_paid >= $due_record['amount_due']) {
                throw new Exception("Invalid partial payment amount");
            }
            
            // avoid duplicate partial payment for today with same due
            $dup = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE lease_id=? AND due_id=? AND payment_date=CURDATE() AND remarks='Partial Payment'");
            $dup->execute([$lease_id, $due_record['id']]);
            if ($dup->fetchColumn() === 0) {
                // Insert partial payment record (store due_id)
                $pdo->prepare("
                    INSERT INTO payments (lease_id, due_id, amount, payment_date, method, remarks) 
                    VALUES (?, ?, ?, CURDATE(), 'partial', 'Partial Payment')
                ")->execute([$lease_id, $due_record['id'], $amount_paid]);
                
                // Mark original due as paid (remainder is tracked separately as arrear entry)
                $pdo->prepare("UPDATE dues SET paid = 1 WHERE id = ?")->execute([$due_record['id']]);
                
                // Add remaining to arrears with arrear_entries tracking
                $remaining = $due_record['amount_due'] - $amount_paid;
                
                // avoid duplicate arrear entry
                $dupEntry = $pdo->prepare("SELECT COUNT(*) FROM arrear_entries WHERE lease_id=? AND due_id=? AND source='partial_payment' AND amount=? AND DATE(created_on)=CURDATE()");
                $dupEntry->execute([$lease_id, $due_record['id'], $remaining]);
                if ($dupEntry->fetchColumn() === 0) {
                    $pdo->prepare("
                        INSERT INTO arrear_entries (lease_id, due_id, amount, source, created_on) 
                        VALUES (?, ?, ?, 'partial_payment', CURDATE())
                    ")->execute([$lease_id, $due_record['id'], $remaining]);
                }
                
                // Update or insert arrears record
                $existing_arrears = $pdo->prepare("SELECT id, total_arrears FROM arrears WHERE lease_id = ?");
                $existing_arrears->execute([$lease_id]);
                $arr = $existing_arrears->fetch();
                
                if ($arr) {
                    $pdo->prepare("
                        UPDATE arrears 
                        SET total_arrears = total_arrears + ?, last_updated = NOW() 
                        WHERE lease_id = ?
                    ")->execute([$remaining, $lease_id]);
                } else {
                    $pdo->prepare("
                        INSERT INTO arrears (lease_id, total_arrears, last_updated) 
                        VALUES (?, ?, NOW())
                    ")->execute([$lease_id, $remaining]);
                }
            }
        
        } elseif ($action_type === 'notpaid') {
            // prevent duplicate not‑paid marker for the same due
            $dup = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE lease_id=? AND due_id=? AND payment_date=CURDATE() AND remarks='Marked as Not Paid'");
            $dup->execute([$lease_id, $due_record['id']]);
            if ($dup->fetchColumn() === 0) {
                // mark the due itself as processed so it moves to history
                $pdo->prepare("UPDATE dues SET paid = 1 WHERE id = ?")->execute([$due_record['id']]);

                // Add unpaid amount to arrears with arrear_entries tracking
                $dupEntry = $pdo->prepare("SELECT COUNT(*) FROM arrear_entries WHERE lease_id=? AND due_id=? AND source='marked_not_paid' AND amount=? AND DATE(created_on)=CURDATE()");
                $dupEntry->execute([$lease_id, $due_record['id'], $due_record['amount_due']]);
                if ($dupEntry->fetchColumn() === 0) {
                    $pdo->prepare("
                        INSERT INTO arrear_entries (lease_id, due_id, amount, source, created_on) 
                        VALUES (?, ?, ?, 'marked_not_paid', CURDATE())
                    ")->execute([$lease_id, $due_record['id'], $due_record['amount_due']]);
                }
                
                // Insert "not paid" payment record for tracking
                $pdo->prepare("
                    INSERT INTO payments (lease_id, due_id, amount, payment_date, method, remarks) 
                    VALUES (?, ?, 0, CURDATE(), 'manual', 'Marked as Not Paid')
                ")->execute([$lease_id, $due_record['id']]);
                
                // Update or insert arrears record
                $existing_arrears = $pdo->prepare("SELECT id, total_arrears FROM arrears WHERE lease_id = ?");
                $existing_arrears->execute([$lease_id]);
                $arr = $existing_arrears->fetch();
                
                if ($arr) {
                    $pdo->prepare("
                        UPDATE arrears 
                        SET total_arrears = total_arrears + ?, last_updated = NOW() 
                        WHERE lease_id = ?
                    ")->execute([$due_record['amount_due'], $lease_id]);
                } else {
                    $pdo->prepare("
                        INSERT INTO arrears (lease_id, total_arrears, last_updated) 
                        VALUES (?, ?, NOW())
                    ")->execute([$lease_id, $due_record['amount_due']]);
                }
            }
        }
        
        // Insert next due if provided
        if ($next_due_date && $next_amount_due > 0) {
            $pdo->prepare("
                INSERT INTO dues (lease_id, due_date, amount_due, paid) 
                VALUES (?, ?, ?, 0)
            ")->execute([$lease_id, $next_due_date, $next_amount_due]);
        }
        
        $pdo->commit();
        
        if ($isAjax) {
            echo json_encode(['success' => true]);
            exit;
        } else {
            // Redirect to refresh page
            header("Location: payments.php?success=1");
            exit;
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_msg = $e->getMessage();
        if ($isAjax) {
            echo json_encode(['error' => $error_msg]);
            exit;
        }
    }
}

// generate a one‑time token for the payment modal form to prevent double submissions
$token = bin2hex(random_bytes(16));
$_SESSION['last_payment_form'] = $token;

// ============================================================
// FETCH PAYMENTS DATA
// ============================================================

$payments_rows = $pdo->query("
    SELECT 
        l.id as lease_id, 
        u.id as tenant_id, 
        u.tenant_id as tenant_code, 
        s.stall_no, 
        CONCAT(u.first_name, ' ', u.last_name) AS full_name, 
        u.business_name,
        COALESCE(a.total_arrears, 0) as total_arrears,
        -- total arrears paid by tenant (entries recorded through arrear payment process)
        COALESCE((
            SELECT SUM(amount) FROM payments 
            WHERE lease_id = l.id AND remarks LIKE 'Arrear Payment%'
        ), 0) as total_arrears_paid,
        -- always show the most recent row regardless of amount (including 'Marked as Not Paid')
        COALESCE((
            SELECT payment_date FROM payments 
            WHERE lease_id = l.id
            ORDER BY payment_date DESC, id DESC LIMIT 1
        ), NULL) as last_payment_date,
        COALESCE((
            SELECT 
                CASE 
                    WHEN remarks = 'Marked as Not Paid' AND due_id IS NOT NULL 
                        THEN COALESCE((SELECT amount_due FROM dues WHERE id = p.due_id), 0)
                    ELSE amount
                END
            FROM payments p
            WHERE lease_id = l.id
            ORDER BY payment_date DESC, id DESC LIMIT 1
        ), 0) as last_payment_amount,
        COALESCE((
            SELECT remarks FROM payments 
            WHERE lease_id = l.id
            ORDER BY payment_date DESC, id DESC LIMIT 1
        ), 'No payments yet') as last_payment_status,
        (SELECT due_date FROM dues WHERE lease_id=l.id AND paid=0 ORDER BY due_date ASC LIMIT 1) AS next_due,
        (SELECT amount_due FROM dues WHERE lease_id=l.id AND paid=0 ORDER BY due_date ASC LIMIT 1) AS next_amount,
        CASE
            WHEN EXISTS(SELECT 1 FROM dues WHERE lease_id=l.id AND paid=0 AND due_date<CURDATE())
            THEN 'Overdue'
            WHEN EXISTS(SELECT 1 FROM dues WHERE lease_id=l.id AND paid=0)
            THEN 'Pending'
            ELSE 'Paid'
        END AS next_payment_status
    FROM leases l
    JOIN users u ON l.tenant_id=u.id
    JOIN stalls s ON l.stall_id=s.id
    LEFT JOIN arrears a ON a.lease_id = l.id
    WHERE u.role = 'tenant'
    ORDER BY s.stall_no ASC
")->fetchAll();

// ============================================================
// FETCH ARREARS DATA
// ============================================================

$arrears_rows = $pdo->query("
    SELECT 
        l.id as lease_id, 
        u.id as tenant_id, 
        u.tenant_id as tenant_code, 
        s.stall_no, 
        CONCAT(u.first_name, ' ', u.last_name) AS full_name, 
        u.business_name,
        COALESCE(a.total_arrears, 0) as total_arrears,
        COALESCE((
            SELECT COALESCE(SUM(amount), 0)
            FROM arrear_entries
            WHERE lease_id = l.id AND source IN ('partial_payment', 'marked_not_paid', 'overdue_7days')
        ), 0) as previous_arrears,
        -- number of days passed since first unpaid arrear entry was created
        COALESCE((
            SELECT DATEDIFF(CURDATE(), MIN(created_on))
            FROM arrear_entries
            WHERE lease_id = l.id AND is_paid = 0
        ), 0) as days_since_arrears
    FROM leases l
    JOIN users u ON l.tenant_id=u.id
    JOIN stalls s ON l.stall_id=s.id
    LEFT JOIN arrears a ON a.lease_id=l.id
    WHERE u.role = 'tenant' AND COALESCE(a.total_arrears, 0) > 0
    ORDER BY s.stall_no ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payments & Arrears - RentFlow</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/rentflow/public/assets/css/layout.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        .table tbody td {
            text-transform: uppercase;
        }
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #ddd;
        }
        .tab-button {
            padding: 12px 20px;
            background: none;
            border: none;
            font-size: 16px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }
        .tab-button.active {
            border-bottom-color: #007bff;
            color: #007bff;
            font-weight: 600;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .btn.small {
            padding: 6px 10px;
            font-size: 12px;
        }
        .btn.small:hover {
            background-color: #0056b3;
        }
        select {
            padding: 6px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .arrear-amount {
            color: #d9534f;
            font-weight: bold;
            cursor: pointer;
            text-decoration: underline;
        }
        .arrear-amount:hover {
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
    <?php if (isset($_GET['success'])): ?>
        <div style="background-color: #dff0d8; color: #3c763d; padding: 12px; border-radius: 4px; margin-bottom: 20px;">
            ✓ Payment recorded successfully!
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error_msg)): ?>
        <div style="background-color: #f2dede; color: #a94442; padding: 12px; border-radius: 4px; margin-bottom: 20px;">
            ✗ Error: <?= htmlspecialchars($error_msg) ?>
        </div>
    <?php endif; ?>

    <!-- TAB NAVIGATION -->
    <div class="tabs">
        <button class="tab-button active" onclick="switchTab('payments')">
            <i class="material-icons" style="vertical-align: middle; margin-right: 5px;">receipt</i>Payments
        </button>
        <button class="tab-button" onclick="switchTab('arrears')">
            <i class="material-icons" style="vertical-align: middle; margin-right: 5px;">warning</i>Arrears
        </button>
    </div>

    <!-- PAYMENTS TABLE TAB -->
    <div id="payments" class="tab-content active">
        <h2>Payment Records</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Stall</th>
                    <th>Tenant</th>
                    <th>Business</th>
                    <th>Previous Payment</th>
                    <th>Previous Status</th>
                    <th>Total Arrears Paid</th>
                    <th>Next Payment</th>
                    <th>Next Payment Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments_rows as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['stall_no']) ?></td>
                    <td>
                        <a href="tenant_profile.php?id=<?= $row['tenant_id'] ?>">
                            <?= htmlspecialchars($row['full_name']) ?> (<?= htmlspecialchars($row['tenant_code']) ?>)
                        </a>
                    </td>
                    <td><?= htmlspecialchars($row['business_name'] ?? '—') ?></td>
                    <td>
                        <?php
                        if ($row['last_payment_date']) {
                            echo htmlspecialchars(date('M d, Y', strtotime($row['last_payment_date'])));
                            echo '<br><small>₱' . number_format($row['last_payment_amount'], 2) . '</small>';
                        } else {
                            echo '—';
                        }
                        ?>
                    </td>
                    <td>
                        <?php
                        if ($row['last_payment_date']) {
                            echo '<small>' . htmlspecialchars($row['last_payment_status']) . '</small>';
                        } else {
                            echo '—';
                        }
                        ?>
                    </td>
                    <td>
                        <?php
                        if ($row['total_arrears_paid'] > 0) {
                            echo '<small>₱' . number_format($row['total_arrears_paid'],2) . '</small>';
                        } else {
                            echo '<small>—</small>';
                        }
                        ?>
                    </td>
                    <td>
                        <?php
                        if ($row['next_due']) {
                            echo htmlspecialchars(date('M d, Y', strtotime($row['next_due'])));
                            echo '<br><small>₱' . number_format($row['next_amount'], 2) . '</small>';
                        } else {
                            echo '—';
                        }
                        ?>
                    </td>
                    <td>
                        <?php
                        if ($row['next_due']) {
                            echo '<small>';
                            if (strtotime($row['next_due']) < strtotime(date('Y-m-d'))) {
                                echo '<span style="color: #d9534f; font-weight: bold;">Overdue</span>';
                            } else {
                                echo '<span style="color: #f0ad4e; font-weight: bold;">Pending</span>';
                            }
                            echo '</small>';
                        } else {
                            echo '<small style="color: #5cb85c; font-weight: bold;">Paid</small>';
                        }
                        ?>
                    </td>
                    <td>
                        <a href="notifications.php?to=<?= $row['tenant_id'] ?>" class="btn small" style="margin-right: 5px;">Message</a>
                        <?php if ($row['next_due']): ?>
                            <select onchange="openPaymentModal(this.value, <?= $row['lease_id'] ?>)" style="margin-top: 5px;">
                                <option value="">Action</option>
                                <option value="paid">Mark as Paid</option>
                                <option value="partial">Mark as Partial</option>
                                <option value="notpaid">Mark as Not Paid</option>
                            </select>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- ARREARS TABLE TAB -->
    <div id="arrears" class="tab-content">
        <h2>Arrears Records</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Stall</th>
                    <th>Tenant</th>
                    <th>Business</th>
                    <th>Previous Arrears</th>
                    <th>Days Since Arrears</th>
                    <th>Total Arrears</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($arrears_rows as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['stall_no']) ?></td>
                    <td>
                        <a href="tenant_profile.php?id=<?= $row['tenant_id'] ?>">
                            <?= htmlspecialchars($row['full_name']) ?> (<?= htmlspecialchars($row['tenant_code']) ?>)
                        </a>
                    </td>
                    <td><?= htmlspecialchars($row['business_name'] ?? '—') ?></td>
                    <td>
                        <span class="arrear-amount" onclick="showArrearsHistory(<?= $row['lease_id'] ?>, <?= $row['total_arrears'] ?>)">
                            ₱<?= number_format($row['previous_arrears'], 2) ?>
                        </span>
                    </td>
                    <td><?= $row['days_since_arrears'] ? $row['days_since_arrears'].' day(s)' : '—' ?></td>
                    <td>
                        <span class="arrear-amount">₱<?= number_format($row['total_arrears'], 2) ?></span>
                    </td>
                    <td>
                        <a href="notifications.php?to=<?= $row['tenant_id'] ?>" class="btn small" style="margin-right:5px;">Message</a>
                        <button class="btn small" onclick="showArrearsHistory(<?= $row['lease_id'] ?>, <?= $row['total_arrears'] ?>)">Pay</button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($arrears_rows)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; color: #999;">No arrears found</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<!-- FOOTER -->
<footer class="footer">
    <p>&copy; <?= date('Y') ?> RentFlow. All rights reserved.</p>
</footer>

<!-- ============================================================
     MODALS
     ============================================================ -->

<!-- PAYMENT ACTION MODAL -->
<div id="paymentModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
    <div style="background-color: white; margin: 10% auto; padding: 30px; border-radius: 8px; width: 90%; max-width: 500px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <span onclick="closePaymentModal()" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
        
        <h3 id="modalTitle">Payment Action</h3>
        <form method="post" id="paymentForm">
            <input type="hidden" name="form_token" value="<?= htmlspecialchars($token) ?>">
            <input type="hidden" name="action_type" id="actionType">
            <input type="hidden" name="lease_id" id="leaseIdInput">
            
            <!-- Partial Payment Amount (shown only for partial action) -->
            <div id="partialPaidSection" style="display: none; margin-bottom: 15px;">
                <label for="amountPaid">Amount Paid (₱):</label>
                <input type="number" id="amountPaid" name="amount_paid" step="0.01" min="0" placeholder="Enter amount paid" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; margin-top: 5px;">
            </div>
            
            <!-- Next Payment Date -->
            <div style="margin-bottom: 15px;">
                <label for="nextDueDate">Next Payment Date:</label>
                <input type="date" id="nextDueDate" name="next_due_date" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; margin-top: 5px;">
            </div>
            
            <!-- Next Payment Amount -->
            <div style="margin-bottom: 15px;">
                <label for="nextAmountDue">Next Payment Amount (₱):</label>
                <input type="number" id="nextAmountDue" name="next_amount_due" step="0.01" min="0" required placeholder="Enter amount due" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; margin-top: 5px;">
            </div>
            
            <!-- Buttons -->
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="submit" class="btn" style="flex: 1;">Submit</button>
                <button type="button" onclick="closePaymentModal()" class="btn" style="flex: 1; background-color: #6c757d;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- ARREARS HISTORY MODAL -->
<div id="arrearsHistoryModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
    <div style="background-color: white; margin: 5% auto; padding: 30px; border-radius: 8px; width: 90%; max-width: 700px; max-height: 80vh; overflow-y: auto; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <span onclick="closeArrearsHistoryModal()" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
        
        <h3>Arrears History</h3>
        <div id="arrearsHistoryContent" style="margin-top: 20px;">
            <p>Loading...</p>
        </div>
    </div>
</div>

<!-- ARREAR PAYMENT MODAL -->
<div id="arrearPayModal" style="display:none; position: fixed; z-index: 1100; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5);">
    <div style="background:#fff; margin:10% auto; padding:20px; border-radius:8px; width:90%; max-width:400px;">
        <span onclick="closeArrearPayModal()" style="color:#aaa; float:right; font-size:28px; cursor:pointer;">&times;</span>
        <h3>Pay Arrear</h3>
        <form id="arrearPayForm">
            <input type="hidden" id="payLeaseId" name="lease_id">
            <input type="hidden" id="payDueDate" name="due_date">
            <div style="margin-bottom:15px;">
                <label for="payAmount">Amount (₱):</label>
                <input type="number" id="payAmount" name="amount_paid" step="0.01" min="0" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px; margin-top:5px;" readonly>
            </div>
            <div style="margin-bottom:15px;">
                <label for="payPenalty">Penalty (₱):</label>
                <input type="number" id="payPenalty" name="penalty" step="0.01" min="0" value="0" placeholder="Enter penalty amount" required style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px; margin-top:5px;">
            </div>
            <div style="display:flex; gap:10px;">
                <button type="button" class="btn" onclick="submitArrearPayment()" style="flex:1;">Confirm</button>
                <button type="button" class="btn" onclick="closeArrearPayModal()" style="flex:1; background-color:#6c757d;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================
     SCRIPTS
     ============================================================ -->
<script src="/rentflow/public/assets/js/table.js"></script>
<script>
// ============================================================
// TAB SWITCHING
// ============================================================
function switchTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(el => {
        el.classList.remove('active');
    });
    
    // Remove active class from all buttons
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Show selected tab
    document.getElementById(tabName).classList.add('active');
    
    // Add active class to clicked button
    event.target.classList.add('active');
}

// ============================================================
// PAYMENT MODAL FUNCTIONS
// ============================================================
// intercept submission for AJAX behavior
document.getElementById('paymentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const form = e.target;
    const data = new FormData(form);
    fetch('', {
        method: 'POST',
        headers: {'X-Requested-With': 'XMLHttpRequest'},
        body: data
    })
    .then(r => r.json())
    .then(json => {
        if (json.success) {
            // reload to show updated records
            location.reload();
        } else {
            alert('Error: ' + (json.error || 'unknown'));
        }
    })
    .catch(err => alert('Network error: ' + err.message));
});

function openPaymentModal(action, leaseId) {
    if (!action) return;
    
    const modal = document.getElementById('paymentModal');
    const form = document.getElementById('paymentForm');
    const partialSection = document.getElementById('partialPaidSection');
    const modalTitle = document.getElementById('modalTitle');
    
    // Set values
    document.getElementById('actionType').value = action;
    document.getElementById('leaseIdInput').value = leaseId;
    
    // Set next month as default due date
    const nextMonth = new Date();
    nextMonth.setMonth(nextMonth.getMonth() + 1);
    document.getElementById('nextDueDate').value = nextMonth.toISOString().split('T')[0];
    
    // Update modal title and show/hide partial payment section
    if (action === 'paid') {
        modalTitle.textContent = 'Mark as Paid';
        partialSection.style.display = 'none';
        document.getElementById('amountPaid').required = false;
    } else if (action === 'partial') {
        modalTitle.textContent = 'Mark as Partial Paid';
        partialSection.style.display = 'block';
        document.getElementById('amountPaid').required = true;
    } else if (action === 'notpaid') {
        modalTitle.textContent = 'Mark as Not Paid';
        partialSection.style.display = 'none';
        document.getElementById('amountPaid').required = false;
    }
    
    // Reset and show modal
    form.reset();
    document.getElementById('nextDueDate').value = nextMonth.toISOString().split('T')[0];
    modal.style.display = 'block';
}

function closePaymentModal() {
    document.getElementById('paymentModal').style.display = 'none';
}

// ============================================================
// ARREARS HISTORY MODAL FUNCTIONS
// ============================================================
function showArrearsHistory(leaseId, previousArrears) {
    const modal = document.getElementById('arrearsHistoryModal');
    const content = document.getElementById('arrearsHistoryContent');
    
    content.innerHTML = '<p>Loading...</p>';
    modal.style.display = 'block';
    
    fetch('/rentflow/api/arrears_history.php?lease_id=' + leaseId, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                content.innerHTML = '<p style="color: #d9534f;">Error: ' + htmlEscape(data.error) + '</p>';
                return;
            }
            
            let html = '<div style="margin-bottom: 15px;">';
            html += '<p><strong>Previous Arrears:</strong> ₱' + previousArrears.toFixed(2) + '</p>';
            
            if (data.total_penalties > 0) {
                html += '<p><strong>Total Penalties Applied:</strong> ₱' + data.total_penalties.toFixed(2) + '</p>';
            }
            html += '</div>';
            
            if (data.history && data.history.length > 0) {
                html += '<table class="table" style="margin-top: 15px;">';
                html += '<thead><tr><th>Date</th><th>Amount</th><th>Type</th><th>Action</th></tr></thead>';
                html += '<tbody>';
                
                data.history.forEach(item => {
                    html += '<tr>';
                    html += '<td>' + htmlEscape(item.date) + '</td>';
                    html += '<td>₱' + item.amount.toFixed(2) + '</td>';
                    html += '<td>' + htmlEscape(item.type) + '</td>';
                    
                    // allow payment for any arrear entry (non-penalty)
                    if (item.type !== 'Penalty Applied') {
                        html += '<td><button class="btn small" onclick="payArrear(' + leaseId + ', \'' + item.date + '\', ' + item.amount + ')">Pay</button></td>';
                    } else {
                        html += '<td>—</td>';
                    }
                    html += '</tr>';
                });
                
                html += '</tbody></table>';
            } else {
                html += '<p>No arrears history found.</p>';
            }
            
            content.innerHTML = html;
        })
        .catch(error => {
            content.innerHTML = '<p style="color: #d9534f;">Error loading history: ' + htmlEscape(error.message) + '</p>';
        });
}

function closeArrearsHistoryModal() {
    document.getElementById('arrearsHistoryModal').style.display = 'none';
}

// open arrear payment modal with prefilled values
function payArrear(leaseId, dueDate, amount) {
    document.getElementById('payLeaseId').value = leaseId;
    document.getElementById('payDueDate').value = dueDate;
    document.getElementById('payAmount').value = amount.toFixed(2);
    document.getElementById('payPenalty').value = '0.00';
    document.getElementById('arrearPayModal').style.display = 'block';
}

// submit arrear payment request
function submitArrearPayment() {
    const leaseId = document.getElementById('payLeaseId').value;
    const dueDate = document.getElementById('payDueDate').value;
    const amountPaid = document.getElementById('payAmount').value;
    const penalty = document.getElementById('payPenalty').value;

    fetch('/rentflow/api/pay_arrear.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'lease_id=' + leaseId + '&due_date=' + encodeURIComponent(dueDate) +
              '&amount_paid=' + encodeURIComponent(amountPaid) +
              '&penalty=' + encodeURIComponent(penalty)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Arrear payment recorded successfully!');
            closeArrearPayModal();
            showArrearsHistory(leaseId, 0);
            location.reload();
        } else {
            alert('Error: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        alert('Error processing payment: ' + error.message);
    });
}

function closeArrearPayModal() {
    document.getElementById('arrearPayModal').style.display = 'none';
}

// ============================================================
// UTILITY FUNCTIONS
// ============================================================
function htmlEscape(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

// Close modals when clicking outside
window.onclick = function(event) {
    const paymentModal = document.getElementById('paymentModal');
    const arrearsModal = document.getElementById('arrearsHistoryModal');
    
    if (event.target === paymentModal) {
        paymentModal.style.display = 'none';
    }
    if (event.target === arrearsModal) {
        arrearsModal.style.display = 'none';
    }
};
</script>
</body>
</html>
