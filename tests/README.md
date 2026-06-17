# Castor CRM Smoke Tests

These tests are the baseline safety checks for the enterprise upgrade work. They are designed to run against a configured Castor CRM environment without leaving test records behind.

## How To Run

From the application root on an environment with PHP and database access:

```bash
php tests/run.php
```

The test runner opens a database transaction, inserts synthetic users, clients, audits, evidence metadata and certificates, exercises the current services, and rolls the transaction back.

## Manual Test Credentials

Seeded local/development credentials are documented in `README.md`:

- Email: `admin@castoraustralia.com.au`
- Password: `ChangeMe123!`

Production credentials may differ. Do not reset or expose production passwords just to run smoke tests. For browser/manual smoke testing on production, use an authorised admin account supplied by the system owner.

## Safe Test Data Assumptions

- Automated smoke tests create records prefixed with `Phase0` and unique random suffixes.
- Automated smoke tests run inside one transaction and roll back at the end.
- The tests assume core migrations and seeders have already run.
- The tests assume at least one active database connection from `.env`.
- The tests do not upload real files or send email.
- The tests do not generate certificate PDFs; they only verify the certificate service is available.
- The tests should not be run while changing production schema unless a backup is available.

## Current Smoke Coverage

- Login success and failure.
- Admin, auditor and client access scoping.
- Admin, auditor and client global search scoping.
- Settings persistence and user theme persistence.
- Table service row and CSV export basics.
- Certificate computed-status column availability.
- Activity logging with related client, audit and certificate context.
- Import dry-run service availability.
- Certificate generation service availability.
- Evidence download access uses audit access rules.
