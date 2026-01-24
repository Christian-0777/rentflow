# RentFlow - Update Changelog

This document tracks all minor and major changes made to the RentFlow project.

---

## **MAJOR CHANGES**

### 1. **Stalls Management & Display Enhancement** (Latest)
- **Version**: 1.3.0
- **Status**: Implemented & Tested
- **Description**: Complete redesign of stalls interface for admin and tenant with improved picture handling and table views

#### Files Modified:
- `admin/stalls.php` - Enhanced admin stalls management interface
- `tenant/stalls.php` - Redesigned tenant stalls browsing interface

#### Admin Stalls Features:
1. **Picture Management**:
   - Thumbnail display at 120×120px in table
   - Click thumbnail to view original size in modal
   - Crop/scale independent of original size using `object-fit: cover`

2. **Stall Removal**:
   - Fixed stall_no parameter handling (was casting string to int)
   - Automatic picture file deletion when stall is removed
   - Prevents orphaned files on server

3. **Stall Editing**:
   - Added file upload field for picture replacement
   - Auto-deletes old picture when new one uploaded
   - Shows current picture thumbnail in edit form
   - Form includes `enctype="multipart/form-data"`

4. **Form Handling**:
   - Implemented POST-Redirect-GET (PRG) pattern
   - Prevents "Confirm Form Resubmission" browser warning
   - Redirects after all actions (add, remove, edit, assign)

#### Tenant Stalls Features:
1. **Available Stalls Table**:
   - Converted from grid/card layout to table format
   - Columns: Stall No, Type, Location, Picture, Action
   - Clickable picture thumbnails (80×80px) to view full size
   - "Apply" button in action column

2. **Rented Stalls Table** (New Section):
   - Displays stalls tenant currently rents
   - Columns: Stall No, Type, Location, Monthly Rent, Lease Start, Picture
   - Shows PHP formatted currency (₱ sign)
   - Shows formatted lease start date
   - Only displays if tenant has rented stalls

3. **Table Organization**:
   - Rented stalls section appears FIRST (top of page)
   - Available stalls section appears SECOND (below rented)
   - Better user experience - see existing rentals before browsing

4. **Image Viewer Modal**:
   - Click any stall picture to view at original size
   - Modal displays image at full resolution
   - Responsive layout up to 80vh height
   - Close button (X) or click outside modal to close

#### Database Queries:
- **Available Stalls**: `SELECT FROM stalls WHERE status='available'`
- **Rented Stalls**: `SELECT FROM leases JOIN stalls WHERE tenant_id=?`

#### User Experience Improvements:
- Professional table layout for better data readability
- Picture modals for full-size viewing
- Clear section separation (Rented vs Available)
- Better mobile responsiveness with table format
- Confirmation dialogs for destructive actions (remove)

#### Security Features:
- All file paths sanitized with `htmlspecialchars()`
- File deletion validates existence before unlinking
- Proper file path construction using `__DIR__`
- XSS protection on all user-displayed data

#### Browser Compatibility:
- `object-fit: cover` for thumbnail cropping
- CSS Grid for responsive tables
- ES6 JavaScript for modal handling
- Tested on Chrome, Firefox, Edge

---

### 2. **Two-Factor Authentication (2FA) & Trusted Device System**
- **Version**: 1.2.0
- **Status**: Implemented & Tested
- **Description**: Complete 2FA and device trust management system for enhanced security

#### Files Created:
- `public/assets/js/otp-verification.js` - OTP verification modal handler (optional)
- `public/assets/css/otp-modal.css` - OTP modal styling (optional)

#### Files Modified:
- `public/register.php` - Added 2FA and trust device checkboxes with OTP verification modal
- `public/login.php` - Updated to support email/tenant_id login with device fingerprinting
- `sql/rentflow_schema.sql` - Already contains `two_factor_enabled` and `remember_device_enabled` columns

#### Key Features:
- Enable/disable 2FA during registration
- Trust device option to skip 2FA on known devices
- OTP verification modal for account setup
- Device fingerprinting based on user agent + IP address
- Automatic trusted device tracking
- 10-minute OTP expiration for security
- Email-based OTP delivery

#### Database Tables Used:
- `users` - Contains `two_factor_enabled` and `remember_device_enabled` flags
- `trusted_devices` - Stores trusted device fingerprints, tokens, and metadata
  - `id`, `user_id`, `device_fingerprint`, `device_name`, `device_token`
  - `user_agent`, `ip_address`, `created_at`, `last_used_at`, `is_active`

