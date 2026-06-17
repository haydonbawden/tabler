# Castor CRM Enterprise Upgrade Checklist

Use this as the working tracker for completing the full enterprise audit and certification CRM brief. Mark items complete only when implemented, deployed, and smoke-tested.

## Phase 0 - Baseline And Safety

- [x] Preserve existing PHP MVC architecture, Tabler UI, MySQL/MariaDB, shared-hosting compatibility.
- [x] Verify current deployment path and server lint workflow.
- [x] Keep activity logging service available for workflow actions.
- [x] Add/expand smoke-test scripts for each material workflow under `tests/`.
- [x] Document manual test credentials and safe test data assumptions.
- [x] Add regression checklist for login/logout, CSRF, route auth, and imported data access.

## Phase 1 - Enterprise Shell And Navigation

- [x] Create reusable view components directory.
- [x] Add `page-header.php`.
- [x] Add `status-badge.php`.
- [x] Add `data-table-toolbar.php`.
- [x] Add `empty-state.php`.
- [x] Add `record-summary-card.php`.
- [x] Add `activity-timeline.php`.
- [x] Add `confirmation-modal.php`.
- [x] Add `form-actions.php`.
- [x] Add `lifecycle-actions.php`.
- [x] Add `metric-card.php`.
- [x] Add `saved-view-tabs.php`.
- [x] Add `global-search.php`.
- [x] Replace top-only admin nav with role-based Tabler sidebar shell.
- [x] Keep user/account menu in authenticated header.
- [x] Add global search UI to header.
- [x] Add admin/auditor/client scoped search routes.
- [x] Verify sidebar responsive behavior at 1440, 1280, 1024, 768 and 390 widths.
- [x] Ensure every primary page passes explicit `pageHeader` metadata.

## Phase 2 - Dashboards And Work Queues

- [x] Build Admin dashboard KPI/work-queue foundation.
- [x] Build Auditor dashboard KPI/review queue foundation.
- [x] Build Client dashboard KPI/action panel foundation.
- [ ] Add dashboard links with real pre-filtered table state.
- [ ] Add empty states to every dashboard queue.
- [ ] Validate dashboard counts against database queries with tests.
- [ ] Add performance smoke test on production-like imported data.

## Phase 3 - Reusable Data Table Framework

- [x] Existing enhanced table supports search, sort, pagination, CSV export, Excel-compatible export, row click and bulk selection.
- [x] Upgrade Clients table with operational count columns.
- [x] Upgrade Contacts table with reminder/certificate/audit notification columns.
- [x] Upgrade Audits table with auditor, evidence progress, last activity and certificate status columns.
- [x] Upgrade Certificates table with computed date status and PDF status columns.
- [ ] Add column visibility controls.
- [ ] Add status filters.
- [ ] Add date range filters.
- [ ] Add context-specific filters for Clients.
- [ ] Add context-specific filters for Contacts.
- [ ] Add context-specific filters for Audits.
- [ ] Add context-specific filters for Certificates.
- [ ] Add context-specific filters for Payments, Email Logs, Activity Logs, Users, Audit Criteria, Reminder Rules.
- [ ] Add permission-checked bulk actions.
- [ ] Persist per-user table preferences.
- [ ] Add saved table views persistence.

## Phase 4 - Admin Record 360 Pages

- [ ] Replace Admin client detail with Client 360 layout.
- [ ] Add Client 360 header summary.
- [ ] Add Client 360 tabs: Overview, Contacts, Audits, Certificates, Evidence, Payments, Emails, Activity, Notes.
- [ ] Add recent activity timeline to Client 360.
- [ ] Add renewal risk and current certification panels.
- [ ] Ensure Client 360 related tabs are role-scoped.

## Phase 5 - Audit Workbench And Lifecycle

- [ ] Create `app/Services/AuditLifecycleService.php`.
- [ ] Define allowed transitions centrally.
- [ ] Validate role permissions in lifecycle service.
- [ ] Validate required data before transition.
- [ ] Move all audit status updates out of controllers.
- [ ] Add lifecycle actions for draft audits.
- [ ] Add lifecycle actions for awaiting evidence audits.
- [ ] Add lifecycle actions for submitted audits.
- [ ] Add lifecycle actions for in-review audits.
- [ ] Add lifecycle actions for passed audits.
- [ ] Reject invalid transitions server-side.
- [ ] Require confirmation and comments for pass/fail/request changes.
- [ ] Log every lifecycle transition.
- [ ] Add lifecycle tests for valid and invalid transitions.
- [ ] Replace Admin audit detail with full audit workbench.
- [ ] Replace Auditor audit review with full evidence/assessment workbench.
- [ ] Replace Client audit view with full evidence submission workbench.

