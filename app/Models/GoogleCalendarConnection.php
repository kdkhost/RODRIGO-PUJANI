<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'google_account_email',
    'access_token',
    'refresh_token',
    'token_expires_at',
    'scopes',
    'calendar_id',
    'calendar_name',
    'sync_enabled',
    'sync_token',
    'last_synced_at',
    'last_success_at',
    'last_failure_at',
    'last_error',
    'metadata',
])]
#[Hidden(['access_token', 'refresh_token', 'sync_token'])]
class GoogleCalendarConnection extends Model
{
    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'sync_token' => 'encrypted',
            'token_expires_at' => 'datetime',
            'scopes' => 'array',
            'sync_enabled' => 'boolean',
            'last_synced_at' => 'datetime',
            'last_success_at' => 'datetime',
            'last_failure_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function eventMappings(): HasMany
    {
        return $this->hasMany(GoogleCalendarEventMapping::class);
    }
}
