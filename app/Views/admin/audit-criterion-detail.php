<div class="detail-shell">
    <div class="row row-cards">
        <div class="col-lg-8">
            <form id="criterion-form" class="card" method="post" action="/admin/audit-criteria/<?= e($criterion['id'] ?? 'new') ?>">
                <div class="card-header"><h3 class="card-title">Audit criterion</h3></div>
                <div class="card-body">
                    <?= csrf_field() ?>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Certification</label><select class="form-select" name="certification_type_id"><?php foreach ($types as $type): ?><option value="<?= e($type['id']) ?>"<?= selected($criterion['certification_type_id'] ?? '', $type['id']) ?>><?= e($type['name']) ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-3"><label class="form-label">Version</label><input class="form-control" type="number" name="version" value="<?= e($criterion['version'] ?? 1) ?>"></div>
                        <div class="col-md-3"><label class="form-label">Sort order</label><input class="form-control" type="number" name="sort_order" value="<?= e($criterion['sort_order'] ?? 0) ?>"></div>
                        <div class="col-md-6"><label class="form-label">Section</label><input class="form-control" name="section" value="<?= e($criterion['section'] ?? '') ?>"></div>
                        <div class="col-md-6"><label class="form-label">Reference</label><input class="form-control" name="reference" value="<?= e($criterion['reference'] ?? '') ?>"></div>
                        <div class="col-12"><label class="form-label">Title</label><input class="form-control" name="title" value="<?= e($criterion['title'] ?? '') ?>" required></div>
                        <div class="col-12"><label class="form-label">Requirement</label><textarea class="form-control" name="requirement_text" rows="5" required><?= e($criterion['requirement_text'] ?? '') ?></textarea></div>
                        <div class="col-md-6"><label class="form-label">Guidance</label><textarea class="form-control" name="guidance_text" rows="5"><?= e($criterion['guidance_text'] ?? '') ?></textarea></div>
                        <div class="col-md-6"><label class="form-label">Evidence prompt</label><textarea class="form-control" name="evidence_prompt" rows="5"><?= e($criterion['evidence_prompt'] ?? '') ?></textarea></div>
                    </div>
                </div>
                <div class="card-footer text-end"><button class="btn btn-primary" type="submit"><i class="ti ti-device-floppy me-1"></i>Save criterion</button></div>
            </form>
        </div>
        <div class="col-lg-4">
            <div class="detail-aside">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Publishing</h3></div>
                    <div class="card-body">
                        <label class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" form="criterion-form" name="is_required" value="1" <?= ($criterion['is_required'] ?? 1) ? 'checked' : '' ?>>
                            <span class="form-check-label">Required criterion</span>
                        </label>
                        <label class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" form="criterion-form" name="is_active" value="1" <?= ($criterion['is_active'] ?? 1) ? 'checked' : '' ?>>
                            <span class="form-check-label">Active in portal</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
