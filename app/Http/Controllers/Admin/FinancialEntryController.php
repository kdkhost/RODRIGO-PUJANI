<?php

namespace App\Http\Controllers\Admin;

use App\Models\Client;
use App\Models\FinancialEntry;
use App\Models\LegalCase;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FinancialEntryController extends AdminCrudController
{
    protected string $modelClass = FinancialEntry::class;
    protected string $viewPath = 'financial-entries';
    protected string $module = 'financial_entries';
    protected string $singularLabel = 'Lançamento financeiro';
    protected string $pluralLabel = 'Financeiro';
    protected string $routeBase = 'admin.financial-entries';
    protected array $searchable = ['description', 'reference', 'category', 'payment_method'];
    protected string $defaultSort = 'due_date';
    protected string $defaultDirection = 'asc';

    public function store(Request $request): JsonResponse
    {
        $record = $this->newModel();
        $data = $this->mutateData($request->validate($this->rules($request, $record)), $request, $record);

        DB::transaction(function () use ($record, $data, $request): void {
            $record->fill($data);
            $record->save();
            $this->afterSave($record, $request, true);
        });

        $installmentIds = FinancialEntry::query()
            ->when($record->installment_group, fn (Builder $query) => $query->where('installment_group', $record->installment_group), fn (Builder $query) => $query->whereKey($record->id))
            ->orderBy('installment_number')
            ->pluck('id')
            ->all();

        $this->clearSiteCaches();
        activity_log($this->module, 'created', $record, [
            'client_id' => $record->client_id,
            'legal_case_id' => $record->legal_case_id,
            'installment_group' => $record->installment_group,
            'installment_count' => $record->installment_count,
            'installment_ids' => $installmentIds,
        ], 'Lançamento financeiro criado com parcelas em transação atômica.');

        return response()->json([
            'message' => $record->installment_count > 1 ? 'Lançamento e parcelas cadastrados com sucesso.' : 'Lançamento cadastrado com sucesso.',
            'tableTarget' => '#admin-resource-table',
        ]);
    }

    protected function indexQuery(Request $request): Builder
    {
        return FinancialEntry::query()
            ->visibleTo($request->user())
            ->with(['client:id,name', 'legalCase:id,title', 'responsibleUser:id,name'])
            ->when($request->filled('entry_type'), fn (Builder $query) => $query->where('entry_type', $request->string('entry_type')->toString()))
            ->when($request->filled('status'), function (Builder $query) use ($request): void {
                $status = $request->string('status')->toString();
                if ($status === 'overdue') {
                    $query->where('status', 'pending')->whereDate('due_date', '<', today());
                } else {
                    $query->where('status', $status);
                }
            })
            ->when($request->filled('client_id'), fn (Builder $query) => $query->where('client_id', $request->integer('client_id')))
            ->when($request->filled('legal_case_id'), fn (Builder $query) => $query->where('legal_case_id', $request->integer('legal_case_id')))
            ->when($request->filled('date_from'), fn (Builder $query) => $query->whereDate('due_date', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn (Builder $query) => $query->whereDate('due_date', '<=', $request->date('date_to')));
    }

    protected function indexData(Request $request): array
    {
        $base = FinancialEntry::query()->visibleTo($request->user());

        return [
            'financialSummary' => [
                'receivable' => (clone $base)->where('entry_type', 'income')->whereIn('status', ['pending', 'overdue'])->sum('amount'),
                'received' => (clone $base)->where('entry_type', 'income')->where('status', 'paid')->sum('amount'),
                'payable' => (clone $base)->where('entry_type', 'expense')->whereIn('status', ['pending', 'overdue'])->sum('amount'),
                'paid_expenses' => (clone $base)->where('entry_type', 'expense')->where('status', 'paid')->sum('amount'),
            ],
        ];
    }

    protected function formData(?Model $record = null): array
    {
        $user = auth()->user();

        return [
            'clients' => Client::query()->visibleTo($user)->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'cases' => LegalCase::query()->visibleTo($user)->where('is_active', true)->orderBy('title')->get(['id', 'client_id', 'title']),
            'users' => User::query()->visibleTo($user)->where('is_active', true)
                ->when(! $user?->canViewAllLegalOperations(), fn (Builder $query) => $query->whereKey($user?->id))
                ->orderBy('name')->get(['id', 'name']),
            'entryTypes' => ['income' => 'Honorário / receita', 'expense' => 'Despesa'],
            'statuses' => ['pending' => 'Pendente', 'paid' => 'Pago', 'canceled' => 'Cancelado'],
            'categories' => [
                'fees' => 'Honorários',
                'success_fee' => 'Êxito',
                'court_costs' => 'Custas processuais',
                'travel' => 'Deslocamento',
                'services' => 'Serviços de terceiros',
                'taxes' => 'Impostos e taxas',
                'other' => 'Outros',
            ],
        ];
    }

    protected function rules(Request $request, ?Model $record = null): array
    {
        $user = $request->user();
        $clientIds = Client::query()->visibleTo($user)->pluck('id')->all();
        $caseIds = LegalCase::query()->visibleTo($user)->pluck('id')->all();
        $userIds = User::query()->visibleTo($user)
            ->when(! $user?->canViewAllLegalOperations(), fn (Builder $query) => $query->whereKey($user?->id))
            ->pluck('id')->all();

        return [
            'client_id' => ['required', 'integer', Rule::in($clientIds)],
            'legal_case_id' => ['nullable', 'integer', Rule::in($caseIds)],
            'responsible_user_id' => ['nullable', 'integer', Rule::in($userIds)],
            'entry_type' => ['required', Rule::in(['income', 'expense'])],
            'category' => ['required', 'string', 'max:60'],
            'description' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:120'],
            'amount' => ['required', 'string', 'max:40'],
            'due_date' => ['required', 'date'],
            'paid_at' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['pending', 'paid', 'canceled'])],
            'payment_method' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'installment_count' => [$record?->exists ? 'nullable' : 'required', 'integer', 'min:1', 'max:120'],
        ];
    }

    protected function mutateData(array $validated, Request $request, ?Model $record = null): array
    {
        if (filled($validated['legal_case_id'] ?? null)) {
            $caseClientId = LegalCase::query()->visibleTo($request->user())
                ->whereKey($validated['legal_case_id'])
                ->value('client_id');

            if ((int) $caseClientId !== (int) $validated['client_id']) {
                throw ValidationException::withMessages([
                    'client_id' => 'O cliente informado não pertence ao processo selecionado.',
                ]);
            }
        }

        $totalAmount = $this->normalizeMoney((string) $validated['amount']);
        if ($totalAmount === null || (float) $totalAmount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Informe um valor financeiro válido e maior que zero.']);
        }

        $count = $record?->exists ? (int) $record->installment_count : (int) ($validated['installment_count'] ?? 1);
        $validated['amount'] = $record?->exists ? $totalAmount : $this->installmentAmount($totalAmount, $count, 1);
        $validated['installment_count'] = max(1, $count);
        $validated['installment_number'] = $record?->installment_number ?: 1;
        $validated['installment_group'] = $record?->installment_group ?: ($count > 1 ? (string) Str::uuid() : null);
        $validated['created_by'] ??= $record?->created_by ?: $request->user()?->id;

        if (! $request->user()?->canViewAllLegalOperations()) {
            $validated['responsible_user_id'] = $request->user()->id;
        }

        if (($validated['status'] ?? null) === 'paid') {
            $validated['paid_at'] = filled($validated['paid_at'] ?? null) ? $validated['paid_at'] : now();
        } else {
            $validated['paid_at'] = null;
        }

        return $validated;
    }

    protected function afterSave(Model $record, Request $request, bool $created): void
    {
        if (! $created || ! $record instanceof FinancialEntry || $record->installment_count <= 1) {
            return;
        }

        $total = $this->normalizeMoney((string) $request->input('amount'));
        if ($total === null) {
            return;
        }

        for ($number = 2; $number <= $record->installment_count; $number++) {
            $copy = $record->replicate();
            $copy->installment_number = $number;
            $copy->amount = $this->installmentAmount($total, $record->installment_count, $number);
            $copy->due_date = Carbon::parse($record->due_date)->addMonthsNoOverflow($number - 1);
            $copy->paid_at = null;
            $copy->status = 'pending';
            $copy->save();
        }
    }

    protected function resolveRecord(string $record): Model
    {
        return FinancialEntry::query()
            ->visibleTo(auth()->user())
            ->with(['client:id,name', 'legalCase:id,title', 'responsibleUser:id,name'])
            ->findOrFail($record);
    }

    private function normalizeMoney(string $value): ?string
    {
        $normalized = preg_replace('/[^\d,.-]/', '', $value);
        if (str_contains((string) $normalized, ',')) {
            $normalized = str_replace('.', '', (string) $normalized);
            $normalized = str_replace(',', '.', (string) $normalized);
        }

        return is_numeric($normalized) ? number_format((float) $normalized, 2, '.', '') : null;
    }

    private function installmentAmount(string $total, int $count, int $number): string
    {
        $cents = (int) round(((float) $total) * 100);
        $base = intdiv($cents, max(1, $count));
        $amount = $number === $count ? $cents - ($base * ($count - 1)) : $base;

        return number_format($amount / 100, 2, '.', '');
    }
}
