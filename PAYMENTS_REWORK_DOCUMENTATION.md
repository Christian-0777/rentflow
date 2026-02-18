# Payments & Arrears System Rework - Documentation

**Date:** February 18, 2026  
**Version:** 2.0  
**Status:** Complete with Dual Table Architecture

---

## Overview

The entire payments and arrears management system has been reworked to provide:
- Separate **Payments** and **Arrears** tables with distinct functionality
- Enhanced **Modal-based forms** for payment actions (Mark as Paid, Partial Paid, Not Paid)
- Improved **Arrears tracking** with trigger reasons and historical data
- Better **User interface** with tabbed navigation and modern design
- Comprehensive **API updates** for better data management

---

## Files Modified/Created

### 1. **Database Schema** (`sql/migration_payments_rework.sql`)

**New Columns Added to `arrears` Table:**
- `previous_arrears` (DECIMAL): Tracks arrears from previous periods
- `triggered_date` (DATETIME): When the arrears was triggered
- `trigger_reason` (ENUM): Reason for triggering ('overdue_7days', 'marked_not_paid', 'penalty')

**Updates to `dues` Table:**
- `arrears_triggered` (TINYINT): Tracks if arrears have been charged
- `arrears_triggered_date` (DATE): When arrears were triggered

**New Indexes:**
- `idx_arrears_lease_triggered`: For better query performance
- `idx_dues_payment_status`: For filtering paid/unpaid dues

**Migration Instructions:**
```bash
# Apply the migration
mysql -u root -p rentflow < sql/migration_payments_rework.sql

# Or manually run the SQL statements in your database client
```

---

### 2. **Admin Payments Page** (`admin/payments.php`)

**Major Changes:**

#### Two Distinct Tables:
1. **Active Payments Table** - Shows all active leases with payment status
2. **Arrears Table** - Shows only leases with outstanding arrears

#### Payment Action Modals:
- **Mark as Paid Modal**: Captures next payment due date and amount
- **Mark as Partial Paid Modal**: Captures partial amount paid, next due date, and amount
- **Mark as Not Paid Modal**: Captures next due date and amount

#### Data Processing:
- Validates lease ID and payment amounts
- Properly handles transaction logic for each payment type
- Automatically creates next due records
- Updates arrears records with proper trigger reasons

#### Payment Status Tracking:
- "Paid" - All dues satisfied
- "Pending" - Payment due but not overdue
- "Overdue" - Payment due date has passed

---

### 3. **Payment Styles** (`public/assets/css/payments.css`)

**New Features:**
- **Tab-based Navigation**: Clean tab interface for payments and arrears
- **Responsive Design**: Works on desktop, tablet, and mobile devices
- **Card-based Layout**: Modern card design for modals and content
- **Color-coded Status**: Visual indicators for payment status
- **Improved Typography**: Better readability with proper font sizing
- **Animation Effects**: Smooth transitions and fade-in animations
- **Form Styling**: Enhanced input fields and form layouts
- **Badge System**: Color-coded badges for status and reasons

**Responsive Breakpoints:**
- Desktop: Full feature set
- Tablet (≤1024px): Optimized spacing
- Mobile (≤768px): Mobile-first table layout with data attributes
- Small Mobile (≤480px): Simplified forms and buttons

---

### 4. **Payment JavaScript** (`public/assets/js/payments.js`)

**Key Functions:**

#### Tab Management:
- `showTab(tabName)` - Switches between payments and arrears tabs
- Tab counts display number of records

#### Modal Management:
- `openModal(modal)` - Opens a modal with animation
- `closeModal(modalId)` - Closes a modal
- `handleOutsideClick(event)` - Closes modal when clicking outside

#### Payment Actions:
- `handlePaymentAction(selectElement, leaseId)` - Routes to appropriate modal
- `openPaidModal(leaseId)` - Opens paid payment modal
- `openPartialModal(leaseId)` - Opens partial payment modal
- `openNotPaidModal(leaseId)` - Opens not paid modal

#### Arrears Management:
- `showArrearsHistory(leaseId)` - Fetches and displays arrears history
- `payArrear(leaseId, dueDate, amount)` - Records arrear payment

#### Utilities:
- `htmlEscape(text)` - Escapes HTML special characters
- `formatCurrency(value)` - Formats numbers as currency
- `formatDate(date)` - Formats dates as YYYY-MM-DD
- `getNextMonthDate()` - Gets next month date

---

### 5. **API Updates**

#### `api/pay_arrear.php`
**Updated:**
- Better error handling with try-catch
- More detailed response messages
- Tracks overpayment scenarios
- Returns full payment details

**Response Format:**
```json
{
  "success": true,
  "message": "...",
  "fully_paid": true|false,
  "amount_paid": 1000,
  "remaining": 500
}
```

#### `api/arrears_history.php`
**Updated:**
- Returns penalties and unpaid dues separately
- Includes current arrears information
- Better error handling
- More flexible response structure

**Response Format:**
```json
{
  "success": true,
  "history": [
    {
      "date": "2026-02-15",
      "amount": 1000,
      "type": "Unpaid Due"
    }
  ],
  "total_penalties": 0,
  "total_unpaid_dues": 1000,
  "current_arrears": {
    "total": 1500,
    "previous": 500,
    "triggered_date": "2026-02-18T10:30:00",
    "trigger_reason": "marked_not_paid"
  }
}
```

