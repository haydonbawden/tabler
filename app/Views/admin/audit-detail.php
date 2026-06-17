<?php
$audit = $audit ?? null;
$responses = $responses ?? [];
$progress = $progress ?? [];
$certificates = $certificates ?? [];
$payments = $payments ?? [];
$formatDate = static function ($value): string {
    if (!$value) {
        return 'Not set';
    }
    $timestamp = strtotime((string) $value);
    return $timestamp ? date('d M Y', $timestamp) : (string) $value;
};
?>

<?php if (!$audit): ?>
    <form class="card detail-shell" method="post" action="/admin/audits/new">
        <div class="card-header"><h3 class="card-title">Create audit</h3></div>
        <div class="card-body">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-4"><label class="form-label">Audit number</label><input class="form-control" name="audit_number"></div>
                <div class="col-md-4"><label class="form-label">Client</label><select class="form-select" name="client_id"><?php foreach ($clients as $client): ?><option value="<?= e($client['id']) ?>"><?= e($client['legal_name']) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-4"><label class="form-label">Certification</label><select class="form-select" name="certification_type_id"><?php foreach ($types as $type): ?><option value="<?= e($type['id']) ?>"><?= e($type['name']) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-4"><label class="form-label">Auditor</label><select class="form-select" name="auditor_id"><option value="">Unassigned</option><?php foreach ($auditors as $auditor): ?><option value="<?= e($auditor['id']) ?>"><?= e($auditor['name']) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-4"><label class="form-label">Source</label><select class="form-select" name="source"><?php foreach (['manual','imported','renewal_payment'] as $source): ?><option><?= e($source) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-4"><label class="form-label">Due</label><input class="form-control" type="date" name="due_date"></div>
                <div class="col-md-4"><label class="form-label">Start</label><input class="form-control" type="date" name="start_date"></div>
                <div class="col-md-4"><label class="form-label">End</label><input class="form-control" type="date" name="end_date"></div>
                <div class="col-12"><label class="form-label">Audit scope</label><textarea class="form-control" name="audit_scope" rows="4"></textarea></div>
            </div>
        </div>
        <div class="card-footer text-end"><button class="btn btn-primary" type="submit"><i class="ti ti-device-floppy me-1"></i>Create audit</button></div>
    </form>
