<?php

namespace App\Exceptions;

use Carbon\CarbonImmutable;
use RuntimeException;

class DjenUnavailableException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly CarbonImmutable $retryAt,
        public readonly ?int $statusCode = null,
    ) {
        parent::__construct($message);
    }
}
