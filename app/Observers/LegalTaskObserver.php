<?php

namespace App\Observers;

use App\Models\LegalTask;
use App\Models\LegalTaskHistory;
use App\Services\LegalTaskCalendarService;
use Illuminate\Support\Facades\Auth;

class LegalTaskObserver
{
    public function created(LegalTask $task): void
    {
        $this->record($task, 'created', $task->getAttributes());
        app(LegalTaskCalendarService::class)->syncTaskToCalendar($task);
    }

    public function updated(LegalTask $task): void
    {
        $changes = collect($task->getChanges())
            ->except(['updated_at'])
            ->mapWithKeys(fn (mixed $value, string $field): array => [
                $field => [
                    'from' => $task->getOriginal($field),
                    'to' => $value,
                ],
            ])
            ->all();

        if ($changes !== []) {
            $this->record($task, 'updated', $changes);
        }

        if (! LegalTaskCalendarService::isSynchronizing()) {
            app(LegalTaskCalendarService::class)->syncTaskToCalendar($task);
        }
    }

    public function deleted(LegalTask $task): void
    {
        $this->record($task, 'deleted', [], null);
        app(LegalTaskCalendarService::class)->detachDeletedTask($task);
    }

    private function record(LegalTask $task, string $action, array $changes, ?int $taskId = -1): void
    {
        LegalTaskHistory::query()->create([
            'legal_task_id' => $taskId === null ? null : $task->id,
            'task_id_snapshot' => $task->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'changes' => $changes !== [] ? $changes : null,
            'snapshot' => $task->attributesToArray(),
            'source' => LegalTaskCalendarService::historySource() !== 'system'
                ? LegalTaskCalendarService::historySource()
                : (app()->runningInConsole() ? 'console' : 'http'),
        ]);
    }
}
