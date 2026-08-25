<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['service', 'enabled', 'configuration', 'secret', 'updated_by'])]
class IntegrationCredential extends Model
{
    protected $hidden = ['secret'];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'configuration' => 'array',
            'secret' => 'encrypted',
        ];
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
