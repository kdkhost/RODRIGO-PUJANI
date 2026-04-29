<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CalendarController extends Controller
{
    private const STATUSES = ['scheduled', 'confirmed', 'done', 'canceled'];
    private const VISIBILITIES = ['private', 'team', 'public'];
    private const DISPLAYS = ['auto', 'background', 'inverse-background'];

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
            ->with(['owner:id,name', 'creator:id,name'])
            ->orderBy('start_at')
            ->paginate(10)
            ->withQueryString();

        return view('admin.calendar.index', [
            'pageTitle' => 'Agenda',
            'users' => $this->availableOwners(),
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
                ->with(['owner:id,name'])
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
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        $query = $this->visibleEventsQuery()->with(['owner:id,name', 'creator:id,name']);
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
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $query = $this->visibleEventsQuery()->with(['owner:id,name', 'creator:id,name']);
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
            'color' => '#c49a3c',
            'text_color' => '#111318',
            'display' => 'auto',
            'owner_id' => Auth::user()?->isAssociatedLawyer() ? Auth::id() : null,
        ]);

        return $this->formResponse($event, 'Novo evento');
    }

    public function store(Request $request): JsonResponse
    {
        $event = new CalendarEvent();
        $event->fill($this->validatedData($request));
        $event->created_by = Auth::id();
        $event->save();

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

    public function update(Request $request, CalendarEvent $event): JsonResponse
    {
        $this->guardVisibleEvent($event);

        $event->fill($this->validatedData($request));
        $event->save();

        activity_log('calendar', 'updated', $event, $event->toArray(), 'Evento atualizado na agenda.');

        return response()->json([
            'message' => 'Evento atualizado com sucesso.',
            'calendarTarget' => '#admin-calendar',
            'tableTarget' => '#admin-calendar-events-table',
        ]);
    }

    public function move(Request $request, CalendarEvent $event): JsonResponse
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

        activity_log('calendar', 'moved', $event, $event->only(['start_at', 'end_at', 'all_day']), 'Evento reposicionado na agenda.');

        return response()->json(['message' => 'Agenda atualizada.']);
    }

    public function destroy(CalendarEvent $event): JsonResponse
    {
        $this->guardVisibleEvent($event);

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
                'canChooseOwner' => ! Auth::user()?->isAssociatedLawyer(),
                'statuses' => self::STATUSES,
                'visibilities' => self::VISIBILITIES,
                'displays' => self::DISPLAYS,
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
        $validated['extended_props'] = filled($validated['extended_props_text'] ?? null)
            ? json_decode((string) $validated['extended_props_text'], true)
            : null;

        if (Auth::user()?->isAssociatedLawyer()) {
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
                'admin-calendar-status-'.$status,
                'admin-calendar-display-'.$display,
            ],
            'extendedProps' => $extendedProps + [
                'status' => $status,
                'statusLabel' => $this->statusLabel($status),
                'visibility' => $visibility,
                'visibilityLabel' => $this->visibilityLabel($visibility),
                'display' => $display,
                'displayLabel' => $this->displayLabel($display),
                'category' => $event->category,
                'location' => $event->location,
                'description' => strip_tags((string) $event->description),
                'owner' => $event->owner?->name,
                'createdBy' => $event->creator?->name,
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
                Auth::user()?->isAssociatedLawyer(),
                fn (Builder $query) => $query->whereKey(Auth::id())
            )
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function visibleEventsQuery(): Builder
    {
        return CalendarEvent::query()
            ->when(Auth::user()?->isAssociatedLawyer(), function (Builder $query): void {
                $query->where(function (Builder $builder): void {
                    $builder
                        ->where('owner_id', Auth::id())
                        ->orWhere(function (Builder $nested): void {
                            $nested
                                ->whereNull('owner_id')
                                ->where('created_by', Auth::id());
                        });
                });
            });
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
        foreach (['category', 'status', 'visibility', 'owner_id', 'display'] as $field) {
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
        if (! Auth::user()?->isAssociatedLawyer()) {
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
}
