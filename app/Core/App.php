<?php

declare(strict_types=1);

namespace App\Core;

final class App
{
    private static ?self $instance = null;

    public function __construct(
        public readonly string $rootPath,
        public readonly Config $config,
        public readonly Database $database,
        public readonly Router $router,
    ) {
        self::$instance = $this;
    }

    public static function instance(): self
    {
        if (!self::$instance) {
            throw new \RuntimeException('Application has not been bootstrapped.');
        }

        return self::$instance;
    }

    public function run(): void
    {
        $this->router->dispatch(
            $_SERVER['REQUEST_METHOD'] ?? 'GET',
            parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'
        );
    }
}
