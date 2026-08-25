<?php

namespace Tests\Feature\Admin;

use App\Models\Client;
use App\Models\LegalCase;
use App\Models\LegalDocumentGeneration;
use App\Models\LegalDocumentTemplate;
use App\Models\LegalDocumentTemplateVersion;
use App\Models\User;
use App\Services\LegalDocumentGenerationService;
use App\Services\LegalDocumentTemplateManager;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use LogicException;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use ZipArchive;

class LegalDocumentGeneratorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.key', 'base64:'.base64_encode(str_repeat('k', 32)));
        Storage::fake('legal_documents');

        foreach ([
            'admin.access',
            'legal-document-templates.view',
            'legal-document-templates.manage',
            'legal-document-templates.generate',
            'legal-documents.manage',
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_template_management_and_generation_require_explicit_permissions(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $template = LegalDocumentTemplate::query()->create([
            'name' => 'Template restrito',
            'slug' => 'template-restrito',
            'context_scope' => LegalDocumentTemplate::CONTEXT_CLIENT,
            'default_output_format' => LegalDocumentTemplate::FORMAT_DOCX,
            'is_active' => true,
        ]);

        $this->assertFalse(Gate::forUser($user)->allows('viewAny', LegalDocumentTemplate::class));
        $this->assertFalse(Gate::forUser($user)->allows('generate', $template));

        $this->expectException(AuthorizationException::class);
        app(LegalDocumentTemplateManager::class)->create(
            $user,
            $this->metadata('sem-permissao', LegalDocumentTemplate::CONTEXT_CLIENT),
            'Documento de {{client.name}}',
            $this->definition('Cliente: {{client.name}}')
        );
    }

    public function test_unknown_or_scope_incompatible_tokens_are_rejected_without_persisting_template(): void
    {
        $actor = $this->actor();
        $manager = app(LegalDocumentTemplateManager::class);

        try {
            $manager->create(
                $actor,
                $this->metadata('token-desconhecido', LegalDocumentTemplate::CONTEXT_CLIENT),
                'Documento {{system.current_date}}',
                $this->definition('Valor: {{php.eval}}')
            );
            $this->fail('O token desconhecido deveria ter sido rejeitado.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('definition_json', $exception->errors());
        }

        try {
            $manager->create(
                $actor,
                $this->metadata('token-de-processo', LegalDocumentTemplate::CONTEXT_CLIENT),
                'Documento de {{client.name}}',
                $this->definition('Processo: {{case.process_number}}')
            );
            $this->fail('O token de processo deveria ser incompatível com o contexto somente cliente.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('definition_json', $exception->errors());
        }

        try {
            $manager->create(
                $actor,
                $this->metadata('expressao-insegura', LegalDocumentTemplate::CONTEXT_CLIENT),
                'Documento de {{client.name}}',
                $this->definition('Data: {{system.current_date | raw}}')
            );
            $this->fail('Expressões fora da allowlist deveriam ser rejeitadas.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('definition_json', $exception->errors());
        }

        $this->assertDatabaseCount('legal_document_templates', 0);
        $this->assertDatabaseCount('legal_document_template_versions', 0);
    }

    public function test_published_versions_are_immutable_and_a_new_version_preserves_the_original(): void
    {
        $actor = $this->actor();
        $manager = app(LegalDocumentTemplateManager::class);
        $template = $manager->create(
            $actor,
            $this->metadata('procuracao', LegalDocumentTemplate::CONTEXT_CLIENT),
            'Procuração de {{client.name}}',
            $this->definition('Primeira versão para {{client.name}}.')
        );
        $versionOne = $template->versions()->firstOrFail();

        $versionTwo = $manager->createVersion(
            $actor,
            $template,
            'Procuração atualizada de {{client.name}}',
            $this->definition('Segunda versão para {{client.name}}.')
        );

        $this->assertSame(1, $versionOne->version);
        $this->assertSame(2, $versionTwo->version);
        $this->assertNotSame($versionOne->content_sha256, $versionTwo->content_sha256);
        $this->assertSame(
            'Primeira versão para {{client.name}}.',
            $versionOne->fresh()->definition['blocks'][0]['text']
        );

        $this->expectException(LogicException::class);
        $versionOne->update(['title_template' => 'Tentativa de sobrescrita']);
    }

    public function test_docx_and_pdf_are_real_private_documents_with_hash_and_complete_audit(): void
    {
        $actor = $this->actor();
        [$client, $case] = $this->legalContext($actor);
        $service = app(LegalDocumentGenerationService::class);
        $manager = app(LegalDocumentTemplateManager::class);

        $clientTemplate = $manager->create(
            $actor,
            $this->metadata('ficha-cliente', LegalDocumentTemplate::CONTEXT_CLIENT),
            'Ficha de {{client.name}}',
            $this->definition("Cliente: {{client.name}}\nCPF/CNPJ: {{client.document_number}}\nCláusula: <segura> & preservada.")
        );
        $docxGeneration = $service->generate(
            $actor,
            $clientTemplate,
            $clientTemplate->versions()->firstOrFail(),
            [
                'context_scope' => LegalDocumentTemplate::CONTEXT_CLIENT,
                'output_format' => LegalDocumentTemplate::FORMAT_DOCX,
                'client_id' => $client->id,
                'shared_with_client' => true,
            ]
        );

        $docx = $docxGeneration->legalDocument;
        Storage::disk('legal_documents')->assertExists($docx->path);
        $docxContents = Storage::disk('legal_documents')->get($docx->path);
        $this->assertStringStartsWith("PK\x03\x04", $docxContents);
        $this->assertSame(hash('sha256', $docxContents), $docx->sha256);
        $this->assertSame($docx->sha256, $docxGeneration->rendered_sha256);
        $this->assertSame('legal_documents', $docx->disk);
        $this->assertSame('private', $docx->storage_status);
        $this->assertSame('docx', $docx->extension);
        $this->assertTrue($docx->is_sensitive);
        $this->assertTrue($docx->shared_with_client);

        $archive = new ZipArchive();
        $this->assertTrue($archive->open(Storage::disk('legal_documents')->path($docx->path)));
        foreach (['[Content_Types].xml', '_rels/.rels', 'word/document.xml', 'word/styles.xml'] as $requiredEntry) {
            $this->assertNotFalse($archive->locateName($requiredEntry));
        }
        $documentXml = $archive->getFromName('word/document.xml');
        $contentTypesXml = $archive->getFromName('[Content_Types].xml');
        $archive->close();
        $this->assertIsString($documentXml);
        $this->assertStringContainsString('Cliente Alfa', $documentXml);
        $this->assertStringContainsString('&lt;segura&gt; &amp; preservada.', $documentXml);
        $this->assertIsString($contentTypesXml);
        $this->assertTrue((new \DOMDocument())->loadXML($documentXml));
        $this->assertTrue((new \DOMDocument())->loadXML($contentTypesXml));

        $caseTemplate = $manager->create(
            $actor,
            $this->metadata('resumo-processo', LegalDocumentTemplate::CONTEXT_CASE, LegalDocumentTemplate::FORMAT_PDF),
            'Resumo do processo {{case.process_number}}',
            $this->definition('Cliente {{client.name}}, processo {{case.title}}, em {{system.current_date}}.')
        );
        $pdfGeneration = $service->generate(
            $actor,
            $caseTemplate,
            $caseTemplate->versions()->firstOrFail(),
            [
                'context_scope' => LegalDocumentTemplate::CONTEXT_CASE,
                'output_format' => LegalDocumentTemplate::FORMAT_PDF,
                'legal_case_id' => $case->id,
            ]
        );

        $pdf = $pdfGeneration->legalDocument;
        $pdfContents = Storage::disk('legal_documents')->get($pdf->path);
        $this->assertStringStartsWith('%PDF-', $pdfContents);
        $this->assertSame(hash('sha256', $pdfContents), $pdf->sha256);
        $this->assertSame('pdf', $pdf->extension);
        $this->assertSame($client->id, $pdfGeneration->client_id);
        $this->assertSame($case->id, $pdfGeneration->legal_case_id);
        $this->assertSame($actor->id, $pdfGeneration->generated_by);
        $this->assertSame($caseTemplate->id, $pdfGeneration->legal_document_template_id);
        $this->assertSame($caseTemplate->versions()->firstOrFail()->id, $pdfGeneration->legal_document_template_version_id);
        $this->assertSame(64, strlen($pdfGeneration->context_sha256));
        $this->assertSame(64, strlen($pdfGeneration->template_sha256));
        $this->assertSame('Cliente Alfa', $pdfGeneration->context_snapshot['tokens']['client.name']);

        $rawSnapshot = (string) DB::table('legal_document_generations')
            ->whereKey($pdfGeneration->id)
            ->value('context_snapshot');
        $this->assertStringNotContainsString('Cliente Alfa', $rawSnapshot);

        $this->actingAs($actor)
            ->get(route('admin.legal-documents.download', $pdf))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_client_case_generation_checks_ownership_and_never_overwrites_an_original(): void
    {
        $actor = $this->actor();
        [$client, $case] = $this->legalContext($actor);
        $otherClient = Client::query()->create([
            'person_type' => 'individual',
            'name' => 'Cliente incompatível',
            'assigned_lawyer_id' => $actor->id,
            'created_by' => $actor->id,
            'is_active' => true,
        ]);
        $template = app(LegalDocumentTemplateManager::class)->create(
            $actor,
            $this->metadata('contrato-processo', LegalDocumentTemplate::CONTEXT_CLIENT_CASE),
            'Contrato de {{client.name}}',
            $this->definition('Cliente {{client.name}} — processo {{case.process_number}}.')
        );
        $version = $template->versions()->firstOrFail();
        $service = app(LegalDocumentGenerationService::class);

        try {
            $service->generate($actor, $template, $version, [
                'context_scope' => LegalDocumentTemplate::CONTEXT_CLIENT_CASE,
                'output_format' => LegalDocumentTemplate::FORMAT_DOCX,
                'client_id' => $otherClient->id,
                'legal_case_id' => $case->id,
            ]);
            $this->fail('Cliente e processo incompatíveis deveriam ser rejeitados.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('client_id', $exception->errors());
        }

        $first = $service->generate($actor, $template, $version, [
            'context_scope' => LegalDocumentTemplate::CONTEXT_CLIENT_CASE,
            'output_format' => LegalDocumentTemplate::FORMAT_DOCX,
            'client_id' => $client->id,
            'legal_case_id' => $case->id,
        ]);
        $second = $service->generate($actor, $template, $version, [
            'context_scope' => LegalDocumentTemplate::CONTEXT_CLIENT_CASE,
            'output_format' => LegalDocumentTemplate::FORMAT_DOCX,
            'client_id' => $client->id,
            'legal_case_id' => $case->id,
        ]);

        $this->assertNotSame($first->legal_document_id, $second->legal_document_id);
        $this->assertNotSame($first->legalDocument->path, $second->legalDocument->path);
        Storage::disk('legal_documents')->assertExists($first->legalDocument->path);
        Storage::disk('legal_documents')->assertExists($second->legalDocument->path);
        $this->assertDatabaseCount('legal_document_generations', 2);
    }

    public function test_associated_user_cannot_generate_or_download_documents_from_another_client(): void
    {
        $actor = $this->actor();
        $owner = $this->actor();
        [$client] = $this->legalContext($owner);
        $template = app(LegalDocumentTemplateManager::class)->create(
            $actor,
            $this->metadata('isolamento', LegalDocumentTemplate::CONTEXT_CLIENT),
            'Documento de {{client.name}}',
            $this->definition('Conteúdo privado de {{client.name}}.')
        );

        $service = app(LegalDocumentGenerationService::class);
        try {
            $service->generate(
                $actor,
                $template,
                $template->versions()->firstOrFail(),
                [
                    'context_scope' => LegalDocumentTemplate::CONTEXT_CLIENT,
                    'output_format' => LegalDocumentTemplate::FORMAT_PDF,
                    'client_id' => $client->id,
                ]
            );
            $this->fail('O usuário não deveria gerar documento para cliente de outro responsável.');
        } catch (ModelNotFoundException) {
            $this->assertDatabaseCount('legal_documents', 0);
        }

        $generation = $service->generate(
            $owner,
            $template,
            $template->versions()->firstOrFail(),
            [
                'context_scope' => LegalDocumentTemplate::CONTEXT_CLIENT,
                'output_format' => LegalDocumentTemplate::FORMAT_PDF,
                'client_id' => $client->id,
            ]
        );

        $this->actingAs($actor)
            ->get(route('admin.legal-documents.download', $generation->legalDocument))
            ->assertForbidden();

        $this->actingAs($actor)
            ->get(route('admin.legal-document-templates.show', $template))
            ->assertOk()
            ->assertDontSee('Cliente Alfa');

        $this->actingAs($owner)
            ->get(route('admin.legal-document-templates.show', $template))
            ->assertOk()
            ->assertSee('Cliente Alfa');
    }

    public function test_generation_audit_records_are_immutable(): void
    {
        $actor = $this->actor();
        [$client] = $this->legalContext($actor);
        $template = app(LegalDocumentTemplateManager::class)->create(
            $actor,
            $this->metadata('auditoria', LegalDocumentTemplate::CONTEXT_CLIENT),
            'Documento de {{client.name}}',
            $this->definition('Emitido em {{system.current_datetime}}.')
        );
        $generation = app(LegalDocumentGenerationService::class)->generate(
            $actor,
            $template,
            $template->versions()->firstOrFail(),
            [
                'context_scope' => LegalDocumentTemplate::CONTEXT_CLIENT,
                'output_format' => LegalDocumentTemplate::FORMAT_PDF,
                'client_id' => $client->id,
            ]
        );

        $this->expectException(LogicException::class);
        LegalDocumentGeneration::query()->findOrFail($generation->id)->update(['output_format' => 'docx']);
    }

    public function test_tampered_template_version_is_rejected_before_file_generation(): void
    {
        $actor = $this->actor();
        [$client] = $this->legalContext($actor);
        $template = app(LegalDocumentTemplateManager::class)->create(
            $actor,
            $this->metadata('integridade-template', LegalDocumentTemplate::CONTEXT_CLIENT),
            'Documento de {{client.name}}',
            $this->definition('Conteúdo íntegro para {{client.name}}.')
        );
        $version = $template->versions()->firstOrFail();
        DB::table('legal_document_template_versions')
            ->where('id', $version->id)
            ->update(['title_template' => 'Conteúdo adulterado']);

        try {
            app(LegalDocumentGenerationService::class)->generate(
                $actor,
                $template,
                $version,
                [
                    'context_scope' => LegalDocumentTemplate::CONTEXT_CLIENT,
                    'output_format' => LegalDocumentTemplate::FORMAT_PDF,
                    'client_id' => $client->id,
                ]
            );
            $this->fail('A adulteração da versão deveria interromper a geração.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('integridade', $exception->getMessage());
        }

        $this->assertDatabaseCount('legal_documents', 0);
        $this->assertDatabaseCount('legal_document_generations', 0);
        $this->assertSame([], Storage::disk('legal_documents')->allFiles());
    }

    private function actor(): User
    {
        $actor = User::factory()->create(['is_active' => true]);
        $actor->givePermissionTo([
            'admin.access',
            'legal-document-templates.view',
            'legal-document-templates.manage',
            'legal-document-templates.generate',
            'legal-documents.manage',
        ]);

        return $actor;
    }

    private function legalContext(User $actor): array
    {
        $client = Client::query()->create([
            'person_type' => 'individual',
            'name' => 'Cliente Alfa',
            'document_number' => '123.456.789-09',
            'email' => 'cliente@example.test',
            'phone' => '11987654321',
            'address_zip' => '01310100',
            'address_street' => 'Avenida Paulista',
            'address_number' => '1000',
            'address_city' => 'São Paulo',
            'address_state' => 'SP',
            'assigned_lawyer_id' => $actor->id,
            'created_by' => $actor->id,
            'is_active' => true,
        ]);
        $case = LegalCase::query()->create([
            'client_id' => $client->id,
            'primary_lawyer_id' => $actor->id,
            'title' => 'Ação de cobrança',
            'process_number' => '1000000-00.2026.8.26.0100',
            'status' => 'active',
            'phase' => 'initial',
            'priority' => 'medium',
            'is_confidential' => true,
            'is_active' => true,
            'created_by' => $actor->id,
        ]);

        return [$client, $case];
    }

    private function metadata(
        string $slug,
        string $scope,
        string $format = LegalDocumentTemplate::FORMAT_DOCX
    ): array {
        return [
            'name' => str($slug)->replace('-', ' ')->title()->toString(),
            'slug' => $slug,
            'description' => 'Template de teste.',
            'context_scope' => $scope,
            'default_output_format' => $format,
            'is_active' => true,
        ];
    }

    private function definition(string $text): array
    {
        return [
            'blocks' => [
                ['type' => 'paragraph', 'text' => $text],
            ],
        ];
    }
}
