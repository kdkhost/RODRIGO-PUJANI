<?php

namespace App\Services;

use App\Models\Client;
use App\Models\LegalCase;
use App\Models\LegalDocument;
use App\Models\LegalDocumentGeneration;
use App\Models\LegalDocumentTemplate;
use App\Models\LegalDocumentTemplateVersion;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class LegalDocumentGenerationService
{
    public function __construct(
        private readonly LegalDocumentTokenEngine $tokens,
        private readonly LegalDocumentOutputRenderer $renderer,
        private readonly LegalDocumentStorage $storage,
        private readonly LegalDocumentCanonicalizer $canonicalizer,
    ) {
    }

    public function generate(
        User $actor,
        LegalDocumentTemplate $template,
        LegalDocumentTemplateVersion $version,
        array $data
    ): LegalDocumentGeneration {
        $this->authorize($actor);
        $template = LegalDocumentTemplate::query()->findOrFail($template->getKey());
        $version = LegalDocumentTemplateVersion::query()
            ->where('legal_document_template_id', $template->id)
            ->findOrFail($version->getKey());
        $this->assertTemplateIntegrity($template, $version);

        $scope = (string) ($data['context_scope'] ?? $template->context_scope);
        $format = (string) ($data['output_format'] ?? $template->default_output_format);
        $this->validateOptions($template, $scope, $format);

        [$client, $legalCase] = $this->resolveContext($actor, $scope, $data);
        $generatedAt = now();
        $context = $this->tokens->context($client, $legalCase, $actor, $generatedAt);
        $title = Str::limit(Str::squish($this->tokens->render($version->title_template, $context)), 255, '');
        if ($title === '') {
            throw ValidationException::withMessages(['template' => 'O título renderizado do documento ficou vazio.']);
        }

        $renderedDefinition = $this->tokens->renderDefinition($version->definition, $context);
        $snapshot = [
            'scope' => $scope,
            'client_id' => $client?->id,
            'legal_case_id' => $legalCase?->id,
            'generated_by' => $actor->id,
            'generated_at' => $generatedAt->toIso8601String(),
            'tokens' => collect($version->allowed_tokens)
                ->mapWithKeys(fn (string $token): array => [$token => (string) ($context[$token] ?? '')])
                ->all(),
        ];
        $contextSha256 = hash('sha256', $this->canonicalizer->json($snapshot));

        $temporaryPath = $this->renderer->render($format, $title, $renderedDefinition);
        $stored = null;

        try {
            if (filesize($temporaryPath) > 15 * 1024 * 1024) {
                throw ValidationException::withMessages(['output_format' => 'O documento gerado excedeu o limite de 15 MB.']);
            }

            $uploaded = new UploadedFile(
                $temporaryPath,
                (Str::slug($title) ?: 'documento-gerado').'.'.$format,
                $format === LegalDocumentTemplate::FORMAT_PDF
                    ? 'application/pdf'
                    : 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                UPLOAD_ERR_OK,
                true,
            );
            $stored = $this->storage->store($uploaded);

            $generation = DB::transaction(function () use (
                $actor,
                $template,
                $version,
                $client,
                $legalCase,
                $data,
                $scope,
                $format,
                $title,
                $stored,
                $snapshot,
                $contextSha256,
                $generatedAt
            ): LegalDocumentGeneration {
                $document = LegalDocument::query()->create([
                    'legal_case_id' => $legalCase?->id,
                    'client_id' => $client?->id,
                    'uploaded_by' => $actor->id,
                    'title' => $title,
                    'category' => 'gerado',
                    'notes' => 'Documento gerado a partir de template jurídico versionado.',
                    'is_sensitive' => true,
                    'shared_with_client' => (bool) ($data['shared_with_client'] ?? false),
                ] + $stored);

                $generation = LegalDocumentGeneration::query()->create([
                    'legal_document_template_id' => $template->id,
                    'legal_document_template_version_id' => $version->id,
                    'legal_document_id' => $document->id,
                    'client_id' => $client?->id,
                    'legal_case_id' => $legalCase?->id,
                    'generated_by' => $actor->id,
                    'context_scope' => $scope,
                    'output_format' => $format,
                    'context_snapshot' => $snapshot,
                    'context_sha256' => $contextSha256,
                    'template_sha256' => $version->content_sha256,
                    'rendered_sha256' => $stored['sha256'],
                    'generated_at' => $generatedAt,
                ]);

                activity_log('legal_document_generations', 'generated', $document, [
                    'generation_id' => $generation->id,
                    'template_id' => $template->id,
                    'template_version_id' => $version->id,
                    'template_version' => $version->version,
                    'client_id' => $client?->id,
                    'legal_case_id' => $legalCase?->id,
                    'output_format' => $format,
                    'context_sha256' => $contextSha256,
                    'rendered_sha256' => $stored['sha256'],
                ], 'Documento jurídico gerado e armazenado privadamente.');

                return $generation;
            });

            return $generation->load(['template', 'templateVersion', 'legalDocument', 'client', 'legalCase', 'generator']);
        } catch (\Throwable $exception) {
            if (is_array($stored) && filled($stored['path'] ?? null)) {
                Storage::disk(LegalDocumentStorage::DISK)->delete($stored['path']);
            }

            throw $exception;
        } finally {
            @unlink($temporaryPath);
        }
    }

    private function authorize(User $actor): void
    {
        if (! $actor->can('legal-document-templates.generate') || ! $actor->can('legal-documents.manage')) {
            throw new AuthorizationException('Você não pode gerar documentos jurídicos.');
        }
    }

    private function assertTemplateIntegrity(
        LegalDocumentTemplate $template,
        LegalDocumentTemplateVersion $version
    ): void {
        if (! $template->is_active || (int) $version->legal_document_template_id !== (int) $template->id) {
            throw ValidationException::withMessages(['template' => 'Template ou versão indisponível para geração.']);
        }

        $content = [
            'title_template' => $version->title_template,
            'definition' => $version->definition,
            'allowed_tokens' => $version->allowed_tokens,
        ];
        $calculatedHash = hash('sha256', $this->canonicalizer->json($content));
        if (! hash_equals((string) $version->content_sha256, $calculatedHash)) {
            throw new RuntimeException('A integridade da versão do template jurídico não pôde ser confirmada.');
        }
    }

    private function validateOptions(LegalDocumentTemplate $template, string $scope, string $format): void
    {
        if ($scope !== $template->context_scope || ! array_key_exists($scope, LegalDocumentTemplate::contextScopes())) {
            throw ValidationException::withMessages(['context_scope' => 'O contexto não corresponde ao template selecionado.']);
        }
        if (! array_key_exists($format, LegalDocumentTemplate::outputFormats())) {
            throw ValidationException::withMessages(['output_format' => 'Formato de saída não suportado.']);
        }
    }

    private function resolveContext(User $actor, string $scope, array $data): array
    {
        $client = null;
        $legalCase = null;

        if (in_array($scope, [LegalDocumentTemplate::CONTEXT_CASE, LegalDocumentTemplate::CONTEXT_CLIENT_CASE], true)) {
            $legalCase = LegalCase::query()
                ->visibleTo($actor)
                ->with('client')
                ->findOrFail((int) ($data['legal_case_id'] ?? 0));
        }

        if (in_array($scope, [LegalDocumentTemplate::CONTEXT_CLIENT, LegalDocumentTemplate::CONTEXT_CLIENT_CASE], true)) {
            $client = Client::query()
                ->visibleTo($actor)
                ->findOrFail((int) ($data['client_id'] ?? 0));
        }

        if ($scope === LegalDocumentTemplate::CONTEXT_CASE) {
            $client = $legalCase?->client;
        }

        if ($scope === LegalDocumentTemplate::CONTEXT_CLIENT_CASE
            && (int) $legalCase?->client_id !== (int) $client?->id) {
            throw ValidationException::withMessages([
                'client_id' => 'O cliente selecionado não é o proprietário do processo informado.',
            ]);
        }

        return [$client, $legalCase];
    }
}
