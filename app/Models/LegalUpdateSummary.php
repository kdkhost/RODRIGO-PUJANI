<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'legal_case_update_id', 'version', 'source_sha256', 'summary_text', 'status',
    'provider', 'model', 'generation_metadata', 'generated_by', 'generated_at',
    'reviewed_by', 'reviewed_at', 'approved_by', 'approved_at', 'published_by',
    'published_at', 'rejection_reason',
])]
class LegalUpdateSummary extends Model
{
    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'generation_metadata' => 'array',
            'generated_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'approved_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function legalCaseUpdate(): BelongsTo
    {
        return $this->belongsTo(LegalCaseUpdate::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
