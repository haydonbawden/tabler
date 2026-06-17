<form class="card card-md" method="post" action="/register" autocomplete="off" novalidate>
    <div class="card-body">
        <h2 class="h2 text-center mb-4">Create your account</h2>
        <?= csrf_field() ?>
        <div class="mb-3">
            <label class="form-label">Name</label>
            <input class="form-control" name="name" placeholder="Your full name" autocomplete="off" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Email address</label>
            <input class="form-control" type="email" name="email" placeholder="your@email.com" autocomplete="off" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Organisation</label>
            <input class="form-control" name="client_name" placeholder="Organisation name">
        </div>
        <div class="mb-3">
            <label class="form-label">Password</label>
            <div class="input-group input-group-flat">
                <input class="form-control" type="password" name="password" placeholder="Choose a strong password" autocomplete="off" required>
                <span class="input-group-text"><i class="ti ti-eye"></i></span>
            </div>
            <div class="form-hint">Use at least 10 characters with upper, lower, number and symbol.</div>
        </div>
        <div class="form-footer">
            <button class="btn btn-primary w-100" type="submit">Create account</button>
        </div>
    </div>
</form>
<div class="text-center text-secondary mt-3">
    Already have an account? <a href="/login" tabindex="-1">Sign in</a>
</div>
