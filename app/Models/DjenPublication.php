<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'last_sync_run_id',
    'legal_case_id',
    'client_id',
    'external_key',
    'communication_number',
    'source_hash',
    'process_number_normalized',
    'tribunal',
    'communication_type',
    'court_body',
    'document_type',
    'availability_date',
    'source_link',
    'raw_text',
    'raw_payload',
    'content_hash',
    'review_status',
    'reviewed_by',
    'reviewed_at',
    'review_notes',
    'legal_case_update_id',
    'discovered_at',
    'last_seen_at',
])]
class DjenPublication extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected function casts(): array
    {
        return [
            'availability_date' => 'date',
            'raw_payload' => 'array',
            'reviewed_at' => 'datetime',
            'discovered_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function lastSyncRun(): BelongsTo
    {
        return $this->belongsTo(DjenSyncRun::class, 'last_sync_run_id');
    }

    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function legalCaseUpdate(): BelongsTo
    {
        return $this->belongsTo(LegalCaseUpdate::class);
    }

    public function monitors(): BelongsToMany
    {
        return $this->belongsToMany(DjenMonitor::class, 'djen_monitor_publication', 'publication_id', 'monitor_id')
            ->withPivot(['sync_run_id', 'first_seen_at', 'last_seen_at'])
            ->withTimestamps();
    }

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (! $user || $user->canViewAllLegalOperations()) {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($user): void {
            $builder->whereHas('legalCase', fn (Builder $caseQuery) => $caseQuery->visibleTo($user))
                ->orWhereHas('monitors', fn (Builder $monitorQuery) => $monitorQuery->visibleTo($user));
        });
    }

    public function isPendingReview(): bool
    {
        return $this->review_status === self::STATUS_PENDING;
    }
}