#### Registration Flow (2FA Enabled):
1. User registers and confirms email with 6-7 digit code
2. On confirmation page, selects "Enable 2FA" checkbox
3. If "Trust this device" is NOT checked:
   - OTP modal appears
   - OTP sent to email
   - User enters 6-digit OTP
   - "Trust this device" checkbox is checked by default in modal
   - After verification, device is registered as trusted
   - User redirects to tenant dashboard
4. If "Trust this device" IS checked:
   - Device is marked as trusted immediately
   - User redirects to tenant dashboard without OTP

#### Login Flow with 2FA:
1. User logs in with email or tenant_id + password
2. If 2FA is enabled:
   - System checks device fingerprint
   - If device is trusted → Login succeeds, redirect to dashboard
   - If device is new → OTP sent to email, verification required
3. If 2FA is disabled:
   - Direct login to dashboard

#### Features:
- Login with **email** OR **tenant_id** (username)
- Automatic device fingerprinting using user agent + IP
- Device trust status tracking
- OTP auto-expiration after 10 minutes
- "Trust this device" checkbox (pre-checked in modal for new registrations)
- Secure token generation for device tracking

#### Security Measures:
- Device fingerprinting to prevent session hijacking
- 6-digit OTP with bcrypt hashing
- 10-minute OTP expiration
- One-time use tokens with automatic clearing
- Secure random device token generation
- IP address tracking for anomaly detection
- User agent validation

#### User Experience:
- Smooth modal animation for OTP entry
- Real-time validation feedback
- Error messages for invalid/expired OTP
- Success messaging before redirect
- Retry capability for OTP entry

---

### 2. **Password Reset Feature**
- **Version**: 1.1.0
- **Status**: Implemented & Tested
- **Description**: Complete password reset functionality added to secure login system

#### Files Created:
- `public/forgot_password.php` - Forgot password request page
- `public/reset_password.php` - Password reset confirmation page
- `sql/migration_password_reset.sql` - Database migration script

#### Files Modified:
- `public/login.php` - Added "Forgot Password?" link

#### Key Features:
- Email-based password reset with unique 64-character tokens
- 24-hour token expiration for security
- One-time use tokens with automatic clearing
- Bcrypt password hashing
- PHPMailer integration for secure email delivery
- Password confirmation validation
- Minimum 6-character password requirement

#### Database Changes:
- Added `password_reset_token` column to `users` table
- Added `password_reset_expires` column to `users` table
- Created index on `password_reset_token` for performance

#### User Flow:
1. Click "Forgot Password?" on login page
2. Enter email address
3. Receive reset link via email (24-hour validity)
4. Click link and set new password
5. Login with new credentials

#### Security Measures:
- Cryptographically random token generation
- Token expiration validation
- Secure password hashing with bcrypt
- Input validation for email and passwords
- Protected against brute force attempts

---

## **MINOR CHANGES**

### 1. **SendGrid API Integration with PHPMailer Fallback** (Latest)
- **Version**: 1.3.1
- **Status**: Implemented
- **Description**: Dual email provider system with SendGrid as primary and PHPMailer as automatic fallback

#### Files Modified:
- `config/mailer.php` - Updated with SendGrid and PHPMailer dual implementation
- `public/register.php` - Now uses updated send_mail function
- `public/login.php` - Removed duplicate mailer include, uses updated send_mail function
- `public/forgot_password.php` - Now uses updated send_mail function
- `api/stalls_apply.php` - Now uses updated send_mail function
- `.env` - Added SENDGRID_API_KEY configuration

#### Key Features:
- **Primary**: SendGrid API for reliable email delivery
- **Fallback**: PHPMailer SMTP automatically activated if SendGrid fails or API key not configured
- **Single Interface**: All existing `send_mail()` calls work without modification
- **Error Logging**: Both providers log errors for debugging and monitoring
- **Seamless Failover**: Automatic fallback to SMTP if API request fails

#### Functions Implemented:
- `send_mail($to, $subject, $body)` - Main function with automatic provider selection
- `send_mail_sendgrid($to, $subject, $body)` - SendGrid API implementation
- `send_mail_phpmailer($to, $subject, $body)` - PHPMailer SMTP implementation

#### Configuration:
- **SendGrid**: Add API key to `.env` file
  ```
  SENDGRID_API_KEY=your_sendgrid_api_key_here
  ```
- **SMTP Fallback**: Existing Gmail SMTP configuration in `.env` remains active
  ```
  MAIL_HOST=smtp.gmail.com
  MAIL_PORT=587
  MAIL_USERNAME=your_email@gmail.com
  MAIL_PASSWORD=your_app_password
  MAIL_FROM=no-reply@rentflow.local
  MAIL_FROM_NAME=Rentflow Team
  ```

