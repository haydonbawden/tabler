<?php $items = $items ?? []; ?>
<div class="card">
    <?php if (!empty($title)): ?><div class="card-header"><h3 class="card-title"><?= e($title) ?></h3></div><?php endif; ?>
    <div class="card-body">
        <div class="datagrid">
            <?php foreach ($items as $item): ?>
                <div class="datagrid-item">
                    <div class="datagrid-title"><?= e($item['label']) ?></div>
                    <div class="datagrid-content"><?= e((string) ($item['value'] ?? '')) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
