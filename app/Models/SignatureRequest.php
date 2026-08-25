<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SignatureRequest extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_DECLINED = 'declined';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_EXPIRED = 'expired';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['ordered' => 'boolean', 'expires_at' => 'datetime', 'sent_at' => 'datetime', 'completed_at' => 'datetime', 'cancelled_at' => 'datetime'];
    }

    public function document(): HasOne
    {
        return $this->hasOne(SignatureDocument::class);
    }

    public function signers(): HasMany
    {
        return $this->hasMany(SignatureSigner::class)->orderBy('signing_order');
    }

    public function events(): HasMany
    {
        return $this->hasMany(SignatureEvent::class)->orderBy('occurred_at');
    }

    public function legalDocument(): BelongsTo
    {
        return $this->belongsTo(LegalDocument::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_DECLINED, self::STATUS_CANCELLED, self::STATUS_EXPIRED], true);
    }
}
