<?php

namespace Tests\Feature\Admin;

use App\Models\Client;
use App\Models\LegalCase;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LegalCaseDataJudSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_sync_case_updates_from_datajud(): void
    {
        $this->seed(PermissionsSeeder::class);

        $admin = User::factory()->create(['is_active' => true]);
        $admin->givePermissionTo(['admin.access', 'legal-cases.manage']);

        $client = Client::query()->create([
            'person_type' => 'individual',
            'name' => 'Cliente CNJ',
            'is_active' => true,
        ]);

        $legalCase = LegalCase::query()->create([
            'client_id' => $client->id,
            'title' => 'Processo CNJ',
            'process_number' => '10000001020268260100',
            'tribunal_alias' => 'tjsp',
            'status' => 'active',
            'phase' => 'initial',
            'priority' => 'medium',
            'portal_visible' => true,
            'is_confidential' => true,
            'is_active' => true,
        ]);

        Http::fake([
            'https://datajud-wiki.cnj.jus.br/api-publica/acesso/' => Http::response(
                '<html><body>Authorization: APIKey TEST_PUBLIC_KEY</body></html>',
                200
            ),
            'https://api-publica.datajud.cnj.jus.br/api_publica_tjsp/_search' => Http::response([
                'hits' => [
                    'hits' => [[
                        '_source' => [
                            'numeroProcesso' => '10000001020268260100',
                            'tribunal' => 'TJSP',
                            'dataHoraUltimaAtualizacao' => '2026-04-27T12:30:00.000Z',
                            'movimentos' => [
                                [
                                    'codigo' => 26,
                                    'nome' => 'Distribuição',
                                    'dataHora' => '2026-04-25T10:00:00.000Z',
                                ],
                                [
                                    'codigo' => 51,
                                    'nome' => 'Conclusos para decisão',
                                    'dataHora' => '2026-04-26T14:30:00.000Z',
                                    'complementosTabelados' => [
                                        [
                                            'nome' => 'Magistrado',
                                            'valor' => '1',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ]],
                ],
            ], 200),
        ]);

        $this->actingAs($admin)
            ->postJson(route('admin.legal-cases.sync-datajud', $legalCase->id))
            ->assertOk()
            ->assertJsonPath('message', 'Sincronização concluída. 2 andamento(s) novo(s) e 0 atualizado(s).');

        $this->assertDatabaseHas('legal_case_updates', [
            'legal_case_id' => $legalCase->id,
            'title' => 'Distribuição',
            'source' => 'datajud',
        ]);

        $this->assertDatabaseHas('legal_case_updates', [
            'legal_case_id' => $legalCase->id,
            'title' => 'Conclusos para decisão',
            'source' => 'datajud',
        ]);

        $this->assertNotNull($legalCase->refresh()->datajud_last_synced_at);
    }
}
