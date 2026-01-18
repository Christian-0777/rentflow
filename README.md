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

## **5. Configure Environment Variables**

1. Copy the example environment file:

```bash
cd C:\xampp\htdocs\rentflow
copy .env.example .env
```

2. Edit the `.env` file with your actual configuration:

```env
# Database Configuration
DB_HOST=localhost
DB_NAME=rentflow
DB_USER=root
DB_PASS=

# SMTP Configuration
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_FROM=no-reply@rentflow.local
MAIL_FROM_NAME=Rentflow Team

# Application Settings
APP_ENV=development
APP_DEBUG=true
APP_NAME=RentFlow

# Penalty Configuration
PENALTY_RATE=0.02
```

3. The configuration will be automatically loaded by the application.

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
