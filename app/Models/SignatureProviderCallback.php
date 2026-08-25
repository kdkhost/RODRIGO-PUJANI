<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SignatureProviderCallback extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['sanitized_payload' => 'array', 'processed_at' => 'datetime'];
    }
}
