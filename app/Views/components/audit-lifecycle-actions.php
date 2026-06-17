<?php
$audit = $audit ?? null;
$lifecycleActions = $lifecycleActions ?? [];
$transitionUrl = $transitionUrl ?? '';
$returnTo = $returnTo ?? ($_SERVER['REQUEST_URI'] ?? '');
$generateCertificateUrl = $generateCertificateUrl ?? '';
$allowAdminOverride = $allowAdminOverride ?? false;
?>
<div class="card audit-lifecycle-card">
    <div class="card-header">
        <h3 class="card-title">Lifecycle actions</h3>
    </div>
    <div class="card-body">
        <?php if (!$audit): ?>
            <div class="text-secondary">Save the audit before lifecycle actions are available.</div>
        <?php elseif (!$lifecycleActions): ?>
            <div class="text-secondary">No lifecycle actions are available for this audit status.</div>
        <?php else: ?>
            <div class="vstack gap-2">
                <?php foreach ($lifecycleActions as $action): ?>
                    <?php
                    $isGenerate = ($action['target'] ?? '') === 'generate_certificate';
                    $formAction = $isGenerate ? $generateCertificateUrl : $transitionUrl;
                    $tone = in_array($action['tone'] ?? '', ['primary', 'success', 'warning', 'danger', 'secondary'], true) ? $action['tone'] : 'secondary';
                    ?>
                    <form class="audit-lifecycle-action border rounded p-2" method="post" action="<?= e($formAction) ?>">
                        <?= csrf_field() ?>
                        <?php if (!$isGenerate): ?>
                            <input type="hidden" name="target_status" value="<?= e($action['target']) ?>">
                        <?php endif; ?>
                        <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
                        <div class="d-flex align-items-center justify-content-between gap-2">
                            <div>
                                <div class="fw-medium"><i class="ti <?= e($action['icon'] ?? 'ti-arrow-right') ?> me-1"></i><?= e($action['label']) ?></div>
                                <?php if (!empty($action['requiresComment'])): ?><div class="text-secondary small">Requires confirmation and an auditor comment.</div><?php endif; ?>
                            </div>
                            <button class="btn btn-<?= e($tone) ?>" type="submit"><?= e($action['label']) ?></button>
                        </div>
                        <?php if (!empty($action['requiresComment'])): ?>
                            <div class="mt-2">
                                <label class="form-label">Decision comment</label>
                                <textarea class="form-control" name="overall_auditor_comment" rows="2" required></textarea>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($action['requiresConfirmation'])): ?>
                            <label class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="confirm" value="1" required>
                                <span class="form-check-label">Confirm this lifecycle decision</span>
                            </label>
                        <?php endif; ?>
                        <?php if ($allowAdminOverride && in_array($action['target'] ?? '', ['changes_requested', 'passed'], true)): ?>
                            <label class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="admin_override" value="1">
                                <span class="form-check-label">Admin override criteria validation</span>
                            </label>
                        <?php endif; ?>
                    </form>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
