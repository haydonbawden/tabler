<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Services\ActivityLogger;
use App\Services\AuditLifecycleService;
use App\Services\ClientRecordService;
use App\Services\EvidenceProgressService;
use App\Services\SettingsService;
use App\Services\TableService;
use App\Services\TableViewService;

final class AdminController extends Controller
{
    public function dashboard(): void
    {
        redirect('/admin/clients');
    }

    public function clients(): void
    {
        $this->table('clients', 'Clients', '/admin/clients/');
    }

    public function contacts(): void
    {
        $this->table('contacts', 'Contacts', '/admin/contacts/');
    }

    public function audits(): void
    {
        $this->table('audits', 'Audits', '/admin/audits/');
    }

    public function certificates(): void
    {
        $this->table('certificates', 'Certificates', '/admin/certificates/');
    }

    public function users(): void
    {
        $this->table('users', 'Users', '/admin/users/');
    }

    public function clientDetail(string $id): void
    {
        $record = $id === 'new' ? null : (new ClientRecordService())->record((int) $id);
        $client = $record['client'] ?? null;
        $contacts = $record['contacts'] ?? [];
        $audits = $record['audits'] ?? [];
        $certificates = $record['certificates'] ?? [];
        $evidence = $record['evidence'] ?? [];
        $payments = $record['payments'] ?? [];
        $emailLogs = $record['emailLogs'] ?? [];
        $activities = $record['activities'] ?? [];
        $summary = $record['summary'] ?? [];
        $title = $client['legal_name'] ?? 'New client';
        $this->view('admin/client-detail', [
            'title' => $title,
            'client' => $client,
            'contacts' => $contacts,
            'audits' => $audits,
            'certificates' => $certificates,
            'evidence' => $evidence,
            'payments' => $payments,
            'emailLogs' => $emailLogs,
            'activities' => $activities,
            'summary' => $summary,
        ] + $this->pageHeader('CLIENT RECORD', $title, $client ? 'Manage organisation details, contacts and audit history.' : 'Create a new client organisation.', [
            'status' => $client ? ['label' => (string) $client['status'], 'class' => $client['status'] === 'active' ? 'bg-success-lt' : 'bg-secondary-lt'] : null,
        ]));
    }

    public function saveClient(string $id): void
    {
        $data = [
            trim((string) $this->input('client_number')) ?: null,
            preg_replace('/\D+/', '', (string) $this->input('abn')) ?: null,
            trim((string) $this->input('legal_name')),
            trim((string) $this->input('trading_name')) ?: null,
            trim((string) $this->input('registered_office_address')) ?: null,
            trim((string) $this->input('billing_email')) ?: null,
            $this->input('status', 'active'),
            trim((string) $this->input('notes')) ?: null,
        ];
        if ($data[2] === '') {
            Session::flash('error', 'Legal name is required.');
            redirect('/admin/clients/' . $id);
        }
        if ($id === 'new') {
            $this->db()->statement('insert into clients (client_number, abn, legal_name, trading_name, registered_office_address, billing_email, status, notes, created_at, updated_at) values (?, ?, ?, ?, ?, ?, ?, ?, now(), now())', $data);
            $id = (string) $this->db()->pdo()->lastInsertId();
            (new ActivityLogger())->log('client_created', 'Client created.', ['client_id' => (int) $id]);
        } else {
            $this->db()->statement('update clients set client_number = ?, abn = ?, legal_name = ?, trading_name = ?, registered_office_address = ?, billing_email = ?, status = ?, notes = ?, updated_at = now() where id = ?', [...$data, $id]);
            (new ActivityLogger())->log('client_updated', 'Client updated.', ['client_id' => (int) $id]);
        }
        Session::flash('success', 'Client saved.');
        redirect('/admin/clients/' . $id);
    }

    public function contactDetail(string $id): void
    {
        $contact = $id === 'new' ? null : $this->db()->first('select * from contacts where id = ?', [$id]);
        $clients = $this->db()->select('select id, legal_name from clients order by legal_name');
        $title = $contact['display_name'] ?? 'New contact';
        $this->view('admin/contact-detail', [
            'title' => $title,
            'contact' => $contact,
            'clients' => $clients,
        ] + $this->pageHeader('CONTACT RECORD', $title, $contact ? 'Maintain contact roles, notifications and certificate delivery preferences.' : 'Create a client contact.', [
            'status' => $contact ? ['label' => ((int) $contact['is_active'] === 1 ? 'Active' : 'Inactive'), 'class' => ((int) $contact['is_active'] === 1 ? 'bg-success-lt' : 'bg-secondary-lt')] : null,
        ]));
    }

