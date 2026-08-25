<?php

namespace App\Services;

use App\Contracts\LegalProductivityProviderInterface;
use App\Models\IntegrationCredential;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class LegalProductivityProviderManager
{
    public function provider(): LegalProductivityProviderInterface
    {
        if (! Schema::hasTable('integration_credentials')) {
            throw new RuntimeException('A infraestrutura do provedor de IA ainda não foi instalada.');
        }

        $credential = IntegrationCredential::query()->where('service', 'legal_ai')->first();
        if (! $credential?->enabled || blank($credential->secret)) {
            throw new RuntimeException('O provedor de IA e transcrição não está configurado ou está desativado.');
        }

        $configuration = is_array($credential->configuration) ? $credential->configuration : [];
        $provider = (string) ($configuration['provider'] ?? 'openai_compatible');

        return match ($provider) {
            'openai_compatible' => new OpenAiCompatibleLegalProductivityProvider($configuration, (string) $credential->secret),
            default => throw new RuntimeException('O provedor configurado não é suportado.'),
        };
    }
}
