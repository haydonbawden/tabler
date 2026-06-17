<?php

declare(strict_types=1);

use App\Core\Database;

return function (Database $db): void {
    $now = date('Y-m-d H:i:s');

    $types = [
        ['HVNL Management Plan', 'HVNL'],
        ['WHS Management System', 'WHS'],
        ['WHSEQ Integrated Management System', 'WHSEQ'],
    ];
    foreach ($types as [$name, $code]) {
        $db->statement(
            'insert into certification_types (name, code, validity_months, created_at, updated_at)
             values (?, ?, 12, ?, ?)
             on duplicate key update name = values(name), updated_at = values(updated_at)',
            [$name, $code, $now, $now]
        );
    }

    $criteria = [
        ['Governance', '1.1', 'Management commitment', 'Provide evidence of management commitment, policy ownership and review cadence.'],
        ['Planning', '2.1', 'Risk and compliance register', 'Provide the current risk, legal and compliance obligations register.'],
        ['Operations', '3.1', 'Operational controls', 'Provide procedures, records or system screenshots demonstrating operational control.'],
        ['Review', '4.1', 'Internal review and improvement', 'Provide review records, corrective actions and improvement evidence.'],
    ];
    foreach ($db->select('select id from certification_types') as $type) {
        foreach ($criteria as $index => [$section, $reference, $title, $requirement]) {
            $exists = $db->scalar('select count(*) from audit_criteria where certification_type_id = ? and reference = ?', [$type['id'], $reference]);
            if (!$exists) {
                $db->statement(
                    'insert into audit_criteria (certification_type_id, section, reference, title, requirement_text, evidence_prompt, sort_order, created_at, updated_at) values (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [$type['id'], $section, $reference, $title, $requirement, 'Upload supporting files and describe how the requirement is met.', ($index + 1) * 10, $now, $now]
                );
            }
        }
    }

    $templates = [
        'client_invitation' => ['Client invitation', 'You have been invited to Castor Portal', '<p>Hello {{contact_name}},</p><p>You have been invited to access {{client_name}} in Castor Portal.</p><p><a href="{{portal_link}}">Open portal</a></p>'],
        'audit_evidence_request' => ['Audit evidence request', 'Audit evidence required: {{audit_number}}', '<p>A renewal audit has been created for {{client_name}}.</p><p>Please log in or register, then submit evidence against each audit criterion.</p><p><a href="{{portal_link}}">Open portal</a></p>'],
        'audit_submitted_confirmation' => ['Audit submitted confirmation', 'Audit submitted: {{audit_number}}', '<p>Your audit evidence has been submitted for review.</p>'],
        'auditor_changes_requested' => ['Changes requested', 'Changes requested: {{audit_number}}', '<p>Your auditor has requested more information for {{audit_number}}.</p>'],
        'audit_passed' => ['Audit passed', 'Audit passed: {{audit_number}}', '<p>Your audit has passed. Certificate generation will follow.</p>'],
        'audit_failed' => ['Audit failed', 'Audit failed: {{audit_number}}', '<p>Your audit result has been recorded as failed.</p>'],
        'certificate_issued' => ['Certificate issued', 'Certificate issued: {{certificate_number}}', '<p>Your certificate has been issued and is available through Castor Portal.</p>'],
        'certificate_expiring_soon' => ['Certificate expiring soon', 'Certificate expiring soon: {{certificate_number}}', '<p>Your {{certification}} certificate expires on {{expiry_date}}.</p><p>A renewal audit is required to maintain certification.</p><p><a href="{{renewal_payment_link}}">Pay for renewal audit</a></p>'],
        'certificate_expired' => ['Certificate expired', 'Certificate expired: {{certificate_number}}', '<p>Your certificate expired on {{expiry_date}}. Please arrange a renewal audit.</p>'],
        'renewal_payment_confirmation' => ['Renewal payment confirmation', 'Renewal payment received', '<p>Payment has been received and a renewal audit has been created.</p>'],
    ];

    foreach ($templates as $key => [$name, $subject, $body]) {
        $db->statement(
            'insert into email_templates (template_key, name, subject, body_html, merge_fields_json, created_at, updated_at)
             values (?, ?, ?, ?, ?, ?, ?)
             on duplicate key update name = values(name), subject = values(subject), body_html = values(body_html), updated_at = values(updated_at)',
            [$key, $name, $subject, $body, json_encode(['client_name', 'client_abn', 'contact_name', 'certificate_number', 'certification', 'issue_date', 'expiry_date', 'last_day_to_renew', 'audit_number', 'audit_due_date', 'portal_link', 'renewal_payment_link', 'castor_phone', 'castor_email'], JSON_THROW_ON_ERROR), $now, $now]
        );
    }

    $templateId = $db->scalar('select id from email_templates where template_key = ?', ['certificate_expiring_soon']);
    foreach ([90, 60, 30, 14, 7, 0, -7] as $days) {
        $exists = $db->scalar('select count(*) from reminder_rules where days_before_expiry = ? and template_id = ?', [$days, $templateId]);
        if (!$exists) {
            $db->statement('insert into reminder_rules (days_before_expiry, template_id, created_at, updated_at) values (?, ?, ?, ?)', [$days, $templateId, $now, $now]);
        }
    }

    $admin = $db->scalar('select count(*) from users where email = ?', ['admin@castoraustralia.com.au']);
    if (!$admin) {
        $db->statement(
            'insert into users (name, email, password_hash, role, status, email_verified_at, created_at, updated_at)
             values (?, ?, ?, ?, ?, ?, ?, ?)',
            ['Castor Admin', 'admin@castoraustralia.com.au', password_hash('ChangeMe123!', PASSWORD_DEFAULT), 'admin', 'active', $now, $now, $now]
        );
    }
};
