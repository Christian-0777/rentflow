# RentFlow Per-Page Documentation

This document describes every main page in the RentFlow project, including what you see, how it works, and the purpose of each page.

---

## Public Pages

### Landing Page (public/index.php)
- **What you see:**
  - Welcome banner, site title, call-to-action buttons (Register, Login), and a preview table of available stalls.
- **How it works:**
  - Fetches up to 6 available stalls from the database and displays them.
  - Provides navigation to registration and login.
- **Purpose:**
  - To introduce RentFlow to new users and allow tenants to browse available stalls before registering.

### Register (public/register.php)
- **What you see:**
  - Registration form (name, email, password, business info), terms popup, OTP modal for email verification.
- **How it works:**
  - Validates input, generates unique tenant ID and confirmation code, sends OTP via email, and stores user as unconfirmed until verified.
- **Purpose:**
  - To onboard new tenants securely and ensure valid contact information.

### Login (public/login.php)
- **What you see:**
  - Login form (email/tenant ID, password), 2FA prompt if enabled, trusted device option.
- **How it works:**
  - Authenticates tenant, checks for 2FA, manages trusted devices, and starts session.
- **Purpose:**
  - To securely authenticate tenants and support two-factor authentication.

### Forgot Password (public/forgot_password.php)
- **What you see:**
  - Email input, OTP request/resend, feedback messages.
- **How it works:**
  - Sends OTP to email, enforces cooldown for resends, verifies user before allowing password reset.
- **Purpose:**
  - To allow tenants to securely reset their password if forgotten.

### Reset Password (public/reset_password.php)
- **What you see:**
  - OTP and new password form, feedback on success/failure.
- **How it works:**
  - Verifies OTP, updates password if valid, handles errors and expiry.
- **Purpose:**
  - To complete the password reset process securely.

### Confirm Code (public/confirm.php)
- **What you see:**
  - Code and email input, confirmation feedback, terms prompt.
- **How it works:**
  - Confirms tenant account, notifies admin, and prompts for terms acceptance.
- **Purpose:**
  - To activate new tenant accounts and notify admin of new tenants.

### Terms Acceptance (public/terms_accept.php)
- **What you see:**
  - Terms and privacy policy, 2FA and device trust options.
- **How it works:**
  - Records acceptance, enables 2FA/device trust if selected.
- **Purpose:**
  - To ensure tenants agree to terms and optionally enable extra security.

### Logout (public/logout.php)
- **What you see:**
  - No UI; logs out and redirects to landing page.
- **How it works:**
  - Destroys session and redirects.
- **Purpose:**
  - To securely log out tenants.

---

## Tenant Pages

### Dashboard (tenant/dashboard.php)
- **What you see:**
  - Welcome message, next payment due, last payment, total arrears, latest notification, navigation bar.
- **How it works:**
  - Fetches tenant's lease, payment, arrears, and notification data for summary display.
- **Purpose:**
  - To give tenants a quick overview of their account and important actions.

### Payments (tenant/payments.php)
- **What you see:**
  - Upcoming payment, recent payment, transaction history, receipts, arrears and penalties.
- **How it works:**
  - Fetches payment and arrears data, displays history, and allows receipt viewing.
- **Purpose:**
  - To let tenants track and review their payment status and history.

### Stalls (tenant/stalls.php)
- **What you see:**
  - List of available stalls, details of rented stalls, application options, flash messages.
- **How it works:**
  - Fetches available and rented stalls, allows tenants to apply for new stalls.
- **Purpose:**
  - To let tenants browse, apply for, and manage their stalls.

### Notifications (tenant/notifications.php)
- **What you see:**
  - List of recent notifications, sender info, timestamps.
- **How it works:**
  - Fetches notifications for the tenant, marks them as read.
- **Purpose:**
  - To keep tenants informed of important updates and messages.

### Profile (tenant/profile.php)
- **What you see:**
  - Profile info, stall and lease details, edit button, navigation bar.
- **How it works:**
  - Fetches tenant and lease info for display and editing.
- **Purpose:**
  - To let tenants view and update their personal and business information.

### Account Settings (tenant/account.php)
- **What you see:**
  - Forms to update profile, email, password, 2FA, and notification settings.
- **How it works:**
  - Handles updates to user info and security settings.
- **Purpose:**
  - To allow tenants to manage their account and security preferences.

