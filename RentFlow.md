<!-- RentFlow project documentation (expanded API + DB schema + UI flows) -->

# RentFlow Documentation (Expanded)

This document expands the API section with exact request parameters and example responses, adds a database schema appendix, and documents per-page UI flows for admin, tenant, and treasury areas. Sources are the live code under `api/`, `public/api/`, `admin/`, `tenant/`, `treasury/`, and `sql/rentflow_schema.sql`.

---

## API Reference (Exact Parameters + Example Responses)

Notes:
- Auth is enforced in the PHP endpoint via `require_role(...)` where present.
- Some endpoints return JSON, others redirect or render HTML.
- All paths below are relative to the app root `/rentflow`.

### `GET /api/get_application_details.php`
Purpose: Fetch stall application details (admin-only).
Auth: `admin`.

Request parameters:
- Query: `id` (string/number, required) — application ID.

Example request:
```
GET /rentflow/api/get_application_details.php?id=0007
```

Example response (200):
```json
{
  "id": "0007",
  "tenant_name": "Juan Dela Cruz",
  "tenant_id": "T123",
  "email": "juan@example.com",
  "business_name": "Juan's Produce",
  "business_description": "Vegetables and fruits",
  "type": "wet",
  "business_permit_path": "/public/uploads/permit.pdf",
  "valid_id_path": "/public/uploads/id.png",
  "signature_path": "/public/uploads/signature.pdf",
  "status": "pending",
  "created_at": "2026-01-20 10:12:45"
}
```

Error responses:
```json
{"error":"Application ID required"}
```
```json
{"error":"Application not found"}
```

---

### `POST /api/approve_application.php`
Purpose: Approve or reject a stall application (admin-only).
Auth: `admin`.

Request parameters (form-encoded or multipart):
- `application_id` (string/number, required)
- `action` (string, required) — `approve` or `reject`

Example request:
```
POST /rentflow/api/approve_application.php
Content-Type: application/x-www-form-urlencoded

application_id=0007&action=approve
```

Example response (200):
```json
{
  "success": "Application approved successfully",
  "status": "approved"
}
```

Error responses:
```json
{"error":"Valid application ID and action required"}
```
```json
{"error":"Failed to process application: Application has already been processed"}
```

Side effects:
- Updates `stall_applications.status`.
- On approve: creates `leases`, `dues`, `arrears`, updates `stalls.status`, sends notifications.
- On reject: sends notification.

---

### `POST /api/stalls_apply.php`
Purpose: Submit a stall application with files (tenant session).
Auth: tenant session (uses `$_SESSION['user']`).

Request parameters (multipart/form-data):
- `type` (string, required) — `wet`, `dry`, or `apparel`
- `business_name` (string, required)
- `business_description` (string, required)
- `permit` (file, required)
- `valid_id` (file, required)
- `signature` (file, required)
- `stall_no` (string, optional) — passed by UI but not used server-side

Example request:
```
POST /rentflow/api/stalls_apply.php
Content-Type: multipart/form-data
```

Response:
- No JSON; the endpoint sets a session flash and redirects.
- Redirects to `/rentflow/tenant/dashboard.php`.

---

### `POST /api/payments_record.php`
Purpose: Record a payment, update dues/arrears, and set receipt path (admin/treasury UI uses direct POST).
Auth: session (no explicit `require_role`, relies on server-side session usage).

Request parameters (form-encoded):
- `lease_id` (int, required)
- `amount` (number, required)
- `method` (string, optional) — defaults to `cash`

Example request:
```
POST /rentflow/api/payments_record.php
Content-Type: application/x-www-form-urlencoded

lease_id=12&amount=1500.00&method=cash
```

Response:
- No JSON; redirects back to `HTTP_REFERER` or `/admin/payments.php`.

Side effects:
- Inserts into `payments`, marks nearest due as paid if covered, reduces `arrears`, sets a placeholder `receipt_path`.

---

### `POST /api/pay_arrear.php`
Purpose: Pay a specific unpaid due (admin/treasury).
Auth: `admin` or `treasury`.

Request parameters (form-encoded):
- `lease_id` (int, required)
- `due_date` (date `YYYY-MM-DD`, required)
- `amount_paid` (number, required)

Example response (200):
```json
{"success": true}
```

Error responses:
```json
{"error":"Invalid parameters"}
```
```json
{"error":"Due not found or already paid"}
```

