<?php $isNew = !$certificate; ?>
<div class="detail-shell">
    <div class="row row-cards">
        <div class="col-lg-8">
            <form class="card" method="post" action="/admin/certificates/<?= e($certificate['id'] ?? 'new') ?>">
                <div class="card-header"><h3 class="card-title">Certificate details</h3></div>
                <div class="card-body">
                    <?= csrf_field() ?>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Audit</label>
                            <select class="form-select" name="audit_id">
                                <?php foreach ($audits as $audit): ?>
                                    <option value="<?= e($audit['id']) ?>"<?= selected($certificate['audit_id'] ?? '', $audit['id']) ?>><?= e($audit['audit_number'] . ' - ' . $audit['client'] . ' - ' . $audit['certification']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4"><label class="form-label">Certificate number</label><input class="form-control" name="certificate_number" value="<?= e($certificate['certificate_number'] ?? '') ?>" required></div>
                        <div class="col-md-4"><label class="form-label">Issue number</label><input class="form-control" type="number" name="issue_number" value="<?= e($certificate['issue_number'] ?? 1) ?>"></div>
                        <div class="col-md-4"><label class="form-label">Status</label><select class="form-select" name="status"><?php foreach (['draft','issued','replaced','revoked'] as $status): ?><option<?= selected($certificate['status'] ?? 'draft', $status) ?>><?= e($status) ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-4"><label class="form-label">Issue date</label><input class="form-control" type="date" name="issue_date" value="<?= e($certificate['issue_date'] ?? date('Y-m-d')) ?>"></div>
                        <div class="col-md-4"><label class="form-label">Expiry date</label><input class="form-control" type="date" name="expiry_date" value="<?= e($certificate['expiry_date'] ?? date('Y-m-d', strtotime('+12 months'))) ?>"></div>
                        <div class="col-md-4"><label class="form-label">Last day to renew</label><input class="form-control" type="date" name="last_day_to_renew" value="<?= e($certificate['last_day_to_renew'] ?? '') ?>"></div>
                        <div class="col-12"><label class="form-label">Audit scope</label><textarea class="form-control" name="audit_scope" rows="4"><?= e($certificate['audit_scope'] ?? '') ?></textarea></div>
                    </div>
                </div>
                <div class="card-footer text-end"><button class="btn btn-primary" type="submit"><i class="ti ti-device-floppy me-1"></i>Save certificate</button></div>
            </form>
        </div>
        <div class="col-lg-4">
            <div class="detail-aside">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Certificate actions</h3></div>
                    <div class="card-body">
                        <div class="datagrid mb-3">
                            <div class="datagrid-item"><div class="datagrid-title">Client</div><div class="datagrid-content"><?= e($certificate['legal_name'] ?? 'Not selected') ?></div></div>
                            <div class="datagrid-item"><div class="datagrid-title">Certification</div><div class="datagrid-content"><?= e($certificate['certification'] ?? 'Not selected') ?></div></div>
                            <div class="datagrid-item"><div class="datagrid-title">PDF</div><div class="datagrid-content"><?= e($certificate['pdf_path'] ?? 'Not generated') ?></div></div>
                        </div>
                        <?php if (!$isNew): ?>
                            <form method="post" action="/admin/certificates/<?= e($certificate['id']) ?>/generate"><?= csrf_field() ?><button class="btn btn-primary w-100" type="submit"><i class="ti ti-file-type-pdf me-1"></i>Generate PDF</button></form>
                            <?php if ($certificate['pdf_path']): ?><a class="btn btn-outline-primary w-100 mt-2" href="/admin/certificates/<?= e($certificate['id']) ?>/download"><i class="ti ti-download me-1"></i>Download PDF</a><?php endif; ?>
                        <?php else: ?>
                            <p class="text-secondary mb-0">Save the certificate before generating a PDF.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
