<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use RuntimeException;

final class AuditLifecycleService
{
    private const TERMINAL_STATUSES = ['passed', 'failed', 'cancelled'];

    private const TRANSITIONS = [
        'draft' => ['awaiting_evidence', 'cancelled'],
        'awaiting_evidence' => ['submitted', 'cancelled'],
        'changes_requested' => ['submitted', 'cancelled'],
        'submitted' => ['in_review', 'changes_requested', 'passed', 'failed', 'cancelled'],
        'in_review' => ['changes_requested', 'passed', 'failed', 'cancelled'],
        'passed' => [],
        'failed' => [],
        'cancelled' => [],
    ];

    private const ROLE_TRANSITIONS = [
        'super_admin' => ['awaiting_evidence', 'submitted', 'in_review', 'changes_requested', 'passed', 'failed', 'cancelled'],
        'admin' => ['awaiting_evidence', 'submitted', 'in_review', 'changes_requested', 'passed', 'failed', 'cancelled'],
        'auditor' => ['in_review', 'changes_requested', 'passed', 'failed'],
        'client' => ['submitted'],
        'viewer' => [],
    ];

    public function transition(int $auditId, string $toStatus, string $role, ?int $actorId, ?string $comment = null, bool $confirmed = false, bool $adminOverride = false): array
    {
        $audit = $this->audit($auditId);
        if (!$audit) {
            throw new RuntimeException('Audit not found.');
        }

        $fromStatus = (string) $audit['status'];
        $this->assertAllowedTransition($fromStatus, $toStatus);
        $this->assertRoleAllowed($audit, $toStatus, $role, $actorId);
        $this->assertRequiredData($audit, $toStatus, $role, trim((string) $comment), $confirmed, $adminOverride);

        $db = App::instance()->database;
        $result = match ($toStatus) {
            'passed' => 'pass',
            'failed' => 'fail',
            default => null,
        };
        $reviewedAtSql = in_array($toStatus, ['in_review', 'changes_requested', 'passed', 'failed'], true) ? 'now()' : 'reviewed_at';
        $completedAtSql = in_array($toStatus, self::TERMINAL_STATUSES, true) ? 'now()' : 'completed_at';
        $submittedAtSql = $toStatus === 'submitted' ? 'coalesce(submitted_at, now())' : 'submitted_at';
        $auditorCommentSql = in_array($toStatus, ['changes_requested', 'passed', 'failed'], true) ? ', overall_auditor_comment = ?' : '';
        $bindings = [$toStatus, $result];
        if ($auditorCommentSql !== '') {
            $bindings[] = trim((string) $comment);
        }
        $bindings[] = $auditId;

        $db->statement(
            "update audits
             set status = ?,
                 result = ?,
                 submitted_at = {$submittedAtSql},
                 reviewed_at = {$reviewedAtSql},
                 completed_at = {$completedAtSql}
                 {$auditorCommentSql},
                 updated_at = now()
             where id = ?",
            $bindings
        );

        if ($toStatus === 'submitted') {
            $db->statement(
                "update audit_responses
                 set client_status = 'submitted',
                     submitted_at = coalesce(submitted_at, now()),
                     updated_at = now()
                 where audit_id = ?",
                [$auditId]
            );
        }

        (new ActivityLogger())->log(
            'audit_transitioned',
            'Audit moved from ' . str_replace('_', ' ', $fromStatus) . ' to ' . str_replace('_', ' ', $toStatus) . '.',
            [
                'audit_id' => $auditId,
                'client_id' => (int) $audit['client_id'],
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
            ]
        );

        return $this->audit($auditId) ?? $audit;
    }

    public function actions(array $audit, string $role, ?int $actorId): array
    {
        $status = (string) ($audit['status'] ?? '');
        $actions = [];
        foreach (self::TRANSITIONS[$status] ?? [] as $target) {
            if (!$this->roleCanAttempt($audit, $target, $role, $actorId)) {
                continue;
            }
            $actions[] = $this->actionDefinition($target);
        }

        if ($status === 'passed' && in_array($role, ['super_admin', 'admin', 'auditor'], true)) {
            $actions[] = [
                'target' => 'generate_certificate',
                'label' => 'Generate certificate',
                'tone' => 'primary',
                'icon' => 'ti-certificate',
                'requiresComment' => false,
                'requiresConfirmation' => false,
            ];
        }

        return $actions;
    }