<?php else: ?>
    <div class="audit-workbench detail-shell">
        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3 align-items-center">
                    <div class="col-lg">
                        <div class="d-flex align-items-center gap-3">
                            <span class="avatar bg-blue-lt text-blue"><i class="ti ti-clipboard-list"></i></span>
                            <div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <h2 class="h3 mb-0"><?= e($audit['audit_number'] ?: 'Audit #' . $audit['id']) ?></h2>
                                    <?php $value = $audit['status']; include app('root') . '/app/Views/components/status-badge.php'; ?>
                                </div>
                                <div class="text-secondary small"><?= e($audit['client'] ?? '') ?> - <?= e($audit['certification'] ?? '') ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-auto">
                        <button class="btn btn-primary" type="submit" form="audit-setup-form"><i class="ti ti-device-floppy me-1"></i>Save setup</button>
                    </div>
                </div>
            </div>
            <div class="card-body border-top py-2">
                <div class="row g-0 record-highlights">
                    <div class="col-6 col-md-3"><div class="record-highlight"><div class="text-secondary">Due</div><div class="fw-medium"><?= e($formatDate($audit['due_date'] ?? null)) ?></div></div></div>
                    <div class="col-6 col-md-3"><div class="record-highlight"><div class="text-secondary">Evidence</div><div class="fw-medium"><?= e((string) ($progress['submitted'] ?? 0)) ?> / <?= e((string) ($progress['total'] ?? 0)) ?> submitted</div></div></div>
                    <div class="col-6 col-md-3"><div class="record-highlight"><div class="text-secondary">Files</div><div class="fw-medium"><?= e((string) ($progress['files'] ?? 0)) ?></div></div></div>
                    <div class="col-6 col-md-3"><div class="record-highlight"><div class="text-secondary">Accepted criteria</div><div class="fw-medium"><?= e((string) ($progress['acceptable_required'] ?? 0)) ?> / <?= e((string) ($progress['required'] ?? 0)) ?></div></div></div>
                </div>
                <div class="progress progress-sm mt-2">
                    <div class="progress-bar" style="width: <?= e((string) ($progress['reviewed_percent'] ?? 0)) ?>%" role="progressbar" aria-valuenow="<?= e((string) ($progress['reviewed_percent'] ?? 0)) ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
        </div>

        <div class="row row-cards">
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs">
                            <li class="nav-item"><a href="#audit-setup" class="nav-link active" data-bs-toggle="tab">Audit setup</a></li>
                            <li class="nav-item"><a href="#audit-assessment" class="nav-link" data-bs-toggle="tab">Assessment</a></li>
                            <li class="nav-item"><a href="#audit-certificates" class="nav-link" data-bs-toggle="tab">Certificates</a></li>
                            <li class="nav-item"><a href="#audit-payments" class="nav-link" data-bs-toggle="tab">Payments</a></li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content">
                            <div class="tab-pane active show" id="audit-setup">
                                <form id="audit-setup-form" method="post" action="/admin/audits/<?= e($audit['id']) ?>">
                                    <?= csrf_field() ?>
                                    <div class="row g-3">
                                        <div class="col-md-4"><label class="form-label">Audit number</label><input class="form-control" name="audit_number" value="<?= e($audit['audit_number'] ?? '') ?>"></div>
                                        <div class="col-md-4"><label class="form-label">Client</label><select class="form-select" name="client_id"><?php foreach ($clients as $client): ?><option value="<?= e($client['id']) ?>"<?= selected($audit['client_id'] ?? '', $client['id']) ?>><?= e($client['legal_name']) ?></option><?php endforeach; ?></select></div>
                                        <div class="col-md-4"><label class="form-label">Certification</label><select class="form-select" name="certification_type_id"><?php foreach ($types as $type): ?><option value="<?= e($type['id']) ?>"<?= selected($audit['certification_type_id'] ?? '', $type['id']) ?>><?= e($type['name']) ?></option><?php endforeach; ?></select></div>
                                        <div class="col-md-4"><label class="form-label">Auditor</label><select class="form-select" name="auditor_id"><option value="">Unassigned</option><?php foreach ($auditors as $auditor): ?><option value="<?= e($auditor['id']) ?>"<?= selected($audit['auditor_id'] ?? '', $auditor['id']) ?>><?= e($auditor['name']) ?></option><?php endforeach; ?></select></div>
                                        <div class="col-md-4"><label class="form-label">Source</label><select class="form-select" name="source"><?php foreach (['manual','imported','renewal_payment'] as $source): ?><option<?= selected($audit['source'] ?? 'manual', $source) ?>><?= e($source) ?></option><?php endforeach; ?></select></div>
                                        <div class="col-md-4"><label class="form-label">Current status</label><div class="form-control-plaintext"><?php $value = $audit['status']; include app('root') . '/app/Views/components/status-badge.php'; ?></div></div>
                                        <div class="col-md-4"><label class="form-label">Start</label><input class="form-control" type="date" name="start_date" value="<?= e($audit['start_date'] ?? '') ?>"></div>
                                        <div class="col-md-4"><label class="form-label">End</label><input class="form-control" type="date" name="end_date" value="<?= e($audit['end_date'] ?? '') ?>"></div>
                                        <div class="col-md-4"><label class="form-label">Due</label><input class="form-control" type="date" name="due_date" value="<?= e($audit['due_date'] ?? '') ?>"></div>
                                        <div class="col-12"><label class="form-label">Audit scope</label><textarea class="form-control" name="audit_scope" rows="4"><?= e($audit['audit_scope'] ?? '') ?></textarea></div>
                                    </div>
                                </form>
                            </div>
                            <div class="tab-pane" id="audit-assessment">
                                <div class="table-responsive">
                                    <table class="table table-vcenter card-table">
                                        <thead><tr><th>Section</th><th>Criterion</th><th>Client status</th><th>Evidence</th><th>Auditor result</th><th>Comment</th></tr></thead>
                                        <tbody>
                                        <?php foreach ($responses as $response): ?>
                                            <tr>
                                                <td><?= e($response['section']) ?></td>
                                                <td class="fw-medium"><?= e($response['title']) ?></td>
                                                <td><?php $value = $response['client_status']; include app('root') . '/app/Views/components/status-badge.php'; ?></td>
                                                <td><?= e((string) $response['evidence_count']) ?> files</td>
                                                <td><?= e($response['auditor_result'] ?? 'Pending') ?></td>
                                                <td class="text-secondary"><?= e($response['auditor_comment'] ?? '') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (!$responses): ?><tr><td colspan="6" class="text-secondary">No assessment criteria have been created for this audit.</td></tr><?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="tab-pane" id="audit-certificates">
                                <div class="table-responsive">
                                    <table class="table table-vcenter card-table">
                                        <thead><tr><th>Certificate</th><th>Issue</th><th>Status</th><th>Issue date</th><th>Expiry</th><th>PDF</th></tr></thead>
                                        <tbody>
                                        <?php foreach ($certificates as $certificate): ?>
                                            <tr class="table-row-link" data-href="/admin/certificates/<?= e($certificate['id']) ?>"><td class="fw-medium"><?= e($certificate['certificate_number']) ?></td><td><?= e($certificate['issue_number']) ?></td><td><?php $value = $certificate['status']; include app('root') . '/app/Views/components/status-badge.php'; ?></td><td><?= e($formatDate($certificate['issue_date'])) ?></td><td><?= e($formatDate($certificate['expiry_date'])) ?></td><td><?= $certificate['pdf_path'] ? '<a href="/admin/certificates/' . e($certificate['id']) . '/download">Download</a>' : '<span class="text-secondary">Not generated</span>' ?></td></tr>
                                        <?php endforeach; ?>
                                        <?php if (!$certificates): ?><tr><td colspan="6" class="text-secondary">No certificates have been generated for this audit.</td></tr><?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="tab-pane" id="audit-payments">
                                <div class="table-responsive">
                                    <table class="table table-vcenter card-table">
                                        <thead><tr><th>ID</th><th>Amount</th><th>Currency</th><th>Status</th><th>Paid at</th><th>Created</th></tr></thead>
                                        <tbody>
                                        <?php foreach ($payments as $payment): ?>
                                            <tr><td>#<?= e($payment['id']) ?></td><td><?= e('$' . number_format(((int) $payment['amount_cents']) / 100, 2)) ?></td><td><?= e($payment['currency']) ?></td><td><?php $value = $payment['status']; include app('root') . '/app/Views/components/status-badge.php'; ?></td><td><?= e($formatDate($payment['paid_at'])) ?></td><td><?= e($formatDate($payment['created_at'])) ?></td></tr>
                                        <?php endforeach; ?>
                                        <?php if (!$payments): ?><tr><td colspan="6" class="text-secondary">No payments are linked to this audit.</td></tr><?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <?php
                $transitionUrl = '/admin/audits/' . $audit['id'] . '/transition';
                $generateCertificateUrl = '/auditor/audits/' . $audit['id'] . '/generate-certificate';
                $allowAdminOverride = true;
                include app('root') . '/app/Views/components/audit-lifecycle-actions.php';
                ?>
            </div>
        </div>
    </div>
<?php endif; ?>
