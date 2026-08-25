<?php

namespace App\Notifications;

use App\Models\SignatureRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SignatureStatusNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly SignatureRequest $signatureRequest, public readonly string $event) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $label = match ($this->event) {
            'completed' => 'concluída', 'declined' => 'recusada', 'cancelled' => 'cancelada', 'expired' => 'expirada', default => 'atualizada'
        };

        return (new MailMessage)
            ->subject('Assinatura eletrônica '.$label)
            ->line('A solicitação “'.$this->signatureRequest->title.'” foi '.$label.'.')
            ->line('Identificador de auditoria: '.$this->signatureRequest->public_uuid)
            ->line('Acesse o portal do cliente para consultar o status e, quando disponível, o comprovante.');
    }
}
