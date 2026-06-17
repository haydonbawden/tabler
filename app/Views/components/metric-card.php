<?php
$label = $label ?? '';
$value = $value ?? 0;
$href = $href ?? '#';
$icon = $icon ?? 'ti-chart-bar';
$tone = $tone ?? 'bg-primary-lt text-primary';
$hint = $hint ?? '';
?>
<a class="card card-link" href="<?= e($href) ?>">
    <div class="card-body">
        <div class="d-flex align-items-center">
            <span class="avatar <?= e($tone) ?> me-3"><i class="ti <?= e($icon) ?>"></i></span>
            <div>
                <div class="subheader"><?= e($label) ?></div>
                <div class="h2 mb-0"><?= e(is_numeric($value) ? number_format((float) $value) : (string) $value) ?></div>
                <?php if ($hint): ?><div class="text-secondary small mt-1"><?= e($hint) ?></div><?php endif; ?>
            </div>
        </div>
    </div>
</a>
