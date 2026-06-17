<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Core\Database;

final class DashboardService
{
    private Database $db;

    public function __construct()
    {
        $this->db = App::instance()->database;
    }

    public function adminStats(): array
    {
        return [
            'total_clients' => $this->db->scalar('select count(*) from clients'),
            'active_clients' => $this->db->scalar("select count(*) from clients where status = 'active'"),
            'current_certificates' => $this->db->scalar("select count(*) from certificates where status = 'issued' and expiry_date >= curdate()"),
            'expiring_90' => $this->db->scalar("select count(*) from certificates where expiry_date between curdate() and date_add(curdate(), interval 90 day)"),
            'expired_certificates' => $this->db->scalar('select count(*) from certificates where expiry_date < curdate()'),
            'awaiting_evidence' => $this->db->scalar("select count(*) from audits where status = 'awaiting_evidence'"),
            'submitted' => $this->db->scalar("select count(*) from audits where status = 'submitted'"),
            'in_review' => $this->db->scalar("select count(*) from audits where status = 'in_review'"),
            'passed_month' => $this->db->scalar("select count(*) from audits where status = 'passed' and completed_at >= date_format(curdate(), '%Y-%m-01')"),
            'failed' => $this->db->scalar("select count(*) from audits where status = 'failed'"),
            'payments_month' => $this->db->scalar("select coalesce(sum(amount_cents), 0) from payments where status = 'paid' and paid_at >= date_format(curdate(), '%Y-%m-01')"),
            'failed_emails' => $this->db->scalar("select count(*) from email_logs where status = 'failed'"),
            'failed_imports' => $this->db->scalar("select count(*) from import_runs where status = 'failed'"),
            'missing_pdfs' => $this->db->scalar("select count(*) from certificates where status = 'issued' and (pdf_path is null or pdf_path = '')"),
            'unassigned_audits' => $this->db->scalar('select count(*) from audits where auditor_id is null and status not in ("passed","failed","cancelled")'),
        ];
    }

    public function adminQueues(): array
    {
        return [
            'renewals' => $this->db->select('select certificates.id, certificates.certificate_number, clients.legal_name as client, certificates.expiry_date, certificates.status from certificates join clients on clients.id = certificates.client_id where certificates.expiry_date <= date_add(curdate(), interval 90 day) order by certificates.expiry_date asc limit 8'),
            'audits' => $this->db->select(
                "select audits.id, audits.audit_number, clients.legal_name as client, audits.status, audits.due_date,
                 concat(coalesce(round(100 * sum(case when audit_responses.client_status = 'submitted' then 1 else 0 end) / nullif(count(audit_responses.id), 0)), 0), '%') as evidence_progress
                 from audits
                 join clients on clients.id = audits.client_id
                 left join audit_responses on audit_responses.audit_id = audits.id
                 where audits.status in ('awaiting_evidence','submitted','in_review','changes_requested')
                 group by audits.id, audits.audit_number, clients.legal_name, audits.status, audits.due_date
                 order by audits.due_date asc limit 8"
            ),
            'exceptions' => $this->db->select("select 'Failed email' as type, subject as title, status, created_at, '/admin/email-logs?status=failed' as href from email_logs where status = 'failed' union all select 'Failed import' as type, filename as title, status, created_at, '/admin/imports' as href from import_runs where status = 'failed' order by created_at desc limit 8"),
        ];
    }

    public function auditorStats(int $userId): array
    {
        return [
            'assigned' => $this->db->scalar('select count(*) from audits where auditor_id = ?', [$userId]),
            'awaiting_client' => $this->db->scalar("select count(*) from audits where auditor_id = ? and status = 'awaiting_evidence'", [$userId]),
            'submitted' => $this->db->scalar("select count(*) from audits where auditor_id = ? and status = 'submitted'", [$userId]),
            'in_review' => $this->db->scalar("select count(*) from audits where auditor_id = ? and status = 'in_review'", [$userId]),
            'changes_requested' => $this->db->scalar("select count(*) from audits where auditor_id = ? and status = 'changes_requested'", [$userId]),
            'completed_month' => $this->db->scalar("select count(*) from audits where auditor_id = ? and completed_at >= date_format(curdate(), '%Y-%m-01')", [$userId]),
        ];
    }

