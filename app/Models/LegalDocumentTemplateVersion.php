<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

#[Fillable([
    'legal_document_template_id',
    'version',
    'title_template',
    'definition',
    'allowed_tokens',
    'content_sha256',
    'created_by',
])]
class LegalDocumentTemplateVersion extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'definition' => 'array',
            'allowed_tokens' => 'array',
            'version' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::updating(static function (): never {
            throw new LogicException('Versões publicadas de templates jurídicos são imutáveis.');
        });

        static::deleting(static function (): never {
            throw new LogicException('Versões publicadas de templates jurídicos não podem ser excluídas.');
        });
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(LegalDocumentTemplate::class, 'legal_document_template_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function generations(): HasMany
    {
        return $this->hasMany(LegalDocumentGeneration::class);
    }
}
