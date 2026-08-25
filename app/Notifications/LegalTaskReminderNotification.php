<?php

namespace App\Notifications;

use App\Models\LegalTask;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LegalTaskReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly LegalTask $task)
    {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $dueAt = $this->task->due_at?->timezone($this->task->assignedUser?->timezone ?: config('app.timezone'));
        $message = (new MailMessage)
            ->subject('Lembrete de prazo: '.$this->task->title)
            ->greeting('Olá, '.($this->task->assignedUser?->name ?: 'profissional').'.')
            ->line('Há uma tarefa jurídica que exige sua atenção.')
            ->line('Tarefa: '.$this->task->title)
            ->line('Prazo: '.($dueAt?->format('d/m/Y H:i') ?: 'não informado'));

        if ($this->task->legalCase?->title) {
            $message->line('Processo: '.$this->task->legalCase->title);
        }

        if ($this->task->client?->name) {
            $message->line('Cliente: '.$this->task->client->name);
        }

        return $message
            ->action('Abrir tarefas e prazos', route('admin.legal-tasks.index'))
            ->line('Este aviso é enviado uma única vez para esta combinação de tarefa, prazo e antecedência.');
    }
}
