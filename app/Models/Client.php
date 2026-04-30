<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'person_type',
    'name',
    'trade_name',
    'document_number',
    'email',
    'phone',
    'whatsapp',
    'alternate_phone',
    'birth_date',
    'profession',
    'avatar_path',
    'address_zip',
    'address_street',
    'address_number',
    'address_complement',
    'address_district',
    'address_city',
    'address_state',
    'notes',
    'metadata',
    'assigned_lawyer_id',
    'created_by',
    'is_active',
    'portal_enabled',
    'portal_access_code',
    'portal_access_code_updated_at',
    'portal_last_login_at',
    'portal_last_login_ip',
])]
class Client extends Model
{
    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'metadata' => 'array',
            'is_active' => 'boolean',
            'portal_enabled' => 'boolean',
            'portal_access_code_updated_at' => 'datetime',
            'portal_last_login_at' => 'datetime',
        ];
    }

    public function assignedLawyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_lawyer_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function legalCases(): HasMany
    {
        return $this->hasMany(LegalCase::class);
    }

    public function legalTasks(): HasMany
    {
        return $this->hasMany(LegalTask::class);
    }

    public function legalDocuments(): HasMany
    {
        return $this->hasMany(LegalDocument::class);
    }

    public function legalCaseUpdates(): HasMany
    {
        return $this->hasMany(LegalCaseUpdate::class);
    }

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (! $user || $user->canViewAllLegalOperations()) {
            return $query;
        }

        $userId = $user->id;

        return $query->where(function (Builder $builder) use ($userId): void {
            $builder
                ->where('assigned_lawyer_id', $userId)
                ->orWhere('created_by', $userId)
                ->orWhereHas('legalCases', function (Builder $caseQuery) use ($userId): void {
                    $caseQuery
                        ->where('primary_lawyer_id', $userId)
                        ->orWhere('supervising_lawyer_id', $userId)
                        ->orWhere('created_by', $userId);
                })
                ->orWhereHas('legalTasks', function (Builder $taskQuery) use ($userId): void {
                    $taskQuery
                        ->where('assigned_user_id', $userId)
                        ->orWhere('created_by', $userId);
                })
                ->orWhereHas('legalDocuments', fn (Builder $documentQuery) => $documentQuery->where('uploaded_by', $userId))
                ->orWhereHas('legalCaseUpdates', fn (Builder $updateQuery) => $updateQuery->where('created_by', $userId));
        });
    }
}
