<?php

namespace App\Exceptions;

use Exception;

class GarageAccessDeniedException extends Exception
{
    public function __construct(
        string $message = '',
        int $code = 403,
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            $message ?: __('messages.flash.garage_access_denied'),
            $code,
            $previous
        );
    }
}