    public function statuses(): array
    {
        return array_keys(self::TRANSITIONS);
    }

    private function audit(int $auditId): ?array
    {
        return App::instance()->database->first('select * from audits where id = ?', [$auditId]);
    }

    private function assertAllowedTransition(string $fromStatus, string $toStatus): void
    {
        if (!in_array($toStatus, self::TRANSITIONS[$fromStatus] ?? [], true)) {
            throw new RuntimeException('This status transition is not allowed.');
        }
    }

    private function assertRoleAllowed(array $audit, string $toStatus, string $role, ?int $actorId): void
    {
        if (!$this->roleCanAttempt($audit, $toStatus, $role, $actorId)) {
            throw new RuntimeException('You do not have permission to perform this transition.');
        }
    }

    private function roleCanAttempt(array $audit, string $toStatus, string $role, ?int $actorId): bool
    {
        if (!in_array($toStatus, self::ROLE_TRANSITIONS[$role] ?? [], true)) {
            return false;
        }

        if ($role === 'auditor' && (int) ($audit['auditor_id'] ?? 0) !== (int) $actorId) {
            return false;
        }

        return true;
    }

    private function assertRequiredData(array $audit, string $toStatus, string $role, string $comment, bool $confirmed, bool $adminOverride): void
    {
        $db = App::instance()->database;
        $responseCount = (int) $db->scalar('select count(*) from audit_responses where audit_id = ?', [$audit['id']]);
        $progress = new EvidenceProgressService();
        $canOverride = in_array($role, ['super_admin', 'admin'], true) && $adminOverride;

        if ($toStatus === 'awaiting_evidence' && $responseCount === 0) {
            throw new RuntimeException('Add audit criteria before requesting evidence.');
        }

        if ($toStatus === 'submitted') {
            $progress->assertClientSubmissionReady((int) $audit['id']);
        }

        if ($toStatus === 'changes_requested') {
            $progress->assertRequestChangesReady((int) $audit['id'], $canOverride);
        }

        if ($toStatus === 'passed') {
            $progress->assertPassReady((int) $audit['id'], $canOverride);
        }

        if (in_array($toStatus, ['changes_requested', 'passed', 'failed'], true)) {
            if (!$confirmed) {
                throw new RuntimeException('Confirm this lifecycle decision before continuing.');
            }
            if ($comment === '') {
                throw new RuntimeException('Add an auditor comment before completing this lifecycle decision.');
            }
        }
    }

    private function actionDefinition(string $target): array
    {
        return match ($target) {
            'awaiting_evidence' => ['target' => $target, 'label' => 'Request evidence', 'tone' => 'primary', 'icon' => 'ti-send', 'requiresComment' => false, 'requiresConfirmation' => false],
            'submitted' => ['target' => $target, 'label' => 'Submit audit evidence', 'tone' => 'success', 'icon' => 'ti-send', 'requiresComment' => false, 'requiresConfirmation' => false],
            'in_review' => ['target' => $target, 'label' => 'Start review', 'tone' => 'primary', 'icon' => 'ti-file-search', 'requiresComment' => false, 'requiresConfirmation' => false],
            'changes_requested' => ['target' => $target, 'label' => 'Request changes', 'tone' => 'warning', 'icon' => 'ti-message-report', 'requiresComment' => true, 'requiresConfirmation' => true],
            'passed' => ['target' => $target, 'label' => 'Pass audit', 'tone' => 'success', 'icon' => 'ti-circle-check', 'requiresComment' => true, 'requiresConfirmation' => true],
            'failed' => ['target' => $target, 'label' => 'Fail audit', 'tone' => 'danger', 'icon' => 'ti-alert-triangle', 'requiresComment' => true, 'requiresConfirmation' => true],
            'cancelled' => ['target' => $target, 'label' => 'Cancel audit', 'tone' => 'danger', 'icon' => 'ti-ban', 'requiresComment' => false, 'requiresConfirmation' => true],
            default => ['target' => $target, 'label' => ucfirst(str_replace('_', ' ', $target)), 'tone' => 'secondary', 'icon' => 'ti-arrow-right', 'requiresComment' => false, 'requiresConfirmation' => false],
        };
    }
}
