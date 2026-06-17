# Castor CRM Regression Checklist

Run this checklist before and after material deployments.

## Authentication

- [ ] Login succeeds for an authorised admin account.
- [ ] Login fails for an incorrect password.
- [ ] Logout ends the session and returns the user to the login flow.
- [ ] Disabled users cannot authenticate.

## CSRF And Session Safety

- [ ] POST requests without `_token` are rejected or redirected with a session-expired message.
- [ ] POST requests with a valid `_token` succeed for authorised users.
- [ ] A stale browser session shows a safe recovery path.

## Route Authorisation

- [ ] Admin can access admin dashboard, clients, contacts, audits, certificates, settings and logs.
- [ ] Auditor cannot access admin-only routes.
- [ ] Client representative cannot access admin-only routes.
- [ ] Anonymous users are redirected to login for authenticated routes.

## Record-Level Access

- [ ] Client representative can access linked client records.
- [ ] Client representative cannot access another client's records by direct URL.
- [ ] Client representative can access linked audits and certificates.
- [ ] Client representative cannot access unrelated audits or certificates by direct URL.
- [ ] Auditor can access assigned audits.
- [ ] Auditor cannot access unassigned audits by direct URL.
- [ ] Admin can access all operational records.

## Imported Data Access

- [ ] Imported clients are visible in Admin Clients.
- [ ] Imported contacts are visible in Admin Contacts and linked to the right client.
- [ ] Imported audits are visible in Admin Audits and open without error.
- [ ] Imported certificates are visible in Admin Certificates and open without error.
- [ ] Search can find imported client legal name, trading name, ABN, audit number and certificate number.

## Core UI Shell

- [ ] Role-specific sidebar renders for admin.
- [ ] Role-specific sidebar renders for auditor.
- [ ] Role-specific sidebar renders for client representative.
- [ ] Page header renders with the correct portal eyebrow and title.
- [ ] Global search returns scoped results.

## Smoke Test Script

- [ ] `php tests/run.php` passes before deployment.
- [ ] `php tests/run.php` passes after deployment.
