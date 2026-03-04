# Admin Payments Page Rework - Implementation Summary

## Overview
Complete rework of the [admin/payments.php](admin/payments.php) page with separate **Payments** and **Arrears** tables, improved modal handling, and enhanced arrear tracking system.

---

## 📋 Database Changes

### Migration File Created: [sql/migration.sql](sql/migration.sql)

#### New Table: `arrear_entries`
Tracks individual arrear items with detailed source information:
- `id` - Primary key
- `lease_id` - Reference to lease
- `due_id` - Reference to dues table
- `amount` - Arrear amount (DECIMAL)
- `source` - Type of arrear:
  - `'unpaid_due'` - Unpaid bills
  - `'marked_not_paid'` - Explicitly marked as not paid
  - `'partial_payment'` - Remaining from partial payment
  - `'overdue_7days'` - Overdue for 7+ days
- `created_on` - Date arrear was created
- `is_paid` - Payment status (0 or 1)
- `paid_on` - Date payment was made
- `created_at` - Timestamp

#### Modified Tables:
- **`dues`**: Added `marked_arrear_on` column to track when marked as overdue
- **`arrears`**: Added `reason` column for context

---

## 🔄 Page Structure

### Two-Tab Interface

#### **Tab 1: Payments**
Displays all leases with payment information:

| Column | Details |
|--------|---------|
| Stall | Stall number |
| Tenant | Tenant name + code with profile link |
| Business | Business name |
| Previous Payment | Date & amount of last payment |
| Previous Status | Last payment status (Full Payment, Partial, Marked Not Paid, etc.) |
| Next Payment | Due date & amount |
| Next Payment Status | Overdue/Pending/Paid (color-coded) |
| Actions | Message button + Action dropdown |

**Action Dropdown Options:**
- ✅ Mark as Paid
- 📊 Mark as Partial Paid
- ❌ Mark as Not Paid

#### **Tab 2: Arrears**
Displays only leases with active arrears:

| Column | Details |
|--------|---------|
| Stall | Stall number |
| Tenant | Tenant name + code with profile link |
| Business | Business name |
| Previous Arrears | Sum of marked-not-paid/partial amounts (clickable) |
| Current Penalties | Penalties from current month |
| Total Arrears | Sum of all arrears |
| Actions | Message button |

---

## 🎯 Action Modals

### Modal 1: Mark as Paid
**Trigger:** Click "Mark as Paid" action

**Fields:**
- Next Payment Date (required)
- Next Payment Amount (required, ₱)

**Behavior:**
1. Mark current unpaid due as paid (`dues.paid = 1`)
2. Insert full payment record
3. Insert next due if provided

### Modal 2: Mark as Partial Paid
**Trigger:** Click "Mark as Partial Paid" action

**Fields:**
- Amount Paid (required, ₱)
- Next Payment Date (required)
- Next Payment Amount (required, ₱)

**Behavior:**
1. Insert partial payment record
2. Calculate remaining = due_amount - amount_paid
3. Add remaining to arrear_entries with source='partial_payment'
4. Update total_arrears in arrears table
5. Insert next due if provided

### Modal 3: Mark as Not Paid
**Trigger:** Click "Mark as Not Paid" action

**Fields:**
- Next Payment Date (required)
- Next Payment Amount (required, ₱)

**Behavior:**
1. Add unpaid amount to arrear_entries with source='marked_not_paid'
2. Insert "Marked as Not Paid" payment record
3. Update total_arrears in arrears table
4. Insert next due if provided

---

## 📊 Arrears History Modal

**Trigger:** Click on "Previous Arrears" amount in Arrears tab

**Shows:**
- Previous Arrears total
- Total Penalties Applied (current month)
- Table of all arrear entries with:
  - Date
  - Amount
  - Type (Unpaid Due, Marked Not Paid, Partial Payment, Penalty Applied)
  - Action buttons (Pay button for payable items)

**Pay Button:**
- Allows partial or full payment of individual arrear entries
- Updates arrear_entries.is_paid and paid_on
- Recalculates total_arrears
- Reloads history after successful payment

---

## 🛠️ PHP Files Modified

### [admin/payments.php](admin/payments.php)
**Changes:**
- Complete page restructure with two-tab interface
- Separated Payments and Arrears queries
- New calculations:
  - `previous_arrears`: Sum from arrear_entries where source IN ('partial_payment', 'marked_not_paid', 'overdue_7days')
  - `current_month_penalties`: Sum of penalties for current month
  - `total_arrears`: Total from arrears table
- Transactional payment processing with proper error handling
- Enhanced modal system with dynamic title/field updates

**Key Queries:**
```sql
-- Payments Data
SELECT ... FROM leases l
JOIN users u ON l.tenant_id=u.id
JOIN stalls s ON l.stall_id=s.id
WHERE u.role = 'tenant'

-- Arrears Data (only with arrears)
SELECT ... FROM leases l
JOIN users u ON l.tenant_id=u.id
JOIN stalls s ON l.stall_id=s.id
LEFT JOIN arrears a ON a.lease_id=l.id
WHERE u.role = 'tenant' AND COALESCE(a.total_arrears, 0) > 0
```

