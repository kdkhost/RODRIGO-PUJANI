<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'legal_case_id',
    'client_id',
    'assigned_user_id',
    'title',
    'task_type',
    'priority',
    'status',
    'start_at',
    'due_at',
    'completed_at',
    'location',
    'reminder_minutes',
    'billable_minutes',
    'description',
    'result_notes',
    'created_by',
])]
class LegalTask extends Model
{
    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
            'reminder_minutes' => 'integer',
            'billable_minutes' => 'integer',
        ];
    }

    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (! $user || $user->canViewAllLegalOperations()) {
            return $query;
        }

        $userId = $user->id;

        return $query->where(function (Builder $builder) use ($userId): void {
            $builder
                ->where('assigned_user_id', $userId)
                ->orWhere('created_by', $userId)
                ->orWhereHas('legalCase', function (Builder $caseQuery) use ($userId): void {
                    $caseQuery
                        ->where('primary_lawyer_id', $userId)
                        ->orWhere('supervising_lawyer_id', $userId)
                        ->orWhere('created_by', $userId);
                })
                ->orWhereHas('client', function (Builder $clientQuery) use ($userId): void {
                    $clientQuery
                        ->where('assigned_lawyer_id', $userId)
                        ->orWhere('created_by', $userId);
                });
        });
    }
}
