<form class="card card-md" method="post" action="/reset-password" autocomplete="off" novalidate>
    <div class="card-body">
        <h2 class="h2 text-center mb-4">Reset password</h2>
        <?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= e($token ?? '') ?>">
        <div class="mb-3">
            <label class="form-label">New password</label>
            <div class="input-group input-group-flat">
                <input class="form-control" type="password" name="password" placeholder="Your new password" autocomplete="off" required>
                <span class="input-group-text"><i class="ti ti-eye"></i></span>
            </div>
            <div class="form-hint">Use at least 10 characters with upper, lower, number and symbol.</div>
        </div>
        <div class="form-footer">
            <button class="btn btn-primary w-100" type="submit">Reset password</button>
        </div>
    </div>
</form>
<div class="text-center text-secondary mt-3">
    <a href="/login" tabindex="-1">Back to sign in</a>
</div>
