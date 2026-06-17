<form class="card card-md" method="post" action="/verify-certificate">
    <div class="card-body">
        <h2 class="h2 text-center mb-4">Verify certificate</h2>
        <?= csrf_field() ?>
        <div class="mb-3"><label class="form-label">Certificate number</label><input class="form-control" name="certificate_number" required></div>
        <button class="btn btn-primary w-100">Verify</button>
        <?php if (!empty($searched)): ?>
            <hr>
            <?php if (!empty($certificate)): ?>
                <dl class="row">
                    <dt class="col-5">Certificate</dt><dd class="col-7"><?= e($certificate['certificate_number']) ?></dd>
                    <dt class="col-5">Client</dt><dd class="col-7"><?= e($certificate['legal_name']) ?></dd>
                    <dt class="col-5">Certification</dt><dd class="col-7"><?= e($certificate['certification']) ?></dd>
                    <dt class="col-5">Issue date</dt><dd class="col-7"><?= e($certificate['issue_date']) ?></dd>
                    <dt class="col-5">Expiry date</dt><dd class="col-7"><?= e($certificate['expiry_date']) ?></dd>
                    <dt class="col-5">Status</dt><dd class="col-7"><?= e($certificate['status']) ?></dd>
                    <dt class="col-5">Validated</dt><dd class="col-7"><?= e($timestamp ?? '') ?></dd>
                </dl>
            <?php else: ?>
                <div class="alert alert-warning">No matching certificate was found.</div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</form>
