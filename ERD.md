# RentFlow Database Tables Documentation

## Overview
RentFlow is a property management system with the following core modules:
- **User Management**: Users with different roles (tenant, admin, treasury)
- **Stall Management**: Rental stalls tracking and status
- **Lease Management**: Lease agreements between tenants and stalls
- **Payments & Arrears**: Payment tracking and arrear management
- **Messaging**: Communication between users
- **Notifications**: In-system notifications
- **Applications**: Stall application requests
- **Security**: Authentication and device management

---

## 1. USERS TABLE
**Purpose**: Core user account information

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INT(11) | PRIMARY KEY, AUTO_INCREMENT | User unique identifier |
| tenant_id | VARCHAR(4) | UNIQUE | Tenant-specific ID |
| role | ENUM('tenant','admin','treasury') | NOT NULL | User role in the system |
| email | VARCHAR(255) | NOT NULL, UNIQUE | User email address |
| first_name | VARCHAR(100) | NOT NULL | First name |
| last_name | VARCHAR(100) | NOT NULL | Last name |
| password_hash | VARCHAR(255) | NOT NULL | Hashed password |
| status | ENUM('active','inactive','lease_ended') | DEFAULT 'active' | Account status |
| created_at | DATETIME | DEFAULT CURRENT_TIMESTAMP | Account creation date |
| confirmed | TINYINT(1) | DEFAULT 0 | Email confirmation status |
| cover_photo | VARCHAR(255) | | Cover photo file path |
| profile_photo | VARCHAR(255) | | Profile photo file path |
| location | VARCHAR(255) | | User location |
| business_name | VARCHAR(255) | | Business name (for tenants) |
| two_factor_enabled | TINYINT(1) | DEFAULT 0 | 2FA status |
| remember_device_enabled | TINYINT(1) | DEFAULT 0 | Device memory status |
| password_reset_otp | VARCHAR(255) | INDEX | OTP for password reset |
| password_reset_expires | DATETIME | | OTP expiration time |
| password_reset_requested_at | DATETIME | | When password reset was requested |
| notif_email | TINYINT(1) | DEFAULT 1 | Email notification preference |
| notif_sms | TINYINT(1) | DEFAULT 0 | SMS notification preference |
| notify_email_on_messages | TINYINT(1) | DEFAULT 1 | Email notification for messages |

---

## 2. STALLS TABLE
**Purpose**: Track rental stall inventory and status

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INT(11) | PRIMARY KEY, AUTO_INCREMENT | Stall unique identifier |
| stall_no | VARCHAR(32) | NOT NULL, UNIQUE | Stall number/reference |
| type | ENUM('wet','dry','apparel') | NOT NULL | Stall type |
| location | VARCHAR(255) | NOT NULL | Physical location |
| status | ENUM('available','occupied','maintenance') | DEFAULT 'available' | Current status |
| created_at | DATETIME | DEFAULT CURRENT_TIMESTAMP | Creation date |
| image_path | VARCHAR(255) | | Stall image path |
| picture_path | VARCHAR(255) | | Stall picture path |

---

## 3. LEASES TABLE
**Purpose**: Management of lease agreements between tenants and stalls

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INT(11) | PRIMARY KEY, AUTO_INCREMENT | Lease unique identifier |
| tenant_id | INT(11) | NOT NULL, FOREIGN KEY | Reference to users table |
| stall_id | INT(11) | NOT NULL, FOREIGN KEY | Reference to stalls table |
| lease_start | DATE | NOT NULL | Lease start date |
| lease_end | DATE | | Lease end date |
| monthly_rent | DECIMAL(10,2) | NOT NULL | Monthly rent amount |

---

## 4. DUES TABLE
**Purpose**: Track monthly rental dues for each lease

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INT(11) | PRIMARY KEY, AUTO_INCREMENT | Due unique identifier |
| lease_id | INT(11) | NOT NULL, FOREIGN KEY | Reference to leases table |
| due_date | DATE | NOT NULL | Payment due date |
| amount_due | DECIMAL(10,2) | NOT NULL | Amount due |
| paid | TINYINT(1) | DEFAULT 0 | Payment status (0=unpaid, 1=paid) |
| marked_arrear_on | DATE | | Date marked as arrear |

---

## 5. PAYMENTS TABLE
**Purpose**: Record all payment transactions

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INT(11) | PRIMARY KEY, AUTO_INCREMENT | Payment unique identifier |
| lease_id | INT(11) | NOT NULL, FOREIGN KEY | Reference to leases table |
| amount | DECIMAL(10,2) | NOT NULL | Payment amount |
| payment_date | DATE | NOT NULL | Date of payment |
| method | ENUM('cash','gcash','bank','card','manual','partial') | NOT NULL | Payment method |
| transaction_id | VARCHAR(64) | UNIQUE | Transaction reference ID |
| remarks | VARCHAR(255) | | Additional comments |
| receipt_path | VARCHAR(255) | | Receipt file path |

