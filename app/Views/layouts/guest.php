<?php use App\Core\Session; ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title><?= e($title ?? 'Castor Portal') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/css/tabler.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.34.1/dist/tabler-icons.min.css">
    <link rel="stylesheet" href="/assets/app.css">
</head>
<body class="d-flex flex-column bg-light">
<div class="page page-center auth-shell">
    <div class="container container-tight py-4">
        <div class="text-center mb-4">
            <a href="/login" class="navbar-brand navbar-brand-autodark justify-content-center">
                <img src="/assets/caa-logo-landscape.png" width="192" height="64" alt="Castor Audit & Advisory" class="auth-brand">
            </a>
        </div>
        <?php if ($message = Session::flash('success')): ?>
            <div class="alert alert-success" role="alert"><i class="ti ti-circle-check me-2"></i><?= e($message) ?></div>
        <?php endif; ?>
        <?php if ($message = Session::flash('error')): ?>
            <div class="alert alert-danger" role="alert"><i class="ti ti-alert-circle me-2"></i><?= e($message) ?></div>
        <?php endif; ?>
        <?= $content ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/js/tabler.min.js"></script>
</body>
</html>
