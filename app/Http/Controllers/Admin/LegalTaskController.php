<?php

namespace App\Http\Controllers\Admin;

use App\Models\Client;
use App\Models\LegalCase;
use App\Models\LegalTask;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LegalTaskController extends AdminCrudController
{
    protected string $modelClass = LegalTask::class;
    protected string $viewPath = 'legal-tasks';
    protected string $module = 'legal_tasks';
    protected string $singularLabel = 'Tarefa';
    protected string $pluralLabel = 'Tarefas e prazos';
    protected string $routeBase = 'admin.legal-tasks';
    protected array $searchable = ['title', 'task_type', 'location', 'description', 'result_notes'];
    protected string $defaultSort = 'due_at';
    protected string $defaultDirection = 'asc';

    protected function indexQuery(Request $request): Builder
    {
        $query = LegalTask::query()->with(['legalCase:id,title', 'client:id,name', 'assignedUser:id,name']);

        if ($request->user()?->isAssociatedLawyer()) {
            $query->where(function (Builder $builder) use ($request): void {
                $builder
                    ->where('assigned_user_id', $request->user()->id)
                    ->orWhereHas('legalCase', fn (Builder $caseQuery) => $caseQuery->where('primary_lawyer_id', $request->user()->id));
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

        $users = User::query()
            ->where('is_active', true)
            ->when(
                auth()->user()?->isAssociatedLawyer(),
                fn (Builder $query) => $query->whereKey(auth()->id())
            )
            ->orderBy('name')
            ->get(['id', 'name']);

        return [
            'clients' => $clients,
            'cases' => $cases,
            'users' => $users,
            'taskTypes' => [
                'deadline' => 'Prazo',
                'hearing' => 'Audiência',
                'meeting' => 'Reunião',
                'filing' => 'Protocolo',
                'follow_up' => 'Follow-up',
                'review' => 'Revisão',
                'internal' => 'Interna',
            ],
            'priorities' => [
                'low' => 'Baixa',
                'medium' => 'Média',
                'high' => 'Alta',
                'urgent' => 'Urgente',
            ],
            'statuses' => [
                'pending' => 'Pendente',
                'in_progress' => 'Em andamento',
                'waiting' => 'Aguardando retorno',
                'done' => 'Concluída',
                'canceled' => 'Cancelada',
            ],
        ];
    }

    protected function rules(Request $request, ?Model $record = null): array
    {
        $clientRule = Rule::exists('clients', 'id');
        $caseRule = Rule::exists('legal_cases', 'id');
        $assignedUserRule = Rule::exists('users', 'id');

        if ($request->user()?->isAssociatedLawyer()) {
            $clientRule = Rule::exists('clients', 'id')
                ->where(fn ($query) => $query->where('assigned_lawyer_id', $request->user()->id));

            $caseRule = Rule::exists('legal_cases', 'id')
                ->where(fn ($query) => $query->where('primary_lawyer_id', $request->user()->id));

            $assignedUserRule = Rule::exists('users', 'id')
                ->where(fn ($query) => $query->where('id', $request->user()->id));
        }

        return [
            'legal_case_id' => ['nullable', 'integer', $caseRule],
            'client_id' => ['nullable', 'integer', $clientRule],
            'assigned_user_id' => ['nullable', 'integer', $assignedUserRule],
            'title' => ['required', 'string', 'max:255'],
            'task_type' => ['required', 'in:deadline,hearing,meeting,filing,follow_up,review,internal'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'status' => ['required', 'in:pending,in_progress,waiting,done,canceled'],
            'start_at' => ['nullable', 'date'],
            'due_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'location' => ['nullable', 'string', 'max:255'],
            'reminder_minutes' => ['nullable', 'integer', 'min:0', 'max:10080'],
            'billable_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'description' => ['nullable', 'string'],
            'result_notes' => ['nullable', 'string'],
        ];
    }

    protected function mutateData(array $validated, Request $request, ?Model $record = null): array
    {
        $validated['created_by'] ??= $record?->created_by ?: $request->user()?->id;

        if ($request->user()?->isAssociatedLawyer()) {
            $validated['assigned_user_id'] = $request->user()->id;
        }

        if (filled($validated['legal_case_id'] ?? null) && blank($validated['client_id'] ?? null)) {
            $validated['client_id'] = LegalCase::query()
                ->whereKey($validated['legal_case_id'])
                ->value('client_id');
        }

        if (($validated['status'] ?? null) === 'done' && blank($validated['completed_at'] ?? $record?->completed_at)) {
            $validated['completed_at'] = now();
        }

        if (($validated['status'] ?? null) !== 'done') {
            $validated['completed_at'] = null;
        }

        return $validated;
    }

    protected function resolveRecord(string $record): Model
    {
        return LegalTask::query()
            ->with(['legalCase:id,title', 'client:id,name', 'assignedUser:id,name'])
            ->when(auth()->user()?->isAssociatedLawyer(), function (Builder $query): void {
                $query->where(function (Builder $builder): void {
                    $builder
                        ->where('assigned_user_id', auth()->id())
                        ->orWhereHas('legalCase', fn (Builder $caseQuery) => $caseQuery->where('primary_lawyer_id', auth()->id()));
                });
            })
            ->findOrFail($record);
    }
}