---

## 6. ARREARS TABLE
**Purpose**: Summary of outstanding arrears per lease

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INT(11) | PRIMARY KEY, AUTO_INCREMENT | Arrear record ID |
| lease_id | INT(11) | NOT NULL, FOREIGN KEY | Reference to leases table |
| total_arrears | DECIMAL(10,2) | DEFAULT 0.00 | Total arrear amount |
| last_updated | DATETIME | DEFAULT CURRENT_TIMESTAMP | Last update timestamp |
| reason | VARCHAR(255) | | Reason for arrear |

---

## 7. ARREAR_ENTRIES TABLE
**Purpose**: Detailed log of individual arrear entries

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INT(11) | PRIMARY KEY, AUTO_INCREMENT | Entry unique identifier |
| lease_id | INT(11) | NOT NULL, FOREIGN KEY | Reference to leases table |
| due_id | INT(11) | FOREIGN KEY | Reference to dues table |
| amount | DECIMAL(10,2) | NOT NULL | Arrear amount |
| source | ENUM('unpaid_due','marked_not_paid','partial_payment','overdue_7days') | NOT NULL | Source of arrear |
| created_on | DATE | NOT NULL, INDEX | Date entry was created |
| is_paid | TINYINT(1) | DEFAULT 0 | Payment status |
| paid_on | DATE | | Date arrear was paid |
| created_at | DATETIME | DEFAULT CURRENT_TIMESTAMP | Timestamp of creation |

---

## 8. PENALTIES TABLE
**Purpose**: Track late payment penalties

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INT(11) | PRIMARY KEY, AUTO_INCREMENT | Penalty unique identifier |
| lease_id | INT(11) | NOT NULL, FOREIGN KEY | Reference to leases table |
| due_id | INT(11) | NOT NULL, FOREIGN KEY | Reference to dues table |
| penalty_amount | DECIMAL(10,2) | NOT NULL | Penalty amount |
| applied_on | DATE | NOT NULL | Date penalty was applied |

---

## 9. MESSAGES TABLE
**Purpose**: Store user-to-user messages and communications

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INT(11) | PRIMARY KEY, AUTO_INCREMENT | Message unique identifier |
| sender_id | INT(11) | NOT NULL, FOREIGN KEY | Reference to sender (users table) |
| receiver_id | INT(11) | NOT NULL, FOREIGN KEY | Reference to receiver (users table) |
| subject | VARCHAR(255) | | Message subject |
| message | TEXT | NOT NULL | Message content |
| sender_email | VARCHAR(255) | | Sender's email address |
| is_read | TINYINT(1) | DEFAULT 0 | Read status |
| is_archived | TINYINT(1) | DEFAULT 0 | Archive status |
| created_at | DATETIME | DEFAULT CURRENT_TIMESTAMP | Creation timestamp |
| updated_at | DATETIME | DEFAULT CURRENT_TIMESTAMP ON UPDATE | Last update timestamp |
| attachment_path | VARCHAR(255) | | File attachment path |
| attachment_type | ENUM('image','document','other') | | Type of attachment |

**Indexes**:
- idx_sender_id
- idx_receiver_id
- idx_conversation (sender_id, receiver_id)
- idx_created_at

---

## 10. MESSAGE_THREADS TABLE
**Purpose**: Organize messages into conversation threads

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INT(11) | PRIMARY KEY, AUTO_INCREMENT | Thread unique identifier |
| user1_id | INT(11) | NOT NULL, FOREIGN KEY | First user (users table) |
| user2_id | INT(11) | NOT NULL, FOREIGN KEY | Second user (users table) |
| last_message_id | INT(11) | FOREIGN KEY | Reference to last message |
| last_message_at | DATETIME | DEFAULT CURRENT_TIMESTAMP | Timestamp of last message |

**Indexes**:
- unique_thread (user1_id, user2_id)
- last_message_at

---

## 11. NOTIFICATIONS TABLE
**Purpose**: Track system and chat notifications

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INT(11) | PRIMARY KEY, AUTO_INCREMENT | Notification unique identifier |
| sender_id | INT(11) | NOT NULL, FOREIGN KEY | Notification sender (users table) |
| receiver_id | INT(11) | NOT NULL, FOREIGN KEY | Notification receiver (users table) |
| type | ENUM('system','chat') | NOT NULL | Notification type |
| title | VARCHAR(255) | | Notification title |
| message | TEXT | NOT NULL | Notification content |
| created_at | DATETIME | DEFAULT CURRENT_TIMESTAMP | Creation timestamp |
| is_read | TINYINT(1) | DEFAULT 0 | Read status |
| message_id | INT(11) | FOREIGN KEY | Reference to related message |

