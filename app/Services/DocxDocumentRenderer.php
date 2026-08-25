<?php

namespace App\Services;

use RuntimeException;
use ZipArchive;

class DocxDocumentRenderer
{
    public function render(string $path, string $title, array $definition): void
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('A extensão ZIP do PHP é necessária para gerar documentos DOCX.');
        }

        if (is_file($path) && ! unlink($path)) {
            throw new RuntimeException('Não foi possível preparar o arquivo DOCX temporário.');
        }

        $archive = new ZipArchive();
        $opened = $archive->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($opened !== true) {
            throw new RuntimeException('Não foi possível criar o pacote DOCX.');
        }

        try {
            foreach ($this->entries($title, $definition) as $entry => $contents) {
                if (! $archive->addFromString($entry, $contents)) {
                    throw new RuntimeException("Não foi possível gravar {$entry} no documento DOCX.");
                }
            }

            if (! $archive->close()) {
                throw new RuntimeException('Não foi possível concluir o documento DOCX.');
            }
        } catch (\Throwable $exception) {
            try {
                $archive->close();
            } catch (\Throwable) {
                // O erro original é mais útil do que uma segunda falha no fechamento do ZIP.
            }
            @unlink($path);

            throw $exception;
        }

        if (! is_file($path) || filesize($path) === 0) {
            throw new RuntimeException('O documento DOCX gerado ficou vazio.');
        }
    }

    public function renderText(string $path, string $title, string $contents): void
    {
        $blocks = collect(preg_split('/\R/u', $contents) ?: [])
            ->map(fn (string $line): array => [
                'type' => trim($line) === '' ? 'spacer' : 'paragraph',
                ...(trim($line) === '' ? ['lines' => 1] : ['text' => $line]),
            ])
            ->all();

        $this->render($path, $title, ['blocks' => $blocks]);
    }

    private function entries(string $title, array $definition): array
    {
        $createdAt = now()->utc()->format('Y-m-d\TH:i:s\Z');

        return [
            '[Content_Types].xml' => $this->contentTypesXml(),
            '_rels/.rels' => $this->packageRelationshipsXml(),
            'docProps/core.xml' => $this->corePropertiesXml($title, $createdAt),
            'docProps/app.xml' => $this->applicationPropertiesXml(),
            'word/document.xml' => $this->documentXml($title, $definition),
            'word/styles.xml' => $this->stylesXml(),
            'word/_rels/document.xml.rels' => $this->documentRelationshipsXml(),
        ];
    }

    private function documentXml(string $title, array $definition): string
    {
        $body = $this->paragraphXml($title, 'Title');

        foreach ($definition['blocks'] ?? [] as $block) {
            $body .= match ($block['type'] ?? null) {
                'heading' => $this->paragraphXml(
                    (string) ($block['text'] ?? ''),
                    'Heading'.max(1, min(3, (int) ($block['level'] ?? 1)))
                ),
                'paragraph' => $this->paragraphXml((string) ($block['text'] ?? '')),
                'list' => $this->listXml($block),
                'page_break' => '<w:p><w:r><w:br w:type="page"/></w:r></w:p>',
                'spacer' => str_repeat('<w:p/>', max(1, min(5, (int) ($block['lines'] ?? 1)))),
                default => '',
            };
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            .'<w:body>'.$body
            .'<w:sectPr><w:pgSz w:w="11906" w:h="16838"/>'
            .'<w:pgMar w:top="1134" w:right="1134" w:bottom="1134" w:left="1134" w:header="708" w:footer="708" w:gutter="0"/>'
            .'</w:sectPr></w:body></w:document>';
    }

    private function paragraphXml(string $text, ?string $style = null, ?int $leftIndent = null): string
    {
        $properties = '';
        if ($style !== null) {
            $properties .= '<w:pStyle w:val="'.$this->xml($style).'"/>';
        }
        if ($leftIndent !== null) {
            $properties .= '<w:ind w:left="'.$leftIndent.'"/>';
        }
        $paragraphProperties = $properties !== '' ? '<w:pPr>'.$properties.'</w:pPr>' : '';
        $lines = preg_split('/\R/u', $text) ?: [$text];
        $runs = '';

        foreach ($lines as $index => $line) {
            if ($index > 0) {
                $runs .= '<w:r><w:br/></w:r>';
            }
            $runs .= '<w:r><w:t xml:space="preserve">'.$this->xml($line).'</w:t></w:r>';
        }

        return '<w:p>'.$paragraphProperties.$runs.'</w:p>';
    }

    private function listXml(array $block): string
    {
        $ordered = (bool) ($block['ordered'] ?? false);

        return collect($block['items'] ?? [])
            ->values()
            ->map(function (mixed $item, int $index) use ($ordered): string {
                $prefix = $ordered ? ($index + 1).'. ' : '• ';

                return $this->paragraphXml($prefix.(string) $item, null, 360);
            })
            ->implode('');
    }

    private function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            .'<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>'
            .'<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            .'<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            .'</Types>';
    }

    private function packageRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            .'<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            .'</Relationships>';
    }

    private function documentRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
    }

    private function corePropertiesXml(string $title, string $createdAt): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" '
            .'xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" '
            .'xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            .'<dc:title>'.$this->xml($title).'</dc:title><dc:creator>'. $this->xml((string) config('app.name')).'</dc:creator>'
            .'<dcterms:created xsi:type="dcterms:W3CDTF">'.$createdAt.'</dcterms:created>'
            .'<dcterms:modified xsi:type="dcterms:W3CDTF">'.$createdAt.'</dcterms:modified>'
            .'</cp:coreProperties>';
    }

    private function applicationPropertiesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" '
            .'xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
            .'<Application>'.$this->xml((string) config('app.name')).'</Application><DocSecurity>0</DocSecurity><AppVersion>1.0</AppVersion>'
            .'</Properties>';
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            .'<w:docDefaults><w:rPrDefault><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:sz w:val="22"/></w:rPr></w:rPrDefault></w:docDefaults>'
            .$this->styleXml('Normal', 'Normal', 22, false, null)
            .$this->styleXml('Title', 'Título', 32, true, 'Normal')
            .$this->styleXml('Heading1', 'Título 1', 28, true, 'Normal')
            .$this->styleXml('Heading2', 'Título 2', 25, true, 'Normal')
            .$this->styleXml('Heading3', 'Título 3', 23, true, 'Normal')
            .'</w:styles>';
    }

    private function styleXml(string $id, string $name, int $size, bool $bold, ?string $basedOn): string
    {
        return '<w:style w:type="paragraph" w:styleId="'.$this->xml($id).'">'
            .'<w:name w:val="'.$this->xml($name).'"/>'
            .($basedOn !== null ? '<w:basedOn w:val="'.$this->xml($basedOn).'"/>' : '')
            .'<w:qFormat/><w:rPr>'.($bold ? '<w:b/>' : '').'<w:sz w:val="'.$size.'"/></w:rPr>'
            .'</w:style>';
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1 | ENT_SUBSTITUTE, 'UTF-8');
    }
}
