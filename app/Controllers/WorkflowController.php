<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Access;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Services\ActivityLogger;
use App\Services\AuditLifecycleService;
use App\Services\CertificateService;
use App\Services\EvidenceProgressService;

final class WorkflowController extends Controller
{
    public function auditorDashboard(): void
    {
        redirect('/auditor/audits');
    }

    public function auditorAudits(): void
    {
        $rows = $this->db()->select(
            "select audits.id, audits.audit_number, clients.legal_name as client, certification_types.name as certification, audits.status,
                    concat(coalesce(round(100 * sum(case when audit_responses.client_status = 'submitted' then 1 else 0 end) / nullif(count(audit_responses.id), 0)), 0), '%') as evidence_progress,
                    audits.due_date
             from audits join clients on clients.id = audits.client_id join certification_types on certification_types.id = audits.certification_type_id
             left join audit_responses on audit_responses.audit_id = audits.id
             where audits.auditor_id = ?" . $this->auditViewSql((string) ($_GET['view'] ?? '')) . ' group by audits.id, audits.audit_number, clients.legal_name, certification_types.name, audits.status, audits.due_date order by max(audits.updated_at) desc',
            [Auth::id()]
        );
        $this->view('admin/simple-table', [
            'title' => 'Current audits',
            'rows' => $rows,
            'detailPrefix' => '/auditor/audits/',
        ] + $this->pageHeader('AUDITOR PORTAL', 'Current audits', 'Open assigned audits and continue assessment work.'));
    }

    public function auditReview(string $id): void
    {
        $this->guardAudit((int) $id);
        $audit = $this->auditRecord((int) $id);
        $evidence = new EvidenceProgressService();
        $responses = $evidence->responses((int) $id);
        $title = $audit['audit_number'] ?? 'Audit review';
        $this->view('auditor/audit-review', [
            'title' => $title,
            'audit' => $audit,
            'responses' => $responses,
            'responseGroups' => $evidence->groupedResponses((int) $id),
            'progress' => $evidence->progressFromResponses($responses),
            'lifecycleActions' => $audit ? (new AuditLifecycleService())->actions($audit, $this->role(), Auth::id()) : [],
        ] + $this->pageHeader('AUDIT REVIEW', $title, 'Review submitted evidence, record comments and decide the audit outcome.', [
            'status' => $audit ? ['label' => (string) $audit['status'], 'class' => 'bg-blue-lt'] : null,
        ]));
    }

    public function saveAuditorComment(string $id, string $responseId): void
    {
        $this->guardAudit((int) $id);
        $this->db()->statement(
            'update audit_responses set auditor_comment = ?, auditor_result = ?, reviewed_by = ?, reviewed_at = now(), updated_at = now() where id = ? and audit_id = ?',
            [$this->input('auditor_comment'), $this->input('auditor_result') ?: null, Auth::id(), $responseId, $id]
        );
        (new ActivityLogger())->log('auditor_comment_saved', 'Auditor comment saved.', ['audit_id' => (int) $id]);
        Session::flash('success', 'Comment saved.');
        redirect('/auditor/audits/' . $id);
    }

    public function requestChanges(string $id): void
    {
        $this->performTransition((int) $id, 'changes_requested', '/auditor/audits/' . $id);
    }

    public function passAudit(string $id): void
    {
        $this->performTransition((int) $id, 'passed', '/auditor/audits/' . $id);
    }

    public function failAudit(string $id): void
    {
        $this->performTransition((int) $id, 'failed', '/auditor/audits/' . $id);
    }

    public function transitionAudit(string $id): void
    {
        $this->performTransition((int) $id, (string) $this->input('target_status'), $this->returnTo('/admin/audits/' . $id));
    }

    public function generateCertificateForAudit(string $id): void
    {
        $this->guardAudit((int) $id);
        $audit = $this->db()->first('select * from audits where id = ?', [$id]);
        if (!$audit || $audit['status'] !== 'passed') {
            Session::flash('error', 'Only passed audits can generate certificates.');
            redirect($this->returnTo('/auditor/audits/' . $id));
        }

        $certificateId = $this->ensureCertificate($audit);
        $this->generatePdf($certificateId);
        redirect($this->returnTo('/auditor/audits/' . $id));
    }

    public function generateCertificate(string $id): void
    {
        $this->generatePdf((int) $id);
        redirect('/admin/certificates/' . $id);
    }

