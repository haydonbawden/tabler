<?php
$metrics = [
    ['Current certificates', 'current', 'ti-certificate', 'bg-success-lt text-success', '/client/certificates?view=current'],
    ['Expiring certificates', 'expiring', 'ti-calendar-due', 'bg-warning-lt text-warning', '/client/certificates?view=expiring_90'],
    ['Expired certificates', 'expired', 'ti-certificate-off', 'bg-danger-lt text-danger', '/client/certificates?view=expired'],
    ['Active audits', 'active_audits', 'ti-clipboard-list', 'bg-blue-lt text-blue', '/client/audits?view=active'],
    ['Evidence outstanding', 'evidence_outstanding', 'ti-inbox', 'bg-warning-lt text-warning', '/client/audits?view=evidence_outstanding'],
    ['Submitted audits', 'submitted', 'ti-send', 'bg-indigo-lt text-indigo', '/client/audits?view=submitted'],
    ['Completed audits', 'completed', 'ti-circle-check', 'bg-success-lt text-success', '/client/audits?view=completed'],
];
?>
<div class="row row-deck row-cards">
    <?php foreach ($metrics as [$label, $key, $icon, $tone, $href]): ?>
        <div class="col-sm-6 col-lg-3"><?php $value = $stats[$key] ?? 0; include app('root') . '/app/Views/components/metric-card.php'; ?></div>
    <?php endforeach; ?>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Evidence required</h3></div>
            <div class="list-group list-group-flush">
                <?php foreach ($actions as $row): ?>
                    <a class="list-group-item list-group-item-action" href="/client/audits/<?= e($row['id']) ?>">
                        <div class="fw-medium"><?= e($row['audit_number'] ?: 'Audit #' . $row['id']) ?></div>
                        <div class="text-secondary small"><?= e($row['client']) ?> - <?= e(str_replace('_', ' ', $row['status'])) ?> - <?= e($row['evidence_progress'] ?? '0%') ?> evidence - due <?= e($row['due_date'] ?? 'not set') ?></div>
                    </a>
                <?php endforeach; ?>
                <?php if (!$actions): ?>
                    <?php $icon = 'ti-circle-check'; $title = 'No evidence currently required'; $description = 'When audit evidence is needed, it will appear here with a direct action link.'; include app('root') . '/app/Views/components/empty-state.php'; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Certificates and renewals</h3></div>
            <div class="list-group list-group-flush">
                <?php foreach ($certificates as $row): ?>
                    <a class="list-group-item list-group-item-action" href="/client/certificates/<?= e($row['id']) ?>">
                        <div class="fw-medium"><?= e($row['certificate_number']) ?></div>
                        <div class="text-secondary small"><?= e($row['client']) ?> · expires <?= e($row['expiry_date']) ?> · <?= $row['pdf_path'] ? 'ready to download' : 'PDF pending' ?></div>
                    </a>
                <?php endforeach; ?>
                <?php if (!$certificates): ?>
                    <?php $icon = 'ti-certificate'; $title = 'No issued certificates yet'; $description = 'Issued certificates will be available here for download.'; include app('root') . '/app/Views/components/empty-state.php'; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
