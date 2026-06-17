<?php
$setting = static function (string $key, $default = '') use ($settings) {
    return $settings[$key] ?? $default;
};
?>
<form class="settings-shell" method="post" action="/admin/settings">
    <?= csrf_field() ?>
    <div class="row row-cards">
        <div class="col-lg-3">
            <div class="card">
                <div class="list-group list-group-flush">
                    <a href="#settings-organisation" class="list-group-item list-group-item-action active"><i class="ti ti-building me-2"></i>Organisation</a>
                    <a href="#settings-branding" class="list-group-item list-group-item-action"><i class="ti ti-palette me-2"></i>Branding</a>
                    <a href="#settings-certificates" class="list-group-item list-group-item-action"><i class="ti ti-certificate me-2"></i>Certificates</a>
                    <a href="#settings-email" class="list-group-item list-group-item-action"><i class="ti ti-mail-cog me-2"></i>Email</a>
                    <a href="#settings-security" class="list-group-item list-group-item-action"><i class="ti ti-shield-lock me-2"></i>Security</a>
                    <a href="#settings-portal" class="list-group-item list-group-item-action"><i class="ti ti-adjustments me-2"></i>Portal</a>
                    <a href="#settings-theme" class="list-group-item list-group-item-action"><i class="ti ti-moon me-2"></i>Theme</a>
                    <a href="#settings-users" class="list-group-item list-group-item-action"><i class="ti ti-users me-2"></i>Users & permissions</a>
                    <a href="#settings-audit-criteria" class="list-group-item list-group-item-action"><i class="ti ti-list-check me-2"></i>Audit criteria</a>
                </div>
            </div>
        </div>
        <div class="col-lg-9">
            <div class="row row-cards">
                <div class="col-12" id="settings-organisation">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">Organisation profile</h3></div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6"><label class="form-label">Trading name</label><input class="form-control" name="business_name" value="<?= e($setting('business_name', 'Castor Audit & Advisory')) ?>"></div>
                                <div class="col-md-6"><label class="form-label">ABN / registration</label><input class="form-control" name="abn" value="<?= e($setting('abn', '50 638 775 381')) ?>"></div>
                                <div class="col-md-6"><label class="form-label">Support email</label><input class="form-control" type="email" name="support_email" value="<?= e($setting('support_email', 'support@castoraustralia.com.au')) ?>"></div>
                                <div class="col-md-6"><label class="form-label">Billing email</label><input class="form-control" type="email" name="billing_email" value="<?= e($setting('billing_email', 'billing@castoraustralia.com.au')) ?>"></div>
                                <div class="col-12"><label class="form-label">Registered address</label><textarea class="form-control" name="registered_address" rows="3"><?= e($setting('registered_address', '')) ?></textarea></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12" id="settings-branding">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">Branding</h3></div>
                        <div class="card-body">
                            <div class="row g-3 align-items-center">
                                <div class="col-md-4">
                                    <div class="border rounded p-3 text-center bg-light">
                                        <img src="/assets/caa-logo-landscape.png" alt="Castor Audit & Advisory" class="img-fluid" style="max-height: 80px;">
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="row g-3">
                                        <div class="col-md-6"><label class="form-label">Primary colour</label><input class="form-control form-control-color" type="color" name="primary_colour" value="<?= e($setting('primary_colour', '#206bc4')) ?>"></div>
                                        <div class="col-md-6"><label class="form-label">Accent colour</label><input class="form-control form-control-color" type="color" name="accent_colour" value="<?= e($setting('accent_colour', '#f59f00')) ?>"></div>
                                        <div class="col-12"><label class="form-label">Logo URL</label><input class="form-control" name="logo_url" value="<?= e($setting('logo_url', '/assets/caa-logo-landscape.png')) ?>"></div>
                                        <div class="col-12"><label class="form-check form-switch"><input class="form-check-input" type="checkbox" name="show_logo" value="1" <?= $setting('show_logo', '1') === '1' ? 'checked' : '' ?>><span class="form-check-label">Show logo in portal navigation</span></label></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6" id="settings-certificates">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">Certificate rules</h3></div>
                        <div class="card-body">
                            <div class="mb-3"><label class="form-label">Default validity period</label><select class="form-select" name="certificate_validity_months"><?php foreach (['12' => '12 months', '24' => '24 months', '36' => '36 months'] as $value => $label): ?><option value="<?= e($value) ?>"<?= selected($setting('certificate_validity_months', '12'), $value) ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
                            <div class="mb-3"><label class="form-label">Renewal reminder window</label><div class="input-group"><input class="form-control" type="number" name="renewal_reminder_days" value="<?= e($setting('renewal_reminder_days', '90')) ?>"><span class="input-group-text">days</span></div></div>
                            <label class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" name="auto_generate_pdf" value="1" <?= $setting('auto_generate_pdf', '1') === '1' ? 'checked' : '' ?>><span class="form-check-label">Auto-generate PDF after passed audit</span></label>
                            <label class="form-check form-switch"><input class="form-check-input" type="checkbox" name="public_verification" value="1" <?= $setting('public_verification', '1') === '1' ? 'checked' : '' ?>><span class="form-check-label">Allow public certificate verification</span></label>
                        </div>
                    </div>
                </div>
                <div class="col-md-6" id="settings-email">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">Email delivery</h3></div>
                        <div class="card-body">
                            <div class="mb-3"><label class="form-label">Sender name</label><input class="form-control" name="sender_name" value="<?= e($setting('sender_name', 'Castor Audit & Advisory')) ?>"></div>
                            <div class="mb-3"><label class="form-label">Sender email</label><input class="form-control" type="email" name="sender_email" value="<?= e($setting('sender_email', 'support@castoraustralia.com.au')) ?>"></div>
                            <label class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" name="send_audit_reminders" value="1" <?= $setting('send_audit_reminders', '1') === '1' ? 'checked' : '' ?>><span class="form-check-label">Send audit reminders</span></label>
                            <label class="form-check form-switch"><input class="form-check-input" type="checkbox" name="send_expiry_notices" value="1" <?= $setting('send_expiry_notices', '1') === '1' ? 'checked' : '' ?>><span class="form-check-label">Send certificate expiry notices</span></label>
                        </div>
                    </div>
                </div>
                <div class="col-md-6" id="settings-security">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">Security</h3></div>
                        <div class="card-body">
                            <div class="mb-3"><label class="form-label">Session timeout</label><select class="form-select" name="session_timeout"><?php foreach (['30' => '30 minutes', '60' => '1 hour', '240' => '4 hours'] as $value => $label): ?><option value="<?= e($value) ?>"<?= selected($setting('session_timeout', '30'), $value) ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
                            <div class="mb-3"><label class="form-label">Login attempt limit</label><div class="input-group"><input class="form-control" type="number" name="login_attempt_limit" value="<?= e($setting('login_attempt_limit', '5')) ?>"><span class="input-group-text">attempts</span></div></div>
                            <label class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" name="strong_passwords" value="1" <?= $setting('strong_passwords', '1') === '1' ? 'checked' : '' ?>><span class="form-check-label">Require strong passwords</span></label>
                            <label class="form-check form-switch"><input class="form-check-input" type="checkbox" name="require_admin_mfa" value="1" <?= $setting('require_admin_mfa', '0') === '1' ? 'checked' : '' ?>><span class="form-check-label">Require MFA for admin users</span></label>
                        </div>
                    </div>
                </div>
                <div class="col-md-6" id="settings-portal">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">Portal experience</h3></div>
                        <div class="card-body">
                            <div class="mb-3"><label class="form-label">Default admin landing page</label><select class="form-select" name="admin_landing_page"><?php foreach (['clients' => 'Clients', 'audits' => 'Audits', 'certificates' => 'Certificates'] as $value => $label): ?><option value="<?= e($value) ?>"<?= selected($setting('admin_landing_page', 'clients'), $value) ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
                            <div class="mb-3"><label class="form-label">Default records per table</label><select class="form-select" name="records_per_table"><?php foreach (['20' => '20 records', '50' => '50 records', '100' => '100 records'] as $value => $label): ?><option value="<?= e($value) ?>"<?= selected($setting('records_per_table', '20'), $value) ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
                            <label class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" name="client_self_service_contacts" value="1" <?= $setting('client_self_service_contacts', '1') === '1' ? 'checked' : '' ?>><span class="form-check-label">Enable client self-service contacts</span></label>
                            <label class="form-check form-switch"><input class="form-check-input" type="checkbox" name="show_public_verification_link" value="1" <?= $setting('show_public_verification_link', '1') === '1' ? 'checked' : '' ?>><span class="form-check-label">Show public verification link</span></label>
                        </div>
                    </div>
                </div>
                <div class="col-md-6" id="settings-theme">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">Theme</h3></div>
                        <div class="card-body">
                            <label class="form-check form-check-card mb-3">
                                <input class="form-check-input" type="radio" name="theme" value="light" <?= $theme === 'light' ? 'checked' : '' ?>>
                                <span class="form-check-label">Light theme</span>
                            </label>
                            <label class="form-check form-check-card">
                                <input class="form-check-input" type="radio" name="theme" value="dark" <?= $theme === 'dark' ? 'checked' : '' ?>>
                                <span class="form-check-label">Dark theme</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="col-12" id="settings-users">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Users & permissions</h3>
                            <div class="card-actions"><a href="/admin/users/new" class="btn btn-primary btn-sm"><i class="ti ti-plus me-1"></i>New user</a></div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table">
                                <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th></tr></thead>
                                <tbody>
                                <?php foreach ($users as $record): ?>
                                    <tr class="table-row-link" data-href="/admin/users/<?= e($record['id']) ?>"><td><?= e($record['name']) ?></td><td><?= e($record['email']) ?></td><td><span class="badge bg-blue-lt status-badge"><?= e($record['role']) ?></span></td><td><span class="badge bg-secondary-lt status-badge"><?= e($record['status']) ?></span></td></tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-12" id="settings-audit-criteria">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Audit criteria</h3>
                            <div class="card-actions"><a href="/admin/audit-criteria/new" class="btn btn-primary btn-sm"><i class="ti ti-plus me-1"></i>New criterion</a></div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table">
                                <thead><tr><th>Certification</th><th>Section</th><th>Criterion</th><th>Status</th></tr></thead>
                                <tbody>
                                <?php foreach ($criteria as $criterion): ?>
                                    <tr class="table-row-link" data-href="/admin/audit-criteria/<?= e($criterion['id']) ?>"><td><?= e($criterion['certification']) ?></td><td><?= e($criterion['section']) ?></td><td><?= e($criterion['title']) ?></td><td><span class="badge <?= $criterion['is_active'] ? 'bg-success-lt' : 'bg-secondary-lt' ?>"><?= $criterion['is_active'] ? 'Active' : 'Inactive' ?></span></td></tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="text-end mt-3">
        <button class="btn btn-primary" type="submit"><i class="ti ti-device-floppy me-1"></i>Save settings</button>
    </div>
</form>
