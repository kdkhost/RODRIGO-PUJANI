<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\LegalDocument;
use App\Models\User;
use App\Services\ElectronicSignatureService;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class ElectronicSignatureFeatureFlagTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('signatures.enabled', false);
        Notification::fake();
        $this->seed(PermissionsSeeder::class);
    }

    public function test_public_signature_routes_return_not_found_when_disabled(): void
    {
        $this->get('/assinatura/token-inexistente')->assertNotFound();
        $this->post('/assinatura/token-inexistente/assinar')->assertNotFound();
        $this->post('/assinatura/token-inexistente/recusar')->assertNotFound();
        $this->get('/assinatura/resultado')->assertNotFound();
    }

    public function test_admin_and_portal_routes_return_not_found_when_disabled(): void
    {
        $admin = $this->admin();
        $client = Client::query()->create(['person_type' => 'individual', 'name' => 'Cliente', 'portal_enabled' => true, 'is_active' => true]);

        $this->actingAs($admin)->get(route('admin.signature-requests.index'))->assertNotFound();
        $this->actingAs($admin)->get(route('admin.signature-requests.create'))->assertNotFound();
        $this->withSession(['portal_client_id' => $client->id])->get(route('portal.signatures.index'))->assertNotFound();
    }

    public function test_service_notifications_and_expiration_command_are_inert_when_disabled(): void
    {
        $this->artisan('signatures:expire')
            ->expectsOutput('Módulo de assinatura eletrônica desabilitado; nenhuma alteração foi realizada.')
            ->assertSuccessful();

        try {
            app(ElectronicSignatureService::class)->create(new LegalDocument, [], 1);
            $this->fail('O serviço deveria bloquear a operação com a flag desativada.');
        } catch (NotFoundHttpException) {
            $this->assertTrue(true);
        }

        Notification::assertNothingSent();
        $this->assertDatabaseCount('signature_requests', 0);
        $this->assertDatabaseCount('signature_events', 0);
    }

    public function test_signature_navigation_and_actions_are_hidden_when_disabled(): void
    {
        $admin = $this->admin();
        $client = Client::query()->create(['person_type' => 'individual', 'name' => 'Cliente', 'portal_enabled' => true, 'is_active' => true]);

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertDontSee('Assinaturas');
        $this->withSession(['portal_client_id' => $client->id])->get(route('portal.dashboard'))->assertOk()->assertDontSee('Assinaturas');
    }

    private function admin(): User
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('Administrador');

        return $admin;
    }
}
