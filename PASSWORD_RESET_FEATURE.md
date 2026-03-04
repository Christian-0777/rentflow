# Password Reset Feature Documentation

## Overview
A complete password reset feature has been added to the RentFlow login system, allowing tenants to securely reset their passwords via email.

## Files Created/Modified

### Modified Files:
1. **[public/login.php](public/login.php)** - Added "Forgot Password?" link below the login form

### New Files:
1. **[public/forgot_password.php](public/forgot_password.php)** - Initial password reset request page where users enter their email
2. **[public/reset_password.php](public/reset_password.php)** - Password reset confirmation page where users set their new password
3. **[sql/migration_password_reset.sql](sql/migration_password_reset.sql)** - Database migration to add reset token columns

## Features

### 1. Forgot Password Page (`forgot_password.php`)
- Users enter their email address
- System validates email exists in database
- Generates unique 64-character reset token
- Token expires after 24 hours
- Sends HTML email with reset link to user

### 2. Reset Password Page (`reset_password.php`)
- Verifies reset token is valid and not expired
- Users enter new password (minimum 6 characters)
- Password confirmation validation
- Updates password hash in database
- Clears reset token after successful reset

### 3. Email Integration
- Uses existing PHPMailer configuration from `config/mailer.php`
- Sends professional HTML formatted emails
- Includes 24-hour expiration information
- Provides direct reset link and fallback copy-paste URL

## Setup Instructions

### Step 1: Update Database Schema
Run the migration SQL to add password reset columns:

```sql
ALTER TABLE `users` 
ADD COLUMN `password_reset_token` varchar(255) DEFAULT NULL,
ADD COLUMN `password_reset_expires` datetime DEFAULT NULL;

CREATE INDEX `idx_password_reset_token` ON `users`(`password_reset_token`);
```

Or execute the migration file:
```bash
mysql -u root rentflow < sql/migration_password_reset.sql
```

### Step 2: Verify PHPMailer Configuration
Ensure `config/mailer.php` is properly configured with SMTP credentials for email sending.

### Step 3: Update Reset Link URL (if needed)
In `forgot_password.php` line ~48, update the base URL if your application is not at `http://localhost/rentflow/`:
```php
$reset_link = "https://yourdomain.com/public/reset_password.php?token=" . urlencode($reset_token);
```

## User Flow

1. User clicks "Forgot Password?" on login page
2. User enters email address on forgot_password.php
3. System sends reset email with unique link
4. User clicks link in email (valid for 24 hours)
5. User enters new password on reset_password.php
6. Password is updated and token is cleared
7. User can login with new password

## Security Features

- **Unique Tokens**: Each reset uses a 64-character cryptographically random token
- **Token Expiration**: Tokens expire after 24 hours
- **One-Time Use**: Tokens are cleared after password reset
- **Password Hashing**: Passwords are hashed using bcrypt
- **Input Validation**: Email validation and password confirmation
- **Minimum Password Length**: 6 characters (can be increased)

## Database Changes

Two new columns added to `users` table:
- `password_reset_token` (varchar(255), nullable) - Stores unique reset token
- `password_reset_expires` (datetime, nullable) - Stores token expiration time

Index added for faster token lookups:
- `idx_password_reset_token` on `password_reset_token` column

## Testing

To test the feature:

1. Go to `http://localhost/rentflow/public/login.php`
2. Click "Forgot Password?"
3. Enter a valid tenant email
4. Check email (or check PHPMailer logs)
5. Click reset link
6. Enter new password
7. Login with new password

## Notes

- The email link uses `http://localhost` by default - update for production
- Reset tokens are cleared after successful password reset
- Failed reset attempts leave the token active (can retry)
- Expired tokens show an error message with option to request new link
- Admin and Treasury roles are not affected by this feature (tenants only)