### [api/arrears_history.php](api/arrears_history.php)
**Changes:**
- Queries arrear_entries instead of dues
- Includes all arrear sources (unpaid_due, marked_not_paid, partial_payment, overdue_7days)
- Returns formatted history with:
  - date (created_on)
  - amount
  - type (Unpaid Due, Marked Not Paid, Partial Payment, Penalty Applied, Overdue)
  - total_penalties for current month

### [api/pay_arrear.php](api/pay_arrear.php)
**Changes:**
- Updated to work with arrear_entries table
- Transactional processing
- Handles both full and partial payments
- Updates is_paid and paid_on fields
- Recalculates total_arrears in arrears table
- Error handling with rollback

---

## 🎨 UI/UX Improvements

### Tab System
- Clean tab navigation at top
- Active tab highlighted with blue underline
- Smooth switching without page reload
- Icon + text for clarity

### Color Coding
- **Overdue**: Red (#d9534f)
- **Pending**: Yellow/Orange (#f0ad4e)
- **Paid**: Green (#5cb85c)
- **Arrear Amount**: Red with underline (clickable)

### Modals
- Modern styling with rounded corners
- Proper z-index management
- Click-outside to close
- X button to close
- Responsive width (90% on mobile, max 500px)

### Buttons & Controls
- Consistent styling with layout.css
- Small action buttons for table actions
- Form buttons with proper submit/cancel
- Color-coded action buttons

---

## 📱 JavaScript Features

### Tab Switching
```javascript
switchTab(tabName)
```
- Manages active state for tabs and content
- Prevents automatic refresh

### Payment Modal Management
```javascript
openPaymentModal(action, leaseId)
closePaymentModal()
```
- Dynamic modal title based on action
- Shows/hides partial payment field based on action
- Default next month date calculation
- Form reset on open

### Arrears History Modal
```javascript
showArrearsHistory(leaseId, previousArrears)
closeArrearsHistoryModal()
```
- AJAX fetch from API endpoint
- Dynamic table generation
- Pay button for individual items
- Error handling

### Pay Arrear Function
```javascript
payArrear(leaseId, dueDate, amount)
```
- Prompt for payment amount
- AJAX submission to API
- Success/error handling
- Page reload on success (optional)

---

## 🔐 Security

- **Authentication**: `require_role(['admin', 'treasury'])`
- **Input Validation**: Type casting, null checks
- **SQL Injection Prevention**: Prepared statements
- **HTML Escaping**: `htmlspecialchars()` for user output
- **Transactional Integrity**: `BEGIN/COMMIT/ROLLBACK` for payment operations

---

## 🧪 Testing Recommendations

1. **Test Mark as Paid:**
   - Verify due marked as paid
   - Check payment record created
   - Confirm next due inserted
   - Check arrears unchanged

2. **Test Mark as Partial:**
   - Verify payment partial recorded
   - Check arrear_entries created with correct source
   - Confirm total_arrears updated
   - Validate arrear amount = due - paid

3. **Test Mark as Not Paid:**
   - Verify arrear_entries created
   - Check total_arrears updated
   - Confirm payment record with 0 amount

4. **Test Arrears History:**
   - Click different previous arrears amounts
   - Verify all arrear entries shown
   - Check penalties calculation
   - Test pay button functionality

5. **Tab Navigation:**
   - Verify tab switching doesn't lose data
   - Check both tables load correctly
   - Confirm table sorting works in both tabs

---

## 🚀 Deployment Steps

1. **Run Migration:**
   ```bash
   mysql -u root -p rentflow < sql/migration.sql
   ```

2. **Verify Database:**
   ```sql
   SHOW TABLES LIKE 'arrear%';
   DESCRIBE arrear_entries;
   DESCRIBE dues;
   DESCRIBE arrears;
   ```

3. **Clear Browser Cache:** Force refresh to load new JavaScript

4. **Test Payments Page:** Visit `/admin/payments.php`

---

## 📞 Support

For issues or questions:
- Check browser console for JavaScript errors
- Verify database migrations applied
- Check PHP error logs
- Ensure API endpoints accessible at `/api/arrears_history.php` and `/api/pay_arrear.php`

---

## ✅ Feature Checklist

- ✅ Two-tab interface (Payments & Arrears)
- ✅ Separate data queries for each tab
- ✅ Previous/Next payment tracking
- ✅ Previous/Total arrears calculation
- ✅ Three payment action modals
- ✅ Arrears history modal with pay functionality
- ✅ arrear_entries table for tracking
- ✅ Transactional payment processing
- ✅ Color-coded status indicators
- ✅ AJAX-based history loading
- ✅ Error handling and validation
- ✅ Mobile-responsive design

---

Generated: February 18, 2026
