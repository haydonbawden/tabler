<?php
$eyebrow = $eyebrow ?? '';
$title = $title ?? '';
$subtitle = $subtitle ?? '';
$breadcrumbs = $breadcrumbs ?? [];
$primaryAction = $primaryAction ?? null;
$secondaryActions = $secondaryActions ?? [];
$status = $status ?? null;
?>
<div class="page-header d-print-none castor-page-header">
    <div class="container-xl">
        <?php if ($breadcrumbs): ?>
            <ol class="breadcrumb breadcrumb-arrows mb-2">
                <?php foreach ($breadcrumbs as $crumb): ?>
                    <li class="breadcrumb-item<?= empty($crumb['href']) ? ' active' : '' ?>">
                        <?php if (!empty($crumb['href'])): ?><a href="<?= e($crumb['href']) ?>"><?= e($crumb['label']) ?></a><?php else: ?><?= e($crumb['label']) ?><?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
        <div class="row g-2 align-items-center">
            <div class="col">
                <?php if ($eyebrow): ?><div class="page-pretitle"><?= e($eyebrow) ?></div><?php endif; ?>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <h2 class="page-title mb-0"><?= e($title) ?></h2>
                    <?php if ($status): ?>
                        <?php $value = is_array($status) ? ($status['label'] ?? '') : (string) $status; $tone = is_array($status) ? ($status['class'] ?? 'bg-secondary-lt') : 'bg-secondary-lt'; ?>
                        <span class="badge <?= e($tone) ?> status-badge"><?= e($value) ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($subtitle): ?><div class="text-secondary mt-1"><?= e($subtitle) ?></div><?php endif; ?>
            </div>
            <?php if ($primaryAction || $secondaryActions): ?>
                <div class="col-auto ms-auto">
                    <div class="btn-list">
                        <?php foreach ($secondaryActions as $action): ?>
                            <a class="btn <?= e($action['class'] ?? 'btn-outline-secondary') ?>" href="<?= e($action['href'] ?? '#') ?>">
                                <?php if (!empty($action['icon'])): ?><i class="ti <?= e($action['icon']) ?> me-1"></i><?php endif; ?><?= e($action['label']) ?>
                            </a>
                        <?php endforeach; ?>
                        <?php if ($primaryAction): ?>
                            <a class="btn <?= e($primaryAction['class'] ?? 'btn-primary') ?>" href="<?= e($primaryAction['href'] ?? '#') ?>">
                                <?php if (!empty($primaryAction['icon'])): ?><i class="ti <?= e($primaryAction['icon']) ?> me-1"></i><?php endif; ?><?= e($primaryAction['label']) ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
