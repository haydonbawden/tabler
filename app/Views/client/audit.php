<?php
if (!$audit): ?><div class="alert alert-warning">Audit not found.</div><?php return; endif;
$responses = $responses ?? [];
$responseGroups = $responseGroups ?? ['General' => $responses];
$progress = $progress ?? [];
$canEditEvidence = in_array($audit['status'], ['awaiting_evidence', 'changes_requested'], true);
$formatDate = static function ($value): string {
    if (!$value) {
        return 'Not set';
    }
    $timestamp = strtotime((string) $value);
    return $timestamp ? date('d M Y', $timestamp) : (string) $value;
};
?>

<div class="audit-workbench detail-shell">
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3 align-items-center">
                <div class="col-lg">
                    <div class="d-flex align-items-center gap-3">
                        <span class="avatar bg-blue-lt text-blue"><i class="ti ti-clipboard-check"></i></span>
                        <div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <h2 class="h3 mb-0"><?= e($audit['audit_number'] ?: 'Audit #' . $audit['id']) ?></h2>
                                <?php $value = $audit['status']; include app('root') . '/app/Views/components/status-badge.php'; ?>
                            </div>
                            <div class="text-secondary small"><?= e($audit['client']) ?> - <?= e($audit['certification']) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body border-top py-2">
            <div class="row g-0 record-highlights">
                <div class="col-6 col-md-3"><div class="record-highlight"><div class="text-secondary">Due</div><div class="fw-medium"><?= e($formatDate($audit['due_date'] ?? null)) ?></div></div></div>
                <div class="col-6 col-md-3"><div class="record-highlight"><div class="text-secondary">Responses</div><div class="fw-medium"><?= e((string) ($progress['required_submitted'] ?? 0)) ?> / <?= e((string) ($progress['required'] ?? 0)) ?> required</div></div></div>
                <div class="col-6 col-md-3"><div class="record-highlight"><div class="text-secondary">Evidence files</div><div class="fw-medium"><?= e((string) ($progress['files'] ?? 0)) ?></div></div></div>
                <div class="col-6 col-md-3"><div class="record-highlight"><div class="text-secondary">Audit result</div><div class="fw-medium"><?= e($audit['result'] ?: 'Pending') ?></div></div></div>
            </div>
            <div class="progress progress-sm mt-2">
                <div class="progress-bar" style="width: <?= e((string) ($progress['required_submitted_percent'] ?? 0)) ?>%" role="progressbar" aria-valuenow="<?= e((string) ($progress['required_submitted_percent'] ?? 0)) ?>" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
        </div>
    </div>

    <div class="row row-cards">
        <div class="col-xl-8">
            <div class="vstack gap-3">
                <?php foreach ($responseGroups as $section => $sectionResponses): ?>
                    <div class="text-secondary text-uppercase small fw-bold mt-1"><?= e($section) ?></div>
                    <?php foreach ($sectionResponses as $response): ?>
                        <div class="card">
                            <form method="post" action="/client/audits/<?= e($audit['id']) ?>/criteria/<?= e($response['id']) ?>">
                            <div class="card-header">
                                <div>
                                    <div class="text-secondary small"><?= e($response['reference']) ?><?= (int) ($response['is_required'] ?? 0) === 1 ? ' - Required' : ' - Optional' ?></div>
                                    <h3 class="card-title mb-0"><?= e($response['title']) ?></h3>
                                </div>
                                <div class="card-actions"><?php $value = $response['client_status']; include app('root') . '/app/Views/components/status-badge.php'; ?></div>
                            </div>
                            <div class="card-body">
                                <?= csrf_field() ?>
                                <div class="row g-3">
                                    <div class="col-lg-5">
                                        <label class="form-label">Requirement</label>
                                        <div class="text-secondary"><?= nl2br(e($response['requirement_text'])) ?></div>
                                        <?php if ($response['guidance_text']): ?>
                                            <label class="form-label mt-3">Guidance</label>
                                            <div class="text-secondary"><?= nl2br(e($response['guidance_text'])) ?></div>
                                        <?php endif; ?>
                                        <?php if ($response['evidence_prompt']): ?><div class="alert alert-info mt-3 mb-0"><?= e($response['evidence_prompt']) ?></div><?php endif; ?>
                                    </div>
                                    <div class="col-lg-7">
                                        <label class="form-label">Response</label>
                                        <textarea class="form-control" name="client_response" rows="6"<?= $canEditEvidence ? '' : ' readonly' ?>><?= e($response['client_response']) ?></textarea>
                                        <div class="mt-2">
                                            <select class="form-select w-auto" name="client_status"<?= $canEditEvidence ? '' : ' disabled' ?>>
                                                <option<?= selected($response['client_status'], 'in_progress') ?>>in_progress</option>
                                                <option<?= selected($response['client_status'], 'submitted') ?>>submitted</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php if ($canEditEvidence): ?><div class="card-footer text-end"><button class="btn btn-primary" type="submit"><i class="ti ti-device-floppy me-1"></i>Save response</button></div><?php endif; ?>
                            </form>
                            <?php if ($canEditEvidence): ?>
                                <form class="card-footer d-flex gap-2 align-items-center" method="post" action="/client/audits/<?= e($audit['id']) ?>/criteria/<?= e($response['id']) ?>/files" enctype="multipart/form-data">
                                    <?= csrf_field() ?>
                                    <input class="form-control" type="file" name="evidence_file">
                                    <button class="btn btn-outline-primary" type="submit"><i class="ti ti-upload me-1"></i>Upload</button>
                                </form>
                            <?php endif; ?>
                            <div class="card-footer">
                                <div class="fw-medium mb-2">Evidence files</div>
                                <?php if (!empty($response['files'])): ?>
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($response['files'] as $file): ?>
                                            <div class="list-group-item px-0 d-flex align-items-center justify-content-between gap-2">
                                                <a href="/evidence/<?= e($file['id']) ?>/download"><i class="ti ti-paperclip me-1"></i><?= e($file['original_filename']) ?> <span class="text-secondary"><?= e(number_format((int) $file['size_bytes'] / 1024, 1)) ?> KB</span></a>
                                                <?php if ((int) ($file['uploaded_by'] ?? 0) === (int) \App\Core\Auth::id() && $canEditEvidence): ?>
                                                    <form method="post" action="/client/audits/<?= e($audit['id']) ?>/evidence/<?= e($file['id']) ?>/delete">
                                                        <?= csrf_field() ?>
                                                        <button class="btn btn-outline-danger btn-icon" type="submit" title="Delete evidence"><i class="ti ti-trash"></i></button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-secondary">No files uploaded.</div>
                                <?php endif; ?>
                            </div>
                            <?php if ($response['auditor_comment']): ?><div class="card-footer"><strong>Auditor comment:</strong> <?= nl2br(e($response['auditor_comment'])) ?></div><?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
                <?php if (!$responses): ?><div class="card"><div class="card-body text-secondary">No criteria are available for this audit.</div></div><?php endif; ?>
            </div>
        </div>
        <div class="col-xl-4">
            <?php
            $transitionUrl = '/client/audits/' . $audit['id'] . '/transition';
            $generateCertificateUrl = '';
            include app('root') . '/app/Views/components/audit-lifecycle-actions.php';
            ?>
        </div>
    </div>
</div>
