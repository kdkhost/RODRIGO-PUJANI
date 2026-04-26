<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'url',
    'path',
    'route_name',
    'page_title',
    'page_slug',
    'referrer',
    'session_id',
    'ip_hash',
    'device_type',
    'browser',
    'platform',
    'country',
    'payload',
    'visited_at',
])]
class PageVisit extends Model
{
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'visited_at' => 'datetime',
        ];
    }
}