---

### `GET /api/arrears_history.php`
Purpose: Return penalties and unpaid dues (admin/treasury).
Auth: `admin` or `treasury`.

Request parameters:
- Query: `lease_id` (int, required)

Example response (200):
```json
{
  "history": [
    {"date":"2026-01-15","amount":120.00,"type":"Penalty Applied"},
    {"date":"2026-01-10","amount":1500.00,"type":"Unpaid Due"}
  ],
  "total_penalties": 120.0
}
```

---

### `GET /api/chat_fetch.php`
Purpose: Fetch notifications or a chat thread (session-based).
Auth: session required.

Request parameters:
- Query: `limit` (int, optional, default 20)
- Query: `peer` (int, optional) — if provided, returns chat messages between current user and peer.

Example response (peer specified):
```json
[
  {
    "id": 55,
    "sender_id": 3,
    "receiver_id": 9,
    "type": "chat",
    "title": "Chat",
    "message": "Hi, I need help.",
    "created_at": "2026-01-25 08:40:10"
  }
]
```

Example response (notifications):
```json
[
  {
    "id": 60,
    "type": "system",
    "title": "Stall Assigned",
    "message": "A stall has been assigned to you.",
    "created_at": "2026-01-25 09:00:00",
    "is_read": 0
  }
]
```

---

### `POST /api/chat_send.php`
Purpose: Send a chat message (stored in `notifications`).
Auth: session required.

Request parameters (form-encoded):
- `receiver_id` (int, required)
- `message` (string, required)

Response:
- No JSON; redirects back to `HTTP_REFERER` or `/admin/notifications.php`.

---

### `GET /api/chart_data.php`
Purpose: Chart data for dashboards/reports.
Auth: none.

Request parameters:
- Query: `type` (string) — `monthly` (default), `yearly`, `availability`.

Example response (monthly):
```json
[
  {"ym":"2025-03","total":"12000.00"},
  {"ym":"2025-04","total":"15400.00"}
]
```

Example response (availability):
```json
[
  {"type":"wet","occupied":"8","available":"4","maintenance":"1"}
]
```

---

### `POST /api/export_csv.php`
Purpose: Export arbitrary data as CSV.
Auth: none.

Request parameters (form-encoded):
- `payload` (stringified JSON array, optional)
- `headers` (stringified JSON array, optional)
- `filename` (string, optional)

Response:
- `text/csv` stream download.

---

### `POST /api/export_excel.php`
Purpose: Export arbitrary data as Excel-compatible TSV.
Auth: none.

Request parameters (form-encoded):
- `payload` (stringified JSON array, optional)
- `headers` (stringified JSON array, optional)
- `filename` (string, optional)

Response:
- `application/vnd.ms-excel` download (TSV).

---

### `POST /api/export_pdf.php`
Purpose: Return printable HTML for PDF export of a canvas image.
Auth: none.

Request parameters (form-encoded):
- `dataUrl` (string, required) — data URL of image
- `name` (string, optional)

Response:
- `text/html` containing an `<img>` tag.

---

### `POST /api/export_png.php`
Purpose: Client-side PNG export helper.
Auth: none.

Response:
```json
{"ok": true}
```

---

### `POST /api/delete_tenant.php`
Purpose: Deactivate tenant and end lease (admin-only).
Auth: `admin`.

Request parameters (form-encoded):
- `tenant_id` (int, required)

Example response (200):
```json
{"success":"Tenant account deactivated successfully"}
```

Error responses:
```json
{"error":"Tenant ID required"}
```
```json
{"error":"Tenant not found"}
```
```json
{"error":"Tenant already inactive"}
```

Side effects:
- Sets `users.status = 'inactive'`.
- Ends lease and marks stall as available if active lease exists.

---

### `GET /api/receipts.php`
Purpose: Render payment receipt HTML.
Auth: none.

Request parameters:
- Query: `id` (int, required) — payment ID.

Response:
- HTML document (receipt).

---

### `GET /api/penalties_cron.php`
Purpose: Apply penalties for overdue dues (cron job).
Auth: none.

Response:
```
Penalties applied.
```

Side effects:
- Inserts into `penalties`, updates `arrears`, sends notifications.

---

### `POST /public/api/stalls_apply.php`
Purpose: Public proxy for `api/stalls_apply.php`.
Auth: tenant session.

