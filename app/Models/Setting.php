<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'group',
    'key',
    'label',
    'type',
    'value',
    'json_value',
    'is_public',
    'sort_order',
])]
class Setting extends Model
{
    protected function casts(): array
    {
        return [
            'json_value' => 'array',
            'is_public' => 'boolean',
        ];
    }
}
