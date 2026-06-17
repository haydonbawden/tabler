<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Csrf;
use App\Core\Session;

final class CsrfMiddleware
{
    public function handle(): void
    {
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            Session::flash('error', 'Your session expired. Please try again.');
            redirect($_SERVER['HTTP_REFERER'] ?? '/');
        }
    }
}
