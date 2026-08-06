# Loan Processing and Tracking System

A professional, responsive Loan Processing and Tracking System built with PHP 8.3+ (OOP / MVC),
PostgreSQL, Bootstrap 5, DataTables, Chart.js, and PhpSpreadsheet.

## Features
- Secure session-based login (bcrypt password hashing, CSRF protection on all write requests)
- Dashboard with KPI cards, dynamic Group 1/2/3 cards, branch & monthly charts, recent activity
- Capture Loan screen with live ID-Number client lookup (auto-fills existing clients, auto
  increments loan count, and calculates the client's Group — nothing here is manually entered)
- Loan Register with server-side DataTables (search, multi-filter, sorting, pagination),
  bulk status / report-status change, bulk delete, and row edit
- Excel export: Selected / Filtered / All / By Group / By Branch (PhpSpreadsheet)
- Reports screen with dynamic KPI + branch breakdown, Excel export, print
- Branch management (create / edit / activate / deactivate)
- User management (Administrator-only: create / edit / reset password / deactivate)
- Fully responsive: collapsible sidebar (hamburger) on tablet/mobile, stacking forms,
  horizontally scrollable tables

## Requirements
- PHP 8.3+
- PostgreSQL 13+
- Composer
- A web server (Apache with mod_rewrite, Nginx, or the PHP built-in server for local testing)

## Setup

1. **Install PHP dependencies** (this pulls in PhpSpreadsheet, required for Excel exports):
   ```bash
   composer install
   ```

2. **Create the database and load the schema:**
   ```bash
   createdb loan_system
   psql -d loan_system -f database/schema.sql
   ```

3. **Create your first Administrator account.** Generate a bcrypt hash with the bundled helper
   (bcrypt hashes must be generated with the same PHP build that will verify them):
   ```bash
   php public/tools/make_hash.php "YourStrongPassword123"
   ```
   Copy the output hash into `database/seed.sql` in place of `__REPLACE_WITH_GENERATED_HASH__`,
   then load the seed data (branches, statuses, and your admin user):
   ```bash
   psql -d loan_system -f database/seed.sql
   ```
   Alternatively, once the app is running you can seed just the lookup tables and register your
   first user directly via `INSERT` — the User Management screen only becomes available after
   you can log in, so the very first admin must be created this way.

4. **Configure environment variables:**
   ```bash
   cp .env.example .env
   ```
   Edit `.env` with your database credentials and site URL, e.g.:
   ```
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_NAME=loan_system
   DB_USER=postgres
   DB_PASS=postgres
   APP_URL=http://localhost/loan-system/public
   ```

5. **Point your web server's document root at `/public`** (this is important — it keeps
   `config/`, `core/`, `models/`, `controllers/`, and `database/` outside the web root).
   For quick local testing you can instead run PHP's built-in server:
   ```bash
   php -S localhost:8000 -t public
   ```
   Then set `APP_URL=http://localhost:8000` in `.env`.

6. Visit the site and log in with the admin credentials you created in step 3.

## Project Structure
```
config/         Environment loader + database connection settings
core/           Router, base Controller/Model, PDO Database wrapper, Auth (sessions/CSRF)
models/         User, Client, Branch, Loan, LoanStatus, ReportStatus (all PDO / prepared statements)
controllers/    Auth, Dashboard, Loan, Client, Branch, User, Report, Export, Settings
views/          PHP views, organized by feature, sharing views/layouts/app.php
public/         Web root — front controller (index.php), assets/css, assets/js
database/       schema.sql (tables + the loan_register_view) and seed.sql
```

## Key Design Notes
- **Loan Count & Group are never stored** — they're computed live by `loan_register_view`
  (a SQL view joining loans → clients → branches → statuses with a per-client COUNT()).
  This guarantees the count/group can never drift out of sync and can never be edited by hand.
- **Reference numbers** (`LN-YYYYMMDD-0001`) are generated transactionally against a
  `daily_counters` table to avoid collisions under concurrent use.
- **Client identity** is enforced by a unique constraint on `clients.id_number`; capturing a
  loan always calls `Client::findOrCreate()`, so an existing ID Number is reused rather than
  duplicated.
- All forms and AJAX writes are protected by CSRF tokens; all queries use PDO prepared
  statements (no string-concatenated SQL).

## Default Roles
- **Administrator** — full access, including User Management
- **Operator** — everything except User Management

## Notes on Excel Export
Exports are generated live from the same filtered query used by the Loan Register/Reports
screens, so "Export Selected", "Export Filtered", "Export All", "Export by Group", and
"Export by Branch" always reflect current data. This requires `composer install` to have
completed successfully (PhpSpreadsheet).