Behavior:
- Forwards to `/api/stalls_apply.php`.

---

## Database Schema Appendix

Source: `sql/rentflow_schema.sql` (Generated 2026-01-15).

### Tables and Columns

**`users`**
- `id` int PK, auto-increment
- `tenant_id` varchar(4), unique (nullable)
- `role` enum('tenant','admin','treasury') not null
- `email` varchar(255) unique
- `first_name` varchar(100)
- `last_name` varchar(100)
- `password_hash` varchar(255)
- `status` enum('active','inactive','lease_ended') default 'active'
- `created_at` datetime default current_timestamp
- `confirmed` tinyint(1) default 0
- `cover_photo` varchar(255)
- `profile_photo` varchar(255)
- `location` varchar(255)
- `business_name` varchar(255)
- `two_factor_enabled` tinyint(1) default 0
- `remember_device_enabled` tinyint(1) default 0
- `password_reset_otp` varchar(255)
- `password_reset_expires` datetime
- `password_reset_requested_at` datetime
- `notif_email` tinyint(1) default 1
- `notif_sms` tinyint(1) default 0
- Index: `idx_password_reset_otp`

**`trusted_devices`**
- `id` int PK, auto-increment
- `user_id` int FK -> `users.id`
- `device_fingerprint` varchar(255) unique
- `device_name` varchar(255)
- `device_token` varchar(255) unique
- `user_agent` text
- `ip_address` varchar(45)
- `created_at` datetime default current_timestamp
- `last_used_at` datetime default current_timestamp on update
- `is_active` tinyint(1) default 1
- Index: `idx_user_id`

**`password_resets`**
- `id` int PK, auto-increment
- `user_id` int FK -> `users.id`
- `email` varchar(255)
- `token` varchar(255) unique
- `created_at` datetime default current_timestamp
- `expires_at` datetime
- `used` tinyint(1) default 0
- Indexes: `user_id_idx`, `token_idx`

**`stalls`**
- `id` int PK, auto-increment
- `stall_no` varchar(32) unique
- `type` enum('wet','dry','apparel')
- `location` varchar(255)
- `status` enum('available','occupied','maintenance') default 'available'
- `created_at` datetime default current_timestamp
- `image_path` varchar(255)
- `picture_path` varchar(255)

**`leases`**
- `id` int PK, auto-increment
- `tenant_id` int FK -> `users.id`
- `stall_id` int FK -> `stalls.id`
- `lease_start` date
- `lease_end` date (nullable)
- `monthly_rent` decimal(10,2)
- Indexes: `tenant_id`, `stall_id`

**`payments`**
- `id` int PK, auto-increment
- `lease_id` int FK -> `leases.id`
- `amount` decimal(10,2)
- `payment_date` date
- `method` enum('cash','gcash','bank','card')
- `transaction_id` varchar(64) unique
- `remarks` varchar(255)
- `receipt_path` varchar(255)
- Index: `lease_id`

**`dues`**
- `id` int PK, auto-increment
- `lease_id` int FK -> `leases.id`
- `due_date` date
- `amount_due` decimal(10,2)
- `paid` tinyint(1) default 0
- Index: `lease_id`

**`arrears`**
- `id` int PK, auto-increment
- `lease_id` int FK -> `leases.id`
- `total_arrears` decimal(10,2) default 0.00
- `last_updated` datetime default current_timestamp
- Index: `lease_id`

**`penalties`**
- `id` int PK, auto-increment
- `lease_id` int FK -> `leases.id`
- `due_id` int FK -> `dues.id`
- `penalty_amount` decimal(10,2)
- `applied_on` date
- Indexes: `lease_id`, `due_id`

**`notifications`**
- `id` int PK, auto-increment
- `sender_id` int FK -> `users.id`
- `receiver_id` int FK -> `users.id`
- `type` enum('system','chat')
- `title` varchar(255)
- `message` text
- `created_at` datetime default current_timestamp
- `is_read` tinyint(1) default 0
- Indexes: `sender_id`, `receiver_id`

**`auth_codes`**
- `id` int PK, auto-increment
- `role` enum('admin','treasury')
- `code1` varchar(16)
- `code2` varchar(16)
- `code3` varchar(16)
- `valid_until` datetime

