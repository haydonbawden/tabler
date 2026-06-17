<?php

declare(strict_types=1);

namespace App\Services;

final class EmailTemplateService
{
    public function render(string $template, array $fields): string
    {
        $replacements = [];
        foreach ($fields as $key => $value) {
            $replacements['{{' . $key . '}}'] = (string) $value;
        }

        return strtr($template, $replacements);
    }
}
