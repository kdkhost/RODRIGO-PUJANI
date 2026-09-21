<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use RuntimeException;

class LegalDocumentOutputRenderer
{
    public function __construct(private readonly DocxDocumentRenderer $docx)
    {
    }

    public function render(string $format, string $title, array $definition): string
    {
        $path = tempnam(sys_get_temp_dir(), 'pujani-legal-document-');
        if (! is_string($path)) {
            throw new RuntimeException('Não foi possível preparar o arquivo temporário do documento.');
        }

        try {
            match ($format) {
                'docx' => $this->renderDocx($path, $title, $definition),
                'pdf' => $this->renderPdf($path, $title, $definition),
                default => throw new RuntimeException('Formato de saída não suportado.'),
            };

            if (! is_file($path) || filesize($path) === 0) {
                throw new RuntimeException('O documento gerado ficou vazio.');
            }

            return $path;
        } catch (\Throwable $exception) {
            @unlink($path);
            throw $exception;
        }
    }

    private function renderDocx(string $path, string $title, array $definition): void
    {
        $this->docx->render($path, $title, $definition);
    }

    private function renderPdf(string $path, string $title, array $definition): void
    {
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);
        $options->set('isJavascriptEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->loadHtml($this->pdfHtml($title, $definition), 'UTF-8');
        $dompdf->render();

        if (file_put_contents($path, $dompdf->output()) === false) {
            throw new RuntimeException('Não foi possível salvar o PDF gerado.');
        }
    }