#### Email Triggers Using New System:
- Account registration confirmation (register.php)
- 2FA OTP verification (register.php, login.php)
- Password reset requests (forgot_password.php - both initial and resend)
- Stall application notifications (api/stalls_apply.php)

#### Provider Selection Logic:
1. Check if SENDGRID_API_KEY is configured in .env
2. If yes, attempt SendGrid API call
3. If SendGrid succeeds, email is sent ✓
4. If SendGrid fails or API key missing, fallback to PHPMailer SMTP
5. Log errors from both providers for monitoring

#### Benefits:
- **High Reliability**: Dual provider ensures email delivery
- **Cost Efficiency**: Use SendGrid's scalable API while maintaining SMTP backup
- **No Code Changes**: All existing email functions continue to work
- **Better Logging**: Track which provider is handling emails
- **Enterprise Ready**: Automatic failover suitable for production environments

#### Testing:
- All authentication emails tested with SendGrid
- Fallback to PHPMailer verified for API failures
- OTP delivery confirmed working
- Admin notifications confirmed working

#### Deployment Instructions:
1. Update `.env` with SendGrid API key (obtain from sendgrid.com)
2. Keep existing SMTP credentials as fallback
3. Monitor logs for email delivery
4. Test registration, login, and password reset flows

---

### 2. **Database Schema Migration**
- Added password reset columns to users table
- Created performance index for token lookups

### 3. **UI/UX Improvements**
- Added "Forgot Password?" link to login page for better user experience

### 4. **Email Configuration**
- PHPMailer integration verified and ready
- SMTP configuration templates provided
- SendGrid API integrated as primary provider

---

## **CONFIGURATION REQUIREMENTS**

### PHPMailer Setup
Ensure `config/mailer.php` is configured with:
- Gmail SMTP credentials
- Sender email address
- App-specific password (for Gmail)

### Database Migration
Run the migration to add reset token columns:
```bash
mysql -u root rentflow < sql/migration_password_reset.sql
```

### Email URL Configuration
Update base URL in `public/forgot_password.php` if application is not at localhost:
```php
$reset_link = "https://yourdomain.com/public/reset_password.php?token=" . urlencode($reset_token);
```

---

## **VERSION HISTORY**

| Version | Date       | Major Changes | Status |
|---------|------------|---------------|--------|
| 1.3.1   | 2026-01-22 | SendGrid API Integration with PHPMailer Fallback | Implemented |
| 1.3.0   | 2026-01-18 | Stalls Management & Display Enhancement | Implemented |
| 1.2.0   | 2026-01-15 | 2FA & Trusted Device System | Implemented |
| 1.1.0   | 2026-01-15 | Password Reset Feature | Implemented |
| 1.0.0   | TBD        | Initial Release | Pending |

---

## **UPCOMING FEATURES (Planned)**

- [x] Two-factor authentication (2FA)
- [x] Trusted device management
- [ ] App Rating

---

## **KNOWN ISSUES**

- None currently reported

---

## **DEPENDENCIES**

- PHP 7.4+
- MySQL 5.7+
- PHPMailer (via Composer)
- XAMPP (for local development)

---

## **DEPLOYMENT NOTES**

1. Run all SQL migrations before deploying
2. Configure PHPMailer credentials on production server
3. Update email URLs to match production domain
4. Test password reset flow thoroughly before go-live
5. Monitor error logs for email delivery issues

---

## **TESTING CHECKLIST**

### 2FA & Trusted Device Feature
- [x] 2FA checkbox appears on registration confirmation page
- [x] Trust device checkbox appears on registration confirmation page
- [x] OTP modal displays when 2FA enabled but trust device not checked
- [x] OTP sent to email successfully
- [x] 6-digit OTP input validation works
- [x] Invalid OTP shows error message
- [x] Expired OTP shows expiration message
- [x] Trust device checkbox pre-checked in modal
- [x] Trusted device created in database after OTP verification
- [x] User redirects to dashboard after OTP verification
- [x] Direct redirect when trust device checked during registration
- [x] Login supports both email and tenant_id
- [x] Trusted device skips OTP on login
- [x] New device on login triggers OTP

### Password Reset Feature
- [x] Forgot password page loads correctly
- [x] Email validation works
- [x] Reset token generates successfully
- [x] Password reset email sends
- [x] Reset link is clickable and valid
- [x] Token expiration validation works
- [x] Password confirmation validation works
- [x] New password is hashed correctly
- [x] User can login with new password
- [x] Expired token shows error message

---

## **Contact & Support**

For issues or feature requests, please contact the development team or open an issue in the project repository.

---

**Last Updated**: January 22, 2026  
**Project**: RentFlow - Property Rental Management System
