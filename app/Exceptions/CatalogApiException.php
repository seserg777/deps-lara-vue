<?php

namespace App\Exceptions;

use RuntimeException;

class CatalogApiException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?string $status = null,
        public readonly ?int $code_api = null,
        public readonly ?string $report = null,
    ) {
        parent::__construct($message);
    }
}
