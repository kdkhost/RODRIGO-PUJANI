<?php

namespace Tests\Feature\Admin;

use App\Models\Client;
use App\Models\DjenPublication;
use App\Models\LegalCase;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LegalCaseDjenSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_imports_public_djen_communications_for_review_without_duplicating_or_exposing_them(): void
    {
        $this->seed(PermissionsSeeder::class);
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('Administrador');
        $client = Client::query()->create(['person_type' => 'individual', 'name' => 'Cliente DJEN', 'is_active' => true]);
        $legalCase = LegalCase::query()->create([
            'client_id' => $client->id,
            'title' => 'Processo DJEN',
            'process_number' => '10000001020268260100',
            'status' => 'active',
            'phase' => 'initial',
            'priority' => 'medium',
            'portal_visible' => true,
            'is_confidential' => true,
            'is_active' => true,
        ]);

        Http::fake(['https://comunicaapi.pje.jus.br/api/v1/comunicacao*' => Http::response([
            'status' => 'success',
            'count' => 1,
            'items' => [[
                'hash' => 'hash-publicacao-1',
                'numeroComunicacao' => 123,
                'numero_processo' => '10000001020268260100',
                'data_disponibilizacao' => '2026-08-20',
                'siglaTribunal' => 'TJSP',
                'tipoComunicacao' => 'Intimação',
                'nomeOrgao' => '1ª Vara',
                'texto' => '<script>alert(1)</script> Prazo iniciado.',
            ]],
        ], 200)]);

        $url = route('admin.legal-cases.sync-djen', $legalCase);
        $this->actingAs($admin)->postJson($url)->assertOk();
        $this->actingAs($admin)->postJson($url)->assertOk();

        $this->assertDatabaseCount('djen_publications', 1);
        $this->assertDatabaseCount('legal_case_updates', 0);
        $publication = DjenPublication::query()->firstOrFail();
        $this->assertSame(DjenPublication::STATUS_PENDING, $publication->review_status);
        $this->assertSame('<script>alert(1)</script> Prazo iniciado.', $publication->raw_text);
    }
}