### Support (tenant/support.php)
- **What you see:**
  - Support chat form, file upload, message history, feedback.
- **How it works:**
  - Sends messages (and attachments) to admin as notifications.
- **Purpose:**
  - To provide tenants with a direct support channel to the admin.

---

## Admin Pages

### Dashboard (admin/dashboard.php)
- **What you see:**
  - Stall availability summary, upcoming payments, revenue highlights, recent payments.
- **How it works:**
  - Aggregates stall, payment, and revenue data for quick admin overview.
- **Purpose:**
  - To give admins a summary of market status and financials.

### Applications (admin/applications.php)
- **What you see:**
  - List of stall applications, status filters, application details, available stalls for assignment.
- **How it works:**
  - Fetches applications and available stalls, allows admin to review and approve/reject applications.
- **Purpose:**
  - To manage and process tenant applications for stalls.

### Tenants (admin/tenants.php)
- **What you see:**
  - Search/filter tenants, payment/arrears summary, transfer modal, CSV export.
- **How it works:**
  - Fetches tenant, lease, payment, and arrears data, supports search and export.
- **Purpose:**
  - To manage tenants, view payment status, and handle transfers.

### Stalls (admin/stalls.php)
- **What you see:**
  - Add/edit/remove stall forms, stall list, picture upload.
- **How it works:**
  - Handles CRUD for stalls, including image management.
- **Purpose:**
  - To manage the inventory of market stalls.

### Payments (admin/payments.php)
- **What you see:**
  - Payment management forms, arrears adjustment, payment history.
- **How it works:**
  - Allows marking payments, adjusting arrears, and viewing payment records.
- **Purpose:**
  - To manage and track all tenant payments and arrears.

### Reports (admin/reports.php)
- **What you see:**
  - Revenue analytics, stall availability, new tenants, export options (CSV/XLSX).
- **How it works:**
  - Aggregates and exports financial and occupancy data.
- **Purpose:**
  - To provide admins with detailed reports for analysis and record-keeping.

### Messages (admin/messages.php)
- **What you see:**
  - Messenger-style chat interface, conversation list, message history.
- **How it works:**
  - Fetches conversations and messages with tenants, allows real-time messaging.
- **Purpose:**
  - To facilitate communication between admin and tenants.

### Notifications (admin/notifications.php)
- **What you see:**
  - List of notifications, chat threads, send message form.
- **How it works:**
  - Displays notifications and allows admin to send messages to tenants.
- **Purpose:**
  - To keep admins informed and enable direct communication with tenants.

### Account (admin/account.php)
- **What you see:**
  - Admin profile, settings update form, logout option.
- **How it works:**
  - Allows admin to update their profile and settings.
- **Purpose:**
  - To manage admin account information.

### Contact Service (admin/contact.php)
- **What you see:**
  - Contact form, file upload, feedback message.
- **How it works:**
  - Sends message (and attachment) as notification to admin/support.
- **Purpose:**
  - To allow admin to contact support or log issues.

### Login/Logout (admin/login.php, admin/logout.php)
- **What you see:**
  - Login form, feedback messages, redirects on logout.
- **How it works:**
  - Authenticates admin, manages session, and logs out securely.
- **Purpose:**
  - To control admin access to the system.

---

## Treasury Pages (Legacy)

> **Note:** The Treasury role has been removed. Pages now redirect to admin login.

- treasury/login.php, treasury/dashboard.php, treasury/adjustments.php
  - **What you see:**
    - Redirects to admin login.
  - **How it works:**
    - Immediately redirects; legacy code may remain for reference.
  - **Purpose:**
    - Previously for treasury management, now handled by admin.

---

## Chat Pages

### Chat (chat/chat.php, chat/notify.php)
- **What you see:**
  - Chat interface for real-time messaging (details depend on implementation).
- **How it works:**
  - Handles chat between users (admin/tenant), notifications.
- **Purpose:**
  - To provide real-time communication within the system.

---

## API Endpoints

- All files in api/ provide backend logic for AJAX and system operations (e.g., payments, applications, messaging, exports, etc.).
- **What you see:**
  - No direct UI; called by frontend pages.
- **How it works:**
  - Handle requests, perform DB operations, return JSON or files.
- **Purpose:**
  - To support all dynamic and asynchronous features of the system.

---

This documentation covers the main user-facing and admin pages. For more technical details, see the code or ask for a specific file breakdown.
