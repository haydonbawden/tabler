<form class="card card-md" method="post" action="/forgot-password" autocomplete="off" novalidate>
    <div class="card-body">
        <h2 class="h2 text-center mb-4">Forgot password?</h2>
        <p class="text-secondary text-center mb-4">Enter your email address and we will create a reset link if the account exists.</p>
        <?= csrf_field() ?>
        <div class="mb-3">
            <label class="form-label">Email address</label>
            <input class="form-control" type="email" name="email" placeholder="your@email.com" autocomplete="off" required>
        </div>
        <div class="form-footer">
            <button class="btn btn-primary w-100" type="submit">Create reset token</button>
        </div>
    </div>
</form>
<div class="text-center text-secondary mt-3">
    Remembered it? <a href="/login" tabindex="-1">Back to sign in</a>
</div>
