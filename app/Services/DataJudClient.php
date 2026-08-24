<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class DataJudClient
{
    public function searchProcess(string $tribunalAlias, string $processNumber): ?array
    {
        $alias = Str::of($tribunalAlias)
            ->trim()
            ->lower()
            ->replace('api_publica_', '')
            ->toString();
        $normalizedProcessNumber = preg_replace('/\D+/', '', $processNumber);

        if (preg_match('/^[a-z0-9-]+$/', $alias) !== 1 || strlen((string) $normalizedProcessNumber) !== 20) {
            throw new RuntimeException('Informe o alias do tribunal e o número CNJ do processo.');
        }

        $apiKey = $this->resolveApiKey();

        if (! $apiKey) {
            throw new RuntimeException('Não foi possível obter a chave pública atual do DataJud.');
        }

        $response = Http::connectTimeout(5)
            ->timeout(20)
            ->retry(2, 500, throw: false)
            ->acceptJson()
            ->withHeaders([
                'Authorization' => 'APIKey '.$apiKey,
                'Content-Type' => 'application/json',
            ])
            ->post("https://api-publica.datajud.cnj.jus.br/api_publica_{$alias}/_search", [
                'size' => 1,
                'query' => [
                    'match' => [
                        'numeroProcesso' => $normalizedProcessNumber,
                    ],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('A consulta ao DataJud falhou para o tribunal informado.');
        }

        return data_get($response->json(), 'hits.hits.0._source');
    }

    public function resolveApiKey(): ?string
    {
        $override = trim((string) setting('portal.datajud_api_key', ''));

        if ($override !== '') {
            return preg_replace('/^Authorization:\s*APIKey\s+/i', '', $override);
        }

        return Cache::remember('portal.datajud.public_key', now()->addHours(12), function (): ?string {
            $response = Http::connectTimeout(5)->timeout(15)->retry(2, 500, throw: false)->get('https://datajud-wiki.cnj.jus.br/api-publica/acesso/');

            if (! $response->ok()) {
                return null;
            }

            if (preg_match('/Authorization:\s*APIKey\s+([A-Za-z0-9+\/=:_\-]+)/', $response->body(), $matches) !== 1) {
                return null;
            }

            return trim($matches[1]);
        });
    }
}
