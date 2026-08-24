<?php

namespace App\Contracts;

use App\Models\SignatureRequest;
use App\Models\SignatureSigner;

interface DocumentSignatureProviderInterface
{
    public function send(SignatureRequest $request): void;

    public function sign(SignatureSigner $signer, array $evidence): void;

    public function decline(SignatureSigner $signer, ?string $reason): void;

    public function cancel(SignatureRequest $request, string $reason): void;
}
