<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'title',
    'description',
    'location',
    'url',
    'category',
    'status',
    'visibility',
    'color',
    'text_color',
    'start_at',
    'end_at',
    'all_day',
    'editable',
    'overlap',
    'display',
    'extended_props',
    'owner_id',
    'created_by',
])]
class CalendarEvent extends Model
{
    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'all_day' => 'boolean',
            'editable' => 'boolean',
            'overlap' => 'boolean',
            'extended_props' => 'array',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