    public function auditorQueue(int $userId): array
    {
        return $this->db->select(
            "select audits.id, audits.audit_number, clients.legal_name as client, certification_types.name as certification, audits.status, audits.submitted_at, audits.due_date,
             concat(coalesce(round(100 * sum(case when audit_responses.client_status = 'submitted' then 1 else 0 end) / nullif(count(audit_responses.id), 0)), 0), '%') as evidence_progress
             from audits
             join clients on clients.id = audits.client_id
             join certification_types on certification_types.id = audits.certification_type_id
             left join audit_responses on audit_responses.audit_id = audits.id
             where audits.auditor_id = ? and audits.status not in ('passed','failed','cancelled')
             group by audits.id, audits.audit_number, clients.legal_name, certification_types.name, audits.status, audits.submitted_at, audits.due_date
             order by audits.due_date asc, audits.updated_at desc limit 20",
            [$userId]
        );
    }

    public function clientStats(array $clientIds): array
    {
        return [
            'current' => $this->countForClients("select count(*) from certificates where status = 'issued' and expiry_date >= curdate() and client_id in (%s)", $clientIds),
            'expiring' => $this->countForClients("select count(*) from certificates where status = 'issued' and expiry_date between curdate() and date_add(curdate(), interval 90 day) and client_id in (%s)", $clientIds),
            'expired' => $this->countForClients("select count(*) from certificates where expiry_date < curdate() and client_id in (%s)", $clientIds),
            'active_audits' => $this->countForClients("select count(*) from audits where status not in ('passed','failed','cancelled') and client_id in (%s)", $clientIds),
            'evidence_outstanding' => $this->countForClients("select count(*) from audits where status in ('awaiting_evidence','changes_requested') and client_id in (%s)", $clientIds),
            'submitted' => $this->countForClients("select count(*) from audits where status = 'submitted' and client_id in (%s)", $clientIds),
            'completed' => $this->countForClients("select count(*) from audits where status in ('passed','failed') and client_id in (%s)", $clientIds),
        ];
    }

    public function clientActions(array $clientIds): array
    {
        if (!$clientIds) {
            return [];
        }

        return $this->db->select(
            "select audits.id, audits.audit_number, clients.legal_name as client, audits.status, audits.due_date,
             concat(coalesce(round(100 * sum(case when audit_responses.client_status = 'submitted' then 1 else 0 end) / nullif(count(audit_responses.id), 0)), 0), '%') as evidence_progress
             from audits join clients on clients.id = audits.client_id
             left join audit_responses on audit_responses.audit_id = audits.id
             where audits.client_id in (" . $this->placeholders($clientIds) . ") and audits.status in ('awaiting_evidence','changes_requested')
             group by audits.id, audits.audit_number, clients.legal_name, audits.status, audits.due_date
             order by audits.due_date asc limit 5",
            $clientIds
        );
    }

    public function clientCertificates(array $clientIds): array
    {
        if (!$clientIds) {
            return [];
        }

        return $this->db->select(
            "select certificates.id, certificates.certificate_number, clients.legal_name as client, certificates.expiry_date, certificates.pdf_path
             from certificates join clients on clients.id = certificates.client_id
             where certificates.client_id in (" . $this->placeholders($clientIds) . ") and certificates.status = 'issued'
             order by certificates.expiry_date asc limit 5",
            $clientIds
        );
    }

    private function countForClients(string $sql, array $clientIds): int
    {
        if (!$clientIds) {
            return 0;
        }

        return (int) $this->db->scalar(sprintf($sql, $this->placeholders($clientIds)), $clientIds);
    }

    private function placeholders(array $items): string
    {
        return implode(',', array_fill(0, count($items), '?'));
    }
}
