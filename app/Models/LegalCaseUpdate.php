<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'legal_case_id',
    'client_id',
    'created_by',
    'external_id',
    'source',
    'update_type',
    'title',
    'body',
    'occurred_at',
    'is_visible_to_client',
    'metadata',
])]
class LegalCaseUpdate extends Model
{
    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'is_visible_to_client' => 'boolean',
            'metadata' => 'array',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
