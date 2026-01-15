# RentFlow Installation Guide

This guide will help you set up the RentFlow project locally on Windows using XAMPP, MySQL Workbench CE, and PHPMailer.

---

## **1. Install XAMPP**

1. Download XAMPP: [https://www.apachefriends.org/download.html](https://www.apachefriends.org/download.html)
2. Run the installer and install:
   - Apache
   - MySQL
   - PHP
3. Open **XAMPP Control Panel** and start:
   - **Apache**
   - **MySQL**

---

## **2. Set Up MySQL Database**

1. Download MySQL Workbench CE: [https://dev.mysql.com/downloads/workbench/](https://dev.mysql.com/downloads/workbench/)
2. Open MySQL Workbench and connect to the local MySQL server:
   - Hostname: `127.0.0.1`
   - Port: `3306`
   - Username: `root`
   - Password: leave empty if default
3. Create a database:

```sql
CREATE DATABASE rentflow_db;
USE rentflow_db;
````
4. Or you can use phpMyAdmin:
   - Open http://localhost/phpmyadmin
   - Create calculator_db
   - Import sql/rentflow_schema.sql

---

## **3. Clone RentFlow into XAMPP**

1. Open **Command Prompt** or **Git Bash**.
2. Navigate to `htdocs`:

```bash
cd C:\xampp\htdocs
```

3. Clone the project:

```bash
git clone https://github.com/Christian-0777/rentflow.git
```

* The project will now be at `C:\xampp\htdocs\rentflow`.

---

## **4. Install Composer & PHPMailer**

1. Download Composer: [https://getcomposer.org/download/](https://getcomposer.org/download/)
2. Install globally on your system.
3. Clone Composer + PHPMailer setup into project root:

```bash
cd C:\xampp\htdocs\rentflow
git clone https://github.com/Christian-0777/composerandphpmailer.git
```

4. Install dependencies:

```bash
cd composerandphpmailer
composer install
```

* This creates the `vendor/` folder in your project with PHPMailer.

---

## **5. Configure `mailer.php`**

1. Place `mailer.php` inside `rentflow/config`.
2. Edit it with your Gmail credentials:

```php
<?php
// config/mailer.php
// PHPMailer SMTP configuration for sending real emails

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Make sure PHPMailer is installed via Composer:
//   composer require phpmailer/phpmailer
require __DIR__ . '/../vendor/autoload.php';

// SMTP Configuration Constants
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'your_email@gmail.com');  // SMTP username
define('MAIL_PASSWORD', 'your_app_password');     // SMTP username
define('MAIL_FROM', 'no-reply@rentflow.local');

/**
 * Send an email using PHPMailer + SMTP
 *
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $body HTML body content
 * @return bool True if sent successfully, false otherwise
 */
function send_mail($to, $subject, $body) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();                                      // Use SMTP
        $mail->Host       = 'smtp.gmail.com';                 // SMTP server (e.g., smtp.gmail.com)
        $mail->SMTPAuth   = true;                             // Enable authentication
        $mail->Username   = 'your_email@gmail.com';           // SMTP username
        $mail->Password   = 'your_app_password';              // SMTP password or app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;   // Encryption (TLS recommended)
        $mail->Port       = 587;                              // TCP port (587 for TLS, 465 for SSL)

        // Sender info
        $mail->setFrom('no-reply@rentflow.local', 'Rentflow Team');

        // Recipient
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);                                  // Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);                   // Fallback plain text

        // Send email
        return $mail->send();

    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
```
## **6. To get the your_email@gmail.com and your_app_password
* Gmail App Password Setup
* Enable 2-Step Verification on Google Account
* Go to Google → App Passwords
* Generate a password for Mail
* Use that password in mailer.php

---

## **7. Run the Project**

1. Make sure **Apache** and **MySQL** are running in XAMPP.
2. Open a browser:

```
http://localhost/rentflow/
```

* You should see the project homepage.

---

## **8. How Others Can Clone**

After XAMPP and Composer are installed:

```bash
cd C:\xampp\htdocs
git clone https://github.com/Christian-0777/rentflow.git
cd rentflow
composer install
```

* Configure `mailer.php` with Gmail credentials.
* Import the database using MySQL Workbench if needed.

---

✅ **The project is now ready to run locally.**