#### `api/process_overdue_arrears.php`
**Updated:**
- Changed trigger from 15 days to **7 days** overdue
- Better tracking of trigger reasons
- Prevents duplicate processing
- More detailed logging

---

## Payment Processing Logic

### Mark as Paid
1. Mark current unpaid due as paid
2. Record payment with full amount
3. Create next due if provided
4. No arrears added

### Mark as Partial Paid
1. Record payment with partial amount
2. Calculate remaining as arrears (full amount - partial amount)
3. If remaining > 0, add to arrears with trigger reason "marked_not_paid"
4. If partial amount >= full amount, mark due as paid
5. Create next due if provided

### Mark as Not Paid
1. Add full due amount to arrears with trigger reason "marked_not_paid"
2. Record payment with 0 amount
3. Create next due if provided

---

## Arrears Triggering

Arrears are automatically triggered in two scenarios:

### 1. Overdue (7 Days)
- Payment not made 7+ days after due date
- CRON job runs daily via `/api/process_overdue_arrears.php`
- Trigger reason: `overdue_7days`

### 2. Manually Marked
- Marked as "Not Paid" by admin/treasury
- Marked as "Partial Paid" with remaining balance
- Trigger reason: `marked_not_paid`

---

## User Interface Changes

### Payments Tab
**Columns:**
- Stall
- Tenant (with code)
- Business
- Previous Payment (date + amount)
- Status (Paid/Pending/Overdue)
- Next Payment Date
- Next Amount
- Remarks
- Actions (Message + Dropdown)

**Actions:**
- Message tenant
- Mark as Paid
- Mark as Partial Paid
- Mark as Not Paid

### Arrears Tab
**Columns:**
- Stall
- Tenant (with code)
- Business
- Total Arrears (current)
- Previous Arrears (clickable for history)
- Triggered Date
- Trigger Reason (badge)
- Actions (View History + Message)

**Actions:**
- View arrears history
- Send reminder message
- Pay individual arrear items

---

## How to Use

### For Administrators

#### Recording a Payment:
1. Navigate to Payments & Arrears page (`/admin/payments.php`)
2. Find the tenant in the "Active Payments" table
3. Click the dropdown in the "Actions" column
4. Select "Mark as Paid", "Mark as Partial Paid", or "Mark as Not Paid"
5. Fill in the required fields:
   - Next Payment Due Date
   - Next Payment Amount
   - (For Partial: Amount Paid)
6. Click "Submit"

#### Viewing Arrears History:
1. Go to "Arrears" tab
2. Click on the "Previous Arrears" amount in any row
3. Modal opens showing detailed history
4. Click "Pay" button on any line item to record payment

#### Automatic Arrears Processing:
1. Set up CRON job to run daily:
   ```bash
   0 2 * * * curl https://your-domain.com/rentflow/api/process_overdue_arrears.php
   ```
2. System automatically triggers arrears for 7+ days overdue

---

## Database Schema Updates

### Before Migration
```
arrears (id, lease_id, total_arrears, last_updated)
dues (id, lease_id, due_date, amount_due, paid)
```

### After Migration
```
arrears (
  id, 
  lease_id, 
  total_arrears,
  previous_arrears,       ← NEW
  triggered_date,         ← NEW
  trigger_reason,         ← NEW
  last_updated
)

dues (
  id, 
  lease_id, 
  due_date, 
  amount_due, 
  paid,
  arrears_triggered,      ← NEW
  arrears_triggered_date  ← NEW
)
```

---

## Testing Checklist

- [ ] Migration script runs without errors
- [ ] New columns populate correctly
- [ ] Payments table displays all active leases
- [ ] Arrears table shows only leases with arrears > 0
- [ ] Modal opens correctly for each payment action
- [ ] Payment actions process correctly
- [ ] Next due dates are created properly
- [ ] Arrears history modal displays all items
- [ ] Pay button in arrears history works
- [ ] Status badges display correct color
- [ ] Tab switching works smoothly
- [ ] Responsive design works on mobile
- [ ] API endpoints return correct JSON
- [ ] CRON job processes overdue arrears

---

## Troubleshooting

### Modals Not Opening
**Solution:** Ensure `payments.js` is loaded correctly

### Empty Arrears Tab
**Solution:** Check if `total_arrears > 0` in database

### CSS Not Applying
**Solution:** Clear browser cache and verify `payments.css` path

### API Errors
**Solution:** Check console for error messages using browser DevTools

---

## Future Enhancements

1. **Batch Payment Processing** - Process multiple payments at once
2. **Payment Templates** - Save common payment scenarios
3. **Email Notifications** - Auto-send payment reminders
4. **SMS Alerts** - Text message notifications for overdue payments
5. **Mobile App** - Native mobile app for payment management
6. **Payment Gateway Integration** - Accept online payments
7. **Advanced Reporting** - More detailed payment analytics
8. **Multi-currency Support** - Handle multiple currencies

---

## Support

For issues or questions:
- Check the troubleshooting section above
- Review browser console for error messages
- Check database for data integrity
- Contact development team with specific error logs

---

**End of Documentation**
