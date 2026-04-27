<?php

namespace App\Http\Controllers\Admin;

use App\Models\Client;
use App\Models\LegalCase;
use App\Models\LegalDocument;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
        $query = LegalDocument::query()->with(['legalCase:id,title', 'client:id,name', 'uploader:id,name']);

        if ($request->user()?->isAssociatedLawyer()) {
            $query->where(function (Builder $builder) use ($request): void {
                $builder
                    ->where('uploaded_by', $request->user()->id)
                    ->orWhereHas('legalCase', fn (Builder $caseQuery) => $caseQuery->where('primary_lawyer_id', $request->user()->id))
                    ->orWhereHas('client', fn (Builder $clientQuery) => $clientQuery->where('assigned_lawyer_id', $request->user()->id));
            });
        }

        return $query;
    }

    protected function formData(?Model $record = null): array
    {
        $clients = Client::query()
            ->when(
                auth()->user()?->isAssociatedLawyer(),
                fn (Builder $query) => $query->where('assigned_lawyer_id', auth()->id())
            )
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $cases = LegalCase::query()
            ->when(
                auth()->user()?->isAssociatedLawyer(),
                fn (Builder $query) => $query->where('primary_lawyer_id', auth()->id())
            )
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

        if ($request->user()?->isAssociatedLawyer()) {
            $clientRule = Rule::exists('clients', 'id')
                ->where(fn ($query) => $query->where('assigned_lawyer_id', $request->user()->id));

            $caseRule = Rule::exists('legal_cases', 'id')
                ->where(fn ($query) => $query->where('primary_lawyer_id', $request->user()->id));
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

        if ($request->hasFile('file')) {
            $this->deleteMediaFile($record?->path);

            $path = $this->storeMediaFile($request, 'file', 'legal-documents', null, false);
            $file = $request->file('file');

            $validated['original_name'] = $file->getClientOriginalName();
            $validated['file_name'] = basename($path);
            $validated['path'] = $path;
            $validated['mime_type'] = $file->getMimeType();
            $validated['extension'] = $file->getClientOriginalExtension();
            $validated['size'] = $file->getSize();
        }

        unset($validated['file']);
        $validated += $this->booleanData($request, ['is_sensitive', 'shared_with_client']);
        $validated['uploaded_by'] ??= $record?->uploaded_by ?: $request->user()?->id;

        return $validated;
    }

    protected function beforeDelete(Model $record): void
    {
        $this->deleteMediaFile($record->path);
    }

    protected function resolveRecord(string $record): Model
    {
        return LegalDocument::query()
            ->with(['legalCase:id,title', 'client:id,name', 'uploader:id,name'])
            ->when(auth()->user()?->isAssociatedLawyer(), function (Builder $query): void {
                $query->where(function (Builder $builder): void {
                    $builder
                        ->where('uploaded_by', auth()->id())
                        ->orWhereHas('legalCase', fn (Builder $caseQuery) => $caseQuery->where('primary_lawyer_id', auth()->id()))
                        ->orWhereHas('client', fn (Builder $clientQuery) => $clientQuery->where('assigned_lawyer_id', auth()->id()));
                });
            })
            ->findOrFail($record);
    }
}
