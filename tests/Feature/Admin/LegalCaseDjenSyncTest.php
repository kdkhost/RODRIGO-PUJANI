<?php

namespace Tests\Feature\Admin;

use App\Models\Client;
use App\Models\LegalCase;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LegalCaseDjenSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_import_public_djen_communications_without_duplicating_them(): void
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
                'data_disponibilizacao' => '2026-08-20',
                'siglaTribunal' => 'TJSP',
                'tipoComunicacao' => 'Intimação',
                'nomeOrgao' => '1ª Vara',
                'texto' => '<script>alert(1)</script> Prazo iniciado.',
            ]],
        ], 200)]);

        $url = route('admin.legal-cases.sync-djen', $legalCase);
        $this->actingAs($admin)->postJson($url)->assertOk()->assertJsonPath('message', 'DJEN sincronizado. 1 comunicação(ões) nova(s) e 0 atualizada(s).');
        $this->actingAs($admin)->postJson($url)->assertOk()->assertJsonPath('message', 'DJEN sincronizado. 0 comunicação(ões) nova(s) e 0 atualizada(s).');

        $this->assertDatabaseCount('legal_case_updates', 1);
        $this->assertDatabaseHas('legal_case_updates', ['legal_case_id' => $legalCase->id, 'source' => 'djen', 'external_id' => 'djen:hash-publicacao-1']);
        $this->assertStringNotContainsString('<script>', (string) $legalCase->updates()->first()->body);
    }
}
