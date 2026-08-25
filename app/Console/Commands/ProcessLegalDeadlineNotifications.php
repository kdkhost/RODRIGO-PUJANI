<?php

namespace App\Console\Commands;

use App\Services\LegalDeadlineNotificationService;
use Illuminate\Console\Command;

class ProcessLegalDeadlineNotifications extends Command
{
    protected $signature = 'legal:process-deadline-notifications';

    protected $description = 'Enfileira lembretes idempotentes e resumos diários de prazos jurídicos.';

    public function handle(LegalDeadlineNotificationService $service): int
    {
        $reminders = $service->queueDueReminders();
        $summaries = $service->queueDailySummaries();

        $this->info("Processamento concluído: {$reminders} lembrete(s), {$summaries} resumo(s).");

        return self::SUCCESS;
    }
}
