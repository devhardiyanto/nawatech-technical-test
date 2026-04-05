<?php

namespace App\Exceptions;

use RuntimeException;

class PaymentAlreadyProcessedException extends RuntimeException
{
    public function __construct(string $message = 'Payment for this order has already been processed.')
    {
        parent::__construct($message);
    }
}
