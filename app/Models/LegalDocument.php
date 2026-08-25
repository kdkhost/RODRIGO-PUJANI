<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'legal_case_id',
    'client_id',
    'uploaded_by',
    'title',
    'category',
    'original_name',
    'file_name',
    'path',
    'disk',
    'mime_type',
    'extension',
    'size',
    'sha256',
    'storage_status',
    'scanned_at',
    'notes',
    'is_sensitive',
    'shared_with_client',
])]
class LegalDocument extends Model
{
    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'is_sensitive' => 'boolean',
            'shared_with_client' => 'boolean',
            'scanned_at' => 'datetime',
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

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function signatureRequests(): HasMany
    {
        return $this->hasMany(SignatureRequest::class);
    }

    public function generation(): HasOne
    {
        return $this->hasOne(LegalDocumentGeneration::class);
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
                        ->orWhere('created_by', $userId)
                        ->orWhereHas('legalTasks', function (Builder $taskQuery) use ($userId): void {
                            $taskQuery
                                ->where('assigned_user_id', $userId)
                                ->orWhere('created_by', $userId);
                        });
                })
                ->orWhereHas('client', function (Builder $clientQuery) use ($userId): void {
                    $clientQuery
                        ->where('assigned_lawyer_id', $userId)
                        ->orWhere('created_by', $userId);
                });
        });
    }
}
