<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'client_id',
    'legal_case_id',
    'sender_user_id',
    'sender_type',
    'subject',
    'message',
    'read_by_client_at',
    'read_by_staff_at',
])]
class PortalMessage extends Model
{
    protected function casts(): array
    {
        return [
            'read_by_client_at' => 'datetime',
            'read_by_staff_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class);
    }

    public function senderUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }
}
