<?php

namespace App\Http\Controllers\Admin;

use App\Models\Client;
use App\Models\LegalDeadlinePreference;
use App\Models\LegalCase;
use App\Models\LegalTask;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
        $query = LegalTask::query()
            ->visibleTo($request->user())
            ->with([
                'legalCase:id,client_id,title,process_number',
                'client:id,name',
                'assignedUser:id,name',
                'calendarEvent:id,legal_task_id,start_at,end_at,status',
            ]);

        foreach (['assigned_user_id', 'legal_case_id', 'client_id', 'status', 'task_type'] as $field) {
            if (filled($request->input($field))) {
                $query->where($field, $request->input($field));
            }
        }

        $timezone = $request->user()?->timezone ?: config('app.timezone');
        $today = Carbon::now($timezone)->startOfDay();

        match ($request->string('due_scope')->toString()) {
            'today' => $query->whereBetween('due_at', [$today, $today->copy()->endOfDay()]),
            'tomorrow' => $query->whereBetween('due_at', [
                $today->copy()->addDay(),
                $today->copy()->addDay()->endOfDay(),
            ]),
            'week' => $query->whereBetween('due_at', [$today, $today->copy()->endOfWeek()]),
            'overdue' => $query
                ->where('due_at', '<', $today)
                ->whereNotIn('status', ['done', 'canceled']),
            default => null,
        };

        return $query;
    }

    protected function indexData(Request $request): array
    {
        $baseQuery = LegalTask::query()->visibleTo($request->user());
        $timezone = $request->user()?->timezone ?: config('app.timezone');
        $today = Carbon::now($timezone)->startOfDay();
        $preference = LegalDeadlinePreference::query()
            ->where('user_id', $request->user()?->id)
            ->first() ?: new LegalDeadlinePreference([
                'user_id' => $request->user()?->id,
                'deadline_reminders_enabled' => true,
                'daily_summary_enabled' => true,
                'daily_summary_time' => '07:00',
                'daily_summary_days_ahead' => 7,
                'timezone' => $timezone,
                'email' => $request->user()?->email,
            ]);

        return [
            'filterClients' => Client::query()
                ->visibleTo($request->user())
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'filterCases' => LegalCase::query()
                ->visibleTo($request->user())
                ->where('is_active', true)
                ->orderBy('title')
                ->get(['id', 'client_id', 'title', 'process_number']),
            'filterUsers' => User::query()
                ->visibleTo($request->user())
                ->where('is_active', true)
                ->when(
                    ! $request->user()?->canViewAllLegalOperations(),
                    fn (Builder $query) => $query->whereKey($request->user()?->id)
                )
                ->orderBy('name')
                ->get(['id', 'name']),
            'taskTypeLabels' => $this->taskTypes(),
            'statusLabels' => $this->statuses(),
            'deadlinePreference' => $preference,
            'deadlineStats' => [
                'today' => (clone $baseQuery)->whereBetween('due_at', [$today, $today->copy()->endOfDay()])->count(),
                'tomorrow' => (clone $baseQuery)->whereBetween('due_at', [$today->copy()->addDay(), $today->copy()->addDay()->endOfDay()])->count(),
                'week' => (clone $baseQuery)->whereBetween('due_at', [$today, $today->copy()->endOfWeek()])->count(),
                'overdue' => (clone $baseQuery)->where('due_at', '<', $today)->whereNotIn('status', ['done', 'canceled'])->count(),
            ],
        ];
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

        $users = User::query()
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
            'cases' => $cases,
            'users' => $users,
            'taskTypes' => $this->taskTypes(),
            'priorities' => [
                'low' => 'Baixa',
                'medium' => 'Média',
                'high' => 'Alta',
                'urgent' => 'Urgente',
            ],
            'statuses' => $this->statuses(),
        ];
    }

    protected function rules(Request $request, ?Model $record = null): array
    {
        $clientRule = Rule::exists('clients', 'id');
        $caseRule = Rule::exists('legal_cases', 'id');
        $assignedUserRule = Rule::exists('users', 'id');

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

            $assignedUserRule = Rule::exists('users', 'id')
                ->where(fn ($query) => $query->where('id', $request->user()->id));
        }

        return [
            'legal_case_id' => ['nullable', 'integer', $caseRule],
            'client_id' => [
                'nullable',
                'integer',
                $clientRule,
                function (string $attribute, mixed $value, \Closure $fail) use ($request): void {
                    if (blank($value) || blank($request->input('legal_case_id'))) {
                        return;
                    }

                    $caseClientId = LegalCase::query()
                        ->visibleTo($request->user())
                        ->whereKey($request->input('legal_case_id'))
                        ->value('client_id');

                    if ((int) $caseClientId !== (int) $value) {
                        $fail('O cliente informado não pertence ao processo selecionado.');
                    }
                },
            ],
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

        if (! $request->user()?->canViewAllLegalOperations()) {
            $validated['assigned_user_id'] = $request->user()->id;
        }

        if (filled($validated['legal_case_id'] ?? null)) {
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
            ->with([
                'legalCase:id,client_id,title,process_number',
                'client:id,name',
                'assignedUser:id,name',
                'calendarEvent:id,legal_task_id,start_at,end_at,status',
            ])
            ->visibleTo(auth()->user())
            ->findOrFail($record);
    }

    public function history(string $record): JsonResponse
    {
        $task = $this->resolveRecord($record);
        $history = $task->histories()
            ->with('user:id,name')
            ->latest('id')
            ->paginate(20);

        return response()->json([
            'title' => 'Histórico do prazo',
            'html' => view('admin.legal-tasks._history', [
                'record' => $task,
                'history' => $history,
            ])->render(),
        ]);
    }

    protected function indexView(): string
    {
        return 'admin.legal-tasks.index';
    }

    private function taskTypes(): array
    {
        return [
            'deadline' => 'Prazo',
            'hearing' => 'Audiência',
            'meeting' => 'Reunião',
            'filing' => 'Protocolo',
            'follow_up' => 'Follow-up',
            'review' => 'Revisão',
            'internal' => 'Interna',
        ];
    }

    private function statuses(): array
    {
        return [
            'pending' => 'Pendente',
            'in_progress' => 'Em andamento',
            'waiting' => 'Aguardando retorno',
            'done' => 'Concluída',
            'canceled' => 'Cancelada',
        ];
    }
}
