<?php

namespace Tests\Feature\Portal;

use App\Models\Client;
use App\Models\LegalCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ClientPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_log_into_portal_and_view_own_case(): void
    {
        $client = Client::query()->create([
            'person_type' => 'individual',
            'name' => 'Cliente Portal',
            'document_number' => '123.456.789-09',
            'portal_enabled' => true,
            'portal_access_code' => Hash::make('PORTAL123'),
            'is_active' => true,
        ]);

        $legalCase = LegalCase::query()->create([
            'client_id' => $client->id,
            'title' => 'Ação de cobrança',
            'process_number' => '1000000-10.2026.8.26.0100',
            'status' => 'active',
            'phase' => 'initial',
            'priority' => 'medium',
            'portal_visible' => true,
            'is_confidential' => true,
            'is_active' => true,
        ]);

        $this->post(route('portal.authenticate'), [
            'document_number' => '12345678909',
            'access_code' => 'PORTAL123',
        ])->assertRedirect(route('portal.dashboard'));

        $this->get(route('portal.dashboard'))
            ->assertOk()
            ->assertSee('Cliente Portal')
            ->assertSee('Ação de cobrança');

        $this->get(route('portal.cases.show', $legalCase->id))
            ->assertOk()
            ->assertSee('1000000-10.2026.8.26.0100');
    }

    public function test_client_cannot_access_case_from_another_client(): void
    {
        $clientA = Client::query()->create([
            'person_type' => 'individual',
            'name' => 'Cliente A',
            'document_number' => '123.456.789-09',
            'portal_enabled' => true,
            'portal_access_code' => Hash::make('PORTAL123'),
            'is_active' => true,
        ]);

        $clientB = Client::query()->create([
            'person_type' => 'individual',
            'name' => 'Cliente B',
            'document_number' => '987.654.321-00',
            'portal_enabled' => true,
            'portal_access_code' => Hash::make('PORTAL321'),
            'is_active' => true,
        ]);

        $legalCase = LegalCase::query()->create([
            'client_id' => $clientB->id,
            'title' => 'Processo reservado',
            'status' => 'active',
            'phase' => 'initial',
            'priority' => 'medium',
            'portal_visible' => true,
            'is_confidential' => true,
            'is_active' => true,
        ]);

        $this->withSession(['portal_client_id' => $clientA->id])
            ->get(route('portal.cases.show', $legalCase->id))
            ->assertNotFound();
    }

    public function test_profile_fields_marked_in_admin_can_be_edited_even_with_legacy_global_toggle_disabled(): void
    {
        $client = Client::query()->create([
            'person_type' => 'individual',
            'name' => 'Cliente Editável',
            'document_number' => '123.456.789-09',
            'email' => 'cliente@exemplo.com',
            'phone' => '(11) 3333-4444',
            'portal_enabled' => true,
            'portal_profile_update_allowed' => false,
            'portal_access_code' => Hash::make('PORTAL123'),
            'is_active' => true,
            'metadata' => [
                'portal_editable_fields' => ['name', 'email', 'phone'],
            ],
        ]);

        $this->withSession(['portal_client_id' => $client->id])
            ->put(route('portal.profile.update'), [
                'name' => 'Cliente Atualizado',
                'email' => 'novo@exemplo.com',
                'phone' => '(11) 99999-0000',
            ])
            ->assertRedirect(route('portal.profile'));

        $client->refresh();

        $this->assertSame('Cliente Atualizado', $client->name);
        $this->assertSame('novo@exemplo.com', $client->email);
        $this->assertSame('(11) 99999-0000', $client->phone);
    }
}
