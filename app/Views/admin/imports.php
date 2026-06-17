<div class="row row-cards">
    <div class="col-lg-4">
        <form class="card" method="post" action="/admin/imports" enctype="multipart/form-data">
            <div class="card-body">
                <?= csrf_field() ?>
                <div class="mb-3"><label class="form-label">Workbook</label><input class="form-control" type="file" name="import_file" accept=".xlsx,.csv"><div class="form-hint">Leave empty to use reference/castor_data.xlsx.</div></div>
                <label class="form-check"><input class="form-check-input" type="checkbox" name="dry_run" value="1" checked><span class="form-check-label">Dry-run validation</span></label>
            </div>
            <div class="card-footer text-end"><button class="btn btn-primary">Run import</button></div>
        </form>
    </div>
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Exports</h3></div>
            <div class="card-body">
                <div class="btn-list">
                    <?php foreach (['clients','contacts','audits','certificates','payments','email_logs','activity_logs','audit_criteria'] as $table): ?>
                        <a class="btn btn-outline-primary" href="/admin/exports?table=<?= e($table) ?>"><i class="ti ti-download me-1"></i><?= e(ucwords(str_replace('_', ' ', $table))) ?> CSV</a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="card"><div class="card-header"><div class="card-title">Recent runs</div></div>
            <div class="table-responsive"><table class="table card-table"><thead><tr><th>File</th><th>Mode</th><th>Status</th><th>User</th><th>Created</th></tr></thead><tbody><?php foreach ($runs as $run): ?><tr><td><?= e($run['filename']) ?></td><td><?= $run['dry_run'] ? 'Dry-run' : 'Commit' ?></td><td><span class="badge bg-blue-lt"><?= e($run['status']) ?></span></td><td><?= e($run['user_name']) ?></td><td><?= e($run['created_at']) ?></td></tr><?php endforeach; ?><?php if (!$runs): ?><tr><td colspan="5"><?php $icon = 'ti-database-import'; $title = 'No import runs yet'; $description = 'Dry-run validations and committed imports will appear here.'; include app('root') . '/app/Views/components/empty-state.php'; ?></td></tr><?php endif; ?></tbody></table></div>
        </div>
    </div>
</div>
