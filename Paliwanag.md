# RentFlow Defense Questions & Answers

## 1. General System Questions

**What problem does RentFlow solve?**
RentFlow addresses the inefficiencies and errors of manual rent tracking in public markets. It automates tenant management, stall assignments, payment tracking, arrears calculation, and reporting, reducing paperwork and human error.

**Who are the primary users of your system?**
The main users are market administrators and tenants. Admins manage tenants and stalls, while tenants handle their accounts and payments.

**How is RentFlow different from traditional manual rent tracking?**
RentFlow automates processes, provides real-time data, reduces paperwork, and minimizes errors. It offers instant reporting, secure data storage, and user-friendly interfaces compared to manual ledgers.

**What are the main objectives of the system?**
- Streamline tenant and stall management
- Automate payment and arrears tracking
- Enhance reporting and analytics
- Improve security and data integrity
- Provide a user-friendly experience

**Why did you choose a web-based system instead of a desktop application?**
A web-based system is accessible from any device with a browser, supports multiple users simultaneously, and is easier to maintain and update compared to desktop apps.

**What are the advantages of using RentFlow in a public market environment?**
- Centralized data management
- Faster tenant processing
- Automated payment reminders and penalties
- Easy access for admins and tenants
- Improved transparency and accountability

**What are the limitations of your system?**
- Dependent on internet connectivity
- Limited offline functionality
- Initial learning curve for non-technical users
- Scalability may require server upgrades for very large markets

## 2. System Architecture Questions

**Can you explain the architecture of RentFlow?**
RentFlow uses a PHP backend with MySQL for data storage. The system follows a modular structure, separating admin and tenant functionalities into different folders. The frontend uses HTML, CSS, and JavaScript.

**Why did you choose PHP and MySQL for this system?**
PHP and MySQL are widely supported, cost-effective, and suitable for rapid web development. They are easy to deploy on common hosting platforms like XAMPP.

**How does your system implement role-based access control?**
User roles (admin, tenant) are stored in the database. Access to pages and actions is checked using session variables and role validation in PHP scripts.

**How does the system separate admin and tenant functionality?**
Separate folders (`admin/`, `tenant/`) contain role-specific pages. Access is restricted based on user role, and navigation menus are customized per role.

**How does the system handle session management?**
Sessions are managed using PHP's `$_SESSION` superglobal. User authentication sets session variables, and session checks are performed on protected pages.

**Why did you choose PDO instead of MySQLi?**
PDO supports prepared statements (preventing SQL injection), is database-agnostic, and offers better error handling compared to MySQLi.

**How does the system ensure scalability if the number of tenants increases?**
The database is normalized, queries are optimized, and pagination is used for large data sets. The modular codebase allows for future scaling and server upgrades.

## 3. Database Questions

**What are the main tables in your database?**
- users
- tenants
- stalls
- leases
- payments
- arrears
- messages
- notifications

**What is the relationship between users, leases, and stalls?**
A user (tenant) can have one or more leases. Each lease is linked to a specific stall. Stalls can only be assigned to one active lease at a time.

**Why did you normalize your database?**
Normalization reduces data redundancy, ensures data integrity, and simplifies updates and queries.

**How does the system track payment history?**
All payments are recorded in the `payments` table, linked to tenants and leases. Payment status and history can be viewed by both admins and tenants.

**How do you prevent duplicate stall assignments?**
Before assigning a stall, the system checks if the stall is already assigned to an active lease. This prevents double-booking.

**What happens to the database record when a lease is terminated?**
The lease status is updated to 'terminated', and the stall becomes available for reassignment. Historical data is retained for reporting.

**Why did you use ENUM for stall types?**
ENUM restricts stall types to predefined values (e.g., Wet, Dry, Apparel), ensuring data consistency and simplifying queries.

**Why did you choose Wet, Dry, and Apparel as stall types?**
These categories reflect common types of stalls in public markets, making management and reporting more organized.

