<?php

namespace Tests\Feature\Admin;

use App\Jobs\SendLegalTaskReminder;
use App\Jobs\SendDailyLegalDeadlineSummary;
use App\Models\CalendarEvent;
use App\Models\Client;
use App\Models\GoogleCalendarConnection;
use App\Models\GoogleCalendarEventMapping;
use App\Models\LegalCase;
use App\Models\LegalDeadlinePreference;
use App\Models\LegalNotificationDelivery;
use App\Models\LegalTask;
use App\Models\User;
use App\Services\GoogleCalendarSyncService;
use App\Services\GoogleCalendarOAuthService;
use App\Services\LegalDeadlineNotificationService;
use App\Services\LegalTaskCalendarService;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class LegalDeadlineCalendarIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_legal_task_has_history_and_one_canonical_calendar_event(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $client = $this->client($user, 'Cliente do prazo');
        $case = $this->legalCase($client, $user, 'Processo do prazo');

        $task = LegalTask::query()->create([
            'legal_case_id' => $case->id,
            'client_id' => $client->id,
            'assigned_user_id' => $user->id,
            'title' => 'Protocolar manifestação',
            'task_type' => 'deadline',
            'priority' => 'urgent',
            'status' => 'pending',
            'start_at' => now()->addDay()->startOfHour(),
            'due_at' => now()->addDay()->startOfHour()->addHour(),
            'reminder_minutes' => 60,
            'created_by' => $user->id,
        ]);

        $this->assertDatabaseHas('legal_task_histories', [
            'legal_task_id' => $task->id,
            'action' => 'created',
        ]);
        $this->assertDatabaseHas('calendar_events', [
            'legal_task_id' => $task->id,
            'client_id' => $client->id,
            'legal_case_id' => $case->id,
            'source' => 'legal_task',
        ]);

        $event = $task->calendarEvent()->firstOrFail();
        $event->forceFill([
            'title' => 'Prazo reposicionado pela agenda',
            'start_at' => now()->addDays(2)->startOfHour(),
            'end_at' => now()->addDays(2)->startOfHour()->addHours(2),
        ])->save();
        app(LegalTaskCalendarService::class)->syncCalendarEventToTask($event);

        $this->assertSame('Prazo reposicionado pela agenda', $task->refresh()->title);
        $this->assertDatabaseHas('legal_task_histories', [
            'legal_task_id' => $task->id,
            'action' => 'updated',
            'source' => 'calendar',
        ]);
        $this->assertSame(1, CalendarEvent::query()->where('legal_task_id', $task->id)->count());
    }

    public function test_legal_task_rejects_client_that_does_not_belong_to_selected_case(): void
    {
        $this->seed(PermissionsSeeder::class);
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('Administrador');
        $caseClient = $this->client($admin, 'Cliente do processo');
        $otherClient = $this->client($admin, 'Cliente incompatível');
        $case = $this->legalCase($caseClient, $admin, 'Processo controlado');

        $this->actingAs($admin)
            ->postJson(route('admin.legal-tasks.store'), [
                'legal_case_id' => $case->id,
                'client_id' => $otherClient->id,
                'assigned_user_id' => $admin->id,
                'title' => 'Prazo inválido',
                'task_type' => 'deadline',
                'priority' => 'high',
                'status' => 'pending',
                'due_at' => now()->addDay()->toIso8601String(),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('client_id');

        $this->assertDatabaseMissing('legal_tasks', ['title' => 'Prazo inválido']);
    }

    public function test_deadline_filters_separate_today_tomorrow_and_overdue(): void
    {
        $this->seed(PermissionsSeeder::class);
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('Administrador');

        foreach ([
            ['Prazo de hoje', now()->setTime(15, 0)],
            ['Prazo de amanhã', now()->addDay()->setTime(15, 0)],
            ['Prazo vencido', now()->subDay()->setTime(15, 0)],
        ] as [$title, $dueAt]) {
            LegalTask::query()->create([
                'assigned_user_id' => $admin->id,
                'title' => $title,
                'task_type' => 'deadline',
                'priority' => 'medium',
                'status' => 'pending',
                'due_at' => $dueAt,
                'created_by' => $admin->id,
            ]);
        }

        $this->actingAs($admin)
            ->get(route('admin.legal-tasks.index', ['due_scope' => 'today']))
            ->assertOk()
            ->assertSee('Prazo de hoje')
            ->assertDontSee('Prazo de amanhã')
            ->assertDontSee('Prazo vencido');
    }

    public function test_deadline_reminder_dispatch_is_idempotent(): void
    {
        Queue::fake();
        $user = User::factory()->create(['is_active' => true]);
        $task = LegalTask::query()->create([
            'assigned_user_id' => $user->id,
            'title' => 'Prazo com lembrete',
            'task_type' => 'deadline',
            'priority' => 'high',
            'status' => 'pending',
            'due_at' => now()->addMinutes(30),
            'reminder_minutes' => 60,
            'created_by' => $user->id,
        ]);
        $service = app(LegalDeadlineNotificationService::class);

        $this->assertSame(1, $service->queueDueReminders(now()));
        $this->assertSame(0, $service->queueDueReminders(now()));
        Queue::assertPushed(SendLegalTaskReminder::class, 1);
        $this->assertSame(1, LegalNotificationDelivery::query()->where('legal_task_id', $task->id)->count());
    }

    public function test_daily_summary_dispatch_is_configurable_and_idempotent(): void
    {
        Queue::fake();
        $user = User::factory()->create(['is_active' => true, 'timezone' => 'America/Sao_Paulo']);
        LegalDeadlinePreference::query()->create([
            'user_id' => $user->id,
            'deadline_reminders_enabled' => true,
            'daily_summary_enabled' => true,
            'daily_summary_time' => '00:01:00',
            'daily_summary_days_ahead' => 10,
            'timezone' => 'America/Sao_Paulo',
        ]);
        $reference = now('America/Sao_Paulo')->setTime(8, 0);
        $service = app(LegalDeadlineNotificationService::class);

        $this->assertSame(1, $service->queueDailySummaries($reference));
        $this->assertSame(0, $service->queueDailySummaries($reference));
        Queue::assertPushed(SendDailyLegalDeadlineSummary::class, 1);
    }

    public function test_google_access_token_is_refreshed_and_persisted_encrypted(): void
    {
        config()->set('google-calendar.client_id', 'client-id');
        config()->set('google-calendar.client_secret', 'client-secret');
        $user = User::factory()->create(['is_active' => true]);
        $connection = GoogleCalendarConnection::query()->create([
            'user_id' => $user->id,
            'access_token' => 'expired-access-token',
            'refresh_token' => 'refresh-token-secret',
            'token_expires_at' => now()->subMinute(),
            'calendar_id' => 'primary',
            'sync_enabled' => true,
        ]);
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'fresh-access-token',
                'expires_in' => 3600,
                'scope' => 'https://www.googleapis.com/auth/calendar.events',
            ]),
        ]);

        $this->assertSame('fresh-access-token', app(GoogleCalendarOAuthService::class)->accessToken($connection));
        $rawToken = GoogleCalendarConnection::query()->getQuery()->where('id', $connection->id)->value('access_token');
        $this->assertNotSame('fresh-access-token', $rawToken);
        $this->assertSame('fresh-access-token', $connection->fresh()->access_token);
    }

    public function test_google_sync_upserts_mapping_without_duplicates(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $connection = GoogleCalendarConnection::query()->create([
            'user_id' => $user->id,
            'google_account_email' => 'agenda@example.test',
            'access_token' => 'access-token-secret',
            'refresh_token' => 'refresh-token-secret',
            'token_expires_at' => now()->addHour(),
            'calendar_id' => 'primary',
            'calendar_name' => 'Principal',
            'sync_enabled' => true,
        ]);
        $event = CalendarEvent::query()->create([
            'title' => 'Audiência sincronizada',
            'category' => 'Audiência',
            'event_type' => 'hearing',
            'status' => 'scheduled',
            'visibility' => 'private',
            'google_sync_enabled' => true,
            'source' => 'manual',
            'start_at' => now()->addDay(),
            'end_at' => now()->addDay()->addHour(),
            'owner_id' => $user->id,
            'created_by' => $user->id,
        ]);

        Http::fake(function (ClientRequest $request) {
            if ($request->method() === 'POST') {
                return Http::response([
                    'id' => 'google-event-1',
                    'iCalUID' => 'google-event-1@example.test',
                    'etag' => 'etag-1',
                    'updated' => now()->toRfc3339String(),
                ]);
            }

            return Http::response(['items' => [], 'nextSyncToken' => 'sync-token-1']);
        });

        $service = app(GoogleCalendarSyncService::class);
        $first = $service->sync($connection);
        $second = $service->sync($connection->fresh());

        $this->assertSame(1, $first['pushed']);
        $this->assertSame(0, $second['pushed']);
        $this->assertSame(1, GoogleCalendarEventMapping::query()->where('calendar_event_id', $event->id)->count());
        $this->assertNotSame('access-token-secret', GoogleCalendarConnection::query()->getQuery()->where('id', $connection->id)->value('access_token'));
    }

    public function test_portal_shows_only_events_explicitly_shared_with_authenticated_client(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $clientA = $this->client($user, 'Cliente A', true);
        $clientB = $this->client($user, 'Cliente B', true);

        CalendarEvent::query()->create([
            'client_id' => $clientA->id,
            'title' => 'Reunião compartilhada A',
            'category' => 'Reunião',
            'event_type' => 'meeting',
            'status' => 'confirmed',
            'visibility' => 'private',
            'shared_with_client' => true,
            'start_at' => now()->addDay(),
            'created_by' => $user->id,
        ]);
        CalendarEvent::query()->create([
            'client_id' => $clientB->id,
            'title' => 'Reunião confidencial B',
            'category' => 'Reunião',
            'event_type' => 'meeting',
            'status' => 'confirmed',
            'visibility' => 'private',
            'shared_with_client' => true,
            'start_at' => now()->addDay(),
            'created_by' => $user->id,
        ]);

        $this->withSession(['portal_client_id' => $clientA->id])
            ->get(route('portal.dashboard'))
            ->assertOk()
            ->assertSee('Reunião compartilhada A')
            ->assertDontSee('Reunião confidencial B');
    }

    private function client(User $user, string $name, bool $portalEnabled = false): Client
    {
        return Client::query()->create([
            'person_type' => 'individual',
            'name' => $name,
            'assigned_lawyer_id' => $user->id,
            'created_by' => $user->id,
            'is_active' => true,
            'portal_enabled' => $portalEnabled,
        ]);
    }

    private function legalCase(Client $client, User $user, string $title): LegalCase
    {
        return LegalCase::query()->create([
            'client_id' => $client->id,
            'title' => $title,
            'primary_lawyer_id' => $user->id,
            'status' => 'active',
            'phase' => 'initial',
            'priority' => 'medium',
            'is_active' => true,
            'portal_visible' => true,
            'created_by' => $user->id,
        ]);
    }
}
