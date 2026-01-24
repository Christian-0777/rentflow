# RentFlow Database Security Migration Guide

## Overview
This guide explains how to migrate your existing RentFlow database to use the new secure database user `rentflow_team` with proper security configurations.

## Prerequisites
- MySQL/MariaDB server running
- Root or admin access to MySQL
- phpMyAdmin access or MySQL client command line

## Migration Steps

### Step 1: Run the Security Migration SQL Script

#### Option A: Using phpMyAdmin (Web Interface)
1. Open phpMyAdmin in your browser (usually `http://localhost/phpmyadmin`)
2. Go to the **SQL** tab
3. Copy the contents of `sql/migration_security.sql`
4. Paste it into the SQL query editor
5. Click **Go** to execute

#### Option B: Using MySQL Command Line
```bash
# Navigate to your project directory
cd c:\xampp\htdocs\rentflow

# Run the migration script as root user
mysql -u root -p < sql/migration_security.sql

# When prompted, enter your MySQL root password
```

#### Option C: Using XAMPP MySQL Console
1. Open XAMPP Control Panel
2. Start Apache and MySQL
3. Click "Shell" button in MySQL row
4. Run: `mysql -u root`
5. Paste the migration script contents

### Step 2: Verify the User Creation

After running the migration, verify the `rentflow_team` user was created:

```sql
-- Check if user exists
SELECT user, host FROM mysql.user WHERE user = 'rentflow_team';

-- Check user permissions
SHOW GRANTS FOR 'rentflow_team'@'localhost';
```

Expected output should show:
- User: `rentflow_team`
- Host: `localhost`
- Permissions: SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER, EXECUTE

### Step 3: Verify .env Configuration

Ensure your `.env` file has the correct credentials:

```env
DB_HOST=localhost
DB_NAME=rentflow
DB_USER=rentflow_team
DB_PASS=rentflow_3006
DB_PORT=3306
DB_CHARSET=utf8mb4
```

### Step 4: Test Database Connection

Test your application's connection to verify everything works:

1. Start Apache and MySQL in XAMPP
2. Navigate to your application in browser: `http://localhost/rentflow`
3. Log in and check that database operations work normally
4. Check browser console and PHP error logs for any connection issues

### Step 5: Update Your Application Code

To fully utilize the security features, update your queries to use the new security class:

#### Old Way (Vulnerable to SQL Injection):
```php
$result = $pdo->query("SELECT * FROM users WHERE email = '$email'");
```

#### New Way (Secure):
```php
require_once __DIR__ . '/config/security.php';

$user = DatabaseSecurity::fetchOne($pdo, 
    "SELECT * FROM users WHERE email = ?", 
    [$email]
);
```

### Step 6: Add Security Headers to Your Application

In your main layout file (e.g., `public/index.php` or your header template), add:

```php
<?php
require_once __DIR__ . '/../config/security.php';

// Set security headers
setSecurityHeaders();

// Configure session security
configureSessionSecurity();
?>
```

## What Changed

### Database User Permissions
**Before:** Using `root` user with full database privileges
**After:** Using `rentflow_team` with only necessary permissions

| Permission | Before | After |
|-----------|--------|-------|
| SELECT | Yes | Yes |
| INSERT | Yes | Yes |
| UPDATE | Yes | Yes |
| DELETE | Yes | Yes |
| CREATE | Yes | Yes |
| DROP | Yes | Yes |
| INDEX | Yes | Yes |
| ALTER | Yes | Yes |
| EXECUTE | Yes | Yes |
| GRANT | No | No |
| SUPER | No | No |

### Database Connection Options
**New secure PDO options:**
- `PDO::ATTR_EMULATE_PREPARES => false` - Uses native prepared statements
- `PDO::ATTR_PERSISTENT => false` - Disables persistent connections
- `PDO::ATTR_TIMEOUT => 5` - Connection timeout protection
- Strict SQL mode enabled

### Security Features Added
1. **Parameterized Queries** - Prevents SQL injection
2. **Password Hashing** - bcrypt with cost factor 12
3. **Session Security** - HTTPOnly, SameSite, Secure flags
4. **Security Headers** - XSS, Clickjacking, MIME sniffing protection
5. **Login Tracking** - Last login, login attempts, account locking
6. **Audit Columns** - Created/updated timestamps for all data

## Troubleshooting

### Issue: "Access denied for user 'rentflow_team'@'localhost'"
**Solution:** 
- Verify the migration script executed successfully
- Check that credentials in `.env` match the database
- Ensure MySQL is running
- Run the verification queries in Step 2

### Issue: "Unknown column 'twofa_enabled'"
**Solution:**
- The migration script fixes this automatically
- The column name is now `two_factor_enabled`
- Ensure migration script ran completely

### Issue: "Connection timeout"
**Solution:**
- Check MySQL is running in XAMPP
- Verify database host is correct (localhost)
- Check network connectivity
- Increase timeout in db.php if needed

### Issue: "Queries still failing"
**Solution:**
- Ensure all queries use parameterized statements
- Use `DatabaseSecurity::executeQuery()` for all database operations
- Check PHP error logs for detailed error messages
- Verify SQL mode is not too strict for your data

## Security Best Practices

### For Production Deployment
1. **Change the password** - Replace `rentflow_3006` with a strong random password
2. **Use SSL connections** - Set up MySQL SSL certificates
3. **Restrict host** - Consider restricting user to specific IP instead of localhost
4. **Enable query logging** - Set up MySQL query log for audit trail
5. **Regular backups** - Implement automated database backups
6. **User accounts** - Create separate read-only user for reporting

### Recommended Changes for Production
```sql
-- Restrict user to specific IP (replace with actual IP)
CREATE USER 'rentflow_team'@'192.168.1.100' IDENTIFIED BY 'strong-random-password';

-- Create read-only user for reports
CREATE USER 'rentflow_reports'@'localhost' IDENTIFIED BY 'another-strong-password';
GRANT SELECT ON `rentflow`.* TO 'rentflow_reports'@'localhost';

-- Enable SSL requirement
REQUIRE SSL FOR 'rentflow_team'@'localhost';
```

## Files Modified/Created

- `.env` - Updated with new database credentials
- `config/db.php` - Enhanced with security options
- `config/security.php` - New security class and helpers
- `sql/seed.sql` - Updated with user creation SQL
- `sql/migration_security.sql` - New migration script

## Verification Checklist

- [ ] Migration script executed without errors
- [ ] `rentflow_team` user created successfully
- [ ] `.env` file updated with correct credentials
- [ ] PHP application connects to database without errors
- [ ] All database operations work normally
- [ ] No sensitive errors shown to users
- [ ] Security headers are being sent
- [ ] Session cookies are HTTPOnly
- [ ] Queries use parameterized statements

## Support

If you encounter issues:
1. Check PHP error logs: `php_errors.log` in XAMPP
2. Check MySQL error logs in XAMPP MySQL folder
3. Verify credentials in `.env` match database user
4. Run verification queries in Step 2
5. Check that migration script executed completely

For more information on the security features, see `config/security.php` documentation.
