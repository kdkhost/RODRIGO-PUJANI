<?php

namespace App\Services;

use App\Models\LegalCase;
use App\Models\LegalCaseUpdate;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LegalCaseDjenSyncService
{
    public function __construct(private readonly DjenClient $djenClient)
    {
    }

    public function sync(LegalCase $legalCase, ?int $userId = null): array
    {
        if (blank($legalCase->process_number)) {
            throw new RuntimeException('Preencha o número CNJ do processo antes de consultar o DJEN.');
        }

        $communications = collect($this->djenClient->searchCommunications($legalCase->process_number))
            ->filter(fn ($item): bool => is_array($item) && filled(data_get($item, 'data_disponibilizacao', data_get($item, 'datadisponibilizacao'))))
            ->sortBy(fn ($item) => data_get($item, 'data_disponibilizacao', data_get($item, 'datadisponibilizacao')))
            ->values();

        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($legalCase, $communications, $userId, &$created, &$updated): void {
            foreach ($communications as $communication) {
                $externalId = 'djen:'.($communication['hash'] ?? $communication['numeroComunicacao'] ?? $communication['numero_comunicacao'] ?? sha1(json_encode($communication)));
                $update = LegalCaseUpdate::query()->firstOrNew([
                    'legal_case_id' => $legalCase->id,
                    'external_id' => $externalId,
                ]);
                $wasExisting = $update->exists;
                $text = trim((string) data_get($communication, 'texto', ''));
                $type = trim((string) data_get($communication, 'tipoComunicacao', 'Comunicação processual'));
                $date = (string) data_get($communication, 'data_disponibilizacao', data_get($communication, 'datadisponibilizacao'));

                $update->fill([
                    'client_id' => $legalCase->client_id,
                    'created_by' => $update->created_by ?: $userId,
                    'source' => 'djen',
                    'update_type' => 'comunicacao',
                    'title' => $type !== '' ? 'DJEN: '.$type : 'DJEN: Comunicação processual',
                    'body' => $text !== '' ? '<p>'.nl2br(e($text), false).'</p>' : '<p>Comunicação importada do DJEN/CNJ.</p>',
                    'occurred_at' => Carbon::parse($date)->startOfDay(),
                    'is_visible_to_client' => true,
                    'metadata' => [
                        'communication_number' => data_get($communication, 'numeroComunicacao', data_get($communication, 'numero_comunicacao')),
                        'tribunal' => data_get($communication, 'siglaTribunal'),
                        'court_body' => data_get($communication, 'nomeOrgao'),
                        'document_type' => data_get($communication, 'tipoDocumento'),
                        'link' => data_get($communication, 'link'),
                        'hash' => data_get($communication, 'hash'),
                    ],
                ]);

                if ($update->isDirty() || ! $wasExisting) {
                    $update->save();
                    $wasExisting ? $updated++ : $created++;
                }
            }
        });

        return ['created' => $created, 'updated' => $updated, 'communications' => $communications->count()];
    }
}
