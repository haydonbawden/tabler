<form class="card card-md" method="post" action="/login" autocomplete="off" novalidate>
    <div class="card-body">
        <h2 class="h2 text-center mb-4">Login to your account</h2>
        <?= csrf_field() ?>
        <div class="mb-3">
            <label class="form-label">Email address</label>
            <input class="form-control" type="email" name="email" placeholder="your@email.com" autocomplete="off" required>
        </div>
        <div class="mb-2">
            <label class="form-label">
                Password
                <span class="form-label-description"><a href="/forgot-password">I forgot password</a></span>
            </label>
            <div class="input-group input-group-flat">
                <input class="form-control" type="password" name="password" placeholder="Your password" autocomplete="off" required>
                <span class="input-group-text"><i class="ti ti-eye"></i></span>
            </div>
        </div>
        <div class="mb-2">
            <label class="form-check">
                <input class="form-check-input" type="checkbox" name="remember" value="1">
                <span class="form-check-label">Remember me on this device</span>
            </label>
        </div>
        <div class="form-footer">
            <button class="btn btn-primary w-100" type="submit">Sign in</button>
        </div>
    </div>
</form>
<div class="text-center text-secondary mt-3">
    Don't have account yet? <a href="/register" tabindex="-1">Sign up</a>
</div>