    public function clientDashboard(): void
    {
        redirect('/client/profile');
    }

    public function clientProfile(): void
    {
        $clients = $this->clientRows();
        $this->view('client/profile', [
            'title' => 'My organisation',
            'clients' => $clients,
        ] + $this->pageHeader('CLIENT PORTAL', 'My organisation', 'Review registered organisation details and billing contacts.'));
    }

    public function clientContacts(): void
    {
        $ids = $this->clientIds();
        $rows = $ids ? $this->db()->select('select contacts.* from contacts where client_id in (' . $this->placeholders($ids) . ') order by display_name', $ids) : [];
        $this->view('client/contacts', [
            'title' => 'Contacts',
            'contacts' => $rows,
            'clients' => $this->clientRows(),
        ] + $this->pageHeader('CLIENT PORTAL', 'Contacts', 'Maintain contacts who receive reminders, evidence requests and certificates.'));
    }

    public function addClientContact(): void
    {
        $clientId = (int) $this->input('client_id');
        if (!Access::canAccessClient($clientId)) {
            http_response_code(403);
            exit('Forbidden');
        }
        $this->db()->statement(
            'insert into contacts (client_id, display_name, email, phone, is_active, created_at, updated_at) values (?, ?, ?, ?, 1, now(), now())',
            [$clientId, trim((string) $this->input('display_name')), strtolower(trim((string) $this->input('email'))), trim((string) $this->input('phone')) ?: null]
        );
        (new ActivityLogger())->log('client_contact_created', 'Client contact created.', ['client_id' => $clientId]);
        redirect('/client/contacts');
    }

    public function updateClientContact(string $id): void
    {
        $contact = $this->db()->first('select * from contacts where id = ?', [$id]);
        if (!$contact || !Access::canAccessClient((int) $contact['client_id'])) {
            http_response_code(403);
            exit('Forbidden');
        }
        $this->db()->statement('update contacts set display_name = ?, email = ?, phone = ?, updated_at = now() where id = ?', [trim((string) $this->input('display_name')), strtolower(trim((string) $this->input('email'))), trim((string) $this->input('phone')) ?: null, $id]);
        (new ActivityLogger())->log('client_contact_updated', 'Client contact updated.', ['client_id' => (int) $contact['client_id']]);
        redirect('/client/contacts');
    }

