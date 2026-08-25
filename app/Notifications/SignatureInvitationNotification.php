<?php

namespace App\Notifications;

use App\Models\SignatureSigner;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SignatureInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly SignatureSigner $signer, private readonly string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Documento disponível para assinatura eletrônica')
            ->greeting('Olá, '.$this->signer->name.'.')
            ->line('Você recebeu o documento “'.$this->signer->signatureRequest->title.'” para assinatura eletrônica.')
            ->action('Revisar e assinar', route('signatures.public.show', ['token' => $this->token]))
            ->line('O link é pessoal, confidencial e possui prazo de validade. Não o encaminhe a terceiros.');
    }
}
