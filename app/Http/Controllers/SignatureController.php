<?php

namespace App\Http\Controllers;

use App\Contracts\DocumentSignatureProviderInterface;
use App\Services\ElectronicSignatureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'document' => ['nullable', 'string', 'max:32'], 'consent' => ['accepted']]);
        $signer = $service->resolveToken($token);
        abort_unless(hash_equals(mb_strtolower(trim($signer->name)), mb_strtolower(trim($data['name']))), 422, 'Nome do signatário divergente.');
        if ($signer->document_normalized) {
            abort_unless(hash_equals($signer->document_normalized, preg_replace('/\D+/', '', (string) $data['document'])), 422, 'Documento do signatário divergente.');
        }
        $provider->sign($signer, ['ip_address' => $request->ip(), 'user_agent' => $request->userAgent(), 'terms_text' => 'Declaro que li e concordo em assinar eletronicamente este documento.']);

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
}
