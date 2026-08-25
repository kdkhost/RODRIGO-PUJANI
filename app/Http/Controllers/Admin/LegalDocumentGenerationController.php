<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\LegalCase;
use App\Models\LegalDocumentTemplate;
use App\Models\LegalDocumentTemplateVersion;
use App\Services\LegalDocumentGenerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
        ]);

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
            ], 201);
        }

        return redirect()
            ->route('admin.legal-document-templates.show', $legalDocumentTemplate)
            ->with('status', 'Documento gerado, versionado e armazenado em área privada.')
            ->with('generated_document_id', $generation->legal_document_id);
    }
}
