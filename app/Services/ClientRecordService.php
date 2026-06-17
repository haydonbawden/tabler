<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;

final class ClientRecordService
{
    public function record(int $clientId): ?array
    {
        $db = App::instance()->database;
        $client = $db->first('select * from clients where id = ?', [$clientId]);
        if (!$client) {
            return null;
        }

        $contacts = $db->select('select * from contacts where client_id = ? order by display_name', [$clientId]);
        $audits = $db->select(
            "select audits.*, certification_types.name as certification, coalesce(users.name, 'Unassigned') as auditor
             from audits
             join certification_types on certification_types.id = audits.certification_type_id
             left join users on users.id = audits.auditor_id
             where audits.client_id = ?
             order by audits.created_at desc",
            [$clientId]
        );
        $certificates = $db->select(
            "select certificates.*, certification_types.name as certification,
             case when certificates.expiry_date < curdate() then 'expired' when certificates.expiry_date <= date_add(curdate(), interval 90 day) then 'expiring_soon' else 'current' end as computed_status
             from certificates
             join certification_types on certification_types.id = certificates.certification_type_id
             where certificates.client_id = ?
             order by certificates.expiry_date desc, certificates.id desc",
            [$clientId]
        );
        $evidence = $db->select(
            "select audit_evidence_files.id, audit_evidence_files.original_filename, audit_evidence_files.mime_type, audit_evidence_files.size_bytes, audit_evidence_files.uploaded_at,
             audits.id as audit_id, audits.audit_number, audit_criteria.section, audit_criteria.title as criterion
             from audit_evidence_files
             join audit_responses on audit_responses.id = audit_evidence_files.audit_response_id
             join audits on audits.id = audit_responses.audit_id
             join audit_criteria on audit_criteria.id = audit_responses.audit_criterion_id
             where audits.client_id = ?
             order by audit_evidence_files.uploaded_at desc
             limit 200",
            [$clientId]
        );
        $payments = $db->select('select * from payments where client_id = ? order by created_at desc limit 200', [$clientId]);
        $emailLogs = $db->select(
            "select distinct email_logs.*
             from email_logs
             left join contacts on contacts.email = email_logs.to_email and contacts.client_id = ?
             where (email_logs.related_type = 'client' and email_logs.related_id = ?)
                or contacts.id is not null
                or email_logs.to_email = ?
             order by email_logs.created_at desc
             limit 200",
            [$clientId, $clientId, $client['billing_email'] ?? '']
        );
        $activities = $db->select(
            "select activity_logs.*, users.name as actor
             from activity_logs
             left join users on users.id = activity_logs.actor_user_id
             where activity_logs.client_id = ?
                or activity_logs.audit_id in (select id from audits where client_id = ?)
                or activity_logs.certificate_id in (select id from certificates where client_id = ?)
             order by activity_logs.created_at desc
             limit 50",
            [$clientId, $clientId, $clientId]
        );

        return [
            'client' => $client,
            'contacts' => $contacts,
            'audits' => $audits,
            'certificates' => $certificates,
            'evidence' => $evidence,
            'payments' => $payments,
            'emailLogs' => $emailLogs,
            'activities' => $activities,
            'summary' => [
                'contacts' => count($contacts),
                'active_audits' => count(array_filter($audits, static fn (array $audit): bool => !in_array($audit['status'], ['passed', 'failed', 'cancelled'], true))),
                'current_certificates' => count(array_filter($certificates, static fn (array $certificate): bool => $certificate['computed_status'] === 'current' && $certificate['status'] === 'issued')),
                'expiring_certificates' => count(array_filter($certificates, static fn (array $certificate): bool => $certificate['computed_status'] === 'expiring_soon')),
                'expired_certificates' => count(array_filter($certificates, static fn (array $certificate): bool => $certificate['computed_status'] === 'expired')),
                'outstanding_evidence' => count(array_filter($audits, static fn (array $audit): bool => in_array($audit['status'], ['awaiting_evidence', 'changes_requested'], true))),
            ],
        ];
    }
}
