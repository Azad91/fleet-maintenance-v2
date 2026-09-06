<?php

namespace App\Exceptions;

use Exception;

class GarageAccessDeniedException extends Exception
{
    public function __construct(
        string $message = "Seçilmiş qaraja daxil olmaq üçün icazəniz yoxdur.",
        int $code = 403,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
