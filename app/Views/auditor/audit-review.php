<?php
if (!$audit): ?><div class="alert alert-warning">Audit not found.</div><?php return; endif;
$responses = $responses ?? [];
$responseGroups = $responseGroups ?? ['General' => $responses];
$progress = $progress ?? [];
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
                        <span class="avatar bg-blue-lt text-blue"><i class="ti ti-file-search"></i></span>
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
                <div class="col-6 col-md-3"><div class="record-highlight"><div class="text-secondary">Evidence</div><div class="fw-medium"><?= e((string) ($progress['submitted'] ?? 0)) ?> / <?= e((string) ($progress['total'] ?? 0)) ?></div></div></div>
                <div class="col-6 col-md-3"><div class="record-highlight"><div class="text-secondary">Reviewed</div><div class="fw-medium"><?= e((string) ($progress['reviewed'] ?? 0)) ?> / <?= e((string) ($progress['total'] ?? 0)) ?></div></div></div>
                <div class="col-6 col-md-3"><div class="record-highlight"><div class="text-secondary">Result</div><div class="fw-medium"><?= e($audit['result'] ?: 'Pending') ?></div></div></div>
            </div>
            <div class="progress progress-sm mt-2">
                <div class="progress-bar" style="width: <?= e((string) ($progress['reviewed_percent'] ?? 0)) ?>%" role="progressbar" aria-valuenow="<?= e((string) ($progress['reviewed_percent'] ?? 0)) ?>" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
        </div>
    </div>

    <div class="row row-cards">
        <div class="col-xl-8">
            <div class="vstack gap-3">
                <?php foreach ($responseGroups as $section => $sectionResponses): ?>
                    <div class="text-secondary text-uppercase small fw-bold mt-1"><?= e($section) ?></div>
                    <?php foreach ($sectionResponses as $response): ?>
                    <form class="card" method="post" action="/auditor/audits/<?= e($audit['id']) ?>/criteria/<?= e($response['id']) ?>/comment">
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
                                <div class="col-lg-6">
                                    <label class="form-label">Requirement</label>
                                    <div class="text-secondary"><?= nl2br(e($response['requirement_text'])) ?></div>
                                    <?php if ($response['guidance_text']): ?>
                                        <label class="form-label mt-3">Guidance</label>
                                        <div class="text-secondary"><?= nl2br(e($response['guidance_text'])) ?></div>
                                    <?php endif; ?>
                                    <?php if ($response['evidence_prompt']): ?>
                                        <label class="form-label mt-3">Evidence prompt</label>
                                        <div class="text-secondary"><?= nl2br(e($response['evidence_prompt'])) ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-lg-6">
                                    <label class="form-label">Client response</label>
                                    <div class="form-control-plaintext audit-response-text"><?= nl2br(e($response['client_response'] ?: 'No response submitted.')) ?></div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Evidence files</label>
                                    <?php if (!empty($response['files'])): ?>
                                        <div class="list-group list-group-flush border rounded">
                                            <?php foreach ($response['files'] as $file): ?>
                                                <a class="list-group-item" href="/evidence/<?= e($file['id']) ?>/download"><i class="ti ti-paperclip me-1"></i><?= e($file['original_filename']) ?> <span class="text-secondary"><?= e(number_format((int) $file['size_bytes'] / 1024, 1)) ?> KB</span></a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-secondary">No files uploaded.</div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-lg-8"><label class="form-label">Auditor comment</label><textarea class="form-control" name="auditor_comment" rows="3"><?= e($response['auditor_comment']) ?></textarea></div>
                                <div class="col-lg-4"><label class="form-label">Result</label><select class="form-select" name="auditor_result"><option value="">Pending</option><?php foreach (['acceptable','not_acceptable','needs_more_information'] as $result): ?><option<?= selected($response['auditor_result'], $result) ?>><?= e($result) ?></option><?php endforeach; ?></select></div>
                            </div>
                        </div>
                        <div class="card-footer text-end"><button class="btn btn-primary" type="submit"><i class="ti ti-device-floppy me-1"></i>Save review</button></div>
                    </form>
                    <?php endforeach; ?>
                <?php endforeach; ?>
                <?php if (!$responses): ?><div class="card"><div class="card-body text-secondary">No criteria are available for this audit.</div></div><?php endif; ?>
            </div>
        </div>
        <div class="col-xl-4">
            <?php
            $transitionUrl = '/auditor/audits/' . $audit['id'] . '/transition';
            $generateCertificateUrl = '/auditor/audits/' . $audit['id'] . '/generate-certificate';
            include app('root') . '/app/Views/components/audit-lifecycle-actions.php';
            ?>
        </div>
    </div>
</div>
