<div class="card-footer d-flex justify-content-between align-items-center">
    <a href="<?= e($cancelHref ?? 'javascript:history.back()') ?>" class="btn btn-link">Cancel</a>
    <div class="btn-list">
        <?php if (!empty($continueName)): ?><button class="btn btn-outline-primary" type="submit" name="<?= e($continueName) ?>" value="1">Save and continue</button><?php endif; ?>
        <button class="btn btn-primary" type="submit"><i class="ti ti-device-floppy me-1"></i><?= e($saveLabel ?? 'Save') ?></button>
    </div>
</div>
