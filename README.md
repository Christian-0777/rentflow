# RentFlow

**A Web-Based Rent Management System**

RentFlow is a web-based rent management system designed to help administrators efficiently manage tenants, rent payments, arrears, and notifications. The system supports both **desktop and mobile devices**, ensuring accessibility across platforms.

---

## 📌 System Requirements

* Windows OS
* XAMPP (Apache, MySQL, PHP)
* MySQL Workbench CE (optional)
* Composer
* Git
* Web Browser (Chrome, Firefox, Edge, Safari)
* Android or iOS device (for mobile testing)

---

## 1️⃣ Install XAMPP

1. Download XAMPP:
   [https://www.apachefriends.org/download.html](https://www.apachefriends.org/download.html)
2. Install the following components:

   * Apache
   * MySQL
   * PHP
3. Open **XAMPP Control Panel** and start:

   * **Apache**
   * **MySQL**

---

## 2️⃣ Set Up the Database

### Option A: Using MySQL Workbench

1. Download MySQL Workbench CE:
   [https://dev.mysql.com/downloads/workbench/](https://dev.mysql.com/downloads/workbench/)
2. Connect to MySQL:

   * Hostname: `127.0.0.1`
   * Port: `3306`
   * Username: `root`
   * Password: *(leave empty if default)*
3. Create the database:

   ```sql
   CREATE DATABASE rentflow_db;
   USE rentflow_db;
   ```

---

### Option B: Using phpMyAdmin

1. Open:

   ```
   http://localhost/phpmyadmin
   ```
2. Import the schema files in the following order:

   * First, import `sql/rentflow_schema_1.sql` to create the database
   * Then, import `sql/rentflow_schema_2.sql` to create the database user and set permissions
   * Finally, import `sql/rentflow_schema_3.sql` to create all tables

3. The database will be created as `rentflow` with the user `rentflow_team` (password: `rentflow_3006`)

---

## 3️⃣ Clone RentFlow into XAMPP

1. Open **Command Prompt** or **Git Bash**
2. Navigate to XAMPP `htdocs`:

   ```bash
   cd C:\xampp\htdocs
   ```
3. Clone the repository:

   ```bash
   git clone https://github.com/Christian-0777/rentflow.git
   ```

The project will be located at:

```
C:\xampp\htdocs\rentflow
```

---

## 4️⃣ Install Composer & Dependencies

1. Download Composer:
   [https://getcomposer.org/download/](https://getcomposer.org/download/)
2. Install Composer globally
3. Open **Command Prompt** and navigate to the project:

   ```bash
   cd C:\xampp\htdocs\rentflow
   ```
4. Install Composer, PHPMailer, and SendGrid API dependencies:

   ```bash
   composer require phpmailer/phpmailer sendgrid/sendgrid
   ```

This will generate the `vendor/` folder with all required dependencies.

---

## 5️⃣ Configure Environment Variables

1. Copy the example environment file:

   ```bash
   copy .env.example .env
   ```
2. Edit `.env` and update the values:

   ```env
   # Database Configuration
   DB_HOST=localhost
   DB_NAME=rentflow_db
   DB_USER=root
   DB_PASS=

   # SMTP Configuration
   MAIL_HOST=smtp.gmail.com
   MAIL_PORT=587
   MAIL_USERNAME=your-email@gmail.com
   MAIL_PASSWORD=your-app-password
   MAIL_FROM=no-reply@rentflow.local
   MAIL_FROM_NAME=RentFlow Team

   # Application Settings
   APP_ENV=development
   APP_DEBUG=true
   APP_NAME=RentFlow

   # Penalty Configuration
   PENALTY_RATE=0.02
   ```

---

## 6️⃣ Gmail App Password Setup (For Email Notifications)

1. Enable **2-Step Verification** on your Google Account
2. Go to **Google Account → Security → App Passwords**
3. Generate an App Password for **Mail**
4. Use this password in the `.env` file

---

## 7️⃣ Run the Project

1. Ensure **Apache** and **MySQL** are running
2. Open a browser and go to:

   ```
   http://localhost/rentflow/
   ```
3. The RentFlow homepage should load successfully

---

## 8️⃣ How Others Can Clone and Run RentFlow

After installing XAMPP, Git, and Composer:

```bash
cd C:\xampp\htdocs
git clone https://github.com/Christian-0777/rentflow.git
cd rentflow
composer install
```

* Configure `.env`
* Import the database
* Start Apache and MySQL
* Access via `http://localhost/rentflow/`

---

## 9️⃣ Test on a Real Mobile Device (Highly Recommended)

RentFlow is responsive and supports **desktop, Android, and iOS** devices.

---

### 9.1 Requirements

* PC and mobile device connected to the **same Wi-Fi**
* Apache and MySQL running
* RentFlow accessible on PC

---

### 9.2 Get Your PC IP Address

Open **Command Prompt**:

```bash
ipconfig
```

Copy the IPv4 Address, e.g.:

```
192.168.1.10
```

---

### 9.3 Allow Apache Through Windows Firewall

1. Press **Windows + R**
2. Type:

   ```
   firewall.cpl
   ```
3. Click **Allow an app through Windows Defender Firewall**
4. Ensure **Apache HTTP Server** is allowed on **Private networks**
5. Restart Apache

---

### 9.4 Test on Android

1. Open **Chrome** on Android
2. Enter:

   ```
   http://YOUR_PC_IP/rentflow/
   ```

Example:

```
http://192.168.1.10/rentflow/
```

---

### 9.5 Test on iOS (iPhone / iPad)

1. Open **Safari**
2. Enter:

   ```
   http://YOUR_PC_IP/rentflow/
   ```

Ensure the device is on the same Wi-Fi network.

---

### 9.6 Notes

* No database changes are required
* PHP and MySQL run on the PC
* Mobile devices access RentFlow via browser only

---

## ✅ Project Status

✔ Local development ready
✔ Desktop and mobile supported
✔ Android and iOS tested
✔ Email notifications enabled

---


