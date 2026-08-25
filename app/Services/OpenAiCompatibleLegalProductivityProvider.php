<?php

namespace App\Services;

use App\Contracts\LegalProductivityProviderInterface;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiCompatibleLegalProductivityProvider implements LegalProductivityProviderInterface
{
    /** @param array<string, mixed> $configuration */
    public function __construct(
        private readonly array $configuration,
        private readonly string $apiKey,
    ) {
    }

    public function summarize(string $source): array
    {
        return $this->chat(
            'Produza um resumo claro em português brasileiro para um cliente de escritório de advocacia. '
            .'Não invente fatos, datas, prazos ou conclusões. Explique somente o conteúdo fornecido, '
            .'mantenha ressalva de revisão jurídica e não inclua dados técnicos internos.',
            $source,
        );
    }

    public function transcribe(string $absolutePath, string $mimeType): array
    {
        $model = (string) ($this->configuration['transcription_model'] ?? 'whisper-1');
        $response = $this->request()
            ->attach('file', fopen($absolutePath, 'rb'), basename($absolutePath), ['Content-Type' => $mimeType])
            ->post($this->endpoint('/audio/transcriptions'), [
                'model' => $model,
                'language' => 'pt',
                'response_format' => 'json',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('O provedor de transcrição não concluiu o processamento.');
        }

        $text = trim((string) $response->json('text'));
        if ($text === '') {
            throw new RuntimeException('O provedor de transcrição retornou conteúdo vazio.');
        }

        return [
            'text' => $text,
            'provider' => 'openai_compatible',
            'model' => $model,
            'reference' => $this->safeReference($response->header('x-request-id')),
            'metadata' => [],
        ];
    }

    public function draftMinutes(string $transcript): array
    {
        return $this->chat(
            'Estruture uma minuta de ata em português brasileiro usando apenas fatos presentes na transcrição. '
            .'Use as seções Participantes, Data e contexto, Assuntos, Decisões, Obrigações, Prazos e Próximos passos. '
            .'Quando algo não estiver identificável, escreva "Não identificado na transcrição". Não invente informações.',
            $transcript,
        );
    }

    /** @return array{text:string,provider:string,model:string,metadata:array<string,mixed>} */
    private function chat(string $instruction, string $source): array
    {
        $model = (string) ($this->configuration['chat_model'] ?? 'gpt-4.1-mini');
        $response = $this->request()->post($this->endpoint('/chat/completions'), [
            'model' => $model,
            'temperature' => 0.1,
            'messages' => [
                ['role' => 'system', 'content' => $instruction],
                ['role' => 'user', 'content' => $source],
            ],
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('O provedor de IA não concluiu a solicitação.');
        }

        $text = trim((string) $response->json('choices.0.message.content'));
        if ($text === '') {
            throw new RuntimeException('O provedor de IA retornou conteúdo vazio.');
        }

        return [
            'text' => $text,
            'provider' => 'openai_compatible',
            'model' => $model,
            'metadata' => ['request_id' => $this->safeReference($response->header('x-request-id'))],
        ];
    }

    private function request(): PendingRequest
    {
        return Http::acceptJson()
            ->withToken($this->apiKey)
            ->timeout((int) config('legal_productivity.ai.timeout_seconds', 120))
            ->retry(2, 500, throw: false);
    }

    private function endpoint(string $path): string
    {
        $baseUrl = rtrim((string) ($this->configuration['base_url'] ?? 'https://api.openai.com/v1'), '/');

        if (! str_starts_with($baseUrl, 'https://') && ! app()->environment('testing', 'local')) {
            throw new RuntimeException('A URL do provedor deve utilizar HTTPS.');
        }

        return $baseUrl.$path;
    }

    private function safeReference(mixed $value): ?string
    {
        $reference = trim((string) $value);

        return preg_match('/^[A-Za-z0-9._:-]{1,255}$/', $reference) === 1 ? $reference : null;
    }
}
