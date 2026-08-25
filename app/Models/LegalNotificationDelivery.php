<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'legal_task_id',
    'user_id',
    'type',
    'channel',
    'deduplication_key',
    'status',
    'attempts',
    'scheduled_for',
    'started_at',
    'sent_at',
    'failed_at',
    'last_error',
    'metadata',
])]
class LegalNotificationDelivery extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'scheduled_for' => 'datetime',
            'started_at' => 'datetime',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function legalTask(): BelongsTo
    {
        return $this->belongsTo(LegalTask::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
