# RentFlow - Complete Project Documentation

**A Web-Based Rent Management System**

---

## 📌 Table of Contents

1. [Project Overview](#project-overview)
2. [How the Project is Used](#how-the-project-is-used)
3. [User Roles & Access](#user-roles--access)
4. [Page-by-Page Functionality](#page-by-page-functionality)
5. [Features](#features)
6. [Security Implementation](#security-implementation)
7. [API Architecture & Endpoints](#api-architecture--endpoints)
8. [Configuration & Handling](#configuration--handling)
9. [Layout & Design Framework](#layout--design-framework)
10. [Database Structure](#database-structure)

---

## Project Overview

**RentFlow** is a modern web-based Property Management System designed to help property administrators and stall owners efficiently manage:
- **Tenant Management** - Register, profile management, and tenant data tracking
- **Rental Payments** - Payment tracking, arrears management, and penalty calculations
- **Stall Applications** - Tenant applications for rental stalls with admin approval workflow
- **Communications** - Messenger-style chat system between admins and tenants
- **Reporting & Analytics** - Revenue reports, occupancy statistics, and data exports
- **Notifications** - Real-time alerts and updates for both admins and tenants

**Technology Stack:**
- **Backend**: PHP 7.4+ with PDO (Database abstraction)
- **Database**: MySQL 5.7+ / MariaDB
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Server**: Apache (via XAMPP)
- **Email Services**: PHPMailer, SendGrid API
- **Payment Processing**: Integrated payment tracking system

---

## How the Project is Used

### User Workflow

1. **Public Access** → New users visit `/public/index.php` (public landing page)
2. **Registration** → Users register as **Admin** or **Tenant** via `/public/register.php`
3. **Login** → Users authenticate via `/public/login.php` with email and password
4. **2FA Verification** → Two-factor authentication via `/public/verify_2fa.php`
5. **Role-Based Dashboard** → Redirected to role-specific dashboard:
   - Admins → `/admin/dashboard.php`
   - Tenants → `/tenant/dashboard.php`


```
Public Access:
├── /public/index.php (landing page)
├── /public/register.php (user registration)
├── /public/login.php (authentication)
├── /public/verify_2fa.php (two-factor auth)
├── /public/forgot_password.php (password recovery)
└── /public/reset_password.php (password reset)

Authenticated Access (based on role):
├── Admin Portal: /admin/*
├── Tenant Portal: /tenant/*
```

---

## User Roles & Access

### 1. **Admin Role**
- Full access to all system features
- Can manage tenants, payments, stalls, and applications
- Sends and receives messages (messenger interface)
- Generates reports and exports data
- Views system notifications and alerts

**Admin Pages:**
- `/admin/dashboard.php` - Analytics and quick overview
- `/admin/tenants.php` - Manage all tenants
- `/admin/payments.php` - Track payments and arrears
- `/admin/applications.php` - Review stall applications
- `/admin/stalls.php` - Manage available stalls
- `/admin/messages.php` - Chat with tenants (messenger-style)
- `/admin/notifications.php` - System notifications
- `/admin/reports.php` - Advanced analytics and exports
- `/admin/account.php` - Admin account settings
- `/admin/contact.php` - Public contact form submissions

### 2. **Tenant Role**
- Limited access to personal account features
- Can view payment status and history
- Apply for rental stalls
- Communicate with admin via messages
- View notifications from admin
- Manage personal profile and account settings

**Tenant Pages:**
- `/tenant/dashboard.php` - Personal overview (next payment, arrears, latest notification)
- `/tenant/payments.php` - Payment history and upcoming dues
- `/tenant/stalls.php` - Apply for available stalls
- `/tenant/notifications.php` - Messages and notifications from admin
- `/tenant/profile.php` - Personal profile with chat modal
- `/tenant/account.php` - Account settings
- `/tenant/support.php` - Support/help section



---

## Page-by-Page Functionality

### **Admin Pages**

#### `/admin/dashboard.php`
**Purpose**: Main admin dashboard with key metrics and quick actions

**Data Displayed:**
- **Stall Availability** - Summary by type (Wet, Dry, Apparel)
  - Available, Occupied, Maintenance counts
- **Upcoming Payments** - Next 10 payments due (with 10-day look-ahead)
- **Revenue Highlights** - Highest, lowest, and average payments (last 90 days)
- **Recent Payments** - Last 10 payments with on-time/late indicators

**Key Features:**
- Color-coded table display
- Real-time data from database
- Quick navigation to detailed pages
- Responsive design for desktop/mobile

---

#### `/admin/tenants.php`
**Purpose**: Comprehensive tenant management

**Functionality:**
- Display all active tenants with details:
  - Name, email, phone, business name
  - Current stall assignment
  - Lease start date and status
- Search and filter capabilities
- Bulk actions (delete, export, message)
- Row-level action dropdown providing:
  - Terminate lease (via confirmation modal)
  - Transfer tenant to another available stall
  - Update tenant documents (ID, permit, logo, signature)
  - Send message (redirects to messages page)
- View tenant profile/details in modal
- Quick links to tenant payments and messages

**Database Queries:**
- Joins: `users`, `leases`, `stalls`
- Filters by active/inactive status
- Real-time search across multiple fields

---

#### `/admin/payments.php`
**Purpose**: Payment management with two-tab interface

**Tab 1: Payments Tab**
Displays all leases with payment tracking:

| Data | Details |
|------|---------|
| Stall | Rental stall number |
| Tenant | Tenant name with profile link |
| Business | Business name |
| Previous Payment | Last payment date & amount |
| Previous Status | Full/Partial/Not Paid |
| Next Payment | Due date & amount |
| Next Status | Overdue/Pending/Paid (color-coded) |
| Actions | Message button + dropdown |

**Action Dropdown:**
- ✅ **Mark as Paid** - Modal to record full payment
- 📊 **Mark as Partial Paid** - Modal for partial payment with arrear calculation
- ❌ **Mark as Not Paid** - Trigger arrear calculation with penalty

**Tab 2: Arrears Tab**
Displays only leases with outstanding balances:

| Data | Details |
|------|---------|
| Stall | Rental stall number |
| Tenant | Tenant name |
| Business | Business name |
| Previous Arrears | Sum of unpaid amounts (clickable) |
| Current Penalties | 2% penalty from current period |
| Total Arrears | Total outstanding balance |
| Actions | Message button |

**Action Modals:**

1. **Mark as Paid Modal**
   - Next Payment Date (date picker)
   - Next Payment Amount (currency input)
   - Submit to create payment record

2. **Mark as Partial Paid Modal**
   - Amount Paid (currency input)
   - Next Payment Date (date picker)
   - Next Payment Amount (currency input)
   - Auto-calculates remaining as arrear

---

#### `/admin/applications.php`
**Purpose**: Stall application management with approval workflow

**Features:**
- Filter buttons: All, Pending, Approved, Rejected
- Application cards displaying:
  - Status badge (color-coded)
  - Business name and type
  - Tenant information
  - Submission date
  - Application ID

**Workflow:**
1. Click "View" on pending application
2. View modal shows all details:
   - Tenant and business information
   - Business logo, permit, ID, signature previews
3. Click "Approve" to proceed to stall assignment
4. Stall Assignment Form:
   - Select available stall (filtered by type)
   - Enter lease start date (calendar picker)
   - Enter monthly rent amount
5. Click "Assign Stall" to complete:
   - Lease record created
   - First due date generated (30 days from lease start)
   - Arrears record initialized
   - Stall status updated to "occupied"
   - Tenant notified

---

#### `/admin/stalls.php`
**Purpose**: Stall inventory and availability management

**Functionality:**
- Display all stalls with:
  - Stall number and type (Wet, Dry, Apparel)
  - Current status (Available, Occupied, Maintenance)
  - Location and features
  - Current tenant (if occupied)
- Add new stalls
- Edit stall details
- Change stall status
- Delete stalls (if not occupied)

---

#### `/admin/messages.php`
**Purpose**: Messenger-style chat interface with tenants

**Design:**
Inspired by Facebook Messenger with two-panel layout:

**Left Panel:**
- List of all conversations with tenants
- Real-time search conversations
- Unread message badges
- Last message preview
- Click tenant to open conversation

**Center Panel:**
- Full message thread with tenant
- Color-coded messages (admin/tenant)
- Message timestamps
- Read receipts if applicable
- Auto-scroll to latest message

**Features:**
- Message polling (auto-refresh)
- Send new messages to selected tenant
- Message history (last 50 by default)
- Responsive design
- Email notification option for tenants

---

#### `/admin/notifications.php`
**Purpose**: System notifications and alerts dashboard

**Displays:**
- New tenant registrations
- Application submissions
- Payment notifications
- System alerts
- Message reminders

---

#### `/admin/reports.php`
**Purpose**: Advanced analytics and export functionality

**Sections:**

1. **New Tenants** (Last 30 Days)
   - Table: Name, Business, Stall, Lease Start Date
   - Date-filtered query

2. **Stall Availability Analytics**
   - Dynamic charts: Pie, Bar, Line (toggle-able)
   - Data breakdown: Wet/Dry/Apparel × Occupied/Available/Maintenance
   - Percentage calculations

3. **Revenue Analytics**
   - **Monthly Revenue Chart** - Bar chart (12-month history)
   - **Yearly Revenue Chart** - Bar chart (all years)
   - **Revenue Cards** - Total revenue, collected, balances

4. **Export Options**
   - **Full Page Export:**
     - To PDF (A4 portrait, high resolution)
     - To Word Document (.doc format)
     - To Google Docs (new window copy-paste)
   - **Individual Chart Export:**
     - Chart to PNG image
     - Chart to PDF document
   - **Data Exports:**
     - CSV format
     - Excel format

---

#### `/admin/account.php`
**Purpose**: Admin account settings and profile management

**Features:**
- Update email address
- Change password
- Update profile information
- View account activity
- Security settings

---

#### `/admin/contact.php`
**Purpose**: Public contact submissions management

**Features:**
- Display contact form submissions
- Send replies to submitted emails
- Mark as resolved/archived
- Search and filter submissions

---

### **Tenant Pages**

#### `/tenant/dashboard.php`
**Purpose**: Personal tenant overview and quick links

**Displays:**
- Welcome message with first name
- **Latest Notification Block** - Most recent admin message/alert
- **Payment Overview Cards:**
  - Next Payment (amount and due date)
  - Last Payment (date and amount)
  - Total Arrears (outstanding balance)
- **Quick Links** - Navigation to key sections
- **Notification Alert** - If admin has sent new messages

---

#### `/tenant/payments.php`
**Purpose**: Detailed payment history and upcoming dues

**Tabs/Sections:**
- **Upcoming Dues** - Next payments with status
- **Payment History** - Full payment record with dates, amounts, methods
- **Arrears History** - Tracking of penalties and overdue amounts
- **Download Options** - Receipt downloads, payment records export

---

#### `/tenant/stalls.php`
**Purpose**: Apply for rental stalls

**Workflow:**
1. Click "Apply Now" button on available stall type
2. Application modal opens with form:
   - **Stall Type** - Dropdown (Wet, Dry, Apparel)
   - **Business Name** - Text input
   - **Business Logo** - Optional image upload (PNG, JPG, GIF, WebP)
   - **Business Description** - Textarea
   - **Business Permit** - Required file upload (PDF, doc)
   - **Valid ID** - Required file upload (PDF, doc)
   - **Digital Signature** - Required file upload (PDF, image)
3. Submit application
4. Application status: PENDING
5. Admin reviews and approves/rejects
6. Upon approval, stall is assigned and lease is created

**Features:**
- Real-time validation
- File type/size restrictions
- Application ID generated
- Confirmation notification
- Upload storage: `/uploads/applications/`

---

#### `/tenant/notifications.php`
**Purpose**: Inbox of messages and notifications

**Displays:**
- All notifications from admin
- Date, sender name, message content
- Message status (read/unread)
- Original message links
- Delete/archive options

---

#### `/tenant/profile.php`
**Purpose**: Personal profile and chat

**Sections:**
- Profile information (name, email, phone, business details)
- Edit profile functionality
- **Chat Modal** - "Chat with Admin" button at bottom
  - Optional email field (for notifications)
  - Message textarea
  - Send/Cancel buttons
  - Messages sent here appear in admin messages interface

---

#### `/tenant/account.php`
**Purpose**: Account settings and security

**Features:**
- Update email address
- Change password
- Update profile information
- Update notification preferences
- View account activity

---

#### `/tenant/support.php`
**Purpose**: Help desk and support information

**Content:**
- FAQ section
- Support contact information
- Troubleshooting guides
- Links to relevant resources

---

### **Public Pages**

#### `/public/index.php`
**Purpose**: Landing page and navigation hub

**Features:**
- Welcome message
- Project overview
- Login/Register buttons
- Feature highlights
- Responsive design for mobile/desktop

---

#### `/public/register.php`
**Purpose**: New user registration

**Fields:**
- First Name (required)
- Last Name (required)
- Email (required, validated)
- Phone (optional)
- Role Selection (Admin or Tenant)
- Business Name (required for Tenant)
- Password (required, secure hash with bcrypt)
- Confirm Password (verification)
- Terms acceptance checkbox

**Validation:**
- Email uniqueness check
- Password strength requirements
- Input sanitization
- Email format validation

---

#### `/public/login.php`
**Purpose**: User authentication

**Process:**
1. Email and password input
2. Password verification against bcrypt hash
3. Session creation if valid
4. Redirect to 2FA verification or dashboard
5. Failed login tracking and security alerts

---

#### `/public/verify_2fa.php`
**Purpose**: Two-factor authentication

**Process:**
1. Code sent to email or SMS (configurable)
2. User enters 6-digit verification code
3. Code validation against stored code
4. Session finalization upon successful verification
5. Redirect to relevant dashboard

---

#### `/public/forgot_password.php`
**Purpose**: Password recovery initiation

**Process:**
1. User enters email address
2. Verification code sent to email
3. Redirect to reset password page

---

#### `/public/reset_password.php`
**Purpose**: Complete password reset

**Fields:**
- Verification code (from email)
- New password
- Confirm password

**Validation:**
- Code expires after time limit
- Password strength check
- Secure hash update in database

---

#### `/public/terms_accept.php`
**Purpose**: Terms and conditions acceptance

**Content:**
- Legal terms
- Privacy policy
- Usage agreement
- Checkbox acceptance

---

## Features

### 1. **User Management**
- ✅ User registration with role assignment (Admin, Tenant)
- ✅ Email-based authentication
- ✅ Two-factor authentication (2FA)
- ✅ Password hashing with bcrypt (cost: 12)
- ✅ Password reset via email
- ✅ Profile management for each user
- ✅ Session management with security

### 2. **Tenant & Stall Management**
- ✅ Stall inventory with types (Wet, Dry, Apparel)
- ✅ Stall status tracking (Available, Occupied, Maintenance)
- ✅ Tenant-to-stall lease assignments
- ✅ Lease start date tracking
- ✅ Lease history and archival
- ✅ Tenant application workflow with admin approval
- ✅ Lease termination from admin panel (stalls automatically freed)
- ✅ Tenant transfer between stalls with availability check
- ✅ Upload or replace tenant documents (ID, permit, logo, signature) via admin interface

### 3. **Stall Application System**
- ✅ Tenant application submission with required documents:
  - Business logo, permit, valid ID, digital signature
- ✅ Admin approval/rejection workflow
- ✅ Stall assignment post-approval with lease creation
- ✅ Application status tracking (Pending, Approved, Rejected)
- ✅ Application ID generation
- ✅ Document storage and retrieval

### 4. **Payment Management**
- ✅ Payment tracking per lease
- ✅ Due date management (recurring monthly)
- ✅ Full payment recording
- ✅ Partial payment recording with arrear calculation
- ✅ Payment method tracking (Cash, Check, Bank Transfer, etc.)
- ✅ Payment history by tenant
- ✅ Receipt generation and download

### 5. **Arrears Management**
- ✅ Automatic arrear calculation from unpaid dues
- ✅ Arrear entry tracking with source (unpaid, partial, overdue)
- ✅ Penalty calculation (2% per month configurable)
- ✅ Arrear payment processing
- ✅ Arrear history and reporting
- ✅ Overdue tracking (7+ day threshold)

### 6. **Messaging System**
- ✅ Messenger-style chat interface (Facebook Messenger inspired)
- ✅ Conversation list with unread badges
- ✅ Real-time search across conversations
- ✅ Message history and organization
- ✅ Admin-to-tenant communication
- ✅ Tenant-to-admin chat modal on profile
- ✅ Optional email notifications for messages
- ✅ Read receipts and status tracking

### 7. **Notification System**
- ✅ Real-time notifications dashboard
- ✅ Email notifications for key events
- ✅ Notification types: Chat, Payment, Application, System Alert
- ✅ Unread notification tracking
- ✅ Notification history
- ✅ Notification preferences per user

### 8. **Reporting & Analytics**
- ✅ Revenue reports (monthly and yearly)
- ✅ Stall occupancy analytics with charts (Pie, Bar, Line)
- ✅ New tenant reports (last 30 days)
- ✅ Arrears tracking reports
- ✅ Multiple export formats:
  - CSV (spreadsheet)
  - Excel (.xlsx)
  - PDF (formatted document)
  - Word (.doc)
  - PNG (chart images)
- ✅ Full page export capability
- ✅ Chart type switching and customization

### 9. **Data Export**
- ✅ Payment records export (CSV, Excel, PDF)
- ✅ Tenant list export
- ✅ Report generation and download
- ✅ Chart export as images (PNG)
- ✅ Bulk operations

### 10. **Administrative Functions**
- ✅ Tenant management (view, edit, delete)
- ✅ Stall management (create, edit, manage status)
- ✅ Application review and approval
- ✅ Message responses and communication
- ✅ Payment tracking and adjustment
- ✅ Report generation and analytics
- ✅ System notifications management

### 11. **Responsive Design**
- ✅ Mobile-friendly layout
- ✅ Desktop optimization
- ✅ Tablet support
- ✅ Hamburger menu for mobile
- ✅ Touch-optimized buttons and inputs
- ✅ Responsive tables and forms

---

## Security Implementation

### 1. **Authentication Security**

#### Password Security
```php
// Bcrypt hashing with cost factor 12 (strong encryption)
password_hash($password, PASSWORD_BCRYPT, ['cost' => 12])
password_verify($input_password, $stored_hash)
```
- Strong bcrypt algorithm prevents brute force attacks
- Cost factor 12 = computational delay of ~0.5 seconds per verification
- One-way hashing ensures passwords never stored in plain text

#### Session Security
```php
session_set_cookie_params([
    'lifetime' => 3600,        // 1-hour session timeout
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => true,          // HTTPS only in production
    'httponly' => true,        // Not accessible via JavaScript
    'samesite' => 'Strict'     // CSRF protection
]);
```
- HttpOnly flag prevents XSS attacks stealing session cookies
- Secure flag ensures HTTPS-only transmission
- SameSite Strict prevents CSRF attacks
- Auto-regeneration of session IDs on login

#### Two-Factor Authentication (2FA)
- Code sent to registered email or SMS
- Code expires after time limit (configurable)
- Prevents unauthorized access even with password compromise
- Verification required post-login

---

### 2. **Database Security**

#### SQL Injection Prevention
```php
// ✅ SECURE: Parameterized queries with placeholders
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = ?");
$stmt->execute([$email, 'active']);

// ❌ INSECURE: String concatenation (vulnerable)
$query = "SELECT * FROM users WHERE email = '" . $email . "'";
```

**Implementation:**
- PDO prepared statements with parameter binding
- `PDO::ATTR_EMULATE_PREPARES => false` (native prepared statements)
- Never concatenate user input into queries
- All database operations via `DatabaseSecurity::executeQuery()`

#### Database Connection Security
```php
PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
PDO::ATTR_EMULATE_PREPARES => false,  // Use native prepared statements
PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
PDO::ATTR_PERSISTENT => false,        // Disable persistent connections
PDO::ATTR_TIMEOUT => 5                // Connection timeout
```

#### Strict SQL Mode
```sql
SET SESSION sql_mode='STRICT_TRANS_TABLES,NO_ZERO_DATE,
               NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO';
```
- Prevents partial updates and data inconsistencies
- Requires explicit NULL handling

---

### 3. **Input Validation & Sanitization**

#### Input Sanitization
```php
// Sanitize output to prevent XSS
echo htmlspecialchars($user_input, ENT_QUOTES, 'UTF-8');

// Sanitize input for storage
$clean_input = DatabaseSecurity::sanitizeInput($user_input);
```

#### Email Validation
```php
filter_var($email, FILTER_VALIDATE_EMAIL) !== false
```
- Server-side validation for all emails
- Format verification before database insertion
- Prevents storage of malformed emails

#### File Upload Security
- File type validation (whitelist allowed extensions)
- File size limits enforced
- Filename randomization to prevent path traversal
- Storage outside web root recommended
- Virus scanning recommended for production

#### Form Validation
- Required field checks
- Type validation (email, number, date, etc.)
- Length limits (min/max)
- Format validation (phone, postal code, etc.)
- Client-side AND server-side validation

---

### 4. **Cross-Site Security**

#### XSS (Cross-Site Scripting) Protection
```php
// Output encoding
htmlspecialchars($data, ENT_QUOTES, 'UTF-8')

// Content Security Policy Header
header("Content-Security-Policy: default-src 'self'; 
       script-src 'self' 'unsafe-inline'; 
       style-src 'self' 'unsafe-inline'");
```

#### CSRF (Cross-Site Request Forgery) Protection
- SameSite cookie attribute set to Strict
- Sessions tied to specific user
- Token-based verification for state-changing requests
- Referrer policy: strict-origin-when-cross-origin

---

### 5. **HTTP Security Headers**

```php
// Prevent clickjacking attacks
X-Frame-Options: SAMEORIGIN

// Prevent MIME type sniffing
X-Content-Type-Options: nosniff

// Enable XSS protection
X-XSS-Protection: 1; mode=block

// Referrer policy
Referrer-Policy: strict-origin-when-cross-origin
```

---

### 6. **Access Control**

#### Role-Based Access Control (RBAC)
```php
function require_role($role) {
    if ($_SESSION['user']['role'] !== $role) {
        http_response_code(403);
        header('Location: /admin/login.php');
        exit;
    }
}
```

**Implemented Roles:**
- **Admin**: Full system access
- **Tenant**: Limited personal access

#### AJAX Request Verification
```php
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    // Valid AJAX request
}
```

---

### 7. **Data Protection**

#### Sensitive Data Handling
- Passwords hashed with bcrypt (never stored plain)
- Credit card data NOT stored (payments processed externally)
- Personal information encrypted in transit (HTTPS)
- Audit logs for sensitive operations
- Data backup and recovery procedures

#### Error Logging
```php
// Secure error logging without exposing details
error_log('Database connection failed: ' . $e->getMessage());
// User never sees actual error details
die('Database connection failed. Please try again later.');
```

#### Database Encryption
- Laravel encryption for sensitive fields recommended
- SSL/TLS for data in transit
- Encrypted database backups

---

### 8. **Security Checklist Implemented**

✅ SQL Injection Prevention (parameterized queries)
✅ XSS Prevention (output encoding)
✅ CSRF Protection (SameSite cookies)
✅ Authentication (password hashing + 2FA)
✅ Authorization (role-based access control)
✅ Session Security (HttpOnly, Secure, SameSite)
✅ Input Validation (type/format checking)
✅ File Upload Security (type/size validation)
✅ Error Handling (no detailed error exposure)
✅ Secure Headers (X-Frame-Options, CSP, etc.)
✅ Email Verification (2FA codes)
✅ Password Reset Security (token expiration)
✅ Activity Logging (audit trails)
✅ HTTPS / SSL (recommended for production)

---

## API Architecture & Endpoints

### API Structure

All API endpoints are located in `/api/` directory and follow RESTful principles:
- **POST** requests for data creation/modification
- **GET** requests for data retrieval
- **JSON** responses with success/error indicators
- Session-based authentication
- Role/permission validation

---

### API Endpoints

#### **Authentication APIs**

##### `POST /api/send_message.php`
**Purpose:** Send message from tenant to admin or admin reply to tenant

**Parameters:**
```php
receiver_id    (int, required)    - User ID of recipient
message        (string, required) - Message content
sender_email   (string, optional) - Tenant email (for notifications)
from_admin     (int, optional)    - Flag: admin is sending
from_tenant    (int, optional)    - Flag: tenant is sending
```

**Response:**
```json
{
  "success": true,
  "message_id": 123,
  "message": "Message sent successfully"
}
```

**Processing:**
1. Insert message into `messages` table
2. Create notification entry in `notifications` table
3. Send email notification if applicable
4. Return message ID and confirmation

---

##### `GET /api/get_messages.php`
**Purpose:** Fetch messages in conversation with specific user

**Parameters:**
```php
peer    (int, required) - User ID to get messages with
limit   (int, optional) - Message count limit (default: 50)
```

**Response:**
```json
{
  "success": true,
  "messages": [
    {
      "id": 1,
      "sender_id": 5,
      "receiver_id": 1,
      "message": "Hello admin",
      "created_at": "2026-02-19 10:30:00",
      "first_name": "John",
      "last_name": "Doe"
    }
  ],
  "count": 1
}
```

---

#### **Payment APIs**

##### `POST /api/pay_arrear.php`
**Purpose:** Record arrear payment from tenant

**Parameters:**
```php
lease_id       (int, required) - Lease ID
amount         (decimal, required) - Payment amount
payment_method (string, required) - Payment method
```

**Processing:**
1. Deduct from total arrears
2. Create payment record
3. Update arrear status
4. Generate receipt

---

##### `GET /api/arrears_history.php`
**Purpose:** Get arrear history for a lease

**Parameters:**
```php
lease_id (int, required) - Lease ID
```

**Response:**
```json
{
  "success": true,
  "arrears": [
    {
      "id": 1,
      "source": "unpaid_due",
      "amount": 5000,
      "created_on": "2026-01-15",
      "is_paid": 0
    }
  ]
}
```

---

##### `GET /api/payments_record.php`
**Purpose:** Get payment records for reporting

**Parameters:**
```php
filters (array) - Optional filters: date_range, lease_id, status
format  (string, optional) - csv, excel, pdf
```

**Response:**
```json
{
  "success": true,
  "records": [...],
  "total": 50000
}
```

---

#### **Application APIs**

##### `POST /api/stalls_apply.php`
**Purpose:** Submit stall application as tenant

**Parameters (FormData):**
```php
stall_type              (string, required) - Wet, Dry, or Apparel
business_name           (string, required) - Business name
business_description    (string, required) - Business description
business_license        (file, required)   - Business license PDF
valid_id                (file, required)   - Valid ID PDF/Image
digital_signature       (file, required)   - Signature image
business_logo           (file, optional)   - Logo image
```

**Processing:**
1. Validate all required fields
2. Check file types and sizes
3. Store files with unique names in `/uploads/applications/`
4. Insert application record with status PENDING
5. Create notification for admin
6. Generate application ID

**Response:**
```json
{
  "success": true,
  "application_id": "APP-2026-001234",
  "message": "Application submitted successfully"
}
```

---

##### `POST /api/approve_application.php`
**Purpose:** Admin approval of stall application

**Parameters:**
```php
application_id (int, required) - Application ID to approve
```

**Processing:**
1. Update application status to APPROVED
2. Prepare for stall assignment
3. Notify applicant of approval
4. Await stall assignment

**Response:**
```json
{
  "success": true,
  "message": "Application approved"
}
```

---

##### `POST /api/assign_stall_to_application.php`
**Purpose:** Assign stall to approved application and create lease

**Parameters:**
```php
application_id   (int, required)   - Approved application ID
stall_id         (int, required)   - Stall to assign
lease_start_date (date, required)  - When lease begins (YYYY-MM-DD)
monthly_rent     (decimal, required) - Monthly rent amount
```

**Processing:**
1. Validate stall is available
2. Create lease record
3. Calculate first due date (30 days from lease_start_date)
4. Create initial arrears record
5. Update stall status to "occupied"
6. Notify tenant of assignment
7. Return lease and stall details

**Response:**
```json
{
  "success": true,
  "lease_id": 42,
  "stall_id": 5,
  "lease_start_date": "2026-03-01",
  "message": "Stall assigned successfully"
}
```

---

#### **Tenant APIs**

##### `GET /api/get_application_details.php`
**Purpose:** Get detailed info about stall application

**Parameters:**
```php
application_id (int, required) - Application ID
```

**Response:**
```json
{
  "success": true,
  "application": {
    "id": 123,
    "tenant_id": 45,
    "stall_type": "Wet",
    "business_name": "Fresh Produce",
    "business_description": "Vegetable stall",
    "business_logo_path": "/uploads/applications/logo_123.png",
    "business_permit_path": "/uploads/applications/permit_123.pdf",
    "valid_id_path": "/uploads/applications/id_123.pdf",
    "digital_signature_path": "/uploads/applications/sig_123.png",
    "status": "APPROVED",
    "created_at": "2026-02-15"
  }
}
```

---

#### **Reporting APIs**

##### `GET /api/chart_data.php`
**Purpose:** Get chart data for reports dashboard

**Parameters:**
```php
chart_type (string, required) - stall_availability, revenue_monthly, etc
```

**Response:**
```json
{
  "success": true,
  "labels": ["Wet", "Dry", "Apparel"],
  "data": [25, 18, 12],
  "colors": ["#FF6384", "#36A2EB", "#FFCE56"]
}
```

---

##### `GET /api/export_csv.php`
**Purpose:** Export report data as CSV

**Parameters:**
```php
report_type (string, required) - payments, tenants, applications
```

**Response:** CSV file download

---

##### `GET /api/export_excel.php`
**Purpose:** Export data as Excel spreadsheet

**Parameters:**
```php
report_type (string, required) - payments, tenants, applications
```

**Response:** Excel (.xlsx) file download

---

##### `GET /api/export_pdf.php`
**Purpose:** Export data or charts as PDF

**Parameters:**
```php
content_type (string, required) - chart, report, receipt
chart_id     (string, optional)  - Chart element ID if chart
```

**Response:** PDF file download

---

#### **Miscellaneous APIs**

##### `POST /api/delete_tenant.php`
**Purpose:** Delete tenant (admin only)

**Parameters:**
```php
tenant_id (int, required) - Tenant user ID
```

**Processing:**
1. Verify tenant has no active leases
2. Archive tenant record
3. Delete associated data if allowed
4. Log deletion action

---

##### `GET /api/receipts.php`
**Purpose:** Generate and download payment receipt

**Parameters:**
```php
payment_id (int, required) - Payment record ID
```

**Response:** PDF receipt file

---

##### `POST /api/penalties_cron.php`
**Purpose:** Automated task: Apply monthly penalties to arrears

**Trigger:** Scheduled via cron job (monthly)

**Processing:**
1. Find all active leases with arrears
2. Calculate penalty (2% of arrear amount)
3. Insert penalty entry
4. Update total_arrears
5. Notify tenants
6. Log penalty application

---

### API Response Format

All API endpoints return JSON with consistent structure:

**Success Response:**
```json
{
  "success": true,
  "data": {...},
  "message": "Operation completed successfully"
}
```

**Error Response:**
```json
{
  "success": false,
  "error": "Descriptive error message",
  "code": 400
}
```

**HTTP Status Codes:**
- `200 OK` - Successful request
- `400 Bad Request` - Invalid parameters
- `401 Unauthorized` - Not authenticated
- `403 Forbidden` - Insufficient permissions
- `404 Not Found` - Resource not found
- `500 Internal Server Error` - Server error

---

## Configuration & Handling

### Configuration Files

#### `/config/env.php`
**Purpose:** Load environment variables from `.env` file

**Function:**
```php
env($key, $default = null) - Get environment variable value
```

**Usage:**
```php
$database_host = env('DB_HOST', 'localhost');
$app_env = env('APP_ENV', 'development');
```

**Supported Variables:**
```
DB_HOST=localhost
DB_PORT=3306
DB_NAME=rentflow
DB_USER=rentflow_team
DB_PASS=rentflow_3006
DB_CHARSET=utf8mb4

MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_FROM=no-reply@rentflow.local
MAIL_FROM_NAME=RentFlow Team

APP_ENV=development
APP_DEBUG=true
APP_NAME=RentFlow

PENALTY_RATE=0.02
2FA_EXPIRY=600
```

---

#### `/config/db.php`
**Purpose:** Database connection initialization with security

**Features:**
- PDO connection with MySQL
- Error handling and logging
- Security configuration:
  - Prepared statement support
  - UTF-8mb4 charset
  - Connection timeout
  - Strict SQL mode
- Connection validation

**Usage:**
```php
require_once __DIR__ . '/db.php';
$result = $pdo->query("SELECT * FROM users");
```

**Error Handling:**
- Connection failures logged securely
- User-friendly error messages
- No database details exposed

---

#### `/config/auth.php`
**Purpose:** Session management and role-based access control

**Key Functions:**

1. **Session Initialization:**
```php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
```

2. **Role Authorization:**
```php
require_role('admin');  // Single role check
// for multiple roles use: require_role(['admin','tenant']);
```

**Behavior:**
- Checks if user is logged in
- Validates user role against required role(s)
- Redirects to login if unauthorized
- Returns JSON error for AJAX requests

3. **Admin Login:**
```php
admin_login($pdo, $role, $code1, $code2, $code3)
```
- Special authentication using 3-part codes
- Validates codes against `auth_codes` table
- Creates session if valid

---

#### `/config/security.php`
**Purpose:** Security implementations and utilities

**Classes & Functions:**

1. **DatabaseSecurity Class**

```php
// Prepare safe queries
DatabaseSecurity::prepareQuery($pdo, $query)

// Execute with parameters
DatabaseSecurity::executeQuery($pdo, $query, $params)

// Fetch single row
DatabaseSecurity::fetchOne($pdo, $query, $params)

// Fetch multiple rows
DatabaseSecurity::fetchAll($pdo, $query, $params)

// Password operations
DatabaseSecurity::hashPassword($password)
DatabaseSecurity::verifyPassword($input, $hash)

// Input sanitization
DatabaseSecurity::sanitizeInput($input)

// Email validation
DatabaseSecurity::isValidEmail($email)
```

2. **Security Headers:**
```php
setSecurityHeaders()  // Set security-related HTTP headers
```

3. **Session Security:**
```php
configureSessionSecurity()  // Configure secure session parameters
```

---

#### `/config/mailer.php`
**Purpose:** Email sending configuration

**Email Providers Supported:**
- PHPMailer (Gmail SMTP, custom SMTP)
- SendGrid API

**Configuration:**
```php
use PHPMailer\PHPMailer\PHPMailer;

$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host = env('MAIL_HOST');
$mail->Port = env('MAIL_PORT');
$mail->Username = env('MAIL_USERNAME');
$mail->Password = env('MAIL_PASSWORD');
```

**Usage:**
```php
// Set email details
$mail->setFrom(env('MAIL_FROM'), env('MAIL_FROM_NAME'));
$mail->addAddress($recipient_email);
$mail->Subject = "Your Subject";
$mail->Body = "<h1>HTML Content</h1>";
$mail->isHTML(true);

// Send
if ($mail->send()) {
    // Success
} else {
    // Failed
}
```

**Email Types Sent:**
- Registration confirmation
- Password reset link
- 2FA verification code
- Application notification
- Payment confirmation
- Arrear notifications
- Message notifications

---

#### `/config/constants.php`
**Purpose:** Application-wide constants

**Typical Constants:**
```php
define('APP_NAME', 'RentFlow');
define('APP_VERSION', '1.4.0');
define('TIMEZONE', 'Asia/Manila');

// Stall types
define('STALL_TYPES', ['Wet', 'Dry', 'Apparel']);

// Payment methods
define('PAYMENT_METHODS', ['Cash', 'Check', 'Bank Transfer', 'Credit Card']);

// Application statuses
define('APP_STATUSES', ['PENDING', 'APPROVED', 'REJECTED']);

// Penalty rate
define('PENALTY_RATE', 0.02);  // 2% monthly
```

---

### Environment Setup

#### `.env` File
Located in project root, contains all environment variables.

**Example Content:**
```env
# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_NAME=rentflow
DB_USER=rentflow_team
DB_PASS=rentflow_3006
DB_CHARSET=utf8mb4

# Email Configuration (Gmail Example)
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=app-specific-password
MAIL_FROM=no-reply@rentflow.local
MAIL_FROM_NAME=RentFlow Support

# SendGrid API (Alternative)
SENDGRID_API_KEY=SG.xxx...

# Application Settings
APP_ENV=development
APP_DEBUG=true
APP_NAME=RentFlow
APP_URL=http://localhost/rentflow/

# Feature Flags
2FA_ENABLED=true
2FA_EXPIRY=600

# Penalty Configuration
PENALTY_RATE=0.02
PENALTY_CHECK_DAY=1
```

**Security Note:** `.env` file should:
- NOT be committed to version control
- Have restrictive file permissions (600)
- Be kept secure with production credentials
- Never be accessible via web

---

### Database Configuration

#### Connection Pool
- Single persistent connection recommended
- Connection timeout: 5 seconds
- Automatic reconnection on timeout
- Connection validation before queries

#### Transaction Handling
- Supported for multi-step operations
- Automatic rollback on error
- Ensures data consistency

```php
try {
    $pdo->beginTransaction();
    // Multiple queries
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
}
```

---

### Error Handling & Logging

#### Error Configuration
- Production: Errors logged, no display
- Development: Errors logged and displayed
- All errors go to `/logs/` or system log

#### Error Responses
- User-friendly messages shown
- Detailed errors logged for admin
- No sensitive information exposed

---

## Layout & Design Framework

### Design Philosophy

RentFlow uses a **custom CSS framework** with:
- Clean, minimal design
- Material Design icons for consistency
- Responsive mobile-first approach
- Color-coded status indicators
- Professional aesthetic for business use

---

### CSS Framework Components

#### Custom Layout System (`layout.css`)

**Base Styling:**
- Font: Calibri (regular text), Arial Black (headings)
- Color Scheme: Professional blue (#0B3C5D header)
- Spacing: Consistent padding/margins
- Responsive breakpoints: 768px (mobile/tablet), 1024px (desktop)

**Component Structure:**
```css
.header              /* Fixed navigation header */
.navigation          /* Horizontal navigation bar */
.content             /* Main content area */
.table-section       /* Data table containers */
.table               /* Data table styling */
.modal               /* Modal dialogs */
.form-group          /* Form input groups */
.button              /* Button styling */
```

---

#### Header & Navigation (`layout.css`)

**Fixed Header:**
```css
.header {
  position: fixed;
  top: 0;
  background: #0B3C5D;  /* Professional blue */
  z-index: 1000;
  display: flex;
  justify-content: space-between;
}
```

**Features:**
- RentFlow logo/title on left
- Navigation links with icons in center
- User profile icon on right
- Fixed positioning ensures always visible
- Responsive hamburger menu on mobile (≤ 768px)

**Navigation Menu:**
- Material Design icons for visual clarity
- Hover effects (background color + scale)
- Active state highlighting
- Flex layout for alignment
- Responsive collapsible menu on mobile

---

#### Tables (`layout.css`, `table.js`)

**Table Structure:**
```html
<table class="table">
  <thead>
    <tr>
      <th>Column 1</th>
      <th>Column 2</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>Data</td>
      <td>Data</td>
    </tr>
  </tbody>
</table>
```

**Styling:**
- Zebra striping (alternating row colors)
- Hover row highlighting
- Responsive horizontal scroll on mobile
- Column freeze on desktop (first column)
- Sortable headers with click handlers
- Pagination controls

**Features:**
- Search functionality via `table.js`
- Sort by column (ascending/descending)
- Pagination (10, 25, 50 rows per page)
- Export-to-CSV button
- Status badges with colors

---

#### Forms (`auth-common.css`, custom styling)

**Form Components:**
```html
<div class="form-group">
  <label for="input">Label</label>
  <input type="text" id="input" class="form-control" required>
  <span class="error-message">Error text</span>
</div>
```

**Styling:**
- Consistent form spacing
- Focus states for accessibility
- Error message styling
- Input validation feedback
- Button styling (primary, secondary)
- Disabled state handling

**Form Types:**
- Login form (auth-common.css)
- Registration form (signup.css)
- Payment form
- Application form
- Profile update form
- Message form

---

#### Modals (`layout.css`, `ui.js`)

**Modal Structure:**
```html
<div class="modal" id="myModal">
  <div class="modal-content">
    <div class="modal-header">
      <h2>Modal Title</h2>
      <button class="close">&times;</button>
    </div>
    <div class="modal-body">
      <!-- Content -->
    </div>
    <div class="modal-footer">
      <button class="btn-secondary">Cancel</button>
      <button class="btn-primary">Submit</button>
    </div>
  </div>
</div>
```

**Features:**
- Overlay backdrop (dark, semi-transparent)
- Center positioning
- Close button (X)
- Keyboard navigation (ESC to close)
- Customizable width/height
- Smooth animations

**Usage Examples:**
- Payment action modals
- Application approval form
- Tenant profile viewing
- Message dialog
- Settings forms

---

#### Cards & Sections (`layout.css`)

**Card Component:**
```css
.card {
  background: white;
  border: 1px solid #ddd;
  border-radius: 4px;
  padding: 20px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
```

**Section Grouping:**
```html
<section class="report-section">
  <h2>Section Title</h2>
  <div class="section-content">
    <!-- Content -->
  </div>
</section>
```

---

### Design System

#### Color Palette

**Primary Colors:**
- Header Blue: `#0B3C5D` (professional, trust-building)
- Success Green: `#28a745` (positive actions, paid status)
- Warning Yellow: `#ffc107` (pending, review needed)
- Danger Red: `#dc3545` (late, not paid, errors)
- Info Blue: `#17a2b8` (notifications, information)

**Status Indicators:**
- ✅ Green: Paid, Approved, Available, Active
- ⏳ Yellow: Pending, Overdue, Processing
- ❌ Red: Not Paid, Rejected, Maintenance, Error
- ℹ️ Blue: Information, New, Message

#### Icon Set

**Material Icons** (Google's icon library):
- `dashboard` - Dashboard
- `people` - Tenants
- `payments` - Payments
- `assessment` - Reports
- `store` / `storefront` - Stalls
- `mail` - Messages
- `notifications` - Alerts
- `person` - Profile/Account
- `contact_support` - Support
- `payment` - Payment method
- `home` - Home/Dashboard
- `edit` - Edit/Modify
- `delete` - Delete/Remove
- `download` - Download/Export
- `print` - Print
- `search` - Search
- `filter_list` - Filter

**Usage:**
```html
<i class="material-icons">dashboard</i>
```

---

### Responsive Design Breakpoints

#### Mobile First Approach

**Small Screens (≤ 480px)**
- Single column layout
- Hamburger menu for navigation
- Touch-optimized buttons (min 44px height)
- Full-width modals
- Vertical card stacking

**Tablet (480px - 768px)**
- Two-column layout where possible
- Adapted table display (horizontal scroll)
- Side panel navigation (slide-out)
- Optimized spacing
- Medium-size buttons

**Desktop (≥ 768px)**
- Full multi-column layout
- Fixed header navigation
- Full-width tables
- Side-by-side panels
- Desktop-optimized spacing

**Large Desktop (≥ 1200px)**
- Maximum content width container
- Three-column layouts
- Sidebar + main content + panel
- Full feature visibility

---

### Tenant-Specific Styling

#### Tenant Bootstrap CSS (`tenant-bootstrap.css`)

Custom overrides for tenant portal:
- Different color scheme (user-friendly)
- Simplified navigation
- Card-based layout
- Vertical content flow
- Bottom navigation bar (mobile)

**Tenant Navbar:**
- Bottom-positioned on mobile
- Horizontal on desktop
- Icon-based navigation
- Active state highlighting
- No text on mobile (icons only)

---

### Admin-Specific Styling

#### Admin Layout Enhancements

**Dashboard Cards:**
- Overview metrics
- Key performance indicators (KPIs)
- Color-coded status
- Large readable numbers
- Quick action buttons

**Sidebar Navigation:**
- Optional sidebar menu (collapsible)
- Active page highlighting
- Section grouping
- Icon + text combination

**Data Tables:**
- Multi-select checkboxes
- Bulk action toolbar
- Inline actions (edit, delete, message)
- Row expansion for details
- Advanced filtering

---

### Charts & Visualizations

#### Chart.js Integration

**Supported Chart Types:**
- Pie Charts (stall availability, status distribution)
- Bar Charts (monthly revenue, comparison data)
- Line Charts (revenue trends, time-series data)
- Doughnut Charts (same as pie, different appearance)

**Chart Features:**
- Responsive sizing
- Interactive tooltips
- Legend display
- Color customization
- Data label options
- Export to PNG/PDF

**Usage Example:**
```javascript
const ctx = document.getElementById('chart').getContext('2d');
new Chart(ctx, {
  type: 'pie',
  data: {
    labels: ['Occupied', 'Available', 'Maintenance'],
    datasets: [{
      data: [25, 18, 12],
      backgroundColor: ['#28a745', '#17a2b8', '#ffc107']
    }]
  }
});
```

---

### JavaScript Components

#### UI Utilities (`ui.js`)

**Functions:**
```javascript
openModal(modalId)          // Open modal dialog
closeModal(modalId)         // Close modal dialog
showNotification(msg, type) // Show toast notification
toggleMenu()                // Toggle mobile menu
formatCurrency(num)         // Format as Philippine peso
formatDate(date)            // Format date
```

#### Table Management (`table.js`)

```javascript
initTable(selector)              // Initialize table features
searchTable(keyword)             // Filter table by search
sortTable(columnIndex)           // Sort by column
paginateTable(pageSize)          // Set pagination
exportTableToCSV(filename)       // Export to CSV
highlightRow(rowId)              // Highlight specific row
```

#### Chart Management (`charts.js`)

```javascript
initChart(chartId, config)       // Initialize chart
updateChartData(chartId, data)   // Update chart data
switchChartType(chartId, type)   // Change chart type
exportChartAsPNG(chartId)        // Export to image
exportChartAsPDF(chartId)        // Export to PDF
```

#### Verification (`verify_2fa.js`)

```javascript
handleCodeInput()                // Handle code entry
submitCode()                     // Submit verification
autoFocusNextInput()             // Tab between inputs
showError(message)               // Display error
```

---

### Custom Messenger UI

#### Messenger CSS (`messenger.css`)

**Two-Panel Layout:**

**Left Panel (Conversations):**
- Search input with debounce
- Conversation list with:
  - User avatar (initials or image)
  - User name
  - Last message preview
  - Timestamp
  - Unread badge (if new messages)
  - Hover highlighting

**Center Panel (Messages):**
- Full conversation thread
- Message bubbles with:
  - Sender name/avatar
  - Message content
  - Timestamp
  - Read receipt (checkmark)
  - Message status (sending/sent/read)
- Color differentiation:
  - Blue for current user (admin)
  - Gray for other user (tenant)
- Auto-scroll to latest message

**Message Input Area:**
- Text input field
- Send button
- Emoji picker (optional)
- Attachment button (optional)

**Theme:**
- Clean white background
- Card-based design
- Subtle shadows
- Professional typography

---

### Accessibility Features

**Implemented:**
- Semantic HTML structure
- ARIA labels for screen readers
- Keyboard navigation support
- Color contrast compliance
- Focus states on interactive elements
- Alternative text for images
- Proper form labeling
- Error message associations

---

### Performance Optimization

**Techniques Used:**
- CSS minification
- JavaScript minification
- Lazy loading for images
- CSS media queries for responsive design
- Efficient JavaScript selectors
- Event delegation for dynamic content
- Caching of static assets
- Debouncing for search/filter inputs

---

## Database Structure

### Core Tables

#### `users`
Stores user account information

```sql
id                  INT PRIMARY KEY AUTO_INCREMENT
email               VARCHAR(255) UNIQUE NOT NULL
password            VARCHAR(255) NOT NULL  -- bcrypt hash
first_name          VARCHAR(100) NOT NULL
last_name           VARCHAR(100) NOT NULL
phone               VARCHAR(20)
business_name       VARCHAR(255)  -- For tenants
role                ENUM('admin','tenant') NOT NULL
profile_photo       VARCHAR(255)  -- Optional
status              ENUM('active','inactive','suspended') DEFAULT 'active'
created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP
updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
```

#### `stalls`
Rental stall inventory and status

```sql
id                  INT PRIMARY KEY AUTO_INCREMENT
stall_no            VARCHAR(50) UNIQUE NOT NULL
type                ENUM('Wet','Dry','Apparel') NOT NULL
location            VARCHAR(255) NOT NULL
status              ENUM('available','occupied','maintenance') DEFAULT 'available'
monthly_rent        DECIMAL(10,2)
features            TEXT  -- Description of features
created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP
```

#### `leases`
Tenant-to-stall assignments

```sql
id                  INT PRIMARY KEY AUTO_INCREMENT
tenant_id           INT NOT NULL
stall_id            INT NOT NULL
lease_start         DATE NOT NULL
lease_end           DATE
status              ENUM('active','expired','terminated') DEFAULT 'active'
monthly_rent        DECIMAL(10,2) NOT NULL
created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP
FOREIGN KEY (tenant_id) REFERENCES users(id)
FOREIGN KEY (stall_id) REFERENCES stalls(id)
```

#### `dues`
Monthly payment obligations

```sql
id                  INT PRIMARY KEY AUTO_INCREMENT
lease_id            INT NOT NULL
due_date            DATE NOT NULL
amount_due          DECIMAL(10,2) NOT NULL
paid                TINYINT DEFAULT 0
marked_arrear_on    DATE  -- When marked as overdue
created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP
FOREIGN KEY (lease_id) REFERENCES leases(id)
```

#### `payments`
Recorded payments

```sql
id                  INT PRIMARY KEY AUTO_INCREMENT
lease_id            INT NOT NULL
amount              DECIMAL(10,2) NOT NULL
payment_date        DATE NOT NULL
method              VARCHAR(50)  -- Cash, Check, Bank Transfer, etc.
reference_no        VARCHAR(100)  -- Check no., bank ref., etc.
notes               TEXT
created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP
FOREIGN KEY (lease_id) REFERENCES leases(id)
```

#### `arrears`
Outstanding balance tracking per lease

```sql
id                  INT PRIMARY KEY AUTO_INCREMENT
lease_id            INT NOT NULL UNIQUE
total_arrears       DECIMAL(10,2) DEFAULT 0
reason              TEXT
created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP
updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
FOREIGN KEY (lease_id) REFERENCES leases(id)
```

#### `arrear_entries`
Detailed arrear item tracking

```sql
id                  INT PRIMARY KEY AUTO_INCREMENT
lease_id            INT NOT NULL
due_id              INT  -- Reference to dues table
amount              DECIMAL(10,2) NOT NULL
source              ENUM('unpaid_due','marked_not_paid','partial_payment','overdue_7days','penalty') NOT NULL
created_on          DATE NOT NULL
is_paid             TINYINT DEFAULT 0
paid_on             DATE
created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP
FOREIGN KEY (lease_id) REFERENCES leases(id)
FOREIGN KEY (due_id) REFERENCES dues(id)
```

#### `messages`
Chat messages between users

```sql
id                  INT PRIMARY KEY AUTO_INCREMENT
sender_id           INT NOT NULL
receiver_id         INT NOT NULL
message             TEXT NOT NULL
sender_email        VARCHAR(255)  -- Optional email provided by tenant
is_read             TINYINT DEFAULT 0
attachment_path     VARCHAR(255)  -- Optional file attachment
created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP
FOREIGN KEY (sender_id) REFERENCES users(id)
FOREIGN KEY (receiver_id) REFERENCES users(id)
```

#### `notifications`
System notifications and alerts

```sql
id                  INT PRIMARY KEY AUTO_INCREMENT
sender_id           INT NOT NULL
receiver_id         INT NOT NULL
type                VARCHAR(50)  -- chat, payment, application, system
title               VARCHAR(255)
message             TEXT
message_id          INT  -- Reference to messages.id if chat
is_read             TINYINT DEFAULT 0
created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP
FOREIGN KEY (sender_id) REFERENCES users(id)
FOREIGN KEY (receiver_id) REFERENCES users(id)
```

#### `stall_applications`
Tenant stall rental applications

```sql
id                  INT PRIMARY KEY AUTO_INCREMENT
tenant_id           INT NOT NULL
stall_type          ENUM('Wet','Dry','Apparel') NOT NULL
business_name       VARCHAR(255) NOT NULL
business_description TEXT NOT NULL
business_logo_path  VARCHAR(255)
business_permit_path VARCHAR(255)
valid_id_path       VARCHAR(255)
digital_signature_path VARCHAR(255)
status              ENUM('PENDING','APPROVED','REJECTED') DEFAULT 'PENDING'
assigned_stall_id   INT  -- After approval
created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP
updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
FOREIGN KEY (tenant_id) REFERENCES users(id)
FOREIGN KEY (assigned_stall_id) REFERENCES stalls(id)
```

#### `contacts`
Public contact form submissions

```sql
id                  INT PRIMARY KEY AUTO_INCREMENT
name                VARCHAR(255) NOT NULL
email               VARCHAR(255) NOT NULL
subject             VARCHAR(255)
message             TEXT
status              ENUM('new','replied','resolved') DEFAULT 'new'
created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP
```

---

### Database Views (Optional)

#### Lease Information View
```sql
CREATE VIEW v_lease_info AS
SELECT 
  l.id as lease_id,
  CONCAT(u.first_name, ' ', u.last_name) as tenant_name,
  s.stall_no,
  s.type as stall_type,
  l.lease_start,
  l.lease_end,
  l.monthly_rent
FROM leases l
JOIN users u ON l.tenant_id = u.id
JOIN stalls s ON l.stall_id = s.id
WHERE l.status = 'active';
```

#### Payment Summary View
```sql
CREATE VIEW v_payment_summary AS
SELECT 
  l.id as lease_id,
  COUNT(p.id) as total_payments,
  SUM(p.amount) as total_paid,
  MAX(p.payment_date) as last_payment_date
FROM leases l
LEFT JOIN payments p ON l.id = p.lease_id
GROUP BY l.id;
```

---

---

## Summary

RentFlow is a comprehensive, professionally-built property management system that combines:

✅ **Robust Backend** - PDO-based PHP with secure database management
✅ **Rich Features** - Payments, applications, messaging, reporting
✅ **Strong Security** - SQL injection prevention, XSS protection, CSRF mitigation, bcrypt passwords, 2FA
✅ **Scalable API** - RESTful endpoints for all major operations
✅ **Custom Design** - Professional UI framework with responsive design
✅ **User-Centric** - Separate portals for admins and tenants
✅ **Data Analytics** - Charts, exports, reports, and KPIs
✅ **Communication** - Messenger-style chat system
✅ **Application Workflow** - Complete tenant application and approval system
✅ **Production-Ready** - Error handling, logging, validation, and best practices

---

**Version:** 1.4.0  
**Last Updated:** February 25, 2026  
**Maintained by:** RentFlow Development Team

