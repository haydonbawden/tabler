# Castor Audit & Advisory CRM Portal

Custom PHP CRM portal for Castor Audit & Advisory audit, certification and renewal workflows. The app is intentionally small MVC-style PHP, server-rendered, and designed for cPanel/VentraIP-style hosting with the web document root pointed at `public/`.

## Current Implementation Slice

- PHP front controller at `public/index.php`
- Custom router, config, database, session, CSRF and auth helpers
- MySQL migrations for users, clients, contacts, audits, audit evidence, certificates, emails, reminder rules, payments, activity logs, import runs and registration requests
- Seed data for default admin, certification types, baseline audit criteria, email templates and reminder rules
- Session auth, password hashing, email verification token support and password reset token support
- Role middleware and central record-level access checks
- Tabler-rendered admin, auditor and client layouts
- Admin dashboard, searchable tables and detail/edit pages for clients, contacts, audits and certificates
- Client contact management, audit evidence responses and private file uploads
- Auditor review comments, request changes, pass/fail and certificate generation actions
- XLSX import pipeline for `reference/castor_data.xlsx` with dry-run summary and idempotent create/update behaviour
- HTML-to-PDF certificate renderer using Dompdf, without LibreOffice
- CSV exports and basic test script

Stripe renewals, automated reminders and advanced saved table views are scaffolded but intentionally deferred until the core audit workflow is stable.

## Requirements

- PHP 8.2+
- MySQL or MariaDB
- Composer
- A dedicated database and database user for the CRM

## Local Setup

```bash
composer install
cp .env.example .env
php scripts/migrate.php
php scripts/seed.php
php scripts/import-castor-data.php reference/castor_data.xlsx --dry-run
php -S localhost:8000 -t public
```

Seeded admin login:

- Email: `admin@castoraustralia.com.au`
- Password: `ChangeMe123!`

Change this password immediately outside local development.

## Imports

The supplied workbook is expected at:

```text
reference/castor_data.xlsx
```

Run a validation pass first:

```bash
php scripts/import-castor-data.php reference/castor_data.xlsx --dry-run
```

Commit the import:

```bash
php scripts/import-castor-data.php reference/castor_data.xlsx
```

The admin UI also exposes `/admin/imports`. Imports do not delete existing records and use ABN, email, audit number and certificate number to update where possible.

## Deployment Notes

Preferred hosting layout:

- Point the domain document root to `public/`.
- Keep `app/`, `config/`, `database/`, `storage/`, `vendor/`, `.env` and `reference/` outside the public webroot.
- Ensure `storage/` is writable by PHP.
- Set production environment values:

```env
APP_ENV=production
APP_DEBUG=false
SESSION_SECURE=true
APP_URL=https://portal.castoraustralia.com.au
```

If cPanel cannot point the document root at `public/`, place only `public/index.php`, `public/.htaccess` and `public/assets/` in the web-accessible directory. Update `public/index.php` so it requires the real path to `bootstrap/app.php` outside the webroot.

## Tests

After migrating and seeding a test database:

```bash
php tests/run.php
```

The test script wraps its fixture data in a transaction and checks login success/failure, role/record scoping, assigned auditor access, importer dry-run service presence and certificate service wiring.
