<?php

namespace Tests\Feature\Admin;

use App\Models\Client;
use App\Models\LegalCase;
use App\Models\LegalDocument;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LegalDocumentSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_pdf_is_stored_privately_with_hash_and_download_requires_authorization(): void
    {
        Storage::fake('legal_documents');
        $this->seed(PermissionsSeeder::class);
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('Administrador');
        $client = Client::query()->create(['person_type' => 'individual', 'name' => 'Cliente', 'is_active' => true]);
        $case = LegalCase::query()->create([
            'client_id' => $client->id, 'title' => 'Caso', 'status' => 'active', 'phase' => 'initial',
            'priority' => 'medium', 'portal_visible' => true, 'is_confidential' => true, 'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->postJson(route('admin.legal-documents.store'), [
            'legal_case_id' => $case->id,
            'client_id' => $client->id,
            'title' => 'Petição',
            'file' => UploadedFile::fake()->createWithContent('peticao.pdf', "%PDF-1.7\nconteudo"),
        ]);

        $response->assertOk();
        $document = LegalDocument::query()->firstOrFail();
        $this->assertSame('legal_documents', $document->disk);
        $this->assertSame(64, strlen((string) $document->sha256));
        Storage::disk('legal_documents')->assertExists($document->path);
        $this->post(route('logout'));
        $this->get(route('admin.legal-documents.download', $document))->assertRedirect(route('login'));
        $this->actingAs($admin)->get(route('admin.legal-documents.download', $document))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_executable_double_extension_and_mismatched_client_are_rejected(): void
    {
        Storage::fake('legal_documents');
        $this->seed(PermissionsSeeder::class);
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('Administrador');
        $clientA = Client::query()->create(['person_type' => 'individual', 'name' => 'A', 'is_active' => true]);
        $clientB = Client::query()->create(['person_type' => 'individual', 'name' => 'B', 'is_active' => true]);
        $case = LegalCase::query()->create([
            'client_id' => $clientA->id, 'title' => 'Caso', 'status' => 'active', 'phase' => 'initial',
            'priority' => 'medium', 'portal_visible' => true, 'is_confidential' => true, 'is_active' => true,
        ]);

        $this->actingAs($admin)->postJson(route('admin.legal-documents.store'), [
            'legal_case_id' => $case->id,
            'client_id' => $clientB->id,
            'title' => 'Malicioso',
            'file' => UploadedFile::fake()->createWithContent('contrato.pdf.php', '<?php echo 1;'),
        ])->assertUnprocessable()->assertJsonValidationErrors(['client_id']);

        $this->assertDatabaseCount('legal_documents', 0);
    }
}
