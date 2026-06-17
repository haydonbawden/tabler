<?php
$metrics = [
    ['Total clients', 'total_clients', 'ti-building-community', 'bg-primary-lt text-primary', '/admin/clients'],
    ['Active clients', 'active_clients', 'ti-building-check', 'bg-success-lt text-success', '/admin/clients?status=active'],
    ['Current certificates', 'current_certificates', 'ti-certificate', 'bg-success-lt text-success', '/admin/certificates?view=current'],
    ['Expiring in 90 days', 'expiring_90', 'ti-calendar-due', 'bg-warning-lt text-warning', '/admin/certificates?view=expiring_90'],
    ['Expired certificates', 'expired_certificates', 'ti-certificate-off', 'bg-danger-lt text-danger', '/admin/certificates?view=expired'],
    ['Awaiting evidence', 'awaiting_evidence', 'ti-inbox', 'bg-blue-lt text-blue', '/admin/audits?view=awaiting_evidence'],
    ['Submitted for review', 'submitted', 'ti-send', 'bg-indigo-lt text-indigo', '/admin/audits?view=submitted'],
    ['In review', 'in_review', 'ti-file-search', 'bg-purple-lt text-purple', '/admin/audits?view=in_review'],
    ['Passed this month', 'passed_month', 'ti-circle-check', 'bg-success-lt text-success', '/admin/audits?view=passed_month'],
    ['Failed audits', 'failed', 'ti-alert-triangle', 'bg-danger-lt text-danger', '/admin/audits?view=failed'],
    ['Renewal payments', 'payments_month', 'ti-credit-card', 'bg-blue-lt text-blue', '/admin/payments?status=paid'],
    ['Failed emails', 'failed_emails', 'ti-mail-x', 'bg-danger-lt text-danger', '/admin/email-logs?status=failed'],
];
$money = static fn ($cents): string => '$' . number_format(((int) $cents) / 100, 2);
?>
<div class="row row-deck row-cards">
    <?php foreach ($metrics as [$label, $key, $icon, $tone, $href]): ?>
        <div class="col-sm-6 col-lg-3">
            <?php
            $value = $key === 'payments_month' ? $money($stats[$key] ?? 0) : ($stats[$key] ?? 0);
            include app('root') . '/app/Views/components/metric-card.php';
            ?>
        </div>
    <?php endforeach; ?>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Urgent certificate renewals</h3></div>
            <div class="list-group list-group-flush">
                <?php foreach ($queues['renewals'] as $row): ?>
                    <a class="list-group-item list-group-item-action" href="/admin/certificates/<?= e($row['id']) ?>">
                        <div class="d-flex align-items-center">
                            <span class="avatar avatar-sm bg-warning-lt me-2"><i class="ti ti-certificate"></i></span>
                            <div class="flex-fill">
                                <div class="fw-medium"><?= e($row['certificate_number']) ?></div>
                                <div class="text-secondary small"><?= e($row['client']) ?> · expires <?= e($row['expiry_date']) ?></div>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
                <?php if (!$queues['renewals']): ?>
                    <?php $icon = 'ti-certificate'; $title = 'No urgent renewals'; $description = 'No certificates are expired or expiring inside the 90 day window.'; include app('root') . '/app/Views/components/empty-state.php'; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Audit workload</h3></div>
            <div class="list-group list-group-flush">
                <?php foreach ($queues['audits'] as $row): ?>
                    <a class="list-group-item list-group-item-action" href="/admin/audits/<?= e($row['id']) ?>">
                        <div class="d-flex align-items-center">
                            <span class="avatar avatar-sm bg-blue-lt me-2"><i class="ti ti-clipboard-list"></i></span>
                            <div class="flex-fill">
                                <div class="fw-medium"><?= e($row['audit_number'] ?: 'Audit #' . $row['id']) ?></div>
                                <div class="text-secondary small"><?= e($row['client']) ?> - <?= e(str_replace('_', ' ', $row['status'])) ?> - <?= e($row['evidence_progress'] ?? '0%') ?> evidence - due <?= e($row['due_date'] ?? 'not set') ?></div>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
                <?php if (!$queues['audits']): ?>
                    <?php $icon = 'ti-clipboard-check'; $title = 'No active audit workload'; $description = 'Submitted and in-progress audits will appear here.'; include app('root') . '/app/Views/components/empty-state.php'; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Operational exceptions</h3>
                <div class="card-actions"><a href="/admin/activity-logs" class="btn btn-sm">View logs</a></div>
            </div>
            <div class="list-group list-group-flush">
                <?php foreach ($queues['exceptions'] as $row): ?>
                    <a class="list-group-item list-group-item-action" href="<?= e($row['href']) ?>">
                        <div class="d-flex align-items-center">
                            <span class="avatar avatar-sm bg-danger-lt me-2"><i class="ti ti-alert-triangle"></i></span>
                            <div class="flex-fill">
                                <div class="fw-medium"><?= e($row['type']) ?></div>
                                <div class="text-secondary small"><?= e($row['title'] ?: 'No subject') ?> - <?= e($row['created_at']) ?></div>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
                <?php if (!$queues['exceptions']): ?>
                    <?php $icon = 'ti-circle-check'; $title = 'No operational exceptions'; $description = 'Failed email and import events will appear here when attention is required.'; include app('root') . '/app/Views/components/empty-state.php'; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
