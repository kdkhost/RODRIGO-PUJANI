<?php

namespace App\Services;

use App\Models\LegalCase;
use App\Models\LegalCaseUpdate;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class LegalCaseDataJudSyncService
{
    public function __construct(
        private readonly DataJudClient $dataJudClient,
    ) {
    }

    public function sync(LegalCase $legalCase, ?int $userId = null): array
    {
        if (blank($legalCase->process_number) || blank($legalCase->tribunal_alias)) {
            throw new RuntimeException('Preencha o número CNJ e o alias do tribunal antes de sincronizar.');
        }

        $source = $this->dataJudClient->searchProcess($legalCase->tribunal_alias, $legalCase->process_number);

        if (! $source) {
            throw new RuntimeException('Nenhum processo correspondente foi encontrado no DataJud.');
        }

        $movements = collect(data_get($source, 'movimentos', []))
            ->filter(fn ($movement) => filled(data_get($movement, 'dataHora')) && filled(data_get($movement, 'nome')))
            ->sortBy(fn ($movement) => data_get($movement, 'dataHora'))
            ->values();

        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($legalCase, $source, $movements, $userId, &$created, &$updated): void {
            foreach ($movements as $movement) {
                $externalId = $this->movementExternalId($legalCase, $movement);

                $update = LegalCaseUpdate::query()->firstOrNew([
                    'legal_case_id' => $legalCase->id,
                    'external_id' => $externalId,
                ]);

                $payload = [
                    'client_id' => $legalCase->client_id,
                    'created_by' => $update->created_by ?: $userId,
                    'source' => 'datajud',
                    'update_type' => 'movimentacao',
                    'title' => (string) data_get($movement, 'nome', 'Movimentação processual'),
                    'body' => $this->movementBody($movement),
                    'occurred_at' => Carbon::parse((string) data_get($movement, 'dataHora')),
                    'is_visible_to_client' => true,
                    'metadata' => [
                        'code' => data_get($movement, 'codigo'),
                        'tribunal' => data_get($source, 'tribunal'),
                        'grau' => data_get($source, 'grau'),
                        'orgao_julgador' => data_get($source, 'orgaoJulgador'),
                        'raw' => $movement,
                    ],
                ];

                $wasExisting = $update->exists;

                $update->fill($payload);

                if ($update->isDirty() || ! $wasExisting) {
                    $update->save();
                    $wasExisting ? $updated++ : $created++;
                }
            }

            $legalCase->forceFill([
                'datajud_last_synced_at' => now(),
                'latest_court_update_at' => filled(data_get($source, 'dataHoraUltimaAtualizacao'))
                    ? Carbon::parse((string) data_get($source, 'dataHoraUltimaAtualizacao'))
                    : $legalCase->latest_court_update_at,
                'court_name' => $legalCase->court_name ?: data_get($source, 'tribunal'),
            ])->save();
        });

        return [
            'process_number' => data_get($source, 'numeroProcesso'),
            'created' => $created,
            'updated' => $updated,
            'movements' => $movements->count(),
            'last_update_at' => data_get($source, 'dataHoraUltimaAtualizacao'),
        ];
    }

    private function movementExternalId(LegalCase $legalCase, array $movement): string
    {
        return sha1(implode('|', [
            $legalCase->id,
            data_get($movement, 'codigo', '0'),
            data_get($movement, 'nome', ''),
            data_get($movement, 'dataHora', ''),
        ]));
    }

    private function movementBody(array $movement): string
    {
        $complements = collect(data_get($movement, 'complementosTabelados', []))
            ->map(function ($item): ?string {
                $name = trim((string) data_get($item, 'nome'));
                $description = trim((string) data_get($item, 'descricao'));
                $value = trim((string) data_get($item, 'valor'));
                $text = trim(implode(': ', array_filter([$name !== '' ? $name : $description, $value !== '' ? $value : null])));

                return $text !== '' ? $text : null;
            })
            ->filter()
            ->values();

        if ($complements->isEmpty()) {
            return '<p>Movimentação importada automaticamente do DataJud/CNJ.</p>';
        }

        $items = $complements
            ->map(fn (string $item): string => '<li>'.e($item).'</li>')
            ->implode('');

        return '<p>Movimentação importada automaticamente do DataJud/CNJ.</p><ul>'.$items.'</ul>';
    }
}
