<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;

final class TableService
{
    private const MAP = [
        'clients' => "select clients.id, clients.client_number, clients.abn, clients.legal_name, clients.trading_name, clients.status,
            count(distinct case when audits.status not in ('passed','failed','cancelled') then audits.id end) as active_audits,
            count(distinct case when certificates.status = 'issued' and certificates.expiry_date >= curdate() then certificates.id end) as current_certificates,
            count(distinct case when certificates.expiry_date between curdate() and date_add(curdate(), interval 90 day) then certificates.id end) as expiring_certificates,
            min(contacts.email) as primary_contact,
            clients.created_at
            from clients
            left join audits on audits.client_id = clients.id
            left join certificates on certificates.client_id = clients.id
            left join contacts on contacts.client_id = clients.id and contacts.is_active = 1
            group by clients.id, clients.client_number, clients.abn, clients.legal_name, clients.trading_name, clients.status, clients.created_at
            order by clients.legal_name",
        'contacts' => 'select contacts.id, clients.legal_name as client, contacts.display_name, contacts.email, contacts.phone, contacts.position_title, contacts.receives_reminders, contacts.receives_certificates, contacts.receives_audit_notifications, contacts.is_active from contacts join clients on clients.id = contacts.client_id order by contacts.display_name',
        'audits' => "select audits.id, audits.audit_number, clients.legal_name as client, certification_types.name as certification, coalesce(users.name, 'Unassigned') as auditor, audits.status, audits.result,
            concat(coalesce(round(100 * sum(case when audit_responses.client_status = 'submitted' then 1 else 0 end) / nullif(count(audit_responses.id), 0)), 0), '%') as evidence_progress,
            audits.due_date,
            max(coalesce(audit_responses.updated_at, audits.updated_at)) as last_activity,
            coalesce(max(certificates.status), 'No certificate') as certificate_status,
            audits.completed_at
            from audits
            join clients on clients.id = audits.client_id
            join certification_types on certification_types.id = audits.certification_type_id
            left join users on users.id = audits.auditor_id
            left join audit_responses on audit_responses.audit_id = audits.id
            left join certificates on certificates.audit_id = audits.id
            group by audits.id, audits.audit_number, clients.legal_name, certification_types.name, users.name, audits.status, audits.result, audits.due_date, audits.completed_at
            order by audits.created_at desc",
        'certificates' => "select certificates.id, certificates.certificate_number, clients.legal_name as client, certification_types.name as certification, certificates.issue_number,
            certificates.issue_date, certificates.expiry_date, certificates.last_day_to_renew, certificates.status as stored_status,
            case when certificates.expiry_date < curdate() then 'expired' when certificates.expiry_date <= date_add(curdate(), interval 90 day) then 'expiring_soon' else 'current' end as computed_status,
            case when certificates.pdf_path is null or certificates.pdf_path = '' then 'missing_pdf' else 'pdf_ready' end as pdf_status,
            certificates.emailed_at
            from certificates join clients on clients.id = certificates.client_id join certification_types on certification_types.id = certificates.certification_type_id order by certificates.expiry_date desc",
        'users' => 'select id, name, email, role, status, email_verified_at, last_login_at from users order by name',
        'payments' => 'select payments.id, clients.legal_name as client, amount_cents, currency, payments.status, paid_at, payments.created_at from payments join clients on clients.id = payments.client_id order by payments.created_at desc',
        'email_logs' => 'select id, to_email, subject, status, sent_at, failed_at, created_at from email_logs order by created_at desc',
        'activity_logs' => 'select activity_logs.id, users.name as actor, activity_logs.action, activity_logs.description, clients.legal_name as client, activity_logs.created_at from activity_logs left join users on users.id = activity_logs.actor_user_id left join clients on clients.id = activity_logs.client_id order by activity_logs.created_at desc',
        'audit_criteria' => 'select audit_criteria.id, certification_types.name as certification, audit_criteria.section, audit_criteria.reference, audit_criteria.title, audit_criteria.is_required, audit_criteria.is_active from audit_criteria join certification_types on certification_types.id = audit_criteria.certification_type_id order by certification_types.name, audit_criteria.sort_order',
    ];

    public function rows(string $table, array $filters = []): array
    {
        if (!isset(self::MAP[$table])) {
            throw new \InvalidArgumentException('Unknown table: ' . $table);
        }

        [$sql, $bindings] = $this->filteredQuery($table, $filters);
        return App::instance()->database->select($sql, $bindings);
    }

    public function csv(string $table, array $filters = []): string
    {
        $rows = $this->rows($table, $filters);
        if (!$rows) {
            return '';
        }

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, array_keys($rows[0]), ',', '"', '\\', "\n");
        foreach ($rows as $row) {
            fputcsv($handle, $row, ',', '"', '\\', "\n");
        }
        rewind($handle);
        return stream_get_contents($handle) ?: '';
    }

    private function filteredQuery(string $table, array $filters): array
    {
        $where = [];
        $bindings = [];
        $view = (string) ($filters['view'] ?? '');
        $status = (string) ($filters['status'] ?? '');

        if ($table === 'clients' && $status !== '') {
            $where[] = 'filtered.status = ?';
            $bindings[] = $status;
        }

        if ($table === 'audits') {
            $auditView = $view !== '' ? $view : $status;
            if (in_array($auditView, ['draft', 'awaiting_evidence', 'submitted', 'in_review', 'changes_requested', 'passed', 'failed', 'cancelled'], true)) {
                $where[] = 'filtered.status = ?';
                $bindings[] = $auditView;
            } elseif ($auditView === 'active') {
                $where[] = "filtered.status not in ('passed','failed','cancelled')";
            } elseif ($auditView === 'evidence_outstanding') {
                $where[] = "filtered.status in ('awaiting_evidence','changes_requested')";
            } elseif ($auditView === 'completed') {
                $where[] = "filtered.status in ('passed','failed')";
            } elseif ($auditView === 'passed_month') {
                $where[] = "filtered.status = 'passed' and filtered.completed_at >= date_format(curdate(), '%Y-%m-01')";
            } elseif ($auditView === 'unassigned') {
                $where[] = "filtered.auditor = 'Unassigned'";
            }
        }

        if ($table === 'certificates') {
            $certificateView = $view !== '' ? $view : $status;
            if (in_array($certificateView, ['current', 'expired', 'missing_pdf', 'pdf_ready'], true)) {
                $column = in_array($certificateView, ['missing_pdf', 'pdf_ready'], true) ? 'pdf_status' : 'computed_status';
                $where[] = "filtered.{$column} = ?";
                $bindings[] = $certificateView;
            } elseif (in_array($certificateView, ['expiring_90', 'expiring_soon'], true)) {
                $where[] = "filtered.computed_status = 'expiring_soon'";
            } elseif (in_array($certificateView, ['draft', 'issued', 'revoked', 'replaced'], true)) {
                $where[] = 'filtered.stored_status = ?';
                $bindings[] = $certificateView;
            }
        }

        if (in_array($table, ['payments', 'email_logs', 'users'], true) && $status !== '') {
            $where[] = 'filtered.status = ?';
            $bindings[] = $status;
        }

        if ($table === 'users' && ($filters['role'] ?? '') !== '') {
            $where[] = 'filtered.role = ?';
            $bindings[] = (string) $filters['role'];
        }

        $sql = 'select * from (' . self::MAP[$table] . ') filtered';
        if ($where) {
            $sql .= ' where ' . implode(' and ', $where);
        }

        return [$sql, $bindings];
    }
}
