# SendGrid API Setup Guide

## Overview
SendGrid is configured as the primary email service for RentFlow, with PHPMailer as a fallback. This guide will walk you through obtaining and configuring your SendGrid API key.

---

## 📋 Prerequisites

- Active SendGrid account (free or paid)
- Access to your RentFlow `.env` file
- Text editor for configuration

---

## 1️⃣ Create a SendGrid Account

1. Visit [SendGrid Sign Up](https://signup.sendgrid.com/)
2. Complete the registration form with:
   - Email address
   - Password (strong)
   - Company name
   - Use case selection

3. Verify your email address via the confirmation link
4. Log in to your SendGrid dashboard

---

## 2️⃣ Create an API Key

1. In the SendGrid dashboard, go to: **Settings** → **API Keys**
2. Click the **Create API Key** button
3. Fill in the API Key details:
   - **API Key Name**: `RentFlow_Production` (or your preferred name)
   - **API Key Type**: Select **Full Access** (or customize permissions)
   
4. Click **Create & Verify**
5. **Important**: Copy the API key immediately and save it securely
   - ⚠️ SendGrid only shows the key once - you cannot retrieve it later
   - If lost, you'll need to create a new one

---

## 3️⃣ Configure Sender Identity

SendGrid requires verification of your sender email address.

### Option A: Single Sender Authentication (Recommended for Development)

1. Go to: **Settings** → **Sender Authentication**
2. Click **Single Sender Verification**
3. Enter your sender email details:
   - **From Email Address**: `noreply@yourdomain.com` or `support@yourdomain.com`
   - **From Name**: `RentFlow Team` (or your preferred name)
   - **Reply-To Email**: `support@yourdomain.com` (optional)
   - **Company Address**: Your organization's address

4. Click **Create**
5. Check the email sent to the address you provided
6. Click the verification link in the email

### Option B: Domain Authentication (Recommended for Production)

1. Go to: **Settings** → **Sender Authentication**
2. Click **Authenticate Your Domain**
3. Select your domain or add a new one
4. Follow the DNS setup instructions provided by SendGrid
5. Once DNS records are verified, your domain is authenticated

---

## 4️⃣ Update Your `.env` File

Add the following configuration to your `.env` file:

```env
# SendGrid Configuration
SENDGRID_API_KEY=SG.your_actual_api_key_here

# SMTP Fallback Configuration (Optional)
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_FROM=noreply@yourdomain.com
MAIL_FROM_NAME=RentFlow Team
```

### Example:
```env
SENDGRID_API_KEY=SG.xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
MAIL_FROM=noreply@rentflow.local
MAIL_FROM_NAME=RentFlow Team
```

---

## 5️⃣ Verify Configuration in PHP

The configuration is loaded in [config/mailer.php](config/mailer.php):

```php
// SendGrid Configuration
define('SENDGRID_API_KEY', env('SENDGRID_API_KEY', ''));

// SMTP Fallback Configuration
define('MAIL_FROM', env('MAIL_FROM', 'no-reply@rentflow.local'));
define('MAIL_FROM_NAME', env('MAIL_FROM_NAME', 'Rentflow Team'));
```

---

## 6️⃣ Test Your SendGrid Integration

### Test via RentFlow

1. Log in to RentFlow as an admin
2. Perform an action that sends an email:
   - Reset a user password
   - Send a notification
   - Approve a tenant application

3. Check if the email is delivered successfully

### Test via SendGrid Dashboard

1. Go to SendGrid Dashboard → **Mail Send**
2. View email statistics and delivery logs
3. Verify that emails from RentFlow are appearing

### Test via Command Line (Optional)

```bash
cd C:\xampp\htdocs\rentflow
php -r "
require_once 'config/env.php';
require_once 'vendor/autoload.php';
use SendGrid\Mail\Mail;

\$mail = new Mail();
\$mail->setFrom('noreply@rentflow.local', 'RentFlow Team');
\$mail->setSubject('Test Email');
\$mail->addTo('your-email@example.com');
\$mail->addContent('text/html', '<p>This is a test email from RentFlow using SendGrid.</p>');

\$sendgrid = new \SendGrid(env('SENDGRID_API_KEY'));
\$response = \$sendgrid->send(\$mail);

echo 'Status Code: ' . \$response->statusCode() . PHP_EOL;
"
```

---

## 7️⃣ SendGrid Features for RentFlow

### Email Categories
RentFlow can categorize emails for better tracking:
- `payment-notifications` - Rent payment reminders
- `application-updates` - Tenant application status
- `password-resets` - User password reset emails
- `system-alerts` - System notifications

### Monitoring & Analytics

1. **Activity Feed**: **Mail Send** → **Overview**
   - Track bounces, opens, clicks
   
2. **Alerts**: **Settings** → **Mail Settings**
   - Set up notifications for bounces and unsubscribes

3. **Unsubscribe Management**: **Settings** → **Unsubscribe Groups**
   - Manage recipient preferences

---

## 8️⃣ Troubleshooting

### Issue: "Invalid API Key"
- Verify the API key is correct and properly copied
- Check that `SENDGRID_API_KEY` is set in `.env`
- Ensure the `.env` file is in the root directory

### Issue: "Invalid From Address"
- Verify the sender email is authenticated in SendGrid
- Use the exact email address configured in SendGrid
- Update `MAIL_FROM` in `.env` to match

### Issue: "Email Not Received"
- Check SendGrid Mail Send logs for bounces or errors
- Verify recipient email address is correct
- Check spam/junk folder
- Confirm domain authentication is complete

### Issue: "Fallback to PHPMailer"
- Check error logs: `error_log()` will indicate the issue
- Verify SMTP credentials if using Gmail as fallback
- Ensure Google App Password is used (not regular password)

---

## 9️⃣ Environment Configuration Reference

| Setting | Value | Description |
|---------|-------|-------------|
| `SENDGRID_API_KEY` | `SG.xxx...` | SendGrid API key (required) |
| `MAIL_FROM` | `noreply@yourdomain.com` | From email address |
| `MAIL_FROM_NAME` | `RentFlow Team` | From display name |
| `MAIL_HOST` | `smtp.gmail.com` | SMTP server (fallback) |
| `MAIL_PORT` | `587` | SMTP port (fallback) |
| `MAIL_USERNAME` | `your-email@gmail.com` | SMTP username (fallback) |
| `MAIL_PASSWORD` | `app-password` | SMTP password (fallback) |

---

## 🔟 Security Best Practices

✅ **DO:**
- Store API keys in `.env` file, never in code
- Use strong, unique passwords for SendGrid account
- Enable two-factor authentication on SendGrid account
- Rotate API keys periodically
- Use separate API keys for development and production

❌ **DON'T:**
- Commit `.env` file to version control
- Share API keys in messages or emails
- Use the same API key across multiple environments
- Store API keys in database or comments

---

## Additional Resources

- [SendGrid Documentation](https://docs.sendgrid.com)
- [SendGrid PHP SDK](https://github.com/sendgrid/sendgrid-php)
- [SendGrid API Reference](https://docs.sendgrid.com/api-reference/)
- [Email Deliverability Best Practices](https://docs.sendgrid.com/glossary/email-deliverability)

---

**Last Updated**: January 24, 2026
**Status**: ✅ Complete
