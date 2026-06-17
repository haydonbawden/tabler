<div class="detail-shell">
    <div class="row row-cards">
        <div class="col-lg-8">
            <form id="template-form" class="card" method="post" action="/admin/email-templates/<?= e($template['id'] ?? 'new') ?>">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs">
                        <li class="nav-item"><a href="#email-template-code" class="nav-link active" data-bs-toggle="tab">HTML code</a></li>
                        <li class="nav-item"><a href="#email-template-preview-pane" class="nav-link" data-bs-toggle="tab">HTML preview</a></li>
                        <li class="nav-item"><a href="#email-template-text-pane" class="nav-link" data-bs-toggle="tab">Text version</a></li>
                    </ul>
                </div>
                <div class="card-body">
                    <?= csrf_field() ?>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Template key</label><input class="form-control" name="template_key" value="<?= e($template['template_key'] ?? '') ?>" required></div>
                        <div class="col-md-6"><label class="form-label">Name</label><input class="form-control" name="name" value="<?= e($template['name'] ?? '') ?>" required></div>
                        <div class="col-12"><label class="form-label">Subject</label><input class="form-control" name="subject" value="<?= e($template['subject'] ?? '') ?>" required></div>
                        <div class="col-12">
                            <div class="tab-content">
                                <div class="tab-pane active show" id="email-template-code">
                                    <label class="form-label">HTML body</label>
                                    <textarea class="form-control font-monospace" name="body_html" rows="18" data-template-preview-source="#email-template-preview" required><?= e($template['body_html'] ?? '') ?></textarea>
                                </div>
                                <div class="tab-pane" id="email-template-preview-pane">
                                    <iframe id="email-template-preview" class="template-preview-frame" title="Email template preview"></iframe>
                                </div>
                                <div class="tab-pane" id="email-template-text-pane">
                                    <label class="form-label">Text body</label>
                                    <textarea class="form-control font-monospace" name="body_text" rows="12"><?= e($template['body_text'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-end"><button class="btn btn-primary" type="submit"><i class="ti ti-device-floppy me-1"></i>Save template</button></div>
            </form>
        </div>
        <div class="col-lg-4">
            <div class="detail-aside">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Template status</h3></div>
                    <div class="card-body">
                        <label class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" form="template-form" name="is_active" value="1" <?= ($template['is_active'] ?? 1) ? 'checked' : '' ?>>
                            <span class="form-check-label">Active template</span>
                        </label>
                        <div class="datagrid">
                            <div class="datagrid-item"><div class="datagrid-title">Key</div><div class="datagrid-content"><?= e($template['template_key'] ?? 'New template') ?></div></div>
                            <div class="datagrid-item"><div class="datagrid-title">Status</div><div class="datagrid-content"><span class="badge bg-blue-lt"><?= ($template['is_active'] ?? 1) ? 'Active' : 'Inactive' ?></span></div></div>
                        </div>
                        <div class="alert alert-info mt-3 mb-0">Use template variables consistently across HTML and text versions before enabling automated sends.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
