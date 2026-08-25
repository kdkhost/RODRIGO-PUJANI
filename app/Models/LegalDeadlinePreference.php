<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'deadline_reminders_enabled',
    'daily_summary_enabled',
    'daily_summary_time',
    'daily_summary_days_ahead',
    'timezone',
    'email',
])]
class LegalDeadlinePreference extends Model
{
    protected function casts(): array
    {
        return [
            'deadline_reminders_enabled' => 'boolean',
            'daily_summary_enabled' => 'boolean',
            'daily_summary_days_ahead' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
