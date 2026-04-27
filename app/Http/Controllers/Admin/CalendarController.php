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

        return view('admin.calendar.index', [
            'pageTitle' => 'Agenda',
            'users' => $this->availableOwners(),
            'statuses' => self::STATUSES,
            'visibilities' => self::VISIBILITIES,
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
                'scheduled' => (clone $eventsQuery)->where('status', 'scheduled')->count(),
                'confirmed' => (clone $eventsQuery)->where('status', 'confirmed')->count(),
            ],
        ]);
    }

    public function events(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start' => ['nullable', 'date'],
            'end' => ['nullable', 'date'],
            'category' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(self::STATUSES)],
            'visibility' => ['nullable', Rule::in(self::VISIBILITIES)],
            'owner_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $query = $this->visibleEventsQuery()->with(['owner:id,name', 'creator:id,name']);

        if (! empty($validated['start'])) {
            $query->where(function ($builder) use ($validated): void {
                $builder->whereNull('end_at')->where('start_at', '>=', Carbon::parse($validated['start']))
                    ->orWhere('end_at', '>=', Carbon::parse($validated['start']));
            });
        }

        if (! empty($validated['end'])) {
            $query->where('start_at', '<=', Carbon::parse($validated['end']));
        }

        foreach (['category', 'status', 'visibility', 'owner_id'] as $field) {
            if (filled($validated[$field] ?? null)) {
                $query->where($field, $validated[$field]);
            }
        }

        return response()->json($query
            ->orderBy('start_at')
            ->get()
            ->map(fn (CalendarEvent $event): array => $this->calendarPayload($event))
            ->values());
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

        if (! $event->editable) {
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

        $extendedProps = $event->extended_props ?? [];
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

        return [
            'id' => (string) $event->id,
            'title' => $event->title,
            'start' => $event->start_at?->toIso8601String(),
            'end' => $event->end_at?->toIso8601String(),
            'allDay' => $event->all_day,
            'color' => $event->color ?: ($statusColors[$event->status] ?? '#c49a3c'),
            'textColor' => $event->text_color ?: '#111318',
            'editable' => $event->editable,
            'overlap' => $event->overlap,
            'display' => $event->display,
            'rendering' => in_array($event->display, ['background', 'inverse-background'], true) ? $event->display : null,
            'classNames' => [
                'admin-calendar-event-pill',
                'admin-calendar-status-'.$event->status,
            ],
            'extendedProps' => $extendedProps + [
                'status' => $event->status,
                'visibility' => $event->visibility,
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

    private function availableOwners()
    {
        return User::query()
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