## Phase 6 - Evidence Submission And Review

- [ ] Group client evidence criteria by section/order.
- [ ] Show requirement text, guidance, evidence prompt and client response per criterion.
- [ ] Add uploaded files list per criterion.
- [ ] Add delete-own-draft-file flow before submission.
- [ ] Block client submission until required criteria are complete.
- [ ] Submit audit through lifecycle service.
- [ ] Add auditor completion summary.
- [ ] Add auditor result selector per criterion.
- [ ] Require not acceptable / needs info marker before request changes unless admin override.
- [ ] Require all required criteria acceptable before pass unless admin override.
- [ ] Require overall auditor comment before fail.
- [ ] Centralise evidence progress calculations in a service/helper.
- [ ] Display evidence progress on audit list, workbench header, auditor dashboard and client dashboard.
- [ ] Add tests for evidence save, upload, submit, review, request changes, pass and fail.

## Phase 7 - Certificate Workbench And Status

- [ ] Create `app/Services/CertificateStatusService.php`.
- [ ] Calculate computed status: current, expiring soon, expired.
- [ ] Use configured renewal reminder window.
- [ ] Use computed status in certificate table and detail.
- [ ] Add tests for current, expiring soon and expired.
- [ ] Replace Admin certificate detail with certificate workbench.
- [ ] Replace Client certificate detail with certificate workbench.
- [ ] Improve New Certificate form derived audit/client/certification state.
- [ ] Auto-suggest issue, expiry and last-day-to-renew dates from settings.
- [ ] Add PDF panel: generated state, generated at/by, storage health, download, preview, regenerate, email.
- [ ] Add permission checks for PDF actions.
- [ ] Add confirmation/reason flows for revoke and mark replaced.
- [ ] Add Create renewal audit action.
- [ ] Add duplicate renewal audit warning.
- [ ] Log certificate generation, download, email, renewal, revoke and replacement actions.

## Phase 8 - Settings, Templates And Admin Configuration

- [x] Add persisted settings tables.
- [x] Add user-specific light/dark theme setting.
- [x] Move Users & Permissions into Settings.
- [x] Move Audit Criteria into Settings/admin flow.
- [x] Add Email/Certificate tabs to Templates view.
- [x] Add email HTML code and preview tabs.
- [x] Add certificate Word template upload.
- [ ] Split Settings into dedicated tabs/pages: Organisation, Branding, Certificates, Renewals, Email Delivery, Security, Portal Experience, Users & Permissions, Audit Criteria, Integrations.
- [ ] Add per-section save actions.
- [ ] Show last updated by/at where practical.
- [ ] Add certificate numbering pattern settings.
- [ ] Add authorised signatory settings.
- [ ] Add certificate footer text setting.
- [ ] Add SMTP settings with masked password.
- [ ] Add test email send action.
- [ ] Log test email success/failure.
- [ ] Add email template merge field picker.
- [ ] Add merge field validation.
- [ ] Add template test-send.
- [ ] Add template "Used by" panel.
- [ ] Validate certificate template `.docx` only.
- [ ] Validate required certificate placeholders where practical.
- [ ] Show current certificate template filename/uploaded at/by.

## Phase 9 - Import And Export

- [x] Restore `/admin/imports` as Administration page.
- [x] Add basic import run history.
- [x] Add export links for major tables.
- [ ] Add routes: `POST /admin/imports/preview`.
- [ ] Add routes: `POST /admin/imports/commit`.
- [ ] Build import wizard steps: upload, validate, dry-run preview, confirm, report.
- [ ] Detect records to create/update/skip.
- [ ] Detect duplicate ABNs.
- [ ] Detect duplicate emails.
- [ ] Detect duplicate audit IDs.
- [ ] Detect duplicate certificate numbers.
- [ ] Detect missing required fields.
- [ ] Detect unmatched client ABNs and audit IDs.
- [ ] Block commit until dry-run passes.
- [ ] Store downloadable import result report.
- [ ] Log import preview and commit.
- [ ] Ensure export respects current filters.
- [ ] Ensure export respects role and record-level permissions.
- [ ] Log exports.

## Phase 10 - Activity Timelines And Auditability

