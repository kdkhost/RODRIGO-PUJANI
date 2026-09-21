<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\DocumentSignatureProviderInterface;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\LegalCase;
use App\Models\LegalDocument;
use App\Models\SignatureRequest;
use App\Services\ElectronicSignatureService;
use App\Services\LegalDocumentStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SignatureRequestController extends Controller
{
    public function index(Request $request, ElectronicSignatureService $service): View
    {
        $this->authorize('viewAny', SignatureRequest::class);

        SignatureRequest::query()
            ->where('status', 'pending')
            ->where('expires_at', '<', now())
            ->each(fn (SignatureRequest $item) => $service->expire($item));

        $items = SignatureRequest::query()
            ->whereHas('legalDocument', fn ($query) => $query->visibleTo($request->user()))
            ->with(['client:id,name', 'document', 'signers'])
            ->latest()
            ->paginate(20);

        return view('admin.signature-requests.index', compact('items'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', SignatureRequest::class);

        $documents = LegalDocument::query()
            ->visibleTo($request->user())
            ->with(['client:id,name', 'legalCase:id,title'])
            ->whereNotNull('client_id')
            ->where('disk', LegalDocumentStorage::DISK)
            ->where('storage_status', 'private')
            ->where('mime_type', 'application/pdf')
            ->where('extension', 'pdf')
            ->whereNotNull('sha256')
            ->latest()
            ->get(['id', 'title', 'client_id', 'legal_case_id', 'original_name', 'sha256', 'created_at']);

        $clients = Client::query()
            ->visibleTo($request->user())
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $cases = LegalCase::query()
            ->visibleTo($request->user())
            ->where('is_active', true)
            ->orderBy('title')
            ->get(['id', 'client_id', 'title']);

        $selectedDocument = (int) $request->integer('document');

        return view('admin.signature-requests.create', [
            'documents' => $documents,
            'clients' => $clients,
            'cases' => $cases,
            'selectedDocument' => $selectedDocument,
            'documentSource' => $selectedDocument > 0 || $documents->isNotEmpty() ? 'existing' : 'upload',
        ]);
    }

    public function store(
        Request $request,
        ElectronicSignatureService $service,
        DocumentSignatureProviderInterface $provider,
        LegalDocumentStorage $storage
    ): RedirectResponse {
        $this->authorize('create', SignatureRequest::class);

        $request->merge([
            'document_source' => $request->input('document_source', $request->hasFile('upload_file') ? 'upload' : 'existing'),
        ]);

        $data = $request->validate([
            'document_source' => ['required', Rule::in(['existing', 'upload'])],
            'legal_document_id' => ['nullable', 'required_if:document_source,existing', 'integer', Rule::exists('legal_documents', 'id')],
            'upload_file' => ['nullable', 'required_if:document_source,upload', 'file', 'mimes:pdf', 'max:15360'],
            'upload_client_id' => ['nullable', 'required_if:document_source,upload', 'integer', $this->clientRule($request)],
            'upload_legal_case_id' => ['nullable', 'integer', $this->caseRule($request)],
            'upload_title' => ['nullable', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:3000'],
            'expires_at' => ['required', 'date', 'after:now'],
            'ordered' => ['nullable', 'boolean'],
            'signers' => ['required', 'array', 'min:1', 'max:20'],
            'signers.*.name' => ['required', 'string', 'max:255'],
            'signers.*.email' => ['required', 'email:rfc', 'max:255', 'distinct:ignore_case'],
            'signers.*.document' => ['nullable', 'string', 'max:32'],
        ]);

        $document = $data['document_source'] === 'upload'
            ? $this->storeUploadedDocument($request, $data, $storage)
            : $this->eligibleDocumentFromSelection($request, (int) $data['legal_document_id']);

        $signatureRequest = $service->create($document, $data, (int) $request->user()->id);
        $provider->send($signatureRequest);

        return redirect()
            ->route('admin.signature-requests.show', $signatureRequest)
            ->with('success', 'Solicitação criada e enviada com segurança.');
    }

    public function show(SignatureRequest $signatureRequest, ElectronicSignatureService $service): View
    {
        $this->authorize('view', $signatureRequest);
        $service->expire($signatureRequest);
        $signatureRequest->load(['client', 'legalCase', 'creator', 'document', 'signers', 'events.signer']);

        return view('admin.signature-requests.show', [
            'signatureRequest' => $signatureRequest,
            'evidenceValid' => $signatureRequest->status === 'completed' ? $service->verifyEvidence($signatureRequest) : null,
        ]);
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

        return Storage::disk($signatureRequest->document->disk)->download(
            $signatureRequest->document->evidence_path,
            'comprovante-'.$signatureRequest->public_uuid.'.json',
            ['X-Content-Type-Options' => 'nosniff']
        );
    }

    public function completedDocument(SignatureRequest $signatureRequest, ElectronicSignatureService $service): StreamedResponse
    {
        $this->authorize('download', $signatureRequest);
        $signatureRequest->load('document');
        abort_unless($signatureRequest->status === 'completed' && $service->verifyEvidence($signatureRequest), 409, 'Documento concluído ausente ou inválido.');
        $document = $signatureRequest->document;
        abort_unless(Storage::disk($document->disk)->exists($document->completed_path), 404);

        return Storage::disk($document->disk)->download(
            $document->completed_path,
            'assinado-'.$document->original_name,
            ['X-Content-Type-Options' => 'nosniff']
        );
    }

    private function eligibleDocumentFromSelection(Request $request, int $documentId): LegalDocument
    {
        $document = LegalDocument::query()
            ->visibleTo($request->user())
            ->whereKey($documentId)
            ->firstOrFail();

        if (! ElectronicSignatureService::supports($document) || blank($document->client_id)) {
            throw ValidationException::withMessages([
                'legal_document_id' => 'Selecione um PDF privado, com cliente vinculado e hash de integridade calculado.',
            ]);
        }

        return $document;
    }

    private function storeUploadedDocument(Request $request, array $data, LegalDocumentStorage $storage): LegalDocument
    {
        $file = $request->file('upload_file');
        if (! $file instanceof UploadedFile) {
            throw ValidationException::withMessages(['upload_file' => 'Anexe o PDF que será enviado para assinatura.']);
        }

        $clientId = (int) $data['upload_client_id'];
        $caseId = filled($data['upload_legal_case_id'] ?? null) ? (int) $data['upload_legal_case_id'] : null;

        if ($caseId) {
            $caseClientId = LegalCase::query()
                ->visibleTo($request->user())
                ->whereKey($caseId)
                ->value('client_id');

            if (! $caseClientId) {
                throw ValidationException::withMessages(['upload_legal_case_id' => 'O processo selecionado não está disponível para este usuário.']);
            }

            if ($clientId !== (int) $caseClientId) {
                throw ValidationException::withMessages(['upload_client_id' => 'O cliente selecionado não pertence ao processo informado.']);
            }
        }

        $stored = $storage->store($file);
        $title = trim((string) ($data['upload_title'] ?? ''));
        if ($title === '') {
            $title = trim((string) $data['title']);
        }

        return LegalDocument::query()->create($stored + [
            'legal_case_id' => $caseId,
            'client_id' => $clientId,
            'uploaded_by' => $request->user()?->id,
            'title' => $title,
            'category' => 'contrato',
            'notes' => 'Documento anexado diretamente na solicitação de assinatura eletrônica.',
            'is_sensitive' => true,
            'shared_with_client' => false,
        ]);
    }

    private function clientRule(Request $request): mixed
    {
        if ($request->user()?->canViewAllLegalOperations()) {
            return Rule::exists('clients', 'id');
        }

        return Rule::in(Client::query()->visibleTo($request->user())->pluck('id')->all());
    }

    private function caseRule(Request $request): mixed
    {
        if ($request->user()?->canViewAllLegalOperations()) {
            return Rule::exists('legal_cases', 'id');
        }

        return Rule::in(LegalCase::query()->visibleTo($request->user())->pluck('id')->all());
    }
}