    private function pdfHtml(string $title, array $definition): string
    {
        if (($definition['layout'] ?? null) === 'absolute') {
            return $this->absolutePdfHtml($title, $definition);
        }

        $body = '<h1>'.$this->escape($title).'</h1>';

        foreach ($definition['blocks'] ?? [] as $block) {
            $body .= match ($block['type']) {
                'heading' => '<h'.(int) $block['level'].'>'.$this->escape((string) $block['text']).'</h'.(int) $block['level'].'>',
                'paragraph' => '<p>'.$this->escapeWithBreaks((string) $block['text']).'</p>',
                'list' => $this->pdfList($block),
                'page_break' => '<div class="page-break"></div>',
                'spacer' => '<div style="height:'.((int) $block['lines'] * 12).'pt"></div>',
                default => '',
            };
        }

        return '<!doctype html><html lang="pt-BR"><head><meta charset="UTF-8"><style>
            @page { margin: 2cm; }
            body { color: #111827; font-family: "DejaVu Sans", sans-serif; font-size: 11pt; line-height: 1.5; }
            h1 { font-size: 18pt; margin: 0 0 20pt; }
            h2 { font-size: 15pt; margin: 16pt 0 8pt; }
            h3 { font-size: 12pt; margin: 12pt 0 6pt; }
            p { margin: 0 0 10pt; text-align: justify; }
            ul, ol { margin: 0 0 10pt 18pt; padding: 0; }
            li { margin-bottom: 4pt; }
            .page-break { page-break-after: always; }
        </style></head><body>'.$body.'</body></html>';
    }

    private function absolutePdfHtml(string $title, array $definition): string
    {
        $pages = $definition['pages'] ?? [];
        $body = '';

        foreach ($pages as $pageIndex => $page) {
            $width = $this->mm($page['width_mm'] ?? 210);
            $height = $this->mm($page['height_mm'] ?? 297);
            $background = $page['background'] ?? [];
            $backgroundColor = $this->cssColor((string) ($background['color'] ?? '#ffffff'));
            $body .= '<section class="page" style="width:'.$width.'mm;height:'.$height.'mm;background:'.$backgroundColor.';" aria-label="Página '.($pageIndex + 1).'">';
            $body .= $this->absoluteBackground($background);

            foreach ($page['elements'] ?? [] as $element) {
                $body .= $this->absoluteElement($element);
            }

            $body .= '</section>';
        }

        return '<!doctype html><html lang="pt-BR"><head><meta charset="UTF-8"><title>'.$this->escape($title).'</title><style>
            @page { margin: 0; size: A4 portrait; }
            * { box-sizing: border-box; }
            body { margin: 0; padding: 0; font-family: "DejaVu Sans", sans-serif; color: #111827; }
            .page { position: relative; overflow: hidden; page-break-after: always; }
            .page:last-child { page-break-after: auto; }
            .page-bg { position: absolute; inset: 0; z-index: 0; }
            .page-bg img { width: 100%; height: 100%; display: block; }
            .fit-cover { object-fit: cover; }
            .fit-contain { object-fit: contain; }
            .fit-stretch { object-fit: fill; }
            .doc-el { position: absolute; z-index: 1; overflow: hidden; }
            .doc-text { white-space: pre-wrap; }
            .doc-signature { border-bottom-style: solid; background: rgba(255,255,255,0.01); }
            .doc-signature-label { position: absolute; left: 0; right: 0; bottom: 0; transform: translateY(100%); font-size: 8pt; text-align: center; color: #374151; }
        </style></head><body>'.$body.'</body></html>';
    }

    private function absoluteBackground(array $background): string
    {
        $imagePath = (string) ($background['image_path'] ?? '');
        if ($imagePath === '') {
            return '';
        }

        $dataUri = $this->imageDataUri($imagePath);
        if ($dataUri === null) {
            return '';
        }

        $opacity = $this->opacity($background['image_opacity'] ?? 0.08);
        $fit = in_array(($background['image_fit'] ?? 'cover'), ['cover', 'contain', 'stretch'], true)
            ? $background['image_fit']
            : 'cover';

        return '<div class="page-bg" style="opacity:'.$opacity.'"><img class="fit-'.$fit.'" src="'.$this->escape($dataUri).'" alt=""></div>';
    }

    private function absoluteElement(array $element): string
    {
        $style = 'left:'.$this->mm($element['x_mm'] ?? 0).'mm;top:'.$this->mm($element['y_mm'] ?? 0).'mm;'
            .'width:'.$this->mm($element['w_mm'] ?? 1).'mm;height:'.$this->mm($element['h_mm'] ?? 1).'mm;'
            .'opacity:'.$this->opacity($element['opacity'] ?? 1).';';

        return match ($element['type'] ?? null) {
            'text' => '<div class="doc-el doc-text" style="'.$style
                .'font-family:'.$this->fontFamily($element['font_family'] ?? 'DejaVu Sans').';'
                .'font-size:'.$this->pt($element['font_size_pt'] ?? 11).'pt;'
                .'font-weight:'.$this->fontWeight($element['font_weight'] ?? '400').';'
                .'line-height:'.$this->lineHeight($element['line_height'] ?? 1.35).';'
                .'text-align:'.$this->align($element['align'] ?? 'left').';'
                .'color:'.$this->cssColor((string) ($element['color'] ?? '#111827')).';">'
                .$this->escape((string) ($element['text'] ?? '')).'</div>',
            'signature' => '<div class="doc-el doc-signature" data-signer-order="'.(int) ($element['signer_order'] ?? 1).'" style="'.$style
                .'border-bottom-width:'.$this->mm(0.35).'mm;'
                .'border-bottom-color:'.$this->cssColor((string) ($element['border_color'] ?? '#111827')).';">'
                .'<span class="doc-signature-label">'.$this->escape((string) ($element['label'] ?? 'Assinatura')).'</span></div>',
            'line' => '<div class="doc-el" style="'.$style
                .'height:'.$this->mm($element['thickness_mm'] ?? 0.25).'mm;'
                .'background:'.$this->cssColor((string) ($element['color'] ?? '#111827')).';"></div>',
            'rectangle' => '<div class="doc-el" style="'.$style
                .'border:'.$this->mm($element['border_width_mm'] ?? 0.25).'mm solid '.$this->cssColor((string) ($element['border_color'] ?? '#111827')).';'
                .'background:'.$this->cssColor((string) ($element['background_color'] ?? 'transparent')).';"></div>',
            'image' => $this->absoluteImage($element, $style),
            default => '',
        };
    }

    private function absoluteImage(array $element, string $style): string
    {
        $dataUri = $this->imageDataUri((string) ($element['image_path'] ?? ''));
        if ($dataUri === null) {
            return '';
        }

        $fit = in_array(($element['fit'] ?? 'contain'), ['cover', 'contain', 'stretch'], true)
            ? $element['fit']
            : 'contain';

        return '<div class="doc-el" style="'.$style.'"><img class="fit-'.$fit.'" src="'.$this->escape($dataUri).'" style="width:100%;height:100%;display:block;" alt=""></div>';
    }

    private function pdfList(array $block): string
    {
        $tag = ($block['ordered'] ?? false) ? 'ol' : 'ul';
        $items = collect($block['items'])
            ->map(fn (string $item): string => '<li>'.$this->escapeWithBreaks($item).'</li>')
            ->implode('');

        return "<{$tag}>{$items}</{$tag}>";
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function escapeWithBreaks(string $value): string
    {
        return nl2br($this->escape($value), false);
    }

    private function imageDataUri(string $path): ?string
    {
        $path = trim(str_replace("\0", '', $path));
        if ($path === '' || str_contains($path, '..') || str_starts_with($path, '/') || preg_match('/^[a-z]+:/i', $path)) {
            return null;
        }

        $fullPath = public_path($path);
        if (! is_file($fullPath) || filesize($fullPath) > 3 * 1024 * 1024) {
            return null;
        }

        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => null,
        };
        if ($mime === null) {
            return null;
        }

        $contents = file_get_contents($fullPath);

        return $contents === false ? null : 'data:'.$mime.';base64,'.base64_encode($contents);
    }

    private function mm(mixed $value): string
    {
        return rtrim(rtrim(number_format((float) $value, 3, '.', ''), '0'), '.');
    }

    private function pt(mixed $value): string
    {
        return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
    }

    private function opacity(mixed $value): string
    {
        return rtrim(rtrim(number_format(max(0, min(1, (float) $value)), 3, '.', ''), '0'), '.') ?: '0';
    }

    private function cssColor(string $value): string
    {
        return $value === 'transparent' || preg_match('/^#[0-9a-f]{6}$/i', $value) === 1 ? $value : '#111827';
    }

    private function fontFamily(mixed $value): string
    {
        return match ((string) $value) {
            'Helvetica' => 'Helvetica, Arial, sans-serif',
            'Times' => 'Times, "Times New Roman", serif',
            'Courier' => 'Courier, monospace',
            default => '"DejaVu Sans", sans-serif',
        };
    }

    private function fontWeight(mixed $value): string
    {
        return in_array((string) $value, ['400', '600', '700'], true) ? (string) $value : '400';
    }

    private function lineHeight(mixed $value): string
    {
        return rtrim(rtrim(number_format(max(1, min(2.5, (float) $value)), 2, '.', ''), '0'), '.');
    }

    private function align(mixed $value): string
    {
        return in_array($value, ['left', 'center', 'right', 'justify'], true) ? $value : 'left';
    }
}
