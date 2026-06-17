<?php
use App\Core\Auth;
use App\Core\Session;
use App\Services\SettingsService;

$user = Auth::user();
$role = $user['role'] ?? 'guest';
$roleLabel = ucwords(str_replace('_', ' ', $role));
$portalLabel = match ($role) {
    'client' => 'CLIENT PORTAL',
    'auditor' => 'AUDITOR PORTAL',
    default => 'ADMIN PORTAL',
};
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$initials = strtoupper(substr((string) ($user['name'] ?? 'CA'), 0, 1));
$canCreateAdminRecords = in_array($role, ['admin', 'super_admin'], true);
$settingsService = new SettingsService();
$theme = $settingsService->userTheme();
$settings = $settingsService->all();
$homeUrl = $role === 'client' ? '/client/profile' : ($role === 'auditor' ? '/auditor/audits' : '/admin/clients');
$searchUrl = $role === 'client' ? '/client/search' : ($role === 'auditor' ? '/auditor/search' : '/admin/search');
$documentTitle = is_array($pageHeader ?? null) && ($pageHeader['title'] ?? '') !== '' ? (string) $pageHeader['title'] : (string) ($title ?? 'Castor CRM');

$navigation = match ($role) {
    'auditor' => [
        ['Audits', '/auditor/audits', 'ti-clipboard-check'],
        ['Evidence Review', '/auditor/audits?view=submitted', 'ti-file-search'],
    ],
    'client' => [
        ['Organisation', '/client/profile', 'ti-building'],
        ['Contacts', '/client/contacts', 'ti-address-book'],
        ['Audits', '/client/audits', 'ti-clipboard-list'],
        ['Certificates', '/client/certificates', 'ti-certificate'],
    ],
    default => [
        ['CRM', null, 'ti-building-community', [
            ['Clients', '/admin/clients', 'ti-building-community'],
            ['Contacts', '/admin/contacts', 'ti-address-book'],
        ]],
        ['Audits', null, 'ti-clipboard-list', [
            ['Audits', '/admin/audits', 'ti-clipboard-list'],
            ['Evidence Review', '/admin/audits?view=submitted', 'ti-file-search'],
        ]],
        ['Certificates', null, 'ti-certificate', [
            ['Certificates', '/admin/certificates', 'ti-certificate'],
            ['Renewals', '/admin/certificates?view=expiring_90', 'ti-refresh'],
            ['Public Verification', '/verify-certificate', 'ti-shield-check'],
        ]],
        ['Logs', null, 'ti-history', [
            ['Payments', '/admin/payments', 'ti-credit-card'],
            ['Email Log', '/admin/email-logs', 'ti-mail-cog'],
            ['Activity Log', '/admin/activity-logs', 'ti-history'],
        ]],
        ['Administration', null, 'ti-settings', [
            ['Settings', '/admin/settings', 'ti-settings'],
            ['Users & Permissions', '/admin/settings#settings-users', 'ti-users'],
            ['Audit Criteria', '/admin/audit-criteria', 'ti-list-check'],
            ['Templates', '/admin/email-templates', 'ti-template'],
            ['Reminder Rules', '/admin/reminder-rules', 'ti-bell-ringing'],
            ['Imports', '/admin/imports', 'ti-database-import'],
        ]],
    ],
};

$isActive = static function (?string $href, array $children = []) use ($requestPath): bool {
    if ($href) {
        $path = parse_url($href, PHP_URL_PATH) ?: $href;
        if ($requestPath === $path || str_starts_with($requestPath, rtrim($path, '/') . '/')) {
            return true;
        }
    }
    foreach ($children as $child) {
        $childHref = $child[1] ?? null;
        $path = $childHref ? (parse_url($childHref, PHP_URL_PATH) ?: $childHref) : null;
        if ($path && ($requestPath === $path || str_starts_with($requestPath, rtrim($path, '/') . '/'))) {
            return true;
        }
    }
    return false;
};
?>
<!doctype html>
<html lang="en" data-bs-theme="<?= e($theme) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="<?= e($theme) ?>">
    <meta name="csrf-token" content="<?= e(\App\Core\Csrf::token()) ?>">
    <title><?= e($documentTitle) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/css/tabler.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.34.1/dist/tabler-icons.min.css">
    <link rel="stylesheet" href="/assets/app.css">
