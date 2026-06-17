<?php $actions = $actions ?? []; ?>
<div class="btn-list">
    <?php foreach ($actions as $action): ?>
        <form method="post" action="<?= e($action['href']) ?>" class="d-inline">
            <?= csrf_field() ?>
            <button class="btn <?= e($action['class'] ?? 'btn-outline-primary') ?>" type="submit">
                <?php if (!empty($action['icon'])): ?><i class="ti <?= e($action['icon']) ?> me-1"></i><?php endif; ?><?= e($action['label']) ?>
            </button>
        </form>
    <?php endforeach; ?>
</div>
