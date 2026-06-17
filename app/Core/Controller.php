<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function view(string $view, array $data = [], ?string $layout = 'layouts/app'): void
    {
        echo View::render($view, $data, $layout);
    }

    protected function pageHeader(string $eyebrow, string $title, string $subtitle = '', array $options = []): array
    {
        return [
            'pageHeader' => [
                'eyebrow' => $eyebrow,
                'title' => $title,
                'subtitle' => $subtitle,
                'breadcrumbs' => $options['breadcrumbs'] ?? [],
                'primaryAction' => $options['primaryAction'] ?? null,
                'secondaryActions' => $options['secondaryActions'] ?? [],
                'status' => $options['status'] ?? null,
            ],
        ];
    }

    protected function db(): Database
    {
        return App::instance()->database;
    }

    protected function config(): Config
    {
        return App::instance()->config;
    }

    protected function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }
}
