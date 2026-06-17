<?php $views = $views ?? []; $active = $active ?? 'all'; ?>
<ul class="nav nav-tabs mb-3">
    <?php foreach ($views as $key => $label): ?>
        <li class="nav-item"><a class="nav-link<?= $active === $key ? ' active' : '' ?>" href="?view=<?= e($key) ?>"><?= e($label) ?></a></li>
    <?php endforeach; ?>
</ul>
