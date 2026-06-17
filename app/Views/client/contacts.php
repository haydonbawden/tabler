<div class="row row-cards">
    <div class="col-lg-4">
        <form class="card" method="post" action="/client/contacts">
            <div class="card-header">
                <h3 class="card-title">Add contact</h3>
            </div>
            <div class="card-body">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Client</label>
                    <select class="form-select" name="client_id">
                        <?php foreach ($clients as $client): ?><option value="<?= e($client['id']) ?>"><?= e($client['legal_name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Display name</label>
                    <div class="input-icon">
                        <span class="input-icon-addon"><i class="ti ti-user"></i></span>
                        <input class="form-control" name="display_name" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <div class="input-icon">
                        <span class="input-icon-addon"><i class="ti ti-mail"></i></span>
                        <input class="form-control" type="email" name="email" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone</label>
                    <div class="input-icon">
                        <span class="input-icon-addon"><i class="ti ti-phone"></i></span>
                        <input class="form-control" name="phone">
                    </div>
                </div>
            </div>
            <div class="card-footer text-end">
                <button class="btn btn-primary" type="submit"><i class="ti ti-plus me-1"></i>Add contact</button>
            </div>
        </form>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Contacts</h3>
            </div>
            <?php foreach ($contacts as $contact): ?>
                <form id="contact-form-<?= e($contact['id']) ?>" method="post" action="/client/contacts/<?= e($contact['id']) ?>">
                    <?= csrf_field() ?>
                </form>
            <?php endforeach; ?>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th class="w-1"></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($contacts as $contact): ?>
                        <?php $formId = 'contact-form-' . $contact['id']; ?>
                        <tr>
                            <td><input class="form-control form-control-sm" form="<?= e($formId) ?>" name="display_name" value="<?= e($contact['display_name']) ?>"></td>
                            <td><input class="form-control form-control-sm" form="<?= e($formId) ?>" name="email" type="email" value="<?= e($contact['email']) ?>"></td>
                            <td><input class="form-control form-control-sm" form="<?= e($formId) ?>" name="phone" value="<?= e($contact['phone']) ?>"></td>
                            <td><button class="btn btn-primary btn-sm" form="<?= e($formId) ?>" type="submit">Save</button></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$contacts): ?>
                        <tr><td colspan="4" class="text-secondary">No contacts found.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
