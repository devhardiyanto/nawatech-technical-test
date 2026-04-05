<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public function __construct(
        private readonly array $details,
        string $message = 'Insufficient stock for one or more products.',
    ) {
        parent::__construct($message);
    }

    public function details(): array
    {
        return $this->details;
    }
}
