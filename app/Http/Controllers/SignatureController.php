<?php

namespace App\Http\Controllers;

use App\Contracts\DocumentSignatureProviderInterface;
use App\Services\ElectronicSignatureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SignatureController extends Controller
{
    public function show(Request $request, string $token, ElectronicSignatureService $service): View
    {
        $signer = $service->resolveToken($token);
        $service->markViewed($signer);

        return view('signatures.show', compact('signer', 'token'));
    }

    public function document(string $token, ElectronicSignatureService $service): BinaryFileResponse
    {
        $signer = $service->resolveToken($token);
        $service->markViewed($signer);

        $document = $signer->signatureRequest->document;
        $disk = Storage::disk($document->disk);

        abort_unless($disk->exists($document->immutable_path), 404);
        abort_unless(hash_equals((string) $document->sha256, hash('sha256', $disk->get($document->immutable_path))), 409, 'A integridade do documento original não pôde ser confirmada.');

        $fileName = preg_replace('/[^\pL\pN\.\-_\s]+/u', '-', (string) ($document->original_name ?: 'documento.pdf')) ?: 'documento.pdf';

        return response()->file($disk->path($document->immutable_path), [
            'Content-Type' => $document->mime_type ?: 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$fileName.'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function sign(Request $request, string $token, ElectronicSignatureService $service, DocumentSignatureProviderInterface $provider): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'document' => ['nullable', 'string', 'max:32'],
            'consent' => ['accepted'],
            'signature_payload' => ['required', 'string', 'max:800000'],
        ]);
        $signer = $service->resolveToken($token);
        abort_unless(hash_equals(mb_strtolower(trim($signer->name)), mb_strtolower(trim($data['name']))), 422, 'Nome do signatário divergente.');
        if ($signer->document_normalized) {
            abort_unless(hash_equals($signer->document_normalized, preg_replace('/\D+/', '', (string) $data['document'])), 422, 'Documento do signatário divergente.');
        }
        $drawnSignature = $this->validateDrawnSignature((string) $data['signature_payload']);
        $provider->sign($signer, [
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'terms_text' => 'Declaro que li e concordo em assinar eletronicamente este documento.',
            'signature_image' => $drawnSignature['image'],
            'signature_metrics' => $drawnSignature['metrics'],
        ]);

        return redirect()->route('signatures.public.result')->with('signature_result', 'signed');
    }

    public function decline(Request $request, string $token, ElectronicSignatureService $service, DocumentSignatureProviderInterface $provider): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']]);
        $provider->decline($service->resolveToken($token), $data['reason']);

        return redirect()->route('signatures.public.result')->with('signature_result', 'declined');
    }

    public function result(): View
    {
        return view('signatures.result');
    }

    private function validateDrawnSignature(string $payload): array
    {
        $decoded = json_decode($payload, true);
        if (! is_array($decoded)) {
            throw ValidationException::withMessages(['signature_payload' => 'A assinatura desenhada é obrigatória.']);
        }

        $dataUrl = (string) ($decoded['data_url'] ?? '');
        if (! str_starts_with($dataUrl, 'data:image/png;base64,')) {
            throw ValidationException::withMessages(['signature_payload' => 'A assinatura deve ser desenhada no campo próprio.']);
        }

        $binary = base64_decode(Str::after($dataUrl, 'data:image/png;base64,'), true);
        if (! is_string($binary) || $binary === '' || strlen($binary) > 600000) {
            throw ValidationException::withMessages(['signature_payload' => 'A imagem da assinatura é inválida ou excede o limite permitido.']);
        }

        $imageInfo = @getimagesizefromstring($binary);
        if (! is_array($imageInfo) || ($imageInfo[0] ?? 0) < 240 || ($imageInfo[1] ?? 0) < 90) {
            throw ValidationException::withMessages(['signature_payload' => 'O campo de assinatura precisa ter tamanho válido.']);
        }

        $metrics = is_array($decoded['metrics'] ?? null) ? $decoded['metrics'] : [];
        $strokeCount = (int) ($metrics['stroke_count'] ?? 0);
        $pointCount = (int) ($metrics['point_count'] ?? 0);
        $distance = (float) ($metrics['distance_px'] ?? 0);
        $duration = (int) ($metrics['duration_ms'] ?? 0);
        $bounds = is_array($metrics['bounds'] ?? null) ? $metrics['bounds'] : [];
        $boundsWidth = (float) ($bounds['width'] ?? 0);
        $boundsHeight = (float) ($bounds['height'] ?? 0);
        $pixelMetrics = $this->signaturePixelMetrics($binary);

        if ($pixelMetrics !== null) {
            $boundsWidth = max($boundsWidth, (float) $pixelMetrics['width']);
            $boundsHeight = max($boundsHeight, (float) $pixelMetrics['height']);
            $metrics['ink_pixels'] = $pixelMetrics['ink_pixels'];
            $metrics['pixel_bounds'] = $pixelMetrics['bounds'];
        }

        if ($strokeCount < 1 || $pointCount < 12 || $distance < 80 || $duration < 250 || $boundsWidth < 50 || $boundsHeight < 8) {
            throw ValidationException::withMessages([
                'signature_payload' => 'A assinatura precisa conter traços reais. Pontos, riscos mínimos ou campo quase vazio não são aceitos.',
            ]);
        }

        if (($metrics['ink_pixels'] ?? 120) < 90) {
            throw ValidationException::withMessages([
                'signature_payload' => 'A assinatura desenhada está muito pequena ou quase vazia.',
            ]);
        }

        $metrics['stroke_count'] = $strokeCount;
        $metrics['point_count'] = $pointCount;
        $metrics['distance_px'] = round($distance, 2);
        $metrics['duration_ms'] = $duration;
        $metrics['image_width'] = (int) $imageInfo[0];
        $metrics['image_height'] = (int) $imageInfo[1];

        return ['image' => $binary, 'metrics' => $metrics];
    }

    private function signaturePixelMetrics(string $binary): ?array
    {
        if (! function_exists('imagecreatefromstring')) {
            return null;
        }

        $image = @imagecreatefromstring($binary);
        if (! $image) {
            return null;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $minX = $width;
        $minY = $height;
        $maxX = 0;
        $maxY = 0;
        $inkPixels = 0;

        for ($y = 0; $y < $height; $y += 2) {
            for ($x = 0; $x < $width; $x += 2) {
                $rgba = imagecolorat($image, $x, $y);
                $alpha = ($rgba & 0x7F000000) >> 24;
                $red = ($rgba >> 16) & 0xFF;
                $green = ($rgba >> 8) & 0xFF;
                $blue = $rgba & 0xFF;
                if ($alpha >= 120 || ($red >= 245 && $green >= 245 && $blue >= 245)) {
                    continue;
                }

                $inkPixels++;
                $minX = min($minX, $x);
                $minY = min($minY, $y);
                $maxX = max($maxX, $x);
                $maxY = max($maxY, $y);
            }
        }

        imagedestroy($image);
        if ($inkPixels === 0) {
            return ['ink_pixels' => 0, 'width' => 0, 'height' => 0, 'bounds' => null];
        }

        return [
            'ink_pixels' => $inkPixels,
            'width' => $maxX - $minX,
            'height' => $maxY - $minY,
            'bounds' => ['x' => $minX, 'y' => $minY, 'width' => $maxX - $minX, 'height' => $maxY - $minY],
        ];
    }
}
