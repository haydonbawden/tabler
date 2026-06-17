<div class="card">
    <div class="card-body">
        <div class="d-flex flex-wrap gap-2">
            <?php foreach (['clients','contacts','audits','certificates','users','payments','email_logs'] as $table): ?>
                <a class="btn btn-outline-primary" href="/admin/exports?table=<?= e($table) ?>"><?= e(str_replace('_', ' ', $table)) ?> CSV</a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
