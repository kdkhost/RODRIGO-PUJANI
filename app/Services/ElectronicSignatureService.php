<?php

namespace App\Services;

use App\Models\SignatureRequest;
use App\Models\SignatureSigner;
use LogicException;

class ElectronicSignatureService
{
    public function send(SignatureRequest $request): void { throw new LogicException('Fluxo de envio ainda nao inicializado.'); }
    public function sign(SignatureSigner $signer, array $evidence): void { throw new LogicException('Fluxo de assinatura ainda nao inicializado.'); }
    public function decline(SignatureSigner $signer, ?string $reason): void { throw new LogicException('Fluxo de recusa ainda nao inicializado.'); }
    public function cancel(SignatureRequest $request, string $reason): void { throw new LogicException('Fluxo de cancelamento ainda nao inicializado.'); }
}
