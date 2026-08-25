<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'legal_case_id',
    'created_by',
    'type',
    'label',
    'process_number_normalized',
    'oab_number_normalized',
    'oab_state',
    'fingerprint',
    'enabled',
    'sync_interval_minutes',
    'lookback_days',
    'overlap_days',
    'starts_at',
    'last_attempt_at',
    'last_successful_sync_at',
    'rate_limited_until',
    'next_sync_at',
    'last_error',
    'metadata',
])]
class DjenMonitor extends Model
{
    public const TYPE_PROCESS = 'process';

    public const TYPE_OAB = 'oab';

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'sync_interval_minutes' => 'integer',
            'lookback_days' => 'integer',
            'overlap_days' => 'integer',
            'starts_at' => 'date',
            'last_attempt_at' => 'datetime',
            'last_successful_sync_at' => 'datetime',
            'rate_limited_until' => 'datetime',
            'next_sync_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function syncRuns(): HasMany
    {
        return $this->hasMany(DjenSyncRun::class, 'monitor_id');
    }

    public function publications(): BelongsToMany
    {
        return $this->belongsToMany(DjenPublication::class, 'djen_monitor_publication', 'monitor_id', 'publication_id')
            ->withPivot(['sync_run_id', 'first_seen_at', 'last_seen_at'])
            ->withTimestamps();
    }

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (! $user || $user->canViewAllLegalOperations()) {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($user): void {
            $builder->where('created_by', $user->id)
                ->orWhereHas('legalCase', fn (Builder $caseQuery) => $caseQuery->visibleTo($user));
        });
    }

    public function scopeDue(Builder $query): Builder
    {
        return $query
            ->where('enabled', true)
            ->where(fn (Builder $builder) => $builder->whereNull('next_sync_at')->orWhere('next_sync_at', '<=', now()))
            ->where(fn (Builder $builder) => $builder->whereNull('rate_limited_until')->orWhere('rate_limited_until', '<=', now()));
    }

    public function queryPayload(): array
    {
        $payload = $this->type === self::TYPE_PROCESS
            ? ['numeroProcesso' => $this->process_number_normalized]
            : ['numeroOab' => $this->oab_number_normalized, 'ufOab' => $this->oab_state];

        if ($this->starts_at) {
            $payload['dataDisponibilizacaoInicio'] = $this->starts_at->format('Y-m-d');
        } elseif ($this->lookback_days > 0) {
            $payload['dataDisponibilizacaoInicio'] = now()->subDays($this->lookback_days)->toDateString();
        }

        return array_filter($payload, fn ($value) => filled($value));
    }

    public static function normalizeProcessNumber(?string $value): string
    {
        return (string) preg_replace('/\D+/', '', (string) $value);
    }

    public static function normalizeOabNumber(?string $value): string
    {
        return strtoupper((string) preg_replace('/[^A-Za-z0-9]+/', '', (string) $value));
    }

    public static function normalizeOabState(?string $value): string
    {
        return strtoupper(trim((string) $value));
    }

    public static function fingerprintFor(string $type, ?string $processNumber = null, ?string $oabNumber = null, ?string $oabState = null): string
    {
        $identity = $type === self::TYPE_PROCESS
            ? 'process:'.self::normalizeProcessNumber($processNumber)
            : 'oab:'.self::normalizeOabNumber($oabNumber).':'.self::normalizeOabState($oabState);

        return hash('sha256', $identity);
    }
}
