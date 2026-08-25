<?php

namespace App\Services;

use App\Models\CalendarEvent;
use App\Models\LegalTask;

class LegalTaskCalendarService
{
    private static bool $synchronizing = false;

    private static string $historySource = 'system';

    public static function isSynchronizing(): bool
    {
        return self::$synchronizing;
    }

    public static function historySource(): string
    {
        return self::$historySource;
    }

    public function syncTaskToCalendar(LegalTask $task): ?CalendarEvent
    {
        if (self::$synchronizing) {
            return $task->calendarEvent;
        }

        self::$synchronizing = true;
        self::$historySource = 'legal_task';

        try {
            $task->loadMissing('legalCase:id,client_id');
            $event = CalendarEvent::query()->firstOrNew(['legal_task_id' => $task->id]);

            if (! $task->start_at && ! $task->due_at) {
                if ($event->exists) {
                    $event->forceFill([
                        'status' => 'canceled',
                        'source' => 'legal_task',
                    ])->save();
                }

                return $event->exists ? $event : null;
            }

            $clientId = $task->legalCase?->client_id ?: $task->client_id;
            $startAt = $task->start_at ?: $task->due_at;
            $endAt = $task->start_at && $task->due_at && $task->due_at->greaterThanOrEqualTo($task->start_at)
                ? $task->due_at
                : null;

            $event->fill([
                'client_id' => $clientId,
                'legal_case_id' => $task->legal_case_id,
                'legal_task_id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'location' => $task->location,
                'category' => $this->categoryForTask($task),
                'event_type' => $task->task_type,
                'status' => $this->eventStatus($task->status),
                'visibility' => $event->visibility ?: 'team',
                'start_at' => $startAt,
                'end_at' => $endAt,
                'reminder_minutes' => $task->reminder_minutes,
                'all_day' => false,
                'editable' => true,
                'overlap' => true,
                'display' => 'auto',
                'owner_id' => $task->assigned_user_id,
                'created_by' => $event->created_by ?: $task->created_by,
                'source' => 'legal_task',
            ]);
            $event->save();

            return $event;
        } finally {
            self::$synchronizing = false;
            self::$historySource = 'system';
        }
    }

    public function syncCalendarEventToTask(CalendarEvent $event): ?LegalTask
    {
        if (self::$synchronizing || ! $event->legal_task_id) {
            return null;
        }

        $task = LegalTask::query()->find($event->legal_task_id);

        if (! $task) {
            return null;
        }

        self::$synchronizing = true;
        self::$historySource = 'calendar';

        try {
            $clientId = $event->legal_case_id
                ? $event->legalCase()->value('client_id')
                : $event->client_id;

            $task->fill([
                'legal_case_id' => $event->legal_case_id,
                'client_id' => $clientId,
                'assigned_user_id' => $event->owner_id,
                'title' => $event->title,
                'task_type' => $this->taskTypeForEvent($event->event_type),
                'status' => $this->taskStatus($event->status),
                'start_at' => $event->start_at,
                'due_at' => $event->end_at ?: $event->start_at,
                'location' => $event->location,
                'reminder_minutes' => $event->reminder_minutes,
                'description' => $event->description,
            ])->save();

            return $task;
        } finally {
            self::$synchronizing = false;
            self::$historySource = 'system';
        }
    }

    public function detachDeletedTask(LegalTask $task): void
    {
        CalendarEvent::query()
            ->where('legal_task_id', $task->id)
            ->update([
                'legal_task_id' => null,
                'status' => 'canceled',
                'source' => 'legal_task_deleted',
                'updated_at' => now(),
            ]);
    }

    private function categoryForTask(LegalTask $task): string
    {
        return match ($task->task_type) {
            'deadline' => 'Prazo',
            'hearing' => 'Audiência',
            'meeting' => 'Reunião',
            'filing' => 'Protocolo',
            'review' => 'Revisão',
            'follow_up' => 'Follow-up',
            default => 'Tarefa jurídica',
        };
    }

    private function eventStatus(?string $status): string
    {
        return match ($status) {
            'done' => 'done',
            'canceled' => 'canceled',
            'in_progress' => 'confirmed',
            default => 'scheduled',
        };
    }

    private function taskStatus(?string $status): string
    {
        return match ($status) {
            'done' => 'done',
            'canceled' => 'canceled',
            'confirmed' => 'in_progress',
            default => 'pending',
        };
    }

    private function taskTypeForEvent(?string $type): string
    {
        return in_array($type, ['deadline', 'hearing', 'meeting', 'filing', 'follow_up', 'review', 'internal'], true)
            ? (string) $type
            : 'follow_up';
    }
}
