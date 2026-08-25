<?php

namespace App\Services;

use App\Exceptions\DjenRateLimitException;
use App\Models\DjenMonitor;
use App\Models\DjenPublication;
use App\Models\DjenSyncRun;
use App\Models\LegalCase;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use JsonException;
use Throwable;

class DjenPublicationSyncService
{
    public function __construct(private readonly DjenClient $client) {}

    public function syncMonitor(DjenMonitor $monitor, ?int $requestedBy = null, string $trigger = 'manual'): DjenSyncRun
    {
        $monitor->refresh();
        $queryPayload = $this->queryPayload($monitor);
        $run = DjenSyncRun::query()->create([
            'uuid' => (string) Str::uuid(),
            'monitor_id' => $monitor->id,
            'requested_by' => $requestedBy,
            'trigger' => $trigger,
            'status' => DjenSyncRun::STATUS_QUEUED,
            'query_payload' => $queryPayload,
        ]);

        if (! $monitor->enabled) {
            return $this->finishSkipped($run, 'O monitor está desabilitado.');
        }

        if ($monitor->rate_limited_until?->isFuture()) {
            return $this->finishRateLimited(
                $run,
                $monitor->rate_limited_until->toImmutable(),
                'O monitor ainda está no período de espera definido pelo DJEN.',
            );
        }

        $lock = Cache::lock('djen-monitor-sync:'.$monitor->id, 300);

        if (! $lock->get()) {
            return $this->finishSkipped($run, 'Já existe uma sincronização em andamento para este monitor.');
        }

        $run->forceFill([
            'status' => DjenSyncRun::STATUS_RUNNING,
            'started_at' => now(),
        ])->save();

        $counters = ['pages' => 0, 'fetched' => 0, 'created' => 0, 'updated' => 0, 'failed' => 0];
        $rateLimit = ['limit' => null, 'remaining' => null];

        try {
            $result = $this->client->paginate(
                $queryPayload,
                function (array $items, int $page, array $context) use ($monitor, $run, &$counters, &$rateLimit): void {
                    $pageStats = $this->persistPage($monitor, $run, $items);
                    $counters['pages']++;
                    $counters['fetched'] += count($items);
                    $counters['created'] += $pageStats['created'];
                    $counters['updated'] += $pageStats['updated'];
                    $rateLimit['limit'] = $context['rate_limit_limit'];
                    $rateLimit['remaining'] = $context['rate_limit_remaining'];

                    $run->forceFill([
                        'pages_processed' => $counters['pages'],
                        'items_fetched' => $counters['fetched'],
                        'items_created' => $counters['created'],
                        'items_updated' => $counters['updated'],
                        'rate_limit_limit' => $rateLimit['limit'],
                        'rate_limit_remaining' => $rateLimit['remaining'],
                    ])->save();
                },
            );

            $rateLimit['limit'] = $result['rate_limit_limit'];
            $rateLimit['remaining'] = $result['rate_limit_remaining'];

            $run->forceFill([
                'status' => $counters['failed'] > 0 ? DjenSyncRun::STATUS_PARTIAL : DjenSyncRun::STATUS_SUCCEEDED,
                'pages_processed' => $counters['pages'],
                'items_fetched' => $counters['fetched'],
                'items_created' => $counters['created'],
                'items_updated' => $counters['updated'],
                'items_failed' => $counters['failed'],
                'rate_limit_limit' => $rateLimit['limit'],
                'rate_limit_remaining' => $rateLimit['remaining'],
                'finished_at' => now(),
            ])->save();

            $monitor->forceFill([
                'last_attempt_at' => now(),
                'last_successful_sync_at' => now(),
                'rate_limited_until' => null,
                'next_sync_at' => now()->addMinutes($monitor->sync_interval_minutes),
                'last_error' => null,
            ])->save();
        } catch (DjenRateLimitException $exception) {
            $status = $counters['pages'] > 0 ? DjenSyncRun::STATUS_PARTIAL : DjenSyncRun::STATUS_RATE_LIMITED;
            $this->finishWithError($run, $status, $exception->getMessage(), $counters, $exception->retryAt, $exception->limit, $exception->remaining);
            $monitor->forceFill([
                'last_attempt_at' => now(),
                'rate_limited_until' => $exception->retryAt,
                'next_sync_at' => $exception->retryAt,
                'last_error' => $exception->getMessage(),
            ])->save();
        } catch (Throwable $exception) {
            $status = $counters['pages'] > 0 ? DjenSyncRun::STATUS_PARTIAL : DjenSyncRun::STATUS_FAILED;
            $this->finishWithError($run, $status, $exception->getMessage(), $counters);
            $monitor->forceFill([
                'last_attempt_at' => now(),
                'next_sync_at' => now()->addMinutes($monitor->sync_interval_minutes),
                'last_error' => Str::limit($exception->getMessage(), 2000, ''),
            ])->save();
        } finally {
            $lock->release();
        }

        return $run->refresh();
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array{created:int,updated:int}
     */
    private function persistPage(DjenMonitor $monitor, DjenSyncRun $run, array $items): array
    {
        return DB::transaction(function () use ($monitor, $run, $items): array {
            $created = 0;
            $updated = 0;

            foreach ($items as $item) {
                $result = $this->persistPublication($monitor, $run, $item);
                $created += (int) $result['created'];
                $updated += (int) $result['updated'];
            }

            return compact('created', 'updated');
        });
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array{created:bool,updated:bool,publication:DjenPublication}
     */
    private function persistPublication(DjenMonitor $monitor, DjenSyncRun $run, array $item): array
    {
        $externalKey = $this->externalKey($item);
        $existing = DjenPublication::query()->where('external_key', $externalKey)->first();
        $legalCase = $this->resolveLegalCase($monitor, $item) ?? $existing?->legalCase;
        $rawPayload = $this->encodeJson($item);
        $contentHash = hash('sha256', $this->encodeJson($this->canonicalize($item)));
        $now = now();

        $attributes = [
            'last_sync_run_id' => $run->id,
            'legal_case_id' => $legalCase?->id,
            'client_id' => $legalCase?->client_id,
            'external_key' => $externalKey,
            'communication_number' => $this->stringValue($item, ['numeroComunicacao', 'numero_comunicacao', 'id']),
            'source_hash' => $this->stringValue($item, ['hash']),
            'process_number_normalized' => $this->processNumber($item),
            'tribunal' => $this->stringValue($item, ['siglaTribunal', 'sigla_tribunal']),
            'communication_type' => $this->stringValue($item, ['tipoComunicacao', 'tipo_comunicacao']),
            'court_body' => $this->stringValue($item, ['nomeOrgao', 'orgao']),
            'document_type' => $this->stringValue($item, ['tipoDocumento', 'tipo_documento']),
            'availability_date' => $this->availabilityDate($item),
            'source_link' => $this->stringValue($item, ['link']),
            'raw_text' => $this->stringValue($item, ['texto']),
            'raw_payload' => $rawPayload,
            'content_hash' => $contentHash,
            'review_status' => DjenPublication::STATUS_PENDING,
            'discovered_at' => $existing?->discovered_at ?? $now,
            'last_seen_at' => $now,
            'created_at' => $existing?->created_at ?? $now,
            'updated_at' => $now,
        ];

        DjenPublication::query()->upsert(
            [$attributes],
            ['external_key'],
            [
                'last_sync_run_id',
                'legal_case_id',
                'client_id',
                'communication_number',
                'source_hash',
                'process_number_normalized',
                'tribunal',
                'communication_type',
                'court_body',
                'document_type',
                'availability_date',
                'source_link',
                'raw_text',
                'raw_payload',
                'content_hash',
                'last_seen_at',
                'updated_at',
            ],
        );

        $publication = DjenPublication::query()->where('external_key', $externalKey)->firstOrFail();

        DB::table('djen_monitor_publication')->upsert([
            [
                'monitor_id' => $monitor->id,
                'publication_id' => $publication->id,
                'sync_run_id' => $run->id,
                'first_seen_at' => $now,
                'last_seen_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['monitor_id', 'publication_id'], ['sync_run_id', 'last_seen_at', 'updated_at']);

        return [
            'created' => $existing === null,
            'updated' => $existing !== null && $existing->content_hash !== $contentHash,
            'publication' => $publication,
        ];
    }

    /** @param array<string, mixed> $item */
    private function resolveLegalCase(DjenMonitor $monitor, array $item): ?LegalCase
    {
        if ($monitor->legal_case_id) {
            return $monitor->legalCase()->first();
        }

        $processNumber = $this->processNumber($item);

        if (strlen($processNumber) !== 20) {
            return null;
        }

        return LegalCase::query()
            ->whereRaw(
                "REPLACE(REPLACE(REPLACE(REPLACE(process_number, '.', ''), '-', ''), '/', ''), ' ', '') = ?",
                [$processNumber],
            )
            ->first();
    }

    private function queryPayload(DjenMonitor $monitor): array
    {
        $payload = $monitor->queryPayload();

        if ($monitor->last_successful_sync_at) {
            $payload['dataDisponibilizacaoInicio'] = $monitor->last_successful_sync_at
                ->copy()
                ->subDays($monitor->overlap_days)
                ->toDateString();
        }

        return $payload;
    }

    /** @param array<string, mixed> $item */
    private function externalKey(array $item): string
    {
        $sourceHash = $this->stringValue($item, ['hash']);

        if (filled($sourceHash)) {
            return hash('sha256', 'hash:'.$sourceHash);
        }

        $communicationNumber = $this->stringValue($item, ['numeroComunicacao', 'numero_comunicacao', 'id']);

        if (filled($communicationNumber)) {
            return hash('sha256', 'communication:'.$communicationNumber);
        }

        return hash('sha256', $this->encodeJson([
            'process' => $this->processNumber($item),
            'date' => $this->stringValue($item, ['data_disponibilizacao', 'datadisponibilizacao']),
            'tribunal' => $this->stringValue($item, ['siglaTribunal', 'sigla_tribunal']),
            'type' => $this->stringValue($item, ['tipoComunicacao', 'tipo_comunicacao']),
            'text' => $this->stringValue($item, ['texto']),
            'link' => $this->stringValue($item, ['link']),
        ]));
    }

    /** @param array<string, mixed> $item */
    private function processNumber(array $item): string
    {
        return DjenMonitor::normalizeProcessNumber($this->stringValue($item, [
            'numero_processo',
            'numeroProcesso',
            'numeroprocessocommascara',
        ]));
    }

    /** @param array<string, mixed> $item */
    private function availabilityDate(array $item): ?string
    {
        $value = $this->stringValue($item, ['data_disponibilizacao', 'datadisponibilizacao', 'data_publicacao']);

        if (blank($value)) {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    /** @param array<string, mixed> $item @param array<int, string> $keys */
    private function stringValue(array $item, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $item) && $item[$key] !== null) {
                $value = trim((string) $item[$key]);

                return $value !== '' ? $value : null;
            }
        }

        return null;
    }

    private function canonicalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (! array_is_list($value)) {
            ksort($value);
        }

        return array_map(fn (mixed $item): mixed => $this->canonicalize($item), $value);
    }

    /** @throws JsonException */
    private function encodeJson(mixed $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    private function finishSkipped(DjenSyncRun $run, string $message): DjenSyncRun
    {
        $run->forceFill([
            'status' => DjenSyncRun::STATUS_SKIPPED,
            'error_summary' => $message,
            'started_at' => now(),
            'finished_at' => now(),
        ])->save();

        return $run->refresh();
    }

    private function finishRateLimited(DjenSyncRun $run, CarbonImmutable $retryAt, string $message): DjenSyncRun
    {
        $this->finishWithError($run, DjenSyncRun::STATUS_RATE_LIMITED, $message, [
            'pages' => 0,
            'fetched' => 0,
            'created' => 0,
            'updated' => 0,
            'failed' => 0,
        ], $retryAt);

        return $run->refresh();
    }

    /** @param array{pages:int,fetched:int,created:int,updated:int,failed:int} $counters */
    private function finishWithError(
        DjenSyncRun $run,
        string $status,
        string $message,
        array $counters,
        ?CarbonImmutable $retryAt = null,
        ?int $limit = null,
        ?int $remaining = null,
    ): void {
        $run->forceFill([
            'status' => $status,
            'pages_processed' => $counters['pages'],
            'items_fetched' => $counters['fetched'],
            'items_created' => $counters['created'],
            'items_updated' => $counters['updated'],
            'items_failed' => $counters['failed'],
            'rate_limit_limit' => $limit,
            'rate_limit_remaining' => $remaining,
            'retry_at' => $retryAt,
            'error_summary' => Str::limit($message, 2000, ''),
            'started_at' => $run->started_at ?? now(),
            'finished_at' => now(),
        ])->save();
    }
}
