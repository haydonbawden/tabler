<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'Castor Portal'),
    'env' => env('APP_ENV', 'production'),
    'debug' => filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN),
    'url' => rtrim((string) env('APP_URL', 'http://localhost:8000'), '/'),
    'timezone' => env('APP_TIMEZONE', 'Australia/Brisbane'),
    'storage_path' => env('STORAGE_PATH', 'storage'),
    'session_secure' => filter_var(env('SESSION_SECURE', false), FILTER_VALIDATE_BOOLEAN),
    'castor' => [
        'phone' => '1300 070 915',
        'email' => 'admin@castoraustralia.com.au',
        'website' => 'castoraustralia.com.au',
        'signatory_name' => 'Haydon Bawden',
        'signatory_title' => 'Lead Auditor',
        'entity' => 'Castor Audit & Advisory',
        'division' => 'A division of Castor Management Australia Pty Ltd',
        'abn' => '50 638 775 381',
    ],
];
