# Castor CRM Implementation Notes

- Keep the CRM as a standalone PHP application; do not bind it to WordPress users, tables or authentication.
- The web server document root should be `public/`.
- Store uploaded evidence and generated certificates under `storage/`, outside the public webroot.
- Use prepared statements through `App\Core\Database`.
- Protect state-changing routes with CSRF middleware unless they are external webhooks.
- Enforce role and record-level permissions in controllers/services, not just navigation.
- Keep Stripe renewals and automated reminders behind service boundaries until the core audit workflow is complete.
- Run `composer install`, `php scripts/migrate.php`, `php scripts/seed.php`, and `php tests/run.php` after PHP/Composer are available.
