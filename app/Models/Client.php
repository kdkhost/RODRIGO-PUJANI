<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
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
])]
class Client extends Model
{
    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'metadata' => 'array',
            'is_active' => 'boolean',
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
}