- [x] Add reusable `activity-timeline.php` component.
- [ ] Use activity timeline on Client detail.
- [ ] Use activity timeline on Audit workbench.
- [ ] Use activity timeline on Certificate workbench.
- [ ] Use activity timeline on User detail.
- [ ] Improve Activity Log list filters: actor, action, client, audit, certificate, date range, IP, entity type.
- [ ] Add Activity Log saved views.
- [ ] Add row expansion for metadata.
- [ ] Add admin-only export for Activity Log.

## Phase 11 - Client And Auditor Portal Polish

- [x] Client dashboard foundation.
- [x] Auditor dashboard foundation.
- [ ] Ensure client pages use plain-language labels throughout.
- [ ] Add clear "what you need to do next" prompts to all client pages.
- [ ] Ensure client users cannot see unrelated records via direct URL tests.
- [ ] Ensure auditor users cannot see unassigned audits via direct URL tests.
- [ ] Optimise auditor review screens for evidence/comments over generic CRUD.
- [ ] Test auditor end-to-end review workflow.
- [ ] Test client end-to-end evidence workflow.

## Phase 12 - Visual Design And Responsive Polish

- [x] Add Castor logo and brand use in shell.
- [x] Add Castor page header styling.
- [ ] Refine status badge severity colours for accessibility.
- [ ] Add meaningful icons consistently for clients, contacts, audits, evidence, certificates, payments, email and settings.
- [ ] Ensure primary actions use contextual labels everywhere.
- [ ] Verify no clipped buttons at 1440 desktop.
- [ ] Verify no clipped buttons at 1280 laptop.
- [ ] Verify no clipped buttons at 1024 tablet landscape.
- [ ] Verify no clipped buttons at 768 tablet portrait.
- [ ] Verify no clipped buttons at 390 mobile.

## Phase 13 - Security And Error UX

- [x] Basic 403 and 404 views exist.
- [ ] Add friendly 419 CSRF/session expired page.
- [ ] Add friendly 500 server error page.
- [ ] Ensure unauthorised access does not leak record existence.
- [ ] Log forbidden and server errors where practical.
- [ ] Add security settings health checks.
- [ ] Add production debug warning.
- [ ] Add private storage health check.
- [ ] Add mail configuration health check.
- [ ] Add certificate storage health check.
- [ ] Ensure no secrets are displayed.

## Phase 14 - Post-MVP Enterprise Features

- [ ] Add saved table views table/migration.
- [ ] Add saved table views UI.
- [ ] Add default personal saved views.
- [ ] Add optional shared admin views.
- [ ] Add notifications/action centre schema.
- [ ] Add notification bell UI.
- [ ] Add notifications for evidence submitted, changes requested, certificate expiring, certificate generated, failed email, import complete, assigned audit.
- [ ] Add mark read/unread.
- [ ] Improve public certificate verification UX.
- [ ] Add public verification rate limiting.
- [ ] Ensure public verification exposes limited fields only.

## Phase 15 - Final Validation

- [ ] Admin can navigate all sidebar sections.
- [ ] Auditor only sees auditor sections.
- [ ] Client only sees client sections.
- [ ] Global search returns scoped results for all roles.
- [ ] Tables filter, sort, paginate and export correctly.
- [ ] Dashboard cards link to filtered tables.
- [ ] Empty states appear for empty payments/email logs/evidence.
- [ ] Client can save evidence draft.
- [ ] Client can upload evidence file.
- [ ] Client can submit audit.
- [ ] Auditor can start review.
- [ ] Auditor can mark criteria acceptable/not acceptable/needs more information.
- [ ] Auditor can request changes.
- [ ] Auditor can pass audit.
- [ ] Auditor can fail audit.
- [ ] Passing audit can generate certificate draft/PDF.
- [ ] Certificate PDF can be downloaded by authorised users only.
- [ ] Client representative cannot access another client's records.
- [ ] Auditor cannot access unassigned audit.
- [ ] Evidence files cannot be downloaded without authorisation.
- [ ] Invalid audit transitions are rejected.
- [ ] Final statuses cannot be changed except explicit admin override.
- [ ] Certificate computed status is correct.
- [ ] All lifecycle transitions create activity logs.
- [ ] Import dry-run validates required fields.
- [ ] Import commit logs result.
- [ ] Export respects filters and permissions.
- [ ] Login/logout still works.
- [ ] CSRF protection still applies.
- [ ] Imported records remain accessible.
- [ ] Production configuration runs with `APP_DEBUG=false`.
