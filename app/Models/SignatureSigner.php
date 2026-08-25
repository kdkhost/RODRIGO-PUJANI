<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SignatureSigner extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return ['token_expires_at' => 'datetime', 'sent_at' => 'datetime', 'viewed_at' => 'datetime', 'signed_at' => 'datetime', 'declined_at' => 'datetime'];
    }

    public function signatureRequest(): BelongsTo
    {
        return $this->belongsTo(SignatureRequest::class);
    }
}
