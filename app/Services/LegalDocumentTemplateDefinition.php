<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class LegalDocumentTemplateDefinition
{
    private const BLOCK_TYPES = ['heading', 'paragraph', 'list', 'page_break', 'spacer'];

    private const VISUAL_ELEMENT_TYPES = ['text', 'signature', 'line', 'rectangle', 'image'];

    public function normalize(array $definition): array
    {
        if (($definition['layout'] ?? null) === 'absolute') {
            return $this->normalizeAbsolute($definition);
        }

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

    private function normalizeAbsolute(array $definition): array
    {
        $pages = $definition['pages'] ?? null;

        if (! is_array($pages) || $pages === [] || count($pages) > 20) {
            $this->fail('O editor visual deve conter entre 1 e 20 páginas.');
        }

        return [
            'layout' => 'absolute',
            'unit' => 'mm',
            'paper' => [
                'size' => 'A4',
                'width_mm' => 210,
                'height_mm' => 297,
            ],
            'pages' => collect($pages)
                ->values()
                ->map(fn (mixed $page, int $index): array => $this->normalizePage($page, $index))
                ->all(),
        ];
    }

    private function normalizePage(mixed $page, int $index): array
    {
        if (! is_array($page)) {
            $this->fail('A página '.($index + 1).' deve ser um objeto.');
        }

        $elements = $page['elements'] ?? null;
        if (! is_array($elements) || count($elements) > 120) {
            $this->fail('A página '.($index + 1).' deve conter até 120 elementos.');
        }

        return [
            'width_mm' => $this->number($page['width_mm'] ?? 210, 100, 400),
            'height_mm' => $this->number($page['height_mm'] ?? 297, 100, 500),
            'background' => $this->normalizeBackground($page['background'] ?? []),
            'elements' => collect($elements)
                ->values()
                ->map(fn (mixed $element, int $elementIndex): array => $this->normalizeVisualElement($element, $index, $elementIndex))
                ->all(),
        ];
    }

    private function normalizeBackground(mixed $background): array
    {
        $background = is_array($background) ? $background : [];

        return [
            'color' => $this->color((string) ($background['color'] ?? '#ffffff'), '#ffffff'),
            'image_path' => $this->path($background['image_path'] ?? null),
            'image_opacity' => $this->number($background['image_opacity'] ?? 0.08, 0, 1),
            'image_fit' => in_array(($background['image_fit'] ?? 'cover'), ['contain', 'cover', 'stretch'], true)
                ? $background['image_fit']
                : 'cover',
        ];
    }

    private function normalizeVisualElement(mixed $element, int $pageIndex, int $elementIndex): array
    {
        if (! is_array($element)) {
            $this->fail('O elemento '.($elementIndex + 1).' da página '.($pageIndex + 1).' deve ser um objeto.');
        }

        $type = strtolower(trim((string) ($element['type'] ?? '')));
        if (! in_array($type, self::VISUAL_ELEMENT_TYPES, true)) {
            $this->fail('O elemento '.($elementIndex + 1).' da página '.($pageIndex + 1).' possui um tipo não permitido.');
        }

        $base = [
            'id' => $this->identifier((string) ($element['id'] ?? 'el-'.($pageIndex + 1).'-'.($elementIndex + 1))),
            'type' => $type,
            'x_mm' => $this->number($element['x_mm'] ?? 20, 0, 400),
            'y_mm' => $this->number($element['y_mm'] ?? 20, 0, 500),
            'w_mm' => $this->number($element['w_mm'] ?? 60, 1, 400),
            'h_mm' => $this->number($element['h_mm'] ?? 12, 1, 500),
            'opacity' => $this->number($element['opacity'] ?? 1, 0, 1),
        ];

        return match ($type) {
            'text' => $base + [
                'text' => $this->text($element['text'] ?? '', 20000),
                'font_size_pt' => $this->number($element['font_size_pt'] ?? 11, 5, 72),
                'font_family' => $this->fontFamily($element['font_family'] ?? 'DejaVu Sans'),
                'font_weight' => in_array((string) ($element['font_weight'] ?? '400'), ['400', '600', '700'], true) ? (string) $element['font_weight'] : '400',
                'line_height' => $this->number($element['line_height'] ?? 1.35, 1, 2.5),
                'align' => in_array(($element['align'] ?? 'left'), ['left', 'center', 'right', 'justify'], true) ? $element['align'] : 'left',
                'color' => $this->color((string) ($element['color'] ?? '#111827'), '#111827'),
            ],
            'signature' => $base + [
                'label' => $this->text($element['label'] ?? 'Assinatura', 255),
                'signer_order' => max(1, min(20, (int) ($element['signer_order'] ?? 1))),
                'required' => filter_var($element['required'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'border_color' => $this->color((string) ($element['border_color'] ?? '#111827'), '#111827'),
            ],
            'line' => $base + [
                'color' => $this->color((string) ($element['color'] ?? '#111827'), '#111827'),
                'thickness_mm' => $this->number($element['thickness_mm'] ?? 0.25, 0.1, 5),
            ],
            'rectangle' => $base + [
                'border_color' => $this->color((string) ($element['border_color'] ?? '#111827'), '#111827'),
                'background_color' => $this->color((string) ($element['background_color'] ?? 'transparent'), 'transparent'),
                'border_width_mm' => $this->number($element['border_width_mm'] ?? 0.25, 0, 5),
            ],
            'image' => $base + [
                'image_path' => $this->path($element['image_path'] ?? null),
                'fit' => in_array(($element['fit'] ?? 'contain'), ['contain', 'cover', 'stretch'], true) ? $element['fit'] : 'contain',
            ],
        };
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

    private function number(mixed $value, float $min, float $max): float
    {
        $number = is_numeric($value) ? (float) $value : $min;

        return round(max($min, min($max, $number)), 3);
    }

    private function color(string $value, string $fallback): string
    {
        $value = trim($value);
        if ($value === 'transparent') {
            return $value;
        }

        return preg_match('/^#[0-9a-f]{6}$/i', $value) === 1 ? strtolower($value) : $fallback;
    }

    private function path(mixed $value): ?string
    {
        $path = trim(str_replace("\0", '', (string) $value));
        if ($path === '') {
            return null;
        }

        if (str_contains($path, '..') || str_starts_with($path, '/') || preg_match('/^[a-z]+:/i', $path)) {
            $this->fail('Caminho de imagem inválido no editor visual.');
        }

        return mb_substr($path, 0, 500);
    }

    private function identifier(string $value): string
    {
        $id = preg_replace('/[^a-z0-9\-_]+/i', '-', $value) ?: '';

        return trim($id, '-') ?: 'elemento';
    }

    private function fontFamily(mixed $value): string
    {
        $font = trim((string) $value);

        return in_array($font, ['DejaVu Sans', 'Helvetica', 'Times', 'Courier'], true) ? $font : 'DejaVu Sans';
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['definition_json' => $message]);
    }
}
