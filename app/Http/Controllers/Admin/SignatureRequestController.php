<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\DocumentSignatureProviderInterface;
use App\Http\Controllers\Controller;
use App\Models\LegalDocument;
use App\Models\SignatureRequest;
use App\Services\ElectronicSignatureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SignatureRequestController extends Controller
{
    public function index(Request $request, ElectronicSignatureService $service): View
    {
        $this->authorize('viewAny', SignatureRequest::class);
        SignatureRequest::query()->where('status', 'pending')->where('expires_at', '<', now())->each(fn ($item) => $service->expire($item));
        $items = SignatureRequest::query()
            ->whereHas('legalDocument', fn ($query) => $query->visibleTo($request->user()))
            ->with(['client:id,name', 'document', 'signers'])->latest()->paginate(20);

        return view('admin.signature-requests.index', compact('items'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', SignatureRequest::class);
        $documents = LegalDocument::query()->visibleTo($request->user())->whereNotNull('client_id')->whereNotNull('path')->latest()->get(['id', 'title', 'client_id']);

        return view('admin.signature-requests.create', ['documents' => $documents, 'selectedDocument' => (int) $request->integer('document')]);
    }

    public function store(Request $request, ElectronicSignatureService $service, DocumentSignatureProviderInterface $provider): RedirectResponse
    {
        $this->authorize('create', SignatureRequest::class);
        $data = $request->validate([
            'legal_document_id' => ['required', 'integer', Rule::exists('legal_documents', 'id')],
            'title' => ['required', 'string', 'max:255'], 'message' => ['nullable', 'string', 'max:3000'],
            'expires_at' => ['required', 'date', 'after:now'], 'ordered' => ['nullable', 'boolean'],
            'signers' => ['required', 'array', 'min:1', 'max:20'], 'signers.*.name' => ['required', 'string', 'max:255'],
            'signers.*.email' => ['required', 'email:rfc', 'max:255', 'distinct:ignore_case'], 'signers.*.document' => ['nullable', 'string', 'max:32'],
        ]);
        $document = LegalDocument::query()->visibleTo($request->user())->findOrFail($data['legal_document_id']);
        $signatureRequest = $service->create($document, $data, $request->user()->id);
        $provider->send($signatureRequest);

        return redirect()->route('admin.signature-requests.show', $signatureRequest)->with('success', 'Solicitação criada e enviada com segurança.');
    }

    public function show(SignatureRequest $signatureRequest, ElectronicSignatureService $service): View
    {
        $this->authorize('view', $signatureRequest);
        $service->expire($signatureRequest);
        $signatureRequest->load(['client', 'legalCase', 'creator', 'document', 'signers', 'events.signer']);

        return view('admin.signature-requests.show', ['signatureRequest' => $signatureRequest, 'evidenceValid' => $signatureRequest->status === 'completed' ? $service->verifyEvidence($signatureRequest) : null]);
    }

    public function resend(SignatureRequest $signatureRequest, DocumentSignatureProviderInterface $provider): RedirectResponse
    {
        $this->authorize('manage', $signatureRequest);
        $provider->send($signatureRequest);

        return back()->with('success', 'Convites pendentes reenviados.');
    }

    public function cancel(Request $request, SignatureRequest $signatureRequest, DocumentSignatureProviderInterface $provider): RedirectResponse
    {
        $this->authorize('cancel', $signatureRequest);
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']]);
        $provider->cancel($signatureRequest, $data['reason']);

        return back()->with('success', 'Solicitação cancelada.');
    }

    public function evidence(SignatureRequest $signatureRequest, ElectronicSignatureService $service): StreamedResponse
    {
        $this->authorize('audit', $signatureRequest);
        abort_unless($service->verifyEvidence($signatureRequest), 409, 'Comprovante ausente ou inválido.');

        return Storage::disk($signatureRequest->document->disk)->download($signatureRequest->document->evidence_path, 'comprovante-'.$signatureRequest->public_uuid.'.json', ['X-Content-Type-Options' => 'nosniff']);
    }

    public function completedDocument(SignatureRequest $signatureRequest, ElectronicSignatureService $service): StreamedResponse
    {
        $this->authorize('download', $signatureRequest);
        $signatureRequest->load('document');
        abort_unless($signatureRequest->status === 'completed' && $service->verifyEvidence($signatureRequest), 409, 'Documento concluído ausente ou inválido.');
        $document = $signatureRequest->document;
        abort_unless(Storage::disk($document->disk)->exists($document->completed_path), 404);

        return Storage::disk($document->disk)->download($document->completed_path, 'assinado-'.$document->original_name, ['X-Content-Type-Options' => 'nosniff']);
    }
}
