<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'email',
    'phone',
    'area_interest',
    'subject',
    'message',
    'consent',
    'status',
    'source_page',
    'source_url',
    'referrer',
    'ip_address',
    'user_agent',
    'notes',
    'contacted_at',
    'viewed_at',
])]
class ContactMessage extends Model
{
    protected function casts(): array
    {
        return [
            'consent' => 'boolean',
            'contacted_at' => 'datetime',
            'viewed_at' => 'datetime',
        ];
    }
}
