<?php

namespace App\Services;

use App\Models\CalendarEvent;
use App\Models\GoogleCalendarConnection;
use App\Models\GoogleCalendarEventMapping;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class GoogleCalendarSyncService
{
    public function __construct(
        private readonly GoogleCalendarOAuthService $oauth,
        private readonly LegalTaskCalendarService $taskCalendar,
    ) {
    }

    public function sync(GoogleCalendarConnection $connection): array
    {
        if (! $connection->sync_enabled) {
            return ['pushed' => 0, 'pulled' => 0, 'canceled' => 0, 'skipped' => true];
        }

        try {
            $result = [
                'pushed' => $this->pushLocalEvents($connection),
                'pulled' => 0,
                'canceled' => 0,
                'skipped' => false,
            ];
            $remote = $this->pullRemoteEvents($connection);
            $result['pulled'] = $remote['pulled'];
            $result['canceled'] = $remote['canceled'];

            $connection->forceFill([
                'last_synced_at' => now(),
                'last_success_at' => now(),
                'last_failure_at' => null,
                'last_error' => null,
            ])->save();

            return $result;
        } catch (Throwable $exception) {
            $connection->forceFill([
                'last_synced_at' => now(),
                'last_failure_at' => now(),
                'last_error' => 'Falha sanitizada na sincronização ('.$exception::class.').',
            ])->save();

            throw $exception;
        }
    }

    private function pushLocalEvents(GoogleCalendarConnection $connection): int
    {
        $pushed = 0;

        $this->localEventsQuery($connection)
            ->with('googleCalendarMappings')
            ->orderBy('id')
            ->chunkById(100, function ($events) use ($connection, &$pushed): void {
                foreach ($events as $event) {
                    $mapping = GoogleCalendarEventMapping::query()
                        ->where('google_calendar_connection_id', $connection->id)
                        ->where('calendar_event_id', $event->id)
                        ->first();
                    $payload = $this->googlePayload($event, $connection);
                    $hash = hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

                    if ($mapping && hash_equals((string) $mapping->sync_hash, $hash)) {
                        continue;
                    }

                    $response = $mapping
                        ? $this->oauth->request($connection, 'PATCH', $this->eventPath($connection, $mapping->google_event_id), ['json' => $payload])
                        : $this->oauth->request($connection, 'POST', $this->eventsPath($connection), ['json' => $payload]);

                    if ($mapping && $response->status() === 404) {
                        $mapping->delete();
                        $mapping = null;
                        $response = $this->oauth->request($connection, 'POST', $this->eventsPath($connection), ['json' => $payload]);
                    }

                    $this->ensureSuccessful($response, 'Não foi possível publicar um evento no Google Calendar.');
                    $remote = (array) $response->json();

                    GoogleCalendarEventMapping::query()->updateOrCreate(
                        [
                            'google_calendar_connection_id' => $connection->id,
                            'google_event_id' => (string) ($remote['id'] ?? $mapping?->google_event_id),
                        ],
                        [
                            'calendar_event_id' => $event->id,
                            'google_ical_uid' => $remote['iCalUID'] ?? $mapping?->google_ical_uid,
                            'etag' => $remote['etag'] ?? null,
                            'sync_hash' => $hash,
                            'google_updated_at' => filled($remote['updated'] ?? null) ? Carbon::parse($remote['updated']) : now(),
                            'last_pushed_at' => now(),
                            'status' => 'active',
                            'metadata' => ['html_link' => $remote['htmlLink'] ?? null],
                        ],
                    );
                    $pushed++;
                }
            });

        return $pushed;
    }

    private function pullRemoteEvents(GoogleCalendarConnection $connection, bool $retryingFullSync = false): array
    {
        $pulled = 0;
        $canceled = 0;
        $pageToken = null;
        $nextSyncToken = null;

        do {
            $query = [
                'maxResults' => 2500,
                'showDeleted' => 'true',
                'singleEvents' => 'true',
                'pageToken' => $pageToken,
            ];

            if (filled($connection->sync_token)) {
                $query['syncToken'] = $connection->sync_token;
                unset($query['singleEvents']);
            } else {
                $query['timeMin'] = now()->subDays((int) config('google-calendar.initial_sync_past_days', 365))->toRfc3339String();
                $query['orderBy'] = 'startTime';
            }

            $response = $this->oauth->request($connection, 'GET', $this->eventsPath($connection), [
                'query' => array_filter($query, fn (mixed $value): bool => filled($value)),
            ]);

            if ($response->status() === 410 && ! $retryingFullSync) {
                $connection->forceFill(['sync_token' => null])->save();

                return $this->pullRemoteEvents($connection->fresh(), true);
            }

            $this->ensureSuccessful($response, 'Não foi possível importar os eventos do Google Calendar.');

            foreach ((array) $response->json('items', []) as $remote) {
                if (! is_array($remote) || blank($remote['id'] ?? null)) {
                    continue;
                }

                if (($remote['status'] ?? null) === 'cancelled') {
                    $canceled += $this->markRemoteCanceled($connection, (string) $remote['id']);
                    continue;
                }

                if (blank(data_get($remote, 'start.date')) && blank(data_get($remote, 'start.dateTime'))) {
                    continue;
                }

                $this->upsertRemoteEvent($connection, $remote);
                $pulled++;
            }

            $pageToken = $response->json('nextPageToken');
            $nextSyncToken = $response->json('nextSyncToken') ?: $nextSyncToken;
        } while (filled($pageToken));

        if (filled($nextSyncToken)) {
            $connection->forceFill(['sync_token' => $nextSyncToken])->save();
        }

        return compact('pulled', 'canceled');
    }

    private function upsertRemoteEvent(GoogleCalendarConnection $connection, array $remote): void
    {
        DB::transaction(function () use ($connection, $remote): void {
            $mapping = GoogleCalendarEventMapping::query()
                ->where('google_calendar_connection_id', $connection->id)
                ->where('google_event_id', $remote['id'])
                ->lockForUpdate()
                ->first();
            $event = $mapping?->calendarEvent;
            $linkedLocalId = data_get($remote, 'extendedProperties.private.pujani_event_id');

            if (! $event && filled($linkedLocalId)) {
                $event = $this->localEventsQuery($connection)->whereKey((int) $linkedLocalId)->first();
            }

            if (! $mapping && $event?->exists) {
                $mapping = GoogleCalendarEventMapping::query()
                    ->where('google_calendar_connection_id', $connection->id)
                    ->where('calendar_event_id', $event->id)
                    ->lockForUpdate()
                    ->first();
            }

            $event ??= new CalendarEvent([
                'owner_id' => $connection->user_id,
                'created_by' => $connection->user_id,
                'visibility' => 'private',
                'editable' => true,
                'overlap' => true,
                'display' => 'auto',
                'source' => 'google',
                'google_sync_enabled' => true,
            ]);

            [$startAt, $endAt, $allDay] = $this->parseRemoteDates($remote);
            $event->fill([
                'title' => trim((string) ($remote['summary'] ?? 'Evento do Google')) ?: 'Evento do Google',
                'description' => (string) ($remote['description'] ?? ''),
                'location' => $remote['location'] ?? null,
                'url' => $remote['htmlLink'] ?? null,
                'category' => $event->category ?: 'Google Calendar',
                'event_type' => $event->event_type ?: 'appointment',
                'status' => $this->localStatus($remote),
                'start_at' => $startAt,
                'end_at' => $endAt,
                'all_day' => $allDay,
                'owner_id' => $connection->user_id,
                'source' => 'google',
                'google_sync_enabled' => true,
                'extended_props' => array_merge($event->extended_props ?? [], [
                    'google_event_id' => $remote['id'],
                    'google_ical_uid' => $remote['iCalUID'] ?? null,
                    'google_creator' => data_get($remote, 'creator.email'),
                ]),
            ]);
            $event->save();

            $payloadHash = hash('sha256', json_encode($this->googlePayload($event, $connection), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $mapping ??= new GoogleCalendarEventMapping([
                'google_calendar_connection_id' => $connection->id,
            ]);
            $mapping->forceFill([
                    'google_event_id' => $remote['id'],
                    'calendar_event_id' => $event->id,
                    'google_ical_uid' => $remote['iCalUID'] ?? null,
                    'etag' => $remote['etag'] ?? null,
                    'sync_hash' => $payloadHash,
                    'google_updated_at' => filled($remote['updated'] ?? null) ? Carbon::parse($remote['updated']) : null,
                    'last_pulled_at' => now(),
                    'status' => 'active',
                    'metadata' => ['html_link' => $remote['htmlLink'] ?? null],
                ])->save();

            if ($event->legal_task_id) {
                $this->taskCalendar->syncCalendarEventToTask($event);
            }
        });
    }

    private function markRemoteCanceled(GoogleCalendarConnection $connection, string $googleEventId): int
    {
        $mapping = GoogleCalendarEventMapping::query()
            ->where('google_calendar_connection_id', $connection->id)
            ->where('google_event_id', $googleEventId)
            ->first();

        if (! $mapping) {
            return 0;
        }

        $mapping->update(['status' => 'canceled', 'last_pulled_at' => now()]);
        $mapping->calendarEvent?->update(['status' => 'canceled']);

        return 1;
    }

    private function localEventsQuery(GoogleCalendarConnection $connection): Builder
    {
        return CalendarEvent::query()
            ->where('google_sync_enabled', true)
            ->where('status', '!=', 'canceled')
            ->where(function (Builder $query) use ($connection): void {
                $query
                    ->where('owner_id', $connection->user_id)
                    ->orWhere(function (Builder $nested) use ($connection): void {
                        $nested
                            ->whereNull('owner_id')
                            ->where('created_by', $connection->user_id);
                    });
            });
    }

    private function googlePayload(CalendarEvent $event, GoogleCalendarConnection $connection): array
    {
        $timezone = $connection->user?->timezone ?: config('app.timezone');
        $start = $event->start_at ?: now();
        $end = $event->end_at ?: $start->copy()->addMinutes(30);
        $datePayload = $event->all_day
            ? [
                'start' => ['date' => $start->toDateString()],
                'end' => ['date' => ($event->end_at ?: $start->copy()->addDay())->toDateString()],
            ]
            : [
                'start' => ['dateTime' => $start->toRfc3339String(), 'timeZone' => $timezone],
                'end' => ['dateTime' => $end->toRfc3339String(), 'timeZone' => $timezone],
            ];

        return array_filter([
            'summary' => $event->title,
            'description' => trim(strip_tags((string) $event->description)),
            'location' => $event->location,
            'visibility' => match ($event->visibility) {
                'private' => 'private',
                'public' => 'public',
                default => 'default',
            },
            'status' => $event->status === 'canceled' ? 'cancelled' : 'confirmed',
            'extendedProperties' => [
                'private' => [
                    'pujani_event_id' => (string) $event->id,
                    'pujani_source' => (string) ($event->source ?: 'manual'),
                ],
            ],
            'reminders' => filled($event->reminder_minutes)
                ? ['useDefault' => false, 'overrides' => [['method' => 'popup', 'minutes' => min(40320, (int) $event->reminder_minutes)]]]
                : ['useDefault' => true],
        ] + $datePayload, fn (mixed $value): bool => $value !== null && $value !== '');
    }

    private function parseRemoteDates(array $remote): array
    {
        $allDay = filled(data_get($remote, 'start.date'));
        $startAt = Carbon::parse((string) data_get($remote, $allDay ? 'start.date' : 'start.dateTime'));
        $endValue = data_get($remote, $allDay ? 'end.date' : 'end.dateTime');
        $endAt = filled($endValue) ? Carbon::parse((string) $endValue) : null;

        return [$startAt, $endAt, $allDay];
    }

    private function localStatus(array $remote): string
    {
        return ($remote['status'] ?? null) === 'cancelled' ? 'canceled' : 'confirmed';
    }

    private function eventsPath(GoogleCalendarConnection $connection): string
    {
        return '/calendars/'.rawurlencode($connection->calendar_id ?: 'primary').'/events';
    }

    private function eventPath(GoogleCalendarConnection $connection, string $eventId): string
    {
        return $this->eventsPath($connection).'/'.rawurlencode($eventId);
    }

    private function ensureSuccessful(Response $response, string $message): void
    {
        if (! $response->successful()) {
            throw new RuntimeException($message.' HTTP '.$response->status().'.');
        }
    }
}
