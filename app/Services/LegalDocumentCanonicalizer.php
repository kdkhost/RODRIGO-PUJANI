<?php

namespace App\Services;

class LegalDocumentCanonicalizer
{
    public function json(array $value): string
    {
        return json_encode(
            $this->sort($value),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );
    }

    private function sort(array $value): array
    {
        if (array_is_list($value)) {
            return array_map(fn (mixed $item): mixed => is_array($item) ? $this->sort($item) : $item, $value);
        }

        ksort($value, SORT_STRING);
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->sort($item);
            }
        }

        return $value;
    }
}
