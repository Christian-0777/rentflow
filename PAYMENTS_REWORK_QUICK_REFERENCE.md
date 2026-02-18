# Payments System Rework - Quick Reference

## What Changed

### 1. Database
- Added columns to `arrears` table for better tracking
- Migration file: `sql/migration_payments_rework.sql`
- **Action:** Run migration before using new system

### 2. Admin Payments Page
- Replaced single table with **two distinct tables**:
  - **Payments Table**: Shows active leases with payment status
  - **Arrears Table**: Shows leases with outstanding arrears
- Added **tab-based navigation**
- File: `admin/payments.php`

### 3. User Interface
- **New Modals** for each payment action:
  - Mark as Paid: Asks for next payment date & amount
  - Mark as Partial: Asks for partial amount + next payment info
  - Mark as Not Paid: Asks for next payment info
- **Improved styling** with modern design
- **Responsive design** for all devices

### 4. Styles & Scripts
- New CSS file: `public/assets/css/payments.css`
- New JS file: `public/assets/js/payments.js`
- Handles modals, tabs, and forms

### 5. APIs
- Updated: `/api/pay_arrear.php`
- Updated: `/api/arrears_history.php`
- Updated: `/api/process_overdue_arrears.php`
- Changed arrears trigger: **7 days** (was 15 days)

---

## Setup Instructions

### Step 1: Apply Database Migration
```bash
mysql -u root -p rentflow < sql/migration_payments_rework.sql
```

### Step 2: Verify Files Exist
- ✓ `admin/payments.php`
- ✓ `public/assets/css/payments.css`
- ✓ `public/assets/js/payments.js`
- ✓ `api/pay_arrear.php`
- ✓ `api/arrears_history.php`
- ✓ `api/process_overdue_arrears.php`

### Step 3: Test the Payment Page
- Navigate to `/admin/payments.php`
- Check both tabs (Payments & Arrears)
- Test modal functions

### Step 4: Setup CRON Job (Optional)
For automatic arrears processing after 7 days:
```bash
# Add to crontab (daily at 2 AM)
0 2 * * * curl "https://your-domain.com/rentflow/api/process_overdue_arrears.php?token=YOUR_TOKEN"
```

---

## Payment Action Workflows

### Workflow 1: Mark Payment as Paid
```
1. Admin clicks "Mark as Paid"
2. Modal opens asking for:
   - Next Payment Due Date
   - Next Payment Amount
3. Admin fills in and clicks Submit
4. System:
   - Marks current due as paid
   - Records payment
   - Creates next due
```

### Workflow 2: Mark as Partial Paid
```
1. Admin clicks "Mark as Partial Paid"
2. Modal opens asking for:
   - Amount Paid (partial)
   - Next Payment Due Date
   - Next Payment Amount
3. Admin fills in and clicks Submit
4. System:
   - Records partial payment
   - Adds remaining to arrears
   - Creates next due
```

### Workflow 3: Mark as Not Paid
```
1. Admin clicks "Mark as Not Paid"
2. Modal opens asking for:
   - Next Payment Due Date
   - Next Payment Amount
3. Admin fills in and clicks Submit
4. System:
   - Adds full amount to arrears
   - Records payment with $0 amount
   - Creates next due
```

---

## Key Features

✅ **Dual Table Architecture**
- Separate management of payments and arrears
- Clear distinction between active payments and outstanding debts

✅ **Modern Modal Design**
- Clean, professional modals for each action
- Form validation
- Smooth animations

✅ **Comprehensive Arrears Tracking**
- Tracks both automatic (7+ days overdue) and manual triggers
- Stores trigger reasons for audit trail
- Previous arrears history available

✅ **Responsive Design**
- Works on desktop, tablet, and mobile
- Touch-friendly buttons and dropdowns
- Optimized performance

✅ **Better Data Management**
- Prevents duplicate arrears processing
- Automatic next month date calculation
- Proper error handling

---

## Important Notes

⚠️ **Before Using:**
- Run migration script first
- Test in development environment
- Clear browser cache after update

⚠️ **Arrears Trigger:**
- Payments trigger arrears after **7 days** of being overdue (was 15 days)
- CRON job must be set up for automatic processing
- Can also be triggered manually by marking as "Not Paid"

⚠️ **Data Integrity:**
- Do not modify `arrears` table directly
- Always use admin interface for changes
- Check database logs for failed operations

---

## Column Definitions

### Payments Table
| Column | Shows |
|--------|-------|
| Stall | Stall number |
| Tenant | Tenant name with ID code |
| Business | Business name |
| Previous Payment | Last payment date and amount |
| Status | Paid/Pending/Overdue |
| Next Payment | Due date |
| Next Amount | Amount due |
| Remarks | Last payment notes |
| Actions | Message + Mark As dropdown |

### Arrears Table
| Column | Shows |
|--------|-------|
| Stall | Stall number |
| Tenant | Tenant name with ID code |
| Business | Business name |
| Total Arrears | Current outstanding amount |
| Previous Arrears | Historical arrears (clickable) |
| Triggered Date | When arrears started |
| Trigger Reason | Why arrears triggered |
| Actions | View History + Message |

---

## Common Issues & Solutions

### Issue: Modals not appearing
**Solution:** Check if `payments.js` is loading correctly

### Issue: Arrears tab is empty
**Solution:** Check if any leases have `total_arrears > 0`

### Issue: Migration fails
**Solution:** Ensure database backup exists; check MySQL error logs

### Issue: API returns 500 error
**Solution:** Check PHP error logs; verify database connection

---

## Files Summary

| File | Purpose | Type |
|------|---------|------|
| `admin/payments.php` | Main payment page | PHP |
| `public/assets/css/payments.css` | Payment page styles | CSS |
| `public/assets/js/payments.js` | Payment page logic | JS |
| `api/pay_arrear.php` | Pay arrear API | PHP |
| `api/arrears_history.php` | Get history API | PHP |
| `api/process_overdue_arrears.php` | Cron arrears processor | PHP |
| `sql/migration_payments_rework.sql` | Database updates | SQL |

---

## Contact & Support

- **Documentation:** See `PAYMENTS_REWORK_DOCUMENTATION.md`
- **Issues:** Check browser console (F12) for errors
- **Questions:** Review code comments in PHP files

---

**Last Updated:** February 18, 2026  
**Status:** Production Ready ✓