## 4. Security Questions

**How does RentFlow protect user passwords?**
Passwords are hashed using bcrypt before storage in the database.

**Why did you choose bcrypt hashing?**
Bcrypt is a strong, adaptive hashing algorithm that protects against brute-force attacks.

**How does the system prevent SQL injection attacks?**
All database queries use prepared statements via PDO, which separates SQL logic from user input.

**What security measures protect user sessions?**
Sessions use secure, HTTP-only cookies. Session IDs are regenerated on login, and session timeouts are enforced.

**How does the Two-Factor Authentication (2FA) work?**
After login, users enter a code sent to their email. The code is time-limited and must match the server-generated value.

**What happens if a user enters the wrong 2FA code?**
The login is denied, and the user is prompted to re-enter the code. Multiple failed attempts may trigger a temporary lockout.

**How do you prevent Cross-Site Scripting (XSS)?**
User input is sanitized and output is escaped using PHP functions like `htmlspecialchars()`.

**How do you secure file uploads for business permits and IDs?**
Uploads are restricted to specific file types (e.g., PDF, JPG, PNG), file size limits are enforced, and files are stored outside the web root when possible.

**What happens if someone tries to upload a malicious file?**
The upload is rejected based on file type and content checks. Suspicious files are not saved, and the user is notified.

## 5. System Features Questions

### Tenant Management

**How does the system register a new tenant?**
Tenants register via a web form. Admins review and approve applications before granting access.

**How does the system assign a stall to a tenant?**
Admins assign available stalls to approved tenants. The system checks for availability before assignment.

**What happens when a tenant transfers to another stall?**
The current lease is updated or terminated, and a new lease is created for the new stall. Payment and arrears records are adjusted accordingly.

### Payment Management

**How does the system handle partial payments?**
Partial payments are recorded, and the remaining balance is tracked in the arrears table. Tenants can pay the balance later.

**How are arrears calculated?**
Arrears are calculated as the difference between the total amount due and the amount paid, including penalties for overdue payments.

**How does the system detect overdue payments?**
A scheduled script checks payment due dates and flags overdue accounts. Notifications are sent to tenants and admins.

**How does the 2% penalty calculation work?**
A 2% penalty is added to the overdue amount for each month missed. The system automatically updates arrears with penalties.

**If a tenant fails to pay for two months, how does your system calculate the arrears?**
The system adds the unpaid rent for both months plus a 2% penalty for each month overdue, compounding as necessary.

## 6. Workflow Questions

**What happens when a tenant submits a stall application?**
The application is saved in the database and marked as pending. Admins review and approve or reject the application.

**What happens after an admin approves an application?**
The tenant is notified, and a lease is created. The tenant can then access their account and assigned stall.

**What process occurs when a payment is recorded?**
The payment is saved in the payments table, arrears are updated, and receipts are generated. Notifications are sent to the tenant.

**What happens when a tenant terminates a lease?**
The lease status is set to terminated, the stall becomes available, and the tenant's access is updated.

**What happens if a tenant forgets their password?**
The tenant can request a password reset link via email. After verification, they can set a new password.

## 7. Reporting & Analytics Questions

**What reports does your system generate?**
- Monthly revenue
- Payment history
- Arrears and overdue accounts
- Stall occupancy
- Tenant lists

**How does the system calculate monthly revenue?**
It sums all payments received within the month, grouped by date and stall type.

**Why did you include data export features?**
To allow admins to analyze data externally, share reports, and comply with record-keeping requirements.

**What file formats can your system export?**
CSV, Excel, PDF, and PNG for charts.

**How can administrators use these reports for decision making?**
Reports help identify trends, monitor arrears, optimize stall allocation, and support financial planning.

## 8. Messaging System Questions

**Why did you include a messenger-style chat system?**
To facilitate direct communication between tenants and admins, improving support and transparency.

**How does the messaging system store conversation data?**
Messages are stored in the `messages` table, linked to sender and receiver IDs, with timestamps.

