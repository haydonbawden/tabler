<div class="row row-cards">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Certificate details</h3></div>
            <div class="card-body">
                <div class="datagrid mb-3">
                    <div class="datagrid-item"><div class="datagrid-title">Certificate number</div><div class="datagrid-content"><?= e($certificate['certificate_number']) ?></div></div>
                    <div class="datagrid-item"><div class="datagrid-title">Client</div><div class="datagrid-content"><?= e($certificate['legal_name']) ?></div></div>
                    <div class="datagrid-item"><div class="datagrid-title">Certification</div><div class="datagrid-content"><?= e($certificate['certification']) ?></div></div>
                    <div class="datagrid-item"><div class="datagrid-title">Issue date</div><div class="datagrid-content"><?= e($certificate['issue_date']) ?></div></div>
                    <div class="datagrid-item"><div class="datagrid-title">Expiry date</div><div class="datagrid-content"><?= e($certificate['expiry_date']) ?></div></div>
                    <div class="datagrid-item"><div class="datagrid-title">Status</div><div class="datagrid-content"><span class="badge bg-blue-lt"><?= e($certificate['status']) ?></span></div></div>
                </div>
                <?php if ($certificate['pdf_path']): ?>
                    <a class="btn btn-primary" href="/client/certificates/<?= e($certificate['id']) ?>/download"><i class="ti ti-download me-1"></i>Download certificate</a>
                <?php else: ?>
                    <div class="alert alert-warning mb-0">The PDF has not been generated yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
