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
}

