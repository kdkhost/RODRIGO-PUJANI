<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class SecurityAccessBlock extends Model
{
    protected $fillable = [
        'type',
        'value',
        'reason',
        'is_active',
        'blocked_by_user_id',
        'released_by_user_id',
        'expires_at',
        'released_at',
        'last_hit_at',
        'hits',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'expires_at' => 'datetime',
            'released_at' => 'datetime',
            'last_hit_at' => 'datetime',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(function (Builder $builder): void {
                $builder->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }
}

