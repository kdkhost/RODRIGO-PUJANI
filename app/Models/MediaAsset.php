<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'original_name',
    'file_name',
    'disk',
    'directory',
    'path',
    'extension',
    'mime_type',
    'size',
    'type',
    'alt_text',
    'caption',
    'metadata',
    'is_public',
    'uploaded_by',
])]
class MediaAsset extends Model
{
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'is_public' => 'boolean',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
