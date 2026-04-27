<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'legal_case_id',
    'client_id',
    'uploaded_by',
    'title',
    'category',
    'original_name',
    'file_name',
    'path',
    'mime_type',
    'extension',
    'size',
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
}
