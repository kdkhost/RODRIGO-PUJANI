<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'client_id', 'legal_case_id', 'calendar_event_id', 'uploaded_by', 'title',
    'original_name', 'disk', 'path', 'mime_type', 'extension', 'size', 'sha256',
    'duration_seconds', 'status', 'provider', 'provider_reference',
    'transcript_original', 'transcript_edited', 'minutes_draft', 'review_status',
    'reviewed_by', 'reviewed_at', 'approved_by', 'approved_at', 'processing_error',
    'metadata',
])]
class HearingTranscription extends Model
{
    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'duration_seconds' => 'integer',
            'reviewed_at' => 'datetime',
            'approved_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class);
    }

    public function calendarEvent(): BelongsTo
    {
        return $this->belongsTo(CalendarEvent::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (! $user || $user->canViewAllLegalOperations()) {
            return $query;
        }

        $userId = $user->id;

        return $query->where(function (Builder $builder) use ($userId): void {
            $builder
                ->where('uploaded_by', $userId)
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
