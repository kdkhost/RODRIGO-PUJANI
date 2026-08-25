<?php

namespace App\Http\Controllers;

use App\Contracts\DocumentSignatureProviderInterface;
use App\Services\ElectronicSignatureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SignatureController extends Controller
{
    public function show(Request $request, string $token, ElectronicSignatureService $service): View
    {
        $signer = $service->resolveToken($token);
        $service->markViewed($signer);

        return view('signatures.show', compact('signer', 'token'));
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
