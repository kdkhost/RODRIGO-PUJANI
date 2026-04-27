<?php

namespace Tests\Feature\Admin;

use App\Models\CalendarEvent;
use App\Models\Client;
use App\Models\LegalCase;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_associated_lawyer_sees_only_own_clients(): void
    {
        $this->seed(PermissionsSeeder::class);

        $lawyerA = User::factory()->create(['is_active' => true]);
        $lawyerB = User::factory()->create(['is_active' => true]);
        $lawyerA->assignRole('Advogado Associado');
        $lawyerB->assignRole('Advogado Associado');

        Client::query()->create([
            'person_type' => 'individual',
            'name' => 'Cliente da Dra. A',
            'assigned_lawyer_id' => $lawyerA->id,
            'created_by' => $lawyerA->id,
            'is_active' => true,
        ]);

        Client::query()->create([
            'person_type' => 'individual',
            'name' => 'Cliente do Dr. B',
            'assigned_lawyer_id' => $lawyerB->id,
            'created_by' => $lawyerB->id,
            'is_active' => true,
        ]);

        $this->actingAs($lawyerA)
            ->get(route('admin.clients.index'))
            ->assertOk()
            ->assertSee('Cliente da Dra. A')
            ->assertDontSee('Cliente do Dr. B');
    }

    public function test_associated_lawyer_cannot_open_case_from_another_portfolio(): void
    {
        $this->seed(PermissionsSeeder::class);

        $lawyerA = User::factory()->create(['is_active' => true]);
        $lawyerB = User::factory()->create(['is_active' => true]);
        $lawyerA->assignRole('Advogado Associado');
        $lawyerB->assignRole('Advogado Associado');

        $client = Client::query()->create([
            'person_type' => 'individual',
            'name' => 'Cliente externo',
            'assigned_lawyer_id' => $lawyerB->id,
            'created_by' => $lawyerB->id,
            'is_active' => true,
        ]);

        $case = LegalCase::query()->create([
            'client_id' => $client->id,
            'title' => 'Processo sigiloso',
            'primary_lawyer_id' => $lawyerB->id,
            'status' => 'active',
            'phase' => 'initial',
            'priority' => 'medium',
            'is_confidential' => true,
            'is_active' => true,
            'created_by' => $lawyerB->id,
        ]);

        $this->actingAs($lawyerA)
            ->get(route('admin.legal-cases.edit', $case))
            ->assertNotFound();
    }

    public function test_associated_lawyer_receives_only_own_calendar_events(): void
    {
        $this->seed(PermissionsSeeder::class);

        $lawyerA = User::factory()->create(['is_active' => true]);
        $lawyerB = User::factory()->create(['is_active' => true]);
        $lawyerA->assignRole('Advogado Associado');
        $lawyerB->assignRole('Advogado Associado');

        $eventA = CalendarEvent::query()->create([
            'title' => 'Audiência A',
            'category' => 'Audiência',
            'status' => 'scheduled',
            'visibility' => 'team',
            'start_at' => now()->addDay(),
            'end_at' => now()->addDay()->addHour(),
            'editable' => true,
            'overlap' => true,
            'display' => 'auto',
            'owner_id' => $lawyerA->id,
            'created_by' => $lawyerA->id,
        ]);

        CalendarEvent::query()->create([
            'title' => 'Audiência B',
            'category' => 'Audiência',
            'status' => 'scheduled',
            'visibility' => 'team',
            'start_at' => now()->addDays(2),
            'end_at' => now()->addDays(2)->addHour(),
            'editable' => true,
            'overlap' => true,
            'display' => 'auto',
            'owner_id' => $lawyerB->id,
            'created_by' => $lawyerB->id,
        ]);

        $response = $this->actingAs($lawyerA)
            ->getJson(route('admin.calendar.events', [
                'start' => now()->startOfDay()->toIso8601String(),
                'end' => now()->addDays(5)->endOfDay()->toIso8601String(),
            ]));

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonPath('0.id', (string) $eventA->id);
    }

    public function test_calendar_event_can_be_updated_and_deleted(): void
    {
        $this->seed(PermissionsSeeder::class);

        $admin = User::factory()->create(['is_active' => true]);
        $admin->givePermissionTo(['admin.access', 'calendar.manage']);

        $event = CalendarEvent::query()->create([
            'title' => 'Prazo inicial',
            'category' => 'Prazo',
            'status' => 'scheduled',
            'visibility' => 'team',
            'start_at' => now()->addDay(),
            'end_at' => now()->addDay()->addHour(),
            'editable' => true,
            'overlap' => true,
            'display' => 'auto',
            'owner_id' => $admin->id,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->putJson(route('admin.calendar.update', $event), [
                'title' => 'Prazo atualizado',
                'description' => '<p>Atualizado</p>',
                'location' => 'Fórum central',
                'url' => 'https://example.com/prazo',
                'category' => 'Prazo',
                'status' => 'confirmed',
                'visibility' => 'team',
                'color' => '#c49a3c',
                'text_color' => '#111318',
                'start_at' => now()->addDay()->toIso8601String(),
                'end_at' => now()->addDay()->addHour()->toIso8601String(),
                'all_day' => false,
                'editable' => true,
                'overlap' => true,
                'display' => 'auto',
                'owner_id' => $admin->id,
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Evento atualizado com sucesso.');

        $this->assertSame('Prazo atualizado', $event->refresh()->title);
        $this->assertSame('confirmed', $event->status);

        $this->actingAs($admin)
            ->deleteJson(route('admin.calendar.destroy', $event))
            ->assertOk()
            ->assertJsonPath('message', 'Evento removido com sucesso.');

        $this->assertDatabaseMissing('calendar_events', [
            'id' => $event->id,
        ]);
    }

    public function test_background_all_day_event_is_returned_in_feed_and_records(): void
    {
        $this->seed(PermissionsSeeder::class);

        $admin = User::factory()->create(['is_active' => true]);
        $admin->givePermissionTo(['admin.access', 'calendar.manage']);

        $event = CalendarEvent::query()->create([
            'title' => 'Plantão de fechamento',
            'description' => '<p>Agenda interna</p>',
            'category' => 'Operação',
            'status' => 'scheduled',
            'visibility' => 'team',
            'start_at' => '2026-04-27 00:00:00',
            'end_at' => '2026-04-28 00:00:00',
            'all_day' => true,
            'editable' => true,
            'overlap' => true,
            'display' => 'background',
            'created_by' => $admin->id,
        ]);

        $feedResponse = $this->actingAs($admin)
            ->getJson(route('admin.calendar.events', [
                'start' => '2026-04-01T00:00:00-03:00',
                'end' => '2026-05-01T00:00:00-03:00',
            ]));

        $feedResponse->assertOk();
        $feedResponse->assertJsonCount(1);
        $feedResponse->assertJsonPath('0.id', (string) $event->id);
        $feedResponse->assertJsonPath('0.display', 'background');

        $recordsResponse = $this->actingAs($admin)
            ->getJson(route('admin.calendar.records', [
                'date_from' => '2026-04-27',
                'date_to' => '2026-04-27',
            ]));

        $recordsResponse->assertOk();
        $this->assertStringContainsString('Plantão de fechamento', $recordsResponse->json('html'));
        $this->assertStringContainsString('Marcação de fundo', $recordsResponse->json('html'));
    }
}
