<div class="detail-shell">
    <div class="row row-cards">
        <div class="col-lg-8">
            <form class="card" method="post" action="/admin/users/<?= e($record['id'] ?? 'new') ?>">
                <div class="card-header"><h3 class="card-title">User account</h3></div>
                <div class="card-body">
                    <?= csrf_field() ?>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Name</label><input class="form-control" name="name" value="<?= e($record['name'] ?? '') ?>" required></div>
                        <div class="col-md-6"><label class="form-label">Email</label><input class="form-control" type="email" name="email" value="<?= e($record['email'] ?? '') ?>" required></div>
                        <div class="col-md-6"><label class="form-label">Role</label><select class="form-select" name="role"><?php foreach (['admin','auditor','client','viewer','super_admin'] as $role): ?><option<?= selected($record['role'] ?? 'client', $role) ?>><?= e($role) ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-6"><label class="form-label">Status</label><select class="form-select" name="status"><?php foreach (['invited','active','disabled'] as $status): ?><option<?= selected($record['status'] ?? 'active', $status) ?>><?= e($status) ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-6"><label class="form-label">Password</label><input class="form-control" type="password" name="password"><div class="form-hint">Required for new users; leave blank to keep existing password.</div></div>
                    </div>
                </div>
                <div class="card-footer text-end"><button class="btn btn-primary" type="submit"><i class="ti ti-device-floppy me-1"></i>Save user</button></div>
            </form>
        </div>
        <div class="col-lg-4">
            <div class="detail-aside">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Access summary</h3></div>
                    <div class="card-body">
                        <div class="datagrid">
                            <div class="datagrid-item"><div class="datagrid-title">Role</div><div class="datagrid-content"><span class="badge bg-blue-lt status-badge"><?= e($record['role'] ?? 'client') ?></span></div></div>
                            <div class="datagrid-item"><div class="datagrid-title">Status</div><div class="datagrid-content"><span class="badge bg-secondary-lt status-badge"><?= e($record['status'] ?? 'new') ?></span></div></div>
                            <div class="datagrid-item"><div class="datagrid-title">Last login</div><div class="datagrid-content"><?= e($record['last_login_at'] ?? 'Never') ?></div></div>
                            <div class="datagrid-item"><div class="datagrid-title">Verified</div><div class="datagrid-content"><?= e($record['email_verified_at'] ?? 'No') ?></div></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
