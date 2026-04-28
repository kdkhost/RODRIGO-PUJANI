<?php

namespace App\Http\Controllers\Admin;

use App\Models\Client;
use App\Models\LegalCase;
use App\Models\LegalCaseUpdate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LegalCaseUpdateController extends AdminCrudController
{
    protected string $modelClass = LegalCaseUpdate::class;
    protected string $viewPath = 'legal-case-updates';
    protected string $module = 'legal_case_updates';
    protected string $singularLabel = 'Andamento';
    protected string $pluralLabel = 'Andamentos processuais';
    protected string $routeBase = 'admin.legal-case-updates';
    protected array $searchable = ['title', 'source', 'update_type', 'body'];
    protected string $defaultSort = 'occurred_at';
    protected string $defaultDirection = 'desc';

    protected function indexQuery(Request $request): Builder
    {
        $query = LegalCaseUpdate::query()->with([
            'legalCase:id,title,process_number,primary_lawyer_id',
            'client:id,name',
            'creator:id,name',
        ])->visibleTo($request->user());

        return $query;
    }

    protected function formData(?Model $record = null): array
    {
        $cases = LegalCase::query()
            ->visibleTo(auth()->user())
            ->where('is_active', true)
            ->orderBy('title')
            ->get(['id', 'title', 'client_id']);

        $clients = Client::query()
            ->visibleTo(auth()->user())
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return [
            'cases' => $cases,
            'clients' => $clients,
            'sourceLabels' => [
                'manual' => 'Manual',
                'cliente' => 'Retorno do cliente',
                'datajud' => 'DataJud / CNJ',
                'externo' => 'Fonte externa',
            ],
            'typeLabels' => [
                'procedural' => 'Movimentação processual',
                'deadline' => 'Prazo',
                'hearing' => 'Audiência',
                'dispatch' => 'Despacho / decisão',
                'strategy' => 'Estratégia interna',
                'service' => 'Atendimento',
            ],
        ];
    }

    protected function rules(Request $request, ?Model $record = null): array
    {
        $caseRule = Rule::exists('legal_cases', 'id');

        if (! $request->user()?->canViewAllLegalOperations()) {
            $caseRule = Rule::in(
                LegalCase::query()
                    ->visibleTo($request->user())
                    ->pluck('id')
                    ->all()
            );
        }

        return [
            'legal_case_id' => ['required', 'integer', $caseRule],
            'title' => ['required', 'string', 'max:255'],
            'source' => ['required', 'in:manual,cliente,datajud,externo'],
            'update_type' => ['required', 'in:procedural,deadline,hearing,dispatch,strategy,service'],
            'occurred_at' => ['required', 'date'],
            'body' => ['nullable', 'string'],
        ];
    }

    protected function mutateData(array $validated, Request $request, ?Model $record = null): array
    {
        $legalCase = LegalCase::query()
            ->visibleTo($request->user())
            ->findOrFail($validated['legal_case_id']);

        $validated['client_id'] = $legalCase->client_id;
        $validated['created_by'] ??= $record?->created_by ?: $request->user()?->id;
        $validated += $this->booleanData($request, ['is_visible_to_client']);

        return $validated;
    }

    protected function resolveRecord(string $record): Model
    {
        return LegalCaseUpdate::query()
            ->with(['legalCase:id,title,primary_lawyer_id', 'client:id,name', 'creator:id,name'])
            ->visibleTo(auth()->user())
            ->findOrFail($record);
    }
}
