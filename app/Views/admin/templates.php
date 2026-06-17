<div class="card mb-3">
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs">
            <li class="nav-item"><a href="#template-emails" class="nav-link active" data-bs-toggle="tab">Email templates</a></li>
            <li class="nav-item"><a href="#template-certificates" class="nav-link" data-bs-toggle="tab">Certificate templates</a></li>
        </ul>
    </div>
</div>
<div class="tab-content">
    <div class="tab-pane active show" id="template-emails">
        <?php
        $title = 'Email templates';
        $detailPrefix = '/admin/email-templates/';
        $createUrl = '/admin/email-templates/new';
        $tableName = 'email_templates';
        include app('root') . '/app/Views/admin/simple-table.php';
        ?>
    </div>
    <div class="tab-pane" id="template-certificates">
        <div class="row row-cards">
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Import MS Word certificate template</h3></div>
                    <form class="card-body" method="post" action="/admin/certificate-templates" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label">Template file</label>
                            <input class="form-control" type="file" name="template_file" accept=".doc,.docx" required>
                            <div class="form-hint">Certificate templates must be uploaded as Microsoft Word files.</div>
                        </div>
                        <button class="btn btn-primary" type="submit"><i class="ti ti-upload me-1"></i>Upload template</button>
                    </form>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Required placeholders</h3></div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead><tr><th>Placeholder</th><th>Description</th></tr></thead>
                            <tbody>
                            <?php foreach ([
                                '{{certificate_number}}' => 'Certificate number',
                                '{{client_name}}' => 'Certified client legal name',
                                '{{abn}}' => 'Client ABN',
                                '{{certification}}' => 'Certification type',
                                '{{issue_date}}' => 'Certificate issue date',
                                '{{expiry_date}}' => 'Certificate expiry date',
                                '{{audit_scope}}' => 'Approved audit scope',
                                '{{validation_url}}' => 'Public verification URL',
                            ] as $placeholder => $description): ?>
                                <tr><td><code><?= e($placeholder) ?></code></td><td><?= e($description) ?></td></tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
