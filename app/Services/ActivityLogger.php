<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Core\Auth;

final class ActivityLogger
{
    public function log(string $action, ?string $description = null, array $context = []): void
    {
        App::instance()->database->statement(
            'insert into activity_logs (actor_user_id, client_id, audit_id, certificate_id, action, description, metadata_json, ip_address, user_agent, created_at)
             values (?, ?, ?, ?, ?, ?, ?, ?, ?, now())',
            [
                Auth::id(),
                $context['client_id'] ?? null,
                $context['audit_id'] ?? null,
                $context['certificate_id'] ?? null,
                $action,
                $description,
                $context ? json_encode($context, JSON_THROW_ON_ERROR) : null,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null,
            ]
        );
    }
}
