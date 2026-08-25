<?php

namespace App\Services;

use App\Models\LegalDocumentTemplate;
use App\Models\LegalDocumentTemplateVersion;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LegalDocumentTemplateManager
{
    public function __construct(
        private readonly LegalDocumentTemplateDefinition $definitions,
        private readonly LegalDocumentTokenEngine $tokens,
        private readonly LegalDocumentCanonicalizer $canonicalizer,
    ) {
    }

    public function create(User $actor, array $templateData, string $titleTemplate, array $definition): LegalDocumentTemplate
    {
        $this->authorize($actor);
        $this->validateMetadata($templateData);
        $scope = (string) ($templateData['context_scope'] ?? '');
        $normalized = $this->prepareVersion($titleTemplate, $definition, $scope);

        return DB::transaction(function () use ($actor, $templateData, $normalized): LegalDocumentTemplate {
            $template = LegalDocumentTemplate::query()->create([
                'name' => trim((string) $templateData['name']),
                'slug' => Str::slug((string) $templateData['slug']),
                'description' => filled($templateData['description'] ?? null) ? trim((string) $templateData['description']) : null,
                'context_scope' => $templateData['context_scope'],
                'default_output_format' => $templateData['default_output_format'],
                'is_active' => (bool) ($templateData['is_active'] ?? false),
                'created_by' => $actor->id,
            ]);

            $version = $template->versions()->create($normalized + [
                'version' => 1,
                'created_by' => $actor->id,
            ]);

            activity_log('legal_document_templates', 'created', $template, [
                'version_id' => $version->id,
                'version' => 1,
                'content_sha256' => $version->content_sha256,
            ], 'Template jurídico criado com sua versão inicial imutável.');

            return $template->load('latestVersion');
        });
    }

    public function updateMetadata(User $actor, LegalDocumentTemplate $template, array $data): LegalDocumentTemplate
    {
        $this->authorize($actor);
        $this->validateMetadata($data, $template->context_scope);

        $template->fill([
            'name' => trim((string) $data['name']),
            'slug' => Str::slug((string) $data['slug']),
            'description' => filled($data['description'] ?? null) ? trim((string) $data['description']) : null,
            'context_scope' => $template->context_scope,
            'default_output_format' => $data['default_output_format'],
            'is_active' => (bool) ($data['is_active'] ?? false),
        ])->save();

        activity_log('legal_document_templates', 'updated', $template, [
            'metadata_only' => true,
        ], 'Metadados do template jurídico atualizados sem alterar versões publicadas.');

        return $template->refresh();
    }

    public function createVersion(
        User $actor,
        LegalDocumentTemplate $template,
        string $titleTemplate,
        array $definition
    ): LegalDocumentTemplateVersion {
        $this->authorize($actor);
        $normalized = $this->prepareVersion($titleTemplate, $definition, $template->context_scope);

        return DB::transaction(function () use ($actor, $template, $normalized): LegalDocumentTemplateVersion {
            $lockedTemplate = LegalDocumentTemplate::query()->whereKey($template->id)->lockForUpdate()->firstOrFail();
            $nextVersion = ((int) $lockedTemplate->versions()->max('version')) + 1;
            $version = $lockedTemplate->versions()->create($normalized + [
                'version' => $nextVersion,
                'created_by' => $actor->id,
            ]);

            activity_log('legal_document_templates', 'version_created', $lockedTemplate, [
                'version_id' => $version->id,
                'version' => $nextVersion,
                'content_sha256' => $version->content_sha256,
            ], 'Nova versão imutável do template jurídico publicada.');

            return $version;
        });
    }

    private function prepareVersion(string $titleTemplate, array $definition, string $scope): array
    {
        $titleTemplate = trim(str_replace("\0", '', $titleTemplate));
        if ($titleTemplate === '') {
            throw ValidationException::withMessages(['title_template' => 'O título do documento é obrigatório.']);
        }

        $normalizedDefinition = $this->definitions->normalize($definition);
        $allowedTokens = $this->tokens->extractFromVersion($titleTemplate, $normalizedDefinition);
        $this->tokens->assertAvailableForScope($allowedTokens, $scope);
        $content = [
            'title_template' => $titleTemplate,
            'definition' => $normalizedDefinition,
            'allowed_tokens' => $allowedTokens,
        ];

        return $content + [
            'content_sha256' => hash('sha256', $this->canonicalizer->json($content)),
        ];
    }

    private function validateMetadata(array $data, ?string $fixedScope = null): void
    {
        $name = trim((string) ($data['name'] ?? ''));
        $slug = Str::slug((string) ($data['slug'] ?? ''));
        $scope = $fixedScope ?? (string) ($data['context_scope'] ?? '');
        $format = (string) ($data['default_output_format'] ?? '');

        if ($name === '') {
            throw ValidationException::withMessages(['name' => 'O nome do template é obrigatório.']);
        }
        if ($slug === '') {
            throw ValidationException::withMessages(['slug' => 'O identificador do template é obrigatório.']);
        }
        if (! array_key_exists($scope, LegalDocumentTemplate::contextScopes())) {
            throw ValidationException::withMessages(['context_scope' => 'O contexto do template não é suportado.']);
        }
        if (! array_key_exists($format, LegalDocumentTemplate::outputFormats())) {
            throw ValidationException::withMessages(['default_output_format' => 'O formato padrão não é suportado.']);
        }
    }

    private function authorize(User $actor): void
    {
        if (! $actor->can('legal-document-templates.manage')) {
            throw new AuthorizationException('Você não pode administrar templates jurídicos.');
        }
    }
}
