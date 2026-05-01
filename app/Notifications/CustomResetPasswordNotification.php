<?php

namespace App\Notifications;

use App\Support\SystemMailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Config;

class CustomResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $token,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $config = smtp_config();
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        if ($config['enabled']) {
            Config::set('mail.default', $config['mailer']);
            Config::set('mail.mailers.smtp.host', $config['host']);
            Config::set('mail.mailers.smtp.port', (int) $config['port']);
            Config::set('mail.mailers.smtp.encryption', $config['encryption'] === 'none' ? null : $config['encryption']);
            Config::set('mail.mailers.smtp.username', $config['username']);
            Config::set('mail.mailers.smtp.password', $config['password']);
            Config::set('mail.from.address', $config['from_address']);
            Config::set('mail.from.name', $config['from_name']);
        }

        $variables = [
            'name' => (string) ($notifiable->name ?? 'Cliente'),
            'email' => (string) ($notifiable->email ?? ''),
            'reset_url' => $url,
            'app_name' => (string) config('app.name'),
            'from_name' => (string) $config['from_name'],
            'year' => (string) now()->year,
        ];

        $subject = SystemMailTemplate::compile((string) $config['template_reset_subject'], $variables);
        $header = SystemMailTemplate::compile((string) $config['template_header'], $variables);
        $body = SystemMailTemplate::compile((string) $config['template_reset_body'], $variables);
        $footer = SystemMailTemplate::compile((string) $config['template_footer'], $variables);

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.custom-system', [
                'subject' => $subject,
                'header' => $header,
                'body' => $body,
                'footer' => $footer,
                'actionUrl' => $url,
                'actionLabel' => 'Redefinir senha',
            ]);
    }
}

