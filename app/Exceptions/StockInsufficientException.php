<?php

namespace App\Exceptions;

use Exception;

class StockInsufficientException extends Exception
{
    public function __construct(
        string $message = '',
        int $code = 422,
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            $message ?: __('messages.flash.stock_insufficient_generic'),
            $code,
            $previous
        );
    }
}