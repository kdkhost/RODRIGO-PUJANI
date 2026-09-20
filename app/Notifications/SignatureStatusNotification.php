<?php

namespace App\Notifications;

use App\Models\SignatureRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

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

        $message = (new MailMessage)
            ->subject('Assinatura eletrônica '.$label)
            ->line('A solicitação “'.$this->signatureRequest->title.'” foi '.$label.'.')
            ->line('Identificador de auditoria: '.$this->signatureRequest->public_uuid)
            ->line('Acesse o portal do cliente para consultar o status e, quando disponível, o comprovante.');

        if ($this->event === 'completed') {
            $this->signatureRequest->loadMissing('document');
            $document = $this->signatureRequest->document;

            if ($document?->completed_path && Storage::disk($document->disk)->exists($document->completed_path)) {
                $fileName = preg_replace('/[^\pL\pN\.\-_\s]+/u', '-', 'assinado-'.($document->original_name ?: 'documento.pdf')) ?: 'documento-assinado.pdf';
                $message
                    ->line('Uma cópia do documento assinado segue anexada a este e-mail.')
                    ->attach(Storage::disk($document->disk)->path($document->completed_path), [
                        'as' => $fileName,
                        'mime' => $document->mime_type ?: 'application/pdf',
                    ]);
            }
        }

        return $message;
    }
}
