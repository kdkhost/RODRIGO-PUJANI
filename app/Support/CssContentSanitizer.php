<?php

namespace App\Support;

class CssContentSanitizer
{
    public function sanitize(?string $css): string
    {
        $css = trim((string) $css);

        if ($css === '' || preg_match('/[<>]|@import|expression\s*\(|javascript\s*:|data\s*:|url\s*\(|behavior\s*:|-moz-binding/iu', $css) === 1) {
            return '';
        }

        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $css) ?? '';
    }
}
