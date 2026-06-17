<div class="detail-shell">
    <div class="row row-cards">
        <div class="col-lg-8">
            <form id="contact-form" class="card" method="post" action="/admin/contacts/<?= e($contact['id'] ?? 'new') ?>">
                <div class="card-header"><h3 class="card-title">Contact details</h3></div>
                <div class="card-body">
                    <?= csrf_field() ?>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Client</label><select class="form-select" name="client_id" required><?php foreach ($clients as $client): ?><option value="<?= e($client['id']) ?>"<?= selected($contact['client_id'] ?? '', $client['id']) ?>><?= e($client['legal_name']) ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-6"><label class="form-label">Display name</label><input class="form-control" name="display_name" value="<?= e($contact['display_name'] ?? '') ?>" required></div>
                        <div class="col-md-6"><label class="form-label">First name</label><input class="form-control" name="first_name" value="<?= e($contact['first_name'] ?? '') ?>"></div>
                        <div class="col-md-6"><label class="form-label">Last name</label><input class="form-control" name="last_name" value="<?= e($contact['last_name'] ?? '') ?>"></div>
                        <div class="col-md-6"><label class="form-label">Email</label><input class="form-control" name="email" type="email" value="<?= e($contact['email'] ?? '') ?>" required></div>
                        <div class="col-md-6"><label class="form-label">Phone</label><input class="form-control" name="phone" value="<?= e($contact['phone'] ?? '') ?>"></div>
                        <div class="col-md-6"><label class="form-label">Position</label><input class="form-control" name="position_title" value="<?= e($contact['position_title'] ?? '') ?>"></div>
                    </div>
                </div>
                <div class="card-footer text-end"><button class="btn btn-primary" type="submit"><i class="ti ti-device-floppy me-1"></i>Save contact</button></div>
            </form>
        </div>
        <div class="col-lg-4">
            <div class="detail-aside">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Notification preferences</h3></div>
                    <div class="card-body">
                        <?php foreach (['is_active' => 'Active contact', 'receives_reminders' => 'Receives reminders', 'receives_certificates' => 'Receives certificates', 'receives_audit_notifications' => 'Receives audit notifications'] as $field => $label): ?>
                            <label class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" form="contact-form" name="<?= e($field) ?>" value="1" <?= ($contact[$field] ?? 1) ? 'checked' : '' ?>>
                                <span class="form-check-label"><?= e($label) ?></span>
                            </label>
                        <?php endforeach; ?>
                        <p class="text-secondary mb-0">Preference toggles are saved with the main contact form.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
