<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $view, array $data = [], ?string $layout = 'layouts/app'): string
    {
        $root = App::instance()->rootPath;
        $viewFile = $root . '/app/Views/' . $view . '.php';
        if (!is_file($viewFile)) {
            throw new \RuntimeException("View [$view] not found.");
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        if ($layout === null) {
            return $content;
        }

        $layoutFile = $root . '/app/Views/' . $layout . '.php';
        if (!is_file($layoutFile)) {
            throw new \RuntimeException("Layout [$layout] not found.");
        }

        ob_start();
        require $layoutFile;
        return ob_get_clean();
    }
}
