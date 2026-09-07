<?php

namespace App\Exceptions;

use Exception;

class StockInsufficientException extends Exception
{
    public function __construct(
        string $message = 'Anbarda kifayət qədər detal yoxdur.',
        int $code = 422,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
