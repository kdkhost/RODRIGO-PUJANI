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
}
