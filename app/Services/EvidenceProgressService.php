<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use RuntimeException;

final class EvidenceProgressService
{
    public function responses(int $auditId): array
    {
        $db = App::instance()->database;
        $responses = $db->select(
            'select audit_responses.*, audit_criteria.section, audit_criteria.reference, audit_criteria.title,
                    audit_criteria.requirement_text, audit_criteria.guidance_text, audit_criteria.evidence_prompt,
                    audit_criteria.is_required, audit_criteria.sort_order
             from audit_responses
             join audit_criteria on audit_criteria.id = audit_responses.audit_criterion_id
             where audit_responses.audit_id = ?
             order by audit_criteria.sort_order, audit_criteria.id',
            [$auditId]
        );

        foreach ($responses as &$response) {
            $response['files'] = $db->select(
                'select id, uploaded_by, original_filename, mime_type, size_bytes, uploaded_at
                 from audit_evidence_files
                 where audit_response_id = ?
                 order by uploaded_at desc',
                [$response['id']]
            );
        }

        return $responses;
    }

    public function groupedResponses(int $auditId): array
    {
        $groups = [];
        foreach ($this->responses($auditId) as $response) {
            $section = trim((string) ($response['section'] ?? ''));
            $section = $section !== '' ? $section : 'General';
            $groups[$section][] = $response;
        }
        return $groups;
    }

    public function progress(int $auditId): array
    {
        return $this->progressFromResponses($this->responses($auditId));
    }

    public function progressFromResponses(array $responses): array
    {
        $total = count($responses);
        $required = count(array_filter($responses, static fn (array $response): bool => (int) ($response['is_required'] ?? 0) === 1));
        $submitted = count(array_filter($responses, static fn (array $response): bool => ($response['client_status'] ?? '') === 'submitted'));
        $requiredSubmitted = count(array_filter($responses, static fn (array $response): bool => (int) ($response['is_required'] ?? 0) === 1 && ($response['client_status'] ?? '') === 'submitted'));
        $reviewed = count(array_filter($responses, static fn (array $response): bool => !empty($response['auditor_result'])));
        $acceptableRequired = count(array_filter($responses, static fn (array $response): bool => (int) ($response['is_required'] ?? 0) === 1 && ($response['auditor_result'] ?? '') === 'acceptable'));
        $files = array_sum(array_map(static fn (array $response): int => count($response['files'] ?? []), $responses));

        return [
            'total' => $total,
            'required' => $required,
            'submitted' => $submitted,
            'required_submitted' => $requiredSubmitted,
            'reviewed' => $reviewed,
            'acceptable_required' => $acceptableRequired,
            'files' => $files,
            'submitted_percent' => $total > 0 ? (int) round(($submitted / $total) * 100) : 0,
            'required_submitted_percent' => $required > 0 ? (int) round(($requiredSubmitted / $required) * 100) : 0,
            'reviewed_percent' => $total > 0 ? (int) round(($reviewed / $total) * 100) : 0,
        ];
    }

    public function assertClientSubmissionReady(int $auditId): void
    {
        $missing = [];
        foreach ($this->responses($auditId) as $response) {
            if ((int) ($response['is_required'] ?? 0) !== 1) {
                continue;
            }

            $responseText = trim((string) ($response['client_response'] ?? ''));
            if (($response['client_status'] ?? '') !== 'submitted' || $responseText === '') {
                $missing[] = (string) $response['title'];
            }
        }

        if ($missing) {
            throw new RuntimeException('Complete required criteria before submitting: ' . implode(', ', array_slice($missing, 0, 5)) . (count($missing) > 5 ? ', ...' : ''));
        }
    }

    public function assertRequestChangesReady(int $auditId, bool $adminOverride = false): void
    {
        if ($adminOverride) {
            return;
        }

        $flagged = false;
        foreach ($this->responses($auditId) as $response) {
            if ((int) ($response['is_required'] ?? 0) !== 1) {
                continue;
            }
            if (in_array($response['auditor_result'] ?? '', ['not_acceptable', 'needs_more_information'], true)) {
                $flagged = true;
                break;
            }
        }

        if (!$flagged) {
            throw new RuntimeException('Mark at least one required criterion as not acceptable or needs more information before requesting changes.');
        }
    }

    public function assertPassReady(int $auditId, bool $adminOverride = false): void
    {
        if ($adminOverride) {
            return;
        }

        $notAccepted = [];
        foreach ($this->responses($auditId) as $response) {
            if ((int) ($response['is_required'] ?? 0) !== 1) {
                continue;
            }
            if (($response['auditor_result'] ?? '') !== 'acceptable') {
                $notAccepted[] = (string) $response['title'];
            }
        }

        if ($notAccepted) {
            throw new RuntimeException('All required criteria must be acceptable before passing the audit: ' . implode(', ', array_slice($notAccepted, 0, 5)) . (count($notAccepted) > 5 ? ', ...' : ''));
        }
    }

    public function listProgressSql(): string
    {
        return "concat(coalesce(round(100 * sum(case when audit_responses.client_status = 'submitted' then 1 else 0 end) / nullif(count(audit_responses.id), 0)), 0), '%')";
    }
}
