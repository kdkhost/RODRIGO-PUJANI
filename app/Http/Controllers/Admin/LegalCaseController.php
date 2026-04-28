<?php

namespace App\Http\Controllers\Admin;

use App\Models\Client;
use App\Models\LegalCase;
use App\Models\User;
use App\Services\LegalCaseDataJudSyncService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

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
        return LegalCase::query()
            ->visibleTo($request->user())
            ->with(['client:id,name', 'primaryLawyer:id,name'])
            ->withCount(['updates', 'legalDocuments']);
    }

    protected function formData(?Model $record = null): array
    {
        $clients = Client::query()
            ->visibleTo(auth()->user())
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $lawyers = User::query()
            ->visibleTo(auth()->user())
            ->where('is_active', true)
            ->when(
                ! auth()->user()?->canViewAllLegalOperations(),
                fn (Builder $query) => $query->whereKey(auth()->id())
            )
            ->orderBy('name')
            ->get(['id', 'name']);

        return [
            'clients' => $clients,
            'lawyers' => $lawyers,
            'canChooseLawyer' => auth()->user()?->canViewAllLegalOperations() ?? false,
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
                'execution' => 'Cumprimento / execução',
            ],
            'priorities' => [
                'low' => 'Baixa',
                'medium' => 'Média',
                'high' => 'Alta',
                'urgent' => 'Urgente',
            ],
            'tribunalSuggestions' => [
                'tjsp' => 'TJSP',
                'tjrj' => 'TJRJ',
                'tjmg' => 'TJMG',
                'tjrs' => 'TJRS',
                'tjpr' => 'TJPR',
                'tjba' => 'TJBA',
                'trf1' => 'TRF1',
                'trf2' => 'TRF2',
                'trf3' => 'TRF3',
                'trf4' => 'TRF4',
                'trf5' => 'TRF5',
                'trt2' => 'TRT2',
                'trt15' => 'TRT15',
                'stj' => 'STJ',
                'tst' => 'TST',
            ],
        ];
    }

    protected function rules(Request $request, ?Model $record = null): array
    {
        $clientRule = Rule::exists('clients', 'id');

        if (! $request->user()?->canViewAllLegalOperations()) {
            $clientRule = Rule::in(
                Client::query()
                    ->visibleTo($request->user())
                    ->pluck('id')
                    ->all()
            );
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
            'portal_summary' => ['nullable', 'string'],
            'tribunal_alias' => ['nullable', 'string', 'max:40', 'regex:/^[a-z0-9-]+$/i'],
        ];
    }

    protected function mutateData(array $validated, Request $request, ?Model $record = null): array
    {
        $validated += $this->booleanData($request, ['is_confidential', 'is_active', 'portal_visible', 'datajud_sync_enabled']);
        $validated['created_by'] ??= $record?->created_by ?: $request->user()?->id;
        $validated['claim_amount'] = $this->normalizeMoney($validated['claim_amount'] ?? null);
        $validated['contract_value'] = $this->normalizeMoney($validated['contract_value'] ?? null);
        $validated['tribunal_alias'] = filled($validated['tribunal_alias'] ?? null)
            ? strtolower(trim((string) $validated['tribunal_alias']))
            : null;

        if (! $request->user()?->canViewAllLegalOperations()) {
            $validated['primary_lawyer_id'] = $request->user()->id;
            $validated['supervising_lawyer_id'] = $record?->supervising_lawyer_id;
        }

        if (! $validated['datajud_sync_enabled']) {
            $validated['tribunal_alias'] = $validated['tribunal_alias'] ?: $record?->tribunal_alias;
        }

        return $validated;
    }

    protected function resolveRecord(string $record): Model
    {
        return LegalCase::query()
            ->with(['client:id,name', 'primaryLawyer:id,name'])
            ->withCount(['updates', 'legalDocuments'])
            ->visibleTo(auth()->user())
            ->findOrFail($record);
    }

    public function syncDataJud(string $record, Request $request, LegalCaseDataJudSyncService $service): JsonResponse
    {
        /** @var LegalCase $legalCase */
        $legalCase = $this->resolveRecord($record);

        try {
            $result = $service->sync($legalCase, $request->user()?->id);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        activity_log('legal_cases', 'synced', $legalCase, $result, 'Processo sincronizado com o DataJud.');

        return response()->json([
            'message' => "Sincronização concluída. {$result['created']} andamento(s) novo(s) e {$result['updated']} atualizado(s).",
            'tableTarget' => '#admin-resource-table',
            'closeModal' => false,
        ]);
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
