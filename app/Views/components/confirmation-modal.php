<?php $id = $id ?? 'confirmation-modal'; ?>
<div class="modal modal-blur fade" id="<?= e($id) ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><?= e($title ?? 'Confirm action') ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body"><?= e($body ?? 'This action cannot be undone.') ?></div>
            <div class="modal-footer"><button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-danger"><?= e($confirmLabel ?? 'Confirm') ?></button></div>
        </div>
    </div>
</div>
