<?php

namespace App\Jobs;

use App\Models\LegalNotificationDelivery;
use App\Notifications\LegalTaskReminderNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Throwable;

class SendLegalTaskReminder implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public function __construct(public readonly int $deliveryId)
    {
        $this->afterCommit();
    }

    public function handle(): void
    {
        $delivery = $this->claimDelivery();

        if (! $delivery) {
            return;
        }

        try {
            $delivery->loadMissing('legalTask.assignedUser', 'legalTask.legalCase', 'legalTask.client');
            $task = $delivery->legalTask;
            $recipient = $task?->assignedUser;

            if (! $task || ! $recipient || blank($recipient->email) || in_array($task->status, ['done', 'canceled'], true)) {
                $delivery->update([
                    'status' => LegalNotificationDelivery::STATUS_FAILED,
                    'failed_at' => now(),
                    'last_error' => 'Destinatário ou tarefa indisponível.',
                ]);

                return;
            }

            $email = $recipient->legalDeadlinePreference?->email ?: $recipient->email;
            Notification::route('mail', [$email => $recipient->name])
                ->notifyNow(new LegalTaskReminderNotification($task));
            $delivery->update([
                'status' => LegalNotificationDelivery::STATUS_SENT,
                'sent_at' => now(),
                'failed_at' => null,
                'last_error' => null,
            ]);
        } catch (Throwable $exception) {
            $delivery->update([
                'status' => LegalNotificationDelivery::STATUS_FAILED,
                'failed_at' => now(),
                'last_error' => 'Falha sanitizada no envio do lembrete ('.$exception::class.').',
            ]);

            throw $exception;
        }
    }

    private function claimDelivery(): ?LegalNotificationDelivery
    {
        return DB::transaction(function (): ?LegalNotificationDelivery {
            $delivery = LegalNotificationDelivery::query()->lockForUpdate()->find($this->deliveryId);

            if (! $delivery || $delivery->status === LegalNotificationDelivery::STATUS_SENT) {
                return null;
            }

            if ($delivery->status === LegalNotificationDelivery::STATUS_PROCESSING
                && $delivery->started_at?->greaterThan(now()->subMinutes(15))) {
                return null;
            }

            $delivery->forceFill([
                'status' => LegalNotificationDelivery::STATUS_PROCESSING,
                'attempts' => $delivery->attempts + 1,
                'started_at' => now(),
            ])->save();

            return $delivery;
        });
    }
}
