<?php

namespace App\Http\Controllers\Admin;

use App\Models\Client;
use App\Models\LegalCase;
use App\Models\LegalDocument;
use App\Services\LegalDocumentStorage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LegalDocumentController extends AdminCrudController
{
    protected string $modelClass = LegalDocument::class;
    protected string $viewPath = 'legal-documents';
    protected string $module = 'legal_documents';
    protected string $singularLabel = 'Documento';
    protected string $pluralLabel = 'Documentos jurídicos';
    protected string $routeBase = 'admin.legal-documents';
    protected array $searchable = ['title', 'category', 'original_name', 'file_name', 'notes'];
    protected string $defaultSort = 'created_at';
    protected string $defaultDirection = 'desc';

    protected function indexQuery(Request $request): Builder
    {
        return LegalDocument::query()
            ->visibleTo($request->user())
            ->with(['legalCase:id,title', 'client:id,name', 'uploader:id,name']);
    }

    protected function formData(?Model $record = null): array
    {
        $clients = Client::query()
            ->visibleTo(auth()->user())
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $cases = LegalCase::query()
            ->visibleTo(auth()->user())
            ->where('is_active', true)
            ->orderBy('title')
            ->get(['id', 'title']);

        return [
            'clients' => $clients,
            'cases' => $cases,
            'categories' => [
                'contrato' => 'Contrato',
                'procuracao' => 'Procuração',
                'peticao' => 'Petição',
                'prova' => 'Prova',
                'audiencia' => 'Audiência',
                'financeiro' => 'Financeiro',
                'interno' => 'Interno',
            ],
        ];
    }

    protected function rules(Request $request, ?Model $record = null): array
    {
        $clientRule = Rule::exists('clients', 'id');
        $caseRule = Rule::exists('legal_cases', 'id');

        if (! $request->user()?->canViewAllLegalOperations()) {
            $clientRule = Rule::in(
                Client::query()
                    ->visibleTo($request->user())
                    ->pluck('id')
                    ->all()
            );

            $caseRule = Rule::in(
                LegalCase::query()
                    ->visibleTo($request->user())
                    ->pluck('id')
                    ->all()
            );
        }

        return [
            'legal_case_id' => ['nullable', 'integer', $caseRule],
            'client_id' => ['nullable', 'integer', $clientRule],
            'title' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'file' => [$record?->exists ? 'nullable' : 'required', 'file', 'max:15360'],
        ];
    }

    protected function mutateData(array $validated, Request $request, ?Model $record = null): array
    {
        if (filled($validated['legal_case_id'] ?? null) && blank($validated['client_id'] ?? null)) {
            $validated['client_id'] = LegalCase::query()
                ->whereKey($validated['legal_case_id'])
                ->value('client_id');
        }

        if (filled($validated['legal_case_id'] ?? null)) {
            $caseClientId = LegalCase::query()
                ->visibleTo($request->user())
                ->whereKey($validated['legal_case_id'])
                ->value('client_id');

            if (filled($validated['client_id'] ?? null) && (int) $validated['client_id'] !== (int) $caseClientId) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'client_id' => 'O cliente selecionado não é o proprietário do processo informado.',
                ]);
            }

            $validated['client_id'] = $caseClientId;
        }

        if ($request->hasFile('file')) {
            $oldDocument = $record?->exists ? clone $record : null;
            $validated = array_merge($validated, app(LegalDocumentStorage::class)->store($request->file('file')));

            if ($oldDocument) {
                app(LegalDocumentStorage::class)->delete($oldDocument);
            }
        }

        unset($validated['file']);
        $validated += $this->booleanData($request, ['is_sensitive', 'shared_with_client']);
        $validated['uploaded_by'] ??= $record?->uploaded_by ?: $request->user()?->id;

        return $validated;
    }

    protected function beforeDelete(Model $record): void
    {
        if ($record instanceof LegalDocument) {
            app(LegalDocumentStorage::class)->delete($record);
        }
    }

    protected function resolveRecord(string $record): Model
    {
        $document = LegalDocument::query()
            ->with(['legalCase:id,title', 'client:id,name', 'uploader:id,name'])
            ->visibleTo(auth()->user())
            ->findOrFail($record);

        $this->authorize('view', $document);

        return $document;
    }

    public function download(string $record, LegalDocumentStorage $storage): BinaryFileResponse
    {
        /** @var LegalDocument $document */
        $document = LegalDocument::query()->findOrFail($record);
        $this->authorize('download', $document);

        $path = $storage->absolutePath($document);
        abort_unless($path, 404);

        activity_log('legal_documents', 'downloaded', $document, ['disk' => $document->disk], 'Documento jurídico baixado.');

        return response()->download(
            $path,
            $storage->safeDownloadName($document->original_name ?: $document->file_name),
            ['X-Content-Type-Options' => 'nosniff']
        );
    }
}
