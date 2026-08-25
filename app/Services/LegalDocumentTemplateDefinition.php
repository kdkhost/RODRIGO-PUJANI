<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class LegalDocumentTemplateDefinition
{
    private const BLOCK_TYPES = ['heading', 'paragraph', 'list', 'page_break', 'spacer'];

    public function normalize(array $definition): array
    {
        $blocks = $definition['blocks'] ?? null;

        if (! is_array($blocks) || $blocks === [] || count($blocks) > 200) {
            $this->fail('A definição deve conter entre 1 e 200 blocos estruturados.');
        }

        return [
            'blocks' => collect($blocks)
                ->values()
                ->map(fn (mixed $block, int $index): array => $this->normalizeBlock($block, $index))
                ->all(),
        ];
    }

    private function normalizeBlock(mixed $block, int $index): array
    {
        if (! is_array($block)) {
            $this->fail("O bloco ".($index + 1).' deve ser um objeto.');
        }

        $type = strtolower(trim((string) ($block['type'] ?? '')));
        if (! in_array($type, self::BLOCK_TYPES, true)) {
            $this->fail("O bloco ".($index + 1).' possui um tipo não permitido.');
        }

        if ($type === 'page_break') {
            return ['type' => $type];
        }

        if ($type === 'spacer') {
            return [
                'type' => $type,
                'lines' => max(1, min(5, (int) ($block['lines'] ?? 1))),
            ];
        }

        if ($type === 'list') {
            $items = $block['items'] ?? null;
            if (! is_array($items) || $items === [] || count($items) > 100) {
                $this->fail("A lista do bloco ".($index + 1).' deve conter entre 1 e 100 itens.');
            }

            $normalizedItems = collect($items)
                ->map(function (mixed $item) use ($index): string {
                    $text = $this->text($item, 4000);
                    if ($text === '') {
                        $this->fail("A lista do bloco ".($index + 1).' contém um item vazio.');
                    }

                    return $text;
                })
                ->values()
                ->all();

            return [
                'type' => $type,
                'ordered' => filter_var($block['ordered'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'items' => $normalizedItems,
            ];
        }

        $text = $this->text($block['text'] ?? null, $type === 'heading' ? 1000 : 20000);
        if ($text === '') {
            $this->fail("O bloco ".($index + 1).' precisa de conteúdo textual.');
        }

        $normalized = ['type' => $type, 'text' => $text];
        if ($type === 'heading') {
            $normalized['level'] = max(1, min(3, (int) ($block['level'] ?? 1)));
        }

        return $normalized;
    }

    private function text(mixed $value, int $maxLength): string
    {
        if (! is_scalar($value) && $value !== null) {
            return '';
        }

        $text = str_replace("\0", '', trim((string) $value));
        if (mb_strlen($text) > $maxLength) {
            $this->fail("Um conteúdo do template excedeu {$maxLength} caracteres.");
        }

        return $text;
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['definition_json' => $message]);
    }
}
