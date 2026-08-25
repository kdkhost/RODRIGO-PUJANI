<?php

namespace App\Jobs;

use App\Models\GoogleCalendarConnection;
use App\Services\GoogleCalendarSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncGoogleCalendar implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public function __construct(public readonly int $connectionId)
    {
        $this->afterCommit();
    }

    public function handle(GoogleCalendarSyncService $service): void
    {
        $connection = GoogleCalendarConnection::query()->find($this->connectionId);

        if ($connection?->sync_enabled) {
            $service->sync($connection);
        }
    }
}
