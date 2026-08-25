<?php

namespace App\Console\Commands;

use App\Jobs\SyncDjenMonitor;
use App\Models\DjenMonitor;
use Illuminate\Console\Command;

class DispatchDueDjenMonitors extends Command
{
    protected $signature = 'djen:dispatch-due {--sync : Executa os jobs imediatamente, sem depender do worker}';

    protected $description = 'Enfileira os monitores DJEN habilitados e vencidos';

    public function handle(): int
    {
        $count = 0;

        DjenMonitor::query()
            ->due()
            ->orderBy('id')
            ->chunkById(100, function ($monitors) use (&$count): void {
                foreach ($monitors as $monitor) {
                    $job = new SyncDjenMonitor($monitor->id, null, 'scheduled');
                    $this->option('sync') ? dispatch_sync($job) : dispatch($job);
                    $count++;
                }
            });

        $this->info($count.' monitor(es) DJEN encaminhado(s) para sincronização.');

        return self::SUCCESS;
    }
}
