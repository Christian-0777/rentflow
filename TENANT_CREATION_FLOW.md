# New Tenant Account Creation and Stall Assignment Flow

## Overview
The RentFlow system has been updated to implement a streamlined tenant onboarding process. Instead of tenants applying for stalls through an application system, administrators now directly create tenant accounts and assign stalls to them immediately.

## Previous Flow (Removed)
1. **Tenant Application Process:**
   - Tenants browsed available stalls via `tenant/stalls.php`
   - Submitted applications with business details and documents via `api/stalls_apply.php`
   - Applications stored in `stall_applications` table

2. **Admin Review Process:**
   - Admins reviewed applications via `admin/applications.php`
   - Approved/rejected applications via `api/approve_application.php`
   - Assigned stalls to approved tenants via `api/assign_stall_to_application.php`

3. **Separate Pages:**
   - `tenant/dashboard.php` - Basic dashboard
   - `tenant/payments.php` - Payment management
   - `admin/applications.php` - Application management

## New Flow (Current)

### 1. Admin Creates Tenant Account
**Location:** `admin/tenants.php` → "Add Tenant" modal

**Process:**
- Admin fills out tenant information (name, email, business details)
- Selects available stall from dropdown
- Sets lease start date and monthly rent
- Submits form to create account and assign stall simultaneously

**Database Actions:**
- Inserts new record into `users` table with `role='tenant'`
- Creates lease record in `leases` table
- Creates first due date in `dues` table (30 days from lease start)
- Initializes arrears record in `arrears` table
- Updates stall status to 'occupied' in `stalls` table
- Sends notification to tenant

**API Endpoint:** `api/add_tenant.php`

### 2. Tenant Access
**Location:** `tenant/home.php` (combined dashboard and payments)

**Features Available:**
- View upcoming payment due date and amount
- View total arrears
- View last payment details
- View payment transaction history
- Access to messages, notifications, and profile

**Navigation:** Simplified navigation without separate stalls, dashboard, or payments pages

## Removed Components

### Files Removed:
- `admin/applications.php` - Application review interface
- `tenant/stalls.php` - Stall browsing and application
- `tenant/dashboard.php` - Separate dashboard page
- `tenant/payments.php` - Separate payments page

### API Endpoints Removed:
- `api/approve_application.php` - Application approval
- `api/assign_stall_to_application.php` - Stall assignment to applications
- `api/get_application_details.php` - Application details retrieval
- `api/stalls_apply.php` - Stall application submission
- `public/api/stalls_apply.php` - Public API wrapper

### UI Elements Removed:
- "Assign Stall to Existing Tenant" section in `admin/stalls.php`
- "View Applications" link in `admin/stalls.php`
- Stall application navigation in tenant interface
- Separate payments navigation in tenant interface

### Database Tables:
- `stall_applications` table remains for historical data but is no longer used

## Benefits of New Flow

1. **Streamlined Process:** Eliminates application review bottleneck
2. **Immediate Assignment:** Tenants get stalls immediately upon account creation
3. **Simplified UI:** Fewer pages and navigation options
4. **Direct Control:** Admins have full control over tenant-stall assignments
5. **Reduced Complexity:** Less code to maintain and fewer potential failure points

## Migration Considerations

- Existing tenants with active leases continue to work normally
- Historical application data preserved in database
- All existing payment and lease functionality remains intact
- Admin can still manage existing tenants via `admin/tenants.php`

## Security and Validation

- All tenant creation requires admin authentication
- Stall availability is validated before assignment
- Lease dates and rent amounts are validated
- Duplicate email prevention maintained
- Proper database transaction handling ensures data consistency

## Future Enhancements

- Bulk tenant creation functionality
- Stall assignment templates
- Automated lease renewal notifications
- Integration with external tenant verification services