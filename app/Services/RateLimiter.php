<?php

declare(strict_types=1);

namespace App\Services;

final class RateLimiter
{
    public function tooManyAttempts(string $key, int $maxAttempts, int $decaySeconds): bool
    {
        $bucket = $_SESSION['_rate_limits'][$key] ?? ['count' => 0, 'reset_at' => time() + $decaySeconds];
        if ($bucket['reset_at'] <= time()) {
            $bucket = ['count' => 0, 'reset_at' => time() + $decaySeconds];
        }

        return $bucket['count'] >= $maxAttempts;
    }

    public function hit(string $key, int $decaySeconds): void
    {
        $bucket = $_SESSION['_rate_limits'][$key] ?? ['count' => 0, 'reset_at' => time() + $decaySeconds];
        if ($bucket['reset_at'] <= time()) {
            $bucket = ['count' => 0, 'reset_at' => time() + $decaySeconds];
        }
        $bucket['count']++;
        $_SESSION['_rate_limits'][$key] = $bucket;
    }

    public function clear(string $key): void
    {
        unset($_SESSION['_rate_limits'][$key]);
    }
}
