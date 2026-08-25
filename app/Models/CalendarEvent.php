<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'title',
    'client_id',
    'legal_case_id',
    'legal_task_id',
    'description',
    'location',
    'url',
    'category',
    'event_type',
    'status',
    'visibility',
    'shared_with_client',
    'google_sync_enabled',
    'source',
    'color',
    'text_color',
    'start_at',
    'end_at',
    'reminder_minutes',
    'all_day',
    'editable',
    'overlap',
    'display',
    'extended_props',
    'owner_id',
    'created_by',
])]
class CalendarEvent extends Model
{
    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'all_day' => 'boolean',
            'shared_with_client' => 'boolean',
            'google_sync_enabled' => 'boolean',
            'reminder_minutes' => 'integer',
            'editable' => 'boolean',
            'overlap' => 'boolean',
            'extended_props' => 'array',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class);
    }

    public function legalTask(): BelongsTo
    {
        return $this->belongsTo(LegalTask::class);
    }

    public function googleCalendarMappings(): HasMany
    {
        return $this->hasMany(GoogleCalendarEventMapping::class);
    }

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (! $user || $user->canViewAllLegalOperations()) {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($user): void {
            $builder
                ->where('owner_id', $user->id)
                ->orWhere(function (Builder $nested) use ($user): void {
                    $nested->whereNull('owner_id')->where('created_by', $user->id);
                });
        });
    }
}