---

## 12. STALL_APPLICATIONS TABLE
**Purpose**: Track tenant applications for stalls

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INT(11) | PRIMARY KEY, AUTO_INCREMENT | Application unique identifier |
| tenant_id | INT(11) | NOT NULL, FOREIGN KEY | Applying tenant (users table) |
| type | ENUM('wet','dry','apparel') | NOT NULL | Requested stall type |
| business_name | VARCHAR(255) | NOT NULL | Business name |
| business_description | TEXT | NOT NULL | Business description |
| business_logo_path | VARCHAR(255) | | Business logo file path |
| business_permit_path | VARCHAR(255) | | Business permit file path |
| valid_id_path | VARCHAR(255) | | Valid ID file path |
| signature_path | VARCHAR(255) | | Digital signature file path |
| status | ENUM('pending','approved','rejected') | DEFAULT 'pending' | Application status |
| created_at | DATETIME | DEFAULT CURRENT_TIMESTAMP | Application submission date |

---

## 13. AUTH_CODES TABLE
**Purpose**: Store authentication codes for admin/treasury access

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INT(11) | PRIMARY KEY, AUTO_INCREMENT | Code set unique identifier |
| role | ENUM('admin','treasury') | NOT NULL | Role these codes are for |
| code1 | VARCHAR(16) | NOT NULL | First authentication code |
| code2 | VARCHAR(16) | NOT NULL | Second authentication code |
| code3 | VARCHAR(16) | NOT NULL | Third authentication code |
| valid_until | DATETIME | NOT NULL | Code expiration datetime |

---

## 14. PASSWORD_RESETS TABLE
**Purpose**: Manage password reset requests and tokens

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INT(11) | PRIMARY KEY, AUTO_INCREMENT | Reset request unique identifier |
| user_id | INT(11) | NOT NULL, FOREIGN KEY | User requesting reset (users table) |
| email | VARCHAR(255) | NOT NULL | Email address for reset |
| token | VARCHAR(255) | NOT NULL, UNIQUE | Reset token |
| created_at | DATETIME | DEFAULT CURRENT_TIMESTAMP | Request creation timestamp |
| expires_at | DATETIME | NOT NULL | Token expiration datetime |
| used | TINYINT(1) | DEFAULT 0 | Whether token has been used |

---

## 15. TRUSTED_DEVICES TABLE
**Purpose**: Track trusted devices for Remember Device feature

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INT(10) UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Device unique identifier |
| user_id | INT(11) | NOT NULL, FOREIGN KEY | Device owner (users table) |
| device_fingerprint | VARCHAR(255) | NOT NULL, UNIQUE | Device fingerprint hash |
| device_name | VARCHAR(255) | | User-friendly device name |
| device_token | VARCHAR(255) | NOT NULL, UNIQUE | Device authentication token |
| user_agent | TEXT | | Browser/client user agent string |
| ip_address | VARCHAR(45) | | Device IP address |
| created_at | DATETIME | DEFAULT CURRENT_TIMESTAMP | Device registration date |
| last_used_at | DATETIME | DEFAULT CURRENT_TIMESTAMP ON UPDATE | Last used timestamp |
| is_active | TINYINT(1) | DEFAULT 1 | Device active status |

---

## Database Relationships Diagram

```
users (1) ─── (M) leases ─── (1) stalls
   │                │
   │                ├─── (M) dues
   │                │      └─── (M) penalties
   │                │
   │                ├─── (M) payments
   │                │
   │                ├─── (M) arrears
   │                └─── (M) arrear_entries
   │
   ├─── (M) messages (bidirectional)
   │
   ├─── (M) notifications (bidirectional)
   │
   ├─── (M) stall_applications
   │
   ├─── (M) password_resets
   │
   ├─── (M) trusted_devices
   │
   └─── (M) message_threads
```

---

## Key Features by Table Category

### Financial Management
- **dues**: Monthly rental tracking
- **payments**: Payment history and methods
- **arrears**: Outstanding balance tracking
- **arrear_entries**: Detailed arrear chronology
- **penalties**: Late payment charges

### User & Access Management
- **users**: Core user accounts
- **auth_codes**: Admin/treasury authentication
- **password_resets**: Password recovery
- **trusted_devices**: Device trust management

### Communication
- **messages**: User-to-user messaging
- **message_threads**: Conversation organization
- **notifications**: System and chat alerts

### Property Management
- **stalls**: Available rental properties
- **leases**: Tenant-stall agreements
- **stall_applications**: Application process

---

**Database Name**: rentflow
**Character Set**: utf8mb4
**Collation**: utf8mb4_unicode_ci
**Engine**: InnoDB