    public function saveContact(string $id): void
    {
        $data = [
            (int) $this->input('client_id'),
            trim((string) $this->input('first_name')) ?: null,
            trim((string) $this->input('last_name')) ?: null,
            trim((string) $this->input('display_name')),
            strtolower(trim((string) $this->input('email'))),
            trim((string) $this->input('phone')) ?: null,
            trim((string) $this->input('position_title')) ?: null,
            $this->input('is_active') ? 1 : 0,
            $this->input('receives_reminders') ? 1 : 0,
            $this->input('receives_certificates') ? 1 : 0,
            $this->input('receives_audit_notifications') ? 1 : 0,
        ];
        if ($data[3] === '' || !filter_var($data[4], FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'Display name and valid email are required.');
            redirect('/admin/contacts/' . $id);
        }
        if ($id === 'new') {
            $this->db()->statement('insert into contacts (client_id, first_name, last_name, display_name, email, phone, position_title, is_active, receives_reminders, receives_certificates, receives_audit_notifications, created_at, updated_at) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, now(), now())', $data);
            $id = (string) $this->db()->pdo()->lastInsertId();
        } else {
            $this->db()->statement('update contacts set client_id = ?, first_name = ?, last_name = ?, display_name = ?, email = ?, phone = ?, position_title = ?, is_active = ?, receives_reminders = ?, receives_certificates = ?, receives_audit_notifications = ?, updated_at = now() where id = ?', [...$data, $id]);
        }
        (new ActivityLogger())->log('contact_saved', 'Contact saved.', ['client_id' => $data[0]]);
        Session::flash('success', 'Contact saved.');
        redirect('/admin/contacts/' . $id);
    }

    public function auditDetail(string $id): void
    {
        $audit = $id === 'new' ? null : $this->db()->first('select * from audits where id = ?', [$id]);
        $clients = $this->db()->select('select id, legal_name from clients order by legal_name');
        $types = $this->db()->select('select id, name from certification_types where is_active = 1 order by name');
        $auditors = $this->db()->select("select id, name from users where role in ('auditor','admin','super_admin') order by name");
        $evidenceService = new EvidenceProgressService();
        $responses = $audit ? $evidenceService->responses((int) $id) : [];
        foreach ($responses as &$response) {
            $response['evidence_count'] = count($response['files'] ?? []);
        }
        unset($response);
        $progress = $audit ? $evidenceService->progressFromResponses($responses) : [];
        $certificates = $audit ? $this->db()->select('select id, certificate_number, issue_number, status, issue_date, expiry_date, pdf_path from certificates where audit_id = ? order by issue_date desc, id desc', [$id]) : [];
        $payments = $audit ? $this->db()->select('select id, amount_cents, currency, status, paid_at, created_at from payments where audit_id = ? order by created_at desc', [$id]) : [];
        $lifecycleActions = $audit ? (new AuditLifecycleService())->actions($audit, (string) (Auth::user()['role'] ?? 'viewer'), Auth::id()) : [];
        $title = $audit['audit_number'] ?? 'New audit';
        $this->view('admin/audit-detail', compact('audit', 'clients', 'types', 'auditors', 'responses', 'progress', 'certificates', 'payments', 'lifecycleActions') + [
            'title' => $title,
        ] + $this->pageHeader('AUDIT RECORD', $title, $audit ? 'Review audit details, payment history, assessment responses and related certificates.' : 'Create a new audit workflow.', [
            'status' => $audit ? ['label' => (string) $audit['status'], 'class' => 'bg-blue-lt'] : null,
        ]));
    }

