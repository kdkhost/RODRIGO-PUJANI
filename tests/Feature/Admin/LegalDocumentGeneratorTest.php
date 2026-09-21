<?php

namespace Tests\Feature\Admin;

use App\Models\Client;
use App\Models\LegalCase;
use App\Models\LegalDocumentGeneration;
use App\Models\LegalDocumentTemplate;
use App\Models\LegalDocumentTemplateVersion;
use App\Models\MediaAsset;
use App\Models\Setting;
use App\Models\User;
use App\Services\LegalDocumentGenerationService;
use App\Services\LegalDocumentTemplateManager;
use App\Services\LegalDocumentTokenEngine;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use LogicException;
use RuntimeException;
use setasign\Fpdi\Fpdi;
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

    public function test_template_create_page_renders_visual_editor_without_error(): void
    {
        $actor = $this->actor();

        $this->actingAs($actor)
            ->get(route('admin.legal-document-templates.create'))
            ->assertOk()
            ->assertSee('Editor visual milimétrico')
            ->assertSee('Grade e margens')
            ->assertSee('Ajuste livre fora da margem')
            ->assertSee('Nova página copia automaticamente o papel timbrado atual')
            ->assertSee('data-document-designer', false)
            ->assertSee('data-doc-grid-visible', false)
            ->assertSee('data-doc-bg-preview', false)
            ->assertSee('data-doc-field="text_html"', false)
            ->assertSee(route('admin.legal-document-templates.background-upload'), false);
    }

    public function test_template_index_renders_latest_version_without_ambiguous_query(): void
    {
        $actor = $this->actor();
        $manager = app(LegalDocumentTemplateManager::class);

        $template = $manager->create(
            $actor,
            $this->metadata('contrato-honorarios', LegalDocumentTemplate::CONTEXT_CLIENT),
            'Contrato de {{client.name}}',
            $this->definition('Primeira versão de {{client.name}}.')
        );

        $manager->createVersion(
            $actor,
            $template,
            'Contrato atualizado de {{client.name}}',
            $this->definition('Segunda versão de {{client.name}}.')
        );

        $this->actingAs($actor)
            ->get(route('admin.legal-document-templates.index'))
            ->assertOk()
            ->assertSee('contrato-honorarios')
            ->assertSee('v2');
    }

    public function test_template_create_page_uses_default_background_setting(): void
    {
        $actor = $this->actor();
        $this->setting('legal_documents.default_background_path', 'uploads/legal-document-backgrounds/padrao.png');
        $this->setting('legal_documents.default_background_opacity', '0.35');
        $this->setting('legal_documents.default_background_fit', 'contain');

        $this->actingAs($actor)
            ->get(route('admin.legal-document-templates.create'))
            ->assertOk()
            ->assertSee('data-default-background-path="uploads/legal-document-backgrounds/padrao.png"', false)
            ->assertSee('data-default-background-opacity="0.35"', false)
            ->assertSee('data-default-background-fit="contain"', false)
            ->assertSee('uploads/legal-document-backgrounds/padrao.png');
    }

    public function test_template_manager_can_upload_a4_background_without_media_library_permission(): void
    {
        $actor = User::factory()->create(['is_active' => true]);
        $actor->givePermissionTo(['admin.access', 'legal-document-templates.manage']);

        try {
            $response = $this->actingAs($actor)
                ->postJson(route('admin.legal-document-templates.background-upload'), [
                    'background' => UploadedFile::fake()->image('papel-timbrado.png', 1240, 1754),
                ])
                ->assertOk()
                ->assertJsonPath('message', 'Plano de fundo enviado com sucesso.');

            $path = $response->json('path');
            $this->assertIsString($path);
            $this->assertStringStartsWith('uploads/legal-document-backgrounds/', $path);
            $this->assertFileExists(public_path($path));
            $this->assertDatabaseHas('media_assets', [
                'path' => $path,
                'type' => 'image',
                'uploaded_by' => $actor->id,
            ]);
            $this->assertSame(1, MediaAsset::query()->count());
        } finally {
            File::deleteDirectory(public_path('uploads/legal-document-backgrounds'));
        }
    }

    public function test_template_manager_can_save_default_background_from_panel(): void
    {
        $actor = User::factory()->create(['is_active' => true]);
        $actor->givePermissionTo(['admin.access', 'legal-document-templates.manage']);
        $directory = public_path('uploads/legal-document-backgrounds');
        File::ensureDirectoryExists($directory);
        File::put($directory.'/padrao.png', UploadedFile::fake()->image('padrao.png', 1240, 1754)->getContent());

        try {
            $this->actingAs($actor)
                ->postJson(route('admin.legal-document-templates.default-background'), [
                    'path' => 'uploads/legal-document-backgrounds/padrao.png',
                    'opacity' => 0.42,
                    'fit' => 'cover',
                ])
                ->assertOk()
                ->assertJsonPath('background.path', 'uploads/legal-document-backgrounds/padrao.png')
                ->assertJsonPath('background.opacity', 0.42)
                ->assertJsonPath('background.fit', 'cover');

            $this->assertDatabaseHas('settings', [
                'key' => 'legal_documents.default_background_path',
                'value' => 'uploads/legal-document-backgrounds/padrao.png',
            ]);
            $this->assertDatabaseHas('settings', [
                'key' => 'legal_documents.default_background_opacity',
                'value' => '0.42',
            ]);
            $this->assertDatabaseHas('settings', [
                'key' => 'legal_documents.default_background_fit',
                'value' => 'cover',
            ]);
        } finally {
            File::deleteDirectory(public_path('uploads/legal-document-backgrounds'));
        }
    }

    public function test_visual_template_moves_signature_fields_to_last_page_preserving_position(): void
    {
        $actor = $this->actor();
        $definition = [
            'layout' => 'absolute',
            'unit' => 'mm',
            'paper' => ['size' => 'A4', 'width_mm' => 210, 'height_mm' => 297],
            'pages' => [
                [
                    'width_mm' => 210,
                    'height_mm' => 297,
                    'background' => [
                        'color' => '#ffffff',
                        'image_path' => 'uploads/legal-document-backgrounds/papel-timbrado.png',
                        'image_opacity' => 0.25,
                        'image_fit' => 'cover',
                    ],
                    'elements' => [
                        [
                            'id' => 'corpo',
                            'type' => 'text',
                            'x_mm' => 22,
                            'y_mm' => 38,
                            'w_mm' => 166,
                            'h_mm' => 110,
                            'text' => 'Documento de {{client.name}}.',
                            'font_size_pt' => 11,
                            'font_weight' => '400',
                            'line_height' => 1.35,
                            'align' => 'justify',
                            'color' => '#111827',
                            'opacity' => 1,
                        ],
                        [
                            'id' => 'assinatura-cliente',
                            'type' => 'signature',
                            'x_mm' => 38,
                            'y_mm' => 232,
                            'w_mm' => 82,
                            'h_mm' => 24,
                            'label' => 'Assinatura do cliente',
                            'signer_order' => 1,
                            'required' => true,
                            'border_color' => '#111827',
                            'opacity' => 1,
                        ],
                        [
                            'id' => 'testemunha',
                            'type' => 'signature',
                            'x_mm' => 126,
                            'y_mm' => 232,
                            'w_mm' => 54,
                            'h_mm' => 24,
                            'label' => 'Testemunha',
                            'signer_order' => 2,
                            'required' => false,
                            'border_color' => '#111827',
                            'opacity' => 1,
                        ],
                    ],
                ],
                [
                    'width_mm' => 210,
                    'height_mm' => 297,
                    'background' => [
                        'image_path' => 'uploads/legal-document-backgrounds/papel-timbrado.png',
                    ],
                    'elements' => [],
                ],
            ],
        ];

        $template = app(LegalDocumentTemplateManager::class)->create(
            $actor,
            $this->metadata('assinaturas-ultima-pagina', LegalDocumentTemplate::CONTEXT_CLIENT, LegalDocumentTemplate::FORMAT_PDF),
            'Documento de {{client.name}}',
            $definition
        );
        $storedDefinition = $template->versions()->firstOrFail()->definition;

        $this->assertCount(1, $storedDefinition['pages'][0]['elements']);
        $this->assertSame('cover', $storedDefinition['pages'][1]['background']['image_fit']);
        $this->assertSame(0.08, $storedDefinition['pages'][1]['background']['image_opacity']);
        $this->assertSame('text', $storedDefinition['pages'][0]['elements'][0]['type']);
        $this->assertCount(2, $storedDefinition['pages'][1]['elements']);
        $this->assertSame('assinatura-cliente', $storedDefinition['pages'][1]['elements'][0]['id']);
        $this->assertEquals(38.0, $storedDefinition['pages'][1]['elements'][0]['x_mm']);
        $this->assertEquals(232.0, $storedDefinition['pages'][1]['elements'][0]['y_mm']);
        $this->assertSame('testemunha', $storedDefinition['pages'][1]['elements'][1]['id']);
        $this->assertEquals(126.0, $storedDefinition['pages'][1]['elements'][1]['x_mm']);
        $this->assertEquals(232.0, $storedDefinition['pages'][1]['elements'][1]['y_mm']);
    }

    public function test_visual_template_accepts_summernote_rich_text_and_escapes_token_values_in_html(): void
    {
        $actor = $this->actor();
        $definition = [
            'layout' => 'absolute',
            'unit' => 'mm',
            'paper' => ['size' => 'A4', 'width_mm' => 210, 'height_mm' => 297],
            'pages' => [[
                'width_mm' => 210,
                'height_mm' => 297,
                'background' => ['color' => '#ffffff', 'image_path' => '', 'image_opacity' => 0.08, 'image_fit' => 'cover'],
                'elements' => [[
                    'id' => 'corpo',
                    'type' => 'text',
                    'x_mm' => 24,
                    'y_mm' => 42,
                    'w_mm' => 160,
                    'h_mm' => 80,
                    'text' => 'Cliente: {{client.name}}',
                    'text_html' => '<p><strong>Cliente:</strong> {{client.name}}</p><script>alert("x")</script>',
                    'font_size_pt' => 11,
                    'font_weight' => '400',
                    'line_height' => 1.35,
                    'align' => 'justify',
                    'color' => '#111827',
                    'opacity' => 1,
                ]],
            ]],
        ];

        $template = app(LegalDocumentTemplateManager::class)->create(
            $actor,
            $this->metadata('texto-rico-summernote', LegalDocumentTemplate::CONTEXT_CLIENT, LegalDocumentTemplate::FORMAT_PDF),
            'Documento de {{client.name}}',
            $definition
        );

        $element = $template->latestVersion->definition['pages'][0]['elements'][0];
        $this->assertStringContainsString('<strong>Cliente:</strong>', $element['text_html']);
        $this->assertStringNotContainsString('script', $element['text_html']);

        $client = Client::query()->create([
            'person_type' => 'individual',
            'name' => 'Cliente <b>Especial</b>',
            'document_number' => '123.456.789-09',
            'email' => 'cliente-rich@example.test',
            'address_zip' => '01310100',
            'address_street' => 'Avenida Paulista',
            'address_number' => '1000',
            'address_city' => 'São Paulo',
            'address_state' => 'SP',
            'assigned_lawyer_id' => $actor->id,
            'created_by' => $actor->id,
            'is_active' => true,
        ]);

        $tokens = app(LegalDocumentTokenEngine::class);
        $rendered = $tokens->renderDefinition(
            $template->latestVersion->definition,
            $tokens->context($client, null, $actor, now())
        );

        $html = $rendered['pages'][0]['elements'][0]['text_html'];
        $this->assertStringContainsString('Cliente &lt;b&gt;Especial&lt;/b&gt;', $html);
        $this->assertStringNotContainsString('<b>Especial</b>', $html);
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

    public function test_generated_pdf_redirects_to_documents_and_can_be_sent_to_signature(): void
    {
        config()->set('signatures.enabled', true);
        Permission::findOrCreate('signature-requests.create', 'web');

        $actor = $this->actor();
        $actor->givePermissionTo('signature-requests.create');
        [$client] = $this->legalContext($actor);

        $template = app(LegalDocumentTemplateManager::class)->create(
            $actor,
            $this->metadata('contrato-assinatura', LegalDocumentTemplate::CONTEXT_CLIENT, LegalDocumentTemplate::FORMAT_PDF),
            'Contrato de {{client.name}}',
            $this->definition('Cliente: {{client.name}}')
        );

        $response = $this->actingAs($actor)->post(
            route('admin.legal-document-templates.generate.store', $template),
            [
                'legal_document_template_version_id' => $template->versions()->firstOrFail()->id,
                'client_id' => $client->id,
                'output_format' => LegalDocumentTemplate::FORMAT_PDF,
            ]
        );

        $generation = LegalDocumentGeneration::query()->with('legalDocument')->firstOrFail();
        $document = $generation->legalDocument;

        $this->assertSame('pdf', $document->extension);
        $this->assertSame($client->id, $document->client_id);
        $this->assertSame('legal_documents', $document->disk);
        Storage::disk('legal_documents')->assertExists($document->path);

        $response
            ->assertRedirect(route('admin.legal-documents.index', ['highlight_document' => $document->id]))
            ->assertSessionHas('generated_document_id', $document->id);

        $this->actingAs($actor)
            ->get(route('admin.legal-documents.index', ['highlight_document' => $document->id]))
            ->assertOk()
            ->assertSee('Documento gerado e salvo em Jurídico &gt; Documentos.', false)
            ->assertSee($document->title)
            ->assertSee('Enviar para assinatura', false)
            ->assertSee(route('admin.signature-requests.create', ['document' => $document->id]), false);
    }

    public function test_visual_absolute_template_generates_a4_pdf_and_rejects_docx_reflow(): void
    {
        $actor = $this->actor();
        [$client] = $this->legalContext($actor);
        $definition = [
            'layout' => 'absolute',
            'unit' => 'mm',
            'paper' => ['size' => 'A4', 'width_mm' => 210, 'height_mm' => 297],
            'guides' => [
                'grid' => ['visible' => true, 'snap' => true, 'size_mm' => 5],
                'margins' => [
                    'enabled' => true,
                    'free_positioning' => false,
                    'top_mm' => 18,
                    'right_mm' => 16,
                    'bottom_mm' => 22,
                    'left_mm' => 16,
                ],
            ],
            'pages' => [
                [
                    'width_mm' => 210,
                    'height_mm' => 297,
                    'background' => [
                        'color' => '#ffffff',
                        'image_path' => '',
                        'image_opacity' => 0.18,
                        'image_fit' => 'cover',
                    ],
                    'elements' => [
                        [
                            'id' => 'cabecalho',
                            'type' => 'text',
                            'x_mm' => 20,
                            'y_mm' => 24,
                            'w_mm' => 170,
                            'h_mm' => 22,
                            'text' => 'Contrato de honorários',
                            'font_size_pt' => 16,
                            'font_weight' => '700',
                            'line_height' => 1.2,
                            'align' => 'center',
                            'color' => '#111827',
                            'opacity' => 1,
                        ],
                        [
                            'id' => 'corpo',
                            'type' => 'text',
                            'x_mm' => 20,
                            'y_mm' => 62,
                            'w_mm' => 170,
                            'h_mm' => 90,
                            'text' => 'Cliente {{client.name}}, CPF/CNPJ {{client.document_number}}.',
                            'font_size_pt' => 12,
                            'font_weight' => '400',
                            'line_height' => 1.35,
                            'align' => 'left',
                            'color' => '#111827',
                            'opacity' => 1,
                        ],
                    ],
                ],
                [
                    'width_mm' => 210,
                    'height_mm' => 297,
                    'background' => [
                        'color' => '#ffffff',
                        'image_path' => '',
                        'image_opacity' => 0.18,
                        'image_fit' => 'cover',
                    ],
                    'elements' => [
                        [
                            'id' => 'assinatura-cliente',
                            'type' => 'signature',
                            'x_mm' => 28,
                            'y_mm' => 226,
                            'w_mm' => 72,
                            'h_mm' => 24,
                            'label' => 'Assinatura do cliente',
                            'signer_order' => 1,
                            'required' => true,
                            'border_color' => '#111827',
                            'opacity' => 1,
                        ],
                        [
                            'id' => 'testemunha-opcional',
                            'type' => 'signature',
                            'x_mm' => 110,
                            'y_mm' => 226,
                            'w_mm' => 72,
                            'h_mm' => 24,
                            'label' => 'Testemunha opcional',
                            'signer_order' => 2,
                            'required' => false,
                            'border_color' => '#111827',
                            'opacity' => 1,
                        ],
                    ],
                ],
            ],
        ];

        $template = app(LegalDocumentTemplateManager::class)->create(
            $actor,
            $this->metadata('contrato-visual-a4', LegalDocumentTemplate::CONTEXT_CLIENT, LegalDocumentTemplate::FORMAT_PDF),
            'Contrato visual de {{client.name}}',
            $definition
        );
        $version = $template->versions()->firstOrFail();

        $this->assertSame('absolute', $version->definition['layout']);
        $this->assertTrue($version->definition['guides']['grid']['visible']);
        $this->assertTrue($version->definition['guides']['grid']['snap']);
        $this->assertEquals(5.0, $version->definition['guides']['grid']['size_mm']);
        $this->assertTrue($version->definition['guides']['margins']['enabled']);
        $this->assertFalse($version->definition['guides']['margins']['free_positioning']);
        $this->assertEquals(16.0, $version->definition['guides']['margins']['left_mm']);
        $this->assertSame('cover', $version->definition['pages'][0]['background']['image_fit']);
        $this->assertSame(2, $version->definition['pages'][1]['elements'][1]['signer_order']);
        $this->assertFalse($version->definition['pages'][1]['elements'][1]['required']);

        $generation = app(LegalDocumentGenerationService::class)->generate(
            $actor,
            $template,
            $version,
            [
                'context_scope' => LegalDocumentTemplate::CONTEXT_CLIENT,
                'output_format' => LegalDocumentTemplate::FORMAT_PDF,
                'client_id' => $client->id,
            ]
        );

        $pdfContents = Storage::disk('legal_documents')->get($generation->legalDocument->path);
        $this->assertStringStartsWith('%PDF-', $pdfContents);
        $this->assertSame('pdf', $generation->legalDocument->extension);
        $this->assertSame(2, (new Fpdi())->setSourceFile(Storage::disk('legal_documents')->path($generation->legalDocument->path)));

        try {
            app(LegalDocumentGenerationService::class)->generate(
                $actor,
                $template,
                $version,
                [
                    'context_scope' => LegalDocumentTemplate::CONTEXT_CLIENT,
                    'output_format' => LegalDocumentTemplate::FORMAT_DOCX,
                    'client_id' => $client->id,
                ]
            );

            $this->fail('Templates visuais milimétricos não devem ser exportados em DOCX.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('output_format', $exception->errors());
        }
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

    private function setting(string $key, string $value, string $type = 'text'): void
    {
        Setting::query()->updateOrCreate(['key' => $key], [
            'group' => str($key)->before('.')->toString(),
            'label' => $key,
            'type' => $type,
            'value' => $value,
            'is_public' => false,
        ]);

        foreach (['site_settings.map.v2', 'site_settings.all.v2'] as $cacheKey) {
            cache()->forget($cacheKey);
        }
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
