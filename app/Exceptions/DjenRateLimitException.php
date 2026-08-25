<?php

namespace App\Exceptions;

use Carbon\CarbonImmutable;
use RuntimeException;

class DjenRateLimitException extends RuntimeException
{
    public function __construct(
        public readonly CarbonImmutable $retryAt,
        public readonly ?int $limit = null,
        public readonly ?int $remaining = null,
    ) {
        parent::__construct('O limite de consultas do DJEN foi atingido. A sincronização poderá ser retomada após '.$retryAt->format('d/m/Y H:i:s').'.');
    }
}
