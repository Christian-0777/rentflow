# RentFlow Project Structure

```
rentflow/
├── admin/
│   ├── account.php
│   ├── contact.php
│   ├── dashboard.php
│   ├── login.php
│   ├── logout.php
│   ├── notifications.php
│   ├── payments.php
│   ├── reports.php
│   ├── stalls.php
│   ├── tenant_profile.php
│   └── tenants.php
├── api/
│   ├── approve_application.php
│   ├── arrears_history.php
│   ├── chart_data.php
│   ├── chat_fetch.php
│   ├── chat_send.php
│   ├── delete_tenant.php
│   ├── export_csv.php
│   ├── export_excel.php
│   ├── export_pdf.php
│   ├── export_png.php
│   ├── get_application_details.php
│   ├── pay_arrear.php
│   ├── payments_record.php
│   ├── penalties_cron.php
│   ├── receipts.php
│   └── stalls_apply.php
├── chat/
│   ├── chat.php
│   └── notify.php
├── config/
│   ├── auth.php
│   ├── constants.php
│   ├── db.php
│   ├── env.php
│   ├── mailer.php
│   └── security.php
├── public/
│   ├── assets/
│   │   ├── css/
│   │   │   ├── auth-common.css
│   │   │   ├── layout.css
│   │   │   ├── login.css
│   │   │   ├── public-landing.css
│   │   │   ├── signup.css
│   │   │   ├── tenant-bootstrap.css
│   │   │   ├── tenant-sidebar.css
│   │   │   └── verify_2fa.css
│   │   └── js/
│   │       ├── charts.js
│   │       ├── notifications.js
│   │       ├── table.js
│   │       ├── ui.js
│   │       └── verify_2fa.js
│   ├── api/
│   │   └── stalls_apply.php
│   ├── uploads/
│   │   ├── New Text Document (2).txt
│   │   ├── New Text Document (3).txt
│   │   ├── New Text Document.txt
│   │   └── renflowstructure.txt
│   ├── confirm.php
│   ├── forgot_password.php
│   ├── index.php
│   ├── login.php
│   ├── logout.php
│   ├── register.php
│   ├── reset_password.php
│   ├── terms_accept.php
│   └── verify_2fa.php
├── sql/
│   ├── rentflow_schema_1.sql
│   ├── rentflow_schema_2.sql
│   ├── rentflow_schema_3.sql
│   └── seed.sql
├── tenant/
│   ├── account.php
│   ├── dashboard.php
│   ├── notifications.php
│   ├── payments.php
│   ├── profile.php
│   ├── stalls.php
│   └── support.php

├── uploads/
│   └── stalls/
├── vendor/
│   ├── autoload.php
│   ├── composer/
│   ├── phpmailer/
│   ├── sendgrid/
│   └── starkbank/
├── composer.json
├── index.html
├── PASSWORD_RESET_FEATURE.md
├── README.md
├── REPORTS_COMPLETE_GUIDE.md
├── REPORTS_PAGE_DOCUMENTATION.md
├── REPORTS_QUICK_REFERENCE.md
├── REPORTS_UPDATE_SUMMARY.md
├── SENDGRID_API_QUICK_REFERENCE.txt
├── SENDGRID_API_SETUP.md
├── TENANT_CSS_OVERHAUL.md
├── UPDATE.md
└── Folder_Structure.md
```

## Directory Descriptions

| Directory | Purpose |
|-----------|---------|
| `admin/` | Admin panel pages and dashboard functionality |
| `api/` | Backend API endpoints for data processing |
| `chat/` | Chat and messaging functionality |
| `config/` | Configuration files for database, auth, mailer |
| `public/` | Public-facing pages and assets |
| `sql/` | Database schemas and seed data |
| `tenant/` | Tenant-specific pages and features |

| `uploads/` | User-uploaded files storage |
| `vendor/` | Composer dependencies (PHPMailer, SendGrid, etc.) |
