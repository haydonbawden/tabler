<?php
$icon = $icon ?? 'ti-database-off';
$title = $title ?? 'No records found';
$description = $description ?? 'There is nothing to show here yet.';
$primaryAction = $primaryAction ?? null;
$secondaryAction = $secondaryAction ?? null;
?>
<div class="empty">
    <div class="empty-icon"><i class="ti <?= e($icon) ?>"></i></div>
    <p class="empty-title"><?= e($title) ?></p>
    <p class="empty-subtitle text-secondary"><?= e($description) ?></p>
    <?php if ($primaryAction || $secondaryAction): ?>
        <div class="empty-action btn-list justify-content-center">
            <?php if ($primaryAction): ?><a href="<?= e($primaryAction['href']) ?>" class="btn btn-primary"><?= e($primaryAction['label']) ?></a><?php endif; ?>
            <?php if ($secondaryAction): ?><a href="<?= e($secondaryAction['href']) ?>" class="btn btn-outline-secondary"><?= e($secondaryAction['label']) ?></a><?php endif; ?>
        </div>
    <?php endif; ?>
</div>
