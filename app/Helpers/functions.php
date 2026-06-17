<?php

declare(strict_types=1);

use App\Core\App;
use App\Core\Csrf;

function app(?string $key = null): mixed
{
    $app = App::instance();
    if ($key === null) {
        return $app;
    }

    return match ($key) {
        'config' => $app->config,
        'db' => $app->database,
        'router' => $app->router,
        'root' => $app->rootPath,
        default => null,
    };
}

function env(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? getenv($key);
    return $value === false || $value === null ? $default : $value;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    $base = (string) app('config')->get('app.url', '');
    return $base . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . $path, true, 302);
    exit;
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(Csrf::token()) . '">';
}

function selected(mixed $actual, mixed $expected): string
{
    return (string) $actual === (string) $expected ? ' selected' : '';
}
