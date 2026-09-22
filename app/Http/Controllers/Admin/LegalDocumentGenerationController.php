<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\LegalCase;
use App\Models\LegalDocumentTemplate;
use App\Models\LegalDocumentTemplateVersion;
use App\Services\ElectronicSignatureService;
use App\Services\LegalDocumentGenerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LegalDocumentGenerationController extends Controller
{
    public function create(
        Request $request,
        LegalDocumentTemplate $legalDocumentTemplate
    ): View {
        $this->authorize('generate', $legalDocumentTemplate);

        $clients = collect();
        $cases = collect();

        if (in_array($legalDocumentTemplate->context_scope, [
            LegalDocumentTemplate::CONTEXT_CLIENT,
            LegalDocumentTemplate::CONTEXT_CLIENT_CASE,
        ], true)) {
            $clients = Client::query()
                ->visibleTo($request->user())
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'document_number']);
        }

        if (in_array($legalDocumentTemplate->context_scope, [
            LegalDocumentTemplate::CONTEXT_CASE,
            LegalDocumentTemplate::CONTEXT_CLIENT_CASE,
        ], true)) {
            $cases = LegalCase::query()
                ->visibleTo($request->user())
                ->where('is_active', true)
                ->orderBy('title')
                ->get(['id', 'client_id', 'title', 'process_number']);
        }

        return view('admin.legal-document-templates.generate', [
            'pageTitle' => 'Gerar documento jurídico',
            'template' => $legalDocumentTemplate,
            'versions' => $legalDocumentTemplate->versions()->orderByDesc('version')->get(),
            'clients' => $clients,
            'cases' => $cases,
            'outputFormats' => LegalDocumentTemplate::outputFormats(),
        ]);
    }

    public function store(
        Request $request,
        LegalDocumentTemplate $legalDocumentTemplate,
        LegalDocumentGenerationService $service
    ): RedirectResponse|JsonResponse {
        $this->authorize('generate', $legalDocumentTemplate);

        $validated = $request->validate([
            'legal_document_template_version_id' => [
                'required',
                'integer',
                Rule::exists('legal_document_template_versions', 'id')
                    ->where('legal_document_template_id', $legalDocumentTemplate->id),
            ],
            'client_id' => ['nullable', 'integer'],
            'legal_case_id' => ['nullable', 'integer'],
            'output_format' => ['required', Rule::in(array_keys(LegalDocumentTemplate::outputFormats()))],
            'shared_with_client' => ['nullable', 'boolean'],
            'after_generate' => ['nullable', Rule::in(['documents', 'signature'])],
        ]);

        if (($validated['after_generate'] ?? 'documents') === 'signature'
            && $validated['output_format'] !== LegalDocumentTemplate::FORMAT_PDF) {
            throw ValidationException::withMessages([
                'output_format' => 'Para enviar para assinatura, gere o documento em PDF.',
            ]);
        }

        $version = LegalDocumentTemplateVersion::query()
            ->where('legal_document_template_id', $legalDocumentTemplate->id)
            ->findOrFail($validated['legal_document_template_version_id']);

        $generation = $service->generate($request->user(), $legalDocumentTemplate, $version, [
            ...$validated,
            'context_scope' => $legalDocumentTemplate->context_scope,
            'shared_with_client' => $request->boolean('shared_with_client'),
        ]);

        $downloadUrl = route('admin.legal-documents.download', $generation->legal_document_id);
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Documento gerado, versionado e armazenado em área privada.',
                'generation_id' => $generation->id,
                'document_id' => $generation->legal_document_id,
                'download_url' => $downloadUrl,
                'signature_url' => ElectronicSignatureService::supports($generation->legalDocument)
                    ? route('admin.signature-requests.create', ['document' => $generation->legal_document_id, 'from_generation' => 1])
                    : null,
            ], 201);
        }

        if (($validated['after_generate'] ?? 'documents') === 'signature') {
            if (! ElectronicSignatureService::supports($generation->legalDocument) || blank($generation->legalDocument->client_id)) {
                throw ValidationException::withMessages([
                    'output_format' => 'O documento gerado não ficou elegível para assinatura. Confirme cliente vinculado, PDF privado e hash SHA-256.',
                ]);
            }

            return redirect()
                ->route('admin.signature-requests.create', [
                    'document' => $generation->legal_document_id,
                    'from_generation' => 1,
                ])
                ->with('status', 'Documento gerado em PDF. Complete os signatários para enviar o link de assinatura ao cliente.');
        }

        return redirect()
            ->route('admin.legal-documents.index', ['highlight_document' => $generation->legal_document_id])
            ->with('status', 'Documento gerado, versionado e armazenado em área privada.')
            ->with('generated_document_id', $generation->legal_document_id);
    }
}
