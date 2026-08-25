<?php

namespace App\Console\Commands;

use App\Jobs\SyncGoogleCalendar;
use App\Models\GoogleCalendarConnection;
use Illuminate\Console\Command;

class SyncGoogleCalendars extends Command
{
    protected $signature = 'google-calendar:sync {--user= : Sincroniza somente o usuário informado}';

    protected $description = 'Enfileira a sincronização idempotente das conexões ativas com o Google Calendar.';

    public function handle(): int
    {
        $query = GoogleCalendarConnection::query()->where('sync_enabled', true);

        if ($this->option('user')) {
            $query->where('user_id', (int) $this->option('user'));
        }

        $count = 0;
        $query->orderBy('id')->each(function (GoogleCalendarConnection $connection) use (&$count): void {
            SyncGoogleCalendar::dispatch($connection->id);
            $count++;
        });

        $this->info("{$count} conexão(ões) enfileirada(s) para sincronização.");

        return self::SUCCESS;
    }
}
