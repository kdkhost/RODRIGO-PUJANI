<?php

namespace App\Services;

use App\Models\SignatureRequest;
use RuntimeException;
use setasign\Fpdi\Fpdi;

class SignedPdfGenerator
{
    public function generate(string $sourcePath, SignatureRequest $request): string
    {
        $request->loadMissing(['document', 'signers', 'legalDocument.generation.templateVersion']);
        $pdf = new Fpdi('P', 'mm');
        $pdf->SetTitle($this->latin('Documento assinado eletronicamente'));
        $pdf->SetAuthor($this->latin((string) config('app.name')));
        $pdf->SetCreator($this->latin('Central Jurídica'));
        $pdf->SetAutoPageBreak(true, 18);
        $placements = $this->signaturePlacements($request);

        try {
            $pageCount = $pdf->setSourceFile($sourcePath);
            for ($page = 1; $page <= $pageCount; $page++) {
                $template = $pdf->importPage($page);
                $size = $pdf->getTemplateSize($template);
                $orientation = $size['width'] > $size['height'] ? 'L' : 'P';
                $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                $pdf->useTemplate($template);
                $this->drawSignaturesOnPage($pdf, $request, $placements[$page] ?? []);
            }
        } catch (\Throwable $exception) {
            throw new RuntimeException('Não foi possível incorporar o PDF original ao documento assinado.', previous: $exception);
        }

        $this->appendCertificate($pdf, $request);

        return $pdf->Output('S');
    }

    private function drawSignaturesOnPage(Fpdi $pdf, SignatureRequest $request, array $placements): void
    {
        if ($placements === []) {
            return;
        }

        $disk = \Illuminate\Support\Facades\Storage::disk('legal_documents');
        foreach ($placements as $placement) {
            $signer = $request->signers->firstWhere('signing_order', (int) ($placement['signer_order'] ?? 1));
            if (! $signer?->signature_image_path || ! $disk->exists($signer->signature_image_path)) {
                continue;
            }

            $x = (float) ($placement['x_mm'] ?? 0);
            $y = (float) ($placement['y_mm'] ?? 0);
            $w = max(8, (float) ($placement['w_mm'] ?? 40));
            $h = max(6, (float) ($placement['h_mm'] ?? 16));
            $paddingX = min(3, $w * 0.08);
            $paddingY = min(3, $h * 0.18);

            $pdf->Image(
                $disk->path($signer->signature_image_path),
                $x + $paddingX,
                $y + $paddingY,
                max(1, $w - ($paddingX * 2)),
                max(1, $h - ($paddingY * 2)),
                'PNG'
            );
        }
    }

    private function signaturePlacements(SignatureRequest $request): array
    {
        $definition = $request->legalDocument?->generation?->templateVersion?->definition;
        if (! is_array($definition) || ($definition['layout'] ?? null) !== 'absolute') {
            return [];
        }

        $placements = [];
        foreach (($definition['pages'] ?? []) as $pageIndex => $page) {
            foreach (($page['elements'] ?? []) as $element) {
                if (($element['type'] ?? null) !== 'signature') {
                    continue;
                }

                $placements[$pageIndex + 1][] = [
                    'signer_order' => (int) ($element['signer_order'] ?? 1),
                    'x_mm' => (float) ($element['x_mm'] ?? 0),
                    'y_mm' => (float) ($element['y_mm'] ?? 0),
                    'w_mm' => (float) ($element['w_mm'] ?? 40),
                    'h_mm' => (float) ($element['h_mm'] ?? 16),
                ];
            }
        }

        return $placements;
    }

