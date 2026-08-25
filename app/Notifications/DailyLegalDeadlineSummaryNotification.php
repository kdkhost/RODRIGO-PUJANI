<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DailyLegalDeadlineSummaryNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly array $summary,
        public readonly string $summaryDate,
    ) {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Resumo jurídico diário - '.$this->summaryDate)
            ->greeting('Olá, '.($notifiable->name ?? 'profissional').'.')
            ->line('Confira os prazos sob sua responsabilidade:')
            ->line('Vencidos: '.($this->summary['overdue'] ?? 0).' | Hoje: '.($this->summary['today'] ?? 0).' | Amanhã: '.($this->summary['tomorrow'] ?? 0).' | Próximos: '.($this->summary['upcoming'] ?? 0));

        foreach (array_slice($this->summary['items'] ?? [], 0, 15) as $item) {
            $message->line('• '.$item['when'].' — '.$item['title'].($item['case'] ? ' — '.$item['case'] : ''));
        }

        return $message
            ->action('Abrir tarefas e prazos', route('admin.legal-tasks.index'))
            ->line('A antecedência e o horário deste resumo podem ser alterados nas preferências de prazos.');
    }
}
