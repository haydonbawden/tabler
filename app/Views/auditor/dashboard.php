<?php
$metrics = [
    ['Assigned audits', 'assigned', 'ti-clipboard-list', 'bg-primary-lt text-primary', '/auditor/audits'],
    ['Awaiting client evidence', 'awaiting_client', 'ti-inbox', 'bg-blue-lt text-blue', '/auditor/audits?view=awaiting_evidence'],
    ['Evidence submitted', 'submitted', 'ti-send', 'bg-indigo-lt text-indigo', '/auditor/audits?view=submitted'],
    ['Requires review', 'in_review', 'ti-file-search', 'bg-purple-lt text-purple', '/auditor/audits?view=in_review'],
    ['Changes requested', 'changes_requested', 'ti-message-report', 'bg-warning-lt text-warning', '/auditor/audits?view=changes_requested'],
    ['Completed this month', 'completed_month', 'ti-circle-check', 'bg-success-lt text-success', '/auditor/audits?view=completed'],
];
?>
<div class="row row-deck row-cards">
    <?php foreach ($metrics as [$label, $key, $icon, $tone, $href]): ?>
        <div class="col-sm-6 col-lg-4"><?php $value = $stats[$key] ?? 0; include app('root') . '/app/Views/components/metric-card.php'; ?></div>
    <?php endforeach; ?>
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">My review queue</h3>
                <div class="card-actions"><a href="/auditor/audits" class="btn btn-primary btn-sm"><i class="ti ti-file-search me-1"></i>Open all audits</a></div>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead><tr><th>Audit</th><th>Client</th><th>Certification</th><th>Status</th><th>Submitted</th><th>Due</th><th>Evidence</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($queue as $row): ?>
                        <tr class="table-row-link" data-href="/auditor/audits/<?= e($row['id']) ?>">
                            <td><?= e($row['audit_number'] ?: 'Audit #' . $row['id']) ?></td>
                            <td><?= e($row['client']) ?></td>
                            <td><?= e($row['certification']) ?></td>
                            <td><?php $value = $row['status']; include app('root') . '/app/Views/components/status-badge.php'; ?></td>
                            <td><?= e($row['submitted_at'] ?? 'Not submitted') ?></td>
                            <td><?= e($row['due_date'] ?? 'Not set') ?></td>
                            <td><?= e($row['evidence_progress'] ?? '0%') ?></td>
                            <td><a class="btn btn-sm btn-primary" href="/auditor/audits/<?= e($row['id']) ?>">Review evidence</a></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$queue): ?><tr><td colspan="8"><?php $icon = 'ti-clipboard-check'; $title = 'No active review queue'; $description = 'Assigned audits that need evidence review will appear here.'; include app('root') . '/app/Views/components/empty-state.php'; ?></td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
