<?php

namespace App\Services;

use App\Contracts\DocumentSignatureProviderInterface;
use App\Models\SignatureRequest;
use App\Models\SignatureSigner;
use LogicException;

class InternalElectronicSignatureProvider implements DocumentSignatureProviderInterface
{
    public function __construct(private readonly ElectronicSignatureService $service) {}

    public function send(SignatureRequest $request): void { $this->service->send($request); }

    public function sign(SignatureSigner $signer, array $evidence): void { $this->service->sign($signer, $evidence); }

    public function decline(SignatureSigner $signer, ?string $reason): void { $this->service->decline($signer, $reason); }

    public function cancel(SignatureRequest $request, string $reason): void { $this->service->cancel($request, $reason); }
}
