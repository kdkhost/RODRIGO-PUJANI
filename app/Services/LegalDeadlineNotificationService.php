<?php

namespace App\Services;

use App\Jobs\SendDailyLegalDeadlineSummary;
use App\Jobs\SendLegalTaskReminder;
use App\Models\LegalDeadlinePreference;
use App\Models\LegalNotificationDelivery;
use App\Models\LegalTask;
use Illuminate\Support\Carbon;

class LegalDeadlineNotificationService
{
    public function queueDueReminders(?Carbon $reference = null): int
    {
        $reference ??= now();
        $queued = 0;

        LegalTask::query()
            ->with(['assignedUser.legalDeadlinePreference'])
            ->whereNotIn('status', ['done', 'canceled'])
            ->whereNotNull('due_at')
            ->whereNotNull('reminder_minutes')
            ->where('due_at', '>=', $reference->copy()->subDay())
            ->orderBy('id')
            ->chunkById(100, function ($tasks) use ($reference, &$queued): void {
                foreach ($tasks as $task) {
                    $user = $task->assignedUser;
                    $preference = $user?->legalDeadlinePreference;

                    if (! $user || blank($user->email) || ($preference && ! $preference->deadline_reminders_enabled)) {
                        continue;
                    }

                    $scheduledFor = $task->due_at->copy()->subMinutes((int) $task->reminder_minutes);

                    if ($scheduledFor->isFuture()) {
                        continue;
                    }

                    $deduplicationKey = hash('sha256', implode('|', [
                        'task-reminder',
                        $task->id,
                        $task->due_at->toIso8601String(),
                        (int) $task->reminder_minutes,
                        mb_strtolower((string) $user->email),
                    ]));

                    $delivery = LegalNotificationDelivery::query()->firstOrCreate(
                        ['deduplication_key' => $deduplicationKey],
                        [
                            'legal_task_id' => $task->id,
                            'user_id' => $user->id,
                            'type' => 'task_reminder',
                            'channel' => 'mail',
                            'status' => LegalNotificationDelivery::STATUS_PENDING,
                            'scheduled_for' => $scheduledFor,
                            'metadata' => ['due_at' => $task->due_at->toIso8601String()],
                        ],
                    );

                    if ($delivery->wasRecentlyCreated
                        || ($delivery->status === LegalNotificationDelivery::STATUS_FAILED && $delivery->attempts < 3)) {
                        SendLegalTaskReminder::dispatch($delivery->id);
                        $queued++;
                    }
                }
            });

        return $queued;
    }

    public function queueDailySummaries(?Carbon $reference = null): int
    {
        $reference ??= now();
        $queued = 0;

        LegalDeadlinePreference::query()
            ->with('user')
            ->where('daily_summary_enabled', true)
            ->orderBy('id')
            ->chunkById(100, function ($preferences) use ($reference, &$queued): void {
                foreach ($preferences as $preference) {
                    $user = $preference->user;

                    if (! $user || blank($preference->email ?: $user->email)) {
                        continue;
                    }

                    $timezone = $preference->timezone ?: $user->timezone ?: config('app.timezone');
                    $localNow = $reference->copy()->timezone($timezone);
                    $summaryTime = Carbon::parse($localNow->toDateString().' '.$preference->daily_summary_time, $timezone);

                    if ($localNow->lt($summaryTime)) {
                        continue;
                    }

                    $deduplicationKey = hash('sha256', 'daily-summary|'.$user->id.'|'.$localNow->toDateString());
                    $delivery = LegalNotificationDelivery::query()->firstOrCreate(
                        ['deduplication_key' => $deduplicationKey],
                        [
                            'user_id' => $user->id,
                            'type' => 'daily_summary',
                            'channel' => 'mail',
                            'status' => LegalNotificationDelivery::STATUS_PENDING,
                            'scheduled_for' => $summaryTime->utc(),
                            'metadata' => ['summary_date' => $localNow->toDateString(), 'timezone' => $timezone],
                        ],
                    );

                    if ($delivery->wasRecentlyCreated
                        || ($delivery->status === LegalNotificationDelivery::STATUS_FAILED && $delivery->attempts < 3)) {
                        SendDailyLegalDeadlineSummary::dispatch($delivery->id);
                        $queued++;
                    }
                }
            });

        return $queued;
    }
}