</head>
<body class="castor-condensed">
<div class="page">
    <header class="navbar navbar-expand-md d-print-none castor-topbar">
        <div class="container-xl">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu" aria-controls="navbar-menu" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <h1 class="navbar-brand navbar-brand-autodark me-md-3">
                <a href="<?= e($homeUrl) ?>" aria-label="Castor CRM">
                    <?php if (($settings['show_logo'] ?? '1') === '1'): ?>
                        <img src="<?= e($settings['logo_url'] ?? '/assets/caa-logo-landscape.png') ?>" width="126" height="36" alt="Castor Audit & Advisory" class="navbar-brand-image castor-fluid-logo">
                    <?php else: ?>
                        Castor CRM
                    <?php endif; ?>
                </a>
            </h1>
            <div class="d-none d-lg-block flex-fill me-3">
                <?php include app('root') . '/app/Views/components/global-search.php'; ?>
            </div>
            <div class="navbar-nav flex-row order-md-last ms-auto align-items-center">
                <?php if ($canCreateAdminRecords): ?>
                    <div class="nav-item d-none d-md-flex me-2">
                        <a href="/admin/audits/new" class="btn btn-primary btn-sm"><i class="ti ti-plus me-1"></i>New audit</a>
                    </div>
                <?php endif; ?>
                <div class="nav-item dropdown">
                    <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-label="Open user menu">
                        <span class="avatar avatar-sm bg-primary-lt"><?= e($initials) ?></span>
                        <div class="d-none d-xl-block ps-2">
                            <div><?= e($user['name'] ?? '') ?></div>
                            <div class="small text-secondary"><?= e($roleLabel) ?></div>
                        </div>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                        <div class="dropdown-header">
                            <div class="fw-medium"><?= e($user['email'] ?? '') ?></div>
                            <div class="text-secondary"><?= e($roleLabel) ?></div>
                        </div>
                        <a class="dropdown-item" href="<?= e($homeUrl) ?>"><i class="ti ti-home me-2"></i>Workspace</a>
                        <a class="dropdown-item" href="/admin/settings"><i class="ti ti-moon me-2"></i><?= $theme === 'dark' ? 'Dark' : 'Light' ?> theme</a>
                        <form method="post" action="/logout" class="m-0">
                            <?= csrf_field() ?>
                            <button class="dropdown-item" type="submit"><i class="ti ti-logout me-2"></i>Logout</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <header class="navbar-expand-md d-print-none castor-nav">
        <div class="collapse navbar-collapse" id="navbar-menu">
            <div class="navbar">
                <div class="container-xl">
                    <div class="d-lg-none w-100 my-2">
                        <?php include app('root') . '/app/Views/components/global-search.php'; ?>
                    </div>
                    <ul class="navbar-nav">
                        <?php foreach ($navigation as $item): ?>
                            <?php [$label, $href, $icon] = $item; $children = $item[3] ?? []; $active = $isActive($href, $children); ?>
                            <?php if ($children): ?>
                                <li class="nav-item dropdown<?= $active ? ' active' : '' ?>">
                                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" role="button" aria-expanded="<?= $active ? 'true' : 'false' ?>">
                                        <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti <?= e($icon) ?>"></i></span>
                                        <span class="nav-link-title"><?= e($label) ?></span>
                                    </a>
                                    <div class="dropdown-menu">
                                        <?php foreach ($children as [$childLabel, $childHref, $childIcon]): ?>
                                            <a class="dropdown-item<?= $isActive($childHref) ? ' active' : '' ?>" href="<?= e($childHref) ?>">
                                                <i class="ti <?= e($childIcon) ?> me-2"></i><?= e($childLabel) ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </li>
                            <?php else: ?>
                                <li class="nav-item<?= $active ? ' active' : '' ?>">
                                    <a class="nav-link" href="<?= e($href ?? '#') ?>">
                                        <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti <?= e($icon) ?>"></i></span>
                                        <span class="nav-link-title"><?= e($label) ?></span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </header>

    <div class="page-wrapper">
        <?php
        $headerData = $pageHeader ?? [
            'eyebrow' => $portalLabel,
            'title' => $title ?? '',
            'subtitle' => $pageSubtitle ?? '',
            'breadcrumbs' => $breadcrumbs ?? [],
            'primaryAction' => $primaryAction ?? null,
            'secondaryActions' => $secondaryActions ?? [],
            'status' => $pageStatus ?? null,
        ];
        extract($headerData, EXTR_SKIP);
        include app('root') . '/app/Views/components/page-header.php';
        ?>
        <main id="content" class="page-body">
            <div class="container-xl">
                <?php if ($message = Session::flash('success')): ?>
                    <div class="alert alert-success alert-dismissible" role="alert">
                        <div class="d-flex"><div><i class="ti ti-circle-check me-2"></i></div><div><?= e($message) ?></div></div>
                        <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
                    </div>
                <?php endif; ?>
                <?php if ($message = Session::flash('error')): ?>
                    <div class="alert alert-danger alert-dismissible" role="alert">
                        <div class="d-flex"><div><i class="ti ti-alert-circle me-2"></i></div><div><?= e($message) ?></div></div>
                        <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
                    </div>
                <?php endif; ?>
                <?= $content ?>
            </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/js/tabler.min.js"></script>
<script src="/assets/app.js"></script>
</body>
</html>
