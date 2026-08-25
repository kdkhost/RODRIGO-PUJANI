<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\LegalDocument;
use App\Models\SignatureRequest;
use App\Models\User;
use App\Notifications\SignatureInvitationNotification;
use App\Services\ElectronicSignatureService;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ElectronicSignatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('legal_documents');
        Notification::fake();
        $this->seed(PermissionsSeeder::class);
    }

    public function test_admin_creates_immutable_request_and_sends_hashed_token(): void
    {
        [$admin, $document] = $this->fixture();
        $response = $this->actingAs($admin)->post(route('admin.signature-requests.store'), [
            'legal_document_id' => $document->id, 'title' => 'Contrato de honorários', 'message' => 'Revise o documento.',
            'expires_at' => now()->addDays(5)->format('Y-m-d H:i:s'), 'ordered' => '1',
            'signers' => [['name' => 'Cliente Assinante', 'email' => 'assinante@example.com', 'document' => '123.456.789-09']],
        ]);
        $signatureRequest = SignatureRequest::query()->with(['document', 'signers'])->firstOrFail();
        $response->assertRedirect(route('admin.signature-requests.show', $signatureRequest));
        $this->assertSame('pending', $signatureRequest->status);
        $this->assertSame(hash('sha256', 'conteudo-imutavel'), $signatureRequest->document->sha256);
        Storage::disk('legal_documents')->assertExists($signatureRequest->document->immutable_path);
        $this->assertSame(64, strlen((string) $signatureRequest->signers->first()->token_hash));
        $this->assertStringNotContainsString('Cliente Assinante', (string) $signatureRequest->signers->first()->token_hash);
        Notification::assertSentOnDemand(SignatureInvitationNotification::class);
    }

    public function test_valid_token_signs_once_and_generates_verifiable_evidence(): void
    {
        [$admin, $document] = $this->fixture();
        $service = app(ElectronicSignatureService::class);
        $signatureRequest = $service->create($document, $this->payload(), $admin->id);
        $token = 'token-seguro-de-teste-com-entropia-suficiente';
        $signer = $signatureRequest->signers()->firstOrFail();
        $signatureRequest->update(['status' => 'pending', 'sent_at' => now()]);
        $signer->update(['status' => 'sent', 'token_hash' => hash('sha256', $token), 'token_expires_at' => now()->addHour()]);

        $this->get(route('signatures.public.show', $token))->assertOk()->assertSee('Contrato de teste');
        $this->post(route('signatures.public.sign', $token), ['name' => 'Cliente Assinante', 'document' => '123.456.789-09', 'consent' => '1'])->assertRedirect(route('signatures.public.result'));
        $signatureRequest->refresh()->load('document');
        $this->assertSame('completed', $signatureRequest->status);
        $this->assertTrue($service->verifyEvidence($signatureRequest));
        $this->assertDatabaseHas('signature_events', ['signature_request_id' => $signatureRequest->id, 'type' => 'viewed']);
        $this->assertDatabaseHas('signature_events', ['signature_request_id' => $signatureRequest->id, 'type' => 'signed']);
        $this->assertDatabaseHas('signature_events', ['signature_request_id' => $signatureRequest->id, 'type' => 'completed']);
        $this->get(route('signatures.public.show', $token))->assertNotFound();
    }

    public function test_tampered_document_is_rejected_without_signing(): void
    {
        [$admin, $document] = $this->fixture();
        $service = app(ElectronicSignatureService::class);
        $request = $service->create($document, $this->payload(), $admin->id);
        $token = 'token-integridade';
        $signer = $request->signers()->firstOrFail();
        $request->update(['status' => 'pending']);
        $signer->update(['status' => 'sent', 'token_hash' => hash('sha256', $token), 'token_expires_at' => now()->addHour()]);
        Storage::disk('legal_documents')->put($request->document->immutable_path, 'adulterado');
        $this->post(route('signatures.public.sign', $token), ['name' => 'Cliente Assinante', 'document' => '12345678909', 'consent' => '1'])->assertSessionHasErrors('document');
        $this->assertSame('sent', $signer->fresh()->status);
    }

    public function test_decline_cancel_and_expiration_invalidate_tokens(): void
    {
        [$admin, $document] = $this->fixture();
        $service = app(ElectronicSignatureService::class);
        $declined = $service->create($document, $this->payload(), $admin->id);
        $declined->update(['status' => 'pending']);
        $signer = $declined->signers()->firstOrFail();
        $signer->update(['status' => 'sent', 'token_hash' => hash('sha256', 'recusar'), 'token_expires_at' => now()->addHour()]);
        $this->post(route('signatures.public.decline', 'recusar'), ['reason' => 'Não concordo com a cláusula.'])->assertRedirect(route('signatures.public.result'));
        $this->assertSame('declined', $declined->fresh()->status);
        $this->assertNull($signer->fresh()->token_hash);

        $cancelled = $service->create($document, $this->payload(), $admin->id);
        $cancelled->update(['status' => 'pending']);
        $this->actingAs($admin)->post(route('admin.signature-requests.cancel', $cancelled), ['reason' => 'Documento substituído.'])->assertRedirect();
        $this->assertSame('cancelled', $cancelled->fresh()->status);

        $expired = $service->create($document, array_merge($this->payload(), ['expires_at' => now()->subMinute()]), $admin->id);
        $expired->update(['status' => 'pending']);
        $expired->signers()->update(['status' => 'sent', 'token_hash' => hash('sha256', 'expirado'), 'token_expires_at' => now()->subMinute()]);
        $this->artisan('signatures:expire')->assertSuccessful();
        $this->assertSame('expired', $expired->fresh()->status);
        $this->assertNull($expired->signers()->first()->token_hash);
    }

    public function test_portal_only_lists_and_downloads_own_valid_evidence(): void
    {
        [$admin, $document, $client] = $this->fixture();
        $other = Client::query()->create(['person_type' => 'individual', 'name' => 'Outro', 'is_active' => true, 'portal_enabled' => true]);
        $service = app(ElectronicSignatureService::class);
        $request = $service->create($document, $this->payload(), $admin->id);
        $request->update(['status' => 'pending']);
        $signer = $request->signers()->firstOrFail();
        $signer->update(['status' => 'sent', 'token_hash' => hash('sha256', 'portal-token'), 'token_expires_at' => now()->addHour()]);
        $service->sign($signer, ['ip_address' => '127.0.0.1', 'user_agent' => 'PHPUnit']);
        $this->withSession(['portal_client_id' => $client->id])->get(route('portal.signatures.index'))->assertOk()->assertSee('Contrato de teste');
        $this->withSession(['portal_client_id' => $client->id])->get(route('portal.signatures.evidence', $request))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->withSession(['portal_client_id' => $other->id])->get(route('portal.signatures.evidence', $request))->assertNotFound();
    }

    public function test_ordered_request_only_releases_the_next_signer(): void
    {
        [$admin, $document] = $this->fixture();
        $service = app(ElectronicSignatureService::class);
        $payload = $this->payload();
        $payload['ordered'] = true;
        $payload['signers'][] = ['name' => 'Segundo Signatário', 'email' => 'segundo@example.com', 'document' => null];
        $request = $service->create($document, $payload, $admin->id);
        $service->send($request);
        $signers = $request->signers()->orderBy('signing_order')->get();
        $this->assertSame('sent', $signers[0]->status);
        $this->assertSame('pending', $signers[1]->status);
        $service->sign($signers[0], ['ip_address' => '127.0.0.1', 'user_agent' => 'PHPUnit']);
        $this->assertSame('sent', $signers[1]->fresh()->status);
        $this->assertNotNull($signers[1]->fresh()->token_hash);
    }

    public function test_user_without_signature_permission_cannot_open_admin_module(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->givePermissionTo('admin.access');

        $this->actingAs($user)->get(route('admin.signature-requests.index'))->assertForbidden();
    }

    private function fixture(): array
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('Administrador');
        $client = Client::query()->create(['person_type' => 'individual', 'name' => 'Cliente', 'is_active' => true, 'portal_enabled' => true]);
        Storage::disk('legal_documents')->put('originais/contrato.pdf', 'conteudo-imutavel');
        $document = LegalDocument::query()->create(['client_id' => $client->id, 'uploaded_by' => $admin->id, 'title' => 'Contrato', 'original_name' => 'contrato.pdf', 'file_name' => 'contrato.pdf', 'path' => 'originais/contrato.pdf', 'disk' => 'legal_documents', 'mime_type' => 'application/pdf', 'extension' => 'pdf', 'size' => 17, 'sha256' => hash('sha256', 'conteudo-imutavel'), 'storage_status' => 'private', 'is_sensitive' => true]);

        return [$admin, $document, $client];
    }

    private function payload(): array
    {
        return ['title' => 'Contrato de teste', 'message' => null, 'ordered' => false, 'expires_at' => now()->addDay(), 'signers' => [['name' => 'Cliente Assinante', 'email' => 'assinante@example.com', 'document' => '123.456.789-09']]];
    }
}
