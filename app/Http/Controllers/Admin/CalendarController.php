<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use App\Models\Client;
use App\Models\LegalCase;
use App\Models\LegalTask;
use App\Models\User;
use App\Services\LegalTaskCalendarService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CalendarController extends Controller
{
    private const STATUSES = ['scheduled', 'confirmed', 'done', 'canceled'];
    private const VISIBILITIES = ['private', 'team', 'public'];
    private const DISPLAYS = ['auto', 'background', 'inverse-background'];
    private const EVENT_TYPES = ['appointment', 'deadline', 'hearing', 'meeting', 'filing', 'follow_up', 'review', 'internal'];

    public function index(): View
    {
        $eventsQuery = $this->visibleEventsQuery();
        $eventsForOwners = (clone $eventsQuery)
            ->with('owner:id,name')
            ->whereNotNull('owner_id')
            ->get();
        $calendarInitialDate = (clone $eventsQuery)
            ->where('start_at', '>=', now()->startOfDay())
            ->orderBy('start_at')
            ->value('start_at');

        if (! $calendarInitialDate) {
            $calendarInitialDate = (clone $eventsQuery)
                ->orderBy('start_at')
                ->value('start_at');
        }

        $records = (clone $eventsQuery)
            ->with($this->eventRelations())
            ->orderBy('start_at')
            ->paginate(10)
            ->withQueryString();

        return view('admin.calendar.index', [
            'pageTitle' => 'Agenda',
            'users' => $this->availableOwners(),
            'clients' => $this->availableClients(),
            'cases' => $this->availableCases(),
            'eventTypes' => self::EVENT_TYPES,
            'statuses' => self::STATUSES,
            'visibilities' => self::VISIBILITIES,
            'displays' => self::DISPLAYS,
            'categories' => (clone $eventsQuery)
                ->select('category')
                ->distinct()
                ->orderBy('category')
                ->pluck('category')
                ->filter()
                ->values(),
            'eventStats' => [
                'total' => (clone $eventsQuery)->count(),
                'today' => (clone $eventsQuery)->whereDate('start_at', today())->count(),
                'upcoming' => (clone $eventsQuery)->whereBetween('start_at', [now(), now()->copy()->addDays(7)->endOfDay()])->count(),
                'all_day' => (clone $eventsQuery)->where('all_day', true)->count(),
                'background' => (clone $eventsQuery)->whereIn('display', ['background', 'inverse-background'])->count(),
            ],
            'upcomingEvents' => (clone $eventsQuery)
                ->with($this->eventRelations())
                ->where('start_at', '>=', now()->startOfDay())
                ->orderBy('start_at')
                ->limit(6)
                ->get(),
            'ownerLoad' => $eventsForOwners
                ->groupBy('owner_id')
                ->map(fn ($items): array => [
                    'name' => $items->first()?->owner?->name ?: 'Sem responsável',
                    'total' => $items->count(),
                ])
                ->sortByDesc('total')
                ->take(5)
                ->values(),
            'calendarInitialDate' => $calendarInitialDate ? Carbon::parse($calendarInitialDate)->toDateString() : now()->toDateString(),
            'records' => $records,
        ]);
    }

    public function events(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start' => ['nullable', 'date'],
            'end' => ['nullable', 'date'],
            'search' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(self::STATUSES)],
            'visibility' => ['nullable', Rule::in(self::VISIBILITIES)],
            'display' => ['nullable', Rule::in(self::DISPLAYS)],
            'owner_id' => ['nullable', 'integer', 'exists:users,id'],
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'legal_case_id' => ['nullable', 'integer', 'exists:legal_cases,id'],
            'event_type' => ['nullable', Rule::in(self::EVENT_TYPES)],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        $query = $this->visibleEventsQuery()->with($this->eventRelations());
        $this->applySearchFilter($query, $validated['search'] ?? null);
        $this->applyRangeFilter(
            $query,
            filled($validated['start'] ?? null) ? Carbon::parse($validated['start']) : null,
            filled($validated['end'] ?? null) ? Carbon::parse($validated['end']) : null,
        );
        $this->applyRangeFilter(
            $query,
            filled($validated['date_from'] ?? null) ? Carbon::parse($validated['date_from'])->startOfDay() : null,
            filled($validated['date_to'] ?? null) ? Carbon::parse($validated['date_to'])->addDay()->startOfDay() : null,
        );
        $this->applyAttributeFilters($query, $validated);

        return response()->json($query
            ->orderBy('start_at')
            ->get()
            ->map(fn (CalendarEvent $event): array => $this->calendarPayload($event))
            ->values());
    }

    public function records(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(self::STATUSES)],
            'visibility' => ['nullable', Rule::in(self::VISIBILITIES)],
            'display' => ['nullable', Rule::in(self::DISPLAYS)],
            'owner_id' => ['nullable', 'integer', 'exists:users,id'],
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'legal_case_id' => ['nullable', 'integer', 'exists:legal_cases,id'],
            'event_type' => ['nullable', Rule::in(self::EVENT_TYPES)],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $query = $this->visibleEventsQuery()->with($this->eventRelations());
        $this->applySearchFilter($query, $validated['search'] ?? null);
        $this->applyRangeFilter(
            $query,
            filled($validated['date_from'] ?? null) ? Carbon::parse($validated['date_from'])->startOfDay() : null,
            filled($validated['date_to'] ?? null) ? Carbon::parse($validated['date_to'])->addDay()->startOfDay() : null,
        );
        $this->applyAttributeFilters($query, $validated);

        $items = $query
            ->orderBy('start_at')
            ->paginate($validated['per_page'] ?? 10)
            ->withQueryString();

        return response()->json([
            'html' => view('admin.calendar._table', [
                'items' => $items,
            ])->render(),
        ]);
    }

    public function create(Request $request): JsonResponse
    {
        $event = new CalendarEvent([
            'start_at' => $request->filled('start') ? Carbon::parse($request->string('start')->toString()) : now()->startOfHour(),
            'end_at' => $request->filled('end') ? Carbon::parse($request->string('end')->toString()) : now()->startOfHour()->addHour(),
            'all_day' => $request->boolean('all_day'),
            'editable' => true,
            'overlap' => true,
            'status' => 'scheduled',
            'visibility' => 'team',
            'category' => 'Atendimento',
            'event_type' => 'appointment',
            'color' => '#c49a3c',
            'text_color' => '#111318',
            'display' => 'auto',
            'owner_id' => Auth::user()?->isAssociatedLawyer() ? Auth::id() : null,
        ]);

        return $this->formResponse($event, 'Novo evento');
    }

    public function store(Request $request, LegalTaskCalendarService $taskCalendar): JsonResponse
    {
        $event = new CalendarEvent();
        $event->fill($this->validatedData($request));
        $event->created_by = Auth::id();
        $event->save();
        $taskCalendar->syncCalendarEventToTask($event);

        activity_log('calendar', 'created', $event, $event->toArray(), 'Evento criado na agenda.');

        return response()->json([
            'message' => 'Evento criado com sucesso.',
            'calendarTarget' => '#admin-calendar',
            'tableTarget' => '#admin-calendar-events-table',
        ]);
    }

    public function edit(CalendarEvent $event): JsonResponse
    {
        $this->guardVisibleEvent($event);

        return $this->formResponse($event, 'Editar evento');
    }

    public function update(Request $request, CalendarEvent $event, LegalTaskCalendarService $taskCalendar): JsonResponse
    {
        $this->guardVisibleEvent($event);

        $event->fill($this->validatedData($request));
        $event->save();
        $taskCalendar->syncCalendarEventToTask($event);

        activity_log('calendar', 'updated', $event, $event->toArray(), 'Evento atualizado na agenda.');

        return response()->json([
            'message' => 'Evento atualizado com sucesso.',
            'calendarTarget' => '#admin-calendar',
            'tableTarget' => '#admin-calendar-events-table',
        ]);
    }

    public function move(Request $request, CalendarEvent $event, LegalTaskCalendarService $taskCalendar): JsonResponse
    {
        $this->guardVisibleEvent($event);

        $validated = $request->validate([
            'start_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'all_day' => ['nullable', 'boolean'],
        ]);

        if ($event->editable === false) {
            return response()->json(['message' => 'Este evento não permite movimentação.'], 422);
        }

        $event->fill([
            'start_at' => Carbon::parse($validated['start_at']),
            'end_at' => filled($validated['end_at'] ?? null) ? Carbon::parse($validated['end_at']) : null,
            'all_day' => $request->boolean('all_day'),
        ])->save();
        $taskCalendar->syncCalendarEventToTask($event);

        activity_log('calendar', 'moved', $event, $event->only(['start_at', 'end_at', 'all_day']), 'Evento reposicionado na agenda.');

        return response()->json(['message' => 'Agenda atualizada.']);
    }

    public function destroy(CalendarEvent $event): JsonResponse
    {
        $this->guardVisibleEvent($event);

        if ($event->legal_task_id) {
            return response()->json([
                'message' => 'Este evento representa um prazo jurídico. Cancele ou exclua a tarefa de origem.',
            ], 422);
        }

        $event->delete();

        activity_log('calendar', 'deleted', $event, [], 'Evento removido da agenda.');

        return response()->json([
            'message' => 'Evento removido com sucesso.',
            'calendarTarget' => '#admin-calendar',
            'tableTarget' => '#admin-calendar-events-table',
        ]);
    }

    private function formResponse(CalendarEvent $event, string $title): JsonResponse
    {
        return response()->json([
            'title' => $title,
            'html' => view('admin.calendar._form', [
                'record' => $event,
                'users' => $this->availableOwners(),
                'clients' => $this->availableClients(),
                'cases' => $this->availableCases(),
                'tasks' => $this->availableTasks($event),
                'canChooseOwner' => Auth::user()?->canViewAllLegalOperations(),
                'statuses' => self::STATUSES,
                'visibilities' => self::VISIBILITIES,
                'displays' => self::DISPLAYS,
                'eventTypes' => self::EVENT_TYPES,
            ])->render(),
        ]);
    }

    private function validatedData(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'url' => ['nullable', 'url', 'max:255'],
            'category' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(self::STATUSES)],
            'visibility' => ['required', Rule::in(self::VISIBILITIES)],
            'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'text_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'start_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'all_day' => ['nullable', 'boolean'],
            'editable' => ['nullable', 'boolean'],
            'overlap' => ['nullable', 'boolean'],
            'display' => ['required', Rule::in(self::DISPLAYS)],
            'owner_id' => ['nullable', 'integer', 'exists:users,id'],
            'client_id' => ['nullable', 'integer'],
            'legal_case_id' => ['nullable', 'integer'],
            'legal_task_id' => [
                'nullable',
                'integer',
                Rule::unique('calendar_events', 'legal_task_id')->ignore($request->route('event')?->id),
            ],
            'event_type' => ['nullable', Rule::in(self::EVENT_TYPES)],
            'reminder_minutes' => ['nullable', 'integer', 'min:0', 'max:40320'],
            'shared_with_client' => ['nullable', 'boolean'],
            'google_sync_enabled' => ['nullable', 'boolean'],
            'extended_props_text' => [
                'nullable',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (blank($value)) {
                        return;
                    }

                    json_decode((string) $value, true);

                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $fail('O JSON informado não é válido.');
                    }
                },
            ],
        ]);

        $validated['start_at'] = Carbon::parse($validated['start_at']);
        $validated['end_at'] = filled($validated['end_at'] ?? null) ? Carbon::parse($validated['end_at']) : null;
        $validated['all_day'] = $request->boolean('all_day');
        $validated['editable'] = $request->boolean('editable');
        $validated['overlap'] = $request->boolean('overlap');
        $validated['shared_with_client'] = $request->boolean('shared_with_client');
        $validated['google_sync_enabled'] = $request->boolean('google_sync_enabled');
        $validated['event_type'] = $validated['event_type'] ?? $request->route('event')?->event_type ?? 'appointment';
        $validated['extended_props'] = filled($validated['extended_props_text'] ?? null)
            ? json_decode((string) $validated['extended_props_text'], true)
            : null;

        $visibleClientIds = $this->availableClients()->pluck('id')->map(fn ($id) => (int) $id);
        $visibleCaseIds = $this->availableCases()->pluck('id')->map(fn ($id) => (int) $id);
        $visibleTaskIds = $this->availableTasks($request->route('event'))->pluck('id')->map(fn ($id) => (int) $id);

        if (filled($validated['client_id'] ?? null) && ! $visibleClientIds->contains((int) $validated['client_id'])) {
            throw ValidationException::withMessages(['client_id' => 'O cliente selecionado não está disponível para este usuário.']);
        }

        $legalCase = null;
        if (filled($validated['legal_case_id'] ?? null)) {
            if (! $visibleCaseIds->contains((int) $validated['legal_case_id'])) {
                throw ValidationException::withMessages(['legal_case_id' => 'O processo selecionado não está disponível para este usuário.']);
            }

            $legalCase = LegalCase::query()->find($validated['legal_case_id']);
            if (filled($validated['client_id'] ?? null) && (int) $validated['client_id'] !== (int) $legalCase?->client_id) {
                throw ValidationException::withMessages(['client_id' => 'O cliente informado não pertence ao processo selecionado.']);
            }
            $validated['client_id'] = $legalCase?->client_id;
        }

        if (filled($validated['legal_task_id'] ?? null)) {
            if (! $visibleTaskIds->contains((int) $validated['legal_task_id'])) {
                throw ValidationException::withMessages(['legal_task_id' => 'O prazo selecionado não está disponível para este usuário.']);
            }

            $task = LegalTask::query()->find($validated['legal_task_id']);
            $validated['legal_case_id'] = $task?->legal_case_id;
            $validated['client_id'] = $task?->legal_case_id
                ? LegalCase::query()->whereKey($task->legal_case_id)->value('client_id')
                : $task?->client_id;
            $validated['owner_id'] = $task?->assigned_user_id;
            $validated['event_type'] = $task?->task_type ?: $validated['event_type'];
            $validated['source'] = 'legal_task';
        } else {
            $validated['source'] = $request->route('event')?->source ?: 'manual';
        }

        if ($validated['shared_with_client'] && blank($validated['client_id'] ?? null)) {
            throw ValidationException::withMessages(['shared_with_client' => 'Selecione um cliente antes de compartilhar o evento no portal.']);
        }

        if (! Auth::user()?->canViewAllLegalOperations()) {
            $validated['owner_id'] = Auth::id();
        }

        unset($validated['extended_props_text']);

        return $validated;
    }

    private function calendarPayload(CalendarEvent $event): array
    {
        $statusColors = [
            'scheduled' => '#c49a3c',
            'confirmed' => '#198754',
            'done' => '#3b82f6',
            'canceled' => '#dc3545',
        ];

        $extendedProps = is_array($event->extended_props) ? $event->extended_props : [];
        $advancedProps = collect($extendedProps)
            ->only([
                'className',
                'classNames',
                'constraint',
                'daysOfWeek',
                'duration',
                'endRecur',
                'endTime',
                'groupId',
                'startRecur',
                'startTime',
            ])
            ->all();

        $status = $event->status ?: 'scheduled';
        $display = $event->display ?: 'auto';
        $visibility = $event->visibility ?: 'team';
        $editable = $event->editable ?? true;
        $overlap = $event->overlap ?? true;

        return [
            'id' => (string) $event->id,
            'title' => $event->title,
            'start' => $event->start_at?->toIso8601String(),
            'end' => $event->end_at?->toIso8601String(),
            'allDay' => $event->all_day,
            'color' => $event->color ?: ($statusColors[$status] ?? '#c49a3c'),
            'textColor' => $event->text_color ?: '#111318',
            'editable' => $editable,
            'startEditable' => $editable,
            'durationEditable' => $editable,
            'overlap' => $overlap,
            'display' => $display,
            'classNames' => [
                'admin-calendar-event-pill',
                'admin-calendar-display-'.$display,
            ],
            'extendedProps' => $extendedProps + [
                'status' => $status,
                'statusLabel' => $this->statusLabel($status),
                'visibility' => $visibility,
                'visibilityLabel' => $this->visibilityLabel($visibility),
                'display' => $display,
                'displayLabel' => $this->displayLabel($display),
                'hasCustomColor' => filled($event->getRawOriginal('color')),
                'category' => $event->category,
                'location' => $event->location,
                'description' => strip_tags((string) $event->description),
                'owner' => $event->owner?->name,
                'createdBy' => $event->creator?->name,
                'client' => $event->client?->name,
                'clientId' => $event->client_id,
                'legalCase' => $event->legalCase?->title,
                'legalCaseId' => $event->legal_case_id,
                'legalTaskId' => $event->legal_task_id,
                'eventType' => $event->event_type,
                'sharedWithClient' => (bool) $event->shared_with_client,
                'googleSyncEnabled' => (bool) $event->google_sync_enabled,
                'externalUrl' => $event->url,
                'editUrl' => route('admin.calendar.edit', $event),
                'moveUrl' => route('admin.calendar.move', $event),
                'deleteUrl' => route('admin.calendar.destroy', $event),
            ],
        ] + $advancedProps;
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'scheduled' => 'Agendado',
            'confirmed' => 'Confirmado',
            'done' => 'Concluído',
            'canceled' => 'Cancelado',
            default => ucfirst($status),
        };
    }

    private function visibilityLabel(string $visibility): string
    {
        return match ($visibility) {
            'private' => 'Privado',
            'team' => 'Equipe',
            'public' => 'Público',
            default => ucfirst($visibility),
        };
    }

    private function displayLabel(string $display): string
    {
        return match ($display) {
            'auto' => 'Evento normal',
            'background' => 'Marcação de fundo',
            'inverse-background' => 'Bloqueio invertido',
            default => ucfirst($display),
        };
    }

    private function availableOwners()
    {
        return User::query()
            ->visibleTo(Auth::user())
            ->where('is_active', true)
            ->when(
                ! Auth::user()?->canViewAllLegalOperations(),
                fn (Builder $query) => $query->whereKey(Auth::id())
            )
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function visibleEventsQuery(): Builder
    {
        return CalendarEvent::query()->visibleTo(Auth::user());
    }

    private function applySearchFilter(Builder $query, ?string $search): void
    {
        $search = trim((string) $search);

        if ($search === '') {
            return;
        }

        $query->where(function (Builder $builder) use ($search): void {
            foreach (['title', 'description', 'location', 'category'] as $index => $field) {
                $method = $index === 0 ? 'where' : 'orWhere';
                $builder->{$method}($field, 'like', "%{$search}%");
            }
        });
    }

    private function applyAttributeFilters(Builder $query, array $validated): void
    {
        foreach (['category', 'status', 'visibility', 'owner_id', 'display', 'client_id', 'legal_case_id', 'event_type'] as $field) {
            if (filled($validated[$field] ?? null)) {
                $query->where($field, $validated[$field]);
            }
        }
    }

    private function applyRangeFilter(Builder $query, ?Carbon $rangeStart = null, ?Carbon $rangeEnd = null): void
    {
        if (! $rangeStart && ! $rangeEnd) {
            return;
        }

        if ($rangeEnd) {
            $query->where('start_at', '<', $rangeEnd);
        }

        if (! $rangeStart) {
            return;
        }

        $query->where(function (Builder $builder) use ($rangeStart): void {
            $builder
                ->where(function (Builder $instantaneous) use ($rangeStart): void {
                    $instantaneous
                        ->whereNull('end_at')
                        ->where('start_at', '>=', $rangeStart);
                })
                ->orWhere(function (Builder $ranged) use ($rangeStart): void {
                    $ranged
                        ->whereNotNull('end_at')
                        ->where('end_at', '>', $rangeStart);
                });
        });
    }

    private function guardVisibleEvent(CalendarEvent $event): void
    {
        if (Auth::user()?->canViewAllLegalOperations()) {
            return;
        }

        if ((int) $event->owner_id === (int) Auth::id()) {
            return;
        }

        if ($event->owner_id === null && (int) $event->created_by === (int) Auth::id()) {
            return;
        }

        abort(404);
    }

    private function availableClients()
    {
        return Client::query()
            ->visibleTo(Auth::user())
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function availableCases()
    {
        return LegalCase::query()
            ->visibleTo(Auth::user())
            ->where('is_active', true)
            ->orderBy('title')
            ->get(['id', 'client_id', 'title', 'process_number']);
    }

    private function availableTasks(?CalendarEvent $event = null)
    {
        return LegalTask::query()
            ->visibleTo(Auth::user())
            ->whereNotIn('status', ['done', 'canceled'])
            ->where(function (Builder $query) use ($event): void {
                $query
                    ->whereDoesntHave('calendarEvent')
                    ->when($event?->legal_task_id, fn (Builder $nested) => $nested->orWhereKey($event->legal_task_id));
            })
            ->orderByRaw('due_at is null, due_at asc')
            ->get(['id', 'legal_case_id', 'client_id', 'assigned_user_id', 'title', 'task_type', 'due_at']);
    }

    private function eventRelations(): array
    {
        return [
            'owner:id,name',
            'creator:id,name',
            'client:id,name',
            'legalCase:id,client_id,title,process_number',
            'legalTask:id,title',
        ];
    }
}
