<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\LegalCase;
use App\Models\LegalDocument;
use App\Models\User;
use App\Services\ElectronicSignatureService;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class LegacyLegalDocumentMigrationTest extends TestCase
{
    use RefreshDatabase;

    private string $testDirectory;

    private string $caseVariantDirectory;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('legal_documents');
        $this->testDirectory = 'uploads/legacy-migration-tests-'.bin2hex(random_bytes(5));
        $this->caseVariantDirectory = 'UPLOADS/legacy-migration-case-tests-'.bin2hex(random_bytes(5));
        File::ensureDirectoryExists(public_path($this->testDirectory));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(public_path($this->testDirectory));
        File::deleteDirectory(public_path($this->caseVariantDirectory));
        File::delete(public_path('legacy-migration-outside.txt'));
        parent::tearDown();
    }

    public function test_txt_legacy_is_migrated_privately_with_hash_audit_and_public_removal(): void
    {
        $document = $this->legacyDocument('resumo.txt', 'conteúdo legado demonstrativo');
        $source = public_path($document->path);
        $hash = hash_file('sha256', $source);

        $this->artisan('legal-documents:migrate-private', ['--document' => $document->id])
            ->assertSuccessful();

        $document->refresh();
        $this->assertSame('legal_documents', $document->disk);
        $this->assertSame('legacy_private', $document->storage_status);
        $this->assertSame('txt', $document->extension);
        $this->assertSame('text/plain', $document->mime_type);
        $this->assertSame($hash, $document->sha256);
        $this->assertTrue($document->is_sensitive);
        $this->assertStringStartsWith('legacy/', $document->path);
        $this->assertNotSame('resumo.txt', $document->file_name);
        Storage::disk('legal_documents')->assertExists($document->path);
        $this->assertSame($hash, hash('sha256', Storage::disk('legal_documents')->get($document->path)));
        $this->assertFileDoesNotExist($source);
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'legal_documents',
            'event' => 'legacy_migrated_private',
            'subject_id' => $document->id,
        ]);
    }

    public function test_dry_run_does_not_copy_update_or_remove_the_public_file(): void
    {
        $document = $this->legacyDocument('dry-run.txt', 'somente validar');
        $source = public_path($document->path);

        $this->artisan('legal-documents:migrate-private', [
            '--document' => $document->id,
            '--dry-run' => true,
        ])->expectsOutputToContain('[DRY-RUN]')->assertSuccessful();

        $this->assertFileExists($source);
        $this->assertSame('legacy_public', $document->fresh()->disk);
        $this->assertNull($document->fresh()->sha256);
        Storage::disk('legal_documents')->assertDirectoryEmpty('/');
        $this->assertDatabaseMissing('activity_logs', ['event' => 'legacy_migrated_private']);
    }

    public function test_file_outside_public_uploads_is_rejected_and_preserved(): void
    {
        File::put(public_path('legacy-migration-outside.txt'), 'fora');
        $document = $this->legacyDocumentRecord('legacy-migration-outside.txt');

        $this->artisan('legal-documents:migrate-private', ['--document' => $document->id])
            ->assertFailed();

        $this->assertFileExists(public_path('legacy-migration-outside.txt'));
        $this->assertSame('legacy_public', $document->fresh()->disk);
    }

    public function test_case_variant_uploads_directory_is_rejected_on_linux(): void
    {
        if (PHP_OS_FAMILY !== 'Linux') {
            $this->markTestSkipped('A distinção entre uploads e UPLOADS deve ser validada em Linux.');
        }

        File::ensureDirectoryExists(public_path($this->caseVariantDirectory));
        $path = $this->caseVariantDirectory.'/fora.txt';
        File::put(public_path($path), 'fora da raiz autorizada');
        $document = $this->legacyDocumentRecord($path);

        $this->artisan('legal-documents:migrate-private', ['--document' => $document->id])
            ->assertFailed();

        $this->assertFileExists(public_path($path));
        $this->assertSame('legacy_public', $document->fresh()->disk);
        Storage::disk('legal_documents')->assertDirectoryEmpty('/');
    }

    public function test_path_traversal_is_rejected_even_when_it_resolves_inside_uploads(): void
    {
        $document = $this->legacyDocument('traversal.txt', 'preservar');
        $document->update(['path' => $this->testDirectory.'/sub/../traversal.txt']);

        $this->artisan('legal-documents:migrate-private', ['--document' => $document->id])
            ->assertFailed();

        $this->assertFileExists(public_path($this->testDirectory.'/traversal.txt'));
    }

    public function test_symbolic_link_is_rejected_and_target_is_preserved(): void
    {
        $target = public_path($this->testDirectory.'/target.txt');
        $link = public_path($this->testDirectory.'/link.txt');
        File::put($target, 'alvo');

        if (! @symlink($target, $link)) {
            $this->markTestSkipped('O ambiente não permite criar link simbólico para o teste.');
        }

        $document = $this->legacyDocumentRecord($this->testDirectory.'/link.txt');
        $this->artisan('legal-documents:migrate-private', ['--document' => $document->id])
            ->assertFailed();

        $this->assertFileExists($target);
        $this->assertSame('legacy_public', $document->fresh()->disk);
    }

    public function test_registered_hash_divergence_blocks_migration_and_public_removal(): void
    {
        $document = $this->legacyDocument('divergente.txt', 'conteúdo original');
        $document->update(['sha256' => str_repeat('a', 64)]);

        $this->artisan('legal-documents:migrate-private', ['--document' => $document->id])
            ->assertFailed();

        $this->assertFileExists(public_path($document->path));
        Storage::disk('legal_documents')->assertDirectoryEmpty('/');
    }

    public function test_repeated_migration_is_idempotent(): void
    {
        $document = $this->legacyDocument('repetido.txt', 'conteúdo');
        $this->artisan('legal-documents:migrate-private', ['--document' => $document->id])->assertSuccessful();
        $path = $document->fresh()->path;

        $this->artisan('legal-documents:migrate-private', ['--document' => $document->id])
            ->expectsOutputToContain('Processados: 0')
            ->assertSuccessful();

        $this->assertSame($path, $document->fresh()->path);
        $this->assertCount(1, Storage::disk('legal_documents')->allFiles());
    }

    public function test_private_download_is_authorized_and_isolated_between_clients(): void
    {
        [$owner, $other] = [$this->client('Cliente proprietário'), $this->client('Outro cliente')];
        $document = $this->legacyDocument('portal.txt', 'download privado', $owner);
        $this->artisan('legal-documents:migrate-private', ['--document' => $document->id])->assertSuccessful();

        $this->withSession(['portal_client_id' => $owner->id])
            ->get(route('portal.documents.download', $document))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->withSession(['portal_client_id' => $other->id])
            ->get(route('portal.documents.download', $document))
            ->assertNotFound();
    }

    public function test_txt_is_rejected_for_signature_and_for_new_upload(): void
    {
        config()->set('signatures.enabled', true);
        $admin = $this->administrator();
        $client = $this->client('Cliente assinatura');
        $document = $this->legacyDocument('assinatura.txt', 'não assinável', $client);
        $this->artisan('legal-documents:migrate-private', ['--document' => $document->id])->assertSuccessful();

        try {
            app(ElectronicSignatureService::class)->create($document->fresh(), [
                'title' => 'Solicitação inválida',
                'expires_at' => now()->addDay(),
                'ordered' => false,
                'signers' => [['name' => 'Teste', 'email' => 'teste@example.test']],
            ], $admin->id);
            $this->fail('O TXT legado não pode ser aceito para assinatura.');
        } catch (HttpException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
        }

        $this->actingAs($admin)->postJson(route('admin.legal-documents.store'), [
            'client_id' => $client->id,
            'title' => 'Novo TXT',
            'file' => UploadedFile::fake()->createWithContent('novo.txt', 'novo conteúdo'),
        ])->assertUnprocessable()->assertJsonValidationErrors('file');
    }

    private function legacyDocument(string $name, string $contents, ?Client $client = null): LegalDocument
    {
        $path = $this->testDirectory.'/'.$name;
        File::put(public_path($path), $contents);

        return $this->legacyDocumentRecord($path, $client);
    }

    private function legacyDocumentRecord(string $path, ?Client $client = null): LegalDocument
    {
        $client ??= $this->client('Cliente legado');

        return LegalDocument::query()->create([
            'client_id' => $client->id,
            'title' => 'Documento legado demonstrativo',
            'original_name' => basename($path),
            'file_name' => basename($path),
            'path' => $path,
            'disk' => 'legacy_public',
            'mime_type' => 'text/plain',
            'extension' => 'txt',
            'size' => is_file(public_path($path)) ? filesize(public_path($path)) : 0,
            'storage_status' => 'legacy',
            'shared_with_client' => true,
        ]);
    }

    private function client(string $name): Client
    {
        return Client::query()->create([
            'person_type' => 'individual',
            'name' => $name,
            'portal_enabled' => true,
            'is_active' => true,
        ]);
    }

    private function administrator(): User
    {
        $this->seed(PermissionsSeeder::class);
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('Administrador');

        return $admin;
    }
}
