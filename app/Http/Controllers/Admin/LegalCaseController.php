<?php

namespace App\Http\Controllers\Admin;

use App\Models\Client;
use App\Models\LegalCase;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LegalCaseController extends AdminCrudController
{
    protected string $modelClass = LegalCase::class;
    protected string $viewPath = 'legal-cases';
    protected string $module = 'legal_cases';
    protected string $singularLabel = 'Processo';
    protected string $pluralLabel = 'Processos';
    protected string $routeBase = 'admin.legal-cases';
    protected array $searchable = ['title', 'process_number', 'internal_code', 'practice_area', 'counterparty', 'court_name'];
    protected string $defaultSort = 'next_deadline_at';
    protected string $defaultDirection = 'asc';

    protected function indexQuery(Request $request): Builder
    {
        $query = LegalCase::query()->with(['client:id,name', 'primaryLawyer:id,name']);

        if ($request->user()?->isAssociatedLawyer()) {
            $query->where('primary_lawyer_id', $request->user()->id);
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

        $lawyers = User::query()
            ->where('is_active', true)
            ->when(
                auth()->user()?->isAssociatedLawyer(),
                fn (Builder $query) => $query->whereKey(auth()->id())
            )
            ->orderBy('name')
            ->get(['id', 'name']);

        return [
            'clients' => $clients,
            'lawyers' => $lawyers,
            'canChooseLawyer' => ! auth()->user()?->isAssociatedLawyer(),
            'statuses' => [
                'active' => 'Ativo',
                'waiting_court' => 'Aguardando Judiciário',
                'waiting_client' => 'Aguardando cliente',
                'appeal' => 'Em recurso',
                'closed' => 'Encerrado',
                'archived' => 'Arquivado',
            ],
            'phases' => [
                'initial' => 'Inicial',
                'instruction' => 'Instrução',
                'evidence' => 'Produção de prova',
                'hearing' => 'Audiência',
                'sentence' => 'Sentença',
                'appeal' => 'Recurso',
                'execution' => 'Cumprimento/execução',
            ],
            'priorities' => [
                'low' => 'Baixa',
                'medium' => 'Média',
                'high' => 'Alta',
                'urgent' => 'Urgente',
            ],
        ];
    }

    protected function rules(Request $request, ?Model $record = null): array
    {
        $clientRule = Rule::exists('clients', 'id');

        if ($request->user()?->isAssociatedLawyer()) {
            $clientRule = Rule::exists('clients', 'id')
                ->where(fn ($query) => $query->where('assigned_lawyer_id', $request->user()->id));
        }

        return [
            'client_id' => ['required', 'integer', $clientRule],
            'primary_lawyer_id' => ['nullable', 'integer', 'exists:users,id'],
            'supervising_lawyer_id' => ['nullable', 'integer', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'process_number' => ['nullable', 'string', 'max:40'],
            'internal_code' => ['nullable', 'string', 'max:40'],
            'practice_area' => ['nullable', 'string', 'max:255'],
            'counterparty' => ['nullable', 'string', 'max:255'],
            'court_name' => ['nullable', 'string', 'max:255'],
            'court_division' => ['nullable', 'string', 'max:255'],
            'court_city' => ['nullable', 'string', 'max:255'],
            'court_state' => ['nullable', 'string', 'max:8'],
            'status' => ['required', 'in:active,waiting_court,waiting_client,appeal,closed,archived'],
            'phase' => ['required', 'in:initial,instruction,evidence,hearing,sentence,appeal,execution'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'filing_date' => ['nullable', 'date'],
            'next_hearing_at' => ['nullable', 'date'],
            'next_deadline_at' => ['nullable', 'date'],
            'claim_amount' => ['nullable', 'string', 'max:30'],
            'contract_value' => ['nullable', 'string', 'max:30'],
            'success_fee_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'summary' => ['nullable', 'string'],
            'strategy_notes' => ['nullable', 'string'],
        ];
    }

    protected function mutateData(array $validated, Request $request, ?Model $record = null): array
    {
        $validated += $this->booleanData($request, ['is_confidential', 'is_active']);
        $validated['created_by'] ??= $record?->created_by ?: $request->user()?->id;
        $validated['claim_amount'] = $this->normalizeMoney($validated['claim_amount'] ?? null);
        $validated['contract_value'] = $this->normalizeMoney($validated['contract_value'] ?? null);

        if ($request->user()?->isAssociatedLawyer()) {
            $validated['primary_lawyer_id'] = $request->user()->id;
            $validated['supervising_lawyer_id'] = $record?->supervising_lawyer_id;
        }

        return $validated;
    }

    protected function resolveRecord(string $record): Model
    {
        return LegalCase::query()
            ->with(['client:id,name', 'primaryLawyer:id,name'])
            ->when(
                auth()->user()?->isAssociatedLawyer(),
                fn (Builder $query) => $query->where('primary_lawyer_id', auth()->id())
            )
            ->findOrFail($record);
    }

    private function normalizeMoney(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $normalized = preg_replace('/[^\d,.-]/', '', (string) $value);

        if (str_contains((string) $normalized, ',')) {
            $normalized = str_replace('.', '', (string) $normalized);
            $normalized = str_replace(',', '.', (string) $normalized);
        }

        return is_numeric($normalized) ? number_format((float) $normalized, 2, '.', '') : null;
    }
}