    public function clientAudits(): void
    {
        $ids = $this->clientIds();
        $rows = $ids ? $this->db()->select(
            "select audits.id, audits.audit_number, clients.legal_name as client, certification_types.name as certification, audits.status,
                    concat(coalesce(round(100 * sum(case when audit_responses.client_status = 'submitted' then 1 else 0 end) / nullif(count(audit_responses.id), 0)), 0), '%') as evidence_progress,
                    audits.due_date
             from audits join clients on clients.id = audits.client_id join certification_types on certification_types.id = audits.certification_type_id
             left join audit_responses on audit_responses.audit_id = audits.id
             where audits.client_id in (" . $this->placeholders($ids) . ')' . $this->auditViewSql((string) ($_GET['view'] ?? '')) . ' group by audits.id, audits.audit_number, clients.legal_name, certification_types.name, audits.status, audits.due_date order by max(audits.updated_at) desc',
            $ids
        ) : [];
        $this->view('admin/simple-table', [
            'title' => 'Audits',
            'rows' => $rows,
            'detailPrefix' => '/client/audits/',
        ] + $this->pageHeader('CLIENT PORTAL', 'Audits', 'Track evidence requests, submissions and audit outcomes.'));
    }

    public function clientAudit(string $id): void
    {
        $this->guardAudit((int) $id);
        $audit = $this->auditRecord((int) $id);
        $evidence = new EvidenceProgressService();
        $responses = $evidence->responses((int) $id);
        $title = $audit['audit_number'] ?? 'Audit';
        $this->view('client/audit', [
            'title' => $title,
            'audit' => $audit,
            'responses' => $responses,
            'responseGroups' => $evidence->groupedResponses((int) $id),
            'progress' => $evidence->progressFromResponses($responses),
            'lifecycleActions' => $audit ? (new AuditLifecycleService())->actions($audit, $this->role(), Auth::id()) : [],
        ] + $this->pageHeader('CLIENT AUDIT', $title, 'Submit evidence and respond to assessment criteria.', [
            'status' => $audit ? ['label' => (string) $audit['status'], 'class' => 'bg-blue-lt'] : null,
        ]));
    }

    public function saveClientResponse(string $id, string $responseId): void
    {
        $this->guardAudit((int) $id);
        $audit = $this->db()->first('select status from audits where id = ?', [$id]);
        if (!$audit || !in_array($audit['status'], ['awaiting_evidence', 'changes_requested'], true)) {
            Session::flash('error', 'Responses can only be edited while evidence is requested.');
            redirect('/client/audits/' . $id);
        }
        $response = $this->db()->first('select id from audit_responses where id = ? and audit_id = ?', [$responseId, $id]);
        if (!$response) {
            http_response_code(404);
            exit('Response not found.');
        }
        $this->db()->statement(
            'update audit_responses set client_response = ?, client_status = ?, submitted_by = ?, submitted_at = now(), updated_at = now() where id = ? and audit_id = ?',
            [$this->input('client_response'), $this->input('client_status', 'in_progress'), Auth::id(), $responseId, $id]
        );
        (new ActivityLogger())->log('audit_evidence_response_saved', 'Audit evidence response saved.', ['audit_id' => (int) $id]);
        redirect('/client/audits/' . $id);
    }

    public function uploadEvidence(string $id, string $responseId): void
    {
        $this->guardAudit((int) $id);
        $audit = $this->db()->first('select status from audits where id = ?', [$id]);
        if (!$audit || !in_array($audit['status'], ['awaiting_evidence', 'changes_requested'], true)) {
            Session::flash('error', 'Evidence can only be uploaded while evidence is requested.');
            redirect('/client/audits/' . $id);
        }
        $response = $this->db()->first('select id from audit_responses where id = ? and audit_id = ?', [$responseId, $id]);
        if (!$response) {
            http_response_code(404);
            exit('Response not found.');
        }
        if (empty($_FILES['evidence_file']['tmp_name']) || !is_uploaded_file($_FILES['evidence_file']['tmp_name'])) {
            Session::flash('error', 'Choose a file to upload.');
            redirect('/client/audits/' . $id);
        }

        $allowed = ['application/pdf', 'image/png', 'image/jpeg', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
        $mime = mime_content_type($_FILES['evidence_file']['tmp_name']) ?: $_FILES['evidence_file']['type'];
        if (!in_array($mime, $allowed, true)) {
            Session::flash('error', 'File type is not allowed.');
            redirect('/client/audits/' . $id);
        }

        $dir = app('root') . '/storage/evidence/audit-' . (int) $id;
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $name = bin2hex(random_bytes(12)) . '-' . preg_replace('/[^A-Za-z0-9_.-]/', '-', $_FILES['evidence_file']['name']);
        $path = $dir . '/' . $name;
        move_uploaded_file($_FILES['evidence_file']['tmp_name'], $path);

        $this->db()->statement(
            'insert into audit_evidence_files (audit_response_id, uploaded_by, original_filename, stored_path, mime_type, size_bytes, checksum, created_at, updated_at) values (?, ?, ?, ?, ?, ?, ?, now(), now())',
            [$responseId, Auth::id(), $_FILES['evidence_file']['name'], 'storage/evidence/audit-' . (int) $id . '/' . $name, $mime, (int) $_FILES['evidence_file']['size'], hash_file('sha256', $path)]
        );
        (new ActivityLogger())->log('audit_evidence_uploaded', 'Evidence file uploaded.', ['audit_id' => (int) $id]);
        redirect('/client/audits/' . $id);
    }

    public function deleteEvidence(string $id, string $fileId): void
    {
        $this->guardAudit((int) $id);
        $file = $this->db()->first(
            'select audit_evidence_files.*, audit_responses.audit_id, audits.status
             from audit_evidence_files
             join audit_responses on audit_responses.id = audit_evidence_files.audit_response_id
             join audits on audits.id = audit_responses.audit_id
             where audit_evidence_files.id = ? and audit_responses.audit_id = ?',
            [$fileId, $id]
        );
        if (!$file || (int) $file['uploaded_by'] !== (int) Auth::id()) {
            http_response_code(403);
            exit('Forbidden');
        }
        if (!in_array($file['status'], ['awaiting_evidence', 'changes_requested'], true)) {
            Session::flash('error', 'Evidence files can only be deleted before audit submission.');
            redirect('/client/audits/' . $id);
        }

        $path = app('root') . '/' . $file['stored_path'];
        if (is_file($path)) {
            unlink($path);
        }
        $this->db()->statement('delete from audit_evidence_files where id = ?', [$fileId]);
        (new ActivityLogger())->log('audit_evidence_deleted', 'Evidence file deleted.', ['audit_id' => (int) $id]);
        Session::flash('success', 'Evidence file deleted.');
        redirect('/client/audits/' . $id);
    }

    public function submitAudit(string $id): void
    {
        $this->performTransition((int) $id, 'submitted', '/client/audits/' . $id);
    }

    public function clientCertificates(): void
    {
        $ids = $this->clientIds();
        $rows = $ids ? $this->db()->select(
            'select certificates.id, certificates.certificate_number, clients.legal_name as client, certification_types.name as certification, certificates.issue_date, certificates.expiry_date, certificates.status
             from certificates join clients on clients.id = certificates.client_id join certification_types on certification_types.id = certificates.certification_type_id
             where certificates.client_id in (' . $this->placeholders($ids) . ')' . $this->certificateViewSql((string) ($_GET['view'] ?? '')) . ' order by certificates.expiry_date desc',
            $ids
        ) : [];
        $this->view('admin/simple-table', [
            'title' => 'Certificates',
            'rows' => $rows,
            'detailPrefix' => '/client/certificates/',
        ] + $this->pageHeader('CLIENT PORTAL', 'Certificates', 'Download issued certificates and review renewal dates.'));
    }

    public function downloadCertificate(string $id): void
    {
        $certificate = $this->db()->first('select * from certificates where id = ?', [$id]);
        if (!$certificate || !Access::canAccessClient((int) $certificate['client_id']) || !$certificate['pdf_path']) {
            http_response_code(404);
            exit('Certificate not found.');
        }
        $path = app('root') . '/' . $certificate['pdf_path'];
        if (!is_file($path)) {
            http_response_code(404);
            exit('Certificate file not found.');
        }
        (new ActivityLogger())->log('certificate_downloaded', 'Certificate downloaded.', ['certificate_id' => (int) $id, 'client_id' => (int) $certificate['client_id']]);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($path) . '"');
        readfile($path);
    }

    public function downloadEvidence(string $id): void
    {
        $file = $this->db()->first(
            'select audit_evidence_files.*, audit_responses.audit_id, audits.client_id
             from audit_evidence_files
             join audit_responses on audit_responses.id = audit_evidence_files.audit_response_id
             join audits on audits.id = audit_responses.audit_id
             where audit_evidence_files.id = ?',
            [$id]
        );
        if (!$file || !Access::canAccessAudit((int) $file['audit_id'])) {
            http_response_code(403);
            exit('Forbidden');
        }
        $path = app('root') . '/' . $file['stored_path'];
        if (!is_file($path)) {
            http_response_code(404);
            exit('Evidence file not found.');
        }
        (new ActivityLogger())->log('audit_evidence_downloaded', 'Evidence file downloaded.', ['audit_id' => (int) $file['audit_id'], 'client_id' => (int) $file['client_id']]);
        header('Content-Type: ' . $file['mime_type']);
        header('Content-Disposition: attachment; filename="' . basename($file['original_filename']) . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
    }

    public function clientCertificate(string $id): void
    {
        $certificate = $this->db()->first(
            'select certificates.*, clients.legal_name, clients.abn, clients.registered_office_address, certification_types.name as certification
             from certificates join clients on clients.id = certificates.client_id join certification_types on certification_types.id = certificates.certification_type_id where certificates.id = ?',
            [$id]
        );
        if (!$certificate || !Access::canAccessClient((int) $certificate['client_id'])) {
            http_response_code(403);
            exit('Forbidden');
        }
        $this->view('client/certificate-detail', [
            'title' => $certificate['certificate_number'],
            'certificate' => $certificate,
        ] + $this->pageHeader('CLIENT CERTIFICATE', (string) $certificate['certificate_number'], 'Download the issued certificate and confirm certificate details.', [
            'status' => ['label' => (string) $certificate['status'], 'class' => $certificate['status'] === 'issued' ? 'bg-success-lt' : 'bg-secondary-lt'],
        ]));
    }

    public function renewCertificate(string $id): void
    {
        Session::flash('error', 'Stripe renewal payments are deferred until after the core audit workflow is complete.');
        redirect('/client/certificates');
    }

    private function ensureCertificate(array $audit): int
    {
        $existing = $this->db()->first('select id from certificates where audit_id = ?', [$audit['id']]);
        if ($existing) {
            return (int) $existing['id'];
        }
        $number = 'CAA-' . date('Y') . '-' . str_pad((string) $audit['id'], 5, '0', STR_PAD_LEFT);
        $issue = date('Y-m-d');
        $expiry = date('Y-m-d', strtotime('+12 months'));
        $this->db()->statement(
            'insert into certificates (certificate_uid, audit_id, client_id, certification_type_id, certificate_number, issue_number, status, issue_date, expiry_date, last_day_to_renew, audit_scope, validation_token, generated_by, created_at, updated_at)
             values (?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?, ?, now(), now())',
            [bin2hex(random_bytes(16)), $audit['id'], $audit['client_id'], $audit['certification_type_id'], $number, 'draft', $issue, $expiry, date('Y-m-d', strtotime('-1 day', strtotime($expiry))), $audit['audit_scope'], bin2hex(random_bytes(24)), Auth::id()]
        );
        return (int) $this->db()->pdo()->lastInsertId();
    }

    private function generatePdf(int $certificateId): void
    {
        try {
            (new CertificateService())->generatePdf($certificateId);
            Session::flash('success', 'Certificate PDF generated.');
        } catch (\Throwable $exception) {
            Session::flash('error', $exception->getMessage());
        }
    }

    private function auditRecord(int $id): ?array
    {
        return $this->db()->first(
            'select audits.*, clients.legal_name as client, certification_types.name as certification
             from audits join clients on clients.id = audits.client_id join certification_types on certification_types.id = audits.certification_type_id where audits.id = ?',
            [$id]
        );
    }

    private function responses(int $auditId): array
    {
        return (new EvidenceProgressService())->responses($auditId);
    }

    private function guardAudit(int $id): void
    {
        if (!Access::canAccessAudit($id)) {
            http_response_code(403);
            exit('Forbidden');
        }
    }

    private function performTransition(int $id, string $targetStatus, string $redirectTo): void
    {
        $this->guardAudit($id);
        try {
            (new AuditLifecycleService())->transition(
                $id,
                $targetStatus,
                $this->role(),
                Auth::id(),
                $this->input('overall_auditor_comment'),
                (bool) $this->input('confirm'),
                (bool) $this->input('admin_override')
            );
            Session::flash('success', 'Audit lifecycle updated.');
        } catch (\Throwable $exception) {
            Session::flash('error', $exception->getMessage());
        }
        redirect($redirectTo);
    }

    private function role(): string
    {
        return (string) (Auth::user()['role'] ?? 'viewer');
    }

    private function returnTo(string $fallback): string
    {
        $returnTo = (string) $this->input('return_to', $fallback);
        if ($returnTo === '' || !str_starts_with($returnTo, '/') || str_starts_with($returnTo, '//')) {
            return $fallback;
        }
        return $returnTo;
    }

    private function clientRows(): array
    {
        $ids = $this->clientIds();
        return $ids ? $this->db()->select('select * from clients where id in (' . $this->placeholders($ids) . ') order by legal_name', $ids) : [];
    }

    private function clientIds(): array
    {
        return array_map('intval', array_column($this->db()->select('select client_id from client_user where user_id = ?', [Auth::id()]), 'client_id'));
    }

    private function placeholders(array $items): string
    {
        return implode(',', array_fill(0, count($items), '?'));
    }

    private function auditViewSql(string $view): string
    {
        return match ($view) {
            'draft', 'awaiting_evidence', 'submitted', 'in_review', 'changes_requested', 'passed', 'failed', 'cancelled' => " and audits.status = '" . $view . "'",
            'active' => " and audits.status not in ('passed','failed','cancelled')",
            'evidence_outstanding' => " and audits.status in ('awaiting_evidence','changes_requested')",
            'completed' => " and audits.status in ('passed','failed')",
            default => '',
        };
    }

    private function certificateViewSql(string $view): string
    {
        return match ($view) {
            'current' => " and certificates.status = 'issued' and certificates.expiry_date >= curdate()",
            'expiring_90', 'expiring_soon' => " and certificates.status = 'issued' and certificates.expiry_date between curdate() and date_add(curdate(), interval 90 day)",
            'expired' => ' and certificates.expiry_date < curdate()',
            default => '',
        };
    }
}
