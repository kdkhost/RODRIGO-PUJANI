<?php

namespace App\Jobs;

use App\Models\DjenMonitor;
use App\Models\DjenSyncRun;
use App\Services\DjenPublicationSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

class SyncDjenMonitor implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $uniqueFor = 600;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900, 1800];

    public function __construct(
        public readonly int $monitorId,
        public readonly ?int $requestedBy = null,
        public readonly string $trigger = 'scheduled',
    ) {
        $this->onQueue('integrations');
    }

    public function uniqueId(): string
    {
        return 'djen-monitor:'.$this->monitorId;
    }

    public function handle(DjenPublicationSyncService $service): void
    {
        $monitor = DjenMonitor::query()->find($this->monitorId);

        if (! $monitor || ! $monitor->enabled) {
            return;
        }

        $run = $service->syncMonitor($monitor, $this->requestedBy, $this->trigger);

        if ($run->retry_at?->isFuture() && $this->attempts() < $this->tries) {
            $this->release((int) max(60, now()->diffInSeconds($run->retry_at, false)));

            return;
        }

        if (in_array($run->status, [DjenSyncRun::STATUS_FAILED, DjenSyncRun::STATUS_PARTIAL], true)) {
            throw new RuntimeException($run->error_summary ?: 'A sincronização automática do DJEN não foi concluída.');
        }
    }
}
