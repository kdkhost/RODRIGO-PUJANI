<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class DjenClient
{
    private const BASE_URL = 'https://comunicaapi.pje.jus.br/api/v1';

    public function searchCommunications(string $processNumber): array
    {
        $normalizedProcessNumber = preg_replace('/\D+/', '', $processNumber);

        if (strlen((string) $normalizedProcessNumber) !== 20) {
            throw new RuntimeException('Informe um número CNJ válido, com 20 dígitos, antes de consultar o DJEN.');
        }

        try {
            $response = Http::acceptJson()
                ->connectTimeout(5)
                ->timeout(20)
                ->retry(2, 500, throw: false)
                ->get(self::BASE_URL.'/comunicacao', [
                    'numeroProcesso' => $normalizedProcessNumber,
                    'meio' => 'D',
                    'itensPorPagina' => 100,
                    'pagina' => 1,
                ]);
        } catch (ConnectionException) {
            throw new RuntimeException('Não foi possível conectar à API pública do DJEN. Tente novamente em instantes.');
        }

        if ($response->status() === 429) {
            throw new RuntimeException('O limite de consultas do DJEN foi atingido. Aguarde um minuto e tente novamente.');
        }

        if (! $response->successful()) {
            throw new RuntimeException('A consulta pública ao DJEN falhou (HTTP '.$response->status().').');
        }

        $items = $response->json('items');

        if (! is_array($items)) {
            throw new RuntimeException('O DJEN retornou uma resposta em formato inesperado.');
        }

        return $items;
    }
}
