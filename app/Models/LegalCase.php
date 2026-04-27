<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'client_id',
    'primary_lawyer_id',
    'supervising_lawyer_id',
    'title',
    'process_number',
    'internal_code',
    'practice_area',
    'counterparty',
    'court_name',
    'court_division',
    'court_city',
    'court_state',
    'status',
    'phase',
    'priority',
    'filing_date',
    'next_hearing_at',
    'next_deadline_at',
    'claim_amount',
    'contract_value',
    'success_fee_percent',
    'summary',
    'strategy_notes',
    'is_confidential',
    'is_active',
    'created_by',
])]
class LegalCase extends Model
{
    protected function casts(): array
    {
        return [
            'filing_date' => 'date',
            'next_hearing_at' => 'datetime',
            'next_deadline_at' => 'datetime',
            'claim_amount' => 'decimal:2',
            'contract_value' => 'decimal:2',
            'success_fee_percent' => 'decimal:2',
            'is_confidential' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function primaryLawyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'primary_lawyer_id');
    }

    public function supervisingLawyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervising_lawyer_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function legalTasks(): HasMany
    {
        return $this->hasMany(LegalTask::class);
    }

    public function legalDocuments(): HasMany
    {
        return $this->hasMany(LegalDocument::class);
    }
}