    public function saveAudit(string $id): void
    {
        $data = [
            trim((string) $this->input('audit_number')) ?: null,
            (int) $this->input('client_id'),
            (int) $this->input('certification_type_id'),
            $this->input('auditor_id') ? (int) $this->input('auditor_id') : null,
            $this->input('source', 'manual'),
            $this->input('start_date') ?: null,
            $this->input('end_date') ?: null,
            $this->input('due_date') ?: null,
            trim((string) $this->input('audit_scope')) ?: null,
        ];
        if ($id === 'new') {
            $this->db()->statement('insert into audits (audit_number, client_id, certification_type_id, auditor_id, source, status, start_date, end_date, due_date, audit_scope, created_by, created_at, updated_at) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, now(), now())', [$data[0], $data[1], $data[2], $data[3], $data[4], 'draft', $data[5], $data[6], $data[7], $data[8], Auth::id()]);
            $id = (string) $this->db()->pdo()->lastInsertId();
            $criteria = $this->db()->select('select id from audit_criteria where certification_type_id = ? and is_active = 1', [$data[2]]);
            foreach ($criteria as $criterion) {
                $this->db()->statement('insert ignore into audit_responses (audit_id, audit_criterion_id, created_at, updated_at) values (?, ?, now(), now())', [$id, $criterion['id']]);
            }
        } else {
            $this->db()->statement('update audits set audit_number = ?, client_id = ?, certification_type_id = ?, auditor_id = ?, source = ?, start_date = ?, end_date = ?, due_date = ?, audit_scope = ?, updated_at = now() where id = ?', [...$data, $id]);
        }
        (new ActivityLogger())->log('audit_saved', 'Audit saved.', ['audit_id' => (int) $id, 'client_id' => $data[1]]);
        Session::flash('success', 'Audit saved.');
        redirect('/admin/audits/' . $id);
    }

    public function certificateDetail(string $id): void
    {
        $certificate = $id === 'new' ? null : $this->db()->first(
            'select certificates.*, clients.legal_name, clients.abn, clients.registered_office_address, certification_types.name as certification
             from certificates join clients on clients.id = certificates.client_id join certification_types on certification_types.id = certificates.certification_type_id where certificates.id = ?',
            [$id]
        );
        $audits = $this->db()->select('select audits.id, audits.audit_number, clients.legal_name as client, certification_types.name as certification from audits join clients on clients.id = audits.client_id join certification_types on certification_types.id = audits.certification_type_id order by audits.created_at desc limit 1000');
        $title = $certificate['certificate_number'] ?? 'New certificate';
        $this->view('admin/certificate-detail', [
            'title' => $title,
            'certificate' => $certificate,
            'audits' => $audits,
        ] + $this->pageHeader('CERTIFICATE RECORD', $title, $certificate ? 'Manage certificate issue details and generated PDF output.' : 'Create a certificate from a completed audit.', [
            'status' => $certificate ? ['label' => (string) $certificate['status'], 'class' => $certificate['status'] === 'issued' ? 'bg-success-lt' : 'bg-secondary-lt'] : null,
        ]));
    }

    public function saveCertificate(string $id): void
    {
        $audit = $this->db()->first('select * from audits where id = ?', [(int) $this->input('audit_id')]);
        if (!$audit) {
            Session::flash('error', 'Select a valid audit.');
            redirect('/admin/certificates/' . $id);
        }

        $data = [
            $audit['id'],
            $audit['client_id'],
            $audit['certification_type_id'],
            trim((string) $this->input('certificate_number')),
            (int) ($this->input('issue_number', 1) ?: 1),
            $this->input('status', 'draft'),
            $this->input('issue_date') ?: date('Y-m-d'),
            $this->input('expiry_date') ?: date('Y-m-d', strtotime('+12 months')),
            $this->input('last_day_to_renew') ?: null,
            trim((string) $this->input('audit_scope')) ?: null,
        ];
        if ($data[3] === '') {
            Session::flash('error', 'Certificate number is required.');
            redirect('/admin/certificates/' . $id);
        }

        if ($id === 'new') {
            $this->db()->statement(
                'insert into certificates (certificate_uid, audit_id, client_id, certification_type_id, certificate_number, issue_number, status, issue_date, expiry_date, last_day_to_renew, audit_scope, validation_token, created_at, updated_at) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, now(), now())',
                [bin2hex(random_bytes(16)), ...$data, bin2hex(random_bytes(24))]
            );
            $id = (string) $this->db()->pdo()->lastInsertId();
            (new ActivityLogger())->log('certificate_created', 'Certificate created.', ['certificate_id' => (int) $id, 'client_id' => (int) $audit['client_id']]);
        } else {
            $this->db()->statement('update certificates set audit_id = ?, client_id = ?, certification_type_id = ?, certificate_number = ?, issue_number = ?, status = ?, issue_date = ?, expiry_date = ?, last_day_to_renew = ?, audit_scope = ?, updated_at = now() where id = ?', [...$data, $id]);
            (new ActivityLogger())->log('certificate_updated', 'Certificate updated.', ['certificate_id' => (int) $id, 'client_id' => (int) $audit['client_id']]);
        }
        Session::flash('success', 'Certificate saved.');
        redirect('/admin/certificates/' . $id);
    }

