<?php

namespace App\Support;

class InputSecuritySanitizer
{
    /**
     * @param array<string,mixed> $payload
     * @return array{data: array<string,mixed>, blocked: bool, reason: string|null, threats: array<int,string>}
     */
    public static function sanitize(array $payload): array
    {
        $threats = [];
        $blocked = false;
        $reason = null;

        $walk = function (mixed $value, string $path = '') use (&$walk, &$threats, &$blocked, &$reason): mixed {
            if (is_array($value)) {
                $result = [];
                foreach ($value as $key => $item) {
                    $childPath = $path === '' ? (string) $key : $path.'.'.$key;
                    $result[$key] = $walk($item, $childPath);
                }

                return $result;
            }

            if (! is_string($value)) {
                return $value;
            }

            $raw = str_replace("\0", '', $value);

            $patterns = [
                'script_tag' => '/<\s*\/?\s*script\b/i',
                'php_tag' => '/<\?(php|=)?/i',
                'event_handler' => '/\bon\w+\s*=/i',
                'js_protocol' => '/javascript\s*:/i',
                'danger_tag' => '/<\s*\/?\s*(iframe|object|embed|meta|link)\b/i',
                'code_exec' => '/\b(eval|exec|shell_exec|system|passthru|base64_decode)\s*\(/i',
            ];

            foreach ($patterns as $label => $pattern) {
                if (preg_match($pattern, $raw) === 1) {
                    $threats[] = ($path !== '' ? $path : 'field').':'.$label;
                    $blocked = true;
                }
            }

            if ($blocked && $reason === null) {
                $reason = 'Padrao de codigo potencialmente malicioso detectado.';
            }

            $clean = preg_replace('/<\s*(script|style|iframe|object|embed|meta|link)\b[^>]*>.*?<\s*\/\s*\1\s*>/is', '', $raw) ?? $raw;
            $clean = preg_replace('/<\s*(script|style|iframe|object|embed|meta|link)\b[^>]*\/?>/is', '', $clean) ?? $clean;
            $clean = preg_replace('/\son\w+\s*=\s*([\'"]).*?\1/iu', '', $clean) ?? $clean;
            $clean = preg_replace('/\son\w+\s*=\s*[^ >]+/iu', '', $clean) ?? $clean;
            $clean = preg_replace('/javascript\s*:/iu', '', $clean) ?? $clean;
            $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $clean) ?? $clean;

            return trim($clean);
        };

        return [
            'data' => $walk($payload),
            'blocked' => $blocked,
            'reason' => $reason,
            'threats' => array_values(array_unique($threats)),
        ];
    }
}

