<?php $activities = $activities ?? []; ?>
<div class="timeline">
    <?php foreach ($activities as $activity): ?>
        <div class="timeline-event">
            <div class="timeline-event-icon bg-primary-lt"><i class="ti ti-history"></i></div>
            <div class="card timeline-event-card">
                <div class="card-body">
                    <div class="text-secondary small"><?= e($activity['created_at'] ?? '') ?></div>
                    <div class="fw-medium"><?= e($activity['actor'] ?? 'System') ?> <?= e(str_replace('_', ' ', $activity['action'] ?? 'updated')) ?></div>
                    <?php if (!empty($activity['description'])): ?><div class="text-secondary"><?= e($activity['description']) ?></div><?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if (!$activities): ?>
        <?php $title = 'No activity yet'; $description = 'Activity will appear here as records change.'; include app('root') . '/app/Views/components/empty-state.php'; ?>
    <?php endif; ?>
</div>
