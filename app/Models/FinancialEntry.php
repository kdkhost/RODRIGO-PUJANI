<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'client_id',
    'legal_case_id',
    'responsible_user_id',
    'created_by',
    'installment_group',
    'installment_number',
    'installment_count',
    'entry_type',
    'category',
    'description',
    'reference',
    'amount',
    'due_date',
    'paid_at',
    'status',
    'payment_method',
    'notes',
    'metadata',
])]
class FinancialEntry extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'due_date' => 'date',
            'paid_at' => 'datetime',
            'installment_number' => 'integer',
            'installment_count' => 'integer',
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

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
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
                ->where('responsible_user_id', $userId)
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

    public function effectiveStatus(): string
    {
        if ($this->status === 'pending' && $this->due_date?->isPast()) {
            return 'overdue';
        }

        return $this->status;
    }
}
