<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class SignatureDocument extends Model { protected $guarded=['id']; public function signatureRequest(): BelongsTo { return $this->belongsTo(SignatureRequest::class); } public function legalDocument(): BelongsTo { return $this->belongsTo(LegalDocument::class); } }
