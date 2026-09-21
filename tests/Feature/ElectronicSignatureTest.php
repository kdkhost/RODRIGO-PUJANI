<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\LegalDocument;
use App\Models\SignatureRequest;
use App\Models\User;
use App\Notifications\SignatureInvitationNotification;
use App\Notifications\SignatureStatusNotification;
use App\Services\ElectronicSignatureService;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ElectronicSignatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('signatures.enabled', true);
        Storage::fake('legal_documents');
        Notification::fake();
        $this->seed(PermissionsSeeder::class);
    }

    public function test_admin_creates_immutable_request_and_sends_hashed_token(): void
    {
        [$admin, $document] = $this->fixture();
        $response = $this->actingAs($admin)->post(route('admin.signature-requests.store'), [
            'document_source' => 'existing',
            'legal_document_id' => $document->id, 'title' => 'Contrato de honorários', 'message' => 'Revise o documento.',
            'expires_at' => now()->addDays(5)->format('Y-m-d H:i:s'), 'ordered' => '1',
            'signers' => [['name' => 'Cliente Assinante', 'email' => 'assinante@example.com', 'document' => '123.456.789-09']],
        ]);
        $signatureRequest = SignatureRequest::query()->with(['document', 'signers'])->firstOrFail();
        $response->assertRedirect(route('admin.signature-requests.show', $signatureRequest));
        $this->assertSame('pending', $signatureRequest->status);
        $this->assertSame($document->sha256, $signatureRequest->document->sha256);
        Storage::disk('legal_documents')->assertExists($signatureRequest->document->immutable_path);
        $this->assertSame(64, strlen((string) $signatureRequest->signers->first()->token_hash));
        $this->assertStringNotContainsString('Cliente Assinante', (string) $signatureRequest->signers->first()->token_hash);
        Notification::assertSentOnDemand(SignatureInvitationNotification::class);
    }

    public function test_create_page_allows_existing_document_or_direct_pdf_upload(): void
    {
        [$admin, $document] = $this->fixture();

        $this->actingAs($admin)
            ->get(route('admin.signature-requests.create', ['document' => $document->id]))
            ->assertOk()
            ->assertSee('Enviar documento para assinatura')
            ->assertSee('Selecionar documento existente')
            ->assertSee('Anexar PDF agora')
            ->assertSee('legal_document_id', false)
            ->assertSee('upload_file', false)
            ->assertSee('Contrato');
    }

    public function test_admin_can_attach_pdf_directly_when_creating_signature_request(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('Administrador');
        $client = Client::query()->create([
            'person_type' => 'individual',
            'name' => 'Cliente Upload',
            'is_active' => true,
            'portal_enabled' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.signature-requests.store'), [
            'document_source' => 'upload',
            'upload_client_id' => $client->id,
            'upload_title' => 'Contrato anexado no envio',
            'upload_file' => UploadedFile::fake()->createWithContent('contrato-assinatura.pdf', "%PDF-1.7\nconteúdo para assinatura"),
            'title' => 'Assinatura do contrato anexado',
            'message' => 'Leia e assine por favor.',
            'expires_at' => now()->addDays(5)->format('Y-m-d H:i:s'),
            'ordered' => '0',
            'signers' => [['name' => 'Cliente Assinante', 'email' => 'assinante-upload@example.com', 'document' => '123.456.789-09']],
        ]);

        $signatureRequest = SignatureRequest::query()->with(['legalDocument', 'document', 'signers'])->firstOrFail();
        $uploadedDocument = $signatureRequest->legalDocument;

        $response->assertRedirect(route('admin.signature-requests.show', $signatureRequest));
        $this->assertSame('Contrato anexado no envio', $uploadedDocument->title);
        $this->assertSame($client->id, $uploadedDocument->client_id);
        $this->assertSame('legal_documents', $uploadedDocument->disk);
        $this->assertSame('private', $uploadedDocument->storage_status);
        $this->assertSame('application/pdf', $uploadedDocument->mime_type);
        $this->assertSame('pdf', $uploadedDocument->extension);
        $this->assertSame(64, strlen((string) $uploadedDocument->sha256));
        Storage::disk('legal_documents')->assertExists($uploadedDocument->path);
        Storage::disk('legal_documents')->assertExists($signatureRequest->document->immutable_path);
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

        $this->get(route('signatures.public.show', $token))
            ->assertOk()
            ->assertSee('Contrato de teste')
            ->assertSee('Abrir PDF original');
        $documentResponse = $this->get(route('signatures.public.document', $token))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringStartsWith('%PDF-', $documentResponse->baseResponse->getFile()->getContent());
        $this->post(route('signatures.public.sign', $token), ['name' => 'Cliente Assinante', 'document' => '123.456.789-09', 'consent' => '1', 'signature_payload' => $this->signaturePayload()])->assertRedirect(route('signatures.public.result'));
        $signatureRequest->refresh()->load('document');
        $this->assertSame('completed', $signatureRequest->status);
        $this->assertTrue($service->verifyEvidence($signatureRequest));
        $completedPdf = Storage::disk('legal_documents')->get($signatureRequest->document->completed_path);
        $this->assertStringStartsWith('%PDF-', $completedPdf);
        $this->assertNotSame(Storage::disk('legal_documents')->get($signatureRequest->document->immutable_path), $completedPdf);
        $completedPath = Storage::disk('legal_documents')->path($signatureRequest->document->completed_path);
        $this->assertSame(2, (new Fpdi)->setSourceFile($completedPath));
        $this->assertDatabaseHas('signature_events', ['signature_request_id' => $signatureRequest->id, 'type' => 'viewed']);
        $this->assertDatabaseHas('signature_events', ['signature_request_id' => $signatureRequest->id, 'type' => 'signed']);
        $this->assertDatabaseHas('signature_events', ['signature_request_id' => $signatureRequest->id, 'type' => 'completed']);
        $this->assertNotNull($signer->fresh()->signature_image_sha256);
        $this->get(route('signatures.public.show', $token))->assertNotFound();

        Storage::disk('legal_documents')->put($signatureRequest->document->completed_path, 'adulterado');
        $this->assertFalse($service->verifyEvidence($signatureRequest->fresh()->load('document')));
    }

    public function test_ordered_request_completes_after_two_signers_and_emails_signed_pdf_copy(): void
    {
        [$admin, $document] = $this->fixture();
        $service = app(ElectronicSignatureService::class);
        $payload = $this->payload();
        $payload['ordered'] = true;
        $payload['signers'][] = ['name' => 'Segundo Signatário', 'email' => 'segundo@example.com', 'document' => null];
        $signatureRequest = $service->create($document, $payload, $admin->id);
        $signatureRequest->update(['status' => 'pending', 'sent_at' => now()]);

        $signers = $signatureRequest->signers()->orderBy('signing_order')->get();
        $firstToken = 'primeiro-token-seguro';
        $secondToken = 'segundo-token-seguro';
        $signers[0]->update(['status' => 'sent', 'token_hash' => hash('sha256', $firstToken), 'token_expires_at' => now()->addHour()]);

        $this->post(route('signatures.public.sign', $firstToken), ['name' => 'Cliente Assinante', 'document' => '123.456.789-09', 'consent' => '1', 'signature_payload' => $this->signaturePayload()])
            ->assertRedirect(route('signatures.public.result'));

        $this->assertSame('pending', $signatureRequest->fresh()->status);
        $this->assertSame('sent', $signers[1]->fresh()->status);
        $signers[1]->update(['token_hash' => hash('sha256', $secondToken), 'token_expires_at' => now()->addHour()]);

        $this->post(route('signatures.public.sign', $secondToken), ['name' => 'Segundo Signatário', 'consent' => '1', 'signature_payload' => $this->signaturePayload()])
            ->assertRedirect(route('signatures.public.result'));

        $signatureRequest->refresh()->load('document');
        $this->assertSame('completed', $signatureRequest->status);
        $this->assertTrue($service->verifyEvidence($signatureRequest));
        Storage::disk('legal_documents')->assertExists($signatureRequest->document->completed_path);

        Notification::assertSentOnDemand(SignatureStatusNotification::class, function (SignatureStatusNotification $notification) use ($signatureRequest): bool {
            if ($notification->event !== 'completed' || ! $notification->signatureRequest->is($signatureRequest)) {
                return false;
            }

            $mail = $notification->toMail((object) []);
            $attachment = $mail->attachments[0] ?? null;

            return str_contains(implode(' ', $mail->introLines), 'cópia do documento assinado')
                && is_array($attachment)
                && file_exists((string) ($attachment['file'] ?? ''))
                && (($attachment['options']['as'] ?? '') === 'assinado-contrato.pdf');
        });
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
        $this->post(route('signatures.public.sign', $token), ['name' => 'Cliente Assinante', 'document' => '12345678909', 'consent' => '1', 'signature_payload' => $this->signaturePayload()])->assertSessionHasErrors('document');
        $this->assertSame('sent', $signer->fresh()->status);
    }

    public function test_public_signature_rejects_dots_or_minimal_marks(): void
    {
        [$admin, $document] = $this->fixture();
        $service = app(ElectronicSignatureService::class);
        $request = $service->create($document, $this->payload(), $admin->id);
        $token = 'token-assinatura-minima';
        $signer = $request->signers()->firstOrFail();
        $request->update(['status' => 'pending']);
        $signer->update(['status' => 'sent', 'token_hash' => hash('sha256', $token), 'token_expires_at' => now()->addHour()]);

        $this->post(route('signatures.public.sign', $token), [
            'name' => 'Cliente Assinante',
            'document' => '12345678909',
            'consent' => '1',
            'signature_payload' => $this->signaturePayload(minimal: true),
        ])->assertSessionHasErrors('signature_payload');

        $this->assertSame('sent', $signer->fresh()->status);
        $this->assertNull($signer->fresh()->signature_image_sha256);
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
        $this->withSession(['portal_client_id' => $client->id])->get(route('portal.signatures.document', $request))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->withSession(['portal_client_id' => $client->id])->get(route('portal.signatures.evidence', $request))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->withSession(['portal_client_id' => $other->id])->get(route('portal.signatures.document', $request))->assertNotFound();
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

    public function test_permission_repair_migration_restores_signature_access_without_general_seeding(): void
    {
        $names = [
            'signature-requests.view', 'signature-requests.create', 'signature-requests.manage',
            'signature-requests.cancel', 'signature-requests.download', 'signature-requests.audit',
        ];
        Permission::query()->whereIn('name', $names)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $migration = require database_path('migrations/2026_08_27_000000_restore_electronic_signature_permissions.php');
        $migration->up();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->assertSame(6, Permission::query()->whereIn('name', $names)->count());
        $this->assertTrue(Role::findByName('Super Admin')->hasAllPermissions($names));
        $this->assertTrue(Role::findByName('Administrador')->hasAllPermissions($names));
    }

    private function fixture(): array
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('Administrador');
        $client = Client::query()->create(['person_type' => 'individual', 'name' => 'Cliente', 'is_active' => true, 'portal_enabled' => true]);
        $pdf = new \FPDF;
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->Cell(0, 10, 'Conteudo imutavel do contrato');
        $contents = $pdf->Output('S');
        Storage::disk('legal_documents')->put('originais/contrato.pdf', $contents);
        $document = LegalDocument::query()->create(['client_id' => $client->id, 'uploaded_by' => $admin->id, 'title' => 'Contrato', 'original_name' => 'contrato.pdf', 'file_name' => 'contrato.pdf', 'path' => 'originais/contrato.pdf', 'disk' => 'legal_documents', 'mime_type' => 'application/pdf', 'extension' => 'pdf', 'size' => strlen($contents), 'sha256' => hash('sha256', $contents), 'storage_status' => 'private', 'is_sensitive' => true]);

        return [$admin, $document, $client];
    }

    private function payload(): array
    {
        return ['title' => 'Contrato de teste', 'message' => null, 'ordered' => false, 'expires_at' => now()->addDay(), 'signers' => [['name' => 'Cliente Assinante', 'email' => 'assinante@example.com', 'document' => '123.456.789-09']]];
    }

    private function signaturePayload(bool $minimal = false): string
    {
        $image = imagecreatetruecolor(900, 260);
        imagesavealpha($image, true);
        $transparent = imagecolorallocatealpha($image, 255, 255, 255, 127);
        imagefill($image, 0, 0, $transparent);
        $ink = imagecolorallocatealpha($image, 17, 24, 39, 0);
        imagesetthickness($image, $minimal ? 3 : 5);

        if ($minimal) {
            imagefilledellipse($image, 110, 120, 4, 4, $ink);
            $metrics = [
                'stroke_count' => 1,
                'point_count' => 2,
                'distance_px' => 2,
                'duration_ms' => 80,
                'bounds' => ['x' => 108, 'y' => 118, 'width' => 4, 'height' => 4],
            ];
        } else {
            $points = [
                [90, 170], [125, 145], [160, 120], [200, 138],
                [240, 160], [285, 128], [330, 92], [380, 118],
                [430, 150], [485, 130], [540, 112], [595, 136],
                [650, 158], [705, 130], [760, 105],
            ];
            foreach (array_slice($points, 1) as $index => $point) {
                imageline($image, $points[$index][0], $points[$index][1], $point[0], $point[1], $ink);
            }
            $metrics = [
                'stroke_count' => 1,
                'point_count' => count($points),
                'distance_px' => 780,
                'duration_ms' => 1300,
                'bounds' => ['x' => 90, 'y' => 92, 'width' => 670, 'height' => 78],
            ];
        }

        ob_start();
        imagepng($image);
        $png = (string) ob_get_clean();
        imagedestroy($image);

        return json_encode([
            'data_url' => 'data:image/png;base64,'.base64_encode($png),
            'metrics' => $metrics,
        ], JSON_THROW_ON_ERROR);
    }
}
