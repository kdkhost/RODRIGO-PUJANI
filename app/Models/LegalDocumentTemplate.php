<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name',
    'slug',
    'description',
    'context_scope',
    'default_output_format',
    'is_active',
    'created_by',
])]
class LegalDocumentTemplate extends Model
{
    use SoftDeletes;

    public const CONTEXT_CLIENT = 'client';
    public const CONTEXT_CASE = 'case';
    public const CONTEXT_CLIENT_CASE = 'client_case';

    public const FORMAT_DOCX = 'docx';
    public const FORMAT_PDF = 'pdf';

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(LegalDocumentTemplateVersion::class);
    }

    public function latestVersion(): HasOne
    {
        return $this->hasOne(LegalDocumentTemplateVersion::class)->ofMany('version', 'max');
    }

    public function generations(): HasMany
    {
        return $this->hasMany(LegalDocumentGeneration::class);
    }

    public static function contextScopes(): array
    {
        return [
            self::CONTEXT_CLIENT => 'Somente cliente',
            self::CONTEXT_CASE => 'Somente processo',
            self::CONTEXT_CLIENT_CASE => 'Cliente e processo',
        ];
    }

    public static function outputFormats(): array
    {
        return [
            self::FORMAT_DOCX => 'Word (DOCX)',
            self::FORMAT_PDF => 'PDF',
        ];
    }
}
