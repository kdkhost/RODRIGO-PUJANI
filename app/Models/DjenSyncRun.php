<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'uuid',
    'monitor_id',
    'requested_by',
    'trigger',
    'status',
    'query_payload',
    'pages_processed',
    'items_fetched',
    'items_created',
    'items_updated',
    'items_failed',
    'rate_limit_limit',
    'rate_limit_remaining',
    'retry_at',
    'error_summary',
    'started_at',
    'finished_at',
])]
class DjenSyncRun extends Model
{
    public const STATUS_QUEUED = 'queued';

    public const STATUS_RUNNING = 'running';

    public const STATUS_SUCCEEDED = 'succeeded';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_FAILED = 'failed';

    public const STATUS_RATE_LIMITED = 'rate_limited';

    public const STATUS_SKIPPED = 'skipped';

    protected function casts(): array
    {
        return [
            'query_payload' => 'array',
            'pages_processed' => 'integer',
            'items_fetched' => 'integer',
            'items_created' => 'integer',
            'items_updated' => 'integer',
            'items_failed' => 'integer',
            'rate_limit_limit' => 'integer',
            'rate_limit_remaining' => 'integer',
            'retry_at' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function monitor(): BelongsTo
    {
        return $this->belongsTo(DjenMonitor::class, 'monitor_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function publications(): HasMany
    {
        return $this->hasMany(DjenPublication::class, 'last_sync_run_id');
    }

    public function hasProcessedItems(): bool
    {
        return $this->pages_processed > 0 || $this->items_fetched > 0;
    }
}
