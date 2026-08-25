<?php

namespace Tests\Feature\Admin;

use App\Contracts\LegalProductivityProviderInterface;
use App\Models\Client;
use App\Models\IntegrationCredential;
use App\Models\LegalCase;
use App\Models\LegalCaseUpdate;
use App\Models\LegalUpdateSummary;
use App\Models\User;
use App\Services\LegalProductivityProviderManager;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LegalAiSummaryWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionsSeeder::class);
    }

    public function test_summary_requires_a_human_workflow_before_portal_publication(): void
    {
        $lawyer = $this->administrator('Revisora');
        [$client, $legalCase] = $this->portfolio($lawyer, 'Resumo');
        $update = $this->update($legalCase, 'Texto jurídico original.', true);
        $this->bindProvider('Resumo claro e revisável para o cliente.');

        $this->actingAs($lawyer)
            ->postJson(route('admin.legal-update-summaries.generate', $update))
            ->assertOk()
            ->assertJsonPath('summary.status', 'draft');

        $summary = LegalUpdateSummary::query()->sole();
        $this->assertSame(hash('sha256', 'Texto jurídico original.'), $summary->source_sha256);
        $this->assertSame('draft', $summary->status);
        $this->assertNull($summary->published_at);

        $this->actingAs($lawyer)
            ->postJson(route('admin.legal-update-summaries.publish', $summary))
            ->assertUnprocessable();

        $this->withSession(['portal_client_id' => $client->id])
            ->get(route('portal.cases.show', $legalCase))
            ->assertOk()
            ->assertSee('Texto jurídico original.')
            ->assertDontSee('Resumo claro e revisável para o cliente.');

        $this->actingAs($lawyer)
            ->putJson(route('admin.legal-update-summaries.update', $summary), [
                'summary_text' => 'Resumo humano revisado e aprovado.',
            ])
            ->assertOk();
        $this->assertSame('reviewed', $summary->refresh()->status);
        $this->assertSame($lawyer->id, $summary->reviewed_by);

        $this->actingAs($lawyer)
            ->postJson(route('admin.legal-update-summaries.approve', $summary))
            ->assertOk();
        $this->assertSame('approved', $summary->refresh()->status);
        $this->assertNull($summary->published_at);

        $this->withSession(['portal_client_id' => $client->id])
            ->get(route('portal.cases.show', $legalCase))
            ->assertOk()
            ->assertDontSee('Resumo humano revisado e aprovado.');

        $this->actingAs($lawyer)
            ->postJson(route('admin.legal-update-summaries.publish', $summary))
            ->assertOk();

        $summary->refresh();
        $this->assertSame('published', $summary->status);
        $this->assertSame($lawyer->id, $summary->published_by);
        $this->assertNotNull($summary->published_at);

        $this->withSession(['portal_client_id' => $client->id])
            ->get(route('portal.cases.show', $legalCase))
            ->assertOk()
            ->assertSee('Resumo humano revisado e aprovado.')
            ->assertDontSee('Texto jurídico original.');
    }

    public function test_generation_never_publishes_or_exposes_an_update_hidden_from_the_client(): void
    {
        $lawyer = $this->administrator('Geradora');
        [$client, $legalCase] = $this->portfolio($lawyer, 'Oculto');
        $update = $this->update($legalCase, 'Conteúdo interno sigiloso.', false);
        $this->bindProvider('Resumo que não pode ser publicado.');

        $this->actingAs($lawyer)
            ->postJson(route('admin.legal-update-summaries.generate', $update))
            ->assertOk();

        $summary = LegalUpdateSummary::query()->sole();
        $this->assertSame('draft', $summary->status);
        $this->assertFalse($update->refresh()->is_visible_to_client);

        $this->actingAs($lawyer)
            ->postJson(route('admin.legal-update-summaries.approve', $summary))
            ->assertOk();
        $this->actingAs($lawyer)
            ->postJson(route('admin.legal-update-summaries.publish', $summary))
            ->assertUnprocessable();

        $this->withSession(['portal_client_id' => $client->id])
            ->get(route('portal.cases.show', $legalCase))
            ->assertOk()
            ->assertDontSee('Conteúdo interno sigiloso.')
            ->assertDontSee('Resumo que não pode ser publicado.');
    }

    public function test_stale_summary_is_rejected_when_the_original_source_changes(): void
    {
        $lawyer = $this->administrator('Validadora');
        [, $legalCase] = $this->portfolio($lawyer, 'Integridade');
        $update = $this->update($legalCase, 'Versão original.', true);
        $this->bindProvider('Resumo da versão original.');

        $this->actingAs($lawyer)->postJson(route('admin.legal-update-summaries.generate', $update))->assertOk();
        $summary = LegalUpdateSummary::query()->sole();
        $this->actingAs($lawyer)->postJson(route('admin.legal-update-summaries.approve', $summary))->assertOk();

        $update->forceFill(['body' => 'Versão original posteriormente alterada.'])->save();

        $this->actingAs($lawyer)
            ->postJson(route('admin.legal-update-summaries.publish', $summary))
            ->assertUnprocessable();

        $this->assertSame('approved', $summary->refresh()->status);
        $this->assertNull($summary->published_at);
    }

    public function test_lawyer_cannot_generate_review_or_view_summaries_from_another_portfolio(): void
    {
        $lawyerA = $this->lawyer('Advogada A');
        $lawyerB = $this->lawyer('Advogada B');
        [, $caseB] = $this->portfolio($lawyerB, 'Privado B');
        $updateB = $this->update($caseB, 'Andamento de outra carteira.', true);
        $summaryB = LegalUpdateSummary::query()->create([
            'legal_case_update_id' => $updateB->id,
            'version' => 1,
            'source_sha256' => hash('sha256', 'Andamento de outra carteira.'),
            'summary_text' => 'Resumo privado B.',
            'status' => 'draft',
            'provider' => 'fake',
            'model' => 'fake-model',
            'generated_by' => $lawyerB->id,
            'generated_at' => now(),
        ]);
        $this->bindProvider('Não deve ser usado.');

        $this->actingAs($lawyerA)
            ->postJson(route('admin.legal-update-summaries.generate', $updateB))
            ->assertNotFound();
        $this->actingAs($lawyerA)
            ->putJson(route('admin.legal-update-summaries.update', $summaryB), ['summary_text' => 'Tentativa indevida'])
            ->assertNotFound();
    }

    public function test_generation_review_approval_and_publication_permissions_are_separated(): void
    {
        $generator = $this->lawyer('Geradora operacional');
        $approver = $this->editor('Revisora aprovadora');
        $publisher = $this->administrator('Publicadora final');
        [, $legalCase] = $this->portfolio($generator, 'Alçadas');
        $legalCase->forceFill(['supervising_lawyer_id' => $approver->id])->save();
        $update = $this->update($legalCase, 'Fonte sob controle de alçada.', true);
        $this->bindProvider('Resumo sob controle de alçada.');

        $this->actingAs($generator)
            ->postJson(route('admin.legal-update-summaries.generate', $update))
            ->assertOk();
        $summary = LegalUpdateSummary::query()->sole();

        $this->actingAs($generator)
            ->postJson(route('admin.legal-update-summaries.approve', $summary))
            ->assertForbidden();
        $this->actingAs($generator)
            ->postJson(route('admin.legal-update-summaries.publish', $summary))
            ->assertForbidden();

        $this->actingAs($approver)
            ->putJson(route('admin.legal-update-summaries.update', $summary), ['summary_text' => 'Resumo revisado por alçada humana.'])
            ->assertOk();
        $this->actingAs($approver)
            ->postJson(route('admin.legal-update-summaries.approve', $summary))
            ->assertOk();
        $this->actingAs($approver)
            ->postJson(route('admin.legal-update-summaries.publish', $summary))
            ->assertForbidden();

        $this->actingAs($publisher)
            ->postJson(route('admin.legal-update-summaries.publish', $summary))
            ->assertOk();
        $this->assertSame('published', $summary->refresh()->status);
    }

    public function test_portal_session_cannot_open_a_case_owned_by_another_client(): void
    {
        $lawyer = $this->lawyer('Responsável');
        [$clientA] = $this->portfolio($lawyer, 'Portal A');
        [, $caseB] = $this->portfolio($lawyer, 'Portal B');

        $this->withSession(['portal_client_id' => $clientA->id])
            ->get(route('portal.cases.show', $caseB))
            ->assertNotFound();
    }

    public function test_integration_secret_is_encrypted_at_rest(): void
    {
        $credential = IntegrationCredential::query()->create([
            'service' => 'legal_ai',
            'enabled' => true,
            'configuration' => ['provider' => 'openai_compatible'],
            'secret' => 'chave-controlada-super-secreta',
        ]);

        $stored = DB::table('integration_credentials')->where('id', $credential->id)->value('secret');

        $this->assertNotSame('chave-controlada-super-secreta', $stored);
        $this->assertSame('chave-controlada-super-secreta', $credential->refresh()->secret);
    }

    private function bindProvider(string $summaryText): void
    {
        $provider = new class($summaryText) implements LegalProductivityProviderInterface
        {
            public function __construct(private readonly string $summaryText) {}

            public function summarize(string $source): array
            {
                return [
                    'text' => $this->summaryText,
                    'provider' => 'fake',
                    'model' => 'fake-summary-model',
                    'metadata' => ['source_length' => mb_strlen($source)],
                ];
            }

            public function transcribe(string $absolutePath, string $mimeType): array
            {
                throw new \LogicException('Não utilizado neste teste.');
            }

            public function draftMinutes(string $transcript): array
            {
                throw new \LogicException('Não utilizado neste teste.');
            }
        };

        $manager = new class($provider) extends LegalProductivityProviderManager
        {
            public function __construct(private readonly LegalProductivityProviderInterface $fakeProvider) {}

            public function provider(): LegalProductivityProviderInterface
            {
                return $this->fakeProvider;
            }
        };

        $this->app->instance(LegalProductivityProviderManager::class, $manager);
    }

    private function lawyer(string $name): User
    {
        $user = User::factory()->create(['name' => $name, 'is_active' => true]);
        $user->assignRole('Advogado Associado');

        return $user;
    }

    private function administrator(string $name): User
    {
        $user = User::factory()->create(['name' => $name, 'is_active' => true]);
        $user->assignRole('Administrador');

        return $user;
    }

    private function editor(string $name): User
    {
        $user = User::factory()->create(['name' => $name, 'is_active' => true]);
        $user->assignRole('Editor');

        return $user;
    }

    /** @return array{Client, LegalCase} */
    private function portfolio(User $lawyer, string $suffix): array
    {
        $client = Client::query()->create([
            'person_type' => 'individual',
            'name' => 'Cliente '.$suffix,
            'assigned_lawyer_id' => $lawyer->id,
            'created_by' => $lawyer->id,
            'is_active' => true,
            'portal_enabled' => true,
        ]);
        $legalCase = LegalCase::query()->create([
            'client_id' => $client->id,
            'primary_lawyer_id' => $lawyer->id,
            'created_by' => $lawyer->id,
            'title' => 'Processo '.$suffix,
            'status' => 'active',
            'phase' => 'initial',
            'priority' => 'medium',
            'is_active' => true,
            'portal_visible' => true,
        ]);

        return [$client, $legalCase];
    }

    private function update(LegalCase $legalCase, string $body, bool $visible): LegalCaseUpdate
    {
        return LegalCaseUpdate::query()->create([
            'legal_case_id' => $legalCase->id,
            'client_id' => $legalCase->client_id,
            'created_by' => $legalCase->primary_lawyer_id,
            'source' => 'manual',
            'update_type' => 'procedural',
            'title' => 'Andamento jurídico',
            'body' => $body,
            'occurred_at' => now(),
            'is_visible_to_client' => $visible,
        ]);
    }
}
