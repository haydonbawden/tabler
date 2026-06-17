<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Access;

final class RoleMiddleware
{
    public function __construct(private readonly string $roles)
    {
    }

    public function handle(): void
    {
        Access::requireRole($this->roles);
    }
}
