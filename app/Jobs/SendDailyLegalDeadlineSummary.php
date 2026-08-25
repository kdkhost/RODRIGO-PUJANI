<?php

namespace App\Jobs;

use App\Models\LegalNotificationDelivery;
use App\Models\LegalTask;
use App\Notifications\DailyLegalDeadlineSummaryNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Throwable;

class SendDailyLegalDeadlineSummary implements ShouldQueue
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
            $delivery->loadMissing('user.legalDeadlinePreference');
            $user = $delivery->user;
            $preference = $user?->legalDeadlinePreference;

            if (! $user || ! $preference?->daily_summary_enabled || blank($preference->email ?: $user->email)) {
                $delivery->update([
                    'status' => LegalNotificationDelivery::STATUS_FAILED,
                    'failed_at' => now(),
                    'last_error' => 'Resumo diário desativado ou sem destinatário.',
                ]);

                return;
            }

            $timezone = $preference->timezone ?: $user->timezone ?: config('app.timezone');
            $now = now($timezone);
            $limit = $now->copy()->addDays($preference->daily_summary_days_ahead)->endOfDay();
            $tasks = LegalTask::query()
                ->visibleTo($user)
                ->with(['legalCase:id,title', 'client:id,name'])
                ->whereNotIn('status', ['done', 'canceled'])
                ->whereNotNull('due_at')
                ->where('due_at', '<=', $limit->copy()->utc())
                ->orderBy('due_at')
                ->get();

            $summary = [
                'overdue' => $tasks->filter(fn (LegalTask $task): bool => $task->due_at->lt($now))->count(),
                'today' => $tasks->filter(fn (LegalTask $task): bool => $task->due_at->timezone($timezone)->isToday())->count(),
                'tomorrow' => $tasks->filter(fn (LegalTask $task): bool => $task->due_at->timezone($timezone)->isTomorrow())->count(),
                'upcoming' => $tasks->filter(fn (LegalTask $task): bool => $task->due_at->timezone($timezone)->isAfter($now->copy()->addDay()->endOfDay()))->count(),
                'items' => $tasks->take(20)->map(fn (LegalTask $task): array => [
                    'title' => $task->title,
                    'case' => $task->legalCase?->title,
                    'client' => $task->client?->name,
                    'when' => $task->due_at->timezone($timezone)->format('d/m/Y H:i'),
                ])->all(),
            ];

            $email = $preference->email ?: $user->email;
            Notification::route('mail', [$email => $user->name])
                ->notifyNow(new DailyLegalDeadlineSummaryNotification($summary, $now->format('d/m/Y')));
            $delivery->update([
                'status' => LegalNotificationDelivery::STATUS_SENT,
                'sent_at' => now(),
                'failed_at' => null,
                'last_error' => null,
                'metadata' => array_merge($delivery->metadata ?? [], ['summary' => collect($summary)->except('items')->all()]),
            ]);
        } catch (Throwable $exception) {
            $delivery->update([
                'status' => LegalNotificationDelivery::STATUS_FAILED,
                'failed_at' => now(),
                'last_error' => 'Falha sanitizada no envio do resumo ('.$exception::class.').',
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
