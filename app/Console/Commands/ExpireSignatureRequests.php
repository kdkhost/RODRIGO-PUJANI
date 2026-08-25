<?php

namespace App\Console\Commands;

use App\Models\SignatureRequest;
use App\Services\ElectronicSignatureService;
use Illuminate\Console\Command;

class ExpireSignatureRequests extends Command
{
    protected $signature = 'signatures:expire';

    protected $description = 'Expira solicitações de assinatura vencidas e invalida seus tokens';

    public function handle(ElectronicSignatureService $service): int
    {
        $count = 0;
        SignatureRequest::query()->whereIn('status', ['draft', 'pending'])->where('expires_at', '<', now())->chunkById(100, function ($items) use ($service, &$count): void {
            foreach ($items as $item) {
                if ($service->expire($item)) {
                    $count++;
                }
            }
        });
        $this->info("{$count} solicitação(ões) expirada(s).");

        return self::SUCCESS;
    }
}