    private function appendCertificate(Fpdi $pdf, SignatureRequest $request): void
    {
        $document = $request->document;
        $pdf->AddPage('P', 'A4');
        $pdf->SetMargins(18, 18, 18);
        $pdf->SetFillColor(12, 17, 23);
        $pdf->Rect(0, 0, 210, 36, 'F');
        $pdf->SetTextColor(196, 154, 60);
        $pdf->SetFont('Helvetica', 'B', 17);
        $pdf->SetXY(18, 13);
        $pdf->Cell(174, 8, $this->latin('CERTIFICADO DE ASSINATURA ELETRÔNICA'), 0, 1);
        $pdf->SetTextColor(60, 66, 74);
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->SetXY(18, 43);
        $pdf->MultiCell(174, 5, $this->latin('Este certificado integra o documento original e registra as evidências do aceite eletrônico. Não representa certificado digital ICP-Brasil.'), 0, 'L');

        $this->labelValue($pdf, 'Solicitação', $request->public_uuid, 59);
        $this->labelValue($pdf, 'Documento', $document->original_name, 72);
        $this->labelValue($pdf, 'Hash SHA-256 do original', $document->sha256, 85);
        $this->labelValue($pdf, 'Concluído em', $request->completed_at?->timezone(config('app.timezone'))->format('d/m/Y H:i:s') ?: now()->format('d/m/Y H:i:s'), 103);

        $pdf->SetXY(18, 119);
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->SetTextColor(18, 24, 31);
        $pdf->Cell(174, 7, $this->latin('SIGNATÁRIOS'), 0, 1);
        $y = 130;
        foreach ($request->signers as $position => $signer) {
            if ($y > 255) {
                $pdf->AddPage('P', 'A4');
                $y = 20;
            }
            $pdf->SetFillColor(245, 246, 248);
            $pdf->Rect(18, $y, 174, 26, 'F');
            $pdf->SetXY(23, $y + 4);
            $pdf->SetFont('Helvetica', 'B', 10);
            $pdf->Cell(164, 5, $this->latin(($position + 1).'. '.$signer->name), 0, 1);
            $pdf->SetX(23);
            $pdf->SetFont('Helvetica', '', 8);
            $signedAt = $signer->signed_at?->timezone(config('app.timezone'))->format('d/m/Y H:i:s') ?: 'Não assinado';
            $documentNumber = $this->maskedDocument((string) $signer->document_normalized);
            $details = 'E-mail: '.$signer->email.' | Documento: '.$documentNumber.' | Data: '.$signedAt;
            $pdf->Cell(164, 5, $this->latin($details), 0, 1);
            $pdf->SetX(23);
            $pdf->Cell(164, 5, $this->latin('IP: '.($signer->ip_address ?: 'não registrado').' | Termos: '.($signer->terms_hash ?: 'não registrado')), 0, 1);
            $y += 31;
        }

        $pdf->SetY(-27);
        $pdf->SetDrawColor(196, 154, 60);
        $pdf->Line(18, $pdf->GetY(), 192, $pdf->GetY());
        $pdf->Ln(4);
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->SetTextColor(90, 96, 104);
        $pdf->MultiCell(174, 4, $this->latin('Validação: confira o código da solicitação e o hash SHA-256 no comprovante de evidências disponibilizado pela Central Jurídica.'), 0, 'L');
    }

    private function labelValue(Fpdi $pdf, string $label, string $value, float $y): void
    {
        $pdf->SetXY(18, $y);
        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->SetTextColor(110, 85, 34);
        $pdf->Cell(174, 4, $this->latin(mb_strtoupper($label)), 0, 1);
        $pdf->SetX(18);
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->SetTextColor(28, 34, 42);
        $pdf->MultiCell(174, 4.5, $this->latin($value), 0, 'L');
    }

    private function maskedDocument(string $value): string
    {
        return match (strlen($value)) {
            11 => substr($value, 0, 3).'.***.***-'.substr($value, -2),
            14 => substr($value, 0, 2).'.***.***/****-'.substr($value, -2),
            default => $value !== '' ? '***'.substr($value, -4) : 'não informado',
        };
    }

    private function latin(string $value): string
    {
        $converted = iconv('UTF-8', 'windows-1252//TRANSLIT', $value);

        return $converted === false ? preg_replace('/[^\x20-\x7E]/', '?', $value) : $converted;
    }
}