**How do tenants receive notifications when they receive a message?**
Notifications are generated and displayed in the user dashboard. Email alerts may also be sent.

**Does your system support real-time messaging or polling?**
The system uses polling (AJAX) to check for new messages at regular intervals.

## 9. UI / Usability Questions

**How did you ensure your system is user-friendly?**
By using clear navigation, responsive design, tooltips, and color-coded indicators. User feedback was considered in the design.

**Why did you include color-coded payment status indicators?**
To help users and admins quickly identify payment status (e.g., paid, overdue, partial) at a glance.

**Why is responsive design important for this system?**
Many users access the system from mobile devices. Responsive design ensures usability across all screen sizes.

**How does the system help admins quickly find tenant information?**
Search and filter features, as well as organized tables and dashboards, allow quick access to tenant data.

## 10. Technical Implementation Questions

**How does the system generate payment records automatically?**
A scheduled script (cron job) creates payment records for each active lease at the start of each billing period.

**How do you implement modals for payment actions?**
Modals are implemented using JavaScript and Bootstrap, allowing users to perform actions without leaving the page.

**How does the system retrieve data from the database?**
PHP scripts use PDO to execute SQL queries and fetch results, which are then displayed in the frontend.

**How do charts get their data for reports?**
AJAX requests fetch data from PHP scripts, which query the database and return JSON for chart rendering (e.g., using Chart.js).

**How do you implement file uploads for tenant applications?**
File inputs in forms allow uploads. PHP scripts validate and store files in the `uploads/` directory, updating the database with file paths.

## 11. Edge Case / Critical Thinking Questions

**What happens if two admins assign the same stall simultaneously?**
The system checks stall availability before assignment. If a race condition occurs, only the first assignment succeeds; the second receives an error.

**What happens if the database connection fails?**
The system displays an error message and logs the issue. No data is processed until the connection is restored.

**How would your system handle 1000+ tenants?**
Pagination, optimized queries, and server upgrades ensure performance. The modular design supports scaling.

**How would you improve RentFlow in the future?**
- Add mobile app support
- Integrate online payment gateways
- Enhance analytics and reporting
- Implement real-time messaging

**What additional features would you add?**
- Mobile notifications
- Advanced search and filtering
- API for third-party integration
- More detailed audit logs

**If internet connection is lost while recording a payment, what will happen?**
The payment is not saved. The user is prompted to retry once the connection is restored.

## 12. Possible "Trap Questions"

**Why didn't you use a framework like Laravel?**
To keep the system lightweight, easy to deploy, and maintain for small teams. Vanilla PHP offers more control and lower hosting requirements.

**Why didn't you use NoSQL instead of MySQL?**
Relational data (tenants, leases, payments) fits best with SQL. MySQL offers strong consistency and is widely supported.

**What are the disadvantages of your system?**
- Dependent on internet and server uptime
- Limited offline support
- Manual scaling for very large markets
- Lacks some advanced features of larger frameworks

**Why did you choose Vanilla JavaScript instead of React or Vue?**
Vanilla JS is simpler for small to medium projects, reduces dependencies, and is easier for new developers to maintain.

**How would you deploy this system in a real public market?**
Host on a secure web server, train staff, migrate existing data, and provide user support during rollout.

## 13. Common Final Defense Questions

**What is the most challenging part of developing RentFlow?**
Designing a secure, user-friendly system that handles complex workflows and edge cases was the biggest challenge.

**What part of the system are you most proud of?**
The automated payment and arrears tracking, as well as the integrated messaging system.

**If you had more time, what would you improve?**
Add more automation, real-time features, and a mobile app for tenants and admins.

**How can this system benefit local government markets?**
It streamlines operations, increases transparency, reduces errors, and improves tenant satisfaction.

**Do you believe your system is ready for real-world deployment?**
Yes, with proper testing and user training, RentFlow is ready for deployment in public markets.
