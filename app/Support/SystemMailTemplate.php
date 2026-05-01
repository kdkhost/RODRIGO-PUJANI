<?php

namespace App\Support;

class SystemMailTemplate
{
    public static function compile(string $template, array $variables = []): string
    {
        $compiled = $template;

        foreach ($variables as $key => $value) {
            $compiled = str_replace('{{'.$key.'}}', (string) $value, $compiled);
        }

        return preg_replace('/{{\s*[^}]+\s*}}/', '', $compiled) ?? $compiled;
    }

    public static function renderMarkup(string $template, array $variables = []): string
    {
        $compiled = trim(self::compile($template, $variables));

        if ($compiled === '') {
            return '';
        }

        if (! preg_match('/<[^>]+>/', $compiled)) {
            return nl2br(htmlspecialchars($compiled, ENT_QUOTES, 'UTF-8'));
        }

        return $compiled;
    }
}
