<?php

namespace App\Services;

use App\Exceptions\DjenRateLimitException;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class DjenClient
{
    private const BASE_URL = 'https://comunicaapi.pje.jus.br/api/v1';

    private const MAX_RESULTS = 10_000;

    private const VALID_PAGE_SIZES = [5, 100];

    /**
     * Mantém compatibilidade com o fluxo já existente e percorre todas as páginas.
     *
     * @return array<int, array<string, mixed>>
     */
    public function searchCommunications(string|array $filters): array
    {
        $items = [];
        $query = is_string($filters) ? ['numeroProcesso' => $filters] : $filters;

        $this->paginate($query, function (array $pageItems) use (&$items): void {
            array_push($items, ...$pageItems);
        });

        return $items;
    }

    /**
     * Percorre a paginação pública oficial do DJEN. Consultas por processo ou OAB
     * são limitadas pelo CNJ a 10.000 resultados e aceitam 5 ou 100 itens por página.
     *
     * @param  array<string, mixed>  $filters
     * @param  callable(array<int, array<string, mixed>>, int, array<string, mixed>): void  $consumePage
     * @return array{pages:int,items:int,rate_limit_limit:?int,rate_limit_remaining:?int}
     */
    public function paginate(array $filters, callable $consumePage, int $pageSize = 100, int $startPage = 1): array
    {
        if (! in_array($pageSize, self::VALID_PAGE_SIZES, true)) {
            throw new RuntimeException('A API oficial do DJEN aceita somente 5 ou 100 itens por página.');
        }

        if ($startPage < 1) {
            throw new RuntimeException('A página inicial da consulta ao DJEN deve ser maior que zero.');
        }

        $query = $this->normalizeFilters($filters);
        $maximumPages = (int) ceil(self::MAX_RESULTS / $pageSize);
        $pagesProcessed = 0;
        $itemsFetched = 0;
        $lastLimit = null;
        $lastRemaining = null;

        for ($page = $startPage; $page <= $maximumPages; $page++) {
            $pageResponse = $this->requestPage($query, $page, $pageSize);
            $items = $pageResponse['items'];
            $lastLimit = $pageResponse['rate_limit_limit'];
            $lastRemaining = $pageResponse['rate_limit_remaining'];

            if ($items === []) {
                break;
            }

            $consumePage($items, $page, $pageResponse);
            $pagesProcessed++;
            $itemsFetched += count($items);

            if (count($items) < $pageSize || $itemsFetched >= self::MAX_RESULTS) {
                break;
            }
        }

        return [
            'pages' => $pagesProcessed,
            'items' => $itemsFetched,
            'rate_limit_limit' => $lastLimit,
            'rate_limit_remaining' => $lastRemaining,
        ];
    }

    /** @return array{items:array<int, array<string, mixed>>,page:int,rate_limit_limit:?int,rate_limit_remaining:?int} */
    private function requestPage(array $filters, int $page, int $pageSize): array
    {
        try {
            $response = Http::acceptJson()
                ->connectTimeout(5)
                ->timeout(25)
                ->retry(3, 500, function (Throwable $exception, PendingRequest $request): bool {
                    if ($exception instanceof ConnectionException) {
                        return true;
                    }

                    return $exception instanceof RequestException && $exception->response->serverError();
                }, throw: false)
                ->get(self::BASE_URL.'/comunicacao', [
                    ...$filters,
                    'meio' => 'D',
                    'itensPorPagina' => $pageSize,
                    'pagina' => $page,
                ]);
        } catch (ConnectionException) {
            throw new RuntimeException('Não foi possível conectar à API pública do DJEN. Tente novamente em instantes.');
        }

        $limit = $this->positiveHeaderInteger($response->header('X-RateLimit-Limit'));
        $remaining = $this->nonNegativeHeaderInteger($response->header('X-RateLimit-Remaining'));

        if ($response->status() === 429) {
            throw new DjenRateLimitException(
                $this->retryAt($response->header('Retry-After')),
                $limit,
                $remaining,
            );
        }

        if (! $response->successful()) {
            throw new RuntimeException('A consulta pública ao DJEN falhou (HTTP '.$response->status().').');
        }

        $items = $response->json('items');

        if (! is_array($items)) {
            throw new RuntimeException('O DJEN retornou uma resposta em formato inesperado.');
        }

        foreach ($items as $item) {
            if (! is_array($item)) {
                throw new RuntimeException('O DJEN retornou uma comunicação em formato inesperado.');
            }
        }

        return [
            'items' => array_values($items),
            'page' => $page,
            'rate_limit_limit' => $limit,
            'rate_limit_remaining' => $remaining,
        ];
    }

    /** @param array<string, mixed> $filters */
    private function normalizeFilters(array $filters): array
    {
        $processNumber = preg_replace('/\D+/', '', (string) ($filters['numeroProcesso'] ?? $filters['process_number'] ?? ''));
        $oabNumber = strtoupper((string) preg_replace('/[^A-Za-z0-9]+/', '', (string) ($filters['numeroOab'] ?? $filters['oab_number'] ?? '')));
        $oabState = strtoupper(trim((string) ($filters['ufOab'] ?? $filters['oab_state'] ?? '')));

        if ($processNumber !== '') {
            if (strlen($processNumber) !== 20) {
                throw new RuntimeException('Informe um número CNJ válido, com 20 dígitos, antes de consultar o DJEN.');
            }

            $normalized = ['numeroProcesso' => $processNumber];
        } else {
            if ($oabNumber === '' || ! preg_match('/^[A-Z]{2}$/', $oabState)) {
                throw new RuntimeException('Informe o número da OAB e a UF para monitorar publicações do advogado.');
            }

            $normalized = ['numeroOab' => $oabNumber, 'ufOab' => $oabState];
        }

        foreach (['dataDisponibilizacaoInicio', 'dataDisponibilizacaoFim'] as $dateFilter) {
            $value = trim((string) ($filters[$dateFilter] ?? ''));

            if ($value !== '') {
                if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                    throw new RuntimeException('As datas da consulta ao DJEN devem usar o formato AAAA-MM-DD.');
                }

                $normalized[$dateFilter] = $value;
            }
        }

        return $normalized;
    }

    private function retryAt(?string $retryAfter): CarbonImmutable
    {
        $value = trim((string) $retryAfter);

        if ($value !== '' && ctype_digit($value)) {
            return CarbonImmutable::now()->addSeconds(max(60, min(3600, (int) $value)));
        }

        if ($value !== '') {
            try {
                $parsed = CarbonImmutable::parse($value);

                if ($parsed->isFuture()) {
                    return $parsed;
                }
            } catch (Throwable) {
                // O CNJ orienta aguardar ao menos um minuto após uma resposta 429.
            }
        }

        return CarbonImmutable::now()->addMinute();
    }

    private function positiveHeaderInteger(?string $value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    private function nonNegativeHeaderInteger(?string $value): ?int
    {
        return is_numeric($value) && (int) $value >= 0 ? (int) $value : null;
    }
}