**`stall_applications`**
- `id` int PK, auto-increment (later formatted to 4-digit string in app)
- `tenant_id` int FK -> `users.id`
- `type` enum('wet','dry','apparel')
- `business_name` varchar(255)
- `business_description` text
- `business_permit_path` varchar(255)
- `valid_id_path` varchar(255)
- `signature_path` varchar(255)
- `status` enum('pending','approved','rejected') default 'pending'
- `created_at` datetime default current_timestamp

### Relationships (Summary)
- `users` 1—N `leases` (tenant leases)
- `stalls` 1—N `leases` (stall occupancy)
- `leases` 1—N `payments`, `dues`, `arrears`, `penalties`
- `dues` 1—N `penalties`
- `users` 1—N `notifications` (as sender and receiver)
- `users` 1—N `stall_applications`
- `users` 1—N `trusted_devices`, `password_resets`

---

## Per-Page UI Flows

### Admin Pages (`/admin`)

**`admin/login.php`**
- One-click login: POST form fetches first admin user and sets `$_SESSION['user']`.
- Redirects to `admin/dashboard.php`.

**`admin/dashboard.php`**
- Loads stall availability, upcoming dues, revenue stats, recent payments from DB.
- Displays summary tables and cards.

**`admin/tenants.php`**
- Filters tenants by query, status, sorting.
- Exports list using `POST /api/export_csv.php` with JSON payload.
- Message modal uses `POST /api/chat_send.php`.
- Links to `admin/tenant_profile.php?id=...`.

**`admin/tenant_profile.php`**
- Displays tenant profile, lease, payment summary.
- Message modal uses `POST /api/chat_send.php`.
- Delete modal submits to `POST /api/delete_tenant.php` (AJAX).

**`admin/payments.php`**
- Lists payment status and arrears per lease.
- “Arrears History” uses `GET /api/arrears_history.php`.
- “Pay” action uses `POST /api/pay_arrear.php`.
- Manual payment workflow posts back to itself (inserts payments, dues, arrears).

**`admin/reports.php`**
- Computes stats and renders charts (Chart.js).
- CSV/Excel exports via `?export=revenue_csv` and `?export=revenue_xlsx` (inline exports).
- Client-side chart exports use html2canvas/jspdf/html2pdf (no API call).

**`admin/stalls.php`**
- Add/edit/remove/assign stalls via POST to same page.
- File uploads saved to `/uploads/stalls/`.
- Assign creates `leases`, `dues`, `arrears`, updates stall status, sends notifications.

**`admin/notifications.php`**
- Shows latest notifications and chat.
- Chat form uses `POST /api/chat_send.php`.
- Application details modal uses `GET /api/get_application_details.php`.
- Approve/reject uses `POST /api/approve_application.php`.

**`admin/account.php`**
- Update profile name/location (POST to same page).
- Logout link to `/public/logout.php`.

**`admin/contact.php`**
- Support contact form inserts a `notifications` record (with optional attachment).

---

### Tenant Pages (`/tenant`)

**`tenant/dashboard.php`**
- Shows next due, last payment, total arrears.
- Builds arrears history list from `penalties` and `dues`.
- Displays flash success from stall application submission.

**`tenant/payments.php`**
- Displays upcoming due, arrears, last payment.
- Shows transaction history with links to receipt HTML (via `receipt_path`).

**`tenant/stalls.php`**
- Shows available stalls and existing leases.
- “Apply” modal posts multipart to `POST /api/stalls_apply.php` (files + details).

**`tenant/notifications.php`**
- Displays notifications, marks them read.
- Chat modal uses `POST /api/chat_send.php` to admin.

**`tenant/profile.php`**
- Read-only profile summary, links to account settings.

**`tenant/account.php`**
- Updates profile, email, password, and 2FA via POST to same page.
- Lists trusted devices from `trusted_devices`.
- Links to support page and logout.

**`tenant/support.php`**
- Support message form inserts notifications to admin/treasury with optional attachment.

---

### Treasury Pages (`/treasury`)

**`treasury/login.php`**
- One-click login: POST fetches first treasury user and sets session.
- Redirects to `/treasury/dashboard.php`.

**`treasury/dashboard.php`**
- Lists leases and arrears.
- Inline form updates `arrears.total_arrears` via POST to same page.

**`treasury/adjustments.php`**
- Updates arrears and optionally next due (date/amount).
- Sends notifications to admin and tenant on update.

---

If you want, I can also add diagrams (ERD and page-flow diagrams) and split this into separate docs under `docs/`.
