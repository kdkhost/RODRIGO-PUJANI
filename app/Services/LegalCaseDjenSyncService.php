<?php

namespace App\Services;

use App\Models\DjenMonitor;
use App\Models\DjenSyncRun;
use App\Models\LegalCase;
use RuntimeException;

class LegalCaseDjenSyncService
{
    public function __construct(private readonly DjenPublicationSyncService $syncService) {}

    public function sync(LegalCase $legalCase, ?int $userId = null): array
    {
        $processNumber = DjenMonitor::normalizeProcessNumber($legalCase->process_number);

        if (strlen($processNumber) !== 20) {
            throw new RuntimeException('Preencha um número CNJ válido, com 20 dígitos, antes de consultar o DJEN.');
        }

        $fingerprint = DjenMonitor::fingerprintFor(DjenMonitor::TYPE_PROCESS, $processNumber);
        $monitor = DjenMonitor::query()->firstOrCreate(
            ['fingerprint' => $fingerprint],
            [
                'legal_case_id' => $legalCase->id,
                'created_by' => $userId,
                'type' => DjenMonitor::TYPE_PROCESS,
                'label' => 'Processo '.$legalCase->title,
                'process_number_normalized' => $processNumber,
                'enabled' => true,
                'sync_interval_minutes' => 60,
                'lookback_days' => 30,
                'overlap_days' => 2,
                'starts_at' => now()->subDays(30)->toDateString(),
            ],
        );

        if (! $monitor->legal_case_id) {
            $monitor->forceFill(['legal_case_id' => $legalCase->id])->save();
        }

        $run = $this->syncService->syncMonitor($monitor, $userId, 'manual');

        if (! in_array($run->status, [DjenSyncRun::STATUS_SUCCEEDED], true)) {
            throw new RuntimeException($run->error_summary ?: 'A sincronização do DJEN não foi concluída.');
        }

        return [
            'created' => $run->items_created,
            'updated' => $run->items_updated,
            'communications' => $run->items_fetched,
            'pending_review' => $run->items_created,
            'sync_run_id' => $run->id,
        ];
    }
}
