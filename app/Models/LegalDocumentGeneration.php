<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable([
    'legal_document_template_id',
    'legal_document_template_version_id',
    'legal_document_id',
    'client_id',
    'legal_case_id',
    'generated_by',
    'context_scope',
    'output_format',
    'context_snapshot',
    'context_sha256',
    'template_sha256',
    'rendered_sha256',
    'generated_at',
])]
class LegalDocumentGeneration extends Model
{
    public const CREATED_AT = 'generated_at';
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'context_snapshot' => 'encrypted:array',
            'generated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(static function (): never {
            throw new LogicException('Registros de geração jurídica são imutáveis.');
        });

        static::deleting(static function (): never {
            throw new LogicException('Registros de geração jurídica não podem ser excluídos.');
        });
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(LegalDocumentTemplate::class, 'legal_document_template_id');
    }

    public function templateVersion(): BelongsTo
    {
        return $this->belongsTo(LegalDocumentTemplateVersion::class, 'legal_document_template_version_id');
    }

    public function legalDocument(): BelongsTo
    {
        return $this->belongsTo(LegalDocument::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (! $user || $user->canViewAllLegalOperations()) {
            return $query;
        }

        return $query->where(function (Builder $nested) use ($user): void {
            $nested
                ->where('generated_by', $user->id)
                ->orWhereHas('legalDocument', fn (Builder $documentQuery) => $documentQuery->visibleTo($user));
        });
    }
}