    public function userDetail(string $id): void
    {
        $user = $id === 'new' ? null : $this->db()->first('select * from users where id = ?', [$id]);
        $title = $user['name'] ?? 'New user';
        $this->view('admin/user-detail', [
            'title' => $title,
            'record' => $user,
        ] + $this->pageHeader('USER RECORD', $title, $user ? 'Manage access, role and account status.' : 'Invite a new portal user.', [
            'status' => $user ? ['label' => (string) $user['status'], 'class' => $user['status'] === 'active' ? 'bg-success-lt' : 'bg-secondary-lt'] : null,
        ]));
    }

    public function saveUser(string $id): void
    {
        $name = trim((string) $this->input('name'));
        $email = strtolower(trim((string) $this->input('email')));
        $role = (string) $this->input('role', 'client');
        $status = (string) $this->input('status', 'active');
        $password = (string) $this->input('password');
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'Name and valid email are required.');
            redirect('/admin/users/' . $id);
        }
        if ($id === 'new') {
            if (strlen($password) < 10) {
                Session::flash('error', 'New users need a temporary password of at least 10 characters.');
                redirect('/admin/users/new');
            }
            $this->db()->statement('insert into users (name, email, password_hash, role, status, email_verified_at, created_at, updated_at) values (?, ?, ?, ?, ?, now(), now(), now())', [$name, $email, password_hash($password, PASSWORD_DEFAULT), $role, $status]);
            $id = (string) $this->db()->pdo()->lastInsertId();
            (new ActivityLogger())->log('user_created', 'User created.');
        } else {
            $this->db()->statement('update users set name = ?, email = ?, role = ?, status = ?, updated_at = now() where id = ?', [$name, $email, $role, $status, $id]);
            if ($password !== '') {
                if (strlen($password) < 10) {
                    Session::flash('error', 'Password must be at least 10 characters.');
                    redirect('/admin/users/' . $id);
                }
                $this->db()->statement('update users set password_hash = ?, updated_at = now() where id = ?', [password_hash($password, PASSWORD_DEFAULT), $id]);
            }
            (new ActivityLogger())->log('user_updated', 'User updated.');
        }
        Session::flash('success', 'User saved.');
        redirect('/admin/users/' . $id);
    }

    public function auditCriteria(): void
    {
        $rows = $this->db()->select('select audit_criteria.*, certification_types.name as certification from audit_criteria join certification_types on certification_types.id = audit_criteria.certification_type_id order by certification_types.name, sort_order');
        $this->view('admin/simple-table', [
            'title' => 'Audit criteria',
            'rows' => $rows,
            'detailPrefix' => '/admin/audit-criteria/',
            'createUrl' => '/admin/audit-criteria/new',
            'tableName' => 'audit_criteria',
        ] + $this->pageHeader('ADMIN SETTINGS', 'Audit criteria', 'Maintain certification assessment criteria and evidence prompts.', [
            'primaryAction' => ['label' => 'New criterion', 'href' => '/admin/audit-criteria/new', 'icon' => 'ti-plus'],
        ]) + $this->tableContext('audit_criteria'));
    }

    public function auditCriterionDetail(string $id): void
    {
        $criterion = $id === 'new' ? null : $this->db()->first('select * from audit_criteria where id = ?', [$id]);
        $types = $this->db()->select('select id, name from certification_types order by name');
        $title = $criterion['title'] ?? 'New audit criterion';
        $this->view('admin/audit-criterion-detail', [
            'title' => $title,
            'criterion' => $criterion,
            'types' => $types,
        ] + $this->pageHeader('AUDIT CRITERION', $title, 'Define the assessment wording, evidence prompt and display order.', [
            'status' => $criterion ? ['label' => ((int) $criterion['is_active'] === 1 ? 'Active' : 'Inactive'), 'class' => ((int) $criterion['is_active'] === 1 ? 'bg-success-lt' : 'bg-secondary-lt')] : null,
        ]));
    }

    public function saveAuditCriterion(string $id): void
    {
        $data = [
            (int) $this->input('certification_type_id'),
            (int) ($this->input('version', 1) ?: 1),
            trim((string) $this->input('section')) ?: null,
            trim((string) $this->input('reference')) ?: null,
            trim((string) $this->input('title')),
            trim((string) $this->input('requirement_text')),
            trim((string) $this->input('guidance_text')) ?: null,
            trim((string) $this->input('evidence_prompt')) ?: null,
            (int) ($this->input('sort_order', 0) ?: 0),
            $this->input('is_required') ? 1 : 0,
            $this->input('is_active') ? 1 : 0,
        ];
        if ($data[4] === '' || $data[5] === '') {
            Session::flash('error', 'Title and requirement text are required.');
            redirect('/admin/audit-criteria/' . $id);
        }
        if ($id === 'new') {
            $this->db()->statement('insert into audit_criteria (certification_type_id, version, section, reference, title, requirement_text, guidance_text, evidence_prompt, sort_order, is_required, is_active, created_at, updated_at) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, now(), now())', $data);
            $id = (string) $this->db()->pdo()->lastInsertId();
        } else {
            $this->db()->statement('update audit_criteria set certification_type_id = ?, version = ?, section = ?, reference = ?, title = ?, requirement_text = ?, guidance_text = ?, evidence_prompt = ?, sort_order = ?, is_required = ?, is_active = ?, updated_at = now() where id = ?', [...$data, $id]);
        }
        (new ActivityLogger())->log('audit_criterion_saved', 'Audit criterion saved.');
        Session::flash('success', 'Audit criterion saved.');
        redirect('/admin/audit-criteria/' . $id);
    }

    public function emailTemplates(): void
    {
        $rows = $this->db()->select('select id, template_key, name, subject, is_active, updated_at from email_templates order by template_key');
        $this->view('admin/templates', [
            'title' => 'Templates',
            'rows' => $rows,
            'tableName' => 'email_templates',
        ] + $this->pageHeader('ADMIN SETTINGS', 'Templates', 'Manage email templates and certificate Word template files.', [
            'primaryAction' => ['label' => 'New email template', 'href' => '/admin/email-templates/new', 'icon' => 'ti-plus'],
        ]) + $this->tableContext('email_templates'));
    }

    public function emailTemplateDetail(string $id): void
    {
        $template = $id === 'new' ? null : $this->db()->first('select * from email_templates where id = ?', [$id]);
        $title = $template['name'] ?? 'New email template';
        $this->view('admin/email-template-detail', [
            'title' => $title,
            'template' => $template,
        ] + $this->pageHeader('EMAIL TEMPLATE', $title, 'Edit HTML, plain text fallback and delivery status.', [
            'status' => $template ? ['label' => ((int) $template['is_active'] === 1 ? 'Active' : 'Inactive'), 'class' => ((int) $template['is_active'] === 1 ? 'bg-success-lt' : 'bg-secondary-lt')] : null,
        ]));
    }

    public function saveEmailTemplate(string $id): void
    {
        $data = [
            trim((string) $this->input('template_key')),
            trim((string) $this->input('name')),
            trim((string) $this->input('subject')),
            (string) $this->input('body_html'),
            trim((string) $this->input('body_text')) ?: null,
            $this->input('is_active') ? 1 : 0,
        ];
        if ($data[0] === '' || $data[1] === '' || $data[2] === '' || $data[3] === '') {
            Session::flash('error', 'Template key, name, subject and HTML body are required.');
            redirect('/admin/email-templates/' . $id);
        }
        if ($id === 'new') {
            $this->db()->statement('insert into email_templates (template_key, name, subject, body_html, body_text, is_active, created_by, created_at, updated_at) values (?, ?, ?, ?, ?, ?, ?, now(), now())', [...$data, \App\Core\Auth::id()]);
            $id = (string) $this->db()->pdo()->lastInsertId();
        } else {
            $this->db()->statement('update email_templates set template_key = ?, name = ?, subject = ?, body_html = ?, body_text = ?, is_active = ?, updated_by = ?, updated_at = now() where id = ?', [...$data, \App\Core\Auth::id(), $id]);
        }
        (new ActivityLogger())->log('email_template_saved', 'Email template saved.');
        Session::flash('success', 'Email template saved.');
        redirect('/admin/email-templates/' . $id);
    }

    public function saveCertificateTemplate(): void
    {
        if (empty($_FILES['template_file']['tmp_name']) || !is_uploaded_file($_FILES['template_file']['tmp_name'])) {
            Session::flash('error', 'Choose a Microsoft Word certificate template to upload.');
            redirect('/admin/email-templates');
        }

        $original = (string) ($_FILES['template_file']['name'] ?? '');
        $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        if (!in_array($extension, ['doc', 'docx'], true)) {
            Session::flash('error', 'Certificate templates must be Microsoft Word .doc or .docx files.');
            redirect('/admin/email-templates');
        }

        $directory = app('root') . '/storage/certificate-templates';
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $filename = date('YmdHis') . '-' . preg_replace('/[^A-Za-z0-9_.-]/', '-', $original);
        if (!move_uploaded_file($_FILES['template_file']['tmp_name'], $directory . '/' . $filename)) {
            Session::flash('error', 'The certificate template could not be uploaded.');
            redirect('/admin/email-templates');
        }

        $settingsService = new SettingsService();
        $settingsService->save([
            'certificate_template_file' => $filename,
            'certificate_template_uploaded_at' => date('c'),
        ]);

        (new ActivityLogger())->log('certificate_template_uploaded', 'Certificate Word template uploaded.');
        Session::flash('success', 'Certificate template uploaded.');
        redirect('/admin/email-templates');
    }

    public function reminderRules(): void
    {
        $rows = $this->db()->select('select reminder_rules.id, certification_types.name as certification, days_before_expiry, email_templates.template_key, enabled, manual_send_allowed from reminder_rules left join certification_types on certification_types.id = reminder_rules.certification_type_id join email_templates on email_templates.id = reminder_rules.template_id order by days_before_expiry desc');
        $this->view('admin/simple-table', [
            'title' => 'Reminder rules',
            'rows' => $rows,
            'detailPrefix' => null,
            'tableName' => 'reminder_rules',
        ] + $this->pageHeader('ADMIN SETTINGS', 'Reminder rules', 'Review reminder timing, template assignment and manual send rules.') + $this->tableContext('reminder_rules'));
    }

    public function exports(): void
    {
        $table = (string) $this->input('table');
        if ($table !== '') {
            $csv = (new TableService())->csv($table, $this->tableFilters());
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $table . '.csv"');
            echo $csv;
            return;
        }
        redirect('/admin/clients');
    }

    public function payments(): void
    {
        $this->table('payments', 'Payments', '');
    }

    public function emailLogs(): void
    {
        $this->table('email_logs', 'Email log', '');
    }

    public function activityLogs(): void
    {
        $rows = $this->db()->select('select activity_logs.id, users.name as actor, activity_logs.action, activity_logs.description, clients.legal_name as client, activity_logs.created_at from activity_logs left join users on users.id = activity_logs.actor_user_id left join clients on clients.id = activity_logs.client_id order by activity_logs.created_at desc limit 500');
        $this->view('admin/simple-table', [
            'title' => 'Activity logs',
            'rows' => $rows,
            'detailPrefix' => null,
            'tableName' => 'activity_logs',
        ] + $this->pageHeader('LOGS', 'Activity logs', 'Review user and system activity across the portal.') + $this->tableContext('activity_logs'));
    }

    public function settings(): void
    {
        $settingsService = new SettingsService();
        $users = $this->db()->select('select id, name, email, role, status from users order by name limit 100');
        $criteria = $this->db()->select('select audit_criteria.id, certification_types.name as certification, audit_criteria.section, audit_criteria.title, audit_criteria.is_active from audit_criteria join certification_types on certification_types.id = audit_criteria.certification_type_id order by certification_types.name, audit_criteria.sort_order limit 100');
        $this->view('admin/settings', [
            'title' => 'Settings',
            'settings' => $settingsService->all(),
            'theme' => $settingsService->userTheme(),
            'users' => $users,
            'criteria' => $criteria,
        ] + $this->pageHeader('ADMIN SETTINGS', 'Settings', 'Configure portal identity, security, notifications, users and audit criteria.'));
    }

    public function saveSettings(): void
    {
        $allowed = [
            'business_name', 'abn', 'support_email', 'billing_email', 'registered_address',
            'primary_colour', 'accent_colour', 'logo_url', 'certificate_validity_months',
            'renewal_reminder_days', 'sender_name', 'sender_email', 'session_timeout',
            'login_attempt_limit', 'admin_landing_page', 'records_per_table',
        ];
        $settings = [];
        foreach ($allowed as $key) {
            $settings[$key] = trim((string) ($_POST[$key] ?? ''));
        }
        foreach ([
            'show_logo', 'auto_generate_pdf', 'public_verification', 'send_audit_reminders',
            'send_expiry_notices', 'strong_passwords', 'require_admin_mfa',
            'client_self_service_contacts', 'show_public_verification_link',
        ] as $key) {
            $settings[$key] = isset($_POST[$key]) ? '1' : '0';
        }

        $settingsService = new SettingsService();
        $settingsService->save($settings);
        if ($userId = Auth::id()) {
            $settingsService->saveUserTheme($userId, (string) ($_POST['theme'] ?? 'light'));
        }

        (new ActivityLogger())->log('settings_saved', 'Portal settings updated.');
        Session::flash('success', 'Settings saved.');
        redirect('/admin/settings');
    }

    private function table(string $table, string $title, string $detailPrefix): void
    {
        $primaryAction = in_array($table, ['clients', 'contacts', 'audits', 'certificates', 'users'], true) ? [
            'label' => 'New ' . rtrim(strtolower($title), 's'),
            'href' => $detailPrefix . 'new',
            'icon' => 'ti-plus',
        ] : null;
        $this->view('admin/simple-table', [
            'title' => $title,
            'rows' => (new TableService())->rows($table, $this->tableFilters()),
            'detailPrefix' => $detailPrefix,
            'tableName' => $table,
            'createUrl' => $primaryAction['href'] ?? null,
            'primaryAction' => $primaryAction,
            'pageSubtitle' => 'Search, filter, export and open operational records.',
        ] + $this->tableContext($table) + $this->pageHeader($this->tableEyebrow($table), $title, 'Search, filter, export and open operational records.', [
            'primaryAction' => $primaryAction,
        ]));
    }

    private function tableEyebrow(string $table): string
    {
        return match ($table) {
            'payments', 'email_logs' => 'LOGS',
            default => 'ADMIN PORTAL',
        };
    }

    private function tableFilters(): array
    {
        return [
            'view' => (string) ($_GET['view'] ?? ''),
            'status' => (string) ($_GET['status'] ?? ''),
            'role' => (string) ($_GET['role'] ?? ''),
        ];
    }

    private function filterDefinitions(string $table): array
    {
        return match ($table) {
            'clients' => [
                ['key' => 'status', 'label' => 'Status', 'column' => 'status', 'type' => 'select', 'options' => ['active' => 'Active', 'inactive' => 'Inactive', 'archived' => 'Archived']],
            ],
            'contacts' => [
                ['key' => 'is_active', 'label' => 'Status', 'column' => 'is_active', 'type' => 'select', 'options' => ['active' => 'Active', 'inactive' => 'Inactive']],
                ['key' => 'receives_reminders', 'label' => 'Reminders', 'column' => 'receives_reminders', 'type' => 'select', 'options' => ['1' => 'Receives reminders', '0' => 'No reminders']],
            ],
            'audits' => [
                ['key' => 'status', 'label' => 'Status', 'column' => 'status', 'type' => 'select', 'options' => ['draft' => 'Draft', 'awaiting_evidence' => 'Awaiting evidence', 'submitted' => 'Submitted', 'in_review' => 'In review', 'changes_requested' => 'Changes requested', 'passed' => 'Passed', 'failed' => 'Failed']],
                ['key' => 'auditor', 'label' => 'Auditor', 'column' => 'auditor', 'type' => 'text'],
                ['key' => 'due_date', 'label' => 'Due date', 'column' => 'due_date', 'type' => 'date_range'],
            ],
            'certificates' => [
                ['key' => 'computed_status', 'label' => 'Date status', 'column' => 'computed_status', 'type' => 'select', 'options' => ['current' => 'Current', 'expiring_soon' => 'Expiring soon', 'expired' => 'Expired']],
                ['key' => 'pdf_status', 'label' => 'PDF', 'column' => 'pdf_status', 'type' => 'select', 'options' => ['pdf_ready' => 'PDF ready', 'missing_pdf' => 'Missing PDF']],
                ['key' => 'expiry_date', 'label' => 'Expiry date', 'column' => 'expiry_date', 'type' => 'date_range'],
            ],
            'payments' => [
                ['key' => 'status', 'label' => 'Status', 'column' => 'status', 'type' => 'select', 'options' => ['paid' => 'Paid', 'pending' => 'Pending', 'failed' => 'Failed']],
                ['key' => 'created_at', 'label' => 'Created', 'column' => 'created_at', 'type' => 'date_range'],
            ],
            'email_logs' => [
                ['key' => 'status', 'label' => 'Status', 'column' => 'status', 'type' => 'select', 'options' => ['sent' => 'Sent', 'failed' => 'Failed', 'queued' => 'Queued']],
                ['key' => 'created_at', 'label' => 'Created', 'column' => 'created_at', 'type' => 'date_range'],
            ],
            'users' => [
                ['key' => 'role', 'label' => 'Role', 'column' => 'role', 'type' => 'select', 'options' => ['admin' => 'Admin', 'auditor' => 'Auditor', 'client' => 'Client']],
                ['key' => 'status', 'label' => 'Status', 'column' => 'status', 'type' => 'select', 'options' => ['active' => 'Active', 'invited' => 'Invited', 'disabled' => 'Disabled']],
            ],
            'audit_criteria' => [
                ['key' => 'is_active', 'label' => 'Status', 'column' => 'is_active', 'type' => 'select', 'options' => ['1' => 'Active', '0' => 'Inactive']],
                ['key' => 'certification', 'label' => 'Certification', 'column' => 'certification', 'type' => 'text'],
            ],
            'email_templates' => [
                ['key' => 'is_active', 'label' => 'Status', 'column' => 'is_active', 'type' => 'select', 'options' => ['1' => 'Active', '0' => 'Inactive']],
                ['key' => 'updated_at', 'label' => 'Updated', 'column' => 'updated_at', 'type' => 'date_range'],
            ],
            'reminder_rules' => [
                ['key' => 'enabled', 'label' => 'Status', 'column' => 'enabled', 'type' => 'select', 'options' => ['1' => 'Enabled', '0' => 'Disabled']],
                ['key' => 'certification', 'label' => 'Certification', 'column' => 'certification', 'type' => 'text'],
            ],
            'activity_logs' => [
                ['key' => 'action', 'label' => 'Action', 'column' => 'action', 'type' => 'text'],
                ['key' => 'created_at', 'label' => 'Created', 'column' => 'created_at', 'type' => 'date_range'],
            ],
            default => [
                ['key' => 'created_at', 'label' => 'Created', 'column' => 'created_at', 'type' => 'date_range'],
            ],
        };
    }

    private function bulkActions(string $table): array
    {
        return [
            ['key' => 'export_selected', 'label' => 'Export selected CSV', 'icon' => 'ti-download'],
            ['key' => 'clear_selection', 'label' => 'Clear selection', 'icon' => 'ti-square'],
        ];
    }

    private function tableContext(string $table): array
    {
        $viewService = new TableViewService();
        return [
            'filterDefinitions' => $this->filterDefinitions($table),
            'savedViews' => $viewService->views($table),
            'activeSavedView' => $viewService->activeView($table, (int) ($_GET['saved_view'] ?? 0)),
            'tablePreferences' => $viewService->preferences($table),
            'bulkActions' => $this->bulkActions($table),
        ];
    }
}
