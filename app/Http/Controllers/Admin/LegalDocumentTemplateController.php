<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LegalDocumentTemplate;
use App\Services\LegalDocumentTemplateManager;
use App\Services\LegalDocumentTokenEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use JsonException;

class LegalDocumentTemplateController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', LegalDocumentTemplate::class);

        $templates = LegalDocumentTemplate::query()
            ->with(['latestVersion:id,legal_document_template_id,version,content_sha256,created_at'])
            ->withCount(['versions', 'generations'])
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = trim($request->string('search')->toString());
                $query->where(function ($nested) use ($search): void {
                    $nested
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.legal-document-templates.index', [
            'pageTitle' => 'Templates jurídicos',
            'templates' => $templates,
        ]);
    }

    public function create(LegalDocumentTokenEngine $tokens): View
    {
        $this->authorize('create', LegalDocumentTemplate::class);

        return $this->formView(new LegalDocumentTemplate(), $tokens, 'Novo template jurídico');
    }

    public function store(
        Request $request,
        LegalDocumentTemplateManager $manager
    ): RedirectResponse {
        $this->authorize('create', LegalDocumentTemplate::class);
        [$metadata, $titleTemplate, $definition] = $this->validateTemplate($request);

        $template = $manager->create($request->user(), $metadata, $titleTemplate, $definition);

        return redirect()
            ->route('admin.legal-document-templates.show', $template)
            ->with('status', 'Template jurídico criado com a versão 1 imutável.');
    }

    public function show(Request $request, LegalDocumentTemplate $legalDocumentTemplate): View
    {
        $this->authorize('view', $legalDocumentTemplate);

        $legalDocumentTemplate->load([
            'creator:id,name',
            'versions' => fn ($query) => $query->with('creator:id,name')->orderByDesc('version'),
            'generations' => fn ($query) => $query
                ->visibleTo($request->user())
                ->with([
                    'legalDocument:id,title,original_name,extension,sha256,storage_status',
                    'client:id,name',
                    'legalCase:id,title',
                    'generator:id,name',
                ])
                ->orderByDesc('generated_at')
                ->limit(50),
        ]);

        return view('admin.legal-document-templates.show', [
            'pageTitle' => $legalDocumentTemplate->name,
            'template' => $legalDocumentTemplate,
        ]);
    }

    public function edit(
        LegalDocumentTemplate $legalDocumentTemplate,
        LegalDocumentTokenEngine $tokens
    ): View {
        $this->authorize('update', $legalDocumentTemplate);

        return $this->formView($legalDocumentTemplate, $tokens, 'Editar template jurídico');
    }

    public function update(
        Request $request,
        LegalDocumentTemplate $legalDocumentTemplate,
        LegalDocumentTemplateManager $manager
    ): RedirectResponse {
        $this->authorize('update', $legalDocumentTemplate);
        $metadata = $this->validateMetadata($request, $legalDocumentTemplate);
        $metadata['context_scope'] = $legalDocumentTemplate->context_scope;

        $manager->updateMetadata($request->user(), $legalDocumentTemplate, $metadata);

        return redirect()
            ->route('admin.legal-document-templates.show', $legalDocumentTemplate)
            ->with('status', 'Metadados atualizados. As versões publicadas não foram alteradas.');
    }

    public function storeVersion(
        Request $request,
        LegalDocumentTemplate $legalDocumentTemplate,
        LegalDocumentTemplateManager $manager
    ): RedirectResponse {
        $this->authorize('createVersion', $legalDocumentTemplate);
        [$titleTemplate, $definition] = $this->validateVersion($request);

        $version = $manager->createVersion(
            $request->user(),
            $legalDocumentTemplate,
            $titleTemplate,
            $definition
        );

        return redirect()
            ->route('admin.legal-document-templates.show', $legalDocumentTemplate)
            ->with('status', "Versão {$version->version} publicada de forma imutável.");
    }

    private function formView(
        LegalDocumentTemplate $template,
        LegalDocumentTokenEngine $tokens,
        string $pageTitle
    ): View {
        $latestVersion = $template->exists
            ? $template->versions()->orderByDesc('version')->first()
            : null;

        return view('admin.legal-document-templates.form', [
            'pageTitle' => $pageTitle,
            'template' => $template,
            'latestVersion' => $latestVersion,
            'contextScopes' => LegalDocumentTemplate::contextScopes(),
            'outputFormats' => LegalDocumentTemplate::outputFormats(),
            'allowedTokens' => $tokens->allowedTokens(),
        ]);
    }

    private function validateTemplate(Request $request): array
    {
        $metadata = $this->validateMetadata($request);
        [$titleTemplate, $definition] = $this->validateVersion($request);

        return [$metadata, $titleTemplate, $definition];
    }

    private function validateMetadata(
        Request $request,
        ?LegalDocumentTemplate $template = null
    ): array {
        $request->merge(['slug' => Str::slug((string) $request->input('slug'))]);

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('legal_document_templates', 'slug')->ignore($template?->id),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'context_scope' => ['required', Rule::in(array_keys(LegalDocumentTemplate::contextScopes()))],
            'default_output_format' => ['required', Rule::in(array_keys(LegalDocumentTemplate::outputFormats()))],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active')];
    }

    private function validateVersion(Request $request): array
    {
        $validated = $request->validate([
            'title_template' => ['required', 'string', 'max:255'],
            'definition_json' => ['required', 'string', 'max:500000'],
        ]);

        try {
            $definition = json_decode($validated['definition_json'], true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw ValidationException::withMessages([
                'definition_json' => 'A estrutura JSON do template é inválida.',
            ]);
        }

        if (! is_array($definition)) {
            throw ValidationException::withMessages([
                'definition_json' => 'A estrutura JSON deve ser um objeto.',
            ]);
        }

        return [$validated['title_template'], $definition];
    }
}
