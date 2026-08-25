<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'legal_task_id',
    'task_id_snapshot',
    'user_id',
    'action',
    'changes',
    'snapshot',
    'source',
])]
class LegalTaskHistory extends Model
{
    protected function casts(): array
    {
        return [
            'changes' => 'array',
            'snapshot' => 'array',
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
